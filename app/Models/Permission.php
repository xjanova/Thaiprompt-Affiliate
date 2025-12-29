<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'category',
    ];

    /**
     * Get the roles that have this permission.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions')
            ->withTimestamps();
    }

    /**
     * Get permissions grouped by category.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getGroupedByCategory()
    {
        return static::all()->groupBy('category');
    }

    /**
     * Get all permission categories.
     */
    public static function getCategories(): array
    {
        return static::distinct('category')->pluck('category')->toArray();
    }

    /**
     * Get category display name in Thai.
     *
     * แปลงชื่อ category เป็นภาษาไทยสำหรับแสดงผลใน UI
     */
    public static function getCategoryDisplayName(string $category): string
    {
        return match ($category) {
            // Core System
            'system' => '⚙️ ระบบหลัก',
            'users' => '👥 ผู้ใช้งาน',
            'roles' => '🔐 บทบาทและสิทธิ์',

            // Identity & Support
            'kyc' => '🪪 ยืนยันตัวตน KYC',
            'tickets' => '🎫 Ticket Support',

            // AI Systems
            'ai_core' => '🤖 AI Core',
            'ai_bots' => '🤖 AI Bots',
            'chatbot' => '💬 ระบบ Chatbot',
            'ai_gen' => '✨ AI Generation',

            // Commerce
            'ecommerce' => '🛒 อีคอมเมิร์ซ',
            'pos' => '🏪 ระบบ POS',
            'nfc' => '💳 บัตร NFC',

            // Hospitality & Services
            'hotels' => '🏨 โรงแรม',
            'service_booking' => '📅 จองบริการ',
            'smart_sliders' => '🎨 Smart Sliders',

            // Finance
            'finance' => '💰 การเงิน THB',
            'crypto' => '₿ คริปโต',
            'payment' => '💳 Payment Gateways',

            // MLM & Affiliate
            'affiliate' => '🤝 แอฟฟิลิเอท',
            'mlm' => '📊 MLM System',
            'ranks' => '🏆 ระดับและแรงค์',

            // Communication
            'email' => '📧 อีเมล',
            'line_oa' => '💚 LINE OA',
            'notifications' => '🔔 การแจ้งเตือน',

            // Learning
            'academy' => '🎓 Academy',
            'learning' => '📚 Learning Center',

            // Marketing
            'marketing' => '📈 การตลาด',

            // Organization
            'hrm' => '👔 HR & พนักงาน',
            'accounting' => '📋 บัญชี',

            // Security
            'security' => '🛡️ ความปลอดภัย',

            // Content & Media
            'pages_seo' => '📄 เพจ & SEO',
            'content_media' => '🎬 คอนเทนต์ & มีเดีย',
            'analytics' => '📊 Analytics',

            // Customization
            'themes' => '🎨 ธีม & การแสดงผล',
            'languages' => '🌐 ภาษา & การแปล',

            // Settings
            'settings' => '⚙️ ตั้งค่าระบบ',

            // Default
            'general' => '📋 ทั่วไป',
            default => '📂 '.ucfirst(str_replace('_', ' ', $category)),
        };
    }

    /**
     * Get category icon only (without text).
     *
     * ดึงเฉพาะ icon ของ category
     */
    public static function getCategoryIcon(string $category): string
    {
        return match ($category) {
            'system' => '⚙️',
            'users' => '👥',
            'roles' => '🔐',
            'kyc' => '🪪',
            'tickets' => '🎫',
            'ai_core' => '🤖',
            'ai_bots' => '🤖',
            'chatbot' => '💬',
            'ai_gen' => '✨',
            'ecommerce' => '🛒',
            'pos' => '🏪',
            'nfc' => '💳',
            'hotels' => '🏨',
            'service_booking' => '📅',
            'smart_sliders' => '🎨',
            'finance' => '💰',
            'crypto' => '₿',
            'payment' => '💳',
            'affiliate' => '🤝',
            'mlm' => '📊',
            'ranks' => '🏆',
            'email' => '📧',
            'line_oa' => '💚',
            'notifications' => '🔔',
            'academy' => '🎓',
            'learning' => '📚',
            'marketing' => '📈',
            'hrm' => '👔',
            'accounting' => '📋',
            'security' => '🛡️',
            'pages_seo' => '📄',
            'content_media' => '🎬',
            'analytics' => '📊',
            'themes' => '🎨',
            'languages' => '🌐',
            'settings' => '⚙️',
            'general' => '📋',
            default => '📂',
        };
    }

    /**
     * Get all category definitions with display names and icons.
     *
     * ดึงรายการ categories ทั้งหมดพร้อมชื่อและ icon
     */
    public static function getAllCategoryDefinitions(): array
    {
        return [
            // กลุ่มหลัก - Core
            'system' => ['name' => 'ระบบหลัก', 'icon' => '⚙️', 'group' => 'core'],
            'users' => ['name' => 'ผู้ใช้งาน', 'icon' => '👥', 'group' => 'core'],
            'roles' => ['name' => 'บทบาทและสิทธิ์', 'icon' => '🔐', 'group' => 'core'],

            // กลุ่ม Identity & Support
            'kyc' => ['name' => 'ยืนยันตัวตน KYC', 'icon' => '🪪', 'group' => 'identity'],
            'tickets' => ['name' => 'Ticket Support', 'icon' => '🎫', 'group' => 'identity'],

            // กลุ่ม AI
            'ai_core' => ['name' => 'AI Core', 'icon' => '🤖', 'group' => 'ai'],
            'ai_bots' => ['name' => 'AI Bots', 'icon' => '🤖', 'group' => 'ai'],
            'chatbot' => ['name' => 'ระบบ Chatbot', 'icon' => '💬', 'group' => 'ai'],
            'ai_gen' => ['name' => 'AI Generation', 'icon' => '✨', 'group' => 'ai'],

            // กลุ่ม Commerce
            'ecommerce' => ['name' => 'อีคอมเมิร์ซ', 'icon' => '🛒', 'group' => 'commerce'],
            'pos' => ['name' => 'ระบบ POS', 'icon' => '🏪', 'group' => 'commerce'],
            'nfc' => ['name' => 'บัตร NFC', 'icon' => '💳', 'group' => 'commerce'],

            // กลุ่ม Hospitality
            'hotels' => ['name' => 'โรงแรม', 'icon' => '🏨', 'group' => 'hospitality'],
            'service_booking' => ['name' => 'จองบริการ', 'icon' => '📅', 'group' => 'hospitality'],
            'smart_sliders' => ['name' => 'Smart Sliders', 'icon' => '🎨', 'group' => 'hospitality'],

            // กลุ่ม Finance
            'finance' => ['name' => 'การเงิน THB', 'icon' => '💰', 'group' => 'finance'],
            'crypto' => ['name' => 'คริปโต', 'icon' => '₿', 'group' => 'finance'],
            'payment' => ['name' => 'Payment Gateways', 'icon' => '💳', 'group' => 'finance'],

            // กลุ่ม MLM & Affiliate
            'affiliate' => ['name' => 'แอฟฟิลิเอท', 'icon' => '🤝', 'group' => 'mlm'],
            'mlm' => ['name' => 'MLM System', 'icon' => '📊', 'group' => 'mlm'],
            'ranks' => ['name' => 'ระดับและแรงค์', 'icon' => '🏆', 'group' => 'mlm'],

            // กลุ่ม Communication
            'email' => ['name' => 'อีเมล', 'icon' => '📧', 'group' => 'communication'],
            'line_oa' => ['name' => 'LINE OA', 'icon' => '💚', 'group' => 'communication'],
            'notifications' => ['name' => 'การแจ้งเตือน', 'icon' => '🔔', 'group' => 'communication'],

            // กลุ่ม Learning
            'academy' => ['name' => 'Academy', 'icon' => '🎓', 'group' => 'learning'],
            'learning' => ['name' => 'Learning Center', 'icon' => '📚', 'group' => 'learning'],

            // กลุ่ม Marketing
            'marketing' => ['name' => 'การตลาด', 'icon' => '📈', 'group' => 'marketing'],

            // กลุ่ม Organization
            'hrm' => ['name' => 'HR & พนักงาน', 'icon' => '👔', 'group' => 'organization'],
            'accounting' => ['name' => 'บัญชี', 'icon' => '📋', 'group' => 'organization'],

            // กลุ่ม Security
            'security' => ['name' => 'ความปลอดภัย', 'icon' => '🛡️', 'group' => 'security'],

            // กลุ่ม Content
            'pages_seo' => ['name' => 'เพจ & SEO', 'icon' => '📄', 'group' => 'content'],
            'content_media' => ['name' => 'คอนเทนต์ & มีเดีย', 'icon' => '🎬', 'group' => 'content'],
            'analytics' => ['name' => 'Analytics', 'icon' => '📊', 'group' => 'content'],

            // กลุ่ม Customization
            'themes' => ['name' => 'ธีม & การแสดงผล', 'icon' => '🎨', 'group' => 'customization'],
            'languages' => ['name' => 'ภาษา & การแปล', 'icon' => '🌐', 'group' => 'customization'],

            // กลุ่ม Settings
            'settings' => ['name' => 'ตั้งค่าระบบ', 'icon' => '⚙️', 'group' => 'settings'],
            'general' => ['name' => 'ทั่วไป', 'icon' => '📋', 'group' => 'settings'],
        ];
    }

    /**
     * Get permissions grouped by category group.
     *
     * ดึง permissions แบ่งตามกลุ่มใหญ่
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getGroupedByCategoryGroup()
    {
        $categoryDefinitions = static::getAllCategoryDefinitions();
        $permissions = static::all();

        $grouped = [];

        foreach ($permissions as $permission) {
            $category = $permission->category;
            $group = $categoryDefinitions[$category]['group'] ?? 'other';

            if (! isset($grouped[$group])) {
                $grouped[$group] = [
                    'name' => static::getGroupDisplayName($group),
                    'categories' => [],
                ];
            }

            if (! isset($grouped[$group]['categories'][$category])) {
                $grouped[$group]['categories'][$category] = [
                    'name' => $categoryDefinitions[$category]['name'] ?? $category,
                    'icon' => $categoryDefinitions[$category]['icon'] ?? '📂',
                    'permissions' => [],
                ];
            }

            $grouped[$group]['categories'][$category]['permissions'][] = $permission;
        }

        return collect($grouped);
    }

    /**
     * Get group display name in Thai.
     *
     * แปลงชื่อกลุ่มเป็นภาษาไทย
     */
    public static function getGroupDisplayName(string $group): string
    {
        return match ($group) {
            'core' => '🏢 ระบบหลัก',
            'identity' => '🪪 ตัวตน & Support',
            'ai' => '🤖 ระบบ AI',
            'commerce' => '🛒 E-Commerce & POS',
            'hospitality' => '🏨 โรงแรม & บริการ',
            'finance' => '💰 การเงิน',
            'mlm' => '📊 MLM & Affiliate',
            'communication' => '📧 การสื่อสาร',
            'learning' => '🎓 การเรียนรู้',
            'marketing' => '📈 การตลาด',
            'organization' => '👔 องค์กร',
            'security' => '🛡️ ความปลอดภัย',
            'content' => '📄 คอนเทนต์',
            'customization' => '🎨 การปรับแต่ง',
            'settings' => '⚙️ ตั้งค่า',
            default => '📂 '.ucfirst($group),
        };
    }
}
