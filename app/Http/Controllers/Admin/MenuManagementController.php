<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\MenuRoleSetting;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * MenuManagementController
 *
 * จัดการระบบเมนูสำหรับ Admin
 * - แสดงรายการเมนูตาม Dashboard Type
 * - เปิด/ปิดเมนูตาม Role
 * - จัดเรียงลำดับเมนู (Drag & Drop)
 * - แก้ไขข้อมูลเมนู
 *
 * @version 1.0.0
 * @since 2025-12-04
 */
class MenuManagementController extends Controller
{
    /**
     * แสดงหน้าจัดการเมนูหลัก
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        // ดึง dashboard type จาก query parameter (default: admin)
        $dashboardType = $request->get('dashboard', 'admin');

        // ดึงรายการ dashboard types ทั้งหมด
        $dashboardTypes = MenuItem::getDashboardTypes();

        // ดึง roles ทั้งหมด
        $roles = Role::orderBy('name')->get();

        // ดึง role ที่เลือก (default: role แรก)
        $selectedRoleId = $request->get('role_id', $roles->first()->id ?? null);
        $selectedRole = $selectedRoleId ? Role::find($selectedRoleId) : null;

        // ดึงเมนูตาม dashboard type (เฉพาะ top-level พร้อม children)
        $menus = MenuItem::forDashboard($dashboardType)
            ->topLevel()
            ->with(['children' => function ($query) {
                $query->orderBy('order');
            }])
            ->orderBy('order')
            ->get();

        // สถิติเมนู
        $stats = [
            'total' => MenuItem::forDashboard($dashboardType)->count(),
            'active' => MenuItem::forDashboard($dashboardType)->active()->count(),
            'hidden' => MenuItem::forDashboard($dashboardType)->where('is_visible', false)->count(),
        ];

        return view('admin.menu-management.index', [
            'pageTitle' => 'จัดการสิทธิ์เมนู',
            'dashboardType' => $dashboardType,
            'dashboardTypes' => $dashboardTypes,
            'roles' => $roles,
            'selectedRole' => $selectedRole,
            'selectedRoleId' => $selectedRoleId,
            'menus' => $menus,
            'stats' => $stats,
        ]);
    }

    /**
     * ดึงเมนูสำหรับ Dashboard Type และ Role (AJAX)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getMenus(Request $request): JsonResponse
    {
        $request->validate([
            'dashboard_type' => 'required|string|in:admin,seller,user,provider,instructor',
            'role_id' => 'nullable|exists:roles,id',
        ]);

        $dashboardType = $request->dashboard_type;
        $roleId = $request->role_id;

        // ดึงเมนูทั้งหมด
        $menus = MenuItem::forDashboard($dashboardType)
            ->topLevel()
            ->with(['children' => function ($query) {
                $query->orderBy('order');
            }, 'roleSettings' => function ($query) use ($roleId) {
                if ($roleId) {
                    $query->where('role_id', $roleId);
                }
            }])
            ->orderBy('order')
            ->get();

        // แปลงเป็น array พร้อมข้อมูล role settings
        $menuData = $menus->map(function ($menu) use ($roleId) {
            return $this->formatMenuWithRoleSettings($menu, $roleId);
        });

        return response()->json([
            'success' => true,
            'menus' => $menuData,
        ]);
    }

    /**
     * อัพเดทการตั้งค่าเมนูสำหรับ Role
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateRoleSetting(Request $request): JsonResponse
    {
        $request->validate([
            'menu_item_id' => 'required|exists:menu_items,id',
            'role_id' => 'required|exists:roles,id',
            'is_visible' => 'required|boolean',
            'is_enabled' => 'required|boolean',
        ]);

        try {
            $setting = MenuRoleSetting::updateOrCreateSetting(
                $request->menu_item_id,
                $request->role_id,
                [
                    'is_visible' => $request->is_visible,
                    'is_enabled' => $request->is_enabled,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'อัพเดทการตั้งค่าสำเร็จ',
                'setting' => $setting,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * อัพเดทลำดับเมนู (Drag & Drop)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateOrder(Request $request): JsonResponse
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:menu_items,id',
            'items.*.order' => 'required|numeric',
            'items.*.parent_id' => 'nullable|exists:menu_items,id',
        ]);

        try {
            DB::transaction(function () use ($request) {
                foreach ($request->items as $item) {
                    MenuItem::where('id', $item['id'])->update([
                        'order' => $item['order'],
                        'parent_id' => $item['parent_id'] ?? null,
                    ]);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'อัพเดทลำดับเมนูสำเร็จ',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * อัพเดทลำดับเมนูสำหรับ Role เฉพาะ
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateRoleOrder(Request $request): JsonResponse
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'items' => 'required|array',
            'items.*.menu_item_id' => 'required|exists:menu_items,id',
            'items.*.custom_order' => 'required|numeric',
        ]);

        try {
            DB::transaction(function () use ($request) {
                foreach ($request->items as $item) {
                    MenuRoleSetting::updateOrCreateSetting(
                        $item['menu_item_id'],
                        $request->role_id,
                        ['custom_order' => $item['custom_order']]
                    );
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'อัพเดทลำดับเมนูสำหรับ Role สำเร็จ',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle เปิด/ปิดเมนูทั้งระบบ
     *
     * @param Request $request
     * @param MenuItem $menuItem
     * @return JsonResponse
     */
    public function toggleActive(Request $request, MenuItem $menuItem): JsonResponse
    {
        try {
            $menuItem->update([
                'is_active' => !$menuItem->is_active,
            ]);

            return response()->json([
                'success' => true,
                'message' => $menuItem->is_active ? 'เปิดใช้งานเมนูแล้ว' : 'ปิดใช้งานเมนูแล้ว',
                'is_active' => $menuItem->is_active,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle แสดง/ซ่อนเมนูทั้งระบบ
     *
     * @param Request $request
     * @param MenuItem $menuItem
     * @return JsonResponse
     */
    public function toggleVisible(Request $request, MenuItem $menuItem): JsonResponse
    {
        try {
            $menuItem->update([
                'is_visible' => !$menuItem->is_visible,
            ]);

            return response()->json([
                'success' => true,
                'message' => $menuItem->is_visible ? 'แสดงเมนูแล้ว' : 'ซ่อนเมนูแล้ว',
                'is_visible' => $menuItem->is_visible,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk toggle เปิด/ปิดเมนูสำหรับ Role
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkToggleRole(Request $request): JsonResponse
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'menu_item_ids' => 'required|array',
            'menu_item_ids.*' => 'exists:menu_items,id',
            'action' => 'required|in:enable,disable,show,hide',
        ]);

        try {
            DB::transaction(function () use ($request) {
                foreach ($request->menu_item_ids as $menuItemId) {
                    $data = [];

                    switch ($request->action) {
                        case 'enable':
                            $data['is_enabled'] = true;
                            break;
                        case 'disable':
                            $data['is_enabled'] = false;
                            break;
                        case 'show':
                            $data['is_visible'] = true;
                            break;
                        case 'hide':
                            $data['is_visible'] = false;
                            break;
                    }

                    MenuRoleSetting::updateOrCreateSetting(
                        $menuItemId,
                        $request->role_id,
                        $data
                    );
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'อัพเดทการตั้งค่าสำเร็จ',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * แก้ไขข้อมูลเมนู
     *
     * @param Request $request
     * @param MenuItem $menuItem
     * @return JsonResponse
     */
    public function update(Request $request, MenuItem $menuItem): JsonResponse
    {
        $request->validate([
            'label' => 'required|string|max:255',
            'icon' => 'nullable|string|max:100',
            'route' => 'nullable|string|max:255',
            'url' => 'nullable|string|max:255',
            'badge' => 'nullable|string|max:50',
            'badge_color' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        try {
            $menuItem->update($request->only([
                'label',
                'icon',
                'route',
                'url',
                'badge',
                'badge_color',
                'description',
            ]));

            return response()->json([
                'success' => true,
                'message' => 'อัพเดทเมนูสำเร็จ',
                'menu' => $menuItem->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ซิงค์เมนูจาก config/menus.php ใหม่
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function syncFromConfig(Request $request): JsonResponse
    {
        try {
            // ลบเมนูเก่าทั้งหมด
            MenuItem::truncate();

            // เรียก Seeder เพื่อนำเข้าใหม่
            $seeder = new \Database\Seeders\MenuItemSeeder();
            $seeder->setCommand(new \Symfony\Component\Console\Output\NullOutput());
            $seeder->run();

            return response()->json([
                'success' => true,
                'message' => 'ซิงค์เมนูจาก config สำเร็จ',
                'count' => MenuItem::count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * รีเซ็ตการตั้งค่า Role สำหรับเมนู
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function resetRoleSettings(Request $request): JsonResponse
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'dashboard_type' => 'nullable|string|in:admin,seller,user,provider,instructor',
        ]);

        try {
            $query = MenuRoleSetting::where('role_id', $request->role_id);

            // ถ้าระบุ dashboard_type ให้รีเซ็ตเฉพาะ type นั้น
            if ($request->dashboard_type) {
                $menuIds = MenuItem::forDashboard($request->dashboard_type)->pluck('id');
                $query->whereIn('menu_item_id', $menuIds);
            }

            $count = $query->delete();

            return response()->json([
                'success' => true,
                'message' => "รีเซ็ตการตั้งค่าสำเร็จ (ลบ {$count} รายการ)",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Format เมนูพร้อม role settings
     *
     * @param MenuItem $menu
     * @param int|null $roleId
     * @return array
     */
    protected function formatMenuWithRoleSettings(MenuItem $menu, ?int $roleId): array
    {
        $setting = $roleId ? $menu->roleSettings->first() : null;

        $data = [
            'id' => $menu->id,
            'menu_key' => $menu->menu_key,
            'label' => $menu->label,
            'icon' => $menu->icon,
            'route' => $menu->route,
            'url' => $menu->getResolvedUrl(),
            'order' => $menu->order,
            'is_active' => $menu->is_active,
            'is_visible' => $menu->is_visible,
            'is_divider' => $menu->is_divider,
            'badge' => $menu->badge,
            'badge_color' => $menu->badge_color,
            'description' => $menu->description,
            'has_children' => $menu->children->count() > 0,
            'role_setting' => $setting ? [
                'is_visible' => $setting->is_visible,
                'is_enabled' => $setting->is_enabled,
                'custom_order' => $setting->custom_order,
            ] : null,
            'children' => [],
        ];

        // Format children
        if ($menu->children->count() > 0) {
            $data['children'] = $menu->children->map(function ($child) use ($roleId) {
                return $this->formatMenuWithRoleSettings($child, $roleId);
            })->toArray();
        }

        return $data;
    }
}
