#!/bin/bash

# ============================================
# ThaiPrompt Marketplace - Deployment Script
# ============================================

set -e  # Exit on error

echo "🚀 Starting deployment..."
echo "================================"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
PROJECT_DIR="/var/www/thaiprompt"
BRANCH="main"

# Functions
print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_info() {
    echo -e "${YELLOW}ℹ $1${NC}"
}

# Check if running as deployer user
if [ "$USER" != "deployer" ] && [ "$USER" != "root" ]; then
    print_error "Please run as deployer user or root"
    exit 1
fi

cd "$PROJECT_DIR" || exit 1

# 1. Enable maintenance mode
echo ""
print_info "Step 1/11: Enabling maintenance mode..."
php artisan down || print_info "Already in maintenance mode"
print_success "Maintenance mode enabled"

# 2. Pull latest code
echo ""
print_info "Step 2/11: Pulling latest code from $BRANCH..."
git fetch origin
git reset --hard origin/$BRANCH
print_success "Code updated"

# 3. Install composer dependencies
echo ""
print_info "Step 3/11: Installing composer dependencies..."
composer install --optimize-autoloader --no-dev --no-interaction
print_success "Composer dependencies installed"

# 4. Install NPM dependencies
echo ""
print_info "Step 4/11: Installing NPM dependencies..."
npm ci --production=false
print_success "NPM dependencies installed"

# 5. Build assets
echo ""
print_info "Step 5/11: Building frontend assets..."
npm run build
print_success "Assets built"

# 6. Run database migrations
echo ""
print_info "Step 6/11: Running database migrations..."
php artisan migrate --force
print_success "Migrations completed"

# 7. Clear old cache
echo ""
print_info "Step 7/11: Clearing old cache..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
print_success "Old cache cleared"

# 8. Cache configuration
echo ""
print_info "Step 8/11: Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
print_success "Configuration cached"

# 9. Update file permissions
echo ""
print_info "Step 9/11: Updating file permissions..."
chown -R deployer:www-data "$PROJECT_DIR"
chmod -R 775 "$PROJECT_DIR/storage"
chmod -R 775 "$PROJECT_DIR/bootstrap/cache"
print_success "Permissions updated"

# 10. Restart queue workers
echo ""
print_info "Step 10/11: Restarting queue workers..."
if command -v supervisorctl &> /dev/null; then
    supervisorctl restart thaiprompt-worker:* 2>/dev/null || print_info "Queue workers not configured"
    print_success "Queue workers restarted"
else
    print_info "Supervisor not installed, skipping queue restart"
fi

# 11. Reload PHP-FPM
echo ""
print_info "Step 11/11: Reloading PHP-FPM..."
if command -v systemctl &> /dev/null; then
    systemctl reload php8.1-fpm || systemctl reload php8.2-fpm || systemctl reload php-fpm
    print_success "PHP-FPM reloaded"
else
    print_info "systemctl not available, skipping PHP-FPM reload"
fi

# Disable maintenance mode
echo ""
print_info "Disabling maintenance mode..."
php artisan up
print_success "Application is back online"

# Summary
echo ""
echo "================================"
echo -e "${GREEN}✅ Deployment completed successfully!${NC}"
echo "================================"
echo ""
echo "Application URL: $(php artisan env:get APP_URL 2>/dev/null || echo 'Check .env')"
echo "Deployed at: $(date)"
echo ""
print_info "Next steps:"
echo "  1. Test the application in browser"
echo "  2. Check logs: tail -f storage/logs/laravel.log"
echo "  3. Monitor queue: php artisan queue:work --verbose"
echo ""
