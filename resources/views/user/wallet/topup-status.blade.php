{{--
    สถานะรายการเติมเงิน (ธีมโนวาเต็ม) — รอ/กำลังตรวจ ระบบเช็คซ้ำให้อัตโนมัติจนสำเร็จหรือหมดอายุ
--}}
@extends('layouts.user-nova')

@section('title', 'สถานะการเติมเงิน')

@php
    $nvR = fn ($name) => \Illuminate\Support\Facades\Route::has($name);
    $st = $transaction->status;
    $expired = ! $transaction->isCompleted() && $transaction->isExpired();
    $meta = [
        'completed' => ['ชำระเงินสำเร็จ', 'ok', 'deposit', 'เงินเข้ากระเป๋าเรียบร้อยแล้ว'],
        'processing' => ['กำลังตรวจสอบการชำระ', 'info', 'hourglass', 'ระบบกำลังยืนยันยอด หน้านี้อัปเดตเองเมื่อเสร็จ'],
        'pending' => ['รอการชำระเงิน', 'warn', 'hourglass', 'ยังไม่ได้รับการชำระสำหรับรายการนี้'],
        'failed' => ['ชำระเงินไม่สำเร็จ', 'bad', 'empty', 'รายการนี้ไม่สำเร็จ ลองสร้างรายการใหม่'],
        'cancelled' => ['ยกเลิกแล้ว', 'bad', 'empty', 'รายการนี้ถูกยกเลิก'],
    ];
    [$label, $tone, $art, $lead] = $meta[$st] ?? [$st, 'info', 'hourglass', ''];
    if ($expired && in_array($st, ['pending', 'processing'], true)) {
        [$label, $tone, $art, $lead] = ['รายการหมดอายุ', 'bad', 'empty', 'เลยเวลาชำระแล้ว หากโอนไปแล้วแจ้งฝ่ายสนับสนุนพร้อมรหัสรายการ'];
    }
    $methodLabels = ['promptpay' => 'พร้อมเพย์', 'bank_transfer' => 'โอนธนาคาร', 'credit_card' => 'บัตรเครดิต', 'pending' => 'ยังไม่เลือก'];
    $payAmount = (float) $transaction->amount;
    $creditAmount = (float) data_get($transaction->metadata, 'original_amount', $payAmount);
    $live = in_array($st, ['pending', 'processing'], true) && ! $expired;
@endphp

@section('content')

<section class="nvm-ph" aria-labelledby="nvm-ts-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            @if($nvR('user.wallet.index'))
                <a class="nvm-ph__back" href="{{ route('user.wallet.index') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>กระเป๋าเงิน</a>
            @endif
            <div class="nvm-eyebrow nvm-latin">Top up status</div>
            <h1 id="nvm-ts-h">{{ $label }}</h1>
            <p class="nvm-ph__lead">{{ $lead }}</p>
            <div class="nvm-bal">
                <small>ยอดเข้ากระเป๋า</small>
                @php [$cInt, $cDec] = explode('.', number_format($creditAmount, 2)); @endphp
                <b class="nvm-foil nvm-num">฿{{ $cInt }}<span>.{{ $cDec }}</span></b>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/wallet/'.$art.'.webp') }}" alt="" width="200" height="200">
    </div>
</section>

<div class="nvm-split" @if($live) x-data="nvmTopupWatch(@js(route('user.wallet.topup.check', $transaction->transaction_id)), @js(optional($transaction->expired_at)->toIso8601String()))" @endif>
    <section class="nvm-card nvm-box" aria-labelledby="nvm-tsd-h">
        <div class="nvm-sec-h">
            <div><small class="nvm-latin">Details</small><h2 id="nvm-tsd-h">รายละเอียดรายการ</h2></div>
            <span class="nvm-chip nvm-chip--{{ $tone }}">{{ $label }}</span>
        </div>
        <dl class="nvm-kv">
            <dt>รหัสรายการ</dt><dd class="nvm-num">{{ $transaction->transaction_id }}</dd>
            <dt>ยอดที่ชำระ</dt><dd class="nvm-num">฿{{ number_format($payAmount, 2) }}</dd>
            @if(abs($creditAmount - $payAmount) >= 0.005)
                {{-- 🐛 (2026-10-29) เดิมโชว์แค่ยอดที่มีสตางค์จับคู่ (เช่น 500.37) แต่เข้ากระเป๋าจริง 500 ลูกค้าเข้าใจว่าเงินหาย --}}
                <dt>สตางค์จับคู่อัตโนมัติ</dt><dd class="nvm-num">฿{{ number_format($payAmount - $creditAmount, 2) }}</dd>
            @endif
            <dt>ช่องทาง</dt><dd>{{ $methodLabels[$transaction->payment_method] ?? $transaction->payment_method }}</dd>
            <dt>สร้างเมื่อ</dt><dd class="nvm-num">{{ $transaction->created_at->format('d/m/Y H:i') }}</dd>
            @if($transaction->paid_at)
                <dt>ชำระเมื่อ</dt><dd class="nvm-num">{{ $transaction->paid_at->format('d/m/Y H:i') }}</dd>
            @elseif($transaction->expired_at)
                <dt>หมดอายุ</dt><dd class="nvm-num">{{ $transaction->expired_at->format('d/m/Y H:i') }}</dd>
            @endif
            <dt class="nvm-kv__total">เข้ากระเป๋า</dt><dd class="nvm-num nvm-kv__total">฿{{ number_format($creditAmount, 2) }}</dd>
        </dl>
        @if($live)
            <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-clock"/></svg><span x-text="left !== null ? 'เช็คสถานะอัตโนมัติ · เหลือเวลา ' + clock : 'เช็คสถานะอัตโนมัติทุก 5 วินาที'">เช็คสถานะอัตโนมัติทุก 5 วินาที</span></p>
        @endif
        <div class="nvm-filter__btns nvm-gap-t">
            @if($st === 'pending' && ! $expired && $nvR('user.wallet.topup.payment'))
                <a class="nvm-btn nvm-btn--gold" href="{{ route('user.wallet.topup.payment', $transaction->transaction_id) }}"><svg class="nvm-ico"><use href="#nvm-i-card"/></svg>ชำระเงินต่อ</a>
            @elseif(($expired || in_array($st, ['failed', 'cancelled'], true)) && $nvR('user.wallet.topup'))
                <a class="nvm-btn nvm-btn--gold" href="{{ route('user.wallet.topup') }}"><svg class="nvm-ico"><use href="#nvm-i-plus"/></svg>สร้างรายการใหม่</a>
            @endif
            @if($nvR('user.wallet.index'))
                <a class="nvm-btn nvm-btn--line" href="{{ route('user.wallet.index') }}"><svg class="nvm-ico"><use href="#nvm-i-wallet"/></svg>กระเป๋าเงิน</a>
            @endif
        </div>
    </section>

    <aside class="nvm-card nvm-box" aria-labelledby="nvm-tsh-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Need help?</small><h2 id="nvm-tsh-h">ยอดยังไม่เข้า?</h2></div></div>
        <ol class="nvm-steps">
            <li>ตรวจว่าโอนยอดตรงทุกสตางค์ตามที่หน้าชำระแสดง</li>
            <li>ระบบธนาคารอาจช้า 1–5 นาที หน้านี้อัปเดตเอง</li>
            <li>เกิน 10 นาที แจ้งฝ่ายสนับสนุนพร้อมรหัส <span class="nvm-num">{{ $transaction->transaction_id }}</span> และสลิป</li>
        </ol>
    </aside>
</div>

@if($live)
    @push('scripts')
    <script>
        // เช็คซ้ำทุก 5 วินาที → สำเร็จหรือหมดอายุแล้วรีโหลดให้เห็นผล (หยุดเช็คทันที)
        document.addEventListener('alpine:init', function () {
            window.Alpine.data('nvmTopupWatch', function (url, expires) {
                return {
                    left: null, poll: null, timer: null, busy: false,
                    get clock() { var m = Math.floor(this.left / 60), s = this.left % 60; return m + ':' + String(s).padStart(2, '0') + ' นาที'; },
                    init() {
                        var self = this;
                        if (expires) {
                            var end = new Date(expires).getTime();
                            var tick = function () { self.left = Math.max(0, Math.round((end - Date.now()) / 1000)); };
                            tick(); this.timer = setInterval(tick, 1000);
                        }
                        this.poll = setInterval(function () {
                            if (self.busy || document.hidden) return;
                            self.busy = true;
                            fetch(url, { headers: { 'Accept': 'application/json' } })
                                .then(function (r) { return r.json(); })
                                .then(function (j) { if (j.success && j.data && (j.data.is_completed || j.data.is_expired)) { self.destroy(); window.location.reload(); } })
                                .catch(function () {})
                                .finally(function () { self.busy = false; });
                        }, 5000);
                    },
                    destroy() { clearInterval(this.poll); clearInterval(this.timer); },
                };
            });
        });
    </script>
    @endpush
@endif
@endsection
