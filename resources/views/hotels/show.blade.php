{{--
    หน้ารายละเอียดโรงแรม (V3 Theme)

    ฟีเจอร์:
    - แกลเลอรีรูปภาพ
    - รายละเอียดห้องพัก
    - ระบบจองห้อง
    - รีวิวจากผู้เข้าพัก
    - Dark/Light mode support
    - Glassmorphism effects
--}}
@extends('layouts.app')

@section('title', $hotel->name . ' - จองที่พัก')

@section('content')
<div x-data="hotelDetail()" class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-cyan-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900">

    {{-- Header & Gallery --}}
    <div class="bg-white dark:bg-slate-800 shadow-sm">
        <div class="container mx-auto px-4 py-6">
            {{-- Breadcrumb --}}
            <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-4">
                <a href="{{ route('hotels.index') }}" class="hover:text-cyan-600 dark:hover:text-cyan-400">โรงแรม</a>
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                @if($hotel->province)
                    <a href="{{ route('hotels.by-province', $hotel->province_id) }}" class="hover:text-cyan-600 dark:hover:text-cyan-400">{{ $hotel->province->name_th }}</a>
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                @endif
                <span class="text-gray-800 dark:text-white font-medium">{{ $hotel->name }}</span>
            </nav>

            {{-- Hotel Header --}}
            <div class="flex flex-col md:flex-row justify-between items-start gap-4">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2">
                        <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">{{ $hotel->name }}</h1>
                        @if($hotel->star_rating)
                            <div class="flex items-center gap-0.5 text-amber-400">
                                @for($i = 0; $i < $hotel->star_rating; $i++)
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                @endfor
                            </div>
                        @endif
                    </div>

                    <div class="flex flex-wrap items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                        <span class="inline-flex items-center gap-1">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                            </svg>
                            {{ $hotel->address }}{{ $hotel->province ? ', ' . $hotel->province->name_th : '' }}
                        </span>
                        @if($hotel->phone)
                            <span class="inline-flex items-center gap-1">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
                                </svg>
                                {{ $hotel->phone }}
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Rating Badge --}}
                @if($hotel->rating > 0)
                    <div class="flex items-center gap-3">
                        <div class="text-right">
                            <div class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                @if($hotel->rating >= 4.5) ยอดเยี่ยม
                                @elseif($hotel->rating >= 4) ดีมาก
                                @elseif($hotel->rating >= 3.5) ดี
                                @else พอใช้
                                @endif
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ number_format($hotel->review_count) }} รีวิว
                            </div>
                        </div>
                        <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center text-white text-xl font-bold shadow-lg shadow-cyan-500/30">
                            {{ number_format($hotel->rating, 1) }}
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Image Gallery --}}
        <div class="container mx-auto px-4 pb-6">
            <div class="grid grid-cols-4 gap-2 md:gap-3 rounded-2xl overflow-hidden" x-data="{ showGallery: false, currentImage: 0 }">
                {{-- Main Image --}}
                <div class="col-span-4 md:col-span-2 row-span-2 relative cursor-pointer" @click="showGallery = true; currentImage = 0">
                    <img src="{{ $hotel->main_image_url }}"
                         alt="{{ $hotel->name }}"
                         class="w-full h-64 md:h-80 object-cover hover:scale-105 transition-transform duration-500">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/30 to-transparent opacity-0 hover:opacity-100 transition-opacity flex items-end justify-center pb-4">
                        <span class="text-white text-sm font-medium">คลิกเพื่อดูแกลเลอรี</span>
                    </div>
                </div>

                {{-- Gallery Images --}}
                @if($hotel->gallery_images_url)
                    @foreach(array_slice($hotel->gallery_images_url, 0, 4) as $index => $image)
                        <div class="hidden md:block cursor-pointer" @click="showGallery = true; currentImage = {{ $index + 1 }}">
                            <img src="{{ $image }}"
                                 alt="{{ $hotel->name }}"
                                 class="w-full h-40 object-cover hover:scale-105 transition-transform duration-500">
                        </div>
                    @endforeach
                @endif

                {{-- Gallery Modal --}}
                <template x-teleport="body">
                    <div x-show="showGallery"
                         x-transition:enter="ease-out duration-300"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="ease-in duration-200"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         class="fixed inset-0 z-50 bg-black/90 flex items-center justify-center p-4"
                         @click.self="showGallery = false">

                        <button @click="showGallery = false" class="absolute top-4 right-4 text-white hover:text-gray-300 transition-colors">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>

                        <img :src="[{{ json_encode(array_merge([$hotel->main_image_url], $hotel->gallery_images_url ?? [])) }}][currentImage]"
                             class="max-h-[90vh] max-w-[90vw] object-contain rounded-lg">

                        {{-- Navigation --}}
                        <button @click="currentImage = (currentImage - 1 + {{ count($hotel->gallery_images_url ?? []) + 1 }}) % {{ count($hotel->gallery_images_url ?? []) + 1 }}"
                                class="absolute left-4 text-white hover:text-gray-300 transition-colors">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </button>
                        <button @click="currentImage = (currentImage + 1) % {{ count($hotel->gallery_images_url ?? []) + 1 }}"
                                class="absolute right-4 text-white hover:text-gray-300 transition-colors">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- Left Column (Content) --}}
            <div class="lg:col-span-2 space-y-8">
                {{-- Description --}}
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-slate-700">
                    <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-4">เกี่ยวกับที่พัก</h2>
                    <p class="text-gray-600 dark:text-gray-300 leading-relaxed">{{ $hotel->description }}</p>
                </div>

                {{-- Facilities --}}
                @if($hotel->facilities && $hotel->facilities->count() > 0)
                    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-slate-700">
                        <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-4">สิ่งอำนวยความสะดวก</h2>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            @foreach($hotel->facilities as $facility)
                                <div class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 dark:bg-slate-700/50">
                                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-cyan-500 to-blue-500 flex items-center justify-center text-white">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $facility->name }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Room Types --}}
                <div id="rooms" class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-slate-700">
                    <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-6">เลือกห้องพัก</h2>

                    @forelse($hotel->roomTypes as $roomType)
                        <div class="border border-gray-200 dark:border-slate-600 rounded-xl p-4 md:p-6 mb-4 hover:shadow-xl transition-all duration-300 hover:border-cyan-300 dark:hover:border-cyan-600">
                            <div class="flex flex-col md:flex-row gap-4 md:gap-6">
                                {{-- Room Image --}}
                                <div class="md:w-1/3">
                                    <img src="{{ $roomType->main_image_url }}"
                                         alt="{{ $roomType->name }}"
                                         class="w-full h-48 md:h-40 object-cover rounded-xl">
                                </div>

                                {{-- Room Details --}}
                                <div class="md:w-2/3 flex flex-col">
                                    <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-2">{{ $roomType->name }}</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3 line-clamp-2">{{ $roomType->description }}</p>

                                    {{-- Room Info --}}
                                    <div class="flex flex-wrap gap-3 mb-4 text-sm text-gray-600 dark:text-gray-400">
                                        @if($roomType->size_sqm)
                                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-gray-100 dark:bg-slate-700">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                                                </svg>
                                                {{ $roomType->size_sqm }} ตร.ม.
                                            </span>
                                        @endif
                                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-gray-100 dark:bg-slate-700">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                                            </svg>
                                            {{ $roomType->max_adults }} ผู้ใหญ่
                                            @if($roomType->max_children > 0)
                                                , {{ $roomType->max_children }} เด็ก
                                            @endif
                                        </span>
                                    </div>

                                    {{-- Amenities --}}
                                    @if($roomType->amenities)
                                        <div class="flex flex-wrap gap-2 mb-4">
                                            @foreach(array_slice($roomType->amenities, 0, 5) as $amenity)
                                                <span class="text-xs px-2 py-1 rounded-full bg-cyan-50 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-400">
                                                    {{ $amenity }}
                                                </span>
                                            @endforeach
                                            @if(count($roomType->amenities) > 5)
                                                <span class="text-xs px-2 py-1 rounded-full bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-gray-400">
                                                    +{{ count($roomType->amenities) - 5 }} อื่นๆ
                                                </span>
                                            @endif
                                        </div>
                                    @endif

                                    {{-- Price & Book Button --}}
                                    <div class="flex items-end justify-between mt-auto pt-4 border-t border-gray-100 dark:border-slate-700">
                                        <div>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">เริ่มต้น</p>
                                            <p class="text-2xl font-bold bg-gradient-to-r from-cyan-600 to-blue-600 bg-clip-text text-transparent">
                                                ฿{{ number_format($roomType->base_price) }}
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">ต่อคืน (ไม่รวมภาษี)</p>
                                        </div>

                                        <a href="{{ route('hotels.bookings.create', [
                                                'room_type_id' => $roomType->id,
                                                'check_in' => request('check_in', date('Y-m-d')),
                                                'check_out' => request('check_out', date('Y-m-d', strtotime('+1 day'))),
                                                'adults' => request('adults', 2),
                                                'children' => request('children', 0)
                                            ]) }}"
                                           class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-500 hover:from-cyan-600 hover:to-blue-600 text-white font-semibold shadow-lg shadow-cyan-500/30 hover:shadow-cyan-500/50 transition-all">
                                            จองเลย
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                            <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>
                            </svg>
                            <p>ไม่มีห้องพักว่างในขณะนี้</p>
                        </div>
                    @endforelse
                </div>

                {{-- Reviews --}}
                <div id="reviews" class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-slate-700">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-800 dark:text-white">รีวิวจากผู้เข้าพัก</h2>
                        <a href="{{ route('hotels.reviews.index', $hotel->slug) }}"
                           class="text-cyan-600 dark:text-cyan-400 hover:underline text-sm font-medium">
                            ดูทั้งหมด ({{ $hotel->review_count }})
                        </a>
                    </div>

                    @forelse($hotel->reviews->take(5) as $review)
                        <div class="border-b border-gray-100 dark:border-slate-700 last:border-0 pb-4 mb-4 last:mb-0 last:pb-0">
                            <div class="flex justify-between items-start mb-2">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-cyan-500 to-blue-500 flex items-center justify-center text-white font-bold">
                                        {{ mb_substr($review->guest_name, 0, 1) }}
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-gray-800 dark:text-white">{{ $review->guest_name }}</h4>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $review->created_at->diffForHumans() }}</p>
                                    </div>
                                </div>
                                <div class="px-3 py-1 rounded-lg bg-gradient-to-r from-cyan-500 to-blue-500 text-white text-sm font-bold">
                                    {{ $review->overall_rating }}/5
                                </div>
                            </div>

                            @if($review->title)
                                <h5 class="font-semibold text-gray-800 dark:text-white mb-1">{{ $review->title }}</h5>
                            @endif
                            <p class="text-gray-600 dark:text-gray-400 text-sm">{{ Str::limit($review->comment, 200) }}</p>
                        </div>
                    @empty
                        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                            <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                            </svg>
                            <p>ยังไม่มีรีวิว เป็นคนแรกที่รีวิวที่พักนี้!</p>
                        </div>
                    @endforelse
                </div>

                {{-- Location Map --}}
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-slate-700">
                    <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-4">ที่ตั้ง</h2>
                    <div class="bg-gray-100 dark:bg-slate-700 rounded-xl p-4 mb-4">
                        <p class="text-gray-700 dark:text-gray-300">
                            <strong>{{ $hotel->name }}</strong><br>
                            {{ $hotel->address }}<br>
                            @if($hotel->province){{ $hotel->province->name_th }}, @endif{{ $hotel->city }} {{ $hotel->postal_code }}<br>
                            {{ $hotel->country }}
                        </p>
                    </div>

                    @if($hotel->latitude && $hotel->longitude)
                        <div class="aspect-video bg-gray-200 dark:bg-slate-700 rounded-xl overflow-hidden">
                            <iframe
                                src="https://maps.google.com/maps?q={{ $hotel->latitude }},{{ $hotel->longitude }}&z=15&output=embed"
                                width="100%"
                                height="100%"
                                style="border:0;"
                                allowfullscreen=""
                                loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade">
                            </iframe>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Right Column (Sidebar) --}}
            <div class="lg:col-span-1">
                {{-- Booking Widget (Sticky) --}}
                <div class="sticky top-4 space-y-6">
                    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-6 border-2 border-cyan-500 dark:border-cyan-600">
                        <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4">จองที่พักนี้</h3>

                        <form action="{{ route('hotels.show', $hotel->slug) }}" method="GET" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เช็คอิน</label>
                                <input type="date"
                                       name="check_in"
                                       value="{{ request('check_in', date('Y-m-d')) }}"
                                       min="{{ date('Y-m-d') }}"
                                       class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-white focus:ring-2 focus:ring-cyan-400 focus:border-transparent"
                                       required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เช็คเอาท์</label>
                                <input type="date"
                                       name="check_out"
                                       value="{{ request('check_out', date('Y-m-d', strtotime('+1 day'))) }}"
                                       min="{{ date('Y-m-d', strtotime('+1 day')) }}"
                                       class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-white focus:ring-2 focus:ring-cyan-400 focus:border-transparent"
                                       required>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ผู้ใหญ่</label>
                                    <select name="adults" class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-white focus:ring-2 focus:ring-cyan-400 focus:border-transparent">
                                        @for($i = 1; $i <= 10; $i++)
                                            <option value="{{ $i }}" {{ request('adults', 2) == $i ? 'selected' : '' }}>{{ $i }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เด็ก</label>
                                    <select name="children" class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-white focus:ring-2 focus:ring-cyan-400 focus:border-transparent">
                                        @for($i = 0; $i <= 5; $i++)
                                            <option value="{{ $i }}" {{ request('children', 0) == $i ? 'selected' : '' }}>{{ $i }}</option>
                                        @endfor
                                    </select>
                                </div>
                            </div>

                            <button type="submit" class="w-full py-3 px-6 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-500 hover:from-cyan-600 hover:to-blue-600 text-white font-bold shadow-lg shadow-cyan-500/30 hover:shadow-cyan-500/50 transition-all">
                                ตรวจสอบห้องว่าง
                            </button>
                        </form>

                        {{-- Price Summary --}}
                        <div class="mt-6 pt-6 border-t border-gray-100 dark:border-slate-700">
                            <div class="text-center">
                                <span class="text-sm text-gray-500 dark:text-gray-400">ราคาเริ่มต้น</span>
                                <p class="text-3xl font-bold bg-gradient-to-r from-cyan-600 to-blue-600 bg-clip-text text-transparent">
                                    ฿{{ number_format($hotel->lowest_price) }}
                                </p>
                                <span class="text-xs text-gray-500 dark:text-gray-400">ต่อคืน</span>
                            </div>
                        </div>

                        {{-- Check-in/out Info --}}
                        <div class="mt-4 pt-4 border-t border-gray-100 dark:border-slate-700 space-y-2 text-sm text-gray-600 dark:text-gray-400">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                </svg>
                                <span>เช็คอิน: {{ \Carbon\Carbon::parse($hotel->check_in_time)->format('H:i') }} น.</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                </svg>
                                <span>เช็คเอาท์: {{ \Carbon\Carbon::parse($hotel->check_out_time)->format('H:i') }} น.</span>
                            </div>
                        </div>
                    </div>

                    {{-- Hotels in Same Province --}}
                    @if(isset($provinceHotels) && $provinceHotels->count() > 0)
                        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-slate-700">
                            <h3 class="font-bold text-gray-800 dark:text-white mb-4">โรงแรมใน{{ $hotel->province->name_th }}</h3>
                            <div class="space-y-3">
                                @foreach($provinceHotels as $related)
                                    <a href="{{ route('hotels.show', $related->slug) }}"
                                       class="flex gap-3 p-2 -mx-2 rounded-xl hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-colors">
                                        <img src="{{ $related->main_image_url }}"
                                             alt="{{ $related->name }}"
                                             class="w-16 h-16 rounded-lg object-cover">
                                        <div class="flex-1 min-w-0">
                                            <h4 class="font-medium text-sm text-gray-800 dark:text-white truncate">{{ $related->name }}</h4>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $related->city }}</p>
                                            <p class="text-sm font-bold text-cyan-600 dark:text-cyan-400 mt-1">฿{{ number_format($related->lowest_price) }}</p>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Related Hotels --}}
                    @if(isset($relatedHotels) && $relatedHotels->count() > 0)
                        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-slate-700">
                            <h3 class="font-bold text-gray-800 dark:text-white mb-4">โรงแรมที่คล้ายกัน</h3>
                            <div class="space-y-3">
                                @foreach($relatedHotels->take(4) as $related)
                                    <a href="{{ route('hotels.show', $related->slug) }}"
                                       class="flex gap-3 p-2 -mx-2 rounded-xl hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-colors">
                                        <img src="{{ $related->main_image_url }}"
                                             alt="{{ $related->name }}"
                                             class="w-16 h-16 rounded-lg object-cover">
                                        <div class="flex-1 min-w-0">
                                            <h4 class="font-medium text-sm text-gray-800 dark:text-white truncate">{{ $related->name }}</h4>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $related->city }}</p>
                                            <p class="text-sm font-bold text-cyan-600 dark:text-cyan-400 mt-1">฿{{ number_format($related->lowest_price) }}</p>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function hotelDetail() {
    return {
        // จะเพิ่ม logic ที่นี่ถ้าต้องการ
    };
}
</script>
@endpush
@endsection
