<?php

namespace Database\Seeders;

use App\Models\LineBotAiSetting;
use App\Models\LineBotKnowledgeBase;
use Illuminate\Database\Seeder;

/**
 * Seeder สำหรับระบบ LINE Recruitment
 *
 * สร้างข้อมูลตัวอย่างสำหรับ:
 * - AI Settings สำหรับ Recruitment Bot
 * - Knowledge Base (ฐานความรู้สำหรับ AI)
 *
 * @version 1.0.0
 * @since 2025-11-25
 */
class LineRecruitmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('🌱 กำลัง seed ข้อมูล LINE Recruitment...');

        // ตรวจสอบว่ามีข้อมูลอยู่แล้วหรือไม่
        if (LineBotAiSetting::where('name', 'Recruitment Bot')->exists()) {
            $this->command->warn('⚠️  มีข้อมูล LINE Recruitment อยู่แล้ว ข้าม...');
            return;
        }

        // 1. สร้าง AI Setting
        $this->command->info('📝 กำลังสร้าง AI Setting...');
        $aiSetting = $this->createAiSetting();

        if (!$aiSetting) {
            $this->command->error('❌ ไม่สามารถสร้าง AI Setting ได้');
            return;
        }

        // 2. สร้าง Knowledge Base
        $this->command->info('📚 กำลังสร้าง Knowledge Base...');
        $this->createKnowledgeBase($aiSetting);

        $this->command->info('✅ Seed ข้อมูล LINE Recruitment สำเร็จ!');
    }

    /**
     * สร้าง AI Setting สำหรับ Recruitment Bot
     *
     * @return LineBotAiSetting|null
     */
    protected function createAiSetting(): ?LineBotAiSetting
    {
        return LineBotAiSetting::create([
            'name' => 'Recruitment Bot',
            'provider' => 'openai',
            'model' => 'gpt-4',
            'temperature' => 0.7,
            'max_tokens' => 500,
            'system_prompt' => <<<'PROMPT'
คุณเป็น AI Assistant สำหรับระบบรับสมัคร Affiliate

ความรับผิดชอบ:
1. ตอบคำถามเกี่ยวกับการสมัครสมาชิก
2. อธิบายสิทธิประโยชน์และรายได้
3. แนะนำขั้นตอนการสมัคร
4. ช่วยแก้ปัญหาเบื้องต้น

กฎการตอบ:
- ตอบเป็นภาษาไทย
- ตอบสั้นกระชับ ไม่เกิน 300 ตัวอักษร
- ใช้ emoji ให้เหมาะสม
- ถ้าไม่รู้คำตอบให้แนะนำติดต่อ Support
PROMPT,
            'enable_conversation_history' => true,
            'conversation_memory_limit' => 10,
            'is_active' => true,
            'enable_fallback' => true,
            'fallback_message' => 'ขออภัยค่ะ ไม่สามารถตอบคำถามได้ในขณะนี้ กรุณาติดต่อ Support',
        ]);
    }

    /**
     * สร้าง Knowledge Base
     *
     * ใช้ columns ที่ถูกต้องตาม migration:
     * - name (ไม่ใช่ title)
     * - type (ไม่ใช่ source_type)
     * - source (ไม่ใช่ source_url)
     *
     * @param LineBotAiSetting $aiSetting
     * @return void
     */
    protected function createKnowledgeBase(LineBotAiSetting $aiSetting): void
    {
        $knowledgeItems = [
            [
                'name' => 'ข้อมูลโปรแกรม Affiliate',
                'type' => 'text',
                'source' => null,
                'content' => <<<'CONTENT'
# โปรแกรม Affiliate

## เกี่ยวกับเรา
เราเป็นแพลตฟอร์ม Affiliate Marketing ชั้นนำที่ช่วยให้คุณสร้างรายได้เสริมจากการแนะนำสินค้าและบริการ

## สิทธิประโยชน์สมาชิก
1. **คอมมิชชั่นสูงสุด 30%** - รับค่าคอมมิชชั่นจากทุกยอดขายที่คุณแนะนำ
2. **โบนัสทีม** - รับโบนัสเพิ่มเมื่อสร้างทีมขาย
3. **อบรมฟรี** - เข้าร่วมการอบรมออนไลน์ฟรี
4. **เครื่องมือการตลาด** - รับเครื่องมือช่วยขายและโปรโมทสินค้า

## ขั้นตอนการสมัคร
1. กรอกข้อมูลส่วนตัว (ชื่อ, เบอร์โทร, อีเมล)
2. ยืนยันตัวตน
3. เริ่มแนะนำสินค้าและรับคอมมิชชั่น

## การถอนเงิน
- ขั้นต่ำ 300 บาท
- โอนเข้าบัญชีธนาคารภายใน 3 วันทำการ
- รองรับทุกธนาคาร
CONTENT,
                'priority' => 100,
                'is_active' => true,
            ],
            [
                'name' => 'อัตราคอมมิชชั่น',
                'type' => 'text',
                'source' => null,
                'content' => <<<'CONTENT'
# อัตราคอมมิชชั่น

## ระดับคอมมิชชั่น
| ระดับ | อัตรา |
|-------|-------|
| Bronze | 10% |
| Silver | 15% |
| Gold | 20% |
| Platinum | 25% |
| Diamond | 30% |

## โบนัสพิเศษ
- **โบนัสทีม**: 5% จากยอดขายทีม
- **โบนัสรายเดือน**: ตามเป้าหมายยอดขาย
- **โบนัสพิเศษ**: กิจกรรมพิเศษประจำเดือน

## เงื่อนไข
- คอมมิชชั่นจะถูกคำนวณจากยอดขายสุทธิ (หักส่วนลดและคืนสินค้า)
- การเลื่อนระดับขึ้นอยู่กับยอดขายสะสมรายเดือน
CONTENT,
                'priority' => 90,
                'is_active' => true,
            ],
            [
                'name' => 'คำถามที่พบบ่อย (FAQ)',
                'type' => 'text',
                'source' => null,
                'content' => <<<'CONTENT'
# คำถามที่พบบ่อย

## Q: สมัครฟรีหรือไม่?
A: ใช่ การสมัครเป็นสมาชิก Affiliate ไม่มีค่าใช้จ่าย

## Q: ต้องขายสินค้าเองไหม?
A: ไม่จำเป็น คุณเพียงแค่แนะนำสินค้าผ่านลิงก์ส่วนตัว

## Q: รับเงินอย่างไร?
A: ถอนเงินผ่านบัญชีธนาคารที่ลงทะเบียนไว้ ขั้นต่ำ 300 บาท

## Q: มีค่าใช้จ่ายรายเดือนไหม?
A: ไม่มี ไม่มีค่าธรรมเนียมรายเดือน

## Q: เริ่มต้นอย่างไร?
A: 1) สมัครสมาชิก 2) รับลิงก์แนะนำ 3) แชร์ให้เพื่อน 4) รับคอมมิชชั่น
CONTENT,
                'priority' => 80,
                'is_active' => true,
            ],
        ];

        foreach ($knowledgeItems as $item) {
            LineBotKnowledgeBase::create([
                'ai_setting_id' => $aiSetting->id,
                'name' => $item['name'],
                'type' => $item['type'],
                'source' => $item['source'],
                'content' => $item['content'],
                'priority' => $item['priority'],
                'is_active' => $item['is_active'],
            ]);
        }
    }
}
