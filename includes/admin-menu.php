<?php
// Add admin menu
function wat_add_admin_menu(): void {
	add_menu_page(
		__( 'WP Activity Tracker', 'wp-activity-tracker' ),
		__( 'WP Activity', 'wp-activity-tracker' ),
		'manage_options',
		'wp-activity-tracker',
		'wat_render_admin_page',
		'dashicons-chart-line',
		30
	);
}

add_action( 'admin_menu', 'wat_add_admin_menu' );


/**
 * Render admin page (container for Vue app)
 */
function wat_render_admin_page(): void {
	?>
    <div id="app-dashboard"></div>
	<?php
}