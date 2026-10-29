@extends('layouts.user-nova')

@section('title', 'โอนเงิน')

@php
    // โอนเงินให้สมาชิก — ธีมโนวาเต็ม
    // ค่าธรรมเนียม/ยอดขั้นต่ำมาจาก WalletSetting (สูตรเดียวกับที่หักจริงใน WalletService::transferWithFee)
    $nvR = fn ($name) => \Illuminate\Support\Facades\Route::has($name);
    [$balInt, $balDec] = explode('.', number_format((float) $wallet->balance, 2));
    $minAmount = max(0.01, (float) $feePolicy['min_amount']);
    $hasPin = $wallet->hasPIN();
    // เปิดจากลิงก์ ?to=TPW... (เช่นจากหน้า QR) → ใส่ที่อยู่ผู้รับให้เลย
    $toPrefill = trim((string) old('wallet_address', (string) request('to', '')));
    $amountPrefill = (string) old('amount', request('amount', ''));
    $feeText = $feePolicy['fee_amount'] <= 0 && $feePolicy['fee_min'] <= 0
        ? 'ไม่มีค่าธรรมเนียม'
        : ($feePolicy['fee_type'] === 'percentage'
            ? rtrim(rtrim(number_format($feePolicy['fee_amount'], 2), '0'), '.').'% ต่อรายการ'
            : '฿'.number_format($feePolicy['fee_amount'], 2).' ต่อรายการ');
@endphp

@section('content')

<section class="nvm-ph" aria-labelledby="nvm-tf-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            @if($nvR('user.wallet.index'))
                <a class="nvm-ph__back" href="{{ route('user.wallet.index') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>กระเป๋าเงิน</a>
            @endif
            <div class="nvm-eyebrow nvm-latin">Transfer</div>
            <h1 id="nvm-tf-h">โอนเงินให้สมาชิก</h1>
            <p class="nvm-ph__lead">โอนถึงกระเป๋าเงินสมาชิกคนอื่นทันที ด้วย Wallet Address · ค่าธรรมเนียม {{ $feeText }}</p>
            <div class="nvm-bal">
                <small>ยอดที่โอนได้</small>
                <b class="nvm-foil nvm-num">฿{{ $balInt }}<span>.{{ $balDec }}</span></b>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/wallet/transfer.webp') }}" alt="" width="200" height="200">
    </div>
</section>

@if($errors->any())
    <section class="nvm-card nvm-alert nvm-alert--bad" role="alert">
        <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg></span>
        <div class="nvm-alert__txt">
            <b>ตรวจสอบข้อมูลอีกครั้ง</b>
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    </section>
@endif

<div class="nvm-split">
    <section class="nvm-card nvm-box" aria-labelledby="nvm-tff-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Send</small><h2 id="nvm-tff-h">แบบฟอร์มโอนเงิน</h2></div></div>

        <form method="POST" action="{{ route('user.wallet.transfer.submit') }}" class="nvm-form"
              x-data="nvmTransfer({ policy: {{ \Illuminate\Support\Js::from($feePolicy) }}, balance: {{ (float) $wallet->balance }}, min: {{ $minAmount }}, to: @js($toPrefill), amount: @js($amountPrefill), lookupUrl: @js($nvR('user.wallet.qr-transfer.lookup') ? route('user.wallet.qr-transfer.lookup') : null) })"
              @submit="if (! ok) { $event.preventDefault(); return; } busy = true" @pageshow.window="busy = false">
            @csrf
            <input type="hidden" name="transfer_token" value="{{ $transferToken }}">

            <div class="nvm-field">
                <label class="nvm-label" for="tf-to">Wallet Address ผู้รับ <em>*</em></label>
                <input id="tf-to" type="text" name="wallet_address" required maxlength="40" autocomplete="off" autocapitalize="off" spellcheck="false"
                       class="nvm-input nvm-num @error('wallet_address') is-bad @enderror" placeholder="TPW…" x-model="to" @input.debounce.500ms="lookup()" @blur="lookup()">
                {{-- ผลค้นหาผู้รับ — ให้เห็นชื่อก่อนกดโอน กันโอนผิดคน --}}
                <div class="nvm-who" x-show="who" x-cloak>
                    <span class="nvm-avatar" x-text="who ? who.replace(/^[เแโใไ]/, '').charAt(0) : ''"></span>
                    <span><small>ผู้รับ</small><b x-text="who"></b></span>
                    <svg class="nvm-ico"><use href="#nvm-i-check"/></svg>
                </div>
                <span class="nvm-err" x-show="whoErr" x-cloak x-text="whoErr"></span>
                <span class="nvm-help" x-show="! who && ! whoErr">ขึ้นต้นด้วย TPW ตามด้วยตัวอักษร 16 ตัว · ขอจากผู้รับ หรือสแกน QR แทนได้</span>
            </div>

            <div class="nvm-field">
                <label class="nvm-label" for="tf-amount">จำนวนเงิน (บาท) <em>*</em></label>
                <div class="nvm-amount"><span>฿</span><input id="tf-amount" type="number" name="amount" step="0.01" min="{{ $minAmount }}" inputmode="decimal" required class="nvm-input @error('amount') is-bad @enderror" placeholder="0.00" x-model="amount"></div>
                <div class="nvm-picks">
                    @foreach([100, 300, 500, 1000] as $preset)
                        <button type="button" @click="amount = '{{ $preset }}'">฿{{ number_format($preset) }}</button>
                    @endforeach
                </div>
                <span class="nvm-help">ขั้นต่ำ ฿{{ number_format($minAmount, 2) }} · ค่าธรรมเนียม {{ $feeText }}</span>
                <span class="nvm-err" x-show="amt > 0 && amt < min" x-cloak>ยอดโอนขั้นต่ำ ฿{{ number_format($minAmount, 2) }}</span>
                <span class="nvm-err" x-show="total > balance" x-cloak>ยอดเงินไม่พอ (ต้องใช้ ฿<span x-text="fmt(total)"></span> รวมค่าธรรมเนียม)</span>
            </div>

            <div class="nvm-mini" x-show="amt > 0" x-cloak>
                <dl class="nvm-kv">
                    <dt>ผู้รับได้</dt><dd class="nvm-num" x-text="'฿' + fmt(amt)"></dd>
                    <dt>ค่าธรรมเนียม</dt><dd class="nvm-num" x-text="fee > 0 ? '฿' + fmt(fee) : 'ฟรี'"></dd>
                    <dt>ยอดคงเหลือหลังโอน</dt><dd class="nvm-num" x-text="'฿' + fmt(Math.max(0, balance - total))"></dd>
                    <dt class="nvm-kv__total">หักจากกระเป๋า</dt><dd class="nvm-num nvm-kv__total" x-text="'฿' + fmt(total)"></dd>
                </dl>
            </div>

            @if($hasPin)
                <div class="nvm-field">
                    <label class="nvm-label" for="tf-pin">PIN กระเป๋าเงิน (6 หลัก) <em>*</em></label>
                    <input id="tf-pin" type="password" name="pin" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" autocomplete="off" required class="nvm-input @error('pin') is-bad @enderror" placeholder="••••••">
                </div>
            @endif

            <div class="nvm-field">
                <label class="nvm-label" for="tf-note">ข้อความถึงผู้รับ (ถ้ามี)</label>
                <input id="tf-note" type="text" name="description" maxlength="255" class="nvm-input" value="{{ old('description') }}" placeholder="เช่น ค่าสินค้า, คืนเงิน">
            </div>

            <div class="nvm-filter__btns">
                <a href="{{ route('user.wallet.index') }}" class="nvm-btn nvm-btn--line">ยกเลิก</a>
                <button type="submit" class="nvm-btn nvm-btn--gold" :disabled="busy || ! ok"><svg class="nvm-ico"><use href="#nvm-i-send"/></svg><span x-text="busy ? 'กำลังโอน…' : 'ยืนยันการโอนเงิน'">ยืนยันการโอนเงิน</span></button>
            </div>
            <p class="nvm-help">โอนแล้วเงินเข้าทันที ยกเลิกไม่ได้ — ตรวจชื่อผู้รับให้ถูกก่อนกดยืนยัน</p>
        </form>
    </section>

    <div class="nvm-stack">
        <aside class="nvm-card nvm-box" aria-labelledby="nvm-myaddr-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">My address</small><h2 id="nvm-myaddr-h">Wallet Address ของฉัน</h2></div></div>
            <p class="nvm-help">ส่งให้คนที่จะโอนเงินมาหาคุณ</p>
            <div class="nvm-copy nvm-copy--box" x-data>
                <code class="nvm-num">{{ $wallet->wallet_address }}</code>
                <button type="button" @click="nvmCopy(@js($wallet->wallet_address), 'คัดลอก Wallet Address แล้ว')"><svg class="nvm-ico"><use href="#nvm-i-copy"/></svg>คัดลอก</button>
            </div>
            <div class="nvm-filter__btns nvm-gap-t">
                @if($nvR('user.wallet.qr-code'))
                    <a class="nvm-btn nvm-btn--line nvm-btn--sm" href="{{ route('user.wallet.qr-code') }}"><svg class="nvm-ico"><use href="#nvm-i-qr"/></svg>QR รับเงิน</a>
                @endif
                @if($nvR('user.wallet.qr-transfer'))
                    <a class="nvm-btn nvm-btn--line nvm-btn--sm" href="{{ route('user.wallet.qr-transfer') }}"><svg class="nvm-ico"><use href="#nvm-i-qr"/></svg>สแกนจ่าย</a>
                @endif
            </div>
        </aside>
        <aside class="nvm-card nvm-box" aria-labelledby="nvm-tfr-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Before you send</small><h2 id="nvm-tfr-h">ก่อนกดโอน</h2></div></div>
            <ol class="nvm-steps">
                <li>ขอ Wallet Address จากผู้รับ หรือให้ผู้รับเปิด QR รับเงิน</li>
                <li>ระบบจะแสดงชื่อผู้รับ — ตรวจให้ตรงก่อนเสมอ</li>
                <li>ยอดหักจากกระเป๋า = ยอดโอน + ค่าธรรมเนียม</li>
                <li>โอนแล้วยกเลิกไม่ได้ · ไม่มีใครขอ PIN จากคุณ รวมถึงทีมงาน</li>
            </ol>
        </aside>
    </div>
</div>

@push('scripts')
<script>
    // โอนเงิน: ค้นชื่อผู้รับจาก Wallet Address + คำนวณยอดหักรวมค่าธรรมเนียม + กันกดซ้ำ
    document.addEventListener('alpine:init', function () {
        window.Alpine.data('nvmTransfer', function (o) {
            return {
                policy: o.policy, balance: o.balance, min: o.min,
                to: o.to, amount: o.amount, busy: false,
                who: '', whoErr: '', looked: '',
                init() { if (this.to) this.lookup(); },
                get amt() { var a = parseFloat(this.amount); return isNaN(a) || a <= 0 ? 0 : a; },
                get fee() {
                    if (this.amt <= 0) return 0;
                    var f = this.policy.fee_type === 'percentage' ? this.amt * this.policy.fee_amount / 100 : this.policy.fee_amount;
                    f = Math.max(this.policy.fee_min, Math.min(this.policy.fee_max, f));
                    return Math.round(f * 100) / 100;
                },
                get total() { return Math.round((this.amt + this.fee) * 100) / 100; },
                // ผู้รับต้องค้นเจอ (ถ้าค้นได้) + ยอดผ่านขั้นต่ำ + เงินพอ
                get ok() { return this.amt >= this.min && this.total <= this.balance && ! this.whoErr && this.to.trim() !== ''; },
                lookup() {
                    var addr = this.to.trim();
                    if (! o.lookupUrl || addr === this.looked) return;
                    this.looked = addr; this.who = ''; this.whoErr = '';
                    // ยังพิมพ์ไม่ครบ (TPW + 16 = 19 ตัว) ยังไม่ค้น — ไม่ขึ้น error ระหว่างพิมพ์
                    if (addr.length < 19) return;
                    var self = this;
                    fetch(o.lookupUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                        body: JSON.stringify({ wallet_address: addr }),
                    }).then(function (r) { return r.json().then(function (j) { return { s: r.status, j: j }; }); })
                      .then(function (res) {
                          if (self.looked !== addr) return; // พิมพ์ต่อไปแล้ว ทิ้งผลเก่า
                          if (res.j && res.j.success) { self.who = res.j.data.user_name; }
                          else if (res.s === 429) { self.looked = ''; }
                          else { self.whoErr = (res.j && res.j.message) || 'ไม่พบ Wallet Address นี้'; }
                      })
                      .catch(function () { self.looked = ''; }); // เน็ตหลุด — ไม่บล็อกการโอน ให้เซิร์ฟเวอร์ตรวจซ้ำตอนส่ง
                },
                fmt(n) { return n.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
            };
        });
    });
</script>
@endpush
@endsection
