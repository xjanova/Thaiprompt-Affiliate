-- ============================================================================
-- Fix Missing LINE Signup Tables
-- Purpose: สร้างตารางที่หายไป 6 ตาราง
-- Database: admin_mlmtestthai (หรือ database ที่ใช้งาน)
-- Date: 2025-11-17
-- ============================================================================

-- ตรวจสอบว่าใช้ database ที่ถูกต้อง
-- USE admin_mlmtestthai;

-- ============================================================================
-- 1. line_signup_step_logs (ตารางหลักที่ต้องการ)
-- ============================================================================

CREATE TABLE IF NOT EXISTS `line_signup_step_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `session_id` BIGINT UNSIGNED NOT NULL,
    `step_name` VARCHAR(255) NOT NULL,
    `status` ENUM('started', 'completed', 'skipped', 'failed') DEFAULT 'started',
    `step_data` JSON NULL,
    `error_message` TEXT NULL,
    `attempts` INT NOT NULL DEFAULT 1,
    `started_at` TIMESTAMP NOT NULL,
    `completed_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    KEY `idx_session_step` (`session_id`, `step_name`),
    CONSTRAINT `fk_step_logs_session`
        FOREIGN KEY (`session_id`)
        REFERENCES `line_signup_sessions`(`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 2. line_signup_conversations
-- ============================================================================

CREATE TABLE IF NOT EXISTS `line_signup_conversations` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `session_id` BIGINT UNSIGNED NOT NULL,
    `role` ENUM('user', 'assistant', 'system') NOT NULL,
    `message` TEXT NOT NULL,
    `metadata` JSON NULL,
    `message_type` VARCHAR(255) DEFAULT 'text',
    `line_message_payload` JSON NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    KEY `idx_session_created` (`session_id`, `created_at`),
    CONSTRAINT `fk_conversations_session`
        FOREIGN KEY (`session_id`)
        REFERENCES `line_signup_sessions`(`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 3. line_signup_analytics
-- ============================================================================

CREATE TABLE IF NOT EXISTS `line_signup_analytics` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `date` DATE NOT NULL,
    `step_name` VARCHAR(255) NOT NULL,
    `visitors` INT NOT NULL DEFAULT 0,
    `completions` INT NOT NULL DEFAULT 0,
    `drop_offs` INT NOT NULL DEFAULT 0,
    `average_time_seconds` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    UNIQUE KEY `uk_date_step` (`date`, `step_name`),
    KEY `idx_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 4. line_signup_rewards
-- ============================================================================

CREATE TABLE IF NOT EXISTS `line_signup_rewards` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `session_id` BIGINT UNSIGNED NOT NULL,
    `reward_type` VARCHAR(255) NOT NULL,
    `reward_name` VARCHAR(255) NOT NULL,
    `reward_amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `reward_description` TEXT NULL,
    `status` ENUM('pending', 'granted', 'claimed', 'expired') DEFAULT 'pending',
    `granted_at` TIMESTAMP NULL,
    `claimed_at` TIMESTAMP NULL,
    `expires_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    KEY `idx_user_status` (`user_id`, `status`),
    CONSTRAINT `fk_rewards_user`
        FOREIGN KEY (`user_id`)
        REFERENCES `users`(`id`)
        ON DELETE CASCADE,
    CONSTRAINT `fk_rewards_session`
        FOREIGN KEY (`session_id`)
        REFERENCES `line_signup_sessions`(`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 5. line_signup_invitations
-- ============================================================================

CREATE TABLE IF NOT EXISTS `line_signup_invitations` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `inviter_user_id` BIGINT UNSIGNED NOT NULL,
    `invitation_token` VARCHAR(255) NOT NULL UNIQUE,
    `line_user_id` VARCHAR(255) NULL,
    `referral_code` VARCHAR(255) NOT NULL,
    `max_uses` INT NOT NULL DEFAULT 1,
    `uses_count` INT NOT NULL DEFAULT 0,
    `status` ENUM('active', 'used', 'expired') DEFAULT 'active',
    `metadata` JSON NULL,
    `expires_at` TIMESTAMP NULL,
    `first_used_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    KEY `idx_token_status` (`invitation_token`, `status`),
    KEY `idx_referral_code` (`referral_code`),
    CONSTRAINT `fk_invitations_user`
        FOREIGN KEY (`inviter_user_id`)
        REFERENCES `users`(`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 6. line_signup_webhook_logs
-- ============================================================================

CREATE TABLE IF NOT EXISTS `line_signup_webhook_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `line_user_id` VARCHAR(255) NULL,
    `event_type` VARCHAR(255) NOT NULL,
    `webhook_payload` JSON NOT NULL,
    `response_payload` JSON NULL,
    `processing_status` ENUM('received', 'processing', 'completed', 'failed') DEFAULT 'received',
    `error_message` TEXT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    KEY `idx_user_created` (`line_user_id`, `created_at`),
    KEY `idx_event_type` (`event_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Verification Queries (ใช้หลังจากรัน script)
-- ============================================================================

-- แสดงตารางทั้งหมดที่เกี่ยวข้อง
-- SHOW TABLES LIKE 'line_signup%';

-- ตรวจสอบโครงสร้างตาราง
-- DESCRIBE line_signup_step_logs;
-- DESCRIBE line_signup_conversations;
-- DESCRIBE line_signup_analytics;
-- DESCRIBE line_signup_rewards;
-- DESCRIBE line_signup_invitations;
-- DESCRIBE line_signup_webhook_logs;

-- นับจำนวนตาราง (ควรได้ 8 ตาราง)
-- SELECT COUNT(*) as total_tables
-- FROM information_schema.tables
-- WHERE table_schema = DATABASE()
--   AND table_name LIKE 'line_signup%';

-- ============================================================================
-- END OF SCRIPT
-- ============================================================================
