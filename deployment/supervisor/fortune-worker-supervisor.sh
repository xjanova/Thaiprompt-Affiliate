#!/bin/bash
# Fortune Queue Worker Supervisor — Auto-scaling pool (1-8 workers)
#
# ⚠️ ไฟล์นี้ไม่ได้ถูกรันจากตำแหน่งนี้ — ตัวจริงติดตั้งอยู่ที่:
#      /home/admin/bin/fortune-worker-supervisor.sh   (เรียกโดย fortune-queue-worker.service)
#    เก็บสำเนาไว้ใน repo เพื่อให้มี version control + ให้คนอ่านโค้ดเห็นว่า worker อ่านคิวอะไรบ้าง
#    แก้ที่นี่แล้ว **ต้อง copy ขึ้นเซิร์ฟเวอร์เอง** + `sudo systemctl restart fortune-queue-worker.service`
#    (deploy.sh แค่ restart service ไม่ได้ sync ไฟล์นี้ให้)
#
# 🚦 (2026-08-22) ลำดับคิวสำคัญมาก — Laravel ไล่ตามลำดับที่ระบุใน --queue
#      tpix-default = งานของลูกค้าที่จ่ายเงินแล้ว (Deep/Celtic Q&A, chat, ทำนาย)
#      default      = งานทั่วไป
#      tpix-low     = งานฟรีที่ท่วมได้ (ProcessCommentEngagement) — ได้รันเฉพาะตอนสองคิวบนว่าง
#    ต้นตอ FTU-260822-P2391: ตอนทุกอย่างอยู่คิวเดียวกันแบบ FIFO คอมเมนต์ฟรี ~100 ตัว
#    (ตัวละ 7-11 วินาที) บล็อกคำถามลูกค้าที่จ่าย 39฿ นาน 12 นาที จน Pro Session ปิดไปก่อน

set -u
LARAVEL_DIR="/home/admin/domains/main.thaiprompt.online/public_html"
PHP_BIN="/usr/local/php83/bin/php"
QUEUE_KEY="tp_affiliate_database_queues:tpix-default"
QUEUE_KEY_LOW="tp_affiliate_database_queues:tpix-low"
QUEUES="tpix-default,default,tpix-low"
MAX_WORKERS=8
MIN_WORKERS=1
SCALE_INTERVAL=30
LOG_PREFIX="[supervisor]"

# Backlog → desired workers
THRESH_4=3000
THRESH_3=800
THRESH_2=100

declare -A WORKERS

spawn_worker() {
  local idx="$1"
  cd "$LARAVEL_DIR" || exit 1
  "$PHP_BIN" -d memory_limit=512M artisan queue:work redis \
    --queue="$QUEUES" --sleep=1 --tries=3 \
    --max-time=3600 --timeout=120 --memory=512 &
  WORKERS[$idx]=$!
  echo "$LOG_PREFIX spawned worker #$idx (pid=${WORKERS[$idx]})"
}

kill_worker() {
  local idx="$1"
  local pid="${WORKERS[$idx]:-}"
  if [ -n "$pid" ] && kill -0 "$pid" 2>/dev/null; then
    echo "$LOG_PREFIX stopping worker #$idx (pid=$pid)"
    kill -TERM "$pid" 2>/dev/null
  fi
  unset 'WORKERS[$idx]'
}

reap_dead() {
  for idx in "${!WORKERS[@]}"; do
    local pid="${WORKERS[$idx]}"
    if ! kill -0 "$pid" 2>/dev/null; then
      echo "$LOG_PREFIX worker #$idx (pid=$pid) exited"
      unset 'WORKERS[$idx]'
    fi
  done
}

# Returns ONLY a number on stdout. All logging to stderr.
#
# 🚦 (2026-08-22) นับ backlog รวมทั้ง tpix-default + tpix-low
#    ถ้านับแค่ tpix-default หลังแยกเลน ตัวเลขจะเป็น ~0 ตลอด → ไม่ scale ขึ้นเลย
#    → คอมเมนต์ค้างสะสมไม่มีวันหมด (เลนช้า ≠ เลนที่ปล่อยให้ตัน)
desired_count() {
  local llen llow result
  llen=$(redis-cli LLEN "$QUEUE_KEY" 2>/dev/null) || llen=0
  llen=${llen:-0}
  llow=$(redis-cli LLEN "$QUEUE_KEY_LOW" 2>/dev/null) || llow=0
  llow=${llow:-0}
  llen=$((llen + llow))
  if   [ "$llen" -gt "$THRESH_4" ]; then result=$MAX_WORKERS
  elif [ "$llen" -gt "$THRESH_3" ]; then result=3
  elif [ "$llen" -gt "$THRESH_2" ]; then result=2
  else                                   result=$MIN_WORKERS
  fi
  echo "$LOG_PREFIX backlog=$llen → desired=$result current=${#WORKERS[@]}" >&2
  printf '%s\n' "$result"
}

shutdown_all() {
  echo "$LOG_PREFIX received signal — terminating all workers"
  for idx in "${!WORKERS[@]}"; do
    kill -TERM "${WORKERS[$idx]}" 2>/dev/null
  done
  local waited=0
  while [ ${#WORKERS[@]} -gt 0 ] && [ $waited -lt 60 ]; do
    sleep 2; waited=$((waited+2)); reap_dead
  done
  for idx in "${!WORKERS[@]}"; do
    kill -KILL "${WORKERS[$idx]}" 2>/dev/null
  done
  exit 0
}

trap shutdown_all SIGTERM SIGINT

echo "$LOG_PREFIX starting (MIN=$MIN_WORKERS MAX=$MAX_WORKERS, interval=${SCALE_INTERVAL}s)"
spawn_worker 1

while true; do
  reap_dead
  desired=$(desired_count)

  # Scale up
  for i in $(seq 1 "$desired"); do
    if [ -z "${WORKERS[$i]:-}" ]; then
      spawn_worker "$i"
    fi
  done
  # Scale down
  for i in $(seq $((desired+1)) "$MAX_WORKERS"); do
    if [ -n "${WORKERS[$i]:-}" ]; then
      kill_worker "$i"
    fi
  done

  sleep $SCALE_INTERVAL
done
