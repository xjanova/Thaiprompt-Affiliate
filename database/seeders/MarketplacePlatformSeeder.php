<?php

namespace Database\Seeders;

use App\Models\MarketplacePlatform;
use Illuminate\Database\Seeder;

class MarketplacePlatformSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * 📌 Smart Seeding Strategy:
     * Adds missing marketplace platforms only, preserves existing configurations.
     */
    public function run(): void
    {
        $existingPlatformsCount = MarketplacePlatform::count();

        if ($existingPlatformsCount > 0) {
            $this->updateMode();
        } else {
            $this->freshInstallMode();
        }
    }

    /**
     * Fresh install mode - seed all platforms
     */
    private function freshInstallMode(): void
    {
        $this->command->info('🌱 Fresh install: Seeding all marketplace platforms...');

        $platforms = $this->getAllPlatforms();

        foreach ($platforms as $platform) {
            MarketplacePlatform::create($platform);
        }

        $this->command->info('✅ Marketplace platforms seeded successfully: ' . count($platforms) . ' platforms');
    }

    /**
     * Update mode - add only missing platforms
     */
    private function updateMode(): void
    {
        $this->command->warn('⚠️  Existing marketplace platforms detected!');
        $this->command->info('   Running in UPDATE mode (adding missing platforms only)...');

        $platforms = $this->getAllPlatforms();
        $added = 0;
        $skipped = 0;

        foreach ($platforms as $platform) {
            if (!MarketplacePlatform::where('slug', $platform['slug'])->exists()) {
                MarketplacePlatform::create($platform);
                $this->command->info("   ➕ Added: {$platform['name']}");
                $added++;
            } else {
                $skipped++;
            }
        }

        if ($added > 0) {
            $this->command->info("✅ Added {$added} new marketplace platforms.");
        }

        if ($skipped > 0) {
            $this->command->info("   ⏭️  Skipped {$skipped} existing platforms (preserved).");
        }
    }

    /**
     * Get all default marketplace platforms
     */
    private function getAllPlatforms(): array
    {
        return [
            [
                'name' => 'Lazada',
                'slug' => 'lazada',
                'logo_url' => 'https://laz-img-cdn.alicdn.com/images/ims-web/TB1T7X3dAL0gK0jSZFAXXcA9pXa.png',
                'api_documentation_url' => 'https://open.lazada.com/doc/doc.htm',
                'is_active' => true,
                'requires_app_key' => true,
                'requires_app_secret' => true,
                'requires_access_token' => true,
                'requires_shop_id' => false,
                'additional_fields' => [
                    'seller_id' => [
                        'label' => 'Seller ID',
                        'type' => 'text',
                        'required' => false,
                    ],
                ],
                'default_commission_rate' => 5.00,
                'min_commission_rate' => 1.00,
                'max_commission_rate' => 15.00,
                'supports_product_sync' => true,
                'supports_order_sync' => true,
                'supports_real_time_webhook' => true,
            ],
            [
                'name' => 'TikTok Shop',
                'slug' => 'tiktok',
                'logo_url' => 'https://sf16-website-login.neutral.ttwstatic.com/obj/tiktok_web_login_static/tiktok/webapp/main/webapp-desktop/8152caf0c8e8bc67ae0d.png',
                'api_documentation_url' => 'https://partner.tiktokshop.com/doc',
                'is_active' => true,
                'requires_app_key' => true,
                'requires_app_secret' => true,
                'requires_access_token' => true,
                'requires_shop_id' => true,
                'additional_fields' => [
                    'shop_cipher' => [
                        'label' => 'Shop Cipher',
                        'type' => 'text',
                        'required' => false,
                    ],
                ],
                'default_commission_rate' => 4.00,
                'min_commission_rate' => 1.00,
                'max_commission_rate' => 12.00,
                'supports_product_sync' => true,
                'supports_order_sync' => true,
                'supports_real_time_webhook' => true,
            ],
            [
                'name' => 'Shopee',
                'slug' => 'shopee',
                'logo_url' => 'https://down-th.img.susercontent.com/file/br-11134207-7r98o-lp9gqhha1tff90',
                'api_documentation_url' => 'https://open.shopee.com/documents',
                'is_active' => true,
                'requires_app_key' => true,
                'requires_app_secret' => true,
                'requires_access_token' => true,
                'requires_shop_id' => true,
                'additional_fields' => [
                    'partner_id' => [
                        'label' => 'Partner ID',
                        'type' => 'text',
                        'required' => true,
                    ],
                    'partner_key' => [
                        'label' => 'Partner Key',
                        'type' => 'password',
                        'required' => true,
                    ],
                ],
                'default_commission_rate' => 3.50,
                'min_commission_rate' => 1.00,
                'max_commission_rate' => 10.00,
                'supports_product_sync' => true,
                'supports_order_sync' => true,
                'supports_real_time_webhook' => true,
            ],
        ];
    }
}
