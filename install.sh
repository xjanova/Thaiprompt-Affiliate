#!/bin/bash

# ============================================
# ThaiPrompt Marketplace - Quick Install Script
# ============================================
# Use this script for first-time installation
# ============================================

set -e  # Exit on error

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Functions
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

print_header() {
    echo ""
    echo -e "${BLUE}================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}================================${NC}"
    echo ""
}

# Check if running as root
if [ "$EUID" -eq 0 ]; then
    print_warning "Running as root. Consider using a non-root user."
fi

print_header "ThaiPrompt Marketplace - Quick Install"

# Get current directory
INSTALL_DIR=$(pwd)
print_info "Installation directory: $INSTALL_DIR"

# Step 1: Check PHP
print_header "Step 1/6: Checking Requirements"
print_info "Checking PHP version..."
if command -v php &> /dev/null; then
    PHP_VERSION=$(php -v | head -n 1 | cut -d " " -f 2 | cut -d "." -f 1,2)
    print_success "PHP $PHP_VERSION found"

    # Check if PHP >= 8.1
    if [ "$(printf '%s\n' "8.1" "$PHP_VERSION" | sort -V | head -n1)" != "8.1" ]; then
        print_error "PHP 8.1 or higher required. You have PHP $PHP_VERSION"
        exit 1
    fi
else
    print_error "PHP not found. Please install PHP 8.1 or higher"
    exit 1
fi

# Check Composer
print_info "Checking Composer..."
if command -v composer &> /dev/null; then
    print_success "Composer found"
else
    print_error "Composer not found. Please install Composer"
    echo "Install with: curl -sS https://getcomposer.org/installer | php && sudo mv composer.phar /usr/local/bin/composer"
    exit 1
fi

# Check Node.js
print_info "Checking Node.js..."
if command -v node &> /dev/null; then
    NODE_VERSION=$(node -v)
    print_success "Node.js $NODE_VERSION found"
else
    print_error "Node.js not found. Please install Node.js"
    exit 1
fi

# Check NPM
print_info "Checking NPM..."
if command -v npm &> /dev/null; then
    NPM_VERSION=$(npm -v)
    print_success "NPM $NPM_VERSION found"
else
    print_error "NPM not found. Please install NPM"
    exit 1
fi

print_success "All requirements met!"

# Step 2: Install Composer Dependencies
print_header "Step 2/6: Installing PHP Dependencies"
print_info "This may take a few minutes..."
composer install --optimize-autoloader --no-interaction
print_success "PHP dependencies installed"

# Step 3: Install NPM Dependencies
print_header "Step 3/6: Installing JavaScript Dependencies"
print_info "This may take a few minutes..."
npm install
print_success "JavaScript dependencies installed"

# Step 4: Build Assets
print_header "Step 4/6: Building Frontend Assets"
print_info "Building production assets..."
npm run build
print_success "Assets built successfully"

# Step 5: Set Permissions
print_header "Step 5/6: Setting File Permissions"
print_info "Setting proper permissions..."

# Check if www-data user exists (common for web servers)
if id "www-data" &>/dev/null; then
    WEB_USER="www-data"
    print_info "Found www-data user"
elif id "nginx" &>/dev/null; then
    WEB_USER="nginx"
    print_info "Found nginx user"
else
    WEB_USER=$(whoami)
    print_warning "Using current user: $WEB_USER"
fi

# Set ownership
if [ "$WEB_USER" != "$(whoami)" ]; then
    print_info "Setting ownership to $WEB_USER..."
    sudo chown -R $WEB_USER:$WEB_USER "$INSTALL_DIR"
fi

# Set permissions for storage and cache
chmod -R 775 "$INSTALL_DIR/storage"
chmod -R 775 "$INSTALL_DIR/bootstrap/cache"

print_success "Permissions set successfully"

# Step 6: Create .env if doesn't exist
print_header "Step 6/6: Environment Configuration"
if [ ! -f "$INSTALL_DIR/.env" ]; then
    print_info "Creating .env file..."
    cp "$INSTALL_DIR/.env.example" "$INSTALL_DIR/.env"
    print_success ".env file created"
else
    print_info ".env file already exists"
fi

# Check if APP_KEY is set
if ! grep -q "APP_KEY=base64:" "$INSTALL_DIR/.env"; then
    print_info "Generating application key..."
    php artisan key:generate --force
    print_success "Application key generated"
fi

# Final Summary
print_header "Installation Complete!"

echo ""
echo -e "${GREEN}✅ ThaiPrompt Marketplace is ready!${NC}"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo -e "${BLUE}📋 Next Steps:${NC}"
echo ""
echo "1. Configure your web server (Nginx/Apache)"
echo "   Example config: See DEPLOYMENT.md"
echo ""
echo "2. Visit your website in a browser"
echo "   → You will see the Setup Wizard automatically"
echo ""
echo "3. Follow the Setup Wizard steps:"
echo "   • Check system requirements"
echo "   • Configure database connection"
echo "   • Create admin account"
echo "   • Done!"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo -e "${YELLOW}📚 Documentation:${NC}"
echo "   • Quick Start: SETUP_WIZARD.md"
echo "   • Full Guide: INSTALLATION_GUIDE.md"
echo "   • Deployment: DEPLOYMENT.md"
echo ""
echo -e "${YELLOW}🆘 Need Help?${NC}"
echo "   • Email: support@thaiprompt.com"
echo "   • Issues: https://github.com/xjanova/Thaiprompt-Affiliate/issues"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

print_info "Installation directory: $INSTALL_DIR"
print_info "Web server user: $WEB_USER"
echo ""
