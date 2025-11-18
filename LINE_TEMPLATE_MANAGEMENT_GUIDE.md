# 📋 LINE Flex Message Template Management System

> **คู่มือการใช้งานระบบจัดการ LINE Flex Message Templates**
>
> Version: 1.0.0 | Updated: 2025-11-18

---

## 🎯 สรุปฟีเจอร์ที่เพิ่มเข้ามา

ระบบจัดการ LINE Flex Message Templates ได้รับการพัฒนาเพิ่มเติมให้สามารถ:

### ✅ ฟีเจอร์หลัก

1. **แก้ไข Templates ได้** (Edit)
   - แก้ไข Flex Message JSON ผ่าน CodeMirror Editor
   - แก้ไขชื่อ, คำอธิบาย, ตัวแปร, หมวดหมู่
   - Beautify และ Validate JSON ก่อนบันทึก
   - รองรับ Dark Mode

2. **รีเซ็ตค่าเริ่มต้นได้** (Reset to Default)
   - Templates ที่มี flag `is_default = true` สามารถ reset กลับค่าเริ่มต้นได้
   - ป้องกันการแก้ไขผิดพลาดถาวร
   - ข้อมูลเดิมจาก Seeder จะถูก restore

3. **คัดลอก Templates ได้** (Duplicate)
   - คัดลอก template ที่มีอยู่เพื่อสร้างเวอร์ชันใหม่
   - Template ที่ duplicate มาจะมี `original_template_key` บอกที่มา
   - เหมาะสำหรับการทดลอง/ปรับแต่งโดยไม่กระทบของเดิม

4. **สร้าง Templates ใหม่ได้** (Create)
   - สร้าง template ใหม่ตั้งแต่ต้น
   - ใช้ CodeMirror JSON Editor พร้อม syntax highlighting
   - Validation แบบเรียลไทม์

5. **ลบ Templates ได้** (Delete)
   - ลบ template ที่สร้างเอง
   - **ป้องกันการลบ default templates** (templates จาก seeder)

---

## 📂 โครงสร้างไฟล์ที่เพิ่ม/แก้ไข

### 1. **Database Migration**
```
database/migrations/2025_11_18_100000_add_reset_fields_to_line_signup_templates_table.php
```
- เพิ่มคอลัมน์ `is_default` (boolean) - ระบุว่าเป็น template ต้นฉบับหรือไม่
- เพิ่มคอลัมน์ `original_template_key` (string) - เก็บ key ต้นฉบับที่ duplicate มา
- เพิ่มคอลัมน์ `category` (string) - หมวดหมู่ของ template

### 2. **Model Enhancement**
```
app/Models/LineSignupTemplate.php
```
**Methods เพิ่มเติม:**
- `canReset()` - ตรวจสอบว่า template สามารถ reset ได้หรือไม่
- `getDefaultData()` - ดึงข้อมูล default จาก seeder
- `getDefaultVariables()` - ดึง default variables
- `getDefaultDescription()` - ดึง default description
- `scopeDefaults()` - Query scope สำหรับ default templates
- `scopeActive()` - Query scope สำหรับ active templates
- `scopeCategory()` - Query scope กรองตาม category

### 3. **Controller Methods**
```
app/Http/Controllers/Admin/LineMembershipSignupAdminController.php
```
**Methods เพิ่มเติม:**
- `create()` - แสดงหน้าสร้าง template ใหม่
- `edit(LineSignupTemplate $template)` - แสดงหน้าแก้ไข template
- `store(Request $request)` - บันทึก template ใหม่
- `update(Request $request, LineSignupTemplate $template)` - อัพเดท template
- `resetTemplate(LineSignupTemplate $template)` - reset template กลับค่าเริ่มต้น
- `duplicateTemplate(LineSignupTemplate $template)` - คัดลอก template
- `deleteTemplate()` - ปรับปรุงให้ป้องกันการลบ default templates

### 4. **Routes**
```
routes/admin.php
```
**Routes เพิ่มเติม:**
- `GET /admin/line-membership-signup/templates/create` - หน้าสร้าง template
- `POST /admin/line-membership-signup/templates` - บันทึก template ใหม่
- `GET /admin/line-membership-signup/templates/{template}/edit` - หน้าแก้ไข
- `PUT /admin/line-membership-signup/templates/{template}` - อัพเดท template
- `POST /admin/line-membership-signup/templates/{template}/reset` - reset template
- `POST /admin/line-membership-signup/templates/{template}/duplicate` - duplicate template

### 5. **Views**
```
resources/views/admin/line-membership-signup/
├── templates.blade.php (ปรับปรุง)
└── template-form.blade.php (ใหม่)
```

**templates.blade.php - ปรับปรุง:**
- เพิ่มปุ่ม "สร้าง Template ใหม่"
- เพิ่มปุ่ม "แก้ไข" ในแต่ละ card
- เพิ่มปุ่ม "Reset" สำหรับ default templates
- เพิ่มปุ่ม "คัดลอก" สำหรับทุก template
- เพิ่มปุ่ม "ลบ" สำหรับ non-default templates
- แสดง badges: Default, Active/Inactive, Usage Count
- Alpine.js functions: `resetTemplate()`, `duplicateTemplate()`, `deleteTemplate()`

**template-form.blade.php - ใหม่:**
- ฟอร์มสำหรับสร้าง/แก้ไข template
- CodeMirror JSON Editor พร้อม syntax highlighting
- Dark mode support (theme: dracula/monokai)
- Beautify JSON button
- Validate JSON button
- Responsive design (mobile-first)

### 6. **Seeder Update**
```
database/seeders/LineSignupTemplateSeeder.php
```
- เพิ่ม `is_default = true` ให้กับ templates ทั้ง 5 ตัว
- เพิ่ม `category` ให้กับแต่ละ template:
  - `welcome_hero` → category: `welcome`
  - `earning_calculator` → category: `earning`
  - `success_story` → category: `success_story`
  - `training_course` → category: `training`
  - `quick_start_guide` → category: `guide`

---

## 🚀 วิธีการใช้งาน

### 1. **ดู Templates ทั้งหมด**
```
URL: /admin/line-membership-signup/templates
```
- แสดงรายการ templates แบบ grid cards
- แต่ละ card แสดงข้อมูล: ชื่อ, template key, variables, created/updated date
- แสดง badges: Default, Active, Usage Count

### 2. **สร้าง Template ใหม่**
```
1. คลิกปุ่ม "สร้าง Template ใหม่" (สีเขียว) ที่มุมบนขวา
2. กรอกข้อมูล:
   - Template Key (a-z, 0-9, _) - ห้ามซ้ำ
   - ชื่อ Template
   - หมวดหมู่ (welcome, earning, success_story, training, guide, other)
   - คำอธิบาย
   - Variables (คั่นด้วย comma: user_name, email, phone)
   - เปิดใช้งาน (checkbox)
3. แก้ไข Flex Message JSON ใน CodeMirror Editor
4. กดปุ่ม "จัดรูปแบบ" เพื่อ beautify JSON
5. กดปุ่ม "ตรวจสอบ" เพื่อ validate JSON
6. กดปุ่ม "สร้าง Template"
```

### 3. **แก้ไข Template**
```
1. คลิกปุ่ม "แก้ไข" (สีม่วง) ที่ template card
2. แก้ไขข้อมูลที่ต้องการ
3. แก้ไข JSON ใน CodeMirror Editor
4. Beautify และ Validate JSON
5. กดปุ่ม "บันทึกการแก้ไข"
```

**หมายเหตุ:**
- Template Key **ไม่สามารถแก้ไขได้** เมื่อสร้างแล้ว
- Default templates สามารถแก้ไขได้ แต่แนะนำให้ duplicate แล้วแก้ไขแทน

### 4. **Reset Template (เฉพาะ Default Templates)**
```
1. คลิกปุ่ม "Reset" (สีส้ม-แดง) ที่ template card
2. ยืนยันการ reset
3. Template จะถูก restore กลับค่าเริ่มต้นจาก seeder
```

**เมื่อไหร่ควรใช้ Reset?**
- แก้ไข default template แล้วผลลัพธ์ไม่ดี
- ต้องการกลับไปใช้ค่าเริ่มต้นจากระบบ
- ทดลองแก้ไขแล้วต้องการเริ่มใหม่

### 5. **คัดลอก Template**
```
1. คลิกปุ่ม "คัดลอก" (สีฟ้า-เขียว) ที่ template card
2. ยืนยันการคัดลอก
3. Template ใหม่จะถูกสร้างขึ้นด้วย:
   - Template Key: {original_key}_copy_{timestamp}
   - ชื่อ: {original_name} (Copy)
   - is_default: false
   - original_template_key: {original_key}
```

**เมื่อไหร่ควรใช้ Duplicate?**
- ต้องการสร้าง template คล้ายๆ กับที่มีอยู่
- ต้องการทดลองแก้ไข default template โดยไม่กระทบของเดิม
- ต้องการสร้างหลายเวอร์ชันของ template เดียวกัน

### 6. **ลบ Template**
```
1. คลิกปุ่ม "ลบ" (สีแดง) ที่ template card (เฉพาะ non-default templates)
2. ยืนยันการลบ
3. Template จะถูกลบออกจากระบบ
```

**ข้อจำกัด:**
- **ไม่สามารถลบ default templates ได้** (is_default = true)
- การลบไม่สามารถยกเลิกได้ (ไม่มี soft delete)

---

## 🔗 การทำงานร่วมกับ LINE Bot

### Workflow

```
User → LINE OA → Webhook → LineWebhookController
                              ↓
                    LineSignupService
                              ↓
        ดึง Template จาก LineSignupTemplate::where('template_key', 'xxx')
                              ↓
        render() - แทนค่าตัวแปร {{variable}}
                              ↓
        ส่ง Flex Message กลับไป LINE
                              ↓
        incrementUsage() - เพิ่ม usage count
```

### Templates ที่ใช้ใน Signup Flow

1. **`welcome_hero`** - Welcome message เมื่อ user ติดตาม LINE OA
2. **`earning_calculator`** - แสดงการคำนวณรายได้
3. **`success_story`** - เรื่องราวความสำเร็จของสมาชิก
4. **`training_course`** - โปรโมทคอร์สอบรม
5. **`quick_start_guide`** - คู่มือเริ่มต้นหลังสมัครสำเร็จ

### การใช้ Templates ใน Code

**ตัวอย่างการดึงและใช้ template:**

```php
use App\Models\LineSignupTemplate;

// ดึง template
$template = LineSignupTemplate::where('template_key', 'welcome_hero')
    ->where('is_active', true)
    ->first();

if (!$template) {
    // Fallback to hardcoded template
    $flexMessage = $this->getHardcodedWelcomeMessage();
} else {
    // ใช้ template จาก database
    $flexMessage = $template->render([
        'user_name' => $user->name,
    ]);

    // เพิ่ม usage count
    $template->incrementUsage();
}

// ส่ง Flex Message ไปยัง LINE
$this->lineMessagingAPI->replyMessage($replyToken, [
    [
        'type' => 'flex',
        'altText' => 'ยินดีต้อนรับ!',
        'contents' => $flexMessage,
    ],
]);
```

### การทดสอบ Template กับ LINE Bot

**วิธีทดสอบ:**

1. **ใช้ LINE Flex Simulator**
   - URL: https://developers.line.biz/flex-simulator/
   - Copy JSON จาก CodeMirror Editor
   - Paste ลงใน Simulator
   - ดูตัวอย่างผลลัพธ์

2. **ทดสอบกับ LINE Bot จริง**
   ```
   1. แก้ไข template
   2. บันทึก
   3. เปิด LINE OA
   4. ทริกเกอร์ action ที่ใช้ template นั้น (เช่น พิมพ์ "start")
   5. ตรวจสอบ Flex Message ที่ได้รับ
   ```

3. **ตรวจสอบ Usage Count**
   ```
   - กลับมาดูที่ /admin/line-membership-signup/templates
   - ดู badge "Usage Count" ว่าเพิ่มขึ้นหรือไม่
   ```

---

## 🔒 ความปลอดภัยและข้อควรระวัง

### 1. **Default Templates Protection**
- Default templates (is_default = true) **ไม่สามารถลบได้**
- แก้ไขได้ แต่สามารถ reset กลับค่าเริ่มต้น
- แนะนำให้ **duplicate แล้วแก้ไข** แทนการแก้ไขโดยตรง

### 2. **JSON Validation**
- ระบบจะ validate JSON ก่อนบันทึก
- ต้องเป็น JSON ที่ถูกต้องตามโครงสร้าง LINE Flex Message
- ต้องมี field `type` (bubble หรือ carousel)
- Bubble message ต้องมี `body` หรือ `hero`

### 3. **Template Key Uniqueness**
- Template Key ต้องไม่ซ้ำกัน (unique constraint)
- ใช้ a-z, 0-9, _ เท่านั้น
- Template Key **ไม่สามารถแก้ไขได้** หลังสร้าง

### 4. **Variables**
- ระบบไม่ได้ validate ว่าตัวแปรใน JSON ตรงกับ field `variables` หรือไม่
- ต้องตรวจสอบเองว่า `{{variable}}` ใน JSON ตรงกับข้อมูลที่จะส่งเข้ามา

---

## 🧪 การทดสอบระบบ

### Test Cases

#### 1. **Test Create Template**
```
✅ สร้าง template ใหม่ได้
✅ Template key ต้องไม่ซ้ำ
✅ JSON ต้องถูกต้อง
✅ Variables ถูกแปลงเป็น array
✅ Category ถูกบันทึก
```

#### 2. **Test Edit Template**
```
✅ แก้ไข template ได้
✅ Template key ไม่สามารถแก้ไขได้ (readonly)
✅ JSON validation ทำงาน
✅ ข้อมูลถูกบันทึกถูกต้อง
```

#### 3. **Test Reset Template**
```
✅ Reset default template ได้
✅ ข้อมูลถูก restore จาก seeder
✅ Non-default template ไม่สามารถ reset ได้ (API return error 400)
```

#### 4. **Test Duplicate Template**
```
✅ Duplicate template ได้
✅ Template ใหม่มี key ไม่ซ้ำ
✅ Template ใหม่มี is_default = false
✅ Template ใหม่มี original_template_key บอกที่มา
✅ Usage count = 0
```

#### 5. **Test Delete Template**
```
✅ ลบ non-default template ได้
✅ ไม่สามารถลบ default template ได้ (API return error 403)
```

#### 6. **Test Template Usage**
```
✅ Template ถูกใช้งานใน LINE Bot
✅ Variables ถูกแทนค่าถูกต้อง
✅ Usage count เพิ่มขึ้นเมื่อใช้งาน
```

---

## 📊 Database Schema

### Table: `line_signup_templates`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| template_key | string | Template key (unique) |
| template_name | string | ชื่อ template |
| description | text | คำอธิบาย |
| flex_message_json | json | Flex Message JSON |
| variables | json | ตัวแปรที่ใช้ใน template |
| is_active | boolean | สถานะการใช้งาน |
| **is_default** | **boolean** | **⭐ ใหม่: Template ต้นฉบับจาก seeder** |
| **original_template_key** | **string** | **⭐ ใหม่: Template key ต้นฉบับ (สำหรับ duplicate)** |
| **category** | **string** | **⭐ ใหม่: หมวดหมู่ (welcome, earning, etc.)** |
| usage_count | integer | จำนวนครั้งที่ใช้งาน |
| created_at | timestamp | วันที่สร้าง |
| updated_at | timestamp | วันที่แก้ไข |

---

## 📚 เอกสารอ้างอิง

### LINE Flex Message Documentation
- **Flex Message Simulator**: https://developers.line.biz/flex-simulator/
- **Flex Message API**: https://developers.line.biz/en/docs/messaging-api/using-flex-messages/
- **Flex Message Elements**: https://developers.line.biz/en/reference/messaging-api/#flex-message

### Project Documentation
- **LINE Membership Signup README**: `/LINE_MEMBERSHIP_SIGNUP_README.md`
- **V3 Coding Guidelines**: `/.claude/V3_CODING_GUIDELINES.md`
- **Database Guidelines**: `/.claude/DATABASE_GUIDELINES.md`

---

## 🛠️ Troubleshooting

### ปัญหาที่อาจเกิดขึ้น

#### 1. **JSON Validation Error**
```
❌ JSON ไม่ถูกต้อง: Unexpected token
```
**แก้ไข:**
- กดปุ่ม "จัดรูปแบบ" เพื่อ beautify JSON
- ตรวจสอบ syntax: missing comma, bracket, quotes
- ใช้ LINE Flex Simulator ทดสอบ JSON

#### 2. **Template Key Already Exists**
```
❌ The template key has already been taken
```
**แก้ไข:**
- เปลี่ยน template key ให้ไม่ซ้ำ
- ตรวจสอบว่ามี template ที่ใช้ key นี้อยู่แล้วหรือไม่

#### 3. **Cannot Reset Template**
```
❌ Template นี้ไม่สามารถ reset ได้ (ไม่ใช่ default template)
```
**แก้ไข:**
- เฉพาะ default templates (is_default = true) เท่านั้นที่ reset ได้
- Template ที่สร้างเองหรือ duplicate มาไม่สามารถ reset ได้

#### 4. **Cannot Delete Default Template**
```
❌ ไม่สามารถลบ default template ได้
```
**แก้ไข:**
- Default templates ถูกป้องกันไม่ให้ลบ
- หากต้องการปิดการใช้งาน ให้ edit และเอาเครื่องหมายถูกออกจาก "เปิดใช้งาน Template"

---

## ✅ สรุป

ระบบจัดการ LINE Flex Message Templates ได้รับการพัฒนาให้:

✅ **สามารถแก้ไขได้** - แก้ไข JSON ผ่าน CodeMirror Editor พร้อม validation
✅ **สามารถรีเซ็ตได้** - Default templates สามารถ reset กลับค่าเริ่มต้น
✅ **สามารถคัดลอกได้** - Duplicate template เพื่อสร้างเวอร์ชันใหม่
✅ **สามารถสร้างใหม่ได้** - สร้าง template ตั้งแต่ต้นด้วย JSON Editor
✅ **มีความปลอดภัย** - ป้องกันการลบ default templates
✅ **ทำงานกับ LINE Bot ได้** - ใช้งานร่วมกับ signup flow ได้ทันที

**Happy Template Editing! 🎉**

---

**Document Version**: 1.0.0
**Last Updated**: 2025-11-18
**Author**: Development Team
**Contact**: support@thaiprompt.com
