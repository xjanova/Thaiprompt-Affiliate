# OAuth ผ่านผู้ให้บริการสำหรับ Juntra

สัญญา `juntra-social-v1` ใช้ OAuth client, Facebook/Google credentials และ callback URI เดิมทั้งหมด ไม่มีค่า secret ชุดใหม่ และไม่เปลี่ยน SSO ปกติ

## ตรวจช่องทางที่เปิดจริง

`GET /api/oauth/social/providers` เป็น public API ไม่ cache ผลลัพธ์ และไม่ส่ง credentials

```json
{"data":{"flow":"juntra-social-v1","providers":[{"id":"facebook","name":"Facebook"}]}}
```

แสดงเฉพาะ `facebook` หรือ `google` ที่เปิดใช้งานและตั้งค่าครบตาม settings เดียวกับหน้าเข้าสู่ระบบ LINE ไม่อยู่ใน bridge รุ่นนี้ เมื่อ API ไม่พร้อมหรือ marker ไม่ตรง ฝั่ง Juntra ต้องซ่อนปุ่มทั้งหมด

## เริ่มคำขอ

`GET /oauth/social/authorize` รับ:

- `response_type=code`, `client_id`, `redirect_uri`, `scope`, `state` ตาม Passport เดิม
- `provider=facebook|google`
- `social_nonce`: สุ่มใหม่ทุกคำขอ เป็น `[A-Za-z0-9_-]{32,128}` เช่น random 32 bytes แปลงเป็น hex 64 ตัว
- `code_challenge` และ `code_challenge_method=S256` ถ้าใช้ PKCE
- `prompt=consent` ถ้าต้องการบังคับแสดงหน้าอนุญาต

ต้องส่ง `redirect_uri` และ `state` เสมอ Passport ตรวจ client, scope, redirect และ PKCE ก่อนบันทึก flow หรือล้าง session เมื่อผ่านแล้วจึง logout บัญชี Thaiprompt ที่ค้างอยู่ ล้าง session/remember cookie เดิม และไปผู้ให้บริการที่ระบุจริง Facebook ใช้ `auth_type=reauthenticate`; Google ใช้ `prompt=select_account`

Socialite ตรวจ state ของ provider ตามเดิม และ bridge ตรวจ state แยกที่ผูกกับคำขอนี้อีกชั้น Callback ปกติ `/auth/facebook/callback` หรือ `/auth/google/callback` ต้องสำเร็จและผ่าน SocialLoginGuard จึงเก็บหลักฐานผู้ใช้ได้ ไม่รับ proof จาก query หรือ session login ทั่วไป

จากนั้นกลับ `/oauth/authorize` ด้วย OAuth parameters เดิม อายุ flow 15 นาที ก่อนออก code ทั้ง auto approve และ consent ต้องตรวจ provider ยังเปิด, ผู้ใช้ตรงกับ callback และคำขอไม่ถูกสลับ บันทึก provider, SHA-256 ของ nonce และ SHA-256 ของ code บนแถว `oauth_auth_codes` ที่ Passport ออกจริง หลักฐานไม่พึ่ง cache และไม่มี nonce/code plaintext ในฐานข้อมูล

## แลก code

`POST /oauth/token` ใช้ body ตาม authorization-code grant เดิม เพิ่ม:

```text
social_provider=facebook
social_nonce=<ค่าเดียวกับตอนเริ่มคำขอ>
```

token endpoint ถอดรหัส code ด้วยกลไก/กุญแจเดียวกับ Passport เพื่อหาแถวจริง ล็อกแถวใน transaction แล้วตรวจ provider, nonce hash, code hash, user, client, revoked และสถานะ provider ก่อนให้ Passport ตรวจ client secret, redirect_uri, อายุ code และ PKCE ตามปกติ

เมื่อสำเร็จ JSON token response เดิมจะเพิ่ม:

```json
{"social_provider":"facebook","social_nonce":"<ค่าเดียวกับตอนเริ่ม>"}
```

Juntra ต้องเทียบทั้งสองค่ากับ pending request ของตัวเองแบบ constant time ก่อนใช้ token และต้องดึง `/api/user` ได้ ID ที่ไม่ว่าง ค่าเหล่านี้เป็นหลักฐานจาก token endpoint ที่ตรวจแล้ว ไม่ใช่ query hint

- social code ที่ขาดหรือสลับสอง field นี้: `400 invalid_grant`
- ordinary SSO code ที่พยายามอ้าง social fields: `400 invalid_grant`
- code ใช้แล้ว, provider ปิด, binding ผิด: `400 invalid_grant`
- ordinary SSO และ refresh grant ไม่ส่ง social fields และไม่รับ proof เพิ่ม
- ผู้ใช้ยกเลิกหรือ flow ล้มเหลว: กลับเฉพาะ redirect_uri ที่ Passport ตรวจไว้ พร้อม `error=access_denied` และ `state` เดิม
- request เริ่มต้นไม่ถูกต้องหรือ provider ปิด: ตอบ 4xx ภายใน ไม่ redirect ไปปลายทางที่ยังไม่ผ่านการตรวจ

## ตรวจสอบและนำขึ้นระบบ

ต้องรัน migration `2026_11_12_000001_add_social_binding_to_oauth_auth_codes.php` ก่อนเปิดใช้ bridge ไม่ต้อง provision OAuth client หรือแก้ credentials เพิ่ม

```sh
php vendor/bin/phpunit tests/Feature/FreshSocialOAuthTest.php tests/Feature/PassportRouteLockdownTest.php tests/Feature/OAuthKycStatusTest.php
```

ชุดทดสอบใหม่ใช้ SQLite ในหน่วยความจำ, Passport จริงและ Socialite จริง จำลอง HTTP ของ provider กับการหาบัญชีเท่านั้น ไม่ใช้บัญชีหรือ credentials production ปิดเฉพาะ middleware ภาษาใน fixture; คง session/auth/CSRF middleware และ guard ความปลอดภัยไว้

ครอบคลุม session เก่า, callback state, provider mismatch, nonce mismatch, code replay, provider ปิด, consent approve/deny, auth token ของ consent, account swap, อายุ flow, PKCE และ ordinary SSO ยังต้องทดสอบ provider จริงหลังผู้ดูแลอนุมัติ merge/deploy แยกต่างหาก
