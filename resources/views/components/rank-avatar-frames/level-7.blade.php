{{--
/**
 * Level 7: Royal Frame - กรอบรอยัล
 * ดีไซน์หรูหรา สีม่วง gradient หลากสี พร้อม ornaments
 */
--}}
@props(['size' => 'md', 'animate' => true])

@php
    $borderWidths = [
        'xs' => 'border-4',
        'sm' => 'border-[5px]',
        'md' => 'border-[6px]',
        'lg' => 'border-[7px]',
        'xl' => 'border-[8px]',
        '2xl' => 'border-[10px]',
    ];
    $borderWidth = $borderWidths[$size] ?? 'border-[6px]';

    $trophySizes = [
        'xs' => 'text-xs -top-1.5',
        'sm' => 'text-sm -top-2',
        'md' => 'text-lg -top-3',
        'lg' => 'text-xl -top-4',
        'xl' => 'text-2xl -top-5',
        '2xl' => 'text-3xl -top-6',
    ];
    $trophySize = $trophySizes[$size] ?? 'text-lg -top-3';
@endphp

{{-- Main royal frame --}}
<div class="absolute inset-0 rounded-full {{ $borderWidth }}
    bg-gradient-to-br from-purple-500 via-violet-400 to-indigo-500
    shadow-2xl shadow-purple-500/60
    {{ $animate ? 'animate-glow-royal' : '' }}"
    style="padding: 3px;">
    <div class="w-full h-full rounded-full bg-gradient-to-br from-purple-200 via-violet-100 to-indigo-200 opacity-50"></div>
</div>

{{-- Trophy decoration --}}
<div class="absolute {{ $trophySize }} left-1/2 -translate-x-1/2 z-30 pointer-events-none
    {{ $animate ? 'animate-float-royal' : '' }}">
    <span class="drop-shadow-lg filter">🏆</span>
</div>

{{-- Multiple glow layers --}}
<div class="absolute -inset-2 rounded-full bg-purple-500/25 blur-lg pointer-events-none {{ $animate ? 'animate-pulse' : '' }}"></div>
<div class="absolute -inset-3 rounded-full bg-violet-500/15 blur-xl pointer-events-none"></div>

{{-- Rotating gradient overlay --}}
@if($animate)
<div class="absolute inset-0 rounded-full overflow-hidden pointer-events-none animate-rotate-royal">
    <div class="absolute inset-0 bg-[conic-gradient(from_0deg,transparent,rgba(147,51,234,0.4),transparent,rgba(139,92,246,0.4),transparent,rgba(99,102,241,0.4),transparent)]"></div>
</div>
@endif

{{-- Sparkle particles --}}
@if($animate)
@for($i = 0; $i < 8; $i++)
    <div class="absolute w-1 h-1 bg-purple-300 rounded-full animate-sparkle-royal pointer-events-none"
         style="top: {{ rand(5, 95) }}%; left: {{ rand(5, 95) }}%; animation-delay: {{ $i * 0.25 }}s;"></div>
@endfor
@endif

{{-- Shimmer --}}
@if($animate)
<div class="absolute inset-0 rounded-full overflow-hidden pointer-events-none">
    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-purple-200/60 to-transparent
        -translate-x-full animate-shimmer-royal"></div>
</div>
@endif

{{-- Inner decorative rings --}}
<div class="absolute inset-[6px] rounded-full border-2 border-purple-300/50 pointer-events-none"></div>
<div class="absolute inset-[9px] rounded-full border border-violet-200/40 pointer-events-none"></div>

{{-- Corner gems --}}
@if($size !== 'xs' && $size !== 'sm')
<div class="absolute top-0 left-1/2 -translate-x-1/2 w-2.5 h-2.5 bg-purple-400 rounded-full shadow-lg shadow-purple-500/50 {{ $animate ? 'animate-pulse' : '' }}"></div>
<div class="absolute bottom-0 left-1/2 -translate-x-1/2 w-2.5 h-2.5 bg-purple-400 rounded-full shadow-lg shadow-purple-500/50 {{ $animate ? 'animate-pulse' : '' }}" style="animation-delay: 0.5s;"></div>
<div class="absolute left-0 top-1/2 -translate-y-1/2 w-2.5 h-2.5 bg-violet-400 rounded-full shadow-lg shadow-violet-500/50 {{ $animate ? 'animate-pulse' : '' }}" style="animation-delay: 1s;"></div>
<div class="absolute right-0 top-1/2 -translate-y-1/2 w-2.5 h-2.5 bg-violet-400 rounded-full shadow-lg shadow-violet-500/50 {{ $animate ? 'animate-pulse' : '' }}" style="animation-delay: 1.5s;"></div>
@endif

@once
@push('styles')
<style>
    @keyframes glow-royal {
        0%, 100% {
            box-shadow: 0 0 20px rgba(147, 51, 234, 0.6), 0 0 40px rgba(139, 92, 246, 0.4), 0 0 60px rgba(99, 102, 241, 0.2);
        }
        50% {
            box-shadow: 0 0 30px rgba(147, 51, 234, 0.8), 0 0 60px rgba(139, 92, 246, 0.5), 0 0 90px rgba(99, 102, 241, 0.3);
        }
    }
    @keyframes float-royal {
        0%, 100% { transform: translateX(-50%) translateY(0); }
        50% { transform: translateX(-50%) translateY(-5px); }
    }
    @keyframes rotate-royal {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    @keyframes sparkle-royal {
        0%, 100% { opacity: 0; transform: scale(0); }
        50% { opacity: 1; transform: scale(1.2); }
    }
    @keyframes shimmer-royal {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }
    .animate-glow-royal {
        animation: glow-royal 2s ease-in-out infinite;
    }
    .animate-float-royal {
        animation: float-royal 2.5s ease-in-out infinite;
    }
    .animate-rotate-royal {
        animation: rotate-royal 10s linear infinite;
    }
    .animate-sparkle-royal {
        animation: sparkle-royal 1.5s ease-in-out infinite;
    }
    .animate-shimmer-royal {
        animation: shimmer-royal 1.5s infinite;
    }
</style>
@endpush
@endonce
