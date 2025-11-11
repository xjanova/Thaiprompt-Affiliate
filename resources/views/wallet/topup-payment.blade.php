@extends('layouts.app')

@section('title', 'ชำระเงิน')

@push('styles')
<style>
    @keyframes pulse-ring {
        0% { transform: scale(0.8); opacity: 1; }
        50% { transform: scale(1.2); opacity: 0.3; }
        100% { transform: scale(0.8); opacity: 1; }
    }
    @keyframes scan {
        0%, 100% { transform: translateY(-100%); }
        50% { transform: translateY(100%); }
    }
    .pulse-ring {
        animation: pulse-ring 2s ease-in-out infinite;
    }
    .scan-line {
        animation: scan 2s linear infinite;
    }
    @keyframes gradient-shift {
        0%, 100% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
    }
    .gradient-animate {
        background-size: 200% 200%;
        animation: gradient-shift 3s ease infinite;
    }
</style>
@endpush

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 py-8 px-4">
    <div class="max-w-4xl mx-auto">

        <!-- Progress Steps -->
        <div class="mb-8">
            <div class="flex items-center justify-center space-x-4">
                <!-- Step 1: Completed -->
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center text-white">
                        <i class="fas fa-check"></i>
                    </div>
                    <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">เลือกจำนวน</span>
                </div>

                <div class="w-16 h-1 bg-green-500 rounded"></div>

                <!-- Step 2: Current -->
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-gradient-to-r from-emerald-500 to-green-600 rounded-full flex items-center justify-center text-white pulse-ring">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <span class="ml-2 text-sm font-medium text-emerald-600 dark:text-emerald-400">ชำระเงิน</span>
                </div>

                <div class="w-16 h-1 bg-gray-300 dark:bg-gray-600 rounded"></div>

                <!-- Step 3: Pending -->
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center text-white">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <span class="ml-2 text-sm font-medium text-gray-400">เสร็จสิ้น</span>
                </div>
            </div>
        </div>

        <!-- Payment Card -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl overflow-hidden">

            <!-- Header -->
            <div class="bg-gradient-to-r from-emerald-500 via-green-600 to-emerald-700 gradient-animate p-8 text-white">
                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-20 h-20 bg-white/20 backdrop-blur-sm rounded-full mb-4">
                        <i class="fas fa-qrcode text-4xl"></i>
                    </div>
                    <h1 class="text-3xl font-bold mb-2">สแกน QR Code เพื่อชำระเงิน</h1>
                    <p class="text-emerald-100">กรุณาชำระเงินภายใน 15 นาที</p>
                </div>

                <!-- Amount Display -->
                <div class="mt-8 bg-white/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20">
                    <div class="text-center">
                        <p class="text-emerald-100 text-sm mb-2">จำนวนเงินที่ต้องชำระ</p>
                        <p class="text-5xl font-bold">฿{{ number_format($transaction->amount, 2) }}</p>
                        <p class="text-emerald-100 text-sm mt-2">หมายเลขอ้างอิง: {{ $transaction->reference_number }}</p>
                    </div>
                </div>
            </div>

            <!-- Payment Content -->
            <div class="p-8">

                @if($transaction->payment_method === 'promptpay')
                    <!-- PromptPay QR Code -->
                    <div class="text-center mb-8">
                        <div class="inline-block relative">
                            <!-- QR Code Container -->
                            <div class="bg-white p-6 rounded-2xl shadow-xl border-4 border-emerald-500 relative overflow-hidden">
                                <!-- Scan Line Effect -->
                                <div class="absolute inset-0 z-10 pointer-events-none">
                                    <div class="relative h-full overflow-hidden">
                                        <div class="absolute w-full h-1 bg-gradient-to-r from-transparent via-emerald-400 to-transparent scan-line opacity-50"></div>
                                    </div>
                                </div>

                                @if($transaction->qr_code_url)
                                    <img src="{{ $transaction->qr_code_url }}" alt="QR Code" class="w-64 h-64">
                                @else
                                    <div class="w-64 h-64 bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                        <div class="text-center">
                                            <i class="fas fa-spinner fa-spin text-4xl text-emerald-500 mb-2"></i>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">กำลังสร้าง QR Code...</p>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <!-- Corner Decorations -->
                            <div class="absolute -top-2 -left-2 w-8 h-8 border-t-4 border-l-4 border-emerald-500 rounded-tl-lg"></div>
                            <div class="absolute -top-2 -right-2 w-8 h-8 border-t-4 border-r-4 border-emerald-500 rounded-tr-lg"></div>
                            <div class="absolute -bottom-2 -left-2 w-8 h-8 border-b-4 border-l-4 border-emerald-500 rounded-bl-lg"></div>
                            <div class="absolute -bottom-2 -right-2 w-8 h-8 border-b-4 border-r-4 border-emerald-500 rounded-br-lg"></div>
                        </div>

                        <div class="mt-6 space-y-3">
                            <button onclick="window.print()" class="inline-flex items-center px-6 py-3 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-xl transition-all">
                                <i class="fas fa-download mr-2"></i>
                                บันทึก QR Code
                            </button>
                        </div>
                    </div>

                @elseif($transaction->payment_method === 'bank_transfer')
                    <!-- Bank Transfer Info -->
                    <div class="space-y-4">
                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-2xl p-6 border border-blue-200 dark:border-blue-800">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                                <i class="fas fa-university text-blue-600 mr-2"></i>
                                ข้อมูลบัญชีธนาคาร
                            </h3>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center p-3 bg-white dark:bg-gray-800 rounded-lg">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">ธนาคาร</span>
                                    <span class="font-semibold text-gray-900 dark:text-white">ธนาคารกสิกรไทย</span>
                                </div>
                                <div class="flex justify-between items-center p-3 bg-white dark:bg-gray-800 rounded-lg">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">เลขที่บัญชี</span>
                                    <span class="font-mono font-bold text-gray-900 dark:text-white">XXX-X-XXXXX-X</span>
                                </div>
                                <div class="flex justify-between items-center p-3 bg-white dark:bg-gray-800 rounded-lg">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">ชื่อบัญชี</span>
                                    <span class="font-semibold text-gray-900 dark:text-white">บริษัท XXX จำกัด</span>
                                </div>
                            </div>
                        </div>
                    </div>

                @elseif($transaction->payment_method === 'credit_card')
                    <!-- Credit Card Processing -->
                    <div class="text-center py-8">
                        <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-purple-500 to-pink-600 rounded-full mb-6">
                            <i class="fas fa-credit-card text-white text-4xl"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">กำลังดำเนินการชำระเงิน</h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-8">กรุณารอสักครู่...</p>
                        <div class="flex justify-center space-x-2">
                            <div class="w-3 h-3 bg-purple-600 rounded-full animate-bounce" style="animation-delay: 0s"></div>
                            <div class="w-3 h-3 bg-purple-600 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                            <div class="w-3 h-3 bg-purple-600 rounded-full animate-bounce" style="animation-delay: 0.4s"></div>
                        </div>
                    </div>
                @endif

                <!-- Instructions -->
                <div class="mt-8 space-y-4">
                    <div class="bg-gradient-to-r from-emerald-50 to-green-50 dark:from-emerald-900/20 dark:to-green-900/20 rounded-2xl p-6 border border-emerald-200 dark:border-emerald-800">
                        <h4 class="font-bold text-gray-900 dark:text-white mb-3 flex items-center">
                            <i class="fas fa-info-circle text-emerald-600 mr-2"></i>
                            วิธีการชำระเงิน
                        </h4>
                        <ol class="list-decimal list-inside space-y-2 text-sm text-gray-700 dark:text-gray-300">
                            <li>เปิดแอปพลิเคชันธนาคารของคุณ</li>
                            <li>เลือกเมนู "สแกน QR Code" หรือ "PromptPay"</li>
                            <li>สแกน QR Code ด้านบน</li>
                            <li>ตรวจสอบจำนวนเงินให้ถูกต้อง</li>
                            <li>ยืนยันการชำระเงิน</li>
                            <li>รอรับการยืนยัน (ประมาณ 5-10 วินาที)</li>
                        </ol>
                    </div>

                    <!-- Warning -->
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-2xl p-6 border border-yellow-200 dark:border-yellow-800">
                        <div class="flex items-start space-x-3">
                            <i class="fas fa-exclamation-triangle text-yellow-600 dark:text-yellow-400 mt-1"></i>
                            <div class="text-sm text-yellow-800 dark:text-yellow-300">
                                <p class="font-semibold mb-1">คำเตือน:</p>
                                <ul class="list-disc list-inside space-y-1">
                                    <li>กรุณาชำระเงินภายใน 15 นาที มิฉะนั้นรายการจะถูกยกเลิก</li>
                                    <li>อย่าปิดหน้าต่างนี้จนกว่าจะได้รับการยืนยัน</li>
                                    <li>เราจะตรวจสอบการชำระเงินอัตโนมัติ</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Status Check -->
                <div class="mt-8 text-center" x-data="{ checking: false }">
                    <button @click="checking = true; setTimeout(() => window.location.reload(), 2000)"
                        class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700 text-white font-bold rounded-xl shadow-lg hover:shadow-2xl transform hover:scale-105 transition-all">
                        <i class="fas fa-sync-alt mr-2" :class="{ 'fa-spin': checking }"></i>
                        <span x-text="checking ? 'กำลังตรวจสอบ...' : 'ตรวจสอบสถานะการชำระเงิน'"></span>
                    </button>
                </div>

                <!-- Timer -->
                <div class="mt-6 text-center" x-data="timer()" x-init="start()">
                    <div class="inline-flex items-center space-x-2 px-6 py-3 bg-gray-100 dark:bg-gray-700 rounded-full">
                        <i class="fas fa-clock text-emerald-600"></i>
                        <span class="font-mono text-lg font-bold text-gray-900 dark:text-white" x-text="timeLeft"></span>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">เวลาที่เหลือในการชำระเงิน</p>
                </div>

            </div>
        </div>

        <!-- Security Badge -->
        <div class="mt-8 flex items-center justify-center space-x-6 text-sm text-gray-600 dark:text-gray-400">
            <div class="flex items-center space-x-2">
                <i class="fas fa-shield-alt text-green-600"></i>
                <span>ปลอดภัย 100%</span>
            </div>
            <div class="flex items-center space-x-2">
                <i class="fas fa-lock text-blue-600"></i>
                <span>SSL Encryption</span>
            </div>
            <div class="flex items-center space-x-2">
                <i class="fas fa-check-circle text-emerald-600"></i>
                <span>PCI Compliant</span>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
function timer() {
    return {
        minutes: 15,
        seconds: 0,
        timeLeft: '15:00',
        start() {
            this.countdown();
        },
        countdown() {
            setInterval(() => {
                if (this.seconds === 0) {
                    if (this.minutes === 0) {
                        // Time's up
                        alert('หมดเวลาชำระเงิน กรุณาทำรายการใหม่');
                        window.location.href = '{{ route("wallet.topup") }}';
                        return;
                    }
                    this.minutes--;
                    this.seconds = 59;
                } else {
                    this.seconds--;
                }

                this.timeLeft = String(this.minutes).padStart(2, '0') + ':' + String(this.seconds).padStart(2, '0');
            }, 1000);
        }
    }
}

// Auto-refresh to check payment status
setInterval(() => {
    fetch('{{ route("wallet.topup.payment", $transaction) }}', {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    }).then(response => response.json()).then(data => {
        if (data.status === 'completed') {
            window.location.href = '{{ route("wallet.topup.success", $transaction) }}';
        }
    }).catch(() => {
        // Silent fail
    });
}, 5000); // Check every 5 seconds
</script>
@endpush
@endsection
