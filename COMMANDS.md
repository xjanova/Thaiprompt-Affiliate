# TP-Affiliate Pro - Command Reference

คู่มือการใช้งาน Artisan Commands สำหรับ TP-Affiliate Pro

## 📋 สารบัญ

- [Version Management](#version-management)
- [License Management](#license-management)
- [Add-on Management](#add-on-management)
- [Update Management](#update-management)

---

## 🏷️ Version Management

### `php artisan app:version`

แสดงข้อมูล Version ของแอปพลิเคชัน

```bash
# แสดงข้อมูล version พื้นฐาน
php artisan app:version

# ตรวจสอบ updates
php artisan app:version --check

# แสดง system requirements
php artisan app:version --system

# แสดง changelog
php artisan app:version --changelog
```

**Output:**
```
╔═══════════════════════════════════════════════════════════╗
║         TP-Affiliate - Version Information                ║
╚═══════════════════════════════════════════════════════════╝

┌──────────────┬───────────────┐
│ Property     │ Value         │
├──────────────┼───────────────┤
│ Version      │ 1.0.0         │
│ Codename     │ Foundation    │
│ Released     │ 2025-10-31    │
│ Laravel      │ 11.x          │
│ PHP          │ 8.2.x         │
│ Min PHP      │ 8.1.0         │
└──────────────┴───────────────┘
```

---

### `php artisan app:check-update`

ตรวจสอบ updates จาก GitHub

```bash
# ตรวจสอบ updates
php artisan app:check-update

# แสดงรายการ versions ทั้งหมด
php artisan app:check-update --list

# ล้าง cache ก่อนตรวจสอบ
php artisan app:check-update --clear-cache
```

**Options:**
- `--list` - แสดงรายการ versions ทั้งหมดที่มี
- `--clear-cache` - ล้าง cache ก่อนตรวจสอบ

---

## 🔑 License Management

### `php artisan license:activate`

เปิดใช้งาน License

```bash
# เปิดใช้งาน license (จะถาม license key)
php artisan license:activate

# เปิดใช้งานพร้อม license key
php artisan license:activate YOUR-LICENSE-KEY-HERE

# Force activate (แม้มี license เดิมอยู่)
php artisan license:activate YOUR-LICENSE-KEY --force
```

**Options:**
- `license-key` - License key ที่ต้องการเปิดใช้งาน
- `--force` - บังคับเปิดใช้งานแม้มี license อยู่แล้ว

**Output:**
```
╔═══════════════════════════════════════════════════════════╗
║          TP-Affiliate Pro License Activation              ║
╚═══════════════════════════════════════════════════════════╝

Activating license...

✓ License activated successfully!

┌────────────────┬──────────────────────────────┐
│ Property       │ Value                        │
├────────────────┼──────────────────────────────┤
│ License Key    │ ABCD1234...WXYZ              │
│ Status         │ active                       │
│ Domain         │ https://your-domain.com      │
│ Customer       │ Your Name                    │
│ Activated At   │ 2025-10-31 12:00:00         │
│ Expires At     │ 2026-10-31                   │
│ Max Activations│ 1/1                          │
└────────────────┴──────────────────────────────┘

🎉 You can now use all features of TP-Affiliate Pro!

Next steps:
  • Run: php artisan addon:list          (Check available add-ons)
  • Run: php artisan license:status      (View license details)
  • Run: php artisan app:check-update    (Check for updates)
```

---

### `php artisan license:check`

ตรวจสอบความถูกต้องของ License กับ Server

```bash
# ตรวจสอบ license
php artisan license:check

# ตรวจสอบและล้าง cache
php artisan license:check --clear-cache
```

**Options:**
- `--clear-cache` - ล้าง cache ก่อนตรวจสอบ

**Output:**
```
✓ License is valid and active!

┌─────────────┬────────────────────────────────┐
│ Property    │ Value                          │
├─────────────┼────────────────────────────────┤
│ License Key │ ABCD1234...WXYZ                │
│ Status      │ ✓ active                       │
│ Domain      │ https://your-domain.com        │
│ Customer    │ Your Name                      │
│ Expires At  │ 2026-10-31 (365 days left)     │
│ Activations │ 1/1                            │
│ Created At  │ 2025-10-31                     │
│ Last Checked│ 2025-10-31 12:00:00           │
│ Source      │ 🌐 Server                      │
└─────────────┴────────────────────────────────┘
```

---

### `php artisan license:status`

แสดงสถานะ License แบบละเอียด

```bash
# แสดงสถานะ license
php artisan license:status

# แสดงเป็น JSON
php artisan license:status --json
```

**Options:**
- `--json` - Output เป็น JSON format

**Output:**
```
╔═══════════════════════════════════════════════════════════╗
║          TP-Affiliate Pro License Status                  ║
╚═══════════════════════════════════════════════════════════╝

📋 License Information

┌─────────────────┬────────────────────────────┐
│ Property        │ Value                      │
├─────────────────┼────────────────────────────┤
│ License Key     │ ABCD1234...WXYZ            │
│ Status          │ ✓ Active                   │
│ Domain          │ https://your-domain.com    │
│ Installation ID │ 12345678...                │
│ Expires         │ 2026-10-31 (365 days left) │
│ Activated       │ 2025-10-31 12:00:00       │
│ Last Check      │ 2025-10-31 12:30:00       │
└─────────────────┴────────────────────────────┘

💻 System Information

┌─────────────┬────────────────┐
│ Property    │ Value          │
├─────────────┼────────────────┤
│ Application │ TP-Affiliate Pro│
│ Version     │ 1.0.0          │
│ Laravel     │ 11.31.0        │
│ PHP         │ 8.2.12         │
│ Environment │ production     │
└─────────────┴────────────────┘

🧩 Add-ons Status

┌──────────────────────┬──────────┬─────────┬──────────┐
│ Add-on               │ Config   │ License │ Status   │
├──────────────────────┼──────────┼─────────┼──────────┤
│ MLM Add-on           │ Enabled  │ 🔑      │ ✓ Active │
│ Payment Gateway      │ Disabled │ -       │ ✗ Inactive│
│ Analytics Dashboard  │ Disabled │ -       │ ✗ Inactive│
└──────────────────────┴──────────┴─────────┴──────────┘

Available Commands:
  • license:check              Check license validity with server
  • license:activate {key}     Activate a new license
  • license:deactivate         Deactivate current license
  • addon:list                 List available add-ons
  • app:check-update           Check for updates
```

---

### `php artisan license:deactivate`

ปิดการใช้งาน License (ปลดล็อกจาก domain นี้)

```bash
# ปิดการใช้งาน license (จะถามยืนยัน)
php artisan license:deactivate

# ปิดการใช้งานโดยไม่ถามยืนยัน
php artisan license:deactivate --force
```

**Options:**
- `--force` - ข้ามการถามยืนยัน

**Output:**
```
╔═══════════════════════════════════════════════════════════╗
║          TP-Affiliate Pro License Deactivation            ║
╚═══════════════════════════════════════════════════════════╝

Current License:
┌─────────────┬────────────────────────────┐
│ Property    │ Value                      │
├─────────────┼────────────────────────────┤
│ License Key │ ABCD1234...WXYZ            │
│ Status      │ active                     │
│ Domain      │ https://your-domain.com    │
│ Activated   │ 2025-10-31 12:00:00       │
└─────────────┴────────────────────────────┘

⚠️  Warning: Deactivating will disable all premium features.

Are you sure you want to deactivate this license? (yes/no) [no]:
> yes

Deactivating license...

✓ License deactivated successfully!

Your license has been released from this domain.
You can now activate it on another domain.

To reactivate, run:
  php artisan license:activate {license-key}
```

---

## 🧩 Add-on Management

### `php artisan addon:list`

แสดงรายการ Add-ons

```bash
# แสดง add-ons ที่ configure ไว้
php artisan addon:list

# แสดง add-ons ทั้งหมดที่มีขาย
php artisan addon:list --available

# แสดงเฉพาะ add-ons ที่เปิดใช้งาน
php artisan addon:list --activated

# แสดงเป็น JSON
php artisan addon:list --json
```

**Options:**
- `--available` - แสดง add-ons ทั้งหมดจาก server
- `--activated` - แสดงเฉพาะ add-ons ที่เปิดใช้งาน
- `--json` - Output เป็น JSON

**Output:**
```
╔═══════════════════════════════════════════════════════════╗
║          TP-Affiliate Pro Add-ons Manager                 ║
╚═══════════════════════════════════════════════════════════╝

🧩 Configured Add-ons

┌───┬──────────────────────┬──────────────────┬──────────┬─────────┬──────────┐
│   │ Name                 │ Slug             │ Config   │ License │ Status   │
├───┼──────────────────────┼──────────────────┼──────────┼─────────┼──────────┤
│ ✓ │ MLM Add-on           │ mlm              │ Enabled  │ 🔑 Yes  │ ✓ Active │
│ ✗ │ Payment Gateway      │ payment-gateway  │ Disabled │ ✗ No    │ ✗ Inactive│
│ ✗ │ Analytics Dashboard  │ analytics        │ Disabled │ ✗ No    │ ✗ Inactive│
└───┴──────────────────────┴──────────────────┴──────────┴─────────┴──────────┘

Active: 1/3

Commands:
  • addon:enable {slug} {license-key}    Enable an add-on
  • addon:disable {slug}                 Disable an add-on
  • addon:list --available               Show all available add-ons
  • addon:list --activated               Show activated add-ons
```

---

### `php artisan addon:enable`

เปิดใช้งาน Add-on

```bash
# เปิดใช้งาน add-on (จะถาม slug และ license key)
php artisan addon:enable

# เปิดใช้งานพร้อม slug
php artisan addon:enable mlm

# เปิดใช้งานพร้อม slug และ license key
php artisan addon:enable mlm ADDON-LICENSE-KEY

# Force enable
php artisan addon:enable mlm ADDON-LICENSE-KEY --force
```

**Arguments:**
- `slug` - Slug ของ add-on (mlm, payment-gateway, analytics)
- `license-key` - License key ของ add-on

**Options:**
- `--force` - บังคับเปิดใช้งานแม้ validation fail

**Output:**
```
╔═══════════════════════════════════════════════════════════╗
║          TP-Affiliate Pro Add-on Activation               ║
╚═══════════════════════════════════════════════════════════╝

Activating MLM Add-on...

✓ Add-on activated successfully!

┌─────────────┬────────────────────────┐
│ Property    │ Value                  │
├─────────────┼────────────────────────┤
│ Add-on      │ MLM Add-on             │
│ Slug        │ mlm                    │
│ License Key │ MLM12345...WXYZ        │
│ Status      │ ✓ Active               │
└─────────────┴────────────────────────┘

🎉 MLM Add-on is now ready to use!

Commands:
  • addon:list              View all add-ons
  • addon:disable mlm       Disable this add-on
  • license:status          View license details
```

---

### `php artisan addon:disable`

ปิดการใช้งาน Add-on

```bash
# ปิดการใช้งาน add-on (จะถาม slug)
php artisan addon:disable

# ปิดการใช้งานพร้อม slug
php artisan addon:disable mlm

# ปิดการใช้งานโดยไม่ถามยืนยัน
php artisan addon:disable mlm --force
```

**Arguments:**
- `slug` - Slug ของ add-on ที่ต้องการปิด

**Options:**
- `--force` - ข้ามการถามยืนยัน

**Output:**
```
╔═══════════════════════════════════════════════════════════╗
║          TP-Affiliate Pro Add-on Deactivation             ║
╚═══════════════════════════════════════════════════════════╝

Current Add-on:
┌──────────┬────────────┐
│ Property │ Value      │
├──────────┼────────────┤
│ Name     │ MLM Add-on │
│ Slug     │ mlm        │
│ Status   │ ✓ Active   │
└──────────┴────────────┘

⚠️  Warning: Disabling will turn off all features of this add-on.

Are you sure you want to disable 'MLM Add-on'? (yes/no) [no]:
> yes

Disabling add-on...

✓ Add-on disabled successfully!

'MLM Add-on' has been disabled.
Your license key remains valid and can be reactivated anytime.

To reactivate, run:
  php artisan addon:enable mlm {license-key}
```

---

## 🔄 Update Management

### `php artisan app:update`

อัปเดตแอปพลิเคชันไปยัง version ล่าสุด

```bash
# อัปเดตไปยัง version ล่าสุด
php artisan app:update

# อัปเดตไปยัง version ที่กำหนด
php artisan app:update v1.2.0

# อัปเดตโดยข้าม confirmation
php artisan app:update --force

# อัปเดตโดยไม่ backup database
php artisan app:update --no-backup

# อัปเดตโดยข้าม dependency installation
php artisan app:update --skip-deps
```

**Arguments:**
- `version` - Version ที่ต้องการอัปเดต (เช่น v1.2.0)

**Options:**
- `--force` - ข้าม confirmation prompts
- `--no-backup` - ข้ามการ backup database
- `--skip-deps` - ข้ามการติดตั้ง dependencies

**Update Process:**
1. ✓ Pre-flight checks (Git, License)
2. ✓ Enable maintenance mode
3. ✓ Backup database
4. ✓ Update code from Git
5. ✓ Install dependencies
6. ✓ Run migrations
7. ✓ Optimize application
8. ✓ Disable maintenance mode

**Output:**
```
╔═══════════════════════════════════════════════════════════╗
║              TP-Affiliate Update Manager                  ║
╚═══════════════════════════════════════════════════════════╝

Running pre-flight checks...
✓ Git is available
✓ Git repository detected
✓ Working directory is clean
Checking license...
✓ License is valid

┌─────────┬─────────┐
│         │ Version │
├─────────┼─────────┤
│ Current │ 1.0.0   │
│ Target  │ 1.1.0   │
└─────────┴─────────┘

Do you want to continue with the update? (yes/no) [yes]:
> yes

[1/6] Enabling maintenance mode...
✓ Maintenance mode enabled

[2/6] Creating database backup...
✓ Database backed up to: storage/app/backups/database-2025-10-31-120000.sql

[3/6] Updating code to v1.1.0...
✓ Code updated to v1.1.0

[4/6] Installing dependencies...
  Installing PHP dependencies...
  ✓ PHP dependencies installed
  Installing Node dependencies...
  ✓ Node dependencies installed

[5/6] Running database migrations...
✓ Migrations completed

[6/6] Optimizing application...
✓ Application optimized

Disabling maintenance mode...
✓ Maintenance mode disabled

╔═══════════════════════════════════════════════════════════╗
║          Update completed successfully! 🎉                ║
╚═══════════════════════════════════════════════════════════╝

Application has been updated to version: 1.1.0
```

---

## 🛠️ Developer Mode

สำหรับ development environment สามารถเปิด Developer Mode เพื่อข้ามการตรวจสอบ License:

```env
# .env
LICENSE_DEVELOPER_MODE=true
```

เมื่อเปิด Developer Mode:
- ✓ ข้ามการตรวจสอบ License กับ Server
- ✓ ใช้งาน Add-ons ทั้งหมดได้โดยไม่ต้องมี License
- ✓ อัปเดตได้โดยไม่ต้องตรวจสอบ License
- ⚠️  **อย่าใช้ใน Production!**

---

## 📞 Support

หากมีปัญหาหรือข้อสงสัย:

- 📧 Email: support@xman4289.com
- 🌐 Website: https://xman4289.com/support
- 📚 Documentation: https://docs.xman4289.com

---

## 📝 License

Copyright © 2025 Xman Enterprise Co., Ltd.
All rights reserved.
