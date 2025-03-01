<?php
/**
 * Event Logger class for WP Activity Tracker
 */
class WPActivityTracker_EventLogger {
    /**
     * @var WPActivityTracker_Database Database instance
     */
    private WPActivityTracker_Database $db;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db = new WPActivityTracker_Database();
    }
    
    /**
     * Log an automatic event
     * 
     * @param string $event_name Event name
     * @param string $category Event category
     * @param string $importance Event importance (low, medium, high, critical)
     * @param string $note Optional note about the event
     * @param array $additional_data Additional data for context
     * @return int|false The ID of the logged event or false on failure
     */
    public function log_automatic_event(
        string $event_name,
        string $category,
        string $importance,
        string $note = '',
        array $additional_data = []
    ): int|false {
        // Prepare event data
        $event_data = [
            'user_id'    => 0, // Automatic events have user_id = 0
            'event_name' => sanitize_text_field($event_name),
            'type'       => 'automatic',
            'category'   => sanitize_text_field($category),
            'importance' => sanitize_text_field($importance),
            'note'       => sanitize_textarea_field($note),
            'date'       => current_time('mysql')
        ];
        
        // Insert event into database
        return $this->db->insert_event($event_data);
    }
    
    /**
     * Log a manual event
     * 
     * @param array $data Event data
     * @return int|false The ID of the logged event or false on failure
     */
    public function log_manual_event(array $data): int|false {
        // Validate and sanitize input data
        $event_data = [
            'user_id'    => get_current_user_id(),
            'event_name' => isset($data['event_name']) ? sanitize_text_field($data['event_name']) : '',
            'type'       => 'manual',
            'category'   => isset($data['category']) ? sanitize_text_field($data['category']) : '',
            'importance' => isset($data['importance']) ? sanitize_text_field($data['importance']) : '',
            'note'       => isset($data['note']) ? sanitize_textarea_field($data['note']) : '',
            'date'       => isset($data['date']) ? sanitize_text_field($data['date']) : current_time('mysql')
        ];
        
        // Insert event into database
        return $this->db->insert_event($event_data);
    }
    
    /**
     * Compare version numbers to determine importance
     * 
     * @param string $old_version Old version number
     * @param string $new_version New version number
     * @return string Importance level (medium or high)
     */
    public function determine_update_importance(string $old_version, string $new_version): string {
        // Extract major version numbers
        $old_parts = explode('.', $old_version);
        $new_parts = explode('.', $new_version);
        
        $old_major = isset($old_parts[0]) ? (int) $old_parts[0] : 0;
        $new_major = isset($new_parts[0]) ? (int) $new_parts[0] : 0;
        
        // If major version changed, it's high importance
        if ($new_major > $old_major) {
            return 'high';
        }
        
        // Otherwise it's medium importance
        return 'medium';
    }
    
    /**
     * Format version change note
     * 
     * @param string $old_version Old version number
     * @param string $new_version New version number
     * @param string $item_name Name of the updated item
     * @return string Formatted note
     */
    public function format_version_note(string $old_version, string $new_version, string $item_name): string {
        return sprintf(
            __('Updated %s from version %s to %s', 'wp-activity-tracker'),
            $item_name,
            $old_version,
            $new_version
        );
    }
}
