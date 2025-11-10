#!/bin/bash

echo "🔧 แก้ไขปัญหาเมนูกดแล้วไม่ไปไหน"
echo "=================================="
echo ""

echo "📝 ขั้นตอนที่ 1: ตรวจสอบข้อมูลเมนูใน Database"
php artisan tinker --execute="
use App\Models\WindowsUiSetting;
\$admin = WindowsUiSetting::where('key', 'windows_start_menu_items_admin')->first();
\$seller = WindowsUiSetting::where('key', 'windows_start_menu_items_seller')->first();
\$user = WindowsUiSetting::where('key', 'windows_start_menu_items_user')->first();

if (!\$admin && !\$seller && !\$user) {
    echo '❌ ไม่พบข้อมูลเมนูใน Database - ต้องรัน Seeder\n';
} else {
    echo '✅ พบข้อมูลเมนูใน Database:\n';
    if (\$admin) echo '  - Admin Menu: มี\n';
    if (\$seller) echo '  - Seller Menu: มี\n';
    if (\$user) echo '  - User Menu: มี\n';
}
"

echo ""
echo "📝 ขั้นตอนที่ 2: รัน Seeder เพื่อเพิ่มข้อมูลเมนู"
php artisan db:seed --class=WindowsUiSeeder --force

echo ""
echo "📝 ขั้นตอนที่ 3: ตรวจสอบ Routes ที่สำคัญ"
echo "กำลังตรวจสอบว่า routes ที่ใช้ในเมนูมีจริงหรือไม่..."
php artisan route:list | grep -E "(admin\.dashboard|seller\.dashboard|user\.dashboard)" || echo "⚠️  ไม่พบ routes dashboard"

echo ""
echo "✅ เสร็จสิ้น!"
echo ""
echo "📋 คำแนะนำเพิ่มเติม:"
echo "  1. ลอง refresh หน้าเว็บ (Ctrl+Shift+R)"
echo "  2. ตรวจสอบ Console ของเบราว์เซอร์ว่ามี Error หรือไม่"
echo "  3. ถ้ายังไม่ได้ ให้ตรวจสอบว่า routes ทั้งหมดใน seed ได้ define ใน routes/web.php แล้วหรือยัง"
