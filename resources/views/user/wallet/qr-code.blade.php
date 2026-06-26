{{--
    QR Code สำหรับรับเงิน - Wallet QR Code Display (Theme V4 นวลทองคำ)
    แสดง QR Code เพื่อให้ผู้อื่นสแกนโอนเงินมาได้
--}}

@extends('layouts.user-v4')

@section('title', 'QR Code รับเงิน')

@php
    // ── ขั้นตอนการใช้งาน (สีตามลำดับ semantic hex) ──────────────
    $qrSteps = [
        ['1', '#5689b8', 'แสดง QR Code', 'ให้ผู้โอนเงินสแกน QR Code นี้'],
        ['2', '#e0a52e', 'ผู้โอนกรอกจำนวนเงิน', 'หรือระบุจำนวนเงินไว้ใน QR'],
        ['3', '#5aa07e', 'รับเงินทันที', 'เงินจะเข้ากระเป๋าเมื่อยืนยัน'],
    ];
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;" x-data="qrCodeManager()">

    {{-- ── หัวข้อ (Hero) ─────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:20px 24px; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
            <div style="display:flex; flex-wrap:wrap; align-items:center; gap:14px;">
                @if(\Illuminate\Support\Facades\Route::has('user.wallet.index'))
                    <a href="{{ route('user.wallet.index') }}" class="tp-icon-btn" title="กลับ"><i class="fas fa-arrow-left"></i></a>
                @endif
                <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:24px;"><i class="fas fa-qrcode" style="color:#fff;"></i></span>
                <div style="flex:1; min-width:200px;">
                    <h1 style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:0;">QR Code รับเงิน</h1>
                    <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">แสดง QR Code ให้คนอื่นสแกนเพื่อโอนเงินให้คุณ</div>
                </div>
            </div>

            {{-- ยอดเงินคงเหลือ --}}
            <div style="margin-top:16px; padding:18px 20px; border-radius:18px; box-shadow:var(--inset);">
                <div style="font-size:12.5px; color:var(--ink2);">ยอดเงินคงเหลือ</div>
                <div class="tp-num" style="font-size:clamp(30px,6vw,46px); font-weight:800; line-height:1.1; margin-top:4px; color:var(--deep1);">฿{{ number_format($wallet->balance, 2) }}</div>
            </div>
        </div>
    </div>

    {{-- ── QR Code Display ───────────────────────────────────── --}}
    <div class="tp-card" style="padding:24px;">
        <div style="text-align:center;">
            {{-- ข้อมูลผู้ใช้ --}}
            <div style="margin-bottom:20px;">
                <div class="tp-tile" style="width:74px; height:74px; border-radius:50%; font-size:30px; font-weight:800; margin:0 auto 12px; color:#fff;">
                    {{ mb_substr(auth()->user()->name, 0, 1) }}
                </div>
                <h2 style="font-size:18px; font-weight:800; margin:0;">{{ auth()->user()->name }}</h2>
                <div class="tp-num" style="font-size:12px; color:var(--ink2); margin-top:4px; word-break:break-all;">{{ $wallet->wallet_address }}</div>
            </div>

            {{-- กล่อง QR Code (พื้นขาวคงที่เพื่อให้สแกนได้เสมอ) --}}
            <div style="display:inline-block; background:#fff; padding:20px; border-radius:18px; box-shadow:var(--inset); border:4px solid color-mix(in srgb, var(--accent1) 30%, transparent);">
                <div id="qrcode" style="margin:0 auto;"></div>
            </div>

            {{-- ระบุจำนวนเงิน (ไม่บังคับ) --}}
            <div style="margin-top:20px; max-width:280px; margin-left:auto; margin-right:auto; text-align:left;">
                <label style="display:block; font-size:11.5px; font-weight:600; color:var(--ink2); margin-bottom:6px;">
                    ระบุจำนวนเงินที่ต้องการรับ (ไม่บังคับ)
                </label>
                <div style="position:relative;">
                    <span style="position:absolute; left:14px; top:50%; transform:translateY(-50%); color:var(--ink2); font-weight:600;">฿</span>
                    <input type="number"
                           x-model="requestedAmount"
                           @input="updateQrCode()"
                           min="0"
                           step="0.01"
                           class="tp-input"
                           style="padding-left:30px;"
                           placeholder="0.00">
                </div>
            </div>

            {{-- ปุ่มดำเนินการ --}}
            <div style="margin-top:20px; display:flex; flex-wrap:wrap; gap:10px; justify-content:center;">
                <button type="button" @click="downloadQr()" class="tp-btn tp-btn-primary">
                    <i class="fas fa-download" style="margin-right:6px;"></i>ดาวน์โหลด QR
                </button>
                <button type="button" @click="shareQr()" class="tp-btn">
                    <i class="fas fa-share-alt" style="margin-right:6px;"></i>แชร์ QR Code
                </button>
                <button type="button" @click="copyWalletAddress()" class="tp-btn">
                    <i class="fas fa-copy" style="margin-right:6px;"></i>คัดลอก Address
                </button>
            </div>
        </div>
    </div>

    {{-- ── ข้อมูลค่าธรรมเนียม ─────────────────────────────────── --}}
    <div class="tp-card" style="padding:18px; box-shadow:var(--inset-sm); border-left:4px solid #e0a52e;">
        <div class="tp-section-h" style="margin-bottom:12px; display:flex; align-items:center; gap:8px;">
            <i class="fas fa-info-circle" style="color:#e0a52e;"></i>ข้อมูลค่าธรรมเนียม
        </div>
        <div style="display:flex; flex-direction:column; gap:8px; font-size:13px; color:var(--ink);">
            @if($transferFee > 0)
                <div style="display:flex; align-items:center; gap:8px;">
                    <i class="fas fa-check-circle" style="color:#5aa07e;"></i>
                    <span>ค่าธรรมเนียมการโอน:
                        @if($transferFeeType === 'percentage')
                            <span class="tp-num">{{ number_format($transferFee, 2) }}%</span> ต่อรายการ
                        @else
                            <span class="tp-num">฿{{ number_format($transferFee, 2) }}</span> ต่อรายการ
                        @endif
                    </span>
                </div>
            @else
                <div style="display:flex; align-items:center; gap:8px;">
                    <i class="fas fa-check-circle" style="color:#5aa07e;"></i>
                    <span>ไม่มีค่าธรรมเนียมการโอน (ฟรี!)</span>
                </div>
            @endif
            <div style="display:flex; align-items:center; gap:8px;">
                <i class="fas fa-check-circle" style="color:#5aa07e;"></i>
                <span>ยอดโอนขั้นต่ำ: <span class="tp-num">฿{{ number_format($minTransferAmount, 2) }}</span></span>
            </div>
            <div style="display:flex; align-items:center; gap:8px;">
                <i class="fas fa-check-circle" style="color:#5aa07e;"></i>
                <span>รับเงินทันทีหลังผู้โอนยืนยันรายการ</span>
            </div>
        </div>
    </div>

    {{-- ── วิธีใช้งาน ─────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:18px;">
        <div class="tp-section-h" style="margin-bottom:14px; display:flex; align-items:center; gap:8px;">
            <i class="fas fa-lightbulb" style="color:#e0a52e;"></i>วิธีใช้งาน
        </div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:12px;">
            @foreach($qrSteps as [$no, $color, $title, $desc])
                <div style="display:flex; align-items:flex-start; gap:11px; padding:14px; border-radius:14px; box-shadow:var(--inset-sm);">
                    <span class="tp-tile" style="width:32px; height:32px; border-radius:50%; font-size:15px; font-weight:800; flex-shrink:0; color:#fff; background:{{ $color }};">{{ $no }}</span>
                    <div style="min-width:0;">
                        <div style="font-size:13px; font-weight:700;">{{ $title }}</div>
                        <div style="font-size:12px; color:var(--ink2); margin-top:2px;">{{ $desc }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

@push('scripts')
{{-- QRCode.js Library (qrcodejs - เหมือนกับหน้า recruit ที่ใช้งานได้) --}}
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
    function qrCodeManager() {
        return {
            requestedAmount: {{ $requestedAmount ?? 'null' }},
            walletAddress: @json($wallet->wallet_address),
            userName: @json(auth()->user()->name),
            qrInstance: null,

            init() {
                this.generateQrCode();
            },

            generateQrCode() {
                const qrData = {
                    type: 'tpwallet_transfer',
                    address: this.walletAddress,
                    name: this.userName,
                    amount: this.requestedAmount || null,
                    timestamp: Date.now()
                };

                const container = document.getElementById('qrcode');
                container.innerHTML = '';

                // ใช้ qrcodejs library (API: new QRCode)
                this.qrInstance = new QRCode(container, {
                    text: JSON.stringify(qrData),
                    width: 250,
                    height: 250,
                    colorDark: '#1e1b4b',
                    colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.H
                });
            },

            updateQrCode() {
                this.generateQrCode();
            },

            getQrCanvas() {
                // qrcodejs สร้าง canvas หรือ img element ภายใน container
                const container = document.getElementById('qrcode');
                return container.querySelector('canvas') || container.querySelector('img');
            },

            downloadQr() {
                const qrElement = this.getQrCanvas();
                if (!qrElement) {
                    this.notify('ไม่พบ QR Code', 'error');
                    return;
                }

                const link = document.createElement('a');
                link.download = `wallet-qr-${this.walletAddress}.png`;

                if (qrElement.tagName === 'CANVAS') {
                    link.href = qrElement.toDataURL('image/png');
                } else {
                    // ถ้าเป็น img element
                    link.href = qrElement.src;
                }
                link.click();
                this.notify('ดาวน์โหลด QR Code สำเร็จ!', 'success');
            },

            async shareQr() {
                const qrElement = this.getQrCanvas();
                if (!qrElement) {
                    this.notify('ไม่พบ QR Code', 'error');
                    return;
                }

                try {
                    let blob;
                    if (qrElement.tagName === 'CANVAS') {
                        blob = await new Promise(resolve =>
                            qrElement.toBlob(resolve, 'image/png')
                        );
                    } else {
                        // ถ้าเป็น img element - fetch และ convert
                        const response = await fetch(qrElement.src);
                        blob = await response.blob();
                    }

                    if (navigator.share && navigator.canShare) {
                        const file = new File([blob], 'wallet-qr.png', { type: 'image/png' });
                        const shareData = {
                            title: 'โอนเงินให้ ' + this.userName,
                            text: `สแกน QR Code นี้เพื่อโอนเงินให้ ${this.userName}\nWallet: ${this.walletAddress}`,
                            files: [file]
                        };

                        if (navigator.canShare(shareData)) {
                            await navigator.share(shareData);
                            return;
                        }
                    }

                    // Fallback: copy to clipboard
                    await this.copyWalletAddress();
                } catch (err) {
                    console.error('Share failed:', err);
                    this.copyWalletAddress();
                }
            },

            async copyWalletAddress() {
                try {
                    await navigator.clipboard.writeText(this.walletAddress);
                    this.notify('คัดลอก Wallet Address สำเร็จ!', 'success');
                } catch (err) {
                    // Fallback for older browsers
                    const textArea = document.createElement('textarea');
                    textArea.value = this.walletAddress;
                    document.body.appendChild(textArea);
                    textArea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textArea);
                    this.notify('คัดลอก Wallet Address สำเร็จ!', 'success');
                }
            },

            notify(message, type = 'info') {
                // ใช้ระบบ toast กลางของ Theme V4
                if (window.showNotification) {
                    window.showNotification(message, type);
                } else {
                    console.log('[' + type + '] ' + message);
                }
            }
        }
    }
</script>
@endpush
@endsection
