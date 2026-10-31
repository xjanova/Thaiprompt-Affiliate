<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\IpUtils;

class ThreatIp extends Model
{
    use HasFactory;

    /**
     * threshold เริ่มต้น — 85 ผ่านเฉพาะ Firehol Level 1 (90)
     *
     * proxies (80) มี residential/mobile proxy ที่อาจชน IP ลูกค้าไทย
     * webserver (75) ชน crawler ราว 1 ใน 4 ของ page view
     */
    public const DEFAULT_CONFIDENCE_THRESHOLD = 85;

    /**
     * prefix กว้างสุดที่ยอมแมตช์ — วง public ในฟีดจริงกว้างสุดแค่ /12
     *
     * แถวที่กว้างกว่านี้คือ bogon หรือฟีดพัง แถวเดียวต้องไม่กวาด IP ไปทั้งก้อน
     */
    protected const WIDEST_PREFIX = 8;

    /**
     * Carrier-grade NAT — firehol_level1 มีวงนี้ แต่ไม่อยู่ใน IpUtils::PRIVATE_SUBNETS
     */
    protected const CARRIER_GRADE_NAT = '100.64.0.0/10';

    protected $fillable = [
        'ip_address',
        'threat_type',
        'source',
        'confidence_score',
        'details',
        'last_seen',
        'expires_at',
    ];

    protected $casts = [
        'last_seen' => 'datetime',
        'expires_at' => 'datetime',
        'details' => 'array',
    ];

    /**
     * ค่า ip_address ทุกแบบในตารางที่ครอบ IP นี้: ตัว IP เอง และเครือข่าย /8 ถึง /32
     *
     * ฟีดเก็บ CIDR เป็นข้อความรูป canonical (production 169,951 แถวไม่มีแถวผิดรูป)
     * จึงค้นผ่าน index ของ ip_address ได้ตรง ๆ — ตาราง 2.9 ล้านแถวใช้ราว 2 ms ต่อ request
     *
     * IP ภายในคืนค่าว่าง: firehol_level1 มี 10.0.0.0/8, 127.0.0.0/8 ฯลฯ ในฐานะ bogon
     * ถ้าแมตช์ตามตรง request ภายในจะโดนบล็อกที่ confidence 90
     * IPv6 แมตช์ตรงตัวเท่านั้น เพราะฟีดทั้งหมดเป็น IPv4
     *
     * @return array<int, string>
     */
    public static function matchCandidates(string $ip): array
    {
        if (filter_var($ip, FILTER_VALIDATE_IP) === false
            || IpUtils::isPrivateIp($ip)
            || IpUtils::checkIp($ip, self::CARRIER_GRADE_NAT)) {
            return [];
        }

        $long = ip2long($ip);
        if ($long === false) {
            return [$ip];
        }

        $candidates = [$ip];
        for ($prefix = self::WIDEST_PREFIX; $prefix <= 32; $prefix++) {
            $candidates[] = long2ip($long & (-1 << (32 - $prefix)))."/{$prefix}";
        }

        return $candidates;
    }

    /**
     * เช็คว่า IP อยู่ในฐานข้อมูล threat หรือไม่ รวมถึงอยู่ในวง CIDR
     */
    public static function isThreatIp(string $ip, int $minConfidence = 50): bool
    {
        return static::matchingIp($ip)
            ->active()
            ->where('confidence_score', '>=', $minConfidence)
            ->exists();
    }

    /**
     * ดึงแถว threat ที่ confidence สูงสุดของ IP นี้
     *
     * @param  array<int, string>|null  $types  จำกัด threat_type — IP หนึ่งอยู่ได้หลายฟีด
     *                                          ต้องกรองที่นี่ ไม่งั้นแถวประเภทอื่นจะบังแถวที่สนใจ
     */
    public static function getThreatInfo(string $ip, int $minConfidence = 0, ?array $types = null): ?self
    {
        return static::matchingIp($ip)
            ->active()
            ->where('confidence_score', '>=', $minConfidence)
            ->when($types !== null, fn ($query) => $query->whereIn('threat_type', $types))
            ->orderBy('confidence_score', 'desc')
            ->first();
    }

    /**
     * Scope: แถวที่ครอบ IP นี้ ทั้งแบบตรงตัวและแบบ CIDR
     */
    public function scopeMatchingIp($query, string $ip)
    {
        return $query->whereIn('ip_address', static::matchCandidates($ip));
    }

    /**
     * Scope: Active threats only
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope: By threat type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('threat_type', $type);
    }

    /**
     * Scope: By source
     */
    public function scopeBySource($query, string $source)
    {
        return $query->where('source', $source);
    }

    /**
     * Scope: High confidence threats
     */
    public function scopeHighConfidence($query, int $threshold = 70)
    {
        return $query->where('confidence_score', '>=', $threshold);
    }

    /**
     * Get threat type badge color
     */
    public function getThreatTypeBadgeColor(): string
    {
        return match ($this->threat_type) {
            'proxy' => 'orange',
            'vpn' => 'yellow',
            'tor' => 'purple',
            'spam' => 'blue',
            'abuse' => 'red',
            'malware' => 'red',
            'scanner' => 'pink',
            default => 'gray',
        };
    }

    /**
     * Get threat type label
     */
    public function getThreatTypeLabel(): string
    {
        return match ($this->threat_type) {
            'proxy' => '🔀 Proxy',
            'vpn' => '🔒 VPN',
            'tor' => '🧅 Tor',
            'spam' => '📧 Spam',
            'abuse' => '⚠️ Abuse',
            'malware' => '🦠 Malware',
            'scanner' => '🔍 Scanner',
            default => '❓ Other',
        };
    }
}
