<?php

namespace Database\Seeders;

use App\Models\LabelTemplate;
use Illuminate\Database\Seeder;

/**
 * Seeder สำหรับสร้าง Template ฉลากเริ่มต้น
 *
 * สร้าง template พื้นฐานสำหรับร้านค้าใช้งานได้ทันที
 *
 * @author Claude AI
 */
class LabelTemplateSeeder extends Seeder
{
    /**
     * สร้างข้อมูล Template ฉลากเริ่มต้น
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('กำลัง seed ข้อมูล Template ฉลากเริ่มต้น...');

        $templates = [
            // Template สำหรับป้ายราคาสินค้า
            [
                'name' => 'ป้ายราคาสินค้า',
                'description' => 'ป้ายราคาสินค้าพื้นฐาน พร้อมบาร์โค้ด',
                'category' => 'price',
                'paper_width' => 50,
                'paper_height' => 30,
                'columns' => 1,
                'rows' => 1,
                'is_public' => true,
                'is_system' => true,
                'elements' => [
                    'text' => [
                        [
                            'id' => 'product_name',
                            'type' => 'text',
                            'content' => '{{product_name}}',
                            'x' => 2,
                            'y' => 2,
                            'width' => 46,
                            'height' => 8,
                            'fontSize' => 10,
                            'fontWeight' => 'bold',
                            'textAlign' => 'center',
                            'color' => '#000000',
                        ],
                        [
                            'id' => 'price',
                            'type' => 'text',
                            'content' => '{{price}} บาท',
                            'x' => 2,
                            'y' => 10,
                            'width' => 46,
                            'height' => 10,
                            'fontSize' => 14,
                            'fontWeight' => 'bold',
                            'textAlign' => 'center',
                            'color' => '#e53e3e',
                        ],
                    ],
                    'barcode' => [
                        [
                            'id' => 'barcode_1',
                            'type' => 'barcode',
                            'format' => 'CODE128',
                            'content' => '{{barcode}}',
                            'x' => 5,
                            'y' => 20,
                            'width' => 40,
                            'height' => 8,
                            'showText' => true,
                            'textSize' => 8,
                        ],
                    ],
                    'qrcode' => [],
                    'image' => [],
                    'shape' => [],
                ],
                'settings' => [
                    'background_color' => '#ffffff',
                    'border_enabled' => false,
                ],
            ],

            // Template สำหรับป้ายราคาพร้อม QR Code
            [
                'name' => 'ป้ายราคา + QR Code',
                'description' => 'ป้ายราคาสินค้าพร้อม QR Code สำหรับดูรายละเอียด',
                'category' => 'price',
                'paper_width' => 60,
                'paper_height' => 40,
                'columns' => 1,
                'rows' => 1,
                'is_public' => true,
                'is_system' => true,
                'elements' => [
                    'text' => [
                        [
                            'id' => 'product_name',
                            'type' => 'text',
                            'content' => '{{product_name}}',
                            'x' => 2,
                            'y' => 2,
                            'width' => 56,
                            'height' => 8,
                            'fontSize' => 10,
                            'fontWeight' => 'bold',
                            'textAlign' => 'left',
                            'color' => '#000000',
                        ],
                        [
                            'id' => 'price',
                            'type' => 'text',
                            'content' => '฿{{price}}',
                            'x' => 2,
                            'y' => 12,
                            'width' => 30,
                            'height' => 12,
                            'fontSize' => 16,
                            'fontWeight' => 'bold',
                            'textAlign' => 'left',
                            'color' => '#e53e3e',
                        ],
                        [
                            'id' => 'sku',
                            'type' => 'text',
                            'content' => 'SKU: {{sku}}',
                            'x' => 2,
                            'y' => 35,
                            'width' => 35,
                            'height' => 5,
                            'fontSize' => 7,
                            'textAlign' => 'left',
                            'color' => '#666666',
                        ],
                    ],
                    'barcode' => [],
                    'qrcode' => [
                        [
                            'id' => 'qrcode_1',
                            'type' => 'qrcode',
                            'content' => '{{product_url}}',
                            'x' => 38,
                            'y' => 8,
                            'width' => 20,
                            'height' => 20,
                            'errorCorrectionLevel' => 'M',
                        ],
                    ],
                    'image' => [],
                    'shape' => [],
                ],
                'settings' => [
                    'background_color' => '#ffffff',
                    'border_enabled' => true,
                    'border_color' => '#e5e5e5',
                    'border_width' => 0.5,
                ],
            ],

            // Template สำหรับฉลากจัดส่งพัสดุ
            [
                'name' => 'ฉลากจัดส่งพัสดุ',
                'description' => 'ฉลากสำหรับติดกล่องจัดส่ง พร้อมที่อยู่และบาร์โค้ด',
                'category' => 'shipping',
                'paper_width' => 100,
                'paper_height' => 150,
                'columns' => 1,
                'rows' => 1,
                'is_public' => true,
                'is_system' => true,
                'elements' => [
                    'text' => [
                        [
                            'id' => 'from_label',
                            'type' => 'text',
                            'content' => 'จาก:',
                            'x' => 5,
                            'y' => 5,
                            'width' => 20,
                            'height' => 6,
                            'fontSize' => 8,
                            'fontWeight' => 'bold',
                            'color' => '#666666',
                        ],
                        [
                            'id' => 'from_name',
                            'type' => 'text',
                            'content' => '{{sender_name}}',
                            'x' => 25,
                            'y' => 5,
                            'width' => 70,
                            'height' => 6,
                            'fontSize' => 9,
                            'color' => '#000000',
                        ],
                        [
                            'id' => 'from_address',
                            'type' => 'text',
                            'content' => '{{sender_address}}',
                            'x' => 25,
                            'y' => 11,
                            'width' => 70,
                            'height' => 15,
                            'fontSize' => 8,
                            'color' => '#333333',
                        ],
                        [
                            'id' => 'to_label',
                            'type' => 'text',
                            'content' => 'ถึง:',
                            'x' => 5,
                            'y' => 35,
                            'width' => 20,
                            'height' => 8,
                            'fontSize' => 10,
                            'fontWeight' => 'bold',
                            'color' => '#000000',
                        ],
                        [
                            'id' => 'to_name',
                            'type' => 'text',
                            'content' => '{{recipient_name}}',
                            'x' => 5,
                            'y' => 45,
                            'width' => 90,
                            'height' => 10,
                            'fontSize' => 14,
                            'fontWeight' => 'bold',
                            'color' => '#000000',
                        ],
                        [
                            'id' => 'to_address',
                            'type' => 'text',
                            'content' => '{{recipient_address}}',
                            'x' => 5,
                            'y' => 56,
                            'width' => 90,
                            'height' => 25,
                            'fontSize' => 10,
                            'color' => '#000000',
                        ],
                        [
                            'id' => 'to_phone',
                            'type' => 'text',
                            'content' => 'โทร: {{recipient_phone}}',
                            'x' => 5,
                            'y' => 82,
                            'width' => 90,
                            'height' => 8,
                            'fontSize' => 11,
                            'fontWeight' => 'bold',
                            'color' => '#000000',
                        ],
                        [
                            'id' => 'tracking',
                            'type' => 'text',
                            'content' => '{{tracking_number}}',
                            'x' => 5,
                            'y' => 140,
                            'width' => 90,
                            'height' => 8,
                            'fontSize' => 10,
                            'textAlign' => 'center',
                            'color' => '#000000',
                        ],
                    ],
                    'barcode' => [
                        [
                            'id' => 'tracking_barcode',
                            'type' => 'barcode',
                            'format' => 'CODE128',
                            'content' => '{{tracking_number}}',
                            'x' => 10,
                            'y' => 120,
                            'width' => 80,
                            'height' => 18,
                            'showText' => false,
                        ],
                    ],
                    'qrcode' => [],
                    'image' => [],
                    'shape' => [
                        [
                            'id' => 'divider',
                            'type' => 'line',
                            'x1' => 0,
                            'y1' => 30,
                            'x2' => 100,
                            'y2' => 30,
                            'strokeWidth' => 0.5,
                            'strokeColor' => '#cccccc',
                            'strokeStyle' => 'dashed',
                        ],
                    ],
                ],
                'settings' => [
                    'background_color' => '#ffffff',
                    'border_enabled' => true,
                    'border_color' => '#000000',
                    'border_width' => 1,
                ],
            ],

            // Template สำหรับฉลากสินค้า Food Passport
            [
                'name' => 'Food Passport Label',
                'description' => 'ฉลากสินค้าอาหาร พร้อม QR Code ตรวจสอบย้อนกลับ',
                'category' => 'food',
                'paper_width' => 70,
                'paper_height' => 50,
                'columns' => 1,
                'rows' => 1,
                'is_public' => true,
                'is_system' => true,
                'elements' => [
                    'text' => [
                        [
                            'id' => 'product_name',
                            'type' => 'text',
                            'content' => '{{product_name}}',
                            'x' => 2,
                            'y' => 2,
                            'width' => 45,
                            'height' => 8,
                            'fontSize' => 11,
                            'fontWeight' => 'bold',
                            'color' => '#000000',
                        ],
                        [
                            'id' => 'weight',
                            'type' => 'text',
                            'content' => 'น้ำหนัก: {{weight}}',
                            'x' => 2,
                            'y' => 11,
                            'width' => 45,
                            'height' => 5,
                            'fontSize' => 8,
                            'color' => '#333333',
                        ],
                        [
                            'id' => 'lot',
                            'type' => 'text',
                            'content' => 'Lot: {{lot_number}}',
                            'x' => 2,
                            'y' => 17,
                            'width' => 45,
                            'height' => 5,
                            'fontSize' => 8,
                            'color' => '#333333',
                        ],
                        [
                            'id' => 'mfg',
                            'type' => 'text',
                            'content' => 'ผลิต: {{mfg_date}}',
                            'x' => 2,
                            'y' => 23,
                            'width' => 25,
                            'height' => 5,
                            'fontSize' => 7,
                            'color' => '#333333',
                        ],
                        [
                            'id' => 'exp',
                            'type' => 'text',
                            'content' => 'หมดอายุ: {{exp_date}}',
                            'x' => 2,
                            'y' => 29,
                            'width' => 45,
                            'height' => 6,
                            'fontSize' => 8,
                            'fontWeight' => 'bold',
                            'color' => '#e53e3e',
                        ],
                        [
                            'id' => 'price',
                            'type' => 'text',
                            'content' => '฿{{price}}',
                            'x' => 2,
                            'y' => 38,
                            'width' => 45,
                            'height' => 10,
                            'fontSize' => 14,
                            'fontWeight' => 'bold',
                            'color' => '#000000',
                        ],
                        [
                            'id' => 'scan_label',
                            'type' => 'text',
                            'content' => 'สแกนตรวจสอบ',
                            'x' => 50,
                            'y' => 40,
                            'width' => 18,
                            'height' => 4,
                            'fontSize' => 5,
                            'textAlign' => 'center',
                            'color' => '#666666',
                        ],
                    ],
                    'barcode' => [],
                    'qrcode' => [
                        [
                            'id' => 'traceability_qr',
                            'type' => 'qrcode',
                            'content' => '{{traceability_url}}',
                            'x' => 50,
                            'y' => 5,
                            'width' => 18,
                            'height' => 18,
                            'errorCorrectionLevel' => 'M',
                        ],
                    ],
                    'image' => [],
                    'shape' => [],
                ],
                'settings' => [
                    'background_color' => '#ffffff',
                    'border_enabled' => true,
                    'border_color' => '#22c55e',
                    'border_width' => 1,
                ],
            ],

            // Template เปล่าสำหรับออกแบบเอง
            [
                'name' => 'ฉลากเปล่า 50x30',
                'description' => 'ฉลากเปล่าขนาด 50x30 มม. สำหรับออกแบบเอง',
                'category' => 'blank',
                'paper_width' => 50,
                'paper_height' => 30,
                'columns' => 1,
                'rows' => 1,
                'is_public' => true,
                'is_system' => true,
                'elements' => [
                    'text' => [],
                    'barcode' => [],
                    'qrcode' => [],
                    'image' => [],
                    'shape' => [],
                ],
                'settings' => [
                    'background_color' => '#ffffff',
                    'border_enabled' => false,
                ],
            ],
            [
                'name' => 'ฉลากเปล่า 60x40',
                'description' => 'ฉลากเปล่าขนาด 60x40 มม. สำหรับออกแบบเอง',
                'category' => 'blank',
                'paper_width' => 60,
                'paper_height' => 40,
                'columns' => 1,
                'rows' => 1,
                'is_public' => true,
                'is_system' => true,
                'elements' => [
                    'text' => [],
                    'barcode' => [],
                    'qrcode' => [],
                    'image' => [],
                    'shape' => [],
                ],
                'settings' => [
                    'background_color' => '#ffffff',
                    'border_enabled' => false,
                ],
            ],
            [
                'name' => 'ฉลากเปล่า 100x70',
                'description' => 'ฉลากเปล่าขนาด 100x70 มม. สำหรับออกแบบเอง',
                'category' => 'blank',
                'paper_width' => 100,
                'paper_height' => 70,
                'columns' => 1,
                'rows' => 1,
                'is_public' => true,
                'is_system' => true,
                'elements' => [
                    'text' => [],
                    'barcode' => [],
                    'qrcode' => [],
                    'image' => [],
                    'shape' => [],
                ],
                'settings' => [
                    'background_color' => '#ffffff',
                    'border_enabled' => false,
                ],
            ],
        ];

        foreach ($templates as $template) {
            // ตรวจสอบว่ามีอยู่แล้วหรือไม่
            LabelTemplate::firstOrCreate(
                [
                    'name' => $template['name'],
                    'is_system' => true,
                ],
                $template
            );
        }

        $this->command->info('✅ Seed Template ฉลากสำเร็จ! จำนวน ' . count($templates) . ' รายการ');
    }
}
