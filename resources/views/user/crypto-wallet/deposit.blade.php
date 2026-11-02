{{--
    ฝากเหรียญคริปโต — เลือกสกุลเงิน (ธีมโนวาเต็ม)
    CryptoWalletController::deposit → $wallet · $currencies (เปิดรับฝากอยู่ เรียงตาม sort_order)
--}}
@extends('layouts.user-nova')

@section('title', 'ฝากเหรียญคริปโต')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/crypto-wallet.css') }}?v={{ filemtime(public_path('theme-nova/member/crypto-wallet.css')) }}">@endpush

@section('content')

<div class="nvm-stack">

<section class="nvm-ph" aria-labelledby="nvm-cwd-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.crypto-wallet.index') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>กระเป๋าคริปโต</a>
            <div class="nvm-eyebrow nvm-latin">Deposit crypto</div>
            <h1 id="nvm-cwd-h">ฝากเหรียญคริปโต</h1>
            <p class="nvm-ph__lead">เลือกสกุลเงินที่ต้องการฝาก แล้วโอนจากกระเป๋าภายนอกเข้ามาที่อยู่ของคุณ</p>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/wallet/deposit.webp') }}" alt="" width="200" height="200">
    </div>
</section>

@include('user.crypto-wallet.partials.flash')

<div class="nvm-split">
    <section class="nvm-card nvm-box" aria-labelledby="nvm-cwd-pick-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Choose a coin</small><h2 id="nvm-cwd-pick-h">เลือกสกุลเงิน</h2></div></div>
        @if($currencies->isNotEmpty())
            <div class="nvm-cw-grid">
                @foreach($currencies as $currency)
                    <a class="nvm-card nvm-cw-cur {{ $currency->deposit_enabled ? '' : 'is-off' }}" href="{{ route('user.crypto-wallet.deposit.currency', $currency->code) }}">
                        <span class="nvm-cw-cur__head">
                            @include('user.crypto-wallet.partials.coin', ['code' => $currency->code])
                            <span><b>{{ $currency->code }}</b><small>{{ $currency->name }}</small></span>
                        </span>
                        <dl class="nvm-kv">
                            <dt>เครือข่าย</dt><dd>{{ strtoupper($currency->network) }}</dd>
                            <dt>ฝากขั้นต่ำ</dt><dd class="nvm-num">{{ number_format((float) $currency->min_deposit, 8) }}</dd>
                            <dt>การยืนยัน</dt><dd class="nvm-num">{{ number_format((int) $currency->confirmations_required) }} บล็อก</dd>
                        </dl>
                        <span class="nvm-cw-cur__foot">
                            @if($currency->deposit_enabled)
                                <span class="nvm-chip nvm-chip--ok">พร้อมใช้งาน</span>
                            @else
                                <span class="nvm-chip nvm-chip--bad">ปิดชั่วคราว</span>
                            @endif
                            <span>เลือก<svg class="nvm-ico"><use href="#nvm-i-chev"/></svg></span>
                        </span>
                    </a>
                @endforeach
            </div>
        @else
            <div class="nvm-empty">
                <img src="{{ asset('images/nova/wallet/empty.webp') }}" alt="" width="86" height="86" loading="lazy">
                <b>ยังไม่มีสกุลเงินที่เปิดรับฝาก</b>
                ทีมงานจะเปิดให้ฝากเร็วๆ นี้
            </div>
        @endif
    </section>

    <div class="nvm-stack">
        <aside class="nvm-card nvm-box" aria-labelledby="nvm-cwd-how-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">How it works</small><h2 id="nvm-cwd-how-h">วิธีการฝากเหรียญ</h2></div></div>
            <ol class="nvm-steps">
                <li>เลือกสกุลเงินที่ต้องการฝาก</li>
                <li>คัดลอกที่อยู่กระเป๋าหรือสแกน QR Code</li>
                <li>โอนเหรียญจากกระเป๋าภายนอก (Exchange, MetaMask ฯลฯ)</li>
                <li>รอการยืนยันจากบล็อกเชน ใช้เวลาตามแต่ละเครือข่าย</li>
                <li>เหรียญเข้ากระเป๋าอัตโนมัติเมื่อยืนยันครบ</li>
            </ol>
        </aside>
        <aside class="nvm-card nvm-alert nvm-alert--bad" aria-labelledby="nvm-cwd-warn-h">
            <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg></span>
            <div class="nvm-alert__txt">
                <b id="nvm-cwd-warn-h">ข้อควรระวัง</b>
                <ul class="nvm-cw-list">
                    <li>ตรวจเครือข่าย (Network) ให้ถูกต้องก่อนโอน มิฉะนั้นเหรียญอาจสูญหาย</li>
                    <li>ส่งเฉพาะสกุลเงินที่ตรงกัน เช่น ที่อยู่ BTC รับได้เฉพาะ BTC</li>
                    <li>อย่าส่งเหรียญจาก Smart Contract โดยตรง</li>
                </ul>
            </div>
        </aside>
    </div>
</div>

</div>
@endsection
