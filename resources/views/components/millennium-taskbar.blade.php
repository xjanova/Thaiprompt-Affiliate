@props(['type' => 'admin'])

@php
    use App\Models\WindowsUiSetting;

    $logo = \App\Models\Setting::get('logo');
    $appName = \App\Models\Setting::get('app_name', 'TP-Affiliate');

    // Taskbar settings
    $taskbarHeight = WindowsUiSetting::get('windows_taskbar_height', 60);
    $taskbarPosition = WindowsUiSetting::get('windows_taskbar_position', 'top');

    // Millennium-specific settings
    $backButtonEnabled = WindowsUiSetting::get('millennium_back_button_enabled', true);
    $backButtonText = WindowsUiSetting::get('millennium_back_button_text', 'กลับ');
    $centerSectionEnabled = WindowsUiSetting::get('millennium_center_section_enabled', true);
    $centerSectionText = WindowsUiSetting::get('millennium_center_section_text', '');
    $millenniumRgbEnabled = WindowsUiSetting::get('millennium_rgb_enabled', true);
    $millenniumRgbSpeed = WindowsUiSetting::get('millennium_rgb_speed', 5);

    // Get user info
    $user = auth()->user();

    // Define menu items based on type
    $menuItems = [];

    if ($type === 'admin') {
        $menuItems = [
            ['icon' => '📊', 'label' => 'แดชบอร์ด', 'url' => route('admin.dashboard'), 'color' => 'from-indigo-600 to-purple-600'],
            ['icon' => '👥', 'label' => 'ผู้ใช้งาน', 'url' => route('admin.users.index'), 'color' => 'from-blue-600 to-cyan-600'],
            ['icon' => '🏨', 'label' => 'จัดการโรงแรม', 'url' => route('admin.hotels.index'), 'color' => 'from-orange-600 to-amber-600'],
            ['icon' => '🛒', 'label' => 'อีคอมเมิร์ซ', 'url' => route('admin.ecommerce.products.index'), 'color' => 'from-green-600 to-emerald-600'],
            ['icon' => '🏪', 'label' => 'ระบบ POS', 'url' => route('admin.pos.dashboard'), 'color' => 'from-teal-600 to-cyan-600'],
            ['icon' => '💰', 'label' => 'กระเป๋าเงิน', 'url' => route('admin.wallet.index'), 'color' => 'from-yellow-600 to-orange-600'],
            ['icon' => '📧', 'label' => 'จัดการอีเมล', 'url' => route('admin.email.templates.index'), 'color' => 'from-blue-600 to-indigo-600'],
            ['icon' => '📱', 'label' => 'LINE OA & AI', 'url' => route('admin.line-oa.index'), 'color' => 'from-green-500 to-emerald-500'],
            ['icon' => '🎓', 'label' => 'Academy System', 'url' => route('admin.academy.courses.index'), 'color' => 'from-purple-600 to-pink-600'],
            ['icon' => '📈', 'label' => 'ระบบการตลาด', 'url' => route('admin.affiliates.index'), 'color' => 'from-pink-600 to-rose-600'],
            ['icon' => '⚙️', 'label' => 'ตั้งค่าระบบ', 'url' => route('admin.settings.index'), 'color' => 'from-gray-600 to-slate-600'],
        ];
    } elseif ($type === 'seller') {
        $menuItems = [
            ['icon' => '📊', 'label' => 'แดชบอร์ด', 'url' => route('seller.dashboard'), 'color' => 'from-cyan-600 to-blue-600'],
            ['icon' => '📦', 'label' => 'สินค้า', 'url' => route('seller.products.index'), 'color' => 'from-blue-600 to-indigo-600'],
            ['icon' => '🏪', 'label' => 'ระบบ POS', 'url' => route('seller.pos.terminal'), 'color' => 'from-green-500 to-emerald-600'],
            ['icon' => '🛒', 'label' => 'ยอดขาย', 'url' => route('seller.orders.index'), 'color' => 'from-orange-600 to-amber-600'],
            ['icon' => '📈', 'label' => 'วิเคราะห์', 'url' => route('seller.analytics'), 'color' => 'from-purple-600 to-pink-600'],
            ['icon' => '👤', 'label' => 'โปรไฟล์', 'url' => route('seller.profile'), 'color' => 'from-indigo-600 to-purple-600'],
        ];
    } else { // user
        $menuItems = [
            ['icon' => '📊', 'label' => 'แดชบอร์ด', 'url' => route('user.dashboard'), 'color' => 'from-indigo-600 to-purple-600'],
            ['icon' => '👤', 'label' => 'โปรไฟล์', 'url' => route('user.profile'), 'color' => 'from-blue-600 to-cyan-600'],
            ['icon' => '🪪', 'label' => 'ยืนยันตัวตน (KYC)', 'url' => route('user.kyc.index'), 'color' => 'from-purple-600 to-pink-600'],
            ['icon' => '💰', 'label' => 'คอมมิชชั่น', 'url' => route('user.commissions'), 'color' => 'from-yellow-600 to-orange-600'],
            ['icon' => '🛒', 'label' => 'ไปช๊อปปิ้ง', 'url' => route('user.shop.index'), 'color' => 'from-green-600 to-teal-600'],
            ['icon' => '🏨', 'label' => 'การจองโรงแรม', 'url' => route('hotels.bookings.index'), 'color' => 'from-orange-600 to-amber-600'],
            ['icon' => '🎫', 'label' => 'Ticket Support', 'url' => route('user.tickets.index'), 'color' => 'from-blue-600 to-indigo-600'],
            ['icon' => '💳', 'label' => 'กระเป๋าเงิน THB', 'url' => route('user.wallet.index'), 'color' => 'from-indigo-600 to-purple-600'],
            ['icon' => '₿', 'label' => 'กระเป๋าคริปโต', 'url' => route('user.crypto-wallet.index'), 'color' => 'from-amber-600 to-orange-600'],
            ['icon' => '📈', 'label' => 'การลงทุน ROI', 'url' => route('user.investments.index'), 'color' => 'from-purple-600 to-pink-600'],
            ['icon' => '👥', 'label' => 'ผู้แนะนำ', 'url' => route('user.referrals'), 'color' => 'from-pink-600 to-rose-600'],
            ['icon' => '🌳', 'label' => 'ผังสายงาน', 'url' => route('user.organization'), 'color' => 'from-green-600 to-emerald-600'],
            ['icon' => '💖', 'label' => 'รักษายอด', 'url' => route('user.retention.index'), 'color' => 'from-red-600 to-pink-600'],
            ['icon' => '🎨', 'label' => 'ตั้งค่าธีม', 'url' => route('user.themes.index'), 'color' => 'from-purple-600 to-pink-600'],
        ];
    }
@endphp

<!-- Millennium Taskbar + Start Menu Container -->
<div
    x-data="{
        startMenuOpen: false,
        isDark: localStorage.getItem('darkMode') === 'dark' || (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches),
        currentTime: '',
        updateTime() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            this.currentTime = hours + ':' + minutes;
        },
        toggleDarkMode() {
            this.isDark = !this.isDark;
            localStorage.setItem('darkMode', this.isDark ? 'dark' : 'light');
            if (this.isDark) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        },
        init() {
            this.updateTime();
            setInterval(() => this.updateTime(), 60000);
        }
    }"
    x-init="init()"
    class="millennium-container">

    <!-- Millennium Taskbar -->
    <div class="fixed left-0 right-0 z-50 {{ $taskbarPosition === 'top' ? 'top-0' : 'bottom-0' }} millennium-taskbar"
         style="height: {{ $taskbarHeight }}px;">

        <!-- RGB Border Animation -->
        @if($millenniumRgbEnabled)
            <div class="absolute inset-0 millennium-taskbar-rgb"></div>
        @endif

        <!-- Taskbar Background -->
        <div class="absolute inset-0 bg-gradient-to-r from-gray-900 via-purple-900/40 to-blue-900/40 backdrop-blur-xl border-{{ $taskbarPosition === 'top' ? 'b' : 't' }}-2 border-white/10 shadow-2xl"></div>

        <!-- Taskbar Content -->
        <div class="relative h-full max-w-full mx-auto px-3 flex items-center justify-between gap-3">

            <!-- Left Section: Start Button + Back Button -->
            <div class="flex items-center gap-3">

                <!-- Start Button -->
                <button
                    @click="startMenuOpen = !startMenuOpen"
                    :class="{'millennium-start-active': startMenuOpen}"
                    class="millennium-start-button group flex items-center gap-3 px-5 py-2.5 rounded-xl bg-gradient-to-r from-pink-600 via-purple-600 to-blue-600 hover:from-pink-500 hover:via-purple-500 hover:to-blue-500 transition-all duration-300 transform hover:scale-105 active:scale-95 shadow-lg hover:shadow-pink-500/50">

                    @if($logo)
                        <img src="{{ asset($logo) }}" alt="{{ $appName }}" class="w-9 h-9 object-contain drop-shadow-lg">
                    @else
                        <div class="w-9 h-9 bg-white/20 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M0 0h11v11H0V0zm13 0h11v11H13V0zM0 13h11v11H0V13zm13 0h11v11H13V13z"/>
                            </svg>
                        </div>
                    @endif

                    <span class="text-white font-bold text-xl hidden md:inline-block drop-shadow-lg">
                        เริ่ม
                    </span>

                    <!-- Glow Effect on Hover -->
                    <div class="absolute inset-0 rounded-xl opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"
                         style="background: linear-gradient(45deg, rgba(236, 72, 153, 0.3), rgba(168, 85, 247, 0.3), rgba(59, 130, 246, 0.3)); filter: blur(10px);"></div>
                </button>

                <!-- Back Button -->
                @if($backButtonEnabled)
                    <button
                        onclick="window.history.back()"
                        class="group flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-gray-700 to-gray-800 hover:from-gray-600 hover:to-gray-700 text-white transition-all duration-300 transform hover:scale-105 active:scale-95 shadow-lg"
                        title="{{ $backButtonText }}">
                        <svg class="w-6 h-6 group-hover:-translate-x-1 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                        </svg>
                        <span class="font-bold text-base hidden lg:inline-block">{{ $backButtonText }}</span>
                    </button>
                @endif

            </div>

            <!-- Center Section: Quick Icons -->
            <div class="flex items-center gap-2 flex-1 justify-center">

                <!-- Shopping Cart -->
                @if(Route::has('user.shop.cart'))
                <a href="{{ route('user.shop.cart') }}"
                   class="group relative flex items-center justify-center w-12 h-12 rounded-xl bg-white/10 hover:bg-gradient-to-br hover:from-green-500 hover:to-emerald-600 transition-all duration-300 transform hover:scale-110 active:scale-95"
                   title="รถเข็น">
                    <span class="text-2xl">🛒</span>
                    @php
                        $cartCount = 0;
                        try {
                            if (auth()->check() && session()->has('cart')) {
                                $cartCount = count(session('cart', []));
                            }
                        } catch (\Exception $e) {}
                    @endphp
                    @if($cartCount > 0)
                        <span class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs font-bold rounded-full flex items-center justify-center">
                            {{ $cartCount > 9 ? '9+' : $cartCount }}
                        </span>
                    @endif
                </a>
                @endif

                <!-- Tarot / ดูดวง -->
                @if(Route::has('tarot.index'))
                <a href="{{ route('tarot.index') }}"
                   class="group relative flex items-center justify-center w-12 h-12 rounded-xl bg-white/10 hover:bg-gradient-to-br hover:from-purple-500 hover:to-pink-600 transition-all duration-300 transform hover:scale-110 active:scale-95"
                   title="ดูดวง">
                    <span class="text-2xl">🔮</span>
                </a>
                @endif

                <!-- Bot Marketplace / เช่าบอท -->
                @if(Route::has('bots.marketplace'))
                <a href="{{ route('bots.marketplace') }}"
                   class="group relative flex items-center justify-center w-12 h-12 rounded-xl bg-white/10 hover:bg-gradient-to-br hover:from-blue-500 hover:to-cyan-600 transition-all duration-300 transform hover:scale-110 active:scale-95"
                   title="เช่าบอท">
                    <span class="text-2xl">🤖</span>
                </a>
                @endif

                <!-- Wallet / กระเป๋าเงิน -->
                @if(Route::has('user.wallet.index'))
                <a href="{{ route('user.wallet.index') }}"
                   class="group relative flex items-center justify-center w-12 h-12 rounded-xl bg-white/10 hover:bg-gradient-to-br hover:from-yellow-500 hover:to-orange-600 transition-all duration-300 transform hover:scale-110 active:scale-95"
                   title="กระเป๋าเงิน">
                    <span class="text-2xl">💰</span>
                </a>
                @endif

                <!-- ROI Investment -->
                @if(Route::has('user.investments.index'))
                <a href="{{ route('user.investments.index') }}"
                   class="group relative flex items-center justify-center w-12 h-12 rounded-xl bg-white/10 hover:bg-gradient-to-br hover:from-pink-500 hover:to-red-600 transition-all duration-300 transform hover:scale-110 active:scale-95"
                   title="การลงทุน ROI">
                    <span class="text-2xl">📈</span>
                </a>
                @endif

                <!-- Wiki -->
                @if(Route::has('wiki.index'))
                <a href="{{ route('wiki.index') }}"
                   class="group relative flex items-center justify-center w-12 h-12 rounded-xl bg-white/10 hover:bg-gradient-to-br hover:from-indigo-500 hover:to-purple-600 transition-all duration-300 transform hover:scale-110 active:scale-95"
                   title="Platform Wiki">
                    <span class="text-2xl">📚</span>
                </a>
                @endif

            </div>

            <!-- Right Section: System Tray -->
            <div class="flex items-center gap-3">

                <!-- Dark Mode Toggle -->
                <button
                    @click="toggleDarkMode()"
                    class="flex items-center justify-center w-12 h-12 rounded-xl bg-white/10 hover:bg-white/20 transition-all duration-300 transform hover:scale-110"
                    :class="isDark ? 'text-yellow-400' : 'text-gray-300'"
                    title="สลับโหมดมืด/สว่าง">
                    <!-- Sun Icon -->
                    <svg x-show="isDark" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <!-- Moon Icon -->
                    <svg x-show="!isDark" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                </button>

                <!-- Notification Badge -->
                @php
                    $notificationCount = 0;
                    try {
                        if (auth()->check()) {
                            $notificationCount = auth()->user()->unreadNotifications()->count();
                        }
                    } catch (\Exception $e) {}
                @endphp

                @if($notificationCount > 0)
                    <div class="relative">
                        <button class="flex items-center justify-center w-12 h-12 rounded-xl bg-white/10 hover:bg-white/20 transition-all duration-300 transform hover:scale-110">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                        </button>
                        <span class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs font-bold rounded-full flex items-center justify-center animate-pulse">
                            {{ $notificationCount > 9 ? '9+' : $notificationCount }}
                        </span>
                    </div>
                @endif

                <!-- Current Time -->
                <div class="hidden lg:flex items-center gap-2 px-4 py-2 rounded-xl bg-white/10 backdrop-blur-sm border border-white/10">
                    <svg class="w-5 h-5 text-pink-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-white font-bold text-base" x-text="currentTime"></span>
                </div>

                <!-- User Avatar -->
                @auth
                    <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-white/10 backdrop-blur-sm border border-white/10 hover:bg-white/20 transition-all duration-300 cursor-pointer">
                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-pink-500 via-purple-500 to-blue-500 flex items-center justify-center text-white font-bold text-sm ring-2 ring-white/30">
                            {{ substr(auth()->user()->name, 0, 2) }}
                        </div>
                        <span class="text-white font-semibold text-base hidden xl:inline-block">
                            {{ auth()->user()->name }}
                        </span>
                    </div>
                @endauth
            </div>

        </div>
    </div>

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
        x-transition:enter-start="opacity-0 -translate-x-full"
        x-transition:enter-end="opacity-100 translate-x-0"
        x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="opacity-100 translate-x-0"
        x-transition:leave-end="opacity-0 -translate-x-full"
        class="fixed left-0 top-0 bottom-0 w-96 md:w-[450px] z-[70] millennium-start-menu"
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
                <div class="p-8 bg-gradient-to-r from-pink-600 via-purple-600 to-blue-600 millennium-header-glow">
                    <div class="flex items-center gap-5">
                        @if($logo)
                            <div class="w-20 h-20 rounded-2xl overflow-hidden ring-4 ring-white/30 millennium-logo-pulse">
                                <img src="{{ asset($logo) }}" alt="{{ $appName }}" class="w-full h-full object-contain">
                            </div>
                        @else
                            <div class="w-20 h-20 bg-gradient-to-br from-cyan-400 to-blue-600 rounded-2xl flex items-center justify-center ring-4 ring-white/30 millennium-logo-pulse">
                                <svg class="w-12 h-12 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M0 0h11v11H0V0zm13 0h11v11H13V0zM0 13h11v11H0V13zm13 0h11v11H13V13z"/>
                                </svg>
                            </div>
                        @endif

                        <div class="flex-1">
                            <h2 class="text-3xl font-bold text-white drop-shadow-lg">{{ $appName }}</h2>
                            @if($user)
                                <p class="text-lg text-blue-100 mt-2 font-semibold">{{ $user->name }}</p>
                                <span class="inline-block mt-2 px-4 py-1 bg-white/20 backdrop-blur-sm rounded-full text-sm font-bold text-white">
                                    {{ $type === 'admin' ? '👑 Admin' : ($type === 'seller' ? '🏪 Seller' : '👤 User') }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Menu Items Section -->
                <div class="flex-1 p-5 overflow-y-auto millennium-scrollbar">
                    <div class="space-y-3">
                        @foreach($menuItems as $item)
                            <a
                                href="{{ $item['url'] }}"
                                class="group flex items-center gap-5 px-6 py-5 rounded-2xl bg-gradient-to-r from-white/5 to-white/10 hover:from-pink-500/30 hover:to-purple-500/30 border border-white/10 hover:border-pink-400/50 transition-all duration-300 transform hover:scale-105 hover:shadow-lg hover:shadow-pink-500/20 millennium-menu-item">

                                <!-- Icon -->
                                <span class="text-5xl group-hover:scale-110 transition-transform duration-300 drop-shadow-lg">
                                    {{ $item['icon'] }}
                                </span>

                                <!-- Label -->
                                <span class="text-xl font-bold text-white group-hover:text-pink-200 transition-colors duration-300 flex-1">
                                    {{ $item['label'] }}
                                </span>

                                <!-- Arrow -->
                                <svg class="w-7 h-7 ml-auto text-white/40 group-hover:text-pink-300 group-hover:translate-x-1 transition-all duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        @endforeach
                    </div>
                </div>

                <!-- Footer Section -->
                <div class="p-5 bg-gradient-to-r from-gray-900 via-purple-900/50 to-blue-900/50 border-t border-white/10">
                    @if($user)
                        <div class="flex items-center gap-4">
                            <!-- User Avatar -->
                            <div class="w-14 h-14 rounded-full bg-gradient-to-br from-pink-500 via-purple-500 to-blue-500 flex items-center justify-center text-white font-bold text-xl ring-4 ring-white/30">
                                {{ substr($user->name, 0, 2) }}
                            </div>

                            <!-- User Info & Logout -->
                            <div class="flex-1 min-w-0">
                                <p class="text-lg font-bold text-white truncate">{{ $user->name }}</p>
                                <p class="text-base text-gray-300 truncate">{{ $user->email }}</p>
                            </div>

                            <!-- Logout Button -->
                            <button
                                onclick="document.getElementById('millennium-logout-form').submit()"
                                class="w-14 h-14 rounded-2xl bg-gradient-to-br from-red-500 to-pink-600 hover:from-red-600 hover:to-pink-700 flex items-center justify-center text-white transition-all duration-300 transform hover:scale-110 hover:rotate-12 shadow-lg hover:shadow-red-500/50"
                                title="ออกจากระบบ">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                </svg>
                            </button>

                            <form id="millennium-logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                                @csrf
                            </form>
                        </div>
                    @else
                        <!-- Login/Register Buttons -->
                        <div class="flex gap-4">
                            <a href="{{ route('login') }}" class="flex-1 px-6 py-4 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white text-lg font-bold rounded-2xl text-center transition-all duration-300 transform hover:scale-105 shadow-lg">
                                เข้าสู่ระบบ
                            </a>
                            <a href="{{ route('register') }}" class="flex-1 px-6 py-4 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white text-lg font-bold rounded-2xl text-center transition-all duration-300 transform hover:scale-105 shadow-lg">
                                สมัครสมาชิก
                            </a>
                        </div>
                    @endif
                </div>

            </div>
        </div>
    </div>

</div>

<style>
    /* Millennium Taskbar RGB Border Animation */
    @keyframes millenniumTaskbarRgb {
        0%, 100% {
            background: linear-gradient(90deg,
                rgba(255, 0, 128, 0.8) 0%,
                rgba(0, 240, 255, 0.8) 25%,
                rgba(127, 0, 255, 0.8) 50%,
                rgba(255, 215, 0, 0.8) 75%,
                rgba(255, 0, 128, 0.8) 100%
            );
            background-size: 200% 100%;
            background-position: 0% 50%;
        }
        50% {
            background-position: 100% 50%;
        }
    }

    .millennium-taskbar-rgb {
        height: 3px;
        {{ $taskbarPosition === 'bottom' ? 'top: 0;' : 'bottom: 0;' }}
        background: linear-gradient(90deg,
            #FF0080 0%,
            #00F0FF 25%,
            #7F00FF 50%,
            #FFD700 75%,
            #FF0080 100%
        );
        background-size: 200% 100%;
        animation: millenniumTaskbarRgb {{ $millenniumRgbSpeed }}s linear infinite;
        filter: blur(2px);
        box-shadow: 0 0 15px currentColor, 0 0 30px currentColor;
    }

    /* Start Button Glow */
    @keyframes startButtonGlow {
        0%, 100% {
            box-shadow:
                0 0 20px rgba(236, 72, 153, 0.6),
                0 0 40px rgba(236, 72, 153, 0.4),
                0 4px 20px rgba(0, 0, 0, 0.3);
        }
        33% {
            box-shadow:
                0 0 20px rgba(168, 85, 247, 0.6),
                0 0 40px rgba(168, 85, 247, 0.4),
                0 4px 20px rgba(0, 0, 0, 0.3);
        }
        66% {
            box-shadow:
                0 0 20px rgba(59, 130, 246, 0.6),
                0 0 40px rgba(59, 130, 246, 0.4),
                0 4px 20px rgba(0, 0, 0, 0.3);
        }
    }

    .millennium-start-button {
        animation: startButtonGlow 3s ease-in-out infinite;
    }

    .millennium-start-button:hover {
        animation: startButtonGlow 1.5s ease-in-out infinite;
    }

    .millennium-start-active {
        background: linear-gradient(135deg, #ec4899, #a855f7, #3b82f6) !important;
        box-shadow:
            0 0 30px rgba(236, 72, 153, 0.8),
            0 0 50px rgba(168, 85, 247, 0.6),
            inset 0 0 20px rgba(255, 255, 255, 0.2) !important;
    }

    /* Taskbar Glass Morphism */
    .millennium-taskbar {
        backdrop-filter: blur(20px) saturate(180%);
    }

    /* Millennium Start Menu Styles */
    /* RGB Glow Effect */
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
</style>
