{{--
/**
 * Dashboard Rank Effects - เอฟเฟคพื้นหลังตาม Rank (V3.2 - Minimal)
 *
 * V3.2: ลดความฟุ้งลงอีก - เหลือแค่ gradient เบาๆ
 * - ลบ orb effects ออกทั้งหมด
 * - ลบ animations ออกทั้งหมด
 * - เหลือแค่ subtle gradient ตาม rank
 *
 * @var int $rankLevel ระดับ Rank (1-8)
 * @version 3.2.0 (Minimal - ลด effects เกือบหมด)
 * @date 2025-11-27
 */
--}}

@php
    $rankLevel = $rankLevel ?? 1;
@endphp

{{-- Background Effects Container - แค่ gradient เบาๆ --}}
<div class="fixed inset-0 pointer-events-none z-0" id="dashboard-rank-effects">
    @switch($rankLevel)
        @case(1)
            <div class="absolute inset-0 bg-gradient-to-br from-amber-50/10 via-transparent to-orange-50/10 dark:from-amber-900/3 dark:via-transparent dark:to-amber-900/3"></div>
            @break
        @case(2)
            <div class="absolute inset-0 bg-gradient-to-br from-gray-100/10 via-transparent to-slate-100/10 dark:from-gray-800/5 dark:via-transparent dark:to-gray-800/5"></div>
            @break
        @case(3)
            <div class="absolute inset-0 bg-gradient-to-br from-yellow-50/10 via-transparent to-amber-50/10 dark:from-yellow-900/3 dark:via-transparent dark:to-yellow-900/3"></div>
            @break
        @case(4)
            <div class="absolute inset-0 bg-gradient-to-br from-slate-100/10 via-transparent to-purple-50/10 dark:from-slate-800/5 dark:via-transparent dark:to-purple-900/3"></div>
            @break
        @case(5)
            <div class="absolute inset-0 bg-gradient-to-br from-cyan-50/10 via-transparent to-blue-50/10 dark:from-cyan-900/3 dark:via-transparent dark:to-blue-900/3"></div>
            @break
        @case(6)
            <div class="absolute inset-0 bg-gradient-to-br from-amber-50/10 via-transparent to-orange-50/10 dark:from-amber-900/5 dark:via-transparent dark:to-orange-900/5"></div>
            @break
        @case(7)
            <div class="absolute inset-0 bg-gradient-to-br from-purple-50/10 via-transparent to-indigo-50/10 dark:from-purple-900/5 dark:via-transparent dark:to-indigo-900/5"></div>
            @break
        @case(8)
            <div class="absolute inset-0 bg-gradient-to-br from-pink-50/10 via-transparent to-indigo-50/10 dark:from-pink-900/5 dark:via-transparent dark:to-indigo-900/5"></div>
            @break
        @default
            <div class="absolute inset-0 bg-gradient-to-br from-gray-50/5 to-gray-100/5 dark:from-gray-900/3 dark:to-gray-800/3"></div>
    @endswitch
</div>
