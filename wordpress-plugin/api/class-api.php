<?php
/**
 * REST API Handler
 */

class TP_License_API
{
    public function register_routes()
    {
        // Validate License
        register_rest_route('tp-license/v1', '/validate', array(
            'methods' => 'POST',
            'callback' => array($this, 'validate_license'),
            'permission_callback' => '__return_true',
        ));

        // Activate License
        register_rest_route('tp-license/v1', '/activate', array(
            'methods' => 'POST',
            'callback' => array($this, 'activate_license'),
            'permission_callback' => '__return_true',
        ));

        // Deactivate License
        register_rest_route('tp-license/v1', '/deactivate', array(
            'methods' => 'POST',
            'callback' => array($this, 'deactivate_license'),
            'permission_callback' => '__return_true',
        ));

        // Check for Updates
        register_rest_route('tp-license/v1', '/check-update', array(
            'methods' => 'POST',
            'callback' => array($this, 'check_update'),
            'permission_callback' => '__return_true',
        ));

        // Download
        register_rest_route('tp-license/v1', '/download', array(
            'methods' => 'POST',
            'callback' => array($this, 'download'),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * Validate License
     */
    public function validate_license($request)
    {
        $license_key = sanitize_text_field($request->get_param('license_key'));
        $domain = sanitize_text_field($request->get_param('domain'));
        $ip = sanitize_text_field($request->get_param('ip'));
        $installation_id = sanitize_text_field($request->get_param('installation_id'));

        if (empty($license_key)) {
            return new WP_Error('missing_license_key', 'License key is required', array('status' => 400));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'tp_licenses';

        $license = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE license_key = %s",
            $license_key
        ));

        if (!$license) {
            return new WP_Error('invalid_license', 'Invalid license key', array('status' => 404));
        }

        // Check if license is active
        if ($license->status !== 'active') {
            return new WP_Error('inactive_license', 'License is not active', array('status' => 403));
        }

        // Check expiry
        if ($license->expires_at && strtotime($license->expires_at) < time()) {
            return new WP_Error('expired_license', 'License has expired', array('status' => 403));
        }

        // Check domain match (if domain is set)
        if ($license->domain && $license->domain !== $domain) {
            // Allow localhost for development
            $settings = get_option('tp_license_settings');
            if (!($settings['allow_localhost'] && in_array($domain, ['localhost', '127.0.0.1', '::1']))) {
                return new WP_Error('domain_mismatch', 'License is not valid for this domain', array('status' => 403));
            }
        }

        // Update last check time
        $activation_table = $wpdb->prefix . 'tp_license_activations';
        $wpdb->update(
            $activation_table,
            array('last_check_at' => current_time('mysql')),
            array('license_id' => $license->id, 'domain' => $domain),
            array('%s'),
            array('%d', '%s')
        );

        // Return license info
        return new WP_REST_Response(array(
            'success' => true,
            'license' => array(
                'key' => $license->license_key,
                'status' => $license->status,
                'product_id' => $license->product_id,
                'customer_email' => $license->customer_email,
                'customer_name' => $license->customer_name,
                'domain' => $license->domain,
                'max_activations' => $license->max_activations,
                'activation_count' => $license->activation_count,
                'expires_at' => $license->expires_at,
                'created_at' => $license->created_at,
            ),
        ), 200);
    }

    /**
     * Activate License
     */
    public function activate_license($request)
    {
        $license_key = sanitize_text_field($request->get_param('license_key'));
        $domain = sanitize_text_field($request->get_param('domain'));
        $ip = sanitize_text_field($request->get_param('ip'));
        $installation_id = sanitize_text_field($request->get_param('installation_id'));
        $php_version = sanitize_text_field($request->get_param('php_version'));
        $laravel_version = sanitize_text_field($request->get_param('laravel_version'));
        $user_agent = sanitize_text_field($request->get_param('user_agent'));

        if (empty($license_key) || empty($domain)) {
            return new WP_Error('missing_params', 'License key and domain are required', array('status' => 400));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'tp_licenses';

        $license = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE license_key = %s",
            $license_key
        ));

        if (!$license) {
            return new WP_Error('invalid_license', 'Invalid license key', array('status' => 404));
        }

        // Check if already activated on this domain
        $activation_table = $wpdb->prefix . 'tp_license_activations';
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $activation_table WHERE license_id = %d AND domain = %s AND status = 'active'",
            $license->id,
            $domain
        ));

        if ($existing) {
            // Already activated, just return success
            return new WP_REST_Response(array(
                'success' => true,
                'message' => 'License already activated on this domain',
                'activation' => array(
                    'domain' => $domain,
                    'activated_at' => $existing->activated_at,
                ),
            ), 200);
        }

        // Check activation limit
        if ($license->activation_count >= $license->max_activations) {
            return new WP_Error('activation_limit', 'License activation limit reached', array('status' => 403));
        }

        // Create new activation
        $wpdb->insert(
            $activation_table,
            array(
                'license_id' => $license->id,
                'domain' => $domain,
                'ip_address' => $ip,
                'installation_id' => $installation_id,
                'user_agent' => $user_agent,
                'php_version' => $php_version,
                'laravel_version' => $laravel_version,
                'status' => 'active',
                'activated_at' => current_time('mysql'),
                'last_check_at' => current_time('mysql'),
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        // Update license
        $wpdb->update(
            $table,
            array(
                'domain' => $domain,
                'ip_address' => $ip,
                'installation_id' => $installation_id,
                'activation_count' => $license->activation_count + 1,
                'activated_at' => current_time('mysql'),
            ),
            array('id' => $license->id),
            array('%s', '%s', '%s', '%d', '%s'),
            array('%d')
        );

        // Send activation email
        $this->send_activation_email($license, $domain);

        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'License activated successfully',
            'activation' => array(
                'domain' => $domain,
                'activated_at' => current_time('mysql'),
            ),
        ), 200);
    }

    /**
     * Deactivate License
     */
    public function deactivate_license($request)
    {
        $license_key = sanitize_text_field($request->get_param('license_key'));
        $domain = sanitize_text_field($request->get_param('domain'));

        if (empty($license_key) || empty($domain)) {
            return new WP_Error('missing_params', 'License key and domain are required', array('status' => 400));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'tp_licenses';

        $license = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE license_key = %s",
            $license_key
        ));

        if (!$license) {
            return new WP_Error('invalid_license', 'Invalid license key', array('status' => 404));
        }

        // Deactivate
        $activation_table = $wpdb->prefix . 'tp_license_activations';
        $wpdb->update(
            $activation_table,
            array(
                'status' => 'inactive',
                'deactivated_at' => current_time('mysql'),
            ),
            array(
                'license_id' => $license->id,
                'domain' => $domain,
            ),
            array('%s', '%s'),
            array('%d', '%s')
        );

        // Decrease activation count
        $wpdb->update(
            $table,
            array('activation_count' => max(0, $license->activation_count - 1)),
            array('id' => $license->id),
            array('%d'),
            array('%d')
        );

        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'License deactivated successfully',
        ), 200);
    }

    /**
     * Check for Updates
     */
    public function check_update($request)
    {
        $license_key = sanitize_text_field($request->get_param('license_key'));
        $current_version = sanitize_text_field($request->get_param('current_version'));

        // Get latest version from your system
        $latest_version = get_option('tp_affiliate_latest_version', '1.0.0');
        $download_url = get_option('tp_affiliate_download_url', '');

        $update_available = version_compare($current_version, $latest_version, '<');

        return new WP_REST_Response(array(
            'success' => true,
            'current_version' => $current_version,
            'latest_version' => $latest_version,
            'update_available' => $update_available,
            'download_url' => $update_available ? $download_url : null,
            'changelog_url' => 'https://xman4289.com/changelog',
        ), 200);
    }

    /**
     * Download
     */
    public function download($request)
    {
        $license_key = sanitize_text_field($request->get_param('license_key'));
        $version = sanitize_text_field($request->get_param('version'));

        global $wpdb;
        $table = $wpdb->prefix . 'tp_licenses';

        $license = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE license_key = %s AND status = 'active'",
            $license_key
        ));

        if (!$license) {
            return new WP_Error('invalid_license', 'Invalid or inactive license key', array('status' => 403));
        }

        // Log download
        $download_table = $wpdb->prefix . 'tp_license_downloads';
        $wpdb->insert(
            $download_table,
            array(
                'license_id' => $license->id,
                'version' => $version,
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'downloaded_at' => current_time('mysql'),
            ),
            array('%d', '%s', '%s', '%s')
        );

        // Return download URL (secured)
        $download_url = add_query_arg(array(
            'action' => 'tp_download',
            'license' => $license_key,
            'version' => $version,
            'nonce' => wp_create_nonce('tp_download_' . $license_key),
        ), home_url());

        return new WP_REST_Response(array(
            'success' => true,
            'download_url' => $download_url,
            'version' => $version,
        ), 200);
    }

    /**
     * Send activation email
     */
    private function send_activation_email($license, $domain)
    {
        $settings = get_option('tp_license_settings');

        if (!$settings['email_notifications']) {
            return;
        }

        $to = $license->customer_email;
        $subject = 'License Activated - TP-Affiliate Pro';
        $message = sprintf(
            "สวัสดีคุณ %s,\n\nLicense ของคุณถูกเปิดใช้งานเรียบร้อยแล้ว\n\nLicense Key: %s\nโดเมน: %s\nวันที่เปิดใช้งาน: %s\nหมดอายุ: %s\n\nขอบคุณที่ใช้บริการ TP-Affiliate Pro\n\nXman Enterprise Co., Ltd.\nhttps://xman4289.com",
            $license->customer_name ?: $license->customer_email,
            $license->license_key,
            $domain,
            current_time('mysql'),
            $license->expires_at ?: 'ไม่หมดอายุ'
        );

        wp_mail($to, $subject, $message);
    }
}
