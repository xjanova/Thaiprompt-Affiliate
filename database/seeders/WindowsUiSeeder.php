<?php

namespace Database\Seeders;

use App\Models\WindowsUiSetting;
use Illuminate\Database\Seeder;

class WindowsUiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Smart Seeding Strategy:
     * - Fresh Install: Seeds all default settings
     * - Update Mode: Adds only missing settings (preserves customizations)
     *
     * This follows the Smart Seeding Guidelines in .claude/seeder-guidelines.md
     */
    public function run(): void
    {
        // Check if core settings exist (indicates previous seeding)
        $coreSettingsExist = WindowsUiSetting::whereIn('key', [
            'windows_taskbar_position',
            'windows_taskbar_height',
            'windows_start_button_position',
        ])->count() > 0;

        if ($coreSettingsExist) {
            $this->updateMode();
        } else {
            $this->freshInstallMode();
        }
    }

    /**
     * Fresh Install Mode: Seed all default settings
     */
    private function freshInstallMode(): void
    {
        $this->command->info('🌱 Fresh install: Seeding all Windows UI settings...');
        // Start Menu Items - Admin Menu Structure
        // Extracted from millennium-start-menu.blade.php (complete menu structure)
        $adminMenuItems = [
            ['icon' => '📊', 'label' => 'แดชบอร์ด', 'route' => 'admin.dashboard', 'order' => 0],
            [
                'icon' => '👥',
                'label' => 'ผู้ใช้งาน',
                'route' => null,
                'order' => 1,
                'submenu' => [
                    ['label' => 'รายชื่อผู้ใช้', 'route' => 'admin.users.index'],
                    ['label' => 'บทบาท (Roles)', 'route' => 'admin.roles.index'],
                ]
            ],
            ['icon' => '🪪', 'label' => 'ยืนยันตัวตน KYC', 'route' => 'admin.kyc.index', 'order' => 2],
            ['icon' => '🎫', 'label' => 'Ticket Support', 'route' => 'admin.tickets.index', 'order' => 3],
            [
                'icon' => '🤖',
                'label' => 'AI Bots & ผู้ช่วย',
                'route' => null,
                'order' => 4,
                'submenu' => [
                    ['label' => 'จัดการ AI Bots', 'route' => 'admin.ai-bots.index'],
                    ['label' => 'AI Providers', 'route' => 'admin.ai-providers.index'],
                    ['label' => 'ติดตั้ง AI', 'route' => 'admin.ai-installation.index'],
                ]
            ],
            [
                'icon' => '🏨',
                'label' => 'จัดการโรงแรม',
                'route' => null,
                'order' => 5,
                'submenu' => [
                    ['label' => 'โรงแรมทั้งหมด', 'route' => 'admin.hotels.index'],
                    ['label' => 'การจองทั้งหมด', 'route' => 'admin.hotels.bookings.index'],
                    ['label' => 'สถิติการจอง', 'route' => 'admin.hotels.bookings.analytics'],
                    ['label' => 'จัดการรีวิว', 'route' => 'admin.hotels.reviews.index'],
                    ['label' => 'สิ่งอำนวยความสะดวก', 'route' => 'admin.hotels.facilities.index'],
                    ['label' => 'โปรโมชั่นพิเศษ', 'route' => 'admin.hotels.special-offers.index'],
                ]
            ],
            [
                'icon' => '🛒',
                'label' => 'อีคอมเมิร์ซ',
                'route' => null,
                'order' => 6,
                'submenu' => [
                    ['label' => 'แดชบอร์ด', 'route' => 'admin.ecommerce.dashboard'],
                    ['label' => 'สินค้าทั้งหมด', 'route' => 'admin.ecommerce.products.index'],
                    ['label' => 'คำสั่งซื้อ', 'route' => 'admin.ecommerce.orders.index'],
                    ['label' => 'หมวดหมู่', 'route' => 'admin.ecommerce.categories.index'],
                    ['label' => 'รีวิวสินค้า', 'route' => 'admin.ecommerce.reviews.index'],
                ]
            ],
            [
                'icon' => '🏪',
                'label' => 'ระบบ POS',
                'route' => null,
                'order' => 7,
                'submenu' => [
                    ['label' => 'แดชบอร์ด', 'route' => 'admin.pos.dashboard'],
                    ['label' => 'อุปกรณ์ POS', 'route' => 'admin.pos.devices.index'],
                    ['label' => 'ธุรกรรม', 'route' => 'admin.pos.transactions.index'],
                    ['label' => 'วิเคราะห์ข้อมูล', 'route' => 'admin.pos.analytics'],
                ]
            ],
            [
                'icon' => '💰',
                'label' => 'กระเป๋าเงิน THB',
                'route' => null,
                'order' => 8,
                'submenu' => [
                    ['label' => 'กระเป๋าเงินทั้งหมด', 'route' => 'admin.wallet.index'],
                    ['label' => 'ประวัติธุรกรรม', 'route' => 'admin.wallet.transactions'],
                    ['label' => 'คำขอถอนเงิน', 'route' => 'admin.withdrawals.pending'],
                    ['label' => 'ประวัติการถอน', 'route' => 'admin.withdrawals.index'],
                    ['label' => 'ตั้งค่า Payment', 'route' => 'admin.payment-gateways.index'],
                ]
            ],
            [
                'icon' => '₿',
                'label' => 'กระเป๋าคริปโต',
                'route' => null,
                'order' => 9,
                'submenu' => [
                    ['label' => 'แดชบอร์ด', 'route' => 'admin.crypto.dashboard'],
                    ['label' => 'จัดการ Wallets', 'route' => 'admin.crypto.wallets'],
                    ['label' => 'ธุรกรรม', 'route' => 'admin.crypto.transactions'],
                    ['label' => 'คำขอถอน', 'route' => 'admin.crypto.withdrawals'],
                    ['label' => 'จัดการเหรียญ/สกุลเงิน', 'route' => 'admin.crypto.currencies'],
                    ['label' => 'ตั้งค่ากระเป๋าเงิน', 'route' => 'admin.wallet-settings.index'],
                    ['label' => 'ตั้งค่าคริปโต', 'route' => 'admin.crypto.settings'],
                ]
            ],
            [
                'icon' => '💵',
                'label' => 'คอมมิชชั่น',
                'route' => null,
                'order' => 10,
                'submenu' => [
                    ['label' => 'รายการทั้งหมด', 'route' => 'admin.commissions.index'],
                    ['label' => 'รายงานคอมมิชชั่น', 'route' => 'admin.mlm.commissions.index'],
                ]
            ],
            [
                'icon' => '📧',
                'label' => 'จัดการอีเมล',
                'route' => null,
                'order' => 11,
                'submenu' => [
                    ['label' => 'เทมเพลต', 'route' => 'admin.email.templates.index'],
                    ['label' => 'ผู้ให้บริการ', 'route' => 'admin.email.providers'],
                    ['label' => 'ประวัติการส่ง', 'route' => 'admin.email.logs'],
                ]
            ],
            [
                'icon' => '📱',
                'label' => 'LINE OA & AI',
                'route' => null,
                'order' => 12,
                'submenu' => [
                    ['label' => 'ตั้งค่า LINE OA', 'route' => 'admin.line-oa.index'],
                    ['label' => 'AI Chat Bot', 'route' => 'admin.line-bot.ai.index'],
                    ['label' => 'Broadcast', 'route' => 'admin.line-bot.broadcast.index'],
                    ['label' => 'Avatar', 'route' => 'admin.line-bot.avatars.index'],
                    ['label' => 'Chat Widget', 'route' => 'admin.line-bot.chat-widget.index'],
                ]
            ],
            [
                'icon' => '🎓',
                'label' => 'Academy System',
                'route' => null,
                'order' => 13,
                'submenu' => [
                    ['label' => 'คอร์สเรียน', 'route' => 'admin.academy.courses.index'],
                    ['label' => 'ใบประกาศ', 'route' => 'admin.academy.certificates.index'],
                    ['label' => 'ตั้งค่า', 'route' => 'admin.academy.settings.index'],
                ]
            ],
            [
                'icon' => '📚',
                'label' => 'Learning Center',
                'route' => null,
                'order' => 14,
                'submenu' => [
                    ['label' => 'บทความ', 'route' => 'admin.articles.index'],
                    ['label' => 'หมวดหมู่', 'route' => 'admin.categories.index'],
                    ['label' => 'ศูนย์เรียนรู้', 'route' => 'admin.learning-center.index'],
                ]
            ],
            [
                'icon' => '💎',
                'label' => 'MLM System',
                'route' => null,
                'order' => 15,
                'submenu' => [
                    ['label' => 'สมาชิก MLM', 'route' => 'admin.mlm.members.index'],
                    ['label' => 'แผน MLM', 'route' => 'admin.mlm.plans.index'],
                    ['label' => 'ผังสายงาน', 'route' => 'admin.mlm.genealogy.index'],
                    ['label' => 'คอมมิชชั่น', 'route' => 'admin.mlm.commissions.index'],
                    ['label' => 'Product PV', 'route' => 'admin.mlm.product-pv.index'],
                    ['label' => 'รายงาน', 'route' => 'admin.mlm.reports.dashboard'],
                    ['label' => 'ตั้งค่า MLM', 'route' => 'admin.mlm.settings.index'],
                ]
            ],
            [
                'icon' => '📈',
                'label' => 'ระบบการตลาด',
                'route' => null,
                'order' => 16,
                'submenu' => [
                    ['label' => 'Affiliates', 'route' => 'admin.affiliates.index'],
                    ['label' => 'โครงสร้างทีม', 'route' => 'admin.affiliates.tree'],
                    ['label' => 'ระบบรักษายอด', 'route' => 'admin.retention.index'],
                    ['label' => 'จัดการระดับ Rank', 'route' => 'admin.ranks.index'],
                    ['label' => 'การเลื่อนระดับ', 'route' => 'admin.ranks.promotions.index'],
                    ['label' => 'Cashback', 'route' => 'admin.cashback.index'],
                ]
            ],
            [
                'icon' => '👨‍💼',
                'label' => 'HRM (HR)',
                'route' => null,
                'order' => 17,
                'submenu' => [
                    ['label' => 'แดชบอร์ด', 'route' => 'admin.hrm.dashboard'],
                    ['label' => 'พนักงาน', 'route' => 'admin.hrm.employees.index'],
                    ['label' => 'แผนก', 'route' => 'admin.hrm.departments.index'],
                    ['label' => 'ตำแหน่ง', 'route' => 'admin.hrm.positions.index'],
                    ['label' => 'การลา', 'route' => 'admin.hrm.leave.index'],
                    ['label' => 'เงินเดือน', 'route' => 'admin.hrm.payroll.index'],
                ]
            ],
            [
                'icon' => '📊',
                'label' => 'บัญชี (Accounting)',
                'route' => null,
                'order' => 18,
                'submenu' => [
                    ['label' => 'แดชบอร์ด', 'route' => 'admin.accounting.dashboard'],
                    ['label' => 'ใบแจ้งหนี้', 'route' => 'admin.accounting.invoices.index'],
                    ['label' => 'ค่าใช้จ่าย', 'route' => 'admin.accounting.expenses.index'],
                    ['label' => 'ผู้ติดต่อ', 'route' => 'admin.accounting.contacts.index'],
                    ['label' => 'สินค้า', 'route' => 'admin.accounting.products.index'],
                    ['label' => 'รายงาน', 'route' => 'admin.accounting.reports.index'],
                    ['label' => 'FlowAccount', 'route' => 'admin.accounting.flowaccount.index'],
                ]
            ],
            [
                'icon' => '🔔',
                'label' => 'การแจ้งเตือน',
                'route' => null,
                'order' => 19,
                'submenu' => [
                    ['label' => 'ส่งการแจ้งเตือน', 'route' => 'admin.notifications.create'],
                    ['label' => 'ประวัติ', 'route' => 'admin.notifications.index'],
                    ['label' => 'เทมเพลต', 'route' => 'admin.notification-templates.index'],
                    ['label' => 'สถิติ', 'route' => 'admin.notifications.statistics'],
                ]
            ],
            [
                'icon' => '🔒',
                'label' => 'ความปลอดภัย',
                'route' => null,
                'order' => 20,
                'submenu' => [
                    ['label' => 'ภาพรวม', 'route' => 'admin.security.index'],
                    ['label' => 'Threat Intelligence', 'route' => 'admin.security.threat-intelligence'],
                    ['label' => 'Analytics', 'route' => 'admin.security.analytics'],
                    ['label' => 'OTP Settings', 'route' => 'admin.otp.settings'],
                ]
            ],
            [
                'icon' => '📄',
                'label' => 'เพจ & SEO',
                'route' => null,
                'order' => 21,
                'submenu' => [
                    ['label' => 'จัดการเพจ', 'route' => 'admin.pages.index'],
                    ['label' => 'SEO Settings', 'route' => 'admin.seo.index'],
                ]
            ],
            [
                'icon' => '📊',
                'label' => 'Analytics',
                'route' => null,
                'order' => 22,
                'submenu' => [
                    ['label' => 'ภาพรวม', 'route' => 'admin.analytics.index'],
                ]
            ],
            [
                'icon' => '🎨',
                'label' => 'ธีม & UI',
                'route' => null,
                'order' => 23,
                'submenu' => [
                    ['label' => 'Theme Builder', 'route' => 'admin.themes.builder'],
                    ['label' => 'Page Builder', 'route' => 'admin.page-builder.index'],
                    ['label' => 'Windows UI', 'route' => 'admin.windows-ui.index'],
                    ['label' => 'Icons', 'route' => 'admin.icons.index'],
                    ['label' => 'Floating Tools', 'route' => 'admin.floating-tools.index'],
                ]
            ],
            [
                'icon' => '🌐',
                'label' => 'ภาษา & แปล',
                'route' => null,
                'order' => 24,
                'submenu' => [
                    ['label' => 'การแปล', 'route' => 'admin.translations.index'],
                    ['label' => 'ตั้งค่าภาษา', 'route' => 'admin.settings.languages'],
                ]
            ],
            [
                'icon' => '⚙️',
                'label' => 'ตั้งค่าระบบ',
                'route' => null,
                'order' => 25,
                'submenu' => [
                    ['label' => 'ตั้งค่าทั่วไป', 'route' => 'admin.settings.index'],
                    ['label' => 'ตั้งค่า Mobile App', 'route' => 'admin.app-management.settings.index'],
                    ['label' => 'ตั้งค่า OCR', 'route' => 'admin.settings.ocr'],
                    ['label' => 'ตั้งค่า 2FA', 'route' => 'admin.two-factor.settings'],
                ]
            ],
        ];

        WindowsUiSetting::set('windows_start_menu_items_admin', $adminMenuItems, 'json');

        // Start Menu Items - Seller Menu Structure
        // Extracted from millennium-start-menu.blade.php (complete seller menu)
        $sellerMenuItems = [
            ['icon' => '📊', 'label' => 'แดชบอร์ด', 'route' => 'seller.dashboard', 'order' => 0],
            [
                'icon' => '📦',
                'label' => 'สินค้า',
                'route' => null,
                'order' => 1,
                'submenu' => [
                    ['label' => 'รายการสินค้า', 'route' => 'seller.products.index'],
                    ['label' => 'เพิ่มสินค้า', 'route' => 'seller.products.create'],
                ]
            ],
            [
                'icon' => '🏪',
                'label' => 'ระบบ POS',
                'route' => null,
                'order' => 2,
                'submenu' => [
                    ['label' => 'ขายสินค้า', 'route' => 'seller.pos.terminal'],
                    ['label' => 'รายการขาย', 'route' => 'seller.pos.transactions'],
                    ['label' => 'Session', 'route' => 'seller.pos.sessions'],
                    ['label' => 'ตั้งค่า POS', 'route' => 'seller.pos.settings'],
                ]
            ],
            [
                'icon' => '🛒',
                'label' => 'ยอดขาย',
                'route' => null,
                'order' => 3,
                'submenu' => [
                    ['label' => 'คำสั่งซื้อ', 'route' => 'seller.orders.index'],
                    ['label' => 'รายงานยอดขาย', 'route' => 'seller.reports.sales'],
                ]
            ],
            [
                'icon' => '💰',
                'label' => 'กระเป๋าเงิน',
                'route' => null,
                'order' => 4,
                'submenu' => [
                    ['label' => 'กระเป๋าของฉัน', 'route' => 'seller.wallet.index'],
                    ['label' => 'ถอนเงิน', 'route' => 'seller.wallet.withdraw'],
                ]
            ],
            ['icon' => '💵', 'label' => 'คอมมิชชั่น', 'route' => 'seller.commissions', 'order' => 5],
            [
                'icon' => '📈',
                'label' => 'วิเคราะห์',
                'route' => null,
                'order' => 6,
                'submenu' => [
                    ['label' => '📊 Dashboard', 'route' => 'seller.analytics.index'],
                    ['label' => '🤖 AI Insights', 'route' => 'seller.analytics.ai-insights'],
                    ['label' => '👥 Customer Segments', 'route' => 'seller.analytics.segmentation'],
                    ['label' => '📈 Cohort Analysis', 'route' => 'seller.analytics.cohort'],
                    ['label' => '🏆 Products Ranking', 'route' => 'seller.analytics.products'],
                    ['label' => '🖥️ System Monitoring', 'route' => 'seller.analytics.system-monitoring'],
                    ['label' => '⚙️ Settings', 'route' => 'seller.analytics.settings'],
                ]
            ],
            ['icon' => '⚙️', 'label' => 'ตั้งค่าร้าน', 'route' => 'seller.settings', 'order' => 7],
            ['icon' => '👤', 'label' => 'โปรไฟล์', 'route' => 'seller.profile', 'order' => 8],
        ];

        WindowsUiSetting::set('windows_start_menu_items_seller', $sellerMenuItems, 'json');

        // Start Menu Items - User Menu Structure
        // Extracted from millennium-start-menu.blade.php (complete user menu)
        $userMenuItems = [
            ['icon' => '📊', 'label' => 'แดชบอร์ด', 'route' => 'user.dashboard', 'order' => 0],
            ['icon' => '👤', 'label' => 'โปรไฟล์', 'route' => 'user.profile', 'order' => 1],
            ['icon' => '🪪', 'label' => 'ยืนยันตัวตน KYC', 'route' => 'user.kyc.index', 'order' => 2],
            ['icon' => '💰', 'label' => 'คอมมิชชั่น', 'route' => 'user.commissions', 'order' => 3],
            [
                'icon' => '🛒',
                'label' => 'ช๊อปปิ้ง',
                'route' => null,
                'order' => 4,
                'submenu' => [
                    ['label' => 'ช๊อปสินค้า', 'route' => 'shop.index'],
                ]
            ],
            [
                'icon' => '🏨',
                'label' => 'โรงแรม',
                'route' => null,
                'order' => 5,
                'submenu' => [
                    ['label' => 'จองโรงแรม', 'route' => 'hotels.index'],
                    ['label' => 'การจองของฉัน', 'route' => 'hotels.bookings.index'],
                ]
            ],
            ['icon' => '🎫', 'label' => 'Ticket Support', 'route' => 'user.tickets.index', 'order' => 6],
            [
                'icon' => '💳',
                'label' => 'กระเป๋าเงิน THB',
                'route' => null,
                'order' => 7,
                'submenu' => [
                    ['label' => 'กระเป๋าของฉัน', 'route' => 'user.wallet.index'],
                    ['label' => 'ถอนเงิน', 'route' => 'user.wallet.withdraw'],
                ]
            ],
            [
                'icon' => '₿',
                'label' => 'กระเป๋าคริปโต',
                'route' => null,
                'order' => 8,
                'submenu' => [
                    ['label' => 'กระเป๋าคริปโต', 'route' => 'user.crypto-wallet.index'],
                ]
            ],
            [
                'icon' => '📈',
                'label' => 'การลงทุน ROI',
                'route' => null,
                'order' => 9,
                'submenu' => [
                    ['label' => 'แดชบอร์ด', 'route' => 'user.investments.index'],
                    ['label' => 'แผนการลงทุน', 'route' => 'user.investments.plans'],
                ]
            ],
            [
                'icon' => '🤖',
                'label' => 'AI Bots',
                'route' => null,
                'order' => 10,
                'submenu' => [
                    ['label' => 'ตลาดบอท', 'route' => 'marketplace.index'],
                ]
            ],
            [
                'icon' => '👥',
                'label' => 'ทีมงาน',
                'route' => null,
                'order' => 11,
                'submenu' => [
                    ['label' => 'ผู้แนะนำ', 'route' => 'user.referrals'],
                    ['label' => 'ผังสายงาน', 'route' => 'user.organization'],
                ]
            ],
            [
                'icon' => '💖',
                'label' => 'รักษายอด',
                'route' => null,
                'order' => 12,
                'submenu' => [
                    ['label' => 'สถานะพลังชีวิต', 'route' => 'user.retention.index'],
                ]
            ],
            [
                'icon' => '🎯',
                'label' => 'เครื่องมือการตลาด',
                'route' => null,
                'order' => 13,
                'submenu' => [
                    ['label' => 'จำลองรายได้', 'route' => 'user.mlm.income-simulator'],
                ]
            ],
            ['icon' => '🎨', 'label' => 'ตั้งค่าธีม', 'route' => 'user.themes.index', 'order' => 14],
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

        // Clock Settings
        WindowsUiSetting::set('millennium_clock_style', 'digital', 'string');
        WindowsUiSetting::set('millennium_clock_format', '24h', 'string');
        WindowsUiSetting::set('millennium_clock_show_seconds', false, 'boolean');
        WindowsUiSetting::set('millennium_clock_show_date', false, 'boolean');
        WindowsUiSetting::set('millennium_clock_date_format', 'short', 'string');

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

    /**
     * Update Mode: Add only missing settings (preserves user customizations)
     */
    private function updateMode(): void
    {
        $this->command->warn('⚠️  Existing settings detected!');
        $this->command->info('   Running in UPDATE mode (adding missing settings only)...');

        $added = 0;
        $skipped = 0;

        // Define all possible settings with their default values
        $allSettings = $this->getAllDefaultSettings();

        // Check and add only missing settings
        foreach ($allSettings as $key => $config) {
            if (!WindowsUiSetting::where('key', $key)->exists()) {
                WindowsUiSetting::set($key, $config['value'], $config['type']);
                $this->command->info("   ➕ Added: {$key}");
                $added++;
            } else {
                $skipped++;
            }
        }

        if ($added > 0) {
            $this->command->info("✅ Added {$added} new settings.");
        }

        if ($skipped > 0) {
            $this->command->info("   ⏭️  Skipped {$skipped} existing settings (preserved).");
        }

        if ($added === 0) {
            $this->command->info('✅ All settings are up to date. No changes needed.');
        }
    }

    /**
     * Get all default settings
     *
     * @return array
     */
    private function getAllDefaultSettings(): array
    {
        // Note: Menu items are handled separately as they're complex structures
        // This method only tracks scalar settings
        return [
            // Taskbar Settings
            'windows_taskbar_position' => ['value' => 'top', 'type' => 'string'],
            'windows_taskbar_height' => ['value' => 60, 'type' => 'integer'],
            'windows_taskbar_blur' => ['value' => true, 'type' => 'boolean'],
            'windows_taskbar_transparency' => ['value' => 95, 'type' => 'integer'],

            // Start Button Settings
            'windows_start_button_text' => ['value' => 'เริ่ม', 'type' => 'string'],
            'windows_start_button_use_logo' => ['value' => true, 'type' => 'boolean'],
            'windows_start_button_position' => ['value' => 'center', 'type' => 'string'],

            // Back Button Settings
            'millennium_back_button_enabled' => ['value' => true, 'type' => 'boolean'],
            'millennium_back_button_text' => ['value' => 'กลับ', 'type' => 'string'],

            // Center Section Settings
            'millennium_center_section_enabled' => ['value' => true, 'type' => 'boolean'],
            'millennium_center_section_text' => ['value' => 'Thai Prompt Affiliate', 'type' => 'string'],

            // RGB Settings
            'millennium_rgb_enabled' => ['value' => true, 'type' => 'boolean'],
            'millennium_rgb_speed' => ['value' => 5, 'type' => 'integer'],

            // Millennium Start Menu Settings
            'millennium_menu_position' => ['value' => 'center', 'type' => 'string'],
            'millennium_menu_width' => ['value' => '400', 'type' => 'string'],
            'millennium_menu_width_unit' => ['value' => 'px', 'type' => 'string'],
            'millennium_menu_max_height' => ['value' => '600', 'type' => 'string'],
            'millennium_menu_max_height_unit' => ['value' => 'px', 'type' => 'string'],
            'millennium_menu_rgb_enabled' => ['value' => true, 'type' => 'boolean'],

            // Responsive Taskbar Settings
            'millennium_taskbar_collapse_enabled' => ['value' => true, 'type' => 'boolean'],
            'millennium_taskbar_collapse_breakpoint' => ['value' => 768, 'type' => 'integer'],

            // Clock Settings
            'millennium_clock_style' => ['value' => 'digital', 'type' => 'string'],
            'millennium_clock_format' => ['value' => '24h', 'type' => 'string'],
            'millennium_clock_show_seconds' => ['value' => false, 'type' => 'boolean'],
            'millennium_clock_show_date' => ['value' => false, 'type' => 'boolean'],
            'millennium_clock_date_format' => ['value' => 'short', 'type' => 'string'],

            // RGB Settings
            'windows_rgb_enabled' => ['value' => true, 'type' => 'boolean'],
            'windows_rgb_speed' => ['value' => 3, 'type' => 'integer'],
            'windows_rgb_glow' => ['value' => true, 'type' => 'boolean'],
            'windows_rgb_colors' => ['value' => ['#FF0080', '#00F0FF', '#7F00FF', '#FF3D00'], 'type' => 'json'],

            // Theme Settings
            'windows_theme_mode' => ['value' => 'auto', 'type' => 'string'],
            'windows_accent_color' => ['value' => '#667eea', 'type' => 'color'],

            // Spaceship Theme
            'windows_spaceship_theme' => ['value' => true, 'type' => 'boolean'],
            'windows_spaceship_stars' => ['value' => true, 'type' => 'boolean'],

            // System Tray Info
            'windows_license_text' => ['value' => 'Licensed to Thai Prompt', 'type' => 'string'],
            'windows_copyright_text' => ['value' => '© 2025 TP-Affiliate Platform', 'type' => 'string'],

            // Content Width Settings
            'content_width_mode' => ['value' => 'max', 'type' => 'string'],
            'content_width_custom' => ['value' => 1400, 'type' => 'integer'],
        ];
    }
}
