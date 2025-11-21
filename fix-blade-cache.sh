#!/bin/bash

##############################################################################
# Blade Template Cache Fix Script
# ไสคริปต์แก้ไข Cache ของ Blade Template
#
# Use: ./fix-blade-cache.sh
# หรือ: bash fix-blade-cache.sh
##############################################################################

echo "=========================================="
echo "🔧 Blade Template Cache Fix"
echo "=========================================="
echo ""

# สีสำหรับ output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# ตรวจสอบว่าอยู่ใน Laravel directory หรือไม่
if [ ! -f "artisan" ]; then
    echo -e "${RED}❌ Error: artisan file not found!${NC}"
    echo "Please run this script from Laravel root directory"
    exit 1
fi

echo -e "${YELLOW}Step 1: Clearing compiled views...${NC}"
if php artisan view:clear; then
    echo -e "${GREEN}✅ Compiled views cleared${NC}"
else
    echo -e "${YELLOW}⚠️  Artisan command failed, trying manual method...${NC}"
    rm -rf storage/framework/views/*
    echo -e "${GREEN}✅ Manually cleared storage/framework/views/${NC}"
fi
echo ""

echo -e "${YELLOW}Step 2: Clearing application cache...${NC}"
php artisan cache:clear 2>/dev/null || echo -e "${YELLOW}⚠️  Cache clear skipped${NC}"
echo ""

echo -e "${YELLOW}Step 3: Clearing config cache...${NC}"
php artisan config:clear 2>/dev/null || echo -e "${YELLOW}⚠️  Config clear skipped${NC}"
echo ""

echo -e "${YELLOW}Step 4: Clearing route cache...${NC}"
php artisan route:clear 2>/dev/null || echo -e "${YELLOW}⚠️  Route clear skipped${NC}"
echo ""

# ตรวจสอบว่าต้องรีสตาร์ท PHP-FPM หรือไม่
echo -e "${YELLOW}Step 5: Checking PHP-FPM status...${NC}"
if command -v systemctl &> /dev/null; then
    PHP_FPM_SERVICE=$(systemctl list-units --type=service | grep -o 'php[0-9.]*-fpm.service' | head -1)
    if [ -n "$PHP_FPM_SERVICE" ]; then
        echo -e "${YELLOW}Found PHP-FPM service: $PHP_FPM_SERVICE${NC}"
        echo -e "${YELLOW}To restart PHP-FPM (requires sudo):${NC}"
        echo -e "  sudo systemctl restart $PHP_FPM_SERVICE"
    fi
fi
echo ""

# สรุปผล
echo "=========================================="
echo -e "${GREEN}✅ Cache clearing completed!${NC}"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Refresh your browser (Ctrl+Shift+R / Cmd+Shift+R)"
echo "2. If error persists, restart PHP-FPM (see command above)"
echo "3. Check error log: storage/logs/laravel.log"
echo ""

# ตรวจสอบไฟล์ Blade template
echo -e "${YELLOW}Verifying Blade template...${NC}"
BLADE_FILE="resources/views/user/ranks/progress.blade.php"
if [ -f "$BLADE_FILE" ]; then
    echo -e "${GREEN}✅ File exists: $BLADE_FILE${NC}"
    echo "   Lines: $(wc -l < "$BLADE_FILE")"
    echo "   Last modified: $(stat -c %y "$BLADE_FILE" 2>/dev/null || stat -f %Sm "$BLADE_FILE" 2>/dev/null)"
else
    echo -e "${RED}❌ File not found: $BLADE_FILE${NC}"
fi
echo ""

echo "=========================================="
echo "Done! กรุณาลองเข้าเว็บใหม่"
echo "=========================================="
