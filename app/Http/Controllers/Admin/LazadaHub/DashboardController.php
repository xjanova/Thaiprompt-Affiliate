<?php

namespace App\Http\Controllers\Admin\LazadaHub;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceAccount;
use App\Models\MarketplaceCommission;
use App\Models\MarketplaceOrder;
use App\Models\MarketplacePlatform;
use App\Models\MarketplaceProduct;

/**
 * Lazada Hub — แดชบอร์ดภาพรวม
 *
 * สรุปสถานะการเชื่อมต่อ + จำนวนสินค้า (แยกโหมด affiliate/ขายเอง) +
 * ออเดอร์ + ค่าคอมมิชชั่นที่รออนุมัติ เพื่อเป็นหน้าแรกของศูนย์ Lazada
 */
class DashboardController extends Controller
{
    /**
     * แสดงแดชบอร์ด Lazada Hub
     */
    public function index()
    {
        $platform = MarketplacePlatform::where('slug', 'lazada')->first();
        $platformId = $platform?->id;

        // ── การเชื่อมต่อ ──
        $accounts = $platformId
            ? MarketplaceAccount::where('platform_id', $platformId)->get()
            : collect();

        $stats = [
            'connections_total' => $accounts->count(),
            'connections_active' => $accounts->where('status', 'active')->count(),
            'has_seller' => $accounts->where('program_type', 'seller')->isNotEmpty(),
            'has_affiliate' => $accounts->where('program_type', 'affiliate')->isNotEmpty(),
        ];

        // ── สินค้า (แยกโหมด) ──
        $productQuery = $platformId
            ? MarketplaceProduct::where('platform_id', $platformId)
            : MarketplaceProduct::query()->whereRaw('1 = 0');

        $stats['products_total'] = (clone $productQuery)->count();
        $stats['products_affiliate'] = (clone $productQuery)->where('fulfillment_mode', 'affiliate')->count();
        $stats['products_resell'] = (clone $productQuery)->where('fulfillment_mode', 'resell')->count();
        $stats['products_published'] = (clone $productQuery)->whereNotNull('internal_product_id')->count();

        // ── ออเดอร์ + ค่าคอม ──
        $orderQuery = $platformId
            ? MarketplaceOrder::where('platform_id', $platformId)
            : MarketplaceOrder::query()->whereRaw('1 = 0');

        $stats['orders_total'] = (clone $orderQuery)->count();
        $stats['orders_pending'] = (clone $orderQuery)->where('commission_status', 'pending')->count();

        $commissionQuery = $platformId
            ? MarketplaceCommission::where('platform_id', $platformId)
            : MarketplaceCommission::query()->whereRaw('1 = 0');

        $stats['commission_pending'] = (clone $commissionQuery)->where('status', 'pending')->sum('net_commission');
        $stats['commission_paid'] = (clone $commissionQuery)->where('status', 'paid')->sum('net_commission');

        return view('admin.lazada-hub.dashboard', [
            'pageTitle' => 'Lazada Hub',
            'platform' => $platform,
            'accounts' => $accounts,
            'stats' => $stats,
        ]);
    }
}
