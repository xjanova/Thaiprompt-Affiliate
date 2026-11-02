{{--
    แลกเปลี่ยน THB ↔ Crypto (ธีมโนวาเต็ม)
    CryptoExchangeController::index → $thbWallet (กระเป๋าเงินบาท อาจเป็น null) · $cryptoWallet · $currencies (เปิดแลกเปลี่ยน)
    $cryptoBalances (คีย์เป็นรหัสเหรียญ) · $prices[code] = [buy_rate, sell_rate, change_24h]
    - ซื้อ: POST exchange.buy → BuyCryptoRequest: currency_code · thb_amount (100-1,000,000) · pin กระเป๋าเงินบาท 6 หลัก
    - ขาย: POST exchange.sell → SellCryptoRequest: currency_code · crypto_amount · pin กระเป๋าคริปโต 6 หลัก
    เดิม: อ่าน $prices[..]['buy_price'] ที่ไม่มีอยู่ (คีย์จริง buy_rate) → 500 ทันทีที่มีราคาในระบบ
    และฟอร์มส่ง currency_id แทน currency_code → ซื้อ/ขายไม่ผ่านการตรวจทุกครั้ง (แก้แล้วทั้งคู่)
--}}
@extends('layouts.user-nova')

@section('title', 'แลกเปลี่ยน THB ↔ Crypto')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/crypto-wallet.css') }}?v={{ filemtime(public_path('theme-nova/member/crypto-wallet.css')) }}">@endpush

@php
    $nvR = fn ($name) => \Illuminate\Support\Facades\Route::has($name);
    $sellable = $currencies->filter(fn ($c) => ($cryptoBalances[$c->code]['balance'] ?? 0) > 0);
    $tab = old('crypto_amount') !== null ? 'sell' : 'buy';
@endphp

@section('content')

<div class="nvm-stack">

<section class="nvm-ph" aria-labelledby="nvm-cwx-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.crypto-wallet.index') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>กระเป๋าคริปโต</a>
            <div class="nvm-eyebrow nvm-latin">Exchange</div>
            <h1 id="nvm-cwx-h">แลกเปลี่ยนสกุลเงิน</h1>
            <p class="nvm-ph__lead">ซื้อหรือขายเหรียญคริปโตด้วยเงินบาทในกระเป๋าเงินของคุณ</p>
            <div class="nvm-pills">
                <span class="nvm-pill nvm-pill--glass"><svg class="nvm-ico"><use href="#nvm-i-wallet"/></svg>กระเป๋าบาท ฿{{ number_format((float) ($thbWallet->balance ?? 0), 2) }}</span>
                <span class="nvm-pill nvm-pill--glass"><svg class="nvm-ico"><use href="#nvm-i-cash"/></svg>คริปโต {{ number_format(count($cryptoBalances)) }} สกุล</span>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/wallet/transfer.webp') }}" alt="" width="200" height="200">
    </div>
</section>

@include('user.crypto-wallet.partials.flash', ['errorTitle' => 'แลกเปลี่ยนไม่สำเร็จ'])

<div class="nvm-split">
    <section class="nvm-card nvm-box" x-data="{ tab: @js($tab) }" aria-labelledby="nvm-cwx-f-h">
        <div class="nvm-sec-h">
            <div><small class="nvm-latin">Trade</small><h2 id="nvm-cwx-f-h">ซื้อ / ขายเหรียญ</h2></div>
            <div class="nvm-tabs" role="tablist" aria-label="เลือกซื้อหรือขาย">
                <button type="button" role="tab" :class="{ 'is-on': tab === 'buy' }" :aria-selected="tab === 'buy'" @click="tab = 'buy'"><svg class="nvm-ico"><use href="#nvm-i-cart"/></svg>ซื้อ</button>
                <button type="button" role="tab" :class="{ 'is-on': tab === 'sell' }" :aria-selected="tab === 'sell'" @click="tab = 'sell'"><svg class="nvm-ico"><use href="#nvm-i-cash"/></svg>ขาย</button>
            </div>
        </div>

        {{-- ซื้อเหรียญด้วยเงินบาท --}}
        <form x-show="tab === 'buy'" method="POST" action="{{ route('user.crypto-wallet.exchange.buy') }}" class="nvm-form" x-data="{ busy: false }" @submit="busy = true" @pageshow.window="busy = false">
            @csrf
            <div class="nvm-field">
                <label class="nvm-label" for="cw-buy-coin">เหรียญที่ต้องการซื้อ <em>*</em></label>
                <select id="cw-buy-coin" name="currency_code" required class="nvm-input">
                    <option value="">เลือกสกุลเงิน</option>
                    @foreach($currencies as $curr)
                        <option value="{{ $curr->code }}" @selected(old('currency_code') === $curr->code && $tab === 'buy')>{{ $curr->code }} ({{ $curr->name }})@if(isset($prices[$curr->code])) · ซื้อ ฿{{ number_format((float) $prices[$curr->code]['buy_rate'], 2) }}@endif</option>
                    @endforeach
                </select>
            </div>
            <div class="nvm-field">
                <label class="nvm-label" for="cw-buy-thb">จำนวนเงินบาทที่ใช้ <em>*</em></label>
                <div class="nvm-amount"><span>฿</span><input id="cw-buy-thb" type="number" name="thb_amount" step="0.01" min="100" max="1000000" inputmode="decimal" required class="nvm-input" placeholder="0.00" value="{{ old('thb_amount') }}"></div>
                <span class="nvm-help">ขั้นต่ำ ฿100 · มีอยู่ ฿{{ number_format((float) ($thbWallet->balance ?? 0), 2) }}</span>
            </div>
            <div class="nvm-field">
                <label class="nvm-label" for="cw-buy-pin">PIN กระเป๋าเงินบาท (6 หลัก) <em>*</em></label>
                <input id="cw-buy-pin" type="password" name="pin" required minlength="6" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" autocomplete="off" class="nvm-input nvm-pin" placeholder="••••••">
            </div>
            <label class="nvm-check">
                <input type="checkbox" name="accept_terms" value="1" required>
                <span>ฉันยอมรับว่าราคาอาจเปลี่ยนแปลงได้ และค่าธรรมเนียมการแลกเปลี่ยนจะถูกหักจากยอด</span>
            </label>
            <button type="submit" class="nvm-btn nvm-btn--gold nvm-cw-submit" :disabled="busy"><svg class="nvm-ico"><use href="#nvm-i-cart"/></svg><span x-text="busy ? 'กำลังซื้อ…' : 'ซื้อเหรียญด้วยเงินบาท'">ซื้อเหรียญด้วยเงินบาท</span></button>
        </form>

        {{-- ขายเหรียญรับเงินบาท --}}
        <form x-show="tab === 'sell'" x-cloak method="POST" action="{{ route('user.crypto-wallet.exchange.sell') }}" class="nvm-form" x-data="{ busy: false }" @submit="busy = true" @pageshow.window="busy = false">
            @csrf
            @if($sellable->isEmpty())
                <div class="nvm-empty">
                    <b>ยังไม่มีเหรียญให้ขาย</b>
                    ซื้อหรือฝากเหรียญเข้ากระเป๋าก่อน
                </div>
            @else
                <div class="nvm-field">
                    <label class="nvm-label" for="cw-sell-coin">เหรียญที่ต้องการขาย <em>*</em></label>
                    <select id="cw-sell-coin" name="currency_code" required class="nvm-input">
                        <option value="">เลือกสกุลเงิน</option>
                        @foreach($sellable as $curr)
                            <option value="{{ $curr->code }}" @selected(old('currency_code') === $curr->code && $tab === 'sell')>{{ $curr->code }} · มี {{ number_format((float) $cryptoBalances[$curr->code]['balance'], 8) }}@if(isset($prices[$curr->code])) · ขาย ฿{{ number_format((float) $prices[$curr->code]['sell_rate'], 2) }}@endif</option>
                        @endforeach
                    </select>
                </div>
                <div class="nvm-field">
                    <label class="nvm-label" for="cw-sell-amt">จำนวนเหรียญที่ขาย <em>*</em></label>
                    <input id="cw-sell-amt" type="number" name="crypto_amount" step="0.00000001" min="0.00000001" inputmode="decimal" required class="nvm-input nvm-num" placeholder="0.00000000" value="{{ old('crypto_amount') }}">
                </div>
                <div class="nvm-field">
                    <label class="nvm-label" for="cw-sell-pin">PIN กระเป๋าคริปโต (6 หลัก) <em>*</em></label>
                    <input id="cw-sell-pin" type="password" name="pin" required minlength="6" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" autocomplete="off" class="nvm-input nvm-pin" placeholder="••••••">
                </div>
                <label class="nvm-check">
                    <input type="checkbox" name="accept_terms" value="1" required>
                    <span>ฉันยอมรับว่าราคาอาจเปลี่ยนแปลงได้ และค่าธรรมเนียมจะถูกหักจากเงินบาทที่ได้รับ</span>
                </label>
                <button type="submit" class="nvm-btn nvm-btn--gold nvm-cw-submit" :disabled="busy"><svg class="nvm-ico"><use href="#nvm-i-cash"/></svg><span x-text="busy ? 'กำลังขาย…' : 'ขายเหรียญรับเงินบาท'">ขายเหรียญรับเงินบาท</span></button>
            @endif
        </form>
    </section>

    <div class="nvm-stack">
        <section class="nvm-card nvm-box" aria-labelledby="nvm-cwx-p-h">
            <div class="nvm-sec-h">
                <div><small class="nvm-latin">Prices</small><h2 id="nvm-cwx-p-h">ราคาปัจจุบัน</h2></div>
                @if($nvR('user.crypto-wallet.exchange.history'))
                    <a href="{{ route('user.crypto-wallet.exchange.history') }}">ประวัติ<svg class="nvm-ico"><use href="#nvm-i-chev"/></svg></a>
                @endif
            </div>
            @php $priced = $currencies->filter(fn ($c) => isset($prices[$c->code])); @endphp
            @if($priced->isNotEmpty())
                <ul class="nvm-txs">
                    @foreach($priced as $curr)
                        @php $p = $prices[$curr->code]; $chg = $p['change_24h']; @endphp
                        <li class="nvm-tx">
                            @include('user.crypto-wallet.partials.coin', ['code' => $curr->code])
                            <div class="nvm-tx__t">
                                <b>{{ $curr->code }}</b>
                                <small class="nvm-num">ขาย ฿{{ number_format((float) $p['sell_rate'], 2) }}</small>
                            </div>
                            <div class="nvm-tx__r">
                                <span class="nvm-tx__amt nvm-num">฿{{ number_format((float) $p['buy_rate'], 2) }}</span>
                                @if($chg !== null)
                                    <span class="nvm-delta {{ $chg >= 0 ? 'nvm-delta--up' : 'nvm-delta--down' }} nvm-num">{{ $chg >= 0 ? '+' : '' }}{{ number_format((float) $chg, 2) }}%</span>
                                @else
                                    <span class="nvm-delta nvm-delta--mute">-</span>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
                <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>ตัวเลขใหญ่คือราคาซื้อ ราคาอ้างอิงตลาดโลก อัปเดตทุก 5 นาที</p>
            @else
                <div class="nvm-empty">
                    <b>ยังไม่มีข้อมูลราคา</b>
                    ระบบดึงราคาไม่สำเร็จ ลองใหม่อีกครั้งภายหลัง
                </div>
            @endif
        </section>
        <aside class="nvm-card nvm-box" aria-labelledby="nvm-cwx-i-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Good to know</small><h2 id="nvm-cwx-i-h">ข้อมูลการแลกเปลี่ยน</h2></div></div>
            <ul class="nvm-cw-list">
                <li>ค่าธรรมเนียมการแลกเปลี่ยนแตกต่างกันตามสกุลเงิน</li>
                <li>แลกเปลี่ยนเสร็จทันที ไม่ต้องรอยืนยันจากบล็อกเชน</li>
                <li>ยอดเงินเข้ากระเป๋าที่เกี่ยวข้องอัตโนมัติ</li>
            </ul>
            @if($nvR('user.wallet.index'))
                <a class="nvm-btn nvm-btn--line nvm-btn--block nvm-gap-t" href="{{ route('user.wallet.index') }}"><svg class="nvm-ico"><use href="#nvm-i-wallet"/></svg>ไปที่กระเป๋าเงินบาท</a>
            @endif
        </aside>
    </div>
</div>

</div>
@endsection
