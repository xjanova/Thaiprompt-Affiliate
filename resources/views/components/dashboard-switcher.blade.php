@php
    $user = Auth::user();
    $currentRoute = request()->route()->getName();

    // Determine current dashboard
    $isAdmin = str_starts_with($currentRoute, 'admin.');
    $isSeller = str_starts_with($currentRoute, 'seller.');
    $isUser = str_starts_with($currentRoute, 'user.');

    // Check user roles
    $hasAdminAccess = in_array($user->role, ['admin', 'super_admin']);
    $hasSellerAccess = in_array($user->role, ['seller', 'super_admin']);
    $hasUserAccess = $user->role === 'user' || $hasAdminAccess || $hasSellerAccess;

    // Count available dashboards
    $availableDashboards = 0;
    if ($hasAdminAccess) $availableDashboards++;
    if ($hasSellerAccess) $availableDashboards++;
    if ($hasUserAccess) $availableDashboards++;

    // Get user initials
    $initials = strtoupper(substr($user->name, 0, 2));
@endphp

<!-- Profile Menu Dropdown -->
<div x-data="{ open: false }" class="relative" @keydown.escape.window="open = false">
    <!-- Profile Button -->
    <button
        @click="open = !open"
        @click.away="open = false"
        class="flex items-center gap-2 px-3 py-2 rounded-xl hover:bg-white/10 transition-all duration-300 group relative overflow-hidden"
        title="เมนูโปรไฟล์">
        <!-- Animated Background on Hover -->
        <div class="absolute inset-0 bg-gradient-to-r from-indigo-500/20 to-purple-500/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>

        <!-- Avatar with enhanced styling -->
        <div class="relative w-10 h-10 rounded-full bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 flex items-center justify-center text-white font-bold text-sm shadow-lg ring-2 ring-white/30 group-hover:ring-white/50 group-hover:scale-105 transition-all duration-300 group-hover:shadow-xl">
            <span class="relative z-10">{{ $initials }}</span>
            <!-- Shine effect -->
            <div class="absolute inset-0 rounded-full bg-gradient-to-tr from-white/0 via-white/20 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
        </div>

        <!-- Name (Hidden on mobile) -->
        <span class="hidden lg:block text-sm font-medium max-w-[120px] truncate relative z-10 group-hover:text-white transition-colors duration-300">{{ $user->name }}</span>

        <!-- Dropdown Icon with smooth rotation -->
        <svg class="w-4 h-4 transition-all duration-300 relative z-10 group-hover:text-white" :class="{ 'rotate-180': open, 'scale-110': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    <!-- Backdrop -->
    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-40"
         style="display: none;"
         @click="open = false"></div>

    <!-- Dropdown Menu -->
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-300 transform"
        x-transition:enter-start="opacity-0 scale-95 -translate-y-3"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200 transform"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 -translate-y-3"
        class="absolute right-0 mt-3 w-80 bg-white/95 dark:bg-gray-800/95 backdrop-blur-xl rounded-2xl shadow-2xl border border-gray-200/50 dark:border-gray-700/50 overflow-hidden z-50 ring-1 ring-black/5"
        style="display: none;">

        <!-- User Info Header with enhanced gradient -->
        <div class="relative px-5 py-5 bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 text-white overflow-hidden">
            <!-- Animated background pattern -->
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-0 -left-4 w-32 h-32 bg-white rounded-full mix-blend-overlay filter blur-xl animate-pulse"></div>
                <div class="absolute bottom-0 -right-4 w-40 h-40 bg-white rounded-full mix-blend-overlay filter blur-xl animate-pulse" style="animation-delay: 1s;"></div>
            </div>

            <div class="relative z-10">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-14 h-14 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center text-white font-bold text-xl ring-2 ring-white/40 shadow-lg">
                        {{ $initials }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-bold text-lg truncate drop-shadow-sm">{{ $user->name }}</p>
                        <p class="text-xs opacity-90 truncate drop-shadow-sm">{{ $user->email }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="px-3 py-1.5 bg-white/25 backdrop-blur-sm rounded-full text-xs font-semibold shadow-sm">
                        {{ ucfirst($user->role) }}
                    </span>
                    @if($isAdmin)
                        <span class="px-3 py-1.5 bg-red-500/90 backdrop-blur-sm rounded-full text-xs font-semibold shadow-sm">แอดมิน</span>
                    @elseif($isSeller)
                        <span class="px-3 py-1.5 bg-green-500/90 backdrop-blur-sm rounded-full text-xs font-semibold shadow-sm">ร้านค้า</span>
                    @else
                        <span class="px-3 py-1.5 bg-blue-500/90 backdrop-blur-sm rounded-full text-xs font-semibold shadow-sm">ผู้ใช้</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Dashboard Switcher Section (Only if multiple dashboards) -->
        @if($availableDashboards > 1)
        <div class="p-3 border-b border-gray-200 dark:border-gray-700">
            <p class="px-2 py-1.5 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">สลับแดชบอร์ด</p>

            @if($hasAdminAccess)
                <a href="{{ route('admin.dashboard') }}"
                   class="flex items-center gap-3 px-3 py-3 mt-1 hover:bg-gradient-to-r hover:from-red-50 hover:to-pink-50 dark:hover:from-red-900/20 dark:hover:to-pink-900/20 rounded-xl transition-all duration-300 group relative overflow-hidden {{ $isAdmin ? 'bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/30 dark:to-purple-900/30 shadow-sm' : '' }}">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-red-500 to-pink-500 flex items-center justify-center text-white group-hover:scale-110 group-hover:rotate-3 transition-all duration-300 shadow-md group-hover:shadow-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="font-bold text-sm text-gray-900 dark:text-gray-100 group-hover:text-red-600 dark:group-hover:text-red-400 transition-colors">แอดมิน</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">จัดการระบบ</p>
                    </div>
                    @if($isAdmin)
                        <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    @endif
                </a>
            @endif

            @if($hasSellerAccess)
                <a href="{{ route('seller.dashboard') }}"
                   class="flex items-center gap-3 px-3 py-3 mt-1 hover:bg-gradient-to-r hover:from-green-50 hover:to-emerald-50 dark:hover:from-green-900/20 dark:hover:to-emerald-900/20 rounded-xl transition-all duration-300 group relative overflow-hidden {{ $isSeller ? 'bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/30 dark:to-purple-900/30 shadow-sm' : '' }}">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-green-500 to-emerald-500 flex items-center justify-center text-white group-hover:scale-110 group-hover:rotate-3 transition-all duration-300 shadow-md group-hover:shadow-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="font-bold text-sm text-gray-900 dark:text-gray-100 group-hover:text-green-600 dark:group-hover:text-green-400 transition-colors">ร้านค้า</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">จัดการสินค้า</p>
                    </div>
                    @if($isSeller)
                        <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    @endif
                </a>
            @endif

            @if($hasUserAccess)
                <a href="{{ route('user.dashboard') }}"
                   class="flex items-center gap-3 px-3 py-3 mt-1 hover:bg-gradient-to-r hover:from-blue-50 hover:to-indigo-50 dark:hover:from-blue-900/20 dark:hover:to-indigo-900/20 rounded-xl transition-all duration-300 group relative overflow-hidden {{ $isUser ? 'bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/30 dark:to-purple-900/30 shadow-sm' : '' }}">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-500 flex items-center justify-center text-white group-hover:scale-110 group-hover:rotate-3 transition-all duration-300 shadow-md group-hover:shadow-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="font-bold text-sm text-gray-900 dark:text-gray-100 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">ผู้ใช้งาน</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">แดชบอร์ดส่วนตัว</p>
                    </div>
                    @if($isUser)
                        <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    @endif
                </a>
            @endif
        </div>
        @endif

        <!-- Quick Actions -->
        <div class="p-3 border-b border-gray-200 dark:border-gray-700">
            <p class="px-2 py-1.5 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">เมนูลัด</p>

            @if($isUser)
                <a href="{{ route('user.profile') }}" class="flex items-center gap-3 px-3 py-2.5 mt-1 hover:bg-gradient-to-r hover:from-indigo-50 hover:to-purple-50 dark:hover:from-indigo-900/20 dark:hover:to-purple-900/20 rounded-xl transition-all duration-300 group">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 flex items-center justify-center text-gray-600 dark:text-gray-300 group-hover:from-indigo-100 group-hover:to-purple-100 dark:group-hover:from-indigo-900/50 dark:group-hover:to-purple-900/50 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 group-hover:scale-110 transition-all duration-300 shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <span class="text-sm text-gray-700 dark:text-gray-300 font-medium group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">โปรไฟล์ของฉัน</span>
                </a>

                <a href="{{ route('user.wallet.index') }}" class="flex items-center gap-3 px-3 py-2.5 mt-1 hover:bg-gradient-to-r hover:from-green-50 hover:to-emerald-50 dark:hover:from-green-900/20 dark:hover:to-emerald-900/20 rounded-xl transition-all duration-300 group">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 flex items-center justify-center text-gray-600 dark:text-gray-300 group-hover:from-green-100 group-hover:to-emerald-100 dark:group-hover:from-green-900/50 dark:group-hover:to-emerald-900/50 group-hover:text-green-600 dark:group-hover:text-green-400 group-hover:scale-110 transition-all duration-300 shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                    </div>
                    <span class="text-sm text-gray-700 dark:text-gray-300 font-medium group-hover:text-green-600 dark:group-hover:text-green-400 transition-colors">กระเป๋าเงิน</span>
                </a>

                <a href="{{ route('mlm.team') }}" class="flex items-center gap-3 px-3 py-2.5 mt-1 hover:bg-gradient-to-r hover:from-purple-50 hover:to-pink-50 dark:hover:from-purple-900/20 dark:hover:to-pink-900/20 rounded-xl transition-all duration-300 group">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 flex items-center justify-center text-gray-600 dark:text-gray-300 group-hover:from-purple-100 group-hover:to-pink-100 dark:group-hover:from-purple-900/50 dark:group-hover:to-pink-900/50 group-hover:text-purple-600 dark:group-hover:text-purple-400 group-hover:scale-110 transition-all duration-300 shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <span class="text-sm text-gray-700 dark:text-gray-300 font-medium group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors">ทีมงาน</span>
                </a>
            @elseif($isAdmin)
                <a href="{{ route('user.profile') }}" class="flex items-center gap-3 px-3 py-2.5 mt-1 hover:bg-gradient-to-r hover:from-indigo-50 hover:to-purple-50 dark:hover:from-indigo-900/20 dark:hover:to-purple-900/20 rounded-xl transition-all duration-300 group">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 flex items-center justify-center text-gray-600 dark:text-gray-300 group-hover:from-indigo-100 group-hover:to-purple-100 dark:group-hover:from-indigo-900/50 dark:group-hover:to-purple-900/50 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 group-hover:scale-110 transition-all duration-300 shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <span class="text-sm text-gray-700 dark:text-gray-300 font-medium group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">โปรไฟล์ของฉัน</span>
                </a>

                <a href="{{ route('user.profile') }}#change-password" class="flex items-center gap-3 px-3 py-2.5 mt-1 hover:bg-gradient-to-r hover:from-purple-50 hover:to-pink-50 dark:hover:from-purple-900/20 dark:hover:to-pink-900/20 rounded-xl transition-all duration-300 group">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 flex items-center justify-center text-gray-600 dark:text-gray-300 group-hover:from-purple-100 group-hover:to-pink-100 dark:group-hover:from-purple-900/50 dark:group-hover:to-pink-900/50 group-hover:text-purple-600 dark:group-hover:text-purple-400 group-hover:scale-110 transition-all duration-300 shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <span class="text-sm text-gray-700 dark:text-gray-300 font-medium group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors">เปลี่ยนรหัสผ่าน</span>
                </a>

                <a href="{{ route('admin.settings.index') }}" class="flex items-center gap-3 px-3 py-2.5 mt-1 hover:bg-gradient-to-r hover:from-green-50 hover:to-emerald-50 dark:hover:from-green-900/20 dark:hover:to-emerald-900/20 rounded-xl transition-all duration-300 group">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 flex items-center justify-center text-gray-600 dark:text-gray-300 group-hover:from-green-100 group-hover:to-emerald-100 dark:group-hover:from-green-900/50 dark:group-hover:to-emerald-900/50 group-hover:text-green-600 dark:group-hover:text-green-400 group-hover:scale-110 transition-all duration-300 shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <span class="text-sm text-gray-700 dark:text-gray-300 font-medium group-hover:text-green-600 dark:group-hover:text-green-400 transition-colors">ตั้งค่าระบบ</span>
                </a>

                <a href="{{ route('admin.users.index') }}" class="flex items-center gap-3 px-3 py-2.5 mt-1 hover:bg-gradient-to-r hover:from-blue-50 hover:to-cyan-50 dark:hover:from-blue-900/20 dark:hover:to-cyan-900/20 rounded-xl transition-all duration-300 group">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 flex items-center justify-center text-gray-600 dark:text-gray-300 group-hover:from-blue-100 group-hover:to-cyan-100 dark:group-hover:from-blue-900/50 dark:group-hover:to-cyan-900/50 group-hover:text-blue-600 dark:group-hover:text-blue-400 group-hover:scale-110 transition-all duration-300 shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <span class="text-sm text-gray-700 dark:text-gray-300 font-medium group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">จัดการผู้ใช้</span>
                </a>
            @elseif($isSeller)
                <a href="{{ route('seller.profile') }}" class="flex items-center gap-3 px-3 py-2.5 mt-1 hover:bg-gradient-to-r hover:from-indigo-50 hover:to-purple-50 dark:hover:from-indigo-900/20 dark:hover:to-purple-900/20 rounded-xl transition-all duration-300 group">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 flex items-center justify-center text-gray-600 dark:text-gray-300 group-hover:from-indigo-100 group-hover:to-purple-100 dark:group-hover:from-indigo-900/50 dark:group-hover:to-purple-900/50 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 group-hover:scale-110 transition-all duration-300 shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <span class="text-sm text-gray-700 dark:text-gray-300 font-medium group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">โปรไฟล์ของฉัน</span>
                </a>

                <a href="{{ route('seller.products.index') }}" class="flex items-center gap-3 px-3 py-2.5 mt-1 hover:bg-gradient-to-r hover:from-orange-50 hover:to-amber-50 dark:hover:from-orange-900/20 dark:hover:to-amber-900/20 rounded-xl transition-all duration-300 group">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 flex items-center justify-center text-gray-600 dark:text-gray-300 group-hover:from-orange-100 group-hover:to-amber-100 dark:group-hover:from-orange-900/50 dark:group-hover:to-amber-900/50 group-hover:text-orange-600 dark:group-hover:text-orange-400 group-hover:scale-110 transition-all duration-300 shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                    <span class="text-sm text-gray-700 dark:text-gray-300 font-medium group-hover:text-orange-600 dark:group-hover:text-orange-400 transition-colors">จัดการสินค้า</span>
                </a>

                <a href="{{ route('seller.pos.index') }}" class="flex items-center gap-3 px-3 py-2.5 mt-1 hover:bg-gradient-to-r hover:from-cyan-50 hover:to-blue-50 dark:hover:from-cyan-900/20 dark:hover:to-blue-900/20 rounded-xl transition-all duration-300 group">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 flex items-center justify-center text-gray-600 dark:text-gray-300 group-hover:from-cyan-100 group-hover:to-blue-100 dark:group-hover:from-cyan-900/50 dark:group-hover:to-blue-900/50 group-hover:text-cyan-600 dark:group-hover:text-cyan-400 group-hover:scale-110 transition-all duration-300 shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <span class="text-sm text-gray-700 dark:text-gray-300 font-medium group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors">🏪 ระบบขายหน้าร้าน (POS)</span>
                </a>

                <a href="{{ route('seller.orders.index') }}" class="flex items-center gap-3 px-3 py-2.5 mt-1 hover:bg-gradient-to-r hover:from-green-50 hover:to-emerald-50 dark:hover:from-green-900/20 dark:hover:to-emerald-900/20 rounded-xl transition-all duration-300 group">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 flex items-center justify-center text-gray-600 dark:text-gray-300 group-hover:from-green-100 group-hover:to-emerald-100 dark:group-hover:from-green-900/50 dark:group-hover:to-emerald-900/50 group-hover:text-green-600 dark:group-hover:text-green-400 group-hover:scale-110 transition-all duration-300 shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <span class="text-sm text-gray-700 dark:text-gray-300 font-medium group-hover:text-green-600 dark:group-hover:text-green-400 transition-colors">คำสั่งซื้อ</span>
                </a>
            @endif
        </div>

        <!-- Logout -->
        <div class="p-3">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex items-center gap-3 px-3 py-2.5 hover:bg-gradient-to-r hover:from-red-50 hover:to-pink-50 dark:hover:from-red-900/20 dark:hover:to-pink-900/20 rounded-xl transition-all duration-300 group w-full">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 flex items-center justify-center text-gray-600 dark:text-gray-300 group-hover:from-red-100 group-hover:to-pink-100 dark:group-hover:from-red-900/50 dark:group-hover:to-pink-900/50 group-hover:text-red-600 dark:group-hover:text-red-400 group-hover:scale-110 transition-all duration-300 shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </div>
                    <span class="text-sm text-gray-700 dark:text-gray-300 group-hover:text-red-600 dark:group-hover:text-red-400 font-medium transition-colors">ออกจากระบบ</span>
                </button>
            </form>
        </div>
    </div>
</div>
