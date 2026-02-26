{{--
    หน้าความคิดเห็นของฉัน
    ใช้ V3 Design System: Tailwind CSS + Alpine.js
--}}

@extends('layouts.user-arrow-x')

@section('title', 'ความคิดเห็นของฉัน - ฟอรั่ม')

@section('content')
<div class="container-fluid px-4 py-6">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-6">
        <a href="{{ route('forum.index') }}" class="hover:text-purple-600 dark:hover:text-purple-400 transition-colors">
            ฟอรั่ม
        </a>
        <i class="fas fa-chevron-right text-xs"></i>
        <span class="text-gray-900 dark:text-white font-medium">ความคิดเห็นของฉัน</span>
    </nav>

    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
            <span class="text-3xl">💬</span>
            ความคิดเห็นของฉัน
        </h1>
    </div>

    {{-- Post List --}}
    <div class="glass-fusion-card rounded-2xl p-6 bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl border border-white/20 dark:border-gray-700/30">
        <div class="space-y-4">
            @forelse($posts as $post)
            <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                <div class="flex items-start gap-4">
                    <div class="flex-1 min-w-0">
                        {{-- ลิงค์ไปกระทู้ --}}
                        <a href="{{ route('forum.thread.show', $post->thread->slug ?? '#') }}#post-{{ $post->id }}"
                           class="text-base font-semibold text-purple-600 dark:text-purple-400 hover:underline line-clamp-1">
                            {{ $post->thread->title ?? 'กระทู้ถูกลบ' }}
                        </a>

                        {{-- หมวดหมู่ --}}
                        @if($post->thread && $post->thread->category)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 mt-1">
                            {{ $post->thread->category->icon ?? '📁' }} {{ $post->thread->category->name }}
                        </span>
                        @endif

                        {{-- เนื้อหาโพสต์ --}}
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-2 line-clamp-2">
                            {{ Str::limit(strip_tags($post->content), 150) }}
                        </p>

                        {{-- สถิติ --}}
                        <div class="flex items-center gap-4 mt-2 text-xs text-gray-500 dark:text-gray-400">
                            <span><i class="fas fa-heart text-red-400 mr-1"></i>{{ $post->likes_count ?? 0 }} ไลค์</span>
                            <span><i class="fas fa-clock mr-1"></i>{{ $post->created_at->diffForHumans() }}</span>
                            @if($post->is_best_answer)
                            <span class="text-green-600 dark:text-green-400 font-bold">
                                <i class="fas fa-check-circle mr-1"></i>คำตอบที่ดีที่สุด
                            </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                <i class="fas fa-comment-dots text-5xl mb-4 opacity-50"></i>
                <p class="text-lg mb-2">คุณยังไม่มีความคิดเห็น</p>
                <p class="text-sm">เริ่มตอบกระทู้ที่น่าสนใจเลย!</p>
            </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($posts->hasPages())
        <div class="mt-6">
            {{ $posts->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
