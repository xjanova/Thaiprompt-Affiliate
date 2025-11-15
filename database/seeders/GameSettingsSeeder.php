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
            // ==========================================
            // 🌐 Server Configuration
            // ==========================================
            [
                'key' => 'snake_io_server_ip',
                'value' => '123.253.62.251',
                'type' => 'string',
                'group' => 'snake_io_server',
                'description' => 'IP Address ของเซิฟเวอร์เกม Snake.io',
                'is_active' => true,
            ],
            [
                'key' => 'snake_io_server_port',
                'value' => '8080',
                'type' => 'integer',
                'group' => 'snake_io_server',
                'description' => 'Port สำหรับ API Server',
                'is_active' => true,
            ],
            [
                'key' => 'snake_io_ws_port',
                'value' => '6001',
                'type' => 'integer',
                'group' => 'snake_io_server',
                'description' => 'Port สำหรับ WebSocket (Laravel Reverb)',
                'is_active' => true,
            ],
            [
                'key' => 'snake_io_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'snake_io_server',
                'description' => 'เปิด/ปิดใช้งานเกม Snake.io',
                'is_active' => true,
            ],
            [
                'key' => 'snake_io_max_players_per_room',
                'value' => '30',
                'type' => 'integer',
                'group' => 'snake_io_server',
                'description' => 'จำนวนผู้เล่นสูงสุดต่อห้อง',
                'is_active' => true,
            ],

            // ==========================================
            // 🌍 Game World Settings
            // ==========================================
            [
                'key' => 'world_size',
                'value' => '200',
                'type' => 'integer',
                'group' => 'snake_io_world',
                'description' => 'ขนาดของโลกเกม (หน่วย)',
                'is_active' => true,
            ],
            [
                'key' => 'initial_snake_length',
                'value' => '5',
                'type' => 'integer',
                'group' => 'snake_io_world',
                'description' => 'ความยาวเริ่มต้นของงู (จำนวนปล้อง)',
                'is_active' => true,
            ],
            [
                'key' => 'segment_size',
                'value' => '0.5',
                'type' => 'float',
                'group' => 'snake_io_world',
                'description' => 'ขนาดของแต่ละปล้องงู',
                'is_active' => true,
            ],
            [
                'key' => 'collision_distance',
                'value' => '0.6',
                'type' => 'float',
                'group' => 'snake_io_world',
                'description' => 'ระยะตรวจจับการชน',
                'is_active' => true,
            ],

            // ==========================================
            // 🏃 Movement Settings
            // ==========================================
            [
                'key' => 'movement_speed',
                'value' => '0.15',
                'type' => 'float',
                'group' => 'snake_io_movement',
                'description' => 'ความเร็วการเคลื่อนที่ปกติ',
                'is_active' => true,
            ],
            [
                'key' => 'boost_speed',
                'value' => '0.3',
                'type' => 'float',
                'group' => 'snake_io_movement',
                'description' => 'ความเร็วเมื่อเร่งความเร็ว (Boost)',
                'is_active' => true,
            ],
            [
                'key' => 'turn_speed',
                'value' => '0.05',
                'type' => 'float',
                'group' => 'snake_io_movement',
                'description' => 'ความเร็วในการหักเลี้ยว',
                'is_active' => true,
            ],

            // ==========================================
            // 📷 Camera Settings
            // ==========================================
            [
                'key' => 'camera_initial_distance',
                'value' => '15',
                'type' => 'integer',
                'group' => 'snake_io_camera',
                'description' => 'ระยะกล้องเริ่มต้น (หน่วย)',
                'is_active' => true,
            ],
            [
                'key' => 'camera_zoomed_out_distance',
                'value' => '50',
                'type' => 'integer',
                'group' => 'snake_io_camera',
                'description' => 'ระยะกล้องเมื่อซูมออกสุด (เมื่อมี Zoom Powerup)',
                'is_active' => true,
            ],
            [
                'key' => 'camera_zoom_speed',
                'value' => '0.05',
                'type' => 'float',
                'group' => 'snake_io_camera',
                'description' => 'ความเร็วในการซูมกล้องเข้า-ออก',
                'is_active' => true,
            ],

            // ==========================================
            // 🏆 Scoring System
            // ==========================================
            [
                'key' => 'food_value',
                'value' => '1',
                'type' => 'integer',
                'group' => 'snake_io_scoring',
                'description' => 'คะแนนต่อการกินอาหาร 1 ก้อน',
                'is_active' => true,
            ],
            [
                'key' => 'points_per_growth',
                'value' => '10',
                'type' => 'integer',
                'group' => 'snake_io_scoring',
                'description' => 'คะแนนที่ต้องสะสมเพื่อเติบโต 1 ปล้อง',
                'is_active' => true,
            ],
            [
                'key' => 'score_multiplier_base',
                'value' => '1',
                'type' => 'integer',
                'group' => 'snake_io_scoring',
                'description' => 'ตัวคูณคะแนนพื้นฐาน (1x)',
                'is_active' => true,
            ],
            [
                'key' => 'save_score_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'snake_io_scoring',
                'description' => 'เปิด/ปิดการบันทึกคะแนน',
                'is_active' => true,
            ],

            // ==========================================
            // 🍎 Food Settings
            // ==========================================
            [
                'key' => 'food_count',
                'value' => '100',
                'type' => 'integer',
                'group' => 'snake_io_food',
                'description' => 'จำนวนอาหารในโลก',
                'is_active' => true,
            ],
            [
                'key' => 'food_spawn_rate',
                'value' => '0.5',
                'type' => 'float',
                'group' => 'snake_io_food',
                'description' => 'อัตราการเกิดอาหารต่อเฟรม (0-1)',
                'is_active' => true,
            ],
            [
                'key' => 'food_lifetime',
                'value' => '0',
                'type' => 'integer',
                'group' => 'snake_io_food',
                'description' => 'ระยะเวลาหายไปของอาหาร (มิลลิวินาที, 0 = ไม่หาย)',
                'is_active' => true,
            ],
            [
                'key' => 'food_value_min',
                'value' => '1',
                'type' => 'integer',
                'group' => 'snake_io_food',
                'description' => 'คะแนนต่ำสุดของอาหาร',
                'is_active' => true,
            ],
            [
                'key' => 'food_value_max',
                'value' => '1',
                'type' => 'integer',
                'group' => 'snake_io_food',
                'description' => 'คะแนนสูงสุดของอาหาร',
                'is_active' => true,
            ],

            // ==========================================
            // 🤖 Bot Settings
            // ==========================================
            [
                'key' => 'bot_count',
                'value' => '30',
                'type' => 'integer',
                'group' => 'snake_io_bots',
                'description' => 'จำนวนบอทในเกม',
                'is_active' => true,
            ],
            [
                'key' => 'bot_max_spawn_per_frame',
                'value' => '2',
                'type' => 'integer',
                'group' => 'snake_io_bots',
                'description' => 'จำนวนบอทสูงสุดที่สร้างต่อเฟรม',
                'is_active' => true,
            ],
            [
                'key' => 'bot_intelligence_level',
                'value' => '5',
                'type' => 'integer',
                'group' => 'snake_io_bots',
                'description' => 'ระดับความฉลาดของบอท (1-10)',
                'is_active' => true,
            ],

            // ==========================================
            // ✨ Powerups - General Settings
            // ==========================================
            [
                'key' => 'powerup_max_count',
                'value' => '4',
                'type' => 'integer',
                'group' => 'snake_io_powerups',
                'description' => 'จำนวน Powerup สูงสุดในโลก',
                'is_active' => true,
            ],
            [
                'key' => 'powerup_spawn_rate',
                'value' => '0.02',
                'type' => 'float',
                'group' => 'snake_io_powerups',
                'description' => 'โอกาสเกิด Powerup ต่อเฟรม (0-1)',
                'is_active' => true,
            ],
            [
                'key' => 'powerup_global_lifetime',
                'value' => '30000',
                'type' => 'integer',
                'group' => 'snake_io_powerups',
                'description' => 'ระยะเวลาหายไปของ Powerup ทั่วไป (มิลลิวินาที, 0 = ไม่หาย)',
                'is_active' => true,
            ],

            // ==========================================
            // 🧲 Powerup: Magnet
            // ==========================================
            [
                'key' => 'magnet_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'snake_io_powerup_magnet',
                'description' => 'เปิด/ปิด Magnet Powerup',
                'is_active' => true,
            ],
            [
                'key' => 'magnet_duration',
                'value' => '10000',
                'type' => 'integer',
                'group' => 'snake_io_powerup_magnet',
                'description' => 'ระยะเวลาใช้งาน Magnet (มิลลิวินาที)',
                'is_active' => true,
            ],
            [
                'key' => 'magnet_spawn_chance',
                'value' => '0.25',
                'type' => 'float',
                'group' => 'snake_io_powerup_magnet',
                'description' => 'โอกาสเกิด Magnet เมื่อ Spawn Powerup (0-1)',
                'is_active' => true,
            ],
            [
                'key' => 'magnet_lifetime',
                'value' => '30000',
                'type' => 'integer',
                'group' => 'snake_io_powerup_magnet',
                'description' => 'ระยะเวลาหายไปถ้าไม่มีคนเก็บ (มิลลิวินาที, 0 = ไม่หาย)',
                'is_active' => true,
            ],
            [
                'key' => 'magnet_range',
                'value' => '10',
                'type' => 'integer',
                'group' => 'snake_io_powerup_magnet',
                'description' => 'ระยะดูดของแม่เหล็ก (หน่วย)',
                'is_active' => true,
            ],

            // ==========================================
            // ⚡ Powerup: Speed Boost
            // ==========================================
            [
                'key' => 'speed_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'snake_io_powerup_speed',
                'description' => 'เปิด/ปิด Speed Boost Powerup',
                'is_active' => true,
            ],
            [
                'key' => 'speed_duration',
                'value' => '10000',
                'type' => 'integer',
                'group' => 'snake_io_powerup_speed',
                'description' => 'ระยะเวลาใช้งาน Speed Boost (มิลลิวินาที)',
                'is_active' => true,
            ],
            [
                'key' => 'speed_spawn_chance',
                'value' => '0.25',
                'type' => 'float',
                'group' => 'snake_io_powerup_speed',
                'description' => 'โอกาสเกิด Speed เมื่อ Spawn Powerup (0-1)',
                'is_active' => true,
            ],
            [
                'key' => 'speed_lifetime',
                'value' => '30000',
                'type' => 'integer',
                'group' => 'snake_io_powerup_speed',
                'description' => 'ระยะเวลาหายไปถ้าไม่มีคนเก็บ (มิลลิวินาที, 0 = ไม่หาย)',
                'is_active' => true,
            ],
            [
                'key' => 'speed_multiplier',
                'value' => '2',
                'type' => 'float',
                'group' => 'snake_io_powerup_speed',
                'description' => 'ตัวคูณความเร็ว (เช่น 2 = เร็ว 2 เท่า)',
                'is_active' => true,
            ],

            // ==========================================
            // ✖️ Powerup: Score Multiplier
            // ==========================================
            [
                'key' => 'multiplier_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'snake_io_powerup_multiplier',
                'description' => 'เปิด/ปิด Multiplier Powerup',
                'is_active' => true,
            ],
            [
                'key' => 'multiplier_duration',
                'value' => '10000',
                'type' => 'integer',
                'group' => 'snake_io_powerup_multiplier',
                'description' => 'ระยะเวลาใช้งาน Multiplier (มิลลิวินาที)',
                'is_active' => true,
            ],
            [
                'key' => 'multiplier_spawn_chance',
                'value' => '0.25',
                'type' => 'float',
                'group' => 'snake_io_powerup_multiplier',
                'description' => 'โอกาสเกิด Multiplier เมื่อ Spawn Powerup (0-1)',
                'is_active' => true,
            ],
            [
                'key' => 'multiplier_lifetime',
                'value' => '30000',
                'type' => 'integer',
                'group' => 'snake_io_powerup_multiplier',
                'description' => 'ระยะเวลาหายไปถ้าไม่มีคนเก็บ (มิลลิวินาที, 0 = ไม่หาย)',
                'is_active' => true,
            ],
            [
                'key' => 'multiplier_value',
                'value' => '2',
                'type' => 'integer',
                'group' => 'snake_io_powerup_multiplier',
                'description' => 'ตัวคูณคะแนน (เช่น 2 = ได้คะแนน 2 เท่า)',
                'is_active' => true,
            ],

            // ==========================================
            // 🔍 Powerup: Zoom Out
            // ==========================================
            [
                'key' => 'zoom_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'snake_io_powerup_zoom',
                'description' => 'เปิด/ปิด Zoom Powerup',
                'is_active' => true,
            ],
            [
                'key' => 'zoom_duration',
                'value' => '15000',
                'type' => 'integer',
                'group' => 'snake_io_powerup_zoom',
                'description' => 'ระยะเวลาใช้งาน Zoom (มิลลิวินาที)',
                'is_active' => true,
            ],
            [
                'key' => 'zoom_spawn_chance',
                'value' => '0.25',
                'type' => 'float',
                'group' => 'snake_io_powerup_zoom',
                'description' => 'โอกาสเกิด Zoom เมื่อ Spawn Powerup (0-1)',
                'is_active' => true,
            ],
            [
                'key' => 'zoom_lifetime',
                'value' => '30000',
                'type' => 'integer',
                'group' => 'snake_io_powerup_zoom',
                'description' => 'ระยะเวลาหายไปถ้าไม่มีคนเก็บ (มิลลิวินาที, 0 = ไม่หาย)',
                'is_active' => true,
            ],
            [
                'key' => 'zoom_distance',
                'value' => '50',
                'type' => 'integer',
                'group' => 'snake_io_powerup_zoom',
                'description' => 'ระยะกล้องเมื่อซูมออก (หน่วย)',
                'is_active' => true,
            ],

            // ==========================================
            // ⚙️ General Game Settings
            // ==========================================
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
