{{--
    รายละเอียดคำขอยืนยันตัวตน (ธีมโนวาเต็ม)
    - KycController::show ตรวจแล้วว่าเป็นคำขอของสมาชิกคนนี้
    - รูปเอกสารเปิดผ่าน route user.kyc.image (private disk · ตรวจสิทธิ์) ห้ามชี้ไป /storage ตรงๆ
    - คำขอแบบ AI eKYC ไม่มีรูปให้สมาชิกเปิด (เข้ารหัส เปิดได้เฉพาะแอดมิน) — บอกตรงๆ แทน "ไม่พบไฟล์"
--}}
@extends('layouts.user-nova')

@section('title', 'รายละเอียดการยืนยันตัวตน')

@php
    $nvArt = fn (string $name, string $fallback) => is_file(public_path("images/nova/account/{$name}.webp"))
        ? asset("images/nova/account/{$name}.webp") : asset("images/nova/{$fallback}.webp");
    $thDate = fn ($d) => $d ? $d->copy()->locale('th')->translatedFormat('j M Y · H:i') : '-';

    $status = $kycVerification->status;
    $chip = [
        'pending' => ['warn', 'clock', 'รอการตรวจสอบ'],
        'approved' => ['ok', 'check', 'อนุมัติแล้ว'],
        'rejected' => ['bad', 'x', 'ไม่ผ่านการตรวจสอบ'],
        'expired' => ['bad', 'clock', 'หมดอายุ'],
    ][$status] ?? ['info', 'info', 'กำลังดำเนินการ'];

    $isEkyc = $kycVerification->isEkyc();
    $isLicense = ($kycVerification->extracted_data['document_type'] ?? null) === 'driver_license';
    $docName = $isLicense ? 'ใบขับขี่' : 'บัตรประชาชน';
    $docs = [
        ['รูป'.$docName, $isEkyc ? null : $kycVerification->idCardImageUrl()],
        ['รูปตัวเองถือ'.$docName, $isEkyc ? null : $kycVerification->selfieImageUrl()],
    ];
@endphp

@section('content')

<div class="nvm-stack" x-data="{ zoom: null }" @keydown.escape.window="zoom = null">

<section class="nvm-ph" aria-labelledby="nvm-kys-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.kyc.index') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>สถานะการยืนยันตัวตน</a>
            <div class="nvm-eyebrow nvm-latin">Request detail</div>
            <h1 id="nvm-kys-h">รายละเอียดการยืนยันตัวตน</h1>
            <p class="nvm-ph__lead">ส่งเมื่อ {{ $thDate($kycVerification->submitted_at) }}</p>
            <div class="nvm-pills">
                <span class="nvm-pill nvm-pill--{{ $chip[0] === 'info' ? 'glass' : $chip[0] }}"><svg class="nvm-ico"><use href="#nvm-i-{{ $chip[1] }}"/></svg>{{ $chip[2] }}</span>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ $nvArt('idcard', 'deco/scroll') }}" alt="" width="200" height="200">
    </div>
</section>

<div class="nvm-split">
    <section class="nvm-card nvm-box" aria-labelledby="nvm-kys-doc-h">
        <div class="nvm-sec-h">
            <div><small class="nvm-latin">Documents</small><h2 id="nvm-kys-doc-h">เอกสารที่ส่ง</h2></div>
            @if(! empty($kycVerification->extracted_data['document_type']))
                <span class="nvm-chip nvm-chip--info"><svg class="nvm-ico"><use href="#nvm-i-id"/></svg>{{ $docName }}</span>
            @endif
        </div>

        @if($isEkyc)
            <div class="nvm-empty">
                <img src="{{ $nvArt('shield', 'deco/scroll') }}" alt="" width="86" height="86" loading="lazy">
                <b>ยืนยันผ่านระบบ AI eKYC</b>
                รูปบัตรและใบหน้าจากการยืนยันแบบนี้ถูกเข้ารหัสไว้ เปิดดูได้เฉพาะทีมงานที่ตรวจ
            </div>
        @else
            <div class="nvm-ky-docs">
                @foreach($docs as [$label, $url])
                    <figure class="nvm-ky-doc">
                        <figcaption>{{ $label }}</figcaption>
                        @if($url)
                            <button type="button" class="nvm-ky-doc__img" @click="zoom = @js(['src' => $url, 'label' => $label])" aria-label="ดู{{ $label }}ขนาดเต็ม">
                                <img src="{{ $url }}" alt="{{ $label }}" loading="lazy">
                            </button>
                        @else
                            {{-- ไฟล์รูปหาย — บอกสมาชิกตรงๆ ว่าต้องส่งใหม่ (ไม่โชว์พาธภายในระบบ) --}}
                            <div class="nvm-ky-doc__miss">
                                <svg class="nvm-ico"><use href="#nvm-i-info"/></svg>
                                <b>ไม่พบไฟล์รูปภาพ</b>
                                <small>กรุณาส่งเอกสารใหม่อีกครั้ง</small>
                            </div>
                        @endif
                    </figure>
                @endforeach
            </div>
            <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-search"/></svg>แตะที่รูปเพื่อดูขนาดเต็ม</p>
        @endif
    </section>

    <aside class="nvm-stack">
        <section class="nvm-card nvm-box" aria-labelledby="nvm-kys-st-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Status</small><h2 id="nvm-kys-st-h">สถานะคำขอ</h2></div></div>
            <dl class="nvm-kv">
                <dt>สถานะ</dt><dd><span class="nvm-chip nvm-chip--{{ $chip[0] }}">{{ $chip[2] }}</span></dd>
                <dt>วันที่ส่ง</dt><dd class="nvm-num">{{ $thDate($kycVerification->submitted_at) }}</dd>
                @if($kycVerification->reviewed_at)
                    <dt>วันที่ตรวจ</dt><dd class="nvm-num">{{ $thDate($kycVerification->reviewed_at) }}</dd>
                @endif
                @if($kycVerification->reviewer)
                    <dt>ตรวจโดย</dt><dd>{{ $kycVerification->reviewer->name }}</dd>
                @endif
            </dl>

            @if($status === 'pending')
                <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-clock"/></svg>ปกติใช้เวลา 1-3 วันทำการ ระบบจะแจ้งเตือนเมื่อตรวจเสร็จ</p>
            @endif

            @if($status === 'rejected' && $kycVerification->rejection_reason)
                <div class="nvm-ky-reason">
                    <b>เหตุผลจากทีมงาน</b>
                    <p>{{ $kycVerification->rejection_reason }}</p>
                </div>
            @endif
        </section>

        @if($status === 'rejected')
            <section class="nvm-card nvm-box nvm-ky-retry" aria-labelledby="nvm-kys-re-h">
                <h2 id="nvm-kys-re-h">ส่งเอกสารใหม่</h2>
                <p class="nvm-desc">แก้ตามเหตุผลด้านบน แล้วถ่ายรูปใหม่ให้ชัดเจน</p>
                <a class="nvm-btn nvm-btn--gold nvm-btn--block nvm-gap-t" href="{{ route('user.kyc.create') }}"><svg class="nvm-ico"><use href="#nvm-i-up"/></svg>ส่งเอกสารใหม่</a>
            </section>
        @endif
    </aside>
</div>

{{-- ดูรูปขนาดเต็ม: แตะพื้นหลังหรือกด Esc เพื่อปิด --}}
<div class="nvm-modal" x-show="zoom" x-cloak x-transition.opacity role="dialog" aria-modal="true" aria-label="ดูรูปเอกสารขนาดเต็ม" @click.self="zoom = null">
    <div class="nvm-ky-zoom">
        <button type="button" class="nvm-ibtn" @click="zoom = null" aria-label="ปิด"><svg class="nvm-ico"><use href="#nvm-i-close"/></svg></button>
        <template x-if="zoom">
            <img :src="zoom.src" :alt="zoom.label">
        </template>
    </div>
</div>

</div>
@endsection
