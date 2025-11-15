<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่ม indexes สำหรับตารางเกม เพื่อเพิ่มประสิทธิภาพ
     *
     * @return void
     */
    public function up(): void
    {
        // เพิ่ม indexes สำหรับตาราง game_rooms
        if (Schema::hasTable('game_rooms')) {
            Schema::table('game_rooms', function (Blueprint $table) {
                // Index สำหรับค้นหาห้องที่ว่าง (status + current_players)
                if (!$this->indexExists('game_rooms', 'game_rooms_status_players_idx')) {
                    $table->index(['status', 'current_players'], 'game_rooms_status_players_idx');
                }

                // Index สำหรับค้นหาห้องตาม game_id และ status
                if (!$this->indexExists('game_rooms', 'game_rooms_game_status_idx')) {
                    $table->index(['game_id', 'status'], 'game_rooms_game_status_idx');
                }

                // Index สำหรับค้นหาห้องด้วย room_code (สำหรับ join by code)
                if (!$this->indexExists('game_rooms', 'game_rooms_code_idx')) {
                    $table->index('room_code', 'game_rooms_code_idx');
                }

                // Index สำหรับค้นหาห้องที่ active ล่าสุด
                if (!$this->indexExists('game_rooms', 'game_rooms_status_created_idx')) {
                    $table->index(['status', 'created_at'], 'game_rooms_status_created_idx');
                }
            });
        }

        // เพิ่ม indexes สำหรับตาราง game_room_players
        if (Schema::hasTable('game_room_players')) {
            Schema::table('game_room_players', function (Blueprint $table) {
                // Index สำหรับค้นหาผู้เล่นในห้อง
                if (!$this->indexExists('game_room_players', 'grp_room_status_idx')) {
                    $table->index(['room_id', 'status'], 'grp_room_status_idx');
                }

                // Index สำหรับค้นหาผู้เล่นที่ alive
                if (!$this->indexExists('game_room_players', 'grp_room_alive_idx')) {
                    $table->index(['room_id', 'is_alive'], 'grp_room_alive_idx');
                }

                // Index สำหรับ leaderboard (เรียงตามคะแนน)
                if (!$this->indexExists('game_room_players', 'grp_room_score_idx')) {
                    $table->index(['room_id', 'score'], 'grp_room_score_idx');
                }

                // Index สำหรับค้นหาผู้เล่นตาม user_id
                if (!$this->indexExists('game_room_players', 'grp_user_idx')) {
                    $table->index('user_id', 'grp_user_idx');
                }

                // Index สำหรับ cleanup ผู้เล่นที่ไม่ active
                if (!$this->indexExists('game_room_players', 'grp_status_updated_idx')) {
                    $table->index(['status', 'last_update'], 'grp_status_updated_idx');
                }
            });
        }

        // เพิ่ม indexes สำหรับตาราง game_room_items
        if (Schema::hasTable('game_room_items')) {
            Schema::table('game_room_items', function (Blueprint $table) {
                // Index สำหรับค้นหาไอเทมในห้อง
                if (!$this->indexExists('game_room_items', 'gri_room_type_idx')) {
                    $table->index(['room_id', 'item_type'], 'gri_room_type_idx');
                }

                // Index สำหรับค้นหาไอเทมที่ยังไม่ถูกเก็บ
                if (!$this->indexExists('game_room_items', 'gri_room_collected_idx')) {
                    $table->index(['room_id', 'is_collected'], 'gri_room_collected_idx');
                }

                // Index สำหรับ cleanup ไอเทมที่หมดอายุ
                if (!$this->indexExists('game_room_items', 'gri_expires_idx')) {
                    $table->index('expires_at', 'gri_expires_idx');
                }

                // Index สำหรับค้นหาไอเทมที่ผู้เล่นเก็บ
                if (!$this->indexExists('game_room_items', 'gri_collected_by_idx')) {
                    $table->index('collected_by', 'gri_collected_by_idx');
                }

                // Index สำหรับค้นหาอาหารที่ยังไม่ถูกเก็บในห้อง
                if (!$this->indexExists('game_room_items', 'gri_room_type_collected_idx')) {
                    $table->index(['room_id', 'item_type', 'is_collected'], 'gri_room_type_collected_idx');
                }
            });
        }

        // เพิ่ม indexes สำหรับตาราง game_leaderboards
        if (Schema::hasTable('game_leaderboards')) {
            Schema::table('game_leaderboards', function (Blueprint $table) {
                // Index สำหรับ leaderboard global (เรียงตามคะแนน)
                if (!$this->indexExists('game_leaderboards', 'gl_game_score_idx')) {
                    $table->index(['game_id', 'score'], 'gl_game_score_idx');
                }

                // Index สำหรับ leaderboard รายวัน
                if (!$this->indexExists('game_leaderboards', 'gl_game_period_score_idx')) {
                    $table->index(['game_id', 'period_type', 'score'], 'gl_game_period_score_idx');
                }

                // Index สำหรับค้นหาคะแนนของผู้เล่น
                if (!$this->indexExists('game_leaderboards', 'gl_user_game_idx')) {
                    $table->index(['user_id', 'game_id'], 'gl_user_game_idx');
                }

                // Index สำหรับ leaderboard ตาม timestamp
                if (!$this->indexExists('game_leaderboards', 'gl_game_created_idx')) {
                    $table->index(['game_id', 'created_at'], 'gl_game_created_idx');
                }
            });
        }
    }

    /**
     * ลบ indexes ที่เพิ่มเข้าไป
     *
     * @return void
     */
    public function down(): void
    {
        // ลบ indexes จากตาราง game_rooms
        if (Schema::hasTable('game_rooms')) {
            Schema::table('game_rooms', function (Blueprint $table) {
                $table->dropIndex('game_rooms_status_players_idx');
                $table->dropIndex('game_rooms_game_status_idx');
                $table->dropIndex('game_rooms_code_idx');
                $table->dropIndex('game_rooms_status_created_idx');
            });
        }

        // ลบ indexes จากตาราง game_room_players
        if (Schema::hasTable('game_room_players')) {
            Schema::table('game_room_players', function (Blueprint $table) {
                $table->dropIndex('grp_room_status_idx');
                $table->dropIndex('grp_room_alive_idx');
                $table->dropIndex('grp_room_score_idx');
                $table->dropIndex('grp_user_idx');
                $table->dropIndex('grp_status_updated_idx');
            });
        }

        // ลบ indexes จากตาราง game_room_items
        if (Schema::hasTable('game_room_items')) {
            Schema::table('game_room_items', function (Blueprint $table) {
                $table->dropIndex('gri_room_type_idx');
                $table->dropIndex('gri_room_collected_idx');
                $table->dropIndex('gri_expires_idx');
                $table->dropIndex('gri_collected_by_idx');
                $table->dropIndex('gri_room_type_collected_idx');
            });
        }

        // ลบ indexes จากตาราง game_leaderboards
        if (Schema::hasTable('game_leaderboards')) {
            Schema::table('game_leaderboards', function (Blueprint $table) {
                $table->dropIndex('gl_game_score_idx');
                $table->dropIndex('gl_game_period_score_idx');
                $table->dropIndex('gl_user_game_idx');
                $table->dropIndex('gl_game_created_idx');
            });
        }
    }

    /**
     * ตรวจสอบว่า index มีอยู่แล้วหรือไม่
     *
     * @param string $table ชื่อตาราง
     * @param string $index ชื่อ index
     * @return bool
     */
    protected function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();

        // ใช้ raw SQL query เพื่อตรวจสอบ index (Laravel 11 compatible)
        $result = $connection->select(
            "SELECT COUNT(*) as count FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND index_name = ?",
            [$databaseName, $table, $index]
        );

        return $result[0]->count > 0;
    }
};
