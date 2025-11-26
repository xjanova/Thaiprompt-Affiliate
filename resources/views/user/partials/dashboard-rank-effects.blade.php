{{--
/**
 * Dashboard Rank Effects - เอฟเฟคพื้นหลังตาม Rank
 *
 * ออกแบบให้ Dashboard สวยงามแตกต่างกัน 8 ระดับ:
 * Level 1: Bronze - สีทองแดงเรียบง่าย
 * Level 2: Silver - สีเงินมี shimmer
 * Level 3: Gold - สีทองเงางาม
 * Level 4: Platinum - holographic effect
 * Level 5: Diamond - sparkle และ particles
 * Level 6: Crown - premium royal
 * Level 7: Royal - ม่วงหรู gradient หลากสี
 * Level 8: Legend - สุดอลังการ aurora + particles
 *
 * @var int $rankLevel ระดับ Rank (1-8)
 * @version 1.0.0
 * @date 2025-11-26
 */
--}}

@php
    $rankLevel = $rankLevel ?? 1;
@endphp

{{-- Background Effects Container (อยู่ด้านหลัง content) --}}
<div class="fixed inset-0 pointer-events-none z-0 overflow-hidden" id="dashboard-rank-effects">

    @switch($rankLevel)
        {{-- Level 1: Bronze (สำริด) - เรียบง่าย --}}
        @case(1)
            <div class="absolute inset-0 bg-gradient-to-br from-amber-50/30 via-orange-50/20 to-amber-50/30 dark:from-amber-900/10 dark:via-orange-900/5 dark:to-amber-900/10"></div>
            @break

        {{-- Level 2: Silver (เงิน) - shimmer --}}
        @case(2)
            <div class="absolute inset-0 bg-gradient-to-br from-gray-100/40 via-slate-50/30 to-gray-100/40 dark:from-gray-800/20 dark:via-slate-800/10 dark:to-gray-800/20"></div>
            <div class="absolute inset-0 animate-shimmer-slow opacity-20">
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent"></div>
            </div>
            @break

        {{-- Level 3: Gold (ทอง) - เงางาม --}}
        @case(3)
            <div class="absolute inset-0 bg-gradient-to-br from-yellow-50/40 via-amber-50/30 to-yellow-50/40 dark:from-yellow-900/15 dark:via-amber-900/10 dark:to-yellow-900/15"></div>
            <div class="absolute top-0 left-0 w-[600px] h-[600px] bg-yellow-400/10 dark:bg-yellow-500/5 rounded-full filter blur-3xl animate-float" style="animation-delay: 0s;"></div>
            <div class="absolute bottom-0 right-0 w-[500px] h-[500px] bg-amber-400/10 dark:bg-amber-500/5 rounded-full filter blur-3xl animate-float" style="animation-delay: 2s;"></div>
            @break

        {{-- Level 4: Platinum (แพลตินัม) - holographic --}}
        @case(4)
            <div class="absolute inset-0 bg-gradient-to-br from-slate-100/40 via-gray-50/30 to-slate-100/40 dark:from-slate-800/20 dark:via-gray-800/15 dark:to-slate-800/20"></div>
            <div class="absolute top-0 left-0 w-full h-full">
                <div class="absolute top-1/4 left-1/4 w-[400px] h-[400px] bg-purple-300/20 dark:bg-purple-500/10 rounded-full filter blur-3xl animate-float"></div>
                <div class="absolute top-1/3 right-1/4 w-[350px] h-[350px] bg-blue-300/20 dark:bg-blue-500/10 rounded-full filter blur-3xl animate-float" style="animation-delay: 1s;"></div>
                <div class="absolute bottom-1/4 left-1/3 w-[300px] h-[300px] bg-pink-300/20 dark:bg-pink-500/10 rounded-full filter blur-3xl animate-float" style="animation-delay: 2s;"></div>
            </div>
            <div class="absolute inset-0 animate-holographic opacity-10">
                <div class="absolute inset-0 bg-[conic-gradient(from_0deg,transparent,rgba(255,255,255,0.3),transparent,rgba(255,255,255,0.2),transparent)]"></div>
            </div>
            @break

        {{-- Level 5: Diamond (เพชร) - sparkle --}}
        @case(5)
            <div class="absolute inset-0 bg-gradient-to-br from-cyan-50/40 via-sky-50/30 to-blue-50/40 dark:from-cyan-900/15 dark:via-sky-900/10 dark:to-blue-900/15"></div>
            <div class="absolute top-0 left-0 w-full h-full">
                <div class="absolute top-1/6 left-1/6 w-[500px] h-[500px] bg-cyan-300/25 dark:bg-cyan-500/10 rounded-full filter blur-3xl animate-float"></div>
                <div class="absolute bottom-1/4 right-1/6 w-[450px] h-[450px] bg-sky-300/25 dark:bg-sky-500/10 rounded-full filter blur-3xl animate-float" style="animation-delay: 1.5s;"></div>
            </div>
            {{-- Sparkles --}}
            @for($i = 0; $i < 6; $i++)
                <div class="absolute w-2 h-2 bg-white dark:bg-cyan-200 rounded-full animate-sparkle shadow-lg shadow-cyan-500/50"
                     style="top: {{ rand(10, 90) }}%; left: {{ rand(10, 90) }}%; animation-delay: {{ $i * 0.5 }}s; opacity: 0.6;"></div>
            @endfor
            @break

        {{-- Level 6: Crown (มงกุฎ) - premium --}}
        @case(6)
            <div class="absolute inset-0 bg-gradient-to-br from-amber-50/50 via-yellow-50/40 to-orange-50/50 dark:from-amber-900/20 dark:via-yellow-900/15 dark:to-orange-900/20"></div>
            <div class="absolute top-0 left-0 w-full h-full">
                <div class="absolute top-0 left-1/4 w-[600px] h-[600px] bg-yellow-300/30 dark:bg-yellow-500/15 rounded-full filter blur-3xl animate-float"></div>
                <div class="absolute bottom-0 right-1/4 w-[550px] h-[550px] bg-amber-300/30 dark:bg-amber-500/15 rounded-full filter blur-3xl animate-float" style="animation-delay: 1.5s;"></div>
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[400px] h-[400px] bg-orange-300/20 dark:bg-orange-500/10 rounded-full filter blur-3xl animate-pulse-slow"></div>
            </div>
            {{-- Crown decoration --}}
            <div class="absolute top-4 left-1/2 -translate-x-1/2 text-yellow-400/20 dark:text-yellow-500/10 text-9xl pointer-events-none select-none">
                👑
            </div>
            {{-- Sparkles --}}
            @for($i = 0; $i < 8; $i++)
                <div class="absolute w-1.5 h-1.5 bg-yellow-400 dark:bg-yellow-300 rounded-full animate-sparkle shadow-lg shadow-yellow-500/50"
                     style="top: {{ rand(5, 95) }}%; left: {{ rand(5, 95) }}%; animation-delay: {{ $i * 0.4 }}s; opacity: 0.5;"></div>
            @endfor
            @break

        {{-- Level 7: Royal (รอยัล) - ม่วงหรู --}}
        @case(7)
            <div class="absolute inset-0 bg-gradient-to-br from-purple-50/50 via-violet-50/40 to-indigo-50/50 dark:from-purple-900/25 dark:via-violet-900/20 dark:to-indigo-900/25"></div>
            <div class="absolute top-0 left-0 w-full h-full">
                <div class="absolute top-0 left-0 w-[700px] h-[700px] bg-purple-300/30 dark:bg-purple-500/15 rounded-full filter blur-3xl animate-float"></div>
                <div class="absolute bottom-0 right-0 w-[600px] h-[600px] bg-violet-300/30 dark:bg-violet-500/15 rounded-full filter blur-3xl animate-float" style="animation-delay: 1s;"></div>
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-pink-300/25 dark:bg-pink-500/10 rounded-full filter blur-3xl animate-float" style="animation-delay: 2s;"></div>
            </div>
            {{-- Gradient overlay --}}
            <div class="absolute inset-0 bg-gradient-to-t from-purple-500/5 via-transparent to-violet-500/5 dark:from-purple-500/10 dark:to-violet-500/10 animate-pulse-slow"></div>
            {{-- Sparkles --}}
            @for($i = 0; $i < 10; $i++)
                <div class="absolute w-1 h-1 bg-violet-400 dark:bg-violet-300 rounded-full animate-sparkle shadow-lg shadow-violet-500/50"
                     style="top: {{ rand(5, 95) }}%; left: {{ rand(5, 95) }}%; animation-delay: {{ $i * 0.35 }}s; opacity: 0.6;"></div>
            @endfor
            @break

        {{-- Level 8: Legend (ตำนาน) - สุดอลังการ --}}
        @case(8)
            <div class="absolute inset-0 bg-gradient-to-br from-pink-50/50 via-purple-50/40 to-indigo-50/50 dark:from-pink-900/25 dark:via-purple-900/20 dark:to-indigo-900/25"></div>

            {{-- Aurora Effect --}}
            <div class="absolute inset-0 overflow-hidden">
                <div class="absolute -inset-[100%] animate-spin-very-slow opacity-30 dark:opacity-20">
                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-cyan-400/20 to-transparent transform rotate-45"></div>
                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-pink-400/20 to-transparent transform -rotate-45"></div>
                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-purple-400/20 to-transparent transform rotate-12"></div>
                </div>
            </div>

            {{-- Glowing Orbs --}}
            <div class="absolute top-0 left-0 w-full h-full">
                <div class="absolute top-1/6 left-1/6 w-[800px] h-[800px] bg-pink-300/35 dark:bg-pink-500/20 rounded-full filter blur-3xl animate-float"></div>
                <div class="absolute bottom-1/6 right-1/6 w-[700px] h-[700px] bg-purple-300/35 dark:bg-purple-500/20 rounded-full filter blur-3xl animate-float" style="animation-delay: 1s;"></div>
                <div class="absolute top-1/3 right-1/3 w-[600px] h-[600px] bg-cyan-300/30 dark:bg-cyan-500/15 rounded-full filter blur-3xl animate-float" style="animation-delay: 2s;"></div>
                <div class="absolute bottom-1/3 left-1/3 w-[500px] h-[500px] bg-indigo-300/30 dark:bg-indigo-500/15 rounded-full filter blur-3xl animate-float" style="animation-delay: 3s;"></div>
            </div>

            {{-- Animated glow border effect --}}
            <div class="absolute inset-4 rounded-3xl border border-pink-400/20 dark:border-pink-500/10 shadow-[inset_0_0_100px_rgba(236,72,153,0.1)] dark:shadow-[inset_0_0_100px_rgba(236,72,153,0.05)] animate-pulse-slow pointer-events-none"></div>

            {{-- Sparkle particles --}}
            @for($i = 0; $i < 15; $i++)
                <div class="absolute w-{{ rand(1, 2) }} h-{{ rand(1, 2) }} bg-white dark:bg-pink-200 rounded-full animate-sparkle shadow-lg"
                     style="top: {{ rand(5, 95) }}%; left: {{ rand(5, 95) }}%; animation-delay: {{ $i * 0.25 }}s; opacity: {{ rand(4, 8) / 10 }};"></div>
            @endfor

            {{-- Legend Badge Watermark --}}
            <div class="absolute bottom-8 right-8 text-pink-500/10 dark:text-pink-400/5 text-[200px] pointer-events-none select-none font-bold leading-none">
                ⭐
            </div>
            @break

        {{-- Default --}}
        @default
            <div class="absolute inset-0 bg-gradient-to-br from-gray-50/20 to-gray-100/20 dark:from-gray-900/10 dark:to-gray-800/10"></div>
    @endswitch
</div>

<style>
    /* Dashboard Rank Effects Animations */
    @keyframes float {
        0%, 100% {
            transform: translateY(0) scale(1);
        }
        50% {
            transform: translateY(-30px) scale(1.05);
        }
    }

    @keyframes sparkle {
        0%, 100% {
            opacity: 0;
            transform: scale(0);
        }
        50% {
            opacity: 1;
            transform: scale(1);
        }
    }

    @keyframes shimmer-slow {
        0% {
            transform: translateX(-100%);
        }
        100% {
            transform: translateX(100%);
        }
    }

    @keyframes holographic {
        0% {
            transform: rotate(0deg);
        }
        100% {
            transform: rotate(360deg);
        }
    }

    @keyframes pulse-slow {
        0%, 100% {
            opacity: 0.5;
        }
        50% {
            opacity: 1;
        }
    }

    @keyframes spin-very-slow {
        0% {
            transform: rotate(0deg);
        }
        100% {
            transform: rotate(360deg);
        }
    }

    .animate-float {
        animation: float 8s ease-in-out infinite;
    }

    .animate-sparkle {
        animation: sparkle 2s ease-in-out infinite;
    }

    .animate-shimmer-slow {
        animation: shimmer-slow 8s linear infinite;
    }

    .animate-holographic {
        animation: holographic 20s linear infinite;
    }

    .animate-pulse-slow {
        animation: pulse-slow 4s ease-in-out infinite;
    }

    .animate-spin-very-slow {
        animation: spin-very-slow 30s linear infinite;
    }
</style>
