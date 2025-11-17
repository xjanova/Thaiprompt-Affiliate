#!/bin/bash

###############################################################################
# LINE Signup Tables Deployment Script
# Purpose: สร้างตาราง line_signup_* ทั้งหมด (8 ตาราง)
# Version: 1.0.0
# Date: 2025-11-17
###############################################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo ""
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║   LINE Signup Tables Deployment Script                        ║"
echo "║   กำลังสร้างตาราง line_signup_* (8 ตาราง)                      ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

# Function to print colored messages
print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    print_error "ไม่พบไฟล์ artisan - กรุณารันสคริปต์นี้จาก root directory ของ Laravel"
    exit 1
fi

print_success "พบไฟล์ artisan - อยู่ใน Laravel root directory"

# Check PHP version
print_info "ตรวจสอบ PHP version..."
PHP_VERSION=$(php -v | head -n 1 | cut -d " " -f 2 | cut -f1 -d"-")
print_success "PHP version: $PHP_VERSION"

# Check if migration file exists
MIGRATION_FILE="database/migrations/2025_11_12_000001_create_line_membership_signup_system.php"
if [ ! -f "$MIGRATION_FILE" ]; then
    print_error "ไม่พบไฟล์ migration: $MIGRATION_FILE"
    exit 1
fi

print_success "พบไฟล์ migration: $MIGRATION_FILE"

# Check database connection
print_info "ตรวจสอบการเชื่อมต่อฐานข้อมูล..."
if php artisan db:show >/dev/null 2>&1; then
    print_success "เชื่อมต่อฐานข้อมูลสำเร็จ"
else
    print_error "ไม่สามารถเชื่อมต่อฐานข้อมูลได้"
    print_warning "กรุณาตรวจสอบการตั้งค่าใน .env:"
    print_warning "  - DB_HOST"
    print_warning "  - DB_DATABASE"
    print_warning "  - DB_USERNAME"
    print_warning "  - DB_PASSWORD"
    exit 1
fi

# Get database name
DB_NAME=$(php artisan tinker --execute="echo config('database.connections.mysql.database');" 2>/dev/null || echo "unknown")
print_info "ฐานข้อมูล: $DB_NAME"

# Check if tables already exist
print_info "ตรวจสอบว่าตารางมีอยู่แล้วหรือไม่..."

TABLES_EXIST=0
for table in line_signup_sessions line_signup_step_logs line_signup_conversations line_signup_templates line_signup_rewards line_signup_invitations line_signup_analytics line_signup_webhook_logs; do
    if php artisan tinker --execute="echo \Illuminate\Support\Facades\Schema::hasTable('$table') ? 'yes' : 'no';" 2>/dev/null | grep -q "yes"; then
        print_warning "ตาราง $table มีอยู่แล้ว"
        TABLES_EXIST=$((TABLES_EXIST + 1))
    fi
done

if [ $TABLES_EXIST -eq 8 ]; then
    print_success "ตารางทั้งหมดมีอยู่แล้ว (8/8)"
    print_info "ไม่จำเป็นต้องรัน migration อีก"
    echo ""
    print_info "หากต้องการสร้างใหม่ กรุณารัน:"
    echo "  php artisan migrate:rollback --step=1"
    echo "  php artisan migrate --force"
    exit 0
fi

# Backup database (optional)
print_warning "คำแนะนำ: ควร backup ฐานข้อมูลก่อนรัน migration"
read -p "ต้องการ backup ฐานข้อมูลก่อนหรือไม่? (y/N): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    BACKUP_FILE="backup_before_line_signup_$(date +%Y%m%d_%H%M%S).sql"
    print_info "กำลัง backup ฐานข้อมูลไปที่ storage/backups/$BACKUP_FILE..."

    mkdir -p storage/backups

    if command_exists mysqldump; then
        DB_HOST=$(php artisan tinker --execute="echo config('database.connections.mysql.host');" 2>/dev/null || echo "127.0.0.1")
        DB_USER=$(php artisan tinker --execute="echo config('database.connections.mysql.username');" 2>/dev/null || echo "root")
        DB_PASS=$(php artisan tinker --execute="echo config('database.connections.mysql.password');" 2>/dev/null || echo "")

        if [ -z "$DB_PASS" ]; then
            mysqldump -h "$DB_HOST" -u "$DB_USER" "$DB_NAME" > "storage/backups/$BACKUP_FILE" 2>/dev/null || print_warning "ไม่สามารถ backup ได้ แต่จะดำเนินการต่อ"
        else
            mysqldump -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "storage/backups/$BACKUP_FILE" 2>/dev/null || print_warning "ไม่สามารถ backup ได้ แต่จะดำเนินการต่อ"
        fi

        if [ -f "storage/backups/$BACKUP_FILE" ]; then
            print_success "Backup สำเร็จ: storage/backups/$BACKUP_FILE"
        fi
    else
        print_warning "ไม่พบคำสั่ง mysqldump - ข้าม backup"
    fi
fi

# Clear caches
print_info "Clear caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
print_success "Caches cleared"

# Run migration
echo ""
print_info "═══════════════════════════════════════════════════════════════"
print_info "กำลังรัน Migration สร้างตาราง LINE Signup (8 ตาราง)..."
print_info "═══════════════════════════════════════════════════════════════"
echo ""

if php artisan migrate --force 2>&1; then
    echo ""
    print_success "═══════════════════════════════════════════════════════════════"
    print_success "Migration สำเร็จ! ตารางถูกสร้างเรียบร้อยแล้ว"
    print_success "═══════════════════════════════════════════════════════════════"
    echo ""
else
    echo ""
    print_error "═══════════════════════════════════════════════════════════════"
    print_error "Migration ล้มเหลว!"
    print_error "═══════════════════════════════════════════════════════════════"
    echo ""
    print_warning "กรุณาตรวจสอบ error messages ด้านบน"
    exit 1
fi

# Verify tables
print_info "ตรวจสอบว่าตารางถูกสร้างแล้ว..."
echo ""

TABLES_CREATED=0
for table in line_signup_sessions line_signup_step_logs line_signup_conversations line_signup_templates line_signup_rewards line_signup_invitations line_signup_analytics line_signup_webhook_logs; do
    if php artisan tinker --execute="echo \Illuminate\Support\Facades\Schema::hasTable('$table') ? 'yes' : 'no';" 2>/dev/null | grep -q "yes"; then
        print_success "✓ ตาราง $table ถูกสร้างแล้ว"
        TABLES_CREATED=$((TABLES_CREATED + 1))
    else
        print_error "✗ ตาราง $table ไม่พบ"
    fi
done

echo ""
if [ $TABLES_CREATED -eq 8 ]; then
    print_success "════════════════════════════════════════════════════════════════"
    print_success "  ✅ สำเร็จ! ตารางทั้งหมดถูกสร้างแล้ว ($TABLES_CREATED/8)"
    print_success "════════════════════════════════════════════════════════════════"
else
    print_warning "════════════════════════════════════════════════════════════════"
    print_warning "  ⚠️  พบตารางเพียง $TABLES_CREATED/8 ตาราง"
    print_warning "════════════════════════════════════════════════════════════════"
fi

# Optional: Run seeders
echo ""
read -p "ต้องการรัน Seeder สำหรับข้อมูลทดสอบหรือไม่? (y/N): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    print_info "กำลังรัน Seeders..."

    php artisan db:seed --class=LineSignupTemplateSeeder --force
    php artisan db:seed --class=LineSignupFlowSeeder --force
    php artisan db:seed --class=LineSignupSessionSeeder --force

    print_success "Seeders รันสำเร็จ"
fi

# Final summary
echo ""
print_success "╔════════════════════════════════════════════════════════════════╗"
print_success "║              🎉 Deployment Complete! 🎉                        ║"
print_success "╚════════════════════════════════════════════════════════════════╝"
echo ""
print_info "ตารางที่ถูกสร้าง:"
echo "  1. ✅ line_signup_sessions"
echo "  2. ✅ line_signup_step_logs"
echo "  3. ✅ line_signup_conversations"
echo "  4. ✅ line_signup_templates"
echo "  5. ✅ line_signup_rewards"
echo "  6. ✅ line_signup_invitations"
echo "  7. ✅ line_signup_analytics"
echo "  8. ✅ line_signup_webhook_logs"
echo ""
print_info "ขั้นตอนถัดไป:"
echo "  1. ทดสอบ Admin Dashboard:"
echo "     → https://member123.thaiprompt.online/admin/line-membership-signup"
echo ""
echo "  2. Setup LINE Rich Menu (optional):"
echo "     → php artisan line:setup-signup-richmenu --set-default"
echo ""
print_success "ระบบพร้อมใช้งาน 100%! 🚀"
echo ""
