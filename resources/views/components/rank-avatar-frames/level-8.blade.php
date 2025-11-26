{{--
/**
 * Level 8: Legend Frame - กรอบตำนาน
 * ดีไซน์สุดอลังการ Aurora effect, particles, และ multi-layer glow
 */
--}}
@props(['size' => 'md', 'animate' => true])

@php
    $borderWidths = [
        'xs' => 'border-4',
        'sm' => 'border-[5px]',
        'md' => 'border-[7px]',
        'lg' => 'border-[8px]',
        'xl' => 'border-[10px]',
        '2xl' => 'border-[12px]',
    ];
    $borderWidth = $borderWidths[$size] ?? 'border-[7px]';

    $starSizes = [
        'xs' => 'text-sm -top-2',
        'sm' => 'text-base -top-2',
        'md' => 'text-xl -top-3',
        'lg' => 'text-2xl -top-4',
        'xl' => 'text-3xl -top-5',
        '2xl' => 'text-4xl -top-7',
    ];
    $starSize = $starSizes[$size] ?? 'text-xl -top-3';

    $particleCount = match($size) {
        'xs', 'sm' => 6,
        'md' => 10,
        'lg' => 14,
        'xl', '2xl' => 18,
        default => 10,
    };
@endphp

{{-- Main legend frame with rainbow gradient --}}
<div class="absolute inset-0 rounded-full {{ $borderWidth }}
    bg-gradient-to-br from-pink-500 via-purple-500 to-cyan-500
    shadow-2xl
    {{ $animate ? 'animate-glow-legend' : '' }}"
    style="padding: 3px;">
    <div class="w-full h-full rounded-full bg-gradient-to-br from-pink-200 via-purple-200 to-cyan-200 opacity-40"></div>
</div>

{{-- Star decoration --}}
<div class="absolute {{ $starSize }} left-1/2 -translate-x-1/2 z-30 pointer-events-none
    {{ $animate ? 'animate-star-legend' : '' }}">
    <span class="drop-shadow-[0_0_10px_rgba(251,191,36,0.8)] filter">⭐</span>
</div>

{{-- Aurora background --}}
@if($animate)
<div class="absolute -inset-3 rounded-full overflow-hidden pointer-events-none opacity-60">
    <div class="absolute inset-0 animate-aurora-legend">
        <div class="absolute inset-0 bg-gradient-to-r from-pink-400/40 via-purple-400/40 to-cyan-400/40 blur-xl"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-cyan-400/30 via-transparent to-pink-400/30 blur-xl" style="animation-delay: 2s;"></div>
    </div>
</div>
@endif

{{-- Multiple glow layers --}}
<div class="absolute -inset-2 rounded-full bg-pink-500/30 blur-lg pointer-events-none {{ $animate ? 'animate-pulse' : '' }}"></div>
<div class="absolute -inset-3 rounded-full bg-purple-500/20 blur-xl pointer-events-none"></div>
<div class="absolute -inset-4 rounded-full bg-cyan-500/10 blur-2xl pointer-events-none"></div>

{{-- Rotating rainbow ring --}}
@if($animate)
<div class="absolute -inset-1 rounded-full overflow-hidden pointer-events-none">
    <div class="absolute inset-0 animate-rotate-legend">
        <div class="absolute inset-0 bg-[conic-gradient(from_0deg,#ec4899,#a855f7,#06b6d4,#22c55e,#eab308,#f97316,#ef4444,#ec4899)]"></div>
    </div>
    <div class="absolute inset-[3px] rounded-full bg-white dark:bg-gray-900"></div>
</div>
@endif

{{-- Sparkle particles --}}
@if($animate)
@for($i = 0; $i < $particleCount; $i++)
    @php
        $colors = ['bg-pink-300', 'bg-purple-300', 'bg-cyan-300', 'bg-yellow-300'];
        $color = $colors[$i % 4];
        $particleSize = rand(1, 2);
    @endphp
    <div class="absolute w-{{ $particleSize }} h-{{ $particleSize }} {{ $color }} rounded-full animate-particle-legend pointer-events-none"
         style="top: {{ rand(0, 100) }}%; left: {{ rand(0, 100) }}%; animation-delay: {{ $i * 0.15 }}s;"></div>
@endfor
@endif

{{-- Rainbow shimmer --}}
@if($animate)
<div class="absolute inset-0 rounded-full overflow-hidden pointer-events-none">
    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/70 to-transparent
        -translate-x-full animate-shimmer-legend"></div>
</div>
@endif

{{-- Inner decorative rings --}}
<div class="absolute inset-[7px] rounded-full border-2 border-pink-300/60 pointer-events-none"></div>
<div class="absolute inset-[10px] rounded-full border border-purple-200/40 pointer-events-none"></div>
<div class="absolute inset-[12px] rounded-full border border-cyan-200/30 pointer-events-none"></div>

{{-- Corner stars --}}
@if($size !== 'xs' && $size !== 'sm')
<div class="absolute -top-1 left-1/2 -translate-x-1/2">
    <span class="text-yellow-400 text-xs {{ $animate ? 'animate-twinkle' : '' }}" style="animation-delay: 0s;">✦</span>
</div>
<div class="absolute -bottom-1 left-1/2 -translate-x-1/2">
    <span class="text-yellow-400 text-xs {{ $animate ? 'animate-twinkle' : '' }}" style="animation-delay: 0.3s;">✦</span>
</div>
<div class="absolute top-1/2 -left-1 -translate-y-1/2">
    <span class="text-pink-400 text-xs {{ $animate ? 'animate-twinkle' : '' }}" style="animation-delay: 0.6s;">✦</span>
</div>
<div class="absolute top-1/2 -right-1 -translate-y-1/2">
    <span class="text-cyan-400 text-xs {{ $animate ? 'animate-twinkle' : '' }}" style="animation-delay: 0.9s;">✦</span>
</div>
@endif

{{-- Legend badge --}}
@if($size === 'xl' || $size === '2xl')
<div class="absolute -bottom-2 left-1/2 -translate-x-1/2 z-30
    px-2 py-0.5 bg-gradient-to-r from-pink-500 via-purple-500 to-cyan-500 rounded-full
    text-[8px] font-bold text-white shadow-lg
    {{ $animate ? 'animate-pulse' : '' }}">
    LEGEND
</div>
@endif

@once
@push('styles')
<style>
    @keyframes glow-legend {
        0%, 100% {
            box-shadow:
                0 0 20px rgba(236, 72, 153, 0.6),
                0 0 40px rgba(168, 85, 247, 0.4),
                0 0 60px rgba(6, 182, 212, 0.3),
                0 0 80px rgba(236, 72, 153, 0.2);
        }
        33% {
            box-shadow:
                0 0 25px rgba(168, 85, 247, 0.7),
                0 0 50px rgba(6, 182, 212, 0.5),
                0 0 75px rgba(236, 72, 153, 0.3),
                0 0 100px rgba(168, 85, 247, 0.2);
        }
        66% {
            box-shadow:
                0 0 30px rgba(6, 182, 212, 0.6),
                0 0 60px rgba(236, 72, 153, 0.4),
                0 0 90px rgba(168, 85, 247, 0.3),
                0 0 120px rgba(6, 182, 212, 0.2);
        }
    }
    @keyframes star-legend {
        0%, 100% {
            transform: translateX(-50%) translateY(0) rotate(0deg) scale(1);
        }
        25% {
            transform: translateX(-50%) translateY(-3px) rotate(5deg) scale(1.1);
        }
        50% {
            transform: translateX(-50%) translateY(-5px) rotate(0deg) scale(1.05);
        }
        75% {
            transform: translateX(-50%) translateY(-3px) rotate(-5deg) scale(1.1);
        }
    }
    @keyframes aurora-legend {
        0%, 100% { transform: translateX(-25%) translateY(-25%) rotate(0deg); }
        50% { transform: translateX(25%) translateY(25%) rotate(180deg); }
    }
    @keyframes rotate-legend {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    @keyframes particle-legend {
        0% {
            opacity: 0;
            transform: translateY(0) scale(0);
        }
        50% {
            opacity: 1;
            transform: translateY(-10px) scale(1);
        }
        100% {
            opacity: 0;
            transform: translateY(-20px) scale(0);
        }
    }
    @keyframes shimmer-legend {
        0% { transform: translateX(-100%) rotate(-5deg); }
        100% { transform: translateX(100%) rotate(-5deg); }
    }
    @keyframes twinkle {
        0%, 100% { opacity: 0.4; transform: scale(0.8); }
        50% { opacity: 1; transform: scale(1.2); }
    }
    .animate-glow-legend {
        animation: glow-legend 3s ease-in-out infinite;
    }
    .animate-star-legend {
        animation: star-legend 3s ease-in-out infinite;
    }
    .animate-aurora-legend {
        animation: aurora-legend 8s ease-in-out infinite;
    }
    .animate-rotate-legend {
        animation: rotate-legend 6s linear infinite;
    }
    .animate-particle-legend {
        animation: particle-legend 2s ease-in-out infinite;
    }
    .animate-shimmer-legend {
        animation: shimmer-legend 1.5s infinite;
    }
    .animate-twinkle {
        animation: twinkle 1.5s ease-in-out infinite;
    }
</style>
@endpush
@endonce
