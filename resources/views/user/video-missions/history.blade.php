@extends('layouts.user-v4')

@section('title', 'ประวัติการทำภารกิจ')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    <div class="relative overflow-hidden bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 dark:from-blue-800 dark:via-indigo-800 dark:to-purple-800 rounded-2xl shadow-2xl p-8">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s"></div>
        </div>
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute text-white/10 text-8xl top-10 right-20" style="animation: float 6s ease-in-out infinite"><i class="fas fa-history"></i></div>
        </div>
        <div class="relative z-10"><div class="flex items-center gap-4">
            <div class="glass-fusion p-4 rounded-2xl"><i class="fas fa-history text-4xl text-white drop-shadow-lg"></i></div>
            <div><h1 class="text-4xl font-bold text-white drop-shadow-lg">ประวัติ Video</h1>
            <p class="text-white/80 text-lg mt-1">ประวัติการดูวิดีโอ</p></div>
        </div></div>
    </div>
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
