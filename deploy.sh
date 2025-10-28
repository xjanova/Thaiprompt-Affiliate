#!/bin/bash

# ============================================
# ThaiPrompt Marketplace - Production Deployment Script
# ============================================
# ใช้ script นี้สำหรับ deploy โปรเจคจาก GitHub
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

# Check if running from project directory
if [ ! -f "composer.json" ] || [ ! -f "artisan" ]; then
    print_error "This script must be run from the project root directory!"
    exit 1
fi

print_header "ThaiPrompt Marketplace - Deployment"

# Get current directory
PROJECT_DIR=$(pwd)
print_info "Project directory: $PROJECT_DIR"

# Step 1: Enable Maintenance Mode
print_header "Step 1/8: Enable Maintenance Mode"
if [ -f "artisan" ]; then
    php artisan down --retry=60 || print_warning "Could not enable maintenance mode"
    print_success "Maintenance mode enabled"
else
    print_warning "artisan not found, skipping maintenance mode"
fi

# Step 2: Pull Latest Code from GitHub
print_header "Step 2/8: Pull Latest Code from GitHub"
print_info "Fetching updates from remote repository..."

# Get current branch
CURRENT_BRANCH=$(git branch --show-current)
print_info "Current branch: $CURRENT_BRANCH"

# Stash local changes if any
if ! git diff-index --quiet HEAD --; then
    print_warning "Local changes detected, stashing..."
    git stash
    STASHED=true
else
    STASHED=false
fi

# Pull latest changes
git fetch origin
git pull origin $CURRENT_BRANCH

# Pop stash if we stashed changes
if [ "$STASHED" = true ]; then
    print_info "Applying stashed changes..."
    git stash pop || print_warning "Could not apply stashed changes"
fi

print_success "Code updated from GitHub"

# Step 3: Install/Update Composer Dependencies
print_header "Step 3/8: Install Composer Dependencies"
print_info "Installing PHP dependencies..."
composer install --no-interaction --optimize-autoloader --no-dev
print_success "Composer dependencies installed"

# Step 4: Install/Update NPM Dependencies
print_header "Step 4/8: Install NPM Dependencies"
print_info "Installing JavaScript dependencies..."
npm ci --only=production
print_success "NPM dependencies installed"

# Step 5: Build Frontend Assets
print_header "Step 5/8: Build Frontend Assets"
print_info "Building production assets..."
npm run build
print_success "Frontend assets built"

# Step 6: Run Database Migrations
print_header "Step 6/8: Run Database Migrations"
print_info "Running database migrations..."
php artisan migrate --force
print_success "Database migrations completed"

# Step 7: Clear and Cache Configuration
print_header "Step 7/8: Optimize Application"
print_info "Clearing old cache..."

# Clear all caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

print_info "Caching configuration..."

# Cache everything for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

print_success "Application optimized"

# Step 8: Set Permissions
print_header "Step 8/8: Set Permissions"
print_info "Setting proper file permissions..."

# Set ownership (if www-data user exists)
if id "www-data" &>/dev/null; then
    sudo chown -R www-data:www-data $PROJECT_DIR 2>/dev/null || print_warning "Could not change ownership"
fi

# Set permissions
chmod -R 755 $PROJECT_DIR/storage
chmod -R 755 $PROJECT_DIR/bootstrap/cache

print_success "Permissions set"

# Disable Maintenance Mode
print_header "Finalizing Deployment"
if [ -f "artisan" ]; then
    php artisan up
    print_success "Maintenance mode disabled"
fi

# Restart Services (optional)
print_info "Restarting services..."

# Restart PHP-FPM if running
if systemctl is-active --quiet php8.2-fpm; then
    sudo systemctl reload php8.2-fpm
    print_success "PHP-FPM reloaded"
elif systemctl is-active --quiet php8.1-fpm; then
    sudo systemctl reload php8.1-fpm
    print_success "PHP-FPM reloaded"
fi

# Restart queue workers if using supervisor
if command -v supervisorctl &> /dev/null; then
    sudo supervisorctl restart all 2>/dev/null && print_success "Queue workers restarted" || true
fi

# Final Summary
print_header "Deployment Complete!"

echo ""
echo -e "${GREEN}✅ Deployment successful!${NC}"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo -e "${BLUE}📋 Deployment Summary:${NC}"
echo ""
echo "  Branch: $CURRENT_BRANCH"
echo "  Commit: $(git rev-parse --short HEAD)"
echo "  Date: $(date '+%Y-%m-%d %H:%M:%S')"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo -e "${YELLOW}🔍 Next Steps:${NC}"
echo ""
echo "  1. Test your application: http://your-domain.com"
echo "  2. Check logs: tail -f storage/logs/laravel.log"
echo "  3. Monitor queue: php artisan queue:work"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
