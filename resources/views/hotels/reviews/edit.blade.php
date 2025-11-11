@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">แก้ไขรีวิว</h1>
            <a href="{{ route('hotels.reviews.show', $review->id) }}" class="text-blue-600 hover:text-blue-800">
                ← กลับไปหน้ารีวิว
            </a>
        </div>

        <!-- Hotel Info -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex gap-4">
                <img src="{{ $review->hotel->main_image_url }}" alt="{{ $review->hotel->name }}"
                     class="w-32 h-32 object-cover rounded-lg">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">{{ $review->hotel->name }}</h2>
                    <p class="text-gray-600">{{ $review->hotel->city }}</p>
                    @if($review->booking)
                        <p class="text-sm text-gray-500 mt-2">
                            เข้าพัก: {{ $review->booking->check_in_date->format('d M Y') }} - {{ $review->booking->check_out_date->format('d M Y') }}
                        </p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Edit Form -->
        <form action="{{ route('hotels.reviews.update', $review->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h3 class="text-xl font-bold text-gray-900 mb-6">แก้ไขคะแนน</h3>

                <!-- Overall Rating -->
                <div class="mb-6">
                    <label class="block text-lg font-medium text-gray-700 mb-3">
                        คะแนนโดยรวม <span class="text-red-500">*</span>
                    </label>
                    <div class="flex items-center gap-2" x-data="{ rating: {{ $review->overall_rating }}, hover: 0 }">
                        @for($i = 1; $i <= 5; $i++)
                            <label class="cursor-pointer">
                                <input type="radio" name="overall_rating" value="{{ $i }}" class="hidden" required
                                       {{ $review->overall_rating == $i ? 'checked' : '' }}
                                       x-model="rating">
                                <svg @mouseenter="hover = {{ $i }}" @mouseleave="hover = 0"
                                     :class="(rating >= {{ $i }} || hover >= {{ $i }}) ? 'text-yellow-400' : 'text-gray-300'"
                                     class="w-12 h-12 fill-current transition-colors"
                                     viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            </label>
                        @endfor
                        <span x-text="rating > 0 ? rating + '/5' : 'เลือกคะแนน'" class="ml-2 text-gray-600"></span>
                    </div>
                    @error('overall_rating')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Category Ratings -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @php
                        $categories = [
                            'cleanliness_rating' => ['label' => 'ความสะอาด', 'icon' => '🧹'],
                            'staff_rating' => ['label' => 'พนักงาน', 'icon' => '👥'],
                            'facilities_rating' => ['label' => 'สิ่งอำนวยความสะดวก', 'icon' => '🏊'],
                            'location_rating' => ['label' => 'ทำเลที่ตั้ง', 'icon' => '📍'],
                            'value_rating' => ['label' => 'คุ้มค่า', 'icon' => '💰'],
                        ];
                    @endphp

                    @foreach($categories as $field => $data)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                {{ $data['icon'] }} {{ $data['label'] }}
                            </label>
                            <div class="flex gap-1">
                                @for($i = 1; $i <= 5; $i++)
                                    <label class="cursor-pointer">
                                        <input type="radio" name="{{ $field }}" value="{{ $i }}" class="hidden"
                                               {{ $review->$field == $i ? 'checked' : '' }}>
                                        <svg class="w-6 h-6 {{ $review->$field >= $i ? 'text-yellow-400' : 'text-gray-300' }} hover:text-yellow-400 fill-current transition-colors rating-star"
                                             viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    </label>
                                @endfor
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Review Content -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h3 class="text-xl font-bold text-gray-900 mb-6">แก้ไขเนื้อหารีวิว</h3>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        หัวข้อรีวิว
                    </label>
                    <input type="text" name="title" value="{{ old('title', $review->title) }}"
                           class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                           placeholder="สรุปประสบการณ์ของคุณในหนึ่งประโยค">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        รีวิวของคุณ <span class="text-red-500">*</span>
                    </label>
                    <textarea name="comment" rows="6" required
                              class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                              placeholder="แชร์ประสบการณ์การเข้าพักของคุณ...">{{ old('comment', $review->comment) }}</textarea>
                    @error('comment')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            👍 สิ่งที่ชอบ
                        </label>
                        <textarea name="pros" rows="3"
                                  class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                  placeholder="อะไรที่คุณชอบมากที่สุด?">{{ old('pros', $review->pros) }}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            👎 สิ่งที่ควรปรับปรุง
                        </label>
                        <textarea name="cons" rows="3"
                                  class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                  placeholder="อะไรที่ควรปรับปรุง?">{{ old('cons', $review->cons) }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Existing Images -->
            @if($review->images && count($review->images) > 0)
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">รูปภาพที่มีอยู่</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        @foreach($review->images as $image)
                            <img src="{{ Storage::url($image) }}" alt="Review image"
                                 class="w-full h-32 object-cover rounded-lg">
                        @endforeach
                    </div>
                    <p class="text-sm text-gray-500 mt-3">
                        หมายเหตุ: การแก้ไขรูปภาพยังไม่รองรับในขณะนี้ หากต้องการเปลี่ยนรูปภาพ กรุณาติดต่อเจ้าหน้าที่
                    </p>
                </div>
            @endif

            <!-- Warning -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <p class="text-sm text-gray-700">
                    <strong>หมายเหตุ:</strong> การแก้ไขรีวิวจะถูกบันทึกเป็นประวัติ และอาจต้องรอการอนุมัติจากทีมงานอีกครั้ง
                </p>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-3">
                <a href="{{ route('hotels.reviews.show', $review->id) }}"
                   class="flex-1 text-center bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-3 px-6 rounded-lg transition">
                    ยกเลิก
                </a>
                <button type="submit"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg transition">
                    บันทึกการแก้ไข
                </button>
            </div>
        </form>

        <!-- Delete Review -->
        <div class="mt-6 text-center">
            <form action="{{ route('hotels.reviews.destroy', $review->id) }}" method="POST"
                  onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบรีวิวนี้? การกระทำนี้ไม่สามารถย้อนกลับได้')">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-red-600 hover:text-red-800 text-sm">
                    ลบรีวิวนี้
                </button>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Rating stars functionality
document.querySelectorAll('.rating-star').forEach(star => {
    star.closest('label').addEventListener('click', function() {
        const parent = this.closest('div');
        const stars = parent.querySelectorAll('.rating-star');
        const value = Array.from(stars).indexOf(star) + 1;

        stars.forEach((s, index) => {
            if (index < value) {
                s.classList.remove('text-gray-300');
                s.classList.add('text-yellow-400');
            } else {
                s.classList.remove('text-yellow-400');
                s.classList.add('text-gray-300');
            }
        });
    });
});
</script>
@endpush
@endsection
