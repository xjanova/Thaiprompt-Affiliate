@extends('layouts.app')

@section('content')
<div class="bg-white">
    <!-- Hotel Header -->
    <div class="container mx-auto px-4 py-6">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">{{ $hotel->name }}</h1>
                <div class="flex items-center mt-2 text-gray-600">
                    @if($hotel->star_rating)
                        <div class="flex mr-3">
                            @for($i = 0; $i < $hotel->star_rating; $i++)
                                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            @endfor
                        </div>
                    @endif
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                    </svg>
                    <span>{{ $hotel->address }}, {{ $hotel->city }}</span>
                </div>
            </div>

            @if($hotel->rating > 0)
                <div class="text-right">
                    <div class="bg-blue-600 text-white text-2xl font-bold px-4 py-2 rounded-lg inline-block">
                        {{ number_format($hotel->rating, 1) }}
                    </div>
                    <p class="text-sm text-gray-600 mt-1">จาก {{ number_format($hotel->review_count) }} รีวิว</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Image Gallery -->
    <div class="container mx-auto px-4 mb-8">
        <div class="grid grid-cols-4 gap-2 rounded-lg overflow-hidden">
            <div class="col-span-2 row-span-2">
                <img src="{{ $hotel->main_image_url }}" alt="{{ $hotel->name }}"
                     class="w-full h-full object-cover">
            </div>
            @if($hotel->gallery_images_url)
                @foreach(array_slice($hotel->gallery_images_url, 0, 4) as $image)
                    <div>
                        <img src="{{ $image }}" alt="{{ $hotel->name }}"
                             class="w-full h-48 object-cover">
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    <div class="container mx-auto px-4 pb-12">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Description -->
                <div>
                    <h2 class="text-2xl font-bold mb-4">เกี่ยวกับที่พัก</h2>
                    <p class="text-gray-700 leading-relaxed">{{ $hotel->description }}</p>
                </div>

                <!-- Facilities -->
                @if($hotel->facilities && $hotel->facilities->count() > 0)
                    <div>
                        <h2 class="text-2xl font-bold mb-4">สิ่งอำนวยความสะดวก</h2>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            @foreach($hotel->facilities as $facility)
                                <div class="flex items-center text-gray-700">
                                    <svg class="w-5 h-5 mr-2 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ $facility->name }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Rooms -->
                <div id="rooms">
                    <h2 class="text-2xl font-bold mb-6">เลือกห้องพัก</h2>

                    @forelse($hotel->roomTypes as $roomType)
                        <div class="bg-white border rounded-lg p-6 mb-4 hover:shadow-lg transition">
                            <div class="flex flex-col md:flex-row gap-6">
                                <div class="md:w-1/3">
                                    <img src="{{ $roomType->main_image_url }}" alt="{{ $roomType->name }}"
                                         class="w-full h-48 object-cover rounded-lg">
                                </div>

                                <div class="md:w-2/3">
                                    <h3 class="text-xl font-bold text-gray-900 mb-2">{{ $roomType->name }}</h3>
                                    <p class="text-gray-600 mb-3">{{ $roomType->description }}</p>

                                    <!-- Room Details -->
                                    <div class="flex flex-wrap gap-4 mb-3 text-sm text-gray-600">
                                        @if($roomType->size_sqm)
                                            <div class="flex items-center">
                                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                                                </svg>
                                                {{ $roomType->size_sqm }} ตร.ม.
                                            </div>
                                        @endif
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                                            </svg>
                                            {{ $roomType->max_adults }} ผู้ใหญ่, {{ $roomType->max_children }} เด็ก
                                        </div>
                                        @if($roomType->bed_types)
                                            <div class="flex items-center">
                                                🛏️ {{ collect($roomType->bed_types)->pluck('type')->implode(', ') }}
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Amenities -->
                                    @if($roomType->amenities)
                                        <div class="flex flex-wrap gap-2 mb-4">
                                            @foreach($roomType->amenities as $amenity)
                                                <span class="text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded">
                                                    {{ $amenity }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif

                                    <!-- Price and Book -->
                                    <div class="flex items-end justify-between mt-4">
                                        <div>
                                            <p class="text-sm text-gray-500">เริ่มต้น</p>
                                            <p class="text-3xl font-bold text-blue-600">
                                                ฿{{ number_format($roomType->base_price) }}
                                            </p>
                                            <p class="text-xs text-gray-500">ต่อคืน (ไม่รวมภาษี)</p>
                                        </div>

                                        <a href="{{ route('hotels.bookings.create', [
                                                'room_type_id' => $roomType->id,
                                                'check_in' => request('check_in'),
                                                'check_out' => request('check_out'),
                                                'adults' => request('adults', 2),
                                                'children' => request('children', 0)
                                            ]) }}"
                                           class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg transition">
                                            จองเลย
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500">ไม่มีห้องพักว่างในขณะนี้</p>
                    @endforelse
                </div>

                <!-- Reviews -->
                <div id="reviews">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold">รีวิวจากผู้เข้าพัก</h2>
                        <a href="{{ route('hotels.reviews.index', $hotel->slug) }}"
                           class="text-blue-600 hover:text-blue-800">
                            ดูรีวิวทั้งหมด →
                        </a>
                    </div>

                    @forelse($hotel->reviews->take(5) as $review)
                        <div class="bg-gray-50 rounded-lg p-6 mb-4">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <h4 class="font-bold text-gray-900">{{ $review->guest_name }}</h4>
                                    <p class="text-sm text-gray-500">
                                        {{ $review->created_at->diffForHumans() }}
                                    </p>
                                </div>
                                <div class="bg-blue-600 text-white px-3 py-1 rounded-lg font-bold">
                                    {{ $review->overall_rating }}/5
                                </div>
                            </div>

                            @if($review->title)
                                <h5 class="font-bold text-gray-800 mb-2">{{ $review->title }}</h5>
                            @endif

                            <p class="text-gray-700">{{ Str::limit($review->comment, 200) }}</p>
                        </div>
                    @empty
                        <p class="text-gray-500">ยังไม่มีรีวิว เป็นคนแรกที่รีวิวที่พักนี้!</p>
                    @endforelse
                </div>

                <!-- Location -->
                <div>
                    <h2 class="text-2xl font-bold mb-4">ที่ตั้ง</h2>
                    <div class="bg-gray-100 rounded-lg p-4">
                        <p class="text-gray-700">
                            <strong>{{ $hotel->name }}</strong><br>
                            {{ $hotel->address }}<br>
                            {{ $hotel->city }}, {{ $hotel->state }} {{ $hotel->postal_code }}<br>
                            {{ $hotel->country }}
                        </p>
                        @if($hotel->phone)
                            <p class="mt-2">
                                <strong>โทร:</strong> {{ $hotel->phone }}
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <!-- Booking Widget (Sticky) -->
                <div class="bg-white border-2 border-blue-500 rounded-lg p-6 sticky top-4">
                    <h3 class="text-xl font-bold mb-4">จองที่พักนี้</h3>

                    <form action="{{ route('hotels.show', $hotel->slug) }}" method="GET" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">เช็คอิน</label>
                            <input type="date" name="check_in" value="{{ request('check_in', date('Y-m-d')) }}"
                                   min="{{ date('Y-m-d') }}"
                                   class="w-full rounded-lg border-gray-300" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">เช็คเอาท์</label>
                            <input type="date" name="check_out" value="{{ request('check_out', date('Y-m-d', strtotime('+1 day'))) }}"
                                   min="{{ date('Y-m-d', strtotime('+1 day')) }}"
                                   class="w-full rounded-lg border-gray-300" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ผู้ใหญ่</label>
                            <select name="adults" class="w-full rounded-lg border-gray-300">
                                @for($i = 1; $i <= 10; $i++)
                                    <option value="{{ $i }}" {{ request('adults', 2) == $i ? 'selected' : '' }}>
                                        {{ $i }} คน
                                    </option>
                                @endfor
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">เด็ก</label>
                            <select name="children" class="w-full rounded-lg border-gray-300">
                                @for($i = 0; $i <= 5; $i++)
                                    <option value="{{ $i }}" {{ request('children', 0) == $i ? 'selected' : '' }}>
                                        {{ $i }} คน
                                    </option>
                                @endfor
                            </select>
                        </div>

                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg">
                            ตรวจสอบห้องว่าง
                        </button>
                    </form>

                    <div class="mt-6 pt-6 border-t">
                        <div class="text-center mb-2">
                            <span class="text-sm text-gray-500">ราคาเริ่มต้น</span>
                            <p class="text-3xl font-bold text-blue-600">
                                ฿{{ number_format($hotel->lowest_price) }}
                            </p>
                            <span class="text-xs text-gray-500">ต่อคืน</span>
                        </div>
                    </div>

                    <!-- Hotel Info -->
                    <div class="mt-6 pt-6 border-t space-y-2 text-sm text-gray-600">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                            </svg>
                            <span>เช็คอิน: {{ \Carbon\Carbon::parse($hotel->check_in_time)->format('H:i') }} น.</span>
                        </div>
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                            </svg>
                            <span>เช็คเอาท์: {{ \Carbon\Carbon::parse($hotel->check_out_time)->format('H:i') }} น.</span>
                        </div>
                    </div>
                </div>

                <!-- Similar Hotels -->
                @if($relatedHotels && $relatedHotels->count() > 0)
                    <div class="mt-8">
                        <h3 class="text-lg font-bold mb-4">โรงแรมใกล้เคียง</h3>
                        @foreach($relatedHotels as $related)
                            <a href="{{ route('hotels.show', $related->slug) }}"
                               class="block bg-white rounded-lg shadow p-3 mb-3 hover:shadow-lg transition">
                                <div class="flex gap-3">
                                    <img src="{{ $related->main_image_url }}" alt="{{ $related->name }}"
                                         class="w-20 h-20 object-cover rounded">
                                    <div class="flex-1">
                                        <h4 class="font-bold text-sm">{{ $related->name }}</h4>
                                        <p class="text-xs text-gray-500">{{ $related->city }}</p>
                                        <p class="text-blue-600 font-bold mt-1">
                                            ฿{{ number_format($related->lowest_price) }}
                                        </p>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
