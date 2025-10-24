<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CloudflareConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'is_active',
        'tunnel_enabled',
        'tunnel_id',
        'tunnel_secret',
        'verify_cf_headers',
        'filter_comments',
        'filter_orders',
        'filter_contact_forms',
        'filter_registrations',
        'challenge_mode',
        'enable_bot_fight_mode',
        'enable_ddos_protection',
        'rate_limit_rules',
        'custom_spam_keywords',
        'ip_whitelist',
        'ip_blacklist',
        'country_whitelist',
        'country_blacklist',
        'enable_waf',
        'waf_rules',
        'security_level',
        'browser_integrity_check',
        'challenge_ttl',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'tunnel_enabled' => 'boolean',
        'verify_cf_headers' => 'boolean',
        'filter_comments' => 'boolean',
        'filter_orders' => 'boolean',
        'filter_contact_forms' => 'boolean',
        'filter_registrations' => 'boolean',
        'enable_bot_fight_mode' => 'boolean',
        'enable_ddos_protection' => 'boolean',
        'enable_waf' => 'boolean',
        'browser_integrity_check' => 'boolean',
        'rate_limit_rules' => 'array',
        'custom_spam_keywords' => 'array',
        'ip_whitelist' => 'array',
        'ip_blacklist' => 'array',
        'country_whitelist' => 'array',
        'country_blacklist' => 'array',
        'waf_rules' => 'array',
        'challenge_ttl' => 'integer',
    ];

    /**
     * Get the active configuration
     *
     * @return CloudflareConfig|null
     */
    public static function getActive()
    {
        return static::where('is_active', true)->first();
    }

    /**
     * Activate this configuration
     *
     * @return bool
     */
    public function activate()
    {
        // Deactivate all other configurations
        static::where('id', '!=', $this->id)->update(['is_active' => false]);

        // Activate this one
        $this->is_active = true;
        return $this->save();
    }

    /**
     * Check if IP is whitelisted
     *
     * @param string $ip
     * @return bool
     */
    public function isIpWhitelisted(string $ip): bool
    {
        if (empty($this->ip_whitelist)) {
            return false;
        }

        return in_array($ip, $this->ip_whitelist);
    }

    /**
     * Check if IP is blacklisted
     *
     * @param string $ip
     * @return bool
     */
    public function isIpBlacklisted(string $ip): bool
    {
        if (empty($this->ip_blacklist)) {
            return false;
        }

        return in_array($ip, $this->ip_blacklist);
    }

    /**
     * Check if country is allowed
     *
     * @param string $countryCode
     * @return bool
     */
    public function isCountryAllowed(string $countryCode): bool
    {
        // If whitelist exists, only whitelisted countries are allowed
        if (!empty($this->country_whitelist)) {
            return in_array($countryCode, $this->country_whitelist);
        }

        // If no whitelist, check blacklist
        if (!empty($this->country_blacklist)) {
            return !in_array($countryCode, $this->country_blacklist);
        }

        // If neither whitelist nor blacklist, allow all
        return true;
    }
}
