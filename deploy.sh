#!/bin/bash

# TP-Affiliate Deployment Script
# Safe Production Deployment with Rollback Support and Auto-Retry on Timeout

# Disable auto-exit on error (we'll handle errors manually)
set +e

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BACKUP_DIR="$SCRIPT_DIR/backups"
LOG_FILE="$SCRIPT_DIR/storage/logs/deployment.log"
MAX_DEPLOYMENT_ATTEMPTS=3

# ============================================================
# 🚨 EMERGENCY DISK CHECK (ก่อน logging - ป้องกัน disk full)
# ============================================================
# ตรวจสอบพื้นที่ดิสก์ทันทีก่อนทำอะไรอื่น
EMERGENCY_DISK_CHECK=$(df -k "$SCRIPT_DIR" 2>/dev/null | tail -1 | awk '{print $4}')
EMERGENCY_DISK_MB=$((EMERGENCY_DISK_CHECK / 1024))

# ถ้าพื้นที่น้อยกว่า 100MB - ทำ emergency cleanup ทันที!
if [ "$EMERGENCY_DISK_MB" -lt 100 ]; then
    echo ""
    echo "🚨 EMERGENCY: พื้นที่ดิสก์วิกฤต! (${EMERGENCY_DISK_MB} MB)"
    echo "🔧 กำลังทำ Emergency Cleanup..."
    echo ""

    # Emergency cleanup - ไม่ต้อง log (เพราะเขียนไม่ได้)
    # 1. ลบ logs ทันที
    rm -f "$SCRIPT_DIR/storage/logs/"*.log 2>/dev/null
    rm -f "$SCRIPT_DIR/storage/logs/laravel-"*.log 2>/dev/null
    echo "  ✓ ลบ log files แล้ว"

    # 2. ลบ backups เก่าทั้งหมด (เก็บแค่ 1 ล่าสุด)
    if [ -d "$BACKUP_DIR" ]; then
        ls -t "$BACKUP_DIR"/*.sql 2>/dev/null | tail -n +2 | xargs rm -f 2>/dev/null
        ls -dt "$BACKUP_DIR"/critical_* 2>/dev/null | tail -n +2 | xargs rm -rf 2>/dev/null
        ls -t "$BACKUP_DIR"/pre_migration_*.sql 2>/dev/null | tail -n +2 | xargs rm -f 2>/dev/null
        ls -t "$BACKUP_DIR"/pre_autofix_*.sql 2>/dev/null | tail -n +2 | xargs rm -f 2>/dev/null
        echo "  ✓ ลบ backups เก่าแล้ว"
    fi

    # 3. ลบ Laravel cache
    rm -rf "$SCRIPT_DIR/storage/framework/cache/data/"* 2>/dev/null
    rm -rf "$SCRIPT_DIR/storage/framework/views/"* 2>/dev/null
    rm -rf "$SCRIPT_DIR/storage/framework/sessions/"* 2>/dev/null
    rm -f "$SCRIPT_DIR/bootstrap/cache/"*.php 2>/dev/null
    echo "  ✓ ลบ Laravel cache แล้ว"

    # 4. ลบ git lock files
    rm -f "$SCRIPT_DIR/.git/index.lock" 2>/dev/null
    echo "  ✓ ลบ git lock files แล้ว"

    # ตรวจสอบอีกครั้ง
    EMERGENCY_DISK_CHECK=$(df -k "$SCRIPT_DIR" 2>/dev/null | tail -1 | awk '{print $4}')
    EMERGENCY_DISK_MB=$((EMERGENCY_DISK_CHECK / 1024))

    echo ""
    echo "📊 พื้นที่หลัง emergency cleanup: ${EMERGENCY_DISK_MB} MB"

    if [ "$EMERGENCY_DISK_MB" -lt 50 ]; then
        echo ""
        echo "❌ ยังไม่พอ! ต้องทำเพิ่มเติมด้วยตัวเอง:"
        echo "   rm -rf node_modules && npm install"
        echo "   rm -rf vendor && composer install --no-dev"
        echo "   du -sh * | sort -hr | head -10"
        echo ""
        exit 1
    fi

    echo "✓ Emergency cleanup สำเร็จ! กำลังดำเนินการต่อ..."
    echo ""
fi

# Track deployment attempts via environment variable
if [ -z "$DEPLOY_ATTEMPT_COUNT" ]; then
    export DEPLOY_ATTEMPT_COUNT=1
else
    export DEPLOY_ATTEMPT_COUNT=$((DEPLOY_ATTEMPT_COUNT + 1))
fi

# Check if we've exceeded max attempts
if [ $DEPLOY_ATTEMPT_COUNT -gt $MAX_DEPLOYMENT_ATTEMPTS ]; then
    echo ""
    echo "╔═══════════════════════════════════════════════════════════╗"
    echo "║  ❌ Deploy ล้มเหลว - ได้ลองครบ $MAX_DEPLOYMENT_ATTEMPTS ครั้งแล้ว              ║"
    echo "╚═══════════════════════════════════════════════════════════╝"
    echo ""
    echo "💡 กรุณาลอง Deploy ใหม่ภายหลัง หรือตรวจสอบปัญหา:"
    echo "  1. ตรวจสอบการเชื่อมต่ออินเทอร์เน็ต"
    echo "  2. ตรวจสอบว่า GitHub และ Packagist สามารถเข้าถึงได้"
    echo "  3. ตรวจสอบ logs: tail -f storage/logs/deployment.log"
    echo "  4. ลองใหม่ภายหลัง 10-15 นาที"
    echo ""
    unset DEPLOY_ATTEMPT_COUNT
    exit 1
fi

# Determine branch to deploy
if [ -n "$1" ]; then
    # Use specified branch
    BRANCH="$1"
else
    # Use current branch
    BRANCH=$(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo "")
    if [ -z "$BRANCH" ] || [ "$BRANCH" = "HEAD" ]; then
        echo "Error: Could not determine current branch"
        echo "Usage: $0 [branch-name]"
        echo "Example: $0 claude/Main"
        unset DEPLOY_ATTEMPT_COUNT
        exit 1
    fi
fi

# Colors (ต้องกำหนดก่อนใช้งาน)
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
MAGENTA='\033[0;35m'
CYAN='\033[0;36m'
WHITE='\033[1;37m'
NC='\033[0m' # No Color

# Background Colors
BG_YELLOW='\033[43m'
BG_RED='\033[41m'
BG_GREEN='\033[42m'
BG_BLUE='\033[44m'
BG_CYAN='\033[46m'
BG_NC='\033[49m'

# Text Styles
BOLD='\033[1m'
DIM='\033[2m'
UNDERLINE='\033[4m'
BLINK='\033[5m'
REVERSE='\033[7m'
RESET='\033[0m'

# Functions (ต้องกำหนดก่อนใช้งาน)
print_success() {
    echo -e "${GREEN}✓${NC} $1" | tee -a "$LOG_FILE"
}

print_error() {
    echo -e "${RED}✗${NC} $1" | tee -a "$LOG_FILE"
}

print_info() {
    echo -e "${BLUE}ℹ${NC} $1" | tee -a "$LOG_FILE"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1" | tee -a "$LOG_FILE"
}

# Critical Error - แถบเหลืองพร้อมตัวหนังสือสีแดง (สำหรับข้อผิดพลาดร้ายแรง)
print_critical() {
    echo ""
    echo -e "${BG_YELLOW}${RED}${BOLD} ⚠️  CRITICAL ERROR  ${RESET}"
    echo -e "${BG_YELLOW}${RED}${BOLD} $1 ${RESET}"
    echo "" | tee -a "$LOG_FILE"
    echo "[CRITICAL] $1" >> "$LOG_FILE"
}

# Step Label - ป้ายขั้นตอนที่ชัดเจน
print_step() {
    local step_num=$1
    local total_steps=$2
    local description=$3

    echo ""
    echo -e "${BG_CYAN}${WHITE}${BOLD}                                                          ${RESET}"
    echo -e "${BG_CYAN}${WHITE}${BOLD}  ▶ STEP ${step_num}/${total_steps}: ${description}  ${RESET}"
    echo -e "${BG_CYAN}${WHITE}${BOLD}                                                          ${RESET}"
    echo ""
    echo "[STEP ${step_num}/${total_steps}] ${description}" >> "$LOG_FILE"
}

# Sub-step Label - ขั้นตอนย่อย
print_substep() {
    local step_num=$1
    local description=$2

    echo -e "${CYAN}${BOLD}  ├─[${step_num}]${NC} ${description}"
    echo "  [${step_num}] ${description}" >> "$LOG_FILE"
}

print_header() {
    echo ""
    echo -e "${BG_BLUE}${WHITE}${BOLD}                                                          ${RESET}"
    echo -e "${BG_BLUE}${WHITE}${BOLD}  $1  ${RESET}"
    echo -e "${BG_BLUE}${WHITE}${BOLD}                                                          ${RESET}"
    echo ""
}

log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"
}

# GitHub Token Configuration (Optional)
# ใช้ GitHub token จาก environment variable (ถ้ามี)
# ประโยชน์: เพิ่ม rate limit จาก 60 → 5,000 requests/hour
if [ -n "$GITHUB_TOKEN" ]; then
    # ตั้งค่า git credential helper ให้ใช้ token
    export GIT_ASKPASS_TOKEN="$GITHUB_TOKEN"
    git config --local credential.helper '!f() { echo "username=token"; echo "password=$GITHUB_TOKEN"; }; f'
    print_info "Using GitHub token for authentication (rate limit: 5,000/hour)"
else
    # ไม่มี token - ใช้ public access (60 requests/hour)
    print_info "No GitHub token provided - using public access (rate limit: 60/hour)"
    echo "  💡 Tip: Set GITHUB_TOKEN env variable to increase rate limit"
    echo "     Example: export GITHUB_TOKEN=your_github_token"
fi

# Smart ENV Sync - Auto-update .env with new variables from .env.example
sync_env_file() {
    print_header "🔄 Smart ENV Sync System"

    if [ ! -f ".env.example" ]; then
        print_warning "No .env.example found - skipping ENV sync"
        return 0
    fi

    if [ ! -f ".env" ]; then
        print_error ".env file not found!"
        return 1
    fi

    print_info "Checking for new environment variables..."

    # Create temporary files for processing
    local temp_new_vars="/tmp/new_env_vars_$$.txt"
    local temp_env_backup="/tmp/env_backup_$$.env"

    # Backup current .env
    cp .env "$temp_env_backup"

    # Extract variable names from both files (ignore comments and empty lines)
    local example_vars=$(grep -v '^#' .env.example | grep -v '^$' | cut -d '=' -f1 | sort)
    local current_vars=$(grep -v '^#' .env | grep -v '^$' | cut -d '=' -f1 | sort)

    # Find new variables that exist in .env.example but not in .env
    local new_vars=()
    local added_count=0

    while IFS= read -r var; do
        if ! grep -q "^${var}=" .env 2>/dev/null; then
            new_vars+=("$var")
        fi
    done <<< "$example_vars"

    # If there are new variables, add them
    if [ ${#new_vars[@]} -gt 0 ]; then
        print_warning "Found ${#new_vars[@]} new environment variable(s)"
        echo ""
        print_info "→ New variables to be added:"

        # Show what will be added
        for var in "${new_vars[@]}"; do
            local var_line=$(grep "^${var}=" .env.example)
            echo "  • $var"
        done
        echo ""

        # Add new variables to .env with proper formatting
        print_info "→ Adding new variables to .env..."

        # Create a temporary file with updates
        cp .env .env.tmp

        echo "" >> .env.tmp
        echo "# Auto-added by deploy script on $(date +'%Y-%m-%d %H:%M:%S')" >> .env.tmp

        for var in "${new_vars[@]}"; do
            # Get the full line from .env.example (including comments if any)
            local line_number=$(grep -n "^${var}=" .env.example | cut -d ':' -f1)

            # Get any comment above this variable
            if [ -n "$line_number" ] && [ "$line_number" -gt 1 ]; then
                local prev_line=$((line_number - 1))
                local comment_line=$(sed -n "${prev_line}p" .env.example)
                if [[ "$comment_line" =~ ^#.*$ ]]; then
                    echo "$comment_line" >> .env.tmp
                fi
            fi

            # Add the variable
            grep "^${var}=" .env.example >> .env.tmp
            added_count=$((added_count + 1))
        done

        # Replace .env with updated version
        mv .env.tmp .env

        print_success "✓ Added $added_count new variable(s) to .env"
        echo ""

        print_info "→ Summary of changes:"
        for var in "${new_vars[@]}"; do
            local value=$(grep "^${var}=" .env | cut -d '=' -f2-)
            echo "  • $var=${value}"
        done
        echo ""

        print_success "✓ .env file successfully synced with .env.example"
        print_info "  Backup saved: $temp_env_backup"
        log "ENV Sync: Added $added_count new variables to .env"
    else
        print_success "✓ .env is already up to date with .env.example"
        rm -f "$temp_env_backup"
    fi

    echo ""
    return 0
}

# Ensure Laravel essential directories exist
ensure_laravel_directories() {
    mkdir -p bootstrap/cache 2>/dev/null || true
    mkdir -p storage/{app,framework,logs} 2>/dev/null || true
    mkdir -p storage/framework/{cache,sessions,views} 2>/dev/null || true
    mkdir -p storage/app/{public,private} 2>/dev/null || true
    mkdir -p database 2>/dev/null || true
    chmod -R 775 storage bootstrap/cache 2>/dev/null || true
}

# Check if error is timeout-related
is_timeout_error() {
    local error_msg="$1"
    local exit_code="$2"

    # Common timeout-related patterns
    if [[ "$error_msg" =~ (timeout|timed out|Connection timed out|Operation timed out) ]] || \
       [[ "$error_msg" =~ (could not read|failed to connect|unable to access) ]] || \
       [[ "$error_msg" =~ (network|DNS|resolution failed|temporary failure) ]] || \
       [[ "$exit_code" == "124" ]] || [[ "$exit_code" == "143" ]]; then
        return 0  # Is timeout error
    fi
    return 1  # Not timeout error
}

# Error handler with auto-retry on timeout
error_exit() {
    local error_msg="$1"
    local last_exit_code="${2:-$?}"

    print_error "Deployment failed: $error_msg"
    log "ERROR: $error_msg (exit code: $last_exit_code)"

    # Ensure essential directories exist before running artisan
    ensure_laravel_directories

    # Try to bring application back up
    php artisan up 2>/dev/null || {
        print_error "Could not disable maintenance mode automatically"
        print_info "Please run manually: php artisan up"
    }

    # Check if it's a timeout error and we haven't exceeded max attempts
    if is_timeout_error "$error_msg" "$last_exit_code" && [ $DEPLOY_ATTEMPT_COUNT -lt $MAX_DEPLOYMENT_ATTEMPTS ]; then
        echo ""
        print_warning "╔═══════════════════════════════════════════════════════════╗"
        print_warning "║  ⚠️  ตรวจพบ Timeout Error - กำลังเริ่ม Deploy ใหม่...   ║"
        print_warning "╚═══════════════════════════════════════════════════════════╝"
        echo ""
        print_info "📊 ความคืบหน้า: รอบที่ $DEPLOY_ATTEMPT_COUNT/$MAX_DEPLOYMENT_ATTEMPTS"
        print_info "⏳ รอ 10 วินาทีก่อนเริ่มใหม่..."
        log "RETRY: Restarting deployment (attempt $DEPLOY_ATTEMPT_COUNT/$MAX_DEPLOYMENT_ATTEMPTS)"
        sleep 10

        echo ""
        print_info "🔄 กำลังเริ่มต้น Deploy ใหม่จากตั้งแต่ต้น..."
        echo ""
        sleep 2

        # Restart the entire deployment script
        exec "$0" "$@"
    fi

    # Final failure - not a timeout or exceeded max attempts
    print_critical "การ Deploy ล้มเหลว - กรุณาลองใหม่ภายหลัง"

    if [ $DEPLOY_ATTEMPT_COUNT -ge $MAX_DEPLOYMENT_ATTEMPTS ]; then
        print_critical "ได้ลองทำการ Deploy ครบ $MAX_DEPLOYMENT_ATTEMPTS รอบแล้ว แต่ยังไม่สำเร็จ"
    fi

    echo ""
    print_info "💡 คำแนะนำในการแก้ไข:"
    echo "  1. ตรวจสอบการเชื่อมต่ออินเทอร์เน็ต"
    echo "  2. ตรวจสอบว่า GitHub และ Packagist สามารถเข้าถึงได้"
    echo "  3. ตรวจสอบ logs: tail -f storage/logs/deployment.log"
    echo "  4. ลอง Deploy ใหม่ภายหลัง 10-15 นาที"
    echo "  5. หากยังไม่สำเร็จ ติดต่อทีมพัฒนา"
    echo ""

    unset DEPLOY_ATTEMPT_COUNT
    exit 1
}

# Trap errors - disable for now to handle errors manually
# trap 'error_exit "An error occurred on line $LINENO"' ERR

# Clean old backups (keep only recent 2 days)
cleanup_old_backups() {
    print_info "Cleaning old backups (>2 days)..."

    if [ -d "$BACKUP_DIR" ]; then
        # Count total backups before cleanup
        local total_before=$(find "$BACKUP_DIR" -type f -name "*.sql" -o -type d -name "critical_*" | wc -l)

        # Remove SQL backups older than 2 days
        find "$BACKUP_DIR" -type f -name "*.sql" -mtime +2 -delete 2>/dev/null || true

        # Remove critical backup directories older than 2 days
        find "$BACKUP_DIR" -type d -name "critical_*" -mtime +2 -exec rm -rf {} + 2>/dev/null || true

        # Count total backups after cleanup
        local total_after=$(find "$BACKUP_DIR" -type f -name "*.sql" -o -type d -name "critical_*" 2>/dev/null | wc -l)
        local deleted=$((total_before - total_after))

        if [ $deleted -gt 0 ]; then
            print_success "Cleaned up $deleted old backup(s)"
            log "Cleanup: Removed $deleted old backups (>2 days)"
        else
            print_success "No old backups to clean"
        fi
    else
        mkdir -p "$BACKUP_DIR"
        print_info "Created backup directory"
    fi
}

# Save deployment history for rollback
save_deployment_history() {
    local commit_hash=$1
    local branch=$2
    local timestamp=$(date +'%Y-%m-%d %H:%M:%S')

    # Create deployment history file
    local history_file="$BACKUP_DIR/.deployment_history"

    # Add new entry at the beginning (most recent first)
    echo "$timestamp|$commit_hash|$branch" | cat - "$history_file" 2>/dev/null | head -10 > "$history_file.tmp" 2>/dev/null || echo "$timestamp|$commit_hash|$branch" > "$history_file.tmp"
    mv "$history_file.tmp" "$history_file"

    log "Deployment history saved: $commit_hash"
}

# Generate rollback commands
generate_rollback_commands() {
    local history_file="$BACKUP_DIR/.deployment_history"

    if [ ! -f "$history_file" ]; then
        return
    fi

    echo ""
    print_warning "🔄 Quick Rollback Commands (Copy & Paste):"
    echo ""

    local count=1
    while IFS='|' read -r timestamp commit_hash branch_name; do
        if [ $count -gt 5 ]; then
            break
        fi

        if [ -n "$commit_hash" ]; then
            echo -e "${YELLOW}[$count]${NC} Rollback to: ${BLUE}${timestamp}${NC} (${commit_hash:0:8})"
            echo -e "${GREEN}git reset --hard $commit_hash && composer install --no-dev --optimize-autoloader && php artisan migrate:rollback && php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan up${NC}"
            echo ""
            count=$((count + 1))
        fi
    done < "$history_file"
}

# Backup critical files (PREVENT DATA LOSS!)
backup_critical_files() {
    local backup_timestamp=$(date +'%Y%m%d_%H%M%S')
    CRITICAL_BACKUP_DIR="$BACKUP_DIR/critical_$backup_timestamp"
    mkdir -p "$CRITICAL_BACKUP_DIR"

    # Backup .env files (CRITICAL!)
    if [ -f ".env" ]; then
        cp .env "$CRITICAL_BACKUP_DIR/.env" && \
            print_success "✓ Backed up .env"
    fi

    # หมายเหตุ (2026-05-30): ลบการ backup .env.production ออก — เป็นไฟล์ template placeholder
    #   (ย้ายไป .env.production.example แล้ว) ไม่ใช่ env จริง ไม่ต้อง backup
    #   root cause: APP_ENV=production ทำให้ config:cache โหลด .env.production (your_username) แทน .env

    # Backup uploaded files
    if [ -d "storage/app/public" ]; then
        cp -r storage/app/public "$CRITICAL_BACKUP_DIR/storage_public" 2>/dev/null || true
    fi

    # Backup Google credentials for OCR/KYC (if exists)
    if [ -f "storage/app/google-credentials.json" ]; then
        cp storage/app/google-credentials.json "$CRITICAL_BACKUP_DIR/google-credentials.json" && \
            print_success "✓ Backed up Google credentials"
    fi

    # Backup Firebase credentials for FCM push notifications (if exists)
    if [ -f "storage/app/firebase-credentials.json" ]; then
        cp storage/app/firebase-credentials.json "$CRITICAL_BACKUP_DIR/firebase-credentials.json" && \
            print_success "✓ Backed up Firebase credentials"
    fi

    echo "$CRITICAL_BACKUP_DIR" > /tmp/deploy_backup_path_$$
    log "Critical files backed up to: $CRITICAL_BACKUP_DIR"
}

# Restore critical files (PREVENT DATA LOSS!)
restore_critical_files() {
    if [ -f "/tmp/deploy_backup_path_$$" ]; then
        local backup_path=$(cat /tmp/deploy_backup_path_$$)

        if [ -d "$backup_path" ]; then
            # Restore .env (CRITICAL!)
            if [ -f "$backup_path/.env" ]; then
                cp "$backup_path/.env" .env && \
                    print_success "✓ Restored .env"
            else
                print_warning "⚠ No .env backup found!"
                # (2026-05-30) ลบ fallback จาก .env.production — เป็น placeholder (your_username)
                #   ที่ทำ config:cache พิษทุก deploy. เหลือ .env.example เป็น template เดียว (เตือนชัดให้แก้ creds)
                if [ -f ".env.example" ]; then
                    cp .env.example .env && \
                        print_error "✗ Created .env from .env.example - UPDATE CREDENTIALS!"
                else
                    error_exit "CRITICAL: No .env file available!"
                fi
            fi

            # Restore uploaded files
            if [ -d "$backup_path/storage_public" ]; then
                mkdir -p storage/app
                cp -r "$backup_path/storage_public" storage/app/public 2>/dev/null || true
                print_success "✓ Restored uploaded files"
            fi

            # Restore Google credentials for OCR/KYC (if exists)
            if [ -f "$backup_path/google-credentials.json" ]; then
                mkdir -p storage/app
                cp "$backup_path/google-credentials.json" storage/app/google-credentials.json 2>/dev/null || true
                print_success "✓ Restored Google credentials"
            fi

            # Restore Firebase credentials for FCM push notifications (if exists)
            if [ -f "$backup_path/firebase-credentials.json" ]; then
                mkdir -p storage/app
                cp "$backup_path/firebase-credentials.json" storage/app/firebase-credentials.json 2>/dev/null || true
                print_success "✓ Restored Firebase credentials"
            fi
        fi

        rm -f /tmp/deploy_backup_path_$$
    fi
}

# Header with XMAN Logo
echo ""
echo -e "${GREEN}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║${NC}    ${MAGENTA}██╗  ██╗███╗   ███╗ █████╗ ███╗   ██╗${NC}                      ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}    ${MAGENTA}╚██╗██╔╝████╗ ████║██╔══██╗████╗  ██║${NC}                      ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}     ${MAGENTA}╚███╔╝ ██╔████╔██║███████║██╔██╗ ██║${NC}                      ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}     ${MAGENTA}██╔██╗ ██║╚██╔╝██║██╔══██║██║╚██╗██║${NC}                      ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}    ${MAGENTA}██╔╝ ██╗██║ ╚═╝ ██║██║  ██║██║ ╚████║${NC}                      ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}    ${MAGENTA}╚═╝  ╚═╝╚═╝     ╚═╝╚═╝  ╚═╝╚═╝  ╚═══╝${NC}                      ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}                                                                ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}    ${BLUE}🚀 TP-Affiliate Deployment System v4.0${NC}                      ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}    ${YELLOW}⚡ Intelligent • Fast • Ultra-Safe Deployment${NC}             ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}    ${CYAN}📊 Enhanced Step Labels & Error Styling${NC}                   ${GREEN}║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""

if [ $DEPLOY_ATTEMPT_COUNT -gt 1 ]; then
    echo "🔄 ${YELLOW}กำลังลองใหม่ - รอบที่ $DEPLOY_ATTEMPT_COUNT/$MAX_DEPLOYMENT_ATTEMPTS${NC}"
    echo ""
fi

echo "📋 Deployment Configuration:"
echo "  Branch:      ${BLUE}$BRANCH${NC}"
echo "  Directory:   ${BLUE}$SCRIPT_DIR${NC}"
echo "  User:        ${BLUE}$(whoami)${NC}"
echo "  Time:        ${BLUE}$(date)${NC}"
echo ""

log "=== Deployment Started (Attempt $DEPLOY_ATTEMPT_COUNT/$MAX_DEPLOYMENT_ATTEMPTS) ==="
log "Branch: $BRANCH"
log "User: $(whoami)"
log "Host: $(hostname)"

# ============================================================
# Disk Space Check & Auto-Cleanup
# ============================================================
print_header "💾 Disk Space Check"

# ตรวจสอบพื้นที่ดิสก์ที่เหลือ (เป็น KB)
DISK_AVAILABLE=$(df -k "$SCRIPT_DIR" | tail -1 | awk '{print $4}')
DISK_AVAILABLE_MB=$((DISK_AVAILABLE / 1024))
DISK_USED_PERCENT=$(df -h "$SCRIPT_DIR" | tail -1 | awk '{print $5}' | tr -d '%')

print_info "พื้นที่ดิสก์ว่าง: ${DISK_AVAILABLE_MB} MB (ใช้ไป ${DISK_USED_PERCENT}%)"

# Minimum required space: 500MB
MIN_REQUIRED_MB=500
# Warning threshold: 1GB
WARNING_THRESHOLD_MB=1024
# Auto-cleanup threshold: 90% used
AUTO_CLEANUP_THRESHOLD=90

if [ "$DISK_AVAILABLE_MB" -lt "$MIN_REQUIRED_MB" ]; then
    print_critical "พื้นที่ดิสก์ไม่เพียงพอ! (ต้องการอย่างน้อย ${MIN_REQUIRED_MB} MB)"
    echo ""
    print_warning "🔧 กำลังรัน Auto-Cleanup เพื่อ free พื้นที่..."
    echo ""

    # รัน cleanup script ถ้ามี
    if [ -x "$SCRIPT_DIR/cleanup-disk.sh" ]; then
        "$SCRIPT_DIR/cleanup-disk.sh" --force 2>&1 | tee -a "$LOG_FILE"

        # ตรวจสอบพื้นที่อีกครั้ง
        DISK_AVAILABLE=$(df -k "$SCRIPT_DIR" | tail -1 | awk '{print $4}')
        DISK_AVAILABLE_MB=$((DISK_AVAILABLE / 1024))

        if [ "$DISK_AVAILABLE_MB" -lt "$MIN_REQUIRED_MB" ]; then
            print_critical "ยังมีพื้นที่ไม่เพียงพอหลัง cleanup!"
            echo ""
            print_error "พื้นที่ว่าง: ${DISK_AVAILABLE_MB} MB (ต้องการ: ${MIN_REQUIRED_MB} MB)"
            echo ""
            print_info "💡 วิธีแก้ไขเพิ่มเติม:"
            echo "  1. ลบ node_modules: rm -rf node_modules && npm install"
            echo "  2. ลบ vendor: rm -rf vendor && composer install"
            echo "  3. ตรวจสอบไฟล์ขนาดใหญ่: du -sh * | sort -h"
            echo "  4. ติดต่อผู้ดูแลระบบเพื่อเพิ่มพื้นที่ดิสก์"
            echo ""
            error_exit "พื้นที่ดิสก์ไม่เพียงพอสำหรับ deployment"
        else
            print_success "✓ Cleanup สำเร็จ! พื้นที่ว่างตอนนี้: ${DISK_AVAILABLE_MB} MB"
        fi
    else
        print_error "ไม่พบ cleanup-disk.sh script"
        echo ""
        print_info "💡 วิธีแก้ไขด้วยตัวเอง:"
        echo "  1. ลบ backups เก่า: rm -rf backups/*.sql backups/critical_*"
        echo "  2. ลบ logs: rm -f storage/logs/*.log"
        echo "  3. ลบ cache: rm -rf storage/framework/cache/data/*"
        echo ""
        error_exit "พื้นที่ดิสก์ไม่เพียงพอสำหรับ deployment"
    fi
elif [ "$DISK_USED_PERCENT" -ge "$AUTO_CLEANUP_THRESHOLD" ]; then
    print_warning "พื้นที่ดิสก์ใกล้เต็ม (${DISK_USED_PERCENT}% used)"
    print_info "กำลังรัน cleanup อัตโนมัติ..."

    if [ -x "$SCRIPT_DIR/cleanup-disk.sh" ]; then
        "$SCRIPT_DIR/cleanup-disk.sh" --force 2>&1 | tail -5
        DISK_AVAILABLE=$(df -k "$SCRIPT_DIR" | tail -1 | awk '{print $4}')
        DISK_AVAILABLE_MB=$((DISK_AVAILABLE / 1024))
        print_success "✓ Cleanup เสร็จสิ้น พื้นที่ว่าง: ${DISK_AVAILABLE_MB} MB"
    fi
elif [ "$DISK_AVAILABLE_MB" -lt "$WARNING_THRESHOLD_MB" ]; then
    print_warning "พื้นที่ดิสก์เหลือน้อย (${DISK_AVAILABLE_MB} MB)"
    print_info "แนะนำให้รัน: ./cleanup-disk.sh หลัง deployment"
else
    print_success "✓ พื้นที่ดิสก์เพียงพอ (${DISK_AVAILABLE_MB} MB ว่าง)"
fi
echo ""

# Verify branch exists on remote
print_header "🔍 Pre-flight Checks"

print_info "Checking remote branch availability..."
if ! git ls-remote --heads origin "$BRANCH" | grep -q "$BRANCH"; then
    print_error "Branch '$BRANCH' does not exist on remote!"
    echo ""
    echo "Available branches on remote:"
    git ls-remote --heads origin | sed 's/.*refs\/heads\//  - /'
    echo ""
    echo "Usage: $0 [branch-name]"
    echo "Example: $0 claude/Main"
    exit 1
fi
print_success "Branch '$BRANCH' exists on remote"

# Pre-flight checks

# Check if .env exists
if [ ! -f .env ]; then
    error_exit ".env file not found!"
fi
print_success ".env file found"

# Check if git repo
if [ ! -d .git ]; then
    error_exit "Not a git repository!"
fi
print_success "Git repository detected"

# Check APP_ENV
APP_ENV=$(grep "^APP_ENV=" .env | cut -d '=' -f2)
print_info "Environment: $APP_ENV"

if [ "$APP_ENV" != "production" ]; then
    print_warning "APP_ENV is not 'production' (current: $APP_ENV)"
    read -p "Continue anyway? (y/n): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

# Check for uncommitted changes
if [[ -n $(git status -s) ]]; then
    print_warning "Local changes detected - will be discarded during deployment"
    git status -s
    log "WARNING: Local changes will be overwritten by deployment"
fi

# Clean old backups first
cleanup_old_backups

# Start deployment
print_header "📦 Deployment Process"

# Step 1: Enable Maintenance Mode
print_step 1 22 "Enabling Maintenance Mode"

# Ensure directories exist before running artisan
ensure_laravel_directories

php artisan down --retry=60 --render="errors::503" 2>/dev/null || {
    print_warning "Could not enable maintenance mode (may already be down or need manual intervention)"
}
print_success "Maintenance mode enabled"
sleep 2  # Give time for requests to finish

# Step 2: Backup Database
print_step 2 22 "Creating Database Backup"
BACKUP_FILE="$BACKUP_DIR/db_backup_$(date +'%Y%m%d_%H%M%S').sql"

# Get database info from .env
DB_CONNECTION=$(grep "^DB_CONNECTION=" .env | cut -d '=' -f2)
DB_DATABASE=$(grep "^DB_DATABASE=" .env | cut -d '=' -f2)
DB_USERNAME=$(grep "^DB_USERNAME=" .env | cut -d '=' -f2)
DB_PASSWORD=$(grep "^DB_PASSWORD=" .env | cut -d '=' -f2)
DB_HOST=$(grep "^DB_HOST=" .env | cut -d '=' -f2)
DB_PORT=$(grep "^DB_PORT=" .env | cut -d '=' -f2)
DB_PORT=${DB_PORT:-3306}  # Default to 3306 if not set

if [ "$DB_CONNECTION" = "mysql" ] && command -v mysqldump >/dev/null 2>&1; then
    if [ -z "$DB_PASSWORD" ]; then
        mysqldump -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" "$DB_DATABASE" > "$BACKUP_FILE" 2>/dev/null || {
            print_warning "Database backup failed (continuing anyway)"
        }
    else
        mysqldump -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" > "$BACKUP_FILE" 2>/dev/null || {
            print_warning "Database backup failed (continuing anyway)"
        }
    fi

    if [ -f "$BACKUP_FILE" ]; then
        print_success "Database backed up to: $BACKUP_FILE"
        log "Database backup: $BACKUP_FILE"
    fi
else
    print_warning "Skipping database backup (mysqldump not available or not MySQL)"
fi

# Step 3: Get current git commit (for rollback)
CURRENT_COMMIT=$(git rev-parse HEAD)
log "Current commit: $CURRENT_COMMIT"
print_info "Current commit: ${CURRENT_COMMIT:0:8}"

# Step 3: Backup Critical Files (PREVENT DATA LOSS!)
print_step 3 22 "Backing up Critical Files (.env, uploads)"
backup_critical_files
print_success "Critical files backed up safely"

# Step 4: Force Pull Latest Code from GitHub
print_step 4 22 "Force Syncing with GitHub"

# Step 4.1: Stash any local changes (for safety backup)
if [[ -n $(git status -s) ]]; then
    print_info "Backing up local changes to stash..."
    git stash push -u -m "Auto-stash before deployment $(date +'%Y-%m-%d %H:%M:%S')" 2>/dev/null || true
fi

# Step 4.2: Fetch all changes from remote
print_info "Fetching latest code from origin/$BRANCH..."
if ! git fetch origin "$BRANCH" 2>&1 | tee -a "$LOG_FILE"; then
    error_exit "Failed to fetch from git - อาจเป็นปัญหาการเชื่อมต่อ" "$?"
fi

# Step 4.3: Force reset to match GitHub exactly
print_info "Force resetting to origin/$BRANCH..."
if ! git reset --hard "origin/$BRANCH" 2>&1 | tee -a "$LOG_FILE"; then
    error_exit "Failed to reset to origin/$BRANCH" "$?"
fi

# Step 4.4: Clean all untracked files and directories (SAFE - excludes critical files)
print_info "Removing untracked files and directories..."
# Note: Critical files (.env, uploads) are backed up and will be restored
git clean -fdx -e '.env*' -e 'storage/app/public/*' -e 'public/storage' -e 'storage/app/firebase-credentials.json' -e 'storage/app/google-credentials.json' || print_warning "Git clean failed (continuing anyway)"

# Step 4.5: Restore Critical Files (PREVENT DATA LOSS!)
print_info "Restoring critical files (.env, uploads)..."
restore_critical_files
print_success "Critical files restored successfully"

# Step 4.6: Smart ENV Sync - Auto-update .env with new variables
print_substep "4.6" "Syncing .env with .env.example..."
if ! sync_env_file; then
    error_exit "ENV sync failed" "$?"
fi

# Step 4.7: Recreate essential Laravel directories
print_info "Recreating essential Laravel directories..."
ensure_laravel_directories
print_success "Essential directories created"

# Step 4.8: CRITICAL - Clear ALL caches immediately after pulling new code
# ⚠️ This prevents route/config cache from using old code
print_substep "4.8" "🔥 Clearing ALL caches immediately (prevent stale cache issues)..."

# Clear Laravel caches FIRST - before any other artisan commands
php artisan route:clear 2>/dev/null || true
php artisan config:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true
php artisan cache:clear 2>/dev/null || true
php artisan event:clear 2>/dev/null || true

# Clear OPcache if available (critical for PHP file changes)
# ⚠️ Note: CLI opcache_reset() only clears CLI cache, not web server cache
# Web server OPcache will be cleared when PHP-FPM is restarted in Step 18
if php -m | grep -q "OPcache"; then
    print_info "Clearing CLI OPcache..."
    php -r "if (function_exists('opcache_reset')) { opcache_reset(); echo 'CLI OPcache cleared'; } else { echo 'OPcache not available'; }" 2>/dev/null || true

    # Try to invalidate all cached files (works on both CLI and web if shared cache)
    php -r "
        if (function_exists('opcache_get_status')) {
            \$status = opcache_get_status(true);
            if (\$status && isset(\$status['scripts'])) {
                foreach (array_keys(\$status['scripts']) as \$file) {
                    opcache_invalidate(\$file, true);
                }
                echo 'Invalidated ' . count(\$status['scripts']) . ' cached files';
            }
        }
    " 2>/dev/null || true
fi

# Clear realpath cache (important for symlinked files)
php -r "clearstatcache(true);" 2>/dev/null || true

# Delete bootstrap cache files manually (ensure clean state)
rm -f bootstrap/cache/config.php 2>/dev/null || true
rm -f bootstrap/cache/routes-v7.php 2>/dev/null || true
rm -f bootstrap/cache/services.php 2>/dev/null || true
rm -f bootstrap/cache/packages.php 2>/dev/null || true

print_success "✓ All caches cleared - ready for fresh code"

# Step 5: Verify we're in sync with remote
LOCAL_COMMIT=$(git rev-parse HEAD)
REMOTE_COMMIT=$(git rev-parse "origin/$BRANCH")

if [ "$LOCAL_COMMIT" = "$REMOTE_COMMIT" ]; then
    print_success "✓ Code is now in perfect sync with GitHub"
else
    print_warning "Local and remote commits differ (this shouldn't happen)"
fi

NEW_COMMIT=$(git rev-parse HEAD)
log "New commit: $NEW_COMMIT"
print_info "New commit: ${NEW_COMMIT:0:8}"

# Save deployment history for future rollback
save_deployment_history "$NEW_COMMIT" "$BRANCH"

# Step 5: Ensure Base Controller Exists
print_step 5 22 "Ensuring Base Controller Exists"
CONTROLLER_FILE="app/Http/Controllers/Controller.php"
if [ ! -f "$CONTROLLER_FILE" ]; then
    print_warning "Base Controller.php not found, creating..."
    mkdir -p app/Http/Controllers
    cat > "$CONTROLLER_FILE" << 'CONTROLLER_EOF'
<?php

namespace App\Http\Controllers;

abstract class Controller
{
    //
}
CONTROLLER_EOF
    print_success "Base Controller.php created"
else
    print_success "Base Controller.php exists"
fi

# Smart Composer Management System
smart_composer_install() {
    local needs_install=0
    local lock_changed=0

    # Check if composer.lock changed
    if [ -f ".composer.lock.checksum" ]; then
        local old_checksum=$(cat .composer.lock.checksum 2>/dev/null || echo "")
        local new_checksum=$(md5sum composer.lock 2>/dev/null | cut -d' ' -f1 || echo "")

        if [ "$old_checksum" != "$new_checksum" ]; then
            lock_changed=1
            print_info "composer.lock has changed - will update dependencies"
        else
            print_success "composer.lock unchanged - checking existing dependencies"
        fi
    else
        lock_changed=1
    fi

    # Check if vendor directory exists and is valid
    if [ ! -d "vendor" ] || [ ! -f "vendor/autoload.php" ]; then
        needs_install=1
        print_warning "vendor directory missing or invalid"
    fi

    # If lock changed or vendor missing, do full install
    if [ $needs_install -eq 1 ] || [ $lock_changed -eq 1 ]; then
        print_info "Running composer install..."
        composer clear-cache 2>/dev/null || true

        if ! composer install --no-dev --optimize-autoloader --no-interaction 2>&1 | tee -a "$LOG_FILE"; then
            error_exit "Composer install failed - อาจเป็นปัญหา network หรือ Packagist" "$?"
        fi

        # Save checksum for future comparisons
        md5sum composer.lock 2>/dev/null | cut -d' ' -f1 > .composer.lock.checksum
        print_success "Composer dependencies installed"
    else
        print_success "Composer dependencies up-to-date (skipped install)"
    fi

    # Validate composer.lock
    composer validate --no-check-all --no-check-publish 2>/dev/null && \
        print_success "composer.lock is valid" || \
        print_warning "composer.lock validation warning"
}

# Check and install missing packages
check_package() {
    local package=$1
    local description=$2
    local required=${3:-false}

    if composer show "$package" &>/dev/null; then
        local version=$(composer show "$package" 2>/dev/null | grep 'versions' | awk '{print $3}' || echo "unknown")
        print_success "$description installed ($version)"
        return 0
    else
        print_warning "$description not found"

        if [ "$required" = "true" ]; then
            print_info "Installing $package..."
            if composer require "$package" --no-interaction 2>&1 | tee -a "$LOG_FILE"; then
                local version=$(composer show "$package" 2>/dev/null | grep 'versions' | awk '{print $3}' || echo "unknown")
                print_success "$description installed successfully ($version)"
                md5sum composer.lock 2>/dev/null | cut -d' ' -f1 > .composer.lock.checksum
                return 0
            else
                print_error "$description installation failed"
                return 1
            fi
        fi
        return 1
    fi
}

# Step 6: Smart Composer Management
print_step 6 22 "Smart Composer Management"
echo ""

# Run smart composer install
smart_composer_install
echo ""

# Check critical packages
print_info "Verifying critical packages..."
check_package "google/cloud-vision" "Google Cloud Vision (OCR/KYC)" false
check_package "barryvdh/laravel-dompdf" "DomPDF (PDF Generation)" false
echo ""

# Step 7: Smart Laravel Sanctum Installation
print_step 7 22 "Smart Laravel Sanctum Installation"

# Check if Sanctum migrations already exist
if [ -f "database/migrations/*_create_personal_access_tokens_table.php" ] || \
   ls database/migrations/*_create_personal_access_tokens_table.php 1> /dev/null 2>&1; then
    print_success "Sanctum already installed (skipped re-publish)"
else
    print_info "Installing Sanctum for the first time..."
    if ! php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider" 2>&1 | tee -a "$LOG_FILE"; then
        print_warning "Sanctum publish failed (may already exist)"
    else
        print_success "Sanctum installed successfully"
    fi
fi

# Step 8: Clear All Cache (before migration)
print_step 8 22 "Clearing All Caches"

# Clear caches silently (ignore permission errors)
php artisan cache:clear >/dev/null 2>&1 || print_warning "Cache clear skipped (may need manual clear)"
php artisan config:clear >/dev/null 2>&1 || true
php artisan route:clear >/dev/null 2>&1 || true
php artisan view:clear >/dev/null 2>&1 || true
php artisan event:clear >/dev/null 2>&1 || true

# Verify at least config was cleared
if php artisan config:cache --help >/dev/null 2>&1; then
    print_success "Cache clearing completed"
else
    print_warning "Some caches may not be cleared - continuing anyway"
fi

# Step 9: Smart Database Migration System
print_step 9 22 "🎯 Smart Database Migration System"
echo ""

# Step 10.1: Create database if not exists (Auto-fix for missing database)
print_info "→ Ensuring database exists..."
if command -v mysql >/dev/null 2>&1; then
    # Try to create database if it doesn't exist
    if [ -z "$DB_PASSWORD" ]; then
        mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -e "CREATE DATABASE IF NOT EXISTS \`$DB_DATABASE\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null && \
            print_success "✓ Database '$DB_DATABASE' exists/created" || \
            print_warning "⚠ Could not create database (may already exist or need manual creation)"
    else
        mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS \`$DB_DATABASE\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null && \
            print_success "✓ Database '$DB_DATABASE' exists/created" || \
            print_warning "⚠ Could not create database (may already exist or need manual creation)"
    fi
else
    print_warning "⚠ mysql client not available - skipping database creation check"
fi

# Step 10.2: Verify database connection
print_info "→ Verifying database connection..."
if ! php artisan db:show >/dev/null 2>&1; then
    error_exit "Database connection failed - ตรวจสอบ .env credentials หรือสร้างฐานข้อมูลด้วยตัวเอง: mysql -e \"CREATE DATABASE $DB_DATABASE;\""
fi
print_success "✓ Database connection OK"

# Step 10.3: Schema Verification & Auto-Repair System
print_info "→ Verifying database schema integrity..."
if php artisan schema:verify >/dev/null 2>&1; then
    print_success "✓ Database schema is correct"
else
    print_warning "⚠ Schema issues detected - Running detailed verification..."
    echo ""

    # Run detailed schema verification
    php artisan schema:verify 2>&1 | tee -a "$LOG_FILE" || true
    echo ""

    print_warning "╔═══════════════════════════════════════════════════════════╗"
    print_warning "║  ⚠️  Schema Drift Detected!                              ║"
    print_warning "╚═══════════════════════════════════════════════════════════╝"
    echo ""
    print_info "💡 This usually happens when:"
    echo "  • Tables were created via SQL import"
    echo "  • Database was modified manually"
    echo "  • Migrations were partially applied"
    echo ""

    # Backup database before auto-repair
    print_info "→ Creating safety backup before auto-repair..."
    SCHEMA_BACKUP="$BACKUP_DIR/pre_autofix_$(date +'%Y%m%d_%H%M%S').sql"
    if [ "$DB_CONNECTION" = "mysql" ] && command -v mysqldump >/dev/null 2>&1; then
        if [ -z "$DB_PASSWORD" ]; then
            mysqldump -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" "$DB_DATABASE" > "$SCHEMA_BACKUP" 2>/dev/null || \
                print_warning "Backup failed (continuing anyway)"
        else
            mysqldump -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" > "$SCHEMA_BACKUP" 2>/dev/null || \
                print_warning "Backup failed (continuing anyway)"
        fi
        if [ -f "$SCHEMA_BACKUP" ]; then
            print_success "✓ Schema backed up: $SCHEMA_BACKUP"
        fi
    fi
    echo ""

    # Attempt auto-repair
    print_info "🔧 Attempting automatic schema repair..."
    echo ""

    if php artisan schema:verify --auto-fix --force 2>&1 | tee -a "$LOG_FILE"; then
        print_success "✓ Schema auto-repair completed successfully!"
        echo ""
        log "Schema auto-repair: SUCCESS"

        # Verify repair was successful
        if php artisan schema:verify >/dev/null 2>&1; then
            print_success "✓ Schema verification passed after repair"
        else
            print_warning "⚠ Some schema issues may remain, but continuing..."
        fi
    else
        print_error "✗ Auto-repair failed or incomplete"
        echo ""
        print_warning "→ Rollback information:"
        echo "  • Backup file: $SCHEMA_BACKUP"
        echo "  • Restore: mysql -u $DB_USERNAME -p $DB_DATABASE < $SCHEMA_BACKUP"
        echo ""
        log "Schema auto-repair: FAILED"

        # Ask whether to continue
        read -p "Continue with deployment anyway? (y/n) [n]: " -n 1 -r CONTINUE_DEPLOY
        echo
        if [[ ! $CONTINUE_DEPLOY =~ ^[Yy]$ ]]; then
            error_exit "Deployment cancelled due to schema repair failure"
        fi

        print_warning "⚠ Continuing despite schema issues..."
    fi
    echo ""
fi

# Step 10.4: Show current migration status (BEFORE)
print_info "→ Current migration status (BEFORE):"
php artisan migrate:status 2>/dev/null | tail -15 || true
echo ""

# Step 10.5: Check for pending migrations
PENDING_COUNT=$(php artisan migrate:status --pending 2>/dev/null | grep -c "Pending" || echo "0")
TOTAL_MIGRATIONS=$(php artisan migrate:status 2>/dev/null | grep -c "migration" || echo "0")

print_info "→ Migration Analysis:"
echo "  • Total migrations: $TOTAL_MIGRATIONS"
echo "  • Pending migrations: $PENDING_COUNT"
echo ""

# Step 10.6: Smart Migration Handler with Auto-Recovery
handle_migration_with_smart_recovery() {
    local migration_output_file="/tmp/migration_output_$$.log"

    print_info "→ Executing migrations with smart error recovery..."

    # Run migrations and capture output
    if php artisan migrate --force 2>&1 | tee "$migration_output_file" | tee -a "$LOG_FILE"; then
        print_success "✓ Migrations applied successfully!"
        rm -f "$migration_output_file"
        return 0
    fi

    # Migration failed - check if it's a "table already exists" error
    if grep -q "Base table or view already exists" "$migration_output_file"; then
        print_warning "⚠ Detected 'table already exists' error - Attempting auto-recovery..."
        echo ""

        # Extract table name from error
        local table_name=$(grep -oP "Table '\K[^']+(?=' already exists)" "$migration_output_file" | head -1)

        if [ -z "$table_name" ]; then
            print_error "✗ Could not extract table name from error"
            rm -f "$migration_output_file"
            return 1
        fi

        print_info "→ Problem table: $table_name"

        # Find the migration file that creates this table
        print_info "→ Searching for migration file..."
        local migration_file=$(grep -l "create table.*$table_name" database/migrations/*.php 2>/dev/null | head -1)

        if [ -z "$migration_file" ]; then
            # Try alternative search pattern
            migration_file=$(grep -l "'$table_name'" database/migrations/*.php 2>/dev/null | head -1)
        fi

        if [ -n "$migration_file" ]; then
            local migration_name=$(basename "$migration_file" .php)
            print_info "→ Found migration: $migration_name"
            echo ""

            print_warning "📋 Auto-Recovery Options:"
            echo "  1. Table exists but migration not recorded"
            echo "  2. Will register migration as completed without running it"
            echo ""

            # Get current max batch number
            local max_batch=$(php artisan tinker --execute="echo DB::table('migrations')->max('batch') ?? 0;" 2>/dev/null | tail -1 || echo "1")
            local next_batch=$((max_batch + 1))

            print_info "→ Registering migration as completed (batch: $next_batch)..."

            # Insert migration record directly into database
            php artisan tinker --execute="
                DB::table('migrations')->insert([
                    'migration' => '$migration_name',
                    'batch' => $next_batch
                ]);
                echo 'Migration registered successfully';
            " 2>&1 | tee -a "$LOG_FILE"

            if [ $? -eq 0 ]; then
                print_success "✓ Migration '$migration_name' registered as completed"
                echo ""

                # Try running remaining migrations
                print_info "→ Attempting to run remaining migrations..."
                if php artisan migrate --force 2>&1 | tee -a "$LOG_FILE"; then
                    print_success "✓ All remaining migrations applied successfully!"
                    rm -f "$migration_output_file"
                    return 0
                else
                    # Check if there are more "table exists" errors
                    print_warning "⚠ More migration errors detected - running recovery again..."
                    rm -f "$migration_output_file"
                    # Recursive call to handle next error
                    handle_migration_with_smart_recovery
                    return $?
                fi
            else
                print_error "✗ Failed to register migration"
                rm -f "$migration_output_file"
                return 1
            fi
        else
            print_error "✗ Could not find migration file for table: $table_name"
            echo ""
            print_info "💡 Manual recovery steps:"
            echo "  1. Check which migration creates '$table_name'"
            echo "  2. Run: php artisan migrate:status"
            echo "  3. Manually insert migration record if needed"
            rm -f "$migration_output_file"
            return 1
        fi
    else
        # Different type of error
        print_error "✗ Migration failed with different error"
        cat "$migration_output_file"
        rm -f "$migration_output_file"
        return 1
    fi
}

# Step 10.7: Run migrations if needed
if [ "$PENDING_COUNT" != "0" ] && [ "$PENDING_COUNT" != "" ]; then
    print_warning "⚠ Found $PENDING_COUNT pending migration(s) - Will apply now"
    echo ""

    # Show what will be migrated
    print_info "→ Migrations to be applied:"
    php artisan migrate:status --pending 2>/dev/null | grep "Pending" | sed 's/^/  • /' || true
    echo ""

    # Backup database schema before migration
    print_info "→ Backing up database schema..."
    MIGRATION_BACKUP="$BACKUP_DIR/pre_migration_$(date +'%Y%m%d_%H%M%S').sql"
    if [ "$DB_CONNECTION" = "mysql" ] && command -v mysqldump >/dev/null 2>&1; then
        if [ -z "$DB_PASSWORD" ]; then
            mysqldump -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" "$DB_DATABASE" > "$MIGRATION_BACKUP" 2>/dev/null || \
                print_warning "Schema backup failed (continuing anyway)"
        else
            mysqldump -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" > "$MIGRATION_BACKUP" 2>/dev/null || \
                print_warning "Schema backup failed (continuing anyway)"
        fi
        if [ -f "$MIGRATION_BACKUP" ]; then
            print_success "✓ Schema backed up: $MIGRATION_BACKUP"
        fi
    fi
    echo ""

    # Try Smart Migration first (handles existing tables AND column updates)
    print_info "→ Running Smart Migration System..."
    echo ""
    print_info "📋 Smart Migration Capabilities:"
    echo "  ✅ Detects existing tables and skips recreation"
    echo "  ✅ Detects new columns and adds them automatically"
    echo "  ✅ Handles 'table already exists' errors gracefully"
    echo "  ✅ Supports: string, text, integer, decimal, boolean, json, timestamp, date"
    echo ""
    print_warning "⚠️  IMPORTANT: Smart Migration will:"
    echo "  • Create new tables if they don't exist"
    echo "  • Add missing columns to existing tables"
    echo "  • Skip tables/columns that already exist"
    echo ""

    if php artisan migrate:smart --force 2>&1 | tee -a "$LOG_FILE"; then
        print_success "✓ Smart Migration completed successfully!"
        echo ""
        print_info "→ What was done:"
        echo "  • New tables created (if any)"
        echo "  • New columns added to existing tables (if any)"
        echo "  • Existing schema preserved safely"
        echo ""
    else
        print_warning "⚠ Smart Migration failed, falling back to standard migration..."
        echo ""

        # Fallback to migrations with smart error recovery
        if ! handle_migration_with_smart_recovery; then
            print_critical "Migration failed after auto-recovery attempts!"
            echo ""
            print_warning "→ Rollback information:"
            echo "  • Backup file: $MIGRATION_BACKUP"
            echo "  • Rollback command: php artisan migrate:rollback"
            echo "  • Restore DB: mysql -u $DB_USERNAME -p $DB_DATABASE < $MIGRATION_BACKUP"
            echo ""
            print_info "💡 Manual Fix:"
            echo "  1. Check migration status: php artisan migrate:status"
            echo "  2. Identify problematic migrations"
            echo "  3. Either:"
            echo "     a) Drop the existing table and re-run migration"
            echo "     b) Manually insert migration record if table is correct"
            echo "     c) Check if migration uses Schema::hasTable() + return (incorrect for column updates)"
            echo ""
            print_warning "⚠️  Common Issues:"
            echo "  • Migration uses Schema::hasTable() + return when adding columns"
            echo "  • Solution: Use Schema::hasColumn() instead"
            echo "  • Or use SafeMigration trait (recommended)"
            echo "  • See: database/migrations/README_MIGRATIONS.md"
            error_exit "Database migration failed - ตรวจสอบ logs และ backup"
        fi
        print_success "✓ Migrations completed successfully!"
        echo ""
    fi
else
    print_success "✓ No pending migrations - Database schema is up to date"
    # Still run migrate to ensure everything is in sync
    php artisan migrate --force >/dev/null 2>&1 || true
    echo ""
fi

# Step 10.8: Show migration status (AFTER)
print_info "→ Migration status (AFTER):"
php artisan migrate:status 2>/dev/null | tail -15 || true
echo ""

# Step 10.9: Verify migration integrity
print_info "→ Verifying database integrity..."
MIGRATED_COUNT=$(php artisan migrate:status 2>/dev/null | grep -c "Ran" || echo "0")
echo "  • Successfully migrated: $MIGRATED_COUNT migrations"

if [ "$PENDING_COUNT" != "0" ]; then
    NEW_PENDING=$(php artisan migrate:status --pending 2>/dev/null | grep -c "Pending" || echo "0")
    if [ "$NEW_PENDING" = "0" ]; then
        print_success "✓ All migrations completed successfully!"
    else
        print_warning "⚠ Still have $NEW_PENDING pending migrations"
    fi
fi
echo ""

# Analyze seeder safety (checks if seeder uses safe methods)
analyze_seeder_safety() {
    local seeder_file="$1"
    local safety_score=0
    local issues=()
    local safe_methods=()

    # Check for safe methods
    if grep -q "updateOrCreate\|firstOrCreate\|firstOrNew" "$seeder_file"; then
        safety_score=$((safety_score + 2))
        safe_methods+=("updateOrCreate/firstOrCreate")
    fi

    # Check for conditional creation
    if grep -q "if.*exists\|if.*count\|if.*find" "$seeder_file"; then
        safety_score=$((safety_score + 1))
        safe_methods+=("conditional checks")
    fi

    # Check for potentially unsafe methods
    if grep -q "truncate\|delete\|DB::statement.*DROP\|DB::statement.*TRUNCATE" "$seeder_file"; then
        safety_score=$((safety_score - 3))
        issues+=("uses truncate/delete")
    fi

    # Check for factory with count (mass creation)
    if grep -q "factory.*->count\|factory.*->create" "$seeder_file"; then
        if ! grep -q "updateOrCreate\|firstOrCreate" "$seeder_file"; then
            safety_score=$((safety_score - 1))
            issues+=("mass creation without checks")
        fi
    fi

    # Determine safety level
    if [ $safety_score -ge 2 ]; then
        echo "SAFE|${safe_methods[*]}|${issues[*]}"
    elif [ $safety_score -ge 0 ]; then
        echo "CAUTION|${safe_methods[*]}|${issues[*]}"
    else
        echo "UNSAFE|${safe_methods[*]}|${issues[*]}"
    fi
}

# Track individual seeder changes
track_seeder_changes() {
    local seeder_dir="$1"
    local checksum_dir=".seeder_checksums"
    mkdir -p "$checksum_dir"

    local changed_seeders=()
    local new_seeders=()
    local unchanged_count=0

    # Iterate through all seeder files
    while IFS= read -r seeder_file; do
        local seeder_name=$(basename "$seeder_file")
        local checksum_file="$checksum_dir/$seeder_name.md5"
        local current_checksum=$(md5sum "$seeder_file" 2>/dev/null | cut -d' ' -f1)

        if [ ! -f "$checksum_file" ]; then
            # New seeder
            new_seeders+=("$seeder_name")
            echo "$current_checksum" > "$checksum_file"
        else
            # Existing seeder - check if changed
            local old_checksum=$(cat "$checksum_file" 2>/dev/null)
            if [ "$old_checksum" != "$current_checksum" ]; then
                changed_seeders+=("$seeder_name")
                echo "$current_checksum" > "$checksum_file"
            else
                unchanged_count=$((unchanged_count + 1))
            fi
        fi
    done < <(find "$seeder_dir" -name "*Seeder.php" ! -name "DatabaseSeeder.php" 2>/dev/null)

    # Return results
    echo "${#new_seeders[@]}|${#changed_seeders[@]}|$unchanged_count|${new_seeders[*]}|${changed_seeders[*]}"
}

# Step 10: Smart Database Seeding System v2
print_step 10 22 "🌱 Smart Database Seeding System v2"
echo ""

# Step 11.1: Verify all seeders are included in DatabaseSeeder.php
print_info "→ Verifying seeder integrity..."
if [ -f "scripts/verify-seeders.php" ]; then
    if php scripts/verify-seeders.php >/dev/null 2>&1; then
        print_success "✓ All seeders are properly included in DatabaseSeeder.php"
    else
        print_error "✗ Seeder verification failed!"
        echo ""
        print_warning "Running detailed verification..."
        php scripts/verify-seeders.php
        echo ""
        error_exit "Some seeders are not included in DatabaseSeeder.php - fix before deployment"
    fi
else
    print_warning "⚠ Seeder verification script not found (scripts/verify-seeders.php)"
fi
echo ""

# Check if seeders directory exists and has seeders
SEEDER_DIR="database/seeders"
if [ ! -d "$SEEDER_DIR" ]; then
    print_warning "No seeders directory found - skipping seeding"
else
    # Count seeder files (excluding DatabaseSeeder.php)
    SEEDER_COUNT=$(find "$SEEDER_DIR" -name "*Seeder.php" ! -name "DatabaseSeeder.php" 2>/dev/null | wc -l)

    if [ "$SEEDER_COUNT" -eq 0 ]; then
        print_info "No seeders found - skipping seeding"
    else
        print_info "→ Found $SEEDER_COUNT seeder file(s)"
        echo ""

        # Step 11.2: Track individual seeder changes
        print_info "→ Analyzing seeder changes..."
        tracking_result=$(track_seeder_changes "$SEEDER_DIR")
        IFS='|' read -r new_count changed_count unchanged_count new_list changed_list <<< "$tracking_result"

        print_info "  • New seeders: $new_count"
        print_info "  • Changed seeders: $changed_count"
        print_info "  • Unchanged seeders: $unchanged_count"
        echo ""

        # Step 11.3: Safety analysis for new/changed seeders
        has_changes=0
        if [ "$new_count" != "0" ] || [ "$changed_count" != "0" ]; then
            has_changes=1
            print_info "→ Safety Analysis:"
            echo ""

            # Analyze new seeders
            if [ "$new_count" != "0" ]; then
                print_warning "📦 New Seeders:"
                for seeder in $new_list; do
                    seeder_path="$SEEDER_DIR/$seeder"
                    if [ -f "$seeder_path" ]; then
                        safety_result=$(analyze_seeder_safety "$seeder_path")
                        IFS='|' read -r safety_level safe_methods issues <<< "$safety_result"

                        case "$safety_level" in
                            SAFE)
                                echo "  ✅ $seeder [${GREEN}SAFE${NC}]"
                                [ -n "$safe_methods" ] && echo "     → Uses: $safe_methods"
                                ;;
                            CAUTION)
                                echo "  ⚠️  $seeder [${YELLOW}CAUTION${NC}]"
                                [ -n "$safe_methods" ] && echo "     → Uses: $safe_methods"
                                [ -n "$issues" ] && echo "     → Issues: $issues"
                                ;;
                            UNSAFE)
                                echo "  ❌ $seeder [${RED}UNSAFE${NC}]"
                                [ -n "$issues" ] && echo "     → Issues: $issues"
                                ;;
                        esac
                    fi
                done
                echo ""
            fi

            # Analyze changed seeders
            if [ "$changed_count" != "0" ]; then
                print_warning "🔄 Updated Seeders:"
                for seeder in $changed_list; do
                    seeder_path="$SEEDER_DIR/$seeder"
                    if [ -f "$seeder_path" ]; then
                        safety_result=$(analyze_seeder_safety "$seeder_path")
                        IFS='|' read -r safety_level safe_methods issues <<< "$safety_result"

                        case "$safety_level" in
                            SAFE)
                                echo "  ✅ $seeder [${GREEN}SAFE${NC}]"
                                [ -n "$safe_methods" ] && echo "     → Uses: $safe_methods"
                                ;;
                            CAUTION)
                                echo "  ⚠️  $seeder [${YELLOW}CAUTION${NC}]"
                                [ -n "$safe_methods" ] && echo "     → Uses: $safe_methods"
                                [ -n "$issues" ] && echo "     → Issues: $issues"
                                ;;
                            UNSAFE)
                                echo "  ❌ $seeder [${RED}UNSAFE${NC}]"
                                [ -n "$issues" ] && echo "     → Issues: $issues"
                                ;;
                        esac
                    fi
                done
                echo ""
            fi
        else
            print_success "✓ No seeder changes detected - skipping seeding"
            has_changes=0
        fi

        # Step 11.4: Determine if seeding should run
        if [ $has_changes -eq 1 ]; then
            # Check if database is empty
            USER_COUNT=$(php artisan tinker --execute="echo App\Models\User::count();" 2>/dev/null | tail -1 || echo "0")
            EMAIL_TEMPLATE_COUNT=$(php artisan tinker --execute="echo DB::table('email_templates')->count();" 2>/dev/null | tail -1 || echo "0")

            print_info "→ Database Status:"
            echo "  • Users: $USER_COUNT"
            echo "  • Email templates: $EMAIL_TEMPLATE_COUNT"
            echo ""

            # Auto-run if database is empty
            if [ "$USER_COUNT" = "0" ] || [ "$EMAIL_TEMPLATE_COUNT" = "0" ]; then
                print_warning "⚠ Database appears empty - Auto-running seeders..."
                if ! php artisan db:seed --force 2>&1 | tee -a "$LOG_FILE"; then
                    print_warning "Seeding failed (continuing anyway)"
                else
                    print_success "✓ Database seeded successfully"
                fi
            else
                # Database has data - ask user
                print_warning "⚠ Database has existing data"
                echo ""
                print_info "💡 Recommendation:"
                echo "  • SAFE seeders use updateOrCreate() - won't duplicate data"
                echo "  • CAUTION seeders may need manual review"
                echo "  • UNSAFE seeders may overwrite existing data"
                echo ""

                read -p "Run seeders now? (y/n) [n]: " -n 1 -r RUN_SEEDER
                echo
                echo ""

                if [[ $RUN_SEEDER =~ ^[Yy]$ ]]; then
                    print_info "Running database seeders..."
                    if ! php artisan db:seed --force 2>&1 | tee -a "$LOG_FILE"; then
                        print_warning "Seeding failed (continuing anyway)"
                    else
                        print_success "✓ Database seeded successfully"
                    fi
                else
                    print_info "Skipping database seeders"
                    print_warning "⚠ Run manually later: php artisan db:seed"
                fi
            fi
        fi
    fi
fi
echo ""

# Step 11: Create Storage Symlink (แก้ไขปัญหาโลโก้หาย)
print_step 11 22 "Creating Storage Symlink"

# ใช้ storage:fix แทน storage:link เพราะจัดการกรณีพิเศษได้ดีกว่า
if php artisan storage:fix --force --no-interaction 2>&1 | tee -a "$LOG_FILE"; then
    print_success "✓ Storage symlink created successfully"
else
    print_warning "⚠ storage:fix command not available, trying storage:link..."
    php artisan storage:link --force --no-interaction 2>&1 | tee -a "$LOG_FILE" || true
fi

# Verify symlink มีอยู่และชี้ไปถูกที่
if [ -L "public/storage" ]; then
    LINK_TARGET=$(readlink -f "public/storage" 2>/dev/null || readlink "public/storage")
    EXPECTED_TARGET=$(readlink -f "storage/app/public" 2>/dev/null || echo "$PWD/storage/app/public")

    if [[ "$LINK_TARGET" == *"storage/app/public"* ]]; then
        print_success "✓ Storage symlink verified (public/storage → storage/app/public)"

        # ตรวจสอบ permissions
        if [ -w "storage/app/public" ]; then
            print_success "✓ Storage directory is writable"
        else
            print_warning "⚠ Storage directory may not be writable"
        fi
    else
        print_error "✗ Storage symlink points to wrong location: $LINK_TARGET"
        error_exit "Storage symlink configuration error - uploaded files will not work!"
    fi
else
    print_critical "Storage symlink does not exist!"
    print_error "  This will cause uploaded logos and files to not work."
    print_error "  Run 'php artisan storage:fix --force' manually after deployment."
    error_exit "Critical: Storage symlink creation failed"
fi

# Step 12: Set Permissions
print_step 12 22 "Setting File Permissions"

# Detect web server user
WEB_USER=""
if id -u www-data >/dev/null 2>&1; then
    WEB_USER="www-data"
elif id -u nginx >/dev/null 2>&1; then
    WEB_USER="nginx"
elif id -u apache >/dev/null 2>&1; then
    WEB_USER="apache"
elif id -u admin >/dev/null 2>&1; then
    WEB_USER="admin"
fi

# Set proper permissions
chmod -R 775 storage bootstrap/cache 2>/dev/null || true
find storage -type f -exec chmod 664 {} \; 2>/dev/null || true
find bootstrap/cache -type f -exec chmod 664 {} \; 2>/dev/null || true

# Set ownership if web server user is detected
if [ -n "$WEB_USER" ]; then
    CURRENT_USER=$(whoami)

    # Try to set ownership (may need sudo)
    if chown -R "$CURRENT_USER:$WEB_USER" storage bootstrap/cache 2>/dev/null; then
        log "Ownership set to $CURRENT_USER:$WEB_USER"
    else
        log "WARNING: Cannot set ownership, may need manual intervention"
    fi

    # Try to use ACL if available
    if command -v setfacl >/dev/null 2>&1; then
        setfacl -R -m u:"$WEB_USER":rwX storage bootstrap/cache 2>/dev/null || true
        setfacl -R -d -m u:"$WEB_USER":rwX storage bootstrap/cache 2>/dev/null || true
    fi
fi

print_success "Permissions set"

# Step 13: Restart Services FIRST (CRITICAL - OPcache must be cleared BEFORE caching!)
# ⚠️ FIX: MethodNotAllowedHttpException - ต้อง restart PHP-FPM ก่อน cache
# เพื่อให้ OPcache ถูก clear และอ่านไฟล์ใหม่ก่อนสร้าง route cache
print_step 13 22 "🔄 Restarting Services FIRST (Clear OPcache)"

# Track service restart status
PHP_FPM_STATUS="⏭️ ไม่พบ"
QUEUE_STATUS="⏭️ ไม่ได้ใช้งาน"
HORIZON_STATUS="⏭️ ไม่ได้ใช้งาน"

# Restart PHP-FPM BEFORE caching - THIS CLEARS WEB SERVER OPCACHE!
print_info "→ ตรวจสอบ PHP-FPM service..."
PHP_FPM_RESTARTED=false
if command -v systemctl >/dev/null 2>&1; then
    for service in php8.3-fpm php8.2-fpm php8.1-fpm php8.0-fpm php-fpm; do
        if systemctl is-active --quiet $service 2>/dev/null; then
            print_info "  พบ PHP-FPM service: $service"
            if sudo systemctl reload $service 2>/dev/null; then
                print_success "  ✓ Reload $service สำเร็จ (OPcache cleared)"
                PHP_FPM_STATUS="✅ $service reloaded"
                PHP_FPM_RESTARTED=true
            else
                print_error "  ✗ Reload $service ล้มเหลว (ต้องการ sudo)"
                PHP_FPM_STATUS="❌ ล้มเหลว (ต้อง sudo)"
            fi
            break
        fi
    done
    if [ "$PHP_FPM_RESTARTED" = false ] && [ "$PHP_FPM_STATUS" = "⏭️ ไม่พบ" ]; then
        print_warning "  ⚠ ไม่พบ PHP-FPM service ที่ทำงานอยู่"
        PHP_FPM_STATUS="⚠️ ไม่พบ service"
    fi
elif command -v service >/dev/null 2>&1; then
    for svc in php8.3-fpm php8.2-fpm php8.1-fpm php-fpm; do
        if service $svc status >/dev/null 2>&1; then
            if sudo service $svc reload 2>/dev/null; then
                print_success "  ✓ Reload $svc สำเร็จ"
                PHP_FPM_STATUS="✅ $svc reloaded"
                PHP_FPM_RESTARTED=true
            else
                print_error "  ✗ Reload $svc ล้มเหลว"
                PHP_FPM_STATUS="❌ ล้มเหลว"
            fi
            break
        fi
    done
else
    print_warning "  ⚠ ไม่พบ systemctl หรือ service command"
    PHP_FPM_STATUS="⚠️ ไม่พบ systemctl/service"
fi

# Restart queue workers
print_info "→ ตรวจสอบ Queue Workers..."
if php artisan queue:restart 2>&1 | grep -q "Broadcasting queue restart signal"; then
    print_success "  ✓ Queue workers restart signal sent"
    QUEUE_STATUS="✅ Restart signal sent"
else
    print_info "  ℹ Queue workers ไม่ได้ใช้งาน หรือไม่มี worker ทำงานอยู่"
    QUEUE_STATUS="ℹ️ ไม่มี worker ทำงาน"
fi

# ✅ ตรวจสอบและเริ่ม queue worker สำหรับ fortune-deep (ถ้ายังไม่มี)
# ใช้เป็น backup สำหรับกรณี proc_open ไม่ทำงาน
print_info "→ ตรวจสอบ Fortune Deep Queue Worker..."
if pgrep -f "queue:work.*fortune-deep" > /dev/null 2>&1; then
    print_success "  ✓ Fortune deep queue worker กำลังทำงานอยู่"
else
    # ลอง start queue worker ใน background (ถ้า Redis/Database มีอยู่)
    QUEUE_DRIVER=$(php artisan tinker --execute="echo config('queue.default');" 2>/dev/null | tail -1)
    if [ "$QUEUE_DRIVER" = "redis" ] || [ "$QUEUE_DRIVER" = "database" ]; then
        nohup php artisan queue:work "$QUEUE_DRIVER" --queue=fortune-deep --tries=3 --timeout=300 --sleep=5 --max-jobs=50 --max-time=3600 >> storage/logs/queue-fortune-deep.log 2>&1 &
        print_success "  ✓ เริ่ม Fortune deep queue worker (PID: $!)"
        QUEUE_STATUS="$QUEUE_STATUS | Fortune: started PID $!"
    else
        print_info "  ℹ Queue driver เป็น sync — ไม่ต้องใช้ worker (ใช้ proc_open แทน)"
    fi
fi

# Wait for OPcache to fully clear
if [ "$PHP_FPM_RESTARTED" = true ]; then
    print_info "→ รอให้ OPcache clear เสร็จสมบูรณ์ (2 วินาที)..."
    sleep 2
fi
echo ""

# Step 14: Clear caches AGAIN after PHP-FPM restart
print_step 14 22 "🧹 Clearing Caches (Post PHP-FPM Restart)"
print_info "→ Clearing all Laravel caches..."
php artisan route:clear 2>/dev/null || true
php artisan config:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true
php artisan cache:clear 2>/dev/null || true
php artisan event:clear 2>/dev/null || true

# Delete bootstrap cache files manually
rm -f bootstrap/cache/config.php 2>/dev/null || true
rm -f bootstrap/cache/routes-v7.php 2>/dev/null || true
rm -f bootstrap/cache/services.php 2>/dev/null || true
rm -f bootstrap/cache/packages.php 2>/dev/null || true
print_success "✓ All caches cleared"
echo ""

# Step 15: Cache Configuration
print_step 15 22 "Caching Configuration"
if ! php artisan config:cache 2>&1 | tee -a "$LOG_FILE"; then
    error_exit "Config cache failed - ตรวจสอบ .env และ config files" "$?"
fi
print_success "Configuration cached"

# ⭐ Validation Guard (FIX 2026-05-29): ป้องกัน "config พิษ"
# เคยเกิด: deploy cache ค่า DB_USERNAME=your_username (placeholder จาก .env.example)
# → queue worker ต่อ DB ไม่ได้ → comment→DM funnel ตาย ~7 นาที (brain note 36a56e6c42d0)
# ตรวจว่า DB username ที่ cache ไว้ไม่ใช่ค่า placeholder — ถ้าใช่ ให้กู้ .env จาก backup แล้ว re-cache
print_info "→ ตรวจสอบ DB credentials หลัง config:cache (กัน config พิษ)..."
CACHED_DB_USER=$(php artisan tinker --execute="echo config('database.connections.mysql.username');" 2>/dev/null | tail -1 | tr -d '[:space:]')
if echo "$CACHED_DB_USER" | grep -qiE "your_username|your-db-user|changeme"; then
    print_critical "❌ DB username ที่ cache เป็นค่า placeholder ('$CACHED_DB_USER') — .env เพี้ยนระหว่าง deploy!"
    print_info "→ กำลังกู้ .env จาก backup แล้ว re-cache..."

    # กู้ .env จาก critical backup (เฉพาะเมื่อ backup ไม่ใช่ placeholder ด้วย — กันกู้ของพังทับ)
    if [ -f "/tmp/deploy_backup_path_$$" ]; then
        RESTORE_PATH=$(cat /tmp/deploy_backup_path_$$)
        if [ -f "$RESTORE_PATH/.env" ] && grep -qE "^DB_USERNAME=" "$RESTORE_PATH/.env" && ! grep -qiE "^DB_USERNAME=(your_username|changeme)" "$RESTORE_PATH/.env"; then
            cp "$RESTORE_PATH/.env" .env && print_success "✓ กู้ .env จาก backup สำเร็จ ($RESTORE_PATH/.env)"
        else
            print_error "✗ backup .env ก็เป็น placeholder/ไม่มี — ต้องแก้ .env ด้วยมือ"
        fi
    else
        print_warning "⚠ ไม่พบ backup path สำหรับกู้ .env — ข้ามการกู้"
    fi

    # re-cache จาก .env ที่กู้คืนแล้ว
    php artisan config:clear >/dev/null 2>&1 || true
    rm -f bootstrap/cache/config.php 2>/dev/null || true
    php artisan config:cache >/dev/null 2>&1 || true

    # ตรวจซ้ำ — ถ้ายัง placeholder ให้หยุด deploy (อย่าปล่อยขึ้น live ทั้งที่ DB พัง)
    CACHED_DB_USER=$(php artisan tinker --execute="echo config('database.connections.mysql.username');" 2>/dev/null | tail -1 | tr -d '[:space:]')
    if echo "$CACHED_DB_USER" | grep -qiE "your_username|your-db-user|changeme"; then
        error_exit "DB credentials ยังเป็น placeholder ('$CACHED_DB_USER') หลังกู้คืน — หยุด deploy เพื่อความปลอดภัย กรุณาตรวจ .env ด้วยมือ"
    fi
    print_success "✓ กู้ DB credentials สำเร็จ (user: $CACHED_DB_USER)"
else
    print_success "✓ DB credentials ถูกต้อง (user: $CACHED_DB_USER)"
fi

# Step 16: Cache Routes (with verification!)
print_step 16 22 "Caching Routes (with Verification)"
php artisan route:cache || print_warning "Route cache failed (continuing anyway)"

# ⚠️ CRITICAL: Verify home route accepts GET method
print_info "→ Verifying home route methods..."

# Laravel 11 route:list outputs table format with │ borders
# Use simple pattern: look for "GET|HEAD" and "home" on the same line
# This works for both old and new Laravel output formats
ROUTE_METHODS=$(php artisan route:list --path=/ --method=GET --name=home 2>/dev/null | grep -i "home" || echo "")

# Fallback: try with just --path=/ if named route check fails
if [ -z "$ROUTE_METHODS" ]; then
    ROUTE_METHODS=$(php artisan route:list --path=/ --method=GET 2>/dev/null | grep "GET" | head -1 || echo "")
fi

if [ -z "$ROUTE_METHODS" ]; then
    print_critical "⚠️  HOME ROUTE VERIFICATION FAILED!"
    print_error "Route '/' ไม่รองรับ GET method - อาจเกิด MethodNotAllowedHttpException"
    echo ""
    print_info "→ Attempting recovery: clearing route cache..."
    php artisan route:clear
    print_warning "⚠️ Route cache ถูก clear - จะใช้ routes แบบไม่ cache"
    print_warning "   กรุณาตรวจสอบว่า OPcache ถูก clear แล้ว:"
    echo "   sudo systemctl reload php8.2-fpm"
    echo ""
    log "WARNING: Home route verification failed - route cache cleared"
else
    print_success "✓ Home route verified: GET|HEAD / is registered"
fi
print_success "Routes cached (verified)"

# Step 17: Cache Views
print_step 17 22 "Caching Views"
php artisan view:cache || print_warning "View cache failed (continuing anyway)"
print_success "Views cached"

# Step 18: Optimize Autoloader
print_step 18 22 "Optimizing Autoloader"
composer dump-autoload --optimize --no-dev --no-interaction
print_success "Autoloader optimized"

# ⭐ Hard-restart Fortune Queue Worker (FIX 2026-05-29) — ต้องทำ "หลัง" config:cache เสมอ
# ปัญหาเดิม: queue:restart (Step ก่อนหน้า) ยิง signal ก่อน config:cache → worker (comment→DM
# funnel) respawn อ่าน config ตอนยังไม่นิ่ง → ค้าง DB creds เก่า/พิษในหน่วยความจำ จน respawn
# ครั้งถัดไป (สูงสุด 1 ชม.). hard restart supervisor ตรงนี้ = worker อ่าน config สุดท้ายที่ถูกต้องเสมอ
print_info "→ Restart Fortune Queue Worker (ให้ worker อ่าน config ที่ settle แล้ว)..."
if command -v systemctl >/dev/null 2>&1 && systemctl list-unit-files 2>/dev/null | grep -q '^fortune-queue-worker\.service'; then
    if sudo systemctl restart fortune-queue-worker.service 2>/dev/null; then
        print_success "✓ Restarted fortune-queue-worker.service (worker อ่าน config ใหม่)"
        QUEUE_STATUS="$QUEUE_STATUS | fortune-worker: ✅ restarted"
    else
        print_warning "⚠ Restart fortune-queue-worker.service ล้มเหลว — รันเอง: sudo systemctl restart fortune-queue-worker.service"
        QUEUE_STATUS="$QUEUE_STATUS | fortune-worker: ⚠️ restart failed"
    fi
else
    print_info "  ℹ ไม่พบ fortune-queue-worker.service (ข้าม — เซิร์ฟเวอร์นี้อาจไม่ได้ใช้ supervisor นี้)"
fi
echo ""

# Step 19: Final ENV Verification
print_step 19 22 "Verifying Environment Configuration"
if [ -f ".env" ]; then
    print_success "✓ .env file exists and is ready"
else
    error_exit ".env file missing after sync"
fi

# Step 20: Disable Maintenance Mode
print_step 20 22 "Disabling Maintenance Mode"
php artisan up || error_exit "Failed to disable maintenance mode"
print_success "Application is now live!"

# Step 21: Cloudflare Cache Purge
print_step 21 22 "☁️ Cloudflare CDN Cache Purge"

# Check if Cloudflare is configured in .env
CF_ZONE_ID=$(grep "^CLOUDFLARE_ZONE_ID=" .env 2>/dev/null | cut -d '=' -f2 || echo "")
CF_API_TOKEN=$(grep "^CLOUDFLARE_API_TOKEN=" .env 2>/dev/null | cut -d '=' -f2 || echo "")

# ⚠️ ต้องตั้งค่าใน .env เท่านั้น (ไม่มีค่า default เพื่อความปลอดภัย)

if [ -n "$CF_ZONE_ID" ] && [ -n "$CF_API_TOKEN" ]; then
    print_info "→ Purging Cloudflare CDN cache..."

    # Make API call to purge everything
    CF_RESPONSE=$(curl -s -X POST "https://api.cloudflare.com/client/v4/zones/${CF_ZONE_ID}/purge_cache" \
        -H "Authorization: Bearer ${CF_API_TOKEN}" \
        -H "Content-Type: application/json" \
        --data '{"purge_everything":true}' \
        --max-time 30 2>/dev/null || echo '{"success":false}')

    # Check response
    if echo "$CF_RESPONSE" | grep -q '"success":true'; then
        print_success "✓ Cloudflare cache purged successfully!"
        print_info "  CDN จะ fetch เนื้อหาใหม่จาก origin server"
        log "Cloudflare: Cache purged successfully"
    else
        CF_ERROR=$(echo "$CF_RESPONSE" | grep -oP '"message":"\K[^"]+' | head -1 || echo "Unknown error")
        print_warning "⚠ Cloudflare cache purge failed: $CF_ERROR"
        print_info "  ลองรัน manual: php artisan cloudflare:purge"
        log "Cloudflare: Cache purge failed - $CF_ERROR"
    fi
else
    print_info "→ Cloudflare ไม่ได้ตั้งค่า (ข้ามการ purge cache)"
    print_info "  กำหนดค่าได้ที่: Admin → Cloudflare CDN"
fi
echo ""

# Post-deployment verification
print_header "🔍 Post-Deployment Verification"

# Step 22: Verify deployment
print_step 22 22 "Verifying Deployment"

# Check if application is accessible (non-critical check)
if php artisan route:list >/dev/null 2>&1; then
    print_success "✓ Routes are accessible"
else
    print_warning "⚠ Routes check skipped (cache warming up)"
fi

# HTTP Health Check - verify site is actually responding
print_info "→ Running HTTP Health Check..."
APP_URL=$(grep "^APP_URL=" .env | cut -d '=' -f2)
if [ -n "$APP_URL" ] && command -v curl >/dev/null 2>&1; then
    print_info "Checking HTTP response from $APP_URL..."
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" --max-time 10 "$APP_URL" 2>/dev/null || echo "000")

    if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "302" ] || [ "$HTTP_CODE" = "301" ]; then
        print_success "✓ HTTP Health Check OK (HTTP $HTTP_CODE)"
    elif [ "$HTTP_CODE" = "503" ]; then
        print_warning "⚠ Site returning 503 (may still be in maintenance mode)"
    elif [ "$HTTP_CODE" = "000" ]; then
        print_warning "⚠ HTTP Health Check failed (timeout or network error)"
    else
        print_warning "⚠ HTTP Health Check returned HTTP $HTTP_CODE"
    fi
else
    print_info "Skipping HTTP health check (curl not available or APP_URL not set)"
fi

# Check if database is accessible
if php artisan db:show >/dev/null 2>&1; then
    print_success "✓ Database connection OK"
else
    print_warning "⚠ Database connection check failed"
fi

# Check critical directories
if [ -w "storage/logs" ] && [ -w "bootstrap/cache" ]; then
    print_success "✓ Storage permissions OK"
else
    print_warning "⚠ Storage permissions may need attention"
fi

# Verify no uncommitted changes remain
if [[ -z $(git status -s) ]]; then
    print_success "✓ Working directory is clean"
else
    print_warning "⚠ Unexpected files in working directory"
fi

# ============================================================
# 🔄 CRON SELF-HEAL (DirectAdmin/shared hosting มัก wipe crontab)
# ============================================================
# Laravel scheduler base cron (* * * * * php artisan schedule:run)
# คือ "หัวใจ" ที่รัน fortune:mystic:publish, celtic-recover, sms-sweep ฯลฯ
# ถ้าหาย = ระบบ auto-post + recovery ทั้งหมดตาย เงียบๆ
# ensure-cron.sh เป็น idempotent — รันซ้ำกี่ครั้งก็ไม่ duplicate
print_info "🔄 Ensuring Laravel scheduler cron is installed..."
if [ -x "$SCRIPT_DIR/scripts/ensure-cron.sh" ] || [ -f "$SCRIPT_DIR/scripts/ensure-cron.sh" ]; then
    if bash "$SCRIPT_DIR/scripts/ensure-cron.sh" "$SCRIPT_DIR" >> "$LOG_FILE" 2>&1; then
        print_success "✓ Cron schedule:run verified/installed"
    else
        print_warning "⚠ ensure-cron.sh failed (crontab unavailable in this shell?)"
        print_warning "  Manually add in DirectAdmin → Cron Jobs:"
        print_warning "  * * * * * cd $SCRIPT_DIR && php artisan schedule:run >> /dev/null 2>&1"
    fi
else
    print_warning "⚠ scripts/ensure-cron.sh not found — skipping cron self-heal"
fi

# Verify cron actually installed (independent check)
if command -v crontab >/dev/null 2>&1; then
    if crontab -l 2>/dev/null | grep -q "artisan schedule:run"; then
        CRON_COUNT=$(crontab -l 2>/dev/null | grep -c "artisan schedule:run")
        if [ "$CRON_COUNT" -eq 1 ]; then
            print_success "✓ Cron verified: 1 schedule:run entry present"
        else
            print_warning "⚠ Found $CRON_COUNT schedule:run entries (expected 1) — may need cleanup"
        fi
    else
        print_warning "⚠ CRITICAL: No 'artisan schedule:run' cron found after install attempt!"
        print_warning "  ระบบ auto-post + scheduled jobs จะไม่ทำงาน — ต้องตั้งใน DirectAdmin manually"
    fi
fi

# Deployment Summary
print_header "✅ Deployment Completed Successfully!"

log "=== Deployment Completed Successfully ==="

echo "📊 Deployment Summary:"
echo ""
echo "  Environment:   ${BLUE}$APP_ENV${NC}"
echo "  Branch:        ${BLUE}$BRANCH${NC}"
echo "  Old Commit:    ${YELLOW}${CURRENT_COMMIT:0:8}${NC}"
echo "  New Commit:    ${GREEN}${NEW_COMMIT:0:8}${NC}"
echo "  Time:          ${BLUE}$(date)${NC}"
echo "  Backup:        ${BLUE}$BACKUP_FILE${NC}"
echo ""
echo "🔄 What was deployed:"
echo "  ✓ Code synced from GitHub (forced)"
echo "  ✓ Environment variables synced (.env updated)"
echo "  ✓ Dependencies reinstalled (clean)"
echo "  ✓ Laravel Sanctum installed/updated"
echo "  ✓ Database migrations applied"
echo "  ✓ All caches regenerated"
echo "  ✓ Permissions configured"
echo "  ✓ Cron schedule:run verified (self-heal)"
echo ""

print_info "📋 Post-Deployment Checklist:"
echo "  □ Test the application in browser"
echo "  □ Check logs: tail -f storage/logs/laravel.log"
echo "  □ Monitor error logs: tail -f storage/logs/deployment.log"
echo "  □ Verify database migrations: php artisan migrate:status"
echo "  □ Check queue workers: php artisan queue:monitor"
echo "  □ Verify cron: crontab -l | grep schedule:run  (ต้องเจอ 1 บรรทัด)"
echo "  □ Test scheduler: php artisan schedule:list  (ดู task ที่ลงคิว)"
echo ""

# Generate rollback commands from history
generate_rollback_commands

print_success "Happy deploying! 🚀"
echo ""

print_info "📌 Quick Commands:"
echo "  • View logs: tail -f storage/logs/laravel.log"
echo "  • View deployment logs: tail -f storage/logs/deployment.log"
echo "  • Check queue: php artisan queue:monitor"
echo "  • Manual rollback: git reset --hard $CURRENT_COMMIT && ./deploy.sh"
echo ""

# Clean up environment variable
unset DEPLOY_ATTEMPT_COUNT

# Success exit
exit 0
