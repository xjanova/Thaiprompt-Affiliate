{{--
 | ตั้งค่าร้านตลาดสด (taladsod.seller.profile) — ธีมโนวาเต็ม (layouts.user-nova)
 | Controller: FreshMarket\HomeController@sellerProfile
 | ตัวแปร: $seller
 | ส่งฟอร์ม PUT taladsod.seller.profile.update: shop_name*, phone*, address*, latitude*, longitude*, shop_description, province, district, sub_district
 | โหมดร้าน: POST taladsod.seller.mobile-mode {is_mobile} (JSON ผ่าน window.ts.post)
 --}}
@extends('layouts.user-nova')

@section('title', 'ตั้งค่าร้าน · '.$seller->shop_name)

@php
    $modeCfg = [
        'isMobile' => $seller->isMobileShop(),
        'url' => route('taladsod.seller.mobile-mode'),
    ];
    $statusMessage = $seller->sellingStatusMessage();
@endphp

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/taladsod.css') }}?v={{ filemtime(public_path('theme-nova/member/taladsod.css')) }}">@endpush

@section('content')
@include('taladsod.partials.kit-js')
<x-theme-v4.leaflet />

<div class="nvm-stack">

<section class="nvm-ph" aria-labelledby="nvm-td-sp-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <div class="nvm-eyebrow nvm-latin">Shop settings</div>
            <h1 id="nvm-td-sp-h">ตั้งค่าร้าน</h1>
            <p class="nvm-ph__lead">{{ $seller->shop_name }} · รหัสแนะนำร้าน <span class="nvm-num">{{ $seller->referral_code }}</span></p>
            <div class="nvm-pills">
                <span class="nvm-pill {{ $seller->status_key === 'active' ? 'nvm-pill--ok' : ($seller->status_key === 'unverified' ? 'nvm-pill--warn' : 'nvm-pill--bad') }}">สถานะร้าน: {{ $seller->status_label }}</span>
            </div>
            <div class="nvm-hero__actions nvm-td-acts">
                <a class="nvm-btn nvm-btn--ghost nvm-btn--sm" href="{{ route('taladsod.seller', $seller->id) }}" target="_blank" rel="noopener"><svg class="nvm-ico"><use href="#nvm-i-store"/></svg>ดูหน้าร้าน</a>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/icons/store.webp') }}" alt="" width="200" height="200">
    </div>
</section>

@include('taladsod.partials.seller-nav', ['seller' => $seller, 'active' => 'profile'])

{{-- 🪪 ร้านยังขายไม่ได้ (รอยืนยันตัวตนในแอป / รอแอดมิน / ถูกระงับ) — ข้อความเดียวกับแดชบอร์ด แอป และ LINE --}}
@if($statusMessage)
    <section class="nvm-card nvm-alert" role="status">
        <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-clock"/></svg></span>
        <div class="nvm-alert__txt"><b>ร้านยังขายไม่ได้</b><p>{{ $statusMessage }}</p></div>
        @if($seller->needsOwnerKyc())
            <a class="nvm-btn nvm-btn--gold nvm-btn--sm" href="{{ \App\Services\Ekyc\EkycGate::downloadUrl() }}"><svg class="nvm-ico"><use href="#nvm-i-id"/></svg>ดาวน์โหลดแอปเพื่อยืนยันตัวตน</a>
        @endif
    </section>
@endif

{{-- ประเภทร้าน (บันทึกทันทีผ่าน AJAX) --}}
<section class="nvm-card nvm-box" aria-labelledby="sp-mode-h"
         x-data="{ isMobile: {{ $modeCfg['isMobile'] ? 'true' : 'false' }}, busy: false,
            async save(on) {
                if (on === this.isMobile || this.busy) { return; }
                this.busy = true;
                const r = await window.ts.post(@js($modeCfg['url']), { is_mobile: on ? 1 : 0 });
                this.busy = false;
                if (r.ok) { this.isMobile = !!(r.data && r.data.is_mobile); }
                // บันทึกไม่สำเร็จ = ตั้งปุ่มเลือกกลับตามสถานะจริง (ค่าเดิมไม่เปลี่ยน Alpine จึงไม่วาดใหม่ให้เอง)
                this.$root.querySelectorAll('input[name=sp_mode]').forEach((el) => { el.checked = (el.value === 'mobile') === this.isMobile; });
                window.ts.notify(r.message, r.ok ? 'success' : 'error');
            } }">
    <div class="nvm-sec-h"><div><small class="nvm-latin">Shop type</small><h2 id="sp-mode-h">ประเภทร้าน</h2></div></div>
    <div class="nvm-opts nvm-opts--2" role="radiogroup" aria-labelledby="sp-mode-h">
        <label class="nvm-opt" :class="busy ? 'is-off' : ''">
            <input type="radio" name="sp_mode" value="fixed" :checked="!isMobile" x-on:change="save(false)" :disabled="busy" @checked(! $modeCfg['isMobile'])>
            <img src="{{ asset('images/nova/icons/store.webp') }}" alt="" width="44" height="44" loading="lazy">
            <span><b>ร้านประจำที่</b><small>ลูกค้าเห็นร้านตามที่อยู่ด้านล่าง</small></span>
        </label>
        <label class="nvm-opt" :class="busy ? 'is-off' : ''">
            <input type="radio" name="sp_mode" value="mobile" :checked="isMobile" x-on:change="save(true)" :disabled="busy" @checked($modeCfg['isMobile'])>
            <img src="{{ asset('images/nova/icons/basket.webp') }}" alt="" width="44" height="44" loading="lazy">
            <span><b>ร้านเคลื่อนที่ (รถเข็น/ตลาดนัด)</b><small>กด "เปิดร้านที่นี่วันนี้" ทุกครั้งที่ไปขาย</small></span>
        </label>
    </div>
    <p class="nvm-note" x-show="isMobile" @if(! $modeCfg['isMobile']) x-cloak @endif><svg class="nvm-ico"><use href="#nvm-i-info"/></svg><span>เปลี่ยนเป็นร้านเคลื่อนที่แล้วร้านจะปิดไว้ก่อน ไปเปิดร้านได้ที่ <a class="nvm-link" href="{{ route('taladsod.seller.dashboard') }}">หน้าร้านวันนี้</a></span></p>
</section>

<form method="POST" action="{{ route('taladsod.seller.profile.update') }}" class="nvm-stack" x-data="{ sending: false }" x-on:submit="sending = true" @pageshow.window="sending = false">
    @csrf
    @method('PUT')
    @if($errors->any())
        <section class="nvm-card nvm-alert nvm-alert--bad" role="alert">
            <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg></span>
            <div class="nvm-alert__txt">
                <b>บันทึกไม่สำเร็จ กรุณาตรวจสอบ</b>
                @foreach($errors->all() as $message)<p>{{ $message }}</p>@endforeach
            </div>
        </section>
    @endif

    @include('taladsod.partials.shop-form', ['shopData' => $seller])

    <div class="nvm-card nvm-td-bar">
        <span>ข้อมูลร้านแสดงให้ลูกค้าเห็นทันทีหลังบันทึก</span>
        <button type="submit" class="nvm-btn nvm-btn--gold" :disabled="sending">
            <svg class="nvm-ico"><use href="#nvm-i-check"/></svg><span x-text="sending ? 'กำลังบันทึก…' : 'บันทึกข้อมูลร้าน'">บันทึกข้อมูลร้าน</span>
        </button>
    </div>
</form>

</div>
@endsection
