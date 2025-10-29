#!/bin/bash

# TP-Affiliate Deployment Script
# One-Command Production Deployment

set -e

echo ""
echo "╔══════════════════════════════════════════════════╗"
echo "║   🚀 TP-Affiliate Deployment Script             ║"
echo "║   Production Deployment Automation              ║"
echo "╚══════════════════════════════════════════════════╝"
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
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

# Check if .env exists
if [ ! -f .env ]; then
    print_error ".env file not found!"
    print_info "Please copy .env.example to .env and configure it first"
    exit 1
fi

# Check if APP_ENV is production
APP_ENV=$(grep "^APP_ENV=" .env | cut -d '=' -f2)
if [ "$APP_ENV" != "production" ]; then
    print_warning "APP_ENV is not set to production (current: $APP_ENV)"
    read -p "Continue anyway? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

echo "════════════════════════════════════════"
echo "  📦 Starting Deployment Process"
echo "════════════════════════════════════════"
echo ""

# Step 1: Enable Maintenance Mode
print_info "[1/10] Enabling maintenance mode..."
php artisan down --retry=60 || print_warning "Maintenance mode may already be enabled"
print_success "Maintenance mode enabled"

# Step 2: Pull Latest Code
print_info "[2/10] Pulling latest code from git..."
git pull origin main || git pull origin master || print_warning "Could not pull from git"
print_success "Code updated"

# Step 3: Install/Update Composer Dependencies
print_info "[3/10] Installing composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction
print_success "Composer dependencies installed"

# Step 4: Clear Cache
print_info "[4/10] Clearing application cache..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
print_success "Cache cleared"

# Step 5: Run Migrations
print_info "[5/10] Running database migrations..."
php artisan migrate --force
print_success "Migrations completed"

# Step 6: Cache Configuration
print_info "[6/10] Caching configuration..."
php artisan config:cache
print_success "Configuration cached"

# Step 7: Cache Routes
print_info "[7/10] Caching routes..."
php artisan route:cache
print_success "Routes cached"

# Step 8: Cache Views
print_info "[8/10] Caching views..."
php artisan view:cache
print_success "Views cached"

# Step 9: Optimize Autoloader
print_info "[9/10] Optimizing autoloader..."
composer dump-autoload --optimize --no-dev
print_success "Autoloader optimized"

# Step 10: Disable Maintenance Mode
print_info "[10/10] Disabling maintenance mode..."
php artisan up
print_success "Application is now live!"

echo ""
echo "════════════════════════════════════════"
print_success "Deployment Completed Successfully! 🎉"
echo "════════════════════════════════════════"
echo ""

# Display deployment info
print_info "Deployment Summary:"
echo ""
echo "  - Environment: ${BLUE}$APP_ENV${NC}"
echo "  - Time: ${BLUE}$(date)${NC}"
echo "  - Git Commit: ${BLUE}$(git rev-parse --short HEAD)${NC}"
echo ""

print_info "Post-Deployment Checklist:"
echo "  □ Test the application"
echo "  □ Check logs: storage/logs/laravel.log"
echo "  □ Monitor performance"
echo "  □ Verify database migrations"
echo ""

print_success "Happy deploying! 🚀"
echo ""
