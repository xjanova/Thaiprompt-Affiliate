{{--
    Base Menu Component - V3 Menu System

    Theme-agnostic menu component ใช้ Alpine.js
    รองรับทุก theme: Arrow X, Millennium, Classic X

    Props:
    - role: Role ของผู้ใช้ (admin, seller, user)
    - menuItems: Array ของเมนู (optional - จะดึงจาก MenuService ถ้าไม่ส่งมา)
    - theme: Theme ที่ใช้แสดงผล (default: จาก config)
    - searchEnabled: เปิดใช้ค้นหาหรือไม่ (default: true)

    @version 3.0.0
    @since 2025-11-15
--}}

@props([
    'role' => 'admin',
    'menuItems' => null,
    'theme' => null,
    'searchEnabled' => true,
])

@php
    use App\Services\MenuService;

    // ดึง MenuService instance
    $menuService = app(MenuService::class);

    // ถ้าไม่มี menuItems ส่งมา ให้ดึงจาก service
    if (!$menuItems) {
        $menuItems = $menuService->getMenuForRole($role, auth()->user());
    }

    // ดึง theme config
    $themeConfig = config('menu-themes.themes.' . ($theme ?? config('menu-themes.default')));
    $behaviors = config('menu-themes.behaviors');

    // สำหรับ active route
    $currentRoute = request()->route() ? request()->route()->getName() : '';
@endphp

<div
    x-data="menuComponent(@js($menuItems), @js($behaviors), @js($currentRoute))"
    x-init="init()"
    class="menu-container"
    @keydown.escape.window="handleEscape()"
>
    {{-- Search Box --}}
    @if($searchEnabled && $behaviors['search_realtime'])
    <div class="menu-search mb-4">
        <input
            type="text"
            x-model="searchKeyword"
            @input="handleSearch()"
            :placeholder="searchPlaceholder"
            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600
                   bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
                   focus:outline-none focus:ring-2 focus:ring-primary-500 transition"
        />
    </div>
    @endif

    {{-- Menu Items --}}
    <div class="menu-items space-y-2">
        <template x-for="(item, index) in filteredMenus" :key="item.id || index">
            <div>
                {{-- Main Menu Item --}}
                <x-menu.menu-item :item="item" />

                {{-- Submenu (ถ้ามี) --}}
                <template x-if="item.submenu && item.submenu.length > 0">
                    <div
                        x-show="openSubmenus[item.id]"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 transform -translate-y-2"
                        x-transition:enter-end="opacity-100 transform translate-y-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 transform translate-y-0"
                        x-transition:leave-end="opacity-0 transform -translate-y-2"
                        class="submenu-container"
                    >
                        <x-menu.submenu :items="item.submenu" :parent-id="item.id" />
                    </div>
                </template>
            </div>
        </template>
    </div>

    {{-- Empty State --}}
    <div x-show="filteredMenus.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
        <svg class="mx-auto h-12 w-12 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <p x-text="searchKeyword ? 'ไม่พบเมนูที่ค้นหา' : 'ไม่มีเมนู'"></p>
    </div>
</div>

{{-- Alpine.js Component Logic --}}
@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('menuComponent', (items, behaviors, currentRoute) => ({
        // State
        menuItems: items,
        openSubmenus: {},
        searchKeyword: '',
        searchPlaceholder: 'ค้นหาเมนู...',
        activeRoute: currentRoute,
        behaviors: behaviors,

        /**
         * Initialize component
         */
        init() {
            // เปิด submenu ที่มี active route อัตโนมัติ
            this.openActiveSubmenu();

            // Keyboard navigation
            if (this.behaviors.keyboard_navigation) {
                this.setupKeyboardNavigation();
            }
        },

        /**
         * Filtered menus (จากการค้นหา)
         */
        get filteredMenus() {
            if (!this.searchKeyword) {
                return this.menuItems;
            }

            return this.searchMenus(this.menuItems, this.searchKeyword.toLowerCase());
        },

        /**
         * ค้นหาเมนู (recursive)
         */
        searchMenus(menus, keyword) {
            const results = [];

            for (const menu of menus) {
                const label = (menu.label || '').toLowerCase();

                // ตรงกับเมนูหลัก
                if (label.includes(keyword)) {
                    results.push(menu);
                    continue;
                }

                // ค้นหาใน submenu
                if (menu.submenu && menu.submenu.length > 0) {
                    const subResults = this.searchMenus(menu.submenu, keyword);
                    if (subResults.length > 0) {
                        // เปิด submenu ถ้าพบ
                        this.openSubmenus[menu.id] = true;
                        results.push({
                            ...menu,
                            submenu: subResults
                        });
                    }
                }
            }

            return results;
        },

        /**
         * Toggle submenu
         */
        toggleSubmenu(itemId) {
            // Auto-close siblings ถ้าเปิดใช้งาน
            if (this.behaviors.auto_close_siblings) {
                // หา parent level เดียวกัน
                const otherIds = this.menuItems
                    .filter(m => m.id !== itemId && m.submenu)
                    .map(m => m.id);

                otherIds.forEach(id => {
                    this.openSubmenus[id] = false;
                });
            }

            // Toggle submenu ปัจจุบัน
            this.openSubmenus[itemId] = !this.openSubmenus[itemId];
        },

        /**
         * เปิด submenu ที่มี active route
         */
        openActiveSubmenu() {
            const findAndOpen = (menus, parentId = null) => {
                for (const menu of menus) {
                    // ตรวจสอบเมนูหลัก
                    if (menu.route === this.activeRoute) {
                        if (parentId) {
                            this.openSubmenus[parentId] = true;
                        }
                        return true;
                    }

                    // ตรวจสอบ submenu
                    if (menu.submenu && menu.submenu.length > 0) {
                        if (findAndOpen(menu.submenu, menu.id)) {
                            this.openSubmenus[menu.id] = true;
                            return true;
                        }
                    }
                }
                return false;
            };

            findAndOpen(this.menuItems);
        },

        /**
         * Handle search input
         */
        handleSearch() {
            // Debounce จะทำใน Alpine.js directive ถ้าต้องการ
        },

        /**
         * Handle ESC key
         */
        handleEscape() {
            if (this.behaviors.close_on_escape) {
                // ปิด submenu ทั้งหมด
                Object.keys(this.openSubmenus).forEach(key => {
                    this.openSubmenus[key] = false;
                });

                // Clear search
                this.searchKeyword = '';
            }
        },

        /**
         * Setup keyboard navigation
         */
        setupKeyboardNavigation() {
            // TODO: Implement arrow key navigation
            // Up/Down: navigate through menu items
            // Enter: select menu
            // Left/Right: open/close submenu
        },

        /**
         * Check if menu is active
         */
        isActive(item) {
            return item.route === this.activeRoute;
        },

        /**
         * Check if submenu has active item
         */
        hasActiveSubmenu(item) {
            if (!item.submenu) return false;

            const checkActive = (menus) => {
                for (const menu of menus) {
                    if (menu.route === this.activeRoute) return true;
                    if (menu.submenu && checkActive(menu.submenu)) return true;
                }
                return false;
            };

            return checkActive(item.submenu);
        },
    }));
});
</script>
@endpush
