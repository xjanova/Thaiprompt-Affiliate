#!/usr/bin/env bash
# ════════════════════════════════════════════════════════════════
# ensure-cron.sh — ติดตั้ง/ฟื้นฟู cron สำหรับ Laravel scheduler
# ════════════════════════════════════════════════════════════════
# ใช้บน DirectAdmin / cPanel / shared hosting ที่ cron มัก "หาย"
# ตอน update ระบบ
#
# Idempotent — รันซ้ำกี่ครั้งก็ได้ ไม่ duplicate
#
# Usage:
#   bash scripts/ensure-cron.sh                # auto-detect path + php
#   bash scripts/ensure-cron.sh /custom/path   # ระบุ path เอง

set -e

PROJECT_PATH="${1:-$(cd "$(dirname "$0")/.." && pwd)}"
PHP_BIN="$(command -v php || echo /usr/local/bin/php)"
CRON_LINE="* * * * * cd ${PROJECT_PATH} && ${PHP_BIN} artisan schedule:run >> /dev/null 2>&1"
MARKER="# laravel-scheduler:${PROJECT_PATH}"

echo "🔍 ตรวจสอบ cron สำหรับ ${PROJECT_PATH}"
echo "   PHP binary: ${PHP_BIN}"

# ดึง crontab ปัจจุบัน (ถ้ายังไม่มีจะได้ empty)
CURRENT="$(crontab -l 2>/dev/null || true)"

# ลบ entry เดิมของโปรเจกต์นี้ทั้งหมด (marker + ทุกบรรทัด schedule:run ของ path นี้)
# ⚠️ BUGFIX: ของเดิมใช้ `grep -v -F "artisan schedule:run.*${PATH}"` — `-F` มองว่า `.*`
#    เป็นตัวอักษรจริง เลย "ไม่เคย match" บรรทัด cron → ลบของเก่าไม่ได้ → สะสม +1 ทุก deploy (เคย 124)
# ใช้ awk ลบเฉพาะบรรทัดที่มีทั้ง path นี้ + "artisan schedule:run" (ไม่แตะ cron ของแอป/ path อื่น)
FILTERED="$(printf '%s\n' "$CURRENT" | grep -v -F "${MARKER}" | awk -v p="${PROJECT_PATH}" '!(index($0, p) && index($0, "artisan schedule:run"))' || true)"

# ใส่ entry ใหม่พร้อม marker
NEW_CRON="$(printf "%s\n%s\n%s\n" "$FILTERED" "$MARKER" "$CRON_LINE" | sed '/^$/d')"

echo "$NEW_CRON" | crontab -

echo "✅ ติดตั้ง cron สำเร็จ:"
echo "   ${CRON_LINE}"
echo ""
echo "📋 crontab ปัจจุบัน:"
crontab -l | grep -A0 -B0 "schedule:run\|${MARKER}" || true
