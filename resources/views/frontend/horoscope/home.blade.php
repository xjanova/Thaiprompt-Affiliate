@extends('frontend.horoscope.layout')

@section('horoscope-content')
<div x-data="horoscopeHome()" x-init="init()">

    {{-- ==================== Hero Section ==================== --}}
    <section class="relative py-16 md:py-24 overflow-hidden">
        {{-- ภาพประกอบจักรวาล (เจนเอง เก็บที่ public/images/art) --}}
        <x-art.backdrop image="cos-horoscope" tone="dark" :opacity="0.55" mask="vignette" />
        <div class="container relative mx-auto px-4 text-center">
            {{-- โลโก้ 3D --}}
            <div class="mb-8 perspective-1000">
                <div class="inline-block transform-gpu hover:rotate-y-12 transition-all duration-700">
                    <div class="relative">
                        {{-- เรืองแสง --}}
                        <div class="absolute inset-0 bg-gradient-to-br from-purple-500 via-pink-500 to-cyan-500 rounded-3xl blur-2xl opacity-50 animate-pulse"></div>
                        {{-- การ์ดหลัก --}}
                        <div class="relative bg-gradient-to-br from-purple-900/80 via-indigo-900/80 to-slate-900/80 backdrop-blur-xl rounded-3xl p-8 border border-white/20 shadow-2xl">
                            <div class="text-7xl md:text-8xl filter drop-shadow-[0_0_30px_rgba(168,85,247,0.8)]">
                                🔮
                            </div>
                            <div class="absolute -top-3 -right-3 w-8 h-8 bg-gradient-to-br from-yellow-400 to-amber-500 rounded-full animate-bounce shadow-lg shadow-yellow-400/50 flex items-center justify-center">
                                <span class="text-xs font-bold">AI</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- หัวข้อหลัก --}}
            <h1 class="text-4xl md:text-6xl lg:text-7xl font-black mb-6">
                <span class="shimmer-text">ดูดวงออนไลน์</span>
                <span class="block text-2xl md:text-3xl mt-3 font-bold text-white/90">
                    AI Fortune Telling
                </span>
            </h1>

            {{-- คำอธิบาย --}}
            <p class="text-lg md:text-xl text-purple-200/80 mb-10 max-w-2xl mx-auto leading-relaxed">
                ดูดวงออนไลน์{{ ($freeFortuneEnabled ?? true) ? 'ฟรี ' : ' ' }}ด้วยเทคโนโลยี AI ที่ทันสมัยที่สุด<br class="hidden md:block">
                ดวงรายวัน ไพ่ทาโรต์ เลขศาสตร์ ทำนายฝัน ครบจบที่เดียว
            </p>

            {{-- ปุ่ม CTA --}}
            <div class="flex flex-col sm:flex-row justify-center gap-4 mb-12">
                <a href="{{ route('horoscope.daily.index') }}"
                   class="group relative overflow-hidden px-8 py-4 bg-gradient-to-r from-purple-600 via-pink-600 to-purple-600 rounded-2xl font-bold text-lg text-white shadow-2xl shadow-purple-500/30 transform hover:scale-105 transition-all duration-300">
                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
                    <span class="relative flex items-center justify-center gap-2">
                        ⭐ เริ่มดูดวง{{ ($freeFortuneEnabled ?? true) ? 'ฟรี' : '' }}
                    </span>
                </a>
                <a href="{{ route('horoscope.tarot.index') }}"
                   class="px-8 py-4 bg-white/10 backdrop-blur-lg border-2 border-white/20 rounded-2xl font-bold text-lg text-white hover:bg-white/20 transition-all duration-300">
                    🃏 เปิดไพ่ทาโรต์
                </a>
            </div>

            {{-- สถิติ --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 max-w-2xl mx-auto">
                <div class="bg-white/5 backdrop-blur-lg rounded-2xl p-4 border border-white/10">
                    <div class="text-2xl md:text-3xl font-black bg-gradient-to-r from-purple-400 to-pink-400 bg-clip-text text-transparent">12</div>
                    <div class="text-purple-300/70 text-sm">ราศี</div>
                </div>
                <div class="bg-white/5 backdrop-blur-lg rounded-2xl p-4 border border-white/10">
                    <div class="text-2xl md:text-3xl font-black bg-gradient-to-r from-cyan-400 to-blue-400 bg-clip-text text-transparent">78</div>
                    <div class="text-purple-300/70 text-sm">ไพ่ทาโรต์</div>
                </div>
                <div class="bg-white/5 backdrop-blur-lg rounded-2xl p-4 border border-white/10">
                    <div class="text-2xl md:text-3xl font-black bg-gradient-to-r from-amber-400 to-yellow-400 bg-clip-text text-transparent">5</div>
                    <div class="text-purple-300/70 text-sm">ประเภทเลขศาสตร์</div>
                </div>
                <div class="bg-white/5 backdrop-blur-lg rounded-2xl p-4 border border-white/10">
                    <div class="text-2xl md:text-3xl font-black bg-gradient-to-r from-emerald-400 to-teal-400 bg-clip-text text-transparent">AI</div>
                    <div class="text-purple-300/70 text-sm">ทำนายอัจฉริยะ</div>
                </div>
            </div>
        </div>
    </section>

    {{-- ==================== 5 หมวดหลัก ==================== --}}
    <section id="categories" class="py-16">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-black text-white mb-4">
                    เลือกหมวดดูดวง
                </h2>
                <p class="text-purple-300/70 text-lg">ครบทุกศาสตร์ ครบทุกความต้องการ</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6 max-w-7xl mx-auto">
                @foreach($categories as $index => $cat)
                <a href="{{ route($cat['route']) }}"
                   class="group perspective-1000 block"
                   x-data="{ show: false }"
                   x-intersect.once="setTimeout(() => show = true, {{ $index * 100 }})">
                    <div class="relative transform-gpu transition-all duration-500 group-hover:scale-105 group-hover:-translate-y-3"
                         :class="show ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'">
                        {{-- Glow --}}
                        <div class="absolute inset-0 rounded-3xl blur-xl opacity-0 group-hover:opacity-60 transition-opacity duration-500"
                             style="background: linear-gradient(135deg, {{ $cat['gradient_from'] }}60, {{ $cat['gradient_to'] }}60);"></div>

                        {{-- Card --}}
                        <div class="relative bg-white/5 backdrop-blur-xl rounded-3xl p-6 border border-white/10 group-hover:border-white/30 shadow-xl transition-all duration-500 h-full">
                            {{-- ไอคอน --}}
                            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl flex items-center justify-center text-3xl shadow-lg transform group-hover:scale-110 group-hover:rotate-6 transition-all duration-500"
                                 style="background: linear-gradient(135deg, {{ $cat['gradient_from'] }}, {{ $cat['gradient_to'] }});">
                                {{ $cat['icon'] }}
                            </div>

                            {{-- ชื่อ --}}
                            <h3 class="text-xl font-bold text-white text-center mb-1 group-hover:text-purple-200 transition-colors">
                                {{ $cat['title'] }}
                            </h3>
                            <p class="text-purple-300/50 text-xs text-center mb-3">{{ $cat['subtitle'] }}</p>

                            {{-- คำอธิบาย --}}
                            <p class="text-purple-300/60 text-sm text-center mb-4 line-clamp-2">
                                {{ $cat['description'] }}
                            </p>

                            {{-- Features Tags --}}
                            <div class="flex flex-wrap justify-center gap-1.5">
                                @foreach($cat['features'] as $feature)
                                    <span class="px-2 py-0.5 bg-white/5 rounded-full text-purple-300/60 text-[10px]">
                                        {{ $feature }}
                                    </span>
                                @endforeach
                            </div>

                            {{-- ปุ่ม Hover --}}
                            <div class="mt-4 opacity-0 group-hover:opacity-100 transition-all duration-300 transform translate-y-2 group-hover:translate-y-0">
                                <div class="w-full py-2.5 rounded-xl text-white font-semibold text-center text-sm"
                                     style="background: linear-gradient(135deg, {{ $cat['gradient_from'] }}, {{ $cat['gradient_to'] }});">
                                    เริ่มดูดวง →
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ==================== ราศีเด่นวันนี้ (Featured) ==================== --}}
    @if($featuredZodiac)
    @php
        $featuredPrediction = $todayPredictions[$featuredZodiac->id] ?? null;
    @endphp
    <section class="py-16">
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto">
                <div class="text-center mb-8">
                    <span class="inline-block px-4 py-1.5 bg-amber-500/20 border border-amber-500/30 rounded-full text-amber-300 text-sm font-medium mb-4">
                        ⭐ ราศีเด่นวันนี้
                    </span>
                    <h2 class="text-3xl md:text-4xl font-black text-white">
                        ราศี{{ $featuredZodiac->name_th }} {{ $featuredZodiac->symbol_emoji }}
                    </h2>
                    <p class="text-purple-300/60 mt-2">{{ \Carbon\Carbon::today()->locale('th')->translatedFormat('l j F Y') }}</p>
                </div>

                <div class="bg-white/5 backdrop-blur-xl rounded-3xl p-6 md:p-8 border border-white/10 shadow-xl"
                     style="box-shadow: 0 0 40px {{ $featuredZodiac->color_hex ?? '#9333ea' }}20;">
                    @if($featuredPrediction)
                        {{-- คะแนนรวม --}}
                        <div class="text-center mb-6">
                            <div class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-amber-500/20 to-yellow-500/20 border border-amber-500/30 rounded-2xl">
                                <span class="text-3xl font-black text-amber-400">{{ number_format($featuredPrediction->average_score, 1) }}</span>
                                <span class="text-amber-300/80 text-sm">/5 คะแนน</span>
                            </div>
                        </div>

                        {{-- คะแนน 5 ด้าน --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-6">
                            @include('frontend.horoscope.partials._score-meter', ['label' => 'ภาพรวม', 'score' => $featuredPrediction->overall_score, 'color' => 'purple'])
                            @include('frontend.horoscope.partials._score-meter', ['label' => 'ความรัก', 'score' => $featuredPrediction->love_score, 'color' => 'pink'])
                            @include('frontend.horoscope.partials._score-meter', ['label' => 'การงาน', 'score' => $featuredPrediction->career_score, 'color' => 'cyan'])
                            @include('frontend.horoscope.partials._score-meter', ['label' => 'การเงิน', 'score' => $featuredPrediction->finance_score, 'color' => 'amber'])
                            @include('frontend.horoscope.partials._score-meter', ['label' => 'สุขภาพ', 'score' => $featuredPrediction->health_score, 'color' => 'emerald'])
                        </div>

                        {{-- คำทำนายภาพรวม --}}
                        @if($featuredPrediction->overall_prediction_th)
                        <div class="bg-white/5 rounded-2xl p-4 mb-4 border border-white/5">
                            <p class="text-purple-200/80 text-sm leading-relaxed">
                                {{ Str::limit($featuredPrediction->overall_prediction_th, 200) }}
                            </p>
                        </div>
                        @endif

                        {{-- ข้อมูลมงคล --}}
                        @include('frontend.horoscope.partials._lucky-info', ['prediction' => $featuredPrediction])
                    @endif

                    {{-- ปุ่มดูเพิ่ม --}}
                    <div class="text-center mt-6">
                        <a href="{{ route('horoscope.daily.zodiac', $featuredZodiac->slug) }}"
                           class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 rounded-xl text-white font-semibold hover:scale-105 transition-all duration-300 shadow-lg shadow-purple-500/20">
                            ดูดวงราศี{{ $featuredZodiac->name_th }} เต็มๆ
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    @endif

    {{-- ==================== 12 ราศี Grid ==================== --}}
    <section class="py-16">
        <div class="container mx-auto px-4">
            <div class="text-center mb-10">
                <h2 class="text-3xl md:text-4xl font-black text-white mb-3">
                    ดวง 12 ราศีวันนี้
                </h2>
                <p class="text-purple-300/70">เลือกราศีของคุณเพื่อดูดวงรายวัน</p>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4 max-w-6xl mx-auto">
                @foreach($zodiacSigns as $zodiac)
                    @include('frontend.horoscope.partials._zodiac-card', [
                        'zodiac' => $zodiac,
                        'todayPredictions' => $todayPredictions,
                    ])
                @endforeach
            </div>

            <div class="text-center mt-8">
                <a href="{{ route('horoscope.daily.index') }}"
                   class="inline-flex items-center gap-2 px-6 py-3 bg-white/10 border border-white/20 rounded-xl text-white font-medium hover:bg-white/20 transition-all duration-300">
                    ดูดวงรายวันทั้งหมด
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </a>
            </div>
        </div>
    </section>

    {{-- ==================== 7 วันเกิด ==================== --}}
    <section class="py-16">
        <div class="container mx-auto px-4">
            <div class="text-center mb-10">
                <h2 class="text-3xl md:text-4xl font-black text-white mb-3">
                    ดวงตามวันเกิด
                </h2>
                <p class="text-purple-300/70">เลือกวันเกิดของคุณ</p>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-7 gap-4 max-w-5xl mx-auto">
                @foreach($birthDays as $day => $birthDay)
                    @include('frontend.horoscope.partials._birth-day-card', [
                        'birthDay' => $birthDay,
                        'todayBirthDayPredictions' => $todayBirthDayPredictions,
                    ])
                @endforeach
            </div>
        </div>
    </section>

    {{-- ==================== Quick Links ==================== --}}
    <section class="py-16">
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto">
                <div class="text-center mb-10">
                    <h2 class="text-3xl md:text-4xl font-black text-white mb-3">
                        ดูดวงเร็ว
                    </h2>
                    <p class="text-purple-300/70">คลิกเลือกสิ่งที่คุณสนใจ</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- ทำนายฝัน --}}
                    <a href="{{ route('horoscope.dream.index') }}"
                       class="group flex items-center gap-4 bg-white/5 backdrop-blur-lg rounded-2xl p-5 border border-white/10 hover:border-purple-400/30 hover:bg-white/10 transition-all duration-300">
                        <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-purple-600 to-blue-600 flex items-center justify-center text-2xl shrink-0 group-hover:scale-110 transition-transform duration-300">
                            💭
                        </div>
                        <div>
                            <h3 class="text-white font-bold text-lg group-hover:text-purple-200 transition-colors">ทำนายฝัน</h3>
                            <p class="text-purple-300/60 text-sm">เล่าฝันให้ AI ฟัง รับเลขเด็ด</p>
                        </div>
                        <svg class="w-5 h-5 text-white/30 ml-auto group-hover:text-white/70 group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>

                    {{-- เลขศาสตร์ --}}
                    <a href="{{ route('horoscope.numerology.index') }}"
                       class="group flex items-center gap-4 bg-white/5 backdrop-blur-lg rounded-2xl p-5 border border-white/10 hover:border-amber-400/30 hover:bg-white/10 transition-all duration-300">
                        <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-amber-600 to-red-600 flex items-center justify-center text-2xl shrink-0 group-hover:scale-110 transition-transform duration-300">
                            🔢
                        </div>
                        <div>
                            <h3 class="text-white font-bold text-lg group-hover:text-amber-200 transition-colors">เลขศาสตร์</h3>
                            <p class="text-purple-300/60 text-sm">วิเคราะห์ชื่อ เบอร์โทร ทะเบียนรถ</p>
                        </div>
                        <svg class="w-5 h-5 text-white/30 ml-auto group-hover:text-white/70 group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>

                    {{-- ไพ่ทาโรต์ --}}
                    <a href="{{ route('horoscope.tarot.index') }}"
                       class="group flex items-center gap-4 bg-white/5 backdrop-blur-lg rounded-2xl p-5 border border-white/10 hover:border-pink-400/30 hover:bg-white/10 transition-all duration-300">
                        <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-pink-600 to-rose-600 flex items-center justify-center text-2xl shrink-0 group-hover:scale-110 transition-transform duration-300">
                            🃏
                        </div>
                        <div>
                            <h3 class="text-white font-bold text-lg group-hover:text-pink-200 transition-colors">เปิดไพ่ทาโรต์</h3>
                            <p class="text-purple-300/60 text-sm">เปิดไพ่ 1 ใบ ฟรี AI ตีความ</p>
                        </div>
                        <svg class="w-5 h-5 text-white/30 ml-auto group-hover:text-white/70 group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>

                    {{-- ดวงรายวัน --}}
                    <a href="{{ route('horoscope.daily.index') }}"
                       class="group flex items-center gap-4 bg-white/5 backdrop-blur-lg rounded-2xl p-5 border border-white/10 hover:border-cyan-400/30 hover:bg-white/10 transition-all duration-300">
                        <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-indigo-600 to-purple-600 flex items-center justify-center text-2xl shrink-0 group-hover:scale-110 transition-transform duration-300">
                            ⭐
                        </div>
                        <div>
                            <h3 class="text-white font-bold text-lg group-hover:text-cyan-200 transition-colors">ดวงรายวัน</h3>
                            <p class="text-purple-300/60 text-sm">12 ราศี + 7 วันเกิด ทำนายด้วย AI</p>
                        </div>
                        <svg class="w-5 h-5 text-white/30 ml-auto group-hover:text-white/70 group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- ==================== SEO Content ==================== --}}
    <section class="py-12">
        <div class="container mx-auto px-4">
            <div class="max-w-3xl mx-auto text-center">
                <div class="bg-white/5 backdrop-blur-lg rounded-3xl p-8 border border-white/10">
                    <h2 class="text-2xl font-bold text-white mb-4">
                        ดูดวงออนไลน์{{ ($freeFortuneEnabled ?? true) ? 'ฟรี ' : ' ' }}— ครบทุกศาสตร์ในที่เดียว
                    </h2>
                    <p class="text-purple-300/60 text-sm leading-relaxed mb-4">
                        ระบบดูดวงออนไลน์ที่ขับเคลื่อนด้วย AI แม่นยำ ทันสมัย
                        ครอบคลุมทุกศาสตร์ — ดวงรายวัน 12 ราศี, ไพ่ทาโรต์ 78 ใบ,
                        เลขศาสตร์วิเคราะห์ชื่อ-เบอร์โทร-ทะเบียนรถ, ทำนายฝันพร้อมเลขเด็ด
                        {{ ($freeFortuneEnabled ?? true) ? 'ฟรีทุกวัน ' : '' }}ใช้งานง่าย สวยงาม รองรับมือถือทุกรุ่น
                    </p>
                    <div class="flex flex-wrap justify-center gap-2 text-xs">
                        @if($freeFortuneEnabled ?? true)
                        <span class="px-3 py-1 bg-white/5 rounded-full text-purple-300/50">ดูดวงฟรี</span>
                        @endif
                        <span class="px-3 py-1 bg-white/5 rounded-full text-purple-300/50">ดวงรายวัน</span>
                        <span class="px-3 py-1 bg-white/5 rounded-full text-purple-300/50">12 ราศี</span>
                        <span class="px-3 py-1 bg-white/5 rounded-full text-purple-300/50">ไพ่ทาโรต์</span>
                        <span class="px-3 py-1 bg-white/5 rounded-full text-purple-300/50">เลขศาสตร์</span>
                        <span class="px-3 py-1 bg-white/5 rounded-full text-purple-300/50">ทำนายฝัน</span>
                        <span class="px-3 py-1 bg-white/5 rounded-full text-purple-300/50">เลขเด็ด</span>
                        <span class="px-3 py-1 bg-white/5 rounded-full text-purple-300/50">AI ทำนาย</span>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
function horoscopeHome() {
    return {
        init() {
            // สามารถเพิ่ม logic เพิ่มเติมในอนาคต เช่น fetch API, animations
        },
    };
}
</script>
@endsection

@push('seo')
<meta name="description" content="{{ $pageDescription ?? 'ดูดวงออนไลน์ฟรี ดวงรายวัน 12 ราศี ไพ่ทาโรต์ ทำนายฝัน เลขเด็ด วิเคราะห์ชื่อ เบอร์โทร ทะเบียนรถ ทำนายด้วย AI แม่นยำ ทันสมัย' }}">
<meta property="og:title" content="{{ $pageTitle ?? 'ดูดวงออนไลน์ ฟรี — ดวงรายวัน ไพ่ทาโรต์ ทำนายฝัน เลขศาสตร์' }}">
<meta property="og:description" content="{{ $pageDescription ?? 'ดูดวงออนไลน์ฟรี ดวงรายวัน 12 ราศี ไพ่ทาโรต์ ทำนายฝัน เลขเด็ด วิเคราะห์ชื่อ เบอร์โทร ทะเบียนรถ ทำนายด้วย AI แม่นยำ ทันสมัย' }}">
<meta property="og:type" content="website">
<meta property="og:url" content="{{ route('horoscope.home') }}">
@endpush
