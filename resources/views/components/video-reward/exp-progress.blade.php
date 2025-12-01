{{--
    Video Reward EXP Progress Component

    แสดง Progress Bar EXP แบบเกม สวยงามพร้อม Animation

    Usage:
    <x-video-reward.exp-progress :user="$user" />
    <x-video-reward.exp-progress :user="$user" show-level show-stats />

    Parameters:
    - user: User object (required)
    - showLevel: boolean - แสดง level badge ด้านซ้าย
    - showStats: boolean - แสดงสถิติเพิ่มเติม
    - height: sm | md | lg (default: md)
    - animated: boolean - เปิด animation
--}}

@props([
    'user',
    'showLevel' => false,
    'showStats' => false,
    'height' => 'md',
    'animated' => true,
])

@php
    // ดึงข้อมูล level ของ user
    $videoLevel = $user->videoLevel;
    $currentLevel = $videoLevel?->currentLevel;
    $nextLevel = $currentLevel?->next_level;

    // ค่าเริ่มต้นถ้ายังไม่มี level
    $levelNumber = $currentLevel?->level ?? 1;
    $levelName = $currentLevel?->name ?? 'เริ่มต้น';
    $stars = $currentLevel?->stars ?? 1;
    $starGradient = $currentLevel?->star_gradient_class ?? 'from-amber-600 to-amber-800';
    $starHex = $currentLevel?->star_color_hex ?? '#CD7F32';

    // ข้อมูล EXP
    $currentExp = $videoLevel?->current_exp ?? 0;
    $totalExp = $videoLevel?->total_exp ?? 0;
    $requiredExp = $nextLevel?->required_exp ?? 100;
    $expToNextLevel = max(0, $requiredExp - $currentExp);
    $progress = $nextLevel ? min(100, ($currentExp / $requiredExp) * 100) : 100;
    $isMaxLevel = !$nextLevel;

    // Stats
    $videosWatched = $videoLevel?->videos_watched ?? 0;
    $questsCompleted = $videoLevel?->quests_completed ?? 0;
    $totalCoins = $videoLevel?->total_coins_earned ?? 0;

    // Height classes
    $heightClasses = match($height) {
        'sm' => 'h-2',
        'lg' => 'h-6',
        default => 'h-4', // md
    };
@endphp

<div {{ $attributes->merge(['class' => 'w-full']) }} x-data="{ progress: 0 }" x-init="setTimeout(() => progress = {{ $progress }}, 100)">
    {{-- Header with Level and EXP --}}
    <div class="flex items-center justify-between mb-2">
        {{-- Left: Level Info --}}
        <div class="flex items-center space-x-3">
            @if($showLevel)
            {{-- Level Badge Mini --}}
            <div class="relative w-10 h-10 rounded-full bg-gradient-to-br {{ $starGradient }} shadow-lg flex items-center justify-center ring-2 ring-white/20">
                <span class="text-sm font-bold text-white">{{ $levelNumber }}</span>
                {{-- Single Star indicator --}}
                <div class="absolute -bottom-0.5 -right-0.5">
                    <svg class="w-4 h-4" style="color: {{ $starHex }}" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                </div>
            </div>
            @endif

            <div>
                <div class="flex items-center space-x-2">
                    <span class="font-bold text-gray-800 dark:text-gray-200">{{ $levelName }}</span>
                    @if($isMaxLevel)
                    <span class="px-2 py-0.5 text-xs font-bold bg-gradient-to-r {{ $starGradient }} text-white rounded-full">MAX</span>
                    @endif
                </div>
                @if(!$isMaxLevel)
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    อีก {{ number_format($expToNextLevel) }} EXP ถึง {{ $nextLevel->name }}
                </p>
                @else
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    ถึงระดับสูงสุดแล้ว!
                </p>
                @endif
            </div>
        </div>

        {{-- Right: EXP Numbers --}}
        <div class="text-right">
            <p class="font-bold text-gray-800 dark:text-gray-200">
                {{ number_format($currentExp) }}
                @if(!$isMaxLevel)
                <span class="text-gray-400 font-normal">/ {{ number_format($requiredExp) }}</span>
                @endif
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                รวม: {{ number_format($totalExp) }} EXP
            </p>
        </div>
    </div>

    {{-- Progress Bar Container --}}
    <div class="relative w-full {{ $heightClasses }} bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden shadow-inner">
        {{-- Subtle pattern background --}}
        <div class="absolute inset-0 opacity-30" style="background-image: linear-gradient(45deg, rgba(255,255,255,.1) 25%, transparent 25%, transparent 50%, rgba(255,255,255,.1) 50%, rgba(255,255,255,.1) 75%, transparent 75%, transparent); background-size: 20px 20px;"></div>

        {{-- Progress Fill --}}
        <div
            class="absolute inset-y-0 left-0 bg-gradient-to-r {{ $starGradient }} rounded-full shadow-lg transition-all duration-1000 ease-out"
            :style="'width: ' + progress + '%'"
        >
            {{-- Inner glow --}}
            <div class="absolute inset-0 bg-gradient-to-b from-white/30 to-transparent rounded-full"></div>

            {{-- Animated shine --}}
            @if($animated)
            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/40 to-transparent animate-shine"></div>
            @endif

            {{-- Progress percentage indicator --}}
            @if($height !== 'sm')
            <div class="absolute right-2 inset-y-0 flex items-center">
                <span class="text-xs font-bold text-white drop-shadow" x-text="Math.round(progress) + '%'"></span>
            </div>
            @endif
        </div>

        {{-- Milestone markers --}}
        @if($height !== 'sm')
        @foreach([25, 50, 75] as $milestone)
        <div class="absolute top-0 bottom-0 w-px bg-white/20" style="left: {{ $milestone }}%"></div>
        @endforeach
        @endif
    </div>

    {{-- Stats Section (optional) --}}
    @if($showStats)
    <div class="mt-4 grid grid-cols-3 gap-4">
        {{-- Videos Watched --}}
        <div class="text-center p-3 bg-gray-100 dark:bg-gray-800 rounded-xl">
            <div class="flex justify-center mb-1">
                <svg class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M2 6a2 2 0 012-2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zm12.553 1.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z"/>
                </svg>
            </div>
            <p class="font-bold text-gray-800 dark:text-gray-200">{{ number_format($videosWatched) }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">วิดีโอดูแล้ว</p>
        </div>

        {{-- Quests Completed --}}
        <div class="text-center p-3 bg-gray-100 dark:bg-gray-800 rounded-xl">
            <div class="flex justify-center mb-1">
                <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
            </div>
            <p class="font-bold text-gray-800 dark:text-gray-200">{{ number_format($questsCompleted) }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">ภารกิจสำเร็จ</p>
        </div>

        {{-- Coins Earned --}}
        <div class="text-center p-3 bg-gray-100 dark:bg-gray-800 rounded-xl">
            <div class="flex justify-center mb-1">
                <svg class="w-5 h-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.736 6.979C9.208 6.193 9.696 6 10 6c.304 0 .792.193 1.264.979a1 1 0 001.715-1.029C12.279 4.784 11.232 4 10 4s-2.279.784-2.979 1.95c-.285.475-.507 1-.67 1.55H6a1 1 0 000 2h.013a9.358 9.358 0 000 1H6a1 1 0 100 2h.351c.163.55.385 1.075.67 1.55C7.721 15.216 8.768 16 10 16s2.279-.784 2.979-1.95a1 1 0 10-1.715-1.029c-.472.786-.96.979-1.264.979-.304 0-.792-.193-1.264-.979a4.265 4.265 0 01-.264-.521H10a1 1 0 100-2H8.017a7.36 7.36 0 010-1H10a1 1 0 100-2H8.472c.08-.185.167-.36.264-.521z"/>
                </svg>
            </div>
            <p class="font-bold text-gray-800 dark:text-gray-200">{{ number_format($totalCoins, 0) }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Coins ได้รับ</p>
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
        animation: shine 3s infinite linear;
    }
</style>
@endpush
@endonce
