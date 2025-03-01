<?php
/**
 * Plugin Name: WP Activity Tracker
 * Description: Tracks WordPress admin activities automatically and allows manual event logging.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: wp-activity-tracker
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

// Define plugin constants
const WP_ACTIVITY_TRACKER_VERSION = '1.0.0';
define( 'WP_ACTIVITY_TRACKER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_ACTIVITY_TRACKER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Class WPActivityTracker
 *
 * Main plugin class to initialize everything
 */
class WPActivityTracker {
	/**
	 * @var WPActivityTracker|null The singleton instance
	 */
	private static ?WPActivityTracker $instance = null;

	/**
	 * Singleton pattern implementation
	 *
	 * @return WPActivityTracker Instance of the plugin
	 */
	public static function instance(): WPActivityTracker {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		// Include required files
		$this->includes();

		// Initialize database
		add_action( 'plugins_loaded', [ $this, 'init_database' ] );

		// Register activation/deactivation hooks
		register_activation_hook( __FILE__, [ $this, 'activate' ] );
		register_uninstall_hook( __FILE__, [ __CLASS__, 'uninstall' ] );

		// Initialize event listeners
		add_action( 'init', [ $this, 'init_event_listeners' ] );

		// Add admin menu
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );

		// Register REST API routes
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );

		// Enqueue scripts and styles
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	/**
	 * Include required files
	 */
	private function includes(): void {
		require_once WP_ACTIVITY_TRACKER_PLUGIN_DIR . 'includes/class-event-logger.php';
		require_once WP_ACTIVITY_TRACKER_PLUGIN_DIR . 'includes/class-event-listeners.php';
		require_once WP_ACTIVITY_TRACKER_PLUGIN_DIR . 'includes/class-rest-controller.php';
	}

	/**
	 * Initialize database tables
	 */
	public function init_database(): void {
		require_once WP_ACTIVITY_TRACKER_PLUGIN_DIR . 'includes/class-database.php';
		$database = new WPActivityTracker_Database();
		$database->maybe_create_tables();
	}

	/**
	 * Handle plugin activation
	 */
	public function activate(): void {
		require_once WP_ACTIVITY_TRACKER_PLUGIN_DIR . 'includes/class-database.php';
		$database = new WPActivityTracker_Database();
		$database->create_tables();

		// Flush rewrite rules
		flush_rewrite_rules();
	}

	/**
	 * Handle plugin uninstallation
	 */
	public static function uninstall(): void {
		require_once WP_ACTIVITY_TRACKER_PLUGIN_DIR . 'includes/class-database.php';
		$database = new WPActivityTracker_Database();
		$database->drop_tables();
	}

	/**
	 * Initialize event listeners
	 */
	public function init_event_listeners(): void {
		$event_listeners = new WPActivityTracker_EventListeners();
		$event_listeners->init();
	}

	/**
	 * Add admin menu item
	 */
	public function add_admin_menu(): void {
		add_menu_page(
			__( 'WP Activity Tracker', 'wp-activity-tracker' ),
			__( 'WP Activity', 'wp-activity-tracker' ),
			'manage_options',
			'wp-activity-tracker',
			[ $this, 'render_admin_page' ],
			'dashicons-chart-line',
			30
		);
	}

	/**
	 * Register REST API routes
	 */
	public function register_rest_routes(): void {
		$rest_controller = new WPActivityTracker_RestController();
		$rest_controller->register_routes();
	}

	/**
	 * Enqueue admin scripts and styles
	 *
	 * @param string $hook The current admin page
	 */
	public function enqueue_assets( string $hook ): void {
		if ( $hook !== 'toplevel_page_wp-activity-tracker' ) {
			return;
		}

		// Enqueue Vue.js from CDN
		wp_enqueue_script(
			'vue',
			'https://unpkg.com/vue@3/dist/vue.global.js',
			[],
			'3.3.4',
			true
		);

		// Enqueue date-fns library
		wp_enqueue_script(
			'date-fns',
			'https://cdn.jsdelivr.net/npm/date-fns@2.30.0/index.min.js',
			[],
			'2.30.0',
			true
		);

		// Enqueue custom app script
		wp_enqueue_script(
			'wp-activity-tracker-app',
			WP_ACTIVITY_TRACKER_PLUGIN_URL . 'assets/js/app.js',
			[ 'vue', 'date-fns', 'wp-api-fetch' ],
			filemtime( WP_ACTIVITY_TRACKER_PLUGIN_DIR . 'assets/js/app.js' ),
			true
		);

		// Localize script with necessary data
		wp_localize_script(
			'wp-activity-tracker-app',
			'wpActivityTracker',
			[
				'apiUrl'            => rest_url( 'wp-activity-tracker/v1' ),
				'nonce'             => wp_create_nonce( 'wp_rest' ),
				'categories'        => $this->get_categories(),
				'importanceOptions' => [
					'low'      => __( 'Low', 'wp-activity-tracker' ),
					'medium'   => __( 'Medium', 'wp-activity-tracker' ),
					'high'     => __( 'High', 'wp-activity-tracker' ),
					'critical' => __( 'Critical', 'wp-activity-tracker' )
				]
			]
		);

		// Enqueue Tailwind CSS from CDN
		wp_enqueue_style(
			'tailwindcss',
			'https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css',
			[],
			'2.2.19'
		);

		// Enqueue custom styles
		wp_enqueue_style(
			'wp-activity-tracker-styles',
			WP_ACTIVITY_TRACKER_PLUGIN_URL . 'assets/css/style.css',
			[ 'tailwindcss' ],
			filemtime( WP_ACTIVITY_TRACKER_PLUGIN_DIR . 'assets/css/style.css' )
		);
	}

	/**
	 * Render admin page (container for Vue app)
	 */
	public function render_admin_page(): void {
		// Container for Vue app with inline template
		include_once( 'templates/admin.html.php' );
	}

	/**
	 * Get all available categories
	 *
	 * @return array List of available categories
	 */
	private function get_categories(): array {
		global $wpdb;
		$table_name = $wpdb->prefix . 'pp_activity_logger';

		// Get existing categories from the database
		$categories = $wpdb->get_col( "SELECT DISTINCT category FROM {$table_name}" );

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

		return array_unique( array_merge( $default_categories, $categories ?: [] ) );
	}
}

// Initialize the plugin
function wp_activity_tracker() {
	return WPActivityTracker::instance();
}

// Global for backwards compatibility
$GLOBALS['wp_activity_tracker'] = wp_activity_tracker();