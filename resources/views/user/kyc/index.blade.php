{{--
    สถานะการยืนยันตัวตน (ธีมโนวาเต็ม)
    - $kycVerification = คำขอล่าสุดของสมาชิก (อาจเป็น null) · KycController::index sync users.kyc_status ให้แล้ว
    - สถานะใช้ isKycVerified()/isKycPending() ชุดเดียวกับที่ KycController::create ใช้กันส่งซ้ำ
      หน้านี้จึงไม่ชวน "ส่งเอกสาร" ในสถานะที่กดไปแล้วโดนเด้งกลับ
--}}
@extends('layouts.user-nova')

@section('title', 'ยืนยันตัวตน (KYC)')

@php
    $nvArt = fn (string $name, string $fallback) => is_file(public_path("images/nova/account/{$name}.webp"))
        ? asset("images/nova/account/{$name}.webp") : asset("images/nova/{$fallback}.webp");

    $user = auth()->user();
    $thDate = fn ($d) => $d ? $d->copy()->locale('th')->translatedFormat('j M Y · H:i') : '-';

    // ลำดับตรวจสำคัญ: อนุมัติแล้วชนะทุกอย่าง · รอตรวจ · ไม่ผ่าน · หมดอายุ · ยังไม่ส่ง
    $state = match (true) {
        $user->isKycVerified() => 'approved',
        $user->isKycPending() => 'pending',
        $user->kyc_status === 'rejected' || $kycVerification?->status === 'rejected' => 'rejected',
        $user->kyc_status === 'expired' => 'expired',
        default => 'none',
    };

    $pill = [
        'approved' => ['ok', 'check', 'ยืนยันตัวตนแล้ว'],
        'pending' => ['warn', 'clock', 'รอตรวจสอบ'],
        'rejected' => ['bad', 'x', 'ไม่ผ่านการตรวจสอบ'],
        'expired' => ['bad', 'clock', 'การยืนยันหมดอายุ'],
        'none' => ['bad', 'id', 'ยังไม่ยืนยันตัวตน'],
    ][$state];

    $flashes = [
        'success' => ['ok', 'check', 'สำเร็จ'],
        'error' => ['bad', 'info', 'ทำรายการไม่สำเร็จ'],
        'warning' => ['', 'info', 'โปรดทราบ'],
        'info' => ['info', 'info', 'แจ้งให้ทราบ'],
    ];
@endphp

@section('content')

<div class="nvm-stack">

<section class="nvm-ph" aria-labelledby="nvm-ky-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.profile') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>โปรไฟล์</a>
            <div class="nvm-eyebrow nvm-latin">Identity check</div>
            <h1 id="nvm-ky-h">ยืนยันตัวตน (KYC)</h1>
            <p class="nvm-ph__lead">ยืนยันด้วยบัตรประชาชนหรือใบขับขี่ เพื่อถอนเงินและใช้งานได้ครบทุกฟีเจอร์</p>
            <div class="nvm-pills">
                <span class="nvm-pill nvm-pill--{{ $pill[0] }}"><svg class="nvm-ico"><use href="#nvm-i-{{ $pill[1] }}"/></svg>{{ $pill[2] }}</span>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ $nvArt('idcard', 'deco/scroll') }}" alt="" width="200" height="200">
    </div>
</section>

@foreach($flashes as $key => [$tone, $icon, $heading])
    @if(session($key))
        <section class="nvm-card nvm-alert {{ $tone ? 'nvm-alert--'.$tone : '' }}" role="{{ $tone === 'bad' ? 'alert' : 'status' }}">
            <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-{{ $icon }}"/></svg></span>
            <div class="nvm-alert__txt"><b>{{ $heading }}</b><p>{{ session($key) }}</p></div>
        </section>
    @endif
@endforeach

@if(session('ocr_success'))
    <section class="nvm-card nvm-alert nvm-alert--ok" role="status">
        <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg></span>
        <div class="nvm-alert__txt">
            <b>{{ session('ocr_success') }}</b>
            <p>ทีมงานจะตรวจความถูกต้องของข้อมูลที่ระบบอ่านจากบัตรอีกครั้ง</p>
        </div>
    </section>
@endif

@if(session('ocr_error'))
    <section class="nvm-card nvm-alert" role="status">
        <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg></span>
        <div class="nvm-alert__txt">
            <b>ระบบอ่านข้อมูลจากบัตรไม่ได้: {{ session('ocr_error') }}</b>
            <p>คำขอของคุณส่งถึงทีมงานแล้ว ทีมงานจะกรอกข้อมูลจากรูปบัตรให้เองตอนตรวจ</p>
            @if(session('ocr_suggestion'))<p>คำแนะนำ: {{ session('ocr_suggestion') }}</p>@endif
        </div>
    </section>
@endif

<div class="nvm-split">
    <section class="nvm-card nvm-box" aria-labelledby="nvm-ky-st-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Status</small><h2 id="nvm-ky-st-h">สถานะของคุณ</h2></div></div>

        @if($state === 'approved')
            <div class="nvm-status">
                <svg class="nvm-ico"><use href="#nvm-i-check"/></svg>
                <span>บัญชีได้รับการยืนยันแล้ว<small>ถอนเงินและใช้งานได้ครบทุกฟีเจอร์</small></span>
            </div>
            <dl class="nvm-kv nvm-gap-t">
                <dt>วันที่อนุมัติ</dt><dd class="nvm-num">{{ $thDate($user->kyc_verified_at ?? $kycVerification?->reviewed_at) }}</dd>
            </dl>
            <ul class="nvm-ky-perks">
                <li><svg class="nvm-ico"><use href="#nvm-i-cash"/></svg>ถอนเงินได้</li>
                <li><svg class="nvm-ico"><use href="#nvm-i-lock"/></svg>บัญชีปลอดภัย</li>
                <li><svg class="nvm-ico"><use href="#nvm-i-award"/></svg>ใช้ได้ครบทุกฟีเจอร์</li>
            </ul>
            @if($kycVerification)
                <a class="nvm-btn nvm-btn--line nvm-gap-t" href="{{ route('user.kyc.show', $kycVerification) }}"><svg class="nvm-ico"><use href="#nvm-i-id"/></svg>ดูเอกสารที่ส่ง</a>
            @endif

        @elseif($state === 'pending')
            <div class="nvm-status is-warn">
                <svg class="nvm-ico"><use href="#nvm-i-clock"/></svg>
                <span>เอกสารอยู่ระหว่างการตรวจสอบ<small>ปกติใช้เวลา 1-3 วันทำการ ระบบจะแจ้งเตือนเมื่อตรวจเสร็จ</small></span>
            </div>
            @if($kycVerification)
                <dl class="nvm-kv nvm-gap-t">
                    <dt>วันที่ส่ง</dt><dd class="nvm-num">{{ $thDate($kycVerification->submitted_at) }}</dd>
                    <dt>สถานะ</dt><dd><span class="nvm-chip nvm-chip--warn">รอการตรวจสอบ</span></dd>
                </dl>
                <a class="nvm-btn nvm-btn--line nvm-gap-t" href="{{ route('user.kyc.show', $kycVerification) }}"><svg class="nvm-ico"><use href="#nvm-i-id"/></svg>ดูเอกสารที่ส่ง</a>
            @endif

        @elseif($state === 'rejected' || $state === 'expired')
            <div class="nvm-status is-bad">
                <svg class="nvm-ico"><use href="#nvm-i-x"/></svg>
                @if($state === 'rejected')
                    <span>เอกสารไม่ผ่านการตรวจสอบ<small>อ่านเหตุผลด้านล่าง แก้ไขแล้วส่งเอกสารใหม่ได้เลย</small></span>
                @else
                    <span>การยืนยันตัวตนหมดอายุ<small>ส่งเอกสารใหม่เพื่อยืนยันตัวตนอีกครั้ง</small></span>
                @endif
            </div>
            @if($state === 'rejected' && $kycVerification?->rejection_reason)
                <div class="nvm-ky-reason">
                    <b>เหตุผลจากทีมงาน</b>
                    <p>{{ $kycVerification->rejection_reason }}</p>
                </div>
            @endif
            <div class="nvm-filter__btns nvm-gap-t">
                @if($kycVerification)
                    <a class="nvm-btn nvm-btn--line" href="{{ route('user.kyc.show', $kycVerification) }}">ดูเอกสารเดิม</a>
                @endif
                <a class="nvm-btn nvm-btn--gold" href="{{ route('user.kyc.create') }}"><svg class="nvm-ico"><use href="#nvm-i-up"/></svg>ส่งเอกสารใหม่</a>
            </div>

        @else
            <div class="nvm-empty">
                <img src="{{ $nvArt('idcard', 'deco/scroll') }}" alt="" width="86" height="86" loading="lazy">
                <b>คุณยังไม่ได้ยืนยันตัวตน</b>
                ใช้เวลาไม่ถึง 2 นาที เตรียมบัตรประชาชนหรือใบขับขี่ไว้ให้พร้อม
                <div><a class="nvm-btn nvm-btn--gold" href="{{ route('user.kyc.create') }}"><svg class="nvm-ico"><use href="#nvm-i-id"/></svg>เริ่มยืนยันตัวตน</a></div>
            </div>
        @endif
    </section>

    <aside class="nvm-card nvm-box" aria-labelledby="nvm-ky-how-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">How it works</small><h2 id="nvm-ky-how-h">ขั้นตอนการยืนยัน</h2></div></div>
        <ol class="nvm-steps">
            <li>ถ่ายรูปบัตรประชาชนหรือใบขับขี่ที่ยังไม่หมดอายุ ให้เห็นข้อความครบ</li>
            <li>ถ่ายรูปตัวเองถือบัตรไว้ข้างใบหน้า ให้เห็นทั้งหน้าและบัตรชัดเจน</li>
            <li>ส่งเอกสาร ระบบอ่านข้อมูลจากบัตรให้อัตโนมัติ</li>
            <li>ทีมงานตรวจภายใน 1-3 วันทำการ แล้วแจ้งผลให้ทราบ</li>
        </ol>
        <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-lock"/></svg>รูปเอกสารเก็บในพื้นที่ส่วนตัว เปิดดูได้เฉพาะคุณและทีมงานที่ตรวจ</p>
    </aside>
</div>

</div>
@endsection
