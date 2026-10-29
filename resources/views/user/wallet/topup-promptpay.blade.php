{{--
    สแกนจ่ายพร้อมเพย์ (เติมเงิน/ฝากเงิน) — QR + ยอดที่ต้องโอน (มีสตางค์สำหรับจับคู่อัตโนมัติ) + เช็คสถานะอัตโนมัติ
    ใช้ร่วมกันโดย processTopupPayment และ depositPromptPay (ธีมโนวาเต็ม)
--}}
@extends('layouts.user-nova')

@section('title', 'สแกนจ่ายพร้อมเพย์')

@php
    $nvR = fn ($name) => \Illuminate\Support\Facades\Route::has($name);
    // QR จากผู้ให้บริการ: รูป (data URI / https) · JSON base64 แจ้งเตือน (ยังไม่ตั้งค่า) · หรือข้อความ payload ดิบ
    $qrIsImage = ! empty($qrCode) && (str_starts_with($qrCode, 'data:image/') || str_starts_with($qrCode, 'https://'));
    $qrData = (! empty($qrCode) && ! $qrIsImage) ? json_decode(base64_decode($qrCode, true) ?: '', true) : null;
    $qrPayload = (! empty($qrCode) && ! $qrIsImage && ! is_array($qrData)) ? $qrCode : null;

    $payAmount = (float) $transaction->amount;
    $creditAmount = (float) data_get($transaction->metadata, 'original_amount', $payAmount);
    [$pInt, $pDec] = explode('.', number_format($payAmount, 2));
    $explain = config('smschecker.customer_explanation', []);
    $ref = $refNo ?? $transaction->transaction_id;
@endphp

@section('content')

<div class="nvm-stack" x-data="nvmPromptPay({
        expires: @js(optional($transaction->expired_at)->toIso8601String()),
        checkUrl: @js(route('user.wallet.topup.check', $transaction->transaction_id)),
        doneUrl: @js(route('user.wallet.index')),
        payload: @js($qrPayload),
    })">

<section class="nvm-ph" aria-labelledby="nvm-pp-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            @if($nvR('user.wallet.index'))
                <a class="nvm-ph__back" href="{{ route('user.wallet.index') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>กระเป๋าเงิน</a>
            @endif
            <div class="nvm-eyebrow nvm-latin">PromptPay</div>
            <h1 id="nvm-pp-h">สแกนจ่ายพร้อมเพย์</h1>
            <p class="nvm-ph__lead">เปิดแอปธนาคาร สแกน QR แล้วโอนตามยอดนี้ทุกสตางค์</p>
            <div class="nvm-bal">
                <small>ยอดที่ต้องโอน</small>
                <b class="nvm-foil nvm-num">฿{{ $pInt }}<span>.{{ $pDec }}</span></b>
                @if(abs($creditAmount - $payAmount) >= 0.005)
                    <em>เข้ากระเป๋า ฿{{ number_format($creditAmount, 2) }}</em>
                @endif
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/wallet/qr.webp') }}" alt="" width="200" height="200">
    </div>
</section>

<div class="nvm-split">
    <section class="nvm-card nvm-box nvm-qr" aria-label="QR Code พร้อมเพย์">
        <span class="nvm-chip" :class="{ 'nvm-chip--info': state === 'wait', 'nvm-chip--ok': state === 'done', 'nvm-chip--bad': state === 'expired' }">
            <svg class="nvm-ico"><use :href="state === 'done' ? '#nvm-i-check' : (state === 'expired' ? '#nvm-i-x' : '#nvm-i-clock')"/></svg>
            <span x-text="state === 'done' ? 'ชำระเงินสำเร็จ กำลังพาไปกระเป๋าเงิน…' : (state === 'expired' ? 'รายการหมดอายุแล้ว' : 'รอการชำระ' + (left !== null ? ' · เหลือ ' + clock : ''))">รอการชำระ</span>
        </span>

        <div class="nvm-qr__box" :class="{ 'is-dim': state === 'expired' }">
            @if($qrIsImage)
                <img src="{{ $qrCode }}" alt="QR Code พร้อมเพย์" width="232" height="232">
            @elseif($qrPayload)
                {{-- 🔒 (2026-10-29) เดิมส่ง payload (เบอร์พร้อมเพย์ + ยอด) ไปให้ api.qrserver.com สร้างรูป — ตอนนี้สร้างในเครื่อง --}}
                <div x-ref="qr" role="img" aria-label="QR Code พร้อมเพย์"></div>
            @else
                <div class="nvm-qr__miss">
                    <svg class="nvm-ico"><use href="#nvm-i-info"/></svg>
                    <b>{{ is_array($qrData) ? ($qrData['warning'] ?? 'ยังไม่ได้ตั้งค่าพร้อมเพย์') : 'สร้าง QR Code ไม่สำเร็จ' }}</b>
                    <small>กรุณาติดต่อฝ่ายสนับสนุน · อ้างอิง {{ $ref }}</small>
                </div>
            @endif
            <span class="nvm-qr__tag">฿{{ number_format($payAmount, 2) }}</span>
        </div>

        <div class="nvm-filter__btns">
            <button type="button" class="nvm-btn nvm-btn--line" @click="nvmCopy(@js(number_format($payAmount, 2, '.', '')), 'คัดลอกยอดโอนแล้ว')"><svg class="nvm-ico"><use href="#nvm-i-copy"/></svg>คัดลอกยอด</button>
            <button type="button" class="nvm-btn nvm-btn--line" @click="nvmCopy(@js($ref), 'คัดลอกเลขอ้างอิงแล้ว')"><svg class="nvm-ico"><use href="#nvm-i-id"/></svg>คัดลอกเลขอ้างอิง</button>
        </div>
        <dl class="nvm-kv nvm-gap-t">
            <dt>เลขอ้างอิง</dt><dd class="nvm-num">{{ $ref }}</dd>
            <dt>รหัสรายการ</dt><dd class="nvm-num">{{ $transaction->transaction_id }}</dd>
        </dl>
        @if($nvR('user.wallet.topup'))
            <a class="nvm-btn nvm-btn--gold nvm-gap-t" href="{{ route('user.wallet.topup') }}" x-show="state === 'expired'" x-cloak><svg class="nvm-ico"><use href="#nvm-i-plus"/></svg>สร้างรายการใหม่</a>
        @endif
    </section>

    <aside class="nvm-stack">
        <section class="nvm-card nvm-alert nvm-alert--info">
            <img class="nvm-alert__img" src="{{ asset('images/nova/deco/coins.webp') }}" alt="" width="56" height="56" loading="lazy">
            <div class="nvm-alert__txt">
                <b>{{ $explain['title'] ?? 'ทำไมยอดโอนมีจุดทศนิยม?' }}</b>
                <p>{{ $explain['message'] ?? 'ยอดมีสตางค์เพื่อให้ระบบจับคู่การโอนได้อัตโนมัติ ไม่ต้องรอแอดมิน' }}</p>
                <p>{{ $explain['note'] ?? 'กรุณาโอนตามยอดที่แสดงทุกประการ (รวมสตางค์)' }}</p>
            </div>
        </section>
        <section class="nvm-card nvm-box" aria-labelledby="nvm-pps-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">How to pay</small><h2 id="nvm-pps-h">วิธีชำระเงิน</h2></div></div>
            <ol class="nvm-steps">
                <li>เปิดแอปธนาคาร เลือก "สแกน QR"</li>
                <li>สแกน QR ด้านซ้าย (หรือบันทึกภาพหน้าจอแล้วเลือกรูปในแอป)</li>
                <li>ตรวจยอดให้ตรง <b class="nvm-num">฿{{ number_format($payAmount, 2) }}</b> แล้วยืนยัน</li>
                <li>อยู่หน้านี้ต่อได้ ระบบเช็คให้ทุก 5 วินาที เงินเข้าแล้วจะพาไปกระเป๋าเงินเอง</li>
            </ol>
            <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg><span>โอนแล้วยอดยังไม่เข้าภายใน 10 นาที แจ้งฝ่ายสนับสนุนพร้อมเลขอ้างอิง <b class="nvm-num">{{ $ref }}</b></span></p>
        </section>
    </aside>
</div>

</div>

@push('scripts')
@if($qrPayload)
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
@endif
<script>
    // เช็คสถานะการชำระทุก 5 วินาที — หยุดเมื่อสำเร็จ/หมดอายุ และไม่ยิงตอนแท็บถูกซ่อน
    document.addEventListener('alpine:init', function () {
        window.Alpine.data('nvmPromptPay', function (o) {
            return {
                state: 'wait', left: null, timer: null, poll: null, busy: false,
                get clock() { var m = Math.floor(this.left / 60), s = this.left % 60; return m + ':' + String(s).padStart(2, '0'); },
                init() {
                    var self = this;
                    if (o.payload && this.$refs.qr && typeof QRCode !== 'undefined') {
                        try { new QRCode(this.$refs.qr, { text: o.payload, width: 464, height: 464, colorDark: '#0b1633', colorLight: '#ffffff', correctLevel: QRCode.CorrectLevel.M }); } catch (e) { this.$refs.qr.textContent = 'สร้าง QR ไม่สำเร็จ'; }
                    }
                    if (o.expires) {
                        var end = new Date(o.expires).getTime();
                        var tick = function () { self.left = Math.max(0, Math.round((end - Date.now()) / 1000)); };
                        tick(); this.timer = setInterval(tick, 1000);
                    }
                    this.poll = setInterval(function () { self.check(); }, 5000);
                },
                check() {
                    if (this.busy || document.hidden || this.state !== 'wait') return;
                    var self = this; this.busy = true;
                    fetch(o.checkUrl, { headers: { 'Accept': 'application/json' } })
                        .then(function (r) { return r.json(); })
                        .then(function (j) {
                            if (! j.success || ! j.data) return;
                            if (j.data.is_completed) {
                                self.state = 'done'; self.stop();
                                window.showNotification('ชำระเงินสำเร็จ เงินเข้ากระเป๋าแล้ว', 'success');
                                setTimeout(function () { window.location.href = o.doneUrl; }, 2000);
                            } else if (j.data.is_expired) {
                                self.state = 'expired'; self.stop();
                            }
                        })
                        .catch(function () {})
                        .finally(function () { self.busy = false; });
                },
                stop() { clearInterval(this.poll); clearInterval(this.timer); this.poll = this.timer = null; },
                destroy() { this.stop(); },
            };
        });
    });
</script>
@endpush
@endsection
