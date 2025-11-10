<?php
/**
 * Database Schema for License Management System
 */

class TP_License_Database
{
    public static function create_tables()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Packages Table
        $table_packages = $wpdb->prefix . 'tp_packages';
        $sql_packages = "CREATE TABLE $table_packages (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            slug varchar(100) NOT NULL,
            description text,
            price decimal(10,2) NOT NULL DEFAULT 0.00,
            license_type enum('personal','business','developer','enterprise') DEFAULT 'personal',
            max_installations int(11) NOT NULL DEFAULT 1,
            update_period varchar(50) DEFAULT 'lifetime',
            support_period varchar(50) DEFAULT '1_year',
            features longtext,
            is_active tinyint(1) DEFAULT 1,
            sort_order int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY slug (slug),
            KEY is_active (is_active)
        ) $charset_collate;";

        dbDelta($sql_packages);

        // Licenses Table
        $table_licenses = $wpdb->prefix . 'tp_licenses';
        $sql_licenses = "CREATE TABLE $table_licenses (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            license_key varchar(255) NOT NULL,
            package_id bigint(20) UNSIGNED,
            order_id bigint(20) UNSIGNED,
            product_id bigint(20) UNSIGNED,
            customer_email varchar(255),
            customer_name varchar(255),
            license_type varchar(50) DEFAULT 'standard',
            status enum('active','inactive','expired','suspended') DEFAULT 'inactive',
            max_installations int(11) DEFAULT 1,
            activation_count int(11) DEFAULT 0,
            domain varchar(255),
            ip_address varchar(50),
            installation_id varchar(100),
            expires_at datetime,
            update_expires_at datetime,
            activated_at datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY license_key (license_key),
            KEY package_id (package_id),
            KEY order_id (order_id),
            KEY customer_email (customer_email),
            KEY status (status)
        ) $charset_collate;";

        dbDelta($sql_licenses);

        // Installations Table
        $table_installations = $wpdb->prefix . 'tp_installations';
        $sql_installations = "CREATE TABLE $table_installations (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            license_id bigint(20) UNSIGNED NOT NULL,
            installation_id varchar(100) NOT NULL,
            domain varchar(255),
            ip_address varchar(50),
            current_version varchar(20),
            server_info longtext,
            status enum('active','inactive','suspended') DEFAULT 'active',
            installed_at datetime,
            last_check_at datetime,
            deactivated_at datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY installation_unique (license_id, installation_id),
            KEY license_id (license_id),
            KEY status (status),
            KEY last_check_at (last_check_at)
        ) $charset_collate;";

        dbDelta($sql_installations);

        // Installation Logs Table
        $table_logs = $wpdb->prefix . 'tp_installation_logs';
        $sql_logs = "CREATE TABLE $table_logs (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            license_id bigint(20) UNSIGNED NOT NULL,
            installation_id varchar(100),
            action varchar(50) NOT NULL,
            status varchar(50),
            data longtext,
            ip_address varchar(50),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY license_id (license_id),
            KEY installation_id (installation_id),
            KEY action (action),
            KEY created_at (created_at)
        ) $charset_collate;";

        dbDelta($sql_logs);

        // Releases Table
        $table_releases = $wpdb->prefix . 'tp_releases';
        $sql_releases = "CREATE TABLE $table_releases (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            version varchar(20) NOT NULL,
            release_name varchar(255),
            release_type enum('major','minor','patch','hotfix') DEFAULT 'patch',
            changelog longtext,
            download_url varchar(500),
            file_size bigint(20),
            checksum varchar(64),
            requires_php varchar(20),
            requires_mysql varchar(20),
            requires_laravel varchar(20),
            is_critical tinyint(1) DEFAULT 0,
            status enum('draft','published','deprecated') DEFAULT 'draft',
            published_at datetime,
            deprecated_at datetime,
            created_by bigint(20) UNSIGNED,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY version (version),
            KEY status (status),
            KEY release_type (release_type),
            KEY published_at (published_at)
        ) $charset_collate;";

        dbDelta($sql_releases);

        // Release Permissions Table (which packages can access which releases)
        $table_release_permissions = $wpdb->prefix . 'tp_release_permissions';
        $sql_release_permissions = "CREATE TABLE $table_release_permissions (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            release_id bigint(20) UNSIGNED NOT NULL,
            package_id bigint(20) UNSIGNED NOT NULL,
            allowed tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY release_package (release_id, package_id),
            KEY release_id (release_id),
            KEY package_id (package_id)
        ) $charset_collate;";

        dbDelta($sql_release_permissions);

        // Activation History Table
        $table_activations = $wpdb->prefix . 'tp_license_activations';
        $sql_activations = "CREATE TABLE $table_activations (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            license_id bigint(20) UNSIGNED NOT NULL,
            domain varchar(255),
            ip_address varchar(50),
            installation_id varchar(100),
            user_agent text,
            php_version varchar(20),
            laravel_version varchar(20),
            status enum('active','inactive') DEFAULT 'active',
            activated_at datetime,
            deactivated_at datetime,
            last_check_at datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY license_id (license_id),
            KEY domain (domain),
            KEY status (status)
        ) $charset_collate;";

        dbDelta($sql_activations);

        // Download Logs Table
        $table_downloads = $wpdb->prefix . 'tp_license_downloads';
        $sql_downloads = "CREATE TABLE $table_downloads (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            license_id bigint(20) UNSIGNED NOT NULL,
            version varchar(20),
            ip_address varchar(50),
            user_agent text,
            downloaded_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY license_id (license_id),
            KEY version (version),
            KEY downloaded_at (downloaded_at)
        ) $charset_collate;";

        dbDelta($sql_downloads);

        // Update version
        update_option('tp_license_db_version', TP_LICENSE_VERSION);
    }

    /**
     * Insert default packages
     */
    public static function insert_default_packages()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'tp_packages';

        // Check if packages already exist
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        if ($count > 0) {
            return;
        }

        $packages = array(
            array(
                'name' => 'Personal License',
                'slug' => 'personal',
                'description' => 'สำหรับผู้ใช้งานส่วนตัว 1 เว็บไซต์',
                'price' => 2999.00,
                'license_type' => 'personal',
                'max_installations' => 1,
                'update_period' => '1_year',
                'support_period' => '1_year',
                'features' => serialize(array(
                    'can_install' => true,
                    'updates' => true,
                    'support' => true,
                    'priority_support' => false,
                    'white_label' => false,
                    'source_code' => false,
                )),
                'sort_order' => 1,
            ),
            array(
                'name' => 'Business License',
                'slug' => 'business',
                'description' => 'สำหรับธุรกิจ 5 เว็บไซต์',
                'price' => 9999.00,
                'license_type' => 'business',
                'max_installations' => 5,
                'update_period' => '1_year',
                'support_period' => '1_year',
                'features' => serialize(array(
                    'can_install' => true,
                    'updates' => true,
                    'support' => true,
                    'priority_support' => true,
                    'white_label' => false,
                    'source_code' => false,
                )),
                'sort_order' => 2,
            ),
            array(
                'name' => 'Developer License',
                'slug' => 'developer',
                'description' => 'สำหรับนักพัฒนา ไม่จำกัดเว็บไซต์',
                'price' => 19999.00,
                'license_type' => 'developer',
                'max_installations' => 999,
                'update_period' => 'lifetime',
                'support_period' => 'lifetime',
                'features' => serialize(array(
                    'can_install' => true,
                    'updates' => true,
                    'support' => true,
                    'priority_support' => true,
                    'white_label' => true,
                    'source_code' => true,
                )),
                'sort_order' => 3,
            ),
            array(
                'name' => 'Enterprise License',
                'slug' => 'enterprise',
                'description' => 'สำหรับองค์กร พร้อม SLA และ Dedicated Support',
                'price' => 49999.00,
                'license_type' => 'enterprise',
                'max_installations' => 999,
                'update_period' => 'lifetime',
                'support_period' => 'lifetime',
                'features' => serialize(array(
                    'can_install' => true,
                    'updates' => true,
                    'support' => true,
                    'priority_support' => true,
                    'white_label' => true,
                    'source_code' => true,
                    'dedicated_support' => true,
                    'sla' => true,
                    'custom_development' => true,
                )),
                'sort_order' => 4,
            ),
        );

        foreach ($packages as $package) {
            $wpdb->insert($table, $package);
        }
    }

    /**
     * Check if database needs upgrade
     */
    public static function needs_upgrade()
    {
        $current_version = get_option('tp_license_db_version');
        return version_compare($current_version, TP_LICENSE_VERSION, '<');
    }

    /**
     * Upgrade database
     */
    public static function upgrade()
    {
        self::create_tables();
        update_option('tp_license_db_version', TP_LICENSE_VERSION);
    }
}
