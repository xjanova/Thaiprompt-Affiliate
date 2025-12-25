@extends('layouts.user-arrow-x')

@section('title', 'สถานะการเติมเงิน')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    {{-- Premium Hero Header (Indigo-Purple-Pink for Top-Up Status) --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 dark:from-indigo-800 dark:via-purple-800 dark:to-pink-800 rounded-2xl shadow-2xl p-8">
        {{-- Animated Background Orbs --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s"></div>
        </div>

        {{-- Floating Icons --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute text-white/10 text-8xl top-10 right-20" style="animation: float 6s ease-in-out infinite">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>

        {{-- Header Content --}}
        <div class="relative z-10">
            <div class="flex items-center gap-4">
                <a href="{{ route('user.wallet.index') }}" class="glass-fusion px-4 py-2 hover:bg-white/25 rounded-lg transition-all">
                    <i class="fas fa-arrow-left mr-2"></i>กลับ
                </a>
                <div class="glass-fusion p-4 rounded-2xl">
                    <i class="fas fa-chart-line text-4xl text-white drop-shadow-lg"></i>
                </div>
                <div>
                    <h1 class="text-4xl font-bold text-white drop-shadow-lg">สถานะการเติมเงิน</h1>
                    <p class="text-indigo-100 text-lg mt-1">ตรวจสอบสถานะรายการเติมเงินของคุณ</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Transaction Status Card -->
    <x-arrow-x.card-v3 class="p-6">
        <div class="text-center">
            <!-- Status Icon -->
            <div class="mb-6">
                @switch($transaction->status)
                    @case('completed')
                        <div class="w-24 h-24 mx-auto bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
                            <span class="text-5xl">✅</span>
                        </div>
                        <h2 class="mt-4 text-2xl font-bold text-green-600 dark:text-green-400">ชำระเงินสำเร็จ!</h2>
                        @break
                    @case('processing')
                        <div class="w-24 h-24 mx-auto bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center">
                            <svg class="w-12 h-12 text-blue-600 dark:text-blue-400 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                        <h2 class="mt-4 text-2xl font-bold text-blue-600 dark:text-blue-400">กำลังดำเนินการ...</h2>
                        @break
                    @case('pending')
                        <div class="w-24 h-24 mx-auto bg-yellow-100 dark:bg-yellow-900/30 rounded-full flex items-center justify-center">
                            <span class="text-5xl">⏳</span>
                        </div>
                        <h2 class="mt-4 text-2xl font-bold text-yellow-600 dark:text-yellow-400">รอการชำระเงิน</h2>
                        @break
                    @case('failed')
                        <div class="w-24 h-24 mx-auto bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center">
                            <span class="text-5xl">❌</span>
                        </div>
                        <h2 class="mt-4 text-2xl font-bold text-red-600 dark:text-red-400">การชำระเงินล้มเหลว</h2>
                        @break
                    @case('cancelled')
                        <div class="w-24 h-24 mx-auto bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                            <span class="text-5xl">🚫</span>
                        </div>
                        <h2 class="mt-4 text-2xl font-bold text-gray-600 dark:text-gray-400">ยกเลิกแล้ว</h2>
                        @break
                    @default
                        <div class="w-24 h-24 mx-auto bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                            <span class="text-5xl">❓</span>
                        </div>
                        <h2 class="mt-4 text-2xl font-bold text-gray-600 dark:text-gray-400">{{ $transaction->status }}</h2>
                @endswitch
            </div>

            <!-- Transaction Details -->
            <div class="max-w-md mx-auto space-y-4">
                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <span class="text-gray-600 dark:text-gray-400">รหัสรายการ</span>
                    <span class="font-mono font-bold text-gray-900 dark:text-gray-100">{{ $transaction->transaction_id }}</span>
                </div>

                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <span class="text-gray-600 dark:text-gray-400">จำนวนเงิน</span>
                    <span class="font-bold text-2xl text-green-600 dark:text-green-400">฿{{ number_format($transaction->amount, 2) }}</span>
                </div>

                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <span class="text-gray-600 dark:text-gray-400">วิธีชำระเงิน</span>
                    <span class="font-medium text-gray-900 dark:text-gray-100">
                        @switch($transaction->payment_method)
                            @case('promptpay')
                                💳 พร้อมเพย์
                                @break
                            @case('bank_transfer')
                                🏦 โอนธนาคาร
                                @break
                            @case('credit_card')
                                💳 บัตรเครดิต
                                @break
                            @default
                                {{ $transaction->payment_method }}
                        @endswitch
                    </span>
                </div>

                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <span class="text-gray-600 dark:text-gray-400">สร้างเมื่อ</span>
                    <span class="text-gray-900 dark:text-gray-100">{{ $transaction->created_at->format('d/m/Y H:i:s') }}</span>
                </div>

                @if($transaction->paid_at)
                <div class="flex justify-between items-center p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                    <span class="text-green-700 dark:text-green-400">ชำระเงินเมื่อ</span>
                    <span class="font-medium text-green-800 dark:text-green-300">{{ $transaction->paid_at->format('d/m/Y H:i:s') }}</span>
                </div>
                @endif

                @if($transaction->expired_at && !$transaction->isCompleted())
                <div class="flex justify-between items-center p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                    <span class="text-yellow-700 dark:text-yellow-400">หมดอายุเมื่อ</span>
                    <span class="font-medium text-yellow-800 dark:text-yellow-300" id="expiry-time">
                        {{ $transaction->expired_at->format('d/m/Y H:i:s') }}
                    </span>
                </div>
                @endif
            </div>

            <!-- Action Buttons -->
            <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center">
                @if($transaction->status === 'pending' && !$transaction->isExpired())
                    <a href="{{ route('user.wallet.topup.payment', $transaction->transaction_id) }}"
                       class="px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-bold rounded-xl transition shadow-lg">
                        💳 ดำเนินการชำระเงิน
                    </a>
                @endif

                @if($transaction->status === 'completed')
                    <a href="{{ route('user.wallet.index') }}"
                       class="px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-bold rounded-xl transition shadow-lg">
                        👛 ไปยัง Wallet
                    </a>
                @endif

                @if(in_array($transaction->status, ['failed', 'cancelled']) || $transaction->isExpired())
                    <a href="{{ route('user.wallet.topup') }}"
                       class="px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold rounded-xl transition shadow-lg">
                        🔄 สร้างรายการใหม่
                    </a>
                @endif

                <a href="{{ route('user.wallet.index') }}"
                   class="px-6 py-3 border-2 border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-bold rounded-xl transition">
                    ← กลับหน้าหลัก
                </a>
            </div>
        </div>
    </x-arrow-x.card-v3>
</div>

@if(in_array($transaction->status, ['pending', 'processing']))
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-refresh status for pending/processing transactions
    function checkStatus() {
        fetch('{{ route("user.wallet.topup.check", $transaction->transaction_id) }}')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.is_completed) {
                    // Reload page to show success
                    window.location.reload();
                }
            })
            .catch(error => console.error('Status check error:', error));
    }

    // Check every 5 seconds
    setInterval(checkStatus, 5000);
});
</script>
@endpush
@endif

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
