#!/bin/bash

echo "🔧 กำลังแก้ไขปัญหาเมนู TPIX & Token Management..."
echo ""

# ล้าง cache ทั้งหมด (ไม่ต้องใช้ artisan)
echo "🗑️  ลบไฟล์ cache..."
rm -rf bootstrap/cache/*.php
rm -rf storage/framework/cache/data/*
rm -rf storage/framework/views/*
rm -rf storage/framework/sessions/*

echo ""
echo "✅ ล้าง cache เสร็จแล้ว!"
echo ""

# ตรวจสอบว่า routes/admin.php ถูก load ใน bootstrap/app.php หรือไม่
echo "🔍 ตรวจสอบการโหลด admin routes..."
if grep -q "routes/admin.php" bootstrap/app.php; then
    echo "✅ admin.php ถูก load ใน bootstrap/app.php แล้ว"
else
    echo "❌ admin.php ไม่ถูก load!"
    echo ""
    echo "⚠️  กรุณาตรวจสอบไฟล์ bootstrap/app.php"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "📋 ขั้นตอนถัดไป:"
echo ""
echo "   1. รันคำสั่งเหล่านี้บนเซิร์ฟเวอร์จริง:"
echo "      cd /path/to/your/project"
echo "      php artisan route:clear"
echo "      php artisan config:clear"
echo "      php artisan cache:clear"
echo "      php artisan config:cache"
echo "      php artisan route:cache"
echo ""
echo "   2. รีโหลดเว็บเบราว์เซอร์ (Ctrl+Shift+R)"
echo ""
echo "   3. ลอง login เข้า admin panel แล้วคลิกเมนู:"
echo "      • TPIX Blockchain"
echo "      • Token Management"
echo ""
echo "   4. ถ้ายังไม่ได้ ให้ตรวจสอบ Laravel logs:"
echo "      tail -f storage/logs/laravel.log"
echo ""
echo "🎯 Routes ที่ควรใช้งานได้:"
echo ""
echo "   TPIX Blockchain:"
echo "   • /admin/tpix/dashboard"
echo "   • /admin/tpix/network-status"
echo "   • /admin/tpix/wallets"
echo "   • /admin/tpix/transactions"
echo "   • /admin/tpix/settings"
echo ""
echo "   Token Management:"
echo "   • /admin/tokens"
echo "   • /admin/tokens?status=draft"
echo "   • /admin/tokens?verified=1"
echo "   • /admin/tokens/import-cmc"
echo ""
