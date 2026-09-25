{{--
 | หน้า "ฟีเจอร์นี้ยังไม่เปิดให้บริการ" ของหลังบ้าน (ธีม V4)
 | ใช้แทนหน้าที่ไม่เคยมี view (เดิมเปิดแล้วเจอ 500) สำหรับฟีเจอร์ที่ยังไม่พร้อมเปิดตัว
 | ตัวแปร (ไม่บังคับ): $featureName, $reason, $backUrl, $backLabel
 --}}
@extends('layouts.admin-v4')

@section('title', 'ยังไม่เปิดให้บริการ')

@section('content')
<div style="min-height:60vh; display:grid; place-items:center; padding:24px 0;">
    <div class="tp-card" style="max-width:520px; width:100%; text-align:center; padding:34px 26px;">
        <div class="tp-tile" style="width:72px; height:72px; border-radius:22px; font-size:32px; margin:0 auto;">🚧</div>
        <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px; margin-top:18px;">หลังบ้าน</div>
        <h1 style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:4px 0 0;">ฟีเจอร์นี้ยังไม่เปิดให้บริการ</h1>
        @if(!empty($featureName))
            <div class="tp-pill tp-pill-soft" style="margin-top:10px;">{{ $featureName }}</div>
        @endif
        <p style="font-size:13.5px; color:var(--ink2); line-height:1.7; margin:14px 0 0;">
            {{ $reason ?? 'ส่วนนี้กำลังปรับปรุงให้พร้อมใช้งานจริง ระหว่างนี้ใช้เมนูอื่นในหลังบ้านได้ตามปกติ' }}
        </p>
        <div style="display:flex; gap:10px; justify-content:center; flex-wrap:wrap; margin-top:22px;">
            <a href="{{ $backUrl ?? route('admin.dashboard') }}" class="tp-btn tp-btn-primary"><i class="fas fa-arrow-left"></i> {{ $backLabel ?? 'กลับแดชบอร์ด' }}</a>
        </div>
    </div>
</div>
@endsection
