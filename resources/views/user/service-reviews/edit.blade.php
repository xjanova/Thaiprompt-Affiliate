{{--
    หน้าแก้ไขรีวิวบริการ
    ผู้ใช้สามารถแก้ไขคะแนนและความคิดเห็นได้
--}}
@extends('layouts.user-v4')

@section('title', $pageTitle ?? 'แก้ไขรีวิว')

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    {{-- Header --}}
    <div class="mb-6 p-6 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 shadow-xl">
        <h1 class="text-3xl font-bold bg-gradient-to-r from-yellow-600 to-orange-600 dark:from-yellow-400 dark:to-orange-400 bg-clip-text text-transparent">
            <i class="fas fa-edit mr-3"></i>{{ $pageTitle ?? 'แก้ไขรีวิว' }}
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">แก้ไขคะแนนและความคิดเห็นสำหรับบริการนี้</p>
    </div>

    {{-- Booking Info --}}
    <div class="mb-6 p-6 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 shadow-xl">
        <div class="flex items-start gap-4">
            <div class="w-14 h-14 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white font-bold text-xl">
                {{ substr($booking->provider->name ?? 'P', 0, 1) }}
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $booking->service->name ?? 'บริการ' }}</h3>
                <p class="text-gray-600 dark:text-gray-400">
                    <i class="fas fa-user mr-1"></i>{{ $booking->provider->name ?? '-' }}
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">
                    <i class="fas fa-hashtag mr-1"></i>{{ $booking->booking_number }}
                    <span class="mx-2">•</span>
                    <i class="fas fa-calendar mr-1"></i>{{ $booking->completed_at ? $booking->completed_at->format('d/m/Y') : '-' }}
                </p>
            </div>
        </div>
    </div>

    {{-- Review Form --}}
    <form action="{{ route('user.service-reviews.update', $booking) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="p-6 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 shadow-xl">
            {{-- Rating --}}
            <div class="mb-6" x-data="{ rating: {{ old('rating', $booking->rating ?? 5) }} }">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">ให้คะแนน <span class="text-red-500">*</span></label>
                <div class="flex items-center gap-2">
                    @for($i = 1; $i <= 5; $i++)
                        <button type="button" @click="rating = {{ $i }}" class="transition-transform hover:scale-110 focus:outline-none">
                            <i class="fas fa-star text-3xl transition-colors" :class="rating >= {{ $i }} ? 'text-yellow-500' : 'text-gray-300 dark:text-gray-600'"></i>
                        </button>
                    @endfor
                    <span class="ml-4 text-2xl font-bold text-gray-900 dark:text-white" x-text="rating + '/5'"></span>
                </div>
                <input type="hidden" name="rating" :value="rating">
                @error('rating')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Review Text --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ความคิดเห็น</label>
                <textarea name="review" rows="5" class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-yellow-500 focus:border-transparent @error('review') border-red-500 @enderror" placeholder="เขียนรีวิวเกี่ยวกับบริการที่คุณได้รับ...">{{ old('review', $booking->review) }}</textarea>
                @error('review')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                <p class="text-xs text-gray-500 mt-1">สูงสุด 1000 ตัวอักษร</p>
            </div>
        </div>

        {{-- Submit --}}
        <div class="flex gap-4">
            <button type="submit" class="flex-1 px-6 py-3 bg-gradient-to-r from-yellow-500 to-orange-600 text-white rounded-xl hover:shadow-lg transition font-medium">
                <i class="fas fa-save mr-2"></i>บันทึกการแก้ไข
            </button>
            <a href="{{ route('user.service-reviews.index') }}" class="px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition text-center">
                ยกเลิก
            </a>
        </div>
    </form>
</div>
@endsection
