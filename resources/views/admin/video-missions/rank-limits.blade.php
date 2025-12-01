@extends('layouts.admin-v3')

@section('title', 'ตั้งค่า Rank Limits')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                🏆 ตั้งค่า Rank Limits
            </h1>
            <p class="text-gray-600 dark:text-gray-400">กำหนดจำนวนภารกิจและรางวัลสูงสุดสำหรับแต่ละ Rank</p>
        </div>
        <a href="{{ route('admin.video-missions.index') }}"
           class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-white rounded-xl font-medium transition-all">
            ← กลับ Dashboard
        </a>
    </div>

    <form action="{{ route('admin.video-missions.rank-limits.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="space-y-6">
            @foreach($ranks as $rank)
            @php $limit = $rankLimits[$rank->id] ?? null; @endphp
            <div class="glass-fusion dark:bg-gray-800/50 rounded-2xl shadow-lg p-6 backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 bg-gradient-to-br from-{{ $rank->color ?? 'pink' }}-500 to-{{ $rank->color ?? 'purple' }}-500 rounded-xl flex items-center justify-center text-2xl text-white">
                        {{ $rank->icon ?? '🏅' }}
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $rank->name }}</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Level {{ $rank->level }}</p>
                    </div>
                </div>

                <input type="hidden" name="limits[{{ $loop->index }}][rank_id]" value="{{ $rank->id }}">

                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4">
                    {{-- Mission Limits --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ภารกิจ/วัน</label>
                        <input type="number" name="limits[{{ $loop->index }}][daily_mission_limit]"
                               value="{{ old("limits.{$loop->index}.daily_mission_limit", $limit->daily_mission_limit ?? 10) }}"
                               min="1" required
                               class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ภารกิจ/สัปดาห์</label>
                        <input type="number" name="limits[{{ $loop->index }}][weekly_mission_limit]"
                               value="{{ old("limits.{$loop->index}.weekly_mission_limit", $limit->weekly_mission_limit ?? 50) }}"
                               min="1" required
                               class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ภารกิจ/เดือน</label>
                        <input type="number" name="limits[{{ $loop->index }}][monthly_mission_limit]"
                               value="{{ old("limits.{$loop->index}.monthly_mission_limit", $limit->monthly_mission_limit ?? 200) }}"
                               min="1" required
                               class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Reward Multiplier</label>
                        <input type="number" name="limits[{{ $loop->index }}][reward_multiplier]"
                               value="{{ old("limits.{$loop->index}.reward_multiplier", $limit->reward_multiplier ?? 1.0) }}"
                               min="0.1" max="10" step="0.1" required
                               class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500">
                    </div>
                </div>

                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4 mt-4">
                    {{-- Reward Limits --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">รางวัล/วัน (บาท)</label>
                        <input type="number" name="limits[{{ $loop->index }}][daily_reward_limit]"
                               value="{{ old("limits.{$loop->index}.daily_reward_limit", $limit->daily_reward_limit ?? 500) }}"
                               min="0" step="0.01"
                               class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">รางวัล/สัปดาห์</label>
                        <input type="number" name="limits[{{ $loop->index }}][weekly_reward_limit]"
                               value="{{ old("limits.{$loop->index}.weekly_reward_limit", $limit->weekly_reward_limit ?? 2000) }}"
                               min="0" step="0.01"
                               class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">รางวัล/เดือน</label>
                        <input type="number" name="limits[{{ $loop->index }}][monthly_reward_limit]"
                               value="{{ old("limits.{$loop->index}.monthly_reward_limit", $limit->monthly_reward_limit ?? 5000) }}"
                               min="0" step="0.01"
                               class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bonus EXP %</label>
                        <input type="number" name="limits[{{ $loop->index }}][bonus_exp_percentage]"
                               value="{{ old("limits.{$loop->index}.bonus_exp_percentage", $limit->bonus_exp_percentage ?? 0) }}"
                               min="0" max="100"
                               class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500">
                    </div>
                </div>

                <div class="flex flex-wrap gap-6 mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="limits[{{ $loop->index }}][can_access_premium_missions]" value="1"
                               {{ old("limits.{$loop->index}.can_access_premium_missions", $limit->can_access_premium_missions ?? false) ? 'checked' : '' }}
                               class="rounded border-gray-300 dark:border-gray-600 text-pink-600 focus:ring-pink-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">เข้าถึงภารกิจ Premium</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="limits[{{ $loop->index }}][can_access_featured_missions]" value="1"
                               {{ old("limits.{$loop->index}.can_access_featured_missions", $limit->can_access_featured_missions ?? true) ? 'checked' : '' }}
                               class="rounded border-gray-300 dark:border-gray-600 text-pink-600 focus:ring-pink-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">เข้าถึงภารกิจแนะนำ</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="limits[{{ $loop->index }}][skip_cooldown]" value="1"
                               {{ old("limits.{$loop->index}.skip_cooldown", $limit->skip_cooldown ?? false) ? 'checked' : '' }}
                               class="rounded border-gray-300 dark:border-gray-600 text-pink-600 focus:ring-pink-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">ข้าม Cooldown</span>
                    </label>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Submit --}}
        <div class="flex justify-end mt-6">
            <button type="submit"
                    class="px-6 py-3 bg-gradient-to-r from-pink-600 to-purple-600 hover:from-pink-700 hover:to-purple-700 text-white rounded-xl font-medium transition-all shadow-lg hover:shadow-pink-500/25">
                💾 บันทึกการตั้งค่า
            </button>
        </div>
    </form>
</div>
@endsection
