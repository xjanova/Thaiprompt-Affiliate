#!/bin/bash

##############################################################################
# Ticket System Deployment Script
# Version: 1.0.0
# Description: Automated deployment script for Ticket Support System
##############################################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Functions
print_header() {
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}========================================${NC}"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

# Check if running as correct user
check_user() {
    if [ "$EUID" -eq 0 ]; then
        print_error "Please do not run this script as root"
        exit 1
    fi
}

# Check PHP version
check_php() {
    print_info "Checking PHP version..."
    PHP_VERSION=$(php -v | head -n 1 | cut -d " " -f 2 | cut -c 1-3)
    if (( $(echo "$PHP_VERSION >= 8.1" | bc -l) )); then
        print_success "PHP version: $PHP_VERSION"
    else
        print_error "PHP 8.1 or higher is required. Current version: $PHP_VERSION"
        exit 1
    fi
}

# Check database connection
check_database() {
    print_info "Checking database connection..."
    if php artisan db:show &> /dev/null; then
        print_success "Database connection successful"
    else
        print_error "Database connection failed. Please check your .env file"
        exit 1
    fi
}

# Backup database
backup_database() {
    print_info "Creating database backup..."
    BACKUP_FILE="backup_$(date +%Y%m%d_%H%M%S).sql"

    DB_DATABASE=$(grep DB_DATABASE .env | cut -d '=' -f2)
    DB_USERNAME=$(grep DB_USERNAME .env | cut -d '=' -f2)
    DB_PASSWORD=$(grep DB_PASSWORD .env | cut -d '=' -f2)

    if mysqldump -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" > "$BACKUP_FILE" 2>/dev/null; then
        print_success "Database backed up to: $BACKUP_FILE"
    else
        print_warning "Database backup failed, but continuing..."
    fi
}

# Run migrations
run_migrations() {
    print_info "Running migrations..."

    # Check if migrations exist
    if [ ! -f "database/migrations/2025_11_07_210000_create_ticket_categories_table.php" ]; then
        print_error "Migration files not found!"
        exit 1
    fi

    # Run migrations
    if php artisan migrate --force; then
        print_success "Migrations completed successfully"
    else
        print_error "Migration failed!"
        exit 1
    fi
}

# Clear caches
clear_caches() {
    print_info "Clearing caches..."

    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    php artisan cache:clear

    print_success "Caches cleared"
}

# Optimize application
optimize_app() {
    print_info "Optimizing application..."

    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

    print_success "Application optimized"
}

# Check routes
check_routes() {
    print_info "Verifying routes..."

    TICKET_ROUTES=$(php artisan route:list | grep -c "tickets" || true)

    if [ "$TICKET_ROUTES" -gt 0 ]; then
        print_success "Found $TICKET_ROUTES ticket routes"
    else
        print_error "No ticket routes found!"
        exit 1
    fi
}

# Run tests
run_tests() {
    print_info "Running tests..."

    if [ -f "tests/Feature/TicketSystemTest.php" ]; then
        if php artisan test --filter=TicketSystemTest; then
            print_success "All tests passed"
        else
            print_warning "Some tests failed, but continuing..."
        fi
    else
        print_warning "Test file not found, skipping tests..."
    fi
}

# Display final information
display_info() {
    print_header "Deployment Complete!"

    echo ""
    print_success "Ticket System has been deployed successfully!"
    echo ""
    echo "Access URLs:"
    echo "  - Admin: ${BLUE}/admin/tickets${NC}"
    echo "  - User:  ${BLUE}/user/tickets${NC}"
    echo ""
    echo "Next Steps:"
    echo "  1. Visit ${BLUE}/admin/tickets${NC} to verify admin interface"
    echo "  2. Visit ${BLUE}/user/tickets${NC} to test user interface"
    echo "  3. Create a test ticket"
    echo "  4. Check the documentation: TICKET_SYSTEM_README.md"
    echo ""
    print_info "Happy ticketing! 🎫"
}

# Main deployment process
main() {
    print_header "Ticket System Deployment"

    echo ""
    print_info "Starting deployment process..."
    echo ""

    # Pre-deployment checks
    check_user
    check_php
    check_database

    # Ask for confirmation
    echo ""
    read -p "Do you want to continue with the deployment? (y/n) " -n 1 -r
    echo ""

    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        print_warning "Deployment cancelled"
        exit 0
    fi

    echo ""

    # Deployment steps
    backup_database
    run_migrations
    clear_caches
    check_routes
    optimize_app

    # Optional: Run tests
    echo ""
    read -p "Do you want to run tests? (y/n) " -n 1 -r
    echo ""

    if [[ $REPLY =~ ^[Yy]$ ]]; then
        run_tests
    fi

    echo ""
    display_info
}

# Run main function
main
