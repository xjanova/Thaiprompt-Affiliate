<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * MenuItemSeeder
 *
 * นำเข้าเมนูทั้งหมดจาก config/menus.php เข้าสู่ database
 * ช่วยให้แอดมินสามารถจัดการเมนูผ่าน UI ได้
 *
 * @version 1.0.0
 * @since 2025-12-04
 */
class MenuItemSeeder extends Seeder
{
    /**
     * ลำดับสำหรับเมนูแต่ละประเภท
     *
     * @var array<string, float>
     */
    protected array $orderCounters = [];

    /**
     * Run the database seeds.
     *
     * นำเข้าเมนูจาก config/menus.php สำหรับทุก dashboard type:
     * - admin
     * - seller
     * - user
     */
    public function run(): void
    {
        $this->command->info('🍔 กำลังนำเข้าเมนูจาก config/menus.php...');

        // ตรวจสอบว่ามีข้อมูลอยู่แล้วหรือไม่
        if (MenuItem::count() > 0) {
            $this->command->warn('⚠️ ตาราง menu_items มีข้อมูลอยู่แล้ว กำลังข้าม...');
            $this->command->info('   หากต้องการนำเข้าใหม่ กรุณาลบข้อมูลเก่าก่อน');
            return;
        }

        DB::transaction(function () {
            // นำเข้าเมนูสำหรับแต่ละ dashboard type
            $dashboardTypes = ['admin', 'seller', 'user'];

            foreach ($dashboardTypes as $type) {
                $menus = config("menus.{$type}", []);

                if (empty($menus)) {
                    $this->command->warn("⚠️ ไม่พบเมนูสำหรับ {$type} ใน config/menus.php");
                    continue;
                }

                $this->command->info("📁 กำลังนำเข้าเมนู {$type}...");
                $this->orderCounters[$type] = 0;

                $this->importMenus($menus, $type, null);

                $count = MenuItem::where('dashboard_type', $type)->count();
                $this->command->info("   ✅ นำเข้า {$count} รายการสำเร็จ");
            }
        });

        $totalCount = MenuItem::count();
        $this->command->info('');
        $this->command->info("✨ นำเข้าเมนูทั้งหมด {$totalCount} รายการสำเร็จ!");
    }

    /**
     * นำเข้าเมนูและ submenu แบบ recursive
     *
     * @param array $menus รายการเมนู
     * @param string $dashboardType ประเภท dashboard
     * @param int|null $parentId Parent menu ID
     * @param string $keyPrefix Prefix สำหรับ menu_key
     */
    protected function importMenus(array $menus, string $dashboardType, ?int $parentId = null, string $keyPrefix = ''): void
    {
        foreach ($menus as $menu) {
            // ข้าม divider ที่ไม่มี id
            $menuId = $menu['id'] ?? null;
            $label = $menu['label'] ?? '';

            // ตรวจสอบว่าเป็น divider หรือไม่
            $isDivider = $label === '---' || empty(trim($label));

            // สร้าง menu_key
            if ($isDivider) {
                $menuKey = $keyPrefix . $dashboardType . '.divider.' . uniqid();
            } else {
                $menuKey = $keyPrefix . $dashboardType . '.' . ($menuId ?? uniqid());
            }

            // ดึง order
            $order = $menu['order'] ?? $this->orderCounters[$dashboardType];
            $this->orderCounters[$dashboardType] = $order + 1;

            // สร้างเมนู
            $menuItem = MenuItem::create([
                'menu_key' => $menuKey,
                'dashboard_type' => $dashboardType,
                'parent_id' => $parentId,
                'label' => $label,
                'icon' => $menu['icon'] ?? null,
                'route' => $menu['route'] ?? null,
                'url' => $menu['url'] ?? null,
                'order' => $order,
                'is_active' => true,
                'is_visible' => true,
                'permissions' => $menu['permissions'] ?? [],
                'condition' => $menu['condition'] ?? null,
                'hide_if_kyc_verified' => $menu['hide_if_kyc_verified'] ?? false,
                'badge' => $menu['badge'] ?? null,
                'badge_color' => $menu['badge_color'] ?? null,
                'description' => $menu['description'] ?? null,
                'is_divider' => $isDivider,
            ]);

            // นำเข้า submenu ถ้ามี
            if (!empty($menu['submenu'])) {
                $this->importMenus(
                    $menu['submenu'],
                    $dashboardType,
                    $menuItem->id,
                    $menuKey . '.'
                );
            }
        }
    }
}
