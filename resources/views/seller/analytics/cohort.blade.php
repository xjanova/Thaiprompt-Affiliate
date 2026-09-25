@extends('layouts.seller-v4')

@section('title', 'ลูกค้ากลับมาซื้อซ้ำ (Cohort)')

@php
    $cohortRows = collect($cohorts ?? []);
    $maxMonths = (int) $cohortRows->map(fn ($c) => count($c['retention'] ?? []))->max();
    $maxMonths = max(1, min(7, $maxMonths));
    $th = 'padding:10px 12px; text-align:center; font-size:10.5px; font-weight:700; color:var(--ink2); letter-spacing:.3px; white-space:nowrap;';
    $td = 'padding:8px 10px; font-size:12.5px; color:var(--ink); text-align:center; white-space:nowrap;';
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    <x-seller-kit.header title="ลูกค้ากลับมาซื้อซ้ำ" icon="🔁" crumb="ร้านค้า · วิเคราะห์"
                         subtitle="ติดตามว่าลูกค้าที่เริ่มซื้อในแต่ละเดือน กลับมาซื้ออีกกี่เปอร์เซ็นต์ในเดือนถัด ๆ ไป (6 เดือนล่าสุด)" />

    @include('seller.analytics.partials.nav')

    @if($cohortRows->isNotEmpty())
        <div class="tp-card" style="padding:0; overflow:hidden;">
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:separate; border-spacing:4px; min-width:620px; padding:8px;">
                    <thead>
                        <tr>
                            <th style="{{ $th }} text-align:left;">เดือนที่เริ่มซื้อ</th>
                            <th style="{{ $th }}">จำนวนออเดอร์</th>
                            @for($m = 0; $m < $maxMonths; $m++)
                                <th style="{{ $th }}">{{ $m === 0 ? 'เดือนแรก' : 'เดือนที่ '.($m + 1) }}</th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($cohortRows as $cohort)
                            <tr>
                                <td style="{{ $td }} text-align:left; font-weight:700;" class="tp-num">{{ \Carbon\Carbon::createFromFormat('Y-m', $cohort['cohort'])->locale('th')->translatedFormat('M Y') }}</td>
                                <td style="{{ $td }} font-weight:800; color:var(--deep1);" class="tp-num">{{ number_format((int) $cohort['size']) }}</td>
                                @for($m = 0; $m < $maxMonths; $m++)
                                    @php
                                        $cell = $cohort['retention'][$m] ?? null;
                                        $rate = $cell ? (float) $cell['rate'] : null;
                                    @endphp
                                    <td class="tp-num" style="{{ $td }} border-radius:10px; font-weight:700; {{ is_null($rate) ? 'color:var(--ink2);' : 'background:color-mix(in srgb, var(--accent1) '.round(8 + min(100, $rate) * 0.8).'%, transparent);' }}">
                                        {{ is_null($rate) ? '—' : number_format($rate, 0).'%' }}
                                    </td>
                                @endfor
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="tp-card" style="background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 14%, transparent), transparent 70%);">
            <div class="tp-section-h">📖 วิธีอ่านตาราง</div>
            <ul style="margin:8px 0 0; padding-left:18px; font-size:13px; line-height:1.8;">
                <li>แต่ละแถว = กลุ่มออเดอร์ที่เริ่มซื้อในเดือนนั้น</li>
                <li>“เดือนแรก” = เดือนที่เริ่มซื้อ (ปกติ 100%)</li>
                <li>ช่องถัดไป = สัดส่วนที่กลับมาซื้ออีกในเดือนนั้น ยิ่งสีเข้ม ยิ่งกลับมาซื้อมาก</li>
            </ul>
        </div>
    @else
        <div class="tp-card">
            <x-seller-kit.empty icon="🔁" title="ยังมีข้อมูลไม่พอ" text="เมื่อร้านมีออเดอร์ที่ชำระเงินแล้วหลายเดือน ตารางการกลับมาซื้อซ้ำจะแสดงที่นี่" />
        </div>
    @endif
</div>
@endsection
