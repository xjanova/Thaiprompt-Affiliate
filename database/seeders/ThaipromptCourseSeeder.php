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
            // ========== ระดับ 1: เริ่มต้น ==========
            [
                'title' => 'รู้จัก Thaiprompt คืออะไร?',
                'slug' => 'thaiprompt-introduction',
                'excerpt' => 'ทำความรู้จักกับโครงการ Thaiprompt ว่าคืออะไร มีวิสัยทัศน์และพันธกิจอย่างไร',
                'duration' => 15,
                'difficulty' => 'beginner',
                'course_level' => 1,
                'is_featured' => true,
                'tags' => ['Thaiprompt', 'เริ่มต้น', 'แนะนำ'],
                'content' => $this->generateContent('รู้จัก Thaiprompt คืออะไร?', [
                    [
                        'title' => 'Thaiprompt คืออะไร?',
                        'content' => 'Thaiprompt เป็นแพลตฟอร์มระบบ All-in-One ที่รวมเครื่องมือธุรกิจออนไลน์ไว้ในที่เดียว ไม่ว่าจะเป็นระบบ MLM, Affiliate Marketing, E-Commerce, AI Bot, Crypto และอีกมากมาย ออกแบบมาเพื่อให้ธุรกิจไทยสามารถเติบโตในยุคดิจิทัลได้อย่างมีประสิทธิภาพ',
                    ],
                    [
                        'title' => 'วิสัยทัศน์',
                        'content' => 'เป็นแพลตฟอร์มชั้นนำที่ช่วยให้ธุรกิจไทยทุกขนาดสามารถเข้าถึงเทคโนโลยีที่ทันสมัย โดยไม่จำเป็นต้องมีความรู้ด้านเทคนิคมากนัก',
                    ],
                    [
                        'title' => 'พันธกิจ',
                        'content' => '• พัฒนาเครื่องมือที่ใช้งานง่ายสำหรับทุกคน\n• ให้บริการที่มีคุณภาพในราคาที่เข้าถึงได้\n• สร้างชุมชนนักธุรกิจที่แข็งแกร่ง\n• สนับสนุนการเติบโตของเศรษฐกิจดิจิทัลไทย',
                    ],
                ]),
                'quiz' => [
                    'time_limit' => 10,
                    'questions' => [
                        [
                            'question' => 'Thaiprompt คือแพลตฟอร์มประเภทใด?',
                            'answers' => [
                                ['text' => 'แพลตฟอร์มเกมออนไลน์', 'is_correct' => false],
                                ['text' => 'แพลตฟอร์ม All-in-One สำหรับธุรกิจ', 'is_correct' => true],
                                ['text' => 'แพลตฟอร์มสื่อสังคมออนไลน์', 'is_correct' => false],
                                ['text' => 'แพลตฟอร์มสตรีมมิ่งวิดีโอ', 'is_correct' => false],
                            ],
                            'explanation' => 'Thaiprompt เป็นแพลตฟอร์ม All-in-One ที่รวมเครื่องมือธุรกิจออนไลน์ไว้ในที่เดียว',
                        ],
                        [
                            'question' => 'Thaiprompt ออกแบบมาเพื่อใคร?',
                            'answers' => [
                                ['text' => 'นักเรียนนักศึกษาเท่านั้น', 'is_correct' => false],
                                ['text' => 'บริษัทขนาดใหญ่เท่านั้น', 'is_correct' => false],
                                ['text' => 'ธุรกิจไทยทุกขนาด', 'is_correct' => true],
                                ['text' => 'หน่วยงานรัฐบาลเท่านั้น', 'is_correct' => false],
                            ],
                            'explanation' => 'Thaiprompt ออกแบบมาเพื่อให้ธุรกิจไทยทุกขนาดสามารถเข้าถึงเทคโนโลยีที่ทันสมัยได้',
                        ],
                        [
                            'question' => 'ข้อใดไม่ใช่พันธกิจของ Thaiprompt?',
                            'answers' => [
                                ['text' => 'พัฒนาเครื่องมือที่ใช้งานง่าย', 'is_correct' => false],
                                ['text' => 'ให้บริการในราคาที่เข้าถึงได้', 'is_correct' => false],
                                ['text' => 'ทำกำไรสูงสุดโดยไม่สนใจลูกค้า', 'is_correct' => true],
                                ['text' => 'สร้างชุมชนนักธุรกิจที่แข็งแกร่ง', 'is_correct' => false],
                            ],
                            'explanation' => 'Thaiprompt มุ่งเน้นการให้บริการที่มีคุณภาพและการสร้างชุมชน ไม่ใช่การทำกำไรสูงสุดโดยไม่สนใจลูกค้า',
                        ],
                    ],
                ],
            ],
            [
                'title' => 'การสมัครสมาชิก Thaiprompt',
                'slug' => 'thaiprompt-registration',
                'excerpt' => 'เรียนรู้วิธีสมัครสมาชิก Thaiprompt ทั้งทางเว็บและ LINE พร้อมการตั้งค่าโปรไฟล์',
                'duration' => 20,
                'difficulty' => 'beginner',
                'course_level' => 1,
                'is_featured' => false,
                'tags' => ['สมัครสมาชิก', 'เริ่มต้น', 'LINE'],
                'content' => $this->generateContent('การสมัครสมาชิก Thaiprompt', [
                    [
                        'title' => 'วิธีสมัครสมาชิกผ่านเว็บไซต์',
                        'content' => "ขั้นตอนการสมัคร:\n\n1. เข้าสู่หน้าเว็บไซต์ Thaiprompt\n2. คลิกปุ่ม \"สมัครสมาชิก\" ที่มุมขวาบน\n3. กรอกข้อมูลส่วนตัว: ชื่อ-นามสกุล, อีเมล, เบอร์โทร\n4. สร้างรหัสผ่านที่ปลอดภัย (อย่างน้อย 8 ตัวอักษร)\n5. ยอมรับเงื่อนไขการใช้งาน\n6. คลิกปุ่ม \"สมัครสมาชิก\"\n7. ยืนยันอีเมลที่ได้รับ",
                    ],
                    [
                        'title' => 'วิธีสมัครสมาชิกผ่าน LINE',
                        'content' => "ขั้นตอนง่ายๆ:\n\n1. เพิ่มเพื่อน LINE Official Account ของ Thaiprompt\n2. พิมพ์คำว่า \"สมัคร\" หรือกดเมนู \"สมัครสมาชิก\"\n3. กรอกข้อมูลตามที่ Bot ขอ\n4. ยืนยัน OTP ที่ส่งไปยังเบอร์โทร\n5. เสร็จสิ้น! บัญชีจะเชื่อมกับ LINE อัตโนมัติ",
                    ],
                    [
                        'title' => 'การตั้งค่าโปรไฟล์',
                        'content' => "หลังสมัครสมาชิกแล้ว ควรตั้งค่าโปรไฟล์ให้ครบถ้วน:\n\n• อัปโหลดรูปโปรไฟล์ - สร้างความน่าเชื่อถือ\n• กรอกที่อยู่ - สำหรับการจัดส่งสินค้า\n• เพิ่มบัญชีธนาคาร - สำหรับรับค่าคอมมิชชั่น\n• เปิดใช้งาน 2FA - เพิ่มความปลอดภัย\n• ยืนยันตัวตน (KYC) - ปลดล็อคฟีเจอร์พิเศษ",
                    ],
                ]),
                'quiz' => [
                    'time_limit' => 10,
                    'questions' => [
                        [
                            'question' => 'รหัสผ่านสำหรับสมัครสมาชิกต้องมีความยาวอย่างน้อยกี่ตัวอักษร?',
                            'answers' => [
                                ['text' => '4 ตัวอักษร', 'is_correct' => false],
                                ['text' => '6 ตัวอักษร', 'is_correct' => false],
                                ['text' => '8 ตัวอักษร', 'is_correct' => true],
                                ['text' => '10 ตัวอักษร', 'is_correct' => false],
                            ],
                        ],
                        [
                            'question' => 'การสมัครสมาชิกผ่าน LINE ต้องยืนยันตัวตนด้วยอะไร?',
                            'answers' => [
                                ['text' => 'อีเมล', 'is_correct' => false],
                                ['text' => 'OTP ทางเบอร์โทร', 'is_correct' => true],
                                ['text' => 'บัตรประชาชน', 'is_correct' => false],
                                ['text' => 'รูปถ่ายหน้าตรง', 'is_correct' => false],
                            ],
                        ],
                        [
                            'question' => '2FA คืออะไร?',
                            'answers' => [
                                ['text' => 'ระบบชำระเงิน 2 ช่องทาง', 'is_correct' => false],
                                ['text' => 'ระบบยืนยันตัวตน 2 ขั้นตอน', 'is_correct' => true],
                                ['text' => 'ระบบส่งข้อความ 2 ทาง', 'is_correct' => false],
                                ['text' => 'ระบบจัดเก็บข้อมูล 2 ชั้น', 'is_correct' => false],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'หน้า Dashboard และการนำทาง',
                'slug' => 'thaiprompt-dashboard-navigation',
                'excerpt' => 'รู้จักกับหน้า Dashboard หลัก เมนูต่างๆ และวิธีการนำทางในระบบ',
                'duration' => 15,
                'difficulty' => 'beginner',
                'course_level' => 1,
                'is_featured' => false,
                'tags' => ['Dashboard', 'เมนู', 'นำทาง'],
                'content' => $this->generateContent('หน้า Dashboard และการนำทาง', [
                    [
                        'title' => 'ภาพรวมหน้า Dashboard',
                        'content' => "หน้า Dashboard เป็นหน้าแรกที่คุณจะเห็นหลัง Login ประกอบด้วย:\n\n• **Widget สรุปข้อมูล** - แสดงยอดเงิน, ยอดขาย, สมาชิกในทีม\n• **กราฟและสถิติ** - แสดงแนวโน้มรายได้และการเติบโต\n• **การแจ้งเตือน** - ข่าวสารและ notification สำคัญ\n• **Quick Actions** - ปุ่มลัดสำหรับงานที่ทำบ่อย",
                    ],
                    [
                        'title' => 'เมนูหลักในระบบ',
                        'content' => "เมนูแบ่งเป็นหมวดหมู่หลักๆ:\n\n🏠 **หน้าหลัก** - Dashboard และข้อมูลสรุป\n💰 **กระเป๋าเงิน** - จัดการยอดเงิน, เติม, ถอน\n👥 **ทีมงาน** - ดูสายงาน, สมาชิกในทีม\n🛒 **ร้านค้า** - สั่งซื้อสินค้า, ประวัติการสั่ง\n📊 **รายงาน** - รายงานค่าคอมมิชชั่น, ยอดขาย\n⚙️ **ตั้งค่า** - ตั้งค่าบัญชี, ความปลอดภัย",
                    ],
                    [
                        'title' => 'การค้นหาและกรองข้อมูล',
                        'content' => "ทุกหน้ารายการสามารถ:\n\n• **ค้นหา** - พิมพ์คำค้นในช่องค้นหา\n• **กรอง** - เลือกเงื่อนไขการแสดงผล\n• **เรียงลำดับ** - คลิกหัวคอลัมน์เพื่อเรียงลำดับ\n• **Export** - ดาวน์โหลดข้อมูลเป็น Excel/CSV\n• **แบ่งหน้า** - เลือกจำนวนรายการต่อหน้า",
                    ],
                ]),
                'quiz' => [
                    'time_limit' => 10,
                    'questions' => [
                        [
                            'question' => 'หน้า Dashboard แสดงข้อมูลใดบ้าง?',
                            'answers' => [
                                ['text' => 'เฉพาะยอดเงิน', 'is_correct' => false],
                                ['text' => 'ยอดเงิน, ยอดขาย, สมาชิกในทีม', 'is_correct' => true],
                                ['text' => 'เฉพาะกราฟ', 'is_correct' => false],
                                ['text' => 'ไม่แสดงข้อมูลใดเลย', 'is_correct' => false],
                            ],
                        ],
                        [
                            'question' => 'เมนู "กระเป๋าเงิน" ใช้ทำอะไร?',
                            'answers' => [
                                ['text' => 'ดูสายงาน', 'is_correct' => false],
                                ['text' => 'สั่งซื้อสินค้า', 'is_correct' => false],
                                ['text' => 'จัดการยอดเงิน, เติม, ถอน', 'is_correct' => true],
                                ['text' => 'ตั้งค่าบัญชี', 'is_correct' => false],
                            ],
                        ],
                        [
                            'question' => 'วิธีการเรียงลำดับข้อมูลในตารางทำอย่างไร?',
                            'answers' => [
                                ['text' => 'พิมพ์ในช่องค้นหา', 'is_correct' => false],
                                ['text' => 'คลิกปุ่ม Export', 'is_correct' => false],
                                ['text' => 'คลิกหัวคอลัมน์', 'is_correct' => true],
                                ['text' => 'กดปุ่ม Enter', 'is_correct' => false],
                            ],
                        ],
                    ],
                ],
            ],

            // ========== ระดับ 2: พื้นฐาน ==========
            [
                'title' => 'ระบบ Affiliate Marketing เบื้องต้น',
                'slug' => 'thaiprompt-affiliate-basic',
                'excerpt' => 'เรียนรู้พื้นฐานระบบ Affiliate การแชร์ลิงก์ และการคำนวณค่าคอมมิชชั่น',
                'duration' => 25,
                'difficulty' => 'beginner',
                'course_level' => 2,
                'is_featured' => true,
                'tags' => ['Affiliate', 'ค่าคอมมิชชั่น', 'แชร์ลิงก์'],
                'content' => $this->generateContent('ระบบ Affiliate Marketing เบื้องต้น', [
                    [
                        'title' => 'Affiliate Marketing คืออะไร?',
                        'content' => "Affiliate Marketing คือการตลาดแบบพันธมิตร โดยคุณจะได้รับค่าคอมมิชชั่นเมื่อแนะนำลูกค้าให้กับร้านค้า\n\n**หลักการทำงาน:**\n1. คุณได้รับลิงก์แนะนำพิเศษ (Referral Link)\n2. แชร์ลิงก์ไปยังช่องทางต่างๆ\n3. เมื่อมีคนสมัครหรือซื้อสินค้าผ่านลิงก์คุณ\n4. คุณได้รับค่าคอมมิชชั่น",
                    ],
                    [
                        'title' => 'วิธีดึง Referral Link',
                        'content' => "ขั้นตอนการดึงลิงก์:\n\n1. Login เข้าสู่ระบบ\n2. ไปที่เมนู \"Affiliate\" > \"ลิงก์แนะนำ\"\n3. คัดลอกลิงก์ที่แสดง\n4. แชร์ไปยังช่องทางที่ต้องการ\n\n**รูปแบบลิงก์:** `https://thaiprompt.com/ref/USERNAME`\n\n**Tips:** สามารถสร้างลิงก์แยกตามแคมเปญเพื่อติดตามผลได้",
                    ],
                    [
                        'title' => 'อัตราค่าคอมมิชชั่น',
                        'content' => "**ค่าคอมมิชชั่นแบ่งเป็น:**\n\n• **Direct Commission** - ได้จากการขายตรงของลูกค้าที่แนะนำ\n• **Indirect Commission** - ได้จากสมาชิกในสายงาน\n• **Rank Bonus** - โบนัสพิเศษตามยศ\n\n**ตัวอย่าง:**\nสินค้าราคา 1,000 บาท อัตราค่าคอมมิชชั่น 10%\nคุณจะได้รับ = 1,000 x 10% = 100 บาท",
                    ],
                ]),
                'quiz' => [
                    'time_limit' => 12,
                    'questions' => [
                        [
                            'question' => 'Affiliate Marketing คืออะไร?',
                            'answers' => [
                                ['text' => 'การขายสินค้าเอง', 'is_correct' => false],
                                ['text' => 'การตลาดแบบพันธมิตรที่ได้ค่าคอมมิชชั่นจากการแนะนำ', 'is_correct' => true],
                                ['text' => 'การโฆษณาแบบเสียเงิน', 'is_correct' => false],
                                ['text' => 'การทำ SEO', 'is_correct' => false],
                            ],
                        ],
                        [
                            'question' => 'Referral Link หาได้จากเมนูไหน?',
                            'answers' => [
                                ['text' => 'กระเป๋าเงิน', 'is_correct' => false],
                                ['text' => 'Affiliate > ลิงก์แนะนำ', 'is_correct' => true],
                                ['text' => 'ร้านค้า', 'is_correct' => false],
                                ['text' => 'ตั้งค่า', 'is_correct' => false],
                            ],
                        ],
                        [
                            'question' => 'ถ้าสินค้าราคา 2,000 บาท อัตราค่าคอมมิชชั่น 15% จะได้รับเท่าไร?',
                            'answers' => [
                                ['text' => '200 บาท', 'is_correct' => false],
                                ['text' => '250 บาท', 'is_correct' => false],
                                ['text' => '300 บาท', 'is_correct' => true],
                                ['text' => '350 บาท', 'is_correct' => false],
                            ],
                            'explanation' => '2,000 x 15% = 300 บาท',
                        ],
                    ],
                ],
            ],
            [
                'title' => 'ระบบกระเป๋าเงิน (Wallet)',
                'slug' => 'thaiprompt-wallet-system',
                'excerpt' => 'เรียนรู้วิธีใช้งานกระเป๋าเงิน การเติมเงิน การถอนเงิน และการโอน',
                'duration' => 20,
                'difficulty' => 'beginner',
                'course_level' => 2,
                'is_featured' => false,
                'tags' => ['Wallet', 'เติมเงิน', 'ถอนเงิน'],
                'content' => $this->generateContent('ระบบกระเป๋าเงิน (Wallet)', [
                    [
                        'title' => 'ประเภทกระเป๋าเงิน',
                        'content' => "ระบบมีกระเป๋าเงินหลายประเภท:\n\n💰 **Main Wallet** - กระเป๋าหลักสำหรับรับค่าคอมมิชชั่นและถอนเงิน\n🎮 **Video Coins** - เหรียญที่ได้จากการดูวิดีโอและทำภารกิจ\n⭐ **Points** - แต้มสะสมสำหรับแลกรางวัล\n₿ **TPIX Token** - สกุลเงินดิจิทัลของระบบ",
                    ],
                    [
                        'title' => 'การเติมเงิน',
                        'content' => "**ช่องทางเติมเงิน:**\n\n• **PromptPay** - โอนผ่าน QR Code ทันที\n• **บัตรเครดิต/เดบิต** - Visa, MasterCard\n• **Internet Banking** - โอนผ่านธนาคารออนไลน์\n• **TrueMoney Wallet** - เติมผ่าน TrueMoney\n• **Counter Service** - เติมที่ 7-Eleven\n\n**ขั้นตอน:**\n1. ไปที่ \"กระเป๋าเงิน\" > \"เติมเงิน\"\n2. ระบุจำนวนเงิน\n3. เลือกช่องทาง\n4. ทำตามขั้นตอน\n5. รอเงินเข้า (ส่วนใหญ่ทันที)",
                    ],
                    [
                        'title' => 'การถอนเงิน',
                        'content' => "**เงื่อนไขการถอน:**\n\n• ยอดขั้นต่ำ: 100 บาท\n• ต้องยืนยันตัวตน (KYC) ก่อน\n• ต้องเพิ่มบัญชีธนาคาร\n• ค่าธรรมเนียม: ขึ้นอยู่กับจำนวนและช่องทาง\n\n**เวลาดำเนินการ:**\n• ธนาคารเดียวกัน: ทันที - 1 ชั่วโมง\n• ต่างธนาคาร: 1-3 วันทำการ",
                    ],
                ]),
                'quiz' => [
                    'time_limit' => 12,
                    'questions' => [
                        [
                            'question' => 'Video Coins คือกระเป๋าประเภทใด?',
                            'answers' => [
                                ['text' => 'กระเป๋าหลักสำหรับถอนเงิน', 'is_correct' => false],
                                ['text' => 'เหรียญที่ได้จากการดูวิดีโอและทำภารกิจ', 'is_correct' => true],
                                ['text' => 'แต้มสะสมสำหรับแลกรางวัล', 'is_correct' => false],
                                ['text' => 'สกุลเงินดิจิทัล', 'is_correct' => false],
                            ],
                        ],
                        [
                            'question' => 'ยอดถอนขั้นต่ำคือเท่าไร?',
                            'answers' => [
                                ['text' => '50 บาท', 'is_correct' => false],
                                ['text' => '100 บาท', 'is_correct' => true],
                                ['text' => '200 บาท', 'is_correct' => false],
                                ['text' => '500 บาท', 'is_correct' => false],
                            ],
                        ],
                        [
                            'question' => 'ก่อนถอนเงินต้องทำอะไรก่อน?',
                            'answers' => [
                                ['text' => 'เปลี่ยนรหัสผ่าน', 'is_correct' => false],
                                ['text' => 'ยืนยันตัวตน (KYC)', 'is_correct' => true],
                                ['text' => 'ซื้อสินค้า', 'is_correct' => false],
                                ['text' => 'เปลี่ยนรูปโปรไฟล์', 'is_correct' => false],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'ระบบสั่งซื้อสินค้าและชำระเงิน',
                'slug' => 'thaiprompt-order-payment',
                'excerpt' => 'วิธีเลือกสินค้า สั่งซื้อ และชำระเงินผ่านช่องทางต่างๆ',
                'duration' => 20,
                'difficulty' => 'beginner',
                'course_level' => 2,
                'is_featured' => false,
                'tags' => ['สั่งซื้อ', 'ชำระเงิน', 'ตะกร้า'],
                'content' => $this->generateContent('ระบบสั่งซื้อสินค้าและชำระเงิน', [
                    [
                        'title' => 'วิธีเลือกสินค้าและเพิ่มตะกร้า',
                        'content' => "**ขั้นตอนการสั่งซื้อ:**\n\n1. เข้าเมนู \"ร้านค้า\" หรือ \"Marketplace\"\n2. เลือกหมวดหมู่หรือค้นหาสินค้า\n3. คลิกสินค้าเพื่อดูรายละเอียด\n4. เลือกตัวเลือก (ขนาด, สี, จำนวน)\n5. คลิก \"เพิ่มลงตะกร้า\"\n6. ไปที่ตะกร้าเพื่อตรวจสอบ",
                    ],
                    [
                        'title' => 'การชำระเงิน',
                        'content' => "**ช่องทางชำระเงิน:**\n\n• **Wallet** - ตัดจากยอดในกระเป๋า (แนะนำ)\n• **PromptPay** - สแกน QR Code\n• **บัตรเครดิต/เดบิต** - กรอกข้อมูลบัตร\n• **โอนเงิน** - โอนแล้วอัปโหลดสลิป\n\n**Tips:** ชำระผ่าน Wallet จะได้รับ Cashback เพิ่มเติม!",
                    ],
                    [
                        'title' => 'การติดตามและยกเลิกคำสั่งซื้อ',
                        'content' => "**ติดตามสถานะ:**\n\n• ไปที่ \"คำสั่งซื้อของฉัน\"\n• ดูสถานะ: รอชำระ, กำลังจัดส่ง, จัดส่งแล้ว\n• ดูเลขพัสดุและติดตามการจัดส่ง\n\n**การยกเลิก:**\n• ยกเลิกได้ก่อนชำระเงินหรือก่อนจัดส่ง\n• หลังจัดส่งแล้วต้องติดต่อ Support",
                    ],
                ]),
                'quiz' => [
                    'time_limit' => 12,
                    'questions' => [
                        [
                            'question' => 'ช่องทางชำระเงินที่แนะนำคือ?',
                            'answers' => [
                                ['text' => 'โอนเงิน', 'is_correct' => false],
                                ['text' => 'Wallet (ได้ Cashback เพิ่มเติม)', 'is_correct' => true],
                                ['text' => 'บัตรเครดิต', 'is_correct' => false],
                                ['text' => 'Counter Service', 'is_correct' => false],
                            ],
                        ],
                        [
                            'question' => 'สามารถยกเลิกคำสั่งซื้อได้เมื่อไร?',
                            'answers' => [
                                ['text' => 'ยกเลิกได้ตลอดเวลา', 'is_correct' => false],
                                ['text' => 'ก่อนชำระเงินหรือก่อนจัดส่ง', 'is_correct' => true],
                                ['text' => 'หลังได้รับสินค้าเท่านั้น', 'is_correct' => false],
                                ['text' => 'ไม่สามารถยกเลิกได้', 'is_correct' => false],
                            ],
                        ],
                    ],
                ],
            ],

            // ========== ระดับ 3: ปานกลาง ==========
            [
                'title' => 'ระบบ MLM และโครงสร้างสายงาน',
                'slug' => 'thaiprompt-mlm-structure',
                'excerpt' => 'เข้าใจระบบ MLM แผน Binary และ Unilevel การสร้างทีมและการคำนวณโบนัส',
                'duration' => 35,
                'difficulty' => 'intermediate',
                'course_level' => 3,
                'is_featured' => true,
                'tags' => ['MLM', 'Binary', 'Unilevel', 'ทีม'],
                'content' => $this->generateContent('ระบบ MLM และโครงสร้างสายงาน', [
                    [
                        'title' => 'MLM คืออะไร?',
                        'content' => "**Multi-Level Marketing (MLM)** คือระบบการตลาดหลายชั้นที่:\n\n• คุณได้รับค่าตอบแทนจากการขายสินค้าของตัวเอง\n• คุณได้รับค่าตอบแทนจากยอดขายของทีมที่คุณสร้าง\n• ยิ่งทีมใหญ่และขยายได้หลายชั้น ยิ่งได้รับรายได้มากขึ้น\n\n**Thaiprompt รองรับ 2 แผน:**\n1. Binary Plan - สายงาน 2 ขา\n2. Unilevel Plan - สายงานไม่จำกัดขา",
                    ],
                    [
                        'title' => 'Binary Plan (แผน 2 ขา)',
                        'content' => "**หลักการ:**\n• สมาชิกแต่ละคนมี 2 ตำแหน่ง: ซ้ายและขวา\n• ยอดขายจะสะสมแยกตามขา\n• คำนวณโบนัสจากขาที่ยอดน้อยกว่า (Weak Leg)\n\n**ตัวอย่าง:**\n```\n        คุณ\n       /    \\\n     ซ้าย   ขวา\n    100PV   200PV\n```\nคำนวณโบนัสจากขาซ้าย 100PV เพราะน้อยกว่า",
                    ],
                    [
                        'title' => 'Unilevel Plan',
                        'content' => "**หลักการ:**\n• ไม่จำกัดจำนวนสมาชิกในชั้นแรก\n• ได้รับค่าคอมมิชชั่นลึกหลายชั้น (Level)\n• แต่ละ Level มีอัตราค่าคอมมิชชั่นต่างกัน\n\n**ตัวอย่าง:**\n```\nLevel 1: 10% - สมาชิกที่แนะนำโดยตรง\nLevel 2: 5%  - สมาชิกที่แนะนำโดย Level 1\nLevel 3: 3%  - สมาชิกที่แนะนำโดย Level 2\n```",
                    ],
                    [
                        'title' => 'การดู Genealogy',
                        'content' => "**วิธีดูโครงสร้างทีม:**\n\n1. ไปที่เมนู \"ทีมงาน\" > \"Genealogy\"\n2. เลือกมุมมอง:\n   • Tree View - แสดงเป็นแผนผัง\n   • Table View - แสดงเป็นตาราง\n   • Matrix View - แสดงเป็น Matrix\n\n**ข้อมูลที่แสดง:**\n• ชื่อสมาชิก\n• ระดับ/ยศ\n• ยอด PV\n• จำนวนสมาชิกในทีม",
                    ],
                ]),
                'quiz' => [
                    'time_limit' => 15,
                    'questions' => [
                        [
                            'question' => 'Binary Plan มีกี่ขา?',
                            'answers' => [
                                ['text' => '1 ขา', 'is_correct' => false],
                                ['text' => '2 ขา', 'is_correct' => true],
                                ['text' => '3 ขา', 'is_correct' => false],
                                ['text' => 'ไม่จำกัด', 'is_correct' => false],
                            ],
                        ],
                        [
                            'question' => 'Binary Plan คำนวณโบนัสจากขาไหน?',
                            'answers' => [
                                ['text' => 'ขาที่ยอดมากกว่า', 'is_correct' => false],
                                ['text' => 'ขาที่ยอดน้อยกว่า (Weak Leg)', 'is_correct' => true],
                                ['text' => 'ทั้งสองขา', 'is_correct' => false],
                                ['text' => 'ขาที่เลือก', 'is_correct' => false],
                            ],
                        ],
                        [
                            'question' => 'Unilevel Plan มีลักษณะอย่างไร?',
                            'answers' => [
                                ['text' => 'จำกัด 2 คนในชั้นแรก', 'is_correct' => false],
                                ['text' => 'ไม่จำกัดจำนวนสมาชิกในชั้นแรก', 'is_correct' => true],
                                ['text' => 'ไม่มีค่าคอมมิชชั่น', 'is_correct' => false],
                                ['text' => 'ได้แค่ 1 Level', 'is_correct' => false],
                            ],
                        ],
                        [
                            'question' => 'Genealogy คือ?',
                            'answers' => [
                                ['text' => 'ระบบชำระเงิน', 'is_correct' => false],
                                ['text' => 'หน้าแสดงโครงสร้างทีม', 'is_correct' => true],
                                ['text' => 'ระบบถอนเงิน', 'is_correct' => false],
                                ['text' => 'รายงานยอดขาย', 'is_correct' => false],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'ระบบ Rank และโบนัสตำแหน่ง',
                'slug' => 'thaiprompt-rank-bonus',
                'excerpt' => 'เรียนรู้ระบบยศ เงื่อนไขการเลื่อนขั้น และโบนัสที่ได้รับจากแต่ละตำแหน่ง',
                'duration' => 30,
                'difficulty' => 'intermediate',
                'course_level' => 3,
                'is_featured' => false,
                'tags' => ['Rank', 'ยศ', 'โบนัส', 'เลื่อนขั้น'],
                'content' => $this->generateContent('ระบบ Rank และโบนัสตำแหน่ง', [
                    [
                        'title' => 'ระดับ Rank ทั้งหมด',
                        'content' => "**ระบบมี Rank หลายระดับ:**\n\n⭐ **Member** - สมาชิกเริ่มต้น\n🥉 **Bronze** - ระดับทองแดง\n🥈 **Silver** - ระดับเงิน\n🥇 **Gold** - ระดับทอง\n💎 **Platinum** - ระดับแพลทินัม\n👑 **Diamond** - ระดับเพชร\n🏆 **Crown Diamond** - ระดับสูงสุด\n\nแต่ละ Rank มีสิทธิประโยชน์และโบนัสที่แตกต่างกัน",
                    ],
                    [
                        'title' => 'เงื่อนไขการเลื่อน Rank',
                        'content' => "**ปัจจัยที่ใช้คำนวณ:**\n\n• **Personal PV** - ยอด PV ส่วนตัว\n• **Group PV** - ยอด PV รวมทั้งทีม\n• **Direct Referrals** - จำนวนผู้แนะนำโดยตรง\n• **Team Size** - จำนวนสมาชิกในทีม\n• **Qualified Legs** - จำนวนขาที่มียอดถึงเกณฑ์\n\n**ตัวอย่าง Silver:**\n• Personal PV: 500+\n• Group PV: 5,000+\n• Direct Referrals: 5+",
                    ],
                    [
                        'title' => 'โบนัสตำแหน่ง',
                        'content' => "**ประเภทโบนัส:**\n\n💰 **Rank Bonus** - โบนัสรายเดือนตามตำแหน่ง\n🎁 **Leadership Bonus** - โบนัสผู้นำทีม\n🏆 **Achievement Bonus** - โบนัสเมื่อถึงเป้า\n✈️ **Travel Incentive** - รางวัลท่องเที่ยว\n🚗 **Car Bonus** - โบนัสรถยนต์\n\n**ตัวอย่างโบนัส:**\n```\nSilver:    5,000 บาท/เดือน\nGold:     15,000 บาท/เดือน\nPlatinum: 35,000 บาท/เดือน\n```",
                    ],
                ]),
                'quiz' => [
                    'time_limit' => 15,
                    'questions' => [
                        [
                            'question' => 'Rank สูงสุดในระบบคือ?',
                            'answers' => [
                                ['text' => 'Diamond', 'is_correct' => false],
                                ['text' => 'Platinum', 'is_correct' => false],
                                ['text' => 'Crown Diamond', 'is_correct' => true],
                                ['text' => 'Gold', 'is_correct' => false],
                            ],
                        ],
                        [
                            'question' => 'Personal PV คืออะไร?',
                            'answers' => [
                                ['text' => 'ยอด PV รวมทั้งทีม', 'is_correct' => false],
                                ['text' => 'ยอด PV ส่วนตัว', 'is_correct' => true],
                                ['text' => 'จำนวนผู้แนะนำ', 'is_correct' => false],
                                ['text' => 'โบนัสตำแหน่ง', 'is_correct' => false],
                            ],
                        ],
                        [
                            'question' => 'โบนัสประเภทใดเป็นโบนัสรายเดือน?',
                            'answers' => [
                                ['text' => 'Achievement Bonus', 'is_correct' => false],
                                ['text' => 'Travel Incentive', 'is_correct' => false],
                                ['text' => 'Rank Bonus', 'is_correct' => true],
                                ['text' => 'Car Bonus', 'is_correct' => false],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'ระบบ AI Bot และ Chatbot',
                'slug' => 'thaiprompt-ai-bot',
                'excerpt' => 'เรียนรู้การใช้งาน AI Chatbot, LINE Bot และเครื่องมือ AI อื่นๆ',
                'duration' => 30,
                'difficulty' => 'intermediate',
                'course_level' => 3,
                'is_featured' => true,
                'tags' => ['AI', 'Chatbot', 'LINE Bot', 'Automation'],
                'content' => $this->generateContent('ระบบ AI Bot และ Chatbot', [
                    [
                        'title' => 'AI Features ใน Thaiprompt',
                        'content' => "**เครื่องมือ AI ที่มีให้ใช้:**\n\n🤖 **AI Chatbot** - Chatbot อัจฉริยะตอบลูกค้า 24/7\n🎨 **AI Image Generator** - สร้างรูปภาพจากข้อความ\n📝 **AI Content Writer** - เขียนเนื้อหาอัตโนมัติ\n💬 **LINE AI Bot** - Bot สำหรับ LINE Official Account\n🔊 **AI Voice** - แปลงข้อความเป็นเสียง\n🌐 **AI Translator** - แปลภาษาอัตโนมัติ",
                    ],
                    [
                        'title' => 'การสร้าง AI Chatbot',
                        'content' => "**ขั้นตอนสร้าง Chatbot:**\n\n1. ไปที่ \"AI Bot\" > \"สร้าง Bot ใหม่\"\n2. ตั้งชื่อและ Personality ให้ Bot\n3. เพิ่ม Knowledge Base (ข้อมูลสินค้า, FAQ)\n4. ตั้งค่าการตอบกลับอัตโนมัติ\n5. ทดสอบและปรับปรุง\n6. เปิดใช้งาน\n\n**Tips:** ยิ่งเพิ่ม Knowledge Base มาก Bot จะตอบได้แม่นยำขึ้น",
                    ],
                    [
                        'title' => 'LINE AI Bot',
                        'content' => "**การเชื่อมต่อ LINE:**\n\n1. มี LINE Official Account\n2. เข้าเมนู \"LINE Bot\" > \"เชื่อมต่อ\"\n3. กรอก Channel ID และ Secret\n4. ตั้งค่า Webhook\n5. เปิดใช้งาน AI\n\n**ฟีเจอร์ LINE Bot:**\n• รับสมัครสมาชิกอัตโนมัติ\n• ตอบคำถามสินค้า\n• ส่ง Broadcast ข่าวสาร\n• Rich Menu ปรับแต่งได้",
                    ],
                ]),
                'quiz' => [
                    'time_limit' => 15,
                    'questions' => [
                        [
                            'question' => 'AI Image Generator ใช้ทำอะไร?',
                            'answers' => [
                                ['text' => 'แปลภาษา', 'is_correct' => false],
                                ['text' => 'สร้างรูปภาพจากข้อความ', 'is_correct' => true],
                                ['text' => 'เขียนเนื้อหา', 'is_correct' => false],
                                ['text' => 'ตอบลูกค้า', 'is_correct' => false],
                            ],
                        ],
                        [
                            'question' => 'Knowledge Base ใน AI Chatbot คือ?',
                            'answers' => [
                                ['text' => 'รูปแบบการตอบกลับ', 'is_correct' => false],
                                ['text' => 'ข้อมูลที่ Bot ใช้ตอบคำถาม', 'is_correct' => true],
                                ['text' => 'ชื่อของ Bot', 'is_correct' => false],
                                ['text' => 'รหัสเชื่อมต่อ', 'is_correct' => false],
                            ],
                        ],
                        [
                            'question' => 'การเชื่อมต่อ LINE Bot ต้องมีอะไร?',
                            'answers' => [
                                ['text' => 'LINE Official Account', 'is_correct' => true],
                                ['text' => 'LINE Premium', 'is_correct' => false],
                                ['text' => 'LINE@ เก่า', 'is_correct' => false],
                                ['text' => 'LINE TV', 'is_correct' => false],
                            ],
                        ],
                    ],
                ],
            ],

            // ========== ระดับ 4: ก้าวหน้า ==========
            [
                'title' => 'การจัดการร้านค้า E-Commerce',
                'slug' => 'thaiprompt-ecommerce-management',
                'excerpt' => 'บริหารร้านค้าออนไลน์ครบวงจร จัดการสินค้า คำสั่งซื้อ และการตลาด',
                'duration' => 40,
                'difficulty' => 'intermediate',
                'course_level' => 4,
                'is_featured' => true,
                'tags' => ['E-Commerce', 'ร้านค้า', 'สินค้า', 'การตลาด'],
                'content' => $this->generateContent('การจัดการร้านค้า E-Commerce', [
                    [
                        'title' => 'การตั้งค่าร้านค้า',
                        'content' => "**ขั้นตอนสร้างร้านค้า:**\n\n1. สมัครเป็น Seller/Vendor\n2. กรอกข้อมูลร้านค้า:\n   • ชื่อร้าน\n   • โลโก้และแบนเนอร์\n   • คำอธิบายร้าน\n   • ที่อยู่และเบอร์ติดต่อ\n3. เพิ่มบัญชีรับเงิน\n4. ตั้งค่าการจัดส่ง\n5. เปิดร้าน",
                    ],
                    [
                        'title' => 'การจัดการสินค้า',
                        'content' => "**เพิ่มสินค้าใหม่:**\n\n1. ไปที่ \"สินค้า\" > \"เพิ่มสินค้า\"\n2. กรอกข้อมูล:\n   • ชื่อและคำอธิบาย\n   • รูปภาพ (หลายรูป)\n   • ราคาและส่วนลด\n   • ตัวเลือก (สี, ไซส์)\n   • สต๊อก\n   • น้ำหนักและขนาด\n3. ตั้งค่า PV (Point Value)\n4. เผยแพร่\n\n**จัดการสต๊อก:**\n• ดู Stock Alert\n• อัปเดตสต๊อกแบบ Batch\n• Import/Export Excel",
                    ],
                    [
                        'title' => 'การจัดการคำสั่งซื้อ',
                        'content' => "**Workflow คำสั่งซื้อ:**\n\n1. **รอยืนยัน** - ตรวจสอบข้อมูล\n2. **กำลังจัดเตรียม** - จัดของและแพ็ค\n3. **จัดส่งแล้ว** - ส่งพัสดุและอัปเดตเลขพัสดุ\n4. **สำเร็จ** - ลูกค้าได้รับสินค้า\n\n**การยกเลิก/คืนเงิน:**\n• ยกเลิกได้ก่อนจัดส่ง\n• คืนเงินผ่านระบบ Refund\n• บันทึกเหตุผลทุกครั้ง",
                    ],
                    [
                        'title' => 'การทำการตลาดร้านค้า',
                        'content' => "**เครื่องมือการตลาด:**\n\n🎫 **คูปอง** - สร้างส่วนลดดึงดูดลูกค้า\n⚡ **Flash Sale** - ลดราคาช่วงเวลาจำกัด\n🎁 **Bundle** - ขายเป็นชุดราคาพิเศษ\n📣 **โฆษณา** - ซื้อพื้นที่โฆษณาในระบบ\n📧 **Email Marketing** - ส่งข่าวสารถึงลูกค้า\n\n**Tips:** ใช้หลายเครื่องมือร่วมกันเพื่อผลลัพธ์ที่ดีที่สุด",
                    ],
                ]),
                'quiz' => [
                    'time_limit' => 18,
                    'questions' => [
                        [
                            'question' => 'PV ในสินค้าใช้สำหรับอะไร?',
                            'answers' => [
                                ['text' => 'ราคาสินค้า', 'is_correct' => false],
                                ['text' => 'คะแนนสำหรับคำนวณค่าคอมมิชชั่น MLM', 'is_correct' => true],
                                ['text' => 'น้ำหนักสินค้า', 'is_correct' => false],
                                ['text' => 'จำนวนสต๊อก', 'is_correct' => false],
                            ],
                        ],
                        [
                            'question' => 'Flash Sale คือ?',
                            'answers' => [
                                ['text' => 'ขายเป็นชุด', 'is_correct' => false],
                                ['text' => 'ลดราคาช่วงเวลาจำกัด', 'is_correct' => true],
                                ['text' => 'คูปองส่วนลด', 'is_correct' => false],
                                ['text' => 'โฆษณา', 'is_correct' => false],
                            ],
                        ],
                        [
                            'question' => 'สถานะคำสั่งซื้อหลัง "กำลังจัดเตรียม" คือ?',
                            'answers' => [
                                ['text' => 'รอยืนยัน', 'is_correct' => false],
                                ['text' => 'จัดส่งแล้ว', 'is_correct' => true],
                                ['text' => 'สำเร็จ', 'is_correct' => false],
                                ['text' => 'ยกเลิก', 'is_correct' => false],
                            ],
                        ],
                    ],
                ],
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
