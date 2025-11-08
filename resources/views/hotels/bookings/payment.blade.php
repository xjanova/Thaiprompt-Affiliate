@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">ชำระเงิน</h1>
        <p class="text-gray-600 mb-8">เลขที่การจอง: <span class="font-mono font-bold">{{ $booking->booking_number }}</span></p>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Payment Methods -->
            <div class="lg:col-span-2">
                <form action="{{ route('hotels.bookings.process-payment', $booking->booking_number) }}" method="POST" id="paymentForm">
                    @csrf

                    <!-- Timer -->
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                        <div class="flex items-center">
                            <svg class="h-6 w-6 text-yellow-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                            </svg>
                            <div>
                                <p class="font-bold text-yellow-800">กรุณาชำระเงินภายใน</p>
                                <p class="text-2xl font-bold text-yellow-900" id="timer">30:00</p>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Methods -->
                    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">เลือกวิธีการชำระเงิน</h2>

                        <div class="space-y-3">
                            <!-- Wallet -->
                            <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50">
                                <input type="radio" name="payment_method" value="wallet" class="w-5 h-5 text-blue-600" required>
                                <div class="ml-4 flex-1">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="font-bold text-gray-900">💰 กระเป๋าเงิน (Wallet)</p>
                                            <p class="text-sm text-gray-600">ยอดคงเหลือ: ฿{{ number_format(auth()->user()->wallet->balance ?? 0) }}</p>
                                        </div>
                                        <span class="text-green-600 font-bold">แนะนำ</span>
                                    </div>
                                </div>
                            </label>

                            <!-- PromptPay -->
                            <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50">
                                <input type="radio" name="payment_method" value="promptpay" class="w-5 h-5 text-blue-600">
                                <div class="ml-4 flex-1">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="font-bold text-gray-900">📱 PromptPay</p>
                                            <p class="text-sm text-gray-600">สแกน QR Code ผ่านแอปธนาคาร</p>
                                        </div>
                                        <img src="https://cdn.jsdelivr.net/npm/simple-icons@v5/icons/line.svg" alt="PromptPay" class="h-8">
                                    </div>
                                </div>
                            </label>

                            <!-- Credit Card -->
                            <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50">
                                <input type="radio" name="payment_method" value="credit_card" class="w-5 h-5 text-blue-600">
                                <div class="ml-4 flex-1">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="font-bold text-gray-900">💳 บัตรเครดิต/เดบิต</p>
                                            <p class="text-sm text-gray-600">Visa, MasterCard, JCB</p>
                                        </div>
                                        <div class="flex gap-2">
                                            <img src="https://upload.wikimedia.org/wikipedia/commons/5/5e/Visa_Inc._logo.svg" alt="Visa" class="h-6">
                                            <img src="https://upload.wikimedia.org/wikipedia/commons/2/2a/Mastercard-logo.svg" alt="MasterCard" class="h-6">
                                        </div>
                                    </div>
                                </div>
                            </label>

                            <!-- Bank Transfer -->
                            <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50">
                                <input type="radio" name="payment_method" value="bank_transfer" class="w-5 h-5 text-blue-600">
                                <div class="ml-4 flex-1">
                                    <p class="font-bold text-gray-900">🏦 โอนเงินผ่านธนาคาร</p>
                                    <p class="text-sm text-gray-600">ต้องรอการตรวจสอบ 1-2 ชั่วโมง</p>
                                </div>
                            </label>
                        </div>

                        @error('payment_method')
                            <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Terms -->
                    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                        <label class="flex items-start">
                            <input type="checkbox" name="accept_terms" required class="mt-1 w-5 h-5 text-blue-600 rounded">
                            <span class="ml-3 text-sm text-gray-700">
                                ข้าพเจ้ายืนยันว่าข้อมูลทั้งหมดถูกต้องและยอมรับ
                                <a href="#" class="text-blue-600 hover:underline">ข้อกำหนดและเงื่อนไข</a>
                                และ
                                <a href="#" class="text-blue-600 hover:underline">นโยบายการคืนเงิน</a>
                            </span>
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white text-lg font-bold py-4 rounded-lg transition">
                        💳 ชำระเงิน ฿{{ number_format($booking->total_amount) }}
                    </button>
                </form>
            </div>

            <!-- Order Summary -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6 sticky top-4">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">สรุปการจอง</h3>

                    <!-- Hotel -->
                    <div class="mb-4 pb-4 border-b">
                        <img src="{{ $booking->hotel->main_image_url }}" alt="{{ $booking->hotel->name }}"
                             class="w-full h-32 object-cover rounded-lg mb-2">
                        <h4 class="font-bold">{{ $booking->hotel->name }}</h4>
                        <p class="text-sm text-gray-600">{{ $booking->roomType->name }}</p>
                    </div>

                    <!-- Details -->
                    <div class="space-y-2 text-sm mb-4 pb-4 border-b">
                        <div class="flex justify-between">
                            <span class="text-gray-600">เช็คอิน</span>
                            <span class="font-medium">{{ $booking->check_in_date->format('d/m/Y') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">เช็คเอาท์</span>
                            <span class="font-medium">{{ $booking->check_out_date->format('d/m/Y') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">จำนวนคืน</span>
                            <span class="font-medium">{{ $booking->number_of_nights }} คืน</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">จำนวนห้อง</span>
                            <span class="font-medium">{{ $booking->number_of_rooms }} ห้อง</span>
                        </div>
                    </div>

                    <!-- Price Breakdown -->
                    <div class="space-y-2 text-sm mb-4">
                        <div class="flex justify-between">
                            <span class="text-gray-600">ค่าห้องพัก</span>
                            <span>฿{{ number_format($booking->subtotal) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">ภาษี</span>
                            <span>฿{{ number_format($booking->tax_amount) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">ค่าบริการ</span>
                            <span>฿{{ number_format($booking->service_charge) }}</span>
                        </div>
                        @if($booking->discount_amount > 0)
                            <div class="flex justify-between text-green-600">
                                <span>ส่วนลด</span>
                                <span>-฿{{ number_format($booking->discount_amount) }}</span>
                            </div>
                        @endif
                    </div>

                    <!-- Total -->
                    <div class="pt-4 border-t">
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-bold">ยอดรวม</span>
                            <span class="text-2xl font-bold text-blue-600">
                                ฿{{ number_format($booking->total_amount) }}
                            </span>
                        </div>
                    </div>

                    <!-- Security -->
                    <div class="mt-6 pt-6 border-t">
                        <div class="flex items-center text-xs text-gray-600">
                            <svg class="w-4 h-4 mr-2 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>การชำระเงินปลอดภัยด้วย SSL</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Payment countdown timer
let timeLeft = 30 * 60; // 30 minutes
const timerElement = document.getElementById('timer');

function updateTimer() {
    const minutes = Math.floor(timeLeft / 60);
    const seconds = timeLeft % 60;
    timerElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;

    if (timeLeft <= 0) {
        alert('หมดเวลาชำระเงิน กรุณาทำการจองใหม่อีกครั้ง');
        window.location.href = '{{ route("hotels.bookings.index") }}';
        return;
    }

    timeLeft--;
}

setInterval(updateTimer, 1000);
updateTimer();

// Form submission
document.getElementById('paymentForm').addEventListener('submit', function(e) {
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<svg class="animate-spin h-5 w-5 mr-3 inline-block" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> กำลังดำเนินการ...';
});
</script>
@endpush
@endsection
