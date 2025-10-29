# 🔐 Authentication Guide - TP-Affiliate

## การ Clone Private Repository

เนื่องจาก TP-Affiliate เป็น private repository คุณจะต้อง authenticate ก่อนที่จะ clone ได้

---

## 🔑 วิธีการ Authentication

### วิธีที่ 1: Personal Access Token (แนะนำ)

#### 1. สร้าง Personal Access Token

1. ไปที่ GitHub Settings: https://github.com/settings/tokens
2. คลิก **"Generate new token"** → **"Generate new token (classic)"**
3. ตั้งชื่อ token เช่น "TP-Affiliate Deployment"
4. เลือก scopes:
   - ✅ `repo` (Full control of private repositories)
5. คลิก **"Generate token"**
6. **คัดลอก token ทันที** (จะไม่สามารถดูอีกครั้ง)

#### 2. Clone Repository ด้วย Token

```bash
# วิธีที่ 1: ใส่ token ใน URL
git clone https://[YOUR_TOKEN]@github.com/xjanova/Thaiprompt-Affiliate.git

# วิธีที่ 2: Git จะถามรหัสผ่าน ให้ใส่ token แทน
git clone https://github.com/xjanova/Thaiprompt-Affiliate.git
# Username: your-github-username
# Password: [ใส่ token ที่คัดลอกมา]
```

#### 3. Cache Credentials (ไม่ต้องใส่ทุกครั้ง)

```bash
# Cache credentials เป็นเวลา 1 ชั่วโมง
git config --global credential.helper 'cache --timeout=3600'

# หรือ cache ถาวร (macOS)
git config --global credential.helper osxkeychain

# หรือ cache ถาวร (Windows)
git config --global credential.helper wincred

# หรือ cache ถาวร (Linux)
git config --global credential.helper store
```

---

### วิธีที่ 2: SSH Key (สำหรับ Advanced Users)

#### 1. สร้าง SSH Key

```bash
# สร้าง SSH key ใหม่
ssh-keygen -t ed25519 -C "your-email@example.com"

# กด Enter ใช้ค่า default
# ตั้งรหัสผ่าน (optional)

# Start SSH agent
eval "$(ssh-agent -s)"

# เพิ่ม SSH key
ssh-add ~/.ssh/id_ed25519
```

#### 2. เพิ่ม SSH Key ไปยัง GitHub

```bash
# คัดลอก SSH public key
cat ~/.ssh/id_ed25519.pub
# หรือ (macOS)
pbcopy < ~/.ssh/id_ed25519.pub
```

1. ไปที่ GitHub Settings: https://github.com/settings/keys
2. คลิก **"New SSH key"**
3. วาง public key ที่คัดลอกมา
4. คลิก **"Add SSH key"**

#### 3. Clone Repository ด้วย SSH

```bash
git clone git@github.com:xjanova/Thaiprompt-Affiliate.git
```

#### 4. ทดสอบ SSH Connection

```bash
ssh -T git@github.com
# ผลลัพธ์ควรเป็น:
# Hi username! You've successfully authenticated...
```

---

### วิธีที่ 3: GitHub CLI (gh)

#### 1. ติดตั้ง GitHub CLI

```bash
# macOS
brew install gh

# Ubuntu/Debian
curl -fsSL https://cli.github.com/packages/githubcli-archive-keyring.gpg | sudo dd of=/usr/share/keyrings/githubcli-archive-keyring.gpg
echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/githubcli-archive-keyring.gpg] https://cli.github.com/packages stable main" | sudo tee /etc/apt/sources.list.d/github-cli.list > /dev/null
sudo apt update
sudo apt install gh

# Windows
winget install --id GitHub.cli
```

#### 2. Login และ Clone

```bash
# Login ผ่าน browser
gh auth login

# Clone repository
gh repo clone xjanova/Thaiprompt-Affiliate
```

---

## 🚀 Quick Setup Guide

### สำหรับผู้ใช้ทั่วไป

```bash
# 1. Clone repository (จะถาม username/password)
git clone https://github.com/xjanova/Thaiprompt-Affiliate.git

# 2. ใส่ username และ Personal Access Token
Username: your-github-username
Password: ghp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx

# 3. เข้าโฟลเดอร์
cd Thaiprompt-Affiliate

# 4. ติดตั้ง
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan serve
```

### สำหรับ Production Server

```bash
# 1. สร้าง Deploy Key (read-only)
ssh-keygen -t ed25519 -C "deploy@your-server" -f ~/.ssh/thaiprompt_deploy

# 2. เพิ่ม public key ไปยัง GitHub
# Settings → Deploy keys → Add deploy key
cat ~/.ssh/thaiprompt_deploy.pub

# 3. Clone ด้วย SSH
git clone git@github.com:xjanova/Thaiprompt-Affiliate.git

# 4. Deploy
cd Thaiprompt-Affiliate
./deploy.sh
```

---

## 🔒 Best Practices

### 1. ใช้ Personal Access Token แทนรหัสผ่าน
```bash
# ❌ ไม่ดี: ใช้รหัสผ่าน GitHub
git clone https://username:password@github.com/...

# ✅ ดี: ใช้ Personal Access Token
git clone https://username:ghp_token@github.com/...
```

### 2. ตั้งค่า Token Expiration
- ตั้งให้ token หมดอายุภายใน 30-90 วัน
- สร้าง token ใหม่เมื่อหมดอายุ
- ใช้ token ที่แตกต่างกันสำหรับแต่ละ project

### 3. จำกัด Token Permissions
- เลือกเฉพาะ permissions ที่จำเป็น
- สำหรับ clone อย่างเดียว ใช้ `repo` (read-only)
- สำหรับ deploy ใช้ `repo` (full access)

### 4. ใช้ Deploy Keys สำหรับ Production
- สร้าง SSH key เฉพาะสำหรับแต่ละ server
- ตั้งเป็น read-only ถ้าไม่ต้องการ push
- ลบ key เมื่อไม่ใช้งานแล้ว

### 5. ไม่ commit token ลง git
```bash
# เช็คว่าไม่มี token ใน git history
git log -p | grep -i token
git log -p | grep ghp_
```

---

## 🐛 Troubleshooting

### ปัญหา: "Repository not found"

```bash
# แก้ไข: ตรวจสอบว่า token มี permission ถูกต้อง
# ไปที่ https://github.com/settings/tokens
# คลิกที่ token → ตรวจสอบ scopes
```

### ปัญหา: "Authentication failed"

```bash
# แก้ไข: ลบ cached credentials และใส่ใหม่
git credential-cache exit
git credential-store --file ~/.git-credentials erase

# หรือลบไฟล์
rm ~/.git-credentials
```

### ปัญหา: "Permission denied (publickey)"

```bash
# แก้ไข: ตรวจสอบ SSH key
ssh -T git@github.com

# เพิ่ม SSH key ใหม่
ssh-add ~/.ssh/id_ed25519

# ตรวจสอบว่า key ถูกเพิ่มแล้ว
ssh-add -l
```

### ปัญหา: "fatal: could not read Password"

```bash
# แก้ไข: ใช้ HTTPS แทน SSH หรือตั้งค่า SSH key
git remote set-url origin https://github.com/xjanova/Thaiprompt-Affiliate.git
```

---

## 📚 Additional Resources

- [GitHub Personal Access Tokens](https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/creating-a-personal-access-token)
- [GitHub SSH Keys](https://docs.github.com/en/authentication/connecting-to-github-with-ssh)
- [GitHub Deploy Keys](https://docs.github.com/en/developers/overview/managing-deploy-keys)
- [Git Credential Storage](https://git-scm.com/book/en/v2/Git-Tools-Credential-Storage)

---

## 💡 Quick Tips

```bash
# ดู remote URL
git remote -v

# เปลี่ยนจาก HTTPS เป็น SSH
git remote set-url origin git@github.com:xjanova/Thaiprompt-Affiliate.git

# เปลี่ยนจาก SSH เป็น HTTPS
git remote set-url origin https://github.com/xjanova/Thaiprompt-Affiliate.git

# ดู stored credentials
git config --list | grep credential
```

---

**Need help?** 📧 ติดต่อ: support@thaiprompt.com
