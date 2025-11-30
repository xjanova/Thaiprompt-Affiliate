<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * เพิ่ม indexes สำหรับตารางเกม เพื่อเพิ่มประสิทธิภาพ
     */
    public function up(): void
    {
        // เพิ่ม indexes สำหรับตาราง game_rooms
        $this->safeAddIndexes('game_rooms', [
            [['status', 'current_players'], 'game_rooms_status_players_idx'],
            [['game_id', 'status'], 'game_rooms_game_status_idx'],
            ['room_code', 'game_rooms_code_idx'],
            [['status', 'created_at'], 'game_rooms_status_created_idx'],
        ]);

        // เพิ่ม indexes สำหรับตาราง game_room_players
        $this->safeAddIndexes('game_room_players', [
            [['room_id', 'status'], 'grp_room_status_idx'],
            [['room_id', 'is_alive'], 'grp_room_alive_idx'],
            [['room_id', 'score'], 'grp_room_score_idx'],
            ['user_id', 'grp_user_idx'],
            [['status', 'last_update'], 'grp_status_updated_idx'],
        ]);

        // เพิ่ม indexes สำหรับตาราง game_room_items
        $this->safeAddIndexes('game_room_items', [
            [['room_id', 'item_type'], 'gri_room_type_idx'],
            [['room_id', 'is_collected'], 'gri_room_collected_idx'],
            ['expires_at', 'gri_expires_idx'],
            ['collected_by', 'gri_collected_by_idx'],
            [['room_id', 'item_type', 'is_collected'], 'gri_room_type_collected_idx'],
        ]);

        // เพิ่ม indexes สำหรับตาราง game_leaderboards
        $this->safeAddIndexes('game_leaderboards', [
            [['game_id', 'score'], 'gl_game_score_idx'],
            [['game_id', 'period_type', 'score'], 'gl_game_period_score_idx'],
            [['user_id', 'game_id'], 'gl_user_game_idx'],
            [['game_id', 'created_at'], 'gl_game_created_idx'],
        ]);
    }

    /**
     * เพิ่ม indexes อย่างปลอดภัย - ตรวจสอบ column และ index ก่อนสร้าง
     */
    private function safeAddIndexes(string $table, array $indexes): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        foreach ($indexes as $indexDef) {
            $columns = $indexDef[0];
            $indexName = $indexDef[1];

            // แปลง column เดียวเป็น array
            if (!is_array($columns)) {
                $columns = [$columns];
            }

            // ตรวจสอบว่าทุก column มีอยู่
            $allColumnsExist = true;
            foreach ($columns as $column) {
                if (!Schema::hasColumn($table, $column)) {
                    $allColumnsExist = false;
                    break;
                }
            }

            if (!$allColumnsExist) {
                continue;
            }

            // ตรวจสอบว่า index มีอยู่แล้วหรือไม่
            if ($this->indexExists($table, $indexName)) {
                continue;
            }

            // สร้าง index
            try {
                Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName) {
                    $blueprint->index($columns, $indexName);
                });
            } catch (\Exception $e) {
                // ถ้าสร้างไม่ได้ก็ข้าม
            }
        }
    }

    /**
     * ลบ indexes ที่เพิ่มเข้าไป
     */
    public function down(): void
    {
        $this->safeDropIndexes('game_rooms', [
            'game_rooms_status_players_idx',
            'game_rooms_game_status_idx',
            'game_rooms_code_idx',
            'game_rooms_status_created_idx',
        ]);

        $this->safeDropIndexes('game_room_players', [
            'grp_room_status_idx',
            'grp_room_alive_idx',
            'grp_room_score_idx',
            'grp_user_idx',
            'grp_status_updated_idx',
        ]);

        $this->safeDropIndexes('game_room_items', [
            'gri_room_type_idx',
            'gri_room_collected_idx',
            'gri_expires_idx',
            'gri_collected_by_idx',
            'gri_room_type_collected_idx',
        ]);

        $this->safeDropIndexes('game_leaderboards', [
            'gl_game_score_idx',
            'gl_game_period_score_idx',
            'gl_user_game_idx',
            'gl_game_created_idx',
        ]);
    }

    /**
     * ลบ indexes อย่างปลอดภัย
     */
    private function safeDropIndexes(string $table, array $indexNames): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        foreach ($indexNames as $indexName) {
            if ($this->indexExists($table, $indexName)) {
                try {
                    Schema::table($table, function (Blueprint $blueprint) use ($indexName) {
                        $blueprint->dropIndex($indexName);
                    });
                } catch (\Exception $e) {
                    // ถ้าลบไม่ได้ก็ข้าม
                }
            }
        }
    }

    /**
     * ตรวจสอบว่า index มีอยู่แล้วหรือไม่
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();

        $result = DB::select("
            SELECT COUNT(*) as count
            FROM information_schema.statistics
            WHERE table_schema = ?
            AND table_name = ?
            AND index_name = ?
        ", [$databaseName, $table, $indexName]);

        return $result[0]->count > 0;
    }
};
