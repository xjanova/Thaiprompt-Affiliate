@extends('layouts.app')

@push('styles')
<style>
    /* Cosmic Theme Animations */
    @keyframes twinkle {
        0%, 100% { opacity: 0.3; transform: scale(1); }
        50% { opacity: 1; transform: scale(1.5); }
    }
    @keyframes float {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-20px) rotate(5deg); }
    }
    @keyframes shimmer {
        0% { background-position: -200% center; }
        100% { background-position: 200% center; }
    }
    @keyframes orbit {
        0% { transform: rotate(0deg) translateX(100px) rotate(0deg); }
        100% { transform: rotate(360deg) translateX(100px) rotate(-360deg); }
    }
    @keyframes glow-pulse {
        0%, 100% { box-shadow: 0 0 20px rgba(168, 85, 247, 0.3); }
        50% { box-shadow: 0 0 40px rgba(168, 85, 247, 0.6), 0 0 80px rgba(168, 85, 247, 0.2); }
    }
    .animate-spin-slow { animation: spin 20s linear infinite; }
    .animate-spin-slow-reverse { animation: spin 25s linear infinite reverse; }
    .shimmer-text {
        background: linear-gradient(90deg, #c084fc, #f472b6, #22d3ee, #c084fc);
        background-size: 200% auto;
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        animation: shimmer 4s linear infinite;
    }
    .card-hover-glow:hover {
        box-shadow: 0 0 30px rgba(168, 85, 247, 0.4), 0 0 60px rgba(168, 85, 247, 0.1);
    }
    .score-bar {
        transition: width 1.5s ease-out;
    }

    /* Scrollbar สไตล์ Cosmic */
    ::-webkit-scrollbar { width: 8px; }
    ::-webkit-scrollbar-track { background: rgba(15, 10, 30, 0.5); }
    ::-webkit-scrollbar-thumb {
        background: linear-gradient(to bottom, #7c3aed, #ec4899);
        border-radius: 4px;
    }
    ::-webkit-scrollbar-thumb:hover { background: linear-gradient(to bottom, #8b5cf6, #f472b6); }
</style>
@endpush

@section('content')
{{-- Horoscope Cosmic Layout --}}
<div class="min-h-screen bg-gradient-to-br from-slate-950 via-purple-950 to-indigo-950 relative overflow-hidden"
     x-data="horoscopeLayout()">

    {{-- พื้นหลัง Cosmic --}}
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        {{-- ดาวกระพริบ --}}
        <div class="absolute inset-0">
            <template x-for="star in stars" :key="star.id">
                <div class="absolute rounded-full bg-white"
                     :style="`left: ${star.x}%; top: ${star.y}%; width: ${star.size}px; height: ${star.size}px; opacity: ${star.opacity}; animation: twinkle ${star.duration}s ease-in-out infinite; animation-delay: ${star.delay}s;`">
                </div>
            </template>
        </div>

        {{-- วงแหวนเวทย์มนต์ --}}
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[900px] h-[900px] opacity-[0.07]">
            <div class="absolute inset-0 border-2 border-purple-400 rounded-full animate-spin-slow"></div>
            <div class="absolute inset-10 border border-pink-400 rounded-full animate-spin-slow-reverse"></div>
            <div class="absolute inset-20 border border-cyan-400 rounded-full animate-spin-slow" style="animation-duration: 30s;"></div>
        </div>

        {{-- กลุ่มหมอก Nebula --}}
        <div class="absolute -top-40 -right-40 w-[500px] h-[500px] bg-purple-600/15 rounded-full blur-[120px] animate-pulse"></div>
        <div class="absolute -bottom-40 -left-40 w-[400px] h-[400px] bg-pink-600/15 rounded-full blur-[100px] animate-pulse" style="animation-delay: 2s;"></div>
        <div class="absolute top-1/3 right-1/4 w-72 h-72 bg-cyan-600/10 rounded-full blur-[80px] animate-pulse" style="animation-delay: 3s;"></div>
        <div class="absolute bottom-1/3 left-1/3 w-56 h-56 bg-indigo-500/10 rounded-full blur-[60px] animate-pulse" style="animation-delay: 1s;"></div>
    </div>

    {{-- Navigation Bar --}}
    @include('frontend.horoscope.partials._category-nav')

    {{-- เนื้อหาหลัก --}}
    <div class="relative z-10">
        @yield('horoscope-content')
    </div>

    {{-- Footer --}}
    <footer class="relative z-10 border-t border-white/10 mt-20">
        <div class="container mx-auto px-4 py-12">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                {{-- คอลัมน์ 1: แบรนด์ --}}
                <div>
                    <h3 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                        <span class="text-2xl">🔮</span> ดูดวงออนไลน์
                    </h3>
                    <p class="text-purple-300/70 text-sm leading-relaxed">
                        ดูดวงออนไลน์{{ ($freeFortuneEnabled ?? true) ? 'ฟรี ' : '' }}ด้วยเทคโนโลยี AI ที่ทันสมัยที่สุด
                        ครอบคลุมทุกด้าน ไม่ว่าจะเป็นดวงรายวัน ไพ่ทาโรต์
                        เลขศาสตร์ และทำนายฝัน
                    </p>
                </div>

                {{-- คอลัมน์ 2: ลิงก์หมวดหมู่ --}}
                <div>
                    <h4 class="text-lg font-semibold text-white mb-4">หมวดหมู่ดูดวง</h4>
                    <ul class="space-y-2">
                        <li>
                            <a href="{{ route('horoscope.daily.index') }}" class="text-purple-300/70 hover:text-purple-200 transition text-sm flex items-center gap-2">
                                <span>⭐</span> ดวงรายวัน 12 ราศี
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('horoscope.tarot.index') }}" class="text-purple-300/70 hover:text-purple-200 transition text-sm flex items-center gap-2">
                                <span>🃏</span> ไพ่ทาโรต์ออนไลน์
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('horoscope.numerology.index') }}" class="text-purple-300/70 hover:text-purple-200 transition text-sm flex items-center gap-2">
                                <span>🔢</span> เลขศาสตร์
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('horoscope.dream.index') }}" class="text-purple-300/70 hover:text-purple-200 transition text-sm flex items-center gap-2">
                                <span>💭</span> ทำนายฝัน
                            </a>
                        </li>
                    </ul>
                </div>

                {{-- คอลัมน์ 3: ข้อมูลเพิ่ม --}}
                <div>
                    <h4 class="text-lg font-semibold text-white mb-4">เกี่ยวกับเรา</h4>
                    <ul class="space-y-2">
                        <li class="text-purple-300/70 text-sm flex items-center gap-2">
                            <span>🤖</span> ขับเคลื่อนด้วย AI
                        </li>
                        <li class="text-purple-300/70 text-sm flex items-center gap-2">
                            <span>🔒</span> ปลอดภัย เป็นส่วนตัว
                        </li>
                        @if($freeFortuneEnabled ?? true)
                        <li class="text-purple-300/70 text-sm flex items-center gap-2">
                            <span>🆓</span> ดูดวงฟรี ทุกวัน
                        </li>
                        @endif
                        <li class="text-purple-300/70 text-sm flex items-center gap-2">
                            <span>📱</span> รองรับทุกอุปกรณ์
                        </li>
                    </ul>
                </div>
            </div>

            {{-- Copyright --}}
            <div class="mt-8 pt-8 border-t border-white/5 text-center">
                <p class="text-purple-400/50 text-sm">
                    &copy; {{ date('Y') }} ดูดวงออนไลน์ — Powered by AI
                </p>
            </div>
        </div>
    </footer>
</div>

<script>
function horoscopeLayout() {
    return {
        stars: [],
        init() {
            // สร้างดาว 80 ดวง
            this.stars = Array.from({ length: 80 }, (_, i) => ({
                id: i,
                x: Math.random() * 100,
                y: Math.random() * 100,
                size: Math.random() * 3 + 1,
                opacity: Math.random() * 0.7 + 0.3,
                duration: Math.random() * 4 + 2,
                delay: Math.random() * 5,
            }));
        },
    };
}
</script>
@endsection
