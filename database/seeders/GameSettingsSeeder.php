<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GameSettingsSeeder extends Seeder
{
    /**
     * สร้างข้อมูลเริ่มต้นสำหรับการตั้งค่าเกม
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('🎮 กำลัง seed ข้อมูล Game Settings...');

        $settings = [
            // Snake.io Server Configuration
            [
                'key' => 'snake_io_server_ip',
                'value' => '123.253.62.251',
                'type' => 'string',
                'group' => 'snake_io',
                'description' => 'IP Address ของเซิฟเวอร์เกม Snake.io',
                'is_active' => true,
            ],
            [
                'key' => 'snake_io_server_port',
                'value' => '8080',
                'type' => 'integer',
                'group' => 'snake_io',
                'description' => 'Port สำหรับ API Server ของ Snake.io',
                'is_active' => true,
            ],
            [
                'key' => 'snake_io_ws_port',
                'value' => '6001',
                'type' => 'integer',
                'group' => 'snake_io',
                'description' => 'Port สำหรับ WebSocket (Laravel Reverb)',
                'is_active' => true,
            ],
            [
                'key' => 'snake_io_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'snake_io',
                'description' => 'เปิด/ปิดใช้งานเกม Snake.io',
                'is_active' => true,
            ],
            [
                'key' => 'snake_io_max_players_per_room',
                'value' => '30',
                'type' => 'integer',
                'group' => 'snake_io',
                'description' => 'จำนวนผู้เล่นสูงสุดต่อห้อง',
                'is_active' => true,
            ],

            // General Game Settings
            [
                'key' => 'games_maintenance_mode',
                'value' => 'false',
                'type' => 'boolean',
                'group' => 'general',
                'description' => 'โหมดปิดปรับปรุงระบบเกม',
                'is_active' => true,
            ],
        ];

        foreach ($settings as $setting) {
            // ตรวจสอบว่ามีข้อมูลอยู่แล้วหรือไม่ (idempotent)
            $exists = DB::table('game_settings')
                ->where('key', $setting['key'])
                ->exists();

            if ($exists) {
                $this->command->info("   ⏭️  ข้าม: {$setting['key']} (มีอยู่แล้ว)");
                continue;
            }

            DB::table('game_settings')->insert([
                'key' => $setting['key'],
                'value' => $setting['value'],
                'type' => $setting['type'],
                'group' => $setting['group'],
                'description' => $setting['description'],
                'is_active' => $setting['is_active'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->command->info("   ✅ เพิ่ม: {$setting['key']}");
        }

        $this->command->info('✅ Seed ข้อมูล Game Settings สำเร็จ!');
    }
}
