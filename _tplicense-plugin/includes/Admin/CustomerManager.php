<?php
/**
 * Customer Manager Admin Page
 *
 * @package TpLicense\Admin
 */

namespace TpLicense\Admin;

use TpLicense\Core\Database;

class CustomerManager
{
    private $database;

    public function __construct()
    {
        $this->database = new Database();
    }

    /**
     * Render customer manager page
     */
    public function render()
    {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Customer Management', 'tp-license'); ?></h1>
            <p><?php echo esc_html__('Manage customers who purchased TP-Affiliate licenses.', 'tp-license'); ?></p>
        </div>
        <?php
    }
}
