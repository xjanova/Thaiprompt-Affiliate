#!/bin/bash

# ===================================================================
# Thai Prompt Affiliate - Manual Version Bump Script
# ===================================================================
# This script bumps version when working on feature branches
# GitHub Actions will handle auto-bump when merging to main branches
# ===================================================================

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Get current version
CURRENT_VERSION=$(cat VERSION | tr -d '\n\r')
echo -e "${BLUE}📦 Current version: v${CURRENT_VERSION}${NC}"

# Default to patch if no argument
BUMP_TYPE=${1:-patch}

# Validate bump type
if [[ ! "$BUMP_TYPE" =~ ^(major|minor|patch)$ ]]; then
    echo -e "${RED}❌ Invalid bump type: $BUMP_TYPE${NC}"
    echo -e "${YELLOW}Usage: ./bump-version.sh [major|minor|patch]${NC}"
    echo ""
    echo "  major - Breaking changes (1.0.0 → 2.0.0)"
    echo "  minor - New features (1.0.0 → 1.1.0)"
    echo "  patch - Bug fixes (1.0.0 → 1.0.1)"
    exit 1
fi

# Calculate new version
IFS='.' read -r MAJOR MINOR PATCH <<< "$CURRENT_VERSION"

case "$BUMP_TYPE" in
    major)
        MAJOR=$((MAJOR + 1))
        MINOR=0
        PATCH=0
        ;;
    minor)
        MINOR=$((MINOR + 1))
        PATCH=0
        ;;
    patch)
        PATCH=$((PATCH + 1))
        ;;
esac

NEW_VERSION="${MAJOR}.${MINOR}.${PATCH}"
NEW_TAG="v${NEW_VERSION}"

echo -e "${GREEN}✨ New version: ${NEW_TAG}${NC}"
echo ""

# Confirm
read -p "Continue with version bump? (y/n) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo -e "${YELLOW}⚠️  Version bump cancelled${NC}"
    exit 1
fi

echo ""
echo -e "${BLUE}🔄 Updating version files...${NC}"

# Update VERSION file
echo "$NEW_VERSION" > VERSION
echo -e "${GREEN}✓${NC} Updated VERSION"

# Update package.json
if [ -f "package.json" ]; then
    npm version $NEW_VERSION --no-git-tag-version --allow-same-version
    echo -e "${GREEN}✓${NC} Updated package.json"
fi

# Update CHANGELOG.md
if [ -f "CHANGELOG.md" ]; then
    RELEASE_DATE=$(date +%Y-%m-%d)
    TEMP_FILE=$(mktemp)

    # Get recent commits for changelog
    echo "" > NEW_ENTRY.txt
    echo "## [${NEW_TAG}] - ${RELEASE_DATE}" >> NEW_ENTRY.txt
    echo "" >> NEW_ENTRY.txt

    echo "### ✨ Features" >> NEW_ENTRY.txt
    git log --since="7 days ago" --pretty=format:"- %s (%h)" --grep="^feat" --grep="^feature" >> NEW_ENTRY.txt 2>/dev/null || echo "- Manual version bump" >> NEW_ENTRY.txt
    echo "" >> NEW_ENTRY.txt

    echo "### 🐛 Bug Fixes" >> NEW_ENTRY.txt
    git log --since="7 days ago" --pretty=format:"- %s (%h)" --grep="^fix" >> NEW_ENTRY.txt 2>/dev/null || echo "- Bug fixes and improvements" >> NEW_ENTRY.txt
    echo "" >> NEW_ENTRY.txt

    echo "### 🔧 Other Changes" >> NEW_ENTRY.txt
    git log --since="7 days ago" --pretty=format:"- %s (%h)" --invert-grep --grep="^feat" --grep="^fix" | head -10 >> NEW_ENTRY.txt 2>/dev/null || echo "- General improvements" >> NEW_ENTRY.txt
    echo "" >> NEW_ENTRY.txt

    # Insert after header (line 3)
    head -n 3 CHANGELOG.md > "$TEMP_FILE"
    cat NEW_ENTRY.txt >> "$TEMP_FILE"
    tail -n +4 CHANGELOG.md >> "$TEMP_FILE"
    mv "$TEMP_FILE" CHANGELOG.md
    rm NEW_ENTRY.txt

    echo -e "${GREEN}✓${NC} Updated CHANGELOG.md"
fi

echo ""
echo -e "${BLUE}📝 Committing changes...${NC}"

# Stage files
git add VERSION package.json package-lock.json CHANGELOG.md 2>/dev/null || true

# Commit
git commit -m "chore: bump version to ${NEW_VERSION} [skip ci]"
echo -e "${GREEN}✓${NC} Committed version bump"

echo ""
echo -e "${GREEN}🎉 Version bumped successfully!${NC}"
echo -e "${BLUE}📦 ${CURRENT_VERSION} → ${NEW_VERSION}${NC}"
echo ""
echo -e "${YELLOW}Next steps:${NC}"
echo -e "  1. Push changes: ${BLUE}git push${NC}"
echo -e "  2. Or push with tag: ${BLUE}git push && git push --tags${NC}"
echo ""
