#!/usr/bin/env bash
# ════════════════════════════════════════════════════════════════
# 🗄️ สำรองทั้งเครื่องขึ้น Cloudflare R2 (เข้ารหัส) — 2026-11-08
# ════════════════════════════════════════════════════════════════
# เจ้าของสั่ง: "ฐานข้อมูลทั้งก้อนของทุกเว็บในเซิร์ฟเวอร์ และทุกเว็บในเซิร์ฟเวอร์"
#            + "เก็บค่าที่จำเป็นไว้ด้วย ลง Ubuntu ใหม่แล้วทำให้เหมือนตอนนี้ได้ทันที"
#            + "config ที่เราตั้งหรือแก้ไว้ เก็บแบบเดียวกันให้เครื่อง 252 (TPIX chain) ด้วย"
#
# สคริปต์เดียวใช้ได้ทุกเครื่อง — สิ่งที่ต่างกันอยู่ใน /etc/server-backup-r2/r2.conf
# (ต้นฉบับ: deployment/server-backup/hosts/<เครื่อง>.conf)
#
# ส่วนประกอบ (component):
#   db      ทุกฐานข้อมูล MySQL/MariaDB + ผู้ใช้/สิทธิ์         → db/<ฐาน>.sql.zst.tpbk
#   system  สภาพเครื่อง: /etc ทั้งหมด + config/สคริปต์ที่ตั้งเอง
#           + manifest (แพ็กเกจ, service, docker, ผู้ใช้, เวอร์ชัน) → system/{etc,config,manifest}.tar.zst.tpbk
#   homes   /home/<user> ทั้งหมด ยกเว้น domains + cache        → homes/<user>.tar.zst.tpbk
#   sites   ทุกเว็บ /home/*/domains/*                         → sites/<user>/<โดเมน>.tar.zst.tpbk
#   docker  image ที่ build เองบนเครื่อง + dump PostgreSQL ใน container → docker/…
#
# ช่องเก็บบน R2 (<bucket>/<prefix>/…):
#   daily/<1-mon … 7-sun>/<component>/…   ทุกคืน 02:00 (NIGHTLY_COMPONENTS) เขียนทับวน 7 วัน
#   full/<day10|day25>/<component>/…       วันที่ 10, 25 = ชุดเต็มที่กู้ทั้งเครื่องได้จากโฟลเดอร์เดียว
#                                          (ของรายคืน copy ฝั่ง R2 มาด้วย + FULL_COMPONENTS)
#   <ช่อง>/_snapshot.txt                    สรุปรอบ (วันเวลา ส่วนประกอบ จำนวนไฟล์) — ไม่มีข้อมูลลับ
#
# ใช้ (root):
#   server-backup-r2 nightly                     ← cron 02:00
#   server-backup-r2 daily  [--local]            ← รอบรายคืนเดี๋ยวนี้
#   server-backup-r2 full   [--local] [--slot=manual]   ← ชุดเต็มเดี๋ยวนี้ (default day10/day25 ตามวันที่)
#   server-backup-r2 run <component…> [--slot=daily/1-mon] [--local]
#   server-backup-r2 check                       ← เครื่องมือ/กุญแจ/DB/R2 เขียน-อ่าน-ลบ
#   server-backup-r2 list                        ← ไฟล์บน R2 + ผลรอบล่าสุด
#   --local = เก็บไฟล์ไว้ใน WORK_DIR/local/<ช่อง>/… ไม่อัปโหลด (ทดสอบ)
#
# เข้ารหัส .tpbk: "TPBK1 0512\n" + RSA-OAEP(แม่กุญแจ, กุญแจสุ่ม 64 hex) + openssl enc -aes-256-ctr -pbkdf2
#   เซิร์ฟเวอร์ถือแค่แม่กุญแจ (public) — ลูกกุญแจอยู่กับเจ้าของคนเดียว (ถอด: scripts/backup/decrypt-backup.sh)
#
# กฎ: ไฟล์บน R2 ถูกแทนเมื่อไฟล์ใหม่ขึ้นครบเท่านั้น · ล้ม → แจ้งเตือน (ALERT_MODE)
set -uo pipefail
umask 077

CONF_DIR="${SERVER_BACKUP_CONF_DIR:-/etc/server-backup-r2}"
SCRIPT_VERSION="2026-11-08.2"

# ─── ค่าเริ่มต้น (ทับได้ใน $CONF_DIR/r2.conf) ───
R2_ACCOUNT_ID=""
R2_BUCKET="thaiprompt-backup"
R2_PREFIX="server-$(hostname -I 2>/dev/null | awk '{print $1}' | tr . -)"
HOST_PROFILE="generic"            # directadmin | docker | generic — ตัวกู้คืนใช้เลือกขั้นตอน
NIGHTLY_COMPONENTS="system"
FULL_COMPONENTS=""
FULL_DAYS="10 25"
PUBLIC_KEY="$CONF_DIR/backup-public.pem"
WORK_DIR="/var/tmp/server-backup-r2"
STATE_DIR="/var/lib/server-backup-r2"
LOG_FILE="/var/log/server-backup-r2.log"
ZSTD_THREADS=4
MIN_FREE_GB=20
# db
MYSQL_DEFAULTS="/usr/local/directadmin/conf/my.cnf"
SKIP_DATABASES="information_schema performance_schema sys"
# system — path ใต้ / (ไม่มี / นำหน้า, ใส่ glob ได้) ที่ไม่ใช่ /etc · /etc เก็บทั้งก้อนเสมอ
SYSTEM_PATHS=(root var/spool/cron usr/local/bin usr/local/sbin)
SYSTEM_EXCLUDES=(root/.cache node_modules)
# homes — /home/<user> ยกเว้นรายการนี้ (pattern ของ GNU tar เทียบชื่อชิ้นใน path)
HOME_EXCLUDES=(.cache .npm node_modules)
# sites
SITES_GLOB="/home/*/domains/*"
SITE_EXCLUDES=(node_modules .git storage/logs storage/framework/cache storage/framework/sessions storage/framework/views storage/debugbar)
# docker
DOCKER_SAVE_PATTERN=""            # regex ของ repo:tag ที่ build เองบนเครื่อง (ดึงจาก registry ไม่ได้)
DOCKER_PG_DUMPS=()                # "container:ฐาน" ของ PostgreSQL ใน container
# แจ้งเตือน: artisan (บอท Telegram หลังบ้าน Thaiprompt) | tpix (คาดแดงหลังบ้าน tpix.online) | none
ALERT_MODE="none"
ALERT_APP_USER="admin"
ALERT_PHP="/usr/local/php83/bin/php"
ALERT_ARTISAN="/home/admin/domains/main.thaiprompt.online/public_html/artisan"
ALERT_TPIX_ENV="/etc/tpix-watchdog.env"

# เขตเวลาของ "วัน" ที่ใช้ตั้งชื่อช่อง (1-mon…) และวันที่ 10/25 — เครื่อง 252 เป็น UTC ⇒ ตรึงเวลาไทยทุกเครื่อง
BACKUP_TZ="Asia/Bangkok"

[ -f "$CONF_DIR/r2.conf" ] && . "$CONF_DIR/r2.conf"
# R2_ACCESS_KEY_ID / R2_SECRET_ACCESS_KEY — ไฟล์แยก root 600 (เจ้าของใส่เอง)
[ -f "$CONF_DIR/r2-credentials.conf" ] && . "$CONF_DIR/r2-credentials.conf"
export TZ="$BACKUP_TZ"

RCLONE="$(command -v rclone || echo /usr/local/bin/rclone)"
MYSQLDUMP="$(command -v mysqldump || echo /usr/local/bin/mysqldump)"
MYSQL="$(command -v mysql || echo /usr/local/bin/mysql)"

MODE="${1:-}"; shift || true
LOCAL_ONLY=0; FORCE_SLOT=""; RUN_COMPONENTS=()
for a in "$@"; do
  case "$a" in
    --local) LOCAL_ONLY=1 ;;
    --slot=*) FORCE_SLOT="${a#--slot=}" ;;
    db|system|homes|sites|docker) RUN_COMPONENTS+=("$a") ;;
    *) echo "ไม่รู้จัก option: $a" >&2; exit 2 ;;
  esac
done

RUN_ID="$(date +%Y%m%d-%H%M%S)-$$"
RUN_DIR="$WORK_DIR/run-$RUN_ID"
FAILURES=()
TOTAL_BYTES=0
TOTAL_FILES=0
LABEL="$MODE"

log() { printf '%s [%s] %s\n' "$(date '+%F %T')" "$LABEL" "$*" | tee -a "$LOG_FILE" >&2; }
fail() { FAILURES+=("$*"); log "❌ $*"; }
human() { numfmt --to=iec-i --suffix=B "$1" 2>/dev/null || echo "${1}B"; }

# $1 = ข้อความ, $2 = ok|fail
alert() {
  local text="$1" kind="${2:-fail}"
  log "📣 $text"
  case "$ALERT_MODE" in
    artisan)
      [ -x "$ALERT_PHP" ] && [ -f "$ALERT_ARTISAN" ] || return 0
      # runuser: artisan ต้องรันเป็นเจ้าของไฟล์เว็บ ไม่งั้น cache/log ที่สร้างจะเป็นของ root แล้วเว็บเขียนทับไม่ได้
      ( cd "$(dirname "$ALERT_ARTISAN")" && timeout 60 runuser -u "$ALERT_APP_USER" -- \
          "$ALERT_PHP" "$ALERT_ARTISAN" alert:telegram "$text" --key="server-backup-r2-$kind" >>"$LOG_FILE" 2>&1 ) || true ;;
    tpix)
      # คาดแดงหลังบ้าน tpix.online (ตัวเดียวกับ tpix-backup-chain) — ส่งเฉพาะตอนล้ม
      [ "$kind" = fail ] || return 0
      ( set -a; . "$ALERT_TPIX_ENV" 2>/dev/null; set +a
        [ -n "${TPIX_ALERT_URL:-}" ] && [ -n "${TPIX_ALERT_TOKEN:-}" ] || exit 0
        msg=${text//\\/\\\\}; msg=${msg//\"/\\\"}
        curl -fsS -m 10 -A "server-backup-r2/1.0" -H "Authorization: Bearer ${TPIX_ALERT_TOKEN}" \
          -H "Content-Type: application/json" \
          -d "{\"node\":\"${TPIX_NODE_NAME:-$(hostname)}\",\"key\":\"server_backup_failed\",\"severity\":\"critical\",\"message\":\"${msg}\"}" \
          "${TPIX_ALERT_URL}/alert" -o /dev/null ) >>"$LOG_FILE" 2>&1 || true ;;
  esac
}

# ─── R2 ผ่าน rclone (ค่าลับส่งทาง env ของ process root — ไม่โผล่ใน ps / ไม่มีไฟล์ rclone.conf) ───
r2_env() {
  export RCLONE_CONFIG_R2_TYPE=s3 RCLONE_CONFIG_R2_PROVIDER=Cloudflare
  export RCLONE_CONFIG_R2_ACCESS_KEY_ID="${R2_ACCESS_KEY_ID:-}" RCLONE_CONFIG_R2_SECRET_ACCESS_KEY="${R2_SECRET_ACCESS_KEY:-}"
  export RCLONE_CONFIG_R2_ENDPOINT="https://${R2_ACCOUNT_ID}.r2.cloudflarestorage.com"
  # NO_HEAD: rclone 1.60 (apt ของ Ubuntu 24.04 บนเครื่อง 252) อัปโหลดสำเร็จแต่ HEAD ตรวจผลได้ 501 NotImplemented
  #   จาก R2 แล้วนับเป็นล้ม (ทดสอบ 2026-11-08) ⇒ ไม่ HEAD — upload() ยืนยันขนาดด้วย lsjson (list) อยู่แล้ว
  # ความเป็นส่วนตัวมาจาก bucket ที่ไม่เปิด public + token ผูก bucket/IP
  export RCLONE_CONFIG_R2_NO_CHECK_BUCKET=true RCLONE_CONFIG_R2_NO_HEAD=true
  export RCLONE_CONFIG_R2_CHUNK_SIZE=64M RCLONE_CONFIG_R2_UPLOAD_CONCURRENCY=4
}
remote() { printf 'r2:%s/%s/%s' "$R2_BUCKET" "$R2_PREFIX" "$1"; }

has_component() { case " $NIGHTLY_COMPONENTS $FULL_COMPONENTS ${RUN_COMPONENTS[*]:-} " in *" $1 "*) return 0 ;; esac; return 1; }

# ─── ตรวจก่อนเริ่ม: ล้มเร็ว ก่อน dump ทิ้งเปล่าๆ ───
preflight() {
  local t
  for t in zstd openssl tar flock; do
    command -v "$t" >/dev/null 2>&1 || { echo "ไม่พบโปรแกรม $t"; return 1; }
  done
  [ -r "$PUBLIC_KEY" ] || { echo "ไม่พบแม่กุญแจ $PUBLIC_KEY — ไม่สำรองแบบไม่เข้ารหัส"; return 1; }
  openssl pkey -pubin -in "$PUBLIC_KEY" -noout 2>/dev/null || { echo "แม่กุญแจ $PUBLIC_KEY อ่านไม่ได้"; return 1; }
  if has_component db; then
    command -v "$MYSQLDUMP" >/dev/null 2>&1 || { echo "ไม่พบ mysqldump"; return 1; }
    [ -r "$MYSQL_DEFAULTS" ] || { echo "อ่านสิทธิ์ MySQL ($MYSQL_DEFAULTS) ไม่ได้ — ต้องรันด้วย root"; return 1; }
  fi
  if has_component docker; then command -v docker >/dev/null 2>&1 || { echo "ไม่พบ docker"; return 1; }; fi
  if [ "$LOCAL_ONLY" -eq 0 ]; then
    [ -x "$RCLONE" ] || { echo "ไม่พบ rclone"; return 1; }
    [ -n "$R2_ACCOUNT_ID" ] && [ -n "${R2_ACCESS_KEY_ID:-}" ] && [ -n "${R2_SECRET_ACCESS_KEY:-}" ] \
      || { echo "ยังไม่ได้ใส่ R2_ACCOUNT_ID / R2_ACCESS_KEY_ID / R2_SECRET_ACCESS_KEY ใน $CONF_DIR"; return 1; }
  fi
  mkdir -p "$WORK_DIR" "$STATE_DIR"
  local free_kb; free_kb=$(df -Pk "$WORK_DIR" | awk 'NR==2{print $4}')
  [ "$free_kb" -ge $((MIN_FREE_GB * 1024 * 1024)) ] || { echo "พื้นที่ว่างใน $WORK_DIR เหลือ $((free_kb/1024/1024)) GB (ต้อง ≥ $MIN_FREE_GB)"; return 1; }
}

key_fingerprint() { openssl pkey -pubin -in "$PUBLIC_KEY" -outform DER 2>/dev/null | openssl dgst -sha256 -r | cut -c1-16; }

# ─── ผลิตไฟล์ .tpbk: <producer> | zstd | encrypt → $out ───
# $1 = ไฟล์ปลายทาง, $2 = ระดับ zstd, $3 = exit code สูงสุดที่ยอมรับของ producer (tar = 1), ที่เหลือ = คำสั่ง producer
make_tpbk() {
  local out="$1" level="$2" max_exit="$3"; shift 3
  local key wrapped len
  key="$(openssl rand -hex 32)" || return 1
  wrapped="$(mktemp -p "$RUN_DIR")"
  printf '%s' "$key" | openssl pkeyutl -encrypt -pubin -inkey "$PUBLIC_KEY" -pkeyopt rsa_padding_mode:oaep -out "$wrapped" || { rm -f "$wrapped"; return 1; }
  len=$(stat -c %s "$wrapped")
  { printf 'TPBK1 %04d\n' "$len"; cat "$wrapped"; } > "$out"
  rm -f "$wrapped"

  "$@" | zstd -q -T"$ZSTD_THREADS" -"$level" -c | TPBK_KEY="$key" openssl enc -aes-256-ctr -pbkdf2 -salt -pass env:TPBK_KEY >> "$out"
  local s=("${PIPESTATUS[@]}")
  unset key
  if [ "${s[0]}" -gt "$max_exit" ] || [ "${s[1]}" -ne 0 ] || [ "${s[2]}" -ne 0 ]; then
    echo "pipe exit codes: ${s[*]}" >&2
    return 1
  fi
  [ "$(stat -c %s "$out")" -gt $((11 + len + 64)) ] || { echo "ไฟล์เล็กผิดปกติ" >&2; return 1; }
}

# ─── อัปโหลด 1 ไฟล์ แล้วยืนยันขนาด ───
upload() {
  local file="$1" rel="$2" size sha
  size=$(stat -c %s "$file")
  if [ "$LOCAL_ONLY" -eq 1 ]; then
    mkdir -p "$WORK_DIR/local/$(dirname "$rel")"
    mv "$file" "$WORK_DIR/local/$rel"
    log "   🧪 $rel ($(human "$size")) เก็บในเครื่อง ไม่อัปโหลด"
    return 0
  fi
  sha=$(sha256sum "$file" | cut -c1-64)
  "$RCLONE" copyto "$file" "$(remote "$rel")" --retries 3 --low-level-retries 10 \
      --header-upload "x-amz-meta-sha256: $sha" --header-upload "x-amz-meta-keyfp: $(key_fingerprint)" \
      >>"$LOG_FILE" 2>&1 || { echo "rclone copyto ล้ม"; return 1; }
  local remote_size
  remote_size=$("$RCLONE" lsjson "$(remote "$rel")" 2>>"$LOG_FILE" | sed -n 's/.*"Size":\([0-9]*\).*/\1/p' | head -1)
  [ "$remote_size" = "$size" ] || { echo "ขนาดบน R2 ไม่ตรง (${remote_size:-?} != $size)"; return 1; }
  rm -f "$file"
  log "   ✓ $rel ($(human "$size"))"
}

# upload ใน $(…) = subshell ⇒ นับยอดรวมที่นี่ (ตัวแปรใน subshell ไม่กลับมา)
ship() {
  local sz err
  sz=$(stat -c %s "$1")
  if err="$(upload "$1" "$2")"; then
    TOTAL_BYTES=$((TOTAL_BYTES + sz)); TOTAL_FILES=$((TOTAL_FILES + 1))
  else
    fail "อัปโหลด $3 ไม่สำเร็จ: $err"
  fi
}

# ผลิต + ส่ง + จดชื่อไว้กันถูกเก็บกวาด — $1 ช่อง, $2 ชื่อไฟล์ในช่อง, $3 keep-file, $4 ป้าย, $5 zstd, $6 max_exit, ที่เหลือ producer
produce() {
  local dir="$1" name="$2" keep="$3" label="$4" level="$5" max_exit="$6"; shift 6
  local out="$RUN_DIR/out.tpbk"
  echo "$name" >> "$keep"
  if make_tpbk "$out" "$level" "$max_exit" "$@" 2>>"$LOG_FILE"; then
    ship "$out" "$dir/$name" "$label"
  else
    fail "$label ไม่สำเร็จ"
  fi
  rm -f "$out"
}

# ─── ลบของในช่องที่ไม่มีแล้ว (ฐาน/โดเมนที่ถูกลบออกจากเครื่อง) — เฉพาะหลังรอบนี้ขึ้นครบ ───
prune_slot() {
  local dir="$1" keep="$2" f
  [ "$LOCAL_ONLY" -eq 1 ] && return 0
  "$RCLONE" lsf -R --files-only "$(remote "$dir")" 2>>"$LOG_FILE" | while IFS= read -r f; do
    grep -qxF "$f" "$keep" || { "$RCLONE" deletefile "$(remote "$dir/$f")" >>"$LOG_FILE" 2>&1 && log "   🗑️ ลบของเก่าที่ไม่มีแล้ว: $dir/$f"; }
  done
}

TAR_QUIET=(--warning=no-file-changed --warning=no-file-removed --warning=no-file-shrank --warning=no-file-ignored)

# ══════════════════════ ส่วนประกอบ ══════════════════════

# ─── db: ทุกฐานข้อมูล → $1/<ฐาน>.sql.zst.tpbk ───
component_db() {
  local dir="$1" keep="$RUN_DIR/keep-db.txt" db dbs
  : > "$keep"
  dbs=$("$MYSQL" --defaults-extra-file="$MYSQL_DEFAULTS" -N -e 'SHOW DATABASES') || { fail "อ่านรายชื่อฐานข้อมูลไม่ได้"; return 1; }
  log "⏳ ฐานข้อมูล → $dir"
  for db in $dbs; do
    case " $SKIP_DATABASES " in *" $db "*) continue ;; esac
    # --single-transaction: InnoDB ได้ภาพนิ่งชุดเดียวกันโดยไม่ล็อกเว็บ · --databases: ไฟล์มี CREATE DATABASE/USE ในตัว
    produce "$dir" "$db.sql.zst.tpbk" "$keep" "ฐาน $db" 6 0 \
      "$MYSQLDUMP" --defaults-extra-file="$MYSQL_DEFAULTS" --single-transaction --quick \
      --routines --triggers --events --hex-blob --default-character-set=utf8mb4 --max-allowed-packet=1G \
      --databases "$db"
  done
  # ผู้ใช้ + สิทธิ์ของ MySQL (CREATE USER/GRANT) — กู้เครื่องใหม่แล้วเว็บต่อ DB ได้ทันที
  produce "$dir" "_users-grants.sql.zst.tpbk" "$keep" "ผู้ใช้/สิทธิ์ MySQL" 6 0 \
    "$MYSQLDUMP" --defaults-extra-file="$MYSQL_DEFAULTS" --system=users
  prune_slot "$dir" "$keep"
}

# ─── manifest: บัญชีสภาพเครื่อง (ตัวกู้คืนอ่านไฟล์เหล่านี้เพื่อสร้างเครื่องใหม่ให้เหมือนเดิม) ───
write_manifest() {
  local m="$1" u
  mkdir -p "$m"
  {
    echo "host_profile=$HOST_PROFILE"; echo "hostname=$(hostname -f 2>/dev/null || hostname)"
    echo "created=$(date -Is)"; echo "script_version=$SCRIPT_VERSION"; echo "r2_prefix=$R2_PREFIX"
    echo "nightly_components=$NIGHTLY_COMPONENTS"; echo "full_components=$FULL_COMPONENTS"
    echo "primary_ip=$(hostname -I 2>/dev/null | awk '{print $1}')"
    echo "timezone=$(cat /etc/timezone 2>/dev/null || timedatectl show -p Timezone --value 2>/dev/null)"
    . /etc/os-release 2>/dev/null && { echo "os_id=$ID"; echo "os_version=$VERSION_ID"; echo "os_pretty=$PRETTY_NAME"; }
    echo "kernel=$(uname -r)"
  } > "$m/host.env"
  # แพ็กเกจ
  dpkg-query -W -f='${binary:Package}\t${Version}\n' > "$m/packages-all.tsv" 2>/dev/null
  apt-mark showmanual > "$m/packages-manual.txt" 2>/dev/null
  command -v snap >/dev/null && snap list > "$m/snap.txt" 2>/dev/null
  command -v npm >/dev/null && npm ls -g --depth=0 --parseable 2>/dev/null | sed 's|.*/node_modules/||' | grep -v '/' > "$m/npm-global.txt"
  command -v pip3 >/dev/null && pip3 list --format=freeze > "$m/pip3.txt" 2>/dev/null
  # service / cron / ผู้ใช้
  systemctl list-unit-files --state=enabled --no-legend --no-pager 2>/dev/null | awk '{print $1}' > "$m/units-enabled.txt"
  systemctl list-units --type=service --state=running --no-legend --no-pager 2>/dev/null | awk '{print $1}' > "$m/units-running.txt"
  getent passwd | awk -F: '$3>=1000 && $3<60000' > "$m/users.txt"
  getent group  | awk -F: '$3>=1000 && $3<60000' > "$m/groups.txt"
  for u in $(cut -d: -f1 "$m/users.txt") root; do
    crontab -l -u "$u" > "$m/crontab-$u.txt" 2>/dev/null || rm -f "$m/crontab-$u.txt"
  done
  ss -lntup > "$m/listening.txt" 2>/dev/null
  ip -br addr > "$m/ip-addr.txt" 2>/dev/null; ip route > "$m/ip-route.txt" 2>/dev/null
  lsblk -f > "$m/lsblk.txt" 2>/dev/null; df -hT > "$m/df.txt" 2>/dev/null
  # เวอร์ชันซอฟต์แวร์หลัก
  {
    command -v mysql >/dev/null && mysql --version
    for p in /usr/local/php*/bin/php /usr/bin/php*; do [ -x "$p" ] && echo "$p: $("$p" -r 'echo PHP_VERSION;' 2>/dev/null)"; done
    command -v nginx >/dev/null && nginx -v 2>&1
    command -v httpd >/dev/null && httpd -v 2>/dev/null | head -1
    command -v node >/dev/null && echo "node $(node -v)"
    command -v docker >/dev/null && docker --version
    [ -x /usr/local/directadmin/directadmin ] && /usr/local/directadmin/directadmin v 2>/dev/null | head -2
  } > "$m/versions.txt" 2>/dev/null
  # DirectAdmin: รายชื่อผู้ใช้/โดเมน (ตัวกู้ใช้สร้างผู้ใช้และวางเว็บ)
  if [ -d /usr/local/directadmin/data/users ]; then
    for u in /usr/local/directadmin/data/users/*/; do
      u="$(basename "$u")"; printf '%s\t%s\n' "$u" "$(tr '\n' ' ' < "/usr/local/directadmin/data/users/$u/domains.list" 2>/dev/null)"
    done > "$m/directadmin-users.tsv"
  fi
  # pm2 (process ของ node ที่รันค้าง)
  for u in root $(cut -d: -f1 "$m/users.txt"); do
    local h; h="$(getent passwd "$u" | cut -d: -f6)"
    [ -f "$h/.pm2/dump.pm2" ] && echo "$u" >> "$m/pm2-users.txt"
  done
  # docker: container, image, compose project (ลำดับ + โฟลเดอร์ + ไฟล์)
  if command -v docker >/dev/null 2>&1 && docker info >/dev/null 2>&1; then
    docker ps -a --format '{{.Names}}\t{{.Image}}\t{{.Status}}' > "$m/docker-ps.tsv"
    docker images --format '{{.Repository}}:{{.Tag}}\t{{.ID}}\t{{.Size}}' > "$m/docker-images.tsv"
    docker volume ls -q > "$m/docker-volumes.txt"; docker network ls --format '{{.Name}}\t{{.Driver}}' > "$m/docker-networks.tsv"
    docker ps -a -q | xargs -r docker inspect --format \
      '{{index .Config.Labels "com.docker.compose.project"}}{{"\t"}}{{index .Config.Labels "com.docker.compose.project.working_dir"}}{{"\t"}}{{index .Config.Labels "com.docker.compose.project.config_files"}}{{"\t"}}{{index .Config.Labels "com.docker.compose.service"}}{{"\t"}}{{.Name}}' \
      2>/dev/null | sed 's|\t/|\t/|' | sort -u > "$m/docker-compose.tsv"
    if [ -n "$DOCKER_SAVE_PATTERN" ]; then docker images --format '{{.Repository}}:{{.Tag}}' | grep -E "$DOCKER_SAVE_PATTERN" > "$m/docker-saved-images.txt"; fi
    printf '%s\n' "${DOCKER_PG_DUMPS[@]}" > "$m/docker-pg-dumps.txt"
  fi
  printf '%s\n' "${SYSTEM_PATHS[@]}" > "$m/system-paths.txt"
  return 0
}

# ─── system: /etc + config ที่ตั้งเอง + manifest → $1/… ───
component_system() {
  local dir="$1" keep="$RUN_DIR/keep-system.txt" p paths=() excl=()
  : > "$keep"
  log "⏳ สภาพเครื่อง → $dir"
  write_manifest "$RUN_DIR/manifest" 2>>"$LOG_FILE"
  produce "$dir" manifest.tar.zst.tpbk "$keep" "manifest" 9 0 tar -C "$RUN_DIR" -cf - manifest
  produce "$dir" etc.tar.zst.tpbk "$keep" "/etc" 9 1 tar -C / "${TAR_QUIET[@]}" -cf - etc
  # glob ใน SYSTEM_PATHS ขยายตอนรัน — เก็บเฉพาะที่มีจริง
  shopt -s nullglob
  for p in "${SYSTEM_PATHS[@]}"; do
    for p in /$p; do [ -e "$p" ] && paths+=("${p#/}"); done
  done
  shopt -u nullglob
  for p in "${SYSTEM_EXCLUDES[@]}"; do excl+=("--exclude=$p"); done
  if [ "${#paths[@]}" -gt 0 ]; then
    produce "$dir" config.tar.zst.tpbk "$keep" "config ระบบ" 6 1 \
      tar -C / "${excl[@]}" "${TAR_QUIET[@]}" --one-file-system -cf - "${paths[@]}"
  fi
  prune_slot "$dir" "$keep"
}

# ─── homes: /home/<user> (ไม่รวม domains — อยู่ใน sites) → $1/<user>.tar.zst.tpbk ───
component_homes() {
  local dir="$1" keep="$RUN_DIR/keep-homes.txt" h user excl=()
  : > "$keep"
  for p in "${HOME_EXCLUDES[@]}"; do excl+=("--exclude=$p"); done
  log "⏳ home ผู้ใช้ → $dir"
  for h in /home/*/; do
    h="${h%/}"; [ -L "$h" ] && continue
    user="$(basename "$h")"
    # --anchored เฉพาะ <user>/domains (ไม่ไปตัดโฟลเดอร์ชื่อ domains ที่ลึกลงไปในโปรเจกต์)
    produce "$dir" "$user.tar.zst.tpbk" "$keep" "home $user" 3 1 \
      tar -C /home --anchored "--exclude=$user/domains" --no-anchored "${excl[@]}" "${TAR_QUIET[@]}" -cf - "$user"
  done
  prune_slot "$dir" "$keep"
}

# ─── sites: ทุกเว็บ → $1/<user>/<โดเมน>.tar.zst.tpbk ───
component_sites() {
  local dir="$1" keep="$RUN_DIR/keep-sites.txt" d user name parent excl=()
  : > "$keep"
  for p in "${SITE_EXCLUDES[@]}"; do excl+=("--exclude=$p"); done
  log "⏳ เว็บไซต์ → $dir"
  for d in $SITES_GLOB; do
    [ -d "$d" ] && [ ! -L "$d" ] || continue
    parent="$(dirname "$d")"; name="$(basename "$d")"
    user="$(basename "$(dirname "$parent")")"
    # tar exit 1 = ไฟล์เปลี่ยนระหว่างอ่าน (เว็บยังวิ่งอยู่) — ไม่ใช่ความเสียหาย
    produce "$dir" "$user/$name.tar.zst.tpbk" "$keep" "เว็บ $user/$name" 3 1 \
      tar -C "$parent" "${excl[@]}" "${TAR_QUIET[@]}" -cf - "$name"
  done
  prune_slot "$dir" "$keep"
}

# ─── docker: image ที่ build เอง + PostgreSQL ใน container → $1/… ───
component_docker() {
  local dir="$1" keep="$RUN_DIR/keep-docker.txt" img name spec c db
  : > "$keep"
  log "⏳ docker → $dir"
  if [ -n "$DOCKER_SAVE_PATTERN" ]; then
    for img in $(docker images --format '{{.Repository}}:{{.Tag}}' | grep -E "$DOCKER_SAVE_PATTERN"); do
      name="$(printf '%s' "$img" | tr '/:' '__')"
      produce "$dir" "images/$name.tar.zst.tpbk" "$keep" "docker image $img" 3 0 docker save "$img"
    done
  fi
  for spec in "${DOCKER_PG_DUMPS[@]}"; do
    c="${spec%%:*}"; db="${spec#*:}"
    # -Fc -Z0: format custom (pg_restore แบบขนานได้) ไม่บีบในตัว ให้ zstd บีบทีเดียว
    produce "$dir" "pg/$c-$db.dump.zst.tpbk" "$keep" "PostgreSQL $c/$db" 3 0 \
      docker exec "$c" sh -c 'pg_dump -U "$POSTGRES_USER" -Fc -Z0 "$1"' _ "$db"
  done
  prune_slot "$dir" "$keep"
}

run_component() {
  case "$1" in
    db) component_db "$2/db" ;;
    system) component_system "$2/system" ;;
    homes) component_homes "$2/homes" ;;
    sites) component_sites "$2/sites" ;;
    docker) component_docker "$2/docker" ;;
  esac
}

daily_slot() { echo "daily/$(date +%u-%a | tr '[:upper:]' '[:lower:]')"; }
full_slot() {
  if [ -n "$FORCE_SLOT" ]; then echo "full/$FORCE_SLOT"; return; fi
  [ "$((10#$(date +%d)))" -le 17 ] && echo full/day10 || echo full/day25
}

# ─── สรุปรอบ: _snapshot.txt ในช่อง + state + แจ้งเตือน ───
# $1 = ป้าย, $2 = ช่อง, $3 = เวลาเริ่ม, $4 = ส่วนประกอบ, $5 = แจ้งตอนสำเร็จไหม (1/0)
finish() {
  local label="$1" slot="$2" started="$3" comps="$4" notify_ok="$5" secs snap
  secs=$(( $(date +%s) - started ))
  snap="$RUN_DIR/_snapshot.txt"
  {
    echo "slot=$slot"; echo "label=$label"; echo "host_profile=$HOST_PROFILE"; echo "components=$comps"
    echo "finished=$(date -Is)"; echo "seconds=$secs"; echo "files=$TOTAL_FILES"; echo "bytes=$TOTAL_BYTES"
    echo "failures=${#FAILURES[@]}"; echo "keyfp=$(key_fingerprint)"; echo "script_version=$SCRIPT_VERSION"
  } > "$snap"
  cp "$snap" "$STATE_DIR/last-$(echo "$slot" | tr '/' '-').txt"
  printf 'failure=%s\n' "${FAILURES[@]}" >> "$STATE_DIR/last-$(echo "$slot" | tr '/' '-').txt"
  if [ "$LOCAL_ONLY" -eq 1 ]; then mkdir -p "$WORK_DIR/local/$slot"; cp "$snap" "$WORK_DIR/local/$slot/_snapshot.txt"
  else "$RCLONE" copyto "$snap" "$(remote "$slot/_snapshot.txt")" >>"$LOG_FILE" 2>&1 || fail "เขียน _snapshot.txt ไม่สำเร็จ"; fi
  if [ "${#FAILURES[@]}" -gt 0 ]; then
    alert "🗄️ สำรองเครื่อง $R2_PREFIX ($label) มีปัญหา ${#FAILURES[@]} รายการ: $(printf '%s; ' "${FAILURES[@]:0:5}") — ชุดเก่าของรายการที่ล้มยังอยู่บน R2" fail
    return 1
  fi
  log "✅ $label เสร็จ — $TOTAL_FILES ไฟล์ $(human "$TOTAL_BYTES") ใน ${secs} วิ"
  [ "$notify_ok" = 1 ] && [ "$LOCAL_ONLY" -eq 0 ] && alert "✅ สำรองเครื่อง $R2_PREFIX ($label) เสร็จ — $TOTAL_FILES ไฟล์ $(human "$TOTAL_BYTES") ใช้ $((secs/60)) นาที" ok
  return 0
}

reset_counters() { FAILURES=(); TOTAL_BYTES=0; TOTAL_FILES=0; }

run_with_lock() {
  exec 9>"/var/lock/server-backup-r2.lock"
  flock -n 9 || { log "มีรอบสำรองอื่นรันอยู่ — ข้าม"; exit 0; }
  local msg
  if ! msg="$(preflight 2>&1)"; then
    log "❌ $msg"
    [ "$LOCAL_ONLY" -eq 1 ] || alert "🗄️ สำรองเครื่อง $R2_PREFIX ไม่ได้เริ่ม: $msg" fail
    exit 1
  fi
  mkdir -p "$RUN_DIR"
  trap 'rm -rf -- "$RUN_DIR"' EXIT
  # รอบที่ตายกลางทางเกิน 1 วัน
  find "$WORK_DIR" -maxdepth 1 -name 'run-*' -type d -mtime +0 -exec rm -rf -- {} + 2>/dev/null
  r2_env
}

# ชุดเต็ม: copy ของรายคืน (ฝั่ง R2) + FULL_COMPONENTS ลงช่อง full/…
# $1 = ช่องรายคืนที่เพิ่งทำ (ว่าง = ทำส่วนรายคืนใหม่ลงช่อง full เลย)
do_full() {
  local dslot="$1" fslot c started
  fslot="$(full_slot)"
  [[ "$fslot" =~ ^full/[a-z0-9-]{1,20}$ ]] || { log "ชื่อช่องไม่ถูกต้อง: $fslot"; return 2; }
  LABEL="full"; reset_counters; started=$(date +%s)
  for c in $NIGHTLY_COMPONENTS; do
    if [ -n "$dslot" ] && [ "$LOCAL_ONLY" -eq 0 ]; then
      "$RCLONE" sync "$(remote "$dslot/$c")" "$(remote "$fslot/$c")" >>"$LOG_FILE" 2>&1 \
        && log "   ✓ คัดลอก $c คืนนี้ → $fslot/$c" || fail "คัดลอก $c ไป $fslot ไม่สำเร็จ"
    else
      run_component "$c" "$fslot"
    fi
  done
  for c in $FULL_COMPONENTS; do run_component "$c" "$fslot"; done
  finish "ชุดเต็ม $fslot" "$fslot" "$started" "$NIGHTLY_COMPONENTS $FULL_COMPONENTS" 1
}

started=$(date +%s)
case "$MODE" in
  nightly)
    LABEL="daily"; run_with_lock
    dslot="$(daily_slot)"
    for c in $NIGHTLY_COMPONENTS; do run_component "$c" "$dslot"; done
    rc=0; finish "รายคืน $dslot" "$dslot" "$started" "$NIGHTLY_COMPONENTS" 0 || rc=1
    if [[ " $FULL_DAYS " == *" $((10#$(date +%d))) "* ]] && [ -n "$FULL_COMPONENTS$NIGHTLY_COMPONENTS" ]; then
      do_full "$dslot" || rc=1
    fi
    exit $rc ;;
  daily)
    LABEL="daily"; run_with_lock
    dslot="${FORCE_SLOT:+daily/$FORCE_SLOT}"; dslot="${dslot:-$(daily_slot)}"
    for c in $NIGHTLY_COMPONENTS; do run_component "$c" "$dslot"; done
    finish "รายคืน $dslot" "$dslot" "$started" "$NIGHTLY_COMPONENTS" 0; exit $? ;;
  full)
    run_with_lock
    do_full ""; exit $? ;;
  run)
    LABEL="run"; [ "${#RUN_COMPONENTS[@]}" -gt 0 ] || { echo "ระบุ component: db system homes sites docker" >&2; exit 2; }
    run_with_lock
    slot="${FORCE_SLOT:-$(daily_slot)}"
    [[ "$slot" =~ ^(daily|full)/[a-z0-9-]{1,20}$ ]] || { log "ชื่อช่องไม่ถูกต้อง: $slot"; exit 2; }
    for c in "${RUN_COMPONENTS[@]}"; do run_component "$c" "$slot"; done
    finish "สั่งเอง ${RUN_COMPONENTS[*]} → $slot" "$slot" "$started" "${RUN_COMPONENTS[*]}" 0; exit $? ;;
  check)
    LOCAL_ONLY=0
    if ! msg="$(preflight 2>&1)"; then echo "❌ $msg"; exit 1; fi
    r2_env
    echo "✓ เครื่องมือครบ · แม่กุญแจ fp $(key_fingerprint) · โปรไฟล์ $HOST_PROFILE · รายคืน [$NIGHTLY_COMPONENTS] · ชุดเต็ม [$FULL_COMPONENTS]"
    has_component db && echo "✓ ฐานข้อมูล $("$MYSQL" --defaults-extra-file="$MYSQL_DEFAULTS" -N -e 'SHOW DATABASES' | wc -l) ฐาน"
    probe="$(remote ".check-$RUN_ID")"
    echo "ok" | "$RCLONE" rcat "$probe" && "$RCLONE" cat "$probe" | grep -qx ok && "$RCLONE" deletefile "$probe" \
      && echo "✓ R2 เขียน/อ่าน/ลบ ได้ — $R2_BUCKET/$R2_PREFIX" || { echo "❌ R2 ใช้ไม่ได้ (ดู $LOG_FILE)"; exit 1; }
    ;;
  list)
    r2_env
    for f in "$STATE_DIR"/last-*.txt; do [ -f "$f" ] && { echo "── $(basename "$f" .txt)"; grep -E '^(finished|files|bytes|failures|failure)=' "$f"; }; done
    "$RCLONE" lsl "$(remote "")" 2>/dev/null | sort -k4 ;;
  *)
    sed -n '2,40p' "$0"; exit 2 ;;
esac
