{{--
    ถอนเหรียญคริปโต (ธีมโนวาเต็ม)
    CryptoWalletController::withdraw → $wallet · $currencies (เปิดถอน + เครือข่ายอยู่ใน WithdrawCryptoRequest::NETWORKS) · $balances (คีย์เป็นรหัสเหรียญ)
    $tpixWithdrawable — TPIX (tpix-chain) ถอนผ่านฟอร์มนี้ไม่ได้ → ลิงก์ไปหน้า user.tpix.withdrawal แทน
    POST user.crypto-wallet.withdraw.submit — WithdrawCryptoRequest ต้องการ:
      currency_code · network (bitcoin/ethereum/bsc/polygon/tron) · to_address · amount · pin (ตัวเลข 6 หลัก) · accept_terms
    เดิมฟอร์มส่ง currency_id ไม่มี network/accept_terms และให้ PIN 4-6 หลัก → ส่งคำขอไม่ผ่านการตรวจทุกครั้ง (แก้แล้ว)
    route มี turnstile:crypto_withdrawal และ two-factor:withdrawal
--}}
@extends('layouts.user-nova')

@section('title', 'ถอนเหรียญคริปโต')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/crypto-wallet.css') }}?v={{ filemtime(public_path('theme-nova/member/crypto-wallet.css')) }}">@endpush

@php
    // ข้อมูลเหรียญที่ Alpine ใช้คำนวณค่าธรรมเนียม/ยอดสุทธิ (ตัวเลขทั้งหมดมาจากตาราง crypto_currencies)
    $currencyData = $currencies->mapWithKeys(fn ($c) => [$c->code => [
        'code' => $c->code,
        'network' => $c->network,
        'min' => (float) $c->min_withdrawal,
        'max' => $c->max_withdrawal !== null ? (float) $c->max_withdrawal : null,
        'fee' => (float) $c->withdrawal_fee,
        'balance' => (float) ($balances[$c->code]['balance'] ?? 0),
    ]])->all();
@endphp

@section('content')

<div class="nvm-stack">

<section class="nvm-ph" aria-labelledby="nvm-cwwd-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.crypto-wallet.index') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>กระเป๋าคริปโต</a>
            <div class="nvm-eyebrow nvm-latin">Withdraw crypto</div>
            <h1 id="nvm-cwwd-h">ถอนเหรียญคริปโต</h1>
            <p class="nvm-ph__lead">โอนเหรียญไปยังกระเป๋าภายนอก ทีมงานตรวจคำขอก่อนส่งขึ้นบล็อกเชน</p>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/wallet/withdraw.webp') }}" alt="" width="200" height="200">
    </div>
</section>

@include('user.crypto-wallet.partials.flash', ['errorTitle' => 'ส่งคำขอถอนไม่สำเร็จ'])

<div class="nvm-split">
    <section class="nvm-card nvm-box" aria-labelledby="nvm-cwwd-f-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Request</small><h2 id="nvm-cwwd-f-h">แบบฟอร์มถอนเหรียญ</h2></div></div>

        @if($currencies->isEmpty())
            <div class="nvm-empty">
                <img src="{{ asset('images/nova/wallet/empty.webp') }}" alt="" width="86" height="86" loading="lazy">
                <b>ยังไม่มีสกุลเงินที่เปิดให้ถอน</b>
                ทีมงานจะเปิดให้ถอนเร็วๆ นี้
            </div>
        @else
            <form method="POST" action="{{ route('user.crypto-wallet.withdraw.submit') }}" class="nvm-form"
                  x-data="nvmCwWithdraw({ coins: {{ \Illuminate\Support\Js::from($currencyData) }}, code: @js((string) old('currency_code', '')), amount: @js((string) old('amount', '')) })"
                  @submit="busy = true" @pageshow.window="busy = false">
                @csrf
                <input type="hidden" name="network" :value="coin ? coin.network : ''">

                <div class="nvm-field">
                    <label class="nvm-label" for="cw-wd-coin">สกุลเงิน <em>*</em></label>
                    <select id="cw-wd-coin" name="currency_code" required class="nvm-input @error('currency_code') is-bad @enderror" x-model="code">
                        <option value="">เลือกสกุลเงิน</option>
                        @foreach($currencies as $curr)
                            <option value="{{ $curr->code }}">{{ $curr->code }} ({{ strtoupper($curr->network) }}) · มี {{ number_format((float) ($balances[$curr->code]['balance'] ?? 0), 8) }}</option>
                        @endforeach
                    </select>
                    @error('currency_code')<span class="nvm-err">{{ $message }}</span>@enderror
                    @error('network')<span class="nvm-err">{{ $message }}</span>@enderror
                </div>

                <div class="nvm-field">
                    <label class="nvm-label" for="cw-wd-to">ที่อยู่ปลายทาง <em>*</em></label>
                    <input id="cw-wd-to" type="text" name="to_address" required maxlength="255" autocomplete="off" spellcheck="false" class="nvm-input nvm-cw-mono @error('to_address') is-bad @enderror" value="{{ old('to_address') }}" placeholder="0x...">
                    <span class="nvm-help">ตรวจที่อยู่ให้ถูกต้อง ถอนไปที่อยู่ผิดกู้คืนไม่ได้</span>
                    @error('to_address')<span class="nvm-err">{{ $message }}</span>@enderror
                </div>

                <div class="nvm-field">
                    <label class="nvm-label" for="cw-wd-amt">จำนวนที่ถอน <em>*</em></label>
                    <input id="cw-wd-amt" type="number" name="amount" step="0.00000001" min="0" inputmode="decimal" required class="nvm-input nvm-num @error('amount') is-bad @enderror" placeholder="0.00000000" x-model="amount">
                    <div class="nvm-picks" x-show="coin && coin.balance > 0" x-cloak>
                        <button type="button" @click="amount = String(coin.balance)">ถอนทั้งหมด <span class="nvm-num" x-text="coin ? fmt(coin.balance) : ''"></span></button>
                    </div>
                    <span class="nvm-help" x-show="coin" x-cloak>ขั้นต่ำ <span class="nvm-num" x-text="coin ? fmt(coin.min) : ''"></span><template x-if="coin && coin.max"><span> · สูงสุด <span class="nvm-num" x-text="fmt(coin.max)"></span></span></template> <span x-text="coin ? coin.code : ''"></span></span>
                    <span class="nvm-err" x-show="coin && amt > coin.balance" x-cloak>ยอดเกินกว่าที่มีในกระเป๋า</span>
                    @error('amount')<span class="nvm-err">{{ $message }}</span>@enderror
                </div>

                <div class="nvm-mini" x-show="coin && amt > 0" x-cloak>
                    <dl class="nvm-kv">
                        <dt>จำนวนถอน</dt><dd class="nvm-num" x-text="coin ? fmt(amt) + ' ' + coin.code : ''"></dd>
                        <dt>ค่าธรรมเนียมเครือข่าย</dt><dd class="nvm-num" x-text="coin ? '-' + fmt(coin.fee) + ' ' + coin.code : ''"></dd>
                        <dt class="nvm-kv__total">จำนวนที่จะได้รับ</dt><dd class="nvm-num nvm-kv__total" x-text="coin ? fmt(net) + ' ' + coin.code : ''"></dd>
                    </dl>
                </div>

                <div class="nvm-field">
                    <label class="nvm-label" for="cw-wd-pin">PIN กระเป๋าคริปโต (6 หลัก) <em>*</em></label>
                    <input id="cw-wd-pin" type="password" name="pin" required minlength="6" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" autocomplete="off" class="nvm-input nvm-pin @error('pin') is-bad @enderror" placeholder="••••••">
                    @error('pin')<span class="nvm-err">{{ $message }}</span>@enderror
                </div>

                <div class="nvm-field">
                    <label class="nvm-label" for="cw-wd-note">หมายเหตุ (ถ้ามี)</label>
                    <textarea id="cw-wd-note" name="note" rows="2" maxlength="500" class="nvm-input" placeholder="หมายเหตุส่วนตัว">{{ old('note') }}</textarea>
                </div>

                <label class="nvm-check">
                    <input type="checkbox" name="accept_terms" value="1" required @checked(old('accept_terms'))>
                    <span>ฉันตรวจที่อยู่ปลายทางและเครือข่ายแล้ว และเข้าใจว่าส่งไปที่อยู่ผิดจะกู้คืนไม่ได้</span>
                </label>
                @error('accept_terms')<span class="nvm-err">{{ $message }}</span>@enderror

                <div class="nvm-turnstile"><x-turnstile point="crypto_withdrawal" /></div>

                <div class="nvm-filter__btns">
                    <a class="nvm-btn nvm-btn--line" href="{{ route('user.crypto-wallet.index') }}">ยกเลิก</a>
                    <button type="submit" class="nvm-btn nvm-btn--gold" :disabled="busy || (coin && amt > coin.balance)"><svg class="nvm-ico"><use href="#nvm-i-send"/></svg><span x-text="busy ? 'กำลังส่งคำขอ…' : 'ส่งคำขอถอน'">ส่งคำขอถอน</span></button>
                </div>
            </form>
        @endif
    </section>

    <div class="nvm-stack">
        <aside class="nvm-card nvm-box" aria-labelledby="nvm-cwwd-bal-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Balances</small><h2 id="nvm-cwwd-bal-h">ยอดคงเหลือ</h2></div></div>
            @if(count($balances) > 0)
                <ul class="nvm-txs">
                    @foreach($balances as $code => $balance)
                        <li class="nvm-tx">
                            @include('user.crypto-wallet.partials.coin', ['code' => $code])
                            <div class="nvm-tx__t"><b>{{ $balance['currency']->name }}</b><small>{{ $code }}</small></div>
                            <div class="nvm-tx__r">
                                <span class="nvm-tx__amt nvm-num">{{ number_format((float) $balance['balance'], 8) }}</span>
                                <time class="nvm-num">≈ ฿{{ number_format((float) $balance['balance_thb'], 2) }}</time>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="nvm-empty">
                    <b>ไม่มียอดคงเหลือ</b>
                    ฝากหรือซื้อเหรียญก่อนจึงจะถอนได้
                </div>
            @endif
        </aside>
        <aside class="nvm-card nvm-alert nvm-alert--bad" aria-labelledby="nvm-cwwd-warn-h">
            <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg></span>
            <div class="nvm-alert__txt">
                <b id="nvm-cwwd-warn-h">คำเตือนสำคัญ</b>
                <ul class="nvm-cw-list">
                    <li>ตรวจที่อยู่ปลายทางและเครือข่ายให้ถูกต้องก่อนยืนยัน</li>
                    <li>ถอนไปที่อยู่หรือเครือข่ายผิด เหรียญจะสูญหายถาวร</li>
                    <li>คำขอจำนวนมากอาจใช้เวลาตรวจสอบเพิ่มเติม</li>
                    <li>ค่าธรรมเนียมเครือข่ายขึ้นอยู่กับความหนาแน่นของบล็อกเชน</li>
                </ul>
            </div>
        </aside>
        @if(($tpixWithdrawable ?? false) && \Illuminate\Support\Facades\Route::has('user.tpix.withdrawal'))
            <a class="nvm-card nvm-alert nvm-alert--info" href="{{ route('user.tpix.withdrawal') }}">
                <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-send"/></svg></span>
                <div class="nvm-alert__txt"><b>ถอนเหรียญ TPIX</b><p>TPIX อยู่บนเครือข่าย TPIX Chain ถอนผ่านหน้ากระเป๋า TPIX</p></div>
                <div class="nvm-alert__side"><small>ไปหน้าถอน TPIX →</small></div>
            </a>
        @endif
        @if(\Illuminate\Support\Facades\Route::has('user.crypto-wallet.withdrawals'))
            <a class="nvm-card nvm-alert nvm-alert--info" href="{{ route('user.crypto-wallet.withdrawals') }}">
                <img class="nvm-alert__img" src="{{ asset('images/nova/wallet/hourglass.webp') }}" alt="" width="58" height="58" loading="lazy">
                <div class="nvm-alert__txt"><b>คำขอถอนของฉัน</b><p>ดูสถานะ หรือยกเลิกคำขอที่ยังรอตรวจ</p></div>
                <div class="nvm-alert__side"><small>ดูทั้งหมด →</small></div>
            </a>
        @endif
    </div>
</div>

</div>
@endsection

@push('scripts')
<script>
    // ฟอร์มถอนเหรียญ: ค่าธรรมเนียม/ยอดสุทธิคำนวณสดจากข้อมูลเหรียญ (สูตรเดียวกับ submitWithdrawal: สุทธิ = จำนวน - withdrawal_fee) + กันกดส่งซ้ำ
    document.addEventListener('alpine:init', function () {
        window.Alpine.data('nvmCwWithdraw', function (o) {
            return {
                coins: o.coins,
                code: o.code,
                amount: o.amount,
                busy: false,
                get coin() { return this.coins[this.code] || null; },
                get amt() { var a = parseFloat(this.amount); return isNaN(a) || a <= 0 ? 0 : a; },
                get net() { return this.coin ? Math.max(0, this.amt - this.coin.fee) : 0; },
                fmt(n) { return Number(n || 0).toLocaleString('th-TH', { minimumFractionDigits: 0, maximumFractionDigits: 8 }); },
            };
        });
    });
</script>
@endpush
