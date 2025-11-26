{{--
/**
 * Level 2: Silver Frame - กรอบเงิน
 * ดีไซน์เงางาม มี shimmer effect
 */
--}}
@props(['size' => 'md', 'animate' => true])

@php
    $borderWidths = [
        'xs' => 'border-2',
        'sm' => 'border-2',
        'md' => 'border-3',
        'lg' => 'border-4',
        'xl' => 'border-4',
        '2xl' => 'border-[5px]',
    ];
    $borderWidth = $borderWidths[$size] ?? 'border-3';
@endphp

<div class="absolute inset-0 rounded-full {{ $borderWidth }}
    bg-gradient-to-br from-gray-300 via-slate-200 to-gray-400
    shadow-lg shadow-gray-400/40"
    style="padding: 2px;">
    <div class="w-full h-full rounded-full bg-gradient-to-br from-white via-gray-100 to-slate-200 opacity-40"></div>
</div>

{{-- Shimmer effect --}}
@if($animate)
<div class="absolute inset-0 rounded-full overflow-hidden pointer-events-none">
    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/50 to-transparent
        -translate-x-full animate-shimmer-slow"></div>
</div>
@endif

{{-- Inner ring --}}
<div class="absolute inset-[3px] rounded-full border border-white/40 pointer-events-none"></div>

@once
@push('styles')
<style>
    @keyframes shimmer-slow {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }
    .animate-shimmer-slow {
        animation: shimmer-slow 3s infinite;
    }
</style>
@endpush
@endonce
