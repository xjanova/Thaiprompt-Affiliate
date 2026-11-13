# Facebook Graph API v27.0

ตรวจเอกสาร Meta โดยตรงเมื่อ 13 พฤศจิกายน 2026: เวอร์ชันล่าสุดคือ **v27.0** เปิดตัว 4 พฤศจิกายน 2026

- [Graph API changelog](https://developers.facebook.com/documentation/development/graph-api/changelog)
- [v27.0 changelog](https://developers.facebook.com/documentation/development/graph-api/changelog/version27.0)

## จุดใช้งาน

`App\Support\FacebookGraphApi::VERSION` กำหนดเวอร์ชันร่วมของ Messenger, คอมเมนต์/ไลค์, Page OAuth, Facebook Login, token/debug-token, การโพสต์รูป/ข้อความ/รีโพสต์ และการลบโพสต์

Facebook Login ตั้ง `usingGraphVersion()` ทั้งขา redirect และ callback ของ Socialite พร้อมขอเฉพาะ `id,name,email` ที่ระบบใช้ ส่วนหน้าแอดมินแสดงเวอร์ชันกลางและไม่อนุญาตให้บันทึกเวอร์ชันเก่า

Migration `2026_11_13_000001_upgrade_facebook_graph_api_version` ปรับค่าแถวเดิม ค่าเริ่มต้นของคอลัมน์ และล้างแคช Facebook OAuth การ rollback คืนเฉพาะค่าเริ่มต้นของคอลัมน์ โดยไม่ลดเวอร์ชันแถวเดิมไปเป็นเวอร์ชันหมดอายุ

## Mobile app

แอป React Native/Expo ใน `thaiprompt/` ไม่เรียก Graph API หรือใช้ Facebook SDK โดยตรง ปุ่ม Facebook เรียก `/api/v1/auth/mobile/init` พร้อม `provider=facebook` แล้วเปิดเว็บผ่าน PKCE → `/mobile-login` → `/auth/facebook` → callback เดิมของเว็บ → `thaiprompt://auth?code=...&state=...` → `/api/v1/auth/mobile/exchange`

ทั้งแอปและเว็บจึงใช้ `FacebookLoginController` และ `FacebookGraphApi::VERSION` ตัวเดียวกัน การเปลี่ยน Graph API เป็น v27.0 มีผลหลัง deploy backend โดยไม่ต้องออก APK/IPA ใหม่เพียงเพื่อเปลี่ยนเวอร์ชัน Graph API

รูปโปรไฟล์จาก `graph.facebook.com` ตรวจ HTTPS และชื่อ host ใน `thaiprompt/utils/linking.ts` โดยไม่ผูกกับเลขเวอร์ชัน จึงรองรับ URL v27.0, URL ไม่มีเวอร์ชัน และรูปเก่าที่เก็บไว้ เทสต์ `kycGate.test.ts` ตรวจทั้งสามรูปแบบ

ก่อนเปิดใช้จริง ให้ทดสอบ Facebook Login จาก Android/iOS จนแลก code เป็น token สำเร็จ รวมถึงยกเลิกล็อกอินและเปิดแอปกลับผ่าน deep link การ export JavaScript bundle และเทสต์จำลองยังไม่ยืนยันพฤติกรรมเบราว์เซอร์หรือ deep link บนเครื่องจริง

## ผลกระทบที่ตรวจ

ตรวจ changelog v22.0–v27.0 เทียบจุดเรียกใน repository นี้ ไม่พบการเรียก Page ratings, Commerce Order Management, Rights Manager, legacy Page fields, `date_format`, root `?ids=`, video insights ที่ถูกยกเลิก หรือ Catalog `/batch` ที่ต้องย้าย endpoint

v27.0 เพิ่ม `is_ai_generated` แบบ optional ตอนแก้ไขโพสต์ หากไม่ส่ง ค่าเดิมยังอยู่ การอัปเกรดนี้จึงไม่เปลี่ยนการเปิดเผยเนื้อหา AI ของโพสต์

## ก่อนเปิดใช้บน production

1. Deploy โค้ดและรัน migration ตามขั้นตอนปกติ
2. เคลียร์ config cache และ restart queue workers เพื่อให้โปรเซสเก่าโหลดเวอร์ชันใหม่
3. ใน Meta App Dashboard ตรวจ Graph API version ของ Webhooks และค่าเริ่มต้นของแอปเป็น v27.0 แยกต่างหาก โค้ดนี้เปลี่ยนการตั้งค่าบน Meta ไม่ได้
4. ใช้เพจทดสอบยืนยัน Facebook Login, อ่านข้อมูลเพจ, ส่ง Messenger, ตอบ/ไลค์คอมเมนต์ และสร้าง/ลบโพสต์รูปกับข้อความ

การทดสอบ HTTP จำลองตรวจ URL/payload และพฤติกรรมแอป ไม่ยืนยันว่าสิทธิ์ App Review, page token หรือ Webhook subscription ของ Meta ผ่านจริง ยังต้องทดสอบกับบัญชี/เพจทดสอบก่อนเปิดใช้จริง
