@props(['type' => 'admin'])

@php
    use App\Models\ClassicXSetting;
    use Illuminate\Support\Facades\Route;

    // Get user info
    $user = auth()->user();

    // Safe route helper
    function safeRoute($routeName, $default = '#') {
        try {
            if (Route::has($routeName)) {
                return route($routeName);
            }
        } catch (\Exception $e) {
            $path = str_replace('.index', '', $routeName);
            $path = str_replace('.', '/', $path);
            return '/' . $path;
        }
        return $default;
    }

    // Current route for active state
    $currentRoute = request()->route() ? request()->route()->getName() : '';

    // Define menu items based on type
    if ($type === 'admin') {
        $menuItems = [
            ['icon' => 'fa-dashboard', 'label' => 'แดชบอร์ด', 'url' => safeRoute('admin.dashboard'), 'route' => 'admin.dashboard'],
            [
                'icon' => 'fa-users',
                'label' => 'ผู้ใช้งาน',
                'url' => '#',
                'submenu' => [
                    ['label' => 'รายชื่อผู้ใช้', 'url' => safeRoute('admin.users.index'), 'route' => 'admin.users.index'],
                    ['label' => 'บทบาท (Roles)', 'url' => safeRoute('admin.roles.index'), 'route' => 'admin.roles.index'],
                ]
            ],
            ['icon' => 'fa-id-card', 'label' => 'ยืนยันตัวตน KYC', 'url' => safeRoute('admin.kyc.index'), 'route' => 'admin.kyc.index'],
            ['icon' => 'fa-ticket', 'label' => 'Ticket Support', 'url' => safeRoute('admin.tickets.index'), 'route' => 'admin.tickets.index'],
            [
                'icon' => 'fa-robot',
                'label' => 'AI Bots & ผู้ช่วย',
                'url' => '#',
                'submenu' => [
                    ['label' => 'จัดการ AI Bots', 'url' => safeRoute('admin.ai-bots.index'), 'route' => 'admin.ai-bots.index'],
                    ['label' => 'AI Providers', 'url' => safeRoute('admin.ai-providers.index'), 'route' => 'admin.ai-providers.index'],
                    ['label' => 'ติดตั้ง AI', 'url' => safeRoute('admin.ai-installation.index'), 'route' => 'admin.ai-installation.index'],
                    ['label' => 'AI Monitoring', 'url' => safeRoute('admin.ai-monitoring.index'), 'route' => 'admin.ai-monitoring.index'],
                    ['label' => 'Knowledge Bases', 'url' => safeRoute('admin.knowledge-bases.index'), 'route' => 'admin.knowledge-bases.index'],
                ]
            ],
            [
                'icon' => 'fa-sliders',
                'label' => 'Smart Slider Pro',
                'url' => '#',
                'badge' => 'NEW',
                'submenu' => [
                    ['label' => 'Dashboard', 'url' => safeRoute('admin.smart-sliders.index'), 'route' => 'admin.smart-sliders.index'],
                    ['label' => 'สร้าง Slider ใหม่', 'url' => safeRoute('admin.smart-sliders.create'), 'route' => 'admin.smart-sliders.create'],
                    ['label' => 'Template Gallery', 'url' => safeRoute('admin.smart-sliders.index'), 'route' => 'admin.smart-sliders.index'],
                ]
            ],
            [
                'icon' => 'fa-hotel',
                'label' => 'จัดการโรงแรม',
                'url' => '#',
                'submenu' => [
                    ['label' => 'โรงแรมทั้งหมด', 'url' => safeRoute('admin.hotels.index'), 'route' => 'admin.hotels.index'],
                    ['label' => 'การจองทั้งหมด', 'url' => safeRoute('admin.hotels.bookings.index'), 'route' => 'admin.hotels.bookings.index'],
                    ['label' => 'สถิติการจอง', 'url' => safeRoute('admin.hotels.bookings.analytics'), 'route' => 'admin.hotels.bookings.analytics'],
                    ['label' => 'จัดการรีวิว', 'url' => safeRoute('admin.hotels.reviews.index'), 'route' => 'admin.hotels.reviews.index'],
                ]
            ],
            [
                'icon' => 'fa-shopping-cart',
                'label' => 'อีคอมเมิร์ซ',
                'url' => '#',
                'submenu' => [
                    ['label' => 'แดชบอร์ด', 'url' => safeRoute('admin.ecommerce.dashboard'), 'route' => 'admin.ecommerce.dashboard'],
                    ['label' => 'สินค้าทั้งหมด', 'url' => safeRoute('admin.ecommerce.products.index'), 'route' => 'admin.ecommerce.products.index'],
                    ['label' => 'คำสั่งซื้อ', 'url' => safeRoute('admin.ecommerce.orders.index'), 'route' => 'admin.ecommerce.orders.index'],
                    ['label' => 'หมวดหมู่', 'url' => safeRoute('admin.ecommerce.categories.index'), 'route' => 'admin.ecommerce.categories.index'],
                ]
            ],
            [
                'icon' => 'fa-cash-register',
                'label' => 'ระบบ POS',
                'url' => '#',
                'submenu' => [
                    ['label' => 'แดชบอร์ด', 'url' => safeRoute('admin.pos.dashboard'), 'route' => 'admin.pos.dashboard'],
                    ['label' => 'อุปกรณ์ POS', 'url' => safeRoute('admin.pos.devices.index'), 'route' => 'admin.pos.devices.index'],
                    ['label' => 'ธุรกรรม', 'url' => safeRoute('admin.pos.transactions.index'), 'route' => 'admin.pos.transactions.index'],
                ]
            ],
            [
                'icon' => 'fa-wallet',
                'label' => 'กระเป๋าเงิน THB',
                'url' => '#',
                'submenu' => [
                    ['label' => 'กระเป๋าเงินทั้งหมด', 'url' => safeRoute('admin.wallet.index'), 'route' => 'admin.wallet.index'],
                    ['label' => 'ประวัติธุรกรรม', 'url' => safeRoute('admin.wallet.transactions'), 'route' => 'admin.wallet.transactions'],
                    ['label' => 'คำขอถอนเงิน', 'url' => safeRoute('admin.withdrawals.pending'), 'route' => 'admin.withdrawals.pending'],
                    ['label' => 'ประวัติการถอน', 'url' => safeRoute('admin.withdrawals.index'), 'route' => 'admin.withdrawals.index'],
                ]
            ],
            [
                'icon' => 'fa-bitcoin',
                'label' => 'กระเป๋าคริปโต',
                'url' => '#',
                'submenu' => [
                    ['label' => 'แดชบอร์ด', 'url' => safeRoute('admin.crypto.dashboard'), 'route' => 'admin.crypto.dashboard'],
                    ['label' => 'จัดการ Wallets', 'url' => safeRoute('admin.crypto.wallets'), 'route' => 'admin.crypto.wallets'],
                    ['label' => 'ธุรกรรม', 'url' => safeRoute('admin.crypto.transactions'), 'route' => 'admin.crypto.transactions'],
                    ['label' => 'คำขอถอน', 'url' => safeRoute('admin.crypto.withdrawals'), 'route' => 'admin.crypto.withdrawals'],
                ]
            ],
            [
                'icon' => 'fa-link',
                'label' => 'TPIX Blockchain',
                'url' => '#',
                'badge' => 'NEW',
                'submenu' => [
                    ['label' => 'Dashboard', 'url' => safeRoute('admin.tpix.dashboard'), 'route' => 'admin.tpix.dashboard'],
                    ['label' => 'Network Status', 'url' => safeRoute('admin.tpix.network-status'), 'route' => 'admin.tpix.network-status'],
                    ['label' => 'Wallets', 'url' => safeRoute('admin.tpix.wallets'), 'route' => 'admin.tpix.wallets'],
                    ['label' => 'Transactions', 'url' => safeRoute('admin.tpix.transactions'), 'route' => 'admin.tpix.transactions'],
                    ['label' => 'Settings', 'url' => safeRoute('admin.tpix.settings'), 'route' => 'admin.tpix.settings'],
                ]
            ],
            [
                'icon' => 'fa-coins',
                'label' => 'Token Management',
                'url' => '#',
                'badge' => 'NEW',
                'submenu' => [
                    ['label' => 'Token ทั้งหมด', 'url' => safeRoute('admin.tokens.index'), 'route' => 'admin.tokens.index'],
                    ['label' => 'Approve/Reject', 'url' => safeRoute('admin.tokens.index'), 'route' => 'admin.tokens.index'],
                    ['label' => 'Verification', 'url' => safeRoute('admin.tokens.index'), 'route' => 'admin.tokens.index'],
                    ['label' => 'Import from CMC', 'url' => safeRoute('admin.tokens.import-cmc'), 'route' => 'admin.tokens.import-cmc'],
                ]
            ],
            [
                'icon' => 'fa-money-bill',
                'label' => 'คอมมิชชั่น',
                'url' => '#',
                'submenu' => [
                    ['label' => 'รายการทั้งหมด', 'url' => safeRoute('admin.commissions.index'), 'route' => 'admin.commissions.index'],
                    ['label' => 'รายงานคอมมิชชั่น', 'url' => safeRoute('admin.mlm.commissions.index'), 'route' => 'admin.mlm.commissions.index'],
                ]
            ],
            [
                'icon' => 'fa-chart-line',
                'label' => 'Trading Bot',
                'url' => '#',
                'badge' => 'NEW',
                'submenu' => [
                    ['label' => 'แดชบอร์ด', 'url' => safeRoute('admin.trading-bot.dashboard'), 'route' => 'admin.trading-bot.dashboard'],
                    ['label' => 'จัดการแพ็คเกจ', 'url' => safeRoute('admin.trading-bot.packages.index'), 'route' => 'admin.trading-bot.packages.index'],
                    ['label' => 'สมาชิก', 'url' => safeRoute('admin.trading-bot.subscriptions.index'), 'route' => 'admin.trading-bot.subscriptions.index'],
                    ['label' => 'บอททั้งหมด', 'url' => safeRoute('admin.trading-bot.bots.index'), 'route' => 'admin.trading-bot.bots.index'],
                    ['label' => 'Exchange', 'url' => safeRoute('admin.trading-bot.exchanges.index'), 'route' => 'admin.trading-bot.exchanges.index'],
                    ['label' => 'Analytics', 'url' => safeRoute('admin.trading-bot.analytics'), 'route' => 'admin.trading-bot.analytics'],
                    ['label' => 'Arbitrage Monitor', 'url' => safeRoute('admin.trading-bot.arbitrage-monitor'), 'route' => 'admin.trading-bot.arbitrage-monitor'],
                ]
            ],
            [
                'icon' => 'fa-envelope',
                'label' => 'จัดการอีเมล',
                'url' => '#',
                'submenu' => [
                    ['label' => 'เทมเพลต', 'url' => safeRoute('admin.email.templates.index'), 'route' => 'admin.email.templates.index'],
                    ['label' => 'ผู้ให้บริการ', 'url' => safeRoute('admin.email.providers'), 'route' => 'admin.email.providers'],
                    ['label' => 'ประวัติการส่ง', 'url' => safeRoute('admin.email.logs'), 'route' => 'admin.email.logs'],
                ]
            ],
            [
                'icon' => 'fa-line',
                'label' => 'LINE OA & AI',
                'url' => '#',
                'submenu' => [
                    ['label' => 'ตั้งค่า LINE OA', 'url' => safeRoute('admin.line-oa.index'), 'route' => 'admin.line-oa.index'],
                    ['label' => 'LINE Analytics', 'url' => safeRoute('admin.line-oa.analytics'), 'route' => 'admin.line-oa.analytics'],
                    ['label' => 'AI Chat Bot', 'url' => safeRoute('admin.line-bot.ai.index'), 'route' => 'admin.line-bot.ai.index'],
                    ['label' => 'Broadcast', 'url' => safeRoute('admin.line-bot.broadcast.index'), 'route' => 'admin.line-bot.broadcast.index'],
                ]
            ],
            [
                'icon' => 'fa-graduation-cap',
                'label' => 'Academy System',
                'url' => '#',
                'submenu' => [
                    ['label' => 'คอร์สเรียน', 'url' => safeRoute('admin.academy.courses.index'), 'route' => 'admin.academy.courses.index'],
                    ['label' => 'จัดการแบบทดสอบ', 'url' => safeRoute('admin.quiz-management.index'), 'route' => 'admin.quiz-management.index'],
                    ['label' => 'ใบประกาศ', 'url' => safeRoute('admin.certificates.index'), 'route' => 'admin.certificates.index'],
                ]
            ],
            [
                'icon' => 'fa-book',
                'label' => 'Learning Center',
                'url' => '#',
                'submenu' => [
                    ['label' => 'บทความ', 'url' => safeRoute('admin.articles.index'), 'route' => 'admin.articles.index'],
                    ['label' => 'หมวดหมู่', 'url' => safeRoute('admin.categories.index'), 'route' => 'admin.categories.index'],
                ]
            ],
            [
                'icon' => 'fa-gem',
                'label' => 'MLM System',
                'url' => '#',
                'submenu' => [
                    ['label' => 'สมาชิก MLM', 'url' => safeRoute('admin.mlm.members.index'), 'route' => 'admin.mlm.members.index'],
                    ['label' => 'แผน MLM', 'url' => safeRoute('admin.mlm.plans.index'), 'route' => 'admin.mlm.plans.index'],
                    ['label' => 'ผังสายงาน', 'url' => safeRoute('admin.mlm.genealogy.index'), 'route' => 'admin.mlm.genealogy.index'],
                    ['label' => 'คอมมิชชั่น', 'url' => safeRoute('admin.mlm.commissions.index'), 'route' => 'admin.mlm.commissions.index'],
                ]
            ],
            [
                'icon' => 'fa-chart-line',
                'label' => 'ระบบการตลาด',
                'url' => '#',
                'submenu' => [
                    ['label' => 'Affiliates', 'url' => safeRoute('admin.affiliates.index'), 'route' => 'admin.affiliates.index'],
                    ['label' => 'โครงสร้างทีม', 'url' => safeRoute('admin.affiliates.tree'), 'route' => 'admin.affiliates.tree'],
                    ['label' => 'ระบบรักษายอด', 'url' => safeRoute('admin.retention.index'), 'route' => 'admin.retention.index'],
                    ['label' => 'จัดการระดับ Rank', 'url' => safeRoute('admin.ranks.index'), 'route' => 'admin.ranks.index'],
                ]
            ],
            [
                'icon' => 'fa-user-tie',
                'label' => 'HRM (HR)',
                'url' => '#',
                'submenu' => [
                    ['label' => 'แดชบอร์ด', 'url' => safeRoute('admin.hrm.dashboard'), 'route' => 'admin.hrm.dashboard'],
                    ['label' => 'พนักงาน', 'url' => safeRoute('admin.hrm.employees.index'), 'route' => 'admin.hrm.employees.index'],
                    ['label' => 'แผนก', 'url' => safeRoute('admin.hrm.departments.index'), 'route' => 'admin.hrm.departments.index'],
                ]
            ],
            [
                'icon' => 'fa-calculator',
                'label' => 'บัญชี (Accounting)',
                'url' => '#',
                'submenu' => [
                    ['label' => 'แดชบอร์ด', 'url' => safeRoute('admin.accounting.dashboard'), 'route' => 'admin.accounting.dashboard'],
                    ['label' => 'ใบแจ้งหนี้', 'url' => safeRoute('admin.accounting.invoices.index'), 'route' => 'admin.accounting.invoices.index'],
                    ['label' => 'ค่าใช้จ่าย', 'url' => safeRoute('admin.accounting.expenses.index'), 'route' => 'admin.accounting.expenses.index'],
                ]
            ],
            [
                'icon' => 'fa-bell',
                'label' => 'การแจ้งเตือน',
                'url' => '#',
                'submenu' => [
                    ['label' => 'ส่งการแจ้งเตือน', 'url' => safeRoute('admin.notifications.create'), 'route' => 'admin.notifications.create'],
                    ['label' => 'ประวัติ', 'url' => safeRoute('admin.notifications.index'), 'route' => 'admin.notifications.index'],
                ]
            ],
            [
                'icon' => 'fa-lock',
                'label' => 'ความปลอดภัย',
                'url' => '#',
                'submenu' => [
                    ['label' => 'ภาพรวม', 'url' => safeRoute('admin.security.index'), 'route' => 'admin.security.index'],
                    ['label' => 'ตั้งค่า OTP', 'url' => safeRoute('admin.otp.settings'), 'route' => 'admin.otp.settings'],
                    ['label' => 'ตั้งค่า 2FA', 'url' => safeRoute('admin.two-factor.settings'), 'route' => 'admin.two-factor.settings'],
                ]
            ],
            [
                'icon' => 'fa-file-alt',
                'label' => 'เพจ & SEO',
                'url' => '#',
                'submenu' => [
                    ['label' => 'จัดการเพจ', 'url' => safeRoute('admin.pages.index'), 'route' => 'admin.pages.index'],
                    ['label' => 'SEO Settings', 'url' => safeRoute('admin.seo.index'), 'route' => 'admin.seo.index'],
                ]
            ],
            [
                'icon' => 'fa-chart-bar',
                'label' => 'Analytics',
                'url' => safeRoute('admin.analytics.index')
            ],
            [
                'icon' => 'fa-palette',
                'label' => 'ธีม & UI',
                'url' => '#',
                'submenu' => [
                    ['label' => 'Theme Builder', 'url' => safeRoute('admin.themes.builder'), 'route' => 'admin.themes.builder'],
                    ['label' => 'Page Builder', 'url' => safeRoute('admin.page-builder.index'), 'route' => 'admin.page-builder.index'],
                    ['label' => 'Windows UI (Millennium)', 'url' => safeRoute('admin.windows-ui.index'), 'route' => 'admin.windows-ui.index'],
                    ['label' => 'Classic X Theme Settings', 'url' => safeRoute('admin.classic-x-settings.index'), 'route' => 'admin.classic-x-settings.index'],
                ]
            ],
            [
                'icon' => 'fa-globe',
                'label' => 'ภาษา & แปล',
                'url' => '#',
                'submenu' => [
                    ['label' => 'การแปล', 'url' => safeRoute('admin.translations.index'), 'route' => 'admin.translations.index'],
                    ['label' => 'ตั้งค่าภาษา', 'url' => safeRoute('admin.settings.languages'), 'route' => 'admin.settings.languages'],
                ]
            ],
            [
                'icon' => 'fa-cog',
                'label' => 'ตั้งค่าระบบ',
                'url' => '#',
                'submenu' => [
                    ['label' => 'ตั้งค่าทั่วไป', 'url' => safeRoute('admin.settings.index'), 'route' => 'admin.settings.index'],
                    ['label' => 'ตั้งค่า Mobile App', 'url' => safeRoute('admin.app-management.settings.index'), 'route' => 'admin.app-management.settings.index'],
                    ['label' => 'จัดการ API', 'url' => safeRoute('admin.api-management.endpoints.index'), 'route' => 'admin.api-management.endpoints.index'],
                ]
            ],
        ];
    } elseif ($type === 'seller') {
        $menuItems = [
            ['icon' => 'fa-dashboard', 'label' => 'แดชบอร์ด', 'url' => safeRoute('seller.dashboard'), 'route' => 'seller.dashboard'],
            ['icon' => 'fa-bullhorn', 'label' => 'การตลาด', 'url' => safeRoute('seller.marketing'), 'route' => 'seller.marketing'],
            [
                'icon' => 'fa-box',
                'label' => 'สินค้า',
                'url' => '#',
                'submenu' => [
                    ['label' => 'รายการสินค้า', 'url' => safeRoute('seller.products.index'), 'route' => 'seller.products.index'],
                    ['label' => 'เพิ่มสินค้า', 'url' => safeRoute('seller.products.create'), 'route' => 'seller.products.create'],
                ]
            ],
            [
                'icon' => 'fa-cash-register',
                'label' => 'ระบบ POS',
                'url' => '#',
                'submenu' => [
                    ['label' => 'ขายสินค้า', 'url' => safeRoute('seller.pos.terminal'), 'route' => 'seller.pos.terminal'],
                    ['label' => 'รายการขาย', 'url' => safeRoute('seller.pos.transactions'), 'route' => 'seller.pos.transactions'],
                    ['label' => 'อุปกรณ์ POS', 'url' => safeRoute('seller.pos.devices'), 'route' => 'seller.pos.devices'],
                ]
            ],
            [
                'icon' => 'fa-shopping-cart',
                'label' => 'ยอดขาย',
                'url' => '#',
                'submenu' => [
                    ['label' => 'คำสั่งซื้อ', 'url' => safeRoute('seller.orders.index'), 'route' => 'seller.orders.index'],
                    ['label' => 'รายงานยอดขาย', 'url' => safeRoute('seller.reports.sales'), 'route' => 'seller.reports.sales'],
                ]
            ],
            [
                'icon' => 'fa-wallet',
                'label' => 'กระเป๋าเงิน',
                'url' => '#',
                'submenu' => [
                    ['label' => 'กระเป๋าของฉัน', 'url' => safeRoute('seller.wallet.index'), 'route' => 'seller.wallet.index'],
                    ['label' => 'ถอนเงิน', 'url' => safeRoute('seller.wallet.withdraw'), 'route' => 'seller.wallet.withdraw'],
                ]
            ],
            ['icon' => 'fa-money-bill', 'label' => 'คอมมิชชั่น', 'url' => safeRoute('seller.commissions'), 'route' => 'seller.commissions'],
            ['icon' => 'fa-cog', 'label' => 'ตั้งค่าร้าน', 'url' => safeRoute('seller.settings'), 'route' => 'seller.settings'],
            ['icon' => 'fa-user', 'label' => 'โปรไฟล์', 'url' => safeRoute('seller.profile'), 'route' => 'seller.profile'],
        ];
    } else { // user
        $menuItems = [
            ['icon' => 'fa-dashboard', 'label' => 'แดชบอร์ด', 'url' => safeRoute('user.dashboard'), 'route' => 'user.dashboard'],
            ['icon' => 'fa-user', 'label' => 'โปรไฟล์', 'url' => safeRoute('user.profile'), 'route' => 'user.profile'],
            ['icon' => 'fa-id-card', 'label' => 'ยืนยันตัวตน KYC', 'url' => safeRoute('user.kyc.index'), 'route' => 'user.kyc.index'],
            [
                'icon' => 'fa-gem',
                'label' => 'คู่มือเสริมทางเศรษฐี',
                'url' => safeRoute('user.wealth-guide-pro'),
                'route' => 'user.wealth-guide-pro',
                'badge' => 'PRO'
            ],
            ['icon' => 'fa-money-bill', 'label' => 'คอมมิชชั่น', 'url' => safeRoute('user.commissions'), 'route' => 'user.commissions'],
            [
                'icon' => 'fa-shopping-cart',
                'label' => 'ช๊อปปิ้ง',
                'url' => '#',
                'submenu' => [
                    ['label' => 'ช๊อปสินค้า', 'url' => safeRoute('shop.index'), 'route' => 'shop.index'],
                ]
            ],
            [
                'icon' => 'fa-hotel',
                'label' => 'โรงแรม',
                'url' => '#',
                'submenu' => [
                    ['label' => 'จองโรงแรม', 'url' => safeRoute('hotels.index'), 'route' => 'hotels.index'],
                    ['label' => 'การจองของฉัน', 'url' => safeRoute('hotels.bookings.index'), 'route' => 'hotels.bookings.index'],
                ]
            ],
            ['icon' => 'fa-ticket', 'label' => 'Ticket Support', 'url' => safeRoute('user.tickets.index'), 'route' => 'user.tickets.index'],
            ['icon' => 'fa-qrcode', 'label' => 'สร้าง QR & Barcode', 'url' => safeRoute('qr-barcode.index'), 'route' => 'qr-barcode.index'],
            [
                'icon' => 'fa-wallet',
                'label' => 'กระเป๋าเงิน THB',
                'url' => '#',
                'submenu' => [
                    ['label' => 'กระเป๋าของฉัน', 'url' => safeRoute('user.wallet.index'), 'route' => 'user.wallet.index'],
                    ['label' => 'เติมเงิน', 'url' => safeRoute('user.wallet.deposit'), 'route' => 'user.wallet.deposit'],
                    ['label' => 'ถอนเงิน', 'url' => safeRoute('user.wallet.withdraw'), 'route' => 'user.wallet.withdraw'],
                    ['label' => 'ประวัติธุรกรรม', 'url' => safeRoute('user.wallet.transactions'), 'route' => 'user.wallet.transactions'],
                ]
            ],
            [
                'icon' => 'fa-link',
                'label' => 'TPIX Blockchain',
                'url' => '#',
                'badge' => 'NEW',
                'submenu' => [
                    ['label' => '💰 TPIX Wallet', 'url' => safeRoute('user.tpix.wallet'), 'route' => 'user.tpix.wallet'],
                    ['label' => '🎫 Token Marketplace', 'url' => safeRoute('user.tokens.index'), 'route' => 'user.tokens.index'],
                    ['label' => '🔄 DEX (Swap)', 'url' => safeRoute('user.dex.swap'), 'route' => 'user.dex.swap'],
                    ['label' => '💧 Liquidity Pools', 'url' => safeRoute('user.dex.pools'), 'route' => 'user.dex.pools'],
                    ['label' => '📊 My Positions', 'url' => safeRoute('user.dex.my-positions'), 'route' => 'user.dex.my-positions'],
                    ['label' => '🔒 Staking', 'url' => safeRoute('user.staking.index'), 'route' => 'user.staking.index'],
                ]
            ],
            ['icon' => 'fa-palette', 'label' => 'เปลี่ยนธีม', 'url' => safeRoute('user.themes.index'), 'route' => 'user.themes.index'],
            ['icon' => 'fa-cog', 'label' => 'ตั้งค่า', 'url' => safeRoute('user.settings'), 'route' => 'user.settings'],
        ];
    }

    // Get app information for footer
    $appName = \App\Models\Setting::get('app_name', 'TP-Affiliate');

    // Get version from package.json
    $packageJson = json_decode(file_get_contents(base_path('package.json')), true);
    $appVersion = $packageJson['version'] ?? '2.169.1';

    $copyrightText = \App\Models\Setting::get('copyright_text', '© 2025 All Rights Reserved');

    // Auto-expand submenu if it contains the active route
    $activeSubmenus = [];
    $parentMenuHasActive = []; // Track which parent menus have active children
    $lockedSubmenus = []; // Track which submenus should stay open (contain active items)
    $currentUrl = request()->url(); // Get current full URL
    $currentPath = request()->path(); // Get current path

    foreach ($menuItems as $index => $item) {
        if (isset($item['submenu'])) {
            foreach ($item['submenu'] as $subitem) {
                $isActive = false;

                // Check by route name first
                if (isset($subitem['route']) && $currentRoute === $subitem['route']) {
                    $isActive = true;
                }
                // Fallback: check by URL matching
                elseif (isset($subitem['url']) && $subitem['url'] !== '#') {
                    // Normalize URLs for comparison
                    $submenuUrl = $subitem['url'];
                    if (strpos($submenuUrl, url('/')) === 0) {
                        $submenuUrl = str_replace(url('/'), '', $submenuUrl);
                    }
                    $submenuUrl = ltrim($submenuUrl, '/');
                    $currentPath = ltrim($currentPath, '/');

                    // Check if current path matches submenu URL
                    if ($submenuUrl === $currentPath || url($submenuUrl) === $currentUrl) {
                        $isActive = true;
                    }
                }

                if ($isActive) {
                    $activeSubmenus[] = 'menu-' . $index;
                    $parentMenuHasActive[$index] = true;
                    $lockedSubmenus[] = 'menu-' . $index; // Lock this submenu open
                    break;
                }
            }
        }
    }
    $activeSubmenusJson = json_encode($activeSubmenus);
    $lockedSubmenusJson = json_encode($lockedSubmenus);
@endphp

<ul class="classic-x-menu" x-data="{
    openSubmenus: {{ $activeSubmenusJson }},
    lockedSubmenus: {{ $lockedSubmenusJson }},
    toggleMenu(menuId) {
        // If this submenu is locked (contains active item), don't allow closing
        if (this.lockedSubmenus.includes(menuId)) {
            return;
        }
        // Otherwise, toggle normally
        if (this.openSubmenus.includes(menuId)) {
            this.openSubmenus = this.openSubmenus.filter(id => id !== menuId);
        } else {
            this.openSubmenus = [...this.openSubmenus, menuId];
        }
    }
}">
    @foreach($menuItems as $index => $item)
        <li class="classic-x-menu-item">
            @if(isset($item['submenu']))
                {{-- Menu item with submenu --}}
                <a href="{{ $item['url'] }}"
                   class="classic-x-menu-link {{ isset($parentMenuHasActive[$index]) ? 'has-active-child' : '' }}"
                   @click.prevent="toggleMenu('menu-{{ $index }}')">
                    <span class="classic-x-menu-icon">
                        <i class="fas {{ $item['icon'] }}"></i>
                    </span>
                    <span class="classic-x-menu-text">{{ $item['label'] }}</span>
                    @if(isset($item['badge']))
                        <span class="classic-x-badge">{{ $item['badge'] }}</span>
                    @endif
                    <span class="classic-x-submenu-indicator"
                          :class="{
                              'open': openSubmenus.includes('menu-{{ $index }}'),
                              'locked': lockedSubmenus.includes('menu-{{ $index }}')
                          }">
                        <i class="fas" :class="lockedSubmenus.includes('menu-{{ $index }}') ? 'fa-lock' : 'fa-chevron-down'"></i>
                    </span>
                </a>

                {{-- Submenu --}}
                <ul class="classic-x-submenu" :class="{ 'open': openSubmenus.includes('menu-{{ $index }}') }">
                    @foreach($item['submenu'] as $subitem)
                        <li>
                            <a href="{{ $subitem['url'] }}"
                               class="classic-x-submenu-link @if(isset($subitem['route']) && $currentRoute === $subitem['route']) active @endif">
                                {{ $subitem['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            @else
                {{-- Simple menu item --}}
                <a href="{{ $item['url'] }}"
                   class="classic-x-menu-link @if(isset($item['route']) && $currentRoute === $item['route']) active @endif">
                    <span class="classic-x-menu-icon">
                        <i class="fas {{ $item['icon'] }}"></i>
                    </span>
                    <span class="classic-x-menu-text">{{ $item['label'] }}</span>
                    @if(isset($item['badge']))
                        <span class="classic-x-badge">{{ $item['badge'] }}</span>
                    @endif
                </a>
            @endif
        </li>
    @endforeach
</ul>
