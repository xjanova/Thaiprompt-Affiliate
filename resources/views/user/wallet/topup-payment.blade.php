@extends('layouts.user-arrow-x')

@section('title', 'ชำระเงินเติมเงิน')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    {{-- Premium Hero Header (Green-Emerald-Teal for Payment Selection) --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-green-600 via-emerald-600 to-teal-600 dark:from-green-800 dark:via-emerald-800 dark:to-teal-800 rounded-2xl shadow-2xl p-8">
        {{-- Animated Background Orbs --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s"></div>
        </div>

        {{-- Floating Icons --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute text-white/10 text-8xl top-10 right-20" style="animation: float 6s ease-in-out infinite">
                <i class="fas fa-credit-card"></i>
            </div>
        </div>

        {{-- Header Content --}}
        <div class="relative z-10">
            <div class="flex items-center gap-4">
                <a href="{{ route('user.wallet.topup') }}" class="glass-fusion px-4 py-2 hover:bg-white/25 rounded-lg transition-all">
                    <i class="fas fa-arrow-left mr-2"></i>กลับ
                </a>
                <div class="glass-fusion p-4 rounded-2xl">
                    <i class="fas fa-hand-holding-usd text-4xl text-white drop-shadow-lg"></i>
                </div>
                <div>
                    <h1 class="text-4xl font-bold text-white drop-shadow-lg">เลือกวิธีชำระเงิน</h1>
                    <p class="text-green-100 text-lg mt-1">เลือกช่องทางการชำระเงินที่สะดวกสำหรับคุณ</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Transaction Info -->
    <x-arrow-x.card-v3 class="p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">รหัสรายการ</p>
                <p class="text-lg font-mono font-bold text-gray-900 dark:text-gray-100">{{ $transaction->transaction_id }}</p>
            </div>
            <div class="text-right">
                <p class="text-sm text-gray-500 dark:text-gray-400">จำนวนเงินที่ต้องชำระ</p>
                <p class="text-3xl font-bold text-green-600 dark:text-green-400">฿{{ number_format($transaction->amount, 2) }}</p>
            </div>
        </div>

        <!-- Countdown Timer -->
        <div class="mt-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg">
            <div class="flex items-center gap-3">
                <span class="text-2xl">⏰</span>
                <div>
                    <p class="text-sm text-yellow-800 dark:text-yellow-300">กรุณาชำระเงินภายใน</p>
                    <p class="text-lg font-bold text-yellow-900 dark:text-yellow-200" id="countdown">
                        {{ $transaction->expired_at ? $transaction->expired_at->diffForHumans() : '30 นาที' }}
                    </p>
                </div>
            </div>
        </div>
    </x-arrow-x.card-v3>

    <!-- Payment Methods -->
    <x-arrow-x.card-v3 class="p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-6">เลือกช่องทางชำระเงิน</h2>

        <form action="{{ route('user.wallet.topup.payment.process', $transaction->transaction_id) }}" method="POST" id="payment-form">
            @csrf

            <div class="space-y-4">
                @forelse($paymentGateways as $gateway)
                    <label class="payment-option block cursor-pointer">
                        <input type="radio"
                               name="payment_method"
                               value="{{ $gateway->code }}"
                               class="hidden peer"
                               required>
                        <div class="p-4 border-2 border-gray-200 dark:border-gray-700 rounded-xl hover:border-green-400 dark:hover:border-green-500 peer-checked:border-green-500 peer-checked:bg-green-50 dark:peer-checked:bg-green-900/20 transition">
                            <div class="flex items-center gap-4">
                                <div class="text-3xl" style="color: {{ $gateway->color ?? '#10B981' }}">
                                    {{ $gateway->icon ?? '💳' }}
                                </div>
                                <div class="flex-1">
                                    <p class="font-bold text-gray-900 dark:text-gray-100">{{ $gateway->name }}</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $gateway->description }}</p>
                                    @if($gateway->fees && isset($gateway->fees['deposit_fee_amount']) && $gateway->fees['deposit_fee_amount'] > 0)
                                        <p class="text-xs text-orange-600 dark:text-orange-400 mt-1">
                                            ค่าธรรมเนียม: {{ $gateway->fees['deposit_fee_type'] === 'percentage' ? $gateway->fees['deposit_fee_amount'] . '%' : '฿' . number_format($gateway->fees['deposit_fee_amount'], 2) }}
                                        </p>
                                    @else
                                        <p class="text-xs text-green-600 dark:text-green-400 mt-1">ไม่มีค่าธรรมเนียม</p>
                                    @endif
                                </div>
                                <div class="w-6 h-6 rounded-full border-2 border-gray-300 dark:border-gray-600 peer-checked:border-green-500 peer-checked:bg-green-500 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white hidden peer-checked:block" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </label>
                @empty
                    <!-- Fallback Payment Methods -->
                    <label class="payment-option block cursor-pointer">
                        <input type="radio" name="payment_method" value="promptpay" class="hidden peer" required>
                        <div class="p-4 border-2 border-gray-200 dark:border-gray-700 rounded-xl hover:border-green-400 dark:hover:border-green-500 peer-checked:border-green-500 peer-checked:bg-green-50 dark:peer-checked:bg-green-900/20 transition">
                            <div class="flex items-center gap-4">
                                <div class="text-3xl">💳</div>
                                <div class="flex-1">
                                    <p class="font-bold text-gray-900 dark:text-gray-100">พร้อมเพย์ (PromptPay)</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">ชำระเงินผ่าน QR Code พร้อมเพย์</p>
                                    <p class="text-xs text-green-600 dark:text-green-400 mt-1">ไม่มีค่าธรรมเนียม</p>
                                </div>
                            </div>
                        </div>
                    </label>

                    <label class="payment-option block cursor-pointer">
                        <input type="radio" name="payment_method" value="bank_transfer" class="hidden peer">
                        <div class="p-4 border-2 border-gray-200 dark:border-gray-700 rounded-xl hover:border-green-400 dark:hover:border-green-500 peer-checked:border-green-500 peer-checked:bg-green-50 dark:peer-checked:bg-green-900/20 transition">
                            <div class="flex items-center gap-4">
                                <div class="text-3xl">🏦</div>
                                <div class="flex-1">
                                    <p class="font-bold text-gray-900 dark:text-gray-100">โอนผ่านธนาคาร</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">โอนเงินผ่านธนาคารและอัพโหลดสลิป</p>
                                    <p class="text-xs text-green-600 dark:text-green-400 mt-1">ไม่มีค่าธรรมเนียม</p>
                                </div>
                            </div>
                        </div>
                    </label>
                @endforelse
            </div>

            <!-- Submit Button -->
            <button type="submit"
                    id="submit-btn"
                    disabled
                    class="w-full mt-6 px-6 py-4 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 disabled:from-gray-400 disabled:to-gray-500 disabled:cursor-not-allowed text-white font-bold text-lg rounded-xl transition shadow-lg">
                <span id="btn-text">กรุณาเลือกวิธีชำระเงิน</span>
                <span id="btn-loading" class="hidden">
                    <svg class="animate-spin inline-block w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    กำลังดำเนินการ...
                </span>
            </button>
        </form>
    </x-arrow-x.card-v3>

    <!-- Security Info -->
    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-200 dark:border-blue-700 rounded-2xl p-6">
        <div class="flex items-start gap-4">
            <div class="text-3xl">🔒</div>
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-2">ความปลอดภัย</h3>
                <ul class="text-sm text-gray-700 dark:text-gray-300 space-y-1 list-disc list-inside">
                    <li>ข้อมูลการชำระเงินของคุณถูกเข้ารหัสและปลอดภัย</li>
                    <li>เงินจะเข้า Wallet ทันทีหลังชำระเงินสำเร็จ</li>
                    <li>หากมีปัญหา สามารถติดต่อทีมงานได้ตลอด 24 ชั่วโมง</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('payment-form');
    const submitBtn = document.getElementById('submit-btn');
    const btnText = document.getElementById('btn-text');
    const btnLoading = document.getElementById('btn-loading');
    const paymentOptions = document.querySelectorAll('input[name="payment_method"]');

    // เปิดปุ่มเมื่อเลือกวิธีชำระเงิน
    paymentOptions.forEach(option => {
        option.addEventListener('change', function() {
            submitBtn.disabled = false;
            btnText.textContent = 'ดำเนินการชำระเงิน ฿{{ number_format($transaction->amount, 2) }}';
        });
    });

    // แสดง loading เมื่อ submit
    form.addEventListener('submit', function() {
        submitBtn.disabled = true;
        btnText.classList.add('hidden');
        btnLoading.classList.remove('hidden');
    });

    // Countdown Timer
    @if($transaction->expired_at)
    const expiredAt = new Date('{{ $transaction->expired_at->toIso8601String() }}');
    const countdownEl = document.getElementById('countdown');

    function updateCountdown() {
        const now = new Date();
        const diff = expiredAt - now;

        if (diff <= 0) {
            countdownEl.textContent = 'หมดเวลาแล้ว';
            countdownEl.classList.add('text-red-600');
            submitBtn.disabled = true;
            return;
        }

        const minutes = Math.floor(diff / 60000);
        const seconds = Math.floor((diff % 60000) / 1000);
        countdownEl.textContent = `${minutes} นาที ${seconds} วินาที`;
    }

    updateCountdown();
    setInterval(updateCountdown, 1000);
    @endif
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
