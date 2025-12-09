{{--
    QR Code สำหรับรับเงิน - Wallet QR Code Display
    แสดง QR Code เพื่อให้ผู้อื่นสแกนโอนเงินมาได้
--}}

@extends('layouts.user-arrow-x')

@section('title', 'QR Code รับเงิน')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6" x-data="qrCodeManager()">
    <!-- Header -->
    <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 rounded-2xl shadow-2xl p-8 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-40 h-40 bg-white opacity-10 rounded-full"></div>
        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-4">
                <a href="{{ route('user.wallet.index') }}" class="p-2 bg-white/20 hover:bg-white/30 rounded-lg transition">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h1 class="text-3xl md:text-4xl font-bold">
                    <i class="fas fa-qrcode mr-2"></i>
                    QR Code รับเงิน
                </h1>
            </div>
            <p class="text-indigo-100">แสดง QR Code ให้คนอื่นสแกนเพื่อโอนเงินให้คุณ</p>

            <!-- Balance Display -->
            <div class="mt-6 bg-white/20 rounded-xl p-4 backdrop-blur-sm">
                <p class="text-indigo-100 text-sm mb-1">ยอดเงินคงเหลือ</p>
                <p class="text-3xl font-bold">฿{{ number_format($wallet->balance, 2) }}</p>
            </div>
        </div>
    </div>

    <!-- QR Code Display -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8">
        <div class="text-center">
            <!-- User Info -->
            <div class="mb-6">
                <div class="w-20 h-20 mx-auto bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center text-white text-3xl font-bold mb-4">
                    {{ mb_substr(auth()->user()->name, 0, 1) }}
                </div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ auth()->user()->name }}</h2>
                <p class="text-gray-500 dark:text-gray-400 text-sm font-mono">{{ $wallet->wallet_address }}</p>
            </div>

            <!-- QR Code Container -->
            <div class="inline-block bg-white p-6 rounded-2xl shadow-lg border-4 border-indigo-100 dark:border-indigo-900">
                <div id="qrcode" class="mx-auto"></div>
            </div>

            <!-- Amount Input (Optional) -->
            <div class="mt-6 max-w-xs mx-auto">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    ระบุจำนวนเงินที่ต้องการรับ (ไม่บังคับ)
                </label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500">฿</span>
                    <input type="number"
                           x-model="requestedAmount"
                           @input="updateQrCode()"
                           min="0"
                           step="0.01"
                           class="w-full pl-10 pr-4 py-3 border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                           placeholder="0.00">
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="mt-6 flex flex-col sm:flex-row gap-3 justify-center">
                <button @click="downloadQr()"
                        class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-medium transition flex items-center justify-center gap-2">
                    <i class="fas fa-download"></i>
                    ดาวน์โหลด QR
                </button>
                <button @click="shareQr()"
                        class="px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-xl font-medium transition flex items-center justify-center gap-2">
                    <i class="fas fa-share-alt"></i>
                    แชร์ QR Code
                </button>
                <button @click="copyWalletAddress()"
                        class="px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-xl font-medium transition flex items-center justify-center gap-2">
                    <i class="fas fa-copy"></i>
                    คัดลอก Address
                </button>
            </div>
        </div>
    </div>

    <!-- Fee Information -->
    <div class="bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/30 dark:to-orange-900/30 border border-amber-200 dark:border-amber-700 rounded-xl p-6">
        <h3 class="font-bold text-amber-800 dark:text-amber-200 mb-3 flex items-center gap-2">
            <i class="fas fa-info-circle"></i>
            ข้อมูลค่าธรรมเนียม
        </h3>
        <div class="space-y-2 text-sm text-amber-700 dark:text-amber-300">
            @if($transferFee > 0)
                <p>
                    <i class="fas fa-check-circle text-green-500 mr-2"></i>
                    ค่าธรรมเนียมการโอน:
                    @if($transferFeeType === 'percentage')
                        {{ number_format($transferFee, 2) }}% ต่อรายการ
                    @else
                        ฿{{ number_format($transferFee, 2) }} ต่อรายการ
                    @endif
                </p>
            @else
                <p>
                    <i class="fas fa-check-circle text-green-500 mr-2"></i>
                    ไม่มีค่าธรรมเนียมการโอน (ฟรี!)
                </p>
            @endif
            <p>
                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                ยอดโอนขั้นต่ำ: ฿{{ number_format($minTransferAmount, 2) }}
            </p>
            <p>
                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                รับเงินทันทีหลังผู้โอนยืนยันรายการ
            </p>
        </div>
    </div>

    <!-- How to Use -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
        <h3 class="font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <i class="fas fa-lightbulb text-yellow-500"></i>
            วิธีใช้งาน
        </h3>
        <div class="grid gap-4 md:grid-cols-3">
            <div class="flex items-start gap-3 p-4 bg-gray-50 dark:bg-gray-700 rounded-xl">
                <div class="w-8 h-8 bg-indigo-600 text-white rounded-full flex items-center justify-center font-bold shrink-0">1</div>
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">แสดง QR Code</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">ให้ผู้โอนเงินสแกน QR Code นี้</p>
                </div>
            </div>
            <div class="flex items-start gap-3 p-4 bg-gray-50 dark:bg-gray-700 rounded-xl">
                <div class="w-8 h-8 bg-purple-600 text-white rounded-full flex items-center justify-center font-bold shrink-0">2</div>
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">ผู้โอนกรอกจำนวนเงิน</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">หรือระบุจำนวนเงินไว้ใน QR</p>
                </div>
            </div>
            <div class="flex items-start gap-3 p-4 bg-gray-50 dark:bg-gray-700 rounded-xl">
                <div class="w-8 h-8 bg-green-600 text-white rounded-full flex items-center justify-center font-bold shrink-0">3</div>
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">รับเงินทันที</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">เงินจะเข้ากระเป๋าเมื่อยืนยัน</p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<!-- QRCode.js Library (qrcodejs - เหมือนกับหน้า recruit ที่ใช้งานได้) -->
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
    function qrCodeManager() {
        return {
            requestedAmount: {{ $requestedAmount ?? 'null' }},
            walletAddress: '{{ $wallet->wallet_address }}',
            userName: '{{ auth()->user()->name }}',
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
                    this.showNotification('ไม่พบ QR Code', 'error');
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
                this.showNotification('ดาวน์โหลด QR Code สำเร็จ!', 'success');
            },

            async shareQr() {
                const qrElement = this.getQrCanvas();
                if (!qrElement) {
                    this.showNotification('ไม่พบ QR Code', 'error');
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
                    this.showNotification('คัดลอก Wallet Address สำเร็จ!', 'success');
                } catch (err) {
                    // Fallback for older browsers
                    const textArea = document.createElement('textarea');
                    textArea.value = this.walletAddress;
                    document.body.appendChild(textArea);
                    textArea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textArea);
                    this.showNotification('คัดลอก Wallet Address สำเร็จ!', 'success');
                }
            },

            showNotification(message, type = 'info') {
                // Simple toast notification
                const toast = document.createElement('div');
                toast.className = `fixed bottom-20 left-1/2 -translate-x-1/2 px-6 py-3 rounded-xl shadow-lg text-white z-50 ${
                    type === 'success' ? 'bg-green-600' : type === 'error' ? 'bg-red-600' : 'bg-indigo-600'
                }`;
                toast.textContent = message;
                document.body.appendChild(toast);

                setTimeout(() => {
                    toast.classList.add('opacity-0', 'transition-opacity');
                    setTimeout(() => toast.remove(), 300);
                }, 2000);
            }
        }
    }
</script>
@endpush
@endsection
