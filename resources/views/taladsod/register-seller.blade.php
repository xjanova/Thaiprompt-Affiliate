{{--
 | สมัครเปิดร้านตลาดสด (taladsod.register-seller) — ธีมโนวาเต็ม (layouts.user-nova)
 | Controller: FreshMarket\HomeController@registerSeller (สมัครแล้ว → ไปหน้าร้านวันนี้)
 | ส่งฟอร์ม POST taladsod.register-seller.store: shop_name*, phone*, address*, latitude*, longitude*, agree_terms*(accepted),
 |   shop_description, province, district, sub_district
 --}}
@extends('layouts.user-nova')

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

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/taladsod.css') }}?v={{ filemtime(public_path('theme-nova/member/taladsod.css')) }}">@endpush

@section('content')
@include('taladsod.partials.kit-js')
<x-theme-v4.leaflet />

<div class="nvm-stack">

<section class="nvm-ph" aria-labelledby="nvm-td-rs-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('taladsod.home') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>ตลาดสด</a>
            <div class="nvm-eyebrow nvm-latin">Open your stall</div>
            <h1 id="nvm-td-rs-h">สมัครเปิดร้านตลาดสด</h1>
            <p class="nvm-ph__lead">กรอกข้อมูล 2 นาที แล้วลงเมนูและกดเปิดร้านได้เลย — รถเข็น ตลาดนัด ร้านเล็ก ขายได้ทันที</p>
            @if($rsGpFree)
                <div class="nvm-pills">
                    <span class="nvm-pill nvm-pill--ok"><svg class="nvm-ico"><use href="#nvm-i-gift"/></svg>ฟรี GP ช่วงเปิดตัว</span>
                </div>
            @endif
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/svc/market.webp') }}" alt="" width="200" height="200">
    </div>
</section>

@if(session('info'))
    <section class="nvm-card nvm-alert nvm-alert--info" role="status">
        <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg></span>
        <div class="nvm-alert__txt"><b>แจ้งให้ทราบ</b><p>{{ session('info') }}</p></div>
    </section>
@endif

<form method="POST" action="{{ route('taladsod.register-seller.store') }}" class="nvm-stack"
      x-data="{ agree: {{ old('agree_terms') ? 'true' : 'false' }}, sending: false }" x-on:submit="sending = true" @pageshow.window="sending = false">
    @csrf
    @if($errors->any())
        <section class="nvm-card nvm-alert nvm-alert--bad" role="alert">
            <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg></span>
            <div class="nvm-alert__txt">
                <b>สมัครไม่สำเร็จ กรุณาตรวจสอบ</b>
                @foreach($errors->all() as $message)<p>{{ $message }}</p>@endforeach
            </div>
        </section>
    @endif

    @include('taladsod.partials.shop-form', ['shopData' => null])

    <section class="nvm-card nvm-box nvm-form" aria-label="ยอมรับเงื่อนไขและสมัคร">
        <label class="nvm-td-agree">
            <input type="checkbox" name="agree_terms" value="1" x-model="agree">
            <span>
                ฉันยอมรับ<a href="{{ $rsTermsUrl }}" target="_blank" rel="noopener">เงื่อนไขการใช้งาน</a> และเงื่อนไขการขายในตลาดสด:
                ขายสินค้าที่ถูกกฎหมาย ปลอดภัย ราคาตรงกับหน้าร้าน รับ/ยกเลิกออเดอร์ตามจริง และยินยอมให้หักค่าธรรมเนียมการขาย (GP) ตามที่ประกาศ{{ $rsGpFree ? ' (ช่วงเปิดตัวฟรี)' : '' }}
            </span>
        </label>
        @error('agree_terms')<span class="nvm-err">{{ $message }}</span>@enderror
        <button type="submit" class="nvm-btn nvm-btn--gold nvm-btn--block nvm-w-full" :disabled="!agree || sending">
            <svg class="nvm-ico"><use href="#nvm-i-store"/></svg><span x-text="sending ? 'กำลังสมัคร…' : 'สมัครเปิดร้าน'">สมัครเปิดร้าน</span>
        </button>
        <span class="nvm-help">สมัครแล้วตั้งเป็น "ร้านเคลื่อนที่" ได้ที่หน้าร้านวันนี้ (สำหรับรถเข็น/ตลาดนัด)</span>
    </section>
</form>

</div>
@endsection
