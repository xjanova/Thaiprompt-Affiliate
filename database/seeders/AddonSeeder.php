<?php

namespace Database\Seeders;

use App\Models\Addon;
use Illuminate\Database\Seeder;

class AddonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $addons = [
            [
                'name' => 'Hotel Management System',
                'slug' => 'hotel-management',
                'description' => 'Complete hotel, resort, and accommodation management system with booking, promotions, and revenue tracking.',
                'type' => 'hotel_management',
                'price' => 4999.00,
                'is_active' => true,
                'features' => [
                    'Unlimited hotels and properties',
                    'Room type management',
                    'Online booking system',
                    'Promotion and discount codes',
                    'Seasonal pricing rules',
                    'Guest management',
                    'Check-in/Check-out tracking',
                    'Revenue reports',
                    'Commission integration with MLM',
                ],
                'icon' => 'hotel',
                'sort_order' => 1,
            ],
            [
                'name' => 'Store Theme Customization',
                'slug' => 'store-theme',
                'description' => 'Customize your store appearance with themes, colors, fonts, and layouts to match your brand.',
                'type' => 'store_theme',
                'price' => 1999.00,
                'is_active' => true,
                'features' => [
                    'Pre-built theme templates',
                    'Color scheme customization',
                    'Typography settings',
                    'Layout options (grid, list, masonry)',
                    'Custom banners and hero sections',
                    'Logo and favicon upload',
                    'Social media integration',
                    'Custom CSS support',
                    'Responsive design',
                ],
                'icon' => 'palette',
                'sort_order' => 2,
            ],
            [
                'name' => 'Advanced Analytics',
                'slug' => 'advanced-analytics',
                'description' => 'Get detailed insights about your sales, customers, and performance.',
                'type' => 'analytics',
                'price' => 999.00,
                'is_active' => true,
                'features' => [
                    'Sales analytics dashboard',
                    'Customer behavior tracking',
                    'Product performance reports',
                    'Revenue forecasting',
                    'Export to Excel/PDF',
                ],
                'icon' => 'chart-line',
                'sort_order' => 3,
            ],
            [
                'name' => 'Email Marketing',
                'slug' => 'email-marketing',
                'description' => 'Send promotional emails and newsletters to your customers.',
                'type' => 'marketing',
                'price' => 1499.00,
                'is_active' => true,
                'features' => [
                    'Email campaign builder',
                    'Customer segmentation',
                    'Automated emails',
                    'Templates library',
                    'Performance tracking',
                ],
                'icon' => 'envelope',
                'sort_order' => 4,
            ],
        ];

        foreach ($addons as $addon) {
            Addon::create($addon);
        }
    }
}
