{{--
    Arrow X Sidebar Component

    Sidebar แบบ Arrow X Theme - ทันสมัย เรียบหรู พร้อม RGB effects

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
@endphp

<div x-data="{ sidebarOpen: true, activeMenu: '' }" class="arrow-x-sidebar-wrapper">
    <!-- Sidebar -->
    <aside
        x-show="sidebarOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
        class="fixed left-0 top-0 h-screen bg-gradient-to-br from-purple-900 via-indigo-800 to-blue-900 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 shadow-2xl z-50 overflow-y-auto"
        style="width: {{ $sidebarWidth }}px;">

        <!-- Logo Header -->
        <div class="p-6 border-b border-white/10 dark:border-gray-700/50">
            <div class="flex items-center space-x-3">
                @if($logo)
                    <img src="{{ asset($logo) }}" alt="{{ $appName }}" class="h-10 w-10 rounded-lg shadow-lg">
                @endif
                <div>
                    <h1 class="text-xl font-bold text-white">{{ $appName }}</h1>
                    <p class="text-xs text-purple-200 dark:text-gray-400">{{ ucfirst($type) }} Dashboard</p>
                </div>
            </div>
        </div>

        <!-- Menu Navigation -->
        <nav class="py-4 px-3">
            @foreach($menus as $menu)
                @if(isset($menu['children']) && count($menu['children']) > 0)
                    <!-- Menu with Submenu -->
                    <div x-data="{ open: false }" class="mb-2">
                        <button
                            @click="open = !open"
                            class="w-full flex items-center justify-between px-4 py-3 text-white/90 hover:text-white hover:bg-white/10 dark:hover:bg-gray-700/50 rounded-lg transition-all duration-200 group">
                            <div class="flex items-center space-x-3">
                                @if(isset($menu['icon']))
                                    <i class="{{ $menu['icon'] }} text-purple-300 dark:text-gray-400 group-hover:text-white transition-colors"></i>
                                @endif
                                <span class="font-medium">{{ $menu['label'] }}</span>
                            </div>
                            <svg
                                class="w-4 h-4 transform transition-transform duration-200"
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
                                    class="block px-4 py-2 text-sm text-white/80 hover:text-white hover:bg-white/10 dark:hover:bg-gray-700/50 rounded-lg transition-all duration-200">
                                    {{ $child['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @else
                    <!-- Single Menu Item -->
                    <a
                        href="{{ $menu['url'] }}"
                        class="flex items-center space-x-3 px-4 py-3 mb-2 text-white/90 hover:text-white hover:bg-white/10 dark:hover:bg-gray-700/50 rounded-lg transition-all duration-200 group
                        {{ request()->is($menu['active'] ?? '') ? 'bg-white/20 dark:bg-gray-700 text-white shadow-lg' : '' }}">
                        @if(isset($menu['icon']))
                            <i class="{{ $menu['icon'] }} text-purple-300 dark:text-gray-400 group-hover:text-white transition-colors"></i>
                        @endif
                        <span class="font-medium">{{ $menu['label'] }}</span>
                    </a>
                @endif
            @endforeach
        </nav>

        <!-- Footer -->
        <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-white/10 dark:border-gray-700/50 bg-black/20 dark:bg-gray-900/50">
            <div class="text-center text-xs text-white/60 dark:text-gray-500">
                <p>Arrow X Theme</p>
                <p class="mt-1">© {{ date('Y') }} {{ $appName }}</p>
            </div>
        </div>
    </aside>

    <!-- Toggle Button -->
    <button
        @click="sidebarOpen = !sidebarOpen"
        class="fixed top-4 left-4 z-50 p-2 bg-gradient-to-r from-purple-600 to-blue-600 dark:from-gray-700 dark:to-gray-600 text-white rounded-lg shadow-lg hover:shadow-xl transition-all duration-200">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
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
        class="fixed inset-0 bg-black/50 z-40 lg:hidden">
    </div>
</div>

<style>
    /* Ensure content doesn't overlap with sidebar */
    @media (min-width: 1024px) {
        .arrow-x-content {
            margin-left: {{ $sidebarWidth }}px;
        }
    }

    /* Custom scrollbar for sidebar */
    .arrow-x-sidebar-wrapper aside::-webkit-scrollbar {
        width: 6px;
    }

    .arrow-x-sidebar-wrapper aside::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.05);
    }

    .arrow-x-sidebar-wrapper aside::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 3px;
    }

    .arrow-x-sidebar-wrapper aside::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.3);
    }
</style>
