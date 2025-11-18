<?php

/**
 * Menu Configuration - V3 Menu System
 *
 * Single Source of Truth สำหรับเมนูทั้งหมดในระบบ
 *
 * ✅ Config-based approach (ไม่ใช้ database)
 * ✅ Theme-agnostic (รองรับทุก theme)
 * ✅ Feature-ready (รองรับ Feature Providers)
 * ✅ Permission-aware (กรองตาม permissions)
 *
 * @version 3.0.0
 * @since 2025-11-15
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Admin Menu Items
    |--------------------------------------------------------------------------
    |
    | เมนูสำหรับ Admin Dashboard
    | แสดงเมื่อ user มี role = 'admin'
    |
    */

    'admin' => [
        [
            'id' => 'dashboard',
            'label' => 'แดชบอร์ด',
            'icon' => '📊',
            'route' => 'admin.dashboard',
            'order' => 0,
            'permissions' => [],
        ],

        [
            'id' => 'users',
            'label' => 'ผู้ใช้งาน',
            'icon' => '👥',
            'route' => null,
            'order' => 1,
            'permissions' => [],
            'submenu' => [
                ['label' => 'รายชื่อผู้ใช้', 'route' => 'admin.users.index'],
                ['label' => 'บทบาท (Roles)', 'route' => 'admin.roles.index'],
            ],
        ],

        [
            'id' => 'kyc',
            'label' => 'ยืนยันตัวตน KYC',
            'icon' => '🪪',
            'route' => 'admin.kyc.index',
            'order' => 2,
            'permissions' => [],
        ],

        [
            'id' => 'tickets',
            'label' => 'Ticket Support',
            'icon' => '🎫',
            'route' => 'admin.tickets.index',
            'order' => 3,
            'permissions' => [],
        ],

        [
            'id' => 'ai-bots',
            'label' => 'AI Bots & ผู้ช่วย',
            'icon' => '🤖',
            'route' => null,
            'order' => 4,
            'permissions' => [],
            'submenu' => [
                ['label' => 'จัดการ AI Bots', 'route' => 'admin.ai-bots.index'],
                ['label' => 'AI Providers', 'route' => 'admin.ai-providers.index'],
                ['label' => 'ติดตั้ง AI', 'route' => 'admin.ai-installation.index'],
                ['label' => 'AI Monitoring', 'route' => 'admin.ai-monitoring.index'],
                ['label' => 'Knowledge Bases', 'route' => 'admin.knowledge-bases.index'],
            ],
        ],

        [
            'id' => 'chatbot',
            'label' => 'ระบบบอทแชท',
            'icon' => '💬',
            'route' => null,
            'order' => 4.3,
            'permissions' => [],
            'submenu' => [
                ['label' => 'บอทของฉัน', 'route' => 'chatbot.index'],
                ['label' => 'สร้างบอทใหม่', 'route' => 'chatbot.create'],
                ['label' => 'ตลาดบอท', 'route' => 'chatbot.marketplace.index'],
                ['label' => 'บอทที่เช่า', 'route' => 'chatbot.marketplace.my-rentals'],
                ['label' => 'รายได้ของฉัน', 'route' => 'chatbot.marketplace.my-earnings'],
            ],
        ],

        [
            'id' => 'smart-sliders',
            'label' => 'Smart Slider Pro',
            'icon' => '🎨',
            'route' => null,
            'order' => 4.5,
            'permissions' => [],
            'badge' => 'NEW',
            'badge_color' => 'bg-gradient-to-r from-blue-500 to-indigo-500',
            'submenu' => [
                ['label' => '🎯 Dashboard', 'route' => 'admin.smart-sliders.index'],
                ['label' => '➕ สร้าง Slider ใหม่', 'route' => 'admin.smart-sliders.create'],
                ['label' => '📚 Template Gallery', 'route' => 'admin.smart-sliders.index'],
                ['label' => '📊 Analytics', 'route' => 'admin.smart-sliders.index'],
                ['label' => '📥 Import/Export', 'route' => 'admin.smart-sliders.index'],
            ],
        ],

        [
            'id' => 'hotels',
            'label' => 'จัดการโรงแรม',
            'icon' => '🏨',
            'route' => null,
            'order' => 5,
            'permissions' => [],
            'submenu' => [
                ['label' => 'โรงแรมทั้งหมด', 'route' => 'admin.hotels.index'],
                ['label' => 'การจองทั้งหมด', 'route' => 'admin.hotels.bookings.index'],
                ['label' => 'สถิติการจอง', 'route' => 'admin.hotels.bookings.analytics'],
                ['label' => 'จัดการรีวิว', 'route' => 'admin.hotels.reviews.index'],
                ['label' => 'เจ้าของโรงแรม', 'route' => 'admin.hotel-owners.index'],
                ['label' => 'สิ่งอำนวยความสะดวก', 'route' => 'admin.hotels.facilities.index'],
                ['label' => 'โปรโมชั่นพิเศษ', 'route' => 'admin.hotels.special-offers.index'],
            ],
        ],

        [
            'id' => 'ecommerce',
            'label' => 'อีคอมเมิร์ซ',
            'icon' => '🛒',
            'route' => null,
            'order' => 6,
            'permissions' => [],
            'submenu' => [
                ['label' => 'แดชบอร์ด', 'route' => 'admin.ecommerce.dashboard'],
                ['label' => 'สินค้าทั้งหมด', 'route' => 'admin.ecommerce.products.index'],
                ['label' => 'คำสั่งซื้อ', 'route' => 'admin.ecommerce.orders.index'],
                ['label' => 'หมวดหมู่', 'route' => 'admin.ecommerce.categories.index'],
                ['label' => 'รีวิวสินค้า', 'route' => 'admin.ecommerce.reviews.index'],
            ],
        ],

        [
            'id' => 'pos',
            'label' => 'ระบบ POS',
            'icon' => '🏪',
            'route' => null,
            'order' => 7,
            'permissions' => [],
            'submenu' => [
                ['label' => 'แดชบอร์ด', 'route' => 'admin.pos.dashboard'],
                ['label' => 'อุปกรณ์ POS', 'route' => 'admin.pos.devices.index'],
                ['label' => 'ธุรกรรม', 'route' => 'admin.pos.transactions.index'],
                ['label' => 'วิเคราะห์ข้อมูล', 'route' => 'admin.pos.analytics'],
            ],
        ],

        [
            'id' => 'wallet-thb',
            'label' => 'กระเป๋าเงิน THB',
            'icon' => '💰',
            'route' => null,
            'order' => 8,
            'permissions' => [],
            'submenu' => [
                ['label' => 'กระเป๋าเงินทั้งหมด', 'route' => 'admin.wallet.index'],
                ['label' => 'ประวัติธุรกรรม', 'route' => 'admin.wallet.transactions'],
                ['label' => 'คำขอถอนเงิน', 'route' => 'admin.withdrawals.pending'],
                ['label' => 'ประวัติการถอน', 'route' => 'admin.withdrawals.index'],
                ['label' => 'ตั้งค่า Payment Gateway', 'route' => 'admin.payment-gateways.index'],
                ['label' => 'ตั้งค่ากระเป๋าเงิน', 'route' => 'admin.wallet-settings.index'],
                ['label' => 'ตั้งค่า Cashback', 'route' => 'admin.cashback.index'],
            ],
        ],

        [
            'id' => 'wallet-crypto',
            'label' => 'กระเป๋าคริปโต',
            'icon' => '₿',
            'route' => null,
            'order' => 9,
            'permissions' => [],
            'submenu' => [
                ['label' => 'แดชบอร์ด', 'route' => 'admin.crypto.dashboard'],
                ['label' => 'จัดการ Wallets', 'route' => 'admin.crypto.wallets'],
                ['label' => 'ธุรกรรม', 'route' => 'admin.crypto.transactions'],
                ['label' => 'คำขอถอน', 'route' => 'admin.crypto.withdrawals'],
                ['label' => 'จัดการเหรียญ/สกุลเงิน', 'route' => 'admin.crypto.currencies'],
                ['label' => 'ตั้งค่ากระเป๋าเงิน', 'route' => 'admin.wallet-settings.index'],
                ['label' => 'ตั้งค่าคริปโต', 'route' => 'admin.crypto.settings'],
            ],
        ],

        [
            'id' => 'commissions',
            'label' => 'คอมมิชชั่น MLM',
            'icon' => '💵',
            'route' => 'admin.mlm.commissions.index',
            'order' => 10,
            'permissions' => [],
        ],

        [
            'id' => 'email',
            'label' => 'จัดการอีเมล',
            'icon' => '📧',
            'route' => null,
            'order' => 11,
            'permissions' => [],
            'submenu' => [
                ['label' => 'เทมเพลต', 'route' => 'admin.email.templates.index'],
                ['label' => 'ผู้ให้บริการ', 'route' => 'admin.email.providers'],
                ['label' => 'ประวัติการส่ง', 'route' => 'admin.email.logs'],
            ],
        ],

        [
            'id' => 'line-oa',
            'label' => 'LINE OA & AI',
            'icon' => '📱',
            'route' => null,
            'order' => 12,
            'permissions' => [],
            'submenu' => [
                ['label' => 'ตั้งค่า LINE OA', 'route' => 'admin.line-oa.index'],
                ['label' => '📊 LINE Analytics', 'route' => 'admin.line-oa.analytics'],
                ['label' => 'AI Chat Bot', 'route' => 'admin.line-bot.ai.index'],
                ['label' => '🤖 Hybrid Bot Keywords', 'route' => 'admin.line-bot.keywords.index', 'badge' => 'NEW', 'badge_color' => 'bg-gradient-to-r from-purple-500 to-pink-500'],
                ['label' => '📊 Activity Logs', 'route' => 'admin.line-bot.keywords.activity.index'],
                ['label' => '⭐ Performance Dashboard', 'route' => 'admin.line-bot.keywords.performance.index'],
                ['label' => '💡 Keyword Suggestions', 'route' => 'admin.line-bot.keywords.suggestions.index'],
                ['label' => '🧪 A/B Testing', 'route' => 'admin.line-bot.keywords.ab-tests.index'],
                ['label' => '😊 Sentiment Analysis', 'route' => 'admin.line-bot.keywords.sentiment-analysis.index'],
                ['label' => '🧠 NLP Analysis', 'route' => 'admin.line-bot.keywords.nlp-analysis.index', 'badge' => 'NEW', 'badge_color' => 'bg-gradient-to-r from-cyan-500 to-blue-500'],
                ['label' => 'Broadcast', 'route' => 'admin.line-bot.broadcast.index'],
                ['label' => 'Avatar', 'route' => 'admin.line-bot.avatars.index'],
                ['label' => 'Chat Widget', 'route' => 'admin.line-bot.chat-widget.index'],
            ],
        ],

        [
            'id' => 'academy',
            'label' => 'Academy System',
            'icon' => '🎓',
            'route' => null,
            'order' => 13,
            'permissions' => [],
            'submenu' => [
                ['label' => 'คอร์สเรียน', 'route' => 'admin.academy.courses.index'],
                ['label' => 'จัดการแบบทดสอบ', 'route' => 'admin.quiz-management.index'],
                ['label' => 'ใบประกาศนักเรียน', 'route' => 'admin.certificates.index'],
                ['label' => 'ใบประกาศระบบ', 'route' => 'admin.academy.certificates.index'],
                ['label' => 'แดชบอร์ดอาจารย์', 'route' => 'admin.instructor.dashboard'],
                ['label' => 'ตั้งค่า', 'route' => 'admin.academy.settings.index'],
            ],
        ],

        [
            'id' => 'learning-center',
            'label' => 'Learning Center',
            'icon' => '📚',
            'route' => null,
            'order' => 14,
            'permissions' => [],
            'submenu' => [
                ['label' => 'บทความ', 'route' => 'admin.articles.index'],
                ['label' => 'หมวดหมู่', 'route' => 'admin.categories.index'],
                ['label' => 'ศูนย์เรียนรู้', 'route' => 'admin.learning-center.index'],
            ],
        ],

        [
            'id' => 'mlm',
            'label' => 'MLM System',
            'icon' => '💎',
            'route' => null,
            'order' => 15,
            'permissions' => [],
            'submenu' => [
                ['label' => 'สมาชิก MLM', 'route' => 'admin.mlm.members.index'],
                ['label' => 'แผน MLM', 'route' => 'admin.mlm.plans.index'],
                ['label' => 'ผังสายงาน', 'route' => 'admin.mlm.genealogy.index'],
                ['label' => 'คอมมิชชั่น', 'route' => 'admin.mlm.commissions.index'],
                ['label' => 'ผู้มุ่งหวัง (Prospects)', 'route' => 'admin.mlm-prospects.index'],
                ['label' => 'Product PV', 'route' => 'admin.mlm.product-pv.index'],
                ['label' => 'รายงาน', 'route' => 'admin.mlm.reports.dashboard'],
                ['label' => 'ตั้งค่า MLM', 'route' => 'admin.mlm.settings.index'],
            ],
        ],

        [
            'id' => 'marketing',
            'label' => 'ระบบการตลาด',
            'icon' => '📈',
            'route' => null,
            'order' => 16,
            'permissions' => [],
            'submenu' => [
                ['label' => 'ระบบรักษายอด', 'route' => 'admin.retention.index'],
                ['label' => 'จัดการระดับ Rank', 'route' => 'admin.ranks.index'],
                ['label' => 'การเลื่อนระดับ', 'route' => 'admin.ranks.promotions.index'],
                ['label' => 'Cashback', 'route' => 'admin.cashback.index'],
            ],
        ],

        [
            'id' => 'hrm',
            'label' => 'HRM (HR)',
            'icon' => '👨‍💼',
            'route' => null,
            'order' => 17,
            'permissions' => [],
            'submenu' => [
                ['label' => 'แดชบอร์ด', 'route' => 'admin.hrm.dashboard'],
                ['label' => 'พนักงาน', 'route' => 'admin.hrm.employees.index'],
                ['label' => 'แผนก', 'route' => 'admin.hrm.departments.index'],
                ['label' => 'ตำแหน่ง', 'route' => 'admin.hrm.positions.index'],
                ['label' => 'การลา', 'route' => 'admin.hrm.leave.index'],
                ['label' => 'เงินเดือน', 'route' => 'admin.hrm.payroll.index'],
            ],
        ],

        [
            'id' => 'accounting',
            'label' => 'บัญชี (Accounting)',
            'icon' => '📊',
            'route' => null,
            'order' => 18,
            'permissions' => [],
            'submenu' => [
                ['label' => 'แดชบอร์ด', 'route' => 'admin.accounting.dashboard'],
                ['label' => 'ใบแจ้งหนี้', 'route' => 'admin.accounting.invoices.index'],
                ['label' => 'ค่าใช้จ่าย', 'route' => 'admin.accounting.expenses.index'],
                ['label' => 'ผู้ติดต่อ', 'route' => 'admin.accounting.contacts.index'],
                ['label' => 'สินค้า', 'route' => 'admin.accounting.products.index'],
                ['label' => 'รายงาน', 'route' => 'admin.accounting.reports.index'],
                ['label' => 'FlowAccount', 'route' => 'admin.accounting.flowaccount.index'],
            ],
        ],

        [
            'id' => 'notifications',
            'label' => 'การแจ้งเตือน',
            'icon' => '🔔',
            'route' => null,
            'order' => 19,
            'permissions' => [],
            'submenu' => [
                ['label' => 'ส่งการแจ้งเตือน', 'route' => 'admin.notifications.create'],
                ['label' => 'ประวัติ', 'route' => 'admin.notifications.index'],
                ['label' => 'เทมเพลต', 'route' => 'admin.notification-templates.index'],
                ['label' => 'สถิติ', 'route' => 'admin.notifications.statistics'],
            ],
        ],

        [
            'id' => 'security',
            'label' => 'ความปลอดภัย',
            'icon' => '🔒',
            'route' => null,
            'order' => 20,
            'permissions' => [],
            'submenu' => [
                ['label' => 'ภาพรวม', 'route' => 'admin.security.index'],
                ['label' => 'Threat Intelligence', 'route' => 'admin.security.threat-intelligence'],
                ['label' => 'Analytics', 'route' => 'admin.security.analytics'],
                ['label' => 'ตั้งค่า OTP', 'route' => 'admin.otp.settings'],
                ['label' => 'ตั้งค่า 2FA', 'route' => 'admin.two-factor.settings'],
            ],
        ],

        [
            'id' => 'pages-seo',
            'label' => 'เพจ & SEO',
            'icon' => '📄',
            'route' => null,
            'order' => 21,
            'permissions' => [],
            'submenu' => [
                ['label' => 'จัดการเพจ', 'route' => 'admin.pages.index'],
                ['label' => 'SEO Settings', 'route' => 'admin.seo.index'],
            ],
        ],

        [
            'id' => 'analytics',
            'label' => 'Analytics',
            'icon' => '📊',
            'route' => null,
            'order' => 22,
            'permissions' => [],
            'submenu' => [
                ['label' => 'ภาพรวม', 'route' => 'admin.analytics.index'],
            ],
        ],

        [
            'id' => 'theme-settings',
            'label' => 'ตั้งค่าธีม',
            'icon' => '🎨',
            'route' => null,
            'order' => 23,
            'permissions' => [],
            'submenu' => [
                ['label' => '📊 ภาพรวมธีม', 'route' => 'admin.arrow-x-theme.index'],
                ['label' => '⚙️ ตั้งค่าทั่วไป', 'route' => 'admin.arrow-x-theme.general-settings'],
                ['label' => '🎨 สีและการไล่สี', 'route' => 'admin.arrow-x-theme.color-settings'],
                ['label' => '✨ เอฟเฟกต์ RGB', 'route' => 'admin.arrow-x-theme.rgb-effects'],
                ['label' => '🔤 ตัวอักษรและฟอนต์', 'route' => 'admin.arrow-x-theme.typography'],
            ],
        ],

        [
            'id' => 'languages',
            'label' => 'ภาษา & แปล',
            'icon' => '🌐',
            'route' => null,
            'order' => 24,
            'permissions' => [],
            'submenu' => [
                ['label' => 'การแปล', 'route' => 'admin.translations.index'],
                ['label' => 'ตั้งค่าภาษา', 'route' => 'admin.settings.languages'],
            ],
        ],

        [
            'id' => 'content-media',
            'label' => 'คอนเทนต์ & มีเดีย',
            'icon' => '🎬',
            'route' => null,
            'order' => 24.5,
            'permissions' => [],
            'submenu' => [
                ['label' => 'WebP Image Converter', 'route' => 'admin.webp.index'],
                ['label' => 'Page Builder', 'route' => 'admin.page-builder.index'],
                ['label' => 'Tarot System', 'route' => 'admin.tarot.index'],
                ['label' => 'Video Rewards', 'route' => 'admin.video-rewards.dashboard'],
            ],
        ],

        [
            'id' => 'ai-gen',
            'label' => 'AI Gen System',
            'icon' => '🎨',
            'route' => null,
            'order' => 24.7,
            'permissions' => [],
            'badge' => 'NEW',
            'badge_color' => 'bg-gradient-to-r from-purple-500 to-pink-500',
            'submenu' => [
                ['label' => '📊 Dashboard', 'route' => 'admin.ai-gen.dashboard'],
                ['label' => '🤖 AI Providers', 'route' => 'admin.ai-gen.providers'],
                ['label' => '📦 Packages', 'route' => 'admin.ai-gen.packages'],
                ['label' => '🎁 Free Quotas', 'route' => 'admin.ai-gen.quotas'],
                ['label' => '👥 Subscriptions', 'route' => 'admin.ai-gen.subscriptions'],
                ['label' => '📋 Usage Logs', 'route' => 'admin.ai-gen.usage-logs'],
                ['label' => '🖼️ All Generations', 'route' => 'admin.ai-gen.generations'],
                ['label' => '⚙️ Settings', 'route' => 'admin.ai-gen.settings'],
            ],
        ],

        [
            'id' => 'settings',
            'label' => 'ตั้งค่าระบบ',
            'icon' => '⚙️',
            'route' => null,
            'order' => 25,
            'permissions' => [],
            'submenu' => [
                ['label' => 'ตั้งค่าทั่วไป', 'route' => 'admin.settings.index'],
                ['label' => 'ตั้งค่า Mobile App', 'route' => 'admin.app-management.settings.index'],
                ['label' => 'คุณสมบัติแอป', 'route' => 'admin.app-management.features.index'],
                ['label' => 'แบนเนอร์แอป', 'route' => 'admin.app-management.banners.index'],
                ['label' => 'โหมดซ่อมบำรุง', 'route' => 'admin.app-management.maintenance.index'],
                ['label' => 'ตั้งค่า OCR', 'route' => 'admin.settings.ocr'],
                ['label' => 'จัดการ API', 'route' => 'admin.api-management.endpoints.index'],
                ['label' => 'API Keys', 'route' => 'admin.api-management.keys.index'],
                ['label' => 'รีเซ็ตระบบ', 'route' => 'admin.system-reset.index'],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Seller Menu Items
    |--------------------------------------------------------------------------
    |
    | เมนูสำหรับ Seller Dashboard
    | แสดงเมื่อ user มี role = 'seller'
    |
    */

    'seller' => [
        [
            'id' => 'dashboard',
            'label' => 'แดชบอร์ด',
            'icon' => '📊',
            'route' => 'seller.dashboard',
            'order' => 0,
            'permissions' => [],
        ],

        [
            'id' => 'marketing',
            'label' => 'การตลาด',
            'icon' => '📢',
            'route' => 'seller.marketing',
            'order' => 0.5,
            'permissions' => [],
        ],

        [
            'id' => 'notifications',
            'label' => 'การแจ้งเตือน',
            'icon' => '🔔',
            'route' => 'seller.notifications.index',
            'order' => 0.7,
            'permissions' => [],
        ],

        [
            'id' => 'products',
            'label' => 'สินค้า',
            'icon' => '📦',
            'route' => null,
            'order' => 1,
            'permissions' => [],
            'submenu' => [
                ['label' => 'รายการสินค้า', 'route' => 'seller.products.index'],
                ['label' => 'เพิ่มสินค้า', 'route' => 'seller.products.create'],
                ['label' => 'แพ็คเกจ/สมาชิก', 'route' => 'seller.packages'],
            ],
        ],

        [
            'id' => 'pos',
            'label' => 'ระบบ POS',
            'icon' => '🏪',
            'route' => null,
            'order' => 2,
            'permissions' => [],
            'submenu' => [
                ['label' => 'ขายสินค้า', 'route' => 'seller.pos.terminal'],
                ['label' => 'รายการขาย', 'route' => 'seller.pos.transactions'],
                ['label' => 'อุปกรณ์ POS', 'route' => 'seller.pos.devices'],
                ['label' => 'Session', 'route' => 'seller.pos.sessions'],
                ['label' => 'หมวดหมู่', 'route' => 'seller.pos.categories'],
                ['label' => 'โฆษณา', 'route' => 'seller.pos.advertisements'],
                ['label' => 'ตั้งค่า POS', 'route' => 'seller.pos.settings'],
            ],
        ],

        [
            'id' => 'sales',
            'label' => 'ยอดขาย',
            'icon' => '🛒',
            'route' => null,
            'order' => 3,
            'permissions' => [],
            'submenu' => [
                ['label' => 'คำสั่งซื้อ', 'route' => 'seller.orders.index'],
                ['label' => 'รายงานยอดขาย', 'route' => 'seller.reports.sales'],
            ],
        ],

        [
            'id' => 'wallet',
            'label' => 'กระเป๋าเงิน',
            'icon' => '💰',
            'route' => null,
            'order' => 4,
            'permissions' => [],
            'submenu' => [
                ['label' => 'กระเป๋าของฉัน', 'route' => 'seller.wallet.index'],
                ['label' => 'ถอนเงิน', 'route' => 'seller.wallet.withdraw'],
            ],
        ],

        [
            'id' => 'commissions',
            'label' => 'คอมมิชชั่น',
            'icon' => '💵',
            'route' => 'seller.commissions',
            'order' => 5,
            'permissions' => [],
        ],

        [
            'id' => 'analytics',
            'label' => 'วิเคราะห์',
            'icon' => '📈',
            'route' => null,
            'order' => 6,
            'permissions' => [],
            'submenu' => [
                ['label' => '📊 Dashboard', 'route' => 'seller.analytics.index'],
                ['label' => '🤖 AI Insights', 'route' => 'seller.analytics.ai-insights'],
                ['label' => '👥 Customer Segments', 'route' => 'seller.analytics.segmentation'],
                ['label' => '📈 Cohort Analysis', 'route' => 'seller.analytics.cohort'],
                ['label' => '🏆 Products Ranking', 'route' => 'seller.analytics.products'],
                ['label' => '🖥️ System Monitoring', 'route' => 'seller.analytics.system-monitoring'],
                ['label' => '📤 Export Data', 'route' => 'seller.analytics.export'],
                ['label' => '⚙️ Settings', 'route' => 'seller.analytics.settings'],
            ],
        ],

        [
            'id' => 'settings',
            'label' => 'ตั้งค่าร้าน',
            'icon' => '⚙️',
            'route' => 'seller.settings',
            'order' => 7,
            'permissions' => [],
        ],

        [
            'id' => 'profile',
            'label' => 'โปรไฟล์',
            'icon' => '👤',
            'route' => 'seller.profile',
            'order' => 8,
            'permissions' => [],
        ],

        [
            'id' => 'chatbot',
            'label' => 'ระบบบอทแชท',
            'icon' => '💬',
            'route' => null,
            'order' => 8.5,
            'permissions' => [],
            'submenu' => [
                ['label' => 'บอทของฉัน', 'route' => 'chatbot.index'],
                ['label' => 'สร้างบอทใหม่', 'route' => 'chatbot.create'],
                ['label' => 'ตลาดบอท', 'route' => 'chatbot.marketplace.index'],
                ['label' => 'บอทที่เช่า', 'route' => 'chatbot.marketplace.my-rentals'],
                ['label' => 'รายได้ของฉัน', 'route' => 'chatbot.marketplace.my-earnings'],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Menu Items
    |--------------------------------------------------------------------------
    |
    | เมนูสำหรับ User Dashboard
    | แสดงเมื่อ user มี role = 'user' หรือ role อื่นๆ
    |
    */

    'user' => [
        [
            'id' => 'dashboard',
            'label' => 'แดชบอร์ด',
            'icon' => '📊',
            'route' => 'user.dashboard',
            'order' => 0,
            'permissions' => [],
        ],

        [
            'id' => 'profile',
            'label' => 'โปรไฟล์',
            'icon' => '👤',
            'route' => 'user.profile',
            'order' => 1,
            'permissions' => [],
        ],

        [
            'id' => 'kyc',
            'label' => 'ยืนยันตัวตน KYC',
            'icon' => '🪪',
            'route' => 'user.kyc.index',
            'order' => 2,
            'permissions' => [],
        ],

        [
            'id' => 'commissions',
            'label' => 'คอมมิชชั่น',
            'icon' => '💰',
            'route' => 'user.commissions',
            'order' => 3,
            'permissions' => [],
        ],

        [
            'id' => 'notifications',
            'label' => 'การแจ้งเตือน',
            'icon' => '🔔',
            'route' => 'user.notifications.index',
            'order' => 3.5,
            'permissions' => [],
        ],

        [
            'id' => 'shopping',
            'label' => 'ช๊อปปิ้ง',
            'icon' => '🛒',
            'route' => null,
            'order' => 4,
            'permissions' => [],
            'submenu' => [
                ['label' => 'ช๊อปสินค้า', 'route' => 'shop.index'],
            ],
        ],

        [
            'id' => 'hotels',
            'label' => 'โรงแรม',
            'icon' => '🏨',
            'route' => null,
            'order' => 5,
            'permissions' => [],
            'submenu' => [
                ['label' => 'จองโรงแรม', 'route' => 'hotels.index'],
                ['label' => 'การจองของฉัน', 'route' => 'hotels.bookings.index'],
            ],
        ],

        [
            'id' => 'tickets',
            'label' => 'Ticket Support',
            'icon' => '🎫',
            'route' => 'user.tickets.index',
            'order' => 6,
            'permissions' => [],
        ],

        [
            'id' => 'chatbot',
            'label' => 'ระบบบอทแชท',
            'icon' => '💬',
            'route' => null,
            'order' => 6.5,
            'permissions' => [],
            'submenu' => [
                ['label' => 'บอทของฉัน', 'route' => 'chatbot.index'],
                ['label' => 'สร้างบอทใหม่', 'route' => 'chatbot.create'],
                ['label' => 'ตลาดบอท', 'route' => 'chatbot.marketplace.index'],
                ['label' => 'บอทที่เช่า', 'route' => 'chatbot.marketplace.my-rentals'],
                ['label' => 'รายได้ของฉัน', 'route' => 'chatbot.marketplace.my-earnings'],
            ],
        ],

        [
            'id' => 'games',
            'label' => 'เกมส์',
            'icon' => '🎮',
            'route' => null,
            'order' => 6.8,
            'permissions' => [],
            'submenu' => [
                ['label' => 'เกมส์ทั้งหมด', 'route' => 'games.index'],
                ['label' => 'ทัวร์นาเมนต์', 'route' => 'tournaments.index'],
                ['label' => 'รางวัลรายวัน', 'route' => 'rewards.daily'],
                ['label' => 'ภารกิจ', 'route' => 'rewards.missions'],
            ],
        ],

        [
            'id' => 'wallet-thb',
            'label' => 'กระเป๋าเงิน THB',
            'icon' => '💳',
            'route' => null,
            'order' => 7,
            'permissions' => [],
            'submenu' => [
                ['label' => 'กระเป๋าของฉัน', 'route' => 'user.wallet.index'],
                ['label' => 'เติมเงิน', 'route' => 'user.wallet.deposit'],
                ['label' => 'ถอนเงิน', 'route' => 'user.wallet.withdraw'],
                ['label' => 'โอนเงิน', 'route' => 'user.wallet.transfer'],
                ['label' => 'ประวัติธุรกรรม', 'route' => 'user.wallet.transactions'],
            ],
        ],

        [
            'id' => 'wallet-crypto',
            'label' => 'กระเป๋าคริปโต',
            'icon' => '₿',
            'route' => null,
            'order' => 8,
            'permissions' => [],
            'submenu' => [
                ['label' => 'กระเป๋าคริปโต', 'route' => 'user.crypto-wallet.index'],
            ],
        ],

        [
            'id' => 'investments',
            'label' => 'การลงทุน ROI',
            'icon' => '📈',
            'route' => null,
            'order' => 9,
            'permissions' => [],
            'submenu' => [
                ['label' => 'แดชบอร์ด', 'route' => 'user.investments.index'],
                ['label' => 'แผนการลงทุน', 'route' => 'user.investments.plans'],
            ],
        ],

        [
            'id' => 'ai-bots',
            'label' => 'AI Bots',
            'icon' => '🤖',
            'route' => null,
            'order' => 10,
            'permissions' => [],
            'submenu' => [
                ['label' => 'ตลาดบอท', 'route' => 'marketplace.index'],
            ],
        ],

        [
            'id' => 'team',
            'label' => 'ทีมงาน',
            'icon' => '👥',
            'route' => 'user.team',
            'order' => 11,
            'permissions' => [],
            'submenu' => [
                ['label' => 'ผู้มุ่งหวัง', 'route' => 'user.prospects.index'],
                ['label' => 'ลีดเดอร์บอร์ด', 'route' => 'user.ranks.leaderboard'],
            ],
        ],

        [
            'id' => 'retention',
            'label' => 'รักษายอด',
            'icon' => '💖',
            'route' => null,
            'order' => 12,
            'permissions' => [],
            'submenu' => [
                ['label' => 'สถานะพลังชีวิต', 'route' => 'user.retention.index'],
            ],
        ],

        [
            'id' => 'marketing-tools',
            'label' => 'เครื่องมือการตลาด',
            'icon' => '🎯',
            'route' => null,
            'order' => 13,
            'permissions' => [],
            'submenu' => [
                ['label' => 'สร้าง QR Code & Barcode', 'route' => 'qr-barcode.index'],
                ['label' => 'จำลองรายได้', 'route' => 'user.mlm.income-simulator'],
                ['label' => 'จำลองเงินปันผล', 'route' => 'user.mlm.dividend-simulator'],
            ],
        ],

        [
            'id' => 'security',
            'label' => 'ความปลอดภัย',
            'icon' => '🔐',
            'route' => null,
            'order' => 13.5,
            'permissions' => [],
            'submenu' => [
                ['label' => 'ตั้งค่า 2FA', 'route' => 'user.two-factor.setup'],
                ['label' => 'การตั้งค่าอีเมล', 'route' => 'user.email.preferences'],
            ],
        ],

        [
            'id' => 'themes',
            'label' => 'ตั้งค่าธีม',
            'icon' => '🎨',
            'route' => 'user.themes.index',
            'order' => 14,
            'permissions' => [],
        ],
    ],

];
