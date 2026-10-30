{{--
 |----------------------------------------------------------------------------
 | Frontend Layout V4 — ธีมนวลทองคำ (Nuan Gold Clay) สำหรับหน้าสาธารณะ
 |----------------------------------------------------------------------------
 | ✅ เปลือกสาธารณะ (ไม่มี sidebar/topbar ของหลังบ้าน) — ใช้กับหน้าแรก/หน้า public
 | ✅ สีแบรนด์ตามโลโก้ตายตัว (html.tp-brand ใน resources/css/theme-v4.css)
 | การใช้งาน:  @extends('layouts.frontend-v4')  →  @section('content') ... @endsection
 --}}
<!DOCTYPE html>
<html lang="th" class="tp-brand">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'ไทยพร๊อมท์ · แพลตฟอร์มเพื่อชีวิตที่ดีกว่า') · {{ config('app.brand_name', 'Thai Prompt') }}</title>
    <meta name="description" content="@yield('meta_description', 'ไทยพร๊อมท์ — แพลตฟอร์มคนไทย รวมอีคอมเมิร์ซ ไรเดอร์ ตลาดสด กระเป๋าเงินดิจิทัล และระบบปันผลโปร่งใสด้วย Blockchain')">
    @yield('meta')

    <link rel="icon" type="image/x-icon" href="{{ asset('images/brand/favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/brand/favicon-32.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">

    {{-- ฟอนต์: Anuphan (ไทย/ทั่วไป) + Sora (ตัวเลข/หัวข้อ) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anuphan:wght@400;500;600;700&family=Sora:wght@500;600;700;800&display=swap" rel="stylesheet">

    {{-- Font Awesome 6.5.1 --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    {{-- Vite: Tailwind base (app.css) + ธีม V4 + Alpine (app.js) --}}
    @vite(['resources/css/app.css', 'resources/css/theme-v4.css', 'resources/js/app.js'])

    {{-- หน้าสาธารณะเป็นโหมดสว่างเสมอ — store 'theme' ใน app.js อาจใส่ class dark ตามค่าเดิมใน localStorage --}}
    <script>document.addEventListener('DOMContentLoaded', () => document.documentElement.classList.remove('dark'));</script>

    @stack('styles')

    {{-- Structured Data (Schema.org JSON-LD) — Organization + WebSite สำหรับ Google AI Overviews / Gemini --}}
    {!! render_global_structured_data() !!}
</head>
{{-- คลาสของ <body>: หน้าใส่เพิ่มเองได้ผ่าน @section('body_class', '...')
     ธีมโนวาบนหน้าสาธารณะ = nv-body — มีผลเฉพาะหน้าที่โหลด theme-nova/nova.css (แถบหัวโนวาโหลดให้เอง) --}}
@php
    $tpBodyClass = collect(explode(' ', 'tp-root '.trim($__env->yieldContent('body_class')).' nv-body'))
        ->filter()->unique()->implode(' ');
@endphp
<body class="{{ $tpBodyClass }}">

    {{-- พื้นหลังลาวา (3 วงเบลอ) --}}
    <div class="tp-lava" aria-hidden="true"><i class="l1"></i><i class="l2"></i><i class="l3"></i></div>

    <div style="position:relative; z-index:1; min-height:100vh; display:flex; flex-direction:column;">
        @yield('content')
    </div>

    {{-- Toast: session flash --}}
    <div class="fixed bottom-4 right-4 z-[100] space-y-2"
         x-data="{ items: [] }"
         @notify.window="items.push($event.detail); setTimeout(() => items.shift(), 3500)">
        <template x-for="(n, i) in items" :key="i">
            <div x-transition.opacity class="tp-card" style="padding:13px 16px; min-width:280px; display:flex; align-items:center; gap:10px;">
                <span class="tp-pill"
                      :style="n.type==='success' ? 'background:var(--sf-ok,#5aa07e);color:var(--on-accent,#fff)' : n.type==='error' ? 'background:var(--sf-sale,#d9534f);color:var(--on-accent,#fff)' : 'background:var(--accent1);color:var(--on-accent,#fff)'"
                      x-text="n.type==='success' ? '✓' : n.type==='error' ? '!' : 'i'"></span>
                <span style="font-size:13.5px; color:var(--ink);" x-text="n.message"></span>
            </div>
        </template>
    </div>
    @if(session('success'))<div x-data x-init="$dispatch('notify',{type:'success',message:@js(session('success'))})"></div>@endif
    @if(session('error'))<div x-data x-init="$dispatch('notify',{type:'error',message:@js(session('error'))})"></div>@endif
    @if(session('info'))<div x-data x-init="$dispatch('notify',{type:'info',message:@js(session('info'))})"></div>@endif
    @if(session('warning'))<div x-data x-init="$dispatch('notify',{type:'info',message:@js(session('warning'))})"></div>@endif
    {{-- 🪪 ผู้ซื้อยังไม่ยืนยันตัวตน → แถบปุ่มดาวน์โหลดแอปไปยืนยัน (EkycGate::FLASH_KEY) --}}
    <x-ekyc-required-banner />

    @stack('scripts')
</body>
</html>
