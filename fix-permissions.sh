#!/bin/bash

# TP-Affiliate Permission Fix Script
# Use this script to fix permission issues after installation

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

print_success() {
    echo -e "${GREEN}✓${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

print_info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

echo ""
echo "╔══════════════════════════════════════════════════╗"
echo "║   🔧 TP-Affiliate Permission Fix                 ║"
echo "║   Fixing storage and cache permissions          ║"
echo "╚══════════════════════════════════════════════════╝"
echo ""

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    print_error "ไม่พบไฟล์ artisan - กรุณารันคำสั่งนี้ในโฟลเดอร์ Laravel"
    exit 1
fi

print_info "กำลังตรวจสอบระบบ..."
echo ""

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

if [ -n "$WEB_USER" ]; then
    print_success "ตรวจพบ web server user: $WEB_USER"
else
    print_warning "ไม่พบ web server user (www-data, nginx, apache, admin)"
    read -p "กรุณาระบุ web server user: " WEB_USER
    if [ -z "$WEB_USER" ]; then
        print_error "จำเป็นต้องระบุ web server user"
        exit 1
    fi
fi

CURRENT_USER=$(whoami)
print_info "Current user: $CURRENT_USER"
echo ""

# Create directories if not exist
print_info "[1/5] สร้างโฟลเดอร์ที่จำเป็น..."
mkdir -p storage/{app,framework,logs}
mkdir -p storage/framework/{cache,sessions,views}
mkdir -p storage/app/{public,uploads}
mkdir -p bootstrap/cache
print_success "โฟลเดอร์ถูกสร้างแล้ว"

# Set directory permissions
print_info "[2/5] ตั้งค่า permissions สำหรับ directories..."
chmod -R 775 storage bootstrap/cache
print_success "Directory permissions: 775"

# Set file permissions
print_info "[3/5] ตั้งค่า permissions สำหรับ files..."
find storage -type f -exec chmod 664 {} \;
find bootstrap/cache -type f -exec chmod 664 {} \;
print_success "File permissions: 664"

# Set ownership
print_info "[4/5] ตั้งค่า ownership..."
if chown -R "$CURRENT_USER:$WEB_USER" storage bootstrap/cache 2>/dev/null; then
    print_success "Ownership set to $CURRENT_USER:$WEB_USER"
else
    print_warning "ไม่สามารถตั้งค่า ownership ได้ (ต้องใช้ sudo)"
    echo ""
    print_info "กรุณารันคำสั่งนี้ด้วย sudo:"
    echo ""
    echo "  ${YELLOW}sudo chown -R $CURRENT_USER:$WEB_USER storage bootstrap/cache${NC}"
    echo "  ${YELLOW}sudo chmod -R 775 storage bootstrap/cache${NC}"
    echo ""
    read -p "ต้องการรันด้วย sudo เดี๋ยวนี้เลยหรือไม่? (y/n) [y]: " RUN_SUDO
    RUN_SUDO=${RUN_SUDO:-y}

    if [ "$RUN_SUDO" = "y" ] || [ "$RUN_SUDO" = "Y" ]; then
        sudo chown -R "$CURRENT_USER:$WEB_USER" storage bootstrap/cache
        sudo chmod -R 775 storage bootstrap/cache
        print_success "Ownership set with sudo"
    else
        print_warning "ข้ามการตั้งค่า ownership"
    fi
fi

# Set ACL if available
print_info "[5/5] ตั้งค่า ACL permissions (ถ้ามี)..."
if command -v setfacl >/dev/null 2>&1; then
    if setfacl -R -m u:"$WEB_USER":rwX storage bootstrap/cache 2>/dev/null; then
        setfacl -R -d -m u:"$WEB_USER":rwX storage bootstrap/cache 2>/dev/null || true
        print_success "ACL permissions set for $WEB_USER"
    else
        print_warning "ต้องใช้ sudo สำหรับ ACL"
        read -p "ต้องการรันด้วย sudo หรือไม่? (y/n) [y]: " RUN_SUDO_ACL
        RUN_SUDO_ACL=${RUN_SUDO_ACL:-y}

        if [ "$RUN_SUDO_ACL" = "y" ] || [ "$RUN_SUDO_ACL" = "Y" ]; then
            sudo setfacl -R -m u:"$WEB_USER":rwX storage bootstrap/cache
            sudo setfacl -R -d -m u:"$WEB_USER":rwX storage bootstrap/cache
            print_success "ACL permissions set with sudo"
        fi
    fi
else
    print_warning "setfacl ไม่พร้อมใช้งาน (ข้ามขั้นตอนนี้)"
fi

echo ""
echo "╔══════════════════════════════════════════════════╗"
echo "║   ✅ Permission Fix Complete!                    ║"
echo "╚══════════════════════════════════════════════════╝"
echo ""

print_info "ตรวจสอบสิทธิ์ปัจจุบัน:"
echo ""
ls -la storage/ | head -5
echo ""

print_info "ขั้นตอนถัดไป:"
echo ""
echo "  1. ล้าง cache:"
echo "     ${BLUE}php artisan cache:clear${NC}"
echo "     ${BLUE}php artisan view:clear${NC}"
echo "     ${BLUE}php artisan config:clear${NC}"
echo ""
echo "  2. ทดสอบแอปพลิเคชัน:"
echo "     เปิด browser และทดสอบว่าแก้ปัญหาแล้ว"
echo ""
echo "  3. ถ้ายังมีปัญหา ลองรีสตาร์ท web server:"
echo "     ${BLUE}sudo systemctl restart php8.2-fpm${NC}"
echo "     ${BLUE}sudo systemctl restart nginx${NC}"
echo ""

print_success "เสร็จสิ้น! 🎉"
echo ""
