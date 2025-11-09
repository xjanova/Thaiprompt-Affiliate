<?php

namespace Database\Seeders;

use App\Models\SeoMeta;
use Illuminate\Database\Seeder;

class SeoMetaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if SEO meta data already exists
        $existingSeoCount = SeoMeta::whereIn('page_type', [
            'home',
            'about',
            'contact'
        ])->count();

        if ($existingSeoCount > 0) {
            $this->updateMode();
        } else {
            $this->freshInstallMode();
        }
    }

    /**
     * Fresh install mode - seed all SEO data
     */
    private function freshInstallMode(): void
    {
        $this->command->info('🌱 Fresh install: Seeding all SEO meta data...');

        $seoData = $this->getAllSeoData();

        foreach ($seoData as $data) {
            SeoMeta::create($data);
        }

        $this->command->info('✅ SEO meta data seeded successfully: ' . count($seoData) . ' entries');
    }

    /**
     * Update mode - add only missing SEO data
     */
    private function updateMode(): void
    {
        $this->command->warn('⚠️  Existing SEO meta data detected!');
        $this->command->info('   Running in UPDATE mode (adding missing entries only)...');

        $seoData = $this->getAllSeoData();
        $added = 0;
        $skipped = 0;

        foreach ($seoData as $data) {
            $exists = SeoMeta::where('page_type', $data['page_type'])
                ->where('language', $data['language'])
                ->exists();

            if (!$exists) {
                SeoMeta::create($data);
                $this->command->info("   ➕ Added: {$data['page_type']} ({$data['language']})");
                $added++;
            } else {
                $skipped++;
            }
        }

        if ($added > 0) {
            $this->command->info("✅ Added {$added} new SEO entries.");
        }

        if ($skipped > 0) {
            $this->command->info("   ⏭️  Skipped {$skipped} existing entries (preserved).");
        }
    }

    /**
     * Get all default SEO meta data
     */
    private function getAllSeoData(): array
    {
        return [
            // Homepage - Thai
            [
                'page_type' => 'home',
                'language' => 'th',
                'meta_title' => 'ระบบ Affiliate Marketing MLM - สร้างรายได้แบบพาสซีฟจากการแนะนำ',
                'meta_description' => 'แพลตฟอร์ม Affiliate Marketing และ MLM ที่ดีที่สุดในไทย สร้างรายได้แบบพาสซีฟ รับค่าคอมมิชชั่นหลายระดับ ระบบติดตามผลชัดเจน จ่ายคอมมิชชั่นทันที เริ่มต้นง่ายๆ วันนี้',
                'meta_keywords' => 'affiliate marketing, MLM, network marketing, passive income, รายได้พาสซีฟ, คอมมิชชั่น, การตลาดแบบบอกต่อ, ธุรกิจออนไลน์, สร้างรายได้เสริม',
                'og_title' => 'ระบบ Affiliate Marketing MLM - สร้างรายได้แบบพาสซีฟจากการแนะนำ',
                'og_description' => 'เข้าร่วมระบบ Affiliate Marketing MLM ที่ดีที่สุด สร้างรายได้แบบพาสซีฟ รับค่าคอมมิชชั่นหลายระดับ',
                'og_type' => 'website',
                'twitter_card' => 'summary_large_image',
                'twitter_title' => 'ระบบ Affiliate Marketing MLM - สร้างรายได้แบบพาสซีฟ',
                'twitter_description' => 'สร้างรายได้แบบพาสซีฟด้วยระบบ Affiliate Marketing MLM ที่ทันสมัย',
                'index' => true,
                'follow' => true,
            ],

            // Homepage - English
            [
                'page_type' => 'home',
                'language' => 'en',
                'meta_title' => 'Affiliate Marketing MLM System - Build Passive Income from Referrals',
                'meta_description' => 'Best Affiliate Marketing and MLM platform in Thailand. Build passive income, earn multi-level commissions, transparent tracking system, instant commission payout. Start easily today',
                'meta_keywords' => 'affiliate marketing, MLM, network marketing, passive income, commission, referral marketing, online business, side income',
                'og_title' => 'Affiliate Marketing MLM System - Build Passive Income from Referrals',
                'og_description' => 'Join the best Affiliate Marketing MLM system. Build passive income, earn multi-level commissions',
                'og_type' => 'website',
                'twitter_card' => 'summary_large_image',
                'twitter_title' => 'Affiliate Marketing MLM System - Build Passive Income',
                'twitter_description' => 'Build passive income with modern Affiliate Marketing MLM system',
                'index' => true,
                'follow' => true,
            ],

            // About - Thai
            [
                'page_type' => 'about',
                'language' => 'th',
                'meta_title' => 'เกี่ยวกับเรา - ระบบ Affiliate Marketing MLM อันดับ 1',
                'meta_description' => 'เรียนรู้เกี่ยวกับระบบ Affiliate Marketing MLM ของเรา พันธกิจในการสร้างโอกาสรายได้ให้กับคนไทย ด้วยระบบที่โปร่งใส ยุติธรรม และให้ผลตอบแทนที่ดีที่สุด',
                'meta_keywords' => 'เกี่ยวกับเรา, ระบบ MLM, affiliate marketing ไทย, โอกาสธุรกิจ',
                'og_title' => 'เกี่ยวกับเรา - ระบบ Affiliate Marketing MLM',
                'og_description' => 'ระบบ Affiliate Marketing MLM ที่โปร่งใส ยุติธรรม และให้ผลตอบแทนที่ดีที่สุด',
                'og_type' => 'website',
                'index' => true,
                'follow' => true,
            ],

            // Contact - Thai
            [
                'page_type' => 'contact',
                'language' => 'th',
                'meta_title' => 'ติดต่อเรา - สอบถามข้อมูลระบบ Affiliate MLM',
                'meta_description' => 'ติดต่อเราเพื่อสอบถามข้อมูลเพิ่มเติมเกี่ยวกับระบบ Affiliate Marketing MLM สอบถามเรื่องการสมัครสมาชิก คอมมิชชั่น หรือปัญหาการใช้งาน ทีมงานพร้อมช่วยเหลือตลอด 24/7',
                'meta_keywords' => 'ติดต่อเรา, สอบถามข้อมูล, support, MLM contact',
                'og_title' => 'ติดต่อเรา - สอบถามข้อมูลระบบ Affiliate MLM',
                'og_description' => 'ติดต่อเราเพื่อสอบถามข้อมูลเกี่ยวกับระบบ Affiliate Marketing MLM',
                'og_type' => 'website',
                'index' => true,
                'follow' => true,
            ],

            // Register - Thai
            [
                'page_type' => 'register',
                'language' => 'th',
                'meta_title' => 'สมัครสมาชิก - เริ่มต้นสร้างรายได้แบบพาสซีฟวันนี้',
                'meta_description' => 'สมัครสมาชิก Affiliate Marketing MLM ฟรี! เริ่มต้นสร้างรายได้แบบพาสซีฟ รับค่าคอมมิชชั่นทันทีที่มีคนสมัครผ่านคุณ ไม่มีค่าธรรมเนียม ไม่มีค่าใช้จ่ายแอบแฝง',
                'meta_keywords' => 'สมัครสมาชิก, register affiliate, MLM signup, เริ่มต้นธุรกิจ MLM',
                'og_title' => 'สมัครสมาชิก - เริ่มต้นสร้างรายได้แบบพาสซีฟ',
                'og_description' => 'สมัครสมาชิกฟรี! เริ่มต้นสร้างรายได้แบบพาสซีฟกับระบบ Affiliate Marketing MLM',
                'og_type' => 'website',
                'index' => true,
                'follow' => true,
            ],

            // Dashboard - Thai
            [
                'page_type' => 'dashboard',
                'language' => 'th',
                'meta_title' => 'แดชบอร์ด - จัดการระบบ Affiliate ของคุณ',
                'meta_description' => 'แดชบอร์ดสำหรับจัดการระบบ Affiliate Marketing ของคุณ ดูรายได้ ติดตามคอมมิชชั่น จัดการลูกข่าย',
                'meta_keywords' => 'dashboard, affiliate dashboard, MLM dashboard',
                'og_title' => 'แดชบอร์ด - จัดการระบบ Affiliate',
                'og_description' => 'จัดการระบบ Affiliate Marketing และดูรายได้ของคุณ',
                'og_type' => 'website',
                'index' => false,
                'follow' => false,
            ],

            // Affiliates - Thai
            [
                'page_type' => 'affiliates',
                'language' => 'th',
                'meta_title' => 'สมาชิกในเครือข่าย - ระบบ Affiliate MLM',
                'meta_description' => 'ดูและจัดการสมาชิกในเครือข่าย Affiliate ของคุณ ติดตามการเติบโตของทีม วิเคราะห์ประสิทธิภาพ และเพิ่มรายได้ของคุณ',
                'meta_keywords' => 'affiliate network, MLM downline, network team, ลูกข่าย',
                'og_title' => 'สมาชิกในเครือข่าย - ระบบ Affiliate MLM',
                'og_description' => 'จัดการและติดตามสมาชิกในเครือข่าย Affiliate ของคุณ',
                'og_type' => 'website',
                'index' => false,
                'follow' => false,
            ],

            // Commissions - Thai
            [
                'page_type' => 'commissions',
                'language' => 'th',
                'meta_title' => 'ค่าคอมมิชชั่น - ติดตามรายได้ของคุณ',
                'meta_description' => 'ติดตามค่าคอมมิชชั่นและรายได้จากระบบ Affiliate Marketing MLM ดูประวัติการจ่ายเงิน รายได้แต่ละระดับ และสรุปยอดรวม',
                'meta_keywords' => 'commission, รายได้, passive income, MLM earnings',
                'og_title' => 'ค่าคอมมิชชั่น - ติดตามรายได้',
                'og_description' => 'ติดตามค่าคอมมิชชั่นและรายได้จากระบบ Affiliate Marketing',
                'og_type' => 'website',
                'index' => false,
                'follow' => false,
            ],
        ];
    }
}
