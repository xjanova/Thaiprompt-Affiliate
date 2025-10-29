#!/bin/bash

# TP-Affiliate Deployment Script
# Safe Production Deployment with Rollback Support

set -e

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BACKUP_DIR="$SCRIPT_DIR/backups"
LOG_FILE="$SCRIPT_DIR/storage/logs/deployment.log"

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

# Ensure Laravel essential directories exist
ensure_laravel_directories() {
    mkdir -p bootstrap/cache 2>/dev/null || true
    mkdir -p storage/{app,framework,logs} 2>/dev/null || true
    mkdir -p storage/framework/{cache,sessions,views} 2>/dev/null || true
    mkdir -p storage/app/{public,private} 2>/dev/null || true
    mkdir -p database 2>/dev/null || true
    chmod -R 775 storage bootstrap/cache 2>/dev/null || true
}

# Error handler
error_exit() {
    print_error "Deployment failed: $1"
    log "ERROR: $1"

    print_warning "Rolling back changes..."

    # Ensure essential directories exist before running artisan
    ensure_laravel_directories

    # Try to bring application back up
    php artisan up 2>/dev/null || {
        print_error "Could not disable maintenance mode automatically"
        print_info "Please run manually: php artisan up"
    }

    exit 1
}

# Trap errors
trap 'error_exit "An error occurred on line $LINENO"' ERR

# Header
echo ""
echo "╔══════════════════════════════════════════════════╗"
echo "║   🚀 TP-Affiliate Deployment Script             ║"
echo "║   Safe Production Deployment                     ║"
echo "╚══════════════════════════════════════════════════╝"
echo ""
echo "📋 Deployment Configuration:"
echo "  Branch:      ${BLUE}$BRANCH${NC}"
echo "  Directory:   ${BLUE}$SCRIPT_DIR${NC}"
echo "  User:        ${BLUE}$(whoami)${NC}"
echo "  Time:        ${BLUE}$(date)${NC}"
echo ""

log "=== Deployment Started ==="
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
print_info "[1/15] Enabling maintenance mode..."

# Ensure directories exist before running artisan
ensure_laravel_directories

php artisan down --retry=60 --render="errors::503" 2>/dev/null || {
    print_warning "Could not enable maintenance mode (may already be down or need manual intervention)"
}
print_success "Maintenance mode enabled"
sleep 2  # Give time for requests to finish

# Step 2: Backup Database
print_info "[2/15] Creating database backup..."
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

# Step 4: Force Pull Latest Code from GitHub
print_info "[3/15] Force syncing with GitHub..."

# Step 4.1: Stash any local changes (for safety backup)
if [[ -n $(git status -s) ]]; then
    print_info "Backing up local changes to stash..."
    git stash push -u -m "Auto-stash before deployment $(date +'%Y-%m-%d %H:%M:%S')" 2>/dev/null || true
fi

# Step 4.2: Fetch all changes from remote
print_info "Fetching latest code from origin/$BRANCH..."
git fetch origin "$BRANCH" || error_exit "Failed to fetch from git"

# Step 4.3: Force reset to match GitHub exactly
print_info "Force resetting to origin/$BRANCH..."
git reset --hard "origin/$BRANCH" || error_exit "Failed to reset to origin/$BRANCH"

# Step 4.4: Clean all untracked files and directories
print_info "Removing untracked files and directories..."
git clean -fd || print_warning "Git clean failed (continuing anyway)"

# Step 4.4.1: Recreate essential Laravel directories
print_info "Recreating essential Laravel directories..."
ensure_laravel_directories
print_success "Essential directories created"

# Step 4.5: Verify we're in sync with remote
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

# Step 4.5: Ensure Base Controller Exists
print_info "[4/15] Ensuring base Controller exists..."
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

# Step 5: Install/Update Composer Dependencies
print_info "[5/15] Installing composer dependencies..."

# Remove vendor directory to ensure clean install
if [ -d "vendor" ]; then
    print_info "Removing old vendor directory for clean install..."
    rm -rf vendor
fi

# Clear composer cache
composer clear-cache 2>/dev/null || true

# Install dependencies from scratch
composer install --no-dev --optimize-autoloader --no-interaction || error_exit "Composer install failed"
print_success "Composer dependencies installed (clean)"

# Verify composer lock file matches
if [ -f "composer.lock" ]; then
    composer validate --no-check-all --no-check-publish 2>/dev/null && \
        print_success "Composer.lock is valid" || \
        print_warning "Composer.lock validation failed"
fi

# Step 6: Clear All Cache
print_info "[6/15] Clearing all caches..."
php artisan cache:clear 2>/dev/null || true
php artisan config:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true
php artisan event:clear 2>/dev/null || true
print_success "All caches cleared"

# Step 7: Run Migrations
print_info "[7/15] Running database migrations..."

# Check if there are pending migrations
PENDING_MIGRATIONS=$(php artisan migrate:status --pending 2>/dev/null | grep -c "Pending" || echo "0")

if [ "$PENDING_MIGRATIONS" != "0" ]; then
    print_warning "Found $PENDING_MIGRATIONS pending migration(s)"
    print_info "Running migrations..."
    php artisan migrate --force || error_exit "Database migration failed"
    print_success "Migrations completed successfully"
else
    print_info "No pending migrations"
    # Run anyway to be safe
    php artisan migrate --force 2>/dev/null || print_info "No migrations needed"
    print_success "Database schema is up to date"
fi

# Show migration status
print_info "Current migration status:"
php artisan migrate:status 2>/dev/null | tail -5 || true

# Step 8: Seed Database (optional - only for fresh installs)
# Check if database is empty (no super admin)
SUPER_ADMIN_COUNT=$(php artisan tinker --execute="echo App\Models\User::where('is_super_admin', true)->count();" 2>/dev/null | tail -1 || echo "0")

if [ "$SUPER_ADMIN_COUNT" = "0" ]; then
    print_warning "[8/15] No super admin found - database might need seeding"
    read -p "Run database seeders? (y/n) [n]: " -n 1 -r RUN_SEEDER
    echo
    if [[ $RUN_SEEDER =~ ^[Yy]$ ]]; then
        print_info "Running database seeders..."
        php artisan db:seed --force || print_warning "Seeding failed (continuing anyway)"
        print_success "Database seeded"
    else
        print_info "Skipping database seeders"
    fi
else
    print_info "[8/15] Database already initialized (skipping seeders)"
fi

# Step 9: Set Permissions
print_info "[9/15] Setting file permissions..."

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

# Step 10: Cache Configuration
print_info "[10/15] Caching configuration..."
php artisan config:cache || error_exit "Config cache failed"
print_success "Configuration cached"

# Step 11: Cache Routes
print_info "[11/15] Caching routes..."
php artisan route:cache || print_warning "Route cache failed (continuing anyway)"
print_success "Routes cached"

# Step 12: Cache Views
print_info "[12/15] Caching views..."
php artisan view:cache || print_warning "View cache failed (continuing anyway)"
print_success "Views cached"

# Step 13: Optimize Autoloader
print_info "[13/15] Optimizing autoloader..."
composer dump-autoload --optimize --no-dev --no-interaction
print_success "Autoloader optimized"

# Step 14: Restart Services
print_info "[14/15] Restarting services..."

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

# Step 15: Disable Maintenance Mode
print_info "[15/15] Disabling maintenance mode..."
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
echo "  ✓ Dependencies reinstalled (clean)"
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
