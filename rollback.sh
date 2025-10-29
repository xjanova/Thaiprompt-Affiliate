#!/bin/bash

# TP-Affiliate Rollback Script
# Quick Rollback to Previous Deployment

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

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

# Header
echo ""
echo "╔══════════════════════════════════════════════════╗"
echo "║   🔄 TP-Affiliate Rollback Script               ║"
echo "║   Emergency Rollback to Previous Version        ║"
echo "╚══════════════════════════════════════════════════╝"
echo ""

# Get target commit
if [ -n "$1" ]; then
    TARGET_COMMIT="$1"
else
    print_info "Recent commits:"
    git log --oneline -5
    echo ""
    read -p "Enter commit hash to rollback to: " TARGET_COMMIT
fi

# Validate commit
if ! git cat-file -e "$TARGET_COMMIT" 2>/dev/null; then
    print_error "Invalid commit hash: $TARGET_COMMIT"
    exit 1
fi

print_warning "⚠️  WARNING: This will rollback your application!"
print_info "Current commit: $(git rev-parse --short HEAD)"
print_info "Target commit:  $TARGET_COMMIT"
echo ""
read -p "Are you sure you want to rollback? (yes/no): " -r
if [[ ! $REPLY =~ ^[Yy][Ee][Ss]$ ]]; then
    print_info "Rollback cancelled"
    exit 0
fi

echo ""
print_info "Starting rollback process..."
echo ""

# Step 1: Enable Maintenance Mode
print_info "[1/8] Enabling maintenance mode..."
php artisan down --retry=60
print_success "Maintenance mode enabled"

# Step 2: Rollback Git
print_info "[2/8] Rolling back code..."
git reset --hard "$TARGET_COMMIT"
print_success "Code rolled back to $TARGET_COMMIT"

# Step 3: Install Dependencies
print_info "[3/8] Installing dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction
print_success "Dependencies installed"

# Step 4: Clear Cache
print_info "[4/8] Clearing cache..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
print_success "Cache cleared"

# Step 5: Rollback Migrations (optional)
read -p "Rollback database migrations? (y/n) [n]: " -n 1 -r ROLLBACK_DB
echo
if [[ $ROLLBACK_DB =~ ^[Yy]$ ]]; then
    print_info "[5/8] Rolling back database migrations..."
    read -p "How many migration steps to rollback? [1]: " STEPS
    STEPS=${STEPS:-1}
    php artisan migrate:rollback --step=$STEPS --force
    print_success "Migrations rolled back"
else
    print_info "[5/8] Skipping database rollback"
fi

# Step 6: Optimize
print_info "[6/8] Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
print_success "Application optimized"

# Step 7: Restart Services
print_info "[7/8] Restarting services..."
if command -v systemctl >/dev/null 2>&1; then
    for service in php-fpm php8.2-fpm php8.1-fpm php8.0-fpm; do
        if systemctl is-active --quiet $service 2>/dev/null; then
            sudo systemctl reload $service 2>/dev/null && print_success "Reloaded $service" || true
            break
        fi
    done
fi
php artisan queue:restart 2>/dev/null || true

# Step 8: Disable Maintenance Mode
print_info "[8/8] Disabling maintenance mode..."
php artisan up
print_success "Application is now live!"

echo ""
print_success "✅ Rollback completed successfully!"
echo ""
print_info "Current commit: $(git rev-parse --short HEAD)"
print_warning "Please test your application thoroughly"
echo ""
