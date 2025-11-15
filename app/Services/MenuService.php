<?php

namespace App\Services;

use Illuminate\Support\Facades\Route;

/**
 * Menu Service
 *
 * จัดการเมนูทั้งหมดในระบบ
 * - ดึงเมนูจาก config
 * - รวมเมนูจาก Feature Providers
 * - กรองตาม permissions
 * - แปลง route names เป็น URLs
 *
 * @version 3.0.0
 * @since 2025-11-15
 */
class MenuService
{
    /**
     * MenuRegistrar instance สำหรับรวบรวมเมนูจาก providers
     */
    protected $registrar;

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
     * @param string $role Role ของผู้ใช้ (admin, seller, user)
     * @param mixed $user User instance (optional) สำหรับกรอง permissions
     * @return array เมนูทั้งหมดที่กรองแล้ว
     *
     * @example
     * $menus = $menuService->getMenuForRole('admin', auth()->user());
     */
    public function getMenuForRole(string $role, $user = null): array
    {
        // 1. ดึงเมนูจาก config
        $configMenus = config("menus.{$role}", []);

        // 2. ดึงเมนูจาก Feature Providers (ถ้ามี)
        $providerMenus = [];
        if ($this->registrar) {
            $providerMenus = $this->registrar->getMenusForRole($role);
        }

        // 3. รวมเมนูทั้งหมด
        $allMenus = $this->mergeMenus($configMenus, $providerMenus);

        // 4. กรองตาม permissions
        if ($user) {
            $allMenus = $this->filterByPermissions($allMenus, $user);
        }

        // 5. แปลง routes เป็น URLs
        $allMenus = $this->resolveRoutes($allMenus);

        // 6. เรียงลำดับตาม order
        $allMenus = $this->sortMenus($allMenus);

        return $allMenus;
    }

    /**
     * รวมเมนูจาก config กับ providers
     *
     * @param array $configMenus เมนูจาก config
     * @param array $providerMenus เมนูจาก providers
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
     * @param array $menus รายการเมนู
     * @param mixed $user User instance
     * @return array เมนูที่กรองแล้ว
     */
    public function filterByPermissions(array $menus, $user): array
    {
        return array_filter($menus, function ($menu) use ($user) {
            // ตรวจสอบ permissions
            if (!empty($menu['permissions']) && is_array($menu['permissions'])) {
                foreach ($menu['permissions'] as $permission) {
                    if (!$this->userHasPermission($user, $permission)) {
                        return false;
                    }
                }
            }

            // กรอง submenu ด้วย
            if (!empty($menu['submenu'])) {
                $menu['submenu'] = $this->filterByPermissions($menu['submenu'], $user);

                // ถ้า submenu ว่างหมดให้ซ่อนเมนูหลักด้วย
                if (empty($menu['submenu'])) {
                    return false;
                }
            }

            return true;
        });
    }

    /**
     * ตรวจสอบว่า user มี permission หรือไม่
     *
     * @param mixed $user User instance
     * @param string $permission Permission name
     * @return bool
     */
    protected function userHasPermission($user, string $permission): bool
    {
        // ถ้าไม่มี user แสดงว่าไม่มี permission
        if (!$user) {
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
     * @param array $menus รายการเมนู
     * @return array เมนูที่แปลง routes แล้ว
     */
    protected function resolveRoutes(array $menus): array
    {
        return array_map(function ($menu) {
            // แปลง route หลัก
            if (!empty($menu['route'])) {
                $menu['url'] = $this->routeToUrl($menu['route']);
            } elseif (!isset($menu['url'])) {
                $menu['url'] = '#';
            }

            // แปลง route ใน submenu
            if (!empty($menu['submenu'])) {
                $menu['submenu'] = $this->resolveRoutes($menu['submenu']);
            }

            return $menu;
        }, $menus);
    }

    /**
     * แปลง route name เป็น URL
     *
     * @param string $routeName Route name
     * @param string $default URL เริ่มต้นถ้า route ไม่มี
     * @return string URL
     */
    protected function routeToUrl(string $routeName, string $default = '#'): string
    {
        try {
            if (Route::has($routeName)) {
                return route($routeName);
            }
        } catch (\Exception $e) {
            // ถ้า route ไม่พบ ลอง fallback เป็น path
            $path = str_replace('.index', '', $routeName);
            $path = str_replace('.', '/', $path);
            return '/' . $path;
        }

        return $default;
    }

    /**
     * เรียงลำดับเมนูตาม order
     *
     * @param array $menus รายการเมนู
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
            if (!empty($menu['submenu'])) {
                $menu['submenu'] = $this->sortMenus($menu['submenu']);
            }
            return $menu;
        }, $menus);
    }

    /**
     * ค้นหาเมนูจาก keyword
     *
     * @param array $menus รายการเมนู
     * @param string $keyword คำค้นหา
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
            if (!empty($menu['submenu'])) {
                $subResults = $this->search($menu['submenu'], $keyword);
                if (!empty($subResults)) {
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
     * @param array $menus รายการเมนู
     * @param string $currentRoute Route ปัจจุบัน
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
            if (!empty($menu['submenu'])) {
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
     * @param array $menus รายการเมนู
     * @return int จำนวนเมนู
     */
    public function count(array $menus): int
    {
        $count = count($menus);

        foreach ($menus as $menu) {
            if (!empty($menu['submenu'])) {
                $count += $this->count($menu['submenu']);
            }
        }

        return $count;
    }

    /**
     * แปลงเมนูเป็น flat array (ไม่มี submenu)
     *
     * @param array $menus รายการเมนู
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
            if (!empty($menu['submenu'])) {
                $flat = array_merge($flat, $this->flatten($menu['submenu']));
            }
        }

        return $flat;
    }

    /**
     * ดึง breadcrumb สำหรับเมนูที่ active
     *
     * @param array $menus รายการเมนู
     * @param string $currentRoute Route ปัจจุบัน
     * @param array $breadcrumb Breadcrumb ปัจจุบัน (ใช้ internal)
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
            if (!empty($menu['submenu'])) {
                $result = $this->getBreadcrumb($menu['submenu'], $currentRoute, $currentBreadcrumb);
                if (!empty($result)) {
                    return $result;
                }
            }
        }

        return [];
    }
}
