<?php

/**
 * Enqueue assets for WP Activity Tracker
 */
class WPActivityTracker_Enqueue_assets {

	public function __construct() {
		// Enqueue scripts and styles
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
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
		// Path to your built assets
		$vue_asset_dir = WP_ACTIVITY_TRACKER_PLUGIN_URL . 'assets/dist/';

		// Enqueue the main JS file
		wp_enqueue_script(
			'wp-vue-dashboard',
			$vue_asset_dir . 'wp-vue-dashboard.js',
			[], // No dependencies
			filemtime( WP_ACTIVITY_TRACKER_PLUGIN_DIR . 'assets/dist/wp-vue-dashboard.js' ),
			true // Load in footer
		);

		// Enqueue the CSS file
		wp_enqueue_style(
			'wp-vue-dashboard-styles',
			$vue_asset_dir . 'wp-vue-index.css',
			[],
			filemtime( WP_ACTIVITY_TRACKER_PLUGIN_DIR . 'assets/dist/wp-vue-index.css' )
		);

		// Localize script with necessary data
		wp_localize_script(
			'wp-activity-tracker-app',
			'wpActivityTracker',
			[
				'apiUrl'            => rest_url( 'wp-activity-tracker/v1' ),
				'nonce'             => wp_create_nonce( 'wp_rest' ),
				'roles'             => self::get_all_wp_roles(),
				'importanceOptions' => [
					'low'      => __( 'Low', 'wp-activity-tracker' ),
					'medium'   => __( 'Medium', 'wp-activity-tracker' ),
					'high'     => __( 'High', 'wp-activity-tracker' ),
					'critical' => __( 'Critical', 'wp-activity-tracker' )
				],
			],
		);
	}

	public static function get_all_wp_roles(): array {

		$editable_roles = wp_roles()->roles;
		$roles          = array();

		foreach ( $editable_roles as $role_name => $role_info ) {
			$roles[] = array(
				'id'   => $role_name,
				'name' => translate_user_role( $role_info['name'] )
			);
		}

		return $roles;
	}
}
