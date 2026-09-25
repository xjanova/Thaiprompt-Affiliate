{{--
 | สมัครเปิดร้านตลาดสด (taladsod.register-seller) — ธีม V4 (user-v4)
 | Controller: FreshMarket\HomeController@registerSeller (สมัครแล้ว → ไปหน้าร้านวันนี้)
 | ส่งฟอร์ม POST taladsod.register-seller.store: shop_name*, phone*, address*, latitude*, longitude*, agree_terms*(accepted),
 |   shop_description, province, district, sub_district
 --}}
@extends('layouts.user-v4')

@section('title', 'สมัครเปิดร้านตลาดสด')

@php
    $rsGpFree = false;
    try {
        $rsGpFree = app(\App\Services\Pricing\PricingEngine::class)->gpPromoActive();
    } catch (\Throwable $e) {
        $rsGpFree = false;
    }
    $rsTermsUrl = \Illuminate\Support\Facades\Route::has('terms-of-service') ? route('terms-of-service') : url('/terms');
@endphp

@section('content')
<x-theme-v4.shop-kit />
@include('taladsod.partials.kit')
<x-theme-v4.leaflet />

<div class="ts-scope ts-stack" style="gap:16px; max-width:880px; margin:0 auto;">
    <section class="ts-hero" style="min-height:0;">
        <img class="ts-hero-img" src="{{ asset('images/taladsod/banner-merchant.webp') }}" alt="">
        <div class="ts-hero-in" style="min-height:0; padding-top:clamp(40px,8vw,80px);">
            @if($rsGpFree)
                <span class="ts-pill solid ts-tone-ok" style="align-self:flex-start;"><i class="fas fa-gift" aria-hidden="true"></i> ฟรี GP ช่วงเปิดตัว</span>
            @endif
            <h1 class="ts-hero-title" style="font-size:clamp(22px,4.4vw,32px);">สมัครเปิดร้านตลาดสด</h1>
            <p class="ts-hero-sub">กรอกข้อมูล 2 นาที แล้วลงเมนูและกดเปิดร้านได้เลย — รถเข็น ตลาดนัด ร้านเล็ก ขายได้ทันที</p>
        </div>
    </section>

    @if(session('info'))
        <div class="sf-note sf-note-info">{{ session('info') }}</div>
    @endif

    <form method="POST" action="{{ route('taladsod.register-seller.store') }}" class="ts-stack" style="gap:16px;" x-data="{ agree: {{ old('agree_terms') ? 'true' : 'false' }}, sending: false }" x-on:submit="sending = true">
        @csrf
        @if($errors->any())
            <div class="sf-note sf-note-err" role="alert">
                <b><i class="fas fa-circle-exclamation" aria-hidden="true"></i> สมัครไม่สำเร็จ กรุณาตรวจสอบ</b>
                <ul style="margin:6px 0 0; padding-left:18px;">
                    @foreach($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @include('taladsod.partials.shop-form', ['shopData' => null])

        <section class="tp-card ts-stack">
            <label class="ts-row" style="gap:10px; flex-wrap:nowrap; align-items:flex-start; cursor:pointer;">
                <input type="checkbox" name="agree_terms" value="1" x-model="agree" style="width:22px; height:22px; flex:none; margin-top:2px; accent-color:var(--accent1);">
                <span style="font-size:13.5px; line-height:1.6;">
                    ฉันยอมรับ<a href="{{ $rsTermsUrl }}" target="_blank" rel="noopener" class="ts-link">เงื่อนไขการใช้งาน</a> และเงื่อนไขการขายในตลาดสด:
                    ขายสินค้าที่ถูกกฎหมาย ปลอดภัย ราคาตรงกับหน้าร้าน รับ/ยกเลิกออเดอร์ตามจริง และยินยอมให้หักค่าธรรมเนียมการขาย (GP) ตามที่ประกาศ{{ $rsGpFree ? ' (ช่วงเปิดตัวฟรี)' : '' }}
                </span>
            </label>
            @error('agree_terms')<p class="ts-err">{{ $message }}</p>@enderror
            <button type="submit" class="ts-btn3d ts-tone-gold lg block" :disabled="!agree || sending">
                <i class="fas" :class="sending ? 'fa-circle-notch ts-spin' : 'fa-store'" aria-hidden="true"></i> สมัครเปิดร้าน
            </button>
            <p class="ts-help" style="margin:0; text-align:center;">สมัครแล้วตั้งเป็น "ร้านเคลื่อนที่" ได้ที่หน้าร้านวันนี้ (สำหรับรถเข็น/ตลาดนัด)</p>
        </section>
    </form>
</div>
@endsection
