@extends('layouts.user-arrow-x')

@section('title', 'ประวัติการทำภารกิจ')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                📜 ประวัติการทำภารกิจ
            </h1>
            <p class="text-gray-600 dark:text-gray-400">ดูประวัติการทำภารกิจและรายได้ทั้งหมด</p>
        </div>
        <a href="{{ route('user.video-missions.index') }}"
           class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-white rounded-xl font-medium transition-all">
            กลับ
        </a>
    </div>

    {{-- Earnings Summary --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="glass-fusion dark:bg-gray-800/50 rounded-xl p-4 backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
            <p class="text-xs text-gray-500 dark:text-gray-400">วันนี้</p>
            <p class="text-xl font-bold text-gray-900 dark:text-white">฿{{ number_format($earnings['today'], 2) }}</p>
        </div>
        <div class="glass-fusion dark:bg-gray-800/50 rounded-xl p-4 backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
            <p class="text-xs text-gray-500 dark:text-gray-400">สัปดาห์นี้</p>
            <p class="text-xl font-bold text-gray-900 dark:text-white">฿{{ number_format($earnings['this_week'], 2) }}</p>
        </div>
        <div class="glass-fusion dark:bg-gray-800/50 rounded-xl p-4 backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
            <p class="text-xs text-gray-500 dark:text-gray-400">เดือนนี้</p>
            <p class="text-xl font-bold text-gray-900 dark:text-white">฿{{ number_format($earnings['this_month'], 2) }}</p>
        </div>
        <div class="glass-fusion dark:bg-gray-800/50 rounded-xl p-4 backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
            <p class="text-xs text-gray-500 dark:text-gray-400">ทั้งหมด</p>
            <p class="text-xl font-bold text-green-600 dark:text-green-400">฿{{ number_format($earnings['total'], 2) }}</p>
        </div>
    </div>

    {{-- History List --}}
    <div class="glass-fusion dark:bg-gray-800/50 rounded-2xl shadow-lg overflow-hidden backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
        @forelse($completions as $completion)
        <div class="flex items-center gap-4 p-4 border-b border-gray-200 dark:border-gray-700 last:border-b-0 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
            <div class="w-12 h-12 bg-gray-200 dark:bg-gray-700 rounded-lg flex items-center justify-center text-2xl">
                {{ $completion->status_icon }}
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-medium text-gray-900 dark:text-white truncate">
                    {{ $completion->mission->display_title ?? 'ภารกิจถูกลบ' }}
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $completion->created_at->format('d/m/Y H:i') }} • {{ $completion->status_label }}
                </p>
            </div>
            <div class="text-right">
                @if($completion->reward_given)
                <p class="text-green-600 dark:text-green-400 font-medium">
                    +{{ $completion->reward_summary }}
                </p>
                @else
                <span class="px-2 py-1 text-xs rounded-full {{ $completion->status_color === 'green' ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300' : ($completion->status_color === 'red' ? 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300') }}">
                    {{ $completion->status_label }}
                </span>
                @endif
            </div>
        </div>
        @empty
        <div class="p-12 text-center">
            <span class="text-5xl mb-4 block">📭</span>
            <p class="text-gray-500 dark:text-gray-400">ยังไม่มีประวัติการทำภารกิจ</p>
            <a href="{{ route('user.video-missions.index') }}"
               class="inline-block mt-4 px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition">
                เริ่มทำภารกิจ
            </a>
        </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($completions->hasPages())
    <div class="mt-6">
        {{ $completions->links() }}
    </div>
    @endif
</div>
@endsection
