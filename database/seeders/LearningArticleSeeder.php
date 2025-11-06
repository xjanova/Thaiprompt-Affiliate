<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LearningArticle;
use App\Models\LearningCategory;
use App\Models\User;
use Illuminate\Support\Str;

class LearningArticleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get first admin user
        $admin = User::where('role', 'admin')->first();

        if (!$admin) {
            $admin = User::first();
        }

        if (!$admin) {
            $this->command->error('No users found. Please create a user first.');
            return;
        }

        $articles = [
            // เริ่มต้นใช้งาน AI
            [
                'category_slug' => 'getting-started-ai',
                'title' => 'รู้จักกับ AI และ ChatGPT สำหรับมือใหม่',
                'slug' => 'introduction-to-ai-chatgpt',
                'excerpt' => 'เรียนรู้พื้นฐาน AI และวิธีการใช้งาน ChatGPT เพื่อเพิ่มประสิทธิภาพในการทำงาน',
                'content' => '<h2>รู้จักกับ AI และ ChatGPT</h2>
<p>AI (Artificial Intelligence) หรือปัญญาประดิษฐ์ คือเทคโนโลยีที่ทำให้คอมพิวเตอร์สามารถเรียนรู้และทำงานที่ต้องใช้สติปัญญาของมนุษย์ได้</p>

<h3>ChatGPT คืออะไร?</h3>
<p>ChatGPT เป็น AI Chatbot ที่พัฒนาโดย OpenAI สามารถสนทนา ตอบคำถาม และช่วยงานต่างๆ ได้อย่างชาญฉลาด</p>

<h3>ประโยชน์ของ ChatGPT</h3>
<ul>
<li>ช่วยเขียนเนื้อหา บทความ และอีเมล</li>
<li>วิเคราะห์ข้อมูลและสรุปใจความสำคัญ</li>
<li>แปลภาษาและแก้ไขไวยากรณ์</li>
<li>เขียนโค้ดและแก้ bug</li>
<li>ระดมความคิดและวางแผน</li>
</ul>

<h3>วิธีเริ่มต้นใช้งาน</h3>
<ol>
<li>สมัครบัญชีที่ chat.openai.com</li>
<li>เข้าสู่ระบบและเริ่มสนทนา</li>
<li>ใช้คำสั่ง (Prompt) ที่ชัดเจน</li>
<li>ทดลองและปรับปรุงคำถาม</li>
</ol>',
                'video_url' => 'https://www.youtube.com/embed/example1',
                'thumbnail' => 'https://via.placeholder.com/800x450/667eea/ffffff?text=AI+ChatGPT',
                'estimated_duration' => 30,
                'difficulty' => 'beginner',
                'is_published' => true,
                'is_featured' => true,
                'tags' => ['AI', 'ChatGPT', 'เริ่มต้น'],
                'views' => 1250,
                'order' => 1,
            ],
            [
                'category_slug' => 'getting-started-ai',
                'title' => 'Prompt Engineering: ศิลปะการเขียนคำสั่ง AI',
                'slug' => 'prompt-engineering-basics',
                'excerpt' => 'เทคนิคการเขียน Prompt ที่ดีเพื่อให้ AI ตอบคำถามได้ตรงประเด็นและมีประสิทธิภาพ',
                'content' => '<h2>Prompt Engineering คืออะไร?</h2>
<p>Prompt Engineering คือศิลปะและวิทยาศาสตร์ในการออกแบบคำสั่งให้ AI เพื่อให้ได้ผลลัพธ์ที่ดีที่สุด</p>

<h3>หลักการเขียน Prompt ที่ดี</h3>
<ul>
<li><strong>ชัดเจน:</strong> ระบุสิ่งที่ต้องการอย่างชัดเจน</li>
<li><strong>เฉพาะเจาจง:</strong> ให้รายละเอียดที่จำเป็น</li>
<li><strong>มีโครงสร้าง:</strong> แบ่งคำสั่งเป็นส่วนๆ</li>
<li><strong>ให้บริบท:</strong> อธิบายสถานการณ์และเป้าหมาย</li>
</ul>

<h3>ตัวอย่าง Prompt ที่ดี</h3>
<pre><code>คุณคือผู้เชี่ยวชาญด้านการตลาดดิจิทัล
ช่วยเขียนโพสต์ Facebook ประชาสัมพันธ์คอร์สออนไลน์
- หัวข้อ: เรียนรู้การใช้ AI
- กลุ่มเป้าหมาย: นักธุรกิจออนไลน์
- ความยาว: 100-150 คำ
- โทนเสียง: เป็นกันเองและสร้างแรงบันดาลใจ</code></pre>',
                'video_url' => 'https://www.youtube.com/embed/example2',
                'thumbnail' => 'https://via.placeholder.com/800x450/764ba2/ffffff?text=Prompt+Engineering',
                'estimated_duration' => 45,
                'difficulty' => 'intermediate',
                'is_published' => true,
                'is_featured' => true,
                'tags' => ['Prompt', 'AI', 'เทคนิค'],
                'views' => 980,
                'order' => 2,
            ],

            // Affiliate Marketing
            [
                'category_slug' => 'affiliate-marketing',
                'title' => 'พื้นฐาน Affiliate Marketing ฉบับมือใหม่',
                'slug' => 'affiliate-marketing-basics',
                'excerpt' => 'เริ่มต้นสร้างรายได้ออนไลน์ด้วย Affiliate Marketing แบบ step-by-step',
                'content' => '<h2>Affiliate Marketing คืออะไร?</h2>
<p>Affiliate Marketing คือการตลาดแบบพันธมิตร ที่คุณได้รับค่าคอมมิชชั่นจากการแนะนำสินค้าหรือบริการของผู้อื่น</p>

<h3>ข้อดีของ Affiliate Marketing</h3>
<ul>
<li>ไม่ต้องมีสินค้าของตัวเอง</li>
<li>ไม่ต้องจัดการสต๊อกหรือจัดส่ง</li>
<li>สามารถทำที่ไหนก็ได้</li>
<li>รายได้แบบพาสซีฟ</li>
<li>เริ่มต้นได้ง่าย ทุนน้อย</li>
</ul>

<h3>ขั้นตอนการเริ่มต้น</h3>
<ol>
<li>เลือก Niche ที่สนใจและมีตลาด</li>
<li>สมัครโปรแกรม Affiliate</li>
<li>สร้างแพลตฟอร์มแชร์ (Blog, Social Media)</li>
<li>สร้างเนื้อหาที่มีคุณภาพ</li>
<li>โปรโมทลิงก์ Affiliate</li>
<li>วิเคราะห์และปรับปรุง</li>
</ol>',
                'video_url' => null,
                'thumbnail' => 'https://via.placeholder.com/800x450/f093fb/ffffff?text=Affiliate+Basics',
                'estimated_duration' => 60,
                'difficulty' => 'beginner',
                'is_published' => true,
                'is_featured' => false,
                'tags' => ['Affiliate', 'Marketing', 'รายได้ออนไลน์'],
                'views' => 1450,
                'order' => 1,
            ],
            [
                'category_slug' => 'affiliate-marketing',
                'title' => 'กลยุทธ์ Affiliate Marketing ขั้นสูง',
                'slug' => 'advanced-affiliate-strategies',
                'excerpt' => 'เทคนิคและกลยุทธ์ขั้นสูงเพื่อเพิ่มยอดขายและคอมมิชชั่นของคุณ',
                'content' => '<h2>กลยุทธ์ขั้นสูงสำหรับ Affiliate</h2>

<h3>1. Email Marketing Automation</h3>
<p>สร้างระบบอีเมลอัตโนมัติเพื่อ nurture ลูกค้าและเพิ่มคอนเวอร์ชั่น</p>

<h3>2. SEO for Affiliate Sites</h3>
<p>เทคนิค SEO เพื่อดึงทราฟฟิกจาก Google แบบฟรี</p>

<h3>3. Retargeting Campaigns</h3>
<p>ใช้ Facebook Pixel และ Google Ads ตามจับลูกค้า</p>

<h3>4. Product Comparison Content</h3>
<p>สร้างเนื้อหาเปรียบเทียบสินค้าที่มี conversion rate สูง</p>',
                'video_url' => 'https://www.youtube.com/embed/example3',
                'thumbnail' => 'https://via.placeholder.com/800x450/f5576c/ffffff?text=Advanced+Affiliate',
                'estimated_duration' => 90,
                'difficulty' => 'advanced',
                'is_published' => true,
                'is_featured' => true,
                'tags' => ['Affiliate', 'ขั้นสูง', 'กลยุทธ์'],
                'views' => 756,
                'order' => 2,
            ],

            // การตลาดดิจิทัล
            [
                'category_slug' => 'digital-marketing',
                'title' => 'Facebook Ads ฉบับเร่งรัด',
                'slug' => 'facebook-ads-crash-course',
                'excerpt' => 'เรียนรู้การยิง Facebook Ads ให้ได้ผลลัพธ์จริงภายใน 1 ชั่วโมง',
                'content' => '<h2>Facebook Ads สำหรับมือใหม่</h2>
<p>Facebook Ads เป็นเครื่องมือโฆษณาที่ทรงพลังที่สุดในปัจจุบัน</p>

<h3>ประเภทของโฆษณา</h3>
<ul>
<li>Image Ads - โฆษณารูปภาพ</li>
<li>Video Ads - โฆษณาวิดีโอ</li>
<li>Carousel Ads - โฆษณาแบบหมุน</li>
<li>Collection Ads - โฆษณาคอลเลคชั่น</li>
</ul>

<h3>ขั้นตอนการสร้างแคมเปญ</h3>
<ol>
<li>กำหนดวัตถุประสงค์</li>
<li>เลือก Audience</li>
<li>ตั้งงบประมาณ</li>
<li>ออกแบบโฆษณา</li>
<li>Launch และติดตามผล</li>
</ol>',
                'video_url' => 'https://www.youtube.com/embed/example4',
                'thumbnail' => 'https://via.placeholder.com/800x450/4facfe/ffffff?text=Facebook+Ads',
                'estimated_duration' => 75,
                'difficulty' => 'intermediate',
                'is_published' => true,
                'is_featured' => true,
                'tags' => ['Facebook', 'Ads', 'การตลาด'],
                'views' => 2100,
                'order' => 1,
            ],

            // สร้างเนื้อหา
            [
                'category_slug' => 'content-creation',
                'title' => 'Copywriting ที่ขายได้',
                'slug' => 'copywriting-that-sells',
                'excerpt' => 'เทคนิคการเขียนคอปปี้ที่กระตุ้นให้คนอยากซื้อ',
                'content' => '<h2>หลักการเขียน Copy ที่ดี</h2>

<h3>AIDA Formula</h3>
<ul>
<li><strong>Attention:</strong> ดึงดูดความสนใจ</li>
<li><strong>Interest:</strong> สร้างความสนใจ</li>
<li><strong>Desire:</strong> กระตุ้นความต้องการ</li>
<li><strong>Action:</strong> เรียกร้องให้ลงมือ</li>
</ul>

<h3>เทคนิคขั้นสูง</h3>
<p>ใช้ความเจ็บปวดและความต้องการของลูกค้าเป็นตัวขับเคลื่อน</p>',
                'video_url' => null,
                'thumbnail' => 'https://via.placeholder.com/800x450/43e97b/ffffff?text=Copywriting',
                'estimated_duration' => 50,
                'difficulty' => 'intermediate',
                'is_published' => true,
                'is_featured' => false,
                'tags' => ['Copywriting', 'เนื้อหา', 'การขาย'],
                'views' => 890,
                'order' => 1,
            ],

            // ธุรกิจออนไลน์
            [
                'category_slug' => 'online-business',
                'title' => 'วิธีสร้างธุรกิจออนไลน์ที่ประสบความสำเร็จ',
                'slug' => 'building-successful-online-business',
                'excerpt' => 'แผนที่ครบวงจรในการสร้างและพัฒนาธุรกิจออนไลน์ให้เติบโตอย่างยั่งยืน',
                'content' => '<h2>5 ขั้นตอนสร้างธุรกิจออนไลน์</h2>

<h3>1. หาไอเดียและตรวจสอบตลาด</h3>
<p>วิเคราะห์ความต้องการของตลาดและหา pain points</p>

<h3>2. สร้างแบรนด์และตำแหน่งทางการตลาด</h3>
<p>กำหนด brand identity และ positioning ที่ชัดเจน</p>

<h3>3. สร้างสินค้าหรือบริการ MVP</h3>
<p>เริ่มจาก Minimum Viable Product และปรับปรุงต่อ</p>

<h3>4. Marketing และหาลูกค้า</h3>
<p>ใช้กลยุทธ์การตลาดที่หลากหลาย</p>

<h3>5. Scale และเติบโต</h3>
<p>ขยายธุรกิจอย่างเป็นระบบ</p>',
                'video_url' => 'https://www.youtube.com/embed/example5',
                'thumbnail' => 'https://via.placeholder.com/800x450/fa709a/ffffff?text=Online+Business',
                'estimated_duration' => 120,
                'difficulty' => 'intermediate',
                'is_published' => true,
                'is_featured' => true,
                'tags' => ['ธุรกิจออนไลน์', 'Startup', 'การเติบโต'],
                'views' => 1678,
                'order' => 1,
            ],

            // เครื่องมือและเทคโนโลยี
            [
                'category_slug' => 'tools-technology',
                'title' => '10 เครื่องมือ AI ที่ทุกคนต้องมี',
                'slug' => 'top-10-ai-tools',
                'excerpt' => 'รวมเครื่องมือ AI ที่จะช่วยเพิ่มประสิทธิภาพการทำงานของคุณ',
                'content' => '<h2>เครื่องมือ AI ยอดนิยม</h2>

<h3>1. ChatGPT - AI Chatbot</h3>
<p>ช่วยเขียน ค้นคว้า และวิเคราะห์</p>

<h3>2. Midjourney - AI สร้างภาพ</h3>
<p>สร้างภาพสวยงามจากคำสั่ง</p>

<h3>3. Notion AI - จดบันทึกอัจฉริยะ</h3>
<p>เขียนและจัดระเบียบงานด้วย AI</p>

<h3>4. Grammarly - แก้ไขภาษาอังกฤษ</h3>
<p>ตรวจสอบไวยากรณ์และปรับปรุงการเขียน</p>

<h3>5. Canva AI - ออกแบบกราฟิก</h3>
<p>สร้างดีไซน์สวยๆ ง่ายๆ</p>',
                'video_url' => null,
                'thumbnail' => 'https://via.placeholder.com/800x450/30cfd0/ffffff?text=AI+Tools',
                'estimated_duration' => 40,
                'difficulty' => 'beginner',
                'is_published' => true,
                'is_featured' => false,
                'tags' => ['AI', 'Tools', 'เครื่องมือ'],
                'views' => 2345,
                'order' => 1,
            ],
        ];

        foreach ($articles as $articleData) {
            $category = LearningCategory::where('slug', $articleData['category_slug'])->first();

            if (!$category) {
                $this->command->warn("Category {$articleData['category_slug']} not found. Skipping article {$articleData['title']}");
                continue;
            }

            unset($articleData['category_slug']);

            LearningArticle::updateOrCreate(
                ['slug' => $articleData['slug']],
                array_merge($articleData, [
                    'category_id' => $category->id,
                    'created_by' => $admin->id,
                    'updated_by' => $admin->id,
                    'published_at' => now(),
                ])
            );
        }

        $this->command->info('Learning articles seeded successfully!');
    }
}
