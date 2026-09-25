@extends('layouts.seller-v4')

@section('title', 'สถานะร้าน Premium')

@php
    $history = $premiumHistory ?? null;
    $statusMap = ['active' => ['เป็นร้าน Premium', 'ok'], 'pending' => ['รอพิจารณา', 'warn'], 'expired' => ['หมดอายุ', 'muted'], 'rejected' => ['ไม่ผ่านการพิจารณา', 'bad'], 'revoked' => ['ถูกถอดสถานะ', 'bad']];
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    <x-seller-kit.header title="สถานะร้าน Premium" icon="💎" crumb="ร้านค้า · การตลาด · รางวัลร้าน"
                         subtitle="ร้าน Premium ได้ป้ายพิเศษ ขึ้นหน้าแนะนำร้าน และลูกค้าเชื่อใจมากขึ้น — ระบบ AI คัดเลือกจากคะแนนร้านอัตโนมัติ">
        <a href="{{ route('seller.achievements.index') }}" class="tp-btn tp-btn-sm">← รางวัลและสถานะ</a>
    </x-seller-kit.header>

    @include('seller.marketing.partials.nav')

    @if($history)
        @php
            [$hLabel, $hTone] = $statusMap[$history->status] ?? [$history->status, 'muted'];
        @endphp
        <div class="tp-card" style="display:flex; flex-wrap:wrap; gap:14px; align-items:center;">
            <span class="tp-tile" style="width:54px; height:54px; font-size:26px;" aria-hidden="true">💎</span>
            <div style="flex:1; min-width:220px;">
                <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                    <span style="font-weight:800; font-size:15px;">{{ $history->featured_label ?: 'สถานะ Premium ของร้าน' }}</span>
                    <x-seller-kit.pill :tone="$hTone">{{ $hLabel }}</x-seller-kit.pill>
                </div>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">
                    @if($history->approved_at)อนุมัติเมื่อ {{ \Illuminate\Support\Carbon::parse($history->approved_at)->format('d/m/Y') }}@endif
                    @if($history->expires_at) · หมดอายุ {{ \Illuminate\Support\Carbon::parse($history->expires_at)->format('d/m/Y') }}@endif
                    @if($history->ai_score) · คะแนนตอนคัดเลือก {{ number_format((float) $history->ai_score, 1) }}@endif
                </div>
                @if($history->rejection_reason)
                    <div style="font-size:12.5px; color:var(--tp-bad, #d9534f); margin-top:4px;">เหตุผล: {{ $history->rejection_reason }}</div>
                @endif
            </div>
        </div>
    @endif

    @include('seller.achievements.partials.premium-progress', ['premiumReport' => $premiumReport, 'showCriteria' => true])

    <div class="tp-card" style="background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 14%, transparent), transparent 70%);">
        <div class="tp-section-h">🌟 สิทธิพิเศษของร้าน Premium</div>
        <ul style="margin:8px 0 0; padding-left:18px; font-size:13px; line-height:1.8;">
            <li>ป้าย Premium บนหน้าร้านและการ์ดสินค้า</li>
            <li>มีสิทธิ์ขึ้นส่วน “ร้านแนะนำ” ของหน้า Storefront</li>
            <li>ได้รับ Trophy พิเศษเฉพาะร้าน Premium</li>
        </ul>
    </div>
</div>
@endsection
