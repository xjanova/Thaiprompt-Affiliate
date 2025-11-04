<?php
/**
 * License Validator
 *
 * @package TpLicense\Core
 */

namespace TpLicense\Core;

class LicenseValidator
{
    private $database;

    public function __construct()
    {
        $this->database = new Database();
    }

    /**
     * Validate license
     */
    public function validate($license_key, $domain = null, $ip = null)
    {
        // Get license
        $license = $this->database->get_license_by_key($license_key);

        if (!$license) {
            return [
                'valid' => false,
                'code' => 'invalid_license',
                'message' => 'Invalid license key',
            ];
        }

        // Check status
        if ($license->status !== 'active') {
            return [
                'valid' => false,
                'code' => 'inactive_license',
                'message' => 'License is not active',
            ];
        }

        // Check expiration
        if ($license->expires_at && strtotime($license->expires_at) < time()) {
            return [
                'valid' => false,
                'code' => 'expired_license',
                'message' => 'License has expired',
            ];
        }

        // Check domain (if provided)
        if ($domain && get_option('tp_license_enable_domain_validation', true)) {
            // Additional domain validation logic here
        }

        // Check IP (if provided)
        if ($ip && get_option('tp_license_enable_ip_whitelist', true)) {
            $ip_manager = new IpManager();
            if (!$ip_manager->is_ip_allowed($license->id, $ip)) {
                return [
                    'valid' => false,
                    'code' => 'ip_not_allowed',
                    'message' => 'IP address not whitelisted',
                ];
            }
        }

        return [
            'valid' => true,
            'license' => $license,
        ];
    }

    /**
     * Generate license key
     */
    public function generate_license_key()
    {
        return sprintf(
            '%s-%s-%s-%s',
            $this->generate_random_string(4),
            $this->generate_random_string(4),
            $this->generate_random_string(4),
            $this->generate_random_string(4)
        );
    }

    /**
     * Generate random string
     */
    private function generate_random_string($length = 4)
    {
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Exclude I, O, 0, 1 for clarity
        $string = '';

        for ($i = 0; $i < $length; $i++) {
            $string .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $string;
    }
}
