@extends('layouts.app')

@section('title', 'รายละเอียดการจอง')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="mb-6">
        <a href="{{ route('user.bookings.index') }}" class="text-purple-600 hover:text-purple-700">
            <i class="fas fa-arrow-left mr-2"></i>กลับ
        </a>
    </div>

    {{-- Booking Header --}}
    <div class="backdrop-blur-xl bg-white/90 dark:bg-gray-800/90 rounded-2xl shadow-xl p-6 mb-6">
        <div class="flex items-center gap-4 mb-4">
            <div class="w-16 h-16 rounded-xl bg-gradient-to-br from-purple-400 to-pink-400 flex items-center justify-center text-3xl">
                {{ $booking->service->category->icon ?? '🔧' }}
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $booking->service->name }}</h1>
                <p class="text-gray-600 dark:text-gray-400">{{ $booking->booking_number }}</p>
            </div>
            <x-booking-status-badge :status="$booking->status" />
        </div>
    </div>

    {{-- Booking Info --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="backdrop-blur-xl bg-white/90 dark:bg-gray-800/90 rounded-xl p-6">
            <h3 class="font-bold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-info-circle mr-2"></i>ข้อมูลการจอง
            </h3>
            <div class="space-y-3 text-sm">
                <div><strong>วันที่:</strong> {{ $booking->scheduled_at?->format('d/m/Y H:i') }}</div>
                <div><strong>ราคา:</strong> ฿{{ number_format($booking->total_price, 2) }}</div>
                @if($booking->provider)
                    <div><strong>ผู้ให้บริการ:</strong> {{ $booking->provider->name }}</div>
                    <div><strong>เบอร์โทร:</strong> {{ $booking->provider->phone ?? '-' }}</div>
                @endif
            </div>
        </div>

        <div class="backdrop-blur-xl bg-white/90 dark:bg-gray-800/90 rounded-xl p-6">
            <h3 class="font-bold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-map-marker-alt mr-2"></i>สถานที่
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $booking->customer_address ?? '-' }}</p>
        </div>
    </div>

    {{-- Status Timeline --}}
    <div class="backdrop-blur-xl bg-white/90 dark:bg-gray-800/90 rounded-xl p-6">
        <h3 class="font-bold text-gray-900 dark:text-white mb-4">
            <i class="fas fa-list-ol mr-2"></i>สถานะการให้บริการ
        </h3>
        <div class="space-y-4">
            @foreach(['pending' => 'รอดำเนินการ', 'paid' => 'ชำระเงินแล้ว', 'provider_accepted' => 'ผู้ให้บริการรับงาน', 'provider_on_way' => 'กำลังเดินทาง', 'in_progress' => 'กำลังให้บริการ', 'completed' => 'เสร็จสิ้น'] as $statusKey => $statusLabel)
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center {{ $booking->status === $statusKey ? 'bg-green-500' : 'bg-gray-300' }}">
                        <i class="fas fa-{{ $booking->status === $statusKey ? 'check' : 'circle' }} text-white text-xs"></i>
                    </div>
                    <span class="text-sm {{ $booking->status === $statusKey ? 'font-bold text-gray-900 dark:text-white' : 'text-gray-500' }}">
                        {{ $statusLabel }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
