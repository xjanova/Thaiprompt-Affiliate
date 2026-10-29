{{--
    สแกนจ่าย — สแกน QR ด้วยกล้อง / อัพโหลดรูป / กรอก Wallet Address เอง แล้วโอนทันที (ธีมโนวาเต็ม)
    endpoint เดิม: qr-transfer.lookup (ค้นผู้รับ) · qr-transfer.process (โอน, มี idempotency + throttle)
--}}
@extends('layouts.user-nova')

@section('title', 'สแกนจ่าย')

@php
    $nvR = fn ($name) => \Illuminate\Support\Facades\Route::has($name);
    [$balInt, $balDec] = explode('.', number_format((float) $wallet->balance, 2));
    $minAmount = max(0.01, (float) $feePolicy['min_amount']);
    $hasPin = $wallet->hasPIN();
@endphp

@section('content')

<div class="nvm-stack" x-data="nvmQrPay({
        policy: {{ \Illuminate\Support\Js::from($feePolicy) }},
        balance: {{ (float) $wallet->balance }},
        min: {{ $minAmount }},
        hasPin: @js($hasPin),
        lookupUrl: @js(route('user.wallet.qr-transfer.lookup')),
        payUrl: @js(route('user.wallet.qr-transfer.process')),
    })" @pagehide.window="stopCamera()">

<section class="nvm-ph" aria-labelledby="nvm-qp-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            @if($nvR('user.wallet.index'))
                <a class="nvm-ph__back" href="{{ route('user.wallet.index') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>กระเป๋าเงิน</a>
            @endif
            <div class="nvm-eyebrow nvm-latin">Scan &amp; pay</div>
            <h1 id="nvm-qp-h">สแกนจ่าย</h1>
            <p class="nvm-ph__lead">สแกน QR รับเงินของสมาชิก แล้วโอนเข้ากระเป๋าเขาทันที</p>
            <div class="nvm-bal">
                <small>ยอดที่ใช้จ่ายได้</small>
                <b class="nvm-foil nvm-num">฿<i x-text="balParts[0]">{{ $balInt }}</i><span x-text="'.' + balParts[1]">.{{ $balDec }}</span></b>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/wallet/phone.webp') }}" alt="" width="200" height="200">
    </div>
</section>

<div class="nvm-split">
    <div class="nvm-stack">
        {{-- ── ขั้น 1: หาผู้รับ ── --}}
        <section class="nvm-card nvm-box" x-show="! who" aria-labelledby="nvm-qps-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Step 1</small><h2 id="nvm-qps-h">หาผู้รับเงิน</h2></div></div>
            <div class="nvm-tabs" role="tablist">
                <button type="button" role="tab" :class="{ 'is-on': mode === 'scan' }" :aria-selected="mode === 'scan'" @click="setMode('scan')"><svg class="nvm-ico"><use href="#nvm-i-qr"/></svg>สแกนกล้อง</button>
                <button type="button" role="tab" :class="{ 'is-on': mode === 'upload' }" :aria-selected="mode === 'upload'" @click="setMode('upload')"><svg class="nvm-ico"><use href="#nvm-i-plus"/></svg>อัพโหลดรูป</button>
                <button type="button" role="tab" :class="{ 'is-on': mode === 'manual' }" :aria-selected="mode === 'manual'" @click="setMode('manual')"><svg class="nvm-ico"><use href="#nvm-i-id"/></svg>กรอกเอง</button>
            </div>

            <div class="nvm-gap-t" x-show="mode === 'scan'">
                <div class="nvm-scan">
                    <video x-ref="video" playsinline muted></video>
                    <div class="nvm-scan__frame" aria-hidden="true"><i></i></div>
                    <div class="nvm-scan__idle" x-show="! cameraOn">
                        <img src="{{ asset('images/nova/wallet/qr.webp') }}" alt="" width="96" height="96" loading="lazy">
                        <span>กดเปิดกล้อง แล้วส่อง QR รับเงินของผู้รับ</span>
                    </div>
                </div>
                <div class="nvm-filter__btns nvm-gap-t">
                    <button type="button" class="nvm-btn nvm-btn--gold" x-show="! cameraOn" @click="startCamera()"><svg class="nvm-ico"><use href="#nvm-i-qr"/></svg>เปิดกล้อง</button>
                    <button type="button" class="nvm-btn nvm-btn--line" x-show="cameraOn" x-cloak @click="stopCamera()"><svg class="nvm-ico"><use href="#nvm-i-x"/></svg>ปิดกล้อง</button>
                </div>
            </div>

            <div class="nvm-gap-t" x-show="mode === 'upload'" x-cloak>
                <label class="nvm-drop-zone">
                    <img src="{{ asset('images/nova/wallet/qr.webp') }}" alt="" width="72" height="72" loading="lazy">
                    <b>เลือกรูป QR Code</b>
                    <small>ภาพหน้าจอหรือรูปถ่าย QR รับเงิน (JPG, PNG)</small>
                    <input type="file" accept="image/*" class="nvm-sr" @change="readImage($event)">
                </label>
            </div>

            <form class="nvm-form nvm-gap-t" x-show="mode === 'manual'" x-cloak @submit.prevent="lookup(manual)">
                <div class="nvm-field">
                    <label class="nvm-label" for="qp-addr">Wallet Address ผู้รับ</label>
                    <input id="qp-addr" type="text" x-model="manual" maxlength="40" autocomplete="off" autocapitalize="off" spellcheck="false" class="nvm-input nvm-num" placeholder="TPW…">
                </div>
                <button type="submit" class="nvm-btn nvm-btn--gold" :disabled="manual.trim().length < 10 || finding"><svg class="nvm-ico"><use href="#nvm-i-search"/></svg><span x-text="finding ? 'กำลังค้นหา…' : 'ค้นหาผู้รับ'">ค้นหาผู้รับ</span></button>
            </form>
        </section>

        {{-- ── ขั้น 2: ใส่ยอด + ยืนยัน ── --}}
        <section class="nvm-card nvm-box" x-show="who" x-cloak aria-labelledby="nvm-qpf-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Step 2</small><h2 id="nvm-qpf-h">ยืนยันการจ่าย</h2></div></div>
            <form class="nvm-form" @submit.prevent="pay()">
                <div class="nvm-who">
                    <span class="nvm-avatar" x-text="who ? who.user_name.replace(/^[เแโใไ]/, '').charAt(0) : ''"></span>
                    <span><small>จ่ายให้</small><b x-text="who && who.user_name"></b><small class="nvm-num" x-text="who && who.wallet_address"></small></span>
                    <svg class="nvm-ico"><use href="#nvm-i-check"/></svg>
                </div>

                <div class="nvm-field">
                    <label class="nvm-label" for="qp-amount">จำนวนเงิน (บาท) <em>*</em></label>
                    <div class="nvm-amount"><span>฿</span><input id="qp-amount" type="number" step="0.01" min="{{ $minAmount }}" inputmode="decimal" class="nvm-input" placeholder="0.00" x-model="amount" x-ref="amount"></div>
                    <span class="nvm-help">ขั้นต่ำ ฿{{ number_format($minAmount, 2) }}</span>
                    <span class="nvm-err" x-show="amt > 0 && amt < min" x-cloak>ยอดจ่ายขั้นต่ำ ฿{{ number_format($minAmount, 2) }}</span>
                    <span class="nvm-err" x-show="total > balance" x-cloak>ยอดเงินไม่พอ (ต้องใช้ ฿<span x-text="fmt(total)"></span> รวมค่าธรรมเนียม)</span>
                </div>

                <div class="nvm-mini" x-show="amt > 0" x-cloak>
                    <dl class="nvm-kv">
                        <dt>ผู้รับได้</dt><dd class="nvm-num" x-text="'฿' + fmt(amt)"></dd>
                        <dt>ค่าธรรมเนียม</dt><dd class="nvm-num" x-text="fee > 0 ? '฿' + fmt(fee) : 'ฟรี'"></dd>
                        <dt class="nvm-kv__total">หักจากกระเป๋า</dt><dd class="nvm-num nvm-kv__total" x-text="'฿' + fmt(total)"></dd>
                    </dl>
                </div>

                <div class="nvm-field">
                    <label class="nvm-label" for="qp-note">หมายเหตุ (ถ้ามี)</label>
                    <input id="qp-note" type="text" maxlength="255" class="nvm-input" x-model="note" placeholder="เช่น ค่าอาหาร, โอนคืน">
                </div>

                <template x-if="hasPin">
                    <div class="nvm-field">
                        <label class="nvm-label" for="qp-pin">PIN กระเป๋าเงิน (6 หลัก) <em>*</em></label>
                        <input id="qp-pin" type="password" maxlength="6" inputmode="numeric" autocomplete="off" class="nvm-input nvm-pin" x-model="pin" placeholder="••••••">
                    </div>
                </template>

                <div class="nvm-filter__btns">
                    <button type="button" class="nvm-btn nvm-btn--line" @click="reset()" :disabled="busy">เปลี่ยนผู้รับ</button>
                    <button type="submit" class="nvm-btn nvm-btn--gold" :disabled="! ok || busy"><svg class="nvm-ico"><use href="#nvm-i-send"/></svg><span x-text="busy ? 'กำลังโอน…' : 'ยืนยันจ่ายเงิน'">ยืนยันจ่ายเงิน</span></button>
                </div>
            </form>
        </section>
    </div>

    <aside class="nvm-card nvm-box" aria-labelledby="nvm-qph-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">How it works</small><h2 id="nvm-qph-h">วิธีสแกนจ่าย</h2></div></div>
        <ol class="nvm-steps">
            <li>ให้ผู้รับเปิดหน้า "QR รับเงิน" ในกระเป๋าเงินของเขา</li>
            <li>สแกน QR — ระบบแสดงชื่อผู้รับให้ตรวจก่อน</li>
            <li>ใส่ยอด (หรือใช้ยอดที่ผู้รับตั้งไว้ใน QR) แล้วกดยืนยัน</li>
            <li>เงินเข้ากระเป๋าผู้รับทันที ยกเลิกไม่ได้</li>
        </ol>
        @if($nvR('user.wallet.qr-code'))
            <a class="nvm-btn nvm-btn--line nvm-btn--sm nvm-gap-t" href="{{ route('user.wallet.qr-code') }}"><svg class="nvm-ico"><use href="#nvm-i-qr"/></svg>QR รับเงินของฉัน</a>
        @endif
    </aside>
</div>

{{-- ── โอนสำเร็จ ── --}}
<div class="nvm-modal" x-show="done" x-cloak x-transition.opacity role="dialog" aria-modal="true" aria-labelledby="nvm-qpd-h">
    <div class="nvm-card nvm-modal__box">
        <img src="{{ asset('images/nova/wallet/deposit.webp') }}" alt="" width="110" height="110">
        <h2 id="nvm-qpd-h">จ่ายเงินสำเร็จ</h2>
        <p x-text="done ? 'โอน ฿' + fmt(done.amount) + ' ให้ ' + done.to_name + ' แล้ว' : ''"></p>
        <dl class="nvm-kv nvm-mini">
            <dt>เลขอ้างอิง</dt><dd class="nvm-num" x-text="done && done.reference"></dd>
            <dt>ค่าธรรมเนียม</dt><dd class="nvm-num" x-text="done ? '฿' + fmt(done.fee || 0) : ''"></dd>
            <dt class="nvm-kv__total">หักจากกระเป๋า</dt><dd class="nvm-num nvm-kv__total" x-text="done ? '฿' + fmt(done.total || done.amount) : ''"></dd>
        </dl>
        <div class="nvm-filter__btns">
            <button type="button" class="nvm-btn nvm-btn--line" @click="done = null; reset()">จ่ายอีกรายการ</button>
            <a class="nvm-btn nvm-btn--gold" href="{{ route('user.wallet.index') }}">กลับกระเป๋าเงิน</a>
        </div>
    </div>
</div>

</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
<script>
    // สแกนจ่าย: อ่าน QR (กล้อง/รูป/พิมพ์เอง) → ค้นชื่อผู้รับจากเซิร์ฟเวอร์ (ไม่เชื่อชื่อใน QR) → โอน
    document.addEventListener('alpine:init', function () {
        window.Alpine.data('nvmQrPay', function (o) {
            return {
                policy: o.policy, balance: o.balance, min: o.min, hasPin: o.hasPin,
                mode: 'scan', cameraOn: false, stream: null, timer: null,
                manual: '', finding: false, who: null,
                amount: '', note: '', pin: '', busy: false, done: null,
                // คีย์กันโอนซ้ำ — ใหม่ทุกครั้งที่เลือกผู้รับ (เซิร์ฟเวอร์จำคำตอบตามคีย์นี้ 24 ชม.)
                idemKey: '',
                get amt() { var a = parseFloat(this.amount); return isNaN(a) || a <= 0 ? 0 : a; },
                get fee() {
                    if (this.amt <= 0) return 0;
                    var f = this.policy.fee_type === 'percentage' ? this.amt * this.policy.fee_amount / 100 : this.policy.fee_amount;
                    f = Math.max(this.policy.fee_min, Math.min(this.policy.fee_max, f));
                    return Math.round(f * 100) / 100;
                },
                // ยอดบนแบนเนอร์ลดตามจริงหลังจ่ายสำเร็จ (ไม่ต้องรีเฟรช)
                get balParts() { return this.fmt(this.balance).split('.'); },
                get total() { return Math.round((this.amt + this.fee) * 100) / 100; },
                get ok() { return this.who && this.amt >= this.min && this.total <= this.balance && (! this.hasPin || /^\d{6}$/.test(this.pin)); },
                setMode(m) { if (m !== 'scan') this.stopCamera(); this.mode = m; },
                async startCamera() {
                    if (! navigator.mediaDevices || ! navigator.mediaDevices.getUserMedia) { window.showNotification('เบราว์เซอร์นี้เปิดกล้องไม่ได้ — ใช้อัพโหลดรูปแทน', 'error'); return; }
                    try {
                        this.stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                        var v = this.$refs.video, self = this;
                        v.srcObject = this.stream; await v.play();
                        this.cameraOn = true;
                        var c = document.createElement('canvas'), ctx = c.getContext('2d', { willReadFrequently: true });
                        this.timer = setInterval(function () {
                            if (v.readyState !== v.HAVE_ENOUGH_DATA || typeof jsQR === 'undefined') return;
                            c.width = v.videoWidth; c.height = v.videoHeight;
                            ctx.drawImage(v, 0, 0, c.width, c.height);
                            var code = jsQR(ctx.getImageData(0, 0, c.width, c.height).data, c.width, c.height);
                            if (code) self.read(code.data);
                        }, 250);
                    } catch (e) {
                        this.stopCamera();
                        window.showNotification('เปิดกล้องไม่ได้ — อนุญาตการใช้กล้อง หรือใช้อัพโหลดรูปแทน', 'error');
                    }
                },
                stopCamera() {
                    if (this.timer) { clearInterval(this.timer); this.timer = null; }
                    if (this.stream) { this.stream.getTracks().forEach(function (t) { t.stop(); }); this.stream = null; }
                    if (this.$refs.video) this.$refs.video.srcObject = null;
                    this.cameraOn = false;
                },
                readImage(e) {
                    var file = e.target.files && e.target.files[0], self = this;
                    e.target.value = '';
                    if (! file) return;
                    var img = new Image(), url = URL.createObjectURL(file);
                    img.onload = function () {
                        // ย่อรูปใหญ่ก่อนถอดรหัส — รูปกล้องมือถือ 12MP ทำให้แท็บค้าง
                        var s = Math.min(1, 1200 / Math.max(img.width, img.height));
                        var c = document.createElement('canvas'); c.width = Math.round(img.width * s); c.height = Math.round(img.height * s);
                        var ctx = c.getContext('2d'); ctx.drawImage(img, 0, 0, c.width, c.height);
                        URL.revokeObjectURL(url);
                        var code = typeof jsQR !== 'undefined' && jsQR(ctx.getImageData(0, 0, c.width, c.height).data, c.width, c.height);
                        if (code) self.read(code.data); else window.showNotification('ไม่พบ QR Code ในรูป ลองรูปที่ชัดขึ้น', 'error');
                    };
                    img.onerror = function () { URL.revokeObjectURL(url); window.showNotification('เปิดรูปนี้ไม่ได้', 'error'); };
                    img.src = url;
                },
                read(data) {
                    this.stopCamera();
                    var addr = '', preset = null;
                    try { var j = JSON.parse(data); addr = j && j.address ? String(j.address) : ''; preset = j && j.amount; } catch (e) { addr = String(data || '').trim(); }
                    if (! /^[A-Za-z0-9]{10,40}$/.test(addr)) { window.showNotification('QR นี้ไม่ใช่ QR รับเงินของ Thai Prompt', 'error'); return; }
                    this.lookup(addr, preset);
                },
                lookup(addr, preset) {
                    addr = String(addr || '').trim();
                    if (addr.length < 10 || this.finding) return;
                    var self = this; this.finding = true;
                    fetch(o.lookupUrl, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }, body: JSON.stringify({ wallet_address: addr }) })
                        .then(function (r) { return r.json().catch(function () { return {}; }); })
                        .then(function (j) {
                            if (! j.success) { window.showNotification(j.message || 'ไม่พบ Wallet Address นี้', 'error'); return; }
                            self.who = j.data;
                            self.idemKey = (window.crypto && crypto.randomUUID) ? crypto.randomUUID() : String(Date.now()) + Math.random();
                            var p = parseFloat(preset);
                            if (p > 0) self.amount = String(Math.round(p * 100) / 100);
                            self.$nextTick(function () { self.$refs.amount && self.$refs.amount.focus(); });
                        })
                        .catch(function () { window.showNotification('เชื่อมต่อไม่ได้ ลองใหม่อีกครั้ง', 'error'); })
                        .finally(function () { self.finding = false; });
                },
                pay() {
                    if (! this.ok || this.busy) return;
                    var self = this; this.busy = true;
                    fetch(o.payUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Idempotency-Key': this.idemKey },
                        body: JSON.stringify({ wallet_address: this.who.wallet_address, amount: this.amt, pin: this.pin, description: this.note || 'สแกนจ่าย' }),
                    })
                        .then(function (r) { return r.json().catch(function () { return {}; }).then(function (j) { return { s: r.status, j: j }; }); })
                        .then(function (res) {
                            if (res.j.success) { self.done = res.j.data; self.balance = Math.max(0, self.balance - (res.j.data.total || res.j.data.amount)); return; }
                            // ต้องยืนยัน 2FA ก่อน → พาไปหน้ายืนยัน แล้วกลับมาหน้านี้
                            if (res.j.verify_url) { window.showNotification(res.j.message, 'info'); setTimeout(function () { window.location.href = res.j.verify_url; }, 900); return; }
                            var msg = res.j.message || (res.s === 429 ? 'ทำรายการถี่เกินไป รอสักครู่แล้วลองใหม่' : 'โอนเงินไม่สำเร็จ');
                            if (res.j.errors) msg = Object.values(res.j.errors)[0][0];
                            // โอนไม่ผ่าน → ใช้คีย์ใหม่ ให้ลองอีกครั้งได้จริง
                            self.idemKey = (window.crypto && crypto.randomUUID) ? crypto.randomUUID() : String(Date.now()) + Math.random();
                            self.pin = '';
                            window.showNotification(msg, 'error');
                        })
                        .catch(function () { window.showNotification('เชื่อมต่อไม่ได้ — ตรวจประวัติธุรกรรมก่อนลองใหม่', 'error'); })
                        .finally(function () { self.busy = false; });
                },
                reset() { this.who = null; this.amount = ''; this.note = ''; this.pin = ''; this.manual = ''; this.idemKey = ''; },
                fmt(n) { return (parseFloat(n) || 0).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
            };
        });
    });
</script>
@endpush
@endsection
