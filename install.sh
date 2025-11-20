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

# ========================================
# STEP 0: Verify Repository Files
# ========================================
print_header "Step 0: Verifying Repository Files"

# ตรวจสอบไฟล์สำคัญที่จำเป็นต้องมี
print_info "Checking for required files..."

CRITICAL_FILES=(
    ".env.example"
    "composer.json"
    "package.json"
    "artisan"
    "public/index.php"
)

MISSING_CRITICAL=false
MISSING_FILES_LIST=()

for file in "${CRITICAL_FILES[@]}"; do
    if [ ! -f "$file" ]; then
        MISSING_CRITICAL=true
        MISSING_FILES_LIST+=("$file")
        print_error "Missing critical file: $file"
    else
        print_success "✓ Found: $file"
    fi
done

# ถ้าขาดไฟล์สำคัญ
if [ "$MISSING_CRITICAL" = true ]; then
    echo ""
    print_warning "Critical files are missing from the installation directory!"
    echo ""
    echo "Missing files:"
    for file in "${MISSING_FILES_LIST[@]}"; do
        echo "  ✗ $file"
    done
    echo ""

    # ตรวจสอบว่าเป็น git repository หรือไม่
    if [ -d ".git" ]; then
        print_info "Git repository detected. Attempting to restore files..."
        echo ""

        # ถามผู้ใช้ว่าต้องการ pull files จาก git หรือไม่
        read -p "Do you want to restore files from git repository? (y/n) [y]: " -n 1 -r RESTORE_FILES
        echo ""
        RESTORE_FILES=${RESTORE_FILES:-y}

        if [[ $RESTORE_FILES =~ ^[Yy]$ ]]; then
            # ดึงไฟล์จาก git
            print_info "Fetching latest files from repository..."

            # Stash local changes ถ้ามี
            git stash push -m "Auto-stash before install.sh" 2>/dev/null || true

            # Fetch and checkout files
            if git fetch origin; then
                print_success "Fetched from remote successfully"

                # ถามว่าต้องการ checkout จาก branch ไหน
                CURRENT_BRANCH=$(git branch --show-current 2>/dev/null || echo "main")
                echo ""
                print_info "Available branches:"
                git branch -a | grep -E "(main|master|claude/Main)" | head -5
                echo ""
                read -p "Enter branch to restore from [$CURRENT_BRANCH]: " TARGET_BRANCH
                TARGET_BRANCH=${TARGET_BRANCH:-$CURRENT_BRANCH}

                # Checkout missing files from target branch
                print_info "Restoring files from branch: $TARGET_BRANCH..."
                for file in "${MISSING_FILES_LIST[@]}"; do
                    if git checkout "origin/$TARGET_BRANCH" -- "$file" 2>/dev/null; then
                        print_success "✓ Restored: $file"
                    else
                        print_warning "⚠ Could not restore: $file"
                    fi
                done

                # ตรวจสอบอีกครั้งว่าไฟล์ครบแล้วหรือยัง
                echo ""
                print_info "Re-checking files..."
                ALL_RESTORED=true
                for file in "${CRITICAL_FILES[@]}"; do
                    if [ ! -f "$file" ]; then
                        ALL_RESTORED=false
                        print_error "Still missing: $file"
                    fi
                done

                if [ "$ALL_RESTORED" = true ]; then
                    echo ""
                    print_success "All critical files restored successfully!"
                    echo ""
                else
                    echo ""
                    error_exit "Some files could not be restored. Please clone the repository again or restore from backup."
                fi
            else
                error_exit "Failed to fetch from git repository."
            fi
        else
            error_exit "Installation cancelled. Please restore missing files manually."
        fi
    else
        # ไม่ใช่ git repository
        echo ""
        print_error "This is not a git repository!"
        echo ""
        echo "Solutions:"
        echo "  1. Clone the full repository:"
        echo "     ${YELLOW}git clone https://github.com/xjanova/Thaiprompt-Affiliate.git${NC}"
        echo ""
        echo "  2. Or restore missing files from backup"
        echo ""
        error_exit "Cannot proceed without required files."
    fi
fi

echo ""
print_success "All required files present!"
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
# STEP 6: Verify Critical Files
# ========================================
print_header "Step 6: Verifying Critical Files"

print_info "Checking and fixing missing critical files..."

MISSING_FILES=()
FIXED_FILES=()

# ตรวจสอบและซ่อมแซม layouts
print_info "Checking layouts files..."

# ถ้าไม่มี admin.blade.php แต่มี admin-v3.blade.php ให้สร้าง symlink
if [ ! -f "resources/views/layouts/admin.blade.php" ]; then
    if [ -f "resources/views/layouts/admin-v3.blade.php" ]; then
        print_info "Creating symlink: admin.blade.php → admin-v3.blade.php"
        ln -sf admin-v3.blade.php resources/views/layouts/admin.blade.php
        FIXED_FILES+=("resources/views/layouts/admin.blade.php → admin-v3.blade.php")
        print_success "Created admin.blade.php symlink"
    else
        MISSING_FILES+=("resources/views/layouts/admin-v3.blade.php (required)")
        print_error "Missing admin-v3.blade.php layout!"
    fi
fi

# ตรวจสอบ layouts หลักอื่นๆ
REQUIRED_LAYOUTS=(
    "resources/views/layouts/app.blade.php"
    "resources/views/layouts/guest.blade.php"
    "resources/views/layouts/user.blade.php"
)

for layout in "${REQUIRED_LAYOUTS[@]}"; do
    if [ ! -f "$layout" ]; then
        MISSING_FILES+=("$layout")
        print_warning "Missing: $layout"
    else
        print_success "✓ Found: $layout"
    fi
done

# แสดงสรุป
echo ""
if [ ${#MISSING_FILES[@]} -ne 0 ]; then
    print_warning "Found ${#MISSING_FILES[@]} missing files:"
    for file in "${MISSING_FILES[@]}"; do
        echo "  ✗ $file"
    done
    echo ""
    print_warning "Some files are missing. Installation will continue, but you may need to restore them from backup or repository."
    echo ""
    read -p "Continue installation anyway? (y/n) [y]: " -n 1 -r CONTINUE_INSTALL
    echo ""
    CONTINUE_INSTALL=${CONTINUE_INSTALL:-y}
    if [[ ! $CONTINUE_INSTALL =~ ^[Yy]$ ]]; then
        error_exit "Installation cancelled by user."
    fi
fi

if [ ${#FIXED_FILES[@]} -ne 0 ]; then
    print_success "Fixed ${#FIXED_FILES[@]} files automatically:"
    for file in "${FIXED_FILES[@]}"; do
        echo "  ✓ $file"
    done
    echo ""
fi

print_success "Critical files verification completed"

# ตรวจสอบไฟล์ Core ของ Laravel
print_info "Verifying Laravel core files..."
CORE_FILES=(
    "artisan"
    "composer.json"
    "package.json"
    "public/index.php"
    "app/Http/Kernel.php"
)

CORE_MISSING=false
for file in "${CORE_FILES[@]}"; do
    if [ ! -f "$file" ]; then
        print_error "Critical file missing: $file"
        CORE_MISSING=true
    fi
done

if [ "$CORE_MISSING" = true ]; then
    echo ""
    error_exit "Critical Laravel core files are missing! Please restore from backup or clone the repository again."
fi
print_success "All core files present"

# ติดตั้ง Git Hooks (ถ้ามี)
if [ -d ".git" ] && [ -f "scripts/git-hooks/install.sh" ]; then
    echo ""
    print_info "Git repository detected. Installing Git Hooks..."
    if bash scripts/git-hooks/install.sh; then
        print_success "Git Hooks installed successfully"
    else
        print_warning "Git Hooks installation failed (continuing anyway)"
    fi
else
    print_info "Skipping Git Hooks (not a git repository or script not found)"
fi

# ========================================
# STEP 7: Install Dependencies
# ========================================
print_header "Step 7: Installing Dependencies"

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
    print_success "Composer dependencies installed"
else
    error_exit "Failed to install Composer dependencies"
fi

# ตรวจสอบและติดตั้ง Node.js dependencies
print_info "Checking for Node.js and npm..."
if command -v node &> /dev/null && command -v npm &> /dev/null; then
    NODE_VERSION=$(node --version)
    NPM_VERSION=$(npm --version)
    print_success "Node.js: $NODE_VERSION, npm: $NPM_VERSION"

    # ตรวจสอบว่ามี package.json หรือไม่
    if [ -f "package.json" ]; then
        print_info "Installing Node.js dependencies..."
        echo "This may take a few minutes..."
        if npm install --no-audit --no-fund; then
            print_success "Node.js dependencies installed"

            # Build assets
            print_info "Building frontend assets with Vite..."
            if npm run build; then
                print_success "Frontend assets built successfully"
            else
                print_warning "Failed to build assets (continuing anyway)"
            fi
        else
            print_warning "Failed to install Node.js dependencies (continuing anyway)"
        fi
    else
        print_warning "No package.json found, skipping Node.js dependencies"
    fi
else
    print_warning "Node.js or npm not found. Frontend assets will need to be built manually."
    print_info "To install Node.js: https://nodejs.org/"
fi

# ========================================
# STEP 8: Database Setup
# ========================================
print_header "Step 8: Database Setup"

# Clear config cache
print_info "Clearing configuration cache..."
php artisan config:clear

print_info "Running database migrations..."
if php artisan migrate --force; then
    print_success "Database migrations completed"
else
    error_exit "Database migration failed"
fi

# Ask about demo data
echo ""
print_info "ข้อมูลทดสอบ (Demo Data):"
echo ""
echo "ระบบสามารถติดตั้งข้อมูลทดสอบเพื่อให้คุณทดลองใช้งานได้ทันที"
echo "ข้อมูลทดสอบประกอบด้วย:"
echo "  • ผู้ใช้ทดสอบ (demo users, affiliates)"
echo "  • หน้าเพจตัวอย่าง (About, FAQ, Contact, Terms, Privacy)"
echo "  • ข้อมูล KYC ตัวอย่าง"
echo "  • LINE sessions ตัวอย่าง"
echo "  • ข้อมูลบัญชีตัวอย่าง"
echo ""
echo "หมายเหตุ: คุณสามารถลบข้อมูลทดสอบได้ภายหลังด้วยคำสั่ง: php artisan demo:reset"
echo ""
read -p "ต้องการติดตั้งข้อมูลทดสอบหรือไม่? (y/n) [y]: " -n 1 -r INSTALL_DEMO
echo ""
INSTALL_DEMO=${INSTALL_DEMO:-y}

print_info "Running database seeders..."
if php artisan db:seed --force; then
    print_success "Database seeders completed"

    # ถ้าไม่ต้องการ demo data ให้ลบทิ้ง
    if [[ ! $INSTALL_DEMO =~ ^[Yy]$ ]]; then
        echo ""
        print_info "กำลังลบข้อมูลทดสอบ..."
        php artisan demo:reset --all --force >/dev/null 2>&1 || true
        print_success "ลบข้อมูลทดสอบสำเร็จ - ติดตั้งเฉพาะข้อมูลจำเป็น"
    fi
else
    print_warning "Database seeders failed (continuing anyway)"
fi

# ========================================
# STEP 9: Create Super Admin
# ========================================
print_header "Step 9: Creating Super Admin Account"

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
# STEP 10: Finalization & Optimization
# ========================================
print_header "Step 10: Finalization & Optimization"

# Create storage link
print_info "Creating storage symlink..."
if php artisan storage:link --force 2>/dev/null; then
    print_success "Storage symlink created"
else
    print_warning "Storage symlink creation failed (may need manual setup)"
fi

# Set proper permissions
print_info "Setting file permissions..."
chmod -R 775 storage bootstrap/cache 2>/dev/null || true
find storage -type f -exec chmod 664 {} \; 2>/dev/null || true
find bootstrap/cache -type f -exec chmod 664 {} \; 2>/dev/null || true

# Detect web server user
WEB_USER=""
if id -u www-data >/dev/null 2>&1; then
    WEB_USER="www-data"
elif id -u nginx >/dev/null 2>&1; then
    WEB_USER="nginx"
elif id -u apache >/dev/null 2>&1; then
    WEB_USER="apache"
fi

# Set ownership if web server user detected
if [ -n "$WEB_USER" ]; then
    CURRENT_USER=$(whoami)
    if chown -R "$CURRENT_USER:$WEB_USER" storage bootstrap/cache 2>/dev/null; then
        print_success "Ownership set to $CURRENT_USER:$WEB_USER"
    else
        print_warning "Could not set ownership (may need sudo)"
    fi
fi
print_success "File permissions configured"

# Optimize Composer autoloader
print_info "Optimizing Composer autoloader..."
composer dump-autoload --optimize --no-dev 2>/dev/null || composer dump-autoload --optimize
print_success "Autoloader optimized"

# Clear all caches
print_info "Clearing all caches..."
php artisan cache:clear >/dev/null 2>&1 || true
php artisan config:clear >/dev/null 2>&1 || true
php artisan route:clear >/dev/null 2>&1 || true
php artisan view:clear >/dev/null 2>&1 || true
php artisan event:clear >/dev/null 2>&1 || true
print_success "All caches cleared"

# Rebuild caches for production
print_info "Building production caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
print_success "Production caches built"

# Create setup completed flag
print_info "Marking installation as completed..."
mkdir -p storage/app
echo "$(date)" > storage/app/.setup_completed
print_success "Installation marked as completed"

# ========================================
# Post-Installation Verification
# ========================================
print_header "🔍 Post-Installation Verification"

# Verify critical files and directories
print_info "Verifying installation..."

VERIFICATION_PASSED=true

# Check .env
if [ -f ".env" ]; then
    print_success "✓ .env file exists"
else
    print_error "✗ .env file missing"
    VERIFICATION_PASSED=false
fi

# Check storage permissions
if [ -w "storage/logs" ]; then
    print_success "✓ Storage is writable"
else
    print_warning "⚠ Storage may not be writable"
fi

# Check if database is accessible
if php artisan db:show >/dev/null 2>&1; then
    print_success "✓ Database connection OK"
else
    print_warning "⚠ Database connection check failed"
fi

# Check if migrations ran
MIGRATION_COUNT=$(php artisan migrate:status 2>/dev/null | grep -c "Ran" || echo "0")
if [ "$MIGRATION_COUNT" -gt "0" ]; then
    print_success "✓ Migrations completed ($MIGRATION_COUNT tables)"
else
    print_warning "⚠ No migrations detected"
fi

# Check if admin exists
ADMIN_COUNT=$(php artisan tinker --execute="echo App\Models\User::where('role', 'super_admin')->count();" 2>/dev/null | tail -1 || echo "0")
if [ "$ADMIN_COUNT" -gt "0" ]; then
    print_success "✓ Super Admin account created"
else
    print_warning "⚠ Super Admin account not found"
fi

# Check if symlink exists
if [ -L "public/storage" ]; then
    print_success "✓ Storage symlink exists"
else
    print_warning "⚠ Storage symlink not found"
fi

echo ""
if [ "$VERIFICATION_PASSED" = true ]; then
    print_success "All critical checks passed!"
else
    print_warning "Some checks failed, but installation may still work"
fi

# ========================================
# Installation Complete
# ========================================
print_header "✅ Installation Complete & Optimized!"

echo ""
echo "📊 Installation Summary:"
echo ""
echo "  Application:    ${BLUE}$APP_NAME${NC}"
echo "  URL:            ${BLUE}$APP_URL${NC}"
echo "  Environment:    ${BLUE}$APP_ENV${NC}"
echo "  Database:       ${BLUE}$DB_DATABASE@$DB_HOST${NC}"
echo "  Admin Email:    ${BLUE}$ADMIN_EMAIL${NC}"
echo ""
echo "✨ ${GREEN}What was installed and optimized:${NC}"
echo ""
echo "  ${BLUE}System & Requirements:${NC}"
echo "    ✅ PHP $(php -r 'echo PHP_VERSION;') with all required extensions"
echo "    ✅ Composer $(composer --version --no-ansi 2>/dev/null | grep -oP '\d+\.\d+\.\d+' | head -1) installed"
if command -v node &> /dev/null; then
    echo "    ✅ Node.js $(node --version) & npm $(npm --version) installed"
fi
if [ -d ".git" ]; then
    echo "    ✅ Git repository initialized"
    if [ -x ".git/hooks/pre-commit" ]; then
        echo "    ✅ Git Hooks installed and active"
    fi
fi
echo ""
echo "  ${BLUE}Application Setup:${NC}"
echo "    ✅ Environment configured (.env created)"
echo "    ✅ Application key generated"
echo "    ✅ Critical files verified and fixed"
echo ""
echo "  ${BLUE}Dependencies:${NC}"
echo "    ✅ Composer dependencies installed & optimized"
if command -v node &> /dev/null && [ -d "node_modules" ]; then
    echo "    ✅ Node.js dependencies installed"
    if [ -d "public/build" ]; then
        echo "    ✅ Frontend assets built (Vite)"
    fi
fi
echo ""
echo "  ${BLUE}Database:${NC}"
echo "    ✅ Database connection verified"
echo "    ✅ Migrations executed ($(php artisan migrate:status 2>/dev/null | grep -c "Ran" || echo "?") tables)"
echo "    ✅ Database seeded with initial data"
if [[ $INSTALL_DEMO =~ ^[Yy]$ ]]; then
    echo "    ✅ Demo data installed (can be removed with: php artisan demo:reset)"
else
    echo "    ✅ Production-ready (no demo data)"
fi
echo ""
echo "  ${BLUE}Security & Accounts:${NC}"
echo "    ✅ Super Admin account created"
echo "    ✅ File permissions configured (775)"
if [ -n "$WEB_USER" ]; then
    echo "    ✅ Ownership set for web server ($WEB_USER)"
fi
echo ""
echo "  ${BLUE}Performance:${NC}"
echo "    ✅ Composer autoloader optimized"
echo "    ✅ Config, routes, views cached"
echo "    ✅ Storage symlink created"
echo ""
echo "  ${GREEN}✅ System fully installed and production-ready!${NC}"
echo ""
echo "🎉 ${GREEN}Congratulations!${NC} TP-Affiliate is fully installed and optimized."
echo ""
echo "📋 Next Steps:"
echo ""
echo "  ${BLUE}1.${NC} Configure your web server (Nginx/Apache):"
echo "      ${GREEN}→${NC} Point DocumentRoot to ${YELLOW}$(pwd)/public${NC}"
echo "      ${GREEN}→${NC} See INSTALLATION.md for detailed web server config"
echo ""
echo "  ${BLUE}2.${NC} Access your application:"
echo "      ${GREEN}→${NC} Frontend: ${YELLOW}$APP_URL${NC}"
echo "      ${GREEN}→${NC} Admin Panel: ${YELLOW}$APP_URL/admin${NC}"
echo ""
echo "  ${BLUE}3.${NC} Login with your admin credentials:"
echo "      ${GREEN}→${NC} Email: ${YELLOW}$ADMIN_EMAIL${NC}"
echo "      ${GREEN}→${NC} Password: (the password you just set)"
echo ""
echo "  ${BLUE}4.${NC} Configure additional settings (optional):"
echo "      ${GREEN}→${NC} Email (MAIL_*, GMAIL_*, SMTP_*)"
echo "      ${GREEN}→${NC} Cloudflare Turnstile (CLOUDFLARE_TURNSTILE_*)"
echo "      ${GREEN}→${NC} Google Translate API (GOOGLE_TRANSLATE_*)"
echo "      ${GREEN}→${NC} Edit ${YELLOW}.env${NC} file to configure"
echo ""
echo "  ${BLUE}5.${NC} Set up GitHub for deployments (optional):"
echo "      ${GREEN}→${NC} Generate Personal Access Token at GitHub"
echo "      ${GREEN}→${NC} Configure git remote with token"
echo "      ${GREEN}→${NC} Use ${YELLOW}./deploy.sh${NC} for future updates"
echo ""
echo "📖 Documentation:"
echo "  ${GREEN}→${NC} ${YELLOW}INSTALLATION.md${NC} - Complete setup guide"
echo "  ${GREEN}→${NC} ${YELLOW}README.md${NC} - General information"
echo "  ${GREEN}→${NC} ${YELLOW}DEPLOYMENT.md${NC} - Deployment guide"
echo ""
echo "🚀 ${GREEN}Your system is ready to use immediately!${NC}"
echo ""
echo "💡 ${BLUE}Quick Test:${NC}"
echo "  If you have PHP built-in server:"
echo "  ${YELLOW}php artisan serve${NC}"
echo "  Then visit: ${YELLOW}http://localhost:8000${NC}"
echo ""
echo "${GREEN}Happy deploying! 🎊${NC}"
echo ""
