#!/bin/bash

echo "🔧 กำลังแก้ไข Admin Routes..."
echo ""

# Clear all caches
echo "🗑️  Clearing caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optimize
echo "⚡ Optimizing..."
php artisan config:cache
php artisan route:cache

echo ""
echo "✅ เสร็จสิ้น!"
echo ""
echo "📋 Routes ที่พร้อมใช้งาน:"
echo "  • /admin/dashboard → Admin Dashboard"
echo "  • /admin/tpix/dashboard → TPIX Blockchain Dashboard"
echo "  • /admin/tpix/network-status → TPIX Network Status"
echo "  • /admin/tpix/wallets → TPIX Wallets"
echo "  • /admin/tpix/transactions → TPIX Transactions"
echo "  • /admin/tpix/settings → TPIX Settings"
echo "  • /admin/tokens → Token Management"
echo "  • /admin/tokens/import-cmc → Import from CMC"
echo ""
echo "🎯 ลองเข้าเมนูใหม่อีกครั้ง!"
