{{--
/**
 * Level 3: Gold Frame - กรอบทอง
 * ดีไซน์หรูหรา สีทองเงางาม พร้อม glow
 */
--}}
@props(['size' => 'md', 'animate' => true])

@php
    $borderWidths = [
        'xs' => 'border-2',
        'sm' => 'border-3',
        'md' => 'border-4',
        'lg' => 'border-[5px]',
        'xl' => 'border-[6px]',
        '2xl' => 'border-[7px]',
    ];
    $borderWidth = $borderWidths[$size] ?? 'border-4';
@endphp

<div class="absolute inset-0 rounded-full {{ $borderWidth }}
    bg-gradient-to-br from-yellow-400 via-amber-300 to-yellow-500
    shadow-xl shadow-yellow-500/50
    {{ $animate ? 'animate-glow-gold' : '' }}"
    style="padding: 2px;">
    <div class="w-full h-full rounded-full bg-gradient-to-br from-yellow-200 via-amber-100 to-yellow-300 opacity-50"></div>
</div>

{{-- Outer glow ring --}}
<div class="absolute -inset-1 rounded-full bg-yellow-400/20 blur-sm pointer-events-none {{ $animate ? 'animate-pulse' : '' }}"></div>

{{-- Shimmer effect --}}
@if($animate)
<div class="absolute inset-0 rounded-full overflow-hidden pointer-events-none">
    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/60 to-transparent
        -translate-x-full animate-shimmer-gold"></div>
</div>
@endif

{{-- Inner decorative ring --}}
<div class="absolute inset-[4px] rounded-full border-2 border-yellow-200/50 pointer-events-none"></div>

@once
@push('styles')
<style>
    @keyframes glow-gold {
        0%, 100% { box-shadow: 0 0 10px rgba(234, 179, 8, 0.5), 0 0 20px rgba(234, 179, 8, 0.3); }
        50% { box-shadow: 0 0 20px rgba(234, 179, 8, 0.7), 0 0 30px rgba(234, 179, 8, 0.4); }
    }
    @keyframes shimmer-gold {
        0% { transform: translateX(-100%) rotate(-10deg); }
        100% { transform: translateX(100%) rotate(-10deg); }
    }
    .animate-glow-gold {
        animation: glow-gold 2s ease-in-out infinite;
    }
    .animate-shimmer-gold {
        animation: shimmer-gold 2.5s infinite;
    }
</style>
@endpush
@endonce
