@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-8">การจองของฉัน</h1>

    <!-- Tabs -->
    <div class="mb-6 border-b">
        <nav class="flex space-x-8">
            <a href="{{ route('hotels.bookings.index') }}"
               class="pb-4 border-b-2 {{ !request('status') ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                ทั้งหมด ({{ $bookings->total() }})
            </a>
            <a href="{{ route('hotels.bookings.index', ['status' => 'upcoming']) }}"
               class="pb-4 border-b-2 {{ request('status') == 'upcoming' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                กำลังจะถึง
            </a>
            <a href="{{ route('hotels.bookings.index', ['status' => 'past']) }}"
               class="pb-4 border-b-2 {{ request('status') == 'past' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                ที่ผ่านมา
            </a>
            <a href="{{ route('hotels.bookings.index', ['status' => 'cancelled']) }}"
               class="pb-4 border-b-2 {{ request('status') == 'cancelled' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                ยกเลิกแล้ว
            </a>
        </nav>
    </div>

    <!-- Bookings List -->
    @forelse($bookings as $booking)
        <div class="bg-white rounded-lg shadow-md p-6 mb-4 hover:shadow-lg transition">
            <div class="flex flex-col md:flex-row gap-6">
                <!-- Hotel Image -->
                <div class="md:w-1/4">
                    <img src="{{ $booking->hotel->main_image_url }}"
                         alt="{{ $booking->hotel->name }}"
                         class="w-full h-40 object-cover rounded-lg">
                </div>

                <!-- Booking Details -->
                <div class="md:w-2/4">
                    <div class="flex items-start justify-between mb-2">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">{{ $booking->hotel->name }}</h3>
                            <p class="text-sm text-gray-600">{{ $booking->hotel->city }}</p>
                        </div>

                        <!-- Status Badge -->
                        <span class="px-3 py-1 rounded-full text-xs font-bold
                            @if($booking->status === 'confirmed') bg-green-100 text-green-800
                            @elseif($booking->status === 'pending') bg-yellow-100 text-yellow-800
                            @elseif($booking->status === 'cancelled') bg-red-100 text-red-800
                            @elseif($booking->status === 'completed') bg-blue-100 text-blue-800
                            @else bg-gray-100 text-gray-800
                            @endif">
                            @switch($booking->status)
                                @case('confirmed') ยืนยันแล้ว @break
                                @case('pending') รอชำระเงิน @break
                                @case('cancelled') ยกเลิกแล้ว @break
                                @case('checked_in') เช็คอินแล้ว @break
                                @case('checked_out') เช็คเอาท์แล้ว @break
                                @case('completed') เสร็จสิ้น @break
                                @default {{ $booking->status }}
                            @endswitch
                        </span>
                    </div>

                    <div class="space-y-2 text-sm text-gray-700 mb-4">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                            </svg>
                            <span>
                                {{ $booking->check_in_date->format('d M Y') }} - {{ $booking->check_out_date->format('d M Y') }}
                                ({{ $booking->number_of_nights }} คืน)
                            </span>
                        </div>
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                            </svg>
                            <span>{{ $booking->roomType->name }} - {{ $booking->number_of_rooms }} ห้อง</span>
                        </div>
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                            </svg>
                            <span>{{ $booking->number_of_adults }} ผู้ใหญ่, {{ $booking->number_of_children }} เด็ก</span>
                        </div>
                        <div>
                            <span class="text-gray-600">เลขที่การจอง:</span>
                            <span class="font-mono font-bold">{{ $booking->booking_number }}</span>
                        </div>
                    </div>
                </div>

                <!-- Actions & Price -->
                <div class="md:w-1/4 flex flex-col justify-between items-end">
                    <div class="text-right">
                        <p class="text-2xl font-bold text-blue-600">
                            ฿{{ number_format($booking->total_amount) }}
                        </p>
                        <p class="text-xs text-gray-500">
                            @if($booking->payment_status === 'paid')
                                ชำระเงินแล้ว
                            @else
                                รอชำระเงิน
                            @endif
                        </p>
                    </div>

                    <div class="flex flex-col gap-2 mt-4">
                        <a href="{{ route('hotels.bookings.show', $booking->booking_number) }}"
                           class="bg-blue-600 hover:bg-blue-700 text-white text-center font-bold py-2 px-4 rounded-lg">
                            ดูรายละเอียด
                        </a>

                        @if($booking->canBeCancelled())
                            <button onclick="confirmCancel('{{ $booking->booking_number }}')"
                                    class="bg-red-50 hover:bg-red-100 text-red-600 font-bold py-2 px-4 rounded-lg border border-red-200">
                                ยกเลิกการจอง
                            </button>
                        @endif

                        @if($booking->status === 'completed' && !$booking->review)
                            <a href="{{ route('hotels.reviews.create', $booking->booking_number) }}"
                               class="bg-yellow-50 hover:bg-yellow-100 text-yellow-600 text-center font-bold py-2 px-4 rounded-lg border border-yellow-200">
                                เขียนรีวิว
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="text-center py-12 bg-white rounded-lg shadow-md">
            <svg class="mx-auto h-24 w-24 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <h3 class="mt-4 text-lg font-medium text-gray-900">ไม่มีการจอง</h3>
            <p class="mt-2 text-gray-500">คุณยังไม่มีการจองโรงแรม</p>
            <a href="{{ route('hotels.index') }}"
               class="mt-4 inline-block bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg">
                ค้นหาโรงแรม
            </a>
        </div>
    @endforelse

    <!-- Pagination -->
    @if($bookings->hasPages())
        <div class="mt-8">
            {{ $bookings->links() }}
        </div>
    @endif
</div>

<!-- Cancel Confirmation Modal -->
<div id="cancelModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg leading-6 font-medium text-gray-900">ยืนยันการยกเลิก</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500">
                    คุณแน่ใจหรือไม่ที่จะยกเลิกการจองนี้? การยกเลิกอาจมีค่าธรรมเนียมตามนโยบายของโรงแรม
                </p>
            </div>
            <form id="cancelForm" method="POST" class="px-4 py-3">
                @csrf
                <textarea name="reason" rows="3" placeholder="เหตุผลในการยกเลิก (ถ้ามี)"
                          class="w-full rounded-lg border-gray-300 mb-3"></textarea>
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
</div>

@push('scripts')
<script>
function confirmCancel(bookingNumber) {
    const form = document.getElementById('cancelForm');
    form.action = '/hotels/bookings/' + bookingNumber + '/cancel';
    document.getElementById('cancelModal').classList.remove('hidden');
}

function closeCancelModal() {
    document.getElementById('cancelModal').classList.add('hidden');
}
</script>
@endpush
@endsection
