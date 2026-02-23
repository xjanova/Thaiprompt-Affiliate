{{-- ผลทำนายฝัน — Shareable Result Page --}}
@extends('frontend.horoscope.layout')

@section('horoscope-content')
<div x-data="dreamResult()">

    {{-- ==================== Header ==================== --}}
    <section class="relative py-10 md:py-14">
        <div class="container mx-auto px-4">

            {{-- Breadcrumb --}}
            <div class="mb-6">
                <nav class="flex items-center gap-2 text-sm text-purple-300/50">
                    <a href="{{ route('horoscope.home') }}" class="hover:text-purple-200 transition">🏠 หน้าแรก</a>
                    <span>/</span>
                    <a href="{{ route('horoscope.dream.index') }}" class="hover:text-purple-200 transition">ทำนายฝัน</a>
                    <span>/</span>
                    <span class="text-purple-200/80">ผลทำนาย</span>
                </nav>
            </div>

            {{-- Header Card --}}
            <div class="relative bg-gradient-to-br from-indigo-600/20 to-violet-600/20 backdrop-blur-lg rounded-3xl p-6 md:p-8 border border-indigo-500/20 overflow-hidden">
                {{-- เอฟเฟกต์พื้นหลัง --}}
                <div class="absolute top-0 right-0 w-48 h-48 bg-indigo-400/10 rounded-bl-full blur-3xl"></div>
                <div class="absolute bottom-0 left-0 w-32 h-32 bg-violet-400/10 rounded-tr-full blur-2xl"></div>

                <div class="relative text-center">
                    <div class="text-5xl mb-3" style="animation: float 4s ease-in-out infinite;">🌙</div>
                    <h1 class="text-2xl md:text-3xl font-black text-white mb-2">ผลทำนายฝัน</h1>
                    <p class="text-purple-300/60 text-sm">
                        ทำนายเมื่อ {{ $reading->created_at->locale('th')->translatedFormat('j F') }} {{ $reading->created_at->year + 543 }}
                        เวลา {{ $reading->created_at->format('H:i') }} น.
                    </p>
                    <div class="flex justify-center gap-4 mt-3">
                        <span class="text-purple-300/40 text-xs flex items-center gap-1">
                            👁️ {{ number_format($reading->view_count) }} ครั้ง
                        </span>
                        @if($reading->ai_provider_used)
                            <span class="text-purple-300/40 text-xs flex items-center gap-1">
                                🤖 {{ $reading->ai_provider_used }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ==================== เรื่องฝันที่เล่า ==================== --}}
    <section class="container mx-auto px-4 mb-6">
        <div class="bg-white/5 backdrop-blur-lg rounded-2xl p-5 md:p-6 border border-white/10">
            <h2 class="text-white text-sm font-bold mb-3 flex items-center gap-2">
                <span>💭</span> เรื่องฝันที่เล่า
            </h2>
            <div class="text-purple-100 text-sm leading-relaxed whitespace-pre-line">{{ $reading->dream_description }}</div>
        </div>
    </section>

    {{-- ==================== สัญลักษณ์ที่พบ ==================== --}}
    @if($matchedSymbols->isNotEmpty())
    <section class="container mx-auto px-4 mb-6">
        <h2 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
            <span>🔑</span> สัญลักษณ์ที่พบในฝัน
        </h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            @foreach($matchedSymbols as $symbol)
                <div class="bg-white/5 backdrop-blur-lg rounded-2xl p-4 border border-white/10">
                    <div class="flex items-start gap-3">
                        {{-- ไอคอน --}}
                        <div class="shrink-0 w-10 h-10 rounded-xl flex items-center justify-center text-lg
                                    {{ $symbol->is_good_omen ? 'bg-emerald-500/20 border border-emerald-500/20' : 'bg-amber-500/20 border border-amber-500/20' }}">
                            {{ $symbol->category?->icon ?? '💭' }}
                        </div>

                        {{-- ข้อมูล --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <h3 class="text-white font-bold text-sm">{{ $symbol->keyword_th }}</h3>
                                <span class="text-xs">{{ $symbol->is_good_omen ? '✅' : '⚠️' }}</span>
                            </div>
                            <p class="text-purple-300/50 text-xs line-clamp-2">{{ $symbol->meaning_th }}</p>

                            {{-- เลขเด็ดของสัญลักษณ์ --}}
                            @if(!empty($symbol->lucky_numbers))
                                <div class="flex flex-wrap gap-1 mt-2">
                                    @foreach($symbol->lucky_numbers as $num)
                                        <span class="px-1.5 py-0.5 bg-amber-500/20 text-amber-300 rounded text-[10px] font-mono font-bold">{{ $num }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        {{-- ความเข้มข้น --}}
                        <div class="shrink-0 flex flex-col gap-0.5 items-center">
                            <span class="text-purple-300/40 text-[9px]">ระดับ</span>
                            <div class="flex flex-col gap-0.5">
                                @for($i = 5; $i >= 1; $i--)
                                    <div class="w-3 h-1.5 rounded-full {{ $i <= $symbol->intensity ? 'bg-indigo-500' : 'bg-white/10' }}"></div>
                                @endfor
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
    @endif

    {{-- ==================== คำทำนาย AI ==================== --}}
    @if($reading->ai_interpretation)
    <section class="container mx-auto px-4 mb-6">
        <h2 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
            <span>🤖</span> คำทำนายจาก AI
        </h2>

        <div class="bg-white/5 backdrop-blur-lg rounded-2xl p-5 md:p-6 border border-white/10">
            <div class="text-purple-100 text-sm leading-relaxed whitespace-pre-line">{{ $reading->ai_interpretation }}</div>
        </div>
    </section>
    @endif

    {{-- ==================== เลขเด็ด (เด่น!) ==================== --}}
    @if(!empty($reading->lucky_numbers))
    <section class="container mx-auto px-4 mb-6">
        <div class="bg-gradient-to-br from-amber-600/20 to-orange-600/20 backdrop-blur-lg rounded-3xl p-6 md:p-8 border border-amber-500/20 text-center">
            <h2 class="text-amber-200 font-bold mb-2 flex items-center justify-center gap-2">
                <span class="text-2xl">🎯</span> เลขเด็ดจากความฝัน
            </h2>
            <p class="text-amber-300/50 text-xs mb-5">คำนวณจากสัญลักษณ์ที่พบในฝัน</p>

            <div class="flex flex-wrap justify-center gap-4">
                @foreach($reading->lucky_numbers as $idx => $num)
                    <div class="relative group">
                        {{-- เรืองแสง --}}
                        <div class="absolute inset-0 bg-amber-400/20 rounded-2xl blur-lg group-hover:bg-amber-400/30 transition"></div>
                        {{-- ตัวเลข --}}
                        <div class="relative w-16 h-16 md:w-20 md:h-20 flex items-center justify-center bg-gradient-to-br from-amber-500/40 to-orange-500/40 border-2 border-amber-400/40 rounded-2xl shadow-xl shadow-amber-500/20"
                             style="animation: float {{ 3 + $idx * 0.5 }}s ease-in-out infinite {{ $idx * 0.3 }}s;">
                            <span class="text-amber-100 font-mono font-black text-2xl md:text-3xl">{{ $num }}</span>
                        </div>
                    </div>
                @endforeach
            </div>

            <p class="text-amber-300/40 text-[10px] mt-4">* เลขเด็ดจากระบบ AI ใช้เพื่อความบันเทิงเท่านั้น</p>
        </div>
    </section>
    @endif

    {{-- ==================== แชร์ + ทำนายใหม่ ==================== --}}
    <section class="container mx-auto px-4 mb-6">
        <div class="bg-white/5 backdrop-blur-lg rounded-2xl p-5 border border-white/10 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div>
                <h3 class="text-white font-bold text-sm">แชร์ผลทำนายฝัน</h3>
                <p class="text-purple-300/50 text-xs mt-1">ให้เพื่อนได้ดูผลทำนายฝันด้วย</p>
            </div>
            @include('frontend.horoscope.partials._share-buttons', [
                'url' => route('horoscope.dream.result', $reading->id),
                'title' => 'ผลทำนายฝัน — เลขเด็ด ' . implode(', ', $reading->lucky_numbers ?? []),
            ])
        </div>
    </section>

    {{-- ==================== CTA ทำนายใหม่ ==================== --}}
    <section class="container mx-auto px-4 pb-16">
        <div class="text-center">
            <a href="{{ route('horoscope.dream.index') }}"
               class="inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-indigo-600 to-violet-600 text-white font-bold text-lg rounded-2xl hover:shadow-xl hover:shadow-indigo-500/30 hover:-translate-y-0.5 transition-all duration-300">
                <span>🔮</span> ทำนายฝันใหม่
            </a>
        </div>
    </section>

</div>

<script>
function dreamResult() {
    return {
        // สามารถเพิ่ม interaction ได้ในอนาคต
    };
}
</script>
@endsection
