<?php

namespace App\Services;

class UserAgentParser
{
    /**
     * Parse user agent string
     */
    public static function parse(?string $userAgent): array
    {
        if (!$userAgent) {
            return [
                'os' => null,
                'os_version' => null,
                'browser' => null,
                'browser_version' => null,
                'device_type' => 'unknown',
            ];
        }

        return [
            'os' => self::detectOS($userAgent),
            'os_version' => self::detectOSVersion($userAgent),
            'browser' => self::detectBrowser($userAgent),
            'browser_version' => self::detectBrowserVersion($userAgent),
            'device_type' => self::detectDeviceType($userAgent),
        ];
    }

    /**
     * Detect Operating System
     */
    protected static function detectOS(string $userAgent): ?string
    {
        $os_array = [
            '/windows nt 10/i'      => 'Windows 10',
            '/windows nt 11/i'      => 'Windows 11',
            '/windows nt 6.3/i'     => 'Windows 8.1',
            '/windows nt 6.2/i'     => 'Windows 8',
            '/windows nt 6.1/i'     => 'Windows 7',
            '/windows nt 6.0/i'     => 'Windows Vista',
            '/windows nt 5.2/i'     => 'Windows Server 2003/XP x64',
            '/windows nt 5.1/i'     => 'Windows XP',
            '/windows xp/i'         => 'Windows XP',
            '/windows nt 5.0/i'     => 'Windows 2000',
            '/windows me/i'         => 'Windows ME',
            '/win98/i'              => 'Windows 98',
            '/win95/i'              => 'Windows 95',
            '/win16/i'              => 'Windows 3.11',
            '/macintosh|mac os x/i' => 'macOS',
            '/mac_powerpc/i'        => 'Mac OS 9',
            '/linux/i'              => 'Linux',
            '/ubuntu/i'             => 'Ubuntu',
            '/iphone/i'             => 'iOS',
            '/ipod/i'               => 'iOS',
            '/ipad/i'               => 'iOS',
            '/android/i'            => 'Android',
            '/blackberry/i'         => 'BlackBerry',
            '/webos/i'              => 'WebOS',
        ];

        foreach ($os_array as $regex => $value) {
            if (preg_match($regex, $userAgent)) {
                return $value;
            }
        }

        return 'Unknown';
    }

    /**
     * Detect OS Version
     */
    protected static function detectOSVersion(string $userAgent): ?string
    {
        // Windows version
        if (preg_match('/windows nt ([\d.]+)/i', $userAgent, $matches)) {
            return $matches[1];
        }

        // macOS version
        if (preg_match('/mac os x ([\d_]+)/i', $userAgent, $matches)) {
            return str_replace('_', '.', $matches[1]);
        }

        // Android version
        if (preg_match('/android ([\d.]+)/i', $userAgent, $matches)) {
            return $matches[1];
        }

        // iOS version
        if (preg_match('/os ([\d_]+)/i', $userAgent, $matches)) {
            return str_replace('_', '.', $matches[1]);
        }

        return null;
    }

    /**
     * Detect Browser
     */
    protected static function detectBrowser(string $userAgent): ?string
    {
        // Check for bots first
        if (preg_match('/(bot|crawler|spider|crawling)/i', $userAgent)) {
            return 'Bot';
        }

        $browser_array = [
            '/edg/i'        => 'Edge',
            '/edge/i'       => 'Edge',
            '/opr/i'        => 'Opera',
            '/opera/i'      => 'Opera',
            '/chrome/i'     => 'Chrome',
            '/safari/i'     => 'Safari',
            '/firefox/i'    => 'Firefox',
            '/msie/i'       => 'Internet Explorer',
            '/trident/i'    => 'Internet Explorer',
            '/brave/i'      => 'Brave',
            '/vivaldi/i'    => 'Vivaldi',
        ];

        foreach ($browser_array as $regex => $value) {
            if (preg_match($regex, $userAgent)) {
                return $value;
            }
        }

        return 'Unknown';
    }

    /**
     * Detect Browser Version
     */
    protected static function detectBrowserVersion(string $userAgent): ?string
    {
        // Edge
        if (preg_match('/edg\/([\d.]+)/i', $userAgent, $matches)) {
            return $matches[1];
        }

        // Chrome
        if (preg_match('/chrome\/([\d.]+)/i', $userAgent, $matches)) {
            return $matches[1];
        }

        // Firefox
        if (preg_match('/firefox\/([\d.]+)/i', $userAgent, $matches)) {
            return $matches[1];
        }

        // Safari
        if (preg_match('/version\/([\d.]+)/i', $userAgent, $matches)) {
            return $matches[1];
        }

        // Opera
        if (preg_match('/opr\/([\d.]+)/i', $userAgent, $matches)) {
            return $matches[1];
        }

        // IE
        if (preg_match('/msie ([\d.]+)/i', $userAgent, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Detect Device Type
     */
    protected static function detectDeviceType(string $userAgent): string
    {
        // Check for bot
        if (preg_match('/(bot|crawler|spider|crawling)/i', $userAgent)) {
            return 'bot';
        }

        // Check for mobile
        if (preg_match('/(mobile|android|iphone|ipod|blackberry|opera mini|iemobile)/i', $userAgent)) {
            return 'mobile';
        }

        // Check for tablet
        if (preg_match('/(tablet|ipad|playbook|silk)/i', $userAgent)) {
            return 'tablet';
        }

        // Default to desktop
        return 'desktop';
    }

    /**
     * Get device icon
     */
    public static function getDeviceIcon(string $deviceType): string
    {
        return match($deviceType) {
            'mobile' => '📱',
            'tablet' => '📱',
            'desktop' => '💻',
            'bot' => '🤖',
            default => '❓',
        };
    }

    /**
     * Get OS icon
     */
    public static function getOsIcon(?string $os): string
    {
        if (!$os) return '❓';

        if (str_contains($os, 'Windows')) return '🪟';
        if (str_contains($os, 'macOS') || str_contains($os, 'Mac')) return '🍎';
        if (str_contains($os, 'Linux')) return '🐧';
        if (str_contains($os, 'Android')) return '🤖';
        if (str_contains($os, 'iOS')) return '📱';

        return '❓';
    }

    /**
     * Get browser icon
     */
    public static function getBrowserIcon(?string $browser): string
    {
        if (!$browser) return '❓';

        return match($browser) {
            'Chrome' => '🌐',
            'Firefox' => '🦊',
            'Safari' => '🧭',
            'Edge' => '🌊',
            'Opera' => '🎭',
            'Brave' => '🦁',
            'Bot' => '🤖',
            default => '🌐',
        };
    }
}
