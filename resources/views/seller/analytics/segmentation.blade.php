@extends('layouts.seller-v4')

@section('title', 'กลุ่มลูกค้า (RFM)')

@php
    // กลุ่มลูกค้าตามคะแนน RFM (ซื้อล่าสุด · ความถี่ · ยอดเงิน)
    $segmentMeta = [
        'champions' => ['🏆', 'ลูกค้าตัวจริง', 'ซื้อบ่อย ซื้อเยอะ และเพิ่งซื้อ — ให้สิทธิพิเศษ/ของแถมเพื่อรักษาไว้', 'gold'],
        'loyal' => ['💙', 'ลูกค้าประจำ', 'กลับมาซื้อสม่ำเสมอ — ชวนลองสินค้าใหม่หรือแพ็กคู่', 'info'],
        'potential' => ['🌱', 'มีแววเป็นลูกค้าประจำ', 'เพิ่งเริ่มซื้อ — ส่งคูปองครั้งถัดไปให้กลับมาซื้ออีก', 'ok'],
        'at_risk' => ['⚠️', 'กำลังจะห่างหาย', 'ไม่ได้ซื้อมาสักพัก — ส่งโปรเฉพาะกลุ่มชวนกลับมา', 'warn'],
        'lost' => ['💔', 'หายไปแล้ว', 'นานมากแล้วที่ไม่ได้ซื้อ — ลองโปรแรง ๆ หรือสอบถามเหตุผล', 'muted'],
    ];
    $segments = $rfmSegments ?? [];
    $totalCustomers = collect($segmentMeta)->keys()->sum(fn ($k) => count($segments[$k] ?? []));
    $th = 'padding:11px 14px; text-align:left; font-size:10.5px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px; white-space:nowrap;';
    $td = 'padding:12px 14px; font-size:13px; color:var(--ink); white-space:nowrap;';
    $row = 'border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);';
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    <x-seller-kit.header title="กลุ่มลูกค้า (RFM)" icon="👥" crumb="ร้านค้า · วิเคราะห์"
                         subtitle="แบ่งลูกค้าที่ชำระเงินแล้วตาม ซื้อล่าสุดเมื่อไร · ซื้อบ่อยแค่ไหน · ใช้เงินเท่าไร เพื่อทำโปรให้ตรงกลุ่ม" />

    @include('seller.analytics.partials.nav')

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(170px,1fr)); gap:14px;">
        @foreach($segmentMeta as $key => [$sIcon, $sLabel, $sHint, $sTone])
            <x-seller-kit.stat :label="$sLabel" :value="number_format(count($segments[$key] ?? [])).' คน'" :icon="$sIcon" :tone="$sTone"
                               :hint="$totalCustomers > 0 ? number_format(count($segments[$key] ?? []) / $totalCustomers * 100, 0).'% ของลูกค้าทั้งหมด' : null" />
        @endforeach
    </div>

    @if($totalCustomers === 0)
        <div class="tp-card">
            <x-seller-kit.empty icon="👥" title="ยังไม่มีลูกค้าที่ชำระเงิน" text="เมื่อมีออเดอร์ที่ชำระเงินแล้ว ระบบจะจัดกลุ่มลูกค้าให้อัตโนมัติ" />
        </div>
    @endif

    @foreach($segmentMeta as $key => [$sIcon, $sLabel, $sHint, $sTone])
        @if(! empty($segments[$key]))
            <div class="tp-card" style="padding:0; overflow:hidden;">
                <div style="padding:16px 18px;">
                    <div class="tp-section-h">{{ $sIcon }} {{ $sLabel }} ({{ number_format(count($segments[$key])) }})</div>
                    <div style="font-size:12px; color:var(--ink2); margin-top:3px;">💡 {{ $sHint }}</div>
                </div>
                <div style="overflow-x:auto;">
                    <table style="width:100%; border-collapse:collapse; min-width:560px;">
                        <thead>
                            <tr style="background:color-mix(in srgb, var(--ink2) 8%, transparent);">
                                <th style="{{ $th }}">ลูกค้า</th>
                                <th style="{{ $th }} text-align:right;">ซื้อล่าสุด</th>
                                <th style="{{ $th }} text-align:right;">จำนวนครั้ง</th>
                                <th style="{{ $th }} text-align:right;">ยอดซื้อรวม</th>
                                <th style="{{ $th }} text-align:center;">คะแนน RFM</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(array_slice($segments[$key], 0, 10) as $customer)
                                <tr style="{{ $row }}">
                                    <td style="{{ $td }} font-weight:700;">{{ $customer['user_name'] ?? '—' }}</td>
                                    <td style="{{ $td }} text-align:right;" class="tp-num">{{ number_format((int) abs($customer['recency'] ?? 0)) }} วันก่อน</td>
                                    <td style="{{ $td }} text-align:right;" class="tp-num">{{ number_format((int) ($customer['frequency'] ?? 0)) }}</td>
                                    <td style="{{ $td }} text-align:right; font-weight:800;" class="tp-num">฿{{ number_format((float) ($customer['monetary'] ?? 0), 2) }}</td>
                                    <td style="{{ $td }} text-align:center;"><x-seller-kit.pill :tone="$sTone">{{ number_format((float) ($customer['rfm_score'] ?? 0), 1) }} / 5</x-seller-kit.pill></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if(count($segments[$key]) > 10)
                    <div style="padding:10px 18px; font-size:12px; color:var(--ink2); border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">และอีก {{ number_format(count($segments[$key]) - 10) }} คน</div>
                @endif
            </div>
        @endif
    @endforeach
</div>
@endsection
