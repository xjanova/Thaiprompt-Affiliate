{{--
    หน้าแสดงบทเรียน
    ใช้ V3 Design System: Tailwind CSS + Alpine.js
--}}

@extends('layouts.user-v4')

@section('title', $article->title . ' - ศูนย์การเรียนรู้')

@section('content')
<div class="container-fluid px-4 py-6 max-w-5xl mx-auto" x-data="articleReader()">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-6">
        <a href="{{ route('user.academy.index') }}" class="hover:text-purple-600 dark:hover:text-purple-400 transition-colors">
            <i class="fas fa-graduation-cap mr-1"></i>ศูนย์การเรียนรู้
        </a>
        <i class="fas fa-chevron-right text-xs"></i>
        <a href="{{ route('user.academy.category', $article->category->slug) }}" class="hover:text-purple-600 dark:hover:text-purple-400 transition-colors">
            {{ $article->category->name }}
        </a>
        <i class="fas fa-chevron-right text-xs"></i>
        <span class="text-gray-900 dark:text-white font-medium truncate max-w-[200px]">{{ $article->title }}</span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        {{-- Main Content --}}
        <div class="lg:col-span-3">
            {{-- Article Header --}}
            <div class="tp-card rounded-2xl p-6 md:p-8 mb-6">
                <div class="flex flex-wrap items-center gap-2 mb-4">
                    <span class="px-3 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 text-sm font-medium rounded-full">
                        {{ $article->category->name }}
                    </span>
                    <span class="px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 text-sm font-medium rounded-full">
                        {{ $article->difficulty_label ?? 'เริ่มต้น' }}
                    </span>
                    @if($progress && $progress->status === 'completed')
                    <span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 text-sm font-medium rounded-full">
                        <i class="fas fa-check mr-1"></i>เรียนจบแล้ว
                    </span>
                    @endif
                </div>

                <h1 class="text-2xl md:text-3xl font-black text-gray-900 dark:text-white mb-4">{{ $article->title }}</h1>

                <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                    <span><i class="fas fa-clock mr-1"></i>{{ $article->formatted_duration ?? '10 นาที' }}</span>
                    <span><i class="fas fa-eye mr-1"></i>{{ number_format($article->views) }} เข้าชม</span>
                    <span><i class="fas fa-calendar mr-1"></i>{{ $article->created_at->thaidate('j M Y') }}</span>
                </div>

                {{-- Progress Bar --}}
                <div class="mt-6">
                    <div class="flex items-center justify-between text-sm mb-2">
                        <span class="text-gray-600 dark:text-gray-400">ความก้าวหน้า</span>
                        <span class="font-medium text-purple-600 dark:text-purple-400" x-text="progressPercent + '%'">{{ $progress->progress_percentage ?? 0 }}%</span>
                    </div>
                    <div class="w-full h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-purple-500 to-pink-500 rounded-full transition-all duration-500"
                             :style="{ width: progressPercent + '%' }"></div>
                    </div>
                </div>
            </div>

            {{-- Article Content --}}
            <div class="tp-card rounded-2xl p-6 md:p-8 mb-6">
                <div class="prose prose-lg dark:prose-invert max-w-none
                            prose-headings:text-gray-900 dark:prose-headings:text-white
                            prose-p:text-gray-700 dark:prose-p:text-gray-300
                            prose-a:text-purple-600 dark:prose-a:text-purple-400
                            prose-img:rounded-xl prose-img:shadow-lg">
                    {!! strip_tags($article->content, '<p><br><strong><em><ul><ol><li><h1><h2><h3><h4><h5><h6><a><img><blockquote><code><pre><table><thead><tbody><tr><th><td><hr><del><sup><sub><span><div>') !!}
                </div>
            </div>

            {{-- Complete Button --}}
            @if(!$progress || $progress->status !== 'completed')
            <div class="bg-gradient-to-r from-purple-500 to-pink-500 rounded-2xl p-6 text-center">
                <h3 class="text-xl font-bold text-white mb-2">เรียนจบบทเรียนนี้แล้ว?</h3>
                <p class="text-white/80 mb-4">กดปุ่มด้านล่างเพื่อบันทึกความก้าวหน้าและรับรางวัล</p>
                <button type="button"
                        @click="completeArticle"
                        :disabled="completing"
                        class="px-8 py-3 bg-white text-purple-600 font-bold rounded-xl hover:bg-gray-100 transition disabled:opacity-50">
                    <i class="fas fa-check-circle mr-2" :class="{ 'animate-spin': completing }"></i>
                    <span x-text="completing ? 'กำลังบันทึก...' : 'เรียนจบบทเรียน'"></span>
                </button>
            </div>
            @else
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-2xl p-6 text-center">
                <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-check-circle text-3xl text-green-600 dark:text-green-400"></i>
                </div>
                <h3 class="text-xl font-bold text-green-800 dark:text-green-400 mb-2">ยินดีด้วย! คุณเรียนจบบทเรียนนี้แล้ว</h3>
                <p class="text-green-700 dark:text-green-500">เรียนจบเมื่อ {{ $progress->completed_at ? $progress->completed_at->thaidate('j M Y เวลา H:i') : '-' }}</p>
            </div>
            @endif

            {{-- Navigation --}}
            <div class="flex items-center justify-between mt-6">
                @if($previousArticle)
                <a href="{{ route('user.academy.article', $previousArticle->slug) }}"
                   class="flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                    <i class="fas fa-arrow-left"></i>
                    <span class="hidden sm:inline">บทก่อนหน้า</span>
                </a>
                @else
                <div></div>
                @endif

                @if($nextArticle)
                <a href="{{ route('user.academy.article', $nextArticle->slug) }}"
                   class="flex items-center gap-2 px-4 py-2 bg-purple-600 text-white rounded-xl hover:bg-purple-700 transition">
                    <span class="hidden sm:inline">บทถัดไป</span>
                    <i class="fas fa-arrow-right"></i>
                </a>
                @else
                <a href="{{ route('user.academy.category', $article->category->slug) }}"
                   class="flex items-center gap-2 px-4 py-2 bg-purple-600 text-white rounded-xl hover:bg-purple-700 transition">
                    <span>กลับไปหมวดหมู่</span>
                    <i class="fas fa-arrow-right"></i>
                </a>
                @endif
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="lg:col-span-1 space-y-6">
            {{-- Related Articles --}}
            @if($relatedArticles->count() > 0)
            <div class="tp-card rounded-2xl p-6">
                <h3 class="font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-book text-purple-500"></i>
                    บทเรียนที่เกี่ยวข้อง
                </h3>
                <div class="space-y-3">
                    @foreach($relatedArticles as $related)
                    <a href="{{ route('user.academy.article', $related->slug) }}"
                       class="block p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        <h4 class="font-medium text-gray-900 dark:text-white text-sm line-clamp-2">{{ $related->title }}</h4>
                        <div class="flex items-center gap-2 mt-1 text-xs text-gray-500 dark:text-gray-400">
                            <span><i class="fas fa-clock mr-1"></i>{{ $related->formatted_duration ?? '10 นาที' }}</span>
                        </div>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Quick Actions --}}
            <div class="tp-card rounded-2xl p-6">
                <h3 class="font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-bolt text-yellow-500"></i>
                    ลิงก์ด่วน
                </h3>
                <div class="space-y-2">
                    <a href="{{ route('user.academy.index') }}"
                       class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        <i class="fas fa-home text-gray-500"></i>
                        <span class="text-gray-700 dark:text-gray-300 text-sm">หน้าแรกศูนย์การเรียนรู้</span>
                    </a>
                    <a href="{{ route('user.academy.my-progress') }}"
                       class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        <i class="fas fa-chart-line text-gray-500"></i>
                        <span class="text-gray-700 dark:text-gray-300 text-sm">ความก้าวหน้าของฉัน</span>
                    </a>
                    <a href="{{ route('user.academy.category', $article->category->slug) }}"
                       class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        <i class="fas fa-folder text-gray-500"></i>
                        <span class="text-gray-700 dark:text-gray-300 text-sm">{{ $article->category->name }}</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function articleReader() {
    return {
        progressPercent: {{ $progress->progress_percentage ?? 0 }},
        completing: false,
        updateInterval: null,

        init() {
            // ติดตามการ scroll เพื่ออัพเดท progress
            this.trackProgress();
        },

        trackProgress() {
            const content = document.querySelector('.prose');
            if (!content) return;

            let lastProgress = this.progressPercent;
            let startTime = Date.now();

            const updateProgress = () => {
                const rect = content.getBoundingClientRect();
                const contentHeight = content.scrollHeight;
                const viewportHeight = window.innerHeight;
                const scrolled = -rect.top + viewportHeight;
                const progress = Math.min(100, Math.max(0, Math.round((scrolled / contentHeight) * 100)));

                if (progress > this.progressPercent) {
                    this.progressPercent = progress;

                    // บันทึก progress ทุกๆ 10%
                    if (progress >= lastProgress + 10 || progress === 100) {
                        this.saveProgress(progress, Math.round((Date.now() - startTime) / 1000));
                        lastProgress = progress;
                    }
                }
            };

            window.addEventListener('scroll', updateProgress);
            updateProgress(); // Initial check
        },

        async saveProgress(progress, timeSpent) {
            try {
                await fetch('{{ route("user.academy.article.progress", $article->slug) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ progress, time_spent: timeSpent })
                });
            } catch (e) {
                console.error('Failed to save progress:', e);
            }
        },

        async completeArticle() {
            if (this.completing) return;
            this.completing = true;

            try {
                const response = await fetch('{{ route("user.academy.article.complete", $article->slug) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    // แสดง success message
                    alert(data.message || 'ยินดีด้วย! คุณเรียนจบบทเรียนนี้แล้ว');

                    // Reload หน้าเพื่อแสดงสถานะใหม่
                    window.location.reload();
                } else {
                    alert(data.message || 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง');
                }
            } catch (e) {
                console.error('Failed to complete article:', e);
                alert('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง');
            }

            this.completing = false;
        }
    };
}
</script>
@endpush
@endsection
