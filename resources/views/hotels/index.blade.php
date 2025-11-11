@extends('layouts.app')

@section('content')
<div class="bg-gradient-to-br from-blue-500 to-blue-700 py-16">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-4xl font-bold text-white mb-8 text-center">ค้นหาโรงแรมและรีสอร์ท</h1>

            <!-- Search Form -->
            <form action="{{ route('hotels.search') }}" method="GET" class="bg-white rounded-lg shadow-xl p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Location -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">สถานที่</label>
                        <input type="text" name="city" value="{{ request('city') }}"
                               placeholder="กรุงเทพ, ภูเก็ต, เชียงใหม่..."
                               class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <!-- Check-in -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">วันเช็คอิน</label>
                        <input type="date" name="check_in" value="{{ request('check_in') }}"
                               min="{{ date('Y-m-d') }}"
                               class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <!-- Check-out -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">วันเช็คเอาท์</label>
                        <input type="date" name="check_out" value="{{ request('check_out') }}"
                               min="{{ date('Y-m-d', strtotime('+1 day')) }}"
                               class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <!-- Guests -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">ผู้เข้าพัก</label>
                        <select name="adults" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                            @for($i = 1; $i <= 10; $i++)
                                <option value="{{ $i }}" {{ request('adults') == $i ? 'selected' : '' }}>
                                    {{ $i }} ผู้ใหญ่
                                </option>
                            @endfor
                        </select>
                    </div>
                </div>

                <!-- Advanced Filters -->
                <div class="mt-4" x-data="{ showFilters: false }">
                    <button type="button" @click="showFilters = !showFilters"
                            class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                        <span x-text="showFilters ? 'ซ่อนตัวกรองเพิ่มเติม' : 'แสดงตัวกรองเพิ่มเติม'"></span>
                    </button>

                    <div x-show="showFilters" x-collapse class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Price Range -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">ช่วงราคา (บาท/คืน)</label>
                            <div class="flex gap-2">
                                <input type="number" name="min_price" value="{{ request('min_price') }}"
                                       placeholder="ต่ำสุด" class="w-full rounded-lg border-gray-300">
                                <input type="number" name="max_price" value="{{ request('max_price') }}"
                                       placeholder="สูงสุด" class="w-full rounded-lg border-gray-300">
                            </div>
                        </div>

                        <!-- Hotel Type -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">ประเภท</label>
                            <select name="type" class="w-full rounded-lg border-gray-300">
                                <option value="">ทั้งหมด</option>
                                <option value="hotel" {{ request('type') == 'hotel' ? 'selected' : '' }}>โรงแรม</option>
                                <option value="resort" {{ request('type') == 'resort' ? 'selected' : '' }}>รีสอร์ท</option>
                                <option value="hostel" {{ request('type') == 'hostel' ? 'selected' : '' }}>โฮสเทล</option>
                                <option value="villa" {{ request('type') == 'villa' ? 'selected' : '' }}>วิลล่า</option>
                            </select>
                        </div>

                        <!-- Star Rating -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">ดาว</label>
                            <select name="star_rating" class="w-full rounded-lg border-gray-300">
                                <option value="">ทั้งหมด</option>
                                @for($i = 5; $i >= 1; $i--)
                                    <option value="{{ $i }}" {{ request('star_rating') == $i ? 'selected' : '' }}>
                                        {{ $i }} ดาวขึ้นไป
                                    </option>
                                @endfor
                            </select>
                        </div>
                    </div>
                </div>

                <button type="submit" class="mt-6 w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg transition duration-200">
                    🔍 ค้นหาโรงแรม
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Results -->
<div class="container mx-auto px-4 py-12">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">
            @if($hotels->total() > 0)
                พบ {{ number_format($hotels->total()) }} โรงแรม
            @else
                โรงแรมแนะนำ
            @endif
        </h2>

        <!-- Sort -->
        <form method="GET" action="{{ route('hotels.index') }}" class="flex items-center gap-2">
            @foreach(request()->except('sort_by') as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach

            <label class="text-sm text-gray-600">เรียงตาม:</label>
            <select name="sort_by" onchange="this.form.submit()"
                    class="rounded-lg border-gray-300 text-sm">
                <option value="popular" {{ request('sort_by') == 'popular' ? 'selected' : '' }}>ยอดนิยม</option>
                <option value="price_low" {{ request('sort_by') == 'price_low' ? 'selected' : '' }}>ราคา: ต่ำ-สูง</option>
                <option value="price_high" {{ request('sort_by') == 'price_high' ? 'selected' : '' }}>ราคา: สูง-ต่ำ</option>
                <option value="rating" {{ request('sort_by') == 'rating' ? 'selected' : '' }}>คะแนนรีวิว</option>
                <option value="name" {{ request('sort_by') == 'name' ? 'selected' : '' }}>ชื่อ A-Z</option>
            </select>
        </form>
    </div>

    @if($hotels->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($hotels as $hotel)
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition duration-300">
                    <a href="{{ route('hotels.show', $hotel->slug) }}">
                        <div class="relative">
                            <img src="{{ $hotel->main_image_url }}"
                                 alt="{{ $hotel->name }}"
                                 class="w-full h-48 object-cover">

                            @if($hotel->is_featured)
                                <span class="absolute top-2 left-2 bg-yellow-500 text-white text-xs font-bold px-3 py-1 rounded-full">
                                    ⭐ แนะนำ
                                </span>
                            @endif

                            @if($hotel->star_rating)
                                <div class="absolute top-2 right-2 bg-white px-2 py-1 rounded-full text-xs font-bold">
                                    @for($i = 0; $i < $hotel->star_rating; $i++)
                                        ⭐
                                    @endfor
                                </div>
                            @endif
                        </div>

                        <div class="p-4">
                            <h3 class="text-lg font-bold text-gray-800 mb-2">{{ $hotel->name }}</h3>

                            <div class="flex items-center text-sm text-gray-600 mb-2">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                                </svg>
                                {{ $hotel->city }}, {{ $hotel->country }}
                            </div>

                            @if($hotel->rating > 0)
                                <div class="flex items-center mb-3">
                                    <span class="bg-blue-600 text-white text-sm font-bold px-2 py-1 rounded">
                                        {{ number_format($hotel->rating, 1) }}
                                    </span>
                                    <span class="ml-2 text-sm text-gray-600">
                                        ({{ number_format($hotel->review_count) }} รีวิว)
                                    </span>
                                </div>
                            @endif

                            <div class="border-t pt-3 flex justify-between items-center">
                                <div>
                                    <p class="text-xs text-gray-500">เริ่มต้น</p>
                                    <p class="text-2xl font-bold text-blue-600">
                                        ฿{{ number_format($hotel->lowest_price) }}
                                    </p>
                                    <p class="text-xs text-gray-500">ต่อคืน</p>
                                </div>
                                <button class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg">
                                    ดูรายละเอียด
                                </button>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-8">
            {{ $hotels->links() }}
        </div>
    @else
        <div class="text-center py-12">
            <svg class="mx-auto h-24 w-24 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            <h3 class="mt-4 text-lg font-medium text-gray-900">ไม่พบโรงแรม</h3>
            <p class="mt-2 text-gray-500">ลองเปลี่ยนเงื่อนไขการค้นหาดูครับ</p>
        </div>
    @endif
</div>

<!-- Popular Destinations -->
@if(isset($popularDestinations) && $popularDestinations->count() > 0)
    <div class="bg-gray-100 py-12">
        <div class="container mx-auto px-4">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">จุดหมายยอดนิยม</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                @foreach($popularDestinations as $destination)
                    <a href="{{ route('hotels.by-city', $destination->city) }}"
                       class="bg-white rounded-lg shadow-md p-4 hover:shadow-lg transition text-center">
                        <h3 class="font-bold text-gray-800">{{ $destination->city }}</h3>
                        <p class="text-sm text-gray-500">{{ $destination->hotel_count }} โรงแรม</p>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
@endif
@endsection
