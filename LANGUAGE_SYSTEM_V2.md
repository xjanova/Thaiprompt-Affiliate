# ระบบภาษาแบบใหม่ (Language System V2)

## 📋 สรุปการอัปเกรด

ระบบภาษาใหม่ได้รับการยกเครื่องทั้งหมด เพื่อให้มีความยืดหยุ่น มืออาชีพ และใช้งานง่ายขึ้น พร้อมการจัดการแบบ admin-friendly

## ✨ ฟีเจอร์ใหม่

### 1. **จัดการภาษาแบบ Dynamic ผ่าน Admin Panel**
   - เลือกเปิด/ปิดภาษาแต่ละภาษาได้
   - ลากเพื่อเรียงลำดับการแสดงผล
   - รองรับภาษามากกว่า 13 ภาษา (Thai, English, Chinese, Japanese, Korean, Vietnamese, Spanish, French, German, Portuguese, Russian, Arabic, Hindi)

### 2. **ปรับแต่งรูปแบบการแสดงผล**
   - **Dropdown**: เมนูแบบดรอปดาวน์ (เหมาะสำหรับภาษาเยอะ)
   - **Flags**: แสดงธงเรียงแนวนอน (เหมาะสำหรับเว็บไซต์สมัยใหม่)
   - **Compact**: แสดงแบบกะทัดรัด (เหมาะสำหรับ mobile)

### 3. **ตัวเลือกการแสดงผลที่หลากหลาย**
   - เลือกแสดง/ซ่อนธงประเทศ
   - ปรับขนาดธง (16-64 pixels)
   - เลือกแสดง/ซ่อนชื่อภาษา
   - ดูตัวอย่างแบบ real-time ใน admin panel

### 4. **Google Translate Integration ที่ปรับปรุงแล้ว**
   - แก้ไขปัญหา API Key initialization
   - รองรับทั้ง API Key และ Service Account
   - มี error handling และ logging ที่ดีขึ้น
   - Cache system เพื่อลด API calls

### 5. **Security Improvements**
   - Rate limiting (60 requests/minute)
   - Input validation สำหรับ language codes
   - CSRF protection
   - Maximum text length limits (5000 characters)
   - Batch translation limits (50 items max)

## 📁 ไฟล์ที่สร้างใหม่

### Database & Models
1. **`database/migrations/2025_10_30_000001_create_language_settings_table.php`**
   - สร้างตาราง `language_settings` สำหรับเก็บข้อมูลภาษา
   - เพิ่มภาษาเริ่มต้น 13 ภาษา

2. **`database/migrations/2025_10_30_000002_add_language_switcher_settings.php`**
   - เพิ่ม settings สำหรับรูปแบบการแสดงผล

3. **`app/Models/LanguageSetting.php`**
   - Model สำหรับจัดการภาษา
   - มี cache system แบบ automatic
   - Helper methods สำหรับดึงข้อมูล

### Controllers
4. **`app/Http/Controllers/Admin/LanguageSettingController.php`**
   - Admin controller สำหรับจัดการภาษา
   - API endpoints สำหรับ AJAX operations

### Views & Components
5. **`resources/views/admin/settings/languages.blade.php`**
   - Admin panel สำหรับจัดการภาษา
   - UI แบบมืออาชีพด้วย Alpine.js
   - Real-time preview

6. **`resources/views/components/language-switcher-pro.blade.php`**
   - Language switcher component ใหม่
   - รองรับ 3 รูปแบบการแสดงผล
   - Dynamic configuration จาก database

### Documentation
7. **`LANGUAGE_SYSTEM_V2.md`** (ไฟล์นี้)
   - เอกสารอธิบายระบบใหม่

## 🔧 ไฟล์ที่แก้ไข

1. **`app/Services/TranslationService.php`**
   - แก้ไข API key initialization (รองรับ DB > ENV > Config)
   - ปรับปรุง error handling และ logging
   - รองรับ LanguageSetting model
   - เพิ่ม validation

2. **`app/Http/Controllers/TranslationController.php`**
   - เพิ่ม validation ที่เข้มงวดขึ้น
   - รองรับ enabled languages จาก database
   - เพิ่ม empty text check

3. **`routes/web.php`**
   - เพิ่ม rate limiting (60 req/min)

4. **`routes/admin.php`**
   - เพิ่ม routes สำหรับ language settings

## 🚀 วิธีการใช้งาน

### สำหรับ Admin

1. **เข้าสู่ Admin Panel**
   ```
   /admin/settings/languages
   ```

2. **เปิดใช้งานภาษา**
   - เลื่อน toggle switch ข้างภาษาที่ต้องการเปิด/ปิด
   - ภาษาที่เปิดใช้งานจะแสดงผลในเว็บไซต์

3. **เรียงลำดับภาษา**
   - คลิกและลากที่ icon ด้านซ้ายของแต่ละภาษา
   - ภาษาที่อยู่ด้านบนจะแสดงก่อน

4. **ปรับแต่งรูปแบบการแสดงผล**
   - ไปที่แท็บ "รูปแบบการแสดงผล"
   - เลือกรูปแบบ: Dropdown, Flags, หรือ Compact
   - ปรับแต่ง:
     - แสดง/ซ่อนธง
     - ขนาดธง (ลากแถบเลื่อน)
     - แสดง/ซ่อนชื่อภาษา
   - ดูตัวอย่าง real-time ด้านล่าง

5. **บันทึกการตั้งค่า**
   - คลิกปุ่ม "บันทึกการตั้งค่า" ด้านบน

### สำหรับ Developer

#### 1. ใช้ Language Switcher ในหน้าเว็บ

**แบบใหม่ (แนะนำ):**
```blade
<x-language-switcher-pro />
```

**แบบเก่า (ยังใช้ได้):**
```blade
<x-language-switcher-advanced />
```

#### 2. ใช้ Translatable Elements

```html
<!-- เพิ่ม attribute data-translate ในเนื้อหาที่ต้องการแปล -->
<h1 data-translate>ยินดีต้อนรับสู่เว็บไซต์</h1>
<p data-translate>เนื้อหาที่จะถูกแปลอัตโนมัติ</p>
```

#### 3. ใช้ API Translation

**Single Translation:**
```javascript
fetch('/api/translate', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
    },
    body: JSON.stringify({
        text: 'สวัสดี',
        target_lang: 'en',
        source_lang: 'th'
    })
})
```

**Batch Translation:**
```javascript
fetch('/api/translate/batch', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
    },
    body: JSON.stringify({
        texts: ['สวัสดี', 'ขอบคุณ', 'ลาก่อน'],
        target_lang: 'en',
        source_lang: 'th'
    })
})
```

## ⚙️ การตั้งค่า Google Translate

### Option 1: ใช้ API Key (แนะนำ)

1. สร้าง API Key จาก [Google Cloud Console](https://console.cloud.google.com/)
2. เพิ่มใน `.env`:
   ```env
   GOOGLE_TRANSLATE_ENABLED=true
   GOOGLE_TRANSLATE_API_KEY=AIzaSy...
   ```

### Option 2: ใช้ Service Account

1. ดาวน์โหลด Service Account JSON จาก Google Cloud Console
2. วางไฟล์ใน `storage/app/google-credentials.json`
3. เพิ่มใน `.env`:
   ```env
   GOOGLE_TRANSLATE_ENABLED=true
   GOOGLE_TRANSLATE_CREDENTIALS=storage/app/google-credentials.json
   GOOGLE_TRANSLATE_PROJECT_ID=your-project-id
   ```

### Cache Settings

```env
TRANSLATE_CACHE_ENABLED=true
TRANSLATE_CACHE_TTL=86400  # 24 hours
TRANSLATE_SOURCE_LANGUAGE=th
```

## 🗂️ Database Schema

### Table: `language_settings`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| code | varchar(10) | Language code (th, en, ja) |
| name | varchar(100) | English name |
| native_name | varchar(100) | Native language name |
| flag_emoji | varchar(10) | Flag emoji |
| flag_image_url | varchar(255) | Custom flag image URL (optional) |
| is_enabled | boolean | Active status |
| sort_order | integer | Display order |
| is_default | boolean | Default language flag |
| created_at | timestamp | |
| updated_at | timestamp | |

### Settings Keys Added

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| language_switcher_style | string | dropdown | dropdown, flags, compact |
| language_switcher_show_flags | boolean | true | Show flags |
| language_switcher_flag_size | integer | 24 | Flag size in pixels |
| language_switcher_show_name | boolean | true | Show language names |
| language_switcher_position | string | top-right | Position on page |

## 🔐 Security Features

1. **Rate Limiting**: 60 requests per minute
2. **Input Validation**:
   - Language codes must be in enabled list
   - Text length max 5000 characters
   - Batch max 50 items
3. **CSRF Protection**: All POST requests protected
4. **Empty Text Check**: Prevents unnecessary API calls
5. **Error Handling**: Graceful fallback on failures

## 📊 Performance Optimizations

1. **Database Cache**: Language settings cached for 1 hour
2. **Translation Cache**: Translations cached for 24 hours
3. **Batch Operations**: Reduce API calls
4. **Lazy Loading**: Components load on demand

## 🐛 การแก้ปัญหา

### ปัญหา: Google Translate ไม่ทำงาน

1. ตรวจสอบ API Key ใน Admin Panel (`/admin/settings?tab=api`)
2. ตรวจสอบ logs: `storage/logs/laravel.log`
3. ทดสอบ API:
   ```bash
   curl http://your-domain/api/translate/status
   ```

### ปัญหา: ภาษาไม่แสดง

1. ตรวจสอบว่าเปิดใช้งานภาษาแล้ว
2. Clear cache:
   ```bash
   php artisan cache:clear
   ```

### ปัญหา: การแปลช้า

1. ตรวจสอบ cache settings
2. ลด batch size
3. เปิดใช้ cache:
   ```env
   TRANSLATE_CACHE_ENABLED=true
   ```

## 📝 API Endpoints

### Public APIs

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/translate/status` | Check translation status |
| GET | `/api/translate/languages` | Get available languages |
| POST | `/api/translate` | Translate single text |
| POST | `/api/translate/batch` | Batch translate |
| POST | `/api/translate/detect` | Detect language |
| GET | `/lang/{locale}` | Switch language (session) |

### Admin APIs

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/settings/languages` | Language settings page |
| POST | `/admin/settings/languages` | Update all settings |
| POST | `/admin/settings/languages/{code}/toggle` | Toggle language |
| PUT | `/admin/settings/languages/{code}` | Update single language |
| POST | `/admin/settings/languages/reorder` | Reorder languages |
| GET | `/admin/settings/languages/switcher` | Get switcher settings |
| PUT | `/admin/settings/languages/switcher` | Update switcher settings |

## 🎨 Supported Languages

| Code | Language | Native Name | Flag |
|------|----------|-------------|------|
| th | Thai | ไทย | 🇹🇭 |
| en | English | English | 🇬🇧 |
| zh | Chinese | 中文 | 🇨🇳 |
| ja | Japanese | 日本語 | 🇯🇵 |
| ko | Korean | 한국어 | 🇰🇷 |
| vi | Vietnamese | Tiếng Việt | 🇻🇳 |
| es | Spanish | Español | 🇪🇸 |
| fr | French | Français | 🇫🇷 |
| de | German | Deutsch | 🇩🇪 |
| pt | Portuguese | Português | 🇵🇹 |
| ru | Russian | Русский | 🇷🇺 |
| ar | Arabic | العربية | 🇸🇦 |
| hi | Hindi | हिन्दी | 🇮🇳 |

## 🚦 Migration Guide

### From Old System to V2

1. **Run Migrations:**
   ```bash
   php artisan migrate
   ```

2. **Update Components:**
   ```blade
   <!-- Old -->
   <x-language-switcher-advanced />

   <!-- New (Recommended) -->
   <x-language-switcher-pro />
   ```

3. **Configure Languages:**
   - Go to `/admin/settings/languages`
   - Enable desired languages
   - Configure display style

4. **Test Translation:**
   - Visit `/api/translate/status`
   - Check enabled languages
   - Test language switching

## 💡 Best Practices

1. **Enable only languages you need** - Reduces API costs
2. **Use cache** - Improves performance significantly
3. **Add data-translate attributes** - For automatic translation
4. **Monitor API quota** - Check Google Cloud Console
5. **Use batch translation** - More efficient than single requests

## 📞 Support

หากพบปัญหาหรือต้องการความช่วยเหลือ:
- ตรวจสอบ logs: `storage/logs/laravel.log`
- ดู error messages ใน browser console
- ตรวจสอบ network requests ใน DevTools

---

**Version**: 2.0.0
**Last Updated**: 2025-10-30
**Status**: Production Ready ✅
