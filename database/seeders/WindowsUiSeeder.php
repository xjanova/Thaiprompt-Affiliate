<?php

namespace Database\Seeders;

use App\Models\WindowsUiSetting;
use Illuminate\Database\Seeder;

class WindowsUiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This seeder populates Windows UI settings with current menu structure
     * from millennium-start-menu.blade.php to ensure Windows UI management
     * reflects the actual menu configuration with working submenus.
     */
    public function run(): void
    {
        // Start Menu Items - Admin Menu Structure
        // Based on millennium-start-menu.blade.php lines 84-324
        $startMenuItems = [
            [
                'icon' => '📊',
                'label' => 'Dashboard',
                'url' => '/admin/dashboard',
                'has_submenu' => false,
                'submenu' => [],
                'order' => 0,
            ],
            [
                'icon' => '👥',
                'label' => 'ผู้ใช้งาน',
                'url' => '#',
                'has_submenu' => true,
                'submenu' => [
                    ['label' => 'รายชื่อผู้ใช้', 'url' => '/admin/users'],
                    ['label' => 'บทบาท (Roles)', 'url' => '/admin/roles'],
                ],
                'order' => 1,
            ],
            [
                'icon' => '🤖',
                'label' => 'AI Bots & ผู้ช่วย',
                'url' => '#',
                'has_submenu' => true,
                'submenu' => [
                    ['label' => 'AI Bots', 'url' => '/admin/ai-bots'],
                    ['label' => 'AI Assistants', 'url' => '/admin/ai-assistants'],
                    ['label' => 'AI Conversations', 'url' => '/admin/ai-conversations'],
                ],
                'order' => 2,
            ],
            [
                'icon' => '🏨',
                'label' => 'จัดการโรงแรม',
                'url' => '#',
                'has_submenu' => true,
                'submenu' => [
                    ['label' => 'โรงแรม', 'url' => '/admin/hotels'],
                    ['label' => 'ห้องพัก', 'url' => '/admin/rooms'],
                    ['label' => 'การจอง', 'url' => '/admin/bookings'],
                    ['label' => 'รีวิว', 'url' => '/admin/reviews'],
                ],
                'order' => 3,
            ],
            [
                'icon' => '🛒',
                'label' => 'อีคอมเมิร์ซ',
                'url' => '#',
                'has_submenu' => true,
                'submenu' => [
                    ['label' => 'สินค้า', 'url' => '/admin/products'],
                    ['label' => 'หมวดหมู่', 'url' => '/admin/categories'],
                    ['label' => 'คำสั่งซื้อ', 'url' => '/admin/orders'],
                    ['label' => 'แบรนด์', 'url' => '/admin/brands'],
                    ['label' => 'คูปอง', 'url' => '/admin/coupons'],
                ],
                'order' => 4,
            ],
            [
                'icon' => '📝',
                'label' => 'จัดการเนื้อหา',
                'url' => '#',
                'has_submenu' => true,
                'submenu' => [
                    ['label' => 'บทความ', 'url' => '/admin/posts'],
                    ['label' => 'หมวดหมู่บทความ', 'url' => '/admin/post-categories'],
                    ['label' => 'หน้าเพจ', 'url' => '/admin/pages'],
                    ['label' => 'สื่อ', 'url' => '/admin/media'],
                ],
                'order' => 5,
            ],
            [
                'icon' => '💼',
                'label' => 'Affiliate',
                'url' => '#',
                'has_submenu' => true,
                'submenu' => [
                    ['label' => 'Affiliates', 'url' => '/admin/affiliates'],
                    ['label' => 'คอมมิชชั่น', 'url' => '/admin/commissions'],
                    ['label' => 'การถอนเงิน', 'url' => '/admin/withdrawals'],
                    ['label' => 'โปรแกรม', 'url' => '/admin/affiliate-programs'],
                ],
                'order' => 6,
            ],
            [
                'icon' => '💰',
                'label' => 'การเงิน',
                'url' => '#',
                'has_submenu' => true,
                'submenu' => [
                    ['label' => 'ธุรกรรม', 'url' => '/admin/transactions'],
                    ['label' => 'การชำระเงิน', 'url' => '/admin/payments'],
                    ['label' => 'กระเป๋าเงิน', 'url' => '/admin/wallets'],
                    ['label' => 'ใบแจ้งหนี้', 'url' => '/admin/invoices'],
                ],
                'order' => 7,
            ],
            [
                'icon' => '📧',
                'label' => 'การสื่อสาร',
                'url' => '#',
                'has_submenu' => true,
                'submenu' => [
                    ['label' => 'อีเมล', 'url' => '/admin/emails'],
                    ['label' => 'การแจ้งเตือน', 'url' => '/admin/notifications'],
                    ['label' => 'SMS', 'url' => '/admin/sms'],
                    ['label' => 'แชท', 'url' => '/admin/chats'],
                ],
                'order' => 8,
            ],
            [
                'icon' => '📊',
                'label' => 'รายงาน',
                'url' => '#',
                'has_submenu' => true,
                'submenu' => [
                    ['label' => 'ภาพรวม', 'url' => '/admin/reports'],
                    ['label' => 'ยอดขาย', 'url' => '/admin/reports/sales'],
                    ['label' => 'คอมมิชชั่น', 'url' => '/admin/reports/commissions'],
                    ['label' => 'ผู้ใช้งาน', 'url' => '/admin/reports/users'],
                ],
                'order' => 9,
            ],
            [
                'icon' => '🎨',
                'label' => 'ธีม & UI',
                'url' => '#',
                'has_submenu' => true,
                'submenu' => [
                    ['label' => 'Windows UI', 'url' => '/admin/windows-ui'],
                    ['label' => 'Page Builder', 'url' => '/admin/page-builder'],
                    ['label' => 'เมนู', 'url' => '/admin/menus'],
                    ['label' => 'ธีม', 'url' => '/admin/themes'],
                ],
                'order' => 10,
            ],
            [
                'icon' => '⚙️',
                'label' => 'ตั้งค่าระบบ',
                'url' => '#',
                'has_submenu' => true,
                'submenu' => [
                    ['label' => 'ทั่วไป', 'url' => '/admin/settings'],
                    ['label' => 'การชำระเงิน', 'url' => '/admin/settings/payment'],
                    ['label' => 'อีเมล', 'url' => '/admin/settings/email'],
                    ['label' => 'SEO', 'url' => '/admin/settings/seo'],
                    ['label' => 'API', 'url' => '/admin/settings/api'],
                ],
                'order' => 11,
            ],
            [
                'icon' => '🎓',
                'label' => 'ศูนย์การเรียนรู้',
                'url' => '#',
                'has_submenu' => true,
                'submenu' => [
                    ['label' => 'บทเรียน', 'url' => '/admin/learning-center'],
                    ['label' => 'คู่มือ', 'url' => '/admin/guides'],
                    ['label' => 'วิกิ', 'url' => '/admin/wiki'],
                ],
                'order' => 12,
            ],
            [
                'icon' => '🔧',
                'label' => 'เครื่องมือ',
                'url' => '#',
                'has_submenu' => true,
                'submenu' => [
                    ['label' => 'ล้างแคช', 'url' => '/admin/tools/clear-cache'],
                    ['label' => 'สำรองข้อมูล', 'url' => '/admin/tools/backup'],
                    ['label' => 'บันทึก', 'url' => '/admin/tools/logs'],
                    ['label' => 'Database', 'url' => '/admin/tools/database'],
                ],
                'order' => 13,
            ],
        ];

        WindowsUiSetting::set('windows_start_menu_items_admin', $startMenuItems, 'json');

        // Start Menu Items - Seller Menu Structure
        $sellerMenuItems = [
            [
                'icon' => '📊',
                'label' => 'Dashboard',
                'url' => '/seller/dashboard',
                'has_submenu' => false,
                'submenu' => [],
                'order' => 0,
            ],
            [
                'icon' => '📦',
                'label' => 'สินค้า',
                'url' => '#',
                'has_submenu' => true,
                'submenu' => [
                    ['label' => 'สินค้าของฉัน', 'url' => '/seller/products'],
                    ['label' => 'เพิ่มสินค้า', 'url' => '/seller/products/create'],
                    ['label' => 'หมวดหมู่', 'url' => '/seller/categories'],
                    ['label' => 'คลังสินค้า', 'url' => '/seller/inventory'],
                ],
                'order' => 1,
            ],
            [
                'icon' => '🛒',
                'label' => 'คำสั่งซื้อ',
                'url' => '#',
                'has_submenu' => true,
                'submenu' => [
                    ['label' => 'คำสั่งซื้อทั้งหมด', 'url' => '/seller/orders'],
                    ['label' => 'รอดำเนินการ', 'url' => '/seller/orders?status=pending'],
                    ['label' => 'กำลังจัดส่ง', 'url' => '/seller/orders?status=shipping'],
                    ['label' => 'เสร็จสิ้น', 'url' => '/seller/orders?status=completed'],
                ],
                'order' => 2,
            ],
            [
                'icon' => '💰',
                'label' => 'กระเป๋าเงิน',
                'url' => '#',
                'has_submenu' => true,
                'submenu' => [
                    ['label' => 'ยอดเงิน', 'url' => '/seller/wallet'],
                    ['label' => 'รายรับ', 'url' => '/seller/income'],
                    ['label' => 'ถอนเงิน', 'url' => '/seller/withdrawals'],
                    ['label' => 'ธุรกรรม', 'url' => '/seller/transactions'],
                ],
                'order' => 3,
            ],
            [
                'icon' => '📈',
                'label' => 'รายงาน',
                'url' => '#',
                'has_submenu' => true,
                'submenu' => [
                    ['label' => 'ภาพรวม', 'url' => '/seller/reports'],
                    ['label' => 'ยอดขาย', 'url' => '/seller/reports/sales'],
                    ['label' => 'สินค้ายอดนิยม', 'url' => '/seller/reports/products'],
                    ['label' => 'ลูกค้า', 'url' => '/seller/reports/customers'],
                ],
                'order' => 4,
            ],
            [
                'icon' => '🎁',
                'label' => 'โปรโมชั่น',
                'url' => '#',
                'has_submenu' => true,
                'submenu' => [
                    ['label' => 'คูปอง', 'url' => '/seller/coupons'],
                    ['label' => 'ส่วนลด', 'url' => '/seller/discounts'],
                    ['label' => 'แคมเปญ', 'url' => '/seller/campaigns'],
                ],
                'order' => 5,
            ],
            [
                'icon' => '⭐',
                'label' => 'รีวิว',
                'url' => '/seller/reviews',
                'has_submenu' => false,
                'submenu' => [],
                'order' => 6,
            ],
            [
                'icon' => '📧',
                'label' => 'การสื่อสาร',
                'url' => '#',
                'has_submenu' => true,
                'submenu' => [
                    ['label' => 'ข้อความ', 'url' => '/seller/messages'],
                    ['label' => 'การแจ้งเตือน', 'url' => '/seller/notifications'],
                    ['label' => 'แชท', 'url' => '/seller/chat'],
                ],
                'order' => 7,
            ],
            [
                'icon' => '🏪',
                'label' => 'ร้านค้า',
                'url' => '#',
                'has_submenu' => true,
                'submenu' => [
                    ['label' => 'ข้อมูลร้าน', 'url' => '/seller/shop/profile'],
                    ['label' => 'การตั้งค่า', 'url' => '/seller/shop/settings'],
                    ['label' => 'การจัดส่ง', 'url' => '/seller/shop/shipping'],
                    ['label' => 'แพคเกจ', 'url' => '/seller/packages'],
                ],
                'order' => 8,
            ],
            [
                'icon' => '⚙️',
                'label' => 'ตั้งค่า',
                'url' => '#',
                'has_submenu' => true,
                'submenu' => [
                    ['label' => 'โปรไฟล์', 'url' => '/seller/profile'],
                    ['label' => 'ธนาคาร', 'url' => '/seller/settings/bank'],
                    ['label' => 'การชำระเงิน', 'url' => '/seller/settings/payment'],
                    ['label' => 'ความปลอดภัย', 'url' => '/seller/settings/security'],
                ],
                'order' => 9,
            ],
        ];

        WindowsUiSetting::set('windows_start_menu_items_seller', $sellerMenuItems, 'json');

        // Start Menu Items - User Menu Structure
        $userMenuItems = [
            [
                'icon' => '🏠',
                'label' => 'หน้าแรก',
                'url' => '/dashboard',
                'has_submenu' => false,
                'submenu' => [],
                'order' => 0,
            ],
            [
                'icon' => '💰',
                'label' => 'กระเป๋าเงิน',
                'url' => '#',
                'has_submenu' => true,
                'submenu' => [
                    ['label' => 'ยอดเงิน', 'url' => '/wallet'],
                    ['label' => 'เติมเงิน', 'url' => '/wallet/deposit'],
                    ['label' => 'ถอนเงิน', 'url' => '/wallet/withdraw'],
                    ['label' => 'ประวัติ', 'url' => '/wallet/history'],
                ],
                'order' => 1,
            ],
            [
                'icon' => '📈',
                'label' => 'การลงทุน',
                'url' => '#',
                'has_submenu' => true,
                'submenu' => [
                    ['label' => 'แพลนการลงทุน', 'url' => '/investments'],
                    ['label' => 'พอร์ตของฉัน', 'url' => '/investments/portfolio'],
                    ['label' => 'รายได้', 'url' => '/investments/earnings'],
                    ['label' => 'ประวัติ', 'url' => '/investments/history'],
                ],
                'order' => 2,
            ],
            [
                'icon' => '💼',
                'label' => 'Affiliate',
                'url' => '#',
                'has_submenu' => true,
                'submenu' => [
                    ['label' => 'แดชบอร์ด', 'url' => '/affiliate/dashboard'],
                    ['label' => 'ลิงค์ของฉัน', 'url' => '/affiliate/links'],
                    ['label' => 'คอมมิชชั่น', 'url' => '/affiliate/commissions'],
                    ['label' => 'ทีมงาน', 'url' => '/affiliate/team'],
                ],
                'order' => 3,
            ],
            [
                'icon' => '🛒',
                'label' => 'ตลาด',
                'url' => '#',
                'has_submenu' => true,
                'submenu' => [
                    ['label' => 'ช็อปปิ้ง', 'url' => '/marketplace'],
                    ['label' => 'คำสั่งซื้อ', 'url' => '/orders'],
                    ['label' => 'รายการโปรด', 'url' => '/wishlist'],
                    ['label' => 'ตะกร้า', 'url' => '/cart'],
                ],
                'order' => 4,
            ],
            [
                'icon' => '🔮',
                'label' => 'ดูดวง',
                'url' => '#',
                'has_submenu' => true,
                'submenu' => [
                    ['label' => 'ไพ่ทาโรต์', 'url' => '/tarot'],
                    ['label' => 'ดวงประจำวัน', 'url' => '/horoscope'],
                    ['label' => 'ประวัติการดูดวง', 'url' => '/tarot/history'],
                ],
                'order' => 5,
            ],
            [
                'icon' => '🎓',
                'label' => 'การเรียนรู้',
                'url' => '#',
                'has_submenu' => true,
                'submenu' => [
                    ['label' => 'คอร์สเรียน', 'url' => '/academy'],
                    ['label' => 'คอร์สของฉัน', 'url' => '/academy/my-courses'],
                    ['label' => 'วิกิ', 'url' => '/wiki'],
                    ['label' => 'คู่มือ', 'url' => '/guides'],
                ],
                'order' => 6,
            ],
            [
                'icon' => '🤖',
                'label' => 'AI ผู้ช่วย',
                'url' => '#',
                'has_submenu' => true,
                'submenu' => [
                    ['label' => 'แชทกับ AI', 'url' => '/ai/chat'],
                    ['label' => 'AI Bots', 'url' => '/ai/bots'],
                    ['label' => 'ประวัติการสนทนา', 'url' => '/ai/conversations'],
                ],
                'order' => 7,
            ],
            [
                'icon' => '🔔',
                'label' => 'การแจ้งเตือน',
                'url' => '/notifications',
                'has_submenu' => false,
                'submenu' => [],
                'order' => 8,
            ],
            [
                'icon' => '👤',
                'label' => 'โปรไฟล์',
                'url' => '#',
                'has_submenu' => true,
                'submenu' => [
                    ['label' => 'ข้อมูลส่วนตัว', 'url' => '/profile'],
                    ['label' => 'ตั้งค่า', 'url' => '/settings'],
                    ['label' => 'ความปลอดภัย', 'url' => '/security'],
                    ['label' => 'ออกจากระบบ', 'url' => '/logout'],
                ],
                'order' => 9,
            ],
        ];

        WindowsUiSetting::set('windows_start_menu_items_user', $userMenuItems, 'json');

        // Taskbar Apps - Common Quick Access Apps
        $taskbarApps = [
            [
                'icon' => '📊',
                'label' => 'Dashboard',
                'url' => '/admin/dashboard',
                'order' => 0,
            ],
            [
                'icon' => '👥',
                'label' => 'Users',
                'url' => '/admin/users',
                'order' => 1,
            ],
            [
                'icon' => '🛒',
                'label' => 'Orders',
                'url' => '/admin/orders',
                'order' => 2,
            ],
            [
                'icon' => '📦',
                'label' => 'Products',
                'url' => '/admin/products',
                'order' => 3,
            ],
            [
                'icon' => '💰',
                'label' => 'Transactions',
                'url' => '/admin/transactions',
                'order' => 4,
            ],
            [
                'icon' => '📈',
                'label' => 'Reports',
                'url' => '/admin/reports',
                'order' => 5,
            ],
            [
                'icon' => '⚙️',
                'label' => 'Settings',
                'url' => '/admin/settings',
                'order' => 6,
            ],
        ];

        WindowsUiSetting::set('windows_taskbar_apps', $taskbarApps, 'json');

        // System Tray Icons - System Status & Quick Actions
        $systemTrayIcons = [
            [
                'icon' => '🔔',
                'label' => 'Notifications',
                'url' => '/notifications',
                'requires_auth' => true,
                'requires_guest' => false,
                'order' => 0,
            ],
            [
                'icon' => '📧',
                'label' => 'Messages',
                'url' => '/messages',
                'requires_auth' => true,
                'requires_guest' => false,
                'order' => 1,
            ],
            [
                'icon' => '🌐',
                'label' => 'Language',
                'url' => '/settings/language',
                'requires_auth' => false,
                'requires_guest' => false,
                'order' => 2,
            ],
            [
                'icon' => '🌙',
                'label' => 'Dark Mode',
                'url' => '#',
                'requires_auth' => false,
                'requires_guest' => false,
                'order' => 3,
            ],
            [
                'icon' => '👤',
                'label' => 'Profile',
                'url' => '/profile',
                'requires_auth' => true,
                'requires_guest' => false,
                'order' => 4,
            ],
            [
                'icon' => '💰',
                'label' => 'Wallet',
                'url' => '/wallet',
                'requires_auth' => true,
                'requires_guest' => false,
                'order' => 5,
            ],
            [
                'icon' => '⚙️',
                'label' => 'Settings',
                'url' => '/settings',
                'requires_auth' => true,
                'requires_guest' => false,
                'order' => 6,
            ],
            [
                'icon' => '🚪',
                'label' => 'Login',
                'url' => '/login',
                'requires_auth' => false,
                'requires_guest' => true,
                'order' => 7,
            ],
            [
                'icon' => '📝',
                'label' => 'Register',
                'url' => '/register',
                'requires_auth' => false,
                'requires_guest' => true,
                'order' => 8,
            ],
        ];

        WindowsUiSetting::set('windows_system_tray_icons', $systemTrayIcons, 'json');

        // Additional Windows UI Settings
        WindowsUiSetting::set('windows_taskbar_position', 'top', 'string');
        WindowsUiSetting::set('windows_taskbar_height', 48, 'integer');
        WindowsUiSetting::set('windows_taskbar_blur', true, 'boolean');
        WindowsUiSetting::set('windows_taskbar_transparency', 95, 'integer');

        WindowsUiSetting::set('windows_start_button_text', 'START', 'string');
        WindowsUiSetting::set('windows_start_button_use_logo', false, 'boolean');
        WindowsUiSetting::set('windows_start_button_position', 'center', 'string');

        WindowsUiSetting::set('millennium_back_button_enabled', true, 'boolean');
        WindowsUiSetting::set('millennium_back_button_text', '← Back', 'string');
        WindowsUiSetting::set('millennium_center_section_enabled', true, 'boolean');
        WindowsUiSetting::set('millennium_center_section_text', 'Thai Prompt Affiliate', 'string');
        WindowsUiSetting::set('millennium_rgb_enabled', true, 'boolean');
        WindowsUiSetting::set('millennium_rgb_speed', 3, 'integer');

        // Millennium Start Menu Size & Position Settings
        WindowsUiSetting::set('millennium_menu_position', 'center', 'string');
        WindowsUiSetting::set('millennium_menu_width', '400', 'string');
        WindowsUiSetting::set('millennium_menu_width_unit', 'px', 'string');
        WindowsUiSetting::set('millennium_menu_max_height', '600', 'string');
        WindowsUiSetting::set('millennium_menu_max_height_unit', 'px', 'string');
        WindowsUiSetting::set('millennium_menu_rgb_enabled', true, 'boolean');

        // Responsive Taskbar Settings
        WindowsUiSetting::set('millennium_taskbar_collapse_enabled', true, 'boolean');
        WindowsUiSetting::set('millennium_taskbar_collapse_breakpoint', 768, 'integer');

        WindowsUiSetting::set('windows_rgb_enabled', true, 'boolean');
        WindowsUiSetting::set('windows_rgb_speed', 3, 'integer');
        WindowsUiSetting::set('windows_rgb_glow', true, 'boolean');
        WindowsUiSetting::set('windows_rgb_colors', ['#FF0080', '#00F0FF', '#7F00FF', '#FF3D00'], 'json');

        WindowsUiSetting::set('windows_theme_mode', 'auto', 'string');
        WindowsUiSetting::set('windows_accent_color', '#667eea', 'color');

        WindowsUiSetting::set('windows_spaceship_theme', true, 'boolean');
        WindowsUiSetting::set('windows_spaceship_stars', true, 'boolean');

        WindowsUiSetting::set('windows_license_text', 'Licensed to Thai Prompt', 'string');
        WindowsUiSetting::set('windows_copyright_text', '© 2025 TP-Affiliate Platform', 'string');

        WindowsUiSetting::set('content_width_mode', 'max', 'string');
        WindowsUiSetting::set('content_width_custom', 1400, 'integer');

        $this->command->info('✅ Windows UI settings seeded successfully!');
        $this->command->info('   - Start Menu (Admin): ' . count($startMenuItems) . ' items with submenus');
        $this->command->info('   - Start Menu (Seller): ' . count($sellerMenuItems) . ' items with submenus');
        $this->command->info('   - Start Menu (User): ' . count($userMenuItems) . ' items with submenus');
        $this->command->info('   - Taskbar Apps: ' . count($taskbarApps) . ' apps');
        $this->command->info('   - System Tray: ' . count($systemTrayIcons) . ' icons');
    }
}
