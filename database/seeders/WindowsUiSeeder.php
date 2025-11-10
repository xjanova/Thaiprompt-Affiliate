<?php

namespace Database\Seeders;

use App\Models\WindowsUiSetting;
use Illuminate\Database\Seeder;

class WindowsUiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Smart Seeding Strategy (NEVER DELETES USER DATA):
     * - Check each setting individually
     * - If exists: SKIP (preserve user customization)
     * - If missing: ADD (insert default value)
     *
     * CRITICAL RULES:
     * 1. ❌ NEVER delete existing settings
     * 2. ❌ NEVER overwrite existing settings
     * 3. ✅ ALWAYS add only missing settings
     * 4. ✅ ALWAYS preserve user customizations
     *
     * This follows the Smart Seeding Guidelines in .claude/seeder-guidelines.md
     */
    public function run(): void
    {
        $this->command->info('🔄 Running Smart Seeding for Windows UI Settings...');
        $this->command->info('   Strategy: Add missing settings only (never delete/overwrite)');

        $added = 0;
        $skipped = 0;

        // Seed all settings using Smart Seeding
        $allSettings = $this->getAllSettings();

        foreach ($allSettings as $key => $config) {
            if (!WindowsUiSetting::where('key', $key)->exists()) {
                WindowsUiSetting::set($key, $config['value'], $config['type']);
                $this->command->info("   ✅ Added: {$key}");
                $added++;
            } else {
                $skipped++;
            }
        }

        if ($added > 0) {
            $this->command->info("✨ Added {$added} new settings.");
        }

        if ($skipped > 0) {
            $this->command->info("   ⏭️  Skipped {$skipped} existing settings (preserved user customizations).");
        }

        if ($added === 0 && $skipped > 0) {
            $this->command->info('✅ All settings are up to date. No changes needed.');
        }
    }

    /**
     * Get all default settings
     * Returns all settings that should exist in the system
     */
    private function getAllSettings(): array
    {
        // Combine all settings: menus + scalar settings
        $settings = [];
        // Start Menu Items - Admin Menu Structure
        // Extracted from millennium-start-menu.blade.php (complete menu structure)
        $adminMenuItems = [
            ['icon' => '📊', 'label' => 'แดชบอร์ด', 'url' => 'admin.dashboard', 'order' => 0],
            [
                'icon' => '👥',
                'label' => 'ผู้ใช้งาน',
                'url' => null,
                'order' => 1,
                'submenu' => [
                    ['label' => 'รายชื่อผู้ใช้', 'url' => 'admin.users.index'],
                    ['label' => 'บทบาท (Roles)', 'url' => 'admin.roles.index'],
                ]
            ],
            ['icon' => '🪪', 'label' => 'ยืนยันตัวตน KYC', 'url' => 'admin.kyc.index', 'order' => 2],
            ['icon' => '🎫', 'label' => 'Ticket Support', 'url' => 'admin.tickets.index', 'order' => 3],
            [
                'icon' => '🤖',
                'label' => 'AI Bots & ผู้ช่วย',
                'url' => null,
                'order' => 4,
                'submenu' => [
                    ['label' => 'จัดการ AI Bots', 'url' => 'admin.ai-bots.index'],
                    ['label' => 'AI Providers', 'url' => 'admin.ai-providers.index'],
                    ['label' => 'ติดตั้ง AI', 'url' => 'admin.ai-installation.index'],
                ]
            ],
            [
                'icon' => '🏨',
                'label' => 'จัดการโรงแรม',
                'url' => null,
                'order' => 5,
                'submenu' => [
                    ['label' => 'โรงแรมทั้งหมด', 'url' => 'admin.hotels.index'],
                    ['label' => 'การจองทั้งหมด', 'url' => 'admin.hotels.bookings.index'],
                    ['label' => 'สถิติการจอง', 'url' => 'admin.hotels.bookings.analytics'],
                    ['label' => 'จัดการรีวิว', 'url' => 'admin.hotels.reviews.index'],
                    ['label' => 'สิ่งอำนวยความสะดวก', 'url' => 'admin.hotels.facilities.index'],
                    ['label' => 'โปรโมชั่นพิเศษ', 'url' => 'admin.hotels.special-offers.index'],
                ]
            ],
            [
                'icon' => '🛒',
                'label' => 'อีคอมเมิร์ซ',
                'url' => null,
                'order' => 6,
                'submenu' => [
                    ['label' => 'แดชบอร์ด', 'url' => 'admin.ecommerce.dashboard'],
                    ['label' => 'สินค้าทั้งหมด', 'url' => 'admin.ecommerce.products.index'],
                    ['label' => 'คำสั่งซื้อ', 'url' => 'admin.ecommerce.orders.index'],
                    ['label' => 'หมวดหมู่', 'url' => 'admin.ecommerce.categories.index'],
                    ['label' => 'รีวิวสินค้า', 'url' => 'admin.ecommerce.reviews.index'],
                ]
            ],
            [
                'icon' => '🏪',
                'label' => 'ระบบ POS',
                'url' => null,
                'order' => 7,
                'submenu' => [
                    ['label' => 'แดชบอร์ด', 'url' => 'admin.pos.dashboard'],
                    ['label' => 'อุปกรณ์ POS', 'url' => 'admin.pos.devices.index'],
                    ['label' => 'ธุรกรรม', 'url' => 'admin.pos.transactions.index'],
                    ['label' => 'วิเคราะห์ข้อมูล', 'url' => 'admin.pos.analytics'],
                ]
            ],
            [
                'icon' => '💰',
                'label' => 'กระเป๋าเงิน THB',
                'url' => null,
                'order' => 8,
                'submenu' => [
                    ['label' => 'กระเป๋าเงินทั้งหมด', 'url' => 'admin.wallet.index'],
                    ['label' => 'ประวัติธุรกรรม', 'url' => 'admin.wallet.transactions'],
                    ['label' => 'คำขอถอนเงิน', 'url' => 'admin.withdrawals.pending'],
                    ['label' => 'ประวัติการถอน', 'url' => 'admin.withdrawals.index'],
                    ['label' => 'ตั้งค่า Payment', 'url' => 'admin.payment-gateways.index'],
                ]
            ],
            [
                'icon' => '₿',
                'label' => 'กระเป๋าคริปโต',
                'url' => null,
                'order' => 9,
                'submenu' => [
                    ['label' => 'แดชบอร์ด', 'url' => 'admin.crypto.dashboard'],
                    ['label' => 'จัดการ Wallets', 'url' => 'admin.crypto.wallets'],
                    ['label' => 'ธุรกรรม', 'url' => 'admin.crypto.transactions'],
                    ['label' => 'คำขอถอน', 'url' => 'admin.crypto.withdrawals'],
                    ['label' => 'จัดการเหรียญ/สกุลเงิน', 'url' => 'admin.crypto.currencies'],
                    ['label' => 'ตั้งค่ากระเป๋าเงิน', 'url' => 'admin.wallet-settings.index'],
                    ['label' => 'ตั้งค่าคริปโต', 'url' => 'admin.crypto.settings'],
                ]
            ],
            [
                'icon' => '💵',
                'label' => 'คอมมิชชั่น',
                'url' => null,
                'order' => 10,
                'submenu' => [
                    ['label' => 'รายการทั้งหมด', 'url' => 'admin.commissions.index'],
                    ['label' => 'รายงานคอมมิชชั่น', 'url' => 'admin.mlm.commissions.index'],
                ]
            ],
            [
                'icon' => '📧',
                'label' => 'จัดการอีเมล',
                'url' => null,
                'order' => 11,
                'submenu' => [
                    ['label' => 'เทมเพลต', 'url' => 'admin.email.templates.index'],
                    ['label' => 'ผู้ให้บริการ', 'url' => 'admin.email.providers'],
                    ['label' => 'ประวัติการส่ง', 'url' => 'admin.email.logs'],
                ]
            ],
            [
                'icon' => '📱',
                'label' => 'LINE OA & AI',
                'url' => null,
                'order' => 12,
                'submenu' => [
                    ['label' => 'ตั้งค่า LINE OA', 'url' => 'admin.line-oa.index'],
                    ['label' => 'AI Chat Bot', 'url' => 'admin.line-bot.ai.index'],
                    ['label' => 'Broadcast', 'url' => 'admin.line-bot.broadcast.index'],
                    ['label' => 'Avatar', 'url' => 'admin.line-bot.avatars.index'],
                    ['label' => 'Chat Widget', 'url' => 'admin.line-bot.chat-widget.index'],
                ]
            ],
            [
                'icon' => '🎓',
                'label' => 'Academy System',
                'url' => null,
                'order' => 13,
                'submenu' => [
                    ['label' => 'คอร์สเรียน', 'url' => 'admin.academy.courses.index'],
                    ['label' => 'ใบประกาศ', 'url' => 'admin.academy.certificates.index'],
                    ['label' => 'ตั้งค่า', 'url' => 'admin.academy.settings.index'],
                ]
            ],
            [
                'icon' => '📚',
                'label' => 'Learning Center',
                'url' => null,
                'order' => 14,
                'submenu' => [
                    ['label' => 'บทความ', 'url' => 'admin.articles.index'],
                    ['label' => 'หมวดหมู่', 'url' => 'admin.categories.index'],
                    ['label' => 'ศูนย์เรียนรู้', 'url' => 'admin.learning-center.index'],
                ]
            ],
            [
                'icon' => '💎',
                'label' => 'MLM System',
                'url' => null,
                'order' => 15,
                'submenu' => [
                    ['label' => 'สมาชิก MLM', 'url' => 'admin.mlm.members.index'],
                    ['label' => 'แผน MLM', 'url' => 'admin.mlm.plans.index'],
                    ['label' => 'ผังสายงาน', 'url' => 'admin.mlm.genealogy.index'],
                    ['label' => 'คอมมิชชั่น', 'url' => 'admin.mlm.commissions.index'],
                    ['label' => 'Product PV', 'url' => 'admin.mlm.product-pv.index'],
                    ['label' => 'รายงาน', 'url' => 'admin.mlm.reports.dashboard'],
                    ['label' => 'ตั้งค่า MLM', 'url' => 'admin.mlm.settings.index'],
                ]
            ],
            [
                'icon' => '📈',
                'label' => 'ระบบการตลาด',
                'url' => null,
                'order' => 16,
                'submenu' => [
                    ['label' => 'Affiliates', 'url' => 'admin.affiliates.index'],
                    ['label' => 'โครงสร้างทีม', 'url' => 'admin.affiliates.tree'],
                    ['label' => 'ระบบรักษายอด', 'url' => 'admin.retention.index'],
                    ['label' => 'จัดการระดับ Rank', 'url' => 'admin.ranks.index'],
                    ['label' => 'การเลื่อนระดับ', 'url' => 'admin.ranks.promotions.index'],
                    ['label' => 'Cashback', 'url' => 'admin.cashback.index'],
                ]
            ],
            [
                'icon' => '👨‍💼',
                'label' => 'HRM (HR)',
                'url' => null,
                'order' => 17,
                'submenu' => [
                    ['label' => 'แดชบอร์ด', 'url' => 'admin.hrm.dashboard'],
                    ['label' => 'พนักงาน', 'url' => 'admin.hrm.employees.index'],
                    ['label' => 'แผนก', 'url' => 'admin.hrm.departments.index'],
                    ['label' => 'ตำแหน่ง', 'url' => 'admin.hrm.positions.index'],
                    ['label' => 'การลา', 'url' => 'admin.hrm.leave.index'],
                    ['label' => 'เงินเดือน', 'url' => 'admin.hrm.payroll.index'],
                ]
            ],
            [
                'icon' => '📊',
                'label' => 'บัญชี (Accounting)',
                'url' => null,
                'order' => 18,
                'submenu' => [
                    ['label' => 'แดชบอร์ด', 'url' => 'admin.accounting.dashboard'],
                    ['label' => 'ใบแจ้งหนี้', 'url' => 'admin.accounting.invoices.index'],
                    ['label' => 'ค่าใช้จ่าย', 'url' => 'admin.accounting.expenses.index'],
                    ['label' => 'ผู้ติดต่อ', 'url' => 'admin.accounting.contacts.index'],
                    ['label' => 'สินค้า', 'url' => 'admin.accounting.products.index'],
                    ['label' => 'รายงาน', 'url' => 'admin.accounting.reports.index'],
                    ['label' => 'FlowAccount', 'url' => 'admin.accounting.flowaccount.index'],
                ]
            ],
            [
                'icon' => '🔔',
                'label' => 'การแจ้งเตือน',
                'url' => null,
                'order' => 19,
                'submenu' => [
                    ['label' => 'ส่งการแจ้งเตือน', 'url' => 'admin.notifications.create'],
                    ['label' => 'ประวัติ', 'url' => 'admin.notifications.index'],
                    ['label' => 'เทมเพลต', 'url' => 'admin.notification-templates.index'],
                    ['label' => 'สถิติ', 'url' => 'admin.notifications.statistics'],
                ]
            ],
            [
                'icon' => '🔒',
                'label' => 'ความปลอดภัย',
                'url' => null,
                'order' => 20,
                'submenu' => [
                    ['label' => 'ภาพรวม', 'url' => 'admin.security.index'],
                    ['label' => 'Threat Intelligence', 'url' => 'admin.security.threat-intelligence'],
                    ['label' => 'Analytics', 'url' => 'admin.security.analytics'],
                    ['label' => 'OTP Settings', 'url' => 'admin.otp.settings'],
                ]
            ],
            [
                'icon' => '📄',
                'label' => 'เพจ & SEO',
                'url' => null,
                'order' => 21,
                'submenu' => [
                    ['label' => 'จัดการเพจ', 'url' => 'admin.pages.index'],
                    ['label' => 'SEO Settings', 'url' => 'admin.seo.index'],
                ]
            ],
            [
                'icon' => '📊',
                'label' => 'Analytics',
                'url' => null,
                'order' => 22,
                'submenu' => [
                    ['label' => 'ภาพรวม', 'url' => 'admin.analytics.index'],
                ]
            ],
            [
                'icon' => '🎨',
                'label' => 'ธีม & UI',
                'url' => null,
                'order' => 23,
                'submenu' => [
                    ['label' => 'Theme Builder', 'url' => 'admin.themes.builder'],
                    ['label' => 'Page Builder', 'url' => 'admin.page-builder.index'],
                    ['label' => 'Windows UI', 'url' => 'admin.windows-ui.index'],
                    ['label' => 'Icons', 'url' => 'admin.icons.index'],
                    ['label' => 'Floating Tools', 'url' => 'admin.floating-tools.index'],
                ]
            ],
            [
                'icon' => '🌐',
                'label' => 'ภาษา & แปล',
                'url' => null,
                'order' => 24,
                'submenu' => [
                    ['label' => 'การแปล', 'url' => 'admin.translations.index'],
                    ['label' => 'ตั้งค่าภาษา', 'url' => 'admin.settings.languages'],
                ]
            ],
            [
                'icon' => '⚙️',
                'label' => 'ตั้งค่าระบบ',
                'url' => null,
                'order' => 25,
                'submenu' => [
                    ['label' => 'ตั้งค่าทั่วไป', 'url' => 'admin.settings.index'],
                    ['label' => 'ตั้งค่า Mobile App', 'url' => 'admin.app-management.settings.index'],
                    ['label' => 'ตั้งค่า OCR', 'url' => 'admin.settings.ocr'],
                    ['label' => 'ตั้งค่า 2FA', 'url' => 'admin.two-factor.settings'],
                ]
            ],
        ];

        // Start Menu Items - Seller Menu Structure
        // Extracted from millennium-start-menu.blade.php (complete seller menu)
        $sellerMenuItems = [
            ['icon' => '📊', 'label' => 'แดชบอร์ด', 'url' => 'seller.dashboard', 'order' => 0],
            [
                'icon' => '📦',
                'label' => 'สินค้า',
                'url' => null,
                'order' => 1,
                'submenu' => [
                    ['label' => 'รายการสินค้า', 'url' => 'seller.products.index'],
                    ['label' => 'เพิ่มสินค้า', 'url' => 'seller.products.create'],
                ]
            ],
            [
                'icon' => '🏪',
                'label' => 'ระบบ POS',
                'url' => null,
                'order' => 2,
                'submenu' => [
                    ['label' => 'ขายสินค้า', 'url' => 'seller.pos.terminal'],
                    ['label' => 'รายการขาย', 'url' => 'seller.pos.transactions'],
                    ['label' => 'Session', 'url' => 'seller.pos.sessions'],
                    ['label' => 'ตั้งค่า POS', 'url' => 'seller.pos.settings'],
                ]
            ],
            [
                'icon' => '🛒',
                'label' => 'ยอดขาย',
                'url' => null,
                'order' => 3,
                'submenu' => [
                    ['label' => 'คำสั่งซื้อ', 'url' => 'seller.orders.index'],
                    ['label' => 'รายงานยอดขาย', 'url' => 'seller.reports.sales'],
                ]
            ],
            [
                'icon' => '💰',
                'label' => 'กระเป๋าเงิน',
                'url' => null,
                'order' => 4,
                'submenu' => [
                    ['label' => 'กระเป๋าของฉัน', 'url' => 'seller.wallet.index'],
                    ['label' => 'ถอนเงิน', 'url' => 'seller.wallet.withdraw'],
                ]
            ],
            ['icon' => '💵', 'label' => 'คอมมิชชั่น', 'url' => 'seller.commissions', 'order' => 5],
            [
                'icon' => '📈',
                'label' => 'วิเคราะห์',
                'url' => null,
                'order' => 6,
                'submenu' => [
                    ['label' => '📊 Dashboard', 'url' => 'seller.analytics.index'],
                    ['label' => '🤖 AI Insights', 'url' => 'seller.analytics.ai-insights'],
                    ['label' => '👥 Customer Segments', 'url' => 'seller.analytics.segmentation'],
                    ['label' => '📈 Cohort Analysis', 'url' => 'seller.analytics.cohort'],
                    ['label' => '🏆 Products Ranking', 'url' => 'seller.analytics.products'],
                    ['label' => '🖥️ System Monitoring', 'url' => 'seller.analytics.system-monitoring'],
                    ['label' => '⚙️ Settings', 'url' => 'seller.analytics.settings'],
                ]
            ],
            ['icon' => '⚙️', 'label' => 'ตั้งค่าร้าน', 'url' => 'seller.settings', 'order' => 7],
            ['icon' => '👤', 'label' => 'โปรไฟล์', 'url' => 'seller.profile', 'order' => 8],
        ];

        // Start Menu Items - User Menu Structure
        // Extracted from millennium-start-menu.blade.php (complete user menu)
        $userMenuItems = [
            ['icon' => '📊', 'label' => 'แดชบอร์ด', 'url' => 'user.dashboard', 'order' => 0],
            ['icon' => '👤', 'label' => 'โปรไฟล์', 'url' => 'user.profile', 'order' => 1],
            ['icon' => '🪪', 'label' => 'ยืนยันตัวตน KYC', 'url' => 'user.kyc.index', 'order' => 2],
            ['icon' => '💰', 'label' => 'คอมมิชชั่น', 'url' => 'user.commissions', 'order' => 3],
            [
                'icon' => '🛒',
                'label' => 'ช๊อปปิ้ง',
                'url' => null,
                'order' => 4,
                'submenu' => [
                    ['label' => 'ช๊อปสินค้า', 'url' => 'shop.index'],
                ]
            ],
            [
                'icon' => '🏨',
                'label' => 'โรงแรม',
                'url' => null,
                'order' => 5,
                'submenu' => [
                    ['label' => 'จองโรงแรม', 'url' => 'hotels.index'],
                    ['label' => 'การจองของฉัน', 'url' => 'hotels.bookings.index'],
                ]
            ],
            ['icon' => '🎫', 'label' => 'Ticket Support', 'url' => 'user.tickets.index', 'order' => 6],
            [
                'icon' => '💳',
                'label' => 'กระเป๋าเงิน THB',
                'url' => null,
                'order' => 7,
                'submenu' => [
                    ['label' => 'กระเป๋าของฉัน', 'url' => 'user.wallet.index'],
                    ['label' => 'ถอนเงิน', 'url' => 'user.wallet.withdraw'],
                ]
            ],
            [
                'icon' => '₿',
                'label' => 'กระเป๋าคริปโต',
                'url' => null,
                'order' => 8,
                'submenu' => [
                    ['label' => 'กระเป๋าคริปโต', 'url' => 'user.crypto-wallet.index'],
                ]
            ],
            [
                'icon' => '📈',
                'label' => 'การลงทุน ROI',
                'url' => null,
                'order' => 9,
                'submenu' => [
                    ['label' => 'แดชบอร์ด', 'url' => 'user.investments.index'],
                    ['label' => 'แผนการลงทุน', 'url' => 'user.investments.plans'],
                ]
            ],
            [
                'icon' => '🤖',
                'label' => 'AI Bots',
                'url' => null,
                'order' => 10,
                'submenu' => [
                    ['label' => 'ตลาดบอท', 'url' => 'marketplace.index'],
                ]
            ],
            [
                'icon' => '👥',
                'label' => 'ทีมงาน',
                'url' => null,
                'order' => 11,
                'submenu' => [
                    ['label' => 'ผู้แนะนำ', 'url' => 'user.referrals'],
                    ['label' => 'ผังสายงาน', 'url' => 'user.organization'],
                ]
            ],
            [
                'icon' => '💖',
                'label' => 'รักษายอด',
                'url' => null,
                'order' => 12,
                'submenu' => [
                    ['label' => 'สถานะพลังชีวิต', 'url' => 'user.retention.index'],
                ]
            ],
            [
                'icon' => '🎯',
                'label' => 'เครื่องมือการตลาด',
                'url' => null,
                'order' => 13,
                'submenu' => [
                    ['label' => 'จำลองรายได้', 'url' => 'user.mlm.income-simulator'],
                ]
            ],
            ['icon' => '🎨', 'label' => 'ตั้งค่าธีม', 'url' => 'user.themes.index', 'order' => 14],
        ];

        // Add menu items to settings array
        $settings['windows_start_menu_items_admin'] = ['value' => $adminMenuItems, 'type' => 'json'];
        $settings['windows_start_menu_items_seller'] = ['value' => $sellerMenuItems, 'type' => 'json'];
        $settings['windows_start_menu_items_user'] = ['value' => $userMenuItems, 'type' => 'json'];

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

        // Add taskbar apps to settings array
        $settings['windows_taskbar_apps'] = ['value' => $taskbarApps, 'type' => 'json'];

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

        // Add system tray to settings array
        $settings['windows_system_tray_icons'] = ['value' => $systemTrayIcons, 'type' => 'json'];

        // Additional Windows UI Settings (Scalar Values)
        $settings += [
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

        return $settings;
    }
}
