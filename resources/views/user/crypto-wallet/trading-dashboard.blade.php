{{--
    ราคาตลาดคริปโต (ธีมโนวาเต็ม) — route user.crypto-wallet.trading
    CryptoWalletController::tradingDashboard → $prices (code => price_thb/buy_price/sell_price/change_24h/volume_24h) · $balances · $currencies
    เดิมเป็น "Trading dashboard" ที่ order book / รายการเทรด / กราฟ สุ่มขึ้นมาทั้งหมดและปุ่มซื้อขายกดแล้วไม่มีผล
    ระบบมีแค่การซื้อ-ขายด้วยบาทผ่านหน้าแลกเปลี่ยน จึงเหลือเฉพาะราคาจริงกับยอดของสมาชิก แล้วพาไปหน้าแลกเปลี่ยน
--}}
@extends('layouts.user-nova')

@section('title', 'ราคาตลาดคริปโต')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/crypto-wallet.css') }}?v={{ filemtime(public_path('theme-nova/member/crypto-wallet.css')) }}">@endpush

@php
    $priced = $currencies->filter(fn ($c) => isset($prices[$c->code]));
@endphp

@section('content')

<div class="nvm-stack">

<section class="nvm-ph" aria-labelledby="nvm-cwmk-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.crypto-wallet.index') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>กระเป๋าคริปโต</a>
            <div class="nvm-eyebrow nvm-latin">Markets</div>
            <h1 id="nvm-cwmk-h">ราคาตลาด</h1>
            <p class="nvm-ph__lead">ราคาซื้อ-ขายเหรียญเป็นเงินบาท พร้อมยอดที่คุณถืออยู่</p>
            <div class="nvm-pills">
                <a class="nvm-btn nvm-btn--gold nvm-btn--sm" href="{{ route('user.crypto-wallet.exchange') }}"><svg class="nvm-ico"><use href="#nvm-i-swap"/></svg>ซื้อ / ขายเหรียญ</a>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/deco/crystal.webp') }}" alt="" width="200" height="200">
    </div>
</section>

@include('user.crypto-wallet.partials.flash')

<section class="nvm-card nvm-box" aria-labelledby="nvm-cwmk-p-h">
    <div class="nvm-sec-h"><div><small class="nvm-latin">Prices</small><h2 id="nvm-cwmk-p-h">ราคาเหรียญ</h2></div></div>
    @if($priced->isNotEmpty())
        <div class="nvm-table-wrap">
            <table class="nvm-table nvm-cw-table">
                <thead>
                    <tr>
                        <th scope="col">เหรียญ</th>
                        <th scope="col" class="r">ราคากลาง</th>
                        <th scope="col" class="r">ราคาซื้อ</th>
                        <th scope="col" class="r">ราคาขาย</th>
                        <th scope="col" class="r">24 ชม.</th>
                        <th scope="col" class="r">คุณถืออยู่</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($priced as $curr)
                        @php
                            $p = $prices[$curr->code];
                            $chg = $p['change_24h'];
                            $held = $balances[$curr->code] ?? null;
                        @endphp
                        <tr>
                            <td><div class="nvm-cw-cell">@include('user.crypto-wallet.partials.coin', ['code' => $curr->code])<div><b>{{ $curr->code }}</b><small>{{ $curr->name }}</small></div></div></td>
                            <td class="r nvm-num">฿{{ number_format((float) $p['price_thb'], 2) }}</td>
                            <td class="r nvm-num">{{ $p['buy_price'] !== null ? '฿'.number_format((float) $p['buy_price'], 2) : '-' }}</td>
                            <td class="r nvm-num">{{ $p['sell_price'] !== null ? '฿'.number_format((float) $p['sell_price'], 2) : '-' }}</td>
                            <td class="r">
                                @if($chg !== null)
                                    <span class="nvm-delta {{ $chg >= 0 ? 'nvm-delta--up' : 'nvm-delta--down' }} nvm-num">{{ $chg >= 0 ? '+' : '' }}{{ number_format((float) $chg, 2) }}%</span>
                                @else
                                    <span class="nvm-delta nvm-delta--mute">-</span>
                                @endif
                                @if($p['volume_24h'])
                                    <div class="nvm-num nvm-cw-muted">วอลุ่ม ฿{{ number_format((float) $p['volume_24h'], 0) }}</div>
                                @endif
                            </td>
                            <td class="r">
                                @if($held && (float) $held['balance'] > 0)
                                    <b class="nvm-num">{{ number_format((float) $held['balance'], 8) }}</b>
                                    <div class="nvm-num nvm-cw-muted">≈ ฿{{ number_format((float) $held['balance_thb'], 2) }}</div>
                                @else
                                    <span class="nvm-cw-muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>ราคาอ้างอิงตลาดโลก อัปเดตทุก 5 นาที ราคาจริงตอนทำรายการยืนยันที่หน้าแลกเปลี่ยน</p>
    @else
        <div class="nvm-empty">
            <img src="{{ asset('images/nova/deco/crystal.webp') }}" alt="" width="86" height="86" loading="lazy">
            <b>ยังไม่มีข้อมูลราคา</b>
            ระบบกำลังดึงราคาล่าสุด ลองใหม่อีกครั้งในไม่กี่นาที
        </div>
    @endif
</section>

<div class="nvm-filter__btns">
    <a class="nvm-btn nvm-btn--line" href="{{ route('user.crypto-wallet.portfolio') }}"><svg class="nvm-ico"><use href="#nvm-i-card"/></svg>พอร์ตของฉัน</a>
    <a class="nvm-btn nvm-btn--gold" href="{{ route('user.crypto-wallet.exchange') }}"><svg class="nvm-ico"><use href="#nvm-i-swap"/></svg>ไปหน้าแลกเปลี่ยน</a>
</div>

</div>
@endsection
