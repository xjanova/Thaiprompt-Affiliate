#!/usr/bin/env bash
# ════════════════════════════════════════════════════════════════
# ติดตั้งระบบสำรองทั้งเครื่องขึ้น R2 — รันเป็น user ที่ sudo ได้ (ใช้ sudo แค่ cp / tee / ls)
# ════════════════════════════════════════════════════════════════
# ใช้:  bash deployment/server-backup/install.sh <R2_ACCOUNT_ID> <แม่กุญแจ backup-public.pem> <เครื่อง>
#       <เครื่อง> = ชื่อไฟล์ใน deployment/server-backup/hosts/ (ไม่ต้องมี .conf) เช่น thaiprompt-251, tpix-252
#
# ติดตั้ง:
#   /usr/local/bin/rclone                     (ถ้ายังไม่มี — ดาวน์โหลดเวอร์ชันตรึง + ตรวจ SHA256)
#   /usr/local/sbin/server-backup-r2          (สคริปต์หลัก)
#   /etc/server-backup-r2/r2.conf             (ค่าของเครื่อง + Account ID — ทับทุกครั้งที่ติดตั้ง)
#   /etc/server-backup-r2/backup-public.pem   (แม่กุญแจ)
#   /etc/server-backup-r2/r2-credentials.conf (root 600 — ไม่ทับของเดิม เจ้าของใส่ key เอง)
#   /etc/cron.d/server-backup-r2
#
# ⚠️ sudoers ของ admin บนเครื่อง 251 ไม่มี chmod/chown/mkdir ⇒ ใช้ "cp ไฟล์ที่ตั้ง mode ไว้แล้ว" (cp รักษา mode ของไฟล์ใหม่)
set -euo pipefail
ACCOUNT_ID="${1:?ต้องระบุ R2 Account ID}"
PUBKEY="${2:?ต้องระบุไฟล์แม่กุญแจ}"
HOST_NAME="${3:?ต้องระบุชื่อเครื่อง (ไฟล์ใน hosts/)}"
HERE="$(cd "$(dirname "$0")" && pwd)"
HOST_CONF="$HERE/hosts/$HOST_NAME.conf"
RCLONE_VERSION="v1.68.2"

[[ "$ACCOUNT_ID" =~ ^[0-9a-f]{32}$ ]] || { echo "Account ID ต้องเป็น hex 32 ตัว"; exit 1; }
[ -f "$HOST_CONF" ] || { echo "ไม่พบ $HOST_CONF"; exit 1; }
openssl pkey -pubin -in "$PUBKEY" -noout || { echo "ไฟล์แม่กุญแจไม่ใช่ public key"; exit 1; }
bash -n "$HERE/server-backup-r2.sh"
bash -n "$HOST_CONF"

STAGE="$(mktemp -d "$HOME/.server-backup-install.XXXXXX")"
trap 'rm -rf -- "$STAGE"' EXIT
chmod 700 "$STAGE"

# ── rclone ──
if ! command -v rclone >/dev/null 2>&1; then
  echo "⏬ rclone $RCLONE_VERSION"
  zip="rclone-$RCLONE_VERSION-linux-amd64.zip"
  curl -fsSL -o "$STAGE/$zip" "https://downloads.rclone.org/$RCLONE_VERSION/$zip"
  curl -fsSL -o "$STAGE/SHA256SUMS" "https://downloads.rclone.org/$RCLONE_VERSION/SHA256SUMS"
  (cd "$STAGE" && grep " $zip\$" SHA256SUMS | sha256sum -c -) || { echo "❌ SHA256 ของ rclone ไม่ตรง — ไม่ติดตั้ง"; exit 1; }
  unzip -q -j "$STAGE/$zip" "*/rclone" -d "$STAGE"
  chmod 755 "$STAGE/rclone"
  sudo cp "$STAGE/rclone" /usr/local/bin/rclone
fi
rclone version | head -1

# ── สคริปต์หลัก ──
install -m 755 "$HERE/server-backup-r2.sh" "$STAGE/server-backup-r2"
sudo cp "$STAGE/server-backup-r2" /usr/local/sbin/server-backup-r2

# ── /etc/server-backup-r2 ──
mkdir -m 700 "$STAGE/etc"
install -m 600 /dev/null "$STAGE/etc/r2.conf"
{
  echo "# ติดตั้งจาก deployment/server-backup/hosts/$HOST_NAME.conf เมื่อ $(date -Is) — แก้ที่ repo แล้วติดตั้งใหม่"
  echo "R2_ACCOUNT_ID=\"$ACCOUNT_ID\""
  cat "$HOST_CONF"
} > "$STAGE/etc/r2.conf"
install -m 644 "$PUBKEY" "$STAGE/etc/backup-public.pem"
install -m 600 /dev/null "$STAGE/etc/r2-credentials.conf"
printf '# ใส่ด้วยคำสั่งใน docs/BACKUP_R2.md (ห้ามวางในแชท/ไฟล์อื่น)\nR2_ACCESS_KEY_ID=""\nR2_SECRET_ACCESS_KEY=""\n' > "$STAGE/etc/r2-credentials.conf"

if ! sudo ls -d /etc/server-backup-r2 >/dev/null 2>&1; then
  sudo cp -r "$STAGE/etc" /etc/server-backup-r2
else
  sudo cp "$STAGE/etc/r2.conf" /etc/server-backup-r2/r2.conf
  sudo cp "$STAGE/etc/backup-public.pem" /etc/server-backup-r2/backup-public.pem
  # มี key อยู่แล้ว (เจ้าของใส่ไว้) → ไม่ทับ
  sudo cp -n "$STAGE/etc/r2-credentials.conf" /etc/server-backup-r2/r2-credentials.conf
fi

# ── cron ──
sudo tee /etc/cron.d/server-backup-r2 < "$HERE/server-backup-r2.cron" >/dev/null

echo "✅ ติดตั้งแล้ว ($HOST_NAME):"
sudo ls -la /etc/server-backup-r2 /usr/local/sbin/server-backup-r2 /etc/cron.d/server-backup-r2
echo "ลายนิ้วมือแม่กุญแจ: $(openssl pkey -pubin -in "$PUBKEY" -outform DER | openssl dgst -sha256 -r | cut -c1-16)"
