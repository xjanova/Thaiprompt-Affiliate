<?php

/*
 * 🛵 ไรเดอร์รอบ 2 (2026-10-04) — ค่าส่งตามถนนจริง + ผู้ช่วย AI ตั้งค่าตอบแทนไรเดอร์ (เจ้าของ: เลน pricing)
 *
 * ถูก require จาก routes/api.php ภายในกลุ่ม prefix v1 + auth:sanctum
 * ชื่อ route ขึ้นต้นด้วย api.v1.
 */

use App\Http\Controllers\Api\V1\RiderPayApiController;
use Illuminate\Support\Facades\Route;

// ค่าตอบแทนไรเดอร์ของร้านค้า — ด่าน SellerPanelGate (role seller + KYC + ร้านเปิด + แพ็กเกจ) · แตะได้เฉพาะร้านตัวเอง
Route::get('/seller/rider-pay', [RiderPayApiController::class, 'showShop'])
    ->middleware('throttle:60,1,api-rider-pay-show')
    ->name('api.v1.seller.rider-pay.show');
Route::put('/seller/rider-pay', [RiderPayApiController::class, 'updateShop'])
    ->middleware('throttle:30,1,api-rider-pay-update')
    ->name('api.v1.seller.rider-pay.update');

// ค่าตอบแทนไรเดอร์ของร้านตลาดสด — ร้านของผู้เรียก (fresh_market_sellers.user_id)
Route::get('/fresh-market/seller/rider-pay', [RiderPayApiController::class, 'showFreshMarket'])
    ->middleware('throttle:60,1,api-fm-rider-pay-show')
    ->name('api.v1.fresh-market.seller.rider-pay.show');
Route::put('/fresh-market/seller/rider-pay', [RiderPayApiController::class, 'updateFreshMarket'])
    ->middleware('throttle:30,1,api-fm-rider-pay-update')
    ->name('api.v1.fresh-market.seller.rider-pay.update');
