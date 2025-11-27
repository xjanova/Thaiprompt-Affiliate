@extends('layouts.admin')

@section('title', $pageTitle ?? 'AI Content Writer Dashboard')

@push('styles')
<style>
    .acw-gradient-blue { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    .acw-gradient-green { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
    .acw-gradient-orange { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
    .acw-gradient-purple { background: linear-gradient(135deg, #8E2DE2 0%, #4A00E0 100%); }
</style>
@endpush

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                <span class="p-3 rounded-xl acw-gradient-purple text-white">
                    <i class="fas fa-pen-fancy fa-fw"></i>
                </span>
                AI Content Writer
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">
                สร้าง Content ด้วย AI - Blog, Script, Caption, และอื่นๆ
            </p>
        </div>

        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.ai-content-writer.playground') }}"
               class="inline-flex items-center px-5 py-2.5 acw-gradient-purple text-white font-semibold rounded-xl hover:opacity-90 transition shadow-lg shadow-purple-500/30">
                <i class="fas fa-magic mr-2"></i>
                สร้าง Content ใหม่
            </a>
            <a href="{{ route('admin.ai-content-writer.settings') }}"
               class="inline-flex items-center px-5 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 font-semibold rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                <i class="fas fa-cog mr-2"></i>
                ตั้งค่า
            </a>
        </div>
    </div>

    {{-- API Status Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @foreach(['openai' => ['OpenAI', 'fa-robot', 'purple'], 'claude' => ['Claude', 'fa-brain', 'blue'], 'gemini' => ['Gemini', 'fa-gem', 'green']] as $key => [$name, $icon, $color])
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-lg bg-{{ $color }}-100 dark:bg-{{ $color }}-900/30 text-{{ $color }}-600 dark:text-{{ $color }}-400">
                        <i class="fas {{ $icon }} fa-lg"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">{{ $name }}</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">AI Provider</p>
                    </div>
                </div>
                <span class="px-2 py-1 text-xs font-medium rounded-full
                    {{ $apiStatus[$key]['configured'] ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' }}">
                    {{ $apiStatus[$key]['configured'] ? 'พร้อมใช้งาน' : 'ยังไม่ตั้งค่า' }}
                </span>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-4">
                <div class="p-3 rounded-xl acw-gradient-blue text-white">
                    <i class="fas fa-file-alt fa-lg"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['templates']['total']) }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">เทมเพลต</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-4">
                <div class="p-3 rounded-xl acw-gradient-green text-white">
                    <i class="fas fa-folder fa-lg"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['projects']['total']) }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">โปรเจกต์</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-4">
                <div class="p-3 rounded-xl acw-gradient-orange text-white">
                    <i class="fas fa-feather-alt fa-lg"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['generations']['total']) }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">การสร้าง</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-4">
                <div class="p-3 rounded-xl acw-gradient-purple text-white">
                    <i class="fas fa-coins fa-lg"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">${{ number_format($stats['usage']['total_cost_usd'], 2) }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">ค่าใช้จ่ายรวม</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Links --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <a href="{{ route('admin.ai-content-writer.templates') }}" class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700 hover:border-purple-500 dark:hover:border-purple-500 transition group">
            <i class="fas fa-layer-group text-3xl text-purple-500 mb-3"></i>
            <h3 class="font-semibold text-gray-900 dark:text-white group-hover:text-purple-500 transition">จัดการเทมเพลต</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $stats['templates']['active'] }} เทมเพลตใช้งานอยู่</p>
        </a>

        <a href="{{ route('admin.ai-content-writer.projects') }}" class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700 hover:border-blue-500 dark:hover:border-blue-500 transition group">
            <i class="fas fa-folder-open text-3xl text-blue-500 mb-3"></i>
            <h3 class="font-semibold text-gray-900 dark:text-white group-hover:text-blue-500 transition">โปรเจกต์ทั้งหมด</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $stats['projects']['completed'] }} เสร็จสิ้น</p>
        </a>

        <a href="{{ route('admin.ai-content-writer.generations') }}" class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700 hover:border-green-500 dark:hover:border-green-500 transition group">
            <i class="fas fa-history text-3xl text-green-500 mb-3"></i>
            <h3 class="font-semibold text-gray-900 dark:text-white group-hover:text-green-500 transition">ประวัติการสร้าง</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $stats['generations']['today'] }} วันนี้</p>
        </a>

        <a href="{{ route('admin.ai-content-writer.usage-logs') }}" class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700 hover:border-orange-500 dark:hover:border-orange-500 transition group">
            <i class="fas fa-chart-bar text-3xl text-orange-500 mb-3"></i>
            <h3 class="font-semibold text-gray-900 dark:text-white group-hover:text-orange-500 transition">Usage Logs</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ number_format($stats['usage']['total_tokens']) }} tokens</p>
        </a>
    </div>

    {{-- Recent Activity --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Recent Projects --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white">โปรเจกต์ล่าสุด</h3>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($recentProjects as $project)
                <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="font-medium text-gray-900 dark:text-white">{{ Str::limit($project->name, 40) }}</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $project->content_type_label }}</p>
                        </div>
                        <span class="px-2 py-1 text-xs rounded-full {{ $project->status_color }}">
                            {{ $project->status_label }}
                        </span>
                    </div>
                </div>
                @empty
                <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                    ยังไม่มีโปรเจกต์
                </div>
                @endforelse
            </div>
        </div>

        {{-- Popular Templates --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white">เทมเพลตยอดนิยม</h3>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($popularTemplates as $template)
                <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="font-medium text-gray-900 dark:text-white">{{ $template->name }}</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $template->content_type_label }}</p>
                        </div>
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            <i class="fas fa-chart-line mr-1"></i>
                            {{ number_format($template->usage_count) }} ครั้ง
                        </span>
                    </div>
                </div>
                @empty
                <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                    ยังไม่มีเทมเพลต
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
