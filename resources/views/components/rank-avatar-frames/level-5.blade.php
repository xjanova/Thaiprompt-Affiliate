{{--
/**
 * Level 5: Diamond Frame - กรอบเพชร
 * ดีไซน์แวววาว มี sparkle effect และ glassmorphism
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

    $sparkleCount = match($size) {
        'xs', 'sm' => 4,
        'md', 'lg' => 6,
        default => 8,
    };
@endphp

{{-- Main diamond frame --}}
<div class="absolute inset-0 rounded-full {{ $borderWidth }}
    bg-gradient-to-br from-cyan-300 via-sky-200 to-blue-300
    shadow-2xl shadow-cyan-400/50
    {{ $animate ? 'animate-glow-diamond' : '' }}"
    style="padding: 2px;">
    <div class="w-full h-full rounded-full bg-gradient-to-br from-cyan-100 via-sky-50 to-blue-100 opacity-70 backdrop-blur-sm"></div>
</div>

{{-- Glassmorphism overlay --}}
<div class="absolute inset-[2px] rounded-full bg-white/20 backdrop-blur-[2px] pointer-events-none"></div>

{{-- Outer glow rings --}}
<div class="absolute -inset-1 rounded-full bg-cyan-400/20 blur-md pointer-events-none {{ $animate ? 'animate-pulse' : '' }}"></div>
<div class="absolute -inset-2 rounded-full bg-blue-400/10 blur-lg pointer-events-none"></div>

{{-- Sparkle particles --}}
@if($animate)
@for($i = 0; $i < $sparkleCount; $i++)
    <div class="absolute w-1.5 h-1.5 bg-white rounded-full animate-sparkle-diamond pointer-events-none"
         style="top: {{ rand(5, 95) }}%; left: {{ rand(5, 95) }}%; animation-delay: {{ $i * 0.3 }}s;"></div>
@endfor
@endif

{{-- Rainbow shimmer --}}
@if($animate)
<div class="absolute inset-0 rounded-full overflow-hidden pointer-events-none">
    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-cyan-200/60 to-transparent
        -translate-x-full animate-shimmer-diamond"></div>
</div>
@endif

{{-- Inner decorative rings --}}
<div class="absolute inset-[5px] rounded-full border border-cyan-200/60 pointer-events-none"></div>
<div class="absolute inset-[7px] rounded-full border border-white/40 pointer-events-none"></div>

@once
@push('styles')
<style>
    @keyframes glow-diamond {
        0%, 100% {
            box-shadow: 0 0 15px rgba(34, 211, 238, 0.5), 0 0 30px rgba(34, 211, 238, 0.3), 0 0 45px rgba(56, 189, 248, 0.2);
        }
        50% {
            box-shadow: 0 0 25px rgba(34, 211, 238, 0.7), 0 0 50px rgba(34, 211, 238, 0.4), 0 0 75px rgba(56, 189, 248, 0.3);
        }
    }
    @keyframes sparkle-diamond {
        0%, 100% { opacity: 0; transform: scale(0); }
        50% { opacity: 1; transform: scale(1); }
    }
    @keyframes shimmer-diamond {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }
    .animate-glow-diamond {
        animation: glow-diamond 2s ease-in-out infinite;
    }
    .animate-sparkle-diamond {
        animation: sparkle-diamond 1.5s ease-in-out infinite;
    }
    .animate-shimmer-diamond {
        animation: shimmer-diamond 2s infinite;
    }
</style>
@endpush
@endonce
