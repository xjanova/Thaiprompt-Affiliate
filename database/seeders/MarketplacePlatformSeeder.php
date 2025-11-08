<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MarketplacePlatformSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $platforms = [
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

        foreach ($platforms as $platform) {
            \App\Models\MarketplacePlatform::updateOrCreate(
                ['slug' => $platform['slug']],
                $platform
            );
        }

        $this->command->info('Marketplace platforms seeded successfully!');
    }
}
