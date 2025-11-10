-- ============================================
-- สคริปต์แก้ไขข้อมูลเมนูในฐานข้อมูล
-- ============================================

-- 1. ตรวจสอบข้อมูลปัจจุบัน
SELECT
    `key`,
    `type`,
    SUBSTRING(`value`, 1, 200) as value_preview
FROM windows_ui_settings
WHERE `key` LIKE 'windows_start_menu_items_%';

-- 2. ลบข้อมูลเมนูเดิมออก (เพื่อให้รัน seeder ใหม่ได้)
-- DELETE FROM windows_ui_settings WHERE `key` = 'windows_start_menu_items_admin';
-- DELETE FROM windows_ui_settings WHERE `key` = 'windows_start_menu_items_seller';
-- DELETE FROM windows_ui_settings WHERE `key` = 'windows_start_menu_items_user';

-- 3. หลังจากลบแล้ว ให้รันคำสั่ง seeder:
-- php artisan db:seed --class=WindowsUiSeeder --force
