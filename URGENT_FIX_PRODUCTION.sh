#!/bin/bash

##############################################################################
# 🚨 URGENT FIX: เพิ่มคอลัมน์ footer_logo_animation บน Production
##############################################################################
# ใช้ script นี้เพื่อแก้ไขปัญหาด่วนบน production server
# โดยไม่ต้องรัน migration ทั้งหมด
##############################################################################

set -e  # หยุดทันทีถ้ามี error

# สี output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}🚨 URGENT FIX: Theme Settings Columns${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# ตรวจสอบว่าอยู่ใน project directory
if [ ! -f "artisan" ]; then
    echo -e "${RED}❌ Error: ไม่พบไฟล์ artisan${NC}"
    echo -e "${YELLOW}💡 กรุณา cd ไปยัง directory ของ project ก่อน${NC}"
    exit 1
fi

echo -e "${YELLOW}⚠️  WARNING: กำลังแก้ไข production database${NC}"
echo -e "${YELLOW}⏳ Backup database ก่อนดำเนินการ...${NC}"
echo ""

# ถาม user ว่า backup แล้วหรือยัง
read -p "คุณได้ backup database แล้วหรือยัง? (y/n): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo -e "${RED}❌ กรุณา backup database ก่อน!${NC}"
    echo ""
    echo -e "${BLUE}วิธี backup:${NC}"
    echo "mysqldump -u root -p database_name > backup_\$(date +%Y%m%d_%H%M%S).sql"
    echo ""
    exit 1
fi

echo -e "${GREEN}✅ เริ่มดำเนินการ...${NC}"
echo ""

# วิธีที่ 1: พยายามรัน migration
echo -e "${BLUE}📦 Method 1: รัน migration${NC}"
if php artisan migrate --force 2>/dev/null; then
    echo -e "${GREEN}✅ Migration สำเร็จ!${NC}"
    echo ""

    # ตรวจสอบว่าคอลัมน์ถูกเพิ่มแล้ว
    echo -e "${BLUE}🔍 ตรวจสอบคอลัมน์...${NC}"
    php artisan tinker --execute="
        \$exists = Schema::hasColumn('theme_settings', 'footer_logo_animation');
        echo \$exists ? '✅ Column exists!' : '❌ Column not found!';
    "
    echo ""
    echo -e "${GREEN}✅ แก้ไขสำเร็จ! คุณสามารถใช้งาน Theme Settings ได้แล้ว${NC}"
    exit 0
else
    echo -e "${YELLOW}⚠️  Migration ล้มเหลว - ลองวิธีที่ 2${NC}"
    echo ""
fi

# วิธีที่ 2: ใช้ SQL โดยตรง
echo -e "${BLUE}📦 Method 2: ใช้ SQL โดยตรง${NC}"

# อ่านข้อมูล database จาก .env
DB_HOST=$(grep DB_HOST .env | cut -d '=' -f2)
DB_PORT=$(grep DB_PORT .env | cut -d '=' -f2)
DB_DATABASE=$(grep DB_DATABASE .env | cut -d '=' -f2)
DB_USERNAME=$(grep DB_USERNAME .env | cut -d '=' -f2)
DB_PASSWORD=$(grep DB_PASSWORD .env | cut -d '=' -f2)

# ค่า default ถ้าไม่มีใน .env
DB_HOST=${DB_HOST:-127.0.0.1}
DB_PORT=${DB_PORT:-3306}

echo -e "${BLUE}Database: ${DB_DATABASE}${NC}"
echo ""

# สร้าง SQL command
SQL_COMMANDS="
-- เพิ่มคอลัมน์ footer_logo_animation
SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'theme_settings'
    AND COLUMN_NAME = 'footer_logo_animation'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE theme_settings ADD COLUMN footer_logo_animation ENUM(''none'', ''float'', ''spin'', ''bounce'', ''pulse'', ''swing'') DEFAULT ''float'' COMMENT ''Footer Logo Animation Style''',
    'SELECT ''Column footer_logo_animation already exists'' AS info');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- เพิ่มคอลัมน์อื่นๆ ที่อาจขาดหาย
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'theme_settings' AND COLUMN_NAME = 'footer_logo_path');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE theme_settings ADD COLUMN footer_logo_path VARCHAR(255) NULL', 'SELECT ''Column footer_logo_path already exists'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'theme_settings' AND COLUMN_NAME = 'bg_effects_enabled');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE theme_settings ADD COLUMN bg_effects_enabled TINYINT(1) DEFAULT 1', 'SELECT ''Column bg_effects_enabled already exists'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'theme_settings' AND COLUMN_NAME = 'bg_circle1_color1');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE theme_settings ADD COLUMN bg_circle1_color1 VARCHAR(7) DEFAULT ''#22d3ee''', 'SELECT ''Column bg_circle1_color1 already exists'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'theme_settings' AND COLUMN_NAME = 'bg_circle1_color2');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE theme_settings ADD COLUMN bg_circle1_color2 VARCHAR(7) DEFAULT ''#2563eb''', 'SELECT ''Column bg_circle1_color2 already exists'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'theme_settings' AND COLUMN_NAME = 'bg_circle2_color1');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE theme_settings ADD COLUMN bg_circle2_color1 VARCHAR(7) DEFAULT ''#f472b6''', 'SELECT ''Column bg_circle2_color1 already exists'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'theme_settings' AND COLUMN_NAME = 'bg_circle2_color2');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE theme_settings ADD COLUMN bg_circle2_color2 VARCHAR(7) DEFAULT ''#9333ea''', 'SELECT ''Column bg_circle2_color2 already exists'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'theme_settings' AND COLUMN_NAME = 'bg_circle3_color1');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE theme_settings ADD COLUMN bg_circle3_color1 VARCHAR(7) DEFAULT ''#fbbf24''', 'SELECT ''Column bg_circle3_color1 already exists'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'theme_settings' AND COLUMN_NAME = 'bg_circle3_color2');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE theme_settings ADD COLUMN bg_circle3_color2 VARCHAR(7) DEFAULT ''#f97316''', 'SELECT ''Column bg_circle3_color2 already exists'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'theme_settings' AND COLUMN_NAME = 'bg_animation_speed');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE theme_settings ADD COLUMN bg_animation_speed ENUM(''slow'', ''normal'', ''fast'') DEFAULT ''normal''', 'SELECT ''Column bg_animation_speed already exists'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'theme_settings' AND COLUMN_NAME = 'bg_circle_opacity');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE theme_settings ADD COLUMN bg_circle_opacity INT DEFAULT 15', 'SELECT ''Column bg_circle_opacity already exists'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'theme_settings' AND COLUMN_NAME = 'bg_circle_blur');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE theme_settings ADD COLUMN bg_circle_blur INT DEFAULT 96', 'SELECT ''Column bg_circle_blur already exists'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'theme_settings' AND COLUMN_NAME = 'bg_circle_size');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE theme_settings ADD COLUMN bg_circle_size INT DEFAULT 384', 'SELECT ''Column bg_circle_size already exists'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- แสดงผลสำเร็จ
SELECT '✅ All columns added successfully!' AS result;
"

# รัน SQL
if [ -n "$DB_PASSWORD" ]; then
    echo "$SQL_COMMANDS" | mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE"
else
    echo "$SQL_COMMANDS" | mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" "$DB_DATABASE"
fi

if [ $? -eq 0 ]; then
    echo ""
    echo -e "${GREEN}✅ เพิ่มคอลัมน์สำเร็จ!${NC}"
    echo ""

    # แสดงคอลัมน์ที่เพิ่ม
    echo -e "${BLUE}📋 ตรวจสอบคอลัมน์ที่เพิ่ม:${NC}"
    if [ -n "$DB_PASSWORD" ]; then
        mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" -e "
            SELECT COLUMN_NAME, COLUMN_TYPE, COLUMN_DEFAULT
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = '$DB_DATABASE'
            AND TABLE_NAME = 'theme_settings'
            AND COLUMN_NAME IN ('footer_logo_animation', 'bg_effects_enabled', 'bg_circle1_color1')
            ORDER BY ORDINAL_POSITION;
        "
    else
        mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" "$DB_DATABASE" -e "
            SELECT COLUMN_NAME, COLUMN_TYPE, COLUMN_DEFAULT
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = '$DB_DATABASE'
            AND TABLE_NAME = 'theme_settings'
            AND COLUMN_NAME IN ('footer_logo_animation', 'bg_effects_enabled', 'bg_circle1_color1')
            ORDER BY ORDINAL_POSITION;
        "
    fi

    echo ""
    echo -e "${GREEN}========================================${NC}"
    echo -e "${GREEN}✅ แก้ไขเสร็จสมบูรณ์!${NC}"
    echo -e "${GREEN}========================================${NC}"
    echo ""
    echo -e "${BLUE}📝 ขั้นตอนต่อไป:${NC}"
    echo "1. ทดสอบ Theme Settings ใน Admin panel"
    echo "2. เลือก Footer Logo Animation"
    echo "3. กด Save"
    echo "4. Refresh และตรวจสอบว่าทำงานปกติ"
    echo ""
else
    echo -e "${RED}❌ เกิดข้อผิดพลาด!${NC}"
    echo -e "${YELLOW}💡 ลอง:${NC}"
    echo "1. ตรวจสอบ .env file"
    echo "2. ตรวจสอบว่าต่อ database ได้"
    echo "3. รัน SQL ใน FIX_THEME_SETTINGS_COLUMNS.sql ด้วยตัวเอง"
    exit 1
fi
