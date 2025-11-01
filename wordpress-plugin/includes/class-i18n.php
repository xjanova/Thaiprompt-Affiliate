<?php
/**
 * Define the internationalization functionality
 */

class TP_License_i18n
{
    public function load_plugin_textdomain()
    {
        load_plugin_textdomain(
            'tp-license',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }
}
