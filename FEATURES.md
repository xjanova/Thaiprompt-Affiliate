# Features Documentation - ThaiPrompt Affiliate Marketplace

## Overview

This document describes all features in the Thaiprompt Affiliate Marketplace system, including both Phase 2 and Version 1.1.0 features.

---

## Phase 2 Features

## 🎨 1. ระบบ Theme Customization (Premium Feature)

### คุณสมบัติหลัก
ระบบปรับแต่งธีมที่ให้ร้านค้าสามารถสร้างเอกลักษณ์เฉพาะตัวได้

#### 1.1 การปรับแต่งสี
- **Primary Color** - สีหลักของธีม
- **Secondary Color** - สีรอง
- **Accent Color** - สีเน้น
- **Gradient Colors** - ไล่เฉดสีแบบ Professional (6 แบบสำเร็จรูป)

#### 1.2 Color Presets
ชุดสีสำเร็จรูปที่คัดสรรมาแล้ว:
- **Default** - สีน้ำเงิน-ชมพู (Indigo-Pink)
- **Ocean** - สีน้ำทะเล (Blue-Cyan-Teal)
- **Sunset** - สีพระอาทิตย์ตก (Orange-Red-Pink)
- **Forest** - สีป่าเขียว (Green-Lime)
- **Royal** - สีม่วงเข้ม (Purple)

#### 1.3 การปรับแต่งโลโก้
- Upload โลโก้ของร้าน
- Upload Favicon
- Upload Banner

#### 1.4 Custom CSS
- เขียน CSS เพิ่มเติมสำหรับการปรับแต่งขั้นสูง
- รองรับ CSS Variables
- Preview แบบ Real-time

#### 1.5 Premium Subscription
- **Monthly** - 299 บาท/เดือน
- **Yearly** - 2,990 บาท/ปี (ประหยัด 17%)
- **Lifetime** - 9,990 บาท (จ่ายครั้งเดียว)

### API Endpoints
```
GET    /admin/theme                 - แสดงหน้าปรับแต่งธีม
POST   /admin/theme                 - บันทึกการตั้งค่าธีม
POST   /admin/theme/logo            - Upload โลโก้
POST   /admin/theme/favicon         - Upload favicon
POST   /admin/theme/subscribe       - สมัครสมาชิก Premium
POST   /admin/theme/preview         - Preview ธีม
```

---

## 🔧 2. ระบบ Setup Wizard

### คุณสมบัติหลัก
ระบบติดตั้งผ่านหน้าเว็บที่ใช้งานง่าย ไม่ต้องใช้ CLI

#### 2.1 ขั้นตอนการติดตั้ง

**Step 1: ตรวจสอบความพร้อม**
- ✅ PHP Version (>= 8.1)
- ✅ PHP Extensions (PDO, mbstring, OpenSSL, etc.)
- ✅ Directory Permissions (storage/, bootstrap/cache/, public/)

**Step 2: ตั้งค่าฐานข้อมูล**
- กรอกข้อมูล Database Connection
- ทดสอบการเชื่อมต่อ
- บันทึก Configuration ลง .env

**Step 3: ติดตั้งฐานข้อมูล**
- Generate Application Key
- Run Migrations
- Run Seeders
- Create Storage Link

**Step 4: สร้าง Admin Account**
- กรอกข้อมูล Super Admin
- สร้าง Admin Role
- Assign Permissions

**Step 5: เสร็จสิ้น**
- สร้าง Backup อัตโนมัติ
- บันทึก System Info
- Redirect ไปยัง Dashboard

#### 2.2 UI Features
- Beautiful Wizard Interface
- Progress Indicator
- Smooth Animations (GSAP)
- Responsive Design
- Error Handling & Validation

### API Endpoints
```
GET    /setup                       - หน้าแรก Setup Wizard
GET    /setup/requirements          - ตรวจสอบความพร้อม
POST   /setup/database/test         - ทดสอบ Database Connection
POST   /setup/database/save         - บันทึก Database Config
POST   /setup/migrate               - Run Migrations
POST   /setup/seed                  - Run Seeders
POST   /setup/admin/create          - สร้าง Admin Account
POST   /setup/complete              - เสร็จสิ้นการติดตั้ง
GET    /setup/progress              - ตรวจสอบความคืบหน้า
```

---

## 💾 3. ระบบ Backup & Version Control

### คุณสมบัติหลัก

#### 3.1 ประเภท Backup
- **Full Backup** - สำรองทั้งไฟล์และฐานข้อมูล
- **Database Backup** - สำรองเฉพาะฐานข้อมูล

#### 3.2 Auto Backup
- สำรองข้อมูลอัตโนมัติก่อนอัพเดท
- Schedule Backup ตามเวลาที่กำหนด
- เก็บประวัติ Backup พร้อมข้อมูล:
  - Version ของระบบ
  - ขนาดไฟล์
  - เวลาที่สำรอง
  - ผู้สร้าง Backup

#### 3.3 Backup Management
- ดูรายการ Backup ทั้งหมด
- Download Backup File
- Restore จาก Backup
- ลบ Backup เก่า (Auto/Manual)

#### 3.4 Version Checking
- ตรวจสอบเวอร์ชั่นปัจจุบัน
- เปรียบเทียบกับเวอร์ชั่นใหม่
- แสดง Change Log
- แจ้งเตือนเมื่อมีเวอร์ชั่นใหม่

### API Endpoints
```
GET    /admin/backups               - แสดงรายการ Backup
POST   /admin/backups               - สร้าง Backup ใหม่
GET    /admin/backups/{id}/download - Download Backup
POST   /admin/backups/{id}/restore  - Restore จาก Backup
DELETE /admin/backups/{id}          - ลบ Backup
POST   /admin/backups/clean         - ล้าง Backup เก่า
```

---

## 📊 4. Super Admin Dashboard (Enhanced)

### คุณสมบัติหลัก

#### 4.1 Statistics Overview
- Total Users
- Total Vendors (Active/Pending)
- Total Products
- Total Orders
- Total Revenue (All-time/Monthly)
- Total Commissions (Paid/Pending)
- MLM Members

#### 4.2 Real-time Charts (Chart.js + GSAP)

**Sales Chart**
- กราฟยอดขาย 12 เดือนล่าสุด
- แสดงทั้งยอดขายและจำนวน Order
- Animation เมื่อโหลดข้อมูล

**Users Growth**
- กราฟจำนวนผู้ใช้ใหม่รายเดือน
- Trend Analysis

**Vendors by Status**
- Pie Chart แสดงสถานะร้านค้า
- Active, Pending, Rejected

**Commission by Type**
- Bar Chart แสดงค่าคอมมิชชั่นแยกตามประเภท
- Level Commission, Rank Bonus, Performance Bonus

**Top Products**
- ตารางสินค้าขายดี Top 10
- จำนวนขาย
- ยอดรวม

#### 4.3 Recent Activities
- Recent Orders (10 รายการล่าสุด)
- Recent Users (10 คนล่าสุด)
- Top Vendors (10 ร้านค้า)

#### 4.4 GSAP Animations
- Smooth scroll animations
- Counter animations สำหรับตัวเลข
- Chart entrance animations
- Card hover effects

### API Endpoints
```
GET    /admin/dashboard             - Dashboard หลัก
GET    /admin/dashboard/charts      - ข้อมูลกราฟ (JSON)
```

---

## ⚙️ 5. ระบบตั้งค่าแบบรวมศูนย์

### คุณสมบัติหลัก

#### 5.1 App Settings Groups
- **General** - ตั้งค่าทั่วไป
  - App Name
  - App Logo
  - Default Currency

- **Vendor** - ตั้งค่าร้านค้า
  - Enable Vendor Registration
  - Vendor Commission Rate

- **MLM** - ตั้งค่า MLM
  - MLM Type (Unilevel/Binary)
  - Max Depth
  - Commission Rates

- **Payment** - ตั้งค่าการชำระเงิน
  - Stripe Settings
  - PromptPay Settings

- **System** - ตั้งค่าระบบ
  - Maintenance Mode
  - Debug Mode
  - Cache Settings

#### 5.2 Settings Features
- Group-based Organization
- Type Casting (string, boolean, integer, json, file)
- Public/Private Settings
- Editable/Read-only Settings
- Cache Management

#### 5.3 File Uploads
- Logo Upload
- Favicon Upload
- ลบไฟล์เก่าอัตโนมัติ
- Image Optimization

### API Endpoints
```
GET    /admin/settings              - หน้าตั้งค่า
POST   /admin/settings              - บันทึกการตั้งค่า
POST   /admin/settings/logo         - Upload Logo
POST   /admin/settings/favicon      - Upload Favicon
GET    /admin/settings/public       - ดึงการตั้งค่าสาธารณะ (สำหรับ Frontend)
```

---

## Version 1.1.0 Features

## 🔄 6. Version Update System

### Description
Automatic version checking system that compares the current installation with the latest version on GitHub and notifies super admin when updates are available.

### Features
- **Automatic version checking** from GitHub releases
- **Update notifications** for super admin
- **Update history** tracking
- **Manual update process** with step-by-step instructions
- **Critical update flagging** for security patches
- **Changelog display** with breaking changes

### Usage

#### For Super Admin:
1. Navigate to **Admin > Version Management**
2. Click "Check for Updates" to manually check
3. System automatically checks every 24 hours
4. View update details, changelog, and release notes
5. Follow the instructions to update the system

#### API Endpoints:
- `GET /api/v1/version` - Get current version info
- `GET /api/v1/version/check` - Check for updates
- `POST /admin/version/update/start` - Start update process
- `POST /admin/version/update/complete` - Mark update as completed

### Technical Details
- GitHub API integration
- Semantic versioning support
- Update logs for audit trail
- Rollback capability

---

## 💳 7. NFC Payment System

### Description
Web NFC API integration for contactless card payments, allowing users to pay using NFC cards through supported devices.

### Features
- **Web NFC API** support for modern browsers
- **Card registration** and management
- **User linking** - Link NFC cards to user wallets
- **Payment processing** via NFC tap
- **Balance checking** without payment
- **Transaction logging** for all NFC operations
- **Multi-device support** - Web and future mobile app ready

### Usage

#### For Customers:
1. Navigate to **NFC Payment** page
2. Tap NFC-enabled device with card
3. Confirm payment amount
4. Complete transaction

#### For Super Admin:
1. Navigate to **Admin > NFC Cards**
2. **Register new cards** by scanning or entering card UID
3. **Link cards to users** and their wallets
4. **View transaction history**
5. **Activate/deactivate** cards as needed

#### For POS Terminals:
- Use `/nfc/payment` endpoint
- Scan customer's NFC card
- Process payment automatically

### Supported Card Types
- Standard (basic NFC cards)
- Premium (enhanced features)
- VIP (priority support)

### Technical Details
- Web NFC API (Chrome/Android support)
- Unique card UID identification
- Encrypted transaction processing
- Real-time balance updates

### API Endpoints:
- `POST /api/v1/nfc/process` - Process payment
- `POST /api/v1/nfc/check-balance` - Check card balance
- `POST /api/v1/nfc/verify` - Verify card validity
- `POST /api/v1/nfc/card-info` - Get card information

### Browser Support
- Chrome 89+ (Android)
- Chrome 114+ (Desktop with flag)
- Edge 89+ (Android)

---

## ✅ 8. Shop Verification System

### Description
Comprehensive KYC (Know Your Customer) system for vendors to verify their shop authenticity with multiple verification levels.

### Features
- **Document upload** system for verification
- **Multi-level badges** (Bronze, Silver, Gold, Platinum)
- **Document types supported:**
  - Business registration
  - Tax certificate
  - Business license
  - ID card (front/back)
  - Selfie with ID
  - Bank statements
  - Bank book photos
- **Admin review** interface
- **Rejection with feedback**
- **Verification badges** displayed on shop profiles

### Verification Badges

#### 🥉 Bronze - Basic Verification
- Requirements: ID card verification
- Benefits: Basic trust badge

#### 🥈 Silver - Full Verification
- Requirements: ID + Business registration + Tax certificate
- Benefits: Enhanced visibility in listings

#### 🥇 Gold - Premium Verification
- Requirements: Silver requirements + Bank verification
- Benefits: Featured placement, higher trust

#### 💎 Platinum - Ultimate Verification
- Requirements: Gold requirements + Business license
- Benefits: Top placement, maximum trust, exclusive features

### Usage

#### For Vendors:
1. Navigate to **Vendor > Verification**
2. Fill out verification form
3. Upload required documents
4. Submit for review
5. Wait for admin approval
6. Receive verification badge

#### For Admin:
1. Navigate to **Admin > Verification**
2. Review pending verifications
3. Check all submitted documents
4. Approve/Reject with feedback
5. Assign appropriate badge level

### Technical Details
- Secure document storage
- Automatic badge calculation based on documents
- Verification status tracking
- Email notifications for status changes

---

## 🌳 9. MLM Network Tree Visualization

### Description
Modern, interactive organizational tree visualization using D3.js for displaying MLM network structure.

### Features
- **Interactive tree diagram** with zoom and pan
- **Real-time data** display
- **Member details on hover** (tooltip)
- **Click to view full details**
- **Add members directly** from tree
- **Visual indicators:**
  - 🟢 Green border: KYC verified
  - 🔴 Red border: Not maintaining minimum sales
  - ⚫ Gray border: Normal status
- **Avatar display:**
  - Default avatar for new users
  - LINE profile picture after KYC
  - Custom uploaded avatar support
- **Rank display** with colored badges
- **Level-based layout**
- **Expandable/collapsible** branches

### Usage

#### For Users:
1. Navigate to **MLM > Network Tree**
2. View your downline structure
3. Hover over members for quick info
4. Click members for detailed view
5. Use zoom controls to navigate large trees

#### For Admin:
1. View any user's tree
2. Add members to downline
3. Monitor network health
4. Track inactive members (red border)

### Interactive Features
- **Zoom:** Scroll wheel or pinch
- **Pan:** Click and drag
- **Center:** Double-click background
- **Details:** Click on member avatar
- **Add Member:** Click "+" button (admin)

### Technical Details
- D3.js hierarchy layout
- SVG rendering for performance
- Lazy loading for large trees
- Real-time sales tracking
- Configurable depth limits

### API Endpoints:
- `GET /mlm/tree-data/{userId?}` - Get tree structure
- `GET /mlm/tree/node/{user}` - Get member details
- `POST /mlm/tree/add-member` - Add new member

---

## 📸 10. Profile Image Management

### Description
Enhanced profile image system with LINE integration and custom upload support.

### Features
- **Default avatars** for new users
- **LINE profile sync** after KYC verification
- **Custom upload** capability
- **Automatic source tracking**
- **Image optimization**

### Usage

#### For Users:
1. Navigate to **Profile Settings**
2. Choose image source:
   - Use LINE profile picture (after KYC)
   - Upload custom image
3. Save changes

### Technical Details
- Image storage in public disk
- Multiple source support
- Automatic resizing
- CDN-ready

---

## 📦 Database Schema

### Phase 2 Tables

#### system_info
```sql
- id
- version
- build_number
- installed_at
- last_updated_at
- php_version (JSON)
- database_version (JSON)
- extensions (JSON)
- is_setup_completed
- setup_notes
- timestamps
```

#### app_settings
```sql
- id
- key (unique)
- value
- type (string, boolean, integer, json, file)
- group
- description
- is_public
- is_editable
- timestamps
```

#### theme_customizations
```sql
- id
- vendor_id (FK)
- theme_name
- primary_color
- secondary_color
- accent_color
- gradient_colors (JSON)
- font_family
- logo_url
- favicon_url
- banner_url
- custom_css (JSON)
- is_premium
- premium_expires_at
- is_active
- timestamps
```

#### premium_features
```sql
- id
- feature_name
- feature_key (unique)
- description
- price
- billing_cycle (monthly, yearly, lifetime)
- is_active
- timestamps
```

#### vendor_premium_subscriptions
```sql
- id
- vendor_id (FK)
- premium_feature_id (FK)
- started_at
- expires_at
- status (active, expired, cancelled)
- amount_paid
- timestamps
```

#### backups
```sql
- id
- name
- type (full, database, files)
- file_path
- file_size
- version
- status (pending, completed, failed)
- error_message
- started_at
- completed_at
- created_by (FK)
- auto_backup
- timestamps
```

### Version 1.1.0 Tables

#### nfc_cards
```sql
- id
- card_uid (unique)
- card_type (standard, premium, vip)
- user_id (FK, nullable)
- wallet_id (FK, nullable)
- is_active
- issued_at
- expires_at
- last_used_at
- timestamps
```

#### nfc_card_transactions
```sql
- id
- nfc_card_id (FK)
- transaction_type (payment, balance_check, verification)
- amount
- status (success, failed, pending)
- error_message
- ip_address
- user_agent
- timestamps
```

#### shop_verifications
```sql
- id
- vendor_id (FK, unique)
- verification_badge (bronze, silver, gold, platinum)
- status (pending, approved, rejected)
- documents (JSON)
- notes
- reviewed_by (FK, nullable)
- reviewed_at
- timestamps
```

#### system_versions
```sql
- id
- version
- release_date
- changelog (JSON)
- is_critical
- download_url
- installed_at
- installed_by (FK)
- timestamps
```

---

## Installation & Setup

### 1. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install NPM dependencies
npm install

# Build assets
npm run build
```

### 2. Run Migrations

```bash
php artisan migrate
```

### 3. Seed Initial Data

```bash
php artisan db:seed
```

### 4. Set Permissions

```bash
chmod -R 755 storage
chmod -R 755 bootstrap/cache
```

### 5. Configure Environment

Add to `.env`:

```env
# NFC Configuration
NFC_ENABLED=true
NFC_TIMEOUT=30

# Version Update
GITHUB_REPO=xjanova/Thaiprompt-Affiliate
VERSION_CHECK_INTERVAL=24

# Verification
VERIFICATION_DOCUMENT_DISK=public
MAX_DOCUMENT_SIZE=5120
```

---

## Configuration

### NFC Settings
- Card types: standard, premium, vip
- Transaction timeout: 30 seconds
- Auto-balance check: enabled

### Verification Settings
- Max document size: 5MB
- Allowed formats: PDF, JPG, PNG
- Review turnaround: 48 hours

### MLM Tree Settings
- Max depth: 5 levels
- Node size: 120x80px
- Animation duration: 750ms

---

## Security Considerations

### Setup Wizard Security
- ป้องกันการเข้าถึง Setup Wizard หลังติดตั้งเสร็จแล้ว
- Middleware `RedirectIfSetupCompleted`

### Backup Security
- เฉพาะ Admin เท่านั้นที่สามารถสร้าง/กู้คืน Backup
- บันทึก Log ทุกครั้งที่มีการสร้าง/กู้คืน

### Premium Features Security
- ตรวจสอบ Subscription Status
- ป้องกันการใช้งาน Premium Feature โดยไม่ได้สมัคร

### Settings Security
- แยก Public/Private Settings
- ป้องกันการแก้ไข Read-only Settings
- Role-based Access Control

### NFC Security
- Card UID encryption
- Transaction signing
- IP address logging
- Rate limiting

### Document Security
- Encrypted storage
- Access control
- Audit logging
- Auto-deletion after verification

### API Security
- Token authentication
- CORS protection
- Input validation
- SQL injection prevention

---

## Performance Optimization

### Caching
- Settings Cache (1 hour TTL)
- Theme CSS Cache
- Chart Data Cache

### Lazy Loading
- Chart.js โหลดเมื่อต้องการใช้งาน
- GSAP โหลดแบบ Async

### Database Optimization
- Index สำหรับ Frequently Queried Columns
- Eager Loading สำหรับ Relationships

---

## Browser Compatibility

### NFC Support
- ✅ Chrome 89+ (Android)
- ✅ Chrome 114+ (Desktop with flag)
- ✅ Edge 89+ (Android)
- ❌ Safari (not supported)
- ❌ Firefox (not supported)

### Tree Visualization
- ✅ All modern browsers
- ✅ IE11+ (with polyfills)
- ✅ Mobile browsers

---

## Troubleshooting

### NFC Issues
1. **"NFC not supported"**: Use Chrome on Android or enable flag on Desktop
2. **Card not detected**: Check NFC is enabled on device
3. **Payment failed**: Verify card is linked and has sufficient balance

### Verification Issues
1. **Upload failed**: Check file size and format
2. **Status stuck**: Contact admin for review
3. **Badge not showing**: Clear cache and refresh

### Tree Issues
1. **Tree not loading**: Check API permissions
2. **Performance slow**: Reduce depth limit
3. **Member not showing**: Verify downline relationships

---

## API Documentation

### Authentication
All protected endpoints require Bearer token:

```http
Authorization: Bearer {token}
```

### Rate Limiting
- Public endpoints: 60 requests/minute
- Authenticated: 120 requests/minute
- Admin: Unlimited

### Response Format
```json
{
    "success": true,
    "data": {},
    "message": "Success message"
}
```

---

## Support

For issues or questions:
- GitHub Issues: https://github.com/xjanova/Thaiprompt-Affiliate/issues
- Repository: https://github.com/xjanova/Thaiprompt-Affiliate

---

## Changelog

### Version 1.1.0 (Current)
- ✨ Added NFC payment system
- ✨ Added shop verification with badges
- ✨ Added version update checker
- ✨ Added MLM tree visualization
- ✨ Added profile image management
- ✨ Added web setup wizard
- ✨ Added theme customization (Premium)
- ✨ Added backup & version control
- ✨ Enhanced super admin dashboard
- ✨ Added centralized settings system
- 🐛 Bug fixes and improvements

### Version 1.0.0
- 🎉 Initial release
- Multi-vendor marketplace
- MLM system
- Wallet system
- POS integration
