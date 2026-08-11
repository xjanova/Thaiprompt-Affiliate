@extends('layouts.app')

@section('content')
{{-- หน้าหลักไพ่ทาโร่ต์ - V3 Premium Design --}}
<div class="min-h-screen bg-gradient-to-br from-slate-950 via-purple-950 to-indigo-950 relative overflow-hidden"
     x-data="tarotHome()"
     x-init="init()">

    {{-- พื้นหลังแบบ Mystical --}}
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        {{-- ดาวกระพริบ --}}
        <div class="stars-container absolute inset-0">
            <template x-for="star in stars" :key="star.id">
                <div class="absolute rounded-full bg-white"
                     :style="`left: ${star.x}%; top: ${star.y}%; width: ${star.size}px; height: ${star.size}px; opacity: ${star.opacity}; animation: twinkle ${star.duration}s ease-in-out infinite; animation-delay: ${star.delay}s;`">
                </div>
            </template>
        </div>

        {{-- วงแหวนเวทย์มนต์ --}}
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] opacity-10">
            <div class="absolute inset-0 border-2 border-purple-400 rounded-full animate-spin-slow"></div>
            <div class="absolute inset-8 border border-pink-400 rounded-full animate-spin-slow-reverse"></div>
            <div class="absolute inset-16 border border-cyan-400 rounded-full animate-spin-slow" style="animation-duration: 30s;"></div>
        </div>

        {{-- กลุ่มหมอก Gradient --}}
        <div class="absolute -top-40 -right-40 w-96 h-96 bg-purple-600/20 rounded-full blur-[100px] animate-pulse"></div>
        <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-pink-600/20 rounded-full blur-[100px] animate-pulse" style="animation-delay: 1.5s;"></div>
        <div class="absolute top-1/3 left-1/4 w-64 h-64 bg-cyan-600/10 rounded-full blur-[80px] animate-pulse" style="animation-delay: 2.5s;"></div>
    </div>

    {{-- Hero Section --}}
    <div class="relative z-10 overflow-hidden">
        {{-- ภาพประกอบไพ่ทาโรต์ลอยในจักรวาล (เจนเอง เก็บที่ public/images/art) --}}
        <x-art.backdrop image="cos-tarot" tone="dark" :opacity="0.5" mask="vignette" />
        <div class="container relative mx-auto px-4 py-16 md:py-24">
            <div class="text-center max-w-4xl mx-auto">
                {{-- โลโก้ไพ่ทาโร่ต์แบบ 3D --}}
                <div class="mb-8 perspective-1000">
                    <div class="inline-block transform-gpu hover:rotate-y-12 transition-all duration-700">
                        <div class="relative">
                            {{-- เอฟเฟกต์เรืองแสง --}}
                            <div class="absolute inset-0 bg-gradient-to-br from-purple-500 via-pink-500 to-cyan-500 rounded-3xl blur-2xl opacity-50 animate-pulse"></div>

                            {{-- การ์ดหลัก --}}
                            <div class="relative bg-gradient-to-br from-purple-900/80 via-indigo-900/80 to-slate-900/80 backdrop-blur-xl rounded-3xl p-8 border border-white/20 shadow-2xl">
                                <div class="text-8xl md:text-9xl mb-2 filter drop-shadow-[0_0_30px_rgba(168,85,247,0.8)]">
                                    🔮
                                </div>
                                <div class="absolute -top-3 -right-3 w-8 h-8 bg-gradient-to-br from-yellow-400 to-amber-500 rounded-full animate-bounce shadow-lg shadow-yellow-400/50">
                                    <span class="flex items-center justify-center h-full text-xs font-bold">✨</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- หัวข้อหลัก --}}
                <h1 class="text-5xl md:text-7xl font-black mb-6 relative">
                    <span class="bg-gradient-to-r from-purple-300 via-pink-300 to-cyan-300 bg-clip-text text-transparent
                                 drop-shadow-[0_4px_24px_rgba(168,85,247,0.5)]">
                        ไพ่ทาโร่ต์
                    </span>
                    <span class="block text-3xl md:text-4xl mt-2 font-bold text-white/90">
                        Mystical Tarot Reading
                    </span>
                </h1>

                {{-- คำอธิบาย --}}
                <p class="text-xl md:text-2xl text-purple-200/90 mb-10 leading-relaxed">
                    ค้นพบคำตอบจากจักรวาล ไขความลึกลับแห่งชะตา<br class="hidden md:block">
                    ด้วยไพ่ทาโร่ต์ 78 ใบ แบบ 3D Interactive
                </p>

                {{-- ปุ่ม CTA --}}
                <div class="flex flex-col sm:flex-row justify-center gap-4">
                    <a href="#categories"
                       class="group relative overflow-hidden px-10 py-5 bg-gradient-to-r from-purple-600 via-pink-600 to-purple-600 rounded-2xl font-bold text-xl text-white shadow-2xl shadow-purple-500/30 transform hover:scale-105 transition-all duration-300">
                        {{-- Shine Effect --}}
                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
                        <span class="relative flex items-center justify-center gap-3">
                            <svg class="w-6 h-6 animate-pulse" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 2a8 8 0 100 16 8 8 0 000-16zm0 14a6 6 0 110-12 6 6 0 010 12z"/>
                            </svg>
                            เริ่มทำนายดวง
                        </span>
                    </a>

                    @auth
                    <a href="{{ route('tarot.history') }}"
                       class="px-10 py-5 bg-white/10 backdrop-blur-lg border-2 border-white/30 rounded-2xl font-bold text-xl text-white hover:bg-white/20 transition-all duration-300">
                        📜 ประวัติการทำนาย
                    </a>
                    @endauth
                </div>

                {{-- สถิติ --}}
                <div class="mt-16 grid grid-cols-3 gap-4 max-w-2xl mx-auto">
                    <div class="bg-white/5 backdrop-blur-lg rounded-2xl p-4 border border-white/10">
                        <div class="text-3xl md:text-4xl font-black bg-gradient-to-r from-purple-400 to-pink-400 bg-clip-text text-transparent">78</div>
                        <div class="text-purple-300/80 text-sm">ไพ่ทาโร่ต์</div>
                    </div>
                    <div class="bg-white/5 backdrop-blur-lg rounded-2xl p-4 border border-white/10">
                        <div class="text-3xl md:text-4xl font-black bg-gradient-to-r from-cyan-400 to-blue-400 bg-clip-text text-transparent">3D</div>
                        <div class="text-purple-300/80 text-sm">Interactive</div>
                    </div>
                    <div class="bg-white/5 backdrop-blur-lg rounded-2xl p-4 border border-white/10">
                        <div class="text-3xl md:text-4xl font-black bg-gradient-to-r from-amber-400 to-yellow-400 bg-clip-text text-transparent">AI</div>
                        <div class="text-purple-300/80 text-sm">ตีความ</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Categories Section --}}
    <div id="categories" class="relative z-10 container mx-auto px-4 py-16">
        <div class="text-center mb-12">
            <h2 class="text-4xl md:text-5xl font-black text-white mb-4">
                เลือกหมวดหมู่ทำนาย
            </h2>
            <p class="text-purple-300/80 text-lg">เลือกหัวข้อที่คุณต้องการค้นหาคำตอบ</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 max-w-6xl mx-auto">
            @foreach($categories as $category)
            <div class="group perspective-1000 cursor-pointer"
                 onclick="window.location='{{ route('tarot.category', $category->slug) }}'">

                <div class="relative transform-gpu transition-all duration-500 group-hover:rotate-y-6 group-hover:scale-105">
                    {{-- Glow Effect --}}
                    <div class="absolute inset-0 bg-gradient-to-br from-purple-500/40 via-pink-500/40 to-cyan-500/40 rounded-3xl blur-xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>

                    {{-- Card Body --}}
                    <div class="relative bg-gradient-to-br from-slate-900/90 via-purple-900/80 to-slate-900/90 backdrop-blur-xl rounded-3xl p-8 border border-white/10 group-hover:border-purple-400/50 shadow-2xl transition-all duration-500">

                        {{-- หมวดหมู่ Icon --}}
                        <div class="relative mb-6">
                            <div class="w-20 h-20 mx-auto bg-gradient-to-br rounded-2xl flex items-center justify-center shadow-lg transform group-hover:scale-110 group-hover:rotate-6 transition-all duration-500"
                                 style="background: linear-gradient(135deg, {{ $category->color ?? '#9333ea' }}, {{ $category->color ?? '#ec4899' }});">
                                <span class="text-4xl">
                                    @if($category->icon)
                                        <i class="fas {{ $category->icon }} text-white drop-shadow-lg"></i>
                                    @else
                                        ⭐
                                    @endif
                                </span>
                            </div>
                            {{-- หยดน้ำสะท้อน --}}
                            <div class="absolute -bottom-2 left-1/2 -translate-x-1/2 w-12 h-3 bg-gradient-to-r from-transparent via-white/20 to-transparent rounded-full blur-sm"></div>
                        </div>

                        {{-- ชื่อหมวดหมู่ --}}
                        <h3 class="text-2xl font-bold text-white text-center mb-3 group-hover:text-purple-200 transition-colors">
                            {{ $category->name_th }}
                        </h3>

                        {{-- คำอธิบาย --}}
                        <p class="text-purple-300/70 text-center mb-6 line-clamp-2">
                            {{ $category->description_th }}
                        </p>

                        {{-- ราคา Badge --}}
                        <div class="text-center">
                            @if($category->price > 0)
                                <div class="inline-flex flex-col items-center">
                                    <span class="inline-flex items-center gap-2 px-5 py-2 bg-gradient-to-r from-amber-500 to-yellow-500 text-slate-900 rounded-full font-bold shadow-lg shadow-amber-500/30">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.736 6.979C9.208 6.193 9.696 6 10 6c.304 0 .792.193 1.264.979a1 1 0 001.715-1.029C12.279 4.784 11.232 4 10 4s-2.279.784-2.979 1.95c-.285.475-.507 1-.67 1.55H6a1 1 0 000 2h.013a9.358 9.358 0 000 1H6a1 1 0 100 2h.351c.163.55.385 1.075.67 1.55C7.721 15.216 8.768 16 10 16s2.279-.784 2.979-1.95a1 1 0 10-1.715-1.029c-.472.786-.96.979-1.264.979-.304 0-.792-.193-1.264-.979a4.265 4.265 0 01-.264-.521H10a1 1 0 100-2H8.017a7.36 7.36 0 010-1H10a1 1 0 100-2H8.472c.08-.185.167-.36.264-.521z"/>
                                        </svg>
                                        {{ number_format($category->price, 0) }} บาท
                                    </span>
                                    @if($category->is_free_first)
                                        <span class="mt-2 text-sm text-emerald-400 font-medium flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                            ฟรีครั้งแรกของวัน
                                        </span>
                                    @endif
                                </div>
                            @else
                                <span class="inline-flex items-center gap-2 px-6 py-2 bg-gradient-to-r from-emerald-500 to-teal-500 text-white rounded-full font-bold shadow-lg shadow-emerald-500/30">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5 5a3 3 0 015-2.236A3 3 0 0114.83 6H16a2 2 0 110 4h-5V9a1 1 0 10-2 0v1H4a2 2 0 110-4h1.17C5.06 5.687 5 5.35 5 5zm4 1V5a1 1 0 10-1 1h1zm3 0a1 1 0 10-1-1v1h1z" clip-rule="evenodd"/>
                                        <path d="M9 11H3v5a2 2 0 002 2h4v-7zM11 18h4a2 2 0 002-2v-5h-6v7z"/>
                                    </svg>
                                    ฟรี
                                </span>
                            @endif
                        </div>

                        {{-- ปุ่ม Hover --}}
                        <div class="mt-6 opacity-0 group-hover:opacity-100 transition-all duration-300 transform translate-y-4 group-hover:translate-y-0">
                            <div class="w-full py-3 bg-gradient-to-r from-purple-500 to-pink-500 rounded-xl text-white font-semibold text-center shadow-lg">
                                เริ่มทำนาย →
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Features Section --}}
    <div class="relative z-10 container mx-auto px-4 py-20">
        <div class="text-center mb-12">
            <h2 class="text-4xl md:text-5xl font-black text-white mb-4">
                ทำไมต้องเลือกเรา
            </h2>
            <p class="text-purple-300/80 text-lg">ประสบการณ์การดูไพ่ทาโร่ต์ที่ไม่เหมือนใคร</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-5xl mx-auto">
            {{-- Feature 1: ไพ่ครบชุด --}}
            <div class="group text-center p-8 bg-gradient-to-br from-slate-900/60 to-purple-900/40 backdrop-blur-lg rounded-3xl border border-white/10 hover:border-purple-400/50 transition-all duration-300 hover:transform hover:-translate-y-2">
                <div class="relative inline-block mb-6">
                    <div class="w-24 h-24 bg-gradient-to-br from-purple-500 to-pink-500 rounded-2xl flex items-center justify-center shadow-xl shadow-purple-500/30 group-hover:shadow-purple-500/50 transition-all duration-300 group-hover:scale-110">
                        <span class="text-5xl">🎴</span>
                    </div>
                    <div class="absolute -top-2 -right-2 w-8 h-8 bg-gradient-to-br from-amber-400 to-yellow-500 rounded-full flex items-center justify-center text-sm font-bold text-slate-900 shadow-lg">
                        78
                    </div>
                </div>
                <h3 class="text-2xl font-bold text-white mb-3">ไพ่ทาโร่ต์ครบชุด</h3>
                <p class="text-purple-300/70">ใช้ไพ่ทาโร่ต์แท้ครบ 78 ใบ<br>ทั้ง Major และ Minor Arcana</p>
            </div>

            {{-- Feature 2: 3D Animation --}}
            <div class="group text-center p-8 bg-gradient-to-br from-slate-900/60 to-cyan-900/40 backdrop-blur-lg rounded-3xl border border-white/10 hover:border-cyan-400/50 transition-all duration-300 hover:transform hover:-translate-y-2">
                <div class="relative inline-block mb-6">
                    <div class="w-24 h-24 bg-gradient-to-br from-cyan-500 to-blue-500 rounded-2xl flex items-center justify-center shadow-xl shadow-cyan-500/30 group-hover:shadow-cyan-500/50 transition-all duration-300 group-hover:scale-110 group-hover:rotate-6">
                        <span class="text-5xl">✨</span>
                    </div>
                </div>
                <h3 class="text-2xl font-bold text-white mb-3">3D Interactive</h3>
                <p class="text-purple-300/70">เลือกและพลิกไพ่แบบ 3D<br>สมจริงด้วย WebGL</p>
            </div>

            {{-- Feature 3: AI ตีความ --}}
            <div class="group text-center p-8 bg-gradient-to-br from-slate-900/60 to-amber-900/40 backdrop-blur-lg rounded-3xl border border-white/10 hover:border-amber-400/50 transition-all duration-300 hover:transform hover:-translate-y-2">
                <div class="relative inline-block mb-6">
                    <div class="w-24 h-24 bg-gradient-to-br from-amber-500 to-orange-500 rounded-2xl flex items-center justify-center shadow-xl shadow-amber-500/30 group-hover:shadow-amber-500/50 transition-all duration-300 group-hover:scale-110">
                        <span class="text-5xl">🧠</span>
                    </div>
                </div>
                <h3 class="text-2xl font-bold text-white mb-3">AI ตีความอัจฉริยะ</h3>
                <p class="text-purple-300/70">ระบบ AI วิเคราะห์และตีความ<br>เฉพาะตัวสำหรับคุณ</p>
            </div>
        </div>
    </div>

    {{-- CTA Section --}}
    <div class="relative z-10 container mx-auto px-4 py-16 mb-8">
        <div class="max-w-4xl mx-auto bg-gradient-to-r from-purple-900/80 via-pink-900/60 to-purple-900/80 backdrop-blur-xl rounded-3xl p-8 md:p-12 border border-white/10 text-center">
            <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">
                พร้อมค้นหาคำตอบจากจักรวาลหรือยัง?
            </h2>
            <p class="text-purple-200/80 text-lg mb-8">
                เลือกไพ่ที่เรียกหาคุณ และค้นพบสิ่งที่โชคชะตากำลังบอก
            </p>
            <a href="#categories"
               class="inline-flex items-center gap-3 px-10 py-4 bg-white text-purple-900 rounded-2xl font-bold text-xl hover:bg-purple-100 transition-all duration-300 shadow-2xl shadow-white/20">
                <span>🔮</span>
                เริ่มทำนายเลย
                <svg class="w-6 h-6 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                </svg>
            </a>
        </div>
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    /* Animation สำหรับดาวกระพริบ */
    @keyframes twinkle {
        0%, 100% { opacity: 0.3; transform: scale(1); }
        50% { opacity: 1; transform: scale(1.5); }
    }

    /* หมุนช้าๆ */
    @keyframes spin-slow {
        from { transform: translate(-50%, -50%) rotate(0deg); }
        to { transform: translate(-50%, -50%) rotate(360deg); }
    }
    .animate-spin-slow {
        animation: spin-slow 40s linear infinite;
    }
    .animate-spin-slow-reverse {
        animation: spin-slow 35s linear infinite reverse;
    }

    /* Perspective สำหรับ 3D */
    .perspective-1000 { perspective: 1000px; }
    .rotate-y-6 { transform: rotateY(6deg); }
    .rotate-y-12 { transform: rotateY(12deg); }

    /* Line Clamp */
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>
@endpush

@push('scripts')
<script>
    // Alpine.js Component สำหรับหน้า Tarot
    function tarotHome() {
        return {
            stars: [],

            init() {
                // สร้างดาวกระพริบ
                this.generateStars();
            },

            generateStars() {
                const count = 100;
                this.stars = [];
                for (let i = 0; i < count; i++) {
                    this.stars.push({
                        id: i,
                        x: Math.random() * 100,
                        y: Math.random() * 100,
                        size: Math.random() * 3 + 1,
                        opacity: Math.random() * 0.7 + 0.3,
                        duration: Math.random() * 3 + 2,
                        delay: Math.random() * 3
                    });
                }
            }
        };
    }
</script>
@endpush
@endsection
