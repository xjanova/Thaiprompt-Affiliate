<?php
/**
 * Settings Page
 *
 * @package TpLicense\Admin
 */

namespace TpLicense\Admin;

class Settings
{
    /**
     * Render settings page
     */
    public function render()
    {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('TP License Settings', 'tp-license'); ?></h1>

            <form method="post" action="options.php">
                <?php
                settings_fields('tp_license_settings');
                do_settings_sections('tp_license_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}
