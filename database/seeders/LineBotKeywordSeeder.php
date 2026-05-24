<?php

namespace Database\Seeders;

use App\Models\LineBotKeyword;
use Illuminate\Database\Seeder;

class LineBotKeywordSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * สร้าง Custom Keywords สำหรับ Hybrid Bot
     *
     * ตัวอย่าง Keywords:
     * - FAQ: คำถามที่ถามบ่อย
     * - Support: ปัญหาการใช้งาน
     * - Product: คำถามเกี่ยวกับสินค้า
     * - Custom: คำตอบที่กำหนดเอง
     */
    public function run(): void
    {
        // ตรวจสอบว่าเมื่อเร็กเล่น หรือครั้งแรก
        $existingCount = LineBotKeyword::count();

        if ($existingCount > 0) {
            $this->command->warn('⚠️  LINE Bot Keywords already exist!');
            $this->command->info('   Skipping to preserve custom keywords.');

            return;
        }

        $this->command->info('🔑 สร้าง Custom Keywords สำหรับ Hybrid Bot...');

        // Keyword 1: FAQ - Refund
        //
        // 🚫 (2026-05-16) ปิด keyword นี้ + ลบ "ยกเลิก" จาก trigger_words
        //    Reason: เราไม่มีนโยบายคืนเงิน (บริการดิจิทัล ส่งคำทำนายทันที)
        //            + "ยกเลิก" ตรงกับปุ่ม cancel ของ Fortune Celtic flow → กล่อง refund โผล่ผิด
        //    ถ้าจะ enable ใหม่ ต้องเขียน response_text ให้ตรงกับ T&C จริง (terms-of-service.html:248-250)
        LineBotKeyword::create([
            'keyword' => 'refund',
            'description' => 'คำตอบเกี่ยวกับการคืนเงิน (ปิดไว้ — ไม่มีนโยบายคืนเงิน)',
            'trigger_words' => ['refund', 'คืนเงิน', 'return', 'เงินคืน'],
            'response_type' => 'text',
            'response_text' => <<<'RESPONSE'
💰 **นโยบายการคืนเงิน**

บริการของเราเป็น *บริการดิจิทัล* ส่งคำทำนายทันทีหลังชำระ
ทางร้านขอ *งดคืนเงิน* ทุกกรณีค่ะ กรุณาตรวจสอบก่อนชำระ 🙏

📞 หากมีข้อสงสัย กรุณาติดต่อแอดมินค่ะ
RESPONSE,
            'category' => 'faq',
            'priority' => 80,
            'is_active' => false, // 🚫 (2026-05-16) ปิดไว้ — user ขอ
        ]);

        // Keyword 2: FAQ - Shipping
        // 🚫 (2026-05-25) ปิดไว้ — leak เข้า Fortune Bot (category=faq match กับ fortune)
        //   เคสจริง: ลูกค้าเล่าเรื่องมี "ส่งของออกต่างประเทศ" → trigger shipping template
        //   user feedback: "ข้อมูลการส่งมาจากไหน อย่ามีสิ"
        //   FortuneConversationService::checkDatabaseKeywords scan category in (fortune, faq)
        //   → faq leaks ecommerce templates → wrong response
        LineBotKeyword::create([
            'keyword' => 'shipping',
            'description' => 'คำถามเกี่ยวกับการจัดส่ง (DISABLED — leak เข้า Fortune Bot)',
            'trigger_words' => ['shipping', 'จัดส่ง', 'delivery', 'ส่งของ', 'ค่าส่ง', 'ขนส่ง'],
            'response_type' => 'text',
            'response_text' => <<<'RESPONSE'
📦 **ข้อมูลการจัดส่ง**

✈️ **ระเยะเวลา:**
• แบบด่วน: 1-2 วันทำการ
• แบบปกติ: 3-5 วันทำการ

🏪 **พื้นที่ให้บริการ:**
• กรุงเทพฯ และใกล้เคียง: ฟรี!
• ต่างจังหวัด: 50-200 บาท (แล้วแต่จังหวัด)

📍 **ค่าใช้จ่าย:**
• ฟรีจัดส่งเมื่อซื้อเกิน 500 บาท
• ใช้บริการ Kerry Express & Flash Express

🔍 **ติดตามการจัดส่ง:**
ส่งลิงก์ tracking หลังจากจัดส่ง

📞 ติดต่อ support@example.com เพื่อข้อมูลเพิ่มเติม
RESPONSE,
            'category' => 'faq',
            'priority' => 75,
            'is_active' => false, // 🚫 ปิดเพื่อกัน leak เข้า Fortune Bot
        ]);

        // Keyword 3: Support - Payment Issue
        LineBotKeyword::create([
            'keyword' => 'payment_issue',
            'description' => 'ปัญหาการชำระเงิน',
            'trigger_words' => ['payment error', 'ชำระเงินไม่ได้', 'error', 'ปัญหา', 'payment fail', 'ล้มเหลว'],
            'response_type' => 'text',
            'response_text' => <<<'RESPONSE'
❌ **ปัญหาการชำระเงิน**

ขอโทษที่ประสบปัญหา! มาแก้ไขกัน 🛠️

🔧 **วิธีแก้ไข:**

1️⃣ **ตรวจสอบ:**
   • เชื่อมต่ออินเทอร์เน็ตได้ไหม?
   • ยอดเงินในบัญชีพอไหม?
   • บัตรหมดอายุหรือไม่?

2️⃣ **ลองใหม่:**
   • รีเฟรชหน้า
   • ลบ cache / cookies
   • ใช้เบราว์เซอร์อื่น

3️⃣ **เปลี่ยนวิธีชำระ:**
   • ลองใช้บัตรอื่น
   • ใช้ QR Code / PromptPay
   • โอนเงินเองให้เราก่อน

💬 **ยังไม่ได้ไหม?**
→ ติดต่อ support@example.com พร้อมส่งหน้าจอ error

🚀 พร้อม handle! ขอบคุณที่รอ 🙏
RESPONSE,
            'category' => 'support',
            'priority' => 85,
            'is_active' => true,
        ]);

        // Keyword 4: Product - Affiliate Package
        LineBotKeyword::create([
            'keyword' => 'affiliate_package',
            'description' => 'แพคเกจสมาชิก Affiliate',
            'trigger_words' => ['affiliate', 'package', 'แพค', 'สมาชิก', 'membership', 'package nào'],
            'response_type' => 'text',
            'response_text' => <<<'RESPONSE'
🎁 **แพคเกจสมาชิก Affiliate**

เรามีแพคเกจให้เลือก 3 แบบ 👇

💰 **Bronze (ฟรี)**
• สมัครฟรี ไม่มีค่าใช้จ่าย
• คอมมิชชั่น 5%
• เข้าโปรแกรม Affiliate พื้นฐาน

🌟 **Silver (99 บาท/เดือน)**
• คอมมิชชั่น 10%
• Priority support
• Marketing materials ฟรี
• Commission bonus +2%

👑 **Gold (299 บาท/เดือน)**
• คอมมิชชั่น 15%
• VIP support 24/7
• Dedicated account manager
• Training materials
• Monthly bonus pool

📊 **ได้รายได้ได้เลย!**
• ไม่มีการจ่ายล่วงหน้า
• รับเงินเลย เมื่อมีการสั่งซื้อ
• Transparent dashboard

🚀 สมัครเลย! ไม่ยุ่งอะไร → www.example.com/signup

💬 มีคำถามไหม?
RESPONSE,
            'category' => 'product',
            'priority' => 70,
            'is_active' => true,
        ]);

        // Keyword 5: Support - Account
        LineBotKeyword::create([
            'keyword' => 'account',
            'description' => 'ปัญหาเกี่ยวกับบัญชี',
            'trigger_words' => ['account', 'password', 'login', 'บัญชี', 'รหัสผ่าน', 'เข้าระบบ', 'forget password'],
            'response_type' => 'text',
            'response_text' => <<<'RESPONSE'
🔐 **ปัญหาบัญชี**

มีปัญหาเข้าระบบไหม? มาแก้ไขกัน 👇

❓ **ลืมรหัสผ่าน?**

1. ไปที่หน้า Login
2. คลิก "Forgot Password"
3. ใส่อีเมลที่ลงทะเบียน
4. ตรวจสอบอีเมล (อาจใน Spam)
5. คลิกลิงก์แล้วตั้งรหัสผ่านใหม่

✅ **เข้าไม่ได้ยังไง?**

• ตรวจสอบ email ถูกไหม
• ตรวจสอบ Caps Lock
• ลองใช้อีเมลแทนชื่อผู้ใช้
• Clear cache / cookies

🆘 **ยังไม่ได้ต่อไป:**

ติดต่อเราพร้อมข้อมูล:
- Email ที่ลงทะเบียน
- Username
- Phone number

📧 support@example.com
💬 Line: @affiliatechannel

เราช่วยได้ 24/7 🚀
RESPONSE,
            'category' => 'support',
            'priority' => 82,
            'is_active' => true,
        ]);

        // Keyword 6: Custom - Commission / ค่าแนะนำดูดวง
        LineBotKeyword::create([
            'keyword' => 'commission',
            'description' => 'ค่าแนะนำจากการแชร์ลิงก์ดูดวง',
            'trigger_words' => ['commission', 'คอมมิชชั่น', 'earning', 'รายได้', 'ได้เงิน', 'ค่าแนะนำ', 'แชร์รายได้'],
            'response_type' => 'text',
            'response_text' => <<<'RESPONSE'
🎁 ระบบแนะนำเพื่อนดูดวง

แชร์ลิงก์ให้เพื่อนมาดูดวง ทุกครั้งที่เพื่อนดูดวง คุณจะได้รับค่าแนะนำเข้า Wallet ตลอดไปค่ะ ✨

📌 วิธีแชร์:
พิมพ์ "แชร์" เพื่อรับลิงก์เชิญเพื่อน

💰 ค่าแนะนำ:
ทุกครั้งที่เพื่อนดูดวง คุณจะได้รับค่าแนะนำอัตโนมัติเข้า Wallet

📲 การถอนเงิน:
ถอนได้ที่เว็บไซต์ เข้าบัญชีภายใน 1-3 วันทำการ

ลองพิมพ์ "แชร์" เพื่อรับลิงก์ได้เลยค่ะ 🔮
RESPONSE,
            'category' => 'faq',
            'priority' => 72,
            'is_active' => false, // 🛑 (2026-05-10) ปิดชั่วคราว — user ขอเอากล่องนี้ออกก่อน
        ]);

        $this->command->info('✅ Custom Keywords สร้างสำเร็จ! (6 keywords)');
        $this->command->line('');
        $this->command->info('📋 Keywords ที่สร้าง:');
        $this->command->line('  1. refund         - การคืนเงิน');
        $this->command->line('  2. shipping       - การจัดส่ง');
        $this->command->line('  3. payment_issue  - ปัญหาชำระเงิน');
        $this->command->line('  4. affiliate_package - แพคเกจสมาชิก');
        $this->command->line('  5. account        - ปัญหาบัญชี');
        $this->command->line('  6. commission     - คอมมิชชั่น');
        $this->command->line('');
        $this->command->info('💡 แก้ไขได้ที่ /admin/line-bot/keywords/');
    }
}
