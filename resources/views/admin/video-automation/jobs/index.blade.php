@extends('layouts.admin-v3')

@section('title', 'Job Queue - Video Automation')

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
    .status-running { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }
    .status-completed { background: rgba(16, 185, 129, 0.2); color: #10b981; }
    .status-failed { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
    .status-cancelled { background: rgba(107, 114, 128, 0.2); color: #6b7280; }

    /* Progress Bar */
    .progress-bar {
        height: 6px;
        border-radius: 3px;
        overflow: hidden;
        background: rgba(0, 0, 0, 0.1);
    }
    .dark .progress-bar {
        background: rgba(255, 255, 255, 0.1);
    }
    .progress-fill {
        height: 100%;
        border-radius: 3px;
        background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        transition: width 0.3s ease;
    }
</style>
@endsection

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <a href="{{ route('admin.video-automation.index') }}"
                   class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    Job Queue
                </h1>
            </div>
            <p class="text-gray-600 dark:text-gray-400">
                ติดตามและจัดการงานที่กำลังประมวลผล
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
                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] ?? 0 }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">ทั้งหมด</p>
            </div>
        </div>
        <div class="glass-card rounded-xl p-4">
            <div class="text-center">
                <p class="text-3xl font-bold text-yellow-600">{{ $stats['pending'] ?? 0 }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">รอดำเนินการ</p>
            </div>
        </div>
        <div class="glass-card rounded-xl p-4">
            <div class="text-center">
                <p class="text-3xl font-bold text-blue-600">{{ $stats['running'] ?? 0 }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">กำลังทำงาน</p>
            </div>
        </div>
        <div class="glass-card rounded-xl p-4">
            <div class="text-center">
                <p class="text-3xl font-bold text-green-600">{{ $stats['completed'] ?? 0 }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">เสร็จสิ้น</p>
            </div>
        </div>
        <div class="glass-card rounded-xl p-4">
            <div class="text-center">
                <p class="text-3xl font-bold text-red-600">{{ $stats['failed'] ?? 0 }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">ล้มเหลว</p>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="glass-card rounded-2xl p-4 mb-6">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            {{-- Status Filter --}}
            <div class="w-48">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สถานะ</label>
                <select name="status"
                        class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    <option value="">ทั้งหมด</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>รอดำเนินการ</option>
                    <option value="running" {{ request('status') == 'running' ? 'selected' : '' }}>กำลังทำงาน</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>เสร็จสิ้น</option>
                    <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>ล้มเหลว</option>
                </select>
            </div>

            {{-- Job Type Filter --}}
            <div class="w-48">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ประเภทงาน</label>
                <select name="type"
                        class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    <option value="">ทั้งหมด</option>
                    <option value="generate_music" {{ request('type') == 'generate_music' ? 'selected' : '' }}>สร้างเพลง</option>
                    <option value="generate_images" {{ request('type') == 'generate_images' ? 'selected' : '' }}>สร้างภาพ</option>
                    <option value="create_video" {{ request('type') == 'create_video' ? 'selected' : '' }}>สร้างวิดีโอ</option>
                    <option value="publish" {{ request('type') == 'publish' ? 'selected' : '' }}>เผยแพร่</option>
                </select>
            </div>

            {{-- Buttons --}}
            <div class="flex gap-2">
                <button type="submit"
                        class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">
                    กรอง
                </button>
                <a href="{{ route('admin.video-automation.jobs.index') }}"
                   class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                    ล้าง
                </a>
            </div>
        </form>
    </div>

    {{-- Jobs List --}}
    @if($jobs->count() > 0)
        <div class="glass-card rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Job
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                โปรเจกต์
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                สถานะ
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Progress
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                เวลา
                            </th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                จัดการ
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($jobs as $job)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                                <td class="px-6 py-4">
                                    <div>
                                        @php
                                            $typeLabels = [
                                                'generate_music' => 'สร้างเพลง',
                                                'generate_images' => 'สร้างภาพ',
                                                'create_video' => 'สร้างวิดีโอ',
                                                'publish' => 'เผยแพร่',
                                            ];
                                            $typeIcons = [
                                                'generate_music' => 'M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3',
                                                'generate_images' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
                                                'create_video' => 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z',
                                                'publish' => 'M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12',
                                            ];
                                        @endphp
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                                                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $typeIcons[$job->job_type] ?? $typeIcons['generate_music'] }}"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="font-semibold text-gray-900 dark:text-white">{{ $typeLabels[$job->job_type] ?? $job->job_type }}</p>
                                                <p class="text-sm text-gray-500 dark:text-gray-400">#{{ $job->id }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    @if($job->project)
                                        <a href="{{ route('admin.video-automation.projects.show', $job->project) }}"
                                           class="text-purple-600 hover:underline">
                                            {{ $job->project->name }}
                                        </a>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @php
                                        $statusLabels = [
                                            'pending' => 'รอดำเนินการ',
                                            'running' => 'กำลังทำงาน',
                                            'completed' => 'เสร็จสิ้น',
                                            'failed' => 'ล้มเหลว',
                                            'cancelled' => 'ยกเลิก',
                                        ];
                                    @endphp
                                    <span class="status-badge status-{{ $job->status }}">
                                        @if($job->status === 'running')
                                            <svg class="w-3 h-3 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        @endif
                                        {{ $statusLabels[$job->status] ?? $job->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="w-32">
                                        <div class="flex items-center justify-between text-xs mb-1">
                                            <span class="text-gray-500 dark:text-gray-400">{{ $job->progress }}%</span>
                                        </div>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: {{ $job->progress }}%"></div>
                                        </div>
                                        @if($job->current_step)
                                            <p class="text-xs text-gray-400 mt-1 truncate">{{ $job->current_step }}</p>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm">
                                        <p class="text-gray-900 dark:text-white">{{ $job->created_at->format('d/m/Y H:i') }}</p>
                                        @if($job->started_at && $job->completed_at)
                                            <p class="text-gray-500 dark:text-gray-400">
                                                ใช้เวลา {{ $job->started_at->diffForHumans($job->completed_at, true) }}
                                            </p>
                                        @elseif($job->started_at)
                                            <p class="text-gray-500 dark:text-gray-400">
                                                เริ่ม {{ $job->started_at->diffForHumans() }}
                                            </p>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        @if($job->status === 'running' || $job->status === 'pending')
                                            <form action="{{ route('admin.video-automation.jobs.cancel', $job) }}"
                                                  method="POST"
                                                  class="inline"
                                                  onsubmit="return confirm('ต้องการยกเลิก job นี้หรือไม่?')">
                                                @csrf
                                                <button type="submit"
                                                        class="p-2 text-red-600 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition"
                                                        title="ยกเลิก">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif
                                        @if($job->status === 'failed')
                                            <form action="{{ route('admin.video-automation.jobs.retry', $job) }}"
                                                  method="POST"
                                                  class="inline">
                                                @csrf
                                                <button type="submit"
                                                        class="p-2 text-orange-600 hover:bg-orange-100 dark:hover:bg-orange-900/30 rounded-lg transition"
                                                        title="ลองใหม่">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif
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
            {{ $jobs->links() }}
        </div>
    @else
        {{-- Empty State --}}
        <div class="glass-card rounded-2xl p-12 text-center">
            <div class="w-24 h-24 mx-auto mb-6 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center">
                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                ไม่มี Job ในคิว
            </h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">
                สร้างโปรเจกต์ใหม่เพื่อเริ่มสร้างงาน
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
@endsection
