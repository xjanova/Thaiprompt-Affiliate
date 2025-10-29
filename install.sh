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
command -v docker >/dev/null 2>&1 && HAS_DOCKER=true || HAS_DOCKER=false
command -v php >/dev/null 2>&1 && HAS_PHP=true || HAS_PHP=false
command -v composer >/dev/null 2>&1 && HAS_COMPOSER=true || HAS_COMPOSER=false

if [ "$HAS_DOCKER" = true ]; then
    print_success "Docker detected"
    INSTALL_METHOD="docker"
elif [ "$HAS_PHP" = true ] && [ "$HAS_COMPOSER" = true ]; then
    print_success "PHP & Composer detected"
    INSTALL_METHOD="manual"
else
    print_error "ไม่พบ Docker, PHP หรือ Composer"
    print_info "กรุณาติดตั้งอย่างน้อยหนึ่งใน:"
    print_info "  1. Docker (แนะนำ)"
    print_info "  2. PHP 8.1+ และ Composer"
    exit 1
fi

echo ""
print_info "วิธีการติดตั้ง: $INSTALL_METHOD"
echo ""

# Installation
if [ "$INSTALL_METHOD" = "docker" ]; then
    echo "════════════════════════════════════════"
    echo "  📦 Docker Installation"
    echo "════════════════════════════════════════"
    echo ""

    print_info "[1/5] Building Docker containers..."
    docker-compose build --no-cache
    print_success "Containers built successfully"

    print_info "[2/5] Starting containers..."
    docker-compose up -d
    print_success "Containers started"

    print_info "[3/5] Installing dependencies..."
    docker-compose exec -T app composer install --no-interaction
    print_success "Dependencies installed"

    print_info "[4/5] Setting up environment..."
    docker-compose exec -T app cp .env.example .env
    docker-compose exec -T app php artisan key:generate
    print_success "Environment configured"

    print_info "[5/5] Running migrations..."
    docker-compose exec -T app php artisan migrate --force
    print_success "Database migrated"

    echo ""
    echo "════════════════════════════════════════"
    print_success "Installation Complete! 🎉"
    echo "════════════════════════════════════════"
    echo ""
    print_info "เปิด browser ไปที่: ${BLUE}http://localhost${NC}"
    print_info "กด Ctrl+C เพื่อหยุดเซิร์ฟเวอร์"
    echo ""

else
    echo "════════════════════════════════════════"
    echo "  🔧 Manual Installation"
    echo "════════════════════════════════════════"
    echo ""

    print_info "[1/6] Installing Composer dependencies..."
    composer install --no-interaction --prefer-dist
    print_success "Dependencies installed"

    print_info "[2/6] Setting up environment..."
    if [ ! -f .env ]; then
        cp .env.example .env
    fi
    print_success "Environment file created"

    print_info "[3/6] Generating application key..."
    php artisan key:generate
    print_success "Key generated"

    print_info "[4/6] Creating database..."
    mkdir -p database
    touch database/database.sqlite
    print_success "Database created"

    print_info "[5/6] Running migrations..."
    php artisan migrate --force
    print_success "Database migrated"

    print_info "[6/6] Installing NPM packages..."
    if command -v npm >/dev/null 2>&1; then
        npm install
        print_success "NPM packages installed"
    else
        print_warning "NPM not found, skipping frontend build"
    fi

    echo ""
    echo "════════════════════════════════════════"
    print_success "Installation Complete! 🎉"
    echo "════════════════════════════════════════"
    echo ""
    print_info "รันเซิร์ฟเวอร์ด้วย: ${BLUE}php artisan serve${NC}"
    print_info "จากนั้นเปิด browser ไปที่: ${BLUE}http://localhost:8000${NC}"
    echo ""
fi

echo "📚 อ่านเอกสารเพิ่มเติมได้ที่: README.md"
echo ""
print_success "Happy coding! 🚀"
echo ""
