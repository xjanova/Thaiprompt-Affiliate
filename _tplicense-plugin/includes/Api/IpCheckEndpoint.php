<?php
/**
 * IP Check Endpoint
 *
 * @package TpLicense\Api
 */

namespace TpLicense\Api;

use TpLicense\Core\Database;
use TpLicense\Core\IpManager;

class IpCheckEndpoint
{
    private $database;
    private $ip_manager;

    public function __construct()
    {
        $this->database = new Database();
        $this->ip_manager = new IpManager();
    }

    /**
     * Handle IP check request
     */
    public function handle($request)
    {
        // Get parameters
        $license_key = sanitize_text_field($request->get_param('license_key'));
        $ip = sanitize_text_field($request->get_param('ip'));

        // Validate required fields
        if (empty($license_key) || empty($ip)) {
            return new \WP_REST_Response([
                'success' => false,
                'code' => 'missing_fields',
                'message' => 'Missing required fields',
            ], 400);
        }

        // Get license
        $license = $this->database->get_license_by_key($license_key);

        if (!$license) {
            return new \WP_REST_Response([
                'success' => false,
                'code' => 'invalid_license',
                'message' => 'Invalid license key',
            ], 404);
        }

        // Check if IP whitelist is enabled
        if (!get_option('tp_license_enable_ip_whitelist', true)) {
            // IP whitelist disabled, allow all IPs
            return new \WP_REST_Response([
                'success' => true,
                'allowed' => true,
                'message' => 'IP whitelist is disabled, all IPs are allowed',
            ], 200);
        }

        // Check if IP is whitelisted
        $allowed = $this->ip_manager->is_ip_allowed($license->id, $ip);

        // Log IP check
        $this->database->log_activity([
            'license_id' => $license->id,
            'action' => 'ip_check',
            'ip_address' => $ip,
            'details' => json_encode([
                'allowed' => $allowed,
            ]),
        ]);

        if (!$allowed) {
            return new \WP_REST_Response([
                'success' => true,
                'allowed' => false,
                'message' => 'IP address is not whitelisted for this license',
            ], 200);
        }

        return new \WP_REST_Response([
            'success' => true,
            'allowed' => true,
            'message' => 'IP is whitelisted',
        ], 200);
    }
}
