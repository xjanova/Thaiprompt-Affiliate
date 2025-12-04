@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">แก้ไขการจอง</h1>
            <p class="text-gray-600 dark:text-gray-400">หมายเลขการจอง: <span class="font-bold">{{ $booking->booking_number }}</span></p>
        </div>

        <!-- Booking Info -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
            <div class="flex gap-4">
                <img src="{{ $booking->hotel->main_image_url }}" alt="{{ $booking->hotel->name }}"
                     class="w-32 h-32 object-cover rounded-lg">
                <div class="flex-1">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ $booking->hotel->name }}</h2>
                    <p class="text-gray-600 dark:text-gray-400">{{ $booking->roomType->name }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                        {{ $booking->hotel->city }}, {{ $booking->hotel->country }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Edit Form -->
        <form action="{{ route('hotels.bookings.update', $booking->booking_number) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Date Selection -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">เปลี่ยนวันที่เข้าพัก</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            วันเช็คอิน <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="check_in_date"
                               value="{{ old('check_in_date', $booking->check_in_date->format('Y-m-d')) }}"
                               min="{{ date('Y-m-d') }}"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500"
                               required>
                        @error('check_in_date')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            วันเช็คเอาท์ <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="check_out_date"
                               value="{{ old('check_out_date', $booking->check_out_date->format('Y-m-d')) }}"
                               min="{{ date('Y-m-d', strtotime('+1 day')) }}"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500"
                               required>
                        @error('check_out_date')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <p class="text-sm text-blue-800 dark:text-blue-300">
                        <strong>หมายเหตุ:</strong> การเปลี่ยนวันที่อาจมีค่าใช้จ่ายเพิ่มเติม หรือคืนเงินส่วนต่าง ขึ้นอยู่กับราคาห้องพักในช่วงวันที่ใหม่
                    </p>
                </div>
            </div>

            <!-- Guest Information -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">ข้อมูลผู้เข้าพัก</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            จำนวนผู้ใหญ่ <span class="text-red-500">*</span>
                        </label>
                        <select name="number_of_adults" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500" required>
                            @for($i = 1; $i <= $booking->roomType->max_adults; $i++)
                                <option value="{{ $i }}" {{ old('number_of_adults', $booking->number_of_adults) == $i ? 'selected' : '' }}>
                                    {{ $i }} คน
                                </option>
                            @endfor
                        </select>
                        @error('number_of_adults')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            จำนวนเด็ก
                        </label>
                        <select name="number_of_children" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500">
                            @for($i = 0; $i <= $booking->roomType->max_children; $i++)
                                <option value="{{ $i }}" {{ old('number_of_children', $booking->number_of_children) == $i ? 'selected' : '' }}>
                                    {{ $i }} คน
                                </option>
                            @endfor
                        </select>
                        @error('number_of_children')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Special Requests -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">คำขอพิเศษ</h3>
                <textarea name="special_requests" rows="4"
                          class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500"
                          placeholder="เช่น ต้องการห้องชั้นสูง, ต้องการเตียงเสริม, แพ้อาหาร...">{{ old('special_requests', $booking->special_requests) }}</textarea>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                    * คำขอพิเศษจะได้รับการพิจารณาตามความเหมาะสมและห้องพักที่มีอยู่
                </p>
            </div>

            <!-- Important Notice -->
            <div class="bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-800 rounded-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-3">⚠️ สิ่งสำคัญที่ควรทราบ</h3>
                <ul class="list-disc list-inside space-y-2 text-sm text-gray-700 dark:text-gray-300">
                    <li>การแก้ไขการจองอาจมีค่าใช้จ่ายเพิ่มเติม หรือคืนเงินส่วนต่าง</li>
                    <li>ห้องพักอาจไม่ว่างในวันที่คุณต้องการเปลี่ยน</li>
                    <li>การเปลี่ยนแปลงต้องได้รับการยืนยันจากโรงแรม</li>
                    <li>ราคาห้องพักอาจเปลี่ยนแปลงตามช่วงเวลาที่จอง</li>
                </ul>
            </div>

            <!-- Current Booking Summary -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">รายละเอียดการจองปัจจุบัน</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">เช็คอิน:</span>
                        <span class="font-medium dark:text-white">{{ $booking->check_in_date->format('d/m/Y') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">เช็คเอาท์:</span>
                        <span class="font-medium dark:text-white">{{ $booking->check_out_date->format('d/m/Y') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">จำนวนคืน:</span>
                        <span class="font-medium dark:text-white">{{ $booking->nights }} คืน</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">ผู้เข้าพัก:</span>
                        <span class="font-medium dark:text-white">{{ $booking->number_of_adults }} ผู้ใหญ่, {{ $booking->number_of_children }} เด็ก</span>
                    </div>
                    <div class="flex justify-between pt-2 border-t dark:border-gray-700">
                        <span class="text-gray-600 dark:text-gray-400">ยอดรวม:</span>
                        <span class="text-xl font-bold text-blue-600 dark:text-blue-400">฿{{ number_format($booking->total_amount) }}</span>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-3">
                <a href="{{ route('hotels.bookings.show', $booking->booking_number) }}"
                   class="flex-1 text-center bg-gray-200 hover:bg-gray-300 dark:bg-gray-600 dark:hover:bg-gray-500 text-gray-800 dark:text-white font-bold py-3 px-6 rounded-lg transition">
                    ยกเลิก
                </a>
                <button type="submit"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white font-bold py-3 px-6 rounded-lg transition">
                    บันทึกการเปลี่ยนแปลง
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
// Auto-update nights calculation
document.addEventListener('DOMContentLoaded', function() {
    const checkIn = document.querySelector('input[name="check_in_date"]');
    const checkOut = document.querySelector('input[name="check_out_date"]');

    function updateMinCheckOut() {
        if (checkIn.value) {
            const minDate = new Date(checkIn.value);
            minDate.setDate(minDate.getDate() + 1);
            checkOut.min = minDate.toISOString().split('T')[0];

            if (checkOut.value && checkOut.value <= checkIn.value) {
                checkOut.value = checkOut.min;
            }
        }
    }

    checkIn.addEventListener('change', updateMinCheckOut);
});
</script>
@endpush
@endsection
