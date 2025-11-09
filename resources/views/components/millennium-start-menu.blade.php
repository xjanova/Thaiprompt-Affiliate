@props(['type' => 'admin'])

@php
    // Get user and role info
    $user = auth()->user();
    $logo = \App\Models\Setting::get('logo');
    $appName = \App\Models\Setting::get('app_name', 'TP-Affiliate');

    // Define simple flat menu items
    $menuItems = [];

    if ($type === 'admin') {
        $menuItems = [
            ['section' => 'แดชบอร์ด', 'items' => [
                ['icon' => '📊', 'label' => 'แดชบอร์ด', 'url' => route('admin.dashboard')],
            ]],
            ['section' => 'ผู้ใช้งาน', 'items' => [
                ['icon' => '👥', 'label' => 'รายชื่อผู้ใช้', 'url' => route('admin.users.index')],
                ['icon' => '🔑', 'label' => 'สิทธิ์ผู้ใช้', 'url' => route('admin.users.permissions')],
                ['icon' => '🎭', 'label' => 'บทบาท (Roles)', 'url' => route('admin.roles.index')],
                ['icon' => '🪪', 'label' => 'ยืนยันตัวตน KYC', 'url' => route('admin.kyc.index')],
            ]],
            ['section' => 'AI & ระบบอัตโนมัติ', 'items' => [
                ['icon' => '🤖', 'label' => 'จัดการ AI Bots', 'url' => route('admin.ai-bots.index')],
                ['icon' => '🧠', 'label' => 'AI Providers', 'url' => route('admin.ai-providers.index')],
                ['icon' => '⚙️', 'label' => 'ติดตั้ง AI', 'url' => route('admin.ai-installation.index')],
            ]],
            ['section' => 'โรงแรม & การท่องเที่ยว', 'items' => [
                ['icon' => '🏨', 'label' => 'โรงแรมทั้งหมด', 'url' => route('admin.hotels.index')],
                ['icon' => '📅', 'label' => 'การจองทั้งหมด', 'url' => route('admin.hotels.bookings.index')],
                ['icon' => '📊', 'label' => 'สถิติการจอง', 'url' => route('admin.hotels.bookings.analytics')],
                ['icon' => '⭐', 'label' => 'จัดการรีวิว', 'url' => route('admin.hotels.reviews.index')],
                ['icon' => '🏊', 'label' => 'สิ่งอำนวยความสะดวก', 'url' => route('admin.hotels.facilities.index')],
                ['icon' => '🎁', 'label' => 'โปรโมชั่นพิเศษ', 'url' => route('admin.hotels.special-offers.index')],
            ]],
            ['section' => 'อีคอมเมิร์ซ & ร้านค้า', 'items' => [
                ['icon' => '🛒', 'label' => 'สินค้าทั้งหมด', 'url' => route('admin.ecommerce.products.index')],
                ['icon' => '📦', 'label' => 'คำสั่งซื้อ', 'url' => route('admin.ecommerce.orders.index')],
                ['icon' => '📂', 'label' => 'หมวดหมู่', 'url' => route('admin.ecommerce.categories.index')],
                ['icon' => '💬', 'label' => 'รีวิวสินค้า', 'url' => route('admin.ecommerce.reviews.index')],
            ]],
            ['section' => 'ระบบ POS', 'items' => [
                ['icon' => '🏪', 'label' => 'แดชบอร์ด POS', 'url' => route('admin.pos.dashboard')],
                ['icon' => '💻', 'label' => 'อุปกรณ์ POS', 'url' => route('admin.pos.devices.index')],
                ['icon' => '🧾', 'label' => 'ธุรกรรม', 'url' => route('admin.pos.transactions.index')],
                ['icon' => '📈', 'label' => 'วิเคราะห์ข้อมูล', 'url' => route('admin.pos.analytics')],
            ]],
            ['section' => 'การเงิน', 'items' => [
                ['icon' => '💰', 'label' => 'กระเป๋าเงิน THB', 'url' => route('admin.wallet.index')],
                ['icon' => '📝', 'label' => 'ประวัติธุรกรรม', 'url' => route('admin.wallet.transactions')],
                ['icon' => '💸', 'label' => 'คำขอถอนเงิน', 'url' => route('admin.withdrawals.pending')],
                ['icon' => '₿', 'label' => 'กระเป๋าคริปโต', 'url' => route('admin.crypto.dashboard')],
                ['icon' => '💵', 'label' => 'คอมมิชชั่น', 'url' => route('admin.commissions.index')],
                ['icon' => '💳', 'label' => 'ตั้งค่า Payment', 'url' => route('admin.payment-gateways.index')],
            ]],
            ['section' => 'การตลาด & MLM', 'items' => [
                ['icon' => '💎', 'label' => 'สมาชิก MLM', 'url' => route('admin.mlm.members.index')],
                ['icon' => '🎯', 'label' => 'แผน MLM', 'url' => route('admin.mlm.plans.index')],
                ['icon' => '🌳', 'label' => 'ผังสายงาน', 'url' => route('admin.mlm.genealogy.index')],
                ['icon' => '📊', 'label' => 'Affiliates', 'url' => route('admin.affiliates.index')],
                ['icon' => '💖', 'label' => 'ระบบรักษายอด', 'url' => route('admin.retention.index')],
                ['icon' => '🏆', 'label' => 'จัดการระดับ Rank', 'url' => route('admin.ranks.index')],
            ]],
            ['section' => 'การสื่อสาร', 'items' => [
                ['icon' => '📧', 'label' => 'เทมเพลตอีเมล', 'url' => route('admin.email.templates.index')],
                ['icon' => '📱', 'label' => 'LINE OA', 'url' => route('admin.line-oa.index')],
                ['icon' => '💬', 'label' => 'AI Chat Bot', 'url' => route('admin.line-bot.ai.index')],
                ['icon' => '🔔', 'label' => 'การแจ้งเตือน', 'url' => route('admin.notifications.index')],
                ['icon' => '🎫', 'label' => 'Ticket Support', 'url' => route('admin.tickets.index')],
            ]],
            ['section' => 'การศึกษา', 'items' => [
                ['icon' => '🎓', 'label' => 'คอร์สเรียน', 'url' => route('admin.academy.courses.index')],
                ['icon' => '📜', 'label' => 'ใบประกาศ', 'url' => route('admin.academy.certificates.index')],
                ['icon' => '📖', 'label' => 'บทความ', 'url' => route('admin.articles.index')],
                ['icon' => '🏫', 'label' => 'ศูนย์เรียนรู้', 'url' => route('admin.learning-center.index')],
            ]],
            ['section' => 'บริหารจัดการ', 'items' => [
                ['icon' => '👨‍💼', 'label' => 'HRM', 'url' => route('admin.hrm.dashboard')],
                ['icon' => '💼', 'label' => 'พนักงาน', 'url' => route('admin.hrm.employees.index')],
                ['icon' => '📊', 'label' => 'บัญชี', 'url' => route('admin.accounting.dashboard')],
                ['icon' => '🔒', 'label' => 'ความปลอดภัย', 'url' => route('admin.security.index')],
            ]],
            ['section' => 'ตั้งค่าระบบ', 'items' => [
                ['icon' => '🎨', 'label' => 'ธีม & UI', 'url' => route('admin.themes.builder')],
                ['icon' => '🌐', 'label' => 'การแปล', 'url' => route('admin.translations.index')],
                ['icon' => '📄', 'label' => 'จัดการเพจ', 'url' => route('admin.pages.index')],
                ['icon' => '📊', 'label' => 'Analytics', 'url' => route('admin.analytics.index')],
                ['icon' => '⚙️', 'label' => 'ตั้งค่าระบบ', 'url' => route('admin.settings.index')],
            ]],
        ];
    } elseif ($type === 'seller') {
        $menuItems = [
            ['section' => 'หลัก', 'items' => [
                ['icon' => '📊', 'label' => 'แดชบอร์ด', 'url' => route('seller.dashboard')],
                ['icon' => '👤', 'label' => 'โปรไฟล์', 'url' => route('seller.profile')],
            ]],
            ['section' => 'สินค้า', 'items' => [
                ['icon' => '📦', 'label' => 'รายการสินค้า', 'url' => route('seller.products.index')],
                ['icon' => '➕', 'label' => 'เพิ่มสินค้า', 'url' => route('seller.products.create')],
            ]],
            ['section' => 'ระบบ POS', 'items' => [
                ['icon' => '🏪', 'label' => 'ขายสินค้า', 'url' => route('seller.pos.terminal')],
                ['icon' => '📊', 'label' => 'แดชบอร์ด POS', 'url' => route('seller.pos.dashboard')],
                ['icon' => '🧾', 'label' => 'รายการขาย', 'url' => route('seller.pos.sales')],
            ]],
            ['section' => 'ยอดขาย', 'items' => [
                ['icon' => '🛒', 'label' => 'คำสั่งซื้อ', 'url' => route('seller.orders.index')],
                ['icon' => '📈', 'label' => 'รายงานยอดขาย', 'url' => route('seller.reports.sales')],
                ['icon' => '📊', 'label' => 'วิเคราะห์', 'url' => route('seller.analytics')],
            ]],
            ['section' => 'การเงิน', 'items' => [
                ['icon' => '💰', 'label' => 'กระเป๋าเงิน', 'url' => route('seller.wallet.index')],
                ['icon' => '💸', 'label' => 'ถอนเงิน', 'url' => route('seller.wallet.withdraw')],
                ['icon' => '💵', 'label' => 'คอมมิชชั่น', 'url' => route('seller.commissions')],
            ]],
            ['section' => 'ตั้งค่า', 'items' => [
                ['icon' => '⚙️', 'label' => 'ตั้งค่าร้าน', 'url' => route('seller.settings')],
            ]],
        ];
    } else { // user
        $menuItems = [
            ['section' => 'หลัก', 'items' => [
                ['icon' => '📊', 'label' => 'แดชบอร์ด', 'url' => route('user.dashboard')],
                ['icon' => '👤', 'label' => 'โปรไฟล์', 'url' => route('user.profile')],
                ['icon' => '🪪', 'label' => 'ยืนยันตัวตน KYC', 'url' => route('user.kyc.index')],
            ]],
            ['section' => 'ช๊อปปิ้ง', 'items' => [
                ['icon' => '🛒', 'label' => 'ช๊อปสินค้า', 'url' => route('shop.index')],
            ]],
            ['section' => 'โรงแรม', 'items' => [
                ['icon' => '🏨', 'label' => 'จองโรงแรม', 'url' => route('hotels.index')],
                ['icon' => '📅', 'label' => 'การจองของฉัน', 'url' => route('hotels.bookings.index')],
            ]],
            ['section' => 'การเงิน', 'items' => [
                ['icon' => '💳', 'label' => 'กระเป๋าเงิน THB', 'url' => route('user.wallet.index')],
                ['icon' => '💸', 'label' => 'ถอนเงิน', 'url' => route('user.wallet.withdraw')],
                ['icon' => '₿', 'label' => 'กระเป๋าคริปโต', 'url' => route('user.crypto-wallet.index')],
                ['icon' => '💰', 'label' => 'คอมมิชชั่น', 'url' => route('user.commissions')],
            ]],
            ['section' => 'การลงทุน', 'items' => [
                ['icon' => '📈', 'label' => 'การลงทุน ROI', 'url' => route('user.investments.index')],
                ['icon' => '💎', 'label' => 'แผนการลงทุน', 'url' => route('user.investments.plans')],
            ]],
            ['section' => 'การเรียนรู้', 'items' => [
                ['icon' => '📚', 'label' => 'ศูนย์เรียนรู้', 'url' => route('learning-center.index')],
            ]],
            ['section' => 'AI & เครื่องมือ', 'items' => [
                ['icon' => '🤖', 'label' => 'ตลาดบอท', 'url' => route('marketplace.index')],
            ]],
            ['section' => 'ทีมงาน & MLM', 'items' => [
                ['icon' => '👥', 'label' => 'ผู้แนะนำ', 'url' => route('user.referrals')],
                ['icon' => '🌳', 'label' => 'ผังสายงาน', 'url' => route('user.organization')],
                ['icon' => '💖', 'label' => 'รักษายอด', 'url' => route('user.retention.index')],
                ['icon' => '🎯', 'label' => 'จำลองรายได้', 'url' => route('user.mlm.income-simulator')],
            ]],
            ['section' => 'อื่นๆ', 'items' => [
                ['icon' => '🎫', 'label' => 'Ticket Support', 'url' => route('user.tickets.index')],
                ['icon' => '🎨', 'label' => 'ตั้งค่าธีม', 'url' => route('user.themes.index')],
            ]],
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
    x-transition:enter="transition ease-out duration-400"
    x-transition:enter-start="opacity-0 -translate-x-full scale-95"
    x-transition:enter-end="opacity-100 translate-x-0 scale-100"
    x-transition:leave="transition ease-in duration-300"
    x-transition:leave-start="opacity-100 translate-x-0 scale-100"
    x-transition:leave-end="opacity-0 -translate-x-full scale-95"
    class="fixed left-0 top-0 bottom-0 w-80 md:w-96 z-[70] millennium-start-menu"
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
                @foreach($menuItems as $section)
                    <!-- Section Header with 3D Effect -->
                    <div class="mb-3 px-3 py-2 bg-gradient-to-r from-purple-500/20 to-pink-500/20 rounded-lg border border-purple-400/30 shadow-3d-sm backdrop-blur-sm">
                        <h3 class="text-xs font-bold text-purple-300 uppercase tracking-wider drop-shadow">{{ $section['section'] }}</h3>
                    </div>

                    <!-- Menu Items -->
                    <div class="space-y-1.5 mb-4">
                        @foreach($section['items'] as $item)
                            <a
                                href="{{ $item['url'] }}"
                                class="group flex items-center gap-3 px-3 py-2.5 rounded-lg bg-gradient-to-r from-white/5 to-white/10 hover:from-purple-500/30 hover:to-pink-500/30 border border-white/10 hover:border-purple-400/50 transition-all duration-300 transform hover:translate-x-1 hover:scale-[1.02] shadow-3d-card hover:shadow-3d-card-hover">

                                <!-- Icon with 3D Effect -->
                                <span class="text-2xl group-hover:scale-110 transition-transform duration-300 drop-shadow-lg filter-3d">
                                    {{ $item['icon'] }}
                                </span>

                                <!-- Label -->
                                <span class="text-sm font-semibold text-white/90 group-hover:text-white transition-colors duration-300 flex-1 drop-shadow">
                                    {{ $item['label'] }}
                                </span>

                                <!-- Arrow -->
                                <svg class="w-4 h-4 text-white/30 group-hover:text-purple-300 group-hover:translate-x-1 transition-all duration-300 drop-shadow" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        @endforeach
                    </div>
                @endforeach
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

    .shadow-3d-card {
        box-shadow:
            3px 3px 10px rgba(0, 0, 0, 0.3),
            -1px -1px 5px rgba(255, 255, 255, 0.03),
            inset 1px 1px 2px rgba(255, 255, 255, 0.08);
    }

    .shadow-3d-card:hover,
    .shadow-3d-card-hover {
        box-shadow:
            5px 5px 20px rgba(168, 85, 247, 0.4),
            0 0 15px rgba(236, 72, 153, 0.3),
            -2px -2px 8px rgba(255, 255, 255, 0.05),
            inset 1px 1px 3px rgba(255, 255, 255, 0.15);
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

    .filter-3d {
        filter: drop-shadow(2px 2px 3px rgba(0, 0, 0, 0.4));
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

    /* Perspective for 3D transforms */
    .millennium-start-menu {
        perspective: 1000px;
    }
</style>
