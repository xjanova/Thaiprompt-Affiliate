# 🚀 วิธีสร้าง Release สำหรับระบบ Update

ระบบ Update ต้องการให้มี **GitHub Releases** เพื่อดึงข้อมูลเวอร์ชันใหม่

---

## ✅ วิธีที่ 1: ใช้ GitHub Actions (แนะนำ - อัตโนมัติ)

Repository นี้มี GitHub Actions Workflow อยู่แล้วที่ `.github/workflows/release.yml`

### ขั้นตอน:

1. **Merge PR ไปยัง main branch**
   - เมื่อ merge PR ไปยัง main
   - GitHub Actions จะทำงานอัตโนมัติ

2. **System จะ:**
   - อ่าน commit messages แบบ Conventional Commits
   - เพิ่มเวอร์ชันอัตโนมัติ (major/minor/patch)
   - อัพเดท `VERSION`, `package.json`, `CHANGELOG.md`
   - สร้าง Git Tag
   - สร้าง GitHub Release พร้อม changelog

### Commit Message Format:

```bash
# Patch version (2.127.13 → 2.127.14)
fix: แก้ไขปัญหา...

# Minor version (2.127.13 → 2.128.0)
feat: เพิ่มฟีเจอร์ใหม่...

# Major version (2.127.13 → 3.0.0)
feat!: เปลี่ยนแปลงใหญ่...
BREAKING CHANGE: ...
```

---

## ✅ วิธีที่ 2: สร้าง Release แบบ Manual

### ผ่าน GitHub Web UI:

1. ไปที่ https://github.com/xjanova/Thaiprompt-Affiliate/releases
2. คลิก **"Draft a new release"**
3. กรอกข้อมูล:
   - **Tag**: `v2.127.14` (เวอร์ชันใหม่)
   - **Title**: `Version 2.127.14`
   - **Description**: รายละเอียดการอัพเดท
4. คลิก **"Publish release"**

### ผ่าน Command Line (gh CLI):

```bash
# ติดตั้ง gh CLI (ถ้ายังไม่มี)
# Ubuntu/Debian:
curl -fsSL https://cli.github.com/packages/githubcli-archive-keyring.gpg | sudo dd of=/usr/share/keyrings/githubcli-archive-keyring.gpg
echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/githubcli-archive-keyring.gpg] https://cli.github.com/packages stable main" | sudo tee /etc/apt/sources.list.d/github-cli.list > /dev/null
sudo apt update
sudo apt install gh

# Login
gh auth login

# สร้าง Release
gh release create v2.127.14 \
  --title "Version 2.127.14" \
  --notes "## What's Changed
- แก้ไขระบบ Update ให้ทำงานได้จริง
- เพิ่ม error reporting ที่ละเอียด
- ลด cache time เพื่อตรวจสอบเวอร์ชันได้เร็วขึ้น

**Full Changelog**: https://github.com/xjanova/Thaiprompt-Affiliate/compare/v2.127.13...v2.127.14"
```

---

## ✅ วิธีที่ 3: สร้าง Release จาก Git Tags ที่มีอยู่

ถ้ามี tags อยู่แล้วแต่ไม่มี releases:

```bash
# ดู tags ที่มี
git tag -l

# สร้าง release จาก tag ที่มีอยู่
gh release create v2.127.13 \
  --title "Version 2.127.13" \
  --notes "Initial release for update system" \
  --verify-tag
```

---

## 🔐 สำหรับ Private Repository

ถ้า repository เป็น private ต้องเพิ่ม GitHub Token:

### 1. สร้าง Personal Access Token:

1. ไปที่ https://github.com/settings/tokens
2. คลิก **"Generate new token (classic)"**
3. ตั้งชื่อ: `Thaiprompt-Affiliate Update System`
4. เลือก scope: `repo` (Full control of private repositories)
5. คลิก **"Generate token"**
6. **คัดลอก token** (จะแสดงครั้งเดียว!)

### 2. เพิ่มใน .env:

```env
GITHUB_TOKEN=ghp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

### 3. Restart application:

```bash
php artisan config:clear
php artisan cache:clear
```

---

## 📊 ตรวจสอบว่าระบบทำงานหรือไม่

### 1. ทดสอบผ่าน Browser:

ไปที่: `/admin/updates/test-connection`

จะเห็น:
```json
{
  "success": true,
  "results": {
    "checks": [
      {
        "check": "GitHub API Reachability",
        "status": "passed"
      },
      {
        "check": "Latest Release",
        "status": "passed",
        "version": "2.127.14"
      }
    ]
  }
}
```

### 2. ทดสอบผ่าน Command Line:

```bash
# ตรวจสอบว่าเชื่อมต่อ GitHub ได้
curl -X GET "https://yourdomain.com/admin/updates/test-connection" \
  -H "Authorization: Bearer YOUR_API_TOKEN"

# เช็คเวอร์ชันใหม่
curl -X GET "https://yourdomain.com/admin/updates/check" \
  -H "Authorization: Bearer YOUR_API_TOKEN"
```

### 3. ทดสอบ API โดยตรง:

```bash
# ดู releases (public repo)
curl https://api.github.com/repos/xjanova/Thaiprompt-Affiliate/releases

# ดู releases (private repo)
curl -H "Authorization: Bearer ghp_xxx..." \
  https://api.github.com/repos/xjanova/Thaiprompt-Affiliate/releases
```

---

## ⚠️ Troubleshooting

### ปัญหา: 404 Not Found

**สาเหตุ:**
- Repository ไม่มี releases
- Repository เป็น private และไม่มี token

**วิธีแก้:**
- สร้าง release ตามวิธีด้านบน
- เพิ่ม GITHUB_TOKEN ถ้าเป็น private repo

### ปัญหา: 401 Unauthorized

**สาเหตุ:**
- Token ไม่ถูกต้องหรือหมดอายุ

**วิธีแก้:**
- สร้าง token ใหม่
- ตรวจสอบ scope ของ token (ต้องมี `repo`)

### ปัญหา: Cache ยังแสดงข้อมูลเก่า

**วิธีแก้:**
```bash
# Clear cache
php artisan cache:forget app_latest_version
php artisan cache:forget app_available_versions
php artisan cache:forget available_updates

# หรือ clear ทั้งหมด
php artisan cache:clear
```

---

## 🎯 Best Practices

1. **ใช้ GitHub Actions** - ให้สร้าง release อัตโนมัติ
2. **Conventional Commits** - ใช้ `feat:`, `fix:` เพื่อ auto-versioning
3. **Changelog** - เขียนรายละเอียดการเปลี่ยนแปลงชัดเจน
4. **Semantic Versioning** - ปฏิบัติตาม MAJOR.MINOR.PATCH
5. **Test ก่อน Release** - ทดสอบบน staging ก่อน production

---

## 📚 เอกสารเพิ่มเติม

- [GitHub Releases Documentation](https://docs.github.com/en/repositories/releasing-projects-on-github)
- [Conventional Commits](https://www.conventionalcommits.org/)
- [Semantic Versioning](https://semver.org/)
- [GitHub CLI Documentation](https://cli.github.com/manual/)

---

**หมายเหตุ:** หลังจากสร้าง release แล้ว รอ 1-2 นาที แล้วลอง refresh หน้า Admin Updates อีกครั้ง
