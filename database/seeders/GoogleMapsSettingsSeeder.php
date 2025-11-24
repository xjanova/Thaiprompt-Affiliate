<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Google Maps Settings Seeder
 *
 * สร้างค่าเริ่มต้นสำหรับการตั้งค่า Google Maps API
 * ใช้กับโมดูล delivery และการคำนวณระยะทาง
 */
class GoogleMapsSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * สร้างการตั้งค่า Google Maps API ทั้งหมด
     * รองรับการรัน seeder ซ้ำได้ (idempotent)
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('🗺️  กำลัง seed ข้อมูล Google Maps Settings...');

        // กลุ่มการตั้งค่า: google_maps
        $settings = [
            // API Key
            [
                'key' => 'google_maps_api_key',
                'value' => env('GOOGLE_MAPS_API_KEY', ''),
                'type' => 'string',
                'group' => 'google_maps',
            ],

            // เปิด/ปิด ฟีเจอร์ต่างๆ
            [
                'key' => 'google_maps_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'google_maps',
            ],
            [
                'key' => 'google_maps_geocoding_enabled',
                'value' => env('GOOGLE_MAPS_GEOCODING_ENABLED', '1'),
                'type' => 'boolean',
                'group' => 'google_maps',
            ],
            [
                'key' => 'google_maps_directions_enabled',
                'value' => env('GOOGLE_MAPS_DIRECTIONS_ENABLED', '1'),
                'type' => 'boolean',
                'group' => 'google_maps',
            ],
            [
                'key' => 'google_maps_distance_matrix_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'google_maps',
            ],
            [
                'key' => 'google_maps_places_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'google_maps',
            ],

            // Cache Settings
            [
                'key' => 'google_maps_cache_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'google_maps',
            ],
            [
                'key' => 'google_maps_cache_ttl',
                'value' => env('GOOGLE_MAPS_CACHE_TTL', '86400'), // 24 ชั่วโมง
                'type' => 'integer',
                'group' => 'google_maps',
            ],

            // Geocoding Settings
            [
                'key' => 'google_maps_geocoding_language',
                'value' => 'th',
                'type' => 'string',
                'group' => 'google_maps',
            ],
            [
                'key' => 'google_maps_geocoding_region',
                'value' => 'TH',
                'type' => 'string',
                'group' => 'google_maps',
            ],

            // Directions Settings
            [
                'key' => 'google_maps_default_mode',
                'value' => 'driving', // driving, walking, bicycling, transit
                'type' => 'string',
                'group' => 'google_maps',
            ],
            [
                'key' => 'google_maps_avoid_tolls',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'google_maps',
            ],
            [
                'key' => 'google_maps_avoid_highways',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'google_maps',
            ],
            [
                'key' => 'google_maps_avoid_ferries',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'google_maps',
            ],

            // Distance Matrix Settings
            [
                'key' => 'google_maps_distance_unit',
                'value' => 'metric', // metric, imperial
                'type' => 'string',
                'group' => 'google_maps',
            ],

            // Places API Settings
            [
                'key' => 'google_maps_places_default_radius',
                'value' => '1000', // เมตร
                'type' => 'integer',
                'group' => 'google_maps',
            ],
            [
                'key' => 'google_maps_places_max_results',
                'value' => '20',
                'type' => 'integer',
                'group' => 'google_maps',
            ],

            // Map Display Settings (สำหรับ frontend)
            [
                'key' => 'google_maps_default_zoom',
                'value' => '12',
                'type' => 'integer',
                'group' => 'google_maps',
            ],
            [
                'key' => 'google_maps_default_center_lat',
                'value' => '13.7563', // กรุงเทพฯ
                'type' => 'string',
                'group' => 'google_maps',
            ],
            [
                'key' => 'google_maps_default_center_lng',
                'value' => '100.5018', // กรุงเทพฯ
                'type' => 'string',
                'group' => 'google_maps',
            ],
            [
                'key' => 'google_maps_map_type',
                'value' => 'roadmap', // roadmap, satellite, hybrid, terrain
                'type' => 'string',
                'group' => 'google_maps',
            ],

            // Delivery Module Settings
            [
                'key' => 'google_maps_delivery_max_distance',
                'value' => '50', // กิโลเมตร
                'type' => 'integer',
                'group' => 'google_maps',
            ],
            [
                'key' => 'google_maps_delivery_cost_per_km',
                'value' => '10', // บาท/กิโลเมตร
                'type' => 'float',
                'group' => 'google_maps',
            ],
            [
                'key' => 'google_maps_delivery_base_fee',
                'value' => '30', // บาท
                'type' => 'float',
                'group' => 'google_maps',
            ],

            // Rate Limiting
            [
                'key' => 'google_maps_rate_limit_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'google_maps',
            ],
            [
                'key' => 'google_maps_rate_limit_per_minute',
                'value' => '60',
                'type' => 'integer',
                'group' => 'google_maps',
            ],

            // Error Handling
            [
                'key' => 'google_maps_fallback_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'google_maps',
            ],
            [
                'key' => 'google_maps_fallback_calculation',
                'value' => 'haversine', // haversine (คำนวณระยะทางตรง)
                'type' => 'string',
                'group' => 'google_maps',
            ],
        ];

        // สร้างหรืออัพเดทการตั้งค่า
        foreach ($settings as $setting) {
            $existing = Setting::where('key', $setting['key'])->first();

            if ($existing) {
                $this->command->info("   ⏭️  ข้าม: {$setting['key']} (มีอยู่แล้ว)");
            } else {
                Setting::create($setting);
                $this->command->info("   ✅ สร้าง: {$setting['key']}");
            }
        }

        $this->command->info('✅ Seed ข้อมูล Google Maps Settings สำเร็จ!');
        $this->command->newLine();
        $this->command->warn('⚠️  คำเตือน: อย่าลืมตั้งค่า GOOGLE_MAPS_API_KEY ใน .env หรือในหน้าตั้งค่า');
        $this->command->info('   📖 วิธีการตั้งค่า: Admin > Settings > Google Maps');
    }
}
