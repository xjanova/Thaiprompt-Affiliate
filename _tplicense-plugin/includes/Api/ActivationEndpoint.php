<?php
/**
 * License Activation Endpoint
 *
 * @package TpLicense\Api
 */

namespace TpLicense\Api;

use TpLicense\Core\Database;
use TpLicense\Core\LicenseValidator;
use TpLicense\Core\IpManager;

class ActivationEndpoint
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
     * Handle activation request
     */
    public function handle($request)
    {
        // Get parameters
        $license_key = sanitize_text_field($request->get_param('license_key'));
        $domain = sanitize_text_field($request->get_param('domain'));
        $ip = sanitize_text_field($request->get_param('ip'));
        $installation_id = sanitize_text_field($request->get_param('installation_id'));
        $php_version = sanitize_text_field($request->get_param('php_version'));
        $laravel_version = sanitize_text_field($request->get_param('laravel_version'));
        $user_agent = sanitize_text_field($request->get_param('user_agent'));

        // Validate required fields
        if (empty($license_key) || empty($domain) || empty($ip) || empty($installation_id)) {
            return new \WP_REST_Response([
                'success' => false,
                'code' => 'missing_fields',
                'message' => 'Missing required fields',
            ], 400);
        }

        // Get license
        $license = $this->database->get_license_by_key($license_key);

        if (!$license) {
            $this->database->log_activity([
                'action' => 'activation_failed',
                'ip_address' => $ip,
                'details' => json_encode([
                    'reason' => 'invalid_license',
                    'license_key' => $license_key,
                    'domain' => $domain,
                ]),
            ]);

            return new \WP_REST_Response([
                'success' => false,
                'code' => 'invalid_license',
                'message' => 'Invalid license key',
            ], 404);
        }

        // Check if license is active
        if ($license->status !== 'active') {
            $this->database->log_activity([
                'license_id' => $license->id,
                'action' => 'activation_failed',
                'ip_address' => $ip,
                'details' => json_encode([
                    'reason' => 'inactive_license',
                    'status' => $license->status,
                ]),
            ]);

            return new \WP_REST_Response([
                'success' => false,
                'code' => 'inactive_license',
                'message' => 'License is not active. Current status: ' . $license->status,
            ], 403);
        }

        // Check if IP is whitelisted (if IP whitelist is enabled)
        if (get_option('tp_license_enable_ip_whitelist', true)) {
            if (!$this->ip_manager->is_ip_allowed($license->id, $ip)) {
                $this->database->log_activity([
                    'license_id' => $license->id,
                    'action' => 'activation_failed',
                    'ip_address' => $ip,
                    'details' => json_encode([
                        'reason' => 'ip_not_allowed',
                        'domain' => $domain,
                    ]),
                ]);

                return new \WP_REST_Response([
                    'success' => false,
                    'code' => 'ip_not_allowed',
                    'message' => 'Your IP address (' . $ip . ') is not authorized for this license. Please contact support to add your IP to the whitelist.',
                ], 403);
            }
        }

        // Check if license has expired
        if ($license->expires_at && strtotime($license->expires_at) < time()) {
            // Check grace period
            $grace_period = get_option('tp_license_grace_period_days', 7);
            $grace_end = strtotime($license->expires_at) + ($grace_period * 86400);

            if (time() > $grace_end) {
                return new \WP_REST_Response([
                    'success' => false,
                    'code' => 'expired_license',
                    'message' => 'License has expired',
                ], 403);
            }
        }

        // Check activation count
        $current_activations = $this->database->get_activation_count($license->id);

        if ($current_activations >= $license->max_activations) {
            // Check if this installation is already activated
            $existing_activation = $this->database->get_activation($license->id, $installation_id);

            if (!$existing_activation) {
                return new \WP_REST_Response([
                    'success' => false,
                    'code' => 'max_activations_reached',
                    'message' => 'Maximum number of activations reached (' . $license->max_activations . ')',
                ], 403);
            }

            // Update existing activation
            $this->database->update_activation($existing_activation->id, [
                'domain' => $domain,
                'ip_address' => $ip,
                'php_version' => $php_version,
                'laravel_version' => $laravel_version,
                'user_agent' => $user_agent,
                'last_validated_at' => current_time('mysql'),
            ]);

            $activation_id = $existing_activation->id;
        } else {
            // Create new activation
            $activation_id = $this->database->create_activation([
                'license_id' => $license->id,
                'domain' => $domain,
                'ip_address' => $ip,
                'installation_id' => $installation_id,
                'php_version' => $php_version,
                'laravel_version' => $laravel_version,
                'user_agent' => $user_agent,
                'last_validated_at' => current_time('mysql'),
            ]);
        }

        // Log successful activation
        $this->database->log_activity([
            'license_id' => $license->id,
            'action' => 'license_activated',
            'ip_address' => $ip,
            'details' => json_encode([
                'domain' => $domain,
                'installation_id' => $installation_id,
                'activation_id' => $activation_id,
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
            'activation' => [
                'id' => $activation_id,
                'license_key' => $license->license_key,
                'domain' => $domain,
                'activated_at' => current_time('mysql'),
            ],
            'license' => [
                'status' => $license->status,
                'expires_at' => $license->expires_at,
                'max_activations' => $license->max_activations,
                'current_activations' => $this->database->get_activation_count($license->id),
                'customer' => $customer ? [
                    'name' => $customer->name,
                    'email' => $customer->email,
                ] : null,
            ],
        ], 200);
    }
}
