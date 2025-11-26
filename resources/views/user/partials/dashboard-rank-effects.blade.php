{{--
/**
 * Dashboard Rank Effects - เอฟเฟคพื้นหลังตาม Rank (V3.1 - ลดความฟุ้ง)
 *
 * ออกแบบให้ Dashboard สวยงามแต่ไม่รบกวนสายตา:
 * Level 1-3: Gradient พื้นหลังเรียบง่าย ไม่มี animation
 * Level 4-5: เพิ่ม orb นิ่งๆ ไม่เคลื่อนไหว
 * Level 6-8: เพิ่มเอฟเฟคเล็กน้อย แต่ช้าและ subtle
 *
 * @var int $rankLevel ระดับ Rank (1-8)
 * @version 3.1.0 (ลดความฟุ้ง)
 * @date 2025-11-26
 */
--}}

@php
    $rankLevel = $rankLevel ?? 1;
@endphp

{{-- Background Effects Container (อยู่ด้านหลัง content) --}}
<div class="fixed inset-0 pointer-events-none z-0 overflow-hidden" id="dashboard-rank-effects">

    @switch($rankLevel)
        {{-- Level 1: Bronze (สำริด) - gradient เรียบง่าย --}}
        @case(1)
            <div class="absolute inset-0 bg-gradient-to-br from-amber-50/20 via-transparent to-orange-50/20 dark:from-amber-900/5 dark:via-transparent dark:to-amber-900/5"></div>
            @break

        {{-- Level 2: Silver (เงิน) - gradient เรียบง่าย --}}
        @case(2)
            <div class="absolute inset-0 bg-gradient-to-br from-gray-100/20 via-transparent to-slate-100/20 dark:from-gray-800/10 dark:via-transparent dark:to-gray-800/10"></div>
            @break

        {{-- Level 3: Gold (ทอง) - gradient เรียบง่าย --}}
        @case(3)
            <div class="absolute inset-0 bg-gradient-to-br from-yellow-50/25 via-transparent to-amber-50/25 dark:from-yellow-900/8 dark:via-transparent dark:to-yellow-900/8"></div>
            @break

        {{-- Level 4: Platinum (แพลตินัม) - เพิ่ม orb นิ่ง --}}
        @case(4)
            <div class="absolute inset-0 bg-gradient-to-br from-slate-100/20 via-transparent to-slate-100/20 dark:from-slate-800/10 dark:via-transparent dark:to-slate-800/10"></div>
            <div class="absolute top-1/4 left-1/4 w-[300px] h-[300px] bg-purple-300/10 dark:bg-purple-500/5 rounded-full filter blur-3xl"></div>
            <div class="absolute bottom-1/4 right-1/4 w-[250px] h-[250px] bg-blue-300/10 dark:bg-blue-500/5 rounded-full filter blur-3xl"></div>
            @break

        {{-- Level 5: Diamond (เพชร) - orb นิ่ง + gradient --}}
        @case(5)
            <div class="absolute inset-0 bg-gradient-to-br from-cyan-50/20 via-transparent to-blue-50/20 dark:from-cyan-900/8 dark:via-transparent dark:to-blue-900/8"></div>
            <div class="absolute top-1/6 left-1/6 w-[350px] h-[350px] bg-cyan-300/10 dark:bg-cyan-500/5 rounded-full filter blur-3xl"></div>
            <div class="absolute bottom-1/4 right-1/6 w-[300px] h-[300px] bg-sky-300/10 dark:bg-sky-500/5 rounded-full filter blur-3xl"></div>
            @break

        {{-- Level 6: Crown (มงกุฎ) - subtle animation --}}
        @case(6)
            <div class="absolute inset-0 bg-gradient-to-br from-amber-50/25 via-transparent to-orange-50/25 dark:from-amber-900/10 dark:via-transparent dark:to-orange-900/10"></div>
            <div class="absolute top-0 left-1/4 w-[400px] h-[400px] bg-yellow-300/10 dark:bg-yellow-500/5 rounded-full filter blur-3xl animate-float-subtle"></div>
            <div class="absolute bottom-0 right-1/4 w-[350px] h-[350px] bg-amber-300/10 dark:bg-amber-500/5 rounded-full filter blur-3xl animate-float-subtle" style="animation-delay: 3s;"></div>
            @break

        {{-- Level 7: Royal (รอยัล) - subtle animation --}}
        @case(7)
            <div class="absolute inset-0 bg-gradient-to-br from-purple-50/25 via-transparent to-indigo-50/25 dark:from-purple-900/10 dark:via-transparent dark:to-indigo-900/10"></div>
            <div class="absolute top-0 left-0 w-[450px] h-[450px] bg-purple-300/10 dark:bg-purple-500/5 rounded-full filter blur-3xl animate-float-subtle"></div>
            <div class="absolute bottom-0 right-0 w-[400px] h-[400px] bg-violet-300/10 dark:bg-violet-500/5 rounded-full filter blur-3xl animate-float-subtle" style="animation-delay: 4s;"></div>
            @break

        {{-- Level 8: Legend (ตำนาน) - subtle premium --}}
        @case(8)
            <div class="absolute inset-0 bg-gradient-to-br from-pink-50/25 via-purple-50/15 to-indigo-50/25 dark:from-pink-900/10 dark:via-purple-900/8 dark:to-indigo-900/10"></div>
            <div class="absolute top-1/6 left-1/6 w-[500px] h-[500px] bg-pink-300/10 dark:bg-pink-500/5 rounded-full filter blur-3xl animate-float-subtle"></div>
            <div class="absolute bottom-1/6 right-1/6 w-[450px] h-[450px] bg-purple-300/10 dark:bg-purple-500/5 rounded-full filter blur-3xl animate-float-subtle" style="animation-delay: 5s;"></div>
            @break

        {{-- Default --}}
        @default
            <div class="absolute inset-0 bg-gradient-to-br from-gray-50/10 to-gray-100/10 dark:from-gray-900/5 dark:to-gray-800/5"></div>
    @endswitch
</div>

<style>
    /* Dashboard Rank Effects Animations - Subtle Version */
    @keyframes float-subtle {
        0%, 100% {
            transform: translateY(0);
        }
        50% {
            transform: translateY(-10px);
        }
    }

    .animate-float-subtle {
        animation: float-subtle 12s ease-in-out infinite;
    }
</style>
