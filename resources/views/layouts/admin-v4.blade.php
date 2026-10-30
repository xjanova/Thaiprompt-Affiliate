{{--
 |----------------------------------------------------------------------------
 | Admin Layout V4 — ธีมนวลทองคำ (Nuan Gold Clay)  [ธีมเดียวของหลังบ้าน]
 |----------------------------------------------------------------------------
 | ✅ ธีมเดียว ไม่มีระบบ "เลือกธีม" — ปรับแต่งสดผ่าน Theme Studio (ปุ่ม 🎨)
 | ✅ ทุกอย่างขับด้วย CSS variables → ดู resources/css/theme-v4.css
 | ✅ เปลือกใหม่นี้แยกจาก admin-v3 (Arrow X เดิม) อย่างสมบูรณ์
 |    หน้าไหนยังไม่ย้ายมา v4 ก็ยังใช้ admin-v3 ต่อได้ (ค่อยย้ายทีละหน้า)
 |
 | การใช้งาน:  @extends('layouts.admin-v4')  →  @section('content') ... @endsection
 | ส่วนเสริม:   @section('page-title','...')  /  @push('styles')  /  @push('scripts')
 --}}
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'หลังบ้าน') · {{ config('app.brand_name', 'Thai Prompt') }}</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('images/brand/favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">

    {{-- ฟอนต์: Anuphan (ไทย/ทั่วไป) + Sora (ตัวเลข/หัวข้อ) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anuphan:wght@400;500;600;700&family=Sora:wght@500;600;700;800&display=swap" rel="stylesheet">

    {{-- Font Awesome 6.5.1 --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    {{-- Vite: Tailwind base (app.css) + ธีม V4 + Alpine (app.js) --}}
    @vite(['resources/css/app.css', 'resources/css/theme-v4.css', 'resources/js/app.js'])

    <x-theme-v4.shell-script />

    @stack('styles')
</head>
<body class="tp-root">

    {{-- พื้นหลังลาวา (3 วงเบลอ) --}}
    <div class="tp-lava" aria-hidden="true"><i class="l1"></i><i class="l2"></i><i class="l3"></i></div>

    <div style="display:flex; flex-direction:column; min-height:100vh; position:relative; z-index:1;"
         x-data="tpShell">

        {{-- แถบบน --}}
        <x-theme-v4.topbar />

        {{-- ฉากมืดตอนเปิด drawer (มือถือ) --}}
        <div x-show="drawer" x-cloak @click="closeDrawer()"
             style="position:fixed; inset:0; z-index:55; background:rgba(0,0,0,.42); -webkit-backdrop-filter:blur(2px); backdrop-filter:blur(2px);"></div>

        {{-- เนื้อหา: sidebar + main — เนื้อหาเต็มกว้าง (ไม่ cap/ไม่ลอย) + gutter ชัด กันชิดขอบ --}}
        <div style="display:flex; gap:24px; padding:8px clamp(18px,3vw,56px) 36px; flex:1; align-items:flex-start;">
            <x-theme-v4.sidebar />
            <main style="flex:1; min-width:0; max-width:1800px; margin-inline:auto;">
                @yield('content')
            </main>
        </div>
    </div>

    {{-- Toast: session flash → แจ้งเตือน --}}
    <div class="fixed bottom-4 right-4 z-[100] space-y-2"
         x-data="{ items: [] }"
         @notify.window="items.push($event.detail); setTimeout(() => items.shift(), 3500)">
        <template x-for="(n, i) in items" :key="i">
            <div x-transition.opacity
                 class="tp-card" style="padding:13px 16px; min-width:280px; display:flex; align-items:center; gap:10px;">
                <span class="tp-pill"
                      :style="n.type==='success' ? 'background:#5aa07e;color:#fff' : n.type==='error' ? 'background:#d9534f;color:#fff' : n.type==='warning' ? 'background:#e0a52e;color:#fff' : 'background:var(--accent1);color:#fff'"
                      x-text="n.type==='success' ? '✓' : n.type==='error' ? '!' : n.type==='warning' ? '⚠' : 'i'"></span>
                <span style="font-size:13.5px; color:var(--ink);" x-text="n.message"></span>
            </div>
        </template>
    </div>
    @if(session('success'))<div x-data x-init="$dispatch('notify',{type:'success',message:@js(session('success'))})"></div>@endif
    @if(session('error'))<div x-data x-init="$dispatch('notify',{type:'error',message:@js(session('error'))})"></div>@endif
    @if(session('warning'))<div x-data x-init="$dispatch('notify',{type:'warning',message:@js(session('warning'))})"></div>@endif
    @if(session('info'))<div x-data x-init="$dispatch('notify',{type:'info',message:@js(session('info'))})"></div>@endif

    {{-- 🌸 น้อง Eve — ผู้ช่วย AI (หลังบ้านแอดมิน) --}}
    <x-eve.widget surface="admin" />

    @stack('scripts')
</body>
</html>
