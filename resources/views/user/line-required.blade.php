@extends('layouts.user-v4')

{{--
    หน้าบังคับเชื่อมต่อ LINE สำหรับยืนยันตัวตน
    จะแสดงเมื่อผู้ใช้ยังไม่ได้เชื่อมต่อ LINE

    @see app/Http/Middleware/RequireLineUid.php
--}}

@section('title', 'เชื่อมต่อ LINE เพื่อยืนยันตัวตน')

@php
    // ── ดึงการตั้งค่า LINE OA (ใช้แสดง QR เพิ่มเพื่อน) ──────────
    $lineSettings = \App\Models\LineOaSetting::getActive();

    // ── เหตุผลที่ต้องเชื่อมต่อ LINE (สีสถานะแบบ hex ตามมาตรฐาน V4) ──
    $reasons = [
        ['fas fa-user-check',  '#a47bbf', 'ยืนยันตัวตน',    'LINE UID ช่วยยืนยันว่าคุณเป็นเจ้าของบัญชีจริง เพิ่มความปลอดภัย'],
        ['fas fa-bell',        '#5aa07e', 'รับการแจ้งเตือน', 'รับแจ้งเตือนคอมมิชชั่น ยอดขาย และกิจกรรมสำคัญผ่าน LINE ทันที'],
        ['fas fa-headset',     '#5689b8', 'รับความช่วยเหลือ', 'ติดต่อทีมงานและรับความช่วยเหลือผ่าน LINE OA ได้สะดวก'],
        ['fas fa-users',       '#e0a52e', 'ติดตามสายงาน',    'ระบบจะแจ้งเตือนเมื่อมีสมาชิกใหม่ในทีมของคุณ'],
    ];
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px; max-width:880px; margin:0 auto;">

    {{-- ── การ์ดต้อนรับ (Hero) ─────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:18px; padding:24px;
                    background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
            <span class="tp-tile" style="width:58px; height:58px; border-radius:18px; font-size:28px;">
                <svg style="width:30px; height:30px; color:#fff;" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .195-.095.378-.246.498l-3.18 2.323 3.18 2.323c.151.12.246.303.246.498 0 .346-.281.631-.63.631-.178 0-.347-.076-.463-.199l-3.477-2.538-3.477 2.538c-.116.123-.285.199-.463.199-.349 0-.63-.285-.63-.631 0-.195.095-.378.246-.498l3.18-2.323-3.18-2.323c-.151-.12-.246-.303-.246-.498 0-.346.281-.631.63-.631.178 0 .347.076.463.199l3.477 2.538 3.477-2.538c.116-.123.285-.199.463-.199M12 2C6.477 2 2 6.145 2 11.259c0 4.017 2.892 7.445 7.017 8.497l-.244 3.176c-.036.464.464.799.928.599l3.889-1.944c.131-.066.247-.159.336-.271C18.163 20.585 22 16.324 22 11.259 22 6.145 17.523 2 12 2"/>
                </svg>
            </span>
            <div style="flex:1; min-width:200px;">
                <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">ยืนยันตัวตน · CONNECT LINE</div>
                <h1 class="tp-num" style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:4px 0 0;">เชื่อมต่อ LINE เพื่อยืนยันตัวตน</h1>
                <div style="font-size:13px; color:var(--ink2); margin-top:6px;">กรุณาเชื่อมต่อบัญชี LINE เพื่อใช้งานระบบได้อย่างครบถ้วน</div>
            </div>
        </div>
    </div>

    {{-- ── สถานะปัจจุบัน: ยังไม่เชื่อมต่อ ─────────────────────── --}}
    <div class="tp-card" style="padding:16px 18px; display:flex; flex-wrap:wrap; align-items:center; gap:14px;
                box-shadow:var(--inset-sm); border-left:4px solid #d9534f;">
        <span class="tp-tile" style="width:46px; height:46px; border-radius:14px; font-size:18px; background:#d9534f;"><i class="fas fa-exclamation-triangle" style="color:#fff;"></i></span>
        <div style="flex:1; min-width:200px;">
            <div style="font-weight:700; font-size:14px; color:#d9534f;">ยังไม่ได้เชื่อมต่อ LINE</div>
            <div style="font-size:12.5px; color:var(--ink2); margin-top:2px;">บัญชีของคุณ ({{ auth()->user()->email }}) ยังไม่ได้เชื่อมต่อกับ LINE</div>
        </div>
    </div>

    {{-- ── ทำไมต้องเชื่อมต่อ LINE? ───────────────────────────── --}}
    <div class="tp-card">
        <div class="tp-section-h" style="display:flex; align-items:center; gap:8px; margin-bottom:14px;">
            <i class="fas fa-shield-alt" style="color:var(--deep1);"></i>
            <span>ทำไมต้องเชื่อมต่อ LINE?</span>
        </div>
        <div style="display:flex; flex-direction:column; gap:10px;">
            @foreach($reasons as [$icon, $color, $title, $desc])
                <div style="display:flex; align-items:flex-start; gap:13px; padding:14px 16px; border-radius:14px; box-shadow:var(--inset-sm);">
                    <span class="tp-tile" style="width:40px; height:40px; border-radius:12px; font-size:16px; flex-shrink:0; background:{{ $color }};"><i class="{{ $icon }}" style="color:#fff;"></i></span>
                    <div>
                        <div style="font-weight:700; font-size:14px;">{{ $title }}</div>
                        <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">{{ $desc }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ── ปุ่มดำเนินการ ─────────────────────────────────────── --}}
    <div class="tp-card">
        {{-- ปุ่มเชื่อมต่อ LINE หลัก --}}
        <a href="{{ route('line.link') }}" class="tp-btn tp-btn-primary" style="width:100%; justify-content:center; gap:10px; padding:15px; font-size:16px;">
            <svg style="width:24px; height:24px;" viewBox="0 0 24 24" fill="currentColor">
                <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .195-.095.378-.246.498l-3.18 2.323 3.18 2.323c.151.12.246.303.246.498 0 .346-.281.631-.63.631-.178 0-.347-.076-.463-.199l-3.477-2.538-3.477 2.538c-.116.123-.285.199-.463.199-.349 0-.63-.285-.63-.631 0-.195.095-.378.246-.498l3.18-2.323-3.18-2.323c-.151-.12-.246-.303-.246-.498 0-.346.281-.631.63-.631.178 0 .347.076.463.199l3.477 2.538 3.477-2.538c.116-.123.285-.199.463-.199M12 2C6.477 2 2 6.145 2 11.259c0 4.017 2.892 7.445 7.017 8.497l-.244 3.176c-.036.464.464.799.928.599l3.889-1.944c.131-.066.247-.159.336-.271C18.163 20.585 22 16.324 22 11.259 22 6.145 17.523 2 12 2"/>
            </svg>
            เชื่อมต่อ LINE ตอนนี้
        </a>

        {{-- ทางเลือก: QR เพิ่มเพื่อน LINE OA --}}
        @if($lineSettings && $lineSettings->line_id)
        <div class="tp-divider" style="margin:18px 0;"></div>
        <div style="text-align:center;">
            <div style="font-size:12.5px; color:var(--ink2); margin-bottom:12px;">หรือเพิ่มเพื่อน LINE OA ด้านล่าง</div>
            <div style="display:inline-block; padding:14px; border-radius:16px; background:#fff; box-shadow:var(--inset-sm);">
                <img src="https://qr-official.line.me/gs/M_{{ $lineSettings->messaging_channel_id ?? $lineSettings->line_id }}_GW.png"
                     alt="LINE QR Code"
                     style="width:160px; height:160px; display:block; margin:0 auto;"
                     onerror="this.parentElement.style.display='none'">
            </div>
            <div style="margin-top:14px;">
                <a href="https://line.me/R/ti/p/{{ $lineSettings->line_id }}" target="_blank" class="tp-btn tp-btn-sm" style="gap:8px;">
                    <i class="fas fa-external-link-alt"></i>
                    เปิดใน LINE App
                </a>
            </div>
        </div>
        @endif

        {{-- ลิงก์รอง --}}
        <div class="tp-divider" style="margin:18px 0;"></div>
        <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:center; gap:10px;">
            <a href="{{ route('user.dashboard') }}" class="tp-btn tp-btn-sm" style="gap:8px;">
                <i class="fas fa-arrow-left"></i> กลับหน้า Dashboard
            </a>
            <a href="{{ route('home') }}" class="tp-btn tp-btn-sm" style="gap:8px;">
                <i class="fas fa-home"></i> หน้าหลัก
            </a>
        </div>
    </div>

    {{-- ── หมายเหตุความปลอดภัย ───────────────────────────────── --}}
    <div style="text-align:center; font-size:12px; color:var(--ink2); display:flex; align-items:center; justify-content:center; gap:6px;">
        <i class="fas fa-lock"></i>
        <span>ข้อมูล LINE ของคุณจะถูกเก็บรักษาอย่างปลอดภัยและใช้เพื่อการยืนยันตัวตนเท่านั้น</span>
    </div>

    {{-- ── ส่วนช่วยเหลือ ─────────────────────────────────────── --}}
    <div style="text-align:center; font-size:12.5px; color:var(--ink2);">
        มีปัญหาในการเชื่อมต่อ LINE?
        <a href="{{ route('home') }}#contact" style="color:var(--deep1); font-weight:600; text-decoration:none;">ติดต่อเรา</a>
    </div>
</div>
@endsection
