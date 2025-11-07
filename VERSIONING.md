# 📦 Version Management Guide

Thai Prompt Affiliate Platform ใช้ระบบ **Semantic Versioning (SemVer)** ในการจัดการเวอร์ชัน

## 📋 รูปแบบเวอร์ชัน

```
MAJOR.MINOR.PATCH
  │     │     │
  │     │     └─── Bug fixes และปรับปรุงเล็กน้อย (2.0.0 → 2.0.1)
  │     └───────── Features ใหม่ (2.0.0 → 2.1.0)
  └─────────────── Breaking changes (2.0.0 → 3.0.0)
```

## 🤖 Auto Version Bump (GitHub Actions)

เวอร์ชันจะถูก bump **อัตโนมัติ** เมื่อ PR ถูก merge เข้า `main`, `master`, หรือ `claude/Main`

### 📝 Version Bump Rules:

| Commit Pattern | Bump Type | Example |
|----------------|-----------|---------|
| `BREAKING CHANGE:` or `!:` | **major** | 2.0.0 → 3.0.0 |
| `feat:` or `feature:` | **minor** | 2.0.0 → 2.1.0 |
| `fix:`, `bugfix:`, `hotfix:` | **patch** | 2.0.0 → 2.0.1 |
| อื่นๆ | **patch** | 2.0.0 → 2.0.1 |

## 🛠️ Manual Version Bump

สำหรับ feature branches:

```bash
# Patch bump (2.0.0 → 2.0.1)
./bump-version.sh patch

# Minor bump (2.0.0 → 2.1.0)  
./bump-version.sh minor

# Major bump (2.0.0 → 3.0.0)
./bump-version.sh major
```

**หรือใช้ Laravel Artisan:**
```bash
php artisan version:bump patch
```

## 🎯 Best Practices

**ใช้ Conventional Commits:**
```bash
feat: add new feature     # → minor bump
fix: resolve bug          # → patch bump
feat!: breaking change    # → major bump
chore: update deps [skip ci]  # → ไม่ bump
```

---

**Last Updated:** 2025-11-07 | **Version:** 2.2.0
