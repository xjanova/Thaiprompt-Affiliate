<?php

/**
 * Menu Theme Configuration
 *
 * การตั้งค่าสำหรับ theme ของเมนูต่างๆ
 * รองรับ Arrow X Theme และ themes อื่นๆ
 *
 * @version 3.0.0
 * @since 2025-11-15
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Default Theme
    |--------------------------------------------------------------------------
    |
    | Theme เริ่มต้นที่จะใช้สำหรับแสดงเมนู
    | Options: 'arrow-x', 'millennium', 'classic-x', 'windows'
    |
    */

    'default' => env('MENU_THEME', 'arrow-x'),

    /*
    |--------------------------------------------------------------------------
    | Allow User Theme Selection
    |--------------------------------------------------------------------------
    |
    | ⚠️ LOCKED: ระบบถูกล็อคให้ใช้ Arrow-X เท่านั้น
    | ห้ามเปลี่ยนเป็น true หรือเพิ่มธีมอื่น
    |
    */

    'user_selectable' => false,

    /*
    |--------------------------------------------------------------------------
    | Available Themes
    |--------------------------------------------------------------------------
    |
    | ⚠️ LOCKED TO ARROW-X ONLY
    | ระบบถูกล็อคให้ใช้ Arrow-X เท่านั้น
    | ห้ามเพิ่มธีมอื่นหรือเปลี่ยนแปลงการตั้งค่านี้
    |
    | DEPRECATED THEMES (ถูกลบออกแล้ว):
    | - millennium (Windows 11-inspired theme) - REMOVED
    | - classic-x (Traditional sidebar theme) - REMOVED
    | - windows (Windows 10-style theme) - REMOVED
    |
    */

    'themes' => [

        /*
        |----------------------------------------------------------------------
        | Arrow X Theme (LOCKED - ONLY AVAILABLE THEME)
        |----------------------------------------------------------------------
        |
        | Theme หลักและเพียงธีมเดียวของระบบ V3
        | - Modern, premium design
        | - Fully customizable
        | - RGB lighting support
        | - 3D effects & glassmorphism
        |
        | ⚠️ DO NOT REMOVE OR MODIFY THIS THEME
        | ⚠️ DO NOT ADD OTHER THEMES
        |
        */

        'arrow-x' => [
            'name' => 'Arrow X',
            'description' => 'Modern premium theme with full customization (LOCKED - ONLY THEME)',
            'component' => 'themes.arrow-x-menu',
            'preview_image' => '/images/themes/arrow-x-preview.jpg',
            'available_for' => ['admin', 'seller', 'user'],
            'settings' => [
                // ตั้งค่าเริ่มต้นสำหรับ Arrow X
                'use_gradient' => true,
                'gradient_from' => '#9333ea',
                'gradient_to' => '#db2777',
                'opacity' => 95,
                'blur_amount' => 24,
                'border_radius' => 16,
                'rgb_enabled' => true,
                'rgb_speed' => 5,
                'animation_style' => 'slide',
            ],
        ],

        // ⚠️ ⚠️ ⚠️ CRITICAL WARNING ⚠️ ⚠️ ⚠️
        // DO NOT ADD ANY OTHER THEMES HERE
        // ห้ามเพิ่มธีมอื่นที่นี่
        //
        // ระบบถูกออกแบบให้ใช้ Arrow-X เท่านั้น
        // การเพิ่มธีมอื่นจะทำให้:
        // 1. ผู้ใช้สับสน
        // 2. เพิ่มภาระในการดูแลรักษา
        // 3. ลด UX consistency
        // 4. ขัดต่อการออกแบบระบบ V3
        //
        // หากต้องการเพิ่มธีม ให้สร้าง theme ใหม่ภายใต้ Arrow-X framework
        // โดยการสร้าง preset ใหม่ใน theme_presets table แทน
    ],

    /*
    |--------------------------------------------------------------------------
    | Theme Settings Categories
    |--------------------------------------------------------------------------
    |
    | หมวดหมู่การตั้งค่าที่สามารถปรับแต่งได้
    | ใช้สำหรับ Arrow X Theme Builder
    |
    */

    'setting_categories' => [

        'layout' => [
            'label' => 'เค้าโครง',
            'icon' => '📐',
            'settings' => [
                'menu_width' => ['type' => 'number', 'label' => 'ความกว้างเมนู', 'unit' => 'px', 'min' => 200, 'max' => 800, 'default' => 400],
                'menu_max_height' => ['type' => 'number', 'label' => 'ความสูงสูงสุด', 'unit' => 'px', 'min' => 400, 'max' => 1000, 'default' => 600],
                'menu_position' => ['type' => 'select', 'label' => 'ตำแหน่งเมนู', 'options' => ['left' => 'ซ้าย', 'center' => 'กลาง', 'right' => 'ขวา'], 'default' => 'center'],
                'border_radius' => ['type' => 'number', 'label' => 'ความโค้งมุม', 'unit' => 'px', 'min' => 0, 'max' => 32, 'default' => 16],
            ],
        ],

        'colors' => [
            'label' => 'สีและ Gradient',
            'icon' => '🎨',
            'settings' => [
                'use_gradient' => ['type' => 'boolean', 'label' => 'ใช้ Gradient', 'default' => true],
                'gradient_from' => ['type' => 'color', 'label' => 'สี Gradient เริ่มต้น', 'default' => '#9333ea'],
                'gradient_to' => ['type' => 'color', 'label' => 'สี Gradient สิ้นสุด', 'default' => '#db2777'],
                'bg_color' => ['type' => 'color', 'label' => 'สีพื้นหลัง (ถ้าไม่ใช้ Gradient)', 'default' => '#9333ea'],
                'text_color' => ['type' => 'color', 'label' => 'สีตัวอักษร', 'default' => '#ffffff'],
                'border_color' => ['type' => 'color', 'label' => 'สีขอบ', 'default' => 'rgba(255, 255, 255, 0.2)'],
            ],
        ],

        'effects' => [
            'label' => 'เอฟเฟกต์',
            'icon' => '✨',
            'settings' => [
                'opacity' => ['type' => 'number', 'label' => 'ความทึบแสง', 'unit' => '%', 'min' => 0, 'max' => 100, 'default' => 95],
                'blur_amount' => ['type' => 'number', 'label' => 'ความเบลอ', 'unit' => 'px', 'min' => 0, 'max' => 50, 'default' => 24],
                'shadow_size' => ['type' => 'select', 'label' => 'ขนาดเงา', 'options' => ['none' => 'ไม่มี', 'sm' => 'เล็ก', 'md' => 'กลาง', 'lg' => 'ใหญ่', 'xl' => 'ใหญ่มาก', '2xl' => 'ใหญ่สุด'], 'default' => 'xl'],
            ],
        ],

        'rgb' => [
            'label' => 'RGB Lighting',
            'icon' => '🌈',
            'settings' => [
                'rgb_enabled' => ['type' => 'boolean', 'label' => 'เปิดใช้งาน RGB', 'default' => true],
                'rgb_speed' => ['type' => 'number', 'label' => 'ความเร็วแอนิเมชั่น', 'unit' => 's', 'min' => 1, 'max' => 20, 'default' => 5],
                'rgb_border_width' => ['type' => 'number', 'label' => 'ความหนาขอบ RGB', 'unit' => 'px', 'min' => 1, 'max' => 10, 'default' => 2],
                'rgb_glow_size' => ['type' => 'number', 'label' => 'ขนาด Glow', 'unit' => 'px', 'min' => 0, 'max' => 50, 'default' => 10],
                'menu_item_hover_rgb' => ['type' => 'boolean', 'label' => 'RGB ตอน Hover เมนู', 'default' => true],
            ],
        ],

        'typography' => [
            'label' => 'ตัวอักษร',
            'icon' => '🔤',
            'settings' => [
                'main_font_size' => ['type' => 'number', 'label' => 'ขนาดตัวอักษรหลัก', 'unit' => 'px', 'min' => 12, 'max' => 24, 'default' => 16],
                'main_font_weight' => ['type' => 'select', 'label' => 'น้ำหนักตัวอักษรหลัก', 'options' => ['normal' => 'ปกติ', 'medium' => 'กลาง', 'semibold' => 'กึ่งหนา', 'bold' => 'หนา'], 'default' => 'bold'],
                'sub_font_size' => ['type' => 'number', 'label' => 'ขนาดตัวอักษร Submenu', 'unit' => 'px', 'min' => 10, 'max' => 20, 'default' => 14],
                'sub_font_weight' => ['type' => 'select', 'label' => 'น้ำหนัก Submenu', 'options' => ['normal' => 'ปกติ', 'medium' => 'กลาง', 'semibold' => 'กึ่งหนา', 'bold' => 'หนา'], 'default' => 'medium'],
            ],
        ],

        'spacing' => [
            'label' => 'ระยะห่าง',
            'icon' => '📏',
            'settings' => [
                'menu_padding' => ['type' => 'number', 'label' => 'Padding เมนู', 'unit' => 'px', 'min' => 0, 'max' => 32, 'default' => 12],
                'menu_item_spacing' => ['type' => 'number', 'label' => 'ระยะห่างระหว่างเมนู', 'unit' => 'px', 'min' => 0, 'max' => 32, 'default' => 8],
                'main_padding_x' => ['type' => 'number', 'label' => 'Padding แนวนอนหลัก', 'unit' => 'px', 'min' => 0, 'max' => 32, 'default' => 16],
                'main_padding_y' => ['type' => 'number', 'label' => 'Padding แนวตั้งหลัก', 'unit' => 'px', 'min' => 0, 'max' => 32, 'default' => 12],
                'sub_padding_x' => ['type' => 'number', 'label' => 'Padding แนวนอน Submenu', 'unit' => 'px', 'min' => 0, 'max' => 32, 'default' => 12],
                'sub_padding_y' => ['type' => 'number', 'label' => 'Padding แนวตั้ง Submenu', 'unit' => 'px', 'min' => 0, 'max' => 32, 'default' => 8],
            ],
        ],

        'branding' => [
            'label' => 'โลโก้ & แบรนด์',
            'icon' => '🖼️',
            'settings' => [
                'show_logo' => ['type' => 'boolean', 'label' => 'แสดงโลโก้', 'default' => true],
                'logo_size' => ['type' => 'number', 'label' => 'ขนาดโลโก้', 'unit' => 'px', 'min' => 20, 'max' => 100, 'default' => 40],
                'show_app_name' => ['type' => 'boolean', 'label' => 'แสดงชื่อแอป', 'default' => true],
                'show_subtitle' => ['type' => 'boolean', 'label' => 'แสดง Subtitle', 'default' => true],
                'custom_app_name' => ['type' => 'text', 'label' => 'ชื่อแอปกำหนดเอง', 'default' => null],
                'custom_subtitle' => ['type' => 'text', 'label' => 'Subtitle กำหนดเอง', 'default' => null],
            ],
        ],

        'animation' => [
            'label' => 'แอนิเมชั่น',
            'icon' => '🎬',
            'settings' => [
                'animation_style' => ['type' => 'select', 'label' => 'รูปแบบแอนิเมชั่น', 'options' => ['fade' => 'Fade', 'slide' => 'Slide', 'scale' => 'Scale', 'none' => 'ไม่มี'], 'default' => 'slide'],
                'animation_duration' => ['type' => 'number', 'label' => 'ความเร็วแอนิเมชั่น', 'unit' => 'ms', 'min' => 100, 'max' => 1000, 'default' => 200],
            ],
        ],

        'search' => [
            'label' => 'ค้นหาเมนู',
            'icon' => '🔍',
            'settings' => [
                'search_enabled' => ['type' => 'boolean', 'label' => 'เปิดใช้งานการค้นหา', 'default' => true],
                'search_placeholder' => ['type' => 'text', 'label' => 'Placeholder ค้นหา', 'default' => 'ค้นหาเมนู...'],
            ],
        ],

        'footer' => [
            'label' => 'Footer',
            'icon' => '📌',
            'settings' => [
                'footer_enabled' => ['type' => 'boolean', 'label' => 'แสดง Footer', 'default' => true],
                'footer_text' => ['type' => 'text', 'label' => 'ข้อความ Footer', 'default' => 'Licensed © 2025 TP-Affiliate'],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Icon Sets
    |--------------------------------------------------------------------------
    |
    | ชุด icon ที่รองรับ
    |
    */

    'icon_sets' => [
        'emoji' => [
            'name' => 'Emoji',
            'description' => 'Unicode emojis (default)',
            'enabled' => true,
        ],
        'fontawesome' => [
            'name' => 'FontAwesome',
            'description' => 'FontAwesome icons',
            'enabled' => true,
            'version' => '6.5.0',
        ],
        'heroicons' => [
            'name' => 'Heroicons',
            'description' => 'Tailwind UI icons',
            'enabled' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Menu Behaviors
    |--------------------------------------------------------------------------
    |
    | พฤติกรรมของเมนู
    |
    */

    'behaviors' => [
        'close_on_click' => true,          // ปิดเมนูเมื่อคลิกรายการ
        'close_on_escape' => true,         // ปิดเมนูเมื่อกด ESC
        'close_on_outside_click' => true,  // ปิดเมนูเมื่อคลิกนอกเมนู
        'remember_submenu_state' => false, // จำสถานะการเปิด submenu
        'auto_close_siblings' => true,     // ปิด submenu พี่น้องอัตโนมัติเมื่อเปิดอันใหม่
        'search_realtime' => true,         // ค้นหาแบบ realtime
        'keyboard_navigation' => true,     // รองรับการนำทางด้วยแป้นพิมพ์
    ],

];
