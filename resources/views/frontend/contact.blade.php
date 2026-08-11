@extends('layouts.landing')

@section('title', 'ติดต่อเรา')

@section('content')
{{-- พื้นหลังเข้มของหน้านี้เอง
     ⚠️ เดิมหน้านี้ไม่ได้กำหนดพื้นหลัง แล้วไปพึ่ง dark mode ของ layouts.landing
     พอผู้ใช้เข้ามาครั้งแรก (โหมดสว่าง = bg-slate-50) ตัวหนังสือขาวจะจมหายไปทั้งหน้า
     จึงกำหนดพื้นเข้มไว้ที่หน้านี้เอง ให้อ่านออกทั้งสองโหมด --}}
<div class="min-h-screen relative z-10 py-20 lg:py-28 overflow-hidden bg-gradient-to-b from-slate-900 via-slate-900 to-slate-950">
    {{-- ภาพประกอบช่องทางติดต่อ (เจนเอง เก็บที่ public/images/art) --}}
    <x-art.backdrop image="cos-contact" tone="dark" :opacity="0.4" mask="vignette" />
    <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- หัวข้อ --}}
        <div class="text-center mb-12">
            <h1 class="text-4xl md:text-5xl font-bold text-white mb-4">ติดต่อเรา</h1>
            <p class="text-lg text-slate-300">เราพร้อมช่วยเหลือ — เลือกช่องทางที่สะดวกสำหรับคุณ</p>
        </div>

        {{-- การ์ดช่องทางติดต่อ (glass card สอดคล้องกับธีม) --}}
        <div class="grid md:grid-cols-2 gap-6">
            {{-- Email --}}
            <a href="mailto:support@thaiprompt.com"
               class="group bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-6 hover:bg-white/10 hover:border-amber-400/40 transition-all">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center mb-4">
                    <i class="fas fa-envelope text-white text-xl"></i>
                </div>
                <div class="text-sm font-semibold uppercase tracking-wider text-amber-400 mb-1">EMAIL</div>
                <h3 class="text-lg font-bold text-white mb-2">ส่งอีเมลถึงเรา</h3>
                <p class="text-slate-300 group-hover:text-white transition-colors">support@thaiprompt.com</p>
            </a>

            {{-- GitHub --}}
            <a href="https://github.com/xjanova" target="_blank" rel="noopener"
               class="group bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-6 hover:bg-white/10 hover:border-amber-400/40 transition-all">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-slate-700 to-slate-900 flex items-center justify-center mb-4">
                    <i class="fab fa-github text-white text-xl"></i>
                </div>
                <div class="text-sm font-semibold uppercase tracking-wider text-amber-400 mb-1">GITHUB</div>
                <h3 class="text-lg font-bold text-white mb-2">โครงการของเรา</h3>
                <p class="text-slate-300 group-hover:text-white transition-colors">@xjanova</p>
            </a>
        </div>

        {{-- ปุ่มกลับหน้าแรก --}}
        <div class="text-center mt-12">
            <a href="{{ route('home') }}"
               class="inline-flex items-center gap-2 px-6 py-3 bg-white/10 hover:bg-white/20 border border-white/20 text-white font-semibold rounded-xl transition-colors">
                <i class="fas fa-arrow-left"></i>
                กลับหน้าแรก
            </a>
        </div>
    </div>
</div>
@endsection
