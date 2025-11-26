{{--
/**
 * Level 6: Crown Frame - กรอบมงกุฎ
 * ดีไซน์ Royal สีทอง มีมงกุฎด้านบน
 */
--}}
@props(['size' => 'md', 'animate' => true])

@php
    $borderWidths = [
        'xs' => 'border-3',
        'sm' => 'border-4',
        'md' => 'border-[5px]',
        'lg' => 'border-[6px]',
        'xl' => 'border-[7px]',
        '2xl' => 'border-[8px]',
    ];
    $borderWidth = $borderWidths[$size] ?? 'border-[5px]';

    $crownSizes = [
        'xs' => 'text-sm -top-2',
        'sm' => 'text-base -top-2',
        'md' => 'text-xl -top-3',
        'lg' => 'text-2xl -top-4',
        'xl' => 'text-3xl -top-5',
        '2xl' => 'text-4xl -top-6',
    ];
    $crownSize = $crownSizes[$size] ?? 'text-xl -top-3';
@endphp

{{-- Main crown frame --}}
<div class="absolute inset-0 rounded-full {{ $borderWidth }}
    bg-gradient-to-br from-yellow-400 via-amber-300 to-orange-400
    shadow-2xl shadow-yellow-500/60
    {{ $animate ? 'animate-glow-crown' : '' }}"
    style="padding: 3px;">
    <div class="w-full h-full rounded-full bg-gradient-to-br from-yellow-200 via-amber-100 to-orange-200 opacity-60"></div>
</div>

{{-- Crown decoration on top --}}
<div class="absolute {{ $crownSize }} left-1/2 -translate-x-1/2 z-30 pointer-events-none
    {{ $animate ? 'animate-bounce-crown' : '' }}">
    <span class="drop-shadow-lg filter">👑</span>
</div>

{{-- Royal glow --}}
<div class="absolute -inset-2 rounded-full bg-yellow-400/30 blur-lg pointer-events-none {{ $animate ? 'animate-pulse' : '' }}"></div>

{{-- Golden rays --}}
@if($animate)
<div class="absolute inset-0 rounded-full overflow-hidden pointer-events-none">
    @for($i = 0; $i < 8; $i++)
        <div class="absolute w-px h-full bg-gradient-to-t from-transparent via-yellow-400/40 to-transparent origin-center"
             style="left: 50%; transform: rotate({{ $i * 45 }}deg);"></div>
    @endfor
</div>
@endif

{{-- Shimmer --}}
@if($animate)
<div class="absolute inset-0 rounded-full overflow-hidden pointer-events-none">
    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-yellow-200/70 to-transparent
        -translate-x-full animate-shimmer-crown"></div>
</div>
@endif

{{-- Inner decorative rings --}}
<div class="absolute inset-[5px] rounded-full border-2 border-yellow-300/60 pointer-events-none"></div>
<div class="absolute inset-[8px] rounded-full border border-amber-200/40 pointer-events-none"></div>

{{-- Corner jewels --}}
@if($size !== 'xs' && $size !== 'sm')
<div class="absolute top-1 left-1 w-2 h-2 bg-red-500 rounded-full shadow-lg shadow-red-500/50 {{ $animate ? 'animate-pulse' : '' }}"></div>
<div class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full shadow-lg shadow-red-500/50 {{ $animate ? 'animate-pulse' : '' }}" style="animation-delay: 0.5s;"></div>
<div class="absolute bottom-1 left-1 w-2 h-2 bg-red-500 rounded-full shadow-lg shadow-red-500/50 {{ $animate ? 'animate-pulse' : '' }}" style="animation-delay: 1s;"></div>
<div class="absolute bottom-1 right-1 w-2 h-2 bg-red-500 rounded-full shadow-lg shadow-red-500/50 {{ $animate ? 'animate-pulse' : '' }}" style="animation-delay: 1.5s;"></div>
@endif

@once
@push('styles')
<style>
    @keyframes glow-crown {
        0%, 100% {
            box-shadow: 0 0 15px rgba(234, 179, 8, 0.6), 0 0 30px rgba(234, 179, 8, 0.4), 0 0 45px rgba(251, 191, 36, 0.2);
        }
        50% {
            box-shadow: 0 0 25px rgba(234, 179, 8, 0.8), 0 0 50px rgba(234, 179, 8, 0.5), 0 0 75px rgba(251, 191, 36, 0.3);
        }
    }
    @keyframes bounce-crown {
        0%, 100% { transform: translateX(-50%) translateY(0); }
        50% { transform: translateX(-50%) translateY(-4px); }
    }
    @keyframes shimmer-crown {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }
    .animate-glow-crown {
        animation: glow-crown 2s ease-in-out infinite;
    }
    .animate-bounce-crown {
        animation: bounce-crown 2s ease-in-out infinite;
    }
    .animate-shimmer-crown {
        animation: shimmer-crown 2s infinite;
    }
</style>
@endpush
@endonce
