{{--
    ประวัติธุรกรรม Coins (user.video-coins.transactions) — กรองตามประเภท/ช่วงวันที่ + แบ่งหน้า · ธีมโนวาเต็ม
    Controller: User\VideoCoinController@transactions (GET type, date_from, date_to)
    ตัวแปร: transactions (paginator ของ VideoCoinTransaction), pageTitle
--}}
@extends('layouts.user-nova')

@section('title', 'ประวัติธุรกรรม Coins')

@push('styles')
    <link rel="stylesheet" href="{{ asset('theme-nova/member/classroom-video.css') }}?v={{ filemtime(public_path('theme-nova/member/classroom-video.css')) }}">
@endpush

@php
    // ตัวเลือกประเภท (ตรงกับ enum ของ video_coin_transactions.type)
    $typeOptions = [
        'earned_video' => 'ได้รับจากวิดีโอ',
        'earned_quest' => 'ได้รับจากภารกิจ',
        'earned_level_up' => 'โบนัสเลื่อนเลเวล',
        'earned_referral' => 'ได้รับจากแนะนำ',
        'earned_bonus' => 'โบนัส',
        'spent_exchange' => 'แลกเป็นเงิน',
        'spent_shop' => 'ซื้อสินค้า',
        'adjustment' => 'ปรับยอด / คืนเหรียญ',
    ];
    $filtered = request()->filled('type') || request()->filled('date_from') || request()->filled('date_to');
@endphp

@section('content')
<div class="nvm-stack nvm-cv">

<section class="nvm-ph" aria-labelledby="nvm-cv-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.video-coins.index') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>Video Coins</a>
            <div class="nvm-eyebrow nvm-latin">Coin history</div>
            <h1 id="nvm-cv-h">ประวัติธุรกรรม Coins</h1>
            <p class="nvm-ph__lead">ดูรายการได้รับและใช้จ่าย Video Coins ทั้งหมดของคุณ</p>
            <div class="nvm-pills">
                <span class="nvm-pill nvm-pill--glass nvm-num"><svg class="nvm-ico"><use href="#nvm-i-history"/></svg>{{ number_format($transactions->total()) }} รายการ</span>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/deco/scroll.webp') }}" alt="" width="200" height="200">
    </div>
</section>

<section class="nvm-card nvm-box" aria-labelledby="nvm-cv-ft-h">
    <div class="nvm-sec-h"><div><small class="nvm-latin">Filter</small><h2 id="nvm-cv-ft-h">ค้นหารายการ</h2></div></div>
    <form action="{{ route('user.video-coins.transactions') }}" method="GET" class="nvm-filter nvm-cv-filter">
        <div class="nvm-field">
            <label class="nvm-label" for="vc-type">ประเภท</label>
            <select id="vc-type" name="type" class="nvm-input">
                <option value="">ทั้งหมด</option>
                @foreach($typeOptions as $val => $label)
                    <option value="{{ $val }}" @selected(request('type') === $val)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="nvm-field">
            <label class="nvm-label" for="vc-from">จากวันที่</label>
            <input id="vc-from" type="date" name="date_from" value="{{ request('date_from') }}" class="nvm-input nvm-num">
        </div>
        <div class="nvm-field">
            <label class="nvm-label" for="vc-to">ถึงวันที่</label>
            <input id="vc-to" type="date" name="date_to" value="{{ request('date_to') }}" class="nvm-input nvm-num">
        </div>
        <div class="nvm-filter__btns">
            <button type="submit" class="nvm-btn nvm-btn--gold"><svg class="nvm-ico"><use href="#nvm-i-search"/></svg>ค้นหา</button>
            @if($filtered)
                <a href="{{ route('user.video-coins.transactions') }}" class="nvm-btn nvm-btn--line">ล้าง</a>
            @endif
        </div>
    </form>
</section>

<section class="nvm-card nvm-box" aria-labelledby="nvm-cv-ls-h">
    <div class="nvm-sec-h"><div><small class="nvm-latin">Transactions</small><h2 id="nvm-cv-ls-h">รายการธุรกรรม</h2></div></div>
    @if($transactions->count() > 0)
        <ul class="nvm-txs">
            @foreach($transactions as $tx)
                @include('user.video-coins.partials.nova-tx', ['tx' => $tx, 'showDate' => true])
            @endforeach
        </ul>
        @if($transactions->hasPages())
            <div class="nvm-pager nvm-gap-t">{{ $transactions->withQueryString()->links() }}</div>
        @endif
    @else
        <div class="nvm-empty">
            <img src="{{ asset('images/nova/wallet/empty.webp') }}" alt="" width="86" height="86" loading="lazy">
            <b>{{ $filtered ? 'ไม่พบรายการตามเงื่อนไขที่ค้นหา' : 'ไม่พบประวัติธุรกรรม' }}</b>
            {{ $filtered ? 'ลองเปลี่ยนประเภทหรือช่วงวันที่' : 'เริ่มดูวิดีโอเพื่อรับ Video Coins กันเลย' }}
        </div>
    @endif
</section>

</div>
@endsection
