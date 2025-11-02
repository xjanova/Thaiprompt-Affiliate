#!/bin/bash

# Migration Helper Script
# This script helps run pending migrations on the production server

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

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

echo ""
echo -e "${GREEN}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║${NC}    ${BLUE}🔄 Database Migration Helper${NC}                              ${GREEN}║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""

# Check if .env exists
if [ ! -f .env ]; then
    print_error ".env file not found!"
    exit 1
fi
print_success ".env file found"

# Verify database connection
print_info "Verifying database connection..."
if ! php artisan db:show >/dev/null 2>&1; then
    print_error "Database connection failed - check your .env credentials"
    exit 1
fi
print_success "Database connection OK"
echo ""

# Show current migration status (BEFORE)
print_info "Current migration status (BEFORE):"
php artisan migrate:status 2>/dev/null | tail -20 || true
echo ""

# Check for pending migrations
PENDING_COUNT=$(php artisan migrate:status --pending 2>/dev/null | grep -c "Pending" || echo "0")
TOTAL_MIGRATIONS=$(php artisan migrate:status 2>/dev/null | grep -c "migration" || echo "0")

print_info "Migration Analysis:"
echo "  • Total migrations: $TOTAL_MIGRATIONS"
echo "  • Pending migrations: $PENDING_COUNT"
echo ""

# Run migrations if needed
if [ "$PENDING_COUNT" != "0" ] && [ "$PENDING_COUNT" != "" ]; then
    print_warning "Found $PENDING_COUNT pending migration(s)"
    echo ""

    # Show what will be migrated
    print_info "Migrations to be applied:"
    php artisan migrate:status --pending 2>/dev/null | grep "Pending" | sed 's/^/  • /' || true
    echo ""

    # Backup database before migration
    print_info "Creating database backup..."

    # Get database info from .env
    DB_CONNECTION=$(grep "^DB_CONNECTION=" .env | cut -d '=' -f2)
    DB_DATABASE=$(grep "^DB_DATABASE=" .env | cut -d '=' -f2)
    DB_USERNAME=$(grep "^DB_USERNAME=" .env | cut -d '=' -f2)
    DB_PASSWORD=$(grep "^DB_PASSWORD=" .env | cut -d '=' -f2)
    DB_HOST=$(grep "^DB_HOST=" .env | cut -d '=' -f2)

    BACKUP_DIR="backups"
    mkdir -p "$BACKUP_DIR"
    MIGRATION_BACKUP="$BACKUP_DIR/pre_migration_$(date +'%Y%m%d_%H%M%S').sql"

    if [ "$DB_CONNECTION" = "mysql" ] && command -v mysqldump >/dev/null 2>&1; then
        if [ -z "$DB_PASSWORD" ]; then
            mysqldump -h "$DB_HOST" -u "$DB_USERNAME" "$DB_DATABASE" > "$MIGRATION_BACKUP" 2>/dev/null || \
                print_warning "Backup failed (continuing anyway)"
        else
            mysqldump -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" > "$MIGRATION_BACKUP" 2>/dev/null || \
                print_warning "Backup failed (continuing anyway)"
        fi
        if [ -f "$MIGRATION_BACKUP" ]; then
            print_success "Database backed up: $MIGRATION_BACKUP"
        fi
    fi
    echo ""

    # Confirm before running
    read -p "$(echo -e ${YELLOW}Run migrations now? [Y/n]: ${NC})" -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Nn]$ ]]; then
        print_info "Executing migrations..."
        if php artisan migrate --force; then
            print_success "Migrations applied successfully!"
        else
            print_error "Migration failed!"
            echo ""
            print_warning "Rollback information:"
            echo "  • Backup file: $MIGRATION_BACKUP"
            echo "  • Rollback command: php artisan migrate:rollback"
            echo "  • Restore DB: mysql -u $DB_USERNAME -p $DB_DATABASE < $MIGRATION_BACKUP"
            exit 1
        fi
    else
        print_info "Migration skipped"
        exit 0
    fi
else
    print_success "No pending migrations - Database schema is up to date"
fi

echo ""

# Show migration status (AFTER)
print_info "Migration status (AFTER):"
php artisan migrate:status 2>/dev/null | tail -20 || true
echo ""

# Verify migration integrity
print_info "Verifying database integrity..."
MIGRATED_COUNT=$(php artisan migrate:status 2>/dev/null | grep -c "Ran" || echo "0")
echo "  • Successfully migrated: $MIGRATED_COUNT migrations"

NEW_PENDING=$(php artisan migrate:status --pending 2>/dev/null | grep -c "Pending" || echo "0")
if [ "$NEW_PENDING" = "0" ]; then
    print_success "All migrations completed successfully!"
else
    print_warning "Still have $NEW_PENDING pending migrations"
fi

echo ""
print_success "Done! 🚀"
echo ""
