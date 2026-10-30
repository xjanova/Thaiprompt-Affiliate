@extends('layouts.landing')

@section('title', 'คำถามที่พบบ่อย')

@section('content')
@php
    $faqEmail = \App\Support\ContactInfo::supportEmail();
    $faqItems = [
        ['q' => 'ระบบ Affiliate คืออะไร?', 'a' => 'ระบบ Affiliate คือระบบการตลาดแบบแนะนำ ที่คุณจะได้รับค่าคอมมิชชั่นเมื่อแนะนำลูกค้ามาใช้บริการของเรา'],
        ['q' => 'สมัครสมาชิกได้อย่างไร?', 'a' => 'คลิกที่ปุ่ม "สมัครสมาชิก" ที่มุมบนขวา กรอกข้อมูลและยืนยันอีเมล จากนั้นคุณก็สามารถเริ่มใช้งานได้ทันที'],
        ['q' => 'ได้รับค่าคอมมิชชั่นเมื่อไหร่?', 'a' => 'คุณจะได้รับค่าคอมมิชชั่นเมื่อผู้ที่คุณแนะนำทำการซื้อสินค้าหรือใช้บริการ โดยจะถูกโอนเข้าบัญชีของคุณภายใน 7-15 วันทำการ'],
        ['q' => 'มีค่าใช้จ่ายในการสมัครหรือไม่?', 'a' => 'ไม่มีค่าใช้จ่ายใดๆ ในการสมัครสมาชิก คุณสามารถสมัครและเริ่มใช้งานได้ฟรี 100%'],
        ['q' => 'ติดต่อทีมงานได้อย่างไร?', 'a' => 'สามารถติดต่อเราได้ผ่านหน้า "ติดต่อเรา" หรือส่งอีเมลมาที่ '.$faqEmail],
    ];
@endphp

{{-- คำถามที่พบบ่อย: เนื้อหา hardcode ที่นี่ที่เดียว ไม่ได้อ่านจากฐานข้อมูล --}}
<section class="nv-page-hero nv-contact">
    <div class="nv-bg" aria-hidden="true">
        <div class="nv-sky"></div>
        <div class="nv-aurora"><i class="a1"></i><i class="a2"></i><i class="a3"></i></div>
        <div class="nv-temple"></div>
        <div class="nv-vignette"></div>
    </div>
    <img class="nv-page-hero__kanok nv-page-hero__kanok--l" src="{{ asset('images/nova/brand/kanok-gold.webp') }}" alt="" aria-hidden="true">
    <img class="nv-page-hero__kanok nv-page-hero__kanok--r" src="{{ asset('images/nova/brand/kanok-gold.webp') }}" alt="" aria-hidden="true">

    <div class="nv-page-hero__in">
        <p class="nv-eyebrow"><span class="nv-eyebrow__line"></span>FAQ<span class="nv-eyebrow__line"></span></p>
        <h1 class="nv-page-hero__title">คำถาม<span class="nv-foil">ที่พบบ่อย</span></h1>
        <p class="nv-page-hero__sub">คำตอบเรื่องการสมัครสมาชิก ค่าคอมมิชชั่น และการติดต่อทีมงาน</p>

        <div class="nv-contact__grid">
            @foreach($faqItems as $item)
                <div class="nv-ccard">
                    <b class="nv-ccard__title">{{ $item['q'] }}</b>
                    <span class="nv-ccard__text">{{ $item['a'] }}</span>
                </div>
            @endforeach
        </div>

        <a href="{{ route('contact') }}" class="nv-btn nv-btn--ghost nv-btn--lg nv-page-hero__back"><i class="fas fa-envelope" aria-hidden="true"></i> ติดต่อเรา</a>
    </div>
</section>
<x-nova.footer />
@endsection
