<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LearningArticle;
use App\Models\LearningCategory;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Seeder สำหรับบทความ Academy Knowledge
 *
 * สร้างบทความวิชาตามโครงสร้าง Wiki ของระบบ TP-Affiliate
 * ทุกวิชาอ่านฟรีสำหรับสมาชิกที่ login
 */
class LearningArticleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ดึง admin user
        $admin = User::where('role', 'admin')->first() ?? User::first();

        if (!$admin) {
            $this->command->error('❌ ไม่พบผู้ใช้ในระบบ กรุณาสร้างผู้ใช้ก่อน');
            return;
        }

        $this->command->info('📚 กำลังสร้างบทความ Academy Knowledge...');

        $articles = $this->getAllArticles();
        $created = 0;
        $skipped = 0;

        foreach ($articles as $articleData) {
            $category = LearningCategory::where('slug', $articleData['category_slug'])->first();

            if (!$category) {
                $this->command->warn("   ⚠️ ไม่พบหมวดหมู่: {$articleData['category_slug']} - ข้าม");
                continue;
            }

            unset($articleData['category_slug']);

            // เช็คว่ามีอยู่แล้วหรือไม่
            if (LearningArticle::where('slug', $articleData['slug'])->exists()) {
                $skipped++;
                continue;
            }

            LearningArticle::create(array_merge($articleData, [
                'category_id' => $category->id,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
                'published_at' => now(),
            ]));

            $created++;
        }

        $this->command->info("✅ สร้างบทความสำเร็จ: {$created} บทความ");
        if ($skipped > 0) {
            $this->command->info("   ⏭️ ข้าม {$skipped} บทความที่มีอยู่แล้ว");
        }
    }

    /**
     * ดึงบทความทั้งหมดตามโครงสร้าง Wiki
     */
    private function getAllArticles(): array
    {
        return array_merge(
            $this->getOverviewArticles(),
            $this->getGettingStartedArticles(),
            $this->getMlmAffiliateArticles(),
            $this->getAiBotArticles(),
            $this->getEcommerceArticles(),
            $this->getHotelArticles(),
            $this->getCryptoArticles(),
            $this->getWalletArticles(),
            $this->getPaymentArticles(),
            $this->getHrmArticles(),
            $this->getAccountingArticles(),
            $this->getSoftwareArticles(),
            $this->getVendorArticles(),
            $this->getTechnologyArticles(),
            $this->getSecurityArticles(),
            $this->getApiIntegrationArticles(),
            $this->getFaqSupportArticles(),
        );
    }

    /**
     * บทความหมวด: ภาพรวม & สรุปฟีเจอร์
     */
    private function getOverviewArticles(): array
    {
        return [
            [
                'category_slug' => 'overview',
                'title' => 'รู้จัก TP-Affiliate Platform',
                'slug' => 'introduction-tp-affiliate',
                'excerpt' => 'ทำความรู้จักกับแพลตฟอร์ม TP-Affiliate ระบบ All-in-One สำหรับธุรกิจออนไลน์',
                'content' => $this->generatePlaceholderContent('รู้จัก TP-Affiliate Platform', [
                    'TP-Affiliate คืออะไร?',
                    'ฟีเจอร์หลักของระบบ',
                    'ใครควรใช้งาน TP-Affiliate?',
                    'ข้อดีของการใช้แพลตฟอร์มแบบ All-in-One',
                ]),
                'estimated_duration' => 15,
                'difficulty' => 'beginner',
                'is_published' => true,
                'is_featured' => true,
                'tags' => ['ภาพรวม', 'เริ่มต้น', 'แนะนำ'],
                'views' => 0,
                'order' => 1,
            ],
            [
                'category_slug' => 'overview',
                'title' => 'สรุป 20+ ฟีเจอร์หลักของระบบ',
                'slug' => 'all-features-summary',
                'excerpt' => 'ภาพรวมฟีเจอร์ทั้งหมดของ TP-Affiliate ตั้งแต่ MLM, E-Commerce, AI Bot จนถึง Crypto',
                'content' => $this->generatePlaceholderContent('สรุป 20+ ฟีเจอร์หลักของระบบ', [
                    '🔗 MLM & Affiliate System',
                    '🛒 E-Commerce & POS',
                    '🤖 AI & Bot System',
                    '💳 Wallet & Payment',
                    '₿ Crypto & Blockchain',
                    '🏨 Hotel Management',
                    '👥 HRM System',
                    '📊 Accounting',
                    '🔒 Security Features',
                ]),
                'estimated_duration' => 30,
                'difficulty' => 'beginner',
                'is_published' => true,
                'is_featured' => true,
                'tags' => ['ฟีเจอร์', 'สรุป', 'ภาพรวม'],
                'views' => 0,
                'order' => 2,
            ],
            [
                'category_slug' => 'overview',
                'title' => 'แผนภาพระบบและ Architecture',
                'slug' => 'system-architecture',
                'excerpt' => 'เข้าใจโครงสร้างและการทำงานของระบบ TP-Affiliate แบบภาพรวม',
                'content' => $this->generatePlaceholderContent('แผนภาพระบบและ Architecture', [
                    'System Architecture Overview',
                    'Module Dependencies',
                    'Data Flow',
                    'Integration Points',
                ]),
                'estimated_duration' => 20,
                'difficulty' => 'intermediate',
                'is_published' => true,
                'is_featured' => false,
                'tags' => ['Architecture', 'ระบบ', 'โครงสร้าง'],
                'views' => 0,
                'order' => 3,
            ],
        ];
    }

    /**
     * บทความหมวด: เริ่มต้นใช้งาน
     */
    private function getGettingStartedArticles(): array
    {
        return [
            [
                'category_slug' => 'getting-started',
                'title' => 'การสมัครสมาชิกและเข้าสู่ระบบ',
                'slug' => 'registration-and-login',
                'excerpt' => 'วิธีสมัครสมาชิกใหม่และเข้าสู่ระบบ รวมถึงการยืนยันตัวตน',
                'content' => $this->generatePlaceholderContent('การสมัครสมาชิกและเข้าสู่ระบบ', [
                    'สมัครสมาชิกผ่านเว็บไซต์',
                    'สมัครสมาชิกผ่าน LINE',
                    'การยืนยันอีเมลและเบอร์โทร',
                    'การเข้าสู่ระบบ',
                    'การรีเซ็ตรหัสผ่าน',
                ]),
                'estimated_duration' => 10,
                'difficulty' => 'beginner',
                'is_published' => true,
                'is_featured' => true,
                'tags' => ['สมัครสมาชิก', 'เข้าสู่ระบบ', 'เริ่มต้น'],
                'views' => 0,
                'order' => 1,
            ],
            [
                'category_slug' => 'getting-started',
                'title' => 'ตั้งค่าโปรไฟล์และข้อมูลส่วนตัว',
                'slug' => 'profile-setup',
                'excerpt' => 'วิธีตั้งค่าโปรไฟล์ ข้อมูลส่วนตัว และการ KYC',
                'content' => $this->generatePlaceholderContent('ตั้งค่าโปรไฟล์และข้อมูลส่วนตัว', [
                    'แก้ไขข้อมูลโปรไฟล์',
                    'อัปโหลดรูปโปรไฟล์',
                    'การยืนยันตัวตน (KYC)',
                    'ตั้งค่าความปลอดภัย 2FA',
                ]),
                'estimated_duration' => 15,
                'difficulty' => 'beginner',
                'is_published' => true,
                'is_featured' => false,
                'tags' => ['โปรไฟล์', 'ตั้งค่า', 'KYC'],
                'views' => 0,
                'order' => 2,
            ],
            [
                'category_slug' => 'getting-started',
                'title' => 'หน้า Dashboard และเมนูหลัก',
                'slug' => 'dashboard-navigation',
                'excerpt' => 'ทำความรู้จักกับหน้า Dashboard และการนำทางในระบบ',
                'content' => $this->generatePlaceholderContent('หน้า Dashboard และเมนูหลัก', [
                    'ภาพรวมหน้า Dashboard',
                    'เมนูหลักและการนำทาง',
                    'Widget และข้อมูลสรุป',
                    'การปรับแต่งหน้า Dashboard',
                ]),
                'estimated_duration' => 10,
                'difficulty' => 'beginner',
                'is_published' => true,
                'is_featured' => false,
                'tags' => ['Dashboard', 'เมนู', 'นำทาง'],
                'views' => 0,
                'order' => 3,
            ],
        ];
    }

    /**
     * บทความหมวด: MLM & Affiliate
     */
    private function getMlmAffiliateArticles(): array
    {
        return [
            [
                'category_slug' => 'mlm-affiliate',
                'title' => 'รู้จักระบบ MLM & Affiliate',
                'slug' => 'mlm-affiliate-introduction',
                'excerpt' => 'ทำความเข้าใจระบบ Multi-Level Marketing และ Affiliate Marketing ใน TP-Affiliate',
                'content' => $this->generatePlaceholderContent('รู้จักระบบ MLM & Affiliate', [
                    'MLM คืออะไร?',
                    'Affiliate Marketing คืออะไร?',
                    'ความแตกต่างระหว่าง MLM กับ Affiliate',
                    'แผนการตลาดที่รองรับ',
                ]),
                'estimated_duration' => 20,
                'difficulty' => 'beginner',
                'is_published' => true,
                'is_featured' => true,
                'tags' => ['MLM', 'Affiliate', 'เริ่มต้น'],
                'views' => 0,
                'order' => 1,
            ],
            [
                'category_slug' => 'mlm-affiliate',
                'title' => 'ระบบ Binary Plan',
                'slug' => 'binary-plan-system',
                'excerpt' => 'วิธีการทำงานของระบบ Binary Plan และการคำนวณค่าคอมมิชชั่น',
                'content' => $this->generatePlaceholderContent('ระบบ Binary Plan', [
                    'Binary Plan คืออะไร?',
                    'โครงสร้างสายงาน Binary',
                    'การคำนวณค่าคอมมิชชั่น',
                    'กฎการจับคู่ (Matching)',
                    'ตัวอย่างการคำนวณ',
                ]),
                'estimated_duration' => 30,
                'difficulty' => 'intermediate',
                'is_published' => true,
                'is_featured' => false,
                'tags' => ['Binary', 'MLM', 'Commission'],
                'views' => 0,
                'order' => 2,
            ],
            [
                'category_slug' => 'mlm-affiliate',
                'title' => 'ระบบ Unilevel Plan',
                'slug' => 'unilevel-plan-system',
                'excerpt' => 'วิธีการทำงานของระบบ Unilevel Plan และการคำนวณค่าคอมมิชชั่น',
                'content' => $this->generatePlaceholderContent('ระบบ Unilevel Plan', [
                    'Unilevel Plan คืออะไร?',
                    'โครงสร้างสายงาน Unilevel',
                    'จำนวนชั้นที่ได้รับคอมมิชชั่น',
                    'อัตราค่าคอมมิชชั่นแต่ละชั้น',
                ]),
                'estimated_duration' => 25,
                'difficulty' => 'intermediate',
                'is_published' => true,
                'is_featured' => false,
                'tags' => ['Unilevel', 'MLM', 'Commission'],
                'views' => 0,
                'order' => 3,
            ],
            [
                'category_slug' => 'mlm-affiliate',
                'title' => 'ระบบ Rank และ Bonus',
                'slug' => 'rank-and-bonus-system',
                'excerpt' => 'ระบบยศและโบนัสพิเศษสำหรับสมาชิกที่มียอดขายสูง',
                'content' => $this->generatePlaceholderContent('ระบบ Rank และ Bonus', [
                    'ระดับ Rank ทั้งหมด',
                    'เงื่อนไขการเลื่อนขั้น',
                    'โบนัสตำแหน่ง',
                    'สิทธิพิเศษแต่ละ Rank',
                ]),
                'estimated_duration' => 20,
                'difficulty' => 'intermediate',
                'is_published' => true,
                'is_featured' => false,
                'tags' => ['Rank', 'Bonus', 'MLM'],
                'views' => 0,
                'order' => 4,
            ],
            [
                'category_slug' => 'mlm-affiliate',
                'title' => 'การดู Genealogy และสายงาน',
                'slug' => 'genealogy-network-view',
                'excerpt' => 'วิธีดูโครงสร้างสายงานและ Genealogy ของทีม',
                'content' => $this->generatePlaceholderContent('การดู Genealogy และสายงาน', [
                    'หน้า Genealogy',
                    'มุมมอง Tree View',
                    'การค้นหาสมาชิก',
                    'สถิติสายงาน',
                ]),
                'estimated_duration' => 15,
                'difficulty' => 'beginner',
                'is_published' => true,
                'is_featured' => false,
                'tags' => ['Genealogy', 'สายงาน', 'ทีม'],
                'views' => 0,
                'order' => 5,
            ],
        ];
    }

    /**
     * บทความหมวด: AI & Bot System
     */
    private function getAiBotArticles(): array
    {
        return [
            [
                'category_slug' => 'ai-bot',
                'title' => 'รู้จักระบบ AI ใน TP-Affiliate',
                'slug' => 'ai-system-introduction',
                'excerpt' => 'ภาพรวมระบบ AI ทั้งหมดใน TP-Affiliate ตั้งแต่ Chatbot, Image Generation จนถึง LINE Bot',
                'content' => $this->generatePlaceholderContent('รู้จักระบบ AI ใน TP-Affiliate', [
                    'AI Features ทั้งหมด',
                    'AI Chatbot',
                    'AI Image/Video Generation',
                    'LINE AI Bot',
                    'AI Provider ที่รองรับ',
                ]),
                'estimated_duration' => 20,
                'difficulty' => 'beginner',
                'is_published' => true,
                'is_featured' => true,
                'tags' => ['AI', 'Chatbot', 'ภาพรวม'],
                'views' => 0,
                'order' => 1,
            ],
            [
                'category_slug' => 'ai-bot',
                'title' => 'การสร้างและจัดการ AI Chatbot',
                'slug' => 'ai-chatbot-management',
                'excerpt' => 'วิธีสร้าง ตั้งค่า และจัดการ AI Chatbot สำหรับธุรกิจ',
                'content' => $this->generatePlaceholderContent('การสร้างและจัดการ AI Chatbot', [
                    'สร้าง Chatbot ใหม่',
                    'ตั้งค่า Personality',
                    'เพิ่ม Knowledge Base',
                    'ทดสอบและปรับปรุง',
                ]),
                'estimated_duration' => 30,
                'difficulty' => 'intermediate',
                'is_published' => true,
                'is_featured' => false,
                'tags' => ['AI', 'Chatbot', 'สร้าง'],
                'views' => 0,
                'order' => 2,
            ],
            [
                'category_slug' => 'ai-bot',
                'title' => 'AI สร้างรูปภาพและวิดีโอ',
                'slug' => 'ai-image-video-generation',
                'excerpt' => 'วิธีใช้ AI สร้างรูปภาพและวิดีโอสำหรับการตลาดและ Content',
                'content' => $this->generatePlaceholderContent('AI สร้างรูปภาพและวิดีโอ', [
                    'Text-to-Image Generation',
                    'Image-to-Image Editing',
                    'AI Video Generation',
                    'Best Practices และ Tips',
                ]),
                'estimated_duration' => 25,
                'difficulty' => 'intermediate',
                'is_published' => true,
                'is_featured' => false,
                'tags' => ['AI', 'Image', 'Video'],
                'views' => 0,
                'order' => 3,
            ],
            [
                'category_slug' => 'ai-bot',
                'title' => 'LINE AI Bot สำหรับธุรกิจ',
                'slug' => 'line-ai-bot-business',
                'excerpt' => 'วิธีสร้างและใช้งาน LINE AI Bot สำหรับการขาย การสนับสนุน และการตลาด',
                'content' => $this->generatePlaceholderContent('LINE AI Bot สำหรับธุรกิจ', [
                    'เชื่อมต่อ LINE Official Account',
                    'ตั้งค่า AI Bot',
                    'สร้าง Flow การสนทนา',
                    'ระบบรับสมัครสมาชิกผ่าน LINE',
                    'Rich Menu และ Quick Reply',
                ]),
                'estimated_duration' => 35,
                'difficulty' => 'intermediate',
                'is_published' => true,
                'is_featured' => true,
                'tags' => ['LINE', 'AI', 'Bot'],
                'views' => 0,
                'order' => 4,
            ],
        ];
    }

    /**
     * บทความหมวด: E-Commerce & POS
     */
    private function getEcommerceArticles(): array
    {
        return [
            [
                'category_slug' => 'ecommerce',
                'title' => 'เริ่มต้นใช้งานร้านค้าออนไลน์',
                'slug' => 'ecommerce-getting-started',
                'excerpt' => 'คู่มือเริ่มต้นสร้างและจัดการร้านค้าออนไลน์',
                'content' => $this->generatePlaceholderContent('เริ่มต้นใช้งานร้านค้าออนไลน์', [
                    'สร้างร้านค้าใหม่',
                    'ตั้งค่าข้อมูลร้าน',
                    'เพิ่มสินค้า',
                    'ตั้งค่าการชำระเงิน',
                    'ตั้งค่าการจัดส่ง',
                ]),
                'estimated_duration' => 30,
                'difficulty' => 'beginner',
                'is_published' => true,
                'is_featured' => true,
                'tags' => ['E-Commerce', 'ร้านค้า', 'เริ่มต้น'],
                'views' => 0,
                'order' => 1,
            ],
            [
                'category_slug' => 'ecommerce',
                'title' => 'การจัดการสินค้าและสต๊อก',
                'slug' => 'product-inventory-management',
                'excerpt' => 'วิธีจัดการสินค้า หมวดหมู่ ตัวเลือก และสต๊อกสินค้า',
                'content' => $this->generatePlaceholderContent('การจัดการสินค้าและสต๊อก', [
                    'เพิ่ม/แก้ไขสินค้า',
                    'จัดการหมวดหมู่',
                    'ตัวเลือกสินค้า (Variants)',
                    'จัดการสต๊อก',
                    'ราคาและโปรโมชั่น',
                ]),
                'estimated_duration' => 25,
                'difficulty' => 'beginner',
                'is_published' => true,
                'is_featured' => false,
                'tags' => ['สินค้า', 'สต๊อก', 'จัดการ'],
                'views' => 0,
                'order' => 2,
            ],
            [
                'category_slug' => 'ecommerce',
                'title' => 'การจัดการคำสั่งซื้อ',
                'slug' => 'order-management',
                'excerpt' => 'วิธีจัดการคำสั่งซื้อ การยืนยัน การจัดส่ง และการคืนเงิน',
                'content' => $this->generatePlaceholderContent('การจัดการคำสั่งซื้อ', [
                    'รายการคำสั่งซื้อ',
                    'การยืนยันคำสั่งซื้อ',
                    'การจัดส่ง',
                    'การคืนเงิน/ยกเลิก',
                    'รายงานการขาย',
                ]),
                'estimated_duration' => 20,
                'difficulty' => 'beginner',
                'is_published' => true,
                'is_featured' => false,
                'tags' => ['คำสั่งซื้อ', 'จัดส่ง', 'Order'],
                'views' => 0,
                'order' => 3,
            ],
            [
                'category_slug' => 'ecommerce',
                'title' => 'ระบบ Point of Sale (POS)',
                'slug' => 'pos-system-guide',
                'excerpt' => 'วิธีใช้งานระบบ POS สำหรับร้านค้าหน้าร้าน',
                'content' => $this->generatePlaceholderContent('ระบบ Point of Sale (POS)', [
                    'เปิด POS Terminal',
                    'การขายสินค้า',
                    'การรับชำระเงิน',
                    'พิมพ์ใบเสร็จ',
                    'การเปิด/ปิดกะ',
                ]),
                'estimated_duration' => 25,
                'difficulty' => 'beginner',
                'is_published' => true,
                'is_featured' => false,
                'tags' => ['POS', 'ขายหน้าร้าน', 'Terminal'],
                'views' => 0,
                'order' => 4,
            ],
        ];
    }

    /**
     * บทความหมวด: Hotel Management
     */
    private function getHotelArticles(): array
    {
        return [
            [
                'category_slug' => 'hotel',
                'title' => 'ระบบจัดการโรงแรม',
                'slug' => 'hotel-management-overview',
                'excerpt' => 'ภาพรวมระบบจัดการโรงแรมและที่พัก',
                'content' => $this->generatePlaceholderContent('ระบบจัดการโรงแรม', [
                    'ฟีเจอร์หลักของระบบ',
                    'การตั้งค่าโรงแรม',
                    'การจัดการห้องพัก',
                    'ระบบการจอง',
                ]),
                'estimated_duration' => 20,
                'difficulty' => 'beginner',
                'is_published' => true,
                'is_featured' => true,
                'tags' => ['Hotel', 'โรงแรม', 'จอง'],
                'views' => 0,
                'order' => 1,
            ],
            [
                'category_slug' => 'hotel',
                'title' => 'การจัดการห้องพักและประเภทห้อง',
                'slug' => 'room-type-management',
                'excerpt' => 'วิธีตั้งค่าประเภทห้องพัก ราคา และสิ่งอำนวยความสะดวก',
                'content' => $this->generatePlaceholderContent('การจัดการห้องพักและประเภทห้อง', [
                    'สร้างประเภทห้องพัก',
                    'ตั้งค่าราคา',
                    'จัดการสิ่งอำนวยความสะดวก',
                    'รูปภาพห้องพัก',
                ]),
                'estimated_duration' => 25,
                'difficulty' => 'intermediate',
                'is_published' => true,
                'is_featured' => false,
                'tags' => ['ห้องพัก', 'ประเภทห้อง', 'ราคา'],
                'views' => 0,
                'order' => 2,
            ],
        ];
    }

    /**
     * บทความหมวด: Crypto & Blockchain
     */
    private function getCryptoArticles(): array
    {
        return [
            [
                'category_slug' => 'crypto',
                'title' => 'รู้จักระบบ Crypto ใน TP-Affiliate',
                'slug' => 'crypto-system-introduction',
                'excerpt' => 'ภาพรวมระบบ Cryptocurrency และ Blockchain ในแพลตฟอร์ม',
                'content' => $this->generatePlaceholderContent('รู้จักระบบ Crypto ใน TP-Affiliate', [
                    'Cryptocurrency ที่รองรับ',
                    'TPIX Token คืออะไร?',
                    'การใช้งาน Crypto ในระบบ',
                    'ความปลอดภัยของ Crypto',
                ]),
                'estimated_duration' => 25,
                'difficulty' => 'beginner',
                'is_published' => true,
                'is_featured' => true,
                'tags' => ['Crypto', 'Blockchain', 'TPIX'],
                'views' => 0,
                'order' => 1,
            ],
            [
                'category_slug' => 'crypto',
                'title' => 'TPIX Token และ Staking',
                'slug' => 'tpix-token-staking',
                'excerpt' => 'วิธีใช้งาน TPIX Token และระบบ Staking',
                'content' => $this->generatePlaceholderContent('TPIX Token และ Staking', [
                    'TPIX Token คืออะไร?',
                    'วิธีซื้อ/ขาย TPIX',
                    'Staking TPIX',
                    'ผลตอบแทน Staking',
                ]),
                'estimated_duration' => 30,
                'difficulty' => 'intermediate',
                'is_published' => true,
                'is_featured' => false,
                'tags' => ['TPIX', 'Staking', 'Token'],
                'views' => 0,
                'order' => 2,
            ],
        ];
    }

    /**
     * บทความหมวด: Wallet System
     */
    private function getWalletArticles(): array
    {
        return [
            [
                'category_slug' => 'wallet',
                'title' => 'ระบบกระเป๋าเงิน (Wallet)',
                'slug' => 'wallet-system-overview',
                'excerpt' => 'ภาพรวมระบบกระเป๋าเงินและการจัดการยอดเงิน',
                'content' => $this->generatePlaceholderContent('ระบบกระเป๋าเงิน (Wallet)', [
                    'ประเภทกระเป๋าเงิน',
                    'ยอดคงเหลือ',
                    'ประวัติการทำรายการ',
                    'การโอนเงินระหว่างกระเป๋า',
                ]),
                'estimated_duration' => 15,
                'difficulty' => 'beginner',
                'is_published' => true,
                'is_featured' => true,
                'tags' => ['Wallet', 'กระเป๋าเงิน', 'ยอดเงิน'],
                'views' => 0,
                'order' => 1,
            ],
            [
                'category_slug' => 'wallet',
                'title' => 'การเติมเงินและถอนเงิน',
                'slug' => 'deposit-withdrawal-guide',
                'excerpt' => 'วิธีเติมเงินเข้ากระเป๋าและถอนเงินออก',
                'content' => $this->generatePlaceholderContent('การเติมเงินและถอนเงิน', [
                    'วิธีเติมเงิน',
                    'ช่องทางเติมเงิน',
                    'วิธีถอนเงิน',
                    'ค่าธรรมเนียม',
                ]),
                'estimated_duration' => 15,
                'difficulty' => 'beginner',
                'is_published' => true,
                'is_featured' => false,
                'tags' => ['เติมเงิน', 'ถอนเงิน', 'Wallet'],
                'views' => 0,
                'order' => 2,
            ],
        ];
    }

    /**
     * บทความหมวด: ช่องทางชำระเงิน
     */
    private function getPaymentArticles(): array
    {
        return [
            [
                'category_slug' => 'payment',
                'title' => 'ช่องทางชำระเงินที่รองรับ',
                'slug' => 'payment-gateways-overview',
                'excerpt' => 'รายการช่องทางชำระเงินทั้งหมดที่ระบบรองรับ',
                'content' => $this->generatePlaceholderContent('ช่องทางชำระเงินที่รองรับ', [
                    'PromptPay',
                    'บัตรเครดิต/เดบิต',
                    'TrueMoney Wallet',
                    'Internet Banking',
                    'Cryptocurrency',
                ]),
                'estimated_duration' => 15,
                'difficulty' => 'beginner',
                'is_published' => true,
                'is_featured' => true,
                'tags' => ['Payment', 'ชำระเงิน', 'PromptPay'],
                'views' => 0,
                'order' => 1,
            ],
        ];
    }

    /**
     * บทความหมวด: HRM (ระบบบุคคล)
     */
    private function getHrmArticles(): array
    {
        return [
            [
                'category_slug' => 'hrm',
                'title' => 'ระบบจัดการทรัพยากรบุคคล (HRM)',
                'slug' => 'hrm-system-overview',
                'excerpt' => 'ภาพรวมระบบ HRM สำหรับจัดการพนักงานและทรัพยากรบุคคล',
                'content' => $this->generatePlaceholderContent('ระบบจัดการทรัพยากรบุคคล (HRM)', [
                    'ฟีเจอร์หลัก HRM',
                    'การจัดการพนักงาน',
                    'ระบบการลา',
                    'ระบบเงินเดือน',
                    'การฝึกอบรม',
                ]),
                'estimated_duration' => 25,
                'difficulty' => 'intermediate',
                'is_published' => true,
                'is_featured' => true,
                'tags' => ['HRM', 'HR', 'พนักงาน'],
                'views' => 0,
                'order' => 1,
            ],
        ];
    }

    /**
     * บทความหมวด: ระบบบัญชี
     */
    private function getAccountingArticles(): array
    {
        return [
            [
                'category_slug' => 'accounting',
                'title' => 'ระบบบัญชีและการเงิน',
                'slug' => 'accounting-system-overview',
                'excerpt' => 'ภาพรวมระบบบัญชีและการจัดการการเงิน',
                'content' => $this->generatePlaceholderContent('ระบบบัญชีและการเงิน', [
                    'ผังบัญชี (Chart of Accounts)',
                    'การบันทึกรายการ',
                    'รายงานทางการเงิน',
                    'งบดุลและงบกำไรขาดทุน',
                ]),
                'estimated_duration' => 30,
                'difficulty' => 'intermediate',
                'is_published' => true,
                'is_featured' => true,
                'tags' => ['บัญชี', 'การเงิน', 'Accounting'],
                'views' => 0,
                'order' => 1,
            ],
        ];
    }

    /**
     * บทความหมวด: ซอฟต์แวร์และไลเซนส์
     */
    private function getSoftwareArticles(): array
    {
        return [
            [
                'category_slug' => 'software',
                'title' => 'ระบบขายซอฟต์แวร์และไลเซนส์',
                'slug' => 'software-license-system',
                'excerpt' => 'วิธีขายซอฟต์แวร์และจัดการไลเซนส์ในระบบ',
                'content' => $this->generatePlaceholderContent('ระบบขายซอฟต์แวร์และไลเซนส์', [
                    'สร้างผลิตภัณฑ์ซอฟต์แวร์',
                    'การจัดการไลเซนส์',
                    'White-label Solutions',
                    'การต่ออายุและอัปเกรด',
                ]),
                'estimated_duration' => 25,
                'difficulty' => 'intermediate',
                'is_published' => true,
                'is_featured' => true,
                'tags' => ['Software', 'License', 'ซอฟต์แวร์'],
                'views' => 0,
                'order' => 1,
            ],
        ];
    }

    /**
     * บทความหมวด: ระบบผู้ขาย (Vendor)
     */
    private function getVendorArticles(): array
    {
        return [
            [
                'category_slug' => 'vendor',
                'title' => 'ระบบผู้ขายและ Marketplace',
                'slug' => 'vendor-marketplace-system',
                'excerpt' => 'วิธีสมัครเป็นผู้ขายและจัดการร้านค้าใน Marketplace',
                'content' => $this->generatePlaceholderContent('ระบบผู้ขายและ Marketplace', [
                    'สมัครเป็นผู้ขาย',
                    'แพ็กเกจผู้ขาย',
                    'ตั้งค่าร้านค้า',
                    'ค่าคอมมิชชั่นและการจ่ายเงิน',
                ]),
                'estimated_duration' => 25,
                'difficulty' => 'beginner',
                'is_published' => true,
                'is_featured' => true,
                'tags' => ['Vendor', 'Marketplace', 'ผู้ขาย'],
                'views' => 0,
                'order' => 1,
            ],
        ];
    }

    /**
     * บทความหมวด: เทคโนโลยีและสถาปัตยกรรม
     */
    private function getTechnologyArticles(): array
    {
        return [
            [
                'category_slug' => 'technology',
                'title' => 'เทคโนโลยีที่ใช้ในระบบ',
                'slug' => 'technology-stack-overview',
                'excerpt' => 'ภาพรวมเทคโนโลยีและ Stack ที่ใช้พัฒนาระบบ',
                'content' => $this->generatePlaceholderContent('เทคโนโลยีที่ใช้ในระบบ', [
                    'Backend: Laravel 11',
                    'Frontend: Tailwind CSS + Alpine.js',
                    'Database: MySQL',
                    'AI Integration',
                    'Blockchain: TPIX Network',
                ]),
                'estimated_duration' => 20,
                'difficulty' => 'advanced',
                'is_published' => true,
                'is_featured' => false,
                'tags' => ['Technology', 'Laravel', 'Stack'],
                'views' => 0,
                'order' => 1,
            ],
        ];
    }

    /**
     * บทความหมวด: ความปลอดภัย
     */
    private function getSecurityArticles(): array
    {
        return [
            [
                'category_slug' => 'security',
                'title' => 'ความปลอดภัยของระบบ',
                'slug' => 'security-features-overview',
                'excerpt' => 'ภาพรวมระบบรักษาความปลอดภัยและการป้องกันข้อมูล',
                'content' => $this->generatePlaceholderContent('ความปลอดภัยของระบบ', [
                    'การยืนยันตัวตนสองขั้นตอน (2FA)',
                    'การเข้ารหัสข้อมูล',
                    'Session Management',
                    'การป้องกัน XSS, CSRF, SQL Injection',
                ]),
                'estimated_duration' => 20,
                'difficulty' => 'intermediate',
                'is_published' => true,
                'is_featured' => true,
                'tags' => ['Security', 'ความปลอดภัย', '2FA'],
                'views' => 0,
                'order' => 1,
            ],
            [
                'category_slug' => 'security',
                'title' => 'การตั้งค่า 2FA (Two-Factor Authentication)',
                'slug' => 'two-factor-authentication-setup',
                'excerpt' => 'วิธีตั้งค่าการยืนยันตัวตนสองขั้นตอนเพื่อเพิ่มความปลอดภัย',
                'content' => $this->generatePlaceholderContent('การตั้งค่า 2FA', [
                    '2FA คืออะไร?',
                    'ติดตั้ง Authenticator App',
                    'เปิดใช้งาน 2FA',
                    'การใช้ Backup Codes',
                ]),
                'estimated_duration' => 15,
                'difficulty' => 'beginner',
                'is_published' => true,
                'is_featured' => false,
                'tags' => ['2FA', 'Security', 'Authentication'],
                'views' => 0,
                'order' => 2,
            ],
        ];
    }

    /**
     * บทความหมวด: API & Integration
     */
    private function getApiIntegrationArticles(): array
    {
        return [
            [
                'category_slug' => 'api-integration',
                'title' => 'API Documentation',
                'slug' => 'api-documentation-overview',
                'excerpt' => 'ภาพรวม API และวิธีเชื่อมต่อกับระบบ',
                'content' => $this->generatePlaceholderContent('API Documentation', [
                    'Authentication (API Token)',
                    'REST API Endpoints',
                    'Request/Response Format',
                    'Rate Limiting',
                    'Error Handling',
                ]),
                'estimated_duration' => 30,
                'difficulty' => 'advanced',
                'is_published' => true,
                'is_featured' => true,
                'tags' => ['API', 'REST', 'Integration'],
                'views' => 0,
                'order' => 1,
            ],
            [
                'category_slug' => 'api-integration',
                'title' => 'Webhook และ Event Notification',
                'slug' => 'webhook-event-notification',
                'excerpt' => 'วิธีตั้งค่าและใช้งาน Webhook สำหรับรับ Event',
                'content' => $this->generatePlaceholderContent('Webhook และ Event Notification', [
                    'Webhook คืออะไร?',
                    'การตั้งค่า Webhook',
                    'Event Types',
                    'การยืนยัน Signature',
                ]),
                'estimated_duration' => 25,
                'difficulty' => 'advanced',
                'is_published' => true,
                'is_featured' => false,
                'tags' => ['Webhook', 'Event', 'Notification'],
                'views' => 0,
                'order' => 2,
            ],
        ];
    }

    /**
     * บทความหมวด: FAQ & ศูนย์ช่วยเหลือ
     */
    private function getFaqSupportArticles(): array
    {
        return [
            [
                'category_slug' => 'faq-support',
                'title' => 'คำถามที่พบบ่อย (FAQ)',
                'slug' => 'frequently-asked-questions',
                'excerpt' => 'รวมคำถามที่พบบ่อยและคำตอบ',
                'content' => $this->generatePlaceholderContent('คำถามที่พบบ่อย (FAQ)', [
                    'คำถามเกี่ยวกับการสมัครสมาชิก',
                    'คำถามเกี่ยวกับการชำระเงิน',
                    'คำถามเกี่ยวกับ MLM/Affiliate',
                    'คำถามเกี่ยวกับการถอนเงิน',
                    'คำถามทั่วไป',
                ]),
                'estimated_duration' => 15,
                'difficulty' => 'beginner',
                'is_published' => true,
                'is_featured' => true,
                'tags' => ['FAQ', 'คำถาม', 'ช่วยเหลือ'],
                'views' => 0,
                'order' => 1,
            ],
            [
                'category_slug' => 'faq-support',
                'title' => 'วิธีติดต่อทีมสนับสนุน',
                'slug' => 'contact-support-team',
                'excerpt' => 'ช่องทางติดต่อและขอความช่วยเหลือจากทีมสนับสนุน',
                'content' => $this->generatePlaceholderContent('วิธีติดต่อทีมสนับสนุน', [
                    'ส่ง Ticket',
                    'Live Chat',
                    'LINE Official Account',
                    'อีเมล',
                    'เวลาทำการ',
                ]),
                'estimated_duration' => 10,
                'difficulty' => 'beginner',
                'is_published' => true,
                'is_featured' => false,
                'tags' => ['Support', 'ติดต่อ', 'ช่วยเหลือ'],
                'views' => 0,
                'order' => 2,
            ],
            [
                'category_slug' => 'faq-support',
                'title' => 'การแก้ไขปัญหาเบื้องต้น',
                'slug' => 'troubleshooting-guide',
                'excerpt' => 'วิธีแก้ไขปัญหาทั่วไปที่พบบ่อย',
                'content' => $this->generatePlaceholderContent('การแก้ไขปัญหาเบื้องต้น', [
                    'ปัญหาการเข้าสู่ระบบ',
                    'ปัญหาการชำระเงิน',
                    'ปัญหาการถอนเงิน',
                    'ปัญหาอื่นๆ',
                ]),
                'estimated_duration' => 15,
                'difficulty' => 'beginner',
                'is_published' => true,
                'is_featured' => false,
                'tags' => ['Troubleshooting', 'แก้ไขปัญหา', 'ช่วยเหลือ'],
                'views' => 0,
                'order' => 3,
            ],
        ];
    }

    /**
     * สร้างเนื้อหา placeholder สำหรับบทความ
     *
     * @param string $title หัวข้อบทความ
     * @param array $sections หัวข้อย่อย
     * @return string HTML content
     */
    private function generatePlaceholderContent(string $title, array $sections): string
    {
        $html = "<div class=\"prose max-w-none dark:prose-invert\">\n";
        $html .= "<h2>{$title}</h2>\n";
        $html .= "<p class=\"text-lg text-gray-600 dark:text-gray-400\">เนื้อหาของบทความนี้กำลังอยู่ระหว่างการจัดทำ กรุณาติดตามการอัปเดต</p>\n\n";

        $html .= "<div class=\"bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-lg p-4 my-4\">\n";
        $html .= "<h3 class=\"text-blue-800 dark:text-blue-200 mt-0\">📋 หัวข้อในบทความนี้</h3>\n";
        $html .= "<ul class=\"mb-0\">\n";

        foreach ($sections as $section) {
            $html .= "<li>{$section}</li>\n";
        }

        $html .= "</ul>\n";
        $html .= "</div>\n\n";

        $html .= "<div class=\"bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 my-4\">\n";
        $html .= "<p class=\"mb-0\">💡 <strong>หมายเหตุ:</strong> บทความนี้เป็นส่วนหนึ่งของ Academy Knowledge ที่สามารถอ่านได้ฟรีสำหรับสมาชิกทุกท่าน</p>\n";
        $html .= "</div>\n";

        $html .= "</div>";

        return $html;
    }
}
