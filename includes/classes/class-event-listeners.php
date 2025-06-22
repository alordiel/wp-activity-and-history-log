<?php
/**
 * Event Listeners class for WP Activity Tracker
 */
class WPActivityTracker_EventListeners {
    /**
     * @var WPActivityTracker_EventLogger Event logger instance
     */
    private WPActivityTracker_EventLogger $logger;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->logger = new WPActivityTracker_EventLogger();
    }
    
    /**
     * Initialize event listeners
     */
    public function init(): void {
        // Plugin activation
        add_action('activated_plugin', [$this, 'on_plugin_activated'], 10, 2);
        
        // Plugin deactivation
        add_action('deactivated_plugin', [$this, 'on_plugin_deactivated'], 10, 2);
        
        // Plugin deletion
        add_action('delete_plugin', [$this, 'on_plugin_deleted'], 10, 1);
        
        // Plugin installation
        add_action('upgrader_process_complete', [$this, 'on_upgrader_process_complete'], 10, 2);
        
        // Settings changes
        add_action('updated_option', [$this, 'on_option_updated'], 10, 3);
        
        // Permalink structure changes
        add_action('permalink_structure_changed', [$this, 'on_permalink_structure_changed'], 10, 2);
    }
    
    /**
     * Handler for plugin activation
     * 
     * @param string $plugin Plugin path
     * @param bool $network_wide Whether the plugin was activated network-wide
     */
    public function on_plugin_activated(string $plugin, bool $network_wide): void {
        $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
        $plugin_name = $plugin_data['Name'] ?? basename($plugin, '.php');
		$description = sprintf(__('Plugin "%s" was activated.', 'wp-activity-tracker'), $plugin_name);
        
        $this->logger->log_automatic_event(
            'Plugin activated',
            'medium',
            $description
        );
    }
    
    /**
     * Handler for plugin deactivation
     * 
     * @param string $plugin Plugin path
     * @param bool $network_wide Whether the plugin was deactivated network-wide
     */
    public function on_plugin_deactivated(string $plugin, bool $network_wide): void {
        $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
        $plugin_name = $plugin_data['Name'] ?? basename($plugin, '.php');
		$description = sprintf(__('Plugin "%s" was deactivated.', 'wp-activity-tracker'), $plugin_name);
        
        $this->logger->log_automatic_event(
            'Plugin deactivated',
            'medium',
            $description
        );
    }
    
    /**
     * Handler for plugin deletion
     * 
     * @param string $plugin Plugin path
     */
    public function on_plugin_deleted(string $plugin): void {
        // Plugin data might not be available at this point, so use basename
        $plugin_name = basename($plugin, '.php');
		$description = sprintf(__('Plugin "%s" was deleted.', 'wp-activity-tracker'), $plugin_name);
        
        $this->logger->log_automatic_event(
            'Plugin deleted',
            'high',
            $description
        );
    }
    
    /**
     * Handler for upgrader process completion (plugin/core updates)
     * 
     * @param WP_Upgrader $upgrader Upgrader instance
     * @param array $hook_extra Extra data about the upgrade
     */
    public function on_upgrader_process_complete(WP_Upgrader $upgrader, array $hook_extra): void {
        // Check if this is a plugin update
        if ($hook_extra['type'] === 'plugin' && $hook_extra['action'] === 'update') {
            // Plugin update
            if (isset($hook_extra['plugins']) && is_array($hook_extra['plugins'])) {
                foreach ($hook_extra['plugins'] as $plugin) {
                    $this->log_plugin_update($plugin, $upgrader);
                }
            } elseif ( !empty($hook_extra['plugin']) ) {
                $this->log_plugin_update($hook_extra['plugin'], $upgrader);
            }
        } 
        // Check if this is a core update
        elseif ($hook_extra['type'] === 'core' && $hook_extra['action'] === 'update') {
            $this->log_core_update($upgrader);
        }
    }
    
    /**
     * Log plugin update
     * 
     * @param string $plugin Plugin path
     * @param WP_Upgrader $upgrader Upgrader instance
     */
    private function log_plugin_update(string $plugin, WP_Upgrader $upgrader): void {
        // Get plugin data
        $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
        $plugin_name = $plugin_data['Name'] ?? basename($plugin, '.php');
        $new_version = $plugin_data['Version'] ?? '';
        
        // Try to get old version from upgrader skin
        $old_version = '';
        if (isset($upgrader->skin->plugin_info['Version'])) {
            $old_version = $upgrader->skin->plugin_info['Version'];
        }
        
        // Determine importance
        $importance = $this->logger->determine_update_importance($old_version, $new_version);
        
        // Create note
        $description = $this->logger->format_version_note($old_version, $new_version, $plugin_name);
        
        // Log the event
        $this->logger->log_automatic_event(
            'Plugin updated',
            $importance,
            $description
        );
    }
    
    /**
     * Log WordPress core update
     * 
     * @param WP_Upgrader $upgrader Upgrader instance
     */
    private function log_core_update(WP_Upgrader $upgrader): void {
        // Get new version
        $new_version = get_bloginfo('version');
        
        // Try to get old version from upgrader skin
        $old_version = '';
        if (isset($upgrader->skin->result['current_version'])) {
            $old_version = $upgrader->skin->result['current_version'];
        } elseif (isset($upgrader->skin->options['current_version'])) {
            $old_version = $upgrader->skin->options['current_version'];
        }
        
        // Determine importance
        $importance = $this->logger->determine_update_importance($old_version, $new_version);
        
        // Create note
        $description = $this->logger->format_version_note($old_version, $new_version, 'WordPress core');
        
        // Log the event
        $this->logger->log_automatic_event(
            'WP core update',
            $importance,
            $description
        );
    }
    
    /**
     * Handler for option updates (general settings changes)
     * 
     * @param string $option Option name
     * @param mixed $old_value Old option value
     * @param mixed $new_value New option value
     */
    public function on_option_updated(string $option, $old_value, $new_value): void {
        // Only track general settings changes
        $general_settings = [
            'blogname',
            'blogdescription',
            'siteurl',
            'home',
            'admin_email',
            'users_can_register',
            'default_role',
            'timezone_string',
            'date_format',
            'time_format',
            'start_of_week'
        ];
		$description = sprintf(__('Setting "%s" was changed. From "%s" to "%s"', 'wp-activity-tracker'), $option, $old_value, $new_value);
        
        if (in_array($option, $general_settings)) {
            $this->logger->log_automatic_event(
                'Setting updated',
                'medium',
                $description
            );
        }
    }
    
    /**
     * Handler for permalink structure changes
     * 
     * @param string $old_permalink_structure Old permalink structure
     * @param string $permalink_structure New permalink structure
     */
    public function on_permalink_structure_changed(string $old_permalink_structure, string $permalink_structure): void {
        $description = sprintf(
                __('Permalink structure was changed from "%s" to "%s".', 'wp-activity-tracker'),
                empty($old_permalink_structure) ? __('Plain', 'wp-activity-tracker') : $old_permalink_structure,
                empty($permalink_structure) ? __('Plain', 'wp-activity-tracker') : $permalink_structure
            );
		$this->logger->log_automatic_event(
            'Permalink structure changed',
            'high',
             $description
        );
    }
}
