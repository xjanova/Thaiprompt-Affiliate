@extends('layouts.admin-v3')

@section('title', $pageTitle ?? 'ประวัติการสร้าง Content')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">ประวัติการสร้าง Content</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">ดูประวัติการสร้าง Content ทั้งหมด</p>
        </div>
        <a href="{{ route('admin.platform-revenue.ai-content-writer.dashboard') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
            <i class="fas fa-arrow-left mr-2"></i> กลับ Dashboard
        </a>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
        <form method="GET" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="ค้นหา..."
                       class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <select name="provider" class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                <option value="">ทุก Provider</option>
                @foreach($providers as $key => $label)
                    <option value="{{ $key }}" {{ request('provider') == $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <select name="status" class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                <option value="">ทุกสถานะ</option>
                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>สำเร็จ</option>
                <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>ล้มเหลว</option>
            </select>
            <select name="date" class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                <option value="">ทุกช่วงเวลา</option>
                <option value="today" {{ request('date') == 'today' ? 'selected' : '' }}>วันนี้</option>
                <option value="week" {{ request('date') == 'week' ? 'selected' : '' }}>7 วันที่ผ่านมา</option>
                <option value="month" {{ request('date') == 'month' ? 'selected' : '' }}>30 วันที่ผ่านมา</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">
                <i class="fas fa-search mr-2"></i> ค้นหา
            </button>
        </form>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
            <div class="text-center">
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total'] ?? 0) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">ทั้งหมด</p>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
            <div class="text-center">
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($stats['completed'] ?? 0) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">สำเร็จ</p>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
            <div class="text-center">
                <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ number_format($stats['failed'] ?? 0) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">ล้มเหลว</p>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
            <div class="text-center">
                <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($stats['total_tokens'] ?? 0) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Tokens</p>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
            <div class="text-center">
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">${{ number_format($stats['total_cost'] ?? 0, 2) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">ค่าใช้จ่าย</p>
            </div>
        </div>
    </div>

    {{-- Generations List --}}
    <div class="space-y-4">
        @forelse($generations as $generation)
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden hover:border-purple-500 dark:hover:border-purple-500 transition">
            <div class="p-4">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center gap-3">
                        <div class="p-2 rounded-lg {{ $generation->ai_provider === 'openai' ? 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400' : ($generation->ai_provider === 'claude' ? 'bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400' : 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400') }}">
                            <i class="fas {{ $generation->ai_provider === 'openai' ? 'fa-robot' : ($generation->ai_provider === 'claude' ? 'fa-brain' : 'fa-gem') }} fa-lg"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">
                                {{ $generation->project?->name ?? 'Playground' }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ ucfirst($generation->ai_provider) }} / {{ $generation->model }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="px-2 py-1 text-xs font-medium rounded-full {{ $generation->status === 'completed' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' }}">
                            {{ $generation->status === 'completed' ? 'สำเร็จ' : 'ล้มเหลว' }}
                        </span>
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $generation->created_at->format('d/m/Y H:i') }}
                        </span>
                    </div>
                </div>

                {{-- Content Preview --}}
                @if($generation->content)
                <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg mb-3">
                    <p class="text-sm text-gray-700 dark:text-gray-300 line-clamp-3">{{ Str::limit($generation->content, 300) }}</p>
                </div>
                @endif

                {{-- Footer Stats --}}
                <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
                    <div class="flex items-center gap-4">
                        <span>
                            <i class="fas fa-chart-bar mr-1"></i>
                            {{ number_format($generation->tokens_used ?? 0) }} tokens
                        </span>
                        <span>
                            <i class="fas fa-coins mr-1"></i>
                            ${{ number_format($generation->cost_usd ?? 0, 4) }}
                        </span>
                        <span>
                            <i class="fas fa-clock mr-1"></i>
                            {{ number_format(($generation->response_time_ms ?? 0) / 1000, 1) }}s
                        </span>
                    </div>
                    <a href="{{ route('admin.platform-revenue.ai-content-writer.generations.show', $generation->id) }}"
                       class="text-blue-600 dark:text-blue-400 hover:underline">
                        ดูรายละเอียด <i class="fas fa-chevron-right ml-1"></i>
                    </a>
                </div>
            </div>
        </div>
        @empty
        <div class="bg-white dark:bg-gray-800 rounded-xl p-12 border border-gray-200 dark:border-gray-700 text-center">
            <i class="fas fa-feather-alt text-5xl text-gray-300 dark:text-gray-600 mb-4"></i>
            <p class="text-gray-500 dark:text-gray-400">ยังไม่มีประวัติการสร้าง Content</p>
            <a href="{{ route('admin.platform-revenue.ai-content-writer.playground') }}" class="text-purple-600 hover:underline mt-2 inline-block">
                ไปที่ Playground <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    <div class="mt-6">
        {{ $generations->links() }}
    </div>
</div>
@endsection
