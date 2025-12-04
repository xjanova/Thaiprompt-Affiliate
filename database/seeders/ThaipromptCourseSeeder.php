<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LearningArticle;
use App\Models\LearningCategory;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuestionOption;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * ThaipromptCourseSeeder
 *
 * สร้างคอร์สเรียนรู้โครงการ Thaiprompt แบบครบถ้วน
 * เรียงตามลำดับความยากจากง่ายไปยาก
 * มีแบบทดสอบทุกบทเรียน พร้อมรางวัล Coin, Points, EXP, PV
 */
class ThaipromptCourseSeeder extends Seeder
{
    /**
     * ระดับความยากและรางวัล
     */
    private const LEVEL_REWARDS = [
        1 => ['points' => 10, 'coins' => 5.00, 'exp' => 50, 'pv' => 1.00],    // เริ่มต้น
        2 => ['points' => 15, 'coins' => 10.00, 'exp' => 75, 'pv' => 2.00],   // พื้นฐาน
        3 => ['points' => 25, 'coins' => 20.00, 'exp' => 100, 'pv' => 3.00],  // ปานกลาง
        4 => ['points' => 40, 'coins' => 35.00, 'exp' => 150, 'pv' => 5.00],  // ก้าวหน้า
        5 => ['points' => 60, 'coins' => 50.00, 'exp' => 200, 'pv' => 8.00],  // ขั้นสูง
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first() ?? User::first();

        if (!$admin) {
            $this->command->error('❌ ไม่พบผู้ใช้ในระบบ กรุณาสร้างผู้ใช้ก่อน');
            return;
        }

        $this->command->info('🎓 กำลังสร้างคอร์ส Thaiprompt Academy...');

        // สร้างหมวดหมู่หลักสำหรับ Thaiprompt
        $category = $this->createThaipromptCategory();

        // สร้างคอร์สตามลำดับ
        $courses = $this->getAllCourses();
        $created = 0;
        $skipped = 0;

        foreach ($courses as $index => $courseData) {
            if (LearningArticle::where('slug', $courseData['slug'])->exists()) {
                $skipped++;
                continue;
            }

            $rewards = self::LEVEL_REWARDS[$courseData['course_level']] ?? self::LEVEL_REWARDS[1];

            $article = LearningArticle::create([
                'category_id' => $category->id,
                'title' => $courseData['title'],
                'slug' => $courseData['slug'],
                'excerpt' => $courseData['excerpt'],
                'content' => $courseData['content'],
                'estimated_duration' => $courseData['duration'],
                'difficulty' => $courseData['difficulty'],
                'course_level' => $courseData['course_level'],
                'order' => $index + 1,
                'is_published' => true,
                'is_featured' => $courseData['is_featured'] ?? false,
                'tags' => $courseData['tags'],
                'views' => 0,
                'require_quiz_pass' => true,
                'min_quiz_score' => 70,
                'unlock_condition' => $index === 0 ? 'none' : 'prerequisite',
                'points_reward' => $rewards['points'],
                'coin_reward' => $rewards['coins'],
                'exp_reward' => $rewards['exp'],
                'pv_value' => $rewards['pv'],
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
                'published_at' => now(),
            ]);

            // สร้าง Quiz และ Questions
            if (!empty($courseData['quiz'])) {
                $this->createQuiz($article, $courseData['quiz']);
            }

            $created++;
            $this->command->info("   ✅ {$courseData['title']}");
        }

        $this->command->info("🎉 สร้างคอร์สสำเร็จ: {$created} คอร์ส");
        if ($skipped > 0) {
            $this->command->info("   ⏭️ ข้าม {$skipped} คอร์สที่มีอยู่แล้ว");
        }
    }

    /**
     * สร้างหมวดหมู่ Thaiprompt
     */
    private function createThaipromptCategory(): LearningCategory
    {
        return LearningCategory::firstOrCreate(
            ['slug' => 'thaiprompt-academy'],
            [
                'name' => 'Thaiprompt Academy',
                'description' => 'คอร์สเรียนรู้โครงการ Thaiprompt ตั้งแต่เริ่มต้นจนถึงขั้นสูง เรียนจบรับ Coins และ PV',
                'icon' => '🎓',
                'color' => '#7C3AED',
                'order' => 0,
                'is_active' => true,
            ]
        );
    }

    /**
     * สร้าง Quiz สำหรับบทความ
     */
    private function createQuiz(LearningArticle $article, array $quizData): void
    {
        $quiz = Quiz::create([
            'article_id' => $article->id,
            'title' => "แบบทดสอบ: {$article->title}",
            'description' => $quizData['description'] ?? "ทดสอบความเข้าใจหลังเรียนจบบทเรียน",
            'passing_score' => 70,
            'time_limit' => $quizData['time_limit'] ?? 15,
            'max_attempts' => null,
            'randomize_questions' => true,
            'show_results_immediately' => true,
            'show_correct_answers' => true,
            'is_required' => true,
            'is_active' => true,
            'order' => 1,
        ]);

        foreach ($quizData['questions'] as $index => $questionData) {
            $question = QuizQuestion::create([
                'quiz_id' => $quiz->id,
                'type' => $questionData['type'] ?? 'multiple_choice',
                'question' => $questionData['question'],
                'explanation' => $questionData['explanation'] ?? null,
                'points' => $questionData['points'] ?? 1,
                'order' => $index + 1,
            ]);

            // สร้างตัวเลือกคำตอบ
            if (!empty($questionData['answers'])) {
                foreach ($questionData['answers'] as $answerIndex => $answer) {
                    QuestionOption::create([
                        'question_id' => $question->id,
                        'option_text' => $answer['text'],
                        'is_correct' => $answer['is_correct'] ?? false,
                        'order' => $answerIndex + 1,
                    ]);
                }
            }
        }
    }

    /**
     * รายการคอร์สทั้งหมด
     */
    private function getAllCourses(): array
    {
        return [
            // ========== ระดับ 1: เริ่มต้น (เนื้อหาละเอียดพร้อมแบบฝึกหัด) ==========
            [
                'title' => 'รู้จัก Thaiprompt คืออะไร?',
                'slug' => 'thaiprompt-introduction',
                'excerpt' => 'ทำความรู้จักกับโครงการ Thaiprompt แบบครบถ้วน ประวัติ วิสัยทัศน์ พันธกิจ ระบบต่างๆ และประโยชน์ที่จะได้รับ พร้อมแบบฝึกหัด',
                'duration' => 30,
                'difficulty' => 'beginner',
                'course_level' => 1,
                'is_featured' => true,
                'tags' => ['Thaiprompt', 'เริ่มต้น', 'แนะนำ', 'ภาพรวม'],
                'content' => $this->generateContent('รู้จัก Thaiprompt คืออะไร?', $this->getCourse1Content()),
                'quiz' => $this->getCourse1Quiz(),
            ],
            [
                'title' => 'การสมัครสมาชิก Thaiprompt',
                'slug' => 'thaiprompt-registration',
                'excerpt' => 'เรียนรู้วิธีสมัครสมาชิก Thaiprompt ทั้งทางเว็บและ LINE การยืนยันตัวตน KYC การตั้งค่าโปรไฟล์และความปลอดภัย พร้อมแบบฝึกหัดปฏิบัติ',
                'duration' => 45,
                'difficulty' => 'beginner',
                'course_level' => 1,
                'is_featured' => false,
                'tags' => ['สมัครสมาชิก', 'เริ่มต้น', 'LINE', 'KYC', '2FA'],
                'content' => $this->generateContent('การสมัครสมาชิก Thaiprompt', $this->getCourse2Content()),
                'quiz' => $this->getCourse2Quiz(),
            ],
            [
                'title' => 'หน้า Dashboard และการนำทาง',
                'slug' => 'thaiprompt-dashboard-navigation',
                'excerpt' => 'เข้าใจ Dashboard อย่างลึกซึ้ง Widget ข้อมูลสรุป เมนูหลัก การค้นหา Filter Sort และการปรับแต่ง พร้อมแบบฝึกหัด',
                'duration' => 40,
                'difficulty' => 'beginner',
                'course_level' => 1,
                'is_featured' => false,
                'tags' => ['Dashboard', 'เมนู', 'นำทาง', 'Widget', 'Filter'],
                'content' => $this->generateContent('หน้า Dashboard และการนำทาง', $this->getCourse3Content()),
                'quiz' => $this->getCourse3Quiz(),
            ],

            // ========== ระดับ 2: พื้นฐาน (เนื้อหาละเอียดพร้อมแบบฝึกหัด) ==========
            [
                'title' => 'ระบบ Affiliate Marketing เบื้องต้น',
                'slug' => 'thaiprompt-affiliate-basic',
                'excerpt' => 'เรียนรู้พื้นฐานระบบ Affiliate การแชร์ลิงก์ โครงสร้างค่าคอมมิชชั่น การติดตามผล และกลยุทธ์เบื้องต้น พร้อมแบบฝึกหัด',
                'duration' => 45,
                'difficulty' => 'beginner',
                'course_level' => 2,
                'is_featured' => true,
                'tags' => ['Affiliate', 'ค่าคอมมิชชั่น', 'แชร์ลิงก์', 'Referral'],
                'content' => $this->generateContent('ระบบ Affiliate Marketing เบื้องต้น', $this->getCourse4Content()),
                'quiz' => $this->getCourse4Quiz(),
            ],
            [
                'title' => 'ระบบกระเป๋าเงิน (Wallet)',
                'slug' => 'thaiprompt-wallet-system',
                'excerpt' => 'เรียนรู้ระบบกระเป๋าเงินครบถ้วน ประเภทกระเป๋า การเติมเงิน การถอนเงิน ความปลอดภัย และการวิเคราะห์ธุรกรรม พร้อมแบบฝึกหัด',
                'duration' => 50,
                'difficulty' => 'beginner',
                'course_level' => 2,
                'is_featured' => false,
                'tags' => ['Wallet', 'เติมเงิน', 'ถอนเงิน', 'TPIX', 'การเงิน'],
                'content' => $this->generateContent('ระบบกระเป๋าเงิน (Wallet)', $this->getCourse5Content()),
                'quiz' => $this->getCourse5Quiz(),
            ],
            [
                'title' => 'ระบบสั่งซื้อสินค้าและชำระเงิน',
                'slug' => 'thaiprompt-order-payment',
                'excerpt' => 'เรียนรู้กระบวนการสั่งซื้อครบถ้วน การเลือกสินค้า ตะกร้า ชำระเงิน ติดตามพัสดุ และการจัดการปัญหา พร้อมแบบฝึกหัด',
                'duration' => 45,
                'difficulty' => 'beginner',
                'course_level' => 2,
                'is_featured' => false,
                'tags' => ['สั่งซื้อ', 'ชำระเงิน', 'ตะกร้า', 'Tracking', 'คืนสินค้า'],
                'content' => $this->generateContent('ระบบสั่งซื้อสินค้าและชำระเงิน', $this->getCourse6Content()),
                'quiz' => $this->getCourse6Quiz(),
            ],

            // ========== ระดับ 3: ปานกลาง (เนื้อหาละเอียดพร้อมแบบฝึกหัด) ==========
            [
                'title' => 'ระบบ MLM และโครงสร้างสายงาน',
                'slug' => 'thaiprompt-mlm-structure',
                'excerpt' => 'เข้าใจระบบ MLM อย่างลึกซึ้ง แผน Binary และ Unilevel การคำนวณโบนัส การสร้างทีม และกลยุทธ์ขยายสายงาน พร้อมแบบฝึกหัด',
                'duration' => 60,
                'difficulty' => 'intermediate',
                'course_level' => 3,
                'is_featured' => true,
                'tags' => ['MLM', 'Binary', 'Unilevel', 'ทีม', 'Genealogy', 'PV'],
                'content' => $this->generateContent('ระบบ MLM และโครงสร้างสายงาน', $this->getCourse7Content()),
                'quiz' => $this->getCourse7Quiz(),
            ],
            [
                'title' => 'ระบบ Rank และโบนัสตำแหน่ง',
                'slug' => 'thaiprompt-rank-bonus',
                'excerpt' => 'เรียนรู้ระบบ Rank ทั้งหมด เงื่อนไขการเลื่อน ประเภทโบนัส การรักษา Rank และกลยุทธ์การเลื่อนขั้น พร้อมแบบฝึกหัด',
                'duration' => 55,
                'difficulty' => 'intermediate',
                'course_level' => 3,
                'is_featured' => false,
                'tags' => ['Rank', 'ยศ', 'โบนัส', 'เลื่อนขั้น', 'Maintenance', 'Achievement'],
                'content' => $this->generateContent('ระบบ Rank และโบนัสตำแหน่ง', $this->getCourse8Content()),
                'quiz' => $this->getCourse8Quiz(),
            ],
            [
                'title' => 'ระบบ AI Bot และ Chatbot',
                'slug' => 'thaiprompt-ai-bot',
                'excerpt' => 'เรียนรู้การใช้งาน AI Bot ครบถ้วน LINE Bot Conversation Flow NLU Training และ Automation พร้อมแบบฝึกหัด',
                'duration' => 60,
                'difficulty' => 'intermediate',
                'course_level' => 3,
                'is_featured' => true,
                'tags' => ['AI', 'Chatbot', 'LINE Bot', 'Automation', 'NLU', 'Intent'],
                'content' => $this->generateContent('ระบบ AI Bot และ Chatbot', $this->getCourse9Content()),
                'quiz' => $this->getCourse9Quiz(),
            ],

            // ========== ระดับ 4: ก้าวหน้า (เนื้อหาละเอียดพร้อมแบบฝึกหัด) ==========
            [
                'title' => 'การจัดการร้านค้า E-Commerce',
                'slug' => 'thaiprompt-ecommerce-management',
                'excerpt' => 'บริหารร้านค้าออนไลน์ครบวงจร จัดการสินค้า คำสั่งซื้อ การจัดส่ง การตลาด และวิเคราะห์รายงาน พร้อมแบบฝึกหัด',
                'duration' => 65,
                'difficulty' => 'intermediate',
                'course_level' => 4,
                'is_featured' => true,
                'tags' => ['E-Commerce', 'ร้านค้า', 'สินค้า', 'การตลาด', 'Analytics', 'Shipping'],
                'content' => $this->generateContent('การจัดการร้านค้า E-Commerce', $this->getCourse10Content()),
                'quiz' => $this->getCourse10Quiz(),
            ],
            [
                'title' => 'ระบบ Crypto และ TPIX Token',
                'slug' => 'thaiprompt-crypto-tpix',
                'excerpt' => 'เรียนรู้เกี่ยวกับ Cryptocurrency, TPIX Token, Staking และ NFT',
                'duration' => 40,
                'difficulty' => 'intermediate',
                'course_level' => 4,
                'is_featured' => true,
                'tags' => ['Crypto', 'TPIX', 'Staking', 'Blockchain'],
                'content' => $this->generateContent('ระบบ Crypto และ TPIX Token', [
                    [
                        'title' => 'TPIX Token คืออะไร?',
                        'content' => "**TPIX** คือ Native Token ของ Thaiprompt Ecosystem:\n\n• **Symbol:** TPIX\n• **Network:** BSC (Binance Smart Chain)\n• **Type:** Utility Token\n\n**ใช้งานได้:**\n• ชำระค่าสินค้าและบริการ\n• Staking รับผลตอบแทน\n• ซื้อ NFT และสินค้าพิเศษ\n• แลกเป็นเงินสด\n• Governance - โหวตตัดสินใจ",
                    ],
                    [
                        'title' => 'การซื้อ/ขาย TPIX',
                        'content' => "**ช่องทางซื้อ TPIX:**\n\n1. **ในระบบ** - ซื้อด้วย Wallet Balance\n2. **DEX** - แลกบน PancakeSwap\n3. **P2P** - ซื้อจากสมาชิกอื่น\n\n**ขั้นตอน:**\n1. ไปที่ \"Crypto\" > \"TPIX\"\n2. เลือก \"ซื้อ\" หรือ \"ขาย\"\n3. ระบุจำนวน\n4. ยืนยันธุรกรรม\n5. TPIX จะเข้ากระเป๋าภายใน 1-5 นาที",
                    ],
                    [
                        'title' => 'Staking TPIX',
                        'content' => "**Staking คือการฝากเหรียญเพื่อรับผลตอบแทน:**\n\n**แพ็กเกจ Staking:**\n\n| ระยะเวลา | APY | ขั้นต่ำ |\n|----------|-----|--------|\n| 30 วัน | 12% | 100 TPIX |\n| 90 วัน | 18% | 500 TPIX |\n| 180 วัน | 24% | 1,000 TPIX |\n| 365 วัน | 36% | 5,000 TPIX |\n\n**ขั้นตอน Staking:**\n1. มี TPIX ในกระเป๋า\n2. ไปที่ \"Staking\"\n3. เลือกแพ็กเกจ\n4. ระบุจำนวน\n5. ยืนยัน\n6. รอรับผลตอบแทน",
                    ],
                ]),
                'quiz' => [
                    'time_limit' => 18,
                    'questions' => [
                        [
                            'question' => 'TPIX อยู่บน Network ใด?',
                            'answers' => [
                                ['text' => 'Ethereum', 'is_correct' => false],
                                ['text' => 'BSC (Binance Smart Chain)', 'is_correct' => true],
                                ['text' => 'Solana', 'is_correct' => false],
                                ['text' => 'Polygon', 'is_correct' => false],
                            ],
                        ],
                        [
                            'question' => 'Staking 365 วัน ได้ APY เท่าไร?',
                            'answers' => [
                                ['text' => '18%', 'is_correct' => false],
                                ['text' => '24%', 'is_correct' => false],
                                ['text' => '36%', 'is_correct' => true],
                                ['text' => '48%', 'is_correct' => false],
                            ],
                        ],
                        [
                            'question' => 'TPIX สามารถใช้ทำอะไรได้บ้าง?',
                            'answers' => [
                                ['text' => 'ชำระค่าสินค้าเท่านั้น', 'is_correct' => false],
                                ['text' => 'Staking เท่านั้น', 'is_correct' => false],
                                ['text' => 'ชำระค่าสินค้า, Staking, ซื้อ NFT, แลกเงินสด', 'is_correct' => true],
                                ['text' => 'แลกเงินสดเท่านั้น', 'is_correct' => false],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'ความปลอดภัยและ 2FA',
                'slug' => 'thaiprompt-security-2fa',
                'excerpt' => 'เรียนรู้การรักษาความปลอดภัยบัญชี การตั้งค่า 2FA และการป้องกันภัย',
                'duration' => 25,
                'difficulty' => 'intermediate',
                'course_level' => 4,
                'is_featured' => false,
                'tags' => ['Security', '2FA', 'ความปลอดภัย'],
                'content' => $this->generateContent('ความปลอดภัยและ 2FA', [
                    [
                        'title' => 'ความสำคัญของความปลอดภัย',
                        'content' => "**ทำไมต้องใส่ใจความปลอดภัย?**\n\n• บัญชีมียอดเงินและค่าคอมมิชชั่น\n• มีข้อมูลส่วนตัวและข้อมูลธนาคาร\n• มี TPIX และ Crypto\n• มีสายงานและทีมงาน\n\n**ภัยคุกคามที่พบบ่อย:**\n• Phishing - เว็บปลอม\n• Password Leak - รหัสผ่านรั่วไหล\n• Social Engineering - หลอกลวงทางสังคม\n• Malware - โปรแกรมไม่พึงประสงค์",
                    ],
                    [
                        'title' => 'การตั้งค่า 2FA',
                        'content' => "**Two-Factor Authentication (2FA):**\n\nคือการยืนยันตัวตน 2 ขั้นตอน:\n1. รหัสผ่าน (Something you know)\n2. รหัสจาก App (Something you have)\n\n**ขั้นตอนเปิด 2FA:**\n1. ไปที่ \"ตั้งค่า\" > \"ความปลอดภัย\"\n2. คลิก \"เปิดใช้งาน 2FA\"\n3. ดาวน์โหลด Authenticator App\n   • Google Authenticator\n   • Authy\n   • Microsoft Authenticator\n4. สแกน QR Code\n5. กรอกรหัส 6 หลัก\n6. บันทึก Backup Codes ไว้ที่ปลอดภัย",
                    ],
                    [
                        'title' => 'Best Practices',
                        'content' => "**แนวปฏิบัติที่ดี:**\n\n✅ ใช้รหัสผ่านยาวและซับซ้อน\n✅ เปิด 2FA เสมอ\n✅ ไม่แชร์รหัสผ่านกับใคร\n✅ ตรวจสอบ URL ก่อน Login\n✅ Logout หลังใช้งานบนเครื่องสาธารณะ\n✅ อัปเดตข้อมูลติดต่อให้เป็นปัจจุบัน\n\n❌ อย่าใช้รหัสผ่านเดียวกันทุกที่\n❌ อย่าบันทึกรหัสผ่านบน Browser สาธารณะ\n❌ อย่าคลิกลิงก์ที่น่าสงสัย",
                    ],
                ]),
                'quiz' => [
                    'time_limit' => 15,
                    'questions' => [
                        [
                            'question' => '2FA คืออะไร?',
                            'answers' => [
                                ['text' => 'รหัสผ่าน 2 ชุด', 'is_correct' => false],
                                ['text' => 'การยืนยันตัวตน 2 ขั้นตอน', 'is_correct' => true],
                                ['text' => 'บัญชี 2 อัน', 'is_correct' => false],
                                ['text' => 'อุปกรณ์ 2 เครื่อง', 'is_correct' => false],
                            ],
                        ],
                        [
                            'question' => 'Authenticator App ใดใช้สำหรับ 2FA ได้?',
                            'answers' => [
                                ['text' => 'Instagram', 'is_correct' => false],
                                ['text' => 'TikTok', 'is_correct' => false],
                                ['text' => 'Google Authenticator', 'is_correct' => true],
                                ['text' => 'Facebook', 'is_correct' => false],
                            ],
                        ],
                        [
                            'question' => 'Backup Codes ควรเก็บไว้ที่ไหน?',
                            'answers' => [
                                ['text' => 'โพสต์บน Social Media', 'is_correct' => false],
                                ['text' => 'ที่ปลอดภัยและส่วนตัว', 'is_correct' => true],
                                ['text' => 'ส่งให้เพื่อน', 'is_correct' => false],
                                ['text' => 'ไม่ต้องเก็บ', 'is_correct' => false],
                            ],
                        ],
                    ],
                ],
            ],

            // ========== ระดับ 5: ขั้นสูง ==========
            [
                'title' => 'API Integration และการพัฒนา',
                'slug' => 'thaiprompt-api-integration',
                'excerpt' => 'เรียนรู้การใช้งาน API เชื่อมต่อระบบภายนอก Webhook และการพัฒนา',
                'duration' => 45,
                'difficulty' => 'advanced',
                'course_level' => 5,
                'is_featured' => false,
                'tags' => ['API', 'Integration', 'Webhook', 'Developer'],
                'content' => $this->generateContent('API Integration และการพัฒนา', [
                    [
                        'title' => 'ภาพรวม API',
                        'content' => "**Thaiprompt API:**\n\n• **Base URL:** `https://api.thaiprompt.com/v1`\n• **Authentication:** Bearer Token\n• **Format:** JSON\n• **Rate Limit:** 60 requests/minute\n\n**API Categories:**\n• Users API - จัดการผู้ใช้\n• Orders API - จัดการคำสั่งซื้อ\n• Products API - จัดการสินค้า\n• Commissions API - ดึงข้อมูลคอมมิชชั่น\n• Wallet API - ดึงข้อมูลกระเป๋าเงิน",
                    ],
                    [
                        'title' => 'การ Authentication',
                        'content' => "**ขั้นตอนการขอ API Key:**\n\n1. ไปที่ \"ตั้งค่า\" > \"API\"\n2. คลิก \"สร้าง API Key ใหม่\"\n3. ตั้งชื่อและกำหนดสิทธิ์\n4. คัดลอก API Key (แสดงครั้งเดียว!)\n\n**การใช้งาน:**\n```\nAuthorization: Bearer YOUR_API_KEY\n```\n\n**ตัวอย่าง Request:**\n```bash\ncurl -X GET \\\n  https://api.thaiprompt.com/v1/me \\\n  -H 'Authorization: Bearer YOUR_API_KEY'\n```",
                    ],
                    [
                        'title' => 'Webhook',
                        'content' => "**Webhook คือ?**\n\nเป็นการแจ้งเตือนอัตโนมัติเมื่อมี Event เกิดขึ้น\n\n**Event ที่รองรับ:**\n• `order.created` - มีคำสั่งซื้อใหม่\n• `order.paid` - ชำระเงินแล้ว\n• `commission.received` - ได้รับค่าคอมมิชชั่น\n• `member.registered` - สมาชิกใหม่สมัคร\n\n**ตั้งค่า Webhook:**\n1. ไปที่ \"ตั้งค่า\" > \"Webhooks\"\n2. เพิ่ม Webhook URL\n3. เลือก Events ที่ต้องการ\n4. บันทึก\n\n**Security:** ทุก Webhook มี Signature Header ให้ตรวจสอบ",
                    ],
                ]),
                'quiz' => [
                    'time_limit' => 20,
                    'questions' => [
                        [
                            'question' => 'API Authentication ใช้วิธีใด?',
                            'answers' => [
                                ['text' => 'Username/Password', 'is_correct' => false],
                                ['text' => 'Bearer Token', 'is_correct' => true],
                                ['text' => 'API Key ใน URL', 'is_correct' => false],
                                ['text' => 'Cookie', 'is_correct' => false],
                            ],
                        ],
                        [
                            'question' => 'Webhook คือ?',
                            'answers' => [
                                ['text' => 'API สำหรับดึงข้อมูล', 'is_correct' => false],
                                ['text' => 'การแจ้งเตือนอัตโนมัติเมื่อมี Event', 'is_correct' => true],
                                ['text' => 'หน้าจัดการผู้ใช้', 'is_correct' => false],
                                ['text' => 'ระบบชำระเงิน', 'is_correct' => false],
                            ],
                        ],
                        [
                            'question' => 'Rate Limit ของ API คือ?',
                            'answers' => [
                                ['text' => '30 requests/minute', 'is_correct' => false],
                                ['text' => '60 requests/minute', 'is_correct' => true],
                                ['text' => '100 requests/minute', 'is_correct' => false],
                                ['text' => 'ไม่จำกัด', 'is_correct' => false],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'การบริหารทีมขนาดใหญ่',
                'slug' => 'thaiprompt-team-leadership',
                'excerpt' => 'กลยุทธ์การสร้างทีม การบริหารสายงานขนาดใหญ่ และการเป็นผู้นำที่ดี',
                'duration' => 50,
                'difficulty' => 'advanced',
                'course_level' => 5,
                'is_featured' => true,
                'tags' => ['Leadership', 'ทีม', 'กลยุทธ์', 'MLM'],
                'content' => $this->generateContent('การบริหารทีมขนาดใหญ่', [
                    [
                        'title' => 'หลักการสร้างทีมที่แข็งแกร่ง',
                        'content' => "**3 เสาหลักของทีมที่ประสบความสำเร็จ:**\n\n1. **การสรรหา (Recruiting)**\n   • หาคนที่มี Mindset ที่ใช่\n   • สอนตั้งแต่เริ่มต้นอย่างเป็นระบบ\n   • ไม่เน้นปริมาณ แต่เน้นคุณภาพ\n\n2. **การฝึกอบรม (Training)**\n   • มีระบบ Onboarding ที่ชัดเจน\n   • จัดอบรมสม่ำเสมอ\n   • ใช้เครื่องมือช่วยเรียนรู้\n\n3. **การรักษา (Retention)**\n   • สร้างวัฒนธรรมทีม\n   • ให้ Recognition และ Reward\n   • ช่วยเหลือเมื่อติดปัญหา",
                    ],
                    [
                        'title' => 'การบริหารสายงานหลายชั้น',
                        'content' => "**โครงสร้างการบริหาร:**\n\n```\n        Leader (คุณ)\n           │\n    ┌──────┼──────┐\n  Leader A    Leader B    Leader C\n    │           │           │\n  Sub-team   Sub-team   Sub-team\n```\n\n**หลักการ:**\n• สร้าง Leader ในแต่ละสาย\n• มอบหมายความรับผิดชอบ\n• ประชุมทีม Leader สม่ำเสมอ\n• ติดตามผลด้วย KPIs\n\n**KPIs ที่ควรดู:**\n• จำนวนสมาชิกใหม่/เดือน\n• Retention Rate\n• Group PV\n• Leader Development",
                    ],
                    [
                        'title' => 'การเป็นผู้นำที่ดี',
                        'content' => "**คุณสมบัติผู้นำที่ดี:**\n\n✅ **Lead by Example** - ทำให้ดูเป็นตัวอย่าง\n✅ **Good Listener** - รับฟังความคิดเห็น\n✅ **Problem Solver** - แก้ปัญหาได้\n✅ **Positive Mindset** - คิดบวก\n✅ **Continuous Learner** - เรียนรู้ตลอดเวลา\n\n**สิ่งที่ควรหลีกเลี่ยง:**\n\n❌ โทษลูกทีมเมื่อล้มเหลว\n❌ เอาเครดิตคนเดียว\n❌ ไม่รับฟังความคิดเห็น\n❌ สอนแต่ไม่ทำเอง\n❌ สื่อสารไม่ชัดเจน",
                    ],
                    [
                        'title' => 'เครื่องมือบริหารทีมใน Thaiprompt',
                        'content' => "**เครื่องมือที่มี:**\n\n📊 **Team Dashboard** - ดูสถิติทีมแบบ Real-time\n📣 **Broadcast** - ส่งข้อความถึงทั้งทีม\n📅 **Events** - จัดกิจกรรมและนัดหมาย\n🏆 **Leaderboard** - กระดานผู้นำ\n📈 **Reports** - รายงานประสิทธิภาพ\n🎯 **Goals** - ตั้งเป้าหมายทีม\n\n**Tips:** ใช้เครื่องมือเหล่านี้ร่วมกับการพบปะตัวจริงเพื่อผลลัพธ์ที่ดีที่สุด",
                    ],
                ]),
                'quiz' => [
                    'time_limit' => 20,
                    'questions' => [
                        [
                            'question' => '3 เสาหลักของทีมที่ประสบความสำเร็จคือ?',
                            'answers' => [
                                ['text' => 'ขาย, โฆษณา, ปิดการขาย', 'is_correct' => false],
                                ['text' => 'Recruiting, Training, Retention', 'is_correct' => true],
                                ['text' => 'Facebook, Instagram, TikTok', 'is_correct' => false],
                                ['text' => 'เงิน, คน, เวลา', 'is_correct' => false],
                            ],
                        ],
                        [
                            'question' => '"Lead by Example" หมายถึง?',
                            'answers' => [
                                ['text' => 'สั่งให้ลูกทีมทำ', 'is_correct' => false],
                                ['text' => 'ทำให้ดูเป็นตัวอย่าง', 'is_correct' => true],
                                ['text' => 'อ่านหนังสือ', 'is_correct' => false],
                                ['text' => 'ให้เงินลูกทีม', 'is_correct' => false],
                            ],
                        ],
                        [
                            'question' => 'KPI ใดควรดูเพื่อวัดผลทีม?',
                            'answers' => [
                                ['text' => 'จำนวน Like บน Facebook', 'is_correct' => false],
                                ['text' => 'จำนวนสมาชิกใหม่, Retention Rate, Group PV', 'is_correct' => true],
                                ['text' => 'จำนวนข้อความใน LINE', 'is_correct' => false],
                                ['text' => 'จำนวนรูปที่โพสต์', 'is_correct' => false],
                            ],
                        ],
                        [
                            'question' => 'ผู้นำที่ดีควรทำอย่างไรเมื่อทีมล้มเหลว?',
                            'answers' => [
                                ['text' => 'โทษลูกทีม', 'is_correct' => false],
                                ['text' => 'หาสาเหตุและช่วยแก้ไข', 'is_correct' => true],
                                ['text' => 'เพิกเฉย', 'is_correct' => false],
                                ['text' => 'ลาออก', 'is_correct' => false],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'กลยุทธ์การตลาดออนไลน์ขั้นสูง',
                'slug' => 'thaiprompt-advanced-marketing',
                'excerpt' => 'เรียนรู้กลยุทธ์การตลาดดิจิทัลขั้นสูง Content Marketing, Funnel และ Automation',
                'duration' => 50,
                'difficulty' => 'advanced',
                'course_level' => 5,
                'is_featured' => true,
                'tags' => ['Marketing', 'Funnel', 'Content', 'Automation'],
                'content' => $this->generateContent('กลยุทธ์การตลาดออนไลน์ขั้นสูง', [
                    [
                        'title' => 'Marketing Funnel',
                        'content' => "**Sales Funnel คือ?**\n\nเส้นทางที่นำพาผู้คนจากไม่รู้จักไปสู่การซื้อสินค้า\n\n```\n    ┌─────────────────────┐\n    │     AWARENESS       │  รู้จักแบรนด์\n    │ (เห็นโฆษณา/Content) │\n    └──────────┬──────────┘\n               ↓\n    ┌─────────────────────┐\n    │      INTEREST       │  สนใจ\n    │  (อ่าน/ดูเนื้อหา)   │\n    └──────────┬──────────┘\n               ↓\n    ┌─────────────────────┐\n    │     DECISION        │  ตัดสินใจ\n    │ (เปรียบเทียบ/ถาม)   │\n    └──────────┬──────────┘\n               ↓\n    ┌─────────────────────┐\n    │      ACTION         │  ซื้อ/สมัคร\n    │    (Conversion)     │\n    └─────────────────────┘\n```",
                    ],
                    [
                        'title' => 'Content Marketing',
                        'content' => "**ประเภท Content:**\n\n📝 **Blog/Article** - สร้างความน่าเชื่อถือ\n📹 **Video** - Engagement สูง\n🎙️ **Podcast** - สร้างความสัมพันธ์\n📸 **Infographic** - Share ง่าย\n📧 **Email** - Direct Communication\n\n**Content Calendar:**\n• วางแผนล่วงหน้า 1 เดือน\n• Mix Content Types\n• สม่ำเสมอ (เช่น 3 ครั้ง/สัปดาห์)\n• วัดผลและปรับปรุง\n\n**80/20 Rule:**\n• 80% Value Content (ให้ความรู้)\n• 20% Promotional Content (ขาย)",
                    ],
                    [
                        'title' => 'Marketing Automation',
                        'content' => "**ระบบ Automation ใน Thaiprompt:**\n\n🤖 **Auto Response** - ตอบกลับอัตโนมัติ\n📧 **Email Sequence** - ส่งอีเมลตามลำดับ\n📱 **LINE Broadcast** - ส่งข้อความอัตโนมัติ\n🎯 **Retargeting** - โฆษณาซ้ำ\n\n**ตัวอย่าง Sequence:**\n```\nDay 0: Welcome Email\nDay 2: แนะนำระบบ\nDay 5: Case Study ความสำเร็จ\nDay 7: Offer พิเศษ\n```\n\n**Tips:** ตั้ง Trigger ตาม Behavior เช่น:\n• สมัครแต่ไม่ซื้อ → ส่ง Offer\n• ซื้อแล้ว → ส่ง Upsell",
                    ],
                    [
                        'title' => 'การวัดผลการตลาด',
                        'content' => "**Metrics ที่ควรติดตาม:**\n\n📊 **Traffic Metrics:**\n• Visitors\n• Page Views\n• Bounce Rate\n• Time on Site\n\n💰 **Conversion Metrics:**\n• Conversion Rate\n• Cost per Lead (CPL)\n• Cost per Acquisition (CPA)\n• Customer Lifetime Value (CLV)\n\n🎯 **Social Metrics:**\n• Reach\n• Engagement Rate\n• Share/Save\n• Comment Sentiment\n\n**การคำนวณ ROI:**\n```\nROI = (Revenue - Cost) / Cost x 100\n```",
                    ],
                ]),
                'quiz' => [
                    'time_limit' => 20,
                    'questions' => [
                        [
                            'question' => 'Sales Funnel ขั้นตอนแรกคือ?',
                            'answers' => [
                                ['text' => 'Action', 'is_correct' => false],
                                ['text' => 'Decision', 'is_correct' => false],
                                ['text' => 'Awareness', 'is_correct' => true],
                                ['text' => 'Interest', 'is_correct' => false],
                            ],
                        ],
                        [
                            'question' => '80/20 Rule ใน Content Marketing คือ?',
                            'answers' => [
                                ['text' => '80% ขาย, 20% ให้ความรู้', 'is_correct' => false],
                                ['text' => '80% ให้ความรู้, 20% ขาย', 'is_correct' => true],
                                ['text' => '80% Video, 20% Text', 'is_correct' => false],
                                ['text' => '80% Facebook, 20% Instagram', 'is_correct' => false],
                            ],
                        ],
                        [
                            'question' => 'CPA หมายถึง?',
                            'answers' => [
                                ['text' => 'Click Per Action', 'is_correct' => false],
                                ['text' => 'Cost Per Acquisition', 'is_correct' => true],
                                ['text' => 'Content Per Article', 'is_correct' => false],
                                ['text' => 'Customer Per Account', 'is_correct' => false],
                            ],
                        ],
                        [
                            'question' => 'สูตรคำนวณ ROI คือ?',
                            'answers' => [
                                ['text' => 'Revenue + Cost', 'is_correct' => false],
                                ['text' => '(Revenue - Cost) / Cost x 100', 'is_correct' => true],
                                ['text' => 'Revenue / Cost', 'is_correct' => false],
                                ['text' => 'Cost - Revenue', 'is_correct' => false],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    // ========================================================================
    // เฟส 1: เนื้อหาระดับเริ่มต้น (คอร์ส 1-3) - เนื้อหาละเอียดพร้อมแบบฝึกหัด
    // ========================================================================

    /**
     * คอร์ส 1: รู้จัก Thaiprompt คืออะไร? (เนื้อหาละเอียด)
     */
    private function getCourse1Content(): array
    {
        return [
            [
                'title' => '🎯 Thaiprompt คืออะไร?',
                'content' => "**Thaiprompt** เป็นแพลตฟอร์ม **All-in-One Business Platform** ที่พัฒนาโดยคนไทย เพื่อคนไทย รวมเครื่องมือธุรกิจออนไลน์ครบวงจรไว้ในที่เดียว

**ระบบหลักที่รวมอยู่ใน Thaiprompt:**

• **MLM System** - ระบบเครือข่ายหลายชั้น รองรับทั้ง Binary และ Unilevel
• **Affiliate Marketing** - ระบบพันธมิตรแนะนำสินค้า รับค่าคอมมิชชั่น
• **E-Commerce** - ร้านค้าออนไลน์ครบวงจร จัดการสินค้า คำสั่งซื้อ สต๊อก
• **AI Bot Platform** - สร้าง Chatbot อัจฉริยะ รองรับ LINE, Facebook
• **Crypto & TPIX Token** - ระบบ Token บน Blockchain
• **Hotel Booking** - ระบบจองโรงแรมและที่พัก
• **POS System** - ระบบขายหน้าร้าน
• **HRM** - ระบบบริหารทรัพยากรบุคคล
• **Food Passport** - ระบบตรวจสอบย้อนกลับอาหาร

**ทำไมต้อง Thaiprompt?**

1. **ประหยัดค่าใช้จ่าย** - ไม่ต้องซื้อหลายระบบแยกกัน
2. **ใช้งานง่าย** - ออกแบบ UI/UX สำหรับคนไทย
3. **รองรับภาษาไทย** - ทุกส่วนเป็นภาษาไทย 100%
4. **Support ตลอด 24 ชม.** - ทีมงานคนไทยพร้อมช่วยเหลือ
5. **ราคาเข้าถึงได้** - เหมาะสำหรับ SME และผู้เริ่มต้น",
            ],
            [
                'title' => '📜 ประวัติและความเป็นมา',
                'content' => "**จุดเริ่มต้นของ Thaiprompt**

Thaiprompt เริ่มต้นจากความต้องการแก้ปัญหาที่ธุรกิจไทยเผชิญ:

• ระบบต่างประเทศ **ราคาแพง** และ **ไม่รองรับภาษาไทย**
• ต้องใช้ **หลายระบบ** ทำให้ข้อมูลกระจัดกระจาย
• **ขาดการ Support** ที่เข้าใจบริบทธุรกิจไทย
• ระบบ MLM ส่วนใหญ่ **ไม่ถูกกฎหมาย** หรือ **ไม่โปร่งใส**

**Timeline สำคัญ:**

• **2022** - เริ่มพัฒนา Core Platform
• **2023** - เปิดตัว MLM + Affiliate System
• **2024** - เพิ่ม AI Bot, E-Commerce, Crypto
• **2025** - ขยายสู่ Hotel Booking, Food Passport

**ทีมพัฒนา:**

• นักพัฒนาซอฟต์แวร์ชาวไทย 20+ คน
• ผู้เชี่ยวชาญด้าน MLM และ Affiliate
• ทีม Support คนไทย 24/7",
            ],
            [
                'title' => '🌟 วิสัยทัศน์และพันธกิจ',
                'content' => "**วิสัยทัศน์ (Vision)**

\"เป็นแพลตฟอร์มธุรกิจดิจิทัลชั้นนำของประเทศไทย ที่ช่วยให้ทุกคนสามารถเริ่มต้นและเติบโตในธุรกิจออนไลน์ได้อย่างยั่งยืน\"

**พันธกิจ (Mission)**

1. **Accessibility** - ทำให้เทคโนโลยีเข้าถึงได้สำหรับทุกคน
2. **Simplicity** - ออกแบบให้ใช้งานง่าย ไม่ต้องมีความรู้เทคนิค
3. **Transparency** - โปร่งใส ตรวจสอบได้ทุกธุรกรรม
4. **Community** - สร้างชุมชนนักธุรกิจที่แข็งแกร่ง
5. **Innovation** - นำเทคโนโลยีใหม่ๆ มาพัฒนาต่อเนื่อง

**ค่านิยมหลัก (Core Values)**

• **ซื่อสัตย์** - ทำธุรกิจอย่างโปร่งใส
• **ใส่ใจ** - ดูแลลูกค้าเหมือนครอบครัว
• **มุ่งมั่น** - พัฒนาไม่หยุดยั้ง
• **แบ่งปัน** - ความสำเร็จต้องแบ่งปันกัน",
            ],
            [
                'title' => '💎 ประโยชน์ที่คุณจะได้รับ',
                'content' => "**สำหรับผู้เริ่มต้นธุรกิจ:**

• ได้ระบบพร้อมใช้งานทันที ไม่ต้องพัฒนาเอง
• มี Mentor และชุมชนคอยช่วยเหลือ
• เริ่มต้นด้วยเงินลงทุนน้อย
• เรียนรู้จากคอร์สฟรีใน Academy

**สำหรับเจ้าของธุรกิจ:**

• รวมทุกระบบไว้ในที่เดียว ง่ายต่อการจัดการ
• ข้อมูลเชื่อมต่อกันอัตโนมัติ
• รายงานครบถ้วน ตัดสินใจได้ดีขึ้น
• ประหยัดค่าใช้จ่ายระบบต่างๆ

**สำหรับ Affiliate:**

• ค่าคอมมิชชั่นสูงถึง 40%
• ระบบติดตามอัตโนมัติ
• ถอนเงินได้ทุกวัน
• สร้างทีมได้ไม่จำกัด

**สำหรับนักพัฒนา:**

• API ครบถ้วน สำหรับ Integration
• Webhook สำหรับ Real-time events
• Documentation ภาษาไทย
• Developer Community",
            ],
            [
                'title' => '📝 แบบฝึกหัดท้ายบท',
                'content' => "**แบบฝึกหัดที่ 1: สำรวจตัวเอง**

ตอบคำถามต่อไปนี้ (เขียนลงสมุดหรือโน้ต):

1. คุณต้องการใช้ Thaiprompt เพื่ออะไร?
   • เริ่มธุรกิจใหม่
   • ขยายธุรกิจเดิม
   • หารายได้เสริม
   • อื่นๆ ระบุ...

2. ระบบใดใน Thaiprompt ที่คุณสนใจมากที่สุด 3 อันดับแรก?

3. เป้าหมายรายได้ของคุณใน 6 เดือนแรกคือเท่าไหร่?

**แบบฝึกหัดที่ 2: ศึกษาเพิ่มเติม**

1. เข้าไปดูหน้าแรกของ Thaiprompt
2. อ่าน FAQ อย่างน้อย 5 ข้อ
3. ดูวิดีโอแนะนำระบบ (ถ้ามี)

**แบบฝึกหัดที่ 3: วางแผนเบื้องต้น**

เขียนแผนธุรกิจง่ายๆ 1 หน้า:
• ธุรกิจของคุณคืออะไร?
• กลุ่มลูกค้าเป้าหมายคือใคร?
• จะใช้ระบบใดของ Thaiprompt บ้าง?
• เป้าหมาย 3 เดือน, 6 เดือน, 1 ปี",
            ],
        ];
    }

    /**
     * คอร์ส 1: Quiz (เพิ่มคำถามครบถ้วน)
     */
    private function getCourse1Quiz(): array
    {
        return [
            'time_limit' => 15,
            'description' => 'ทดสอบความเข้าใจเกี่ยวกับโครงการ Thaiprompt',
            'questions' => [
                [
                    'question' => 'Thaiprompt คือแพลตฟอร์มประเภทใด?',
                    'answers' => [
                        ['text' => 'แพลตฟอร์มเกมออนไลน์', 'is_correct' => false],
                        ['text' => 'แพลตฟอร์ม All-in-One Business Platform', 'is_correct' => true],
                        ['text' => 'แพลตฟอร์มสื่อสังคมออนไลน์', 'is_correct' => false],
                        ['text' => 'แพลตฟอร์มสตรีมมิ่งวิดีโอ', 'is_correct' => false],
                    ],
                    'explanation' => 'Thaiprompt เป็น All-in-One Business Platform ที่รวมระบบ MLM, Affiliate, E-Commerce, AI Bot และอื่นๆ ไว้ในที่เดียว',
                ],
                [
                    'question' => 'ระบบใดต่อไปนี้ไม่ได้อยู่ใน Thaiprompt?',
                    'answers' => [
                        ['text' => 'MLM System', 'is_correct' => false],
                        ['text' => 'AI Bot Platform', 'is_correct' => false],
                        ['text' => 'Video Streaming Platform', 'is_correct' => true],
                        ['text' => 'E-Commerce', 'is_correct' => false],
                    ],
                    'explanation' => 'Thaiprompt มีระบบ MLM, AI Bot, E-Commerce แต่ไม่มี Video Streaming Platform',
                ],
                [
                    'question' => 'TPIX Token คืออะไร?',
                    'answers' => [
                        ['text' => 'แต้มสะสมทั่วไป', 'is_correct' => false],
                        ['text' => 'Token บน Blockchain ของ Thaiprompt', 'is_correct' => true],
                        ['text' => 'คูปองส่วนลด', 'is_correct' => false],
                        ['text' => 'บัตรสมาชิก', 'is_correct' => false],
                    ],
                    'explanation' => 'TPIX Token เป็น Cryptocurrency บน Blockchain ที่ใช้ในระบบ Thaiprompt',
                ],
                [
                    'question' => 'ข้อใดเป็นค่านิยมหลักของ Thaiprompt?',
                    'answers' => [
                        ['text' => 'กำไรสูงสุด', 'is_correct' => false],
                        ['text' => 'ซื่อสัตย์ ใส่ใจ มุ่งมั่น แบ่งปัน', 'is_correct' => true],
                        ['text' => 'แข่งขันเอาชนะ', 'is_correct' => false],
                        ['text' => 'ทำคนเดียวไม่พึ่งใคร', 'is_correct' => false],
                    ],
                    'explanation' => 'ค่านิยมหลักของ Thaiprompt คือ ซื่อสัตย์ ใส่ใจ มุ่งมั่น และแบ่งปัน',
                ],
                [
                    'question' => 'Thaiprompt เริ่มพัฒนา Core Platform ในปีใด?',
                    'answers' => [
                        ['text' => '2020', 'is_correct' => false],
                        ['text' => '2021', 'is_correct' => false],
                        ['text' => '2022', 'is_correct' => true],
                        ['text' => '2023', 'is_correct' => false],
                    ],
                    'explanation' => 'Thaiprompt เริ่มพัฒนา Core Platform ในปี 2022',
                ],
                [
                    'question' => 'ค่าคอมมิชชั่น Affiliate สูงสุดของ Thaiprompt คือเท่าไหร่?',
                    'answers' => [
                        ['text' => '10%', 'is_correct' => false],
                        ['text' => '20%', 'is_correct' => false],
                        ['text' => '30%', 'is_correct' => false],
                        ['text' => '40%', 'is_correct' => true],
                    ],
                    'explanation' => 'ค่าคอมมิชชั่น Affiliate ของ Thaiprompt สูงสุดถึง 40%',
                ],
                [
                    'question' => 'ข้อใดไม่ใช่ประโยชน์ของ Thaiprompt?',
                    'answers' => [
                        ['text' => 'รวมหลายระบบไว้ในที่เดียว', 'is_correct' => false],
                        ['text' => 'รองรับภาษาไทย 100%', 'is_correct' => false],
                        ['text' => 'ต้องมีความรู้เขียนโปรแกรมขั้นสูง', 'is_correct' => true],
                        ['text' => 'มี API สำหรับนักพัฒนา', 'is_correct' => false],
                    ],
                    'explanation' => 'Thaiprompt ออกแบบให้ใช้งานง่าย ไม่จำเป็นต้องมีความรู้เขียนโปรแกรม',
                ],
            ],
        ];
    }

    /**
     * คอร์ส 2: การสมัครสมาชิก Thaiprompt (เนื้อหาละเอียด)
     */
    private function getCourse2Content(): array
    {
        return [
            [
                'title' => '🚀 ช่องทางการสมัครสมาชิก',
                'content' => "**Thaiprompt มี 3 ช่องทางหลักในการสมัครสมาชิก:**

**1. สมัครผ่านเว็บไซต์ (แนะนำ)**
• เหมาะสำหรับผู้ที่ต้องการกรอกข้อมูลครบถ้วน
• สามารถอัปโหลดเอกสารยืนยันตัวตนได้ทันที
• รองรับการสมัครผ่าน Desktop และ Mobile

**2. สมัครผ่าน LINE Official Account**
• สะดวก รวดเร็ว สมัครผ่าน Chat
• บัญชีเชื่อมกับ LINE อัตโนมัติ
• รับแจ้งเตือนผ่าน LINE ได้ทันที

**3. สมัครผ่าน Referral Link**
• เมื่อมีคนแนะนำ จะได้รับ Link พิเศษ
• ระบบจะบันทึกผู้แนะนำอัตโนมัติ
• อาจได้รับโบนัสต้อนรับพิเศษ

**ข้อมูลที่ต้องเตรียม:**
• อีเมลที่ใช้งานได้จริง (สำหรับยืนยัน)
• เบอร์โทรศัพท์มือถือ (รับ OTP)
• บัตรประชาชน (สำหรับ KYC)
• สมุดบัญชีธนาคาร (สำหรับรับเงิน)",
            ],
            [
                'title' => '💻 ขั้นตอนการสมัครผ่านเว็บไซต์',
                'content' => "**ขั้นตอนที่ 1: เข้าหน้าสมัครสมาชิก**
• ไปที่ thaiprompt.com
• คลิกปุ่ม \"สมัครสมาชิก\" ที่มุมขวาบน
• หรือคลิก \"เริ่มต้นใช้งานฟรี\"

**ขั้นตอนที่ 2: กรอกข้อมูลส่วนตัว**
• ชื่อ-นามสกุล (ตรงกับบัตรประชาชน)
• อีเมล (ต้องยืนยันได้)
• เบอร์โทรศัพท์ (10 หลัก)
• สร้างรหัสผ่าน (อย่างน้อย 8 ตัวอักษร มีตัวเลขและอักขระพิเศษ)

**ขั้นตอนที่ 3: ยืนยันอีเมล**
• ระบบจะส่ง Email ไปยังอีเมลที่ลงทะเบียน
• คลิกลิงก์ยืนยันในอีเมล (ภายใน 24 ชั่วโมง)
• หากไม่พบ ให้ตรวจสอบ Spam folder

**ขั้นตอนที่ 4: ยืนยัน OTP**
• ระบบจะส่ง SMS ไปยังเบอร์โทรที่ลงทะเบียน
• กรอก OTP 6 หลัก
• มีเวลา 5 นาที ก่อนหมดอายุ

**ขั้นตอนที่ 5: เสร็จสิ้น!**
• เข้าสู่ระบบด้วยอีเมลและรหัสผ่าน
• ตั้งค่าโปรไฟล์เพิ่มเติม
• เริ่มใช้งานได้ทันที

**Tips:**
• ใช้ Email จริงที่เช็คบ่อย
• จดรหัสผ่านเก็บไว้ในที่ปลอดภัย
• เปิดใช้งาน 2FA ทันทีหลังสมัคร",
            ],
            [
                'title' => '📱 ขั้นตอนการสมัครผ่าน LINE',
                'content' => "**ขั้นตอนที่ 1: เพิ่มเพื่อน LINE OA**
• ค้นหา @thaiprompt ใน LINE
• หรือสแกน QR Code จากเว็บไซต์
• กดปุ่ม \"เพิ่มเพื่อน\"

**ขั้นตอนที่ 2: เริ่มการสมัคร**
• พิมพ์ \"สมัคร\" หรือ \"register\"
• หรือกดเมนู \"สมัครสมาชิก\" ด้านล่าง

**ขั้นตอนที่ 3: กรอกข้อมูลผ่าน Chat**
Bot จะถามข้อมูลทีละขั้น:
• \"กรุณาพิมพ์ชื่อ-นามสกุลของคุณ\"
• \"กรุณาพิมพ์อีเมลของคุณ\"
• \"กรุณาพิมพ์เบอร์โทรศัพท์\"

**ขั้นตอนที่ 4: ยืนยัน OTP**
• Bot จะส่ง OTP มาทาง SMS
• พิมพ์ OTP กลับไปใน Chat

**ขั้นตอนที่ 5: ตั้งรหัสผ่าน**
• คลิกลิงก์ที่ Bot ส่งมา
• ตั้งรหัสผ่านสำหรับเข้าเว็บ

**ข้อดีของการสมัครผ่าน LINE:**
• สะดวก รวดเร็ว ไม่ต้องกรอกฟอร์มยาว
• บัญชีเชื่อมกับ LINE อัตโนมัติ
• รับแจ้งเตือนทุกกิจกรรมผ่าน LINE
• สามารถสั่งซื้อสินค้าผ่าน LINE ได้เลย",
            ],
            [
                'title' => '✅ การยืนยันตัวตน (KYC)',
                'content' => "**KYC คืออะไร?**
KYC (Know Your Customer) คือกระบวนการยืนยันตัวตน เพื่อความปลอดภัยและป้องกันการฉ้อโกง

**ทำไมต้องทำ KYC?**
• ปลดล็อคการถอนเงินไม่จำกัด
• เข้าถึงฟีเจอร์ Premium
• สร้างความน่าเชื่อถือในระบบ
• ป้องกันการสวมรอยบัญชี

**เอกสารที่ต้องใช้:**

**1. บัตรประชาชน**
• ถ่ายรูปด้านหน้าชัดเจน
• เห็นหน้า ชื่อ เลขบัตรครบ
• ไม่หมดอายุ

**2. รูปถ่ายคู่บัตร (Selfie)**
• ถือบัตรประชาชนข้างหน้า
• เห็นหน้าและบัตรชัดเจน
• ไม่สวมแว่นกันแดด หมวก

**3. สมุดบัญชีธนาคาร**
• หน้าที่มีชื่อและเลขบัญชี
• ชื่อต้องตรงกับบัตรประชาชน

**ระดับ KYC:**

| ระดับ | เอกสาร | วงเงินถอน/วัน |
|-------|--------|---------------|
| Level 1 | อีเมล+เบอร์โทร | 1,000 บาท |
| Level 2 | +บัตรประชาชน | 50,000 บาท |
| Level 3 | +Selfie+บัญชี | ไม่จำกัด |

**ระยะเวลาอนุมัติ:**
• Level 1: ทันที (อัตโนมัติ)
• Level 2: 1-24 ชั่วโมง
• Level 3: 1-3 วันทำการ",
            ],
            [
                'title' => '⚙️ การตั้งค่าโปรไฟล์และความปลอดภัย',
                'content' => "**การตั้งค่าโปรไฟล์ที่แนะนำ:**

**1. อัปโหลดรูปโปรไฟล์**
• ใช้รูปหน้าตรง ชัดเจน
• แนะนำขนาด 500x500 px
• รูปแบบ JPG หรือ PNG
• สร้างความน่าเชื่อถือให้ทีมงาน

**2. กรอกข้อมูลส่วนตัว**
• ที่อยู่ (สำหรับจัดส่งสินค้า)
• วันเกิด (รับของขวัญวันเกิด)
• เพศ (สำหรับโปรโมชั่น)

**3. เพิ่มบัญชีธนาคาร**
• สำหรับรับค่าคอมมิชชั่น
• ต้องเป็นบัญชีออมทรัพย์
• ชื่อต้องตรงกับบัญชี Thaiprompt

**การตั้งค่าความปลอดภัย:**

**1. เปิดใช้งาน 2FA (Two-Factor Authentication)**
• ไปที่ ตั้งค่า > ความปลอดภัย > 2FA
• เลือก Google Authenticator หรือ SMS
• สแกน QR Code ด้วย App
• กรอก OTP เพื่อยืนยัน

**2. ตั้งค่ารหัส PIN**
• ใช้สำหรับยืนยันการถอนเงิน
• PIN 6 หลัก
• ห้ามใช้เลขง่ายๆ เช่น 123456

**3. การแจ้งเตือน**
• เปิดแจ้งเตือน Login ใหม่
• เปิดแจ้งเตือนการถอนเงิน
• เปิดแจ้งเตือนการเปลี่ยนรหัสผ่าน

**4. อุปกรณ์ที่เชื่อมต่อ**
• ตรวจสอบอุปกรณ์ที่ Login อยู่
• ลบอุปกรณ์ที่ไม่รู้จัก
• ออกจากระบบอุปกรณ์เก่า",
            ],
            [
                'title' => '📝 แบบฝึกหัดท้ายบท',
                'content' => "**แบบฝึกหัดที่ 1: สมัครสมาชิก**

ถ้ายังไม่ได้สมัคร ให้ลงมือสมัครเลย!

1. เลือกช่องทางที่สะดวก (เว็บ หรือ LINE)
2. กรอกข้อมูลตามขั้นตอน
3. ยืนยัน Email และ OTP
4. เข้าสู่ระบบครั้งแรก

**แบบฝึกหัดที่ 2: ตั้งค่าโปรไฟล์**

หลังสมัครสมาชิกแล้ว:

1. อัปโหลดรูปโปรไฟล์
2. กรอกที่อยู่ให้ครบถ้วน
3. เพิ่มบัญชีธนาคาร
4. ตรวจสอบข้อมูลให้ถูกต้อง

**แบบฝึกหัดที่ 3: เปิด 2FA**

ทำตามขั้นตอนเพื่อเพิ่มความปลอดภัย:

1. ดาวน์โหลด Google Authenticator
2. ไปที่ ตั้งค่า > ความปลอดภัย > 2FA
3. สแกน QR Code
4. กรอก OTP ยืนยัน
5. บันทึก Backup Codes เก็บไว้

**แบบฝึกหัดที่ 4: ทำ KYC Level 2**

ถ่ายรูปบัตรประชาชน:
1. วางบัตรบนพื้นขาว
2. ถ่ายในที่แสงสว่าง
3. ตรวจสอบความชัดเจน
4. อัปโหลดในระบบ
5. รอการอนุมัติ

**Checklist:**
☐ สมัครสมาชิกสำเร็จ
☐ ยืนยัน Email
☐ ยืนยัน OTP
☐ อัปโหลดรูปโปรไฟล์
☐ กรอกที่อยู่
☐ เพิ่มบัญชีธนาคาร
☐ เปิด 2FA
☐ ทำ KYC Level 2",
            ],
        ];
    }

    /**
     * คอร์ส 2: Quiz
     */
    private function getCourse2Quiz(): array
    {
        return [
            'time_limit' => 15,
            'description' => 'ทดสอบความเข้าใจเกี่ยวกับการสมัครสมาชิก',
            'questions' => [
                [
                    'question' => 'Thaiprompt มีกี่ช่องทางหลักในการสมัครสมาชิก?',
                    'answers' => [
                        ['text' => '1 ช่องทาง', 'is_correct' => false],
                        ['text' => '2 ช่องทาง', 'is_correct' => false],
                        ['text' => '3 ช่องทาง', 'is_correct' => true],
                        ['text' => '4 ช่องทาง', 'is_correct' => false],
                    ],
                    'explanation' => 'มี 3 ช่องทาง: เว็บไซต์, LINE OA, และ Referral Link',
                ],
                [
                    'question' => 'รหัสผ่านสำหรับสมัครสมาชิกต้องมีความยาวอย่างน้อยกี่ตัวอักษร?',
                    'answers' => [
                        ['text' => '6 ตัวอักษร', 'is_correct' => false],
                        ['text' => '8 ตัวอักษร', 'is_correct' => true],
                        ['text' => '10 ตัวอักษร', 'is_correct' => false],
                        ['text' => '12 ตัวอักษร', 'is_correct' => false],
                    ],
                    'explanation' => 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร พร้อมตัวเลขและอักขระพิเศษ',
                ],
                [
                    'question' => 'KYC ย่อมาจากอะไร?',
                    'answers' => [
                        ['text' => 'Keep Your Cash', 'is_correct' => false],
                        ['text' => 'Know Your Customer', 'is_correct' => true],
                        ['text' => 'Key Your Code', 'is_correct' => false],
                        ['text' => 'Keep Your Credit', 'is_correct' => false],
                    ],
                    'explanation' => 'KYC ย่อมาจาก Know Your Customer คือกระบวนการยืนยันตัวตน',
                ],
                [
                    'question' => 'KYC Level 3 ต้องใช้เอกสารอะไรบ้าง?',
                    'answers' => [
                        ['text' => 'เฉพาะบัตรประชาชน', 'is_correct' => false],
                        ['text' => 'บัตรประชาชน + Selfie', 'is_correct' => false],
                        ['text' => 'บัตรประชาชน + Selfie + สมุดบัญชี', 'is_correct' => true],
                        ['text' => 'ไม่ต้องใช้เอกสาร', 'is_correct' => false],
                    ],
                    'explanation' => 'KYC Level 3 ต้องใช้ บัตรประชาชน, Selfie คู่บัตร, และสมุดบัญชีธนาคาร',
                ],
                [
                    'question' => '2FA คืออะไร?',
                    'answers' => [
                        ['text' => 'ระบบชำระเงิน 2 ช่องทาง', 'is_correct' => false],
                        ['text' => 'ระบบยืนยันตัวตน 2 ขั้นตอน', 'is_correct' => true],
                        ['text' => 'ระบบสมัคร 2 ครั้ง', 'is_correct' => false],
                        ['text' => 'ระบบแบ่งเงิน 2 บัญชี', 'is_correct' => false],
                    ],
                    'explanation' => '2FA (Two-Factor Authentication) คือระบบยืนยันตัวตน 2 ขั้นตอน เพิ่มความปลอดภัย',
                ],
                [
                    'question' => 'OTP มีอายุการใช้งานกี่นาที?',
                    'answers' => [
                        ['text' => '1 นาที', 'is_correct' => false],
                        ['text' => '3 นาที', 'is_correct' => false],
                        ['text' => '5 นาที', 'is_correct' => true],
                        ['text' => '10 นาที', 'is_correct' => false],
                    ],
                    'explanation' => 'OTP มีอายุ 5 นาที ก่อนหมดอายุ',
                ],
                [
                    'question' => 'วงเงินถอน/วัน ของ KYC Level 2 คือเท่าไหร่?',
                    'answers' => [
                        ['text' => '1,000 บาท', 'is_correct' => false],
                        ['text' => '10,000 บาท', 'is_correct' => false],
                        ['text' => '50,000 บาท', 'is_correct' => true],
                        ['text' => 'ไม่จำกัด', 'is_correct' => false],
                    ],
                    'explanation' => 'KYC Level 2 มีวงเงินถอน 50,000 บาท/วัน',
                ],
            ],
        ];
    }

    /**
     * คอร์ส 3: หน้า Dashboard และการนำทาง (เนื้อหาละเอียด)
     */
    private function getCourse3Content(): array
    {
        return [
            [
                'title' => '🏠 ภาพรวมหน้า Dashboard',
                'content' => "**Dashboard คืออะไร?**

Dashboard คือหน้าแรกที่คุณจะเห็นหลังจาก Login เข้าระบบ เป็นศูนย์รวมข้อมูลสำคัญทั้งหมดของบัญชีคุณ

**ส่วนประกอบหลักของ Dashboard:**

**1. Header Bar (แถบบนสุด)**
• โลโก้ Thaiprompt - คลิกเพื่อกลับ Dashboard
• Search Box - ค้นหาทุกอย่างในระบบ
• Notification Bell - การแจ้งเตือนใหม่
• Profile Menu - ตั้งค่า, ออกจากระบบ

**2. Summary Cards (การ์ดสรุปข้อมูล)**
• ยอดเงินในกระเป๋า (Wallet Balance)
• ยอดขายวันนี้ (Today Sales)
• จำนวนสมาชิกในทีม (Team Members)
• ค่าคอมมิชชั่นเดือนนี้ (This Month Commission)

**3. Quick Actions (ปุ่มลัด)**
• ฝากเงิน / ถอนเงิน
• สร้าง Referral Link
• สั่งซื้อสินค้า
• ดูรายงาน

**4. Charts & Graphs (กราฟและแผนภูมิ)**
• กราฟรายได้ 7 วันย้อนหลัง
• แผนภูมิสัดส่วนรายได้
• กราฟการเติบโตของทีม

**5. Recent Activities (กิจกรรมล่าสุด)**
• ออเดอร์ใหม่
• สมาชิกใหม่ในทีม
• การถอนเงิน
• การแจ้งเตือนสำคัญ",
            ],
            [
                'title' => '📊 Widget และข้อมูลสรุป',
                'content' => "**การอ่าน Widget แต่ละประเภท:**

**💰 Wallet Balance (ยอดเงิน)**
• **Available** - เงินที่ถอนได้ทันที
• **Pending** - เงินที่รอดำเนินการ
• **Total** - ยอดรวมทั้งหมด
• คลิกที่ Widget เพื่อไปหน้า Wallet

**📈 Sales Summary (ยอดขาย)**
• **Today** - ยอดขายวันนี้
• **This Week** - ยอดขายสัปดาห์นี้
• **This Month** - ยอดขายเดือนนี้
• เปรียบเทียบกับช่วงเดียวกันที่ผ่านมา (%)

**👥 Team Overview (ทีมงาน)**
• **Direct** - สมาชิกตรง (Level 1)
• **Total** - สมาชิกทั้งหมดทุก Level
• **Active** - สมาชิกที่ Active เดือนนี้
• **New This Month** - สมาชิกใหม่เดือนนี้

**🏆 Rank Progress (ความคืบหน้า Rank)**
• Rank ปัจจุบันของคุณ
• Progress bar ไปยัง Rank ถัดไป
• เงื่อนไขที่ยังขาดอยู่
• โบนัสที่จะได้รับเมื่อ Rank Up

**🔔 Notification Badge**
• ตัวเลขสีแดง = จำนวนการแจ้งเตือนใหม่
• จุดสีน้ำเงิน = มีข้อความยังไม่อ่าน
• ไม่มี Badge = ไม่มีอะไรใหม่",
            ],
            [
                'title' => '📱 เมนูหลักในระบบ',
                'content' => "**เมนูหลัก (Main Navigation):**

**🏠 หน้าหลัก (Dashboard)**
• ภาพรวมบัญชี
• สถิติสำคัญ
• กิจกรรมล่าสุด

**💰 กระเป๋าเงิน (Wallet)**
• ยอดเงินทั้งหมด
• ประวัติธุรกรรม
• ฝากเงิน / ถอนเงิน
• โอนเงินภายใน

**👥 ทีมงาน (Team)**
• สายงาน (Genealogy)
• รายชื่อสมาชิก
• ผลงานทีม
• สถิติการเติบโต

**🛒 ร้านค้า (Shop)**
• สินค้าทั้งหมด
• ตะกร้าสินค้า
• ประวัติการสั่งซื้อ
• ติดตามพัสดุ

**📊 รายงาน (Reports)**
• รายงานค่าคอมมิชชั่น
• รายงานยอดขาย
• รายงาน PV/BV
• รายงาน Rank

**🎓 Academy**
• คอร์สเรียน
• บทความความรู้
• วิดีโอสอนการใช้งาน
• ใบประกาศนียบัตร

**⚙️ ตั้งค่า (Settings)**
• โปรไฟล์ส่วนตัว
• ความปลอดภัย
• การแจ้งเตือน
• ธีม (Light/Dark)

**เมนูเสริม (สำหรับ Seller/Admin):**
• 📦 จัดการสินค้า
• 📋 จัดการคำสั่งซื้อ
• 📢 โปรโมชั่น
• 📈 Analytics",
            ],
            [
                'title' => '🔍 การค้นหาและกรองข้อมูล',
                'content' => "**การใช้งาน Search Box:**

**Global Search (ค้นหาทั่วไป)**
• กด `Ctrl + K` หรือ `/` เพื่อเปิด Search
• พิมพ์คำค้นหา แล้ว Enter
• ผลลัพธ์แบ่งตามประเภท (สมาชิก, สินค้า, คำสั่งซื้อ)

**การค้นหาในหน้ารายการ:**
• ช่อง Search อยู่มุมขวาบนของตาราง
• พิมพ์แล้วผลลัพธ์จะ Filter อัตโนมัติ
• ค้นหาได้ทุกคอลัมน์

**การใช้งาน Filter:**

**1. Filter Dropdown**
• คลิกปุ่ม \"Filter\" หรือ \"กรอง\"
• เลือกเงื่อนไข (วันที่, สถานะ, ประเภท)
• คลิก \"Apply\" เพื่อใช้งาน

**2. Date Range Filter**
• เลือกวันที่เริ่มต้น - วันที่สิ้นสุด
• มี Preset: วันนี้, 7 วัน, 30 วัน, เดือนนี้

**3. Status Filter**
• Pending - รอดำเนินการ
• Approved - อนุมัติแล้ว
• Completed - เสร็จสิ้น
• Cancelled - ยกเลิก

**การเรียงลำดับ (Sorting):**
• คลิกหัวคอลัมน์เพื่อเรียงลำดับ
• คลิกอีกครั้งเพื่อสลับ (น้อย→มาก หรือ มาก→น้อย)
• ไอคอน ▲ = A-Z / น้อย→มาก
• ไอคอน ▼ = Z-A / มาก→น้อย

**การ Export ข้อมูล:**
• คลิกปุ่ม \"Export\" หรือ \"ส่งออก\"
• เลือกรูปแบบ: Excel (.xlsx) หรือ CSV
• ระบบจะดาวน์โหลดไฟล์อัตโนมัติ",
            ],
            [
                'title' => '🎨 การปรับแต่ง Dashboard',
                'content' => "**การเปลี่ยนธีม (Theme):**

**Light Mode (สว่าง)**
• พื้นหลังสีขาว
• เหมาะสำหรับใช้งานกลางวัน
• ประหยัดแบตน้อยกว่า (หน้าจอ LCD)

**Dark Mode (มืด)**
• พื้นหลังสีเข้ม
• ลดความเมื่อยล้าตอนกลางคืน
• ประหยัดแบตเตอรี่ (หน้าจอ OLED)

**วิธีเปลี่ยนธีม:**
1. คลิกรูปโปรไฟล์ที่มุมขวาบน
2. คลิกไอคอน 🌙/☀️
3. หรือไปที่ ตั้งค่า > ธีม

**การจัดเรียง Widget:**

บาง Dashboard รองรับการปรับแต่ง:
• ลาก Widget ไปวางตำแหน่งใหม่
• ซ่อน/แสดง Widget ที่ต้องการ
• เปลี่ยนขนาด Widget

**การตั้งค่าหน้าแรก:**
• เลือกหน้าเริ่มต้นหลัง Login
• ตั้งค่า Default filters
• เลือก Widget ที่แสดงบน Dashboard

**Keyboard Shortcuts:**
• `Ctrl + K` - เปิด Search
• `Ctrl + /` - แสดง Shortcuts ทั้งหมด
• `Esc` - ปิด Popup/Modal
• `←` `→` - เปลี่ยนหน้าในตาราง

**Mobile vs Desktop:**
• บน Mobile เมนูจะซ่อนในปุ่ม ☰
• Widget จะเรียงเป็นคอลัมน์เดียว
• ใช้ Swipe เพื่อนำทาง",
            ],
            [
                'title' => '📝 แบบฝึกหัดท้ายบท',
                'content' => "**แบบฝึกหัดที่ 1: สำรวจ Dashboard**

Login เข้าระบบแล้วตอบคำถามต่อไปนี้:

1. ยอดเงินในกระเป๋าของคุณเป็นเท่าไหร่?
2. มีการแจ้งเตือนกี่รายการ?
3. Rank ปัจจุบันของคุณคืออะไร?
4. กิจกรรมล่าสุดคืออะไร?

**แบบฝึกหัดที่ 2: ทดลองใช้เมนู**

ลองเข้าไปในแต่ละเมนูและบันทึก:

1. เมนู กระเป๋าเงิน - มีกี่แท็บย่อย?
2. เมนู ทีมงาน - คุณมีสมาชิกตรงกี่คน?
3. เมนู ร้านค้า - สินค้ายอดนิยมคืออะไร?
4. เมนู รายงาน - มีรายงานกี่ประเภท?

**แบบฝึกหัดที่ 3: ทดลอง Search และ Filter**

1. ใช้ Search หาคำว่า \"คอมมิชชั่น\"
2. ไปหน้า ประวัติธุรกรรม แล้ว Filter เฉพาะ 7 วันล่าสุด
3. ลองเรียงลำดับตามจำนวนเงิน (มาก→น้อย)
4. ลอง Export เป็น Excel

**แบบฝึกหัดที่ 4: ปรับแต่ง Dashboard**

1. เปลี่ยนธีมเป็น Dark Mode
2. ลองปรับขนาดหน้าจอ (resize browser) ดูว่าเปลี่ยนอย่างไร
3. ทดลองใช้ Keyboard Shortcut อย่างน้อย 3 อัน

**Checklist:**
☐ เข้าใจส่วนประกอบของ Dashboard
☐ ใช้งานทุกเมนูหลักได้
☐ ใช้ Search และ Filter ได้
☐ เปลี่ยน Theme ได้
☐ ใช้ Keyboard Shortcut ได้",
            ],
        ];
    }

    /**
     * คอร์ส 3: Quiz
     */
    private function getCourse3Quiz(): array
    {
        return [
            'time_limit' => 15,
            'description' => 'ทดสอบความเข้าใจเกี่ยวกับ Dashboard และการนำทาง',
            'questions' => [
                [
                    'question' => 'Summary Card ใดแสดงยอดเงินที่ถอนได้ทันที?',
                    'answers' => [
                        ['text' => 'Pending', 'is_correct' => false],
                        ['text' => 'Available', 'is_correct' => true],
                        ['text' => 'Total', 'is_correct' => false],
                        ['text' => 'Reserved', 'is_correct' => false],
                    ],
                    'explanation' => 'Available คือยอดเงินที่ถอนได้ทันที, Pending คือเงินที่รอดำเนินการ',
                ],
                [
                    'question' => 'Keyboard Shortcut ใดใช้เปิด Global Search?',
                    'answers' => [
                        ['text' => 'Ctrl + S', 'is_correct' => false],
                        ['text' => 'Ctrl + F', 'is_correct' => false],
                        ['text' => 'Ctrl + K', 'is_correct' => true],
                        ['text' => 'Ctrl + G', 'is_correct' => false],
                    ],
                    'explanation' => 'Ctrl + K หรือ / ใช้เปิด Global Search',
                ],
                [
                    'question' => 'เมนูใดใช้ดูสายงานและสมาชิกในทีม?',
                    'answers' => [
                        ['text' => 'Dashboard', 'is_correct' => false],
                        ['text' => 'Wallet', 'is_correct' => false],
                        ['text' => 'Team', 'is_correct' => true],
                        ['text' => 'Reports', 'is_correct' => false],
                    ],
                    'explanation' => 'เมนู Team ใช้ดูสายงาน (Genealogy) และรายชื่อสมาชิก',
                ],
                [
                    'question' => 'Dark Mode มีข้อดีอย่างไร?',
                    'answers' => [
                        ['text' => 'โหลดเร็วขึ้น', 'is_correct' => false],
                        ['text' => 'ลดความเมื่อยล้าตาตอนกลางคืน', 'is_correct' => true],
                        ['text' => 'แสดงสีสดใสกว่า', 'is_correct' => false],
                        ['text' => 'ใช้ RAM น้อยกว่า', 'is_correct' => false],
                    ],
                    'explanation' => 'Dark Mode ช่วยลดความเมื่อยล้าตาเมื่อใช้งานในที่มืดหรือตอนกลางคืน',
                ],
                [
                    'question' => 'การเรียงลำดับข้อมูลในตารางทำอย่างไร?',
                    'answers' => [
                        ['text' => 'คลิกขวาที่ตาราง', 'is_correct' => false],
                        ['text' => 'คลิกหัวคอลัมน์', 'is_correct' => true],
                        ['text' => 'กดปุ่ม Sort', 'is_correct' => false],
                        ['text' => 'ลากคอลัมน์', 'is_correct' => false],
                    ],
                    'explanation' => 'คลิกหัวคอลัมน์เพื่อเรียงลำดับ และคลิกอีกครั้งเพื่อสลับทิศทาง',
                ],
                [
                    'question' => 'สัญลักษณ์ ▲ ในหัวคอลัมน์หมายถึงอะไร?',
                    'answers' => [
                        ['text' => 'เรียงจาก Z-A', 'is_correct' => false],
                        ['text' => 'เรียงจาก A-Z หรือน้อยไปมาก', 'is_correct' => true],
                        ['text' => 'ข้อมูลเพิ่มขึ้น', 'is_correct' => false],
                        ['text' => 'คอลัมน์สำคัญ', 'is_correct' => false],
                    ],
                    'explanation' => 'สัญลักษณ์ ▲ หมายถึงเรียงจาก A-Z หรือน้อยไปมาก, ▼ หมายถึงสลับทิศทาง',
                ],
                [
                    'question' => 'Date Range Filter มี Preset อะไรบ้าง?',
                    'answers' => [
                        ['text' => 'เฉพาะวันนี้', 'is_correct' => false],
                        ['text' => 'วันนี้, 7 วัน, 30 วัน, เดือนนี้', 'is_correct' => true],
                        ['text' => 'เฉพาะเดือนนี้และปีนี้', 'is_correct' => false],
                        ['text' => 'ไม่มี Preset', 'is_correct' => false],
                    ],
                    'explanation' => 'Date Range Filter มี Preset: วันนี้, 7 วัน, 30 วัน, เดือนนี้ ให้เลือกใช้งานสะดวก',
                ],
            ],
        ];
    }

    /**
     * คอร์ส 4: ระบบ Affiliate Marketing เบื้องต้น - เนื้อหา
     *
     * เนื้อหาครอบคลุม: หลักการ Affiliate, การแชร์ลิงก์, การติดตามผล, กลยุทธ์เบื้องต้น
     */
    private function getCourse4Content(): array
    {
        return [
            [
                'title' => '🎯 Affiliate Marketing คืออะไร?',
                'content' => "**Affiliate Marketing** หรือ **การตลาดแบบพันธมิตร** คือรูปแบบการตลาดที่คุณได้รับค่าตอบแทน (Commission) เมื่อแนะนำลูกค้าให้กับธุรกิจ

**หลักการทำงานพื้นฐาน:**

```
┌─────────────┐    แชร์ลิงก์    ┌─────────────┐
│    คุณ      │ ─────────────► │  ลูกค้า     │
│  (Affiliate)│                │  (Customer) │
└─────────────┘                └──────┬──────┘
       ▲                              │
       │ ค่าคอมมิชชั่น                │ ซื้อสินค้า/สมัคร
       │                              ▼
┌──────┴──────────────────────────────────────┐
│              Thaiprompt Platform            │
└─────────────────────────────────────────────┘
```

**ทำไม Affiliate Marketing ถึงได้รับความนิยม?**

• **ไม่ต้องมีสินค้าเอง** - ไม่ต้องลงทุนสต๊อก ไม่ต้องจัดส่ง
• **เริ่มต้นฟรี** - ไม่มีค่าใช้จ่ายในการเริ่มต้น
• **รายได้ไม่จำกัด** - ยิ่งแนะนำมาก ยิ่งได้มาก
• **ทำงานที่ไหนก็ได้** - แค่มีอินเทอร์เน็ต
• **Passive Income** - ลิงก์ทำงานให้ 24/7

**ใน Thaiprompt คุณสามารถเป็น Affiliate ได้หลายรูปแบบ:**

1. **Product Affiliate** - แนะนำสินค้าในร้านค้า
2. **Service Affiliate** - แนะนำบริการต่างๆ
3. **Member Affiliate** - แนะนำสมาชิกใหม่เข้าระบบ
4. **AI Bot Affiliate** - แนะนำ AI Bot และบริการ AI",
            ],
            [
                'title' => '🔗 การดึง Referral Link และประเภทลิงก์',
                'content' => "**วิธีดึง Referral Link ของคุณ:**

**ขั้นตอนที่ 1:** Login เข้าสู่ระบบ Thaiprompt
**ขั้นตอนที่ 2:** ไปที่เมนู **\"Affiliate\"** > **\"ลิงก์แนะนำ\"**
**ขั้นตอนที่ 3:** คัดลอกลิงก์ที่แสดง

**รูปแบบ Referral Link:**

```
https://thaiprompt.com/ref/USERNAME
https://thaiprompt.com/r/USERNAME
https://thaiprompt.com/invite/USERNAME
```

**ประเภทลิงก์ที่สร้างได้:**

| ประเภท | ใช้สำหรับ | ตัวอย่าง |
|--------|----------|----------|
| **ลิงก์ทั่วไป** | แนะนำสมัครสมาชิก | `/ref/username` |
| **ลิงก์สินค้า** | แนะนำสินค้าเฉพาะ | `/ref/username?product=123` |
| **ลิงก์แคมเปญ** | ติดตามแหล่งที่มา | `/ref/username?utm=facebook` |
| **ลิงก์ Landing Page** | หน้า Landing พิเศษ | `/lp/promo?ref=username` |

**การสร้างลิงก์แคมเปญ (Campaign Link):**

1. ไปที่ \"Affiliate\" > \"สร้างลิงก์แคมเปญ\"
2. ตั้งชื่อแคมเปญ (เช่น \"Facebook_Jan2025\")
3. เลือก Landing Page
4. ระบบจะสร้างลิงก์พร้อม Tracking Code

**ประโยชน์ของ Campaign Link:**
• รู้ว่าลูกค้ามาจากช่องทางไหน
• วัดผลแต่ละแคมเปญได้
• ปรับปรุงกลยุทธ์ได้ตรงจุด",
            ],
            [
                'title' => '💰 โครงสร้างค่าคอมมิชชั่น',
                'content' => "**ประเภทค่าคอมมิชชั่นใน Thaiprompt:**

**1. Direct Commission (ค่าคอมมิชชั่นตรง)**
• ได้รับเมื่อลูกค้าที่คุณแนะนำซื้อสินค้า
• อัตรา: **5% - 30%** ขึ้นอยู่กับประเภทสินค้า

```
ตัวอย่าง:
สินค้าราคา 1,000 บาท × อัตรา 10% = 100 บาท
```

**2. Indirect Commission (ค่าคอมมิชชั่นทางอ้อม)**
• ได้รับจากยอดขายของสมาชิกในสายงานคุณ
• อัตรา: **1% - 5%** ตามระดับ

```
Level 1: 5% (สมาชิกที่คุณแนะนำโดยตรง)
Level 2: 3% (สมาชิกที่ Level 1 แนะนำ)
Level 3: 2% (สมาชิกที่ Level 2 แนะนำ)
```

**3. Registration Bonus (โบนัสสมัครสมาชิก)**
• ได้รับเมื่อแนะนำสมาชิกใหม่สำเร็จ
• อัตรา: **50 - 500 บาท** ต่อคน

**4. Recurring Commission (ค่าคอมมิชชั่นต่อเนื่อง)**
• ได้รับทุกเดือนจากบริการ Subscription
• อัตรา: **10% - 20%** ของค่าบริการรายเดือน

**ตารางอัตราค่าคอมมิชชั่นตามประเภทสินค้า:**

| หมวดหมู่ | Direct | Indirect L1 | Indirect L2 |
|----------|--------|-------------|-------------|
| สินค้าทั่วไป | 10% | 3% | 1% |
| สินค้าดิจิทัล | 20% | 5% | 2% |
| AI Bot/Software | 25% | 7% | 3% |
| คอร์สเรียน | 30% | 8% | 4% |",
            ],
            [
                'title' => '📊 การติดตามผลและรายงาน',
                'content' => "**Dashboard การติดตามผล:**

เข้าถึงได้ที่: **\"Affiliate\"** > **\"รายงาน\"**

**ข้อมูลที่แสดงใน Dashboard:**

**📈 Summary Cards:**
• **Total Clicks** - จำนวนคลิกลิงก์ทั้งหมด
• **Unique Visitors** - จำนวนผู้เข้าชมไม่ซ้ำ
• **Conversions** - จำนวนการสมัคร/ซื้อสำเร็จ
• **Conversion Rate** - อัตราการแปลง (%)
• **Total Earnings** - รายได้ทั้งหมด
• **Pending** - รายได้รอดำเนินการ

**📊 กราฟและแผนภูมิ:**
• กราฟรายได้รายวัน/สัปดาห์/เดือน
• แผนภูมิแหล่งที่มาของ Traffic
• Top Products ที่ขายดี
• Top Referrals ที่ทำรายได้สูง

**📋 ตารางรายละเอียด:**

| คอลัมน์ | คำอธิบาย |
|---------|----------|
| วันที่ | วันที่เกิด Transaction |
| ลูกค้า | ชื่อ/Username ลูกค้า |
| สินค้า | ชื่อสินค้าที่ซื้อ |
| ยอดสั่งซื้อ | มูลค่าคำสั่งซื้อ |
| Commission | ค่าคอมมิชชั่นที่ได้ |
| สถานะ | Pending/Approved/Paid |

**การ Export ข้อมูล:**
• Export เป็น Excel (.xlsx)
• Export เป็น CSV
• Export เป็น PDF (รายงานสรุป)

**Tips สำหรับการวิเคราะห์:**
• ดูว่าช่องทางไหนได้ผลดีที่สุด
• สินค้าอะไรขายดี
• ช่วงเวลาไหนมี Conversion สูง",
            ],
            [
                'title' => '🚀 กลยุทธ์ Affiliate เบื้องต้น',
                'content' => "**5 กลยุทธ์สำหรับผู้เริ่มต้น:**

**1. เริ่มจากคนรู้จัก (Warm Market)**

• ครอบครัว เพื่อน คนรู้จัก
• แชร์ประสบการณ์จริงของคุณ
• อย่า Hard Sell แต่ให้ Value

```
❌ \"มาสมัครเลย มีโปรโมชั่น!\"
✅ \"ลองใช้ระบบนี้ มันช่วยฉันได้มาก...\"
```

**2. สร้าง Content บน Social Media**

• Facebook: โพสต์รีวิว, Live สาธิต
• Instagram: รูปสวย, Stories, Reels
• TikTok: วิดีโอสั้นน่าสนใจ
• YouTube: รีวิวละเอียด, Tutorial

**3. ใช้ LINE สร้างความสัมพันธ์**

• สร้าง LINE Official Account
• ส่งข้อมูลที่มีประโยชน์
• ตอบคำถามอย่างใส่ใจ
• ใช้ Rich Menu แนะนำสินค้า

**4. เข้ากลุ่มและ Community**

• เข้ากลุ่ม Facebook ที่เกี่ยวข้อง
• ตอบคำถาม ช่วยเหลือคนอื่น
• แชร์ความรู้ก่อน แชร์ลิงก์ทีหลัง
• อย่า Spam!

**5. สร้าง Landing Page ส่วนตัว**

• ใช้เครื่องมือสร้าง Landing Page ฟรี
• เขียน Copy ที่น่าสนใจ
• ใส่ Call-to-Action ชัดเจน
• เก็บ Lead ด้วย Form

**ข้อควรระวัง:**

❌ อย่า Spam ลิงก์ทุกที่
❌ อย่าโกหกหรือพูดเกินจริง
❌ อย่าละเลยการติดตามผล
❌ อย่าหยุดเรียนรู้",
            ],
            [
                'title' => '📝 แบบฝึกหัดท้ายบท',
                'content' => "**แบบฝึกหัดที่ 1: ดึง Referral Link**

1. Login เข้าสู่ระบบ Thaiprompt
2. ไปที่เมนู \"Affiliate\" > \"ลิงก์แนะนำ\"
3. คัดลอก Referral Link ของคุณ
4. ทดลองเปิดลิงก์ใน Browser แบบ Incognito
5. บันทึกลิงก์ไว้ในที่ที่หาง่าย

**แบบฝึกหัดที่ 2: สร้าง Campaign Link**

1. ไปที่ \"Affiliate\" > \"สร้างลิงก์แคมเปญ\"
2. สร้างลิงก์ 3 แคมเปญ:
   - Facebook_Profile
   - LINE_Group
   - Instagram_Bio
3. บันทึกลิงก์ทั้ง 3 ไว้ใช้งาน

**แบบฝึกหัดที่ 3: ดู Dashboard**

1. ไปที่ \"Affiliate\" > \"รายงาน\"
2. ทำความเข้าใจแต่ละ Metric
3. ลอง Filter ตามช่วงเวลา (7 วัน, 30 วัน)
4. Export รายงานเป็น Excel

**แบบฝึกหัดที่ 4: เขียน Introduction Post**

เขียนโพสต์แนะนำ Thaiprompt ความยาว 3-5 บรรทัด ที่:
• บอกว่า Thaiprompt คืออะไร
• ประโยชน์ที่จะได้รับ
• เชิญชวนให้ลองใช้งาน
• ไม่ Hard Sell เกินไป

**Checklist ความเข้าใจ:**

☐ เข้าใจว่า Affiliate Marketing คืออะไร
☐ สามารถดึง Referral Link ได้
☐ รู้จักประเภทค่าคอมมิชชั่น
☐ ใช้งาน Dashboard รายงานได้
☐ รู้กลยุทธ์เบื้องต้น 5 ข้อ",
            ],
        ];
    }

    /**
     * คอร์ส 5: ระบบกระเป๋าเงิน (Wallet) - เนื้อหา
     *
     * เนื้อหาครอบคลุม: ประเภทกระเป๋า, การเติมเงิน, การถอนเงิน, การโอน, ความปลอดภัย
     */
    private function getCourse5Content(): array
    {
        return [
            [
                'title' => '💰 ภาพรวมระบบกระเป๋าเงิน',
                'content' => "**Thaiprompt Wallet System** คือระบบกระเป๋าเงินดิจิทัลที่รองรับหลายประเภทสกุลเงินและเหรียญ

**ประเภทกระเป๋าเงินในระบบ:**

```
┌─────────────────────────────────────────────────────┐
│              💼 WALLET OVERVIEW                     │
├─────────────────────────────────────────────────────┤
│                                                     │
│  💵 Main Wallet          ฿ 15,250.00               │
│     กระเป๋าหลัก - เงินบาท                          │
│                                                     │
│  🎮 Video Coins           VC 1,500                 │
│     เหรียญจากการดูวิดีโอ                           │
│                                                     │
│  ⭐ Points                 PT 2,350                 │
│     แต้มสะสมแลกรางวัล                              │
│                                                     │
│  ₿ TPIX Token             TP 850.5                 │
│     สกุลเงินดิจิทัล                                │
│                                                     │
│  🎁 Bonus Wallet          ฿ 500.00                 │
│     โบนัสและโปรโมชั่น                              │
│                                                     │
└─────────────────────────────────────────────────────┘
```

**รายละเอียดแต่ละกระเป๋า:**

| กระเป๋า | สกุลเงิน | ใช้สำหรับ | ถอนได้? |
|---------|----------|-----------|---------|
| Main Wallet | บาท (฿) | รับค่าคอมมิชชั่น, ถอนเงิน | ✅ ได้ |
| Video Coins | VC | แลกสินค้า, เล่นเกม | ❌ |
| Points | PT | แลกรางวัล, ส่วนลด | ❌ |
| TPIX Token | TP | ซื้อขาย, Staking, ชำระเงิน | ✅ ได้ |
| Bonus Wallet | บาท (฿) | ใช้ซื้อสินค้าในระบบ | ❌ |

**การเข้าถึง Wallet:**
ไปที่เมนู **\"กระเป๋าเงิน\"** หรือ **\"Wallet\"** ในหน้า Dashboard",
            ],
            [
                'title' => '💳 การเติมเงิน (Deposit)',
                'content' => "**ช่องทางการเติมเงินเข้า Main Wallet:**

**1. PromptPay (แนะนำ) ⭐**
• โอนผ่าน QR Code ทันที
• ไม่มีค่าธรรมเนียม
• เงินเข้าทันที (Real-time)

**2. บัตรเครดิต/เดบิต**
• รองรับ Visa, MasterCard, JCB
• ค่าธรรมเนียม 2.9%
• เงินเข้าทันที

**3. Internet Banking**
• โอนผ่านธนาคารออนไลน์
• รองรับทุกธนาคาร
• เงินเข้าภายใน 1-5 นาที

**4. TrueMoney Wallet**
• เติมผ่าน TrueMoney App
• ค่าธรรมเนียม 1%
• เงินเข้าทันที

**5. Counter Service**
• เติมที่ 7-Eleven, Lotus's
• ค่าธรรมเนียม 10-20 บาท
• เงินเข้าภายใน 15-30 นาที

**ขั้นตอนการเติมเงิน:**

```
1. ไปที่ \"กระเป๋าเงิน\" > \"เติมเงิน\"
2. เลือกจำนวนเงิน (ขั้นต่ำ 100 บาท)
3. เลือกช่องทางการชำระ
4. ทำตามขั้นตอน
5. รอเงินเข้า (ส่วนใหญ่ทันที)
```

**โปรโมชั่นเติมเงิน:**
• เติมครั้งแรก +10% Bonus
• เติม 1,000+ ได้ Points x2
• เติมครบ 5,000 ได้ของรางวัลพิเศษ",
            ],
            [
                'title' => '🏧 การถอนเงิน (Withdrawal)',
                'content' => "**เงื่อนไขก่อนถอนเงิน:**

**✅ สิ่งที่ต้องมี:**
• ยืนยันตัวตน (KYC) ระดับ 2 ขึ้นไป
• เพิ่มบัญชีธนาคารแล้ว
• เปิดใช้งาน 2FA
• ยอดเงินขั้นต่ำ 100 บาท

**ขั้นตอนเพิ่มบัญชีธนาคาร:**

```
1. ไปที่ \"ตั้งค่า\" > \"บัญชีธนาคาร\"
2. คลิก \"เพิ่มบัญชี\"
3. เลือกธนาคาร
4. กรอกเลขบัญชี (10-12 หลัก)
5. กรอกชื่อบัญชี (ต้องตรงกับ KYC)
6. ยืนยันด้วย OTP
```

**ขั้นตอนการถอนเงิน:**

```
1. ไปที่ \"กระเป๋าเงิน\" > \"ถอนเงิน\"
2. ระบุจำนวนเงิน (ขั้นต่ำ 100 บาท)
3. เลือกบัญชีปลายทาง
4. ตรวจสอบข้อมูล
5. ยืนยันด้วย 2FA Code
6. รอเงินเข้าบัญชี
```

**ระยะเวลาและค่าธรรมเนียม:**

| ประเภท | ระยะเวลา | ค่าธรรมเนียม |
|--------|----------|--------------|
| ธนาคารเดียวกัน | ทันที - 1 ชม. | ฟรี |
| ต่างธนาคาร | 1-3 วันทำการ | 10-25 บาท |
| ถอนด่วน (Express) | 30 นาที | 50 บาท |

**หมายเหตุ:**
• ถอนได้สูงสุด 100,000 บาท/วัน (ปรับได้ตาม KYC)
• ถอนนอกเวลาทำการอาจล่าช้า
• ตรวจสอบเลขบัญชีให้ถูกต้องทุกครั้ง",
            ],
            [
                'title' => '🔄 การโอนเงินและแลกเปลี่ยน',
                'content' => "**การโอนเงินระหว่างสมาชิก (P2P Transfer):**

**ขั้นตอนการโอน:**
```
1. ไปที่ \"กระเป๋าเงิน\" > \"โอนเงิน\"
2. กรอก Username หรือ Email ผู้รับ
3. ระบุจำนวนเงิน
4. เพิ่มข้อความ (ถ้ามี)
5. ยืนยันด้วย 2FA
6. เงินถึงผู้รับทันที
```

**ค่าธรรมเนียมโอน:**
• 0-1,000 บาท: ฟรี
• 1,001-10,000 บาท: 1% (ไม่เกิน 50 บาท)
• 10,001+ บาท: 0.5% (ไม่เกิน 100 บาท)

**การแลกเปลี่ยนสกุลเงิน (Exchange):**

**ตัวอย่างอัตราแลกเปลี่ยน:**
```
Video Coins → Points:     100 VC = 50 PT
Points → Main Wallet:     1,000 PT = 100 บาท
TPIX → Main Wallet:       1 TP = (ตามราคาตลาด)
Bonus → Main Wallet:      ไม่สามารถแลกได้
```

**ขั้นตอนการแลกเปลี่ยน:**
```
1. ไปที่ \"กระเป๋าเงิน\" > \"แลกเปลี่ยน\"
2. เลือกสกุลเงินต้นทาง
3. เลือกสกุลเงินปลายทาง
4. ระบุจำนวน
5. ดูอัตราแลกเปลี่ยน
6. ยืนยันการแลก
```

**Tips การแลกเปลี่ยน:**
• ดูอัตราแลกเปลี่ยนก่อนทำรายการ
• TPIX อาจมีราคาผันผวน ควรดู Chart
• บาง Promo มีอัตราแลกพิเศษ",
            ],
            [
                'title' => '📊 ประวัติธุรกรรมและรายงาน',
                'content' => "**การดูประวัติธุรกรรม:**

**เข้าถึงที่:** \"กระเป๋าเงิน\" > \"ประวัติธุรกรรม\"

**ข้อมูลที่แสดง:**

| คอลัมน์ | คำอธิบาย |
|---------|----------|
| วันที่/เวลา | Timestamp ของธุรกรรม |
| ประเภท | เติม/ถอน/โอน/รับ/ชำระ |
| จำนวน | +/- ตามประเภท |
| ยอดคงเหลือ | Balance หลังธุรกรรม |
| หมายเหตุ | รายละเอียดเพิ่มเติม |
| สถานะ | สำเร็จ/รอดำเนินการ/ยกเลิก |

**การ Filter ข้อมูล:**

• **ตามประเภท:** เติม, ถอน, โอน, รับ, ค่าคอมมิชชั่น
• **ตามสถานะ:** ทั้งหมด, สำเร็จ, รอดำเนินการ
• **ตามวันที่:** วันนี้, 7 วัน, 30 วัน, กำหนดเอง
• **ตามกระเป๋า:** Main, Video Coins, Points, TPIX

**การ Export รายงาน:**

```
รูปแบบที่รองรับ:
• PDF - สำหรับพิมพ์/เก็บเอกสาร
• Excel (.xlsx) - สำหรับวิเคราะห์ข้อมูล
• CSV - สำหรับ Import เข้าระบบอื่น
```

**สถิติสรุป (Summary):**
• รายรับรวม/เดือน
• รายจ่ายรวม/เดือน
• Balance เฉลี่ย
• ธุรกรรมที่ใช้บ่อย",
            ],
            [
                'title' => '🔒 ความปลอดภัยกระเป๋าเงิน',
                'content' => "**มาตรการความปลอดภัย:**

**1. Two-Factor Authentication (2FA)**
• ต้องใช้ทุกครั้งที่ถอน/โอนเงิน
• รองรับ Google Authenticator, Authy
• Backup Codes สำหรับกรณีฉุกเฉิน

**2. ยืนยันตัวตน (KYC)**
| Level | วงเงิน/วัน | สิ่งที่ต้องทำ |
|-------|-----------|--------------|
| 1 | 5,000 บาท | ยืนยัน Email + เบอร์โทร |
| 2 | 50,000 บาท | + บัตรประชาชน |
| 3 | 500,000 บาท | + หนังสือเดินทาง + ที่อยู่ |

**3. Session Security**
• Auto-logout หลังไม่ใช้งาน 30 นาที
• แจ้งเตือน Login จากอุปกรณ์ใหม่
• Lock บัญชีหลังใส่รหัสผิด 5 ครั้ง

**4. Transaction Limits**
• ตั้งวงเงินถอน/โอนสูงสุดต่อวัน
• ยืนยันธุรกรรมมูลค่าสูงทาง Email
• Delay 24 ชม. สำหรับบัญชีธนาคารใหม่

**แนวปฏิบัติที่ดี:**

✅ **ควรทำ:**
• เปิด 2FA เสมอ
• ใช้รหัสผ่านที่แข็งแกร่ง
• ตรวจสอบประวัติธุรกรรมเป็นประจำ
• อัปเดตข้อมูลติดต่อให้เป็นปัจจุบัน

❌ **ไม่ควรทำ:**
• แชร์รหัสผ่านหรือ 2FA Code
• Login จากเครื่องสาธารณะ
• คลิกลิงก์ที่ไม่น่าไว้ใจ
• เพิกเฉยการแจ้งเตือนผิดปกติ",
            ],
            [
                'title' => '📝 แบบฝึกหัดท้ายบท',
                'content' => "**แบบฝึกหัดที่ 1: สำรวจกระเป๋าเงิน**

1. Login เข้าสู่ระบบ
2. ไปที่เมนู \"กระเป๋าเงิน\"
3. ดูยอดเงินในแต่ละกระเป๋า
4. บันทึกยอดเงินปัจจุบัน:
   - Main Wallet: ______
   - Video Coins: ______
   - Points: ______

**แบบฝึกหัดที่ 2: ดูประวัติธุรกรรม**

1. ไปที่ \"ประวัติธุรกรรม\"
2. Filter แสดงเฉพาะ 7 วันล่าสุด
3. ดูธุรกรรมประเภท \"รับเงิน\"
4. Export เป็น Excel

**แบบฝึกหัดที่ 3: ตรวจสอบความปลอดภัย**

ตรวจสอบและทำเครื่องหมายสิ่งที่ทำแล้ว:
☐ ยืนยัน Email แล้ว
☐ ยืนยันเบอร์โทรแล้ว
☐ เปิดใช้งาน 2FA แล้ว
☐ ทำ KYC Level 2 แล้ว
☐ เพิ่มบัญชีธนาคารแล้ว

**แบบฝึกหัดที่ 4: จำลองถอนเงิน**

1. ไปที่ \"ถอนเงิน\"
2. กรอกจำนวน 100 บาท
3. เลือกบัญชีธนาคาร
4. ดูค่าธรรมเนียมและระยะเวลา
5. **ยังไม่ต้องกดยืนยัน** - แค่ดูขั้นตอน

**Checklist ความเข้าใจ:**

☐ รู้จักประเภทกระเป๋าเงินทั้งหมด
☐ เข้าใจวิธีเติมเงิน
☐ เข้าใจวิธีถอนเงินและเงื่อนไข
☐ รู้วิธีดูประวัติธุรกรรม
☐ ตั้งค่าความปลอดภัยครบถ้วน",
            ],
        ];
    }

    /**
     * คอร์ส 6: ระบบสั่งซื้อสินค้าและชำระเงิน - เนื้อหา
     */
    private function getCourse6Content(): array
    {
        return [
            [
                'title' => '🛒 ภาพรวมระบบสั่งซื้อสินค้า',
                'content' => "**ระบบ E-Commerce ใน Thaiprompt** เป็นตลาดออนไลน์ครบวงจรที่มีสินค้าหลากหลาย

**ประเภทสินค้าในระบบ:**

| ประเภท | ตัวอย่าง | PV |
|--------|---------|-----|
| 📦 สินค้าทั่วไป | เสื้อผ้า, อุปกรณ์ | 5-20% |
| 💊 สุขภาพ/ความงาม | อาหารเสริม, ครีม | 10-30% |
| 📱 ดิจิทัล | E-book, Software | 20-40% |
| 🤖 AI Products | AI Bot, Prompts | 25-50% |
| 📚 คอร์สเรียน | Online Courses | 30-60% |
| 🎫 บัตร/Voucher | Gift Cards, Coupons | 5-15% |

**Flow การสั่งซื้อ:**

```
┌──────────┐   ┌──────────┐   ┌──────────┐   ┌──────────┐
│ เลือกสินค้า │ → │ ใส่ตะกร้า │ → │ ชำระเงิน │ → │ รอรับสินค้า│
└──────────┘   └──────────┘   └──────────┘   └──────────┘
```

**การเข้าถึงร้านค้า:**
• เมนู **\"ร้านค้า\"** หรือ **\"Marketplace\"**
• ค้นหาจาก Search Bar
• หมวดหมู่สินค้า
• สินค้าแนะนำบน Dashboard",
            ],
            [
                'title' => '🔍 การเลือกสินค้าและเพิ่มตะกร้า',
                'content' => "**ขั้นตอนเลือกสินค้า:**

**1. ค้นหาสินค้า:**
• พิมพ์ชื่อใน Search Bar
• เลือกจากหมวดหมู่
• Filter ตามราคา/Rating
• เรียงตามยอดขาย/ใหม่ล่าสุด

**2. ดูรายละเอียด:**
```
┌─────────────────────────────────────────┐
│  [รูปสินค้า]        ชื่อสินค้า          │
│                     ⭐⭐⭐⭐⭐ (4.8)     │
│  [รูปเล็ก] [รูปเล็ก]  ราคา: ฿299        │
│                     ส่วนลด: ฿199        │
│                     PV: 15              │
│                                         │
│  ขนาด: [S] [M] [L] [XL]               │
│  สี:   [🔴] [🔵] [⚫]                  │
│  จำนวน: [-] 1 [+]                      │
│                                         │
│  [🛒 เพิ่มลงตะกร้า]  [❤️ เพิ่มรายการโปรด] │
└─────────────────────────────────────────┘
```

**3. เพิ่มลงตะกร้า:**
• เลือกตัวเลือก (ถ้ามี)
• ระบุจำนวน
• คลิก \"เพิ่มลงตะกร้า\"
• ดำเนินการต่อหรือดูตะกร้า

**การจัดการตะกร้า:**
• แก้ไขจำนวน
• ลบสินค้า
• บันทึกไว้ซื้อทีหลัง
• ใช้คูปองส่วนลด",
            ],
            [
                'title' => '💳 การชำระเงิน',
                'content' => "**ช่องทางการชำระเงิน:**

**1. Wallet Balance (แนะนำ) ⭐**
• ตัดยอดจากกระเป๋าเงินทันที
• ได้ Cashback เพิ่ม 1-5%
• ไม่มีค่าธรรมเนียม

**2. PromptPay**
• สแกน QR Code ชำระ
• ยืนยันอัตโนมัติ
• ไม่มีค่าธรรมเนียม

**3. บัตรเครดิต/เดบิต**
• Visa, MasterCard, JCB
• รองรับผ่อนชำระ (บางสินค้า)
• ค่าธรรมเนียม 2.9%

**4. โอนเงิน/อัปโหลดสลิป**
• โอนตามบัญชีที่แสดง
• อัปโหลดหลักฐาน
• รอตรวจสอบ 5-30 นาที

**5. TPIX Token**
• ชำระด้วย TPIX
• ส่วนลดพิเศษ 5-10%
• ราคาตามอัตราแลกเปลี่ยน

**ขั้นตอน Checkout:**

```
1. ตรวจสอบตะกร้า → ✅
2. กรอกที่อยู่จัดส่ง → ✅
3. เลือกวิธีจัดส่ง → ✅
4. ใส่คูปอง (ถ้ามี) → ✅
5. เลือกวิธีชำระเงิน → ✅
6. ยืนยันคำสั่งซื้อ → ✅
```

**Tips:**
• ใช้ Wallet จะได้ Cashback
• เช็คคูปองก่อนจ่ายเสมอ
• ตรวจสอบที่อยู่ให้ถูกต้อง",
            ],
            [
                'title' => '📦 การติดตามและรับสินค้า',
                'content' => "**สถานะคำสั่งซื้อ:**

| สถานะ | ความหมาย |
|-------|---------|
| 🟡 รอชำระเงิน | ยังไม่ได้ชำระ |
| 🟠 กำลังตรวจสอบ | รอตรวจสอบการชำระ |
| 🔵 ยืนยันแล้ว | ร้านค้ายืนยันแล้ว |
| 🟣 กำลังจัดเตรียม | กำลังแพ็คสินค้า |
| 🔶 จัดส่งแล้ว | ส่งมอบให้ขนส่งแล้ว |
| 🟢 สำเร็จ | ได้รับสินค้าแล้ว |
| 🔴 ยกเลิก | คำสั่งซื้อถูกยกเลิก |

**การติดตามพัสดุ:**

```
1. ไปที่ \"คำสั่งซื้อของฉัน\"
2. เลือกคำสั่งซื้อที่ต้องการ
3. คลิก \"ติดตามพัสดุ\"
4. ดูสถานะและประวัติการจัดส่ง
```

**บริษัทขนส่งที่รองรับ:**
• Kerry Express
• Flash Express
• J&T Express
• ไปรษณีย์ไทย
• Shopee Express
• Grab Express (Same-day)

**การยืนยันรับสินค้า:**
• ตรวจสอบสินค้าให้เรียบร้อย
• กด \"ได้รับสินค้าแล้ว\"
• ให้คะแนนและรีวิว (ได้ Points เพิ่ม!)
• หากมีปัญหา กด \"แจ้งปัญหา\"",
            ],
            [
                'title' => '🔄 การยกเลิกและขอคืนเงิน',
                'content' => "**เงื่อนไขการยกเลิก:**

| สถานะ | ยกเลิกได้? |
|-------|-----------|
| รอชำระเงิน | ✅ ได้เลย |
| ยืนยันแล้ว | ✅ ได้ (รอร้านค้าอนุมัติ) |
| กำลังจัดเตรียม | ⚠️ ต้องติดต่อร้านค้า |
| จัดส่งแล้ว | ❌ ต้องรอรับก่อนค่อยคืน |
| สำเร็จ | ❌ ต้องเปิดเรื่องคืนสินค้า |

**ขั้นตอนยกเลิก:**
```
1. ไปที่ \"คำสั่งซื้อของฉัน\"
2. เลือกคำสั่งซื้อ
3. คลิก \"ยกเลิกคำสั่งซื้อ\"
4. ระบุเหตุผล
5. รอการอนุมัติ
```

**การขอคืนเงิน/คืนสินค้า:**

```
1. ไปที่คำสั่งซื้อที่ได้รับแล้ว
2. คลิก \"ขอคืนสินค้า/เงิน\"
3. เลือกเหตุผล:
   • สินค้าไม่ตรงตามที่สั่ง
   • สินค้าชำรุด/เสียหาย
   • ได้รับสินค้าผิด
   • เปลี่ยนใจ (มีเงื่อนไข)
4. แนบรูปหลักฐาน
5. รอร้านค้าตอบรับ
6. ส่งคืนสินค้า (ถ้าต้องคืน)
7. รับเงินคืน
```

**ระยะเวลาคืนเงิน:**
• Wallet: ทันที - 24 ชม.
• บัตรเครดิต: 7-14 วันทำการ
• โอนเงิน: 3-7 วันทำการ

**หมายเหตุ:**
• สินค้าดิจิทัลไม่สามารถคืนได้
• สินค้าเปิดใช้แล้วอาจคืนไม่ได้
• เก็บหลักฐานการจัดส่งไว้เสมอ",
            ],
            [
                'title' => '📝 แบบฝึกหัดท้ายบท',
                'content' => "**แบบฝึกหัดที่ 1: สำรวจร้านค้า**

1. เข้าไปที่เมนู \"ร้านค้า\"
2. ดูหมวดหมู่สินค้าทั้งหมด
3. ใช้ Filter เลือกสินค้าราคาไม่เกิน 500 บาท
4. เรียงตามยอดขายสูงสุด
5. บันทึก 3 สินค้าที่น่าสนใจ

**แบบฝึกหัดที่ 2: ทดลองใส่ตะกร้า**

1. เลือกสินค้า 1 ชิ้น
2. ดูรายละเอียดให้ครบ (ราคา, PV, รีวิว)
3. เลือกตัวเลือก (ถ้ามี)
4. เพิ่มลงตะกร้า
5. ไปที่ตะกร้า ดูสรุปยอด
6. **ยังไม่ต้องซื้อจริง**

**แบบฝึกหัดที่ 3: ดูประวัติคำสั่งซื้อ**

1. ไปที่ \"คำสั่งซื้อของฉัน\"
2. ดูสถานะคำสั่งซื้อล่าสุด
3. ลองกดดู \"รายละเอียด\"
4. หาวิธีติดตามพัสดุ

**แบบฝึกหัดที่ 4: คำนวณ PV**

สมมติสั่งสินค้า 3 ชิ้น:
• สินค้า A: 500 บาท, PV = 10%
• สินค้า B: 300 บาท, PV = 15%
• สินค้า C: 200 บาท, PV = 20%

คำนวณ:
• PV จากสินค้า A = ______
• PV จากสินค้า B = ______
• PV จากสินค้า C = ______
• **รวม PV = ______**

**Checklist ความเข้าใจ:**

☐ รู้วิธีค้นหาและเลือกสินค้า
☐ เพิ่มสินค้าลงตะกร้าได้
☐ รู้ช่องทางชำระเงินทั้งหมด
☐ ติดตามสถานะคำสั่งซื้อได้
☐ รู้วิธียกเลิกและขอคืนเงิน",
            ],
        ];
    }

    /**
     * คอร์ส 6: ระบบสั่งซื้อสินค้าและชำระเงิน - Quiz
     */
    private function getCourse6Quiz(): array
    {
        return [
            'time_limit' => 15,
            'description' => 'ทดสอบความเข้าใจเกี่ยวกับระบบสั่งซื้อและชำระเงิน',
            'questions' => [
                [
                    'question' => 'ช่องทางชำระเงินใดได้ Cashback เพิ่ม?',
                    'answers' => [
                        ['text' => 'โอนเงิน', 'is_correct' => false],
                        ['text' => 'Wallet Balance', 'is_correct' => true],
                        ['text' => 'บัตรเครดิต', 'is_correct' => false],
                        ['text' => 'PromptPay', 'is_correct' => false],
                    ],
                    'explanation' => 'การชำระผ่าน Wallet Balance จะได้รับ Cashback เพิ่ม 1-5% และไม่มีค่าธรรมเนียม',
                ],
                [
                    'question' => 'สถานะ \"กำลังจัดเตรียม\" หมายถึงอะไร?',
                    'answers' => [
                        ['text' => 'รอชำระเงิน', 'is_correct' => false],
                        ['text' => 'ร้านค้ากำลังแพ็คสินค้า', 'is_correct' => true],
                        ['text' => 'จัดส่งแล้ว', 'is_correct' => false],
                        ['text' => 'ได้รับสินค้าแล้ว', 'is_correct' => false],
                    ],
                    'explanation' => 'สถานะ \"กำลังจัดเตรียม\" หมายถึงร้านค้ากำลังจัดเตรียมและแพ็คสินค้าเพื่อจัดส่ง',
                ],
                [
                    'question' => 'เมื่อไหร่ที่ยกเลิกคำสั่งซื้อไม่ได้?',
                    'answers' => [
                        ['text' => 'รอชำระเงิน', 'is_correct' => false],
                        ['text' => 'ยืนยันแล้ว', 'is_correct' => false],
                        ['text' => 'จัดส่งแล้ว', 'is_correct' => true],
                        ['text' => 'กำลังตรวจสอบ', 'is_correct' => false],
                    ],
                    'explanation' => 'เมื่อจัดส่งแล้วไม่สามารถยกเลิกได้ ต้องรอรับสินค้าก่อนแล้วค่อยเปิดเรื่องคืน',
                ],
                [
                    'question' => 'PV คืออะไร?',
                    'answers' => [
                        ['text' => 'ราคาสินค้า', 'is_correct' => false],
                        ['text' => 'Point Value สำหรับคำนวณค่าคอมมิชชั่น', 'is_correct' => true],
                        ['text' => 'ค่าจัดส่ง', 'is_correct' => false],
                        ['text' => 'ส่วนลด', 'is_correct' => false],
                    ],
                    'explanation' => 'PV (Point Value) คือค่าคะแนนที่ใช้ในการคำนวณค่าคอมมิชชั่นและโบนัส MLM',
                ],
                [
                    'question' => 'การชำระด้วย TPIX มีข้อดีอย่างไร?',
                    'answers' => [
                        ['text' => 'ไม่มีข้อดีพิเศษ', 'is_correct' => false],
                        ['text' => 'ได้ส่วนลดพิเศษ 5-10%', 'is_correct' => true],
                        ['text' => 'จัดส่งเร็วขึ้น', 'is_correct' => false],
                        ['text' => 'ได้ของแถม', 'is_correct' => false],
                    ],
                    'explanation' => 'การชำระด้วย TPIX Token จะได้รับส่วนลดพิเศษ 5-10%',
                ],
                [
                    'question' => 'หลังได้รับสินค้าควรทำอะไร?',
                    'answers' => [
                        ['text' => 'ไม่ต้องทำอะไร', 'is_correct' => false],
                        ['text' => 'กด \"ได้รับสินค้าแล้ว\" และให้รีวิว', 'is_correct' => true],
                        ['text' => 'ติดต่อร้านค้า', 'is_correct' => false],
                        ['text' => 'ยกเลิกคำสั่งซื้อ', 'is_correct' => false],
                    ],
                    'explanation' => 'หลังได้รับสินค้าควรกดยืนยันการรับ และให้คะแนน/รีวิว จะได้ Points เพิ่ม',
                ],
                [
                    'question' => 'ระยะเวลาคืนเงินผ่าน Wallet นานเท่าไร?',
                    'answers' => [
                        ['text' => 'ทันที - 24 ชั่วโมง', 'is_correct' => true],
                        ['text' => '3-7 วันทำการ', 'is_correct' => false],
                        ['text' => '7-14 วันทำการ', 'is_correct' => false],
                        ['text' => '30 วัน', 'is_correct' => false],
                    ],
                    'explanation' => 'การคืนเงินเข้า Wallet จะใช้เวลาทันที - 24 ชั่วโมง ซึ่งเร็วที่สุด',
                ],
            ],
        ];
    }

    /**
     * คอร์ส 7: ระบบ MLM และโครงสร้างสายงาน - เนื้อหา
     */
    private function getCourse7Content(): array
    {
        return [
            [
                'title' => '🏗️ MLM คืออะไร?',
                'content' => "**Multi-Level Marketing (MLM)** คือระบบการตลาดหลายชั้นที่สร้างรายได้จากทั้งการขายและการสร้างทีม

**หลักการพื้นฐาน:**

```
┌─────────────────────────────────────────────────────┐
│                   MLM STRUCTURE                     │
├─────────────────────────────────────────────────────┤
│                                                     │
│              ┌───────────────┐                      │
│              │    YOU (คุณ)   │                     │
│              │   Upline = 0   │                     │
│              └───────┬───────┘                      │
│         ┌───────────┼───────────┐                  │
│         ▼           ▼           ▼                  │
│    ┌────────┐ ┌────────┐ ┌────────┐               │
│    │ Level 1 │ │ Level 1 │ │ Level 1 │              │
│    │ (Direct)│ │ (Direct)│ │ (Direct)│              │
│    └────┬───┘ └────┬───┘ └────────┘               │
│         ▼          ▼                               │
│    ┌────────┐ ┌────────┐                          │
│    │ Level 2 │ │ Level 2 │  ... และต่อไป          │
│    └────────┘ └────────┘                          │
│                                                     │
└─────────────────────────────────────────────────────┘
```

**รายได้จาก MLM:**

1. **Personal Sales** - ขายสินค้าด้วยตัวเอง
2. **Direct Commission** - ค่าคอมมิชชั่นจากคนที่แนะนำโดยตรง
3. **Team Commission** - ค่าคอมมิชชั่นจากยอดขายทีม
4. **Rank Bonus** - โบนัสตามตำแหน่ง
5. **Leadership Bonus** - โบนัสผู้นำทีม

**ข้อดีของ MLM ใน Thaiprompt:**
• ไม่ต้องสต๊อกสินค้า
• ไม่มีค่าใช้จ่ายในการเริ่มต้น
• ระบบ Auto-ship ทำงานอัตโนมัติ
• รองรับหลายแผน (Binary, Unilevel)",
            ],
            [
                'title' => '🌳 Binary Plan (แผน 2 ขา)',
                'content' => "**Binary Plan** คือระบบที่สมาชิกแต่ละคนมี 2 ตำแหน่ง (ซ้าย-ขวา)

**โครงสร้าง:**

```
                    คุณ
                   /    \\
               ซ้าย    ขวา
              /    \\  /    \\
            L1    L2 R1    R2
           /  \\
         ...  ...
```

**หลักการทำงาน:**

| ข้อ | รายละเอียด |
|-----|-----------|
| 1 | ทุกคนมีแค่ 2 ตำแหน่งตรง |
| 2 | Overflow ตกไปให้ Downline |
| 3 | คำนวณโบนัสจากขาที่อ่อนกว่า |
| 4 | ยอด Carry Forward ได้ |

**ตัวอย่างการคำนวณ:**

```
        คุณ
       /    \\
   ซ้าย     ขวา
  1,000 PV  2,500 PV
     ↑
  Weak Leg

โบนัส = Weak Leg × Commission Rate
      = 1,000 PV × 10%
      = 100 บาท

ยอด Carry Forward (ขาขวา):
= 2,500 - 1,000 = 1,500 PV
(ยกไปคำนวณรอบหน้า)
```

**ข้อดี Binary:**
• Overflow ช่วย Downline
• Team Work ทำงานเป็นทีม
• Balance ทั้งสองขา

**ข้อควรระวัง:**
• ต้อง Balance สองขา
• ขาเดียวแข็งไม่ได้โบนัส",
            ],
            [
                'title' => '📊 Unilevel Plan',
                'content' => "**Unilevel Plan** คือระบบที่ไม่จำกัดจำนวนสมาชิกในแต่ละชั้น

**โครงสร้าง:**

```
                      คุณ
           ┌──────────┼──────────┐
           │          │          │
        Level 1    Level 1    Level 1
       ┌──┼──┐        │       ┌──┼──┐
       │  │  │     Level 2    │  │  │
    L2 L2 L2 L2      ...     L2 L2 L2
```

**อัตราค่าคอมมิชชั่นตาม Level:**

| Level | อัตรา | คำอธิบาย |
|-------|-------|---------|
| 1 | 10% | สมาชิกที่คุณแนะนำโดยตรง |
| 2 | 5% | สมาชิกที่ Level 1 แนะนำ |
| 3 | 3% | สมาชิกที่ Level 2 แนะนำ |
| 4 | 2% | สมาชิกที่ Level 3 แนะนำ |
| 5 | 1% | สมาชิกที่ Level 4 แนะนำ |
| 6+ | 0.5% | ต้องมี Rank ถึงเกณฑ์ |

**ตัวอย่างการคำนวณ:**

```
สมมติ:
• Level 1: 5 คน × ยอดเฉลี่ย 1,000 บาท
• Level 2: 20 คน × ยอดเฉลี่ย 800 บาท
• Level 3: 50 คน × ยอดเฉลี่ย 500 บาท

คำนวณ:
• Level 1: 5 × 1,000 × 10% = 500 บาท
• Level 2: 20 × 800 × 5% = 800 บาท
• Level 3: 50 × 500 × 3% = 750 บาท

รวม = 2,050 บาท/เดือน
```

**ข้อดี Unilevel:**
• ไม่จำกัดความกว้าง
• เข้าใจง่าย
• สร้างทีมได้อิสระ",
            ],
            [
                'title' => '👁️ การดู Genealogy (แผนผังสายงาน)',
                'content' => "**Genealogy** คือหน้าแสดงโครงสร้างสายงานของคุณ

**การเข้าถึง:**
ไปที่ **\"ทีมงาน\"** > **\"Genealogy\"**

**มุมมองที่รองรับ:**

**1. Tree View (แผนผังต้นไม้)**
```
        ┌─ สมชาย (Silver)
   You ─┤
        └─ สมหญิง (Bronze)
              └─ สมศักดิ์ (Member)
```

**2. Table View (ตาราง)**
| ชื่อ | Level | Rank | PV | สมาชิก |
|------|-------|------|-----|--------|
| สมชาย | 1 | Silver | 500 | 10 |
| สมหญิง | 1 | Bronze | 300 | 5 |
| สมศักดิ์ | 2 | Member | 100 | 0 |

**3. Matrix View (เมทริกซ์)**
• แสดงแบบ Binary 2×∞
• เห็นตำแหน่งซ้าย-ขวา
• ดู Spillover และตำแหน่งว่าง

**ข้อมูลที่แสดง:**
• ชื่อ/Username
• รูปโปรไฟล์
• Rank ปัจจุบัน
• Personal PV
• Group PV
• จำนวน Direct/Total Referrals
• วันที่สมัคร
• Active Status

**Filter และ Search:**
• ค้นหาตามชื่อ/Username
• Filter ตาม Level
• Filter ตาม Rank
• Filter ตามสถานะ (Active/Inactive)
• เรียงตาม PV/วันที่/ชื่อ",
            ],
            [
                'title' => '🎯 กลยุทธ์การสร้างทีม',
                'content' => "**5 ขั้นตอนสร้างทีมให้ประสบความสำเร็จ:**

**ขั้นตอนที่ 1: สร้างรากฐาน (Foundation)**

• เรียนรู้ระบบให้เข้าใจ 100%
• ใช้สินค้าด้วยตัวเอง
• มีผลลัพธ์/Story ให้แชร์
• เตรียม Presentation

**ขั้นตอนที่ 2: หา 5 คนแรก (Core Team)**

```
เป้าหมาย: หา 5 คนที่ \"ใช่\"

          คุณ
    /  /  |  \\  \\
   1  2   3   4   5
   ↓  ↓   ↓   ↓   ↓
  (แต่ละคนหา 5 คนต่อ)
```

• เริ่มจากคนรู้จัก
• คนที่มีเป้าหมายคล้ายกัน
• คนที่พร้อมเรียนรู้
• อย่าเลือกคนที่ \"ง่าย\" เลือกคนที่ \"ใช่\"

**ขั้นตอนที่ 3: สอนให้ทำซ้ำได้ (Duplication)**

```
สูตร: EDDC
E = Explain (อธิบาย)
D = Demonstrate (สาธิต)
D = Do together (ทำด้วยกัน)
C = Check (ตรวจสอบ)
```

**ขั้นตอนที่ 4: สร้างระบบ (System)**

• กลุ่ม LINE/Facebook สำหรับทีม
• Weekly Meeting
• Training Materials
• Recognition Program

**ขั้นตอนที่ 5: พัฒนา Leaders (Leadership)**

• Identify potential leaders
• 1-on-1 coaching
• ให้โอกาส lead กิจกรรม
• Celebrate ความสำเร็จ",
            ],
            [
                'title' => '📝 แบบฝึกหัดท้ายบท',
                'content' => "**แบบฝึกหัดที่ 1: ดู Genealogy**

1. ไปที่ \"ทีมงาน\" > \"Genealogy\"
2. ลองสลับมุมมอง Tree/Table/Matrix
3. ใช้ Filter ดูเฉพาะ Level 1
4. หา Member ที่มี PV สูงสุด
5. บันทึกข้อมูลสำคัญ

**แบบฝึกหัดที่ 2: คำนวณ Binary**

สมมติคุณมีสายงาน:
• ขาซ้าย: 2,000 PV
• ขาขวา: 3,500 PV
• Commission Rate: 10%

คำนวณ:
• Weak Leg = ______
• โบนัส = ______
• Carry Forward = ______

**แบบฝึกหัดที่ 3: คำนวณ Unilevel**

สมมติคุณมี:
• Level 1: 3 คน × 500 PV
• Level 2: 8 คน × 300 PV

คำนวณ (อัตรา L1=10%, L2=5%):
• Commission L1 = ______
• Commission L2 = ______
• รวม = ______

**แบบฝึกหัดที่ 4: วางแผนทีม**

เขียนรายชื่อ 10 คนที่คุณอยากแนะนำ:
1. ______ (ความสัมพันธ์: ______)
2. ______ (ความสัมพันธ์: ______)
... (ถึง 10)

จัดลำดับความสำคัญ: 1️⃣ 2️⃣ 3️⃣

**Checklist ความเข้าใจ:**

☐ เข้าใจความแตกต่าง Binary vs Unilevel
☐ คำนวณ Commission ได้
☐ ใช้งาน Genealogy ได้
☐ รู้กลยุทธ์สร้างทีมเบื้องต้น
☐ มี List คนที่จะแนะนำ",
            ],
        ];
    }

    /**
     * คอร์ส 8: ระบบ Rank และโบนัสตำแหน่ง - เนื้อหา
     */
    private function getCourse8Content(): array
    {
        return [
            [
                'title' => '🏆 ภาพรวมระบบ Rank',
                'content' => "**ระบบ Rank (ยศ/ตำแหน่ง)** คือระบบที่กำหนดระดับความสำเร็จและสิทธิประโยชน์ของสมาชิก

**ทำไมต้องมี Rank?**

• กระตุ้นให้สมาชิกพัฒนาตัวเอง
• กำหนดอัตราค่าคอมมิชชั่นที่สูงขึ้น
• ให้โบนัสและรางวัลพิเศษ
• สร้างเป้าหมายที่ชัดเจน

**ระดับ Rank ทั้งหมดใน Thaiprompt:**

```
┌─────────────────────────────────────────────────────┐
│                  RANK HIERARCHY                     │
├─────────────────────────────────────────────────────┤
│                                                     │
│  👑 Crown Diamond    ← สูงสุด (Top 0.1%)           │
│      ▲                                              │
│  💎 Diamond          ← ผู้นำระดับสูง                │
│      ▲                                              │
│  💜 Platinum         ← ผู้นำทีม                     │
│      ▲                                              │
│  🥇 Gold             ← นักธุรกิจมืออาชีพ            │
│      ▲                                              │
│  🥈 Silver           ← กำลังเติบโต                  │
│      ▲                                              │
│  🥉 Bronze           ← เริ่มสร้างทีม                │
│      ▲                                              │
│  ⭐ Member           ← สมาชิกเริ่มต้น               │
│                                                     │
└─────────────────────────────────────────────────────┘
```

**สิทธิประโยชน์ตาม Rank:**

| Rank | Commission Rate | Depth | โบนัส/เดือน |
|------|----------------|-------|-------------|
| Member | 10% | 3 Levels | - |
| Bronze | 12% | 4 Levels | 1,000 ฿ |
| Silver | 15% | 5 Levels | 5,000 ฿ |
| Gold | 18% | 6 Levels | 15,000 ฿ |
| Platinum | 22% | 8 Levels | 35,000 ฿ |
| Diamond | 25% | 10 Levels | 80,000 ฿ |
| Crown Diamond | 30% | Unlimited | 200,000 ฿ |",
            ],
            [
                'title' => '📊 เงื่อนไขการเลื่อน Rank',
                'content' => "**ปัจจัยที่ใช้คำนวณ Rank:**

**1. Personal PV (PPV)**
• ยอด PV จากการซื้อสินค้าของตัวเอง
• ต้องรักษายอดขั้นต่ำทุกเดือน

**2. Group PV (GPV)**
• ยอด PV รวมของทั้งทีม
• รวมทุก Level ในสายงาน

**3. Direct Referrals**
• จำนวนสมาชิกที่แนะนำโดยตรง
• ต้อง Active (มียอด PV)

**4. Qualified Legs**
• จำนวนขาที่มียอดถึงเกณฑ์
• สำหรับ Binary Plan

**ตารางเงื่อนไขแต่ละ Rank:**

```
┌──────────────┬────────┬─────────┬─────────┬────────────┐
│    Rank      │  PPV   │   GPV   │ Direct  │ Qual.Legs  │
├──────────────┼────────┼─────────┼─────────┼────────────┤
│ Member       │    0   │      0  │    0    │     0      │
│ Bronze       │  100   │  1,000  │    3    │     1      │
│ Silver       │  200   │  5,000  │    5    │     2      │
│ Gold         │  300   │ 15,000  │   10    │     2      │
│ Platinum     │  500   │ 50,000  │   20    │     3      │
│ Diamond      │  800   │150,000  │   50    │     4      │
│ Crown Diamond│ 1,000  │500,000  │  100    │     5      │
└──────────────┴────────┴─────────┴─────────┴────────────┘
```

**หมายเหตุ:**
• ต้องผ่านเงื่อนไข **ทุกข้อ** จึงจะเลื่อน Rank
• Rank จะอัพเดททุกวันที่ 1 ของเดือน
• มีระบบ Maintain - ถ้าไม่ถึงเกณฑ์อาจถูกลด Rank",
            ],
            [
                'title' => '💰 ประเภทโบนัสและรางวัล',
                'content' => "**โบนัสที่ได้รับตาม Rank:**

**1. 💵 Rank Bonus (โบนัสตำแหน่ง)**
• ได้รับทุกเดือนตาม Rank
• จ่ายอัตโนมัติวันที่ 15

```
Bronze:        1,000 ฿/เดือน
Silver:        5,000 ฿/เดือน
Gold:         15,000 ฿/เดือน
Platinum:     35,000 ฿/เดือน
Diamond:      80,000 ฿/เดือน
Crown Diamond: 200,000 ฿/เดือน
```

**2. 🎯 Leadership Bonus (โบนัสผู้นำ)**
• ได้จากยอด GPV ของทีม
• คำนวณ: GPV × Leadership Rate

```
Gold+:      1% ของ GPV
Platinum+:  2% ของ GPV
Diamond+:   3% ของ GPV
```

**3. 🏆 Achievement Bonus (โบนัสความสำเร็จ)**
• ได้ครั้งเดียวเมื่อเลื่อน Rank

```
Bronze → Silver:    2,000 ฿
Silver → Gold:     10,000 ฿
Gold → Platinum:   30,000 ฿
Platinum → Diamond: 100,000 ฿
Diamond → Crown:   500,000 ฿
```

**4. 🎁 รางวัลพิเศษ**
• ✈️ **Travel Incentive** - ทริปท่องเที่ยว (Platinum+)
• 🚗 **Car Bonus** - โบนัสรถยนต์ (Diamond+)
• 🏠 **House Bonus** - โบนัสบ้าน (Crown Diamond)
• 💍 **Luxury Rewards** - ของรางวัลหรูหรา",
            ],
            [
                'title' => '📈 การรักษา Rank (Maintenance)',
                'content' => "**ระบบ Rank Maintenance:**

เมื่อได้ Rank แล้ว ต้องรักษายอดทุกเดือนเพื่อคง Rank ไว้

**เงื่อนไข Maintenance:**

| Rank | PPV ขั้นต่ำ | GPV ขั้นต่ำ |
|------|------------|------------|
| Bronze | 50 | 500 |
| Silver | 100 | 2,500 |
| Gold | 150 | 7,500 |
| Platinum | 250 | 25,000 |
| Diamond | 400 | 75,000 |
| Crown Diamond | 500 | 250,000 |

**กรณีไม่ถึงเกณฑ์:**

```
เดือนที่ 1: ⚠️ Warning - ยังคง Rank แต่ได้รับแจ้งเตือน
เดือนที่ 2: ⏸️ Suspended - หยุดรับโบนัส Rank ชั่วคราว
เดือนที่ 3: ⬇️ Demoted - ลด Rank ลง 1 ระดับ
```

**วิธีป้องกันการถูกลด Rank:**

✅ ตั้ง Auto-ship สินค้ารายเดือน
✅ ติดตามยอด GPV ของทีมอย่างใกล้ชิด
✅ กระตุ้นทีมให้ Active ก่อนสิ้นเดือน
✅ ใช้ Dashboard ดู Progress
✅ ตั้ง Goal และแจ้งเตือน

**Grace Period:**
• มี Grace Period 7 วัน หลังสิ้นเดือน
• สามารถทำยอดเพิ่มได้ในช่วงนี้",
            ],
            [
                'title' => '🎯 กลยุทธ์การเลื่อน Rank',
                'content' => "**5 กลยุทธ์เลื่อน Rank อย่างมั่นคง:**

**1. 📊 วางแผนล่วงหน้า**

```
ตัวอย่าง: จาก Silver → Gold

ต้องการ:
• PPV: 300 (ปัจจุบัน 200) → ต้องเพิ่ม 100
• GPV: 15,000 (ปัจจุบัน 8,000) → ต้องเพิ่ม 7,000
• Direct: 10 (ปัจจุบัน 6) → ต้องเพิ่ม 4

แผน:
• เพิ่ม Auto-ship ตัวเอง +100 PV
• หา Direct ใหม่ 4 คน × 500 PV = 2,000 PV
• กระตุ้นทีมเดิมเพิ่ม 5,000 PV
```

**2. 🎯 Focus ที่ Qualified Legs**

• สร้าง 2-3 ขาที่แข็งแกร่ง
• ช่วย Downline ใน Leg เหล่านั้นให้เติบโต
• อย่ากระจายมากเกินไป

**3. 👥 Duplicate ตัวเอง**

• สอนทีมให้ทำเหมือนที่คุณทำ
• สร้าง Leader ในแต่ละสาย
• เมื่อทีมเก่ง GPV จะเพิ่มเอง

**4. 📅 Timing ที่เหมาะสม**

• Push ยอดช่วงปลายเดือน
• ใช้ Promotion ช่วยกระตุ้น
• จัด Event ให้ทีม

**5. 🔄 สม่ำเสมอ**

• อย่ารอจนนาทีสุดท้าย
• ทำทุกวัน ไม่ใช่ทุกเดือน
• Track Progress ทุกสัปดาห์",
            ],
            [
                'title' => '📝 แบบฝึกหัดท้ายบท',
                'content' => "**แบบฝึกหัดที่ 1: ตรวจสอบ Rank ปัจจุบัน**

1. ไปที่ \"โปรไฟล์\" หรือ \"Dashboard\"
2. ดู Rank ปัจจุบันของคุณ
3. บันทึกข้อมูล:
   - Rank ปัจจุบัน: ______
   - PPV เดือนนี้: ______
   - GPV เดือนนี้: ______
   - Direct Referrals: ______

**แบบฝึกหัดที่ 2: คำนวณเป้าหมาย**

สมมติคุณเป็น Bronze และต้องการเป็น Silver:

เงื่อนไข Silver:
• PPV: 200
• GPV: 5,000
• Direct: 5
• Qualified Legs: 2

ถ้าปัจจุบัน PPV=120, GPV=3,000, Direct=4

คำนวณสิ่งที่ต้องทำ:
• PPV ต้องเพิ่ม: ______
• GPV ต้องเพิ่ม: ______
• Direct ต้องเพิ่ม: ______

**แบบฝึกหัดที่ 3: วางแผน 3 เดือน**

เขียนแผนเลื่อน Rank:

เดือนที่ 1:
- เป้า PPV: ______
- เป้า GPV: ______
- กิจกรรม: ______

เดือนที่ 2:
- เป้า PPV: ______
- เป้า GPV: ______
- กิจกรรม: ______

เดือนที่ 3:
- เป้า PPV: ______
- เป้า GPV: ______
- กิจกรรม: ______

**Checklist ความเข้าใจ:**

☐ รู้จัก Rank ทั้งหมดในระบบ
☐ เข้าใจเงื่อนไขการเลื่อน Rank
☐ รู้ประเภทโบนัสที่ได้รับ
☐ เข้าใจระบบ Maintenance
☐ วางแผนเลื่อน Rank ได้",
            ],
        ];
    }

    /**
     * คอร์ส 8: ระบบ Rank และโบนัสตำแหน่ง - Quiz
     */
    private function getCourse8Quiz(): array
    {
        return [
            'time_limit' => 15,
            'description' => 'ทดสอบความเข้าใจเกี่ยวกับระบบ Rank และโบนัส',
            'questions' => [
                [
                    'question' => 'Rank สูงสุดในระบบ Thaiprompt คือ?',
                    'answers' => [
                        ['text' => 'Diamond', 'is_correct' => false],
                        ['text' => 'Platinum', 'is_correct' => false],
                        ['text' => 'Crown Diamond', 'is_correct' => true],
                        ['text' => 'Gold', 'is_correct' => false],
                    ],
                    'explanation' => 'Crown Diamond คือ Rank สูงสุดในระบบ มีสิทธิประโยชน์สูงสุดและโบนัส 200,000 บาท/เดือน',
                ],
                [
                    'question' => 'PPV ย่อมาจากอะไร?',
                    'answers' => [
                        ['text' => 'Group PV', 'is_correct' => false],
                        ['text' => 'Personal PV (ยอด PV ส่วนตัว)', 'is_correct' => true],
                        ['text' => 'Pending PV', 'is_correct' => false],
                        ['text' => 'Premium PV', 'is_correct' => false],
                    ],
                    'explanation' => 'PPV = Personal PV คือยอด PV จากการซื้อสินค้าของตัวเอง',
                ],
                [
                    'question' => 'Rank Bonus จ่ายเมื่อไร?',
                    'answers' => [
                        ['text' => 'ทุกวัน', 'is_correct' => false],
                        ['text' => 'วันที่ 15 ของทุกเดือน', 'is_correct' => true],
                        ['text' => 'วันที่ 1 ของทุกเดือน', 'is_correct' => false],
                        ['text' => 'สิ้นเดือน', 'is_correct' => false],
                    ],
                    'explanation' => 'Rank Bonus จ่ายอัตโนมัติทุกวันที่ 15 ของเดือน',
                ],
                [
                    'question' => 'ถ้าไม่ถึงเกณฑ์ Maintenance ติดต่อกัน 3 เดือน จะเกิดอะไรขึ้น?',
                    'answers' => [
                        ['text' => 'ไม่มีอะไรเกิดขึ้น', 'is_correct' => false],
                        ['text' => 'ถูกลด Rank ลง 1 ระดับ', 'is_correct' => true],
                        ['text' => 'ถูกยกเลิกบัญชี', 'is_correct' => false],
                        ['text' => 'ต้องจ่ายค่าปรับ', 'is_correct' => false],
                    ],
                    'explanation' => 'เดือน 1 = Warning, เดือน 2 = Suspended, เดือน 3 = Demoted (ลด Rank)',
                ],
                [
                    'question' => 'Achievement Bonus จาก Silver → Gold ได้เท่าไร?',
                    'answers' => [
                        ['text' => '2,000 บาท', 'is_correct' => false],
                        ['text' => '10,000 บาท', 'is_correct' => true],
                        ['text' => '30,000 บาท', 'is_correct' => false],
                        ['text' => '5,000 บาท', 'is_correct' => false],
                    ],
                    'explanation' => 'Achievement Bonus จาก Silver → Gold คือ 10,000 บาท (ได้ครั้งเดียวเมื่อเลื่อน Rank)',
                ],
                [
                    'question' => 'Qualified Legs คืออะไร?',
                    'answers' => [
                        ['text' => 'จำนวนสมาชิกทั้งหมด', 'is_correct' => false],
                        ['text' => 'จำนวนขาที่มียอดถึงเกณฑ์', 'is_correct' => true],
                        ['text' => 'จำนวน Direct Referrals', 'is_correct' => false],
                        ['text' => 'ยอด GPV', 'is_correct' => false],
                    ],
                    'explanation' => 'Qualified Legs คือจำนวนขา (สาย) ที่มียอด PV ถึงเกณฑ์ที่กำหนด',
                ],
                [
                    'question' => 'Grace Period หลังสิ้นเดือนมีกี่วัน?',
                    'answers' => [
                        ['text' => '3 วัน', 'is_correct' => false],
                        ['text' => '7 วัน', 'is_correct' => true],
                        ['text' => '14 วัน', 'is_correct' => false],
                        ['text' => 'ไม่มี Grace Period', 'is_correct' => false],
                    ],
                    'explanation' => 'มี Grace Period 7 วันหลังสิ้นเดือน สามารถทำยอดเพิ่มได้ในช่วงนี้',
                ],
            ],
        ];
    }

    /**
     * คอร์ส 9: AI Bot และระบบ Chatbot - เนื้อหา
     */
    private function getCourse9Content(): array
    {
        return [
            [
                'title' => '🤖 ภาพรวม AI Bot ใน Thaiprompt',
                'content' => "
## AI Bot คืออะไร?

AI Bot ใน Thaiprompt คือระบบ **Chatbot อัจฉริยะ** ที่ขับเคลื่อนด้วย AI สามารถโต้ตอบกับลูกค้าได้อัตโนมัติ ช่วยเพิ่มยอดขายและบริการลูกค้าได้ 24/7

### 🎯 ประเภท AI Bot ในระบบ

| ประเภท | แพลตฟอร์ม | ความสามารถ |
|--------|-----------|------------|
| **LINE Bot** | LINE Official Account | ตอบแชท, ส่งโปรโมชั่น, รับออเดอร์ |
| **Facebook Bot** | Messenger | ตอบ Inbox, Product Catalog |
| **Web Chatbot** | เว็บไซต์ | Live Chat, FAQ อัตโนมัติ |
| **Instagram Bot** | Instagram DM | ตอบ DM, Story Reply |
| **WhatsApp Bot** | WhatsApp Business | ข้อความ, สถานะออเดอร์ |

### 🧠 เทคโนโลยี AI ที่ใช้

```
┌─────────────────────────────────────────────────┐
│           AI Engine Architecture                │
├─────────────────────────────────────────────────┤
│                                                 │
│   ┌─────────────┐    ┌─────────────────────┐   │
│   │   NLU/NLP   │───▶│  Intent Detection   │   │
│   │   Engine    │    │  (ตรวจจับเจตนา)      │   │
│   └─────────────┘    └─────────────────────┘   │
│         │                      │                │
│         ▼                      ▼                │
│   ┌─────────────┐    ┌─────────────────────┐   │
│   │   Entity    │    │  Response Generator │   │
│   │  Extraction │    │  (สร้างคำตอบ)        │   │
│   └─────────────┘    └─────────────────────┘   │
│                              │                  │
│                              ▼                  │
│                    ┌─────────────────────┐     │
│                    │   Product/Order     │     │
│                    │   Database          │     │
│                    └─────────────────────┘     │
└─────────────────────────────────────────────────┘
```

### ⭐ ความสามารถหลัก

1. **ตอบคำถามอัตโนมัติ** - FAQ, ข้อมูลสินค้า, ราคา
2. **รับออเดอร์** - สั่งซื้อผ่าน Chat ได้เลย
3. **ติดตามพัสดุ** - เช็คสถานะจัดส่งอัตโนมัติ
4. **แนะนำสินค้า** - AI วิเคราะห์และแนะนำตามพฤติกรรม
5. **โอนให้พนักงาน** - Handoff เมื่อต้องการคนจริง
                ",
            ],
            [
                'title' => '⚙️ การตั้งค่า LINE Bot',
                'content' => "
## การสร้างและเชื่อมต่อ LINE Bot

### 📋 ขั้นตอนการสร้าง LINE Bot

**Step 1: สร้าง LINE Official Account**

1. ไปที่ [LINE Official Account Manager](https://manager.line.biz/)
2. คลิก \"สร้างบัญชี\" → เลือกประเภทธุรกิจ
3. กรอกข้อมูลธุรกิจ → ยืนยันอีเมล
4. เข้า Dashboard → ไปที่ Settings

**Step 2: เปิดใช้งาน Messaging API**

```
LINE Official Account Manager
    │
    ├── Settings (ตั้งค่า)
    │       │
    │       └── Messaging API
    │               │
    │               ├── Enable Messaging API ✓
    │               │
    │               └── สร้าง Channel ใน LINE Developers
    │
    └── LINE Developers Console
            │
            ├── Channel ID
            ├── Channel Secret
            └── Channel Access Token (Long-lived)
```

**Step 3: เชื่อมต่อกับ Thaiprompt**

| ฟิลด์ | คำอธิบาย | ตัวอย่าง |
|------|---------|---------|
| Channel ID | รหัส Channel | 1657XXXXXX |
| Channel Secret | รหัสลับ | 8d4a5b6c7d... |
| Access Token | Token สำหรับส่งข้อความ | eyJhbGciOi... |
| Webhook URL | URL รับ Events | https://yourdomain.com/webhook/line |

### 🔧 การตั้งค่า Webhook

```php
// Webhook URL ที่ต้องตั้งใน LINE Developers
https://your-thaiprompt-domain.com/api/line/webhook

// Events ที่รองรับ:
// - message (ข้อความ)
// - follow (มีคนเพิ่มเพื่อน)
// - unfollow (ยกเลิกติดตาม)
// - postback (กดปุ่ม)
// - beacon (LINE Beacon)
```

### 📱 Rich Menu

```
┌──────────────────────────────────────┐
│          Rich Menu (6 ปุ่ม)          │
├────────────┬────────────┬────────────┤
│   🛒 สั่งซื้อ  │  📦 ติดตาม   │  💬 สอบถาม  │
├────────────┼────────────┼────────────┤
│   🎁 โปรโมชั่น │  👤 บัญชี    │  📞 ติดต่อ   │
└────────────┴────────────┴────────────┘

การตั้งค่า: Admin → AI Bot → LINE → Rich Menu
```

### ⚡ Quick Reply Buttons

```json
{
  \"type\": \"text\",
  \"text\": \"ต้องการสอบถามเรื่องอะไรคะ?\",
  \"quickReply\": {
    \"items\": [
      { \"type\": \"action\", \"action\": { \"type\": \"message\", \"label\": \"สินค้า\", \"text\": \"สินค้า\" }},
      { \"type\": \"action\", \"action\": { \"type\": \"message\", \"label\": \"ราคา\", \"text\": \"ราคา\" }},
      { \"type\": \"action\", \"action\": { \"type\": \"message\", \"label\": \"โปรโมชั่น\", \"text\": \"โปรโมชั่น\" }}
    ]
  }
}
```
                ",
            ],
            [
                'title' => '💬 การสร้าง Conversation Flow',
                'content' => "
## ออกแบบ Flow การสนทนา

### 🎯 Intent (เจตนาของผู้ใช้)

**Intent คืออะไร?**
Intent คือ \"สิ่งที่ผู้ใช้ต้องการ\" เมื่อพิมพ์ข้อความมา

| Intent | ตัวอย่างข้อความ | Action |
|--------|---------------|--------|
| `greeting` | สวัสดี, หวัดดี, Hi | ทักทายกลับ |
| `product_inquiry` | มีสินค้าอะไรบ้าง, ขายอะไร | แสดง Catalog |
| `price_check` | ราคาเท่าไร, แพงไหม | แสดงราคา |
| `order_status` | พัสดุถึงไหน, เช็คสถานะ | ค้นหา Tracking |
| `order_create` | สั่งซื้อ, ซื้อสินค้า | เริ่ม Flow สั่งซื้อ |
| `human_agent` | ขอคุยกับคน, ติดต่อแอดมิน | Handoff |

### 🔀 Conversation Flow Diagram

```
[User Message]
      │
      ▼
┌─────────────────┐
│  NLU: Detect    │
│  Intent         │
└────────┬────────┘
         │
    ┌────┴────┐
    │ Intent? │
    └────┬────┘
         │
    ┌────┼────────┬───────────┬────────────┐
    ▼    ▼        ▼           ▼            ▼
greeting product  order     tracking    human
    │       │        │           │            │
    ▼       ▼        ▼           ▼            ▼
 \"สวัสดี\"  Catalog  Order     Status    Handoff
           List    Flow      API       to Admin
```

### 📝 การสร้าง Conversation Node

**ใน Admin Panel: AI Bot → Conversation Builder**

```
┌─────────────────────────────────────────────────────┐
│  Conversation Builder                               │
├─────────────────────────────────────────────────────┤
│                                                     │
│  [Start] ──▶ [ทักทาย] ──▶ [ถามความต้องการ]          │
│                              │                      │
│              ┌───────────────┼───────────────┐      │
│              ▼               ▼               ▼      │
│         [สินค้า]        [สั่งซื้อ]       [ติดต่อ]    │
│              │               │               │      │
│              ▼               ▼               ▼      │
│         [Catalog]      [Order Flow]    [Handoff]   │
│                                                     │
└─────────────────────────────────────────────────────┘
```

### 🎨 Node Types

| Type | Icon | Description | Use Case |
|------|------|-------------|----------|
| **Text** | 💬 | ข้อความธรรมดา | ทักทาย, อธิบาย |
| **Image** | 🖼️ | ส่งรูปภาพ | สินค้า, โปรโมชั่น |
| **Carousel** | 🎠 | สไลด์หลายรูป | Product Catalog |
| **Quick Reply** | ⚡ | ปุ่มตอบด่วน | เลือกตัวเลือก |
| **Condition** | ❓ | เงื่อนไข | แยกเส้นทาง |
| **API Call** | 🔌 | เรียก API | ดึงข้อมูลสด |
| **Handoff** | 👤 | โอนให้คน | ปัญหาซับซ้อน |

### 💡 Tips การออกแบบ Flow

1. **Keep it Simple** - อย่าซับซ้อนเกินไป (ไม่เกิน 5 levels)
2. **Always have Fallback** - มีทางออกเสมอเมื่อไม่เข้าใจ
3. **Confirm before Action** - ยืนยันก่อนทำ Action สำคัญ
4. **Provide Escape** - มีทางให้พูดกับคนจริงเสมอ
                ",
            ],
            [
                'title' => '📊 AI Training และ NLU',
                'content' => "
## การ Train AI ให้เข้าใจภาษา

### 🧠 Natural Language Understanding (NLU)

**NLU ทำหน้าที่อะไร?**

```
User Input: \"อยากซื้อสินค้า\"
      │
      ▼
┌─────────────────────────────────────────┐
│              NLU Engine                  │
├─────────────────────────────────────────┤
│                                          │
│  1. Tokenization (แยกคำ)                 │
│     [\"อยาก\", \"ซื้อ\", \"สินค้า\"]          │
│                                          │
│  2. Intent Classification               │
│     → order_create (confidence: 92%)    │
│                                          │
│  3. Entity Extraction                   │
│     → product_type: null                │
│     → quantity: null                    │
│                                          │
└─────────────────────────────────────────┘
      │
      ▼
Action: Start Order Flow
```

### 📚 Training Data

**การเพิ่ม Training Phrases (ประโยคตัวอย่าง)**

| Intent | Training Phrases |
|--------|-----------------|
| `order_create` | \"สั่งซื้อ\", \"อยากซื้อ\", \"ซื้อหน่อย\", \"สั่งของ\", \"order\" |
| `product_inquiry` | \"มีอะไรขาย\", \"สินค้ามีอะไร\", \"ขายอะไรบ้าง\", \"catalog\" |
| `price_check` | \"ราคาเท่าไร\", \"กี่บาท\", \"แพงไหม\", \"ลดราคาไหม\" |

**Best Practices:**
- ✅ เพิ่มอย่างน้อย 10-20 ประโยคต่อ Intent
- ✅ รวมคำพูดแบบต่างๆ (ทางการ, ไม่ทางการ, ภาษาวัยรุ่น)
- ✅ รวม Typo ที่พบบ่อย
- ✅ อัพเดทจาก Unhandled Messages

### 🎯 Entity Extraction

**Entity คืออะไร?**
Entity คือ \"ข้อมูลสำคัญ\" ที่แยกออกมาจากประโยค

```
Input: \"สั่ง iPhone 15 Pro 3 เครื่อง ส่งไปเชียงใหม่\"

Extracted Entities:
┌─────────────────────────────────────────┐
│  product_name: \"iPhone 15 Pro\"         │
│  quantity: 3                            │
│  unit: \"เครื่อง\"                        │
│  shipping_location: \"เชียงใหม่\"         │
└─────────────────────────────────────────┘
```

### 📈 Model Improvement

**Continuous Learning Loop:**

```
┌──────────────────────────────────────────────────┐
│                                                  │
│   ┌─────────┐   ┌─────────┐   ┌─────────┐       │
│   │ Deploy  │──▶│ Collect │──▶│ Review  │       │
│   │  Bot    │   │  Logs   │   │  Errors │       │
│   └─────────┘   └─────────┘   └────┬────┘       │
│        ▲                           │            │
│        │                           ▼            │
│   ┌─────────┐              ┌─────────────┐      │
│   │ Re-train│◀─────────────│ Add Training│      │
│   │  Model  │              │    Data     │      │
│   └─────────┘              └─────────────┘      │
│                                                  │
└──────────────────────────────────────────────────┘
```

### 📊 Performance Metrics

| Metric | Target | Description |
|--------|--------|-------------|
| **Intent Accuracy** | ≥ 85% | ตรวจจับ Intent ถูกต้อง |
| **Entity Accuracy** | ≥ 80% | แยก Entity ถูกต้อง |
| **Fallback Rate** | ≤ 15% | อัตราตอบไม่ได้ |
| **Resolution Rate** | ≥ 70% | แก้ปัญหาได้โดยไม่ต้องใช้คน |
                ",
            ],
            [
                'title' => '🔌 การเชื่อมต่อ API และ Automation',
                'content' => "
## เชื่อมต่อกับระบบอื่น

### 🔗 API Integration

**ใน Admin: AI Bot → Integrations**

| Integration | Use Case | Example |
|-------------|----------|---------|
| **Product API** | ดึงข้อมูลสินค้า | ราคา, สต็อก, รายละเอียด |
| **Order API** | สร้าง/เช็คออเดอร์ | สถานะ, Tracking |
| **User API** | ข้อมูลลูกค้า | ประวัติซื้อ, แต้ม |
| **Payment API** | ชำระเงิน | QR Code, Link จ่ายเงิน |
| **Shipping API** | ติดตามพัสดุ | Flash, Kerry, ไปรษณีย์ |

### 📡 Webhook Events

**Events ที่ส่งออกจาก Bot:**

```json
// เมื่อมีข้อความใหม่
{
  \"event\": \"message.received\",
  \"platform\": \"line\",
  \"user_id\": \"U1234567890\",
  \"message\": {
    \"type\": \"text\",
    \"text\": \"สวัสดีครับ\"
  },
  \"timestamp\": \"2024-01-15T10:30:00Z\"
}

// เมื่อสร้างออเดอร์สำเร็จ
{
  \"event\": \"order.created\",
  \"order_id\": \"ORD-20240115-001\",
  \"user_id\": \"U1234567890\",
  \"total\": 1500,
  \"items\": [...]
}
```

### ⚡ Automation Rules

**การตั้ง Auto-Response:**

| Trigger | Condition | Action |
|---------|-----------|--------|
| **Time-based** | นอกเวลาทำการ | ส่งข้อความ \"ติดต่อกลับในวันถัดไป\" |
| **Keyword** | มีคำว่า \"urgent\" | แจ้ง Admin ทันที |
| **User Segment** | VIP Customer | ใช้ Flow พิเศษ |
| **Cart Abandon** | ทิ้ง Cart 30 นาที | ส่ง Reminder |

### 🔄 Workflow Automation

```
┌─────────────────────────────────────────────────────┐
│              Order Automation Flow                   │
├─────────────────────────────────────────────────────┤
│                                                      │
│  [ลูกค้าสั่งซื้อ] ──▶ [Bot ยืนยัน]                     │
│         │                  │                         │
│         ▼                  ▼                         │
│  [สร้าง Order]      [ส่ง QR Payment]                 │
│         │                  │                         │
│         └────────┬─────────┘                         │
│                  ▼                                   │
│         [รอชำระเงิน 24 ชม.]                          │
│                  │                                   │
│         ┌───────┴───────┐                           │
│         ▼               ▼                           │
│    [ชำระแล้ว]      [ไม่ชำระ]                         │
│         │               │                           │
│         ▼               ▼                           │
│   [แจ้ง Shipping]  [ยกเลิก Order]                    │
│         │               │                           │
│         ▼               ▼                           │
│   [ส่ง Tracking]   [Reminder ครั้งที่ 2]             │
│                                                      │
└─────────────────────────────────────────────────────┘
```

### 📊 Analytics Dashboard

| Metric | Description |
|--------|-------------|
| **Messages/Day** | ข้อความต่อวัน |
| **Response Time** | เวลาตอบเฉลี่ย |
| **Bot vs Human** | อัตราส่วน Bot ตอบ vs คนตอบ |
| **Conversion Rate** | อัตราการซื้อผ่าน Bot |
| **CSAT Score** | ความพึงพอใจลูกค้า |
                ",
            ],
            [
                'title' => '📝 แบบฝึกหัดท้ายบท',
                'content' => "
## แบบฝึกหัด: AI Bot และ Chatbot

### 📋 แบบฝึกหัดที่ 1: วางแผน Bot Strategy

**โจทย์:** คุณเปิดร้านขายเสื้อผ้าออนไลน์ ต้องการใช้ LINE Bot

**ให้ออกแบบ:**
1. Rich Menu 6 ปุ่ม - กำหนดว่าแต่ละปุ่มทำอะไร
2. Intent อย่างน้อย 5 Intent พร้อม Training Phrases
3. Conversation Flow สำหรับการสั่งซื้อเสื้อผ้า

```
คำตอบตัวอย่าง Rich Menu:
┌──────────┬──────────┬──────────┐
│  👗 สินค้า │  💰 โปรโม  │  📦 สถานะ  │
├──────────┼──────────┼──────────┤
│  📐 ไซส์   │  🛒 ตะกร้า │  📞 ติดต่อ  │
└──────────┴──────────┴──────────┘
```

---

### 📋 แบบฝึกหัดที่ 2: เขียน Intent & Entities

**โจทย์:** สำหรับ Intent \"check_order_status\"

**ให้เขียน:**
1. Training Phrases 10 ประโยค
2. Entity ที่ต้อง Extract
3. Response Template

```
Training Phrases ตัวอย่าง:
1. \"เช็คสถานะพัสดุ\"
2. \"ของถึงไหนแล้ว\"
3. \"tracking ORD12345\"
4. \"ออเดอร์ของฉันไปถึงไหน\"
5. ...

Entity:
- order_id: ORD12345
- tracking_number: TH1234567890

Response:
\"ออเดอร์ {order_id} สถานะ: {status}
📦 Tracking: {tracking_number}
🚚 คาดว่าจะได้รับภายใน {estimated_date}\"
```

---

### 📋 แบบฝึกหัดที่ 3: Debug Conversation

**โจทย์:** Bot ตอบผิดในกรณีนี้:

```
User: \"ไม่อยากซื้อแล้ว ยกเลิกออเดอร์\"
Bot: \"ยินดีต้อนรับสู่การสั่งซื้อ กรุณาเลือกสินค้า\"
```

**วิเคราะห์:**
1. ปัญหาเกิดจากอะไร?
2. จะแก้ไขอย่างไร?
3. ต้องเพิ่ม Training Data อะไร?

---

### 📋 แบบฝึกหัดที่ 4: ออกแบบ Automation

**โจทย์:** ลูกค้าสั่งซื้อแล้วไม่ชำระเงินภายใน 24 ชม.

**ให้ออกแบบ Automation Flow:**
1. เวลาไหนส่ง Reminder?
2. ข้อความ Reminder พูดอะไร?
3. ถ้ายังไม่จ่าย ทำอย่างไรต่อ?

---

### ✅ Checklist ก่อนสอบ

- [ ] เข้าใจการสร้างและเชื่อมต่อ LINE Bot
- [ ] สามารถอธิบาย Intent และ Entity ได้
- [ ] ออกแบบ Conversation Flow ได้
- [ ] เข้าใจ NLU และการ Train Model
- [ ] ตั้งค่า Automation Rules ได้
- [ ] อ่าน Analytics Dashboard เป็น
                ",
            ],
        ];
    }

    /**
     * คอร์ส 9: AI Bot และระบบ Chatbot - Quiz
     */
    private function getCourse9Quiz(): array
    {
        return [
            'time_limit' => 15,
            'description' => 'ทดสอบความเข้าใจเกี่ยวกับ AI Bot และระบบ Chatbot',
            'questions' => [
                [
                    'question' => 'NLU ย่อมาจากอะไร?',
                    'answers' => [
                        ['text' => 'Natural Language Understanding', 'is_correct' => true],
                        ['text' => 'Network Language Unit', 'is_correct' => false],
                        ['text' => 'New Language Update', 'is_correct' => false],
                        ['text' => 'Natural Learning Unit', 'is_correct' => false],
                    ],
                    'explanation' => 'NLU = Natural Language Understanding คือเทคโนโลยีที่ช่วยให้ AI เข้าใจภาษามนุษย์',
                ],
                [
                    'question' => 'Intent ในระบบ Chatbot หมายถึง?',
                    'answers' => [
                        ['text' => 'ชื่อของ Bot', 'is_correct' => false],
                        ['text' => 'สิ่งที่ผู้ใช้ต้องการ/เจตนา', 'is_correct' => true],
                        ['text' => 'รูปภาพที่ส่ง', 'is_correct' => false],
                        ['text' => 'ราคาสินค้า', 'is_correct' => false],
                    ],
                    'explanation' => 'Intent คือ \"เจตนา\" หรือ \"สิ่งที่ผู้ใช้ต้องการ\" เมื่อพิมพ์ข้อความมา',
                ],
                [
                    'question' => 'Rich Menu ใน LINE Bot มีได้สูงสุดกี่ปุ่ม?',
                    'answers' => [
                        ['text' => '3 ปุ่ม', 'is_correct' => false],
                        ['text' => '6 ปุ่ม', 'is_correct' => true],
                        ['text' => '9 ปุ่ม', 'is_correct' => false],
                        ['text' => '12 ปุ่ม', 'is_correct' => false],
                    ],
                    'explanation' => 'Rich Menu รองรับได้สูงสุด 6 ปุ่ม แบ่งเป็น 2 แถว แถวละ 3 ปุ่ม',
                ],
                [
                    'question' => 'Entity Extraction ทำหน้าที่อะไร?',
                    'answers' => [
                        ['text' => 'ส่งข้อความหาลูกค้า', 'is_correct' => false],
                        ['text' => 'แยกข้อมูลสำคัญจากประโยค', 'is_correct' => true],
                        ['text' => 'ลบข้อความเก่า', 'is_correct' => false],
                        ['text' => 'สร้าง Rich Menu', 'is_correct' => false],
                    ],
                    'explanation' => 'Entity Extraction แยกข้อมูลสำคัญจากประโยค เช่น ชื่อสินค้า, จำนวน, ที่อยู่',
                ],
                [
                    'question' => 'Handoff ในระบบ Chatbot คืออะไร?',
                    'answers' => [
                        ['text' => 'ปิด Bot', 'is_correct' => false],
                        ['text' => 'โอนการสนทนาให้พนักงานจริง', 'is_correct' => true],
                        ['text' => 'ส่งอีเมล', 'is_correct' => false],
                        ['text' => 'ยกเลิกออเดอร์', 'is_correct' => false],
                    ],
                    'explanation' => 'Handoff คือการโอนการสนทนาจาก Bot ไปให้พนักงานจริงเมื่อต้องการ',
                ],
                [
                    'question' => 'Fallback Rate ที่ดีควรเป็นเท่าไร?',
                    'answers' => [
                        ['text' => '≤ 15%', 'is_correct' => true],
                        ['text' => '≤ 50%', 'is_correct' => false],
                        ['text' => '≤ 80%', 'is_correct' => false],
                        ['text' => '100%', 'is_correct' => false],
                    ],
                    'explanation' => 'Fallback Rate ที่ดีควร ≤ 15% หมายความว่า Bot ตอบได้มากกว่า 85%',
                ],
                [
                    'question' => 'Training Phrases ควรมีอย่างน้อยกี่ประโยคต่อ Intent?',
                    'answers' => [
                        ['text' => '1-2 ประโยค', 'is_correct' => false],
                        ['text' => '5 ประโยค', 'is_correct' => false],
                        ['text' => '10-20 ประโยค', 'is_correct' => true],
                        ['text' => '100 ประโยค', 'is_correct' => false],
                    ],
                    'explanation' => 'ควรมี 10-20 ประโยคต่อ Intent เพื่อให้ AI เรียนรู้รูปแบบต่างๆ ได้ดี',
                ],
            ],
        ];
    }

    /**
     * คอร์ส 10: การจัดการ E-Commerce และร้านค้า - เนื้อหา
     */
    private function getCourse10Content(): array
    {
        return [
            [
                'title' => '🛍️ ภาพรวมระบบ E-Commerce',
                'content' => "
## E-Commerce ใน Thaiprompt คืออะไร?

ระบบ E-Commerce ใน Thaiprompt เป็น **Multi-Vendor Marketplace** ที่รองรับผู้ขายหลายราย พร้อมระบบ Affiliate ครบวงจร

### 🏪 ประเภทร้านค้าในระบบ

| ประเภท | คำอธิบาย | สิทธิประโยชน์ |
|--------|---------|--------------|
| **Individual Seller** | ผู้ขายรายบุคคล | ขายสินค้าเบื้องต้น |
| **Business Seller** | ผู้ขายแบบธุรกิจ | ร้านค้าหลายสาขา |
| **Brand Store** | แบรนด์อย่างเป็นทางการ | Badge + Priority |
| **Affiliate Seller** | ขายพร้อมแนะนำ | Commission + Bonus |

### 🔗 โครงสร้างระบบ

```
┌───────────────────────────────────────────────────────┐
│                  E-Commerce Platform                   │
├───────────────────────────────────────────────────────┤
│                                                        │
│   ┌─────────────┐   ┌─────────────┐   ┌────────────┐  │
│   │   Product   │   │   Order     │   │  Payment   │  │
│   │  Management │   │  Management │   │   Gateway  │  │
│   └──────┬──────┘   └──────┬──────┘   └─────┬──────┘  │
│          │                 │                 │         │
│          └─────────────────┼─────────────────┘         │
│                            │                           │
│                     ┌──────┴──────┐                    │
│                     │   Shipping  │                    │
│                     │   & Fulfillment │                │
│                     └─────────────┘                    │
│                            │                           │
│   ┌─────────────┐   ┌──────┴──────┐   ┌────────────┐  │
│   │  Affiliate  │   │   Report    │   │  Customer  │  │
│   │   System    │   │  Analytics  │   │   Service  │  │
│   └─────────────┘   └─────────────┘   └────────────┘  │
│                                                        │
└───────────────────────────────────────────────────────┘
```

### ⭐ ฟีเจอร์หลัก

1. **Multi-Vendor** - รองรับผู้ขายหลายราย
2. **Inventory Management** - จัดการสต็อกสินค้า
3. **Multiple Payment** - หลายช่องทางชำระเงิน
4. **Shipping Integration** - เชื่อมต่อขนส่งหลายบริษัท
5. **Analytics Dashboard** - รายงานการขายแบบเรียลไทม์
6. **Affiliate Commission** - ระบบค่าคอมมิชชั่นอัตโนมัติ
                ",
            ],
            [
                'title' => '📦 การจัดการสินค้า (Product Management)',
                'content' => "
## การเพิ่มและจัดการสินค้า

### 📋 ขั้นตอนการเพิ่มสินค้าใหม่

**Seller Dashboard → Products → Add New Product**

```
┌────────────────────────────────────────────────────────┐
│                  Add New Product                       │
├────────────────────────────────────────────────────────┤
│                                                        │
│  1. Basic Information (ข้อมูลพื้นฐาน)                   │
│     ├── Product Name (ชื่อสินค้า)                      │
│     ├── SKU (รหัสสินค้า)                               │
│     ├── Category (หมวดหมู่)                            │
│     └── Brand (แบรนด์)                                 │
│                                                        │
│  2. Description (รายละเอียด)                           │
│     ├── Short Description                              │
│     └── Full Description (HTML Editor)                │
│                                                        │
│  3. Media (รูปภาพ/วิดีโอ)                              │
│     ├── Main Image (1200x1200px)                      │
│     ├── Gallery (สูงสุด 10 รูป)                        │
│     └── Video URL (Optional)                          │
│                                                        │
│  4. Pricing (ราคา)                                     │
│     ├── Regular Price                                  │
│     ├── Sale Price                                     │
│     └── PV Value                                       │
│                                                        │
│  5. Inventory (สต็อก)                                  │
│     ├── Stock Quantity                                 │
│     ├── Low Stock Threshold                            │
│     └── Allow Backorders                               │
│                                                        │
└────────────────────────────────────────────────────────┘
```

### 🏷️ Product Types

| Type | Description | Use Case |
|------|-------------|----------|
| **Simple** | สินค้าเดี่ยว | สินค้าไม่มี Variant |
| **Variable** | มีหลายตัวเลือก | เสื้อผ้าหลายสี/ไซส์ |
| **Digital** | สินค้าดิจิทัล | E-book, Software |
| **Bundle** | ชุดรวม | Package Deal |
| **Subscription** | สมาชิกรายเดือน | Membership |

### 📊 Product Attributes

```php
// ตัวอย่าง Attributes
[
    'color' => ['Red', 'Blue', 'Black'],
    'size'  => ['S', 'M', 'L', 'XL'],
    'material' => ['Cotton', 'Polyester']
]

// Variations ที่เกิดขึ้น
Red-S, Red-M, Red-L, Red-XL,
Blue-S, Blue-M, Blue-L, Blue-XL,
...
```

### 💰 Pricing Strategies

| Strategy | Description |
|----------|-------------|
| **Regular Price** | ราคาปกติ |
| **Sale Price** | ราคาลด (กำหนดช่วงเวลาได้) |
| **Member Price** | ราคาสมาชิก Affiliate |
| **Bulk Pricing** | ซื้อมาก ลดมาก |
| **Flash Sale** | ลดแบบจำกัดเวลา |
                ",
            ],
            [
                'title' => '🛒 ระบบตะกร้าและ Checkout',
                'content' => "
## Cart & Checkout Flow

### 🛒 Shopping Cart Features

```
┌────────────────────────────────────────────────────────┐
│                   Shopping Cart                        │
├────────────────────────────────────────────────────────┤
│                                                        │
│  ┌──────────────────────────────────────────────────┐  │
│  │ [รูป] iPhone 15 Pro 256GB      Qty: [1] ×฿42,900 │  │
│  │       Color: Black                      Remove   │  │
│  └──────────────────────────────────────────────────┘  │
│                                                        │
│  ┌──────────────────────────────────────────────────┐  │
│  │ [รูป] เคส iPhone 15             Qty: [2] ×฿590   │  │
│  │       Color: Clear                      Remove   │  │
│  └──────────────────────────────────────────────────┘  │
│                                                        │
│  ───────────────────────────────────────────────────── │
│  Subtotal:                                   ฿44,080   │
│  Shipping:                                      ฿50   │
│  Discount (Coupon: SAVE10):                   -฿4,408  │
│  ───────────────────────────────────────────────────── │
│  Total:                                     ฿39,722   │
│  PV Earned:                                    440 PV  │
│                                                        │
│              [🔖 Apply Coupon]  [🛒 Checkout]          │
│                                                        │
└────────────────────────────────────────────────────────┘
```

### 📝 Checkout Process

```
Step 1          Step 2          Step 3          Step 4
[Cart] ────────▶ [Address] ─────▶ [Payment] ────▶ [Confirm]
                                                    │
                                                    ▼
                                              [Order Created]
                                                    │
                                           ┌────────┴────────┐
                                           ▼                 ▼
                                    [Seller Notified]  [Buyer Receipt]
```

### 🏠 Shipping Address

| Field | Required | Description |
|-------|----------|-------------|
| Full Name | ✓ | ชื่อผู้รับ |
| Phone | ✓ | เบอร์โทร |
| Address Line 1 | ✓ | ที่อยู่ |
| Address Line 2 | - | ที่อยู่เพิ่มเติม |
| Province | ✓ | จังหวัด |
| District | ✓ | อำเภอ/เขต |
| Subdistrict | ✓ | ตำบล/แขวง |
| Postal Code | ✓ | รหัสไปรษณีย์ |

### 💳 Payment Methods

```
┌────────────────────────────────────────────────────────┐
│              Select Payment Method                     │
├────────────────────────────────────────────────────────┤
│                                                        │
│  ○ Credit/Debit Card                                   │
│    [VISA] [MasterCard] [JCB]                          │
│                                                        │
│  ○ Bank Transfer                                       │
│    [กสิกร] [กรุงเทพ] [ไทยพาณิชย์]                        │
│                                                        │
│  ○ PromptPay QR                                        │
│    [Scan to Pay]                                       │
│                                                        │
│  ○ Wallet Balance                                      │
│    Available: ฿5,000                                   │
│                                                        │
│  ○ Installment (0%)                                    │
│    6 เดือน ×฿6,620/เดือน                               │
│                                                        │
└────────────────────────────────────────────────────────┘
```

### 🎫 Coupon/Promotion

| Type | Example | Description |
|------|---------|-------------|
| **Percentage** | SAVE10 | ลด 10% |
| **Fixed Amount** | MINUS500 | ลด 500 บาท |
| **Free Shipping** | FREESHIP | ส่งฟรี |
| **Bundle Deal** | BUY2GET1 | ซื้อ 2 แถม 1 |
| **First Order** | NEWUSER | ลดพิเศษออเดอร์แรก |
                ",
            ],
            [
                'title' => '🚚 การจัดการคำสั่งซื้อและจัดส่ง',
                'content' => "
## Order Management

### 📋 Order Status Flow

```
┌──────────────────────────────────────────────────────────────┐
│                    Order Lifecycle                            │
├──────────────────────────────────────────────────────────────┤
│                                                               │
│  [Pending] ─────▶ [Confirmed] ─────▶ [Processing]            │
│      │                                     │                  │
│      │ (ถ้าไม่ชำระ)                          ▼                  │
│      ▼                              [Ready to Ship]          │
│  [Cancelled]                               │                  │
│                                            ▼                  │
│                                       [Shipped]              │
│                                            │                  │
│                              ┌─────────────┼─────────────┐    │
│                              ▼             ▼             ▼    │
│                        [Delivered]   [Returned]   [Refunded] │
│                              │                               │
│                              ▼                               │
│                        [Completed]                           │
│                                                               │
└──────────────────────────────────────────────────────────────┘
```

### 📦 Order Status Description

| Status | Thai | Description |
|--------|------|-------------|
| `pending` | รอชำระเงิน | รอยืนยันการชำระ |
| `confirmed` | ยืนยันแล้ว | ได้รับเงินแล้ว |
| `processing` | กำลังจัดเตรียม | ผู้ขายเตรียมสินค้า |
| `ready_to_ship` | พร้อมส่ง | รอขนส่งมารับ |
| `shipped` | จัดส่งแล้ว | อยู่ระหว่างขนส่ง |
| `delivered` | ส่งถึงแล้ว | ลูกค้าได้รับสินค้า |
| `completed` | เสร็จสิ้น | ยืนยันรับสินค้า |
| `cancelled` | ยกเลิก | ยกเลิกออเดอร์ |
| `returned` | คืนสินค้า | ลูกค้าส่งคืน |
| `refunded` | คืนเงิน | คืนเงินแล้ว |

### 🚚 Shipping Integration

**ขนส่งที่รองรับ:**

| Carrier | API | Tracking | COD |
|---------|-----|----------|-----|
| **Flash Express** | ✓ | ✓ | ✓ |
| **Kerry Express** | ✓ | ✓ | ✓ |
| **Thailand Post** | ✓ | ✓ | ✓ |
| **J&T Express** | ✓ | ✓ | ✓ |
| **Shopee Xpress** | ✓ | ✓ | - |
| **Lazada Express** | ✓ | ✓ | - |

### 📊 Seller Dashboard - Orders

```
┌────────────────────────────────────────────────────────────────┐
│  Seller Dashboard → Orders                                     │
├────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Filter: [All ▼] [Today ▼] [Search: ___________] [🔍]          │
│                                                                 │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ #ORD-2024-001  │ 15 Jan 2024  │ ฿4,500  │ [Processing]  │   │
│  │ John Doe       │ 3 items      │ Flash   │ [View] [Ship] │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ #ORD-2024-002  │ 15 Jan 2024  │ ฿1,200  │ [Pending]     │   │
│  │ Jane Smith     │ 1 item       │ Kerry   │ [View] [Cancel]│  │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
│  Today: 15 orders │ ฿45,600 │ Pending: 3 │ Ready to Ship: 8   │
│                                                                 │
└────────────────────────────────────────────────────────────────┘
```

### 📄 Packing Slip / Invoice

เอกสารที่พิมพ์ได้:
- **Packing Slip** - ใบนำส่งสินค้า
- **Invoice** - ใบแจ้งหนี้/ใบเสร็จ
- **Shipping Label** - ใบปิดหน้ากล่อง
- **Return Form** - ใบคืนสินค้า
                ",
            ],
            [
                'title' => '📊 รายงานและวิเคราะห์ยอดขาย',
                'content' => "
## Analytics & Reports

### 📈 Dashboard Overview

```
┌────────────────────────────────────────────────────────────────┐
│                    Sales Dashboard                              │
├────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Today's Performance                                            │
│  ┌─────────────┐ ┌─────────────┐ ┌─────────────┐ ┌───────────┐ │
│  │    ฿45,600  │ │     23      │ │    ฿1,983   │ │   ฿2,280  │ │
│  │   Revenue   │ │   Orders    │ │  Avg. Order │ │ Commission│ │
│  │   ↑ 15%    │ │   ↑ 8%     │ │   ↑ 3%     │ │   ↑ 15%   │ │
│  └─────────────┘ └─────────────┘ └─────────────┘ └───────────┘ │
│                                                                 │
│  Revenue Chart (Last 30 Days)                                  │
│  ┌───────────────────────────────────────────────────────────┐ │
│  │     📊 [Bar Chart showing daily revenue]                  │ │
│  │  ฿60k ┤                                                   │ │
│  │  ฿40k ┤      ▄▄    ▄▄▄▄                    ▄▄▄▄           │ │
│  │  ฿20k ┤  ▄▄▄▄████▄▄██████▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄██████▄▄         │ │
│  │     0 ┼──────────────────────────────────────────────▶    │ │
│  │        1    5    10   15   20   25   30                   │ │
│  └───────────────────────────────────────────────────────────┘ │
│                                                                 │
└────────────────────────────────────────────────────────────────┘
```

### 📊 Key Metrics

| Metric | Description | Formula |
|--------|-------------|---------|
| **GMV** | Gross Merchandise Value | Total Sales Value |
| **Revenue** | รายได้สุทธิ | GMV - Returns - Discounts |
| **AOV** | Average Order Value | Revenue ÷ Orders |
| **Conversion** | อัตราการซื้อ | Orders ÷ Visitors × 100 |
| **CAC** | Customer Acquisition Cost | Marketing ÷ New Customers |
| **LTV** | Customer Lifetime Value | Avg. Revenue per Customer |

### 📑 Available Reports

| Report | Content | Export |
|--------|---------|--------|
| **Sales Report** | ยอดขายรายวัน/เดือน | CSV, PDF |
| **Product Report** | สินค้าขายดี | CSV, PDF |
| **Customer Report** | ลูกค้าใหม่/เก่า | CSV, PDF |
| **Affiliate Report** | ค่าคอมมิชชั่น | CSV, PDF |
| **Inventory Report** | สต็อกคงเหลือ | CSV, PDF |
| **Returns Report** | สินค้าคืน | CSV, PDF |

### 🏆 Top Products

```
┌────────────────────────────────────────────────────────────────┐
│  Top Selling Products (This Month)                             │
├────────────────────────────────────────────────────────────────┤
│                                                                 │
│  #1  iPhone 15 Pro             │ 156 sold  │ ฿6.7M  │ ⭐ 4.9   │
│  #2  AirPods Pro 2             │ 89 sold   │ ฿890K  │ ⭐ 4.8   │
│  #3  iPad Air                  │ 67 sold   │ ฿2.1M  │ ⭐ 4.7   │
│  #4  Apple Watch Series 9      │ 45 sold   │ ฿720K  │ ⭐ 4.9   │
│  #5  MacBook Air M3            │ 34 sold   │ ฿1.4M  │ ⭐ 4.8   │
│                                                                 │
└────────────────────────────────────────────────────────────────┘
```

### 📧 Automated Reports

ตั้งค่าส่งรายงานอัตโนมัติ:

| Report | Frequency | Recipients |
|--------|-----------|------------|
| Daily Sales | ทุกวัน 8:00 AM | Owner, Manager |
| Weekly Summary | ทุกวันจันทร์ | All Team |
| Monthly Report | วันที่ 1 ของเดือน | Owner, Accountant |
| Low Stock Alert | Real-time | Inventory Team |
                ",
            ],
            [
                'title' => '📝 แบบฝึกหัดท้ายบท',
                'content' => "
## แบบฝึกหัด: E-Commerce และร้านค้า

### 📋 แบบฝึกหัดที่ 1: เพิ่มสินค้าใหม่

**โจทย์:** คุณต้องการขายเสื้อยืด 3 สี (ขาว, ดำ, น้ำเงิน) 4 ไซส์ (S, M, L, XL)

**ให้ทำ:**
1. เลือก Product Type ที่เหมาะสม
2. สร้าง Attributes และ Variations
3. คำนวณจำนวน SKU ที่ต้องสร้าง

```
คำตอบ:
- Product Type: Variable
- Attributes: Color (3), Size (4)
- Total SKU: 3 × 4 = 12 variations

SKU Examples:
TSHIRT-WHT-S, TSHIRT-WHT-M, ...
TSHIRT-BLK-S, TSHIRT-BLK-M, ...
TSHIRT-BLU-S, TSHIRT-BLU-M, ...
```

---

### 📋 แบบฝึกหัดที่ 2: คำนวณ Pricing

**โจทย์:** สินค้าราคาทุน 500 บาท ต้องการกำไร 40% และให้ PV 5%

**คำนวณ:**
1. Regular Price
2. PV Value
3. Member Price (ลด 10% จาก Regular)

```
คำตอบ:
1. Regular Price = 500 × 1.4 = 700 บาท
2. PV Value = 700 × 0.05 = 35 PV
3. Member Price = 700 × 0.9 = 630 บาท
```

---

### 📋 แบบฝึกหัดที่ 3: Order Fulfillment

**โจทย์:** มี 10 orders ที่ต้อง ship วันนี้

**ให้เรียงลำดับขั้นตอน:**
- [ ] พิมพ์ Packing Slip
- [ ] เปลี่ยนสถานะเป็น Shipped
- [ ] แพ็คสินค้าตาม Order
- [ ] พิมพ์ Shipping Label
- [ ] ส่งมอบขนส่ง
- [ ] เลือก Orders ที่ Ready to Ship

```
คำตอบ (ลำดับที่ถูกต้อง):
1. เลือก Orders ที่ Ready to Ship
2. พิมพ์ Packing Slip
3. แพ็คสินค้าตาม Order
4. พิมพ์ Shipping Label
5. เปลี่ยนสถานะเป็น Shipped
6. ส่งมอบขนส่ง
```

---

### 📋 แบบฝึกหัดที่ 4: วิเคราะห์รายงาน

**โจทย์:** จากข้อมูล:
- Revenue: ฿100,000
- Orders: 50
- Visitors: 1,000
- New Customers: 30
- Marketing Cost: ฿9,000

**คำนวณ:**
1. AOV (Average Order Value)
2. Conversion Rate
3. CAC (Customer Acquisition Cost)

```
คำตอบ:
1. AOV = 100,000 ÷ 50 = ฿2,000
2. Conversion = (50 ÷ 1,000) × 100 = 5%
3. CAC = 9,000 ÷ 30 = ฿300
```

---

### ✅ Checklist ก่อนสอบ

- [ ] เข้าใจประเภทสินค้า (Simple, Variable, Digital)
- [ ] สร้าง Product Variations ได้
- [ ] เข้าใจ Order Status Flow
- [ ] ใช้งาน Shipping Integration ได้
- [ ] อ่านและวิเคราะห์ Dashboard ได้
- [ ] คำนวณ Key Metrics ได้ (AOV, Conversion, CAC)
                ",
            ],
        ];
    }

    /**
     * คอร์ส 10: การจัดการ E-Commerce และร้านค้า - Quiz
     */
    private function getCourse10Quiz(): array
    {
        return [
            'time_limit' => 15,
            'description' => 'ทดสอบความเข้าใจเกี่ยวกับระบบ E-Commerce และการจัดการร้านค้า',
            'questions' => [
                [
                    'question' => 'Product Type ใดเหมาะกับเสื้อผ้าที่มีหลายสี หลายไซส์?',
                    'answers' => [
                        ['text' => 'Simple', 'is_correct' => false],
                        ['text' => 'Variable', 'is_correct' => true],
                        ['text' => 'Digital', 'is_correct' => false],
                        ['text' => 'Bundle', 'is_correct' => false],
                    ],
                    'explanation' => 'Variable Product ใช้สำหรับสินค้าที่มีหลาย Variations เช่น สี, ไซส์',
                ],
                [
                    'question' => 'Order Status \"Processing\" หมายถึง?',
                    'answers' => [
                        ['text' => 'รอชำระเงิน', 'is_correct' => false],
                        ['text' => 'ผู้ขายกำลังจัดเตรียมสินค้า', 'is_correct' => true],
                        ['text' => 'จัดส่งแล้ว', 'is_correct' => false],
                        ['text' => 'ยกเลิก', 'is_correct' => false],
                    ],
                    'explanation' => 'Processing หมายถึงผู้ขายได้รับเงินแล้วและกำลังจัดเตรียมสินค้า',
                ],
                [
                    'question' => 'AOV (Average Order Value) คำนวณอย่างไร?',
                    'answers' => [
                        ['text' => 'Orders ÷ Revenue', 'is_correct' => false],
                        ['text' => 'Revenue ÷ Orders', 'is_correct' => true],
                        ['text' => 'Revenue × Orders', 'is_correct' => false],
                        ['text' => 'Revenue + Orders', 'is_correct' => false],
                    ],
                    'explanation' => 'AOV = Revenue ÷ Orders คือมูลค่าเฉลี่ยต่อออเดอร์',
                ],
                [
                    'question' => 'ถ้ามี 1,000 Visitors และ 50 Orders, Conversion Rate เท่าไร?',
                    'answers' => [
                        ['text' => '0.5%', 'is_correct' => false],
                        ['text' => '5%', 'is_correct' => true],
                        ['text' => '50%', 'is_correct' => false],
                        ['text' => '20%', 'is_correct' => false],
                    ],
                    'explanation' => 'Conversion = (50 ÷ 1,000) × 100 = 5%',
                ],
                [
                    'question' => 'SKU ย่อมาจากอะไร?',
                    'answers' => [
                        ['text' => 'Stock Keeping Unit', 'is_correct' => true],
                        ['text' => 'Sales Key Unit', 'is_correct' => false],
                        ['text' => 'Standard Known Unit', 'is_correct' => false],
                        ['text' => 'Shop Keeping Unit', 'is_correct' => false],
                    ],
                    'explanation' => 'SKU = Stock Keeping Unit คือรหัสสินค้าที่ใช้จัดการสต็อก',
                ],
                [
                    'question' => 'CAC คืออะไร?',
                    'answers' => [
                        ['text' => 'ต้นทุนสินค้า', 'is_correct' => false],
                        ['text' => 'ต้นทุนการได้ลูกค้าใหม่', 'is_correct' => true],
                        ['text' => 'ค่าขนส่ง', 'is_correct' => false],
                        ['text' => 'กำไรสุทธิ', 'is_correct' => false],
                    ],
                    'explanation' => 'CAC = Customer Acquisition Cost คือต้นทุนในการหาลูกค้าใหม่ 1 คน',
                ],
                [
                    'question' => 'เอกสารใดใช้ติดหน้ากล่องสำหรับจัดส่ง?',
                    'answers' => [
                        ['text' => 'Invoice', 'is_correct' => false],
                        ['text' => 'Packing Slip', 'is_correct' => false],
                        ['text' => 'Shipping Label', 'is_correct' => true],
                        ['text' => 'Return Form', 'is_correct' => false],
                    ],
                    'explanation' => 'Shipping Label คือใบปิดหน้ากล่อง มีข้อมูลผู้รับและ Barcode สำหรับขนส่ง',
                ],
            ],
        ];
    }

    /**
     * คอร์ส 7: ระบบ MLM และโครงสร้างสายงาน - Quiz
     */
    private function getCourse7Quiz(): array
    {
        return [
            'time_limit' => 18,
            'description' => 'ทดสอบความเข้าใจเกี่ยวกับระบบ MLM และโครงสร้างสายงาน',
            'questions' => [
                [
                    'question' => 'Binary Plan มีกี่ขา?',
                    'answers' => [
                        ['text' => '1 ขา', 'is_correct' => false],
                        ['text' => '2 ขา (ซ้าย-ขวา)', 'is_correct' => true],
                        ['text' => '3 ขา', 'is_correct' => false],
                        ['text' => 'ไม่จำกัด', 'is_correct' => false],
                    ],
                    'explanation' => 'Binary Plan มี 2 ขา คือ ซ้ายและขวา โดยสมาชิกแต่ละคนมีแค่ 2 ตำแหน่งตรง',
                ],
                [
                    'question' => 'Binary Plan คำนวณโบนัสจากขาไหน?',
                    'answers' => [
                        ['text' => 'ขาที่ยอดมากกว่า (Strong Leg)', 'is_correct' => false],
                        ['text' => 'ขาที่ยอดน้อยกว่า (Weak Leg)', 'is_correct' => true],
                        ['text' => 'รวมทั้งสองขา', 'is_correct' => false],
                        ['text' => 'ขาที่เลือกเอง', 'is_correct' => false],
                    ],
                    'explanation' => 'Binary คำนวณโบนัสจาก Weak Leg (ขาที่ยอดน้อยกว่า) ส่วนยอดที่เหลือจะ Carry Forward',
                ],
                [
                    'question' => 'Unilevel Plan มีลักษณะอย่างไร?',
                    'answers' => [
                        ['text' => 'จำกัด 2 คนในชั้นแรก', 'is_correct' => false],
                        ['text' => 'ไม่จำกัดจำนวนสมาชิกในแต่ละชั้น', 'is_correct' => true],
                        ['text' => 'ไม่มีค่าคอมมิชชั่น', 'is_correct' => false],
                        ['text' => 'ได้แค่ 1 Level', 'is_correct' => false],
                    ],
                    'explanation' => 'Unilevel Plan ไม่จำกัดความกว้าง สามารถแนะนำสมาชิกใน Level 1 ได้ไม่จำกัด',
                ],
                [
                    'question' => 'ถ้าขาซ้าย 1,500 PV ขาขวา 2,000 PV และ Rate 10% จะได้โบนัสเท่าไร?',
                    'answers' => [
                        ['text' => '150 บาท', 'is_correct' => true],
                        ['text' => '200 บาท', 'is_correct' => false],
                        ['text' => '350 บาท', 'is_correct' => false],
                        ['text' => '500 บาท', 'is_correct' => false],
                    ],
                    'explanation' => 'คำนวณจาก Weak Leg: 1,500 PV × 10% = 150 บาท',
                ],
                [
                    'question' => 'Genealogy คือ?',
                    'answers' => [
                        ['text' => 'ระบบชำระเงิน', 'is_correct' => false],
                        ['text' => 'หน้าแสดงโครงสร้างสายงาน', 'is_correct' => true],
                        ['text' => 'ระบบถอนเงิน', 'is_correct' => false],
                        ['text' => 'รายงานยอดขาย', 'is_correct' => false],
                    ],
                    'explanation' => 'Genealogy คือหน้าที่แสดงโครงสร้างสายงานทั้งหมดของคุณ รองรับหลายมุมมอง',
                ],
                [
                    'question' => 'Level 1 ใน Unilevel หมายถึงใคร?',
                    'answers' => [
                        ['text' => 'คนที่แนะนำคุณ', 'is_correct' => false],
                        ['text' => 'คนที่คุณแนะนำโดยตรง', 'is_correct' => true],
                        ['text' => 'Admin ระบบ', 'is_correct' => false],
                        ['text' => 'ลูกค้าทั่วไป', 'is_correct' => false],
                    ],
                    'explanation' => 'Level 1 คือสมาชิกที่คุณแนะนำโดยตรง (Direct Referrals)',
                ],
                [
                    'question' => 'สูตร EDDC ใช้สำหรับอะไร?',
                    'answers' => [
                        ['text' => 'คำนวณค่าคอมมิชชั่น', 'is_correct' => false],
                        ['text' => 'สอนทีมให้ทำซ้ำได้ (Duplication)', 'is_correct' => true],
                        ['text' => 'ถอนเงิน', 'is_correct' => false],
                        ['text' => 'สมัครสมาชิก', 'is_correct' => false],
                    ],
                    'explanation' => 'EDDC = Explain, Demonstrate, Do together, Check เป็นสูตรสอนทีมให้ทำซ้ำได้',
                ],
            ],
        ];
    }

    /**
     * คอร์ส 5: ระบบกระเป๋าเงิน (Wallet) - Quiz
     */
    private function getCourse5Quiz(): array
    {
        return [
            'time_limit' => 15,
            'description' => 'ทดสอบความเข้าใจเกี่ยวกับระบบกระเป๋าเงิน',
            'questions' => [
                [
                    'question' => 'กระเป๋าใดใช้สำหรับรับค่าคอมมิชชั่นและถอนเงินได้?',
                    'answers' => [
                        ['text' => 'Video Coins', 'is_correct' => false],
                        ['text' => 'Main Wallet', 'is_correct' => true],
                        ['text' => 'Points', 'is_correct' => false],
                        ['text' => 'Bonus Wallet', 'is_correct' => false],
                    ],
                    'explanation' => 'Main Wallet เป็นกระเป๋าหลักสำหรับรับค่าคอมมิชชั่นและสามารถถอนเป็นเงินสดได้',
                ],
                [
                    'question' => 'ยอดถอนขั้นต่ำคือเท่าไร?',
                    'answers' => [
                        ['text' => '50 บาท', 'is_correct' => false],
                        ['text' => '100 บาท', 'is_correct' => true],
                        ['text' => '200 บาท', 'is_correct' => false],
                        ['text' => '500 บาท', 'is_correct' => false],
                    ],
                    'explanation' => 'ยอดถอนขั้นต่ำคือ 100 บาท',
                ],
                [
                    'question' => 'ก่อนถอนเงินต้องทำอะไรก่อน?',
                    'answers' => [
                        ['text' => 'เปลี่ยนรหัสผ่าน', 'is_correct' => false],
                        ['text' => 'ยืนยันตัวตน (KYC) และเปิด 2FA', 'is_correct' => true],
                        ['text' => 'ซื้อสินค้าอย่างน้อย 1 ชิ้น', 'is_correct' => false],
                        ['text' => 'เปลี่ยนรูปโปรไฟล์', 'is_correct' => false],
                    ],
                    'explanation' => 'ต้องยืนยันตัวตน (KYC) ระดับ 2+, เพิ่มบัญชีธนาคาร และเปิดใช้งาน 2FA ก่อนถอนเงิน',
                ],
                [
                    'question' => 'ช่องทางเติมเงินใดที่เงินเข้าทันทีและไม่มีค่าธรรมเนียม?',
                    'answers' => [
                        ['text' => 'Counter Service', 'is_correct' => false],
                        ['text' => 'บัตรเครดิต', 'is_correct' => false],
                        ['text' => 'PromptPay', 'is_correct' => true],
                        ['text' => 'TrueMoney', 'is_correct' => false],
                    ],
                    'explanation' => 'PromptPay เป็นช่องทางที่แนะนำ เงินเข้าทันทีและไม่มีค่าธรรมเนียม',
                ],
                [
                    'question' => 'Video Coins ได้มาจากไหน?',
                    'answers' => [
                        ['text' => 'ซื้อด้วยเงินสด', 'is_correct' => false],
                        ['text' => 'การดูวิดีโอและทำภารกิจ', 'is_correct' => true],
                        ['text' => 'ค่าคอมมิชชั่น', 'is_correct' => false],
                        ['text' => 'การถอนเงิน', 'is_correct' => false],
                    ],
                    'explanation' => 'Video Coins เป็นเหรียญที่ได้จากการดูวิดีโอและทำภารกิจต่างๆ ในระบบ',
                ],
                [
                    'question' => 'ถ้าโอนเงิน 5,000 บาท ให้สมาชิกอื่น ค่าธรรมเนียมเท่าไร?',
                    'answers' => [
                        ['text' => 'ฟรี', 'is_correct' => false],
                        ['text' => '1% = 50 บาท', 'is_correct' => true],
                        ['text' => '2% = 100 บาท', 'is_correct' => false],
                        ['text' => '5% = 250 บาท', 'is_correct' => false],
                    ],
                    'explanation' => 'ยอด 1,001-10,000 บาท คิดค่าธรรมเนียม 1% (ไม่เกิน 50 บาท) ดังนั้น 5,000 × 1% = 50 บาท',
                ],
                [
                    'question' => 'KYC Level 2 ต้องยืนยันอะไรบ้าง?',
                    'answers' => [
                        ['text' => 'แค่ Email', 'is_correct' => false],
                        ['text' => 'Email + เบอร์โทร + บัตรประชาชน', 'is_correct' => true],
                        ['text' => 'เฉพาะบัตรประชาชน', 'is_correct' => false],
                        ['text' => 'หนังสือเดินทางอย่างเดียว', 'is_correct' => false],
                    ],
                    'explanation' => 'KYC Level 2 ต้องยืนยัน Email, เบอร์โทร และบัตรประชาชน รองรับวงเงินถึง 50,000 บาท/วัน',
                ],
            ],
        ];
    }

    /**
     * คอร์ส 4: ระบบ Affiliate Marketing เบื้องต้น - Quiz
     */
    private function getCourse4Quiz(): array
    {
        return [
            'time_limit' => 15,
            'description' => 'ทดสอบความเข้าใจเกี่ยวกับระบบ Affiliate Marketing เบื้องต้น',
            'questions' => [
                [
                    'question' => 'Affiliate Marketing คืออะไร?',
                    'answers' => [
                        ['text' => 'การขายสินค้าของตัวเอง', 'is_correct' => false],
                        ['text' => 'การตลาดแบบพันธมิตรที่ได้ค่าคอมมิชชั่นจากการแนะนำ', 'is_correct' => true],
                        ['text' => 'การโฆษณาแบบเสียเงิน', 'is_correct' => false],
                        ['text' => 'การทำ SEO เว็บไซต์', 'is_correct' => false],
                    ],
                    'explanation' => 'Affiliate Marketing คือการตลาดแบบพันธมิตร โดยคุณจะได้รับค่าคอมมิชชั่นเมื่อแนะนำลูกค้าให้กับธุรกิจ',
                ],
                [
                    'question' => 'Referral Link หาได้จากเมนูไหน?',
                    'answers' => [
                        ['text' => 'กระเป๋าเงิน > เติมเงิน', 'is_correct' => false],
                        ['text' => 'Affiliate > ลิงก์แนะนำ', 'is_correct' => true],
                        ['text' => 'ร้านค้า > สินค้าของฉัน', 'is_correct' => false],
                        ['text' => 'ตั้งค่า > โปรไฟล์', 'is_correct' => false],
                    ],
                    'explanation' => 'Referral Link อยู่ที่เมนู Affiliate > ลิงก์แนะนำ',
                ],
                [
                    'question' => 'ถ้าสินค้าราคา 2,000 บาท อัตราค่าคอมมิชชั่น 15% จะได้รับเท่าไร?',
                    'answers' => [
                        ['text' => '200 บาท', 'is_correct' => false],
                        ['text' => '250 บาท', 'is_correct' => false],
                        ['text' => '300 บาท', 'is_correct' => true],
                        ['text' => '350 บาท', 'is_correct' => false],
                    ],
                    'explanation' => 'คำนวณ: 2,000 × 15% = 2,000 × 0.15 = 300 บาท',
                ],
                [
                    'question' => 'Direct Commission คืออะไร?',
                    'answers' => [
                        ['text' => 'ค่าคอมมิชชั่นจากสมาชิกในสายงาน', 'is_correct' => false],
                        ['text' => 'ค่าคอมมิชชั่นจากลูกค้าที่คุณแนะนำโดยตรง', 'is_correct' => true],
                        ['text' => 'โบนัสรายเดือน', 'is_correct' => false],
                        ['text' => 'โบนัสตำแหน่ง', 'is_correct' => false],
                    ],
                    'explanation' => 'Direct Commission คือค่าคอมมิชชั่นที่ได้รับเมื่อลูกค้าที่คุณแนะนำโดยตรงซื้อสินค้า',
                ],
                [
                    'question' => 'Campaign Link มีประโยชน์อย่างไร?',
                    'answers' => [
                        ['text' => 'ทำให้ลิงก์สั้นลง', 'is_correct' => false],
                        ['text' => 'ติดตามได้ว่าลูกค้ามาจากช่องทางไหน', 'is_correct' => true],
                        ['text' => 'เพิ่มค่าคอมมิชชั่น', 'is_correct' => false],
                        ['text' => 'ป้องกันการโกง', 'is_correct' => false],
                    ],
                    'explanation' => 'Campaign Link ช่วยให้คุณติดตามได้ว่าลูกค้ามาจากช่องทางไหน เพื่อวัดผลและปรับปรุงกลยุทธ์',
                ],
                [
                    'question' => 'กลยุทธ์ใดเหมาะสำหรับผู้เริ่มต้น?',
                    'answers' => [
                        ['text' => 'ซื้อโฆษณาราคาแพง', 'is_correct' => false],
                        ['text' => 'เริ่มจากคนรู้จักและสร้าง Content บน Social Media', 'is_correct' => true],
                        ['text' => 'Spam ลิงก์ทุกที่', 'is_correct' => false],
                        ['text' => 'รอให้คนมาหาเอง', 'is_correct' => false],
                    ],
                    'explanation' => 'สำหรับผู้เริ่มต้น ควรเริ่มจากคนรู้จัก (Warm Market) และสร้าง Content ที่มีคุณค่าบน Social Media',
                ],
                [
                    'question' => 'ข้อใดควรหลีกเลี่ยงในการทำ Affiliate?',
                    'answers' => [
                        ['text' => 'สร้าง Content ที่มีประโยชน์', 'is_correct' => false],
                        ['text' => 'ตอบคำถามลูกค้าอย่างใส่ใจ', 'is_correct' => false],
                        ['text' => 'Spam ลิงก์และพูดเกินจริง', 'is_correct' => true],
                        ['text' => 'ติดตามผลจาก Dashboard', 'is_correct' => false],
                    ],
                    'explanation' => 'การ Spam ลิงก์และพูดเกินจริงจะทำลายความน่าเชื่อถือและอาจถูกระงับบัญชี',
                ],
            ],
        ];
    }

    /**
     * สร้างเนื้อหา HTML
     */
    private function generateContent(string $title, array $sections): string
    {
        $html = '<div class="prose prose-lg max-w-none dark:prose-invert">' . "\n";

        foreach ($sections as $section) {
            $html .= '<h2 class="text-2xl font-bold text-gray-900 dark:text-white mt-8 mb-4">';
            $html .= htmlspecialchars($section['title']);
            $html .= '</h2>' . "\n";

            // แปลง Markdown-like syntax เป็น HTML
            $content = $section['content'];

            // แปลง Bold
            $content = preg_replace('/\*\*(.+?)\*\*/', '<strong class="text-purple-600 dark:text-purple-400">$1</strong>', $content);

            // แปลง Code blocks
            $content = preg_replace('/```(.+?)```/s', '<pre class="bg-gray-900 text-green-400 p-4 rounded-lg overflow-x-auto"><code>$1</code></pre>', $content);

            // แปลง Inline code
            $content = preg_replace('/`(.+?)`/', '<code class="bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded">$1</code>', $content);

            // แปลง Newlines เป็น HTML
            $content = nl2br($content);

            // แปลง Lists
            $content = preg_replace('/^• (.+)$/m', '<li class="ml-4">$1</li>', $content);
            $content = preg_replace('/^(\d+)\. (.+)$/m', '<li class="ml-4">$2</li>', $content);

            $html .= '<div class="text-gray-700 dark:text-gray-300 leading-relaxed">';
            $html .= $content;
            $html .= '</div>' . "\n";
        }

        $html .= '</div>';

        return $html;
    }
}
