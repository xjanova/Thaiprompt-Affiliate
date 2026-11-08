# 🗄️ สำรองข้อมูลขึ้น Cloudflare R2 (`php artisan backup:r2`)

> เพิ่ม 2026-11-08 — เจ้าของสั่ง: ฐานข้อมูลทุกวัน 02:00 เก็บ 7 วันเขียนทับวน / ทั้งเว็บวันที่ 10 และ 25 เก็บเดือนเดียว
> และ "เก็บที่ R2 ต้องไม่ให้คนอื่นเข้าถึงไฟล์ได้"

## ทำอะไร เมื่อไหร่

| งาน | เวลา | ไฟล์บน R2 (ใต้ `main.thaiprompt.online/`) | ขนาด (วัด 2026-11-08) |
|---|---|---|---|
| ฐานข้อมูลทั้งก้อน | ทุกวัน 02:00 | `db/db-1-mon.sql.zst.tpbk` … `db/db-7-sun.sql.zst.tpbk` (7 ช่อง) | ~245 MB/ไฟล์ · ใช้ 35 วิ |
| ทั้งเว็บ (ไฟล์ + ฐานข้อมูล ณ วันนั้น) | วันที่ 10, 25 เวลา 02:00 | `site/day10/…`, `site/day25/…` (2 ช่อง) | ~1.8 GB/ชุด · ใช้ ~2 นาที |

- **เขียนทับวนเอง** — ไม่มีโค้ดลบ: วันจันทร์หน้าทับไฟล์วันจันทร์นี้ ⇒ มีของ 7 วันล่าสุดเสมอ
- **ชุดเก่าไม่หายถ้ารอบใหม่ล้ม** — R2 แทนที่ไฟล์เดิมเมื่ออัปโหลดใหม่ขึ้นครบเท่านั้น
- พื้นที่รวม ~5.4 GB (7 × 0.25 + 2 × 1.8) — อยู่ในโควตาฟรี 10 GB ของ R2
- ล้มเหลว → **Telegram แจ้งแอดมิน** (บอทแจ้งเตือน ไม่ใช้โควตา LINE push)

ไฟล์เว็บเก็บ: โค้ด, `.env`, `storage/app` (รูปที่ผู้ใช้อัปโหลด) และโฟลเดอร์ส่วนตัวนอก git tree ที่ deploy.sh ย้ายไป
(`fortune_data` สลิป, `ekyc_data`, `profile_photo_data`, `kyc_private_data`, `rider_data`, `rider_job_data` — หาเองจาก symlink ใน `storage/app`)
**ไม่เก็บ:** `vendor`, `node_modules`, `.git`, `storage/logs`, cache/session/view, `backups/` ของ deploy.sh (ติดตั้งใหม่ได้ / ไม่มีค่า)

## 🔐 ความเป็นส่วนตัว — 2 ชั้น

1. **bucket ส่วนตัว** — R2 bucket ปิดสาธารณะเป็นค่าเริ่มต้น ห้ามเปิด Public access / r2.dev / custom domain ให้ bucket นี้
   token ที่เซิร์ฟเวอร์ใช้มีสิทธิ์แค่ bucket นี้
2. **เข้ารหัสทุกไฟล์ก่อนออกจากเซิร์ฟเวอร์** — ต่อให้ไฟล์หลุด (token รั่ว / บัญชี Cloudflare โดนแฮก) ก็เปิดไม่ได้
   - เซิร์ฟเวอร์ถือแค่ **แม่กุญแจ** (`backup-public.pem`) — ล็อกได้ เปิดไม่ได้
   - **ลูกกุญแจ** (`backup-private.pem`) อยู่กับเจ้าของคนเดียว **ไม่เคยขึ้นเซิร์ฟเวอร์**
   - ลายนิ้วมือคู่กุญแจปัจจุบัน: `e9bb08152fc54157`

### ⚠️ ลูกกุญแจ — ต้องเก็บ 2 ที่ ห้ามหาย

ทำลูกกุญแจหาย = ไฟล์สำรอง **ทุกไฟล์** เปิดไม่ได้อีกเลย (ไม่มีใครกู้ให้ได้ รวมถึง Cloudflare)

1. ตัวจัดการรหัสผ่าน (Bitwarden / 1Password / Google Password Manager → โน้ตปลอดภัย วางเนื้อไฟล์ทั้งหมด)
2. สำเนาออฟไลน์ (USB ที่เก็บแยก หรือพิมพ์ลงกระดาษ)

**ห้าม** เก็บลูกกุญแจไว้บนเซิร์ฟเวอร์ ใน git หรือใน bucket R2 เดียวกัน

## ตั้งค่าครั้งแรก (เจ้าของทำ)

1. Cloudflare → R2 → **Create bucket** ชื่อ `thaiprompt-backup` (ไม่ต้องเปิด Public access)
2. R2 → **Manage API tokens** → Create → สิทธิ์ **Object Read & Write** → เลือก **เฉพาะ bucket `thaiprompt-backup`**
3. ใส่ใน `.env` บน prod:
   ```
   BACKUP_R2_ENABLED=true
   BACKUP_R2_ACCOUNT_ID=<Account ID>
   BACKUP_R2_ACCESS_KEY_ID=<Access Key ID>
   BACKUP_R2_SECRET_ACCESS_KEY=<Secret Access Key>
   BACKUP_R2_BUCKET=thaiprompt-backup
   ```
4. `php artisan config:clear` แล้ว `php artisan backup:r2 check` → ต้องขึ้น ✅ และลายนิ้วมือ `e9bb08152fc54157`
5. (แนะนำ) รันรอบแรกเอง: `php artisan backup:r2 db` แล้ว `php artisan backup:r2 list`

แม่กุญแจอยู่ที่ `/home/admin/domains/main.thaiprompt.online/backup_key_data/backup-public.pem`
(deploy.sh symlink เป็น `storage/app/backup-key` ให้เอง — รอด `git clean -fdx`)

## คำสั่ง

```bash
php artisan backup:r2 check             # ทดสอบ R2 + แม่กุญแจ
php artisan backup:r2 list              # ไฟล์บน R2 + ผลรอบล่าสุด
php artisan backup:r2 db                # สำรองฐานข้อมูลเดี๋ยวนี้
php artisan backup:r2 site              # สำรองทั้งเว็บเดี๋ยวนี้ (วันที่ 1-17 → day10, 18-31 → day25)
php artisan backup:r2 site --slot=manual   # ลงช่องแยก ไม่ทับรอบอัตโนมัติ (อย่าลืมลบเองใน R2)
php artisan backup:r2 db --local        # ทดสอบสร้างไฟล์ ไม่อัปโหลด
```

## กู้คืน

ใช้ได้แม้เซิร์ฟเวอร์พังทั้งเครื่อง — ต้องมีแค่ `bash`, `openssl`, `zstd` (`apt install zstd`)
และสคริปต์ [`scripts/backup/decrypt-backup.sh`](../scripts/backup/decrypt-backup.sh)

1. ดาวน์โหลดไฟล์ `.tpbk` จาก Cloudflare dashboard (R2 → bucket → ไฟล์ → Download)
2. เช็กว่าลูกกุญแจถูกคู่: `decrypt-backup.sh --fingerprint backup-private.pem` → `e9bb08152fc54157`
3. ถอดรหัส:

```bash
# ฐานข้อมูล — เทเข้า MariaDB ตรงๆ (ควรสร้างฐานใหม่ก่อน อย่าทับฐานที่ใช้งานอยู่จนกว่าจะเช็กแล้ว)
scripts/backup/decrypt-backup.sh db-1-mon.sql.zst.tpbk backup-private.pem - | mysql -u USER -p NEW_DB

# ทั้งเว็บ — แตกที่โฟลเดอร์โดเมน ได้ public_html/ + fortune_data/ + ekyc_data/ … ตามโครงเดิม
scripts/backup/decrypt-backup.sh files.tar.zst.tpbk backup-private.pem - | tar -C /home/admin/domains/main.thaiprompt.online -xf -
cd /home/admin/domains/main.thaiprompt.online/public_html && composer install --no-dev && npm ci && npm run build
```

> ไฟล์ dump มาจาก MariaDB 10.6 บรรทัดแรกเป็น `/*M!999999\- enable the sandbox mode */`
> client ของ MariaDB อ่านได้ปกติ — ถ้าต้องเทเข้า **MySQL** ให้ตัดบรรทัดแรกก่อน (`| sed 1d |`)

## รูปแบบไฟล์ `.tpbk`

```
"TPBK1 0512\n"                 11 ไบต์ — 0512 = ความยาวกุญแจที่ถูกห่อ
RSA-OAEP(แม่กุญแจ, กุญแจสุ่ม)   512 ไบต์ — กุญแจสุ่มใหม่ทุกไฟล์
openssl enc -aes-256-ctr -pbkdf2 ของ zstd ของข้อมูลจริง
```

metadata บน R2 มี `sha256` ของไฟล์ `.tpbk` ไว้เทียบหลังดาวน์โหลด (`sha256sum ไฟล์`)

## เปลี่ยนคู่กุญแจ

สร้างคู่ใหม่ (`openssl genpkey -algorithm RSA -pkeyopt rsa_keygen_bits:4096 -out backup-private.pem`
แล้ว `openssl pkey -in backup-private.pem -pubout -out backup-public.pem`) → วางแม่กุญแจใหม่ทับใน `backup_key_data/`
⚠️ **เก็บลูกกุญแจเก่าไว้จนครบ 1 เดือน** — ไฟล์ที่สร้างก่อนเปลี่ยนยังต้องใช้ลูกกุญแจเก่าเปิด

โค้ด: `app/Services/Backup/R2BackupService.php` · `app/Console/Commands/BackupToR2.php` · `config/backup.php` · ตารางเวลาใน `routes/console.php`
