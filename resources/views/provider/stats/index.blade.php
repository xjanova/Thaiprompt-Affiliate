{{--
    หน้าสถิติและรีวิวของผู้ให้บริการ
    แสดงคะแนน, รีวิว และประสิทธิภาพการทำงาน
--}}
@extends('layouts.app')

@section('title', $pageTitle ?? 'สถิติและรีวิว')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    {{-- Header --}}
    <div class="mb-6 p-6 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 shadow-xl">
        <h1 class="text-3xl font-bold bg-gradient-to-r from-yellow-600 to-orange-600 dark:from-yellow-400 dark:to-orange-400 bg-clip-text text-transparent">
            <i class="fas fa-chart-pie mr-3"></i>{{ $pageTitle ?? 'สถิติและรีวิว' }}
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">ดูประสิทธิภาพและคะแนนรีวิวของคุณ</p>
    </div>

    {{-- Stats Overview --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="p-6 rounded-xl backdrop-blur-xl bg-gradient-to-br from-blue-500/90 to-blue-600/90 text-white shadow-xl">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-80">งานทั้งหมด</p>
                    <p class="text-3xl font-bold">{{ number_format($stats['total_bookings'] ?? 0) }}</p>
                </div>
                <i class="fas fa-briefcase text-3xl opacity-50"></i>
            </div>
        </div>
        <div class="p-6 rounded-xl backdrop-blur-xl bg-gradient-to-br from-green-500/90 to-green-600/90 text-white shadow-xl">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-80">เสร็จสมบูรณ์</p>
                    <p class="text-3xl font-bold">{{ number_format($stats['completed_bookings'] ?? 0) }}</p>
                </div>
                <i class="fas fa-check-circle text-3xl opacity-50"></i>
            </div>
        </div>
        <div class="p-6 rounded-xl backdrop-blur-xl bg-gradient-to-br from-yellow-500/90 to-orange-500/90 text-white shadow-xl">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-80">คะแนนเฉลี่ย</p>
                    <p class="text-3xl font-bold">{{ number_format($stats['average_rating'] ?? 0, 1) }}</p>
                </div>
                <i class="fas fa-star text-3xl opacity-50"></i>
            </div>
        </div>
        <div class="p-6 rounded-xl backdrop-blur-xl bg-gradient-to-br from-emerald-500/90 to-teal-600/90 text-white shadow-xl">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-80">รายได้รวม</p>
                    <p class="text-3xl font-bold">฿{{ number_format($stats['total_earnings'] ?? 0, 0) }}</p>
                </div>
                <i class="fas fa-baht-sign text-3xl opacity-50"></i>
            </div>
        </div>
    </div>

    {{-- Performance Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        {{-- Performance Metrics --}}
        <div class="p-6 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 shadow-xl">
            <h2 class="text-xl font-bold mb-4 text-gray-900 dark:text-white">
                <i class="fas fa-gauge-high mr-2 text-blue-500"></i>ประสิทธิภาพ
            </h2>
            <div class="space-y-4">
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-600 dark:text-gray-400">อัตราการรับงาน</span>
                        <span class="font-semibold text-green-600">{{ $stats['acceptance_rate'] ?? 0 }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                        <div class="bg-gradient-to-r from-green-500 to-emerald-500 h-3 rounded-full" style="width: {{ min($stats['acceptance_rate'] ?? 0, 100) }}%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-600 dark:text-gray-400">อัตราสำเร็จ</span>
                        <span class="font-semibold text-blue-600">{{ $stats['completion_rate'] ?? 0 }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                        <div class="bg-gradient-to-r from-blue-500 to-cyan-500 h-3 rounded-full" style="width: {{ min($stats['completion_rate'] ?? 0, 100) }}%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-600 dark:text-gray-400">งานที่ยกเลิก</span>
                        <span class="font-semibold text-red-600">{{ $stats['cancelled_bookings'] ?? 0 }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Rating Distribution --}}
        <div class="p-6 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 shadow-xl">
            <h2 class="text-xl font-bold mb-4 text-gray-900 dark:text-white">
                <i class="fas fa-star mr-2 text-yellow-500"></i>การกระจายคะแนน
            </h2>
            @if(isset($ratingDistribution) && count($ratingDistribution) > 0)
                <div class="space-y-2">
                    @foreach([5, 4, 3, 2, 1] as $star)
                        @php
                            $count = $ratingDistribution[$star] ?? 0;
                            $total = array_sum($ratingDistribution);
                            $percentage = $total > 0 ? ($count / $total) * 100 : 0;
                        @endphp
                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-1 w-16">
                                <span class="text-sm font-medium">{{ $star }}</span>
                                <i class="fas fa-star text-yellow-500 text-sm"></i>
                            </div>
                            <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-4">
                                <div class="bg-gradient-to-r from-yellow-400 to-yellow-500 h-4 rounded-full" style="width: {{ $percentage }}%"></div>
                            </div>
                            <span class="text-sm text-gray-600 dark:text-gray-400 w-12 text-right">{{ $count }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-center text-gray-500 dark:text-gray-400 py-8">ยังไม่มีรีวิว</p>
            @endif
        </div>
    </div>

    {{-- Recent Reviews --}}
    <div class="p-6 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 shadow-xl">
        <h2 class="text-xl font-bold mb-4 text-gray-900 dark:text-white">
            <i class="fas fa-comments mr-2 text-purple-500"></i>รีวิวล่าสุด ({{ $stats['total_reviews'] ?? 0 }} รีวิว)
        </h2>
        @if(isset($reviews) && $reviews->count() > 0)
            <div class="space-y-4">
                @foreach($reviews as $review)
                    <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-700/50 border border-gray-100 dark:border-gray-600">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $review->user->name ?? 'ลูกค้า' }}</p>
                                <p class="text-sm text-gray-500">{{ $review->service->name ?? '-' }}</p>
                            </div>
                            <div class="text-right">
                                <div class="flex items-center gap-1">
                                    @for($i = 1; $i <= 5; $i++)
                                        <i class="fas fa-star {{ $i <= ($review->rating ?? 0) ? 'text-yellow-500' : 'text-gray-300 dark:text-gray-600' }}"></i>
                                    @endfor
                                </div>
                                <p class="text-xs text-gray-500 mt-1">{{ $review->rated_at ? $review->rated_at->diffForHumans() : '-' }}</p>
                            </div>
                        </div>
                        @if($review->review)
                            <p class="text-gray-600 dark:text-gray-400 text-sm mt-2">{{ $review->review }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
            <div class="mt-4">
                {{ $reviews->links() }}
            </div>
        @else
            <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                <i class="fas fa-comment-slash text-4xl mb-3"></i>
                <p>ยังไม่มีรีวิว</p>
            </div>
        @endif
    </div>
</div>
@endsection
