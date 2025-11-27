{{-- 📚 Academy Knowledge - หน้าหมวดหมู่ (V3 UI) --}}
@extends('layouts.admin')

@section('title', $category->name . ' - Academy Knowledge')

@section('content')
@php
    $categoryColor = $category->color ?? '#6366F1';
@endphp

<div
    x-data="{
        searchQuery: '',
        filterDifficulty: 'all',
        sortBy: 'order',

        // กรองบทความ
        filterArticles() {
            const query = this.searchQuery.toLowerCase();
            const difficulty = this.filterDifficulty;
            const cards = document.querySelectorAll('.article-card');

            cards.forEach(card => {
                const title = card.dataset.title?.toLowerCase() || '';
                const articleDifficulty = card.dataset.difficulty || '';

                const matchesSearch = query === '' || title.includes(query);
                const matchesDifficulty = difficulty === 'all' || articleDifficulty === difficulty;

                if (matchesSearch && matchesDifficulty) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        }
    }"
    class="min-h-screen"
>
    {{-- Hero Section --}}
    <div class="relative overflow-hidden rounded-2xl mb-8"
         style="background: linear-gradient(135deg, {{ $categoryColor }} 0%, {{ $categoryColor }}99 100%);">

        {{-- Decorative Elements --}}
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute -top-10 -right-10 w-40 h-40 bg-white/10 rounded-full blur-2xl"></div>
            <div class="absolute -bottom-10 -left-10 w-60 h-60 bg-white/10 rounded-full blur-3xl"></div>
        </div>

        <div class="relative px-6 py-10 md:px-12 md:py-14">
            {{-- Breadcrumb --}}
            <nav class="flex items-center gap-2 text-sm text-white/80 mb-6">
                <a href="{{ route('admin.learning-center.index') }}" class="hover:text-white transition">
                    🎓 Academy Knowledge
                </a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <span class="text-white">{{ $category->name }}</span>
            </nav>

            {{-- Header --}}
            <div class="flex items-center gap-6">
                <div class="w-20 h-20 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center text-5xl">
                    {{ $category->icon ?? '📚' }}
                </div>
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold text-white mb-2">
                        {{ $category->name }}
                    </h1>
                    <p class="text-lg text-white/80 max-w-2xl">
                        {{ $category->description }}
                    </p>
                </div>
            </div>

            {{-- Stats --}}
            <div class="flex items-center gap-6 mt-8">
                <div class="bg-white/20 backdrop-blur-sm rounded-lg px-4 py-2">
                    <span class="text-xl font-bold text-white">{{ $articles->count() }}</span>
                    <span class="text-white/80 ml-1">บทเรียน</span>
                </div>
                <div class="bg-white/20 backdrop-blur-sm rounded-lg px-4 py-2">
                    <span class="text-xl font-bold text-white">{{ $articles->filter(fn($a) => $a['progress'] && $a['progress']->status === 'completed')->count() }}</span>
                    <span class="text-white/80 ml-1">เรียนจบแล้ว</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-4 mb-6">
        <div class="flex flex-col md:flex-row gap-4">
            {{-- Search --}}
            <div class="flex-1 relative">
                <input
                    type="text"
                    x-model="searchQuery"
                    @input="filterArticles()"
                    placeholder="ค้นหาบทเรียน..."
                    class="w-full pl-10 pr-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg text-gray-900 dark:text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>

            {{-- Difficulty Filter --}}
            <select
                x-model="filterDifficulty"
                @change="filterArticles()"
                class="px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
            >
                <option value="all">ทุกระดับ</option>
                <option value="beginner">เริ่มต้น</option>
                <option value="intermediate">ปานกลาง</option>
                <option value="advanced">ขั้นสูง</option>
            </select>
        </div>
    </div>

    {{-- Articles List --}}
    <div class="space-y-4">
        @forelse($articles as $index => $item)
        @php
            $article = $item['article'];
            $progress = $item['progress'];
            $canUnlock = $item['can_unlock'];
            $isLocked = $item['is_locked'];

            $progressPercent = $progress ? $progress->progress_percentage : 0;
            $status = $progress ? $progress->status : 'not_started';

            $difficultyColors = [
                'beginner' => 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-400',
                'intermediate' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-400',
                'advanced' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-400',
            ];
            $difficultyLabels = [
                'beginner' => 'เริ่มต้น',
                'intermediate' => 'ปานกลาง',
                'advanced' => 'ขั้นสูง',
            ];
        @endphp

        <div
            class="article-card bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden transition-all duration-300 {{ $isLocked ? 'opacity-60' : 'hover:shadow-xl' }}"
            data-title="{{ $article->title }}"
            data-difficulty="{{ $article->difficulty }}"
        >
            @if($isLocked)
                {{-- Locked Article --}}
                <div class="flex items-center gap-6 p-5">
                    {{-- Number --}}
                    <div class="w-12 h-12 rounded-xl bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-xl font-bold text-gray-500">
                        {{ $index + 1 }}
                    </div>

                    {{-- Content --}}
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-gray-400">🔒</span>
                            <h3 class="font-bold text-gray-500 dark:text-gray-400">
                                {{ $article->title }}
                            </h3>
                        </div>
                        <p class="text-sm text-gray-400 dark:text-gray-500">
                            ต้องเรียนบทความก่อนหน้าให้จบก่อน
                        </p>
                    </div>

                    {{-- Meta --}}
                    <div class="flex items-center gap-4">
                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $difficultyColors[$article->difficulty] ?? 'bg-gray-100 text-gray-700' }}">
                            {{ $difficultyLabels[$article->difficulty] ?? $article->difficulty }}
                        </span>
                        <span class="text-sm text-gray-400">
                            {{ $article->formatted_duration }}
                        </span>
                    </div>
                </div>
            @else
                {{-- Unlocked Article --}}
                <a href="{{ route('admin.learning-center.article', $article->slug) }}"
                   class="flex items-center gap-6 p-5 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition group">

                    {{-- Number / Status --}}
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl font-bold
                        {{ $status === 'completed' ? 'bg-green-100 dark:bg-green-900/50' : ($status === 'in_progress' ? 'bg-indigo-100 dark:bg-indigo-900/50' : 'bg-gray-100 dark:bg-gray-700') }}"
                        style="{{ $status === 'not_started' ? 'background: ' . $categoryColor . '15;' : '' }}"
                    >
                        @if($status === 'completed')
                            <span class="text-green-600 dark:text-green-400">✓</span>
                        @elseif($status === 'in_progress')
                            <span class="text-indigo-600 dark:text-indigo-400">▶</span>
                        @else
                            <span class="text-gray-600 dark:text-gray-400">{{ $index + 1 }}</span>
                        @endif
                    </div>

                    {{-- Content --}}
                    <div class="flex-1 min-w-0">
                        <h3 class="font-bold text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition mb-1">
                            {{ $article->title }}
                        </h3>
                        @if($article->excerpt)
                        <p class="text-sm text-gray-600 dark:text-gray-400 truncate">
                            {{ $article->excerpt }}
                        </p>
                        @endif

                        {{-- Progress Bar --}}
                        @if($progressPercent > 0 && $status !== 'completed')
                        <div class="flex items-center gap-2 mt-2">
                            <div class="w-32 h-1.5 bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden">
                                <div class="h-full bg-indigo-500 rounded-full" style="width: {{ $progressPercent }}%"></div>
                            </div>
                            <span class="text-xs text-gray-500">{{ $progressPercent }}%</span>
                        </div>
                        @endif
                    </div>

                    {{-- Meta --}}
                    <div class="flex items-center gap-4 flex-shrink-0">
                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $difficultyColors[$article->difficulty] ?? 'bg-gray-100 text-gray-700' }}">
                            {{ $difficultyLabels[$article->difficulty] ?? $article->difficulty }}
                        </span>
                        <span class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            {{ $article->formatted_duration }}
                        </span>
                        @if($status === 'completed')
                            <span class="px-3 py-1 bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-400 rounded-full text-xs font-medium">
                                เรียนจบแล้ว
                            </span>
                        @elseif($status === 'in_progress')
                            <span class="px-3 py-1 bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-400 rounded-full text-xs font-medium">
                                กำลังเรียน
                            </span>
                        @endif
                        <svg class="w-5 h-5 text-gray-400 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </a>
            @endif
        </div>
        @empty
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-12 text-center">
            <div class="text-6xl mb-4">📭</div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">ยังไม่มีบทเรียน</h3>
            <p class="text-gray-600 dark:text-gray-400">หมวดหมู่นี้ยังไม่มีบทเรียน กรุณากลับมาใหม่ภายหลัง</p>
            <a href="{{ route('admin.learning-center.index') }}"
               class="inline-flex items-center gap-2 mt-6 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                กลับหน้าหลัก
            </a>
        </div>
        @endforelse
    </div>

    {{-- Back Button --}}
    <div class="mt-8">
        <a href="{{ route('admin.learning-center.index') }}"
           class="inline-flex items-center gap-2 px-6 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 font-semibold rounded-xl transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            กลับหน้าหลัก
        </a>
    </div>
</div>

@endsection
