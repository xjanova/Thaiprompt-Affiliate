@extends('layouts.admin-v4')

@section('title', 'รายงานยอดขายอีคอมเมิร์ซ')

@php
    $c = [
        'ok' => 'var(--tp-ok,#5aa07e)',
        'bad' => 'var(--tp-bad,#d9534f)',
        'warn' => 'var(--tp-warn,#e0a52e)',
        'info' => 'var(--tp-info,#5689b8)',
        'violet' => 'var(--tp-violet,#8c6fd6)',
        'teal' => 'var(--tp-teal,#4d97a6)',
        'mute' => 'var(--ink2)',
    ];
    $th = 'padding:11px 14px; text-align:left; font-size:11px; font-weight:700; color:var(--ink2); letter-spacing:.3px; white-space:nowrap;';
    $td = 'padding:11px 14px; font-size:13px; color:var(--ink); vertical-align:middle;';
    $lbl = 'display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;';
    $money = fn ($v, $d = 0) => '฿' . number_format((float) $v, $d);

    $periodLabels = [
        'today' => 'วันนี้', 'yesterday' => 'เมื่อวาน', 'week' => '7 วัน',
        'month' => '30 วัน', 'quarter' => '3 เดือน', 'year' => '1 ปี',
    ];
    $customRange = request()->filled('date_from') && request()->filled('date_to');

    // กราฟยอดขายรายวัน (CSS bars) — ช่วงยาวมากจะบีบแท่งให้แคบลงเอง
    $salesRows = collect($salesReport ?? [])->values();
    $salesMax = (float) ($salesRows->max('revenue') ?: 0);

    $statusColorMap = [
        'yellow' => $c['warn'], 'blue' => $c['info'], 'indigo' => $c['violet'], 'teal' => $c['teal'],
        'green' => $c['ok'], 'red' => $c['bad'], 'gray' => $c['mute'],
    ];
    $statusTotal = (int) collect($orderStatusDistribution ?? [])->sum('count');

    $retention = ($customerStats['total_customers'] ?? 0) > 0
        ? round((($customerStats['returning_customers'] ?? 0) / $customerStats['total_customers']) * 100, 1)
        : 0;
    $moneySplit = $moneySplit ?? null;
    $growth = function ($v) use ($c) {
        $v = (float) $v;
        if ($v == 0.0) {
            return '<span style="color:var(--ink2);">คงที่</span>';
        }
        $color = $v > 0 ? $c['ok'] : $c['bad'];
        $arrow = $v > 0 ? '▲' : '▼';

        return '<span style="color:'.$color.'; font-weight:700;">'.$arrow.' '.number_format(abs($v), 1).'%</span>';
    };
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== หัวหน้า ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · อีคอมเมิร์ซ · รายงาน</div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">รายงานยอดขาย 📈</h1>
            <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">ช่วง <span class="tp-num">{{ \Illuminate\Support\Carbon::parse($dateFrom)->format('d/m/Y') }} – {{ \Illuminate\Support\Carbon::parse($dateTo)->format('d/m/Y') }}</span></div>
        </div>
        <div style="display:flex; gap:9px; flex-wrap:wrap;">
            <a href="{{ route('admin.unified-reports.export', ['type' => 'ecommerce', 'period' => $period ?? 'month']) }}" class="tp-btn tp-btn-sm"><i class="fas fa-file-pdf"></i> ส่งออก</a>
            <a href="{{ route('admin.unified-reports.export-csv', ['type' => 'ecommerce', 'period' => $period ?? 'month']) }}" class="tp-btn tp-btn-sm"><i class="fas fa-file-csv"></i> CSV</a>
        </div>
    </div>

    {{-- ===== เลือกช่วงเวลา ===== --}}
    <div class="tp-card" style="padding:16px 18px; display:flex; flex-wrap:wrap; gap:14px; align-items:flex-end; justify-content:space-between;">
        <div class="tp-inset-sm" style="display:flex; flex-wrap:wrap; gap:4px; padding:5px; border-radius:14px;">
            @foreach($periodLabels as $key => $label)
                @php $activePeriod = ! $customRange && ($period ?? 'month') === $key; @endphp
                <a href="{{ route('admin.ecommerce.reports', ['period' => $key]) }}" class="tp-seg" style="text-decoration:none; text-align:center; {{ $activePeriod ? 'background:var(--card-bg); box-shadow:var(--raise);' : '' }}">{{ $label }}</a>
            @endforeach
        </div>
        <form method="GET" action="{{ route('admin.ecommerce.reports') }}" style="display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end;">
            <div>
                <label style="{{ $lbl }}">ตั้งแต่</label>
                <input type="date" name="date_from" value="{{ $customRange ? request('date_from') : $dateFrom }}" class="tp-input" required>
            </div>
            <div>
                <label style="{{ $lbl }}">ถึง</label>
                <input type="date" name="date_to" value="{{ $customRange ? request('date_to') : $dateTo }}" class="tp-input" required>
            </div>
            <button type="submit" class="tp-btn tp-btn-primary"><i class="fas fa-calendar-check"></i> ดูช่วงนี้</button>
        </form>
    </div>

    {{-- ===== สรุป ===== --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:16px;">
        <div class="tp-card">
            <div style="font-size:12px; color:var(--ink2); font-weight:600;"><i class="fas fa-sack-dollar" style="color:{{ $c['ok'] }};"></i> ยอดขาย (ชำระแล้ว)</div>
            <div class="tp-num" style="font-size:26px; font-weight:800; margin-top:6px;">{{ $money($summary['total_revenue'] ?? 0) }}</div>
            <div style="font-size:12px; margin-top:4px;">{!! $growth($summary['revenue_growth'] ?? 0) !!} <span style="color:var(--ink2);">เทียบช่วงก่อน</span></div>
        </div>
        <div class="tp-card">
            <div style="font-size:12px; color:var(--ink2); font-weight:600;"><i class="fas fa-receipt" style="color:{{ $c['info'] }};"></i> คำสั่งซื้อ</div>
            <div class="tp-num" style="font-size:26px; font-weight:800; margin-top:6px;">{{ number_format($summary['total_orders'] ?? 0) }}</div>
            <div style="font-size:12px; margin-top:4px;">{!! $growth($summary['orders_growth'] ?? 0) !!} <span style="color:var(--ink2);">· สำเร็จ {{ number_format($summary['completed_orders'] ?? 0) }} · ยกเลิก {{ number_format($summary['cancelled_orders'] ?? 0) }}</span></div>
        </div>
        <div class="tp-card">
            <div style="font-size:12px; color:var(--ink2); font-weight:600;"><i class="fas fa-basket-shopping" style="color:{{ $c['violet'] }};"></i> ยอดเฉลี่ยต่อออเดอร์</div>
            <div class="tp-num" style="font-size:26px; font-weight:800; margin-top:6px;">{{ $money($summary['average_order_value'] ?? 0) }}</div>
            <div style="font-size:12px; color:var(--ink2); margin-top:4px;">รอดำเนินการ {{ number_format($summary['pending_orders'] ?? 0) }} ออเดอร์</div>
        </div>
        <div class="tp-card">
            <div style="font-size:12px; color:var(--ink2); font-weight:600;"><i class="fas fa-boxes-stacked" style="color:{{ $c['warn'] }};"></i> ชิ้นที่ขายได้</div>
            <div class="tp-num" style="font-size:26px; font-weight:800; margin-top:6px;">{{ number_format($summary['total_items_sold'] ?? 0) }}</div>
            <div style="font-size:12px; color:var(--ink2); margin-top:4px;">จากออเดอร์ที่ชำระแล้ว</div>
        </div>
    </div>

    {{-- ===== ส่วนแบ่งแพลตฟอร์มในช่วงนี้ ===== --}}
    @if($moneySplit)
        <div class="tp-card">
            <div class="tp-section-h" style="margin-bottom:4px;"><i class="fas fa-scale-balanced" style="color:var(--accent1);"></i> การแบ่งเงินในช่วงนี้</div>
            <div style="font-size:12px; color:var(--ink2); margin-bottom:12px;">GP เข้ากระเป๋าแพลตฟอร์ม · VAT เก็บแทนร้านที่จด VAT · ค่าแนะนำเข้ากองทุนผู้แนะนำ · ส่วนผู้ขายพักไว้จนลูกค้าได้รับของ</div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:12px;">
                @foreach([
                    ['ค่า GP แพลตฟอร์ม', $moneySplit['gp'], $c['info']],
                    ['VAT', $moneySplit['vat'], $c['violet']],
                    ['กองทุนผู้แนะนำ', $moneySplit['referral_pool'], $c['warn']],
                    ['พักเงินให้ผู้ขาย', $moneySplit['seller_escrow'], $c['ok']],
                    ['รายได้ร้านทางการ', $moneySplit['official_shop'], $c['mute']],
                ] as [$label, $value, $color])
                    <div class="tp-well" style="padding:12px 14px;">
                        <div style="font-size:11.5px; color:var(--ink2); font-weight:600;">{{ $label }}</div>
                        <div class="tp-num" style="font-size:18px; font-weight:800; color:{{ $color }}; margin-top:2px;">{{ $money($value, 2) }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ===== กราฟยอดขายรายวัน ===== --}}
    <div class="tp-card">
        <div style="display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap; margin-bottom:14px;">
            <div class="tp-section-h"><i class="fas fa-chart-column" style="color:var(--accent1);"></i> แนวโน้มยอดขายรายวัน</div>
            <span class="tp-pill tp-pill-soft tp-num">{{ $salesRows->count() }} วันที่มียอดขาย</span>
        </div>
        @if($salesMax > 0)
            <div style="overflow-x:auto;">
                <div class="tp-bars" style="min-width:{{ max(320, $salesRows->count() * 30) }}px; height:200px; gap:6px;">
                    @foreach($salesRows as $i => $r)
                        @php $h = max(3, round(((float) $r->revenue / $salesMax) * 100)); @endphp
                        <div class="col" title="{{ \Illuminate\Support\Carbon::parse($r->date)->format('d/m/Y') }} · {{ $money($r->revenue) }} · {{ number_format((int) $r->orders) }} ออเดอร์">
                            <div class="stack"><i class="bar a" style="height:{{ $h }}%; width:70%; animation-delay:{{ min($i * 25, 800) }}ms;"></i></div>
                            <span class="lbl">{{ \Illuminate\Support\Carbon::parse($r->date)->format('d/m') }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div style="text-align:center; color:var(--ink2); padding:40px 0; font-size:13px;">ไม่มียอดขายที่ชำระแล้วในช่วงนี้</div>
        @endif
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,320px),1fr)); gap:16px;">
        {{-- สถานะออเดอร์ --}}
        <div class="tp-card">
            <div class="tp-section-h" style="margin-bottom:12px;"><i class="fas fa-list-check" style="color:var(--accent1);"></i> สถานะคำสั่งซื้อ</div>
            @forelse($orderStatusDistribution as $row)
                @php $pct = $statusTotal > 0 ? round($row->count / $statusTotal * 100, 1) : 0; $col = $statusColorMap[$row->color] ?? $c['mute']; @endphp
                <div style="margin-bottom:10px;">
                    <div style="display:flex; justify-content:space-between; font-size:13px;">
                        <span><i class="fas fa-circle" style="color:{{ $col }}; font-size:9px;"></i> {{ $row->label }}</span>
                        <span class="tp-num"><strong>{{ number_format($row->count) }}</strong> <span style="color:var(--ink2);">({{ $pct }}%)</span></span>
                    </div>
                    <div class="tp-inset-sm" style="height:8px; border-radius:6px; margin-top:5px; overflow:hidden;">
                        <div style="height:100%; width:{{ $pct }}%; background:{{ $col }}; border-radius:6px;"></div>
                    </div>
                </div>
            @empty
                <div style="font-size:13px; color:var(--ink2);">ไม่มีคำสั่งซื้อในช่วงนี้</div>
            @endforelse
        </div>

        {{-- ลูกค้า --}}
        <div class="tp-card">
            <div class="tp-section-h" style="margin-bottom:12px;"><i class="fas fa-users" style="color:var(--accent1);"></i> ลูกค้า</div>
            <div style="display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px;">
                @foreach([
                    ['ลูกค้าทั้งหมด', $customerStats['total_customers'] ?? 0],
                    ['ลูกค้าซื้อซ้ำ', $customerStats['returning_customers'] ?? 0],
                    ['ลูกค้าใหม่', $customerStats['new_customers'] ?? 0],
                ] as [$label, $value])
                    <div class="tp-well" style="padding:10px; text-align:center;">
                        <div class="tp-num" style="font-size:20px; font-weight:800;">{{ number_format($value) }}</div>
                        <div style="font-size:11px; color:var(--ink2);">{{ $label }}</div>
                    </div>
                @endforeach
            </div>
            <div style="margin-top:14px;">
                <div style="display:flex; justify-content:space-between; font-size:13px;"><span>อัตราซื้อซ้ำ</span><span class="tp-num" style="font-weight:800;">{{ $retention }}%</span></div>
                <div class="tp-inset-sm" style="height:10px; border-radius:6px; margin-top:6px; overflow:hidden;">
                    <div style="height:100%; width:{{ min(100, $retention) }}%; background:linear-gradient(90deg,var(--accent1),var(--accent2)); border-radius:6px;"></div>
                </div>
            </div>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,360px),1fr)); gap:16px;">
        {{-- สินค้าขายดี --}}
        <div class="tp-card" style="padding:0; overflow:hidden;">
            <div style="padding:14px 18px; display:flex; justify-content:space-between; align-items:center; box-shadow:var(--inset-sm);">
                <div class="tp-section-h">สินค้าขายดี 10 อันดับ</div>
                <a href="{{ route('admin.ecommerce.products.index', ['sort_by' => 'sales_count']) }}" style="font-size:12.5px; color:var(--deep1);">ดูสินค้า →</a>
            </div>
            <div style="overflow-x:auto;">
                <table style="width:100%; min-width:420px; border-collapse:collapse;">
                    <thead><tr><th style="{{ $th }}">#</th><th style="{{ $th }}">สินค้า</th><th style="{{ $th }} text-align:right;">ขาย</th><th style="{{ $th }} text-align:right;">ยอดขาย</th></tr></thead>
                    <tbody>
                        @forelse($topProducts->where('total_sales', '>', 0) as $i => $product)
                            <tr style="border-top:1px solid color-mix(in srgb, var(--ink2) 12%, transparent);">
                                <td style="{{ $td }} width:34px;" class="tp-num">{{ $loop->iteration }}</td>
                                <td style="{{ $td }}"><a href="{{ route('admin.ecommerce.products.show', $product) }}" style="color:var(--ink); text-decoration:none; display:block; max-width:220px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $product->name }}</a></td>
                                <td style="{{ $td }} text-align:right;" class="tp-num">{{ number_format((int) $product->total_sales) }}</td>
                                <td style="{{ $td }} text-align:right; font-weight:700;" class="tp-num">{{ $money($product->total_revenue) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" style="padding:30px; text-align:center; color:var(--ink2);">ยังไม่มีสินค้าที่ขายได้ในช่วงนี้</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- หมวดหมู่ --}}
        <div class="tp-card">
            <div class="tp-section-h" style="margin-bottom:12px;"><i class="fas fa-tags" style="color:var(--accent1);"></i> ยอดขายตามหมวดหมู่</div>
            @php $catRows = collect($categoryPerformance ?? [])->where('total_revenue', '>', 0)->take(10); @endphp
            @forelse($catRows as $cat)
                <div style="margin-bottom:10px;">
                    <div style="display:flex; justify-content:space-between; gap:8px; font-size:13px;">
                        <span style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $cat->name }}</span>
                        <span class="tp-num" style="white-space:nowrap;"><strong>{{ $money($cat->total_revenue) }}</strong> <span style="color:var(--ink2);">{{ $cat->percentage }}%</span></span>
                    </div>
                    <div class="tp-inset-sm" style="height:8px; border-radius:6px; margin-top:5px; overflow:hidden;">
                        <div style="height:100%; width:{{ min(100, (float) $cat->percentage) }}%; background:linear-gradient(90deg,var(--accent1),var(--accent2)); border-radius:6px;"></div>
                    </div>
                </div>
            @empty
                <div style="font-size:13px; color:var(--ink2);">ยังไม่มียอดขายตามหมวดหมู่ในช่วงนี้</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
