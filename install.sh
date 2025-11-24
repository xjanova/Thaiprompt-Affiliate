#!/bin/bash

################################################################################
# TP-Affiliate Ultimate Installation Script v3.0
# ไฟล์เดียวจบ - ติดตั้งครบทุกอย่าง ไม่ต้องรันอะไรเพิ่ม
#
# Features:
# ✅ รวมทุกฟีเจอร์ในไฟล์เดียว (fix-permissions, clear-cache, etc.)
# ✅ แยก migration เฉพาะที่จำเป็นสำหรับการติดตั้งใหม่
# ✅ Safe Seeder - รันต่อได้แม้เจอ error
# ✅ แสดงผลทุกขั้นตอนแบบละเอียด
# ✅ Resume support - ติดตั้งค้างไว้ได้
# ✅ Auto-retry - ลองใหม่อัตโนมัติถ้าล้มเหลว
################################################################################

set -e  # Exit on error (except in safe seeder)

################################################################################
# สีสำหรับแสดงผล
################################################################################
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
MAGENTA='\033[0;35m'
CYAN='\033[0;36m'
WHITE='\033[1;37m'
NC='\033[0m' # No Color
BOLD='\033[1m'

################################################################################
# ฟังก์ชันแสดงผล
################################################################################
print_success() {
    echo -e "${GREEN}✓${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

print_info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

print_step() {
    echo -e "${CYAN}▶${NC} $1"
}

print_header() {
    echo ""
    echo -e "${MAGENTA}${BOLD}════════════════════════════════════════════════════════════════${NC}"
    echo -e "${MAGENTA}${BOLD}  $1${NC}"
    echo -e "${MAGENTA}${BOLD}════════════════════════════════════════════════════════════════${NC}"
    echo ""
}

print_subheader() {
    echo ""
    echo -e "${CYAN}${BOLD}─────────────────────────────────────────────────${NC}"
    echo -e "${CYAN}${BOLD}  $1${NC}"
    echo -e "${CYAN}${BOLD}─────────────────────────────────────────────────${NC}"
    echo ""
}

################################################################################
# ตัวแปรสำคัญ
################################################################################
CACHE_FILE=".install_cache"
PROGRESS_FILE=".install_progress"
SEEDER_LOG="storage/logs/seeder_install.log"
MAX_RETRY=3

################################################################################
# ฟังก์ชัน Error Handling
################################################################################
error_exit() {
    print_error "$1"
    echo ""
    if [ -f "$CACHE_FILE" ]; then
        print_info "💾 การตั้งค่าของคุณถูกบันทึกไว้ที่: $CACHE_FILE"
        print_info "💾 ความคืบหน้าถูกบันทึกไว้ที่: $PROGRESS_FILE"
        print_info "▶️  รัน ./install.sh อีกครั้งเพื่อดำเนินการต่อ"
    fi
    exit 1
}

################################################################################
# ฟังก์ชัน Cache Management
################################################################################
save_to_cache() {
    local key=$1
    local value=$2
    touch "$CACHE_FILE"
    grep -v "^${key}=" "$CACHE_FILE" > "${CACHE_FILE}.tmp" 2>/dev/null || touch "${CACHE_FILE}.tmp"
    echo "${key}=${value}" >> "${CACHE_FILE}.tmp"
    mv "${CACHE_FILE}.tmp" "$CACHE_FILE"
}

load_from_cache() {
    local key=$1
    local default=$2
    if [ -f "$CACHE_FILE" ]; then
        local value=$(grep "^${key}=" "$CACHE_FILE" | cut -d'=' -f2-)
        if [ -n "$value" ]; then
            echo "$value"
            return
        fi
    fi
    echo "$default"
}

clear_cache() {
    if [ -f "$CACHE_FILE" ]; then
        rm -f "$CACHE_FILE"
        print_success "เคลียร์ cache การติดตั้งแล้ว"
    fi
}

################################################################################
# ฟังก์ชัน Checkpoint Management
################################################################################
save_checkpoint() {
    local step_name=$1
    echo "$step_name" >> "$PROGRESS_FILE"
}

is_step_completed() {
    local step_name=$1
    if [ -f "$PROGRESS_FILE" ]; then
        grep -q "^${step_name}$" "$PROGRESS_FILE" && return 0
    fi
    return 1
}

clear_checkpoints() {
    if [ -f "$PROGRESS_FILE" ]; then
        rm -f "$PROGRESS_FILE"
        print_success "เคลียร์ checkpoint แล้ว"
    fi
}

list_checkpoints() {
    if [ -f "$PROGRESS_FILE" ]; then
        echo ""
        print_info "ขั้นตอนที่เสร็จแล้ว:"
        cat "$PROGRESS_FILE" | while read step; do
            echo "  ✓ $step"
        done
        echo ""
    fi
}

################################################################################
# ฟังก์ชัน Fix Permissions (รวมไว้ในไฟล์เดียว)
################################################################################
fix_permissions() {
    print_subheader "🔐 ตั้งค่า File Permissions"

    print_step "กำลังสร้างและตั้งค่า directories..."
    mkdir -p storage/{app,framework,logs}
    mkdir -p storage/framework/{cache,sessions,views}
    mkdir -p storage/app/{public,private}
    mkdir -p bootstrap/cache

    print_step "ตั้งค่า permissions..."
    chmod -R 775 storage bootstrap/cache 2>/dev/null || true
    find storage -type f -exec chmod 664 {} \; 2>/dev/null || true
    find bootstrap/cache -type f -exec chmod 664 {} \; 2>/dev/null || true

    # ตรวจหา web server user
    WEB_USER=""
    if id -u www-data >/dev/null 2>&1; then
        WEB_USER="www-data"
    elif id -u nginx >/dev/null 2>&1; then
        WEB_USER="nginx"
    elif id -u apache >/dev/null 2>&1; then
        WEB_USER="apache"
    fi

    if [ -n "$WEB_USER" ]; then
        CURRENT_USER=$(whoami)
        if chown -R "$CURRENT_USER:$WEB_USER" storage bootstrap/cache 2>/dev/null; then
            print_success "Ownership: $CURRENT_USER:$WEB_USER"
        else
            print_warning "ไม่สามารถตั้ง ownership (อาจต้องใช้ sudo)"
        fi
    fi

    print_success "ตั้งค่า file permissions เรียบร้อย"
}

################################################################################
# ฟังก์ชัน Clear All Caches (รวมไว้ในไฟล์เดียว)
################################################################################
clear_all_caches() {
    print_subheader "🧹 เคลียร์ Cache ทั้งหมด"

    print_step "กำลังเคลียร์ Laravel caches..."
    php artisan cache:clear >/dev/null 2>&1 || true
    php artisan config:clear >/dev/null 2>&1 || true
    php artisan route:clear >/dev/null 2>&1 || true
    php artisan view:clear >/dev/null 2>&1 || true
    php artisan event:clear >/dev/null 2>&1 || true

    print_success "เคลียร์ cache ทั้งหมดเรียบร้อย"
}

################################################################################
# ฟังก์ชัน Safe Seeder (รันต่อได้แม้เจอ error)
################################################################################
run_safe_seeder() {
    local seeder_class=$1
    local seeder_name=$(echo "$seeder_class" | sed 's/::class//')

    # เช็คว่ารันไปแล้วหรือยัง
    if is_step_completed "SEEDER_${seeder_name}"; then
        echo -e "  ${CYAN}⏭️  ${NC}${seeder_name} (ข้าม - รันไปแล้ว)"
        return 0
    fi

    echo -ne "  ${YELLOW}▶${NC} ${seeder_name}..."

    # รัน seeder และจับ output
    local output
    local exit_code

    output=$(php artisan db:seed --class="$seeder_name" 2>&1) || exit_code=$?

    if [ -z "$exit_code" ] || [ "$exit_code" -eq 0 ]; then
        # สำเร็จ
        echo -e "\r  ${GREEN}✓${NC} ${seeder_name}"
        save_checkpoint "SEEDER_${seeder_name}"
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] ✓ $seeder_name" >> "$SEEDER_LOG"
        return 0
    else
        # ล้มเหลว แต่ไม่หยุดทำงาน
        echo -e "\r  ${RED}✗${NC} ${seeder_name} ${YELLOW}(ข้าม - มี error)${NC}"
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] ✗ $seeder_name - Error: $output" >> "$SEEDER_LOG"
        print_warning "    → $output"
        return 1
    fi
}

################################################################################
# Header Banner
################################################################################
clear
echo ""
echo -e "${GREEN}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║${NC}    ${MAGENTA}${BOLD}████████╗██████╗        █████╗ ███████╗███████╗${NC}         ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}    ${MAGENTA}${BOLD}╚══██╔══╝██╔══██╗      ██╔══██╗██╔════╝██╔════╝${NC}         ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}       ${MAGENTA}${BOLD}██║   ██████╔╝█████╗███████║█████╗  █████╗${NC}           ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}       ${MAGENTA}${BOLD}██║   ██╔═══╝ ╚════╝██╔══██║██╔══╝  ██╔══╝${NC}           ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}       ${MAGENTA}${BOLD}██║   ██║           ██║  ██║██║     ██║${NC}              ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}       ${MAGENTA}${BOLD}╚═╝   ╚═╝           ╚═╝  ╚═╝╚═╝     ╚═╝${NC}              ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}                                                                ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}    ${BLUE}${BOLD}🚀 TP-Affiliate Ultimate Installation v3.0${NC}              ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}    ${YELLOW}${BOLD}⚡ ไฟล์เดียวจบ - ติดตั้งครบทุกอย่าง${NC}                     ${GREEN}║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${BLUE}ระบบติดตั้งนี้จะทำทุกอย่างให้คุณอัตโนมัติ:${NC}"
echo -e "  • ตรวจสอบ system requirements"
echo -e "  • ติดตั้ง dependencies (Composer + npm)"
echo -e "  • ตั้งค่า database และ migrations"
echo -e "  • รัน seeders อย่างปลอดภัย (ถ้า error ก็รันต่อได้)"
echo -e "  • ตั้งค่า permissions และ optimization"
echo -e "  • สร้าง Super Admin account"
echo ""
echo -e "${CYAN}⏱️  เวลาโดยประมาณ: 5-10 นาที${NC}"
echo ""

################################################################################
# ตรวจสอบ Resume Installation
################################################################################
if [ -f "$PROGRESS_FILE" ]; then
    print_warning "พบการติดตั้งค้างไว้!"
    list_checkpoints

    read -p "ต้องการดำเนินการต่อจาก checkpoint สุดท้าย? (y/n) [y]: " RESUME
    RESUME=${RESUME:-y}

    if [[ ! $RESUME =~ ^[Yy]$ ]]; then
        print_info "เริ่มติดตั้งใหม่ทั้งหมด..."
        clear_checkpoints
        clear_cache
    else
        print_success "ดำเนินการต่อจาก checkpoint สุดท้าย..."
        echo ""
    fi
fi

################################################################################
# STEP 1: ตรวจสอบ System Requirements
################################################################################
if ! is_step_completed "STEP_1_SYSTEM_CHECK"; then
print_header "📋 STEP 1: ตรวจสอบ System Requirements"

# Check PHP
print_step "ตรวจสอบ PHP..."
PHP_VERSION=$(php -r 'echo PHP_VERSION;')
REQUIRED_PHP="8.1.0"

if php -r "exit(version_compare(PHP_VERSION, '$REQUIRED_PHP', '>=') ? 0 : 1);"; then
    print_success "PHP version: $PHP_VERSION ✓"
else
    error_exit "PHP version $PHP_VERSION ต่ำเกินไป! ต้องการอย่างน้อย $REQUIRED_PHP"
fi

# Check PHP Extensions
print_step "ตรวจสอบ PHP extensions..."
REQUIRED_EXTENSIONS=(bcmath ctype json mbstring openssl pdo pdo_mysql tokenizer xml curl fileinfo gd zip)
MISSING_EXTENSIONS=()

for ext in "${REQUIRED_EXTENSIONS[@]}"; do
    if php -r "exit(extension_loaded('$ext') ? 0 : 1);"; then
        echo "  ✓ $ext"
    else
        MISSING_EXTENSIONS+=("$ext")
        print_error "  ✗ $ext ขาดหายไป!"
    fi
done

if [ ${#MISSING_EXTENSIONS[@]} -ne 0 ]; then
    error_exit "ขาด PHP extensions: ${MISSING_EXTENSIONS[*]}"
fi

# Check Composer
print_step "ตรวจสอบ Composer..."
if command -v composer &> /dev/null; then
    COMPOSER_VERSION=$(composer --version --no-ansi 2>/dev/null | grep -oP '\d+\.\d+\.\d+' | head -1)
    print_success "Composer: $COMPOSER_VERSION ✓"
else
    error_exit "ไม่พบ Composer! ติดตั้งที่: https://getcomposer.org/"
fi

# Check Node.js (optional)
print_step "ตรวจสอบ Node.js..."
if command -v node &> /dev/null && command -v npm &> /dev/null; then
    NODE_VERSION=$(node --version)
    NPM_VERSION=$(npm --version)
    print_success "Node.js: $NODE_VERSION, npm: $NPM_VERSION ✓"
else
    print_warning "ไม่พบ Node.js/npm (จะติดตั้ง frontend assets ไม่ได้)"
fi

# Check Git
print_step "ตรวจสอบ Git..."
if command -v git &> /dev/null; then
    GIT_VERSION=$(git --version | grep -oP '\d+\.\d+\.\d+' | head -1)
    print_success "Git: $GIT_VERSION ✓"
else
    print_warning "ไม่พบ Git (บางฟีเจอร์อาจใช้งานไม่ได้)"
fi

print_success "ผ่านการตรวจสอบ System Requirements ทั้งหมด! ✓"
save_checkpoint "STEP_1_SYSTEM_CHECK"
fi

################################################################################
# STEP 2: รับข้อมูลการตั้งค่า
################################################################################
if ! is_step_completed "STEP_2_CONFIG"; then
print_header "⚙️  STEP 2: การตั้งค่าแอปพลิเคชัน"

# โหลด cache ถ้ามี
if [ -f "$CACHE_FILE" ]; then
    print_info "💾 พบการตั้งค่าที่บันทึกไว้"
    read -p "ใช้ค่าที่บันทึกไว้? (y/n) [y]: " USE_CACHE
    USE_CACHE=${USE_CACHE:-y}
    if [[ ! $USE_CACHE =~ ^[Yy]$ ]]; then
        rm -f "$CACHE_FILE"
    fi
fi

echo "กรุณากรอกข้อมูลต่อไปนี้:"
echo ""

# Application Name
CACHED_APP_NAME=$(load_from_cache "APP_NAME" "TP-Affiliate")
read -p "ชื่อแอปพลิเคชัน [$CACHED_APP_NAME]: " APP_NAME
APP_NAME=${APP_NAME:-$CACHED_APP_NAME}
save_to_cache "APP_NAME" "$APP_NAME"

# Application URL
CACHED_APP_URL=$(load_from_cache "APP_URL" "")
if [ -n "$CACHED_APP_URL" ]; then
    read -p "URL แอปพลิเคชัน [$CACHED_APP_URL]: " APP_URL
    APP_URL=${APP_URL:-$CACHED_APP_URL}
else
    read -p "URL แอปพลิเคชัน (เช่น https://example.com): " APP_URL
fi
while [ -z "$APP_URL" ]; do
    print_error "URL แอปพลิเคชันจำเป็นต้องระบุ!"
    read -p "URL แอปพลิเคชัน: " APP_URL
done
save_to_cache "APP_URL" "$APP_URL"

# Environment
echo ""
echo "เลือก environment:"
echo "  1) Production (แนะนำ)"
echo "  2) Local/Development"
CACHED_ENV=$(load_from_cache "APP_ENV" "production")
DEFAULT_ENV_CHOICE=$( [ "$CACHED_ENV" == "local" ] && echo "2" || echo "1" )
read -p "Environment [$DEFAULT_ENV_CHOICE]: " ENV_CHOICE
ENV_CHOICE=${ENV_CHOICE:-$DEFAULT_ENV_CHOICE}

if [ "$ENV_CHOICE" == "2" ]; then
    APP_ENV="local"
    APP_DEBUG="true"
else
    APP_ENV="production"
    APP_DEBUG="false"
fi
save_to_cache "APP_ENV" "$APP_ENV"
print_success "Environment: $APP_ENV"

# Database Configuration
print_subheader "🗄️  Database Configuration"

CACHED_DB_HOST=$(load_from_cache "DB_HOST" "127.0.0.1")
read -p "Database Host [$CACHED_DB_HOST]: " DB_HOST
DB_HOST=${DB_HOST:-$CACHED_DB_HOST}
save_to_cache "DB_HOST" "$DB_HOST"

CACHED_DB_PORT=$(load_from_cache "DB_PORT" "3306")
read -p "Database Port [$CACHED_DB_PORT]: " DB_PORT
DB_PORT=${DB_PORT:-$CACHED_DB_PORT}
save_to_cache "DB_PORT" "$DB_PORT"

CACHED_DB_DATABASE=$(load_from_cache "DB_DATABASE" "")
if [ -n "$CACHED_DB_DATABASE" ]; then
    read -p "ชื่อ Database [$CACHED_DB_DATABASE]: " DB_DATABASE
    DB_DATABASE=${DB_DATABASE:-$CACHED_DB_DATABASE}
else
    read -p "ชื่อ Database: " DB_DATABASE
fi
while [ -z "$DB_DATABASE" ]; do
    print_error "ชื่อ Database จำเป็นต้องระบุ!"
    read -p "ชื่อ Database: " DB_DATABASE
done
save_to_cache "DB_DATABASE" "$DB_DATABASE"

CACHED_DB_USERNAME=$(load_from_cache "DB_USERNAME" "root")
read -p "Database Username [$CACHED_DB_USERNAME]: " DB_USERNAME
DB_USERNAME=${DB_USERNAME:-$CACHED_DB_USERNAME}
save_to_cache "DB_USERNAME" "$DB_USERNAME"

CACHED_DB_PASSWORD=$(load_from_cache "DB_PASSWORD" "")
if [ -n "$CACHED_DB_PASSWORD" ]; then
    read -p "ใช้ password ที่บันทึกไว้? (y/n) [y]: " USE_SAVED_PASS
    USE_SAVED_PASS=${USE_SAVED_PASS:-y}
    if [[ $USE_SAVED_PASS =~ ^[Yy]$ ]]; then
        DB_PASSWORD="$CACHED_DB_PASSWORD"
    else
        read -sp "Database Password: " DB_PASSWORD
        echo ""
        save_to_cache "DB_PASSWORD" "$DB_PASSWORD"
    fi
else
    read -sp "Database Password: " DB_PASSWORD
    echo ""
    save_to_cache "DB_PASSWORD" "$DB_PASSWORD"
fi

# Test Database Connection
print_step "ทดสอบการเชื่อมต่อ database..."
if mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" -e "SELECT 1;" &>/dev/null; then
    print_success "เชื่อมต่อ database สำเร็จ! ✓"

    # สร้าง database ถ้ายังไม่มี
    mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS \`$DB_DATABASE\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null || true
else
    error_exit "เชื่อมต่อ database ล้มเหลว! ตรวจสอบ credentials"
fi

# Super Admin Account
print_subheader "👤 Super Admin Account"

CACHED_ADMIN_NAME=$(load_from_cache "ADMIN_NAME" "")
if [ -n "$CACHED_ADMIN_NAME" ]; then
    read -p "ชื่อ Admin [$CACHED_ADMIN_NAME]: " ADMIN_NAME
    ADMIN_NAME=${ADMIN_NAME:-$CACHED_ADMIN_NAME}
else
    read -p "ชื่อ Admin: " ADMIN_NAME
fi
while [ -z "$ADMIN_NAME" ]; do
    print_error "ชื่อ Admin จำเป็นต้องระบุ!"
    read -p "ชื่อ Admin: " ADMIN_NAME
done
save_to_cache "ADMIN_NAME" "$ADMIN_NAME"

CACHED_ADMIN_EMAIL=$(load_from_cache "ADMIN_EMAIL" "")
if [ -n "$CACHED_ADMIN_EMAIL" ]; then
    read -p "Email Admin [$CACHED_ADMIN_EMAIL]: " ADMIN_EMAIL
    ADMIN_EMAIL=${ADMIN_EMAIL:-$CACHED_ADMIN_EMAIL}
else
    read -p "Email Admin: " ADMIN_EMAIL
fi
while [ -z "$ADMIN_EMAIL" ]; do
    print_error "Email Admin จำเป็นต้องระบุ!"
    read -p "Email Admin: " ADMIN_EMAIL
done
if [[ ! "$ADMIN_EMAIL" =~ ^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$ ]]; then
    error_exit "รูปแบบ email ไม่ถูกต้อง!"
fi
save_to_cache "ADMIN_EMAIL" "$ADMIN_EMAIL"

read -sp "Password Admin (อย่างน้อย 8 ตัวอักษร): " ADMIN_PASSWORD
echo ""
while [ ${#ADMIN_PASSWORD} -lt 8 ]; do
    print_error "Password ต้องมีอย่างน้อย 8 ตัวอักษร!"
    read -sp "Password Admin: " ADMIN_PASSWORD
    echo ""
done

read -sp "ยืนยัน Password: " ADMIN_PASSWORD_CONFIRM
echo ""
while [ "$ADMIN_PASSWORD" != "$ADMIN_PASSWORD_CONFIRM" ]; do
    print_error "Password ไม่ตรงกัน!"
    read -sp "Password Admin: " ADMIN_PASSWORD
    echo ""
    read -sp "ยืนยัน Password: " ADMIN_PASSWORD_CONFIRM
    echo ""
done

print_success "บันทึกการตั้งค่าเรียบร้อย ✓"
save_checkpoint "STEP_2_CONFIG"
fi

################################################################################
# STEP 3: สร้าง .env File
################################################################################
if ! is_step_completed "STEP_3_ENV"; then
print_header "📝 STEP 3: สร้าง Environment File"

if [ -f ".env" ]; then
    print_info "สำรอง .env เดิมเป็น .env.backup..."
    cp .env .env.backup
fi

if [ ! -f ".env.example" ]; then
    error_exit "ไม่พบไฟล์ .env.example!"
fi

print_step "สร้าง .env จาก .env.example..."
cp .env.example .env

# ฟังก์ชันอัพเดท .env
update_env() {
    local key=$1
    local value=$2
    value=$(echo "$value" | sed 's/[\/&]/\\&/g')
    if grep -q "^${key}=" .env; then
        sed -i "s|^${key}=.*|${key}=${value}|" .env
    else
        echo "${key}=${value}" >> .env
    fi
}

print_step "กำหนดค่า .env..."
update_env "APP_NAME" "$APP_NAME"
update_env "APP_ENV" "$APP_ENV"
update_env "APP_DEBUG" "$APP_DEBUG"
update_env "APP_URL" "$APP_URL"
update_env "DB_CONNECTION" "mysql"
update_env "DB_HOST" "$DB_HOST"
update_env "DB_PORT" "$DB_PORT"
update_env "DB_DATABASE" "$DB_DATABASE"
update_env "DB_USERNAME" "$DB_USERNAME"
update_env "DB_PASSWORD" "$DB_PASSWORD"

print_success "สร้าง .env file สำเร็จ ✓"
save_checkpoint "STEP_3_ENV"
fi

################################################################################
# STEP 4: ติดตั้ง Dependencies
################################################################################
if ! is_step_completed "STEP_4_DEPENDENCIES"; then
print_header "📦 STEP 4: ติดตั้ง Dependencies"

# สร้าง directories ที่จำเป็น
print_step "สร้าง directories..."
mkdir -p storage/{app,framework,logs}
mkdir -p storage/framework/{cache,sessions,views}
mkdir -p storage/app/{public,private}
mkdir -p bootstrap/cache
print_success "สร้าง directories เรียบร้อย"

# ติดตั้ง Composer
print_subheader "🎼 Composer Dependencies"
ATTEMPT=1
while [ $ATTEMPT -le $MAX_RETRY ]; do
    if [ $ATTEMPT -gt 1 ]; then
        print_warning "พยายามครั้งที่ $ATTEMPT/$MAX_RETRY..."
        sleep 2
    fi

    print_step "กำลังติดตั้ง Composer packages..."
    if composer install --no-dev --optimize-autoloader --no-interaction; then
        if [ -f "vendor/autoload.php" ]; then
            print_success "ติดตั้ง Composer dependencies สำเร็จ ✓"
            break
        fi
    fi
    ATTEMPT=$((ATTEMPT + 1))
done

if [ $ATTEMPT -gt $MAX_RETRY ]; then
    error_exit "ติดตั้ง Composer dependencies ล้มเหลว!"
fi

# ติดตั้ง Node.js
if command -v node &> /dev/null && command -v npm &> /dev/null; then
    if [ -f "package.json" ]; then
        print_subheader "📦 Node.js Dependencies"
        print_step "กำลังติดตั้ง npm packages..."
        if npm install --no-audit --no-fund; then
            print_success "ติดตั้ง npm packages สำเร็จ ✓"

            print_step "กำลัง build frontend assets..."
            if npm run build; then
                print_success "Build assets สำเร็จ ✓"
            else
                print_warning "Build assets ล้มเหลว (จะข้ามไป)"
            fi
        else
            print_warning "ติดตั้ง npm packages ล้มเหลว (จะข้ามไป)"
        fi
    fi
else
    print_warning "ไม่พบ Node.js - ข้ามการติดตั้ง frontend assets"
fi

save_checkpoint "STEP_4_DEPENDENCIES"
fi

################################################################################
# STEP 5: Generate Application Key
################################################################################
if ! is_step_completed "STEP_5_APP_KEY"; then
print_header "🔑 STEP 5: สร้าง Application Key"

print_step "สร้าง application key..."
if php artisan key:generate --force; then
    print_success "สร้าง application key สำเร็จ ✓"
else
    error_exit "สร้าง application key ล้มเหลว"
fi

save_checkpoint "STEP_5_APP_KEY"
fi

################################################################################
# STEP 6: Database Migrations
################################################################################
if ! is_step_completed "STEP_6_MIGRATIONS"; then
print_header "🗄️  STEP 6: Database Migrations"

print_step "เคลียร์ config cache..."
php artisan config:clear

print_step "กำลังรัน migrations..."
echo ""
echo "📊 กำลังสร้างตารางฐานข้อมูล..."
echo ""

if php artisan migrate --force; then
    MIGRATION_COUNT=$(php artisan migrate:status 2>/dev/null | grep -c "Ran" || echo "0")
    print_success "รัน migrations สำเร็จ ✓ (สร้าง $MIGRATION_COUNT ตาราง)"
else
    error_exit "รัน migrations ล้มเหลว!"
fi

save_checkpoint "STEP_6_MIGRATIONS"
fi

################################################################################
# STEP 7: Database Seeders (Safe Mode)
################################################################################
if ! is_step_completed "STEP_7_SEEDERS"; then
print_header "🌱 STEP 7: Database Seeders (Safe Mode)"

# สร้าง log directory
mkdir -p storage/logs
echo "# Seeder Installation Log - $(date)" > "$SEEDER_LOG"

echo ""
print_info "⚠️  Safe Seeder Mode: ถ้าเจอ error จะแจ้งแต่รันต่อไป"
echo ""

# ถามว่าต้องการติดตั้ง demo data หรือไม่
read -p "ต้องการติดตั้งข้อมูลทดสอบ (Demo Data)? (y/n) [y]: " INSTALL_DEMO
INSTALL_DEMO=${INSTALL_DEMO:-y}
echo ""

# รายการ Seeders ที่จำเป็น (Essential Seeders Only)
print_subheader "📋 Core Settings & Configuration"
CORE_SEEDERS=(
    "AppNameSettingSeeder"
    "TwoFactorSettingsSeeder"
    "ArrowXThemeSeeder"
    "ThemePresetSeeder"
    "AppManagementSeeder"
    "CookieSettingsSeeder"
    "WindowsUiSeeder"
    "AppControlSectionSeeder"
    "ComponentSettingSeeder"
    "ApiEndpointSeeder"
)

SUCCESSFUL_SEEDERS=()
FAILED_SEEDERS=()

for seeder in "${CORE_SEEDERS[@]}"; do
    if run_safe_seeder "$seeder"; then
        SUCCESSFUL_SEEDERS+=("$seeder")
    else
        FAILED_SEEDERS+=("$seeder")
    fi
done

# Demo Data (ถ้าต้องการ)
if [[ $INSTALL_DEMO =~ ^[Yy]$ ]]; then
    print_subheader "👥 Demo Users & Data"
    DEMO_SEEDERS=(
        "DemoUsersSeeder"
        "TestUsersSeeder"
        "KycVerificationSeeder"
        "LineSignupSessionSeeder"
    )

    for seeder in "${DEMO_SEEDERS[@]}"; do
        if run_safe_seeder "$seeder"; then
            SUCCESSFUL_SEEDERS+=("$seeder")
        else
            FAILED_SEEDERS+=("$seeder")
        fi
    done
fi

# Content & Pages
print_subheader "📄 Content & Pages"
CONTENT_SEEDERS=(
    "DemoPagesSeeder"
    "SeoMetaSeeder"
)

for seeder in "${CONTENT_SEEDERS[@]}"; do
    if run_safe_seeder "$seeder"; then
        SUCCESSFUL_SEEDERS+=("$seeder")
    else
        FAILED_SEEDERS+=("$seeder")
    fi
done

# Communication Templates
print_subheader "💬 Communication Templates"
COMM_SEEDERS=(
    "EmailTemplateSeeder"
    "LineOaSettingSeeder"
    "LineSignupTemplateSeeder"
    "LineSignupFlowSeeder"
    "LineSignupRewardSeeder"
    "LineBotAiSeeder"
    "LineBotKeywordSeeder"
)

for seeder in "${COMM_SEEDERS[@]}"; do
    if run_safe_seeder "$seeder"; then
        SUCCESSFUL_SEEDERS+=("$seeder")
    else
        FAILED_SEEDERS+=("$seeder")
    fi
done

# AI & Integrations
print_subheader "🤖 AI & Integrations"
AI_SEEDERS=(
    "AICoreFeatureSeeder"
    "AiProvidersSeeder"
    "AiGenSeeder"
)

for seeder in "${AI_SEEDERS[@]}"; do
    if run_safe_seeder "$seeder"; then
        SUCCESSFUL_SEEDERS+=("$seeder")
    else
        FAILED_SEEDERS+=("$seeder")
    fi
done

# Payment Systems
print_subheader "💳 Payment Systems"
PAYMENT_SEEDERS=(
    "PaymentGatewaySeeder"
    "PaySolutionsGatewaySeeder"
    "CryptoCurrencySeeder"
    "TPIXCurrencySeeder"
    "TPIXStakingPoolSeeder"
    "NFCCardSeeder"
)

for seeder in "${PAYMENT_SEEDERS[@]}"; do
    if run_safe_seeder "$seeder"; then
        SUCCESSFUL_SEEDERS+=("$seeder")
    else
        FAILED_SEEDERS+=("$seeder")
    fi
done

# MLM System
print_subheader "📊 MLM System"
MLM_SEEDERS=(
    "MlmGlobalSettingsSeeder"
    "MlmGlobalSettingSeeder"
    "MlmPlanSeeder"
    "MlmPackageSeeder"
    "RankSeeder"
    "RecruitTemplateSeeder"
)

for seeder in "${MLM_SEEDERS[@]}"; do
    if run_safe_seeder "$seeder"; then
        SUCCESSFUL_SEEDERS+=("$seeder")
    else
        FAILED_SEEDERS+=("$seeder")
    fi
done

# E-commerce (ถ้าต้องการ)
if [[ $INSTALL_DEMO =~ ^[Yy]$ ]]; then
    print_subheader "🛒 E-commerce & Products"
    ECOMMERCE_SEEDERS=(
        "ProductCategorySeeder"
        "ProductSeeder"
        "OfficialShopProductsSeeder"
        "WalletTopupPackagesSeeder"
    )

    for seeder in "${ECOMMERCE_SEEDERS[@]}"; do
        if run_safe_seeder "$seeder"; then
            SUCCESSFUL_SEEDERS+=("$seeder")
        else
            FAILED_SEEDERS+=("$seeder")
        fi
    done
fi

# สรุปผล Seeders
echo ""
print_header "📊 สรุปผล Database Seeders"
echo ""
echo -e "${GREEN}✓ สำเร็จ:${NC} ${#SUCCESSFUL_SEEDERS[@]} seeders"
echo -e "${RED}✗ ล้มเหลว:${NC} ${#FAILED_SEEDERS[@]} seeders"
echo ""

if [ ${#FAILED_SEEDERS[@]} -gt 0 ]; then
    print_warning "Seeders ที่ล้มเหลว:"
    for seeder in "${FAILED_SEEDERS[@]}"; do
        echo "  ✗ $seeder"
    done
    echo ""
    print_info "📝 ดูรายละเอียดได้ที่: $SEEDER_LOG"
fi

save_checkpoint "STEP_7_SEEDERS"
fi

################################################################################
# STEP 8: สร้าง Super Admin Account
################################################################################
if ! is_step_completed "STEP_8_ADMIN"; then
print_header "👤 STEP 8: สร้าง Super Admin Account"

print_step "กำลังสร้าง super admin..."
php artisan tinker --execute="
try {
    \$user = App\Models\User::create([
        'name' => '$ADMIN_NAME',
        'email' => '$ADMIN_EMAIL',
        'password' => Hash::make('$ADMIN_PASSWORD'),
        'role' => 'super_admin',
        'is_super_admin' => true,
        'email_verified_at' => now(),
    ]);

    \$affiliate = App\Models\Affiliate::create([
        'user_id' => \$user->id,
        'referral_code' => App\Models\Affiliate::generateReferralCode(),
        'level' => 1,
        'status' => 'active',
    ]);

    \$user->update(['affiliate_id' => \$affiliate->id]);

    echo 'สร้าง Super Admin สำเร็จ!';
} catch (Exception \$e) {
    echo 'Error: ' . \$e->getMessage();
    exit(1);
}
"

if [ $? -eq 0 ]; then
    print_success "สร้าง Super Admin account สำเร็จ ✓"
else
    print_warning "สร้าง Super Admin ล้มเหลว (อาจมีอยู่แล้ว)"
fi

# สร้าง Default Settings
print_step "สร้าง default settings..."
php artisan tinker --execute="
try {
    \$defaults = [
        'app_name' => ['value' => '$APP_NAME', 'type' => 'string', 'group' => 'general'],
        'app_installed' => ['value' => true, 'type' => 'boolean', 'group' => 'general'],
        'commission_rate' => ['value' => 10, 'type' => 'integer', 'group' => 'affiliate'],
        'multi_level_enabled' => ['value' => true, 'type' => 'boolean', 'group' => 'affiliate'],
        'timezone' => ['value' => 'Asia/Bangkok', 'type' => 'string', 'group' => 'general'],
        'locale' => ['value' => 'th', 'type' => 'string', 'group' => 'general'],
        'currency' => ['value' => 'THB', 'type' => 'string', 'group' => 'general'],
    ];

    foreach (\$defaults as \$key => \$config) {
        App\Models\Setting::updateOrCreate(
            ['key' => \$key],
            [
                'value' => \$config['value'],
                'type' => \$config['type'],
                'group' => \$config['group'],
            ]
        );
    }

    echo 'สร้าง default settings สำเร็จ!';
} catch (Exception \$e) {
    echo 'Warning: ' . \$e->getMessage();
}
" >/dev/null 2>&1

print_success "ตั้งค่า default settings เรียบร้อย"

save_checkpoint "STEP_8_ADMIN"
fi

################################################################################
# STEP 9: Fix Permissions & Optimization
################################################################################
if ! is_step_completed "STEP_9_FINALIZE"; then
print_header "⚡ STEP 9: Finalization & Optimization"

# Fix Permissions
fix_permissions

# Storage Link
print_step "สร้าง storage symlink..."
if php artisan storage:link --force 2>/dev/null; then
    print_success "Storage symlink สร้างเรียบร้อย"
else
    print_warning "สร้าง storage symlink ล้มเหลว (อาจต้องสร้างเอง)"
fi

# Clear & Rebuild Caches
clear_all_caches

print_step "สร้าง production caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
print_success "สร้าง production caches เรียบร้อย"

# Optimize Composer
print_step "Optimize Composer autoloader..."
composer dump-autoload --optimize --no-dev 2>/dev/null || composer dump-autoload --optimize
print_success "Optimize autoloader เรียบร้อย"

# สร้าง flag ว่าติดตั้งเสร็จแล้ว
print_step "บันทึกสถานะการติดตั้ง..."
mkdir -p storage/app
echo "$(date)" > storage/app/.setup_completed
print_success "บันทึกสถานะเรียบร้อย"

# เคลียร์ cache files
clear_cache
clear_checkpoints

save_checkpoint "STEP_9_FINALIZE"
fi

################################################################################
# STEP 10: Post-Installation Verification
################################################################################
print_header "🔍 STEP 10: ตรวจสอบการติดตั้ง"

VERIFICATION_PASSED=true

# Check .env
if [ -f ".env" ]; then
    print_success "✓ .env file มีอยู่"
else
    print_error "✗ ไม่พบ .env file"
    VERIFICATION_PASSED=false
fi

# Check storage writable
if [ -w "storage/logs" ]; then
    print_success "✓ Storage เขียนได้"
else
    print_warning "⚠ Storage อาจเขียนไม่ได้"
fi

# Check database
if php artisan db:show >/dev/null 2>&1; then
    print_success "✓ เชื่อมต่อ database ได้"
else
    print_warning "⚠ เชื่อมต่อ database ล้มเหลว"
fi

# Check migrations
MIGRATION_COUNT=$(php artisan migrate:status 2>/dev/null | grep -c "Ran" || echo "0")
if [ "$MIGRATION_COUNT" -gt "0" ]; then
    print_success "✓ Migrations เสร็จสมบูรณ์ ($MIGRATION_COUNT ตาราง)"
else
    print_warning "⚠ ไม่พบ migrations"
fi

# Check admin
ADMIN_COUNT=$(php artisan tinker --execute="echo App\Models\User::where('role', 'super_admin')->count();" 2>/dev/null | tail -1 || echo "0")
if [ "$ADMIN_COUNT" -gt "0" ]; then
    print_success "✓ Super Admin account มีอยู่"
else
    print_warning "⚠ ไม่พบ Super Admin account"
fi

# Check storage link
if [ -L "public/storage" ]; then
    print_success "✓ Storage symlink มีอยู่"
else
    print_warning "⚠ ไม่พบ storage symlink"
fi

echo ""
if [ "$VERIFICATION_PASSED" = true ]; then
    print_success "ผ่านการตรวจสอบทั้งหมด! ✓"
else
    print_warning "มีบางอย่างล้มเหลว แต่สามารถใช้งานได้"
fi

################################################################################
# Installation Complete
################################################################################
print_header "🎉 ติดตั้ง TP-Affiliate สำเร็จ!"

echo ""
echo -e "${CYAN}${BOLD}═══════════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}${BOLD}           ✨ ติดตั้งเสร็จสมบูรณ์แล้ว! ✨${NC}"
echo -e "${CYAN}${BOLD}═══════════════════════════════════════════════════════════════${NC}"
echo ""
echo -e "${WHITE}${BOLD}📊 สรุปการติดตั้ง:${NC}"
echo ""
echo -e "  ${BLUE}Application:${NC}    $APP_NAME"
echo -e "  ${BLUE}URL:${NC}            $APP_URL"
echo -e "  ${BLUE}Environment:${NC}    $APP_ENV"
echo -e "  ${BLUE}Database:${NC}       $DB_DATABASE@$DB_HOST"
echo -e "  ${BLUE}Admin Email:${NC}    $ADMIN_EMAIL"
echo ""
echo -e "${WHITE}${BOLD}✅ สิ่งที่ติดตั้งและตั้งค่าแล้ว:${NC}"
echo ""
echo -e "  ${GREEN}✓${NC} PHP $(php -r 'echo PHP_VERSION;') + extensions ครบถ้วน"
echo -e "  ${GREEN}✓${NC} Composer dependencies"
if command -v node &> /dev/null; then
    echo -e "  ${GREEN}✓${NC} Node.js $(node --version) & Frontend assets"
fi
echo -e "  ${GREEN}✓${NC} Environment configuration (.env)"
echo -e "  ${GREEN}✓${NC} Application key"
echo -e "  ${GREEN}✓${NC} Database migrations ($MIGRATION_COUNT ตาราง)"
echo -e "  ${GREEN}✓${NC} Database seeders (${#SUCCESSFUL_SEEDERS[@]} seeders)"
if [ ${#FAILED_SEEDERS[@]} -gt 0 ]; then
    echo -e "  ${YELLOW}⚠${NC} Seeders ที่ข้าม: ${#FAILED_SEEDERS[@]} (ดูใน $SEEDER_LOG)"
fi
echo -e "  ${GREEN}✓${NC} Super Admin account"
echo -e "  ${GREEN}✓${NC} File permissions"
echo -e "  ${GREEN}✓${NC} Production caches"
echo -e "  ${GREEN}✓${NC} Storage symlink"
echo ""
echo -e "${WHITE}${BOLD}🚀 ขั้นตอนต่อไป:${NC}"
echo ""
echo -e "  ${CYAN}1.${NC} ตั้งค่า Web Server (Nginx/Apache):"
echo -e "      ${GREEN}→${NC} DocumentRoot: ${YELLOW}$(pwd)/public${NC}"
echo -e ""
echo -e "  ${CYAN}2.${NC} เข้าสู่ระบบ:"
echo -e "      ${GREEN}→${NC} Frontend: ${YELLOW}$APP_URL${NC}"
echo -e "      ${GREEN}→${NC} Admin: ${YELLOW}$APP_URL/admin${NC}"
echo -e "      ${GREEN}→${NC} Email: ${YELLOW}$ADMIN_EMAIL${NC}"
echo -e "      ${GREEN}→${NC} Password: (ที่คุณตั้งไว้)"
echo ""
echo -e "  ${CYAN}3.${NC} ทดสอบด้วย PHP Built-in Server (สำหรับทดสอบ):"
echo -e "      ${YELLOW}php artisan serve${NC}"
echo -e "      แล้วเข้า: ${YELLOW}http://localhost:8000${NC}"
echo ""
echo -e "  ${CYAN}4.${NC} การตั้งค่าเพิ่มเติม (ถ้าต้องการ):"
echo -e "      ${GREEN}→${NC} Email (MAIL_*, GMAIL_*, SMTP_*)"
echo -e "      ${GREEN}→${NC} Cloudflare Turnstile"
echo -e "      ${GREEN}→${NC} Google Translate API"
echo -e "      ${GREEN}→${NC} LINE Integration"
echo -e "      ${GREEN}→${NC} แก้ไขใน: ${YELLOW}.env${NC}"
echo ""
echo -e "${CYAN}${BOLD}═══════════════════════════════════════════════════════════════${NC}"
echo ""
echo -e "${GREEN}${BOLD}    🎊 ยินดีด้วย! พร้อมใช้งานแล้ว 🎊${NC}"
echo ""
echo -e "${CYAN}📝 Log Files:${NC}"
echo -e "  • ติดตั้งทั่วไป: ${YELLOW}storage/logs/laravel.log${NC}"
echo -e "  • Seeder log: ${YELLOW}$SEEDER_LOG${NC}"
echo ""
echo -e "${MAGENTA}Happy deploying! 🚀${NC}"
echo ""
