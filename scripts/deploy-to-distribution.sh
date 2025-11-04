#!/bin/bash

###############################################################################
# Deploy to Distribution Repository
# Pushes production-ready code to xjanova/TP-Affiliate
###############################################################################

set -e  # Exit on any error

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Configuration
DIST_REPO="https://github.com/xjanova/TP-Affiliate.git"
DIST_BRANCH="main"
BUILD_DIR="./build-dist"
VERSION=$(cat VERSION 2>/dev/null || echo "1.0.0")

echo -e "${BLUE}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║                                                            ║${NC}"
echo -e "${BLUE}║         Deploy to Distribution Repository                  ║${NC}"
echo -e "${BLUE}║         Version: ${VERSION}                                       ║${NC}"
echo -e "${BLUE}║                                                            ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""

# Step 1: Run tests
echo -e "${BLUE}[1/9]${NC} Running tests..."
if php artisan test --testsuite=Feature 2>/dev/null; then
    echo -e "${GREEN}✓ Tests passed${NC}"
else
    echo -e "${YELLOW}⚠ Tests skipped or failed (continuing anyway)${NC}"
fi

# Step 2: Build assets
echo -e "${BLUE}[2/9]${NC} Building frontend assets..."
if [ -f "package.json" ]; then
    npm install --production
    npm run build
    echo -e "${GREEN}✓ Assets built${NC}"
else
    echo -e "${YELLOW}⚠ No package.json found, skipping npm build${NC}"
fi

# Step 3: Install PHP dependencies
echo -e "${BLUE}[3/9]${NC} Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction
echo -e "${GREEN}✓ Dependencies installed${NC}"

# Step 4: Generate integrity checksums
echo -e "${BLUE}[4/9]${NC} Generating file integrity checksums..."
php artisan app:integrity-check --generate-checksums > .installation/checksums.json
echo -e "${GREEN}✓ Checksums generated${NC}"

# Step 5: Clean build directory
echo -e "${BLUE}[5/9]${NC} Preparing build directory..."
rm -rf "$BUILD_DIR"
mkdir -p "$BUILD_DIR"
echo -e "${GREEN}✓ Build directory ready${NC}"

# Step 6: Copy files to build directory
echo -e "${BLUE}[6/9]${NC} Copying files..."

# Files and directories to include in distribution
rsync -av --progress \
    --exclude='.git' \
    --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='.env' \
    --exclude='.env.example' \
    --exclude='storage/logs/*' \
    --exclude='storage/framework/cache/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/framework/views/*' \
    --exclude='tests' \
    --exclude='phpunit.xml' \
    --exclude='_tplicense-plugin' \
    --exclude='scripts' \
    --exclude='build-dist' \
    --exclude='.github/workflows/release.yml' \
    ./ "$BUILD_DIR/"

# Copy distribution-specific files
cp .distribution/install.sh "$BUILD_DIR/"
chmod +x "$BUILD_DIR/install.sh"

# Create production .env.example
cat > "$BUILD_DIR/.env.example" << 'EOF'
APP_NAME="TP-Affiliate"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=thaiprompt_affiliate
DB_USERNAME=root
DB_PASSWORD=

LICENSE_KEY=
LICENSE_INSTALLATION_ID=
LICENSE_API_URL=https://xman4289.com/wp-json/tp-license/v1
LICENSE_ALLOW_OFFLINE=false

CACHE_DRIVER=file
QUEUE_CONNECTION=sync
SESSION_DRIVER=file

MAIL_MAILER=smtp
MAIL_HOST=localhost
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
EOF

echo -e "${GREEN}✓ Files copied${NC}"

# Step 7: Re-install dependencies in build directory
echo -e "${BLUE}[7/9]${NC} Installing production dependencies..."
cd "$BUILD_DIR"
composer install --no-dev --optimize-autoloader --no-interaction
cd ..
echo -e "${GREEN}✓ Production dependencies installed${NC}"

# Step 8: Initialize git repository
echo -e "${BLUE}[8/9]${NC} Initializing distribution repository..."
cd "$BUILD_DIR"

if [ ! -d ".git" ]; then
    git init
    git remote add origin "$DIST_REPO"
fi

git add -A
git commit -m "chore: release version $VERSION [skip ci]

Generated from xjanova/Thaiprompt-Affiliate
Release date: $(date +%Y-%m-%d)
" || echo "No changes to commit"

echo -e "${GREEN}✓ Repository initialized${NC}"

# Step 9: Push to distribution repository
echo -e "${BLUE}[9/9]${NC} Pushing to distribution repository..."
echo ""
echo -e "${YELLOW}Ready to push to: $DIST_REPO${NC}"
echo -e "${YELLOW}Branch: $DIST_BRANCH${NC}"
echo -e "${YELLOW}Version: v$VERSION${NC}"
echo ""

read -p "Continue with push? (y/N): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    # Retry logic for push
    MAX_RETRIES=4
    RETRY_COUNT=0
    SLEEP_TIME=2

    while [ $RETRY_COUNT -lt $MAX_RETRIES ]; do
        echo "Push attempt $((RETRY_COUNT + 1)) of $MAX_RETRIES..."

        if git push -f origin HEAD:$DIST_BRANCH; then
            echo -e "${GREEN}✓ Successfully pushed to distribution repository!${NC}"
            break
        else
            RETRY_COUNT=$((RETRY_COUNT + 1))
            if [ $RETRY_COUNT -lt $MAX_RETRIES ]; then
                echo -e "${YELLOW}⚠ Push failed. Retrying in ${SLEEP_TIME}s...${NC}"
                sleep $SLEEP_TIME
                SLEEP_TIME=$((SLEEP_TIME * 2))
            else
                echo -e "${RED}✗ Push failed after $MAX_RETRIES attempts${NC}"
                exit 1
            fi
        fi
    done

    # Create git tag
    git tag -a "v$VERSION" -m "Release v$VERSION" || echo "Tag already exists"
    git push origin "v$VERSION" || echo "Failed to push tag"

    echo ""
    echo -e "${GREEN}╔════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║                                                            ║${NC}"
    echo -e "${GREEN}║         ✓ Deployment Successful!                          ║${NC}"
    echo -e "${GREEN}║                                                            ║${NC}"
    echo -e "${GREEN}╚════════════════════════════════════════════════════════════╝${NC}"
    echo ""
    echo -e "${BLUE}Distribution repository:${NC} $DIST_REPO"
    echo -e "${BLUE}Version deployed:${NC} v$VERSION"
    echo -e "${BLUE}Installation URL:${NC} https://github.com/xjanova/TP-Affiliate"
    echo ""
else
    echo -e "${YELLOW}Deployment cancelled${NC}"
fi

cd ..

# Cleanup
read -p "Remove build directory? (y/N): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    rm -rf "$BUILD_DIR"
    echo -e "${GREEN}✓ Build directory removed${NC}"
fi

echo ""
echo -e "${GREEN}Done!${NC}"
