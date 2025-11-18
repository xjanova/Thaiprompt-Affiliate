@extends('layouts.admin-v3')

@section('title', 'สร้างการจองใหม่')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 via-rose-50 to-orange-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 p-6">
    <!-- Language Switcher -->
    <div class="flex justify-end mb-4" x-data="{ open: false }">
        <div class="relative">
            <button @click="open = !open" class="flex items-center space-x-2 px-4 py-2 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 hover:shadow-xl transition-all">
                <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                </svg>
                <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">ภาษา</span>
            </button>
            <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700 py-2 z-50">
                <a href="#" class="flex items-center px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                    <span class="text-2xl mr-3">🇹🇭</span>
                    <span class="text-gray-700 dark:text-gray-200">ไทย</span>
                </a>
                <a href="#" class="flex items-center px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                    <span class="text-2xl mr-3">🇬🇧</span>
                    <span class="text-gray-700 dark:text-gray-200">English</span>
                </a>
                <a href="#" class="flex items-center px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                    <span class="text-2xl mr-3">🇨🇳</span>
                    <span class="text-gray-700 dark:text-gray-200">中文</span>
                </a>
                <a href="#" class="flex items-center px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                    <span class="text-2xl mr-3">🇯🇵</span>
                    <span class="text-gray-700 dark:text-gray-200">日本語</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Gradient Header -->
    <div class="relative overflow-hidden bg-gradient-to-br from-rose-600 via-orange-600 to-pink-600 rounded-3xl shadow-2xl p-8 mb-8">
        <div class="absolute inset-0 bg-black/10"></div>
        <div class="absolute inset-0 bg-white/5 backdrop-blur-sm"></div>

        <div class="relative flex items-center justify-between">
            <div class="flex items-center">
                <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center mr-4">
                    <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-4xl font-bold text-white mb-2" data-translate>สร้างการจองใหม่</h1>
                    <p class="text-rose-100" data-translate>เพิ่มการจองโรงแรมด้วยตนเอง</p>
                </div>
            </div>

            <a href="{{ route('admin.hotels.bookings.index') }}"
               class="relative overflow-hidden group px-6 py-3 bg-white/20 backdrop-blur-sm hover:bg-white/30 rounded-xl transition-all text-white font-semibold flex items-center shadow-lg">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span data-translate>กลับ</span>
            </a>
        </div>
    </div>

    <form action="{{ route('admin.hotels.bookings.store') }}" method="POST" class="space-y-6">
        @csrf

        <!-- Hotel & Room Selection Section -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="bg-gradient-to-r from-rose-500 to-orange-600 px-6 py-4">
                <h2 class="text-xl font-bold text-white flex items-center">
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <span data-translate>โรงแรมและห้องพัก</span>
                </h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2" data-translate>
                            เลือกโรงแรม <span class="text-red-500">*</span>
                        </label>
                        <select name="hotel_id" required
                                class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-rose-500 dark:focus:ring-rose-400 focus:border-transparent text-gray-900 dark:text-white transition">
                            <option value="" data-translate>-- เลือกโรงแรม --</option>
                            @foreach($hotels as $hotel)
                                <option value="{{ $hotel->id }}">{{ $hotel->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2" data-translate>
                            ประเภทห้อง <span class="text-red-500">*</span>
                        </label>
                        <select name="room_type_id" required
                                class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-rose-500 dark:focus:ring-rose-400 focus:border-transparent text-gray-900 dark:text-white transition">
                            <option value="" data-translate>-- เลือกประเภทห้อง --</option>
                            @foreach($roomTypes as $roomType)
                                <option value="{{ $roomType->id }}">{{ $roomType->name }} (฿{{ number_format($roomType->price_per_night, 2) }}/คืน)</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2" data-translate>
                            จำนวนห้อง <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="rooms_count" min="1" value="1" required
                               class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-rose-500 dark:focus:ring-rose-400 focus:border-transparent text-gray-900 dark:text-white transition">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2" data-translate>
                            Booking Number <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="booking_number" value="{{ 'BK-' . strtoupper(uniqid()) }}" required
                               class="w-full px-4 py-3 bg-gray-100 dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-rose-500 dark:focus:ring-rose-400 focus:border-transparent text-gray-900 dark:text-white transition" readonly>
                    </div>
                </div>
            </div>
        </div>

        <!-- Check-in/Check-out Dates Section -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="bg-gradient-to-r from-blue-500 to-indigo-600 px-6 py-4">
                <h2 class="text-xl font-bold text-white flex items-center">
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span data-translate>วันที่เข้าพัก</span>
                </h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2" data-translate>
                            วันเช็คอิน <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="check_in_date" required
                               class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent text-gray-900 dark:text-white transition">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2" data-translate>
                            วันเช็คเอาท์ <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="check_out_date" required
                               class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent text-gray-900 dark:text-white transition">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2" data-translate>
                            จำนวนคืน
                        </label>
                        <div class="px-4 py-3 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border-2 border-blue-200 dark:border-blue-800 rounded-xl">
                            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400" id="nights-display">0 <span class="text-sm font-normal" data-translate>คืน</span></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Guest Information Section -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="bg-gradient-to-r from-purple-500 to-pink-600 px-6 py-4">
                <h2 class="text-xl font-bold text-white flex items-center">
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <span data-translate>ข้อมูลผู้เข้าพัก</span>
                </h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2" data-translate>
                            ชื่อผู้เข้าพัก <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="guest_name" required
                               class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-transparent text-gray-900 dark:text-white transition">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2" data-translate>
                            อีเมลผู้เข้าพัก <span class="text-red-500">*</span>
                        </label>
                        <input type="email" name="guest_email" required
                               class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-transparent text-gray-900 dark:text-white transition">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2" data-translate>
                            เบอร์โทรผู้เข้าพัก <span class="text-red-500">*</span>
                        </label>
                        <input type="tel" name="guest_phone" required
                               class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-transparent text-gray-900 dark:text-white transition">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2" data-translate>
                            เลือกผู้ใช้ (ถ้ามี)
                        </label>
                        <select name="user_id"
                                class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-transparent text-gray-900 dark:text-white transition">
                            <option value="" data-translate>-- เลือกผู้ใช้ --</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2" data-translate>
                            จำนวนผู้ใหญ่ <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="adults" min="1" value="2" required
                               class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-transparent text-gray-900 dark:text-white transition">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2" data-translate>
                            จำนวนเด็ก
                        </label>
                        <input type="number" name="children" min="0" value="0"
                               class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-transparent text-gray-900 dark:text-white transition">
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment & Status Section -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="bg-gradient-to-r from-green-500 to-emerald-600 px-6 py-4">
                <h2 class="text-xl font-bold text-white flex items-center">
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                    <span data-translate>การชำระเงินและสถานะ</span>
                </h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2" data-translate>
                            วิธีชำระเงิน <span class="text-red-500">*</span>
                        </label>
                        <select name="payment_method" required
                                class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-green-500 dark:focus:ring-green-400 focus:border-transparent text-gray-900 dark:text-white transition">
                            <option value="cash" data-translate>เงินสด</option>
                            <option value="credit_card" data-translate>บัตรเครดิต</option>
                            <option value="bank_transfer" data-translate>โอนเงิน</option>
                            <option value="promptpay" data-translate>พร้อมเพย์</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2" data-translate>
                            สถานะการชำระเงิน <span class="text-red-500">*</span>
                        </label>
                        <select name="payment_status" required
                                class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-green-500 dark:focus:ring-green-400 focus:border-transparent text-gray-900 dark:text-white transition">
                            <option value="pending" data-translate>รอชำระเงิน</option>
                            <option value="paid" data-translate>ชำระแล้ว</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2" data-translate>
                            สถานะการจอง <span class="text-red-500">*</span>
                        </label>
                        <select name="status" required
                                class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-green-500 dark:focus:ring-green-400 focus:border-transparent text-gray-900 dark:text-white transition">
                            <option value="pending" data-translate>รอชำระเงิน</option>
                            <option value="confirmed" data-translate>ยืนยันแล้ว</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Special Requests Section -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="bg-gradient-to-r from-amber-500 to-orange-600 px-6 py-4">
                <h2 class="text-xl font-bold text-white flex items-center">
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                    </svg>
                    <span data-translate>คำขอพิเศษ</span>
                </h2>
            </div>
            <div class="p-6">
                <textarea name="special_requests" rows="4"
                          class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-amber-500 dark:focus:ring-amber-400 focus:border-transparent text-gray-900 dark:text-white transition resize-none"
                          placeholder="กรอกคำขอพิเศษ เช่น ชั้นสูง, เตียงเสริม, ห้องปลอดบุหรี่ ฯลฯ"></textarea>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-end items-center space-x-4">
            <a href="{{ route('admin.hotels.bookings.index') }}"
               class="px-8 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                <span data-translate>ยกเลิก</span>
            </a>
            <button type="submit"
                    class="px-8 py-3 bg-gradient-to-r from-rose-600 to-pink-600 hover:from-rose-700 hover:to-pink-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span data-translate>สร้างการจอง</span>
            </button>
        </div>
    </form>
</div>

<script>
/**
 * คำนวณจำนวนคืนจากวันเช็คอินและเช็คเอาท์
 */
document.addEventListener('DOMContentLoaded', function() {
    const checkInInput = document.querySelector('input[name="check_in_date"]');
    const checkOutInput = document.querySelector('input[name="check_out_date"]');
    const nightsDisplay = document.getElementById('nights-display');

    function calculateNights() {
        const checkIn = new Date(checkInInput.value);
        const checkOut = new Date(checkOutInput.value);

        if (checkIn && checkOut && checkOut > checkIn) {
            const diffTime = Math.abs(checkOut - checkIn);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            nightsDisplay.innerHTML = `${diffDays} <span class="text-sm font-normal" data-translate>คืน</span>`;
        } else {
            nightsDisplay.innerHTML = `0 <span class="text-sm font-normal" data-translate>คืน</span>`;
        }
    }

    checkInInput.addEventListener('change', calculateNights);
    checkOutInput.addEventListener('change', calculateNights);
});
</script>
@endsection
