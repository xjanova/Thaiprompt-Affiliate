#!/bin/bash

# TP-Affiliate Installation Script
# Production Server Installation for Laravel 11

set -e

echo ""
echo "╔══════════════════════════════════════════════════╗"
echo "║   🚀 TP-Affiliate Installation Wizard            ║"
echo "║   Thai Prompt Affiliate Marketing Platform      ║"
echo "║   Production Server Setup                        ║"
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

print_header() {
    echo ""
    echo -e "${BLUE}════════════════════════════════════════${NC}"
    echo -e "${BLUE}  $1${NC}"
    echo -e "${BLUE}════════════════════════════════════════${NC}"
    echo ""
}

# Check if running as root
if [[ $EUID -eq 0 ]]; then
   print_warning "คำเตือน: ไม่แนะนำให้รันด้วย root"
   echo ""
fi

print_info "กำลังตรวจสอบระบบ..."
echo ""

# Check for required tools
command -v php >/dev/null 2>&1 && HAS_PHP=true || HAS_PHP=false
command -v composer >/dev/null 2>&1 && HAS_COMPOSER=true || HAS_COMPOSER=false

if [ "$HAS_PHP" = false ]; then
    print_error "ไม่พบ PHP"
    exit 1
fi

if [ "$HAS_COMPOSER" = false ]; then
    print_error "ไม่พบ Composer"
    exit 1
fi

# Display PHP version (info only, don't fail)
PHP_VERSION=$(php -r 'echo PHP_VERSION;' 2>/dev/null || echo "unknown")
print_success "PHP $PHP_VERSION"
print_success "Composer $(composer --version 2>/dev/null | cut -d' ' -f3 || echo '')"

echo ""

# Installation
print_header "🔧 Laravel Installation"

print_info "[1/7] Preparing Laravel directories..."
mkdir -p bootstrap/cache
mkdir -p storage/{app,framework,logs}
mkdir -p storage/framework/{cache,sessions,views}
mkdir -p database
print_success "Directories created"

print_info "[2/7] Installing Composer dependencies..."
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
print_success "Dependencies installed"

print_info "[3/7] Setting up environment file..."
if [ ! -f .env ]; then
    cp .env.example .env
    print_success "Environment file created"
else
    print_warning ".env already exists, skipping..."
fi

print_info "[4/7] Generating application key..."
php artisan key:generate --force --no-interaction
print_success "Application key generated"

# MySQL Configuration
print_header "📊 MySQL Database Configuration"

print_info "กรุณากรอกข้อมูล MySQL ที่เตรียมไว้:"
echo ""

read -p "  DB Host [127.0.0.1]: " DB_HOST
DB_HOST=${DB_HOST:-127.0.0.1}

read -p "  DB Port [3306]: " DB_PORT
DB_PORT=${DB_PORT:-3306}

read -p "  Database Name [thaiprompt_affiliate]: " DB_DATABASE
DB_DATABASE=${DB_DATABASE:-thaiprompt_affiliate}

read -p "  DB Username: " DB_USERNAME
if [ -z "$DB_USERNAME" ]; then
    print_error "Username จำเป็นต้องระบุ"
    exit 1
fi

read -sp "  DB Password: " DB_PASSWORD
echo ""
echo ""

print_info "[5/7] Configuring database connection..."

# Escape special characters for sed
DB_HOST_ESC=$(echo "$DB_HOST" | sed 's/[\/&]/\\&/g')
DB_PORT_ESC=$(echo "$DB_PORT" | sed 's/[\/&]/\\&/g')
DB_DATABASE_ESC=$(echo "$DB_DATABASE" | sed 's/[\/&]/\\&/g')
DB_USERNAME_ESC=$(echo "$DB_USERNAME" | sed 's/[\/&]/\\&/g')
DB_PASSWORD_ESC=$(echo "$DB_PASSWORD" | sed 's/[\/&]/\\&/g')

# Update .env file with MySQL settings
if [[ "$OSTYPE" == "darwin"* ]]; then
    # macOS
    sed -i '' "s/^DB_CONNECTION=.*/DB_CONNECTION=mysql/" .env
    sed -i '' "s/^DB_HOST=.*/DB_HOST=$DB_HOST_ESC/" .env
    sed -i '' "s/^DB_PORT=.*/DB_PORT=$DB_PORT_ESC/" .env
    sed -i '' "s/^DB_DATABASE=.*/DB_DATABASE=$DB_DATABASE_ESC/" .env
    sed -i '' "s/^DB_USERNAME=.*/DB_USERNAME=$DB_USERNAME_ESC/" .env
    sed -i '' "s/^DB_PASSWORD=.*/DB_PASSWORD=$DB_PASSWORD_ESC/" .env
else
    # Linux
    sed -i "s/^DB_CONNECTION=.*/DB_CONNECTION=mysql/" .env
    sed -i "s/^DB_HOST=.*/DB_HOST=$DB_HOST_ESC/" .env
    sed -i "s/^DB_PORT=.*/DB_PORT=$DB_PORT_ESC/" .env
    sed -i "s/^DB_DATABASE=.*/DB_DATABASE=$DB_DATABASE_ESC/" .env
    sed -i "s/^DB_USERNAME=.*/DB_USERNAME=$DB_USERNAME_ESC/" .env
    sed -i "s/^DB_PASSWORD=.*/DB_PASSWORD=$DB_PASSWORD_ESC/" .env
fi

print_success "Database configuration updated"

# Verify .env was updated correctly
print_info "ตรวจสอบการตั้งค่า..."
if grep -q "^DB_CONNECTION=mysql" .env && grep -q "^DB_DATABASE=$DB_DATABASE" .env; then
    print_success "Database settings verified"
else
    print_error "Failed to update .env file"
    print_info "กรุณาแก้ไข .env ด้วยตนเอง:"
    print_info "  DB_CONNECTION=mysql"
    print_info "  DB_HOST=$DB_HOST"
    print_info "  DB_PORT=$DB_PORT"
    print_info "  DB_DATABASE=$DB_DATABASE"
    print_info "  DB_USERNAME=$DB_USERNAME"
    print_info "  DB_PASSWORD=your_password"
    exit 1
fi

# Test MySQL connection (optional, don't fail if can't test)
print_info "ทดสอบการเชื่อมต่อ MySQL..."
if command -v mysql >/dev/null 2>&1; then
    if mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" -e "USE $DB_DATABASE;" 2>/dev/null; then
        print_success "MySQL connection successful"
    else
        print_warning "ไม่สามารถเชื่อมต่อ MySQL ได้"
        print_info "กรุณาตรวจสอบ:"
        print_info "  - MySQL Server รันอยู่"
        print_info "  - Username/Password ถูกต้อง"
        print_info "  - Database '$DB_DATABASE' ถูกสร้างแล้ว"
        echo ""
        read -p "ต้องการดำเนินการต่อหรือไม่? (y/n) [y]: " CONTINUE
        CONTINUE=${CONTINUE:-y}
        if [ "$CONTINUE" != "y" ] && [ "$CONTINUE" != "Y" ]; then
            print_error "ยกเลิกการติดตั้ง"
            exit 1
        fi
    fi
else
    print_warning "ไม่พบ mysql client (ข้ามการทดสอบ)"
fi

echo ""
print_info "[6/7] Running database migrations..."

# Clear any cached config to ensure new database settings are used
php artisan config:clear --no-interaction 2>/dev/null || true

# Run migrations
php artisan migrate --force --no-interaction
print_success "Database migrated successfully"

print_info "[7/7] Setting permissions..."
# Set proper permissions for web server
chmod -R 755 storage bootstrap/cache
find storage -type f -exec chmod 644 {} \;
find bootstrap/cache -type f -exec chmod 644 {} \;
print_success "Permissions configured"

# Optimize for production
print_header "⚡ Optimizing for Production"

print_info "Caching configuration..."
php artisan config:cache --no-interaction 2>/dev/null || print_warning "Config cache skipped"

print_info "Caching routes..."
php artisan route:cache --no-interaction 2>/dev/null || print_warning "Route cache skipped"

print_info "Caching views..."
php artisan view:cache --no-interaction 2>/dev/null || print_warning "View cache skipped"

print_success "Optimization complete"

print_header "✅ Installation Complete!"

print_success "ติดตั้ง TP-Affiliate สำเร็จ!"
echo ""
print_info "ขั้นตอนถัดไป:"
echo ""
echo "  1. ตั้งค่า Web Server (Nginx/Apache) ให้ชี้ไปที่ public/"
echo "  2. ตั้งค่า DocumentRoot: $(pwd)/public"
echo "  3. เปิด browser ไปที่ domain ของคุณ"
echo "  4. ระบบจะพาไปหน้า Setup Wizard เพื่อสร้าง Super Admin"
echo ""
print_info "สำหรับ Development (ถ้าต้องการทดสอบ):"
echo ""
echo "  ${BLUE}php artisan serve${NC}"
echo "  ${BLUE}http://localhost:8000${NC}"
echo ""
print_info "ข้อมูลเพิ่มเติม:"
echo ""
echo "  📖 Documentation: README.md"
echo "  🚀 Deployment Guide: DEPLOYMENT.md"
echo "  🔐 .env file: ตั้งค่า APP_ENV=production สำหรับ production"
echo ""
print_warning "คำแนะนำความปลอดภัย:"
echo ""
echo "  - เปลี่ยน APP_ENV=production ใน .env"
echo "  - ตั้ง APP_DEBUG=false ใน .env"
echo "  - ตรวจสอบ file permissions (storage และ bootstrap/cache)"
echo "  - ตั้งค่า SSL certificate"
echo "  - Backup database เป็นประจำ"
echo ""
print_success "Installation Complete! 🎉"
echo ""
