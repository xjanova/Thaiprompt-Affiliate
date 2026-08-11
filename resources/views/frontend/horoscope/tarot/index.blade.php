{{-- หน้าหลักไพ่ทาโรต์ — Horoscope Public --}}
@extends('frontend.horoscope.layout')

@section('horoscope-content')
<div x-data="tarotHome()" x-init="init()">

    {{-- ==================== Hero Section ==================== --}}
    <section class="relative py-12 md:py-20 overflow-hidden">
        {{-- ภาพประกอบไพ่ทาโรต์ลอยในจักรวาล (เจนเอง) --}}
        <x-art.backdrop image="cos-tarot" tone="dark" :opacity="0.55" mask="vignette" />
        <div class="container relative mx-auto px-4 text-center">
            {{-- ไอคอน 3D --}}
            <div class="mb-6 relative inline-block">
                <div class="w-24 h-24 rounded-2xl bg-gradient-to-br from-violet-600/30 to-fuchsia-600/30 border border-violet-500/30 backdrop-blur-lg shadow-2xl shadow-violet-500/20 flex items-center justify-center mx-auto"
                     style="animation: float 4s ease-in-out infinite;">
                    <span class="text-6xl filter drop-shadow-[0_0_25px_rgba(139,92,246,0.7)]">🃏</span>
                </div>
                {{-- วงแหวนหมุน --}}
                <div class="absolute inset-0 w-32 h-32 -top-4 -left-4 border border-violet-400/20 rounded-full animate-spin-slow mx-auto"></div>
            </div>

            {{-- หัวข้อ --}}
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-black mb-4">
                <span class="shimmer-text">ไพ่ทาโรต์ออนไลน์</span>
            </h1>
            <p class="text-purple-200/70 text-lg md:text-xl mb-3 max-w-2xl mx-auto">
                เปิดไพ่ดูดวง วิเคราะห์คำทำนายด้วย AI ที่ทันสมัยที่สุด
            </p>
            <p class="text-purple-300/50 text-sm mb-8">
                ไพ่ทาโรต์ {{ $totalCards }} ใบ • ตีความแล้วกว่า {{ number_format($totalReadings) }} ครั้ง
            </p>

            {{-- สถิติ --}}
            <div class="flex justify-center gap-6 md:gap-10 mb-10">
                <div class="text-center">
                    <div class="text-2xl md:text-3xl font-black text-white">{{ $totalCards }}</div>
                    <div class="text-purple-300/50 text-xs mt-1">ไพ่ทั้งหมด</div>
                </div>
                <div class="w-px h-12 bg-white/10"></div>
                <div class="text-center">
                    <div class="text-2xl md:text-3xl font-black text-violet-400">{{ number_format($totalReadings) }}</div>
                    <div class="text-purple-300/50 text-xs mt-1">ตีความแล้ว</div>
                </div>
                <div class="w-px h-12 bg-white/10"></div>
                <div class="text-center">
                    <div class="text-2xl md:text-3xl font-black text-fuchsia-400">{{ $todayReadings }}</div>
                    <div class="text-purple-300/50 text-xs mt-1">วันนี้</div>
                </div>
            </div>
        </div>
    </section>

    {{-- ==================== 3 ตัวเลือกหลัก ==================== --}}
    <section class="container mx-auto px-4 pb-16">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-5xl mx-auto">

            {{-- 1. Quick 1-Card Reading --}}
            <a href="{{ route('horoscope.tarot.quick') }}"
               class="group relative block overflow-hidden">
                <div class="absolute inset-0 rounded-3xl blur-xl opacity-0 group-hover:opacity-50 transition-opacity duration-500 bg-gradient-to-br from-violet-600 to-fuchsia-600"></div>
                <div class="relative bg-gradient-to-br from-violet-600/15 to-fuchsia-600/15 backdrop-blur-lg rounded-3xl p-8 border border-violet-500/20 group-hover:border-violet-500/50 transition-all duration-300 group-hover:scale-[1.02] group-hover:-translate-y-1 h-full">
                    @if($freeFortuneEnabled ?? true)
                    {{-- Badge ฟรี --}}
                    <div class="absolute top-4 right-4">
                        <span class="px-3 py-1 bg-gradient-to-r from-emerald-500 to-green-500 text-white text-xs font-bold rounded-full shadow-lg shadow-emerald-500/30">
                            ฟรี!
                        </span>
                    </div>
                    @endif

                    {{-- Icon --}}
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-violet-500 to-fuchsia-500 flex items-center justify-center text-3xl mb-5 shadow-xl shadow-violet-500/30 group-hover:shadow-violet-500/50 transition-shadow">
                        ✨
                    </div>

                    <h3 class="text-white text-xl font-bold mb-2">เปิดไพ่ 1 ใบ</h3>
                    <p class="text-purple-200/60 text-sm leading-relaxed mb-4">
                        Quick Reading — จับไพ่ 1 ใบ ตีความด้วย AI ทันที
                        เหมาะสำหรับคำถามเฉพาะเจาะจง
                    </p>

                    {{-- Features --}}
                    <ul class="space-y-1.5 mb-6">
                        <li class="flex items-center gap-2 text-xs text-purple-300/60">
                            <span class="text-emerald-400">✓</span> ไม่ต้องสมัครสมาชิก
                        </li>
                        <li class="flex items-center gap-2 text-xs text-purple-300/60">
                            <span class="text-emerald-400">✓</span> ตีความด้วย AI ทันที
                        </li>
                        <li class="flex items-center gap-2 text-xs text-purple-300/60">
                            <span class="text-emerald-400">✓</span> Animation เปิดไพ่ 3D
                        </li>
                    </ul>

                    <div class="flex items-center gap-2 text-violet-400 font-bold text-sm group-hover:text-violet-300 transition-colors">
                        เปิดไพ่เลย
                        <svg class="w-4 h-4 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </div>
            </a>

            {{-- 2. Full Reading (ลิงก์ไประบบเดิม) --}}
            <a href="{{ route('tarot.index') }}"
               class="group relative block overflow-hidden">
                <div class="absolute inset-0 rounded-3xl blur-xl opacity-0 group-hover:opacity-50 transition-opacity duration-500 bg-gradient-to-br from-amber-600 to-orange-600"></div>
                <div class="relative bg-gradient-to-br from-amber-600/15 to-orange-600/15 backdrop-blur-lg rounded-3xl p-8 border border-amber-500/20 group-hover:border-amber-500/50 transition-all duration-300 group-hover:scale-[1.02] group-hover:-translate-y-1 h-full">
                    {{-- Badge --}}
                    <div class="absolute top-4 right-4">
                        <span class="px-3 py-1 bg-gradient-to-r from-amber-500 to-orange-500 text-white text-xs font-bold rounded-full shadow-lg shadow-amber-500/30">
                            แนะนำ
                        </span>
                    </div>

                    {{-- Icon --}}
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-amber-500 to-orange-500 flex items-center justify-center text-3xl mb-5 shadow-xl shadow-amber-500/30 group-hover:shadow-amber-500/50 transition-shadow">
                        🔮
                    </div>

                    <h3 class="text-white text-xl font-bold mb-2">Full Reading</h3>
                    <p class="text-purple-200/60 text-sm leading-relaxed mb-4">
                        เลือก Spread แบบต่างๆ ตั้งแต่ 3 ใบ จนถึง Celtic Cross
                        สำหรับคำทำนายเชิงลึก
                    </p>

                    {{-- Features --}}
                    <ul class="space-y-1.5 mb-6">
                        <li class="flex items-center gap-2 text-xs text-purple-300/60">
                            <span class="text-amber-400">✓</span> หลาย Spread Types
                        </li>
                        <li class="flex items-center gap-2 text-xs text-purple-300/60">
                            <span class="text-amber-400">✓</span> คำทำนายเชิงลึก
                        </li>
                        <li class="flex items-center gap-2 text-xs text-purple-300/60">
                            <span class="text-amber-400">✓</span> บันทึกและดูย้อนหลัง
                        </li>
                    </ul>

                    <div class="flex items-center gap-2 text-amber-400 font-bold text-sm group-hover:text-amber-300 transition-colors">
                        เริ่ม Full Reading
                        <svg class="w-4 h-4 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </div>
            </a>

            {{-- 3. ตามหมวดหมู่ --}}
            <a href="{{ route('tarot.index') }}#categories"
               class="group relative block overflow-hidden">
                <div class="absolute inset-0 rounded-3xl blur-xl opacity-0 group-hover:opacity-50 transition-opacity duration-500 bg-gradient-to-br from-cyan-600 to-teal-600"></div>
                <div class="relative bg-gradient-to-br from-cyan-600/15 to-teal-600/15 backdrop-blur-lg rounded-3xl p-8 border border-cyan-500/20 group-hover:border-cyan-500/50 transition-all duration-300 group-hover:scale-[1.02] group-hover:-translate-y-1 h-full">
                    {{-- Icon --}}
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-cyan-500 to-teal-500 flex items-center justify-center text-3xl mb-5 shadow-xl shadow-cyan-500/30 group-hover:shadow-cyan-500/50 transition-shadow">
                        📋
                    </div>

                    <h3 class="text-white text-xl font-bold mb-2">ตามหมวดหมู่</h3>
                    <p class="text-purple-200/60 text-sm leading-relaxed mb-4">
                        เลือกเปิดไพ่ตามด้านที่สนใจ — ความรัก การงาน การเงิน
                        สุขภาพ หรือพัฒนาตนเอง
                    </p>

                    {{-- Categories --}}
                    <div class="flex flex-wrap gap-1.5 mb-6">
                        @foreach($categories->take(5) as $cat)
                            <span class="px-2 py-0.5 bg-cyan-500/15 border border-cyan-500/20 rounded-full text-cyan-300/70 text-[10px]">
                                {{ $cat->name_th ?? $cat->name_en }}
                            </span>
                        @endforeach
                    </div>

                    <div class="flex items-center gap-2 text-cyan-400 font-bold text-sm group-hover:text-cyan-300 transition-colors">
                        เลือกหมวดหมู่
                        <svg class="w-4 h-4 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </div>
            </a>
        </div>
    </section>

    {{-- ==================== ข้อมูลไพ่ทาโรต์ ==================== --}}
    <section class="container mx-auto px-4 pb-16">
        <div class="max-w-5xl mx-auto">
            <h2 class="text-2xl font-bold text-white mb-6 text-center">
                📖 รู้จักไพ่ทาโรต์
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Major Arcana --}}
                <div class="bg-white/5 backdrop-blur-lg rounded-2xl p-6 border border-white/10">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center text-xl shadow-lg">
                            👑
                        </div>
                        <div>
                            <h3 class="text-white font-bold">Major Arcana</h3>
                            <p class="text-purple-300/50 text-xs">22 ใบ — ไพ่หมวดใหญ่</p>
                        </div>
                    </div>
                    <p class="text-purple-200/60 text-sm leading-relaxed">
                        แทนเหตุการณ์สำคัญ จุดเปลี่ยน บทเรียนชีวิต
                        เริ่มจาก The Fool ถึง The World ครอบคลุมเส้นทางชีวิตทั้งหมด
                    </p>
                </div>

                {{-- Minor Arcana --}}
                <div class="bg-white/5 backdrop-blur-lg rounded-2xl p-6 border border-white/10">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-fuchsia-500 to-pink-600 flex items-center justify-center text-xl shadow-lg">
                            🎴
                        </div>
                        <div>
                            <h3 class="text-white font-bold">Minor Arcana</h3>
                            <p class="text-purple-300/50 text-xs">56 ใบ — 4 ชุด</p>
                        </div>
                    </div>
                    <p class="text-purple-200/60 text-sm leading-relaxed">
                        แทนเรื่องราวในชีวิตประจำวัน 4 ชุด: Wands (ไม้เท้า), Cups (ถ้วย),
                        Swords (ดาบ), Pentacles (เหรียญ) — แต่ละชุด 14 ใบ
                    </p>
                </div>
            </div>

            {{-- Spread Types --}}
            @if($spreadTypes->isNotEmpty())
            <div class="mt-6">
                <h3 class="text-lg font-bold text-white mb-4 text-center">
                    🌟 รูปแบบการวาง (Spread Types)
                </h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    @foreach($spreadTypes->take(4) as $spread)
                        <div class="bg-white/5 backdrop-blur-lg rounded-xl p-4 border border-white/10 text-center">
                            <div class="text-2xl font-black text-violet-400 mb-1">{{ $spread->card_count }}</div>
                            <div class="text-white text-xs font-bold">{{ $spread->getName('th') }}</div>
                            <div class="text-purple-300/40 text-[10px] mt-1">{{ $spread->card_count }} ใบ</div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </section>

    {{-- ==================== CTA ==================== --}}
    <section class="container mx-auto px-4 pb-16">
        <div class="max-w-3xl mx-auto bg-gradient-to-r from-violet-600/20 via-fuchsia-600/20 to-violet-600/20 border border-violet-500/30 rounded-3xl p-8 md:p-12 text-center backdrop-blur-lg">
            <div class="text-5xl mb-4">🔮</div>
            <h2 class="text-2xl md:text-3xl font-black text-white mb-3">
                พร้อมรับคำตอบจากจักรวาลหรือยัง?
            </h2>
            <p class="text-purple-200/60 text-sm mb-6">
                เปิดไพ่ 1 ใบ รับคำตีความจาก AI ได้ทันที ไม่ต้องสมัครสมาชิก
            </p>
            <a href="{{ route('horoscope.tarot.quick') }}"
               class="inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-violet-500 to-fuchsia-500 text-white font-bold rounded-2xl shadow-xl shadow-violet-500/30 hover:shadow-violet-500/50 hover:scale-105 transition-all duration-300 text-lg">
                ✨ เปิดไพ่เลย
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
    </section>

    {{-- ==================== SEO Content ==================== --}}
    <section class="container mx-auto px-4 pb-12">
        <div class="bg-white/3 rounded-2xl p-6 border border-white/5">
            <h2 class="text-lg font-bold text-white/60 mb-3">ไพ่ทาโรต์ออนไลน์ — {{ ($freeFortuneEnabled ?? true) ? 'ดูดวงฟรี ' : '' }}ตีความด้วย AI</h2>
            <p class="text-purple-300/40 text-xs leading-relaxed">
                เปิดไพ่ทาโรต์ออนไลน์{{ ($freeFortuneEnabled ?? true) ? 'ฟรี ' : ' ' }}ไพ่ทาโรต์ 78 ใบ Major Arcana 22 ใบ Minor Arcana 56 ใบ
                ตีความด้วยปัญญาประดิษฐ์ AI ที่ทันสมัยที่สุด Quick Reading 1 ใบ Full Reading
                3 ใบ อดีต ปัจจุบัน อนาคต Celtic Cross และอื่นๆ อีกมากมาย
                ครอบคลุมทุกด้านของชีวิต ความรัก การงาน การเงิน สุขภาพ พัฒนาตนเอง
            </p>
        </div>
    </section>

</div>

<script>
function tarotHome() {
    return {
        init() {
            // Animation สำหรับ cards
        },
    };
}
</script>
@endsection
