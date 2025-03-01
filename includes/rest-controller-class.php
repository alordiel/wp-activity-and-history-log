<?php
/**
 * REST API Controller for WP Activity Tracker
 */
class WPActivityTracker_RestController extends WP_REST_Controller {
    /**
     * @var string API namespace
     */
    protected string $namespace = 'wp-activity-tracker/v1';
    
    /**
     * @var WPActivityTracker_Database Database instance
     */
    private WPActivityTracker_Database $db;
    
    /**
     * @var WPActivityTracker_EventLogger Event logger instance
     */
    private WPActivityTracker_EventLogger $logger;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db = new WPActivityTracker_Database();
        $this->logger = new WPActivityTracker_EventLogger();
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes(): void {
        // Route for listing/filtering events
        register_rest_route(
            $this->namespace,
            '/events',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'get_events'],
                    'permission_callback' => [$this, 'get_items_permissions_check'],
                    'args'                => $this->get_collection_params()
                ]
            ]
        );
        
        // Route for creating manual events
        register_rest_route(
            $this->namespace,
            '/events',
            [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [$this, 'create_event'],
                    'permission_callback' => [$this, 'create_item_permissions_check'],
                    'args'                => $this->get_endpoint_args_for_item_schema(WP_REST_Server::CREATABLE)
                ]
            ]
        );
        
        // Route for retrieving a single event
        register_rest_route(
            $this->namespace,
            '/events/(?P<id>\d+)',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'get_event'],
                    'permission_callback' => [$this, 'get_item_permissions_check'],
                    'args'                => [
                        'id' => [
                            'validate_callback' => function($param) {
                                return is_numeric($param);
                            }
                        ]
                    ]
                ]
            ]
        );
        
        // Route for getting categories
        register_rest_route(
            $this->namespace,
            '/categories',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'get_categories'],
                    'permission_callback' => [$this, 'get_items_permissions_check']
                ]
            ]
        );
    }
    
    /**
     * Get events with filtering and pagination
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function get_events(WP_REST_Request $request): WP_REST_Response {
        $args = [
            'per_page'   => $request->get_param('per_page') ? (int) $request->get_param('per_page') : 40,
            'page'       => $request->get_param('page') ? (int) $request->get_param('page') : 1,
            'search'     => $request->get_param('search'),
            'category'   => $request->get_param('category'),
            'type'       => $request->get_param('type'),
            'importance' => $request->get_param('importance'),
            'orderby'    => $request->get_param('orderby') ?? 'date',
            'order'      => $request->get_param('order') ?? 'DESC'
        ];
        
        $result = $this->db->get_events($args);
        
        $events = [];
        foreach ($result['events'] as $event) {
            $event['user'] = $this->get_user_data($event['user_id']);
            $events[] = $this->prepare_item_for_response($event, $request);
        }
        
        $response = new WP_REST_Response($events, 200);
        
        $response->header('X-WP-Total', $result['total']);
        $response->header('X-WP-TotalPages', $result['pages']);
        
        return $response;
    }
    
    /**
     * Get a single event
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function get_event(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $id = (int) $request->get_param('id');
        
        $event = $this->db->get_event($id);
        
        if (!$event) {
            return new WP_Error(
                'rest_event_not_found',
                __('Event not found.', 'wp-activity-tracker'),
                ['status' => 404]
            );
        }
        
        $event['user'] = $this->get_user_data($event['user_id']);
        $data = $this->prepare_item_for_response($event, $request);
        
        return new WP_REST_Response($data, 200);
    }
    
    /**
     * Create a manual event
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function create_event(WP_REST_Request $request): WP_REST_Response|WP_Error {
        // Check nonce
        $nonce = $request->get_header('X-WP-Nonce');
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_Error(
                'rest_invalid_nonce',
                __('Invalid nonce.', 'wp-activity-tracker'),
                ['status' => 403]
            );
        }
        
        $data = [
            'event_name' => $request->get_param('event_name'),
            'category'   => $request->get_param('category'),
            'importance' => $request->get_param('importance'),
            'note'       => $request->get_param('note'),
            'date'       => $request->get_param('date') ?? current_time('mysql')
        ];
        
        // Validate required fields
        $required_fields = ['event_name', 'category', 'importance'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return new WP_Error(
                    'rest_missing_field',
                    sprintf(__('Missing required field: %s', 'wp-activity-tracker'), $field),
                    ['status' => 400]
                );
            }
        }
        
        // Log the event
        $result = $this->logger->log_manual_event($data);
        
        if (!$result) {
            return new WP_Error(
                'rest_event_creation_failed',
                __('Failed to create event.', 'wp-activity-tracker'),
                ['status' => 500]
            );
        }
        
        // Get the newly created event
        $event = $this->db->get_event($result);
        $event['user'] = $this->get_user_data($event['user_id']);
        $data = $this->prepare_item_for_response($event, $request);
        
        return new WP_REST_Response($data, 201);
    }
    
    /**
     * Get available categories
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function get_categories(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;
        $table_name = $this->db->get_table_name();
        
        // Get existing categories from the database
        $categories = $wpdb->get_col("SELECT DISTINCT category FROM {$table_name}");
        
        // Merge with default categories
        $default_categories = [
            'Plugin update',
            'Adding new plugin',
            'Activating plugin',
            'Deactivating plugin',
            'Deleting plugin',
            'WP core update',
            'Plugin settings change'
        ];
        
        $all_categories = array_unique(array_merge($default_categories, $categories ?: []));
        sort($all_categories);
        
        return new WP_REST_Response($all_categories, 200);
    }
    
    /**
     * Check if current user has permission to get items
     * 
     * @param WP_REST_Request $request Request object
     * @return bool|WP_Error True if user has permission, error otherwise
     */
    public function get_items_permissions_check(WP_REST_Request $request): bool|WP_Error {
        if (!current_user_can('manage_options')) {
            return new WP_Error(
                'rest_forbidden',
                __('Sorry, you are not allowed to view activity logs.', 'wp-activity-tracker'),
                ['status' => 403]
            );
        }
        
        return true;
    }
    
    /**
     * Check if current user has permission to get an item
     * 
     * @param WP_REST_Request $request Request object
     * @return bool|WP_Error True if user has permission, error otherwise
     */
    public function get_item_permissions_check(WP_REST_Request $request): bool|WP_Error {
        if (!current_user_can('manage_options')) {
            return new WP_Error(
                'rest_forbidden',
                __('Sorry, you are not allowed to view this activity log.', 'wp-activity-tracker'),
                ['status' => 403]
            );
        }
        
        return true;
    }
    
    /**
     * Check if current user has permission to create an item
     * 
     * @param WP_REST_Request $request Request object
     * @return bool|WP_Error True if user has permission, error otherwise
     */
    public function create_item_permissions_check(WP_REST_Request $request): bool|WP_Error {
        if (!current_user_can('manage_options')) {
            return new WP_Error(
                'rest_forbidden',
                __('Sorry, you are not allowed to create activity logs.', 'wp-activity-tracker'),
                ['status' => 403]
            );
        }
        
        return true;
    }
    
    /**
     * Get user data for display
     * 
     * @param int $user_id User ID
     * @return array User data
     */
    private function get_user_data(int $user_id): array {
        if ($user_id === 0) {
            return [
                'id'   => 0,
                'name' => __('System', 'wp-activity-tracker'),
                'avatar' => '',
            ];
        }
        
        $user = get_userdata($user_id);
        
        if (!$user) {
            return [
                'id'   => $user_id,
                'name' => __('Unknown', 'wp-activity-tracker'),
                'avatar' => '',
            ];
        }
        
        return [
            'id'     => $user_id,
            'name'   => $user->display_name,
            'avatar' => get_avatar_url($user_id, ['size' => 32]),
        ];
    }
    
    /**
     * Prepare item for response
     * 
     * @param array $item Item data
     * @param WP_REST_Request $request Request object
     * @return array Prepared item
     */
    public function prepare_item_for_response(array $item, WP_REST_Request $request): array {
        return [
            'id'         => (int) $item['ID'],
            'user_id'    => (int) $item['user_id'],
            'user'       => $item['user'] ?? $this->get_user_data($item['user_id']),
            'event_name' => $item['event_name'],
            'type'       => $item['type'],
            'category'   => $item['category'],
            'importance' => $item['importance'],
            'note'       => $item['note'],
            'date'       => $item['date'],
            'date_formatted' => date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($item['date'])),
        ];
    }
    
    /**
     * Get collection parameters
     * 
     * @return array Collection parameters
     */
    public function get_collection_params(): array {
        return [
            'page' => [
                'description'       => __('Current page of the collection.', 'wp-activity-tracker'),
                'type'              => 'integer',
                'default'           => 1,
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
                'minimum'           => 1,
            ],
            'per_page' => [
                'description'       => __('Maximum number of items to be returned in result set.', 'wp-activity-tracker'),
                'type'              => 'integer',
                'default'           => 40,
                'minimum'           => 1,
                'maximum'           => 100,
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
            ],
            'search' => [
                'description'       => __('Limit results to those matching a string.', 'wp-activity-tracker'),
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => 'rest_validate_request_arg',
            ],
            'category' => [
                'description'       => __('Limit results to a specific category.', 'wp-activity-tracker'),
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => 'rest_validate_request_arg',
            ],
            'type' => [
                'description'       => __('Limit results to a specific type (manual or automatic).', 'wp-activity-tracker'),
                'type'              => 'string',
                'enum'              => ['manual', 'automatic'],
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => 'rest_validate_request_arg',
            ],
            'importance' => [
                'description'       => __('Limit results to a specific importance level.', 'wp-activity-tracker'),
                'type'              => 'string',
                'enum'              => ['low', 'medium', 'high', 'critical'],
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => 'rest_validate_request_arg',
            ],
            'orderby' => [
                'description'       => __('Sort collection by object attribute.', 'wp-activity-tracker'),
                'type'              => 'string',
                'default'           => 'date',
                'enum'              => ['date', 'event_name', 'category', 'importance', 'type'],
                'sanitize_callback' => 'sanitize_key',
                'validate_callback' => 'rest_validate_request_arg',
            ],
            'order' => [
                'description'       => __('Order sort attribute ascending or descending.', 'wp-activity-tracker'),
                'type'              => 'string',
                'default'           => 'DESC',
                'enum'              => ['ASC', 'DESC'],
                'sanitize_callback' => 'sanitize_key',
                'validate_callback' => 'rest_validate_request_arg',
            ],
        ];
    }
}
