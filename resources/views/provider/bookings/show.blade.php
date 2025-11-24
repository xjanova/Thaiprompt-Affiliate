@extends('layouts.app')

@section('title', 'รายละเอียดงาน')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8" x-data="providerBookingDetail()">
    <div class="mb-6">
        <a href="{{ route('provider.bookings.index') }}" class="text-purple-600 hover:text-purple-700">
            <i class="fas fa-arrow-left mr-2"></i>กลับไปหน้างาน
        </a>
    </div>

    {{-- Header --}}
    <div class="backdrop-blur-xl bg-white/90 dark:bg-gray-800/90 rounded-2xl shadow-xl p-6 mb-6">
        <div class="flex items-start justify-between mb-4">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-xl bg-gradient-to-br from-purple-400 to-pink-400 flex items-center justify-center text-3xl">
                    {{ $booking->service->category->icon ?? '🔧' }}
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $booking->service->name }}</h1>
                    <p class="text-gray-600 dark:text-gray-400">{{ $booking->booking_number }}</p>
                </div>
            </div>
            <x-booking-status-badge :status="$booking->status" />
        </div>

        {{-- Quick Actions --}}
        <div class="flex flex-wrap gap-3">
            @if(in_array($booking->status, ['waiting_provider', 'notifying_provider']))
                <button @click="acceptBooking()" :disabled="processing"
                        class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-xl font-semibold shadow-lg transition-all disabled:opacity-50">
                    <i class="fas fa-check mr-2"></i>รับงานนี้
                </button>
                <button @click="showRejectModal = true"
                        class="px-6 py-3 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-xl font-semibold transition-all">
                    <i class="fas fa-times mr-2"></i>ปฏิเสธ
                </button>
            @elseif($booking->status === 'provider_accepted')
                <form action="{{ route('provider.bookings.start-journey', $booking) }}" method="POST">
                    @csrf
                    <button type="submit" class="px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-xl font-semibold shadow-lg transition-all">
                        <i class="fas fa-route mr-2"></i>เริ่มเดินทาง
                    </button>
                </form>
            @elseif($booking->status === 'provider_on_way')
                <form action="{{ route('provider.bookings.start-service', $booking) }}" method="POST">
                    @csrf
                    <button type="submit" class="px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-xl font-semibold shadow-lg transition-all">
                        <i class="fas fa-play mr-2"></i>เริ่มบริการ
                    </button>
                </form>
            @elseif($booking->status === 'in_progress')
                <form action="{{ route('provider.bookings.complete', $booking) }}" method="POST">
                    @csrf
                    <button type="submit" class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-xl font-semibold shadow-lg transition-all">
                        <i class="fas fa-check-double mr-2"></i>เสร็จสิ้น
                    </button>
                </form>
            @endif

            @if(in_array($booking->status, ['provider_on_way', 'in_progress']))
                <a href="tel:{{ $booking->contact_phone ?? $booking->user->phone }}"
                   class="px-6 py-3 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded-xl font-semibold transition-all">
                    <i class="fas fa-phone mr-2"></i>โทรหาลูกค้า
                </a>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Customer Info --}}
        <div class="backdrop-blur-xl bg-white/90 dark:bg-gray-800/90 rounded-xl shadow-lg p-6">
            <h3 class="font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <i class="fas fa-user text-purple-600"></i>
                ข้อมูลลูกค้า
            </h3>
            <div class="space-y-3 text-sm">
                <div><strong>ชื่อ:</strong> {{ $booking->user->name }}</div>
                <div><strong>เบอร์โทร:</strong> <a href="tel:{{ $booking->contact_phone ?? $booking->user->phone }}" class="text-blue-600">{{ $booking->contact_phone ?? $booking->user->phone }}</a></div>
            </div>
        </div>

        {{-- Booking Info --}}
        <div class="backdrop-blur-xl bg-white/90 dark:bg-gray-800/90 rounded-xl shadow-lg p-6">
            <h3 class="font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <i class="fas fa-calendar text-purple-600"></i>
                ข้อมูลการจอง
            </h3>
            <div class="space-y-3 text-sm">
                <div><strong>วันที่:</strong> {{ $booking->scheduled_at?->format('d/m/Y H:i') }}</div>
                <div><strong>ระยะเวลา:</strong> {{ $booking->service->estimated_duration_minutes ?? 60 }} นาที</div>
            </div>
        </div>

        {{-- Location Info --}}
        @if($booking->customer_address)
        <div class="md:col-span-2 backdrop-blur-xl bg-white/90 dark:bg-gray-800/90 rounded-xl shadow-lg p-6">
            <h3 class="font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <i class="fas fa-map-marker-alt text-purple-600"></i>
                สถานที่ให้บริการ
            </h3>
            <p class="text-sm text-gray-700 dark:text-gray-300 mb-3">{{ $booking->customer_address }}</p>
            @if($booking->customer_latitude && $booking->customer_longitude)
                <a href="https://www.google.com/maps/dir/?api=1&destination={{ $booking->customer_latitude }},{{ $booking->customer_longitude }}"
                   target="_blank"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold transition-all">
                    <i class="fas fa-directions"></i>
                    เปิด Google Maps
                </a>
            @endif
        </div>
        @endif

        {{-- Pricing --}}
        <div class="md:col-span-2 backdrop-blur-xl bg-gradient-to-br from-green-50/90 to-emerald-50/90 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl shadow-lg p-6 border border-green-200 dark:border-green-800">
            <h3 class="font-bold text-green-900 dark:text-green-300 mb-4">รายได้ของคุณ</h3>
            <div class="space-y-2 text-sm mb-4">
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">ราคารวมทั้งหมด:</span>
                    <span>฿{{ number_format($booking->total_price, 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">ค่าแพลตฟอร์ม ({{ $booking->platform_fee_percent ?? 15 }}%):</span>
                    <span class="text-red-600">-฿{{ number_format($booking->platform_fee, 2) }}</span>
                </div>
            </div>
            <div class="pt-3 border-t border-green-300 dark:border-green-700 flex justify-between text-xl font-bold text-green-600 dark:text-green-400">
                <span>คุณจะได้รับ:</span>
                <span>฿{{ number_format($booking->provider_fee, 2) }}</span>
            </div>
        </div>

        {{-- Customer Notes --}}
        @if($booking->customer_notes)
        <div class="md:col-span-2 backdrop-blur-xl bg-blue-50/90 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6">
            <h3 class="font-bold text-blue-900 dark:text-blue-300 mb-2 flex items-center gap-2">
                <i class="fas fa-comment"></i>
                หมายเหตุจากลูกค้า
            </h3>
            <p class="text-sm text-blue-800 dark:text-blue-400">{{ $booking->customer_notes }}</p>
        </div>
        @endif
    </div>

    {{-- Reject Modal --}}
    <div x-show="showRejectModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div x-show="showRejectModal" @click="showRejectModal = false" class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>
            <div x-show="showRejectModal" class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 max-w-md w-full shadow-xl">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-times-circle text-red-600 mr-2"></i>ปฏิเสธงาน
                </h3>
                <form action="{{ route('provider.bookings.reject', $booking) }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            เหตุผลที่ปฏิเสธ <span class="text-red-500">*</span>
                        </label>
                        <textarea name="reason" required rows="3"
                                  class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-red-500 text-gray-900 dark:text-white"
                                  placeholder="โปรดระบุเหตุผล..."></textarea>
                    </div>
                    <div class="flex gap-3">
                        <button type="submit" class="flex-1 px-4 py-3 bg-red-600 hover:bg-red-700 text-white rounded-xl font-semibold transition-all">
                            ยืนยันการปฏิเสธ
                        </button>
                        <button type="button" @click="showRejectModal = false" class="flex-1 px-4 py-3 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-xl font-semibold transition-all">
                            ยกเลิก
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function providerBookingDetail() {
    return {
        showRejectModal: false,
        processing: false,

        async acceptBooking() {
            if (!confirm('ยืนยันการรับงานนี้?')) return;

            this.processing = true;
            try {
                const response = await fetch('{{ route('provider.bookings.accept', $booking) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (response.ok) {
                    window.location.reload();
                } else {
                    alert('เกิดข้อผิดพลาด');
                }
            } catch (error) {
                console.error(error);
                alert('เกิดข้อผิดพลาด');
            } finally {
                this.processing = false;
            }
        }
    }
}
</script>
@endpush
