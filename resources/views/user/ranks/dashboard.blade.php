@extends('layouts.user-arrow-x')

@section('title', 'ระดับของฉัน')

@section('content')
{{--
/**
 * User Rank Dashboard
 *
 * แสดงข้อมูล Rank ของผู้ใช้พร้อมเงื่อนไขและรางวัลอย่างครบถ้วน
 * - Rank ปัจจุบันและ Rank ถัดไป
 * - เงื่อนไขในการเลื่อนระดับ (Requirements)
 * - รางวัลและสิทธิพิเศษ (Bonuses & Privileges)
 * - ความคืบหน้า
 *
 * @version 2.0.0
 * @date 2025-11-26
 */
--}}

<div class="space-y-6 pb-20 lg:pb-6">
    @php
        $rankBadges = [
            1 => '🥉', 2 => '🥈', 3 => '🥇', 4 => '💎',
            5 => '💠', 6 => '👑', 7 => '🏆', 8 => '⭐',
        ];
        $currentLevel = $currentRank->level ?? 1;
        $currentBadge = $rankBadges[$currentLevel] ?? '🏅';
        $nextLevel = $nextRank->level ?? null;
        $nextBadge = $nextLevel ? ($rankBadges[$nextLevel] ?? '🏅') : null;
    @endphp

    {{-- Hero Header with Current Rank --}}
    <div class="relative overflow-hidden rounded-3xl shadow-2xl">
        {{-- Dynamic Background based on Rank --}}
        <div class="absolute inset-0 bg-gradient-to-br
            @if($currentLevel >= 7) from-purple-600 via-pink-600 to-rose-600
            @elseif($currentLevel >= 5) from-cyan-500 via-blue-600 to-indigo-600
            @elseif($currentLevel >= 3) from-yellow-400 via-amber-500 to-orange-500
            @else from-gray-500 via-slate-600 to-gray-700
            @endif"></div>
        <div class="absolute inset-0 opacity-20">
            <div class="absolute top-0 right-0 w-80 h-80 bg-white rounded-full -translate-y-1/2 translate-x-1/2"></div>
            <div class="absolute bottom-0 left-0 w-60 h-60 bg-white rounded-full translate-y-1/2 -translate-x-1/2"></div>
        </div>

        {{-- Animated Particles for High Ranks --}}
        @if($currentLevel >= 5)
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            @for($i = 0; $i < 15; $i++)
            <div class="absolute w-2 h-2 bg-white rounded-full opacity-60 animate-float"
                 style="left: {{ rand(5, 95) }}%; top: {{ rand(5, 95) }}%; animation-delay: {{ $i * 0.3 }}s;"></div>
            @endfor
        </div>
        @endif

        <div class="relative z-10 p-8 md:p-12 text-white">
            <div class="flex flex-col lg:flex-row items-center gap-8">
                {{-- Avatar with Rank Frame --}}
                <div class="flex-shrink-0">
                    <x-rank-avatar
                        :user="$user"
                        :rank="$currentRank"
                        size="2xl"
                        :show-badge="true"
                    />
                </div>

                {{-- Rank Info --}}
                <div class="flex-1 text-center lg:text-left">
                    <div class="mb-2 text-sm font-semibold text-white/80 uppercase tracking-wider">
                        {{ __('ระดับปัจจุบันของคุณ') }}
                    </div>
                    <h1 class="text-4xl md:text-5xl font-bold mb-3 flex items-center justify-center lg:justify-start gap-4">
                        <span class="text-6xl">{{ $currentBadge }}</span>
                        <span>{{ $currentRank->name_th ?? $currentRank->name ?? 'ไม่มีระดับ' }}</span>
                    </h1>
                    <p class="text-xl text-white/80 mb-4">Level {{ $currentLevel }}</p>

                    @if($currentRank?->description_th ?? $currentRank?->description)
                    <p class="text-white/70 max-w-2xl">
                        {{ $currentRank->description_th ?? $currentRank->description }}
                    </p>
                    @endif

                    {{-- Stats Row --}}
                    <div class="flex flex-wrap justify-center lg:justify-start gap-4 mt-6">
                        <div class="px-6 py-3 bg-white/20 backdrop-blur-lg rounded-xl">
                            <div class="text-2xl font-bold">{{ number_format($user->rank_points ?? 0) }}</div>
                            <div class="text-sm text-white/80">คะแนนสะสม</div>
                        </div>
                        <div class="px-6 py-3 bg-white/20 backdrop-blur-lg rounded-xl">
                            <div class="text-2xl font-bold">#{{ $leaderboardPosition }}</div>
                            <div class="text-sm text-white/80">อันดับในกระดาน</div>
                        </div>
                        @if($currentRank?->commission_rate)
                        <div class="px-6 py-3 bg-white/20 backdrop-blur-lg rounded-xl">
                            <div class="text-2xl font-bold">{{ $currentRank->commission_rate }}%</div>
                            <div class="text-sm text-white/80">ค่าคอมมิชชั่น</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Current Rank Benefits --}}
    @if($currentRankBonuses->count() > 0 || count($currentRankPrivileges) > 0)
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-green-500 to-emerald-600 text-white">
            <h2 class="text-xl font-bold flex items-center gap-2">
                <span>🎁</span>
                <span>สิทธิพิเศษของคุณในระดับปัจจุบัน</span>
            </h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                {{-- Bonuses --}}
                @foreach($currentRankBonuses as $bonus)
                <div class="flex items-start gap-4 p-4 bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl border border-green-200 dark:border-green-800">
                    <div class="flex-shrink-0 w-12 h-12 bg-green-500 rounded-xl flex items-center justify-center text-white text-xl">
                        @if($bonus->bonus_type === 'one_time')
                            💰
                        @elseif($bonus->bonus_type === 'monthly')
                            📆
                        @elseif($bonus->bonus_type === 'commission')
                            📈
                        @elseif($bonus->bonus_type === 'multiplier')
                            ✖️
                        @else
                            🎁
                        @endif
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-white">
                            {{ $bonus->name_th ?? $bonus->name }}
                        </h3>
                        @if($bonus->amount > 0)
                            <p class="text-sm text-green-600 font-semibold">฿{{ number_format($bonus->amount, 2) }}</p>
                        @elseif($bonus->percentage > 0)
                            <p class="text-sm text-green-600 font-semibold">{{ $bonus->percentage }}%</p>
                        @endif
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                            {{ $bonus->description_th ?? $bonus->description }}
                        </p>
                    </div>
                </div>
                @endforeach

                {{-- Privileges --}}
                @foreach($currentRankPrivileges as $key => $privilege)
                <div class="flex items-start gap-4 p-4 bg-gradient-to-br from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 rounded-xl border border-purple-200 dark:border-purple-800">
                    <div class="flex-shrink-0 w-12 h-12 bg-purple-500 rounded-xl flex items-center justify-center text-white text-2xl">
                        {{ $privilege['icon'] ?? '⭐' }}
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-white">
                            {{ $privilege['name_th'] ?? $privilege['name'] }}
                        </h3>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                            {{ $privilege['description_th'] ?? $privilege['description'] }}
                        </p>
                    </div>
                </div>
                @endforeach

                @if($currentRankBonuses->count() === 0 && count($currentRankPrivileges) === 0)
                <div class="col-span-full text-center py-8 text-gray-500 dark:text-gray-400">
                    ยังไม่มีสิทธิพิเศษที่กำหนดไว้สำหรับระดับนี้
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- Progress to Next Rank --}}
    @if($nextRank)
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-blue-500 to-indigo-600 text-white">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold flex items-center gap-2">
                    <span>🎯</span>
                    <span>ความคืบหน้าสู่ระดับถัดไป</span>
                </h2>
                <div class="flex items-center gap-2">
                    <span class="text-3xl">{{ $nextBadge }}</span>
                    <span class="font-semibold">{{ $nextRank->name_th ?? $nextRank->name }}</span>
                </div>
            </div>
        </div>

        <div class="p-6">
            {{-- Overall Progress Bar --}}
            @if($nextRankProgress)
            <div class="mb-8">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">ความคืบหน้ารวม</span>
                    <span class="text-lg font-bold text-blue-600">
                        {{ number_format($nextRankProgress->progress_percentage ?? 0, 1) }}%
                    </span>
                </div>
                <div class="h-6 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full transition-all duration-500 relative"
                         style="width: {{ min(100, $nextRankProgress->progress_percentage ?? 0) }}%">
                        <div class="absolute inset-0 bg-white/30 animate-pulse"></div>
                    </div>
                </div>
                <p class="text-center text-sm text-gray-600 dark:text-gray-400 mt-2">
                    อีก
                    <span class="font-semibold text-blue-600">{{ number_format(max(0, ($nextRankProgress->target_points ?? 0) - ($user->rank_points ?? 0))) }}</span>
                    คะแนนจะถึงระดับ
                    <span class="font-semibold">{{ $nextRank->name_th ?? $nextRank->name }}</span>
                </p>
            </div>
            @endif

            {{-- Requirements Checklist --}}
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <span>📋</span>
                <span>เงื่อนไขที่ต้องทำให้ครบ</span>
            </h3>

            @if($nextRankRequirements->count() > 0)
            <div class="space-y-4">
                @foreach($nextRankRequirements as $requirement)
                    @php
                        $progress = $nextRankRequirementsProgress[$requirement->id] ?? null;
                        $isMet = $progress['met'] ?? false;
                        $percentage = $progress['percentage'] ?? 0;
                        $current = $progress['current'] ?? 0;
                        $target = $progress['target'] ?? $requirement->target_value;

                        // Icons ตาม requirement type
                        $typeIcons = [
                            'points' => '🎯',
                            'referrals' => '👥',
                            'sales' => '💰',
                            'active_referrals' => '✅',
                            'team_sales' => '📊',
                            'consecutive_months' => '📅',
                            'diamond_legs' => '💎',
                            'crown_legs' => '👑',
                            'royal_legs' => '🏆',
                        ];
                        $icon = $typeIcons[$requirement->requirement_type] ?? '📌';
                    @endphp

                    <div class="relative p-4 rounded-xl border-2 transition-all
                        {{ $isMet
                            ? 'bg-green-50 dark:bg-green-900/20 border-green-400 dark:border-green-600'
                            : 'bg-gray-50 dark:bg-gray-700/50 border-gray-200 dark:border-gray-600'
                        }}">
                        <div class="flex items-start gap-4">
                            {{-- Status Icon --}}
                            <div class="flex-shrink-0 w-12 h-12 rounded-xl flex items-center justify-center text-2xl
                                {{ $isMet ? 'bg-green-500 text-white' : 'bg-gray-300 dark:bg-gray-600' }}">
                                @if($isMet)
                                    ✓
                                @else
                                    {{ $icon }}
                                @endif
                            </div>

                            {{-- Content --}}
                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="font-bold text-gray-900 dark:text-white">
                                        {{ $requirement->name_th ?? $requirement->name }}
                                        @if($requirement->is_required)
                                            <span class="text-red-500">*</span>
                                        @endif
                                    </h4>
                                    @if($isMet)
                                        <span class="px-3 py-1 bg-green-500 text-white text-xs rounded-full font-semibold">
                                            ✓ ผ่านแล้ว
                                        </span>
                                    @else
                                        <span class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ number_format($current, 0) }} / {{ number_format($target, 0) }}
                                            {{ $requirement->unit }}
                                        </span>
                                    @endif
                                </div>

                                @if($requirement->description_th ?? $requirement->description)
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                    {{ $requirement->description_th ?? $requirement->description }}
                                </p>
                                @endif

                                {{-- Progress Bar --}}
                                @if(!$isMet)
                                <div class="h-3 bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-blue-400 to-blue-600 rounded-full transition-all duration-500"
                                         style="width: {{ min(100, $percentage) }}%"></div>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 text-right">
                                    {{ number_format($percentage, 1) }}%
                                </p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            @else
            <div class="text-center py-8 text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                <div class="text-4xl mb-2">📋</div>
                <p>ยังไม่มีเงื่อนไขที่กำหนดไว้สำหรับระดับนี้</p>
            </div>
            @endif

            {{-- What You'll Get --}}
            @if($nextRankBonuses->count() > 0 || count($nextRankPrivileges) > 0)
            <div class="mt-8">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span>🎁</span>
                    <span>สิ่งที่คุณจะได้รับเมื่อเลื่อนระดับ</span>
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Next Rank Bonuses --}}
                    @foreach($nextRankBonuses as $bonus)
                    <div class="flex items-center gap-3 p-4 bg-gradient-to-r from-amber-50 to-yellow-50 dark:from-amber-900/20 dark:to-yellow-900/20 rounded-xl border border-amber-200 dark:border-amber-800">
                        <div class="text-3xl">
                            @if($bonus->bonus_type === 'one_time')
                                💰
                            @elseif($bonus->bonus_type === 'monthly')
                                📆
                            @elseif($bonus->bonus_type === 'commission')
                                📈
                            @else
                                🎁
                            @endif
                        </div>
                        <div>
                            <div class="font-bold text-gray-900 dark:text-white">
                                {{ $bonus->name_th ?? $bonus->name }}
                            </div>
                            @if($bonus->amount > 0)
                                <div class="text-amber-600 font-semibold">฿{{ number_format($bonus->amount, 2) }}</div>
                            @elseif($bonus->percentage > 0)
                                <div class="text-amber-600 font-semibold">{{ $bonus->percentage }}%</div>
                            @endif
                        </div>
                    </div>
                    @endforeach

                    {{-- Next Rank Privileges --}}
                    @foreach($nextRankPrivileges as $key => $privilege)
                    <div class="flex items-center gap-3 p-4 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl border border-purple-200 dark:border-purple-800">
                        <div class="text-3xl">{{ $privilege['icon'] ?? '⭐' }}</div>
                        <div>
                            <div class="font-bold text-gray-900 dark:text-white">
                                {{ $privilege['name_th'] ?? $privilege['name'] }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $privilege['description_th'] ?? $privilege['description'] }}
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
    @else
    {{-- At Max Rank --}}
    <div class="bg-gradient-to-br from-purple-600 via-pink-600 to-rose-600 rounded-2xl shadow-xl p-8 text-white text-center">
        <div class="text-6xl mb-4">🏆</div>
        <h2 class="text-3xl font-bold mb-2">ยินดีด้วย!</h2>
        <p class="text-xl text-white/80">คุณอยู่ในระดับสูงสุดแล้ว</p>
        <div class="mt-6">
            <div class="inline-flex items-center gap-2 px-6 py-3 bg-white/20 backdrop-blur-lg rounded-xl">
                <span class="text-4xl">{{ $currentBadge }}</span>
                <span class="text-2xl font-bold">{{ $currentRank->name_th ?? $currentRank->name }}</span>
            </div>
        </div>
    </div>
    @endif

    {{-- Rank Roadmap --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-indigo-500 to-purple-600 text-white">
            <h2 class="text-xl font-bold flex items-center gap-2">
                <span>🗺️</span>
                <span>เส้นทางสู่ความสำเร็จ</span>
            </h2>
        </div>

        <div class="p-6">
            <div class="relative">
                {{-- Vertical Line --}}
                <div class="absolute left-8 top-0 bottom-0 w-1 bg-gray-200 dark:bg-gray-700"></div>

                <div class="space-y-6">
                    @foreach($allRanks as $rank)
                        @php
                            $isCurrentRank = $currentRank && $currentRank->id === $rank->id;
                            $isAchieved = $currentRank && $currentRank->level >= $rank->level;
                            $badge = $rankBadges[$rank->level] ?? '🏅';
                        @endphp

                        <div class="relative flex items-start gap-6">
                            {{-- Icon Circle --}}
                            <div class="flex-shrink-0 w-16 h-16 rounded-full flex items-center justify-center z-10 shadow-lg
                                @if($isCurrentRank)
                                    bg-gradient-to-br from-yellow-400 to-orange-500 ring-4 ring-yellow-300 animate-pulse
                                @elseif($isAchieved)
                                    bg-gradient-to-br from-green-400 to-emerald-600
                                @else
                                    bg-gray-300 dark:bg-gray-600
                                @endif">
                                <span class="text-2xl">{{ $badge }}</span>
                            </div>

                            {{-- Content Card --}}
                            <div class="flex-1 p-4 rounded-xl border-2 transition-all
                                @if($isCurrentRank)
                                    bg-yellow-50 dark:bg-yellow-900/20 border-yellow-400
                                @elseif($isAchieved)
                                    bg-green-50 dark:bg-green-900/20 border-green-300
                                @else
                                    bg-gray-50 dark:bg-gray-700/50 border-gray-200 dark:border-gray-600
                                @endif">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <div class="flex items-center gap-2 mb-1">
                                            <h3 class="font-bold text-gray-900 dark:text-white text-lg">
                                                {{ $rank->name_th ?? $rank->name }}
                                            </h3>
                                            @if($isCurrentRank)
                                                <span class="px-2 py-1 bg-yellow-500 text-white text-xs rounded-full">คุณอยู่ที่นี่</span>
                                            @elseif($isAchieved)
                                                <span class="px-2 py-1 bg-green-500 text-white text-xs rounded-full">✓ ผ่านแล้ว</span>
                                            @endif
                                        </div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Level {{ $rank->level }}</p>
                                    </div>

                                    @if($rank->commission_rate)
                                    <div class="text-right">
                                        <div class="text-lg font-bold text-blue-600">{{ $rank->commission_rate }}%</div>
                                        <div class="text-xs text-gray-500">ค่าคอมมิชชั่น</div>
                                    </div>
                                    @endif
                                </div>

                                {{-- Preview of rewards --}}
                                @if($rank->activeBonuses->count() > 0)
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach($rank->activeBonuses->take(3) as $bonus)
                                        <span class="inline-flex items-center gap-1 px-2 py-1 bg-white dark:bg-gray-800 rounded-lg text-xs">
                                            🎁 {{ $bonus->name_th ?? $bonus->name }}
                                        </span>
                                    @endforeach
                                    @if($rank->activeBonuses->count() > 3)
                                        <span class="px-2 py-1 text-xs text-gray-500">
                                            +{{ $rank->activeBonuses->count() - 3 }} อื่นๆ
                                        </span>
                                    @endif
                                </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <a href="{{ route('user.ranks.progress') }}"
           class="group bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl shadow-xl p-6 text-white hover:shadow-2xl transition block">
            <div class="flex items-center gap-4 mb-3">
                <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center group-hover:scale-110 transition">
                    <span class="text-3xl">📊</span>
                </div>
                <div>
                    <h3 class="text-xl font-bold">ดูความคืบหน้า</h3>
                    <p class="text-blue-100 text-sm">ติดตามความก้าวหน้าทุกระดับ</p>
                </div>
            </div>
        </a>

        <a href="{{ route('user.ranks.leaderboard') }}"
           class="group bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl shadow-xl p-6 text-white hover:shadow-2xl transition block">
            <div class="flex items-center gap-4 mb-3">
                <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center group-hover:scale-110 transition">
                    <span class="text-3xl">🏆</span>
                </div>
                <div>
                    <h3 class="text-xl font-bold">กระดานผู้นำ</h3>
                    <p class="text-purple-100 text-sm">เปรียบเทียบกับสมาชิกอื่น</p>
                </div>
            </div>
        </a>

        <a href="{{ route('user.virtual-id-card') }}"
           class="group bg-gradient-to-br from-orange-500 to-red-500 rounded-2xl shadow-xl p-6 text-white hover:shadow-2xl transition block">
            <div class="flex items-center gap-4 mb-3">
                <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center group-hover:scale-110 transition">
                    <span class="text-3xl">🪪</span>
                </div>
                <div>
                    <h3 class="text-xl font-bold">บัตรสมาชิก</h3>
                    <p class="text-orange-100 text-sm">ดูบัตรประจำตัวเสมือนของคุณ</p>
                </div>
            </div>
        </a>
    </div>

    {{-- How to Earn Points --}}
    <div class="bg-gradient-to-br from-slate-50 to-gray-100 dark:from-gray-800 dark:to-gray-900 rounded-2xl shadow-xl p-8">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
            <span>💡</span>
            <span>วิธีสะสมคะแนนและเลื่อนระดับ</span>
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg hover:shadow-xl transition">
                <div class="w-14 h-14 bg-gradient-to-br from-green-400 to-green-600 rounded-xl flex items-center justify-center text-white text-2xl mb-4">
                    👥
                </div>
                <h3 class="font-bold text-gray-900 dark:text-white mb-2">แนะนำสมาชิกใหม่</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">เชิญเพื่อนมาสมัครสมาชิกเพื่อรับคะแนนโบนัส</p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg hover:shadow-xl transition">
                <div class="w-14 h-14 bg-gradient-to-br from-blue-400 to-blue-600 rounded-xl flex items-center justify-center text-white text-2xl mb-4">
                    💰
                </div>
                <h3 class="font-bold text-gray-900 dark:text-white mb-2">สร้างยอดขาย</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">ทำยอดขายเพื่อสะสม PV และคะแนน</p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg hover:shadow-xl transition">
                <div class="w-14 h-14 bg-gradient-to-br from-purple-400 to-purple-600 rounded-xl flex items-center justify-center text-white text-2xl mb-4">
                    📈
                </div>
                <h3 class="font-bold text-gray-900 dark:text-white mb-2">พัฒนาทีม</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">ช่วยทีมให้เติบโตเพื่อรับโบนัสทีม</p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg hover:shadow-xl transition">
                <div class="w-14 h-14 bg-gradient-to-br from-orange-400 to-orange-600 rounded-xl flex items-center justify-center text-white text-2xl mb-4">
                    🎯
                </div>
                <h3 class="font-bold text-gray-900 dark:text-white mb-2">รักษาความต่อเนื่อง</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">ทำยอดอย่างสม่ำเสมอทุกเดือน</p>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    @keyframes float {
        0%, 100% { transform: translateY(0); opacity: 0.6; }
        50% { transform: translateY(-20px); opacity: 0.3; }
    }
    .animate-float {
        animation: float 4s ease-in-out infinite;
    }
</style>
@endpush
@endsection
