{{--
    หน้าความก้าวหน้าการเรียนของฉัน
    ใช้ V3 Design System: Tailwind CSS + Alpine.js
--}}

@extends('layouts.user-v4')

@section('title', 'ความก้าวหน้าของฉัน - ศูนย์การเรียนรู้')

@section('content')
<div class="container-fluid px-4 py-6 max-w-7xl mx-auto">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-6">
        <a href="{{ route('user.academy.index') }}" class="hover:text-purple-600 dark:hover:text-purple-400 transition-colors">
            <i class="fas fa-graduation-cap mr-1"></i>ศูนย์การเรียนรู้
        </a>
        <i class="fas fa-chevron-right text-xs"></i>
        <span class="text-gray-900 dark:text-white font-medium">ความก้าวหน้าของฉัน</span>
    </nav>

    {{-- Header --}}
    <div class="relative overflow-hidden bg-gradient-to-br from-green-500 via-emerald-600 to-teal-500 rounded-3xl p-8 mb-8 shadow-2xl">
        <div class="absolute inset-0 opacity-20">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_30%_50%,rgba(255,255,255,0.2),transparent_50%)]"></div>
        </div>

        <div class="relative z-10">
            <div class="flex flex-col md:flex-row md:items-center gap-6">
                <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center">
                    <i class="fas fa-chart-line text-3xl text-white"></i>
                </div>
                <div>
                    <h1 class="text-3xl md:text-4xl font-black text-white">ความก้าวหน้าของฉัน</h1>
                    <p class="text-white/80">ติดตามผลการเรียนรู้และพัฒนาของคุณ</p>
                </div>
            </div>

            {{-- Stats --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
                <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 text-center">
                    <div class="text-3xl font-bold text-white">{{ $stats['courses_completed'] ?? 0 }}</div>
                    <div class="text-white/80 text-sm">เรียนจบแล้ว</div>
                </div>
                <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 text-center">
                    <div class="text-3xl font-bold text-white">{{ $stats['courses_in_progress'] ?? 0 }}</div>
                    <div class="text-white/80 text-sm">กำลังเรียน</div>
                </div>
                <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 text-center">
                    <div class="text-3xl font-bold text-white">{{ isset($stats['total_time_spent']) ? round($stats['total_time_spent'] / 3600, 1) : 0 }}</div>
                    <div class="text-white/80 text-sm">ชั่วโมงที่เรียน</div>
                </div>
                <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 text-center">
                    <div class="text-3xl font-bold text-white">{{ $stats['total_points'] ?? 0 }}</div>
                    <div class="text-white/80 text-sm">คะแนนสะสม</div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        {{-- In Progress --}}
        <div>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <i class="fas fa-spinner text-blue-500"></i>
                กำลังเรียนอยู่ ({{ $inProgressArticles->count() }})
            </h2>

            @if($inProgressArticles->count() > 0)
            <div class="space-y-4">
                @foreach($inProgressArticles as $progress)
                <a href="{{ route('user.academy.article', $progress->article->slug) }}"
                   class="tp-card block rounded-2xl hover:shadow-xl transition-all hover:-translate-y-1 overflow-hidden">
                    <div class="p-5">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="px-2 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 text-xs font-medium rounded-full">
                                {{ $progress->article->category->name ?? 'ทั่วไป' }}
                            </span>
                            <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-xs font-medium rounded-full">
                                กำลังเรียน
                            </span>
                        </div>
                        <h3 class="font-bold text-gray-900 dark:text-white mb-2">{{ $progress->article->title }}</h3>
                        <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400 mb-3">
                            <span>เข้าเรียนล่าสุด: {{ $progress->last_accessed_at ? $progress->last_accessed_at->diffForHumans() : '-' }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="flex-1 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-blue-500 to-purple-500 rounded-full"
                                     style="width: {{ $progress->progress_percentage }}%"></div>
                            </div>
                            <span class="text-sm font-medium text-blue-600 dark:text-blue-400">{{ $progress->progress_percentage }}%</span>
                        </div>
                    </div>
                </a>
                @endforeach
            </div>
            @else
            <div class="tp-card rounded-2xl p-8 text-center">
                <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-book-open text-3xl text-gray-400"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-600 dark:text-gray-400 mb-2">ยังไม่มีบทเรียนที่กำลังเรียน</h3>
                <a href="{{ route('user.academy.index') }}" class="inline-flex items-center gap-2 text-purple-600 dark:text-purple-400 hover:underline">
                    <i class="fas fa-plus"></i>
                    เริ่มเรียนบทเรียนใหม่
                </a>
            </div>
            @endif
        </div>

        {{-- Completed --}}
        <div>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <i class="fas fa-check-circle text-green-500"></i>
                เรียนจบแล้ว ({{ $completedArticles->count() }})
            </h2>

            @if($completedArticles->count() > 0)
            <div class="space-y-4">
                @foreach($completedArticles as $progress)
                <a href="{{ route('user.academy.article', $progress->article->slug) }}"
                   class="tp-card block rounded-2xl hover:shadow-xl transition-all hover:-translate-y-1 overflow-hidden">
                    <div class="p-5">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="px-2 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 text-xs font-medium rounded-full">
                                {{ $progress->article->category->name ?? 'ทั่วไป' }}
                            </span>
                            <span class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 text-xs font-medium rounded-full">
                                <i class="fas fa-check mr-1"></i>เรียนจบแล้ว
                            </span>
                        </div>
                        <h3 class="font-bold text-gray-900 dark:text-white mb-2">{{ $progress->article->title }}</h3>
                        <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
                            <span>เรียนจบเมื่อ: {{ $progress->completed_at ? $progress->completed_at->thaidate('j M Y') : '-' }}</span>
                            @if($progress->points_earned ?? 0 > 0)
                            <span class="text-yellow-600 dark:text-yellow-400">
                                <i class="fas fa-star mr-1"></i>+{{ $progress->points_earned }} คะแนน
                            </span>
                            @endif
                        </div>
                    </div>
                </a>
                @endforeach
            </div>
            @else
            <div class="tp-card rounded-2xl p-8 text-center">
                <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-trophy text-3xl text-gray-400"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-600 dark:text-gray-400 mb-2">ยังไม่มีบทเรียนที่เรียนจบ</h3>
                <p class="text-gray-500 dark:text-gray-500">เรียนให้จบบทเรียนเพื่อรับรางวัล!</p>
            </div>
            @endif
        </div>
    </div>

    {{-- Back to Academy --}}
    <div class="mt-8 text-center">
        <a href="{{ route('user.academy.index') }}"
           class="inline-flex items-center gap-2 px-6 py-3 bg-purple-600 text-white font-bold rounded-xl hover:bg-purple-700 transition">
            <i class="fas fa-graduation-cap"></i>
            กลับไปศูนย์การเรียนรู้
        </a>
    </div>

</div>
@endsection
