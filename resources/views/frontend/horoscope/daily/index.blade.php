{{-- หน้าดวงรายวัน — 12 ราศี + 7 วันเกิด --}}
@extends('frontend.horoscope.layout')

@section('horoscope-content')
<div x-data="dailyIndex()" x-init="init()">

    {{-- ==================== Hero Section ==================== --}}
    <section class="relative py-12 md:py-16 overflow-hidden">
        {{-- ภาพประกอบดวงอาทิตย์+ราศี (เจนเอง) --}}
        <x-art.backdrop image="cos-daily" tone="dark" :opacity="0.55" mask="vignette" />
        <div class="container relative mx-auto px-4 text-center">
            {{-- ไอคอน --}}
            <div class="mb-6">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-gradient-to-br from-purple-600/30 to-pink-600/30 border border-purple-500/30 backdrop-blur-lg shadow-2xl shadow-purple-500/20">
                    <span class="text-5xl filter drop-shadow-[0_0_20px_rgba(168,85,247,0.6)]">⭐</span>
                </div>
            </div>

            {{-- หัวข้อ --}}
            <h1 class="text-3xl md:text-5xl font-black mb-3">
                <span class="shimmer-text">ดวงรายวัน</span>
            </h1>
            <p class="text-purple-200/70 text-lg mb-2">
                {{ today()->locale('th')->translatedFormat('l j F') }} {{ today()->year + 543 }}
            </p>
            <p class="text-purple-300/50 text-sm max-w-lg mx-auto">
                เช็คดวงชะตาประจำวัน ทั้ง 12 ราศี และ 7 วันเกิด วิเคราะห์ด้วย AI
            </p>
        </div>
    </section>

    {{-- ==================== Tab Switcher ==================== --}}
    <section class="container mx-auto px-4 mb-8">
        <div class="flex justify-center">
            <div class="inline-flex bg-white/5 backdrop-blur-lg rounded-2xl p-1.5 border border-white/10">
                <button @click="activeTab = 'zodiac'"
                        :class="activeTab === 'zodiac'
                            ? 'bg-gradient-to-r from-purple-600 to-pink-600 text-white shadow-lg shadow-purple-500/30'
                            : 'text-purple-300/60 hover:text-white hover:bg-white/5'"
                        class="px-6 py-3 rounded-xl font-bold text-sm transition-all duration-300 flex items-center gap-2">
                    <span>⭐</span> 12 ราศี
                </button>
                <button @click="activeTab = 'birthday'"
                        :class="activeTab === 'birthday'
                            ? 'bg-gradient-to-r from-cyan-600 to-blue-600 text-white shadow-lg shadow-cyan-500/30'
                            : 'text-purple-300/60 hover:text-white hover:bg-white/5'"
                        class="px-6 py-3 rounded-xl font-bold text-sm transition-all duration-300 flex items-center gap-2">
                    <span>📅</span> 7 วันเกิด
                </button>
            </div>
        </div>
    </section>

    {{-- ==================== 12 ราศี Grid ==================== --}}
    <section x-show="activeTab === 'zodiac'"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="container mx-auto px-4 pb-16">

        {{-- หัวข้อย่อย --}}
        <div class="text-center mb-8">
            <h2 class="text-2xl md:text-3xl font-bold text-white mb-2">
                🔮 ดวง 12 ราศี วันนี้
            </h2>
            <p class="text-purple-300/60 text-sm">
                คลิกที่ราศีของคุณเพื่อดูคำทำนายละเอียด
            </p>
        </div>

        {{-- Grid 12 ราศี --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
            @foreach($zodiacSigns as $zodiac)
                @include('frontend.horoscope.partials._zodiac-card', [
                    'zodiac' => $zodiac,
                    'todayPredictions' => $zodiacPredictions,
                ])
            @endforeach
        </div>

        {{-- สรุปราศีเด่นวันนี้ --}}
        @php
            $bestZodiac = $zodiacPredictions->sortByDesc('overall_score')->first();
        @endphp
        @if($bestZodiac)
            @php
                $zodiacModel = $zodiacSigns->firstWhere('id', $bestZodiac->zodiac_sign_id);
            @endphp
            @if($zodiacModel)
            <div class="mt-12 bg-gradient-to-r from-amber-600/10 via-yellow-600/10 to-amber-600/10 border border-amber-500/20 rounded-2xl p-6 backdrop-blur-lg">
                <div class="flex flex-col md:flex-row items-center gap-4 text-center md:text-left">
                    <div class="flex items-center gap-3">
                        <div class="w-14 h-14 rounded-xl flex items-center justify-center text-3xl shadow-lg"
                             style="background: linear-gradient(135deg, {{ $zodiacModel->color_hex ?? '#9333ea' }}, {{ $zodiacModel->gradient_to ?? '#ec4899' }});">
                            {{ $zodiacModel->symbol_emoji }}
                        </div>
                        <div>
                            <div class="text-amber-400 text-sm font-bold mb-0.5">🏆 ราศีเด่นวันนี้</div>
                            <h3 class="text-white text-xl font-bold">{{ $zodiacModel->name_th }}</h3>
                        </div>
                    </div>
                    <div class="flex-1">
                        <p class="text-purple-200/80 text-sm leading-relaxed">
                            {{ Str::limit($bestZodiac->overall_prediction_th, 120) }}
                        </p>
                    </div>
                    <a href="{{ route('horoscope.daily.zodiac', $zodiacModel->slug) }}"
                       class="shrink-0 px-5 py-2.5 bg-gradient-to-r from-amber-500 to-yellow-500 text-white font-bold rounded-xl hover:scale-105 transition-transform text-sm">
                        ดูรายละเอียด →
                    </a>
                </div>
            </div>
            @endif
        @endif
    </section>

    {{-- ==================== 7 วันเกิด Grid ==================== --}}
    <section x-show="activeTab === 'birthday'"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="container mx-auto px-4 pb-16">

        {{-- หัวข้อย่อย --}}
        <div class="text-center mb-8">
            <h2 class="text-2xl md:text-3xl font-bold text-white mb-2">
                📅 ดวงตามวันเกิด วันนี้
            </h2>
            <p class="text-purple-300/60 text-sm">
                เลือกวันเกิดของคุณเพื่อดูคำทำนาย
            </p>
        </div>

        {{-- Grid 7 วันเกิด --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-5 max-w-4xl mx-auto">
            @foreach($birthDays as $birthDay)
                @include('frontend.horoscope.partials._birth-day-card', [
                    'birthDay' => $birthDay,
                    'todayBirthDayPredictions' => $birthDayPredictions,
                ])
            @endforeach
        </div>

        {{-- ข้อมูลเพิ่มเติม — โหราศาสตร์ไทย --}}
        <div class="mt-12 bg-white/5 backdrop-blur-lg rounded-2xl p-6 border border-white/10 max-w-4xl mx-auto">
            <h3 class="text-white font-bold text-lg mb-4 flex items-center gap-2">
                <span class="text-xl">📖</span> เกี่ยวกับดวงตามวันเกิด
            </h3>
            <p class="text-purple-200/70 text-sm leading-relaxed mb-4">
                ดวงตามวันเกิดใช้หลักโหราศาสตร์ไทย ระบบเชาจันทร์ (Chaochana)
                ซึ่งวิเคราะห์จากดาวประจำวันเกิด ธาตุประจำตัว และดาวมิตร-ศัตรู
                เพื่อให้คำทำนายที่แม่นยำสำหรับแต่ละวัน
            </p>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="bg-white/5 rounded-lg p-3 text-center">
                    <div class="text-red-400 text-xs mb-1">🔥 ธาตุไฟ</div>
                    <div class="text-white text-xs">อาทิตย์ · อังคาร</div>
                </div>
                <div class="bg-white/5 rounded-lg p-3 text-center">
                    <div class="text-yellow-400 text-xs mb-1">🌍 ธาตุดิน</div>
                    <div class="text-white text-xs">เสาร์</div>
                </div>
                <div class="bg-white/5 rounded-lg p-3 text-center">
                    <div class="text-green-400 text-xs mb-1">💨 ธาตุลม</div>
                    <div class="text-white text-xs">พุธ · ศุกร์</div>
                </div>
                <div class="bg-white/5 rounded-lg p-3 text-center">
                    <div class="text-blue-400 text-xs mb-1">💧 ธาตุน้ำ</div>
                    <div class="text-white text-xs">จันทร์ · พฤหัสบดี</div>
                </div>
            </div>
        </div>
    </section>

    {{-- ==================== Quick Links ==================== --}}
    <section class="container mx-auto px-4 pb-16">
        <div class="text-center mb-8">
            <h2 class="text-xl md:text-2xl font-bold text-white">
                ✨ สำรวจเพิ่มเติม
            </h2>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 max-w-3xl mx-auto">
            <a href="{{ route('horoscope.tarot.index') }}"
               class="group bg-white/5 backdrop-blur-lg rounded-2xl p-5 border border-white/10 text-center hover:bg-white/10 hover:border-white/20 transition-all duration-300 hover:scale-105">
                <div class="text-3xl mb-2">🃏</div>
                <div class="text-white font-bold text-sm">ไพ่ทาโรต์</div>
                <div class="text-purple-300/50 text-xs mt-1">เปิดไพ่{{ ($freeFortuneEnabled ?? true) ? 'ฟรี' : '' }}</div>
            </a>
            <a href="{{ route('horoscope.numerology.index') }}"
               class="group bg-white/5 backdrop-blur-lg rounded-2xl p-5 border border-white/10 text-center hover:bg-white/10 hover:border-white/20 transition-all duration-300 hover:scale-105">
                <div class="text-3xl mb-2">🔢</div>
                <div class="text-white font-bold text-sm">เลขศาสตร์</div>
                <div class="text-purple-300/50 text-xs mt-1">วิเคราะห์ชื่อ/เบอร์</div>
            </a>
            <a href="{{ route('horoscope.dream.index') }}"
               class="group bg-white/5 backdrop-blur-lg rounded-2xl p-5 border border-white/10 text-center hover:bg-white/10 hover:border-white/20 transition-all duration-300 hover:scale-105">
                <div class="text-3xl mb-2">💭</div>
                <div class="text-white font-bold text-sm">ทำนายฝัน</div>
                <div class="text-purple-300/50 text-xs mt-1">แปลความฝัน</div>
            </a>
            <a href="{{ route('horoscope.home') }}"
               class="group bg-white/5 backdrop-blur-lg rounded-2xl p-5 border border-white/10 text-center hover:bg-white/10 hover:border-white/20 transition-all duration-300 hover:scale-105">
                <div class="text-3xl mb-2">🏠</div>
                <div class="text-white font-bold text-sm">หน้าหลัก</div>
                <div class="text-purple-300/50 text-xs mt-1">ดูดวงทุกหมวด</div>
            </a>
        </div>
    </section>

    {{-- ==================== SEO Content ==================== --}}
    <section class="container mx-auto px-4 pb-12">
        <div class="bg-white/3 rounded-2xl p-6 border border-white/5">
            <h2 class="text-lg font-bold text-white/60 mb-3">ดวงรายวัน 12 ราศี — {{ today()->locale('th')->translatedFormat('j F Y') }}</h2>
            <p class="text-purple-300/40 text-xs leading-relaxed">
                ดวงรายวัน 12 ราศี วิเคราะห์โดย AI ที่ทันสมัยที่สุด ครอบคลุมทั้ง ดวงราศีเมษ ราศีพฤษภ ราศีเมถุน ราศีกรกฎ ราศีสิงห์ ราศีกันย์
                ราศีตุลย์ ราศีพิจิก ราศีธนู ราศีมังกร ราศีกุมภ์ ราศีมีน พร้อมดวงตามวันเกิด 7 วัน วันอาทิตย์ วันจันทร์ วันอังคาร
                วันพุธ วันพฤหัสบดี วันศุกร์ วันเสาร์ ทุกด้านของชีวิต ความรัก การงาน การเงิน สุขภาพ พร้อมเลขมงคล สีมงคล ทิศมงคล
            </p>
        </div>
    </section>

</div>

<script>
function dailyIndex() {
    return {
        activeTab: '{{ $activeTab }}',
        init() {
            // อ่าน tab จาก URL parameter
            const urlParams = new URLSearchParams(window.location.search);
            const tab = urlParams.get('tab');
            if (tab === 'birthday' || tab === 'zodiac') {
                this.activeTab = tab;
            }

            // เปลี่ยน URL เมื่อเปลี่ยน tab (ไม่ reload)
            this.$watch('activeTab', (value) => {
                const url = new URL(window.location);
                url.searchParams.set('tab', value);
                history.replaceState({}, '', url);
            });
        },
    };
}
</script>
@endsection
