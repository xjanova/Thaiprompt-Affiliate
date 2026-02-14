<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * อัปเดตไอคอนหมวดหมู่บริการให้ใช้ SVG icons ที่สวยงาม
 *
 * เปลี่ยนจาก Font Awesome icon class เป็น SVG file path
 * ไอคอน SVG เก็บไว้ที่ public/icons/services/
 */
return new class extends Migration
{
    /**
     * รันการ migration
     */
    public function up(): void
    {
        // รายการ mapping slug → SVG path
        $iconMappings = [
            'massage-spa' => '/icons/services/massage-spa.svg',
            'hair-salon' => '/icons/services/hair-salon.svg',
            'beauty' => '/icons/services/beauty.svg',
            'air-conditioner' => '/icons/services/air-conditioner.svg',
            'electrician' => '/icons/services/electrician.svg',
            'plumber' => '/icons/services/plumber.svg',
            'appliance-repair' => '/icons/services/appliance-repair.svg',
            'handyman' => '/icons/services/handyman.svg',
            'house-cleaning' => '/icons/services/house-cleaning.svg',
            'car-wash' => '/icons/services/car-wash.svg',
            'laundry' => '/icons/services/laundry.svg',
            'food-delivery' => '/icons/services/food-delivery.svg',
            'parcel-delivery' => '/icons/services/parcel-delivery.svg',
            'moving' => '/icons/services/moving.svg',
            'ride-hailing' => '/icons/services/ride-hailing.svg',
            'tutoring' => '/icons/services/tutoring.svg',
            'fitness-training' => '/icons/services/fitness-training.svg',
            'pet-services' => '/icons/services/pet-services.svg',
            'nursing-care' => '/icons/services/nursing-care.svg',
            'photography' => '/icons/services/photography.svg',
            'gardening' => '/icons/services/gardening.svg',
            'others' => '/icons/services/others.svg',
        ];

        // อัปเดต icon และ image ในตาราง service_categories
        foreach ($iconMappings as $slug => $svgPath) {
            DB::table('service_categories')
                ->where('slug', $slug)
                ->update([
                    'icon' => $svgPath,
                    'image' => $svgPath,
                ]);
        }
    }

    /**
     * ย้อนกลับการ migration
     */
    public function down(): void
    {
        // รายการ mapping slug → Font Awesome icon (ย้อนกลับ)
        $iconMappings = [
            'massage-spa' => 'fa-solid fa-spa',
            'hair-salon' => 'fa-solid fa-scissors',
            'beauty' => 'fa-solid fa-wand-magic-sparkles',
            'air-conditioner' => 'fa-solid fa-snowflake',
            'electrician' => 'fa-solid fa-bolt',
            'plumber' => 'fa-solid fa-faucet',
            'appliance-repair' => 'fa-solid fa-tv',
            'handyman' => 'fa-solid fa-screwdriver-wrench',
            'house-cleaning' => 'fa-solid fa-broom',
            'car-wash' => 'fa-solid fa-car',
            'laundry' => 'fa-solid fa-shirt',
            'food-delivery' => 'fa-solid fa-utensils',
            'parcel-delivery' => 'fa-solid fa-box',
            'moving' => 'fa-solid fa-truck',
            'ride-hailing' => 'fa-solid fa-motorcycle',
            'tutoring' => 'fa-solid fa-chalkboard-user',
            'fitness-training' => 'fa-solid fa-dumbbell',
            'pet-services' => 'fa-solid fa-paw',
            'nursing-care' => 'fa-solid fa-user-nurse',
            'photography' => 'fa-solid fa-camera',
            'gardening' => 'fa-solid fa-leaf',
            'others' => 'fa-solid fa-ellipsis',
        ];

        // ย้อนกลับ icon ในตาราง service_categories
        foreach ($iconMappings as $slug => $faIcon) {
            DB::table('service_categories')
                ->where('slug', $slug)
                ->update([
                    'icon' => $faIcon,
                    'image' => null,
                ]);
        }
    }
};
