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

# ════════════════════════════════════════════════════════════════
# 🛡️ (2026-08-18) cron ระดับ root ชนะเสมอ — เจอแล้วต้อง "ถอย"
# ════════════════════════════════════════════════════════════════
# DirectAdmin เขียนทับ crontab ของ user เป็นระยะ แล้ว "กลืน" บรรทัด schedule:run
# ทิ้งไว้แต่บรรทัด marker → scheduler ตายเงียบ ไม่มี error ที่ไหนเลย
#   เกิดจริง 17 ส.ค. 2026 — ดวงฟรีรายวันกลายเป็นเมนูแพคเกจ
#   เกิดจริง 18 ส.ค. 2026 สองรอบ — โพสดวงรายวันรอบ 00:01 หลุดไปออก 09:35
#                                   แล้วตายซ้ำตอน 17:50
# ทางแก้ถาวร = ย้ายไป /etc/cron.d/laravel-thaiprompt (root:root 0644) ซึ่ง panel แตะไม่ได้
#
# ⚠️ เมื่อมี root cron แล้ว ห้ามติดตั้งใน user crontab ซ้ำเด็ดขาด
#    จะได้ schedule:run 2 ตัวต่อนาที = ทุก scheduled command รันซ้อน
#    (โพสซ้ำ / DM ซ้ำ / บิลซ้ำ) — แย่กว่าไม่มี cron เสียอีก
ROOT_CRON_FILE=""
for _cron_file in /etc/cron.d/*; do
    [ -f "$_cron_file" ] || continue
    # ไฟล์บางตัวเป็น 0600 ของ root (เช่น directadmin_cron) → grep ไม่ได้ ก็ข้ามไป
    if grep -q -F -- "${PROJECT_PATH}" "$_cron_file" 2>/dev/null \
        && grep -q -- 'artisan schedule:run' "$_cron_file" 2>/dev/null; then
        ROOT_CRON_FILE="$_cron_file"
        break
    fi
done

if [ -n "$ROOT_CRON_FILE" ]; then
    echo "🛡️  เจอ cron ระดับ root แล้ว: ${ROOT_CRON_FILE}"
    echo "   → ข้ามการติดตั้งใน user crontab (กัน schedule:run ซ้ำ 2 ตัว/นาที)"

    # ล้างซากของโปรเจกต์นี้ที่อาจค้างใน user crontab จาก deploy รอบก่อนๆ
    CURRENT="$(crontab -l 2>/dev/null || true)"
    if printf '%s\n' "$CURRENT" \
        | awk -v p="${PROJECT_PATH}" -v m="${MARKER}" \
            'index($0, m) || (index($0, p) && index($0, "artisan schedule:run")) { found = 1 } END { exit !found }'; then
        printf '%s\n' "$CURRENT" \
            | grep -v -F -- "${MARKER}" \
            | awk -v p="${PROJECT_PATH}" '!(index($0, p) && index($0, "artisan schedule:run"))' \
            | sed '/^$/d' \
            | crontab -
        echo "   🧹 ล้าง entry เก่าของโปรเจกต์นี้ออกจาก user crontab แล้ว"
    fi

    echo ""
    echo "📋 cron ที่ใช้งานจริง:"
    grep -- 'artisan schedule:run' "$ROOT_CRON_FILE" 2>/dev/null || true
    exit 0
fi

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
