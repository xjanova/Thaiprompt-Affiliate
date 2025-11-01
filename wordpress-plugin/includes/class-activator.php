<?php
/**
 * Fired during plugin activation
 */

class TP_License_Activator
{
    public static function activate()
    {
        // สร้าง database tables
        self::create_tables();

        // สร้าง default options
        self::create_default_options();

        // สร้าง rewrite rules
        flush_rewrite_rules();
    }

    private static function create_tables()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Table สำหรับเก็บ licenses
        $table_licenses = $wpdb->prefix . 'tp_licenses';

        $sql_licenses = "CREATE TABLE IF NOT EXISTS $table_licenses (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            license_key varchar(255) NOT NULL,
            product_id bigint(20) UNSIGNED NOT NULL,
            order_id bigint(20) UNSIGNED NOT NULL,
            customer_email varchar(255) NOT NULL,
            customer_name varchar(255) DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            domain varchar(255) DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            installation_id varchar(255) DEFAULT NULL,
            max_activations int(11) NOT NULL DEFAULT 1,
            activation_count int(11) NOT NULL DEFAULT 0,
            expires_at datetime DEFAULT NULL,
            activated_at datetime DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY license_key (license_key),
            KEY product_id (product_id),
            KEY order_id (order_id),
            KEY customer_email (customer_email),
            KEY status (status)
        ) $charset_collate;";

        // Table สำหรับเก็บ activation history
        $table_activations = $wpdb->prefix . 'tp_license_activations';

        $sql_activations = "CREATE TABLE IF NOT EXISTS $table_activations (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            license_id bigint(20) UNSIGNED NOT NULL,
            domain varchar(255) NOT NULL,
            ip_address varchar(45) DEFAULT NULL,
            installation_id varchar(255) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            php_version varchar(20) DEFAULT NULL,
            laravel_version varchar(20) DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            activated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            deactivated_at datetime DEFAULT NULL,
            last_check_at datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY license_id (license_id),
            KEY domain (domain),
            KEY status (status)
        ) $charset_collate;";

        // Table สำหรับเก็บ download logs
        $table_downloads = $wpdb->prefix . 'tp_license_downloads';

        $sql_downloads = "CREATE TABLE IF NOT EXISTS $table_downloads (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            license_id bigint(20) UNSIGNED NOT NULL,
            version varchar(20) NOT NULL,
            ip_address varchar(45) DEFAULT NULL,
            downloaded_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY license_id (license_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_licenses);
        dbDelta($sql_activations);
        dbDelta($sql_downloads);
    }

    private static function create_default_options()
    {
        // Default settings
        $default_settings = array(
            'license_prefix' => 'TPAF',
            'license_length' => 32,
            'license_expiry_days' => 365,
            'max_activations' => 1,
            'allow_localhost' => true,
            'email_notifications' => true,
        );

        add_option('tp_license_settings', $default_settings);
        add_option('tp_license_version', TP_LICENSE_VERSION);
    }
}
