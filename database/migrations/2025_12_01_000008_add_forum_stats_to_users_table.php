<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์สถิติ Forum ในตาราง users
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // ตรวจสอบและเพิ่มคอลัมน์ถ้ายังไม่มี
            if (! Schema::hasColumn('users', 'forum_threads_count')) {
                $table->unsignedInteger('forum_threads_count')->default(0)->after('email');
            }

            if (! Schema::hasColumn('users', 'forum_posts_count')) {
                $table->unsignedInteger('forum_posts_count')->default(0)->after('forum_threads_count');
            }

            if (! Schema::hasColumn('users', 'forum_likes_received')) {
                $table->unsignedInteger('forum_likes_received')->default(0)->after('forum_posts_count');
            }

            if (! Schema::hasColumn('users', 'forum_likes_given')) {
                $table->unsignedInteger('forum_likes_given')->default(0)->after('forum_likes_received');
            }

            if (! Schema::hasColumn('users', 'forum_best_answers_count')) {
                $table->unsignedInteger('forum_best_answers_count')->default(0)->after('forum_likes_given');
            }

            if (! Schema::hasColumn('users', 'forum_warnings_count')) {
                $table->unsignedInteger('forum_warnings_count')->default(0)->after('forum_best_answers_count');
            }

            if (! Schema::hasColumn('users', 'forum_banned_until')) {
                $table->timestamp('forum_banned_until')->nullable()->after('forum_warnings_count');
            }

            if (! Schema::hasColumn('users', 'forum_spam_reports')) {
                $table->unsignedInteger('forum_spam_reports')->default(0)->after('forum_banned_until');
            }

            if (! Schema::hasColumn('users', 'forum_banned_count')) {
                $table->unsignedInteger('forum_banned_count')->default(0)->after('forum_spam_reports');
            }
        });
    }

    /**
     * ลบคอลัมน์สถิติ Forum
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columnsToRemove = [
                'forum_threads_count',
                'forum_posts_count',
                'forum_likes_received',
                'forum_likes_given',
                'forum_best_answers_count',
                'forum_warnings_count',
                'forum_banned_until',
                'forum_spam_reports',
                'forum_banned_count',
            ];

            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
