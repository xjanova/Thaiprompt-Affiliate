<?php

namespace Database\Seeders;

use App\Models\LabelTemplate;
use Illuminate\Database\Seeder;

/**
 * PosLabelTemplateSeeder
 *
 * สร้าง Label Templates สำหรับ POS System
 * - Product Labels (ฉลากสินค้า)
 * - Shipping Labels (ใบปะสินค้า)
 *
 * @author Claude AI
 */
class PosLabelTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 กำลัง seed POS Label Templates...');

        // ตรวจสอบว่ามี templates อยู่แล้วหรือไม่
        if (LabelTemplate::where('is_pos_template', true)->exists()) {
            $this->command->info('⏭️  POS Label Templates มีอยู่แล้ว ข้าม...');

            return;
        }

        $templates = [
            // ===== PRODUCT LABELS =====
            [
                'name' => 'ฉลากสินค้า + Barcode (50x30mm)',
                'description' => 'ฉลากสินค้าพร้อม barcode สำหรับติดสินค้า',
                'category' => 'product',
                'paper_width' => 210,
                'paper_height' => 297,
                'paper_unit' => 'mm',
                'paper_type' => 'A4',
                'columns' => 5,
                'rows' => 6,
                'gap_horizontal' => 5,
                'gap_vertical' => 5,
                'margin_top' => 10,
                'margin_left' => 10,
                'margin_right' => 10,
                'margin_bottom' => 10,
                'elements' => [
                    'text' => [
                        [
                            'id' => 'product_name',
                            'content' => '{{product_name}}',
                            'x' => 2,
                            'y' => 2,
                            'width' => 35,
                            'height' => 8,
                            'fontSize' => 10,
                            'fontWeight' => 'bold',
                            'textAlign' => 'center',
                        ],
                        [
                            'id' => 'price',
                            'content' => '฿{{price}}',
                            'x' => 2,
                            'y' => 10,
                            'width' => 35,
                            'height' => 6,
                            'fontSize' => 14,
                            'fontWeight' => 'bold',
                            'textAlign' => 'center',
                            'color' => '#d9534f',
                        ],
                    ],
                    'barcode' => [
                        [
                            'id' => 'barcode_1',
                            'format' => 'CODE128',
                            'content' => '{{barcode}}',
                            'x' => 5,
                            'y' => 18,
                            'width' => 35,
                            'height' => 8,
                            'showText' => true,
                            'textSize' => 8,
                        ],
                    ],
                ],
                'is_public' => true,
                'is_system' => true,
                'is_active' => true,
                'is_pos_template' => true,
                'pos_category' => 'product_label',
                'printer_type' => 'any',
                'is_continuous_roll' => false,
            ],

            // Thermal Roll Product Label
            [
                'name' => 'ฉลากสินค้า Thermal Roll (50x30mm)',
                'description' => 'สำหรับเครื่องพิมพ์แบบม้วน (Thermal Roll)',
                'category' => 'product',
                'paper_width' => 50,
                'paper_height' => 30,
                'paper_unit' => 'mm',
                'paper_type' => 'thermal_roll_50x30',
                'columns' => 1,
                'rows' => 1,
                'gap_horizontal' => 0,
                'gap_vertical' => 0,
                'margin_top' => 2,
                'margin_left' => 2,
                'margin_right' => 2,
                'margin_bottom' => 2,
                'elements' => [
                    'text' => [
                        [
                            'id' => 'product_name',
                            'content' => '{{product_name}}',
                            'x' => 2,
                            'y' => 2,
                            'width' => 46,
                            'height' => 6,
                            'fontSize' => 9,
                            'fontWeight' => 'bold',
                            'textAlign' => 'center',
                        ],
                        [
                            'id' => 'price',
                            'content' => '฿{{price}}',
                            'x' => 2,
                            'y' => 8,
                            'width' => 46,
                            'height' => 5,
                            'fontSize' => 12,
                            'fontWeight' => 'bold',
                            'textAlign' => 'center',
                        ],
                    ],
                    'barcode' => [
                        [
                            'id' => 'barcode_1',
                            'format' => 'CODE128',
                            'content' => '{{barcode}}',
                            'x' => 5,
                            'y' => 15,
                            'width' => 40,
                            'height' => 10,
                            'showText' => true,
                            'textSize' => 7,
                        ],
                    ],
                ],
                'is_public' => true,
                'is_system' => true,
                'is_active' => true,
                'is_pos_template' => true,
                'pos_category' => 'product_label',
                'printer_type' => 'thermal_roll',
                'is_continuous_roll' => true,
            ],

            // ===== SHIPPING LABELS =====
            [
                'name' => 'ใบปะสินค้า (100x150mm)',
                'description' => 'ใบปะสินค้าพร้อมที่อยู่ลูกค้าและรายการสินค้า',
                'category' => 'shipping',
                'paper_width' => 100,
                'paper_height' => 150,
                'paper_unit' => 'mm',
                'paper_type' => 'label_100x150',
                'columns' => 1,
                'rows' => 1,
                'gap_horizontal' => 0,
                'gap_vertical' => 0,
                'margin_top' => 5,
                'margin_left' => 5,
                'margin_right' => 5,
                'margin_bottom' => 5,
                'elements' => [
                    'text' => [
                        [
                            'id' => 'shop_name',
                            'content' => 'ส่งจาก: {{shop_name}}',
                            'x' => 5,
                            'y' => 5,
                            'width' => 60,
                            'height' => 6,
                            'fontSize' => 10,
                            'fontWeight' => 'bold',
                        ],
                        [
                            'id' => 'customer_name',
                            'content' => 'ส่งถึง: {{customer_name}}',
                            'x' => 5,
                            'y' => 15,
                            'width' => 60,
                            'height' => 6,
                            'fontSize' => 11,
                            'fontWeight' => 'bold',
                        ],
                        [
                            'id' => 'customer_phone',
                            'content' => 'Tel: {{customer_phone}}',
                            'x' => 5,
                            'y' => 22,
                            'width' => 60,
                            'height' => 5,
                            'fontSize' => 9,
                        ],
                        [
                            'id' => 'customer_address',
                            'content' => '{{customer_address}}',
                            'x' => 5,
                            'y' => 28,
                            'width' => 60,
                            'height' => 15,
                            'fontSize' => 9,
                        ],
                    ],
                    'qrcode' => [
                        [
                            'id' => 'tracking_qr',
                            'content' => '{{tracking_url}}',
                            'x' => 70,
                            'y' => 10,
                            'width' => 25,
                            'height' => 25,
                        ],
                    ],
                    'barcode' => [
                        [
                            'id' => 'order_barcode',
                            'format' => 'CODE128',
                            'content' => '{{transaction_code}}',
                            'x' => 10,
                            'y' => 130,
                            'width' => 80,
                            'height' => 12,
                            'showText' => true,
                            'textSize' => 8,
                        ],
                    ],
                ],
                'is_public' => true,
                'is_system' => true,
                'is_active' => true,
                'is_pos_template' => true,
                'pos_category' => 'shipping_label',
                'printer_type' => 'thermal_label',
                'is_continuous_roll' => false,
            ],
        ];

        foreach ($templates as $templateData) {
            LabelTemplate::create($templateData);
            $this->command->info('  ✓ สร้าง: '.$templateData['name']);
        }

        $this->command->info('✅ Seed POS Label Templates สำเร็จ! ('.count($templates).' templates)');
    }
}
