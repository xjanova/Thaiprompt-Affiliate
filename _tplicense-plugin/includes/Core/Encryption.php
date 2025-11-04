<?php
/**
 * Encryption Helper
 *
 * @package TpLicense\Core
 */

namespace TpLicense\Core;

class Encryption
{
    /**
     * Encrypt data
     */
    public static function encrypt($data, $key = null)
    {
        if ($key === null) {
            $key = self::get_encryption_key();
        }

        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);

        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt data
     */
    public static function decrypt($data, $key = null)
    {
        if ($key === null) {
            $key = self::get_encryption_key();
        }

        $data = base64_decode($data);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);

        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }

    /**
     * Get encryption key
     */
    private static function get_encryption_key()
    {
        // Use WordPress AUTH_KEY as encryption key
        if (defined('AUTH_KEY')) {
            return substr(AUTH_KEY, 0, 32);
        }

        // Fallback
        return hash('sha256', 'tp-license-encryption-key', true);
    }

    /**
     * Hash license key for storage
     */
    public static function hash_license_key($license_key)
    {
        return hash('sha256', $license_key);
    }
}
