{{--
    Arrow X Sidebar Component - Premium Edition

    Sidebar แบบ Arrow X Theme - ทันสมัย เรียบหรู พร้อม RGB effects, Glassmorphism และ 3D effects

    Props:
    - type: ประเภท (admin, seller, user)
--}}

@props(['type' => 'admin'])

@php
    use App\Services\MenuService;
    use App\Models\Setting;
    use App\Models\WindowsUiSetting;

    // ดึง menu service
    $menuService = app(MenuService::class);
    $menus = $menuService->getMenuForRole($type, auth()->user());

    // Logo & Settings
    $logo = Setting::get('logo');
    $appName = Setting::get('app_name', 'TP-Affiliate');
    $sidebarWidth = WindowsUiSetting::get('sidebar_width', 280);

    // User info
    $user = auth()->user();
    $userRole = ucfirst($type);
@endphp

<div x-data="arrowXSidebar()" class="arrow-x-sidebar-wrapper">
    <!-- Sidebar -->
    <aside
        x-show="sidebarOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
        class="fixed left-0 top-0 h-screen flex flex-col bg-gradient-to-br from-purple-900 via-indigo-900 to-blue-900 dark:from-gray-950 dark:via-gray-900 dark:to-gray-950 shadow-2xl z-50"
        style="width: {{ $sidebarWidth }}px;">

        <!-- Glassmorphism overlay -->
        <div class="absolute inset-0 bg-gradient-to-br from-white/5 to-transparent pointer-events-none"></div>

        <!-- RGB Glow Effect -->
        <div class="absolute -top-20 -left-20 w-40 h-40 bg-purple-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-pulse"></div>
        <div class="absolute -bottom-20 -right-20 w-40 h-40 bg-blue-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-pulse" style="animation-delay: 1s;"></div>

        <!-- User Profile Section -->
        <div class="relative p-6 border-b border-white/10 dark:border-gray-700/30 bg-black/10 backdrop-blur-sm">
            <div class="flex items-center space-x-3">
                <!-- Avatar -->
                <div class="relative">
                    @if($user->avatar)
                        <img src="{{ asset($user->avatar) }}" alt="{{ $user->name }}" class="h-12 w-12 rounded-full ring-2 ring-purple-400/50 shadow-lg">
                    @else
                        <div class="h-12 w-12 rounded-full bg-gradient-to-br from-purple-500 to-blue-500 flex items-center justify-center ring-2 ring-purple-400/50 shadow-lg">
                            <span class="text-white font-bold text-lg">{{ substr($user->name, 0, 1) }}</span>
                        </div>
                    @endif
                    <!-- Online Status -->
                    <div class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-purple-900 rounded-full"></div>
                </div>

                <!-- User Info -->
                <div class="flex-1 min-w-0">
                    <h3 class="text-sm font-bold text-white truncate">{{ $user->name }}</h3>
                    <p class="text-xs text-purple-200 dark:text-gray-400">{{ $userRole }}</p>
                </div>
            </div>
        </div>

        <!-- Logo Header -->
        <div class="relative p-4 border-b border-white/10 dark:border-gray-700/30">
            <div class="flex items-center space-x-3">
                @if($logo)
                    <img src="{{ asset($logo) }}" alt="{{ $appName }}" class="h-8 w-8 rounded-lg shadow-lg">
                @else
                    <div class="h-8 w-8 rounded-lg bg-gradient-to-br from-purple-500 to-blue-500 shadow-lg flex items-center justify-center">
                        <span class="text-white font-bold text-xs">TP</span>
                    </div>
                @endif
                <div>
                    <h1 class="text-base font-bold text-white">{{ $appName }}</h1>
                    <p class="text-xs text-purple-200 dark:text-gray-400">Dashboard</p>
                </div>
            </div>
        </div>

        <!-- Menu Navigation (Scrollable) -->
        <nav class="flex-1 overflow-y-auto py-4 px-3 scrollbar-thin scrollbar-thumb-white/20 scrollbar-track-transparent">
            @foreach($menus as $menu)
                @if(isset($menu['children']) && count($menu['children']) > 0)
                    <!-- Menu with Submenu -->
                    <div x-data="{ open: false }" class="mb-2 relative">
                        <button
                            @click="open = !open"
                            class="w-full flex items-center justify-between px-4 py-3 text-white/90 hover:text-white hover:bg-white/10 dark:hover:bg-gray-700/50 rounded-xl transition-all duration-300 group relative overflow-hidden backdrop-blur-sm">
                            <!-- Hover Glow -->
                            <div class="absolute inset-0 bg-gradient-to-r from-purple-500/0 via-purple-500/10 to-purple-500/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>

                            <div class="flex items-center space-x-3 relative z-10">
                                @if(isset($menu['icon']))
                                    <i class="{{ $menu['icon'] }} text-purple-300 dark:text-gray-400 group-hover:text-white transition-colors text-lg"></i>
                                @endif
                                <span class="font-medium text-sm">{{ $menu['label'] }}</span>
                            </div>
                            <svg
                                class="w-4 h-4 transform transition-transform duration-300 relative z-10"
                                :class="{ 'rotate-180': open }"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <!-- Submenu -->
                        <div
                            x-show="open"
                            x-collapse
                            class="ml-4 mt-1 space-y-1">
                            @foreach($menu['children'] as $child)
                                <a
                                    href="{{ $child['url'] }}"
                                    class="block px-4 py-2.5 text-sm text-white/80 hover:text-white hover:bg-white/10 dark:hover:bg-gray-700/50 rounded-lg transition-all duration-200 hover:translate-x-1
                                    {{ request()->is(ltrim($child['url'] ?? '', '/')) ? 'bg-white/20 text-white shadow-lg' : '' }}">
                                    <div class="flex items-center space-x-2">
                                        <div class="w-1.5 h-1.5 rounded-full bg-purple-400"></div>
                                        <span>{{ $child['label'] }}</span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @else
                    <!-- Single Menu Item -->
                    <a
                        href="{{ $menu['url'] }}"
                        class="flex items-center space-x-3 px-4 py-3 mb-2 text-white/90 hover:text-white hover:bg-white/10 dark:hover:bg-gray-700/50 rounded-xl transition-all duration-300 group relative overflow-hidden backdrop-blur-sm
                        {{ request()->is(ltrim($menu['url'] ?? '', '/')) ? 'bg-gradient-to-r from-purple-500/30 to-blue-500/30 text-white shadow-lg shadow-purple-500/20' : '' }}">

                        <!-- Active Indicator -->
                        @if(request()->is(ltrim($menu['url'] ?? '', '/')))
                            <div class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-8 bg-gradient-to-b from-purple-400 to-blue-400 rounded-r-full"></div>
                        @endif

                        <!-- Hover Glow -->
                        <div class="absolute inset-0 bg-gradient-to-r from-purple-500/0 via-purple-500/10 to-purple-500/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>

                        @if(isset($menu['icon']))
                            <i class="{{ $menu['icon'] }} text-purple-300 dark:text-gray-400 group-hover:text-white transition-colors text-lg relative z-10
                            {{ request()->is(ltrim($menu['url'] ?? '', '/')) ? 'text-white' : '' }}"></i>
                        @endif
                        <span class="font-medium text-sm relative z-10">{{ $menu['label'] }}</span>

                        <!-- Badge (if needed) -->
                        @if(isset($menu['badge']) && $menu['badge'] > 0)
                            <span class="ml-auto px-2 py-0.5 bg-red-500 text-white text-xs font-bold rounded-full shadow-lg relative z-10">
                                {{ $menu['badge'] }}
                            </span>
                        @endif
                    </a>
                @endif
            @endforeach
        </nav>

        <!-- Footer - Static at bottom -->
        <div class="relative p-4 border-t border-white/10 dark:border-gray-700/30 bg-black/20 backdrop-blur-sm">
            <!-- Logout Button -->
            <form action="{{ route('logout') }}" method="POST" class="mb-3">
                @csrf
                <button type="submit" class="w-full flex items-center justify-center space-x-2 px-4 py-2.5 bg-red-500/20 hover:bg-red-500/30 text-red-300 hover:text-white rounded-lg transition-all duration-200 border border-red-500/30 hover:border-red-500/50">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="text-sm font-medium">ออกจากระบบ</span>
                </button>
            </form>

            <!-- Copyright -->
            <div class="text-center text-xs text-white/40 dark:text-gray-600">
                <p class="font-semibold text-purple-300/60">Arrow X Theme</p>
                <p class="mt-0.5">© {{ date('Y') }} {{ $appName }}</p>
            </div>
        </div>
    </aside>

    <!-- Toggle Button (with state indicator) - ปุ่มเบอร์เกอร์เมนู -->
    <button
        @click="toggleSidebar()"
        type="button"
        class="fixed top-4 z-[60] p-3 bg-gradient-to-br from-purple-600 to-blue-600 dark:from-purple-700 dark:to-blue-700 text-white rounded-xl shadow-2xl hover:shadow-purple-500/50 transition-all duration-300 group backdrop-blur-sm border border-white/20 active:scale-95"
        :class="sidebarOpen ? 'left-[{{ $sidebarWidth + 16 }}px]' : 'left-4'"
        :style="sidebarOpen ? 'left: calc({{ $sidebarWidth }}px + 1rem)' : 'left: 1rem'"
        aria-label="เปิด/ปิดเมนู">

        <!-- Icon changes based on state -->
        <svg x-show="!sidebarOpen" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
        <svg x-show="sidebarOpen" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>

        <!-- Pulsing Ring -->
        <div class="absolute inset-0 rounded-xl bg-purple-500 animate-ping opacity-20 pointer-events-none"></div>
    </button>

    <!-- Overlay for Mobile -->
    <div
        x-show="sidebarOpen"
        @click="sidebarOpen = false"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-black/60 backdrop-blur-sm z-40 lg:hidden">
    </div>
</div>

<style>
    /* Ensure content doesn't overlap with sidebar on desktop */
    @media (min-width: 1024px) {
        .arrow-x-content {
            margin-left: {{ $sidebarWidth }}px;
            transition: margin-left 0.3s ease;
        }
    }

    /* Custom scrollbar for sidebar */
    .arrow-x-sidebar-wrapper aside::-webkit-scrollbar {
        width: 4px;
    }

    .arrow-x-sidebar-wrapper aside::-webkit-scrollbar-track {
        background: transparent;
    }

    .arrow-x-sidebar-wrapper aside::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.15);
        border-radius: 2px;
    }

    .arrow-x-sidebar-wrapper aside::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.25);
    }

    /* Smooth scrolling */
    .arrow-x-sidebar-wrapper nav {
        scroll-behavior: smooth;
    }
</style>

<script>
    /**
     * Arrow X Sidebar - Alpine.js Component
     *
     * จัดการการเปิด/ปิด sidebar พร้อม responsive behavior
     */
    function arrowXSidebar() {
        return {
            sidebarOpen: window.innerWidth >= 1024, // Open by default on desktop

            init() {
                console.log('Arrow X Sidebar initialized', {
                    sidebarOpen: this.sidebarOpen,
                    windowWidth: window.innerWidth
                });

                // Auto-close on mobile when route changes
                if (window.innerWidth < 1024) {
                    this.sidebarOpen = false;
                }

                // Handle window resize
                window.addEventListener('resize', () => {
                    if (window.innerWidth >= 1024) {
                        this.sidebarOpen = true;
                    } else {
                        this.sidebarOpen = false;
                    }
                });

                // Close sidebar when clicking on menu items on mobile
                this.$el.querySelectorAll('nav a').forEach(link => {
                    link.addEventListener('click', () => {
                        if (window.innerWidth < 1024) {
                            setTimeout(() => {
                                this.sidebarOpen = false;
                            }, 200);
                        }
                    });
                });
            },

            // Toggle sidebar method
            toggleSidebar() {
                this.sidebarOpen = !this.sidebarOpen;
                console.log('Sidebar toggled:', this.sidebarOpen);
            }
        }
    }
</script>
