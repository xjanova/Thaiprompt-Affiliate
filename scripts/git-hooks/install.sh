#!/bin/bash

###############################################################################
# Git Hooks Installation Script
#
# This script installs Git hooks from scripts/git-hooks/ to .git/hooks/
#
# Usage:
#   bash scripts/git-hooks/install.sh
#
# What it does:
#   - Installs pre-commit hook (seeder verification)
#   - Makes hooks executable
#   - Creates backup of existing hooks
###############################################################################

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

echo ""
echo -e "${CYAN}═══════════════════════════════════════════════${NC}"
echo -e "${CYAN}  📦 Git Hooks Installation${NC}"
echo -e "${CYAN}═══════════════════════════════════════════════${NC}"
echo ""

# Check if we're in a git repository
if [ ! -d ".git" ]; then
    echo -e "${RED}✗${NC} Error: Not in a git repository"
    echo -e "${YELLOW}   Please run this script from the project root${NC}"
    echo ""
    exit 1
fi

# Check if git-hooks directory exists
if [ ! -d "scripts/git-hooks" ]; then
    echo -e "${RED}✗${NC} Error: scripts/git-hooks directory not found"
    echo ""
    exit 1
fi

# Create .git/hooks directory if it doesn't exist
mkdir -p .git/hooks

# Counter for installed hooks
INSTALLED=0
SKIPPED=0
BACKED_UP=0

# Install pre-commit hook
HOOK_NAME="pre-commit"
SOURCE="scripts/git-hooks/$HOOK_NAME"
DEST=".git/hooks/$HOOK_NAME"

if [ -f "$SOURCE" ]; then
    echo -e "${BLUE}ℹ${NC} Installing $HOOK_NAME hook..."

    # Backup existing hook if it exists
    if [ -f "$DEST" ]; then
        BACKUP="$DEST.backup.$(date +%Y%m%d_%H%M%S)"
        cp "$DEST" "$BACKUP"
        echo -e "${YELLOW}  ⚠${NC}  Backed up existing hook to: $BACKUP"
        ((BACKED_UP++))
    fi

    # Copy hook
    cp "$SOURCE" "$DEST"

    # Make executable
    chmod +x "$DEST"

    echo -e "${GREEN}  ✓${NC} Installed: $HOOK_NAME"
    ((INSTALLED++))
else
    echo -e "${YELLOW}⚠${NC} Skipped: $HOOK_NAME (source file not found)"
    ((SKIPPED++))
fi

echo ""
echo -e "${CYAN}─────────────────────────────────────────────${NC}"
echo -e "${GREEN}✓${NC} Installation completed!"
echo ""
echo -e "${BLUE}Summary:${NC}"
echo "  • Hooks installed: $INSTALLED"
if [ $BACKED_UP -gt 0 ]; then
    echo "  • Hooks backed up: $BACKED_UP"
fi
if [ $SKIPPED -gt 0 ]; then
    echo "  • Hooks skipped: $SKIPPED"
fi
echo ""

if [ $INSTALLED -gt 0 ]; then
    echo -e "${BLUE}📖 What these hooks do:${NC}"
    echo "  • pre-commit: Verifies all seeders are in DatabaseSeeder.php"
    echo ""
    echo -e "${BLUE}💡 Tips:${NC}"
    echo "  • Hooks run automatically before git commit"
    echo "  • If verification fails, your commit will be blocked"
    echo "  • Fix the issue and try committing again"
    echo "  • To bypass (NOT RECOMMENDED): git commit --no-verify"
    echo ""
fi

echo -e "${GREEN}✨ All done!${NC}"
echo ""
