# 🔑 GitHub Token Setup Guide

## ภาพรวม

GitHub Token เป็น **optional** สำหรับ deployment แต่แนะนำให้ใช้เพื่อเพิ่ม rate limit และความยืดหยุ่น

---

## 📊 เปรียบเทียบ: มี Token vs ไม่มี Token

| ด้าน | ❌ ไม่มี Token | ✅ มี Token |
|------|---------------|-------------|
| **Rate Limit** | 60 requests/hour | 5,000 requests/hour |
| **Public Repo** | ใช้งานได้ปกติ | ใช้งานได้ดีกว่า |
| **Private Repo** | ❌ ใช้ไม่ได้ | ✅ ใช้ได้ |
| **Automation** | จำกัด | เหมาะสำหรับ CI/CD |

---

## 🔧 วิธีสร้าง GitHub Personal Access Token

### ขั้นตอนที่ 1: สร้าง Token

1. ไปที่: https://github.com/settings/tokens
2. คลิก "Generate new token" → "Generate new token (classic)"
3. ตั้งค่า:
   - Note: `TP-Affiliate Deployment`
   - Expiration: 90 days (แนะนำ)
   - Scopes: ✓ `public_repo` (สำหรับ public repo)

4. คลิก "Generate token"
5. **คัดลอก token ทันที!** (จะดูไม่ได้อีก)

---

### ขั้นตอนที่ 2: ใช้งาน Token

#### วิธีที่ 1: Export ก่อนรัน (แนะนำ)

\`\`\`bash
export GITHUB_TOKEN="ghp_your_token_here"
./deploy.sh
\`\`\`

#### วิธีที่ 2: ใส่ใน .bashrc

\`\`\`bash
echo 'export GITHUB_TOKEN="ghp_your_token_here"' >> ~/.bashrc
source ~/.bashrc
\`\`\`

#### วิธีที่ 3: Inline

\`\`\`bash
GITHUB_TOKEN="ghp_your_token_here" ./deploy.sh
\`\`\`

---

## 🔒 ความปลอดภัย

### ✅ ควรทำ:
- ใช้ environment variable
- ตั้ง expiration date
- Permission เฉพาะที่จำเป็น

### ❌ ห้ามทำ:
- ❌ **ห้าม commit token เข้า git**
- ❌ ห้ามแชร์ token
- ❌ ห้าม hardcode ในไฟล์

---

## ❓ FAQ

**Q: จำเป็นต้องมีหรือไม่?**  
A: **ไม่จำเป็น** สำหรับ public repo แต่แนะนำให้มี

**Q: ถ้าไม่มี token จะเป็นยังไง?**  
A: deploy.sh จะทำงานปกติด้วย public access (60 requests/hour)

**Q: Token หมดอายุแล้วจะรู้ได้ยังไง?**  
A: deploy.sh จะแจ้ง error "authentication failed"

---

## 🎯 สรุป

\`\`\`yaml
GitHub Token สำหรับ deploy.sh:
  Required: ❌ ไม่บังคับ (repo เป็น public)
  Recommended: ✅ แนะนำ (เพิ่ม rate limit)

Benefits:
  - Rate limit: 60 → 5,000 requests/hour
  - รองรับ private repo
  - เหมาะสำหรับ automation
\`\`\`

---

**🔗 Links:**
- [GitHub Personal Access Tokens](https://github.com/settings/tokens)
- [Rate Limiting](https://docs.github.com/en/rest/overview/resources-in-the-rest-api#rate-limiting)
