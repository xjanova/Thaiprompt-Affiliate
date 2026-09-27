@extends('layouts.landing')

@section('title', 'ติดต่อเรา')

@section('content')
@php
    // ธีมโนวา (config shop.nova_public) — ปิดได้ แล้วหน้าเดิมกลับมา
    $ctNova = config('shop.nova_public', true);
    $ctR = fn (string $name, $params = [], ?string $fallback = null) => \Illuminate\Support\Facades\Route::has($name)
        ? route($name, $params)
        : ($fallback ?? url('/'));
    $ctEmail = \App\Support\ContactInfo::supportEmail();
@endphp

@if($ctNova)
{{-- ติดต่อเรา ธีมโนวา: ฉากกลางคืน + การ์ดกระจกกรมท่าขอบทอง + วัตถุตกแต่ง --}}
<section class="nv-page-hero nv-contact">
    <div class="nv-bg" aria-hidden="true">
        <div class="nv-sky"></div>
        <div class="nv-aurora"><i class="a1"></i><i class="a2"></i><i class="a3"></i></div>
        <div class="nv-temple"></div>
        <div class="nv-vignette"></div>
    </div>
    <img class="nv-page-hero__kanok nv-page-hero__kanok--l" src="{{ asset('images/nova/brand/kanok-gold.webp') }}" alt="" aria-hidden="true">
    <img class="nv-page-hero__kanok nv-page-hero__kanok--r" src="{{ asset('images/nova/brand/kanok-gold.webp') }}" alt="" aria-hidden="true">
    <img class="nv-deco nv-hide-m" src="{{ asset('images/nova/deco/bell.webp') }}" alt="" aria-hidden="true" decoding="async" style="--x:7%;--y:30%;--w:74px;--z:12;--t:8s;--r0:-4deg;--r1:4deg">
    <img class="nv-deco nv-hide-m" src="{{ asset('images/nova/deco/umbrella.webp') }}" alt="" aria-hidden="true" decoding="async" style="--x:84%;--y:18%;--w:150px;--z:16;--t:10s;--dl:-3s;--r0:-2deg;--r1:3deg">

    <div class="nv-page-hero__in">
        <p class="nv-eyebrow"><span class="nv-eyebrow__line"></span>CONTACT US<span class="nv-eyebrow__line"></span></p>
        <h1 class="nv-page-hero__title">ติดต่อ<span class="nv-foil">เรา</span></h1>
        <p class="nv-page-hero__sub">เราพร้อมช่วยเหลือ — เลือกช่องทางที่สะดวกสำหรับคุณ</p>

        <div class="nv-contact__grid">
            {{-- 📬 (2026-09-25) PLAY-25: อีเมลชุดเดียวกับแอป/นโยบายความเป็นส่วนตัว --}}
            <a class="nv-ccard" href="mailto:{{ $ctEmail }}">
                <span class="nv-ccard__ic"><i class="fas fa-envelope" aria-hidden="true"></i></span>
                <span class="nv-ccard__en">EMAIL</span>
                <b class="nv-ccard__title">ส่งอีเมลถึงเรา</b>
                <span class="nv-ccard__text">{{ $ctEmail }}</span>
            </a>
            <a class="nv-ccard" href="{{ $ctR('wiki.index') }}">
                <span class="nv-ccard__ic"><i class="fas fa-book-open" aria-hidden="true"></i></span>
                <span class="nv-ccard__en">HELP CENTER</span>
                <b class="nv-ccard__title">คู่มือการใช้งาน</b>
                <span class="nv-ccard__text">วิธีใช้ร้านค้า ตลาดสด ไรเดอร์ และกระเป๋าเงิน</span>
            </a>
            <a class="nv-ccard" href="{{ $ctR('page.show', 'faq') }}">
                <span class="nv-ccard__ic"><i class="fas fa-circle-question" aria-hidden="true"></i></span>
                <span class="nv-ccard__en">FAQ</span>
                <b class="nv-ccard__title">คำถามที่พบบ่อย</b>
                <span class="nv-ccard__text">คำตอบเรื่องสมัครสมาชิก การสั่งซื้อ และการจัดส่ง</span>
            </a>
            <a class="nv-ccard" href="https://github.com/xjanova" target="_blank" rel="noopener">
                <span class="nv-ccard__ic"><i class="fab fa-github" aria-hidden="true"></i></span>
                <span class="nv-ccard__en">GITHUB</span>
                <b class="nv-ccard__title">โครงการของเรา</b>
                <span class="nv-ccard__text">@xjanova</span>
            </a>
        </div>

        <a href="{{ $ctR('home') }}" class="nv-btn nv-btn--ghost nv-btn--lg nv-page-hero__back"><i class="fas fa-arrow-left" aria-hidden="true"></i> กลับหน้าแรก</a>
    </div>
</section>
<x-nova.footer />
@else
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
            {{-- 📬 (2026-09-25) PLAY-25: อีเมลชุดเดียวกับแอป/นโยบายความเป็นส่วนตัว (เดิม .com ไม่ตรงกับแอป) --}}
            <a href="mailto:{{ $ctEmail }}"
               class="group bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-6 hover:bg-white/10 hover:border-amber-400/40 transition-all">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center mb-4">
                    <i class="fas fa-envelope text-white text-xl"></i>
                </div>
                <div class="text-sm font-semibold uppercase tracking-wider text-amber-400 mb-1">EMAIL</div>
                <h3 class="text-lg font-bold text-white mb-2">ส่งอีเมลถึงเรา</h3>
                <p class="text-slate-300 group-hover:text-white transition-colors">{{ $ctEmail }}</p>
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
@endif
@endsection
