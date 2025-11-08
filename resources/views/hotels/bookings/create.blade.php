@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-6xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">ยืนยันการจอง</h1>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Booking Form -->
            <div class="lg:col-span-2">
                <form action="{{ route('hotels.bookings.store') }}" method="POST" class="space-y-6">
                    @csrf

                    <input type="hidden" name="room_type_id" value="{{ $roomType->id }}">
                    <input type="hidden" name="check_in_date" value="{{ $bookingData['check_in'] }}">
                    <input type="hidden" name="check_out_date" value="{{ $bookingData['check_out'] }}">
                    <input type="hidden" name="number_of_rooms" value="{{ $bookingData['rooms'] ?? 1 }}">
                    <input type="hidden" name="number_of_adults" value="{{ $bookingData['adults'] ?? 2 }}">
                    <input type="hidden" name="number_of_children" value="{{ $bookingData['children'] ?? 0 }}">

                    <!-- Guest Information -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">ข้อมูลผู้เข้าพัก</h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    ชื่อ-นามสกุล <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="guest_name"
                                       value="{{ old('guest_name', auth()->user()->name) }}"
                                       class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                       required>
                                @error('guest_name')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    อีเมล <span class="text-red-500">*</span>
                                </label>
                                <input type="email" name="guest_email"
                                       value="{{ old('guest_email', auth()->user()->email) }}"
                                       class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                       required>
                                @error('guest_email')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    เบอร์โทรศัพท์ <span class="text-red-500">*</span>
                                </label>
                                <input type="tel" name="guest_phone"
                                       value="{{ old('guest_phone', auth()->user()->phone) }}"
                                       class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                       required>
                                @error('guest_phone')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    เลขบัตรประชาชน (ถ้ามี)
                                </label>
                                <input type="text" name="guest_id_card"
                                       value="{{ old('guest_id_card') }}"
                                       class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                       maxlength="13">
                            </div>
                        </div>
                    </div>

                    <!-- Special Requests -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">คำขอพิเศษ</h2>
                        <textarea name="special_requests" rows="4"
                                  class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                  placeholder="เช่น ต้องการห้องชั้นสูง, ต้องการเตียงเสริม, แพ้อาหาร...">{{ old('special_requests') }}</textarea>
                        <p class="text-sm text-gray-500 mt-2">
                            * คำขอพิเศษจะได้รับการพิจารณาตามความเหมาะสมและห้องพักที่มีอยู่
                        </p>
                    </div>

                    <!-- Promo Code -->
                    <div class="bg-white rounded-lg shadow-md p-6" x-data="{ showPromo: false }">
                        <button type="button" @click="showPromo = !showPromo"
                                class="text-blue-600 hover:text-blue-800 font-medium">
                            <span x-text="showPromo ? '- ซ่อนโค้ดส่วนลด' : '+ มีโค้ดส่วนลด?'"></span>
                        </button>

                        <div x-show="showPromo" x-collapse class="mt-4">
                            <div class="flex gap-2">
                                <input type="text" name="promo_code" value="{{ old('promo_code') }}"
                                       placeholder="กรอกโค้ดส่วนลด"
                                       class="flex-1 rounded-lg border-gray-300">
                                <button type="button" onclick="applyPromo()"
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">
                                    ใช้โค้ด
                                </button>
                            </div>

                            @if($offers && $offers->count() > 0)
                                <div class="mt-4">
                                    <p class="text-sm font-medium text-gray-700 mb-2">โปรโมชั่นที่ใช้ได้:</p>
                                    @foreach($offers as $offer)
                                        <div class="bg-green-50 border border-green-200 rounded p-3 mb-2">
                                            <div class="flex justify-between items-start">
                                                <div>
                                                    <p class="font-bold text-green-800">{{ $offer['name'] }}</p>
                                                    <p class="text-sm text-gray-600">{{ $offer['description'] }}</p>
                                                    @if($offer['code'])
                                                        <p class="text-sm text-green-600 mt-1">
                                                            โค้ด: <span class="font-mono font-bold">{{ $offer['code'] }}</span>
                                                        </p>
                                                    @endif
                                                </div>
                                                <span class="bg-green-600 text-white text-sm px-3 py-1 rounded-full">
                                                    -฿{{ number_format($offer['discount_amount']) }}
                                                </span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">วิธีการชำระเงิน</h2>

                        <div class="space-y-3">
                            <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50">
                                <input type="radio" name="payment_method" value="wallet"
                                       class="w-4 h-4 text-blue-600" required>
                                <div class="ml-3">
                                    <p class="font-medium">กระเป๋าเงิน (Wallet)</p>
                                    <p class="text-sm text-gray-500">ชำระผ่านกระเป๋าเงินในระบบ</p>
                                </div>
                            </label>

                            <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50">
                                <input type="radio" name="payment_method" value="promptpay"
                                       class="w-4 h-4 text-blue-600">
                                <div class="ml-3">
                                    <p class="font-medium">PromptPay</p>
                                    <p class="text-sm text-gray-500">สแกน QR Code ชำระเงิน</p>
                                </div>
                            </label>

                            <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50">
                                <input type="radio" name="payment_method" value="credit_card"
                                       class="w-4 h-4 text-blue-600">
                                <div class="ml-3">
                                    <p class="font-medium">บัตรเครดิต/เดบิต</p>
                                    <p class="text-sm text-gray-500">Visa, MasterCard, JCB</p>
                                </div>
                            </label>
                        </div>
                        @error('payment_method')
                            <p class="text-red-500 text-xs mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Terms -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <label class="flex items-start">
                            <input type="checkbox" name="accept_terms" value="1"
                                   class="mt-1 w-4 h-4 text-blue-600 rounded" required>
                            <span class="ml-2 text-sm text-gray-700">
                                ข้าพเจ้ายอมรับ
                                <a href="#" class="text-blue-600 hover:underline">ข้อกำหนดและเงื่อนไข</a>
                                และ
                                <a href="#" class="text-blue-600 hover:underline">นโยบายการยกเลิก</a>
                                ของที่พัก
                            </span>
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white text-lg font-bold py-4 rounded-lg transition">
                        ยืนยันการจองและชำระเงิน
                    </button>
                </form>
            </div>

            <!-- Booking Summary -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6 sticky top-4">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">สรุปการจอง</h2>

                    <!-- Hotel Info -->
                    <div class="mb-4 pb-4 border-b">
                        <img src="{{ $roomType->hotel->main_image_url }}" alt="{{ $roomType->hotel->name }}"
                             class="w-full h-32 object-cover rounded-lg mb-3">
                        <h3 class="font-bold text-gray-900">{{ $roomType->hotel->name }}</h3>
                        <p class="text-sm text-gray-600">{{ $roomType->hotel->city }}</p>
                    </div>

                    <!-- Room Info -->
                    <div class="mb-4 pb-4 border-b space-y-2 text-sm">
                        <div>
                            <span class="text-gray-600">ห้องพัก:</span>
                            <span class="font-medium">{{ $roomType->name }}</span>
                        </div>
                        <div>
                            <span class="text-gray-600">เช็คอิน:</span>
                            <span class="font-medium">{{ \Carbon\Carbon::parse($bookingData['check_in'])->format('d/m/Y') }}</span>
                        </div>
                        <div>
                            <span class="text-gray-600">เช็คเอาท์:</span>
                            <span class="font-medium">{{ \Carbon\Carbon::parse($bookingData['check_out'])->format('d/m/Y') }}</span>
                        </div>
                        <div>
                            <span class="text-gray-600">จำนวนคืน:</span>
                            <span class="font-medium">
                                {{ \Carbon\Carbon::parse($bookingData['check_in'])->diffInDays($bookingData['check_out']) }} คืน
                            </span>
                        </div>
                        <div>
                            <span class="text-gray-600">ผู้เข้าพัก:</span>
                            <span class="font-medium">
                                {{ $bookingData['adults'] ?? 2 }} ผู้ใหญ่
                                @if(isset($bookingData['children']) && $bookingData['children'] > 0)
                                    , {{ $bookingData['children'] }} เด็ก
                                @endif
                            </span>
                        </div>
                        <div>
                            <span class="text-gray-600">จำนวนห้อง:</span>
                            <span class="font-medium">{{ $bookingData['rooms'] ?? 1 }} ห้อง</span>
                        </div>
                    </div>

                    <!-- Price Breakdown -->
                    <div class="space-y-2 mb-4 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">ค่าห้องพัก</span>
                            <span class="font-medium">฿{{ number_format($pricing['subtotal']) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">ภาษี (7%)</span>
                            <span class="font-medium">฿{{ number_format($pricing['tax_amount']) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">ค่าบริการ (10%)</span>
                            <span class="font-medium">฿{{ number_format($pricing['service_charge']) }}</span>
                        </div>
                        @if($pricing['discount_amount'] > 0)
                            <div class="flex justify-between text-green-600">
                                <span>ส่วนลด</span>
                                <span class="font-medium">-฿{{ number_format($pricing['discount_amount']) }}</span>
                            </div>
                        @endif
                    </div>

                    <!-- Total -->
                    <div class="pt-4 border-t">
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-bold text-gray-900">ยอดรวมทั้งหมด</span>
                            <span class="text-2xl font-bold text-blue-600">
                                ฿{{ number_format($pricing['total_amount']) }}
                            </span>
                        </div>
                    </div>

                    <!-- Cancellation Policy -->
                    <div class="mt-4 pt-4 border-t">
                        <p class="text-xs text-gray-600">
                            <strong>นโยบายการยกเลิก:</strong><br>
                            • ยกเลิกฟรีก่อน 7 วัน: คืนเงิน 100%<br>
                            • ยกเลิก 3-7 วัน: คืนเงิน 50%<br>
                            • ยกเลิกน้อยกว่า 3 วัน: ไม่คืนเงิน
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function applyPromo() {
    const promoCode = document.querySelector('input[name="promo_code"]').value;
    if (!promoCode) {
        alert('กรุณากรอกโค้ดส่วนลด');
        return;
    }

    // TODO: Implement AJAX call to validate and apply promo code
    alert('กำลังตรวจสอบโค้ดส่วนลด...');
}
</script>
@endpush
@endsection
