-- =====================================================================
-- SQL Script: ลบ columns พิเศษจากตาราง line_signup_rewards
-- =====================================================================
-- วัตถุประสงค์: ลบ columns ที่ไม่ควรมีในตาราง template (line_signup_rewards)
--               เพราะ columns เหล่านี้ควรอยู่ในตาราง line_signup_reward_claims แทน
--
-- สร้างโดย: Migration 2025_11_23_184623_cleanup_old_columns_from_line_signup_rewards_table.php
-- วันที่: 2025-11-24
-- =====================================================================

-- ตรวจสอบว่าตารางมีอยู่จริง
SELECT 'Checking if line_signup_rewards table exists...' as status;

-- =====================================================================
-- ขั้นตอนที่ 1: ลบ indexes ที่เกี่ยวข้องกับ columns ที่จะลบ
-- =====================================================================

-- ลบ composite index (ถ้ามี)
DROP INDEX IF EXISTS idx_user_status ON line_signup_rewards;
DROP INDEX IF EXISTS idx_session_status ON line_signup_rewards;

-- ลบ single column indexes
DROP INDEX IF EXISTS line_signup_rewards_user_id_index ON line_signup_rewards;
DROP INDEX IF EXISTS line_signup_rewards_session_id_index ON line_signup_rewards;
DROP INDEX IF EXISTS line_signup_rewards_status_index ON line_signup_rewards;

SELECT 'Step 1: Dropped indexes successfully' as status;

-- =====================================================================
-- ขั้นตอนที่ 2: ลบ foreign key constraints
-- =====================================================================

-- ลบ foreign keys (ถ้ามี)
-- สำหรับ MySQL: ต้องระบุชื่อ constraint แน่นอน
-- หากไม่แน่ใจชื่อ foreign key ให้ query จาก information_schema ก่อน

-- Query ดูชื่อ foreign keys ที่มีอยู่:
-- SELECT
--     CONSTRAINT_NAME,
--     COLUMN_NAME,
--     REFERENCED_TABLE_NAME,
--     REFERENCED_COLUMN_NAME
-- FROM information_schema.KEY_COLUMN_USAGE
-- WHERE TABLE_SCHEMA = 'thaiprompt_affiliate'
--   AND TABLE_NAME = 'line_signup_rewards'
--   AND REFERENCED_TABLE_NAME IS NOT NULL;

-- ลบ foreign keys โดยอ้างอิงจากชื่อทั่วไป
SET @drop_fk_user_id = (
    SELECT CONCAT('ALTER TABLE line_signup_rewards DROP FOREIGN KEY ', CONSTRAINT_NAME, ';')
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'line_signup_rewards'
      AND COLUMN_NAME = 'user_id'
      AND REFERENCED_TABLE_NAME IS NOT NULL
    LIMIT 1
);

SET @drop_fk_session_id = (
    SELECT CONCAT('ALTER TABLE line_signup_rewards DROP FOREIGN KEY ', CONSTRAINT_NAME, ';')
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'line_signup_rewards'
      AND COLUMN_NAME = 'session_id'
      AND REFERENCED_TABLE_NAME IS NOT NULL
    LIMIT 1
);

-- Execute dynamic SQL (ถ้ามี foreign keys)
PREPARE stmt FROM COALESCE(@drop_fk_user_id, 'SELECT "No user_id FK to drop" as status');
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

PREPARE stmt FROM COALESCE(@drop_fk_session_id, 'SELECT "No session_id FK to drop" as status');
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Step 2: Dropped foreign keys successfully' as status;

-- =====================================================================
-- ขั้นตอนที่ 3: ลบ columns ที่ไม่ต้องการ
-- =====================================================================

-- ลบ columns ทีละตัว (เพื่อความปลอดภัย)
-- ใช้ IF EXISTS ใน MySQL 8.0.12+

-- Column: user_id
SET @col_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'line_signup_rewards'
      AND COLUMN_NAME = 'user_id'
);

SET @sql = IF(@col_exists > 0,
    'ALTER TABLE line_signup_rewards DROP COLUMN user_id',
    'SELECT "Column user_id does not exist, skipping..." as status'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Column: session_id
SET @col_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'line_signup_rewards'
      AND COLUMN_NAME = 'session_id'
);

SET @sql = IF(@col_exists > 0,
    'ALTER TABLE line_signup_rewards DROP COLUMN session_id',
    'SELECT "Column session_id does not exist, skipping..." as status'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Column: reward_name
SET @col_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'line_signup_rewards'
      AND COLUMN_NAME = 'reward_name'
);

SET @sql = IF(@col_exists > 0,
    'ALTER TABLE line_signup_rewards DROP COLUMN reward_name',
    'SELECT "Column reward_name does not exist, skipping..." as status'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Column: reward_amount
SET @col_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'line_signup_rewards'
      AND COLUMN_NAME = 'reward_amount'
);

SET @sql = IF(@col_exists > 0,
    'ALTER TABLE line_signup_rewards DROP COLUMN reward_amount',
    'SELECT "Column reward_amount does not exist, skipping..." as status'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Column: reward_description
SET @col_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'line_signup_rewards'
      AND COLUMN_NAME = 'reward_description'
);

SET @sql = IF(@col_exists > 0,
    'ALTER TABLE line_signup_rewards DROP COLUMN reward_description',
    'SELECT "Column reward_description does not exist, skipping..." as status'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Column: status
SET @col_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'line_signup_rewards'
      AND COLUMN_NAME = 'status'
);

SET @sql = IF(@col_exists > 0,
    'ALTER TABLE line_signup_rewards DROP COLUMN status',
    'SELECT "Column status does not exist, skipping..." as status'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Column: granted_at
SET @col_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'line_signup_rewards'
      AND COLUMN_NAME = 'granted_at'
);

SET @sql = IF(@col_exists > 0,
    'ALTER TABLE line_signup_rewards DROP COLUMN granted_at',
    'SELECT "Column granted_at does not exist, skipping..." as status'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Column: claimed_at
SET @col_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'line_signup_rewards'
      AND COLUMN_NAME = 'claimed_at'
);

SET @sql = IF(@col_exists > 0,
    'ALTER TABLE line_signup_rewards DROP COLUMN claimed_at',
    'SELECT "Column claimed_at does not exist, skipping..." as status'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Column: expires_at
SET @col_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'line_signup_rewards'
      AND COLUMN_NAME = 'expires_at'
);

SET @sql = IF(@col_exists > 0,
    'ALTER TABLE line_signup_rewards DROP COLUMN expires_at',
    'SELECT "Column expires_at does not exist, skipping..." as status'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Step 3: Dropped columns successfully' as status;

-- =====================================================================
-- ขั้นตอนที่ 4: ตรวจสอบโครงสร้างตารางหลังจากลบเสร็จ
-- =====================================================================

SELECT '========================================' as '';
SELECT 'CLEANUP COMPLETED SUCCESSFULLY!' as status;
SELECT '========================================' as '';
SELECT 'Remaining columns in line_signup_rewards:' as '';

DESCRIBE line_signup_rewards;

-- ตรวจสอบว่าไม่มี columns ที่ไม่ต้องการเหลืออยู่
SELECT
    CASE
        WHEN COUNT(*) = 0 THEN '✅ All unwanted columns removed successfully!'
        ELSE CONCAT('⚠️ WARNING: Found ', COUNT(*), ' unwanted column(s) remaining!')
    END as final_check
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'line_signup_rewards'
  AND COLUMN_NAME IN (
      'user_id', 'session_id', 'reward_name', 'reward_amount',
      'reward_description', 'status', 'granted_at', 'claimed_at', 'expires_at'
  );

-- =====================================================================
-- วิธีการใช้งาน:
-- =====================================================================
-- 1. Connect to MySQL database:
--    mysql -u root -p thaiprompt_affiliate
--
-- 2. Run this script:
--    source database/sql/cleanup_line_signup_rewards_table.sql
--
-- 3. หรือใช้ command:
--    mysql -u root -p thaiprompt_affiliate < database/sql/cleanup_line_signup_rewards_table.sql
-- =====================================================================
