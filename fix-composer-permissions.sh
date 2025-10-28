#!/bin/bash

##############################################################################
# Fix Composer Permission Issues
#
# This script resolves composer install errors caused by permission issues
# when trying to remove development packages on production server.
#
# Usage: Run this script on the production server when composer fails
##############################################################################

set -e  # Exit on error

echo "================================"
echo "Fix Composer Permissions"
echo "================================"
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Get the script directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

echo -e "${BLUE}Current directory: $(pwd)${NC}"
echo ""

# Function to show menu
show_menu() {
    echo "Choose a solution:"
    echo ""
    echo "1) Fix permissions and retry composer install (Recommended)"
    echo "2) Clean vendor directory and fresh install"
    echo "3) Exit"
    echo ""
    read -p "Enter your choice [1-3]: " choice
}

# Function to fix permissions
fix_permissions() {
    echo -e "${YELLOW}Fixing vendor directory permissions...${NC}"

    if [ -d "vendor" ]; then
        echo "  • Setting write permissions on vendor directory..."
        chmod -R u+w vendor/ 2>/dev/null || {
            echo -e "${YELLOW}  ⚠ Some files couldn't be changed. Trying with sudo...${NC}"
            sudo chmod -R u+w vendor/ 2>/dev/null || true
        }

        echo "  • Ensuring ownership is correct..."
        sudo chown -R $(whoami):$(whoami) vendor/ 2>/dev/null || {
            echo -e "${YELLOW}  ⚠ Couldn't change ownership (may need root access)${NC}"
        }

        echo -e "${GREEN}✓ Permissions fixed${NC}"
    else
        echo -e "${YELLOW}  ⚠ Vendor directory not found${NC}"
    fi
    echo ""
}

# Function to clean install
clean_install() {
    echo -e "${YELLOW}Performing clean installation...${NC}"

    # Backup composer.lock
    if [ -f "composer.lock" ]; then
        echo "  • Backing up composer.lock..."
        cp composer.lock composer.lock.backup
        echo -e "${GREEN}✓ Backup created: composer.lock.backup${NC}"
    fi

    # Remove vendor directory
    if [ -d "vendor" ]; then
        echo "  • Removing vendor directory..."
        rm -rf vendor/ 2>/dev/null || {
            echo -e "${YELLOW}  ⚠ Need elevated permissions...${NC}"
            sudo rm -rf vendor/ || {
                echo -e "${RED}✗ Failed to remove vendor directory${NC}"
                echo "  Please manually run: sudo rm -rf vendor/"
                return 1
            }
        }
        echo -e "${GREEN}✓ Vendor directory removed${NC}"
    fi

    # Remove composer cache
    echo "  • Clearing composer cache..."
    composer clear-cache 2>/dev/null || true
    echo -e "${GREEN}✓ Cache cleared${NC}"
    echo ""
}

# Function to run composer install
run_composer_install() {
    echo -e "${YELLOW}Running composer install...${NC}"
    echo ""

    # Install without dev dependencies (production mode)
    composer install --no-dev --optimize-autoloader --no-interaction || {
        echo -e "${RED}✗ Composer install failed${NC}"
        echo ""
        echo "Try running manually with verbose output:"
        echo "  composer install --no-dev --optimize-autoloader -vvv"
        return 1
    }

    echo ""
    echo -e "${GREEN}✓ Composer install completed successfully!${NC}"
    echo ""
}

# Main script
show_menu

case $choice in
    1)
        echo ""
        echo "=== Option 1: Fix Permissions and Retry ==="
        echo ""
        fix_permissions
        run_composer_install
        ;;
    2)
        echo ""
        echo "=== Option 2: Clean Install ==="
        echo ""
        echo -e "${RED}WARNING: This will delete the entire vendor directory!${NC}"
        read -p "Are you sure you want to continue? (yes/no): " confirm
        if [ "$confirm" = "yes" ]; then
            clean_install
            run_composer_install
        else
            echo "Operation cancelled."
            exit 0
        fi
        ;;
    3)
        echo "Exiting..."
        exit 0
        ;;
    *)
        echo -e "${RED}Invalid choice. Exiting...${NC}"
        exit 1
        ;;
esac

# Final status check
if [ $? -eq 0 ]; then
    echo ""
    echo -e "${GREEN}================================${NC}"
    echo -e "${GREEN}✓ All operations completed successfully!${NC}"
    echo -e "${GREEN}================================${NC}"
    echo ""
    echo "Next steps:"
    echo "  1. Continue with your deployment process"
    echo "  2. Run: php artisan optimize"
    echo "  3. Test your application"
else
    echo ""
    echo -e "${RED}================================${NC}"
    echo -e "${RED}✗ Some operations failed${NC}"
    echo -e "${RED}================================${NC}"
    echo ""
    echo "For manual fix, try:"
    echo "  1. sudo chmod -R u+w vendor/"
    echo "  2. sudo chown -R \$(whoami):\$(whoami) vendor/"
    echo "  3. composer install --no-dev --optimize-autoloader"
fi
