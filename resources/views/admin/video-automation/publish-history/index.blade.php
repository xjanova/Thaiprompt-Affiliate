@extends('layouts.admin-v3')

@section('title', 'ประวัติการโพสต์ - Video Automation')

@section('styles')
<style>
    /* การ์ด Glassmorphism */
    .glass-card {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    .dark .glass-card {
        background: rgba(30, 41, 59, 0.8);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    /* Status Badge */
    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
    }
    .status-pending { background: rgba(251, 191, 36, 0.2); color: #f59e0b; }
    .status-publishing { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }
    .status-published { background: rgba(16, 185, 129, 0.2); color: #10b981; }
    .status-failed { background: rgba(239, 68, 68, 0.2); color: #ef4444; }

    /* Platform Badge */
    .platform-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 0.5rem;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .platform-youtube { background: rgba(255, 0, 0, 0.1); color: #ff0000; }
    .platform-facebook { background: rgba(59, 89, 152, 0.1); color: #3b5998; }
    .platform-instagram { background: rgba(225, 48, 108, 0.1); color: #e1306c; }
    .platform-tiktok { background: rgba(0, 0, 0, 0.1); color: #000000; }
    .dark .platform-tiktok { background: rgba(255, 255, 255, 0.1); color: #ffffff; }
    .platform-twitter { background: rgba(29, 161, 242, 0.1); color: #1da1f2; }
    .platform-threads { background: rgba(0, 0, 0, 0.1); color: #000000; }
    .dark .platform-threads { background: rgba(255, 255, 255, 0.1); color: #ffffff; }
    .platform-line_voom { background: rgba(0, 195, 0, 0.1); color: #00c300; }

    /* Thumbnail */
    .thumbnail-container {
        width: 80px;
        height: 45px;
        border-radius: 0.5rem;
        overflow: hidden;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    .thumbnail-container img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
</style>
@endsection

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <a href="{{ route('admin.video-automation.dashboard') }}"
                   class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    ประวัติการโพสต์
                </h1>
            </div>
            <p class="text-gray-600 dark:text-gray-400">
                ติดตามวิดีโอที่โพสต์ไปบนแพลตฟอร์มต่างๆ พร้อม Engagement
            </p>
        </div>
        <div class="flex items-center gap-3">
            <button type="button"
                    onclick="window.location.reload()"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                รีเฟรช
            </button>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
        <div class="glass-card rounded-xl p-4">
            <div class="text-center">
                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total'] ?? 0) }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">โพสต์ทั้งหมด</p>
            </div>
        </div>
        <div class="glass-card rounded-xl p-4">
            <div class="text-center">
                <p class="text-3xl font-bold text-green-600">{{ number_format($stats['published'] ?? 0) }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">โพสต์สำเร็จ</p>
            </div>
        </div>
        <div class="glass-card rounded-xl p-4">
            <div class="text-center">
                <p class="text-3xl font-bold text-red-600">{{ number_format($stats['failed'] ?? 0) }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">โพสต์ล้มเหลว</p>
            </div>
        </div>
        <div class="glass-card rounded-xl p-4">
            <div class="text-center">
                <p class="text-3xl font-bold text-blue-600">{{ number_format($stats['total_views'] ?? 0) }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Views ทั้งหมด</p>
            </div>
        </div>
        <div class="glass-card rounded-xl p-4">
            <div class="text-center">
                <p class="text-3xl font-bold text-pink-600">{{ number_format($stats['total_likes'] ?? 0) }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Likes ทั้งหมด</p>
            </div>
        </div>
    </div>

    {{-- Platform Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3 mb-6">
        @foreach($platforms as $key => $label)
            @php
                $pStats = $platformStats[$key] ?? null;
                $platformIcons = [
                    'youtube' => 'M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z',
                    'facebook' => 'M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z',
                    'instagram' => 'M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z',
                    'tiktok' => 'M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z',
                    'twitter' => 'M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z',
                    'threads' => 'M12.186 24h-.007c-3.581-.024-6.334-1.205-8.184-3.509C2.35 18.44 1.5 15.586 1.472 12.01v-.017c.03-3.579.879-6.43 2.525-8.482C5.845 1.205 8.6.024 12.18 0h.014c2.746.02 5.043.725 6.826 2.098 1.677 1.29 2.858 3.13 3.509 5.467l-2.04.569c-1.104-3.96-3.898-5.984-8.304-6.015-2.91.022-5.11.936-6.54 2.717C4.307 6.504 3.616 8.914 3.589 12c.027 3.086.718 5.496 2.057 7.164 1.43 1.783 3.631 2.698 6.54 2.717 2.623-.02 4.358-.631 5.8-2.045 1.647-1.613 1.618-3.593 1.09-4.798-.31-.71-.873-1.3-1.634-1.75-.192 1.352-.622 2.446-1.284 3.272-.886 1.102-2.14 1.704-3.73 1.79-1.202.065-2.361-.218-3.259-.801-1.063-.689-1.685-1.74-1.752-2.96-.065-1.182.408-2.256 1.33-3.022.812-.674 1.894-1.036 3.21-1.076 1.172-.035 2.218.16 3.136.58l.003-.004c-.007-.616-.074-1.174-.205-1.672-.233-.89-.628-1.558-1.12-1.948-.592-.47-1.373-.71-2.32-.71H11c-.93 0-1.706.182-2.31.544-.708.424-1.091 1.048-1.14 1.855l-2.114-.15c.088-1.353.68-2.46 1.763-3.285C8.24 4.96 9.508 4.498 11 4.457h.192c1.373.04 2.558.418 3.521 1.127.936.69 1.611 1.672 2.006 2.92l.025.08c.069.211.128.436.178.68.168-.093.338-.18.51-.26 1.17-.54 2.49-.715 3.67-.21 1.27.546 2.14 1.573 2.59 3.052.29.96.302 1.963.035 2.95-.538 1.992-1.922 3.638-4.008 4.76-1.74.936-3.907 1.413-6.461 1.42h-.03zm-1.18-8.86c-1.028.03-1.814.29-2.335.773-.521.483-.68 1.024-.655 1.51.028.545.282 1.023.76 1.42.493.41 1.17.614 2.014.572 1.087-.055 1.94-.443 2.533-1.155.477-.572.786-1.344.923-2.31-.798-.266-1.65-.371-2.559-.39l-.043-.002c-.214-.013-.43-.018-.638-.018z',
                    'line_voom' => 'M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314',
                ];
            @endphp
            <div class="glass-card rounded-lg p-3 text-center">
                <div class="w-8 h-8 mx-auto mb-2 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                        <path d="{{ $platformIcons[$key] ?? '' }}"/>
                    </svg>
                </div>
                <p class="font-bold text-gray-900 dark:text-white">{{ number_format($pStats['count'] ?? 0) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $label }}</p>
            </div>
        @endforeach
    </div>

    {{-- Filters --}}
    <div class="glass-card rounded-2xl p-4 mb-6">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            {{-- Search --}}
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ค้นหา</label>
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="ชื่อวิดีโอ, ชื่อเพลง..."
                       class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
            </div>

            {{-- Platform Filter --}}
            <div class="w-40">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">แพลตฟอร์ม</label>
                <select name="platform"
                        class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    <option value="">ทั้งหมด</option>
                    @foreach($platforms as $key => $label)
                        <option value="{{ $key }}" {{ request('platform') == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Status Filter --}}
            <div class="w-40">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สถานะ</label>
                <select name="status"
                        class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    <option value="">ทั้งหมด</option>
                    @foreach($statuses as $key => $label)
                        <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Date Filter --}}
            <div class="w-40">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ช่วงเวลา</label>
                <select name="date"
                        class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    <option value="">ทั้งหมด</option>
                    <option value="today" {{ request('date') == 'today' ? 'selected' : '' }}>วันนี้</option>
                    <option value="week" {{ request('date') == 'week' ? 'selected' : '' }}>7 วันที่ผ่านมา</option>
                    <option value="month" {{ request('date') == 'month' ? 'selected' : '' }}>30 วันที่ผ่านมา</option>
                </select>
            </div>

            {{-- Buttons --}}
            <div class="flex gap-2">
                <button type="submit"
                        class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">
                    กรอง
                </button>
                <a href="{{ route('admin.video-automation.publish-history') }}"
                   class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                    ล้าง
                </a>
            </div>
        </form>
    </div>

    {{-- History List --}}
    @if($histories->count() > 0)
        <div class="glass-card rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Thumbnail
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                ข้อมูล
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                แพลตฟอร์ม
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Engagement
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                โพสต์เมื่อ
                            </th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                จัดการ
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($histories as $history)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                                <td class="px-6 py-4">
                                    <div class="thumbnail-container">
                                        @if($history->thumbnail_path)
                                            <img src="{{ $history->thumbnail_url }}" alt="{{ $history->title }}" loading="lazy">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center text-white text-xs">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                                </svg>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="max-w-xs">
                                        <p class="font-semibold text-gray-900 dark:text-white truncate" title="{{ $history->title }}">
                                            {{ Str::limit($history->title, 40) }}
                                        </p>
                                        @if($history->music_title)
                                            <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                                                🎵 {{ $history->music_title }}
                                                @if($history->music_artist)
                                                    - {{ $history->music_artist }}
                                                @endif
                                            </p>
                                        @endif
                                        <span class="status-badge status-{{ $history->status }} mt-1">
                                            {{ $statuses[$history->status] ?? $history->status }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="platform-badge platform-{{ $history->platform }}">
                                        {{ $platforms[$history->platform] ?? $history->platform }}
                                    </span>
                                    @if($history->platform_url)
                                        <a href="{{ $history->platform_url }}"
                                           target="_blank"
                                           class="block text-xs text-blue-600 hover:underline mt-1">
                                            ดูโพสต์ ↗
                                        </a>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm space-y-1">
                                        <p class="flex items-center gap-2">
                                            <span class="text-gray-500 dark:text-gray-400">👁️</span>
                                            <span class="font-medium text-gray-900 dark:text-white">{{ number_format($history->views ?? 0) }}</span>
                                        </p>
                                        <p class="flex items-center gap-2">
                                            <span class="text-gray-500 dark:text-gray-400">❤️</span>
                                            <span class="font-medium text-gray-900 dark:text-white">{{ number_format($history->likes ?? 0) }}</span>
                                        </p>
                                        <p class="flex items-center gap-2">
                                            <span class="text-gray-500 dark:text-gray-400">💬</span>
                                            <span class="font-medium text-gray-900 dark:text-white">{{ number_format($history->comments ?? 0) }}</span>
                                        </p>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm">
                                        @if($history->published_at)
                                            <p class="text-gray-900 dark:text-white">{{ $history->published_at->format('d/m/Y H:i') }}</p>
                                            <p class="text-gray-500 dark:text-gray-400">{{ $history->published_at->diffForHumans() }}</p>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                        @if($history->source_deleted)
                                            <span class="text-xs text-orange-500" title="ไฟล์ต้นฉบับถูกลบแล้ว">🗑️ ลบไฟล์แล้ว</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.video-automation.publish-history.show', $history) }}"
                                           class="p-2 text-blue-600 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded-lg transition"
                                           title="ดูรายละเอียด">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </a>
                                        @if(!$history->source_deleted)
                                            <button type="button"
                                                    onclick="deleteSourceFiles({{ $history->id }})"
                                                    class="p-2 text-orange-600 hover:bg-orange-100 dark:hover:bg-orange-900/30 rounded-lg transition"
                                                    title="ลบไฟล์ต้นฉบับ">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                                </svg>
                                            </button>
                                        @endif
                                        <button type="button"
                                                onclick="deleteHistory({{ $history->id }})"
                                                class="p-2 text-red-600 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition"
                                                title="ลบประวัติ">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pagination --}}
        <div class="mt-6 flex justify-center">
            {{ $histories->links() }}
        </div>
    @else
        {{-- Empty State --}}
        <div class="glass-card rounded-2xl p-12 text-center">
            <div class="w-24 h-24 mx-auto mb-6 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center">
                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                ยังไม่มีประวัติการโพสต์
            </h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">
                สร้างโปรเจกต์แล้วเผยแพร่เพื่อดูประวัติที่นี่
            </p>
            <a href="{{ route('admin.video-automation.projects.create') }}"
               class="inline-flex items-center gap-2 px-6 py-3 bg-purple-600 text-white rounded-xl hover:bg-purple-700 transition font-semibold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                สร้างโปรเจกต์
            </a>
        </div>
    @endif
</div>

@push('scripts')
<script>
/**
 * ลบไฟล์ต้นฉบับของการโพสต์
 *
 * @param {number} id
 */
async function deleteSourceFiles(id) {
    if (!confirm('ต้องการลบไฟล์ต้นฉบับ (เพลง, รูปภาพ, วิดีโอ) หรือไม่?\n\nหลังจากลบจะไม่สามารถกู้คืนได้')) {
        return;
    }

    try {
        const response = await fetch(`{{ url('admin/video-automation/publish-history') }}/${id}/source-files`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            }
        });

        const result = await response.json();

        if (result.success) {
            alert('ลบไฟล์ต้นฉบับเรียบร้อยแล้ว');
            window.location.reload();
        } else {
            alert(result.message || 'เกิดข้อผิดพลาดในการลบไฟล์');
        }
    } catch (error) {
        alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
        console.error(error);
    }
}

/**
 * ลบประวัติการโพสต์
 *
 * @param {number} id
 */
async function deleteHistory(id) {
    if (!confirm('ต้องการลบประวัติการโพสต์นี้หรือไม่?')) {
        return;
    }

    try {
        const response = await fetch(`{{ url('admin/video-automation/publish-history') }}/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            }
        });

        const result = await response.json();

        if (result.success) {
            alert('ลบประวัติเรียบร้อยแล้ว');
            window.location.reload();
        } else {
            alert(result.message || 'เกิดข้อผิดพลาดในการลบ');
        }
    } catch (error) {
        alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
        console.error(error);
    }
}
</script>
@endpush
@endsection
