@props(['type' => 'admin'])

@php
    // Get user and role info
    $user = auth()->user();
    $logo = \App\Models\Setting::get('logo');
    $appName = \App\Models\Setting::get('app_name', 'TP-Affiliate');

    // Define menu items with working submenu support
    $menuItems = [];

    if ($type === 'admin') {
        $menuItems = [
            ['icon' => '📊', 'label' => 'แดชบอร์ด', 'url' => route('admin.dashboard')],
            [
                'icon' => '👥',
                'label' => 'ผู้ใช้งาน',
                'submenu' => [
                    ['label' => 'รายชื่อผู้ใช้', 'url' => route('admin.users.index')],
                    ['label' => 'บทบาท (Roles)', 'url' => route('admin.roles.index')],
                ]
            ],
            ['icon' => '🪪', 'label' => 'ยืนยันตัวตน KYC', 'url' => route('admin.kyc.index')],
            ['icon' => '🎫', 'label' => 'Ticket Support', 'url' => route('admin.tickets.index')],
            [
                'icon' => '🤖',
                'label' => 'AI Bots & ผู้ช่วย',
                'submenu' => [
                    ['label' => 'จัดการ AI Bots', 'url' => route('admin.ai-bots.index')],
                    ['label' => 'AI Providers', 'url' => route('admin.ai-providers.index')],
                    ['label' => 'ติดตั้ง AI', 'url' => route('admin.ai-installation.index')],
                ]
            ],
            [
                'icon' => '🏨',
                'label' => 'จัดการโรงแรม',
                'submenu' => [
                    ['label' => 'โรงแรมทั้งหมด', 'url' => route('admin.hotels.index')],
                    ['label' => 'การจองทั้งหมด', 'url' => route('admin.hotels.bookings.index')],
                    ['label' => 'สถิติการจอง', 'url' => route('admin.hotels.bookings.analytics')],
                    ['label' => 'จัดการรีวิว', 'url' => route('admin.hotels.reviews.index')],
                    ['label' => 'สิ่งอำนวยความสะดวก', 'url' => route('admin.hotels.facilities.index')],
                    ['label' => 'โปรโมชั่นพิเศษ', 'url' => route('admin.hotels.special-offers.index')],
                ]
            ],
            [
                'icon' => '🛒',
                'label' => 'อีคอมเมิร์ซ',
                'submenu' => [
                    ['label' => 'สินค้าทั้งหมด', 'url' => route('admin.ecommerce.products.index')],
                    ['label' => 'คำสั่งซื้อ', 'url' => route('admin.ecommerce.orders.index')],
                    ['label' => 'หมวดหมู่', 'url' => route('admin.ecommerce.categories.index')],
                    ['label' => 'รีวิวสินค้า', 'url' => route('admin.ecommerce.reviews.index')],
                ]
            ],
            [
                'icon' => '🏪',
                'label' => 'ระบบ POS',
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
                'submenu' => [
                    ['label' => 'กระเป๋าเงินทั้งหมด', 'url' => route('admin.wallet.index')],
                    ['label' => 'ประวัติธุรกรรม', 'url' => route('admin.wallet.transactions')],
                    ['label' => 'คำขอถอนเงิน', 'url' => route('admin.withdrawals.pending')],
                    ['label' => 'ตั้งค่า Payment', 'url' => route('admin.payment-gateways.index')],
                ]
            ],
            [
                'icon' => '₿',
                'label' => 'กระเป๋าคริปโต',
                'submenu' => [
                    ['label' => 'แดชบอร์ด', 'url' => route('admin.crypto.dashboard')],
                ]
            ],
            [
                'icon' => '💵',
                'label' => 'คอมมิชชั่น',
                'submenu' => [
                    ['label' => 'รายการทั้งหมด', 'url' => route('admin.commissions.index')],
                ]
            ],
            [
                'icon' => '📧',
                'label' => 'จัดการอีเมล',
                'submenu' => [
                    ['label' => 'เทมเพลต', 'url' => route('admin.email.templates.index')],
                ]
            ],
            [
                'icon' => '📱',
                'label' => 'LINE OA & AI',
                'submenu' => [
                    ['label' => 'ตั้งค่า LINE OA', 'url' => route('admin.line-oa.index')],
                    ['label' => 'AI Chat Bot', 'url' => route('admin.line-bot.ai.index')],
                ]
            ],
            [
                'icon' => '🎓',
                'label' => 'Academy System',
                'submenu' => [
                    ['label' => 'คอร์สเรียน', 'url' => route('admin.academy.courses.index')],
                    ['label' => 'ใบประกาศ', 'url' => route('admin.academy.certificates.index')],
                ]
            ],
            [
                'icon' => '📚',
                'label' => 'Learning Center',
                'submenu' => [
                    ['label' => 'บทความ', 'url' => route('admin.articles.index')],
                    ['label' => 'ศูนย์เรียนรู้', 'url' => route('admin.learning-center.index')],
                ]
            ],
            [
                'icon' => '💎',
                'label' => 'MLM System',
                'submenu' => [
                    ['label' => 'สมาชิก MLM', 'url' => route('admin.mlm.members.index')],
                    ['label' => 'แผน MLM', 'url' => route('admin.mlm.plans.index')],
                    ['label' => 'ผังสายงาน', 'url' => route('admin.mlm.genealogy.index')],
                ]
            ],
            [
                'icon' => '📈',
                'label' => 'ระบบการตลาด',
                'submenu' => [
                    ['label' => 'Affiliates', 'url' => route('admin.affiliates.index')],
                    ['label' => 'ระบบรักษายอด', 'url' => route('admin.retention.index')],
                    ['label' => 'จัดการระดับ Rank', 'url' => route('admin.ranks.index')],
                    ['label' => 'การเลื่อนระดับ', 'url' => route('admin.ranks.promotions.index')],
                ]
            ],
            [
                'icon' => '👨‍💼',
                'label' => 'HRM (HR)',
                'submenu' => [
                    ['label' => 'แดชบอร์ด', 'url' => route('admin.hrm.dashboard')],
                    ['label' => 'พนักงาน', 'url' => route('admin.hrm.employees.index')],
                ]
            ],
            [
                'icon' => '📊',
                'label' => 'บัญชี (Accounting)',
                'submenu' => [
                    ['label' => 'แดชบอร์ด', 'url' => route('admin.accounting.dashboard')],
                ]
            ],
            [
                'icon' => '🔔',
                'label' => 'การแจ้งเตือน',
                'submenu' => [
                    ['label' => 'ส่งการแจ้งเตือน', 'url' => route('admin.notifications.create')],
                    ['label' => 'ประวัติ', 'url' => route('admin.notifications.index')],
                ]
            ],
            [
                'icon' => '🔒',
                'label' => 'ความปลอดภัย',
                'submenu' => [
                    ['label' => 'ภาพรวม', 'url' => route('admin.security.index')],
                ]
            ],
            [
                'icon' => '📄',
                'label' => 'เพจ & SEO',
                'submenu' => [
                    ['label' => 'จัดการเพจ', 'url' => route('admin.pages.index')],
                ]
            ],
            [
                'icon' => '📊',
                'label' => 'Analytics',
                'submenu' => [
                    ['label' => 'ภาพรวม', 'url' => route('admin.analytics.index')],
                ]
            ],
            [
                'icon' => '🎨',
                'label' => 'ธีม & UI',
                'submenu' => [
                    ['label' => 'Theme Builder', 'url' => route('admin.themes.builder')],
                ]
            ],
            [
                'icon' => '🌐',
                'label' => 'ภาษา & แปล',
                'submenu' => [
                    ['label' => 'การแปล', 'url' => route('admin.translations.index')],
                ]
            ],
            ['icon' => '⚙️', 'label' => 'ตั้งค่าระบบ', 'url' => route('admin.settings.index')],
        ];
    } elseif ($type === 'seller') {
        $menuItems = [
            ['icon' => '📊', 'label' => 'แดชบอร์ด', 'url' => route('seller.dashboard')],
            [
                'icon' => '📦',
                'label' => 'สินค้า',
                'submenu' => [
                    ['label' => 'รายการสินค้า', 'url' => route('seller.products.index')],
                    ['label' => 'เพิ่มสินค้า', 'url' => route('seller.products.create')],
                ]
            ],
            [
                'icon' => '🏪',
                'label' => 'ระบบ POS',
                'submenu' => [
                    ['label' => 'ขายสินค้า', 'url' => route('seller.pos.terminal')],
                    ['label' => 'รายการขาย', 'url' => route('seller.pos.sales')],
                ]
            ],
            [
                'icon' => '🛒',
                'label' => 'ยอดขาย',
                'submenu' => [
                    ['label' => 'คำสั่งซื้อ', 'url' => route('seller.orders.index')],
                    ['label' => 'รายงานยอดขาย', 'url' => route('seller.reports.sales')],
                ]
            ],
            [
                'icon' => '💰',
                'label' => 'กระเป๋าเงิน',
                'submenu' => [
                    ['label' => 'กระเป๋าของฉัน', 'url' => route('seller.wallet.index')],
                    ['label' => 'ถอนเงิน', 'url' => route('seller.wallet.withdraw')],
                ]
            ],
            ['icon' => '💵', 'label' => 'คอมมิชชั่น', 'url' => route('seller.commissions')],
            ['icon' => '📈', 'label' => 'วิเคราะห์', 'url' => route('seller.analytics')],
            ['icon' => '⚙️', 'label' => 'ตั้งค่าร้าน', 'url' => route('seller.settings')],
            ['icon' => '👤', 'label' => 'โปรไฟล์', 'url' => route('seller.profile')],
        ];
    } else { // user
        $menuItems = [
            ['icon' => '📊', 'label' => 'แดชบอร์ด', 'url' => route('user.dashboard')],
            ['icon' => '👤', 'label' => 'โปรไฟล์', 'url' => route('user.profile')],
            ['icon' => '🪪', 'label' => 'ยืนยันตัวตน KYC', 'url' => route('user.kyc.index')],
            ['icon' => '💰', 'label' => 'คอมมิชชั่น', 'url' => route('user.commissions')],
            [
                'icon' => '🛒',
                'label' => 'ช๊อปปิ้ง',
                'submenu' => [
                    ['label' => 'ช๊อปสินค้า', 'url' => route('shop.index')],
                ]
            ],
            [
                'icon' => '🏨',
                'label' => 'โรงแรม',
                'submenu' => [
                    ['label' => 'จองโรงแรม', 'url' => route('hotels.index')],
                    ['label' => 'การจองของฉัน', 'url' => route('hotels.bookings.index')],
                ]
            ],
            ['icon' => '🎫', 'label' => 'Ticket Support', 'url' => route('user.tickets.index')],
            [
                'icon' => '💳',
                'label' => 'กระเป๋าเงิน THB',
                'submenu' => [
                    ['label' => 'กระเป๋าของฉัน', 'url' => route('user.wallet.index')],
                    ['label' => 'ถอนเงิน', 'url' => route('user.wallet.withdraw')],
                ]
            ],
            [
                'icon' => '₿',
                'label' => 'กระเป๋าคริปโต',
                'submenu' => [
                    ['label' => 'กระเป๋าคริปโต', 'url' => route('user.crypto-wallet.index')],
                ]
            ],
            [
                'icon' => '📈',
                'label' => 'การลงทุน ROI',
                'submenu' => [
                    ['label' => 'แดชบอร์ด', 'url' => route('user.investments.index')],
                    ['label' => 'แผนการลงทุน', 'url' => route('user.investments.plans')],
                ]
            ],
            [
                'icon' => '🤖',
                'label' => 'AI Bots',
                'submenu' => [
                    ['label' => 'ตลาดบอท', 'url' => route('marketplace.index')],
                ]
            ],
            [
                'icon' => '👥',
                'label' => 'ทีมงาน',
                'submenu' => [
                    ['label' => 'ผู้แนะนำ', 'url' => route('user.referrals')],
                    ['label' => 'ผังสายงาน', 'url' => route('user.organization')],
                ]
            ],
            [
                'icon' => '💖',
                'label' => 'รักษายอด',
                'submenu' => [
                    ['label' => 'สถานะพลังชีวิต', 'url' => route('user.retention.index')],
                ]
            ],
            [
                'icon' => '🎯',
                'label' => 'เครื่องมือการตลาด',
                'submenu' => [
                    ['label' => 'จำลองรายได้', 'url' => route('user.mlm.income-simulator')],
                ]
            ],
            ['icon' => '🎨', 'label' => 'ตั้งค่าธีม', 'url' => route('user.themes.index')],
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
    x-data="{ openSubmenus: {} }"
    x-transition:enter="transition ease-out duration-400"
    x-transition:enter-start="opacity-0 -translate-x-full scale-95"
    x-transition:enter-end="opacity-100 translate-x-0 scale-100"
    x-transition:leave="transition ease-in duration-300"
    x-transition:leave-start="opacity-100 translate-x-0 scale-100"
    x-transition:leave-end="opacity-0 -translate-x-full scale-95"
    class="fixed left-0 top-0 bottom-0 w-80 md:w-96 z-[70]"
    style="display: none;">

    <!-- Main Panel with 3D Effect -->
    <div class="relative h-full bg-gradient-to-br from-slate-900 via-purple-900/40 to-blue-900/40 backdrop-blur-xl border-r-4 border-purple-500/30 overflow-hidden shadow-3d">

        <!-- Animated Background -->
        <div class="absolute inset-0 opacity-5">
            <div class="absolute inset-0 millennium-grid"></div>
        </div>

        <!-- Content Container -->
        <div class="relative h-full flex flex-col">

            <!-- Header Section with 3D Effect -->
            <div class="p-5 bg-gradient-to-r from-purple-600 via-pink-600 to-blue-600 shadow-3d-header border-b-2 border-white/10">
                <div class="flex items-center gap-3">
                    @if($logo)
                        <div class="w-14 h-14 rounded-xl overflow-hidden ring-3 ring-white/30 shadow-3d-logo transform hover:scale-110 transition-transform duration-300">
                            <img src="{{ asset($logo) }}" alt="{{ $appName }}" class="w-full h-full object-contain">
                        </div>
                    @else
                        <div class="w-14 h-14 bg-gradient-to-br from-cyan-400 to-blue-600 rounded-xl flex items-center justify-center ring-3 ring-white/30 shadow-3d-logo transform hover:scale-110 transition-transform duration-300">
                            <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M0 0h11v11H0V0zm13 0h11v11H13V0zM0 13h11v11H0V13zm13 0h11v11H13V13z"/>
                            </svg>
                        </div>
                    @endif

                    <div class="flex-1 min-w-0">
                        <h2 class="text-xl font-bold text-white drop-shadow-3d truncate">{{ $appName }}</h2>
                        @if($user)
                            <p class="text-sm text-blue-100 mt-1 font-semibold truncate drop-shadow">{{ $user->name }}</p>
                            <span class="inline-block mt-1 px-3 py-0.5 bg-white/20 backdrop-blur-sm rounded-full text-xs font-bold text-white shadow-3d-sm">
                                {{ $type === 'admin' ? '👑 Admin' : ($type === 'seller' ? '🏪 Seller' : '👤 User') }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Menu Items Section -->
            <div class="flex-1 p-4 overflow-y-auto millennium-scrollbar">
                <div class="space-y-2">
                    @foreach($menuItems as $index => $item)
                        <div>
                            @if(isset($item['submenu']))
                                <!-- Menu Item with Submenu - MAIN HEADER -->
                                <button
                                    @click="openSubmenus[{{ $index }}] = !openSubmenus[{{ $index }}]"
                                    class="w-full group flex items-center gap-3 px-4 py-3 rounded-xl bg-gradient-to-r from-purple-600/40 to-pink-600/40 hover:from-purple-600/60 hover:to-pink-600/60 border-2 border-purple-400/40 hover:border-purple-300/60 transition-all duration-300 transform hover:scale-[1.02] shadow-lg hover:shadow-xl"
                                    :class="openSubmenus[{{ $index }}] ? 'from-purple-600/60 to-pink-600/60 border-purple-300/60 shadow-xl' : ''">

                                    <!-- Icon with 3D Effect - LARGER -->
                                    <span class="text-3xl group-hover:scale-110 transition-transform duration-300 drop-shadow-lg">
                                        {{ $item['icon'] }}
                                    </span>

                                    <!-- Label - LARGER & BOLDER -->
                                    <span class="text-base font-bold text-white transition-colors duration-300 flex-1 text-left">
                                        {{ $item['label'] }}
                                    </span>

                                    <!-- Chevron Arrow -->
                                    <svg
                                        class="w-5 h-5 text-white/60 transition-all duration-300"
                                        :class="openSubmenus[{{ $index }}] ? 'rotate-90 text-white' : ''"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                        stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </button>

                                <!-- Submenu Items with Animation - CLEARLY DIFFERENT -->
                                <div
                                    x-show="openSubmenus[{{ $index }}]"
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0 -translate-y-2"
                                    x-transition:enter-end="opacity-100 translate-y-0"
                                    x-transition:leave="transition ease-in duration-150"
                                    x-transition:leave-start="opacity-100 translate-y-0"
                                    x-transition:leave-end="opacity-0 -translate-y-2"
                                    class="mt-2 ml-8 space-y-1.5 pb-2"
                                    style="display: none;">
                                    @foreach($item['submenu'] as $subitem)
                                        <a
                                            href="{{ $subitem['url'] }}"
                                            class="flex items-center gap-2.5 px-3 py-2 rounded-lg bg-white/5 hover:bg-blue-500/20 border border-white/10 hover:border-blue-400/40 transition-all duration-200 transform hover:translate-x-1 group">

                                            <!-- Bullet Point - SMALLER -->
                                            <span class="w-1.5 h-1.5 rounded-full bg-blue-400/60 group-hover:bg-blue-300 group-hover:scale-125 transition-all duration-200"></span>

                                            <!-- Submenu Label - SMALLER & LIGHTER -->
                                            <span class="text-sm font-medium text-white/80 group-hover:text-white transition-colors duration-200 flex-1">
                                                {{ $subitem['label'] }}
                                            </span>

                                            <!-- Small Arrow -->
                                            <svg class="w-3.5 h-3.5 text-white/30 group-hover:text-blue-300 group-hover:translate-x-0.5 transition-all duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
                                            </svg>
                                        </a>
                                    @endforeach
                                </div>
                            @else
                                <!-- Regular Menu Item without Submenu - MAIN STYLE -->
                                <a
                                    href="{{ $item['url'] }}"
                                    class="group flex items-center gap-3 px-4 py-3 rounded-xl bg-gradient-to-r from-purple-600/40 to-pink-600/40 hover:from-purple-600/60 hover:to-pink-600/60 border-2 border-purple-400/40 hover:border-purple-300/60 transition-all duration-300 transform hover:scale-[1.02] shadow-lg hover:shadow-xl">

                                    <!-- Icon with 3D Effect - LARGER -->
                                    <span class="text-3xl group-hover:scale-110 transition-transform duration-300 drop-shadow-lg">
                                        {{ $item['icon'] }}
                                    </span>

                                    <!-- Label - LARGER & BOLDER -->
                                    <span class="text-base font-bold text-white transition-colors duration-300 flex-1">
                                        {{ $item['label'] }}
                                    </span>

                                    <!-- Arrow -->
                                    <svg class="w-5 h-5 text-white/60 group-hover:text-white group-hover:translate-x-1 transition-all duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Footer Section with 3D Effect -->
            <div class="p-3 bg-gradient-to-r from-gray-900 via-purple-900/50 to-blue-900/50 border-t-2 border-white/10 shadow-3d-footer">
                @if($user)
                    <div class="flex items-center gap-2">
                        <!-- User Avatar with 3D Effect -->
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-pink-500 via-purple-500 to-blue-500 flex items-center justify-center text-white font-bold text-sm ring-2 ring-white/30 shadow-3d-avatar">
                            {{ substr($user->name, 0, 2) }}
                        </div>

                        <!-- User Info -->
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-white truncate drop-shadow">{{ $user->name }}</p>
                            <p class="text-xs text-gray-300 truncate">{{ $user->email }}</p>
                        </div>

                        <!-- Logout Button with 3D Effect -->
                        <button
                            onclick="document.getElementById('millennium-logout-form').submit()"
                            class="w-10 h-10 rounded-xl bg-gradient-to-br from-red-500 to-pink-600 hover:from-red-600 hover:to-pink-700 flex items-center justify-center text-white transition-all duration-300 transform hover:scale-110 hover:rotate-6 shadow-3d-button hover:shadow-3d-button-hover"
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
                    <!-- Login/Register Buttons with 3D Effect -->
                    <div class="flex gap-2">
                        <a href="{{ route('login') }}" class="flex-1 px-3 py-2 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white text-sm font-bold rounded-xl text-center transition-all duration-300 transform hover:scale-105 shadow-3d-button hover:shadow-3d-button-hover">
                            เข้าสู่ระบบ
                        </a>
                        <a href="{{ route('register') }}" class="flex-1 px-3 py-2 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white text-sm font-bold rounded-xl text-center transition-all duration-300 transform hover:scale-105 shadow-3d-button hover:shadow-3d-button-hover">
                            สมัครสมาชิก
                        </a>
                    </div>
                @endif
            </div>

        </div>
    </div>
</div>

<style>
    /* 3D Effects */
    .shadow-3d {
        box-shadow:
            5px 5px 15px rgba(0, 0, 0, 0.5),
            -2px -2px 8px rgba(255, 255, 255, 0.05),
            inset 1px 1px 2px rgba(255, 255, 255, 0.1);
    }

    .shadow-3d-header {
        box-shadow:
            0 8px 20px rgba(0, 0, 0, 0.4),
            0 2px 8px rgba(168, 85, 247, 0.3),
            inset 0 1px 0 rgba(255, 255, 255, 0.2);
    }

    .shadow-3d-footer {
        box-shadow:
            0 -8px 20px rgba(0, 0, 0, 0.4),
            0 -2px 8px rgba(168, 85, 247, 0.3);
    }

    .shadow-3d-logo {
        box-shadow:
            0 4px 12px rgba(0, 0, 0, 0.4),
            0 2px 6px rgba(147, 51, 234, 0.5),
            inset 0 -2px 4px rgba(0, 0, 0, 0.3),
            inset 0 2px 4px rgba(255, 255, 255, 0.2);
    }

    .shadow-3d-avatar {
        box-shadow:
            0 4px 10px rgba(0, 0, 0, 0.3),
            0 2px 4px rgba(236, 72, 153, 0.4),
            inset 0 -1px 2px rgba(0, 0, 0, 0.2),
            inset 0 1px 2px rgba(255, 255, 255, 0.3);
    }

    .shadow-3d-sm {
        box-shadow:
            2px 2px 8px rgba(0, 0, 0, 0.3),
            -1px -1px 4px rgba(255, 255, 255, 0.05),
            inset 1px 1px 2px rgba(255, 255, 255, 0.1);
    }

    .shadow-3d-button {
        box-shadow:
            0 4px 12px rgba(0, 0, 0, 0.4),
            0 2px 6px rgba(239, 68, 68, 0.4),
            inset 0 -2px 4px rgba(0, 0, 0, 0.3),
            inset 0 1px 2px rgba(255, 255, 255, 0.2);
    }

    .shadow-3d-button:hover,
    .shadow-3d-button-hover {
        box-shadow:
            0 6px 20px rgba(239, 68, 68, 0.5),
            0 3px 10px rgba(236, 72, 153, 0.4),
            inset 0 -2px 4px rgba(0, 0, 0, 0.4),
            inset 0 1px 2px rgba(255, 255, 255, 0.3);
    }

    .drop-shadow-3d {
        filter: drop-shadow(2px 2px 4px rgba(0, 0, 0, 0.5))
                drop-shadow(0 0 8px rgba(168, 85, 247, 0.3));
    }

    /* Grid Pattern */
    .millennium-grid {
        background-image:
            linear-gradient(rgba(168, 85, 247, 0.08) 1px, transparent 1px),
            linear-gradient(90deg, rgba(168, 85, 247, 0.08) 1px, transparent 1px);
        background-size: 20px 20px;
        animation: gridMove 30s linear infinite;
    }

    @keyframes gridMove {
        0% { background-position: 0 0; }
        100% { background-position: 20px 20px; }
    }

    /* Custom Scrollbar with 3D Effect */
    .millennium-scrollbar::-webkit-scrollbar {
        width: 10px;
    }

    .millennium-scrollbar::-webkit-scrollbar-track {
        background: rgba(0, 0, 0, 0.3);
        border-radius: 5px;
        box-shadow: inset 2px 2px 5px rgba(0, 0, 0, 0.5);
    }

    .millennium-scrollbar::-webkit-scrollbar-thumb {
        background: linear-gradient(180deg, #ec4899, #a855f7, #3b82f6);
        border-radius: 5px;
        box-shadow:
            2px 2px 5px rgba(0, 0, 0, 0.4),
            inset 1px 1px 2px rgba(255, 255, 255, 0.3);
    }

    .millennium-scrollbar::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(180deg, #f472b6, #c084fc, #60a5fa);
        box-shadow:
            3px 3px 8px rgba(0, 0, 0, 0.5),
            0 0 10px rgba(168, 85, 247, 0.5),
            inset 1px 1px 2px rgba(255, 255, 255, 0.4);
    }
</style>
