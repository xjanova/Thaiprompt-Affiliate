{{--
    QR รับเงิน — ให้สมาชิกคนอื่นสแกนแล้วโอนเข้ากระเป๋าเรา (ธีมโนวาเต็ม)
    ตั้งยอดที่ต้องการรับไว้ใน QR ได้ · ดาวน์โหลด/แชร์รูป QR · คัดลอก Wallet Address
--}}
@extends('layouts.user-nova')

@section('title', 'QR รับเงิน')

@php
    $nvR = fn ($name) => \Illuminate\Support\Facades\Route::has($name);
    $me = auth()->user();
    // ตัวอักษรแรกของชื่อ (ข้ามสระหน้าภาษาไทย เช่น "เ" ใน "เพื่อน")
    $initial = mb_substr(preg_replace('/^[เแโใไ]/u', '', (string) $me->name), 0, 1);
    $feeText = $feePolicy['fee_amount'] <= 0
        ? 'ฟรี ไม่มีค่าธรรมเนียม'
        : ($feePolicy['fee_type'] === 'percentage'
            ? number_format($feePolicy['fee_amount'], 2).'% ของยอดโอน'
            : '฿'.number_format($feePolicy['fee_amount'], 2).' ต่อรายการ');
@endphp

@section('content')

<div class="nvm-stack" x-data="nvmQrReceive({
        amount: @js($requestedAmount),
        address: @js($wallet->wallet_address),
        name: @js($me->name),
    })">

<section class="nvm-ph" aria-labelledby="nvm-qr-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            @if($nvR('user.wallet.index'))
                <a class="nvm-ph__back" href="{{ route('user.wallet.index') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>กระเป๋าเงิน</a>
            @endif
            <div class="nvm-eyebrow nvm-latin">Receive money</div>
            <h1 id="nvm-qr-h">QR รับเงิน</h1>
            <p class="nvm-ph__lead">ให้เพื่อนสมาชิกสแกน QR นี้ในหน้า "สแกนจ่าย" เงินจะเข้ากระเป๋าคุณทันที</p>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/wallet/qr.webp') }}" alt="" width="200" height="200">
    </div>
</section>

<div class="nvm-split">
    <section class="nvm-card nvm-box nvm-qr" aria-label="QR Code รับเงินของฉัน">
        <div class="nvm-qr__who">
            <span class="nvm-avatar">{{ $initial }}</span>
            <b>{{ $me->name }}</b>
            <small class="nvm-num">{{ $wallet->wallet_address }}</small>
        </div>

        <div class="nvm-qr__box">
            <div x-ref="qr" role="img" aria-label="QR Code รับเงิน"></div>
            <span class="nvm-qr__tag" x-show="amt > 0" x-cloak x-text="'รับ ฿' + fmt(amt)"></span>
        </div>

        <div class="nvm-field nvm-qr__amt">
            <label class="nvm-label" for="qr-amount">ตั้งยอดที่ต้องการรับ (ไม่บังคับ)</label>
            <div class="nvm-amount"><span>฿</span><input id="qr-amount" type="number" min="0" step="0.01" inputmode="decimal" class="nvm-input" placeholder="0.00" x-model="amount" @input.debounce.300ms="draw()"></div>
            <span class="nvm-help">เว้นว่างไว้ ผู้โอนจะกรอกยอดเอง</span>
        </div>

        <div class="nvm-filter__btns">
            <button type="button" class="nvm-btn nvm-btn--gold" @click="download()"><svg class="nvm-ico"><use href="#nvm-i-down"/></svg>บันทึกรูป QR</button>
            <button type="button" class="nvm-btn nvm-btn--line" @click="share()"><svg class="nvm-ico"><use href="#nvm-i-send"/></svg>แชร์</button>
            <button type="button" class="nvm-btn nvm-btn--line" @click="nvmCopy(address, 'คัดลอก Wallet Address แล้ว')"><svg class="nvm-ico"><use href="#nvm-i-copy"/></svg>คัดลอก Address</button>
        </div>
    </section>

    <aside class="nvm-stack">
        <section class="nvm-card nvm-box" aria-labelledby="nvm-qrf-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Fees</small><h2 id="nvm-qrf-h">ค่าธรรมเนียม</h2></div></div>
            <dl class="nvm-kv">
                <dt>ผู้รับ (คุณ)</dt><dd>ได้เต็มจำนวน</dd>
                <dt>ผู้โอนจ่าย</dt><dd>{{ $feeText }}</dd>
                @if($feePolicy['fee_amount'] > 0 && $feePolicy['fee_min'] > 0)
                    <dt>ขั้นต่ำค่าธรรมเนียม</dt><dd class="nvm-num">฿{{ number_format($feePolicy['fee_min'], 2) }}</dd>
                @endif
                @if($feePolicy['min_amount'] > 0)
                    <dt>ยอดโอนขั้นต่ำ</dt><dd class="nvm-num">฿{{ number_format($feePolicy['min_amount'], 2) }}</dd>
                @endif
            </dl>
        </section>

        <section class="nvm-card nvm-box" aria-labelledby="nvm-qrs-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">How it works</small><h2 id="nvm-qrs-h">วิธีรับเงิน</h2></div></div>
            <ol class="nvm-steps">
                <li>เปิดหน้านี้ (ตั้งยอดไว้ด้วยก็ได้) แล้วยื่นให้ผู้โอนสแกน หรือส่งรูป QR ให้</li>
                <li>ผู้โอนเปิด "สแกนจ่าย" ในกระเป๋าเงินของเขา แล้วตรวจชื่อคุณ</li>
                <li>ยืนยันแล้ว เงินเข้ากระเป๋าคุณทันที ดูได้ที่ประวัติธุรกรรม</li>
            </ol>
            <div class="nvm-filter__btns nvm-gap-t">
                @if($nvR('user.wallet.qr-transfer'))
                    <a class="nvm-btn nvm-btn--line nvm-btn--sm" href="{{ route('user.wallet.qr-transfer') }}"><svg class="nvm-ico"><use href="#nvm-i-qr"/></svg>สแกนจ่าย</a>
                @endif
                @if($nvR('user.wallet.transactions'))
                    <a class="nvm-btn nvm-btn--line nvm-btn--sm" href="{{ route('user.wallet.transactions') }}"><svg class="nvm-ico"><use href="#nvm-i-history"/></svg>ประวัติ</a>
                @endif
            </div>
        </section>
    </aside>
</div>

</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
    // QR รับเงิน: payload {type, address, amount} — หน้าสแกนจ่ายอ่าน address/amount แล้วค้นชื่อจากเซิร์ฟเวอร์
    document.addEventListener('alpine:init', function () {
        window.Alpine.data('nvmQrReceive', function (o) {
            return {
                amount: o.amount ? String(o.amount) : '', address: o.address, name: o.name,
                get amt() { var a = parseFloat(this.amount); return isNaN(a) || a <= 0 ? 0 : Math.round(a * 100) / 100; },
                init() { this.draw(); },
                draw() {
                    var box = this.$refs.qr;
                    if (typeof QRCode === 'undefined') { box.textContent = 'โหลดตัวสร้าง QR ไม่สำเร็จ — รีเฟรชหน้าอีกครั้ง'; return; }
                    box.innerHTML = '';
                    // 🐛 (2026-10-29) เดิมใส่ชื่อผู้ใช้ใน QR — qrcodejs นับความยาวอักษรไทย (3 ไบต์) ผิด
                    //    → "code length overflow" สมาชิกชื่อไทยได้กรอบขาวว่าง สแกนไม่ได้
                    //    หน้าสแกนจ่ายดึงชื่อจากเซิร์ฟเวอร์อยู่แล้ว จึงเหลือแค่ address + ยอด (ASCII ล้วน)
                    try {
                        new QRCode(box, {
                            text: JSON.stringify({ type: 'tpwallet_transfer', address: this.address, amount: this.amt || null }),
                            width: 464, height: 464, colorDark: '#0b1633', colorLight: '#ffffff', correctLevel: QRCode.CorrectLevel.M,
                        });
                    } catch (e) {
                        box.textContent = 'สร้าง QR ไม่สำเร็จ — ใช้ปุ่มคัดลอก Address แทน';
                    }
                },
                canvas() { return this.$refs.qr.querySelector('canvas'); },
                // วาด QR ลงการ์ดขาวพร้อมชื่อ/ยอด — รูปที่บันทึกไปส่งต่อแล้วผู้รับรู้ว่าของใคร
                card() {
                    var q = this.canvas(); if (! q) return null;
                    var w = 560, c = document.createElement('canvas'); c.width = w; c.height = 720;
                    var x = c.getContext('2d');
                    x.fillStyle = '#ffffff'; x.fillRect(0, 0, w, 720);
                    x.fillStyle = '#0b1633'; x.fillRect(0, 0, w, 92);
                    x.fillStyle = '#f5d27f'; x.font = 'bold 30px sans-serif'; x.textAlign = 'center';
                    x.fillText('Thai Prompt · QR รับเงิน', w / 2, 58);
                    x.drawImage(q, 48, 120, 464, 464);
                    x.fillStyle = '#0b1633'; x.font = 'bold 28px sans-serif';
                    x.fillText(this.name, w / 2, 632, w - 40);
                    x.fillStyle = '#5b6274'; x.font = '20px sans-serif';
                    x.fillText(this.amt ? 'ยอด ฿' + this.fmt(this.amt) + ' · ' + this.address : this.address, w / 2, 672, w - 40);
                    return c;
                },
                download() {
                    var c = this.card(); if (! c) { window.showNotification('ยังสร้าง QR ไม่เสร็จ ลองอีกครั้ง', 'error'); return; }
                    var a = document.createElement('a');
                    a.download = 'qr-receive-' + this.address + '.png'; a.href = c.toDataURL('image/png'); a.click();
                    window.showNotification('บันทึกรูป QR แล้ว', 'success');
                },
                share() {
                    var c = this.card(), self = this;
                    if (! c) { window.showNotification('ยังสร้าง QR ไม่เสร็จ ลองอีกครั้ง', 'error'); return; }
                    c.toBlob(function (blob) {
                        var data = { title: 'โอนเงินให้ ' + self.name, text: 'สแกน QR นี้ในหน้า "สแกนจ่าย" เพื่อโอนเงินให้ ' + self.name, files: [new File([blob], 'qr-receive.png', { type: 'image/png' })] };
                        if (navigator.canShare && navigator.canShare(data)) {
                            navigator.share(data).catch(function () {});
                        } else {
                            // แชร์ไฟล์ไม่ได้ (เดสก์ท็อปส่วนใหญ่) → คัดลอก address แทน
                            window.nvmCopy(self.address, 'เครื่องนี้แชร์รูปไม่ได้ — คัดลอก Wallet Address ให้แล้ว');
                        }
                    }, 'image/png');
                },
                fmt(n) { return (parseFloat(n) || 0).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
            };
        });
    });
</script>
@endpush
@endsection
