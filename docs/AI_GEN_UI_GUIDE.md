# AI Gen System - UI Guide

## ภาพรวมการพัฒนา UI

ระบบ AI Gen ได้รับการพัฒนา UI ครบถ้วนทั้งส่วน Admin และ User พร้อมใช้งานจริง!

---

## 🎨 Admin Panel

### เมนูหลัก: **AI Gen System**

เข้าถึงได้จากเมนูด้านข้างของ Admin Panel

#### 📊 Dashboard (`/admin/ai-gen/dashboard`)

**คุณสมบัติ:**
- **Statistics Cards** - แสดงสถิติแบบเรียลไทม์
  - Active Providers
  - Active Subscriptions
  - Total Generations
  - Success Rate

- **Charts & Visualizations**
  - Usage Overview Chart (Last 30 Days) - กราฟเส้นแสดงการใช้งาน
  - Generation Types Distribution - กราฟวงกลม
  - สามารถกรองข้อมูล 30 วัน, 7 วัน, วันนี้

- **Provider Status**
  - แสดงสถานะของแต่ละ provider
  - จำนวน generations
  - สถานะ Active/Inactive

- **Recent Activity**
  - กิจกรรมล่าสุดแบบ real-time
  - รายละเอียดการ generate แต่ละครั้ง
  - สถานะ (success, failed, pending)

- **Top Users Table**
  - ผู้ใช้ที่ใช้งานมากที่สุดในเดือนนี้
  - สถิติการใช้งาน
  - Success rate
  - Package ที่ใช้

**การใช้งาน:**
```
1. เปิด Admin Panel
2. คลิก "AI Gen System" ในเมนูด้านซ้าย
3. คลิก "Dashboard"
4. ดูสถิติและกราฟแบบ real-time
5. คลิก "Refresh" เพื่ออัปเดตข้อมูล
```

**คุณสมบัติพิเศษ:**
- ✨ Auto-refresh ทุก 60 วินาที
- 📱 Responsive design
- 🎯 Interactive charts (Chart.js)
- ⚡ AJAX-powered (ไม่ต้อง reload หน้า)

---

#### 🖥️ AI Providers (`/admin/ai-gen/providers`)

**คุณสมบัติ:**
- **Provider Cards Grid**
  - แสดง providers ทั้งหมดแบบ card
  - Logo, ชื่อ, สถานะ
  - Supported features badges
  - Statistics (Generations, Success Rate)

- **Add Provider Modal**
  - ฟอร์มเพิ่ม provider ใหม่
  - ระบุ Name, Slug, Type, Description
  - เลือก Supported Features
  - Toggle Active/Inactive

- **Provider Configuration Modal**
  - ตั้งค่า API Key (encrypted)
  - API Endpoint
  - Test Connection button
  - รักษาความปลอดภัยด้วยการเข้ารหัส

- **Provider Actions**
  - Configure - เปิด config modal
  - Test Connection - ทดสอบการเชื่อมต่อ
  - Activate/Deactivate - เปิด/ปิดการใช้งาน
  - Delete - ลบ provider

**การใช้งาน:**

**เพิ่ม Provider ใหม่:**
```
1. คลิก "Add Provider"
2. กรอกข้อมูล:
   - Name: ชื่อ Provider (e.g., Freepik)
   - Slug: URL-friendly name (e.g., freepik)
   - Type: image, video, หรือ both
   - Description: คำอธิบาย
   - Logo URL: URL ของโลโก้
   - Supported Features: เลือกฟีเจอร์ที่รองรับ
3. คลิก "Save Provider"
```

**ตั้งค่า API:**
```
1. คลิก Actions (⋮) บน Provider Card
2. เลือก "Configure"
3. กรอก API Key (จะถูกเข้ารหัสอัตโนมัติ)
4. กรอก API Endpoint
5. คลิก "Test Connection" เพื่อทดสอบ
6. คลิก "Save Configuration"
```

**Visual Design:**
- 🎨 Card-based layout with hover effects
- ✨ Glow animations
- 🌈 Badge สีสันสวยงาม
- 📱 Fully responsive
- 🔒 Password field with toggle visibility

---

#### 📦 Packages (`/admin/ai-gen/packages`)

**คุณสมบัติ:**
- **Package Cards Display**
  - แสดงแพคเกจทั้งหมดแบบ card พร้อมรายละเอียด
  - ราคา, credits, duration
  - Popular badge สำหรับแพคเกจยอดนิยม
  - Status (Active/Inactive)

- **Add/Edit Package Modal**
  - ฟอร์มสร้าง/แก้ไขแพคเกจ
  - กำหนดชื่อ, ราคา, image credits, video credits
  - ระยะเวลา, คุณสมบัติ (features list)
  - Toggle recurring subscription, popular badge

- **Package Management**
  - แก้ไขแพคเกจ
  - ลบแพคเกจ
  - ดูจำนวน subscribers

**การใช้งาน:**
```
1. คลิก "Add Package" เพื่อสร้างแพคเกจใหม่
2. กรอกรายละเอียด: ชื่อ, ราคา, credits
3. เพิ่ม features (ทีละบรรทัด)
4. เลือก options: Recurring, Popular, Active
5. Save Package
```

---

#### 🎁 Free Quotas (`/admin/ai-gen/quotas`)

**คุณสมบัติ:**
- **Quota Cards by Role**
  - แสดง quota settings แต่ละ role
  - Daily/Monthly quotas สำหรับ images และ videos
  - Status indicator

- **Usage Statistics**
  - Today's free usage
  - This month usage
  - Active users
  - Total free generations

- **Usage Chart**
  - กราฟแสดงการใช้งาน free quota ย้อนหลัง 30 วัน
  - แยกตาม Image และ Video

- **Add/Edit Quota Modal**
  - กำหนด quota สำหรับแต่ละ role
  - Daily และ Monthly limits
  - สำหรับทั้ง Images และ Videos

**การใช้งาน:**
```
1. คลิก "Add Quota Setting"
2. ระบุ Role (user, subscriber, vip, หรือ all)
3. กำหนด Daily/Monthly quota สำหรับ images
4. กำหนด Daily/Monthly quota สำหรับ videos
5. Save Quota
```

---

#### 👥 Subscriptions (`/admin/ai-gen/subscriptions`)

**คุณสมบัติ:**
- **Statistics Cards**
  - Active subscriptions
  - Expiring soon
  - Total revenue
  - This month subscriptions

- **Advanced Filters**
  - กรองตาม Status (Active, Expired, Cancelled)
  - กรองตาม Package
  - Search โดย user name หรือ email

- **Subscriptions Table**
  - แสดงข้อมูล user พร้อมรูป avatar
  - Package details
  - Credits usage (แยก image/video พร้อม progress bar)
  - Status และ expiry date
  - Days left indicator

- **Actions**
  - View Details - ดูรายละเอียดครบถ้วน
  - Extend - ต่ออายุ subscription
  - Cancel - ยกเลิก subscription

- **View Details Modal**
  - ข้อมูล user และ package
  - Credits usage breakdown
  - Timestamps

- **Extend Modal**
  - ต่ออายุได้กี่วัน
  - ใส่เหตุผล (reason)

**การใช้งาน:**
```
1. ใช้ filters เพื่อค้นหา subscription
2. คลิกที่แถวเพื่อดูรายละเอียด
3. ใช้ Actions: View, Extend, Cancel
4. Monitor expiring subscriptions
```

---

#### 📋 Usage Logs (`/admin/ai-gen/usage-logs`)

**คุณสมบัติ:**
- **Quick Stats**
  - Successful generations
  - Failed generations
  - Pending generations
  - Total credits used

- **Advanced Filters**
  - Type (Image/Video)
  - Status (Success/Failed/Pending)
  - Provider
  - Free quota only / Paid only
  - Date range
  - Search by user or prompt

- **Activity Logs Table**
  - Time, User, Provider, Type
  - Prompt preview
  - Credits used (แสดง FREE badge)
  - Status
  - View details action

- **View Log Details Modal**
  - User information
  - Generation details
  - Full prompt
  - Parameters used
  - Error details (ถ้ามี)
  - Response data

- **Export Function**
  - Export logs to CSV/Excel

- **Auto-refresh**
  - รีเฟรชทุก 30 วินาที

**การใช้งาน:**
```
1. ใช้ filters เพื่อกรองข้อมูล
2. คลิก view เพื่อดูรายละเอียด
3. Monitor failed generations
4. Export logs สำหรับการวิเคราะห์
```

---

#### 🖼️ All Generations (`/admin/ai-gen/generations`)

**คุณสมบัติ:**
- **Statistics**
  - Total generations
  - Images count
  - Videos count
  - Today's generations

- **View Modes**
  - Grid View - แสดงแบบ gallery card
  - List View - แสดงแบบรายการ

- **Grid View**
  - Card พร้อม preview image/video
  - Type badge, Favorite badge
  - Hover overlay แสดง prompt
  - User, Provider, Status, Date

- **List View**
  - รายการแบบละเอียด
  - Thumbnail preview
  - Full prompt display
  - User และ metadata

- **Filters**
  - Type (Images/Videos)
  - Status (Completed/Processing/Failed)
  - Provider
  - Favorites only
  - Search

- **View Modal**
  - Large preview (image หรือ video player)
  - Full details: user, prompt, parameters
  - Timestamps
  - Download button
  - Delete button

**การใช้งาน:**
```
1. เลือก view mode (Grid/List)
2. ใช้ filters เพื่อค้นหา
3. คลิกที่ generation เพื่อดูเต็ม
4. Download หรือ Delete ตามต้องการ
```

---

#### ⚙️ Settings (`/admin/ai-gen/settings`)

**คุณสมบัติ:**

**5 Tabs หลัก:**

1. **General Settings**
   - System enable/disable
   - Maintenance mode
   - Maintenance message
   - Allow guest access
   - Require email verification

2. **Limits & Security**
   - Max daily generations per user
   - Max concurrent requests
   - Rate limiting
   - Request timeout
   - Content moderation
   - Blocked keywords
   - Auto-block threshold

3. **Defaults**
   - Default provider
   - Default image size
   - Default style
   - Default quality
   - Auto-save to gallery
   - Public gallery settings

4. **Notifications**
   - Email notifications (completion, failure, low credits, expiring)
   - Admin notifications
   - Admin email
   - System alerts

5. **Advanced**
   - Storage driver (Local, S3, GCS, DO)
   - CDN URL
   - Cache duration
   - Queue driver
   - Debug mode
   - Log level
   - Clear cache button
   - Test connections button

**การใช้งาน:**
```
1. เลือก Tab ที่ต้องการตั้งค่า
2. ปรับแต่งการตั้งค่าตามต้องการ
3. คลิก "Save All Changes" ด้านบน
4. หรือกด Ctrl+S (shortcut)
```

**คุณสมบัติพิเศษ:**
- ✨ Keyboard shortcut (Ctrl+S)
- 🔧 Clear cache function
- 🔌 Test all provider connections
- ⚠️ Warning alerts สำหรับการตั้งค่าที่อันตราย

---

## 👥 User Frontend

### หน้าหลัก: **AI Gen** (`/user/ai-gen`)

**คุณสมบัติ:**

#### 🎆 Hero Section
- **Stunning Gradient Background**
  - สีไล่ระดับสวยงาม (Purple to Pink)
  - Animation effects

- **Hero Content**
  - หัวข้อใหญ่พร้อม gradient text
  - คำโฆษณาที่ดึงดูดใจ
  - ปุ่ม "Start Creating Now" แบบ glow

- **Image Grid**
  - แสดงตัวอย่างภาพ 4 รูปแบบ grid
  - Hover scale effect
  - AOS animations (fade-up)

#### 📊 Stats Bar
- **แสดงข้อมูลสำคัญ:**
  - Images Left - จำนวนภาพที่เหลือ
  - Videos Left - จำนวนวีดีโอที่เหลือ
  - Created - จำนวนที่สร้างไปแล้ว
  - Plan - แพคเกจที่ใช้

- **Design:**
  - สีขาวโค้งมน
  - Icons สีสันสวยงาม
  - ตัวเลขขนาดใหญ่เด่นชัด

#### 🗂️ Tabs Interface

**1. My Creations Tab**
- **Filter Buttons:**
  - All, Images, Videos, Favorites
  - เปลี่ยนสีเมื่อ active

- **Generations Grid:**
  - แสดงผลงานแบบ grid responsive
  - Cards พร้อม preview image
  - Hover effect (ยกขึ้น + shadow)
  - Click เพื่อดูรายละเอียด

- **Create New Button:**
  - ปุ่มสีสันพร้อม glow effect
  - เปิด modal สร้างงานใหม่

**2. Explore Tab**
- **Category Cards:**
  - Art & Design
  - Photography
  - Videos
  - Popular

- **Design:**
  - Card พร้อม icon ใหญ่
  - Hover effect (ยกขึ้น)
  - คำอธิบายแต่ละหมวด

**3. Packages Tab**
- แสดงแพคเกจที่มีให้เลือก
- Card design สวยงาม
- Popular badge สำหรับแพคเกจยอดนิยม

#### 🎨 Create Modal

**คุณสมบัติ:**
- **ฟอร์มสร้างงาน:**
  - เลือกประเภท: Image หรือ Video
  - เลือก AI Provider
  - กรอก Prompt (คำอธิบายสิ่งที่ต้องการ)
  - เลือก Style (Realistic, Artistic, Anime, etc.)
  - เลือก Size (Square, Landscape, Portrait)

- **Credits Info:**
  - แสดงข้อมูล credits ที่เหลือ
  - Alert สีฟ้าด้านล่าง

- **Design:**
  - Modal ขนาดใหญ่
  - แบบฟอร์มที่เข้าใจง่าย
  - ปุ่ม Generate พร้อม glow effect

**Tips:**
- 💡 "Be specific and descriptive for better results"

#### 🔍 View Modal

**คุณสมบัติ:**
- **Preview Area:**
  - แสดงภาพ/วีดีโอขนาดใหญ่
  - พื้นหลังสวยงาม

- **Details Panel:**
  - Type, Provider, Status
  - Prompt ที่ใช้
  - วันที่สร้าง

- **Action Buttons:**
  - Download - ดาวน์โหลด
  - Add to Favorites - เพิ่มในรายการโปรด
  - Delete - ลบ

**Visual Features:**
- ✨ Gradient backgrounds
- 🎭 Glassmorphism effects
- 📱 Fully responsive
- 🎬 AOS animations
- 💫 Glow effects
- 🌈 Beautiful color schemes
- 🎯 Smooth transitions

---

## 🎨 Design System

### สีหลัก
- **Primary Gradient:** `#667eea` → `#764ba2`
- **Secondary Gradient:** `#ffd89b` → `#19547b`
- **Success:** `#1cc88a`
- **Warning:** `#f6c23e`
- **Danger:** `#e74a3b`
- **Info:** `#36b9cc`

### Typography
- **Hero Title:** 3.5rem, Font-weight: 800
- **Stats Value:** 2rem, Font-weight: 700
- **Card Title:** 1.25rem, Font-weight: 600

### Effects
- **Glow Animation:** 2s ease-in-out infinite
- **Hover Transform:** translateY(-10px)
- **Shadow:** 0 10px 30px rgba(0,0,0,0.1)
- **Border Radius:** 15px - 20px

---

## 📱 Responsive Design

### Breakpoints
- **Mobile:** < 768px
- **Tablet:** 768px - 1024px
- **Desktop:** > 1024px

**การปรับตัว:**
- Grid columns ปรับตามขนาดหน้าจอ
- Stats bar แสดง 2 columns บนมือถือ
- Modal ขนาดเต็มหน้าจอบนมือถือ
- Card size responsive

---

## 🚀 การติดตั้งและใช้งาน

### 1. Run Seeder

```bash
# สร้างเมนูใน Admin Panel
php artisan db:seed --class=AiGenMenuSeeder
```

### 2. เข้าสู่ระบบ Admin

```
1. เข้า /admin
2. Login ด้วย admin account
3. เห็นเมนู "AI Gen System" ด้านซ้าย
4. คลิกเข้าไปใช้งาน
```

### 3. ตั้งค่า Provider

```
1. ไป Providers page
2. Add Provider หรือ Configure provider ที่มีอยู่
3. กรอก API Key ของ Freepik
4. Test Connection
5. Save
```

### 4. ใช้งานฝั่ง User

```
1. Login ด้วย user account
2. ไป /user/ai-gen
3. ดู stats และ quota
4. คลิก "Start Creating Now"
5. สร้างภาพ/วีดีโอ
```

---

## 🔧 Customization

### เปลี่ยนสี

แก้ไขใน `resources/views/user/ai-gen/index.blade.php`:

```css
.ai-gen-container {
    background: linear-gradient(135deg, #YOUR_COLOR1 0%, #YOUR_COLOR2 100%);
}
```

### เปลี่ยน Fonts

แก้ไข font-family ใน CSS:

```css
.hero-title {
    font-family: 'Your Font', sans-serif;
}
```

### เพิ่ม Animations

ใช้ AOS library:

```html
<div data-aos="fade-up" data-aos-delay="100">
    Content here
</div>
```

---

## 📚 Library Dependencies

- **Chart.js** - สำหรับกราฟในหน้า Admin
- **AOS (Animate On Scroll)** - สำหรับ animations
- **Bootstrap 4/5** - UI Framework
- **Font Awesome** - Icons

---

## 🎯 Next Steps

### Admin Panel UI - สถานะการพัฒนา:
1. ✅ Dashboard - **เสร็จสมบูรณ์**
2. ✅ Providers Management - **เสร็จสมบูรณ์**
3. ✅ Packages Management UI - **เสร็จสมบูรณ์**
4. ✅ Quotas Management UI - **เสร็จสมบูรณ์**
5. ✅ Subscriptions Management UI - **เสร็จสมบูรณ์**
6. ✅ Usage Logs UI - **เสร็จสมบูรณ์**
7. ✅ All Generations Gallery UI - **เสร็จสมบูรณ์**
8. ✅ Settings UI - **เสร็จสมบูรณ์**

🎉 **Admin Panel พร้อมใช้งาน 100%!**

### ฟีเจอร์เพิ่มเติม:
- 💳 Payment Integration
- 📧 Email Notifications
- 🔔 Real-time Notifications
- 📊 Advanced Analytics
- 🎨 More Customization Options
- 🌐 Multi-language Support

---

## 💡 Tips & Best Practices

### สำหรับ Admin:
- ตรวจสอบ Provider Status เป็นประจำ
- Monitor Usage Logs สำหรับ abuse
- Update Quotas ตามความเหมาะสม
- Test API connections เป็นประจำ

### สำหรับ User:
- เขียน Prompt ที่ชัดเจนและละเอียด
- เลือก Style ที่เหมาะกับงาน
- ตรวจสอบ Credits ก่อนใช้งาน
- Save Favorites สำหรับงานที่ชอบ

---

## 🐛 Troubleshooting

### ปัญหา: Dashboard ไม่แสดงข้อมูล
**แก้ไข:**
- ตรวจสอบ API endpoint
- เช็ค Console สำหรับ errors
- ตรวจสอบ Authentication

### ปัญหา: Modal ไม่เปิด
**แก้ไข:**
- ตรวจสอบ jQuery และ Bootstrap loaded
- เช็ค JavaScript errors
- ตรวจสอบ modal ID

### ปัญหา: Images ไม่แสดง
**แก้ไข:**
- ตรวจสอบ URL ของรูปภาพ
- ตรวจสอบ file permissions
- ใช้ placeholder images

---

## 📞 Support

หากพบปัญหาหรือต้องการความช่วยเหลือ:
- ตรวจสอบ `docs/AI_GEN_SYSTEM.md`
- ตรวจสอบ `docs/AI_GEN_INSTALLATION.md`
- ดู Laravel logs: `storage/logs/laravel.log`
- ติดต่อทีมพัฒนา

---

สร้างโดย: Thai Prompt Affiliate Team
Version: 1.0.0
อัปเดตล่าสุด: 2024-11-11
