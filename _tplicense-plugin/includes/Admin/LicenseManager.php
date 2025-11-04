<?php
/**
 * License Manager Admin Page
 *
 * @package TpLicense\Admin
 */

namespace TpLicense\Admin;

use TpLicense\Core\Database;
use TpLicense\Core\LicenseValidator;

class LicenseManager
{
    private $database;
    private $validator;

    public function __construct()
    {
        $this->database = new Database();
        $this->validator = new LicenseValidator();
    }

    /**
     * Render license manager page
     */
    public function render()
    {
        ?>
        <div class="wrap">
            <h1>
                <?php echo esc_html__('License Management', 'tp-license'); ?>
                <a href="#" class="page-title-action" id="tp-add-license">
                    <?php echo esc_html__('Add New License', 'tp-license'); ?>
                </a>
            </h1>

            <p><?php echo esc_html__('Manage TP-Affiliate licenses here.', 'tp-license'); ?></p>

            <!-- License form modal -->
            <div id="tp-license-modal" class="tp-modal" style="display:none;">
                <div class="tp-modal-content">
                    <span class="tp-close">&times;</span>
                    <h2><?php echo esc_html__('Add New License', 'tp-license'); ?></h2>

                    <form id="tp-license-form" method="post">
                        <?php wp_nonce_field('tp_license_add', 'tp_license_nonce'); ?>

                        <table class="form-table">
                            <tr>
                                <th><label for="license_key"><?php echo esc_html__('License Key', 'tp-license'); ?></label></th>
                                <td>
                                    <input type="text" name="license_key" id="license_key" class="regular-text" readonly>
                                    <button type="button" class="button" id="generate-license-key">
                                        <?php echo esc_html__('Generate', 'tp-license'); ?>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="customer_email"><?php echo esc_html__('Customer Email', 'tp-license'); ?></label></th>
                                <td><input type="email" name="customer_email" id="customer_email" class="regular-text"></td>
                            </tr>
                            <tr>
                                <th><label for="max_activations"><?php echo esc_html__('Max Activations', 'tp-license'); ?></label></th>
                                <td><input type="number" name="max_activations" id="max_activations" value="1" min="1"></td>
                            </tr>
                            <tr>
                                <th><label for="expires_at"><?php echo esc_html__('Expires At', 'tp-license'); ?></label></th>
                                <td>
                                    <input type="date" name="expires_at" id="expires_at">
                                    <p class="description"><?php echo esc_html__('Leave empty for lifetime license', 'tp-license'); ?></p>
                                </td>
                            </tr>
                        </table>

                        <p class="submit">
                            <button type="submit" class="button button-primary">
                                <?php echo esc_html__('Create License', 'tp-license'); ?>
                            </button>
                        </p>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }
}
