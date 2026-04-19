@extends('layouts.admin-v3')

@section('title', $project->name . ' - Video Automation')

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

    /* Progress Bar */
    .progress-bar-lg {
        height: 12px;
        border-radius: 6px;
        overflow: hidden;
        background: rgba(0, 0, 0, 0.1);
    }
    .dark .progress-bar-lg {
        background: rgba(255, 255, 255, 0.1);
    }
    .progress-fill {
        height: 100%;
        border-radius: 6px;
        background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        transition: width 0.5s ease;
    }

    /* Status Badge */
    .status-badge-lg {
        padding: 0.5rem 1.25rem;
        border-radius: 9999px;
        font-size: 0.875rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    .status-draft { background: rgba(156, 163, 175, 0.2); color: #6b7280; }
    .status-pending { background: rgba(251, 191, 36, 0.2); color: #f59e0b; }
    .status-generating_music { background: rgba(168, 85, 247, 0.2); color: #a855f7; }
    .status-generating_images { background: rgba(6, 182, 212, 0.2); color: #06b6d4; }
    .status-creating_video { background: rgba(16, 185, 129, 0.2); color: #10b981; }
    .status-publishing { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }
    .status-completed { background: rgba(16, 185, 129, 0.2); color: #10b981; }
    .status-failed { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
    .status-cancelled { background: rgba(107, 114, 128, 0.2); color: #6b7280; }

    /* Step Timeline */
    .timeline-step {
        position: relative;
        padding-left: 2.5rem;
        padding-bottom: 1.5rem;
    }
    .timeline-step::before {
        content: '';
        position: absolute;
        left: 0.75rem;
        top: 1.5rem;
        bottom: 0;
        width: 2px;
        background: rgba(156, 163, 175, 0.3);
    }
    .timeline-step:last-child::before {
        display: none;
    }
    .timeline-step .step-icon {
        position: absolute;
        left: 0;
        top: 0;
        width: 1.5rem;
        height: 1.5rem;
        border-radius: 9999px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Log Entry */
    .log-entry {
        padding: 0.75rem 1rem;
        border-radius: 0.5rem;
        font-family: monospace;
        font-size: 0.875rem;
    }
    .log-info { background: rgba(59, 130, 246, 0.1); border-left: 3px solid #3b82f6; }
    .log-warning { background: rgba(251, 191, 36, 0.1); border-left: 3px solid #f59e0b; }
    .log-error { background: rgba(239, 68, 68, 0.1); border-left: 3px solid #ef4444; }
    .log-debug { background: rgba(156, 163, 175, 0.1); border-left: 3px solid #9ca3af; }

    /* Auto refresh indicator */
    .pulse-dot {
        width: 8px;
        height: 8px;
        background: #10b981;
        border-radius: 50%;
        animation: pulse 2s infinite;
    }
    @keyframes pulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.5; transform: scale(1.5); }
    }
</style>
@endsection

@section('content')
<div class="container mx-auto px-4 py-8" x-data="projectShow()" x-init="init()">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <a href="{{ route('admin.video-automation.projects.index') }}"
                   class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    {{ $project->name }}
                </h1>
            </div>
            <div class="flex items-center gap-4">
                @php
                    $statusLabels = [
                        'draft' => 'แบบร่าง',
                        'pending' => 'รอดำเนินการ',
                        'generating_music' => 'กำลังสร้างเพลง',
                        'generating_images' => 'กำลังสร้างภาพ',
                        'creating_video' => 'กำลังสร้างวิดีโอ',
                        'publishing' => 'กำลังเผยแพร่',
                        'completed' => 'เสร็จสิ้น',
                        'failed' => 'ล้มเหลว',
                        'cancelled' => 'ยกเลิก',
                    ];
                @endphp
                <span class="status-badge-lg status-{{ $project->status }}">
                    @if(in_array($project->status, ['generating_music', 'generating_images', 'creating_video', 'publishing']))
                        <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    @endif
                    {{ $statusLabels[$project->status] ?? $project->status }}
                </span>
                @if(in_array($project->status, ['pending', 'generating_music', 'generating_images', 'creating_video', 'publishing']))
                    <span class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                        <span class="pulse-dot"></span>
                        รีเฟรชอัตโนมัติ
                    </span>
                @endif
            </div>
        </div>
        <div class="flex items-center gap-3">
            @if($project->status == 'draft')
                <form action="{{ route('admin.video-automation.projects.start', $project) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-6 py-3 bg-green-600 text-white rounded-xl hover:bg-green-700 transition font-semibold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        เริ่มดำเนินการ
                    </button>
                </form>
            @endif
            @if($project->status == 'failed')
                <form action="{{ route('admin.video-automation.projects.retry', $project) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-6 py-3 bg-orange-600 text-white rounded-xl hover:bg-orange-700 transition font-semibold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        ลองใหม่
                    </button>
                </form>
            @endif
            @if(in_array($project->status, ['pending', 'generating_music', 'generating_images', 'creating_video', 'publishing']))
                <form action="{{ route('admin.video-automation.projects.cancel', $project) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit"
                            onclick="return confirm('ต้องการยกเลิกโปรเจกต์นี้หรือไม่?')"
                            class="inline-flex items-center gap-2 px-6 py-3 bg-red-600 text-white rounded-xl hover:bg-red-700 transition font-semibold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        ยกเลิก
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- Progress Section --}}
    <div class="glass-card rounded-2xl p-6 mb-8">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">ความคืบหน้า</h2>
            <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ $project->progress }}%</span>
        </div>
        <div class="progress-bar-lg mb-6">
            <div class="progress-fill" style="width: {{ $project->progress }}%"></div>
        </div>

        {{-- Step Indicators --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @php
                $steps = [
                    ['key' => 'music', 'label' => 'สร้างเพลง', 'icon' => 'M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3', 'color' => 'purple', 'status' => 'generating_music'],
                    ['key' => 'images', 'label' => 'สร้างภาพ', 'icon' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z', 'color' => 'cyan', 'status' => 'generating_images'],
                    ['key' => 'video', 'label' => 'สร้างวิดีโอ', 'icon' => 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z', 'color' => 'green', 'status' => 'creating_video'],
                    ['key' => 'publish', 'label' => 'เผยแพร่', 'icon' => 'M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12', 'color' => 'orange', 'status' => 'publishing'],
                ];
                $stepOrder = ['draft', 'pending', 'generating_music', 'generating_images', 'creating_video', 'publishing', 'completed'];
                $currentIndex = array_search($project->status, $stepOrder);
            @endphp

            @foreach($steps as $index => $step)
                @php
                    $stepIndex = array_search($step['status'], $stepOrder);
                    $isCompleted = $currentIndex > $stepIndex || $project->status === 'completed';
                    $isActive = $project->status === $step['status'];
                    $isPending = $currentIndex < $stepIndex;
                @endphp
                <div class="text-center">
                    <div class="w-14 h-14 mx-auto rounded-full flex items-center justify-center mb-2
                        @if($isCompleted) bg-green-500
                        @elseif($isActive) bg-{{ $step['color'] }}-500 animate-pulse
                        @else bg-gray-200 dark:bg-gray-700
                        @endif">
                        @if($isCompleted)
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        @else
                            <svg class="w-7 h-7 @if($isActive) text-white @else text-gray-400 dark:text-gray-500 @endif" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $step['icon'] }}"/>
                            </svg>
                        @endif
                    </div>
                    <p class="text-sm font-medium @if($isActive || $isCompleted) text-gray-900 dark:text-white @else text-gray-400 @endif">
                        {{ $step['label'] }}
                    </p>
                </div>
            @endforeach
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-8">
            {{-- Output Files --}}
            @if($project->status === 'completed' || $project->music_path || $project->video_path)
                <div class="glass-card rounded-2xl p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ไฟล์ที่สร้างแล้ว</h2>

                    <div class="space-y-4">
                        @if($project->music_path)
                            <div class="flex items-center justify-between p-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-purple-500 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">เพลง</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ basename($project->music_path) }}</p>
                                    </div>
                                </div>
                                <a href="{{ Storage::url($project->music_path) }}"
                                   target="_blank"
                                   class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition text-sm">
                                    ดาวน์โหลด
                                </a>
                            </div>
                        @endif

                        @if($project->video_path)
                            <div class="flex items-center justify-between p-4 bg-green-50 dark:bg-green-900/20 rounded-xl">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-green-500 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">วิดีโอ</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ basename($project->video_path) }}</p>
                                    </div>
                                </div>
                                <a href="{{ Storage::url($project->video_path) }}"
                                   target="_blank"
                                   class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-sm">
                                    ดาวน์โหลด
                                </a>
                            </div>
                        @endif

                        @if($project->youtube_url)
                            <div class="flex items-center justify-between p-4 bg-red-50 dark:bg-red-900/20 rounded-xl">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-red-600 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">YouTube</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $project->youtube_url }}</p>
                                    </div>
                                </div>
                                <a href="{{ $project->youtube_url }}"
                                   target="_blank"
                                   class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition text-sm">
                                    เปิดดู
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Logs --}}
            <div class="glass-card rounded-2xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Logs</h2>
                    <button type="button"
                            @click="showAllLogs = !showAllLogs"
                            class="text-sm text-purple-600 hover:underline">
                        <span x-text="showAllLogs ? 'แสดงน้อยลง' : 'แสดงทั้งหมด'"></span>
                    </button>
                </div>

                <div class="space-y-2 max-h-96 overflow-y-auto">
                    @forelse($project->logs()->latest()->take(50)->get() as $log)
                        <div class="log-entry log-{{ $log->level }}">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <span class="text-gray-500 dark:text-gray-400">{{ $log->logged_at->format('H:i:s') }}</span>
                                    <span class="font-medium text-gray-900 dark:text-white ml-2">{{ $log->message }}</span>
                                </div>
                                @if($log->step)
                                    <span class="text-xs text-gray-400 ml-2">{{ $log->step }}</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-center text-gray-500 dark:text-gray-400 py-8">
                            ยังไม่มี log
                        </p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Project Info --}}
            <div class="glass-card rounded-2xl p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ข้อมูลโปรเจกต์</h2>

                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">Template</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">{{ $project->template?->name ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">แนวเพลง</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">{{ \App\Models\VideoAutoTemplate::GENRES[$project->template?->music_genre] ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">สไตล์ภาพ</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">{{ \App\Models\VideoAutoTemplate::IMAGE_STYLES[$project->template?->image_style] ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">สร้างเมื่อ</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">{{ $project->created_at->format('d/m/Y H:i') }}</dd>
                    </div>
                    @if($project->started_at)
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">เริ่มทำงาน</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">{{ $project->started_at->format('d/m/Y H:i') }}</dd>
                        </div>
                    @endif
                    @if($project->completed_at)
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">เสร็จสิ้น</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">{{ $project->completed_at->format('d/m/Y H:i') }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            {{-- Error Info (if failed) --}}
            @if($project->status === 'failed' && $project->error_message)
                <div class="glass-card rounded-2xl p-6 border-2 border-red-200 dark:border-red-800">
                    <h2 class="text-lg font-semibold text-red-600 dark:text-red-400 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        ข้อผิดพลาด
                    </h2>
                    <p class="text-sm text-red-600 dark:text-red-400 font-mono bg-red-50 dark:bg-red-900/20 p-3 rounded-lg">
                        {{ $project->error_message }}
                    </p>
                </div>
            @endif

            {{-- Quick Actions --}}
            <div class="glass-card rounded-2xl p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">การดำเนินการ</h2>

                <div class="space-y-3">
                    @if($project->template)
                        <a href="{{ route('admin.video-automation.templates.edit', $project->template) }}"
                           class="block w-full px-4 py-3 text-center bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                            ดู Template
                        </a>
                    @endif

                    <a href="{{ route('admin.video-automation.projects.create', ['template' => $project->template_id]) }}"
                       class="block w-full px-4 py-3 text-center bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400 rounded-lg hover:bg-purple-200 dark:hover:bg-purple-900/50 transition">
                        สร้างใหม่จาก Template เดียวกัน
                    </a>

                    @if(in_array($project->status, ['draft', 'failed', 'cancelled']))
                        <form action="{{ route('admin.video-automation.projects.destroy', $project) }}"
                              method="POST"
                              onsubmit="return confirm('ต้องการลบโปรเจกต์นี้หรือไม่?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="block w-full px-4 py-3 text-center bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 transition">
                                ลบโปรเจกต์
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function projectShow() {
    return {
        showAllLogs: false,
        refreshInterval: null,

        init() {
            // Auto refresh if project is processing
            const status = '{{ $project->status }}';
            const processingStatuses = ['pending', 'generating_music', 'generating_images', 'creating_video', 'publishing'];

            if (processingStatuses.includes(status)) {
                this.refreshInterval = setInterval(() => {
                    window.location.reload();
                }, 10000); // Refresh every 10 seconds
            }
        },

        destroy() {
            if (this.refreshInterval) {
                clearInterval(this.refreshInterval);
            }
        }
    }
}
</script>
@endsection
