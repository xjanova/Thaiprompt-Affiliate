<?php

namespace Database\Seeders;

use App\Models\LabelPaperSize;
use Illuminate\Database\Seeder;

/**
 * Seeder สำหรับสร้างขนาดกระดาษมาตรฐานสำหรับระบบฉลาก
 *
 * รองรับกระดาษมาตรฐาน, สติ๊กเกอร์, ใบเสร็จ, และเครื่องพิมพ์ความร้อนต่างๆ
 *
 * @author Claude AI
 */
class LabelPaperSizeSeeder extends Seeder
{
    /**
     * สร้างข้อมูลขนาดกระดาษมาตรฐาน
     */
    public function run(): void
    {
        $this->command->info('กำลัง seed ข้อมูลขนาดกระดาษสำหรับฉลาก...');

        $paperSizes = [
            // กระดาษมาตรฐาน
            [
                'name' => 'A4',
                'name_th' => 'กระดาษ A4',
                'description' => 'กระดาษ A4 มาตรฐาน สำหรับเครื่องพิมพ์ทั่วไป',
                'category' => 'standard',
                'width' => 210,
                'height' => 297,
                'labels_per_row' => 1,
                'labels_per_column' => 1,
                'margin_top' => 10,
                'margin_left' => 10,
                'margin_right' => 10,
                'margin_bottom' => 10,
                'supported_printers' => ['inkjet', 'laser'],
                'sort_order' => 1,
            ],
            [
                'name' => 'A5',
                'name_th' => 'กระดาษ A5',
                'description' => 'กระดาษ A5 ครึ่งหนึ่งของ A4',
                'category' => 'standard',
                'width' => 148,
                'height' => 210,
                'labels_per_row' => 1,
                'labels_per_column' => 1,
                'margin_top' => 5,
                'margin_left' => 5,
                'margin_right' => 5,
                'margin_bottom' => 5,
                'supported_printers' => ['inkjet', 'laser'],
                'sort_order' => 2,
            ],
            [
                'name' => 'A6',
                'name_th' => 'กระดาษ A6',
                'description' => 'กระดาษ A6 ขนาดโปสการ์ด',
                'category' => 'standard',
                'width' => 105,
                'height' => 148,
                'labels_per_row' => 1,
                'labels_per_column' => 1,
                'margin_top' => 3,
                'margin_left' => 3,
                'margin_right' => 3,
                'margin_bottom' => 3,
                'supported_printers' => ['inkjet', 'laser', 'thermal'],
                'sort_order' => 3,
            ],

            // สติ๊กเกอร์ขนาดมาตรฐาน
            [
                'name' => 'Label 50x30',
                'name_th' => 'สติ๊กเกอร์ 50x30 มม.',
                'description' => 'สติ๊กเกอร์ขนาดมาตรฐานสำหรับติดราคาสินค้า',
                'category' => 'label',
                'width' => 50,
                'height' => 30,
                'labels_per_row' => 1,
                'labels_per_column' => 1,
                'supported_printers' => ['thermal', 'Xprinter', 'TSC', 'Zebra'],
                'sort_order' => 10,
            ],
            [
                'name' => 'Label 40x30',
                'name_th' => 'สติ๊กเกอร์ 40x30 มม.',
                'description' => 'สติ๊กเกอร์ขนาดเล็กสำหรับติดราคา',
                'category' => 'label',
                'width' => 40,
                'height' => 30,
                'labels_per_row' => 1,
                'labels_per_column' => 1,
                'supported_printers' => ['thermal', 'Xprinter', 'TSC'],
                'sort_order' => 11,
            ],
            [
                'name' => 'Label 60x40',
                'name_th' => 'สติ๊กเกอร์ 60x40 มม.',
                'description' => 'สติ๊กเกอร์ขนาดกลางสำหรับติดผลิตภัณฑ์',
                'category' => 'label',
                'width' => 60,
                'height' => 40,
                'labels_per_row' => 1,
                'labels_per_column' => 1,
                'supported_printers' => ['thermal', 'Xprinter', 'TSC', 'Zebra'],
                'sort_order' => 12,
            ],
            [
                'name' => 'Label 70x50',
                'name_th' => 'สติ๊กเกอร์ 70x50 มม.',
                'description' => 'สติ๊กเกอร์ขนาดใหญ่สำหรับติดกล่องสินค้า',
                'category' => 'label',
                'width' => 70,
                'height' => 50,
                'labels_per_row' => 1,
                'labels_per_column' => 1,
                'supported_printers' => ['thermal', 'Xprinter', 'TSC', 'Zebra'],
                'sort_order' => 13,
            ],
            [
                'name' => 'Label 100x70',
                'name_th' => 'สติ๊กเกอร์ 100x70 มม.',
                'description' => 'สติ๊กเกอร์ขนาดใหญ่สำหรับจัดส่งพัสดุ',
                'category' => 'label',
                'width' => 100,
                'height' => 70,
                'labels_per_row' => 1,
                'labels_per_column' => 1,
                'supported_printers' => ['thermal', 'Zebra', 'TSC'],
                'sort_order' => 14,
            ],
            [
                'name' => 'Label 100x150',
                'name_th' => 'สติ๊กเกอร์ขนส่ง 100x150 มม.',
                'description' => 'สติ๊กเกอร์สำหรับติดพัสดุขนส่ง (4x6 นิ้ว)',
                'category' => 'label',
                'width' => 100,
                'height' => 150,
                'labels_per_row' => 1,
                'labels_per_column' => 1,
                'supported_printers' => ['thermal', 'Zebra', 'TSC', 'DYMO'],
                'sort_order' => 15,
            ],

            // A4 Labels (แผ่นสติ๊กเกอร์หลายชิ้น)
            [
                'name' => 'A4 Label 21',
                'name_th' => 'สติ๊กเกอร์ A4 (21 ดวง)',
                'description' => 'แผ่นสติ๊กเกอร์ขนาด A4 จำนวน 21 ดวง (3x7)',
                'category' => 'label',
                'width' => 210,
                'height' => 297,
                'labels_per_row' => 3,
                'labels_per_column' => 7,
                'label_width' => 63.5,
                'label_height' => 38.1,
                'gap_horizontal' => 2.5,
                'gap_vertical' => 0,
                'margin_top' => 10.7,
                'margin_left' => 7.2,
                'margin_right' => 7.2,
                'margin_bottom' => 10.7,
                'supported_printers' => ['inkjet', 'laser'],
                'sort_order' => 20,
            ],
            [
                'name' => 'A4 Label 24',
                'name_th' => 'สติ๊กเกอร์ A4 (24 ดวง)',
                'description' => 'แผ่นสติ๊กเกอร์ขนาด A4 จำนวน 24 ดวง (3x8)',
                'category' => 'label',
                'width' => 210,
                'height' => 297,
                'labels_per_row' => 3,
                'labels_per_column' => 8,
                'label_width' => 64,
                'label_height' => 33.9,
                'gap_horizontal' => 2.5,
                'gap_vertical' => 0,
                'margin_top' => 8.5,
                'margin_left' => 7.2,
                'margin_right' => 7.2,
                'margin_bottom' => 8.5,
                'supported_printers' => ['inkjet', 'laser'],
                'sort_order' => 21,
            ],
            [
                'name' => 'A4 Label 30',
                'name_th' => 'สติ๊กเกอร์ A4 (30 ดวง)',
                'description' => 'แผ่นสติ๊กเกอร์ขนาด A4 จำนวน 30 ดวง (3x10)',
                'category' => 'label',
                'width' => 210,
                'height' => 297,
                'labels_per_row' => 3,
                'labels_per_column' => 10,
                'label_width' => 70,
                'label_height' => 29.7,
                'gap_horizontal' => 0,
                'gap_vertical' => 0,
                'margin_top' => 0,
                'margin_left' => 0,
                'margin_right' => 0,
                'margin_bottom' => 0,
                'supported_printers' => ['inkjet', 'laser'],
                'sort_order' => 22,
            ],

            // กระดาษใบเสร็จ
            [
                'name' => 'Receipt 58mm',
                'name_th' => 'กระดาษใบเสร็จ 58 มม.',
                'description' => 'กระดาษความร้อนสำหรับเครื่องพิมพ์ใบเสร็จ 58mm',
                'category' => 'receipt',
                'width' => 58,
                'height' => 100,
                'labels_per_row' => 1,
                'labels_per_column' => 1,
                'supported_printers' => ['POS', 'Xprinter XP-Q200', 'POS-5890'],
                'sort_order' => 30,
            ],
            [
                'name' => 'Receipt 80mm',
                'name_th' => 'กระดาษใบเสร็จ 80 มม.',
                'description' => 'กระดาษความร้อนสำหรับเครื่องพิมพ์ใบเสร็จ 80mm',
                'category' => 'receipt',
                'width' => 80,
                'height' => 100,
                'labels_per_row' => 1,
                'labels_per_column' => 1,
                'supported_printers' => ['POS', 'Epson TM-T82III', 'Xprinter'],
                'sort_order' => 31,
            ],

            // DYMO
            [
                'name' => 'DYMO 30252',
                'name_th' => 'DYMO Address 30252',
                'description' => 'ฉลากที่อยู่ DYMO 28x89mm',
                'category' => 'sticker',
                'width' => 89,
                'height' => 28,
                'labels_per_row' => 1,
                'labels_per_column' => 1,
                'supported_printers' => ['DYMO LabelWriter'],
                'sort_order' => 40,
            ],
            [
                'name' => 'DYMO 30323',
                'name_th' => 'DYMO Shipping 30323',
                'description' => 'ฉลากจัดส่ง DYMO 54x101mm',
                'category' => 'sticker',
                'width' => 101,
                'height' => 54,
                'labels_per_row' => 1,
                'labels_per_column' => 1,
                'supported_printers' => ['DYMO LabelWriter 4XL'],
                'sort_order' => 41,
            ],

            // Brother QL
            [
                'name' => 'Brother DK-11201',
                'name_th' => 'Brother ฉลากที่อยู่',
                'description' => 'ฉลากที่อยู่ Brother 29x90mm',
                'category' => 'sticker',
                'width' => 90,
                'height' => 29,
                'labels_per_row' => 1,
                'labels_per_column' => 1,
                'supported_printers' => ['Brother QL-820NWB', 'Brother QL-1110NWB'],
                'sort_order' => 50,
            ],
            [
                'name' => 'Brother DK-11202',
                'name_th' => 'Brother ฉลากจัดส่ง',
                'description' => 'ฉลากจัดส่ง Brother 62x100mm',
                'category' => 'sticker',
                'width' => 100,
                'height' => 62,
                'labels_per_row' => 1,
                'labels_per_column' => 1,
                'supported_printers' => ['Brother QL-820NWB', 'Brother QL-1110NWB'],
                'sort_order' => 51,
            ],

            // Zebra
            [
                'name' => 'Zebra 4x6',
                'name_th' => 'Zebra ฉลากขนส่ง 4x6 นิ้ว',
                'description' => 'ฉลากขนส่ง Zebra 101.6x152.4mm (4x6 นิ้ว)',
                'category' => 'sticker',
                'width' => 152.4,
                'height' => 101.6,
                'labels_per_row' => 1,
                'labels_per_column' => 1,
                'supported_printers' => ['Zebra ZD420', 'Zebra ZD620', 'Zebra GK420t'],
                'sort_order' => 60,
            ],
            [
                'name' => 'Zebra 2x1',
                'name_th' => 'Zebra ฉลากสินค้า 2x1 นิ้ว',
                'description' => 'ฉลากสินค้า Zebra 50.8x25.4mm (2x1 นิ้ว)',
                'category' => 'sticker',
                'width' => 50.8,
                'height' => 25.4,
                'labels_per_row' => 1,
                'labels_per_column' => 1,
                'supported_printers' => ['Zebra ZD420', 'Zebra ZD620', 'Zebra GK420t'],
                'sort_order' => 61,
            ],

            // สติ๊กเกอร์ม้วน
            [
                'name' => 'Roll 35x25',
                'name_th' => 'สติ๊กเกอร์ม้วน 35x25 มม.',
                'description' => 'สติ๊กเกอร์ม้วนขนาดเล็ก สำหรับติดราคา',
                'category' => 'thermal',
                'width' => 35,
                'height' => 25,
                'labels_per_row' => 1,
                'labels_per_column' => 1,
                'supported_printers' => ['thermal', 'Xprinter XP-365B'],
                'sort_order' => 70,
            ],
            [
                'name' => 'Roll 40x20',
                'name_th' => 'สติ๊กเกอร์ม้วน 40x20 มม.',
                'description' => 'สติ๊กเกอร์ม้วนขนาดเล็กมาก',
                'category' => 'thermal',
                'width' => 40,
                'height' => 20,
                'labels_per_row' => 1,
                'labels_per_column' => 1,
                'supported_printers' => ['thermal', 'Xprinter'],
                'sort_order' => 71,
            ],
            [
                'name' => 'Roll 80x50',
                'name_th' => 'สติ๊กเกอร์ม้วน 80x50 มม.',
                'description' => 'สติ๊กเกอร์ม้วนขนาดใหญ่',
                'category' => 'thermal',
                'width' => 80,
                'height' => 50,
                'labels_per_row' => 1,
                'labels_per_column' => 1,
                'supported_printers' => ['thermal', 'TSC', 'Zebra'],
                'sort_order' => 72,
            ],
        ];

        foreach ($paperSizes as $size) {
            // ตรวจสอบว่ามีอยู่แล้วหรือไม่
            LabelPaperSize::firstOrCreate(
                ['name' => $size['name']],
                $size
            );
        }

        $this->command->info('✅ Seed ขนาดกระดาษสำเร็จ! จำนวน '.count($paperSizes).' รายการ');
    }
}
