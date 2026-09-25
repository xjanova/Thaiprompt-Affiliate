{{--
 | ตั้งค่าร้านตลาดสด (taladsod.seller.profile) — ธีม V4 (user-v4)
 | Controller: FreshMarket\HomeController@sellerProfile
 | ตัวแปร: $seller
 | ส่งฟอร์ม PUT taladsod.seller.profile.update: shop_name*, phone*, address*, latitude*, longitude*, shop_description, province, district, sub_district
 | โหมดร้าน: POST taladsod.seller.mobile-mode {is_mobile} (JSON)
 --}}
@extends('layouts.user-v4')

@section('title', 'ตั้งค่าร้าน · '.$seller->shop_name)

@php
    $modeCfg = [
        'isMobile' => $seller->isMobileShop(),
        'url' => route('taladsod.seller.mobile-mode'),
    ];
@endphp

@section('content')
<x-theme-v4.shop-kit />
@include('taladsod.partials.kit')
<x-theme-v4.leaflet />

<div class="ts-scope ts-stack" style="gap:16px; max-width:880px; margin:0 auto;">
    @include('taladsod.partials.seller-nav', ['seller' => $seller, 'active' => 'profile'])

    <div class="ts-row" style="justify-content:space-between;">
        <div>
            <h1 class="ts-h1">ตั้งค่าร้าน</h1>
            <p class="ts-muted" style="margin:6px 0 0; font-size:13.5px;">สถานะร้าน: <b>{{ $seller->status_label }}</b> · รหัสแนะนำร้าน {{ $seller->referral_code }}</p>
        </div>
        <a href="{{ route('taladsod.seller', $seller->id) }}" class="tp-btn" target="_blank" rel="noopener"><i class="fas fa-store" aria-hidden="true"></i> ดูหน้าร้าน</a>
    </div>

    {{-- ประเภทร้าน --}}
    <section class="tp-card ts-stack" x-data="{ isMobile: {{ $modeCfg['isMobile'] ? 'true' : 'false' }}, busy: false,
            async save(on) {
                this.busy = true;
                const r = await window.ts.post(@js($modeCfg['url']), { is_mobile: on ? 1 : 0 });
                this.busy = false;
                if (!r.ok) { window.ts.notify(r.message, 'error'); return; }
                this.isMobile = !!(r.data && r.data.is_mobile);
                window.ts.notify(r.message, 'success');
            } }" aria-labelledby="sp-mode-h">
        <h2 id="sp-mode-h" class="ts-h2"><i class="fas fa-cart-flatbed" style="color:var(--accent2);" aria-hidden="true"></i> ประเภทร้าน</h2>
        <div class="ts-grid" style="--ts-min:230px; gap:10px;">
            <button type="button" class="ts-choice" :class="!isMobile ? 'is-on' : ''" x-on:click="isMobile && save(false)" :disabled="busy" :aria-pressed="!isMobile ? 'true' : 'false'">
                <span class="ind"><i class="fas fa-check" aria-hidden="true"></i></span>
                <span class="name">ร้านประจำที่<span class="ts-muted ts-small" style="display:block; font-weight:600;">ลูกค้าเห็นร้านตามที่อยู่ด้านล่าง</span></span>
            </button>
            <button type="button" class="ts-choice" :class="isMobile ? 'is-on' : ''" x-on:click="!isMobile && save(true)" :disabled="busy" :aria-pressed="isMobile ? 'true' : 'false'">
                <span class="ind"><i class="fas fa-check" aria-hidden="true"></i></span>
                <span class="name">ร้านเคลื่อนที่ (รถเข็น/ตลาดนัด)<span class="ts-muted ts-small" style="display:block; font-weight:600;">กด "เปิดร้านที่นี่วันนี้" ทุกครั้งที่ไปขาย</span></span>
            </button>
        </div>
        <p class="ts-help" style="margin:0;" x-show="isMobile">เปลี่ยนเป็นร้านเคลื่อนที่แล้วร้านจะปิดไว้ก่อน ไปเปิดร้านได้ที่ <a href="{{ route('taladsod.seller.dashboard') }}" class="ts-link">หน้าร้านวันนี้</a></p>
    </section>

    <form method="POST" action="{{ route('taladsod.seller.profile.update') }}" class="ts-stack" style="gap:16px;" x-data="{ sending: false }" x-on:submit="sending = true">
        @csrf
        @method('PUT')
        @if($errors->any())
            <div class="sf-note sf-note-err" role="alert">
                <b><i class="fas fa-circle-exclamation" aria-hidden="true"></i> บันทึกไม่สำเร็จ กรุณาตรวจสอบ</b>
                <ul style="margin:6px 0 0; padding-left:18px;">
                    @foreach($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @include('taladsod.partials.shop-form', ['shopData' => $seller])

        <div class="tp-card ts-row" style="justify-content:flex-end; position:sticky; bottom:12px; z-index:20;">
            <button type="submit" class="ts-btn3d ts-tone-gold" :disabled="sending">
                <i class="fas" :class="sending ? 'fa-circle-notch ts-spin' : 'fa-floppy-disk'" aria-hidden="true"></i> บันทึกข้อมูลร้าน
            </button>
        </div>
    </form>
</div>
@endsection
