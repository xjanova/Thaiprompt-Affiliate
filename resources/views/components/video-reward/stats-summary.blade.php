{{--
    Video Reward Stats Summary Component

    แสดงสรุปสถิติทั้งหมดของระบบ Video Rewards แบบชัดเจน

    Usage:
    <x-video-reward.stats-summary :user="$user" />

    Parameters:
    - user: User object (required)
    - showRanking: boolean - แสดงอันดับ
    - compact: boolean - แสดงแบบกระชับ
--}}

@props([
    'user',
    'showRanking' => false,
    'compact' => false,
])

@php
    // ดึงข้อมูล level และ coins
    $videoLevel = $user->videoLevel;
    $videoCoin = $user->videoCoin;
    $currentLevel = $videoLevel?->currentLevel;
    $nextLevel = $currentLevel?->next_level;

    // Level Info
    $levelNumber = $currentLevel?->level ?? 1;
    $levelName = $currentLevel?->name ?? 'เริ่มต้น';
    $stars = $currentLevel?->stars ?? 1;
    $starColor = $currentLevel?->star_color ?? 'bronze';
    $starHex = $currentLevel?->star_color_hex ?? '#CD7F32';
    $starGradient = $currentLevel?->star_gradient_class ?? 'from-amber-600 to-amber-800';
    $titlePrefix = $currentLevel?->title_prefix ?? 'มือใหม่';
    $rewardMultiplier = $currentLevel?->reward_multiplier ?? 1.0;

    // EXP Info
    $currentExp = $videoLevel?->current_exp ?? 0;
    $totalExp = $videoLevel?->total_exp ?? 0;
    $requiredExp = $nextLevel?->required_exp ?? 100;
    $expToNext = max(0, $requiredExp - $currentExp);
    $progress = $nextLevel ? min(100, ($currentExp / $requiredExp) * 100) : 100;
    $isMaxLevel = !$nextLevel;

    // Stats
    $videosWatched = $videoLevel?->videos_watched ?? 0;
    $questsCompleted = $videoLevel?->quests_completed ?? 0;

    // Coins Info
    $coinBalance = $videoCoin?->balance ?? 0;
    $lifetimeEarned = $videoCoin?->lifetime_earned ?? 0;
    $lifetimeSpent = $videoCoin?->lifetime_spent ?? 0;
    $lifetimeExchanged = $videoCoin?->lifetime_exchanged ?? 0;
    $totalCoinsEarned = $videoLevel?->total_coins_earned ?? 0;
@endphp

<div {{ $attributes->merge(['class' => 'space-y-6']) }}>
    {{-- Header with Level and Stars --}}
    <div class="relative overflow-hidden bg-gradient-to-br {{ $starGradient }} rounded-2xl p-6 text-white shadow-xl">
        {{-- Background Pattern --}}
        <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                <defs>
                    <pattern id="stars-pattern" patternUnits="userSpaceOnUse" width="30" height="30">
                        <path d="M15 2l3.09 6.26L25 9.27l-5 4.87 1.18 6.88L15 17.77l-6.18 3.25L10 14.14 5 9.27l6.91-1.01L15 2z" fill="currentColor" opacity="0.3"/>
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#stars-pattern)"/>
            </svg>
        </div>

        <div class="relative flex items-center justify-between">
            {{-- Left: Level Badge --}}
            <div class="flex items-center gap-4">
                {{-- Level Circle --}}
                <div class="relative">
                    <div class="w-20 h-20 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center ring-4 ring-white/30 shadow-lg">
                        <span class="text-3xl font-black">{{ $levelNumber }}</span>
                    </div>
                    {{-- Stars below --}}
                    <div class="absolute -bottom-2 left-1/2 transform -translate-x-1/2 flex gap-0.5 bg-black/50 px-2 py-1 rounded-full">
                        @for($i = 0; $i < $stars; $i++)
                            <svg class="w-4 h-4 text-yellow-400 animate-pulse" style="animation-delay: {{ $i * 0.1 }}s" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        @endfor
                    </div>
                </div>

                {{-- Level Info --}}
                <div>
                    <p class="text-white/80 text-sm">ระดับปัจจุบัน</p>
                    <h2 class="text-2xl font-bold">{{ $titlePrefix }}</h2>
                    <p class="text-lg">{{ $levelName }}</p>
                    @if($rewardMultiplier > 1)
                    <p class="text-sm mt-1 bg-white/20 px-2 py-0.5 rounded-full inline-block">
                        โบนัสรางวัล x{{ number_format($rewardMultiplier, 2) }}
                    </p>
                    @endif
                </div>
            </div>

            {{-- Right: Total EXP --}}
            <div class="text-right">
                <p class="text-white/80 text-sm">ประสบการณ์รวม</p>
                <p class="text-3xl font-black">{{ number_format($totalExp) }}</p>
                <p class="text-sm">EXP</p>
            </div>
        </div>

        {{-- EXP Progress Bar --}}
        @if(!$isMaxLevel)
        <div class="relative mt-6">
            <div class="flex justify-between text-sm mb-2">
                <span>EXP ถัดไป: {{ $nextLevel->name }}</span>
                <span>{{ number_format($currentExp) }} / {{ number_format($requiredExp) }}</span>
            </div>
            <div class="w-full h-4 bg-black/30 rounded-full overflow-hidden">
                <div class="h-full bg-white rounded-full transition-all duration-1000 relative" style="width: {{ $progress }}%">
                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/50 to-transparent animate-shine"></div>
                </div>
            </div>
            <p class="text-center text-sm mt-2 text-white/80">
                อีก <span class="font-bold text-white">{{ number_format($expToNext) }}</span> EXP เพื่อขึ้นระดับ
            </p>
        </div>
        @else
        <div class="mt-4 text-center py-2 bg-white/20 rounded-lg">
            <span class="text-lg font-bold">ถึงระดับสูงสุดแล้ว!</span>
        </div>
        @endif
    </div>

    {{-- Stats Grid --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        {{-- Coin Balance --}}
        <div class="bg-gradient-to-br from-yellow-400 to-orange-500 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center gap-2 mb-2">
                <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.736 6.979C9.208 6.193 9.696 6 10 6c.304 0 .792.193 1.264.979a1 1 0 001.715-1.029C12.279 4.784 11.232 4 10 4s-2.279.784-2.979 1.95c-.285.475-.507 1-.67 1.55H6a1 1 0 000 2h.013a9.358 9.358 0 000 1H6a1 1 0 100 2h.351c.163.55.385 1.075.67 1.55C7.721 15.216 8.768 16 10 16s2.279-.784 2.979-1.95a1 1 0 10-1.715-1.029c-.472.786-.96.979-1.264.979-.304 0-.792-.193-1.264-.979a4.265 4.265 0 01-.264-.521H10a1 1 0 100-2H8.017a7.36 7.36 0 010-1H10a1 1 0 100-2H8.472c.08-.185.167-.36.264-.521z"/>
                    </svg>
                </div>
                <span class="text-sm font-medium">Coins คงเหลือ</span>
            </div>
            <p class="text-2xl font-black">{{ number_format($coinBalance, 2) }}</p>
        </div>

        {{-- Videos Watched --}}
        <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center gap-2 mb-2">
                <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2 6a2 2 0 012-2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zm12.553 1.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z"/>
                    </svg>
                </div>
                <span class="text-sm font-medium">วิดีโอดูแล้ว</span>
            </div>
            <p class="text-2xl font-black">{{ number_format($videosWatched) }}</p>
        </div>

        {{-- Quests Completed --}}
        <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center gap-2 mb-2">
                <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <span class="text-sm font-medium">ภารกิจสำเร็จ</span>
            </div>
            <p class="text-2xl font-black">{{ number_format($questsCompleted) }}</p>
        </div>

        {{-- Total Coins Earned --}}
        <div class="bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center gap-2 mb-2">
                <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <span class="text-sm font-medium">Coins ได้รับรวม</span>
            </div>
            <p class="text-2xl font-black">{{ number_format($lifetimeEarned, 0) }}</p>
        </div>
    </div>

    {{-- Detailed Coin Stats --}}
    @if(!$compact)
    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.736 6.979C9.208 6.193 9.696 6 10 6c.304 0 .792.193 1.264.979a1 1 0 001.715-1.029C12.279 4.784 11.232 4 10 4s-2.279.784-2.979 1.95c-.285.475-.507 1-.67 1.55H6a1 1 0 000 2h.013a9.358 9.358 0 000 1H6a1 1 0 100 2h.351c.163.55.385 1.075.67 1.55C7.721 15.216 8.768 16 10 16s2.279-.784 2.979-1.95a1 1 0 10-1.715-1.029c-.472.786-.96.979-1.264.979-.304 0-.792-.193-1.264-.979a4.265 4.265 0 01-.264-.521H10a1 1 0 100-2H8.017a7.36 7.36 0 010-1H10a1 1 0 100-2H8.472c.08-.185.167-.36.264-.521z"/>
            </svg>
            สรุปยอด Coins
        </h3>

        <div class="space-y-3">
            {{-- Balance --}}
            <div class="flex justify-between items-center p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-yellow-500 rounded-full flex items-center justify-center text-white">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z"/>
                        </svg>
                    </div>
                    <span class="font-medium text-gray-900 dark:text-white">ยอดคงเหลือ</span>
                </div>
                <span class="text-xl font-bold text-yellow-600 dark:text-yellow-400">{{ number_format($coinBalance, 2) }}</span>
            </div>

            {{-- Lifetime Earned --}}
            <div class="flex justify-between items-center p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center text-white">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13a1 1 0 102 0V9.414l1.293 1.293a1 1 0 001.414-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <span class="font-medium text-gray-900 dark:text-white">ได้รับทั้งหมด</span>
                </div>
                <span class="text-xl font-bold text-green-600 dark:text-green-400">+{{ number_format($lifetimeEarned, 2) }}</span>
            </div>

            {{-- Lifetime Spent --}}
            <div class="flex justify-between items-center p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-red-500 rounded-full flex items-center justify-center text-white">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v3.586L7.707 9.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 10.586V7z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <span class="font-medium text-gray-900 dark:text-white">ใช้ไปแล้ว</span>
                </div>
                <span class="text-xl font-bold text-red-600 dark:text-red-400">-{{ number_format($lifetimeSpent, 2) }}</span>
            </div>

            {{-- Lifetime Exchanged --}}
            <div class="flex justify-between items-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M8 5a1 1 0 100 2h5.586l-1.293 1.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L13.586 5H8zM12 15a1 1 0 100-2H6.414l1.293-1.293a1 1 0 10-1.414-1.414l-3 3a1 1 0 000 1.414l3 3a1 1 0 001.414-1.414L6.414 15H12z"/>
                        </svg>
                    </div>
                    <span class="font-medium text-gray-900 dark:text-white">แลกเป็นเงินแล้ว</span>
                </div>
                <span class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($lifetimeExchanged, 2) }}</span>
            </div>
        </div>
    </div>
    @endif
</div>

{{-- Animation styles --}}
@once
@push('styles')
<style>
    @keyframes shine {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(200%); }
    }
    .animate-shine {
        animation: shine 2s infinite;
    }
</style>
@endpush
@endonce
