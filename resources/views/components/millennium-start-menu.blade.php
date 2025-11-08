@props(['type' => 'admin'])

@php
    // Get user and role info
    $user = auth()->user();
    $logo = \App\Models\Setting::get('logo');
    $appName = \App\Models\Setting::get('app_name', 'TP-Affiliate');

    // Define menu items based on type
    if ($type === 'admin') {
        $menuItems = [
            ['icon' => '📊', 'label' => 'แดชบอร์ด', 'url' => route('admin.dashboard')],
            ['icon' => '👥', 'label' => 'ผู้ใช้งาน', 'url' => route('admin.users.index')],
            ['icon' => '🏨', 'label' => 'โรงแรม', 'url' => route('admin.hotels.index')],
            ['icon' => '🛒', 'label' => 'อีคอมเมิร์ซ', 'url' => route('admin.ecommerce.products.index')],
            ['icon' => '🏪', 'label' => 'ระบบ POS', 'url' => route('admin.pos.dashboard')],
            ['icon' => '💰', 'label' => 'กระเป๋าเงิน', 'url' => route('admin.wallet.index')],
            ['icon' => '📧', 'label' => 'อีเมล', 'url' => route('admin.email.templates.index')],
            ['icon' => '📱', 'label' => 'LINE OA', 'url' => route('admin.line-oa.index')],
            ['icon' => '🎓', 'label' => 'Academy', 'url' => route('admin.academy.courses.index')],
            ['icon' => '⚙️', 'label' => 'ตั้งค่าระบบ', 'url' => route('admin.settings.index')],
        ];
    } else {
        // Seller menu
        $menuItems = [
            ['icon' => '📊', 'label' => 'แดชบอร์ด', 'url' => route('seller.dashboard')],
            ['icon' => '📦', 'label' => 'สินค้า', 'url' => route('seller.products.index')],
            ['icon' => '🏪', 'label' => 'ระบบ POS', 'url' => route('seller.pos.terminal')],
            ['icon' => '🛒', 'label' => 'ยอดขาย', 'url' => route('seller.orders.index')],
            ['icon' => '📈', 'label' => 'วิเคราะห์', 'url' => route('seller.analytics')],
            ['icon' => '👤', 'label' => 'โปรไฟล์', 'url' => route('seller.profile')],
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
            <div class="p-6 bg-gradient-to-r from-pink-600 via-purple-600 to-blue-600 millennium-header-glow">
                <div class="flex items-center gap-4">
                    @if($logo)
                        <div class="w-16 h-16 rounded-xl overflow-hidden ring-4 ring-white/30 millennium-logo-pulse">
                            <img src="{{ asset($logo) }}" alt="{{ $appName }}" class="w-full h-full object-contain">
                        </div>
                    @else
                        <div class="w-16 h-16 bg-gradient-to-br from-cyan-400 to-blue-600 rounded-xl flex items-center justify-center ring-4 ring-white/30 millennium-logo-pulse">
                            <svg class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M0 0h11v11H0V0zm13 0h11v11H13V0zM0 13h11v11H0V13zm13 0h11v11H13V13z"/>
                            </svg>
                        </div>
                    @endif

                    <div class="flex-1">
                        <h2 class="text-2xl font-bold text-white drop-shadow-lg">{{ $appName }}</h2>
                        @if($user)
                            <p class="text-base text-blue-100 mt-1">{{ $user->name }}</p>
                            <span class="inline-block mt-1 px-3 py-0.5 bg-white/20 backdrop-blur-sm rounded-full text-xs font-semibold text-white">
                                {{ $type === 'admin' ? '👑 Admin' : '🏪 Seller' }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Menu Items Section -->
            <div class="flex-1 p-4 overflow-y-auto millennium-scrollbar">
                <div class="space-y-2">
                    @foreach($menuItems as $item)
                        <a
                            href="{{ $item['url'] }}"
                            class="group flex items-center gap-4 px-5 py-4 rounded-xl bg-gradient-to-r from-white/5 to-white/10 hover:from-pink-500/30 hover:to-purple-500/30 border border-white/10 hover:border-pink-400/50 transition-all duration-300 transform hover:scale-105 hover:shadow-lg hover:shadow-pink-500/20 millennium-menu-item">

                            <!-- Icon -->
                            <span class="text-4xl group-hover:scale-110 transition-transform duration-300 drop-shadow-lg">
                                {{ $item['icon'] }}
                            </span>

                            <!-- Label -->
                            <span class="text-lg font-semibold text-white group-hover:text-pink-200 transition-colors duration-300">
                                {{ $item['label'] }}
                            </span>

                            <!-- Arrow -->
                            <svg class="w-6 h-6 ml-auto text-white/40 group-hover:text-pink-300 group-hover:translate-x-1 transition-all duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </a>
                    @endforeach
                </div>
            </div>

            <!-- Settings & Tools Section -->
            <div class="p-4 space-y-2 border-t border-white/10 bg-black/20">

                <!-- Dark Mode Toggle -->
                <button
                    @click="toggleDarkMode()"
                    class="w-full group flex items-center gap-4 px-5 py-3 rounded-xl bg-gradient-to-r from-yellow-500/20 to-blue-500/20 hover:from-yellow-500/30 hover:to-blue-500/30 border border-white/10 hover:border-yellow-400/50 transition-all duration-300 transform hover:scale-105">

                    <!-- Sun/Moon Icon -->
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-yellow-400 to-orange-500 dark:from-blue-600 dark:to-purple-600 flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                        <svg x-show="!isDark" class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        <svg x-show="isDark" class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                        </svg>
                    </div>

                    <span class="text-lg font-semibold text-white flex-1 text-left">
                        <span x-show="!isDark">โหมดมืด</span>
                        <span x-show="isDark">โหมดสว่าง</span>
                    </span>
                </button>

                <!-- Language Switcher -->
                <button
                    onclick="window.location.href='/lang/' + (document.documentElement.lang === 'th' ? 'en' : 'th')"
                    class="w-full group flex items-center gap-4 px-5 py-3 rounded-xl bg-gradient-to-r from-green-500/20 to-blue-500/20 hover:from-green-500/30 hover:to-blue-500/30 border border-white/10 hover:border-green-400/50 transition-all duration-300 transform hover:scale-105">

                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-green-400 to-blue-500 flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                        </svg>
                    </div>

                    <span class="text-lg font-semibold text-white flex-1 text-left">ภาษา / Language</span>
                </button>
            </div>

            <!-- Footer Section -->
            <div class="p-4 bg-gradient-to-r from-gray-900 via-purple-900/50 to-blue-900/50 border-t border-white/10">
                @if($user)
                    <div class="flex items-center gap-3">
                        <!-- User Avatar -->
                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-pink-500 via-purple-500 to-blue-500 flex items-center justify-center text-white font-bold text-lg ring-2 ring-white/30">
                            {{ substr($user->name, 0, 2) }}
                        </div>

                        <!-- User Info & Logout -->
                        <div class="flex-1 min-w-0">
                            <p class="text-base font-semibold text-white truncate">{{ $user->name }}</p>
                            <p class="text-sm text-gray-300 truncate">{{ $user->email }}</p>
                        </div>

                        <!-- Logout Button -->
                        <button
                            onclick="document.getElementById('millennium-logout-form').submit()"
                            class="w-12 h-12 rounded-xl bg-gradient-to-br from-red-500 to-pink-600 hover:from-red-600 hover:to-pink-700 flex items-center justify-center text-white transition-all duration-300 transform hover:scale-110 hover:rotate-12 shadow-lg hover:shadow-red-500/50"
                            title="ออกจากระบบ">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                            </svg>
                        </button>

                        <form id="millennium-logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                            @csrf
                        </form>
                    </div>
                @else
                    <!-- Login/Register Buttons -->
                    <div class="flex gap-3">
                        <a href="{{ route('login') }}" class="flex-1 px-4 py-3 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white text-base font-bold rounded-xl text-center transition-all duration-300 transform hover:scale-105 shadow-lg">
                            เข้าสู่ระบบ
                        </a>
                        <a href="{{ route('register') }}" class="flex-1 px-4 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white text-base font-bold rounded-xl text-center transition-all duration-300 transform hover:scale-105 shadow-lg">
                            สมัครสมาชิก
                        </a>
                    </div>
                @endif
            </div>

        </div>
    </div>
</div>

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
        0%, 100% { filter: drop-shadow(0 0 10px rgba(255, 0, 128, 0.5)); }
        33% { filter: drop-shadow(0 0 10px rgba(0, 240, 255, 0.5)); }
        66% { filter: drop-shadow(0 0 10px rgba(127, 0, 255, 0.5)); }
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
        width: 8px;
    }

    .millennium-scrollbar::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.05);
        border-radius: 4px;
    }

    .millennium-scrollbar::-webkit-scrollbar-thumb {
        background: linear-gradient(180deg, #ec4899, #a855f7, #3b82f6);
        border-radius: 4px;
    }

    .millennium-scrollbar::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(180deg, #f472b6, #c084fc, #60a5fa);
    }
</style>

<script>
    // Dark Mode Toggle Function
    window.toggleDarkMode = function() {
        const html = document.documentElement;
        const isDark = html.classList.contains('dark');

        if (isDark) {
            html.classList.remove('dark');
            localStorage.setItem('darkMode', 'light');
        } else {
            html.classList.add('dark');
            localStorage.setItem('darkMode', 'dark');
        }

        // Update Alpine.js state if available
        if (window.Alpine && window.Alpine.store) {
            window.Alpine.store('darkMode', !isDark);
        }
    };
</script>
