{{--
    หน้าบังคับเชื่อมต่อ LINE เพื่อยืนยันตัวตน (ธีมโนวาเต็ม)
    แสดงเมื่อสมาชิกยังไม่มี line_user_id — middleware require.line.uid เด้งมาที่นี่ (route user.line-required)
    ถ้าเชื่อมต่อแล้ว route จะ redirect กลับหน้าเดิมเอง ไม่มาถึงหน้านี้

    @see app/Http/Middleware/RequireLineUid.php
--}}
@extends('layouts.user-nova')

@section('title', 'เชื่อมต่อ LINE เพื่อยืนยันตัวตน')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/user-misc.css') }}?v={{ filemtime(public_path('theme-nova/member/user-misc.css')) }}">@endpush

@php
    // ตั้งค่า LINE OA — ใช้แสดง QR เพิ่มเพื่อน
    // ⚠️ ตาราง line_oa_settings ไม่มีคอลัมน์ line_id (LINE Basic ID) → ส่วน QR จะแสดงเมื่อมีค่านี้เท่านั้น (ตอนนี้ยังไม่แสดง)
    $lineSettings = \App\Models\LineOaSetting::getActive();
    $lineBasicId = $lineSettings?->line_id;

    // เหตุผลที่ต้องเชื่อมต่อ LINE: [ไอคอน, หัวข้อ, คำอธิบาย]
    $reasons = [
        ['id', 'ยืนยันตัวตน', 'LINE UID ช่วยยืนยันว่าคุณเป็นเจ้าของบัญชีจริง เพิ่มความปลอดภัย'],
        ['bell', 'รับการแจ้งเตือน', 'รับแจ้งเตือนคอมมิชชั่น ยอดขาย และกิจกรรมสำคัญผ่าน LINE ทันที'],
        ['line', 'รับความช่วยเหลือ', 'ติดต่อทีมงานและรับความช่วยเหลือผ่าน LINE OA ได้สะดวก'],
        ['users', 'ติดตามสายงาน', 'ระบบจะแจ้งเตือนเมื่อมีสมาชิกใหม่ในทีมของคุณ'],
    ];
@endphp

@section('content')

<div class="nvm-stack nvm-um-narrow">

<section class="nvm-ph" aria-labelledby="nvm-um-line-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.dashboard') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>แดชบอร์ด</a>
            <div class="nvm-eyebrow nvm-latin">Connect LINE</div>
            <h1 id="nvm-um-line-h">เชื่อมต่อ LINE เพื่อยืนยันตัวตน</h1>
            <p class="nvm-ph__lead">กรุณาเชื่อมต่อบัญชี LINE เพื่อใช้งานระบบได้อย่างครบถ้วน</p>
            <div class="nvm-pills">
                <span class="nvm-pill nvm-pill--bad"><svg class="nvm-ico"><use href="#nvm-i-x"/></svg>ยังไม่ได้เชื่อมต่อ LINE</span>
            </div>
            <div class="nvm-hero__actions">
                <a class="nvm-btn nvm-btn--gold" href="{{ route('line.link') }}"><svg class="nvm-ico"><use href="#nvm-i-line"/></svg>เชื่อมต่อ LINE ตอนนี้</a>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/account/chat.webp') }}" alt="" width="200" height="200">
    </div>
</section>

<section class="nvm-card nvm-alert" role="status">
    <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg></span>
    <div class="nvm-alert__txt">
        <b>บัญชีนี้ยังไม่ได้เชื่อมต่อกับ LINE</b>
        <p>บัญชีของคุณ ({{ auth()->user()->email }}) ต้องเชื่อมต่อ LINE ก่อนจึงจะใช้งานหน้านี้ได้</p>
    </div>
</section>

<section class="nvm-card nvm-box" aria-labelledby="nvm-um-line-why">
    <div class="nvm-sec-h"><div><small class="nvm-latin">Why LINE</small><h2 id="nvm-um-line-why">ทำไมต้องเชื่อมต่อ LINE?</h2></div></div>
    <ul class="nvm-um-feats">
        @foreach($reasons as [$icon, $title, $desc])
            <li>
                <span class="nvm-um-ic"><svg class="nvm-ico"><use href="#nvm-i-{{ $icon }}"/></svg></span>
                <span><b>{{ $title }}</b><small>{{ $desc }}</small></span>
            </li>
        @endforeach
    </ul>
</section>

<section class="nvm-card nvm-box" aria-labelledby="nvm-um-line-go">
    <div class="nvm-sec-h"><div><small class="nvm-latin">Connect</small><h2 id="nvm-um-line-go">เชื่อมต่อบัญชี</h2></div></div>
    <a class="nvm-btn nvm-btn--gold nvm-btn--block" href="{{ route('line.link') }}"><svg class="nvm-ico"><use href="#nvm-i-line"/></svg>เชื่อมต่อ LINE ตอนนี้</a>

    @if($lineBasicId)
        <div class="nvm-um-lineqr">
            <p class="nvm-help">หรือเพิ่มเพื่อน LINE OA ด้านล่าง</p>
            <div class="nvm-um-lineqr__box">
                <img src="https://qr-official.line.me/gs/M_{{ rawurlencode($lineSettings->messaging_channel_id ?? $lineBasicId) }}_GW.png" alt="QR Code เพิ่มเพื่อน LINE OA" width="160" height="160" onerror="this.parentElement.remove()">
            </div>
            <a class="nvm-btn nvm-btn--line nvm-btn--sm" href="https://line.me/R/ti/p/{{ rawurlencode($lineBasicId) }}" target="_blank" rel="noopener">เปิดใน LINE App</a>
        </div>
    @endif

    <div class="nvm-filter__btns nvm-gap-t">
        <a class="nvm-btn nvm-btn--line nvm-btn--sm" href="{{ route('user.dashboard') }}"><svg class="nvm-ico"><use href="#nvm-i-grid"/></svg>กลับหน้าแดชบอร์ด</a>
        <a class="nvm-btn nvm-btn--line nvm-btn--sm" href="{{ route('home') }}"><svg class="nvm-ico"><use href="#nvm-i-home"/></svg>หน้าแรก</a>
    </div>

    <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-lock"/></svg>ข้อมูล LINE ของคุณจะถูกเก็บรักษาอย่างปลอดภัยและใช้เพื่อการยืนยันตัวตนเท่านั้น</p>
    <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg><span>มีปัญหาในการเชื่อมต่อ LINE? <a class="nvm-link" href="{{ route('home') }}#contact">ติดต่อเรา</a></span></p>
</section>

</div>
@endsection
