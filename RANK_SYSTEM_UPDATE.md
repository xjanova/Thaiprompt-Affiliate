# 🏆 Rank System Enhancement - Complete Documentation

## 📋 Overview

การปรับปรุงระบบ Rank ให้มีความสมบูรณ์และยืดหยุ่นมากขึ้น พร้อมฟีเจอร์ที่ทั่วโลกนิยม

## ✨ New Features

### 1. **Rank Stars System**
- เพิ่มฟิลด์ `stars` ในตาราง ranks สำหรับกำหนดจำนวนดาว (1-10 ดาว)
- แสดงดาวในผังสายงาน (Genealogy Tree)
- กำหนดได้โดยแอดมิน

### 2. **Enhanced Rank Management**
- แก้ไขชื่อ Rank ได้
- เปลี่ยนสี, ไอคอน, Badge Icon ได้
- ลบ Rank ได้ (หากไม่มีผู้ใช้งาน)
- กำหนดจำนวนดาวตามระดับ Rank

### 3. **Visual Improvements in Tree View**
- กรอบอวาตาร์มีสีตาม Rank
- แสดงดาวด้านล่างอวาตาร์ในผังสายงาน
- แสดง Badge และชื่อ Rank ข้างชื่อผู้ใช้

## 🗄️ Database Changes

### Migration: `2025_11_02_120000_add_stars_to_ranks_table.php`

เพิ่มฟิลด์ `stars` (integer) ในตาราง `ranks`:

```php
$table->integer('stars')->default(1)->after('badge_icon');
```

## 📊 Standard Rank Seed Data

ข้อมูล Rank มาตรฐานที่ทั่วโลกนิยม:

| Rank | Thai | Level | Stars | Color | Badge | Commission Rate |
|------|------|-------|-------|-------|-------|-----------------|
| Bronze | สำริด | 1 | ⭐ | #CD7F32 | 🥉 | 5% |
| Silver | เงิน | 2 | ⭐⭐ | #C0C0C0 | 🥈 | 7.5% |
| Gold | ทอง | 3 | ⭐⭐⭐ | #FFD700 | 🥇 | 10% |
| Platinum | แพลตินัม | 4 | ⭐⭐⭐⭐ | #E5E4E2 | 💎 | 15% |
| Diamond | เพชร | 5 | ⭐⭐⭐⭐⭐ | #B9F2FF | 💠 | 20% |

## 🔧 Modified Files

### 1. Database

#### Migrations
- ✅ `database/migrations/2025_11_02_120000_add_stars_to_ranks_table.php` (NEW)

#### Seeders
- ✅ `database/seeders/RankSeeder.php` - เพิ่ม stars ในข้อมูล seed

### 2. Models

- ✅ `app/Models/Rank.php`
  - เพิ่ม `stars` ใน `$fillable`
  - เพิ่ม `stars` ใน `$casts` (integer)

### 3. Controllers

- ✅ `app/Http/Controllers/Admin/RankController.php`
  - อัพเดท validation rules ให้รองรับ:
    - `icon` (nullable|string|max:255)
    - `badge_icon` (nullable|string|max:10)
    - `stars` (required|integer|min:1|max:10)

### 4. Views

#### Admin Rank Management
- ✅ `resources/views/admin/ranks/edit.blade.php`
  - เพิ่มฟิลด์ Icon URL
  - เพิ่มฟิลด์ Badge Icon (Emoji)
  - เพิ่มฟิลด์จำนวนดาว (1-10)

- ✅ `resources/views/admin/ranks/create.blade.php`
  - เพิ่มฟิลด์ Icon URL
  - เพิ่มฟิลด์ Badge Icon (Emoji)
  - เพิ่มฟิลด์จำนวนดาว (1-10)

#### Tree View
- ✅ `resources/views/admin/affiliates/partials/tree-node.blade.php`
  - กรอบอวาตาร์มีสีตาม Rank (`border: 3px solid`)
  - แสดงดาวด้านล่างอวาตาร์
  - แสดง Badge Icon และชื่อ Rank ข้างชื่อผู้ใช้

## 📖 Usage

### For Admins

#### 1. จัดการ Ranks
```
/admin/ranks
```

**ฟีเจอร์:**
- ✅ สร้าง Rank ใหม่
- ✅ แก้ไขชื่อ, สี, ไอคอน, ดาว
- ✅ กำหนด Commission Rate และ Bonus Multiplier
- ✅ ลบ Rank (หากไม่มีผู้ใช้งาน)

#### 2. ฟิลด์ที่แก้ไขได้

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| name | string | ✅ | ชื่อ Rank (EN) |
| name_th | string | ❌ | ชื่อ Rank (TH) |
| level | integer | ✅ | ระดับของ Rank |
| icon | string | ❌ | URL ไอคอน (เช่น /images/ranks/bronze.svg) |
| color | color | ✅ | สีประจำ Rank (hex code) |
| badge_icon | string | ❌ | อิโมจิ Badge (เช่น 🥉, 🥈, 🥇) |
| stars | integer | ✅ | จำนวนดาว (1-10) |

### For Users

#### ดูผังสายงาน
```
/admin/affiliates/tree
```

**จะเห็น:**
- อวาตาร์มีกรอบสีตาม Rank
- ดาวแสดงด้านล่างอวาตาร์
- Badge และชื่อ Rank ข้างชื่อผู้ใช้

## 🎨 Visual Examples

### Avatar with Rank Stars

```
┌─────────────────┐
│   ┌─────────┐   │
│   │    A    │   │  <- Avatar with colored border
│   └─────────┘   │
│   [⭐⭐⭐⭐⭐]   │  <- Stars below avatar
└─────────────────┘
```

### Rank Badge Display

```
John Doe [💎 Diamond]
john@example.com
```

## 🔄 Migration Process

### 1. Run Migration
```bash
php artisan migrate
```

### 2. Seed Standard Ranks (Optional)
```bash
php artisan db:seed --class=RankSeeder
```

**หมายเหตุ:** หาก Ranks มีอยู่แล้ว ข้ามขั้นตอนนี้

### 3. Update Existing Ranks
```bash
# Update stars for existing ranks via Admin Panel
# หรือใช้ SQL:
UPDATE ranks SET stars = 1 WHERE level = 1;
UPDATE ranks SET stars = 2 WHERE level = 2;
UPDATE ranks SET stars = 3 WHERE level = 3;
UPDATE ranks SET stars = 4 WHERE level = 4;
UPDATE ranks SET stars = 5 WHERE level = 5;
```

## ✅ Testing Checklist

- [x] Migration runs successfully
- [x] Rank model updated with stars field
- [x] Admin can create new ranks with stars
- [x] Admin can edit existing ranks
- [x] Admin can delete ranks without users
- [x] Tree view shows stars on avatars
- [x] Avatar borders show rank colors
- [x] Rank badges display correctly

## 🎯 Future Enhancements

### Planned Features
- [ ] Upload custom rank icons
- [ ] Animated star effects
- [ ] Rank progression timeline
- [ ] Achievement badges
- [ ] Rank comparison tool

### Suggestions
- Dynamic rank colors based on performance
- Rank milestone notifications
- Leaderboard with rank display
- Public rank badges for sharing

## 📞 Support

หากมีปัญหาหรือข้อสงสัย:
- GitHub Issues: [repository-url]
- Documentation: See RANKING_SYSTEM.md

---

**Created by**: Thai Prompt Team
**Version**: 1.1.0
**Date**: November 2, 2025
**Feature**: Enhanced Rank System with Stars
