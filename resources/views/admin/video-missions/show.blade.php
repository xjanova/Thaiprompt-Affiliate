@extends('layouts.admin-v3')

@section('title', 'ภารกิจ: ' . $mission->display_title)

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 bg-gradient-to-br from-pink-500 to-purple-500 rounded-2xl flex items-center justify-center text-3xl text-white">
                {{ $mission->icon ?? '🎬' }}
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $mission->display_title }}</h1>
                <p class="text-gray-600 dark:text-gray-400">
                    {{ $mission->video_type }} • {{ $mission->required_watch_seconds }} วินาที
                    @if($mission->is_active)
                    <span class="ml-2 px-2 py-0.5 text-xs bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300 rounded-full">เปิดใช้งาน</span>
                    @else
                    <span class="ml-2 px-2 py-0.5 text-xs bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 rounded-full">ปิดใช้งาน</span>
                    @endif
                </p>
            </div>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.video-missions.missions') }}"
               class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-white rounded-xl font-medium transition-all">
                ← กลับ
            </a>
            <a href="{{ route('admin.video-missions.edit', $mission) }}"
               class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-xl font-medium transition-all">
                ✏️ แก้ไข
            </a>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        {{-- Main Info --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Stats --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="glass-fusion dark:bg-gray-800/50 rounded-xl p-4 backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
                    <p class="text-xs text-gray-500 dark:text-gray-400">ทำสำเร็จ</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_completions']) }}</p>
                </div>
                <div class="glass-fusion dark:bg-gray-800/50 rounded-xl p-4 backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
                    <p class="text-xs text-gray-500 dark:text-gray-400">ผู้ใช้</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['unique_users']) }}</p>
                </div>
                <div class="glass-fusion dark:bg-gray-800/50 rounded-xl p-4 backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
                    <p class="text-xs text-gray-500 dark:text-gray-400">วันนี้</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['completions_today']) }}</p>
                </div>
                <div class="glass-fusion dark:bg-gray-800/50 rounded-xl p-4 backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
                    <p class="text-xs text-gray-500 dark:text-gray-400">น่าสงสัย</p>
                    <p class="text-2xl font-bold {{ $stats['suspicious_count'] > 0 ? 'text-red-600' : 'text-gray-900 dark:text-white' }}">{{ number_format($stats['suspicious_count']) }}</p>
                </div>
            </div>

            {{-- Description --}}
            <div class="glass-fusion dark:bg-gray-800/50 rounded-2xl shadow-lg p-6 backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">📝 รายละเอียด</h2>
                <div class="prose dark:prose-invert max-w-none">
                    <p>{{ $mission->display_description ?: 'ไม่มีคำอธิบาย' }}</p>
                </div>
            </div>

            {{-- Video Preview --}}
            <div class="glass-fusion dark:bg-gray-800/50 rounded-2xl shadow-lg p-6 backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">🎥 ตัวอย่างวิดีโอ</h2>
                @if($mission->video_type === 'youtube' && $mission->video_id)
                <div class="aspect-video rounded-xl overflow-hidden bg-gray-900">
                    <iframe src="https://www.youtube.com/embed/{{ $mission->video_id }}"
                            class="w-full h-full" frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen></iframe>
                </div>
                @else
                <div class="aspect-video rounded-xl bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                    <p class="text-gray-500 dark:text-gray-400">{{ $mission->video_url }}</p>
                </div>
                @endif
            </div>

            {{-- Recent Completions --}}
            <div class="glass-fusion dark:bg-gray-800/50 rounded-2xl shadow-lg overflow-hidden backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">📋 การทำล่าสุด</h2>
                </div>
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($recentCompletions as $completion)
                    <div class="flex items-center gap-4 p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <div class="w-10 h-10 bg-gray-200 dark:bg-gray-700 rounded-full flex items-center justify-center">
                            {{ $completion->status_icon }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-900 dark:text-white truncate">
                                {{ $completion->user->name ?? 'Unknown' }}
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $completion->created_at->format('d/m/Y H:i') }} •
                                ดู {{ $completion->watch_percentage }}%
                                @if($completion->is_suspicious)
                                <span class="text-red-500">⚠️</span>
                                @endif
                            </p>
                        </div>
                        <div class="text-right">
                            <span class="px-2 py-1 text-xs rounded-full {{ $completion->status === 'verified' ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300' : ($completion->status === 'rejected' ? 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300') }}">
                                {{ $completion->status_label }}
                            </span>
                        </div>
                    </div>
                    @empty
                    <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                        ยังไม่มีคนทำภารกิจนี้
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Reward Info --}}
            <div class="glass-fusion dark:bg-gray-800/50 rounded-2xl shadow-lg p-6 backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">🎁 รางวัล</h2>
                <div class="space-y-3">
                    @if($mission->reward_money > 0)
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600 dark:text-gray-400">💵 เงิน</span>
                        <span class="font-semibold text-green-600 dark:text-green-400">฿{{ number_format($mission->reward_money, 2) }}</span>
                    </div>
                    @endif
                    @if($mission->reward_coins > 0)
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600 dark:text-gray-400">🪙 Coins</span>
                        <span class="font-semibold text-yellow-600 dark:text-yellow-400">{{ number_format($mission->reward_coins) }}</span>
                    </div>
                    @endif
                    @if($mission->reward_points > 0)
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600 dark:text-gray-400">⭐ Points</span>
                        <span class="font-semibold text-blue-600 dark:text-blue-400">{{ number_format($mission->reward_points) }}</span>
                    </div>
                    @endif
                    @if($mission->reward_exp > 0)
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600 dark:text-gray-400">📈 EXP</span>
                        <span class="font-semibold text-purple-600 dark:text-purple-400">{{ number_format($mission->reward_exp) }}</span>
                    </div>
                    @endif
                    <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            แจกไปแล้ว: <span class="font-semibold text-gray-900 dark:text-white">฿{{ number_format($stats['total_rewards'], 2) }}</span>
                        </p>
                    </div>
                </div>
            </div>

            {{-- Watch Requirements --}}
            <div class="glass-fusion dark:bg-gray-800/50 rounded-2xl shadow-lg p-6 backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">⏱️ เงื่อนไขการดู</h2>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">ต้องดู</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ $mission->required_watch_seconds }} วินาที</span>
                    </div>
                    @if($mission->required_watch_percentage)
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">หรือ</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ $mission->required_watch_percentage }}% ของวิดีโอ</span>
                    </div>
                    @endif
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">ความยาววิดีโอ</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ $mission->video_duration_seconds ?? '-' }} วินาที</span>
                    </div>
                </div>
            </div>

            {{-- Frequency --}}
            <div class="glass-fusion dark:bg-gray-800/50 rounded-2xl shadow-lg p-6 backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">🔄 ความถี่</h2>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">ทำได้</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ $mission->frequency_label }}</span>
                    </div>
                    @if($mission->cooldown_minutes)
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Cooldown</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ $mission->cooldown_minutes }} นาที</span>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Eligibility --}}
            <div class="glass-fusion dark:bg-gray-800/50 rounded-2xl shadow-lg p-6 backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">👤 คุณสมบัติ</h2>
                <div class="space-y-2 text-sm">
                    @if($mission->minRank)
                    <p class="text-gray-600 dark:text-gray-400">Rank ขั้นต่ำ: <span class="font-medium text-gray-900 dark:text-white">{{ $mission->minRank->name }}</span></p>
                    @endif
                    @if($mission->maxRank)
                    <p class="text-gray-600 dark:text-gray-400">Rank สูงสุด: <span class="font-medium text-gray-900 dark:text-white">{{ $mission->maxRank->name }}</span></p>
                    @endif
                    @if($mission->min_days_registered)
                    <p class="text-gray-600 dark:text-gray-400">สมาชิกอย่างน้อย: <span class="font-medium text-gray-900 dark:text-white">{{ $mission->min_days_registered }} วัน</span></p>
                    @endif
                    <div class="flex flex-wrap gap-2 pt-2">
                        @if($mission->require_kyc)
                        <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300 rounded">KYC</span>
                        @endif
                        @if($mission->require_email_verified)
                        <span class="px-2 py-1 text-xs bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300 rounded">Email</span>
                        @endif
                        @if($mission->require_phone_verified)
                        <span class="px-2 py-1 text-xs bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-300 rounded">Phone</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Anti-Cheat --}}
            <div class="glass-fusion dark:bg-gray-800/50 rounded-2xl shadow-lg p-6 backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">🛡️ Anti-Cheat</h2>
                <div class="space-y-2 text-sm">
                    <div class="flex items-center gap-2">
                        @if($mission->anti_cheat_enabled)
                        <span class="text-green-500">✅</span>
                        @else
                        <span class="text-gray-400">⭕</span>
                        @endif
                        <span class="text-gray-600 dark:text-gray-400">Anti-Cheat</span>
                    </div>
                    <div class="flex items-center gap-2">
                        @if($mission->require_focus)
                        <span class="text-green-500">✅</span>
                        @else
                        <span class="text-gray-400">⭕</span>
                        @endif
                        <span class="text-gray-600 dark:text-gray-400">ต้อง Focus</span>
                    </div>
                    <div class="flex items-center gap-2">
                        @if($mission->detect_tab_switch)
                        <span class="text-green-500">✅</span>
                        @else
                        <span class="text-gray-400">⭕</span>
                        @endif
                        <span class="text-gray-600 dark:text-gray-400">ตรวจจับ Tab Switch</span>
                    </div>
                    @if($mission->max_tab_switches)
                    <p class="text-gray-500 dark:text-gray-400 text-xs pt-1">
                        อนุญาตสลับ Tab ได้ {{ $mission->max_tab_switches }} ครั้ง
                    </p>
                    @endif
                </div>
            </div>

            {{-- Meta --}}
            <div class="glass-fusion dark:bg-gray-800/50 rounded-2xl shadow-lg p-6 backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">📋 ข้อมูลเพิ่มเติม</h2>
                <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <p>สร้างโดย: {{ $mission->creator->name ?? 'System' }}</p>
                    <p>สร้างเมื่อ: {{ $mission->created_at->format('d/m/Y H:i') }}</p>
                    @if($mission->updated_at != $mission->created_at)
                    <p>แก้ไขล่าสุด: {{ $mission->updated_at->format('d/m/Y H:i') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
