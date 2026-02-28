<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์ player_name เพื่อเก็บชื่อหนอนที่ผู้เล่นตั้ง (แทนที่จะใช้ชื่อบัญชี)
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('game_leaderboards', function (Blueprint $table) {
            if (!Schema::hasColumn('game_leaderboards', 'player_name')) {
                $table->string('player_name', 50)->nullable()->after('game_id')
                    ->comment('ชื่อที่ผู้เล่นตั้งในเกม (ชื่อหนอน)');
            }
        });
    }

    /**
     * ลบคอลัมน์ player_name
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('game_leaderboards', function (Blueprint $table) {
            if (Schema::hasColumn('game_leaderboards', 'player_name')) {
                $table->dropColumn('player_name');
            }
        });
    }
};
