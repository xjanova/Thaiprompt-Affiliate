<?php
/**
 * Add-on API Endpoints
 */

require_once TP_LICENSE_PLUGIN_DIR . 'includes/class-addon-manager.php';

class TP_License_Addon_API
{
    public function register_routes()
    {
        // Get Available Add-ons
        register_rest_route('tp-license/v1', '/addons', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_available_addons'),
            'permission_callback' => '__return_true',
        ));

        // Get Activated Add-ons
        register_rest_route('tp-license/v1', '/addons/activated', array(
            'methods' => 'POST',
            'callback' => array($this, 'get_activated_addons'),
            'permission_callback' => '__return_true',
        ));

        // Validate Add-on License
        register_rest_route('tp-license/v1', '/addons/validate', array(
            'methods' => 'POST',
            'callback' => array($this, 'validate_addon'),
            'permission_callback' => '__return_true',
        ));

        // Check Add-on Status
        register_rest_route('tp-license/v1', '/addons/status', array(
            'methods' => 'POST',
            'callback' => array($this, 'check_addon_status'),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * รายการ Add-ons ทั้งหมดที่มีจำหน่าย
     */
    public function get_available_addons($request)
    {
        $addons = TP_License_Addon_Manager::get_available_addons();

        return new WP_REST_Response(array(
            'success' => true,
            'addons' => $addons,
        ), 200);
    }

    /**
     * รายการ Add-ons ที่เปิดใช้งาน
     */
    public function get_activated_addons($request)
    {
        $license_key = sanitize_text_field($request->get_param('license_key'));

        if (empty($license_key)) {
            return new WP_Error('missing_license_key', 'License key is required', array('status' => 400));
        }

        $activated_addons = TP_License_Addon_Manager::get_activated_addons($license_key);

        return new WP_REST_Response(array(
            'success' => true,
            'addons' => $activated_addons,
        ), 200);
    }

    /**
     * ตรวจสอบ Add-on License
     */
    public function validate_addon($request)
    {
        $core_license_key = sanitize_text_field($request->get_param('license_key'));
        $addon_slug = sanitize_text_field($request->get_param('addon_slug'));
        $addon_license_key = sanitize_text_field($request->get_param('addon_license_key'));

        if (empty($core_license_key) || empty($addon_slug)) {
            return new WP_Error('missing_params', 'Required parameters are missing', array('status' => 400));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'tp_licenses';

        // ตรวจสอบ Core License
        $core_license = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE license_key = %s AND license_type = 'core' AND status = 'active'",
            $core_license_key
        ));

        if (!$core_license) {
            return new WP_Error('invalid_core_license', 'Invalid or inactive core license', array('status' => 403));
        }

        // ตรวจสอบ Add-on License
        $addon_license = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE license_key = %s AND license_type = %s AND parent_license_id = %d AND status = 'active'",
            $addon_license_key ?: '',
            $addon_slug,
            $core_license->id
        ));

        if (!$addon_license) {
            // ค้นหาโดยใช้ core license id และ addon slug
            $addon_license = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table WHERE license_type = %s AND parent_license_id = %d AND status = 'active'",
                $addon_slug,
                $core_license->id
            ));
        }

        if (!$addon_license) {
            return new WP_Error('addon_not_activated', 'Add-on is not activated', array('status' => 403));
        }

        // ตรวจสอบวันหมดอายุ
        if ($addon_license->expires_at && strtotime($addon_license->expires_at) < time()) {
            return new WP_Error('addon_expired', 'Add-on license has expired', array('status' => 403));
        }

        $addon_info = TP_License_Addon_Manager::get_addon_info($addon_slug);

        return new WP_REST_Response(array(
            'success' => true,
            'addon' => array(
                'slug' => $addon_slug,
                'name' => $addon_info['name'],
                'version' => $addon_info['version'],
                'license_key' => $addon_license->license_key,
                'status' => 'active',
                'activated_at' => $addon_license->activated_at,
                'expires_at' => $addon_license->expires_at,
            ),
        ), 200);
    }

    /**
     * ตรวจสอบสถานะ Add-on
     */
    public function check_addon_status($request)
    {
        $license_key = sanitize_text_field($request->get_param('license_key'));
        $addons = $request->get_param('addons'); // array of addon slugs

        if (empty($license_key)) {
            return new WP_Error('missing_license_key', 'License key is required', array('status' => 400));
        }

        $addon_status = array();

        foreach ((array)$addons as $addon_slug) {
            $is_activated = TP_License_Addon_Manager::is_addon_activated($license_key, $addon_slug);

            $addon_status[$addon_slug] = array(
                'activated' => $is_activated,
                'info' => TP_License_Addon_Manager::get_addon_info($addon_slug),
            );
        }

        return new WP_REST_Response(array(
            'success' => true,
            'license_key' => $license_key,
            'addons' => $addon_status,
        ), 200);
    }
}
