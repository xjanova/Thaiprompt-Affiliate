#!/bin/bash

# ============================================================
# TP-Affiliate Disk Cleanup Script
# ============================================================
# สคริปต์ทำความสะอาดพื้นที่ดิสก์อัตโนมัติ
# ใช้เมื่อพื้นที่ดิสก์เต็มหรือต้องการ free พื้นที่
#
# การใช้งาน:
#   ./cleanup-disk.sh          # รันปกติ (ถามยืนยันก่อนลบ)
#   ./cleanup-disk.sh --force  # รันโดยไม่ถามยืนยัน
#   ./cleanup-disk.sh --dry-run # แสดงสิ่งที่จะลบโดยไม่ลบจริง
# ============================================================

set -e

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BACKUP_DIR="$SCRIPT_DIR/backups"
LOG_DIR="$SCRIPT_DIR/storage/logs"
CACHE_DIR="$SCRIPT_DIR/storage/framework"

# จำนวน backups ที่เก็บไว้
KEEP_BACKUPS=2
# จำนวนวันที่เก็บ logs
KEEP_LOGS_DAYS=7

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'
BOLD='\033[1m'

# Parse arguments
FORCE_MODE=false
DRY_RUN=false

for arg in "$@"; do
    case $arg in
        --force|-f)
            FORCE_MODE=true
            ;;
        --dry-run|-n)
            DRY_RUN=true
            ;;
        --help|-h)
            echo "Usage: $0 [OPTIONS]"
            echo ""
            echo "Options:"
            echo "  --force, -f     รันโดยไม่ถามยืนยัน"
            echo "  --dry-run, -n   แสดงสิ่งที่จะลบโดยไม่ลบจริง"
            echo "  --help, -h      แสดงข้อความช่วยเหลือนี้"
            exit 0
            ;;
    esac
done

# Functions
print_header() {
    echo ""
    echo -e "${CYAN}${BOLD}════════════════════════════════════════════════════════════${NC}"
    echo -e "${CYAN}${BOLD}  $1${NC}"
    echo -e "${CYAN}${BOLD}════════════════════════════════════════════════════════════${NC}"
    echo ""
}

print_success() {
    echo -e "${GREEN}✓${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

print_info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

# แปลงขนาดไฟล์เป็น human readable
human_size() {
    local size=$1
    if [ $size -ge 1073741824 ]; then
        echo "$(echo "scale=2; $size/1073741824" | bc) GB"
    elif [ $size -ge 1048576 ]; then
        echo "$(echo "scale=2; $size/1048576" | bc) MB"
    elif [ $size -ge 1024 ]; then
        echo "$(echo "scale=2; $size/1024" | bc) KB"
    else
        echo "$size bytes"
    fi
}

# คำนวณขนาด directory
get_dir_size() {
    local dir=$1
    if [ -d "$dir" ]; then
        du -sb "$dir" 2>/dev/null | cut -f1 || echo "0"
    else
        echo "0"
    fi
}

# Header
print_header "🧹 TP-Affiliate Disk Cleanup Script"

if [ "$DRY_RUN" = true ]; then
    echo -e "${YELLOW}${BOLD}⚠️  DRY RUN MODE - ไม่มีการลบไฟล์จริง${NC}"
    echo ""
fi

# แสดงพื้นที่ดิสก์ก่อนทำความสะอาด
echo -e "${BOLD}📊 พื้นที่ดิสก์ก่อนทำความสะอาด:${NC}"
df -h "$SCRIPT_DIR" | tail -1 | awk '{print "  Used: " $3 " / " $2 " (" $5 " used)"}'
echo ""

# Track total freed space
TOTAL_FREED=0

# ============================================================
# 1. ลบ Database Backups เก่า
# ============================================================
print_header "1️⃣ ลบ Database Backups เก่า"

if [ -d "$BACKUP_DIR" ]; then
    # นับจำนวน SQL backups
    SQL_COUNT=$(find "$BACKUP_DIR" -maxdepth 1 -name "*.sql" 2>/dev/null | wc -l)

    if [ "$SQL_COUNT" -gt "$KEEP_BACKUPS" ]; then
        # หา backups ที่จะลบ (เก็บไว้แค่ KEEP_BACKUPS ไฟล์ล่าสุด)
        TO_DELETE=$((SQL_COUNT - KEEP_BACKUPS))

        print_info "พบ SQL backups: $SQL_COUNT ไฟล์ (เก็บไว้ $KEEP_BACKUPS ไฟล์ล่าสุด)"
        print_warning "จะลบ: $TO_DELETE ไฟล์"

        # คำนวณขนาดที่จะลบ
        SIZE_TO_FREE=$(ls -lt "$BACKUP_DIR"/*.sql 2>/dev/null | tail -n +$((KEEP_BACKUPS + 1)) | awk '{sum += $5} END {print sum}')
        SIZE_TO_FREE=${SIZE_TO_FREE:-0}

        echo ""
        echo "  ไฟล์ที่จะลบ:"
        ls -lht "$BACKUP_DIR"/*.sql 2>/dev/null | tail -n +$((KEEP_BACKUPS + 1)) | awk '{print "    - " $9 " (" $5 ")"}'
        echo ""

        if [ "$DRY_RUN" = false ]; then
            ls -t "$BACKUP_DIR"/*.sql 2>/dev/null | tail -n +$((KEEP_BACKUPS + 1)) | xargs rm -f
            print_success "ลบ SQL backups เก่าแล้ว (freed: $(human_size $SIZE_TO_FREE))"
            TOTAL_FREED=$((TOTAL_FREED + SIZE_TO_FREE))
        else
            print_info "[DRY RUN] จะ free: $(human_size $SIZE_TO_FREE)"
        fi
    else
        print_success "SQL backups อยู่ในเกณฑ์ ($SQL_COUNT ไฟล์)"
    fi

    # ลบ critical backup directories เก่า
    CRITICAL_COUNT=$(find "$BACKUP_DIR" -maxdepth 1 -type d -name "critical_*" 2>/dev/null | wc -l)

    if [ "$CRITICAL_COUNT" -gt "$KEEP_BACKUPS" ]; then
        TO_DELETE=$((CRITICAL_COUNT - KEEP_BACKUPS))

        print_info "พบ critical backups: $CRITICAL_COUNT folders (เก็บไว้ $KEEP_BACKUPS folders ล่าสุด)"
        print_warning "จะลบ: $TO_DELETE folders"

        # คำนวณขนาดที่จะลบ
        SIZE_TO_FREE=0
        for dir in $(ls -dt "$BACKUP_DIR"/critical_* 2>/dev/null | tail -n +$((KEEP_BACKUPS + 1))); do
            dir_size=$(get_dir_size "$dir")
            SIZE_TO_FREE=$((SIZE_TO_FREE + dir_size))
        done

        echo ""
        echo "  Folders ที่จะลบ:"
        ls -dt "$BACKUP_DIR"/critical_* 2>/dev/null | tail -n +$((KEEP_BACKUPS + 1)) | while read dir; do
            echo "    - $(basename "$dir") ($(du -sh "$dir" 2>/dev/null | cut -f1))"
        done
        echo ""

        if [ "$DRY_RUN" = false ]; then
            ls -dt "$BACKUP_DIR"/critical_* 2>/dev/null | tail -n +$((KEEP_BACKUPS + 1)) | xargs rm -rf
            print_success "ลบ critical backups เก่าแล้ว (freed: $(human_size $SIZE_TO_FREE))"
            TOTAL_FREED=$((TOTAL_FREED + SIZE_TO_FREE))
        else
            print_info "[DRY RUN] จะ free: $(human_size $SIZE_TO_FREE)"
        fi
    else
        print_success "Critical backups อยู่ในเกณฑ์ ($CRITICAL_COUNT folders)"
    fi

    # ลบ pre_migration และ pre_autofix backups เก่า
    for prefix in "pre_migration_" "pre_autofix_"; do
        PREFIX_COUNT=$(find "$BACKUP_DIR" -maxdepth 1 -name "${prefix}*.sql" 2>/dev/null | wc -l)
        if [ "$PREFIX_COUNT" -gt 2 ]; then
            SIZE_TO_FREE=$(ls -lt "$BACKUP_DIR"/${prefix}*.sql 2>/dev/null | tail -n +3 | awk '{sum += $5} END {print sum}')
            SIZE_TO_FREE=${SIZE_TO_FREE:-0}

            if [ "$DRY_RUN" = false ]; then
                ls -t "$BACKUP_DIR"/${prefix}*.sql 2>/dev/null | tail -n +3 | xargs rm -f
                print_success "ลบ ${prefix} backups เก่าแล้ว (freed: $(human_size $SIZE_TO_FREE))"
                TOTAL_FREED=$((TOTAL_FREED + SIZE_TO_FREE))
            else
                print_info "[DRY RUN] ${prefix} backups: จะ free $(human_size $SIZE_TO_FREE)"
            fi
        fi
    done
else
    print_info "ไม่พบ backup directory"
fi

# ============================================================
# 2. ลบ Log Files เก่า
# ============================================================
print_header "2️⃣ ลบ Log Files เก่า"

if [ -d "$LOG_DIR" ]; then
    # ลบ logs เก่ากว่า KEEP_LOGS_DAYS วัน
    OLD_LOGS=$(find "$LOG_DIR" -type f -name "*.log" -mtime +$KEEP_LOGS_DAYS 2>/dev/null)
    OLD_LOGS_COUNT=$(echo "$OLD_LOGS" | grep -c "." 2>/dev/null || echo "0")

    if [ "$OLD_LOGS_COUNT" -gt 0 ] && [ -n "$OLD_LOGS" ]; then
        SIZE_TO_FREE=0
        for log in $OLD_LOGS; do
            if [ -f "$log" ]; then
                file_size=$(stat -f%z "$log" 2>/dev/null || stat -c%s "$log" 2>/dev/null || echo "0")
                SIZE_TO_FREE=$((SIZE_TO_FREE + file_size))
            fi
        done

        print_warning "พบ log files เก่ากว่า $KEEP_LOGS_DAYS วัน: $OLD_LOGS_COUNT ไฟล์"

        if [ "$DRY_RUN" = false ]; then
            find "$LOG_DIR" -type f -name "*.log" -mtime +$KEEP_LOGS_DAYS -delete 2>/dev/null
            print_success "ลบ log files เก่าแล้ว (freed: $(human_size $SIZE_TO_FREE))"
            TOTAL_FREED=$((TOTAL_FREED + SIZE_TO_FREE))
        else
            print_info "[DRY RUN] จะ free: $(human_size $SIZE_TO_FREE)"
        fi
    else
        print_success "ไม่มี log files เก่ากว่า $KEEP_LOGS_DAYS วัน"
    fi

    # Truncate laravel.log ถ้าใหญ่กว่า 100MB
    LARAVEL_LOG="$LOG_DIR/laravel.log"
    if [ -f "$LARAVEL_LOG" ]; then
        LOG_SIZE=$(stat -f%z "$LARAVEL_LOG" 2>/dev/null || stat -c%s "$LARAVEL_LOG" 2>/dev/null || echo "0")
        if [ "$LOG_SIZE" -gt 104857600 ]; then  # 100MB
            print_warning "laravel.log มีขนาดใหญ่: $(human_size $LOG_SIZE)"

            if [ "$DRY_RUN" = false ]; then
                # เก็บ 1000 บรรทัดสุดท้าย
                tail -1000 "$LARAVEL_LOG" > "$LARAVEL_LOG.tmp" && mv "$LARAVEL_LOG.tmp" "$LARAVEL_LOG"
                NEW_SIZE=$(stat -f%z "$LARAVEL_LOG" 2>/dev/null || stat -c%s "$LARAVEL_LOG" 2>/dev/null || echo "0")
                FREED=$((LOG_SIZE - NEW_SIZE))
                print_success "Truncated laravel.log (freed: $(human_size $FREED))"
                TOTAL_FREED=$((TOTAL_FREED + FREED))
            else
                print_info "[DRY RUN] จะ truncate laravel.log"
            fi
        else
            print_success "laravel.log ขนาดปกติ ($(human_size $LOG_SIZE))"
        fi
    fi

    # ลบ deployment.log ถ้าใหญ่กว่า 50MB
    DEPLOY_LOG="$LOG_DIR/deployment.log"
    if [ -f "$DEPLOY_LOG" ]; then
        LOG_SIZE=$(stat -f%z "$DEPLOY_LOG" 2>/dev/null || stat -c%s "$DEPLOY_LOG" 2>/dev/null || echo "0")
        if [ "$LOG_SIZE" -gt 52428800 ]; then  # 50MB
            print_warning "deployment.log มีขนาดใหญ่: $(human_size $LOG_SIZE)"

            if [ "$DRY_RUN" = false ]; then
                tail -500 "$DEPLOY_LOG" > "$DEPLOY_LOG.tmp" && mv "$DEPLOY_LOG.tmp" "$DEPLOY_LOG"
                NEW_SIZE=$(stat -f%z "$DEPLOY_LOG" 2>/dev/null || stat -c%s "$DEPLOY_LOG" 2>/dev/null || echo "0")
                FREED=$((LOG_SIZE - NEW_SIZE))
                print_success "Truncated deployment.log (freed: $(human_size $FREED))"
                TOTAL_FREED=$((TOTAL_FREED + FREED))
            else
                print_info "[DRY RUN] จะ truncate deployment.log"
            fi
        fi
    fi
else
    print_info "ไม่พบ log directory"
fi

# ============================================================
# 3. ลบ Laravel Cache
# ============================================================
print_header "3️⃣ ลบ Laravel Cache"

# Cache data
CACHE_DATA_DIR="$CACHE_DIR/cache/data"
if [ -d "$CACHE_DATA_DIR" ]; then
    SIZE_BEFORE=$(get_dir_size "$CACHE_DATA_DIR")

    if [ "$SIZE_BEFORE" -gt 0 ]; then
        print_info "Cache data size: $(human_size $SIZE_BEFORE)"

        if [ "$DRY_RUN" = false ]; then
            rm -rf "$CACHE_DATA_DIR"/*
            print_success "ลบ cache data แล้ว (freed: $(human_size $SIZE_BEFORE))"
            TOTAL_FREED=$((TOTAL_FREED + SIZE_BEFORE))
        else
            print_info "[DRY RUN] จะ free: $(human_size $SIZE_BEFORE)"
        fi
    else
        print_success "Cache data ว่างเปล่า"
    fi
fi

# Compiled views
VIEWS_DIR="$CACHE_DIR/views"
if [ -d "$VIEWS_DIR" ]; then
    SIZE_BEFORE=$(get_dir_size "$VIEWS_DIR")

    if [ "$SIZE_BEFORE" -gt 1048576 ]; then  # > 1MB
        print_info "Compiled views size: $(human_size $SIZE_BEFORE)"

        if [ "$DRY_RUN" = false ]; then
            rm -rf "$VIEWS_DIR"/*
            print_success "ลบ compiled views แล้ว (freed: $(human_size $SIZE_BEFORE))"
            TOTAL_FREED=$((TOTAL_FREED + SIZE_BEFORE))
        else
            print_info "[DRY RUN] จะ free: $(human_size $SIZE_BEFORE)"
        fi
    else
        print_success "Compiled views ขนาดปกติ ($(human_size $SIZE_BEFORE))"
    fi
fi

# Sessions (เก็บไว้แค่ sessions ไม่เกิน 7 วัน)
SESSIONS_DIR="$CACHE_DIR/sessions"
if [ -d "$SESSIONS_DIR" ]; then
    OLD_SESSIONS=$(find "$SESSIONS_DIR" -type f -mtime +7 2>/dev/null | wc -l)

    if [ "$OLD_SESSIONS" -gt 0 ]; then
        SIZE_TO_FREE=0
        for session in $(find "$SESSIONS_DIR" -type f -mtime +7 2>/dev/null); do
            file_size=$(stat -f%z "$session" 2>/dev/null || stat -c%s "$session" 2>/dev/null || echo "0")
            SIZE_TO_FREE=$((SIZE_TO_FREE + file_size))
        done

        print_warning "พบ sessions เก่า: $OLD_SESSIONS ไฟล์"

        if [ "$DRY_RUN" = false ]; then
            find "$SESSIONS_DIR" -type f -mtime +7 -delete 2>/dev/null
            print_success "ลบ sessions เก่าแล้ว (freed: $(human_size $SIZE_TO_FREE))"
            TOTAL_FREED=$((TOTAL_FREED + SIZE_TO_FREE))
        else
            print_info "[DRY RUN] จะ free: $(human_size $SIZE_TO_FREE)"
        fi
    else
        print_success "ไม่มี sessions เก่า"
    fi
fi

# Bootstrap cache
BOOTSTRAP_CACHE="$SCRIPT_DIR/bootstrap/cache"
if [ -d "$BOOTSTRAP_CACHE" ]; then
    SIZE_BEFORE=$(get_dir_size "$BOOTSTRAP_CACHE")

    if [ "$DRY_RUN" = false ]; then
        rm -f "$BOOTSTRAP_CACHE"/*.php 2>/dev/null
        print_success "ลบ bootstrap cache แล้ว"
    else
        print_info "[DRY RUN] จะลบ bootstrap cache files"
    fi
fi

# ============================================================
# 4. ลบ Temporary Files
# ============================================================
print_header "4️⃣ ลบ Temporary Files"

# .git/index.lock (ถ้ามี)
GIT_LOCK="$SCRIPT_DIR/.git/index.lock"
if [ -f "$GIT_LOCK" ]; then
    if [ "$DRY_RUN" = false ]; then
        rm -f "$GIT_LOCK"
        print_success "ลบ git index.lock แล้ว"
    else
        print_info "[DRY RUN] จะลบ git index.lock"
    fi
else
    print_success "ไม่มี git lock files"
fi

# Temporary deployment files
find /tmp -maxdepth 1 -name "migration_output_*.log" -mtime +1 -delete 2>/dev/null || true
find /tmp -maxdepth 1 -name "deploy_backup_path_*" -mtime +1 -delete 2>/dev/null || true
find /tmp -maxdepth 1 -name "new_env_vars_*.txt" -mtime +1 -delete 2>/dev/null || true
find /tmp -maxdepth 1 -name "env_backup_*.env" -mtime +1 -delete 2>/dev/null || true
print_success "ลบ temporary deployment files แล้ว"

# .seeder_checksums (ไม่จำเป็นต้องลบ แต่ถ้าใหญ่เกินไป)
SEEDER_CHECKSUMS="$SCRIPT_DIR/.seeder_checksums"
if [ -d "$SEEDER_CHECKSUMS" ]; then
    SIZE=$(get_dir_size "$SEEDER_CHECKSUMS")
    if [ "$SIZE" -gt 1048576 ]; then  # > 1MB
        if [ "$DRY_RUN" = false ]; then
            rm -rf "$SEEDER_CHECKSUMS"
            print_success "ลบ seeder checksums แล้ว (freed: $(human_size $SIZE))"
            TOTAL_FREED=$((TOTAL_FREED + SIZE))
        else
            print_info "[DRY RUN] จะลบ seeder checksums ($(human_size $SIZE))"
        fi
    fi
fi

# .composer.lock.checksum (ไม่จำเป็น)
if [ -f "$SCRIPT_DIR/.composer.lock.checksum" ]; then
    rm -f "$SCRIPT_DIR/.composer.lock.checksum" 2>/dev/null || true
fi

# ============================================================
# 5. Optional: ลบ node_modules/.cache และ vendor cache
# ============================================================
print_header "5️⃣ ลบ Development Caches (Optional)"

# node_modules/.cache
NODE_CACHE="$SCRIPT_DIR/node_modules/.cache"
if [ -d "$NODE_CACHE" ]; then
    SIZE=$(get_dir_size "$NODE_CACHE")
    if [ "$SIZE" -gt 0 ]; then
        print_info "node_modules/.cache size: $(human_size $SIZE)"

        if [ "$DRY_RUN" = false ]; then
            rm -rf "$NODE_CACHE"
            print_success "ลบ node_modules/.cache แล้ว (freed: $(human_size $SIZE))"
            TOTAL_FREED=$((TOTAL_FREED + SIZE))
        else
            print_info "[DRY RUN] จะ free: $(human_size $SIZE)"
        fi
    fi
else
    print_success "ไม่มี node_modules/.cache"
fi

# Vite cache
VITE_CACHE="$SCRIPT_DIR/node_modules/.vite"
if [ -d "$VITE_CACHE" ]; then
    SIZE=$(get_dir_size "$VITE_CACHE")
    if [ "$SIZE" -gt 0 ]; then
        if [ "$DRY_RUN" = false ]; then
            rm -rf "$VITE_CACHE"
            print_success "ลบ Vite cache แล้ว (freed: $(human_size $SIZE))"
            TOTAL_FREED=$((TOTAL_FREED + SIZE))
        else
            print_info "[DRY RUN] Vite cache: จะ free $(human_size $SIZE)"
        fi
    fi
fi

# ============================================================
# Summary
# ============================================================
print_header "📊 สรุปผลการทำความสะอาด"

echo -e "${BOLD}พื้นที่ดิสก์หลังทำความสะอาด:${NC}"
df -h "$SCRIPT_DIR" | tail -1 | awk '{print "  Used: " $3 " / " $2 " (" $5 " used)"}'
echo ""

if [ "$DRY_RUN" = true ]; then
    echo -e "${YELLOW}${BOLD}📝 DRY RUN - ไม่มีการลบไฟล์จริง${NC}"
    echo -e "   รันโดยไม่มี --dry-run เพื่อลบจริง"
else
    echo -e "${GREEN}${BOLD}✅ พื้นที่ที่ free ได้ทั้งหมด: $(human_size $TOTAL_FREED)${NC}"
fi

echo ""
echo -e "${BLUE}💡 Tips:${NC}"
echo "  • รัน script นี้เป็นประจำเพื่อป้องกันดิสก์เต็ม"
echo "  • ใช้ cron job: 0 2 * * * /path/to/cleanup-disk.sh --force"
echo "  • ถ้ายังไม่พอ ลองลบ node_modules แล้ว npm install ใหม่"
echo ""

exit 0
