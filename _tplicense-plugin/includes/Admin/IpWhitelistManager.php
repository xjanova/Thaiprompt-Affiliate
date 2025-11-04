<?php
/**
 * IP Whitelist Manager Admin Page
 *
 * @package TpLicense\Admin
 */

namespace TpLicense\Admin;

use TpLicense\Core\Database;
use TpLicense\Core\IpManager;

class IpWhitelistManager
{
    private $database;
    private $ip_manager;

    public function __construct()
    {
        $this->database = new Database();
        $this->ip_manager = new IpManager();
    }

    /**
     * Render IP whitelist manager page
     */
    public function render()
    {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('IP Whitelist Management', 'tp-license'); ?></h1>
            <p><?php echo esc_html__('Manage IP addresses allowed to install TP-Affiliate.', 'tp-license'); ?></p>

            <div class="tp-ip-whitelist-info">
                <p>
                    <strong><?php echo esc_html__('Important:', 'tp-license'); ?></strong>
                    <?php echo esc_html__('Only IP addresses in the whitelist can install and activate TP-Affiliate licenses.', 'tp-license'); ?>
                </p>
            </div>
        </div>
        <?php
    }
}
