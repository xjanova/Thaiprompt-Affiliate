{{--
 | หน้าแนะนำสำหรับผู้ซื้อ (taladsod.landing.buyer) — ธีม V4 (frontend-v4)
 | Controller: FreshMarket\HomeController@landingBuyer (เก็บ ?ref= ลิงก์ชวนเพื่อนไว้ใน session)
 --}}
@extends('layouts.frontend-v4')

@section('title', 'สั่งของสด อาหารใกล้บ้าน · ตลาดสดไทยพร้อม')
@section('meta_description', 'สั่งของสดและอาหารร้อนๆ จากร้านและรถเข็นใกล้บ้าน เลือกเมนูได้ตามใจ ไรเดอร์ส่งถึงมือ ติดตามได้บนแผนที่ หรือไปรับเองที่ร้าน')

@php
    $lbReferred = session()->has('taladsod_referral_token');
    $lbLineUrl = config('services.line.fresh_market_add_friend_url');
    $lbRegister = \Illuminate\Support\Facades\Route::has('register') ? route('register') : url('/register');
    $lbSteps = [
        ['icon' => 'fa-map-location-dot', 'title' => 'เลือกร้านใกล้บ้าน', 'text' => 'ดูร้านและรถเข็นที่เปิดอยู่ตอนนี้บนแผนที่'],
        ['icon' => 'fa-sliders', 'title' => 'เลือกเมนูตามใจ', 'text' => 'เลือกเนื้อสัตว์ เพิ่มไข่ดาว เขียนโน้ตถึงร้านได้'],
        ['icon' => 'fa-motorcycle', 'title' => 'ไรเดอร์ส่งถึงมือ', 'text' => 'หรือไปรับเองที่ร้าน ไม่เสียค่าส่ง'],
        ['icon' => 'fa-face-smile', 'title' => 'รับของ ให้ดาว', 'text' => 'ยืนยันรับของแล้วรีวิวร้านและไรเดอร์'],
    ];
    $lbPerks = [
        ['icon' => 'fa-bell', 'tone' => 'bad', 'title' => 'รถเข็นเปิดเมื่อไหร่ รู้ทันที', 'text' => 'กดติดตามร้านโปรด ระบบแจ้งเตือนเมื่อร้านเปิดขายใกล้คุณ'],
        ['icon' => 'fa-location-dot', 'tone' => 'info', 'title' => 'เห็นไรเดอร์บนแผนที่', 'text' => 'ติดตามไรเดอร์สดตลอดทาง และแชร์ตำแหน่งให้ไรเดอร์หาเจอง่าย (เลือกเองได้)'],
        ['icon' => 'fa-wallet', 'tone' => 'ok', 'title' => 'จ่ายง่าย ปลอดภัย', 'text' => 'จ่ายด้วยกระเป๋าเงิน ระบบถือเงินไว้จนได้รับของ หรือเก็บเงินปลายทาง'],
        ['icon' => 'fa-tags', 'tone' => 'gold', 'title' => 'ราคาเดียวกับหน้าร้าน', 'text' => 'ราคาที่ร้านตั้ง ไม่บวกเพิ่ม เห็นค่าส่งก่อนกดสั่งทุกครั้ง'],
    ];
@endphp

@section('content')
<x-theme-v4.shop-kit />
@include('taladsod.partials.kit')
<x-theme-v4.public-header active="taladsod" />

<main class="ts-scope" style="flex:1; padding-bottom:48px;">
    <div class="sf-wrap">
        @include('taladsod.partials.nav', ['active' => null])

        <section class="ts-hero">
            <img class="ts-hero-img" src="{{ asset('images/taladsod/banner-market.webp') }}" alt="">
            <div class="ts-hero-in">
                @if($lbReferred)
                    <span class="ts-pill solid ts-tone-ok" style="align-self:flex-start;"><i class="fas fa-user-group" aria-hidden="true"></i> เพื่อนชวนคุณมาช้อปตลาดสด</span>
                @else
                    <span class="ts-pill solid ts-tone-gold" style="align-self:flex-start;"><i class="fas fa-carrot" aria-hidden="true"></i> ตลาดสดไทยพร้อม</span>
                @endif
                <h1 class="ts-hero-title">ของสด อาหารร้อนๆ<br>จากร้านใกล้บ้าน ส่งถึงมือ</h1>
                <p class="ts-hero-sub">ร้านในชุมชน รถเข็น ตลาดนัด รวมไว้ในที่เดียว เลือกได้ สั่งง่าย ติดตามได้ทุกขั้นตอน</p>
                <div class="ts-row" style="margin-top:6px;">
                    <a href="{{ route('taladsod.home') }}" class="ts-btn3d ts-tone-gold lg"><i class="fas fa-basket-shopping" aria-hidden="true"></i> เริ่มช้อปเลย</a>
                    @guest
                        <a href="{{ $lbRegister }}" class="ts-btn3d soft lg"><i class="fas fa-user-plus" aria-hidden="true"></i> สมัครฟรี</a>
                    @endguest
                </div>
            </div>
        </section>

        <section class="sf-section" aria-labelledby="lb-steps-h">
            <div class="sf-section-h"><h2 id="lb-steps-h" class="sf-title">สั่งง่าย 4 ขั้นตอน</h2></div>
            <div class="ts-grid" style="--ts-min:200px;">
                @foreach($lbSteps as $i => $step)
                    <div class="tp-card ts-stack" style="gap:10px;">
                        <div class="ts-row" style="justify-content:space-between;">
                            <span class="tp-tile" style="width:48px; height:48px; border-radius:16px; font-size:20px;"><i class="fas {{ $step['icon'] }}" aria-hidden="true"></i></span>
                            <span class="ts-num" style="font-size:30px; color:color-mix(in srgb, var(--accent1) 45%, transparent);">{{ $i + 1 }}</span>
                        </div>
                        <b style="font-size:15.5px;">{{ $step['title'] }}</b>
                        <span class="ts-muted" style="font-size:13.5px; line-height:1.6;">{{ $step['text'] }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="sf-section" aria-labelledby="lb-menu-h">
            <div class="tp-card" style="padding:0; overflow:hidden;">
                <div class="ts-grid" style="--ts-min:300px; gap:0; align-items:stretch;">
                    <img src="{{ asset('images/taladsod/krapao-hero.webp') }}" alt="ผัดกะเพราราดข้าว เมนูเปิดตัว" loading="lazy" style="width:100%; height:100%; min-height:240px; object-fit:cover;">
                    <div class="ts-stack" style="padding:clamp(18px,4vw,32px); justify-content:center;">
                        <span class="ts-pill ts-tone-bad" style="align-self:flex-start;"><i class="fas fa-fire" aria-hidden="true"></i> เมนูเปิดตัว</span>
                        <h2 id="lb-menu-h" class="ts-h1" style="font-size:clamp(22px,3.8vw,30px);">ผัดกะเพราราดข้าว</h2>
                        <p class="ts-muted" style="margin:0; line-height:1.7;">เลือกได้ หมูสับ ไก่ หมึก หรือกุ้ง เพิ่มไข่ดาวกรอบๆ ได้ตามใจ ผัดร้อนๆ ส่งถึงมือ</p>
                        <div class="ts-row">
                            <span class="ts-money" style="font-size:28px;">เริ่ม ฿50</span>
                            <a href="{{ route('taladsod.search', ['q' => 'กะเพรา']) }}" class="ts-btn3d ts-tone-gold"><i class="fas fa-utensils" aria-hidden="true"></i> สั่งกะเพราเลย</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="sf-section" aria-labelledby="lb-perks-h">
            <div class="sf-section-h"><h2 id="lb-perks-h" class="sf-title">ทำไมต้องตลาดสดไทยพร้อม</h2></div>
            <div class="ts-grid" style="--ts-min:240px;">
                @foreach($lbPerks as $perk)
                    <div class="tp-card ts-stat ts-tone-{{ $perk['tone'] }}" style="gap:8px;">
                        <span class="ic"><i class="fas {{ $perk['icon'] }}" aria-hidden="true"></i></span>
                        <b style="font-size:15px;">{{ $perk['title'] }}</b>
                        <span class="ts-muted" style="font-size:13.5px; line-height:1.6;">{{ $perk['text'] }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="sf-section">
            <div class="tp-card ts-stack" style="align-items:center; text-align:center; padding:clamp(22px,5vw,40px); background:linear-gradient(135deg, var(--a1soft), var(--a2soft));">
                <h2 class="ts-h1" style="font-size:clamp(22px,4vw,30px);">หิวแล้วใช่ไหม? ร้านใกล้บ้านรออยู่</h2>
                <p class="ts-muted" style="margin:0;">เปิดดูร้านที่เปิดอยู่ตอนนี้ แล้วสั่งได้ในไม่กี่แตะ</p>
                <div class="ts-row" style="justify-content:center;">
                    <a href="{{ route('taladsod.home') }}#near-me" class="ts-btn3d ts-tone-gold lg"><i class="fas fa-map-location-dot" aria-hidden="true"></i> ดูร้านใกล้ฉัน</a>
                    @if($lbLineUrl)
                        <a href="{{ $lbLineUrl }}" target="_blank" rel="noopener" class="ts-btn3d ts-tone-ok lg"><i class="fab fa-line" aria-hidden="true"></i> สั่งผ่าน LINE</a>
                    @endif
                </div>
            </div>
        </section>

        <section class="sf-section" aria-label="บทบาทอื่น">
            <div class="ts-row" style="justify-content:center; gap:10px;">
                <span class="ts-muted">สนใจบทบาทอื่น?</span>
                <a href="{{ route('taladsod.landing.seller') }}" class="sf-chip"><i class="fas fa-store" aria-hidden="true"></i> เปิดร้านฟรี</a>
                <a href="{{ route('taladsod.landing.rider') }}" class="sf-chip"><i class="fas fa-motorcycle" aria-hidden="true"></i> สมัครไรเดอร์</a>
            </div>
        </section>
    </div>
</main>

<x-theme-v4.public-footer />
@endsection
