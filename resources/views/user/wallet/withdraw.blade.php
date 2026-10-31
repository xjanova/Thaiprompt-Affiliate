@extends('layouts.user-nova')

@section('title', 'ถอนเงิน')

@php
    // ถอนเงิน — ธีมโนวาเต็ม · ยอดสุทธิคำนวณสดจากนโยบายจริง (WalletSetting ผ่าน $feePolicy)
    $nvR = fn ($name) => \Illuminate\Support\Facades\Route::has($name);
    [$balInt, $balDec] = explode('.', number_format((float) $wallet->balance, 2));
    $minAmount = max(1, (float) $feePolicy['min_amount']);
    $maxAmount = min((float) $wallet->balance, (float) $feePolicy['max_amount']);
    // ยอดคงเหลือไม่ถึงขั้นต่ำ → ฟอร์มยังโชว์แต่กดส่งไม่ได้ พร้อมบอกเหตุผล
    $canWithdraw = $maxAmount >= $minAmount;
    $turnstile = config('turnstile.enabled') && config('turnstile.site_key') && config('turnstile.points.withdrawal_request');
    $last4 = fn ($no) => substr(preg_replace('/[^0-9A-Za-z]/', '', (string) $no), -4);
    $pct = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
    // ส่งไม่ผ่านแล้วกลับมา → เลือกบัญชีเดิม ไม่งั้นใช้บัญชีค่าเริ่มต้น
    $pickedMethod = (string) old('payment_method_id', optional($paymentMethods->firstWhere('is_default', true) ?? $paymentMethods->first())->id);
@endphp

@push('scripts')
@if($turnstile)
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
@endif
@endpush

@section('content')

<section class="nvm-ph" aria-labelledby="nvm-wd-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            @if($nvR('user.wallet.index'))
                <a class="nvm-ph__back" href="{{ route('user.wallet.index') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>กระเป๋าเงิน</a>
            @endif
            <div class="nvm-eyebrow nvm-latin">Withdraw</div>
            <h1 id="nvm-wd-h">ถอนเงินเข้าบัญชี</h1>
            <p class="nvm-ph__lead">ระบบตรวจและโอนเป็นรอบ วันที่ 2 และ 17 ของทุกเดือน · ชื่อบัญชีต้องตรงกับชื่อที่ยืนยันตัวตน</p>
            <div class="nvm-bal">
                <small>ยอดที่ถอนได้</small>
                <b class="nvm-foil nvm-num">฿{{ $balInt }}<span>.{{ $balDec }}</span></b>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/wallet/withdraw.webp') }}" alt="" width="200" height="200">
    </div>
</section>

@if(! $canWithdraw && $paymentMethods->isNotEmpty())
    <section class="nvm-card nvm-alert" role="status">
        <img class="nvm-alert__img" src="{{ asset('images/nova/wallet/scale.webp') }}" alt="" width="58" height="58" loading="lazy">
        <div class="nvm-alert__txt">
            <b>ยอดคงเหลือยังไม่ถึงขั้นต่ำ</b>
            <p>ถอนได้ตั้งแต่ ฿{{ number_format($minAmount, 2) }} ขึ้นไป ตอนนี้มี ฿{{ number_format((float) $wallet->balance, 2) }}</p>
        </div>
    </section>
@endif

@if($errors->any() || session('error'))
    <section class="nvm-card nvm-alert nvm-alert--bad" role="alert">
        <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg></span>
        <div class="nvm-alert__txt">
            <b>ถอนเงินไม่สำเร็จ</b>
            @if(session('error'))<p>{{ session('error') }}</p>@endif
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    </section>
@endif

<div class="nvm-split">
    <section class="nvm-card nvm-box" aria-labelledby="nvm-wdf-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Request</small><h2 id="nvm-wdf-h">แบบฟอร์มถอนเงิน</h2></div></div>

        @if($paymentMethods->isEmpty())
            <div class="nvm-empty">
                <img src="{{ asset('images/nova/wallet/bank.webp') }}" alt="" width="86" height="86" loading="lazy">
                <b>ยังไม่มีบัญชีรับเงิน</b>
                เพิ่มบัญชีธนาคารหรือพร้อมเพย์ก่อน แล้วกลับมาถอนได้ทันที
                @if($nvR('user.wallet.payment-methods'))
                    <div><a class="nvm-btn nvm-btn--gold nvm-btn--sm" href="{{ route('user.wallet.payment-methods') }}"><svg class="nvm-ico"><use href="#nvm-i-plus"/></svg>เพิ่มบัญชีรับเงิน</a></div>
                @endif
            </div>
        @else
            {{-- นโยบายค่าธรรมเนียม → Alpine ผ่าน Js::from (ห้าม @json กับ closure) --}}
            <form method="POST" action="{{ route('user.wallet.withdraw.submit') }}" class="nvm-form"
                  x-data="nvmWithdraw({ policy: {{ \Illuminate\Support\Js::from($feePolicy) }}, amount: @js((string) old('amount', '')), max: {{ $maxAmount }}, can: @js($canWithdraw) })"
                  @submit="busy = true" @pageshow.window="busy = false">
                @csrf

                <div class="nvm-field">
                    <span class="nvm-label" id="nvm-pm-lbl">บัญชีรับเงิน <em>*</em></span>
                    <div class="nvm-opts" role="radiogroup" aria-labelledby="nvm-pm-lbl">
                        @foreach($paymentMethods as $method)
                            <label class="nvm-opt">
                                <input type="radio" name="payment_method_id" value="{{ $method->id }}" required @checked($pickedMethod === (string) $method->id)>
                                <img src="{{ asset('images/nova/wallet/'.($method->type === 'promptpay' ? 'qr' : ($method->type === 'paypal' ? 'transfer' : 'bank')).'.webp') }}" alt="" width="44" height="44" loading="lazy">
                                <span>
                                    @if($method->type === 'bank_transfer')
                                        <b>{{ $method->bank_name }}</b>
                                        <small>{{ $method->account_name }} · <span class="nvm-num">•••• {{ $last4($method->account_number) }}</span></small>
                                    @elseif($method->type === 'promptpay')
                                        <b>พร้อมเพย์</b>
                                        <small>{{ $method->account_name }} · <span class="nvm-num">•••• {{ $last4($method->account_number) }}</span></small>
                                    @elseif($method->type === 'paypal')
                                        <b>PayPal</b>
                                        <small>{{ $method->paypal_email }}</small>
                                    @else
                                        <b>{{ $method->name }}</b>
                                    @endif
                                    @if($method->is_default)<span class="nvm-chip nvm-chip--gold">ค่าเริ่มต้น</span>@endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @if($nvR('user.wallet.payment-methods'))
                        <span class="nvm-help">บัญชีไม่อยู่ในรายการ? <a class="nvm-link" href="{{ route('user.wallet.payment-methods') }}">จัดการบัญชีรับเงิน</a></span>
                    @endif
                </div>

                <div class="nvm-field">
                    <label class="nvm-label" for="wd-amount">จำนวนเงินที่ถอน (บาท) <em>*</em></label>
                    <div class="nvm-amount"><span>฿</span><input id="wd-amount" type="number" name="amount" step="0.01" min="{{ $minAmount }}" max="{{ $maxAmount }}" inputmode="decimal" required class="nvm-input @error('amount') is-bad @enderror" placeholder="0.00" x-model="amount"></div>
                    <div class="nvm-picks">
                        <button type="button" @click="amount = String(max)">ถอนทั้งหมด ฿{{ number_format($maxAmount, 2) }}</button>
                    </div>
                    <span class="nvm-help">ขั้นต่ำ ฿{{ number_format($minAmount, 2) }} · สูงสุด ฿{{ number_format($maxAmount, 2) }}</span>
                    <span class="nvm-err" x-show="amt > max" x-cloak>ยอดเกินกว่าที่ถอนได้</span>
                </div>

                {{-- สรุปยอดสุทธิ — คำนวณสดตามนโยบายจริง --}}
                <div class="nvm-mini" x-show="amt > 0" x-cloak>
                    <dl class="nvm-kv">
                        <dt>ยอดถอน</dt><dd class="nvm-num" x-text="'฿' + fmt(amt)"></dd>
                        <dt>ค่าธรรมเนียม</dt><dd class="nvm-num" x-text="'-฿' + fmt(fee)"></dd>
                        <dt x-show="tax > 0">ภาษีหัก ณ ที่จ่าย</dt><dd class="nvm-num" x-show="tax > 0" x-text="'-฿' + fmt(tax)"></dd>
                        <dt class="nvm-kv__total">ยอดที่จะได้รับ</dt><dd class="nvm-num nvm-kv__total" x-text="'฿' + fmt(net)"></dd>
                    </dl>
                </div>

                @if($wallet->hasPIN())
                    {{-- PIN เฉพาะกระเป๋าที่ตั้งไว้ — ลูกค้าที่ไม่เคยตั้ง PIN ไม่ต้องกรอก --}}
                    <div class="nvm-field">
                        <label class="nvm-label" for="wd-pin">PIN กระเป๋าเงิน (6 หลัก) <em>*</em></label>
                        <input id="wd-pin" type="password" name="pin" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" autocomplete="off" required class="nvm-input @error('pin') is-bad @enderror" placeholder="••••••">
                        <span class="nvm-help">กรอก PIN เพื่อยืนยันการถอนเงิน</span>
                    </div>
                @endif

                <div class="nvm-field">
                    <label class="nvm-label" for="wd-note">หมายเหตุ (ถ้ามี)</label>
                    <textarea id="wd-note" name="user_note" rows="3" maxlength="500" class="nvm-input" placeholder="ไม่เกิน 500 ตัวอักษร">{{ old('user_note') }}</textarea>
                </div>

                @if($turnstile)
                    <div class="nvm-turnstile">
                        <div class="cf-turnstile" data-sitekey="{{ config('turnstile.site_key') }}" data-theme="{{ config('turnstile.theme') }}" data-size="{{ config('turnstile.size') }}"></div>
                    </div>
                @endif

                <div class="nvm-filter__btns">
                    <a href="{{ route('user.wallet.index') }}" class="nvm-btn nvm-btn--line">ยกเลิก</a>
                    <button type="submit" class="nvm-btn nvm-btn--gold" :disabled="! can || busy || amt > max"><svg class="nvm-ico"><use href="#nvm-i-cash"/></svg><span x-text="busy ? 'กำลังส่งคำขอ…' : 'ยืนยันการถอนเงิน'">ยืนยันการถอนเงิน</span></button>
                </div>
            </form>
        @endif
    </section>

    <div class="nvm-stack">
        <aside class="nvm-card nvm-box" aria-labelledby="nvm-rule-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Conditions</small><h2 id="nvm-rule-h">เงื่อนไขการถอนเงิน</h2></div></div>
            <ol class="nvm-steps">
                <li>ถอนขั้นต่ำ ฿{{ number_format($minAmount) }}</li>
                <li>ค่าธรรมเนียม
                    @if((float) $feePolicy['fee_amount'] <= 0 && (float) $feePolicy['fee_min'] <= 0)
                        ไม่มี — ได้รับเต็มจำนวน
                    @elseif($feePolicy['fee_type'] === 'percentage')
                        {{ $pct($feePolicy['fee_amount']) }}% (ขั้นต่ำ ฿{{ number_format((float) $feePolicy['fee_min']) }}@if($feePolicy['fee_max'] < 999999) สูงสุด ฿{{ number_format((float) $feePolicy['fee_max']) }}@endif)
                    @else
                        ฿{{ number_format((float) $feePolicy['fee_amount'], 2) }} ต่อรายการ
                    @endif
                </li>
                @if($feePolicy['tax_enabled'] && $feePolicy['tax_percentage'] > 0)
                    <li>ยอดตั้งแต่ ฿{{ number_format((float) $feePolicy['tax_threshold']) }} ขึ้นไป หักภาษี ณ ที่จ่าย {{ $pct($feePolicy['tax_percentage']) }}%</li>
                @endif
                <li>ตรวจและโอนเข้าบัญชีเป็นรอบ วันที่ 2 และ 17 ของทุกเดือน</li>
                <li>ชื่อบัญชีรับเงินต้องตรงกับชื่อที่ยืนยันตัวตน (KYC)</li>
                {{-- (2026-10-29) เดิมเขียน "รีเซ็ตได้ที่หน้าโปรไฟล์" แต่หน้าเว็บไม่มีที่ตั้ง/รีเซ็ต PIN — รีเซ็ตได้ทางแอดมินเท่านั้น --}}
                <li>เก็บ PIN เป็นความลับ · ลืม PIN แจ้งฝ่ายสนับสนุนเพื่อรีเซ็ต</li>
                <li>พบปัญหาการถอนเงิน ติดต่อฝ่ายสนับสนุน</li>
            </ol>
        </aside>
        @if($nvR('user.wallet.withdrawals'))
            <a class="nvm-card nvm-alert nvm-alert--info" href="{{ route('user.wallet.withdrawals') }}">
                <img class="nvm-alert__img" src="{{ asset('images/nova/wallet/hourglass.webp') }}" alt="" width="58" height="58" loading="lazy">
                <div class="nvm-alert__txt">
                    <b>คำขอถอนเงินของฉัน</b>
                    <p>ดูสถานะ รอบโอน หรือยกเลิกคำขอที่ยังรอตรวจ</p>
                </div>
                <div class="nvm-alert__side"><small>ดูทั้งหมด →</small></div>
            </a>
        @endif
    </div>
</div>

@push('scripts')
<script>
    // ถอนเงิน: คำนวณค่าธรรมเนียม/ภาษี/ยอดสุทธิสด (สูตรเดียวกับ WithdrawalService) + กันกดส่งซ้ำ
    document.addEventListener('alpine:init', function () {
        window.Alpine.data('nvmWithdraw', function (o) {
            return {
                policy: o.policy,
                amount: o.amount,
                max: o.max,
                can: o.can,
                busy: false,
                get amt() { var a = parseFloat(this.amount); return isNaN(a) || a <= 0 ? 0 : a; },
                get fee() {
                    if (this.amt <= 0) return 0;
                    var f = this.policy.fee_type === 'percentage' ? this.amt * this.policy.fee_amount / 100 : this.policy.fee_amount;
                    f = Math.max(this.policy.fee_min, Math.min(this.policy.fee_max, f));
                    return Math.round(f * 100) / 100;
                },
                get tax() {
                    if (! this.policy.tax_enabled || this.amt <= 0 || this.amt < this.policy.tax_threshold) return 0;
                    return Math.round(this.amt * this.policy.tax_percentage) / 100;
                },
                get net() { return Math.max(0, Math.round((this.amt - this.fee - this.tax) * 100) / 100); },
                fmt(n) { return n.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
            };
        });
    });
</script>
@endpush
@endsection
