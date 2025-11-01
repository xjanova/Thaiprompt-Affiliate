<?php
/**
 * Plugin Name: TP-Affiliate License Manager
 * Plugin URI: https://xman4289.com
 * Description: ระบบจัดการ License สำหรับ TP-Affiliate Pro พร้อม WooCommerce Integration
 * Version: 1.0.0
 * Author: Xman Enterprise Co., Ltd.
 * Author URI: https://xman4289.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: tp-license
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * WC requires at least: 7.0
 * WC tested up to: 8.5
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Current plugin version.
 */
define('TP_LICENSE_VERSION', '1.0.0');
define('TP_LICENSE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TP_LICENSE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TP_LICENSE_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * The code that runs during plugin activation.
 */
function activate_tp_license_manager()
{
    require_once TP_LICENSE_PLUGIN_DIR . 'includes/class-activator.php';
    TP_License_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_tp_license_manager()
{
    require_once TP_LICENSE_PLUGIN_DIR . 'includes/class-deactivator.php';
    TP_License_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_tp_license_manager');
register_deactivation_hook(__FILE__, 'deactivate_tp_license_manager');

/**
 * The core plugin class.
 */
require TP_LICENSE_PLUGIN_DIR . 'includes/class-license-manager.php';

/**
 * Begins execution of the plugin.
 */
function run_tp_license_manager()
{
    $plugin = new TP_License_Manager();
    $plugin->run();
}

run_tp_license_manager();
