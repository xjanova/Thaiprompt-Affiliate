@extends('layouts.app')

@section('content')
<div class="bg-gradient-to-br from-blue-500 to-blue-700 py-16">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto text-center">
            <h1 class="text-4xl font-bold text-white mb-4">โรงแรมแนะนำพิเศษ</h1>
            <p class="text-xl text-blue-100">คัดสรรโรงแรมคุณภาพดีเยี่ยม พร้อมโปรโมชั่นพิเศษ</p>
        </div>
    </div>
</div>

<div class="container mx-auto px-4 py-12">
    <!-- Featured Hotels Count -->
    <div class="mb-8">
        <div class="flex justify-between items-center">
            <h2 class="text-2xl font-bold text-gray-800">
                พบ {{ $hotels->total() }} โรงแรมแนะนำ
            </h2>
            <a href="{{ route('hotels.index') }}" class="text-blue-600 hover:text-blue-800">
                ดูโรงแรมทั้งหมด →
            </a>
        </div>
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

                            <!-- Featured Badge -->
                            <span class="absolute top-2 left-2 bg-yellow-500 text-white text-xs font-bold px-3 py-1 rounded-full">
                                ⭐ แนะนำพิเศษ
                            </span>

                            <!-- Star Rating -->
                            @if($hotel->star_rating)
                                <div class="absolute top-2 right-2 bg-white px-2 py-1 rounded-full text-xs font-bold">
                                    @for($i = 0; $i < $hotel->star_rating; $i++)
                                        ⭐
                                    @endfor
                                </div>
                            @endif

                            <!-- Special Offer Badge -->
                            @if($hotel->has_special_offers)
                                <span class="absolute bottom-2 right-2 bg-red-500 text-white text-xs font-bold px-3 py-1 rounded-full">
                                    🔥 โปรโมชั่น
                                </span>
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

                            <!-- Rating -->
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

                            <!-- Hotel Description -->
                            @if($hotel->description)
                                <p class="text-sm text-gray-600 mb-3 line-clamp-2">
                                    {{ Str::limit($hotel->description, 100) }}
                                </p>
                            @endif

                            <!-- Facilities Preview -->
                            @if($hotel->facilities && $hotel->facilities->count() > 0)
                                <div class="flex flex-wrap gap-1 mb-3">
                                    @foreach($hotel->facilities->take(4) as $facility)
                                        <span class="text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded">
                                            {{ $facility->name }}
                                        </span>
                                    @endforeach
                                    @if($hotel->facilities->count() > 4)
                                        <span class="text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded">
                                            +{{ $hotel->facilities->count() - 4 }} อื่นๆ
                                        </span>
                                    @endif
                                </div>
                            @endif

                            <!-- Price and Button -->
                            <div class="border-t pt-3 flex justify-between items-center">
                                <div>
                                    <p class="text-xs text-gray-500">เริ่มต้น</p>
                                    <p class="text-2xl font-bold text-blue-600">
                                        ฿{{ number_format($hotel->lowest_price) }}
                                    </p>
                                    <p class="text-xs text-gray-500">ต่อคืน</p>
                                </div>
                                <button class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition">
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
            <h3 class="mt-4 text-lg font-medium text-gray-900">ไม่พบโรงแรมแนะนำ</h3>
            <p class="mt-2 text-gray-500">กรุณากลับมาดูใหม่ภายหลัง</p>
            <a href="{{ route('hotels.index') }}" class="mt-4 inline-block bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg">
                ดูโรงแรมทั้งหมด
            </a>
        </div>
    @endif
</div>

<!-- Why Featured Section -->
<div class="bg-gray-100 py-12">
    <div class="container mx-auto px-4">
        <h2 class="text-2xl font-bold text-gray-800 mb-8 text-center">ทำไมต้องเลือกโรงแรมแนะนำของเรา?</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                <div class="text-4xl mb-3">✅</div>
                <h3 class="text-lg font-bold mb-2">ผ่านการตรวจสอบ</h3>
                <p class="text-gray-600">คัดสรรโรงแรมคุณภาพดี ผ่านการตรวจสอบมาตรฐาน</p>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                <div class="text-4xl mb-3">💰</div>
                <h3 class="text-lg font-bold mb-2">ราคาคุ้มค่า</h3>
                <p class="text-gray-600">โปรโมชั่นพิเศษและราคาที่ดีที่สุด</p>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                <div class="text-4xl mb-3">⭐</div>
                <h3 class="text-lg font-bold mb-2">รีวิวดีเยี่ยม</h3>
                <p class="text-gray-600">ได้รับคะแนนรีวิวสูงจากผู้เข้าพักจริง</p>
            </div>
        </div>
    </div>
</div>
@endsection
