<?php
/**
 * AJAX handlers for WP Activity Tracker
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Save settings AJAX handler
 */
function wat_save_settings_ajax_handler(): void {
	try {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'wp_rest' ) ) {
			wp_die( __( 'Security check failed', 'wp-activity-tracker' ) );
		}

		// Check user permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'You do not have permission to save settings', 'wp-activity-tracker' ) );

			return;
		}

		// Sanitize and validate input
		$tracking_plugins = ! isset( $_POST['tracking_plugins'] ) || sanitize_text_field( $_POST['tracking_plugins'] );
		$tracking_posts   = ! isset( $_POST['tracking_posts'] ) || sanitize_text_field( $_POST['tracking_posts'] );

		// Prepare settings array
		$settings = [
			'tracking_plugins' => $tracking_plugins,
			'tracking_posts'   => $tracking_posts
		];

		// Save to wp_options
		$result = update_option( 'wat_settings', $settings );

		if ( $result !== false ) {
			wp_send_json_success( [
				'message'  => __( 'Settings saved successfully', 'wp-activity-tracker' ),
				'settings' => $settings
			] );
		} else {
			// update_option returns false if the value is the same or if there was an error
			// Let's check if the option exists and has the same value
			$current_settings = get_option( 'wat_settings', [] );
			if ( $current_settings === $settings ) {
				// Values are the same, this is not an error
				wp_send_json_success( [
					'message'  => __( 'Settings saved successfully', 'wp-activity-tracker' ),
					'settings' => $settings
				] );
			} else {
				throw new Exception( __( 'Failed to save settings to database', 'wp-activity-tracker' ) );
			}
		}

	} catch ( Exception $e ) {
		error_log( 'WAT Settings Save Error: ' . print_r( $e->getMessage(), true ) );
		wp_send_json_error( __( 'An error occurred while saving settings', 'wp-activity-tracker' ) );
	}
}

add_action( 'wp_ajax_wat_save_settings', 'wat_save_settings_ajax_handler' );


/**
 * Load settings AJAX handler
 * Register AJAX handlers for both logged-in and non-logged-in users (though we check permissions)
 */
function wat_load_settings_ajax_handler(): void {
	try {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'wp_rest' ) ) {
			wp_die( __( 'Security check failed', 'wp-activity-tracker' ) );
		}

		// Check user permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'You do not have permission to view settings', 'wp-activity-tracker' ) );

			return;
		}

		// Get settings from wp_options with defaults
		$default_settings = [
			'tracking_plugins' => true,
			'tracking_posts'   => true
		];

		$settings = get_option( 'wat_settings', $default_settings );

		// Ensure we have all required keys with proper types
		$settings = wp_parse_args( $settings, $default_settings );

		// Ensure boolean values
		$settings['tracking_plugins'] = (bool) $settings['tracking_plugins'];
		$settings['tracking_posts']   = (bool) $settings['tracking_posts'];

		wp_send_json_success( $settings );

	} catch ( Exception $e ) {
		error_log( 'WAT Settings Load Error: ' . print_r( $e->getMessage(), true ) );
		wp_send_json_error( __( 'An error occurred while loading settings', 'wp-activity-tracker' ) );
	}
}

add_action( 'wp_ajax_wat_load_settings', 'wat_load_settings_ajax_handler' );
