@extends('layouts.app')

@section('content')
<div class="bg-white border-b">
    <div class="container mx-auto px-4 py-6">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">รีวิว {{ $hotel->name }}</h1>
                <a href="{{ route('hotels.show', $hotel->slug) }}" class="text-blue-600 hover:text-blue-800">
                    ← กลับไปหน้าโรงแรม
                </a>
            </div>
            <div class="text-right">
                <div class="bg-blue-600 text-white text-3xl font-bold px-6 py-3 rounded-lg inline-block">
                    {{ $stats['average_rating'] }}
                </div>
                <p class="text-sm text-gray-600 mt-1">จาก {{ number_format($stats['total_reviews']) }} รีวิว</p>
            </div>
        </div>
    </div>
</div>

<div class="container mx-auto px-4 py-12">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Sidebar - Statistics -->
        <div class="lg:col-span-1">
            <!-- Overall Rating -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6 sticky top-4">
                <h3 class="text-xl font-bold text-gray-900 mb-4">คะแนนโดยรวม</h3>

                <!-- Category Ratings -->
                <div class="space-y-3 mb-6">
                    @foreach($stats['category_ratings'] as $category => $rating)
                        @php
                            $categoryNames = [
                                'cleanliness' => 'ความสะอาด',
                                'staff' => 'พนักงาน',
                                'facilities' => 'สิ่งอำนวยความสะดวก',
                                'location' => 'ทำเลที่ตั้ง',
                                'value' => 'คุ้มค่า'
                            ];
                        @endphp
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-700">{{ $categoryNames[$category] ?? $category }}</span>
                                <span class="font-bold text-gray-900">{{ number_format($rating, 1) }}</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ ($rating / 5) * 100 }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Rating Distribution -->
                <div class="border-t pt-4">
                    <h4 class="font-bold text-gray-900 mb-3">การกระจายคะแนน</h4>
                    @foreach($stats['rating_distribution'] as $star => $data)
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-sm font-medium text-gray-700 w-8">{{ $star }} ⭐</span>
                            <div class="flex-1 bg-gray-200 rounded-full h-2">
                                <div class="bg-yellow-400 h-2 rounded-full" style="width: {{ $data['percentage'] }}%"></div>
                            </div>
                            <span class="text-xs text-gray-600 w-12 text-right">{{ $data['count'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Main Content - Reviews List -->
        <div class="lg:col-span-2">
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-900">รีวิวทั้งหมด ({{ number_format($reviews->total()) }})</h2>
            </div>

            @forelse($reviews as $review)
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <!-- Review Header -->
                    <div class="flex justify-between items-start mb-4">
                        <div class="flex items-start gap-3">
                            <div class="w-12 h-12 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold text-lg">
                                {{ substr($review->guest_name, 0, 1) }}
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900">{{ $review->guest_name }}</h3>
                                <p class="text-sm text-gray-500">
                                    {{ $review->created_at->diffForHumans() }}
                                    @if($review->is_verified)
                                        <span class="text-green-600 ml-2">✓ การจองที่ยืนยันแล้ว</span>
                                    @endif
                                </p>
                                @if($review->guest_type)
                                    <span class="text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded mt-1 inline-block">
                                        {{ $review->guest_type }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Overall Rating -->
                        <div class="bg-blue-600 text-white px-4 py-2 rounded-lg font-bold text-xl">
                            {{ $review->overall_rating }}/5
                        </div>
                    </div>

                    <!-- Category Ratings -->
                    @if($review->cleanliness_rating || $review->staff_rating || $review->facilities_rating || $review->location_rating || $review->value_rating)
                        <div class="flex flex-wrap gap-3 mb-4 text-sm">
                            @if($review->cleanliness_rating)
                                <div class="flex items-center gap-1">
                                    <span class="text-gray-600">🧹 ความสะอาด:</span>
                                    <span class="font-bold">{{ $review->cleanliness_rating }}/5</span>
                                </div>
                            @endif
                            @if($review->staff_rating)
                                <div class="flex items-center gap-1">
                                    <span class="text-gray-600">👥 พนักงาน:</span>
                                    <span class="font-bold">{{ $review->staff_rating }}/5</span>
                                </div>
                            @endif
                            @if($review->facilities_rating)
                                <div class="flex items-center gap-1">
                                    <span class="text-gray-600">🏊 สิ่งอำนวยความสะดวก:</span>
                                    <span class="font-bold">{{ $review->facilities_rating }}/5</span>
                                </div>
                            @endif
                            @if($review->location_rating)
                                <div class="flex items-center gap-1">
                                    <span class="text-gray-600">📍 ทำเล:</span>
                                    <span class="font-bold">{{ $review->location_rating }}/5</span>
                                </div>
                            @endif
                            @if($review->value_rating)
                                <div class="flex items-center gap-1">
                                    <span class="text-gray-600">💰 คุ้มค่า:</span>
                                    <span class="font-bold">{{ $review->value_rating }}/5</span>
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- Review Title -->
                    @if($review->title)
                        <h4 class="font-bold text-gray-800 text-lg mb-2">{{ $review->title }}</h4>
                    @endif

                    <!-- Review Comment -->
                    <p class="text-gray-700 mb-4 leading-relaxed">{{ $review->comment }}</p>

                    <!-- Pros and Cons -->
                    @if($review->pros || $review->cons)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            @if($review->pros)
                                <div class="bg-green-50 rounded-lg p-4">
                                    <h5 class="font-bold text-green-800 mb-2">👍 สิ่งที่ชอบ</h5>
                                    <p class="text-sm text-gray-700">{{ $review->pros }}</p>
                                </div>
                            @endif
                            @if($review->cons)
                                <div class="bg-red-50 rounded-lg p-4">
                                    <h5 class="font-bold text-red-800 mb-2">👎 สิ่งที่ควรปรับปรุง</h5>
                                    <p class="text-sm text-gray-700">{{ $review->cons }}</p>
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- Review Images -->
                    @if($review->images && count($review->images) > 0)
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mb-4">
                            @foreach($review->images as $image)
                                <img src="{{ Storage::url($image) }}" alt="Review image"
                                     class="w-full h-32 object-cover rounded-lg cursor-pointer hover:opacity-75 transition"
                                     onclick="openImageModal('{{ Storage::url($image) }}')">
                            @endforeach
                        </div>
                    @endif

                    <!-- Review Actions -->
                    <div class="flex items-center gap-4 pt-4 border-t text-sm">
                        @auth
                            <form action="{{ route('hotels.reviews.helpful', $review->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-gray-600 hover:text-green-600 transition">
                                    👍 มีประโยชน์ ({{ $review->helpful_count ?? 0 }})
                                </button>
                            </form>
                            <form action="{{ route('hotels.reviews.not-helpful', $review->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-gray-600 hover:text-red-600 transition">
                                    👎 ไม่มีประโยชน์
                                </button>
                            </form>

                            @if($review->user_id === auth()->id())
                                <a href="{{ route('hotels.reviews.edit', $review->id) }}" class="text-blue-600 hover:text-blue-800">
                                    ✏️ แก้ไข
                                </a>
                            @endif
                        @endauth

                        <a href="{{ route('hotels.reviews.show', $review->id) }}" class="text-blue-600 hover:text-blue-800 ml-auto">
                            อ่านเพิ่มเติม →
                        </a>
                    </div>

                    <!-- Hotel Response -->
                    @if($review->response)
                        <div class="mt-4 bg-gray-50 rounded-lg p-4 border-l-4 border-blue-600">
                            <div class="flex items-start gap-2 mb-2">
                                <span class="text-blue-600 font-bold">{{ $hotel->name }}</span>
                                <span class="text-xs text-gray-500">ตอบกลับ</span>
                            </div>
                            <p class="text-sm text-gray-700">{{ $review->response }}</p>
                            @if($review->response_at)
                                <p class="text-xs text-gray-500 mt-2">{{ $review->response_at->diffForHumans() }}</p>
                            @endif
                        </div>
                    @endif
                </div>
            @empty
                <div class="text-center py-12">
                    <svg class="mx-auto h-24 w-24 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">ยังไม่มีรีวิว</h3>
                    <p class="mt-2 text-gray-500">เป็นคนแรกที่รีวิวที่พักนี้!</p>
                </div>
            @endforelse

            <!-- Pagination -->
            @if($reviews->hasPages())
                <div class="mt-8">
                    {{ $reviews->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Image Modal -->
<div id="imageModal" class="hidden fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center" onclick="closeImageModal()">
    <div class="max-w-4xl max-h-full p-4">
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
</script>
@endpush
@endsection
