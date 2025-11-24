@extends('layouts.app')

@section('title', 'งานของฉัน - Provider')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="providerDashboard()">
    {{-- Header Section --}}
    <div class="mb-6 p-6 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 shadow-xl">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 dark:from-purple-400 dark:to-pink-400 bg-clip-text text-transparent">
                    <i class="fas fa-briefcase mr-3"></i>งานของฉัน
                </h1>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    จัดการงานที่ได้รับและติดตามสถานะการให้บริการ
                </p>
            </div>

            {{-- Availability Toggle --}}
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                    <div>
                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">สถานะ:</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            <span x-text="provider.is_available ? 'พร้อมรับงาน' : 'ไม่พร้อมรับงาน'"></span>
                        </p>
                    </div>
                    <button @click="toggleAvailability()"
                            :disabled="toggling"
                            class="relative inline-flex items-center h-8 w-16 rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-purple-500"
                            :class="provider.is_available ? 'bg-green-600' : 'bg-gray-400'">
                        <span class="inline-block h-6 w-6 transform transition-transform bg-white rounded-full"
                              :class="provider.is_available ? 'translate-x-9' : 'translate-x-1'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="p-4 rounded-xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 shadow-lg hover:shadow-xl transition-all duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-600 dark:text-gray-400">ทั้งหมด</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] ?? 0 }}</p>
                </div>
                <div class="p-3 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-lg">
                    <i class="fas fa-briefcase text-white"></i>
                </div>
            </div>
        </div>

        <div class="p-4 rounded-xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 shadow-lg hover:shadow-xl transition-all duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-600 dark:text-gray-400">รอตอบรับ</p>
                    <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $stats['waiting'] ?? 0 }}</p>
                </div>
                <div class="p-3 bg-gradient-to-br from-yellow-500 to-orange-500 rounded-lg">
                    <i class="fas fa-clock text-white"></i>
                </div>
            </div>
        </div>

        <div class="p-4 rounded-xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 shadow-lg hover:shadow-xl transition-all duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-600 dark:text-gray-400">ที่รับแล้ว</p>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['accepted'] ?? 0 }}</p>
                </div>
                <div class="p-3 bg-gradient-to-br from-green-500 to-emerald-500 rounded-lg">
                    <i class="fas fa-check-circle text-white"></i>
                </div>
            </div>
        </div>

        <div class="p-4 rounded-xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 shadow-lg hover:shadow-xl transition-all duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-600 dark:text-gray-400">กำลังทำ</p>
                    <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $stats['in_progress'] ?? 0 }}</p>
                </div>
                <div class="p-3 bg-gradient-to-br from-purple-500 to-pink-500 rounded-lg">
                    <i class="fas fa-spinner text-white"></i>
                </div>
            </div>
        </div>

        <div class="p-4 rounded-xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 shadow-lg hover:shadow-xl transition-all duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-600 dark:text-gray-400">เสร็จสิ้น</p>
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['completed'] ?? 0 }}</p>
                </div>
                <div class="p-3 bg-gradient-to-br from-blue-500 to-indigo-500 rounded-lg">
                    <i class="fas fa-check-double text-white"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Tabs --}}
    <div class="mb-6">
        <div class="backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 rounded-xl shadow-lg p-2">
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('provider.bookings.index') }}"
                   class="px-4 py-2 rounded-lg font-semibold transition-all duration-200 {{ !request('status') ? 'bg-gradient-to-r from-purple-600 to-pink-600 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    <i class="fas fa-list mr-2"></i>ทั้งหมด
                </a>
                <a href="{{ route('provider.bookings.pending') }}"
                   class="px-4 py-2 rounded-lg font-semibold transition-all duration-200 {{ request()->routeIs('provider.bookings.pending') ? 'bg-gradient-to-r from-yellow-500 to-orange-500 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    <i class="fas fa-bell mr-2"></i>งานใหม่
                    @if(($stats['waiting'] ?? 0) > 0)
                        <span class="ml-1 px-2 py-0.5 bg-red-500 text-white text-xs rounded-full">{{ $stats['waiting'] }}</span>
                    @endif
                </a>
                <a href="{{ route('provider.bookings.index', ['status' => 'provider_accepted']) }}"
                   class="px-4 py-2 rounded-lg font-semibold transition-all duration-200 {{ request('status') === 'provider_accepted' ? 'bg-gradient-to-r from-green-500 to-emerald-500 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    <i class="fas fa-check mr-2"></i>ที่รับแล้ว
                </a>
                <a href="{{ route('provider.bookings.index', ['status' => 'in_progress']) }}"
                   class="px-4 py-2 rounded-lg font-semibold transition-all duration-200 {{ in_array(request('status'), ['provider_on_way', 'in_progress']) ? 'bg-gradient-to-r from-purple-500 to-pink-500 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    <i class="fas fa-tasks mr-2"></i>กำลังทำ
                </a>
                <a href="{{ route('provider.bookings.index', ['status' => 'completed']) }}"
                   class="px-4 py-2 rounded-lg font-semibold transition-all duration-200 {{ request('status') === 'completed' ? 'bg-gradient-to-r from-blue-500 to-indigo-500 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    <i class="fas fa-check-double mr-2"></i>เสร็จสิ้น
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
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-user text-purple-600 dark:text-purple-400"></i>
                                        <span>{{ $booking->user->name ?? 'N/A' }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-phone text-purple-600 dark:text-purple-400"></i>
                                        <span>{{ $booking->contact_phone ?? $booking->user->phone ?? 'N/A' }}</span>
                                    </div>
                                </div>

                                @if($booking->customer_address)
                                    <div class="mt-2 flex items-start gap-2 text-sm text-gray-600 dark:text-gray-400">
                                        <i class="fas fa-map-marker-alt text-purple-600 dark:text-purple-400 mt-0.5"></i>
                                        <span class="line-clamp-1">{{ $booking->customer_address }}</span>
                                    </div>
                                @endif

                                @if($booking->provider_response_deadline && in_array($booking->status, ['waiting_provider', 'notifying_provider']))
                                    <div class="mt-2 flex items-center gap-2 text-sm text-orange-600 dark:text-orange-400 font-semibold">
                                        <i class="fas fa-clock"></i>
                                        <span>ต้องตอบภายใน: {{ $booking->provider_response_deadline->diffForHumans() }}</span>
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
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                (คุณได้: ฿{{ number_format($booking->provider_fee, 2) }})
                            </p>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="flex flex-wrap gap-2 justify-end">
                            {{-- View Details --}}
                            <a href="{{ route('provider.bookings.show', $booking) }}"
                               class="px-4 py-2 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded-lg hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-all duration-200 text-sm font-semibold">
                                <i class="fas fa-eye mr-1"></i>ดู
                            </a>

                            {{-- Accept (for waiting status) --}}
                            @if(in_array($booking->status, ['waiting_provider', 'notifying_provider']))
                                <button @click="acceptBooking({{ $booking->id }})"
                                        class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-all duration-200 text-sm font-semibold">
                                    <i class="fas fa-check mr-1"></i>รับงาน
                                </button>
                                <button @click="showRejectModal({{ $booking->id }})"
                                        class="px-4 py-2 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 transition-all duration-200 text-sm font-semibold">
                                    <i class="fas fa-times mr-1"></i>ปฏิเสธ
                                </button>
                            @endif

                            {{-- Start Journey --}}
                            @if($booking->status === 'provider_accepted')
                                <form action="{{ route('provider.bookings.start-journey', $booking) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit"
                                            class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-all duration-200 text-sm font-semibold">
                                        <i class="fas fa-route mr-1"></i>เริ่มเดินทาง
                                    </button>
                                </form>
                            @endif

                            {{-- Start Service --}}
                            @if($booking->status === 'provider_on_way')
                                <form action="{{ route('provider.bookings.start-service', $booking) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit"
                                            class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-all duration-200 text-sm font-semibold">
                                        <i class="fas fa-play mr-1"></i>เริ่มบริการ
                                    </button>
                                </form>
                            @endif

                            {{-- Complete Service --}}
                            @if($booking->status === 'in_progress')
                                <form action="{{ route('provider.bookings.complete', $booking) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit"
                                            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-all duration-200 text-sm font-semibold">
                                        <i class="fas fa-check-double mr-1"></i>เสร็จสิ้น
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-xl p-12 text-center">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-gray-100 dark:bg-gray-700 rounded-full mb-4">
                <i class="fas fa-briefcase text-3xl text-gray-400"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">ไม่มีงาน</h3>
            <p class="text-gray-600 dark:text-gray-400">
                @if(request()->routeIs('provider.bookings.pending'))
                    ไม่มีงานใหม่ที่รอการตอบรับ
                @else
                    ไม่มีงานในสถานะนี้
                @endif
            </p>
        </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($bookings->hasPages())
        <div class="mt-6">
            {{ $bookings->links() }}
        </div>
    @endif

    {{-- Reject Modal --}}
    <div x-show="showRejectModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div x-show="showRejectModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 class="fixed inset-0 bg-black/50 backdrop-blur-sm"
                 @click="showRejectModal = false"></div>

            <div x-show="showRejectModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 max-w-md w-full shadow-xl">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-times-circle text-red-600 mr-2"></i>
                    ปฏิเสธงาน
                </h3>

                <form :action="'/provider/bookings/' + rejectBookingId + '/reject'" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            เหตุผลที่ปฏิเสธ <span class="text-red-500">*</span>
                        </label>
                        <textarea name="reason"
                                  required
                                  rows="3"
                                  class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-red-500 text-gray-900 dark:text-white"
                                  placeholder="โปรดระบุเหตุผล..."></textarea>
                    </div>

                    <div class="flex gap-3">
                        <button type="submit"
                                class="flex-1 px-4 py-3 bg-red-600 hover:bg-red-700 text-white rounded-xl font-semibold transition-all duration-200">
                            ยืนยันการปฏิเสธ
                        </button>
                        <button type="button"
                                @click="showRejectModal = false"
                                class="flex-1 px-4 py-3 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-xl font-semibold transition-all duration-200">
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
function providerDashboard() {
    return {
        provider: @json($provider),
        toggling: false,
        showRejectModal: false,
        rejectBookingId: null,

        async toggleAvailability() {
            this.toggling = true;

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
                    this.provider.is_available = data.status === 'available';
                    alert(data.message);
                }
            } catch (error) {
                console.error('Error toggling availability:', error);
                alert('เกิดข้อผิดพลาด');
            } finally {
                this.toggling = false;
            }
        },

        async acceptBooking(bookingId) {
            if (!confirm('ยืนยันการรับงานนี้?')) return;

            try {
                const response = await fetch(`/provider/bookings/${bookingId}/accept`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (response.ok) {
                    window.location.reload();
                } else {
                    alert('เกิดข้อผิดพลาดในการรับงาน');
                }
            } catch (error) {
                console.error('Error accepting booking:', error);
                alert('เกิดข้อผิดพลาด');
            }
        },

        showRejectModal(bookingId) {
            this.rejectBookingId = bookingId;
            this.showRejectModal = true;
        }
    }
}
</script>
@endpush
