{{--
    หน้ารีวิวบริการของผู้ใช้
    แสดงรายการรีวิวทั้งหมดที่ผู้ใช้ได้ทำไว้
--}}
@extends('layouts.user-v4')

@section('title', $pageTitle ?? 'รีวิวบริการของฉัน')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    {{-- Header --}}
    <div class="mb-6 p-6 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 shadow-xl">
        <h1 class="text-3xl font-bold bg-gradient-to-r from-yellow-600 to-orange-600 dark:from-yellow-400 dark:to-orange-400 bg-clip-text text-transparent">
            <i class="fas fa-star mr-3"></i>{{ $pageTitle ?? 'รีวิวบริการของฉัน' }}
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">รีวิวบริการที่คุณได้ใช้และให้คะแนนไว้</p>
    </div>

    {{-- Reviews List --}}
    @if(isset($reviews) && $reviews->count() > 0)
        <div class="space-y-4">
            @foreach($reviews as $review)
                <div class="p-6 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 shadow-xl">
                    <div class="flex flex-col md:flex-row justify-between gap-4">
                        {{-- Service & Provider Info --}}
                        <div class="flex-1">
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white font-bold text-lg">
                                    {{ substr($review->provider->name ?? 'P', 0, 1) }}
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $review->service->name ?? 'บริการ' }}</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        <i class="fas fa-user mr-1"></i>{{ $review->provider->name ?? '-' }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                        <i class="fas fa-calendar mr-1"></i>เลขที่จอง: {{ $review->booking_number }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        {{-- Rating --}}
                        <div class="text-right">
                            <div class="flex items-center justify-end gap-1 mb-1">
                                @for($i = 1; $i <= 5; $i++)
                                    <i class="fas fa-star {{ $i <= ($review->rating ?? 0) ? 'text-yellow-500' : 'text-gray-300 dark:text-gray-600' }} text-lg"></i>
                                @endfor
                            </div>
                            <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $review->rating ?? 0 }}/5</p>
                            <p class="text-xs text-gray-500">{{ $review->rated_at ? $review->rated_at->format('d/m/Y H:i') : '-' }}</p>
                        </div>
                    </div>

                    {{-- Review Text --}}
                    @if($review->review)
                        <div class="mt-4 p-4 rounded-xl bg-gray-50 dark:bg-gray-700/50">
                            <p class="text-gray-700 dark:text-gray-300">{{ $review->review }}</p>
                        </div>
                    @endif

                    {{-- Actions --}}
                    <div class="mt-4 flex justify-end gap-2">
                        <a href="{{ route('user.service-reviews.edit', $review) }}" class="px-4 py-2 text-sm bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300 rounded-lg hover:bg-orange-200 dark:hover:bg-orange-900/50 transition">
                            <i class="fas fa-edit mr-1"></i>แก้ไขรีวิว
                        </a>
                        <form action="{{ route('user.service-reviews.destroy', $review) }}" method="POST" class="inline" onsubmit="return confirm('ลบรีวิวนี้?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-4 py-2 text-sm bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 transition">
                                <i class="fas fa-trash mr-1"></i>ลบ
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $reviews->links() }}
        </div>
    @else
        <div class="p-12 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 shadow-xl text-center">
            <i class="fas fa-star text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">ยังไม่มีรีวิว</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">คุณยังไม่ได้ให้คะแนนและรีวิวบริการใดๆ</p>
            <a href="{{ route('user.services.index') }}" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-purple-500 to-pink-600 text-white rounded-xl hover:shadow-lg transition">
                <i class="fas fa-concierge-bell mr-2"></i>ดูบริการ
            </a>
        </div>
    @endif
</div>
@endsection
