#!/bin/bash

###############################################################################
# Fix LINE Signup Tables - Create Missing Tables Manually
# Purpose: สร้างตารางที่หายไป 6 ตาราง (line_signup_step_logs และอื่นๆ)
# Version: 1.0.0
# Date: 2025-11-17
###############################################################################

set -e

echo "╔════════════════════════════════════════════════════════════════╗"
echo "║   Fix Missing LINE Signup Tables                              ║"
echo "║   กำลังสร้างตารางที่หายไป 6 ตาราง                             ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

# Create missing tables using raw SQL
php artisan tinker --execute="
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo '🔧 Creating missing tables...' . PHP_EOL . PHP_EOL;

// 1. line_signup_step_logs
if (!Schema::hasTable('line_signup_step_logs')) {
    DB::statement(\"
        CREATE TABLE line_signup_step_logs (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            session_id BIGINT UNSIGNED NOT NULL,
            step_name VARCHAR(255) NOT NULL,
            status ENUM('started', 'completed', 'skipped', 'failed') DEFAULT 'started',
            step_data JSON NULL,
            error_message TEXT NULL,
            attempts INT NOT NULL DEFAULT 1,
            started_at TIMESTAMP NOT NULL,
            completed_at TIMESTAMP NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            KEY idx_session_step (session_id, step_name),
            CONSTRAINT fk_step_logs_session
                FOREIGN KEY (session_id)
                REFERENCES line_signup_sessions(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    \");
    echo '✅ Created: line_signup_step_logs' . PHP_EOL;
} else {
    echo '⏭️  Exists: line_signup_step_logs' . PHP_EOL;
}

// 2. line_signup_conversations
if (!Schema::hasTable('line_signup_conversations')) {
    DB::statement(\"
        CREATE TABLE line_signup_conversations (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            session_id BIGINT UNSIGNED NOT NULL,
            role ENUM('user', 'assistant', 'system') NOT NULL,
            message TEXT NOT NULL,
            metadata JSON NULL,
            message_type VARCHAR(255) DEFAULT 'text',
            line_message_payload JSON NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            KEY idx_session_created (session_id, created_at),
            CONSTRAINT fk_conversations_session
                FOREIGN KEY (session_id)
                REFERENCES line_signup_sessions(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    \");
    echo '✅ Created: line_signup_conversations' . PHP_EOL;
} else {
    echo '⏭️  Exists: line_signup_conversations' . PHP_EOL;
}

// 3. line_signup_analytics
if (!Schema::hasTable('line_signup_analytics')) {
    DB::statement(\"
        CREATE TABLE line_signup_analytics (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            date DATE NOT NULL,
            step_name VARCHAR(255) NOT NULL,
            visitors INT NOT NULL DEFAULT 0,
            completions INT NOT NULL DEFAULT 0,
            drop_offs INT NOT NULL DEFAULT 0,
            average_time_seconds DECIMAL(10,2) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            UNIQUE KEY uk_date_step (date, step_name),
            KEY idx_date (date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    \");
    echo '✅ Created: line_signup_analytics' . PHP_EOL;
} else {
    echo '⏭️  Exists: line_signup_analytics' . PHP_EOL;
}

// 4. line_signup_rewards
if (!Schema::hasTable('line_signup_rewards')) {
    DB::statement(\"
        CREATE TABLE line_signup_rewards (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            session_id BIGINT UNSIGNED NOT NULL,
            reward_type VARCHAR(255) NOT NULL,
            reward_name VARCHAR(255) NOT NULL,
            reward_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
            reward_description TEXT NULL,
            status ENUM('pending', 'granted', 'claimed', 'expired') DEFAULT 'pending',
            granted_at TIMESTAMP NULL,
            claimed_at TIMESTAMP NULL,
            expires_at TIMESTAMP NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            KEY idx_user_status (user_id, status),
            CONSTRAINT fk_rewards_user
                FOREIGN KEY (user_id)
                REFERENCES users(id)
                ON DELETE CASCADE,
            CONSTRAINT fk_rewards_session
                FOREIGN KEY (session_id)
                REFERENCES line_signup_sessions(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    \");
    echo '✅ Created: line_signup_rewards' . PHP_EOL;
} else {
    echo '⏭️  Exists: line_signup_rewards' . PHP_EOL;
}

// 5. line_signup_invitations
if (!Schema::hasTable('line_signup_invitations')) {
    DB::statement(\"
        CREATE TABLE line_signup_invitations (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            inviter_user_id BIGINT UNSIGNED NOT NULL,
            invitation_token VARCHAR(255) NOT NULL UNIQUE,
            line_user_id VARCHAR(255) NULL,
            referral_code VARCHAR(255) NOT NULL,
            max_uses INT NOT NULL DEFAULT 1,
            uses_count INT NOT NULL DEFAULT 0,
            status ENUM('active', 'used', 'expired') DEFAULT 'active',
            metadata JSON NULL,
            expires_at TIMESTAMP NULL,
            first_used_at TIMESTAMP NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            KEY idx_token_status (invitation_token, status),
            KEY idx_referral_code (referral_code),
            CONSTRAINT fk_invitations_user
                FOREIGN KEY (inviter_user_id)
                REFERENCES users(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    \");
    echo '✅ Created: line_signup_invitations' . PHP_EOL;
} else {
    echo '⏭️  Exists: line_signup_invitations' . PHP_EOL;
}

// 6. line_signup_webhook_logs
if (!Schema::hasTable('line_signup_webhook_logs')) {
    DB::statement(\"
        CREATE TABLE line_signup_webhook_logs (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            line_user_id VARCHAR(255) NULL,
            event_type VARCHAR(255) NOT NULL,
            webhook_payload JSON NOT NULL,
            response_payload JSON NULL,
            processing_status ENUM('received', 'processing', 'completed', 'failed') DEFAULT 'received',
            error_message TEXT NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            KEY idx_user_created (line_user_id, created_at),
            KEY idx_event_type (event_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    \");
    echo '✅ Created: line_signup_webhook_logs' . PHP_EOL;
} else {
    echo '⏭️  Exists: line_signup_webhook_logs' . PHP_EOL;
}

echo PHP_EOL . '✅ All missing tables created successfully!' . PHP_EOL;
"

echo ""
echo "════════════════════════════════════════════════════════════════"
echo "  ✅ Done! Verifying tables..."
echo "════════════════════════════════════════════════════════════════"
echo ""

# Verify tables
php artisan tinker --execute="
use Illuminate\Support\Facades\Schema;

\$tables = [
    'line_signup_sessions',
    'line_signup_step_logs',
    'line_signup_conversations',
    'line_signup_templates',
    'line_signup_rewards',
    'line_signup_invitations',
    'line_signup_analytics',
    'line_signup_webhook_logs',
];

echo 'Tables verification:' . PHP_EOL;
foreach (\$tables as \$table) {
    \$exists = Schema::hasTable(\$table);
    echo (\$exists ? '✅' : '❌') . ' ' . \$table . PHP_EOL;
}
"

echo ""
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║              🎉 Fix Complete! 🎉                               ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""
