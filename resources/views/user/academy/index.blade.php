{{--
    หน้าหลักศูนย์การเรียนรู้ (Academy)
    ใช้ V3 Design System: Tailwind CSS + Alpine.js
--}}

@extends('layouts.user-v4')

@section('title', 'ศูนย์การเรียนรู้')

@section('content')
<div class="container-fluid px-4 py-6 max-w-7xl mx-auto">

    {{-- Header --}}
    <div class="relative overflow-hidden bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-500 rounded-3xl p-8 mb-8 shadow-2xl">
        <div class="absolute inset-0 opacity-20">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_30%_50%,rgba(255,255,255,0.2),transparent_50%)]"></div>
        </div>

        <div class="relative z-10">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                <div>
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-14 h-14 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center">
                            <i class="fas fa-graduation-cap text-3xl text-white"></i>
                        </div>
                        <div>
                            <h1 class="text-3xl md:text-4xl font-black text-white">ศูนย์การเรียนรู้</h1>
                            <p class="text-white/80">เรียนรู้และพัฒนาทักษะไปด้วยกัน</p>
                        </div>
                    </div>
                </div>

                <a href="{{ route('user.academy.my-progress') }}"
                   class="inline-flex items-center gap-2 px-6 py-3 bg-white/20 backdrop-blur-sm text-white rounded-xl hover:bg-white/30 transition">
                    <i class="fas fa-chart-line"></i>
                    <span>ความก้าวหน้าของฉัน</span>
                </a>
            </div>

            {{-- Stats --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
                <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 text-center">
                    <div class="text-3xl font-bold text-white">{{ $userStats['completed_courses'] ?? 0 }}</div>
                    <div class="text-white/80 text-sm">เรียนจบแล้ว</div>
                </div>
                <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 text-center">
                    <div class="text-3xl font-bold text-white">{{ $userStats['in_progress'] ?? 0 }}</div>
                    <div class="text-white/80 text-sm">กำลังเรียน</div>
                </div>
                <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 text-center">
                    <div class="text-3xl font-bold text-white">{{ $userStats['total_hours'] ?? 0 }}</div>
                    <div class="text-white/80 text-sm">ชั่วโมงที่เรียน</div>
                </div>
                <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 text-center">
                    <div class="text-3xl font-bold text-white">{{ $userStats['certificates'] ?? 0 }}</div>
                    <div class="text-white/80 text-sm">ใบประกาศ</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Featured Articles --}}
    @if($featuredArticles->count() > 0)
    <div class="mb-8">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <i class="fas fa-star text-yellow-500"></i>
            บทเรียนแนะนำ
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach($featuredArticles as $article)
            <a href="{{ route('user.academy.article', $article->slug) }}"
               class="tp-card group relative overflow-hidden rounded-2xl hover:shadow-xl transition-all hover:-translate-y-1">
                <div class="absolute inset-0 bg-gradient-to-br from-purple-500/10 to-pink-500/10 opacity-0 group-hover:opacity-100 transition"></div>
                <div class="p-6">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="px-2 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 text-xs font-medium rounded-full">
                            {{ $article->category->name ?? 'ทั่วไป' }}
                        </span>
                        @if($article->is_featured)
                        <span class="px-2 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400 text-xs font-medium rounded-full">
                            <i class="fas fa-star mr-1"></i>แนะนำ
                        </span>
                        @endif
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2 group-hover:text-purple-600 dark:group-hover:text-purple-400 transition">
                        {{ $article->title }}
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm mb-4 line-clamp-2">
                        {{ Str::limit(strip_tags($article->excerpt ?? $article->content), 100) }}
                    </p>
                    <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
                        <span><i class="fas fa-clock mr-1"></i>{{ $article->formatted_duration ?? '10 นาที' }}</span>
                        <span><i class="fas fa-eye mr-1"></i>{{ number_format($article->views) }}</span>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Categories --}}
    <div class="mb-8">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <i class="fas fa-folder-open text-blue-500"></i>
            หมวดหมู่การเรียนรู้
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($categories as $category)
            <a href="{{ route('user.academy.category', $category['slug']) }}"
               class="tp-card group relative overflow-hidden rounded-2xl hover:shadow-xl transition-all hover:-translate-y-1">
                <div class="p-6">
                    <div class="flex items-start gap-4">
                        <div class="w-14 h-14 bg-gradient-to-br from-{{ $category['color'] ?? 'blue' }}-400 to-{{ $category['color'] ?? 'blue' }}-600 rounded-xl flex items-center justify-center text-2xl shadow-lg">
                            {{ $category['icon'] ?? '📚' }}
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1 group-hover:text-purple-600 dark:group-hover:text-purple-400 transition">
                                {{ $category['name'] }}
                            </h3>
                            <p class="text-gray-600 dark:text-gray-400 text-sm mb-3 line-clamp-2">
                                {{ $category['description'] ?? 'เรียนรู้เพิ่มเติม' }}
                            </p>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                    <i class="fas fa-book mr-1"></i>{{ $category['articles_count'] }} บทเรียน
                                </span>
                                @if(($category['progress']['total'] ?? 0) > 0)
                                @php
                                    $progressTotal = $category['progress']['total'];
                                    $progressCompleted = $category['progress']['completed'] ?? 0;
                                    $progressPercent = round(($progressCompleted / $progressTotal) * 100);
                                @endphp
                                <div class="flex items-center gap-2">
                                    <div class="w-20 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                        <div class="h-full bg-gradient-to-r from-green-400 to-green-600 rounded-full"
                                             style="width: {{ $progressPercent }}%"></div>
                                    </div>
                                    <span class="text-xs text-gray-500">{{ $progressPercent }}%</span>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </a>
            @empty
            <div class="col-span-3 text-center py-12">
                <div class="w-20 h-20 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-graduation-cap text-4xl text-gray-400"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-600 dark:text-gray-400">ยังไม่มีหมวดหมู่</h3>
                <p class="text-gray-500 dark:text-gray-500">บทเรียนกำลังจะมาเร็วๆ นี้</p>
            </div>
            @endforelse
        </div>
    </div>

    {{-- Popular Articles --}}
    @if(count($popularArticles) > 0)
    <div class="mb-8">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <i class="fas fa-fire text-orange-500"></i>
            บทเรียนยอดนิยม
        </h2>
        <div class="tp-card rounded-2xl overflow-hidden">
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($popularArticles as $index => $article)
                <a href="{{ route('user.academy.article', $article['slug']) }}"
                   class="flex items-center gap-4 p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                    <div class="w-10 h-10 bg-gradient-to-br {{ $index < 3 ? 'from-yellow-400 to-orange-500' : 'from-gray-300 to-gray-400 dark:from-gray-600 dark:to-gray-700' }} rounded-full flex items-center justify-center text-white font-bold">
                        {{ $index + 1 }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-medium text-gray-900 dark:text-white truncate">{{ $article['title'] }}</h3>
                        <div class="flex items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
                            <span>{{ $article['category'] }}</span>
                            <span><i class="fas fa-eye mr-1"></i>{{ number_format($article['views']) }}</span>
                            <span><i class="fas fa-clock mr-1"></i>{{ $article['duration'] }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        @if($article['status'] === 'completed')
                        <span class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 text-xs font-medium rounded-full">
                            <i class="fas fa-check mr-1"></i>เรียนจบแล้ว
                        </span>
                        @elseif($article['progress'] > 0)
                        <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-xs font-medium rounded-full">
                            {{ $article['progress'] }}%
                        </span>
                        @elseif(!$article['can_access'])
                        <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 text-xs font-medium rounded-full">
                            <i class="fas fa-lock mr-1"></i>ล็อค
                        </span>
                        @endif
                        <i class="fas fa-chevron-right text-gray-400"></i>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
    </div>
    @endif

</div>
@endsection
