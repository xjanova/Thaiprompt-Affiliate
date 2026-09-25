@extends('layouts.seller-v4')

@section('title', 'วิเคราะห์ยอดขาย')

@php
    // ── กราฟ CSS (.tp-bars / .tp-spark) แทน Chart.js ──
    $dates = $chartData['dates'] ?? [];
    $revenue = array_map('floatval', $chartData['revenue'] ?? []);
    $orders = array_map('intval', $chartData['orders'] ?? []);
    $views = array_map('intval', $chartData['page_views'] ?? []);
    $visitors = array_map('intval', $chartData['unique_visitors'] ?? []);
    $revMax = max(1, ...(count($revenue) ? $revenue : [0]));
    $viewMax = max(1, ...(count($views) ? $views : [0]), ...(count($visitors) ? $visitors : [0]));

    $th = 'padding:11px 14px; text-align:left; font-size:10.5px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px; white-space:nowrap;';
    $td = 'padding:12px 14px; font-size:13px; color:var(--ink); white-space:nowrap;';
    $row = 'border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);';
    $convTone = fn ($r) => $r >= 5 ? 'ok' : ($r >= 2 ? 'warn' : 'bad');
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    <x-seller-kit.header title="วิเคราะห์ยอดขาย" icon="📊" crumb="ร้านค้า · วิเคราะห์"
                         :subtitle="'ช่วงวันที่ '.\Carbon\Carbon::parse($startDate)->format('d/m/Y').' – '.\Carbon\Carbon::parse($endDate)->format('d/m/Y')">
        <a href="{{ route('seller.analytics.export', request()->query()) }}" class="tp-btn tp-btn-sm">📥 ดาวน์โหลด CSV</a>
    </x-seller-kit.header>

    @include('seller.analytics.partials.nav')

    {{-- ตัวกรอง --}}
    <form method="GET" action="{{ route('seller.analytics.index') }}" class="tp-card" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:12px; align-items:end;">
        <div>
            <label for="an-start" style="font-size:12px; font-weight:700; color:var(--ink2);">วันที่เริ่มต้น</label>
            <input id="an-start" type="date" name="start_date" value="{{ $startDate }}" class="tp-input" style="margin-top:6px;">
        </div>
        <div>
            <label for="an-end" style="font-size:12px; font-weight:700; color:var(--ink2);">วันที่สิ้นสุด</label>
            <input id="an-end" type="date" name="end_date" value="{{ $endDate }}" class="tp-input" style="margin-top:6px;">
        </div>
        <div>
            <label for="an-orders" style="font-size:12px; font-weight:700; color:var(--ink2);">ออเดอร์ขั้นต่ำ/วัน</label>
            <input id="an-orders" type="number" name="min_orders" value="{{ $minOrders }}" min="0" class="tp-input tp-num" style="margin-top:6px;">
        </div>
        <div>
            <label for="an-conv" style="font-size:12px; font-weight:700; color:var(--ink2);">Conversion ขั้นต่ำ (%)</label>
            <input id="an-conv" type="number" name="min_conversion" value="{{ $minConversion }}" min="0" max="100" step="0.1" class="tp-input tp-num" style="margin-top:6px;">
        </div>
        <div>
            <label for="an-bounce" style="font-size:12px; font-weight:700; color:var(--ink2);">Bounce สูงสุด (%)</label>
            <input id="an-bounce" type="number" name="max_bounce" value="{{ $maxBounce }}" min="0" max="100" step="0.1" class="tp-input tp-num" style="margin-top:6px;">
        </div>
        <div style="display:flex; gap:8px;">
            <button type="submit" class="tp-btn tp-btn-primary" style="flex:1;">🔍 กรอง</button>
            <a href="{{ route('seller.analytics.index') }}" class="tp-btn">ล้าง</a>
        </div>
    </form>

    {{-- วันนี้แบบเรียลไทม์ --}}
    <div class="tp-card" style="background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 20%, var(--card-bg)), var(--card-bg) 70%);">
        <div class="tp-section-h" style="display:flex; align-items:center; gap:8px;"><span style="width:8px; height:8px; border-radius:50%; background:var(--tp-ok, #5aa07e); animation:tpPulse 1.6s infinite;"></span> วันนี้แบบเรียลไทม์</div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:10px; margin-top:12px;">
            @foreach([
                ['กำลังดูร้านอยู่', number_format((int) ($realTimeStats['current_active_visitors'] ?? 0)), '🟢'],
                ['เข้าชมวันนี้', number_format((int) ($realTimeStats['page_views_today'] ?? 0)), '👁️'],
                ['ผู้เยี่ยมชมไม่ซ้ำ', number_format((int) ($realTimeStats['unique_visitors_today'] ?? 0)), '👥'],
                ['ออเดอร์วันนี้', number_format((int) ($realTimeStats['orders_today'] ?? 0)), '🛒'],
                ['รายได้วันนี้', '฿'.number_format((float) ($realTimeStats['revenue_today'] ?? 0), 2), '💰'],
            ] as [$rtLabel, $rtValue, $rtIcon])
                <div class="tp-inset-sm" style="border-radius:14px; padding:12px;">
                    <div style="font-size:11.5px; color:var(--ink2);">{{ $rtIcon }} {{ $rtLabel }}</div>
                    <div class="tp-num" style="font-size:20px; font-weight:800; margin-top:4px;">{{ $rtValue }}</div>
                </div>
            @endforeach
        </div>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:14px;">
        <x-seller-kit.stat label="การเข้าชมทั้งหมด" :value="number_format((int) $summary['total_page_views'])" icon="👁️" tone="info" />
        <x-seller-kit.stat label="ผู้เยี่ยมชมไม่ซ้ำ" :value="number_format((int) $summary['total_unique_visitors'])" icon="👥" tone="ok" />
        <x-seller-kit.stat label="ออเดอร์ทั้งหมด" :value="number_format((int) $summary['total_orders'])" icon="🛒" tone="warn" />
        <x-seller-kit.stat label="รายได้ทั้งหมด" :value="'฿'.number_format((float) $summary['total_revenue'], 2)" icon="💰" tone="gold" />
        <x-seller-kit.stat label="อัตราการแปลงเฉลี่ย" :value="number_format((float) $summary['avg_conversion_rate'], 2).'%'" icon="🎯" tone="violet" hint="ผู้เข้าชม → ผู้ซื้อ" />
        <x-seller-kit.stat label="มูลค่าเฉลี่ยต่อออเดอร์" :value="'฿'.number_format((float) $summary['avg_order_value'], 2)" icon="🧾" tone="ok" />
        <x-seller-kit.stat label="อัตราตีกลับเฉลี่ย" :value="number_format((float) $summary['avg_bounce_rate'], 2).'%'" icon="↩️" tone="bad" hint="เข้าแล้วออกทันที" />
    </div>

    @if(count($dates) > 0)
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(320px,1fr)); gap:16px;">
            <div class="tp-card">
                <div class="tp-section-h">💰 รายได้รายวัน</div>
                <div style="overflow-x:auto; margin-top:14px;">
                    <div class="tp-bars" style="height:180px; gap:4px; min-width:{{ max(300, count($dates) * 22) }}px;">
                        @foreach($dates as $i => $d)
                            @php
                                $rv = $revenue[$i] ?? 0;
                            @endphp
                            <div class="col" title="{{ $d }} · ฿{{ number_format($rv, 2) }} · {{ $orders[$i] ?? 0 }} ออเดอร์">
                                <div class="stack"><div class="bar a" style="height:{{ max(2, $rv / $revMax * 100) }}%; width:70%; {{ $rv > 0 ? '' : 'opacity:.25;' }}"></div></div>
                                <div class="lbl">{{ $i % max(1, (int) ceil(count($dates) / 8)) === 0 ? $d : '' }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="tp-card">
                <div class="tp-section-h">👁️ การเข้าชม vs ผู้เยี่ยมชมไม่ซ้ำ</div>
                <div style="overflow-x:auto; margin-top:14px;">
                    <div class="tp-bars" style="height:180px; gap:4px; min-width:{{ max(300, count($dates) * 22) }}px;">
                        @foreach($dates as $i => $d)
                            <div class="col" title="{{ $d }} · เข้าชม {{ number_format($views[$i] ?? 0) }} · ไม่ซ้ำ {{ number_format($visitors[$i] ?? 0) }}">
                                <div class="stack">
                                    <div class="bar a" style="height:{{ max(2, ($views[$i] ?? 0) / $viewMax * 100) }}%;"></div>
                                    <div class="bar b" style="height:{{ max(2, ($visitors[$i] ?? 0) / $viewMax * 100) }}%;"></div>
                                </div>
                                <div class="lbl">{{ $i % max(1, (int) ceil(count($dates) / 8)) === 0 ? $d : '' }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div style="display:flex; gap:14px; font-size:11.5px; color:var(--ink2); margin-top:8px;">
                    <span><span style="display:inline-block; width:10px; height:10px; border-radius:3px; background:var(--accent1);"></span> การเข้าชม</span>
                    <span><span style="display:inline-block; width:10px; height:10px; border-radius:3px; background:var(--accent2);"></span> ผู้เยี่ยมชมไม่ซ้ำ</span>
                </div>
            </div>
        </div>
    @endif

    {{-- ตารางรายวัน --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div class="tp-section-h" style="padding:16px 18px;">📅 ข้อมูลรายวัน</div>
        @if($dailyAnalytics->count() > 0)
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; min-width:640px;">
                    <thead>
                        <tr style="background:color-mix(in srgb, var(--ink2) 8%, transparent);">
                            <th style="{{ $th }}">วันที่</th>
                            <th style="{{ $th }} text-align:right;">การเข้าชม</th>
                            <th style="{{ $th }} text-align:right;">ผู้เยี่ยมชม</th>
                            <th style="{{ $th }} text-align:right;">ออเดอร์</th>
                            <th style="{{ $th }} text-align:right;">รายได้</th>
                            <th style="{{ $th }} text-align:center;">อัตราแปลง</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($dailyAnalytics as $day)
                            <tr style="{{ $row }}">
                                <td style="{{ $td }}" class="tp-num">{{ \Carbon\Carbon::parse($day->date)->format('d/m/Y') }}</td>
                                <td style="{{ $td }} text-align:right;" class="tp-num">{{ number_format((int) $day->page_views) }}</td>
                                <td style="{{ $td }} text-align:right;" class="tp-num">{{ number_format((int) $day->unique_visitors) }}</td>
                                <td style="{{ $td }} text-align:right;" class="tp-num">{{ number_format((int) $day->orders_count) }}</td>
                                <td style="{{ $td }} text-align:right; font-weight:800;" class="tp-num">฿{{ number_format((float) $day->total_sales, 2) }}</td>
                                <td style="{{ $td }} text-align:center;"><x-seller-kit.pill :tone="$convTone((float) $day->conversion_rate)">{{ number_format((float) $day->conversion_rate, 2) }}%</x-seller-kit.pill></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-seller-kit.empty icon="📅" title="ยังไม่มีข้อมูลรายวัน" text="ระบบรวบรวมสถิติของเมื่อวานทุกคืน (ตี 1:20) เมื่อร้านมีผู้เข้าชมหรือออเดอร์ ตารางนี้จะเริ่มมีข้อมูล" />
        @endif
    </div>
</div>
@endsection
