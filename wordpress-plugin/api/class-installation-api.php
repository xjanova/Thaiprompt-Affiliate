<?php
/**
 * Installation & Update Authorization API
 *
 * API สำหรับตรวจสอบสิทธิ์การติดตั้งและอัพเดท
 * เชื่อมโยงกับ Dev Release Manager และ Package System
 */

class TP_License_Installation_API
{
    /**
     * Register API routes
     */
    public function register_routes()
    {
        // Installation Authorization
        register_rest_route('tp-license/v1', '/installation/authorize', array(
            'methods' => 'POST',
            'callback' => array($this, 'authorize_installation'),
            'permission_callback' => '__return_true',
        ));

        // Update Authorization
        register_rest_route('tp-license/v1', '/update/authorize', array(
            'methods' => 'POST',
            'callback' => array($this, 'authorize_update'),
            'permission_callback' => '__return_true',
        ));

        // Get Available Updates for License
        register_rest_route('tp-license/v1', '/updates/available', array(
            'methods' => 'POST',
            'callback' => array($this, 'get_available_updates'),
            'permission_callback' => '__return_true',
        ));

        // Get Package Info
        register_rest_route('tp-license/v1', '/package/info', array(
            'methods' => 'POST',
            'callback' => array($this, 'get_package_info'),
            'permission_callback' => '__return_true',
        ));

        // Track Installation Activity
        register_rest_route('tp-license/v1', '/installation/track', array(
            'methods' => 'POST',
            'callback' => array($this, 'track_installation_activity'),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * Authorize Installation
     */
    public function authorize_installation($request)
    {
        $license_key = sanitize_text_field($request->get_param('license_key'));
        $domain = sanitize_text_field($request->get_param('domain'));
        $ip = sanitize_text_field($request->get_param('ip'));
        $installation_id = sanitize_text_field($request->get_param('installation_id'));
        $action = sanitize_text_field($request->get_param('action')); // 'install' or 'update'
        $server_info = $request->get_param('server_info');

        // Validate required parameters
        if (empty($license_key)) {
            return new WP_Error(
                'missing_license',
                'License key is required',
                array('status' => 400, 'code' => 'missing_license')
            );
        }

        global $wpdb;
        $license_table = $wpdb->prefix . 'tp_licenses';
        $package_table = $wpdb->prefix . 'tp_packages';
        $installation_table = $wpdb->prefix . 'tp_installations';

        // Get license
        $license = $wpdb->get_row($wpdb->prepare(
            "SELECT l.*, p.*
            FROM $license_table l
            LEFT JOIN $package_table p ON l.package_id = p.id
            WHERE l.license_key = %s",
            $license_key
        ));

        if (!$license) {
            return $this->authorization_error('invalid_license', 'License key not found');
        }

        // Check license status
        if ($license->status !== 'active') {
            return $this->authorization_error('inactive_license', 'License is not active');
        }

        // Check expiry
        if ($license->expires_at && strtotime($license->expires_at) < time()) {
            return $this->authorization_error('expired_license', 'License has expired');
        }

        // Check package permissions
        if ($action === 'install' && !$this->check_package_permission($license, 'can_install')) {
            return $this->authorization_error('no_install_permission', 'Package does not allow new installations');
        }

        // Check installation limit
        $installation_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $installation_table
            WHERE license_id = %d AND status = 'active'",
            $license->id
        ));

        if ($action === 'install' && $installation_count >= $license->max_installations) {
            return $this->authorization_error(
                'installation_limit',
                sprintf('Maximum installations reached (%d/%d)', $installation_count, $license->max_installations)
            );
        }

        // Check if installation already exists
        $existing_installation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $installation_table
            WHERE installation_id = %s AND license_id = %d",
            $installation_id,
            $license->id
        ));

        if ($existing_installation) {
            // Update existing installation
            $wpdb->update(
                $installation_table,
                array(
                    'domain' => $domain,
                    'ip_address' => $ip,
                    'server_info' => maybe_serialize($server_info),
                    'last_check_at' => current_time('mysql'),
                ),
                array('id' => $existing_installation->id),
                array('%s', '%s', '%s', '%s'),
                array('%d')
            );
        } else {
            // Create new installation record
            $wpdb->insert(
                $installation_table,
                array(
                    'license_id' => $license->id,
                    'installation_id' => $installation_id,
                    'domain' => $domain,
                    'ip_address' => $ip,
                    'server_info' => maybe_serialize($server_info),
                    'status' => 'active',
                    'installed_at' => current_time('mysql'),
                    'last_check_at' => current_time('mysql'),
                ),
                array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
            );
        }

        // Log installation activity
        $this->log_installation_activity($license->id, $installation_id, $action, 'authorized', $server_info);

        // Send notification to customer
        if ($action === 'install') {
            $this->send_installation_notification($license, $domain);
        }

        // Return authorization success
        return new WP_REST_Response(array(
            'success' => true,
            'authorized' => true,
            'message' => ucfirst($action) . ' authorized successfully',
            'license' => array(
                'key' => $license->license_key,
                'type' => $license->license_type,
                'status' => $license->status,
                'expires_at' => $license->expires_at,
                'max_installations' => $license->max_installations,
                'current_installations' => $installation_count + ($existing_installation ? 0 : 1),
            ),
            'package' => array(
                'name' => $license->name,
                'features' => maybe_unserialize($license->features),
                'update_period' => $license->update_period,
                'support_period' => $license->support_period,
            ),
            'installation_id' => $installation_id,
        ), 200);
    }

    /**
     * Authorize Update
     */
    public function authorize_update($request)
    {
        $license_key = sanitize_text_field($request->get_param('license_key'));
        $installation_id = sanitize_text_field($request->get_param('installation_id'));
        $version = sanitize_text_field($request->get_param('version'));
        $current_version = sanitize_text_field($request->get_param('current_version'));
        $server_info = $request->get_param('server_info');

        if (empty($license_key) || empty($installation_id)) {
            return $this->authorization_error('missing_params', 'License key and installation ID are required');
        }

        global $wpdb;
        $license_table = $wpdb->prefix . 'tp_licenses';
        $package_table = $wpdb->prefix . 'tp_packages';
        $installation_table = $wpdb->prefix . 'tp_installations';
        $release_table = $wpdb->prefix . 'tp_releases';

        // Get license with package info
        $license = $wpdb->get_row($wpdb->prepare(
            "SELECT l.*, p.*
            FROM $license_table l
            LEFT JOIN $package_table p ON l.package_id = p.id
            WHERE l.license_key = %s",
            $license_key
        ));

        if (!$license) {
            return $this->authorization_error('invalid_license', 'License key not found');
        }

        // Verify installation belongs to this license
        $installation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $installation_table
            WHERE installation_id = %s AND license_id = %d",
            $installation_id,
            $license->id
        ));

        if (!$installation) {
            return $this->authorization_error('invalid_installation', 'Installation not found or does not belong to this license');
        }

        // Check update permissions based on package
        if (!$this->check_update_permission($license, $version)) {
            return $this->authorization_error(
                'no_update_permission',
                'Your package does not include access to this update. Please upgrade your package.'
            );
        }

        // Get release info
        $release = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $release_table WHERE version = %s AND status = 'published'",
            $version
        ));

        if (!$release) {
            return $this->authorization_error('release_not_found', 'Release version not found or not published');
        }

        // Update installation record
        $wpdb->update(
            $installation_table,
            array(
                'current_version' => $current_version,
                'server_info' => maybe_serialize($server_info),
                'last_check_at' => current_time('mysql'),
            ),
            array('id' => $installation->id),
            array('%s', '%s', '%s'),
            array('%d')
        );

        // Log update activity
        $this->log_installation_activity($license->id, $installation_id, 'update', 'authorized', array(
            'from_version' => $current_version,
            'to_version' => $version,
        ));

        // Return authorization with update info
        return new WP_REST_Response(array(
            'success' => true,
            'authorized' => true,
            'message' => 'Update authorized',
            'license_info' => array(
                'key' => $license->license_key,
                'status' => $license->status,
                'expires_at' => $license->expires_at,
            ),
            'update_info' => array(
                'version' => $release->version,
                'release_date' => $release->published_at,
                'download_url' => $release->download_url,
                'changelog' => $release->changelog,
                'requires_php' => $release->requires_php,
                'requires_mysql' => $release->requires_mysql,
            ),
        ), 200);
    }

    /**
     * Get Available Updates
     */
    public function get_available_updates($request)
    {
        $license_key = sanitize_text_field($request->get_param('license_key'));
        $current_version = sanitize_text_field($request->get_param('current_version'));

        global $wpdb;
        $license_table = $wpdb->prefix . 'tp_licenses';
        $package_table = $wpdb->prefix . 'tp_packages';
        $release_table = $wpdb->prefix . 'tp_releases';

        $license = $wpdb->get_row($wpdb->prepare(
            "SELECT l.*, p.*
            FROM $license_table l
            LEFT JOIN $package_table p ON l.package_id = p.id
            WHERE l.license_key = %s AND l.status = 'active'",
            $license_key
        ));

        if (!$license) {
            return new WP_Error('invalid_license', 'Invalid license', array('status' => 403));
        }

        // Get releases available for this package
        $releases = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $release_table
            WHERE status = 'published'
            AND version > %s
            ORDER BY published_at DESC",
            $current_version
        ));

        // Filter based on package permissions
        $available_updates = array();
        foreach ($releases as $release) {
            if ($this->check_update_permission($license, $release->version)) {
                $available_updates[] = array(
                    'version' => $release->version,
                    'release_date' => $release->published_at,
                    'changelog' => $release->changelog,
                    'type' => $release->release_type,
                    'critical' => (bool) $release->is_critical,
                );
            }
        }

        return new WP_REST_Response(array(
            'success' => true,
            'current_version' => $current_version,
            'updates_available' => count($available_updates),
            'updates' => $available_updates,
        ), 200);
    }

    /**
     * Get Package Info
     */
    public function get_package_info($request)
    {
        $license_key = sanitize_text_field($request->get_param('license_key'));

        global $wpdb;
        $license_table = $wpdb->prefix . 'tp_licenses';
        $package_table = $wpdb->prefix . 'tp_packages';

        $license = $wpdb->get_row($wpdb->prepare(
            "SELECT l.*, p.*
            FROM $license_table l
            LEFT JOIN $package_table p ON l.package_id = p.id
            WHERE l.license_key = %s",
            $license_key
        ));

        if (!$license) {
            return new WP_Error('invalid_license', 'Invalid license', array('status' => 404));
        }

        return new WP_REST_Response(array(
            'success' => true,
            'package' => array(
                'name' => $license->name,
                'slug' => $license->slug,
                'price' => $license->price,
                'features' => maybe_unserialize($license->features),
                'max_installations' => $license->max_installations,
                'update_period' => $license->update_period,
                'support_period' => $license->support_period,
            ),
            'license' => array(
                'status' => $license->status,
                'type' => $license->license_type,
                'expires_at' => $license->expires_at,
                'created_at' => $license->created_at,
            ),
        ), 200);
    }

    /**
     * Track Installation Activity
     */
    public function track_installation_activity($request)
    {
        $installation_id = sanitize_text_field($request->get_param('installation_id'));
        $event = sanitize_text_field($request->get_param('event'));
        $data = $request->get_param('data');

        global $wpdb;
        $installation_table = $wpdb->prefix . 'tp_installations';

        $installation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $installation_table WHERE installation_id = %s",
            $installation_id
        ));

        if (!$installation) {
            return new WP_Error('invalid_installation', 'Installation not found', array('status' => 404));
        }

        $this->log_installation_activity($installation->license_id, $installation_id, $event, 'tracked', $data);

        // Update last check time
        $wpdb->update(
            $installation_table,
            array('last_check_at' => current_time('mysql')),
            array('id' => $installation->id),
            array('%s'),
            array('%d')
        );

        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Activity tracked',
        ), 200);
    }

    /**
     * Helper: Check package permission
     */
    private function check_package_permission($license, $permission)
    {
        $features = maybe_unserialize($license->features);
        return isset($features[$permission]) && $features[$permission];
    }

    /**
     * Helper: Check update permission
     */
    private function check_update_permission($license, $version)
    {
        // Check if license has active support/updates
        if ($license->update_period === 'lifetime') {
            return true;
        }

        // Check update expiry
        if ($license->update_expires_at && strtotime($license->update_expires_at) < time()) {
            return false;
        }

        // Check package update permissions
        $features = maybe_unserialize($license->features);
        if (isset($features['updates']) && !$features['updates']) {
            return false;
        }

        return true;
    }

    /**
     * Helper: Log installation activity
     */
    private function log_installation_activity($license_id, $installation_id, $action, $status, $data = null)
    {
        global $wpdb;
        $log_table = $wpdb->prefix . 'tp_installation_logs';

        $wpdb->insert(
            $log_table,
            array(
                'license_id' => $license_id,
                'installation_id' => $installation_id,
                'action' => $action,
                'status' => $status,
                'data' => maybe_serialize($data),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'created_at' => current_time('mysql'),
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s')
        );
    }

    /**
     * Helper: Send installation notification
     */
    private function send_installation_notification($license, $domain)
    {
        $settings = get_option('tp_license_settings', array());

        if (empty($settings['email_notifications'])) {
            return;
        }

        $to = $license->customer_email;
        $subject = 'New Installation - TP-Affiliate Pro';
        $message = sprintf(
            "สวัสดีคุณ %s,\n\nระบบตรวจพบการติดตั้งใหม่\n\nLicense Key: %s\nโดเมน: %s\nเวลา: %s\n\nหากคุณไม่ได้ทำการติดตั้ง กรุณาติดต่อฝ่ายสนับสนุนทันที\n\nXman Enterprise Co., Ltd.\nhttps://xman4289.com",
            $license->customer_name ?: $license->customer_email,
            $license->license_key,
            $domain,
            current_time('mysql')
        );

        wp_mail($to, $subject, $message);
    }

    /**
     * Helper: Return authorization error
     */
    private function authorization_error($code, $message)
    {
        return new WP_REST_Response(array(
            'success' => false,
            'authorized' => false,
            'code' => $code,
            'message' => $message,
        ), 403);
    }
}
