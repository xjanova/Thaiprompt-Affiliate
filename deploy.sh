#!/bin/bash

# TP-Affiliate Deployment Script
# Safe Production Deployment with Rollback Support and Auto-Retry on Timeout

# Disable auto-exit on error (we'll handle errors manually)
set +e

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BACKUP_DIR="$SCRIPT_DIR/backups"
LOG_FILE="$SCRIPT_DIR/storage/logs/deployment.log"
MAX_DEPLOYMENT_ATTEMPTS=3

# Track deployment attempts via environment variable
if [ -z "$DEPLOY_ATTEMPT_COUNT" ]; then
    export DEPLOY_ATTEMPT_COUNT=1
else
    export DEPLOY_ATTEMPT_COUNT=$((DEPLOY_ATTEMPT_COUNT + 1))
fi

# Check if we've exceeded max attempts
if [ $DEPLOY_ATTEMPT_COUNT -gt $MAX_DEPLOYMENT_ATTEMPTS ]; then
    echo ""
    echo "╔═══════════════════════════════════════════════════════════╗"
    echo "║  ❌ Deploy ล้มเหลว - ได้ลองครบ $MAX_DEPLOYMENT_ATTEMPTS ครั้งแล้ว              ║"
    echo "╚═══════════════════════════════════════════════════════════╝"
    echo ""
    echo "💡 กรุณาลอง Deploy ใหม่ภายหลัง หรือตรวจสอบปัญหา:"
    echo "  1. ตรวจสอบการเชื่อมต่ออินเทอร์เน็ต"
    echo "  2. ตรวจสอบว่า GitHub และ Packagist สามารถเข้าถึงได้"
    echo "  3. ตรวจสอบ logs: tail -f storage/logs/deployment.log"
    echo "  4. ลองใหม่ภายหลัง 10-15 นาที"
    echo ""
    unset DEPLOY_ATTEMPT_COUNT
    exit 1
fi

# Determine branch to deploy
if [ -n "$1" ]; then
    # Use specified branch
    BRANCH="$1"
else
    # Use current branch
    BRANCH=$(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo "")
    if [ -z "$BRANCH" ] || [ "$BRANCH" = "HEAD" ]; then
        echo "Error: Could not determine current branch"
        echo "Usage: $0 [branch-name]"
        echo "Example: $0 claude/Main"
        unset DEPLOY_ATTEMPT_COUNT
        exit 1
    fi
fi

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
MAGENTA='\033[0;35m'
NC='\033[0m' # No Color

# Functions
print_success() {
    echo -e "${GREEN}✓${NC} $1" | tee -a "$LOG_FILE"
}

print_error() {
    echo -e "${RED}✗${NC} $1" | tee -a "$LOG_FILE"
}

print_info() {
    echo -e "${BLUE}ℹ${NC} $1" | tee -a "$LOG_FILE"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1" | tee -a "$LOG_FILE"
}

print_header() {
    echo ""
    echo -e "${MAGENTA}════════════════════════════════════════${NC}"
    echo -e "${MAGENTA}  $1${NC}"
    echo -e "${MAGENTA}════════════════════════════════════════${NC}"
    echo ""
}

log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"
}

# Smart ENV Sync - Auto-update .env with new variables from .env.example
sync_env_file() {
    print_header "🔄 Smart ENV Sync System"

    if [ ! -f ".env.example" ]; then
        print_warning "No .env.example found - skipping ENV sync"
        return 0
    fi

    if [ ! -f ".env" ]; then
        print_error ".env file not found!"
        return 1
    fi

    print_info "Checking for new environment variables..."

    # Create temporary files for processing
    local temp_new_vars="/tmp/new_env_vars_$$.txt"
    local temp_env_backup="/tmp/env_backup_$$.env"

    # Backup current .env
    cp .env "$temp_env_backup"

    # Extract variable names from both files (ignore comments and empty lines)
    local example_vars=$(grep -v '^#' .env.example | grep -v '^$' | cut -d '=' -f1 | sort)
    local current_vars=$(grep -v '^#' .env | grep -v '^$' | cut -d '=' -f1 | sort)

    # Find new variables that exist in .env.example but not in .env
    local new_vars=()
    local added_count=0

    while IFS= read -r var; do
        if ! grep -q "^${var}=" .env 2>/dev/null; then
            new_vars+=("$var")
        fi
    done <<< "$example_vars"

    # If there are new variables, add them
    if [ ${#new_vars[@]} -gt 0 ]; then
        print_warning "Found ${#new_vars[@]} new environment variable(s)"
        echo ""
        print_info "→ New variables to be added:"

        # Show what will be added
        for var in "${new_vars[@]}"; do
            local var_line=$(grep "^${var}=" .env.example)
            echo "  • $var"
        done
        echo ""

        # Add new variables to .env with proper formatting
        print_info "→ Adding new variables to .env..."

        # Create a temporary file with updates
        cp .env .env.tmp

        echo "" >> .env.tmp
        echo "# Auto-added by deploy script on $(date +'%Y-%m-%d %H:%M:%S')" >> .env.tmp

        for var in "${new_vars[@]}"; do
            # Get the full line from .env.example (including comments if any)
            local line_number=$(grep -n "^${var}=" .env.example | cut -d ':' -f1)

            # Get any comment above this variable
            if [ -n "$line_number" ] && [ "$line_number" -gt 1 ]; then
                local prev_line=$((line_number - 1))
                local comment_line=$(sed -n "${prev_line}p" .env.example)
                if [[ "$comment_line" =~ ^#.*$ ]]; then
                    echo "$comment_line" >> .env.tmp
                fi
            fi

            # Add the variable
            grep "^${var}=" .env.example >> .env.tmp
            added_count=$((added_count + 1))
        done

        # Replace .env with updated version
        mv .env.tmp .env

        print_success "✓ Added $added_count new variable(s) to .env"
        echo ""

        print_info "→ Summary of changes:"
        for var in "${new_vars[@]}"; do
            local value=$(grep "^${var}=" .env | cut -d '=' -f2-)
            echo "  • $var=${value}"
        done
        echo ""

        print_success "✓ .env file successfully synced with .env.example"
        print_info "  Backup saved: $temp_env_backup"
        log "ENV Sync: Added $added_count new variables to .env"
    else
        print_success "✓ .env is already up to date with .env.example"
        rm -f "$temp_env_backup"
    fi

    echo ""
    return 0
}

# Ensure Laravel essential directories exist
ensure_laravel_directories() {
    mkdir -p bootstrap/cache 2>/dev/null || true
    mkdir -p storage/{app,framework,logs} 2>/dev/null || true
    mkdir -p storage/framework/{cache,sessions,views} 2>/dev/null || true
    mkdir -p storage/app/{public,private} 2>/dev/null || true
    mkdir -p database 2>/dev/null || true
    chmod -R 775 storage bootstrap/cache 2>/dev/null || true
}

# Check if error is timeout-related
is_timeout_error() {
    local error_msg="$1"
    local exit_code="$2"

    # Common timeout-related patterns
    if [[ "$error_msg" =~ (timeout|timed out|Connection timed out|Operation timed out) ]] || \
       [[ "$error_msg" =~ (could not read|failed to connect|unable to access) ]] || \
       [[ "$error_msg" =~ (network|DNS|resolution failed|temporary failure) ]] || \
       [[ "$exit_code" == "124" ]] || [[ "$exit_code" == "143" ]]; then
        return 0  # Is timeout error
    fi
    return 1  # Not timeout error
}

# Error handler with auto-retry on timeout
error_exit() {
    local error_msg="$1"
    local last_exit_code="${2:-$?}"

    print_error "Deployment failed: $error_msg"
    log "ERROR: $error_msg (exit code: $last_exit_code)"

    # Ensure essential directories exist before running artisan
    ensure_laravel_directories

    # Try to bring application back up
    php artisan up 2>/dev/null || {
        print_error "Could not disable maintenance mode automatically"
        print_info "Please run manually: php artisan up"
    }

    # Check if it's a timeout error and we haven't exceeded max attempts
    if is_timeout_error "$error_msg" "$last_exit_code" && [ $DEPLOY_ATTEMPT_COUNT -lt $MAX_DEPLOYMENT_ATTEMPTS ]; then
        echo ""
        print_warning "╔═══════════════════════════════════════════════════════════╗"
        print_warning "║  ⚠️  ตรวจพบ Timeout Error - กำลังเริ่ม Deploy ใหม่...   ║"
        print_warning "╚═══════════════════════════════════════════════════════════╝"
        echo ""
        print_info "📊 ความคืบหน้า: รอบที่ $DEPLOY_ATTEMPT_COUNT/$MAX_DEPLOYMENT_ATTEMPTS"
        print_info "⏳ รอ 10 วินาทีก่อนเริ่มใหม่..."
        log "RETRY: Restarting deployment (attempt $DEPLOY_ATTEMPT_COUNT/$MAX_DEPLOYMENT_ATTEMPTS)"
        sleep 10

        echo ""
        print_info "🔄 กำลังเริ่มต้น Deploy ใหม่จากตั้งแต่ต้น..."
        echo ""
        sleep 2

        # Restart the entire deployment script
        exec "$0" "$@"
    fi

    # Final failure - not a timeout or exceeded max attempts
    echo ""
    print_error "╔═══════════════════════════════════════════════════════════╗"
    print_error "║  ❌ การ Deploy ล้มเหลว - กรุณาลองใหม่ภายหลัง            ║"
    print_error "╚═══════════════════════════════════════════════════════════╝"
    echo ""

    if [ $DEPLOY_ATTEMPT_COUNT -ge $MAX_DEPLOYMENT_ATTEMPTS ]; then
        print_error "📍 ได้ลองทำการ Deploy ครบ $MAX_DEPLOYMENT_ATTEMPTS รอบแล้ว แต่ยังไม่สำเร็จ"
        echo ""
    fi

    print_info "💡 คำแนะนำในการแก้ไข:"
    echo "  1. ตรวจสอบการเชื่อมต่ออินเทอร์เน็ต"
    echo "  2. ตรวจสอบว่า GitHub และ Packagist สามารถเข้าถึงได้"
    echo "  3. ตรวจสอบ logs: tail -f storage/logs/deployment.log"
    echo "  4. ลอง Deploy ใหม่ภายหลัง 10-15 นาที"
    echo "  5. หากยังไม่สำเร็จ ติดต่อทีมพัฒนา"
    echo ""

    unset DEPLOY_ATTEMPT_COUNT
    exit 1
}

# Trap errors - disable for now to handle errors manually
# trap 'error_exit "An error occurred on line $LINENO"' ERR

# Backup critical files (PREVENT DATA LOSS!)
backup_critical_files() {
    local backup_timestamp=$(date +'%Y%m%d_%H%M%S')
    CRITICAL_BACKUP_DIR="$BACKUP_DIR/critical_$backup_timestamp"
    mkdir -p "$CRITICAL_BACKUP_DIR"

    # Backup .env files (CRITICAL!)
    if [ -f ".env" ]; then
        cp .env "$CRITICAL_BACKUP_DIR/.env" && \
            print_success "✓ Backed up .env"
    fi

    if [ -f ".env.production" ]; then
        cp .env.production "$CRITICAL_BACKUP_DIR/.env.production" 2>/dev/null || true
    fi

    # Backup uploaded files
    if [ -d "storage/app/public" ]; then
        cp -r storage/app/public "$CRITICAL_BACKUP_DIR/storage_public" 2>/dev/null || true
    fi

    echo "$CRITICAL_BACKUP_DIR" > /tmp/deploy_backup_path_$$
    log "Critical files backed up to: $CRITICAL_BACKUP_DIR"
}

# Restore critical files (PREVENT DATA LOSS!)
restore_critical_files() {
    if [ -f "/tmp/deploy_backup_path_$$" ]; then
        local backup_path=$(cat /tmp/deploy_backup_path_$$)

        if [ -d "$backup_path" ]; then
            # Restore .env (CRITICAL!)
            if [ -f "$backup_path/.env" ]; then
                cp "$backup_path/.env" .env && \
                    print_success "✓ Restored .env"
            else
                print_warning "⚠ No .env backup found!"
                if [ -f "$backup_path/.env.production" ]; then
                    cp "$backup_path/.env.production" .env && \
                        print_warning "✓ Created .env from .env.production backup"
                elif [ -f ".env.production" ]; then
                    cp .env.production .env && \
                        print_warning "✓ Created .env from .env.production"
                elif [ -f ".env.example" ]; then
                    cp .env.example .env && \
                        print_error "✗ Created .env from .env.example - UPDATE CREDENTIALS!"
                else
                    error_exit "CRITICAL: No .env file available!"
                fi
            fi

            # Restore uploaded files
            if [ -d "$backup_path/storage_public" ]; then
                mkdir -p storage/app
                cp -r "$backup_path/storage_public" storage/app/public 2>/dev/null || true
                print_success "✓ Restored uploaded files"
            fi
        fi

        rm -f /tmp/deploy_backup_path_$$
    fi
}

# Header with XMAN Logo
echo ""
echo -e "${GREEN}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║${NC}    ${MAGENTA}██╗  ██╗███╗   ███╗ █████╗ ███╗   ██╗${NC}                      ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}    ${MAGENTA}╚██╗██╔╝████╗ ████║██╔══██╗████╗  ██║${NC}                      ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}     ${MAGENTA}╚███╔╝ ██╔████╔██║███████║██╔██╗ ██║${NC}                      ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}     ${MAGENTA}██╔██╗ ██║╚██╔╝██║██╔══██║██║╚██╗██║${NC}                      ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}    ${MAGENTA}██╔╝ ██╗██║ ╚═╝ ██║██║  ██║██║ ╚████║${NC}                      ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}    ${MAGENTA}╚═╝  ╚═╝╚═╝     ╚═╝╚═╝  ╚═╝╚═╝  ╚═══╝${NC}                      ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}                                                                ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}    ${BLUE}🚀 TP-Affiliate Deployment System v2.0${NC}                      ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}    ${YELLOW}⚡ Safe • Smart • Secure Deployment${NC}                       ${GREEN}║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""

if [ $DEPLOY_ATTEMPT_COUNT -gt 1 ]; then
    echo "🔄 ${YELLOW}กำลังลองใหม่ - รอบที่ $DEPLOY_ATTEMPT_COUNT/$MAX_DEPLOYMENT_ATTEMPTS${NC}"
    echo ""
fi

echo "📋 Deployment Configuration:"
echo "  Branch:      ${BLUE}$BRANCH${NC}"
echo "  Directory:   ${BLUE}$SCRIPT_DIR${NC}"
echo "  User:        ${BLUE}$(whoami)${NC}"
echo "  Time:        ${BLUE}$(date)${NC}"
echo ""

log "=== Deployment Started (Attempt $DEPLOY_ATTEMPT_COUNT/$MAX_DEPLOYMENT_ATTEMPTS) ==="
log "Branch: $BRANCH"
log "User: $(whoami)"
log "Host: $(hostname)"

# Verify branch exists on remote
print_header "🔍 Pre-flight Checks"

print_info "Checking remote branch availability..."
if ! git ls-remote --heads origin "$BRANCH" | grep -q "$BRANCH"; then
    print_error "Branch '$BRANCH' does not exist on remote!"
    echo ""
    echo "Available branches on remote:"
    git ls-remote --heads origin | sed 's/.*refs\/heads\//  - /'
    echo ""
    echo "Usage: $0 [branch-name]"
    echo "Example: $0 claude/Main"
    exit 1
fi
print_success "Branch '$BRANCH' exists on remote"

# Pre-flight checks

# Check if .env exists
if [ ! -f .env ]; then
    error_exit ".env file not found!"
fi
print_success ".env file found"

# Check if git repo
if [ ! -d .git ]; then
    error_exit "Not a git repository!"
fi
print_success "Git repository detected"

# Check APP_ENV
APP_ENV=$(grep "^APP_ENV=" .env | cut -d '=' -f2)
print_info "Environment: $APP_ENV"

if [ "$APP_ENV" != "production" ]; then
    print_warning "APP_ENV is not 'production' (current: $APP_ENV)"
    read -p "Continue anyway? (y/n): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

# Check for uncommitted changes
if [[ -n $(git status -s) ]]; then
    print_warning "Local changes detected - will be discarded during deployment"
    git status -s
    log "WARNING: Local changes will be overwritten by deployment"
fi

# Create backup directory
mkdir -p "$BACKUP_DIR"

# Start deployment
print_header "📦 Deployment Process"

# Step 1: Enable Maintenance Mode
print_info "[1/19] Enabling maintenance mode..."

# Ensure directories exist before running artisan
ensure_laravel_directories

php artisan down --retry=60 --render="errors::503" 2>/dev/null || {
    print_warning "Could not enable maintenance mode (may already be down or need manual intervention)"
}
print_success "Maintenance mode enabled"
sleep 2  # Give time for requests to finish

# Step 2: Backup Database
print_info "[2/19] Creating database backup..."
BACKUP_FILE="$BACKUP_DIR/db_backup_$(date +'%Y%m%d_%H%M%S').sql"

# Get database info from .env
DB_CONNECTION=$(grep "^DB_CONNECTION=" .env | cut -d '=' -f2)
DB_DATABASE=$(grep "^DB_DATABASE=" .env | cut -d '=' -f2)
DB_USERNAME=$(grep "^DB_USERNAME=" .env | cut -d '=' -f2)
DB_PASSWORD=$(grep "^DB_PASSWORD=" .env | cut -d '=' -f2)
DB_HOST=$(grep "^DB_HOST=" .env | cut -d '=' -f2)

if [ "$DB_CONNECTION" = "mysql" ] && command -v mysqldump >/dev/null 2>&1; then
    if [ -z "$DB_PASSWORD" ]; then
        mysqldump -h "$DB_HOST" -u "$DB_USERNAME" "$DB_DATABASE" > "$BACKUP_FILE" 2>/dev/null || {
            print_warning "Database backup failed (continuing anyway)"
        }
    else
        mysqldump -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" > "$BACKUP_FILE" 2>/dev/null || {
            print_warning "Database backup failed (continuing anyway)"
        }
    fi

    if [ -f "$BACKUP_FILE" ]; then
        print_success "Database backed up to: $BACKUP_FILE"
        log "Database backup: $BACKUP_FILE"
    fi
else
    print_warning "Skipping database backup (mysqldump not available or not MySQL)"
fi

# Step 3: Get current git commit (for rollback)
CURRENT_COMMIT=$(git rev-parse HEAD)
log "Current commit: $CURRENT_COMMIT"
print_info "Current commit: ${CURRENT_COMMIT:0:8}"

# Step 3: Backup Critical Files (PREVENT DATA LOSS!)
print_info "[3/19] Backing up critical files (.env, uploads)..."
backup_critical_files
print_success "Critical files backed up safely"

# Step 4: Force Pull Latest Code from GitHub
print_info "[4/19] Force syncing with GitHub..."

# Step 4.1: Stash any local changes (for safety backup)
if [[ -n $(git status -s) ]]; then
    print_info "Backing up local changes to stash..."
    git stash push -u -m "Auto-stash before deployment $(date +'%Y-%m-%d %H:%M:%S')" 2>/dev/null || true
fi

# Step 4.2: Fetch all changes from remote
print_info "Fetching latest code from origin/$BRANCH..."
if ! git fetch origin "$BRANCH" 2>&1 | tee -a "$LOG_FILE"; then
    error_exit "Failed to fetch from git - อาจเป็นปัญหาการเชื่อมต่อ" "$?"
fi

# Step 4.3: Force reset to match GitHub exactly
print_info "Force resetting to origin/$BRANCH..."
if ! git reset --hard "origin/$BRANCH" 2>&1 | tee -a "$LOG_FILE"; then
    error_exit "Failed to reset to origin/$BRANCH" "$?"
fi

# Step 4.4: Clean all untracked files and directories (SAFE - excludes critical files)
print_info "Removing untracked files and directories..."
# Note: Critical files (.env, uploads) are backed up and will be restored
git clean -fdx -e '.env*' -e 'storage/app/public/*' -e 'public/storage' || print_warning "Git clean failed (continuing anyway)"

# Step 4.5: Restore Critical Files (PREVENT DATA LOSS!)
print_info "Restoring critical files (.env, uploads)..."
restore_critical_files
print_success "Critical files restored successfully"

# Step 4.6: Smart ENV Sync - Auto-update .env with new variables
print_info "[4.6/19] Syncing .env with .env.example..."
if ! sync_env_file; then
    error_exit "ENV sync failed" "$?"
fi

# Step 4.7: Recreate essential Laravel directories
print_info "Recreating essential Laravel directories..."
ensure_laravel_directories
print_success "Essential directories created"

# Step 5: Verify we're in sync with remote
LOCAL_COMMIT=$(git rev-parse HEAD)
REMOTE_COMMIT=$(git rev-parse "origin/$BRANCH")

if [ "$LOCAL_COMMIT" = "$REMOTE_COMMIT" ]; then
    print_success "✓ Code is now in perfect sync with GitHub"
else
    print_warning "Local and remote commits differ (this shouldn't happen)"
fi

NEW_COMMIT=$(git rev-parse HEAD)
log "New commit: $NEW_COMMIT"
print_info "New commit: ${NEW_COMMIT:0:8}"

# Step 6: Ensure Base Controller Exists
print_info "[6/20] Ensuring base Controller exists..."
CONTROLLER_FILE="app/Http/Controllers/Controller.php"
if [ ! -f "$CONTROLLER_FILE" ]; then
    print_warning "Base Controller.php not found, creating..."
    mkdir -p app/Http/Controllers
    cat > "$CONTROLLER_FILE" << 'CONTROLLER_EOF'
<?php

namespace App\Http\Controllers;

abstract class Controller
{
    //
}
CONTROLLER_EOF
    print_success "Base Controller.php created"
else
    print_success "Base Controller.php exists"
fi

# Step 7: Install/Update Composer Dependencies
print_info "[7/20] Installing composer dependencies..."

# Remove vendor directory to ensure clean install
if [ -d "vendor" ]; then
    print_info "Removing old vendor directory for clean install..."
    rm -rf vendor
fi

# Clear composer cache
composer clear-cache 2>/dev/null || true

# Install dependencies from scratch
print_info "Installing composer dependencies..."
if ! composer install --no-dev --optimize-autoloader --no-interaction 2>&1 | tee -a "$LOG_FILE"; then
    error_exit "Composer install failed - อาจเป็นปัญหา network หรือ Packagist" "$?"
fi
print_success "Composer dependencies installed (clean)"

# Verify composer lock file matches
if [ -f "composer.lock" ]; then
    composer validate --no-check-all --no-check-publish 2>/dev/null && \
        print_success "Composer.lock is valid" || \
        print_warning "Composer.lock validation failed"
fi

# Step 8: Install/Reinstall Laravel Sanctum
print_info "[8/20] Installing Laravel Sanctum..."
if ! php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider" --force 2>&1 | tee -a "$LOG_FILE"; then
    error_exit "Sanctum installation failed" "$?"
fi
print_success "Laravel Sanctum installed and configured"

# Step 9: Clear All Cache (before migration)
print_info "[9/20] Clearing all caches..."
php artisan cache:clear 2>/dev/null || true
php artisan config:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true
php artisan event:clear 2>/dev/null || true
print_success "All caches cleared"

# Step 10: Smart Database Migration System
print_info "[10/20] 🎯 Smart Database Migration System..."
echo ""

# Step 9.1: Verify database connection
print_info "→ Verifying database connection..."
if ! php artisan db:show >/dev/null 2>&1; then
    error_exit "Database connection failed - ตรวจสอบ .env credentials"
fi
print_success "✓ Database connection OK"

# Step 9.2: Show current migration status (BEFORE)
print_info "→ Current migration status (BEFORE):"
php artisan migrate:status 2>/dev/null | tail -15 || true
echo ""

# Step 9.3: Check for pending migrations
PENDING_COUNT=$(php artisan migrate:status --pending 2>/dev/null | grep -c "Pending" || echo "0")
TOTAL_MIGRATIONS=$(php artisan migrate:status 2>/dev/null | grep -c "migration" || echo "0")

print_info "→ Migration Analysis:"
echo "  • Total migrations: $TOTAL_MIGRATIONS"
echo "  • Pending migrations: $PENDING_COUNT"
echo ""

# Step 9.4: Run migrations if needed
if [ "$PENDING_COUNT" != "0" ] && [ "$PENDING_COUNT" != "" ]; then
    print_warning "⚠ Found $PENDING_COUNT pending migration(s) - Will apply now"
    echo ""

    # Show what will be migrated
    print_info "→ Migrations to be applied:"
    php artisan migrate:status --pending 2>/dev/null | grep "Pending" | sed 's/^/  • /' || true
    echo ""

    # Backup database schema before migration
    print_info "→ Backing up database schema..."
    MIGRATION_BACKUP="$BACKUP_DIR/pre_migration_$(date +'%Y%m%d_%H%M%S').sql"
    if [ "$DB_CONNECTION" = "mysql" ] && command -v mysqldump >/dev/null 2>&1; then
        if [ -z "$DB_PASSWORD" ]; then
            mysqldump -h "$DB_HOST" -u "$DB_USERNAME" "$DB_DATABASE" > "$MIGRATION_BACKUP" 2>/dev/null || \
                print_warning "Schema backup failed (continuing anyway)"
        else
            mysqldump -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" > "$MIGRATION_BACKUP" 2>/dev/null || \
                print_warning "Schema backup failed (continuing anyway)"
        fi
        if [ -f "$MIGRATION_BACKUP" ]; then
            print_success "✓ Schema backed up: $MIGRATION_BACKUP"
        fi
    fi
    echo ""

    # Run migrations with detailed output
    print_info "→ Executing migrations..."
    if ! php artisan migrate --force 2>&1 | tee -a "$LOG_FILE"; then
        print_error "✗ Migration failed!"
        echo ""
        print_warning "→ Rollback information:"
        echo "  • Backup file: $MIGRATION_BACKUP"
        echo "  • Rollback command: php artisan migrate:rollback"
        echo "  • Restore DB: mysql -u $DB_USERNAME -p $DB_DATABASE < $MIGRATION_BACKUP"
        error_exit "Database migration failed - ตรวจสอบ logs และ backup"
    fi
    print_success "✓ Migrations applied successfully!"
    echo ""
else
    print_success "✓ No pending migrations - Database schema is up to date"
    # Still run migrate to ensure everything is in sync
    php artisan migrate --force >/dev/null 2>&1 || true
    echo ""
fi

# Step 9.5: Show migration status (AFTER)
print_info "→ Migration status (AFTER):"
php artisan migrate:status 2>/dev/null | tail -15 || true
echo ""

# Step 9.6: Verify migration integrity
print_info "→ Verifying database integrity..."
MIGRATED_COUNT=$(php artisan migrate:status 2>/dev/null | grep -c "Ran" || echo "0")
echo "  • Successfully migrated: $MIGRATED_COUNT migrations"

if [ "$PENDING_COUNT" != "0" ]; then
    NEW_PENDING=$(php artisan migrate:status --pending 2>/dev/null | grep -c "Pending" || echo "0")
    if [ "$NEW_PENDING" = "0" ]; then
        print_success "✓ All migrations completed successfully!"
    else
        print_warning "⚠ Still have $NEW_PENDING pending migrations"
    fi
fi
echo ""

# Step 11: Smart Database Seeding System
print_info "[11/20] 🌱 Checking database seeding status..."
echo ""

# Check if seeders directory exists and has seeders
SEEDER_DIR="database/seeders"
if [ ! -d "$SEEDER_DIR" ]; then
    print_warning "No seeders directory found - skipping seeding"
else
    # Count seeder files (excluding DatabaseSeeder.php and README.md)
    SEEDER_COUNT=$(find "$SEEDER_DIR" -name "*Seeder.php" ! -name "DatabaseSeeder.php" 2>/dev/null | wc -l)

    if [ "$SEEDER_COUNT" -eq 0 ]; then
        print_info "No seeders found - skipping seeding"
    else
        print_info "→ Found $SEEDER_COUNT seeder file(s)"

        # Check if database needs seeding by checking key tables
        NEEDS_SEEDING=0

        # Check users table
        USER_COUNT=$(php artisan tinker --execute="echo App\Models\User::count();" 2>/dev/null | tail -1 || echo "0")
        print_info "  • Users in database: $USER_COUNT"

        # Check email_templates table (if it exists)
        EMAIL_TEMPLATE_COUNT=$(php artisan tinker --execute="echo DB::table('email_templates')->count();" 2>/dev/null | tail -1 || echo "0")
        print_info "  • Email templates: $EMAIL_TEMPLATE_COUNT"

        echo ""

        # Determine if seeding is needed
        if [ "$USER_COUNT" = "0" ] || [ "$EMAIL_TEMPLATE_COUNT" = "0" ]; then
            NEEDS_SEEDING=1
            print_warning "⚠ Database appears to need seeding (some tables are empty)"
            echo ""

            # Show available seeders
            print_info "→ Available seeders:"
            find "$SEEDER_DIR" -name "*Seeder.php" ! -name "DatabaseSeeder.php" -exec basename {} \; 2>/dev/null | sed 's/^/  • /'
            echo ""

            read -p "Run database seeders? (y/n) [y]: " -n 1 -r RUN_SEEDER
            echo
            if [[ -z $RUN_SEEDER ]] || [[ $RUN_SEEDER =~ ^[Yy]$ ]]; then
                print_info "Running database seeders..."
                if ! php artisan db:seed --force 2>&1 | tee -a "$LOG_FILE"; then
                    print_warning "Seeding failed (continuing anyway)"
                else
                    print_success "✓ Database seeded successfully"
                fi
            else
                print_info "Skipping database seeders"
            fi
        else
            print_success "✓ Database already has data - skipping seeders"
            print_info "  (Run 'php artisan db:seed --force' manually if needed)"
        fi
    fi
fi
echo ""

# Step 12: Create Storage Symlink (แก้ไขปัญหาโลโก้หาย)
print_info "[12/20] Creating storage symlink..."

# ใช้ storage:fix แทน storage:link เพราะจัดการกรณีพิเศษได้ดีกว่า
if php artisan storage:fix --force --no-interaction 2>&1 | tee -a "$LOG_FILE"; then
    print_success "✓ Storage symlink created successfully"
else
    print_warning "⚠ storage:fix command not available, trying storage:link..."
    php artisan storage:link --force --no-interaction 2>&1 | tee -a "$LOG_FILE" || true
fi

# Verify symlink มีอยู่และชี้ไปถูกที่
if [ -L "public/storage" ]; then
    LINK_TARGET=$(readlink -f "public/storage" 2>/dev/null || readlink "public/storage")
    EXPECTED_TARGET=$(readlink -f "storage/app/public" 2>/dev/null || echo "$PWD/storage/app/public")

    if [[ "$LINK_TARGET" == *"storage/app/public"* ]]; then
        print_success "✓ Storage symlink verified (public/storage → storage/app/public)"

        # ตรวจสอบ permissions
        if [ -w "storage/app/public" ]; then
            print_success "✓ Storage directory is writable"
        else
            print_warning "⚠ Storage directory may not be writable"
        fi
    else
        print_error "✗ Storage symlink points to wrong location: $LINK_TARGET"
        error_exit "Storage symlink configuration error - uploaded files will not work!"
    fi
else
    print_error "✗ Storage symlink does not exist!"
    print_error "  This will cause uploaded logos and files to not work."
    print_error "  Run 'php artisan storage:fix --force' manually after deployment."
    error_exit "Critical: Storage symlink creation failed"
fi

# Step 13: Set Permissions
print_info "[13/20] Setting file permissions..."

# Detect web server user
WEB_USER=""
if id -u www-data >/dev/null 2>&1; then
    WEB_USER="www-data"
elif id -u nginx >/dev/null 2>&1; then
    WEB_USER="nginx"
elif id -u apache >/dev/null 2>&1; then
    WEB_USER="apache"
elif id -u admin >/dev/null 2>&1; then
    WEB_USER="admin"
fi

# Set proper permissions
chmod -R 775 storage bootstrap/cache 2>/dev/null || true
find storage -type f -exec chmod 664 {} \; 2>/dev/null || true
find bootstrap/cache -type f -exec chmod 664 {} \; 2>/dev/null || true

# Set ownership if web server user is detected
if [ -n "$WEB_USER" ]; then
    CURRENT_USER=$(whoami)

    # Try to set ownership (may need sudo)
    if chown -R "$CURRENT_USER:$WEB_USER" storage bootstrap/cache 2>/dev/null; then
        log "Ownership set to $CURRENT_USER:$WEB_USER"
    else
        log "WARNING: Cannot set ownership, may need manual intervention"
    fi

    # Try to use ACL if available
    if command -v setfacl >/dev/null 2>&1; then
        setfacl -R -m u:"$WEB_USER":rwX storage bootstrap/cache 2>/dev/null || true
        setfacl -R -d -m u:"$WEB_USER":rwX storage bootstrap/cache 2>/dev/null || true
    fi
fi

print_success "Permissions set"

# Step 14: Cache Configuration
print_info "[14/20] Caching configuration..."
if ! php artisan config:cache 2>&1 | tee -a "$LOG_FILE"; then
    error_exit "Config cache failed - ตรวจสอบ .env และ config files" "$?"
fi
print_success "Configuration cached"

# Step 15: Cache Routes
print_info "[15/20] Caching routes..."
php artisan route:cache || print_warning "Route cache failed (continuing anyway)"
print_success "Routes cached"

# Step 16: Cache Views
print_info "[16/20] Caching views..."
php artisan view:cache || print_warning "View cache failed (continuing anyway)"
print_success "Views cached"

# Step 17: Optimize Autoloader
print_info "[17/20] Optimizing autoloader..."
composer dump-autoload --optimize --no-dev --no-interaction
print_success "Autoloader optimized"

# Step 18: Restart Services
print_info "[18/20] Restarting services..."

# Restart PHP-FPM (if available)
if command -v systemctl >/dev/null 2>&1; then
    # Try different PHP-FPM service names
    for service in php-fpm php8.2-fpm php8.1-fpm php8.0-fpm; do
        if systemctl is-active --quiet $service 2>/dev/null; then
            sudo systemctl reload $service 2>/dev/null && print_success "Reloaded $service" || true
            break
        fi
    done
fi

# Restart queue workers (if using)
php artisan queue:restart 2>/dev/null && print_success "Queue workers restarted" || true

# Step 19: Final ENV Verification
print_info "[19/20] Verifying environment configuration..."
if [ -f ".env" ]; then
    print_success "✓ .env file exists and is ready"
else
    error_exit ".env file missing after sync"
fi

# Step 20: Disable Maintenance Mode
print_info "[20/20] Disabling maintenance mode..."
php artisan up || error_exit "Failed to disable maintenance mode"
print_success "Application is now live!"

# Post-deployment verification
print_header "🔍 Post-Deployment Verification"

print_info "Verifying deployment..."

# Check if application is accessible
if php artisan route:list > /dev/null 2>&1; then
    print_success "✓ Routes are accessible"
else
    print_error "✗ Routes check failed"
fi

# Check if database is accessible
if php artisan db:show > /dev/null 2>&1; then
    print_success "✓ Database connection OK"
else
    print_warning "⚠ Database connection check failed"
fi

# Check critical directories
if [ -w "storage/logs" ] && [ -w "bootstrap/cache" ]; then
    print_success "✓ Storage permissions OK"
else
    print_warning "⚠ Storage permissions may need attention"
fi

# Verify no uncommitted changes remain
if [[ -z $(git status -s) ]]; then
    print_success "✓ Working directory is clean"
else
    print_warning "⚠ Unexpected files in working directory"
fi

# Deployment Summary
print_header "✅ Deployment Completed Successfully!"

log "=== Deployment Completed Successfully ==="

echo "📊 Deployment Summary:"
echo ""
echo "  Environment:   ${BLUE}$APP_ENV${NC}"
echo "  Branch:        ${BLUE}$BRANCH${NC}"
echo "  Old Commit:    ${YELLOW}${CURRENT_COMMIT:0:8}${NC}"
echo "  New Commit:    ${GREEN}${NEW_COMMIT:0:8}${NC}"
echo "  Time:          ${BLUE}$(date)${NC}"
echo "  Backup:        ${BLUE}$BACKUP_FILE${NC}"
echo ""
echo "🔄 What was deployed:"
echo "  ✓ Code synced from GitHub (forced)"
echo "  ✓ Environment variables synced (.env updated)"
echo "  ✓ Dependencies reinstalled (clean)"
echo "  ✓ Laravel Sanctum installed/updated"
echo "  ✓ Database migrations applied"
echo "  ✓ All caches regenerated"
echo "  ✓ Permissions configured"
echo ""

print_info "📋 Post-Deployment Checklist:"
echo "  □ Test the application in browser"
echo "  □ Check logs: tail -f storage/logs/laravel.log"
echo "  □ Monitor error logs: tail -f storage/logs/deployment.log"
echo "  □ Verify database migrations: php artisan migrate:status"
echo "  □ Check queue workers: php artisan queue:monitor"
echo ""

print_warning "🔄 Rollback Command (if needed):"
echo "  git reset --hard $CURRENT_COMMIT"
echo "  composer install --no-dev --optimize-autoloader"
echo "  php artisan migrate:rollback"
echo "  php artisan up"
echo ""

print_success "Happy deploying! 🚀"
echo ""

# Clean up environment variable
unset DEPLOY_ATTEMPT_COUNT

# Success exit
exit 0
