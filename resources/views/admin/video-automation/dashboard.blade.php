@extends('layouts.admin')

@section('title', $pageTitle ?? 'Video Automation Dashboard')

@push('styles')
<style>
    /* กราเดียนต์พิเศษสำหรับ Video Automation */
    .va-gradient-purple {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    .va-gradient-blue {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }
    .va-gradient-green {
        background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    }
    .va-gradient-orange {
        background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    }
    .va-gradient-red {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }

    /* Animation สำหรับ pulse */
    @keyframes va-pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }
    .va-pulse {
        animation: va-pulse 2s infinite;
    }
</style>
@endpush

@section('content')
<div class="space-y-6" x-data="videoAutomationDashboard()">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                <span class="p-3 rounded-xl va-gradient-purple text-white">
                    <i class="fas fa-video fa-fw"></i>
                </span>
                Video Automation
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">
                ระบบสร้างวีดีโออัตโนมัติ - สร้างเพลง, ภาพ, และโพสลง Social Media
            </p>
        </div>

        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.video-automation.projects.create') }}"
               class="inline-flex items-center px-5 py-2.5 va-gradient-purple text-white font-semibold rounded-xl hover:opacity-90 transition shadow-lg shadow-purple-500/30">
                <i class="fas fa-plus mr-2"></i>
                สร้างโปรเจกต์ใหม่
            </a>
            <a href="{{ route('admin.video-automation.settings') }}"
               class="inline-flex items-center px-5 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 font-semibold rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                <i class="fas fa-cog mr-2"></i>
                ตั้งค่า
            </a>
        </div>
    </div>

    {{-- API Status Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        {{-- Suno AI --}}
        <div class="glass-fusion rounded-xl p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-lg bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400">
                        <i class="fas fa-music fa-lg"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">Suno AI</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">สร้างเพลง</p>
                    </div>
                </div>
                <span class="px-2 py-1 text-xs font-medium rounded-full
                    {{ $apiStatus['suno']['configured'] ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' }}">
                    {{ $apiStatus['suno']['configured'] ? 'เชื่อมต่อแล้ว' : 'ยังไม่ตั้งค่า' }}
                </span>
            </div>
        </div>

        {{-- Freepik --}}
        <div class="glass-fusion rounded-xl p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400">
                        <i class="fas fa-images fa-lg"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">Freepik AI</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">สร้างภาพ</p>
                    </div>
                </div>
                <span class="px-2 py-1 text-xs font-medium rounded-full
                    {{ $apiStatus['freepik']['configured'] ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' }}">
                    {{ $apiStatus['freepik']['configured'] ? 'เชื่อมต่อแล้ว' : 'ยังไม่ตั้งค่า' }}
                </span>
            </div>
        </div>

        {{-- FFmpeg --}}
        <div class="glass-fusion rounded-xl p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-lg bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400">
                        <i class="fas fa-film fa-lg"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">FFmpeg</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">สร้างวีดีโอ</p>
                    </div>
                </div>
                <span class="px-2 py-1 text-xs font-medium rounded-full
                    {{ $apiStatus['ffmpeg']['configured'] ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' }}">
                    {{ $apiStatus['ffmpeg']['configured'] ? 'พร้อมใช้งาน' : 'ไม่พร้อม' }}
                </span>
            </div>
        </div>

        {{-- YouTube --}}
        <div class="glass-fusion rounded-xl p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-lg bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400">
                        <i class="fab fa-youtube fa-lg"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">YouTube</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">อัปโหลดวีดีโอ</p>
                    </div>
                </div>
                @php
                    $ytConnected = isset($apiStatus['platforms']['youtube']) && $apiStatus['platforms']['youtube']['connected'];
                @endphp
                <span class="px-2 py-1 text-xs font-medium rounded-full
                    {{ $ytConnected ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' }}">
                    {{ $ytConnected ? 'เชื่อมต่อแล้ว' : 'รอเชื่อมต่อ' }}
                </span>
            </div>
        </div>
    </div>

    {{-- Statistics --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="glass-fusion rounded-xl p-5 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-4">
                <div class="p-3 rounded-xl va-gradient-purple text-white">
                    <i class="fas fa-folder-open fa-lg"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_projects']) }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">โปรเจกต์ทั้งหมด</p>
                </div>
            </div>
        </div>

        <div class="glass-fusion rounded-xl p-5 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-4">
                <div class="p-3 rounded-xl va-gradient-green text-white">
                    <i class="fas fa-check-circle fa-lg"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['published_projects']) }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">โพสแล้ว</p>
                </div>
            </div>
        </div>

        <div class="glass-fusion rounded-xl p-5 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-4">
                <div class="p-3 rounded-xl va-gradient-blue text-white">
                    <i class="fas fa-spinner fa-lg"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['in_progress']) }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">กำลังทำงาน</p>
                </div>
            </div>
        </div>

        <div class="glass-fusion rounded-xl p-5 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-4">
                <div class="p-3 rounded-xl va-gradient-orange text-white">
                    <i class="fas fa-layer-group fa-lg"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_templates']) }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">เทมเพลต</p>
                </div>
            </div>
        </div>

        <div class="glass-fusion rounded-xl p-5 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-4">
                <div class="p-3 rounded-xl va-gradient-red text-white">
                    <i class="fas fa-calendar-alt fa-lg"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['active_schedules']) }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Schedule ใช้งาน</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <a href="{{ route('admin.video-automation.templates') }}"
           class="glass-fusion rounded-xl p-6 border border-gray-200 dark:border-gray-700 hover:shadow-lg transition group">
            <div class="text-center">
                <div class="w-14 h-14 mx-auto rounded-xl va-gradient-purple text-white flex items-center justify-center mb-3 group-hover:scale-110 transition">
                    <i class="fas fa-palette fa-xl"></i>
                </div>
                <h3 class="font-semibold text-gray-900 dark:text-white">จัดการเทมเพลต</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">สร้างรูปแบบวีดีโอ</p>
            </div>
        </a>

        <a href="{{ route('admin.video-automation.projects') }}"
           class="glass-fusion rounded-xl p-6 border border-gray-200 dark:border-gray-700 hover:shadow-lg transition group">
            <div class="text-center">
                <div class="w-14 h-14 mx-auto rounded-xl va-gradient-blue text-white flex items-center justify-center mb-3 group-hover:scale-110 transition">
                    <i class="fas fa-video fa-xl"></i>
                </div>
                <h3 class="font-semibold text-gray-900 dark:text-white">โปรเจกต์ทั้งหมด</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">ดูและจัดการวีดีโอ</p>
            </div>
        </a>

        <a href="{{ route('admin.video-automation.schedules') }}"
           class="glass-fusion rounded-xl p-6 border border-gray-200 dark:border-gray-700 hover:shadow-lg transition group">
            <div class="text-center">
                <div class="w-14 h-14 mx-auto rounded-xl va-gradient-green text-white flex items-center justify-center mb-3 group-hover:scale-110 transition">
                    <i class="fas fa-clock fa-xl"></i>
                </div>
                <h3 class="font-semibold text-gray-900 dark:text-white">ตั้งเวลาอัตโนมัติ</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">สร้างวีดีโอตามเวลา</p>
            </div>
        </a>

        <a href="{{ route('admin.video-automation.documentation') }}"
           class="glass-fusion rounded-xl p-6 border border-gray-200 dark:border-gray-700 hover:shadow-lg transition group">
            <div class="text-center">
                <div class="w-14 h-14 mx-auto rounded-xl va-gradient-orange text-white flex items-center justify-center mb-3 group-hover:scale-110 transition">
                    <i class="fas fa-book fa-xl"></i>
                </div>
                <h3 class="font-semibold text-gray-900 dark:text-white">คู่มือการใช้งาน</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">วิธีตั้งค่า API Keys</p>
            </div>
        </a>
    </div>

    {{-- Recent Projects --}}
    <div class="glass-fusion rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                <i class="fas fa-history mr-2 text-purple-500"></i>
                โปรเจกต์ล่าสุด
            </h2>
            <a href="{{ route('admin.video-automation.projects') }}"
               class="text-sm text-purple-600 dark:text-purple-400 hover:underline">
                ดูทั้งหมด <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>

        @if($recentProjects->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ชื่อโปรเจกต์</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">สถานะ</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Progress</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">สร้างเมื่อ</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">การกระทำ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($recentProjects as $project)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-lg va-gradient-purple text-white flex items-center justify-center">
                                            <i class="fas fa-video"></i>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-white">{{ $project->name }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $project->template?->name ?? 'ไม่มีเทมเพลต' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 text-xs font-medium rounded-full bg-{{ $project->status_color }}-100 text-{{ $project->status_color }}-700 dark:bg-{{ $project->status_color }}-900/30 dark:text-{{ $project->status_color }}-400">
                                        <i class="fas {{ $project->status_icon }} mr-1"></i>
                                        {{ $project->status_label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-24 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                            <div class="va-gradient-purple h-2 rounded-full" style="width: {{ $project->progress }}%"></div>
                                        </div>
                                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ $project->progress }}%</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    {{ $project->created_at->diffForHumans() }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('admin.video-automation.projects.show', $project->id) }}"
                                       class="text-purple-600 dark:text-purple-400 hover:underline">
                                        ดูรายละเอียด
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-12 text-center">
                <div class="w-20 h-20 mx-auto rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center mb-4">
                    <i class="fas fa-video fa-2xl text-gray-400"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">ยังไม่มีโปรเจกต์</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-4">เริ่มสร้างวีดีโอแรกของคุณ!</p>
                <a href="{{ route('admin.video-automation.projects.create') }}"
                   class="inline-flex items-center px-4 py-2 va-gradient-purple text-white font-medium rounded-lg">
                    <i class="fas fa-plus mr-2"></i>
                    สร้างโปรเจกต์
                </a>
            </div>
        @endif
    </div>

    {{-- Workflow Diagram --}}
    <div class="glass-fusion rounded-xl border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
            <i class="fas fa-project-diagram mr-2 text-purple-500"></i>
            ขั้นตอนการทำงานอัตโนมัติ
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-center">
            {{-- Step 1: Suno --}}
            <div class="text-center">
                <div class="w-16 h-16 mx-auto rounded-full va-gradient-purple text-white flex items-center justify-center mb-2">
                    <i class="fas fa-music fa-xl"></i>
                </div>
                <h4 class="font-medium text-gray-900 dark:text-white">1. สร้างเพลง</h4>
                <p class="text-xs text-gray-500 dark:text-gray-400">Suno AI</p>
            </div>

            <div class="hidden md:flex justify-center">
                <i class="fas fa-arrow-right fa-xl text-gray-300 dark:text-gray-600"></i>
            </div>

            {{-- Step 2: Freepik --}}
            <div class="text-center">
                <div class="w-16 h-16 mx-auto rounded-full va-gradient-blue text-white flex items-center justify-center mb-2">
                    <i class="fas fa-images fa-xl"></i>
                </div>
                <h4 class="font-medium text-gray-900 dark:text-white">2. สร้างภาพ</h4>
                <p class="text-xs text-gray-500 dark:text-gray-400">Freepik AI</p>
            </div>

            <div class="hidden md:flex justify-center">
                <i class="fas fa-arrow-right fa-xl text-gray-300 dark:text-gray-600"></i>
            </div>

            {{-- Step 3: FFmpeg --}}
            <div class="text-center">
                <div class="w-16 h-16 mx-auto rounded-full va-gradient-green text-white flex items-center justify-center mb-2">
                    <i class="fas fa-film fa-xl"></i>
                </div>
                <h4 class="font-medium text-gray-900 dark:text-white">3. สร้างวีดีโอ</h4>
                <p class="text-xs text-gray-500 dark:text-gray-400">FFmpeg</p>
            </div>
        </div>

        <div class="flex justify-center my-4">
            <i class="fas fa-arrow-down fa-xl text-gray-300 dark:text-gray-600"></i>
        </div>

        <div class="text-center">
            <div class="inline-flex gap-4">
                <div class="w-12 h-12 rounded-full bg-red-500 text-white flex items-center justify-center">
                    <i class="fab fa-youtube"></i>
                </div>
                <div class="w-12 h-12 rounded-full bg-blue-600 text-white flex items-center justify-center">
                    <i class="fab fa-facebook"></i>
                </div>
                <div class="w-12 h-12 rounded-full bg-gradient-to-tr from-yellow-400 via-pink-500 to-purple-600 text-white flex items-center justify-center">
                    <i class="fab fa-instagram"></i>
                </div>
                <div class="w-12 h-12 rounded-full bg-black text-white flex items-center justify-center">
                    <i class="fab fa-tiktok"></i>
                </div>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">4. โพสอัตโนมัติไปทุก Platform</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function videoAutomationDashboard() {
    return {
        // สำหรับ real-time updates ในอนาคต
        init() {
            console.log('Video Automation Dashboard initialized');
        }
    }
}
</script>
@endpush
