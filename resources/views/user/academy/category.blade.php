{{--
    หน้าแสดงบทเรียนในหมวดหมู่
    ใช้ V3 Design System: Tailwind CSS + Alpine.js
--}}

@extends('layouts.user-v4')

@section('title', $category->name . ' - ศูนย์การเรียนรู้')

@section('content')
<div class="container-fluid px-4 py-6 max-w-7xl mx-auto">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-6">
        <a href="{{ route('user.academy.index') }}" class="hover:text-purple-600 dark:hover:text-purple-400 transition-colors">
            <i class="fas fa-graduation-cap mr-1"></i>ศูนย์การเรียนรู้
        </a>
        <i class="fas fa-chevron-right text-xs"></i>
        <span class="text-gray-900 dark:text-white font-medium">{{ $category->name }}</span>
    </nav>

    {{-- Category Header --}}
    <div class="relative overflow-hidden bg-gradient-to-br from-{{ $category->color ?? 'indigo' }}-500 via-{{ $category->color ?? 'purple' }}-600 to-{{ $category->color ?? 'pink' }}-500 rounded-3xl p-8 mb-8 shadow-2xl">
        <div class="absolute inset-0 opacity-20">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_30%_50%,rgba(255,255,255,0.2),transparent_50%)]"></div>
        </div>

        <div class="relative z-10">
            <div class="flex flex-col md:flex-row md:items-center gap-6">
                <div class="w-20 h-20 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center text-5xl">
                    {{ $category->icon ?? '📚' }}
                </div>
                <div class="flex-1">
                    <h1 class="text-3xl md:text-4xl font-black text-white mb-2">{{ $category->name }}</h1>
                    @if($category->description)
                    <p class="text-white/80 text-lg">{{ $category->description }}</p>
                    @endif
                </div>
            </div>

            {{-- Progress --}}
            @if(($categoryProgress['total'] ?? 0) > 0)
            @php
                $progressTotal = $categoryProgress['total'];
                $progressCompleted = $categoryProgress['completed'] ?? 0;
                $progressPercent = round(($progressCompleted / $progressTotal) * 100);
            @endphp
            <div class="mt-6 bg-white/10 backdrop-blur-sm rounded-xl p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-white font-medium">ความก้าวหน้าของคุณ</span>
                    <span class="text-white">{{ $progressCompleted }}/{{ $progressTotal }} บทเรียน</span>
                </div>
                <div class="w-full h-3 bg-white/20 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-green-400 to-green-500 rounded-full transition-all"
                         style="width: {{ $progressPercent }}%"></div>
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- Articles by Level --}}
    @forelse($articlesByLevel as $level => $levelArticles)
    <div class="mb-8">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            @if($level == 1)
            <span class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center text-white text-sm">1</span>
            <span>ระดับเริ่มต้น</span>
            @elseif($level == 2)
            <span class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm">2</span>
            <span>ระดับกลาง</span>
            @elseif($level == 3)
            <span class="w-8 h-8 bg-purple-500 rounded-full flex items-center justify-center text-white text-sm">3</span>
            <span>ระดับสูง</span>
            @else
            <span class="w-8 h-8 bg-gray-500 rounded-full flex items-center justify-center text-white text-sm">{{ $level }}</span>
            <span>ระดับ {{ $level }}</span>
            @endif
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($levelArticles as $item)
            @php
                $article = $item['article'];
                $progress = $item['progress'];
                $isLocked = $item['is_locked'];
            @endphp
            <div class="relative group">
                @if($isLocked)
                {{-- Locked Article --}}
                <div class="tp-card rounded-2xl p-6 opacity-60">
                    <div class="absolute inset-0 bg-gray-900/50 dark:bg-gray-900/70 rounded-2xl flex items-center justify-center z-10">
                        <div class="text-center">
                            <div class="w-12 h-12 bg-gray-800 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-2">
                                <i class="fas fa-lock text-xl text-gray-400"></i>
                            </div>
                            <p class="text-white text-sm font-medium">{{ $item['lock_reason'] ?? 'เนื้อหาถูกล็อค' }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 mb-3">
                        <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 text-xs font-medium rounded-full">
                            {{ $article->difficulty_label ?? 'เริ่มต้น' }}
                        </span>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">{{ $article->title }}</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm line-clamp-2">
                        {{ Str::limit(strip_tags($article->excerpt ?? $article->content), 80) }}
                    </p>
                </div>
                @else
                {{-- Accessible Article --}}
                <a href="{{ route('user.academy.article', $article->slug) }}"
                   class="tp-card block rounded-2xl hover:shadow-xl transition-all hover:-translate-y-1 overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="px-2 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 text-xs font-medium rounded-full">
                                {{ $article->difficulty_label ?? 'เริ่มต้น' }}
                            </span>
                            @if($progress && $progress->status === 'completed')
                            <span class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 text-xs font-medium rounded-full">
                                <i class="fas fa-check mr-1"></i>เรียนจบแล้ว
                            </span>
                            @elseif($progress && $progress->status === 'in_progress')
                            <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-xs font-medium rounded-full">
                                กำลังเรียน
                            </span>
                            @endif
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2 group-hover:text-purple-600 dark:group-hover:text-purple-400 transition">
                            {{ $article->title }}
                        </h3>
                        <p class="text-gray-600 dark:text-gray-400 text-sm mb-4 line-clamp-2">
                            {{ Str::limit(strip_tags($article->excerpt ?? $article->content), 80) }}
                        </p>

                        {{-- Progress Bar --}}
                        @if($progress && $progress->progress_percentage > 0)
                        <div class="mb-4">
                            <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
                                <span>ความก้าวหน้า</span>
                                <span>{{ $progress->progress_percentage }}%</span>
                            </div>
                            <div class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-purple-500 to-pink-500 rounded-full"
                                     style="width: {{ $progress->progress_percentage }}%"></div>
                            </div>
                        </div>
                        @endif

                        <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
                            <span><i class="fas fa-clock mr-1"></i>{{ $article->formatted_duration ?? '10 นาที' }}</span>
                            <span><i class="fas fa-eye mr-1"></i>{{ number_format($article->views) }}</span>
                        </div>
                    </div>
                </a>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @empty
    <div class="text-center py-12">
        <div class="w-20 h-20 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-book text-4xl text-gray-400"></i>
        </div>
        <h3 class="text-lg font-medium text-gray-600 dark:text-gray-400">ยังไม่มีบทเรียนในหมวดหมู่นี้</h3>
        <p class="text-gray-500 dark:text-gray-500">บทเรียนกำลังจะมาเร็วๆ นี้</p>
        <a href="{{ route('user.academy.index') }}" class="inline-flex items-center gap-2 mt-4 text-purple-600 dark:text-purple-400 hover:underline">
            <i class="fas fa-arrow-left"></i>
            กลับไปหน้าศูนย์การเรียนรู้
        </a>
    </div>
    @endforelse

</div>
@endsection
