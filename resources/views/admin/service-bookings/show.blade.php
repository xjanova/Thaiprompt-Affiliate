@extends('layouts.admin')

@section('title', 'รายละเอียดการจอง')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('admin.service-bookings.index') }}" class="text-purple-600 hover:text-purple-700">
            <i class="fas fa-arrow-left mr-2"></i>กลับไปหน้ารายการ
        </a>
    </div>

    {{-- Header --}}
    <div class="backdrop-blur-xl bg-white/90 dark:bg-gray-800/90 rounded-2xl shadow-xl p-6 mb-6">
        <div class="flex items-start justify-between">
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
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Info --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Customer Info --}}
            <div class="backdrop-blur-xl bg-white/90 dark:bg-gray-800/90 rounded-xl shadow-lg p-6">
                <h3 class="font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-user text-purple-600"></i>
                    ข้อมูลลูกค้า
                </h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">ชื่อ:</span>
                        <span class="font-semibold">{{ $booking->user->name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">อีเมล:</span>
                        <span>{{ $booking->user->email }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">เบอร์โทร:</span>
                        <span>{{ $booking->contact_phone ?? $booking->user->phone ?? '-' }}</span>
                    </div>
                </div>
            </div>

            {{-- Provider Info --}}
            @if($booking->provider)
            <div class="backdrop-blur-xl bg-white/90 dark:bg-gray-800/90 rounded-xl shadow-lg p-6">
                <h3 class="font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-user-tie text-purple-600"></i>
                    ผู้ให้บริการ
                </h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">ชื่อ:</span>
                        <span class="font-semibold">{{ $booking->provider->name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">เบอร์โทร:</span>
                        <span>{{ $booking->provider->phone ?? '-' }}</span>
                    </div>
                    @if($booking->provider_accepted_at)
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">รับงานเมื่อ:</span>
                            <span>{{ $booking->provider_accepted_at->format('d/m/Y H:i') }}</span>
                        </div>
                    @endif
                </div>
            </div>
            @else
            <div class="backdrop-blur-xl bg-yellow-50/90 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl p-6 text-center">
                <i class="fas fa-hourglass-half text-yellow-600 text-3xl mb-2"></i>
                <p class="text-yellow-800 dark:text-yellow-400 font-semibold">กำลังหาผู้ให้บริการ...</p>
            </div>
            @endif

            {{-- Location Info --}}
            @if($booking->customer_address)
            <div class="backdrop-blur-xl bg-white/90 dark:bg-gray-800/90 rounded-xl shadow-lg p-6">
                <h3 class="font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-map-marker-alt text-purple-600"></i>
                    สถานที่ให้บริการ
                </h3>
                <p class="text-sm text-gray-700 dark:text-gray-300">{{ $booking->customer_address }}</p>
                @if($booking->landmark)
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                        <i class="fas fa-building mr-1"></i>{{ $booking->landmark }}
                    </p>
                @endif
            </div>
            @endif

            {{-- Notes --}}
            @if($booking->customer_notes)
            <div class="backdrop-blur-xl bg-blue-50/90 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6">
                <h3 class="font-bold text-blue-900 dark:text-blue-300 mb-2 flex items-center gap-2">
                    <i class="fas fa-comment"></i>
                    หมายเหตุจากลูกค้า
                </h3>
                <p class="text-sm text-blue-800 dark:text-blue-400">{{ $booking->customer_notes }}</p>
            </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Booking Summary --}}
            <div class="backdrop-blur-xl bg-white/90 dark:bg-gray-800/90 rounded-xl shadow-lg p-6">
                <h3 class="font-bold text-gray-900 dark:text-white mb-4">สรุปการจอง</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">วันที่นัดหมาย:</span>
                        <span class="font-semibold">{{ $booking->scheduled_at?->format('d/m/Y') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">เวลา:</span>
                        <span class="font-semibold">{{ $booking->scheduled_at?->format('H:i') }} น.</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">ระยะเวลา:</span>
                        <span>{{ $booking->service->estimated_duration_minutes ?? 60 }} นาที</span>
                    </div>
                </div>
            </div>

            {{-- Pricing --}}
            <div class="backdrop-blur-xl bg-gradient-to-br from-purple-50/90 to-pink-50/90 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl shadow-lg p-6 border border-purple-200 dark:border-purple-800">
                <h3 class="font-bold text-purple-900 dark:text-purple-300 mb-4">ราคา</h3>
                <div class="space-y-2 text-sm mb-4">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">ค่าบริการ:</span>
                        <span>฿{{ number_format($booking->service_price, 2) }}</span>
                    </div>
                    @if($booking->distance_price > 0)
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">ค่าระยะทาง:</span>
                            <span>฿{{ number_format($booking->distance_price, 2) }}</span>
                        </div>
                    @endif
                    @if($booking->platform_fee > 0)
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">ค่าแพลตฟอร์ม:</span>
                            <span>฿{{ number_format($booking->platform_fee, 2) }}</span>
                        </div>
                    @endif
                </div>
                <div class="pt-3 border-t border-purple-300 dark:border-purple-700 flex justify-between text-lg font-bold text-purple-600 dark:text-purple-400">
                    <span>รวมทั้งหมด:</span>
                    <span>฿{{ number_format($booking->total_price, 2) }}</span>
                </div>
            </div>

            {{-- Actions --}}
            <div class="backdrop-blur-xl bg-white/90 dark:bg-gray-800/90 rounded-xl shadow-lg p-6">
                <h3 class="font-bold text-gray-900 dark:text-white mb-4">จัดการ</h3>
                <div class="space-y-3">
                    @if(!$booking->provider && in_array($booking->status, ['paid', 'waiting_provider']))
                        <button class="w-full px-4 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-semibold transition-all">
                            <i class="fas fa-user-plus mr-2"></i>มอบหมายผู้ให้บริการ
                        </button>
                    @endif
                    @if(in_array($booking->status, ['pending', 'paid']))
                        <button class="w-full px-4 py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg font-semibold transition-all">
                            <i class="fas fa-times mr-2"></i>ยกเลิกการจอง
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
