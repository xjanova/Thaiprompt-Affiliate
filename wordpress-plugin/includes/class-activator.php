<?php
/**
 * Fired during plugin activation
 */

class TP_License_Activator
{
    public static function activate()
    {
        // Create database tables
        require_once TP_LICENSE_PLUGIN_DIR . 'includes/class-database.php';
        TP_License_Database::create_tables();
        TP_License_Database::insert_default_packages();

        // Set default settings
        self::create_default_options();

        // Set activation flag
        update_option('tp_license_activated', time());
        update_option('tp_license_version', TP_LICENSE_VERSION);

        // Schedule cron jobs
        self::schedule_cron_jobs();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    private static function create_default_options()
    {
        $default_settings = array(
            'license_prefix' => 'TPAF',
            'license_length' => 32,
            'license_expiry_days' => 365,
            'max_activations' => 1,
            'allow_localhost' => true,
            'email_notifications' => true,
            'notification_email' => get_option('admin_email'),
            'auto_activate_woocommerce' => true,
            'grace_period_days' => 7,
            'check_interval_hours' => 24,
            'log_retention_days' => 90,
            'enable_api' => true,
            'api_rate_limit' => 100,
        );

        $existing = get_option('tp_license_settings');
        if (!$existing) {
            update_option('tp_license_settings', $default_settings);
        }

        update_option('tp_license_version', TP_LICENSE_VERSION);
    }

    private static function schedule_cron_jobs()
    {
        // Check expired licenses daily
        if (!wp_next_scheduled('tp_license_check_expired')) {
            wp_schedule_event(time(), 'daily', 'tp_license_check_expired');
        }

        // Clean old logs weekly
        if (!wp_next_scheduled('tp_license_clean_logs')) {
            wp_schedule_event(time(), 'weekly', 'tp_license_clean_logs');
        }
    }
}
