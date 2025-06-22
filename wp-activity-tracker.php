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

		// Register REST API routes
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );

		new WPActivityTracker_Enqueue_assets();
	}

	/**
	 * Include required files
	 */
	private function includes(): void {
		require_once WP_ACTIVITY_TRACKER_PLUGIN_DIR . 'includes/classes/class-event-logger.php';
		require_once WP_ACTIVITY_TRACKER_PLUGIN_DIR . 'includes/classes/class-event-listeners.php';
		require_once WP_ACTIVITY_TRACKER_PLUGIN_DIR . 'includes/classes/class-rest-controller.php';
		require_once WP_ACTIVITY_TRACKER_PLUGIN_DIR . 'includes/classes/class-enqueue-scripts-and-styles.php';
		require_once WP_ACTIVITY_TRACKER_PLUGIN_DIR . 'includes/admin-menu.php';
	}

	/**
	 * Initialize database tables
	 */
	public function init_database(): void {
		require_once WP_ACTIVITY_TRACKER_PLUGIN_DIR . 'includes/classes/class-database.php';
		$database = new WPActivityTracker_Database();
		$database->maybe_create_tables();
	}

	/**
	 * Handle plugin activation
	 */
	public function activate(): void {
		require_once WP_ACTIVITY_TRACKER_PLUGIN_DIR . 'includes/class-database.php';
		$database = new WPActivityTracker_Database();
		$database->maybe_create_tables();

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
	 * Register REST API routes
	 */
	public function register_rest_routes(): void {
		$rest_controller = new WPActivityTracker_RestController();
		$rest_controller->register_routes();
	}

}

// Initialize the plugin
WPActivityTracker::instance();
