{{--
    เติมเงิน ขั้น 2: เลือกช่องทางชำระ (ธีมโนวาเต็ม) — รายการมีอายุ (expired_at) นับถอยหลังบนจอ
--}}
@extends('layouts.user-nova')

@section('title', 'เลือกช่องทางชำระ')

@php
    $nvR = fn ($name) => \Illuminate\Support\Facades\Route::has($name);
    // รูปประกอบตามรหัสเกตเวย์
    $artFor = function (string $code) {
        $c = strtolower($code);

        return match (true) {
            str_contains($c, 'promptpay'), str_contains($c, 'qr') => 'qr',
            str_contains($c, 'bank') => 'bank',
            str_contains($c, 'card'), str_contains($c, 'stripe'), str_contains($c, 'omise') => 'card',
            str_contains($c, 'true') || str_contains($c, 'wallet') => 'phone',
            default => 'deposit',
        };
    };
    // ช่องทางสำรองเมื่อแอดมินยังไม่ตั้งเกตเวย์ (ต้องตรงกับที่ processTopupPayment ยอมรับ)
    $options = $paymentGateways->map(fn ($g) => [
        'code' => $g->code,
        'name' => $g->name,
        'desc' => $g->description,
        'fees' => is_array($g->fees) ? $g->fees : [],
    ])->values()->all();
    if (! count($options)) {
        $options = [
            ['code' => 'promptpay', 'name' => 'พร้อมเพย์ (PromptPay)', 'desc' => 'สแกน QR จากแอปธนาคาร เข้าอัตโนมัติ', 'fees' => []],
            ['code' => 'bank_transfer', 'name' => 'โอนผ่านธนาคาร', 'desc' => 'โอนแล้วรอตรวจสอบ', 'fees' => []],
        ];
    }
    $picked = old('payment_method', count($options) === 1 ? $options[0]['code'] : null);
@endphp

@section('content')

<section class="nvm-ph" aria-labelledby="nvm-tp-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            @if($nvR('user.wallet.topup'))
                <a class="nvm-ph__back" href="{{ route('user.wallet.topup') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>เปลี่ยนยอด</a>
            @endif
            <div class="nvm-eyebrow nvm-latin">Step 2 of 2</div>
            <h1 id="nvm-tp-h">เลือกช่องทางชำระ</h1>
            <p class="nvm-ph__lead">รหัสรายการ <span class="nvm-num">{{ $transaction->transaction_id }}</span></p>
            <div class="nvm-bal">
                <small>ยอดเติมเงิน</small>
                @php [$aInt, $aDec] = explode('.', number_format((float) $transaction->amount, 2)); @endphp
                <b class="nvm-foil nvm-num">฿{{ $aInt }}<span>.{{ $aDec }}</span></b>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/wallet/card.webp') }}" alt="" width="200" height="200">
    </div>
</section>

@if($errors->any())
    <section class="nvm-card nvm-alert nvm-alert--bad" role="alert">
        <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg></span>
        <div class="nvm-alert__txt">
            <b>ชำระเงินไม่สำเร็จ</b>
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    </section>
@endif

<div class="nvm-split" x-data="nvmTopupPay({ method: @js($picked), expires: @js(optional($transaction->expired_at)->toIso8601String()) })">
    <section class="nvm-card nvm-box" aria-labelledby="nvm-tpm-h">
        <div class="nvm-sec-h">
            <div><small class="nvm-latin">Payment method</small><h2 id="nvm-tpm-h">ช่องทางที่เปิดให้บริการ</h2></div>
            <span class="nvm-chip" :class="left > 300 ? 'nvm-chip--warn' : 'nvm-chip--bad'" x-show="expires" x-cloak>
                <svg class="nvm-ico"><use href="#nvm-i-clock"/></svg><span x-text="left > 0 ? 'เหลือ ' + clock : 'หมดเวลาแล้ว'"></span>
            </span>
        </div>
        <form method="POST" action="{{ route('user.wallet.topup.payment.process', $transaction->transaction_id) }}" class="nvm-form" @submit="if (! method || left === 0) { $event.preventDefault(); return; } busy = true" @pageshow.window="busy = false">
            @csrf
            <div class="nvm-opts" role="radiogroup" aria-labelledby="nvm-tpm-h">
                @foreach($options as $opt)
                    @php
                        $feeAmt = (float) ($opt['fees']['deposit_fee_amount'] ?? 0);
                        $feeType = $opt['fees']['deposit_fee_type'] ?? 'fixed';
                    @endphp
                    <label class="nvm-opt">
                        <input type="radio" name="payment_method" value="{{ $opt['code'] }}" x-model="method" required>
                        <img src="{{ asset('images/nova/wallet/'.$artFor($opt['code']).'.webp') }}" alt="" width="44" height="44" loading="lazy">
                        <span>
                            <b>{{ $opt['name'] }}</b>
                            @if($opt['desc'])<small>{{ $opt['desc'] }}</small>@endif
                            @if($feeAmt > 0)
                                <span class="nvm-chip nvm-chip--warn">ค่าธรรมเนียม {{ $feeType === 'percentage' ? rtrim(rtrim(number_format($feeAmt, 2), '0'), '.').'%' : '฿'.number_format($feeAmt, 2) }}</span>
                            @else
                                <span class="nvm-chip nvm-chip--ok">ไม่มีค่าธรรมเนียม</span>
                            @endif
                        </span>
                    </label>
                @endforeach
            </div>
            <button type="submit" class="nvm-btn nvm-btn--gold" :disabled="! method || busy || left === 0">
                <svg class="nvm-ico"><use href="#nvm-i-lock"/></svg>
                <span x-text="busy ? 'กำลังดำเนินการ…' : (left === 0 ? 'รายการหมดอายุ' : (method ? 'ชำระเงิน ฿{{ number_format((float) $transaction->amount, 2) }}' : 'เลือกช่องทางก่อน'))">ชำระเงิน</span>
            </button>
            <p class="nvm-note is-bad" x-show="left === 0" x-cloak>
                <svg class="nvm-ico"><use href="#nvm-i-info"/></svg>
                <span>รายการนี้หมดอายุแล้ว @if($nvR('user.wallet.topup'))<a class="nvm-link" href="{{ route('user.wallet.topup') }}">สร้างรายการใหม่</a>@endif</span>
            </p>
        </form>
    </section>

    <aside class="nvm-card nvm-box" aria-labelledby="nvm-tps-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Safe &amp; sound</small><h2 id="nvm-tps-h">ความปลอดภัย</h2></div></div>
        <ol class="nvm-steps">
            <li>ชำระผ่านช่องทางของธนาคาร/ผู้ให้บริการโดยตรง เว็บไม่เก็บข้อมูลบัตร</li>
            <li>ยอดเข้ากระเป๋าอัตโนมัติเมื่อระบบยืนยันการชำระ</li>
            <li>มีปัญหา แจ้งฝ่ายสนับสนุนพร้อมรหัส <span class="nvm-num">{{ $transaction->transaction_id }}</span></li>
        </ol>
    </aside>
</div>

@push('scripts')
<script>
    // นับถอยหลังอายุรายการ — หมดเวลาแล้วปิดปุ่มชำระ
    document.addEventListener('alpine:init', function () {
        window.Alpine.data('nvmTopupPay', function (o) {
            return {
                method: o.method, expires: o.expires, busy: false, left: null, timer: null,
                get clock() { var m = Math.floor(this.left / 60), s = this.left % 60; return m + ':' + String(s).padStart(2, '0') + ' นาที'; },
                init() {
                    if (! this.expires) return;
                    var end = new Date(this.expires).getTime(), self = this;
                    var tick = function () { self.left = Math.max(0, Math.round((end - Date.now()) / 1000)); if (self.left === 0) clearInterval(self.timer); };
                    tick(); this.timer = setInterval(tick, 1000);
                },
                destroy() { clearInterval(this.timer); },
            };
        });
    });
</script>
@endpush
@endsection
