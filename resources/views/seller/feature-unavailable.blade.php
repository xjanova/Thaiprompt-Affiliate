@extends('layouts.seller-v4')

{{--
 | หน้า "ฟีเจอร์นี้ยังไม่เปิดให้บริการ" ของแผงร้านค้า V4
 | ใช้แทนหน้า 500 สำหรับฟีเจอร์ที่ยังเป็นโครง/ข้อมูลจำลอง (ยังไม่พร้อมเปิดตัว)
 | ตัวแปร (ทุกตัวมีค่าเริ่มต้น): $feature, $icon, $description, $backUrl, $backLabel
 --}}

@section('title', 'ฟีเจอร์นี้ยังไม่เปิดให้บริการ')

@php
    $feature = $feature ?? 'ฟีเจอร์นี้';
    $icon = $icon ?? '🚧';
    $description = $description ?? 'ทีมงานกำลังพัฒนาให้พร้อมใช้งานจริง เมื่อเปิดให้บริการแล้วจะแจ้งให้ทราบผ่านการแจ้งเตือนของร้าน';
    $backUrl = $backUrl ?? route('seller.dashboard');
    $backLabel = $backLabel ?? 'กลับแดชบอร์ดร้าน';
@endphp

@section('content')
<div style="display:flex; justify-content:center; padding:clamp(12px,6vh,64px) 0;">
    <div class="tp-card" style="width:100%; max-width:560px; padding:clamp(24px,5vw,40px); text-align:center; background:linear-gradient(160deg, color-mix(in srgb, var(--accent1) 16%, var(--card-bg)), var(--card-bg) 60%);">
        <div class="tp-inset" style="width:96px; height:96px; margin:0 auto; border-radius:28px; display:grid; place-items:center; font-size:44px;" aria-hidden="true">{{ $icon }}</div>
        <span class="tp-pill tp-pill-gold" style="margin-top:18px;">เร็ว ๆ นี้</span>
        <h1 style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:12px 0 0; color:var(--ink);">{{ $feature }} ยังไม่เปิดให้บริการ</h1>
        <p style="font-size:13.5px; color:var(--ink2); line-height:1.75; margin:10px auto 0; max-width:420px;">{{ $description }}</p>
        <div style="display:flex; flex-wrap:wrap; justify-content:center; gap:10px; margin-top:22px;">
            <a href="{{ $backUrl }}" class="tp-btn tp-btn-primary">← {{ $backLabel }}</a>
            @if($backUrl !== route('seller.dashboard'))
                <a href="{{ route('seller.dashboard') }}" class="tp-btn">🏠 แดชบอร์ดร้าน</a>
            @endif
        </div>
    </div>
</div>
@endsection
