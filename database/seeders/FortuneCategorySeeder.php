<?php

namespace Database\Seeders;

use App\Models\FortuneCategory;
use Illuminate\Database\Seeder;

/**
 * Fortune Category Seeder
 *
 * สร้างหมวดหมู่การทำนายเริ่มต้น
 */
class FortuneCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 กำลัง seed หมวดหมู่การทำนาย...');

        $categories = [
            [
                'name' => 'ความรัก',
                'slug' => 'khwam-rak',
                'icon' => '💕',
                'color' => '#EF4444',
                'description' => 'ทำนายเรื่องความรัก ความสัมพันธ์ คู่ครอง',
                'prompt_context' => 'เน้นวิเคราะห์เรื่องความรัก ความสัมพันธ์ คู่ครอง และโอกาสในความรัก',
                'example_questions' => "ความรักของฉันจะเป็นอย่างไร\nคนที่ฉันชอบเขารักฉันไหม\nจะได้พบคู่ชีวิตเมื่อไหร่",
                'is_active' => true,
                'order' => 1,
            ],
            [
                'name' => 'การเงิน',
                'slug' => 'kan-ngoen',
                'icon' => '💰',
                'color' => '#10B981',
                'description' => 'ทำนายเรื่องการเงิน การงาน ความมั่งคั่ง',
                'prompt_context' => 'เน้นวิเคราะห์เรื่องการเงิน การลงทุน โชคลาภ และความมั่งคั่ง',
                'example_questions' => "การเงินของฉันจะดีขึ้นไหม\nควรลงทุนอะไรดี\nจะรวยเมื่อไหร่",
                'is_active' => true,
                'order' => 2,
            ],
            [
                'name' => 'สุขภาพ',
                'slug' => 'sukhaphap',
                'icon' => '🏥',
                'color' => '#3B82F6',
                'description' => 'ทำนายเรื่องสุขภาพ โรคภัย',
                'prompt_context' => 'เน้นวิเคราะห์เรื่องสุขภาพ โรคภัย และการดูแลสุขภาพ',
                'example_questions' => "สุขภาพของฉันเป็นอย่างไร\nควรระวังโรคอะไร\nจะแข็งแรงไหม",
                'is_active' => true,
                'order' => 3,
            ],
            [
                'name' => 'การงาน',
                'slug' => 'kan-ngan',
                'icon' => '💼',
                'color' => '#F59E0B',
                'description' => 'ทำนายเรื่องการงาน อาชีพ ความก้าวหน้า',
                'prompt_context' => 'เน้นวิเคราะห์เรื่องการงาน อาชีพ โอกาสความก้าวหน้า และการเปลี่ยนงาน',
                'example_questions' => "การงานของฉันจะดีไหม\nควรเปลี่ยนงานไหม\nจะได้เลื่อนตำแหน่งเมื่อไหร่",
                'is_active' => true,
                'order' => 4,
            ],
            [
                'name' => 'ครอบครัว',
                'slug' => 'khrop-khrua',
                'icon' => '👨‍👩‍👧‍👦',
                'color' => '#8B5CF6',
                'description' => 'ทำนายเรื่องครอบครัว บุตร',
                'prompt_context' => 'เน้นวิเคราะห์เรื่องครอบครัว พ่อแม่ ลูก และความสัมพันธ์ในครอบครัว',
                'example_questions' => "ครอบครัวของฉันจะอบอุ่นไหม\nจะมีลูกเมื่อไหร่\nพ่อแม่จะมีสุขภาพดีไหม",
                'is_active' => true,
                'order' => 5,
            ],
            [
                'name' => 'โชคลาภ',
                'slug' => 'chok-lap',
                'icon' => '🍀',
                'color' => '#EC4899',
                'description' => 'ทำนายเรื่องโชคลาภ ดวง โชคชะตา',
                'prompt_context' => 'เน้นวิเคราะห์เรื่องโชคลาภ โชคชะตา เลขเด็ด และโชคดี',
                'example_questions' => "ดวงของฉันเป็นอย่างไร\nเลขอะไรจะถูกรางวัล\nจะมีโชคลาภไหม",
                'is_active' => true,
                'order' => 6,
            ],
        ];

        foreach ($categories as $categoryData) {
            // ✅ ตรวจสอบว่ามีหมวดหมู่นี้แล้วหรือยัง
            if (FortuneCategory::where('slug', $categoryData['slug'])->exists()) {
                $this->command->info("หมวดหมู่ '{$categoryData['name']}' มีอยู่แล้ว ข้าม...");
                continue;
            }

            FortuneCategory::create($categoryData);
            $this->command->info("✅ สร้างหมวดหมู่ '{$categoryData['name']}' สำเร็จ");
        }

        $this->command->info('✅ Seed หมวดหมู่การทำนายสำเร็จ!');
    }
}
