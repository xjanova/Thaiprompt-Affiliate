{{--
 | หน้าแนะนำสำหรับผู้ขาย / ร้านรถเข็น / ตลาดนัด (taladsod.landing.seller) — ธีม V4 (frontend-v4)
 | Controller: FreshMarket\HomeController@landingSeller
 | อ่านโปรฯ GP ฟรีช่วงเปิดตัว + อัตรา GP ตลาดสดจาก PricingEngine (อ่านไม่ได้ = ไม่แสดงตัวเลข)
 --}}
@extends('layouts.frontend-v4')

@section('title', 'เปิดร้านฟรีในตลาดสด · รถเข็น ตลาดนัด ร้านเล็ก ขายออนไลน์ได้ทันที')
@section('meta_description', 'เปิดร้านในตลาดสดไทยพร้อมฟรี รถเข็นและตลาดนัดบอกลูกค้าได้ว่าวันนี้ขายที่ไหนด้วยปุ่มเดียว ลูกค้าติดตามร้านแล้วได้แจ้งเตือนเมื่อร้านเปิด มีไรเดอร์รับของไปส่ง')

@php
    $lsGpFree = false;
    $lsGpRate = null;
    try {
        $lsPricing = app(\App\Services\Pricing\PricingEngine::class);
        $lsGpFree = $lsPricing->gpPromoActive();
        $lsGpRate = $lsPricing->gpRateForFreshListing(new \App\Models\FreshMarketListing);
    } catch (\Throwable $e) {
        $lsGpFree = false;
    }

    $lsIsSeller = auth()->check() && \App\Models\FreshMarketSeller::where('user_id', auth()->id())->exists();
    $lsCtaUrl = $lsIsSeller ? route('taladsod.seller.dashboard') : route('taladsod.register-seller');
    $lsCtaLabel = $lsIsSeller ? 'ไปหน้าร้านของฉัน' : 'สมัครเปิดร้านฟรี';
    $lsLineUrl = config('services.line.fresh_market_add_friend_url');

    $lsWho = [
        ['icon' => 'fa-cart-flatbed', 'label' => 'รถเข็นขายอาหาร'],
        ['icon' => 'fa-tents', 'label' => 'แผงตลาดนัด'],
        ['icon' => 'fa-bowl-rice', 'label' => 'ร้านข้าวแกง ตามสั่ง'],
        ['icon' => 'fa-apple-whole', 'label' => 'ผัก ผลไม้ ของสด'],
        ['icon' => 'fa-cookie-bite', 'label' => 'ขนม ของทำเองที่บ้าน'],
    ];
    $lsFeatures = [
        ['icon' => 'fa-location-crosshairs', 'tone' => 'gold', 'title' => 'ปุ่มเดียว "เปิดร้านที่นี่วันนี้"', 'text' => 'ย้ายไปขายที่ไหน กดปุ่มเดียวลูกค้าก็เห็นร้านคุณบนแผนที่ ส่งตำแหน่งสดได้ระหว่างขาย'],
        ['icon' => 'fa-bell', 'tone' => 'bad', 'title' => 'ลูกค้าประจำได้แจ้งเตือน', 'text' => 'ลูกค้ากดติดตามร้าน พอคุณเปิดร้าน ระบบแจ้งเตือนให้ทันที'],
        ['icon' => 'fa-sliders', 'tone' => 'info', 'title' => 'เมนูมีตัวเลือกได้', 'text' => 'ตั้ง "เลือกเนื้อสัตว์" "เพิ่มไข่ดาว" "ระดับความเผ็ด" พร้อมราคาเพิ่มได้เอง'],
        ['icon' => 'fa-motorcycle', 'tone' => 'deep', 'title' => 'มีไรเดอร์ไปส่งให้', 'text' => 'ลูกค้าเลือกให้ไรเดอร์ส่งได้ ร้านแค่เตรียมของ ระบบเรียกไรเดอร์ใกล้ร้านมารับ'],
        ['icon' => 'fa-sack-dollar', 'tone' => 'ok', 'title' => 'เงินเข้ากระเป๋าอัตโนมัติ', 'text' => 'ลูกค้าจ่ายผ่านกระเป๋าเงิน ระบบโอนให้ร้านทันทีที่ลูกค้าได้รับของ ดูรายได้รายวันได้'],
        ['icon' => 'fa-mobile-screen', 'tone' => 'gold', 'title' => 'จัดการได้จากมือถือ', 'text' => 'รับออเดอร์ เปิด-ปิดร้าน ลงเมนู ทำได้ทั้งเว็บ แอป และ LINE'],
    ];
    $lsSteps = [
        ['title' => 'สมัครร้าน', 'text' => 'กรอกชื่อร้าน เบอร์โทร ปักหมุดร้าน ใช้เวลาไม่ถึง 3 นาที'],
        ['title' => 'ลงเมนู', 'text' => 'ถ่ายรูป ตั้งราคา เพิ่มตัวเลือก ลงได้หลายเมนู'],
        ['title' => 'กดเปิดร้าน', 'text' => 'ไปถึงจุดขายแล้วกด "เปิดร้านที่นี่วันนี้"'],
        ['title' => 'รับออเดอร์', 'text' => 'กดรับ → เตรียม → พร้อมส่ง เงินเข้ากระเป๋าเมื่อลูกค้าได้ของ'],
    ];
@endphp

@section('content')
<x-theme-v4.shop-kit />
@include('taladsod.partials.kit')
<x-theme-v4.public-header active="merchant" />

<main class="ts-scope" style="flex:1; padding-bottom:48px;">
    <div class="sf-wrap">
        @include('taladsod.partials.nav', ['active' => 'open-shop'])

        <section class="ts-hero">
            <img class="ts-hero-img" src="{{ asset('images/taladsod/banner-merchant.webp') }}" alt="">
            <div class="ts-hero-in">
                @if($lsGpFree)
                    <span class="ts-pill solid ts-tone-ok" style="align-self:flex-start; font-size:13px; padding:8px 14px;"><i class="fas fa-gift" aria-hidden="true"></i> ฟรี GP ช่วงเปิดตัว — ไม่หักค่าธรรมเนียมการขาย</span>
                @else
                    <span class="ts-pill solid ts-tone-gold" style="align-self:flex-start;"><i class="fas fa-store" aria-hidden="true"></i> เปิดร้านฟรี ไม่มีค่าสมัคร</span>
                @endif
                <h1 class="ts-hero-title">รถเข็น ตลาดนัด ร้านเล็ก<br>ก็ขายออนไลน์ได้วันนี้</h1>
                <p class="ts-hero-sub">บอกลูกค้าว่าวันนี้ขายที่ไหนด้วยปุ่มเดียว รับออเดอร์ มีไรเดอร์ไปส่ง เงินเข้ากระเป๋าอัตโนมัติ</p>
                <div class="ts-row" style="margin-top:6px;">
                    <a href="{{ $lsCtaUrl }}" class="ts-btn3d ts-tone-gold lg"><i class="fas fa-store" aria-hidden="true"></i> {{ $lsCtaLabel }}</a>
                    <a href="#ls-how" class="ts-btn3d soft lg"><i class="fas fa-circle-play" aria-hidden="true"></i> ดูวิธีเริ่มขาย</a>
                </div>
            </div>
        </section>

        <section class="sf-section" aria-labelledby="ls-who-h">
            <div class="sf-section-h"><h2 id="ls-who-h" class="sf-title">เหมาะกับใคร</h2></div>
            <div class="sf-scroll">
                @foreach($lsWho as $who)
                    <span class="sf-chip" style="min-height:48px; padding:0 18px; font-size:13.5px;"><i class="fas {{ $who['icon'] }}" style="color:var(--accent2);" aria-hidden="true"></i> {{ $who['label'] }}</span>
                @endforeach
            </div>
        </section>

        <section class="sf-section" aria-labelledby="ls-feat-h">
            <div class="sf-section-h"><h2 id="ls-feat-h" class="sf-title">ขายง่าย ได้เงินไว</h2></div>
            <div class="ts-grid" style="--ts-min:260px;">
                @foreach($lsFeatures as $feature)
                    <div class="tp-card ts-stat ts-tone-{{ $feature['tone'] }}" style="gap:8px;">
                        <span class="ic"><i class="fas {{ $feature['icon'] }}" aria-hidden="true"></i></span>
                        <b style="font-size:15px;">{{ $feature['title'] }}</b>
                        <span class="ts-muted" style="font-size:13.5px; line-height:1.6;">{{ $feature['text'] }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        <section id="ls-how" class="sf-section" aria-labelledby="ls-how-h" style="scroll-margin-top:90px;">
            <div class="sf-section-h"><h2 id="ls-how-h" class="sf-title">เริ่มขายใน 4 ขั้นตอน</h2></div>
            <div class="tp-card">
                <ol class="sf-tl">
                    @foreach($lsSteps as $i => $step)
                        <li class="sf-tl-item">
                            <span class="sf-tl-dot ts-num">{{ $i + 1 }}</span>
                            <div style="padding-top:6px;">
                                <b style="font-size:15px;">{{ $step['title'] }}</b>
                                <p class="ts-muted" style="margin:4px 0 0; font-size:13.5px; line-height:1.6;">{{ $step['text'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>

        <section class="sf-section" aria-labelledby="ls-fee-h">
            <div class="ts-grid" style="--ts-min:280px;">
                <div class="tp-card ts-stack">
                    <h2 id="ls-fee-h" class="ts-h2"><i class="fas fa-receipt" style="color:var(--accent2);" aria-hidden="true"></i> ค่าธรรมเนียมชัดเจน</h2>
                    <div class="ts-kv"><span>ค่าสมัคร / ค่าเปิดร้าน</span><b style="color:var(--ts-ok);">ฟรี</b></div>
                    <div class="ts-kv">
                        <span>ค่า GP ต่อออเดอร์</span>
                        @if($lsGpFree)
                            <b style="color:var(--ts-ok);">ฟรีช่วงเปิดตัว</b>
                        @elseif($lsGpRate !== null)
                            <b>{{ rtrim(rtrim(number_format((float) $lsGpRate, 2), '0'), '.') }}% ของยอดขาย</b>
                        @else
                            <b>ตามที่ประกาศในหน้าร้าน</b>
                        @endif
                    </div>
                    <div class="ts-kv"><span>ค่าส่งไรเดอร์</span><b>ลูกค้าเป็นคนจ่าย</b></div>
                    <p class="ts-help">ร้านเห็นยอดรับจริงทุกออเดอร์ก่อนกดรับ ไม่มีค่าใช้จ่ายแอบแฝง</p>
                </div>
                <div class="tp-card" style="padding:0; overflow:hidden; min-height:220px; position:relative;">
                    <img src="{{ asset('images/taladsod/krapao-hero.webp') }}" alt="ตัวอย่างเมนูผัดกะเพราพร้อมตัวเลือก" loading="lazy" style="position:absolute; inset:0; width:100%; height:100%; object-fit:cover;">
                    <div style="position:absolute; left:14px; right:14px; bottom:14px;" class="tp-card">
                        <b style="font-size:14px;">ผัดกะเพราราดข้าว</b>
                        <div class="ts-row" style="gap:6px; margin-top:6px;">
                            <span class="ts-pill ts-tone-gold">หมูสับ</span><span class="ts-pill ts-tone-gold">กุ้ง +฿20</span><span class="ts-pill ts-tone-ok">ไข่ดาว +฿10</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="sf-section">
            <div class="tp-card ts-stack" style="align-items:center; text-align:center; padding:clamp(22px,5vw,40px); background:linear-gradient(135deg, var(--a2soft), var(--a1soft));">
                <h2 class="ts-h1" style="font-size:clamp(22px,4vw,30px);">พร้อมเปิดร้านแล้วหรือยัง?</h2>
                <p class="ts-muted" style="margin:0;">สมัครวันนี้ เปิดขายได้เลย{{ $lsGpFree ? ' — ฟรี GP ช่วงเปิดตัว' : '' }}</p>
                <div class="ts-row" style="justify-content:center;">
                    <a href="{{ $lsCtaUrl }}" class="ts-btn3d ts-tone-gold lg"><i class="fas fa-store" aria-hidden="true"></i> {{ $lsCtaLabel }}</a>
                    @if($lsLineUrl)
                        <a href="{{ $lsLineUrl }}" target="_blank" rel="noopener" class="ts-btn3d ts-tone-ok lg"><i class="fab fa-line" aria-hidden="true"></i> สมัครผ่าน LINE</a>
                    @endif
                </div>
            </div>
        </section>

        <section class="sf-section" aria-label="บทบาทอื่น">
            <div class="ts-row" style="justify-content:center; gap:10px;">
                <span class="ts-muted">สนใจบทบาทอื่น?</span>
                <a href="{{ route('taladsod.landing.buyer') }}" class="sf-chip"><i class="fas fa-basket-shopping" aria-hidden="true"></i> สั่งของสด</a>
                <a href="{{ route('taladsod.landing.rider') }}" class="sf-chip"><i class="fas fa-motorcycle" aria-hidden="true"></i> สมัครไรเดอร์</a>
            </div>
        </section>
    </div>
</main>

<x-theme-v4.public-footer />
@endsection
