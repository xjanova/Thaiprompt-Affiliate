@extends('layouts.app')

@section('title', 'รอชำระเงิน')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-purple-900 via-indigo-900 to-purple-800 py-12">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="inline-block animate-pulse mb-4">
                <span class="text-6xl">⏳</span>
            </div>
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-4">
                รอการชำระเงิน
            </h1>
            <p class="text-purple-200 text-lg">กรุณาโอนเงินตามยอดด้านล่าง</p>
        </div>

        @if(session('info'))
            <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded-lg mb-6 text-center">
                {{ session('info') }}
            </div>
        @endif

        <!-- Payment Card -->
        <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl p-6 mb-6">
            <!-- Amount Display -->
            <div class="text-center py-8 border-b border-white/20 mb-6">
                <p class="text-purple-200 text-lg mb-2">ยอดที่ต้องชำระ</p>
                <div class="text-5xl md:text-6xl font-bold text-yellow-300 mb-2">
                    ฿{{ number_format($amount, 2) }}
                </div>
                <p class="text-red-300 text-sm font-semibold">
                    ⚠️ โปรดโอนตามยอดนี้เท่านั้น (รวมทศนิยม)
                </p>
            </div>

            <!-- Payment Instructions -->
            <div class="space-y-4 mb-6">
                <h3 class="text-lg font-bold text-white flex items-center gap-2">
                    <span>📱</span> วิธีชำระเงิน
                </h3>

                @if($transaction->payment_method === 'promptpay')
                <div class="bg-white/5 rounded-lg p-4">
                    <ol class="list-decimal list-inside space-y-2 text-purple-200">
                        <li>เปิดแอปธนาคารบนมือถือ</li>
                        <li>เลือก "สแกน QR" หรือ "พร้อมเพย์"</li>
                        <li>โอนเงินไปยังหมายเลขพร้อมเพย์ที่กำหนด</li>
                        <li>ใส่ยอดเงิน <span class="text-yellow-300 font-bold">฿{{ number_format($amount, 2) }}</span> (ต้องตรงทุกตัว)</li>
                        <li>ยืนยันการโอน - ระบบจะตรวจสอบอัตโนมัติ</li>
                    </ol>
                </div>
                @else
                <div class="bg-white/5 rounded-lg p-4">
                    <ol class="list-decimal list-inside space-y-2 text-purple-200">
                        <li>โอนเงินผ่าน Mobile Banking หรือ ATM</li>
                        <li>ใส่ยอดเงิน <span class="text-yellow-300 font-bold">฿{{ number_format($amount, 2) }}</span> (ต้องตรงทุกตัว)</li>
                        <li>ระบบจะตรวจสอบการโอนอัตโนมัติ</li>
                    </ol>
                </div>
                @endif
            </div>

            <!-- Status Indicator -->
            <div id="payment-status" class="bg-white/5 rounded-lg p-4 mb-4">
                <div class="flex items-center justify-center gap-3">
                    <div class="animate-spin rounded-full h-5 w-5 border-2 border-yellow-400 border-t-transparent"></div>
                    <span class="text-purple-200">กำลังรอรับการชำระเงิน...</span>
                </div>
            </div>

            <!-- Timer -->
            @if($expiredAt)
            <div class="text-center text-purple-200">
                <p class="text-sm">หมดอายุใน: <span id="countdown" class="text-yellow-300 font-mono"></span></p>
            </div>
            @endif
        </div>

        <!-- Transaction Info -->
        <div class="bg-white/5 backdrop-blur-sm border border-white/10 rounded-xl p-4 mb-6">
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-purple-300">รหัสรายการ</p>
                    <p class="text-white font-mono">{{ $transaction->transaction_id }}</p>
                </div>
                <div>
                    <p class="text-purple-300">สถานะ</p>
                    <p class="text-yellow-300 font-semibold">รอชำระเงิน</p>
                </div>
            </div>
        </div>

        <!-- Help -->
        <div class="text-center">
            <p class="text-purple-200 text-sm mb-2">มีปัญหาในการชำระเงิน?</p>
            <a href="{{ route('contact') ?? '#' }}" class="text-yellow-300 hover:text-yellow-200 text-sm underline">
                ติดต่อฝ่ายสนับสนุน
            </a>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const transactionId = {{ $transaction->id }};
    const statusUrl = '{{ route('tarot.payment.status', $transaction->id) }}';

    // Check payment status every 5 seconds
    const checkInterval = setInterval(function() {
        fetch(statusUrl)
            .then(response => response.json())
            .then(data => {
                if (data.completed && data.redirect_url) {
                    clearInterval(checkInterval);
                    document.getElementById('payment-status').innerHTML = `
                        <div class="flex items-center justify-center gap-3 text-green-400">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="font-bold">ชำระเงินสำเร็จ! กำลังพาไปหน้าผลทำนาย...</span>
                        </div>
                    `;
                    setTimeout(() => window.location.href = data.redirect_url, 2000);
                }
            })
            .catch(err => console.error('Error checking status:', err));
    }, 5000);

    // Countdown timer
    @if($expiredAt)
    const expiredAt = new Date('{{ $expiredAt->toIso8601String() }}').getTime();
    const countdownEl = document.getElementById('countdown');

    const countdownInterval = setInterval(function() {
        const now = new Date().getTime();
        const distance = expiredAt - now;

        if (distance < 0) {
            clearInterval(countdownInterval);
            countdownEl.textContent = 'หมดอายุแล้ว';
            countdownEl.classList.add('text-red-400');
            return;
        }

        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);
        countdownEl.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
    }, 1000);
    @endif
});
</script>
@endpush
@endsection
