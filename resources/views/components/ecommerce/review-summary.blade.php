{{--
    Review Summary Component - แสดงสรุปรีวิวสินค้า

    ใช้งาน: <x-ecommerce.review-summary :product="$product" />

    แสดง:
    - คะแนนเฉลี่ย
    - จำนวนรีวิวทั้งหมด
    - แถบกราฟแบ่งตามดาว (5-1)
    - เปอร์เซ็นต์สินค้าที่ซื้อจริง
--}}

@props(['product', 'reviews' => null])

@php
    $allReviews = $reviews ?? ($product->approvedReviews ?? collect());
    $totalReviews = $allReviews->count();
    $averageRating = $totalReviews > 0 ? round($allReviews->avg('rating'), 1) : 0;
    $fullStars = floor($averageRating);
    $hasHalfStar = ($averageRating - $fullStars) >= 0.5;

    // นับจำนวนรีวิวแต่ละดาว
    $ratingCounts = [
        5 => $allReviews->where('rating', 5)->count(),
        4 => $allReviews->where('rating', 4)->count(),
        3 => $allReviews->where('rating', 3)->count(),
        2 => $allReviews->where('rating', 2)->count(),
        1 => $allReviews->where('rating', 1)->count(),
    ];

    // จำนวนรีวิวจากผู้ซื้อจริง
    $verifiedCount = $allReviews->where('is_verified_purchase', true)->count();
    $verifiedPercent = $totalReviews > 0 ? round(($verifiedCount / $totalReviews) * 100) : 0;
@endphp

@if($totalReviews > 0)
<div {{ $attributes->merge(['class' => 'bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden']) }}>
    <div class="p-6">
        <div class="flex flex-col md:flex-row gap-8">

            {{-- คะแนนเฉลี่ย --}}
            <div class="flex flex-col items-center justify-center md:min-w-[160px]">
                <div class="text-5xl font-black text-gray-900 dark:text-gray-100 mb-2">
                    {{ number_format($averageRating, 1) }}
                </div>
                <div class="flex items-center gap-0.5 mb-2">
                    @for($i = 1; $i <= 5; $i++)
                        @if($i <= $fullStars)
                            <svg class="w-5 h-5 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        @elseif($i == $fullStars + 1 && $hasHalfStar)
                            <svg class="w-5 h-5 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                <defs>
                                    <linearGradient id="halfStarGrad">
                                        <stop offset="50%" stop-color="currentColor"/>
                                        <stop offset="50%" stop-color="#D1D5DB"/>
                                    </linearGradient>
                                </defs>
                                <path fill="url(#halfStarGrad)" d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        @else
                            <svg class="w-5 h-5 text-gray-300 dark:text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        @endif
                    @endfor
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    {{ number_format($totalReviews) }} รีวิว
                </div>
                @if($verifiedCount > 0)
                <div class="mt-2 inline-flex items-center gap-1 px-2.5 py-1 bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-400 text-xs font-semibold rounded-full">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    {{ $verifiedPercent }}% ซื้อจริง
                </div>
                @endif
            </div>

            {{-- แถบกราฟแบ่งตามดาว --}}
            <div class="flex-1 space-y-2">
                @for($star = 5; $star >= 1; $star--)
                    @php
                        $count = $ratingCounts[$star];
                        $percent = $totalReviews > 0 ? round(($count / $totalReviews) * 100) : 0;
                    @endphp
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-1 w-12 justify-end">
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ $star }}</span>
                            <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        </div>
                        <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-500
                                @if($star >= 4) bg-gradient-to-r from-amber-400 to-yellow-400
                                @elseif($star == 3) bg-gradient-to-r from-yellow-400 to-orange-400
                                @else bg-gradient-to-r from-orange-400 to-red-400
                                @endif"
                                 style="width: {{ $percent }}%">
                            </div>
                        </div>
                        <div class="w-16 text-right">
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                {{ $count }} <span class="text-xs">({{ $percent }}%)</span>
                            </span>
                        </div>
                    </div>
                @endfor
            </div>
        </div>
    </div>
</div>
@else
<div {{ $attributes->merge(['class' => 'bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden']) }}>
    <div class="p-8 text-center">
        <svg class="mx-auto w-16 h-16 text-gray-300 dark:text-gray-600 mb-4" fill="currentColor" viewBox="0 0 20 20">
            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
        </svg>
        <p class="text-lg font-semibold text-gray-600 dark:text-gray-400 mb-1">ยังไม่มีรีวิว</p>
        <p class="text-sm text-gray-500 dark:text-gray-500">สินค้านี้ยังไม่มีรีวิวจากผู้ซื้อ</p>
    </div>
</div>
@endif
