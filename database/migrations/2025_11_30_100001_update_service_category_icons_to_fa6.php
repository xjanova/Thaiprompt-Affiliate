<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * อัปเดตไอคอนหมวดหมู่บริการให้ใช้ Font Awesome 6 syntax
 *
 * ไอคอนบางตัวเปลี่ยนชื่อใน Font Awesome 6:
 * - fas fa-cut → fa-solid fa-scissors
 * - fas fa-magic → fa-solid fa-wand-magic-sparkles
 * - fas fa-tools → fa-solid fa-screwdriver-wrench
 * - fas fa-tshirt → fa-solid fa-shirt
 * - fas fa-chalkboard-teacher → fa-solid fa-chalkboard-user
 * - fas fa-ellipsis-h → fa-solid fa-ellipsis
 *
 * และเปลี่ยน fas → fa-solid ทั้งหมดเพื่อใช้ syntax ที่ถูกต้อง
 */
return new class extends Migration
{
    /**
     * รันการ migration
     *
     * @return void
     */
    public function up(): void
    {
        // รายการ mapping ไอคอนเก่า → ใหม่
        $iconMappings = [
            // ไอคอนที่เปลี่ยนชื่อใน FA6
            'fas fa-cut' => 'fa-solid fa-scissors',
            'fas fa-magic' => 'fa-solid fa-wand-magic-sparkles',
            'fas fa-tools' => 'fa-solid fa-screwdriver-wrench',
            'fas fa-tshirt' => 'fa-solid fa-shirt',
            'fas fa-chalkboard-teacher' => 'fa-solid fa-chalkboard-user',
            'fas fa-ellipsis-h' => 'fa-solid fa-ellipsis',

            // ไอคอนที่เปลี่ยน syntax fas → fa-solid
            'fas fa-spa' => 'fa-solid fa-spa',
            'fas fa-snowflake' => 'fa-solid fa-snowflake',
            'fas fa-bolt' => 'fa-solid fa-bolt',
            'fas fa-faucet' => 'fa-solid fa-faucet',
            'fas fa-tv' => 'fa-solid fa-tv',
            'fas fa-broom' => 'fa-solid fa-broom',
            'fas fa-car' => 'fa-solid fa-car',
            'fas fa-utensils' => 'fa-solid fa-utensils',
            'fas fa-box' => 'fa-solid fa-box',
            'fas fa-truck' => 'fa-solid fa-truck',
            'fas fa-motorcycle' => 'fa-solid fa-motorcycle',
            'fas fa-dumbbell' => 'fa-solid fa-dumbbell',
            'fas fa-paw' => 'fa-solid fa-paw',
            'fas fa-user-nurse' => 'fa-solid fa-user-nurse',
            'fas fa-camera' => 'fa-solid fa-camera',
            'fas fa-leaf' => 'fa-solid fa-leaf',
        ];

        // อัปเดตไอคอนในตาราง service_categories
        foreach ($iconMappings as $oldIcon => $newIcon) {
            DB::table('service_categories')
                ->where('icon', $oldIcon)
                ->update(['icon' => $newIcon]);
        }
    }

    /**
     * ย้อนกลับการ migration
     *
     * @return void
     */
    public function down(): void
    {
        // รายการ mapping ไอคอนใหม่ → เก่า (ย้อนกลับ)
        $iconMappings = [
            'fa-solid fa-scissors' => 'fas fa-cut',
            'fa-solid fa-wand-magic-sparkles' => 'fas fa-magic',
            'fa-solid fa-screwdriver-wrench' => 'fas fa-tools',
            'fa-solid fa-shirt' => 'fas fa-tshirt',
            'fa-solid fa-chalkboard-user' => 'fas fa-chalkboard-teacher',
            'fa-solid fa-ellipsis' => 'fas fa-ellipsis-h',
            'fa-solid fa-spa' => 'fas fa-spa',
            'fa-solid fa-snowflake' => 'fas fa-snowflake',
            'fa-solid fa-bolt' => 'fas fa-bolt',
            'fa-solid fa-faucet' => 'fas fa-faucet',
            'fa-solid fa-tv' => 'fas fa-tv',
            'fa-solid fa-broom' => 'fas fa-broom',
            'fa-solid fa-car' => 'fas fa-car',
            'fa-solid fa-utensils' => 'fas fa-utensils',
            'fa-solid fa-box' => 'fas fa-box',
            'fa-solid fa-truck' => 'fas fa-truck',
            'fa-solid fa-motorcycle' => 'fas fa-motorcycle',
            'fa-solid fa-dumbbell' => 'fas fa-dumbbell',
            'fa-solid fa-paw' => 'fas fa-paw',
            'fa-solid fa-user-nurse' => 'fas fa-user-nurse',
            'fa-solid fa-camera' => 'fas fa-camera',
            'fa-solid fa-leaf' => 'fas fa-leaf',
        ];

        // ย้อนกลับไอคอนในตาราง service_categories
        foreach ($iconMappings as $newIcon => $oldIcon) {
            DB::table('service_categories')
                ->where('icon', $newIcon)
                ->update(['icon' => $oldIcon]);
        }
    }
};
