@extends('layouts.landing')

@section('title', 'ติดต่อเรา')

@section('content')
@php
    $ctR = fn (string $name, $params = [], ?string $fallback = null) => \Illuminate\Support\Facades\Route::has($name)
        ? route($name, $params)
        : ($fallback ?? url('/'));
    $ctEmail = \App\Support\ContactInfo::supportEmail();
@endphp

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
            <a class="nv-ccard" href="{{ $ctR('faq') }}">
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

        {{-- ข้อมูลนิติบุคคล hardcode ที่นี่ (เดิมอยู่ในหน้า CMS "ติดต่อเรา") --}}
        <div class="nv-ccard mt-6">
            <span class="nv-ccard__ic"><i class="fas fa-building" aria-hidden="true"></i></span>
            <span class="nv-ccard__en">COMPANY</span>
            <b class="nv-ccard__title">บริษัท เอ็กซ์แมน เอ็นเตอร์ไพรส์ จำกัด (XMAN ENTERPRISE CO., LTD.)</b>
            <span class="nv-ccard__text">ไทยพร๊อมท์ (Thaiprompt / TP-Affiliate) ดำเนินการและให้บริการโดยบริษัทนี้ · เลขทะเบียนนิติบุคคล 0675563000120</span>
            <span class="nv-ccard__text">โทร <a href="tel:0909335963">090-9335963</a> · <a href="mailto:{{ $ctEmail }}">{{ $ctEmail }}</a></span>
            <span class="nv-ccard__text"><a href="https://main.thaiprompt.online">main.thaiprompt.online</a> · <a href="https://xman4289.com" target="_blank" rel="noopener noreferrer">xman4289.com</a></span>
        </div>

        <a href="{{ $ctR('home') }}" class="nv-btn nv-btn--ghost nv-btn--lg nv-page-hero__back"><i class="fas fa-arrow-left" aria-hidden="true"></i> กลับหน้าแรก</a>
    </div>
</section>
<x-nova.footer />
@endsection
