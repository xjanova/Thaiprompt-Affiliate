@props(['type' => 'admin'])

@php
    use App\Models\WindowsUiSetting;

    $logo = \App\Models\Setting::get('logo');
    $appName = \App\Models\Setting::get('app_name', 'TP-Affiliate');

    // Taskbar settings
    $taskbarHeight = WindowsUiSetting::get('windows_taskbar_height', 56);
    $taskbarPosition = WindowsUiSetting::get('windows_taskbar_position', 'top');

    // Millennium-specific settings
    $backButtonEnabled = WindowsUiSetting::get('millennium_back_button_enabled', true);
    $backButtonText = WindowsUiSetting::get('millennium_back_button_text', 'กลับ');
    $centerSectionEnabled = WindowsUiSetting::get('millennium_center_section_enabled', true);
    $centerSectionText = WindowsUiSetting::get('millennium_center_section_text', '');
    $millenniumRgbEnabled = WindowsUiSetting::get('millennium_rgb_enabled', true);
    $millenniumRgbSpeed = WindowsUiSetting::get('millennium_rgb_speed', 5);
@endphp

<!-- Millennium Taskbar -->
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
        init() {
            this.updateTime();
            setInterval(() => this.updateTime(), 60000);
        }
    }"
    x-init="init()"
    class="fixed left-0 right-0 z-50 {{ $taskbarPosition === 'top' ? 'top-0' : 'bottom-0' }} millennium-taskbar"
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
                    <img src="{{ asset($logo) }}" alt="{{ $appName }}" class="w-8 h-8 object-contain drop-shadow-lg">
                @else
                    <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M0 0h11v11H0V0zm13 0h11v11H13V0zM0 13h11v11H0V13zm13 0h11v11H13V13z"/>
                        </svg>
                    </div>
                @endif

                <span class="text-white font-bold text-lg hidden md:inline-block drop-shadow-lg">
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

                    <svg class="w-5 h-5 group-hover:-translate-x-1 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>

                    <span class="font-semibold text-base hidden lg:inline-block">{{ $backButtonText }}</span>
                </button>
            @endif

        </div>

        <!-- Center Section: App Name + Current Page Title -->
        @if($centerSectionEnabled)
            <div class="hidden md:flex items-center gap-3 flex-1 justify-center">
                <div class="px-6 py-2 rounded-lg bg-white/5 backdrop-blur-sm border border-white/10">
                    <span class="text-white font-bold text-lg drop-shadow-lg">
                        @if($centerSectionText)
                            {{ $centerSectionText }}
                        @else
                            {{ $appName }} - <span class="text-pink-300">{{ $type === 'admin' ? 'Admin Dashboard' : 'Seller Dashboard' }}</span>
                        @endif
                    </span>
                </div>
            </div>
        @endif

        <!-- Right Section: System Tray -->
        <div class="flex items-center gap-3">

            <!-- Notification Badge (Example) -->
            @php
                $notificationCount = 0;
                try {
                    if (auth()->check()) {
                        $notificationCount = auth()->user()->unreadNotifications()->count();
                    }
                } catch (\Exception $e) {
                    // Gracefully handle if notifications don't exist
                }
            @endphp

            @if($notificationCount > 0)
                <div class="relative">
                    <button class="flex items-center justify-center w-10 h-10 rounded-lg bg-white/10 hover:bg-white/20 transition-all duration-300 transform hover:scale-110">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                    </button>
                    <span class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs font-bold rounded-full flex items-center justify-center animate-pulse">
                        {{ $notificationCount > 9 ? '9+' : $notificationCount }}
                    </span>
                </div>
            @endif

            <!-- Current Time -->
            <div class="hidden lg:flex items-center gap-2 px-4 py-2 rounded-lg bg-white/10 backdrop-blur-sm border border-white/10">
                <svg class="w-5 h-5 text-pink-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-white font-bold text-base" x-text="currentTime"></span>
            </div>

            <!-- User Avatar -->
            @auth
                <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white/10 backdrop-blur-sm border border-white/10 hover:bg-white/20 transition-all duration-300 cursor-pointer">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-pink-500 via-purple-500 to-blue-500 flex items-center justify-center text-white font-bold text-sm">
                        {{ substr(auth()->user()->name, 0, 2) }}
                    </div>
                    <span class="text-white font-semibold text-sm hidden xl:inline-block">
                        {{ auth()->user()->name }}
                    </span>
                </div>
            @endauth
        </div>

    </div>
</div>

<!-- Include Millennium Start Menu -->
<div x-data="{ startMenuOpen: false }">
    <x-millennium-start-menu :type="$type" />
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
</style>
