{{--
/**
 * ID Card Decorations - องค์ประกอบตกแต่งบัตรตาม Rank
 *
 * ออกแบบให้สวยงามแตกต่างกัน 8 ระดับ:
 * ยิ่งระดับสูง ยิ่งมี decorations ที่สวยงามมากขึ้น
 *
 * @var int $rankLevel ระดับ Rank (1-8)
 */
--}}

{{-- Level 3+ : Corner decorations --}}
@if($rankLevel >= 3)
<div class="absolute top-0 right-0 w-24 h-24 overflow-hidden pointer-events-none z-5">
    <div class="absolute top-2 right-2 w-16 h-16 border-t-2 border-r-2 border-white/30 rounded-tr-xl"></div>
</div>
<div class="absolute bottom-0 left-0 w-24 h-24 overflow-hidden pointer-events-none z-5">
    <div class="absolute bottom-2 left-2 w-16 h-16 border-b-2 border-l-2 border-white/30 rounded-bl-xl"></div>
</div>
@endif

{{-- Level 4+ : Line decorations --}}
@if($rankLevel >= 4)
<div class="absolute top-0 left-1/4 right-1/4 h-[1px] bg-gradient-to-r from-transparent via-white/40 to-transparent pointer-events-none z-5"></div>
<div class="absolute bottom-0 left-1/4 right-1/4 h-[1px] bg-gradient-to-r from-transparent via-white/40 to-transparent pointer-events-none z-5"></div>
@endif

{{-- Level 5 : Diamond sparkles --}}
@if($rankLevel == 5)
<div class="absolute inset-0 pointer-events-none z-5">
    {{-- Diamond icons --}}
    <svg class="absolute top-3 right-3 w-6 h-6 text-white/20" viewBox="0 0 24 24" fill="currentColor">
        <path d="M12 2L2 9l10 13 10-13L12 2zm0 2.5l6.5 5.5L12 18 5.5 10 12 4.5z"/>
    </svg>
    <svg class="absolute bottom-3 left-3 w-5 h-5 text-white/15" viewBox="0 0 24 24" fill="currentColor">
        <path d="M12 2L2 9l10 13 10-13L12 2zm0 2.5l6.5 5.5L12 18 5.5 10 12 4.5z"/>
    </svg>
</div>
@endif

{{-- Level 6 : Crown decoration --}}
@if($rankLevel == 6)
<div class="absolute inset-0 pointer-events-none z-5">
    {{-- Crown icon in corner --}}
    <div class="absolute top-2 right-2 text-yellow-200/40 text-2xl">👑</div>
    {{-- Gold lines --}}
    <div class="absolute top-1/2 left-0 w-8 h-[1px] bg-gradient-to-r from-yellow-300/50 to-transparent"></div>
    <div class="absolute top-1/2 right-0 w-8 h-[1px] bg-gradient-to-l from-yellow-300/50 to-transparent"></div>
</div>
@endif

{{-- Level 7 : Royal ornaments --}}
@if($rankLevel == 7)
<div class="absolute inset-0 pointer-events-none z-5">
    {{-- Royal ornament corners --}}
    <svg class="absolute top-0 left-0 w-12 h-12 text-white/20" viewBox="0 0 50 50">
        <path d="M0 0 Q25 25 0 50" fill="none" stroke="currentColor" stroke-width="1"/>
        <circle cx="5" cy="5" r="2" fill="currentColor"/>
    </svg>
    <svg class="absolute top-0 right-0 w-12 h-12 text-white/20 transform scale-x-[-1]" viewBox="0 0 50 50">
        <path d="M0 0 Q25 25 0 50" fill="none" stroke="currentColor" stroke-width="1"/>
        <circle cx="5" cy="5" r="2" fill="currentColor"/>
    </svg>
    <svg class="absolute bottom-0 left-0 w-12 h-12 text-white/20 transform scale-y-[-1]" viewBox="0 0 50 50">
        <path d="M0 0 Q25 25 0 50" fill="none" stroke="currentColor" stroke-width="1"/>
        <circle cx="5" cy="5" r="2" fill="currentColor"/>
    </svg>
    <svg class="absolute bottom-0 right-0 w-12 h-12 text-white/20 transform scale-[-1]" viewBox="0 0 50 50">
        <path d="M0 0 Q25 25 0 50" fill="none" stroke="currentColor" stroke-width="1"/>
        <circle cx="5" cy="5" r="2" fill="currentColor"/>
    </svg>
    {{-- Trophy icon --}}
    <div class="absolute top-2 right-2 text-yellow-300/40 text-xl">🏆</div>
</div>
@endif

{{-- Level 8 : Legend ultimate decorations --}}
@if($rankLevel == 8)
<div class="absolute inset-0 pointer-events-none z-5">
    {{-- Animated star in corner --}}
    <div class="absolute top-2 right-2 text-3xl animate-pulse">🌟</div>

    {{-- Glowing corners --}}
    <div class="absolute top-0 left-0 w-20 h-20">
        <div class="absolute inset-0 bg-gradient-to-br from-pink-400/30 to-transparent rounded-tl-2xl"></div>
        <svg class="absolute top-1 left-1 w-8 h-8 text-pink-300/50" viewBox="0 0 50 50">
            <path d="M5 5 L20 5 L5 20 Z" fill="currentColor"/>
        </svg>
    </div>
    <div class="absolute bottom-0 right-0 w-20 h-20">
        <div class="absolute inset-0 bg-gradient-to-tl from-purple-400/30 to-transparent rounded-br-2xl"></div>
        <svg class="absolute bottom-1 right-1 w-8 h-8 text-purple-300/50 transform rotate-180" viewBox="0 0 50 50">
            <path d="M5 5 L20 5 L5 20 Z" fill="currentColor"/>
        </svg>
    </div>

    {{-- Animated particles --}}
    <div class="absolute inset-0 overflow-hidden">
        @for($i = 0; $i < 5; $i++)
            <div class="absolute w-1 h-1 bg-white rounded-full opacity-60"
                 style="
                    top: {{ rand(10, 90) }}%;
                    left: {{ rand(10, 90) }}%;
                    animation: float {{ 2 + ($i * 0.5) }}s ease-in-out infinite;
                    animation-delay: {{ $i * 0.3 }}s;
                 "></div>
        @endfor
    </div>

    {{-- Luxury border glow --}}
    <div class="absolute inset-[2px] rounded-2xl border border-white/20"></div>
    <div class="absolute inset-[4px] rounded-xl border border-pink-400/10"></div>

    {{-- LEGEND text watermark --}}
    <div class="absolute bottom-1 right-4 text-[8px] font-bold tracking-[0.3em] text-white/10 uppercase">
        Legend
    </div>
</div>
@endif

{{-- CSS for float animation --}}
<style>
    @keyframes float {
        0%, 100% {
            transform: translateY(0) translateX(0);
            opacity: 0.6;
        }
        25% {
            transform: translateY(-10px) translateX(5px);
            opacity: 1;
        }
        50% {
            transform: translateY(-5px) translateX(-3px);
            opacity: 0.8;
        }
        75% {
            transform: translateY(-15px) translateX(2px);
            opacity: 0.4;
        }
    }

    @keyframes spin-slow {
        from {
            transform: rotate(0deg);
        }
        to {
            transform: rotate(360deg);
        }
    }

    .animate-spin-slow {
        animation: spin-slow 20s linear infinite;
    }
</style>
