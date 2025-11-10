#!/bin/bash

################################################################################
# TP-Affiliate Installation Script
# ระบบติดตั้งอัตโนมัติสำหรับ Thaiprompt Affiliate Platform
#
# การใช้งาน:
#   chmod +x install.sh
#   ./install.sh
#
# หมายเหตุ:
#   - สคริปต์นี้จะเตรียมสภาพแวดล้อมให้พร้อมสำหรับการติดตั้ง
#   - หลังจากนี้ต้องติดตั้งต่อผ่าน Web Wizard ที่ /setup
################################################################################

# สี ANSI สำหรับ output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
MAGENTA='\033[0;35m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m' # No Color

# ตัวแปรสำหรับนับความสำเร็จ/ล้มเหลว
ERRORS=0
WARNINGS=0

################################################################################
# ฟังก์ชันสำหรับแสดงข้อความ
################################################################################

print_header() {
    echo ""
    echo -e "${BOLD}${CYAN}═══════════════════════════════════════════════════════════════════${NC}"
    echo -e "${BOLD}${CYAN}  $1${NC}"
    echo -e "${BOLD}${CYAN}═══════════════════════════════════════════════════════════════════${NC}"
    echo ""
}

print_success() {
    echo -e "${GREEN}✓${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
    ((ERRORS++))
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
    ((WARNINGS++))
}

print_info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

print_step() {
    echo ""
    echo -e "${BOLD}${MAGENTA}▸ $1${NC}"
    echo ""
}

################################################################################
# ฟังก์ชันตรวจสอบความต้องการของระบบ
################################################################################

check_php_version() {
    print_step "ตรวจสอบ PHP Version"

    if ! command -v php &> /dev/null; then
        print_error "ไม่พบ PHP ในระบบ กรุณาติดตั้ง PHP 8.1 หรือสูงกว่า"
        return 1
    fi

    PHP_VERSION=$(php -r "echo PHP_VERSION;")
    print_info "PHP Version: $PHP_VERSION"

    REQUIRED_VERSION="8.1.0"
    if php -r "exit(version_compare(PHP_VERSION, '$REQUIRED_VERSION', '>=') ? 0 : 1);"; then
        print_success "PHP Version ผ่านข้อกำหนด (ต้องการ >= $REQUIRED_VERSION)"
    else
        print_error "PHP Version ต่ำเกินไป (ต้องการ >= $REQUIRED_VERSION แต่พบ $PHP_VERSION)"
        return 1
    fi

    return 0
}

check_php_extensions() {
    print_step "ตรวจสอบ PHP Extensions ที่จำเป็น"

    REQUIRED_EXTENSIONS=(
        "bcmath"
        "ctype"
        "curl"
        "fileinfo"
        "json"
        "mbstring"
        "openssl"
        "pdo"
        "pdo_mysql"
        "tokenizer"
        "xml"
        "gd"
        "zip"
    )

    MISSING_EXTENSIONS=()

    for ext in "${REQUIRED_EXTENSIONS[@]}"; do
        if php -m | grep -qi "^$ext$"; then
            print_success "Extension: $ext"
        else
            print_error "Extension ขาดหายไป: $ext"
            MISSING_EXTENSIONS+=("$ext")
        fi
    done

    if [ ${#MISSING_EXTENSIONS[@]} -gt 0 ]; then
        echo ""
        print_error "กรุณาติดตั้ง PHP Extensions ที่ขาดหายไป:"
        for ext in "${MISSING_EXTENSIONS[@]}"; do
            echo "  - php-$ext"
        done
        echo ""
        echo -e "${YELLOW}สำหรับ Ubuntu/Debian:${NC}"
        echo "  sudo apt-get install ${MISSING_EXTENSIONS[@]/#/php-}"
        echo ""
        return 1
    fi

    return 0
}

check_composer() {
    print_step "ตรวจสอบ Composer"

    if ! command -v composer &> /dev/null; then
        print_error "ไม่พบ Composer กรุณาติดตั้ง Composer"
        echo ""
        echo -e "${YELLOW}วิธีติดตั้ง Composer:${NC}"
        echo "  curl -sS https://getcomposer.org/installer | php"
        echo "  sudo mv composer.phar /usr/local/bin/composer"
        echo ""
        return 1
    fi

    COMPOSER_VERSION=$(composer --version --no-ansi 2>&1 | grep -oP 'Composer version \K[0-9.]+')
    print_success "Composer Version: $COMPOSER_VERSION"

    return 0
}

check_permissions() {
    print_step "ตรวจสอบ File Permissions"

    DIRS_TO_CHECK=(
        "storage"
        "storage/app"
        "storage/framework"
        "storage/framework/cache"
        "storage/framework/sessions"
        "storage/framework/views"
        "storage/logs"
        "bootstrap/cache"
    )

    for dir in "${DIRS_TO_CHECK[@]}"; do
        if [ ! -d "$dir" ]; then
            print_warning "สร้าง directory: $dir"
            mkdir -p "$dir"
        fi

        if [ -w "$dir" ]; then
            print_success "Writable: $dir"
        else
            print_warning "ไม่สามารถเขียนได้: $dir (กำลังแก้ไข...)"
            chmod -R 775 "$dir" 2>/dev/null
            if [ -w "$dir" ]; then
                print_success "แก้ไข permissions สำเร็จ: $dir"
            else
                print_error "ไม่สามารถแก้ไข permissions: $dir"
            fi
        fi
    done

    return 0
}

################################################################################
# ฟังก์ชันสำหรับติดตั้ง
################################################################################

setup_env_file() {
    print_step "ตั้งค่าไฟล์ Environment (.env)"

    if [ -f ".env" ]; then
        print_info "ไฟล์ .env มีอยู่แล้ว"
        read -p "ต้องการสร้างใหม่หรือไม่? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            print_info "ข้ามการสร้างไฟล์ .env ใหม่"
            return 0
        fi

        # Backup existing .env
        BACKUP_FILE=".env.backup.$(date +%Y%m%d_%H%M%S)"
        cp .env "$BACKUP_FILE"
        print_info "สำรองไฟล์ .env เดิมไปที่: $BACKUP_FILE"
    fi

    if [ ! -f ".env.example" ]; then
        print_error "ไม่พบไฟล์ .env.example"
        return 1
    fi

    cp .env.example .env
    print_success "คัดลอก .env.example เป็น .env สำเร็จ"

    return 0
}

generate_app_key() {
    print_step "สร้าง Application Key"

    if grep -q "APP_KEY=base64:" .env 2>/dev/null; then
        print_info "APP_KEY มีค่าอยู่แล้ว"
        read -p "ต้องการสร้างใหม่หรือไม่? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            print_info "ข้ามการสร้าง APP_KEY ใหม่"
            return 0
        fi
    fi

    php artisan key:generate --ansi

    if [ $? -eq 0 ]; then
        print_success "สร้าง Application Key สำเร็จ"
    else
        print_error "เกิดข้อผิดพลาดในการสร้าง Application Key"
        return 1
    fi

    return 0
}

install_dependencies() {
    print_step "ติดตั้ง Composer Dependencies"

    if [ -d "vendor" ] && [ -f "vendor/autoload.php" ]; then
        print_info "Dependencies มีอยู่แล้ว"
        read -p "ต้องการติดตั้งใหม่หรือไม่? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            print_info "ข้ามการติดตั้ง dependencies ใหม่"
            return 0
        fi
    fi

    print_info "กำลังติดตั้ง Composer dependencies... (อาจใช้เวลา 2-5 นาที)"

    composer install --no-dev --optimize-autoloader --no-interaction

    if [ $? -eq 0 ]; then
        print_success "ติดตั้ง Composer dependencies สำเร็จ"
    else
        print_error "เกิดข้อผิดพลาดในการติดตั้ง Composer dependencies"
        return 1
    fi

    return 0
}

create_storage_link() {
    print_step "สร้าง Storage Symbolic Link"

    # Check if public directory exists
    if [ ! -d "public" ]; then
        print_warning "ไม่พบ directory: public"
        mkdir -p public
        print_success "สร้าง directory: public"
    fi

    # Create storage link
    php artisan storage:link --force &>/dev/null

    if [ $? -eq 0 ]; then
        print_success "สร้าง symbolic link: public/storage → storage/app/public"
    else
        print_warning "ไม่สามารถสร้าง storage link ได้ (อาจต้องทำด้วยตนเอง)"
    fi

    return 0
}

set_permissions() {
    print_step "ตั้งค่า File Permissions"

    # Set ownership (if running as root)
    if [ "$EUID" -eq 0 ]; then
        print_info "กำลังตั้งค่า ownership..."
        WEB_USER=${WEB_USER:-www-data}

        if id "$WEB_USER" &>/dev/null; then
            chown -R $WEB_USER:$WEB_USER storage bootstrap/cache
            print_success "ตั้งค่า ownership เป็น $WEB_USER"
        else
            print_warning "ไม่พบ user: $WEB_USER"
        fi
    fi

    # Set permissions
    chmod -R 775 storage bootstrap/cache
    find storage -type f -exec chmod 664 {} \; 2>/dev/null
    find bootstrap/cache -type f -exec chmod 664 {} \; 2>/dev/null
    print_success "ตั้งค่า permissions สำเร็จ"

    # Try to use ACL if available (better for shared access)
    WEB_USER=""
    if id -u www-data &>/dev/null; then
        WEB_USER="www-data"
    elif id -u nginx &>/dev/null; then
        WEB_USER="nginx"
    elif id -u apache &>/dev/null; then
        WEB_USER="apache"
    fi

    if [ -n "$WEB_USER" ] && command -v setfacl &>/dev/null; then
        print_info "ตั้งค่า ACL permissions สำหรับ web server..."
        setfacl -R -m u:"$WEB_USER":rwX storage bootstrap/cache 2>/dev/null
        setfacl -R -d -m u:"$WEB_USER":rwX storage bootstrap/cache 2>/dev/null
        print_success "ตั้งค่า ACL สำหรับ $WEB_USER"
    fi

    return 0
}

clear_cache() {
    print_step "ล้าง Cache"

    php artisan config:clear &>/dev/null
    php artisan cache:clear &>/dev/null
    php artisan view:clear &>/dev/null
    php artisan route:clear &>/dev/null

    print_success "ล้าง cache ทั้งหมดสำเร็จ"

    return 0
}

################################################################################
# ฟังก์ชันแสดงข้อมูลสรุป
################################################################################

show_summary() {
    echo ""
    echo ""
    print_header "สรุปผลการเตรียมระบบ"

    if [ $ERRORS -eq 0 ]; then
        echo -e "${GREEN}${BOLD}✓ การเตรียมระบบเสร็จสมบูรณ์!${NC}"
    else
        echo -e "${RED}${BOLD}✗ พบข้อผิดพลาด: $ERRORS รายการ${NC}"
    fi

    if [ $WARNINGS -gt 0 ]; then
        echo -e "${YELLOW}⚠ คำเตือน: $WARNINGS รายการ${NC}"
    fi

    echo ""
    echo -e "${BOLD}ขั้นตอนถัดไป:${NC}"
    echo ""

    if [ $ERRORS -eq 0 ]; then
        echo -e "${GREEN}${BOLD}1. เปิด Web Server${NC}"
        echo "   ถ้าใช้ Development:"
        echo -e "   ${CYAN}php artisan serve${NC}"
        echo ""
        echo "   ถ้าใช้ Production ให้ตั้งค่า Web Server (Nginx/Apache) ให้ชี้ไปที่:"
        echo -e "   ${CYAN}DocumentRoot: $(pwd)/public${NC}"
        echo ""
        echo -e "${GREEN}${BOLD}2. เปิด Browser และไปที่:${NC}"
        echo -e "   ${CYAN}${BOLD}http://your-domain.com/setup${NC}"
        echo "   หรือ"
        echo -e "   ${CYAN}${BOLD}http://localhost:8000/setup${NC} ${YELLOW}(ถ้าใช้ php artisan serve)${NC}"
        echo ""
        echo -e "${GREEN}${BOLD}3. ทำตามขั้นตอนใน Setup Wizard:${NC}"
        echo "   ${MAGENTA}⚡${NC} ตรวจสอบความพร้อมของระบบ"
        echo "   ${MAGENTA}🗄${NC}  ตั้งค่า Database Connection"
        echo "   ${MAGENTA}📦${NC} ติดตั้ง Dependencies (ถ้ายังไม่ได้ติดตั้ง)"
        echo "   ${MAGENTA}🔨${NC} Run Database Migrations"
        echo "   ${MAGENTA}👤${NC} สร้างบัญชี Super Admin"
        echo ""
        echo -e "${YELLOW}${BOLD}📝 ข้อมูลที่ต้องเตรียม:${NC}"
        echo "   • MySQL Database Host (เช่น 127.0.0.1)"
        echo "   • MySQL Port (เช่น 3306)"
        echo "   • Database Name (เช่น thaiprompt_affiliate)"
        echo "   • Database Username"
        echo "   • Database Password"
        echo ""
        echo -e "${CYAN}${BOLD}⏱ เวลาโดยประมาณ:${NC} 5-10 นาที"
        echo ""
        echo -e "${GREEN}${BOLD}4. เข้าสู่ระบบและเริ่มใช้งาน!${NC}"
        echo ""
        echo -e "${YELLOW}${BOLD}⚠ หมายเหตุสำหรับ Production:${NC}"
        echo "   • ตั้งค่า APP_ENV=production ใน .env"
        echo "   • ตั้งค่า APP_DEBUG=false ใน .env"
        echo "   • ติดตั้ง SSL Certificate"
        echo "   • ตั้งค่า Firewall และ Security"
        echo "   • ตรวจสอบ File Permissions"
        echo "   • ตั้งค่า Backup อัตโนมัติ"
    else
        echo -e "${RED}กรุณาแก้ไขข้อผิดพลาดที่พบก่อนดำเนินการต่อ${NC}"
        echo ""
        echo "หากต้องการความช่วยเหลือ กรุณาดูเอกสารที่:"
        echo "  • README.md"
        echo "  • INSTALLATION.md"
        echo "  • DEPLOYMENT.md"
    fi

    echo ""
    echo -e "${CYAN}═══════════════════════════════════════════════════════════════════${NC}"
    echo ""
}

################################################################################
# Main Installation Flow
################################################################################

main() {
    # Show welcome banner
    clear
    echo ""
    echo -e "${BOLD}${CYAN}"
    echo "╔═══════════════════════════════════════════════════════════════════╗"
    echo "║                                                                   ║"
    echo "║           TP-AFFILIATE INSTALLATION SCRIPT                        ║"
    echo "║           Thaiprompt Affiliate Marketing Platform                ║"
    echo "║                                                                   ║"
    echo "║           Version: 1.0                                            ║"
    echo "║           Stage: Environment Preparation                          ║"
    echo "║                                                                   ║"
    echo "╚═══════════════════════════════════════════════════════════════════╝"
    echo -e "${NC}"
    echo ""
    echo -e "${YELLOW}สคริปต์นี้จะเตรียมสภาพแวดล้อมให้พร้อมสำหรับการติดตั้ง${NC}"
    echo -e "${YELLOW}หลังจากนี้คุณจะต้องติดตั้งต่อผ่าน Web Wizard ที่ /setup${NC}"
    echo ""
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""

    read -p "กด Enter เพื่อเริ่มต้น... " -r

    # Check system requirements
    print_header "ตรวจสอบความต้องการของระบบ"

    check_php_version || exit 1
    check_php_extensions || exit 1
    check_composer || exit 1
    check_permissions

    # Setup environment
    print_header "เตรียมสภาพแวดล้อม"

    setup_env_file || exit 1
    generate_app_key || exit 1

    # Install dependencies
    print_header "ติดตั้ง Dependencies"

    install_dependencies || exit 1

    # Set permissions and clear cache
    print_header "ตั้งค่าระบบ"

    create_storage_link
    set_permissions
    clear_cache

    # Show summary
    show_summary

    # Exit with appropriate code
    if [ $ERRORS -eq 0 ]; then
        exit 0
    else
        exit 1
    fi
}

# Run main function
main
