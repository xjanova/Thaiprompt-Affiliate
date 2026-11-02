{{--
    Video Coins (user.video-coins.index) — ยอดเหรียญ + ธุรกรรมล่าสุด + อัตราแลก · ธีมโนวาเต็ม
    Controller: User\VideoCoinController@index
    ตัวแปร: videoCoin, earnedToday, spentToday, recentTransactions, exchangeRates, pageTitle
--}}
@extends('layouts.user-nova')

@section('title', 'Video Coins')

@push('styles')
    <link rel="stylesheet" href="{{ asset('theme-nova/member/classroom-video.css') }}?v={{ filemtime(public_path('theme-nova/member/classroom-video.css')) }}">
@endpush

@php
    $vcNum = fn ($n, int $d = 0) => number_format((float) $n, $d);
    [$balInt, $balDec] = explode('.', number_format((float) ($videoCoin->balance ?? 0), 2));

    // การ์ดตัวเลข: [ป้ายไทย, ป้ายอังกฤษ, ค่า, ภาพ, ท้ายการ์ด]
    $kpis = [
        ['ได้รับวันนี้', 'Earned today', $vcNum($earnedToday ?? 0, 2), 'images/nova/deco/coins.webp', 'Coins ที่ได้วันนี้'],
        ['ใช้ไปวันนี้', 'Spent today', $vcNum($spentToday ?? 0, 2), 'images/nova/deco/bag.webp', 'แลกเงิน · ซื้อสินค้า'],
        ['สะสมทั้งหมด', 'Lifetime earned', $vcNum($videoCoin->lifetime_earned ?? 0), 'images/nova/deco/crystal.webp', 'ตั้งแต่เริ่มใช้งาน'],
        ['แลกไปแล้ว', 'Exchanged', $vcNum($videoCoin->lifetime_exchanged ?? 0), 'images/nova/wallet/transfer.webp', 'ใช้ไปทั้งหมด '.$vcNum($videoCoin->lifetime_spent ?? 0)],
    ];
@endphp

@section('content')
<div class="nvm-stack nvm-cv">

<section class="nvm-ph" aria-labelledby="nvm-cv-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <div class="nvm-eyebrow nvm-latin">Video Coins</div>
            <h1 id="nvm-cv-h">Video Coins ของฉัน</h1>
            <div class="nvm-bal">
                <small>ยอด Video Coins คงเหลือ</small>
                <b class="nvm-foil nvm-num">{{ $balInt }}<span>.{{ $balDec }}</span></b><em>VC</em>
            </div>
            <div class="nvm-pills">
                <a class="nvm-pill nvm-pill--gold" href="{{ route('user.video-coins.exchange') }}"><svg class="nvm-ico"><use href="#nvm-i-swap"/></svg>แลกเป็นเงิน</a>
                <a class="nvm-pill nvm-pill--glass" href="{{ route('user.video-coins.transactions') }}"><svg class="nvm-ico"><use href="#nvm-i-history"/></svg>ประวัติธุรกรรม</a>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/deco/coins.webp') }}" alt="" width="200" height="200">
    </div>
</section>

<section class="nvm-kpis" aria-label="สรุป Video Coins">
    @foreach($kpis as [$label, $en, $value, $img, $foot])
        <div class="nvm-card nvm-kpi">
            <img class="nvm-kpi__img" src="{{ asset($img) }}" alt="" width="78" height="78" loading="lazy">
            <div class="nvm-kpi__lbl">{{ $label }}</div>
            <div class="nvm-kpi__en nvm-latin">{{ $en }}</div>
            <div class="nvm-kpi__val nvm-num">{{ $value }}</div>
            <div class="nvm-kpi__foot"><small>{{ $foot }}</small></div>
        </div>
    @endforeach
</section>

<div class="nvm-split">
    <section class="nvm-card nvm-box" aria-labelledby="nvm-cv-tx-h">
        <div class="nvm-sec-h">
            <div><small class="nvm-latin">Recent activity</small><h2 id="nvm-cv-tx-h">ธุรกรรมล่าสุด</h2></div>
            <a href="{{ route('user.video-coins.transactions') }}">ดูทั้งหมด<svg class="nvm-ico"><use href="#nvm-i-chev"/></svg></a>
        </div>
        @if($recentTransactions->isNotEmpty())
            <ul class="nvm-txs">
                @foreach($recentTransactions as $tx)
                    @include('user.video-coins.partials.nova-tx', ['tx' => $tx])
                @endforeach
            </ul>
        @else
            <div class="nvm-empty">
                <img src="{{ asset('images/nova/wallet/empty.webp') }}" alt="" width="86" height="86" loading="lazy">
                <b>ยังไม่มีประวัติธุรกรรม</b>
                เริ่มดูวิดีโอเพื่อรับ Coins ฟรี
            </div>
        @endif
    </section>

    <section class="nvm-card nvm-box" aria-labelledby="nvm-cv-rt-h">
        <div class="nvm-sec-h">
            <div><small class="nvm-latin">Exchange rates</small><h2 id="nvm-cv-rt-h">อัตราแลกเปลี่ยน</h2></div>
            @if($exchangeRates->isNotEmpty())
                <a href="{{ route('user.video-coins.exchange') }}">แลก Coins<svg class="nvm-ico"><use href="#nvm-i-chev"/></svg></a>
            @endif
        </div>
        @if($exchangeRates->isNotEmpty())
            <div class="nvm-cv-rates">
                @foreach($exchangeRates as $rate)
                    <div class="nvm-cv-rate">
                        <div class="nvm-cv-rate__pair">
                            <span><b class="nvm-num">{{ $vcNum($rate->coins_amount) }}</b> <small>VC</small></span>
                            <svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>
                            <span><b class="nvm-num is-in">{{ $vcNum($rate->money_amount) }}</b> <small>บาท</small></span>
                        </div>
                        <div class="nvm-cv-rate__info"><span class="nvm-num">อัตรา: 1 Coin = {{ number_format((float) $rate->rate, 4) }} บาท</span></div>
                    </div>
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
</div>

</div>
@endsection
