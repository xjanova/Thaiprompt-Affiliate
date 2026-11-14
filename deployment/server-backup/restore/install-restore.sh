#!/usr/bin/env bash
# ════════════════════════════════════════════════════════════════
# ♻️ ติดตั้งหน้าเว็บกู้คืนบน Ubuntu ที่ลงใหม่ — คำสั่งเดียว แล้วเปิดลิงก์ที่พิมพ์ออกมา
# ════════════════════════════════════════════════════════════════
# ใช้ (root หรือ user ที่ sudo ได้) จากโฟลเดอร์ restore-kit:
#   sudo bash install-restore.sh                 # หน้าเว็บที่ https://<IP>:8443/?t=<โทเค็น>
#   sudo bash install-restore.sh --port 9443
#   sudo bash install-restore.sh --test <โฟลเดอร์>   # ทดสอบ: เขียนลงโฟลเดอร์นั้นแทน / ไม่แตะระบบจริง
#   sudo bash install-restore.sh --source <โฟลเดอร์> # อ่านชุดสำรองจากโฟลเดอร์ที่ดาวน์โหลดมาแล้ว แทน R2
#   sudo bash install-restore.sh --stop          # ปิดหน้าเว็บ + ลบกุญแจ/โทเค็น
#
# ความปลอดภัย: HTTPS (ใบรับรองชั่วคราว 7 วัน — เบราว์เซอร์จะเตือน "ไม่ปลอดภัย" ให้กด "ดำเนินการต่อ")
#   + โทเค็นสุ่มในลิงก์ · กุญแจ/คีย์เก็บใน /run (RAM) · ปิดตัวเองใน 24 ชม.
set -euo pipefail
[ "$(id -u)" -eq 0 ] || exec sudo -E bash "$0" "$@"

KIT="$(cd "$(dirname "$0")" && pwd)"
DEST=/opt/server-restore
SESSION=/run/server-restore
PORT=8443; TARGET_ROOT=/; SOURCE_DIR=""
RCLONE_VERSION="v1.68.2"
while [ $# -gt 0 ]; do
  case "$1" in
    --port) PORT="$2"; shift ;;
    --test) TARGET_ROOT="$(realpath -m "$2")"; shift ;;
    --source) SOURCE_DIR="$(realpath "$2")"; shift ;;
    --stop)
      systemctl stop server-restore-ui 2>/dev/null || true
      for f in private.pem session.env token; do [ -f "$SESSION/$f" ] && { shred -u "$SESSION/$f" 2>/dev/null || rm -f "$SESSION/$f"; }; done
      echo "✓ ปิดหน้าเว็บและลบกุญแจแล้ว"; exit 0 ;;
    *) echo "ไม่รู้จัก $1"; exit 2 ;;
  esac
  shift
done

echo "⏳ ลงเครื่องมือที่ต้องใช้"
export DEBIAN_FRONTEND=noninteractive
apt-get update -q >/dev/null
apt-get install -y -q python3 zstd openssl curl unzip rsync tar gnupg ca-certificates >/dev/null

if ! command -v rclone >/dev/null 2>&1 || ! rclone version 2>/dev/null | grep -q 'v1\.\(6[5-9]\|[7-9][0-9]\)'; then
  echo "⏬ rclone $RCLONE_VERSION"
  T="$(mktemp -d)"; zip="rclone-$RCLONE_VERSION-linux-amd64.zip"
  curl -fsSL -o "$T/$zip" "https://downloads.rclone.org/$RCLONE_VERSION/$zip"
  curl -fsSL -o "$T/SHA256SUMS" "https://downloads.rclone.org/$RCLONE_VERSION/SHA256SUMS"
  (cd "$T" && grep " $zip\$" SHA256SUMS | sha256sum -c - >/dev/null) || { echo "❌ SHA256 ของ rclone ไม่ตรง"; exit 1; }
  unzip -q -j "$T/$zip" "*/rclone" -d "$T" && install -m 755 "$T/rclone" /usr/local/bin/rclone; rm -rf "$T"
fi

install -d -m 700 "$DEST" "$SESSION"
install -m 700 "$KIT/server-restore.sh" "$DEST/server-restore.sh"
install -m 600 "$KIT/restore-ui.py" "$KIT/restore-ui.html" "$DEST/"
ln -sf "$DEST/server-restore.sh" /usr/local/sbin/server-restore

# ใบรับรองชั่วคราว (ใส่ IP ของเครื่องใน SAN)
IP="$(hostname -I | awk '{print $1}')"
if [ ! -f "$DEST/cert.pem" ]; then
  openssl req -x509 -newkey rsa:2048 -nodes -days 7 -subj "/CN=server-restore" \
    -addext "subjectAltName=IP:$IP,DNS:localhost" -keyout "$DEST/key.pem" -out "$DEST/cert.pem" 2>/dev/null
  chmod 600 "$DEST/key.pem"
fi

# เปิดพอร์ตชั่วคราว (ถ้าเปิด ufw / csf อยู่)
if command -v ufw >/dev/null && ufw status | grep -q active; then ufw allow "$PORT/tcp" >/dev/null && echo "เปิด ufw พอร์ต $PORT (ปิดเองด้วย: ufw delete allow $PORT/tcp)"; fi

systemctl stop server-restore-ui 2>/dev/null || true
systemctl reset-failed server-restore-ui 2>/dev/null || true
systemd-run --unit=server-restore-ui --property=RuntimeMaxSec=86400 --property=PrivateTmp=no \
  --setenv=RESTORE_WORK=/var/tmp/server-restore \
  python3 "$DEST/restore-ui.py" --port "$PORT" --session "$SESSION" --engine "$DEST/server-restore.sh" \
  --cert "$DEST/cert.pem" --key "$DEST/key.pem" --target-root "$TARGET_ROOT" ${SOURCE_DIR:+--source-dir "$SOURCE_DIR"} >/dev/null

for _ in $(seq 20); do [ -s "$SESSION/token" ] && break; sleep 0.5; done
[ -s "$SESSION/token" ] || { echo "❌ หน้าเว็บไม่ขึ้น — journalctl -u server-restore-ui"; exit 1; }

echo
echo "════════════════════════════════════════════════════════════"
echo " ✅ หน้าเว็บกู้คืนพร้อมแล้ว — เปิดลิงก์นี้ในเบราว์เซอร์:"
echo
echo "    https://$IP:$PORT/?t=$(cat "$SESSION/token")"
echo
echo " เบราว์เซอร์จะเตือนใบรับรอง → กด Advanced / ขั้นสูง → ดำเนินการต่อ"
[ "$TARGET_ROOT" != / ] && echo " 🧪 โหมดทดสอบ: เขียนลง $TARGET_ROOT"
echo " ปิด: sudo bash $KIT/install-restore.sh --stop   (ปิดเองใน 24 ชม.)"
echo "════════════════════════════════════════════════════════════"
