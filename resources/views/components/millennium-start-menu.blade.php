@props(['type' => 'admin'])

@php
    // Get user and role info
    $user = auth()->user();
    $logo = \App\Models\Setting::get('logo');
    $appName = \App\Models\Setting::get('app_name', 'TP-Affiliate');

    // Define comprehensive menu items with submenu support
    $menuItems = [];

    if ($type === 'admin') {
        $menuItems = [
            ['icon' => '📊', 'label' => 'แดชบอร์ด', 'url' => route('admin.dashboard'), 'color' => 'from-indigo-600 to-purple-600'],
            [
                'icon' => '👥',
                'label' => 'ผู้ใช้งาน',
                'color' => 'from-blue-600 to-cyan-600',
                'submenu' => [
                    ['label' => 'รายชื่อผู้ใช้', 'url' => route('admin.users.index')],
                    ['label' => 'สิทธิ์ผู้ใช้', 'url' => route('admin.users.permissions')],
                    ['label' => 'บทบาท (Roles)', 'url' => route('admin.roles.index')],
                ]
            ],
            ['icon' => '🪪', 'label' => 'ยืนยันตัวตน KYC', 'url' => route('admin.kyc.index'), 'color' => 'from-purple-600 to-pink-600'],
            ['icon' => '🎫', 'label' => 'Ticket Support', 'url' => route('admin.tickets.index'), 'color' => 'from-blue-500 to-indigo-500'],
            [
                'icon' => '🤖',
                'label' => 'AI Bots & ผู้ช่วย',
                'color' => 'from-violet-600 to-purple-600',
                'submenu' => [
                    ['label' => 'จัดการ AI Bots', 'url' => route('admin.ai-bots.index')],
                    ['label' => 'AI Providers', 'url' => route('admin.ai-providers.index')],
                    ['label' => 'ติดตั้ง AI', 'url' => route('admin.ai-installation.index')],
                    ['label' => 'Knowledge Base', 'url' => route('admin.knowledge-bases.index')],
                ]
            ],
            [
                'icon' => '🏨',
                'label' => 'จัดการโรงแรม',
                'color' => 'from-orange-600 to-amber-600',
                'submenu' => [
                    ['label' => 'โรงแรมทั้งหมด', 'url' => route('admin.hotels.index')],
                    ['label' => 'การจองทั้งหมด', 'url' => route('admin.hotels.bookings.index')],
                    ['label' => 'รายงานและสถิติ', 'url' => route('admin.hotels.reports')],
                    ['label' => 'จัดการรีวิว', 'url' => route('admin.hotels.reviews.index')],
                    ['label' => 'สิ่งอำนวยความสะดวก', 'url' => route('admin.hotels.facilities.index')],
                    ['label' => 'โปรโมชั่นพิเศษ', 'url' => route('admin.hotels.promotions.index')],
                ]
            ],
            [
                'icon' => '🛒',
                'label' => 'อีคอมเมิร์ซ',
                'color' => 'from-green-600 to-emerald-600',
                'submenu' => [
                    ['label' => 'สินค้าทั้งหมด', 'url' => route('admin.ecommerce.products.index')],
                    ['label' => 'คำสั่งซื้อ', 'url' => route('admin.ecommerce.orders.index')],
                    ['label' => 'หมวดหมู่', 'url' => route('admin.ecommerce.categories.index')],
                    ['label' => 'รีวิวสินค้า', 'url' => route('admin.ecommerce.reviews.index')],
                    ['label' => 'แดชบอร์ด', 'url' => route('admin.ecommerce.dashboard')],
                ]
            ],
            [
                'icon' => '🏪',
                'label' => 'ระบบ POS',
                'color' => 'from-teal-600 to-cyan-600',
                'submenu' => [
                    ['label' => 'แดชบอร์ด', 'url' => route('admin.pos.dashboard')],
                    ['label' => 'อุปกรณ์ POS', 'url' => route('admin.pos.devices.index')],
                    ['label' => 'ธุรกรรม', 'url' => route('admin.pos.transactions.index')],
                    ['label' => 'วิเคราะห์ข้อมูล', 'url' => route('admin.pos.analytics')],
                ]
            ],
            [
                'icon' => '💰',
                'label' => 'กระเป๋าเงิน THB',
                'color' => 'from-yellow-600 to-orange-600',
                'submenu' => [
                    ['label' => 'กระเป๋าเงินทั้งหมด', 'url' => route('admin.wallet.index')],
                    ['label' => 'ประวัติธุรกรรม', 'url' => route('admin.wallet.transactions')],
                    ['label' => 'คำขอถอนเงิน', 'url' => route('admin.withdrawals.pending')],
                    ['label' => 'ประวัติการถอน', 'url' => route('admin.withdrawals.index')],
                    ['label' => 'ตั้งค่า Payment', 'url' => route('admin.payment-gateways.index')],
                ]
            ],
            [
                'icon' => '₿',
                'label' => 'กระเป๋าคริปโต',
                'color' => 'from-amber-500 to-yellow-600',
                'submenu' => [
                    ['label' => 'แดชบอร์ด', 'url' => route('admin.crypto.dashboard')],
                    ['label' => 'ธุรกรรม', 'url' => route('admin.crypto.transactions')],
                    ['label' => 'คำขอถอน', 'url' => route('admin.crypto.withdrawals')],
                ]
            ],
            [
                'icon' => '💵',
                'label' => 'คอมมิชชั่น',
                'color' => 'from-green-500 to-emerald-600',
                'submenu' => [
                    ['label' => 'รายการทั้งหมด', 'url' => route('admin.commissions.index')],
                    ['label' => 'รายงานคอมมิชชั่น', 'url' => route('admin.mlm.commissions.index')],
                ]
            ],
            [
                'icon' => '📧',
                'label' => 'จัดการอีเมล',
                'color' => 'from-blue-600 to-indigo-600',
                'submenu' => [
                    ['label' => 'เทมเพลต', 'url' => route('admin.email.templates.index')],
                    ['label' => 'ผู้ให้บริการ', 'url' => route('admin.email.providers')],
                    ['label' => 'ประวัติการส่ง', 'url' => route('admin.email.logs')],
                ]
            ],
            [
                'icon' => '📱',
                'label' => 'LINE OA & AI',
                'color' => 'from-green-500 to-emerald-500',
                'submenu' => [
                    ['label' => 'ตั้งค่า LINE OA', 'url' => route('admin.line-oa.index')],
                    ['label' => 'AI Chat Bot', 'url' => route('admin.line-bot.ai.index')],
                    ['label' => 'Rich Menus', 'url' => route('admin.line-bot.rich-menus.index')],
                    ['label' => 'Flex Messages', 'url' => route('admin.line-bot.flex-messages.index')],
                    ['label' => 'Broadcast', 'url' => route('admin.line-bot.broadcast.index')],
                    ['label' => 'Avatar', 'url' => route('admin.line-bot.avatars.index')],
                    ['label' => 'Chat Widget', 'url' => route('admin.line-bot.chat-widget.index')],
                ]
            ],
            [
                'icon' => '🎓',
                'label' => 'Academy System',
                'color' => 'from-purple-600 to-pink-600',
                'submenu' => [
                    ['label' => 'คอร์สเรียน', 'url' => route('admin.academy.courses.index')],
                    ['label' => 'ใบประกาศ', 'url' => route('admin.academy.certificates.index')],
                    ['label' => 'ตั้งค่า', 'url' => route('admin.academy.settings.index')],
                ]
            ],
            [
                'icon' => '📚',
                'label' => 'Learning Center',
                'color' => 'from-indigo-500 to-blue-600',
                'submenu' => [
                    ['label' => 'บทความ', 'url' => route('admin.articles.index')],
                    ['label' => 'หมวดหมู่', 'url' => route('admin.categories.index')],
                    ['label' => 'ศูนย์เรียนรู้', 'url' => route('admin.learning-center.index')],
                ]
            ],
            [
                'icon' => '💎',
                'label' => 'MLM System',
                'color' => 'from-pink-600 to-rose-600',
                'submenu' => [
                    ['label' => 'สมาชิก MLM', 'url' => route('admin.mlm.members.index')],
                    ['label' => 'แผน MLM', 'url' => route('admin.mlm.plans.index')],
                    ['label' => 'ผังสายงาน', 'url' => route('admin.mlm.genealogy.index')],
                    ['label' => 'คอมมิชชั่น', 'url' => route('admin.mlm.commissions.index')],
                    ['label' => 'Product PV', 'url' => route('admin.mlm.product-pv.index')],
                    ['label' => 'รายงาน', 'url' => route('admin.mlm.reports.dashboard')],
                    ['label' => 'ตั้งค่า MLM', 'url' => route('admin.mlm.settings.index')],
                ]
            ],
            [
                'icon' => '📈',
                'label' => 'ระบบการตลาด',
                'color' => 'from-rose-600 to-pink-600',
                'submenu' => [
                    ['label' => 'Affiliates', 'url' => route('admin.affiliates.index')],
                    ['label' => 'โครงสร้างทีม', 'url' => route('admin.affiliates.tree')],
                    ['label' => 'ระบบรักษายอด', 'url' => route('admin.retention.index')],
                    ['label' => 'จัดการระดับ Rank', 'url' => route('admin.ranks.index')],
                    ['label' => 'การเลื่อนระดับ', 'url' => route('admin.ranks.promotions')],
                    ['label' => 'Cashback', 'url' => route('admin.cashback.index')],
                ]
            ],
            [
                'icon' => '👨‍💼',
                'label' => 'HRM (HR)',
                'color' => 'from-cyan-600 to-blue-600',
                'submenu' => [
                    ['label' => 'แดชบอร์ด', 'url' => route('admin.hrm.dashboard')],
                    ['label' => 'พนักงาน', 'url' => route('admin.hrm.employees.index')],
                    ['label' => 'แผนก', 'url' => route('admin.hrm.departments.index')],
                    ['label' => 'ตำแหน่ง', 'url' => route('admin.hrm.positions.index')],
                    ['label' => 'การลา', 'url' => route('admin.hrm.leave.index')],
                    ['label' => 'เงินเดือน', 'url' => route('admin.hrm.payroll.index')],
                ]
            ],
            [
                'icon' => '📊',
                'label' => 'บัญชี (Accounting)',
                'color' => 'from-emerald-600 to-green-600',
                'submenu' => [
                    ['label' => 'แดชบอร์ด', 'url' => route('admin.accounting.dashboard')],
                    ['label' => 'ใบแจ้งหนี้', 'url' => route('admin.accounting.invoices.index')],
                    ['label' => 'ค่าใช้จ่าย', 'url' => route('admin.accounting.expenses.index')],
                    ['label' => 'ผู้ติดต่อ', 'url' => route('admin.accounting.contacts.index')],
                    ['label' => 'สินค้า', 'url' => route('admin.accounting.products.index')],
                    ['label' => 'รายงาน', 'url' => route('admin.accounting.reports.index')],
                    ['label' => 'FlowAccount', 'url' => route('admin.accounting.flowaccount.index')],
                ]
            ],
            [
                'icon' => '🔔',
                'label' => 'การแจ้งเตือน',
                'color' => 'from-yellow-500 to-amber-600',
                'submenu' => [
                    ['label' => 'ส่งการแจ้งเตือน', 'url' => route('admin.notifications.create')],
                    ['label' => 'ประวัติ', 'url' => route('admin.notifications.index')],
                    ['label' => 'เทมเพลต', 'url' => route('admin.notification-templates.index')],
                    ['label' => 'สถิติ', 'url' => route('admin.notifications.statistics')],
                ]
            ],
            [
                'icon' => '🔒',
                'label' => 'ความปลอดภัย',
                'color' => 'from-red-600 to-rose-600',
                'submenu' => [
                    ['label' => 'ภาพรวม', 'url' => route('admin.security.index')],
                    ['label' => 'Threat Intelligence', 'url' => route('admin.security.threat-intelligence')],
                    ['label' => 'Analytics', 'url' => route('admin.security.analytics')],
                    ['label' => 'OTP Settings', 'url' => route('admin.otp.settings')],
                ]
            ],
            [
                'icon' => '📄',
                'label' => 'เพจ & SEO',
                'color' => 'from-slate-600 to-gray-600',
                'submenu' => [
                    ['label' => 'จัดการเพจ', 'url' => route('admin.pages.index')],
                    ['label' => 'SEO Settings', 'url' => route('admin.seo.index')],
                ]
            ],
            [
                'icon' => '📊',
                'label' => 'Analytics',
                'color' => 'from-purple-500 to-violet-600',
                'submenu' => [
                    ['label' => 'ภาพรวม', 'url' => route('admin.analytics.index')],
                    ['label' => 'Cookie Analytics', 'url' => route('admin.cookie-analytics.index')],
                ]
            ],
            [
                'icon' => '🎨',
                'label' => 'ธีม & UI',
                'color' => 'from-fuchsia-600 to-pink-600',
                'submenu' => [
                    ['label' => 'Theme Builder', 'url' => route('admin.themes.builder')],
                    ['label' => 'Windows UI', 'url' => route('admin.windows-ui.index')],
                    ['label' => 'Icons', 'url' => route('admin.icons.index')],
                    ['label' => 'Floating Tools', 'url' => route('admin.floating-tools.index')],
                ]
            ],
            [
                'icon' => '🌐',
                'label' => 'ภาษา & แปล',
                'color' => 'from-sky-600 to-blue-600',
                'submenu' => [
                    ['label' => 'การแปล', 'url' => route('admin.translations.index')],
                    ['label' => 'ตั้งค่าภาษา', 'url' => route('admin.settings.languages')],
                ]
            ],
            ['icon' => '⚙️', 'label' => 'ตั้งค่าระบบ', 'url' => route('admin.settings.index'), 'color' => 'from-gray-600 to-slate-600'],
        ];
    } elseif ($type === 'seller') {
        $menuItems = [
            ['icon' => '📊', 'label' => 'แดชบอร์ด', 'url' => route('seller.dashboard'), 'color' => 'from-cyan-600 to-blue-600'],
            [
                'icon' => '📦',
                'label' => 'สินค้า',
                'color' => 'from-blue-600 to-indigo-600',
                'submenu' => [
                    ['label' => 'รายการสินค้า', 'url' => route('seller.products.index')],
                    ['label' => 'เพิ่มสินค้า', 'url' => route('seller.products.create')],
                ]
            ],
            [
                'icon' => '🏪',
                'label' => 'ระบบ POS',
                'color' => 'from-green-500 to-emerald-600',
                'submenu' => [
                    ['label' => 'ขายสินค้า', 'url' => route('seller.pos.terminal')],
                    ['label' => 'แดชบอร์ด', 'url' => route('seller.pos.dashboard')],
                    ['label' => 'รายการขาย', 'url' => route('seller.pos.sales')],
                    ['label' => 'Session', 'url' => route('seller.pos.sessions')],
                    ['label' => 'ตั้งค่า POS', 'url' => route('seller.pos.settings')],
                ]
            ],
            [
                'icon' => '🛒',
                'label' => 'ยอดขาย',
                'color' => 'from-orange-600 to-amber-600',
                'submenu' => [
                    ['label' => 'คำสั่งซื้อ', 'url' => route('seller.orders.index')],
                    ['label' => 'รายงานยอดขาย', 'url' => route('seller.reports.sales')],
                ]
            ],
            [
                'icon' => '💰',
                'label' => 'กระเป๋าเงิน',
                'color' => 'from-yellow-600 to-orange-600',
                'submenu' => [
                    ['label' => 'กระเป๋าของฉัน', 'url' => route('seller.wallet.index')],
                    ['label' => 'ถอนเงิน', 'url' => route('seller.wallet.withdraw')],
                    ['label' => 'ประวัติการถอน', 'url' => route('seller.wallet.withdrawals')],
                ]
            ],
            ['icon' => '💵', 'label' => 'คอมมิชชั่น', 'url' => route('seller.commissions'), 'color' => 'from-green-500 to-emerald-600'],
            ['icon' => '📈', 'label' => 'วิเคราะห์', 'url' => route('seller.analytics'), 'color' => 'from-purple-600 to-pink-600'],
            ['icon' => '⚙️', 'label' => 'ตั้งค่าร้าน', 'url' => route('seller.settings'), 'color' => 'from-gray-600 to-slate-600'],
            ['icon' => '👤', 'label' => 'โปรไฟล์', 'url' => route('seller.profile'), 'color' => 'from-indigo-600 to-purple-600'],
        ];
    } else { // user
        $menuItems = [
            ['icon' => '📊', 'label' => 'แดชบอร์ด', 'url' => route('user.dashboard'), 'color' => 'from-indigo-600 to-purple-600'],
            ['icon' => '👤', 'label' => 'โปรไฟล์', 'url' => route('user.profile'), 'color' => 'from-blue-600 to-cyan-600'],
            ['icon' => '🪪', 'label' => 'ยืนยันตัวตน KYC', 'url' => route('user.kyc.index'), 'color' => 'from-purple-600 to-pink-600'],
            ['icon' => '💰', 'label' => 'คอมมิชชั่น', 'url' => route('user.commissions'), 'color' => 'from-yellow-600 to-orange-600'],
            [
                'icon' => '🛒',
                'label' => 'ช๊อปปิ้ง',
                'color' => 'from-green-600 to-teal-600',
                'submenu' => [
                    ['label' => 'ช๊อปสินค้า', 'url' => route('shop.index')],
                    ['label' => 'คำสั่งซื้อของฉัน', 'url' => route('user.orders.index')],
                    ['label' => 'รายการโปรด', 'url' => route('user.wishlist')],
                ]
            ],
            [
                'icon' => '🏨',
                'label' => 'โรงแรม',
                'color' => 'from-orange-600 to-amber-600',
                'submenu' => [
                    ['label' => 'จองโรงแรม', 'url' => route('hotels.index')],
                    ['label' => 'การจองของฉัน', 'url' => route('hotels.bookings.index')],
                ]
            ],
            ['icon' => '🎫', 'label' => 'Ticket Support', 'url' => route('user.tickets.index'), 'color' => 'from-blue-600 to-indigo-600'],
            [
                'icon' => '💳',
                'label' => 'กระเป๋าเงิน THB',
                'color' => 'from-indigo-600 to-purple-600',
                'submenu' => [
                    ['label' => 'กระเป๋าของฉัน', 'url' => route('user.wallet.index')],
                    ['label' => 'ประวัติธุรกรรม', 'url' => route('user.wallet.transactions')],
                    ['label' => 'ถอนเงิน', 'url' => route('user.wallet.withdraw')],
                    ['label' => 'ประวัติการถอน', 'url' => route('user.wallet.withdrawals')],
                    ['label' => 'ช่องทางรับเงิน', 'url' => route('user.wallet.payment-methods')],
                ]
            ],
            [
                'icon' => '₿',
                'label' => 'กระเป๋าคริปโต',
                'color' => 'from-amber-600 to-orange-600',
                'submenu' => [
                    ['label' => 'กระเป๋าคริปโต', 'url' => route('user.crypto-wallet.index')],
                    ['label' => 'จัดการกระเป๋า', 'url' => route('user.crypto-wallet.wallet-management')],
                    ['label' => 'ประวัติธุรกรรม', 'url' => route('user.crypto-wallet.transactions')],
                    ['label' => 'เทรดดิ้ง', 'url' => route('user.crypto-wallet.trading')],
                    ['label' => 'พอร์ตโฟลิโอ', 'url' => route('user.crypto-wallet.portfolio')],
                    ['label' => 'ฝากเหรียญ', 'url' => route('user.crypto-wallet.deposit')],
                    ['label' => 'ถอนเหรียญ', 'url' => route('user.crypto-wallet.withdraw')],
                    ['label' => 'ประวัติการถอน', 'url' => route('user.crypto-wallet.withdrawals')],
                    ['label' => 'แลกเปลี่ยน THB ↔ Crypto', 'url' => route('user.crypto-wallet.exchange')],
                ]
            ],
            [
                'icon' => '📈',
                'label' => 'การลงทุน ROI',
                'color' => 'from-purple-600 to-pink-600',
                'submenu' => [
                    ['label' => 'แดชบอร์ด', 'url' => route('user.investments.index')],
                    ['label' => 'แผนการลงทุน', 'url' => route('user.investments.plans')],
                    ['label' => 'ประวัติ ROI', 'url' => route('user.investments.history')],
                ]
            ],
            [
                'icon' => '🎓',
                'label' => 'เรียนรู้',
                'color' => 'from-violet-500 to-purple-600',
                'submenu' => [
                    ['label' => 'คอร์สของฉัน', 'url' => route('user.courses.index')],
                    ['label' => 'ใบประกาศ', 'url' => route('user.certificates.index')],
                    ['label' => 'ศูนย์เรียนรู้', 'url' => route('learning-center.index')],
                ]
            ],
            [
                'icon' => '🤖',
                'label' => 'AI Bots',
                'color' => 'from-cyan-500 to-blue-600',
                'submenu' => [
                    ['label' => 'บอทของฉัน', 'url' => route('user.ai-bots.index')],
                    ['label' => 'ตลาดบอท', 'url' => route('marketplace.index')],
                ]
            ],
            [
                'icon' => '👥',
                'label' => 'ทีมงาน',
                'color' => 'from-pink-600 to-rose-600',
                'submenu' => [
                    ['label' => 'ผู้แนะนำ', 'url' => route('user.referrals')],
                    ['label' => 'ผังสายงาน', 'url' => route('user.organization')],
                    ['label' => 'ผัง Binary', 'url' => route('user.organization.binary')],
                ]
            ],
            [
                'icon' => '💖',
                'label' => 'รักษายอด',
                'color' => 'from-red-600 to-pink-600',
                'submenu' => [
                    ['label' => 'สถานะพลังชีวิต', 'url' => route('user.retention.index')],
                    ['label' => 'ซ่อมสิทธิ์', 'url' => route('user.retention.repair')],
                    ['label' => 'เติมวันล่วงหน้า', 'url' => route('user.retention.advance-renewal')],
                ]
            ],
            [
                'icon' => '🎯',
                'label' => 'เครื่องมือการตลาด',
                'color' => 'from-violet-600 to-purple-600',
                'submenu' => [
                    ['label' => 'จำลองรายได้', 'url' => route('user.mlm.income-simulator')],
                    ['label' => 'เปรียบเทียบรายได้', 'url' => route('user.mlm.income-comparison')],
                    ['label' => 'จำลองการปันผล', 'url' => route('user.mlm.dividend-simulator')],
                ]
            ],
            ['icon' => '🎨', 'label' => 'ตั้งค่าธีม', 'url' => route('user.themes.index'), 'color' => 'from-purple-600 to-pink-600'],
        ];
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
    x-data="millenniumMenu()"
    x-transition:enter="transition ease-out duration-400"
    x-transition:enter-start="opacity-0 -translate-x-full"
    x-transition:enter-end="opacity-100 translate-x-0"
    x-transition:leave="transition ease-in duration-300"
    x-transition:leave-start="opacity-100 translate-x-0"
    x-transition:leave-end="opacity-0 -translate-x-full"
    class="fixed left-0 top-0 bottom-0 w-80 md:w-96 z-[70] millennium-start-menu"
    style="display: none;">

    <!-- RGB Glow Border -->
    <div class="absolute inset-0 millennium-rgb-glow"></div>

    <!-- Main Panel -->
    <div class="relative h-full bg-gradient-to-br from-gray-900 via-purple-900/30 to-blue-900/30 backdrop-blur-xl border-r-4 border-transparent overflow-hidden">

        <!-- Animated Background Pattern -->
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0 millennium-grid-pattern"></div>
        </div>

        <!-- Content Container -->
        <div class="relative h-full flex flex-col">

            <!-- Header Section -->
            <div class="p-5 bg-gradient-to-r from-pink-600 via-purple-600 to-blue-600 millennium-header-glow">
                <div class="flex items-center gap-3">
                    @if($logo)
                        <div class="w-14 h-14 rounded-xl overflow-hidden ring-3 ring-white/30 millennium-logo-pulse">
                            <img src="{{ asset($logo) }}" alt="{{ $appName }}" class="w-full h-full object-contain">
                        </div>
                    @else
                        <div class="w-14 h-14 bg-gradient-to-br from-cyan-400 to-blue-600 rounded-xl flex items-center justify-center ring-3 ring-white/30 millennium-logo-pulse">
                            <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M0 0h11v11H0V0zm13 0h11v11H13V0zM0 13h11v11H0V13zm13 0h11v11H13V13z"/>
                            </svg>
                        </div>
                    @endif

                    <div class="flex-1 min-w-0">
                        <h2 class="text-xl font-bold text-white drop-shadow-lg truncate">{{ $appName }}</h2>
                        @if($user)
                            <p class="text-sm text-blue-100 mt-1 font-semibold truncate">{{ $user->name }}</p>
                            <span class="inline-block mt-1 px-3 py-0.5 bg-white/20 backdrop-blur-sm rounded-full text-xs font-bold text-white">
                                {{ $type === 'admin' ? '👑 Admin' : ($type === 'seller' ? '🏪 Seller' : '👤 User') }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Menu Items Section -->
            <div class="flex-1 p-3 overflow-y-auto millennium-scrollbar">
                <div class="space-y-2">
                    @foreach($menuItems as $index => $item)
                        <div class="millennium-menu-group">
                            @if(isset($item['submenu']))
                                <!-- Menu Item with Submenu -->
                                <div>
                                    <!-- Main Menu Button -->
                                    <button
                                        @click="toggleSubmenu({{ $index }})"
                                        class="w-full group flex items-center gap-3 px-4 py-3 rounded-xl bg-gradient-to-r from-white/5 to-white/10 hover:from-pink-500/30 hover:to-purple-500/30 border border-white/10 hover:border-pink-400/50 transition-all duration-300 transform hover:scale-[1.02] hover:shadow-lg hover:shadow-pink-500/20 millennium-menu-item"
                                        :class="openMenus[{{ $index }}] ? 'from-pink-500/20 to-purple-500/20 border-pink-400/50' : ''">

                                        <!-- Icon -->
                                        <span class="text-3xl group-hover:scale-110 transition-transform duration-300 drop-shadow-lg">
                                            {{ $item['icon'] }}
                                        </span>

                                        <!-- Label -->
                                        <span class="text-base font-bold text-white group-hover:text-pink-200 transition-colors duration-300 flex-1 text-left">
                                            {{ $item['label'] }}
                                        </span>

                                        <!-- Chevron Arrow -->
                                        <svg
                                            class="w-5 h-5 ml-auto text-white/40 group-hover:text-pink-300 transition-all duration-300"
                                            :class="openMenus[{{ $index }}] ? 'rotate-90 text-pink-300' : ''"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                            stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </button>

                                    <!-- Submenu Items with Smooth Transition -->
                                    <div
                                        x-show="openMenus[{{ $index }}]"
                                        x-transition:enter="transition ease-out duration-200"
                                        x-transition:enter-start="opacity-0 -translate-y-2"
                                        x-transition:enter-end="opacity-100 translate-y-0"
                                        x-transition:leave="transition ease-in duration-150"
                                        x-transition:leave-start="opacity-100 translate-y-0"
                                        x-transition:leave-end="opacity-0 -translate-y-2"
                                        class="mt-1.5 ml-6 space-y-1.5"
                                        style="display: none;">
                                        @foreach($item['submenu'] as $subitem)
                                            <a
                                                href="{{ $subitem['url'] }}"
                                                class="flex items-center gap-2 px-3 py-2 rounded-lg bg-gradient-to-r from-white/5 to-white/10 hover:from-purple-500/30 hover:to-blue-500/30 border border-white/10 hover:border-purple-400/50 transition-all duration-300 transform hover:translate-x-1 hover:shadow-lg hover:shadow-purple-500/20 group">

                                                <!-- Bullet Point -->
                                                <span class="w-1.5 h-1.5 rounded-full bg-pink-400 group-hover:bg-purple-300 group-hover:scale-125 transition-all duration-300"></span>

                                                <!-- Submenu Label -->
                                                <span class="text-sm font-semibold text-white/90 group-hover:text-white transition-colors duration-300 flex-1">
                                                    {{ $subitem['label'] }}
                                                </span>

                                                <!-- Small Arrow -->
                                                <svg class="w-4 h-4 text-white/30 group-hover:text-purple-300 group-hover:translate-x-1 transition-all duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
                                                </svg>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <!-- Regular Menu Item without Submenu -->
                                <a
                                    href="{{ $item['url'] }}"
                                    class="group flex items-center gap-3 px-4 py-3 rounded-xl bg-gradient-to-r from-white/5 to-white/10 hover:from-pink-500/30 hover:to-purple-500/30 border border-white/10 hover:border-pink-400/50 transition-all duration-300 transform hover:scale-[1.02] hover:shadow-lg hover:shadow-pink-500/20 millennium-menu-item">

                                    <!-- Icon -->
                                    <span class="text-3xl group-hover:scale-110 transition-transform duration-300 drop-shadow-lg">
                                        {{ $item['icon'] }}
                                    </span>

                                    <!-- Label -->
                                    <span class="text-base font-bold text-white group-hover:text-pink-200 transition-colors duration-300 flex-1">
                                        {{ $item['label'] }}
                                    </span>

                                    <!-- Arrow -->
                                    <svg class="w-5 h-5 ml-auto text-white/40 group-hover:text-pink-300 group-hover:translate-x-1 transition-all duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Footer Section -->
            <div class="p-3 bg-gradient-to-r from-gray-900 via-purple-900/50 to-blue-900/50 border-t border-white/10">
                @if($user)
                    <div class="flex items-center gap-2">
                        <!-- User Avatar -->
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-pink-500 via-purple-500 to-blue-500 flex items-center justify-center text-white font-bold text-sm ring-2 ring-white/30">
                            {{ substr($user->name, 0, 2) }}
                        </div>

                        <!-- User Info & Logout -->
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-white truncate">{{ $user->name }}</p>
                            <p class="text-xs text-gray-300 truncate">{{ $user->email }}</p>
                        </div>

                        <!-- Logout Button -->
                        <button
                            onclick="document.getElementById('millennium-logout-form').submit()"
                            class="w-10 h-10 rounded-xl bg-gradient-to-br from-red-500 to-pink-600 hover:from-red-600 hover:to-pink-700 flex items-center justify-center text-white transition-all duration-300 transform hover:scale-110 hover:rotate-12 shadow-lg hover:shadow-red-500/50"
                            title="ออกจากระบบ">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                            </svg>
                        </button>

                        <form id="millennium-logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                            @csrf
                        </form>
                    </div>
                @else
                    <!-- Login/Register Buttons -->
                    <div class="flex gap-2">
                        <a href="{{ route('login') }}" class="flex-1 px-3 py-2 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white text-sm font-bold rounded-xl text-center transition-all duration-300 transform hover:scale-105 shadow-lg">
                            เข้าสู่ระบบ
                        </a>
                        <a href="{{ route('register') }}" class="flex-1 px-3 py-2 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white text-sm font-bold rounded-xl text-center transition-all duration-300 transform hover:scale-105 shadow-lg">
                            สมัครสมาชิก
                        </a>
                    </div>
                @endif
            </div>

        </div>
    </div>
</div>

<script>
    function millenniumMenu() {
        return {
            openMenus: {},
            toggleSubmenu(index) {
                this.openMenus[index] = !this.openMenus[index];
            }
        }
    }
</script>

<style>
    /* Millennium RGB Glow Effect */
    @keyframes millenniumRgbGlow {
        0%, 100% {
            box-shadow:
                0 0 20px rgba(255, 0, 128, 0.8),
                0 0 40px rgba(255, 0, 128, 0.6),
                0 0 60px rgba(255, 0, 128, 0.4);
        }
        25% {
            box-shadow:
                0 0 20px rgba(0, 240, 255, 0.8),
                0 0 40px rgba(0, 240, 255, 0.6),
                0 0 60px rgba(0, 240, 255, 0.4);
        }
        50% {
            box-shadow:
                0 0 20px rgba(127, 0, 255, 0.8),
                0 0 40px rgba(127, 0, 255, 0.6),
                0 0 60px rgba(127, 0, 255, 0.4);
        }
        75% {
            box-shadow:
                0 0 20px rgba(255, 215, 0, 0.8),
                0 0 40px rgba(255, 215, 0, 0.6),
                0 0 60px rgba(255, 215, 0, 0.4);
        }
    }

    .millennium-rgb-glow {
        animation: millenniumRgbGlow 4s ease-in-out infinite;
        pointer-events: none;
    }

    /* Header Glow */
    @keyframes headerGlow {
        0%, 100% { filter: drop-shadow(0 0 15px rgba(255, 0, 128, 0.5)); }
        33% { filter: drop-shadow(0 0 15px rgba(0, 240, 255, 0.5)); }
        66% { filter: drop-shadow(0 0 15px rgba(127, 0, 255, 0.5)); }
    }

    .millennium-header-glow {
        animation: headerGlow 6s ease-in-out infinite;
    }

    /* Logo Pulse */
    @keyframes logoPulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }

    .millennium-logo-pulse {
        animation: logoPulse 2s ease-in-out infinite;
    }

    /* Grid Pattern */
    .millennium-grid-pattern {
        background-image:
            linear-gradient(rgba(255, 255, 255, 0.05) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255, 255, 255, 0.05) 1px, transparent 1px);
        background-size: 20px 20px;
        animation: gridMove 20s linear infinite;
    }

    @keyframes gridMove {
        0% { background-position: 0 0; }
        100% { background-position: 20px 20px; }
    }

    /* Menu Item Glow on Hover */
    .millennium-menu-item:hover {
        box-shadow:
            0 0 15px rgba(236, 72, 153, 0.5),
            0 0 25px rgba(168, 85, 247, 0.3);
    }

    /* Custom Scrollbar */
    .millennium-scrollbar::-webkit-scrollbar {
        width: 10px;
    }

    .millennium-scrollbar::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.05);
        border-radius: 5px;
    }

    .millennium-scrollbar::-webkit-scrollbar-thumb {
        background: linear-gradient(180deg, #ec4899, #a855f7, #3b82f6);
        border-radius: 5px;
    }

    .millennium-scrollbar::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(180deg, #f472b6, #c084fc, #60a5fa);
    }

    /* Submenu Animations */
    [x-cloak] {
        display: none !important;
    }
</style>
