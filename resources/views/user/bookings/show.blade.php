{{--
    หน้ารายละเอียดการจอง (User)
    แสดงข้อมูลการจอง, ผู้ให้บริการ, timeline, รายละเอียดราคา
--}}
@extends('layouts.user-v4')

@section('title', 'รายละเอียดการจอง #' . $booking->booking_number)

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="bookingDetail()">
    {{-- Premium Hero Header (Purple-Pink-Red for Booking Detail) --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-purple-600 via-pink-600 to-red-600 dark:from-purple-800 dark:via-pink-800 dark:to-red-800 rounded-2xl shadow-2xl p-8 mb-6">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s"></div>
        </div>
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute text-white/10 text-8xl top-10 right-20" style="animation: float 6s ease-in-out infinite">
                <i class="fas fa-file-alt"></i>
            </div>
        </div>
        <div class="relative z-10">
            <a href="{{ route('user.bookings.index') }}"
               class="inline-flex items-center gap-2 text-white/90 hover:text-white transition-colors mb-4">
                <i class="fas fa-arrow-left"></i>
                <span>กลับไปรายการจอง</span>
            </a>
            <div class="flex items-center gap-4">
                <div class="glass-fusion p-4 rounded-2xl">
                    <i class="fas fa-receipt text-4xl text-white drop-shadow-lg"></i>
                </div>
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold text-white drop-shadow-lg">📄 รายละเอียดการจอง</h1>
                    <p class="text-purple-100 mt-1">#{{ $booking->booking_number }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Booking Header Card --}}
    <div class="backdrop-blur-xl bg-white/90 dark:bg-gray-800/90 border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-xl overflow-hidden mb-6">
        {{-- Status Banner --}}
        <div class="p-4 text-center text-white
            @switch($booking->status)
                @case('pending') bg-gradient-to-r from-gray-500 to-gray-600 @break
                @case('paid') bg-gradient-to-r from-blue-500 to-cyan-500 @break
                @case('notifying_provider') bg-gradient-to-r from-yellow-500 to-orange-500 @break
                @case('waiting_provider') bg-gradient-to-r from-yellow-500 to-orange-500 @break
                @case('provider_accepted') bg-gradient-to-r from-green-500 to-emerald-500 @break
                @case('provider_on_way') bg-gradient-to-r from-purple-500 to-pink-500 @break
                @case('in_progress') bg-gradient-to-r from-purple-600 to-pink-600 @break
                @case('completed') bg-gradient-to-r from-green-600 to-emerald-600 @break
                @case('cancelled') bg-gradient-to-r from-red-500 to-pink-500 @break
                @default bg-gradient-to-r from-gray-500 to-gray-600
            @endswitch">
            <div class="flex items-center justify-center gap-2">
                <i class="fas fa-{{ $booking->getStatusIcon() ?? 'circle' }}"></i>
                <span class="font-semibold">{{ $booking->getStatusLabel() ?? $booking->status }}</span>
            </div>
        </div>

        {{-- Main Info --}}
        <div class="p-6">
            <div class="flex flex-col md:flex-row md:items-start gap-6">
                {{-- Service Info --}}
                <div class="flex items-start gap-4 flex-1">
                    <div class="flex-shrink-0 w-20 h-20 rounded-2xl bg-gradient-to-br from-purple-400 to-pink-400 flex items-center justify-center text-4xl shadow-lg">
                        {{ $booking->service->category->icon ?? '🔧' }}
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-1">
                            {{ $booking->service->name }}
                        </h1>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                            {{ $booking->service->category->name ?? 'บริการทั่วไป' }}
                        </p>
                        <div class="flex flex-wrap items-center gap-3 text-sm text-gray-600 dark:text-gray-400">
                            <span class="inline-flex items-center gap-1 px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded-lg">
                                <i class="fas fa-hashtag text-purple-600"></i>
                                <span class="font-mono">{{ $booking->booking_number }}</span>
                            </span>
                            <span class="inline-flex items-center gap-1">
                                <i class="fas fa-calendar-alt text-purple-600"></i>
                                {{ $booking->scheduled_at?->format('d/m/Y') }}
                            </span>
                            <span class="inline-flex items-center gap-1">
                                <i class="fas fa-clock text-purple-600"></i>
                                {{ $booking->scheduled_at?->format('H:i') }} น.
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Price --}}
                <div class="text-right">
                    <p class="text-xs text-gray-500 dark:text-gray-400">ราคารวม</p>
                    <p class="text-3xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">
                        ฿{{ number_format($booking->total_amount, 2) }}
                    </p>
                    @if($booking->payment_status === 'paid')
                        <span class="inline-flex items-center gap-1 px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-lg text-xs mt-1">
                            <i class="fas fa-check-circle"></i>
                            ชำระเงินแล้ว
                        </span>
                    @elseif($booking->payment_status === 'pending')
                        <span class="inline-flex items-center gap-1 px-2 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 rounded-lg text-xs mt-1">
                            <i class="fas fa-clock"></i>
                            รอชำระเงิน
                        </span>
                    @endif
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700 flex flex-wrap gap-3 justify-end">
                @if(in_array($booking->status, ['provider_on_way', 'in_progress']))
                    <a href="{{ route('user.bookings.track', $booking) }}"
                       class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-xl font-semibold shadow-lg transition-all duration-200">
                        <i class="fas fa-map-marked-alt"></i>
                        ติดตามตำแหน่ง
                    </a>
                @endif

                @if($booking->status === 'pending' && \Illuminate\Support\Facades\Route::has('user.bookings.pay'))
                    <a href="{{ route('user.bookings.pay', $booking) }}"
                       class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white rounded-xl font-semibold shadow-lg transition-all duration-200">
                        <i class="fas fa-credit-card"></i>
                        ชำระเงิน
                    </a>
                @endif

                @if(in_array($booking->status, ['pending', 'paid', 'waiting_provider']))
                    <button @click="showCancelConfirm = true"
                            class="inline-flex items-center gap-2 px-6 py-3 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-xl font-semibold hover:bg-red-200 dark:hover:bg-red-900/50 transition-all duration-200">
                        <i class="fas fa-times"></i>
                        ยกเลิกการจอง
                    </button>
                @endif

                @if($booking->status === 'completed' && !$booking->review && \Illuminate\Support\Facades\Route::has('user.service-reviews.create'))
                    <a href="{{ route('user.service-reviews.create', $booking) }}"
                       class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-yellow-500 to-orange-500 hover:from-yellow-600 hover:to-orange-600 text-white rounded-xl font-semibold shadow-lg transition-all duration-200">
                        <i class="fas fa-star"></i>
                        ให้คะแนนบริการ
                    </a>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left Column --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Provider Info (if assigned) --}}
            @if($booking->provider)
                <div class="backdrop-blur-xl bg-white/90 dark:bg-gray-800/90 border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                        <i class="fas fa-user-tie text-purple-600 dark:text-purple-400 mr-2"></i>
                        ผู้ให้บริการ
                    </h3>

                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            @if($booking->provider->profile_image)
                                <img src="{{ asset('storage/' . $booking->provider->profile_image) }}"
                                     alt="{{ $booking->provider->display_name }}"
                                     class="w-16 h-16 rounded-full object-cover">
                            @else
                                <div class="w-16 h-16 rounded-full bg-gradient-to-br from-purple-400 to-pink-400 flex items-center justify-center">
                                    <i class="fas fa-user text-white text-2xl"></i>
                                </div>
                            @endif
                        </div>
                        <div class="flex-1">
                            <h4 class="font-bold text-gray-900 dark:text-white">
                                {{ $booking->provider->display_name }}
                            </h4>

                            @if($booking->provider->average_rating)
                                <div class="flex items-center gap-1 mt-1">
                                    @for($i = 1; $i <= 5; $i++)
                                        <i class="fas fa-star text-sm {{ $i <= round($booking->provider->average_rating) ? 'text-yellow-400' : 'text-gray-300' }}"></i>
                                    @endfor
                                    <span class="text-sm text-gray-600 dark:text-gray-400 ml-1">
                                        {{ number_format($booking->provider->average_rating, 1) }}
                                        ({{ $booking->provider->reviews_count ?? 0 }} รีวิว)
                                    </span>
                                </div>
                            @endif

                            @if($booking->provider->bio)
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                                    {{ $booking->provider->bio }}
                                </p>
                            @endif
                        </div>
                    </div>

                    {{-- Contact Buttons --}}
                    @if(in_array($booking->status, ['provider_accepted', 'provider_on_way', 'in_progress']))
                        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 flex gap-3">
                            @if($booking->provider->phone)
                                <a href="tel:{{ $booking->provider->phone }}"
                                   class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-3 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-xl font-semibold hover:bg-green-200 dark:hover:bg-green-900/50 transition-all duration-200">
                                    <i class="fas fa-phone"></i>
                                    โทรติดต่อ
                                </a>
                            @endif
                            <button @click="openChat()"
                                    class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-3 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded-xl font-semibold hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-all duration-200">
                                <i class="fas fa-comment"></i>
                                แชท
                            </button>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Status Timeline --}}
            <div class="backdrop-blur-xl bg-white/90 dark:bg-gray-800/90 border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6">
                    <i class="fas fa-history text-purple-600 dark:text-purple-400 mr-2"></i>
                    สถานะการให้บริการ
                </h3>

                @php
                    $statusFlow = [
                        'pending' => ['label' => 'รอชำระเงิน', 'icon' => 'clock', 'color' => 'gray'],
                        'paid' => ['label' => 'ชำระเงินแล้ว', 'icon' => 'credit-card', 'color' => 'blue'],
                        'waiting_provider' => ['label' => 'กำลังหาผู้ให้บริการ', 'icon' => 'search', 'color' => 'yellow'],
                        'provider_accepted' => ['label' => 'ผู้ให้บริการรับงาน', 'icon' => 'check-circle', 'color' => 'green'],
                        'provider_on_way' => ['label' => 'กำลังเดินทาง', 'icon' => 'route', 'color' => 'purple'],
                        'in_progress' => ['label' => 'กำลังให้บริการ', 'icon' => 'cog', 'color' => 'purple'],
                        'completed' => ['label' => 'เสร็จสิ้น', 'icon' => 'check-double', 'color' => 'green'],
                    ];
                    $statusOrder = array_keys($statusFlow);
                    $currentIndex = array_search($booking->status, $statusOrder);
                    if ($booking->status === 'cancelled') {
                        $currentIndex = -1;
                    }
                @endphp

                <div class="relative">
                    {{-- Timeline Line --}}
                    <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200 dark:bg-gray-700"></div>

                    <div class="space-y-6">
                        @foreach($statusFlow as $statusKey => $statusInfo)
                            @php
                                $index = array_search($statusKey, $statusOrder);
                                $isPassed = $index <= $currentIndex;
                                $isCurrent = $booking->status === $statusKey;
                            @endphp

                            <div class="relative flex items-start gap-4 pl-2">
                                {{-- Circle Indicator --}}
                                <div class="relative z-10 flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center
                                    @if($isCurrent)
                                        bg-{{ $statusInfo['color'] }}-500 text-white ring-4 ring-{{ $statusInfo['color'] }}-200 dark:ring-{{ $statusInfo['color'] }}-900/30
                                    @elseif($isPassed)
                                        bg-green-500 text-white
                                    @else
                                        bg-gray-300 dark:bg-gray-600 text-gray-500 dark:text-gray-400
                                    @endif">
                                    <i class="fas fa-{{ $isPassed && !$isCurrent ? 'check' : $statusInfo['icon'] }} text-sm"></i>
                                </div>

                                {{-- Content --}}
                                <div class="flex-1 pb-2">
                                    <p class="font-semibold {{ $isPassed ? 'text-gray-900 dark:text-white' : 'text-gray-400 dark:text-gray-500' }}">
                                        {{ $statusInfo['label'] }}
                                    </p>

                                    @if($isCurrent && $booking->status !== 'completed')
                                        <p class="text-sm text-{{ $statusInfo['color'] }}-600 dark:text-{{ $statusInfo['color'] }}-400">
                                            ← สถานะปัจจุบัน
                                        </p>
                                    @endif

                                    {{-- Show timestamp if passed --}}
                                    @if($isPassed && isset($booking->status_timestamps[$statusKey]))
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            {{ \Carbon\Carbon::parse($booking->status_timestamps[$statusKey])->format('d/m/Y H:i') }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        @endforeach

                        {{-- Show cancelled status if applicable --}}
                        @if($booking->status === 'cancelled')
                            <div class="relative flex items-start gap-4 pl-2">
                                <div class="relative z-10 flex-shrink-0 w-8 h-8 rounded-full bg-red-500 text-white flex items-center justify-center">
                                    <i class="fas fa-times text-sm"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="font-semibold text-red-600 dark:text-red-400">ยกเลิกแล้ว</p>
                                    @if($booking->cancellation_reason)
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            เหตุผล: {{ $booking->cancellation_reason }}
                                        </p>
                                    @endif
                                    @if($booking->cancelled_at)
                                        <p class="text-xs text-gray-500 mt-1">
                                            {{ $booking->cancelled_at->format('d/m/Y H:i') }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Service Location --}}
            @if($booking->locations && $booking->locations->count() > 0)
                @php
                    $serviceLocation = $booking->locations->where('type', 'service_location')->first();
                @endphp
                @if($serviceLocation)
                    <div class="backdrop-blur-xl bg-white/90 dark:bg-gray-800/90 border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-lg p-6">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                            <i class="fas fa-map-marker-alt text-purple-600 dark:text-purple-400 mr-2"></i>
                            สถานที่ให้บริการ
                        </h3>

                        <div class="space-y-3 text-sm">
                            <div class="flex items-start gap-3">
                                <i class="fas fa-home text-gray-400 mt-1"></i>
                                <span class="text-gray-700 dark:text-gray-300">
                                    {{ $serviceLocation->address }}
                                </span>
                            </div>
                            @if($serviceLocation->building_name)
                                <div class="flex items-start gap-3">
                                    <i class="fas fa-building text-gray-400 mt-1"></i>
                                    <span class="text-gray-700 dark:text-gray-300">
                                        {{ $serviceLocation->building_name }}
                                        @if($serviceLocation->floor_number)
                                            ชั้น {{ $serviceLocation->floor_number }}
                                        @endif
                                        @if($serviceLocation->room_number)
                                            ห้อง {{ $serviceLocation->room_number }}
                                        @endif
                                    </span>
                                </div>
                            @endif
                            @if($serviceLocation->landmark)
                                <div class="flex items-start gap-3">
                                    <i class="fas fa-landmark text-gray-400 mt-1"></i>
                                    <span class="text-gray-700 dark:text-gray-300">
                                        จุดสังเกต: {{ $serviceLocation->landmark }}
                                    </span>
                                </div>
                            @endif
                            <div class="flex items-start gap-3">
                                <i class="fas fa-user text-gray-400 mt-1"></i>
                                <span class="text-gray-700 dark:text-gray-300">
                                    {{ $serviceLocation->contact_name ?? $booking->user->name }}
                                </span>
                            </div>
                            @if($serviceLocation->contact_phone)
                                <div class="flex items-start gap-3">
                                    <i class="fas fa-phone text-gray-400 mt-1"></i>
                                    <span class="text-gray-700 dark:text-gray-300">
                                        {{ $serviceLocation->contact_phone }}
                                    </span>
                                </div>
                            @endif
                        </div>

                        {{-- Map Preview --}}
                        @if($serviceLocation->latitude && $serviceLocation->longitude)
                            <div class="mt-4 rounded-xl overflow-hidden h-48 bg-gray-200 dark:bg-gray-700">
                                <div id="map-preview" class="w-full h-full"></div>
                            </div>
                        @endif
                    </div>
                @endif
            @endif
        </div>

        {{-- Right Column --}}
        <div class="space-y-6">
            {{-- Price Breakdown --}}
            <div class="backdrop-blur-xl bg-white/90 dark:bg-gray-800/90 border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-receipt text-purple-600 dark:text-purple-400 mr-2"></i>
                    รายละเอียดราคา
                </h3>

                <div class="space-y-3 text-sm">
                    {{-- Base Price --}}
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">ค่าบริการ</span>
                        <span class="text-gray-900 dark:text-white font-medium">
                            ฿{{ number_format($booking->base_price, 2) }}
                        </span>
                    </div>

                    {{-- Distance Price --}}
                    @if($booking->distance_price > 0)
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">
                                ค่าเดินทาง ({{ $booking->distance_km }} km)
                            </span>
                            <span class="text-gray-900 dark:text-white font-medium">
                                ฿{{ number_format($booking->distance_price, 2) }}
                            </span>
                        </div>
                    @endif

                    {{-- Options Price --}}
                    @if($booking->options_price > 0)
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">ออฟชั่นเพิ่มเติม</span>
                            <span class="text-gray-900 dark:text-white font-medium">
                                ฿{{ number_format($booking->options_price, 2) }}
                            </span>
                        </div>
                    @endif

                    {{-- Subtotal --}}
                    <div class="flex justify-between pt-2 border-t border-gray-200 dark:border-gray-700">
                        <span class="text-gray-600 dark:text-gray-400">ยอดรวม</span>
                        <span class="text-gray-900 dark:text-white font-medium">
                            ฿{{ number_format($booking->subtotal, 2) }}
                        </span>
                    </div>

                    {{-- Tax --}}
                    @if($booking->tax_amount > 0)
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">ภาษี</span>
                            <span class="text-gray-900 dark:text-white font-medium">
                                ฿{{ number_format($booking->tax_amount, 2) }}
                            </span>
                        </div>
                    @endif

                    {{-- Discount --}}
                    @if($booking->discount_amount > 0)
                        <div class="flex justify-between">
                            <span class="text-green-600 dark:text-green-400">ส่วนลด</span>
                            <span class="text-green-600 dark:text-green-400 font-medium">
                                -฿{{ number_format($booking->discount_amount, 2) }}
                            </span>
                        </div>
                    @endif

                    {{-- Total --}}
                    <div class="flex justify-between pt-3 mt-2 border-t-2 border-purple-200 dark:border-purple-800">
                        <span class="text-gray-900 dark:text-white font-bold">ราคารวมทั้งหมด</span>
                        <span class="text-xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">
                            ฿{{ number_format($booking->total_amount, 2) }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Customer Notes --}}
            @if($booking->customer_notes)
                <div class="backdrop-blur-xl bg-white/90 dark:bg-gray-800/90 border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                        <i class="fas fa-sticky-note text-purple-600 dark:text-purple-400 mr-2"></i>
                        หมายเหตุ
                    </h3>
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        {{ $booking->customer_notes }}
                    </p>
                </div>
            @endif

            {{-- Booking Items --}}
            @if($booking->items && $booking->items->count() > 0)
                <div class="backdrop-blur-xl bg-white/90 dark:bg-gray-800/90 border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                        <i class="fas fa-list text-purple-600 dark:text-purple-400 mr-2"></i>
                        รายการ
                    </h3>

                    <div class="space-y-3">
                        @foreach($booking->items as $item)
                            <div class="flex justify-between items-start text-sm">
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">
                                        {{ $item->name }}
                                    </p>
                                    @if($item->description)
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $item->description }}
                                        </p>
                                    @endif
                                </div>
                                <span class="text-gray-700 dark:text-gray-300 font-medium">
                                    ฿{{ number_format($item->subtotal, 2) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- PV Info (if applicable) --}}
            @if($booking->service && $booking->service->pv_value > 0)
                <div class="backdrop-blur-xl bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 border border-purple-200 dark:border-purple-800 rounded-2xl shadow-lg p-6">
                    <h3 class="text-lg font-bold text-purple-900 dark:text-purple-200 mb-4">
                        <i class="fas fa-star text-purple-600 dark:text-purple-400 mr-2"></i>
                        PV ที่ได้รับ
                    </h3>
                    <div class="text-center">
                        <p class="text-4xl font-bold text-purple-600 dark:text-purple-400">
                            {{ number_format($booking->service->pv_value) }}
                        </p>
                        <p class="text-sm text-purple-700 dark:text-purple-300 mt-1">
                            Point Value
                        </p>
                    </div>
                </div>
            @endif

            {{-- Help --}}
            <div class="backdrop-blur-xl bg-white/90 dark:bg-gray-800/90 border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-question-circle text-purple-600 dark:text-purple-400 mr-2"></i>
                    ต้องการความช่วยเหลือ?
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    หากมีปัญหาหรือข้อสงสัย สามารถติดต่อทีมงานได้ตลอด 24 ชั่วโมง
                </p>
                <a href="#"
                   class="block w-full text-center px-4 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-semibold hover:bg-gray-200 dark:hover:bg-gray-600 transition-all duration-200">
                    <i class="fas fa-headset mr-2"></i>
                    ติดต่อฝ่ายสนับสนุน
                </a>
            </div>
        </div>
    </div>

    {{-- Cancel Confirmation Modal --}}
    <div x-show="showCancelConfirm"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div x-show="showCancelConfirm"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 class="fixed inset-0 bg-black/50 backdrop-blur-sm"
                 @click="showCancelConfirm = false"></div>

            <div x-show="showCancelConfirm"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="tp-card relative rounded-2xl p-6 max-w-md w-full">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>
                    ยืนยันการยกเลิก
                </h3>

                <form action="{{ route('user.bookings.cancel', $booking) }}" method="POST">
                    @csrf

                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        คุณแน่ใจหรือไม่ว่าต้องการยกเลิกการจองนี้?
                    </p>

                    @if($booking->payment_status === 'paid')
                        <div class="p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg mb-4">
                            <p class="text-sm text-yellow-700 dark:text-yellow-400">
                                <i class="fas fa-info-circle mr-1"></i>
                                เงินจะถูกคืนเข้า Wallet ของคุณ
                            </p>
                        </div>
                    @endif

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            เหตุผลในการยกเลิก (ไม่บังคับ)
                        </label>
                        <textarea name="reason"
                                  rows="3"
                                  class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500"
                                  placeholder="กรุณาระบุเหตุผล..."></textarea>
                    </div>

                    <div class="flex gap-3">
                        <button type="submit"
                                class="flex-1 px-4 py-3 bg-red-600 hover:bg-red-700 text-white rounded-xl font-semibold transition-all duration-200">
                            ยืนยันยกเลิก
                        </button>
                        <button type="button"
                                @click="showCancelConfirm = false"
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
function bookingDetail() {
    return {
        showCancelConfirm: false,

        openChat() {
            // TODO: Open chat with provider
            alert('ฟีเจอร์แชทกำลังพัฒนา');
        }
    }
}
</script>

@if(isset($serviceLocation) && $serviceLocation->latitude && $serviceLocation->longitude)
<script>
// Initialize map when Google Maps is loaded
function initBookingMap() {
    const map = new google.maps.Map(document.getElementById('map-preview'), {
        center: { lat: {{ $serviceLocation->latitude }}, lng: {{ $serviceLocation->longitude }} },
        zoom: 15,
        styles: [
            { elementType: "geometry", stylers: [{ color: "#f5f5f5" }] },
            { elementType: "labels.icon", stylers: [{ visibility: "off" }] },
            { elementType: "labels.text.fill", stylers: [{ color: "#616161" }] },
        ]
    });

    new google.maps.Marker({
        position: { lat: {{ $serviceLocation->latitude }}, lng: {{ $serviceLocation->longitude }} },
        map: map,
        title: 'สถานที่ให้บริการ'
    });
}

// Load Google Maps if not already loaded
if (typeof google !== 'undefined') {
    initBookingMap();
} else {
    window.initBookingMap = initBookingMap;
}
</script>
@endif
@endpush

@push('styles')
<style>
.glass-fusion {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}
@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-20px); }
}
</style>
@endpush
