@props(['items' => []])

@php
    // Get app information for footer
    $appName = \App\Models\Setting::get('app_name', 'TP-Affiliate');
    $appVersion = \App\Models\Setting::get('app_version', '2.0');
    $copyrightText = \App\Models\Setting::get('copyright_text', '© 2025 All Rights Reserved');
@endphp

<div class="flex flex-col h-full">
    <!-- Start Menu Header -->
    <div class="p-6 bg-gradient-to-r from-blue-600 to-cyan-600 text-white">
        <div class="flex items-center gap-3">
            @php
                $logo = \App\Models\Setting::get('logo');
                $appName = \App\Models\Setting::get('app_name', 'TP-Affiliate');

                // Logo size settings for start menu
                $logoStartMenuWidth = \App\Models\Setting::get('logo_start_menu_width', 48);
                $logoStartMenuHeight = \App\Models\Setting::get('logo_start_menu_height', 48);
            @endphp

            @if($logo)
                <img src="{{ asset($logo) }}" alt="{{ $appName }}" style="width: {{ $logoStartMenuWidth }}px; height: {{ $logoStartMenuHeight }}px;" class="object-contain">
            @else
                <div style="width: {{ $logoStartMenuWidth }}px; height: {{ $logoStartMenuHeight }}px;" class="bg-white/20 rounded-lg flex items-center justify-center">
                    <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M0 0h11v11H0V0zm13 0h11v11H13V0zM0 13h11v11H0V13zm13 0h11v11H13V13z"/>
                    </svg>
                </div>
            @endif

            <div>
                <h3 class="text-lg font-bold">{{ $appName }}</h3>
                @auth
                    <p class="text-xs text-blue-100">{{ auth()->user()->name }}</p>
                @else
                    <p class="text-xs text-blue-100">ยินดีต้อนรับ</p>
                @endauth
            </div>
        </div>
    </div>

    <!-- Menu Items -->
    <div class="flex-1 p-4 overflow-y-auto space-y-1">
        @if(empty($items))
            <p class="text-center text-gray-400 text-sm py-8">ไม่มีรายการเมนู</p>
        @else
            @foreach($items as $item)
                <a
                    href="{{ $item['url'] }}"
                    class="group flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/10 transition-all duration-200 transform hover:scale-105 active:scale-95">
                    <span class="text-2xl group-hover:scale-110 transition-transform">{{ $item['icon'] }}</span>
                    <span class="text-white font-medium text-sm group-hover:text-blue-300 transition-colors">{{ $item['label'] }}</span>
                    <svg class="w-4 h-4 ml-auto text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </a>
            @endforeach
        @endif
    </div>

    <!-- Start Menu Footer -->
    <div class="p-4 bg-gray-800/50 border-t border-white/10">
        <div class="flex items-center justify-between gap-2">
            @auth
                <!-- User Info -->
                <a href="{{ route('user.dashboard') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-white/10 transition-all flex-1">
                    <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-500 rounded-full flex items-center justify-center text-white font-bold text-xs">
                        {{ substr(auth()->user()->name, 0, 2) }}
                    </div>
                    <span class="text-white text-xs font-medium truncate">{{ auth()->user()->name }}</span>
                </a>

                <!-- Power Options -->
                <div class="flex items-center gap-1">
                    <button
                        onclick="document.getElementById('logout-form').submit()"
                        class="w-10 h-10 flex items-center justify-center rounded-lg hover:bg-red-500/20 hover:text-red-400 text-gray-400 transition-all"
                        title="ออกจากระบบ">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                    </button>

                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                        @csrf
                    </form>
                </div>
            @else
                <!-- Login/Register Buttons -->
                <a href="{{ route('login') }}" class="flex-1 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg text-center transition-colors">
                    เข้าสู่ระบบ
                </a>
                <a href="{{ route('register') }}" class="flex-1 px-3 py-2 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white text-xs font-semibold rounded-lg text-center transition-colors">
                    สมัครสมาชิก
                </a>
            @endauth
        </div>

        <!-- App Info Footer -->
        <div class="mt-3 pt-3 border-t border-white/10 text-center space-y-1">
            <div class="text-white/60 text-xs font-semibold">
                {{ $appName }}
            </div>
            <div class="text-white/40 text-[10px]">
                Version {{ $appVersion }}
            </div>
            <div class="text-white/30 text-[10px]">
                {{ $copyrightText }}
            </div>
        </div>
    </div>
</div>

<style>
    /* Custom scrollbar for start menu */
    .overflow-y-auto::-webkit-scrollbar {
        width: 6px;
    }

    .overflow-y-auto::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.05);
        border-radius: 3px;
    }

    .overflow-y-auto::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 3px;
    }

    .overflow-y-auto::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.3);
    }
</style>
