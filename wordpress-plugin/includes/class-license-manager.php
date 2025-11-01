<?php
/**
 * The core plugin class
 */

class TP_License_Manager
{
    protected $loader;
    protected $plugin_name;
    protected $version;

    public function __construct()
    {
        $this->version = TP_LICENSE_VERSION;
        $this->plugin_name = 'tp-license';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_api_hooks();
        $this->define_woocommerce_hooks();
    }

    private function load_dependencies()
    {
        // Core
        require_once TP_LICENSE_PLUGIN_DIR . 'includes/class-loader.php';
        require_once TP_LICENSE_PLUGIN_DIR . 'includes/class-i18n.php';
        require_once TP_LICENSE_PLUGIN_DIR . 'includes/class-database.php';
        require_once TP_LICENSE_PLUGIN_DIR . 'includes/class-license-generator.php';
        require_once TP_LICENSE_PLUGIN_DIR . 'includes/class-license-validator.php';

        // Admin
        require_once TP_LICENSE_PLUGIN_DIR . 'admin/class-admin.php';
        require_once TP_LICENSE_PLUGIN_DIR . 'admin/class-admin-menu.php';

        // API
        require_once TP_LICENSE_PLUGIN_DIR . 'api/class-api.php';

        // WooCommerce
        if (class_exists('WooCommerce')) {
            require_once TP_LICENSE_PLUGIN_DIR . 'woocommerce/class-woocommerce.php';
            require_once TP_LICENSE_PLUGIN_DIR . 'woocommerce/class-product-type.php';
            require_once TP_LICENSE_PLUGIN_DIR . 'woocommerce/class-order-handler.php';
        }

        $this->loader = new TP_License_Loader();
    }

    private function set_locale()
    {
        $plugin_i18n = new TP_License_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    private function define_admin_hooks()
    {
        $plugin_admin = new TP_License_Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');

        // Admin Menu
        $admin_menu = new TP_License_Admin_Menu();
        $this->loader->add_action('admin_menu', $admin_menu, 'add_plugin_admin_menu');
    }

    private function define_public_hooks()
    {
        // Public hooks if needed
    }

    private function define_api_hooks()
    {
        $plugin_api = new TP_License_API();
        $this->loader->add_action('rest_api_init', $plugin_api, 'register_routes');
    }

    private function define_woocommerce_hooks()
    {
        if (class_exists('WooCommerce')) {
            $woocommerce = new TP_License_WooCommerce();
            $this->loader->add_filter('product_type_selector', $woocommerce, 'add_license_product_type');
            $this->loader->add_action('woocommerce_order_status_completed', $woocommerce, 'generate_license_on_purchase');
        }
    }

    public function run()
    {
        $this->loader->run();
    }

    public function get_plugin_name()
    {
        return $this->plugin_name;
    }

    public function get_loader()
    {
        return $this->loader;
    }

    public function get_version()
    {
        return $this->version;
    }
}
