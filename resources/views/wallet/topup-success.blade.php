@extends('layouts.app')

@section('title', 'เติมเงินสำเร็จ')

@push('styles')
<style>
    @keyframes checkmark {
        0% { stroke-dashoffset: 100; }
        100% { stroke-dashoffset: 0; }
    }
    @keyframes celebrate {
        0%, 100% { transform: scale(1) rotate(0deg); }
        25% { transform: scale(1.1) rotate(-5deg); }
        75% { transform: scale(1.1) rotate(5deg); }
    }
    @keyframes slideInUp {
        from { transform: translateY(30px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    @keyframes confetti-fall {
        to { transform: translateY(100vh) rotate(360deg); }
    }
    .checkmark-circle {
        stroke-dasharray: 166;
        stroke-dashoffset: 166;
        animation: checkmark 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
    }
    .checkmark-check {
        stroke-dasharray: 48;
        stroke-dashoffset: 48;
        animation: checkmark 0.3s 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
    }
    .celebrate-icon {
        animation: celebrate 0.6s ease-in-out;
    }
    .slide-in-up {
        animation: slideInUp 0.8s ease-out;
    }
    .confetti {
        position: fixed;
        width: 10px;
        height: 10px;
        background: #ffd700;
        position: absolute;
        animation: confetti-fall 3s linear infinite;
    }
    @keyframes pulse-success {
        0%, 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
        50% { box-shadow: 0 0 0 20px rgba(16, 185, 129, 0); }
    }
    .pulse-success {
        animation: pulse-success 2s infinite;
    }
</style>
@endpush

@section('content')
<div class="min-h-screen bg-gradient-to-br from-emerald-50 via-green-50 to-emerald-100 dark:from-gray-900 dark:to-gray-800 py-8 px-4 relative overflow-hidden">

    <!-- Confetti Effect -->
    <div id="confetti-container" class="fixed inset-0 pointer-events-none z-0"></div>

    <div class="max-w-4xl mx-auto relative z-10">

        <!-- Success Card -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl overflow-hidden slide-in-up">

            <!-- Success Header with Animation -->
            <div class="bg-gradient-to-r from-emerald-500 via-green-600 to-emerald-700 p-12 text-center relative overflow-hidden">
                <!-- Decorative Elements -->
                <div class="absolute inset-0 opacity-10">
                    <div class="absolute top-10 left-10 w-32 h-32 bg-white rounded-full"></div>
                    <div class="absolute bottom-10 right-10 w-40 h-40 bg-white rounded-full"></div>
                </div>

                <div class="relative">
                    <!-- Animated Success Checkmark -->
                    <div class="inline-flex items-center justify-center mb-6">
                        <div class="relative pulse-success rounded-full bg-white p-4">
                            <svg class="w-32 h-32" viewBox="0 0 52 52">
                                <circle class="checkmark-circle" cx="26" cy="26" r="25" fill="none" stroke="#10B981" stroke-width="2"/>
                                <path class="checkmark-check" fill="none" stroke="#10B981" stroke-width="3" stroke-linecap="round" d="M14 27l7.5 7.5L38 18"/>
                            </svg>
                        </div>
                    </div>

                    <h1 class="text-4xl font-bold text-white mb-3 celebrate-icon">🎉 เติมเงินสำเร็จ!</h1>
                    <p class="text-xl text-emerald-100">ยอดเงินของคุณได้รับการอัปเดตแล้ว</p>
                </div>
            </div>

            <!-- Transaction Details -->
            <div class="p-8">

                <!-- Amount Display -->
                <div class="text-center mb-8">
                    <div class="inline-block bg-gradient-to-r from-emerald-500 to-green-600 rounded-3xl p-8 shadow-xl">
                        <p class="text-white text-sm mb-2 opacity-90">จำนวนเงินที่เติม</p>
                        <p class="text-6xl font-bold text-white mb-2">฿{{ number_format($transaction->amount, 2) }}</p>
                        <div class="flex items-center justify-center space-x-2 text-emerald-100">
                            <i class="fas fa-check-circle"></i>
                            <span class="text-sm">รับเข้ากระเป๋าเรียบร้อย</span>
                        </div>
                    </div>
                </div>

                <!-- Transaction Info Cards -->
                <div class="grid md:grid-cols-2 gap-4 mb-8">
                    <!-- Transaction ID -->
                    <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-600">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm text-gray-600 dark:text-gray-400">หมายเลขอ้างอิง</span>
                            <i class="fas fa-hashtag text-emerald-600"></i>
                        </div>
                        <p class="text-lg font-mono font-bold text-gray-900 dark:text-white">{{ $transaction->reference_number }}</p>
                    </div>

                    <!-- Payment Method -->
                    <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-600">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm text-gray-600 dark:text-gray-400">ช่องทางการชำระ</span>
                            @if($transaction->payment_method === 'promptpay')
                                <i class="fas fa-qrcode text-blue-600"></i>
                            @elseif($transaction->payment_method === 'credit_card')
                                <i class="fas fa-credit-card text-purple-600"></i>
                            @else
                                <i class="fas fa-university text-indigo-600"></i>
                            @endif
                        </div>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white capitalize">{{ str_replace('_', ' ', $transaction->payment_method) }}</p>
                    </div>

                    <!-- Date & Time -->
                    <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-600">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm text-gray-600 dark:text-gray-400">วันที่และเวลา</span>
                            <i class="fas fa-calendar-alt text-emerald-600"></i>
                        </div>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $transaction->created_at->format('d/m/Y H:i') }}</p>
                    </div>

                    <!-- Status -->
                    <div class="bg-gradient-to-br from-emerald-50 to-green-50 dark:from-emerald-900/20 dark:to-green-900/20 rounded-2xl p-6 border border-emerald-200 dark:border-emerald-800">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm text-emerald-700 dark:text-emerald-400">สถานะ</span>
                            <i class="fas fa-check-circle text-emerald-600"></i>
                        </div>
                        <p class="text-lg font-bold text-emerald-700 dark:text-emerald-300">สำเร็จ</p>
                    </div>
                </div>

                <!-- New Balance Display -->
                @if(auth()->user()->wallet)
                <div class="bg-gradient-to-r from-emerald-500 to-green-600 rounded-2xl p-6 text-white mb-8">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-emerald-100 text-sm mb-1">ยอดเงินคงเหลือปัจจุบัน</p>
                            <p class="text-4xl font-bold">฿{{ number_format(auth()->user()->wallet->balance, 2) }}</p>
                        </div>
                        <div class="text-5xl opacity-30">
                            <i class="fas fa-wallet"></i>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Success Messages -->
                <div class="grid md:grid-cols-3 gap-4 mb-8">
                    <div class="text-center p-6 bg-green-50 dark:bg-green-900/20 rounded-2xl border border-green-200 dark:border-green-800">
                        <div class="text-4xl mb-3">⚡</div>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white mb-1">รวดเร็ว</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">เข้าบัญชีทันที</p>
                    </div>
                    <div class="text-center p-6 bg-blue-50 dark:bg-blue-900/20 rounded-2xl border border-blue-200 dark:border-blue-800">
                        <div class="text-4xl mb-3">🔒</div>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white mb-1">ปลอดภัย</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">เข้ารหัส 100%</p>
                    </div>
                    <div class="text-center p-6 bg-purple-50 dark:bg-purple-900/20 rounded-2xl border border-purple-200 dark:border-purple-800">
                        <div class="text-4xl mb-3">✅</div>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white mb-1">ยืนยันแล้ว</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">พร้อมใช้งาน</p>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="{{ route('user.dashboard') }}" class="flex-1 inline-flex items-center justify-center px-8 py-4 bg-gradient-to-r from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700 text-white font-bold rounded-xl shadow-lg hover:shadow-2xl transform hover:scale-105 transition-all">
                        <i class="fas fa-home mr-2"></i>
                        กลับหน้าหลัก
                    </a>
                    <a href="{{ route('user.wallet.topup') }}" class="flex-1 inline-flex items-center justify-center px-8 py-4 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-bold rounded-xl border-2 border-gray-200 dark:border-gray-600 shadow-lg hover:shadow-2xl transform hover:scale-105 transition-all">
                        <i class="fas fa-plus-circle mr-2"></i>
                        เติมเงินอีกครั้ง
                    </a>
                </div>

                <!-- Receipt Download -->
                <div class="mt-6 text-center">
                    <button onclick="window.print()" class="inline-flex items-center px-6 py-3 text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 font-semibold transition-colors">
                        <i class="fas fa-file-pdf mr-2"></i>
                        ดาวน์โหลดใบเสร็จ
                    </button>
                </div>

            </div>

            <!-- Footer Note -->
            <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-700 px-8 py-6 border-t border-gray-200 dark:border-gray-600">
                <div class="flex items-start space-x-3">
                    <i class="fas fa-info-circle text-blue-600 dark:text-blue-400 mt-1"></i>
                    <div class="text-sm text-gray-700 dark:text-gray-300">
                        <p class="font-semibold mb-1">หมายเหตุ:</p>
                        <ul class="list-disc list-inside space-y-1 text-xs text-gray-600 dark:text-gray-400">
                            <li>ยอดเงินได้รับการอัปเดตในระบบแล้ว</li>
                            <li>คุณสามารถตรวจสอบประวัติการทำรายการได้ในหน้ากระเป๋าเงิน</li>
                            <li>หากมีข้อสงสัย กรุณาติดต่อฝ่ายสนับสนุนลูกค้า</li>
                        </ul>
                    </div>
                </div>
            </div>

        </div>

        <!-- Social Share (Optional) -->
        <div class="mt-8 text-center">
            <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">ชอบบริการของเราใช่ไหม? แชร์ให้เพื่อนๆ รู้จัก!</p>
            <div class="flex items-center justify-center space-x-3">
                <button class="w-12 h-12 bg-blue-600 hover:bg-blue-700 text-white rounded-full shadow-lg hover:shadow-xl transform hover:scale-110 transition-all">
                    <i class="fab fa-facebook-f"></i>
                </button>
                <button class="w-12 h-12 bg-blue-400 hover:bg-blue-500 text-white rounded-full shadow-lg hover:shadow-xl transform hover:scale-110 transition-all">
                    <i class="fab fa-twitter"></i>
                </button>
                <button class="w-12 h-12 bg-green-500 hover:bg-green-600 text-white rounded-full shadow-lg hover:shadow-xl transform hover:scale-110 transition-all">
                    <i class="fab fa-line"></i>
                </button>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
// Confetti Animation
function createConfetti() {
    const container = document.getElementById('confetti-container');
    const colors = ['#10B981', '#34D399', '#6EE7B7', '#FCD34D', '#FBBF24', '#F59E0B'];

    for (let i = 0; i < 50; i++) {
        const confetti = document.createElement('div');
        confetti.className = 'confetti';
        confetti.style.left = Math.random() * 100 + '%';
        confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
        confetti.style.animationDelay = Math.random() * 3 + 's';
        confetti.style.animationDuration = (Math.random() * 3 + 2) + 's';
        container.appendChild(confetti);

        setTimeout(() => confetti.remove(), 5000);
    }
}

// Trigger confetti on page load
window.addEventListener('load', () => {
    createConfetti();
    setTimeout(createConfetti, 2000);
});

// Play success sound (optional)
// const successSound = new Audio('/sounds/success.mp3');
// successSound.play().catch(() => {}); // Ignore if sound fails
</script>
@endpush
@endsection
