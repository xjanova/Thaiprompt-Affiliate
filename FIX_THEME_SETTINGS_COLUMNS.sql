-- =====================================================
-- แก้ไขปัญหา theme_settings ขาดคอลัมน์ใหม่
-- =====================================================
-- ไฟล์นี้เพิ่มคอลัมน์ที่ขาดหายจาก migration:
-- 2025_11_17_000001_add_background_effects_to_theme_settings_table.php
-- =====================================================

USE thaiprompt_affiliate;  -- เปลี่ยนชื่อ database ตามที่ใช้จริง

-- เช็คว่าตาราง theme_settings มีอยู่หรือไม่
SELECT 'Checking theme_settings table...' AS status;
SHOW TABLES LIKE 'theme_settings';

-- เพิ่มคอลัมน์ footer_logo_path (ถ้ายังไม่มี)
SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'theme_settings'
    AND COLUMN_NAME = 'footer_logo_path'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE theme_settings ADD COLUMN footer_logo_path VARCHAR(255) NULL AFTER logo_path',
    'SELECT "Column footer_logo_path already exists" AS info');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- เพิ่มคอลัมน์ footer_logo_animation (ถ้ายังไม่มี) ⭐ CRITICAL
SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'theme_settings'
    AND COLUMN_NAME = 'footer_logo_animation'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE theme_settings ADD COLUMN footer_logo_animation ENUM(''none'', ''float'', ''spin'', ''bounce'', ''pulse'', ''swing'') DEFAULT ''float'' COMMENT ''Footer Logo Animation Style''',
    'SELECT "Column footer_logo_animation already exists" AS info');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- เพิ่มคอลัมน์ bg_effects_enabled (ถ้ายังไม่มี)
SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'theme_settings'
    AND COLUMN_NAME = 'bg_effects_enabled'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE theme_settings ADD COLUMN bg_effects_enabled TINYINT(1) DEFAULT 1',
    'SELECT "Column bg_effects_enabled already exists" AS info');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- เพิ่มคอลัมน์ bg_circle1_color1 (ถ้ายังไม่มี)
SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'theme_settings'
    AND COLUMN_NAME = 'bg_circle1_color1'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE theme_settings ADD COLUMN bg_circle1_color1 VARCHAR(7) DEFAULT ''#22d3ee'' COMMENT ''Circle 1 Start Color''',
    'SELECT "Column bg_circle1_color1 already exists" AS info');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- เพิ่มคอลัมน์ bg_circle1_color2 (ถ้ายังไม่มี)
SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'theme_settings'
    AND COLUMN_NAME = 'bg_circle1_color2'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE theme_settings ADD COLUMN bg_circle1_color2 VARCHAR(7) DEFAULT ''#2563eb'' COMMENT ''Circle 1 End Color''',
    'SELECT "Column bg_circle1_color2 already exists" AS info');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- เพิ่มคอลัมน์ bg_circle2_color1 (ถ้ายังไม่มี)
SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'theme_settings'
    AND COLUMN_NAME = 'bg_circle2_color1'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE theme_settings ADD COLUMN bg_circle2_color1 VARCHAR(7) DEFAULT ''#f472b6'' COMMENT ''Circle 2 Start Color''',
    'SELECT "Column bg_circle2_color1 already exists" AS info');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- เพิ่มคอลัมน์ bg_circle2_color2 (ถ้ายังไม่มี)
SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'theme_settings'
    AND COLUMN_NAME = 'bg_circle2_color2'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE theme_settings ADD COLUMN bg_circle2_color2 VARCHAR(7) DEFAULT ''#9333ea'' COMMENT ''Circle 2 End Color''',
    'SELECT "Column bg_circle2_color2 already exists" AS info');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- เพิ่มคอลัมน์ bg_circle3_color1 (ถ้ายังไม่มี)
SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'theme_settings'
    AND COLUMN_NAME = 'bg_circle3_color1'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE theme_settings ADD COLUMN bg_circle3_color1 VARCHAR(7) DEFAULT ''#fbbf24'' COMMENT ''Circle 3 Start Color''',
    'SELECT "Column bg_circle3_color1 already exists" AS info');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- เพิ่มคอลัมน์ bg_circle3_color2 (ถ้ายังไม่มี)
SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'theme_settings'
    AND COLUMN_NAME = 'bg_circle3_color2'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE theme_settings ADD COLUMN bg_circle3_color2 VARCHAR(7) DEFAULT ''#f97316'' COMMENT ''Circle 3 End Color''',
    'SELECT "Column bg_circle3_color2 already exists" AS info');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- เพิ่มคอลัมน์ bg_animation_speed (ถ้ายังไม่มี)
SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'theme_settings'
    AND COLUMN_NAME = 'bg_animation_speed'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE theme_settings ADD COLUMN bg_animation_speed ENUM(''slow'', ''normal'', ''fast'') DEFAULT ''normal'' COMMENT ''Animation Speed''',
    'SELECT "Column bg_animation_speed already exists" AS info');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- เพิ่มคอลัมน์ bg_circle_opacity (ถ้ายังไม่มี)
SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'theme_settings'
    AND COLUMN_NAME = 'bg_circle_opacity'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE theme_settings ADD COLUMN bg_circle_opacity INT DEFAULT 15 COMMENT ''Circle Opacity (0-100)''',
    'SELECT "Column bg_circle_opacity already exists" AS info');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- เพิ่มคอลัมน์ bg_circle_blur (ถ้ายังไม่มี)
SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'theme_settings'
    AND COLUMN_NAME = 'bg_circle_blur'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE theme_settings ADD COLUMN bg_circle_blur INT DEFAULT 96 COMMENT ''Circle Blur Intensity (0-200)''',
    'SELECT "Column bg_circle_blur already exists" AS info');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- เพิ่มคอลัมน์ bg_circle_size (ถ้ายังไม่มี)
SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'theme_settings'
    AND COLUMN_NAME = 'bg_circle_size'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE theme_settings ADD COLUMN bg_circle_size INT DEFAULT 384 COMMENT ''Circle Size in px (200-800)''',
    'SELECT "Column bg_circle_size already exists" AS info');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- แสดงผลคอลัมน์ทั้งหมดใน theme_settings
SELECT 'All columns in theme_settings table:' AS status;
SHOW COLUMNS FROM theme_settings;

-- เช็คว่าคอลัมน์ใหม่ถูกเพิ่มแล้ว
SELECT '✅ Checking new columns...' AS status;
SELECT
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'theme_settings'
AND COLUMN_NAME IN (
    'footer_logo_path',
    'footer_logo_animation',
    'bg_effects_enabled',
    'bg_circle1_color1',
    'bg_circle1_color2',
    'bg_circle2_color1',
    'bg_circle2_color2',
    'bg_circle3_color1',
    'bg_circle3_color2',
    'bg_animation_speed',
    'bg_circle_opacity',
    'bg_circle_blur',
    'bg_circle_size'
)
ORDER BY ORDINAL_POSITION;

SELECT '✅ Done! Theme settings columns updated successfully!' AS status;
