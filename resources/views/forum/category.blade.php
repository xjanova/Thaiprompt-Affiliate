{{--
    หน้าหมวดหมู่ฟอรั่ม
    แสดงกระทู้ในหมวดหมู่ที่เลือก
    ใช้ V3 Design System: Tailwind CSS + Alpine.js
--}}

@extends('layouts.user-arrow-x')

@section('title', $category->name . ' - ฟอรั่ม')

@section('content')
<div class="container-fluid px-4 py-6" x-data="forumCategory()">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-6">
        @foreach($breadcrumbs as $index => $crumb)
            @if($crumb['url'])
            <a href="{{ $crumb['url'] }}" class="hover:text-purple-600 dark:hover:text-purple-400 transition-colors">
                {{ $crumb['name'] }}
            </a>
            @else
            <span class="text-gray-900 dark:text-white font-medium">{{ $crumb['name'] }}</span>
            @endif

            @if($index < count($breadcrumbs) - 1)
            <i class="fas fa-chevron-right text-xs"></i>
            @endif
        @endforeach
    </nav>

    {{-- Header --}}
    <div class="relative overflow-hidden bg-gradient-to-r {{ $category->gradient_class }} rounded-3xl p-8 mb-8 shadow-2xl">
        <div class="absolute inset-0 opacity-20">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_50%,rgba(255,255,255,0.2),transparent_50%)]"></div>
        </div>

        <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center">
                    <i class="{{ $category->icon ?? 'fas fa-folder' }} text-3xl text-white"></i>
                </div>
                <div>
                    <h1 class="text-3xl md:text-4xl font-black text-white">{{ $category->name }}</h1>
                    <p class="text-white/90 mt-1">{{ $category->description }}</p>
                    <div class="flex items-center gap-4 mt-2 text-white/80 text-sm">
                        <span><i class="fas fa-comments mr-1"></i> {{ number_format($category->threads_count) }} กระทู้</span>
                        <span><i class="fas fa-message mr-1"></i> {{ number_format($category->posts_count) }} คอมเมนต์</span>
                    </div>
                </div>
            </div>

            @auth
            @if(!$category->is_locked)
            <a href="{{ route('forum.thread.create', ['category' => $category->slug]) }}"
               class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-white text-gray-900 font-bold rounded-xl hover:bg-white/90 hover:scale-105 transition-all shadow-lg">
                <i class="fas fa-plus"></i>
                <span>สร้างกระทู้ใหม่</span>
            </a>
            @endif
            @endauth
        </div>
    </div>

    {{-- หมวดหมู่ย่อย --}}
    @if($category->children->count() > 0)
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
        @foreach($category->children as $child)
        <a href="{{ route('forum.category', $child->slug) }}"
           class="flex items-center gap-4 p-4 glass-fusion-card rounded-xl bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl border border-white/20 dark:border-gray-700/30 hover:shadow-lg hover:scale-102 transition-all group">
            <div class="w-10 h-10 bg-gradient-to-br {{ $child->gradient_class }} rounded-lg flex items-center justify-center">
                <i class="{{ $child->icon ?? 'fas fa-folder' }} text-white"></i>
            </div>
            <div class="flex-1 min-w-0">
                <h3 class="font-semibold text-gray-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400">
                    {{ $child->name }}
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $child->threads_count }} กระทู้</p>
            </div>
            <i class="fas fa-chevron-right text-gray-400 group-hover:text-purple-500 transition-colors"></i>
        </a>
        @endforeach
    </div>
    @endif

    {{-- กระทู้ปักหมุด --}}
    @if($pinnedThreads->count() > 0)
    <div class="glass-fusion-card rounded-2xl p-6 bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl border border-white/20 dark:border-gray-700/30 mb-6">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <i class="fas fa-thumbtack text-red-500"></i>
            กระทู้ปักหมุด
        </h2>

        <div class="space-y-3">
            @foreach($pinnedThreads as $thread)
            @include('forum.components.thread-card', ['thread' => $thread])
            @endforeach
        </div>
    </div>
    @endif

    {{-- กระทู้ทั้งหมด --}}
    <div class="glass-fusion-card rounded-2xl p-6 bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl border border-white/20 dark:border-gray-700/30">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="fas fa-list text-blue-500"></i>
                กระทู้ทั้งหมด
            </h2>

            {{-- Sort Options --}}
            <select x-model="sortBy" @change="window.location = '{{ route('forum.category', $category->slug) }}?sort=' + sortBy"
                    class="text-sm px-3 py-2 bg-gray-100 dark:bg-gray-700 border-0 rounded-lg focus:ring-2 focus:ring-purple-500">
                <option value="latest">ล่าสุด</option>
                <option value="popular">ยอดนิยม</option>
                <option value="most_replied">ตอบเยอะ</option>
            </select>
        </div>

        <div class="space-y-3">
            @forelse($threads as $thread)
            @include('forum.components.thread-card', ['thread' => $thread])
            @empty
            <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                <i class="fas fa-inbox text-5xl mb-4"></i>
                <p class="mb-4">ยังไม่มีกระทู้ในหมวดหมู่นี้</p>
                @auth
                @if(!$category->is_locked)
                <a href="{{ route('forum.thread.create', ['category' => $category->slug]) }}"
                   class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white font-bold rounded-xl hover:shadow-lg transition-all">
                    <i class="fas fa-plus"></i>
                    เป็นคนแรกที่สร้างกระทู้
                </a>
                @endif
                @endauth
            </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($threads->hasPages())
        <div class="mt-6">
            {{ $threads->links() }}
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
function forumCategory() {
    return {
        sortBy: new URLSearchParams(window.location.search).get('sort') || 'latest',
    };
}
</script>
@endpush
@endsection
