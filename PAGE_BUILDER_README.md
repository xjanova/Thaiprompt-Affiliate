# 🎨 Page Builder System - ระบบสร้างหน้าเว็บแบบ Drag & Drop

ระบบ Page Builder ที่มีความสามารถชั้นสูง สามารถใช้สร้างและจัดการหน้าเว็บต่างๆ เช่น Homepage, Wiki, About Page แบบ Real-time Preview

---

## ✨ คุณสมบัติหลัก

### 🎯 ความสามารถหลัก
- ✅ **Drag & Drop Interface** - จัดเรียง sections ได้ง่ายด้วยการลากและวาง
- ✅ **Realtime Preview** - ดูผลลัพธ์ทันทีพร้อม responsive preview (Desktop, Tablet, Mobile)
- ✅ **Template System** - มี templates สำเร็จรูปให้เลือกใช้มากมาย
- ✅ **Reusable & Extensible** - สามารถนำไปใช้กับหน้าอื่นๆ ได้ เช่น Wiki, About, Landing Pages
- ✅ **Visual Editor** - แก้ไขเนื้อหาได้ง่าย ไม่ต้องเขียนโค้ด
- ✅ **Section Management** - จัดการ sections: สร้าง, แก้ไข, ลบ, ซ่อน/แสดง, duplicate

### 📦 Section Types ที่รองรับ
1. **Hero Section** - หน้าแรก พร้อม gradient background
2. **Features Grid** - แสดงฟีเจอร์แบบ grid (3-4 columns)
3. **Statistics Counter** - แสดงสถิติแบบ animated counter
4. **Call to Action (CTA)** - ปุ่มเรียกร้องการกระทำ
5. **Content Block** - บล็อกเนื้อหาทั่วไป
6. **Spacer** - เว้นระยะห่าง
7. และอื่นๆ อีกมากมาย (ขยายได้ง่าย)

---

## 🏗️ สถาปัตยกรรมระบบ

### Database Schema
```
page_builders
├── id
├── page_type (homepage, wiki, about, custom)
├── name
├── slug
├── is_active
├── meta_data (JSON)
├── settings (JSON)
└── timestamps

page_builder_sections
├── id
├── page_builder_id
├── section_type
├── name
├── order (สำหรับเรียงลำดับ)
├── settings (JSON)
├── content (JSON)
├── is_active
├── is_visible
└── timestamps

page_builder_templates
├── id
├── template_type
├── name
├── slug
├── description
├── preview_image
├── category
├── default_settings (JSON)
├── default_content (JSON)
└── timestamps
```

### Service Layer
```
PageBuilderService       - จัดการ pages
PageSectionService       - จัดการ sections
PageTemplateService      - จัดการ templates
```

### Controllers
```
Admin\PageBuilderController        - CRUD pages
Admin\PageBuilderSectionController - CRUD sections
```

---

## 🚀 การติดตั้งและใช้งาน

### 1. Run Migrations
```bash
php artisan migrate
```

### 2. Seed Templates
```bash
php artisan db:seed --class=PageBuilderSeeder
```

### 3. เข้าใช้งาน Admin Panel
```
URL: /admin/page-builder
```

---

## 📖 คู่มือการใช้งาน

### สร้างหน้าใหม่

1. เข้า **Admin Panel** → **Page Builder**
2. คลิก **"Create New Page"**
3. กรอกข้อมูล:
   - **Page Name**: ชื่อหน้า (เช่น "Homepage", "About Us")
   - **URL Slug**: URL ของหน้า (auto-generate จาก name)
   - **Page Type**: ประเภทหน้า (homepage, wiki, about, custom)
   - **SEO Settings**: Meta title, description (optional)
4. คลิก **"Create Page & Start Building"**

### แก้ไขหน้าด้วย Visual Editor

1. เลือกหน้าที่ต้องการแก้ไข → คลิก **"Edit"**
2. **Left Panel** (Sections Manager):
   - **Sections Tab**: จัดการ sections ที่มีในหน้า
   - **Templates Tab**: เพิ่ม section ใหม่จาก templates
   - **Settings Tab**: ตั้งค่าหน้า
3. **Right Panel** (Preview):
   - ดู preview realtime
   - สลับโหมด: Desktop, Tablet, Mobile

### เพิ่ม Section ใหม่

**วิธีที่ 1: จาก Templates**
1. ไปที่ Tab **"Templates"**
2. คลิกเลือก template ที่ต้องการ
3. Section จะถูกเพิ่มเข้าหน้าทันที

**วิธีที่ 2: ปุ่ม Add Section**
1. คลิก **"+ Add Section"**
2. เลือก section type
3. กรอกข้อมูล

### จัดเรียง Sections

**Drag & Drop:**
- คลิกค้างที่ Section card
- ลากไปวางตำแหน่งที่ต้องการ
- ระบบจะ save ลำดับอัตโนมัติ

### แก้ไข Section

1. คลิกปุ่ม **"Edit"** ใน section card
2. แก้ไขเนื้อหาและการตั้งค่า
3. คลิก **"Save"**

### Actions อื่นๆ
- **👁️ Visibility**: Toggle แสดง/ซ่อน section
- **📋 Duplicate**: คัดลอก section
- **🗑️ Delete**: ลบ section

---

## 🎨 การสร้าง Section Type ใหม่

### 1. เพิ่ม Section Type ใน Model
แก้ไข `app/Models/PageBuilderSection.php`:
```php
public const SECTION_TYPES = [
    // ... existing types
    'my_custom_section' => 'My Custom Section',
];
```

### 2. สร้าง Blade Component
สร้างไฟล์ `resources/views/page-builder/sections/my-custom-section.blade.php`:
```blade
@php
$settings = $section['settings'] ?? [];
$content = $section['content'] ?? [];
@endphp

<section class="py-16">
    <div class="container mx-auto">
        <!-- Your HTML here -->
        <h2>{{ $content['title'] ?? 'Title' }}</h2>
    </div>
</section>
```

### 3. สร้าง Template (Optional)
เพิ่มใน seeder หรือ admin panel

---

## 🔧 API Endpoints

### Pages
```
GET    /admin/page-builder              - List all pages
GET    /admin/page-builder/create       - Show create form
POST   /admin/page-builder              - Create new page
GET    /admin/page-builder/{page}/edit  - Show editor
PUT    /admin/page-builder/{page}       - Update page
DELETE /admin/page-builder/{page}       - Delete page
POST   /admin/page-builder/{page}/duplicate        - Duplicate page
POST   /admin/page-builder/{page}/toggle-active    - Toggle active status
POST   /admin/page-builder/{page}/reorder-sections - Reorder sections
GET    /admin/page-builder/{page}/preview          - Preview page
```

### Sections
```
POST   /admin/page-builder/{page}/sections                   - Create section
POST   /admin/page-builder/{page}/sections/from-template/{template} - Create from template
PUT    /admin/page-builder/sections/{section}                - Update section
DELETE /admin/page-builder/sections/{section}                - Delete section
POST   /admin/page-builder/sections/{section}/duplicate      - Duplicate section
POST   /admin/page-builder/sections/{section}/move-up        - Move up
POST   /admin/page-builder/sections/{section}/move-down      - Move down
POST   /admin/page-builder/sections/{section}/toggle-visibility - Toggle visibility
POST   /admin/page-builder/sections/{section}/toggle-active     - Toggle active
```

---

## 🎯 Use Cases (กรณีการใช้งาน)

### 1. สร้าง Homepage ใหม่
```
1. Create Page → Type: "homepage"
2. Add Sections:
   - Hero Section (gradient)
   - Statistics Counter
   - Features Grid (3 columns)
   - Call to Action
3. Customize content
4. Save & Activate
```

### 2. สร้าง Wiki Page
```
1. Create Page → Type: "wiki"
2. Add Sections:
   - Hero (minimal)
   - Content Blocks
   - Spacers for separation
3. Write content
4. Organize with drag & drop
```

### 3. Landing Page สำหรับ Campaign
```
1. Create Page → Type: "landing"
2. Add Sections:
   - Hero with CTA
   - Features
   - Testimonials
   - Pricing
   - Final CTA
3. Optimize for conversions
```

---

## 🌟 Advanced Features

### Caching System
ระบบใช้ Laravel Cache เพื่อเพิ่มประสิทธิภาพ:
```php
PageBuilderService::getRenderData($page);
// Cached for 24 hours
```

### Responsive Preview
```javascript
// Switch between devices
previewDevice: 'desktop' | 'tablet' | 'mobile'
```

### Auto-Save (Optional)
```javascript
// Auto-save every 30 seconds
initAutoSave() {
    setInterval(() => {
        this.savePage();
    }, 30000);
}
```

---

## 🔐 Security

- ✅ CSRF Protection
- ✅ Admin Authentication Required
- ✅ Input Validation
- ✅ XSS Protection (Laravel Blade escaping)

---

## 📁 ไฟล์ที่สร้างขึ้น

### Migrations
```
database/migrations/
├── 2025_01_09_000001_create_page_builders_table.php
├── 2025_01_09_000002_create_page_builder_sections_table.php
└── 2025_01_09_000003_create_page_builder_templates_table.php
```

### Models
```
app/Models/
├── PageBuilder.php
├── PageBuilderSection.php
└── PageBuilderTemplate.php
```

### Services
```
app/Services/
├── PageBuilderService.php
├── PageSectionService.php
└── PageTemplateService.php
```

### Controllers
```
app/Http/Controllers/Admin/
├── PageBuilderController.php
└── PageBuilderSectionController.php
```

### Views
```
resources/views/admin/page-builder/
├── index.blade.php      (List pages)
├── create.blade.php     (Create form)
├── edit.blade.php       (Visual editor)
└── preview.blade.php    (Preview page)

resources/views/page-builder/sections/
├── hero.blade.php
├── features.blade.php
├── statistics.blade.php
├── cta.blade.php
├── content-block.blade.php
└── spacer.blade.php
```

### Seeders
```
database/seeders/
└── PageBuilderSeeder.php
```

---

## 🚀 Future Enhancements (ต่อยอดได้)

- [ ] Section Editor Modal (WYSIWYG)
- [ ] Image Upload & Media Library
- [ ] Version History & Rollback
- [ ] A/B Testing
- [ ] Analytics Integration
- [ ] Import/Export Pages
- [ ] Multi-language Support
- [ ] More Section Types (Gallery, Video, Form, etc.)
- [ ] Block-based Editor (Gutenberg-style)

---

## 📞 Support

หากมีปัญหาหรือต้องการความช่วยเหลือ:
- ตรวจสอบ logs: `storage/logs/laravel.log`
- ตรวจสอบ migrations: `php artisan migrate:status`
- Clear cache: `php artisan cache:clear`

---

## 🎉 สรุป

ระบบ Page Builder นี้เป็น **Reusable Model** ที่สามารถนำไปประยุกต์ใช้กับส่วนต่างๆ ได้:
- ✅ Homepage Builder
- ✅ Wiki Page Builder
- ✅ Landing Page Builder
- ✅ About Page Builder
- ✅ Custom Page Builder

ด้วยสถาปัตยกรรมที่ดี มี Drag & Drop, Realtime Preview และ Template System ที่ทรงพลัง

**Happy Building! 🎨🚀**
