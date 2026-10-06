<?php

namespace App\Http\Controllers\Api\Admin\Fortune;

use App\Http\Controllers\Admin\FortuneBillsController as WebFortuneBillsController;
use App\Http\Controllers\Controller;
use App\Models\FortuneCategory;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use Illuminate\Http\JsonResponse;

/**
 * Admin Mobile API: บริการดูดวง (อ่านอย่างเดียว) — แอปไทยพร้อม แอดมิน
 *
 * ไม่มีทางเขียน: หน้าเว็บไม่มีปุ่มเปิด/ปิดแพคเกจเดี่ยว ๆ (เปิดปิดผ่านฟอร์มตั้งค่าแม่หมอทั้งก้อน
 * และหมวดคำถามแก้ผ่านฟอร์ม resource) จึงไม่สร้างทางเขียนใหม่ที่หน้าเว็บไม่มี
 */
class FortuneServicesController extends Controller
{
    /**
     * GET /api/admin/fortune/services
     */
    public function index(): JsonResponse
    {
        $settings = FortuneTellingSetting::getSettings();
        $labels = WebFortuneBillsController::PACKAGES;

        $services = [
            [
                'id' => 'deep',
                'reading_type' => FortuneReading::READING_TYPE_DEEP,
                'name' => $labels['deep'][0],
                'price_thb' => round((float) ($settings->deep_reading_price ?? 39), 2),
                'is_active' => $settings->isDeepReadingEnabled(),
            ],
            [
                'id' => 'celtic',
                'reading_type' => FortuneReading::READING_TYPE_CELTIC_CROSS,
                'name' => $labels['celtic'][0],
                'price_thb' => round((float) ($settings->celtic_cross_price ?? 99), 2),
                'is_active' => (bool) ($settings->enable_celtic_cross ?? false),
            ],
            [
                'id' => 'free_card',
                'reading_type' => FortuneReading::READING_TYPE_FREE_CARD,
                'name' => $labels['free_card'][0],
                'price_thb' => 0.0,
                'is_active' => $settings->isFreeReadingEnabled(),
            ],
        ];

        try {
            $categories = FortuneCategory::query()
                ->orderBy('order')
                ->orderBy('id')
                ->get(['id', 'name', 'slug', 'icon', 'color', 'is_active'])
                ->map(fn ($c) => [
                    'id' => (int) $c->id,
                    'name' => $c->name,
                    'slug' => $c->slug,
                    'icon' => $c->icon,
                    'color' => $c->color,
                    'is_active' => (bool) $c->is_active,
                ])
                ->values();
        } catch (\Throwable $e) {
            $categories = collect();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'services' => $services,
                'categories' => $categories,
                'writable' => false,
            ],
        ]);
    }
}
