{{--
    เติมเงิน (ผ่านเกตเวย์ชำระเงิน) — เลือกยอด แล้วไปเลือกช่องทางในขั้นถัดไป (ธีมโนวาเต็ม)
    หน้านี้ถูกลิงก์จากร้านค้า/เกม/ชำระเงิน เมื่อยอดในกระเป๋าไม่พอ
--}}
@extends('layouts.user-nova')

@section('title', 'เติมเงิน')

@php
    $nvR = fn ($name) => \Illuminate\Support\Facades\Route::has($name);
    [$balInt, $balDec] = explode('.', number_format((float) $wallet->balance, 2));
    $min = 100;
    $max = 100000;
    $presets = [100, 300, 500, 1000, 2000, 5000, 10000];
    $tpixOn = $nvR('tpix-pay.topup') && app(\App\Services\TpixPay\TpixPaySettings::class)->paymentsEnabled();
@endphp

@section('content')

<section class="nvm-ph" aria-labelledby="nvm-tu-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            @if($nvR('user.wallet.index'))
                <a class="nvm-ph__back" href="{{ route('user.wallet.index') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>กระเป๋าเงิน</a>
            @endif
            <div class="nvm-eyebrow nvm-latin">Top up</div>
            <h1 id="nvm-tu-h">เติมเงินเข้ากระเป๋า</h1>
            <p class="nvm-ph__lead">เลือกยอดที่ต้องการ แล้วเลือกช่องทางชำระในขั้นถัดไป</p>
            <div class="nvm-bal">
                <small>ยอดเงินปัจจุบัน</small>
                <b class="nvm-foil nvm-num">฿{{ $balInt }}<span>.{{ $balDec }}</span></b>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/wallet/deposit.webp') }}" alt="" width="200" height="200">
    </div>
</section>

@if($errors->any())
    <section class="nvm-card nvm-alert nvm-alert--bad" role="alert">
        <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg></span>
        <div class="nvm-alert__txt">
            <b>เติมเงินไม่สำเร็จ</b>
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    </section>
@endif

<div class="nvm-split">
    <div class="nvm-stack">
        <section class="nvm-card nvm-box" aria-labelledby="nvm-tua-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Step 1 of 2</small><h2 id="nvm-tua-h">เลือกยอดที่ต้องการเติม</h2></div></div>
            <form method="POST" action="{{ route('user.wallet.topup.process') }}" class="nvm-form"
                  x-data="{ amount: @js((string) old('amount', '')), busy: false, get amt() { var a = parseFloat(this.amount); return isNaN(a) ? 0 : a; }, get ok() { return this.amt >= {{ $min }} && this.amt <= {{ $max }}; } }"
                  @submit="if (! ok) { $event.preventDefault(); return; } busy = true" @pageshow.window="busy = false">
                @csrf
                <div class="nvm-field">
                    <label class="nvm-label" for="tu-amount">จำนวนเงิน (บาท) <em>*</em></label>
                    {{-- 🐛 (2026-10-29) เดิมช่องกรอกเองกับยอดที่ส่งจริงเป็นคนละตัว — ลบตัวเลขลงต่ำกว่า 100 ยอดที่ส่งยังเป็นค่าเก่า
                         ตอนนี้ช่องเดียว ส่งตามที่เห็นบนจอ --}}
                    <div class="nvm-amount"><span>฿</span><input id="tu-amount" name="amount" type="number" min="{{ $min }}" max="{{ $max }}" step="1" inputmode="numeric" class="nvm-input @error('amount') is-bad @enderror" placeholder="0" x-model="amount" required></div>
                    <div class="nvm-picks">
                        @foreach($presets as $preset)
                            <button type="button" :class="{ 'is-on': amt === {{ $preset }} }" @click="amount = '{{ $preset }}'">฿{{ number_format($preset) }}</button>
                        @endforeach
                    </div>
                    <span class="nvm-help">ขั้นต่ำ ฿{{ number_format($min) }} · สูงสุด ฿{{ number_format($max) }} ต่อรายการ</span>
                    <span class="nvm-err" x-show="amt > 0 && amt < {{ $min }}" x-cloak>ยอดขั้นต่ำ ฿{{ number_format($min) }}</span>
                    <span class="nvm-err" x-show="amt > {{ $max }}" x-cloak>เติมได้สูงสุด ฿{{ number_format($max) }} ต่อรายการ</span>
                </div>
                <button type="submit" class="nvm-btn nvm-btn--gold" :disabled="! ok || busy">
                    <svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>
                    <span x-text="busy ? 'กำลังสร้างรายการ…' : (ok ? 'ต่อไป · เติม ฿' + amt.toLocaleString('th-TH') : 'ต่อไป · เลือกช่องทางชำระ')">ต่อไป · เลือกช่องทางชำระ</span>
                </button>
            </form>
        </section>

        @if($tpixOn)
            <a class="nvm-card nvm-alert" href="{{ route('tpix-pay.topup') }}">
                <img class="nvm-alert__img" src="{{ asset('images/nova/deco/coins.webp') }}" alt="" width="56" height="56" loading="lazy">
                <div class="nvm-alert__txt">
                    <b>เติมด้วยเหรียญ TPIX</b>
                    <p>โอน TPIX บนเชน TPIX รับเงินบาทตามราคาตลาดจริง (TPIX DEX)</p>
                </div>
                <svg class="nvm-ico nvm-alert__side"><use href="#nvm-i-chev"/></svg>
            </a>
        @endif
    </div>

    <aside class="nvm-stack">
        <section class="nvm-card nvm-box" aria-labelledby="nvm-tus-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">How it works</small><h2 id="nvm-tus-h">ขั้นตอนเติมเงิน</h2></div></div>
            <ol class="nvm-steps">
                <li>เลือกยอดแล้วกด "ต่อไป"</li>
                <li>เลือกช่องทางชำระ เช่น พร้อมเพย์ — รายการมีอายุ 30 นาที</li>
                <li>ชำระเงินตามยอดที่แสดง (รวมสตางค์)</li>
                <li>ระบบตรวจเจอแล้ว เงินเข้ากระเป๋าอัตโนมัติ</li>
            </ol>
        </section>
        <section class="nvm-card nvm-box" aria-labelledby="nvm-tuu-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Use your balance</small><h2 id="nvm-tuu-h">ใช้เงินในกระเป๋าได้ที่</h2></div></div>
            <div class="nvm-pills">
                <span class="nvm-chip nvm-chip--gold">ซื้อสินค้าในร้านค้า</span>
                <span class="nvm-chip nvm-chip--gold">บริการ AI และบอท</span>
                <span class="nvm-chip nvm-chip--gold">คอร์สเรียน Academy</span>
                <span class="nvm-chip nvm-chip--gold">แพ็คเกจสมาชิก</span>
                <span class="nvm-chip nvm-chip--gold">โอน/สแกนจ่ายให้สมาชิก</span>
            </div>
            @if($nvR('user.wallet.deposit'))
                <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg><span>มีสลิปโอนธนาคารอยู่แล้ว? แนบสลิปได้ที่ <a class="nvm-link" href="{{ route('user.wallet.deposit') }}">หน้าฝากเงิน</a></span></p>
            @endif
        </section>
    </aside>
</div>
@endsection
