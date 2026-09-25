{{--
 | รายงานค่าธรรมเนียมตลาดสด (admin.fresh-market.commissions) — ธีม V4
 | ตัวแปรจาก Admin\FreshMarketController@commissions:
 |   $stats{total_platform_fees,total_seller_earnings,total_cashback,total_referral_fees,outstanding_gp_debt,total_refunded,total_mlm_processed(จำนวนออเดอร์)},
 |   $recentOrders (ออเดอร์สำเร็จ 20 ล่าสุด with buyer, seller)
 | ใช้คอลัมน์จริง seller_earning / platform_fee / gp_rate (ไม่มีคอลัมน์ mlm_commission / seller_amount)
--}}
@extends('layouts.admin-v4')

@section('title', 'ค่าธรรมเนียมตลาดสด')

@section('content')
@include('admin.riders.partials.v4-kit')
@php
    $gp = (float) ($stats['total_platform_fees'] ?? 0);
    $sellerNet = (float) ($stats['total_seller_earnings'] ?? 0);
    $cashback = (float) ($stats['total_cashback'] ?? 0);
    $grossPie = max(0.01, $gp + $sellerNet);
    $gpShare = round($gp / $grossPie * 100, 1);
    try {
        $currentGpRate = app(\App\Services\Pricing\PricingEngine::class)->gpRateForFreshListing(new \App\Models\FreshMarketListing);
    } catch (\Throwable) {
        $currentGpRate = null;
    }
@endphp
<div x-data="{}" style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== หัวเรื่อง ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div style="display:flex; align-items:center; gap:14px;">
            <a href="{{ route('admin.fresh-market.dashboard') }}" class="tp-icon-btn" title="กลับแดชบอร์ดตลาดสด"><i class="fas fa-arrow-left"></i></a>
            <div>
                <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ตลาดสด · ค่าธรรมเนียม</div>
                <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">ค่าธรรมเนียมและส่วนแบ่ง 💰</h1>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">
                    ค่า GP ที่ใช้กับออเดอร์ใหม่ตอนนี้:
                    <b class="tp-num" style="color:var(--ink);">{{ $currentGpRate !== null ? rtrim(rtrim(number_format($currentGpRate, 2), '0'), '.').'%' : '-' }}</b>
                    <a href="{{ route('admin.fresh-market.settings') }}" class="w1-link" style="margin-left:6px;">ตั้งค่า →</a>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== ตัวเลขสรุป ===== --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(175px,1fr)); gap:14px;">
        @include('admin.riders.partials.kpi', ['kpiIcon' => 'fa-building', 'kpiValue' => '฿'.number_format($gp, 2), 'kpiLabel' => 'ค่า GP แพลตฟอร์ม', 'kpiTone' => null, 'kpiHref' => null, 'kpiHint' => 'จากออเดอร์ที่เสร็จสิ้น', 'kpiPulse' => false])
        @include('admin.riders.partials.kpi', ['kpiIcon' => 'fa-store', 'kpiValue' => '฿'.number_format($sellerNet, 2), 'kpiLabel' => 'ร้านได้รับสุทธิ', 'kpiTone' => 'ok', 'kpiHref' => null, 'kpiHint' => null, 'kpiPulse' => false])
        @include('admin.riders.partials.kpi', ['kpiIcon' => 'fa-gift', 'kpiValue' => '฿'.number_format($cashback, 2), 'kpiLabel' => 'แคชแบ็คจ่ายผู้ซื้อ', 'kpiTone' => 'info', 'kpiHref' => null, 'kpiHint' => null, 'kpiPulse' => false])
        @include('admin.riders.partials.kpi', ['kpiIcon' => 'fa-user-plus', 'kpiValue' => '฿'.number_format((float) ($stats['total_referral_fees'] ?? 0), 2), 'kpiLabel' => 'ค่าแนะนำร้าน', 'kpiTone' => 'violet', 'kpiHref' => null, 'kpiHint' => null, 'kpiPulse' => false])
        @include('admin.riders.partials.kpi', ['kpiIcon' => 'fa-file-invoice-dollar', 'kpiValue' => '฿'.number_format((float) ($stats['outstanding_gp_debt'] ?? 0), 2), 'kpiLabel' => 'ค่า GP ค้างชำระ (COD)', 'kpiTone' => ($stats['outstanding_gp_debt'] ?? 0) > 0 ? 'warn' : null, 'kpiHref' => null, 'kpiHint' => null, 'kpiPulse' => false])
        @include('admin.riders.partials.kpi', ['kpiIcon' => 'fa-rotate-left', 'kpiValue' => '฿'.number_format((float) ($stats['total_refunded'] ?? 0), 2), 'kpiLabel' => 'คืนเงินผู้ซื้อทั้งหมด', 'kpiTone' => 'bad', 'kpiHref' => null, 'kpiHint' => null, 'kpiPulse' => false])
        @include('admin.riders.partials.kpi', ['kpiIcon' => 'fa-sitemap', 'kpiValue' => number_format((int) ($stats['total_mlm_processed'] ?? 0)), 'kpiLabel' => 'ออเดอร์ที่คำนวณค่าแนะนำแล้ว', 'kpiTone' => null, 'kpiHref' => null, 'kpiHint' => 'นับเป็นจำนวนออเดอร์ ไม่ใช่ยอดเงิน', 'kpiPulse' => false])
    </div>

    {{-- ===== สัดส่วนยอดขาย ===== --}}
    <div class="tp-card" style="padding:20px;">
        <div class="tp-section-h" style="margin-bottom:12px;"><i class="fas fa-chart-pie"></i> ยอดขายสำเร็จแบ่งให้ใคร</div>
        <div style="display:flex; flex-wrap:wrap; gap:22px; align-items:center;">
            <div style="width:150px; height:150px; border-radius:50%; flex:none; box-shadow:var(--raise); position:relative;
                        background:conic-gradient(var(--accent1) 0 {{ $gpShare }}%, var(--w-ok) {{ $gpShare }}% 100%);">
                <div style="position:absolute; inset:24px; border-radius:50%; background:var(--surf); display:grid; place-items:center; text-align:center; box-shadow:var(--inset-sm);">
                    <span><b class="tp-num" style="font-size:18px;">{{ $gpShare }}%</b><br><span style="font-size:10.5px; color:var(--ink2);">เป็นค่า GP</span></span>
                </div>
            </div>
            <div style="flex:1; min-width:220px; display:flex; flex-direction:column; gap:10px; font-size:13px;">
                <div style="display:flex; align-items:center; gap:10px;"><span style="width:12px; height:12px; border-radius:4px; background:var(--accent1);"></span><span style="flex:1;">ค่า GP แพลตฟอร์ม</span><b class="tp-num">฿{{ number_format($gp, 2) }}</b></div>
                <div style="display:flex; align-items:center; gap:10px;"><span style="width:12px; height:12px; border-radius:4px; background:var(--w-ok);"></span><span style="flex:1;">ร้านได้รับ</span><b class="tp-num">฿{{ number_format($sellerNet, 2) }}</b></div>
                <div class="tp-divider"></div>
                <div style="font-size:12px; color:var(--ink2); line-height:1.6;">
                    ตลาดสด: ร้านได้ = ยอดขาย − ค่า GP (ไม่มี VAT/ค่าแนะนำหักจากร้าน) · แคชแบ็คตามโปรแพลตฟอร์มออกจากกระเป๋าแพลตฟอร์ม
                    · ออเดอร์เก็บเงินปลายทาง ร้านรับเงินสดเอง ค่า GP จึงตั้งเป็นยอดค้างแล้วหักจากวอลเลตร้านภายหลัง
                </div>
            </div>
        </div>
    </div>

    {{-- ===== ออเดอร์สำเร็จล่าสุด ===== --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div class="tp-section-h" style="padding:14px 18px;"><i class="fas fa-receipt"></i> ออเดอร์สำเร็จล่าสุด</div>
        <div style="overflow-x:auto;">
            <table style="width:100%; min-width:820px; border-collapse:collapse; font-size:13px;">
                <thead>
                    <tr style="text-align:right; font-size:11px; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">
                        <th style="padding:10px 18px; text-align:left;">ออเดอร์</th>
                        <th style="padding:10px 12px; text-align:left;">ร้าน</th>
                        <th style="padding:10px 12px;">ยอดขาย</th>
                        <th style="padding:10px 12px;">ค่า GP</th>
                        <th style="padding:10px 12px;">แคชแบ็ค</th>
                        <th style="padding:10px 12px;">ร้านได้รับ</th>
                        <th style="padding:10px 18px;">เสร็จเมื่อ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentOrders as $order)
                        <tr class="w1-row tp-num" style="text-align:right; box-shadow:inset 0 1px 0 color-mix(in srgb, var(--ink2) 14%, transparent);">
                            <td style="padding:10px 18px; text-align:left;"><a href="{{ route('admin.fresh-market.orders.show', $order) }}" class="w1-link">#{{ $order->order_number }}</a></td>
                            <td style="padding:10px 12px; text-align:left;">{{ $order->seller?->shop_name ?? '-' }}</td>
                            <td style="padding:10px 12px;">฿{{ number_format((float) $order->total_amount, 2) }}</td>
                            <td style="padding:10px 12px;">฿{{ number_format((float) $order->platform_fee, 2) }} <span style="font-size:11px; color:var(--ink2);">({{ $order->gp_rate !== null ? rtrim(rtrim(number_format((float) $order->gp_rate, 2), '0'), '.') : '-' }}%)</span></td>
                            <td style="padding:10px 12px;">฿{{ number_format((float) $order->cashback_amount, 2) }}</td>
                            <td style="padding:10px 12px; font-weight:700;">฿{{ number_format((float) $order->seller_earning, 2) }}</td>
                            <td style="padding:10px 18px; color:var(--ink2); white-space:nowrap;">{{ ($order->completed_at ?? $order->updated_at)?->thaidate('j M Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" style="padding:36px; text-align:center; color:var(--ink2);">ยังไม่มีออเดอร์ที่เสร็จสิ้น</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
