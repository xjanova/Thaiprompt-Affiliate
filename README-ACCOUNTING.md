# 📊 ระบบบัญชี - Accounting System

ระบบบัญชีครบวงจร เชื่อมต่อกับ FlowAccount พร้อมใช้งานทันที!

## 🚀 การติดตั้ง

### 1. รัน Migration

```bash
php artisan migrate
```

### 2. รัน Seeders

```bash
# สร้าง Permissions สำหรับระบบบัญชี
php artisan db:seed --class=AccountingPermissionsSeeder

# สร้างข้อมูลตัวอย่าง (Demo Data)
php artisan db:seed --class=AccountingDemoSeeder
```

### 3. ตั้งค่า FlowAccount (Optional)

เพิ่มใน `.env`:

```env
# FlowAccount Configuration
FLOWACCOUNT_USE_SANDBOX=true
FLOWACCOUNT_CLIENT_ID=your_client_id
FLOWACCOUNT_CLIENT_SECRET=your_client_secret
FLOWACCOUNT_REDIRECT_URI=${APP_URL}/admin/accounting/flowaccount/callback

# Accounting Add-on
ADDON_ACCOUNTING_ENABLED=true
ADDON_ACCOUNTING_LICENSE_KEY=your_license_key
```

## 📦 ข้อมูลตัวอย่างที่สร้าง (Demo Data)

เมื่อรัน `AccountingDemoSeeder` จะได้ข้อมูลตัวอย่างดังนี้:

### 🏢 บริษัท
- บริษัท ไทยพร้อมท์ จำกัด (Thai Prompt Co., Ltd.)

### 📊 ผังบัญชี (Chart of Accounts)
- สินทรัพย์ (Assets): เงินฝากธนาคาร, ลูกหนี้การค้า
- หนี้สิน (Liabilities): เจ้าหนี้การค้า, ภาษีขาย
- ส่วนของเจ้าของ (Equity): กำไรสะสม
- รายได้ (Revenue): รายได้จากขายสินค้า, รายได้จากบริการ
- ค่าใช้จ่าย (Expenses): เงินเดือน, ค่าเช่า, ค่าน้ำไฟ, วัสดุสำนักงาน, การตลาด

### 🏦 บัญชีธนาคาร
1. ธนาคารกสิกรไทย - บัญชีกระแสรายวัน (500,000 บาท)
2. ธนาคารไทยพาณิชย์ - บัญชีออมทรัพย์ (300,000 บาท)

### 💰 อัตราภาษี
- VAT 7% (ค่าเริ่มต้น)
- ยกเว้นภาษี 0%

### 👥 ลูกค้า (Customers)
1. บริษัท สยามเทคโนโลยี จำกัด - วงเงินเครดิต 500,000 บาท
2. บริษัท ดิจิทัล มาร์เก็ตติ้ง จำกัด - วงเงินเครดิต 300,000 บาท
3. ห้างหุ้นส่วนจำกัด เอสเอ็มอี คอนซัลติ้ง - จ่ายสด

### 🏪 ผู้จำหน่าย (Vendors)
1. บริษัท ออฟฟิศ ซัพพลาย จำกัด
2. บริษัท โฮสติ้ง เซอร์วิส จำกัด

### 📦 สินค้า/บริการ (Products/Services)
1. บริการพัฒนาเว็บไซต์ - 50,000 บาท/โครงการ
2. บริการ SEO - 15,000 บาท/เดือน
3. บริการดูแลระบบ - 5,000 บาท/เดือน
4. บริการให้คำปรึกษา - 3,000 บาท/ชั่วโมง
5. ระบบจัดการเนื้อหา (CMS) - 80,000 บาท/ชุด

### 📄 ใบแจ้งหนี้ (Invoices)
1. **INV-00001** - ชำระแล้ว (Paid) - 65,000 บาท
   - พัฒนาเว็บไซต์ + บริการดูแล 3 เดือน

2. **INV-00002** - รอชำระ (Pending) - 15,000 บาท
   - บริการ SEO 1 เดือน

3. **INV-00003** - ชำระบางส่วน (Partial) - 95,000 บาท
   - CMS + คำปรึกษา 5 ชม. (ชำระแล้ว 50,000 บาท)

4. **INV-00004** - เกินกำหนด (Overdue) - 30,000 บาท
   - บริการ SEO 2 เดือน (เกินกำหนดชำระ 20 วัน)

5. **QUO-00001** - แบบร่าง (Draft) - 50,000 บาท
   - ใบเสนอราคาพัฒนาเว็บไซต์

### 💸 รายจ่าย (Expenses)
1. **EXP-00001** - ชำระแล้ว - 2,500 บาท
   - วัสดุสำนักงาน (กระดาษ, ปากกา, แฟ้ม)

2. **EXP-00002** - ชำระแล้ว - 1,500 บาท
   - ค่า Hosting + Domain

3. **EXP-00003** - รอชำระ - 75,000 บาท
   - เงินเดือนพนักงาน 3 คน

4. **EXP-00004** - ชำระแล้ว - 10,000 บาท
   - ค่าโฆษณา Google Ads

### 💳 การชำระเงิน (Payments)
- 4 รายการชำระเงิน (โอนเงิน, เงินสด, บัตรเครดิต)

## 📋 Features

### ✅ ระบบใบแจ้งหนี้
- สร้าง/แก้ไข/ลบใบแจ้งหนี้
- ใบกำกับภาษี
- ใบเสนอราคา
- บันทึกการชำระเงิน
- ติดตามยอดคงค้าง
- Export PDF (กำลังพัฒนา)
- ส่ง Email (กำลังพัฒนา)

### ✅ ระบบรายจ่าย
- บันทึกรายจ่าย
- อัพโหลดใบเสร็จ
- จัดหมวดหมู่
- บันทึกการจ่ายเงิน
- ติดตามยอดค้างจ่าย

### ✅ จัดการลูกค้า/ผู้จำหน่าย
- เพิ่ม/แก้ไข/ลบผู้ติดต่อ
- ระบุประเภท (ลูกค้า/ผู้จำหน่าย/ทั้งสองอย่าง)
- กำหนดวงเงินเครดิต
- เงื่อนไขการชำระเงิน
- ออกใบแจ้งหนี้

### ✅ จัดการสินค้า/บริการ
- เพิ่ม/แก้ไข/ลบสินค้า
- กำหนดราคาขาย/ต้นทุน
- คำนวณกำไร
- ตั้งค่าภาษี
- เชื่อมโยงบัญชี

### ✅ รายงานทางการเงิน
- **กำไร-ขาดทุน** (Profit & Loss)
- **งบดุล** (Balance Sheet)
- **กระแสเงินสด** (Cash Flow)
- **รายงานการขาย** (Sales Report)
- **รายงานค่าใช้จ่าย** (Expense Report)
- **รายงานภาษี** (Tax Report)

### ✅ FlowAccount Integration
- OAuth 2.0 Authentication
- เชื่อมต่อบัญชี FlowAccount
- Sync ข้อมูล (ใบแจ้งหนี้, รายจ่าย, ลูกค้า, สินค้า)
- Auto refresh token

### ✅ การจัดการสิทธิ์
40+ Permissions:
- `accounting.view_dashboard`
- `accounting.view_invoices`
- `accounting.create_invoices`
- `accounting.edit_invoices`
- `accounting.delete_invoices`
- `accounting.approve_invoices`
- และอื่น ๆ อีกมากมาย

## 🎯 การใช้งาน

### เข้าสู่ระบบ
1. ไปที่ **Admin Panel**
2. คลิกเมนู **"📊 ระบบบัญชี"**

### สร้างใบแจ้งหนี้
1. เข้าเมนู **ระบบบัญชี > ใบแจ้งหนี้**
2. คลิก **"สร้างใบแจ้งหนี้"**
3. เลือกลูกค้า
4. เพิ่มรายการสินค้า/บริการ
5. ระบุวันที่และเงื่อนไขการชำระ
6. บันทึก

### บันทึกรายจ่าย
1. เข้าเมนู **ระบบบัญชี > รายจ่าย**
2. คลิก **"บันทึกรายจ่าย"**
3. เลือกผู้จำหน่าย (Optional)
4. เลือกหมวดหมู่ค่าใช้จ่าย
5. เพิ่มรายละเอียด
6. อัพโหลดใบเสร็จ (Optional)
7. บันทึก

### ดูรายงาน
1. เข้าเมนู **ระบบบัญชี > รายงาน**
2. เลือกประเภทรายงาน
3. กำหนดช่วงเวลา
4. ดูรายงาน / Export

## 🔧 เชื่อมต่อ FlowAccount

### ขั้นตอนที่ 1: ลงทะเบียน FlowAccount
1. ไปที่ [FlowAccount Developer](https://developers.flowaccount.com)
2. สร้างแอปพลิเคชัน
3. รับ Client ID และ Client Secret

### ขั้นตอนที่ 2: ตั้งค่า
1. เข้าเมนู **ระบบบัญชี > เชื่อมต่อ FlowAccount**
2. ใส่ Client ID และ Client Secret
3. คลิก **"เชื่อมต่อ"**
4. ยืนยันการอนุญาต OAuth
5. เสร็จสิ้น!

### ขั้นตอนที่ 3: Sync ข้อมูล
- คลิก **"Sync ทั้งหมด"** หรือ
- เลือก Sync เฉพาะประเภท (Invoices, Expenses, Contacts, Products)

## 📊 Database Schema

### ตารางหลัก
- `accounting_settings` - การตั้งค่า
- `accounting_companies` - บริษัท
- `accounting_chart_of_accounts` - ผังบัญชี
- `accounting_contacts` - ลูกค้า/ผู้จำหน่าย
- `accounting_products` - สินค้า/บริการ
- `accounting_invoices` + `accounting_invoice_items` - ใบแจ้งหนี้
- `accounting_expenses` + `accounting_expense_items` - รายจ่าย
- `accounting_payments` - การชำระเงิน
- `accounting_bank_accounts` - บัญชีธนาคาร
- `accounting_journal_entries` - บันทึกรายการบัญชี
- `accounting_flowaccount_connections` - การเชื่อมต่อ FlowAccount
- `accounting_tax_rates` - อัตราภาษี
- `accounting_export_templates` - แม่แบบ Export
- `accounting_activity_logs` - บันทึกกิจกรรม

## 🎨 UI/UX Features

- 🌓 Dark Mode Support
- 📱 Responsive Design
- 🎨 Beautiful Tailwind CSS
- ⚡ Alpine.js Interactivity
- 📊 Stats Cards & Charts
- 🔍 Advanced Filters
- 📄 Pagination
- 🎯 Status Badges

## 📝 TODO (Optional Enhancement)

- [ ] PDF Export Implementation
- [ ] Email Sending Implementation
- [ ] CSV/Excel Import
- [ ] Advanced Charts (Chart.js)
- [ ] Print Templates
- [ ] Recurring Invoices
- [ ] Multi-currency Support
- [ ] Advanced Reports
- [ ] Bank Reconciliation
- [ ] Expense Categories Management UI

## 🤝 Contributing

ระบบนี้พัฒนาโดย Claude AI และพร้อมใช้งานทันที!

## 📄 License

Same as main project license.

## 🆘 Support

ติดปัญหา? เปิด Issue ที่ GitHub Repository

---

สร้างด้วย ❤️ โดย Claude AI 🤖
