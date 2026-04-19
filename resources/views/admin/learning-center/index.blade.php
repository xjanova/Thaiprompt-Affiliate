{{-- 📚 Academy Knowledge - ศูนย์เรียนรู้ (V3 UI) --}}
@extends('layouts.admin-v3')

@section('title', 'Academy Knowledge - ศูนย์เรียนรู้')

@section('content')
@php
    // สีหลักของระบบ
    $primaryColor = \App\Models\Setting::get('primary_color', '#6366F1');
    $secondaryColor = \App\Models\Setting::get('secondary_color', '#8B5CF6');
@endphp

<div
    x-data="{
        searchQuery: '',
        selectedDifficulty: 'all',
        viewMode: 'grid',
        isLoading: false,
        showFilters: false,

        // กรองหมวดหมู่ตามการค้นหา
        filterCategories() {
            const query = this.searchQuery.toLowerCase();
            const cards = document.querySelectorAll('.category-card');

            cards.forEach(card => {
                const name = card.dataset.name?.toLowerCase() || '';
                const desc = card.dataset.description?.toLowerCase() || '';

                if (query === '' || name.includes(query) || desc.includes(query)) {
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
         style="background: linear-gradient(135deg, {{ $primaryColor }} 0%, {{ $secondaryColor }} 100%);">

        {{-- Decorative Elements --}}
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute -top-10 -right-10 w-40 h-40 bg-white/10 rounded-full blur-2xl"></div>
            <div class="absolute -bottom-10 -left-10 w-60 h-60 bg-white/10 rounded-full blur-3xl"></div>
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-white/5 rounded-full blur-3xl"></div>
        </div>

        <div class="relative px-6 py-12 md:px-12 md:py-16">
            <div class="max-w-4xl">
                {{-- Badge --}}
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 backdrop-blur-sm rounded-full text-white text-sm font-medium mb-6">
                    <span class="text-xl">🎓</span>
                    <span>Academy Knowledge</span>
                    <span class="px-2 py-0.5 bg-green-400/30 rounded-full text-xs">อ่านฟรี</span>
                </div>

                {{-- Title --}}
                <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-4">
                    ศูนย์เรียนรู้ TP-Affiliate
                </h1>

                {{-- Description --}}
                <p class="text-lg md:text-xl text-white/80 max-w-2xl mb-8">
                    เรียนรู้ทุกอย่างเกี่ยวกับระบบ TP-Affiliate ตั้งแต่พื้นฐานจนถึงขั้นสูง
                    พร้อมรับใบประกาศนียบัตรเมื่อเรียนจบ
                </p>

                {{-- Search Box --}}
                <div class="relative max-w-xl">
                    <input
                        type="text"
                        x-model="searchQuery"
                        @input="filterCategories()"
                        placeholder="ค้นหาหัวข้อที่ต้องการเรียน..."
                        class="w-full px-6 py-4 pl-14 bg-white/95 dark:bg-gray-800/95 backdrop-blur-sm rounded-xl shadow-lg text-gray-900 dark:text-white placeholder-gray-500 focus:outline-none focus:ring-4 focus:ring-white/30 transition"
                    >
                    <svg class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </div>

            {{-- Stats Cards --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-10">
                <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center">
                    <div class="text-3xl font-bold text-white">{{ $user_stats['completed_courses'] ?? 0 }}</div>
                    <div class="text-sm text-white/80">บทเรียนที่เรียนจบ</div>
                </div>
                <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center">
                    <div class="text-3xl font-bold text-white">{{ $user_stats['in_progress'] ?? 0 }}</div>
                    <div class="text-sm text-white/80">กำลังเรียน</div>
                </div>
                <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center">
                    <div class="text-3xl font-bold text-white">{{ $user_stats['total_hours'] ?? 0 }}</div>
                    <div class="text-sm text-white/80">ชั่วโมงเรียน</div>
                </div>
                <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center">
                    <div class="text-3xl font-bold text-white">{{ $user_stats['certificates'] ?? 0 }}</div>
                    <div class="text-sm text-white/80">ใบประกาศนียบัตร</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Featured Articles --}}
    @if($featured_articles && $featured_articles->count() > 0)
    <div class="mb-10">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                    แนะนำสำหรับคุณ
                </h2>
                <p class="text-gray-600 dark:text-gray-400">บทเรียนยอดนิยมที่ไม่ควรพลาด</p>
            </div>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($featured_articles as $article)
            <a href="{{ route('admin.learning-center.article', $article->slug) }}"
               class="group relative bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden hover:shadow-2xl transition-all duration-300 hover:-translate-y-1">

                {{-- Thumbnail --}}
                <div class="aspect-video relative overflow-hidden"
                     style="background: linear-gradient(135deg, {{ $article->category->color ?? '#6366F1' }}, {{ $article->category->color ?? '#8B5CF6' }}99);">
                    @if($article->thumbnail)
                        <img src="{{ $article->thumbnail }}" alt="{{ $article->title }}" class="w-full h-full object-cover">
                    @else
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span class="text-6xl">{{ $article->category->icon ?? '📚' }}</span>
                        </div>
                    @endif
                    <div class="absolute top-4 left-4">
                        <span class="px-3 py-1 bg-white/90 dark:bg-gray-900/90 backdrop-blur-sm rounded-full text-sm font-medium text-gray-900 dark:text-white">
                            {{ $article->category->name }}
                        </span>
                    </div>
                    <div class="absolute top-4 right-4">
                        <span class="px-2 py-1 bg-yellow-400 text-yellow-900 rounded-full text-xs font-bold">
                            ⭐ แนะนำ
                        </span>
                    </div>
                </div>

                {{-- Content --}}
                <div class="p-5">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition line-clamp-2">
                        {{ $article->title }}
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm mb-4 line-clamp-2">
                        {{ $article->excerpt }}
                    </p>

                    <div class="flex items-center justify-between text-sm">
                        <div class="flex items-center gap-4 text-gray-500 dark:text-gray-400">
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                {{ $article->formatted_duration }}
                            </span>
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                {{ number_format($article->views) }}
                            </span>
                        </div>

                        @php
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
                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $difficultyColors[$article->difficulty] ?? 'bg-gray-100 text-gray-700' }}">
                            {{ $difficultyLabels[$article->difficulty] ?? $article->difficulty }}
                        </span>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Categories Section --}}
    <div class="mb-10">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                    หมวดหมู่วิชา
                </h2>
                <p class="text-gray-600 dark:text-gray-400">เลือกหมวดหมู่ที่ต้องการเรียนรู้</p>
            </div>

            {{-- View Mode Toggle --}}
            <div class="flex items-center gap-2 bg-gray-100 dark:bg-gray-800 rounded-lg p-1">
                <button
                    @click="viewMode = 'grid'"
                    :class="viewMode === 'grid' ? 'bg-white dark:bg-gray-700 shadow text-indigo-600 dark:text-indigo-400' : 'text-gray-500'"
                    class="p-2 rounded-md transition"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                    </svg>
                </button>
                <button
                    @click="viewMode = 'list'"
                    :class="viewMode === 'list' ? 'bg-white dark:bg-gray-700 shadow text-indigo-600 dark:text-indigo-400' : 'text-gray-500'"
                    class="p-2 rounded-md transition"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Grid View --}}
        <div x-show="viewMode === 'grid'" class="grid md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @foreach($categories as $category)
            <a href="{{ route('admin.learning-center.category', $category['slug']) }}"
               class="category-card group relative bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden hover:shadow-2xl transition-all duration-300 hover:-translate-y-1"
               data-name="{{ $category['name'] }}"
               data-description="{{ $category['description'] }}">

                {{-- Top Accent --}}
                <div class="h-2" style="background: {{ $category['color'] }};"></div>

                <div class="p-6">
                    {{-- Icon --}}
                    <div class="w-14 h-14 rounded-xl flex items-center justify-center text-3xl mb-4 transition-transform group-hover:scale-110"
                         style="background: {{ $category['color'] }}20;">
                        {{ $category['icon'] }}
                    </div>

                    {{-- Title --}}
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition">
                        {{ $category['name'] }}
                    </h3>

                    {{-- Description --}}
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">
                        {{ $category['description'] }}
                    </p>

                    {{-- Meta --}}
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $category['articles_count'] }} บทเรียน
                        </span>
                        <span class="text-indigo-600 dark:text-indigo-400 group-hover:translate-x-1 transition-transform">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </span>
                    </div>
                </div>
            </a>
            @endforeach
        </div>

        {{-- List View --}}
        <div x-show="viewMode === 'list'" class="space-y-4" style="display: none;">
            @foreach($categories as $category)
            <a href="{{ route('admin.learning-center.category', $category['slug']) }}"
               class="category-card group flex items-center gap-6 bg-white dark:bg-gray-800 rounded-xl shadow p-4 hover:shadow-lg transition-all duration-300"
               data-name="{{ $category['name'] }}"
               data-description="{{ $category['description'] }}">

                {{-- Icon --}}
                <div class="w-14 h-14 rounded-xl flex items-center justify-center text-3xl flex-shrink-0"
                     style="background: {{ $category['color'] }}20;">
                    {{ $category['icon'] }}
                </div>

                {{-- Content --}}
                <div class="flex-1 min-w-0">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition">
                        {{ $category['name'] }}
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 truncate">
                        {{ $category['description'] }}
                    </p>
                </div>

                {{-- Meta --}}
                <div class="flex items-center gap-4 flex-shrink-0">
                    <span class="px-3 py-1 bg-gray-100 dark:bg-gray-700 rounded-full text-sm text-gray-600 dark:text-gray-300">
                        {{ $category['articles_count'] }} บทเรียน
                    </span>
                    <span class="text-indigo-600 dark:text-indigo-400 group-hover:translate-x-1 transition-transform">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </span>
                </div>
            </a>
            @endforeach
        </div>
    </div>

    {{-- Popular Articles --}}
    @if($popular_articles && count($popular_articles) > 0)
    <div class="mb-10">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                    บทเรียนยอดนิยม
                </h2>
                <p class="text-gray-600 dark:text-gray-400">บทเรียนที่มีผู้เข้าชมมากที่สุด</p>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden">
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($popular_articles as $index => $article)
                <a href="{{ route('admin.learning-center.article', $article['slug']) }}"
                   class="flex items-center gap-4 p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">

                    {{-- Rank --}}
                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-lg font-bold
                        {{ $index === 0 ? 'bg-yellow-100 text-yellow-700' : ($index === 1 ? 'bg-gray-100 text-gray-600' : ($index === 2 ? 'bg-orange-100 text-orange-700' : 'bg-gray-50 text-gray-500')) }}">
                        {{ $index + 1 }}
                    </div>

                    {{-- Content --}}
                    <div class="flex-1 min-w-0">
                        <h4 class="font-semibold text-gray-900 dark:text-white truncate">
                            {{ $article['title'] }}
                        </h4>
                        <div class="flex items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
                            <span>{{ $article['category'] }}</span>
                            <span>•</span>
                            <span>{{ $article['duration'] }}</span>
                        </div>
                    </div>

                    {{-- Progress --}}
                    @if($article['progress'] > 0)
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <div class="w-20 h-2 bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden">
                            <div class="h-full bg-green-500 rounded-full" style="width: {{ $article['progress'] }}%"></div>
                        </div>
                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $article['progress'] }}%</span>
                    </div>
                    @endif

                    {{-- Views --}}
                    <div class="flex items-center gap-1 text-gray-500 dark:text-gray-400 flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <span class="text-sm">{{ number_format($article['views']) }}</span>
                    </div>

                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- Quick Links / CTA --}}
    <div class="grid md:grid-cols-2 gap-6 mb-10">
        {{-- My Certificates --}}
        <a href="{{ route('admin.certificates.index') }}"
           class="group relative overflow-hidden bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl p-6 text-white hover:shadow-xl transition-all duration-300">
            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/10 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
            <div class="relative">
                <div class="text-4xl mb-3">🏆</div>
                <h3 class="text-xl font-bold mb-2">ใบประกาศนียบัตรของฉัน</h3>
                <p class="text-white/80 text-sm">ดูและดาวน์โหลดใบประกาศนียบัตรที่ได้รับ</p>
            </div>
        </a>

        {{-- My Progress --}}
        <div class="relative overflow-hidden bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl p-6 text-white">
            <div class="relative">
                <div class="text-4xl mb-3">📈</div>
                <h3 class="text-xl font-bold mb-2">ความคืบหน้าของคุณ</h3>
                <div class="flex items-center gap-4 mt-4">
                    <div>
                        <div class="text-3xl font-bold">{{ $user_stats['completed_courses'] ?? 0 }}</div>
                        <div class="text-sm text-white/80">เรียนจบแล้ว</div>
                    </div>
                    <div class="w-px h-12 bg-white/30"></div>
                    <div>
                        <div class="text-3xl font-bold">{{ $user_stats['in_progress'] ?? 0 }}</div>
                        <div class="text-sm text-white/80">กำลังเรียน</div>
                    </div>
                    <div class="w-px h-12 bg-white/30"></div>
                    <div>
                        <div class="text-3xl font-bold">{{ $user_stats['total_hours'] ?? 0 }}</div>
                        <div class="text-sm text-white/80">ชั่วโมง</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Help Section --}}
    <div class="bg-gray-100 dark:bg-gray-800/50 rounded-2xl p-6">
        <div class="flex flex-col md:flex-row items-center gap-6">
            <div class="text-5xl">💡</div>
            <div class="flex-1 text-center md:text-left">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1">
                    ต้องการความช่วยเหลือ?
                </h3>
                <p class="text-gray-600 dark:text-gray-400">
                    หากมีคำถามหรือปัญหาเกี่ยวกับการใช้งาน สามารถติดต่อทีมสนับสนุนได้ตลอดเวลา
                </p>
            </div>
            <a href="{{ route('admin.tickets.index') }}"
               class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition flex-shrink-0">
                ติดต่อสนับสนุน
            </a>
        </div>
    </div>
</div>

@endsection
