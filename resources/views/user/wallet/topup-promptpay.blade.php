@extends('layouts.user-arrow-x')

@section('title', 'ชำระเงินผ่านพร้อมเพย์')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    {{-- Premium Hero Header (Green-Emerald-Teal for PromptPay Top-Up) --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-green-600 via-emerald-600 to-teal-600 dark:from-green-800 dark:via-emerald-800 dark:to-teal-800 rounded-2xl shadow-2xl p-8">
        {{-- Animated Background Orbs --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s"></div>
        </div>

        {{-- Floating Icons --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute text-white/10 text-8xl top-10 right-20" style="animation: float 6s ease-in-out infinite">
                <i class="fas fa-qrcode"></i>
            </div>
        </div>

        {{-- Header Content --}}
        <div class="relative z-10">
            <div class="flex items-center gap-4">
                <a href="{{ route('user.wallet.topup') }}" class="glass-fusion px-4 py-2 hover:bg-white/25 rounded-lg transition-all">
                    <i class="fas fa-arrow-left mr-2"></i>กลับ
                </a>
                <div class="glass-fusion p-4 rounded-2xl">
                    <i class="fas fa-mobile-alt text-4xl text-white drop-shadow-lg"></i>
                </div>
                <div>
                    <h1 class="text-4xl font-bold text-white drop-shadow-lg">สแกน QR Code พร้อมเพย์</h1>
                    <p class="text-green-100 text-lg mt-1">สแกน QR Code ด้วยแอปธนาคารเพื่อชำระเงิน</p>
                </div>
            </div>
        </div>
    </div>

    <!-- QR Code Section -->
    <x-arrow-x.card-v3 class="p-6">
        <div class="text-center">
            <!-- Transaction Info -->
            <div class="mb-6">
                <p class="text-sm text-gray-500 dark:text-gray-400">รหัสรายการ</p>
                <p class="text-lg font-mono font-bold text-gray-900 dark:text-gray-100">{{ $transaction->transaction_id }}</p>
            </div>

            <!-- Amount -->
            <div class="mb-6 p-4 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-2 border-green-300 dark:border-green-600 rounded-xl inline-block">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">จำนวนเงินที่ต้องชำระ</p>
                <p class="text-4xl font-bold text-green-600 dark:text-green-400">฿{{ number_format($transaction->amount, 2) }}</p>
            </div>

            <!-- QR Code -->
            <div class="my-8">
                <div class="bg-white p-6 rounded-2xl shadow-lg inline-block">
                    @if($qrCode)
                        {{-- ถ้า QR Code เป็น Base64 encoded JSON --}}
                        @php
                            $qrData = json_decode(base64_decode($qrCode), true);
                        @endphp
                        @if($qrData && isset($qrData['warning']))
                            {{-- Mock QR Code - แสดงข้อความแจ้งเตือน --}}
                            <div class="w-64 h-64 bg-gray-100 dark:bg-gray-800 flex items-center justify-center rounded-lg">
                                <div class="text-center p-4">
                                    <p class="text-4xl mb-3">📱</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $qrData['warning'] ?? 'กรุณาตั้งค่า PromptPay' }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-500 mt-2">
                                        Ref: {{ $qrData['ref_no'] ?? $refNo ?? 'N/A' }}
                                    </p>
                                </div>
                            </div>
                        @else
                            {{-- Real QR Code --}}
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=256x256&data={{ urlencode($qrCode) }}"
                                 alt="PromptPay QR Code"
                                 class="w-64 h-64">
                        @endif
                    @else
                        <div class="w-64 h-64 bg-gray-100 dark:bg-gray-800 flex items-center justify-center rounded-lg">
                            <p class="text-gray-500 dark:text-gray-400">ไม่สามารถสร้าง QR Code ได้</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Reference Number -->
            @if($refNo)
            <div class="mb-6">
                <p class="text-sm text-gray-500 dark:text-gray-400">หมายเลขอ้างอิง</p>
                <p class="text-xl font-mono font-bold text-blue-600 dark:text-blue-400">{{ $refNo }}</p>
            </div>
            @endif

            <!-- Countdown Timer -->
            <div class="mb-6 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg inline-block">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">⏰</span>
                    <div class="text-left">
                        <p class="text-sm text-yellow-800 dark:text-yellow-300">กรุณาชำระเงินภายใน</p>
                        <p class="text-lg font-bold text-yellow-900 dark:text-yellow-200" id="countdown">30:00</p>
                    </div>
                </div>
            </div>

            <!-- Status Check -->
            <div id="status-section" class="mb-6">
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded-full">
                    <svg class="animate-spin w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>กำลังรอการชำระเงิน...</span>
                </div>
            </div>
        </div>
    </x-arrow-x.card-v3>

    {{-- คำชี้แจงทำไมยอดโอนมีจุดทศนิยม --}}
    <x-arrow-x.card-v3 class="p-0 overflow-hidden">
        @include('components.sms-payment-explanation')
    </x-arrow-x.card-v3>

    <!-- Instructions -->
    <x-arrow-x.card-v3 class="p-6">
        <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4">📋 วิธีการชำระเงิน</h3>
        <ol class="space-y-3 text-gray-700 dark:text-gray-300">
            <li class="flex items-start gap-3">
                <span class="flex-shrink-0 w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm font-bold">1</span>
                <span>เปิดแอปธนาคารบนมือถือของคุณ (กสิกร, กรุงไทย, ไทยพาณิชย์, ฯลฯ)</span>
            </li>
            <li class="flex items-start gap-3">
                <span class="flex-shrink-0 w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm font-bold">2</span>
                <span>เลือกเมนู "สแกน QR" หรือ "จ่ายบิล"</span>
            </li>
            <li class="flex items-start gap-3">
                <span class="flex-shrink-0 w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm font-bold">3</span>
                <span>สแกน QR Code ด้านบน</span>
            </li>
            <li class="flex items-start gap-3">
                <span class="flex-shrink-0 w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm font-bold">4</span>
                <span>ตรวจสอบจำนวนเงิน และยืนยันการโอน</span>
            </li>
            <li class="flex items-start gap-3">
                <span class="flex-shrink-0 w-6 h-6 bg-green-500 text-white rounded-full flex items-center justify-center text-sm font-bold">✓</span>
                <span>หลังโอนเงินสำเร็จ ยอดเงินจะเข้า Wallet อัตโนมัติ</span>
            </li>
        </ol>
    </x-arrow-x.card-v3>

    <!-- Help Section -->
    <div class="bg-gradient-to-br from-orange-50 to-amber-50 dark:from-orange-900/20 dark:to-amber-900/20 border border-orange-200 dark:border-orange-700 rounded-2xl p-6">
        <div class="flex items-start gap-4">
            <div class="text-3xl">❓</div>
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-2">มีปัญหา?</h3>
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    หากชำระเงินแล้วแต่ยอดเงินยังไม่เข้า กรุณารอสักครู่ ระบบจะตรวจสอบอัตโนมัติ
                    หรือติดต่อทีมงานพร้อมหมายเลขอ้างอิง: <strong class="font-mono">{{ $refNo ?? $transaction->transaction_id }}</strong>
                </p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const transactionId = '{{ $transaction->transaction_id }}';
    const countdownEl = document.getElementById('countdown');
    const statusSection = document.getElementById('status-section');

    // Countdown Timer
    @if($transaction->expired_at)
    const expiredAt = new Date('{{ $transaction->expired_at->toIso8601String() }}');

    function updateCountdown() {
        const now = new Date();
        const diff = expiredAt - now;

        if (diff <= 0) {
            countdownEl.textContent = 'หมดเวลาแล้ว';
            countdownEl.classList.add('text-red-600');
            statusSection.innerHTML = `
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 rounded-full">
                    <span>❌</span>
                    <span>รายการหมดอายุแล้ว</span>
                </div>
            `;
            return;
        }

        const minutes = Math.floor(diff / 60000);
        const seconds = Math.floor((diff % 60000) / 1000);
        countdownEl.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
    }

    updateCountdown();
    setInterval(updateCountdown, 1000);
    @endif

    // Poll for payment status
    function checkPaymentStatus() {
        fetch('{{ route("user.wallet.topup.check", $transaction->transaction_id) }}')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.is_completed) {
                    // ชำระเงินสำเร็จ!
                    statusSection.innerHTML = `
                        <div class="inline-flex items-center gap-2 px-4 py-2 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 rounded-full">
                            <span>✅</span>
                            <span>ชำระเงินสำเร็จ! กำลังนำท่านไปหน้า Wallet...</span>
                        </div>
                    `;

                    // Redirect หลังจาก 2 วินาที
                    setTimeout(() => {
                        window.location.href = '{{ route("user.wallet.index") }}?success=topup';
                    }, 2000);
                } else if (data.data && data.data.is_expired) {
                    // หมดอายุ
                    statusSection.innerHTML = `
                        <div class="inline-flex items-center gap-2 px-4 py-2 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 rounded-full">
                            <span>❌</span>
                            <span>รายการหมดอายุแล้ว</span>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Status check error:', error);
            });
    }

    // Check status every 5 seconds
    setInterval(checkPaymentStatus, 5000);
});
</script>
@endpush

@push('styles')
<style>
.glass-fusion {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}
@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-20px); }
}
</style>
@endpush
@endsection
