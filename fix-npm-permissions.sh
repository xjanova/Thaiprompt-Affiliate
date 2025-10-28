#!/bin/bash

##############################################################################
# Fix NPM Permission Issues
#
# This script resolves npm install errors caused by permission issues
# when trying to remove or modify files in node_modules directory.
#
# Usage: Run this script on the production server when npm fails
##############################################################################

set -e  # Exit on error

echo "================================"
echo "Fix NPM Permissions"
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
    echo "1) Fix permissions and retry npm install (Recommended)"
    echo "2) Clean node_modules and fresh install"
    echo "3) Exit"
    echo ""
    read -p "Enter your choice [1-3]: " choice
}

# Function to fix permissions
fix_permissions() {
    echo -e "${YELLOW}Fixing node_modules directory permissions...${NC}"

    if [ -d "node_modules" ]; then
        echo "  • Setting write permissions on node_modules directory..."
        chmod -R u+w node_modules/ 2>/dev/null || {
            echo -e "${YELLOW}  ⚠ Some files couldn't be changed. Trying with sudo...${NC}"
            sudo chmod -R u+w node_modules/ 2>/dev/null || true
        }

        echo "  • Ensuring ownership is correct..."
        sudo chown -R $(whoami):$(whoami) node_modules/ 2>/dev/null || {
            echo -e "${YELLOW}  ⚠ Couldn't change ownership (may need root access)${NC}"
        }

        # Fix .vite cache specifically
        if [ -d "node_modules/.vite" ]; then
            echo "  • Fixing .vite cache permissions..."
            chmod -R u+w node_modules/.vite 2>/dev/null || sudo chmod -R u+w node_modules/.vite 2>/dev/null || true
            sudo chown -R $(whoami):$(whoami) node_modules/.vite 2>/dev/null || true
        fi

        echo -e "${GREEN}✓ Permissions fixed${NC}"
    else
        echo -e "${YELLOW}  ⚠ node_modules directory not found${NC}"
    fi
    echo ""
}

# Function to clean install
clean_install() {
    echo -e "${YELLOW}Performing clean installation...${NC}"

    # Backup package-lock.json
    if [ -f "package-lock.json" ]; then
        echo "  • Backing up package-lock.json..."
        cp package-lock.json package-lock.json.backup
        echo -e "${GREEN}✓ Backup created: package-lock.json.backup${NC}"
    fi

    # Remove node_modules directory
    if [ -d "node_modules" ]; then
        echo "  • Removing node_modules directory..."
        rm -rf node_modules/ 2>/dev/null || {
            echo -e "${YELLOW}  ⚠ Need elevated permissions...${NC}"
            sudo rm -rf node_modules/ || {
                echo -e "${RED}✗ Failed to remove node_modules directory${NC}"
                echo "  Please manually run: sudo rm -rf node_modules/"
                return 1
            }
        }
        echo -e "${GREEN}✓ node_modules directory removed${NC}"
    fi

    # Remove .vite cache in public
    if [ -d "public/.vite" ]; then
        echo "  • Removing public/.vite cache..."
        rm -rf public/.vite 2>/dev/null || sudo rm -rf public/.vite 2>/dev/null || true
    fi

    # Remove npm cache
    echo "  • Clearing npm cache..."
    npm cache clean --force 2>/dev/null || true
    echo -e "${GREEN}✓ Cache cleared${NC}"
    echo ""
}

# Function to run npm install
run_npm_install() {
    echo -e "${YELLOW}Running npm install...${NC}"
    echo ""

    # Check if we should use npm ci or npm install
    if [ -f "package-lock.json" ]; then
        echo "  Using npm ci (clean install from lock file)..."
        npm ci --omit=dev || {
            echo -e "${RED}✗ npm ci failed, trying npm install...${NC}"
            npm install --omit=dev || {
                echo -e "${RED}✗ npm install failed${NC}"
                echo ""
                echo "Try running manually with verbose output:"
                echo "  npm install --omit=dev --verbose"
                return 1
            }
        }
    else
        echo "  Using npm install..."
        npm install --omit=dev || {
            echo -e "${RED}✗ npm install failed${NC}"
            echo ""
            echo "Try running manually with verbose output:"
            echo "  npm install --omit=dev --verbose"
            return 1
        }
    fi

    echo ""
    echo -e "${GREEN}✓ NPM install completed successfully!${NC}"
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
        run_npm_install
        ;;
    2)
        echo ""
        echo "=== Option 2: Clean Install ==="
        echo ""
        echo -e "${RED}WARNING: This will delete the entire node_modules directory!${NC}"
        read -p "Are you sure you want to continue? (yes/no): " confirm
        if [ "$confirm" = "yes" ]; then
            clean_install
            run_npm_install
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
    echo "  2. Run: npm run build"
    echo "  3. Test your application"
else
    echo ""
    echo -e "${RED}================================${NC}"
    echo -e "${RED}✗ Some operations failed${NC}"
    echo -e "${RED}================================${NC}"
    echo ""
    echo "For manual fix, try:"
    echo "  1. sudo chmod -R u+w node_modules/"
    echo "  2. sudo chown -R \$(whoami):\$(whoami) node_modules/"
    echo "  3. npm cache clean --force"
    echo "  4. npm install --omit=dev"
fi
