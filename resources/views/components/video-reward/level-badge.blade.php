{{--
    Video Reward Level Badge Component

    แสดง Badge ระดับพร้อมดาว (Stars) สำหรับระบบ Video Reward

    Usage:
    <x-video-reward.level-badge :user="$user" />
    <x-video-reward.level-badge :user="$user" size="lg" />
    <x-video-reward.level-badge :user="$user" show-details />

    Parameters:
    - user: User object (required)
    - size: sm | md | lg | xl (default: md)
    - show-details: boolean - แสดงข้อมูลเพิ่มเติม
    - animated: boolean - เปิด animation
--}}

@props([
    'user',
    'size' => 'md',
    'showDetails' => false,
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
    $starColor = $currentLevel?->star_color ?? 'bronze';
    $starHex = $currentLevel?->star_color_hex ?? '#CD7F32';
    $starGradient = $currentLevel?->star_gradient_class ?? 'from-amber-600 to-amber-800';
    $titlePrefix = $currentLevel?->title_prefix ?? 'มือใหม่';

    // ข้อมูล EXP
    $currentExp = $videoLevel?->current_exp ?? 0;
    $totalExp = $videoLevel?->total_exp ?? 0;
    $requiredExp = $nextLevel?->required_exp ?? 100;
    $progress = $nextLevel ? min(100, ($currentExp / $requiredExp) * 100) : 100;

    // Size classes
    $sizeClasses = match($size) {
        'sm' => [
            'wrapper' => 'w-20 h-24',
            'badge' => 'w-12 h-12',
            'star' => 'w-3 h-3',
            'text' => 'text-xs',
            'level' => 'text-sm font-bold',
        ],
        'lg' => [
            'wrapper' => 'w-40 h-48',
            'badge' => 'w-24 h-24',
            'star' => 'w-6 h-6',
            'text' => 'text-base',
            'level' => 'text-2xl font-bold',
        ],
        'xl' => [
            'wrapper' => 'w-52 h-60',
            'badge' => 'w-32 h-32',
            'star' => 'w-8 h-8',
            'text' => 'text-lg',
            'level' => 'text-3xl font-bold',
        ],
        default => [ // md
            'wrapper' => 'w-28 h-32',
            'badge' => 'w-16 h-16',
            'star' => 'w-4 h-4',
            'text' => 'text-sm',
            'level' => 'text-lg font-bold',
        ],
    };
@endphp

<div
    {{ $attributes->merge(['class' => 'inline-flex flex-col items-center']) }}
    x-data="{
        showTooltip: false,
        animateStars: {{ $animated ? 'true' : 'false' }},
    }"
>
    {{-- Badge Container --}}
    <div class="relative {{ $sizeClasses['wrapper'] }} flex flex-col items-center justify-center">

        {{-- Glow Effect --}}
        @if($animated)
        <div class="absolute inset-0 bg-gradient-to-r {{ $starGradient }} rounded-full opacity-20 blur-xl animate-pulse"></div>
        @endif

        {{-- Main Badge Circle --}}
        <div
            class="relative {{ $sizeClasses['badge'] }} rounded-full bg-gradient-to-br {{ $starGradient }} shadow-lg
                   flex items-center justify-center
                   {{ $animated ? 'hover:scale-110 transition-transform duration-300' : '' }}
                   ring-4 ring-white/20"
            @mouseenter="showTooltip = true"
            @mouseleave="showTooltip = false"
        >
            {{-- Level Number --}}
            <span class="{{ $sizeClasses['level'] }} text-white drop-shadow-lg">
                {{ $levelNumber }}
            </span>

            {{-- Stars Ring --}}
            <div class="absolute -bottom-1 flex justify-center space-x-0.5 bg-gray-900/80 px-2 py-0.5 rounded-full">
                @for($i = 0; $i < $stars; $i++)
                    <svg
                        class="{{ $sizeClasses['star'] }} {{ $animated ? 'animate-pulse' : '' }}"
                        style="color: {{ $starHex }}; animation-delay: {{ $i * 0.15 }}s"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                    >
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                @endfor
            </div>
        </div>

        {{-- Level Name --}}
        <div class="mt-2 text-center">
            <span class="{{ $sizeClasses['text'] }} font-semibold text-gray-800 dark:text-gray-200">
                {{ $levelName }}
            </span>
        </div>

        {{-- Tooltip --}}
        <div
            x-show="showTooltip"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-1"
            class="absolute bottom-full mb-2 px-3 py-2 bg-gray-900 dark:bg-gray-700 text-white text-xs rounded-lg shadow-lg z-50 whitespace-nowrap"
            style="display: none;"
        >
            <div class="text-center">
                <p class="font-bold">{{ $titlePrefix }} - {{ $levelName }}</p>
                <p class="text-gray-300">Level {{ $levelNumber }} | {{ $stars }} ดาว</p>
                <p class="text-gray-400">EXP: {{ number_format($totalExp) }}</p>
            </div>
            <div class="absolute top-full left-1/2 transform -translate-x-1/2 border-4 border-transparent border-t-gray-900 dark:border-t-gray-700"></div>
        </div>
    </div>

    {{-- Progress Bar (optional) --}}
    @if($showDetails && $nextLevel)
    <div class="w-full mt-3 px-2">
        {{-- EXP Progress --}}
        <div class="flex justify-between items-center mb-1">
            <span class="text-xs text-gray-500 dark:text-gray-400">EXP</span>
            <span class="text-xs font-medium text-gray-700 dark:text-gray-300">
                {{ number_format($currentExp) }} / {{ number_format($requiredExp) }}
            </span>
        </div>

        {{-- Progress Bar --}}
        <div class="relative w-full h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
            {{-- Background glow --}}
            <div class="absolute inset-0 bg-gradient-to-r {{ $starGradient }} opacity-20"></div>

            {{-- Progress fill --}}
            <div
                class="absolute inset-y-0 left-0 bg-gradient-to-r {{ $starGradient }} rounded-full transition-all duration-500 ease-out"
                style="width: {{ $progress }}%"
            >
                {{-- Shine effect --}}
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent animate-shine"></div>
            </div>
        </div>

        {{-- Next Level Info --}}
        <div class="flex justify-between items-center mt-1">
            <span class="text-xs text-gray-500 dark:text-gray-400">
                {{ round($progress, 1) }}%
            </span>
            <span class="text-xs text-gray-500 dark:text-gray-400">
                ถัดไป: {{ $nextLevel->name }}
            </span>
        </div>
    </div>
    @elseif($showDetails && !$nextLevel)
    <div class="mt-2 px-3 py-1 bg-gradient-to-r {{ $starGradient }} text-white text-xs rounded-full">
        MAX LEVEL
    </div>
    @endif
</div>

{{-- Custom Animation Styles --}}
@once
@push('styles')
<style>
    @keyframes shine {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }
    .animate-shine {
        animation: shine 2s infinite;
    }
</style>
@endpush
@endonce
