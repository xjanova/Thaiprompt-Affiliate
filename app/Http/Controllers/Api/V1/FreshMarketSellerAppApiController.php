<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\FreshMarketCategory;
use App\Models\FreshMarketOrder;
use App\Models\WalletTransaction;
use App\Services\FreshMarketSellerEarningsService;
use App\Support\TaladsodWebUi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * หน้าร้านตลาดสดในแอป (อ่านอย่างเดียว) — ของที่เดิมต้องเปิดเว็บ
 *
 * - GET /fresh-market/seller/earnings      รายได้ร้าน (ตัวเลขชุดเดียวกับหน้าเว็บ /taladsod/seller/earnings)
 * - GET /fresh-market/seller/listing-form  ค่าที่ฟอร์มลงขาย/แก้สินค้าต้องใช้ (หมวดหมู่ หน่วย ความสด เพดานแคชแบ็ค อัตรา GP โควต้า)
 *
 * ไม่ใช่เจ้าของร้าน = 403 NOT_SELLER (เหมือน endpoint ผู้ขายอื่นๆ) · ตัวเลขเงินเป็น JSON number
 */
class FreshMarketSellerAppApiController extends FreshMarketApiController
{
    /** หน่วยขายที่แนะนำ (ตรงกับรายการในฟอร์มเว็บ — พิมพ์หน่วยอื่นเองได้) */
    public const SUGGESTED_UNITS = ['จาน', 'กล่อง', 'ถุง', 'ชุด', 'แก้ว', 'ชิ้น', 'กก.', 'ขีด', 'กำ', 'ห่อ', 'ลูก', 'แพ็ค'];

    /** ระดับความสดที่ server รับ (ตรงกับกฎ freshness_level) */
    public const FRESHNESS_LEVELS = ['สด', 'สดมาก', 'ผลิตวันนี้'];

    /** รูปสินค้าสูงสุดต่อรายการ */
    public const MAX_IMAGES = 5;

    /**
     * GET /api/v1/fresh-market/seller/earnings
     */
    public function earnings(Request $request): JsonResponse
    {
        $seller = $this->sellerOf($request);

        if (! $seller) {
            return $this->error('NOT_SELLER', 'คุณยังไม่ได้สมัครเป็นผู้ขาย', 403);
        }

        $summary = app(FreshMarketSellerEarningsService::class)->summary($seller);

        $periods = [];
        foreach ($summary['periods'] as $key => $period) {
            $periods[$key] = array_merge(['label' => $period['label']], $period['data']);
        }

        return $this->ok([
            'periods' => $periods,
            'daily' => $summary['daily'],
            'pending' => $summary['pending'],
            'gp_debt' => round((float) $summary['gp_debt'], 2),
            'gp_rate' => (float) $summary['gp_rate'],
            'gp_free' => (bool) $summary['gp_free'],
            'gp_free_until' => $summary['gp_free_until']?->toIso8601String(),
            'wallet_balance' => (float) $summary['wallet_balance'],
            'payouts' => $summary['payouts']->map(fn (WalletTransaction $tx) => [
                'id' => (int) $tx->id,
                'amount' => round((float) $tx->amount, 2),
                'description' => (string) ($tx->description ?? ''),
                'order_id' => $tx->reference_id !== null ? (int) $tx->reference_id : null,
                'created_at' => $tx->created_at?->toIso8601String(),
            ])->values(),
            'recent_completed' => $summary['recent_completed']->map(function (FreshMarketOrder $order) {
                $lines = $order->lineItems();
                $first = $lines->first();
                $count = $lines->count();

                return [
                    'id' => (int) $order->id,
                    'order_number' => (string) $order->order_number,
                    'title' => $first?->title,
                    'items_count' => $count,
                    'total_amount' => round((float) $order->total_amount, 2),
                    'platform_fee' => round((float) $order->platform_fee, 2),
                    'seller_earning' => round((float) $order->seller_earning, 2),
                    'payment_method' => (string) $order->payment_method,
                    'payment_method_label' => TaladsodWebUi::paymentShortLabel($order->payment_method),
                    'completed_at' => $order->completed_at?->toIso8601String(),
                ];
            })->values(),
        ], 'ดึงรายได้ร้านสำเร็จ');
    }

    /**
     * GET /api/v1/fresh-market/seller/listing-form
     */
    public function listingForm(Request $request): JsonResponse
    {
        $seller = $this->sellerOf($request);

        if (! $seller) {
            return $this->error('NOT_SELLER', 'คุณยังไม่ได้สมัครเป็นผู้ขาย', 403);
        }

        $earnings = app(FreshMarketSellerEarningsService::class);
        $canCreate = $seller->canCreateListing();

        // หมวดหมู่ทั้งหมดที่เปิดอยู่ (หมวดหลักก่อน แล้วหมวดย่อย) — ไม่ส่งอีโมจิ ให้แอปใช้ไอคอนของตัวเอง
        $categories = FreshMarketCategory::active()
            ->orderByRaw('parent_id IS NOT NULL')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'name', 'parent_id']);

        return $this->ok([
            'categories' => $categories->map(fn (FreshMarketCategory $c) => [
                'id' => (int) $c->id,
                'name' => (string) $c->name,
                'parent_id' => $c->parent_id !== null ? (int) $c->parent_id : null,
            ])->values(),
            'units' => self::SUGGESTED_UNITS,
            'freshness_levels' => self::FRESHNESS_LEVELS,
            'max_images' => self::MAX_IMAGES,
            'max_cashback_percent' => round((float) $this->marketService->maxCashbackPercent($seller), 2),
            'gp_rate' => $earnings->currentGpRate($seller),
            'gp_free' => $earnings->gpPromoActive(),
            'can_create_listing' => $canCreate,
            'limit_message' => $canCreate ? null : ((! $seller->is_active || $seller->is_suspended)
                ? 'ร้านของคุณถูกระงับหรือปิดอยู่ ไม่สามารถลงขายได้'
                : $this->marketService->listingLimitMessage()),
        ]);
    }
}
