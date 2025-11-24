@extends('layouts.app')

@section('title', 'การจองของฉัน')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="myBookings()">
    {{-- Header --}}
    <div class="mb-6 p-6 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 shadow-xl">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 dark:from-purple-400 dark:to-pink-400 bg-clip-text text-transparent">
                    <i class="fas fa-calendar-check mr-3"></i>การจองของฉัน
                </h1>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    ดูและจัดการการจองบริการทั้งหมดของคุณ
                </p>
            </div>

            <a href="{{ route('user.services.index') }}"
               class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-xl font-semibold shadow-lg transition-all duration-200">
                <i class="fas fa-plus"></i>
                <span>จองบริการใหม่</span>
            </a>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="p-4 rounded-xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-600 dark:text-gray-400">ทั้งหมด</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] ?? 0 }}</p>
                </div>
                <div class="p-3 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-lg">
                    <i class="fas fa-list text-white"></i>
                </div>
            </div>
        </div>

        <div class="p-4 rounded-xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-600 dark:text-gray-400">กำลังดำเนินการ</p>
                    <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $stats['pending'] ?? 0 }}</p>
                </div>
                <div class="p-3 bg-gradient-to-br from-yellow-500 to-orange-500 rounded-lg">
                    <i class="fas fa-clock text-white"></i>
                </div>
            </div>
        </div>

        <div class="p-4 rounded-xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-600 dark:text-gray-400">กำลังทำ</p>
                    <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $stats['in_progress'] ?? 0 }}</p>
                </div>
                <div class="p-3 bg-gradient-to-br from-purple-500 to-pink-500 rounded-lg">
                    <i class="fas fa-cog fa-spin text-white"></i>
                </div>
            </div>
        </div>

        <div class="p-4 rounded-xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-600 dark:text-gray-400">เสร็จสิ้น</p>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['completed'] ?? 0 }}</p>
                </div>
                <div class="p-3 bg-gradient-to-br from-green-500 to-emerald-500 rounded-lg">
                    <i class="fas fa-check-double text-white"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Tabs --}}
    <div class="mb-6">
        <div class="backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 rounded-xl shadow-lg p-2">
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('user.bookings.index') }}"
                   class="px-4 py-2 rounded-lg font-semibold transition-all duration-200 {{ !request('status') ? 'bg-gradient-to-r from-purple-600 to-pink-600 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    ทั้งหมด
                </a>
                <a href="{{ route('user.bookings.index', ['status' => 'pending']) }}"
                   class="px-4 py-2 rounded-lg font-semibold transition-all duration-200 {{ request('status') === 'pending' ? 'bg-yellow-500 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    รอดำเนินการ
                </a>
                <a href="{{ route('user.bookings.index', ['status' => 'in_progress']) }}"
                   class="px-4 py-2 rounded-lg font-semibold transition-all duration-200 {{ request('status') === 'in_progress' ? 'bg-purple-500 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    กำลังทำ
                </a>
                <a href="{{ route('user.bookings.index', ['status' => 'completed']) }}"
                   class="px-4 py-2 rounded-lg font-semibold transition-all duration-200 {{ request('status') === 'completed' ? 'bg-green-500 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    เสร็จสิ้น
                </a>
                <a href="{{ route('user.bookings.index', ['status' => 'cancelled']) }}"
                   class="px-4 py-2 rounded-lg font-semibold transition-all duration-200 {{ request('status') === 'cancelled' ? 'bg-red-500 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    ยกเลิก
                </a>
            </div>
        </div>
    </div>

    {{-- Bookings List --}}
    <div class="space-y-4">
        @forelse($bookings as $booking)
        <div class="backdrop-blur-xl bg-white/90 dark:bg-gray-800/90 border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 overflow-hidden">
            <div class="p-6">
                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                    {{-- Left: Booking Info --}}
                    <div class="flex-1">
                        <div class="flex items-start gap-4">
                            {{-- Service Icon --}}
                            <div class="flex-shrink-0 w-16 h-16 rounded-xl bg-gradient-to-br from-purple-400 to-pink-400 flex items-center justify-center text-3xl shadow-lg">
                                {{ $booking->service->category->icon ?? '🔧' }}
                            </div>

                            {{-- Booking Details --}}
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                        {{ $booking->service->name }}
                                    </h3>
                                    <x-booking-status-badge :status="$booking->status" />
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm text-gray-600 dark:text-gray-400">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-hashtag text-purple-600 dark:text-purple-400"></i>
                                        <span class="font-mono">{{ $booking->booking_number }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-calendar text-purple-600 dark:text-purple-400"></i>
                                        <span>{{ $booking->scheduled_at?->format('d/m/Y H:i') ?? '-' }}</span>
                                    </div>
                                    @if($booking->provider)
                                        <div class="flex items-center gap-2">
                                            <i class="fas fa-user-tie text-purple-600 dark:text-purple-400"></i>
                                            <span>{{ $booking->provider->name ?? 'ผู้ให้บริการ' }}</span>
                                        </div>
                                    @else
                                        <div class="flex items-center gap-2 text-yellow-600 dark:text-yellow-400">
                                            <i class="fas fa-hourglass-half"></i>
                                            <span>กำลังหาผู้ให้บริการ...</span>
                                        </div>
                                    @endif
                                </div>

                                @if($booking->customer_address)
                                    <div class="mt-2 flex items-start gap-2 text-sm text-gray-600 dark:text-gray-400">
                                        <i class="fas fa-map-marker-alt text-purple-600 dark:text-purple-400 mt-0.5"></i>
                                        <span class="line-clamp-1">{{ $booking->customer_address }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Right: Price & Actions --}}
                    <div class="flex flex-col items-end gap-3 md:min-w-[200px]">
                        {{-- Price --}}
                        <div class="text-right">
                            <p class="text-xs text-gray-500 dark:text-gray-400">ราคารวม</p>
                            <p class="text-2xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 dark:from-purple-400 dark:to-pink-400 bg-clip-text text-transparent">
                                ฿{{ number_format($booking->total_price, 2) }}
                            </p>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="flex flex-wrap gap-2 justify-end">
                            {{-- View Details --}}
                            <a href="{{ route('user.bookings.show', $booking) }}"
                               class="px-4 py-2 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded-lg hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-all duration-200 text-sm font-semibold">
                                <i class="fas fa-eye mr-1"></i>ดูรายละเอียด
                            </a>

                            {{-- Track (if in progress) --}}
                            @if(in_array($booking->status, ['provider_on_way', 'in_progress']))
                                <a href="{{ route('user.bookings.track', $booking) }}"
                                   class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-all duration-200 text-sm font-semibold">
                                    <i class="fas fa-map-marked-alt mr-1"></i>ติดตาม
                                </a>
                            @endif

                            {{-- Cancel (if can cancel) --}}
                            @if(in_array($booking->status, ['pending', 'paid', 'waiting_provider']))
                                <button @click="showCancelModal({{ $booking->id }})"
                                        class="px-4 py-2 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 transition-all duration-200 text-sm font-semibold">
                                    <i class="fas fa-times mr-1"></i>ยกเลิก
                                </button>
                            @endif

                            {{-- Review (if completed and not reviewed) --}}
                            @if($booking->status === 'completed' && !$booking->review)
                                <a href="{{ route('user.service-reviews.create', $booking) }}"
                                   class="px-4 py-2 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-lg hover:bg-green-200 dark:hover:bg-green-900/50 transition-all duration-200 text-sm font-semibold">
                                    <i class="fas fa-star mr-1"></i>รีวิว
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-xl p-12 text-center">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-gray-100 dark:bg-gray-700 rounded-full mb-4">
                <i class="fas fa-calendar-times text-3xl text-gray-400"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">ไม่มีการจอง</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">
                คุณยังไม่มีการจองบริการ
            </p>
            <a href="{{ route('user.services.index') }}"
               class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-xl font-semibold shadow-lg transition-all duration-200">
                <i class="fas fa-search"></i>
                <span>ค้นหาบริการ</span>
            </a>
        </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($bookings->hasPages())
        <div class="mt-6">
            {{ $bookings->links() }}
        </div>
    @endif

    {{-- Cancel Modal --}}
    <div x-show="showCancelModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div x-show="showCancelModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 class="fixed inset-0 bg-black/50 backdrop-blur-sm"
                 @click="showCancelModal = false"></div>

            <div x-show="showCancelModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 max-w-md w-full shadow-xl">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>
                    ยืนยันการยกเลิก
                </h3>

                <form :action="'/user/bookings/' + cancelBookingId + '/cancel'" method="POST">
                    @csrf
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        คุณแน่ใจหรือไม่ว่าต้องการยกเลิกการจองนี้? การกระทำนี้ไม่สามารถย้อนกลับได้
                    </p>

                    <div class="flex gap-3">
                        <button type="submit"
                                class="flex-1 px-4 py-3 bg-red-600 hover:bg-red-700 text-white rounded-xl font-semibold transition-all duration-200">
                            ยืนยันยกเลิก
                        </button>
                        <button type="button"
                                @click="showCancelModal = false"
                                class="flex-1 px-4 py-3 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-xl font-semibold transition-all duration-200">
                            ปิด
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
function myBookings() {
    return {
        showCancelModal: false,
        cancelBookingId: null,

        showCancelModal(bookingId) {
            this.cancelBookingId = bookingId;
            this.showCancelModal = true;
        }
    }
}
</script>
@endpush
