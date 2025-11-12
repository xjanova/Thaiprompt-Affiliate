@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-8 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-yellow-100 dark:bg-yellow-900/20 rounded-full mb-4">
                <svg class="w-8 h-8 text-yellow-600 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white mb-2">รอการชำระเงิน</h1>
            <p class="text-gray-600 dark:text-gray-400">คำสั่งซื้อ #{{ $order->order_number }}</p>
        </div>

        <div class="space-y-6">
            <!-- PromptPay QR Code -->
            @if($order->payment_method === 'promptpay' && isset($paymentData['qr_code']))
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 p-6 text-center">
                    <h2 class="text-2xl font-bold text-white">📱 สแกน QR Code PromptPay</h2>
                    <p class="text-blue-100 mt-2">เปิดแอพธนาคารของคุณและสแกน QR Code ด้านล่าง</p>
                </div>

                <div class="p-8 text-center">
                    <!-- QR Code Display -->
                    <div class="inline-block p-6 bg-white rounded-2xl shadow-xl mb-6">
                        @if(isset($paymentData['qr_code_image']))
                        <img src="{{ $paymentData['qr_code_image'] }}" alt="PromptPay QR Code" class="w-64 h-64 mx-auto">
                        @else
                        <div class="w-64 h-64 bg-gray-100 dark:bg-gray-700 rounded-xl flex items-center justify-center">
                            <p class="text-sm text-gray-500 dark:text-gray-400">QR Code จะแสดงที่นี่</p>
                        </div>
                        @endif
                    </div>

                    <!-- Amount -->
                    <div class="mb-6">
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">ยอดชำระ</p>
                        <p class="text-4xl font-black text-gray-900 dark:text-white">฿{{ number_format($order->total_amount, 2) }}</p>
                    </div>

                    <!-- Reference Number -->
                    @if(isset($paymentData['ref_no']))
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-6">
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">เลขอ้างอิง</p>
                        <p class="text-xl font-mono font-bold text-blue-600">{{ $paymentData['ref_no'] }}</p>
                    </div>
                    @endif

                    <!-- Instructions -->
                    <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-6 text-left">
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-3">วิธีการชำระเงิน:</h3>
                        <ol class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                            <li class="flex items-start">
                                <span class="flex-shrink-0 w-6 h-6 bg-blue-100 dark:bg-blue-900/50 rounded-full flex items-center justify-center text-blue-600 font-semibold text-xs mr-3">1</span>
                                <span>เปิดแอพธนาคารหรือ Mobile Banking ของคุณ</span>
                            </li>
                            <li class="flex items-start">
                                <span class="flex-shrink-0 w-6 h-6 bg-blue-100 dark:bg-blue-900/50 rounded-full flex items-center justify-center text-blue-600 font-semibold text-xs mr-3">2</span>
                                <span>เลือกเมนู "สแกน QR" หรือ "PromptPay"</span>
                            </li>
                            <li class="flex items-start">
                                <span class="flex-shrink-0 w-6 h-6 bg-blue-100 dark:bg-blue-900/50 rounded-full flex items-center justify-center text-blue-600 font-semibold text-xs mr-3">3</span>
                                <span>สแกน QR Code ด้านบน</span>
                            </li>
                            <li class="flex items-start">
                                <span class="flex-shrink-0 w-6 h-6 bg-blue-100 dark:bg-blue-900/50 rounded-full flex items-center justify-center text-blue-600 font-semibold text-xs mr-3">4</span>
                                <span>ตรวจสอบยอดเงินและยืนยันการชำระเงิน</span>
                            </li>
                        </ol>
                    </div>
                </div>
            </div>
            @endif

            <!-- Bank Transfer -->
            @if($order->payment_method === 'bank_transfer')
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="bg-gradient-to-r from-green-500 to-green-600 p-6 text-center">
                    <h2 class="text-2xl font-bold text-white">🏦 โอนเงินผ่านธนาคาร</h2>
                    <p class="text-green-100 mt-2">โอนเงินไปยังบัญชีธนาคารด้านล่าง</p>
                </div>

                <div class="p-8">
                    <!-- Amount -->
                    <div class="text-center mb-8">
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">ยอดชำระ</p>
                        <p class="text-4xl font-black text-gray-900 dark:text-white">฿{{ number_format($order->total_amount, 2) }}</p>
                        <p class="text-xs text-red-600 dark:text-red-400 mt-2">⚠️ โปรดโอนตามยอดที่ระบุเท่านั้น</p>
                    </div>

                    <!-- Bank Account Info -->
                    <div class="bg-gray-50 dark:bg-gray-900 rounded-xl p-6 mb-6">
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-4">ข้อมูลบัญชีธนาคาร:</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                                <span class="text-sm text-gray-600 dark:text-gray-400">ธนาคาร:</span>
                                <span class="font-semibold text-gray-900 dark:text-white">ธนาคารกสิกรไทย (KBANK)</span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                                <span class="text-sm text-gray-600 dark:text-gray-400">ชื่อบัญชี:</span>
                                <span class="font-semibold text-gray-900 dark:text-white">บริษัท ไทยพรอมท์ จำกัด</span>
                            </div>
                            <div class="flex justify-between items-center py-2">
                                <span class="text-sm text-gray-600 dark:text-gray-400">เลขที่บัญชี:</span>
                                <div class="flex items-center gap-2">
                                    <span class="font-mono font-bold text-lg text-gray-900 dark:text-white">123-4-56789-0</span>
                                    <button onclick="copyBankAccount()" class="text-indigo-600 hover:text-indigo-700">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Instructions -->
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 mb-6">
                        <h4 class="font-semibold text-yellow-800 dark:text-yellow-300 mb-2">📋 สิ่งที่ต้องทำหลังโอนเงิน:</h4>
                        <ol class="space-y-1 text-sm text-yellow-700 dark:text-yellow-400">
                            <li>1. เก็บหลักฐานการโอนเงิน (Slip)</li>
                            <li>2. ส่งหลักฐานผ่าน Line: @thaiprompt</li>
                            <li>3. แจ้งเลขที่คำสั่งซื้อ: <span class="font-semibold">{{ $order->order_number }}</span></li>
                        </ol>
                    </div>
                </div>
            </div>

            <script>
            function copyBankAccount() {
                const accountNumber = '123-4-56789-0';
                navigator.clipboard.writeText(accountNumber).then(() => {
                    alert('คัดลอกเลขที่บัญชีแล้ว!');
                });
            }
            </script>
            @endif

            <!-- Status & Timer -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-900 dark:text-white">สถานะการชำระเงิน</h3>
                    <span class="px-3 py-1 rounded-full text-sm font-semibold
                        {{ $transaction->status === 'pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400' : '' }}
                        {{ $transaction->status === 'processing' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400' : '' }}">
                        {{ $transaction->status === 'pending' ? 'รอชำระเงิน' : ($transaction->status === 'processing' ? 'กำลังตรวจสอบ' : $transaction->status) }}
                    </span>
                </div>

                <div class="text-center py-6">
                    <div class="inline-flex items-center justify-center w-20 h-20 bg-yellow-100 dark:bg-yellow-900/20 rounded-full mb-4">
                        <svg class="w-10 h-10 text-yellow-600 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </div>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white mb-2">กำลังรอการชำระเงิน</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">เมื่อคุณชำระเงินสำเร็จ ระบบจะอัพเดทอัตโนมัติ</p>
                </div>

                @if($transaction->expired_at)
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-red-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-sm font-medium text-red-800 dark:text-red-300">หมดอายุ:</span>
                        </div>
                        <span class="text-sm font-semibold text-red-800 dark:text-red-300">{{ $transaction->expired_at->format('d/m/Y H:i') }} น.</span>
                    </div>
                </div>
                @endif
            </div>

            <!-- Actions -->
            <div class="flex flex-col sm:flex-row gap-4">
                <a href="{{ route('orders.show', $order->id) }}"
                   class="flex-1 py-3 px-4 rounded-lg font-semibold text-center text-indigo-600 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-900/20 dark:hover:bg-indigo-900/40 transition">
                    ดูคำสั่งซื้อ
                </a>
                <a href="{{ route('home') }}"
                   class="flex-1 py-3 px-4 rounded-lg font-semibold text-center text-gray-700 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 transition">
                    กลับหน้าแรก
                </a>
            </div>

            <!-- Auto Refresh Info -->
            <div class="text-center">
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    หน้านี้จะรีเฟรชอัตโนมัติทุก 30 วินาที เพื่อตรวจสอบสถานะการชำระเงิน
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Auto Refresh Script -->
<script>
// Auto refresh every 30 seconds to check payment status
setTimeout(() => {
    window.location.reload();
}, 30000);

// Check payment status via AJAX every 10 seconds
setInterval(async () => {
    try {
        const response = await fetch('{{ route("orders.show", $order->id) }}', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });

        if (response.ok) {
            const data = await response.json();
            if (data.order && data.order.payment_status === 'paid') {
                // Payment completed, redirect to success page
                window.location.href = '{{ route("checkout.success", $order->id) }}';
            }
        }
    } catch (error) {
        console.error('Failed to check payment status:', error);
    }
}, 10000);
</script>
@endsection
