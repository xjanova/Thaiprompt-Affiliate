<?php

namespace Database\Seeders;

use App\Models\TicketSlaPolicy;
use App\Models\TicketCategory;
use Illuminate\Database\Seeder;

class TicketSlaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $policies = [
            [
                'name' => 'Critical Priority - All Categories',
                'description' => 'SLA สำหรับ tickets ที่มีความสำคัญระดับวิกฤต',
                'first_response_time' => 15, // 15 minutes
                'resolution_time' => 240, // 4 hours
                'category_id' => null,
                'priority' => 'critical',
                'business_hours_only' => false,
                'business_hours' => null,
                'is_active' => true,
                'sort_order' => 100,
            ],
            [
                'name' => 'High Priority - All Categories',
                'description' => 'SLA สำหรับ tickets ที่มีความสำคัญระดับสูง',
                'first_response_time' => 30, // 30 minutes
                'resolution_time' => 480, // 8 hours
                'category_id' => null,
                'priority' => 'high',
                'business_hours_only' => true,
                'business_hours' => [
                    'start' => '09:00',
                    'end' => '18:00',
                    'days' => [1, 2, 3, 4, 5],
                ],
                'is_active' => true,
                'sort_order' => 90,
            ],
            [
                'name' => 'Medium Priority - All Categories',
                'description' => 'SLA สำหรับ tickets ที่มีความสำคัญระดับปานกลาง',
                'first_response_time' => 120, // 2 hours
                'resolution_time' => 1440, // 24 hours
                'category_id' => null,
                'priority' => 'medium',
                'business_hours_only' => true,
                'business_hours' => [
                    'start' => '09:00',
                    'end' => '18:00',
                    'days' => [1, 2, 3, 4, 5],
                ],
                'is_active' => true,
                'sort_order' => 80,
            ],
            [
                'name' => 'Low Priority - All Categories',
                'description' => 'SLA สำหรับ tickets ที่มีความสำคัญระดับต่ำ',
                'first_response_time' => 480, // 8 hours
                'resolution_time' => 2880, // 48 hours
                'category_id' => null,
                'priority' => 'low',
                'business_hours_only' => true,
                'business_hours' => [
                    'start' => '09:00',
                    'end' => '18:00',
                    'days' => [1, 2, 3, 4, 5],
                ],
                'is_active' => true,
                'sort_order' => 70,
            ],
        ];

        // Check if Technical Support category exists
        $techCategory = TicketCategory::where('name', 'Technical Support')->first();
        if ($techCategory) {
            $policies[] = [
                'name' => 'Technical Support - High Priority',
                'description' => 'SLA พิเศษสำหรับปัญหาทางเทคนิคที่มีความสำคัญสูง',
                'first_response_time' => 20, // 20 minutes
                'resolution_time' => 360, // 6 hours
                'category_id' => $techCategory->id,
                'priority' => 'high',
                'business_hours_only' => false,
                'business_hours' => null,
                'is_active' => true,
                'sort_order' => 95,
            ];
        }

        // Check if Finance category exists
        $financeCategory = TicketCategory::where('name', 'Finance & Payments')->first();
        if ($financeCategory) {
            $policies[] = [
                'name' => 'Finance & Payments - Critical',
                'description' => 'SLA พิเศษสำหรับปัญหาการเงินที่มีความสำคัญวิกฤต',
                'first_response_time' => 10, // 10 minutes
                'resolution_time' => 180, // 3 hours
                'category_id' => $financeCategory->id,
                'priority' => 'critical',
                'business_hours_only' => false,
                'business_hours' => null,
                'is_active' => true,
                'sort_order' => 98,
            ];
        }

        foreach ($policies as $policy) {
            TicketSlaPolicy::updateOrCreate(
                [
                    'name' => $policy['name'],
                ],
                $policy
            );
        }

        $this->command->info('✓ SLA policies seeded successfully!');
    }
}
