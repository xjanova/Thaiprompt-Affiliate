@php
    $user = Auth::user();
    $currentRoute = request()->route()->getName();

    // Determine current dashboard
    $isAdmin = str_starts_with($currentRoute, 'admin.');
    $isSeller = str_starts_with($currentRoute, 'seller.');
    $isUser = str_starts_with($currentRoute, 'user.');

    // Check user roles
    $hasAdminAccess = in_array($user->role, ['admin', 'super_admin']);
    $hasSellerAccess = $user->role === 'seller';
    $hasUserAccess = $user->role === 'user' || $hasAdminAccess || $hasSellerAccess;

    // Count available dashboards
    $availableDashboards = 0;
    if ($hasAdminAccess) $availableDashboards++;
    if ($hasSellerAccess) $availableDashboards++;
    if ($hasUserAccess) $availableDashboards++;
@endphp

@if($availableDashboards > 1)
<!-- Dashboard Switcher Dropdown -->
<div x-data="{ open: false }" class="relative">
    <!-- Trigger Button -->
    <button
        @click="open = !open"
        @click.away="open = false"
        class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-white/10 transition-colors duration-200"
        title="สลับแดชบอร์ด">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
        </svg>
        <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    <!-- Dropdown Menu -->
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 transform scale-95"
        x-transition:enter-end="opacity-100 transform scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 transform scale-100"
        x-transition:leave-end="opacity-0 transform scale-95"
        class="absolute right-0 mt-2 w-64 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden z-50"
        style="display: none;">

        <!-- Header -->
        <div class="px-4 py-3 bg-gradient-to-r from-indigo-500 to-purple-600 text-white">
            <p class="text-sm font-semibold">สลับแดชบอร์ด</p>
            <p class="text-xs opacity-90">เลือกหน้าแดชบอร์ดที่ต้องการ</p>
        </div>

        <!-- Menu Items -->
        <div class="py-2">
            @if($hasAdminAccess)
                <a href="{{ route('admin.dashboard') }}"
                   class="flex items-center gap-3 px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors {{ $isAdmin ? 'bg-indigo-50 dark:bg-indigo-900/20 border-l-4 border-indigo-600' : '' }}">
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-red-500 to-pink-500 flex items-center justify-center text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold text-gray-900 dark:text-gray-100">แอดมิน</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">จัดการระบบทั้งหมด</p>
                    </div>
                    @if($isAdmin)
                        <svg class="w-5 h-5 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    @endif
                </a>
            @endif

            @if($hasSellerAccess)
                <a href="{{ route('seller.dashboard') }}"
                   class="flex items-center gap-3 px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors {{ $isSeller ? 'bg-indigo-50 dark:bg-indigo-900/20 border-l-4 border-indigo-600' : '' }}">
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-green-500 to-emerald-500 flex items-center justify-center text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold text-gray-900 dark:text-gray-100">ร้านค้า</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">จัดการสินค้าและคำสั่งซื้อ</p>
                    </div>
                    @if($isSeller)
                        <svg class="w-5 h-5 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    @endif
                </a>
            @endif

            @if($hasUserAccess)
                <a href="{{ route('user.dashboard') }}"
                   class="flex items-center gap-3 px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors {{ $isUser ? 'bg-indigo-50 dark:bg-indigo-900/20 border-l-4 border-indigo-600' : '' }}">
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-500 to-indigo-500 flex items-center justify-center text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold text-gray-900 dark:text-gray-100">ผู้ใช้งาน</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">แดชบอร์ดส่วนตัว</p>
                    </div>
                    @if($isUser)
                        <svg class="w-5 h-5 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    @endif
                </a>
            @endif
        </div>

        <!-- Footer -->
        <div class="px-4 py-2 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
            <p class="text-xs text-gray-600 dark:text-gray-400 text-center">
                บทบาท: <span class="font-semibold text-indigo-600">{{ ucfirst($user->role) }}</span>
            </p>
        </div>
    </div>
</div>
@endif
