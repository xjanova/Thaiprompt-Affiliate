#!/bin/bash

##############################################################################
# Fix Deployment Conflict Script
#
# This script resolves git merge conflicts caused by untracked files
# that would be overwritten during deployment.
#
# Usage: Run this script on the production server before deploying
##############################################################################

set -e  # Exit on error

echo "================================"
echo "Fix Deployment Conflict"
echo "================================"
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Define the conflicting files
CONFLICTING_FILES=(
    "bootstrap/cache/packages.php"
    "bootstrap/cache/services.php"
    "composer.lock"
    "config/permission.php"
    "package-lock.json"
)

# Create backup directory with timestamp
BACKUP_DIR="deployment-backup-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$BACKUP_DIR"

echo -e "${YELLOW}Step 1: Backing up conflicting files...${NC}"
for file in "${CONFLICTING_FILES[@]}"; do
    if [ -f "$file" ]; then
        echo "  • Backing up: $file"
        # Create directory structure in backup
        mkdir -p "$BACKUP_DIR/$(dirname "$file")"
        cp "$file" "$BACKUP_DIR/$file"
    else
        echo "  ⊘ File not found (skipped): $file"
    fi
done
echo -e "${GREEN}✓ Backup completed in: $BACKUP_DIR${NC}"
echo ""

echo -e "${YELLOW}Step 2: Removing conflicting files...${NC}"
for file in "${CONFLICTING_FILES[@]}"; do
    if [ -f "$file" ]; then
        echo "  • Removing: $file"
        rm -f "$file"
    fi
done
echo -e "${GREEN}✓ Conflicting files removed${NC}"
echo ""

echo -e "${YELLOW}Step 3: Checking git status...${NC}"
git status --short
echo ""

echo -e "${GREEN}✓ Ready for deployment!${NC}"
echo ""
echo "Next steps:"
echo "  1. Run your deployment script (e.g., ./deploy.sh)"
echo "  2. If deployment succeeds, you can delete the backup: rm -rf $BACKUP_DIR"
echo "  3. If issues occur, restore from backup: cp -r $BACKUP_DIR/* ./"
echo ""
