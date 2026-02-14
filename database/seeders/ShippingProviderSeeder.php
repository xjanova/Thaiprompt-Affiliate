<?php

namespace Database\Seeders;

use App\Models\ShippingProvider;
use Illuminate\Database\Seeder;

/**
 * Seeder สำหรับบริษัทขนส่งในประเทศไทย
 */
class ShippingProviderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚚 กำลัง seed ข้อมูลบริษัทขนส่ง...');

        $providers = [
            [
                'code' => 'THAIPOST',
                'name' => 'ไปรษณีย์ไทย',
                'name_en' => 'Thailand Post',
                'tracking_url' => 'https://track.thailandpost.co.th/?trackNumber={tracking_number}',
                'website' => 'https://www.thailandpost.co.th',
                'hotline' => '1545',
                'sort_order' => 1,
            ],
            [
                'code' => 'KERRY',
                'name' => 'Kerry Express',
                'name_en' => 'Kerry Express',
                'tracking_url' => 'https://th.kerryexpress.com/th/track/?track={tracking_number}',
                'website' => 'https://th.kerryexpress.com',
                'hotline' => '1217',
                'sort_order' => 2,
            ],
            [
                'code' => 'FLASH',
                'name' => 'Flash Express',
                'name_en' => 'Flash Express',
                'tracking_url' => 'https://www.flashexpress.co.th/tracking/?se={tracking_number}',
                'website' => 'https://www.flashexpress.co.th',
                'hotline' => '1284',
                'sort_order' => 3,
            ],
            [
                'code' => 'JNT',
                'name' => 'J&T Express',
                'name_en' => 'J&T Express',
                'tracking_url' => 'https://www.jtexpress.co.th/service/track?bills={tracking_number}',
                'website' => 'https://www.jtexpress.co.th',
                'hotline' => '02-026-2626',
                'sort_order' => 4,
            ],
            [
                'code' => 'NINJA',
                'name' => 'Ninja Van',
                'name_en' => 'Ninja Van',
                'tracking_url' => 'https://www.ninjavan.co/th-th/tracking?id={tracking_number}',
                'website' => 'https://www.ninjavan.co',
                'hotline' => '02-714-8200',
                'sort_order' => 5,
            ],
            [
                'code' => 'SCG',
                'name' => 'SCG Express',
                'name_en' => 'SCG Express',
                'tracking_url' => 'https://www.scgexpress.co.th/tracking/?refNo={tracking_number}',
                'website' => 'https://www.scgexpress.co.th',
                'hotline' => '02-586-0000',
                'sort_order' => 6,
            ],
            [
                'code' => 'BEST',
                'name' => 'Best Express',
                'name_en' => 'Best Express',
                'tracking_url' => 'https://www.best-inc.co.th/track?bills={tracking_number}',
                'website' => 'https://www.best-inc.co.th',
                'hotline' => '02-016-7888',
                'sort_order' => 7,
            ],
            [
                'code' => 'DHL',
                'name' => 'DHL Express',
                'name_en' => 'DHL Express',
                'tracking_url' => 'https://www.dhl.com/th-th/home/tracking.html?tracking-id={tracking_number}',
                'website' => 'https://www.dhl.com/th-th',
                'hotline' => '02-345-5000',
                'sort_order' => 8,
            ],
            [
                'code' => 'SHOPEE',
                'name' => 'Shopee Express',
                'name_en' => 'Shopee Express',
                'tracking_url' => 'https://spx.co.th/th/search?trackingNo={tracking_number}',
                'website' => 'https://spx.co.th',
                'hotline' => '02-017-8888',
                'sort_order' => 9,
            ],
            [
                'code' => 'LAZADA',
                'name' => 'Lazada Express',
                'name_en' => 'Lazada Express',
                'tracking_url' => 'https://www.lazada.co.th/track/?packageId={tracking_number}',
                'website' => 'https://www.lazada.co.th',
                'hotline' => '02-018-0000',
                'sort_order' => 10,
            ],
            [
                'code' => 'OTHER',
                'name' => 'อื่นๆ',
                'name_en' => 'Other',
                'tracking_url' => null,
                'website' => null,
                'hotline' => null,
                'sort_order' => 99,
            ],
        ];

        foreach ($providers as $provider) {
            ShippingProvider::updateOrCreate(
                ['code' => $provider['code']],
                array_merge($provider, ['is_active' => true])
            );
        }

        $this->command->info('✅ Seed ข้อมูลบริษัทขนส่งสำเร็จ! ('.count($providers).' รายการ)');
    }
}
