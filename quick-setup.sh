#!/bin/bash

# Quick Setup Script for Login System
# This script will set up the basic login system without Docker

set -e  # Exit on error

echo ""
echo "========================================="
echo "  Quick Setup - Login System"
echo "========================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print success
success() {
    echo -e "${GREEN}✓${NC} $1"
}

# Function to print error
error() {
    echo -e "${RED}✗${NC} $1"
}

# Function to print info
info() {
    echo -e "${YELLOW}ℹ${NC} $1"
}

# Check if we're in the right directory
if [ ! -f "composer.json" ]; then
    error "composer.json not found. Please run this script from the project root."
    exit 1
fi

info "Starting quick setup..."
echo ""

# Step 1: Create .env if not exists
echo "[1/8] Checking .env file..."
if [ ! -f ".env" ]; then
    cp .env.example .env
    success ".env file created"
else
    success ".env file already exists"
fi

# Step 2: Create database directory
echo ""
echo "[2/8] Setting up SQLite database..."
mkdir -p database
touch database/database.sqlite
success "SQLite database file created"

# Step 3: Set permissions
echo ""
echo "[3/8] Setting permissions..."
chmod -R 775 storage bootstrap/cache 2>/dev/null || true
success "Permissions set for storage and bootstrap/cache"

# Step 4: Install Composer dependencies (if composer exists)
echo ""
echo "[4/8] Installing Composer dependencies..."
if command -v composer &> /dev/null; then
    composer install --no-interaction --prefer-dist
    success "Composer dependencies installed"
else
    error "Composer not found. Please install composer and run: composer install"
    info "You can continue with Docker setup instead"
fi

# Step 5: Generate APP_KEY (if php exists)
echo ""
echo "[5/8] Generating APP_KEY..."
if command -v php &> /dev/null; then
    php artisan key:generate --force
    success "APP_KEY generated"
else
    error "PHP not found. APP_KEY will need to be generated manually"
fi

# Step 6: Run migrations (if php exists)
echo ""
echo "[6/8] Running database migrations..."
if command -v php &> /dev/null; then
    php artisan migrate --force
    success "Database migrations completed"
else
    error "PHP not found. Migrations will need to be run manually"
fi

# Step 7: Seed database with test users (if php exists)
echo ""
echo "[7/8] Creating test users..."
if command -v php &> /dev/null; then
    php artisan db:seed --class=SimpleUserSeeder --force
    success "Test users created"
else
    error "PHP not found. Test users will need to be created manually"
fi

# Step 8: Clear cache (if php exists)
echo ""
echo "[8/8] Clearing cache..."
if command -v php &> /dev/null; then
    php artisan config:clear
    php artisan cache:clear
    php artisan view:clear
    success "Cache cleared"
fi

# Final message
echo ""
echo "========================================="
echo "  Setup Complete!"
echo "========================================="
echo ""

if command -v php &> /dev/null; then
    info "You can now start the server with:"
    echo "  php artisan serve"
    echo ""
    info "Then visit: http://localhost:8000/login"
else
    info "Since PHP is not available, you have two options:"
    echo ""
    echo "Option 1: Use Docker"
    echo "  docker-compose up -d"
    echo "  docker-compose exec app php artisan migrate"
    echo "  docker-compose exec app php artisan db:seed --class=SimpleUserSeeder"
    echo "  Visit: http://localhost:8000/login"
    echo ""
    echo "Option 2: Install PHP 8.1+ and Composer"
    echo "  Then run this script again"
fi

echo ""
echo "Test Accounts:"
echo "  Email: admin@test.com | Password: password"
echo "  Email: user@test.com  | Password: password"
echo "  Email: test@test.com  | Password: 123456"
echo ""
echo "========================================="
