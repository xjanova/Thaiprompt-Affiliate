<?php
/**
 * Admin Dashboard
 *
 * @package TpLicense\Admin
 */

namespace TpLicense\Admin;

use TpLicense\Core\Database;

class AdminDashboard
{
    private $database;

    public function __construct()
    {
        $this->database = new Database();
    }

    /**
     * Render dashboard
     */
    public function render()
    {
        // Get statistics
        $stats = $this->database->get_statistics();

        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('TP License Manager', 'tp-license'); ?></h1>

            <div class="tp-license-stats">
                <div class="tp-stat-card">
                    <h3><?php echo esc_html__('Total Licenses', 'tp-license'); ?></h3>
                    <p class="tp-stat-number"><?php echo esc_html($stats['total_licenses']); ?></p>
                </div>

                <div class="tp-stat-card">
                    <h3><?php echo esc_html__('Active Licenses', 'tp-license'); ?></h3>
                    <p class="tp-stat-number"><?php echo esc_html($stats['active_licenses']); ?></p>
                </div>

                <div class="tp-stat-card">
                    <h3><?php echo esc_html__('Total Activations', 'tp-license'); ?></h3>
                    <p class="tp-stat-number"><?php echo esc_html($stats['total_activations']); ?></p>
                </div>

                <div class="tp-stat-card warning">
                    <h3><?php echo esc_html__('Expiring Soon', 'tp-license'); ?></h3>
                    <p class="tp-stat-number"><?php echo esc_html($stats['expiring_soon']); ?></p>
                </div>
            </div>

            <h2><?php echo esc_html__('Recent Licenses', 'tp-license'); ?></h2>

            <?php $this->render_licenses_table(); ?>
        </div>
        <?php
    }

    /**
     * Render licenses table
     */
    private function render_licenses_table()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'tp_licenses';

        $licenses = $wpdb->get_results("
            SELECT l.*, c.name as customer_name, c.email as customer_email
            FROM {$table} l
            LEFT JOIN {$wpdb->prefix}tp_customers c ON l.customer_id = c.id
            ORDER BY l.created_at DESC
            LIMIT 20
        ");

        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php echo esc_html__('License Key', 'tp-license'); ?></th>
                    <th><?php echo esc_html__('Customer', 'tp-license'); ?></th>
                    <th><?php echo esc_html__('Status', 'tp-license'); ?></th>
                    <th><?php echo esc_html__('Activations', 'tp-license'); ?></th>
                    <th><?php echo esc_html__('Expires', 'tp-license'); ?></th>
                    <th><?php echo esc_html__('Created', 'tp-license'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($licenses as $license) : ?>
                    <tr>
                        <td><code><?php echo esc_html($license->license_key); ?></code></td>
                        <td>
                            <?php echo esc_html($license->customer_name ?? 'N/A'); ?>
                            <?php if ($license->customer_email) : ?>
                                <br><small><?php echo esc_html($license->customer_email); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="tp-status-badge tp-status-<?php echo esc_attr($license->status); ?>">
                                <?php echo esc_html($license->status); ?>
                            </span>
                        </td>
                        <td>
                            <?php
                            $count = $this->database->get_activation_count($license->id);
                            echo esc_html($count . ' / ' . $license->max_activations);
                            ?>
                        </td>
                        <td><?php echo $license->expires_at ? esc_html(date('Y-m-d', strtotime($license->expires_at))) : 'Never'; ?></td>
                        <td><?php echo esc_html(date('Y-m-d', strtotime($license->created_at))); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
}
