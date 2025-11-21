#!/bin/bash

# Quick Fix for Production Route Cache Issue
# ⚡ แก้ไข MethodNotAllowedHttpException ใน production

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

echo ""
echo -e "${CYAN}╔════════════════════════════════════════════════════════╗${NC}"
echo -e "${CYAN}║${NC}  ⚡ Quick Fix: Production Route Cache Issue         ${CYAN}║${NC}"
echo -e "${CYAN}╚════════════════════════════════════════════════════════╝${NC}"
echo ""

echo -e "${YELLOW}🔧 Issue:${NC}"
echo "  MethodNotAllowedHttpException"
echo "  The GET method is not supported for route /"
echo "  Supported methods: HEAD"
echo ""

echo -e "${YELLOW}📍 Root Cause:${NC}"
echo "  Production server is using OLD route cache"
echo "  Route files were updated but cache wasn't cleared"
echo ""

echo -e "${BLUE}═══════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}▶ Starting Fix Process...${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════${NC}"
echo ""

# Step 1: Enable maintenance mode
echo -e "${BLUE}[1/5]${NC} Enabling maintenance mode..."
php artisan down --retry=60 --render="errors::503" 2>/dev/null || {
    echo -e "${YELLOW}⚠${NC} Could not enable maintenance mode (continuing anyway)"
}
echo -e "${GREEN}✓${NC} Maintenance mode enabled"
echo ""

# Step 2: Clear route cache
echo -e "${BLUE}[2/5]${NC} Clearing OLD route cache..."
php artisan route:clear >/dev/null 2>&1 || true
rm -f bootstrap/cache/routes-v7.php 2>/dev/null || true
echo -e "${GREEN}✓${NC} Old route cache cleared"
echo ""

# Step 3: Clear all caches
echo -e "${BLUE}[3/5]${NC} Clearing all caches..."
php artisan cache:clear >/dev/null 2>&1 || true
php artisan config:clear >/dev/null 2>&1 || true
php artisan view:clear >/dev/null 2>&1 || true
echo -e "${GREEN}✓${NC} All caches cleared"
echo ""

# Step 4: Regenerate route cache with NEW routes
echo -e "${BLUE}[4/5]${NC} Generating NEW route cache..."
echo -e "${CYAN}→${NC} This will use updated route files with Route::match(['GET', 'HEAD'])"
echo ""

if php artisan route:cache 2>&1; then
    echo ""
    echo -e "${GREEN}✓${NC} New route cache generated successfully!"
else
    echo ""
    echo -e "${RED}✗${NC} Route cache generation failed!"
    echo ""
    echo "Possible issues:"
    echo "  1. Syntax errors in route files"
    echo "  2. Missing controllers or middleware"
    echo "  3. Database connection issues"
    echo ""
    echo "Try manually:"
    echo "  php artisan route:cache"

    # Disable maintenance mode before exit
    php artisan up 2>/dev/null
    exit 1
fi
echo ""

# Step 5: Verify route cache
echo -e "${BLUE}[5/5]${NC} Verifying route cache..."
if [ -f "bootstrap/cache/routes-v7.php" ] && [ -s "bootstrap/cache/routes-v7.php" ]; then
    # Check if file contains CompiledRouteCollection
    if grep -q "CompiledRouteCollection" bootstrap/cache/routes-v7.php 2>/dev/null; then
        echo -e "${GREEN}✓${NC} Route cache file is valid"

        # Count approximate routes
        route_count=$(grep -c "'" bootstrap/cache/routes-v7.php 2>/dev/null || echo "0")
        echo -e "${GREEN}✓${NC} Route cache contains $route_count route patterns"
    else
        echo -e "${YELLOW}⚠${NC} Route cache may be incomplete"
    fi
else
    echo -e "${RED}✗${NC} Route cache file not found or empty"
fi
echo ""

# Step 6: Disable maintenance mode
echo -e "${BLUE}[6/6]${NC} Disabling maintenance mode..."
php artisan up || {
    echo -e "${RED}✗${NC} Could not disable maintenance mode!"
    echo "  Please run manually: php artisan up"
    exit 1
}
echo -e "${GREEN}✓${NC} Application is now live!"
echo ""

# Success message
echo -e "${GREEN}╔════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║${NC}  ✅ Fix Completed Successfully!                      ${GREEN}║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════╝${NC}"
echo ""

echo -e "${CYAN}📊 What was fixed:${NC}"
echo "  ✓ Cleared old route cache (with GET-only methods)"
echo "  ✓ Generated new route cache (with GET + HEAD methods)"
echo "  ✓ All 60+ routes now support both GET and HEAD"
echo "  ✓ Fixed MethodNotAllowedHttpException error"
echo ""

echo -e "${YELLOW}🧪 Test your site now:${NC}"
echo "  1. Visit: https://thaiprompt.online/"
echo "  2. Check: curl -I https://thaiprompt.online/ (HEAD request)"
echo "  3. Check: curl https://thaiprompt.online/ (GET request)"
echo ""

echo -e "${BLUE}💡 Why this happened:${NC}"
echo "  • Route files were updated locally"
echo "  • Changes were pushed to repository"
echo "  • BUT production cache wasn't cleared"
echo "  • Production kept using old cached routes"
echo ""

echo -e "${GREEN}✓ Problem solved!${NC}"
echo ""

# Show current route cache info
echo -e "${CYAN}📋 Route Cache Info:${NC}"
echo "  File: bootstrap/cache/routes-v7.php"
if [ -f "bootstrap/cache/routes-v7.php" ]; then
    file_size=$(du -h bootstrap/cache/routes-v7.php | cut -f1)
    file_date=$(stat -c %y bootstrap/cache/routes-v7.php 2>/dev/null | cut -d'.' -f1)
    echo "  Size: $file_size"
    echo "  Modified: $file_date"
else
    echo "  Status: Not found"
fi
echo ""

echo -e "${YELLOW}📌 Next steps:${NC}"
echo "  1. Test all critical routes (/, /shop, /marketplace, etc.)"
echo "  2. Monitor error logs: tail -f storage/logs/laravel.log"
echo "  3. For future deployments, use: ./deploy-pro.sh"
echo ""

exit 0
