{{--
/**
 * Level 1: Bronze Frame - กรอบสำริด
 * ดีไซน์เรียบง่าย สีทองแดง
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
    bg-gradient-to-br from-amber-600 via-orange-500 to-amber-700
    shadow-lg shadow-amber-600/30
    {{ $animate ? 'animate-pulse-frame' : '' }}"
    style="padding: 2px;">
    <div class="w-full h-full rounded-full bg-gradient-to-br from-amber-400 via-orange-400 to-amber-500 opacity-30"></div>
</div>

{{-- Inner glow --}}
<div class="absolute inset-[3px] rounded-full border border-amber-300/30 pointer-events-none"></div>
