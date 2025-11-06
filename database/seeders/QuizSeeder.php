<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuestionOption;
use App\Models\LearningArticle;

class QuizSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $quizzes = [
            [
                'article_slug' => 'introduction-to-ai-chatgpt',
                'title' => 'ทดสอบความเข้าใจ: AI และ ChatGPT',
                'description' => 'ทดสอบความเข้าใจเกี่ยวกับพื้นฐาน AI และการใช้งาน ChatGPT',
                'passing_score' => 70,
                'time_limit' => 15,
                'max_attempts' => 3,
                'is_required' => true,
                'questions' => [
                    [
                        'type' => 'multiple_choice',
                        'question' => 'AI ย่อมาจากอะไร?',
                        'points' => 10,
                        'explanation' => 'AI ย่อมาจาก Artificial Intelligence หรือปัญญาประดิษฐ์',
                        'options' => [
                            ['text' => 'Artificial Intelligence', 'is_correct' => true],
                            ['text' => 'Automated Intelligence', 'is_correct' => false],
                            ['text' => 'Advanced Information', 'is_correct' => false],
                            ['text' => 'Artificial Information', 'is_correct' => false],
                        ],
                    ],
                    [
                        'type' => 'multiple_choice',
                        'question' => 'ChatGPT ถูกพัฒนาโดยบริษัทใด?',
                        'points' => 10,
                        'explanation' => 'ChatGPT ถูกพัฒนาโดย OpenAI',
                        'options' => [
                            ['text' => 'Google', 'is_correct' => false],
                            ['text' => 'OpenAI', 'is_correct' => true],
                            ['text' => 'Microsoft', 'is_correct' => false],
                            ['text' => 'Meta', 'is_correct' => false],
                        ],
                    ],
                    [
                        'type' => 'multiple_answer',
                        'question' => 'ChatGPT สามารถช่วยงานด้านใดได้บ้าง? (เลือกได้มากกว่า 1 ข้อ)',
                        'points' => 15,
                        'explanation' => 'ChatGPT สามารถช่วยได้หลายด้าน ทั้งการเขียน การวิเคราะห์ และการแปลภาษา',
                        'options' => [
                            ['text' => 'เขียนเนื้อหาและบทความ', 'is_correct' => true],
                            ['text' => 'วิเคราะห์ข้อมูล', 'is_correct' => true],
                            ['text' => 'แปลภาษา', 'is_correct' => true],
                            ['text' => 'ซื้อของออนไลน์', 'is_correct' => false],
                        ],
                    ],
                    [
                        'type' => 'true_false',
                        'question' => 'ChatGPT สามารถเข้าถึงอินเทอร์เน็ตและดูข้อมูลแบบ real-time ได้',
                        'points' => 10,
                        'explanation' => 'ChatGPT รุ่นพื้นฐานไม่สามารถเข้าถึงอินเทอร์เน็ตแบบ real-time ได้ แต่มีความรู้จากข้อมูลที่ถูก train มา',
                        'options' => [
                            ['text' => 'จริง', 'is_correct' => false],
                            ['text' => 'เท็จ', 'is_correct' => true],
                        ],
                    ],
                    [
                        'type' => 'multiple_choice',
                        'question' => 'การเขียน Prompt ที่ดีควรมีลักษณะอย่างไร?',
                        'points' => 15,
                        'explanation' => 'Prompt ที่ดีควรชัดเจนและมีรายละเอียด เพื่อให้ AI เข้าใจและตอบได้ตรงประเด็น',
                        'options' => [
                            ['text' => 'สั้นและกระชับที่สุด', 'is_correct' => false],
                            ['text' => 'ชัดเจนและมีรายละเอียด', 'is_correct' => true],
                            ['text' => 'ใช้ภาษายากๆ', 'is_correct' => false],
                            ['text' => 'เขียนแบบสบายๆ ไม่ต้องระบุอะไร', 'is_correct' => false],
                        ],
                    ],
                ],
            ],
            [
                'article_slug' => 'prompt-engineering-basics',
                'title' => 'Quiz: Prompt Engineering',
                'description' => 'ทดสอบทักษะการเขียน Prompt ให้มีประสิทธิภาพ',
                'passing_score' => 75,
                'time_limit' => 20,
                'max_attempts' => 2,
                'is_required' => true,
                'questions' => [
                    [
                        'type' => 'multiple_choice',
                        'question' => 'Prompt Engineering คืออะไร?',
                        'points' => 10,
                        'explanation' => 'Prompt Engineering คือศิลปะการออกแบบคำสั่งให้ AI เพื่อให้ได้ผลลัพธ์ที่ดีที่สุด',
                        'options' => [
                            ['text' => 'การสร้างโปรแกรมคอมพิวเตอร์', 'is_correct' => false],
                            ['text' => 'การออกแบบคำสั่งให้ AI', 'is_correct' => true],
                            ['text' => 'การทำงานวิศวกรรม', 'is_correct' => false],
                            ['text' => 'การเขียนโค้ด', 'is_correct' => false],
                        ],
                    ],
                    [
                        'type' => 'multiple_answer',
                        'question' => 'หลักการเขียน Prompt ที่ดีมีอะไรบ้าง?',
                        'points' => 15,
                        'options' => [
                            ['text' => 'ชัดเจนและเฉพาะเจาจง', 'is_correct' => true],
                            ['text' => 'มีโครงสร้าง', 'is_correct' => true],
                            ['text' => 'ให้บริบท', 'is_correct' => true],
                            ['text' => 'ใช้ภาษาที่ยากและซับซ้อน', 'is_correct' => false],
                        ],
                    ],
                    [
                        'type' => 'true_false',
                        'question' => 'การให้บริบทใน Prompt จะช่วยให้ AI ตอบได้ดีขึ้น',
                        'points' => 10,
                        'options' => [
                            ['text' => 'จริง', 'is_correct' => true],
                            ['text' => 'เท็จ', 'is_correct' => false],
                        ],
                    ],
                ],
            ],
            [
                'article_slug' => 'affiliate-marketing-basics',
                'title' => 'แบบทดสอบ: Affiliate Marketing พื้นฐาน',
                'description' => 'ทดสอบความเข้าใจพื้นฐานของ Affiliate Marketing',
                'passing_score' => 70,
                'time_limit' => 15,
                'max_attempts' => 3,
                'is_required' => false,
                'questions' => [
                    [
                        'type' => 'multiple_choice',
                        'question' => 'Affiliate Marketing คืออะไร?',
                        'points' => 10,
                        'explanation' => 'Affiliate Marketing คือการตลาดแบบพันธมิตรที่ได้รับค่าคอมมิชชั่นจากการแนะนำสินค้า',
                        'options' => [
                            ['text' => 'การขายของออนไลน์', 'is_correct' => false],
                            ['text' => 'การตลาดแบบพันธมิตร', 'is_correct' => true],
                            ['text' => 'การโฆษณาแบบจ่ายต่อคลิก', 'is_correct' => false],
                            ['text' => 'การขายตรง', 'is_correct' => false],
                        ],
                    ],
                    [
                        'type' => 'multiple_answer',
                        'question' => 'ข้อดีของ Affiliate Marketing มีอะไรบ้าง?',
                        'points' => 15,
                        'options' => [
                            ['text' => 'ไม่ต้องมีสินค้าของตัวเอง', 'is_correct' => true],
                            ['text' => 'ไม่ต้องจัดการสต๊อก', 'is_correct' => true],
                            ['text' => 'รายได้แบบพาสซีฟ', 'is_correct' => true],
                            ['text' => 'ต้องลงทุนเยอะ', 'is_correct' => false],
                        ],
                    ],
                    [
                        'type' => 'true_false',
                        'question' => 'Affiliate สามารถทำรายได้ได้หลายช่องทางพร้อมกัน',
                        'points' => 10,
                        'options' => [
                            ['text' => 'จริง', 'is_correct' => true],
                            ['text' => 'เท็จ', 'is_correct' => false],
                        ],
                    ],
                ],
            ],
            [
                'article_slug' => 'facebook-ads-crash-course',
                'title' => 'ทดสอบความรู้: Facebook Ads',
                'description' => 'แบบทดสอบความเข้าใจเกี่ยวกับ Facebook Ads',
                'passing_score' => 70,
                'time_limit' => 10,
                'max_attempts' => 3,
                'is_required' => false,
                'questions' => [
                    [
                        'type' => 'multiple_choice',
                        'question' => 'ประเภทโฆษณาใดที่แสดงหลายรูปภาพในโฆษณาเดียว?',
                        'points' => 10,
                        'options' => [
                            ['text' => 'Image Ads', 'is_correct' => false],
                            ['text' => 'Video Ads', 'is_correct' => false],
                            ['text' => 'Carousel Ads', 'is_correct' => true],
                            ['text' => 'Collection Ads', 'is_correct' => false],
                        ],
                    ],
                    [
                        'type' => 'true_false',
                        'question' => 'Facebook Ads สามารถกำหนดกลุ่มเป้าหมายได้อย่างละเอียด',
                        'points' => 10,
                        'options' => [
                            ['text' => 'จริง', 'is_correct' => true],
                            ['text' => 'เท็จ', 'is_correct' => false],
                        ],
                    ],
                ],
            ],
        ];

        foreach ($quizzes as $quizData) {
            $article = LearningArticle::where('slug', $quizData['article_slug'])->first();

            if (!$article) {
                $this->command->warn("Article {$quizData['article_slug']} not found. Skipping quiz.");
                continue;
            }

            $questions = $quizData['questions'];
            unset($quizData['questions']);
            unset($quizData['article_slug']);

            $quiz = Quiz::updateOrCreate(
                [
                    'article_id' => $article->id,
                    'title' => $quizData['title'],
                ],
                array_merge($quizData, [
                    'article_id' => $article->id,
                    'randomize_questions' => false,
                    'show_results_immediately' => true,
                    'show_correct_answers' => true,
                    'is_active' => true,
                ])
            );

            // Create questions
            foreach ($questions as $index => $questionData) {
                $options = $questionData['options'];
                unset($questionData['options']);

                $question = QuizQuestion::updateOrCreate(
                    [
                        'quiz_id' => $quiz->id,
                        'question' => $questionData['question'],
                    ],
                    array_merge($questionData, [
                        'quiz_id' => $quiz->id,
                        'order' => $index,
                    ])
                );

                // Create options
                foreach ($options as $optionIndex => $optionData) {
                    QuestionOption::updateOrCreate(
                        [
                            'question_id' => $question->id,
                            'option_text' => $optionData['text'],
                        ],
                        array_merge($optionData, [
                            'question_id' => $question->id,
                            'order' => $optionIndex,
                        ])
                    );
                }
            }
        }

        $this->command->info('Quizzes seeded successfully!');
    }
}
