-- ============================================================
-- Fortune System - Pending Migrations SQL
-- รันใน phpMyAdmin หรือ MySQL Client ได้เลย
-- สร้างจาก migration files ที่ยังไม่ได้รัน
-- ============================================================

-- ============================================================
-- 1. สร้างตาราง fortune_comment_engagements (ถ้ายังไม่มี)
-- ============================================================
CREATE TABLE IF NOT EXISTS `fortune_comment_engagements` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `facebook_user_id` varchar(100) NOT NULL,
    `facebook_post_id` varchar(100) NOT NULL,
    `facebook_comment_id` varchar(100) NOT NULL,
    `comment_text` text DEFAULT NULL,
    `comment_reply` text DEFAULT NULL,
    `dm_message` text DEFAULT NULL,
    `user_profile` json DEFAULT NULL,
    `engaged_at` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `fortune_comment_engagements_facebook_user_id_index` (`facebook_user_id`),
    INDEX `fortune_comment_engagements_facebook_post_id_index` (`facebook_post_id`),
    UNIQUE KEY `unique_user_post_engagement` (`facebook_user_id`, `facebook_post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. เพิ่มคอลัมน์ Comment Engagement ในตาราง fortune_telling_settings
-- ============================================================
SET @table_name = 'fortune_telling_settings';

-- comment_engagement_enabled
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = @table_name AND COLUMN_NAME = 'comment_engagement_enabled');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `fortune_telling_settings` ADD COLUMN `comment_engagement_enabled` tinyint(1) NOT NULL DEFAULT 0 AFTER `respond_in_comment`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- comment_engagement_mode
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = @table_name AND COLUMN_NAME = 'comment_engagement_mode');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `fortune_telling_settings` ADD COLUMN `comment_engagement_mode` varchar(20) NOT NULL DEFAULT \'ai\' AFTER `comment_engagement_enabled`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- comment_reply_template
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = @table_name AND COLUMN_NAME = 'comment_reply_template');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `fortune_telling_settings` ADD COLUMN `comment_reply_template` text DEFAULT NULL AFTER `comment_engagement_mode`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- comment_dm_template
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = @table_name AND COLUMN_NAME = 'comment_dm_template');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `fortune_telling_settings` ADD COLUMN `comment_dm_template` text DEFAULT NULL AFTER `comment_reply_template`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- comment_engagement_prompt
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = @table_name AND COLUMN_NAME = 'comment_engagement_prompt');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `fortune_telling_settings` ADD COLUMN `comment_engagement_prompt` text DEFAULT NULL AFTER `comment_dm_template`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- 3. เพิ่มคอลัมน์ Admin Handover ในตาราง fortune_telling_settings
-- ============================================================

-- admin_handover_enabled
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = @table_name AND COLUMN_NAME = 'admin_handover_enabled');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `fortune_telling_settings` ADD COLUMN `admin_handover_enabled` tinyint(1) NOT NULL DEFAULT 1 COMMENT \'เปิด/ปิดระบบ Admin Handover\'', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- admin_handover_timeout
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = @table_name AND COLUMN_NAME = 'admin_handover_timeout');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `fortune_telling_settings` ADD COLUMN `admin_handover_timeout` int unsigned NOT NULL DEFAULT 15 COMMENT \'ระยะเวลา (นาที) ที่บอทหยุดหลังแอดมินตอบ\'', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- 4. แก้ไข fortune_readings - ai_response และ ai_provider ให้เป็น nullable
-- ปัญหา: ระบบสร้าง record ก่อน แล้วค่อยอัพเดทหลัง AI ตอบ
-- แต่คอลัมน์เป็น NOT NULL ทำให้ INSERT ล้มเหลว
-- ============================================================
ALTER TABLE `fortune_readings` MODIFY COLUMN `ai_response` longtext DEFAULT NULL COMMENT 'คำทำนายจาก AI';
ALTER TABLE `fortune_readings` MODIFY COLUMN `ai_provider` varchar(50) DEFAULT NULL COMMENT 'AI Provider ที่ใช้ (gemini, groq, qwen)';
ALTER TABLE `fortune_readings` MODIFY COLUMN `basic_response` longtext DEFAULT NULL COMMENT 'คำทำนายพื้นฐาน';
ALTER TABLE `fortune_readings` MODIFY COLUMN `deep_response` longtext DEFAULT NULL COMMENT 'คำทำนายเชิงลึก';

-- ============================================================
-- 5. บันทึกใน migrations table ว่ารันแล้ว (ป้องกันรันซ้ำ)
-- ============================================================
INSERT IGNORE INTO `migrations` (`migration`, `batch`)
SELECT '2026_02_01_100000_add_comment_engagement_to_fortune_telling_settings_table', COALESCE(MAX(batch), 0) + 1 FROM `migrations`
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_02_01_100000_add_comment_engagement_to_fortune_telling_settings_table');

INSERT IGNORE INTO `migrations` (`migration`, `batch`)
SELECT '2026_02_06_100000_add_admin_handover_to_fortune_telling_settings_table', COALESCE(MAX(batch), 0) + 1 FROM `migrations`
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_02_06_100000_add_admin_handover_to_fortune_telling_settings_table');

INSERT IGNORE INTO `migrations` (`migration`, `batch`)
SELECT '2026_02_07_130000_fix_fortune_readings_nullable_columns', COALESCE(MAX(batch), 0) + 1 FROM `migrations`
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_02_07_130000_fix_fortune_readings_nullable_columns');

-- ============================================================
-- เสร็จสิ้น! ตรวจสอบผลลัพธ์:
-- ============================================================
SELECT 'fortune_comment_engagements' AS `table`, COUNT(*) AS `rows` FROM `fortune_comment_engagements`
UNION ALL
SELECT 'fortune_telling_settings', COUNT(*) FROM `fortune_telling_settings`;
