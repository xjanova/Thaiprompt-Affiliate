{{--
 | หน้าแนะนำสำหรับไรเดอร์ (taladsod.landing.rider) — ธีม V4 (frontend-v4)
 | Controller: FreshMarket\HomeController@landingRider
 | ตัวเลขค่าส่ง/ส่วนแบ่งอ่านจาก Setting กลุ่ม rider (ชุดเดียวกับ DeliveryFeeCalculator)
 --}}
@extends('layouts.frontend-v4')

@section('title', 'สมัครไรเดอร์ รับงานส่งใกล้บ้าน · ตลาดสดไทยพร้อม')
@section('meta_description', 'มาเป็นไรเดอร์ตลาดสดไทยพร้อม เลือกเวลาเอง รับงานส่งของสดและอาหารใกล้บ้าน ค่าส่งเข้ากระเป๋าทันทีที่ส่งสำเร็จ')

@php
    $ui = \App\Support\TaladsodWebUi::class;
    $lrBase = (float) \App\Models\Setting::get('rider.base_fee', 30);
    $lrPerKm = (float) \App\Models\Setting::get('rider.per_km_fee', 10);
    $lrFreeKm = (float) \App\Models\Setting::get('rider.free_km', 2);
    $lrShare = (float) \App\Models\Setting::get('rider.rider_share_percent', 80);
    $lrDeposit = filter_var(\App\Models\Setting::get('rider.require_deposit', false), FILTER_VALIDATE_BOOLEAN);
    // ตัวอย่างคำนวณด้วยสูตรจริงของระบบ (DeliveryFeeCalculator) — อ่านไม่ได้ใช้สูตรประมาณ
    $lrExampleKm = 4;
    try {
        $lrQuote = (new \App\Services\DeliveryFeeCalculator)->quoteForDistance($lrExampleKm);
        $lrExampleFee = (float) $lrQuote['total_fee'];
        $lrExampleEarn = (float) $lrQuote['rider_earnings'];
    } catch (\Throwable $e) {
        $lrExampleFee = max($lrBase, $lrBase + max(0, $lrExampleKm - $lrFreeKm) * $lrPerKm);
        $lrExampleEarn = round($lrExampleFee * $lrShare / 100, 2);
    }

    $lrIsRider = auth()->check() && \App\Models\Rider::where('user_id', auth()->id())->exists();
    $lrCtaUrl = $lrIsRider ? route('user.rider.dashboard') : route('user.rider.register');
    $lrCtaLabel = $lrIsRider ? 'ไปหน้าไรเดอร์ของฉัน' : 'สมัครเป็นไรเดอร์';

    $lrPerks = [
        ['icon' => 'fa-clock', 'tone' => 'gold', 'title' => 'เลือกเวลาเอง', 'text' => 'เปิด-ปิดรับงานได้ตลอด ไม่มีกะบังคับ'],
        ['icon' => 'fa-location-dot', 'tone' => 'info', 'title' => 'งานใกล้บ้าน', 'text' => 'ระบบส่งงานให้ไรเดอร์ที่อยู่ใกล้ร้านก่อน ไม่ต้องวิ่งไกล'],
        ['icon' => 'fa-bolt', 'tone' => 'ok', 'title' => 'ได้เงินทันที', 'text' => 'ส่งสำเร็จ ค่าส่งส่วนของคุณเข้ากระเป๋าเงินทันที ถอนได้'],
        ['icon' => 'fa-shield-heart', 'tone' => 'bad', 'title' => 'ปลอดภัย โปร่งใส', 'text' => 'ลูกค้าเห็นตำแหน่งคุณเฉพาะระหว่างส่งงานของเขาเท่านั้น'],
    ];
    $lrNeeds = [
        'อายุ 18 ปีขึ้นไป มีบัตรประชาชน',
        'สมาร์ทโฟนที่เปิด GPS ได้',
        'มอเตอร์ไซค์หรือรถยนต์: ใบขับขี่ + ทะเบียนรถ (จักรยาน/เดินส่งไม่ต้องใช้)',
        'รูปถ่ายหน้าตรงสำหรับโปรไฟล์',
    ];
    $lrSteps = [
        ['title' => 'สมัครออนไลน์', 'text' => 'กรอกข้อมูลส่วนตัวและข้อมูลรถ'],
        ['title' => 'อัปโหลดเอกสาร', 'text' => 'ถ่ายรูปบัตร ใบขับขี่ ทะเบียนรถ จากมือถือได้เลย'],
        ['title' => 'รอทีมงานตรวจ', 'text' => 'อนุมัติแล้วได้รับแจ้งเตือนทันที'],
        ['title' => 'เปิดรับงาน', 'text' => 'กดพร้อมรับงาน รับงานใกล้คุณ ส่งสำเร็จได้เงิน'],
    ];
@endphp

@section('content')
<x-theme-v4.shop-kit />
@include('taladsod.partials.kit')
<x-theme-v4.public-header active="rider" />

<main class="ts-scope" style="flex:1; padding-bottom:48px;">
    <div class="sf-wrap">
        @include('taladsod.partials.nav', ['active' => null])

        <section class="ts-hero">
            <img class="ts-hero-img" src="{{ asset('images/taladsod/banner-rider.webp') }}" alt="">
            <div class="ts-hero-in">
                <span class="ts-pill solid ts-tone-info" style="align-self:flex-start;"><i class="fas fa-motorcycle" aria-hidden="true"></i> รับสมัครไรเดอร์ทั่วประเทศ</span>
                <h1 class="ts-hero-title">มาเป็นไรเดอร์<br>รับงานใกล้บ้าน รายได้เข้าทุกวัน</h1>
                <p class="ts-hero-sub">ส่งของสดและอาหารจากร้านในชุมชน เลือกเวลาเอง ค่าส่งเข้ากระเป๋าทันทีที่ส่งสำเร็จ</p>
                <div class="ts-row" style="margin-top:6px;">
                    <a href="{{ $lrCtaUrl }}" class="ts-btn3d ts-tone-info lg"><i class="fas fa-id-card" aria-hidden="true"></i> {{ $lrCtaLabel }}</a>
                    <a href="#lr-earn" class="ts-btn3d soft lg"><i class="fas fa-calculator" aria-hidden="true"></i> ดูรายได้</a>
                </div>
            </div>
        </section>

        <section class="sf-section">
            <div class="ts-grid" style="--ts-min:230px;">
                @foreach($lrPerks as $perk)
                    <div class="tp-card ts-stat ts-tone-{{ $perk['tone'] }}" style="gap:8px;">
                        <span class="ic"><i class="fas {{ $perk['icon'] }}" aria-hidden="true"></i></span>
                        <b style="font-size:15px;">{{ $perk['title'] }}</b>
                        <span class="ts-muted" style="font-size:13.5px; line-height:1.6;">{{ $perk['text'] }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        <section id="lr-earn" class="sf-section" aria-labelledby="lr-earn-h" style="scroll-margin-top:90px;">
            <div class="ts-grid" style="--ts-min:300px; align-items:start;">
                <div class="tp-card ts-stack">
                    <h2 id="lr-earn-h" class="ts-h2"><i class="fas fa-sack-dollar" style="color:var(--accent2);" aria-hidden="true"></i> รายได้ต่องาน</h2>
                    <div class="ts-kv"><span>ค่าส่งเริ่มต้น</span><b>฿{{ $ui::money($lrBase) }}{{ $lrFreeKm > 0 ? ' (รวม '.rtrim(rtrim(number_format($lrFreeKm, 1), '0'), '.').' กม. แรก)' : '' }}</b></div>
                    <div class="ts-kv"><span>กิโลเมตรถัดไป</span><b>฿{{ $ui::money($lrPerKm) }} / กม.</b></div>
                    <div class="ts-kv"><span>ส่วนแบ่งของไรเดอร์</span><b style="color:var(--ts-ok);">{{ rtrim(rtrim(number_format($lrShare, 1), '0'), '.') }}% ของค่าส่ง</b></div>
                    <hr class="ts-divider">
                    <div class="sf-note sf-note-ok">
                        <b>ตัวอย่าง:</b> ส่งระยะ {{ $lrExampleKm }} กม. ค่าส่ง ฿{{ $ui::money($lrExampleFee) }} → คุณได้ <b class="ts-money">฿{{ $ui::money($lrExampleEarn) }}</b> ต่องาน
                    </div>
                    <p class="ts-help">งานเก็บเงินปลายทาง: ต้องมียอดในกระเป๋าพอสำหรับค่าสินค้าที่เก็บแทนร้าน ระบบหักคืนอัตโนมัติเมื่อส่งสำเร็จ</p>
                </div>
                <div class="tp-card ts-stack">
                    <h2 class="ts-h2"><i class="fas fa-list-check" style="color:var(--accent2);" aria-hidden="true"></i> สิ่งที่ต้องมี</h2>
                    <ul style="list-style:none; margin:0; padding:0;" class="ts-stack">
                        @foreach($lrNeeds as $need)
                            <li class="ts-row" style="gap:10px; flex-wrap:nowrap; align-items:flex-start; font-size:14px;"><i class="fas fa-circle-check" style="color:var(--ts-ok); margin-top:3px;" aria-hidden="true"></i> <span>{{ $need }}</span></li>
                        @endforeach
                    </ul>
                    <div class="sf-note {{ $lrDeposit ? 'sf-note-warn' : 'sf-note-ok' }}">
                        {{ $lrDeposit ? 'มีค่าประกันงานแรกเข้า (ขอคืนได้เมื่อหยุดทำงาน) — ทีมงานแจ้งยอดหลังอนุมัติ' : 'ไม่มีค่าสมัคร ไม่มีค่าประกันงาน สมัครฟรี' }}
                    </div>
                </div>
            </div>
        </section>

        <section class="sf-section" aria-labelledby="lr-steps-h">
            <div class="sf-section-h"><h2 id="lr-steps-h" class="sf-title">เริ่มรับงานใน 4 ขั้นตอน</h2></div>
            <div class="tp-card">
                <ol class="sf-tl">
                    @foreach($lrSteps as $i => $step)
                        <li class="sf-tl-item">
                            <span class="sf-tl-dot ts-num">{{ $i + 1 }}</span>
                            <div style="padding-top:6px;">
                                <b style="font-size:15px;">{{ $step['title'] }}</b>
                                <p class="ts-muted" style="margin:4px 0 0; font-size:13.5px;">{{ $step['text'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>

        <section class="sf-section">
            <div class="tp-card ts-stack" style="align-items:center; text-align:center; padding:clamp(22px,5vw,40px); background:linear-gradient(135deg, var(--a1soft), var(--a2soft));">
                <h2 class="ts-h1" style="font-size:clamp(22px,4vw,30px);">พร้อมออกถนนแล้วหรือยัง?</h2>
                <p class="ts-muted" style="margin:0;">สมัครวันนี้ อนุมัติแล้วเปิดรับงานได้ทันที</p>
                <a href="{{ $lrCtaUrl }}" class="ts-btn3d ts-tone-info lg"><i class="fas fa-motorcycle" aria-hidden="true"></i> {{ $lrCtaLabel }}</a>
            </div>
        </section>

        <section class="sf-section" aria-label="บทบาทอื่น">
            <div class="ts-row" style="justify-content:center; gap:10px;">
                <span class="ts-muted">สนใจบทบาทอื่น?</span>
                <a href="{{ route('taladsod.landing.buyer') }}" class="sf-chip"><i class="fas fa-basket-shopping" aria-hidden="true"></i> สั่งของสด</a>
                <a href="{{ route('taladsod.landing.seller') }}" class="sf-chip"><i class="fas fa-store" aria-hidden="true"></i> เปิดร้านฟรี</a>
            </div>
        </section>
    </div>
</main>

<x-theme-v4.public-footer />
@endsection
