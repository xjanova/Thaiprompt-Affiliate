@props(['type' => 'admin'])

@php
    use App\Models\WindowsUiSetting;

    $logo = \App\Models\Setting::get('logo');
    $appName = \App\Models\Setting::get('app_name', 'TP-Affiliate');

    // Taskbar settings
    $taskbarHeight = WindowsUiSetting::get('windows_taskbar_height', 60);
    $taskbarPosition = WindowsUiSetting::get('windows_taskbar_position', 'top');

    // Millennium Taskbar Customization
    $taskbarOpacity = WindowsUiSetting::get('windows_taskbar_transparency', 95);
    $taskbarBlur = WindowsUiSetting::get('windows_taskbar_blur', true);
    $taskbarBlurAmount = WindowsUiSetting::get('millennium_taskbar_blur_amount', 20);

    // RGB Settings
    $millenniumRgbEnabled = WindowsUiSetting::get('millennium_rgb_enabled', true);
    $millenniumRgbSpeed = WindowsUiSetting::get('millennium_rgb_speed', 5);
    $millenniumRgbBlur = WindowsUiSetting::get('millennium_rgb_blur', 2);
    $millenniumRgbGlowSize = WindowsUiSetting::get('millennium_rgb_glow_size', 15);
    $millenniumRgbColors = WindowsUiSetting::get('millennium_rgb_colors', ['#FF0080', '#00F0FF', '#7F00FF', '#FFD700']);

    // Start Button Settings
    $startButtonPosition = WindowsUiSetting::get('millennium_start_button_position', 'center');
    $startButtonWidth = WindowsUiSetting::get('millennium_start_button_width', 120);
    $startButtonHeight = WindowsUiSetting::get('millennium_start_button_height', 48);
    $startButtonShape = WindowsUiSetting::get('millennium_start_button_shape', 'rounded');
    $startButtonBorderRadius = WindowsUiSetting::get('millennium_start_button_border_radius', 16);
    $startButtonShowIcon = WindowsUiSetting::get('millennium_start_button_show_icon', true);
    $startButtonShowText = WindowsUiSetting::get('millennium_start_button_show_text', true);
    $startButtonIconSize = WindowsUiSetting::get('millennium_start_button_icon_size', 32);
    $startButtonFontSize = WindowsUiSetting::get('millennium_start_button_font_size', 20);

    // Back Button Settings
    $backButtonEnabled = WindowsUiSetting::get('millennium_back_button_enabled', true);
    $backButtonText = WindowsUiSetting::get('millennium_back_button_text', 'กลับ');
    $backButtonShowIcon = WindowsUiSetting::get('millennium_back_button_show_icon', true);
    $backButtonShowText = WindowsUiSetting::get('millennium_back_button_show_text', true);

    // Clock Settings
    $clockFormat = WindowsUiSetting::get('millennium_clock_format', '24h');
    $clockShowSeconds = WindowsUiSetting::get('millennium_clock_show_seconds', false);
    $clockShowDate = WindowsUiSetting::get('millennium_clock_show_date', false);
    $clockDateFormat = WindowsUiSetting::get('millennium_clock_date_format', 'short');
    $clockStyle = WindowsUiSetting::get('millennium_clock_style', 'digital');

    // Taskbar Icons
    $taskbarIcons = WindowsUiSetting::get('millennium_taskbar_icons', [
        ['icon' => '🛒', 'label' => 'รถเข็น', 'url' => '/cart', 'border' => false, 'opacity' => 10, 'order' => 1],
        ['icon' => '🔮', 'label' => 'ดูดวง', 'url' => '/tarot', 'border' => false, 'opacity' => 10, 'order' => 2],
        ['icon' => '🤖', 'label' => 'เช่าบอท', 'url' => '/marketplace', 'border' => false, 'opacity' => 10, 'order' => 3],
        ['icon' => '💰', 'label' => 'กระเป๋าเงิน', 'url' => '/user/wallet', 'border' => false, 'opacity' => 10, 'order' => 4],
        ['icon' => '📈', 'label' => 'การลงทุน ROI', 'url' => '/user/investments', 'border' => false, 'opacity' => 10, 'order' => 5],
        ['icon' => '📚', 'label' => 'Platform Wiki', 'url' => '/platform-wiki', 'border' => false, 'opacity' => 10, 'order' => 6],
    ]);
    $taskbarIconSize = WindowsUiSetting::get('millennium_taskbar_icon_size', 48);
    $taskbarIconBorderRadius = WindowsUiSetting::get('millennium_taskbar_icon_border_radius', 12);

    // Sort taskbar icons by order
    usort($taskbarIcons, fn($a, $b) => ($a['order'] ?? 0) - ($b['order'] ?? 0));

    // Calculate start button border radius based on shape
    $startButtonRadius = match($startButtonShape) {
        'square' => 0,
        'rounded' => $startButtonBorderRadius,
        'pill' => 9999,
        'circle' => 9999,
        default => $startButtonBorderRadius,
    };

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
            ['icon' => '💰', 'label' => 'เส้นทางเศรษฐี', 'url' => route('user.wealth-guide'), 'color' => 'from-yellow-600 via-amber-600 to-orange-600', 'highlight' => true],
            ['icon' => '👤', 'label' => 'โปรไฟล์', 'url' => route('user.profile'), 'color' => 'from-blue-600 to-cyan-600'],
            ['icon' => '🪪', 'label' => 'ยืนยันตัวตน (KYC)', 'url' => route('user.kyc.index'), 'color' => 'from-purple-600 to-pink-600'],
            ['icon' => '💵', 'label' => 'คอมมิชชั่น', 'url' => route('user.commissions'), 'color' => 'from-green-600 to-emerald-600'],
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
        currentDate: '',
        clockFormat: '{{ $clockFormat }}',
        showSeconds: {{ $clockShowSeconds ? 'true' : 'false' }},
        showDate: {{ $clockShowDate ? 'true' : 'false' }},
        dateFormat: '{{ $clockDateFormat }}',
        updateTime() {
            const now = new Date();
            let hours = now.getHours();
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');

            // Format based on clock format
            if (this.clockFormat === '12h') {
                const ampm = hours >= 12 ? 'PM' : 'AM';
                hours = hours % 12 || 12;
                this.currentTime = String(hours).padStart(2, '0') + ':' + minutes + (this.showSeconds ? ':' + seconds : '') + ' ' + ampm;
            } else {
                this.currentTime = String(hours).padStart(2, '0') + ':' + minutes + (this.showSeconds ? ':' + seconds : '');
            }

            // Format date if enabled
            if (this.showDate) {
                if (this.dateFormat === 'long') {
                    this.currentDate = now.toLocaleDateString('th-TH', { year: 'numeric', month: 'long', day: 'numeric' });
                } else {
                    this.currentDate = now.toLocaleDateString('th-TH', { year: '2-digit', month: '2-digit', day: '2-digit' });
                }
            }
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
            const interval = this.showSeconds ? 1000 : 60000;
            setInterval(() => this.updateTime(), interval);
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
        <div class="absolute inset-0 bg-gradient-to-r from-gray-900 via-purple-900/50 to-blue-900/50 backdrop-blur border-{{ $taskbarPosition === 'top' ? 'b' : 't' }}-2 border-white/20 shadow-2xl rounded-2xl mx-2 my-1"
             style="opacity: {{ $taskbarOpacity / 100 }}; backdrop-filter: blur({{ $taskbarBlurAmount }}px); box-shadow: 0 0 30px rgba(168, 85, 247, 0.3), 0 0 60px rgba(59, 130, 246, 0.2);"></div>

        <!-- Taskbar Content -->
        <div class="relative h-full max-w-full mx-auto px-3 flex items-center justify-between gap-3">

            <!-- Left Section: Back Button + Quick Icons -->
            <div class="flex items-center gap-2 flex-1">

                <!-- Back Button -->
                @if($backButtonEnabled)
                    <button
                        onclick="window.history.back()"
                        class="group flex items-center gap-2 px-4 py-2 rounded-xl bg-gradient-to-r from-gray-700/80 to-gray-800/80 hover:from-indigo-600 hover:to-purple-600 text-white transition-all duration-300 transform hover:scale-105 active:scale-95 shadow-lg hover:shadow-indigo-500/50"
                        title="{{ $backButtonText }}">
                        @if($backButtonShowIcon)
                            <svg class="w-5 h-5 group-hover:-translate-x-1 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                            </svg>
                        @endif
                        @if($backButtonShowText)
                            <span class="font-bold text-sm hidden lg:inline-block">{{ $backButtonText }}</span>
                        @endif
                    </button>
                @endif

                <!-- Dynamic Taskbar Icons -->
                @foreach($taskbarIcons as $taskbarIcon)
                    <a href="{{ $taskbarIcon['url'] }}"
                       class="group relative flex items-center justify-center rounded-xl transition-all duration-300 transform hover:scale-110 active:scale-95 {{ $taskbarIcon['border'] ? 'border-2 border-white/20' : '' }}"
                       style="width: {{ $taskbarIconSize }}px; height: {{ $taskbarIconSize }}px; border-radius: {{ $taskbarIconBorderRadius }}px; background: rgba(255, 255, 255, {{ ($taskbarIcon['opacity'] ?? 10) / 100 }}); background-image: linear-gradient(135deg, rgba(168, 85, 247, 0.3), rgba(59, 130, 246, 0.3));"
                       title="{{ $taskbarIcon['label'] }}">
                        <span style="font-size: {{ ($taskbarIconSize * 0.5) }}px;">{{ $taskbarIcon['icon'] }}</span>
                    </a>
                @endforeach

                <!-- Wealth Guide (E-book) -->
                @if(Route::has('user.wealth-guide'))
                <a href="{{ route('user.wealth-guide') }}"
                   class="group relative flex items-center justify-center w-12 h-12 rounded-xl bg-white/10 hover:bg-gradient-to-br hover:from-yellow-500 hover:via-amber-500 hover:to-orange-600 transition-all duration-300 transform hover:scale-110 active:scale-95 animate-pulse"
                   title="เส้นทางเศรษฐี - คู่มือสู่ความร่ำรวย">
                    <span class="text-2xl">💰</span>
                    <span class="absolute -top-1 -right-1 w-4 h-4 bg-gradient-to-r from-yellow-400 to-orange-500 rounded-full flex items-center justify-center">
                        <span class="text-white text-xs font-bold">📖</span>
                    </span>
                </a>
                @endif

            </div>

            <!-- Center Section: Start Button -->
            @php
                $startButtonJustify = match($startButtonPosition) {
                    'left' => 'justify-start',
                    'right' => 'justify-end',
                    default => 'justify-center',
                };
                $startButtonAbsoluteClass = $startButtonPosition === 'center' ? 'absolute left-1/2 transform -translate-x-1/2' : '';
            @endphp
            <div class="flex items-center {{ $startButtonPosition === 'center' ? $startButtonAbsoluteClass : $startButtonJustify }}">
                <!-- Start Button -->
                <button
                    @click="startMenuOpen = !startMenuOpen"
                    :class="{'millennium-start-active': startMenuOpen}"
                    class="millennium-start-button group flex items-center gap-3 bg-gradient-to-r from-pink-600 via-purple-600 to-blue-600 hover:from-pink-500 hover:via-purple-500 hover:to-blue-500 transition-all duration-300 transform hover:scale-110 active:scale-95 shadow-2xl hover:shadow-pink-500/70"
                    style="width: {{ $startButtonWidth }}px; height: {{ $startButtonHeight }}px; border-radius: {{ $startButtonRadius }}px; margin-top: -8px; margin-bottom: -8px; {{ !$startButtonShowIcon && !$startButtonShowText ? 'padding: 12px;' : 'padding: 0 20px;' }}">

                    @if($startButtonShowIcon)
                        @if($logo)
                            <img src="{{ asset($logo) }}" alt="{{ $appName }}" class="object-contain drop-shadow-2xl" style="width: {{ $startButtonIconSize }}px; height: {{ $startButtonIconSize }}px;">
                        @else
                            <div class="bg-white/20 rounded-xl flex items-center justify-center" style="width: {{ $startButtonIconSize }}px; height: {{ $startButtonIconSize }}px;">
                                <svg class="text-white" fill="currentColor" viewBox="0 0 24 24" style="width: {{ $startButtonIconSize * 0.7 }}px; height: {{ $startButtonIconSize * 0.7 }}px;">
                                    <path d="M0 0h11v11H0V0zm13 0h11v11H13V0zM0 13h11v11H0V13zm13 0h11v11H13V13z"/>
                                </svg>
                            </div>
                        @endif
                    @endif

                    @if($startButtonShowText)
                        <span class="text-white font-bold hidden md:inline-block drop-shadow-2xl" style="font-size: {{ $startButtonFontSize }}px;">
                            เริ่ม
                        </span>
                    @endif

                    <!-- Glow Effect on Hover -->
                    <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"
                         style="border-radius: {{ $startButtonRadius }}px; background: linear-gradient(45deg, rgba(236, 72, 153, 0.4), rgba(168, 85, 247, 0.4), rgba(59, 130, 246, 0.4)); filter: blur(15px);"></div>
                </button>
            </div>

            <!-- Right Section: System Tray -->
            <div class="flex items-center gap-3 flex-1 justify-end">

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

                <!-- Current Time & Date -->
                @if($clockStyle === 'digital')
                    <div class="hidden lg:flex flex-col items-end gap-1 px-4 py-2 rounded-xl bg-white/10 backdrop-blur-sm border border-white/10">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-pink-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="text-white font-bold text-base" x-text="currentTime"></span>
                        </div>
                        @if($clockShowDate)
                            <span class="text-white/70 text-xs" x-text="currentDate"></span>
                        @endif
                    </div>
                @endif

            </div>

        </div>
    </div>

    <!-- Include Millennium Start Menu Component -->
    <x-millennium-start-menu :type="$type" />

</div>

<style>
    /* Millennium Taskbar RGB Border Animation */
    @keyframes millenniumTaskbarRgb {
        0%, 100% {
            background: linear-gradient(90deg,
                {{ $millenniumRgbColors[0] ?? '#FF0080' }}80 0%,
                {{ $millenniumRgbColors[1] ?? '#00F0FF' }}80 25%,
                {{ $millenniumRgbColors[2] ?? '#7F00FF' }}80 50%,
                {{ $millenniumRgbColors[3] ?? '#FFD700' }}80 75%,
                {{ $millenniumRgbColors[0] ?? '#FF0080' }}80 100%
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
            {{ $millenniumRgbColors[0] ?? '#FF0080' }} 0%,
            {{ $millenniumRgbColors[1] ?? '#00F0FF' }} 25%,
            {{ $millenniumRgbColors[2] ?? '#7F00FF' }} 50%,
            {{ $millenniumRgbColors[3] ?? '#FFD700' }} 75%,
            {{ $millenniumRgbColors[0] ?? '#FF0080' }} 100%
        );
        background-size: 200% 100%;
        animation: millenniumTaskbarRgb {{ $millenniumRgbSpeed }}s linear infinite;
        filter: blur({{ $millenniumRgbBlur }}px);
        box-shadow: 0 0 {{ $millenniumRgbGlowSize }}px currentColor, 0 0 {{ $millenniumRgbGlowSize * 2 }}px currentColor;
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
        border-radius: {{ $startButtonRadius }}px !important;
    }

    /* Taskbar Glass Morphism */
    .millennium-taskbar {
        backdrop-filter: blur(20px) saturate(180%);
    }
</style>
