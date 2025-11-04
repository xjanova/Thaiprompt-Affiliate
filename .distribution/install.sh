#!/bin/bash

###############################################################################
# TP-Affiliate Installation Script
# Version: 1.0.0
# Description: Automated installer for TP-Affiliate platform
# Repository: https://github.com/xjanova/TP-Affiliate
###############################################################################

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
REQUIRED_PHP_VERSION="8.1.0"
REQUIRED_MYSQL_VERSION="5.7.0"
REQUIRED_MARIADB_VERSION="10.3.0"
LICENSE_API_URL="https://xman4289.com/wp-json/tp-license/v1"
REPO_URL="https://github.com/xjanova/TP-Affiliate.git"
INSTALL_DIR=$(pwd)

# Functions
print_header() {
    echo -e "${BLUE}"
    echo "╔════════════════════════════════════════════════════════════╗"
    echo "║                                                            ║"
    echo "║         TP-Affiliate Installation Wizard                  ║"
    echo "║         Version 1.0.0                                      ║"
    echo "║                                                            ║"
    echo "╚════════════════════════════════════════════════════════════╝"
    echo -e "${NC}"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ $1${NC}"
}

print_step() {
    echo -e "\n${BLUE}━━━ $1 ━━━${NC}\n"
}

check_root() {
    if [ "$EUID" -eq 0 ]; then
        print_error "Please do not run this script as root"
        print_info "Run as the web server user (e.g., www-data, apache)"
        exit 1
    fi
}

check_php_version() {
    print_step "Step 1/10: Checking PHP Version"

    if ! command -v php &> /dev/null; then
        print_error "PHP is not installed"
        echo "Please install PHP $REQUIRED_PHP_VERSION or higher"
        exit 1
    fi

    PHP_VERSION=$(php -r "echo PHP_VERSION;")
    print_info "Installed PHP version: $PHP_VERSION"

    if php -r "exit(version_compare(PHP_VERSION, '$REQUIRED_PHP_VERSION', '>=') ? 0 : 1);"; then
        print_success "PHP version meets requirements"
    else
        print_error "PHP version $REQUIRED_PHP_VERSION or higher is required"
        echo "Current version: $PHP_VERSION"
        exit 1
    fi
}

check_php_extensions() {
    print_step "Step 2/10: Checking PHP Extensions"

    REQUIRED_EXTENSIONS=("pdo" "pdo_mysql" "mbstring" "openssl" "json" "curl" "gd" "xml" "zip" "fileinfo")
    MISSING_EXTENSIONS=()

    for ext in "${REQUIRED_EXTENSIONS[@]}"; do
        if php -m | grep -qi "^$ext$"; then
            print_success "$ext extension is installed"
        else
            MISSING_EXTENSIONS+=("$ext")
            print_error "$ext extension is NOT installed"
        fi
    done

    if [ ${#MISSING_EXTENSIONS[@]} -gt 0 ]; then
        echo ""
        print_error "Missing PHP extensions: ${MISSING_EXTENSIONS[*]}"
        echo "Install them with: sudo apt-get install php-${MISSING_EXTENSIONS[*]}"
        exit 1
    fi

    print_success "All required PHP extensions are installed"
}

check_composer() {
    print_step "Step 3/10: Checking Composer"

    if ! command -v composer &> /dev/null; then
        print_error "Composer is not installed"
        echo "Install Composer from: https://getcomposer.org/"
        exit 1
    fi

    COMPOSER_VERSION=$(composer --version | grep -oP '\d+\.\d+\.\d+' | head -1)
    print_info "Composer version: $COMPOSER_VERSION"
    print_success "Composer is installed"
}

check_database() {
    print_step "Step 4/10: Database Configuration"

    read -p "Database host [localhost]: " DB_HOST
    DB_HOST=${DB_HOST:-localhost}

    read -p "Database port [3306]: " DB_PORT
    DB_PORT=${DB_PORT:-3306}

    read -p "Database name: " DB_NAME
    while [ -z "$DB_NAME" ]; do
        print_error "Database name is required"
        read -p "Database name: " DB_NAME
    done

    read -p "Database username: " DB_USER
    while [ -z "$DB_USER" ]; do
        print_error "Database username is required"
        read -p "Database username: " DB_USER
    done

    read -sp "Database password: " DB_PASS
    echo

    # Test database connection
    print_info "Testing database connection..."

    if mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" -p"$DB_PASS" -e "USE $DB_NAME" 2>/dev/null; then
        print_success "Database connection successful"
    else
        print_error "Cannot connect to database"
        echo "Please check your credentials and try again"
        exit 1
    fi
}

check_license() {
    print_step "Step 5/10: License Verification"

    read -p "Enter your license key (format: XXXX-XXXX-XXXX-XXXX): " LICENSE_KEY
    while [ -z "$LICENSE_KEY" ]; do
        print_error "License key is required"
        read -p "Enter your license key: " LICENSE_KEY
    done

    # Get server IP
    SERVER_IP=$(curl -s https://api.ipify.org)
    if [ -z "$SERVER_IP" ]; then
        SERVER_IP=$(hostname -I | awk '{print $1}')
    fi

    print_info "Server IP: $SERVER_IP"
    print_info "Verifying license with server..."

    # Check IP whitelist
    RESPONSE=$(curl -s -X POST "$LICENSE_API_URL/check-ip" \
        -H "Content-Type: application/json" \
        -d "{\"license_key\":\"$LICENSE_KEY\",\"ip\":\"$SERVER_IP\"}")

    ALLOWED=$(echo "$RESPONSE" | grep -o '"allowed":true' || echo "")

    if [ -z "$ALLOWED" ]; then
        print_error "IP address not authorized for this license"
        echo ""
        echo "Your IP: $SERVER_IP"
        echo "This IP address is not in the whitelist for your license."
        echo ""
        echo "Please contact support to add your IP to the whitelist:"
        echo "→ Email: support@xman4289.com"
        echo "→ Include your license key and server IP"
        exit 1
    fi

    print_success "IP address is authorized"
    print_success "License verification successful"
}

download_application() {
    print_step "Step 6/10: Downloading Application"

    if [ -d "$INSTALL_DIR/app" ]; then
        print_warning "Application directory already exists"
        read -p "Overwrite? (y/N): " OVERWRITE
        if [[ ! $OVERWRITE =~ ^[Yy]$ ]]; then
            print_info "Installation cancelled"
            exit 0
        fi
    fi

    print_info "Cloning from repository..."

    # Clone specific version/tag
    if git clone --depth 1 "$REPO_URL" "$INSTALL_DIR/tp-affiliate-tmp"; then
        print_success "Repository cloned successfully"

        # Move files to current directory
        mv "$INSTALL_DIR/tp-affiliate-tmp/"* "$INSTALL_DIR/" 2>/dev/null || true
        mv "$INSTALL_DIR/tp-affiliate-tmp/."* "$INSTALL_DIR/" 2>/dev/null || true
        rm -rf "$INSTALL_DIR/tp-affiliate-tmp"
    else
        print_error "Failed to clone repository"
        exit 1
    fi
}

create_env_file() {
    print_step "Step 7/10: Configuring Environment"

    if [ -f "$INSTALL_DIR/.env" ]; then
        print_warning ".env file already exists"
        mv "$INSTALL_DIR/.env" "$INSTALL_DIR/.env.backup.$(date +%s)"
        print_info "Backed up existing .env file"
    fi

    cp "$INSTALL_DIR/.env.example" "$INSTALL_DIR/.env"

    # Generate APP_KEY
    print_info "Generating application key..."
    php artisan key:generate --force

    # Configure database
    sed -i "s/DB_HOST=.*/DB_HOST=$DB_HOST/" "$INSTALL_DIR/.env"
    sed -i "s/DB_PORT=.*/DB_PORT=$DB_PORT/" "$INSTALL_DIR/.env"
    sed -i "s/DB_DATABASE=.*/DB_DATABASE=$DB_NAME/" "$INSTALL_DIR/.env"
    sed -i "s/DB_USERNAME=.*/DB_USERNAME=$DB_USER/" "$INSTALL_DIR/.env"
    sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=$DB_PASS/" "$INSTALL_DIR/.env"

    # Configure license
    INSTALLATION_ID=$(cat /proc/sys/kernel/random/uuid)
    sed -i "s/LICENSE_KEY=.*/LICENSE_KEY=$LICENSE_KEY/" "$INSTALL_DIR/.env"
    sed -i "s/LICENSE_INSTALLATION_ID=.*/LICENSE_INSTALLATION_ID=$INSTALLATION_ID/" "$INSTALL_DIR/.env"

    # Set APP_ENV to production
    sed -i "s/APP_ENV=.*/APP_ENV=production/" "$INSTALL_DIR/.env"
    sed -i "s/APP_DEBUG=.*/APP_DEBUG=false/" "$INSTALL_DIR/.env"

    print_success "Environment configured"
}

install_dependencies() {
    print_step "Step 8/10: Installing Dependencies"

    print_info "Installing PHP dependencies..."
    composer install --no-dev --optimize-autoloader --no-interaction

    if [ -f "$INSTALL_DIR/package.json" ]; then
        if command -v npm &> /dev/null; then
            print_info "Installing Node dependencies..."
            npm install --production

            print_info "Building assets..."
            npm run build
        else
            print_warning "npm not found, skipping Node dependencies"
        fi
    fi

    print_success "Dependencies installed"
}

setup_database() {
    print_step "Step 9/10: Setting Up Database"

    print_info "Running database migrations..."
    php artisan migrate --force

    read -p "Do you want to seed demo data? (y/N): " SEED_DATA
    if [[ $SEED_DATA =~ ^[Yy]$ ]]; then
        print_info "Seeding demo data..."
        php artisan db:seed --force
        print_success "Demo data seeded"
    else
        print_info "Skipping demo data"
    fi

    print_success "Database setup complete"
}

finalize_installation() {
    print_step "Step 10/10: Finalizing Installation"

    # Set proper permissions
    print_info "Setting file permissions..."
    chmod -R 755 "$INSTALL_DIR/storage"
    chmod -R 755 "$INSTALL_DIR/bootstrap/cache"

    # Create storage link
    php artisan storage:link

    # Cache configuration
    print_info "Caching configuration..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

    # Optimize
    php artisan optimize

    print_success "Installation finalized"
}

print_completion() {
    echo ""
    echo -e "${GREEN}"
    echo "╔════════════════════════════════════════════════════════════╗"
    echo "║                                                            ║"
    echo "║         ✓ Installation Completed Successfully!            ║"
    echo "║                                                            ║"
    echo "╚════════════════════════════════════════════════════════════╝"
    echo -e "${NC}"
    echo ""
    echo -e "${BLUE}Next Steps:${NC}"
    echo ""
    echo "1. Configure your web server (Nginx/Apache) to point to:"
    echo "   → $INSTALL_DIR/public"
    echo ""
    echo "2. Visit your domain in a browser"
    echo ""
    echo "3. Complete the first-time setup wizard"
    echo "   → Set admin password"
    echo "   → Configure site settings"
    echo ""
    echo "4. Activate your license:"
    echo "   → php artisan license:activate $LICENSE_KEY"
    echo ""
    echo -e "${YELLOW}Important:${NC}"
    echo "→ Keep your license key safe"
    echo "→ Backup your database regularly"
    echo "→ Keep the application up to date"
    echo ""
    echo -e "${GREEN}Support:${NC}"
    echo "→ Documentation: https://github.com/xjanova/TP-Affiliate/wiki"
    echo "→ Email: support@xman4289.com"
    echo ""
}

# Main installation flow
main() {
    print_header

    # Pre-installation checks
    check_root
    check_php_version
    check_php_extensions
    check_composer

    # Configuration
    check_database
    check_license

    # Installation
    download_application
    create_env_file
    install_dependencies
    setup_database
    finalize_installation

    # Completion
    print_completion
}

# Run installation
main "$@"
