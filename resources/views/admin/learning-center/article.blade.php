{{-- 📚 Academy Knowledge - หน้าบทเรียน (V3 UI) --}}
@extends('layouts.admin-v3')

@section('title', $article->title . ' - Academy Knowledge')

@section('content')
@php
    $categoryColor = $article->category->color ?? '#6366F1';
    $progressPercent = $progress ? $progress->progress_percentage : 0;
    $status = $progress ? $progress->status : 'not_started';
    $timeSpent = $progress ? $progress->time_spent : 0;

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
    x-data="{
        progress: {{ $progressPercent }},
        timeSpent: {{ $timeSpent }},
        status: '{{ $status }}',
        isCompleted: {{ $status === 'completed' ? 'true' : 'false' }},
        showCompleteModal: false,
        startTime: Date.now(),
        timerInterval: null,

        // เริ่มจับเวลา
        startTimer() {
            this.timerInterval = setInterval(() => {
                this.timeSpent++;
            }, 1000);
        },

        // หยุดจับเวลา
        stopTimer() {
            if (this.timerInterval) {
                clearInterval(this.timerInterval);
            }
        },

        // อัพเดท Progress
        async updateProgress(percent) {
            this.progress = percent;

            try {
                const response = await fetch('{{ route('admin.learning-center.article.progress', $article->slug) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify({
                        progress: percent,
                        time_spent: this.timeSpent
                    })
                });

                if (!response.ok) {
                    console.error('Failed to update progress');
                }
            } catch (error) {
                console.error('Error updating progress:', error);
            }
        },

        // กดเรียนจบ
        async markComplete() {
            try {
                const response = await fetch('{{ route('admin.learning-center.article.complete', $article->slug) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.isCompleted = true;
                    this.status = 'completed';
                    this.progress = 100;
                    this.showCompleteModal = true;
                }
            } catch (error) {
                console.error('Error marking complete:', error);
            }
        },

        // Format เวลา
        formatTime(seconds) {
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const secs = seconds % 60;

            if (hours > 0) {
                return `${hours}:${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
            }
            return `${minutes}:${String(secs).padStart(2, '0')}`;
        },

        init() {
            this.startTimer();

            // บันทึก progress เมื่อออกจากหน้า
            window.addEventListener('beforeunload', () => {
                this.stopTimer();
                if (!this.isCompleted) {
                    navigator.sendBeacon(
                        '{{ route('admin.learning-center.article.progress', $article->slug) }}',
                        JSON.stringify({
                            progress: this.progress,
                            time_spent: this.timeSpent
                        })
                    );
                }
            });
        }
    }"
    x-init="init()"
    class="min-h-screen"
>
    {{-- Top Progress Bar --}}
    <div class="fixed top-0 left-0 right-0 h-1 bg-gray-200 dark:bg-gray-700 z-50">
        <div
            class="h-full bg-gradient-to-r from-indigo-500 to-purple-500 transition-all duration-500"
            :style="`width: ${progress}%`"
        ></div>
    </div>

    <div class="max-w-5xl mx-auto">
        {{-- Breadcrumb --}}
        <nav class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 mb-6">
            <a href="{{ route('admin.learning-center.index') }}" class="hover:text-indigo-600 transition">
                🎓 Academy Knowledge
            </a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <a href="{{ route('admin.learning-center.category', $article->category->slug) }}" class="hover:text-indigo-600 transition">
                {{ $article->category->name }}
            </a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-gray-900 dark:text-white truncate max-w-xs">{{ $article->title }}</span>
        </nav>

        {{-- Article Header --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden mb-8">
            {{-- Video/Thumbnail Area --}}
            @if($article->video_url)
            <div class="aspect-video bg-gray-900">
                <iframe
                    src="{{ $article->video_url }}"
                    class="w-full h-full"
                    frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen
                ></iframe>
            </div>
            @elseif($article->thumbnail)
            <div class="aspect-video">
                <img src="{{ $article->thumbnail }}" alt="{{ $article->title }}" class="w-full h-full object-cover">
            </div>
            @else
            <div class="aspect-video flex items-center justify-center"
                 style="background: linear-gradient(135deg, {{ $categoryColor }} 0%, {{ $categoryColor }}99 100%);">
                <span class="text-8xl">{{ $article->category->icon ?? '📚' }}</span>
            </div>
            @endif

            {{-- Header Content --}}
            <div class="p-6 md:p-8">
                {{-- Category & Meta --}}
                <div class="flex flex-wrap items-center gap-3 mb-4">
                    <span class="px-3 py-1 rounded-full text-sm font-medium text-white"
                          style="background: {{ $categoryColor }};">
                        {{ $article->category->icon }} {{ $article->category->name }}
                    </span>
                    <span class="px-2 py-1 rounded-full text-xs font-medium {{ $difficultyColors[$article->difficulty] ?? 'bg-gray-100 text-gray-700' }}">
                        {{ $difficultyLabels[$article->difficulty] ?? $article->difficulty }}
                    </span>
                    <span class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        {{ $article->formatted_duration }}
                    </span>
                    <span class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        {{ number_format($article->views) }} ครั้ง
                    </span>
                </div>

                {{-- Title --}}
                <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-4">
                    {{ $article->title }}
                </h1>

                {{-- Excerpt --}}
                @if($article->excerpt)
                <p class="text-lg text-gray-600 dark:text-gray-400 mb-6">
                    {{ $article->excerpt }}
                </p>
                @endif

                {{-- Progress Card --}}
                <div class="bg-gray-100 dark:bg-gray-700/50 rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <span class="font-medium text-gray-700 dark:text-gray-300">ความคืบหน้า</span>
                        <div class="flex items-center gap-4">
                            <span class="text-sm text-gray-500 dark:text-gray-400" x-text="`เวลาเรียน: ${formatTime(timeSpent)}`"></span>
                            <span class="font-bold text-indigo-600 dark:text-indigo-400" x-text="`${progress}%`"></span>
                        </div>
                    </div>
                    <div class="w-full h-3 bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden">
                        <div
                            class="h-full bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full transition-all duration-500"
                            :style="`width: ${progress}%`"
                        ></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Article Content --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 md:p-8 mb-8">
            <div class="prose prose-lg max-w-none dark:prose-invert
                        prose-headings:font-bold prose-headings:text-gray-900 dark:prose-headings:text-white
                        prose-p:text-gray-700 dark:prose-p:text-gray-300
                        prose-a:text-indigo-600 dark:prose-a:text-indigo-400
                        prose-code:bg-gray-100 dark:prose-code:bg-gray-700 prose-code:px-1 prose-code:rounded
                        prose-pre:bg-gray-900 prose-pre:text-gray-100
                        prose-img:rounded-xl prose-img:shadow-lg">
                {!! strip_tags($article->content, '<p><br><strong><em><ul><ol><li><h1><h2><h3><h4><h5><h6><a><img><blockquote><code><pre><table><thead><tbody><tr><th><td><hr><del><sup><sub><span><div>') !!}
            </div>
        </div>

        {{-- Tags --}}
        @if($article->tags && count($article->tags) > 0)
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 mb-8">
            <h3 class="font-bold text-gray-900 dark:text-white mb-4">แท็ก</h3>
            <div class="flex flex-wrap gap-2">
                @foreach($article->tags as $tag)
                <span class="px-3 py-1 bg-gray-100 dark:bg-gray-700 rounded-full text-sm text-gray-700 dark:text-gray-300">
                    #{{ $tag }}
                </span>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Complete Button --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 mb-8">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                <div>
                    <h3 class="font-bold text-gray-900 dark:text-white mb-1">
                        <template x-if="isCompleted">
                            <span class="flex items-center gap-2">
                                <span class="text-green-500">✓</span> คุณเรียนจบบทเรียนนี้แล้ว!
                            </span>
                        </template>
                        <template x-if="!isCompleted">
                            <span>เรียนจบบทเรียนแล้วหรือยัง?</span>
                        </template>
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm" x-show="!isCompleted">
                        กดปุ่มด้านล่างเพื่อยืนยันว่าคุณเรียนจบบทเรียนนี้แล้ว
                    </p>
                </div>

                <button
                    x-show="!isCompleted"
                    @click="markComplete()"
                    class="px-8 py-3 bg-gradient-to-r from-green-500 to-emerald-500 hover:from-green-600 hover:to-emerald-600 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all transform hover:scale-105"
                >
                    ✓ เรียนจบบทเรียนนี้
                </button>

                <a
                    x-show="isCompleted"
                    href="{{ route('admin.certificates.generate', $article->id) }}"
                    class="px-8 py-3 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all transform hover:scale-105"
                >
                    🏆 รับใบประกาศนียบัตร
                </a>
            </div>
        </div>

        {{-- Related Articles --}}
        @if($relatedArticles && $relatedArticles->count() > 0)
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 mb-8">
            <h3 class="font-bold text-gray-900 dark:text-white mb-4">บทเรียนที่เกี่ยวข้อง</h3>
            <div class="grid md:grid-cols-3 gap-4">
                @foreach($relatedArticles as $related)
                <a href="{{ route('admin.learning-center.article', $related->slug) }}"
                   class="group bg-gray-100 dark:bg-gray-700/50 rounded-xl p-4 hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                    <h4 class="font-semibold text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition mb-2 line-clamp-2">
                        {{ $related->title }}
                    </h4>
                    <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        {{ $related->formatted_duration }}
                    </div>
                </a>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Navigation --}}
        <div class="flex items-center justify-between mb-8">
            <a href="{{ route('admin.learning-center.category', $article->category->slug) }}"
               class="inline-flex items-center gap-2 px-6 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 font-semibold rounded-xl transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                กลับหน้าหมวดหมู่
            </a>
        </div>
    </div>

    {{-- Complete Modal --}}
    <div
        x-show="showCompleteModal"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
        @click.self="showCompleteModal = false"
        style="display: none;"
    >
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-8 max-w-md w-full mx-4 text-center">
            <div class="text-6xl mb-4">🎉</div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                ยินดีด้วย!
            </h2>
            <p class="text-gray-600 dark:text-gray-400 mb-6">
                คุณเรียนจบบทเรียน "{{ $article->title }}" เรียบร้อยแล้ว
            </p>
            <div class="flex flex-col gap-3">
                <a href="{{ route('admin.certificates.generate', $article->id) }}"
                   class="px-6 py-3 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white font-bold rounded-xl transition">
                    🏆 รับใบประกาศนียบัตร
                </a>
                <button
                    @click="showCompleteModal = false"
                    class="px-6 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 font-semibold rounded-xl transition"
                >
                    ปิด
                </button>
            </div>
        </div>
    </div>
</div>

@endsection
