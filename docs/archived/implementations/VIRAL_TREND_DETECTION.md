# Viral Trend Detection System

ระบบอัจฉริยะในการตรวจจับและวิเคราะห์กระแสไวรัลบนอินเทอร์เน็ต

## คุณสมบัติหลัก

### 1. **การตรวจจับกระแสไวรัล**
- ติดตามและวิเคราะห์ความเปลี่ยนแปลงบนเว็บ/บอร์ดต่างๆ
- ระบุสิ่งที่ถูกพูดถึงมากที่สุดโดยอัตโนมัติ
- คำนวณ Viral Score จากหลายปัจจัย (engagement, shares, views, comments)

### 2. **การวิเคราะห์คีย์เวิร์ด**
- แยกและวิเคราะห์คีย์เวิร์ดจากข้อมูลที่รวบรวม
- ใช้ TF-IDF สำหรับการคำนวณน้ำหนักคีย์เวิร์ด
- ติดตาม growth rate และ trending score
- หาคีย์เวิร์ดที่เกี่ยวข้องด้วย co-occurrence analysis

### 3. **การแสดงผลด้วยกราฟ**
- แสดงกราฟแนวโน้มการพูดถึง (Mentions Over Time)
- แสดงกราฟ Engagement
- Dashboard สรุปภาพรวม
- Real-time updates

### 4. **การสร้างคอนเทนต์ด้วย AI**
- สร้างบทความบล็อกจากเทรนด์ที่ตรวจพบ
- สร้างโพสต์โซเชียลมีเดียสำหรับหลายแพลตฟอร์ม (Facebook, Twitter, Instagram, TikTok)
- สร้าง Newsletter
- สร้างสรุปเนื้อหา
- รองรับ AI หลายผู้ให้บริการ (OpenAI, Claude, Local AI)

### 5. **การกำหนดแหล่งข้อมูล**
- รองรับหลายประเภท: Web, API, RSS, Social Media
- กำหนด URL และ API credentials
- ตั้งค่า scraping selectors
- กำหนดความถี่ในการตรวจสอบ
- ระบุลำดับความสำคัญ (Priority)

## โครงสร้างระบบ

### Database Schema

#### 1. `trend_sources`
- เก็บข้อมูลแหล่งที่มาของเทรนด์
- Fields: name, type, url, api_config, selectors, check_interval, priority

#### 2. `trend_data`
- เก็บข้อมูลดิบที่ scrape มา
- Fields: title, content, url, engagement_count, viral_score

#### 3. `trend_keywords`
- เก็บคีย์เวิร์ดที่แยกออกมา
- Fields: keyword, frequency, trend_score, growth_rate

#### 4. `viral_trends`
- เก็บเทรนด์ไวรัลที่ตรวจพบ
- Fields: title, viral_score, total_mentions, velocity, status

#### 5. `trend_analytics`
- เก็บ snapshot ของข้อมูลวิเคราะห์
- Fields: mention_count, engagement_count, sentiment_score

### Models

```
App\Models\
├── TrendSource.php         - แหล่งข้อมูล
├── TrendData.php           - ข้อมูลที่ scrape
├── TrendKeyword.php        - คีย์เวิร์ด
├── ViralTrend.php          - เทรนด์ไวรัล
└── TrendAnalytic.php       - Analytics snapshot
```

### Services

```
App\Services\TrendDetection\
├── TrendScraperService.php              - Web scraping
├── KeywordAnalyzerService.php           - การวิเคราะห์คีย์เวิร์ด
├── ViralTrendDetectionService.php       - ตรวจจับ viral trends
└── TrendContentGeneratorService.php     - สร้างคอนเทนต์ด้วย AI
```

### Jobs (Scheduled Tasks)

```
App\Jobs\
├── ScrapeSourcesJob.php      - Scrape แหล่งข้อมูล
├── AnalyzeTrendDataJob.php   - วิเคราะห์คีย์เวิร์ด
└── DetectViralTrendsJob.php  - ตรวจจับ viral trends
```

### Controllers

#### Admin Panel
- `App\Http\Controllers\Admin\TrendManagementController.php`
  - Dashboard
  - Trend details
  - Source management

#### API
- `App\Http\Controllers\Api\TrendApiController.php`
  - RESTful API endpoints
  - JSON responses

### Routes

#### Admin Routes (`/admin/trends`)
```
GET  /admin/trends                          - Dashboard
GET  /admin/trends/keywords                 - Browse keywords
GET  /admin/trends/{trend}                  - Trend details
POST /admin/trends/{trend}/generate-content - Generate AI content

GET  /admin/trends/sources                  - List sources
POST /admin/trends/sources                  - Create source
GET  /admin/trends/sources/{source}/edit    - Edit source
PUT  /admin/trends/sources/{source}         - Update source
DELETE /admin/trends/sources/{source}       - Delete source
POST /admin/trends/sources/{source}/test-scrape - Test scraping
```

#### API Routes (`/api/v1/trends`)
```
GET  /api/v1/trends/dashboard               - Dashboard data
GET  /api/v1/trends                         - List trends
GET  /api/v1/trends/{trend}                 - Trend details
POST /api/v1/trends/{trend}/generate-content - Generate content

GET  /api/v1/trends/keywords/trending       - Trending keywords
GET  /api/v1/trends/keywords/emerging       - Emerging keywords
GET  /api/v1/trends/keywords/{keyword}/related - Related keywords

GET  /api/v1/trends/sources                 - List sources
POST /api/v1/trends/sources                 - Create source
PUT  /api/v1/trends/sources/{source}        - Update source
DELETE /api/v1/trends/sources/{source}      - Delete source

GET  /api/v1/trends/analytics               - Analytics data
```

### Views

```
resources/views/admin/trends/
├── index.blade.php    - Dashboard
├── show.blade.php     - Trend details + charts
└── sources.blade.php  - Source management
```

## การติดตั้งและใช้งาน

### 1. Run Migrations

```bash
php artisan migrate
```

### 2. Setup Cron Jobs

เพิ่มใน Laravel Scheduler (`app/Console/Kernel.php`):

```php
protected function schedule(Schedule $schedule)
{
    // Scrape sources every 15 minutes
    $schedule->job(new ScrapeSourcesJob())->everyFifteenMinutes();

    // Analyze trend data every 30 minutes
    $schedule->job(new AnalyzeTrendDataJob())->everyThirtyMinutes();

    // Detect viral trends every hour
    $schedule->job(new DetectViralTrendsJob())->hourly();
}
```

จากนั้นเพิ่ม cron job:
```
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

### 3. Manual Commands

```bash
# Scrape all ready sources
php artisan trends:detect --scrape

# Analyze trend data
php artisan trends:detect --analyze

# Detect viral trends
php artisan trends:detect --detect

# Run all tasks
php artisan trends:detect --all
```

## การเพิ่มแหล่งข้อมูล

### ผ่าน Admin Panel

1. ไปที่ `/admin/trends/sources`
2. คลิก "Add New Source"
3. กรอกข้อมูล:
   - **Name**: ชื่อแหล่งข้อมูล
   - **Type**: web, api, rss, social_media
   - **URL**: ลิงก์แหล่งข้อมูล
   - **Scraping Method**: html, json, xml, rss
   - **Check Interval**: ระยะเวลาตรวจสอบ (นาที)
   - **Priority**: 1-10

### ผ่าน API

```bash
POST /api/v1/trends/sources
Content-Type: application/json

{
  "name": "Pantip Tech",
  "description": "Pantip technology forum",
  "type": "web",
  "url": "https://pantip.com/forum/technology",
  "scraping_method": "html",
  "check_interval": 60,
  "priority": 8,
  "selectors": {
    "container": "article.post",
    "title": "h2.title",
    "content": "div.content",
    "url": "a.link",
    "engagement": "span.like-count"
  }
}
```

### Web Scraping Configuration

สำหรับ HTML scraping ต้องกำหนด CSS selectors:

```json
{
  "selectors": {
    "container": ".post-item",        // Container ของแต่ละโพสต์
    "title": "h2.title",              // หัวข้อ
    "content": ".content",            // เนื้อหา
    "url": "a.permalink",             // URL
    "author": ".author-name",         // ผู้เขียน
    "engagement": ".like-count",      // Likes/Reactions
    "comments": ".comment-count",     // Comments
    "shares": ".share-count",         // Shares
    "views": ".view-count"            // Views
  }
}
```

### API Configuration

สำหรับ API sources:

```json
{
  "api_config": {
    "method": "GET",
    "headers": {
      "Authorization": "Bearer YOUR_TOKEN",
      "Accept": "application/json"
    },
    "params": {
      "limit": 100,
      "sort": "popular"
    },
    "data_path": "data.posts",        // JSONPath to data array
    "field_mapping": {
      "title": "post_title",
      "content": "post_content",
      "url": "permalink",
      "engagement": "likes_count",
      "comments": "comments_count"
    }
  }
}
```

## การสร้างคอนเทนต์ด้วย AI

### ผ่าน Admin Panel

1. ไปที่ trend details
2. เลือกประเภทคอนเทนต์:
   - **Blog Post**: บทความบล็อกยาว 800-1200 คำ
   - **Social Media**: โพสต์สำหรับ Facebook, Twitter, Instagram, TikTok
   - **Summary**: สรุปกระชับ 3-5 ประโยค
   - **Newsletter**: คอนเทนต์สำหรับอีเมล

### ผ่าน API

```bash
POST /api/v1/trends/{trend_id}/generate-content
Content-Type: application/json

{
  "content_type": "blog"
}
```

Response:
```json
{
  "success": true,
  "content": {
    "title": "หัวข้อบทความ",
    "content": "เนื้อหาบทความ...",
    "excerpt": "สรุปบทความ",
    "tags": ["tag1", "tag2"],
    "seo_title": "SEO title",
    "meta_description": "Meta description"
  },
  "metadata": {
    "type": "blog",
    "provider": "OpenAI",
    "model": "GPT-4"
  }
}
```

## Dashboard และ Analytics

### Dashboard (/admin/trends)
- สถิติภาพรวม (Active Trends, Rising Trends, Keywords)
- Rising Trends Table
- Top Keywords Cards
- Trending Now Cards

### Trend Details (/admin/trends/{id})
- ข้อมูลเทรนด์ (Viral Score, Mentions, Velocity, Status)
- กราฟ Mentions Over Time (Line Chart)
- กราฟ Engagement (Bar Chart)
- Top Posts ที่เกี่ยวข้อง
- ปุ่มสร้างคอนเทนต์ด้วย AI

### Keywords (/admin/trends/keywords)
- รายการคีย์เวิร์ดทั้งหมด
- Trending Score
- Growth Rate
- Frequency

## API Response Format

### Success Response
```json
{
  "success": true,
  "data": { ... }
}
```

### Error Response
```json
{
  "success": false,
  "error": "Error message",
  "errors": { ... }
}
```

## Viral Score Calculation

Viral Score คำนวณจาก:
```
score = (engagement * 0.3) + (comments * 0.25) + (shares * 0.35) + (views * 0.1)
```

พร้อมปรับด้วย time factor (คอนเทนต์ใหม่จะได้คะแนนสูงกว่า)

## Trend Status

- **rising**: กำลังขึ้น (velocity เพิ่มขึ้น > 20%)
- **peaked**: ถึงจุดสูงสุดแล้ว
- **stable**: คงที่
- **declining**: กำลังลดลง (velocity ลดลง > 20%)

## ตัวอย่างการใช้งาน

### 1. เพิ่มแหล่งข้อมูล Pantip

```php
TrendSource::create([
    'name' => 'Pantip Trending',
    'type' => 'web',
    'url' => 'https://pantip.com/tag/trending',
    'scraping_method' => 'html',
    'check_interval' => 30,
    'priority' => 9,
    'selectors' => [
        'container' => 'div.post-item',
        'title' => 'a.post-title',
        'url' => 'a.post-title',
        'engagement' => 'span.like-count',
        'comments' => 'span.comment-count',
    ],
]);
```

### 2. ตรวจจับและสร้างคอนเทนต์

```php
// Scrape data
$scraperService->scrapeAllReady();

// Analyze keywords
$analyzerService->analyzeUnprocessed();

// Detect viral trends
$trends = $detectionService->detectViralTrends();

// Generate blog post for top trend
$topTrend = $trends[0];
$content = $contentGenerator->generateContent($topTrend, 'blog');
```

## เทคโนโลยีที่ใช้

- **Backend**: Laravel, PHP
- **Database**: MySQL/MariaDB
- **Frontend**: Blade Templates, Tailwind CSS, Alpine.js
- **Charts**: Chart.js
- **Web Scraping**: Symfony DomCrawler, Guzzle HTTP
- **AI**: OpenAI API, Claude API (via existing AiServiceFactory)
- **Queue**: Laravel Queue (Redis/Database)

## License

ระบบนี้เป็นส่วนหนึ่งของโปรเจกต์ Thaiprompt-Affiliate
