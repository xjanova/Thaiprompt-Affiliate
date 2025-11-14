#!/bin/bash

# สคริปต์แก้ไขปัญหาเมนู TPIX บน Production Server
# สำหรับ: Thaiprompt-Affiliate v2.216.0+
# วันที่: 2025-11-14

echo "=================================================="
echo "🔧 กำลังแก้ไขปัญหาเมนู TPIX Blockchain & Token"
echo "=================================================="
echo ""

# สี
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# ตรวจสอบว่ารันด้วย root หรือไม่
if [ "$EUID" -eq 0 ]; then
    echo -e "${YELLOW}⚠️  กำลังรันด้วย root - แนะนำให้ใช้ user ของ web server${NC}"
fi

# 1. Pull Code ใหม่
echo -e "${GREEN}📥 1. Pulling latest code...${NC}"
if git pull origin main; then
    echo -e "${GREEN}   ✅ Pull สำเร็จ${NC}"
else
    echo -e "${YELLOW}   ⚠️  Pull ไม่สำเร็จ - ลองใช้ branch อื่น${NC}"
    echo "   คุณสามารถ pull branch เฉพาะได้ด้วย:"
    echo "   git pull origin claude/fix-tpix-token-menu-01ESWUCE6Wq4YNxjN4Zpzb4W"
fi
echo ""

# 2. ล้าง Cache
echo -e "${GREEN}🗑️  2. Clearing caches...${NC}"

# Route Cache
if [ -f bootstrap/cache/routes-v7.php ]; then
    rm -f bootstrap/cache/routes-v7.php
    echo -e "${GREEN}   ✅ ลบ route cache${NC}"
fi

# Config Cache
if [ -f bootstrap/cache/config.php ]; then
    rm -f bootstrap/cache/config.php
    echo -e "${GREEN}   ✅ ลบ config cache${NC}"
fi

# Application Cache
if [ -d storage/framework/cache/data ]; then
    rm -rf storage/framework/cache/data/*
    echo -e "${GREEN}   ✅ ลบ application cache${NC}"
fi

# View Cache
if [ -d storage/framework/views ]; then
    rm -rf storage/framework/views/*
    echo -e "${GREEN}   ✅ ลบ view cache${NC}"
fi

echo ""

# 3. สร้าง Cache ใหม่ (ถ้ามี PHP artisan)
echo -e "${GREEN}♻️  3. Rebuilding caches...${NC}"
if command -v php &> /dev/null; then
    if [ -f artisan ]; then
        php artisan config:cache && echo -e "${GREEN}   ✅ Config cached${NC}"
        php artisan route:cache && echo -e "${GREEN}   ✅ Routes cached${NC}"
    else
        echo -e "${YELLOW}   ⚠️  ไม่พบ artisan - ข้ามขั้นตอนนี้${NC}"
    fi
else
    echo -e "${YELLOW}   ⚠️  ไม่พบ PHP - ข้ามขั้นตอนนี้${NC}"
fi
echo ""

# 4. Fix Permissions
echo -e "${GREEN}🔐 4. Fixing permissions...${NC}"
chmod -R 755 storage bootstrap/cache 2>/dev/null
echo -e "${GREEN}   ✅ Permissions fixed${NC}"
echo ""

# 5. Restart Services
echo -e "${GREEN}🔄 5. Restarting services...${NC}"
echo ""
echo -e "${YELLOW}กรุณารันคำสั่งเหล่านี้ด้วย sudo:${NC}"
echo ""
echo "   sudo systemctl restart php8.1-fpm"
echo "   sudo systemctl restart nginx"
echo ""
echo -e "${YELLOW}หรือถ้าใช้ Apache:${NC}"
echo "   sudo systemctl restart apache2"
echo ""

# 6. สรุป
echo "=================================================="
echo -e "${GREEN}✨ เสร็จสิ้น!${NC}"
echo "=================================================="
echo ""
echo "📋 ขั้นตอนต่อไป:"
echo ""
echo "   1. Restart web server (ตามคำสั่งด้านบน)"
echo "   2. เปิดเบราว์เซอร์และกด Ctrl+Shift+R"
echo "   3. Login เข้า Admin Panel"
echo "   4. ทดสอบคลิกเมนู:"
echo "      • TPIX Blockchain → Dashboard"
echo "      • Token Management → Import from CMC"
echo ""
echo "🎯 Routes ที่ควรทำงาน:"
echo "   ✅ /admin/tpix/dashboard"
echo "   ✅ /admin/tpix/network-status"
echo "   ✅ /admin/tpix/wallets"
echo "   ✅ /admin/tpix/transactions"
echo "   ✅ /admin/tpix/settings"
echo "   ✅ /admin/tokens"
echo "   ✅ /admin/tokens/import-cmc"
echo ""
echo "❌ Routes เก่าที่ไม่ควรใช้แล้ว:"
echo "   ❌ /admin/crypto/tpix/dashboard"
echo "   ❌ /admin/crypto/tokens"
echo ""
echo "=================================================="
