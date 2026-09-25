{{--
 | แดชบอร์ดตลาดสด (admin.fresh-market.dashboard) — ธีม V4
 | ตัวแปรจาก Admin\FreshMarketController@dashboard:
 |   $stats{total_sellers,active_sellers,unverified_sellers,total_listings,active_listings,total_orders,pending_orders,active_orders,
 |          delivery_failed_orders,completed_orders,total_revenue,total_platform_fees,outstanding_gp_debt,total_categories},
 |   $recentOrders (buyer, seller), $recentSellers (user)
--}}
@extends('layouts.admin-v4')

@section('title', 'แดชบอร์ดตลาดสด')

@section('content')
@include('admin.riders.partials.v4-kit')
<div x-data="{}" style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== หัวเรื่อง ===== --}}
    <div class="tp-card" style="padding:22px; position:relative; overflow:hidden;">
        <div style="position:absolute; right:-40px; top:-60px; width:220px; height:220px; border-radius:50%; background:radial-gradient(circle, color-mix(in srgb, var(--w-ok) 30%, transparent), transparent 70%); pointer-events:none;"></div>
        <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:14px; position:relative;">
            <div>
                <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ตลาดสด</div>
                <h1 class="tp-num" style="font-size:clamp(22px,4vw,30px); font-weight:800; margin:4px 0 0;">ตลาดสดไทยพร๊อม 🥬</h1>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">ภาพรวมร้าน สินค้า ออเดอร์ และค่าธรรมเนียมของตลาดสด</div>
            </div>
            <div style="display:flex; flex-wrap:wrap; gap:9px;">
                <a href="{{ route('admin.fresh-market.orders', ['status' => 'pending']) }}" class="tp-btn tp-btn-sm"><i class="fas fa-hourglass-half" style="color:var(--w-warn);"></i> รอร้านยืนยัน <span class="tp-pill tp-pill-gold">{{ number_format($stats['pending_orders'] ?? 0) }}</span></a>
                <a href="{{ route('admin.fresh-market.sellers', ['status' => 'unverified']) }}" class="tp-btn tp-btn-sm"><i class="fas fa-user-clock" style="color:var(--w-info);"></i> ร้านรอยืนยัน <span class="tp-pill tp-pill-gold">{{ number_format($stats['unverified_sellers'] ?? 0) }}</span></a>
                <a href="{{ route('admin.fresh-market.settings') }}" class="tp-btn tp-btn-sm tp-btn-primary"><i class="fas fa-gear"></i> ตั้งค่า</a>
            </div>
        </div>
    </div>

    @include('admin.riders.partials.flash')

    @if (($stats['delivery_failed_orders'] ?? 0) > 0)
        <a href="{{ route('admin.fresh-market.orders', ['status' => 'delivery_failed']) }}" class="tp-card tp-card-hover"
           style="padding:14px 18px; border-left:4px solid var(--w-bad); text-decoration:none; color:var(--ink); display:flex; align-items:center; gap:12px;">
            <i class="fas fa-triangle-exclamation" style="color:var(--w-bad);"></i>
            <span style="flex:1; font-size:13.5px;"><b class="tp-num">{{ number_format($stats['delivery_failed_orders']) }}</b> ออเดอร์จัดส่งไม่สำเร็จ ต้องตัดสินใจ — เรียกไรเดอร์ใหม่ หรือยกเลิกและคืนเงิน</span>
            <i class="fas fa-chevron-right" style="color:var(--ink2);"></i>
        </a>
    @endif

    {{-- ===== ตัวเลขสรุป ===== --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(170px,1fr)); gap:14px;">
        @include('admin.riders.partials.kpi', ['kpiIcon' => 'fa-store', 'kpiValue' => number_format($stats['total_sellers'] ?? 0), 'kpiLabel' => 'ร้านค้าทั้งหมด', 'kpiTone' => null, 'kpiHref' => route('admin.fresh-market.sellers'), 'kpiHint' => 'เปิดขาย '.number_format($stats['active_sellers'] ?? 0).' ร้าน', 'kpiPulse' => false])
        @include('admin.riders.partials.kpi', ['kpiIcon' => 'fa-carrot', 'kpiValue' => number_format($stats['active_listings'] ?? 0), 'kpiLabel' => 'สินค้าที่เปิดขาย', 'kpiTone' => 'ok', 'kpiHref' => route('admin.fresh-market.listings', ['status' => 'active']), 'kpiHint' => 'ทั้งหมด '.number_format($stats['total_listings'] ?? 0).' รายการ', 'kpiPulse' => false])
        @include('admin.riders.partials.kpi', ['kpiIcon' => 'fa-receipt', 'kpiValue' => number_format($stats['total_orders'] ?? 0), 'kpiLabel' => 'ออเดอร์ทั้งหมด', 'kpiTone' => 'info', 'kpiHref' => route('admin.fresh-market.orders'), 'kpiHint' => 'สำเร็จ '.number_format($stats['completed_orders'] ?? 0), 'kpiPulse' => false])
        @include('admin.riders.partials.kpi', ['kpiIcon' => 'fa-truck-fast', 'kpiValue' => number_format($stats['active_orders'] ?? 0), 'kpiLabel' => 'ออเดอร์ที่ยังไม่จบ', 'kpiTone' => 'violet', 'kpiHref' => route('admin.fresh-market.orders'), 'kpiHint' => 'รอร้านยืนยัน '.number_format($stats['pending_orders'] ?? 0), 'kpiPulse' => ($stats['pending_orders'] ?? 0) > 0])
        @include('admin.riders.partials.kpi', ['kpiIcon' => 'fa-sack-dollar', 'kpiValue' => '฿'.number_format($stats['total_revenue'] ?? 0, 2), 'kpiLabel' => 'ยอดขายสำเร็จ', 'kpiTone' => 'ok', 'kpiHref' => route('admin.fresh-market.commissions'), 'kpiHint' => null, 'kpiPulse' => false])
        @include('admin.riders.partials.kpi', ['kpiIcon' => 'fa-building', 'kpiValue' => '฿'.number_format($stats['total_platform_fees'] ?? 0, 2), 'kpiLabel' => 'ค่า GP แพลตฟอร์ม', 'kpiTone' => null, 'kpiHref' => route('admin.fresh-market.commissions'), 'kpiHint' => null, 'kpiPulse' => false])
        @include('admin.riders.partials.kpi', ['kpiIcon' => 'fa-file-invoice-dollar', 'kpiValue' => '฿'.number_format($stats['outstanding_gp_debt'] ?? 0, 2), 'kpiLabel' => 'ค่า GP ค้างชำระ (COD)', 'kpiTone' => ($stats['outstanding_gp_debt'] ?? 0) > 0 ? 'warn' : null, 'kpiHref' => route('admin.fresh-market.commissions'), 'kpiHint' => null, 'kpiPulse' => false])
        @include('admin.riders.partials.kpi', ['kpiIcon' => 'fa-folder-tree', 'kpiValue' => number_format($stats['total_categories'] ?? 0), 'kpiLabel' => 'หมวดหมู่', 'kpiTone' => null, 'kpiHref' => route('admin.fresh-market.categories'), 'kpiHint' => null, 'kpiPulse' => false])
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,440px),1fr)); gap:16px; align-items:start;">
        {{-- ===== ออเดอร์ล่าสุด ===== --}}
        <div class="tp-card" style="padding:0; overflow:hidden;">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; padding:14px 18px;">
                <div class="tp-section-h"><i class="fas fa-receipt"></i> ออเดอร์ล่าสุด</div>
                <a href="{{ route('admin.fresh-market.orders') }}" class="w1-link" style="font-size:12.5px;">ดูทั้งหมด →</a>
            </div>
            <div style="overflow-x:auto;">
                <table style="width:100%; min-width:460px; border-collapse:collapse; font-size:13px;">
                    <tbody>
                        @forelse ($recentOrders as $order)
                            <tr class="w1-row" style="box-shadow:inset 0 1px 0 color-mix(in srgb, var(--ink2) 14%, transparent);">
                                <td style="padding:10px 18px;">
                                    <a href="{{ route('admin.fresh-market.orders.show', $order) }}" class="w1-link tp-num">#{{ $order->order_number }}</a>
                                    <div style="font-size:11.5px; color:var(--ink2);">{{ $order->buyer?->name ?? '-' }} · {{ $order->seller?->shop_name ?? '-' }}</div>
                                </td>
                                <td style="padding:10px 12px; text-align:right;" class="tp-num">฿{{ number_format((float) $order->total_amount, 2) }}</td>
                                <td style="padding:10px 18px; text-align:right;">@include('admin.riders.partials.status', ['statusKind' => 'fm_order', 'statusValue' => $order->order_status, 'statusLabel' => null])</td>
                            </tr>
                        @empty
                            <tr><td style="padding:30px; text-align:center; color:var(--ink2);">ยังไม่มีออเดอร์</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ===== ร้านค้าล่าสุด ===== --}}
        <div class="tp-card" style="padding:0; overflow:hidden;">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; padding:14px 18px;">
                <div class="tp-section-h"><i class="fas fa-store"></i> ร้านค้าที่สมัครล่าสุด</div>
                <a href="{{ route('admin.fresh-market.sellers') }}" class="w1-link" style="font-size:12.5px;">ดูทั้งหมด →</a>
            </div>
            <div style="overflow-x:auto;">
                <table style="width:100%; min-width:420px; border-collapse:collapse; font-size:13px;">
                    <tbody>
                        @forelse ($recentSellers as $seller)
                            <tr class="w1-row" style="box-shadow:inset 0 1px 0 color-mix(in srgb, var(--ink2) 14%, transparent);">
                                <td style="padding:10px 18px;">
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        <span class="tp-tile" style="width:34px; height:34px; border-radius:50%; font-weight:800;">{{ mb_substr($seller->shop_name ?: 'ร', 0, 1) }}</span>
                                        <div style="min-width:0;">
                                            <a href="{{ route('admin.fresh-market.sellers.show', $seller) }}" class="w1-link" style="color:var(--ink);">{{ $seller->shop_name }}</a>
                                            <div style="font-size:11.5px; color:var(--ink2);">{{ $seller->user?->name ?? '-' }} · {{ number_format((int) $seller->total_listings) }} สินค้า</div>
                                        </div>
                                    </div>
                                </td>
                                <td style="padding:10px 18px; text-align:right;">@include('admin.riders.partials.status', ['statusKind' => 'seller', 'statusValue' => $seller->status_key, 'statusLabel' => null])</td>
                            </tr>
                        @empty
                            <tr><td style="padding:30px; text-align:center; color:var(--ink2);">ยังไม่มีร้านค้า</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ===== ทางลัด ===== --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:12px;">
        @foreach ([
            ['admin.fresh-market.sellers', 'fa-user-tie', 'ผู้ขาย'],
            ['admin.fresh-market.listings', 'fa-carrot', 'สินค้า'],
            ['admin.fresh-market.orders', 'fa-receipt', 'ออเดอร์'],
            ['admin.fresh-market.categories', 'fa-folder-tree', 'หมวดหมู่'],
            ['admin.fresh-market.commissions', 'fa-percent', 'ค่าธรรมเนียม'],
            ['admin.rider-jobs.index', 'fa-motorcycle', 'งานไรเดอร์'],
            ['admin.fresh-market.test-line', 'fa-comment-dots', 'ทดสอบ LINE'],
        ] as [$shortcutRoute, $shortcutIcon, $shortcutLabel])
            <a href="{{ route($shortcutRoute) }}" class="tp-card tp-card-hover" style="padding:14px; display:flex; align-items:center; gap:10px; text-decoration:none; color:var(--ink); font-weight:600; font-size:13px;">
                <span class="tp-tile" style="width:34px; height:34px; font-size:14px;"><i class="fas {{ $shortcutIcon }}"></i></span>
                {{ $shortcutLabel }}
            </a>
        @endforeach
    </div>
</div>
@endsection
