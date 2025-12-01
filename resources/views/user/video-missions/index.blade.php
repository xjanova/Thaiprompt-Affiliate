@extends('layouts.user-arrow-x')

@section('title', 'ภารกิจดูคลิปรับรางวัล')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6" x-data="videoMissions()">
    {{-- Header --}}
    <div class="glass-fusion dark:bg-gray-800/50 rounded-2xl shadow-lg p-6 mb-6 backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold bg-gradient-to-r from-pink-600 to-purple-600 bg-clip-text text-transparent">
                    🎬 ภารกิจดูคลิปรับรางวัล
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">ดูคลิปตามเวลาที่กำหนด รับรางวัลทันที!</p>
            </div>
            <a href="{{ route('user.video-missions.history') }}"
               class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-white rounded-xl font-medium transition-all flex items-center gap-2 w-fit">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                ประวัติ
            </a>
        </div>
    </div>

    {{-- Stats & Limits --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        {{-- Today's Progress --}}
        <div class="glass-fusion dark:bg-gray-800/50 rounded-xl p-4 backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-lg flex items-center justify-center text-white">
                    📊
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">ทำแล้ววันนี้</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">
                        {{ $limits['daily']['completed'] }}/{{ $limits['daily']['limit'] }}
                    </p>
                </div>
            </div>
            <div class="mt-2 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-blue-500 to-cyan-500 rounded-full transition-all"
                     style="width: {{ min(100, ($limits['daily']['completed'] / $limits['daily']['limit']) * 100) }}%"></div>
            </div>
        </div>

        {{-- Weekly Progress --}}
        <div class="glass-fusion dark:bg-gray-800/50 rounded-xl p-4 backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-500 rounded-lg flex items-center justify-center text-white">
                    📅
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">สัปดาห์นี้</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">
                        {{ $limits['weekly']['completed'] }}/{{ $limits['weekly']['limit'] }}
                    </p>
                </div>
            </div>
            <div class="mt-2 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-purple-500 to-pink-500 rounded-full transition-all"
                     style="width: {{ min(100, ($limits['weekly']['completed'] / $limits['weekly']['limit']) * 100) }}%"></div>
            </div>
        </div>

        {{-- Today's Earnings --}}
        <div class="glass-fusion dark:bg-gray-800/50 rounded-xl p-4 backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-500 rounded-lg flex items-center justify-center text-white">
                    💰
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">รายได้วันนี้</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">
                        ฿{{ number_format($stats['earnings_today'], 2) }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Total Earnings --}}
        <div class="glass-fusion dark:bg-gray-800/50 rounded-xl p-4 backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-yellow-500 to-orange-500 rounded-lg flex items-center justify-center text-white">
                    🏆
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">รายได้ทั้งหมด</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">
                        ฿{{ number_format($stats['total_earnings'], 2) }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Rank Bonus Info --}}
    @if($rankLimit->reward_multiplier > 1)
    <div class="bg-gradient-to-r from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl p-4 mb-6">
        <div class="flex items-center gap-3">
            <span class="text-2xl">⭐</span>
            <div>
                <p class="font-semibold text-yellow-800 dark:text-yellow-200">
                    โบนัส Rank: x{{ number_format($rankLimit->reward_multiplier, 2) }} รางวัล!
                </p>
                <p class="text-sm text-yellow-600 dark:text-yellow-300">
                    คุณได้รับรางวัลเพิ่ม {{ round(($rankLimit->reward_multiplier - 1) * 100) }}% จาก Rank {{ $user->currentRank->display_name ?? 'ปัจจุบัน' }}
                </p>
            </div>
        </div>
    </div>
    @endif

    {{-- In Progress Missions --}}
    @if($inProgressMissions->count() > 0)
    <div class="mb-6">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            ⏳ กำลังทำอยู่
        </h2>
        <div class="grid md:grid-cols-2 gap-4">
            @foreach($inProgressMissions as $progress)
            <div class="glass-fusion dark:bg-gray-800/50 rounded-xl p-4 border border-yellow-200 dark:border-yellow-800 bg-yellow-50/50 dark:bg-yellow-900/10">
                <div class="flex items-center gap-4">
                    @if($progress->mission->thumbnail_url)
                    <img src="{{ $progress->mission->thumbnail_url }}" alt="" class="w-20 h-12 object-cover rounded-lg">
                    @else
                    <div class="w-20 h-12 bg-gray-200 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                        <span class="text-xl">🎬</span>
                    </div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-gray-900 dark:text-white truncate">{{ $progress->mission->display_title }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $progress->watched_seconds }}/{{ $progress->required_seconds }} วินาที ({{ round($progress->watch_percentage) }}%)
                        </p>
                        <div class="mt-1 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-yellow-500 to-orange-500 rounded-full"
                                 style="width: {{ $progress->watch_percentage }}%"></div>
                        </div>
                    </div>
                    <a href="{{ route('user.video-missions.watch', $progress->mission) }}"
                       class="px-3 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg text-sm font-medium">
                        ดูต่อ
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Featured Missions --}}
    @if($featuredMissions->count() > 0)
    <div class="mb-6">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            ⭐ ภารกิจแนะนำ
        </h2>
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($featuredMissions as $mission)
            <a href="{{ route('user.video-missions.show', $mission) }}"
               class="glass-fusion dark:bg-gray-800/50 rounded-xl overflow-hidden hover:shadow-lg transition-all border border-white/20 dark:border-gray-700/50 group">
                <div class="relative">
                    @if($mission->thumbnail_url)
                    <img src="{{ $mission->thumbnail_url }}" alt="" class="w-full h-32 object-cover group-hover:scale-105 transition-transform">
                    @else
                    <div class="w-full h-32 bg-gradient-to-br from-pink-500 to-purple-600 flex items-center justify-center">
                        <span class="text-4xl">{{ $mission->icon ?? '🎬' }}</span>
                    </div>
                    @endif
                    <div class="absolute top-2 right-2 px-2 py-1 bg-yellow-500 text-white text-xs font-bold rounded">
                        ⭐ แนะนำ
                    </div>
                    <div class="absolute bottom-2 left-2 px-2 py-1 bg-black/60 text-white text-xs rounded">
                        {{ $mission->required_watch_time_formatted }}
                    </div>
                </div>
                <div class="p-4">
                    <h3 class="font-semibold text-gray-900 dark:text-white truncate group-hover:text-pink-600 dark:group-hover:text-pink-400">
                        {{ $mission->display_title }}
                    </h3>
                    <p class="text-sm text-green-600 dark:text-green-400 mt-1 font-medium">
                        🎁 {{ $mission->reward_summary }}
                    </p>
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- All Missions --}}
    <div>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                📋 ภารกิจทั้งหมด
            </h2>
            <a href="{{ route('user.video-missions.list') }}" class="text-pink-600 dark:text-pink-400 hover:underline text-sm font-medium">
                ดูทั้งหมด →
            </a>
        </div>

        @if(!$limits['can_do_more'])
        <div class="bg-gray-100 dark:bg-gray-800 rounded-xl p-6 text-center mb-4">
            <span class="text-4xl mb-2 block">🎯</span>
            <p class="text-gray-600 dark:text-gray-400">{{ $limits['reason'] }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-500 mt-2">กลับมาใหม่ในวัน/สัปดาห์/เดือนหน้า</p>
        </div>
        @endif

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($missions as $mission)
            <a href="{{ route('user.video-missions.show', $mission) }}"
               class="glass-fusion dark:bg-gray-800/50 rounded-xl overflow-hidden hover:shadow-lg transition-all border border-white/20 dark:border-gray-700/50 group {{ !$limits['can_do_more'] ? 'opacity-50 pointer-events-none' : '' }}">
                <div class="relative">
                    @if($mission->thumbnail_url)
                    <img src="{{ $mission->thumbnail_url }}" alt="" class="w-full h-40 object-cover group-hover:scale-105 transition-transform">
                    @else
                    <div class="w-full h-40 bg-gradient-to-br from-gray-300 to-gray-400 dark:from-gray-700 dark:to-gray-600 flex items-center justify-center">
                        <span class="text-5xl">{{ $mission->icon ?? '🎬' }}</span>
                    </div>
                    @endif
                    @if($mission->is_premium)
                    <div class="absolute top-2 left-2 px-2 py-1 bg-purple-600 text-white text-xs font-bold rounded">
                        👑 Premium
                    </div>
                    @endif
                    <div class="absolute bottom-2 left-2 px-2 py-1 bg-black/60 text-white text-xs rounded flex items-center gap-1">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                        </svg>
                        {{ $mission->required_watch_time_formatted }}
                    </div>
                </div>
                <div class="p-4">
                    <h3 class="font-semibold text-gray-900 dark:text-white truncate group-hover:text-pink-600 dark:group-hover:text-pink-400">
                        {{ $mission->display_title }}
                    </h3>
                    @if($mission->display_description)
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">
                        {{ Str::limit($mission->display_description, 60) }}
                    </p>
                    @endif
                    <div class="flex items-center justify-between mt-3">
                        <span class="text-sm text-green-600 dark:text-green-400 font-medium">
                            🎁 {{ $mission->reward_summary }}
                        </span>
                        <span class="text-xs text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">
                            {{ $mission->completion_count }} ทำแล้ว
                        </span>
                    </div>
                </div>
            </a>
            @empty
            <div class="col-span-full text-center py-12">
                <span class="text-5xl mb-4 block">🎬</span>
                <p class="text-gray-500 dark:text-gray-400">ยังไม่มีภารกิจในขณะนี้</p>
            </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($missions->hasPages())
        <div class="mt-6">
            {{ $missions->links() }}
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
function videoMissions() {
    return {
        // สามารถเพิ่ม Alpine.js logic ได้ที่นี่
    }
}
</script>
@endpush
@endsection
