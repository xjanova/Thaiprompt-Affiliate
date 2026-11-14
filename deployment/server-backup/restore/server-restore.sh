#!/usr/bin/env bash
# ════════════════════════════════════════════════════════════════
# ♻️ ตัวกู้คืนทั้งเครื่องจากชุดสำรอง R2 (คู่กับ server-backup-r2.sh) — 2026-11-08
# ════════════════════════════════════════════════════════════════
# เจ้าของสั่ง: "ลง Ubuntu ใหม่ คุณสามารถทำให้เหมือนตอนนี้ได้ทันที"
#            + "ทำเป็นเว็บสคริปรีสโตร์แบบกราฟิกใช้ง่ายๆ แค่ติดตั้งก็รีสโตร์ได้เลย"
# หน้าเว็บ (restore-ui.py) เรียกสคริปต์นี้ทีละขั้น — สั่งเองจาก shell ก็ได้
#
# ค่าที่ต้องมี: $SESSION_DIR/session.env (หน้าเว็บเขียนให้ — root 600 ใน /run ที่เป็น RAM ไม่ลงดิสก์)
#   R2_ACCOUNT_ID R2_ACCESS_KEY_ID R2_SECRET_ACCESS_KEY R2_BUCKET   หรือ SOURCE_DIR=<โฟลเดอร์ที่ดาวน์โหลดมาแล้ว>
#   PRIVATE_KEY=<ลูกกุญแจ .pem>   DA_LICENSE=<คีย์ DirectAdmin ถ้ากู้เครื่อง DirectAdmin>
#   TARGET_ROOT=/ (ทดสอบ: ชี้ไปโฟลเดอร์อื่น ⇒ ขั้นที่แตะระบบจริงจะแค่บอกว่าจะทำอะไร)
#
# ใช้:
#   server-restore servers                      รายชื่อเครื่องในชุดสำรอง
#   server-restore snapshots <เครื่อง>          ช่องที่มี (daily/…, full/…) + เวลา
#   server-restore plan <เครื่อง> <ช่อง>        อ่าน manifest → บอกโปรไฟล์ + ขั้นที่ทำได้
#   server-restore step <ขั้น> <เครื่อง> <ช่อง>  ทำทีละขั้น (ลำดับ: ดู STEPS_ORDER)
set -uo pipefail
umask 077

SESSION_DIR="${SERVER_RESTORE_SESSION:-/run/server-restore}"
[ -f "$SESSION_DIR/session.env" ] && . "$SESSION_DIR/session.env"
R2_BUCKET="${R2_BUCKET:-thaiprompt-backup}"
TARGET_ROOT="${TARGET_ROOT:-/}"
SOURCE_DIR="${SOURCE_DIR:-}"
WORK="${RESTORE_WORK:-/var/tmp/server-restore}"   # ดิสก์ (ไฟล์ใหญ่ — ไม่ใช่ RAM)
REF_DIR="${RESTORE_REF:-/root/server-restore}"   # สำเนา /etc เดิมไว้เทียบ + log
RCLONE="$(command -v rclone || echo /usr/local/bin/rclone)"

STEPS_ORDER="prepare packages directadmin users config etc homes sites databases docker-images tpix-chain docker-up services verify"

sandbox() { [ "$TARGET_ROOT" != "/" ]; }
say() { printf '%s %s\n' "$(date '+%T')" "$*"; }
die() { say "❌ $*"; exit 1; }
T() { printf '%s%s' "${TARGET_ROOT%/}" "$1"; }      # path จริงบนเครื่องปลายทาง
# รันคำสั่งที่แตะระบบจริง — โหมดทดสอบแค่บอก
sys() { if sandbox; then say "   (ทดสอบ — ไม่รันจริง) $*"; return 0; fi; "$@"; }

r2_env() {
  export RCLONE_CONFIG=/dev/null   # ค่าทั้งหมดมาจาก env — กัน NOTICE "config not found" รก log
  export RCLONE_CONFIG_R2_TYPE=s3 RCLONE_CONFIG_R2_PROVIDER=Cloudflare
  export RCLONE_CONFIG_R2_ACCESS_KEY_ID="${R2_ACCESS_KEY_ID:-}" RCLONE_CONFIG_R2_SECRET_ACCESS_KEY="${R2_SECRET_ACCESS_KEY:-}"
  export RCLONE_CONFIG_R2_ENDPOINT="https://${R2_ACCOUNT_ID:-x}.r2.cloudflarestorage.com"
  export RCLONE_CONFIG_R2_NO_CHECK_BUCKET=true
}
r2_env

# ─── อ่านไฟล์จากแหล่ง (R2 หรือโฟลเดอร์) ───
src_ls_dirs() {  # $1 = path ในแหล่ง → รายชื่อโฟลเดอร์ย่อย
  # -L: โฟลเดอร์ที่ดาวน์โหลดมาอาจเป็น symlink (เช่น ชี้ไปดิสก์อื่น)
  if [ -n "$SOURCE_DIR" ]; then find -L "$SOURCE_DIR/$1" -mindepth 1 -maxdepth 1 -type d -printf '%f\n' 2>/dev/null | sort
  else "$RCLONE" lsf --dirs-only "r2:$R2_BUCKET/$1" 2>/dev/null | sed 's|/$||'; fi
}
src_ls_files() {  # $1 = path → ไฟล์ทั้งหมด (relative, recursive)
  if [ -n "$SOURCE_DIR" ]; then (cd "$SOURCE_DIR/$1" 2>/dev/null && find -L . -type f -printf '%P\n' | sort)
  else "$RCLONE" lsf -R --files-only "r2:$R2_BUCKET/$1" 2>/dev/null; fi
}
src_cat() {  # $1 = path → stdout
  if [ -n "$SOURCE_DIR" ]; then cat "$SOURCE_DIR/$1"
  else "$RCLONE" cat "r2:$R2_BUCKET/$1" --retries 3 --low-level-retries 10; fi
}
src_exists() { src_ls_files "$(dirname "$1")" | grep -qxF "$(basename "$1")"; }

# ─── ถอดรหัส .tpbk แบบสตรีม (stdin → stdout) ไม่ต้องมีไฟล์ดิบลงดิสก์ ───
# dd bs=1 อ่านทีละไบต์ = ไม่กินเกินหัวไฟล์ (head -c กับ pipe อ่านเกินได้)
tpbk_dec() {
  local hdr len key
  hdr="$(dd bs=1 count=11 2>/dev/null)"
  [[ "$hdr" =~ ^TPBK1\ ([0-9]{4})$ ]] || { echo "ไม่ใช่ไฟล์ .tpbk" >&2; return 3; }
  len=$((10#${BASH_REMATCH[1]}))
  key="$(dd bs=1 count="$len" 2>/dev/null | openssl pkeyutl -decrypt -inkey "$PRIVATE_KEY" -pkeyopt rsa_padding_mode:oaep 2>/dev/null)"
  [ -n "$key" ] || { echo "ลูกกุญแจไม่ตรงกับไฟล์นี้" >&2; return 4; }
  TPBK_KEY="$key" openssl enc -d -aes-256-ctr -pbkdf2 -pass env:TPBK_KEY | zstd -d -q
}
# $1 = path ในแหล่ง → ข้อมูลที่ถอดแล้วทาง stdout
fetch() { src_cat "$1" | tpbk_dec; local s=("${PIPESTATUS[@]}"); [ "${s[0]}" -eq 0 ] && [ "${s[1]}" -eq 0 ]; }

key_fp() { openssl pkey -in "$PRIVATE_KEY" -pubout -outform DER 2>/dev/null | openssl dgst -sha256 -r | cut -c1-16; }

# ─── manifest + etc ของช่องที่เลือก (แตกไว้ที่ WORK ครั้งเดียว) ───
SERVER="${2:-}"; SNAP="${3:-}"
BASE="$SERVER/$SNAP"
M="$WORK/$SERVER-${SNAP//\//-}/manifest"
ETC_SNAP="$WORK/$SERVER-${SNAP//\//-}/etc"

need_manifest() {
  [ -f "$M/host.env" ] && return 0
  mkdir -p "$(dirname "$M")"
  fetch "$BASE/system/manifest.tar.zst.tpbk" | tar -C "$(dirname "$M")" -xf - || die "อ่าน manifest ของ $BASE ไม่ได้"
}
need_etc() {
  [ -d "$ETC_SNAP" ] && return 0
  mkdir -p "$ETC_SNAP.tmp"
  fetch "$BASE/system/etc.tar.zst.tpbk" | tar -C "$ETC_SNAP.tmp" -xf - || die "อ่าน etc.tar ไม่ได้"
  mv "$ETC_SNAP.tmp/etc" "$ETC_SNAP" && rmdir "$ETC_SNAP.tmp"
}
mval() { sed -n "s/^$1=//p" "$M/host.env" | head -1; }
profile() { need_manifest; mval host_profile; }

# ══════════════════════ ขั้นตอน ══════════════════════

# ใช้ได้กับช่องนี้ไหม (plan ใช้บอกหน้าเว็บ) — $1 = ขั้น
applicable() {
  local p; p="$(profile)"
  case "$1" in
    directadmin) [ "$p" = directadmin ] ;;
    databases) src_ls_dirs "$BASE" | grep -qx db ;;
    homes) src_ls_dirs "$BASE" | grep -qx homes ;;
    sites) src_ls_dirs "$BASE" | grep -qx sites ;;
    docker-images|docker-up) [ -s "$M/docker-compose.tsv" ] ;;
    tpix-chain) need_etc; [ -f "$ETC_SNAP/tpix-backup.env" ] ;;
    *) return 0 ;;
  esac
}

step_prepare() {
  say "เตรียมเครื่องมือพื้นฐาน"
  local want="zstd openssl tar curl rsync ca-certificates gnupg unzip"
  sys apt-get update -q
  sys env DEBIAN_FRONTEND=noninteractive apt-get install -y -q $want
  need_manifest; need_etc
  say "เครื่องเดิม: $(mval os_pretty) · $(mval hostname) · IP $(mval primary_ip) · โปรไฟล์ $(mval host_profile)"
  . /etc/os-release 2>/dev/null
  [ "${VERSION_ID:-}" = "$(mval os_version)" ] || say "⚠️ เครื่องนี้ Ubuntu ${VERSION_ID:-?} แต่เครื่องเดิม $(mval os_version) — แพ็กเกจบางตัวอาจไม่มี"
  # ชื่อเครื่อง + เขตเวลา
  sys hostnamectl set-hostname "$(mval hostname)"
  [ -n "$(mval timezone)" ] && sys timedatectl set-timezone "$(mval timezone)"
  mkdir -p "$REF_DIR"; rsync -a --delete "$ETC_SNAP/" "$(T "$REF_DIR")/etc-before-restore-snapshot/" 2>/dev/null \
    || cp -a "$ETC_SNAP" "$(T "$REF_DIR")/etc-snapshot"
  say "✓ เก็บสำเนา /etc ของเครื่องเดิมไว้เทียบที่ $REF_DIR/etc-before-restore-snapshot"
}

step_packages() {
  need_manifest; need_etc
  say "คืนแหล่งแพ็กเกจ (apt sources + keyrings) แล้วติดตั้งแพ็กเกจที่เครื่องเดิมติดตั้งไว้"
  local d
  for d in apt/sources.list apt/sources.list.d apt/keyrings apt/trusted.gpg.d apt/preferences.d; do
    [ -e "$ETC_SNAP/$d" ] && { mkdir -p "$(dirname "$(T "/etc/$d")")"; rsync -a "$ETC_SNAP/$d" "$(T "/etc/$(dirname "$d")")/"; }
  done
  [ -d "$ETC_SNAP/../usr-share-keyrings" ] || true
  sys apt-get update -q
  # ข้ามเคอร์เนล/บูตโหลดเดอร์ (เครื่องใหม่มีของตัวเองที่เข้ากับฮาร์ดแวร์) และแพ็กเกจที่ไม่มีใน repo
  local pkgs avail=() p
  pkgs="$(grep -Ev '^(linux-|grub|shim|efibootmgr|cloud-init$|ubuntu-server-minimal$)' "$M/packages-manual.txt")"
  if sandbox; then say "   (ทดสอบ) จะติดตั้ง $(echo "$pkgs" | wc -l) แพ็กเกจ"; return 0; fi
  for p in $pkgs; do apt-cache show "$p" >/dev/null 2>&1 && avail+=("$p") || say "   ข้าม $p (ไม่มีใน repo)"; done
  DEBIAN_FRONTEND=noninteractive apt-get install -y -q "${avail[@]}" || die "apt-get install ล้ม (ดู log ด้านบน)"
  # npm global (pm2 ฯลฯ)
  if [ -s "$M/npm-global.txt" ] && command -v npm >/dev/null; then
    grep -v '^npm$\|^corepack$' "$M/npm-global.txt" | xargs -r npm install -g || say "⚠️ npm global บางตัวไม่สำเร็จ"
  fi
  say "✓ ติดตั้ง ${#avail[@]} แพ็กเกจ"
}

step_directadmin() {
  need_manifest
  [ "$(profile)" = directadmin ] || { say "ไม่ใช่เครื่อง DirectAdmin — ข้าม"; return 0; }
  local DA=/usr/local/directadmin
  if [ ! -x "$(T "$DA/directadmin")" ]; then
    [ -n "${DA_LICENSE:-}" ] || die "ต้องใส่ License key ของ DirectAdmin (ดูใน my.directadmin.com) ในหน้าเว็บก่อน"
    say "ติดตั้ง DirectAdmin ด้วยตัวติดตั้งทางการ (ใช้เวลาราว 20-60 นาที)"
    sys bash -c "curl -fsSL https://download.directadmin.com/setup.sh -o /root/da-setup.sh && bash /root/da-setup.sh '$DA_LICENSE'" \
      || die "ติดตั้ง DirectAdmin ไม่สำเร็จ"
  else
    say "มี DirectAdmin อยู่แล้ว — ไม่ติดตั้งซ้ำ"
  fi
  # เก็บรหัส da_admin ของการติดตั้งใหม่ไว้ (ใช้ต่อ DB ก่อนคืนสิทธิ์เดิม)
  [ -f "$(T "$DA/conf/my.cnf")" ] && cp -n "$(T "$DA/conf/my.cnf")" "$(T "$REF_DIR")/da-new-my.cnf" 2>/dev/null
  say "คืนตัวเลือก custombuild เดิม (เวอร์ชัน PHP / เว็บเซิร์ฟเวอร์ / MariaDB) แล้ว build ใหม่ตามนั้น"
  mkdir -p "$WORK/da"
  fetch "$BASE/system/config.tar.zst.tpbk" | tar -C "$WORK/da" -xf - --wildcards \
    'usr/local/directadmin/custombuild/options.conf' 'usr/local/directadmin/custombuild/php_extensions.conf' \
    'usr/local/directadmin/custombuild/custom*' 2>/dev/null
  [ -f "$WORK/da/usr/local/directadmin/custombuild/options.conf" ] || die "ไม่พบ options.conf ในชุดสำรอง"
  rsync -a "$WORK/da/usr/local/directadmin/custombuild/" "$(T "$DA/custombuild")/"
  sys bash -c "cd $DA/custombuild && ./build update && ./build all d" || die "custombuild ล้ม (ดู log)"
  say "✓ DirectAdmin + stack ตามตัวเลือกเดิม"
}

step_users() {
  need_manifest; need_etc
  say "สร้างกลุ่ม/ผู้ใช้ตามเครื่องเดิม (uid/gid เดิม, รหัสผ่านเดิม)"
  local name x gid members uid home shell line hash
  while IFS=: read -r name x gid members; do
    getent group "$name" >/dev/null || sys groupadd -g "$gid" "$name" || say "⚠️ สร้างกลุ่ม $name ไม่ได้"
  done < "$M/groups.txt"
  while IFS=: read -r name x uid gid x home shell; do
    if getent passwd "$name" >/dev/null; then say "   มี $name อยู่แล้ว"; continue; fi
    sys useradd -M -u "$uid" -g "$gid" -d "$home" -s "$shell" "$name" && say "   + $name ($uid)"
    hash="$(awk -F: -v u="$name" '$1==u{print $2}' "$ETC_SNAP/shadow")"
    [ -n "$hash" ] && sys usermod -p "$hash" "$name"
  done < "$M/users.txt"
  # สมาชิกกลุ่มเสริม (sudo, docker, access ฯลฯ)
  while IFS=: read -r name x gid members; do
    [ -n "$members" ] || continue
    for x in ${members//,/ }; do getent passwd "$x" >/dev/null && sys usermod -aG "$name" "$x"; done
  done < <(cat "$ETC_SNAP/group")
  say "✓ ผู้ใช้ครบ"
}

step_config() {
  say "คืน config/สคริปต์ที่ตั้งเอง ($(tr '\n' ' ' < "$M/system-paths.txt" 2>/dev/null || need_manifest))"
  need_manifest
  fetch "$BASE/system/config.tar.zst.tpbk" | tar -C "$TARGET_ROOT" -xpf - --numeric-owner || die "แตก config.tar ไม่สำเร็จ"
  say "✓ config ระบบ"
}

# /etc: เขียนทับเฉพาะส่วนที่เราตั้ง/แก้เอง (ไม่ทับ fstab/netplan/machine-id/ssh host key ของเครื่องใหม่)
ETC_OVERLAY="cron.d crontab systemd/system sudoers.d nginx fail2ban ufw csf logrotate.d sysctl.d security/limits.d
 ssh/sshd_config.d letsencrypt supervisor redis docker/daemon.json hosts environment profile.d
 server-backup-r2 tpix-backup.env tpix-r2.env tpix-watchdog.env antix exim.conf exim.pl exim.variables.conf exim.strings.conf.custom
 dovecot pure-ftpd.conf named.conf bind proftpd.conf httpd php.ini php.d my.cnf my.cnf.d mysql modsecurity.d"
step_etc() {
  need_etc
  say "คืนส่วนของ /etc ที่ตั้งเอง (เครือข่าย/ดิสก์/ssh host key ของเครื่องใหม่ไม่ถูกแตะ)"
  local p n=0
  for p in $ETC_OVERLAY; do
    [ -e "$ETC_SNAP/$p" ] || continue
    mkdir -p "$(T "/etc/$(dirname "$p")")"
    if [ -d "$ETC_SNAP/$p" ]; then rsync -a "$ETC_SNAP/$p/" "$(T "/etc/$p")/"; else rsync -a "$ETC_SNAP/$p" "$(T "/etc/$p")"; fi
    n=$((n+1))
  done
  # ไฟล์ /etc ที่ไม่ได้มาจากแพ็กเกจไหน = สร้าง/แก้เอง → คืนด้วย (เช่น *.env ของระบบเรา)
  sys systemctl daemon-reload
  say "✓ คืน /etc $n ส่วน — ของที่ไม่ได้คืน เทียบได้ที่ $REF_DIR/etc-before-restore-snapshot"
}

step_homes() {
  local f n=0
  say "คืน home ผู้ใช้ (mail, สคริปต์, งานนอกเว็บ)"
  mkdir -p "$(T /home)"
  for f in $(src_ls_files "$BASE/homes"); do
    say "   ⏳ $f"
    fetch "$BASE/homes/$f" | tar -C "$(T /home)" -xpf - || die "แตก homes/$f ไม่สำเร็จ"
    n=$((n+1))
  done
  say "✓ home $n ผู้ใช้"
}

step_sites() {
  local f user n=0
  say "คืนทุกเว็บ → /home/<user>/domains"
  for f in $(src_ls_files "$BASE/sites"); do
    user="${f%%/*}"
    mkdir -p "$(T "/home/$user/domains")"
    say "   ⏳ $f"
    fetch "$BASE/sites/$f" | tar -C "$(T "/home/$user/domains")" -xpf - || die "แตก sites/$f ไม่สำเร็จ"
    n=$((n+1))
  done
  sandbox || chown "$(stat -c %U /home/"$user" 2>/dev/null || echo root)": "/home/$user/domains" 2>/dev/null
  say "✓ เว็บ $n โดเมน"
}

mysql_cmd() {  # หา credential ที่ใช้ได้: conf ที่คืนแล้ว → conf ของการติดตั้งใหม่ → root ผ่าน socket
  local c
  for c in /usr/local/directadmin/conf/my.cnf "$REF_DIR/da-new-my.cnf"; do
    [ -r "$c" ] && mysql --defaults-extra-file="$c" -e 'SELECT 1' >/dev/null 2>&1 && { echo "mysql --defaults-extra-file=$c"; return; }
  done
  mysql -e 'SELECT 1' >/dev/null 2>&1 && { echo mysql; return; }
  return 1
}
step_databases() {
  local f db my n=0
  say "คืนทุกฐานข้อมูล + ผู้ใช้/สิทธิ์"
  if sandbox; then
    for f in $(src_ls_files "$BASE/db"); do fetch "$BASE/db/$f" | head -c 200000 | grep -q 'CREATE DATABASE\|CREATE USER' \
      && say "   (ทดสอบ) ถอด $f ได้" || die "ถอด $f ไม่ได้"; done
    return 0
  fi
  my="$(mysql_cmd)" || die "ต่อ MySQL ไม่ได้ — ติดตั้ง/สตาร์ต MariaDB ก่อน (ขั้น DirectAdmin หรือ packages)"
  # ผู้ใช้/สิทธิ์ก่อน: CREATE USER → CREATE OR REPLACE USER (ทับรหัส da_admin ให้ตรงกับ conf เดิม)
  # แต่ไม่แตะ root@localhost / mariadb.sys / mysql@localhost ของเครื่องใหม่ (ตัดทิ้ง)
  fetch "$BASE/db/_users-grants.sql.zst.tpbk" \
    | grep -v -E '^(CREATE USER|GRANT .* TO|ALTER USER|SET DEFAULT ROLE) `(root|mariadb\.sys|mysql)`@`localhost`' \
    | sed -E 's/^CREATE USER /CREATE OR REPLACE USER /' | $my --force || say "⚠️ ผู้ใช้/สิทธิ์บางบรรทัดไม่ผ่าน (ดูด้านบน)"
  my="$(mysql_cmd)" || die "ต่อ MySQL ไม่ได้หลังคืนสิทธิ์"
  for f in $(src_ls_files "$BASE/db"); do
    db="${f%.sql.zst.tpbk}"
    case "$db" in _users-grants|mysql|sys|information_schema|performance_schema) continue ;; esac
    say "   ⏳ $db"
    fetch "$BASE/db/$f" | $my || die "นำเข้า $db ไม่สำเร็จ"
    n=$((n+1))
  done
  $my -e 'FLUSH PRIVILEGES'
  say "✓ ฐานข้อมูล $n ฐาน"
}

step_docker_images() {
  need_manifest
  command -v docker >/dev/null || sandbox || die "ยังไม่มี docker (ขั้น packages ต้องผ่านก่อน)"
  sys systemctl enable --now docker
  local f n=0
  for f in $(src_ls_files "$BASE/docker/images"); do
    say "   ⏳ image $f"
    if sandbox; then fetch "$BASE/docker/images/$f" | head -c 1048576 >/dev/null; else fetch "$BASE/docker/images/$f" | docker load || die "docker load $f ไม่สำเร็จ"; fi
    n=$((n+1))
  done
  # image จาก registry: ดึงตามรายการเดิม
  if ! sandbox; then
    cut -f1 "$M/docker-images.tsv" | grep -v '<none>' | grep -vE "$(sed 's/[.[\*^$]/\\&/g' "$M/docker-saved-images.txt" 2>/dev/null | paste -sd'|' || echo '^$')" \
      | while read -r img; do docker image inspect "$img" >/dev/null 2>&1 || docker pull -q "$img" || say "⚠️ ดึง $img ไม่ได้"; done
  fi
  say "✓ image ที่ build เอง $n ตัว + ดึง image ที่เหลือ"
}

# ข้อมูลบล็อก TPIX: ดึงชุดล่าสุดจาก bucket tpix-chain-backup (gpg) ด้วยค่าใน /etc ที่สำรองไว้
step_tpix_chain() {
  need_etc
  [ -f "$ETC_SNAP/tpix-backup.env" ] || { say "ไม่มีเชน TPIX ในเครื่องนี้ — ข้าม"; return 0; }
  local pass bucket acct ak sk latest tmp v
  pass="$(set -a; . "$ETC_SNAP/tpix-backup.env" 2>/dev/null; echo "${TPIX_BACKUP_PASS:-}")"
  acct="$(set -a; . "$ETC_SNAP/tpix-r2.env" 2>/dev/null; . "$ETC_SNAP/tpix-backup.env" 2>/dev/null; echo "${TPIX_R2_ACCOUNT_ID:-}")"
  bucket="$(set -a; . "$ETC_SNAP/tpix-r2.env" 2>/dev/null; . "$ETC_SNAP/tpix-backup.env" 2>/dev/null; echo "${TPIX_R2_BUCKET:-tpix-chain-backup}")"
  ak="$(set -a; . "$ETC_SNAP/tpix-r2.env" 2>/dev/null; echo "${TPIX_R2_ACCESS_KEY_ID:-}")"
  sk="$(set -a; . "$ETC_SNAP/tpix-r2.env" 2>/dev/null; echo "${TPIX_R2_SECRET_ACCESS_KEY:-}")"
  [ -n "$pass" ] || die "ไม่พบ TPIX_BACKUP_PASS ใน /etc/tpix-backup.env ของชุดสำรอง"
  export RCLONE_CONFIG_TC_TYPE=s3 RCLONE_CONFIG_TC_PROVIDER=Cloudflare RCLONE_CONFIG_TC_NO_CHECK_BUCKET=true
  export RCLONE_CONFIG_TC_ENDPOINT="https://$acct.r2.cloudflarestorage.com"
  export RCLONE_CONFIG_TC_ACCESS_KEY_ID="${TPIX_RESTORE_KEY_ID:-$ak}" RCLONE_CONFIG_TC_SECRET_ACCESS_KEY="${TPIX_RESTORE_SECRET:-$sk}"
  latest="$("$RCLONE" lsf "tc:$bucket" --include 'tpix-chain-*.gpg' 2>/dev/null | sort | tail -1)"
  [ -n "$latest" ] || die "ไม่พบไฟล์ tpix-chain-*.gpg ใน $bucket (token เดิมอาจผูก IP เครื่องเก่า — ใส่ token อ่านอย่างเดียวของ $bucket ในหน้าเว็บ)"
  say "ข้อมูลเชนล่าสุด: $latest"
  tmp="$WORK/tpix-chain"; rm -rf "$tmp"; mkdir -p "$tmp"
  "$RCLONE" cat "tc:$bucket/$latest" | gpg --batch --quiet --pinentry-mode loopback --passphrase-fd 3 --decrypt 3<<<"$pass" \
    | tar -C "$tmp" -xzf - || die "ถอด/แตก $latest ไม่สำเร็จ"
  [ -d "$tmp/validator-4/blockchain" ] || die "ในไฟล์ไม่มี validator-4/blockchain"
  local D; D="$(T /opt/tpix/chain/data)"; mkdir -p "$D"
  sys docker stop tpix-validator-1 tpix-validator-2 tpix-validator-3 tpix-validator-4 tpix-bootnode >/dev/null 2>&1
  # validator-4: ทั้งโฟลเดอร์ (มีคีย์ของมันเอง) · ตัวอื่น: แทนเฉพาะ blockchain/trie คีย์ของตัวเองมาจาก config.tar
  rsync -a --delete "$tmp/validator-4/" "$D/validator-4/"
  for v in "$D/validator-1" "$D/validator-2" "$D/validator-3" "$(T /opt/tpix-bootnode/data)"; do
    [ -d "$v" ] || continue
    rsync -a --delete "$tmp/validator-4/blockchain/" "$v/blockchain/"
    rsync -a --delete "$tmp/validator-4/trie/" "$v/trie/"
  done
  rm -rf "$tmp"
  say "✓ ข้อมูลเชนจาก $latest ลง validator 1-4 + bootnode (คีย์ของแต่ละตัวมาจาก config เดิม)"
}

step_docker_up() {
  need_manifest
  local proj dir files svc name spec c db started=""
  # ฐาน PostgreSQL ใน container ก่อน (ขึ้นเฉพาะ container ฐาน → restore → ค่อยขึ้นทั้งชุด)
  while IFS= read -r spec; do
    [ -n "$spec" ] || continue
    c="${spec%%:*}"; db="${spec#*:}"
    IFS=$'\t' read -r proj dir files svc name < <(grep -P "\t/$c$" "$M/docker-compose.tsv" | head -1)
    [ -n "${dir:-}" ] || { say "⚠️ ไม่รู้ว่า $c อยู่ compose ไหน — ข้ามฐาน $db"; continue; }
    if ! src_exists "$BASE/docker/pg/$c-$db.dump.zst.tpbk"; then say "⚠️ ไม่มี dump $c/$db ในช่องนี้ (มีเฉพาะชุดเต็ม)"; continue; fi
    say "   ⏳ ฐาน $db ใน $c"
    sys bash -c "cd '$dir' && docker compose -p '$proj' up -d '$svc'"
    sandbox && continue
    for _ in $(seq 60); do docker exec "$c" sh -c 'pg_isready -U "$POSTGRES_USER"' >/dev/null 2>&1 && break; sleep 2; done
    docker exec "$c" sh -c 'dropdb -U "$POSTGRES_USER" --if-exists "$1" && createdb -U "$POSTGRES_USER" "$1"' _ "$db" || die "สร้างฐาน $db ไม่ได้"
    fetch "$BASE/docker/pg/$c-$db.dump.zst.tpbk" | docker exec -i "$c" sh -c 'pg_restore -U "$POSTGRES_USER" -d "$1" --no-owner' _ "$db" \
      || say "⚠️ pg_restore $db มีคำเตือน (ดูด้านบน)"
  done < "$M/docker-pg-dumps.txt"
  # ขึ้นทุก compose project ตามโฟลเดอร์เดิม (ลำดับ: เชนก่อน explorer)
  cut -f1,2,3 "$M/docker-compose.tsv" | sort -u | while IFS=$'\t' read -r proj dir files; do
    [ -n "$proj" ] && [ -d "$(T "$dir")" ] || continue
    say "   ⏳ compose $proj ($dir)"
    sys bash -c "cd '$dir' && docker compose -p '$proj' up -d" || say "⚠️ compose $proj ขึ้นไม่ครบ"
  done
  say "✓ container ขึ้นแล้ว"
}

step_services() {
  need_manifest
  say "เปิด service ตามเครื่องเดิม"
  sys systemctl daemon-reload
  if [ "$(profile)" = directadmin ] && ! sandbox; then
    (cd /usr/local/directadmin/custombuild && ./build rewrite_confs) || say "⚠️ rewrite_confs มีปัญหา"
  fi
  local u n=0
  while read -r u; do
    [ -n "$u" ] || continue
    case "$u" in *@.service|*.target|getty*|cloud-*|snap.*|*.socket|systemd-*) continue ;; esac
    systemctl cat "$u" >/dev/null 2>&1 || sandbox || continue
    sys systemctl enable "$u" >/dev/null 2>&1 && n=$((n+1))
  done < "$M/units-enabled.txt"
  while read -r u; do
    [ -n "$u" ] || continue
    systemctl is-active --quiet "$u" 2>/dev/null || sys systemctl restart "$u" 2>/dev/null || say "⚠️ start $u ไม่ขึ้น"
  done < "$M/units-running.txt"
  # pm2 (node process ที่รันค้าง)
  if [ -s "$M/pm2-users.txt" ] && command -v pm2 >/dev/null; then
    while read -r u; do sys runuser -l "$u" -c 'pm2 resurrect' || say "⚠️ pm2 ของ $u"; done < "$M/pm2-users.txt"
  fi
  say "✓ เปิด $n unit · ที่ล้ม: $(systemctl --failed --no-legend 2>/dev/null | awk '{print $2}' | tr '\n' ' ')"
}

step_verify() {
  need_manifest
  say "ตรวจผล"
  local dom code
  if [ -f "$M/directadmin-users.tsv" ]; then
    cut -f2 "$M/directadmin-users.tsv" | tr ' ' '\n' | grep -v '^$' | while read -r dom; do
      code="$(curl -sk -o /dev/null -m 15 -w '%{http_code}' --resolve "$dom:443:127.0.0.1" "https://$dom/" 2>/dev/null)"
      say "   $dom → ${code:-ไม่ตอบ}"
    done
  fi
  command -v docker >/dev/null && docker ps --format '   {{.Names}}  {{.Status}}' 2>/dev/null
  my="$(mysql_cmd 2>/dev/null)" && say "   ฐานข้อมูล $($my -N -e 'SHOW DATABASES' | wc -l) ฐาน"
  say "   service ที่ล้ม: $(systemctl --failed --no-legend 2>/dev/null | awk '{print $2}' | tr '\n' ' ')"
  say "✓ ตรวจเสร็จ — เทียบ IP: เครื่องเดิม $(mval primary_ip) / เครื่องนี้ $(hostname -I | awk '{print $1}')"
}

# ══════════════════════ คำสั่ง ══════════════════════
CMD="${1:-}"
case "$CMD" in
  fingerprint) key_fp ;;
  servers) src_ls_dirs "" | grep '^server-' ;;
  snapshots)
    [ -n "$SERVER" ] || die "ระบุเครื่อง"
    for kind in full daily; do
      for s in $(src_ls_dirs "$SERVER/$kind"); do
        info="$(src_cat "$SERVER/$kind/$s/_snapshot.txt" 2>/dev/null | tr '\n' '|')"
        printf '%s/%s\t%s\n' "$kind" "$s" "$info"
      done
    done ;;
  plan)
    [ -n "$SERVER" ] && [ -n "$SNAP" ] || die "ระบุเครื่องและช่อง"
    need_manifest
    cat "$M/host.env"
    echo "components=$(src_ls_dirs "$BASE" | tr '\n' ' ')"
    echo "db_count=$(src_ls_files "$BASE/db" 2>/dev/null | grep -c . )"
    echo "site_count=$(src_ls_files "$BASE/sites" 2>/dev/null | grep -c . )"
    echo "home_count=$(src_ls_files "$BASE/homes" 2>/dev/null | grep -c . )"
    echo "package_count=$(wc -l < "$M/packages-manual.txt")"
    echo "container_count=$(grep -c . "$M/docker-ps.tsv" 2>/dev/null || echo 0)"
    for s in $STEPS_ORDER; do applicable "$s" && echo "step=$s"; done ;;
  step)
    s="${4:-}"; [ -n "$s" ] || { s="$SNAP"; }
    # รูปแบบ: step <ขั้น> <เครื่อง> <ช่อง>
    STEP="$2"; SERVER="$3"; SNAP="$4"; BASE="$SERVER/$SNAP"
    M="$WORK/$SERVER-${SNAP//\//-}/manifest"; ETC_SNAP="$WORK/$SERVER-${SNAP//\//-}/etc"
    [ -n "$STEP" ] && [ -n "$SERVER" ] && [ -n "$SNAP" ] || die "ใช้: step <ขั้น> <เครื่อง> <ช่อง>"
    [ -r "${PRIVATE_KEY:-}" ] || die "ยังไม่มีลูกกุญแจ"
    mkdir -p "$WORK" "$(T "$REF_DIR")"
    sandbox && say "🧪 โหมดทดสอบ — เขียนลง $TARGET_ROOT แทน / และไม่แตะระบบจริง"
    fn="step_${STEP//-/_}"
    declare -F "$fn" >/dev/null || die "ไม่รู้จักขั้น $STEP"
    "$fn" ;;
  *) sed -n '2,20p' "$0"; exit 2 ;;
esac
