{{--
    กระเป๋าคริปโต — หน้าหลัก (ธีมโนวาเต็ม)
    CryptoWalletController::index → $wallet (กระเป๋าหลัก อาจเป็น null) · $balances (getAllBalances: code => [currency, balance, balance_thb])
    $totalValueTHB · $recentTransactions (10 รายการล่าสุดของสมาชิกคนนี้)
    $hasWallets — มีกระเป๋าอย่างน้อยหนึ่งใบ (มีแต่ไม่มีกระเป๋าหลัก → ปุ่มไปตั้งกระเป๋าหลักที่ /wallets)
    ยังไม่มีกระเป๋า → ปุ่มเปิดโมดัลสร้างกระเป๋า (ฟอร์มไม่ผ่าน = เปิดโมดัลค้างไว้พร้อมข้อความผิดพลาด)
--}}
@extends('layouts.user-nova')

@section('title', 'กระเป๋าคริปโต')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/crypto-wallet.css') }}?v={{ filemtime(public_path('theme-nova/member/crypto-wallet.css')) }}">@endpush

@php
    $nvR = fn ($name) => \Illuminate\Support\Facades\Route::has($name);
    [$balInt, $balDec] = explode('.', number_format((float) $totalValueTHB, 2, '.', ','));

    // เมนูด่วน: [ภาพ, ป้ายไทย, ป้ายอังกฤษ, route]
    $tiles = array_filter([
        ['images/nova/wallet/deposit.webp', 'ฝากเหรียญ', 'Deposit', 'user.crypto-wallet.deposit'],
        ['images/nova/wallet/withdraw.webp', 'ถอนเหรียญ', 'Withdraw', 'user.crypto-wallet.withdraw'],
        ['images/nova/wallet/transfer.webp', 'แลกเปลี่ยน', 'Exchange', 'user.crypto-wallet.exchange'],
        ['images/nova/deco/scroll.webp', 'ธุรกรรม', 'History', 'user.crypto-wallet.transactions'],
        ['images/nova/wallet/card.webp', 'พอร์ตของฉัน', 'Portfolio', 'user.crypto-wallet.portfolio'],
        ['images/nova/deco/crystal.webp', 'ราคาตลาด', 'Markets', 'user.crypto-wallet.trading'],
    ], fn ($t) => $nvR($t[3]));

    $statusPill = match ($wallet?->status) {
        'active' => ['ok', 'check', 'กระเป๋าพร้อมใช้งาน'],
        'locked' => ['bad', 'lock', 'กระเป๋าถูกล็อก'],
        'suspended' => ['bad', 'x', 'กระเป๋าถูกระงับ'],
        default => null,
    };
@endphp

@section('content')

<div class="nvm-stack" x-data="{ create: @js($errors->has('pin') || $errors->has('pin_confirmation')) }">

<section class="nvm-ph" aria-labelledby="nvm-cw-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <div class="nvm-eyebrow nvm-latin">Crypto wallet</div>
            <h1 id="nvm-cw-h">กระเป๋าคริปโต</h1>
            <div class="nvm-bal">
                <small>มูลค่ารวมทั้งหมด (ประมาณ)</small>
                <b class="nvm-foil nvm-num">฿{{ $balInt }}<span>.{{ $balDec }}</span></b>
            </div>
            <div class="nvm-pills">
                @if($statusPill)
                    <span class="nvm-pill nvm-pill--{{ $statusPill[0] }}"><svg class="nvm-ico"><use href="#nvm-i-{{ $statusPill[1] }}"/></svg>{{ $statusPill[2] }}</span>
                @elseif($hasWallets)
                    <span class="nvm-pill nvm-pill--glass"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>ยังไม่ได้ตั้งกระเป๋าหลัก</span>
                @else
                    <span class="nvm-pill nvm-pill--glass"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>ยังไม่มีกระเป๋าคริปโต</span>
                @endif
                @if(count($balances))
                    <span class="nvm-pill nvm-pill--glass"><svg class="nvm-ico"><use href="#nvm-i-wallet"/></svg>{{ number_format(count($balances)) }} สกุลเงิน</span>
                @endif
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/deco/coins.webp') }}" alt="" width="200" height="200">
    </div>
</section>

@include('user.crypto-wallet.partials.flash', ['errorTitle' => 'สร้างกระเป๋าไม่สำเร็จ'])

@if($wallet && count($tiles))
    <nav class="nvm-tiles" aria-label="เมนูกระเป๋าคริปโต">
        @foreach($tiles as [$img, $label, $en, $route])
            <a class="nvm-card nvm-tile" href="{{ route($route) }}">
                <img src="{{ asset($img) }}" alt="" width="76" height="76" loading="lazy">
                <b>{{ $label }}</b>
                <small class="nvm-latin">{{ $en }}</small>
            </a>
        @endforeach
    </nav>
@endif

<div class="nvm-split">
    <section class="nvm-card nvm-box" aria-labelledby="nvm-cw-bal-h">
        <div class="nvm-sec-h">
            <div><small class="nvm-latin">Balances</small><h2 id="nvm-cw-bal-h">ยอดคงเหลือแต่ละเหรียญ</h2></div>
            @if($wallet && $nvR('user.crypto-wallet.portfolio'))
                <a href="{{ route('user.crypto-wallet.portfolio') }}">ดูพอร์ต<svg class="nvm-ico"><use href="#nvm-i-chev"/></svg></a>
            @endif
        </div>
        @if($wallet && count($balances) > 0)
            <ul class="nvm-txs">
                @foreach($balances as $code => $balance)
                    <li class="nvm-tx">
                        @include('user.crypto-wallet.partials.coin', ['code' => $code])
                        <div class="nvm-tx__t">
                            <b>{{ $balance['currency']->name }}</b>
                            <small>{{ $code }}</small>
                        </div>
                        <div class="nvm-tx__r">
                            <span class="nvm-tx__amt nvm-num">{{ number_format((float) $balance['balance'], 8) }}</span>
                            <time class="nvm-num">≈ ฿{{ number_format((float) $balance['balance_thb'], 2) }}</time>
                        </div>
                    </li>
                @endforeach
            </ul>
        @else
            <div class="nvm-empty">
                <img src="{{ asset('images/nova/wallet/empty.webp') }}" alt="" width="86" height="86" loading="lazy">
                <b>ยังไม่มียอดคงเหลือ</b>
                @if($wallet)
                    ฝากเหรียญหรือซื้อเหรียญด้วยเงินบาท แล้วยอดจะแสดงที่นี่
                    @if($nvR('user.crypto-wallet.deposit'))
                        <div><a class="nvm-btn nvm-btn--gold nvm-btn--sm" href="{{ route('user.crypto-wallet.deposit') }}"><svg class="nvm-ico"><use href="#nvm-i-down"/></svg>ฝากเหรียญ</a></div>
                    @endif
                @elseif($hasWallets)
                    ตั้งกระเป๋าหลักก่อน แล้วจึงฝาก ถอน หรือแลกเปลี่ยนเหรียญได้
                @else
                    สร้างกระเป๋าคริปโตก่อน แล้วจึงฝาก ถอน หรือแลกเปลี่ยนเหรียญได้
                @endif
            </div>
        @endif
    </section>

    <aside class="nvm-card nvm-box" aria-labelledby="nvm-cw-me-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">My wallet</small><h2 id="nvm-cw-me-h">กระเป๋าของฉัน</h2></div></div>
        @if($wallet)
            <dl class="nvm-kv">
                <dt>ชื่อกระเป๋า</dt><dd>{{ $wallet->name ?: 'กระเป๋า #'.$wallet->id }}</dd>
                <dt>ประเภท</dt><dd>{{ $wallet->wallet_type === 'custodial' ? 'ระบบดูแลกุญแจ (Custodial)' : 'กระเป๋าภายนอก' }}</dd>
                <dt>สถานะ</dt>
                <dd>
                    @if($wallet->status === 'active')
                        <span class="nvm-chip nvm-chip--ok">ใช้งานได้</span>
                    @elseif($wallet->status === 'locked')
                        <span class="nvm-chip nvm-chip--bad">ถูกล็อก</span>
                    @else
                        <span class="nvm-chip nvm-chip--bad">ถูกระงับ</span>
                    @endif
                </dd>
            </dl>
            @if($nvR('user.crypto-wallet.wallets'))
                <a class="nvm-btn nvm-btn--line nvm-btn--block nvm-gap-t" href="{{ route('user.crypto-wallet.wallets') }}"><svg class="nvm-ico"><use href="#nvm-i-gear"/></svg>จัดการกระเป๋า</a>
            @endif
        @elseif($hasWallets)
            {{-- มีกระเป๋าแล้วแต่ยังไม่มีกระเป๋าหลัก (middleware crypto.wallet.exists จะส่งมาตั้งที่ /wallets) --}}
            <div class="nvm-empty">
                <img src="{{ asset('images/nova/cat/wallet.webp') }}" alt="" width="86" height="86" loading="lazy">
                <b>ยังไม่ได้ตั้งกระเป๋าหลัก</b>
                เลือกกระเป๋าหลักก่อน จึงจะฝาก ถอน และแลกเปลี่ยนเหรียญได้
                @if($nvR('user.crypto-wallet.wallets'))
                    <div><a class="nvm-btn nvm-btn--gold" href="{{ route('user.crypto-wallet.wallets') }}"><svg class="nvm-ico"><use href="#nvm-i-award"/></svg>ตั้งกระเป๋าหลัก</a></div>
                @endif
            </div>
        @else
            <div class="nvm-empty">
                <img src="{{ asset('images/nova/deco/coins.webp') }}" alt="" width="86" height="86" loading="lazy">
                <b>คุณยังไม่มีกระเป๋าคริปโต</b>
                ตั้ง PIN 6 หลัก ใช้เวลาไม่ถึงนาที
                <div><button type="button" class="nvm-btn nvm-btn--gold" @click="create = true"><svg class="nvm-ico"><use href="#nvm-i-plus"/></svg>สร้างกระเป๋าใหม่</button></div>
            </div>
        @endif
    </aside>
</div>

<section class="nvm-card nvm-box" aria-labelledby="nvm-cw-tx-h">
    <div class="nvm-sec-h">
        <div><small class="nvm-latin">Recent activity</small><h2 id="nvm-cw-tx-h">ธุรกรรมล่าสุด</h2></div>
        @if($wallet && $nvR('user.crypto-wallet.transactions'))
            <a href="{{ route('user.crypto-wallet.transactions') }}">ดูทั้งหมด<svg class="nvm-ico"><use href="#nvm-i-chev"/></svg></a>
        @endif
    </div>
    @if($recentTransactions->isNotEmpty())
        <ul class="nvm-txs">
            @foreach($recentTransactions as $tx)
                @include('user.crypto-wallet.partials.tx-row', ['tx' => $tx])
            @endforeach
        </ul>
    @else
        <div class="nvm-empty">
            <img src="{{ asset('images/nova/deco/scroll.webp') }}" alt="" width="86" height="86" loading="lazy">
            <b>ยังไม่มีธุรกรรม</b>
            รายการฝาก ถอน และแลกเปลี่ยนเหรียญจะแสดงที่นี่
        </div>
    @endif
</section>

@include('user.crypto-wallet.partials.create-wallet-modal')

</div>
@endsection
