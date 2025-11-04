<?php
/**
 * IP Manager
 *
 * @package TpLicense\Core
 */

namespace TpLicense\Core;

class IpManager
{
    private $database;

    public function __construct()
    {
        $this->database = new Database();
    }

    /**
     * Check if IP is allowed for license
     */
    public function is_ip_allowed($license_id, $ip_address)
    {
        // If IP whitelist is disabled, allow all IPs
        if (!get_option('tp_license_enable_ip_whitelist', true)) {
            return true;
        }

        return $this->database->is_ip_whitelisted($license_id, $ip_address);
    }

    /**
     * Add IP to whitelist
     */
    public function add_ip($license_id, $ip_address, $description = '')
    {
        return $this->database->add_ip_to_whitelist($license_id, $ip_address, $description);
    }

    /**
     * Remove IP from whitelist
     */
    public function remove_ip($license_id, $ip_address)
    {
        return $this->database->remove_ip_from_whitelist($license_id, $ip_address);
    }

    /**
     * Validate IP address format
     */
    public function is_valid_ip($ip_address)
    {
        return filter_var($ip_address, FILTER_VALIDATE_IP) !== false;
    }
}
