# 🚀 AI Rental with Cloud GPU System

> **ระบบให้เช่า AI Models บน Cloud GPU แบบครบวงจร**
>
> **Version**: 1.0.0 | **Status**: Production Ready (85%) | **Framework**: Laravel 11 + V3 Standards

---

## 📑 **สารบัญ**

1. [Overview](#-overview)
2. [Features](#-features)
3. [Tech Stack](#%EF%B8%8F-tech-stack)
4. [Installation](#-installation)
5. [Database Schema](#-database-schema)
6. [Architecture](#%EF%B8%8F-architecture)
7. [User Guide](#-user-guide)
8. [Developer Guide](#-developer-guide)
9. [API Reference](#-api-reference)
10. [Deployment](#-deployment)
11. [Troubleshooting](#-troubleshooting)
12. [FAQ](#-faq)

---

## 🌟 **Overview**

**AI Rental with Cloud GPU** เป็นระบบที่ช่วยให้ผู้ใช้สามารถ:
- 🔍 ค้นหา AI Models ยอดนิยมจาก Hugging Face
- ☁️ เลือก Cloud GPU Provider ที่เหมาะสม (ฟรีและเสียเงิน)
- 💰 คำนวณค่าใช้จ่ายก่อน deploy
- 🔑 จัดการ API Keys อย่างปลอดภัย
- 📊 Deploy และ track การใช้งาน models

### 🎯 **จุดเด่น**

- ✅ รองรับ 8+ Cloud Providers (Google Colab, RunPod, Vast.ai, etc.)
- ✅ Database มี 13+ Trending Models พร้อมข้อมูลครบถ้วน
- ✅ ข่าวสาร & Tutorials จาก Hugging Face
- ✅ Cost Calculator แม่นยำ
- ✅ Setup Guides ครบทุก Provider
- ✅ API Keys Encryption (Laravel Crypt)
- ✅ Dark Mode Support
- ✅ Mobile Responsive

---

## ✨ **Features**

### 🎨 **Phase 1: Foundation** ✅ 100%
- [x] Database Schema (5 tables)
- [x] Models & Relationships (5 models)
- [x] Admin Menu Integration
- [x] Coming Soon Pages
- [x] Seeder: Cloud Providers (8 providers)

### 📚 **Phase 2: Content & Tools** ✅ 100%
- [x] Setup Guide System
  - Interactive provider selector
  - Google Colab detailed guide
  - Generic template for others
- [x] Cost Calculator
  - Live calculation (Alpine.js)
  - Comparison table
  - Quick presets
- [x] Trending Models
  - 13 models with full details
  - Search & filters
  - Hardware requirements
  - Deployment links
- [x] News Feed System
  - 5 detailed articles
  - Magazine-style layout
  - Markdown rendering
  - Share buttons

### 🔧 **Phase 3: Management** ✅ 100%
- [x] Cloud Providers CRUD
  - Full admin management
  - Activate/Deactivate
  - Ratings
  - Drag & drop ordering
- [x] My Configurations
  - User API Keys management
  - Encrypted credentials
  - GPU settings
  - Set default config

### 🚀 **Phase 4: Advanced** 🔄 10%
- [ ] Deployments Tracking
- [ ] Real HuggingFace API Integration
- [ ] Auto-scaling
- [ ] Cost Analytics
- [ ] Performance Monitoring

---

## 🛠️ **Tech Stack**

### **Backend**
- **Framework**: Laravel 11.x
- **PHP**: 8.1+
- **Database**: MySQL 8.0+ / MariaDB 10.3+
- **Authentication**: Laravel Sanctum
- **Encryption**: Laravel Crypt (AES-256-CBC)

### **Frontend (V3 Standards)**
- **CSS**: Tailwind CSS 3.4
- **JavaScript**: Alpine.js 3.13.5
- **Build**: Vite 5.0
- **UI**: Glassmorphism, 3D effects
- **Icons**: Font Awesome 6

### **Cloud Providers Supported**
#### Free Tier:
- ✅ Google Colab (12 hrs/session)
- ✅ Kaggle (30 hrs/week)
- ✅ Lightning AI (22 hrs/month)

#### Paid:
- ✅ RunPod ($0.20/hr+)
- ✅ Vast.ai ($0.10/hr+)
- ✅ Lambda Labs ($0.50/hr+)
- ✅ Paperspace
- ✅ Banana.dev

---

## 📦 **Installation**

### **Prerequisites**
```bash
- PHP 8.1+
- Composer 2.x
- Node.js 18+ & NPM
- MySQL 8.0+
- Git
```

### **Step 1: Clone Repository**
```bash
git clone https://github.com/xjanova/Thaiprompt-Affiliate.git
cd Thaiprompt-Affiliate
```

### **Step 2: Install Dependencies**
```bash
# PHP dependencies
composer install

# Node dependencies
npm install
```

### **Step 3: Environment Setup**
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure database in .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=thaiprompt_affiliate
DB_USERNAME=root
DB_PASSWORD=your_password
```

### **Step 4: Database Migration & Seeding**
```bash
# Run migrations
php artisan migrate

# Seed AI Rental data
php artisan db:seed --class=AiRentalCloudProvidersSeeder
php artisan db:seed --class=HuggingFaceTrendingModelsSeeder
php artisan db:seed --class=HuggingFaceModelNewsSeeder

# หรือ seed ทั้งหมด
php artisan migrate:fresh --seed
```

### **Step 5: Build Assets**
```bash
# Development
npm run dev

# Production
npm run build
```

### **Step 6: Start Server**
```bash
php artisan serve

# เข้าใช้งานที่: http://localhost:8000/admin/ai-rental
```

---

## 🗄️ **Database Schema**

### **ตาราง 5 ตาราง**

#### 1. **ai_rental_cloud_providers**
เก็บข้อมูล Cloud GPU Providers
```sql
- id (Primary Key)
- name (varchar) - ชื่อ provider
- slug (varchar, unique) - URL slug
- description (text) - คำอธิบาย
- tier (enum: free, paid, both) - ประเภท
- website_url (varchar) - Website
- signup_url, documentation_url, pricing_url
- gpu_types (json) - ประเภท GPU ที่รองรับ
- min_price_per_hour, max_price_per_hour (decimal)
- supports_* (boolean) - Features (huggingface, docker, jupyter, etc.)
- free_tier_* - ข้อมูล Free tier
- average_rating (decimal) - คะแนนเฉลี่ย
- popularity_score (integer)
- is_active, is_recommended (boolean)
- display_order (integer)
- timestamps, soft_deletes
```

#### 2. **ai_rental_cloud_configs**
เก็บ Configurations ของผู้ใช้ (API Keys)
```sql
- id (Primary Key)
- user_id (FK -> users)
- cloud_provider_id (FK -> ai_rental_cloud_providers)
- config_name (varchar) - ชื่อ config
- api_key (text, encrypted) - API Key (encrypted)
- api_secret (text, encrypted) - API Secret (encrypted)
- gpu_config (json) - การตั้งค่า GPU
- status (enum: ready, error, testing)
- is_active, is_default (boolean)
- last_used_at (timestamp)
- timestamps, soft_deletes

UNIQUE KEY: (user_id, config_name, cloud_provider_id)
```

#### 3. **ai_rental_deployments**
เก็บข้อมูลการ deploy models
```sql
- id (Primary Key)
- user_id (FK -> users)
- cloud_config_id (FK -> ai_rental_cloud_configs)
- deployment_id (varchar, unique) - Auto-generated
- model_name, model_source (varchar)
- huggingface_model_id (varchar)
- status (enum: pending, running, stopped, error, completed)
- api_endpoint (varchar)
- gpu_type, instance_type (varchar)
- started_at, stopped_at (timestamp)
- total_hours (decimal)
- cost_per_hour, total_cost (decimal)
- total_requests, successful_requests, failed_requests (integer)
- performance_metrics (json)
- error_logs (text)
- timestamps, soft_deletes
```

#### 4. **huggingface_trending_models**
เก็บข้อมูล Models ยอดนิยม
```sql
- id (Primary Key)
- model_id (varchar, unique) - Hugging Face Model ID
- model_name (varchar)
- description (text)
- task, library (varchar)
- tags, pipeline_tag (json)
- author, organization (varchar)
- downloads, downloads_last_month, likes (bigint)
- trending_score (integer) - คะแนน trending (0-100)
- model_size, model_size_bytes
- supported_languages (json)
- license (varchar)
- min_gpu_memory, recommended_gpu (varchar)
- hardware_requirements, benchmark_scores (json)
- avg_inference_time_ms (decimal)
- performance_tier (enum)
- model_url, paper_url, demo_url, documentation_url (varchar)
- supports_inference_api, supports_spaces, can_run_locally (boolean)
- compatible_cloud_providers (json)
- estimated_cost_per_hour (decimal)
- rank_overall, rank_in_category (integer)
- category (varchar)
- is_featured, is_recommended, is_production_ready (boolean)
- difficulty_level (enum: beginner, intermediate, advanced, expert)
- first_seen_at, last_updated_at, featured_at (timestamp)
- timestamps
```

#### 5. **huggingface_model_news**
เก็บข่าวสารและ tutorials
```sql
- id (Primary Key)
- title, slug (varchar)
- summary, content (text)
- model_id, model_name (varchar)
- news_type (enum: new_model, model_update, trending, tutorial, etc.)
- importance (enum: low, normal, high, critical)
- tags, categories (json)
- is_published, is_featured, is_pinned (boolean)
- featured_image_url (varchar)
- author_name (varchar)
- model_url, paper_url, demo_url, documentation_url (varchar)
- published_at (timestamp)
- view_count, like_count, share_count, bookmark_count (integer)
- metadata (json)
- timestamps, soft_deletes

FULLTEXT INDEX: (title, content, summary)
```

### **Relationships**

```
User (ผู้ใช้)
├── hasMany: AiRentalCloudConfig
├── hasMany: AiRentalDeployment

AiRentalCloudProvider (Cloud Provider)
├── hasMany: AiRentalCloudConfig
└── hasManyThrough: AiRentalDeployment

AiRentalCloudConfig (การตั้งค่า)
├── belongsTo: User
├── belongsTo: AiRentalCloudProvider
└── hasMany: AiRentalDeployment

AiRentalDeployment (การ Deploy)
├── belongsTo: User
└── belongsTo: AiRentalCloudConfig

HuggingFaceTrendingModel (Model ยอดนิยม)
└── [Independent Table]

HuggingFaceModelNews (ข่าวสาร)
└── [Independent Table]
```

---

## 🏗️ **Architecture**

### **Directory Structure**
```
app/
├── Http/Controllers/Admin/
│   ├── AiRentalController.php          # Main dashboard & tools
│   ├── AiRentalCloudProviderController.php  # Providers CRUD
│   └── AiRentalConfigController.php    # User configs CRUD
│
├── Models/
│   ├── AiRentalCloudProvider.php
│   ├── AiRentalCloudConfig.php
│   ├── AiRentalDeployment.php
│   ├── HuggingFaceTrendingModel.php
│   └── HuggingFaceModelNews.php
│
└── Policies/
    └── AiRentalCloudConfigPolicy.php

database/
├── migrations/
│   ├── 2025_11_24_000001_create_ai_rental_cloud_providers_table.php
│   ├── 2025_11_24_000002_create_ai_rental_cloud_configs_table.php
│   ├── 2025_11_24_000003_create_ai_rental_deployments_table.php
│   ├── 2025_11_24_000004_create_huggingface_model_news_table.php
│   └── 2025_11_24_000005_create_huggingface_trending_models_table.php
│
└── seeders/
    ├── AiRentalCloudProvidersSeeder.php
    ├── HuggingFaceTrendingModelsSeeder.php
    └── HuggingFaceModelNewsSeeder.php

resources/views/admin/ai-rental/
├── dashboard.blade.php
├── coming-soon.blade.php
├── setup-guide.blade.php
├── cost-calculator.blade.php
├── setup-guides/
│   ├── google-colab.blade.php
│   └── generic.blade.php
├── cloud-providers/
│   ├── index.blade.php
│   ├── create.blade.php
│   ├── edit.blade.php
│   └── _form.blade.php
├── configs/
│   ├── index.blade.php
│   ├── create.blade.php
│   ├── edit.blade.php
│   └── _form.blade.php
├── trending-models/
│   └── index.blade.php
└── news/
    ├── index.blade.php
    └── show.blade.php

routes/
└── admin.php (AI Rental section)
```

---

## 📖 **User Guide**

### **สำหรับ Admin**

#### **1. เข้าสู่ระบบ**
```
URL: /admin/ai-rental
Menu: Sidebar > AI Rental GPU
```

#### **2. Dashboard**
- แสดงสถิติภาพรวม (Providers, Deployments, GPU Hours, Hot Models)
- Progress bars แสดงความสมบูรณ์แต่ละส่วน
- Quick actions
- Recommended providers

#### **3. จัดการ Cloud Providers** (Admin Only)
```
Path: /admin/ai-rental/cloud-providers
```

**Features:**
- ✅ ดูรายการ Providers ทั้งหมด
- ✅ เพิ่ม Provider ใหม่
- ✅ แก้ไข Provider
- ✅ เปิด/ปิดใช้งาน
- ✅ ลบ Provider
- ✅ กรอง & ค้นหา

**ข้อมูลที่ต้องกรอก:**
- ชื่อ, Slug, คำอธิบาย
- Tier (free/paid/both)
- URLs (website, signup, docs, pricing)
- ราคา (min/max per hour)
- Features (HuggingFace, Docker, Jupyter, SSH, API, Spot Instances)
- Free Tier (ถ้ามี)
- Status (Active/Inactive, Recommended)

### **สำหรับ User**

#### **4. My Configurations**
```
Path: /admin/ai-rental/configs
```

**วิธีใช้:**

**4.1 เพิ่ม Configuration**
1. คลิก "เพิ่ม Configuration"
2. กรอกข้อมูล:
   - **Config Name**: ชื่อที่ต้องการ (เช่น "My RunPod Config")
   - **Cloud Provider**: เลือก provider
   - **API Key**: กรอก API Key จาก provider (จะถูก encrypt)
   - **API Secret**: กรอก secret (ถ้ามี)
   - **GPU Config**:
     - Region: เช่น us-east-1
     - GPU Type: เช่น RTX 4090
     - Max Instances: จำนวนสูงสุด
     - Auto Shutdown: เปิด/ปิด
   - **Settings**:
     - Active: เปิดใช้งาน
     - Set as Default: ตั้งเป็นค่าเริ่มต้น
3. บันทึก

**4.2 แก้ไข Configuration**
- คลิกปุ่ม "แก้ไข"
- เปลี่ยนแปลงข้อมูลที่ต้องการ
- API Key เดิมจะไม่แสดง (เว้นว่างถ้าไม่เปลี่ยน)

**4.3 Set Default**
- คลิกปุ่ม "Set Default" บน config ที่ต้องการ
- Config อื่นๆ จะถูกยกเลิก default อัตโนมัติ

**4.4 ลบ Configuration**
- คลิกปุ่มลบ (ถังขยะ)
- ยืนยันการลบ
- ⚠️ ไม่สามารถลบถ้ามี deployments ที่กำลังรันอยู่

#### **5. Trending Models**
```
Path: /admin/ai-rental/trending-models
```

**การใช้งาน:**
- ดู Models ยอดนิยม 13 models
- กรองตาม:
  - Category (Image Generation, Language Model, etc.)
  - Difficulty (Beginner, Intermediate, Advanced)
  - Cost (ราคาต่ำ-สูง)
  - Features (Featured, Recommended, Production Ready, Free Tier)
- ค้นหาจากชื่อ model หรือคำอธิบาย
- คลิก "View on HF" เพื่อไปหน้า Hugging Face
- คลิก "Calculate Cost" เพื่อคำนวณค่าใช้จ่าย

#### **6. Cost Calculator**
```
Path: /admin/ai-rental/cost-calculator
```

**วิธีใช้:**
1. เลือก Provider ที่ต้องการ
2. ปรับจำนวนชั่วโมง (slider 1-720 hrs)
3. ปรับจำนวน GPU (slider 1-8 GPUs)
4. หรือใช้ Quick Presets:
   - 1 Day (24 hrs)
   - 1 Week (168 hrs)
   - 1 Month (720 hrs)
5. ดูผลลัพธ์:
   - Hourly Rate
   - Total Hours
   - GPU Count
   - **Total Cost**
6. ดู Comparison Table (เปรียบเทียบทุก providers)

#### **7. Setup Guide**
```
Path: /admin/ai-rental/setup-guide
```

**เนื้อหา:**
- เลือก Provider ที่ต้องการ
- ดูคู่มือทีละขั้นตอน:
  - Google Colab: 7 ขั้นตอนละเอียด
  - Providers อื่นๆ: Generic guide
- Features checklist
- GPU types รองรับ
- Free tier limitations
- Links ไปยัง provider websites

#### **8. News Feed**
```
Path: /admin/ai-rental/news
```

**เนื้อหา:**
- ข่าวสาร 5 บทความ:
  1. FLUX.1 trending
  2. Llama 3.1 405B release
  3. Whisper V3 best practices
  4. Mixtral deployment guide
  5. SD 3.5 update
- กรองตาม:
  - Category
  - News Type (New Model, Update, Tutorial, etc.)
- ค้นหาข่าว
- คลิกอ่านบทความเต็ม
- Share ผ่าน Twitter, Facebook, LinkedIn

---

## 👨‍💻 **Developer Guide**

### **Adding New Cloud Provider**

**1. ผ่าน Admin UI** (แนะนำ)
```
1. เข้า /admin/ai-rental/cloud-providers
2. คลิก "เพิ่ม Provider ใหม่"
3. กรอกข้อมูลครบถ้วน
4. บันทึก
```

**2. ผ่าน Seeder**
```php
// database/seeders/AiRentalCloudProvidersSeeder.php

AiRentalCloudProvider::create([
    'name' => 'New Provider',
    'slug' => 'new-provider',
    'description' => 'Description...',
    'tier' => 'paid',
    'website_url' => 'https://example.com',
    'min_price_per_hour' => 0.50,
    'gpu_types' => ['RTX 4090', 'A100'],
    'supports_huggingface' => true,
    'supports_docker' => true,
    'is_active' => true,
    'display_order' => 100,
]);
```

### **Adding New Trending Model**

```php
// database/seeders/HuggingFaceTrendingModelsSeeder.php

HuggingFaceTrendingModel::create([
    'model_id' => 'organization/model-name',
    'model_name' => 'Model Name',
    'description' => 'Description...',
    'task' => 'text-generation',
    'library' => 'transformers',
    'tags' => ['tag1', 'tag2'],
    'author' => 'Author Name',
    'organization' => 'organization',
    'downloads' => 1000000,
    'downloads_last_month' => 100000,
    'likes' => 5000,
    'trending_score' => 85,
    'model_size' => '7.5 GB',
    'model_size_bytes' => 8053063680,
    'min_gpu_memory' => '16 GB',
    'recommended_gpu' => 'RTX 4090, A100',
    'hardware_requirements' => [
        'min_vram' => '16 GB',
        'recommended_vram' => '24 GB',
        'min_ram' => '32 GB',
        'storage' => '10 GB',
    ],
    'estimated_cost_per_hour' => 0.80,
    'category' => 'Language Model',
    'difficulty_level' => 'intermediate',
    'is_production_ready' => true,
]);
```

### **Creating New News Article**

```php
// database/seeders/HuggingFaceModelNewsSeeder.php

HuggingFaceModelNews::create([
    'title' => 'Article Title',
    'summary' => 'Short summary...',
    'content' => "# Markdown Content\n\nFull article in markdown...",
    'model_id' => 'organization/model-name',
    'model_name' => 'Model Name',
    'news_type' => 'tutorial',
    'importance' => 'high',
    'tags' => ['tag1', 'tag2'],
    'categories' => ['Category'],
    'is_published' => true,
    'is_featured' => false,
    'author_name' => 'Author',
    'published_at' => now(),
    'metadata' => [
        'reading_time' => '10 min',
        'difficulty' => 'beginner',
    ],
]);
```

### **Custom Scopes**

**Models:**

```php
// Providers
AiRentalCloudProvider::active()->get();
AiRentalCloudProvider::freeTier()->get();
AiRentalCloudProvider::paidTier()->get();
AiRentalCloudProvider::recommended()->get();
AiRentalCloudProvider::popular()->get();
AiRentalCloudProvider::ordered()->get();

// Configs
AiRentalCloudConfig::active()->get();

// Deployments
AiRentalDeployment::running()->get();
AiRentalDeployment::stopped()->get();
AiRentalDeployment::forUser($userId)->get();

// Trending Models
HuggingFaceTrendingModel::trending()->get();
HuggingFaceTrendingModel::popular()->get();
HuggingFaceTrendingModel::featured()->get();
HuggingFaceTrendingModel::recommended()->get();
HuggingFaceTrendingModel::productionReady()->get();
HuggingFaceTrendingModel::search($query)->get();

// News
HuggingFaceModelNews::published()->get();
HuggingFaceModelNews::featured()->get();
HuggingFaceModelNews::pinned()->get();
HuggingFaceModelNews::ofType($type)->get();
HuggingFaceModelNews::recent()->get();
HuggingFaceModelNews::search($query)->get();
```

### **Encryption/Decryption**

API Keys จะถูก encrypt/decrypt อัตโนมัติ:

```php
// Set API Key (auto-encrypt)
$config->api_key = 'my-secret-key';
$config->save();

// Get API Key (auto-decrypt)
$apiKey = $config->api_key; // Returns decrypted value
```

---

## 🌐 **API Reference**

### **Public Endpoints**

#### **GET** `/admin/ai-rental/api/stats`
ดึงสถิติภาพรวม

**Response:**
```json
{
  "success": true,
  "data": {
    "providers": {
      "total": 8,
      "active": 8,
      "free": 3
    },
    "deployments": {
      "total": 0,
      "running": 0,
      "stopped": 0
    },
    "usage": {
      "total_hours": 0,
      "total_cost": 0,
      "total_requests": 0
    },
    "trending": {
      "models_count": 13,
      "hot_models": 10,
      "news_count": 5
    }
  }
}
```

#### **POST** `/admin/ai-rental/api/calculate-cost`
คำนวณค่าใช้จ่าย

**Request:**
```json
{
  "provider_id": 1,
  "hours": 24,
  "gpu_count": 1
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "provider": "RunPod",
    "hourly_rate": 0.20,
    "hours": 24,
    "gpu_count": 1,
    "total_cost": 4.80,
    "currency": "USD"
  }
}
```

---

## 🚀 **Deployment**

### **Production Checklist**

- [ ] Set `APP_ENV=production` in .env
- [ ] Set `APP_DEBUG=false`
- [ ] Generate new `APP_KEY`
- [ ] Configure database
- [ ] Run migrations
- [ ] Run seeders
- [ ] Build assets: `npm run build`
- [ ] Clear cache: `php artisan optimize:clear`
- [ ] Optimize: `php artisan optimize`
- [ ] Set proper file permissions
- [ ] Configure web server (Nginx/Apache)
- [ ] Enable HTTPS
- [ ] Setup queue workers (if using)
- [ ] Setup cron jobs (if needed)

### **Performance Optimization**

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer install --optimize-autoloader --no-dev
```

---

## 🐛 **Troubleshooting**

### **Common Issues**

#### **1. API Keys ไม่สามารถ decrypt ได้**
```
Error: "The payload is invalid."
```

**Solution:**
- ตรวจสอบว่า `APP_KEY` ใน .env ไม่เปลี่ยนแปลง
- ถ้า APP_KEY เปลี่ยน ต้องตั้งค่า API Keys ใหม่ทั้งหมด

#### **2. Migrations failed - Table already exists**
```
SQLSTATE[42S01]: Base table or view already exists
```

**Solution:**
```bash
# ลบตารางเดิมก่อน
php artisan migrate:fresh

# หรือ rollback
php artisan migrate:rollback
php artisan migrate
```

#### **3. Seeder not found**
```
Class "AiRentalCloudProvidersSeeder" not found
```

**Solution:**
```bash
# Regenerate autoload
composer dump-autoload

# ตรวจสอบว่า seeder อยู่ใน DatabaseSeeder.php
```

#### **4. View not found**
```
View [admin.ai-rental.dashboard] not found
```

**Solution:**
```bash
# Clear view cache
php artisan view:clear

# ตรวจสอบว่าไฟล์มีอยู่จริง
ls resources/views/admin/ai-rental/dashboard.blade.php
```

---

## ❓ **FAQ**

### **Q: ระบบนี้ฟรีหรือเปล่า?**
A: ระบบเป็น open-source แต่การใช้งาน Cloud GPU ต้องจ่ายตามราคาของแต่ละ provider (มีทั้งฟรีและเสียเงิน)

### **Q: รองรับ Cloud Provider ไหนบ้าง?**
A: ตอนนี้รองรับ 8 providers:
- Free: Google Colab, Kaggle, Lightning AI
- Paid: RunPod, Vast.ai, Lambda Labs, Paperspace, Banana.dev

### **Q: API Keys ปลอดภัยหรือไม่?**
A: ใช่! ทุก API Key จะถูก encrypt ด้วย Laravel Crypt (AES-256-CBC) ก่อนบันทึกลงฐานข้อมูล

### **Q: สามารถเพิ่ม Provider ใหม่ได้หรือไม่?**
A: ได้! Admin สามารถเพิ่ม provider ใหม่ผ่าน UI ได้เลย ที่ `/admin/ai-rental/cloud-providers`

### **Q: จะ deploy model จริงได้ยังไง?**
A: Phase 4 (Advanced) กำลังพัฒนา จะมีระบบ deploy model จริงผ่าน API

### **Q: ข้อมูล Trending Models มาจากไหน?**
A: ข้อมูลมาจาก Seeder ที่เตรียมไว้ (13 models) Phase 4 จะเพิ่ม real-time sync กับ Hugging Face API

### **Q: รองรับภาษาไทยหรือไม่?**
A: ใช่! ทุก UI เป็นภาษาไทย และ comments/docs ก็เป็นภาษาไทยทั้งหมด

### **Q: Mobile responsive หรือไม่?**
A: ใช่! ออกแบบแบบ mobile-first และรองรับทุกขนาดหน้าจอ

### **Q: มี Dark Mode หรือไม่?**
A: ใช่! รองรับ Dark/Light mode ด้วย Tailwind CSS

---

## 📝 **Changelog**

### **Version 1.0.0** (2025-11-24)

#### **Phase 1: Foundation**
- ✅ Database schema (5 tables)
- ✅ Models & relationships (5 models)
- ✅ Admin menu integration
- ✅ Seeder: Cloud Providers (8 providers)

#### **Phase 2: Content & Tools**
- ✅ Setup Guide with interactive selector
- ✅ Cost Calculator with live calculation
- ✅ Trending Models (13 models)
- ✅ News Feed (5 articles)

#### **Phase 3: Management**
- ✅ Cloud Providers CRUD (Admin)
- ✅ My Configurations CRUD (User)
- ✅ API Keys encryption
- ✅ Policy-based authorization

---

## 📄 **License**

This project is part of **Thaiprompt-Affiliate** system.

**Commercial License** - Contact: [support@thaiprompt.com](mailto:support@thaiprompt.com)

---

## 👥 **Credits**

**Development Team:**
- Lead Developer: Claude (Anthropic)
- Project Manager: xjanova
- Framework: Laravel 11
- UI Framework: Tailwind CSS + Alpine.js

**Special Thanks:**
- Hugging Face for AI Models data
- All Cloud GPU Providers

---

## 🔗 **Links**

- **Project**: [Thaiprompt-Affiliate](https://github.com/xjanova/Thaiprompt-Affiliate)
- **Documentation**: This file
- **Support**: GitHub Issues

---

**Last Updated**: 2025-11-24
**Version**: 1.0.0
**Status**: Production Ready (85%)

---

Made with ❤️ by **Thaiprompt Team** 🇹🇭
