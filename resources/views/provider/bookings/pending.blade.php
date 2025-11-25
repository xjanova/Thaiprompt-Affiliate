{{--
    หน้างานใหม่ที่รอตอบรับ - Provider
    แสดงรายการงานที่รอให้ผู้ให้บริการตอบรับหรือปฏิเสธ
--}}
@extends('layouts.app')

@section('title', 'งานใหม่ - Provider')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="pendingBookings()">
    {{-- Header Section --}}
    <div class="mb-6 p-6 rounded-2xl backdrop-blur-xl bg-gradient-to-r from-yellow-500/20 to-orange-500/20 dark:from-yellow-500/10 dark:to-orange-500/10 border border-yellow-500/30 dark:border-yellow-500/20 shadow-xl">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-yellow-600 to-orange-600 dark:from-yellow-400 dark:to-orange-400 bg-clip-text text-transparent">
                    <i class="fas fa-bell mr-3"></i>งานใหม่รอตอบรับ
                </h1>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    คุณมี <span class="font-bold text-yellow-600 dark:text-yellow-400">{{ $bookings->count() }}</span> งานที่รอการตอบรับ
                </p>
            </div>

            {{-- Back to All Bookings --}}
            <a href="{{ route('provider.bookings.index') }}"
               class="inline-flex items-center gap-2 px-6 py-3 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200 font-semibold">
                <i class="fas fa-arrow-left"></i>
                ดูงานทั้งหมด
            </a>
        </div>
    </div>

    {{-- Provider Status Warning --}}
    @if($provider && $provider->status !== 'available')
        <div class="mb-6 p-4 rounded-xl bg-orange-100 dark:bg-orange-900/30 border border-orange-300 dark:border-orange-700">
            <div class="flex items-center gap-3">
                <i class="fas fa-exclamation-triangle text-orange-600 dark:text-orange-400 text-xl"></i>
                <div>
                    <p class="font-semibold text-orange-800 dark:text-orange-300">คุณอยู่ในสถานะ "{{ $provider->status === 'busy' ? 'กำลังให้บริการ' : 'ออฟไลน์' }}"</p>
                    <p class="text-sm text-orange-700 dark:text-orange-400">
                        @if($provider->status === 'offline')
                            เปลี่ยนสถานะเป็น "พร้อมรับงาน" เพื่อรับงานใหม่
                        @else
                            คุณจะได้รับงานใหม่หลังจากเสร็จสิ้นงานปัจจุบัน
                        @endif
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- Pending Bookings List --}}
    <div class="space-y-6">
        @forelse($bookings as $booking)
        <div class="backdrop-blur-xl bg-white/90 dark:bg-gray-800/90 border-2 border-yellow-400/50 dark:border-yellow-500/30 rounded-2xl shadow-xl overflow-hidden animate-pulse-subtle"
             x-data="{ expanded: false }">
            {{-- Urgent Banner --}}
            @if($booking->provider_response_deadline && $booking->provider_response_deadline->diffInMinutes(now()) < 5)
                <div class="bg-red-500 text-white px-4 py-2 text-center font-semibold">
                    <i class="fas fa-exclamation-triangle mr-2 animate-pulse"></i>
                    ด่วน! เหลือเวลาตอบรับ {{ $booking->provider_response_deadline->diffForHumans() }}
                </div>
            @endif

            <div class="p-6">
                <div class="flex flex-col lg:flex-row lg:items-start gap-6">
                    {{-- Left: Booking Info --}}
                    <div class="flex-1">
                        <div class="flex items-start gap-4">
                            {{-- Service Icon --}}
                            <div class="flex-shrink-0 w-20 h-20 rounded-xl bg-gradient-to-br from-purple-400 to-pink-400 flex items-center justify-center text-4xl shadow-lg">
                                {{ $booking->service->category->icon ?? '🔧' }}
                            </div>

                            {{-- Booking Details --}}
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                                        {{ $booking->service->name }}
                                    </h3>
                                    <span class="px-3 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300 rounded-full text-xs font-semibold">
                                        <i class="fas fa-clock mr-1"></i>รอตอบรับ
                                    </span>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm text-gray-600 dark:text-gray-400">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-hashtag text-purple-600 dark:text-purple-400 w-4"></i>
                                        <span class="font-mono">{{ $booking->booking_number }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-calendar text-purple-600 dark:text-purple-400 w-4"></i>
                                        <span class="font-semibold">{{ $booking->scheduled_at?->format('d/m/Y') }}</span>
                                        <span class="text-purple-600 dark:text-purple-400 font-bold">{{ $booking->scheduled_at?->format('H:i') }} น.</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-user text-purple-600 dark:text-purple-400 w-4"></i>
                                        <span>{{ $booking->user->name ?? 'N/A' }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-phone text-purple-600 dark:text-purple-400 w-4"></i>
                                        <a href="tel:{{ $booking->contact_phone ?? $booking->user->phone }}"
                                           class="text-blue-600 dark:text-blue-400 hover:underline">
                                            {{ $booking->contact_phone ?? $booking->user->phone ?? 'N/A' }}
                                        </a>
                                    </div>
                                </div>

                                {{-- Response Deadline --}}
                                @if($booking->provider_response_deadline)
                                    <div class="mt-3 flex items-center gap-2 text-sm"
                                         :class="countdownClass('{{ $booking->provider_response_deadline->toISOString() }}')">
                                        <i class="fas fa-hourglass-half"></i>
                                        <span>ต้องตอบภายใน: </span>
                                        <span class="font-bold" x-text="formatCountdown('{{ $booking->provider_response_deadline->toISOString() }}')"></span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Location Info --}}
                        @php
                            $location = $booking->locations()->serviceLocation()->first();
                        @endphp
                        @if($location)
                            <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800">
                                <div class="flex items-start gap-3">
                                    <i class="fas fa-map-marker-alt text-blue-600 dark:text-blue-400 text-xl mt-0.5"></i>
                                    <div class="flex-1">
                                        <p class="font-semibold text-blue-800 dark:text-blue-300">สถานที่ให้บริการ</p>
                                        <p class="text-sm text-blue-700 dark:text-blue-400 mt-1">{{ $location->address }}</p>
                                        @if($location->building_name || $location->floor_number || $location->room_number)
                                            <p class="text-sm text-blue-600 dark:text-blue-400 mt-1">
                                                @if($location->building_name) {{ $location->building_name }} @endif
                                                @if($location->floor_number) ชั้น {{ $location->floor_number }} @endif
                                                @if($location->room_number) ห้อง {{ $location->room_number }} @endif
                                            </p>
                                        @endif
                                        @if($location->landmark)
                                            <p class="text-sm text-blue-600 dark:text-blue-400 mt-1">
                                                <i class="fas fa-info-circle mr-1"></i>{{ $location->landmark }}
                                            </p>
                                        @endif

                                        {{-- Map Link --}}
                                        <a href="https://www.google.com/maps/dir/?api=1&destination={{ $location->latitude }},{{ $location->longitude }}"
                                           target="_blank"
                                           class="inline-flex items-center gap-2 mt-2 text-sm text-blue-700 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-semibold">
                                            <i class="fas fa-directions"></i>
                                            นำทาง Google Maps
                                            <i class="fas fa-external-link-alt text-xs"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Customer Notes --}}
                        @if($booking->customer_notes)
                            <div class="mt-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg border-l-4 border-purple-500">
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    <i class="fas fa-comment text-purple-600 dark:text-purple-400 mr-2"></i>
                                    <span class="font-semibold">หมายเหตุ:</span> {{ $booking->customer_notes }}
                                </p>
                            </div>
                        @endif

                        {{-- Expandable Service Details --}}
                        <div class="mt-4">
                            <button @click="expanded = !expanded"
                                    class="flex items-center gap-2 text-sm text-purple-600 dark:text-purple-400 font-semibold hover:text-purple-700 dark:hover:text-purple-300">
                                <i :class="expanded ? 'fas fa-chevron-up' : 'fas fa-chevron-down'"></i>
                                <span x-text="expanded ? 'ซ่อนรายละเอียด' : 'ดูรายละเอียดเพิ่มเติม'"></span>
                            </button>

                            <div x-show="expanded"
                                 x-collapse
                                 class="mt-3 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl space-y-3">
                                {{-- Service Items --}}
                                @if($booking->items && $booking->items->count() > 0)
                                    <div>
                                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">รายการบริการ</h4>
                                        <ul class="space-y-2">
                                            @foreach($booking->items as $item)
                                                <li class="flex items-center justify-between text-sm">
                                                    <span class="text-gray-700 dark:text-gray-300">
                                                        {{ $item->name }}
                                                        @if($item->quantity > 1) x{{ $item->quantity }} @endif
                                                    </span>
                                                    <span class="text-gray-600 dark:text-gray-400">฿{{ number_format($item->subtotal, 2) }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                {{-- Service Duration --}}
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600 dark:text-gray-400">ระยะเวลาบริการ</span>
                                    <span class="font-semibold text-gray-900 dark:text-white">{{ $booking->service_duration_minutes ?? $booking->service->duration_minutes ?? 60 }} นาที</span>
                                </div>

                                {{-- Distance --}}
                                @if($booking->distance_km > 0)
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-600 dark:text-gray-400">ระยะทาง</span>
                                        <span class="font-semibold text-gray-900 dark:text-white">{{ number_format($booking->distance_km, 1) }} กม.</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Right: Price & Actions --}}
                    <div class="lg:min-w-[280px] space-y-4">
                        {{-- Price Summary --}}
                        <div class="p-4 bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl border border-purple-200 dark:border-purple-800">
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between text-gray-700 dark:text-gray-300">
                                    <span>ค่าบริการ</span>
                                    <span>฿{{ number_format($booking->base_price, 2) }}</span>
                                </div>
                                @if($booking->distance_price > 0)
                                    <div class="flex justify-between text-gray-700 dark:text-gray-300">
                                        <span>ค่าเดินทาง</span>
                                        <span>฿{{ number_format($booking->distance_price, 2) }}</span>
                                    </div>
                                @endif
                                @if($booking->options_price > 0)
                                    <div class="flex justify-between text-gray-700 dark:text-gray-300">
                                        <span>ค่าออฟชั่น</span>
                                        <span>฿{{ number_format($booking->options_price, 2) }}</span>
                                    </div>
                                @endif
                                <div class="pt-2 border-t border-purple-300 dark:border-purple-700">
                                    <div class="flex justify-between text-lg font-bold text-purple-600 dark:text-purple-400">
                                        <span>ราคารวม</span>
                                        <span>฿{{ number_format($booking->total_amount, 2) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Your Earnings --}}
                        <div class="p-4 bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl border border-green-200 dark:border-green-800">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs text-green-700 dark:text-green-400">คุณจะได้รับ</p>
                                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                                        ฿{{ number_format($booking->provider_earnings ?? ($booking->total_amount * 0.85), 2) }}
                                    </p>
                                </div>
                                <div class="text-4xl">💰</div>
                            </div>
                            <p class="text-xs text-green-600 dark:text-green-400 mt-2">
                                <i class="fas fa-info-circle mr-1"></i>
                                หลังหักค่าธรรมเนียมแพลตฟอร์ม {{ $booking->platform_fee_percentage ?? 10 }}%
                            </p>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="space-y-3">
                            {{-- Accept Button --}}
                            <form action="{{ route('provider.bookings.accept', $booking) }}" method="POST">
                                @csrf
                                <button type="submit"
                                        class="w-full py-4 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105">
                                    <i class="fas fa-check-circle mr-2"></i>
                                    รับงานนี้
                                </button>
                            </form>

                            {{-- Reject Button --}}
                            <button @click="showRejectModal({{ $booking->id }})"
                                    class="w-full py-3 bg-white dark:bg-gray-700 text-red-600 dark:text-red-400 border border-red-300 dark:border-red-700 font-semibold rounded-xl hover:bg-red-50 dark:hover:bg-red-900/20 transition-all duration-200">
                                <i class="fas fa-times-circle mr-2"></i>
                                ปฏิเสธงาน
                            </button>

                            {{-- View Details Link --}}
                            <a href="{{ route('provider.bookings.show', $booking) }}"
                               class="block w-full text-center py-2 text-purple-600 dark:text-purple-400 font-semibold hover:text-purple-700 dark:hover:text-purple-300">
                                <i class="fas fa-eye mr-2"></i>
                                ดูรายละเอียดเพิ่มเติม
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-xl p-12 text-center">
            <div class="inline-flex items-center justify-center w-24 h-24 bg-green-100 dark:bg-green-900/30 rounded-full mb-6">
                <i class="fas fa-check-double text-4xl text-green-600 dark:text-green-400"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">ไม่มีงานใหม่ในขณะนี้</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">
                คุณตอบรับงานทั้งหมดแล้ว! รอรับงานใหม่ได้เลย
            </p>

            @if($provider && $provider->status === 'offline')
                <button @click="toggleAvailability()"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-xl font-semibold hover:shadow-lg transition-all duration-200">
                    <i class="fas fa-toggle-on"></i>
                    เปิดรับงาน
                </button>
            @else
                <a href="{{ route('provider.bookings.index') }}"
                   class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-xl font-semibold hover:shadow-lg transition-all duration-200">
                    <i class="fas fa-list"></i>
                    ดูงานทั้งหมด
                </a>
            @endif
        </div>
        @endforelse
    </div>

    {{-- Reject Modal --}}
    <div x-show="showRejectModalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div x-show="showRejectModalOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 class="fixed inset-0 bg-black/50 backdrop-blur-sm"
                 @click="showRejectModalOpen = false"></div>

            <div x-show="showRejectModalOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 max-w-md w-full shadow-xl">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-times-circle text-red-600 mr-2"></i>
                    ปฏิเสธงาน
                </h3>

                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    กรุณาระบุเหตุผลที่ปฏิเสธงานนี้ เพื่อให้ระบบหาผู้ให้บริการท่านอื่นแทน
                </p>

                <form :action="'/provider/bookings/' + rejectBookingId + '/reject'" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            เหตุผลที่ปฏิเสธ <span class="text-red-500">*</span>
                        </label>

                        {{-- Quick Reasons --}}
                        <div class="grid grid-cols-2 gap-2 mb-3">
                            <button type="button"
                                    @click="rejectReason = 'ติดงานอื่น'"
                                    :class="rejectReason === 'ติดงานอื่น' ? 'bg-red-100 dark:bg-red-900/30 border-red-500' : 'bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600'"
                                    class="px-3 py-2 border rounded-lg text-sm text-left hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                ติดงานอื่น
                            </button>
                            <button type="button"
                                    @click="rejectReason = 'ระยะทางไกลเกินไป'"
                                    :class="rejectReason === 'ระยะทางไกลเกินไป' ? 'bg-red-100 dark:bg-red-900/30 border-red-500' : 'bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600'"
                                    class="px-3 py-2 border rounded-lg text-sm text-left hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                ระยะทางไกลเกินไป
                            </button>
                            <button type="button"
                                    @click="rejectReason = 'ไม่ว่างในเวลาที่นัด'"
                                    :class="rejectReason === 'ไม่ว่างในเวลาที่นัด' ? 'bg-red-100 dark:bg-red-900/30 border-red-500' : 'bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600'"
                                    class="px-3 py-2 border rounded-lg text-sm text-left hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                ไม่ว่างในเวลาที่นัด
                            </button>
                            <button type="button"
                                    @click="rejectReason = 'เหตุผลอื่น'"
                                    :class="rejectReason === 'เหตุผลอื่น' ? 'bg-red-100 dark:bg-red-900/30 border-red-500' : 'bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600'"
                                    class="px-3 py-2 border rounded-lg text-sm text-left hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                เหตุผลอื่น...
                            </button>
                        </div>

                        <textarea name="reason"
                                  x-model="rejectReason"
                                  required
                                  rows="3"
                                  class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-red-500 text-gray-900 dark:text-white"
                                  placeholder="โปรดระบุเหตุผล..."></textarea>
                    </div>

                    <div class="flex gap-3">
                        <button type="submit"
                                :disabled="!rejectReason"
                                class="flex-1 px-4 py-3 bg-red-600 hover:bg-red-700 text-white rounded-xl font-semibold transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                            ยืนยันการปฏิเสธ
                        </button>
                        <button type="button"
                                @click="showRejectModalOpen = false"
                                class="flex-1 px-4 py-3 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-xl font-semibold transition-all duration-200 hover:bg-gray-300 dark:hover:bg-gray-500">
                            ยกเลิก
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
@keyframes pulse-subtle {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.95; }
}
.animate-pulse-subtle {
    animation: pulse-subtle 2s ease-in-out infinite;
}
</style>
@endpush

@push('scripts')
<script>
function pendingBookings() {
    return {
        provider: @json($provider),
        showRejectModalOpen: false,
        rejectBookingId: null,
        rejectReason: '',

        showRejectModal(bookingId) {
            this.rejectBookingId = bookingId;
            this.rejectReason = '';
            this.showRejectModalOpen = true;
        },

        formatCountdown(deadline) {
            const now = new Date();
            const target = new Date(deadline);
            const diff = target - now;

            if (diff <= 0) return 'หมดเวลา';

            const minutes = Math.floor(diff / 60000);
            const seconds = Math.floor((diff % 60000) / 1000);

            if (minutes > 60) {
                const hours = Math.floor(minutes / 60);
                return `${hours} ชั่วโมง ${minutes % 60} นาที`;
            }

            return `${minutes} นาที ${seconds} วินาที`;
        },

        countdownClass(deadline) {
            const now = new Date();
            const target = new Date(deadline);
            const diff = target - now;

            if (diff <= 0) return 'text-red-600 dark:text-red-400';
            if (diff <= 300000) return 'text-red-600 dark:text-red-400 animate-pulse'; // < 5 min
            if (diff <= 600000) return 'text-orange-600 dark:text-orange-400'; // < 10 min
            return 'text-yellow-600 dark:text-yellow-400';
        },

        async toggleAvailability() {
            try {
                const response = await fetch('{{ route('provider.toggle-availability') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();
                if (data.success) {
                    this.provider.status = data.status;
                    alert(data.message);
                }
            } catch (error) {
                console.error('Error toggling availability:', error);
                alert('เกิดข้อผิดพลาด');
            }
        }
    }
}

// Update countdown every second
setInterval(() => {
    document.querySelectorAll('[x-text*="formatCountdown"]').forEach(el => {
        el.__x.$data && el.__x.$data.$nextTick && el.__x.$data.$nextTick();
    });
}, 1000);
</script>
@endpush
