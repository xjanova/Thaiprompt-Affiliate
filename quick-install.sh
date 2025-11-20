#!/bin/bash

# ================================================================
# TP-Affiliate Quick Installation Script
# ================================================================
# โคลนโปรเจคและติดตั้งอัตโนมัติในโฟลเดอร์ปัจจุบัน
#
# การใช้งาน:
#   curl -fsSL https://raw.githubusercontent.com/xjanova/Thaiprompt-Affiliate/claude/Main/quick-install.sh | bash
#
# หรือ:
#   wget -qO- https://raw.githubusercontent.com/xjanova/Thaiprompt-Affiliate/claude/Main/quick-install.sh | bash
#
# หรือ clone มาแล้ว:
#   bash quick-install.sh
# ================================================================

set -e  # Exit on error

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
MAGENTA='\033[0;35m'
NC='\033[0m' # No Color

# Print functions
print_header() {
    echo ""
    echo -e "${MAGENTA}════════════════════════════════════════════════════════════════${NC}"
    echo -e "${MAGENTA}  $1${NC}"
    echo -e "${MAGENTA}════════════════════════════════════════════════════════════════${NC}"
    echo ""
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

error_exit() {
    print_error "$1"
    exit 1
}

# Banner
clear
echo ""
echo -e "${GREEN}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║${NC}    ${MAGENTA}██╗  ██╗███╗   ███╗ █████╗ ███╗   ██╗${NC}                      ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}    ${MAGENTA}╚██╗██╔╝████╗ ████║██╔══██╗████╗  ██║${NC}                      ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}     ${MAGENTA}╚███╔╝ ██╔████╔██║███████║██╔██╗ ██║${NC}                      ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}     ${MAGENTA}██╔██╗ ██║╚██╔╝██║██╔══██║██║╚██╗██║${NC}                      ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}    ${MAGENTA}██╔╝ ██╗██║ ╚═╝ ██║██║  ██║██║ ╚████║${NC}                      ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}    ${MAGENTA}╚═╝  ╚═╝╚═╝     ╚═╝╚═╝  ╚═╝╚═╝  ╚═══╝${NC}                      ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}                                                                ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}    ${BLUE}🚀 TP-Affiliate Quick Installation${NC}                        ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}    ${YELLOW}⚡ Clone & Install in One Step${NC}                          ${GREEN}║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${BLUE}This script will clone and install TP-Affiliate automatically${NC}"
echo -e "${BLUE}Repository: https://github.com/xjanova/Thaiprompt-Affiliate${NC}"
echo -e "${BLUE}Branch: claude/Main${NC}"
echo ""

# Configuration
REPO_URL="https://github.com/xjanova/Thaiprompt-Affiliate.git"
BRANCH="claude/Main"
DEFAULT_DIR="thaiprompt-affiliate"

# ========================================
# STEP 1: Check Requirements
# ========================================
print_header "Step 1: Checking System Requirements"

# Check Git
if ! command -v git &> /dev/null; then
    error_exit "Git is not installed. Please install Git first: https://git-scm.com/"
fi
print_success "Git: $(git --version)"

# Check PHP
if ! command -v php &> /dev/null; then
    error_exit "PHP is not installed. Please install PHP 8.1+ first."
fi
PHP_VERSION=$(php -r 'echo PHP_VERSION;')
print_success "PHP: $PHP_VERSION"

# Check Composer
if ! command -v composer &> /dev/null; then
    print_warning "Composer is not installed."
    echo ""
    read -p "Do you want to install Composer automatically? (y/n) [y]: " -n 1 -r INSTALL_COMPOSER
    echo ""
    INSTALL_COMPOSER=${INSTALL_COMPOSER:-y}

    if [[ $INSTALL_COMPOSER =~ ^[Yy]$ ]]; then
        print_info "Installing Composer..."
        curl -sS https://getcomposer.org/installer | php
        sudo mv composer.phar /usr/local/bin/composer
        print_success "Composer installed"
    else
        error_exit "Composer is required. Install it from: https://getcomposer.org/"
    fi
fi
COMPOSER_VERSION=$(composer --version --no-ansi 2>/dev/null | grep -oP '\d+\.\d+\.\d+' | head -1)
print_success "Composer: $COMPOSER_VERSION"

# Check MySQL client
if ! command -v mysql &> /dev/null; then
    print_warning "MySQL client is not installed (optional for DB connection test)"
fi

echo ""
print_success "All system requirements met!"
echo ""

# ========================================
# STEP 2: Choose Installation Directory
# ========================================
print_header "Step 2: Choose Installation Directory"

echo "Where do you want to install TP-Affiliate?"
echo ""
read -p "Installation directory [$DEFAULT_DIR]: " INSTALL_DIR
INSTALL_DIR=${INSTALL_DIR:-$DEFAULT_DIR}

# Check if directory exists
if [ -d "$INSTALL_DIR" ]; then
    print_warning "Directory '$INSTALL_DIR' already exists!"
    read -p "Do you want to remove it and continue? (y/n) [n]: " -n 1 -r REMOVE_DIR
    echo ""

    if [[ $REMOVE_DIR =~ ^[Yy]$ ]]; then
        print_info "Removing existing directory..."
        rm -rf "$INSTALL_DIR"
        print_success "Directory removed"
    else
        error_exit "Installation cancelled. Please choose a different directory."
    fi
fi

# ========================================
# STEP 3: Clone Repository
# ========================================
print_header "Step 3: Cloning Repository"

print_info "Cloning from: $REPO_URL"
print_info "Branch: $BRANCH"
echo ""

if git clone -b "$BRANCH" "$REPO_URL" "$INSTALL_DIR"; then
    print_success "Repository cloned successfully!"
else
    error_exit "Failed to clone repository. Please check your internet connection."
fi

# Enter directory
cd "$INSTALL_DIR" || error_exit "Failed to enter directory: $INSTALL_DIR"
print_info "Working directory: $(pwd)"
echo ""

# ========================================
# STEP 4: Run Installation
# ========================================
print_header "Step 4: Running Installation Script"

if [ ! -f "install.sh" ]; then
    error_exit "install.sh not found! Repository may be corrupted."
fi

# Make install.sh executable
chmod +x install.sh

print_info "Starting interactive installation..."
print_warning "You will be asked to provide configuration details."
echo ""

# Run install.sh
if bash install.sh; then
    print_success "Installation completed successfully!"
else
    print_error "Installation failed!"
    echo ""
    print_info "You can retry the installation by running:"
    echo ""
    echo -e "  ${YELLOW}cd $(pwd)${NC}"
    echo -e "  ${YELLOW}./install.sh${NC}"
    echo ""
    exit 1
fi

# ========================================
# Post-Installation
# ========================================
print_header "🎉 Installation Complete!"

echo ""
echo -e "${GREEN}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║${NC}  ${BLUE}TP-Affiliate has been installed successfully!${NC}                ${GREEN}║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${BLUE}📂 Installation directory:${NC}"
echo -e "   $(pwd)"
echo ""
echo -e "${BLUE}🚀 Quick Start:${NC}"
echo ""
echo "  1. Test with built-in server:"
echo -e "     ${YELLOW}cd $(pwd)${NC}"
echo -e "     ${YELLOW}php artisan serve${NC}"
echo -e "     Visit: ${GREEN}http://localhost:8000${NC}"
echo ""
echo "  2. Configure web server:"
echo "     Point DocumentRoot to: $(pwd)/public"
echo ""
echo "  3. View documentation:"
echo -e "     ${YELLOW}cat README.md${NC}"
echo ""
echo -e "${GREEN}✨ Happy coding! 🎊${NC}"
echo ""
