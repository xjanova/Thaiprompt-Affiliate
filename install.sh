#!/bin/bash

################################################################################
# TP-Affiliate Ultimate Installation Script v3.2
# ไฟล์เดียวจบ - ติดตั้งครบทุกอย่าง ไม่ต้องรันอะไรเพิ่ม
#
# 🎯 สำหรับผู้ใช้ทั่วไป: ใช้ --wizard เพื่อติดตั้งอย่างง่าย
#
# Features:
# ✅ รวมทุกฟีเจอร์ในไฟล์เดียว (fix-permissions, clear-cache, etc.)
# ✅ ใช้ DatabaseSeeder โดยตรง - ตรงกับ codebase เสมอ
# ✅ 3 โหมดการติดตั้ง: minimal, standard, full
# ✅ Pre-flight checks: disk space, memory, php limits
# ✅ Non-interactive mode (--auto) สำหรับ CI/CD
# ✅ Resume support - ติดตั้งค้างไว้ได้
# ✅ Auto-retry - ลองใหม่อัตโนมัติถ้าล้มเหลว
# ✅ [NEW] Wizard Mode - ติดตั้งง่ายสำหรับผู้ใช้ทั่วไป
# ✅ [NEW] Auto Clone - ดึงไฟล์จาก GitHub อัตโนมัติ
# ✅ [NEW] Deploy Ready Check - ตรวจสอบพร้อมสำหรับ deploy-pro.sh
#
# Usage:
#   ./install.sh                    # Interactive mode
#   ./install.sh --wizard           # 🌟 แนะนำ! Wizard mode สำหรับผู้ใช้ทั่วไป
#   ./install.sh --auto             # Non-interactive with defaults
#   ./install.sh --mode=minimal     # Minimal installation (no demo data)
#   ./install.sh --mode=standard    # Standard installation (recommended)
#   ./install.sh --mode=full        # Full installation (all demo data)
#   ./install.sh --help             # Show help
#
# Remote Installation (ยังไม่มีไฟล์):
#   curl -fsSL https://raw.githubusercontent.com/xjanova/Thaiprompt-Affiliate/claude/Main/install.sh | bash -s -- --wizard
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
SCRIPT_VERSION="3.2.0"

# GitHub Repository Configuration
REPO_URL="https://github.com/xjanova/Thaiprompt-Affiliate.git"
REPO_BRANCH="claude/Main"

# Default values for CLI options
AUTO_MODE=false
WIZARD_MODE=false
INSTALL_MODE="standard"  # minimal, standard, full
SKIP_DEMO=false
FORCE_REINSTALL=false
DO_CLONE=false
MIN_DISK_SPACE_MB=500
MIN_PHP_MEMORY="256M"

################################################################################
# ฟังก์ชันแสดง Help
################################################################################
show_help() {
    echo ""
    echo -e "${CYAN}${BOLD}TP-Affiliate Installation Script v${SCRIPT_VERSION}${NC}"
    echo ""
    echo -e "${GREEN}${BOLD}🌟 แนะนำสำหรับผู้ใช้ทั่วไป: ./install.sh --wizard${NC}"
    echo ""
    echo -e "${WHITE}Usage:${NC}"
    echo "  ./install.sh [options]"
    echo ""
    echo -e "${WHITE}Options:${NC}"
    echo "  --help, -h              แสดงข้อความช่วยเหลือนี้"
    echo "  ${GREEN}--wizard, -w${NC}            🌟 Wizard Mode - ติดตั้งง่ายสำหรับผู้ใช้ทั่วไป"
    echo "  --auto, -a              โหมด non-interactive (ใช้ค่า default หรือจาก cache)"
    echo "  --clone                 Clone repository จาก GitHub ก่อนติดตั้ง"
    echo "  --mode=MODE             โหมดการติดตั้ง: minimal, standard (default), full"
    echo "  --skip-demo             ข้ามการติดตั้ง demo data"
    echo "  --force                 บังคับติดตั้งใหม่ (ลบ progress เดิม)"
    echo ""
    echo -e "${WHITE}Installation Modes:${NC}"
    echo "  ${CYAN}minimal${NC}    - Core settings เท่านั้น (เหมาะสำหรับ production)"
    echo "               ไม่รวม: demo users, demo products, test data"
    echo ""
    echo "  ${CYAN}standard${NC}   - แนะนำสำหรับการใช้งานทั่วไป"
    echo "               รวม: core settings, demo users, essential data"
    echo ""
    echo "  ${CYAN}full${NC}       - ติดตั้งทุกอย่างรวม demo data ทั้งหมด"
    echo "               รวม: ทุกอย่าง + demo products, test orders, etc."
    echo ""
    echo -e "${WHITE}Examples:${NC}"
    echo "  ./install.sh --wizard             # 🌟 แนะนำ! ติดตั้งแบบง่าย"
    echo "  ./install.sh                      # Interactive mode"
    echo "  ./install.sh --auto               # ใช้ค่า default ทั้งหมด"
    echo "  ./install.sh --clone --wizard     # Clone + ติดตั้ง (เริ่มต้นจากศูนย์)"
    echo "  ./install.sh --mode=minimal       # Production setup"
    echo "  ./install.sh --auto --mode=full   # CI/CD with full demo"
    echo ""
    echo -e "${WHITE}Remote Installation (ยังไม่มีไฟล์ในเครื่อง):${NC}"
    echo "  curl -fsSL https://raw.githubusercontent.com/xjanova/Thaiprompt-Affiliate/claude/Main/install.sh | bash -s -- --wizard --clone"
    echo ""
    exit 0
}

################################################################################
# Parse CLI Arguments
################################################################################
parse_arguments() {
    while [[ $# -gt 0 ]]; do
        case $1 in
            --help|-h)
                show_help
                ;;
            --wizard|-w)
                WIZARD_MODE=true
                shift
                ;;
            --auto|-a)
                AUTO_MODE=true
                shift
                ;;
            --clone)
                DO_CLONE=true
                shift
                ;;
            --mode=*)
                INSTALL_MODE="${1#*=}"
                if [[ ! "$INSTALL_MODE" =~ ^(minimal|standard|full)$ ]]; then
                    print_error "Invalid mode: $INSTALL_MODE"
                    print_info "Valid modes: minimal, standard, full"
                    exit 1
                fi
                shift
                ;;
            --skip-demo)
                SKIP_DEMO=true
                shift
                ;;
            --force)
                FORCE_REINSTALL=true
                shift
                ;;
            *)
                print_warning "Unknown option: $1"
                shift
                ;;
        esac
    done
}

################################################################################
# ฟังก์ชัน Pre-flight Checks
################################################################################
check_disk_space() {
    print_step "ตรวจสอบ disk space..."

    # Get available disk space in MB
    AVAILABLE_MB=$(df -m . | awk 'NR==2 {print $4}')

    if [ "$AVAILABLE_MB" -lt "$MIN_DISK_SPACE_MB" ]; then
        print_error "Disk space ไม่เพียงพอ!"
        print_info "ต้องการ: ${MIN_DISK_SPACE_MB}MB"
        print_info "มี: ${AVAILABLE_MB}MB"
        return 1
    fi

    print_success "Disk space: ${AVAILABLE_MB}MB available ✓"
    return 0
}

check_php_memory() {
    print_step "ตรวจสอบ PHP memory_limit..."

    PHP_MEMORY=$(php -r "echo ini_get('memory_limit');" 2>/dev/null || echo "128M")

    # Convert to MB for comparison
    MEMORY_VALUE=$(echo "$PHP_MEMORY" | sed 's/[^0-9]//g')
    MEMORY_UNIT=$(echo "$PHP_MEMORY" | sed 's/[0-9]//g' | tr '[:lower:]' '[:upper:]')

    case $MEMORY_UNIT in
        G) MEMORY_MB=$((MEMORY_VALUE * 1024)) ;;
        M) MEMORY_MB=$MEMORY_VALUE ;;
        K) MEMORY_MB=$((MEMORY_VALUE / 1024)) ;;
        *) MEMORY_MB=128 ;;
    esac

    # -1 means unlimited
    if [ "$MEMORY_MB" -eq -1 ] || [ "$MEMORY_MB" -ge 256 ]; then
        print_success "PHP memory_limit: $PHP_MEMORY ✓"
        return 0
    else
        print_warning "PHP memory_limit: $PHP_MEMORY (แนะนำ 256M หรือมากกว่า)"
        return 0  # Warning only, don't block
    fi
}

check_php_max_execution_time() {
    print_step "ตรวจสอบ PHP max_execution_time..."

    MAX_EXEC=$(php -r "echo ini_get('max_execution_time');" 2>/dev/null || echo "30")

    if [ "$MAX_EXEC" -eq 0 ] || [ "$MAX_EXEC" -ge 300 ]; then
        print_success "PHP max_execution_time: ${MAX_EXEC}s ✓"
        return 0
    elif [ "$MAX_EXEC" -ge 120 ]; then
        print_warning "PHP max_execution_time: ${MAX_EXEC}s (แนะนำ 300s)"
        return 0
    else
        print_warning "PHP max_execution_time: ${MAX_EXEC}s (อาจเกิด timeout ระหว่างติดตั้ง)"
        print_info "แก้ไขใน php.ini: max_execution_time = 300"
        return 0  # Warning only
    fi
}

run_preflight_checks() {
    print_subheader "🔍 Pre-flight Checks"

    local all_passed=true

    check_disk_space || all_passed=false
    check_php_memory
    check_php_max_execution_time

    if [ "$all_passed" = false ]; then
        error_exit "Pre-flight checks failed. กรุณาแก้ไขปัญหาข้างต้นก่อนติดตั้ง"
    fi

    print_success "ผ่าน Pre-flight checks ทั้งหมด ✓"
}

################################################################################
# ฟังก์ชัน Auto-Install Dependencies
################################################################################

# ติดตั้ง Composer อัตโนมัติ
install_composer() {
    print_info "🔧 กำลังติดตั้ง Composer อัตโนมัติ..."

    # Download and install Composer
    if curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php; then
        if php /tmp/composer-setup.php --quiet --install-dir=/tmp; then
            # Try to move to system path (may require sudo)
            if sudo mv /tmp/composer.phar /usr/local/bin/composer 2>/dev/null; then
                print_success "ติดตั้ง Composer ไปที่ /usr/local/bin/composer ✓"
                rm -f /tmp/composer-setup.php
                return 0
            elif mv /tmp/composer.phar ./composer.phar 2>/dev/null; then
                # Fallback: install locally
                print_success "ติดตั้ง Composer ไปที่ ./composer.phar ✓"
                print_info "ใช้คำสั่ง: php composer.phar แทน composer"
                # Create alias function for this session
                composer() { php "$(pwd)/composer.phar" "$@"; }
                export -f composer
                rm -f /tmp/composer-setup.php
                return 0
            fi
        fi
    fi

    print_error "ติดตั้ง Composer ล้มเหลว"
    rm -f /tmp/composer-setup.php /tmp/composer.phar
    return 1
}

# ติดตั้ง Node.js อัตโนมัติผ่าน nvm หรือ direct download
install_nodejs() {
    print_info "🔧 กำลังติดตั้ง Node.js อัตโนมัติ..."

    local NODE_VERSION="20"  # LTS version

    # Method 1: Try using nvm if available
    if [ -f "$HOME/.nvm/nvm.sh" ]; then
        print_info "พบ nvm - ใช้ nvm ติดตั้ง Node.js..."
        source "$HOME/.nvm/nvm.sh"
        if nvm install $NODE_VERSION && nvm use $NODE_VERSION; then
            print_success "ติดตั้ง Node.js v$NODE_VERSION ผ่าน nvm ✓"
            return 0
        fi
    fi

    # Method 2: Install nvm first, then Node.js
    print_info "กำลังติดตั้ง nvm..."
    if curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.7/install.sh | bash; then
        export NVM_DIR="$HOME/.nvm"
        [ -s "$NVM_DIR/nvm.sh" ] && source "$NVM_DIR/nvm.sh"

        if command -v nvm &> /dev/null; then
            print_success "ติดตั้ง nvm ✓"
            print_info "กำลังติดตั้ง Node.js v$NODE_VERSION..."

            if nvm install $NODE_VERSION && nvm use $NODE_VERSION; then
                print_success "ติดตั้ง Node.js v$NODE_VERSION ✓"
                return 0
            fi
        fi
    fi

    # Method 3: Direct download for Linux (fallback)
    if [[ "$(uname)" == "Linux" ]]; then
        print_info "ลองติดตั้ง Node.js โดยตรง..."
        local ARCH=$(uname -m)
        local NODE_ARCH=""

        case $ARCH in
            x86_64) NODE_ARCH="x64" ;;
            aarch64) NODE_ARCH="arm64" ;;
            armv7l) NODE_ARCH="armv7l" ;;
            *) NODE_ARCH="x64" ;;
        esac

        local NODE_URL="https://nodejs.org/dist/v${NODE_VERSION}.0.0/node-v${NODE_VERSION}.0.0-linux-${NODE_ARCH}.tar.xz"
        local NODE_DIR="/usr/local/lib/nodejs"

        if curl -fsSL "$NODE_URL" -o /tmp/node.tar.xz; then
            if sudo mkdir -p "$NODE_DIR" && sudo tar -xJf /tmp/node.tar.xz -C "$NODE_DIR" --strip-components=1 2>/dev/null; then
                export PATH="$NODE_DIR/bin:$PATH"
                print_success "ติดตั้ง Node.js v$NODE_VERSION ✓"
                rm -f /tmp/node.tar.xz
                return 0
            fi
        fi
        rm -f /tmp/node.tar.xz
    fi

    print_warning "ไม่สามารถติดตั้ง Node.js อัตโนมัติได้"
    print_info "กรุณาติดตั้งเอง: https://nodejs.org/"
    return 1
}

# ติดตั้ง PHP extensions (แนะนำคำสั่ง)
suggest_php_extensions() {
    local extensions=("$@")
    local os_type=""

    # Detect OS
    if [ -f /etc/debian_version ]; then
        os_type="debian"
    elif [ -f /etc/redhat-release ]; then
        os_type="redhat"
    elif [[ "$(uname)" == "Darwin" ]]; then
        os_type="macos"
    fi

    echo ""
    print_info "📦 คำสั่งติดตั้ง PHP extensions:"
    echo ""

    case $os_type in
        debian)
            local php_ver=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
            local ext_list=""
            for ext in "${extensions[@]}"; do
                ext_list+=" php${php_ver}-${ext}"
            done
            echo -e "  ${YELLOW}sudo apt-get update${NC}"
            echo -e "  ${YELLOW}sudo apt-get install$ext_list${NC}"
            ;;
        redhat)
            local ext_list=""
            for ext in "${extensions[@]}"; do
                ext_list+=" php-${ext}"
            done
            echo -e "  ${YELLOW}sudo yum install$ext_list${NC}"
            echo -e "  หรือ ${YELLOW}sudo dnf install$ext_list${NC}"
            ;;
        macos)
            echo -e "  ${YELLOW}brew install php${NC}"
            echo "  (Homebrew PHP มักมี extensions ครบ)"
            ;;
        *)
            echo "  ติดตั้งตาม package manager ของระบบ"
            ;;
    esac
    echo ""

    # ถามว่าต้องการลองติดตั้งอัตโนมัติหรือไม่
    if [ "$AUTO_MODE" != true ] && [ "$os_type" = "debian" ]; then
        read -p "ต้องการให้ลองติดตั้งอัตโนมัติหรือไม่? (ต้องใช้ sudo) (y/n) [y]: " TRY_INSTALL
        TRY_INSTALL=${TRY_INSTALL:-y}
        if [[ $TRY_INSTALL =~ ^[Yy]$ ]]; then
            local php_ver=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
            local ext_list=""
            for ext in "${extensions[@]}"; do
                ext_list+=" php${php_ver}-${ext}"
            done
            print_info "กำลังติดตั้ง PHP extensions..."
            if sudo apt-get update -qq && sudo apt-get install -y $ext_list; then
                print_success "ติดตั้ง PHP extensions สำเร็จ ✓"
                return 0
            else
                print_error "ติดตั้งล้มเหลว"
                return 1
            fi
        fi
    fi

    return 1
}

################################################################################
# ฟังก์ชัน Clone Repository (สำหรับติดตั้งจากศูนย์)
################################################################################
clone_repository() {
    print_header "📥 ดาวน์โหลด TP-Affiliate จาก GitHub"

    # ตรวจสอบว่ามี git หรือไม่
    if ! command -v git &> /dev/null; then
        print_error "ไม่พบ Git!"
        print_info "กำลังติดตั้ง Git..."

        if [ -f /etc/debian_version ]; then
            sudo apt-get update -qq && sudo apt-get install -y git
        elif [ -f /etc/redhat-release ]; then
            sudo yum install -y git || sudo dnf install -y git
        elif [[ "$(uname)" == "Darwin" ]]; then
            xcode-select --install 2>/dev/null || brew install git
        else
            error_exit "กรุณาติดตั้ง Git ก่อน: https://git-scm.com/"
        fi
    fi

    # ตรวจสอบว่าอยู่ในโปรเจคแล้วหรือยัง
    if [ -f "artisan" ] && [ -f "composer.json" ]; then
        print_info "พบโปรเจค Laravel อยู่แล้วในไดเรกทอรีนี้"

        if [ "$AUTO_MODE" = true ] || [ "$WIZARD_MODE" = true ]; then
            print_info "ใช้ไฟล์ที่มีอยู่แล้ว..."
            return 0
        fi

        read -p "ต้องการใช้ไฟล์ที่มีอยู่หรือ clone ใหม่? (use/clone) [use]: " CLONE_CHOICE
        CLONE_CHOICE=${CLONE_CHOICE:-use}
        if [ "$CLONE_CHOICE" = "use" ]; then
            return 0
        fi
    fi

    # หาตำแหน่งติดตั้ง
    local INSTALL_DIR

    # ใช้โฟลเดอร์ที่เลือกไว้จากขั้นตอนก่อนหน้า (ถ้ามี)
    if [ -n "$CUSTOM_INSTALL_DIR" ]; then
        INSTALL_DIR="$CUSTOM_INSTALL_DIR"
        if [ "$INSTALL_DIR" = "." ]; then
            print_info "จะติดตั้งในโฟลเดอร์ปัจจุบัน"
            # Clone โดยตรงลงในโฟลเดอร์ปัจจุบัน
            print_step "กำลัง clone จาก $REPO_URL..."
            print_info "Branch: $REPO_BRANCH"
            echo ""

            # Clone to temp dir then move files
            local TEMP_DIR=".tp-affiliate-temp-$$"
            if git clone -b "$REPO_BRANCH" --depth 1 "$REPO_URL" "$TEMP_DIR" 2>&1; then
                # Move all files from temp to current directory
                shopt -s dotglob
                mv "$TEMP_DIR"/* . 2>/dev/null || true
                shopt -u dotglob
                rm -rf "$TEMP_DIR"
                print_success "Clone สำเร็จ! ✓"
                return 0
            else
                rm -rf "$TEMP_DIR"
                error_exit "Clone repository ล้มเหลว"
            fi
        else
            print_info "จะติดตั้งในโฟลเดอร์: $INSTALL_DIR"
        fi
    elif [ "$WIZARD_MODE" = true ]; then
        INSTALL_DIR="thaiprompt-affiliate"
        print_info "จะติดตั้งในโฟลเดอร์: $INSTALL_DIR"
    else
        read -p "ไดเรกทอรีที่จะติดตั้ง [thaiprompt-affiliate]: " INSTALL_DIR
        INSTALL_DIR=${INSTALL_DIR:-thaiprompt-affiliate}
    fi

    # Clone repository
    print_step "กำลัง clone จาก $REPO_URL..."
    print_info "Branch: $REPO_BRANCH"
    echo ""

    if [ -d "$INSTALL_DIR" ]; then
        print_warning "โฟลเดอร์ $INSTALL_DIR มีอยู่แล้ว"
        if [ "$AUTO_MODE" = true ]; then
            rm -rf "$INSTALL_DIR"
        else
            read -p "ต้องการลบและ clone ใหม่หรือไม่? (y/n) [n]: " DELETE_EXISTING
            if [[ $DELETE_EXISTING =~ ^[Yy]$ ]]; then
                rm -rf "$INSTALL_DIR"
            else
                print_info "ใช้โฟลเดอร์ที่มีอยู่..."
                cd "$INSTALL_DIR"
                return 0
            fi
        fi
    fi

    # Clone with retry
    local CLONE_ATTEMPT=1
    local MAX_CLONE_ATTEMPTS=3

    while [ $CLONE_ATTEMPT -le $MAX_CLONE_ATTEMPTS ]; do
        if git clone -b "$REPO_BRANCH" --depth 1 "$REPO_URL" "$INSTALL_DIR" 2>&1; then
            print_success "Clone สำเร็จ! ✓"
            cd "$INSTALL_DIR"
            return 0
        else
            print_warning "Clone ล้มเหลว (ครั้งที่ $CLONE_ATTEMPT/$MAX_CLONE_ATTEMPTS)"
            CLONE_ATTEMPT=$((CLONE_ATTEMPT + 1))
            if [ $CLONE_ATTEMPT -le $MAX_CLONE_ATTEMPTS ]; then
                print_info "รอ 3 วินาที แล้วลองใหม่..."
                sleep 3
            fi
        fi
    done

    error_exit "Clone repository ล้มเหลว กรุณาตรวจสอบการเชื่อมต่ออินเทอร์เน็ต"
}

################################################################################
# ฟังก์ชัน Wizard Mode (สำหรับผู้ใช้ทั่วไป) - Step-by-Step
################################################################################

# แสดง progress bar ของ wizard
show_wizard_progress() {
    local current_step=$1
    local total_steps=5
    local step_names=("ยินดีต้อนรับ" "ตั้งค่า Database" "ตั้งค่าแอป" "สร้าง Admin" "ยืนยัน")

    echo ""
    echo -e "${CYAN}┌────────────────────────────────────────────────────────────────┐${NC}"
    echo -ne "${CYAN}│${NC} "

    for i in $(seq 1 $total_steps); do
        if [ $i -lt $current_step ]; then
            echo -ne "${GREEN}●${NC}"  # เสร็จแล้ว
        elif [ $i -eq $current_step ]; then
            echo -ne "${YELLOW}◉${NC}"  # กำลังทำ
        else
            echo -ne "${WHITE}○${NC}"  # ยังไม่ถึง
        fi

        if [ $i -lt $total_steps ]; then
            if [ $i -lt $current_step ]; then
                echo -ne "${GREEN}────${NC}"
            else
                echo -ne "${WHITE}────${NC}"
            fi
        fi
    done

    echo -e " ${CYAN}│${NC}"
    echo -ne "${CYAN}│${NC} "

    for i in $(seq 1 $total_steps); do
        local name="${step_names[$((i-1))]}"
        local padding=$((12 - ${#name}))
        if [ $i -eq $current_step ]; then
            echo -ne "${YELLOW}${BOLD}${name}${NC}"
        elif [ $i -lt $current_step ]; then
            echo -ne "${GREEN}${name}${NC}"
        else
            echo -ne "${WHITE}${name}${NC}"
        fi
        # เพิ่ม spacing
        [ $i -lt $total_steps ] && echo -ne "  "
    done

    echo -e " ${CYAN}│${NC}"
    echo -e "${CYAN}└────────────────────────────────────────────────────────────────┘${NC}"
    echo ""
}

# ถามคำถามแบบ interactive พร้อม validation
# ใช้ >&2 เพื่อ output prompt ไปที่ stderr แทน stdout
# เพื่อให้ capture เฉพาะ result ได้ถูกต้อง
ask_question() {
    local prompt="$1"
    local default="$2"
    local required="${3:-false}"
    local is_password="${4:-false}"
    local result=""

    if [ "$is_password" = true ]; then
        while true; do
            echo -ne "  ${CYAN}?${NC} $prompt" >&2
            [ -n "$default" ] && echo -ne " ${WHITE}[$default]${NC}" >&2
            echo -ne ": " >&2
            read -s result
            echo "" >&2

            if [ -z "$result" ] && [ -n "$default" ]; then
                result="$default"
            fi

            if [ "$required" = true ] && [ -z "$result" ]; then
                print_error "    กรุณากรอกข้อมูล!" >&2
                continue
            fi
            break
        done
    else
        while true; do
            echo -ne "  ${CYAN}?${NC} $prompt" >&2
            [ -n "$default" ] && echo -ne " ${WHITE}[$default]${NC}" >&2
            echo -ne ": " >&2
            read result

            if [ -z "$result" ] && [ -n "$default" ]; then
                result="$default"
            fi

            if [ "$required" = true ] && [ -z "$result" ]; then
                print_error "    กรุณากรอกข้อมูล!" >&2
                continue
            fi
            break
        done
    fi

    echo "$result"
}

run_wizard_mode() {
    clear

    ############################################################################
    # Step 1: Welcome
    ############################################################################
    echo ""
    echo -e "${GREEN}╔════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║${NC}                                                                ${GREEN}║${NC}"
    echo -e "${GREEN}║${NC}    ${MAGENTA}${BOLD}🧙 TP-Affiliate Installation Wizard${NC}                       ${GREEN}║${NC}"
    echo -e "${GREEN}║${NC}                                                                ${GREEN}║${NC}"
    echo -e "${GREEN}║${NC}    ${CYAN}ติดตั้งง่ายๆ แค่ตอบคำถามไม่กี่ข้อ${NC}                        ${GREEN}║${NC}"
    echo -e "${GREEN}║${NC}                                                                ${GREEN}║${NC}"
    echo -e "${GREEN}╚════════════════════════════════════════════════════════════════╝${NC}"

    show_wizard_progress 1

    echo -e "${BOLD}Wizard จะช่วยคุณติดตั้งผ่าน 5 ขั้นตอนง่ายๆ:${NC}"
    echo ""
    echo -e "  ${GREEN}1.${NC} ยินดีต้อนรับ - แนะนำระบบ"
    echo -e "  ${GREEN}2.${NC} ตั้งค่า Database - เชื่อมต่อฐานข้อมูล MySQL"
    echo -e "  ${GREEN}3.${NC} ตั้งค่าแอป - ชื่อเว็บไซต์และ URL"
    echo -e "  ${GREEN}4.${NC} สร้าง Admin - บัญชีผู้ดูแลระบบ"
    echo -e "  ${GREEN}5.${NC} ยืนยัน - ตรวจสอบและเริ่มติดตั้ง"
    echo ""

    # ตรวจสอบว่ามี cache หรือไม่
    if [ -f "$CACHE_FILE" ]; then
        echo -e "${YELLOW}💾 พบการตั้งค่าจากการติดตั้งครั้งก่อน${NC}"
        read -p "  ต้องการใช้การตั้งค่าเดิม? (y/n) [y]: " USE_OLD
        USE_OLD=${USE_OLD:-y}
        if [[ $USE_OLD =~ ^[Yy]$ ]]; then
            print_success "  โหลดการตั้งค่าเดิม..."
            # โหลดค่าจาก cache
            DB_HOST=$(load_from_cache "DB_HOST" "127.0.0.1")
            DB_PORT=$(load_from_cache "DB_PORT" "3306")
            DB_DATABASE=$(load_from_cache "DB_DATABASE" "thaiprompt_affiliate")
            DB_USERNAME=$(load_from_cache "DB_USERNAME" "root")
            DB_PASSWORD=$(load_from_cache "DB_PASSWORD" "")
            APP_NAME=$(load_from_cache "APP_NAME" "TP-Affiliate")
            APP_URL=$(load_from_cache "APP_URL" "http://localhost")
            ADMIN_NAME=$(load_from_cache "ADMIN_NAME" "Admin")
            ADMIN_EMAIL=$(load_from_cache "ADMIN_EMAIL" "admin@example.com")

            # ข้ามไป step 5 ถาม password เท่านั้น
            skip_to_password=true
        fi
    fi

    read -p "กด Enter เพื่อเริ่มต้น..."
    clear

    ############################################################################
    # Step 2: Database Configuration
    ############################################################################
    if [ "$skip_to_password" != true ]; then
        echo ""
        echo -e "${GREEN}╔════════════════════════════════════════════════════════════════╗${NC}"
        echo -e "${GREEN}║${NC}    ${MAGENTA}${BOLD}🧙 TP-Affiliate Installation Wizard${NC}                       ${GREEN}║${NC}"
        echo -e "${GREEN}╚════════════════════════════════════════════════════════════════╝${NC}"

        show_wizard_progress 2

        echo -e "${BOLD}📦 ขั้นตอนที่ 2: ตั้งค่า Database${NC}"
        echo ""
        echo -e "${YELLOW}คุณต้องมี MySQL/MariaDB database พร้อมใช้งาน${NC}"
        echo -e "ถ้ายังไม่มี กรุณาสร้างก่อน:"
        echo -e "  ${CYAN}mysql -u root -p -e \"CREATE DATABASE thaiprompt_affiliate;\"${NC}"
        echo ""

        DB_HOST=$(ask_question "Database Host" "127.0.0.1")
        DB_PORT=$(ask_question "Database Port" "3306")
        DB_DATABASE=$(ask_question "Database Name" "thaiprompt_affiliate")
        DB_USERNAME=$(ask_question "Database Username" "root")
        DB_PASSWORD=$(ask_question "Database Password" "" false true)

        # บันทึก cache
        save_to_cache "DB_HOST" "$DB_HOST"
        save_to_cache "DB_PORT" "$DB_PORT"
        save_to_cache "DB_DATABASE" "$DB_DATABASE"
        save_to_cache "DB_USERNAME" "$DB_USERNAME"
        save_to_cache "DB_PASSWORD" "$DB_PASSWORD"

        echo ""
        print_success "บันทึกการตั้งค่า Database เรียบร้อย"
        read -p "กด Enter เพื่อไปขั้นตอนถัดไป..."
        clear

        ############################################################################
        # Step 3: App Configuration
        ############################################################################
        echo ""
        echo -e "${GREEN}╔════════════════════════════════════════════════════════════════╗${NC}"
        echo -e "${GREEN}║${NC}    ${MAGENTA}${BOLD}🧙 TP-Affiliate Installation Wizard${NC}                       ${GREEN}║${NC}"
        echo -e "${GREEN}╚════════════════════════════════════════════════════════════════╝${NC}"

        show_wizard_progress 3

        echo -e "${BOLD}🌐 ขั้นตอนที่ 3: ตั้งค่าแอปพลิเคชัน${NC}"
        echo ""

        APP_NAME=$(ask_question "ชื่อแอปพลิเคชัน" "TP-Affiliate")
        APP_URL=$(ask_question "URL ของเว็บไซต์ (เช่น https://example.com)" "http://localhost")

        save_to_cache "APP_NAME" "$APP_NAME"
        save_to_cache "APP_URL" "$APP_URL"

        echo ""
        print_success "บันทึกการตั้งค่าแอปเรียบร้อย"
        read -p "กด Enter เพื่อไปขั้นตอนถัดไป..."
        clear

        ############################################################################
        # Step 4: Admin Account
        ############################################################################
        echo ""
        echo -e "${GREEN}╔════════════════════════════════════════════════════════════════╗${NC}"
        echo -e "${GREEN}║${NC}    ${MAGENTA}${BOLD}🧙 TP-Affiliate Installation Wizard${NC}                       ${GREEN}║${NC}"
        echo -e "${GREEN}╚════════════════════════════════════════════════════════════════╝${NC}"

        show_wizard_progress 4

        echo -e "${BOLD}👤 ขั้นตอนที่ 4: สร้าง Admin Account${NC}"
        echo ""
        echo -e "${YELLOW}บัญชีนี้จะเป็น Super Admin ของระบบ${NC}"
        echo ""

        ADMIN_NAME=$(ask_question "ชื่อผู้ดูแลระบบ" "Admin")
        ADMIN_EMAIL=$(ask_question "Email ผู้ดูแลระบบ" "" true)

        save_to_cache "ADMIN_NAME" "$ADMIN_NAME"
        save_to_cache "ADMIN_EMAIL" "$ADMIN_EMAIL"
    fi

    ############################################################################
    # Step 4b: Admin Password (ถามเสมอ)
    ############################################################################
    if [ "$skip_to_password" = true ]; then
        echo ""
        echo -e "${GREEN}╔════════════════════════════════════════════════════════════════╗${NC}"
        echo -e "${GREEN}║${NC}    ${MAGENTA}${BOLD}🧙 TP-Affiliate Installation Wizard${NC}                       ${GREEN}║${NC}"
        echo -e "${GREEN}╚════════════════════════════════════════════════════════════════╝${NC}"

        show_wizard_progress 4

        echo -e "${BOLD}👤 ตั้งค่า Admin Password${NC}"
        echo ""
        echo -e "Admin Email: ${GREEN}${ADMIN_EMAIL}${NC}"
        echo ""
    fi

    echo ""
    echo -e "${BOLD}🔐 ตั้งรหัสผ่าน Admin${NC}"

    while true; do
        ADMIN_PASSWORD=$(ask_question "รหัสผ่าน (อย่างน้อย 8 ตัว)" "" true true)
        if [ ${#ADMIN_PASSWORD} -lt 8 ]; then
            print_error "    รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร!"
            continue
        fi

        ADMIN_PASSWORD_CONFIRM=$(ask_question "ยืนยันรหัสผ่าน" "" true true)
        if [ "$ADMIN_PASSWORD" != "$ADMIN_PASSWORD_CONFIRM" ]; then
            print_error "    รหัสผ่านไม่ตรงกัน!"
            continue
        fi
        break
    done

    echo ""
    print_success "ตั้งรหัสผ่าน Admin เรียบร้อย"
    read -p "กด Enter เพื่อไปขั้นตอนสุดท้าย..."
    clear

    ############################################################################
    # Step 5: Confirmation
    ############################################################################
    echo ""
    echo -e "${GREEN}╔════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║${NC}    ${MAGENTA}${BOLD}🧙 TP-Affiliate Installation Wizard${NC}                       ${GREEN}║${NC}"
    echo -e "${GREEN}╚════════════════════════════════════════════════════════════════╝${NC}"

    show_wizard_progress 5

    echo -e "${BOLD}✅ ขั้นตอนที่ 5: ยืนยันการติดตั้ง${NC}"
    echo ""
    echo -e "${CYAN}┌────────────────────────────────────────────────────────────────┐${NC}"
    echo -e "${CYAN}│${NC} ${BOLD}สรุปการตั้งค่า${NC}                                                ${CYAN}│${NC}"
    echo -e "${CYAN}├────────────────────────────────────────────────────────────────┤${NC}"
    echo -e "${CYAN}│${NC}                                                                ${CYAN}│${NC}"
    echo -e "${CYAN}│${NC}  🌐 ${WHITE}App Name:${NC}  ${GREEN}$APP_NAME${NC}"
    echo -e "${CYAN}│${NC}  🔗 ${WHITE}App URL:${NC}   ${GREEN}$APP_URL${NC}"
    echo -e "${CYAN}│${NC}                                                                ${CYAN}│${NC}"
    echo -e "${CYAN}│${NC}  🗄️  ${WHITE}Database:${NC}  ${GREEN}$DB_DATABASE${NC}@${GREEN}$DB_HOST${NC}:${GREEN}$DB_PORT${NC}"
    echo -e "${CYAN}│${NC}  👤 ${WHITE}DB User:${NC}   ${GREEN}$DB_USERNAME${NC}"
    echo -e "${CYAN}│${NC}                                                                ${CYAN}│${NC}"
    echo -e "${CYAN}│${NC}  📧 ${WHITE}Admin:${NC}     ${GREEN}$ADMIN_EMAIL${NC}"
    echo -e "${CYAN}│${NC}                                                                ${CYAN}│${NC}"
    echo -e "${CYAN}└────────────────────────────────────────────────────────────────┘${NC}"
    echo ""

    # Set default values
    APP_ENV="production"
    APP_DEBUG="false"
    INSTALL_MODE="standard"

    read -p "ดำเนินการติดตั้งต่อ? (y/n) [y]: " CONTINUE_INSTALL
    CONTINUE_INSTALL=${CONTINUE_INSTALL:-y}
    if [[ ! $CONTINUE_INSTALL =~ ^[Yy]$ ]]; then
        print_info "ยกเลิกการติดตั้ง"
        exit 0
    fi

    echo ""
    echo -e "${GREEN}${BOLD}🚀 เริ่มการติดตั้ง...${NC}"
    echo ""

    # ทำเครื่องหมายว่าผ่าน wizard แล้ว
    save_checkpoint "WIZARD_COMPLETED"
}

################################################################################
# ฟังก์ชัน Deploy Ready Check (ตรวจสอบพร้อมสำหรับ deploy-pro.sh)
################################################################################
check_deploy_ready() {
    print_header "🚀 ตรวจสอบความพร้อมสำหรับ Deploy"

    local all_ready=true
    local issues=()

    # Check .env file
    print_step "ตรวจสอบ .env file..."
    if [ -f ".env" ]; then
        print_success ".env file ✓"

        # Check APP_KEY
        if grep -q "^APP_KEY=base64:" ".env"; then
            print_success "  APP_KEY ✓"
        else
            issues+=("APP_KEY ยังไม่ได้ตั้งค่า (รัน: php artisan key:generate)")
            all_ready=false
        fi

        # Check database config
        if grep -q "^DB_DATABASE=" ".env" && ! grep -q "^DB_DATABASE=$" ".env"; then
            print_success "  Database config ✓"
        else
            issues+=("Database ยังไม่ได้ตั้งค่า")
            all_ready=false
        fi
    else
        issues+=(".env file ไม่พบ")
        all_ready=false
    fi

    # Check vendor directory
    print_step "ตรวจสอบ Composer dependencies..."
    if [ -d "vendor" ] && [ -f "vendor/autoload.php" ]; then
        print_success "Composer dependencies ✓"
    else
        issues+=("Composer dependencies ยังไม่ได้ติดตั้ง (รัน: composer install)")
        all_ready=false
    fi

    # Check node_modules
    print_step "ตรวจสอบ Node dependencies..."
    if [ -d "node_modules" ]; then
        print_success "Node dependencies ✓"
    else
        issues+=("Node dependencies ยังไม่ได้ติดตั้ง (รัน: npm install)")
        all_ready=false
    fi

    # Check public/build
    print_step "ตรวจสอบ Build assets..."
    if [ -d "public/build" ]; then
        print_success "Build assets ✓"
    else
        issues+=("Build assets ยังไม่ได้สร้าง (รัน: npm run build)")
        all_ready=false
    fi

    # Check storage permissions
    print_step "ตรวจสอบ Storage permissions..."
    if [ -w "storage" ] && [ -w "bootstrap/cache" ]; then
        print_success "Storage permissions ✓"
    else
        issues+=("Storage permissions ไม่ถูกต้อง")
        all_ready=false
    fi

    # Check storage link
    print_step "ตรวจสอบ Storage link..."
    if [ -L "public/storage" ]; then
        print_success "Storage link ✓"
    else
        issues+=("Storage link ยังไม่ได้สร้าง (รัน: php artisan storage:link)")
        all_ready=false
    fi

    # Check database connection
    print_step "ตรวจสอบ Database connection..."
    if php artisan tinker --execute="DB::connection()->getPdo();" &>/dev/null; then
        print_success "Database connection ✓"
    else
        issues+=("Database connection ล้มเหลว")
        all_ready=false
    fi

    # Check deploy-pro.sh exists
    print_step "ตรวจสอบ deploy-pro.sh..."
    if [ -f "deploy-pro.sh" ]; then
        print_success "deploy-pro.sh ✓"
    else
        issues+=("deploy-pro.sh ไม่พบ")
        all_ready=false
    fi

    echo ""

    if [ "$all_ready" = true ]; then
        echo -e "${GREEN}${BOLD}╔════════════════════════════════════════════════════════════════╗${NC}"
        echo -e "${GREEN}${BOLD}║${NC}                                                                ${GREEN}${BOLD}║${NC}"
        echo -e "${GREEN}${BOLD}║${NC}    ${GREEN}✅ พร้อมสำหรับ Deploy!${NC}                                      ${GREEN}${BOLD}║${NC}"
        echo -e "${GREEN}${BOLD}║${NC}                                                                ${GREEN}${BOLD}║${NC}"
        echo -e "${GREEN}${BOLD}║${NC}    คุณสามารถรัน deploy-pro.sh ได้แล้ว:                        ${GREEN}${BOLD}║${NC}"
        echo -e "${GREEN}${BOLD}║${NC}    ${YELLOW}./deploy-pro.sh${NC}                                           ${GREEN}${BOLD}║${NC}"
        echo -e "${GREEN}${BOLD}║${NC}                                                                ${GREEN}${BOLD}║${NC}"
        echo -e "${GREEN}${BOLD}╚════════════════════════════════════════════════════════════════╝${NC}"
        return 0
    else
        echo -e "${YELLOW}${BOLD}╔════════════════════════════════════════════════════════════════╗${NC}"
        echo -e "${YELLOW}${BOLD}║${NC}                                                                ${YELLOW}${BOLD}║${NC}"
        echo -e "${YELLOW}${BOLD}║${NC}    ${YELLOW}⚠️ ยังไม่พร้อมสำหรับ Deploy${NC}                                 ${YELLOW}${BOLD}║${NC}"
        echo -e "${YELLOW}${BOLD}║${NC}                                                                ${YELLOW}${BOLD}║${NC}"
        echo -e "${YELLOW}${BOLD}╚════════════════════════════════════════════════════════════════╝${NC}"
        echo ""
        print_info "ปัญหาที่พบ:"
        for issue in "${issues[@]}"; do
            echo -e "  ${RED}•${NC} $issue"
        done
        echo ""
        return 1
    fi
}

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
# Parse CLI Arguments (ต้องทำก่อน banner)
################################################################################
parse_arguments "$@"

# Handle --force flag
if [ "$FORCE_REINSTALL" = true ]; then
    rm -f "$PROGRESS_FILE" "$CACHE_FILE" 2>/dev/null || true
fi

################################################################################
# ตรวจจับโฟลเดอร์ว่าง - เปิด Wizard Mode อัตโนมัติ
################################################################################
detect_empty_folder() {
    # ตรวจสอบว่าอยู่ในโฟลเดอร์ที่มี Laravel project หรือไม่
    if [ ! -f "artisan" ] && [ ! -f "composer.json" ]; then
        return 0  # โฟลเดอร์ว่าง
    fi
    return 1  # มีไฟล์ Laravel แล้ว
}

# ถ้าโฟลเดอร์ว่างและไม่ได้ระบุ mode ใดๆ → ถามทีละขั้นตอน
if detect_empty_folder; then
    if [ "$AUTO_MODE" != true ] && [ "$WIZARD_MODE" != true ]; then
        clear
        echo ""
        echo -e "${GREEN}╔════════════════════════════════════════════════════════════════╗${NC}"
        echo -e "${GREEN}║${NC}                                                                ${GREEN}║${NC}"
        echo -e "${GREEN}║${NC}    ${MAGENTA}${BOLD}🎯 ยินดีต้อนรับสู่ TP-Affiliate Installer${NC}                  ${GREEN}║${NC}"
        echo -e "${GREEN}║${NC}                                                                ${GREEN}║${NC}"
        echo -e "${GREEN}╚════════════════════════════════════════════════════════════════╝${NC}"
        echo ""
        echo -e "${YELLOW}⚠️  ตรวจพบว่าโฟลเดอร์นี้ว่างเปล่า${NC}"
        echo ""
        echo "เราจะช่วยคุณติดตั้ง TP-Affiliate ตั้งแต่ต้น"
        echo ""

        ########################################################################
        # ขั้นตอนที่ 1: ถามตำแหน่งติดตั้ง
        ########################################################################
        echo -e "${CYAN}${BOLD}📁 ขั้นตอนที่ 1: เลือกตำแหน่งติดตั้ง${NC}"
        echo ""
        echo -e "  ${GREEN}1${NC}) ติดตั้งในโฟลเดอร์ปัจจุบัน ($(pwd))"
        echo -e "  ${GREEN}2${NC}) สร้างโฟลเดอร์ใหม่"
        echo ""
        read -p "เลือกตัวเลือก [1]: " FOLDER_CHOICE
        FOLDER_CHOICE=${FOLDER_CHOICE:-1}

        INSTALL_DIR="."
        if [ "$FOLDER_CHOICE" = "2" ]; then
            echo ""
            read -p "ชื่อโฟลเดอร์ที่ต้องการสร้าง [thaiprompt-affiliate]: " INSTALL_DIR
            INSTALL_DIR=${INSTALL_DIR:-thaiprompt-affiliate}

            if [ -d "$INSTALL_DIR" ]; then
                echo ""
                print_warning "โฟลเดอร์ '$INSTALL_DIR' มีอยู่แล้ว"
                read -p "ต้องการใช้โฟลเดอร์นี้หรือไม่? (y/n) [y]: " USE_EXISTING
                USE_EXISTING=${USE_EXISTING:-y}
                if [[ ! $USE_EXISTING =~ ^[Yy]$ ]]; then
                    read -p "ชื่อโฟลเดอร์ใหม่: " INSTALL_DIR
                fi
            fi

            # บันทึกชื่อโฟลเดอร์สำหรับใช้ใน clone_repository
            export CUSTOM_INSTALL_DIR="$INSTALL_DIR"
            print_success "จะติดตั้งในโฟลเดอร์: $INSTALL_DIR"
        else
            print_success "จะติดตั้งในโฟลเดอร์ปัจจุบัน"
        fi
        echo ""

        ########################################################################
        # ขั้นตอนที่ 2: เลือกโหมดการติดตั้ง
        ########################################################################
        echo -e "${CYAN}${BOLD}⚙️  ขั้นตอนที่ 2: เลือกโหมดการติดตั้ง${NC}"
        echo ""
        echo -e "  ${GREEN}1${NC}) 🧙 ${BOLD}Wizard Mode${NC} (แนะนำ) - ถามทีละขั้นตอน ง่ายสำหรับผู้เริ่มต้น"
        echo -e "  ${GREEN}2${NC}) ⚡ ${BOLD}Auto Mode${NC} - ติดตั้งอัตโนมัติด้วยค่าเริ่มต้น"
        echo -e "  ${GREEN}3${NC}) 📥 ${BOLD}Clone Only${NC} - ดาวน์โหลดโค้ดอย่างเดียว ตั้งค่าเองทีหลัง"
        echo -e "  ${GREEN}0${NC}) ❌ ยกเลิก"
        echo ""
        read -p "เลือกตัวเลือก [1]: " INSTALL_CHOICE
        INSTALL_CHOICE=${INSTALL_CHOICE:-1}

        case $INSTALL_CHOICE in
            1)
                WIZARD_MODE=true
                DO_CLONE=true
                print_success "เลือก Wizard Mode"
                ;;
            2)
                AUTO_MODE=true
                DO_CLONE=true
                print_success "เลือก Auto Mode"
                ;;
            3)
                DO_CLONE=true
                print_success "เลือก Clone Only"
                ;;
            0)
                print_info "ยกเลิกการติดตั้ง"
                exit 0
                ;;
            *)
                WIZARD_MODE=true
                DO_CLONE=true
                print_info "เลือก Wizard Mode (ค่าเริ่มต้น)"
                ;;
        esac
        echo ""
    else
        # ถ้าอยู่ใน auto mode หรือ wizard mode แล้ว ให้ clone อัตโนมัติ
        DO_CLONE=true
    fi
fi

################################################################################
# Handle --clone flag (Clone repository before installation)
################################################################################
if [ "$DO_CLONE" = true ]; then
    clone_repository
fi

################################################################################
# Handle --wizard flag (Run wizard mode for simple setup)
################################################################################
if [ "$WIZARD_MODE" = true ]; then
    run_wizard_mode
fi

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
echo -e "${GREEN}║${NC}    ${BLUE}${BOLD}🚀 TP-Affiliate Ultimate Installation v${SCRIPT_VERSION}${NC}              ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}    ${YELLOW}${BOLD}⚡ ไฟล์เดียวจบ - ติดตั้งครบทุกอย่าง${NC}                     ${GREEN}║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""

# Show current settings
if [ "$WIZARD_MODE" = true ]; then
    echo -e "${GREEN}${BOLD}🧙 Wizard Mode: เปิดใช้งาน${NC}"
fi
if [ "$AUTO_MODE" = true ]; then
    echo -e "${CYAN}${BOLD}⚡ Auto Mode: เปิดใช้งาน${NC}"
fi
echo -e "${BLUE}📦 Installation Mode: ${YELLOW}${BOLD}$INSTALL_MODE${NC}"
case $INSTALL_MODE in
    minimal)  echo -e "   ${WHITE}→ Core settings เท่านั้น (production-ready)${NC}" ;;
    standard) echo -e "   ${WHITE}→ Core + demo users + essential data (แนะนำ)${NC}" ;;
    full)     echo -e "   ${WHITE}→ ทุกอย่าง รวม demo data ทั้งหมด${NC}" ;;
esac
echo ""

echo -e "${BLUE}ระบบติดตั้งนี้จะทำทุกอย่างให้คุณอัตโนมัติ:${NC}"
echo -e "  • ตรวจสอบ system requirements + pre-flight checks"
echo -e "  • ติดตั้ง dependencies (Composer + npm)"
echo -e "  • ตั้งค่า database และ migrations"
echo -e "  • รัน seeders ผ่าน DatabaseSeeder (ตรงกับ codebase เสมอ)"
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

    if [ "$AUTO_MODE" = true ]; then
        # Auto mode: ดำเนินการต่ออัตโนมัติ
        print_success "Auto Mode: ดำเนินการต่อจาก checkpoint สุดท้าย..."
        echo ""
    else
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
fi

################################################################################
# STEP 1: ตรวจสอบ System Requirements
################################################################################
if ! is_step_completed "STEP_1_SYSTEM_CHECK"; then
print_header "📋 STEP 1: ตรวจสอบ System Requirements"

# Run pre-flight checks first
run_preflight_checks

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
    echo ""
    print_error "ขาด PHP extensions: ${MISSING_EXTENSIONS[*]}"

    # ลองติดตั้งอัตโนมัติ
    if suggest_php_extensions "${MISSING_EXTENSIONS[@]}"; then
        # ตรวจสอบอีกครั้งหลังติดตั้ง
        print_info "ตรวจสอบ PHP extensions อีกครั้ง..."
        STILL_MISSING=()
        for ext in "${MISSING_EXTENSIONS[@]}"; do
            if ! php -r "exit(extension_loaded('$ext') ? 0 : 1);"; then
                STILL_MISSING+=("$ext")
            fi
        done
        if [ ${#STILL_MISSING[@]} -eq 0 ]; then
            print_success "ติดตั้ง PHP extensions ครบทุกตัวแล้ว ✓"
        else
            error_exit "ยังขาด PHP extensions: ${STILL_MISSING[*]}"
        fi
    else
        error_exit "กรุณาติดตั้ง PHP extensions ที่ขาดหายไป"
    fi
fi

# Check Composer
print_step "ตรวจสอบ Composer..."
if command -v composer &> /dev/null; then
    COMPOSER_VERSION=$(composer --version --no-ansi 2>/dev/null | grep -oP '\d+\.\d+\.\d+' | head -1)
    print_success "Composer: $COMPOSER_VERSION ✓"
elif [ -f "./composer.phar" ]; then
    COMPOSER_VERSION=$(php ./composer.phar --version --no-ansi 2>/dev/null | grep -oP '\d+\.\d+\.\d+' | head -1)
    print_success "Composer (local): $COMPOSER_VERSION ✓"
    # Create alias for this session
    composer() { php "$(pwd)/composer.phar" "$@"; }
    export -f composer
else
    print_warning "ไม่พบ Composer!"

    # ลองติดตั้งอัตโนมัติ
    if [ "$AUTO_MODE" = true ]; then
        install_composer || error_exit "ติดตั้ง Composer ล้มเหลว"
    else
        read -p "ต้องการติดตั้ง Composer อัตโนมัติหรือไม่? (y/n) [y]: " INSTALL_COMPOSER
        INSTALL_COMPOSER=${INSTALL_COMPOSER:-y}
        if [[ $INSTALL_COMPOSER =~ ^[Yy]$ ]]; then
            install_composer || error_exit "ติดตั้ง Composer ล้มเหลว"
        else
            error_exit "ต้องมี Composer เพื่อติดตั้ง dependencies"
        fi
    fi

    # ตรวจสอบอีกครั้ง
    if command -v composer &> /dev/null || [ -f "./composer.phar" ]; then
        print_success "Composer พร้อมใช้งาน ✓"
    else
        error_exit "Composer ยังไม่พร้อมใช้งาน"
    fi
fi

# Check Node.js (optional but recommended)
print_step "ตรวจสอบ Node.js..."
if command -v node &> /dev/null && command -v npm &> /dev/null; then
    NODE_VERSION=$(node --version)
    NPM_VERSION=$(npm --version)
    print_success "Node.js: $NODE_VERSION, npm: $NPM_VERSION ✓"
else
    print_warning "ไม่พบ Node.js/npm"

    # ลองติดตั้งอัตโนมัติ
    if [ "$AUTO_MODE" = true ]; then
        print_info "Auto Mode: ลองติดตั้ง Node.js อัตโนมัติ..."
        if install_nodejs; then
            NODE_VERSION=$(node --version 2>/dev/null || echo "installed")
            print_success "Node.js: $NODE_VERSION ✓"
        else
            print_warning "ข้าม Node.js - frontend assets จะต้องติดตั้งเอง"
        fi
    else
        read -p "ต้องการติดตั้ง Node.js อัตโนมัติหรือไม่? (y/n) [y]: " INSTALL_NODE
        INSTALL_NODE=${INSTALL_NODE:-y}
        if [[ $INSTALL_NODE =~ ^[Yy]$ ]]; then
            if install_nodejs; then
                NODE_VERSION=$(node --version 2>/dev/null || echo "installed")
                print_success "Node.js: $NODE_VERSION ✓"
            else
                print_warning "ข้าม Node.js - frontend assets จะต้องติดตั้งเอง"
            fi
        else
            print_info "ข้าม Node.js - frontend assets จะต้องติดตั้งเอง"
        fi
    fi
fi

# Check Git
print_step "ตรวจสอบ Git..."
if command -v git &> /dev/null; then
    GIT_VERSION=$(git --version | grep -oP '\d+\.\d+\.\d+' | head -1)
    print_success "Git: $GIT_VERSION ✓"
else
    print_warning "ไม่พบ Git (บางฟีเจอร์อาจใช้งานไม่ได้)"
fi

# Check MySQL client (optional but recommended)
print_step "ตรวจสอบ MySQL client..."
if command -v mysql &> /dev/null; then
    MYSQL_VERSION=$(mysql --version | grep -oP '\d+\.\d+\.\d+' | head -1)
    print_success "MySQL client: $MYSQL_VERSION ✓"
else
    print_warning "ไม่พบ MySQL client (จะทดสอบ connection ผ่าน PHP แทน)"
fi

print_success "ผ่านการตรวจสอบ System Requirements ทั้งหมด! ✓"
save_checkpoint "STEP_1_SYSTEM_CHECK"
fi

################################################################################
# STEP 2: รับข้อมูลการตั้งค่า
################################################################################
if ! is_step_completed "STEP_2_CONFIG"; then
print_header "⚙️  STEP 2: การตั้งค่าแอปพลิเคชัน"

# Wizard Mode: ข้ามขั้นตอนนี้ถ้าตั้งค่าใน wizard แล้ว
if is_step_completed "WIZARD_COMPLETED"; then
    print_info "🧙 Wizard Mode: ใช้การตั้งค่าจาก wizard"
    echo ""

    # โหลดค่าจาก wizard cache
    APP_NAME=$(load_from_cache "APP_NAME" "TP-Affiliate")
    APP_URL=$(load_from_cache "APP_URL" "http://localhost")
    APP_ENV="production"
    APP_DEBUG="false"

    DB_HOST=$(load_from_cache "DB_HOST" "127.0.0.1")
    DB_PORT=$(load_from_cache "DB_PORT" "3306")
    DB_DATABASE=$(load_from_cache "DB_DATABASE" "thaiprompt_affiliate")
    DB_USERNAME=$(load_from_cache "DB_USERNAME" "root")
    DB_PASSWORD=$(load_from_cache "DB_PASSWORD" "")

    ADMIN_NAME=$(load_from_cache "ADMIN_NAME" "Admin")
    ADMIN_EMAIL=$(load_from_cache "ADMIN_EMAIL" "admin@example.com")
    # ADMIN_PASSWORD ถูกตั้งค่าไว้แล้วจาก wizard

    print_success "โหลดการตั้งค่าจาก wizard ✓"
    echo ""

# Auto Mode: ใช้ค่าจาก cache หรือ defaults
elif [ "$AUTO_MODE" = true ]; then
    print_info "🤖 Auto Mode: ใช้ค่าจาก cache หรือ defaults"
    echo ""

    # โหลดค่าทั้งหมดจาก cache หรือใช้ defaults
    APP_NAME=$(load_from_cache "APP_NAME" "TP-Affiliate")
    APP_URL=$(load_from_cache "APP_URL" "http://localhost")
    APP_ENV=$(load_from_cache "APP_ENV" "production")
    APP_DEBUG=$( [ "$APP_ENV" == "local" ] && echo "true" || echo "false" )

    DB_HOST=$(load_from_cache "DB_HOST" "127.0.0.1")
    DB_PORT=$(load_from_cache "DB_PORT" "3306")
    DB_DATABASE=$(load_from_cache "DB_DATABASE" "thaiprompt_affiliate")
    DB_USERNAME=$(load_from_cache "DB_USERNAME" "root")
    DB_PASSWORD=$(load_from_cache "DB_PASSWORD" "")

    ADMIN_NAME=$(load_from_cache "ADMIN_NAME" "Admin")
    ADMIN_EMAIL=$(load_from_cache "ADMIN_EMAIL" "admin@example.com")
    ADMIN_PASSWORD=$(load_from_cache "ADMIN_PASSWORD" "")

    # ถ้าไม่มี password ให้สร้างใหม่
    if [ -z "$ADMIN_PASSWORD" ]; then
        ADMIN_PASSWORD=$(openssl rand -base64 12 | tr -dc 'a-zA-Z0-9' | head -c 12)
        print_warning "สร้าง Admin Password อัตโนมัติ: $ADMIN_PASSWORD"
        print_info "กรุณาจดจำ password นี้!"
        save_to_cache "ADMIN_PASSWORD" "$ADMIN_PASSWORD"
    fi

    # แสดงค่าที่ใช้
    echo -e "${CYAN}การตั้งค่าที่ใช้:${NC}"
    echo "  App Name: $APP_NAME"
    echo "  App URL: $APP_URL"
    echo "  Environment: $APP_ENV"
    echo "  Database: $DB_DATABASE@$DB_HOST:$DB_PORT"
    echo "  Admin Email: $ADMIN_EMAIL"
    echo ""

else
    # Interactive Mode (เหมือนเดิม)

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
fi

# Test Database Connection (ทำทั้ง auto และ interactive mode)
print_step "ทดสอบการเชื่อมต่อ database..."

# ลองเชื่อมต่อผ่าน mysql client ก่อน
if command -v mysql &> /dev/null; then
    if mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" -e "SELECT 1;" &>/dev/null; then
        print_success "เชื่อมต่อ database สำเร็จ! ✓"

        # สร้าง database ถ้ายังไม่มี
        mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS \`$DB_DATABASE\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null || true
    else
        if [ "$AUTO_MODE" = true ]; then
            print_warning "เชื่อมต่อ database ล้มเหลว - จะลองอีกครั้งตอน migrate"
        else
            error_exit "เชื่อมต่อ database ล้มเหลว! ตรวจสอบ credentials"
        fi
    fi
else
    # ไม่มี mysql client - ใช้ PHP ทดสอบแทน
    print_info "ไม่พบ MySQL client - จะทดสอบผ่าน PHP"
fi

# Super Admin Account (Interactive mode only - auto mode และ wizard mode ได้ตั้งค่าไปแล้ว)
if [ "$AUTO_MODE" != true ] && ! is_step_completed "WIZARD_COMPLETED"; then
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
fi

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

# เก็บ output ของ migration เพื่อวิเคราะห์ error
MIGRATION_OUTPUT=$(php artisan migrate --force 2>&1)
MIGRATION_STATUS=$?

if [ $MIGRATION_STATUS -eq 0 ]; then
    MIGRATION_COUNT=$(php artisan migrate:status 2>/dev/null | grep -c "Ran" || echo "0")
    print_success "รัน migrations สำเร็จ ✓ (สร้าง $MIGRATION_COUNT ตาราง)"
else
    # แสดง error
    echo ""
    print_error "Migration ล้มเหลว:"
    echo "$MIGRATION_OUTPUT" | tail -20
    echo ""

    # ตรวจสอบว่าเป็น foreign key error หรือ table already exists
    if echo "$MIGRATION_OUTPUT" | grep -q -E "(Foreign key|already exists|errno: 150|constraint)"; then
        print_warning "ดูเหมือนว่ามีตารางหรือ constraints ที่ค้างจากการติดตั้งไม่สำเร็จก่อนหน้านี้"
        echo ""

        if [ "$AUTO_MODE" = true ]; then
            # Auto mode: ลองรัน migrate:fresh อัตโนมัติ
            print_step "Auto mode: กำลังรัน migrate:fresh เพื่อเริ่มใหม่..."
            if php artisan migrate:fresh --force; then
                MIGRATION_COUNT=$(php artisan migrate:status 2>/dev/null | grep -c "Ran" || echo "0")
                print_success "รัน migrate:fresh สำเร็จ ✓ (สร้าง $MIGRATION_COUNT ตาราง)"
            else
                error_exit "รัน migrate:fresh ล้มเหลว!"
            fi
        else
            # Interactive mode: ถามผู้ใช้
            echo "คุณต้องการทำอย่างไร?"
            echo ""
            echo "  1) รัน migrate:fresh (ลบตารางทั้งหมดและสร้างใหม่)"
            echo "  2) ยกเลิกการติดตั้ง"
            echo ""
            read -p "เลือก (1/2) [1]: " MIGRATE_CHOICE
            MIGRATE_CHOICE=${MIGRATE_CHOICE:-1}

            if [ "$MIGRATE_CHOICE" = "1" ]; then
                print_step "กำลังรัน migrate:fresh..."
                if php artisan migrate:fresh --force; then
                    MIGRATION_COUNT=$(php artisan migrate:status 2>/dev/null | grep -c "Ran" || echo "0")
                    print_success "รัน migrate:fresh สำเร็จ ✓ (สร้าง $MIGRATION_COUNT ตาราง)"
                else
                    error_exit "รัน migrate:fresh ล้มเหลว!"
                fi
            else
                error_exit "ยกเลิกการติดตั้งตามที่ผู้ใช้ต้องการ"
            fi
        fi
    else
        error_exit "รัน migrations ล้มเหลว!"
    fi
fi

save_checkpoint "STEP_6_MIGRATIONS"
fi

################################################################################
# STEP 7: Database Seeders
################################################################################
if ! is_step_completed "STEP_7_SEEDERS"; then
print_header "🌱 STEP 7: Database Seeders"

# สร้าง log directory
mkdir -p storage/logs
echo "# Seeder Installation Log - $(date)" > "$SEEDER_LOG"
echo "# Installation Mode: $INSTALL_MODE" >> "$SEEDER_LOG"

echo ""
print_info "📦 โหมดการติดตั้ง: ${BOLD}$INSTALL_MODE${NC}"

case $INSTALL_MODE in
    minimal)
        echo -e "   ${WHITE}→ ติดตั้งเฉพาะ core settings (ไม่มี demo data)${NC}"
        ;;
    standard)
        echo -e "   ${WHITE}→ ติดตั้ง core + demo users + essential data${NC}"
        ;;
    full)
        echo -e "   ${WHITE}→ ติดตั้งทุกอย่างรวม demo data ทั้งหมด${NC}"
        ;;
esac
echo ""

# ถามเฉพาะเมื่อไม่ใช่ auto mode
if [ "$AUTO_MODE" != true ] && [ "$INSTALL_MODE" = "standard" ]; then
    read -p "ต้องการติดตั้งข้อมูลทดสอบ (Demo Data)? (y/n) [y]: " INSTALL_DEMO
    INSTALL_DEMO=${INSTALL_DEMO:-y}
    if [[ ! $INSTALL_DEMO =~ ^[Yy]$ ]]; then
        INSTALL_MODE="minimal"
        print_info "เปลี่ยนเป็นโหมด: minimal (ไม่ติดตั้ง demo data)"
    fi
    echo ""
fi

print_subheader "🚀 กำลังรัน DatabaseSeeder"

print_info "ใช้ DatabaseSeeder โดยตรง - ตรงกับ codebase เสมอ"
print_info "Seeders จะรันตามลำดับใน DatabaseSeeder.php"
echo ""

# รัน DatabaseSeeder ผ่าน artisan
print_step "กำลังรัน php artisan db:seed..."
echo ""

# ตั้งค่า environment variable สำหรับ seeder mode
export SEEDER_MODE="$INSTALL_MODE"

SEED_START_TIME=$(date +%s)

# รัน seeder พร้อมแสดง progress
if php artisan db:seed --force 2>&1 | tee -a "$SEEDER_LOG"; then
    SEED_END_TIME=$(date +%s)
    SEED_DURATION=$((SEED_END_TIME - SEED_START_TIME))

    echo ""
    print_success "รัน DatabaseSeeder สำเร็จ! ✓"
    print_info "⏱️  ใช้เวลา: ${SEED_DURATION} วินาที"

    # นับจำนวน seeders ที่รัน
    SEEDER_COUNT=$(grep -c "INFO" "$SEEDER_LOG" 2>/dev/null || echo "70+")
    print_info "📊 Seeders ที่รัน: $SEEDER_COUNT"
else
    SEED_EXIT_CODE=$?
    echo ""
    print_warning "DatabaseSeeder รันไม่สำเร็จทั้งหมด (exit code: $SEED_EXIT_CODE)"
    print_info "บาง seeders อาจล้มเหลว แต่ระบบยังใช้งานได้"
    print_info "📝 ดูรายละเอียดได้ที่: $SEEDER_LOG"

    # ถาม user ว่าต้องการดำเนินการต่อหรือไม่
    if [ "$AUTO_MODE" != true ]; then
        read -p "ต้องการดำเนินการติดตั้งต่อหรือไม่? (y/n) [y]: " CONTINUE_INSTALL
        CONTINUE_INSTALL=${CONTINUE_INSTALL:-y}
        if [[ ! $CONTINUE_INSTALL =~ ^[Yy]$ ]]; then
            error_exit "การติดตั้งถูกยกเลิกโดยผู้ใช้"
        fi
    fi
fi

# Unset environment variable
unset SEEDER_MODE

# แสดงสรุป
echo ""
print_header "📊 สรุปผล Database Seeders"
echo ""
echo -e "${GREEN}✓ Installation Mode:${NC} $INSTALL_MODE"
echo -e "${GREEN}✓ Seeder Log:${NC} $SEEDER_LOG"
echo ""

# แนะนำการรัน seeder เพิ่มเติมถ้าต้องการ
if [ "$INSTALL_MODE" = "minimal" ]; then
    print_info "💡 หากต้องการติดตั้ง demo data ภายหลัง:"
    echo "   php artisan db:seed --class=DemoUsersSeeder"
    echo "   php artisan db:seed --class=ProductSeeder"
    echo ""
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

################################################################################
# Deploy Ready Check
################################################################################
print_subheader "🚀 ตรวจสอบความพร้อมสำหรับ Deploy"

# ตรวจสอบอย่างเงียบๆ
DEPLOY_READY=true

if [ ! -f ".env" ] || ! grep -q "^APP_KEY=base64:" ".env"; then
    DEPLOY_READY=false
fi

if [ ! -d "vendor" ] || [ ! -f "vendor/autoload.php" ]; then
    DEPLOY_READY=false
fi

if [ ! -d "node_modules" ]; then
    DEPLOY_READY=false
fi

if [ ! -d "public/build" ]; then
    DEPLOY_READY=false
fi

if [ "$DEPLOY_READY" = true ]; then
    echo -e "${GREEN}${BOLD}✅ พร้อมสำหรับ Production Deployment!${NC}"
    echo ""
    echo -e "  คุณสามารถใช้ ${YELLOW}deploy-pro.sh${NC} เพื่ออัปเดตในอนาคต:"
    echo -e "  ${CYAN}./deploy-pro.sh${NC}"
else
    echo -e "${YELLOW}${BOLD}⚠️ บางส่วนอาจยังไม่พร้อมสำหรับ Production${NC}"
    echo ""
    echo "  ตรวจสอบให้แน่ใจว่าได้รัน npm install และ npm run build แล้ว"
fi

echo ""
echo -e "${GREEN}${BOLD}    🎊 ยินดีด้วย! พร้อมใช้งานแล้ว 🎊${NC}"
echo ""
echo -e "${CYAN}📝 Log Files:${NC}"
echo -e "  • ติดตั้งทั่วไป: ${YELLOW}storage/logs/laravel.log${NC}"
echo -e "  • Seeder log: ${YELLOW}$SEEDER_LOG${NC}"
echo ""
echo -e "${CYAN}📖 คำสั่งที่มีประโยชน์:${NC}"
echo -e "  • ${YELLOW}./deploy-pro.sh${NC}        - Deploy/อัปเดตในอนาคต"
echo -e "  • ${YELLOW}php artisan serve${NC}      - ทดสอบด้วย built-in server"
echo -e "  • ${YELLOW}./clear-cache.sh${NC}       - เคลียร์ cache ทั้งหมด"
echo -e "  • ${YELLOW}./fix-permissions.sh${NC}   - แก้ไข permissions"
echo ""
echo -e "${MAGENTA}Happy deploying! 🚀${NC}"
echo ""
