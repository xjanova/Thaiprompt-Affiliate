@extends('layouts.admin-v4')

@section('title', 'แดชบอร์ดอีคอมเมิร์ซ')

@php
    $c = [
        'ok' => 'var(--tp-ok,#5aa07e)',
        'bad' => 'var(--tp-bad,#d9534f)',
        'warn' => 'var(--tp-warn,#e0a52e)',
        'info' => 'var(--tp-info,#5689b8)',
        'violet' => 'var(--tp-violet,#8c6fd6)',
        'mute' => 'var(--ink2)',
    ];
    $pill = fn (string $color) => "background:color-mix(in srgb, {$color} 16%, transparent); color:{$color};";
    $th = 'padding:11px 14px; text-align:left; font-size:11px; font-weight:700; color:var(--ink2); letter-spacing:.3px; white-space:nowrap;';
    $td = 'padding:11px 14px; font-size:13px; color:var(--ink); vertical-align:middle;';
    $money = fn ($v, $d = 0) => '฿' . number_format((float) $v, $d);
    $thMonths = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

    // กราฟรายได้ 12 เดือน (CSS .tp-bars)
    $chartRows = collect($monthlyRevenue ?? [])->values();
    $chartMax = (float) ($chartRows->max('total') ?: 0);

    // สัดส่วนสถานะออเดอร์ (conic-gradient)
    $statusMeta = [
        'pending' => ['รอดำเนินการ', $c['warn']],
        'processing' => ['กำลังเตรียม', $c['info']],
        'completed' => ['สำเร็จ', $c['ok']],
        'cancelled' => ['ยกเลิก', $c['bad']],
    ];
    $statusTotal = array_sum(array_map('intval', $orderStatusData ?? []));
    $segments = [];
    $acc = 0;
    foreach ($statusMeta as $key => [$label, $color]) {
        $n = (int) ($orderStatusData[$key] ?? 0);
        if ($statusTotal > 0 && $n > 0) {
            $from = $acc / $statusTotal * 100;
            $acc += $n;
            $to = $acc / $statusTotal * 100;
            $segments[] = "{$color} ".round($from, 2).'% '.round($to, 2).'%';
        }
    }
    $donut = $segments ? 'conic-gradient('.implode(', ', $segments).')' : 'conic-gradient(color-mix(in srgb, var(--ink2) 25%, transparent) 0 100%)';

    $statusColor = [
        'pending' => $c['warn'], 'paid' => $c['info'], 'processing' => $c['info'], 'shipped' => $c['violet'],
        'delivered' => $c['ok'], 'completed' => $c['ok'], 'cancelled' => $c['bad'], 'refunded' => $c['mute'],
    ];
    $moneySplit = $moneySplit ?? null;
    $gpInfo = $gpInfo ?? null;
    $revenueRoute = collect(['admin.revenue-share.index', 'admin.revenue-share.edit', 'admin.pricing.index', 'admin.pricing-settings.index'])
        ->first(fn ($r) => \Illuminate\Support\Facades\Route::has($r));
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== หัวหน้า ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · อีคอมเมิร์ซ</div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">แดชบอร์ดอีคอมเมิร์ซ 🛒</h1>
            <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">ภาพรวมสินค้า ออเดอร์ ยอดขาย และส่วนแบ่งรายได้ของแพลตฟอร์ม</div>
        </div>
        <div style="display:flex; gap:9px; flex-wrap:wrap;">
            <a href="{{ route('admin.ecommerce.reports') }}" class="tp-btn tp-btn-sm"><i class="fas fa-chart-column"></i> รายงาน</a>
            <a href="{{ route('admin.storefront.vendor-stores.index') }}" class="tp-btn tp-btn-sm"><i class="fas fa-store"></i> ร้านค้า</a>
            <a href="{{ route('admin.ecommerce.orders.index') }}" class="tp-btn tp-btn-sm tp-btn-primary"><i class="fas fa-receipt"></i> คำสั่งซื้อ</a>
        </div>
    </div>

    {{-- ===== KPI หลัก ===== --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:16px;">
        @foreach([
            ['สินค้าทั้งหมด', number_format($stats['total_products'] ?? 0), 'fa-box', null, route('admin.ecommerce.products.index'), 'ใช้งาน '.number_format($stats['active_products'] ?? 0)],
            ['คำสั่งซื้อทั้งหมด', number_format($stats['total_orders'] ?? 0), 'fa-receipt', $c['info'], route('admin.ecommerce.orders.index'), 'เดือนนี้ '.(($orderGrowth ?? 0) >= 0 ? '+' : '').number_format($orderGrowth ?? 0, 1).'%'],
            ['รายได้ (ชำระแล้ว)', $money($stats['total_revenue'] ?? 0), 'fa-sack-dollar', $c['ok'], route('admin.ecommerce.reports'), 'เดือนนี้ '.$money($stats['monthly_revenue'] ?? 0).' ('.(($revenueGrowth ?? 0) >= 0 ? '+' : '').number_format($revenueGrowth ?? 0, 1).'%)'],
            ['รอดำเนินการ', number_format($stats['pending_orders'] ?? 0), 'fa-clock', $c['warn'], route('admin.ecommerce.orders.index', ['status' => 'pending']), 'กำลังเตรียม '.number_format($stats['processing_orders'] ?? 0)],
        ] as [$label, $value, $icon, $color, $url, $sub])
            <a href="{{ $url }}" class="tp-card tp-card-hover" style="padding:18px; text-decoration:none; color:inherit;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <div class="tp-tile" style="width:44px; height:44px; font-size:18px; {{ $color ? 'background:'.$color.';' : '' }}"><i class="fas {{ $icon }}"></i></div>
                    <div style="min-width:0;">
                        <div class="tp-num" style="font-size:24px; font-weight:800; line-height:1.1; overflow-wrap:anywhere;">{{ $value }}</div>
                        <div style="font-size:12px; color:var(--ink2); margin-top:3px;">{{ $label }}</div>
                    </div>
                </div>
                <div style="font-size:11.5px; color:var(--ink2); margin-top:10px;">{{ $sub }}</div>
            </a>
        @endforeach
    </div>

    {{-- ===== ส่วนแบ่งรายได้แพลตฟอร์ม ===== --}}
    @if($moneySplit)
        <div class="tp-card" style="background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 14%, var(--card-bg)), var(--card-bg) 70%);">
            <div style="display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap; align-items:center; margin-bottom:14px;">
                <div>
                    <div class="tp-section-h"><i class="fas fa-scale-balanced" style="color:var(--accent1);"></i> เงินที่แบ่งจากออเดอร์ร้านค้า (สะสม)</div>
                    <div style="font-size:12px; color:var(--ink2); margin-top:3px;">รวมจากกระเป๋าแพลตฟอร์ม — แบ่งอัตโนมัติเมื่อออเดอร์ชำระเงินแล้ว</div>
                </div>
                <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                    @if($gpInfo)
                        @if($gpInfo['promo_active'])
                            <span class="tp-pill" style="{{ $pill($c['ok']) }}">🎉 โปรฯ GP ฟรี{{ $gpInfo['promo_ends_at'] ? ' ถึง '.$gpInfo['promo_ends_at']->format('d/m/Y') : '' }}</span>
                        @else
                            <span class="tp-pill tp-pill-soft">GP กลาง {{ rtrim(rtrim(number_format($gpInfo['default_rate'], 2), '0'), '.') }}% · ขั้นต่ำ {{ rtrim(rtrim(number_format($gpInfo['min_rate'], 2), '0'), '.') }}%</span>
                        @endif
                        <span class="tp-pill tp-pill-soft">{{ $gpInfo['mlm_enabled'] ? 'ระบบค่าแนะนำ: เปิด' : 'ระบบค่าแนะนำ: ปิด' }}</span>
                    @endif
                    @if($revenueRoute)
                        <a href="{{ route($revenueRoute) }}" class="tp-btn tp-btn-sm"><i class="fas fa-sliders"></i> ตั้งค่าส่วนแบ่ง & GP</a>
                    @endif
                </div>
            </div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:12px;">
                @foreach([
                    ['ค่า GP แพลตฟอร์ม', $moneySplit['gp'], $c['info'], 'fa-percent'],
                    ['VAT ที่เก็บแทน', $moneySplit['vat'], $c['violet'], 'fa-file-invoice'],
                    ['กองทุนผู้แนะนำ', $moneySplit['referral_pool'], $c['warn'], 'fa-people-arrows'],
                    ['พักเงินให้ผู้ขาย', $moneySplit['seller_escrow'], $c['ok'], 'fa-vault'],
                    ['รายได้ร้านทางการ', $moneySplit['official_shop'], $c['mute'], 'fa-crown'],
                ] as [$label, $value, $color, $icon])
                    <div class="tp-well" style="padding:12px 14px;">
                        <div style="font-size:11.5px; color:var(--ink2); font-weight:600;"><i class="fas {{ $icon }}" style="color:{{ $color }};"></i> {{ $label }}</div>
                        <div class="tp-num" style="font-size:19px; font-weight:800; margin-top:3px;">{{ $money($value, 2) }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ===== กราฟ ===== --}}
    <div style="display:flex; flex-wrap:wrap; gap:16px;">
        <div class="tp-card" style="flex:2 1 420px; min-width:0;">
            <div style="display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap; margin-bottom:14px;">
                <div class="tp-section-h"><i class="fas fa-chart-column" style="color:var(--accent1);"></i> รายได้ 12 เดือนล่าสุด</div>
                <span class="tp-pill tp-pill-soft tp-num">รวม {{ $money($chartRows->sum('total')) }}</span>
            </div>
            @if($chartMax > 0)
                <div style="overflow-x:auto;">
                    <div class="tp-bars" style="min-width:{{ max(320, $chartRows->count() * 46) }}px; height:190px;">
                        @foreach($chartRows as $i => $r)
                            @php
                                $parts = explode('-', (string) $r->month);
                                $mLabel = isset($parts[1]) ? ($thMonths[(int) $parts[1]] ?? $r->month) : $r->month;
                                $h = max(3, round(((float) $r->total / $chartMax) * 100));
                            @endphp
                            <div class="col" title="{{ $r->month }} · {{ $money($r->total) }} · {{ number_format((int) $r->orders) }} ออเดอร์">
                                <div class="stack"><i class="bar a" style="height:{{ $h }}%; animation-delay:{{ $i * 40 }}ms;"></i></div>
                                <span class="lbl">{{ $mLabel }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div style="text-align:center; color:var(--ink2); padding:40px 0; font-size:13px;">ยังไม่มีรายได้จากออเดอร์ที่ชำระแล้ว</div>
            @endif
        </div>

        <div class="tp-card" style="flex:1 1 280px; min-width:0;">
            <div class="tp-section-h" style="margin-bottom:14px;"><i class="fas fa-chart-pie" style="color:var(--accent1);"></i> สถานะออเดอร์</div>
            <div style="display:flex; align-items:center; gap:18px; flex-wrap:wrap;">
                <div style="width:130px; height:130px; border-radius:50%; background:{{ $donut }}; box-shadow:var(--raise); display:grid; place-items:center; flex:none;">
                    <div style="width:78px; height:78px; border-radius:50%; background:var(--card-bg); box-shadow:var(--inset-sm); display:grid; place-items:center;">
                        <div style="text-align:center;">
                            <div class="tp-num" style="font-size:20px; font-weight:800; line-height:1;">{{ number_format($statusTotal) }}</div>
                            <div style="font-size:10.5px; color:var(--ink2);">ออเดอร์</div>
                        </div>
                    </div>
                </div>
                <div style="flex:1; min-width:130px; display:flex; flex-direction:column; gap:8px;">
                    @foreach($statusMeta as $key => [$label, $color])
                        <a href="{{ route('admin.ecommerce.orders.index', ['status' => $key]) }}" style="display:flex; justify-content:space-between; gap:8px; font-size:13px; color:var(--ink); text-decoration:none;">
                            <span><i class="fas fa-circle" style="color:{{ $color }}; font-size:9px;"></i> {{ $label }}</span>
                            <span class="tp-num" style="font-weight:700;">{{ number_format((int) ($orderStatusData[$key] ?? 0)) }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- ===== ตัวเลขรอง ===== --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:14px;">
        @foreach([
            ['ใกล้หมดสต็อก', $stats['low_stock'] ?? 0, 'fa-triangle-exclamation', $c['warn'], route('admin.ecommerce.products.index', ['stock_status' => 'low_stock'])],
            ['สินค้าหมด', $stats['out_of_stock'] ?? 0, 'fa-circle-xmark', $c['bad'], route('admin.ecommerce.products.index', ['stock_status' => 'out_of_stock'])],
            ['ลูกค้าที่สั่งซื้อ', $stats['total_customers'] ?? 0, 'fa-users', $c['info'], null],
            ['หมวดหมู่', $stats['total_categories'] ?? 0, 'fa-tags', $c['violet'], route('admin.ecommerce.categories.index')],
            ['รีวิว (เฉลี่ย '.number_format((float) ($stats['average_rating'] ?? 0), 1).'★)', $stats['total_reviews'] ?? 0, 'fa-star', $c['warn'], route('admin.ecommerce.reviews.index')],
        ] as [$label, $value, $icon, $color, $url])
            @if($url)<a href="{{ $url }}" class="tp-card tp-card-hover" style="padding:14px 16px; text-decoration:none; color:inherit;">@else<div class="tp-card" style="padding:14px 16px;">@endif
                <div style="display:flex; align-items:center; gap:10px;">
                    <i class="fas {{ $icon }}" style="color:{{ $color }}; font-size:17px;"></i>
                    <div>
                        <div class="tp-num" style="font-size:19px; font-weight:800; line-height:1;">{{ number_format((int) $value) }}</div>
                        <div style="font-size:11.5px; color:var(--ink2); margin-top:2px;">{{ $label }}</div>
                    </div>
                </div>
            @if($url)</a>@else</div>@endif
        @endforeach
    </div>

    {{-- ===== ออเดอร์ล่าสุด + สต็อกต่ำ ===== --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,340px),1fr)); gap:16px;">
        <div class="tp-card" style="padding:0; overflow:hidden;">
            <div style="padding:14px 18px; display:flex; justify-content:space-between; align-items:center; box-shadow:var(--inset-sm);">
                <div class="tp-section-h">ออเดอร์ล่าสุด</div>
                <a href="{{ route('admin.ecommerce.orders.index') }}" style="font-size:12.5px; color:var(--deep1);">ดูทั้งหมด →</a>
            </div>
            <div style="overflow-x:auto;">
                <table style="width:100%; min-width:460px; border-collapse:collapse;">
                    <tbody>
                        @forelse($recentOrders as $order)
                            <tr style="border-top:1px solid color-mix(in srgb, var(--ink2) 12%, transparent);">
                                <td style="{{ $td }}">
                                    <a href="{{ route('admin.ecommerce.orders.show', $order) }}" class="tp-num" style="font-weight:700; color:var(--deep1); text-decoration:none;">#{{ $order->order_number }}</a>
                                    <div style="font-size:11.5px; color:var(--ink2);">{{ $order->user?->name ?? 'ไม่ระบุ' }} · {{ $order->created_at?->format('d/m H:i') }}</div>
                                </td>
                                <td style="{{ $td }} text-align:right; white-space:nowrap;">
                                    <div class="tp-num" style="font-weight:700;">{{ $money($order->total_amount, 2) }}</div>
                                    <span class="tp-pill" style="{{ $pill($statusColor[$order->status] ?? $c['mute']) }}">{{ $order->status_label }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr><td style="padding:34px; text-align:center; color:var(--ink2);">ยังไม่มีออเดอร์</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="tp-card" style="padding:0; overflow:hidden;">
            <div style="padding:14px 18px; display:flex; justify-content:space-between; align-items:center; box-shadow:var(--inset-sm);">
                <div class="tp-section-h">สินค้าใกล้หมด</div>
                <a href="{{ route('admin.ecommerce.products.index', ['stock_status' => 'low_stock']) }}" style="font-size:12.5px; color:var(--deep1);">ดูทั้งหมด →</a>
            </div>
            <div style="overflow-x:auto;">
                <table style="width:100%; min-width:420px; border-collapse:collapse;">
                    <thead>
                        <tr><th style="{{ $th }}">สินค้า</th><th style="{{ $th }} text-align:right;">คงเหลือ</th><th style="{{ $th }}"></th></tr>
                    </thead>
                    <tbody>
                        @forelse($lowStockProducts as $product)
                            <tr style="border-top:1px solid color-mix(in srgb, var(--ink2) 12%, transparent);">
                                <td style="{{ $td }}">
                                    <div style="font-weight:600; max-width:230px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $product->name }}</div>
                                    <div style="font-size:11.5px; color:var(--ink2);">SKU: {{ $product->sku ?: '-' }}</div>
                                </td>
                                <td style="{{ $td }} text-align:right;">
                                    <span class="tp-num" style="font-weight:800; color:{{ (int) $product->stock_quantity <= 0 ? $c['bad'] : $c['warn'] }};">{{ number_format((int) $product->stock_quantity) }}</span>
                                </td>
                                <td style="{{ $td }} text-align:right;"><a href="{{ route('admin.ecommerce.products.show', $product) }}" class="tp-btn tp-btn-sm">ดู</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="3" style="padding:34px; text-align:center; color:var(--ink2);">สต็อกสินค้าปกติทุกรายการ 👍</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ===== สินค้าขายดี ===== --}}
    @if(isset($topProducts) && $topProducts->where('total_sales', '>', 0)->isNotEmpty())
        <div class="tp-card">
            <div class="tp-section-h" style="margin-bottom:12px;"><i class="fas fa-fire" style="color:var(--accent1);"></i> สินค้าขายดี</div>
            @php $topMax = max(1, (int) $topProducts->max('total_sales')); @endphp
            <div style="display:flex; flex-direction:column; gap:10px;">
                @foreach($topProducts->where('total_sales', '>', 0) as $product)
                    <a href="{{ route('admin.ecommerce.products.show', $product) }}" style="display:grid; grid-template-columns:minmax(0,1fr) 90px; gap:10px; align-items:center; text-decoration:none; color:var(--ink);">
                        <div style="min-width:0;">
                            <div style="font-size:13px; font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $product->name }}</div>
                            <div class="tp-inset-sm" style="height:8px; border-radius:6px; margin-top:5px; overflow:hidden;">
                                <div style="height:100%; width:{{ round(((int) $product->total_sales / $topMax) * 100) }}%; background:linear-gradient(90deg,var(--accent1),var(--accent2)); border-radius:6px;"></div>
                            </div>
                        </div>
                        <div class="tp-num" style="text-align:right; font-weight:700;">{{ number_format((int) $product->total_sales) }} ชิ้น</div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
