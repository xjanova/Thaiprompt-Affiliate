{{--
/**
 * Level 4: Platinum Frame - กรอบแพลตินัม
 * ดีไซน์ holographic เปลี่ยนสีตามมุมมอง
 */
--}}
@props(['size' => 'md', 'animate' => true])

@php
    $borderWidths = [
        'xs' => 'border-3',
        'sm' => 'border-3',
        'md' => 'border-4',
        'lg' => 'border-[5px]',
        'xl' => 'border-[6px]',
        '2xl' => 'border-[8px]',
    ];
    $borderWidth = $borderWidths[$size] ?? 'border-4';
@endphp

{{-- Main frame with holographic gradient --}}
<div class="absolute inset-0 rounded-full {{ $borderWidth }}
    bg-gradient-to-br from-slate-300 via-purple-200 to-pink-200
    shadow-xl shadow-purple-400/30"
    style="padding: 2px;">
    <div class="w-full h-full rounded-full bg-gradient-to-br from-slate-100 via-purple-100 to-pink-100 opacity-60"></div>
</div>

{{-- Holographic overlay --}}
<div class="absolute inset-0 rounded-full overflow-hidden pointer-events-none {{ $animate ? 'animate-holographic' : '' }}">
    <div class="absolute inset-0 bg-[conic-gradient(from_0deg,transparent,rgba(168,85,247,0.3),transparent,rgba(236,72,153,0.3),transparent,rgba(59,130,246,0.3),transparent)]"></div>
</div>

{{-- Outer glow --}}
<div class="absolute -inset-1 rounded-full bg-gradient-to-r from-purple-400/20 via-pink-400/20 to-blue-400/20 blur-md pointer-events-none
    {{ $animate ? 'animate-pulse' : '' }}"></div>

{{-- Shimmer --}}
@if($animate)
<div class="absolute inset-0 rounded-full overflow-hidden pointer-events-none">
    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/50 to-transparent
        -translate-x-full animate-shimmer-platinum"></div>
</div>
@endif

{{-- Inner ring --}}
<div class="absolute inset-[5px] rounded-full border border-white/50 pointer-events-none"></div>

@once
@push('styles')
<style>
    @keyframes holographic {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    @keyframes shimmer-platinum {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }
    .animate-holographic {
        animation: holographic 8s linear infinite;
    }
    .animate-shimmer-platinum {
        animation: shimmer-platinum 2s infinite;
    }
</style>
@endpush
@endonce
