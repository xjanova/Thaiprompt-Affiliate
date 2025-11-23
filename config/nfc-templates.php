<?php

return [

    /*
    |--------------------------------------------------------------------------
    | NFC Card Templates
    |--------------------------------------------------------------------------
    |
    | Templates สำเร็จรูปสำหรับเขียนข้อมูลลงบัตร NFC
    | แต่ละ template จะมี NDEF records ที่กำหนดไว้ล่วงหน้า
    |
    */

    'templates' => [

        /**
         * บัตรสมาชิก (Member Card)
         *
         * สำหรับ: บัตรสมาชิกระบบ Affiliate
         * เขียนลงบัตร: ข้อมูลสมาชิก, URL ตรวจสอบ, Anti-counterfeit code
         */
        'member_card' => [
            'name' => 'บัตรสมาชิก',
            'name_en' => 'Member Card',
            'icon' => 'fa-id-card',
            'color' => 'blue',
            'description' => 'บัตรสมาชิกระบบ Affiliate พร้อมรหัสป้องกันปลอม',
            'fields' => [
                'card_number' => ['label' => 'หมายเลขบัตร', 'type' => 'text', 'required' => true],
                'member_name' => ['label' => 'ชื่อสมาชิก', 'type' => 'text', 'required' => true],
                'member_id' => ['label' => 'รหัสสมาชิก', 'type' => 'text', 'required' => true],
                'rank' => ['label' => 'ยศ/ระดับ', 'type' => 'text', 'required' => false],
                'expiry_date' => ['label' => 'วันหมดอายุ', 'type' => 'date', 'required' => false],
            ],
            'records_builder' => function ($data) {
                return [
                    ['type' => 'text', 'data' => "MEMBER:{$data['member_id']}"],
                    ['type' => 'text', 'data' => "NAME:{$data['member_name']}"],
                    ['type' => 'text', 'data' => "CARD:{$data['card_number']}"],
                    ['type' => 'text', 'data' => "RANK:" . ($data['rank'] ?? 'Member')],
                    ['type' => 'text', 'data' => "EXPIRE:" . ($data['expiry_date'] ?? 'N/A')],
                    ['type' => 'url', 'data' => url("/nfc/verify/{$data['card_number']}")],
                ];
            },
        ],

        /**
         * นามบัตร (Business Card)
         *
         * สำหรับ: นามบัตรดิจิทัล
         * เขียนลงบัตร: ชื่อ, ตำแหน่ง, เบอร์โทร, อีเมล, Website, vCard
         */
        'business_card' => [
            'name' => 'นามบัตร',
            'name_en' => 'Business Card',
            'icon' => 'fa-address-card',
            'color' => 'purple',
            'description' => 'นามบัตรดิจิทัล พร้อมข้อมูลติดต่อและ vCard',
            'fields' => [
                'name' => ['label' => 'ชื่อ-นามสกุล', 'type' => 'text', 'required' => true],
                'position' => ['label' => 'ตำแหน่ง', 'type' => 'text', 'required' => false],
                'company' => ['label' => 'บริษัท', 'type' => 'text', 'required' => false],
                'phone' => ['label' => 'เบอร์โทรศัพท์', 'type' => 'tel', 'required' => true],
                'email' => ['label' => 'อีเมล', 'type' => 'email', 'required' => true],
                'website' => ['label' => 'เว็บไซต์', 'type' => 'url', 'required' => false],
                'address' => ['label' => 'ที่อยู่', 'type' => 'textarea', 'required' => false],
            ],
            'records_builder' => function ($data) {
                $records = [
                    ['type' => 'text', 'data' => "NAME:{$data['name']}"],
                    ['type' => 'text', 'data' => "TEL:{$data['phone']}"],
                    ['type' => 'text', 'data' => "EMAIL:{$data['email']}"],
                ];

                if (!empty($data['position'])) {
                    $records[] = ['type' => 'text', 'data' => "POSITION:{$data['position']}"];
                }

                if (!empty($data['company'])) {
                    $records[] = ['type' => 'text', 'data' => "COMPANY:{$data['company']}"];
                }

                if (!empty($data['website'])) {
                    $records[] = ['type' => 'url', 'data' => $data['website']];
                }

                // vCard
                $vcard = "BEGIN:VCARD\nVERSION:3.0\n";
                $vcard .= "FN:{$data['name']}\n";
                if (!empty($data['company'])) {
                    $vcard .= "ORG:{$data['company']}\n";
                }
                if (!empty($data['position'])) {
                    $vcard .= "TITLE:{$data['position']}\n";
                }
                $vcard .= "TEL:{$data['phone']}\n";
                $vcard .= "EMAIL:{$data['email']}\n";
                if (!empty($data['website'])) {
                    $vcard .= "URL:{$data['website']}\n";
                }
                if (!empty($data['address'])) {
                    $vcard .= "ADR:{$data['address']}\n";
                }
                $vcard .= "END:VCARD";

                $records[] = ['type' => 'text', 'data' => $vcard, 'mediaType' => 'text/vcard'];

                return $records;
            },
        ],

        /**
         * ข้อมูลสินค้า (Product Info)
         *
         * สำหรับ: ติดบนสินค้า เพื่อแสดงข้อมูลสินค้า
         * เขียนลงบัตร: ชื่อสินค้า, SKU, ราคา, URL สินค้า, รายละเอียด
         */
        'product_info' => [
            'name' => 'ข้อมูลสินค้า',
            'name_en' => 'Product Info',
            'icon' => 'fa-box',
            'color' => 'green',
            'description' => 'ติดบนสินค้าเพื่อแสดงข้อมูลรายละเอียด',
            'fields' => [
                'product_name' => ['label' => 'ชื่อสินค้า', 'type' => 'text', 'required' => true],
                'sku' => ['label' => 'รหัสสินค้า (SKU)', 'type' => 'text', 'required' => true],
                'price' => ['label' => 'ราคา', 'type' => 'number', 'required' => true],
                'category' => ['label' => 'หมวดหมู่', 'type' => 'text', 'required' => false],
                'description' => ['label' => 'รายละเอียด', 'type' => 'textarea', 'required' => false],
                'product_url' => ['label' => 'URL สินค้า', 'type' => 'url', 'required' => false],
            ],
            'records_builder' => function ($data) {
                $records = [
                    ['type' => 'text', 'data' => "PRODUCT:{$data['product_name']}"],
                    ['type' => 'text', 'data' => "SKU:{$data['sku']}"],
                    ['type' => 'text', 'data' => "PRICE:{$data['price']} THB"],
                ];

                if (!empty($data['category'])) {
                    $records[] = ['type' => 'text', 'data' => "CATEGORY:{$data['category']}"];
                }

                if (!empty($data['description'])) {
                    $records[] = ['type' => 'text', 'data' => "DESC:{$data['description']}"];
                }

                if (!empty($data['product_url'])) {
                    $records[] = ['type' => 'url', 'data' => $data['product_url']];
                }

                return $records;
            },
        ],

        /**
         * บัตรเข้า-ออก (Access Card)
         *
         * สำหรับ: ระบบเข้า-ออกอาคาร หรือ ระบบเข้าร่วมงาน
         * เขียนลงบัตร: รหัสพนักงาน, ชื่อ, แผนก, สิทธิ์เข้าถึง
         */
        'access_card' => [
            'name' => 'บัตรเข้า-ออก',
            'name_en' => 'Access Card',
            'icon' => 'fa-door-open',
            'color' => 'orange',
            'description' => 'บัตรสำหรับระบบเข้า-ออกอาคาร',
            'fields' => [
                'employee_id' => ['label' => 'รหัสพนักงาน', 'type' => 'text', 'required' => true],
                'employee_name' => ['label' => 'ชื่อพนักงาน', 'type' => 'text', 'required' => true],
                'department' => ['label' => 'แผนก', 'type' => 'text', 'required' => false],
                'access_level' => ['label' => 'ระดับสิทธิ์', 'type' => 'text', 'required' => false],
                'valid_from' => ['label' => 'วันที่เริ่มใช้งาน', 'type' => 'date', 'required' => false],
                'valid_until' => ['label' => 'วันหมดอายุ', 'type' => 'date', 'required' => false],
            ],
            'records_builder' => function ($data) {
                return [
                    ['type' => 'text', 'data' => "EMP:{$data['employee_id']}"],
                    ['type' => 'text', 'data' => "NAME:{$data['employee_name']}"],
                    ['type' => 'text', 'data' => "DEPT:" . ($data['department'] ?? 'N/A')],
                    ['type' => 'text', 'data' => "ACCESS:" . ($data['access_level'] ?? 'Standard')],
                    ['type' => 'text', 'data' => "VALID:" . ($data['valid_from'] ?? 'N/A') . " - " . ($data['valid_until'] ?? 'N/A')],
                ];
            },
        ],

        /**
         * WiFi Credentials
         *
         * สำหรับ: แชร์ WiFi password
         * เขียนลงบัตร: SSID, Password, Encryption type
         */
        'wifi_share' => [
            'name' => 'WiFi Share',
            'name_en' => 'WiFi Share',
            'icon' => 'fa-wifi',
            'color' => 'cyan',
            'description' => 'แชร์ WiFi password แบบง่ายๆ',
            'fields' => [
                'ssid' => ['label' => 'SSID (ชื่อ WiFi)', 'type' => 'text', 'required' => true],
                'password' => ['label' => 'Password', 'type' => 'password', 'required' => true],
                'encryption' => [
                    'label' => 'ประเภทการเข้ารหัส',
                    'type' => 'select',
                    'options' => ['WPA' => 'WPA/WPA2', 'WEP' => 'WEP', 'nopass' => 'ไม่มี Password'],
                    'required' => true,
                ],
                'hidden' => ['label' => 'Hidden Network', 'type' => 'checkbox', 'required' => false],
            ],
            'records_builder' => function ($data) {
                // WiFi NDEF format: WIFI:T:WPA;S:MyNetwork;P:MyPassword;H:false;;
                $hidden = isset($data['hidden']) && $data['hidden'] ? 'true' : 'false';
                $wifiString = "WIFI:T:{$data['encryption']};S:{$data['ssid']};P:{$data['password']};H:{$hidden};;";

                return [
                    ['type' => 'text', 'data' => $wifiString, 'mediaType' => 'application/vnd.wfa.wsc'],
                ];
            },
        ],

        /**
         * URL Shortcut
         *
         * สำหรับ: ลิงก์ไปยังเว็บไซต์
         * เขียนลงบัตร: URL
         */
        'url_shortcut' => [
            'name' => 'URL Shortcut',
            'name_en' => 'URL Shortcut',
            'icon' => 'fa-link',
            'color' => 'indigo',
            'description' => 'ลิงก์ไปยังเว็บไซต์ หรือ Social Media',
            'fields' => [
                'title' => ['label' => 'ชื่อลิงก์', 'type' => 'text', 'required' => false],
                'url' => ['label' => 'URL', 'type' => 'url', 'required' => true, 'placeholder' => 'https://example.com'],
            ],
            'records_builder' => function ($data) {
                $records = [];

                if (!empty($data['title'])) {
                    $records[] = ['type' => 'text', 'data' => $data['title']];
                }

                $records[] = ['type' => 'url', 'data' => $data['url']];

                return $records;
            },
        ],

        /**
         * Social Media Links
         *
         * สำหรับ: รวม Social Media Links
         * เขียนลงบัตร: Facebook, Line, Instagram, etc.
         */
        'social_links' => [
            'name' => 'Social Media',
            'name_en' => 'Social Media',
            'icon' => 'fa-share-alt',
            'color' => 'pink',
            'description' => 'รวม Social Media Links ของคุณ',
            'fields' => [
                'name' => ['label' => 'ชื่อ', 'type' => 'text', 'required' => true],
                'facebook' => ['label' => 'Facebook', 'type' => 'url', 'required' => false, 'placeholder' => 'https://facebook.com/...'],
                'line' => ['label' => 'Line ID', 'type' => 'text', 'required' => false, 'placeholder' => '@lineid'],
                'instagram' => ['label' => 'Instagram', 'type' => 'url', 'required' => false, 'placeholder' => 'https://instagram.com/...'],
                'twitter' => ['label' => 'Twitter/X', 'type' => 'url', 'required' => false, 'placeholder' => 'https://twitter.com/...'],
                'tiktok' => ['label' => 'TikTok', 'type' => 'url', 'required' => false, 'placeholder' => 'https://tiktok.com/@...'],
            ],
            'records_builder' => function ($data) {
                $records = [
                    ['type' => 'text', 'data' => "NAME:{$data['name']}"],
                ];

                if (!empty($data['facebook'])) {
                    $records[] = ['type' => 'url', 'data' => $data['facebook']];
                }

                if (!empty($data['line'])) {
                    $records[] = ['type' => 'text', 'data' => "LINE:{$data['line']}"];
                }

                if (!empty($data['instagram'])) {
                    $records[] = ['type' => 'url', 'data' => $data['instagram']];
                }

                if (!empty($data['twitter'])) {
                    $records[] = ['type' => 'url', 'data' => $data['twitter']];
                }

                if (!empty($data['tiktok'])) {
                    $records[] = ['type' => 'url', 'data' => $data['tiktok']];
                }

                return $records;
            },
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Supported Card Types
    |--------------------------------------------------------------------------
    |
    | ชนิดของบัตร NFC ที่รองรับ พร้อมข้อมูลความจุ
    |
    */

    'card_types' => [
        'NTAG213' => [
            'name' => 'NTAG213',
            'memory' => 144, // bytes
            'max_url_length' => 132,
            'description' => 'บัตร NFC ทั่วไป ความจุ 144 bytes',
        ],
        'NTAG215' => [
            'name' => 'NTAG215',
            'memory' => 504, // bytes
            'max_url_length' => 492,
            'description' => 'บัตร NFC ความจุสูง 504 bytes (ใช้ใน Amiibo)',
        ],
        'NTAG216' => [
            'name' => 'NTAG216',
            'memory' => 888, // bytes
            'max_url_length' => 876,
            'description' => 'บัตร NFC ความจุสูงสุด 888 bytes',
        ],
        'Mifare Classic 1K' => [
            'name' => 'Mifare Classic 1K',
            'memory' => 1024, // bytes
            'max_url_length' => 1000,
            'description' => 'Mifare Classic 1K (ต้องมี Key)',
        ],
        'Mifare Classic 4K' => [
            'name' => 'Mifare Classic 4K',
            'memory' => 4096, // bytes
            'max_url_length' => 4000,
            'description' => 'Mifare Classic 4K (ต้องมี Key)',
        ],
        'Mifare Ultralight' => [
            'name' => 'Mifare Ultralight',
            'memory' => 64, // bytes
            'max_url_length' => 50,
            'description' => 'Mifare Ultralight ความจุต่ำ 64 bytes',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | NDEF Record Types
    |--------------------------------------------------------------------------
    |
    | ประเภทของ NDEF records ที่รองรับ
    |
    */

    'ndef_types' => [
        'text' => [
            'name' => 'Text',
            'icon' => 'fa-font',
            'description' => 'ข้อความทั่วไป',
        ],
        'url' => [
            'name' => 'URL',
            'icon' => 'fa-link',
            'description' => 'ลิงก์เว็บไซต์',
        ],
        'phone' => [
            'name' => 'Phone',
            'icon' => 'fa-phone',
            'description' => 'เบอร์โทรศัพท์',
        ],
        'email' => [
            'name' => 'Email',
            'icon' => 'fa-envelope',
            'description' => 'อีเมล',
        ],
        'vcard' => [
            'name' => 'vCard',
            'icon' => 'fa-address-card',
            'description' => 'ข้อมูลติดต่อ',
        ],
        'wifi' => [
            'name' => 'WiFi',
            'icon' => 'fa-wifi',
            'description' => 'WiFi Credentials',
        ],
        'custom' => [
            'name' => 'Custom',
            'icon' => 'fa-code',
            'description' => 'กำหนดเอง',
        ],
    ],

];
