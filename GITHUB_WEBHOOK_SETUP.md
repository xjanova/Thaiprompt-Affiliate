# 🔔 GitHub Webhook Setup Guide

การตั้งค่า GitHub Webhook เพื่อให้ระบบอัพเดทเห็นเวอร์ชั่นใหม่ทันทีโดยไม่ต้องรอ cache หมดอายุ

---

## 📋 ภาพรวม

เมื่อมี Release ใหม่บน GitHub:
1. GitHub จะส่ง webhook notification มาที่ระบบ
2. ระบบรับ notification และ clear cache อัตโนมัติ
3. ผู้ใช้เห็นเวอร์ชั่นใหม่ทันที (ไม่ต้องรอ 5 นาที)

---

## 🚀 ขั้นตอนการตั้งค่า

### Step 1: สร้าง Webhook Secret

```bash
# Generate secure random secret
openssl rand -base64 32
```

คัดลอกผลลัพธ์ (ตัวอย่าง: `abc123...xyz789`)

### Step 2: เพิ่ม Secret ใน .env

เพิ่มบรรทัดนี้ใน `.env`:

```env
GITHUB_WEBHOOK_SECRET=abc123...xyz789
```

(ใช้ secret ที่ generate จาก Step 1)

### Step 3: ตั้งค่า GitHub Webhook

1. ไปที่ GitHub Repository → **Settings** → **Webhooks** → **Add webhook**

2. กรอกข้อมูล:

   **Payload URL:**
   ```
   https://your-domain.com/api/webhooks/github/release
   ```
   (เปลี่ยน `your-domain.com` เป็น domain จริง)

   **Content type:**
   ```
   application/json
   ```

   **Secret:**
   ```
   abc123...xyz789
   ```
   (ใช้ secret เดียวกับใน .env)

   **SSL verification:**
   ```
   ✓ Enable SSL verification (recommended)
   ```

   **Which events would you like to trigger this webhook?**
   ```
   ○ Let me select individual events:
   ✓ Releases
   ```

   **Active:**
   ```
   ✓ Active
   ```

3. คลิก **Add webhook**

### Step 4: ทดสอบ Webhook

#### วิธีที่ 1: ทดสอบจาก GitHub UI

1. ไปที่ **Settings** → **Webhooks**
2. คลิกที่ webhook ที่สร้าง
3. เลื่อนลงไปที่ **Recent Deliveries**
4. คลิก **Redeliver** เพื่อส่ง test payload

#### วิธีที่ 2: สร้าง Test Release

```bash
# สร้าง tag ใหม่
git tag v2.127.7-test
git push origin v2.127.7-test

# สร้าง release ผ่าน GitHub CLI
gh release create v2.127.7-test --title "Test Release" --notes "Testing webhook"

# ลบ release และ tag ทดสอบ
gh release delete v2.127.7-test -y
git push --delete origin v2.127.7-test
git tag -d v2.127.7-test
```

#### วิธีที่ 3: ทดสอบด้วย curl

```bash
# ต้องมี payload signature ที่ถูกต้อง
curl -X POST https://your-domain.com/api/webhooks/github/release \
  -H "Content-Type: application/json" \
  -H "X-Hub-Signature-256: sha256=..." \
  -d '{
    "action": "published",
    "release": {
      "tag_name": "v2.127.7",
      "name": "Release v2.127.7",
      "draft": false,
      "prerelease": false
    }
  }'
```

### Step 5: ตรวจสอบ Logs

ดู logs เพื่อยืนยันว่า webhook ทำงาน:

```bash
tail -f storage/logs/laravel.log | grep "GitHub"
```

ควรเห็น:
```
[2025-11-11 10:00:00] local.INFO: GitHub release webhook received {"action":"published","release_tag":"v2.127.7"}
[2025-11-11 10:00:00] local.INFO: Version caches cleared after new release {"tag":"v2.127.7","name":"Release v2.127.7","prerelease":false}
```

---

## 🔧 Alternative: Manual Cache Clear

ถ้าไม่ต้องการตั้งค่า webhook สามารถ clear cache manual ได้:

### วิธีที่ 1: ผ่าน Admin UI

1. เข้า Admin Panel
2. ไปที่ **System Updates**
3. คลิกปุ่ม **🔄 Clear Cache**

### วิธีที่ 2: ผ่าน API (สำหรับ Super Admin)

```bash
curl -X POST https://your-domain.com/admin/updates/clear-cache \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json"
```

### วิธีที่ 3: ผ่าน Command Line

```bash
php artisan app:check-update --clear-cache
```

---

## 📊 Response Examples

### Success Response

```json
{
  "success": true,
  "message": "Version caches cleared successfully",
  "release": {
    "tag": "v2.127.7",
    "name": "Release v2.127.7",
    "prerelease": false
  }
}
```

### Draft Release (Ignored)

```json
{
  "success": true,
  "message": "Draft release ignored"
}
```

### Invalid Signature

```json
{
  "success": false,
  "message": "Invalid signature"
}
```

---

## ⚙️ Configuration

### Cache TTL

แก้ไข `config/version.php`:

```php
'update' => [
    'cache_ttl' => env('VERSION_CHECK_CACHE_TTL', 300), // 5 minutes
    // หรือ 60 = 1 minute
    // หรือ 600 = 10 minutes
],
```

หรือใน `.env`:

```env
VERSION_CHECK_CACHE_TTL=300
```

### Disable Webhook Verification (Development Only)

```env
# ไม่แนะนำใน production!
GITHUB_WEBHOOK_SECRET=
```

---

## 🐛 Troubleshooting

### ปัญหา: Webhook ไม่ทำงาน

**เช็ค:**
1. URL ถูกต้องไหม?
   ```
   https://your-domain.com/api/webhooks/github/release
   ```

2. SSL ทำงานไหม?
   ```bash
   curl https://your-domain.com/api/webhooks/github/release
   ```

3. Secret ตรงกันไหม?
   - ใน `.env`
   - ใน GitHub webhook settings

### ปัญหา: 401 Unauthorized

**สาเหตุ:** Signature ไม่ตรงกัน

**แก้ไข:**
1. ตรวจสอบ `GITHUB_WEBHOOK_SECRET` ใน `.env`
2. Update secret ใน GitHub webhook settings
3. ลอง redeliver webhook

### ปัญหา: ยังไม่เห็นเวอร์ชั่นใหม่

**แก้ไข:**
```bash
# Clear cache manual
php artisan cache:clear
php artisan app:check-update --clear-cache
```

---

## 📝 Webhook Events

ระบบจะ respond กับ events เหล่านี้:

| Event | Action | Cache Clear? |
|-------|--------|-------------|
| Release Published | ✅ | Yes |
| Release Draft | ❌ | No |
| Release Edited | ❌ | No |
| Release Deleted | ❌ | No |

---

## 🔐 Security

### Best Practices

1. ✅ **Always use HTTPS** - ป้องกัน man-in-the-middle attacks
2. ✅ **Strong secret** - ใช้ secret อย่างน้อย 32 characters
3. ✅ **Verify signatures** - ตรวจสอบ `X-Hub-Signature-256` header
4. ✅ **Rate limiting** - จำกัดจำนวน requests (optional)
5. ✅ **Log monitoring** - ตรวจสอบ logs เป็นประจำ

### Don'ts

1. ❌ **Don't hardcode secrets** - ใช้ `.env` เท่านั้น
2. ❌ **Don't disable SSL** - เว้นแต่ development
3. ❌ **Don't expose webhook URL** - ใช้เฉพาะกับ GitHub
4. ❌ **Don't skip signature verification** - ยกเว้น development

---

## 📚 References

- [GitHub Webhooks Documentation](https://docs.github.com/en/developers/webhooks-and-events/webhooks)
- [Securing your webhooks](https://docs.github.com/en/developers/webhooks-and-events/webhooks/securing-your-webhooks)
- [Webhook event payloads](https://docs.github.com/en/developers/webhooks-and-events/webhooks/webhook-events-and-payloads#release)

---

## ✅ Checklist

- [ ] สร้าง webhook secret
- [ ] เพิ่ม secret ใน `.env`
- [ ] สร้าง GitHub webhook
- [ ] เลือก "Releases" event
- [ ] Enable webhook
- [ ] ทดสอบ webhook
- [ ] ตรวจสอบ logs
- [ ] ทดสอบโดยสร้าง release จริง

---

**Last Updated:** 2025-11-11
**Version:** 1.0.0
