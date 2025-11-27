@extends('layouts.admin')

@section('title', $pageTitle ?? 'รายละเอียดการโพสต์')

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
        padding: 0.5rem 1rem;
        border-radius: 9999px;
        font-size: 0.875rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    .status-pending { background: rgba(251, 191, 36, 0.2); color: #f59e0b; }
    .status-publishing { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }
    .status-published { background: rgba(16, 185, 129, 0.2); color: #10b981; }
    .status-failed { background: rgba(239, 68, 68, 0.2); color: #ef4444; }

    /* Thumbnail */
    .thumbnail-container {
        aspect-ratio: 16/9;
        border-radius: 0.75rem;
        overflow: hidden;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    .thumbnail-container img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* Engagement Card */
    .engagement-card {
        text-align: center;
        padding: 1rem;
        border-radius: 0.75rem;
    }
</style>
@endsection

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <a href="{{ route('admin.video-automation.publish-history') }}"
                   class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    รายละเอียดการโพสต์
                </h1>
            </div>
            <p class="text-gray-600 dark:text-gray-400">
                #{{ $history->id }} - โพสต์เมื่อ {{ $history->published_at ? $history->published_at->format('d/m/Y H:i') : 'ยังไม่ได้โพสต์' }}
            </p>
        </div>
        <div class="flex items-center gap-3">
            @if($history->platform_url)
                <a href="{{ $history->platform_url }}"
                   target="_blank"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                    ดูโพสต์
                </a>
            @endif
            @if(!$history->source_deleted)
                <button type="button"
                        onclick="deleteSourceFiles()"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                    </svg>
                    ลบไฟล์ต้นฉบับ
                </button>
            @endif
            <button type="button"
                    onclick="deleteHistory()"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                ลบประวัติ
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Thumbnail & Video Info --}}
            <div class="glass-card rounded-2xl overflow-hidden">
                <div class="thumbnail-container">
                    @if($history->thumbnail_path)
                        <img src="{{ $history->thumbnail_url }}" alt="{{ $history->title }}" loading="lazy">
                    @else
                        <div class="w-full h-full flex items-center justify-center text-white">
                            <svg class="w-24 h-24 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    @endif
                </div>
                <div class="p-6">
                    <div class="flex items-start justify-between gap-4 mb-4">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ $history->title }}
                        </h2>
                        @php
                            $statuses = \App\Models\VideoAutoPublishHistory::STATUSES;
                        @endphp
                        <span class="status-badge status-{{ $history->status }}">
                            @if($history->status === 'published')
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            @elseif($history->status === 'failed')
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                            @endif
                            {{ $statuses[$history->status] ?? $history->status }}
                        </span>
                    </div>

                    @if($history->description)
                        <p class="text-gray-600 dark:text-gray-400 mb-4 whitespace-pre-wrap">{{ $history->description }}</p>
                    @endif

                    @if($history->tags && is_array($history->tags) && count($history->tags) > 0)
                        <div class="flex flex-wrap gap-2">
                            @foreach($history->tags as $tag)
                                <span class="px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full text-sm">
                                    #{{ $tag }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Music Info --}}
            @if($history->music_title)
                <div class="glass-card rounded-2xl p-6">
                    <h3 class="font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
                        </svg>
                        ข้อมูลเพลง
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">ชื่อเพลง</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $history->music_title }}</p>
                        </div>
                        @if($history->music_artist)
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">ศิลปิน</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $history->music_artist }}</p>
                            </div>
                        @endif
                        @if($history->music_genre)
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">แนวเพลง</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $history->music_genre }}</p>
                            </div>
                        @endif
                        @if($history->music_duration)
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">ความยาว</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ gmdate('i:s', $history->music_duration) }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Error Message --}}
            @if($history->status === 'failed' && $history->error_message)
                <div class="glass-card rounded-2xl p-6 bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800">
                    <h3 class="font-bold text-red-700 dark:text-red-400 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        ข้อผิดพลาด
                    </h3>
                    <pre class="text-sm text-red-700 dark:text-red-400 whitespace-pre-wrap bg-red-100 dark:bg-red-900/30 p-4 rounded-lg">{{ $history->error_message }}</pre>
                </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Engagement Stats --}}
            <div class="glass-card rounded-2xl p-6">
                <h3 class="font-bold text-gray-900 dark:text-white mb-4">Engagement</h3>
                <div class="grid grid-cols-2 gap-3">
                    <div class="engagement-card bg-blue-50 dark:bg-blue-900/20">
                        <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($history->views ?? 0) }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">👁️ Views</p>
                    </div>
                    <div class="engagement-card bg-pink-50 dark:bg-pink-900/20">
                        <p class="text-2xl font-bold text-pink-600 dark:text-pink-400">{{ number_format($history->likes ?? 0) }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">❤️ Likes</p>
                    </div>
                    <div class="engagement-card bg-green-50 dark:bg-green-900/20">
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($history->comments ?? 0) }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">💬 Comments</p>
                    </div>
                    <div class="engagement-card bg-purple-50 dark:bg-purple-900/20">
                        <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($history->shares ?? 0) }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">🔄 Shares</p>
                    </div>
                </div>

                @if($history->engagement_updated_at)
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-4 text-center">
                        อัพเดทล่าสุด: {{ $history->engagement_updated_at->format('d/m/Y H:i') }}
                    </p>
                @endif
            </div>

            {{-- Platform Info --}}
            <div class="glass-card rounded-2xl p-6">
                <h3 class="font-bold text-gray-900 dark:text-white mb-4">แพลตฟอร์ม</h3>
                @php
                    $platforms = \App\Models\VideoAutoPublishHistory::PLATFORMS;
                @endphp
                <div class="space-y-3">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">แพลตฟอร์ม</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $platforms[$history->platform] ?? $history->platform }}</p>
                    </div>
                    @if($history->platform_post_id)
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Post ID</p>
                            <p class="font-mono text-sm text-gray-900 dark:text-white">{{ $history->platform_post_id }}</p>
                        </div>
                    @endif
                    @if($history->platform_url)
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">URL</p>
                            <a href="{{ $history->platform_url }}" target="_blank" class="text-blue-600 hover:underline break-all">
                                {{ Str::limit($history->platform_url, 40) }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Related Info --}}
            <div class="glass-card rounded-2xl p-6">
                <h3 class="font-bold text-gray-900 dark:text-white mb-4">ข้อมูลที่เกี่ยวข้อง</h3>
                <div class="space-y-3 text-sm">
                    @if($history->project)
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">โปรเจกต์</span>
                            <a href="{{ route('admin.video-automation.projects.show', $history->project_id) }}"
                               class="text-blue-600 hover:underline">
                                {{ Str::limit($history->project->name, 20) }}
                            </a>
                        </div>
                    @endif
                    @if($history->schedule)
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Schedule</span>
                            <span class="text-gray-900 dark:text-white">{{ $history->schedule->name }}</span>
                        </div>
                    @endif
                    @if($history->publishedBy)
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">โพสต์โดย</span>
                            <span class="text-gray-900 dark:text-white">{{ $history->publishedBy->name }}</span>
                        </div>
                    @endif
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">สร้างเมื่อ</span>
                        <span class="text-gray-900 dark:text-white">{{ $history->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    @if($history->published_at)
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">โพสต์เมื่อ</span>
                            <span class="text-gray-900 dark:text-white">{{ $history->published_at->format('d/m/Y H:i') }}</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- File Status --}}
            <div class="glass-card rounded-2xl p-6">
                <h3 class="font-bold text-gray-900 dark:text-white mb-4">สถานะไฟล์</h3>
                <div class="space-y-3">
                    @if($history->source_deleted)
                        <div class="flex items-center gap-3 p-3 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
                            <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                            </svg>
                            <div>
                                <p class="font-medium text-orange-700 dark:text-orange-400">ลบไฟล์ต้นฉบับแล้ว</p>
                                @if($history->source_deleted_at)
                                    <p class="text-sm text-orange-600 dark:text-orange-500">{{ $history->source_deleted_at->format('d/m/Y H:i') }}</p>
                                @endif
                            </div>
                        </div>
                    @else
                        <div class="flex items-center gap-3 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z"/>
                            </svg>
                            <div>
                                <p class="font-medium text-green-700 dark:text-green-400">ไฟล์ต้นฉบับยังอยู่</p>
                                <p class="text-sm text-green-600 dark:text-green-500">พร้อมลบเมื่อต้องการ</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
/**
 * ลบไฟล์ต้นฉบับของการโพสต์
 */
async function deleteSourceFiles() {
    if (!confirm('ต้องการลบไฟล์ต้นฉบับ (เพลง, รูปภาพ, วิดีโอ) หรือไม่?\n\nหลังจากลบจะไม่สามารถกู้คืนได้')) {
        return;
    }

    try {
        const response = await fetch(`{{ route('admin.video-automation.publish-history.delete-source', $history) }}`, {
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
 */
async function deleteHistory() {
    if (!confirm('ต้องการลบประวัติการโพสต์นี้หรือไม่?')) {
        return;
    }

    try {
        const response = await fetch(`{{ route('admin.video-automation.publish-history.delete', $history) }}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            }
        });

        const result = await response.json();

        if (result.success) {
            alert('ลบประวัติเรียบร้อยแล้ว');
            window.location.href = '{{ route('admin.video-automation.publish-history') }}';
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
