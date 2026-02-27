@extends('layouts.user-arrow-x')

@section('title', 'ชำระเงินผ่าน PromptPay')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    {{-- Premium Hero Header (Green-Emerald-Teal for PromptPay) --}}
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
                <a href="{{ route('user.wallet.deposit') }}" class="glass-fusion px-4 py-2 hover:bg-white/25 rounded-lg transition-all">
                    <i class="fas fa-arrow-left mr-2"></i>กลับ
                </a>
                <div class="glass-fusion p-4 rounded-2xl">
                    <i class="fas fa-mobile-alt text-4xl text-white drop-shadow-lg"></i>
                </div>
                <div>
                    <h1 class="text-4xl font-bold text-white drop-shadow-lg">ชำระเงินผ่าน PromptPay</h1>
                    <p class="text-green-100 text-lg mt-1">สแกน QR Code เพื่อชำระเงิน</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Information -->
    <x-arrow-x.card-v3 class="p-6">
        <div class="text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 dark:bg-green-900 rounded-full mb-4">
                <svg class="w-8 h-8 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">รายการฝากเงินของคุณ</h2>
            <p class="text-gray-600 dark:text-gray-400">กรุณาสแกน QR Code ด้านล่างเพื่อชำระเงิน</p>
        </div>

        <div class="mt-6 bg-gradient-to-r from-indigo-100 to-purple-100 dark:from-indigo-900 dark:to-purple-900 rounded-xl p-6 border-2 border-indigo-200 dark:border-indigo-700">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <p class="text-sm text-gray-700 dark:text-gray-300 font-medium mb-1">จำนวนเงิน</p>
                    <p class="text-3xl font-bold text-indigo-700 dark:text-indigo-300">฿{{ number_format($result['amount'], 2) }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-700 dark:text-gray-300 font-medium mb-1">รหัสอ้างอิง</p>
                    <p class="text-lg font-mono font-semibold text-gray-900 dark:text-gray-100">{{ $result['reference'] }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-700 dark:text-gray-300 font-medium mb-1">วิธีชำระเงิน</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $result['payment_method'] === 'promptpay' ? 'พร้อมเพย์' : $result['payment_method'] }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-700 dark:text-gray-300 font-medium mb-1">สถานะ</p>
                    <span class="inline-block px-3 py-1 bg-yellow-100 text-yellow-900 dark:bg-yellow-900 dark:text-yellow-100 rounded-full text-sm font-semibold border-2 border-yellow-300 dark:border-yellow-700">
                        {{ $result['status'] === 'pending' ? 'รอการชำระเงิน' : $result['status'] }}
                    </span>
                </div>
            </div>
        </div>
    </x-arrow-x.card-v3>

    <!-- QR Code Section -->
    <x-arrow-x.card-v3 class="p-6">
        <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-6 text-center">สแกน QR Code เพื่อชำระเงิน</h3>

        <div class="flex flex-col items-center">
            <!-- QR Code Display -->
            <div class="bg-white p-6 rounded-2xl shadow-lg border-4 border-green-200 inline-block">
                @php
                    $qrCode = $result['qr_code'] ?? '';
                @endphp
                @if(!empty($qrCode) && (str_starts_with($qrCode, 'data:image/') || str_starts_with($qrCode, 'https://')))
                    {{-- QR Code เป็น data URI (BaconQrCode) หรือ URL (Google Charts) → ใช้เป็น src โดยตรง --}}
                    <img src="{{ $qrCode }}"
                         alt="PromptPay QR Code"
                         class="w-64 h-64 md:w-80 md:h-80"
                         onerror="this.parentElement.innerHTML='<div class=\'w-64 h-64 flex items-center justify-center bg-gray-100 rounded-lg\'><p class=\'text-gray-500 text-center p-4\'>ไม่สามารถโหลด QR Code ได้</p></div>'">
                @elseif(!empty($qrCode))
                    {{-- QR Code เป็น raw payload text → สร้างผ่าน api.qrserver.com --}}
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data={{ urlencode($qrCode) }}"
                         alt="PromptPay QR Code"
                         class="w-64 h-64 md:w-80 md:h-80"
                         onerror="this.parentElement.innerHTML='<div class=\'w-64 h-64 flex items-center justify-center bg-gray-100 rounded-lg\'><p class=\'text-gray-500 text-center p-4\'>ไม่สามารถโหลด QR Code ได้</p></div>'">
                @else
                    {{-- ไม่มี QR Code → แสดงข้อความแจ้ง --}}
                    <div class="w-64 h-64 md:w-80 md:h-80 bg-gray-100 dark:bg-gray-800 flex items-center justify-center rounded-lg">
                        <div class="text-center p-4">
                            <p class="text-5xl mb-3">⚠️</p>
                            <p class="text-gray-600 dark:text-gray-400 font-semibold">ไม่สามารถสร้าง QR Code ได้</p>
                            <p class="text-sm text-gray-500 dark:text-gray-500 mt-2">กรุณาตรวจสอบการตั้งค่า PromptPay<br>หรือติดต่อผู้ดูแลระบบ</p>
                        </div>
                    </div>
                @endif
            </div>

            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">หรือใช้รหัสอ้างอิงนี้</p>
                <div class="flex items-center justify-center gap-2">
                    <code class="px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg text-lg font-mono font-bold text-indigo-600">
                        {{ $result['reference'] }}
                    </code>
                    <button onclick="copyReference()"
                            class="p-2 bg-indigo-100 text-indigo-600 rounded-lg hover:bg-indigo-200 transition">
                        📋
                    </button>
                </div>
            </div>

            <!-- Timer -->
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">QR Code หมดอายุใน</p>
                <div id="countdown" class="text-3xl font-bold text-red-600">15:00</div>
            </div>
        </div>
    </x-arrow-x.card-v3>

    {{-- คำชี้แจงทำไมยอดโอนมีจุดทศนิยม --}}
    <x-arrow-x.card-v3 class="p-0 overflow-hidden">
        @include('components.sms-payment-explanation')
    </x-arrow-x.card-v3>

    <!-- Instructions -->
    <x-arrow-x.card-v3 class="p-6">
        <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4">📱 วิธีการชำระเงิน</h3>

        <div class="space-y-4">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 w-10 h-10 bg-indigo-100 dark:bg-indigo-900 rounded-full flex items-center justify-center text-indigo-600 dark:text-indigo-300 font-bold">
                    1
                </div>
                <div>
                    <p class="font-semibold text-gray-900 dark:text-gray-100 mb-1">เปิดแอปธนาคารหรือแอปพร้อมเพย์</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">เช่น Mobile Banking, TrueMoney Wallet, Rabbit LINE Pay, ฯลฯ</p>
                </div>
            </div>

            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 w-10 h-10 bg-indigo-100 dark:bg-indigo-900 rounded-full flex items-center justify-center text-indigo-600 dark:text-indigo-300 font-bold">
                    2
                </div>
                <div>
                    <p class="font-semibold text-gray-900 dark:text-gray-100 mb-1">เลือกสแกน QR Code</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">ใช้กล้องในแอปสแกน QR Code ด้านบน</p>
                </div>
            </div>

            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 w-10 h-10 bg-indigo-100 dark:bg-indigo-900 rounded-full flex items-center justify-center text-indigo-600 dark:text-indigo-300 font-bold">
                    3
                </div>
                <div>
                    <p class="font-semibold text-gray-900 dark:text-gray-100 mb-1">ตรวจสอบจำนวนเงิน</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">ตรวจสอบว่าจำนวนเงินถูกต้อง: ฿{{ number_format($result['amount'], 2) }}</p>
                </div>
            </div>

            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 w-10 h-10 bg-indigo-100 dark:bg-indigo-900 rounded-full flex items-center justify-center text-indigo-600 dark:text-indigo-300 font-bold">
                    4
                </div>
                <div>
                    <p class="font-semibold text-gray-900 dark:text-gray-100 mb-1">ยืนยันการชำระเงิน</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">กดยืนยันการโอนเงินในแอป</p>
                </div>
            </div>

            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 w-10 h-10 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center text-green-600 dark:text-green-300 font-bold">
                    ✓
                </div>
                <div>
                    <p class="font-semibold text-gray-900 dark:text-gray-100 mb-1">รอการตรวจสอบ</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">ระบบจะตรวจสอบการชำระเงินอัตโนมัติภายใน 1-5 นาที</p>
                </div>
            </div>
        </div>
    </x-arrow-x.card-v3>

    <!-- Status Check -->
    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-2xl shadow-xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-xl font-bold mb-2">ตรวจสอบสถานะการชำระเงิน</h3>
                <p class="text-sm text-indigo-100">กดปุ่มด้านล่างเพื่อตรวจสอบสถานะหลังจากโอนเงินแล้ว</p>
            </div>
        </div>

        <div class="mt-4 flex gap-3">
            <button onclick="checkPaymentStatus()"
                    id="check-status-btn"
                    class="flex-1 px-6 py-3 bg-white dark:bg-gray-800 text-indigo-600 font-semibold rounded-lg hover:bg-indigo-50 transition">
                🔍 ตรวจสอบสถานะ
            </button>
            <a href="{{ route('user.wallet.index') }}"
               class="flex-1 px-6 py-3 bg-white dark:bg-gray-800 bg-opacity-20 hover:bg-opacity-30 text-white font-semibold rounded-lg transition text-center">
                กลับหน้าหลัก
            </a>
        </div>
    </div>

    <!-- Warning -->
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-lg">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm text-yellow-700">
                    <strong>คำเตือน:</strong> QR Code นี้จะหมดอายุใน 15 นาที หากหมดอายุ กรุณาสร้างรายการใหม่
                </p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Countdown Timer
let timeLeft = {{ isset($result['expires_at']) ? $result['expires_at']->diffInSeconds(now()) : 900 }};

function updateCountdown() {
    const minutes = Math.floor(timeLeft / 60);
    const seconds = timeLeft % 60;
    document.getElementById('countdown').textContent =
        `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

    if (timeLeft <= 0) {
        document.getElementById('countdown').textContent = 'หมดเวลา';
        document.getElementById('countdown').classList.add('text-gray-400');
        return;
    }

    if (timeLeft <= 60) {
        document.getElementById('countdown').classList.add('animate-pulse');
    }

    timeLeft--;
    setTimeout(updateCountdown, 1000);
}

updateCountdown();

// Copy Reference
function copyReference() {
    const reference = '{{ $result['reference'] }}';
    navigator.clipboard.writeText(reference).then(() => {
        const toast = document.createElement('div');
        toast.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
        toast.innerHTML = '✅ คัดลอกรหัสอ้างอิงสำเร็จ!';
        document.body.appendChild(toast);

        setTimeout(() => toast.remove(), 3000);
    });
}

// Check Payment Status
function checkPaymentStatus() {
    const btn = document.getElementById('check-status-btn');
    btn.disabled = true;
    btn.innerHTML = '⏳ กำลังตรวจสอบ...';

    // Redirect to verify endpoint
    window.location.href = '{{ route('user.wallet.deposit.verify', $result['reference']) }}';
}

// Auto-check every 30 seconds
setInterval(() => {
    // In production, use AJAX to check status without page reload
    console.log('Auto-checking payment status...');
}, 30000);
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
