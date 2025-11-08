@props(['icons' => [], 'position' => 'top'])

<div class="flex items-center gap-2 text-white/80">
    <!-- System Tray Icons -->
    @foreach($icons as $icon)
        @php
            $shouldShow = true;
            if (isset($icon['requires_auth']) && $icon['requires_auth'] && !auth()->check()) {
                $shouldShow = false;
            }
            if (isset($icon['requires_guest']) && $icon['requires_guest'] && auth()->check()) {
                $shouldShow = false;
            }
        @endphp

        @if($shouldShow)
            <a
                href="{{ $icon['url'] }}"
                class="group relative flex items-center justify-center w-10 h-10 rounded-lg hover:bg-white/20 transition-all duration-300 transform hover:scale-110 active:scale-95"
                title="{{ $icon['label'] }}">

                @if($icon['icon'] === 'cart')
                    <div class="relative">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        @auth
                            @php
                                $cartCount = 0; // TODO: Get actual cart count
                            @endphp
                            @if($cartCount > 0)
                                <span class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 text-white text-xs rounded-full flex items-center justify-center font-bold">
                                    {{ $cartCount > 9 ? '9+' : $cartCount }}
                                </span>
                            @endif
                        @endauth
                    </div>

                @elseif($icon['icon'] === 'user')
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>

                @elseif($icon['icon'] === 'login')
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                    </svg>

                @elseif($icon['icon'] === 'notification')
                    <div class="relative">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                        @auth
                            @php
                                $notificationCount = 0; // TODO: Get actual notification count
                            @endphp
                            @if($notificationCount > 0)
                                <span class="absolute -top-1 -right-1 w-4 h-4 bg-blue-500 text-white text-xs rounded-full flex items-center justify-center font-bold animate-pulse">
                                    {{ $notificationCount > 9 ? '9+' : $notificationCount }}
                                </span>
                            @endif
                        @endauth
                    </div>

                @else
                    <span class="text-lg">{{ $icon['icon'] }}</span>
                @endif

                <!-- Tooltip -->
                <div class="absolute {{ $position === 'top' ? 'top-full mt-2' : 'bottom-full mb-2' }} right-0 px-3 py-1.5 bg-gray-800 text-white text-xs rounded shadow-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap z-10">
                    {{ $icon['label'] }}
                </div>
            </a>
        @endif
    @endforeach

    <!-- Divider -->
    <div class="w-px h-6 bg-white/20 mx-1"></div>

    <!-- System Info -->
    <div class="flex items-center gap-3 px-3 text-xs">
        @php
            $licenseText = \App\Models\WindowsUiSetting::get('windows_license_text', 'Licensed');
            $copyrightText = \App\Models\WindowsUiSetting::get('windows_copyright_text', '© 2025 TP-Affiliate');
        @endphp

        <div class="hidden lg:flex flex-col text-right">
            <span class="text-white/90 font-semibold">{{ $licenseText }}</span>
            <span class="text-white/60 text-[10px]">{{ $copyrightText }}</span>
        </div>

        <!-- Theme Toggle -->
        <button
            onclick="toggleDarkMode()"
            class="group relative w-10 h-10 flex items-center justify-center rounded-lg hover:bg-white/20 transition-all duration-300 transform hover:scale-110 active:scale-95"
            title="สลับธีม">
            <svg class="w-5 h-5 dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
            </svg>
            <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
            </svg>

            <!-- Tooltip -->
            <div class="absolute {{ $position === 'top' ? 'top-full mt-2' : 'bottom-full mb-2' }} right-0 px-3 py-1.5 bg-gray-800 text-white text-xs rounded shadow-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap z-10">
                สลับธีม
            </div>
        </button>

        <!-- Current Time -->
        <div class="hidden md:flex flex-col text-right">
            <span class="text-white/90 font-mono font-semibold" id="current-time">00:00</span>
            <span class="text-white/60 text-[10px] font-mono" id="current-date">2025-01-08</span>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Update clock
    function updateClock() {
        const now = new Date();

        // Time
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const timeElement = document.getElementById('current-time');
        if (timeElement) {
            timeElement.textContent = `${hours}:${minutes}`;
        }

        // Date
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const dateElement = document.getElementById('current-date');
        if (dateElement) {
            dateElement.textContent = `${year}-${month}-${day}`;
        }
    }

    // Update every second
    setInterval(updateClock, 1000);
    updateClock();
</script>
@endpush
