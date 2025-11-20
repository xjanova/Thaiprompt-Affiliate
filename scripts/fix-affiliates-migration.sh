#!/bin/bash

# ================================================================
# Fix Affiliates Table Migration Issues
# ================================================================
# ใช้สคริปต์นี้เมื่อเจอปัญหา:
# - SQLSTATE[42S01]: Base table or view already exists: 1050 Table 'affiliates' already exists
# - Foreign key constraint is incorrectly formed
# ================================================================

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

print_header() {
    echo ""
    echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
    echo -e "${BLUE}  $1${NC}"
    echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
    echo ""
}

print_success() { echo -e "${GREEN}✓ $1${NC}"; }
print_error() { echo -e "${RED}✗ $1${NC}"; }
print_info() { echo -e "${BLUE}ℹ $1${NC}"; }
print_warning() { echo -e "${YELLOW}⚠ $1${NC}"; }

# Header
print_header "🔧 Fix Affiliates Table Migration Issues"

echo "This script will:"
echo "  1. Remove old affiliates table (if exists)"
echo "  2. Clean up migrations table entries"
echo "  3. Remove old migration file (2024_01_01_000002)"
echo "  4. Prepare for fresh migration run"
echo ""
print_warning "This will DROP the affiliates table if it exists!"
echo ""
read -p "Continue? (y/n) [y]: " CONTINUE
CONTINUE=${CONTINUE:-y}

if [[ ! $CONTINUE =~ ^[Yy]$ ]]; then
    echo "Aborted."
    exit 0
fi

# ========================================
# STEP 1: Check Database Connection
# ========================================
print_header "Step 1: Checking Database Connection"

if ! php artisan db:show >/dev/null 2>&1; then
    print_error "Cannot connect to database. Please check .env configuration."
    exit 1
fi
print_success "Database connection OK"

# ========================================
# STEP 2: Remove Old Migration File
# ========================================
print_header "Step 2: Removing Old Migration Files"

OLD_MIGRATION="database/migrations/2024_01_01_000002_create_affiliates_table.php"

if [ -f "$OLD_MIGRATION" ]; then
    print_info "Found old migration file: $OLD_MIGRATION"
    rm -f "$OLD_MIGRATION"
    print_success "Old migration file removed"
else
    print_info "Old migration file not found (OK)"
fi

# ========================================
# STEP 3: Drop Affiliates Table
# ========================================
print_header "Step 3: Dropping Affiliates Table"

print_info "Checking if affiliates table exists..."

# Check if table exists using artisan
TABLE_EXISTS=$(php -r "
require 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
echo \Illuminate\Support\Facades\Schema::hasTable('affiliates') ? 'yes' : 'no';
")

if [ "$TABLE_EXISTS" = "yes" ]; then
    print_warning "Table 'affiliates' exists. Dropping..."

    # Drop table using artisan tinker
    php artisan tinker --execute="
        \Illuminate\Support\Facades\Schema::dropIfExists('affiliates');
        echo 'Table dropped successfully';
    "

    print_success "Table 'affiliates' dropped"
else
    print_info "Table 'affiliates' does not exist (OK)"
fi

# ========================================
# STEP 4: Clean Migrations Table
# ========================================
print_header "Step 4: Cleaning Migrations Table"

print_info "Removing old migration entries..."

# Remove old migration entries
php artisan tinker --execute="
    \$deleted = DB::table('migrations')
        ->where('migration', 'like', '%create_affiliates_table')
        ->delete();
    echo \"Deleted \$deleted migration entries\";
"

print_success "Migrations table cleaned"

# ========================================
# STEP 5: Verify New Migration File
# ========================================
print_header "Step 5: Verifying New Migration File"

NEW_MIGRATION="database/migrations/2025_11_17_000001_create_affiliates_table.php"

if [ -f "$NEW_MIGRATION" ]; then
    print_success "New migration file exists: $NEW_MIGRATION"
else
    print_error "New migration file not found!"
    print_info "Please pull the latest code from repository."
    exit 1
fi

# Check if users table exists (required)
USERS_TABLE_EXISTS=$(php -r "
require 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
echo \Illuminate\Support\Facades\Schema::hasTable('users') ? 'yes' : 'no';
")

if [ "$USERS_TABLE_EXISTS" = "yes" ]; then
    print_success "Users table exists (required for affiliates foreign key)"
else
    print_warning "Users table does not exist yet"
    print_info "Make sure to run migrations in order"
fi

# ========================================
# Summary
# ========================================
print_header "✅ Cleanup Complete!"

echo ""
echo -e "${GREEN}All done! You can now run migrations again:${NC}"
echo ""
echo -e "  ${YELLOW}php artisan migrate${NC}"
echo ""
echo -e "  Or if running install.sh:"
echo -e "  ${YELLOW}./install.sh${NC}"
echo ""
echo -e "${BLUE}The new migration will create affiliates table with correct foreign keys.${NC}"
echo ""
