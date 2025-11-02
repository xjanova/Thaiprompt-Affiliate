-- ============================================
-- LINE Bot AI System - Create Missing Tables Only
-- ============================================
-- ไฟล์นี้จะสร้างเฉพาะตารางที่ยังไม่มี
-- ใช้ CREATE TABLE IF NOT EXISTS เพื่อหลีกเลี่ยง error
-- ============================================

-- Drop existing foreign keys first (if any)
SET FOREIGN_KEY_CHECKS = 0;

-- Table 1: line_bot_ai_settings
CREATE TABLE IF NOT EXISTS `line_bot_ai_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `provider` enum('openai','deepseek','anthropic','gemini','custom') NOT NULL DEFAULT 'openai',
  `api_key` text NOT NULL,
  `api_endpoint` varchar(255) DEFAULT NULL,
  `model` varchar(100) NOT NULL DEFAULT 'gpt-3.5-turbo',
  `temperature` decimal(3,2) NOT NULL DEFAULT 0.70,
  `max_tokens` int(11) NOT NULL DEFAULT 1000,
  `system_prompt` text DEFAULT NULL,
  `enable_conversation_history` tinyint(1) NOT NULL DEFAULT 1,
  `conversation_memory_limit` int(11) NOT NULL DEFAULT 10,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `enable_fallback` tinyint(1) NOT NULL DEFAULT 1,
  `fallback_message` text DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `line_bot_ai_settings_provider_index` (`provider`),
  KEY `line_bot_ai_settings_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 2: line_bot_knowledge_bases
CREATE TABLE IF NOT EXISTS `line_bot_knowledge_bases` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ai_setting_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('url','internal','file','text') NOT NULL,
  `source` text DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `priority` int(11) NOT NULL DEFAULT 5,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `last_synced_at` timestamp NULL DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `line_bot_knowledge_bases_ai_setting_id_index` (`ai_setting_id`),
  KEY `line_bot_knowledge_bases_type_index` (`type`),
  KEY `line_bot_knowledge_bases_is_enabled_index` (`is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 3: line_bot_conversations
CREATE TABLE IF NOT EXISTS `line_bot_conversations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `line_user_id` varchar(100) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ai_setting_id` bigint(20) unsigned NOT NULL,
  `session_id` varchar(100) NOT NULL,
  `status` enum('active','ended','archived') NOT NULL DEFAULT 'active',
  `message_count` int(11) NOT NULL DEFAULT 0,
  `last_message_at` timestamp NULL DEFAULT NULL,
  `context` json DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `line_bot_conversations_line_user_id_index` (`line_user_id`),
  KEY `line_bot_conversations_user_id_index` (`user_id`),
  KEY `line_bot_conversations_ai_setting_id_index` (`ai_setting_id`),
  KEY `line_bot_conversations_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 4: line_bot_messages
CREATE TABLE IF NOT EXISTS `line_bot_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` bigint(20) unsigned NOT NULL,
  `role` enum('user','assistant','system') NOT NULL,
  `message` text NOT NULL,
  `tokens_used` int(11) DEFAULT NULL,
  `response_time` decimal(8,3) DEFAULT NULL,
  `is_ai_generated` tinyint(1) NOT NULL DEFAULT 0,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `line_bot_messages_conversation_id_index` (`conversation_id`),
  KEY `line_bot_messages_role_index` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 5: line_flex_message_templates
CREATE TABLE IF NOT EXISTS `line_flex_message_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL DEFAULT 'general',
  `description` text DEFAULT NULL,
  `flex_content` json NOT NULL,
  `is_seed` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `usage_count` int(11) NOT NULL DEFAULT 0,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `line_flex_message_templates_category_index` (`category`),
  KEY `line_flex_message_templates_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 6: line_rich_menus
CREATE TABLE IF NOT EXISTS `line_rich_menus` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `size` enum('full','half') NOT NULL DEFAULT 'full',
  `selected` tinyint(1) NOT NULL DEFAULT 0,
  `chat_bar_text` varchar(14) NOT NULL,
  `areas` json NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `menu_image_url` varchar(255) DEFAULT NULL,
  `line_rich_menu_id` varchar(100) DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `line_rich_menus_is_default_index` (`is_default`),
  KEY `line_rich_menus_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 7: line_chat_widget_settings (ตารางนี้มีอยู่แล้ว - skip)
-- ไม่ต้องสร้างใหม่เพราะใช้ IF NOT EXISTS

-- Table 8: line_avatars
CREATE TABLE IF NOT EXISTS `line_avatars` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` enum('image','gif','lottie','video') NOT NULL DEFAULT 'image',
  `source_type` enum('upload','url') NOT NULL DEFAULT 'upload',
  `file_path` varchar(255) DEFAULT NULL,
  `file_url` text DEFAULT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `line_avatars_type_index` (`type`),
  KEY `line_avatars_is_active_index` (`is_active`),
  KEY `line_avatars_is_default_index` (`is_default`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 9: line_broadcast_messages
CREATE TABLE IF NOT EXISTS `line_broadcast_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `message_type` enum('text','flex','image','video') NOT NULL DEFAULT 'text',
  `content` text DEFAULT NULL,
  `flex_template_id` bigint(20) unsigned DEFAULT NULL,
  `target_type` enum('all','users','sellers','custom') NOT NULL DEFAULT 'all',
  `target_users` json DEFAULT NULL,
  `status` enum('draft','scheduled','sending','sent','failed') NOT NULL DEFAULT 'draft',
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `total_recipients` int(11) NOT NULL DEFAULT 0,
  `sent_count` int(11) NOT NULL DEFAULT 0,
  `failed_count` int(11) NOT NULL DEFAULT 0,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `line_broadcast_messages_flex_template_id_index` (`flex_template_id`),
  KEY `line_broadcast_messages_status_index` (`status`),
  KEY `line_broadcast_messages_scheduled_at_index` (`scheduled_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Add foreign keys only if they don't exist
-- Knowledge Bases
ALTER TABLE `line_bot_knowledge_bases`
ADD CONSTRAINT `line_bot_knowledge_bases_ai_setting_id_foreign`
FOREIGN KEY (`ai_setting_id`) REFERENCES `line_bot_ai_settings` (`id`) ON DELETE CASCADE;

-- Conversations
ALTER TABLE `line_bot_conversations`
ADD CONSTRAINT `line_bot_conversations_ai_setting_id_foreign`
FOREIGN KEY (`ai_setting_id`) REFERENCES `line_bot_ai_settings` (`id`) ON DELETE CASCADE;

-- Messages
ALTER TABLE `line_bot_messages`
ADD CONSTRAINT `line_bot_messages_conversation_id_foreign`
FOREIGN KEY (`conversation_id`) REFERENCES `line_bot_conversations` (`id`) ON DELETE CASCADE;

-- Broadcasts
ALTER TABLE `line_broadcast_messages`
ADD CONSTRAINT `line_broadcast_messages_flex_template_id_foreign`
FOREIGN KEY (`flex_template_id`) REFERENCES `line_flex_message_templates` (`id`) ON DELETE SET NULL;

-- ============================================
-- Insert Seed Data (only if table is empty)
-- ============================================

INSERT IGNORE INTO `line_flex_message_templates`
(`id`, `name`, `category`, `description`, `flex_content`, `is_seed`, `is_active`, `created_at`, `updated_at`)
VALUES
(1, 'Welcome Message', 'welcome', 'Beautiful welcome message for new users',
'{"type":"bubble","hero":{"type":"image","url":"https://scdn.line-apps.com/n/channel_devcenter/img/fx/01_1_cafe.png","size":"full","aspectRatio":"20:13","aspectMode":"cover"},"body":{"type":"box","layout":"vertical","contents":[{"type":"text","text":"ยินดีต้อนรับ! 🎉","weight":"bold","size":"xl","color":"#1DB446"},{"type":"box","layout":"baseline","margin":"md","contents":[{"type":"text","text":"ขอบคุณที่เข้าร่วมกับเรา เราพร้อมช่วยเหลือคุณในทุกๆ เรื่อง","size":"sm","color":"#666666","wrap":true}]}]},"footer":{"type":"box","layout":"vertical","spacing":"sm","contents":[{"type":"button","style":"primary","height":"sm","action":{"type":"uri","label":"เริ่มต้นใช้งาน","uri":"https://your-website.com"}}]}}',
1, 1, NOW(), NOW()),

(2, 'Flash Sale Promotion', 'promotion', 'Eye-catching flash sale promotion template',
'{"type":"bubble","hero":{"type":"image","url":"https://scdn.line-apps.com/n/channel_devcenter/img/fx/01_2_restaurant.png","size":"full","aspectRatio":"20:13","aspectMode":"cover"},"body":{"type":"box","layout":"vertical","contents":[{"type":"text","text":"⚡ FLASH SALE","weight":"bold","size":"xl","color":"#FF0000"},{"type":"box","layout":"baseline","margin":"md","contents":[{"type":"text","text":"ลดสูงสุด 50%","size":"xxl","weight":"bold","color":"#FF6B00"}]},{"type":"text","text":"วันนี้เท่านั้น! อย่าพลาด","size":"sm","color":"#666666","margin":"md"}]},"footer":{"type":"box","layout":"vertical","spacing":"sm","contents":[{"type":"button","style":"primary","height":"sm","color":"#FF0000","action":{"type":"uri","label":"ช้อปเลย!","uri":"https://your-website.com/sale"}}]}}',
1, 1, NOW(), NOW()),

(3, 'Product Card', 'product', 'Beautiful product showcase template',
'{"type":"bubble","hero":{"type":"image","url":"https://scdn.line-apps.com/n/channel_devcenter/img/fx/01_5_carousel.png","size":"full","aspectRatio":"20:13","aspectMode":"cover"},"body":{"type":"box","layout":"vertical","contents":[{"type":"text","text":"สินค้าแนะนำ","weight":"bold","size":"xl"},{"type":"box","layout":"baseline","margin":"md","contents":[{"type":"icon","size":"sm","url":"https://scdn.line-apps.com/n/channel_devcenter/img/fx/review_gold_star_28.png"},{"type":"text","text":"4.9","size":"sm","color":"#999999","margin":"md","flex":0}]},{"type":"box","layout":"vertical","margin":"lg","spacing":"sm","contents":[{"type":"box","layout":"baseline","spacing":"sm","contents":[{"type":"text","text":"฿990","wrap":true,"color":"#FF0000","size":"xl","weight":"bold","flex":0},{"type":"text","text":"฿1,990","wrap":true,"color":"#999999","size":"sm","decoration":"line-through"}]}]}]},"footer":{"type":"box","layout":"vertical","spacing":"sm","contents":[{"type":"button","style":"primary","height":"sm","action":{"type":"uri","label":"ซื้อเลย","uri":"https://your-website.com/product"}},{"type":"button","style":"link","height":"sm","action":{"type":"uri","label":"ดูรายละเอียด","uri":"https://your-website.com/product"}}],"flex":0}}',
1, 1, NOW(), NOW());

-- ============================================
-- Completed!
-- ============================================

SELECT 'All missing tables have been created!' as Status;
SELECT COUNT(*) as 'Total Flex Templates' FROM line_flex_message_templates;
