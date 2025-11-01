<?php
/**
 * Fired during plugin deactivation
 */

class TP_License_Deactivator
{
    public static function deactivate()
    {
        // Clear scheduled hooks
        wp_clear_scheduled_hook('tp_license_daily_check');

        // Flush rewrite rules
        flush_rewrite_rules();
    }
}
