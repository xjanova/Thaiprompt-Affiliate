@extends('layouts.admin')

@section('title', 'โปรเจกต์ - Video Automation')

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

    /* ปุ่ม Gradient */
    .btn-gradient-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        transition: all 0.3s ease;
    }
    .btn-gradient-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
    }

    /* Status Badge Colors */
    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
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
                    โปรเจกต์ทั้งหมด
                </h1>
            </div>
            <p class="text-gray-600 dark:text-gray-400">
                จัดการและติดตามการสร้างวิดีโออัตโนมัติ
            </p>
        </div>
        <a href="{{ route('admin.video-automation.projects.create') }}"
           class="btn-gradient-primary inline-flex items-center gap-2 px-6 py-3 text-white rounded-xl font-semibold">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            สร้างโปรเจกต์ใหม่
        </a>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="glass-card rounded-xl p-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] ?? 0 }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">ทั้งหมด</p>
                </div>
            </div>
        </div>

        <div class="glass-card rounded-xl p-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center">
                    <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['processing'] ?? 0 }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">กำลังทำงาน</p>
                </div>
            </div>
        </div>

        <div class="glass-card rounded-xl p-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['completed'] ?? 0 }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">เสร็จสิ้น</p>
                </div>
            </div>
        </div>

        <div class="glass-card rounded-xl p-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['failed'] ?? 0 }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">ล้มเหลว</p>
                </div>
            </div>
        </div>
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
                       placeholder="ชื่อโปรเจกต์..."
                       class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
            </div>

            {{-- Status Filter --}}
            <div class="w-48">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สถานะ</label>
                <select name="status"
                        class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    <option value="">ทั้งหมด</option>
                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>แบบร่าง</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>รอดำเนินการ</option>
                    <option value="generating_music" {{ request('status') == 'generating_music' ? 'selected' : '' }}>กำลังสร้างเพลง</option>
                    <option value="generating_images" {{ request('status') == 'generating_images' ? 'selected' : '' }}>กำลังสร้างภาพ</option>
                    <option value="creating_video" {{ request('status') == 'creating_video' ? 'selected' : '' }}>กำลังสร้างวิดีโอ</option>
                    <option value="publishing" {{ request('status') == 'publishing' ? 'selected' : '' }}>กำลังเผยแพร่</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>เสร็จสิ้น</option>
                    <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>ล้มเหลว</option>
                </select>
            </div>

            {{-- Template Filter --}}
            <div class="w-48">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Template</label>
                <select name="template_id"
                        class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    <option value="">ทั้งหมด</option>
                    @foreach($templates ?? [] as $template)
                        <option value="{{ $template->id }}" {{ request('template_id') == $template->id ? 'selected' : '' }}>{{ $template->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Buttons --}}
            <div class="flex gap-2">
                <button type="submit"
                        class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
                <a href="{{ route('admin.video-automation.projects.index') }}"
                   class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </a>
            </div>
        </form>
    </div>

    {{-- Alert Messages --}}
    @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-xl flex items-center gap-3">
            <svg class="w-6 h-6 text-green-600 dark:text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="text-green-700 dark:text-green-300">{{ session('success') }}</span>
        </div>
    @endif

    {{-- Projects List --}}
    @if($projects->count() > 0)
        <div class="glass-card rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                โปรเจกต์
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Template
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                สถานะ
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Progress
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                วันที่สร้าง
                            </th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                จัดการ
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($projects as $project)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center flex-shrink-0">
                                            @if($project->thumbnail_path)
                                                <img src="{{ Storage::url($project->thumbnail_path) }}"
                                                     alt=""
                                                     class="w-full h-full object-cover rounded-lg">
                                            @else
                                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                                </svg>
                                            @endif
                                        </div>
                                        <div>
                                            <a href="{{ route('admin.video-automation.projects.show', $project) }}"
                                               class="font-semibold text-gray-900 dark:text-white hover:text-purple-600 dark:hover:text-purple-400">
                                                {{ $project->name }}
                                            </a>
                                            @if($project->schedule)
                                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                                    <span class="inline-flex items-center gap-1">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                        </svg>
                                                        กำหนดการ
                                                    </span>
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    @if($project->template)
                                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ $project->template->name }}</span>
                                    @else
                                        <span class="text-sm text-gray-400 dark:text-gray-500">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
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
                                    <span class="status-badge status-{{ $project->status }}">
                                        @if(in_array($project->status, ['generating_music', 'generating_images', 'creating_video', 'publishing']))
                                            <svg class="w-3 h-3 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        @endif
                                        {{ $statusLabels[$project->status] ?? $project->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="w-32">
                                        <div class="flex items-center justify-between text-xs mb-1">
                                            <span class="text-gray-500 dark:text-gray-400">{{ $project->progress }}%</span>
                                        </div>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: {{ $project->progress }}%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $project->created_at->format('d/m/Y H:i') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.video-automation.projects.show', $project) }}"
                                           class="p-2 text-blue-600 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded-lg transition"
                                           title="ดูรายละเอียด">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </a>
                                        @if($project->status == 'draft')
                                            <form action="{{ route('admin.video-automation.projects.start', $project) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit"
                                                        class="p-2 text-green-600 hover:bg-green-100 dark:hover:bg-green-900/30 rounded-lg transition"
                                                        title="เริ่มดำเนินการ">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif
                                        @if(in_array($project->status, ['draft', 'failed']))
                                            <form action="{{ route('admin.video-automation.projects.destroy', $project) }}"
                                                  method="POST"
                                                  class="inline"
                                                  onsubmit="return confirm('ต้องการลบโปรเจกต์นี้หรือไม่?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="p-2 text-red-600 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition"
                                                        title="ลบ">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
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
            {{ $projects->links() }}
        </div>
    @else
        {{-- Empty State --}}
        <div class="glass-card rounded-2xl p-12 text-center">
            <div class="w-24 h-24 mx-auto mb-6 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center">
                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                ยังไม่มีโปรเจกต์
            </h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">
                สร้างโปรเจกต์แรกของคุณเพื่อเริ่มสร้างวิดีโออัตโนมัติ
            </p>
            <a href="{{ route('admin.video-automation.projects.create') }}"
               class="btn-gradient-primary inline-flex items-center gap-2 px-6 py-3 text-white rounded-xl font-semibold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                สร้างโปรเจกต์แรก
            </a>
        </div>
    @endif
</div>
@endsection
