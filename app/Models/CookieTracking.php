<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CookieTracking extends Model
{
    use HasFactory;

    protected $table = 'cookie_tracking';

    protected $fillable = [
        'session_id',
        'user_id',
        'ip_address',
        'country',
        'city',
        'referrer_url',
        'referrer_domain',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'landing_page',
        'current_page',
        'page_views',
        'session_duration',
        'device_type',
        'browser',
        'browser_version',
        'os',
        'os_version',
        'viewed_products',
        'searched_keywords',
        'interests_detected',
        'behavior_tags',
        'first_visit_at',
        'last_activity_at',
    ];

    protected $casts = [
        'viewed_products' => 'array',
        'searched_keywords' => 'array',
        'interests_detected' => 'array',
        'behavior_tags' => 'array',
        'first_visit_at' => 'datetime',
        'last_activity_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ตรวจจับความสนใจจาก keywords และ products
     */
    public function detectInterests(): array
    {
        $interests = [];
        $keywords = $this->searched_keywords ?? [];
        $products = $this->viewed_products ?? [];

        // Interest categories
        $categories = [
            'shopping' => ['ซื้อ', 'สั่ง', 'ช้อป', 'ราคา', 'ลด', 'โปร', 'buy', 'shop', 'price', 'discount'],
            'technology' => ['tech', 'ai', 'software', 'app', 'digital', 'เทคโนโลยี', 'ซอฟต์แวร์'],
            'education' => ['เรียน', 'คอร์ส', 'วิชา', 'learn', 'course', 'tutorial', 'education'],
            'business' => ['ธุรกิจ', 'งาน', 'การตลาด', 'business', 'marketing', 'work'],
            'lifestyle' => ['ท่องเที่ยว', 'อาหาร', 'แฟชั่น', 'travel', 'food', 'fashion', 'lifestyle'],
        ];

        foreach ($categories as $category => $patterns) {
            foreach ($patterns as $pattern) {
                foreach ($keywords as $keyword) {
                    if (stripos($keyword, $pattern) !== false) {
                        $interests[] = $category;
                        break 2;
                    }
                }
            }
        }

        return array_unique($interests);
    }

    /**
     * วิเคราะห์พฤติกรรม
     */
    public function analyzeBehavior(): array
    {
        $behaviors = [];

        if ($this->page_views > 10) {
            $behaviors[] = 'active_browser';
        }

        if (count($this->viewed_products ?? []) > 5) {
            $behaviors[] = 'shopping_intent';
        }

        if (count($this->searched_keywords ?? []) > 3) {
            $behaviors[] = 'researcher';
        }

        if ($this->session_duration > 300) { // 5+ minutes
            $behaviors[] = 'engaged_visitor';
        }

        return $behaviors;
    }
}
