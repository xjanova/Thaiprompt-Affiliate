<?php

namespace Database\Seeders;

use App\Models\ForumCategory;
use Illuminate\Database\Seeder;

/**
 * ForumCategorySeeder - สร้างหมวดหมู่ฟอรั่มเริ่มต้น
 *
 * หมวดหมู่ที่เกี่ยวข้องกับแพลตฟอร์ม Thaiprompt-Affiliate ทั้งหมด
 */
class ForumCategorySeeder extends Seeder
{
    /**
     * รันการ seed หมวดหมู่ฟอรั่ม
     */
    public function run(): void
    {
        $this->command->info('🗂️ กำลัง seed หมวดหมู่ฟอรั่ม...');

        // หมวดหมู่หลักที่จะสร้าง
        $categories = [
            // 1. ประกาศและข่าวสาร
            [
                'name' => '📢 ประกาศและข่าวสาร',
                'slug' => 'announcements',
                'description' => 'ประกาศสำคัญ อัปเดตระบบ และข่าวสารจากทีมงาน',
                'icon' => 'fas fa-bullhorn',
                'icon_color' => 'text-red-500',
                'gradient_from' => 'red-500',
                'gradient_to' => 'orange-500',
                'display_order' => 1,
                'is_locked' => true, // เฉพาะ admin โพสต์ได้
            ],

            // 2. แนะนำตัว
            [
                'name' => '👋 แนะนำตัว',
                'slug' => 'introductions',
                'description' => 'แนะนำตัวเอง พูดคุยทำความรู้จักกับสมาชิกใหม่',
                'icon' => 'fas fa-hand-wave',
                'icon_color' => 'text-yellow-500',
                'gradient_from' => 'yellow-400',
                'gradient_to' => 'orange-400',
                'display_order' => 2,
            ],

            // 3. MLM และ Affiliate
            [
                'name' => '💰 MLM และ Affiliate',
                'slug' => 'mlm-affiliate',
                'description' => 'พูดคุยเกี่ยวกับระบบ MLM, การสร้างสายงาน, คอมมิชชั่น และเทคนิคการหาสมาชิก',
                'icon' => 'fas fa-network-wired',
                'icon_color' => 'text-green-500',
                'gradient_from' => 'green-500',
                'gradient_to' => 'emerald-600',
                'display_order' => 3,
                'children' => [
                    [
                        'name' => 'เทคนิคการหาสมาชิก',
                        'slug' => 'mlm-recruiting',
                        'description' => 'แชร์เทคนิคและวิธีการหาสมาชิกใหม่',
                        'icon' => 'fas fa-user-plus',
                    ],
                    [
                        'name' => 'สร้างสายงาน',
                        'slug' => 'mlm-team-building',
                        'description' => 'วิธีสร้างและบริหารสายงาน MLM',
                        'icon' => 'fas fa-sitemap',
                    ],
                    [
                        'name' => 'คอมมิชชั่นและรายได้',
                        'slug' => 'mlm-commission',
                        'description' => 'พูดคุยเรื่องคอมมิชชั่น โบนัส และรายได้',
                        'icon' => 'fas fa-dollar-sign',
                    ],
                ],
            ],

            // 4. E-Commerce และการขาย
            [
                'name' => '🛒 E-Commerce และการขาย',
                'slug' => 'ecommerce',
                'description' => 'พูดคุยเกี่ยวกับการขายสินค้า การตลาดออนไลน์ และ E-Commerce',
                'icon' => 'fas fa-shopping-cart',
                'icon_color' => 'text-blue-500',
                'gradient_from' => 'blue-500',
                'gradient_to' => 'indigo-600',
                'display_order' => 4,
                'children' => [
                    [
                        'name' => 'เทคนิคการขาย',
                        'slug' => 'sales-techniques',
                        'description' => 'แชร์เทคนิคการขายและปิดดีล',
                        'icon' => 'fas fa-handshake',
                    ],
                    [
                        'name' => 'การตลาดออนไลน์',
                        'slug' => 'digital-marketing',
                        'description' => 'กลยุทธ์การตลาดดิจิทัล SEO, Social Media Marketing',
                        'icon' => 'fas fa-bullseye',
                    ],
                    [
                        'name' => 'จัดการร้านค้า',
                        'slug' => 'shop-management',
                        'description' => 'วิธีจัดการร้านค้า สต๊อก และคำสั่งซื้อ',
                        'icon' => 'fas fa-store',
                    ],
                ],
            ],

            // 5. AI และ Bot
            [
                'name' => '🤖 AI และ Bot',
                'slug' => 'ai-bot',
                'description' => 'พูดคุยเกี่ยวกับ AI, Chatbot, LINE Bot และระบบอัตโนมัติ',
                'icon' => 'fas fa-robot',
                'icon_color' => 'text-purple-500',
                'gradient_from' => 'purple-500',
                'gradient_to' => 'pink-500',
                'display_order' => 5,
                'children' => [
                    [
                        'name' => 'LINE Bot',
                        'slug' => 'line-bot',
                        'description' => 'พัฒนาและใช้งาน LINE Bot',
                        'icon' => 'fab fa-line',
                    ],
                    [
                        'name' => 'AI Content & Image',
                        'slug' => 'ai-content',
                        'description' => 'สร้างเนื้อหาและรูปภาพด้วย AI',
                        'icon' => 'fas fa-wand-magic-sparkles',
                    ],
                    [
                        'name' => 'Automation',
                        'slug' => 'automation',
                        'description' => 'ระบบอัตโนมัติและ workflow',
                        'icon' => 'fas fa-gears',
                    ],
                ],
            ],

            // 6. Crypto และ TPIX
            [
                'name' => '🪙 Crypto และ TPIX',
                'slug' => 'crypto-tpix',
                'description' => 'พูดคุยเกี่ยวกับ TPIX Token, Blockchain, NFT และ Cryptocurrency',
                'icon' => 'fas fa-coins',
                'icon_color' => 'text-amber-500',
                'gradient_from' => 'amber-400',
                'gradient_to' => 'yellow-500',
                'display_order' => 6,
                'children' => [
                    [
                        'name' => 'TPIX Token',
                        'slug' => 'tpix-token',
                        'description' => 'เรียนรู้และใช้งาน TPIX Token',
                        'icon' => 'fas fa-circle-dollar-to-slot',
                    ],
                    [
                        'name' => 'Trading',
                        'slug' => 'crypto-trading',
                        'description' => 'การเทรด Crypto และกลยุทธ์',
                        'icon' => 'fas fa-chart-line',
                    ],
                    [
                        'name' => 'NFT',
                        'slug' => 'nft',
                        'description' => 'NFT และ Digital Collectibles',
                        'icon' => 'fas fa-image',
                    ],
                ],
            ],

            // 7. Wallet และการเงิน
            [
                'name' => '💳 Wallet และการเงิน',
                'slug' => 'wallet-finance',
                'description' => 'พูดคุยเกี่ยวกับ Wallet, การชำระเงิน, การถอนเงิน และการเงิน',
                'icon' => 'fas fa-wallet',
                'icon_color' => 'text-teal-500',
                'gradient_from' => 'teal-500',
                'gradient_to' => 'cyan-500',
                'display_order' => 7,
            ],

            // 8. เรียนรู้และพัฒนา
            [
                'name' => '📚 เรียนรู้และพัฒนา',
                'slug' => 'learning',
                'description' => 'แชร์ความรู้ บทเรียน และการพัฒนาตัวเอง',
                'icon' => 'fas fa-graduation-cap',
                'icon_color' => 'text-indigo-500',
                'gradient_from' => 'indigo-500',
                'gradient_to' => 'blue-600',
                'display_order' => 8,
                'children' => [
                    [
                        'name' => 'คอร์สและบทเรียน',
                        'slug' => 'courses',
                        'description' => 'รีวิวและพูดคุยเรื่องคอร์สเรียน',
                        'icon' => 'fas fa-book',
                    ],
                    [
                        'name' => 'ทักษะธุรกิจ',
                        'slug' => 'business-skills',
                        'description' => 'พัฒนาทักษะการทำธุรกิจ',
                        'icon' => 'fas fa-briefcase',
                    ],
                ],
            ],

            // 9. บริการและการจอง
            [
                'name' => '🎯 บริการและการจอง',
                'slug' => 'services-booking',
                'description' => 'พูดคุยเกี่ยวกับระบบจองบริการ, โรงแรม และบริการต่างๆ',
                'icon' => 'fas fa-concierge-bell',
                'icon_color' => 'text-pink-500',
                'gradient_from' => 'pink-500',
                'gradient_to' => 'rose-500',
                'display_order' => 9,
            ],

            // 10. ความช่วยเหลือ
            [
                'name' => '🆘 ความช่วยเหลือ',
                'slug' => 'help-support',
                'description' => 'ถามคำถาม ขอความช่วยเหลือ และแก้ปัญหาการใช้งาน',
                'icon' => 'fas fa-life-ring',
                'icon_color' => 'text-red-500',
                'gradient_from' => 'red-500',
                'gradient_to' => 'pink-500',
                'display_order' => 10,
                'children' => [
                    [
                        'name' => 'ถาม-ตอบ ทั่วไป',
                        'slug' => 'general-qa',
                        'description' => 'คำถามทั่วไปเกี่ยวกับระบบ',
                        'icon' => 'fas fa-circle-question',
                    ],
                    [
                        'name' => 'ปัญหาการใช้งาน',
                        'slug' => 'technical-issues',
                        'description' => 'รายงานปัญหาและขอความช่วยเหลือทางเทคนิค',
                        'icon' => 'fas fa-bug',
                    ],
                    [
                        'name' => 'คำแนะนำและข้อเสนอแนะ',
                        'slug' => 'suggestions',
                        'description' => 'เสนอแนะฟีเจอร์ใหม่และปรับปรุงระบบ',
                        'icon' => 'fas fa-lightbulb',
                    ],
                ],
            ],

            // 11. พูดคุยทั่วไป
            [
                'name' => '☕ พูดคุยทั่วไป',
                'slug' => 'general-discussion',
                'description' => 'พูดคุยเรื่องทั่วไป นอกเหนือจากหมวดหมู่อื่น',
                'icon' => 'fas fa-comments',
                'icon_color' => 'text-gray-500',
                'gradient_from' => 'gray-500',
                'gradient_to' => 'slate-600',
                'display_order' => 11,
            ],

            // 12. Success Stories
            [
                'name' => '🏆 Success Stories',
                'slug' => 'success-stories',
                'description' => 'แชร์ความสำเร็จ รีวิว และเรื่องราวที่สร้างแรงบันดาลใจ',
                'icon' => 'fas fa-trophy',
                'icon_color' => 'text-yellow-500',
                'gradient_from' => 'yellow-400',
                'gradient_to' => 'amber-500',
                'display_order' => 12,
            ],
        ];

        foreach ($categories as $categoryData) {
            $children = $categoryData['children'] ?? [];
            unset($categoryData['children']);

            // สร้างหมวดหมู่หลัก
            $parent = ForumCategory::updateOrCreate(
                ['slug' => $categoryData['slug']],
                $categoryData
            );

            // สร้างหมวดหมู่ย่อย
            foreach ($children as $index => $childData) {
                $childData['parent_id'] = $parent->id;
                $childData['gradient_from'] = $parent->gradient_from;
                $childData['gradient_to'] = $parent->gradient_to;
                $childData['display_order'] = $index + 1;

                ForumCategory::updateOrCreate(
                    ['slug' => $childData['slug']],
                    $childData
                );
            }

            $this->command->info("  ✅ สร้างหมวดหมู่: {$categoryData['name']}");
        }

        $this->command->info('✨ Seed หมวดหมู่ฟอรั่มสำเร็จ!');
    }
}
