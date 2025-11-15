@props(['type' => 'admin'])

@php
    use App\Models\ClassicXSetting;
    use Illuminate\Support\Facades\Route;
    use App\Services\MenuService;

    // Get user info
    $user = auth()->user();

    // Safe route helper
    function safeRoute($routeName, $default = '#') {
        try {
            if (Route::has($routeName)) {
                return route($routeName);
            }
        } catch (\Exception $e) {
            $path = str_replace('.index', '', $routeName);
            $path = str_replace('.', '/', $path);
            return '/' . $path;
        }
        return $default;
    }

    // Current route for active state
    $currentRoute = request()->route() ? request()->route()->getName() : '';

    // ========================================
    // ✅ V3 MENU SYSTEM - Using MenuService (Single Source of Truth)
    // ========================================

    // ใช้ MenuService เพื่อดึงเมนูจาก config/menus.php
    $menuService = app(MenuService::class);
    $menuItems = $menuService->getMenuForRole($type, $user);

    // แปลง emoji icons เป็น Font Awesome icons (สำหรับ Classic X theme)
    $iconMap = [
        '📊' => 'fa-dashboard',
        '👤' => 'fa-user',
        '👥' => 'fa-users',
        '🪪' => 'fa-id-card',
        '💰' => 'fa-money-bill',
        '💳' => 'fa-wallet',
        '₿' => 'fa-bitcoin',
        '🛒' => 'fa-shopping-cart',
        '🏨' => 'fa-hotel',
        '🎫' => 'fa-ticket',
        '💬' => 'fa-comments',
        '🎮' => 'fa-gamepad',
        '🤖' => 'fa-robot',
        '📈' => 'fa-chart-line',
        '📦' => 'fa-box',
        '🏪' => 'fa-cash-register',
        '💵' => 'fa-money-bill',
        '📢' => 'fa-bullhorn',
        '🔔' => 'fa-bell',
        '🎨' => 'fa-palette',
        '🎯' => 'fa-bullseye',
        '🔐' => 'fa-lock',
        '⚙️' => 'fa-cog',
        '💎' => 'fa-gem',
        '💖' => 'fa-heart',
        '📱' => 'fa-line',
        '🎓' => 'fa-graduation-cap',
        '📚' => 'fa-book',
        '👨‍💼' => 'fa-user-tie',
        '📊' => 'fa-calculator',
        '🔒' => 'fa-lock',
        '📄' => 'fa-file-alt',
        '🌐' => 'fa-globe',
        '🎬' => 'fa-film',
        '📧' => 'fa-envelope',
    ];

    // แปลง icons
    foreach ($menuItems as &$item) {
        if (isset($item['icon']) && isset($iconMap[$item['icon']])) {
            $item['icon'] = $iconMap[$item['icon']];
        }
    }
    unset($item);

    // Get app information for footer
    $appName = \App\Models\Setting::get('app_name', 'TP-Affiliate');

    // Get version from package.json
    $packageJson = json_decode(file_get_contents(base_path('package.json')), true);
    $appVersion = $packageJson['version'] ?? '2.169.1';

    $copyrightText = \App\Models\Setting::get('copyright_text', '© 2025 All Rights Reserved');

    // Auto-expand submenu if it contains the active route
    $activeSubmenus = [];
    $parentMenuHasActive = []; // Track which parent menus have active children
    $lockedSubmenus = []; // Track which submenus should stay open (contain active items)
    $currentUrl = request()->url(); // Get current full URL
    $currentPath = request()->path(); // Get current path

    foreach ($menuItems as $index => $item) {
        if (isset($item['submenu'])) {
            foreach ($item['submenu'] as $subitem) {
                $isActive = false;

                // Check by route name first
                if (isset($subitem['route']) && $currentRoute === $subitem['route']) {
                    $isActive = true;
                }
                // Fallback: check by URL matching
                elseif (isset($subitem['url']) && $subitem['url'] !== '#') {
                    // Normalize URLs for comparison
                    $submenuUrl = $subitem['url'];
                    if (strpos($submenuUrl, url('/')) === 0) {
                        $submenuUrl = str_replace(url('/'), '', $submenuUrl);
                    }
                    $submenuUrl = ltrim($submenuUrl, '/');
                    $currentPath = ltrim($currentPath, '/');

                    // Check if current path matches submenu URL
                    if ($submenuUrl === $currentPath || url($submenuUrl) === $currentUrl) {
                        $isActive = true;
                    }
                }

                if ($isActive) {
                    $activeSubmenus[] = 'menu-' . $index;
                    $parentMenuHasActive[$index] = true;
                    $lockedSubmenus[] = 'menu-' . $index; // Lock this submenu open
                    break;
                }
            }
        }
    }
    $activeSubmenusJson = json_encode($activeSubmenus);
    $lockedSubmenusJson = json_encode($lockedSubmenus);
@endphp

<ul class="classic-x-menu" x-data="{
    openSubmenus: {{ $activeSubmenusJson }},
    lockedSubmenus: {{ $lockedSubmenusJson }},
    toggleMenu(menuId) {
        // If this submenu is locked (contains active item), don't allow closing
        if (this.lockedSubmenus.includes(menuId)) {
            return;
        }
        // Otherwise, toggle normally
        if (this.openSubmenus.includes(menuId)) {
            this.openSubmenus = this.openSubmenus.filter(id => id !== menuId);
        } else {
            this.openSubmenus = [...this.openSubmenus, menuId];
        }
    }
}">
    @foreach($menuItems as $index => $item)
        <li class="classic-x-menu-item">
            @if(isset($item['submenu']))
                {{-- Menu item with submenu --}}
                <a href="{{ $item['url'] }}"
                   class="classic-x-menu-link {{ isset($parentMenuHasActive[$index]) ? 'has-active-child' : '' }}"
                   @click.prevent="toggleMenu('menu-{{ $index }}')">
                    <span class="classic-x-menu-icon">
                        <i class="fas {{ $item['icon'] }}"></i>
                    </span>
                    <span class="classic-x-menu-text">{{ $item['label'] }}</span>
                    @if(isset($item['badge']))
                        <span class="classic-x-badge">{{ $item['badge'] }}</span>
                    @endif
                    <span class="classic-x-submenu-indicator"
                          :class="{
                              'open': openSubmenus.includes('menu-{{ $index }}'),
                              'locked': lockedSubmenus.includes('menu-{{ $index }}')
                          }">
                        <i class="fas" :class="lockedSubmenus.includes('menu-{{ $index }}') ? 'fa-lock' : 'fa-chevron-down'"></i>
                    </span>
                </a>

                {{-- Submenu --}}
                <ul class="classic-x-submenu" :class="{ 'open': openSubmenus.includes('menu-{{ $index }}') }">
                    @foreach($item['submenu'] as $subitem)
                        <li>
                            <a href="{{ $subitem['url'] }}"
                               class="classic-x-submenu-link @if(isset($subitem['route']) && $currentRoute === $subitem['route']) active @endif">
                                {{ $subitem['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            @else
                {{-- Simple menu item --}}
                <a href="{{ $item['url'] }}"
                   class="classic-x-menu-link @if(isset($item['route']) && $currentRoute === $item['route']) active @endif">
                    <span class="classic-x-menu-icon">
                        <i class="fas {{ $item['icon'] }}"></i>
                    </span>
                    <span class="classic-x-menu-text">{{ $item['label'] }}</span>
                    @if(isset($item['badge']))
                        <span class="classic-x-badge">{{ $item['badge'] }}</span>
                    @endif
                </a>
            @endif
        </li>
    @endforeach
</ul>
