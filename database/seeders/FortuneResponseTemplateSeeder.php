<?php

namespace Database\Seeders;

use App\Models\FortuneResponseTemplate;
use Illuminate\Database\Seeder;

/**
 * Fortune Response Template Seeder
 *
 * สร้างเทมเพลตตอบกลับคำทำนายเริ่มต้น
 * รองรับ: basic, deep, welcome, payment, limit_exceeded, error
 */
class FortuneResponseTemplateSeeder extends Seeder
{
    /**
     * สร้างเทมเพลตตอบกลับเริ่มต้น
     */
    public function run(): void
    {
        $this->command->info('🌱 กำลัง seed เทมเพลตตอบกลับคำทำนาย...');

        $templates = $this->getTemplates();

        foreach ($templates as $templateData) {
            if (FortuneResponseTemplate::where('slug', $templateData['slug'])->exists()) {
                $this->command->info("เทมเพลต '{$templateData['name']}' มีอยู่แล้ว ข้าม...");

                continue;
            }

            FortuneResponseTemplate::create($templateData);
            $this->command->info("✅ สร้างเทมเพลต '{$templateData['name']}' สำเร็จ");
        }

        $this->command->info('✅ Seed เทมเพลตตอบกลับคำทำนายสำเร็จ!');
    }

    /**
     * รายการเทมเพลตเริ่มต้น
     */
    protected function getTemplates(): array
    {
        return [
            // ============================================================
            // เทมเพลตคำทำนายพื้นฐาน
            // ============================================================
            [
                'name' => 'คำทำนายพื้นฐาน - มาตรฐาน',
                'slug' => 'basic-standard',
                'type' => 'basic',
                'body' => <<<'EOT'
🔮 คำทำนายสำหรับคุณ{user_name}
━━━━━━━━━━━━━━━━━━━

{response}

━━━━━━━━━━━━━━━━━━━
📅 วันที่ทำนาย: {date}
⭐ ให้คะแนน: {rate_url}

💡 ต้องการคำทำนายเชิงลึกละเอียดกว่านี้?
พิมพ์: "ดูดวงละเอียด" เพื่อรับคำทำนายแบบเจาะลึก
📅 บอกวันเดือนปีเกิด เพื่อทำนายแม่นยำยิ่งขึ้น!
EOT,
                'header_text' => null,
                'footer_text' => null,
                'header_image_url' => null,
                'footer_image_url' => null,
                'available_placeholders' => [
                    '{response}', '{user_name}', '{date}', '{rate_url}',
                ],
                'is_active' => true,
                'is_default' => true,
                'sort_order' => 1,
            ],

            [
                'name' => 'คำทำนายพื้นฐาน - สั้นกระชับ',
                'slug' => 'basic-short',
                'type' => 'basic',
                'body' => <<<'EOT'
🔮 ผลดูดวง ({date})

{response}

---
⭐ ให้คะแนน: {rate_url}
🌟 ดูดวงละเอียด → พิมพ์ "ดูดวงละเอียด"
EOT,
                'header_text' => null,
                'footer_text' => null,
                'header_image_url' => null,
                'footer_image_url' => null,
                'available_placeholders' => [
                    '{response}', '{date}', '{rate_url}',
                ],
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 2,
            ],

            // ============================================================
            // เทมเพลตคำทำนายเชิงลึก
            // ============================================================
            [
                'name' => 'คำทำนายเชิงลึก - มาตรฐาน',
                'slug' => 'deep-standard',
                'type' => 'deep',
                'body' => <<<'EOT'
🌟✨ คำทำนายเชิงลึกสำหรับคุณ{user_name} ✨🌟
━━━━━━━━━━━━━━━━━━━━━━━━━━

📋 คำถามของคุณ:
{questions}

🔮 คำทำนายเชิงลึก:

{response}

━━━━━━━━━━━━━━━━━━━━━━━━━━
📅 ทำนายเมื่อ: {date_thai}
♈ ราศี: {zodiac}
⭐ ให้คะแนนความพึงพอใจ: {rate_url}

💡 บอกวันเดือนปีเกิด เพื่อรับคำทำนายที่แม่นยำยิ่งขึ้น!
🙏 ขอบคุณที่ไว้วางใจใช้บริการ
📱 สมัครสมาชิกเพื่อรับคำทำนายไม่จำกัด: {register_url}
EOT,
                'header_text' => null,
                'footer_text' => null,
                'header_image_url' => null,
                'footer_image_url' => null,
                'available_placeholders' => [
                    '{response}', '{user_name}', '{questions}',
                    '{date_thai}', '{rate_url}', '{register_url}',
                    '{birth_date}', '{zodiac}',
                ],
                'is_active' => true,
                'is_default' => true,
                'sort_order' => 1,
            ],

            [
                'name' => 'คำทำนายเชิงลึก - พรีเมียม',
                'slug' => 'deep-premium',
                'type' => 'deep',
                'body' => <<<'EOT'
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  🌟 ⭐ คำทำนายพรีเมียม ⭐ 🌟
  สำหรับ: {user_name}
  วันที่: {date_thai}
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

📝 คำถามที่ได้รับ:
{questions}

🔮 ✦ คำทำนายละเอียดเชิงลึก ✦

{response}

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📊 เลขที่: #{reading_id}
⭐ ให้คะแนน: {rate_url}

💡 ดูดวงอีกครั้งได้เลย → พิมพ์ "ดูดวง"
📅 บอกวันเดือนปีเกิด เพื่อทำนายแม่นยำยิ่งขึ้น!
📱 สมัครสมาชิก VIP: {register_url}
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
EOT,
                'header_text' => null,
                'footer_text' => null,
                'header_image_url' => null,
                'footer_image_url' => null,
                'available_placeholders' => [
                    '{response}', '{user_name}', '{questions}', '{date_thai}',
                    '{reading_id}', '{rate_url}', '{register_url}',
                    '{birth_date}', '{zodiac}',
                ],
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 2,
            ],

            // ============================================================
            // เทมเพลตต้อนรับ
            // ============================================================
            [
                'name' => 'ข้อความต้อนรับ - มาตรฐาน',
                'slug' => 'welcome-standard',
                'type' => 'welcome',
                'body' => <<<'EOT'
🔮 สวัสดีค่ะ ยินดีต้อนรับสู่ระบบดูดวง!

📖 วิธีใช้งาน:
━━━━━━━━━━━━━━━━
✅ พิมพ์: "ดูดวง" ตามด้วยคำถาม
   เช่น: ดูดวง เรื่องความรัก, การเงิน

🌟 พิมพ์: "ดูดวงละเอียด" เพื่อรับคำทำนายเชิงลึก
   เช่น: ดูดวงละเอียด การงานปีนี้

📸 ส่งรูปภาพ: ส่งรูปมือ/ลายเส้นเพื่อดูดวงจากรูป

━━━━━━━━━━━━━━━━
🆓 ฟรีวันละ {max_free} ครั้ง
🌟 ดูดวงเชิงลึกฟรีวันละ 1 ครั้ง

เริ่มเลย → พิมพ์ "ดูดวง" 🔮
EOT,
                'header_text' => null,
                'footer_text' => null,
                'header_image_url' => null,
                'footer_image_url' => null,
                'available_placeholders' => [
                    '{max_free}',
                ],
                'is_active' => true,
                'is_default' => true,
                'sort_order' => 1,
            ],

            // ============================================================
            // เทมเพลตแจ้งชำระเงิน (พร้อมรูป QR Code)
            // ============================================================
            [
                'name' => 'แจ้งชำระเงิน - QR Code',
                'slug' => 'payment-qr',
                'type' => 'payment',
                'body' => <<<'EOT'
💰 ชำระเงินเพื่อใช้งานต่อ
━━━━━━━━━━━━━━━━━━━

💎 ราคาต่อครั้ง: {price} บาท

📲 วิธีชำระเงิน:
1. สแกน QR Code ด้านล่าง
2. โอนเงินตามจำนวนที่ระบุ
3. ส่งสลิปมาในแชทนี้
4. รอรับคำทำนายภายใน 1 นาที

💡 หรือสมัครสมาชิกรายเดือนเพื่อใช้งานไม่จำกัด
📱 สมัครสมาชิก: {register_url}
EOT,
                'header_text' => null,
                'footer_text' => '🙏 ขอบคุณที่ใช้บริการค่ะ',
                'header_image_url' => null,
                'footer_image_url' => null, // ตั้งค่า QR Code URL ภายหลัง
                'available_placeholders' => [
                    '{price}', '{register_url}',
                ],
                'is_active' => true,
                'is_default' => true,
                'sort_order' => 1,
            ],

            [
                'name' => 'แจ้งชำระเงิน - บัญชีธนาคาร',
                'slug' => 'payment-bank',
                'type' => 'payment',
                'body' => <<<'EOT'
💰 ชำระเงินเพื่อใช้งานต่อ
━━━━━━━━━━━━━━━━━━━

💎 ราคาต่อครั้ง: {price} บาท

🏦 ช่องทางชำระเงิน:
━━━━━━━━━━━━━━━━━━━
📱 พร้อมเพย์ / QR Code
   (ดูรูปด้านล่าง)

📋 หรือโอนผ่านบัญชีธนาคาร:
   ธนาคาร: กสิกรไทย
   ชื่อบัญชี: บริษัท ไทยพร๊อมท์ จำกัด
   เลขบัญชี: XXX-X-XXXXX-X

━━━━━━━━━━━━━━━━━━━
📸 หลังโอนเงิน: ส่งสลิปมาในแชทนี้
⏱️ ระบบจะยืนยันภายใน 1-5 นาที

📱 สมัครสมาชิกรายเดือน: {register_url}
EOT,
                'header_text' => null,
                'footer_text' => null,
                'header_image_url' => null,
                'footer_image_url' => null, // ตั้งค่ารูปบัญชีธนาคารภายหลัง
                'available_placeholders' => [
                    '{price}', '{register_url}',
                ],
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 2,
            ],

            // ============================================================
            // เทมเพลตหมดโควต้าฟรี
            // ============================================================
            [
                'name' => 'หมดโควต้าฟรี - มาตรฐาน',
                'slug' => 'limit-exceeded-standard',
                'type' => 'limit_exceeded',
                'body' => <<<'EOT'
⏳ หมดโควต้าฟรีวันนี้แล้วค่ะ
━━━━━━━━━━━━━━━━━━━

คุณ{user_name} ได้ใช้งานครบ {max_free} ครั้งแล้ววันนี้
เหลือ: {remaining_free} ครั้ง

💎 ต้องการดูดวงเพิ่ม?
━━━━━━━━━━━━━━━━━━━
💰 ชำระต่อครั้ง: {price} บาท
📱 สมัครสมาชิก: ใช้งานไม่จำกัด!

📲 ชำระเงิน/สมัคร: {register_url}

⏰ หรือกลับมาใช้ฟรีอีกครั้งพรุ่งนี้ค่ะ!
EOT,
                'header_text' => null,
                'footer_text' => null,
                'header_image_url' => null,
                'footer_image_url' => null,
                'available_placeholders' => [
                    '{user_name}', '{max_free}', '{remaining_free}',
                    '{price}', '{register_url}',
                ],
                'is_active' => true,
                'is_default' => true,
                'sort_order' => 1,
            ],

            // ============================================================
            // เทมเพลตข้อผิดพลาด
            // ============================================================
            [
                'name' => 'ข้อผิดพลาด - มาตรฐาน',
                'slug' => 'error-standard',
                'type' => 'error',
                'body' => <<<'EOT'
😔 ขออภัยค่ะ ขณะนี้ระบบมีปัญหา

กรุณาลองใหม่อีกครั้งในอีกสักครู่ค่ะ
หากยังมีปัญหา กรุณาติดต่อแอดมิน 🙏

พิมพ์ "ดูดวง" เพื่อลองใหม่
EOT,
                'header_text' => null,
                'footer_text' => null,
                'header_image_url' => null,
                'footer_image_url' => null,
                'available_placeholders' => [],
                'is_active' => true,
                'is_default' => true,
                'sort_order' => 1,
            ],
        ];
    }
}
