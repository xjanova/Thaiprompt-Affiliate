<?php

namespace App\Services;

use App\Models\MenuItem;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/**
 * Menu Service
 *
 * จัดการเมนูทั้งหมดในระบบ
 * - ดึงเมนูจาก Database (primary) หรือ config (fallback)
 * - รวมเมนูจาก Feature Providers
 * - กรองตาม permissions และ role settings
 * - แปลง route names เป็น URLs
 *
 * @version 3.1.0
 *
 * @since 2025-11-15
 *
 * @updated 2025-12-04 - เพิ่มการดึงเมนูจาก Database และ Role Settings
 */
class MenuService
{
    /**
     * MenuRegistrar instance สำหรับรวบรวมเมนูจาก providers
     */
    protected $registrar;

    /**
     * Cache สำหรับเมนูที่ดึงมาแล้ว
     */
    protected static array $menuCache = [];

    /**
     * Constructor
     */
    public function __construct(?MenuRegistrar $registrar = null)
    {
        $this->registrar = $registrar;
    }

    /**
     * ดึงเมนูสำหรับ role ที่กำหนด
     *
     * แนวทางใหม่ (V3.2):
     * - ใช้ config/menus.php เป็นหลักเสมอ (Hardcoded - ง่ายต่อการพัฒนา)
     * - Database ใช้สำหรับ Override เท่านั้น (ซ่อน/เรียงลำดับ/ปรับแต่งผ่าน Admin UI)
     * - Pinned menus จัดการแยกทาง localStorage (frontend)
     *
     * @param  string  $role  Role ของผู้ใช้ (admin, seller, user)
     * @param  mixed  $user  User instance (optional) สำหรับกรอง permissions
     * @return array เมนูทั้งหมดที่กรองแล้ว
     *
     * @example
     * $menus = $menuService->getMenuForRole('admin', auth()->user());
     */
    public function getMenuForRole(string $role, $user = null): array
    {
        // 1. ดึงเมนูจาก config/menus.php เป็นหลัก (Hardcoded)
        $configMenus = config("menus.{$role}", []);

        // 2. ดึงเมนูจาก Feature Providers (ถ้ามี)
        $providerMenus = [];
        if ($this->registrar) {
            $providerMenus = $this->registrar->getMenusForRole($role);
        }

        // 3. รวมเมนูทั้งหมด
        $allMenus = $this->mergeMenus($configMenus, $providerMenus);

        // 4. Apply Database Overrides ถ้ามี (สำหรับ Admin customization)
        // ตั้งค่าใน .env: MENU_USE_DB_OVERRIDES=true
        if (config('app.menu_use_db_overrides', false)) {
            $allMenus = $this->applyDatabaseOverrides($allMenus, $role, $user);
        }

        // 5. กรองตาม permissions
        if ($user) {
            $allMenus = $this->filterByPermissions($allMenus, $user);
        }

        // 6. แปลง routes เป็น URLs
        $allMenus = $this->resolveRoutes($allMenus);

        // 7. เรียงลำดับตาม order
        $allMenus = $this->sortMenus($allMenus);

        return $allMenus;
    }

    /**
     * Apply Database Overrides ลงบนเมนูจาก config
     *
     * ใช้สำหรับ Admin ปรับแต่งเมนูผ่าน UI เช่น:
     * - ซ่อนเมนูบางรายการ
     * - เปลี่ยนลำดับเมนู
     * - เปลี่ยน label/icon
     *
     * @param  array  $menus  เมนูจาก config
     * @param  string  $dashboardType  ประเภท dashboard
     * @param  mixed  $user  User instance
     * @return array เมนูที่ apply overrides แล้ว
     */
    protected function applyDatabaseOverrides(array $menus, string $dashboardType, $user = null): array
    {
        // ตรวจสอบว่าตาราง menu_items มีอยู่หรือไม่
        if (! Schema::hasTable('menu_items')) {
            return $menus;
        }

        // ดึง overrides จาก database
        $overrides = MenuItem::forDashboard($dashboardType)
            ->get()
            ->keyBy('menu_key');

        if ($overrides->isEmpty()) {
            return $menus;
        }

        // Apply overrides
        return array_map(function ($menu) use ($overrides) {
            $menuKey = $menu['id'] ?? null;
            if (! $menuKey || ! $overrides->has($menuKey)) {
                return $menu;
            }

            $override = $overrides->get($menuKey);

            // ซ่อนเมนูถ้า is_visible = false
            if (! $override->is_visible || ! $override->is_active) {
                $menu['hidden'] = true;
            }

            // Override order
            if ($override->order !== null) {
                $menu['order'] = $override->order;
            }

            // Override label/icon (ถ้ามีการตั้งค่า)
            if ($override->label && $override->label !== $menu['label']) {
                $menu['label'] = $override->label;
            }
            if ($override->icon && $override->icon !== $menu['icon']) {
                $menu['icon'] = $override->icon;
            }

            return $menu;
        }, $menus);

        // กรองเมนูที่ถูกซ่อน
        return array_filter($menus, fn ($m) => empty($m['hidden']));
    }

    /**
     * ดึงเมนูจาก Database
     *
     * @param  string  $dashboardType  ประเภท dashboard (admin, seller, user)
     * @param  mixed  $user  User instance (optional)
     * @return array เมนูจาก database
     */
    protected function getMenusFromDatabase(string $dashboardType, $user = null): array
    {
        // ตรวจสอบว่าตาราง menu_items มีอยู่หรือไม่
        if (! Schema::hasTable('menu_items')) {
            return [];
        }

        // ตรวจสอบว่ามีข้อมูลในตารางหรือไม่
        if (MenuItem::count() === 0) {
            return [];
        }

        // Cache key
        $roleId = $user && isset($user->role_id) ? $user->role_id : null;
        $cacheKey = "{$dashboardType}_{$roleId}";

        // ใช้ cache ถ้ามี
        if (isset(self::$menuCache[$cacheKey])) {
            return self::$menuCache[$cacheKey];
        }

        // ดึงเมนู top-level พร้อม children
        $query = MenuItem::forDashboard($dashboardType)
            ->active()
            ->visible()
            ->topLevel()
            ->with(['children' => function ($q) {
                $q->active()->visible()->orderBy('order');
            }])
            ->orderBy('order');

        $menuItems = $query->get();

        // แปลงเป็น array format เดิม
        $menus = $menuItems->map(function ($item) use ($roleId) {
            return $this->convertMenuItemToArray($item, $roleId);
        })->toArray();

        // กรองตาม Role Settings (ถ้ามี roleId)
        if ($roleId) {
            $menus = $this->filterByRoleSettings($menus, $roleId);
        }

        // Cache ผลลัพธ์
        self::$menuCache[$cacheKey] = $menus;

        return $menus;
    }

    /**
     * แปลง MenuItem model เป็น array format
     */
    protected function convertMenuItemToArray(MenuItem $item, ?int $roleId = null): array
    {
        $menu = [
            'id' => $item->menu_key,
            'label' => $item->label,
            'icon' => $item->icon,
            'route' => $item->route,
            'url' => $item->url,
            'order' => $item->order,
            'permissions' => $item->permissions ?? [],
            'condition' => $item->condition,
            'hide_if_kyc_verified' => $item->hide_if_kyc_verified,
            'badge' => $item->badge,
            'badge_color' => $item->badge_color,
            'description' => $item->description,
            '_db_id' => $item->id, // เก็บ ID จาก database ไว้ใช้ภายใน
        ];

        // เพิ่ม role setting ถ้ามี
        if ($roleId) {
            $roleSetting = $item->getSettingForRole($roleId);
            if ($roleSetting) {
                $menu['_role_visible'] = $roleSetting->is_visible;
                $menu['_role_enabled'] = $roleSetting->is_enabled;
                // ใช้ custom_order ถ้ามี
                if ($roleSetting->custom_order !== null) {
                    $menu['order'] = $roleSetting->custom_order;
                }
            }
        }

        // เพิ่ม submenu
        if ($item->children && $item->children->count() > 0) {
            $menu['submenu'] = $item->children->map(function ($child) use ($roleId) {
                // ข้าม divider
                if ($child->is_divider) {
                    return [
                        'id' => $child->menu_key,
                        'label' => '---',
                        'route' => null,
                    ];
                }

                return $this->convertMenuItemToArray($child, $roleId);
            })->toArray();
        }

        return $menu;
    }

    /**
     * กรองเมนูตาม Role Settings
     */
    protected function filterByRoleSettings(array $menus, int $roleId): array
    {
        return array_filter($menus, function ($menu) use ($roleId) {
            // ตรวจสอบ role visibility
            if (isset($menu['_role_visible']) && ! $menu['_role_visible']) {
                return false;
            }
            if (isset($menu['_role_enabled']) && ! $menu['_role_enabled']) {
                return false;
            }

            // กรอง submenu ด้วย
            if (! empty($menu['submenu'])) {
                $menu['submenu'] = $this->filterByRoleSettings($menu['submenu'], $roleId);
            }

            return true;
        });
    }

    /**
     * Clear menu cache
     */
    public static function clearCache(): void
    {
        self::$menuCache = [];
    }

    /**
     * รวมเมนูจาก config กับ providers
     *
     * @param  array  $configMenus  เมนูจาก config
     * @param  array  $providerMenus  เมนูจาก providers
     * @return array เมนูที่รวมแล้ว
     */
    protected function mergeMenus(array $configMenus, array $providerMenus): array
    {
        // ใช้ id เป็น key เพื่อป้องกันเมนูซ้ำ
        $merged = [];

        // เพิ่มเมนูจาก config
        foreach ($configMenus as $menu) {
            $id = $menu['id'] ?? uniqid('menu_');
            $merged[$id] = $menu;
        }

        // เพิ่มเมนูจาก providers (ถ้า id ซ้ำจะ override)
        foreach ($providerMenus as $menu) {
            $id = $menu['id'] ?? uniqid('menu_');
            $merged[$id] = $menu;
        }

        return array_values($merged);
    }

    /**
     * กรองเมนูตาม permissions ของ user
     *
     * @param  array  $menus  รายการเมนู
     * @param  mixed  $user  User instance
     * @return array เมนูที่กรองแล้ว
     */
    public function filterByPermissions(array $menus, $user): array
    {
        $filteredMenus = [];

        foreach ($menus as $menu) {
            // ตรวจสอบ permissions
            if (! empty($menu['permissions']) && is_array($menu['permissions'])) {
                $hasAllPermissions = true;
                foreach ($menu['permissions'] as $permission) {
                    if (! $this->userHasPermission($user, $permission)) {
                        $hasAllPermissions = false;
                        break;
                    }
                }
                if (! $hasAllPermissions) {
                    continue; // ข้ามเมนูนี้
                }
            }

            // ตรวจสอบเงื่อนไขพิเศษ: condition
            // รองรับ conditions ต่างๆ เช่น hasProviderAccess, hideIfProvider
            // ⚠️ Admin bypass: Admin เห็นเมนูทั้งหมดเสมอ (ไม่ถูกกรองโดย condition)
            if (! empty($menu['condition']) && $user) {
                // Admin เห็นเมนูทั้งหมด ไม่ต้องเช็ค condition
                if (! $this->userHasAdminAccess($user)) {
                    if (! $this->checkCondition($menu['condition'], $user)) {
                        continue; // ข้ามเมนูนี้
                    }
                }
            }

            // ตรวจสอบเงื่อนไขพิเศษ: hide_if_kyc_verified
            // ซ่อนเมนูเมื่อ user ยืนยัน KYC แล้ว
            if (! empty($menu['hide_if_kyc_verified']) && $user) {
                if (method_exists($user, 'isKycVerified') && $user->isKycVerified()) {
                    continue; // ข้ามเมนูนี้
                }
            }

            // กรอง submenu ด้วย (แก้ไข: อัพเดท submenu ใน $menu ก่อน push)
            if (! empty($menu['submenu'])) {
                $menu['submenu'] = $this->filterByPermissions($menu['submenu'], $user);

                // ถ้า submenu ว่างหมดให้ซ่อนเมนูหลักด้วย
                if (empty($menu['submenu'])) {
                    continue; // ข้ามเมนูนี้
                }
            }

            // เพิ่มเมนูที่ผ่านการกรองเข้า array
            $filteredMenus[] = $menu;
        }

        return $filteredMenus;
    }

    /**
     * ตรวจสอบเงื่อนไขพิเศษของเมนู
     *
     * @param  string  $condition  ชื่อเงื่อนไข
     * @param  mixed  $user  User instance
     * @return bool true ถ้าผ่านเงื่อนไข
     */
    protected function checkCondition(string $condition, $user): bool
    {
        switch ($condition) {
            case 'hasProviderAccess':
                // ตรวจสอบว่า user เป็น service provider หรือไม่
                // แสดงเมนูเฉพาะเมื่อเป็น approved provider
                return $this->userHasProviderAccess($user);

            case 'hideIfProvider':
                // ซ่อนเมนูเมื่อเป็น provider แล้ว (เช่น เมนูสมัครเป็นผู้ให้บริการ)
                // แสดงเมนูเฉพาะเมื่อยังไม่เป็น provider
                return ! $this->userHasProviderAccess($user);

            case 'hasSellerAccess':
                // ตรวจสอบว่า user เป็น seller หรือไม่
                return $this->userHasSellerAccess($user);

            case 'hasAdminAccess':
                // ตรวจสอบว่า user เป็น admin หรือไม่
                return $this->userHasAdminAccess($user);

            default:
                // ถ้าไม่รู้จัก condition ให้ผ่าน
                return true;
        }
    }

    /**
     * ตรวจสอบว่า user เป็น Service Provider หรือไม่
     *
     * @param  mixed  $user  User instance
     */
    protected function userHasProviderAccess($user): bool
    {
        if (! $user) {
            return false;
        }

        // ใช้ method isApprovedProvider() ที่สร้างไว้ใน User model
        if (method_exists($user, 'isApprovedProvider')) {
            return $user->isApprovedProvider();
        }

        // Fallback: ตรวจสอบ relation serviceProvider
        if (method_exists($user, 'serviceProvider')) {
            $provider = $user->serviceProvider;

            // ต้องมี provider และสถานะต้องเป็น approved
            return $provider && $provider->status === 'approved';
        }

        // ตรวจสอบ is_provider flag
        if (isset($user->is_provider)) {
            return (bool) $user->is_provider;
        }

        // ตรวจสอบ role
        if (method_exists($user, 'hasRole')) {
            return $user->hasRole('provider') || $user->hasRole('service_provider');
        }

        return false;
    }

    /**
     * ตรวจสอบว่า user เป็น Seller หรือไม่
     *
     * @param  mixed  $user  User instance
     */
    protected function userHasSellerAccess($user): bool
    {
        if (! $user) {
            return false;
        }

        // ตรวจสอบ is_seller flag
        if (isset($user->is_seller)) {
            return (bool) $user->is_seller;
        }

        // ตรวจสอบ role
        if (method_exists($user, 'hasRole')) {
            return $user->hasRole('seller');
        }

        return false;
    }

    /**
     * ตรวจสอบว่า user เป็น Admin หรือไม่
     *
     * @param  mixed  $user  User instance
     */
    protected function userHasAdminAccess($user): bool
    {
        if (! $user) {
            return false;
        }

        // ตรวจสอบ is_admin flag
        if (isset($user->is_admin)) {
            return (bool) $user->is_admin;
        }

        // ตรวจสอบ role
        if (method_exists($user, 'hasRole')) {
            return $user->hasRole('admin') || $user->hasRole('super_admin');
        }

        return false;
    }

    /**
     * ตรวจสอบว่า user มี permission หรือไม่
     *
     * @param  mixed  $user  User instance
     * @param  string  $permission  Permission name
     */
    protected function userHasPermission($user, string $permission): bool
    {
        // ถ้าไม่มี user แสดงว่าไม่มี permission
        if (! $user) {
            return false;
        }

        // ถ้า permission เป็น string ว่าง แสดงว่าทุกคนดูได้
        if (empty($permission)) {
            return true;
        }

        // ตรวจสอบ permission (รองรับหลายรูปแบบ)
        if (method_exists($user, 'hasPermission')) {
            return $user->hasPermission($permission);
        }

        if (method_exists($user, 'can')) {
            return $user->can($permission);
        }

        // Default: อนุญาตทั้งหมด
        return true;
    }

    /**
     * แปลง route names เป็น URLs
     *
     * @param  array  $menus  รายการเมนู
     * @return array เมนูที่แปลง routes แล้ว
     */
    protected function resolveRoutes(array $menus): array
    {
        return array_map(function ($menu) {
            // แปลง route หลัก
            if (! empty($menu['route'])) {
                $menu['url'] = $this->routeToUrl($menu['route']);
            } elseif (! isset($menu['url'])) {
                $menu['url'] = '#';
            }

            // แปลง route ใน submenu
            if (! empty($menu['submenu'])) {
                $menu['submenu'] = $this->resolveRoutes($menu['submenu']);
            }

            return $menu;
        }, $menus);
    }

    /**
     * แปลง route name เป็น URL
     *
     * @param  string  $routeName  Route name
     * @param  string  $default  URL เริ่มต้นถ้า route ไม่มี
     * @return string URL
     */
    protected function routeToUrl(string $routeName, string $default = '#'): string
    {
        try {
            // ลองใช้ route() helper โดยตรง
            if (Route::has($routeName)) {
                return route($routeName);
            }
        } catch (\Exception $e) {
            // Log error สำหรับ debugging (optional)
            // \Log::warning("Route error: {$routeName} - " . $e->getMessage());
        }

        // Fallback: แปลง route name เป็น path
        // เช่น user.services.index -> /user/services
        // เช่น user.bookings.index -> /user/bookings
        $path = str_replace('.index', '', $routeName);
        $path = str_replace('.', '/', $path);

        // ตรวจสอบว่า path ไม่ว่างเปล่า
        if (! empty($path) && $path !== $routeName) {
            return '/'.$path;
        }

        return $default;
    }

    /**
     * เรียงลำดับเมนูตาม order
     *
     * @param  array  $menus  รายการเมนู
     * @return array เมนูที่เรียงแล้ว
     */
    protected function sortMenus(array $menus): array
    {
        usort($menus, function ($a, $b) {
            $orderA = $a['order'] ?? 9999;
            $orderB = $b['order'] ?? 9999;

            return $orderA <=> $orderB;
        });

        // เรียง submenu ด้วย
        return array_map(function ($menu) {
            if (! empty($menu['submenu'])) {
                $menu['submenu'] = $this->sortMenus($menu['submenu']);
            }

            return $menu;
        }, $menus);
    }

    /**
     * ค้นหาเมนูจาก keyword
     *
     * @param  array  $menus  รายการเมนู
     * @param  string  $keyword  คำค้นหา
     * @return array เมนูที่ค้นพบ
     */
    public function search(array $menus, string $keyword): array
    {
        $keyword = strtolower($keyword);
        $results = [];

        foreach ($menus as $menu) {
            $label = strtolower($menu['label'] ?? '');

            // ค้นหาในเมนูหลัก
            if (str_contains($label, $keyword)) {
                $results[] = $menu;

                continue;
            }

            // ค้นหาใน submenu
            if (! empty($menu['submenu'])) {
                $subResults = $this->search($menu['submenu'], $keyword);
                if (! empty($subResults)) {
                    $menu['submenu'] = $subResults;
                    $results[] = $menu;
                }
            }
        }

        return $results;
    }

    /**
     * ดึง active menu จาก current route
     *
     * @param  array  $menus  รายการเมนู
     * @param  string  $currentRoute  Route ปัจจุบัน
     * @return array|null Menu ที่ active หรือ null
     */
    public function getActiveMenu(array $menus, string $currentRoute): ?array
    {
        foreach ($menus as $menu) {
            // ตรวจสอบเมนูหลัก
            if (isset($menu['route']) && $menu['route'] === $currentRoute) {
                return $menu;
            }

            // ตรวจสอบ submenu
            if (! empty($menu['submenu'])) {
                $activeSubmenu = $this->getActiveMenu($menu['submenu'], $currentRoute);
                if ($activeSubmenu) {
                    return $menu;
                }
            }
        }

        return null;
    }

    /**
     * นับจำนวนเมนูทั้งหมด (รวม submenu)
     *
     * @param  array  $menus  รายการเมนู
     * @return int จำนวนเมนู
     */
    public function count(array $menus): int
    {
        $count = count($menus);

        foreach ($menus as $menu) {
            if (! empty($menu['submenu'])) {
                $count += $this->count($menu['submenu']);
            }
        }

        return $count;
    }

    /**
     * แปลงเมนูเป็น flat array (ไม่มี submenu)
     *
     * @param  array  $menus  รายการเมนู
     * @return array เมนู flat array
     */
    public function flatten(array $menus): array
    {
        $flat = [];

        foreach ($menus as $menu) {
            // เพิ่มเมนูหลัก
            $flatMenu = $menu;
            unset($flatMenu['submenu']);
            $flat[] = $flatMenu;

            // เพิ่ม submenu
            if (! empty($menu['submenu'])) {
                $flat = array_merge($flat, $this->flatten($menu['submenu']));
            }
        }

        return $flat;
    }

    /**
     * ดึง breadcrumb สำหรับเมนูที่ active
     *
     * @param  array  $menus  รายการเมนู
     * @param  string  $currentRoute  Route ปัจจุบัน
     * @param  array  $breadcrumb  Breadcrumb ปัจจุบัน (ใช้ internal)
     * @return array Breadcrumb
     */
    public function getBreadcrumb(array $menus, string $currentRoute, array $breadcrumb = []): array
    {
        foreach ($menus as $menu) {
            $currentBreadcrumb = array_merge($breadcrumb, [$menu]);

            // พบเมนูที่ active
            if (isset($menu['route']) && $menu['route'] === $currentRoute) {
                return $currentBreadcrumb;
            }

            // ค้นหาใน submenu
            if (! empty($menu['submenu'])) {
                $result = $this->getBreadcrumb($menu['submenu'], $currentRoute, $currentBreadcrumb);
                if (! empty($result)) {
                    return $result;
                }
            }
        }

        return [];
    }
}
