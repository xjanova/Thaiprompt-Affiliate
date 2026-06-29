<?php

/**
 * Menu Configuration - V3 Menu System
 *
 * ╔══════════════════════════════════════════════════════════════════════════════╗
 * ║  🎯 SINGLE SOURCE OF TRUTH สำหรับเมนูทั้งหมดในระบบ                             ║
 * ║                                                                                ║
 * ║  ‼️  ถ้าจะเพิ่ม/แก้/ย้าย/ลบเมนู Admin/Seller/User → แก้ที่ไฟล์นี้เท่านั้น        ║
 * ║                                                                                ║
 * ║  ❌ ห้ามแก้ resources/views/components/arrow-x/sidebar-v3.blade.php            ║
 * ║     (Hardcoded menu ในนั้นเป็น dead code — ไม่ render)                          ║
 * ║                                                                                ║
 * ║  Flow:  config/menus.php → MenuService::getMenuForRole() → sidebar-v3 @foreach║
 * ║                                                                                ║
 * ║  หลัง edit ไฟล์นี้แล้ว:                                                         ║
 * ║    1. composer dump-autoload   (ไม่จำเป็น — config ไม่ต้อง autoload)            ║
 * ║    2. php artisan config:clear  (สำคัญ! Laravel cache config)                  ║
 * ║    3. php artisan view:clear    (ถ้า sidebar component ไม่ refresh)            ║
 * ╚══════════════════════════════════════════════════════════════════════════════╝
 *
 * ✅ Config-based approach (ไม่ใช้ database)
 * ✅ Theme-agnostic (รองรับทุก theme)
 * ✅ Feature-ready (รองรับ Feature Providers)
 * ✅ Permission-aware (กรองตาม permissions)
 *
 * @version 3.0.0
 *
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
                ['label' => 'รายชื่อผู้ใช้', 'route' => 'admin.users.index', 'icon' => 'fas fa-list'],
                ['label' => 'บทบาท (Roles)', 'route' => 'admin.roles.index', 'icon' => 'fas fa-user-shield'],
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
            'id' => 'ai-core',
            'label' => 'AI Core',
            'icon' => '🧠',
            'route' => null,
            'order' => 4,
            'permissions' => [],
            'badge' => 'CORE',
            'badge_color' => 'bg-gradient-to-r from-cyan-500 via-blue-500 to-purple-600',
            'submenu' => [
                ['label' => '🎯 AI Core Dashboard', 'route' => 'admin.ai-core.dashboard', 'badge' => 'NEW', 'badge_color' => 'bg-gradient-to-r from-green-500 to-emerald-500'],
                ['label' => '⚡ Features Management', 'route' => 'admin.ai-core.features.index', 'description' => 'จัดการ AI Features ทั้ง 8 กลุ่ม'],
                ['label' => '👥 Tenants & Access', 'route' => 'admin.ai-core.tenants.index', 'description' => 'จัดการ Multi-tenancy'],
                ['label' => '📊 Quota Management', 'route' => 'admin.ai-core.quotas.index', 'description' => 'ควบคุมโควต้าการใช้งาน'],
                ['label' => '⏰ Schedules', 'route' => 'admin.ai-core.schedules.index', 'description' => 'ตั้งเวลาเปิด/ปิด Features'],
                ['label' => '🔔 Alerts & Notifications', 'route' => 'admin.ai-core.alerts.index', 'description' => 'แจ้งเตือนและการแจ้งเตือน'],
                ['label' => '📈 Usage Analytics', 'route' => 'admin.ai-core.analytics.index', 'description' => 'วิเคราะห์การใช้งาน AI'],
                ['label' => '⚙️ Global Settings', 'route' => 'admin.ai-core.settings.index', 'description' => 'ตั้งค่าทั่วไป'],
                ['label' => '---', 'route' => null], // Divider
                ['label' => '🎨 AI Generation', 'route' => 'admin.ai-gen.dashboard', 'description' => 'สร้างภาพและวิดีโอ'],
                ['label' => '🔄 Bot Automation', 'route' => 'admin.bot-automation.index', 'description' => 'ระบบอัตโนมัติ'],
                ['label' => '📈 Trading Bot', 'route' => 'admin.trading-bot.dashboard', 'description' => 'Bot เทรดด้วย AI'],
            ],
        ],

        [
            'id' => 'ai-bots',
            'label' => 'AI Bots & ผู้ช่วย',
            'icon' => '🤖',
            'route' => null,
            'order' => 4.1,
            'permissions' => [],
            'submenu' => [
                ['label' => 'จัดการ AI Bots', 'route' => 'admin.ai-bots.index', 'icon' => 'fas fa-robot'],
                ['label' => 'AI Providers', 'route' => 'admin.ai-providers.index', 'icon' => 'fas fa-plug'],
                ['label' => 'ติดตั้ง AI', 'route' => 'admin.ai-installation.index', 'icon' => 'fas fa-download'],
                ['label' => 'AI Monitoring', 'route' => 'admin.ai-monitoring.index', 'icon' => 'fas fa-heartbeat'],
                // Knowledge Bases - ต้องระบุ botId จึงไม่สามารถลิงก์ตรงได้ (removed)
            ],
        ],

        [
            'id' => 'central-ai',
            'label' => 'Central AI',
            'icon' => '🖥️',
            'route' => null,
            'order' => 4.2,
            'permissions' => [],
            'badge' => 'NEW',
            'badge_color' => 'bg-gradient-to-r from-blue-500 to-purple-600',
            'submenu' => [
                ['label' => '🎯 Dashboard', 'route' => 'admin.central-ai.dashboard', 'description' => 'ควบคุมและติดตามระบบ'],
                ['label' => '🔧 Setup Wizard', 'route' => 'admin.central-ai.wizard', 'description' => 'ติดตั้งและตั้งค่า'],
                ['label' => '🤖 Ollama Management', 'route' => 'admin.central-ai.ollama.index', 'description' => 'จัดการ Ollama Service'],
                ['label' => '⚙️ Settings', 'route' => 'admin.central-ai.index', 'description' => 'ตั้งค่าระบบ'],
            ],
        ],

        // ระบบบอทแชท - ย้ายไปที่เมนู user แล้ว (ไม่ใช่ admin route)

        [
            'id' => 'ai-content-writer',
            'label' => 'AI Content Writer',
            'icon' => '✍️',
            'route' => null,
            'order' => 4.4,
            'permissions' => [],
            'badge' => 'NEW',
            'badge_color' => 'bg-gradient-to-r from-purple-500 to-pink-500',
            'submenu' => [
                ['label' => '📊 Dashboard', 'route' => 'admin.platform-revenue.ai-content-writer.dashboard'],
                ['label' => '✨ Playground', 'route' => 'admin.platform-revenue.ai-content-writer.playground', 'description' => 'ทดสอบสร้าง Content'],
                ['label' => '📄 เทมเพลต', 'route' => 'admin.platform-revenue.ai-content-writer.templates', 'description' => 'จัดการเทมเพลต'],
                ['label' => '📁 โปรเจกต์', 'route' => 'admin.platform-revenue.ai-content-writer.projects', 'description' => 'โปรเจกต์ Content'],
                ['label' => '📜 ประวัติการสร้าง', 'route' => 'admin.platform-revenue.ai-content-writer.generations', 'description' => 'ดูประวัติ'],
                ['label' => '📈 Usage Logs', 'route' => 'admin.platform-revenue.ai-content-writer.usage-logs', 'description' => 'ติดตามการใช้งาน'],
                ['label' => '⚙️ ตั้งค่า', 'route' => 'admin.platform-revenue.ai-content-writer.settings', 'description' => 'API Keys'],
            ],
        ],

        [
            'id' => 'smart-sliders',
            'label' => 'Smart Slider Pro',
            'icon' => '🖼️',
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
                ['label' => 'โรงแรมทั้งหมด', 'route' => 'admin.hotels.index', 'icon' => 'fas fa-building'],
                ['label' => 'การจองทั้งหมด', 'route' => 'admin.hotels.bookings.index', 'icon' => 'fas fa-calendar-check'],
                ['label' => 'สถิติการจอง', 'route' => 'admin.hotels.bookings.analytics', 'icon' => 'fas fa-chart-bar'],
                ['label' => 'จัดการรีวิว', 'route' => 'admin.hotels.reviews.index', 'icon' => 'fas fa-star'],
                ['label' => 'เจ้าของโรงแรม', 'route' => 'admin.hotel-owners.index', 'icon' => 'fas fa-user-tie'],
                ['label' => 'สิ่งอำนวยความสะดวก', 'route' => 'admin.hotels.facilities.index', 'icon' => 'fas fa-concierge-bell'],
                ['label' => 'โปรโมชั่นพิเศษ', 'route' => 'admin.hotels.special-offers.index', 'icon' => 'fas fa-gift'],
            ],
        ],

        [
            'id' => 'service-booking',
            'label' => 'จองบริการ (Service)',
            'icon' => '🔧',
            'route' => null,
            'order' => 5.5,
            'permissions' => [],
            'badge' => 'NEW',
            'badge_color' => 'bg-gradient-to-r from-purple-500 to-pink-500',
            'submenu' => [
                ['label' => '📊 Dashboard', 'route' => 'admin.service-bookings.index'],
                ['label' => '🗂️ หมวดหมู่บริการ', 'route' => 'admin.service-categories.index'],
                ['label' => '🔧 จัดการบริการ', 'route' => 'admin.services.index'],
                ['label' => '🚫 บริการที่ถูกบล็อก', 'route' => 'admin.services.blocked', 'badge' => 'NEW', 'badge_color' => 'bg-gradient-to-r from-red-500 to-orange-500'],
                ['label' => '📅 การจองทั้งหมด', 'route' => 'admin.service-bookings.index'],
                ['label' => '👷 ผู้ให้บริการ', 'route' => 'admin.service-providers.index', 'description' => 'จัดการผู้ให้บริการ'],
                ['label' => '💰 กฎการคิดราคา', 'route' => 'admin.service-pricing-rules.index', 'description' => 'ตั้งค่าราคาตามระยะทาง'],
                ['label' => '📈 รายงานและสถิติ', 'route' => 'admin.service-bookings.analytics'],
            ],
        ],

        [
            'id' => 'fresh-market',
            'label' => 'ตลาดสดไทยพร๊อม',
            'icon' => '🏪',
            'route' => null,
            'order' => 5.7,
            'permissions' => [],
            'badge' => 'NEW',
            'badge_color' => 'bg-gradient-to-r from-green-500 to-emerald-500',
            'submenu' => [
                ['label' => '📊 แดชบอร์ด', 'route' => 'admin.fresh-market.dashboard'],
                ['label' => '⚙️ ตั้งค่าระบบ', 'route' => 'admin.fresh-market.settings'],
                ['label' => '📂 หมวดหมู่สินค้า', 'route' => 'admin.fresh-market.categories'],
                ['label' => '👨‍🌾 ผู้ขาย', 'route' => 'admin.fresh-market.sellers'],
                ['label' => '🥬 รายการสินค้า', 'route' => 'admin.fresh-market.listings'],
                ['label' => '📦 ออเดอร์', 'route' => 'admin.fresh-market.orders'],
                ['label' => '💰 คอมมิชชั่น', 'route' => 'admin.fresh-market.commissions'],
                ['label' => '🤖 ทดสอบ LINE Bot', 'route' => 'admin.fresh-market.test-line'],
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
                ['label' => 'แดชบอร์ด', 'route' => 'admin.ecommerce.dashboard', 'icon' => 'fas fa-tachometer-alt'],
                ['label' => 'สินค้าทั้งหมด', 'route' => 'admin.ecommerce.products.index', 'icon' => 'fas fa-box'],
                ['label' => 'นำเข้าจาก Lazada', 'route' => 'admin.ecommerce.lazada-import.form', 'icon' => 'fas fa-cloud-download-alt', 'badge' => 'NEW', 'badge_color' => 'bg-gradient-to-r from-blue-500 to-cyan-500'],
                ['label' => 'สินค้าที่ถูกบล็อก', 'route' => 'admin.ecommerce.products.blocked', 'icon' => 'fas fa-ban', 'badge' => 'NEW', 'badge_color' => 'bg-gradient-to-r from-red-500 to-orange-500'],
                ['label' => 'คำสั่งซื้อ', 'route' => 'admin.ecommerce.orders.index', 'icon' => 'fas fa-shopping-cart'],
                ['label' => 'หมวดหมู่', 'route' => 'admin.ecommerce.categories.index', 'icon' => 'fas fa-tags'],
                ['label' => 'รีวิวสินค้า', 'route' => 'admin.ecommerce.reviews.index', 'icon' => 'fas fa-star'],
                ['label' => 'Official Shop', 'route' => 'admin.official-shop.dashboard', 'icon' => 'fas fa-crown', 'badge' => 'Premium', 'badge_color' => 'bg-gradient-to-r from-amber-500 to-yellow-500'],
            ],
        ],

        [
            'id' => 'lazada-hub',
            'label' => 'Lazada Hub',
            'icon' => '🛒',
            'route' => null,
            'order' => 6.5,
            'permissions' => [],
            'submenu' => [
                ['label' => '📊 แดชบอร์ด', 'route' => 'admin.lazada-hub.dashboard', 'icon' => 'fas fa-gauge-high', 'badge' => 'NEW', 'badge_color' => 'bg-gradient-to-r from-orange-500 to-amber-500'],
                ['label' => '🔌 การเชื่อมต่อ API', 'route' => 'admin.lazada-hub.connections.index', 'icon' => 'fas fa-plug'],
                ['label' => '🗂️ แคตตาล็อก', 'route' => 'admin.lazada-hub.catalog.index', 'icon' => 'fas fa-layer-group'],
                ['label' => '⬇️ นำเข้าสินค้า', 'route' => 'admin.lazada-hub.catalog.import', 'icon' => 'fas fa-cloud-download-alt'],
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
                ['label' => 'แดชบอร์ด', 'route' => 'admin.pos.dashboard', 'icon' => 'fas fa-tachometer-alt'],
                ['label' => 'อุปกรณ์ POS', 'route' => 'admin.pos.devices.index', 'icon' => 'fas fa-desktop'],
                ['label' => 'ธุรกรรม', 'route' => 'admin.pos.transactions.index', 'icon' => 'fas fa-receipt'],
                ['label' => 'วิเคราะห์ข้อมูล', 'route' => 'admin.pos.analytics', 'icon' => 'fas fa-chart-pie'],
            ],
        ],

        [
            'id' => 'nfc-system',
            'label' => 'บัตร NFC',
            'icon' => '💳',
            'route' => null,
            'order' => 7.5,
            'permissions' => [],
            'badge' => 'NEW',
            'badge_color' => 'bg-gradient-to-r from-blue-500 to-purple-500',
            'submenu' => [
                ['label' => '📊 Dashboard', 'route' => 'admin.nfc.dashboard'],
                ['label' => '💳 จัดการบัตร', 'route' => 'admin.nfc-cards.index'],
                ['label' => '➕ ออกบัตรใหม่', 'route' => 'admin.nfc-cards.create'],
                ['label' => '✏️ NFC Writer', 'route' => 'admin.nfc-cards.writer', 'badge' => 'NEW', 'badge_color' => 'bg-green-500'],
                ['label' => '📜 ธุรกรรมทั้งหมด', 'route' => 'admin.nfc.transactions'],
                ['label' => '📥 ส่งออกข้อมูล', 'route' => 'admin.nfc-cards.export'],
                ['label' => '---', 'route' => null], // Divider
                ['label' => '📡 เครื่องอ่าน NFC', 'route' => 'admin.nfc-readers.index'],
            ],
        ],

        // =============================================
        // 📱 SMS Payment Checker - ระบบตรวจสอบ SMS ชำระเงินอัตโนมัติ
        // =============================================
        [
            'id' => 'sms-payment',
            'label' => 'SMS Payment',
            'icon' => '📱',
            'route' => null,
            'order' => 7.5,
            'permissions' => [],
            'badge' => 'NEW',
            'badge_color' => 'bg-gradient-to-r from-green-500 to-emerald-500',
            'submenu' => [
                ['label' => '📊 Dashboard', 'route' => 'admin.smschecker.index', 'description' => 'ภาพรวมระบบ SMS Payment'],
                ['label' => '⚙️ ตั้งค่าการเชื่อมต่อ', 'route' => 'admin.smschecker.settings', 'description' => 'การตั้งค่าและคู่มือเชื่อมต่อ'],
                ['label' => '📱 อุปกรณ์', 'route' => 'admin.smschecker.devices', 'description' => 'จัดการอุปกรณ์ Android'],
                ['label' => '📨 SMS Notifications', 'route' => 'admin.smschecker.notifications', 'description' => 'ประวัติ SMS ที่ได้รับ'],
                ['label' => '⏳ รอตรวจสอบ', 'route' => 'admin.smschecker.pending-orders', 'description' => 'คำสั่งซื้อรอยืนยันการชำระเงิน'],
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
                ['label' => 'กระเป๋าเงินทั้งหมด', 'route' => 'admin.wallet.index', 'icon' => 'fas fa-wallet'],
                ['label' => 'ประวัติธุรกรรม', 'route' => 'admin.wallet.transactions', 'icon' => 'fas fa-history'],
                ['label' => 'คำขอถอนเงิน', 'route' => 'admin.withdrawals.pending', 'icon' => 'fas fa-clock'],
                ['label' => 'ประวัติการถอน', 'route' => 'admin.withdrawals.index', 'icon' => 'fas fa-money-bill-wave'],
                ['label' => 'ตั้งค่า Payment Gateway', 'route' => 'admin.payment-gateways.index', 'icon' => 'fas fa-credit-card'],
                ['label' => 'บัญชีธนาคาร', 'route' => 'admin.payment-bank-accounts.index', 'icon' => 'fas fa-university', 'description' => 'จัดการบัญชีธนาคารรับชำระเงิน + PromptPay + SMS Checker'],
                ['label' => 'ตั้งค่ากระเป๋าเงิน', 'route' => 'admin.wallet-settings.index', 'icon' => 'fas fa-cog'],
                ['label' => 'ตั้งค่า Cashback', 'route' => 'admin.cashback.index', 'icon' => 'fas fa-percentage'],
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
                ['label' => 'แดชบอร์ด', 'route' => 'admin.crypto.dashboard', 'icon' => 'fas fa-tachometer-alt'],
                ['label' => 'จัดการ Wallets', 'route' => 'admin.crypto.wallets', 'icon' => 'fas fa-wallet'],
                ['label' => 'ธุรกรรม', 'route' => 'admin.crypto.transactions', 'icon' => 'fas fa-exchange-alt'],
                ['label' => 'คำขอถอน', 'route' => 'admin.crypto.withdrawals', 'icon' => 'fas fa-arrow-circle-up'],
                ['label' => 'จัดการเหรียญ/สกุลเงิน', 'route' => 'admin.crypto.currencies', 'icon' => 'fas fa-coins'],
                ['label' => 'ตั้งค่าคริปโต', 'route' => 'admin.crypto.settings', 'icon' => 'fas fa-sliders-h'],
            ],
        ],

        // =============================================
        // 💼 Platform Finance - กระเป๋าเงินพิเศษของแพลตฟอร์ม
        // =============================================
        [
            'id' => 'platform-finance',
            'label' => '💼 การเงินแพลตฟอร์ม',
            'icon' => '💼',
            'route' => null,
            'order' => 9.3,
            'permissions' => [],
            'badge' => 'ADMIN',
            'badge_color' => 'bg-gradient-to-r from-emerald-500 to-teal-500',
            'submenu' => [
                ['label' => '📊 รายได้แพลตฟอร์ม', 'route' => 'admin.platform-revenue.index', 'description' => 'ดูรายได้ Platform Fee, VAT, MLM Pool'],
                ['label' => '💰 กระเป๋าเงินแพลตฟอร์ม', 'route' => 'admin.platform-revenue.wallets.index', 'description' => 'ดูยอดเงินใน Platform Wallets'],
                ['label' => '📝 ธุรกรรมแพลตฟอร์ม', 'route' => 'admin.platform-revenue.transactions', 'description' => 'ประวัติการเงินเข้า-ออก'],
                ['label' => '📈 รายงานการเงิน', 'route' => 'admin.platform-revenue.reports', 'description' => 'รายงานรายได้รายวัน/เดือน'],
                ['label' => '---', 'route' => null],
                ['label' => '💸 จ่ายเงินออก (Payouts)', 'route' => 'admin.platform-revenue.payouts.index', 'description' => 'จัดการคำขอถอนเงิน Seller'],
                ['label' => '📝 จัดการหนี้สิน', 'route' => 'admin.platform-revenue.debts.index', 'description' => 'ติดตามหนี้ที่ต้องเรียกคืน'],
                ['label' => '🔑 HD Wallets', 'route' => 'admin.crypto.hd-wallets.index', 'description' => 'Hierarchical Deterministic'],
            ],
        ],

        [
            'id' => 'staking',
            'label' => 'ระบบลงทุน Staking',
            'icon' => '📈',
            'route' => null,
            'order' => 9.5,
            'permissions' => [],
            'badge' => 'NEW',
            'badge_color' => 'bg-gradient-to-r from-indigo-500 to-purple-500',
            'submenu' => [
                ['label' => '📊 Dashboard', 'route' => 'admin.staking-plans.index', 'description' => 'ภาพรวมแผนลงทุน'],
                ['label' => '➕ สร้างแผนใหม่', 'route' => 'admin.staking-plans.create', 'description' => 'สร้างแผนลงทุนใหม่'],
                ['label' => '💰 Positions ทั้งหมด', 'route' => 'admin.staking-plans.positions', 'description' => 'ดูการลงทุนของ Users'],
                ['label' => '🪙 ตั้งค่า Coin', 'route' => 'admin.staking-plans.coin-settings', 'description' => 'อัตราแลกเปลี่ยน Coin/THB', 'badge' => 'HOT', 'badge_color' => 'bg-gradient-to-r from-yellow-500 to-orange-500'],
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
            'route' => 'admin.email.index',
            'order' => 11,
            'permissions' => [],
            'submenu' => [
                ['label' => '📊 Dashboard', 'route' => 'admin.email.index'],
                ['label' => '📢 แคมเปญ', 'route' => 'admin.email.campaigns.index', 'badge' => 'NEW', 'badge_color' => 'bg-gradient-to-r from-purple-500 to-pink-500'],
                ['label' => '⏳ คิวการส่ง', 'route' => 'admin.email.queue.index', 'badge' => 'NEW', 'badge_color' => 'bg-gradient-to-r from-blue-500 to-cyan-500'],
                ['label' => '📝 เทมเพลต', 'route' => 'admin.email.templates.index'],
                ['label' => '📡 ผู้ให้บริการ', 'route' => 'admin.email.providers'],
                ['label' => '📋 ประวัติการส่ง', 'route' => 'admin.email.logs'],
                ['label' => '📈 สถิติขั้นสูง', 'route' => 'admin.email.analytics', 'badge' => 'NEW', 'badge_color' => 'bg-gradient-to-r from-green-500 to-emerald-500'],
            ],
        ],

        [
            'id' => 'line-oa',
            'label' => 'LINE OA & AI',
            'icon' => '💚',
            'route' => null,
            'order' => 12,
            'permissions' => [],
            'badge' => 'PRO',
            'badge_color' => 'bg-gradient-to-r from-green-500 to-emerald-500',
            'submenu' => [
                // ═══════════════════════════════════════
                // 🎯 Dashboard & Settings
                // ═══════════════════════════════════════
                ['label' => '🎯 ภาพรวม Dashboard', 'route' => 'admin.line-oa.analytics', 'badge' => 'NEW', 'badge_color' => 'bg-gradient-to-r from-blue-500 to-cyan-500'],
                ['label' => '⚙️ ตั้งค่า LINE OA', 'route' => 'admin.line-oa.index', 'description' => 'Access Token, Webhook, Config'],
                ['label' => '---', 'route' => null], // Divider

                // ═══════════════════════════════════════
                // 💬 AI Chat Bot
                // ═══════════════════════════════════════
                ['label' => '💬 AI Chat Bot', 'route' => 'admin.line-bot.ai.index', 'description' => 'AI Settings & Conversations'],
                ['label' => '🎨 Rich Menus', 'route' => 'admin.line-bot.rich-menu.index', 'description' => 'จัดการเมนูด่วน LINE'],
                ['label' => '---', 'route' => null], // Divider

                // ═══════════════════════════════════════
                // 👥 Membership Signup System
                // ═══════════════════════════════════════
                ['label' => '👥 ระบบสมัครสมาชิก', 'route' => 'admin.line-membership-signup.index', 'badge' => '⭐', 'badge_color' => 'bg-gradient-to-r from-yellow-500 to-orange-500', 'description' => 'LINE Chatbot Registration'],
                ['label' => '   ├─ ⚙️ ตั้งค่าระบบ', 'route' => 'admin.line-membership-signup.settings', 'badge' => 'NEW', 'badge_color' => 'bg-gradient-to-r from-green-500 to-emerald-500', 'description' => 'ตั้งค่า OTP, Validation, Rewards, Gamification'],
                ['label' => '   ├─ 📊 Sessions', 'route' => 'admin.line-membership-signup.sessions', 'description' => 'ดู Session การสมัคร'],
                ['label' => '   ├─ 🎨 Templates', 'route' => 'admin.line-membership-signup.templates', 'description' => 'Flex Message Templates'],
                ['label' => '   ├─ 🎁 Rewards', 'route' => 'admin.line-membership-signup.rewards.index', 'description' => 'จัดการรางวัล'],
                ['label' => '   ├─ 🔧 Signup Flows', 'route' => 'admin.line-bot.signup-flow.index', 'description' => 'ตั้งค่า Flow'],
                ['label' => '   └─ 📈 Analytics', 'route' => 'admin.line-membership-signup.analytics.data', 'description' => 'วิเคราะห์ Funnel'],
                ['label' => '---', 'route' => null], // Divider

                // ═══════════════════════════════════════
                // 🤖 Keywords & Automation
                // ═══════════════════════════════════════
                ['label' => '🤖 Hybrid Bot Keywords', 'route' => 'admin.line-bot.keywords.index', 'description' => 'คีย์เวิร์ดอัตโนมัติ'],
                ['label' => '   ├─ 📊 Activity Logs', 'route' => 'admin.line-bot.keywords.activity.index'],
                ['label' => '   ├─ ⭐ Performance', 'route' => 'admin.line-bot.keywords.performance.index'],
                ['label' => '   └─ 💡 Suggestions', 'route' => 'admin.line-bot.keywords.suggestions.index'],
                ['label' => '---', 'route' => null], // Divider

                // ═══════════════════════════════════════
                // 🔬 Advanced Analysis
                // ═══════════════════════════════════════
                ['label' => '🔬 การวิเคราะห์ขั้นสูง', 'route' => null, 'description' => 'Advanced Analytics'],
                ['label' => '   ├─ 🧪 A/B Testing', 'route' => 'admin.line-bot.keywords.ab-tests.index'],
                ['label' => '   ├─ 😊 Sentiment Analysis', 'route' => 'admin.line-bot.keywords.sentiment-analysis.index'],
                ['label' => '   └─ 🧠 NLP Analysis', 'route' => 'admin.line-bot.keywords.nlp-analysis.index', 'badge' => 'AI', 'badge_color' => 'bg-gradient-to-r from-purple-500 to-pink-500'],
                ['label' => '---', 'route' => null], // Divider

                // ═══════════════════════════════════════
                // 📢 Broadcast
                // ═══════════════════════════════════════
                ['label' => '📢 Broadcast', 'route' => 'admin.line-bot.broadcast.index', 'description' => 'ส่งข้อความแบบรอบ'],
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
                ['label' => 'คอร์สเรียน', 'route' => 'admin.academy.courses.index', 'icon' => 'fas fa-book-open'],
                ['label' => 'จัดการแบบทดสอบ', 'route' => 'admin.quiz-management.index', 'icon' => 'fas fa-question-circle'],
                ['label' => 'ใบประกาศนักเรียน', 'route' => 'admin.certificates.index', 'icon' => 'fas fa-certificate'],
                ['label' => 'ใบประกาศระบบ', 'route' => 'admin.academy.certificates.index', 'icon' => 'fas fa-award'],
                ['label' => 'แดชบอร์ดอาจารย์', 'route' => 'admin.instructor.dashboard', 'icon' => 'fas fa-chalkboard-teacher'],
                ['label' => 'ตั้งค่า', 'route' => 'admin.academy.settings.index', 'icon' => 'fas fa-cog'],
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
                ['label' => 'บทความ', 'route' => 'admin.articles.index', 'icon' => 'fas fa-newspaper'],
                ['label' => 'หมวดหมู่', 'route' => 'admin.categories.index', 'icon' => 'fas fa-folder'],
                ['label' => 'ศูนย์เรียนรู้', 'route' => 'admin.learning-center.index', 'icon' => 'fas fa-graduation-cap'],
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
                ['label' => '📊 Dashboard', 'route' => 'admin.mlm.reports.dashboard', 'icon' => 'fas fa-chart-pie'],
                ['label' => '👥 สมาชิก MLM', 'route' => 'admin.mlm.members.index', 'icon' => 'fas fa-users-cog'],
                ['label' => '📋 แผน MLM', 'route' => 'admin.mlm.plans.index', 'icon' => 'fas fa-layer-group'],
                ['label' => '💰 คอมมิชชั่น', 'route' => 'admin.mlm.commissions.index', 'icon' => 'fas fa-money-bill-wave'],
                ['label' => '🏷️ Product PV', 'route' => 'admin.mlm.product-pv.index', 'icon' => 'fas fa-tags'],
                ['label' => '📈 รายงาน', 'route' => 'admin.mlm.reports.index', 'icon' => 'fas fa-chart-line'],
                ['label' => '🌳 ผังสายงาน (Genealogy)', 'route' => 'admin.mlm.genealogy.index', 'icon' => 'fas fa-project-diagram'],
                ['label' => '🏅 ระดับสมาชิก (Ranks)', 'route' => 'admin.ranks.index', 'icon' => 'fas fa-medal'],
                ['label' => '🎯 ผู้มุ่งหวัง (Prospects)', 'route' => 'admin.mlm-prospects.index', 'icon' => 'fas fa-user-plus'],
                ['label' => '🧮 เครื่องคิดเลข MLM', 'route' => 'admin.mlm.calculator', 'icon' => 'fas fa-calculator'],
                ['label' => '💡 ตัวอย่าง Placement', 'route' => 'admin.mlm.placement-examples', 'icon' => 'fas fa-lightbulb'],
                ['label' => '---', 'route' => null], // Divider
                ['label' => '🔄 โอนย้ายทีม', 'route' => 'admin.team-transfer.index', 'icon' => 'fas fa-exchange-alt', 'description' => 'ย้ายสายงาน'],
                ['label' => '↔️ ย้ายทีมโดยตรง', 'route' => 'admin.team-transfer.direct', 'icon' => 'fas fa-random', 'description' => 'ย้ายทีมแบบตรง'],
                ['label' => '📄 แม่แบบรับสมัคร', 'route' => 'admin.recruit-templates.index', 'icon' => 'fas fa-file-alt', 'description' => 'Templates สำหรับรับสมาชิก'],
                ['label' => '⚙️ ตั้งค่า MLM', 'route' => 'admin.mlm.settings.index', 'icon' => 'fas fa-cogs'],
                ['label' => '📚 คู่มือ MLM', 'url' => '/mlm-documentation.html', 'icon' => 'fas fa-book'],
            ],
        ],

        [
            'id' => 'marketing',
            'label' => 'ระบบการตลาด',
            'icon' => '🏆',
            'route' => null,
            'order' => 16,
            'permissions' => [],
            'submenu' => [
                ['label' => 'จัดการระดับ Rank', 'route' => 'admin.ranks.index', 'icon' => 'fas fa-medal'],
                ['label' => 'การเลื่อนระดับ', 'route' => 'admin.ranks.promotions.index', 'icon' => 'fas fa-arrow-up'],
                ['label' => 'Cashback', 'route' => 'admin.cashback.index', 'icon' => 'fas fa-percentage'],
                ['label' => '---', 'route' => null], // Divider
                ['label' => 'อัพเกรดดาว', 'route' => 'admin.star-upgrade.index', 'icon' => 'fas fa-star', 'description' => 'ราคาอัพเกรด Star'],
                ['label' => 'เทรนด์', 'route' => 'admin.trends.index', 'icon' => 'fas fa-fire', 'description' => 'Trending Items'],
            ],
        ],

        [
            'id' => 'video-missions',
            'label' => 'ภารกิจดูคลิป',
            'icon' => '🎬',
            'route' => null,
            'order' => 16.5,
            'permissions' => [],
            'badge' => 'NEW',
            'badge_color' => 'bg-gradient-to-r from-pink-500 to-purple-500',
            'submenu' => [
                ['label' => '📊 Dashboard', 'route' => 'admin.video-missions.index', 'description' => 'ภาพรวมระบบ'],
                ['label' => '🎬 จัดการภารกิจ', 'route' => 'admin.video-missions.missions', 'description' => 'สร้าง/แก้ไขภารกิจ'],
                ['label' => '📋 การทำภารกิจ', 'route' => 'admin.video-missions.completions', 'description' => 'ตรวจสอบการทำภารกิจ'],
                ['label' => '🏆 Rank Limits', 'route' => 'admin.video-missions.rank-limits', 'description' => 'ตั้งค่าลิมิตตาม Rank'],
                ['label' => '📈 รายงาน', 'route' => 'admin.video-missions.reports', 'description' => 'สถิติและรายงาน'],
                ['label' => '⚙️ ตั้งค่า', 'route' => 'admin.video-missions.settings', 'description' => 'ตั้งค่าระบบ'],
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
                ['label' => 'แดชบอร์ด', 'route' => 'admin.hrm.dashboard', 'icon' => 'fas fa-tachometer-alt'],
                ['label' => 'พนักงาน', 'route' => 'admin.hrm.employees.index', 'icon' => 'fas fa-users'],
                ['label' => 'แผนก', 'route' => 'admin.hrm.departments.index', 'icon' => 'fas fa-building'],
                ['label' => 'ตำแหน่ง', 'route' => 'admin.hrm.positions.index', 'icon' => 'fas fa-id-badge'],
                ['label' => 'การลา', 'route' => 'admin.hrm.leave.index', 'icon' => 'fas fa-calendar-minus'],
                ['label' => 'เงินเดือน', 'route' => 'admin.hrm.payroll.index', 'icon' => 'fas fa-money-check-alt'],
                ['label' => '---', 'route' => null], // Divider
                ['label' => 'ลงเวลา', 'route' => 'admin.hrm.attendance.index', 'icon' => 'fas fa-clock', 'description' => 'บันทึกเข้า-ออก'],
                ['label' => 'อบรม', 'route' => 'admin.hrm.training.courses.index', 'icon' => 'fas fa-chalkboard', 'description' => 'หลักสูตรฝึกอบรม'],
            ],
        ],

        [
            'id' => 'accounting',
            'label' => 'บัญชี (Accounting)',
            'icon' => '📒',
            'route' => null,
            'order' => 18,
            'permissions' => [],
            'submenu' => [
                ['label' => 'แดชบอร์ด', 'route' => 'admin.accounting.dashboard', 'icon' => 'fas fa-tachometer-alt'],
                ['label' => 'ใบแจ้งหนี้', 'route' => 'admin.accounting.invoices.index', 'icon' => 'fas fa-file-invoice'],
                ['label' => 'ค่าใช้จ่าย', 'route' => 'admin.accounting.expenses.index', 'icon' => 'fas fa-receipt'],
                ['label' => 'ผู้ติดต่อ', 'route' => 'admin.accounting.contacts.index', 'icon' => 'fas fa-address-book'],
                ['label' => 'สินค้า', 'route' => 'admin.accounting.products.index', 'icon' => 'fas fa-box'],
                ['label' => 'รายงาน', 'route' => 'admin.accounting.reports.index', 'icon' => 'fas fa-chart-bar'],
                ['label' => 'FlowAccount', 'route' => 'admin.accounting.flowaccount.index', 'icon' => 'fas fa-plug'],
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
                ['label' => 'ส่งการแจ้งเตือน', 'route' => 'admin.notifications.create', 'icon' => 'fas fa-paper-plane'],
                ['label' => 'ประวัติ', 'route' => 'admin.notifications.index', 'icon' => 'fas fa-history'],
                ['label' => 'เทมเพลต', 'route' => 'admin.notification-templates.index', 'icon' => 'fas fa-file-alt'],
                ['label' => 'สถิติ', 'route' => 'admin.notifications.statistics', 'icon' => 'fas fa-chart-pie'],
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
                ['label' => 'ภาพรวม', 'route' => 'admin.security.index', 'icon' => 'fas fa-shield-alt'],
                ['label' => 'Threat Intelligence', 'route' => 'admin.security.threat-intelligence', 'icon' => 'fas fa-bug'],
                ['label' => 'Analytics', 'route' => 'admin.security.analytics', 'icon' => 'fas fa-chart-line'],
                ['label' => 'ตั้งค่า OTP', 'route' => 'admin.otp.settings', 'icon' => 'fas fa-mobile-alt'],
                ['label' => 'ตั้งค่า 2FA', 'route' => 'admin.two-factor.settings', 'icon' => 'fas fa-key'],
                ['label' => '---', 'route' => null], // Divider
                ['label' => 'ป้องกันการละเมิด', 'route' => 'admin.anti-abuse.dashboard', 'icon' => 'fas fa-user-shield', 'description' => 'Anti-Abuse System'],
            ],
        ],

        [
            'id' => 'pages',
            'label' => 'จัดการเพจ',
            'icon' => '📄',
            'route' => 'admin.pages.index',
            'order' => 21,
            'permissions' => [],
        ],

        [
            'id' => 'games',
            'label' => 'เกม & ความบันเทิง',
            'icon' => '🎮',
            'route' => null,
            'order' => 21.5,
            'permissions' => [],
            'badge' => 'NEW',
            'badge_color' => 'bg-gradient-to-r from-green-500 to-teal-500',
            'submenu' => [
                ['label' => '🪙 ร้านค้าเหรียญ', 'route' => 'admin.coin-shop.index', 'description' => 'ขายเหรียญในเกม'],
                ['label' => '🐍 เกมงู', 'route' => 'admin.games.snake-io.monitor', 'description' => 'Snake.io Game'],
                ['label' => '📍 ติดตาม GPS', 'route' => 'admin.gps-monitoring.index', 'description' => 'Location Tracking'],
            ],
        ],

        [
            'id' => 'analytics',
            'label' => 'Analytics & SEO',
            'icon' => '📉',
            'route' => null,
            'order' => 22,
            'permissions' => [],
            'badge' => 'NEW',
            'badge_color' => 'bg-gradient-to-r from-blue-500 to-cyan-500',
            'submenu' => [
                ['label' => '📊 ภาพรวมระบบ', 'route' => 'admin.analytics.index', 'description' => 'System Analytics'],
                ['label' => '---', 'route' => null], // Divider
                ['label' => '👁️ สถิติการเข้าชม', 'route' => 'admin.analytics.page-views.index', 'badge' => 'NEW', 'badge_color' => 'bg-gradient-to-r from-green-500 to-emerald-500', 'description' => 'Page Views Analytics'],
                ['label' => '   ├─ ⚡ Real-time', 'route' => 'admin.analytics.page-views.realtime', 'description' => 'ดูการเข้าชมแบบเรียลไทม์'],
                ['label' => '   ├─ 📄 หน้าทั้งหมด', 'route' => 'admin.analytics.page-views.pages', 'description' => 'รายละเอียดแต่ละหน้า'],
                ['label' => '   ├─ 🔗 Traffic Sources', 'route' => 'admin.analytics.page-views.sources', 'description' => 'แหล่งที่มาของผู้เยี่ยมชม'],
                ['label' => '   └─ 📱 อุปกรณ์', 'route' => 'admin.analytics.page-views.devices', 'description' => 'สถิติตามอุปกรณ์/Browser'],
                ['label' => '---', 'route' => null], // Divider
                ['label' => '🔍 SEO Settings', 'route' => 'admin.seo.index', 'description' => 'ตั้งค่า Meta Tags และ SEO'],
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
                ['label' => 'ตั้งค่าภาษา', 'route' => 'admin.settings.languages', 'icon' => 'fas fa-globe'],
            ],
        ],

        // =============================================
        // 🔮 ระบบดูดวง Multi-Channel - Fortune Telling
        // =============================================
        [
            'id' => 'fortune-telling',
            'label' => 'ระบบดูดวง',
            'icon' => '🔮',
            'route' => null,
            'order' => 24.3,
            'permissions' => [],
            'badge' => 'NEW',
            'badge_color' => 'bg-gradient-to-r from-purple-500 to-indigo-600',
            'submenu' => [
                ['label' => '🔮 ตั้งค่าระบบดูดวง', 'route' => 'admin.fortune.settings.index', 'description' => 'ตั้งค่า Facebook, AI, ระบบ Freemium'],
                ['label' => '📊 Dashboard', 'route' => 'admin.fortune.dashboard', 'description' => 'สถิติภาพรวม กราฟ รายได้'],
                ['label' => '✨ โหราศาสตร์', 'route' => 'admin.fortune.astrology.index', 'description' => 'ตารางเจ้าชนะ ดาวเคราะห์ ทดสอบ Birth Chart'],
                ['label' => '📱 ช่องทางรับข้อความ', 'route' => 'admin.fortune.channels.index', 'description' => 'Facebook Messenger, LINE Official Account'],
                ['label' => '📂 หมวดหมู่การทำนาย', 'route' => 'admin.fortune.categories.index', 'description' => 'ความรัก, การเงิน, สุขภาพ'],
                ['label' => '📜 ประวัติคำทำนาย', 'route' => 'admin.fortune.readings.index', 'description' => 'ดูประวัติการทำนายทั้งหมด (มีปุ่ม Export CSV ในหน้านี้)'],
                ['label' => '🖼️ แบนเนอร์ DM', 'route' => 'admin.fortune.banners.index', 'description' => 'อัพโหลด/จัดการแบนเนอร์ที่ส่งให้ลูกค้าใน DM (reaction/comment/welcome)'],
                ['label' => '💬 ข้อความชวนดูดวง (สุ่ม)', 'route' => 'admin.fortune.invite-messages.index', 'description' => 'คลังข้อความเชิญชวนแบบเนียน — ส่งแทนรูปเมื่อลูกค้าได้รูปในสัปดาห์นี้แล้ว (เพิ่ม/แก้/ลบได้)'],
                ['label' => '🎨 Payment Banner (QR+ธนาคาร)', 'route' => 'admin.fortune.payment-banner.index', 'description' => 'ฝัง Dynamic QR ใน banner ธนาคาร — anti-FB-detection + SMS checker จับคู่ได้'],
                ['label' => '🎯 เทคโอเวอร์ (แม่หมอคุยเอง)', 'route' => 'admin.fortune.takeover.index', 'description' => 'หยุด AI ให้แม่หมอ/แอดมินคุยเอง — LINE + Facebook'],
                ['label' => '🚫 ระบบคุก (แบน user)', 'route' => 'admin.fortune.bans.index', 'description' => 'แบน user ไม่ให้บอทคุยด้วย (สแปม/ก่อกวน) — แอดมินยังคุยผ่าน Inbox ได้'],
                ['label' => '📚 RAG Admin Q&A (เทรนบอทจากคำตอบแอดมิน)', 'route' => 'admin.fortune.admin-qa.index', 'description' => 'จับคู่ Q&A จาก FB Page Inbox → AI เลียนสไตล์แอดมินตอบ chat ลูกค้า'],
                ['label' => '🧠 คลังความรู้แม่หมอ (RAG)', 'route' => 'admin.fortune.knowledge.index', 'description' => 'จัดการตำราสุขภาพ/ฮวงจุ้ย/เจ้าที่/องค์เทพ/ไสยศาสตร์ ที่ AI ดึงไปทำนายตามไพ่ — แก้/เพิ่มได้'],
                ['label' => '👥 ผู้ใช้ดูดวง', 'route' => 'admin.fortune.users.index', 'description' => 'จัดการผู้ใช้ที่เคยดูดวง ส่งข้อความ เพิ่มเครดิต'],
                ['label' => '🎴 บุคลิกลูกค้า (RPG)', 'route' => 'admin.fortune.personas.index', 'description' => 'ระบบจดจำบุคลิกลูกค้า — RPG card, radar chart, level, rarity tier'],
                ['label' => '🎁 เครดิตฟรีรายคน', 'route' => 'admin.fortune.credits.index', 'description' => 'เพิ่ม/รีเซ็ตเครดิตดูฟรีเป็นรายคน'],
                ['label' => '📣 การตลาดอัตโนมัติ', 'route' => 'admin.fortune.marketing.index', 'description' => 'AI สร้างข้อความ + ตั้งเวลาส่งอัตโนมัติ'],
                ['label' => '🌟 ดวงรายวันอัตโนมัติ', 'route' => 'admin.fortune.horoscope.index', 'description' => 'AI สร้างดวง 7 วันเกิด + โพส FB/LINE อัตโนมัติ'],
                ['label' => '🌙 คอนเทนต์สายมูอัตโนมัติ', 'route' => 'admin.fortune.mystic.index', 'description' => 'โพส FB อัตโนมัติ — สายมู/แก้เคล็ด/ปัญหาชีวิต/สิ่งลี้ลับ/รู้หรือไม่ทั่วโลก'],
                ['label' => '🔮 Celtic Cross Tarot (99฿)', 'route' => 'admin.fortune.celtic-cross.index', 'description' => 'ดูดวงไพ่ยิปซีเต็มสำรับ 10 ใบ — toggle, ราคา, prompt, log Q&A'],
                ['label' => '📜 กติกาก่อนจองคิว (เด้งก่อนสร้างบิล)', 'route' => 'admin.fortune.consent.index', 'description' => 'แก้ข้อความกติกา + คลังรูปเตือน (สุ่ม) เด้งให้ลูกค้ายืนยันก่อนออก QR ค่าครู + เตือนตอนยกเลิก'],
                ['label' => '🚨 Emergency Recover (Celtic)', 'route' => 'admin.fortune.celtic-cross.emergency-recover', 'description' => 'กู้บิล Celtic ด่วน — ใส่เลขบิลแล้วระบบ re-push prompt ทันที'],
                ['label' => '🧾 ประวัติตรวจสลิป (SlipOK)', 'route' => 'admin.fortune.slip-logs.index', 'description' => 'ประวัติการตรวจสลิปทุกครั้ง — ส่งไปเช็คจริงไหม / สลิปซ้ำไหม / บิลไหน + กรอง'],
                ['label' => '🪪 SlipOK Account Pool', 'route' => 'admin.fortune.slipok-accounts.index', 'description' => 'หลายบัญชี SlipOK หมุนเวียนกัน quota ฟรี (~100/เดือน) ตัน — โหมด near-empty / failover / balance + auto-failover'],
                ['label' => '🎧 จัดการเสียง (Voice)', 'route' => 'admin.fortune.voice.index', 'description' => 'รวมที่เดียว — ตั้งค่าโมเดล/เสียง · คลังเสียงระบบ (กล่องกระตุ้น/กติกา/วันเกิด) กดฟัง/สร้าง/อัปโหลด · เสียงทำนาย · 🩺 ตรวจระบบ (provider test/storage/fail)'],
                ['label' => '🐛 Debug Tools', 'route' => 'admin.fortune.debug-tools.index', 'description' => 'Tail laravel.log + ทดสอบ AI sync — debug Fortune flows โดยไม่ต้อง SSH'],
                ['label' => '❓ คำถามรอตอบ', 'route' => 'admin.fortune.saved-questions.index', 'description' => 'คำถามที่ AI ตอบไม่ได้ รอแอดมินตอบกลับ'],
                ['label' => '📝 เทมเพลตตอบกลับ', 'route' => 'admin.fortune.response-templates.index', 'description' => 'จัดการเทมเพลตคำตอบ รูปภาพ QR Code'],
                ['label' => '💰 จัดการบิลดูดวง', 'route' => 'admin.fortune.billing.index', 'description' => 'ดูรายได้ บิลลอย การชำระเงิน'],
                ['label' => '📊 ภาพรวมคอมมิชชั่น', 'route' => 'admin.fortune.commissions.index', 'description' => 'สถิติคอมมิชชั่นดูดวง L1/L2'],
                ['label' => '⚙️ จัดการคอมมิชชั่น', 'route' => 'admin.fortune.commissions.manage', 'description' => 'อนุมัติ ปฏิเสธ จ่ายเงิน ปรับจำนวน'],
                ['label' => '🌳 ผังสายงานดูดวง', 'route' => 'admin.fortune.referral-tree.index', 'description' => 'ดูโครงสร้างสายงาน L1/L2'],
                ['label' => '🎨 Rich Menu Editor', 'route' => 'admin.fortune.rich-menu.editor', 'description' => 'แก้ไขปุ่ม สี ข้อความ Rich Menu + อัปโหลดภาพเอง'],
                ['label' => '📤 Rich Menu Deploy', 'route' => 'admin.fortune.rich-menu.index', 'description' => 'Deploy Rich Menu ไป LINE'],
                ['label' => '---', 'route' => null], // Divider
                ['label' => '🌙 ตั้งค่าดูดวงสาธารณะ', 'route' => 'admin.fortune.horoscope-public.settings', 'description' => 'เปิด/ปิด จำกัดฟรี SEO'],
                ['label' => '♈ 12 ราศี + ดวงรายวัน', 'route' => 'admin.fortune.horoscope-public.zodiac.index', 'description' => 'จัดการราศี + Generate ดวง AI'],
                ['label' => '💤 พจนานุกรมฝัน', 'route' => 'admin.fortune.horoscope-public.dream.index', 'description' => 'สัญลักษณ์ฝัน หมวดหมู่ ผลทำนาย'],
                ['label' => '📈 สถิติดูดวงสาธารณะ', 'route' => 'admin.fortune.horoscope-public.analytics', 'description' => 'กราฟ สถิติ Top ราศี/ฝัน'],
            ],
        ],

        [
            'id' => 'content-media',
            'label' => 'คอนเทนต์ & มีเดีย',
            'icon' => '📝',
            'route' => null,
            'order' => 24.5,
            'permissions' => [],
            'submenu' => [
                ['label' => 'WebP Image Converter', 'route' => 'admin.webp.index', 'icon' => 'fas fa-image'],
                ['label' => 'Page Builder', 'route' => 'admin.page-builder.index', 'icon' => 'fas fa-puzzle-piece'],
                ['label' => 'Tarot System', 'route' => 'admin.tarot.index', 'icon' => 'fas fa-magic'],
                // 🚧 (2026-05-04) Video Rewards ซ่อนชั่วคราว — VideoRewardAdminController เป็น API-only
                //    ทุก method return JSON ไม่มี Blade view — คลิกแล้วเจอ raw JSON แทน UI
                //    ดูบันทึกบักเต็ม: brain note "Bug — Video Rewards admin missing UI (API-only controller)"
                //    ['label' => 'Video Rewards', 'route' => 'admin.video-rewards.dashboard', 'icon' => 'fas fa-video'],
            ],
        ],

        [
            'id' => 'ai-gen',
            'label' => 'AI Gen System',
            'icon' => '✨',
            'route' => null,
            'order' => 24.7,
            'permissions' => [],
            'badge' => 'NEW',
            'badge_color' => 'bg-gradient-to-r from-purple-500 to-pink-500',
            'submenu' => [
                ['label' => '📊 Dashboard', 'route' => 'admin.ai-gen.dashboard'],
                ['label' => '🤖 AI Providers', 'route' => 'admin.ai-gen.providers.index'],
                ['label' => '📦 Packages', 'route' => 'admin.ai-gen.packages.index'],
                ['label' => '🎁 Free Quotas', 'route' => 'admin.ai-gen.quotas.index'],
                ['label' => '📋 Usage Logs', 'route' => 'admin.ai-gen.usage-logs'],
                ['label' => '⚙️ ตั้งค่าระบบ', 'route' => 'admin.ai-gen.settings'],
                ['label' => '🎉 โปรโมชั่น', 'route' => 'admin.ai-gen.promotions.index'],
            ],
        ],

        [
            'id' => 'video-automation',
            'label' => 'Video Automation',
            'icon' => '🎥',
            'route' => null,
            'order' => 24.8,
            'permissions' => [],
            'badge' => 'NEW',
            'badge_color' => 'bg-gradient-to-r from-purple-600 to-pink-600',
            'submenu' => [
                ['label' => '📊 Dashboard', 'route' => 'admin.platform-revenue.video-automation.dashboard', 'description' => 'ภาพรวมระบบ Video Automation'],
                ['label' => '🎨 เทมเพลต', 'route' => 'admin.platform-revenue.video-automation.templates', 'description' => 'จัดการเทมเพลตวิดีโอ'],
                ['label' => '📁 โปรเจกต์', 'route' => 'admin.platform-revenue.video-automation.projects', 'description' => 'สร้างและจัดการโปรเจกต์'],
                ['label' => '⏰ Jobs', 'route' => 'admin.platform-revenue.video-automation.jobs', 'description' => 'ติดตามงานที่กำลังทำ'],
                ['label' => '📅 Schedules', 'route' => 'admin.platform-revenue.video-automation.schedules', 'description' => 'ตั้งเวลาสร้างอัตโนมัติ'],
                ['label' => '---', 'route' => null], // Divider
                ['label' => '🌐 Platforms', 'route' => 'admin.platform-revenue.video-automation.platforms', 'description' => 'เชื่อมต่อ YouTube, Facebook, TikTok, Lemon8'],
                ['label' => '📜 ประวัติการโพสต์', 'route' => 'admin.platform-revenue.video-automation.publish-history', 'description' => 'ดูประวัติการเผยแพร่วิดีโอ'],
                ['label' => '---', 'route' => null], // Divider
                ['label' => '⚙️ ตั้งค่า API', 'route' => 'admin.platform-revenue.video-automation.settings', 'description' => 'Suno AI, Freepik, YouTube'],
                ['label' => '📖 คู่มือการใช้งาน', 'route' => 'admin.platform-revenue.video-automation.documentation', 'description' => 'วิธีใช้งานและขอ API Key'],
            ],
        ],

        [
            'id' => 'forum',
            'label' => 'ฟอรั่มชุมชน',
            'icon' => '💬',
            'route' => null,
            'order' => 24.9,
            'permissions' => [],
            'badge' => 'NEW',
            'badge_color' => 'bg-gradient-to-r from-blue-500 to-purple-500',
            'submenu' => [
                ['label' => '📁 หมวดหมู่', 'route' => 'admin.platform-revenue.forum.categories.index', 'description' => 'จัดการหมวดหมู่'],
                ['label' => '📝 กระทู้ทั้งหมด', 'route' => 'admin.platform-revenue.forum.threads.index', 'description' => 'จัดการกระทู้'],
                ['label' => '🚨 รายงาน', 'route' => 'admin.platform-revenue.forum.reports.index', 'description' => 'รายงานเนื้อหาไม่เหมาะสม', 'badge' => 'count', 'badge_color' => 'bg-red-500'],
                ['label' => '🏆 ถ้วยรางวัล', 'route' => 'admin.platform-revenue.forum.trophies.index', 'description' => 'จัดการ Trophies'],
                ['label' => '📈 สถิติ', 'route' => 'admin.platform-revenue.forum.analytics.index', 'description' => 'สถิติการใช้งาน'],
                ['label' => '⚙️ ตั้งค่า', 'route' => 'admin.platform-revenue.forum.settings.index', 'description' => 'ตั้งค่าฟอรั่ม'],
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
                ['label' => 'ตั้งค่าทั่วไป', 'route' => 'admin.settings.index', 'icon' => 'fas fa-cog'],
                ['label' => 'จัดการสิทธิ์เมนู', 'route' => 'admin.menu-management.index', 'icon' => 'fas fa-bars', 'badge' => 'NEW', 'badge_color' => 'bg-gradient-to-r from-purple-500 to-pink-500', 'description' => 'เปิด/ปิด จัดเรียงเมนูตาม Role'],
                // =====================================================
                // 📱 Mobile App Management (3 อย่างเท่านั้น)
                // แอพเป็น Standalone - Admin ควบคุมได้เฉพาะ:
                // 1. Push Notifications
                // 2. Banner โฆษณา
                // 3. Device Analytics
                // =====================================================
                ['label' => 'Mobile App', 'route' => 'admin.mobile-app.index', 'icon' => 'fas fa-mobile-alt', 'badge' => 'NEW', 'badge_color' => 'bg-gradient-to-r from-blue-500 to-cyan-500', 'description' => 'จัดการแอพมือถือ'],
                ['label' => 'Push Notifications', 'route' => 'admin.mobile-app.push.index', 'icon' => 'fas fa-bell', 'description' => 'ส่งข้อความถึงผู้ใช้'],
                ['label' => 'Banner โฆษณา', 'route' => 'admin.mobile-app.banners.index', 'icon' => 'fas fa-ad', 'description' => 'จัดการแบนเนอร์ในแอพ'],
                ['label' => 'Device Analytics', 'route' => 'admin.mobile-app.analytics.index', 'icon' => 'fas fa-chart-bar', 'description' => 'สถิติเครื่องที่ลงทะเบียน'],
                ['label' => 'ตั้งค่า OCR', 'route' => 'admin.settings.ocr', 'icon' => 'fas fa-file-image'],
                ['label' => 'จัดการ API', 'route' => 'admin.api-management.endpoints.index', 'icon' => 'fas fa-code'],
                ['label' => 'API Keys', 'route' => 'admin.api-management.keys.index', 'icon' => 'fas fa-key'],
                ['label' => 'จัดการข้อมูลทดสอบ', 'route' => 'admin.demo-data.index', 'icon' => 'fas fa-broom'],
                ['label' => 'รีเซ็ตระบบ', 'route' => 'admin.system-reset.index', 'icon' => 'fas fa-redo'],
                ['label' => '---', 'route' => null], // Divider
                ['label' => 'จัดการหน้าแรก', 'route' => 'admin.homepage-manager.index', 'icon' => 'fas fa-home', 'description' => 'ปรับแต่งหน้า Homepage'],
                ['label' => 'ไอคอน', 'route' => 'admin.icons.index', 'icon' => 'fas fa-icons', 'description' => 'จัดการไอคอนระบบ'],
                ['label' => 'เครื่องมือลอย', 'route' => 'admin.floating-tools.index', 'icon' => 'fas fa-toolbox', 'description' => 'Floating Widgets'],
                ['label' => 'Cloudflare', 'route' => 'admin.cloudflare.index', 'icon' => 'fas fa-cloud', 'description' => 'จัดการ CDN'],
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
                ['label' => 'ฉลาก Barcode', 'route' => 'seller.pos.labels.index'],
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
            'id' => 'shipping',
            'label' => 'จัดการการจัดส่ง',
            'icon' => '📦',
            'route' => null,
            'order' => 3.5,
            'permissions' => [],
            'badge' => 'NEW',
            'badge_color' => 'bg-gradient-to-r from-cyan-500 to-blue-500',
            'submenu' => [
                ['label' => '📋 รอจัดส่ง', 'route' => 'seller.orders.pending-shipping', 'description' => 'คำสั่งซื้อที่รอกรอกเลขพัสดุ'],
                ['label' => '🚚 จัดส่งแล้ว', 'route' => 'seller.orders.shipped', 'description' => 'คำสั่งซื้อที่จัดส่งแล้ว'],
                ['label' => '✅ สำเร็จ', 'route' => 'seller.orders.delivered', 'description' => 'คำสั่งซื้อที่ส่งถึงแล้ว'],
            ],
        ],

        [
            'id' => 'customer-chat',
            'label' => 'แชทกับลูกค้า',
            'icon' => '💬',
            'route' => null,
            'order' => 3.6,
            'permissions' => [],
            'badge' => 'NEW',
            'badge_color' => 'bg-gradient-to-r from-green-500 to-emerald-500',
            'submenu' => [
                ['label' => '📩 ข้อความทั้งหมด', 'route' => 'seller.messages.index', 'description' => 'ดูข้อความทั้งหมด'],
                ['label' => '🔔 ยังไม่อ่าน', 'route' => 'seller.messages.unread', 'description' => 'ข้อความที่ยังไม่ได้อ่าน', 'badge' => 'count', 'badge_color' => 'bg-red-500'],
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
            'id' => 'direct-payment',
            'label' => 'รับชำระเงินตรง',
            'icon' => '🏦',
            'route' => null,
            'order' => 4.5,
            'permissions' => [],
            'badge' => 'Enterprise',
            'badge_color' => 'bg-gradient-to-r from-yellow-500 to-amber-500',
            'submenu' => [
                ['label' => '📊 ภาพรวม', 'route' => 'seller.sms-gateway.index', 'description' => 'แดชบอร์ดระบบรับชำระเงินตรง'],
                ['label' => '📱 อุปกรณ์', 'route' => 'seller.sms-gateway.devices', 'description' => 'จัดการอุปกรณ์ SMS Checker'],
                ['label' => '🏦 บัญชีธนาคาร', 'route' => 'seller.sms-gateway.bank-accounts', 'description' => 'จัดการบัญชีรับชำระเงิน'],
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
            'hide_if_kyc_verified' => true, // ซ่อนเมนูเมื่อ KYC approved แล้ว
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
                ['label' => 'ช๊อปสินค้า', 'route' => 'storefront.index'],
                ['label' => 'คำสั่งซื้อของฉัน', 'route' => 'orders.index'],
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
            'id' => 'service-booking',
            'label' => 'จองบริการ',
            'icon' => '🔧',
            'route' => null,
            'order' => 5.5,
            'permissions' => [],
            'badge' => 'NEW',
            'badge_color' => 'bg-gradient-to-r from-purple-500 to-pink-500',
            'submenu' => [
                ['label' => '🔍 ค้นหาบริการ', 'route' => 'user.services.index', 'description' => 'เลือกบริการที่ต้องการ'],
                ['label' => '📅 การจองของฉัน', 'route' => 'user.bookings.index', 'description' => 'ดูประวัติการจอง'],
                ['label' => '⭐ รีวิวบริการ', 'route' => 'user.service-reviews.index', 'description' => 'รีวิวและให้คะแนน'],
                ['label' => '👷 สมัครเป็นผู้ให้บริการ', 'route' => 'provider.register', 'description' => 'ลงทะเบียนเป็น Provider', 'badge' => '💼', 'badge_color' => 'bg-gradient-to-r from-green-500 to-emerald-500', 'condition' => 'hideIfProvider'],
            ],
        ],

        [
            'id' => 'provider-panel',
            'label' => 'งานของฉัน (Provider)',
            'icon' => '👷',
            'route' => null,
            'order' => 5.7,
            'permissions' => [],
            'badge' => 'PRO',
            'badge_color' => 'bg-gradient-to-r from-orange-500 to-red-500',
            'condition' => 'hasProviderAccess', // ต้องเป็น Provider ถึงจะเห็น
            'submenu' => [
                ['label' => '📊 Dashboard งาน', 'route' => 'provider.bookings.index'],
                ['label' => '🔔 งานใหม่ที่รอ', 'route' => 'provider.bookings.pending', 'badge' => 'count', 'badge_color' => 'bg-red-500'],
                ['label' => '✅ งานที่รับแล้ว', 'route' => 'provider.bookings.index'],
                ['label' => '💰 รายได้ของฉัน', 'route' => 'provider.earnings.index'],
                ['label' => '📈 สถิติและรีวิว', 'route' => 'provider.stats.index'],
                ['label' => '⚙️ ตั้งค่า Provider', 'route' => 'provider.settings.index'],
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
            'id' => 'academy',
            'label' => 'ศูนย์การเรียนรู้',
            'icon' => '🎓',
            'route' => null,
            'order' => 6.1,
            'permissions' => [],
            'badge' => 'NEW',
            'badge_color' => 'bg-gradient-to-r from-indigo-500 to-purple-500',
            'submenu' => [
                ['label' => '🏠 หน้าหลัก', 'route' => 'user.academy.index', 'description' => 'ศูนย์การเรียนรู้'],
                ['label' => '📊 ความก้าวหน้าของฉัน', 'route' => 'user.academy.my-progress', 'description' => 'ติดตามผลการเรียน'],
            ],
        ],

        [
            'id' => 'forum',
            'label' => 'ฟอรั่มชุมชน',
            'icon' => '💬',
            'route' => null,
            'order' => 6.3,
            'permissions' => [],
            'badge' => 'NEW',
            'badge_color' => 'bg-gradient-to-r from-blue-500 to-purple-500',
            'submenu' => [
                ['label' => '🏠 หน้าแรกฟอรั่ม', 'route' => 'forum.index', 'description' => 'เข้าสู่ชุมชน'],
                ['label' => '📝 กระทู้ของฉัน', 'route' => 'forum.my-threads', 'description' => 'กระทู้ที่สร้างไว้'],
                ['label' => '💬 ความคิดเห็นของฉัน', 'route' => 'forum.my-posts', 'description' => 'โพสต์ที่ตอบไว้'],
                ['label' => '🔔 การแจ้งเตือน', 'route' => 'forum.notifications', 'description' => 'แจ้งเตือนจากฟอรั่ม'],
            ],
        ],

        [
            'id' => 'chatbot',
            'label' => 'ระบบบอทแชท',
            'icon' => '🤖',
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
            'id' => 'video-missions',
            'label' => 'ภารกิจดูคลิป',
            'icon' => '🎬',
            'route' => null,
            'order' => 6.9,
            'permissions' => [],
            'badge' => 'NEW',
            'badge_color' => 'bg-gradient-to-r from-pink-500 to-purple-500',
            'submenu' => [
                ['label' => '🎬 ภารกิจทั้งหมด', 'route' => 'user.video-missions.index', 'description' => 'ดูและทำภารกิจ'],
                ['label' => '📜 ประวัติการทำ', 'route' => 'user.video-missions.history', 'description' => 'ประวัติและรายได้'],
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
                ['label' => '⚠️ หนี้ค้างชำระ', 'route' => 'user.wallet.debts'],
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
                ['label' => '💎 TPIX Wallet', 'route' => 'user.tpix.wallet', 'description' => 'กระเป๋า TPIX'],
                ['label' => '📊 Staking', 'route' => 'user.staking.index', 'badge' => 'NEW', 'description' => 'Stake เหรียญรับผลตอบแทน'],
                ['label' => '🔄 DEX Trading', 'route' => 'user.dex.index', 'description' => 'เทรดคริปโต'],
            ],
        ],

        [
            'id' => 'tpix-tokens',
            'label' => 'TPIX Token',
            'icon' => '🪙',
            'route' => null,
            'order' => 8.3,
            'permissions' => [],
            'badge' => 'NEW',
            'badge_color' => 'bg-gradient-to-r from-amber-500 to-orange-500',
            'submenu' => [
                ['label' => '📖 คู่มือสร้างเหรียญ', 'route' => 'user.tokens.tutorial', 'description' => 'เรียนรู้การสร้างเหรียญของคุณเอง'],
                ['label' => '🪙 ตลาด Token', 'route' => 'user.tokens.index', 'description' => 'ดู Token ทั้งหมดในระบบ'],
                ['label' => '✨ สร้างเหรียญใหม่', 'route' => 'user.tokens.create', 'description' => 'สร้าง Token ของคุณเอง'],
                ['label' => '📁 เหรียญของฉัน', 'route' => 'user.tokens.my-tokens', 'description' => 'จัดการ Token ที่สร้างไว้'],
                ['label' => '💰 ยอดคงเหลือ', 'route' => 'user.tokens.my-balances', 'description' => 'ดูยอด Token ที่ถือครอง'],
            ],
        ],

        [
            'id' => 'nfc-cards',
            'label' => 'บัตร NFC',
            'icon' => 'fas fa-credit-card',
            'route' => 'user.nfc.index',
            'order' => 8.5,
            'permissions' => [],
            'badge' => 'NEW',
            'badge_color' => 'bg-gradient-to-r from-blue-500 to-purple-500',
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
                ['label' => '🎨 AI Generation', 'route' => 'user.ai-gen.index', 'badge' => 'NEW', 'description' => 'สร้างภาพด้วย AI'],
                ['label' => '🛒 Marketplace', 'route' => 'user.marketplace.index', 'description' => 'ซื้อขายบอท'],
            ],
        ],

        [
            'id' => 'coin-shop',
            'label' => 'ร้านค้าเหรียญ',
            'icon' => '🪙',
            'route' => 'user.coin-shop.index',
            'order' => 10.5,
            'permissions' => [],
            'badge' => 'NEW',
            'badge_color' => 'bg-gradient-to-r from-yellow-500 to-amber-500',
        ],

        [
            'id' => 'team',
            'label' => 'ทีมงาน',
            'icon' => '👥',
            'route' => 'user.prospects.index',
            'order' => 11,
            'permissions' => [],
            'submenu' => [
                ['label' => 'ผู้มุ่งหวัง', 'route' => 'user.prospects.index', 'icon' => '🎯'],
                ['label' => 'ทีมของฉัน', 'route' => 'user.mlm.team', 'icon' => '👥'],
                ['label' => 'ลีดเดอร์บอร์ด', 'route' => 'user.ranks.leaderboard', 'icon' => '🏆'],
                ['label' => '---', 'route' => null],
                ['label' => '📝 รับสมัครสมาชิก', 'route' => 'user.marketing.recruit.index', 'description' => 'ชวนเพื่อนสมัคร'],
                ['label' => '🔄 โอนย้ายทีม', 'route' => 'user.team-transfer.index', 'description' => 'จัดการสายงาน'],
                ['label' => '⭐ อัพเกรดดาว', 'route' => 'user.star-upgrade.index', 'description' => 'เลื่อนระดับ Star'],
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
                ['label' => '📊 แดชบอร์ดการตลาด', 'route' => 'user.marketing.recruit.index', 'description' => 'ภาพรวมการตลาด'],
                ['label' => 'สร้าง QR Code & Barcode', 'route' => 'qr-barcode.index'],
                ['label' => 'จำลองรายได้', 'route' => 'user.mlm.income-simulator'],
                ['label' => 'จำลองเงินปันผล', 'route' => 'user.mlm.dividend-simulator'],
            ],
        ],

        [
            'id' => 'fortune-referral',
            'label' => 'ดูดวง - ปันผล',
            'icon' => '🔮',
            'route' => null,
            'order' => 12.5,
            'permissions' => [],
            'submenu' => [
                ['label' => '💰 คอมมิชชั่นดูดวง', 'route' => 'user.fortune-referral.commissions', 'icon' => '💰', 'description' => 'รายได้จากการแนะนำดูดวง'],
                ['label' => '📢 ชวนเพื่อนดูดวง', 'route' => 'user.fortune-referral.recruit', 'icon' => '📢', 'description' => 'แชร์ลิงก์เชิญเพื่อน'],
                ['label' => '🔮 ผังสายงานดูดวง', 'route' => 'user.fortune-referral.tree', 'icon' => '🔮', 'description' => 'ดูโครงสร้างสายงานดูดวง'],
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

        [
            'id' => 'wealth-guide',
            'label' => 'หนังสือเส้นทางเศรษฐี',
            'icon' => '📖',
            'route' => null,
            'order' => 15,
            'permissions' => [],
            'badge' => 'HOT',
            'badge_color' => 'bg-gradient-to-r from-yellow-500 to-orange-500',
            'submenu' => [
                ['label' => '📚 เส้นทางเศรษฐี (คู่มือเริ่มต้น)', 'route' => 'user.wealth-guide', 'description' => 'เรียนรู้พื้นฐานสู่ความสำเร็จ'],
                ['label' => '💎 เส้นทางเศรษฐี PRO', 'route' => 'user.wealth-guide-pro', 'description' => 'เทคนิคขั้นสูงสำหรับมืออาชีพ', 'badge' => 'PRO', 'badge_color' => 'bg-gradient-to-r from-purple-500 to-pink-500'],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Instructor Menu Items
    |--------------------------------------------------------------------------
    |
    | เมนูสำหรับ Instructor Dashboard (ผู้สอน)
    | แสดงเมื่อ user มี role = 'instructor' หรือเป็นเจ้าของคอร์ส
    |
    */

    'instructor' => [
        [
            'id' => 'dashboard',
            'label' => 'แดชบอร์ด',
            'icon' => '📊',
            'route' => 'admin.instructor.dashboard',
            'order' => 0,
            'permissions' => [],
        ],

        [
            'id' => 'courses',
            'label' => 'คอร์สของฉัน',
            'icon' => '📚',
            'route' => null,
            'order' => 1,
            'permissions' => [],
            'submenu' => [
                ['label' => '📋 รายการคอร์ส', 'route' => 'admin.instructor.courses.index', 'description' => 'คอร์สทั้งหมดของฉัน'],
                ['label' => '➕ สร้างคอร์สใหม่', 'route' => 'admin.instructor.courses.create', 'description' => 'เพิ่มคอร์สเรียนใหม่'],
            ],
        ],

        [
            'id' => 'earnings',
            'label' => 'รายได้',
            'icon' => '💰',
            'route' => 'admin.instructor.earnings',
            'order' => 2,
            'permissions' => [],
        ],

        [
            'id' => 'profile',
            'label' => 'โปรไฟล์',
            'icon' => '👤',
            'route' => 'user.profile',
            'order' => 3,
            'permissions' => [],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Provider Menu Items
    |--------------------------------------------------------------------------
    |
    | เมนูสำหรับ Provider Dashboard (ผู้ให้บริการ)
    | แสดงเมื่อ user มี role = 'provider' หรือเป็นผู้ให้บริการ
    |
    */

    'provider' => [
        [
            'id' => 'dashboard',
            'label' => 'แดชบอร์ด',
            'icon' => '📊',
            'route' => 'provider.dashboard',
            'order' => 0,
            'permissions' => [],
        ],

        [
            'id' => 'bookings',
            'label' => 'รายการจอง',
            'icon' => '📅',
            'route' => null,
            'order' => 1,
            'permissions' => [],
            'submenu' => [
                ['label' => '📋 งานทั้งหมด', 'route' => 'provider.bookings.index', 'description' => 'รายการงานทั้งหมด'],
                ['label' => '⏳ งานรอรับ', 'route' => 'provider.bookings.pending', 'description' => 'งานที่รอการตอบรับ'],
            ],
        ],

        [
            'id' => 'profile',
            'label' => 'โปรไฟล์',
            'icon' => '👤',
            'route' => null,
            'order' => 2,
            'permissions' => [],
            'submenu' => [
                ['label' => '✏️ แก้ไขโปรไฟล์', 'route' => 'provider.profile.edit', 'description' => 'อัพเดทข้อมูล'],
                ['label' => '🏦 ข้อมูลธนาคาร', 'route' => 'provider.bank-info.update', 'description' => 'บัญชีรับเงิน'],
            ],
        ],

        [
            'id' => 'register',
            'label' => 'สมัครเป็นผู้ให้บริการ',
            'icon' => '📝',
            'route' => 'provider.register',
            'order' => 3,
            'permissions' => [],
        ],
    ],

];
