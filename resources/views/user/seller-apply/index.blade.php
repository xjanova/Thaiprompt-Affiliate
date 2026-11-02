{{--
 | สมัครเปิดร้านค้า (สมาชิกทั่วไป) — SELLER-07 · ธีมโนวาเต็ม
 | ตัวแปร: $user, $store (VendorStore|null), $state (can_apply|pending|rejected|approved|seller|role_not_eligible), $kycApproved (bool)
 | ฟอร์ม POST → route('user.seller-apply.store') — ชื่อช่องต้องตรงกับ SellerApplicationService::rules()
 --}}
@extends('layouts.user-nova')

@section('title', 'สมัครเปิดร้านค้า')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/user-misc.css') }}?v={{ filemtime(public_path('theme-nova/member/user-misc.css')) }}">@endpush

@php
    $nvR = fn ($name) => \Illuminate\Support\Facades\Route::has($name);
    $canApply = in_array($state, ['can_apply', 'rejected'], true);

    $pill = match ($state) {
        'seller', 'approved' => ['ok', 'check', 'คุณเป็นผู้ขายแล้ว'],
        'pending' => ['warn', 'clock', 'รอทีมงานตรวจสอบ'],
        'rejected' => ['bad', 'x', 'คำขอครั้งก่อนยังไม่ผ่าน'],
        'role_not_eligible' => ['glass', 'info', 'บัญชีประเภทนี้สมัครเองไม่ได้'],
        default => ['glass', 'store', 'ยังไม่ได้ยื่นคำขอ'],
    };

    // ค่าในช่อง: กรอกค้างจากการส่งที่ไม่ผ่าน > ค่าในคำขอเดิม (ยื่นใหม่หลังถูกปฏิเสธ) > ค่าตั้งต้น
    $field = fn (string $name, $fallback = null) => old($name, $store?->{$name} ?? $fallback);
@endphp

@section('content')

<div class="nvm-stack">

<section class="nvm-ph" aria-labelledby="nvm-um-sa-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.dashboard') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>แดชบอร์ด</a>
            <div class="nvm-eyebrow nvm-latin">Open your store</div>
            <h1 id="nvm-um-sa-h">เปิดร้านค้าบนไทยพร๊อมท์</h1>
            <p class="nvm-ph__lead">ขายสินค้าให้สมาชิกทั่วประเทศ ส่งพัสดุหรือให้ไรเดอร์ในพื้นที่ไปส่งก็ได้</p>
            <div class="nvm-pills">
                <span class="nvm-pill nvm-pill--{{ $pill[0] }}"><svg class="nvm-ico"><use href="#nvm-i-{{ $pill[1] }}"/></svg>{{ $pill[2] }}</span>
                @if($kycApproved)
                    <span class="nvm-pill nvm-pill--ok"><svg class="nvm-ico"><use href="#nvm-i-id"/></svg>ยืนยันตัวตนแล้ว</span>
                @endif
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/earn/store.webp') }}" alt="" width="200" height="200">
    </div>
</section>

{{-- ข้อผิดพลาดตอนส่ง (session error) แสดงเป็น toast จาก layouts.user-nova อยู่แล้ว — ไม่ซ้ำในหน้า --}}

{{-- ── สถานะคำขอ ─────────────────────────────────────── --}}
@if(in_array($state, ['seller', 'approved'], true))
    <section class="nvm-card nvm-box" aria-labelledby="nvm-um-sa-ok">
        <div class="nvm-status">
            <svg class="nvm-ico"><use href="#nvm-i-check"/></svg>
            <span id="nvm-um-sa-ok">คุณเป็นผู้ขายแล้ว<small>ร้านของคุณได้รับการอนุมัติแล้ว เข้าหลังร้านเพื่อยืนยันตัวตน (KYC) และเริ่มลงสินค้า</small></span>
        </div>
        @if($nvR('seller.onboarding.index'))
            <a class="nvm-btn nvm-btn--gold nvm-gap-t" href="{{ route('seller.onboarding.index') }}"><svg class="nvm-ico"><use href="#nvm-i-store"/></svg>ไปหลังร้าน</a>
        @endif
    </section>
@elseif($state === 'pending')
    <section class="nvm-card nvm-box" aria-labelledby="nvm-um-sa-wait">
        <div class="nvm-status is-warn">
            <svg class="nvm-ico"><use href="#nvm-i-clock"/></svg>
            <span id="nvm-um-sa-wait">คำขอของคุณกำลังรอตรวจสอบ<small>ทีมงานจะแจ้งผลผ่านการแจ้งเตือน (ในเว็บและแอป)</small></span>
        </div>
        @if($store)
            <dl class="nvm-kv nvm-gap-t">
                <dt>ชื่อร้าน</dt><dd>{{ $store->store_name }}</dd>
                <dt>ส่งคำขอเมื่อ</dt><dd class="nvm-num">{{ $store->updated_at?->format('d/m/Y H:i') ?? '-' }}</dd>
            </dl>
        @endif
        @unless($kycApproved)
            <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-id"/></svg><span>ระหว่างรอ ยืนยันตัวตน (KYC) ไว้ก่อนได้เลย ร้านจะเปิดขายได้ทันทีหลังอนุมัติ <a class="nvm-link" href="{{ route('user.kyc.index') }}">ยืนยันตัวตน</a></span></p>
        @endunless
    </section>
@elseif($state === 'role_not_eligible')
    <section class="nvm-card nvm-box" aria-labelledby="nvm-um-sa-role">
        <div class="nvm-status is-warn">
            <svg class="nvm-ico"><use href="#nvm-i-info"/></svg>
            <span id="nvm-um-sa-role">บัญชีประเภทนี้สมัครเปิดร้านเองไม่ได้<small>บัญชีของคุณมีบทบาทเฉพาะอยู่แล้ว หากต้องการเปิดร้านค้าด้วย กรุณาติดต่อทีมงาน</small></span>
        </div>
        @if($nvR('user.tickets.index'))
            <a class="nvm-btn nvm-btn--line nvm-gap-t" href="{{ route('user.tickets.index') }}"><svg class="nvm-ico"><use href="#nvm-i-send"/></svg>ติดต่อทีมงาน</a>
        @endif
    </section>
@endif

{{-- ── ฟอร์มสมัคร (ยื่นครั้งแรก หรือยื่นใหม่หลังถูกปฏิเสธ) ── --}}
@if($canApply)
    @if($state === 'rejected' && $store?->suspension_reason)
        <section class="nvm-card nvm-alert nvm-alert--bad" role="alert">
            <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-x"/></svg></span>
            <div class="nvm-alert__txt">
                <b>คำขอครั้งก่อนยังไม่ผ่านการอนุมัติ</b>
                <p>เหตุผล: {{ $store->suspension_reason }}</p>
                <p>แก้ไขข้อมูลด้านล่างแล้วยื่นใหม่ได้เลย</p>
            </div>
        </section>
    @endif

    <div class="nvm-split">
        <section class="nvm-card nvm-box" aria-labelledby="nvm-um-sa-form">
            <form method="POST" action="{{ route('user.seller-apply.store') }}" class="nvm-form"
                  x-data="{ businessType: @js(old('business_type', $store?->business_type ?? 'individual')), submitting: false }"
                  @submit="submitting = true" @pageshow.window="submitting = false">
                @csrf

                @if($errors->any())
                    <div class="nvm-card nvm-alert nvm-alert--bad" role="alert">
                        <div class="nvm-alert__txt">
                            <b>กรุณาตรวจสอบข้อมูลอีกครั้ง</b>
                            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
                        </div>
                    </div>
                @endif

                <div class="nvm-sec-h"><div><small class="nvm-latin">Store details</small><h2 id="nvm-um-sa-form">ข้อมูลร้านค้า</h2></div></div>

                <div class="nvm-field">
                    <label class="nvm-label" for="sa-store_name">ชื่อร้าน <em>*</em></label>
                    <input type="text" id="sa-store_name" name="store_name" class="nvm-input @error('store_name') is-bad @enderror" maxlength="100" required value="{{ $field('store_name') }}" placeholder="เช่น ร้านผักป้าแดง">
                    @error('store_name')<span class="nvm-err">{{ $message }}</span>@enderror
                </div>

                <div class="nvm-field">
                    <span class="nvm-label" id="sa-bt-lbl">ประเภทผู้ขาย <em>*</em></span>
                    <div class="nvm-opts nvm-opts--2" role="radiogroup" aria-labelledby="sa-bt-lbl">
                        <label class="nvm-opt">
                            <input type="radio" name="business_type" value="individual" x-model="businessType">
                            <span class="nvm-um-ic"><svg class="nvm-ico"><use href="#nvm-i-user"/></svg></span>
                            <span><b>บุคคลธรรมดา</b><small>ขายในนามตัวเอง</small></span>
                        </label>
                        <label class="nvm-opt">
                            <input type="radio" name="business_type" value="company" x-model="businessType">
                            <span class="nvm-um-ic"><svg class="nvm-ico"><use href="#nvm-i-bank"/></svg></span>
                            <span><b>นิติบุคคล</b><small>บริษัท / หจก.</small></span>
                        </label>
                    </div>
                    @error('business_type')<span class="nvm-err">{{ $message }}</span>@enderror
                </div>

                <div class="nvm-um-2col" x-show="businessType === 'company'" x-cloak>
                    <div class="nvm-field">
                        <label class="nvm-label" for="sa-company_name">ชื่อบริษัท <em>*</em></label>
                        <input type="text" id="sa-company_name" name="company_name" class="nvm-input @error('company_name') is-bad @enderror" maxlength="255" value="{{ $field('company_name') }}">
                        @error('company_name')<span class="nvm-err">{{ $message }}</span>@enderror
                    </div>
                    <div class="nvm-field">
                        <label class="nvm-label" for="sa-tax_id">เลขประจำตัวผู้เสียภาษี (13 หลัก) <em>*</em></label>
                        <input type="text" id="sa-tax_id" name="tax_id" class="nvm-input nvm-num @error('tax_id') is-bad @enderror" inputmode="numeric" maxlength="13" value="{{ $field('tax_id') }}">
                        @error('tax_id')<span class="nvm-err">{{ $message }}</span>@enderror
                    </div>
                </div>

                <div class="nvm-field">
                    <label class="nvm-label" for="sa-store_phone">เบอร์โทรร้าน <em>*</em></label>
                    <input type="tel" id="sa-store_phone" name="store_phone" class="nvm-input nvm-num @error('store_phone') is-bad @enderror" inputmode="numeric" maxlength="10" required value="{{ $field('store_phone', $user->phone) }}" placeholder="0812345678">
                    @error('store_phone')<span class="nvm-err">{{ $message }}</span>@enderror
                </div>

                <div class="nvm-field">
                    <label class="nvm-label" for="sa-store_description">รายละเอียดร้าน</label>
                    <textarea id="sa-store_description" name="store_description" class="nvm-input nvm-textarea @error('store_description') is-bad @enderror" rows="3" maxlength="1000" placeholder="ขายอะไร จุดเด่นของร้าน">{{ $field('store_description') }}</textarea>
                    @error('store_description')<span class="nvm-err">{{ $message }}</span>@enderror
                </div>

                <div class="nvm-sec-h nvm-gap-t"><div><small class="nvm-latin">Pickup address</small><h2>ที่อยู่ร้าน / จุดรับสินค้า</h2></div></div>

                <div class="nvm-field">
                    <label class="nvm-label" for="sa-store_address">ที่อยู่ <em>*</em></label>
                    <textarea id="sa-store_address" name="store_address" class="nvm-input nvm-textarea nvm-um-textarea-sm @error('store_address') is-bad @enderror" rows="2" maxlength="500" required placeholder="บ้านเลขที่ ถนน ตำบล">{{ $field('store_address') }}</textarea>
                    @error('store_address')<span class="nvm-err">{{ $message }}</span>@enderror
                </div>

                <div class="nvm-um-3col">
                    <div class="nvm-field">
                        <label class="nvm-label" for="sa-store_city">อำเภอ/เขต <em>*</em></label>
                        <input type="text" id="sa-store_city" name="store_city" class="nvm-input @error('store_city') is-bad @enderror" maxlength="100" required value="{{ $field('store_city') }}">
                        @error('store_city')<span class="nvm-err">{{ $message }}</span>@enderror
                    </div>
                    <div class="nvm-field">
                        <label class="nvm-label" for="sa-store_state">จังหวัด <em>*</em></label>
                        <input type="text" id="sa-store_state" name="store_state" class="nvm-input @error('store_state') is-bad @enderror" maxlength="100" required value="{{ $field('store_state') }}">
                        @error('store_state')<span class="nvm-err">{{ $message }}</span>@enderror
                    </div>
                    <div class="nvm-field">
                        <label class="nvm-label" for="sa-store_postal_code">รหัสไปรษณีย์ <em>*</em></label>
                        <input type="text" id="sa-store_postal_code" name="store_postal_code" class="nvm-input nvm-num @error('store_postal_code') is-bad @enderror" inputmode="numeric" maxlength="5" required value="{{ $field('store_postal_code') }}">
                        @error('store_postal_code')<span class="nvm-err">{{ $message }}</span>@enderror
                    </div>
                </div>

                <label class="nvm-check nvm-um-terms">
                    <input type="checkbox" name="accept_terms" value="1" {{ old('accept_terms') ? 'checked' : '' }} required>
                    <span>ฉันยืนยันว่าข้อมูลถูกต้อง และยอมรับ
                        <a class="nvm-link" href="{{ route('terms-of-service') }}" target="_blank" rel="noopener">ข้อกำหนดการใช้บริการ</a>
                        และ <a class="nvm-link" href="{{ route('privacy-policy') }}" target="_blank" rel="noopener">นโยบายความเป็นส่วนตัว</a>
                    </span>
                </label>
                @error('accept_terms')<span class="nvm-err">{{ $message }}</span>@enderror

                <div>
                    <button type="submit" class="nvm-btn nvm-btn--gold" :disabled="submitting">
                        <svg class="nvm-ico"><use href="#nvm-i-send"/></svg>
                        <span x-text="submitting ? 'กำลังส่ง…' : @js($state === 'rejected' ? 'ยื่นคำขอใหม่' : 'ส่งคำขอเปิดร้าน')">{{ $state === 'rejected' ? 'ยื่นคำขอใหม่' : 'ส่งคำขอเปิดร้าน' }}</span>
                    </button>
                </div>
            </form>
        </section>

        <aside class="nvm-card nvm-box" aria-labelledby="nvm-um-sa-how">
            <div class="nvm-sec-h"><div><small class="nvm-latin">How it works</small><h2 id="nvm-um-sa-how">ขั้นตอนหลังส่งคำขอ</h2></div></div>
            <ol class="nvm-steps">
                <li>ทีมงานตรวจสอบข้อมูลร้านของคุณ</li>
                <li>อนุมัติ แล้วแจ้งผลผ่านการแจ้งเตือน</li>
                <li>ยืนยันตัวตน (KYC) ที่หลังร้าน</li>
                <li>เริ่มลงสินค้าและเปิดขายได้เลย</li>
            </ol>
            @unless($kycApproved)
                <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-id"/></svg><span>ยืนยันตัวตนไว้ก่อนได้ที่ <a class="nvm-link" href="{{ route('user.kyc.index') }}">หน้า KYC</a></span></p>
            @endunless
        </aside>
    </div>
@endif

</div>
@endsection
