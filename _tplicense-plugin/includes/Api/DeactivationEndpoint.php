<?php
/**
 * License Deactivation Endpoint
 *
 * @package TpLicense\Api
 */

namespace TpLicense\Api;

use TpLicense\Core\Database;

class DeactivationEndpoint
{
    private $database;

    public function __construct()
    {
        $this->database = new Database();
    }

    /**
     * Handle deactivation request
     */
    public function handle($request)
    {
        // Get parameters
        $license_key = sanitize_text_field($request->get_param('license_key'));
        $domain = sanitize_text_field($request->get_param('domain'));

        // Validate required fields
        if (empty($license_key) || empty($domain)) {
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

        // Deactivate
        $result = $this->database->deactivate_license($license->id, $domain);

        if ($result === false) {
            return new \WP_REST_Response([
                'success' => false,
                'code' => 'deactivation_failed',
                'message' => 'Failed to deactivate license',
            ], 500);
        }

        // Log deactivation
        $this->database->log_activity([
            'license_id' => $license->id,
            'action' => 'license_deactivated',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'details' => json_encode([
                'domain' => $domain,
            ]),
        ]);

        // Return success response
        return new \WP_REST_Response([
            'success' => true,
            'message' => 'License deactivated successfully',
        ], 200);
    }
}
