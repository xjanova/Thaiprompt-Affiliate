{{--
    แลก Coins เป็นเงิน (user.video-coins.exchange) — เลือกแพคเกจ + ประวัติการแลก · ธีมโนวาเต็ม
    Controller: User\VideoCoinController@exchange · ส่งฟอร์ม → POST user.video-coins.exchange.submit (exchange_rate_id)
    ตัวแปร: videoCoin, exchangeRates, exchangeHistory, pageTitle
    กดแลก = หัก Coins ทันที (รอแอดมินอนุมัติ / ปฏิเสธแล้วคืน Coins) จึงต้องยืนยันก่อนส่ง + กันกดซ้ำ
--}}
@extends('layouts.user-nova')

@section('title', 'แลก Coins เป็นเงิน')

@push('styles')
    <link rel="stylesheet" href="{{ asset('theme-nova/member/classroom-video.css') }}?v={{ filemtime(public_path('theme-nova/member/classroom-video.css')) }}">
@endpush

@php
    $vcNum = fn ($n, int $d = 0) => number_format((float) $n, $d);
    [$balInt, $balDec] = explode('.', number_format((float) ($videoCoin->balance ?? 0), 2));

    // สถานะคำขอแลก → [ข้อความ, โทนชิป]
    $exStatusMap = [
        'pending' => ['รอดำเนินการ', 'nvm-chip--warn'],
        'approved' => ['อนุมัติแล้ว', 'nvm-chip--ok'],
        'completed' => ['สำเร็จ', 'nvm-chip--ok'],
        'rejected' => ['ปฏิเสธ (คืน Coins แล้ว)', 'nvm-chip--bad'],
    ];

    $flashes = [
        'success' => ['ok', 'check', 'สำเร็จ'],
        'error' => ['bad', 'info', 'ทำรายการไม่สำเร็จ'],
    ];
@endphp

@section('content')
<div class="nvm-stack nvm-cv">

<section class="nvm-ph" aria-labelledby="nvm-cv-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.video-coins.index') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>Video Coins</a>
            <div class="nvm-eyebrow nvm-latin">Exchange</div>
            <h1 id="nvm-cv-h">แลก Coins เป็นเงิน</h1>
            <p class="nvm-ph__lead">แลก Video Coins เป็นเงินบาทเข้ากระเป๋าเงิน หลังแอดมินอนุมัติ</p>
            <div class="nvm-bal">
                <small>ยอด Coins ของคุณ</small>
                <b class="nvm-foil nvm-num">{{ $balInt }}<span>.{{ $balDec }}</span></b><em>VC</em>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/wallet/transfer.webp') }}" alt="" width="200" height="200">
    </div>
</section>

@foreach($flashes as $key => [$tone, $icon, $heading])
    @if(session($key))
        <section class="nvm-card nvm-alert nvm-alert--{{ $tone }}" role="{{ $tone === 'bad' ? 'alert' : 'status' }}">
            <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-{{ $icon }}"/></svg></span>
            <div class="nvm-alert__txt"><b>{{ $heading }}</b><p>{{ session($key) }}</p></div>
        </section>
    @endif
@endforeach

@if($errors->any())
    <section class="nvm-card nvm-alert nvm-alert--bad" role="alert">
        <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg></span>
        <div class="nvm-alert__txt"><b>ทำรายการไม่สำเร็จ</b><p>{{ $errors->first() }}</p></div>
    </section>
@endif

<section class="nvm-card nvm-box" aria-labelledby="nvm-cv-pk-h">
    <div class="nvm-sec-h"><div><small class="nvm-latin">Packages</small><h2 id="nvm-cv-pk-h">เลือกจำนวนที่ต้องการแลก</h2></div></div>
    @if($exchangeRates->isNotEmpty())
        <p class="nvm-help">กดเลือกแพคเกจแล้วยืนยัน ระบบจะหัก Coins ทันทีและส่งคำขอให้แอดมินอนุมัติ (ถ้าถูกปฏิเสธจะคืน Coins ให้)</p>
        <div class="nvm-cv-rates nvm-gap-t">
            @foreach($exchangeRates as $rate)
                @php
                    // เช็คว่ายอด Coins พอแลกหรือไม่
                    $canExchange = (float) $videoCoin->balance >= (float) $rate->coins_amount;
                    $confirmText = 'ยืนยันแลก '.$vcNum($rate->coins_amount).' VC เป็นเงิน '.$vcNum($rate->money_amount, 2).' บาท? ระบบจะหัก Coins ทันที';
                @endphp
                <form action="{{ route('user.video-coins.exchange.submit') }}" method="POST" class="nvm-cv-form"
                      data-confirm="{{ $confirmText }}"
                      x-data="{ busy: false, go(e) { if (this.busy || ! window.confirm(e.target.dataset.confirm)) { e.preventDefault(); return; } this.busy = true; } }"
                      x-on:submit="go($event)">
                    @csrf
                    <input type="hidden" name="exchange_rate_id" value="{{ $rate->id }}">
                    <button type="submit" class="nvm-cv-rate" @disabled(! $canExchange) :disabled="busy || {{ $canExchange ? 'false' : 'true' }}">
                        <span class="nvm-cv-rate__pair">
                            <span><b class="nvm-num">{{ $vcNum($rate->coins_amount) }}</b> <small>VC</small></span>
                            <svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>
                            <span><b class="nvm-num is-in">{{ $vcNum($rate->money_amount) }}</b> <small>บาท</small></span>
                        </span>
                        <span class="nvm-cv-rate__info">
                            <span class="nvm-num">อัตรา: 1 Coin = {{ number_format((float) $rate->rate, 4) }} บาท</span>
                            @if($rate->min_level)
                                <span>ต้องมี Level {{ $rate->min_level }} ขึ้นไป</span>
                            @endif
                            @if($rate->max_coins_per_day)
                                <span class="nvm-num">แลกได้สูงสุด {{ $vcNum($rate->max_coins_per_day) }} VC ต่อวัน</span>
                            @endif
                        </span>
                        @if($canExchange)
                            <span class="nvm-cv-rate__cta"><svg class="nvm-ico"><use href="#nvm-i-swap"/></svg><span x-text="busy ? 'กำลังส่งคำขอ...' : 'แลกเลย'">แลกเลย</span></span>
                        @else
                            <span class="nvm-cv-rate__cta is-off"><svg class="nvm-ico"><use href="#nvm-i-lock"/></svg>Coins ไม่พอ</span>
                        @endif
                    </button>
                </form>
            @endforeach
        </div>
    @else
        <div class="nvm-empty">
            <img src="{{ asset('images/nova/deco/coins.webp') }}" alt="" width="86" height="86" loading="lazy">
            <b>ยังไม่มีอัตราแลกเปลี่ยน</b>
            เมื่อแอดมินเปิดอัตราแลกเปลี่ยนจะแสดงที่นี่
        </div>
    @endif
</section>

@if($exchangeHistory->isNotEmpty())
    <section class="nvm-card nvm-box" aria-labelledby="nvm-cv-hs-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">History</small><h2 id="nvm-cv-hs-h">ประวัติการแลก</h2></div><span class="nvm-chip">10 รายการล่าสุด</span></div>
        <ul class="nvm-txs">
            @foreach($exchangeHistory as $exchange)
                @php
                    // สถานะที่ไม่รู้จัก → แสดงค่าดิบด้วยโทนกลาง
                    [$exLabel, $exTone] = $exStatusMap[$exchange->status] ?? [(string) $exchange->status, ''];
                    $exDone = in_array($exchange->status, ['approved', 'completed'], true);
                @endphp
                <li class="nvm-tx">
                    <span class="nvm-tx__ic {{ $exDone ? 'is-in' : '' }}"><svg class="nvm-ico"><use href="#nvm-i-swap"/></svg></span>
                    <div class="nvm-tx__t">
                        <b class="nvm-num">{{ $vcNum($exchange->coins_amount) }} VC → ฿{{ $vcNum($exchange->money_amount, 2) }}</b>
                        <small class="nvm-num">{{ $exchange->created_at?->format('d/m/Y H:i') }}</small>
                    </div>
                    <div class="nvm-tx__r">
                        <span class="nvm-tx__amt nvm-num {{ $exDone ? 'is-in' : '' }}">฿{{ $vcNum($exchange->money_amount, 2) }}</span>
                        <span class="nvm-chip {{ $exTone }}">{{ $exLabel }}</span>
                    </div>
                </li>
            @endforeach
        </ul>
    </section>
@endif

</div>
@endsection
