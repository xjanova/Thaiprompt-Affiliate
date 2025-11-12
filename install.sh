#!/bin/bash

# TP-Affiliate Installation Script
# One-time setup for first installation

set -e  # Exit on error

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
MAGENTA='\033[0;35m'
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
    echo -e "${MAGENTA}════════════════════════════════════════${NC}"
    echo -e "${MAGENTA}  $1${NC}"
    echo -e "${MAGENTA}════════════════════════════════════════${NC}"
    echo ""
}

error_exit() {
    print_error "$1"
    exit 1
}

# Header
echo ""
echo -e "${GREEN}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║${NC}    ${MAGENTA}██╗  ██╗███╗   ███╗ █████╗ ███╗   ██╗${NC}                      ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}    ${MAGENTA}╚██╗██╔╝████╗ ████║██╔══██╗████╗  ██║${NC}                      ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}     ${MAGENTA}╚███╔╝ ██╔████╔██║███████║██╔██╗ ██║${NC}                      ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}     ${MAGENTA}██╔██╗ ██║╚██╔╝██║██╔══██║██║╚██╗██║${NC}                      ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}    ${MAGENTA}██╔╝ ██╗██║ ╚═╝ ██║██║  ██║██║ ╚████║${NC}                      ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}    ${MAGENTA}╚═╝  ╚═╝╚═╝     ╚═╝╚═╝  ╚═╝╚═╝  ╚═══╝${NC}                      ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}                                                                ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}    ${BLUE}🚀 TP-Affiliate Installation System${NC}                       ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}    ${YELLOW}⚡ First-Time Setup & Configuration${NC}                      ${GREEN}║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${BLUE}This script will guide you through the initial setup of TP-Affiliate${NC}"
echo -e "${BLUE}Estimated time: 5-10 minutes${NC}"
echo ""

# Check if already installed
if [ -f "storage/app/.setup_completed" ]; then
    print_warning "TP-Affiliate is already installed!"
    echo ""
    read -p "Do you want to reinstall? This will create a new .env file (y/n): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "Installation cancelled."
        exit 0
    fi
    echo ""
fi

# ========================================
# STEP 1: System Requirements Check
# ========================================
print_header "Step 1: System Requirements Check"

# Check PHP version
PHP_VERSION=$(php -r 'echo PHP_VERSION;')
REQUIRED_PHP="8.1.0"

print_info "Checking PHP version..."
if php -r "exit(version_compare(PHP_VERSION, '$REQUIRED_PHP', '>=') ? 0 : 1);"; then
    print_success "PHP version: $PHP_VERSION (>= $REQUIRED_PHP required)"
else
    error_exit "PHP version $PHP_VERSION is too old. Minimum required: $REQUIRED_PHP"
fi

# Check required PHP extensions
print_info "Checking PHP extensions..."
REQUIRED_EXTENSIONS=(bcmath ctype json mbstring openssl pdo pdo_mysql tokenizer xml curl fileinfo gd zip)
MISSING_EXTENSIONS=()

for ext in "${REQUIRED_EXTENSIONS[@]}"; do
    if php -r "exit(extension_loaded('$ext') ? 0 : 1);"; then
        print_success "$ext extension installed"
    else
        MISSING_EXTENSIONS+=("$ext")
        print_error "$ext extension is missing"
    fi
done

if [ ${#MISSING_EXTENSIONS[@]} -ne 0 ]; then
    error_exit "Missing required PHP extensions: ${MISSING_EXTENSIONS[*]}"
fi

# Check Composer
print_info "Checking Composer..."
if command -v composer &> /dev/null; then
    COMPOSER_VERSION=$(composer --version --no-ansi 2>/dev/null | grep -oP '\d+\.\d+\.\d+' | head -1)
    print_success "Composer installed: $COMPOSER_VERSION"
else
    error_exit "Composer is not installed. Please install Composer first: https://getcomposer.org/"
fi

# Check Git
print_info "Checking Git..."
if command -v git &> /dev/null; then
    GIT_VERSION=$(git --version | grep -oP '\d+\.\d+\.\d+' | head -1)
    print_success "Git installed: $GIT_VERSION"
else
    print_warning "Git is not installed. Some features may not work."
fi

# Check if in git repository
if [ ! -d ".git" ]; then
    print_warning "Not a git repository. You may want to initialize git for version control."
fi

echo ""
print_success "All system requirements met!"
echo ""

# ========================================
# STEP 2: Configuration Setup
# ========================================
print_header "Step 2: Application Configuration"

echo "Please provide the following information:"
echo ""

# Application Name
read -p "Application Name [TP-Affiliate]: " APP_NAME
APP_NAME=${APP_NAME:-TP-Affiliate}

# Application URL
read -p "Application URL (e.g., https://example.com): " APP_URL
while [ -z "$APP_URL" ]; do
    print_error "Application URL is required!"
    read -p "Application URL: " APP_URL
done

# Application Environment
echo ""
echo "Select environment:"
echo "  1) Production (recommended)"
echo "  2) Local/Development"
read -p "Environment [1]: " ENV_CHOICE
ENV_CHOICE=${ENV_CHOICE:-1}

if [ "$ENV_CHOICE" == "2" ]; then
    APP_ENV="local"
    APP_DEBUG="true"
else
    APP_ENV="production"
    APP_DEBUG="false"
fi

print_success "Environment: $APP_ENV"

# ========================================
# STEP 3: Database Configuration
# ========================================
print_header "Step 3: Database Configuration"

read -p "Database Host [127.0.0.1]: " DB_HOST
DB_HOST=${DB_HOST:-127.0.0.1}

read -p "Database Port [3306]: " DB_PORT
DB_PORT=${DB_PORT:-3306}

read -p "Database Name: " DB_DATABASE
while [ -z "$DB_DATABASE" ]; do
    print_error "Database name is required!"
    read -p "Database Name: " DB_DATABASE
done

read -p "Database Username [root]: " DB_USERNAME
DB_USERNAME=${DB_USERNAME:-root}

read -sp "Database Password: " DB_PASSWORD
echo ""

# Test database connection
print_info "Testing database connection..."
if mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" -e "SELECT 1;" &>/dev/null; then
    print_success "Database connection successful!"
    
    # Create database if not exists
    print_info "Creating database if not exists..."
    mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS \`$DB_DATABASE\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null || {
        print_warning "Could not create database. Make sure it exists."
    }
else
    error_exit "Database connection failed! Please check your credentials."
fi

# ========================================
# STEP 4: Super Admin Account
# ========================================
print_header "Step 4: Super Admin Account"

echo "Create your Super Admin account:"
echo ""

read -p "Admin Name: " ADMIN_NAME
while [ -z "$ADMIN_NAME" ]; do
    print_error "Admin name is required!"
    read -p "Admin Name: " ADMIN_NAME
done

read -p "Admin Email: " ADMIN_EMAIL
while [ -z "$ADMIN_EMAIL" ]; do
    print_error "Admin email is required!"
    read -p "Admin Email: " ADMIN_EMAIL
done

# Validate email format
if [[ ! "$ADMIN_EMAIL" =~ ^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$ ]]; then
    error_exit "Invalid email format!"
fi

read -sp "Admin Password (min 8 characters): " ADMIN_PASSWORD
echo ""
while [ ${#ADMIN_PASSWORD} -lt 8 ]; do
    print_error "Password must be at least 8 characters!"
    read -sp "Admin Password: " ADMIN_PASSWORD
    echo ""
done

read -sp "Confirm Password: " ADMIN_PASSWORD_CONFIRM
echo ""
while [ "$ADMIN_PASSWORD" != "$ADMIN_PASSWORD_CONFIRM" ]; do
    print_error "Passwords do not match!"
    read -sp "Admin Password: " ADMIN_PASSWORD
    echo ""
    read -sp "Confirm Password: " ADMIN_PASSWORD_CONFIRM
    echo ""
done

print_success "Admin account configured"

# ========================================
# STEP 5: Create .env File
# ========================================
print_header "Step 5: Creating Environment File"

if [ -f ".env" ]; then
    print_info "Backing up existing .env to .env.backup..."
    cp .env .env.backup
fi

if [ ! -f ".env.example" ]; then
    error_exit ".env.example not found!"
fi

print_info "Creating .env file from .env.example..."
cp .env.example .env
print_success ".env file created"

# Update .env with user inputs
print_info "Configuring .env file..."

# Helper function to update .env
update_env() {
    local key=$1
    local value=$2
    
    # Escape special characters
    value=$(echo "$value" | sed 's/[\/&]/\\&/g')
    
    if grep -q "^${key}=" .env; then
        sed -i "s|^${key}=.*|${key}=${value}|" .env
    else
        echo "${key}=${value}" >> .env
    fi
}

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

print_success ".env file configured"

# Generate APP_KEY
print_info "Generating application key..."
mkdir -p bootstrap/cache
php artisan key:generate --force
print_success "Application key generated"

# ========================================
# STEP 6: Install Dependencies
# ========================================
print_header "Step 6: Installing Dependencies"

print_info "Creating required directories..."
mkdir -p storage/{app,framework,logs}
mkdir -p storage/framework/{cache,sessions,views}
mkdir -p storage/app/{public,private}
mkdir -p bootstrap/cache
chmod -R 775 storage bootstrap/cache
print_success "Directories created"

print_info "Installing Composer dependencies..."
echo "This may take a few minutes..."
if composer install --no-dev --optimize-autoloader --no-interaction; then
    print_success "Dependencies installed"
else
    error_exit "Failed to install dependencies"
fi

# ========================================
# STEP 7: Database Setup
# ========================================
print_header "Step 7: Database Setup"

# Clear config cache
print_info "Clearing configuration cache..."
php artisan config:clear

print_info "Running database migrations..."
if php artisan migrate --force; then
    print_success "Database migrations completed"
else
    error_exit "Database migration failed"
fi

print_info "Running database seeders..."
if php artisan db:seed --force; then
    print_success "Database seeders completed"
else
    print_warning "Database seeders failed (continuing anyway)"
fi

# ========================================
# STEP 8: Create Super Admin
# ========================================
print_header "Step 8: Creating Super Admin Account"

print_info "Creating super admin user..."

# Create super admin using tinker
php artisan tinker --execute="
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

echo 'Super admin created successfully!';
"

if [ $? -eq 0 ]; then
    print_success "Super admin account created"
else
    error_exit "Failed to create super admin account"
fi

# Create default settings
print_info "Creating default settings..."
php artisan tinker --execute="
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

echo 'Default settings created!';
"

if [ $? -eq 0 ]; then
    print_success "Default settings created"
else
    print_warning "Failed to create default settings (continuing anyway)"
fi

# ========================================
# STEP 9: Finalization
# ========================================
print_header "Step 9: Finalization"

# Create storage link
print_info "Creating storage symlink..."
php artisan storage:link --force
print_success "Storage symlink created"

# Cache configuration
print_info "Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
print_success "Configuration cached"

# Create setup completed flag
print_info "Marking installation as completed..."
mkdir -p storage/app
echo "$(date)" > storage/app/.setup_completed
print_success "Installation marked as completed"

# ========================================
# Installation Complete
# ========================================
print_header "✅ Installation Complete!"

echo ""
echo "📊 Installation Summary:"
echo ""
echo "  Application:    ${BLUE}$APP_NAME${NC}"
echo "  URL:            ${BLUE}$APP_URL${NC}"
echo "  Environment:    ${BLUE}$APP_ENV${NC}"
echo "  Database:       ${BLUE}$DB_DATABASE@$DB_HOST${NC}"
echo "  Admin Email:    ${BLUE}$ADMIN_EMAIL${NC}"
echo ""
echo "🎉 ${GREEN}Congratulations!${NC} TP-Affiliate has been installed successfully."
echo ""
echo "📋 Next Steps:"
echo ""
echo "  ${BLUE}1.${NC} Configure your web server (Nginx/Apache) to point to the ${YELLOW}public/${NC} directory"
echo "  ${BLUE}2.${NC} Set up your GitHub personal access token for deployments:"
echo "      ${GREEN}→${NC} Go to GitHub Settings → Developer Settings → Personal Access Tokens"
echo "      ${GREEN}→${NC} Generate new token with 'repo' permissions"
echo "      ${GREEN}→${NC} Keep it safe for use with deploy.sh"
echo "  ${BLUE}3.${NC} Configure additional settings in ${YELLOW}.env${NC} file if needed:"
echo "      ${GREEN}→${NC} Email settings (MAIL_*, GMAIL_*, SMTP_*)"
echo "      ${GREEN}→${NC} Cloudflare Turnstile (CLOUDFLARE_TURNSTILE_*)"
echo "      ${GREEN}→${NC} Google Translate API (GOOGLE_TRANSLATE_*)"
echo "  ${BLUE}4.${NC} Access your application:"
echo "      ${GREEN}→${NC} Frontend: ${YELLOW}$APP_URL${NC}"
echo "      ${GREEN}→${NC} Admin Panel: ${YELLOW}$APP_URL/admin${NC}"
echo "  ${BLUE}5.${NC} Login with your admin credentials:"
echo "      ${GREEN}→${NC} Email: ${YELLOW}$ADMIN_EMAIL${NC}"
echo "      ${GREEN}→${NC} Password: (the password you set)"
echo ""
echo "📖 Documentation:"
echo "  ${GREEN}→${NC} Read INSTALLATION.md for detailed deployment guide"
echo "  ${GREEN}→${NC} Read README.md for general information"
echo ""
echo "🚀 Deployment:"
echo "  ${GREEN}→${NC} For future updates, use: ${YELLOW}./deploy.sh${NC}"
echo "  ${GREEN}→${NC} Make sure to set up your git remote first"
echo ""
echo "${GREEN}Happy deploying! 🎊${NC}"
echo ""
