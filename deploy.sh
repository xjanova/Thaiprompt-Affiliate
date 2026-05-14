#!/bin/bash
# ================================================================
# TP-Affiliate Ultra-Minimal Deploy
# ----------------------------------------------------------------
# ใช้งาน: ./deploy.sh [branch-name]
# ถ้าไม่ระบุ branch จะใช้ branch ปัจจุบัน
#
# ขั้นตอน (ทั้งหมด ~8 step):
#   1. fetch + reset --hard origin/<branch>
#   2. composer install (เฉพาะตอน composer.lock เปลี่ยน)
#   3. migrate --force
#   4. storage:link (idempotent)
#   5. clear + rebuild cache (config/route/view)
#   6. queue:restart
#   7. reload PHP-FPM (best-effort)
#
# ⚠️ ไม่ทำ: DB backup, maintenance mode, Cloudflare purge,
#          smart schema repair, smart seeding, auto retry
#          (ทำเองได้ถ้าจำเป็น — เก็บของเก่าไว้ที่ deploy.sh.old)
# ================================================================

set -e  # หยุดทันทีถ้า command ไหน fail

# สีข้อความ
G='\033[0;32m'; Y='\033[1;33m'; R='\033[0;31m'; B='\033[0;34m'; N='\033[0m'
info()  { echo -e "${B}ℹ${N} $1"; }
ok()    { echo -e "${G}✓${N} $1"; }
warn()  { echo -e "${Y}⚠${N} $1"; }
fail()  { echo -e "${R}✗${N} $1" >&2; exit 1; }

# กำหนด branch ที่จะ deploy
BRANCH="${1:-$(git rev-parse --abbrev-ref HEAD 2>/dev/null)}"
[ -z "$BRANCH" ] || [ "$BRANCH" = "HEAD" ] && fail "ระบุ branch ไม่ได้ — ลอง: $0 claude/Main"

# Pre-flight check
[ -f .env ] || fail "ไม่พบไฟล์ .env"
[ -d .git ] || fail "ไม่ใช่ git repository"

echo ""
echo -e "${G}╔══════════════════════════════════════════════╗${N}"
echo -e "${G}║${N}  🚀 Deploy: origin/$BRANCH"
echo -e "${G}║${N}  📁 $(pwd)"
echo -e "${G}║${N}  🕐 $(date '+%Y-%m-%d %H:%M:%S')"
echo -e "${G}╚══════════════════════════════════════════════╝${N}"
echo ""

# ───────────────────────────────────────────────
# Step 1: Pull latest code (force)
# ───────────────────────────────────────────────
info "[1/7] git fetch + reset --hard origin/$BRANCH"
git fetch origin "$BRANCH"
git reset --hard "origin/$BRANCH"
NEW_COMMIT=$(git rev-parse --short HEAD)
ok "อยู่ที่ commit: $NEW_COMMIT"

# ───────────────────────────────────────────────
# Step 2: Composer (เฉพาะตอน composer.lock เปลี่ยน)
# ───────────────────────────────────────────────
LOCK_HASH_FILE=".composer.lock.checksum"
NEW_HASH=$(md5sum composer.lock 2>/dev/null | cut -d' ' -f1)
OLD_HASH=$(cat "$LOCK_HASH_FILE" 2>/dev/null || echo "")

if [ ! -d vendor ] || [ ! -f vendor/autoload.php ] || [ "$NEW_HASH" != "$OLD_HASH" ]; then
    info "[2/7] composer install (lock เปลี่ยน หรือ vendor หาย)"
    composer install --no-dev --optimize-autoloader --no-interaction
    echo "$NEW_HASH" > "$LOCK_HASH_FILE"
    ok "composer install เสร็จ"
else
    # ⚠️ ข้าม composer install — แต่ยังต้อง dump-autoload เผื่อมี class ใหม่
    # ใน app/ ที่ commit เข้ามา (composer.lock ไม่เปลี่ยน → autoload map เก่า → ClassNotFound 500)
    info "[2/7] dump-autoload (มี class ใหม่ใน app/?)"
    composer dump-autoload -o --no-dev --no-interaction 2>&1 | tail -3
    ok "dump-autoload เสร็จ"
fi

# ───────────────────────────────────────────────
# Step 3: Migrate
# ───────────────────────────────────────────────
info "[3/7] php artisan migrate --force"
php artisan migrate --force
ok "migrate เสร็จ"

# ───────────────────────────────────────────────
# Step 4: Storage symlink (idempotent)
# ───────────────────────────────────────────────
if [ ! -L public/storage ]; then
    info "[4/7] สร้าง storage symlink"
    php artisan storage:link 2>/dev/null || true
fi
ok "storage symlink พร้อม"

# ───────────────────────────────────────────────
# Step 5: Cache rebuild
# ───────────────────────────────────────────────
info "[5/7] clear + rebuild cache"
php artisan optimize:clear  # clear ทุกตัวในคำสั่งเดียว
php artisan config:cache
php artisan route:cache
php artisan view:cache
ok "cache rebuild เสร็จ"

# ───────────────────────────────────────────────
# Step 6: Queue restart (signal เท่านั้น)
# ───────────────────────────────────────────────
info "[6/7] queue:restart signal"
php artisan queue:restart 2>/dev/null || true
ok "queue restart signal ส่งแล้ว"

# ───────────────────────────────────────────────
# Step 7: Reload PHP-FPM (best-effort, ไม่ fail)
# ───────────────────────────────────────────────
info "[7/7] reload PHP-FPM"
FPM_RELOADED=false
if command -v systemctl >/dev/null 2>&1; then
    for svc in php8.3-fpm php8.2-fpm php8.1-fpm php-fpm; do
        if systemctl is-active --quiet "$svc" 2>/dev/null; then
            if sudo -n systemctl reload "$svc" 2>/dev/null; then
                ok "reload $svc สำเร็จ (OPcache cleared)"
                FPM_RELOADED=true
                break
            fi
        fi
    done
fi

if [ "$FPM_RELOADED" = false ]; then
    warn "ไม่ได้ reload PHP-FPM (อาจต้องรันเองด้วย sudo: systemctl reload php8.2-fpm)"
fi

# ───────────────────────────────────────────────
# เสร็จสิ้น
# ───────────────────────────────────────────────
echo ""
echo -e "${G}╔══════════════════════════════════════════════╗${N}"
echo -e "${G}║${N}  ✅ Deploy เสร็จสิ้น"
echo -e "${G}║${N}  📌 commit: $NEW_COMMIT"
echo -e "${G}║${N}  🕐 $(date '+%Y-%m-%d %H:%M:%S')"
echo -e "${G}╚══════════════════════════════════════════════╝${N}"
echo ""
echo "📋 ถ้าต้องการ:"
echo "  • DB backup:    mysqldump -u USER -p DBNAME > backup.sql"
echo "  • CF purge:     เข้า admin panel → Cloudflare CDN"
echo "  • Health check: curl -I https://main.thaiprompt.online"
echo "  • Rollback:     git reset --hard <old-commit> && ./deploy.sh"
echo "  • ของเก่า:      ./deploy.sh.old (เก็บไว้สำรอง)"
echo ""
