# Cloudflare Configuration for LINE Webhook

เนื่องจาก LINE webhook อาจถูก Cloudflare security features block ทำให้เกิด error 419 จึงต้องตั้งค่า Cloudflare เพื่อให้ webhook ทำงานได้

## ✅ วิธีแก้ไข (เลือกวิธีใดวิธีหนึ่ง)

### วิธีที่ 1: Page Rule (แนะนำ - ง่ายที่สุด)

1. เข้า **Cloudflare Dashboard**
2. เลือก domain ของคุณ
3. ไปที่ **Rules** > **Page Rules**
4. คลิก **Create Page Rule**

**ตั้งค่าดังนี้:**
```
URL: yourdomain.com/api/webhook/line
```

**Settings:**
- ✅ Security Level: `Essentially Off`
- ✅ Browser Integrity Check: `Off`
- ✅ WAF (Web Application Firewall): `Off`
- ✅ Cache Level: `Bypass`

5. คลิก **Save and Deploy**

---

### วิธีที่ 2: WAF Custom Rule

1. เข้า **Cloudflare Dashboard**
2. ไปที่ **Security** > **WAF** > **Custom rules**
3. คลิก **Create rule**

**Rule Configuration:**
```
Rule name: Allow LINE Webhook

Expression:
(http.request.uri.path eq "/api/webhook/line")

Action: Skip
  - ✅ Skip all remaining custom rules
  - ✅ Skip all managed rules
  - ✅ Skip Bot Fight Mode
  - ✅ Skip User Agent Blocking
```

4. คลิก **Deploy**

---

### วิธีที่ 3: IP Whitelist (ปลอดภัยที่สุด)

Whitelist IP addresses ของ LINE Platform:

1. เข้า **Cloudflare Dashboard**
2. ไปที่ **Security** > **WAF** > **Tools**
3. ไปที่ส่วน **IP Access Rules**
4. คลิก **Add rule**

**เพิ่ม IP Range:**
```
IP Address: 147.92.128.0/17
Action: Allow
Note: LINE Platform
```

5. คลิก **Add**

หรือสร้าง **Firewall Rule:**
```
Rule name: Allow LINE Platform IPs

Expression:
(ip.src in {147.92.128.0/17} and http.request.uri.path contains "/webhook/line")

Action: Allow
```

---

### วิธีที่ 4: Configuration Rules (Cloudflare ใหม่)

ถ้าใช้ Cloudflare UI ใหม่:

1. เข้า **Security** > **Settings**
2. ไปที่ **Configuration Rules**
3. คลิก **Create rule**

**Rule Configuration:**
```
Rule name: Disable security for LINE webhook

When incoming requests match:
  Field: URI Path
  Operator: equals
  Value: /api/webhook/line

Then:
  - Security Level: Off
  - WAF: Off
  - Browser Integrity Check: Off
```

4. **Deploy**

---

## 📝 ขั้นตอนหลังตั้งค่า Cloudflare

### 1. อัพเดท Webhook URL ใน LINE Developers Console

เข้า [LINE Developers Console](https://developers.line.biz/console/)

**Webhook URL ใหม่:**
```
https://yourdomain.com/api/webhook/line
```

⚠️ **สำคัญ:** URL เปลี่ยนจาก `/webhook/line` เป็น `/api/webhook/line`

### 2. Verify Webhook

กด **Verify** ที่ LINE Developers Console

ควรได้รับข้อความ:
```
✅ Success
```

### 3. Enable Webhook

เปิดใช้งาน webhook:
```
✅ Use webhook: ON
```

---

## 🧪 ทดสอบการทำงาน

### 1. Test Connection ในระบบ

1. เข้า **Admin Panel** > **LINE OA Settings**
2. คลิกปุ่ม **Test Connection**
3. ตรวจสอบผลลัพธ์:
   - ✅ Settings configured
   - ✅ Credentials configured
   - ✅ Messaging API connection
   - ✅ LINE Login configuration

### 2. Test Message

1. คลิกปุ่ม **Test Message**
2. เลือก user จากตาราง หรือ กรอก LINE User ID แบบ manual
3. กรอกข้อความทดสอบ
4. คลิก **Send Message**

### 3. ทดสอบจาก LINE App

1. เพิ่มเพื่อน LINE Official Account
2. ส่งข้อความไปยัง OA
3. ตรวจสอบว่าระบบตอบกลับหรือไม่

---

## 🔍 Troubleshooting

### ยังเกิด Error 419 อยู่

**ตรวจสอบ:**
1. ✅ Page Rule ถูกสร้างและ active แล้วหรือไม่
2. ✅ URL pattern ตรงกับ `/api/webhook/line` หรือไม่
3. ✅ Cloudflare cache ถูก purge แล้วหรือไม่

**แก้ไข:**
```bash
# ใน Cloudflare Dashboard
Caching > Configuration > Purge Everything
```

### Error 403 Forbidden

**สาเหตุ:** Cloudflare firewall rule อาจยัง block อยู่

**แก้ไข:**
1. ตรวจสอบ **Security Events** ว่ามี request ถูก block หรือไม่
2. ปิด **Bot Fight Mode** สำหรับ webhook path
3. ปิด **Challenge Passage** สำหรับ webhook path

### Error 522 Connection Timed Out

**สาเหตุ:** Origin server ตอบช้าหรือไม่ตอบ

**แก้ไข:**
1. ตรวจสอบว่า server ทำงานปกติ
2. เพิ่ม timeout ใน Cloudflare (Enterprise plan)
3. Optimize webhook handler ให้ตอบเร็วขึ้น

---

## 📊 LINE Platform IP Ranges

สำหรับ whitelist (อ้างอิงจาก LINE Documentation):

```
147.92.128.0/17
```

**หมายเหตุ:** IP ranges อาจมีการเปลี่ยนแปลง ตรวจสอบล่าสุดที่:
https://developers.line.biz/en/docs/messaging-api/receiving-messages/

---

## 🎯 Best Practices

1. **ใช้ Page Rule** - ง่ายที่สุดและมีประสิทธิภาพ
2. **Whitelist IP** - ปลอดภัยที่สุด แต่ต้องอัพเดทเมื่อ LINE เปลี่ยน IP
3. **Monitor Security Events** - ตรวจสอบ Cloudflare logs เป็นประจำ
4. **Test หลังแก้ไข** - ทดสอบ webhook ทุกครั้งหลังเปลี่ยนแปลงการตั้งค่า

---

## 📚 เอกสารอ้างอิง

- [LINE Messaging API - Webhook](https://developers.line.biz/en/docs/messaging-api/receiving-messages/)
- [Cloudflare Page Rules](https://developers.cloudflare.com/rules/page-rules/)
- [Cloudflare WAF Custom Rules](https://developers.cloudflare.com/waf/custom-rules/)

---

**อัพเดทล่าสุด:** 2025-01-XX
