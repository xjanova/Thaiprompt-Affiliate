{{--
    ถอน TPIX ออกไปกระเป๋าภายนอก (ธีมโนวาเต็ม)
    - เลขทุกตัวใช้ bcmath แบบเดียวกับ processWithdrawal (ยอดเป็น decimal 18 ตำแหน่ง)
    - route ตรวจ turnstile:tpix_withdrawal + two-factor:withdrawal ก่อนถึง controller
--}}
@extends('layouts.user-nova')

@section('title', 'ถอน TPIX')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/tpix-tokens.css') }}?v={{ filemtime(public_path('theme-nova/member/tpix-tokens.css')) }}">@endpush

@php
    $fee = (string) $tpixCurrency->withdrawal_fee;
    $minAmount = (string) $tpixCurrency->min_withdrawal;
    $maxAmount = bcsub((string) $wallet->balance, $fee, 8);
    if ($tpixCurrency->max_withdrawal && bccomp($maxAmount, (string) $tpixCurrency->max_withdrawal, 8) > 0) {
        $maxAmount = (string) $tpixCurrency->max_withdrawal;
    }
    $walletActive = $wallet->isActive();
    // ยอดไม่ถึงขั้นต่ำ → ฟอร์มยังแสดงแต่กดส่งไม่ได้ พร้อมบอกเหตุผล
    $canWithdraw = $walletActive && bccomp($maxAmount, '0', 8) > 0 && bccomp($maxAmount, $minAmount, 8) >= 0;
    $money = fn ($n) => number_format((float) $n, 8);
@endphp

@section('content')

<div class="nvm-stack">

<section class="nvm-ph" aria-labelledby="nvm-tpw-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.tpix.wallet') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>กลับกระเป๋า</a>
            <div class="nvm-eyebrow nvm-latin">Withdraw TPIX</div>
            <h1 id="nvm-tpw-h">ถอน TPIX</h1>
            <p class="nvm-ph__lead">โอนเหรียญออกไปยังกระเป๋าภายนอกบนเครือข่าย TPIX</p>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/wallet/withdraw.webp') }}" alt="" width="200" height="200">
    </div>
</section>

<div class="nvm-split">
    <section class="nvm-card nvm-box" aria-labelledby="nvm-tpw-f-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Withdrawal</small><h2 id="nvm-tpw-f-h">ฟอร์มถอน TPIX</h2></div></div>

        @unless($walletActive)
            <div class="nvm-status is-bad">
                <svg class="nvm-ico"><use href="#nvm-i-lock"/></svg>
                <span>กระเป๋า TPIX ของคุณถูกระงับการใช้งาน ยังถอนไม่ได้</span>
            </div>
        @elseif(! $canWithdraw)
            <div class="nvm-status is-bad">
                <svg class="nvm-ico"><use href="#nvm-i-info"/></svg>
                <span>ยอดคงเหลือไม่ถึงขั้นต่ำ<small>ต้องมีอย่างน้อย <span class="nvm-num">{{ $money(bcadd($minAmount, $fee, 8)) }}</span> TPIX รวมค่าธรรมเนียม</small></span>
            </div>
        @endunless

        <form class="nvm-form {{ $canWithdraw ? '' : 'nvm-gap-t' }}" action="{{ route('user.tpix.withdrawal.process') }}" method="POST"
              x-data="{ amount: @js(old('amount', '')), fee: {{ (float) $fee }}, maxAmount: @js(rtrim(rtrim($maxAmount, '0'), '.')), canWithdraw: @js($canWithdraw), submitting: false }"
              x-on:submit="submitting = true"
              x-on:pageshow.window="submitting = false">
            @csrf

            <div class="nvm-field">
                <label class="nvm-label" for="tpix-address">ที่อยู่ปลายทาง <em>*</em></label>
                <input id="tpix-address" class="nvm-input nvm-tx-mono @error('address') is-bad @enderror" type="text" name="address" value="{{ old('address') }}" required
                       maxlength="42" pattern="0x[a-fA-F0-9]{40}" title="ที่อยู่ต้องขึ้นต้นด้วย 0x ตามด้วยอักขระ 40 ตัว"
                       autocomplete="off" autocapitalize="off" spellcheck="false" placeholder="0x...">
                @error('address')
                    <span class="nvm-err">{{ $message }}</span>
                @else
                    <span class="nvm-help">ตรวจสอบที่อยู่ให้ถูกต้อง โอนไปที่อยู่ผิดแล้วกู้คืนไม่ได้</span>
                @enderror
            </div>

            <div class="nvm-field">
                <div class="nvm-tx-bar">
                    <label class="nvm-label" for="tpix-amount">จำนวนที่ต้องการถอน <em>*</em></label>
                    @if($canWithdraw)
                        <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" x-on:click="amount = maxAmount">ถอนสูงสุด</button>
                    @endif
                </div>
                <input id="tpix-amount" class="nvm-input nvm-num @error('amount') is-bad @enderror" type="number" name="amount" x-model="amount" required
                       step="0.00000001" min="{{ $minAmount }}" @if($canWithdraw) max="{{ $maxAmount }}" @endif
                       inputmode="decimal" placeholder="0.00000000">
                @error('amount')
                    <span class="nvm-err">{{ $message }}</span>
                @enderror
            </div>

            {{-- ยอดที่จะถูกหัก --}}
            <dl class="nvm-kv" x-show="parseFloat(amount) > 0" x-cloak>
                <dt>ปลายทางจะได้รับ</dt><dd class="nvm-num"><span x-text="(parseFloat(amount) || 0).toFixed(8)"></span> TPIX</dd>
                <dt>ค่าธรรมเนียม</dt><dd class="nvm-num"><span x-text="fee.toFixed(8)"></span> TPIX</dd>
                <dt class="nvm-kv__total">หักจากกระเป๋า</dt><dd class="nvm-kv__total nvm-num"><span x-text="((parseFloat(amount) || 0) + fee).toFixed(8)"></span> TPIX</dd>
            </dl>

            <label class="nvm-check">
                <input type="checkbox" required>
                <span>ฉันตรวจสอบที่อยู่ปลายทางและจำนวนแล้ว และเข้าใจว่าระบบจะหักยอดทันทีเมื่อส่งคำขอ</span>
            </label>

            {{-- route ตรวจ turnstile:tpix_withdrawal --}}
            <div class="nvm-turnstile"><x-turnstile point="tpix_withdrawal" /></div>

            <button type="submit" class="nvm-btn nvm-btn--gold nvm-w-full" @disabled(! $canWithdraw)
                    x-bind:disabled="submitting || ! canWithdraw">
                <svg class="nvm-ico"><use href="#nvm-i-up"/></svg>
                <span x-text="submitting ? 'กำลังส่งคำขอ...' : 'ส่งคำขอถอน'">ส่งคำขอถอน</span>
            </button>
        </form>
    </section>

    <div class="nvm-stack">
        <aside class="nvm-card nvm-box" aria-labelledby="nvm-tpw-b-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Balance</small><h2 id="nvm-tpw-b-h">ยอดคงเหลือ</h2></div></div>
            <div class="nvm-tx-big nvm-num">{{ $money($wallet->balance) }}<span>TPIX</span></div>
            <div class="nvm-minis nvm-gap-t">
                <div class="nvm-mini"><small>ถอนขั้นต่ำ</small><b class="nvm-num">{{ $money($minAmount) }}</b></div>
                <div class="nvm-mini"><small>ค่าธรรมเนียม</small><b class="nvm-num">{{ $money($fee) }}</b></div>
                <div class="nvm-mini nvm-mini--wide"><small>ถอนสูงสุดต่อครั้ง</small><b class="nvm-num">{{ $tpixCurrency->max_withdrawal ? $money($tpixCurrency->max_withdrawal) : 'ไม่จำกัด' }}</b></div>
            </div>
        </aside>

        <aside class="nvm-card nvm-box" aria-labelledby="nvm-tpw-w-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Before you withdraw</small><h2 id="nvm-tpw-w-h">ก่อนถอน</h2></div></div>
            <ul class="nvm-tx-ul">
                <li>รับเฉพาะที่อยู่บนเครือข่าย TPIX เท่านั้น</li>
                <li>โอนไปที่อยู่ผิดหรือเครือข่ายผิด เหรียญจะสูญหายถาวร</li>
                <li>หลังส่งคำขอ รายการจะอยู่ในสถานะรอดำเนินการ</li>
            </ul>
        </aside>
    </div>
</div>

<section class="nvm-card nvm-box" aria-labelledby="nvm-tpw-l-h">
    <div class="nvm-sec-h"><div><small class="nvm-latin">Recent withdrawals</small><h2 id="nvm-tpw-l-h">รายการถอนล่าสุด</h2></div></div>
    @if($recentWithdrawals->isNotEmpty())
        <ul class="nvm-txs">
            @foreach($recentWithdrawals as $withdrawal)
                <li class="nvm-tx">
                    <span class="nvm-tx__ic"><svg class="nvm-ico"><use href="#nvm-i-up"/></svg></span>
                    <span class="nvm-tx__t nvm-tx__t--wrap">
                        <b class="nvm-num">{{ $money($withdrawal->amount) }} TPIX</b>
                        <small><span class="nvm-tx-mono">{{ substr((string) $withdrawal->to_address, 0, 6) }}...{{ substr((string) $withdrawal->to_address, -4) }}</span> · <span class="nvm-num">{{ $withdrawal->created_at->format('d/m/Y H:i') }}</span></small>
                        @if($withdrawal->status === 'cancelled' && $withdrawal->error_message)
                            <small class="nvm-err">ระบบคืนยอดแล้ว · เหตุผล: {{ $withdrawal->error_message }}</small>
                        @endif
                        @if($withdrawal->tx_hash)
                            <small class="nvm-tx-mono">tx {{ $withdrawal->tx_hash }}</small>
                        @endif
                    </span>
                    <span class="nvm-tx__r">@include('user.tpix.partials.status-pill', ['status' => $withdrawal->status])</span>
                </li>
            @endforeach
        </ul>
    @else
        <div class="nvm-empty">
            <img src="{{ asset('images/nova/wallet/empty.webp') }}" alt="" width="86" height="86" loading="lazy">
            <b>ยังไม่มีรายการถอน</b>
        </div>
    @endif
</section>

</div>
@endsection
