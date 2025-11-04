<?php
/**
 * Database handler
 *
 * @package TpLicense\Core
 */

namespace TpLicense\Core;

class Database
{
    /**
     * Create database tables
     */
    public function create_tables()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Licenses table
        $table_licenses = $wpdb->prefix . 'tp_licenses';
        $sql_licenses = "CREATE TABLE IF NOT EXISTS {$table_licenses} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            license_key varchar(255) NOT NULL,
            customer_id bigint(20) UNSIGNED NULL,
            product_id bigint(20) UNSIGNED NULL,
            status varchar(50) NOT NULL DEFAULT 'active',
            expires_at datetime NULL,
            max_activations int(11) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY license_key (license_key),
            KEY customer_id (customer_id),
            KEY status (status),
            KEY expires_at (expires_at)
        ) $charset_collate;";

        // Activations table
        $table_activations = $wpdb->prefix . 'tp_license_activations';
        $sql_activations = "CREATE TABLE IF NOT EXISTS {$table_activations} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            license_id bigint(20) UNSIGNED NOT NULL,
            domain varchar(255) NOT NULL,
            ip_address varchar(45) NOT NULL,
            installation_id varchar(255) NOT NULL,
            php_version varchar(50) NULL,
            laravel_version varchar(50) NULL,
            user_agent text NULL,
            activated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            last_validated_at datetime NULL,
            deactivated_at datetime NULL,
            PRIMARY KEY  (id),
            KEY license_id (license_id),
            KEY domain (domain),
            KEY installation_id (installation_id),
            UNIQUE KEY unique_activation (license_id, installation_id)
        ) $charset_collate;";

        // IP Whitelist table
        $table_ip_whitelist = $wpdb->prefix . 'tp_license_ip_whitelist';
        $sql_ip_whitelist = "CREATE TABLE IF NOT EXISTS {$table_ip_whitelist} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            license_id bigint(20) UNSIGNED NOT NULL,
            ip_address varchar(45) NOT NULL,
            description text NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY license_id (license_id),
            KEY ip_address (ip_address)
        ) $charset_collate;";

        // Customers table
        $table_customers = $wpdb->prefix . 'tp_customers';
        $sql_customers = "CREATE TABLE IF NOT EXISTS {$table_customers} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            email varchar(255) NOT NULL,
            company varchar(255) NULL,
            phone varchar(50) NULL,
            notes text NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY email (email)
        ) $charset_collate;";

        // Activity log table
        $table_activity_log = $wpdb->prefix . 'tp_license_activity_log';
        $sql_activity_log = "CREATE TABLE IF NOT EXISTS {$table_activity_log} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            license_id bigint(20) UNSIGNED NULL,
            action varchar(100) NOT NULL,
            ip_address varchar(45) NULL,
            details text NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY license_id (license_id),
            KEY action (action),
            KEY created_at (created_at)
        ) $charset_collate;";

        // Execute queries
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_licenses);
        dbDelta($sql_activations);
        dbDelta($sql_ip_whitelist);
        dbDelta($sql_customers);
        dbDelta($sql_activity_log);
    }

    /**
     * Get license by key
     */
    public function get_license_by_key($license_key)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'tp_licenses';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE license_key = %s",
            $license_key
        ));
    }

    /**
     * Get license by ID
     */
    public function get_license($license_id)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'tp_licenses';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $license_id
        ));
    }

    /**
     * Create new license
     */
    public function create_license($data)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'tp_licenses';

        $defaults = [
            'status' => 'active',
            'max_activations' => 1,
        ];

        $data = wp_parse_args($data, $defaults);

        $wpdb->insert($table, $data);

        return $wpdb->insert_id;
    }

    /**
     * Update license
     */
    public function update_license($license_id, $data)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'tp_licenses';

        return $wpdb->update(
            $table,
            $data,
            ['id' => $license_id]
        );
    }

    /**
     * Get activation
     */
    public function get_activation($license_id, $installation_id)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'tp_license_activations';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table}
            WHERE license_id = %d
            AND installation_id = %s
            AND deactivated_at IS NULL",
            $license_id,
            $installation_id
        ));
    }

    /**
     * Get active activations count
     */
    public function get_activation_count($license_id)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'tp_license_activations';

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table}
            WHERE license_id = %d
            AND deactivated_at IS NULL",
            $license_id
        ));
    }

    /**
     * Create activation
     */
    public function create_activation($data)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'tp_license_activations';

        $wpdb->insert($table, $data);

        return $wpdb->insert_id;
    }

    /**
     * Update activation
     */
    public function update_activation($activation_id, $data)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'tp_license_activations';

        return $wpdb->update(
            $table,
            $data,
            ['id' => $activation_id]
        );
    }

    /**
     * Deactivate license
     */
    public function deactivate_license($license_id, $domain)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'tp_license_activations';

        return $wpdb->update(
            $table,
            ['deactivated_at' => current_time('mysql')],
            [
                'license_id' => $license_id,
                'domain' => $domain,
            ]
        );
    }

    /**
     * Check if IP is whitelisted
     */
    public function is_ip_whitelisted($license_id, $ip_address)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'tp_license_ip_whitelist';

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table}
            WHERE license_id = %d
            AND ip_address = %s",
            $license_id,
            $ip_address
        ));

        return $count > 0;
    }

    /**
     * Add IP to whitelist
     */
    public function add_ip_to_whitelist($license_id, $ip_address, $description = '')
    {
        global $wpdb;
        $table = $wpdb->prefix . 'tp_license_ip_whitelist';

        $wpdb->insert($table, [
            'license_id' => $license_id,
            'ip_address' => $ip_address,
            'description' => $description,
        ]);

        return $wpdb->insert_id;
    }

    /**
     * Remove IP from whitelist
     */
    public function remove_ip_from_whitelist($license_id, $ip_address)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'tp_license_ip_whitelist';

        return $wpdb->delete($table, [
            'license_id' => $license_id,
            'ip_address' => $ip_address,
        ]);
    }

    /**
     * Get customer
     */
    public function get_customer($customer_id)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'tp_customers';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $customer_id
        ));
    }

    /**
     * Get customer by email
     */
    public function get_customer_by_email($email)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'tp_customers';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE email = %s",
            $email
        ));
    }

    /**
     * Create customer
     */
    public function create_customer($data)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'tp_customers';

        $wpdb->insert($table, $data);

        return $wpdb->insert_id;
    }

    /**
     * Log activity
     */
    public function log_activity($data)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'tp_license_activity_log';

        $defaults = [
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        ];

        $data = wp_parse_args($data, $defaults);

        $wpdb->insert($table, $data);

        return $wpdb->insert_id;
    }

    /**
     * Get statistics
     */
    public function get_statistics()
    {
        global $wpdb;
        $table_licenses = $wpdb->prefix . 'tp_licenses';
        $table_activations = $wpdb->prefix . 'tp_license_activations';

        $stats = [
            'total_licenses' => $wpdb->get_var("SELECT COUNT(*) FROM {$table_licenses}"),
            'active_licenses' => $wpdb->get_var("SELECT COUNT(*) FROM {$table_licenses} WHERE status = 'active'"),
            'expired_licenses' => $wpdb->get_var("SELECT COUNT(*) FROM {$table_licenses} WHERE status = 'expired'"),
            'total_activations' => $wpdb->get_var("SELECT COUNT(*) FROM {$table_activations} WHERE deactivated_at IS NULL"),
            'expiring_soon' => $wpdb->get_var("
                SELECT COUNT(*) FROM {$table_licenses}
                WHERE status = 'active'
                AND expires_at IS NOT NULL
                AND expires_at <= DATE_ADD(NOW(), INTERVAL 30 DAY)
                AND expires_at > NOW()
            "),
        ];

        return $stats;
    }
}
