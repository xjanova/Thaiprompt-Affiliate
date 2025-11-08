@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 mb-2">รายละเอียดการจอง</h1>
                    <p class="text-gray-600">เลขที่การจอง: <span class="font-mono font-bold">{{ $booking->booking_number }}</span></p>
                </div>

                <span class="px-4 py-2 rounded-full text-sm font-bold
                    @if($booking->status === 'confirmed') bg-green-100 text-green-800
                    @elseif($booking->status === 'pending') bg-yellow-100 text-yellow-800
                    @elseif($booking->status === 'cancelled') bg-red-100 text-red-800
                    @elseif($booking->status === 'completed') bg-blue-100 text-blue-800
                    @else bg-gray-100 text-gray-800
                    @endif">
                    @switch($booking->status)
                        @case('confirmed') ✓ ยืนยันแล้ว @break
                        @case('pending') ⏳ รอชำระเงิน @break
                        @case('cancelled') ✗ ยกเลิกแล้ว @break
                        @case('checked_in') 🏨 เช็คอินแล้ว @break
                        @case('checked_out') ✓ เช็คเอาท์แล้ว @break
                        @case('completed') ✓ เสร็จสิ้น @break
                        @default {{ $booking->status }}
                    @endswitch
                </span>
            </div>

            @if($booking->status === 'pending')
                <div class="mt-4 bg-yellow-50 border-l-4 border-yellow-400 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                <strong>กรุณาชำระเงินภายใน 30 นาที</strong> เพื่อยืนยันการจองของคุณ
                            </p>
                            <a href="{{ route('hotels.bookings.payment', $booking->booking_number) }}"
                               class="mt-2 inline-block bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded">
                                ชำระเงินเลย
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Hotel Information -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">ข้อมูลที่พัก</h2>

            <div class="flex gap-4">
                <img src="{{ $booking->hotel->main_image_url }}"
                     alt="{{ $booking->hotel->name }}"
                     class="w-32 h-32 object-cover rounded-lg">

                <div class="flex-1">
                    <h3 class="text-lg font-bold text-gray-900">{{ $booking->hotel->name }}</h3>
                    <div class="mt-2 space-y-1 text-sm text-gray-600">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                            </svg>
                            {{ $booking->hotel->address }}, {{ $booking->hotel->city }}
                        </div>
                        @if($booking->hotel->phone)
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
                                </svg>
                                {{ $booking->hotel->phone }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Booking Details -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">รายละเอียดการจอง</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="text-sm font-medium text-gray-500 mb-2">ห้องพัก</h4>
                    <p class="text-gray-900 font-medium">{{ $booking->roomType->name }}</p>
                    <p class="text-sm text-gray-600">{{ $booking->number_of_rooms }} ห้อง</p>
                </div>

                <div>
                    <h4 class="text-sm font-medium text-gray-500 mb-2">ผู้เข้าพัก</h4>
                    <p class="text-gray-900">
                        {{ $booking->number_of_adults }} ผู้ใหญ่
                        @if($booking->number_of_children > 0)
                            , {{ $booking->number_of_children }} เด็ก
                        @endif
                    </p>
                </div>

                <div>
                    <h4 class="text-sm font-medium text-gray-500 mb-2">วันเช็คอิน</h4>
                    <p class="text-gray-900 font-medium">{{ $booking->check_in_date->format('d F Y') }}</p>
                    <p class="text-sm text-gray-600">
                        เวลา {{ \Carbon\Carbon::parse($booking->hotel->check_in_time)->format('H:i') }} น.
                    </p>
                </div>

                <div>
                    <h4 class="text-sm font-medium text-gray-500 mb-2">วันเช็คเอาท์</h4>
                    <p class="text-gray-900 font-medium">{{ $booking->check_out_date->format('d F Y') }}</p>
                    <p class="text-sm text-gray-600">
                        เวลา {{ \Carbon\Carbon::parse($booking->hotel->check_out_time)->format('H:i') }} น.
                    </p>
                </div>

                <div>
                    <h4 class="text-sm font-medium text-gray-500 mb-2">จำนวนคืน</h4>
                    <p class="text-gray-900 font-medium">{{ $booking->number_of_nights }} คืน</p>
                </div>

                <div>
                    <h4 class="text-sm font-medium text-gray-500 mb-2">วันที่จอง</h4>
                    <p class="text-gray-900">{{ $booking->created_at->format('d F Y H:i') }} น.</p>
                </div>
            </div>

            @if($booking->special_requests)
                <div class="mt-4 pt-4 border-t">
                    <h4 class="text-sm font-medium text-gray-500 mb-2">คำขอพิเศษ</h4>
                    <p class="text-gray-700">{{ $booking->special_requests }}</p>
                </div>
            @endif
        </div>

        <!-- Guest Information -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">ข้อมูลผู้เข้าพัก</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <h4 class="text-sm font-medium text-gray-500">ชื่อ-นามสกุล</h4>
                    <p class="text-gray-900">{{ $booking->guest_name }}</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-500">อีเมล</h4>
                    <p class="text-gray-900">{{ $booking->guest_email }}</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-500">เบอร์โทรศัพท์</h4>
                    <p class="text-gray-900">{{ $booking->guest_phone }}</p>
                </div>
                @if($booking->guest_id_card)
                    <div>
                        <h4 class="text-sm font-medium text-gray-500">เลขบัตรประชาชน</h4>
                        <p class="text-gray-900">{{ $booking->guest_id_card }}</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Payment Information -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">ข้อมูลการชำระเงิน</h2>

            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">ค่าห้องพัก</span>
                    <span class="font-medium">฿{{ number_format($booking->subtotal) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">ภาษี (7%)</span>
                    <span class="font-medium">฿{{ number_format($booking->tax_amount) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">ค่าบริการ (10%)</span>
                    <span class="font-medium">฿{{ number_format($booking->service_charge) }}</span>
                </div>
                @if($booking->discount_amount > 0)
                    <div class="flex justify-between text-green-600">
                        <span>ส่วนลด</span>
                        <span class="font-medium">-฿{{ number_format($booking->discount_amount) }}</span>
                    </div>
                @endif
                <div class="flex justify-between pt-3 border-t text-lg font-bold">
                    <span>ยอดรวมทั้งหมด</span>
                    <span class="text-blue-600">฿{{ number_format($booking->total_amount) }}</span>
                </div>
            </div>

            <div class="mt-4 pt-4 border-t">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">สถานะการชำระเงิน</span>
                    <span class="px-3 py-1 rounded-full text-sm font-bold
                        {{ $booking->payment_status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                        {{ $booking->payment_status === 'paid' ? 'ชำระเงินแล้ว' : 'รอชำระเงิน' }}
                    </span>
                </div>

                @if($booking->payment_date)
                    <p class="text-sm text-gray-600 mt-2">
                        ชำระเมื่อ: {{ $booking->payment_date->format('d F Y H:i') }} น.
                    </p>
                @endif
            </div>
        </div>

        <!-- Actions -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('hotels.bookings.index') }}"
                   class="flex-1 text-center bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-3 px-6 rounded-lg">
                    ← กลับไปรายการจอง
                </a>

                @if($booking->status === 'pending')
                    <a href="{{ route('hotels.bookings.payment', $booking->booking_number) }}"
                       class="flex-1 text-center bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg">
                        ชำระเงิน
                    </a>
                @endif

                @if($booking->canBeCancelled())
                    <button onclick="confirmCancel()"
                            class="flex-1 bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-6 rounded-lg">
                        ยกเลิกการจอง
                    </button>
                @endif

                @if($booking->status === 'completed' && !$booking->review)
                    <a href="{{ route('hotels.reviews.create', $booking->booking_number) }}"
                       class="flex-1 text-center bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-3 px-6 rounded-lg">
                        ⭐ เขียนรีวิว
                    </a>
                @endif
            </div>
        </div>

        <!-- Cancellation Info -->
        @if($booking->status === 'cancelled')
            <div class="bg-red-50 border border-red-200 rounded-lg p-6">
                <h3 class="font-bold text-red-800 mb-2">ข้อมูลการยกเลิก</h3>
                <p class="text-sm text-red-700">
                    <strong>ยกเลิกเมื่อ:</strong> {{ $booking->cancelled_at->format('d F Y H:i') }} น.
                </p>
                @if($booking->cancellation_reason)
                    <p class="text-sm text-red-700 mt-2">
                        <strong>เหตุผล:</strong> {{ $booking->cancellation_reason }}
                    </p>
                @endif
                @if($booking->refund_amount > 0)
                    <p class="text-sm text-red-700 mt-2">
                        <strong>จำนวนเงินคืน:</strong> ฿{{ number_format($booking->refund_amount) }}
                    </p>
                @endif
            </div>
        @endif
    </div>
</div>

<!-- Cancel Modal -->
<div id="cancelModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <h3 class="text-lg font-bold text-gray-900 mb-4">ยืนยันการยกเลิกการจอง</h3>
        <p class="text-sm text-gray-600 mb-4">
            การยกเลิกการจองอาจมีค่าธรรมเนียมตามนโยบายของโรงแรม<br>
            คุณจะได้รับเงินคืน <strong>฿{{ number_format(app(\App\Services\HotelBookingService::class)->calculateRefund($booking)) }}</strong>
        </p>

        <form action="{{ route('hotels.bookings.cancel', $booking->booking_number) }}" method="POST">
            @csrf
            <textarea name="reason" rows="3" placeholder="เหตุผลในการยกเลิก (ถ้ามี)"
                      class="w-full rounded-lg border-gray-300 mb-4"></textarea>

            <div class="flex gap-2">
                <button type="button" onclick="closeCancelModal()"
                        class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded">
                    ยกเลิก
                </button>
                <button type="submit"
                        class="flex-1 bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                    ยืนยันการยกเลิก
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function confirmCancel() {
    document.getElementById('cancelModal').classList.remove('hidden');
}

function closeCancelModal() {
    document.getElementById('cancelModal').classList.add('hidden');
}
</script>
@endpush
@endsection
