@props(['type' => 'admin'])

@php
    use App\Models\WindowsUiSetting;
    use Illuminate\Support\Facades\Route;

    // Get user and role info
    $user = auth()->user();
    $logo = \App\Models\Setting::get('logo');
    $appName = \App\Models\Setting::get('app_name', 'TP-Affiliate');

    // Safe route helper - returns URL if route doesn't exist
    function safeRoute($routeName, $default = '#') {
        try {
            if (Route::has($routeName)) {
                return route($routeName);
            }
        } catch (\Exception $e) {
            // Fallback: convert route name to URL path
            // e.g., 'admin.users.index' => '/admin/users'
            $path = str_replace('.index', '', $routeName);
            $path = str_replace('.', '/', $path);
            return '/' . $path;
        }
        return $default;
    }

    // Millennium Menu Customization Settings
    $menuWidth = WindowsUiSetting::get('millennium_menu_width', '400');
    $menuWidthUnit = WindowsUiSetting::get('millennium_menu_width_unit', 'px');
    $menuMaxHeight = WindowsUiSetting::get('millennium_menu_max_height', '600');
    $menuMaxHeightUnit = WindowsUiSetting::get('millennium_menu_max_height_unit', 'px');
    $menuPadding = WindowsUiSetting::get('millennium_menu_padding', 16);
    $menuItemSpacing = WindowsUiSetting::get('millennium_menu_item_spacing', 8);
    $menuPosition = WindowsUiSetting::get('millennium_menu_position', 'center');
    $menuOffsetX = WindowsUiSetting::get('millennium_menu_offset_x', 0);
    $menuOffsetY = WindowsUiSetting::get('millennium_menu_offset_y', 10);

    // Build width and height CSS values
    $menuWidthCss = $menuWidth . $menuWidthUnit;
    $menuMaxHeightCss = $menuMaxHeight . $menuMaxHeightUnit;

    // Main Header Style
    $mainIconSize = WindowsUiSetting::get('millennium_menu_main_icon_size', 28);
    $mainFontSize = WindowsUiSetting::get('millennium_menu_main_font_size', 16);
    $mainFontWeight = WindowsUiSetting::get('millennium_menu_main_font_weight', 'bold');
    $mainPaddingX = WindowsUiSetting::get('millennium_menu_main_padding_x', 16);
    $mainPaddingY = WindowsUiSetting::get('millennium_menu_main_padding_y', 12);
    $mainBorderRadius = WindowsUiSetting::get('millennium_menu_main_border_radius', 12);
    $mainBorderWidth = WindowsUiSetting::get('millennium_menu_main_border_width', 2);
    $mainGradientFrom = WindowsUiSetting::get('millennium_menu_main_gradient_from', '#9333ea');
    $mainGradientTo = WindowsUiSetting::get('millennium_menu_main_gradient_to', '#db2777');

    // Submenu Style
    $subFontSize = WindowsUiSetting::get('millennium_menu_sub_font_size', 14);
    $subFontWeight = WindowsUiSetting::get('millennium_menu_sub_font_weight', 'medium');
    $subPaddingX = WindowsUiSetting::get('millennium_menu_sub_padding_x', 12);
    $subPaddingY = WindowsUiSetting::get('millennium_menu_sub_padding_y', 8);
    $subBorderRadius = WindowsUiSetting::get('millennium_menu_sub_border_radius', 8);
    $subIndent = WindowsUiSetting::get('millennium_menu_sub_indent', 32);
    $subBulletSize = WindowsUiSetting::get('millennium_menu_sub_bullet_size', 6);

    // Background & Effects
    $menuBgOpacity = WindowsUiSetting::get('millennium_menu_bg_opacity', 95);
    $menuBlurAmount = WindowsUiSetting::get('millennium_menu_blur_amount', 24);
    $menuShadowSize = WindowsUiSetting::get('millennium_menu_shadow_size', 'xl');
    $menuBorderWidth = WindowsUiSetting::get('millennium_menu_border_width', 1);
    $menuBorderColor = WindowsUiSetting::get('millennium_menu_border_color', 'rgba(255, 255, 255, 0.2)');

    // Animation
    $menuAnimationDuration = WindowsUiSetting::get('millennium_menu_animation_duration', 200);
    $menuAnimationStyle = WindowsUiSetting::get('millennium_menu_animation_style', 'slide');

    // RGB Border
    $menuRgbEnabled = WindowsUiSetting::get('millennium_menu_rgb_enabled', true);
    $menuItemHoverRgb = WindowsUiSetting::get('millennium_menu_item_hover_rgb', true);
    $menuRgbBorderWidth = WindowsUiSetting::get('millennium_menu_rgb_border_width', 2);
    $menuRgbGlowSize = WindowsUiSetting::get('millennium_menu_rgb_glow_size', 10);
    $menuRgbSpeed = WindowsUiSetting::get('millennium_menu_rgb_speed', 5);

    // Search & Footer
    $menuSearchEnabled = WindowsUiSetting::get('millennium_menu_search_enabled', true);
    $menuSearchPlaceholder = WindowsUiSetting::get('millennium_menu_search_placeholder', 'ค้นหาเมนู...');
    $menuFooterEnabled = WindowsUiSetting::get('millennium_menu_footer_enabled', true);
    $menuFooterText = WindowsUiSetting::get('millennium_menu_footer_text', 'Licensed © 2025 TP-Affiliate');

    // Calculate font weight CSS value
    $mainFontWeightValue = match($mainFontWeight) {
        'normal' => '400',
        'medium' => '500',
        'semibold' => '600',
        'bold' => '700',
        default => '700',
    };
    $subFontWeightValue = match($subFontWeight) {
        'normal' => '400',
        'medium' => '500',
        'semibold' => '600',
        'bold' => '700',
        default => '500',
    };

    // Calculate menu position class
    $menuPositionClass = match($menuPosition) {
        'left' => 'left-0',
        'right' => 'right-0',
        default => 'left-1/2 -translate-x-1/2',
    };

    // ========================================
    // HYBRID APPROACH: Try database first, fallback to hard-coded
    // ========================================

    // Try to load from database first (for backward compatibility)
    $menuItemsRaw = WindowsUiSetting::get("windows_start_menu_items_{$type}", null);

    // If database has menu items, use them; otherwise use hard-coded fallback
    if (!empty($menuItemsRaw) && is_array($menuItemsRaw)) {
        // Use database menu items (preserves user customizations)
        $menuItems = collect($menuItemsRaw)->sortBy('order')->values()->toArray();
    } else {
        // Fallback to hard-coded menus
        if ($type === 'admin') {
            $menuItems = [
            ['icon' => '📊', 'label' => 'แดชบอร์ด', 'url' => safeRoute('admin.dashboard'), 'order' => 0],
            [
                'icon' => '👥',
                'label' => 'ผู้ใช้งาน',
                'url' => '#',
                'order' => 1,
                'submenu' => [
                    ['label' => 'รายชื่อผู้ใช้', 'url' => safeRoute('admin.users.index')],
                    ['label' => 'บทบาท (Roles)', 'url' => safeRoute('admin.roles.index')],
                ]
            ],
            ['icon' => '🪪', 'label' => 'ยืนยันตัวตน KYC', 'url' => safeRoute('admin.kyc.index'), 'order' => 2],
            ['icon' => '🎫', 'label' => 'Ticket Support', 'url' => safeRoute('admin.tickets.index'), 'order' => 3],
            [
                'icon' => '🤖',
                'label' => 'AI Bots & ผู้ช่วย',
                'url' => '#',
                'order' => 4,
                'submenu' => [
                    ['label' => 'จัดการ AI Bots', 'url' => safeRoute('admin.ai-bots.index')],
                    ['label' => 'AI Providers', 'url' => safeRoute('admin.ai-providers.index')],
                    ['label' => 'ติดตั้ง AI', 'url' => safeRoute('admin.ai-installation.index')],
                ]
            ],
            [
                'icon' => '🏨',
                'label' => 'จัดการโรงแรม',
                'url' => '#',
                'order' => 5,
                'submenu' => [
                    ['label' => 'โรงแรมทั้งหมด', 'url' => safeRoute('admin.hotels.index')],
                    ['label' => 'การจองทั้งหมด', 'url' => safeRoute('admin.hotels.bookings.index')],
                    ['label' => 'สถิติการจอง', 'url' => safeRoute('admin.hotels.bookings.analytics')],
                    ['label' => 'จัดการรีวิว', 'url' => safeRoute('admin.hotels.reviews.index')],
                    ['label' => 'สิ่งอำนวยความสะดวก', 'url' => safeRoute('admin.hotels.facilities.index')],
                    ['label' => 'โปรโมชั่นพิเศษ', 'url' => safeRoute('admin.hotels.special-offers.index')],
                ]
            ],
            [
                'icon' => '🛒',
                'label' => 'อีคอมเมิร์ซ',
                'url' => '#',
                'order' => 6,
                'submenu' => [
                    ['label' => 'แดชบอร์ด', 'url' => safeRoute('admin.ecommerce.dashboard')],
                    ['label' => 'สินค้าทั้งหมด', 'url' => safeRoute('admin.ecommerce.products.index')],
                    ['label' => 'คำสั่งซื้อ', 'url' => safeRoute('admin.ecommerce.orders.index')],
                    ['label' => 'หมวดหมู่', 'url' => safeRoute('admin.ecommerce.categories.index')],
                    ['label' => 'รีวิวสินค้า', 'url' => safeRoute('admin.ecommerce.reviews.index')],
                ]
            ],
            [
                'icon' => '🏪',
                'label' => 'ระบบ POS',
                'url' => '#',
                'order' => 7,
                'submenu' => [
                    ['label' => 'แดชบอร์ด', 'url' => safeRoute('admin.pos.dashboard')],
                    ['label' => 'อุปกรณ์ POS', 'url' => safeRoute('admin.pos.devices.index')],
                    ['label' => 'ธุรกรรม', 'url' => safeRoute('admin.pos.transactions.index')],
                    ['label' => 'วิเคราะห์ข้อมูล', 'url' => safeRoute('admin.pos.analytics')],
                ]
            ],
            [
                'icon' => '💰',
                'label' => 'กระเป๋าเงิน THB',
                'url' => '#',
                'order' => 8,
                'submenu' => [
                    ['label' => 'กระเป๋าเงินทั้งหมด', 'url' => safeRoute('admin.wallet.index')],
                    ['label' => 'ประวัติธุรกรรม', 'url' => safeRoute('admin.wallet.transactions')],
                    ['label' => 'คำขอถอนเงิน', 'url' => safeRoute('admin.withdrawals.pending')],
                    ['label' => 'ประวัติการถอน', 'url' => safeRoute('admin.withdrawals.index')],
                    ['label' => 'ตั้งค่า Payment', 'url' => safeRoute('admin.payment-gateways.index')],
                ]
            ],
            [
                'icon' => '₿',
                'label' => 'กระเป๋าคริปโต',
                'url' => '#',
                'order' => 9,
                'submenu' => [
                    ['label' => 'แดชบอร์ด', 'url' => safeRoute('admin.crypto.dashboard')],
                    ['label' => 'จัดการ Wallets', 'url' => safeRoute('admin.crypto.wallets')],
                    ['label' => 'ธุรกรรม', 'url' => safeRoute('admin.crypto.transactions')],
                    ['label' => 'คำขอถอน', 'url' => safeRoute('admin.crypto.withdrawals')],
                    ['label' => 'จัดการเหรียญ/สกุลเงิน', 'url' => safeRoute('admin.crypto.currencies')],
                    ['label' => 'ตั้งค่ากระเป๋าเงิน', 'url' => safeRoute('admin.wallet-settings.index')],
                    ['label' => 'ตั้งค่าคริปโต', 'url' => safeRoute('admin.crypto.settings')],
                ]
            ],
            [
                'icon' => '💵',
                'label' => 'คอมมิชชั่น',
                'url' => '#',
                'order' => 10,
                'submenu' => [
                    ['label' => 'รายการทั้งหมด', 'url' => safeRoute('admin.commissions.index')],
                    ['label' => 'รายงานคอมมิชชั่น', 'url' => safeRoute('admin.mlm.commissions.index')],
                ]
            ],
            [
                'icon' => '📧',
                'label' => 'จัดการอีเมล',
                'url' => '#',
                'order' => 11,
                'submenu' => [
                    ['label' => 'เทมเพลต', 'url' => safeRoute('admin.email.templates.index')],
                    ['label' => 'ผู้ให้บริการ', 'url' => safeRoute('admin.email.providers')],
                    ['label' => 'ประวัติการส่ง', 'url' => safeRoute('admin.email.logs')],
                ]
            ],
            [
                'icon' => '📱',
                'label' => 'LINE OA & AI',
                'url' => '#',
                'order' => 12,
                'submenu' => [
                    ['label' => 'ตั้งค่า LINE OA', 'url' => safeRoute('admin.line-oa.index')],
                    ['label' => 'AI Chat Bot', 'url' => safeRoute('admin.line-bot.ai.index')],
                    ['label' => 'Broadcast', 'url' => safeRoute('admin.line-bot.broadcast.index')],
                    ['label' => 'Avatar', 'url' => safeRoute('admin.line-bot.avatars.index')],
                    ['label' => 'Chat Widget', 'url' => safeRoute('admin.line-bot.chat-widget.index')],
                ]
            ],
            [
                'icon' => '🎓',
                'label' => 'Academy System',
                'url' => '#',
                'order' => 13,
                'submenu' => [
                    ['label' => 'คอร์สเรียน', 'url' => safeRoute('admin.academy.courses.index')],
                    ['label' => 'ใบประกาศ', 'url' => safeRoute('admin.academy.certificates.index')],
                    ['label' => 'ตั้งค่า', 'url' => safeRoute('admin.academy.settings.index')],
                ]
            ],
            [
                'icon' => '📚',
                'label' => 'Learning Center',
                'url' => '#',
                'order' => 14,
                'submenu' => [
                    ['label' => 'บทความ', 'url' => safeRoute('admin.articles.index')],
                    ['label' => 'หมวดหมู่', 'url' => safeRoute('admin.categories.index')],
                    ['label' => 'ศูนย์เรียนรู้', 'url' => safeRoute('admin.learning-center.index')],
                ]
            ],
            [
                'icon' => '💎',
                'label' => 'MLM System',
                'url' => '#',
                'order' => 15,
                'submenu' => [
                    ['label' => 'สมาชิก MLM', 'url' => safeRoute('admin.mlm.members.index')],
                    ['label' => 'แผน MLM', 'url' => safeRoute('admin.mlm.plans.index')],
                    ['label' => 'ผังสายงาน', 'url' => safeRoute('admin.mlm.genealogy.index')],
                    ['label' => 'คอมมิชชั่น', 'url' => safeRoute('admin.mlm.commissions.index')],
                    ['label' => 'Product PV', 'url' => safeRoute('admin.mlm.product-pv.index')],
                    ['label' => 'รายงาน', 'url' => safeRoute('admin.mlm.reports.dashboard')],
                    ['label' => 'ตั้งค่า MLM', 'url' => safeRoute('admin.mlm.settings.index')],
                ]
            ],
            [
                'icon' => '📈',
                'label' => 'ระบบการตลาด',
                'url' => '#',
                'order' => 16,
                'submenu' => [
                    ['label' => 'Affiliates', 'url' => safeRoute('admin.affiliates.index')],
                    ['label' => 'โครงสร้างทีม', 'url' => safeRoute('admin.affiliates.tree')],
                    ['label' => 'ระบบรักษายอด', 'url' => safeRoute('admin.retention.index')],
                    ['label' => 'จัดการระดับ Rank', 'url' => safeRoute('admin.ranks.index')],
                    ['label' => 'การเลื่อนระดับ', 'url' => safeRoute('admin.ranks.promotions.index')],
                    ['label' => 'Cashback', 'url' => safeRoute('admin.cashback.index')],
                ]
            ],
            [
                'icon' => '👨‍💼',
                'label' => 'HRM (HR)',
                'url' => '#',
                'order' => 17,
                'submenu' => [
                    ['label' => 'แดชบอร์ด', 'url' => safeRoute('admin.hrm.dashboard')],
                    ['label' => 'พนักงาน', 'url' => safeRoute('admin.hrm.employees.index')],
                    ['label' => 'แผนก', 'url' => safeRoute('admin.hrm.departments.index')],
                    ['label' => 'ตำแหน่ง', 'url' => safeRoute('admin.hrm.positions.index')],
                    ['label' => 'การลา', 'url' => safeRoute('admin.hrm.leave.index')],
                    ['label' => 'เงินเดือน', 'url' => safeRoute('admin.hrm.payroll.index')],
                ]
            ],
            [
                'icon' => '📊',
                'label' => 'บัญชี (Accounting)',
                'url' => '#',
                'order' => 18,
                'submenu' => [
                    ['label' => 'แดชบอร์ด', 'url' => safeRoute('admin.accounting.dashboard')],
                    ['label' => 'ใบแจ้งหนี้', 'url' => safeRoute('admin.accounting.invoices.index')],
                    ['label' => 'ค่าใช้จ่าย', 'url' => safeRoute('admin.accounting.expenses.index')],
                    ['label' => 'ผู้ติดต่อ', 'url' => safeRoute('admin.accounting.contacts.index')],
                    ['label' => 'สินค้า', 'url' => safeRoute('admin.accounting.products.index')],
                    ['label' => 'รายงาน', 'url' => safeRoute('admin.accounting.reports.index')],
                    ['label' => 'FlowAccount', 'url' => safeRoute('admin.accounting.flowaccount.index')],
                ]
            ],
            [
                'icon' => '🔔',
                'label' => 'การแจ้งเตือน',
                'url' => '#',
                'order' => 19,
                'submenu' => [
                    ['label' => 'ส่งการแจ้งเตือน', 'url' => safeRoute('admin.notifications.create')],
                    ['label' => 'ประวัติ', 'url' => safeRoute('admin.notifications.index')],
                    ['label' => 'เทมเพลต', 'url' => safeRoute('admin.notification-templates.index')],
                    ['label' => 'สถิติ', 'url' => safeRoute('admin.notifications.statistics')],
                ]
            ],
            [
                'icon' => '🔒',
                'label' => 'ความปลอดภัย',
                'url' => '#',
                'order' => 20,
                'submenu' => [
                    ['label' => 'ภาพรวม', 'url' => safeRoute('admin.security.index')],
                    ['label' => 'Threat Intelligence', 'url' => safeRoute('admin.security.threat-intelligence')],
                    ['label' => 'Analytics', 'url' => safeRoute('admin.security.analytics')],
                    ['label' => 'OTP Settings', 'url' => safeRoute('admin.otp.settings')],
                ]
            ],
            [
                'icon' => '📄',
                'label' => 'เพจ & SEO',
                'url' => '#',
                'order' => 21,
                'submenu' => [
                    ['label' => 'จัดการเพจ', 'url' => safeRoute('admin.pages.index')],
                    ['label' => 'SEO Settings', 'url' => safeRoute('admin.seo.index')],
                ]
            ],
            [
                'icon' => '📊',
                'label' => 'Analytics',
                'url' => '#',
                'order' => 22,
                'submenu' => [
                    ['label' => 'ภาพรวม', 'url' => safeRoute('admin.analytics.index')],
                ]
            ],
            [
                'icon' => '🎨',
                'label' => 'ธีม & UI',
                'url' => '#',
                'order' => 23,
                'submenu' => [
                    ['label' => 'Theme Builder', 'url' => safeRoute('admin.themes.builder')],
                    ['label' => 'Page Builder', 'url' => safeRoute('admin.page-builder.index')],
                    ['label' => 'Windows UI', 'url' => safeRoute('admin.windows-ui.index')],
                    ['label' => 'Icons', 'url' => safeRoute('admin.icons.index')],
                    ['label' => 'Floating Tools', 'url' => safeRoute('admin.floating-tools.index')],
                ]
            ],
            [
                'icon' => '🌐',
                'label' => 'ภาษา & แปล',
                'url' => '#',
                'order' => 24,
                'submenu' => [
                    ['label' => 'การแปล', 'url' => safeRoute('admin.translations.index')],
                    ['label' => 'ตั้งค่าภาษา', 'url' => safeRoute('admin.settings.languages')],
                ]
            ],
            [
                'icon' => '⚙️',
                'label' => 'ตั้งค่าระบบ',
                'url' => '#',
                'order' => 25,
                'submenu' => [
                    ['label' => 'ตั้งค่าทั่วไป', 'url' => safeRoute('admin.settings.index')],
                    ['label' => 'ตั้งค่า Mobile App', 'url' => safeRoute('admin.app-management.settings.index')],
                    ['label' => 'ตั้งค่า OCR', 'url' => safeRoute('admin.settings.ocr')],
                    ['label' => 'ตั้งค่า 2FA', 'url' => safeRoute('admin.two-factor.settings')],
                ]
            ],
        ];
    } elseif ($type === 'seller') {
        $menuItems = [
            ['icon' => '📊', 'label' => 'แดชบอร์ด', 'url' => safeRoute('seller.dashboard'), 'order' => 0],
            [
                'icon' => '📦',
                'label' => 'สินค้า',
                'url' => '#',
                'order' => 1,
                'submenu' => [
                    ['label' => 'รายการสินค้า', 'url' => safeRoute('seller.products.index')],
                    ['label' => 'เพิ่มสินค้า', 'url' => safeRoute('seller.products.create')],
                ]
            ],
            [
                'icon' => '🏪',
                'label' => 'ระบบ POS',
                'url' => '#',
                'order' => 2,
                'submenu' => [
                    ['label' => 'ขายสินค้า', 'url' => safeRoute('seller.pos.terminal')],
                    ['label' => 'รายการขาย', 'url' => safeRoute('seller.pos.transactions')],
                    ['label' => 'Session', 'url' => safeRoute('seller.pos.sessions')],
                    ['label' => 'ตั้งค่า POS', 'url' => safeRoute('seller.pos.settings')],
                ]
            ],
            [
                'icon' => '🛒',
                'label' => 'ยอดขาย',
                'url' => '#',
                'order' => 3,
                'submenu' => [
                    ['label' => 'คำสั่งซื้อ', 'url' => safeRoute('seller.orders.index')],
                    ['label' => 'รายงานยอดขาย', 'url' => safeRoute('seller.reports.sales')],
                ]
            ],
            [
                'icon' => '💰',
                'label' => 'กระเป๋าเงิน',
                'url' => '#',
                'order' => 4,
                'submenu' => [
                    ['label' => 'กระเป๋าของฉัน', 'url' => safeRoute('seller.wallet.index')],
                    ['label' => 'ถอนเงิน', 'url' => safeRoute('seller.wallet.withdraw')],
                ]
            ],
            ['icon' => '💵', 'label' => 'คอมมิชชั่น', 'url' => safeRoute('seller.commissions'), 'order' => 5],
            [
                'icon' => '📈',
                'label' => 'วิเคราะห์',
                'url' => '#',
                'order' => 6,
                'submenu' => [
                    ['label' => '📊 Dashboard', 'url' => safeRoute('seller.analytics.index')],
                    ['label' => '🤖 AI Insights', 'url' => safeRoute('seller.analytics.ai-insights')],
                    ['label' => '👥 Customer Segments', 'url' => safeRoute('seller.analytics.segmentation')],
                    ['label' => '📈 Cohort Analysis', 'url' => safeRoute('seller.analytics.cohort')],
                    ['label' => '🏆 Products Ranking', 'url' => safeRoute('seller.analytics.products')],
                    ['label' => '🖥️ System Monitoring', 'url' => safeRoute('seller.analytics.system-monitoring')],
                    ['label' => '⚙️ Settings', 'url' => safeRoute('seller.analytics.settings')],
                ]
            ],
            ['icon' => '⚙️', 'label' => 'ตั้งค่าร้าน', 'url' => safeRoute('seller.settings'), 'order' => 7],
            ['icon' => '👤', 'label' => 'โปรไฟล์', 'url' => safeRoute('seller.profile'), 'order' => 8],
        ];
    } else { // user
        $menuItems = [
            ['icon' => '📊', 'label' => 'แดชบอร์ด', 'url' => safeRoute('user.dashboard'), 'order' => 0],
            ['icon' => '👤', 'label' => 'โปรไฟล์', 'url' => safeRoute('user.profile'), 'order' => 1],
            ['icon' => '🪪', 'label' => 'ยืนยันตัวตน KYC', 'url' => safeRoute('user.kyc.index'), 'order' => 2],
            ['icon' => '💰', 'label' => 'คอมมิชชั่น', 'url' => safeRoute('user.commissions'), 'order' => 3],
            [
                'icon' => '🛒',
                'label' => 'ช๊อปปิ้ง',
                'url' => '#',
                'order' => 4,
                'submenu' => [
                    ['label' => 'ช๊อปสินค้า', 'url' => safeRoute('shop.index')],
                ]
            ],
            [
                'icon' => '🏨',
                'label' => 'โรงแรม',
                'url' => '#',
                'order' => 5,
                'submenu' => [
                    ['label' => 'จองโรงแรม', 'url' => safeRoute('hotels.index')],
                    ['label' => 'การจองของฉัน', 'url' => safeRoute('hotels.bookings.index')],
                ]
            ],
            ['icon' => '🎫', 'label' => 'Ticket Support', 'url' => safeRoute('user.tickets.index'), 'order' => 6],
            [
                'icon' => '💳',
                'label' => 'กระเป๋าเงิน THB',
                'url' => '#',
                'order' => 7,
                'submenu' => [
                    ['label' => 'กระเป๋าของฉัน', 'url' => safeRoute('user.wallet.index')],
                    ['label' => 'ถอนเงิน', 'url' => safeRoute('user.wallet.withdraw')],
                ]
            ],
            [
                'icon' => '₿',
                'label' => 'กระเป๋าคริปโต',
                'url' => '#',
                'order' => 8,
                'submenu' => [
                    ['label' => 'กระเป๋าคริปโต', 'url' => safeRoute('user.crypto-wallet.index')],
                ]
            ],
            [
                'icon' => '📈',
                'label' => 'การลงทุน ROI',
                'url' => '#',
                'order' => 9,
                'submenu' => [
                    ['label' => 'แดชบอร์ด', 'url' => safeRoute('user.investments.index')],
                    ['label' => 'แผนการลงทุน', 'url' => safeRoute('user.investments.plans')],
                ]
            ],
            [
                'icon' => '🤖',
                'label' => 'AI Bots',
                'url' => '#',
                'order' => 10,
                'submenu' => [
                    ['label' => 'ตลาดบอท', 'url' => safeRoute('marketplace.index')],
                ]
            ],
            [
                'icon' => '👥',
                'label' => 'ทีมงาน',
                'url' => '#',
                'order' => 11,
                'submenu' => [
                    ['label' => 'ผู้แนะนำ', 'url' => safeRoute('user.referrals')],
                    ['label' => 'ผังสายงาน', 'url' => safeRoute('user.organization')],
                ]
            ],
            [
                'icon' => '💖',
                'label' => 'รักษายอด',
                'url' => '#',
                'order' => 12,
                'submenu' => [
                    ['label' => 'สถานะพลังชีวิต', 'url' => safeRoute('user.retention.index')],
                ]
            ],
            [
                'icon' => '🎯',
                'label' => 'เครื่องมือการตลาด',
                'url' => '#',
                'order' => 13,
                'submenu' => [
                    ['label' => 'จำลองรายได้', 'url' => safeRoute('user.mlm.income-simulator')],
                ]
            ],
            ['icon' => '🎨', 'label' => 'ตั้งค่าธีม', 'url' => safeRoute('user.themes.index'), 'order' => 14],
            ];
        }
    }
@endphp

<!-- Millennium Start Menu Overlay -->
<div
    x-show="startMenuOpen"
    @click="startMenuOpen = false"
    x-transition:enter="transition-opacity ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition-opacity ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[60]"
    style="display: none;">
</div>

<!-- Millennium Start Menu Panel -->
<div
    x-show="startMenuOpen"
    @click.away="startMenuOpen = false"
    x-data="{ openSubmenus: {} }"
    x-transition:enter="transition ease-out duration-{{ $menuAnimationDuration }}"
    x-transition:enter-start="opacity-0 {{ $menuAnimationStyle === 'slide' ? '-translate-x-full' : '' }} {{ $menuAnimationStyle === 'scale' ? 'scale-95' : '' }}"
    x-transition:enter-end="opacity-100 translate-x-0 scale-100"
    x-transition:leave="transition ease-in duration-{{ $menuAnimationDuration * 0.75 }}"
    x-transition:leave-start="opacity-100 translate-x-0 scale-100"
    x-transition:leave-end="opacity-0 {{ $menuAnimationStyle === 'slide' ? '-translate-x-full' : '' }} {{ $menuAnimationStyle === 'scale' ? 'scale-95' : '' }}"
    class="fixed {{ $menuPositionClass }} top-0 bottom-0 z-[70]"
    style="width: {{ $menuWidthCss }}; max-height: {{ $menuMaxHeightCss }}; display: none; margin-left: {{ $menuPosition === 'left' ? $menuOffsetX : 0 }}px; margin-right: {{ $menuPosition === 'right' ? $menuOffsetX : 0 }}px; margin-top: {{ $menuOffsetY }}px;">

    <!-- Main Panel with 3D Effect -->
    <div class="relative h-full bg-gradient-to-br from-slate-900 via-purple-900/40 to-blue-900/40 overflow-hidden shadow-{{ $menuShadowSize }}"
         style="opacity: {{ $menuBgOpacity / 100 }}; backdrop-filter: blur({{ $menuBlurAmount }}px); border-right-width: {{ $menuBorderWidth }}px; border-color: {{ $menuBorderColor }};">

        @if($menuRgbEnabled)
        <!-- RGB Border Effect -->
        <div class="absolute inset-0 millennium-menu-rgb pointer-events-none" style="border-width: {{ $menuRgbBorderWidth }}px;"></div>
        @endif

        <!-- Animated Background -->
        <div class="absolute inset-0 opacity-5">
            <div class="absolute inset-0 millennium-grid"></div>
        </div>

        <!-- Content Container -->
        <div class="relative h-full flex flex-col" style="padding: {{ $menuPadding }}px;">

            <!-- Header Section -->
            <div class="flex-shrink-0 mb-6">
                <!-- Logo & App Name -->
                <div class="flex items-center gap-3 mb-4">
                    @if($logo)
                        <img src="{{ Storage::url($logo) }}" alt="{{ $appName }}" class="h-10 w-10 rounded-lg object-cover">
                    @else
                        <div class="h-10 w-10 rounded-lg bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white text-xl font-bold">
                            {{ substr($appName, 0, 1) }}
                        </div>
                    @endif
                    <div>
                        <div class="text-white font-bold text-lg">{{ $appName }}</div>
                        <div class="text-white/60 text-xs">{{ ucfirst($type) }} Dashboard</div>
                    </div>
                </div>

                <!-- Search Bar -->
                @if($menuSearchEnabled)
                <div class="relative">
                    <input
                        type="text"
                        placeholder="{{ $menuSearchPlaceholder }}"
                        class="w-full px-4 py-2 pl-10 bg-white/10 border border-white/20 rounded-lg text-white placeholder-white/40 focus:outline-none focus:ring-2 focus:ring-purple-500/50 focus:bg-white/20 transition-all"
                        x-model="searchQuery">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-white/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                @endif
            </div>

            <!-- Menu Items - Scrollable Area -->
            <div class="flex-1 overflow-y-auto pr-2 millennium-scrollbar" style="margin: -{{ $menuItemSpacing }}px; padding: {{ $menuItemSpacing }}px;">
                <div class="space-y-{{ $menuItemSpacing }}">
                    @foreach($menuItems as $index => $item)
                        @if(!empty($item['submenu']))
                            <!-- Menu Item with Submenu -->
                            <div x-data="{ open: false }">
                                <!-- Main Menu Item (Clickable to toggle submenu) -->
                                <button
                                    type="button"
                                    @click="open = !open"
                                    class="w-full group flex items-center justify-between gap-3 bg-gradient-to-r hover:opacity-80 transition-all duration-300 transform hover:scale-[1.02] shadow-lg hover:shadow-xl {{ $menuItemHoverRgb ? 'millennium-menu-item-hover-rgb' : '' }}"
                                    style="padding: {{ $mainPaddingY }}px {{ $mainPaddingX }}px; border-radius: {{ $mainBorderRadius }}px; border-width: {{ $mainBorderWidth }}px; background: linear-gradient(90deg, {{ $mainGradientFrom }}66 0%, {{ $mainGradientTo }}66 100%); border-color: {{ $mainGradientFrom }}66;">

                                    <div class="flex items-center gap-3">
                                        <!-- Icon with 3D Effect -->
                                        <span class="group-hover:scale-110 transition-transform duration-300 drop-shadow-lg" style="font-size: {{ $mainIconSize }}px;">
                                            {!! $item['icon'] !!}
                                        </span>

                                        <!-- Label -->
                                        <span class="text-white font-bold tracking-wide drop-shadow-md" style="font-size: {{ $mainFontSize }}px; font-weight: {{ $mainFontWeightValue }};">
                                            {{ $item['label'] }}
                                        </span>
                                    </div>

                                    <!-- Arrow Icon -->
                                    <svg class="w-5 h-5 text-white/70 transition-transform duration-300" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>

                                <!-- Submenu Items -->
                                <div x-show="open"
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0 -translate-y-2"
                                    x-transition:enter-end="opacity-100 translate-y-0"
                                    x-transition:leave="transition ease-in duration-150"
                                    x-transition:leave-start="opacity-100 translate-y-0"
                                    x-transition:leave-end="opacity-0 -translate-y-2"
                                    style="margin-top: {{ $menuItemSpacing }}px; margin-left: {{ $subIndent }}px; display: flex; flex-direction: column; gap: {{ $menuItemSpacing / 2 }}px; padding-bottom: {{ $menuItemSpacing }}px; display: none;">
                                    @foreach($item['submenu'] as $subitem)
                                        <a
                                            href="{{ $subitem['url'] }}"
                                            @click.stop
                                            class="flex items-center gap-2.5 bg-white/5 hover:bg-blue-500/20 border border-white/10 hover:border-blue-400/40 transition-all duration-200 transform hover:translate-x-1 group"
                                            style="padding: {{ $subPaddingY }}px {{ $subPaddingX }}px; border-radius: {{ $subBorderRadius }}px;">

                                            <!-- Bullet Point -->
                                            <span class="rounded-full bg-blue-400/60 group-hover:bg-blue-300 group-hover:scale-125 transition-all duration-200" style="width: {{ $subBulletSize }}px; height: {{ $subBulletSize }}px;"></span>

                                            <!-- Submenu Label -->
                                            <span class="text-white/80 group-hover:text-white transition-colors duration-200 flex-1" style="font-size: {{ $subFontSize }}px; font-weight: {{ $subFontWeightValue }};">
                                                {{ $subitem['label'] }}
                                            </span>

                                            <!-- Small Arrow -->
                                            <svg class="w-3.5 h-3.5 text-white/30 group-hover:text-blue-300 group-hover:translate-x-0.5 transition-all duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
                                            </svg>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <!-- Regular Menu Item without Submenu - MAIN STYLE -->
                            <a
                                href="{{ $item['url'] }}"
                                @click.stop
                                class="group flex items-center gap-3 bg-gradient-to-r hover:opacity-80 transition-all duration-300 transform hover:scale-[1.02] shadow-lg hover:shadow-xl {{ $menuItemHoverRgb ? 'millennium-menu-item-hover-rgb' : '' }}"
                                style="padding: {{ $mainPaddingY }}px {{ $mainPaddingX }}px; border-radius: {{ $mainBorderRadius }}px; border-width: {{ $mainBorderWidth }}px; background: linear-gradient(90deg, {{ $mainGradientFrom }}66 0%, {{ $mainGradientTo }}66 100%); border-color: {{ $mainGradientFrom }}66;">

                                <!-- Icon with 3D Effect -->
                                <span class="group-hover:scale-110 transition-transform duration-300 drop-shadow-lg" style="font-size: {{ $mainIconSize }}px;">
                                    {!! $item['icon'] !!}
                                </span>

                                <!-- Label -->
                                <span class="text-white font-bold tracking-wide drop-shadow-md flex-1" style="font-size: {{ $mainFontSize }}px; font-weight: {{ $mainFontWeightValue }};">
                                    {{ $item['label'] }}
                                </span>

                                <!-- Arrow Icon -->
                                <svg class="w-5 h-5 text-white/50 group-hover:text-white group-hover:translate-x-1 transition-all duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>

            <!-- Footer Section -->
            @if($menuFooterEnabled)
            <div class="flex-shrink-0 mt-4 pt-4 border-t border-white/10">
                <div class="text-white/40 text-xs text-center">
                    {{ $menuFooterText }}
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<style>
    /* Custom Scrollbar */
    .millennium-scrollbar::-webkit-scrollbar {
        width: 6px;
    }

    .millennium-scrollbar::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.05);
        border-radius: 10px;
    }

    .millennium-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 10px;
    }

    .millennium-scrollbar::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.3);
    }

    /* RGB Border Animation for Menu */
    @keyframes millenniumMenuRgb {
        0%, 100% {
            border-image: linear-gradient(90deg, #FF0080, #00F0FF, #7F00FF, #FFD700, #FF0080) 1;
            filter: drop-shadow(0 0 {{ $menuRgbGlowSize }}px #FF0080);
        }
        25% {
            border-image: linear-gradient(90deg, #00F0FF, #7F00FF, #FFD700, #FF0080, #00F0FF) 1;
            filter: drop-shadow(0 0 {{ $menuRgbGlowSize }}px #00F0FF);
        }
        50% {
            border-image: linear-gradient(90deg, #7F00FF, #FFD700, #FF0080, #00F0FF, #7F00FF) 1;
            filter: drop-shadow(0 0 {{ $menuRgbGlowSize }}px #7F00FF);
        }
        75% {
            border-image: linear-gradient(90deg, #FFD700, #FF0080, #00F0FF, #7F00FF, #FFD700) 1;
            filter: drop-shadow(0 0 {{ $menuRgbGlowSize }}px #FFD700);
        }
    }

    .millennium-menu-rgb {
        border-style: solid;
        animation: millenniumMenuRgb {{ $menuRgbSpeed }}s linear infinite;
    }

    /* RGB Hover Effect for Menu Items */
    @keyframes menuItemRgbHover {
        0%, 100% {
            box-shadow: 0 0 20px rgba(255, 0, 128, 0.5), inset 0 0 20px rgba(255, 0, 128, 0.1);
        }
        33% {
            box-shadow: 0 0 20px rgba(0, 240, 255, 0.5), inset 0 0 20px rgba(0, 240, 255, 0.1);
        }
        66% {
            box-shadow: 0 0 20px rgba(127, 0, 255, 0.5), inset 0 0 20px rgba(127, 0, 255, 0.1);
        }
    }

    .millennium-menu-item-hover-rgb:hover {
        animation: menuItemRgbHover 2s ease-in-out infinite;
    }

    /* Grid Background Animation */
    @keyframes millenniumGrid {
        0% {
            background-position: 0 0;
        }
        100% {
            background-position: 40px 40px;
        }
    }

    .millennium-grid {
        background-image:
            linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
        background-size: 40px 40px;
        animation: millenniumGrid 20s linear infinite;
    }
</style>
