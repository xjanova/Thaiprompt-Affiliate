#!/bin/bash

# TP-Affiliate Installation Script
# One-Click Installation for TP-Affiliate System

set -e

echo ""
echo "╔══════════════════════════════════════════════════╗"
echo "║   🚀 TP-Affiliate Installation Wizard            ║"
echo "║   Thai Prompt Affiliate Marketing Platform      ║"
echo "╚══════════════════════════════════════════════════╝"
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Functions
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

# Check if running as root
if [[ $EUID -eq 0 ]]; then
   print_warning "This script should not be run as root"
fi

echo "กำลังตรวจสอบระบบ..."
echo ""

# Check for required tools
command -v php >/dev/null 2>&1 && HAS_PHP=true || HAS_PHP=false
command -v composer >/dev/null 2>&1 && HAS_COMPOSER=true || HAS_COMPOSER=false

if [ "$HAS_PHP" = false ] || [ "$HAS_COMPOSER" = false ]; then
    print_error "ไม่พบ PHP หรือ Composer"
    print_info "กรุณาติดตั้ง:"
    print_info "  - PHP 8.1 หรือสูงกว่า"
    print_info "  - Composer"
    exit 1
fi

# Check PHP version
PHP_VERSION=$(php -r 'echo PHP_VERSION;')
PHP_MAJOR=$(echo $PHP_VERSION | cut -d. -f1)
PHP_MINOR=$(echo $PHP_VERSION | cut -d. -f2)

if [ "$PHP_MAJOR" -lt 8 ] || ([ "$PHP_MAJOR" -eq 8 ] && [ "$PHP_MINOR" -lt 1 ]); then
    print_error "PHP version ต้อง 8.1 หรือสูงกว่า (ปัจจุบัน: $PHP_VERSION)"
    exit 1
fi

print_success "PHP $PHP_VERSION detected"
print_success "Composer detected"

echo ""

# Installation
echo "  🔧 Installing TP-Affiliate"
echo "════════════════════════════════════════"
echo ""

print_info "[1/6] Installing Composer dependencies..."
composer install --no-interaction --prefer-dist
print_success "Dependencies installed"

print_info "[2/6] Setting up environment..."
if [ ! -f .env ]; then
    cp .env.example .env
    print_success "Environment file created"
else
    print_warning ".env file already exists, skipping..."
fi

print_info "[3/6] Generating application key..."
php artisan key:generate --force
print_success "Key generated"

print_info "[4/6] Creating database..."
if [ ! -f database/database.sqlite ]; then
    mkdir -p database
    touch database/database.sqlite
    print_success "Database created (SQLite)"
else
    print_warning "Database already exists, skipping..."
fi

print_info "[5/6] Running migrations..."
php artisan migrate --force
print_success "Database migrated"

print_info "[6/6] Setting permissions..."
chmod -R 775 storage bootstrap/cache 2>/dev/null || true
print_success "Permissions set"

echo ""
echo "════════════════════════════════════════"
print_success "Installation Complete! 🎉"
echo "════════════════════════════════════════"
echo ""
print_info "รันเซิร์ฟเวอร์ด้วย:"
echo ""
echo "  ${BLUE}php artisan serve${NC}"
echo ""
print_info "จากนั้นเปิด browser ไปที่:"
echo ""
echo "  ${BLUE}http://localhost:8000${NC}"
echo ""
print_info "ระบบจะพาคุณไปหน้า Setup Wizard เพื่อสร้าง Super Admin"
echo ""

echo "📚 อ่านเอกสารเพิ่มเติมได้ที่: README.md"
echo ""
print_success "Happy coding! 🚀"
echo ""
