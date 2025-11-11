# 🔐 การตั้งค่า GitHub Token ผ่าน Admin Settings

ตอนนี้คุณสามารถตั้งค่า GitHub Token ผ่านหน้า Admin Settings ได้โดยตรง ไม่ต้องแก้ไขไฟล์ `.env` อีกต่อไป!

---

## ✅ วิธีตั้งค่าผ่าน Admin Panel

### 1. เข้าหน้า Settings

ไปที่: **Admin Panel → Updates → Settings**

หรือเข้าผ่าน URL:
```
https://yourdomain.com/admin/updates/settings
```

### 2. กรอก GitHub Token

**ฟิลด์ที่มีให้ตั้งค่า:**
- ✅ **GitHub Token** - Personal Access Token จาก GitHub
- ✅ **Auto Check** - เช็คอัพเดทอัตโนมัติ
- ✅ **Auto Update** - อัพเดทอัตโนมัติ (ระวัง!)
- ✅ **Backup Before Update** - สำรองก่อนอัพเดท
- ✅ **Notification Email** - อีเมลรับการแจ้งเตือน

### 3. บันทึกการตั้งค่า

กด **"บันทึก"** และระบบจะ:
- ✅ Encrypt Token ก่อนบันทึกลง database (ปลอดภัย)
- ✅ Clear cache อัตโนมัติ
- ✅ ใช้งาน Token ใหม่ได้ทันที

---

## 🔑 วิธีสร้าง GitHub Personal Access Token

### ขั้นตอนที่ 1: เข้า GitHub Settings

1. ไปที่ https://github.com/settings/tokens
2. คลิก **"Generate new token (classic)"**

### ขั้นตอนที่ 2: ตั้งค่า Token

**Note (ชื่อ Token):**
```
Thaiprompt-Affiliate Update System
```

**Expiration:**
- เลือก **"No expiration"** (แนะนำ)
- หรือ **"90 days"** (ต้องต่ออายุทุก 90 วัน)

**Select scopes:**
- ✅ **`repo`** (Full control of private repositories)
  - ถ้า repository เป็น public ไม่จำเป็นต้องเลือก
  - ถ้า repository เป็น private **ต้องเลือก**

### ขั้นตอนที่ 3: คัดลอก Token

1. คลิก **"Generate token"**
2. **คัดลอก token** (จะแสดงครั้งเดียว!)
   - รูปแบบ: `ghp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx`
3. เก็บไว้ในที่ปลอดภัย

---

## 🔒 ความปลอดภัย

### Token ถูก Encrypt ก่อนบันทึก

```php
// ✅ ระบบจะ encrypt ก่อนบันทึกลง database
$encryptedToken = Crypt::encryptString($token);
Setting::set('update_github_token', $encryptedToken);
```

### ไม่แสดงค่าจริงใน UI

```json
{
  "github_token": "***xxxx",  // แสดงแค่ 4 ตัวท้าย
  "has_github_token": true
}
```

### ตรวจสอบรูปแบบ Token

ระบบจะตรวจสอบว่า Token ถูกต้องหรือไม่:
- ✅ ต้องขึ้นต้นด้วย `ghp_`, `gho_`, `ghs_`, หรือ `ghu_`
- ✅ ความยาวอย่างน้อย 40 ตัวอักษร

---

## 📡 API Endpoints

### 1. ดึงการตั้งค่าปัจจุบัน

```bash
GET /admin/updates/settings
```

**Response:**
```json
{
  "success": true,
  "settings": {
    "auto_check": false,
    "auto_update": false,
    "backup_before_update": true,
    "notification_email": "admin@example.com",
    "github_token": "***xxxx",
    "has_github_token": true
  }
}
```

### 2. บันทึกการตั้งค่า

```bash
POST /admin/updates/settings
Content-Type: application/json

{
  "github_token": "ghp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
  "auto_check": true,
  "backup_before_update": true,
  "notification_email": "admin@example.com"
}
```

**Response (สำเร็จ):**
```json
{
  "success": true,
  "message": "บันทึกการตั้งค่าสำเร็จ"
}
```

**Response (Token ไม่ถูกต้อง):**
```json
{
  "success": false,
  "message": "รูปแบบ GitHub Token ไม่ถูกต้อง (ต้องขึ้นต้นด้วย ghp_, gho_, ghs_, หรือ ghu_)"
}
```

---

## 🧪 ทดสอบว่า Token ทำงาน

### 1. ทดสอบผ่าน Test Connection

```bash
GET /admin/updates/test-connection
```

**Response:**
```json
{
  "success": true,
  "results": {
    "checks": [
      {
        "check": "GitHub API Reachability",
        "status": "passed",
        "message": "Repository accessible (response time: 150ms)"
      },
      {
        "check": "Latest Release",
        "status": "passed",
        "version": "2.127.14"
      }
    ]
  },
  "debug_info": {
    "api_url": "https://api.github.com/repos/...",
    "has_token": true,
    "php_version": "8.2.0"
  }
}
```

### 2. ทดสอบ Check for Updates

```bash
GET /admin/updates/check
```

ถ้ามี Token:
- ✅ เข้าถึง private repositories ได้
- ✅ ไม่โดน rate limit (60 requests/hour → 5000 requests/hour)

---

## 🔄 การทำงานของระบบ

### Priority การอ่าน Token:

1. **Database (Admin Settings)** - อันดับแรก
   ```php
   $token = Setting::get('update_github_token'); // Encrypted
   $decrypted = Crypt::decryptString($token);
   ```

2. **Environment (.env)** - Fallback
   ```php
   $token = env('GITHUB_TOKEN');
   ```

### Auto Clear Cache:

เมื่อบันทึกการตั้งค่า ระบบจะ clear cache อัตโนมัติ:
```php
$this->versionService->clearCache();
Cache::forget('available_updates');
```

ทำให้ Token ใหม่ถูกใช้งานทันที!

---

## ❌ การลบ Token

### วิธีลบ Token:

**ส่ง empty string:**
```json
{
  "github_token": ""
}
```

**หรือส่ง null:**
```json
{
  "github_token": null
}
```

ระบบจะลบ Token ออกจาก database และกลับไปใช้ `.env`

---

## 🆚 เปรียบเทียบ: Database vs .env

| Feature | Database (Admin Settings) | .env File |
|---------|--------------------------|-----------|
| **ง่ายต่อการใช้** | ✅ แก้ผ่าน UI | ❌ ต้องแก้ไฟล์ |
| **ปลอดภัย** | ✅ Encrypted | ⚠️ Plain text |
| **Auto Clear Cache** | ✅ อัตโนมัติ | ❌ ต้องทำเอง |
| **Access Control** | ✅ ผ่าน Admin Auth | ❌ ต้องเข้า server |
| **Audit Log** | ✅ มี log | ❌ ไม่มี |
| **Priority** | 🥇 อันดับ 1 | 🥈 อันดับ 2 |

---

## 📝 ตัวอย่างการใช้งาน

### ตัวอย่างที่ 1: ตั้งค่า Token ครั้งแรก

```bash
# 1. สร้าง Token บน GitHub
# 2. เข้า Admin Panel
# 3. ไปที่ Updates → Settings
# 4. วาง Token ในฟิลด์ GitHub Token
# 5. กด "บันทึก"

# 6. ทดสอบ
curl https://yourdomain.com/admin/updates/test-connection
```

### ตัวอย่างที่ 2: เปลี่ยน Token

```bash
# Token เก่าหมดอายุ? เปลี่ยนง่ายๆ:

# 1. สร้าง Token ใหม่บน GitHub
# 2. ไปที่ Updates → Settings
# 3. แทนที่ Token เก่าด้วย Token ใหม่
# 4. กด "บันทึก"

# ✅ ระบบใช้ Token ใหม่ทันที (auto clear cache)
```

### ตัวอย่างที่ 3: ลบ Token

```bash
# ไม่ต้องการใช้ Token แล้ว?

# 1. ไปที่ Updates → Settings
# 2. ลบค่าในฟิลด์ GitHub Token (เว้นว่าง)
# 3. กด "บันทึก"

# ✅ กลับไปใช้ค่าจาก .env (ถ้ามี)
```

---

## 🔧 Troubleshooting

### ปัญหา: ยังได้ 404 แม้มี Token

**สาเหตุ:** Repository ยังไม่มี releases

**วิธีแก้:**
1. สร้าง Release บน GitHub (อ่าน `CREATE_RELEASE.md`)
2. หรือใช้ GitHub Actions

### ปัญหา: Token ไม่ทำงาน

**ตรวจสอบ:**
```bash
# 1. ดูว่า Token ถูกบันทึกหรือไม่
GET /admin/updates/settings

# 2. ทดสอบ connection
GET /admin/updates/test-connection

# 3. ดู logs
tail -f storage/logs/laravel.log | grep -i github
```

### ปัญหา: Decrypt failed

**สาเหตุ:** APP_KEY เปลี่ยน

**วิธีแก้:**
1. ลบ Token เก่า
2. ตั้ง Token ใหม่

---

## 🎯 Best Practices

1. ✅ **ใช้ Database แทน .env** - ง่ายกว่าและปลอดภัยกว่า
2. ✅ **ตั้งชื่อ Token ให้ชัดเจน** - เช่น "Thaiprompt Update System"
3. ✅ **เลือก No expiration** - ไม่ต้องต่ออายุบ่อยๆ
4. ✅ **Scope เฉพาะที่จำเป็น** - `repo` สำหรับ private repo เท่านั้น
5. ✅ **Test หลังตั้งค่า** - ใช้ `/test-connection` ทุกครั้ง
6. ⚠️ **อย่าแชร์ Token** - เป็นความลับ!

---

## 📚 เอกสารเพิ่มเติม

- [CREATE_RELEASE.md](CREATE_RELEASE.md) - วิธีสร้าง GitHub Release
- [GitHub PAT Documentation](https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/managing-your-personal-access-tokens)
- [Laravel Encryption](https://laravel.com/docs/encryption)

---

**หมายเหตุ:** Token จะถูก encrypt ด้วย Laravel's encryption ดังนั้นห้ามเปลี่ยน `APP_KEY` หลังตั้งค่า Token ไปแล้ว!
