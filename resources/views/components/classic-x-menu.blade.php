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
            ['icon' => 'fa-dashboard', 'label' => 'แดชบอร์ด', 'url' => safeRoute('admin.dashboard')],
            [
                'icon' => 'fa-users',
                'label' => 'ผู้ใช้งาน',
                'url' => '#',
                'submenu' => [
                    ['label' => 'รายชื่อผู้ใช้', 'url' => safeRoute('admin.users.index')],
                    ['label' => 'บทบาท (Roles)', 'url' => safeRoute('admin.roles.index')],
                ]
            ],
            ['icon' => 'fa-id-card', 'label' => 'ยืนยันตัวตน KYC', 'url' => safeRoute('admin.kyc.index')],
            ['icon' => 'fa-ticket', 'label' => 'Ticket Support', 'url' => safeRoute('admin.tickets.index')],
            [
                'icon' => 'fa-robot',
                'label' => 'AI Bots & ผู้ช่วย',
                'url' => '#',
                'submenu' => [
                    ['label' => 'จัดการ AI Bots', 'url' => safeRoute('admin.ai-bots.index')],
                    ['label' => 'AI Providers', 'url' => safeRoute('admin.ai-providers.index')],
                    ['label' => 'ติดตั้ง AI', 'url' => safeRoute('admin.ai-installation.index')],
                    ['label' => 'AI Monitoring', 'url' => safeRoute('admin.ai-monitoring.index')],
                    ['label' => 'Knowledge Bases', 'url' => safeRoute('admin.knowledge-bases.index')],
                ]
            ],
            [
                'icon' => 'fa-sliders',
                'label' => 'Smart Slider Pro',
                'url' => '#',
                'badge' => 'NEW',
                'submenu' => [
                    ['label' => 'Dashboard', 'url' => safeRoute('admin.smart-sliders.index')],
                    ['label' => 'สร้าง Slider ใหม่', 'url' => safeRoute('admin.smart-sliders.create')],
                    ['label' => 'Template Gallery', 'url' => safeRoute('admin.smart-sliders.index')],
                ]
            ],
            [
                'icon' => 'fa-hotel',
                'label' => 'จัดการโรงแรม',
                'url' => '#',
                'submenu' => [
                    ['label' => 'โรงแรมทั้งหมด', 'url' => safeRoute('admin.hotels.index')],
                    ['label' => 'การจองทั้งหมด', 'url' => safeRoute('admin.hotels.bookings.index')],
                    ['label' => 'สถิติการจอง', 'url' => safeRoute('admin.hotels.bookings.analytics')],
                    ['label' => 'จัดการรีวิว', 'url' => safeRoute('admin.hotels.reviews.index')],
                ]
            ],
            [
                'icon' => 'fa-shopping-cart',
                'label' => 'อีคอมเมิร์ซ',
                'url' => '#',
                'submenu' => [
                    ['label' => 'แดชบอร์ด', 'url' => safeRoute('admin.ecommerce.dashboard')],
                    ['label' => 'สินค้าทั้งหมด', 'url' => safeRoute('admin.ecommerce.products.index')],
                    ['label' => 'คำสั่งซื้อ', 'url' => safeRoute('admin.ecommerce.orders.index')],
                    ['label' => 'หมวดหมู่', 'url' => safeRoute('admin.ecommerce.categories.index')],
                ]
            ],
            [
                'icon' => 'fa-cash-register',
                'label' => 'ระบบ POS',
                'url' => '#',
                'submenu' => [
                    ['label' => 'แดชบอร์ด', 'url' => safeRoute('admin.pos.dashboard')],
                    ['label' => 'อุปกรณ์ POS', 'url' => safeRoute('admin.pos.devices.index')],
                    ['label' => 'ธุรกรรม', 'url' => safeRoute('admin.pos.transactions.index')],
                ]
            ],
            [
                'icon' => 'fa-wallet',
                'label' => 'กระเป๋าเงิน THB',
                'url' => '#',
                'submenu' => [
                    ['label' => 'กระเป๋าเงินทั้งหมด', 'url' => safeRoute('admin.wallet.index')],
                    ['label' => 'ประวัติธุรกรรม', 'url' => safeRoute('admin.wallet.transactions')],
                    ['label' => 'คำขอถอนเงิน', 'url' => safeRoute('admin.withdrawals.pending')],
                    ['label' => 'ประวัติการถอน', 'url' => safeRoute('admin.withdrawals.index')],
                ]
            ],
            [
                'icon' => 'fa-bitcoin',
                'label' => 'กระเป๋าคริปโต',
                'url' => '#',
                'submenu' => [
                    ['label' => 'แดชบอร์ด', 'url' => safeRoute('admin.crypto.dashboard')],
                    ['label' => 'จัดการ Wallets', 'url' => safeRoute('admin.crypto.wallets')],
                    ['label' => 'ธุรกรรม', 'url' => safeRoute('admin.crypto.transactions')],
                    ['label' => 'คำขอถอน', 'url' => safeRoute('admin.crypto.withdrawals')],
                ]
            ],
            [
                'icon' => 'fa-money-bill',
                'label' => 'คอมมิชชั่น',
                'url' => '#',
                'submenu' => [
                    ['label' => 'รายการทั้งหมด', 'url' => safeRoute('admin.commissions.index')],
                    ['label' => 'รายงานคอมมิชชั่น', 'url' => safeRoute('admin.mlm.commissions.index')],
                ]
            ],
            [
                'icon' => 'fa-envelope',
                'label' => 'จัดการอีเมล',
                'url' => '#',
                'submenu' => [
                    ['label' => 'เทมเพลต', 'url' => safeRoute('admin.email.templates.index')],
                    ['label' => 'ผู้ให้บริการ', 'url' => safeRoute('admin.email.providers')],
                    ['label' => 'ประวัติการส่ง', 'url' => safeRoute('admin.email.logs')],
                ]
            ],
            [
                'icon' => 'fa-line',
                'label' => 'LINE OA & AI',
                'url' => '#',
                'submenu' => [
                    ['label' => 'ตั้งค่า LINE OA', 'url' => safeRoute('admin.line-oa.index')],
                    ['label' => 'LINE Analytics', 'url' => safeRoute('admin.line-oa.analytics')],
                    ['label' => 'AI Chat Bot', 'url' => safeRoute('admin.line-bot.ai.index')],
                    ['label' => 'Broadcast', 'url' => safeRoute('admin.line-bot.broadcast.index')],
                ]
            ],
            [
                'icon' => 'fa-graduation-cap',
                'label' => 'Academy System',
                'url' => '#',
                'submenu' => [
                    ['label' => 'คอร์สเรียน', 'url' => safeRoute('admin.academy.courses.index')],
                    ['label' => 'จัดการแบบทดสอบ', 'url' => safeRoute('admin.quiz-management.index')],
                    ['label' => 'ใบประกาศ', 'url' => safeRoute('admin.certificates.index')],
                ]
            ],
            [
                'icon' => 'fa-book',
                'label' => 'Learning Center',
                'url' => '#',
                'submenu' => [
                    ['label' => 'บทความ', 'url' => safeRoute('admin.articles.index')],
                    ['label' => 'หมวดหมู่', 'url' => safeRoute('admin.categories.index')],
                ]
            ],
            [
                'icon' => 'fa-gem',
                'label' => 'MLM System',
                'url' => '#',
                'submenu' => [
                    ['label' => 'สมาชิก MLM', 'url' => safeRoute('admin.mlm.members.index')],
                    ['label' => 'แผน MLM', 'url' => safeRoute('admin.mlm.plans.index')],
                    ['label' => 'ผังสายงาน', 'url' => safeRoute('admin.mlm.genealogy.index')],
                    ['label' => 'คอมมิชชั่น', 'url' => safeRoute('admin.mlm.commissions.index')],
                ]
            ],
            [
                'icon' => 'fa-chart-line',
                'label' => 'ระบบการตลาด',
                'url' => '#',
                'submenu' => [
                    ['label' => 'Affiliates', 'url' => safeRoute('admin.affiliates.index')],
                    ['label' => 'โครงสร้างทีม', 'url' => safeRoute('admin.affiliates.tree')],
                    ['label' => 'ระบบรักษายอด', 'url' => safeRoute('admin.retention.index')],
                    ['label' => 'จัดการระดับ Rank', 'url' => safeRoute('admin.ranks.index')],
                ]
            ],
            [
                'icon' => 'fa-user-tie',
                'label' => 'HRM (HR)',
                'url' => '#',
                'submenu' => [
                    ['label' => 'แดชบอร์ด', 'url' => safeRoute('admin.hrm.dashboard')],
                    ['label' => 'พนักงาน', 'url' => safeRoute('admin.hrm.employees.index')],
                    ['label' => 'แผนก', 'url' => safeRoute('admin.hrm.departments.index')],
                ]
            ],
            [
                'icon' => 'fa-calculator',
                'label' => 'บัญชี (Accounting)',
                'url' => '#',
                'submenu' => [
                    ['label' => 'แดชบอร์ด', 'url' => safeRoute('admin.accounting.dashboard')],
                    ['label' => 'ใบแจ้งหนี้', 'url' => safeRoute('admin.accounting.invoices.index')],
                    ['label' => 'ค่าใช้จ่าย', 'url' => safeRoute('admin.accounting.expenses.index')],
                ]
            ],
            [
                'icon' => 'fa-bell',
                'label' => 'การแจ้งเตือน',
                'url' => '#',
                'submenu' => [
                    ['label' => 'ส่งการแจ้งเตือน', 'url' => safeRoute('admin.notifications.create')],
                    ['label' => 'ประวัติ', 'url' => safeRoute('admin.notifications.index')],
                ]
            ],
            [
                'icon' => 'fa-lock',
                'label' => 'ความปลอดภัย',
                'url' => '#',
                'submenu' => [
                    ['label' => 'ภาพรวม', 'url' => safeRoute('admin.security.index')],
                    ['label' => 'ตั้งค่า OTP', 'url' => safeRoute('admin.otp.settings')],
                    ['label' => 'ตั้งค่า 2FA', 'url' => safeRoute('admin.two-factor.settings')],
                ]
            ],
            [
                'icon' => 'fa-file-alt',
                'label' => 'เพจ & SEO',
                'url' => '#',
                'submenu' => [
                    ['label' => 'จัดการเพจ', 'url' => safeRoute('admin.pages.index')],
                    ['label' => 'SEO Settings', 'url' => safeRoute('admin.seo.index')],
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
                    ['label' => 'Theme Builder', 'url' => safeRoute('admin.themes.builder')],
                    ['label' => 'Page Builder', 'url' => safeRoute('admin.page-builder.index')],
                    ['label' => 'Windows UI (Millennium)', 'url' => safeRoute('admin.windows-ui.index')],
                    ['label' => 'Classic X Theme Settings', 'url' => safeRoute('admin.classic-x-settings.index')],
                ]
            ],
            [
                'icon' => 'fa-globe',
                'label' => 'ภาษา & แปล',
                'url' => '#',
                'submenu' => [
                    ['label' => 'การแปล', 'url' => safeRoute('admin.translations.index')],
                    ['label' => 'ตั้งค่าภาษา', 'url' => safeRoute('admin.settings.languages')],
                ]
            ],
            [
                'icon' => 'fa-cog',
                'label' => 'ตั้งค่าระบบ',
                'url' => '#',
                'submenu' => [
                    ['label' => 'ตั้งค่าทั่วไป', 'url' => safeRoute('admin.settings.index')],
                    ['label' => 'ตั้งค่า Mobile App', 'url' => safeRoute('admin.app-management.settings.index')],
                    ['label' => 'จัดการ API', 'url' => safeRoute('admin.api-management.endpoints.index')],
                ]
            ],
        ];
    } elseif ($type === 'seller') {
        $menuItems = [
            ['icon' => 'fa-dashboard', 'label' => 'แดชบอร์ด', 'url' => safeRoute('seller.dashboard')],
            ['icon' => 'fa-bullhorn', 'label' => 'การตลาด', 'url' => safeRoute('seller.marketing')],
            [
                'icon' => 'fa-box',
                'label' => 'สินค้า',
                'url' => '#',
                'submenu' => [
                    ['label' => 'รายการสินค้า', 'url' => safeRoute('seller.products.index')],
                    ['label' => 'เพิ่มสินค้า', 'url' => safeRoute('seller.products.create')],
                ]
            ],
            [
                'icon' => 'fa-cash-register',
                'label' => 'ระบบ POS',
                'url' => '#',
                'submenu' => [
                    ['label' => 'ขายสินค้า', 'url' => safeRoute('seller.pos.terminal')],
                    ['label' => 'รายการขาย', 'url' => safeRoute('seller.pos.transactions')],
                    ['label' => 'อุปกรณ์ POS', 'url' => safeRoute('seller.pos.devices')],
                ]
            ],
            [
                'icon' => 'fa-shopping-cart',
                'label' => 'ยอดขาย',
                'url' => '#',
                'submenu' => [
                    ['label' => 'คำสั่งซื้อ', 'url' => safeRoute('seller.orders.index')],
                    ['label' => 'รายงานยอดขาย', 'url' => safeRoute('seller.reports.sales')],
                ]
            ],
            [
                'icon' => 'fa-wallet',
                'label' => 'กระเป๋าเงิน',
                'url' => '#',
                'submenu' => [
                    ['label' => 'กระเป๋าของฉัน', 'url' => safeRoute('seller.wallet.index')],
                    ['label' => 'ถอนเงิน', 'url' => safeRoute('seller.wallet.withdraw')],
                ]
            ],
            ['icon' => 'fa-money-bill', 'label' => 'คอมมิชชั่น', 'url' => safeRoute('seller.commissions')],
            ['icon' => 'fa-cog', 'label' => 'ตั้งค่าร้าน', 'url' => safeRoute('seller.settings')],
            ['icon' => 'fa-user', 'label' => 'โปรไฟล์', 'url' => safeRoute('seller.profile')],
        ];
    } else { // user
        $menuItems = [
            ['icon' => 'fa-dashboard', 'label' => 'แดชบอร์ด', 'url' => safeRoute('user.dashboard')],
            ['icon' => 'fa-user', 'label' => 'โปรไฟล์', 'url' => safeRoute('user.profile')],
            ['icon' => 'fa-id-card', 'label' => 'ยืนยันตัวตน KYC', 'url' => safeRoute('user.kyc.index')],
            ['icon' => 'fa-money-bill', 'label' => 'คอมมิชชั่น', 'url' => safeRoute('user.commissions')],
            [
                'icon' => 'fa-shopping-cart',
                'label' => 'ช๊อปปิ้ง',
                'url' => '#',
                'submenu' => [
                    ['label' => 'ช๊อปสินค้า', 'url' => safeRoute('shop.index')],
                ]
            ],
            [
                'icon' => 'fa-hotel',
                'label' => 'โรงแรม',
                'url' => '#',
                'submenu' => [
                    ['label' => 'จองโรงแรม', 'url' => safeRoute('hotels.index')],
                    ['label' => 'การจองของฉัน', 'url' => safeRoute('hotels.bookings.index')],
                ]
            ],
            ['icon' => 'fa-ticket', 'label' => 'Ticket Support', 'url' => safeRoute('user.tickets.index')],
            [
                'icon' => 'fa-wallet',
                'label' => 'กระเป๋าเงิน THB',
                'url' => '#',
                'submenu' => [
                    ['label' => 'กระเป๋าของฉัน', 'url' => safeRoute('user.wallet.index')],
                    ['label' => 'เติมเงิน', 'url' => safeRoute('user.wallet.deposit')],
                    ['label' => 'ถอนเงิน', 'url' => safeRoute('user.wallet.withdraw')],
                    ['label' => 'ประวัติธุรกรรม', 'url' => safeRoute('user.wallet.transactions')],
                ]
            ],
            ['icon' => 'fa-cog', 'label' => 'ตั้งค่า', 'url' => safeRoute('user.settings')],
        ];
    }
@endphp

<ul class="classic-x-menu" x-data="{ openSubmenus: [] }">
    @foreach($menuItems as $index => $item)
        <li class="classic-x-menu-item" x-data="{ menuId: 'menu-{{ $index }}' }">
            @if(isset($item['submenu']))
                {{-- Menu item with submenu --}}
                <a href="{{ $item['url'] }}"
                   class="classic-x-menu-link"
                   @click.prevent="openSubmenus.includes(menuId) ? openSubmenus = openSubmenus.filter(id => id !== menuId) : openSubmenus.push(menuId)">
                    <span class="classic-x-menu-icon">
                        <i class="fas {{ $item['icon'] }}"></i>
                    </span>
                    <span class="classic-x-menu-text">{{ $item['label'] }}</span>
                    @if(isset($item['badge']))
                        <span class="classic-x-badge">{{ $item['badge'] }}</span>
                    @endif
                    <span class="classic-x-submenu-indicator" :class="{ 'open': openSubmenus.includes(menuId) }">
                        <i class="fas fa-chevron-down"></i>
                    </span>
                </a>

                {{-- Submenu --}}
                <ul class="classic-x-submenu" :class="{ 'open': openSubmenus.includes(menuId) }">
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
