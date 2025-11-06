# Academy Platform Seeders

## 📚 ภาพรวม

Seeders สำหรับสร้างข้อมูลตัวอย่างของ Academy Platform รวมถึง:
- หมวดหมู่คอร์ส (6 หมวดหมู่)
- คอร์สตัวอย่าง (10 คอร์ส)
- Quiz และคำถาม (4 quizzes)

## 🚀 การใช้งาน

### รัน Seeder ทั้งหมด (รวม Academy)

```bash
php artisan db:seed
```

### รัน Seeder เฉพาะ Academy Platform

```bash
php artisan db:seed --class=AcademySeeder
```

### รัน Seeder แยกเป็นแต่ละส่วน

```bash
# หมวดหมู่เท่านั้น
php artisan db:seed --class=LearningCategorySeeder

# คอร์สเท่านั้น (ต้องมีหมวดหมู่ก่อน)
php artisan db:seed --class=LearningArticleSeeder

# Quiz เท่านั้น (ต้องมีคอร์สก่อน)
php artisan db:seed --class=QuizSeeder
```

## 📋 ข้อมูลที่จะถูกสร้าง

### 1. หมวดหมู่ (6 Categories)

| ชื่อหมวดหมู่ | Slug | Icon | คำอธิบาย |
|-------------|------|------|---------|
| เริ่มต้นใช้งาน AI | getting-started-ai | 🤖 | พื้นฐาน AI และ ChatGPT |
| Affiliate Marketing | affiliate-marketing | 💰 | การตลาด Affiliate |
| การตลาดดิจิทัล | digital-marketing | 📱 | Digital Marketing |
| สร้างเนื้อหา | content-creation | ✍️ | Content Creation |
| ธุรกิจออนไลน์ | online-business | 💼 | Online Business |
| เครื่องมือและเทคโนโลยี | tools-technology | 🔧 | Tools & Technology |

### 2. คอร์สตัวอย่าง (10 Courses)

**เริ่มต้นใช้งาน AI:**
1. รู้จักกับ AI และ ChatGPT สำหรับมือใหม่
2. Prompt Engineering: ศิลปะการเขียนคำสั่ง AI

**Affiliate Marketing:**
3. พื้นฐาน Affiliate Marketing ฉบับมือใหม่
4. กลยุทธ์ Affiliate Marketing ขั้นสูง

**การตลาดดิจิทัล:**
5. Facebook Ads ฉบับเร่งรัด

**สร้างเนื้อหา:**
6. Copywriting ที่ขายได้

**ธุรกิจออนไลน์:**
7. วิธีสร้างธุรกิจออนไลน์ที่ประสบความสำเร็จ

**เครื่องมือและเทคโนโลยี:**
8. 10 เครื่องมือ AI ที่ทุกคนต้องมี

### 3. Quiz (4 Quizzes)

แต่ละ Quiz มีคำถามหลายประเภท:
- Multiple Choice (เลือกตอบเดียว)
- Multiple Answer (เลือกได้หลายข้อ)
- True/False (จริง/เท็จ)

**Quiz List:**
1. **ทดสอบความเข้าใจ: AI และ ChatGPT**
   - 5 คำถาม
   - ผ่าน: 70%
   - เวลา: 15 นาที
   - Required: ✅

2. **Quiz: Prompt Engineering**
   - 3 คำถาม
   - ผ่าน: 75%
   - เวลา: 20 นาที
   - Required: ✅

3. **แบบทดสอบ: Affiliate Marketing พื้นฐาน**
   - 3 คำถาม
   - ผ่าน: 70%
   - เวลา: 15 นาที
   - Required: ❌

4. **ทดสอบความรู้: Facebook Ads**
   - 2 คำถาม
   - ผ่าน: 70%
   - เวลา: 10 นาที
   - Required: ❌

## 🎯 คุณสมบัติที่ถูก Seed

### คอร์ส (Learning Articles)
- ✅ Title, Slug, Excerpt
- ✅ เนื้อหา HTML แบบครบถ้วน
- ✅ Video URL (บางคอร์ส)
- ✅ Thumbnail placeholder
- ✅ Estimated Duration
- ✅ Difficulty Level (beginner/intermediate/advanced)
- ✅ Featured flag
- ✅ Tags
- ✅ View counts
- ✅ Published status

### Quiz
- ✅ Title, Description
- ✅ Passing Score
- ✅ Time Limit
- ✅ Max Attempts
- ✅ Required flag
- ✅ Multiple question types
- ✅ Correct answers
- ✅ Explanations

## 🔄 Reset และ Reseed

### ลบข้อมูลและ Seed ใหม่ทั้งหมด

```bash
php artisan migrate:fresh --seed
```

### ลบข้อมูล Academy และ Seed ใหม่

```bash
# ลบข้อมูลเก่า (ถ้ามี)
php artisan db:seed --class=AcademySeeder
```

**⚠️ คำเตือน:**
- Seeder ใช้ `updateOrCreate` จะไม่สร้างซ้ำถ้ามีอยู่แล้ว
- ถ้าต้องการสร้างใหม่ทั้งหมด ให้ลบข้อมูลก่อน

## 🎨 การ Customize

### เพิ่มหมวดหมู่ใหม่

แก้ไขไฟล์: `database/seeders/LearningCategorySeeder.php`

```php
[
    'name' => 'ชื่อหมวดหมู่',
    'slug' => 'category-slug',
    'description' => 'คำอธิบาย',
    'icon' => '🎯',
    'color' => '#667eea',
    'order' => 7,
    'is_active' => true,
],
```

### เพิ่มคอร์สใหม่

แก้ไขไฟล์: `database/seeders/LearningArticleSeeder.php`

```php
[
    'category_slug' => 'category-slug',
    'title' => 'ชื่อคอร์ส',
    'slug' => 'course-slug',
    'excerpt' => 'สรุปสั้นๆ',
    'content' => '<h2>เนื้อหา HTML</h2>',
    'video_url' => null,
    'thumbnail' => 'https://...',
    'estimated_duration' => 60,
    'difficulty' => 'beginner',
    'is_published' => true,
    'is_featured' => true,
    'tags' => ['tag1', 'tag2'],
    'views' => 0,
    'order' => 1,
],
```

### เพิ่ม Quiz ใหม่

แก้ไขไฟล์: `database/seeders/QuizSeeder.php`

```php
[
    'article_slug' => 'course-slug',
    'title' => 'ชื่อ Quiz',
    'description' => 'คำอธิบาย',
    'passing_score' => 70,
    'time_limit' => 15,
    'max_attempts' => 3,
    'is_required' => false,
    'questions' => [
        // คำถาม...
    ],
],
```

## ✅ การตรวจสอบ

หลังจาก seed แล้ว ตรวจสอบได้ที่:

```bash
# จำนวน Categories
SELECT COUNT(*) FROM learning_categories;

# จำนวน Articles
SELECT COUNT(*) FROM learning_articles;

# จำนวน Quizzes
SELECT COUNT(*) FROM quizzes;

# จำนวน Questions
SELECT COUNT(*) FROM quiz_questions;

# จำนวน Options
SELECT COUNT(*) FROM question_options;
```

หรือเข้าชมผ่าน UI:
- `/admin/learning-center` - หน้าหลัก Academy
- `/admin/categories` - จัดการหมวดหมู่
- `/admin/articles` - จัดการคอร์ส

## 🐛 Troubleshooting

### ปัญหา: ไม่พบ User

```
Solution: สร้าง admin user ก่อน
php artisan db:seed --class=DemoUsersSeeder
```

### ปัญหา: Category not found

```
Solution: รัน Category seeder ก่อน
php artisan db:seed --class=LearningCategorySeeder
```

### ปัญหา: Article not found (Quiz seeder)

```
Solution: รัน Article seeder ก่อน
php artisan db:seed --class=LearningArticleSeeder
```

## 📝 Notes

- Seeders ออกแบบให้รันได้หลายครั้งโดยไม่สร้างซ้ำ (idempotent)
- ใช้ `updateOrCreate` เพื่อป้องกันการสร้างซ้ำ
- Slug เป็น unique identifier หลัก
- Video URLs เป็น placeholder (ต้องเปลี่ยนเป็น URL จริง)
- Thumbnails เป็น placeholder images

## 🎓 ขั้นตอนถัดไป

หลังจาก seed แล้ว:

1. เข้าไปที่ `/admin/learning-center` เพื่อดูหน้าแรก
2. คลิกที่คอร์สเพื่อดูรายละเอียด
3. ทำ Quiz เพื่อทดสอบ
4. ดู Certificate ที่ `/admin/certificates`
5. เข้า Instructor Dashboard ที่ `/admin/instructor/dashboard`

---

**Happy Learning!** 🎓✨
