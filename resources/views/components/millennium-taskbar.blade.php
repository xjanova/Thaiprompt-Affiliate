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

    // Taskbar Color Settings
    $taskbarBgColor = WindowsUiSetting::get('windows_taskbar_bg_color', '#1e293b');
    $taskbarTextColor = WindowsUiSetting::get('windows_taskbar_text_color', '#ffffff');
    $taskbarHoverBgColor = WindowsUiSetting::get('windows_taskbar_hover_bg_color', '#334155');
    $taskbarActiveBgColor = WindowsUiSetting::get('windows_taskbar_active_bg_color', '#475569');
    $taskbarBorderColor = WindowsUiSetting::get('windows_taskbar_border_color', '#475569');
    $taskbarUseGradient = WindowsUiSetting::get('windows_taskbar_use_gradient', false);
    $taskbarGradientFrom = WindowsUiSetting::get('windows_taskbar_gradient_from', '#1e293b');
    $taskbarGradientTo = WindowsUiSetting::get('windows_taskbar_gradient_to', '#0f172a');

    // RGB Settings
    $millenniumRgbEnabled = WindowsUiSetting::get('millennium_rgb_enabled', true);
    $millenniumRgbSpeed = WindowsUiSetting::get('millennium_rgb_speed', 5);
    $millenniumRgbBlur = WindowsUiSetting::get('millennium_rgb_blur', 2);
    $millenniumRgbGlowSize = WindowsUiSetting::get('millennium_rgb_glow_size', 15);
    $millenniumRgbColors = WindowsUiSetting::get('millennium_rgb_colors', ['#FF0080', '#00F0FF', '#7F00FF', '#FFD700']);

    // Start Button Settings
    $startButtonPosition = WindowsUiSetting::get('windows_start_button_position', 'center');
    $startButtonWidth = WindowsUiSetting::get('millennium_start_button_width', 120);
    $startButtonHeight = WindowsUiSetting::get('millennium_start_button_height', 48);
    $startButtonShape = WindowsUiSetting::get('millennium_start_button_shape', 'rounded');
    $startButtonBorderRadius = WindowsUiSetting::get('millennium_start_button_border_radius', 16);
    $startButtonShowIcon = WindowsUiSetting::get('millennium_start_button_show_icon', true);
    $startButtonShowText = WindowsUiSetting::get('millennium_start_button_show_text', true);
    $startButtonText = WindowsUiSetting::get('millennium_start_button_text', 'เริ่ม');
    $startButtonIconSize = WindowsUiSetting::get('millennium_start_button_icon_size', 32);
    $startButtonFontSize = WindowsUiSetting::get('millennium_start_button_font_size', 20);

    // Start Button Icon Settings
    $startButtonIconType = WindowsUiSetting::get('millennium_start_button_icon_type', 'default');
    $startButtonCustomIconPath = WindowsUiSetting::get('millennium_start_button_custom_icon_path');
    $startButtonFontAwesomeIcon = WindowsUiSetting::get('millennium_start_button_fontawesome_icon', 'fa-rocket');
    $startButtonEmoji = WindowsUiSetting::get('millennium_start_button_emoji', '🚀');
    $startButtonIconPosition = WindowsUiSetting::get('millennium_start_button_icon_position', 'left');

    // Fallback: Ensure text is always shown with proper values
    if ($startButtonShowText === false || $startButtonShowText === 0 || $startButtonShowText === '0' || $startButtonShowText === null) {
        $startButtonShowText = true; // Force enable if not explicitly set
    }
    if (empty($startButtonText) || trim($startButtonText) === '') {
        $startButtonText = 'เริ่ม'; // Force default text
    }
    if (empty($startButtonFontSize) || $startButtonFontSize < 12 || !is_numeric($startButtonFontSize)) {
        $startButtonFontSize = 20; // Force default size
    }

    // Start Button Tooltip Settings
    $tooltipEnabled = WindowsUiSetting::get('millennium_start_button_tooltip_enabled', true);
    $tooltipText = WindowsUiSetting::get('millennium_start_button_tooltip_text', 'คลิกที่นี่เพื่อเริ่มต้น! 🚀');
    $tooltipDuration = WindowsUiSetting::get('millennium_start_button_tooltip_duration', 8);
    $tooltipPosition = WindowsUiSetting::get('millennium_start_button_tooltip_position', 'top');
    $tooltipAnimation = WindowsUiSetting::get('millennium_start_button_tooltip_animation', 'bounce');

    // Responsive Taskbar Settings
    $taskbarCollapseEnabled = WindowsUiSetting::get('millennium_taskbar_collapse_enabled', true);
    $taskbarCollapseBreakpoint = WindowsUiSetting::get('millennium_taskbar_collapse_breakpoint', 768);
    $taskbarCollapseStyle = WindowsUiSetting::get('millennium_taskbar_collapse_style', 'slide-up'); // dropdown, slide-up, fullscreen

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

    // Back to Top Button Settings
    $backToTopEnabled = WindowsUiSetting::get('millennium_back_to_top_enabled', true);
    $backToTopThreshold = WindowsUiSetting::get('millennium_back_to_top_threshold', 20);
    $backToTopAnimation = WindowsUiSetting::get('millennium_back_to_top_animation', 'fade');

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
            ['icon' => '📈', 'label' => 'วิเคราะห์ระบบ', 'url' => route('admin.analytics.index'), 'color' => 'from-cyan-600 to-blue-600'],
            ['icon' => '👥', 'label' => 'ผู้ใช้งาน', 'url' => route('admin.users.index'), 'color' => 'from-blue-600 to-cyan-600'],
            ['icon' => '🔒', 'label' => 'ความปลอดภัย', 'url' => route('admin.security.index'), 'color' => 'from-red-600 to-rose-600', 'highlight' => true],
            ['icon' => '🎨', 'label' => 'AI Gen System', 'url' => route('admin.ai-gen.dashboard'), 'color' => 'from-violet-600 via-fuchsia-600 to-pink-600', 'highlight' => true],
            ['icon' => '🏨', 'label' => 'จัดการโรงแรม', 'url' => route('admin.hotels.index'), 'color' => 'from-orange-600 to-amber-600'],
            ['icon' => '👨‍💼', 'label' => 'ผู้เช่าโรงแรม', 'url' => route('admin.hotel-owners.index'), 'color' => 'from-amber-600 to-yellow-600'],
            ['icon' => '🛒', 'label' => 'อีคอมเมิร์ซ', 'url' => route('admin.ecommerce.products.index'), 'color' => 'from-green-600 to-emerald-600'],
            ['icon' => '🏪', 'label' => 'ระบบ POS', 'url' => route('admin.pos.dashboard'), 'color' => 'from-teal-600 to-cyan-600'],
            ['icon' => '💰', 'label' => 'กระเป๋าเงิน', 'url' => route('admin.wallet.index'), 'color' => 'from-yellow-600 to-orange-600'],
            ['icon' => '₿', 'label' => 'คริปโตเคอเรนซี', 'url' => route('admin.crypto.dashboard'), 'color' => 'from-amber-500 to-orange-500', 'highlight' => true],
            ['icon' => '📊', 'label' => 'ระบบบัญชี', 'url' => route('admin.accounting.dashboard'), 'color' => 'from-emerald-600 to-green-600', 'highlight' => true],
            ['icon' => '🤖', 'label' => 'Bot Automation', 'url' => route('admin.bot-automation.dashboard'), 'color' => 'from-violet-600 to-purple-600', 'highlight' => true],
            ['icon' => '👔', 'label' => 'ระบบ HRM', 'url' => route('admin.hrm.dashboard'), 'color' => 'from-blue-500 to-cyan-500', 'highlight' => true],
            ['icon' => '🌐', 'label' => 'MLM System', 'url' => route('admin.mlm.reports.index'), 'color' => 'from-fuchsia-600 to-pink-600'],
            ['icon' => '🎨', 'label' => 'Smart Sliders', 'url' => route('admin.smart-sliders.index'), 'color' => 'from-rose-600 to-pink-600'],
            ['icon' => '🤖', 'label' => 'AI Management', 'url' => route('admin.ai-providers.index'), 'color' => 'from-purple-500 to-violet-500', 'highlight' => true],
            ['icon' => '🔌', 'label' => 'API Management', 'url' => route('admin.api-endpoints.index'), 'color' => 'from-slate-600 to-gray-600'],
            ['icon' => '📱', 'label' => 'App Management', 'url' => route('admin.app-management.settings.index'), 'color' => 'from-sky-600 to-blue-600'],
            ['icon' => '📧', 'label' => 'จัดการอีเมล', 'url' => route('admin.email.templates.index'), 'color' => 'from-blue-600 to-indigo-600'],
            ['icon' => '📱', 'label' => 'LINE OA & AI', 'url' => route('admin.line-oa.index'), 'color' => 'from-green-500 to-emerald-500'],
            ['icon' => '🎓', 'label' => 'Academy System', 'url' => route('admin.academy.courses.index'), 'color' => 'from-purple-600 to-pink-600'],
            ['icon' => '🎮', 'label' => 'เกม & เอนเตอร์เทนเมนต์', 'url' => route('admin.games.index'), 'color' => 'from-cyan-500 to-blue-500', 'highlight' => true],
            ['icon' => '🐍', 'label' => 'Snake.io Monitor', 'url' => route('admin.games.snake-io.monitor'), 'color' => 'from-lime-500 to-green-500', 'highlight' => true],
            ['icon' => '⚙️', 'label' => 'ตั้งค่าเกม (IP/Port)', 'url' => route('admin.games.game-settings.index'), 'color' => 'from-violet-500 to-purple-500', 'highlight' => true],
            ['icon' => '📊', 'label' => 'ระบบการตลาด', 'url' => route('admin.affiliates.index'), 'color' => 'from-pink-600 to-rose-600'],
            ['icon' => '⚙️', 'label' => 'ตั้งค่าระบบ', 'url' => route('admin.settings.index'), 'color' => 'from-gray-600 to-slate-600'],
        ];

        // Add Developer Release Manager (only visible to whitelisted IPs)
        if (\App\Http\Middleware\DevMode::isDevMode()) {
            $menuItems[] = ['icon' => '🛠️', 'label' => 'Dev Release Manager', 'url' => route('admin.dev.releases.index'), 'color' => 'from-red-600 to-orange-600', 'highlight' => true];
        }
    } elseif ($type === 'seller') {
        $menuItems = [
            ['icon' => '📊', 'label' => 'แดชบอร์ด', 'url' => route('seller.dashboard'), 'color' => 'from-cyan-600 to-blue-600'],
            ['icon' => '📦', 'label' => 'สินค้า', 'url' => route('seller.products.index'), 'color' => 'from-blue-600 to-indigo-600'],
            ['icon' => '🏪', 'label' => 'ระบบ POS', 'url' => route('seller.pos.terminal'), 'color' => 'from-green-500 to-emerald-600'],
            ['icon' => '🛒', 'label' => 'ยอดขาย', 'url' => route('seller.orders.index'), 'color' => 'from-orange-600 to-amber-600'],
            ['icon' => '📈', 'label' => 'วิเคราะห์', 'url' => route('seller.analytics.index'), 'color' => 'from-purple-600 to-pink-600'],
            ['icon' => '👤', 'label' => 'โปรไฟล์', 'url' => route('seller.profile'), 'color' => 'from-indigo-600 to-purple-600'],
        ];
    } else { // user
        $menuItems = [
            ['icon' => '📊', 'label' => 'แดชบอร์ด', 'url' => route('user.dashboard'), 'color' => 'from-indigo-600 to-purple-600'],
            ['icon' => '💰', 'label' => 'เส้นทางเศรษฐี', 'url' => route('user.wealth-guide'), 'color' => 'from-yellow-600 via-amber-600 to-orange-600', 'highlight' => true],
            ['icon' => '🎨', 'label' => 'AI Gen (สร้างภาพ/วิดีโอ)', 'url' => route('user.ai-gen.index'), 'color' => 'from-violet-600 via-purple-600 to-fuchsia-600', 'highlight' => true],
            ['icon' => '👤', 'label' => 'โปรไฟล์', 'url' => route('user.profile'), 'color' => 'from-blue-600 to-cyan-600'],
            ['icon' => '🪪', 'label' => 'ยืนยันตัวตน (KYC)', 'url' => route('user.kyc.index'), 'color' => 'from-purple-600 to-pink-600'],
            ['icon' => '🔐', 'label' => 'Two-Factor Auth', 'url' => route('user.two-factor.setup'), 'color' => 'from-red-500 to-rose-500', 'highlight' => true],
            ['icon' => '🌐', 'label' => 'MLM Dashboard', 'url' => route('user.mlm.dashboard'), 'color' => 'from-violet-600 to-purple-600', 'highlight' => true],
            ['icon' => '💵', 'label' => 'คอมมิชชั่น', 'url' => route('user.commissions'), 'color' => 'from-green-600 to-emerald-600'],
            ['icon' => '🏨', 'label' => 'การจองโรงแรม', 'url' => route('hotels.bookings.index'), 'color' => 'from-orange-600 to-amber-600'],
            ['icon' => '🎫', 'label' => 'Ticket Support', 'url' => route('user.tickets.index'), 'color' => 'from-blue-600 to-indigo-600'],
            ['icon' => '💳', 'label' => 'กระเป๋าเงิน THB', 'url' => route('user.wallet.index'), 'color' => 'from-indigo-600 to-purple-600'],
            ['icon' => '₿', 'label' => 'กระเป๋าคริปโต', 'url' => route('user.crypto-wallet.index'), 'color' => 'from-amber-600 to-orange-600'],
            ['icon' => '📈', 'label' => 'การลงทุน ROI', 'url' => route('user.investments.index'), 'color' => 'from-purple-600 to-pink-600'],
            ['icon' => '👥', 'label' => 'ทีมงาน', 'url' => route('user.team'), 'color' => 'from-pink-600 to-rose-600'],
            ['icon' => '💖', 'label' => 'รักษายอด', 'url' => route('user.retention.index'), 'color' => 'from-red-600 to-pink-600'],
            ['icon' => '📦', 'label' => 'ที่อยู่จัดส่ง', 'url' => route('shipping-addresses.index'), 'color' => 'from-cyan-600 to-blue-600'],
            ['icon' => '🔔', 'label' => 'การแจ้งเตือน', 'url' => route('user.notifications.index'), 'color' => 'from-sky-600 to-blue-600'],
            ['icon' => '⚙️', 'label' => 'ตั้งค่าธีม', 'url' => route('user.themes.index'), 'color' => 'from-purple-600 to-pink-600'],
        ];

        // Add Hotel Owner menu if user is hotel admin
        if ($user && $user->is_hotel_admin && $user->managed_hotel_id) {
            array_splice($menuItems, 6, 0, [
                ['icon' => '🏨', 'label' => 'จัดการโรงแรม', 'url' => route('hotel-admin.dashboard'), 'color' => 'from-orange-600 to-red-600', 'highlight' => true],
            ]);
        }
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
        showBackToTop: false,
        backToTopThreshold: {{ $backToTopThreshold }},
        showTooltip: false,
        tooltipEnabled: {{ $tooltipEnabled ? 'true' : 'false' }},
        tooltipDuration: {{ $tooltipDuration }},
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
        scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        },
        handleScroll() {
            const scrollPercentage = (window.scrollY / (document.documentElement.scrollHeight - window.innerHeight)) * 100;
            this.showBackToTop = scrollPercentage >= this.backToTopThreshold;
        },
        initTooltip() {
            if (!this.tooltipEnabled) return;

            const hasSeenTooltip = localStorage.getItem('millennium_start_button_tooltip_seen');

            if (!hasSeenTooltip) {
                // Show tooltip after a short delay
                setTimeout(() => {
                    this.showTooltip = true;

                    // Auto hide after duration
                    setTimeout(() => {
                        this.showTooltip = false;
                        localStorage.setItem('millennium_start_button_tooltip_seen', 'true');
                    }, this.tooltipDuration * 1000);
                }, 1500); // Wait 1.5s before showing
            }
        },
        hideTooltip() {
            this.showTooltip = false;
            localStorage.setItem('millennium_start_button_tooltip_seen', 'true');
        },
        init() {
            this.updateTime();
            const interval = this.showSeconds ? 1000 : 60000;
            setInterval(() => this.updateTime(), interval);

            // Set up scroll listener for Back to Top button
            window.addEventListener('scroll', () => this.handleScroll());
            this.handleScroll();

            // Initialize tooltip
            this.initTooltip();
        }
    }"
    x-init="init()"
    class="millennium-container">

    <!-- Millennium Taskbar - Windows 11 Style -->
    <div class="fixed left-0 right-0 z-50 {{ $taskbarPosition === 'top' ? 'top-0' : 'bottom-0' }} millennium-taskbar"
         style="
            height: {{ $taskbarHeight }}px;
            --taskbar-text-color: {{ $taskbarTextColor }};
            --taskbar-hover-bg: {{ $taskbarHoverBgColor }};
            --taskbar-active-bg: {{ $taskbarActiveBgColor }};
         ">

        <!-- RGB Border Animation -->
        @if($millenniumRgbEnabled)
            <div class="absolute inset-0 millennium-taskbar-rgb"></div>
        @endif

        <!-- Taskbar Background - Windows 11 Style -->
        <div class="absolute inset-0 border-{{ $taskbarPosition === 'top' ? 'b' : 't' }}-2"
             style="
                opacity: {{ $taskbarOpacity / 100 }};
                backdrop-filter: blur({{ $taskbarBlur ? $taskbarBlurAmount : 0 }}px) saturate(180%);
                -webkit-backdrop-filter: blur({{ $taskbarBlur ? $taskbarBlurAmount : 0 }}px) saturate(180%);
                @if($millenniumRgbEnabled)
                border-image: linear-gradient(90deg, rgba(168, 85, 247, 0.6), rgba(236, 72, 153, 0.6), rgba(59, 130, 246, 0.6)) 1;
                @else
                border-color: {{ $taskbarBorderColor }};
                @endif
                @if($taskbarUseGradient)
                background: linear-gradient(to right, {{ $taskbarGradientFrom }}, {{ $taskbarGradientTo }});
                @else
                background-color: {{ $taskbarBgColor }};
                @endif
                box-shadow:
                    0 0 1px rgba(255, 255, 255, 0.15) inset,
                    0 {{ $taskbarPosition === 'top' ? '1' : '-1' }}px 3px rgba(255, 255, 255, 0.1) inset,
                    0 {{ $taskbarPosition === 'top' ? '-2' : '2' }}px 8px rgba(0, 0, 0, 0.15),
                    0 {{ $taskbarPosition === 'top' ? '-4' : '4' }}px 16px rgba(0, 0, 0, 0.1);
             "></div>

        <!-- Taskbar Content -->
        <div class="relative h-full w-full px-3 flex items-center gap-3">

            <!-- Left Section: Back Button + Quick Icons + Start Button (if position is 'left') -->
            <div class="flex items-center gap-2 {{ $startButtonPosition === 'center' ? 'flex-1' : '' }}">

                <!-- Start Button (Left Position) -->
                @if($startButtonPosition === 'left')
                    <div class="relative">
                        <button
                            @click="startMenuOpen = !startMenuOpen; hideTooltip()"
                            :class="{'millennium-start-active': startMenuOpen}"
                            class="millennium-start-button group flex items-center gap-3 bg-gradient-to-r from-pink-600 via-purple-600 to-blue-600 hover:from-pink-500 hover:via-purple-500 hover:to-blue-500 transition-all duration-300 transform hover:scale-110 hover:-translate-y-1 active:scale-95 active:translate-y-0 shadow-2xl hover:shadow-pink-500/70 border-2 border-white/20"
                            style="width: {{ $startButtonWidth }}px; height: {{ $startButtonHeight }}px; border-radius: {{ $startButtonRadius }}px; margin-top: -8px; margin-bottom: -8px; {{ !$startButtonShowIcon && !$startButtonShowText ? 'padding: 12px;' : 'padding: 0 20px;' }}; box-shadow: 0 0 2px rgba(255, 255, 255, 0.4) inset, 0 2px 4px rgba(255, 255, 255, 0.2) inset, 0 -2px 4px rgba(0, 0, 0, 0.3) inset, 0 8px 24px rgba(236, 72, 153, 0.6), 0 16px 48px rgba(168, 85, 247, 0.5), 0 24px 72px rgba(59, 130, 246, 0.4);">

                            @if($startButtonShowIcon)
                                @if($startButtonIconType === 'upload' && $startButtonCustomIconPath)
                                    <!-- Custom Uploaded Icon -->
                                    <img src="{{ asset('storage/' . $startButtonCustomIconPath) }}" alt="{{ $appName }}" class="object-contain drop-shadow-2xl" style="width: {{ $startButtonIconSize }}px; height: {{ $startButtonIconSize }}px;">
                                @elseif($startButtonIconType === 'fontawesome')
                                    <!-- FontAwesome Icon -->
                                    <i class="{{ $startButtonFontAwesomeIcon }} text-white drop-shadow-2xl" style="font-size: {{ $startButtonIconSize }}px;"></i>
                                @elseif($startButtonIconType === 'emoji')
                                    <!-- Emoji Icon -->
                                    <span class="drop-shadow-2xl" style="font-size: {{ $startButtonIconSize }}px;">{{ $startButtonEmoji }}</span>
                                @elseif($logo)
                                    <!-- Default: System Logo -->
                                    <img src="{{ asset($logo) }}" alt="{{ $appName }}" class="object-contain drop-shadow-2xl" style="width: {{ $startButtonIconSize }}px; height: {{ $startButtonIconSize }}px;">
                                @else
                                    <!-- Fallback: Windows Logo SVG -->
                                    <div class="bg-white/20 rounded-xl flex items-center justify-center" style="width: {{ $startButtonIconSize }}px; height: {{ $startButtonIconSize }}px;">
                                        <svg class="text-white" fill="currentColor" viewBox="0 0 24 24" style="width: {{ $startButtonIconSize * 0.7 }}px; height: {{ $startButtonIconSize * 0.7 }}px;">
                                            <path d="M0 0h11v11H0V0zm13 0h11v11H13V0zM0 13h11v11H0V13zm13 0h11v11H13V13z"/>
                                        </svg>
                                    </div>
                                @endif
                            @endif

                            @if($startButtonShowText)
                                <span class="text-white font-bold drop-shadow-2xl" style="font-size: {{ $startButtonFontSize }}px;">
                                    {{ $startButtonText }}
                                </span>
                            @endif

                            <!-- Glow Effect on Hover -->
                            <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"
                                 style="border-radius: {{ $startButtonRadius }}px; background: linear-gradient(45deg, rgba(236, 72, 153, 0.6), rgba(168, 85, 247, 0.6), rgba(59, 130, 246, 0.6)); filter: blur(20px);"></div>
                            <!-- Inner Highlight -->
                            <div class="absolute inset-x-0 top-0 h-1/2 opacity-30 pointer-events-none"
                                 style="border-radius: {{ $startButtonRadius }}px {{ $startButtonRadius }}px 0 0; background: linear-gradient(to bottom, rgba(255, 255, 255, 0.4), transparent);"></div>
                        </button>

                        <!-- Tooltip -->
                        @if($tooltipEnabled)
                            <div
                                x-show="showTooltip"
                                x-transition:enter="transition ease-out duration-500"
                                x-transition:enter-start="opacity-0 scale-0"
                                x-transition:enter-end="opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-300"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-0"
                                @click="hideTooltip()"
                                class="millennium-tooltip millennium-tooltip-{{ $tooltipPosition }} millennium-tooltip-{{ $tooltipAnimation }} absolute z-[100] cursor-pointer"
                                style="display: none;">
                                <div class="bg-gradient-to-br from-purple-600 via-pink-600 to-blue-600 text-white px-5 py-3 rounded-2xl shadow-2xl border-2 border-white/30 backdrop-blur-sm max-w-[250px]">
                                    <p class="text-sm font-bold text-center leading-relaxed">{{ $tooltipText }}</p>
                                    <button class="absolute -top-2 -right-2 w-6 h-6 bg-white/20 hover:bg-white/30 rounded-full flex items-center justify-center text-white text-xs font-bold transition-colors">✕</button>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Back Button -->
                @if($backButtonEnabled)
                    <button
                        onclick="window.history.back()"
                        class="group flex items-center gap-2 px-4 py-2 rounded-xl bg-gradient-to-r from-gray-700/80 to-gray-800/80 hover:from-indigo-600 hover:to-purple-600 text-white transition-all duration-300 transform hover:scale-105 hover:-translate-y-1 active:scale-95 active:translate-y-0 shadow-lg hover:shadow-indigo-500/50 border-2 border-white/10 hover:border-white/30"
                        style="box-shadow: 0 1px 2px rgba(255, 255, 255, 0.1) inset, 0 -1px 2px rgba(0, 0, 0, 0.2) inset, 0 4px 12px rgba(79, 70, 229, 0.3), 0 8px 24px rgba(139, 92, 246, 0.2);"
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

            </div>

            <!-- Center Section: Start Button (if position is 'center') -->
            @if($startButtonPosition === 'center')
                <div class="flex items-center justify-center relative">
                    <button
                        @click="startMenuOpen = !startMenuOpen; hideTooltip()"
                        :class="{'millennium-start-active': startMenuOpen}"
                        class="millennium-start-button group flex items-center gap-3 bg-gradient-to-r from-pink-600 via-purple-600 to-blue-600 hover:from-pink-500 hover:via-purple-500 hover:to-blue-500 transition-all duration-300 transform hover:scale-110 hover:-translate-y-1 active:scale-95 active:translate-y-0 shadow-2xl hover:shadow-pink-500/70 border-2 border-white/20"
                        style="width: {{ $startButtonWidth }}px; height: {{ $startButtonHeight }}px; border-radius: {{ $startButtonRadius }}px; margin-top: -8px; margin-bottom: -8px; {{ !$startButtonShowIcon && !$startButtonShowText ? 'padding: 12px;' : 'padding: 0 20px;' }}; box-shadow: 0 0 2px rgba(255, 255, 255, 0.4) inset, 0 2px 4px rgba(255, 255, 255, 0.2) inset, 0 -2px 4px rgba(0, 0, 0, 0.3) inset, 0 8px 24px rgba(236, 72, 153, 0.6), 0 16px 48px rgba(168, 85, 247, 0.5), 0 24px 72px rgba(59, 130, 246, 0.4);">

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
                            <span class="text-white font-bold drop-shadow-2xl" style="font-size: {{ $startButtonFontSize }}px;">
                                {{ $startButtonText }}
                            </span>
                        @endif

                        <!-- Glow Effect on Hover -->
                        <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"
                             style="border-radius: {{ $startButtonRadius }}px; background: linear-gradient(45deg, rgba(236, 72, 153, 0.6), rgba(168, 85, 247, 0.6), rgba(59, 130, 246, 0.6)); filter: blur(20px);"></div>
                        <!-- Inner Highlight -->
                        <div class="absolute inset-x-0 top-0 h-1/2 opacity-30 pointer-events-none"
                             style="border-radius: {{ $startButtonRadius }}px {{ $startButtonRadius }}px 0 0; background: linear-gradient(to bottom, rgba(255, 255, 255, 0.4), transparent);"></div>
                    </button>

                    <!-- Tooltip -->
                    @if($tooltipEnabled)
                        <div
                            x-show="showTooltip"
                            x-transition:enter="transition ease-out duration-500"
                            x-transition:enter-start="opacity-0 scale-0"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-300"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-0"
                            @click="hideTooltip()"
                            class="millennium-tooltip millennium-tooltip-{{ $tooltipPosition }} millennium-tooltip-{{ $tooltipAnimation }} absolute z-[100] cursor-pointer"
                            style="display: none;">
                            <div class="bg-gradient-to-br from-purple-600 via-pink-600 to-blue-600 text-white px-5 py-3 rounded-2xl shadow-2xl border-2 border-white/30 backdrop-blur-sm max-w-[250px]">
                                <p class="text-sm font-bold text-center leading-relaxed">{{ $tooltipText }}</p>
                                <button class="absolute -top-2 -right-2 w-6 h-6 bg-white/20 hover:bg-white/30 rounded-full flex items-center justify-center text-white text-xs font-bold transition-colors">✕</button>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Right Section: System Tray (Always on the right) -->
            <div class="flex items-center gap-3 {{ $startButtonPosition === 'center' ? 'flex-1' : '' }} justify-end ml-auto">

                <!-- Start Button (Right Position - Before separator) -->
                @if($startButtonPosition === 'right')
                    <div class="relative">
                        <button
                            @click="startMenuOpen = !startMenuOpen; hideTooltip()"
                            :class="{'millennium-start-active': startMenuOpen}"
                            class="millennium-start-button group flex items-center gap-3 bg-gradient-to-r from-pink-600 via-purple-600 to-blue-600 hover:from-pink-500 hover:via-purple-500 hover:to-blue-500 transition-all duration-300 transform hover:scale-110 hover:-translate-y-1 active:scale-95 active:translate-y-0 shadow-2xl hover:shadow-pink-500/70 border-2 border-white/20"
                            style="width: {{ $startButtonWidth }}px; height: {{ $startButtonHeight }}px; border-radius: {{ $startButtonRadius }}px; margin-top: -8px; margin-bottom: -8px; {{ !$startButtonShowIcon && !$startButtonShowText ? 'padding: 12px;' : 'padding: 0 20px;' }}; box-shadow: 0 0 2px rgba(255, 255, 255, 0.4) inset, 0 2px 4px rgba(255, 255, 255, 0.2) inset, 0 -2px 4px rgba(0, 0, 0, 0.3) inset, 0 8px 24px rgba(236, 72, 153, 0.6), 0 16px 48px rgba(168, 85, 247, 0.5), 0 24px 72px rgba(59, 130, 246, 0.4);">

                            @if($startButtonShowIcon)
                                @if($startButtonIconType === 'upload' && $startButtonCustomIconPath)
                                    <!-- Custom Uploaded Icon -->
                                    <img src="{{ asset('storage/' . $startButtonCustomIconPath) }}" alt="{{ $appName }}" class="object-contain drop-shadow-2xl" style="width: {{ $startButtonIconSize }}px; height: {{ $startButtonIconSize }}px;">
                                @elseif($startButtonIconType === 'fontawesome')
                                    <!-- FontAwesome Icon -->
                                    <i class="{{ $startButtonFontAwesomeIcon }} text-white drop-shadow-2xl" style="font-size: {{ $startButtonIconSize }}px;"></i>
                                @elseif($startButtonIconType === 'emoji')
                                    <!-- Emoji Icon -->
                                    <span class="drop-shadow-2xl" style="font-size: {{ $startButtonIconSize }}px;">{{ $startButtonEmoji }}</span>
                                @elseif($logo)
                                    <!-- Default: System Logo -->
                                    <img src="{{ asset($logo) }}" alt="{{ $appName }}" class="object-contain drop-shadow-2xl" style="width: {{ $startButtonIconSize }}px; height: {{ $startButtonIconSize }}px;">
                                @else
                                    <!-- Fallback: Windows Logo SVG -->
                                    <div class="bg-white/20 rounded-xl flex items-center justify-center" style="width: {{ $startButtonIconSize }}px; height: {{ $startButtonIconSize }}px;">
                                        <svg class="text-white" fill="currentColor" viewBox="0 0 24 24" style="width: {{ $startButtonIconSize * 0.7 }}px; height: {{ $startButtonIconSize * 0.7 }}px;">
                                            <path d="M0 0h11v11H0V0zm13 0h11v11H13V0zM0 13h11v11H0V13zm13 0h11v11H13V13z"/>
                                        </svg>
                                    </div>
                                @endif
                            @endif

                            @if($startButtonShowText)
                                <span class="text-white font-bold drop-shadow-2xl" style="font-size: {{ $startButtonFontSize }}px;">
                                    {{ $startButtonText }}
                                </span>
                            @endif

                            <!-- Glow Effect on Hover -->
                            <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"
                                 style="border-radius: {{ $startButtonRadius }}px; background: linear-gradient(45deg, rgba(236, 72, 153, 0.6), rgba(168, 85, 247, 0.6), rgba(59, 130, 246, 0.6)); filter: blur(20px);"></div>
                            <!-- Inner Highlight -->
                            <div class="absolute inset-x-0 top-0 h-1/2 opacity-30 pointer-events-none"
                                 style="border-radius: {{ $startButtonRadius }}px {{ $startButtonRadius }}px 0 0; background: linear-gradient(to bottom, rgba(255, 255, 255, 0.4), transparent);"></div>
                        </button>

                        <!-- Tooltip -->
                        @if($tooltipEnabled)
                            <div
                                x-show="showTooltip"
                                x-transition:enter="transition ease-out duration-500"
                                x-transition:enter-start="opacity-0 scale-0"
                                x-transition:enter-end="opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-300"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-0"
                                @click="hideTooltip()"
                                class="millennium-tooltip millennium-tooltip-{{ $tooltipPosition }} millennium-tooltip-{{ $tooltipAnimation }} absolute z-[100] cursor-pointer"
                                style="display: none;">
                                <div class="bg-gradient-to-br from-purple-600 via-pink-600 to-blue-600 text-white px-5 py-3 rounded-2xl shadow-2xl border-2 border-white/30 backdrop-blur-sm max-w-[250px]">
                                    <p class="text-sm font-bold text-center leading-relaxed">{{ $tooltipText }}</p>
                                    <button class="absolute -top-2 -right-2 w-6 h-6 bg-white/20 hover:bg-white/30 rounded-full flex items-center justify-center text-white text-xs font-bold transition-colors">✕</button>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                <!-- System Tray Separator (Windows-style) -->
                <div class="h-12 w-0.5 bg-gradient-to-b from-transparent via-white/40 to-transparent mx-2 shadow-lg" style="box-shadow: 0 0 8px rgba(255, 255, 255, 0.3);"></div>

                <!-- Back to Top Button -->
                @if($backToTopEnabled)
                    <button
                        x-show="showBackToTop"
                        @click="scrollToTop()"
                        x-transition:enter="millennium-back-to-top-{{ $backToTopAnimation }}-enter"
                        x-transition:leave="millennium-back-to-top-{{ $backToTopAnimation }}-leave"
                        class="flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-purple-600 to-pink-600 hover:from-purple-500 hover:to-pink-500 transition-all duration-300 transform hover:scale-110 hover:-translate-y-1 active:scale-95 active:translate-y-0 shadow-lg hover:shadow-purple-500/50 border-2 border-white/20 hover:border-white/40"
                        style="box-shadow: 0 1px 2px rgba(255, 255, 255, 0.2) inset, 0 -1px 2px rgba(0, 0, 0, 0.3) inset, 0 4px 12px rgba(147, 51, 234, 0.5), 0 8px 24px rgba(236, 72, 153, 0.4);"
                        title="กลับขึ้นด้านบน">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                        </svg>
                    </button>
                @endif

                <!-- Dark Mode Toggle -->
                <button
                    @click="toggleDarkMode()"
                    class="flex items-center justify-center w-12 h-12 rounded-xl bg-white/10 hover:bg-white/20 transition-all duration-300 transform hover:scale-110 hover:-translate-y-1 active:scale-95 active:translate-y-0 border-2 border-white/10 hover:border-white/30"
                    :class="isDark ? 'text-yellow-400' : 'text-gray-300'"
                    style="box-shadow: 0 1px 2px rgba(255, 255, 255, 0.1) inset, 0 -1px 2px rgba(0, 0, 0, 0.2) inset, 0 4px 12px rgba(255, 255, 255, 0.1), 0 8px 24px rgba(0, 0, 0, 0.2);"
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
                @elseif($clockStyle === 'minimal')
                    <div class="hidden lg:flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white/5">
                        <span class="text-white font-semibold text-sm" x-text="currentTime"></span>
                    </div>
                @elseif($clockStyle === 'full')
                    <div class="hidden lg:flex flex-col items-end gap-0.5 px-5 py-2.5 rounded-xl bg-gradient-to-br from-white/15 to-white/5 backdrop-blur-md border border-white/20 shadow-lg">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-yellow-300 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="text-white font-bold text-lg tracking-wider" x-text="currentTime"></span>
                        </div>
                        @if($clockShowDate)
                            <div class="flex items-center gap-1.5 mt-0.5">
                                <svg class="w-4 h-4 text-cyan-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span class="text-cyan-100 text-xs font-medium" x-text="currentDate"></span>
                            </div>
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
        height: 2px;
        {{ $taskbarPosition === 'bottom' ? 'top: 0;' : 'bottom: 0;' }}
        left: 0;
        right: 0;
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

    /* Taskbar Glass Morphism - Windows 11 Acrylic */
    .millennium-taskbar {
        backdrop-filter: blur(30px) saturate(180%);
        -webkit-backdrop-filter: blur(30px) saturate(180%);
    }

    /* Taskbar Color Customization */
    .millennium-taskbar .text-white {
        color: var(--taskbar-text-color, #ffffff);
    }

    .millennium-taskbar a:hover,
    .millennium-taskbar button:hover {
        background-color: var(--taskbar-hover-bg, rgba(255, 255, 255, 0.1));
    }

    .millennium-taskbar a.active,
    .millennium-taskbar button.active {
        background-color: var(--taskbar-active-bg, rgba(255, 255, 255, 0.2));
    }

    /* Back to Top Button Animations */
    /* Fade Animation */
    .millennium-back-to-top-fade-enter {
        transition: opacity 300ms ease-out;
        opacity: 0;
    }
    .millennium-back-to-top-fade-enter.millennium-back-to-top-fade-enter-active {
        opacity: 1;
    }
    .millennium-back-to-top-fade-leave {
        transition: opacity 300ms ease-in;
        opacity: 1;
    }
    .millennium-back-to-top-fade-leave.millennium-back-to-top-fade-leave-active {
        opacity: 0;
    }

    /* Slide Animation */
    .millennium-back-to-top-slide-enter {
        transition: all 300ms ease-out;
        opacity: 0;
        transform: translateY(20px);
    }
    .millennium-back-to-top-slide-enter.millennium-back-to-top-slide-enter-active {
        opacity: 1;
        transform: translateY(0);
    }
    .millennium-back-to-top-slide-leave {
        transition: all 300ms ease-in;
        opacity: 1;
        transform: translateY(0);
    }
    .millennium-back-to-top-slide-leave.millennium-back-to-top-slide-leave-active {
        opacity: 0;
        transform: translateY(20px);
    }

    /* Bounce Animation */
    .millennium-back-to-top-bounce-enter {
        animation: bounceIn 600ms ease-out;
    }
    .millennium-back-to-top-bounce-leave {
        animation: bounceOut 300ms ease-in;
    }
    @keyframes bounceIn {
        0% { opacity: 0; transform: scale(0.3) translateY(20px); }
        50% { opacity: 1; transform: scale(1.05); }
        70% { transform: scale(0.9); }
        100% { transform: scale(1); }
    }
    @keyframes bounceOut {
        0% { transform: scale(1); opacity: 1; }
        100% { transform: scale(0.3) translateY(20px); opacity: 0; }
    }

    /* Scale Animation */
    .millennium-back-to-top-scale-enter {
        transition: all 300ms ease-out;
        opacity: 0;
        transform: scale(0);
    }
    .millennium-back-to-top-scale-enter.millennium-back-to-top-scale-enter-active {
        opacity: 1;
        transform: scale(1);
    }
    .millennium-back-to-top-scale-leave {
        transition: all 300ms ease-in;
        opacity: 1;
        transform: scale(1);
    }
    .millennium-back-to-top-scale-leave.millennium-back-to-top-scale-leave-active {
        opacity: 0;
        transform: scale(0);
    }

    /* Zoom Animation */
    .millennium-back-to-top-zoom-enter {
        transition: all 400ms cubic-bezier(0.68, -0.55, 0.265, 1.55);
        opacity: 0;
        transform: scale(0.5);
    }
    .millennium-back-to-top-zoom-enter.millennium-back-to-top-zoom-enter-active {
        opacity: 1;
        transform: scale(1);
    }
    .millennium-back-to-top-zoom-leave {
        transition: all 200ms ease-in;
        opacity: 1;
        transform: scale(1);
    }
    .millennium-back-to-top-zoom-leave.millennium-back-to-top-zoom-leave-active {
        opacity: 0;
        transform: scale(0.5);
    }

    /* ========================================
       Tooltip Positioning
       ======================================== */

    .millennium-tooltip-top {
        bottom: calc(100% + 15px);
        left: 50%;
        transform: translateX(-50%);
    }

    .millennium-tooltip-top::after {
        content: '';
        position: absolute;
        top: 100%;
        left: 50%;
        transform: translateX(-50%);
        border: 8px solid transparent;
        border-top-color: rgba(168, 85, 247, 0.9);
        filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.3));
    }

    .millennium-tooltip-bottom {
        top: calc(100% + 15px);
        left: 50%;
        transform: translateX(-50%);
    }

    .millennium-tooltip-bottom::after {
        content: '';
        position: absolute;
        bottom: 100%;
        left: 50%;
        transform: translateX(-50%);
        border: 8px solid transparent;
        border-bottom-color: rgba(168, 85, 247, 0.9);
        filter: drop-shadow(0 -4px 6px rgba(0, 0, 0, 0.3));
    }

    .millennium-tooltip-left {
        right: calc(100% + 15px);
        top: 50%;
        transform: translateY(-50%);
    }

    .millennium-tooltip-left::after {
        content: '';
        position: absolute;
        left: 100%;
        top: 50%;
        transform: translateY(-50%);
        border: 8px solid transparent;
        border-left-color: rgba(168, 85, 247, 0.9);
        filter: drop-shadow(4px 0 6px rgba(0, 0, 0, 0.3));
    }

    .millennium-tooltip-right {
        left: calc(100% + 15px);
        top: 50%;
        transform: translateY(-50%);
    }

    .millennium-tooltip-right::after {
        content: '';
        position: absolute;
        right: 100%;
        top: 50%;
        transform: translateY(-50%);
        border: 8px solid transparent;
        border-right-color: rgba(168, 85, 247, 0.9);
        filter: drop-shadow(-4px 0 6px rgba(0, 0, 0, 0.3));
    }

    /* ========================================
       Tooltip Animations
       ======================================== */

    /* Bounce Animation */
    @keyframes tooltipBounce {
        0%, 20%, 50%, 80%, 100% {
            transform: translateY(0);
        }
        40% {
            transform: translateY(-10px);
        }
        60% {
            transform: translateY(-5px);
        }
    }

    .millennium-tooltip-bounce {
        animation: tooltipBounce 2s ease-in-out infinite;
    }

    /* Pulse Animation */
    @keyframes tooltipPulse {
        0% {
            transform: scale(1);
            box-shadow: 0 0 20px rgba(168, 85, 247, 0.6);
        }
        50% {
            transform: scale(1.05);
            box-shadow: 0 0 30px rgba(236, 72, 153, 0.8);
        }
        100% {
            transform: scale(1);
            box-shadow: 0 0 20px rgba(168, 85, 247, 0.6);
        }
    }

    .millennium-tooltip-pulse {
        animation: tooltipPulse 2s ease-in-out infinite;
    }

    /* Shake Animation */
    @keyframes tooltipShake {
        0%, 100% {
            transform: translateX(0);
        }
        10%, 30%, 50%, 70%, 90% {
            transform: translateX(-5px);
        }
        20%, 40%, 60%, 80% {
            transform: translateX(5px);
        }
    }

    .millennium-tooltip-shake {
        animation: tooltipShake 3s ease-in-out infinite;
    }

    /* Swing Animation */
    @keyframes tooltipSwing {
        0%, 100% {
            transform: rotate(0deg);
            transform-origin: top center;
        }
        20% {
            transform: rotate(15deg);
        }
        40% {
            transform: rotate(-10deg);
        }
        60% {
            transform: rotate(5deg);
        }
        80% {
            transform: rotate(-5deg);
        }
    }

    .millennium-tooltip-swing {
        animation: tooltipSwing 2s ease-in-out infinite;
    }

    /* Tada Animation */
    @keyframes tooltipTada {
        0% {
            transform: scale(1) rotate(0deg);
        }
        10%, 20% {
            transform: scale(0.9) rotate(-3deg);
        }
        30%, 50%, 70%, 90% {
            transform: scale(1.1) rotate(3deg);
        }
        40%, 60%, 80% {
            transform: scale(1.1) rotate(-3deg);
        }
        100% {
            transform: scale(1) rotate(0deg);
        }
    }

    .millennium-tooltip-tada {
        animation: tooltipTada 2s ease-in-out infinite;
    }

    /* Flash Animation */
    @keyframes tooltipFlash {
        0%, 50%, 100% {
            opacity: 1;
        }
        25%, 75% {
            opacity: 0.5;
        }
    }

    .millennium-tooltip-flash {
        animation: tooltipFlash 2s ease-in-out infinite;
    }

    /* Tooltip Glow Effect */
    .millennium-tooltip > div {
        position: relative;
        box-shadow:
            0 0 20px rgba(168, 85, 247, 0.6),
            0 0 40px rgba(236, 72, 153, 0.4),
            0 10px 30px rgba(0, 0, 0, 0.5);
    }

    .millennium-tooltip > div:hover {
        box-shadow:
            0 0 30px rgba(168, 85, 247, 0.8),
            0 0 50px rgba(236, 72, 153, 0.6),
            0 15px 40px rgba(0, 0, 0, 0.6);
    }
</style>
