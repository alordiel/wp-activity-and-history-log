<?php
/**
 * Database handling class for WP Activity Tracker
 */
class WPActivityTracker_Database {
    /**
     * @var string The name of the database table
     */
    private string $table_name;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'pp_activity_logger';
    }
    
    /**
     * Get the table name
     * 
     * @return string The table name
     */
    public function get_table_name(): string {
        return $this->table_name;
    }
    
    /**
     * Check if tables exist and create them if they don't
     */
    public function maybe_create_tables(): void {
        global $wpdb;
        
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'") === $this->table_name;
        
        if (!$table_exists) {
            $this->create_tables();
        }
    }
    
    /**
     * Create necessary database tables
     */
    public function create_tables(): void {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$this->table_name} (
            ID int(16) NOT NULL AUTO_INCREMENT,
            user_id int(16) NOT NULL DEFAULT 0,
            event_name varchar(255) NOT NULL,
            type varchar(255) NOT NULL,
            category varchar(255) NOT NULL,
            importance varchar(255) NOT NULL,
            note text,
            date datetime NOT NULL,
            PRIMARY KEY  (ID)
        ) $charset_collate;";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
    
    /**
     * Drop the plugin tables
     */
    public function drop_tables(): void {
        global $wpdb;
        
        $wpdb->query("DROP TABLE IF EXISTS {$this->table_name}");
    }
    
    /**
     * Insert a new event into the database
     * 
     * @param array $data Event data
     * @return int|false The ID of the inserted event or false on failure
     */
    public function insert_event(array $data): int|false {
        global $wpdb;
        
        // Ensure all required fields are present
        $required_fields = ['event_name', 'type', 'category', 'importance', 'date'];
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return false;
            }
        }
        
        // Set default user_id if not provided
        if (!isset($data['user_id'])) {
            $data['user_id'] = get_current_user_id();
        }
        
        // Set note to empty string if not provided
        if (!isset($data['note'])) {
            $data['note'] = '';
        }
        
        $result = $wpdb->insert(
            $this->table_name,
            [
                'user_id'    => $data['user_id'],
                'event_name' => $data['event_name'],
                'type'       => $data['type'],
                'category'   => $data['category'],
                'importance' => $data['importance'],
                'note'       => $data['note'],
                'date'       => $data['date']
            ],
            [
                '%d', // user_id
                '%s', // event_name
                '%s', // type
                '%s', // category
                '%s', // importance
                '%s', // note
                '%s'  // date
            ]
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Get events with filtering and pagination
     * 
     * @param array $args Query arguments
     * @return array Events data and total count
     */
    public function get_events(array $args = []): array {
        global $wpdb;
        
        $defaults = [
            'per_page' => 40,
            'page'     => 1,
            'search'   => '',
            'category' => '',
            'type'     => '',
            'importance' => '',
            'orderby'  => 'date',
            'order'    => 'DESC'
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        // Build WHERE clause
        $where = [];
        $where_values = [];
        
        if (!empty($args['search'])) {
            $where[] = "(event_name LIKE %s OR note LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        if (!empty($args['category'])) {
            $where[] = "category = %s";
            $where_values[] = $args['category'];
        }
        
        if (!empty($args['type'])) {
            $where[] = "type = %s";
            $where_values[] = $args['type'];
        }
        
        if (!empty($args['importance'])) {
            $where[] = "importance = %s";
            $where_values[] = $args['importance'];
        }
        
        $where_clause = !empty($where) ? "WHERE " . implode(' AND ', $where) : '';
        
        // Calculate offset
        $offset = ($args['page'] - 1) * $args['per_page'];
        
        // Validate and sanitize ordering parameters
        $allowed_orderby = ['date', 'event_name', 'category', 'importance', 'type'];
        $orderby = in_array($args['orderby'], $allowed_orderby) ? $args['orderby'] : 'date';
        $order = $args['order'] === 'ASC' ? 'ASC' : 'DESC';
        
        // Get total count
        $count_query = "SELECT COUNT(*) FROM {$this->table_name} $where_clause";
        $total = $wpdb->get_var($wpdb->prepare($count_query, $where_values));
        
        // Get events
        $query = "SELECT * FROM {$this->table_name} $where_clause ORDER BY $orderby $order LIMIT %d OFFSET %d";
        $prepared_values = array_merge($where_values, [$args['per_page'], $offset]);
        $events = $wpdb->get_results($wpdb->prepare($query, $prepared_values), ARRAY_A);
        
        return [
            'events' => $events,
            'total'  => (int) $total,
            'pages'  => ceil($total / $args['per_page'])
        ];
    }
    
    /**
     * Get a specific event by ID
     * 
     * @param int $id Event ID
     * @return array|null Event data or null if not found
     */
    public function get_event(int $id): ?array {
        global $wpdb;
        
        $query = "SELECT * FROM {$this->table_name} WHERE ID = %d";

	    return $wpdb->get_row($wpdb->prepare($query, $id), ARRAY_A);
    }

	/**
     * Update an existing event
     *
     * @param int $id Event ID
     * @param array $data Updated event data
     * @return bool Success or failure
     */
    public function update_event(int $id, array $data): bool {
        global $wpdb;

        // Get the existing event
        $existing_event = $this->get_event($id);
        if (!$existing_event) {
            return false;
        }

        // Only manual events can be updated
        if ($existing_event['type'] !== 'manual') {
            return false;
        }

        // Prepare the data for update
        $update_data = [];
        $update_format = [];

        // Update fields if provided
        if (isset($data['event_name'])) {
            $update_data['event_name'] = sanitize_text_field($data['event_name']);
            $update_format[] = '%s';
        }

        if (isset($data['category'])) {
            $update_data['category'] = sanitize_text_field($data['category']);
            $update_format[] = '%s';
        }

        if (isset($data['importance'])) {
            $update_data['importance'] = sanitize_text_field($data['importance']);
            $update_format[] = '%s';
        }

        if (isset($data['note'])) {
            $update_data['note'] = sanitize_textarea_field($data['note']);
            $update_format[] = '%s';
        }

        if (isset($data['date'])) {
            $update_data['date'] = sanitize_text_field($data['date']);
            $update_format[] = '%s';
        }

        // If no data to update, return true (no change needed)
        if (empty($update_data)) {
            return true;
        }

        // Update the event
        $result = $wpdb->update(
            $this->table_name,
            $update_data,
            ['ID' => $id],
            $update_format,
            ['%d']
        );

        return $result !== false;
    }

    /**
     * Delete an event
     *
     * @param int $id Event ID
     * @return bool Success or failure
     */
    public function delete_event(int $id): bool {
        global $wpdb;

        // Get the existing event
        $existing_event = $this->get_event($id);
        if (!$existing_event) {
            return false;
        }

        // Only manual events can be deleted
        if ($existing_event['type'] !== 'manual') {
            return false;
        }

        // Delete the event
        $result = $wpdb->delete(
            $this->table_name,
            ['ID' => $id],
            ['%d']
        );

        return $result !== false;
    }
}
