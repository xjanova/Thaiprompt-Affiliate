@extends('layouts.admin-v3')

@section('title', 'ภารกิจดูคลิปรับรางวัล')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    {{-- Header --}}
    <div class="glass-fusion dark:bg-gray-800/50 rounded-2xl shadow-lg p-6 mb-8 backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-pink-600 to-purple-600 bg-clip-text text-transparent">
                    🎬 ภารกิจดูคลิปรับรางวัล
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">จัดการภารกิจดูคลิป ตั้งค่า Rank Limits และดูสถิติ</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.video-missions.create') }}"
                   class="px-4 py-2 bg-gradient-to-r from-pink-600 to-purple-600 hover:from-pink-700 hover:to-purple-700 text-white rounded-xl font-medium transition-all shadow-lg hover:shadow-pink-500/25 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    สร้างภารกิจใหม่
                </a>
                <a href="{{ route('admin.video-missions.settings') }}"
                   class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-white rounded-xl font-medium transition-all flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    ตั้งค่า
                </a>
            </div>
        </div>
    </div>

    {{-- Stats Grid --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        {{-- Total Missions --}}
        <div class="glass-fusion dark:bg-gray-800/50 rounded-xl p-5 backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-xl flex items-center justify-center text-white text-xl">
                    🎬
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">ภารกิจทั้งหมด</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_missions']) }}</p>
                </div>
            </div>
        </div>

        {{-- Active Missions --}}
        <div class="glass-fusion dark:bg-gray-800/50 rounded-xl p-5 backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-500 rounded-xl flex items-center justify-center text-white text-xl">
                    ✅
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">เปิดใช้งาน</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['active_missions']) }}</p>
                </div>
            </div>
        </div>

        {{-- Completions Today --}}
        <div class="glass-fusion dark:bg-gray-800/50 rounded-xl p-5 backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl flex items-center justify-center text-white text-xl">
                    📊
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">ทำสำเร็จวันนี้</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['completions_today']) }}</p>
                </div>
            </div>
        </div>

        {{-- Total Rewards --}}
        <div class="glass-fusion dark:bg-gray-800/50 rounded-xl p-5 backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-gradient-to-br from-yellow-500 to-orange-500 rounded-xl flex items-center justify-center text-white text-xl">
                    💰
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">รางวัลที่แจก</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">฿{{ number_format($stats['total_rewards_given'], 2) }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Alert Cards --}}
    @if($stats['suspicious_count'] > 0 || $stats['pending_verification'] > 0)
    <div class="grid md:grid-cols-2 gap-4 mb-8">
        @if($stats['suspicious_count'] > 0)
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4">
            <div class="flex items-center gap-3">
                <span class="text-2xl">⚠️</span>
                <div>
                    <p class="font-semibold text-red-800 dark:text-red-200">{{ $stats['suspicious_count'] }} รายการน่าสงสัย</p>
                    <p class="text-sm text-red-600 dark:text-red-300">รอการตรวจสอบจาก Admin</p>
                </div>
                <a href="{{ route('admin.video-missions.completions') }}?suspicious=1" class="ml-auto px-3 py-1 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700">
                    ตรวจสอบ
                </a>
            </div>
        </div>
        @endif

        @if($stats['pending_verification'] > 0)
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl p-4">
            <div class="flex items-center gap-3">
                <span class="text-2xl">⏳</span>
                <div>
                    <p class="font-semibold text-yellow-800 dark:text-yellow-200">{{ $stats['pending_verification'] }} รายการรอยืนยัน</p>
                    <p class="text-sm text-yellow-600 dark:text-yellow-300">รอการ verify</p>
                </div>
                <a href="{{ route('admin.video-missions.completions') }}?status=completed" class="ml-auto px-3 py-1 bg-yellow-600 text-white rounded-lg text-sm hover:bg-yellow-700">
                    ดูทั้งหมด
                </a>
            </div>
        </div>
        @endif
    </div>
    @endif

    {{-- Quick Actions --}}
    <div class="grid md:grid-cols-4 gap-4 mb-8">
        <a href="{{ route('admin.video-missions.missions') }}" class="glass-fusion dark:bg-gray-800/50 rounded-xl p-5 hover:shadow-lg transition-all border border-white/20 dark:border-gray-700/50 group">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/50 rounded-lg flex items-center justify-center text-blue-600 dark:text-blue-400 group-hover:scale-110 transition-transform">
                    📋
                </div>
                <div>
                    <p class="font-semibold text-gray-900 dark:text-white">จัดการภารกิจ</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">ดู/แก้ไข/ลบ</p>
                </div>
            </div>
        </a>

        <a href="{{ route('admin.video-missions.completions') }}" class="glass-fusion dark:bg-gray-800/50 rounded-xl p-5 hover:shadow-lg transition-all border border-white/20 dark:border-gray-700/50 group">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-100 dark:bg-green-900/50 rounded-lg flex items-center justify-center text-green-600 dark:text-green-400 group-hover:scale-110 transition-transform">
                    ✓
                </div>
                <div>
                    <p class="font-semibold text-gray-900 dark:text-white">การทำภารกิจ</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">ดูประวัติ/ตรวจสอบ</p>
                </div>
            </div>
        </a>

        <a href="{{ route('admin.video-missions.rank-limits') }}" class="glass-fusion dark:bg-gray-800/50 rounded-xl p-5 hover:shadow-lg transition-all border border-white/20 dark:border-gray-700/50 group">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/50 rounded-lg flex items-center justify-center text-purple-600 dark:text-purple-400 group-hover:scale-110 transition-transform">
                    🏆
                </div>
                <div>
                    <p class="font-semibold text-gray-900 dark:text-white">Rank Limits</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">จำกัดตาม Rank</p>
                </div>
            </div>
        </a>

        <a href="{{ route('admin.video-missions.reports') }}" class="glass-fusion dark:bg-gray-800/50 rounded-xl p-5 hover:shadow-lg transition-all border border-white/20 dark:border-gray-700/50 group">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-orange-100 dark:bg-orange-900/50 rounded-lg flex items-center justify-center text-orange-600 dark:text-orange-400 group-hover:scale-110 transition-transform">
                    📈
                </div>
                <div>
                    <p class="font-semibold text-gray-900 dark:text-white">รายงาน</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">สถิติและรายงาน</p>
                </div>
            </div>
        </a>
    </div>

    {{-- Recent Data --}}
    <div class="grid lg:grid-cols-2 gap-8">
        {{-- Recent Missions --}}
        <div class="glass-fusion dark:bg-gray-800/50 rounded-2xl shadow-lg backdrop-blur-sm border border-white/20 dark:border-gray-700/50 overflow-hidden">
            <div class="p-5 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    🎬 ภารกิจล่าสุด
                </h2>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($recentMissions as $mission)
                <a href="{{ route('admin.video-missions.show', $mission) }}" class="block p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                    <div class="flex items-center gap-3">
                        @if($mission->thumbnail_url)
                        <img src="{{ $mission->thumbnail_url }}" alt="" class="w-16 h-10 object-cover rounded-lg">
                        @else
                        <div class="w-16 h-10 bg-gray-200 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                            <span class="text-xl">{{ $mission->icon ?? '🎬' }}</span>
                        </div>
                        @endif
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-900 dark:text-white truncate">{{ $mission->display_title }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $mission->reward_summary }}</p>
                        </div>
                        <span class="px-2 py-1 text-xs rounded-full {{ $mission->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                            {{ $mission->status_label }}
                        </span>
                    </div>
                </a>
                @empty
                <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                    ยังไม่มีภารกิจ
                </div>
                @endforelse
            </div>
            <div class="p-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                <a href="{{ route('admin.video-missions.missions') }}" class="text-pink-600 dark:text-pink-400 hover:underline text-sm font-medium">
                    ดูทั้งหมด →
                </a>
            </div>
        </div>

        {{-- Recent Completions --}}
        <div class="glass-fusion dark:bg-gray-800/50 rounded-2xl shadow-lg backdrop-blur-sm border border-white/20 dark:border-gray-700/50 overflow-hidden">
            <div class="p-5 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    ✓ การทำภารกิจล่าสุด
                </h2>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($recentCompletions as $completion)
                <a href="{{ route('admin.video-missions.completion', $completion) }}" class="block p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gray-200 dark:bg-gray-700 rounded-full flex items-center justify-center">
                            <span class="text-lg">{{ $completion->status_icon }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-900 dark:text-white truncate">
                                {{ $completion->user->name ?? 'Unknown' }}
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                                {{ $completion->mission->display_title ?? 'Unknown Mission' }}
                            </p>
                        </div>
                        <div class="text-right">
                            <span class="px-2 py-1 text-xs rounded-full
                                @if($completion->status === 'verified') bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300
                                @elseif($completion->status === 'failed') bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300
                                @elseif($completion->status === 'completed') bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300
                                @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                                @endif">
                                {{ $completion->status_label }}
                            </span>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                {{ $completion->created_at->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                </a>
                @empty
                <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                    ยังไม่มีการทำภารกิจ
                </div>
                @endforelse
            </div>
            <div class="p-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                <a href="{{ route('admin.video-missions.completions') }}" class="text-pink-600 dark:text-pink-400 hover:underline text-sm font-medium">
                    ดูทั้งหมด →
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
