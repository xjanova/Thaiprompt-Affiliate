<?php

namespace Database\Seeders;

use App\Models\ForumTrophy;
use Illuminate\Database\Seeder;

/**
 * ForumTrophySeeder - สร้างโทรฟี่ฟอรั่มเริ่มต้น
 *
 * รองรับทั้งโทรฟี่ดี (positive) และไม่ดี (negative)
 * โทรฟี่ไม่ดีจะติดตลอดไป (is_permanent = true)
 */
class ForumTrophySeeder extends Seeder
{
    /**
     * รันการ seed โทรฟี่ฟอรั่ม
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('🏆 กำลัง seed โทรฟี่ฟอรั่ม...');

        // โทรฟี่ที่จะสร้าง
        $trophies = [
            // ========================================
            // POSITIVE TROPHIES - โทรฟี่ดี
            // ========================================

            // --- ระดับไลค์ ---
            [
                'name' => '❤️ ถูกใจ',
                'slug' => 'liked',
                'description' => 'ได้รับไลค์ 10 ครั้ง - เริ่มต้นเป็นที่รัก',
                'icon' => '❤️',
                'icon_color' => 'text-red-400',
                'name_color' => null,
                'badge_gradient_from' => 'red-400',
                'badge_gradient_to' => 'pink-400',
                'type' => 'positive',
                'is_permanent' => false,
                'requirement_type' => 'likes_received',
                'requirement_value' => 10,
                'display_order' => 1,
            ],
            [
                'name' => '💖 เป็นที่รัก',
                'slug' => 'beloved',
                'description' => 'ได้รับไลค์ 50 ครั้ง - เป็นที่รักของชุมชน',
                'icon' => '💖',
                'icon_color' => 'text-pink-500',
                'name_color' => 'text-pink-500',
                'badge_gradient_from' => 'pink-500',
                'badge_gradient_to' => 'rose-500',
                'type' => 'positive',
                'is_permanent' => false,
                'requirement_type' => 'likes_received',
                'requirement_value' => 50,
                'display_order' => 2,
            ],
            [
                'name' => '💝 ขวัญใจชุมชน',
                'slug' => 'community-darling',
                'description' => 'ได้รับไลค์ 100 ครั้ง - ขวัญใจของทุกคน',
                'icon' => '💝',
                'icon_color' => 'text-rose-500',
                'name_color' => 'text-rose-500',
                'badge_gradient_from' => 'rose-500',
                'badge_gradient_to' => 'red-500',
                'type' => 'positive',
                'is_permanent' => false,
                'requirement_type' => 'likes_received',
                'requirement_value' => 100,
                'display_order' => 3,
            ],
            [
                'name' => '🌟 ซูเปอร์สตาร์',
                'slug' => 'superstar',
                'description' => 'ได้รับไลค์ 500 ครั้ง - ดาวเด่นของชุมชน',
                'icon' => '🌟',
                'icon_color' => 'text-yellow-400',
                'name_color' => 'text-yellow-500 font-bold',
                'badge_gradient_from' => 'yellow-400',
                'badge_gradient_to' => 'amber-500',
                'type' => 'positive',
                'is_permanent' => false,
                'requirement_type' => 'likes_received',
                'requirement_value' => 500,
                'display_order' => 4,
            ],
            [
                'name' => '👑 ราชา/ราชินีแห่งหัวใจ',
                'slug' => 'heart-royalty',
                'description' => 'ได้รับไลค์ 1,000 ครั้ง - ครองใจทุกคน',
                'icon' => '👑',
                'icon_color' => 'text-amber-500',
                'name_color' => 'bg-gradient-to-r from-yellow-400 via-amber-500 to-orange-500 bg-clip-text text-transparent font-bold',
                'badge_gradient_from' => 'amber-400',
                'badge_gradient_to' => 'orange-500',
                'type' => 'positive',
                'is_permanent' => false,
                'requirement_type' => 'likes_received',
                'requirement_value' => 1000,
                'display_order' => 5,
            ],

            // --- ระดับโพสต์ ---
            [
                'name' => '✍️ นักเขียนหน้าใหม่',
                'slug' => 'new-writer',
                'description' => 'โพสต์ครบ 10 โพสต์ - เริ่มต้นการแบ่งปัน',
                'icon' => '✍️',
                'icon_color' => 'text-blue-400',
                'name_color' => null,
                'badge_gradient_from' => 'blue-400',
                'badge_gradient_to' => 'indigo-400',
                'type' => 'positive',
                'is_permanent' => false,
                'requirement_type' => 'posts_count',
                'requirement_value' => 10,
                'display_order' => 10,
            ],
            [
                'name' => '📝 นักเขียนประจำ',
                'slug' => 'regular-writer',
                'description' => 'โพสต์ครบ 50 โพสต์ - มีส่วนร่วมอย่างสม่ำเสมอ',
                'icon' => '📝',
                'icon_color' => 'text-indigo-500',
                'name_color' => 'text-indigo-500',
                'badge_gradient_from' => 'indigo-500',
                'badge_gradient_to' => 'purple-500',
                'type' => 'positive',
                'is_permanent' => false,
                'requirement_type' => 'posts_count',
                'requirement_value' => 50,
                'display_order' => 11,
            ],
            [
                'name' => '📚 นักเขียนมืออาชีพ',
                'slug' => 'pro-writer',
                'description' => 'โพสต์ครบ 100 โพสต์ - ผู้เชี่ยวชาญด้านเนื้อหา',
                'icon' => '📚',
                'icon_color' => 'text-purple-500',
                'name_color' => 'text-purple-500',
                'badge_gradient_from' => 'purple-500',
                'badge_gradient_to' => 'violet-600',
                'type' => 'positive',
                'is_permanent' => false,
                'requirement_type' => 'posts_count',
                'requirement_value' => 100,
                'display_order' => 12,
            ],
            [
                'name' => '🖋️ นักเขียนตำนาน',
                'slug' => 'legendary-writer',
                'description' => 'โพสต์ครบ 500 โพสต์ - ตำนานแห่งชุมชน',
                'icon' => '🖋️',
                'icon_color' => 'text-violet-600',
                'name_color' => 'bg-gradient-to-r from-purple-500 via-violet-500 to-pink-500 bg-clip-text text-transparent font-bold',
                'badge_gradient_from' => 'violet-500',
                'badge_gradient_to' => 'purple-600',
                'type' => 'positive',
                'is_permanent' => false,
                'requirement_type' => 'posts_count',
                'requirement_value' => 500,
                'display_order' => 13,
            ],

            // --- ระดับกระทู้ ---
            [
                'name' => '💡 นักคิดหน้าใหม่',
                'slug' => 'new-thinker',
                'description' => 'สร้างกระทู้ครบ 5 กระทู้ - เริ่มต้นแบ่งปันความคิด',
                'icon' => '💡',
                'icon_color' => 'text-yellow-400',
                'name_color' => null,
                'badge_gradient_from' => 'yellow-400',
                'badge_gradient_to' => 'orange-400',
                'type' => 'positive',
                'is_permanent' => false,
                'requirement_type' => 'threads_count',
                'requirement_value' => 5,
                'display_order' => 20,
            ],
            [
                'name' => '🧠 นักคิดชั้นเซียน',
                'slug' => 'master-thinker',
                'description' => 'สร้างกระทู้ครบ 20 กระทู้ - ผู้นำความคิดของชุมชน',
                'icon' => '🧠',
                'icon_color' => 'text-orange-500',
                'name_color' => 'text-orange-500',
                'badge_gradient_from' => 'orange-500',
                'badge_gradient_to' => 'red-500',
                'type' => 'positive',
                'is_permanent' => false,
                'requirement_type' => 'threads_count',
                'requirement_value' => 20,
                'display_order' => 21,
            ],

            // --- ระดับ Best Answer ---
            [
                'name' => '✅ ผู้ช่วยเหลือ',
                'slug' => 'helper',
                'description' => 'ได้รับ Best Answer 5 ครั้ง - ช่วยเหลือคนอื่นได้ดี',
                'icon' => '✅',
                'icon_color' => 'text-green-500',
                'name_color' => 'text-green-500',
                'badge_gradient_from' => 'green-500',
                'badge_gradient_to' => 'emerald-600',
                'type' => 'positive',
                'is_permanent' => false,
                'requirement_type' => 'best_answers_count',
                'requirement_value' => 5,
                'display_order' => 30,
            ],
            [
                'name' => '🎓 ผู้เชี่ยวชาญ',
                'slug' => 'expert',
                'description' => 'ได้รับ Best Answer 20 ครั้ง - ผู้รู้ของชุมชน',
                'icon' => '🎓',
                'icon_color' => 'text-emerald-600',
                'name_color' => 'text-emerald-600 font-semibold',
                'badge_gradient_from' => 'emerald-500',
                'badge_gradient_to' => 'teal-600',
                'type' => 'positive',
                'is_permanent' => false,
                'requirement_type' => 'best_answers_count',
                'requirement_value' => 20,
                'display_order' => 31,
            ],
            [
                'name' => '🧙 ปรมาจารย์',
                'slug' => 'guru',
                'description' => 'ได้รับ Best Answer 50 ครั้ง - ปรมาจารย์แห่งชุมชน',
                'icon' => '🧙',
                'icon_color' => 'text-teal-600',
                'name_color' => 'bg-gradient-to-r from-emerald-500 via-teal-500 to-cyan-500 bg-clip-text text-transparent font-bold',
                'badge_gradient_from' => 'teal-500',
                'badge_gradient_to' => 'cyan-600',
                'type' => 'positive',
                'is_permanent' => false,
                'requirement_type' => 'best_answers_count',
                'requirement_value' => 50,
                'display_order' => 32,
            ],

            // --- โทรฟี่พิเศษ (Manual) ---
            [
                'name' => '💎 VIP Member',
                'slug' => 'vip',
                'description' => 'สมาชิก VIP พิเศษ - ได้รับสิทธิพิเศษจาก Admin',
                'icon' => '💎',
                'icon_color' => 'text-cyan-400',
                'name_color' => 'text-cyan-400 font-bold',
                'badge_gradient_from' => 'cyan-400',
                'badge_gradient_to' => 'blue-500',
                'type' => 'positive',
                'is_permanent' => false,
                'requirement_type' => 'manual',
                'requirement_value' => 0,
                'display_order' => 40,
            ],
            [
                'name' => '🌈 Contributor',
                'slug' => 'contributor',
                'description' => 'ผู้มีส่วนร่วมพิเศษ - ช่วยพัฒนาชุมชน',
                'icon' => '🌈',
                'icon_color' => 'text-pink-400',
                'name_color' => 'bg-gradient-to-r from-pink-400 via-purple-400 to-indigo-400 bg-clip-text text-transparent',
                'badge_gradient_from' => 'pink-400',
                'badge_gradient_to' => 'purple-500',
                'type' => 'positive',
                'is_permanent' => false,
                'requirement_type' => 'manual',
                'requirement_value' => 0,
                'display_order' => 41,
            ],
            [
                'name' => '⭐ Official Staff',
                'slug' => 'staff',
                'description' => 'ทีมงานอย่างเป็นทางการ',
                'icon' => '⭐',
                'icon_color' => 'text-amber-400',
                'name_color' => 'text-amber-500 font-bold',
                'badge_gradient_from' => 'amber-400',
                'badge_gradient_to' => 'yellow-500',
                'type' => 'positive',
                'is_permanent' => false,
                'requirement_type' => 'manual',
                'requirement_value' => 0,
                'display_order' => 42,
            ],

            // ========================================
            // NEGATIVE TROPHIES - โทรฟี่ไม่ดี (ติดตลอด)
            // ========================================

            [
                'name' => '⚠️ ถูกเตือน',
                'slug' => 'warned',
                'description' => 'ได้รับคำเตือน 3 ครั้ง - ระวังพฤติกรรม',
                'icon' => '⚠️',
                'icon_color' => 'text-yellow-600',
                'name_color' => 'text-yellow-600',
                'badge_gradient_from' => 'yellow-500',
                'badge_gradient_to' => 'orange-500',
                'type' => 'negative',
                'is_permanent' => true,
                'requirement_type' => 'warnings_count',
                'requirement_value' => 3,
                'display_order' => 100,
            ],
            [
                'name' => '🚫 ผู้สร้างปัญหา',
                'slug' => 'troublemaker',
                'description' => 'ได้รับคำเตือน 5 ครั้ง - พฤติกรรมไม่เหมาะสมซ้ำซาก',
                'icon' => '🚫',
                'icon_color' => 'text-orange-600',
                'name_color' => 'text-orange-600 font-semibold',
                'badge_gradient_from' => 'orange-500',
                'badge_gradient_to' => 'red-500',
                'type' => 'negative',
                'is_permanent' => true,
                'requirement_type' => 'warnings_count',
                'requirement_value' => 5,
                'display_order' => 101,
            ],
            [
                'name' => '☠️ บัญชีดำ',
                'slug' => 'blacklisted',
                'description' => 'ถูกแบน 2 ครั้งขึ้นไป - บัญชีดำถาวร',
                'icon' => '☠️',
                'icon_color' => 'text-red-600',
                'name_color' => 'text-red-600 font-bold',
                'badge_gradient_from' => 'red-600',
                'badge_gradient_to' => 'rose-700',
                'type' => 'negative',
                'is_permanent' => true,
                'requirement_type' => 'banned_count',
                'requirement_value' => 2,
                'display_order' => 102,
            ],
            [
                'name' => '🗑️ สแปมเมอร์',
                'slug' => 'spammer',
                'description' => 'ถูกรายงานสแปม 10 ครั้ง - ผู้ส่งสแปมซ้ำซาก',
                'icon' => '🗑️',
                'icon_color' => 'text-gray-600',
                'name_color' => 'text-gray-500 line-through',
                'badge_gradient_from' => 'gray-500',
                'badge_gradient_to' => 'slate-600',
                'type' => 'negative',
                'is_permanent' => true,
                'requirement_type' => 'spam_reports',
                'requirement_value' => 10,
                'display_order' => 103,
            ],

            // --- โทรฟี่ไม่ดี (Manual) ---
            [
                'name' => '🔴 ถูกระงับถาวร',
                'slug' => 'permanently-suspended',
                'description' => 'ถูกระงับการใช้งานถาวรจาก Admin',
                'icon' => '🔴',
                'icon_color' => 'text-red-700',
                'name_color' => 'text-red-700 font-bold line-through',
                'badge_gradient_from' => 'red-700',
                'badge_gradient_to' => 'rose-900',
                'type' => 'negative',
                'is_permanent' => true,
                'requirement_type' => 'manual',
                'requirement_value' => 0,
                'display_order' => 110,
            ],
            [
                'name' => '🎭 นักโกหก',
                'slug' => 'liar',
                'description' => 'ถูกจับได้ว่าโกหกหรือให้ข้อมูลเท็จ',
                'icon' => '🎭',
                'icon_color' => 'text-purple-700',
                'name_color' => 'text-purple-700 italic',
                'badge_gradient_from' => 'purple-700',
                'badge_gradient_to' => 'violet-800',
                'type' => 'negative',
                'is_permanent' => true,
                'requirement_type' => 'manual',
                'requirement_value' => 0,
                'display_order' => 111,
            ],
            [
                'name' => '💀 สแกมเมอร์',
                'slug' => 'scammer',
                'description' => 'พยายามหลอกลวงสมาชิก - อันตราย!',
                'icon' => '💀',
                'icon_color' => 'text-black',
                'name_color' => 'text-black font-bold bg-yellow-300 px-2',
                'badge_gradient_from' => 'gray-900',
                'badge_gradient_to' => 'black',
                'type' => 'negative',
                'is_permanent' => true,
                'requirement_type' => 'manual',
                'requirement_value' => 0,
                'display_order' => 112,
            ],
        ];

        foreach ($trophies as $trophyData) {
            ForumTrophy::updateOrCreate(
                ['slug' => $trophyData['slug']],
                $trophyData
            );

            $type = $trophyData['type'] === 'positive' ? '✅' : '❌';
            $this->command->info("  {$type} สร้างโทรฟี่: {$trophyData['name']}");
        }

        $this->command->info('✨ Seed โทรฟี่ฟอรั่มสำเร็จ!');
    }
}
