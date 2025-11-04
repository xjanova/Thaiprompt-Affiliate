<?php
/**
 * Plugin Name: TP License Manager
 * Plugin URI: https://xman4289.com/
 * Description: Advanced license management system for TP-Affiliate with IP whitelist, domain validation, and REST API
 * Version: 1.0.0
 * Author: xjanova
 * Author URI: https://xman4289.com/
 * License: Proprietary
 * Text Domain: tp-license
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.1
 *
 * @package TpLicense
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('TP_LICENSE_VERSION', '1.0.0');
define('TP_LICENSE_PLUGIN_FILE', __FILE__);
define('TP_LICENSE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TP_LICENSE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TP_LICENSE_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'TpLicense\\';
    $base_dir = TP_LICENSE_PLUGIN_DIR . 'includes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

/**
 * Main plugin class
 */
class TP_License_Manager
{
    /**
     * Plugin instance
     */
    private static $instance = null;

    /**
     * Database handler
     */
    public $database;

    /**
     * License validator
     */
    public $validator;

    /**
     * IP manager
     */
    public $ip_manager;

    /**
     * API handler
     */
    public $api;

    /**
     * Get plugin instance
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        $this->init();
        $this->load_dependencies();
        $this->setup_hooks();
    }

    /**
     * Initialize plugin
     */
    private function init()
    {
        // Load text domain
        load_plugin_textdomain('tp-license', false, dirname(TP_LICENSE_PLUGIN_BASENAME) . '/languages');
    }

    /**
     * Load dependencies
     */
    private function load_dependencies()
    {
        // Core classes
        require_once TP_LICENSE_PLUGIN_DIR . 'includes/Core/Database.php';
        require_once TP_LICENSE_PLUGIN_DIR . 'includes/Core/LicenseValidator.php';
        require_once TP_LICENSE_PLUGIN_DIR . 'includes/Core/IpManager.php';
        require_once TP_LICENSE_PLUGIN_DIR . 'includes/Core/Encryption.php';

        // API classes
        require_once TP_LICENSE_PLUGIN_DIR . 'includes/Api/RestApi.php';
        require_once TP_LICENSE_PLUGIN_DIR . 'includes/Api/ActivationEndpoint.php';
        require_once TP_LICENSE_PLUGIN_DIR . 'includes/Api/ValidationEndpoint.php';
        require_once TP_LICENSE_PLUGIN_DIR . 'includes/Api/DeactivationEndpoint.php';
        require_once TP_LICENSE_PLUGIN_DIR . 'includes/Api/IpCheckEndpoint.php';

        // Admin classes
        if (is_admin()) {
            require_once TP_LICENSE_PLUGIN_DIR . 'includes/Admin/AdminDashboard.php';
            require_once TP_LICENSE_PLUGIN_DIR . 'includes/Admin/LicenseManager.php';
            require_once TP_LICENSE_PLUGIN_DIR . 'includes/Admin/CustomerManager.php';
            require_once TP_LICENSE_PLUGIN_DIR . 'includes/Admin/IpWhitelistManager.php';
            require_once TP_LICENSE_PLUGIN_DIR . 'includes/Admin/Settings.php';
        }

        // Initialize core components
        $this->database = new TpLicense\Core\Database();
        $this->validator = new TpLicense\Core\LicenseValidator();
        $this->ip_manager = new TpLicense\Core\IpManager();
        $this->api = new TpLicense\Api\RestApi();
    }

    /**
     * Setup WordPress hooks
     */
    private function setup_hooks()
    {
        // Activation/Deactivation
        register_activation_hook(TP_LICENSE_PLUGIN_FILE, [$this, 'activate']);
        register_deactivation_hook(TP_LICENSE_PLUGIN_FILE, [$this, 'deactivate']);

        // Admin hooks
        if (is_admin()) {
            add_action('admin_menu', [$this, 'register_admin_menu']);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        }

        // REST API
        add_action('rest_api_init', [$this->api, 'register_routes']);

        // Cron jobs
        add_action('tp_license_check_expirations', [$this, 'check_license_expirations']);
        add_action('tp_license_cleanup_logs', [$this, 'cleanup_old_logs']);
    }

    /**
     * Plugin activation
     */
    public function activate()
    {
        // Create database tables
        $this->database->create_tables();

        // Schedule cron jobs
        if (!wp_next_scheduled('tp_license_check_expirations')) {
            wp_schedule_event(time(), 'daily', 'tp_license_check_expirations');
        }

        if (!wp_next_scheduled('tp_license_cleanup_logs')) {
            wp_schedule_event(time(), 'weekly', 'tp_license_cleanup_logs');
        }

        // Create default settings
        $this->create_default_settings();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate()
    {
        // Clear scheduled events
        wp_clear_scheduled_hook('tp_license_check_expirations');
        wp_clear_scheduled_hook('tp_license_cleanup_logs');

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Create default settings
     */
    private function create_default_settings()
    {
        $defaults = [
            'tp_license_api_version' => 'v1',
            'tp_license_cache_duration' => 3600,
            'tp_license_max_activations_default' => 1,
            'tp_license_grace_period_days' => 7,
            'tp_license_enable_ip_whitelist' => true,
            'tp_license_enable_domain_validation' => true,
            'tp_license_enable_analytics' => true,
            'tp_license_log_retention_days' => 90,
        ];

        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }

    /**
     * Register admin menu
     */
    public function register_admin_menu()
    {
        add_menu_page(
            __('TP License Manager', 'tp-license'),
            __('TP Licenses', 'tp-license'),
            'manage_options',
            'tp-license',
            [new TpLicense\Admin\AdminDashboard(), 'render'],
            'dashicons-shield-alt',
            30
        );

        add_submenu_page(
            'tp-license',
            __('All Licenses', 'tp-license'),
            __('Licenses', 'tp-license'),
            'manage_options',
            'tp-license',
            [new TpLicense\Admin\AdminDashboard(), 'render']
        );

        add_submenu_page(
            'tp-license',
            __('Customers', 'tp-license'),
            __('Customers', 'tp-license'),
            'manage_options',
            'tp-license-customers',
            [new TpLicense\Admin\CustomerManager(), 'render']
        );

        add_submenu_page(
            'tp-license',
            __('IP Whitelist', 'tp-license'),
            __('IP Whitelist', 'tp-license'),
            'manage_options',
            'tp-license-ip-whitelist',
            [new TpLicense\Admin\IpWhitelistManager(), 'render']
        );

        add_submenu_page(
            'tp-license',
            __('Settings', 'tp-license'),
            __('Settings', 'tp-license'),
            'manage_options',
            'tp-license-settings',
            [new TpLicense\Admin\Settings(), 'render']
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook)
    {
        // Only load on our admin pages
        if (strpos($hook, 'tp-license') === false) {
            return;
        }

        // CSS
        wp_enqueue_style(
            'tp-license-admin',
            TP_LICENSE_PLUGIN_URL . 'assets/css/admin.css',
            [],
            TP_LICENSE_VERSION
        );

        // JS
        wp_enqueue_script(
            'tp-license-admin',
            TP_LICENSE_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            TP_LICENSE_VERSION,
            true
        );

        // Localize script
        wp_localize_script('tp-license-admin', 'tpLicense', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tp-license-admin'),
            'restUrl' => rest_url('tp-license/v1/'),
            'restNonce' => wp_create_nonce('wp_rest'),
        ]);
    }

    /**
     * Check license expirations (daily cron)
     */
    public function check_license_expirations()
    {
        global $wpdb;

        $table = $wpdb->prefix . 'tp_licenses';
        $grace_period = get_option('tp_license_grace_period_days', 7);

        // Get expiring licenses (within 7 days)
        $expiring_soon = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$table}
            WHERE status = 'active'
            AND expires_at IS NOT NULL
            AND expires_at <= DATE_ADD(NOW(), INTERVAL %d DAY)
            AND expires_at > NOW()
        ", $grace_period));

        // Send notifications for expiring licenses
        foreach ($expiring_soon as $license) {
            $this->send_expiration_notification($license);
        }

        // Expire licenses past grace period
        $wpdb->query($wpdb->prepare("
            UPDATE {$table}
            SET status = 'expired'
            WHERE status = 'active'
            AND expires_at IS NOT NULL
            AND expires_at < DATE_SUB(NOW(), INTERVAL %d DAY)
        ", $grace_period));
    }

    /**
     * Send expiration notification
     */
    private function send_expiration_notification($license)
    {
        // Get customer email
        $customer = $this->database->get_customer($license->customer_id);
        if (!$customer || !$customer->email) {
            return;
        }

        // Email subject
        $subject = sprintf(
            __('Your TP-Affiliate License Expires Soon', 'tp-license')
        );

        // Email message
        $message = sprintf(
            __('Your license %s will expire on %s. Please renew to continue using TP-Affiliate.', 'tp-license'),
            $license->license_key,
            date('Y-m-d', strtotime($license->expires_at))
        );

        // Send email
        wp_mail($customer->email, $subject, $message);

        // Log notification
        $this->database->log_activity([
            'license_id' => $license->id,
            'action' => 'expiration_notification_sent',
            'details' => json_encode(['email' => $customer->email]),
        ]);
    }

    /**
     * Cleanup old logs (weekly cron)
     */
    public function cleanup_old_logs()
    {
        global $wpdb;

        $retention_days = get_option('tp_license_log_retention_days', 90);
        $table = $wpdb->prefix . 'tp_license_activity_log';

        $wpdb->query($wpdb->prepare("
            DELETE FROM {$table}
            WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)
        ", $retention_days));
    }
}

/**
 * Initialize plugin
 */
function tp_license_init()
{
    return TP_License_Manager::get_instance();
}

// Start the plugin
tp_license_init();
