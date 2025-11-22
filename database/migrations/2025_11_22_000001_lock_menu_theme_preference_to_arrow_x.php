<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * ล็อค menu_theme_preference ให้ใช้แค่ arrow-x เท่านั้น
     *
     * Migration นี้จะ:
     * 1. อัพเดทค่า menu_theme_preference ทั้งหมดให้เป็น 'arrow-x'
     * 2. เปลี่ยน default value เป็น 'arrow-x'
     * 3. ไม่ใช้ ENUM เพราะ MySQL มีข้อจำกัด แต่จะใช้ validation ใน Model แทน
     *
     * @return void
     */
    public function up(): void
    {
        // อัพเดทค่าทั้งหมดที่ไม่ใช่ arrow-x ให้เป็น arrow-x
        DB::table('users')
            ->whereNotNull('menu_theme_preference')
            ->where('menu_theme_preference', '!=', 'arrow-x')
            ->update([
                'menu_theme_preference' => 'arrow-x',
                'updated_at' => now(),
            ]);

        // อัพเดทค่า NULL ให้เป็น arrow-x
        DB::table('users')
            ->whereNull('menu_theme_preference')
            ->update([
                'menu_theme_preference' => 'arrow-x',
                'updated_at' => now(),
            ]);

        // เปลี่ยน column เป็น NOT NULL และ default 'arrow-x'
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'menu_theme_preference')) {
                $table->string('menu_theme_preference', 50)
                    ->default('arrow-x')
                    ->nullable(false)
                    ->change();
            }
        });
    }

    /**
     * Reverse the migration (ไม่สามารถ reverse ได้เพราะข้อมูลเดิมถูกแทนที่แล้ว)
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'menu_theme_preference')) {
                $table->string('menu_theme_preference', 50)
                    ->default('arrow-x')
                    ->nullable()
                    ->change();
            }
        });
    }
};
