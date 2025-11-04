<?php
/**
 * License Validation Endpoint
 *
 * @package TpLicense\Api
 */

namespace TpLicense\Api;

use TpLicense\Core\Database;
use TpLicense\Core\LicenseValidator;
use TpLicense\Core\IpManager;

class ValidationEndpoint
{
    private $database;
    private $validator;
    private $ip_manager;

    public function __construct()
    {
        $this->database = new Database();
        $this->validator = new LicenseValidator();
        $this->ip_manager = new IpManager();
    }

    /**
     * Handle validation request
     */
    public function handle($request)
    {
        // Get parameters
        $license_key = sanitize_text_field($request->get_param('license_key'));
        $domain = sanitize_text_field($request->get_param('domain'));
        $ip = sanitize_text_field($request->get_param('ip'));
        $installation_id = sanitize_text_field($request->get_param('installation_id'));

        // Validate required fields
        if (empty($license_key) || empty($domain) || empty($ip)) {
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

        // Check license status
        if ($license->status !== 'active') {
            return new \WP_REST_Response([
                'success' => false,
                'code' => 'inactive_license',
                'message' => 'License is not active',
            ], 403);
        }

        // Check expiration
        if ($license->expires_at && strtotime($license->expires_at) < time()) {
            return new \WP_REST_Response([
                'success' => false,
                'code' => 'expired_license',
                'message' => 'License has expired',
            ], 403);
        }

        // Check if domain is activated
        if ($installation_id) {
            $activation = $this->database->get_activation($license->id, $installation_id);

            if (!$activation) {
                return new \WP_REST_Response([
                    'success' => false,
                    'code' => 'not_activated',
                    'message' => 'License is not activated for this installation',
                ], 403);
            }

            // Check domain match
            if (get_option('tp_license_enable_domain_validation', true)) {
                if ($activation->domain !== $domain) {
                    return new \WP_REST_Response([
                        'success' => false,
                        'code' => 'domain_mismatch',
                        'message' => 'Domain does not match activation',
                    ], 403);
                }
            }

            // Update last validation time
            $this->database->update_activation($activation->id, [
                'last_validated_at' => current_time('mysql'),
            ]);
        }

        // Log validation
        $this->database->log_activity([
            'license_id' => $license->id,
            'action' => 'license_validated',
            'ip_address' => $ip,
            'details' => json_encode([
                'domain' => $domain,
                'installation_id' => $installation_id,
            ]),
        ]);

        // Get customer info
        $customer = null;
        if ($license->customer_id) {
            $customer = $this->database->get_customer($license->customer_id);
        }

        // Return success response
        return new \WP_REST_Response([
            'success' => true,
            'license' => [
                'key' => $license->license_key,
                'status' => $license->status,
                'domain' => $domain,
                'expires_at' => $license->expires_at,
                'customer_name' => $customer ? $customer->name : null,
                'customer_email' => $customer ? $customer->email : null,
                'activation_count' => $this->database->get_activation_count($license->id),
                'max_activations' => $license->max_activations,
                'created_at' => $license->created_at,
            ],
        ], 200);
    }
}
