#!/usr/bin/env bash
# ════════════════════════════════════════════════════════════════
# ถอดรหัสไฟล์สำรอง .tpbk (จาก php artisan backup:r2) — ไม่ต้องมี Laravel
# ════════════════════════════════════════════════════════════════
# ต้องมี: bash, openssl, zstd (Ubuntu: apt install zstd)
#
# ใช้:
#   decrypt-backup.sh <ไฟล์.tpbk> <backup-private.pem>                 → แตกเป็นไฟล์ข้างๆ (ตัด .zst.tpbk)
#   decrypt-backup.sh <ไฟล์.tpbk> <backup-private.pem> -              → ส่งออกทาง stdout (ต่อท่อได้)
#   decrypt-backup.sh --fingerprint <backup-private.pem>               → พิมพ์ลายนิ้วมือกุญแจ
#
# ตัวอย่างกู้ฐานข้อมูลตรงเข้า MariaDB:
#   decrypt-backup.sh db-1-mon.sql.zst.tpbk backup-private.pem - | mysql -u USER -p DBNAME
# ตัวอย่างกู้ไฟล์เว็บ:
#   decrypt-backup.sh files.tar.zst.tpbk backup-private.pem - | tar -C /path/to/public_html -xf -
#
# รูปแบบไฟล์ (ดู app/Services/Backup/R2BackupService.php):
#   "TPBK1 NNNN\n" (11 ไบต์) + กุญแจที่ห่อด้วย RSA-OAEP (NNNN ไบต์) + openssl enc -aes-256-ctr -pbkdf2
set -euo pipefail

fingerprint() {
  openssl pkey -in "$1" -pubout -outform DER 2>/dev/null | openssl dgst -sha256 -r | cut -c1-16
}

if [ "${1:-}" = "--fingerprint" ]; then
  [ -f "${2:-}" ] || { echo "ไม่พบไฟล์ลูกกุญแจ: ${2:-}" >&2; exit 2; }
  fingerprint "$2"
  exit 0
fi

IN="${1:-}"; KEY="${2:-}"; OUT="${3:-}"
if [ -z "$IN" ] || [ -z "$KEY" ]; then
  sed -n '2,20p' "$0" >&2
  exit 2
fi
[ -f "$IN" ]  || { echo "ไม่พบไฟล์สำรอง: $IN" >&2; exit 2; }
[ -f "$KEY" ] || { echo "ไม่พบไฟล์ลูกกุญแจ: $KEY" >&2; exit 2; }
command -v zstd >/dev/null || { echo "ต้องติดตั้ง zstd ก่อน (apt install zstd)" >&2; exit 2; }

HEADER="$(head -c 11 "$IN")"
case "$HEADER" in
  "TPBK1 "[0-9][0-9][0-9][0-9]) ;;
  *) echo "ไม่ใช่ไฟล์ .tpbk (หัวไฟล์ผิด)" >&2; exit 3 ;;
esac
WRAPPED_LEN=$((10#${HEADER:6:4}))
DATA_OFFSET=$((11 + WRAPPED_LEN + 1))   # tail -c +N นับจาก 1

# กุญแจสุ่มของไฟล์นี้ — เก็บใน tmp ที่อ่านได้คนเดียว แล้วลบทิ้งเสมอ
umask 077
KEYFILE="$(mktemp)"
trap 'rm -f "$KEYFILE"' EXIT

if ! head -c $((11 + WRAPPED_LEN)) "$IN" | tail -c "$WRAPPED_LEN" \
     | openssl pkeyutl -decrypt -inkey "$KEY" -pkeyopt rsa_padding_mode:oaep -out "$KEYFILE" 2>/dev/null; then
  echo "เปิดกุญแจไม่ได้ — ลูกกุญแจไม่ใช่คู่ของไฟล์นี้ (ลายนิ้วมือ: $(fingerprint "$KEY"))" >&2
  exit 4
fi

decrypt() {
  tail -c +"$DATA_OFFSET" "$IN" \
    | openssl enc -d -aes-256-ctr -pbkdf2 -pass file:"$KEYFILE" \
    | zstd -d -q -c
}

if [ "$OUT" = "-" ]; then
  decrypt
else
  [ -n "$OUT" ] || OUT="${IN%.zst.tpbk}"
  [ "$OUT" != "$IN" ] || OUT="${IN}.out"
  [ ! -e "$OUT" ] || { echo "มีไฟล์ $OUT อยู่แล้ว — ไม่เขียนทับ" >&2; exit 5; }
  decrypt > "$OUT"
  echo "✅ ถอดรหัสแล้ว → $OUT" >&2
fi
