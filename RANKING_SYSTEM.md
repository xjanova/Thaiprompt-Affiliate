# 🏆 Ranking System - Complete Guide

## Overview

ระบบ Ranking แบบครบวงจรสำหรับ Thai Prompt Affiliate System ที่ออกแบบมาเพื่อเพิ่มแรงจูงใจให้กับ Affiliates พร้อมฟีเจอร์ที่หลากหลายและยืดหยุ่น

## ✨ Features

### 1. **ระบบ Rank หลายระดับ**
- 🥉 Bronze (สำริด)
- 🥈 Silver (เงิน)
- 🥇 Gold (ทอง)
- 💎 Platinum (แพลตินัม)
- 💠 Diamond (เพชร)

แต่ละระดับมี:
- ไอคอนและสีประจำระดับที่สวยงาม
- อัตรา Commission ที่เพิ่มขึ้นตามระดับ
- Bonus Multiplier พิเศษ

### 2. **เงื่อนไขการขึ้นระดับที่ยืดหยุ่น**

#### ประเภทเงื่อนไข:
- **Points** - คะแนนสะสม
- **Referrals** - จำนวนคนแนะนำ
- **Sales** - ยอดขายรวม
- **Active Referrals** - คนแนะนำที่ยัง Active
- **Team Sales** - ยอดขายของทีม
- **Consecutive Months** - จำนวนเดือนที่ทำได้ติดต่อกัน
- **Custom** - เงื่อนไขพิเศษ

#### ตัวอย่าง Requirements:
```php
// Gold Rank Requirements
- Earn 500 Points (คะแนน 500 แต้ม)
- 20 Referrals (แนะนำ 20 คน)
- ฿50,000 Sales (ยอดขาย 50,000 บาท)
- 10 Active Members (สมาชิกที่ Active 10 คน)
```

### 3. **3 วิธีการขึ้นระดับ**

#### A. Auto Promotion (ขึ้นอัตโนมัติ)
- ระบบตรวจสอบเงื่อนไขอัตโนมัติ
- เมื่อผ่านทุกเงื่อนไข → ขึ้นระดับทันที
- แจ้งเตือนผู้ใช้และ Admin

#### B. Manual Approval (รออนุมัติ)
- ผู้ใช้ต้องยื่นคำขอ
- Admin ตรวจสอบและอนุมัติ
- เก็บประวัติการอนุมัติ

#### C. Purchase (ซื้อเพื่อขึ้นระดับ)
- ผู้ใช้สามารถซื้อเพื่อข้ามระดับได้
- รองรับหลายช่องทางชำระเงิน
- สามารถตั้งค่าให้ต้องอนุมัติหรือไม่

### 4. **ระบบโบนัสหลากหลาย**

#### ประเภทโบนัส:
- **One-time Bonus** - โบนัสครั้งเดียวเมื่อขึ้นระดับ
- **Monthly Bonus** - โบนัสรายเดือน
- **Commission Boost** - เพิ่มอัตรา Commission
- **Multiplier** - ตัวคูณรายได้
- **Privilege** - สิทธิพิเศษ (VIP Support, etc.)
- **Reward** - รางวัลพิเศษ

#### ตัวอย่าง Bonuses:
```php
// Diamond Rank Bonuses
- ฿50,000 One-time Bonus
- ฿5,000 Monthly Bonus
- +15% Commission Rate
- VIP Support Access
```

### 5. **UI/UX Features**

#### User Dashboard:
- 📊 **Progress Gauge** - เกจวิ่งวิบวับแบบ Animated
- 🎯 **Requirements Tracker** - ติดตามความคืบหน้าแต่ละเงื่อนไข
- 🏅 **Rank Badge** - แสดงไอคอนระดับที่สวยงาม
- 📈 **Next Rank Preview** - ดูข้อมูลระดับถัดไป

#### Leaderboard:
- 🏆 **Top 100 Rankings** - แสดง Top Users
- 👥 **Team Rankings** - ดูระดับของทีม/สายงาน
- 🎖️ **Achievement Badges** - เหรียญรางวัลพิเศษ

### 6. **Admin Panel**

#### Rank Management:
- ✅ สร้าง/แก้ไข/ลบ Ranks
- 🎨 กำหนดสี, ไอคอน, ระดับ
- 💰 ตั้งค่า Commission Rate และ Bonuses
- 📋 จัดการ Requirements

#### Promotion Management:
- ✓ อนุมัติ/ปฏิเสธการขึ้นระดับ
- 📜 ดูประวัติการขึ้นระดับทั้งหมด
- 🔍 ตรวจสอบ Requirements ที่ผ่าน
- 📝 เพิ่มหมายเหตุในการอนุมัติ

## 📊 Database Schema

### Tables:
1. **ranks** - ข้อมูลระดับต่างๆ
2. **rank_requirements** - เงื่อนไขการขึ้นระดับ
3. **rank_promotions** - ประวัติการขึ้นระดับ
4. **rank_bonuses** - โบนัสของแต่ละระดับ
5. **user_rank_progress** - ติดตามความคืบหน้าของผู้ใช้
6. **rank_settings** - การตั้งค่าระบบ Ranking

## 🔧 Installation

### 1. Run Migrations
```bash
php artisan migrate
```

### 2. Seed Sample Ranks
```bash
php artisan db:seed --class=RankSeeder
```

### 3. Configure Settings
เข้าไปที่ Admin Panel → Settings → Ranking System และตั้งค่า:
- เปิด/ปิด Auto Promotion
- เปิด/ปิด Manual Approval
- เปิด/ปิด Rank Purchase
- ตั้งค่า Point System

## 🚀 Usage

### For Users

#### 1. ดู Rank ปัจจุบัน
```
/user/ranks/dashboard
```

#### 2. ดูความคืบหน้า
```
/user/ranks/progress
```

#### 3. ดู Leaderboard
```
/user/ranks/leaderboard
```

#### 4. ขอขึ้นระดับ
```
POST /user/ranks/request-promotion
{
    "rank_id": 3
}
```

### For Admins

#### 1. จัดการ Ranks
```
/admin/ranks
```

#### 2. อนุมัติการขึ้นระดับ
```
/admin/ranks/promotions
```

#### 3. ดู Analytics
```
/admin/ranks/analytics
```

## 📱 API Endpoints

### Get All Ranks
```
GET /api/v1/ranks
```

### Get User Progress
```
GET /api/v1/ranks/user/progress
```

### Get Leaderboard
```
GET /api/v1/ranks/leaderboard?limit=100
```

### Check Eligibility
```
GET /api/v1/ranks/user/eligibility
```

### Request Promotion
```
POST /api/v1/ranks/promotions/request
{
    "rank_id": 3
}
```

## ⚙️ Configuration

### Points System

ตั้งค่าคะแนนใน `rank_settings`:

```php
points_per_referral = 10      // คะแนนต่อการแนะนำ 1 คน
points_per_sale = 1.00         // คะแนนต่อยอดขาย 1 บาท
points_per_active_month = 5    // คะแนนต่อเดือนที่ active
```

### Auto Promotion Settings

```php
enable_auto_promotion = true
promotion_check_frequency = 'daily' // realtime, hourly, daily, weekly
notify_on_eligible = true
notify_on_promotion = true
```

### Manual Approval Settings

```php
enable_manual_approval = false
require_all_approvals = false
approval_admins = [1, 2, 3] // Admin IDs
```

### Purchase Settings

```php
enable_rank_purchase = false
allow_skip_requirements = false
purchase_discount_percentage = 0
purchase_requires_approval = true
```

## 🎨 Customization

### Custom Rank Icons

วางไฟล์ SVG ใน `public/images/ranks/`:
- bronze.svg
- silver.svg
- gold.svg
- platinum.svg
- diamond.svg

### Custom Colors

แก้ไขสีในตาราง `ranks`:
```sql
UPDATE ranks SET color = '#FF6B6B' WHERE name = 'Diamond';
```

### Custom Requirements

เพิ่ม Requirement ใหม่:
```php
RankRequirement::create([
    'rank_id' => 5,
    'requirement_type' => 'custom',
    'name' => 'Complete Training',
    'name_th' => 'ผ่านการอบรม',
    'target_value' => 1,
    'operator' => '>=',
]);
```

## 📈 Performance

- ใช้ **Eager Loading** สำหรับลด N+1 queries
- **Cache** Leaderboard ทุก 5 นาที
- **Index** ที่สำคัญ:
  - `ranks.level`
  - `user_rank_progress.progress_percentage`
  - `rank_promotions.status`

## 🔐 Security

- ✅ Middleware authentication
- ✅ Permission checking สำหรับ Admin
- ✅ Input validation
- ✅ SQL injection protection
- ✅ XSS protection

## 🐛 Troubleshooting

### ปัญหา: Points ไม่อัพเดท
**แก้ไข**: รัน manual calculation
```php
$rankingService->calculateRankPoints($user);
```

### ปัญหา: Auto Promotion ไม่ทำงาน
**แก้ไข**: ตรวจสอบ settings และ cron job
```bash
php artisan schedule:run
```

### ปัญหา: Progress ไม่แสดง
**แก้ไข**: รัน manual progress update
```php
$rankingService->updateUserProgress($user);
```

## 📝 Notes

- ระบบรองรับ Multi-language (EN/TH)
- ใช้ Laravel Eloquent ORM
- รองรับ REST API
- มี Mobile App support

## 🎯 Future Enhancements

- [ ] Rank Achievement Badges
- [ ] Social Sharing
- [ ] Rank Challenges/Quests
- [ ] Team Competitions
- [ ] Custom Rank Paths
- [ ] AI-powered Recommendations

## 📞 Support

หากมีปัญหาหรือข้อสงสัย:
- GitHub Issues: [repository-url]
- Email: support@thaiprompt.com
- Docs: [docs-url]

---

**Created by**: Thai Prompt Team
**Version**: 1.0.0
**Last Updated**: November 2024
