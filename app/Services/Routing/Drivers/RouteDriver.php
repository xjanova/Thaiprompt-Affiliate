<?php

namespace App\Services\Routing\Drivers;

use App\Services\Routing\RouteProviderException;
use App\Services\Routing\RouteResult;

/**
 * ผู้ให้บริการหาเส้นทางตามถนน 1 ราย (Valhalla / Google Routes)
 */
interface RouteDriver
{
    /**
     * ชื่อแหล่งข้อมูล (ตรงกับ RouteResult::source)
     */
    public function name(): string;

    /**
     * เปิดใช้อยู่และตั้งค่าครบหรือไม่ (ไม่แตะเครือข่าย)
     */
    public function enabled(): bool;

    /**
     * หาเส้นทางจาก → ถึง (ระยะ กม., เวลา นาที, polyline precision 6)
     *
     * @throws RouteProviderException เมื่อหาไม่ได้ (ชนิดบอกว่าควรตัดวงจรหรือไม่)
     */
    public function route(float $fromLat, float $fromLng, float $toLat, float $toLng): RouteResult;
}
