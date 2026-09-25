@extends('layouts.seller-v4')

@section('title', 'AI วิเคราะห์ร้านค้า')

@php
    $typeMap = ['success' => ['✅', 'ok'], 'warning' => ['⚠️', 'warn'], 'danger' => ['🚨', 'bad'], 'info' => ['💡', 'info']];
    $prioMap = ['high' => ['สำคัญมาก', 'bad'], 'medium' => ['ปานกลาง', 'warn'], 'low' => ['ทั่วไป', 'muted']];
    $stageNames = ['Visitors' => 'ผู้เข้าชมร้าน', 'Product Views' => 'ดูหน้าสินค้า', 'Add to Cart' => 'ใส่ตะกร้า', 'Orders' => 'สั่งซื้อ', 'Completed' => 'ได้รับสินค้าแล้ว'];
    $metricNames = ['page_views' => 'การเข้าชม', 'unique_visitors' => 'ผู้เยี่ยมชม', 'orders_count' => 'ออเดอร์', 'total_sales' => 'รายได้', 'conversion_rate' => 'อัตราแปลง'];

    $forecast = $revenueForecast ?? ['predictions' => [], 'confidence' => 0, 'trend' => 'insufficient_data'];
    $hasForecast = ($forecast['confidence'] ?? 0) > 0 && ! empty($forecast['predictions']);
    $preds = collect($forecast['predictions'] ?? []);
    $predMax = max(1, (float) $preds->max('predicted_revenue'));
    $next7 = (float) $preds->take(7)->sum('predicted_revenue');
    $trendUp = ($forecast['trend'] ?? '') === 'up';

    $th = 'padding:11px 14px; text-align:left; font-size:10.5px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px; white-space:nowrap;';
    $td = 'padding:12px 14px; font-size:13px; color:var(--ink); white-space:nowrap;';
    $row = 'border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);';
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    <x-seller-kit.header title="AI วิเคราะห์ร้านค้า" icon="🤖" crumb="ร้านค้า · วิเคราะห์"
                         subtitle="สรุปจุดแข็ง จุดที่ควรปรับ คาดการณ์รายได้ และเส้นทางการซื้อของลูกค้า จากข้อมูลจริงของร้าน" />

    @include('seller.analytics.partials.nav')

    {{-- คำแนะนำจาก AI --}}
    @if(! empty($insights))
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(300px,1fr)); gap:16px;">
            @foreach($insights as $insight)
                @php
                    [$iIcon, $iTone] = $typeMap[$insight['type'] ?? 'info'] ?? $typeMap['info'];
                    [$pLabel, $pTone] = $prioMap[$insight['priority'] ?? 'low'] ?? $prioMap['low'];
                    $iColor = ['ok' => 'var(--tp-ok, #5aa07e)', 'warn' => 'var(--tp-warn, #e0a52e)', 'bad' => 'var(--tp-bad, #d9534f)', 'info' => 'var(--tp-info, #5689b8)'][$iTone];
                @endphp
                <div class="tp-card" style="border-left:4px solid {{ $iColor }}; display:flex; flex-direction:column; gap:8px;">
                    <div style="display:flex; justify-content:space-between; gap:10px; align-items:flex-start;">
                        <div style="font-weight:800; font-size:14.5px;">{{ $iIcon }} {{ $insight['title'] ?? '' }}</div>
                        <x-seller-kit.pill :tone="$pTone">{{ $pLabel }}</x-seller-kit.pill>
                    </div>
                    <div style="font-size:13px; line-height:1.65;">{{ $insight['message'] ?? '' }}</div>
                    @if(! empty($insight['action']))
                        <div class="tp-inset-sm" style="border-radius:12px; padding:10px 12px; font-size:12.5px; line-height:1.6;">
                            <span style="font-weight:700;">💡 ควรทำ:</span> {{ $insight['action'] }}
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <div class="tp-card">
            <x-seller-kit.empty icon="🤖" title="ยังมีข้อมูลไม่พอให้ AI วิเคราะห์" text="ต้องมีสถิติร้านอย่างน้อย 7 วัน ระบบรวบรวมข้อมูลให้อัตโนมัติทุกคืน — กลับมาดูอีกครั้งในสัปดาห์หน้า" />
        </div>
    @endif

    {{-- คาดการณ์รายได้ --}}
    <div class="tp-card">
        <div style="display:flex; flex-wrap:wrap; justify-content:space-between; gap:12px; align-items:flex-start;">
            <div>
                <div class="tp-section-h">📈 คาดการณ์รายได้ 30 วันข้างหน้า</div>
                <div style="font-size:12px; color:var(--ink2); margin-top:3px;">คำนวณแนวโน้มจากยอดขายย้อนหลังสูงสุด 90 วัน</div>
            </div>
            @if($hasForecast)
                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                    <x-seller-kit.pill :tone="$trendUp ? 'ok' : 'bad'">{{ $trendUp ? '↗ แนวโน้มขาขึ้น' : '↘ แนวโน้มขาลง' }}</x-seller-kit.pill>
                    <x-seller-kit.pill tone="info">ความแม่นยำ {{ number_format((float) $forecast['confidence'], 0) }}%</x-seller-kit.pill>
                </div>
            @endif
        </div>

        @if($hasForecast)
            <div style="overflow-x:auto; margin-top:14px;">
                <div class="tp-bars" style="height:170px; gap:4px; min-width:{{ max(300, $preds->count() * 20) }}px;">
                    @foreach($preds as $i => $p)
                        @php
                            $pv = (float) ($p['predicted_revenue'] ?? 0);
                        @endphp
                        <div class="col" title="{{ \Carbon\Carbon::parse($p['date'])->format('d/m') }} · ฿{{ number_format($pv, 2) }}">
                            <div class="stack"><div class="bar {{ $i < 7 ? 'a' : 'b' }}" style="height:{{ max(2, $pv / $predMax * 100) }}%; width:70%;"></div></div>
                            <div class="lbl">{{ $i % 5 === 0 ? \Carbon\Carbon::parse($p['date'])->format('d/m') : '' }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(170px,1fr)); gap:10px; margin-top:14px;">
                <div class="tp-inset-sm" style="border-radius:14px; padding:12px;">
                    <div style="font-size:11.5px; color:var(--ink2);">รายได้เฉลี่ยต่อวัน (ที่ผ่านมา)</div>
                    <div class="tp-num" style="font-weight:800; font-size:19px;">฿{{ number_format((float) ($forecast['daily_average'] ?? 0), 2) }}</div>
                </div>
                <div class="tp-inset-sm" style="border-radius:14px; padding:12px;">
                    <div style="font-size:11.5px; color:var(--ink2);">คาดการณ์ 7 วันข้างหน้า</div>
                    <div class="tp-num" style="font-weight:800; font-size:19px; color:var(--deep1);">฿{{ number_format($next7, 2) }}</div>
                </div>
                <div class="tp-inset-sm" style="border-radius:14px; padding:12px;">
                    <div style="font-size:11.5px; color:var(--ink2);">คาดการณ์ 30 วัน</div>
                    <div class="tp-num" style="font-weight:800; font-size:19px;">฿{{ number_format((float) $preds->sum('predicted_revenue'), 2) }}</div>
                </div>
            </div>
            <div style="font-size:11px; color:var(--ink2); margin-top:8px;">ตัวเลขนี้เป็นการประมาณจากแนวโน้มเท่านั้น ไม่ใช่การรับประกันรายได้</div>
        @else
            <x-seller-kit.empty icon="📈" title="ต้องมีข้อมูลอย่างน้อย 14 วัน" text="เมื่อร้านมียอดขายต่อเนื่อง ระบบจะคาดการณ์รายได้ล่วงหน้าให้อัตโนมัติ" />
        @endif
    </div>

    {{-- ความผิดปกติ --}}
    @if(! empty($anomalies))
        <div class="tp-card" style="padding:0; overflow:hidden;">
            <div style="padding:16px 18px;">
                <div class="tp-section-h">🔍 วันที่ตัวเลขผิดปกติ</div>
                <div style="font-size:12px; color:var(--ink2); margin-top:3px;">วันที่ค่าแตกต่างจากปกติมาก (สูงหรือต่ำผิดสังเกต) ลองดูว่ามีโปรหรือปัญหาอะไรในวันนั้น</div>
            </div>
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; min-width:620px;">
                    <thead>
                        <tr style="background:color-mix(in srgb, var(--ink2) 8%, transparent);">
                            <th style="{{ $th }}">วันที่</th>
                            <th style="{{ $th }}">ตัวชี้วัด</th>
                            <th style="{{ $th }} text-align:right;">ค่าจริง</th>
                            <th style="{{ $th }} text-align:right;">ค่าปกติ</th>
                            <th style="{{ $th }} text-align:center;">ลักษณะ</th>
                            <th style="{{ $th }} text-align:center;">ระดับ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($anomalies as $anomaly)
                            <tr style="{{ $row }}">
                                <td style="{{ $td }}" class="tp-num">{{ \Carbon\Carbon::parse($anomaly['date'])->format('d/m/Y') }}</td>
                                <td style="{{ $td }}">{{ $metricNames[$anomaly['metric']] ?? str_replace('_', ' ', $anomaly['metric']) }}</td>
                                <td style="{{ $td }} text-align:right; font-weight:800;" class="tp-num">{{ number_format((float) $anomaly['value'], 2) }}</td>
                                <td style="{{ $td }} text-align:right; color:var(--ink2);" class="tp-num">{{ number_format((float) $anomaly['expected'], 2) }}</td>
                                <td style="{{ $td }} text-align:center;"><x-seller-kit.pill :tone="$anomaly['type'] === 'spike' ? 'ok' : 'bad'">{{ $anomaly['type'] === 'spike' ? '⬆ พุ่งขึ้น' : '⬇ ตกลง' }}</x-seller-kit.pill></td>
                                <td style="{{ $td }} text-align:center;"><x-seller-kit.pill :tone="($anomaly['severity'] ?? '') === 'high' ? 'bad' : 'warn'">{{ ($anomaly['severity'] ?? '') === 'high' ? 'มาก' : 'ปานกลาง' }}</x-seller-kit.pill></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Funnel --}}
    <div class="tp-card">
        <div class="tp-section-h">🔄 เส้นทางการซื้อของลูกค้า (30 วันล่าสุด)</div>
        <div style="display:flex; flex-direction:column; gap:12px; margin-top:14px;">
            @foreach($funnel as $stage)
                @php
                    $pct = min(100, max(0, (float) ($stage['percentage'] ?? 0)));
                @endphp
                <div>
                    <div style="display:flex; justify-content:space-between; gap:10px; font-size:13px;">
                        <span style="font-weight:700;">{{ $stageNames[$stage['stage']] ?? $stage['stage'] }}</span>
                        <span class="tp-num"><strong>{{ number_format((int) $stage['count']) }}</strong> <span style="color:var(--ink2);">({{ number_format($pct, 1) }}%)</span></span>
                    </div>
                    <div class="tp-inset-sm" style="height:14px; border-radius:99px; overflow:hidden; margin-top:6px;">
                        <div style="height:100%; width:{{ max(1.5, $pct) }}%; border-radius:99px; background:linear-gradient(90deg, var(--accent1), var(--accent2));"></div>
                    </div>
                    @if(($stage['drop_off'] ?? 0) > 0)
                        <div style="font-size:11.5px; color:var(--tp-bad, #d9534f); margin-top:3px;">หลุดออกที่ขั้นนี้ {{ number_format((float) $stage['drop_off'], 1) }}%</div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
