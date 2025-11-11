@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- Back Link -->
        <div class="mb-6">
            <a href="{{ route('hotels.reviews.index', $review->hotel->slug) }}" class="text-blue-600 hover:text-blue-800">
                ← กลับไปหน้ารีวิวทั้งหมด
            </a>
        </div>

        <!-- Review Card -->
        <div class="bg-white rounded-lg shadow-lg p-8">
            <!-- Hotel Info -->
            <div class="mb-6 pb-6 border-b">
                <a href="{{ route('hotels.show', $review->hotel->slug) }}" class="flex items-center gap-4 hover:opacity-75 transition">
                    <img src="{{ $review->hotel->main_image_url }}" alt="{{ $review->hotel->name }}"
                         class="w-24 h-24 object-cover rounded-lg">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">{{ $review->hotel->name }}</h2>
                        <p class="text-gray-600">{{ $review->hotel->city }}, {{ $review->hotel->country }}</p>
                    </div>
                </a>
            </div>

            <!-- Review Header -->
            <div class="flex justify-between items-start mb-6">
                <div class="flex items-start gap-4">
                    <div class="w-16 h-16 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold text-2xl">
                        {{ substr($review->guest_name, 0, 1) }}
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-gray-900">{{ $review->guest_name }}</h3>
                        <p class="text-gray-600">
                            เข้าพักเมื่อ {{ $review->stayed_date ? \Carbon\Carbon::parse($review->stayed_date)->format('F Y') : 'N/A' }}
                        </p>
                        <p class="text-sm text-gray-500 mt-1">
                            รีวิวเมื่อ {{ $review->created_at->diffForHumans() }}
                            @if($review->is_verified)
                                <span class="text-green-600 ml-2">✓ การจองที่ยืนยันแล้ว</span>
                            @endif
                        </p>
                        @if($review->guest_type)
                            <span class="inline-block mt-2 text-sm bg-gray-100 text-gray-700 px-3 py-1 rounded">
                                {{ $review->guest_type }}
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Overall Rating -->
                <div class="text-center">
                    <div class="bg-blue-600 text-white px-6 py-4 rounded-lg font-bold text-3xl">
                        {{ $review->overall_rating }}/5
                    </div>
                    <p class="text-sm text-gray-600 mt-2">คะแนนโดยรวม</p>
                </div>
            </div>

            <!-- Category Ratings -->
            <div class="mb-6">
                <h4 class="text-lg font-bold text-gray-900 mb-4">คะแนนแยกตามหมวดหมู่</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                    @if($review->cleanliness_rating)
                        <div class="bg-gray-50 rounded-lg p-4 text-center">
                            <div class="text-2xl mb-2">🧹</div>
                            <p class="text-sm text-gray-600">ความสะอาด</p>
                            <p class="text-xl font-bold text-blue-600">{{ $review->cleanliness_rating }}/5</p>
                        </div>
                    @endif
                    @if($review->staff_rating)
                        <div class="bg-gray-50 rounded-lg p-4 text-center">
                            <div class="text-2xl mb-2">👥</div>
                            <p class="text-sm text-gray-600">พนักงาน</p>
                            <p class="text-xl font-bold text-blue-600">{{ $review->staff_rating }}/5</p>
                        </div>
                    @endif
                    @if($review->facilities_rating)
                        <div class="bg-gray-50 rounded-lg p-4 text-center">
                            <div class="text-2xl mb-2">🏊</div>
                            <p class="text-sm text-gray-600">สิ่งอำนวยความสะดวก</p>
                            <p class="text-xl font-bold text-blue-600">{{ $review->facilities_rating }}/5</p>
                        </div>
                    @endif
                    @if($review->location_rating)
                        <div class="bg-gray-50 rounded-lg p-4 text-center">
                            <div class="text-2xl mb-2">📍</div>
                            <p class="text-sm text-gray-600">ทำเลที่ตั้ง</p>
                            <p class="text-xl font-bold text-blue-600">{{ $review->location_rating }}/5</p>
                        </div>
                    @endif
                    @if($review->value_rating)
                        <div class="bg-gray-50 rounded-lg p-4 text-center">
                            <div class="text-2xl mb-2">💰</div>
                            <p class="text-sm text-gray-600">คุ้มค่า</p>
                            <p class="text-xl font-bold text-blue-600">{{ $review->value_rating }}/5</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Review Title -->
            @if($review->title)
                <div class="mb-4">
                    <h4 class="text-2xl font-bold text-gray-900">{{ $review->title }}</h4>
                </div>
            @endif

            <!-- Review Comment -->
            <div class="mb-6">
                <p class="text-gray-700 text-lg leading-relaxed">{{ $review->comment }}</p>
            </div>

            <!-- Pros and Cons -->
            @if($review->pros || $review->cons)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    @if($review->pros)
                        <div class="bg-green-50 rounded-lg p-6">
                            <h5 class="font-bold text-green-800 text-lg mb-3">👍 สิ่งที่ชอบ</h5>
                            <p class="text-gray-700">{{ $review->pros }}</p>
                        </div>
                    @endif
                    @if($review->cons)
                        <div class="bg-red-50 rounded-lg p-6">
                            <h5 class="font-bold text-red-800 text-lg mb-3">👎 สิ่งที่ควรปรับปรุง</h5>
                            <p class="text-gray-700">{{ $review->cons }}</p>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Review Images -->
            @if($review->images && count($review->images) > 0)
                <div class="mb-6">
                    <h4 class="text-lg font-bold text-gray-900 mb-4">รูปภาพจากผู้เข้าพัก</h4>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                        @foreach($review->images as $image)
                            <img src="{{ Storage::url($image) }}" alt="Review image"
                                 class="w-full h-48 object-cover rounded-lg cursor-pointer hover:opacity-75 transition"
                                 onclick="openImageModal('{{ Storage::url($image) }}')">
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Review Actions -->
            <div class="flex items-center gap-6 py-6 border-t border-b">
                @auth
                    <form action="{{ route('hotels.reviews.helpful', $review->id) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="flex items-center gap-2 text-gray-700 hover:text-green-600 transition">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2 10.5a1.5 1.5 0 113 0v6a1.5 1.5 0 01-3 0v-6zM6 10.333v5.43a2 2 0 001.106 1.79l.05.025A4 4 0 008.943 18h5.416a2 2 0 001.962-1.608l1.2-6A2 2 0 0015.56 8H12V4a2 2 0 00-2-2 1 1 0 00-1 1v.667a4 4 0 01-.8 2.4L6.8 7.933a4 4 0 00-.8 2.4z"/>
                            </svg>
                            <span class="font-medium">มีประโยชน์ ({{ $review->helpful_count ?? 0 }})</span>
                        </button>
                    </form>

                    @if($review->user_id === auth()->id())
                        <a href="{{ route('hotels.reviews.edit', $review->id) }}" class="flex items-center gap-2 text-blue-600 hover:text-blue-800 transition">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                            </svg>
                            <span class="font-medium">แก้ไขรีวิว</span>
                        </a>
                    @endif
                @endauth

                <button onclick="shareReview()" class="flex items-center gap-2 text-gray-700 hover:text-blue-600 transition ml-auto">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M15 8a3 3 0 10-2.977-2.63l-4.94 2.47a3 3 0 100 4.319l4.94 2.47a3 3 0 10.895-1.789l-4.94-2.47a3.027 3.027 0 000-.74l4.94-2.47C13.456 7.68 14.19 8 15 8z"/>
                    </svg>
                    <span class="font-medium">แชร์</span>
                </button>
            </div>

            <!-- Hotel Response -->
            @if($review->response)
                <div class="mt-6 bg-blue-50 rounded-lg p-6 border-l-4 border-blue-600">
                    <div class="flex items-start gap-3 mb-3">
                        <div class="w-12 h-12 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold">
                            {{ substr($review->hotel->name, 0, 1) }}
                        </div>
                        <div>
                            <h5 class="text-lg font-bold text-gray-900">{{ $review->hotel->name }}</h5>
                            <p class="text-sm text-gray-600">ตอบกลับรีวิว</p>
                            @if($review->response_at)
                                <p class="text-xs text-gray-500">{{ $review->response_at->diffForHumans() }}</p>
                            @endif
                        </div>
                    </div>
                    <p class="text-gray-700 leading-relaxed">{{ $review->response }}</p>
                </div>
            @endif

            <!-- Booking Info -->
            @if($review->booking)
                <div class="mt-6 pt-6 border-t">
                    <h5 class="font-bold text-gray-900 mb-3">ข้อมูลการจอง</h5>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <p class="text-gray-600">ห้องพัก</p>
                            <p class="font-medium">{{ $review->booking->roomType->name }}</p>
                        </div>
                        <div>
                            <p class="text-gray-600">เช็คอิน</p>
                            <p class="font-medium">{{ $review->booking->check_in_date->format('d/m/Y') }}</p>
                        </div>
                        <div>
                            <p class="text-gray-600">เช็คเอาท์</p>
                            <p class="font-medium">{{ $review->booking->check_out_date->format('d/m/Y') }}</p>
                        </div>
                        <div>
                            <p class="text-gray-600">จำนวนคืน</p>
                            <p class="font-medium">{{ $review->booking->nights }} คืน</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Image Modal -->
<div id="imageModal" class="hidden fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center" onclick="closeImageModal()">
    <div class="max-w-6xl max-h-full p-4">
        <img id="modalImage" src="" alt="Review image" class="max-w-full max-h-screen rounded-lg">
    </div>
</div>

@push('scripts')
<script>
function openImageModal(imageUrl) {
    document.getElementById('modalImage').src = imageUrl;
    document.getElementById('imageModal').classList.remove('hidden');
}

function closeImageModal() {
    document.getElementById('imageModal').classList.add('hidden');
}

function shareReview() {
    if (navigator.share) {
        navigator.share({
            title: '{{ $review->title ?? "รีวิว " . $review->hotel->name }}',
            text: '{{ Str::limit($review->comment, 100) }}',
            url: window.location.href
        }).catch(err => console.log('Error sharing:', err));
    } else {
        // Fallback: Copy to clipboard
        navigator.clipboard.writeText(window.location.href);
        alert('คัดลอกลิงก์แล้ว!');
    }
}
</script>
@endpush
@endsection
