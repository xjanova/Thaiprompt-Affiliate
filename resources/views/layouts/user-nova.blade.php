{{--
 |----------------------------------------------------------------------------
 | User Layout — ธีม "โนวา" (Nova) ฝั่งสมาชิก  [ชุดเดียวกับหน้าแรก]
 |----------------------------------------------------------------------------
 | กรอบกรมท่า + ทองฟอยล์ · พื้นงาช้าง/กรมท่าตามโหมด · สไตล์อยู่ที่ public/theme-nova/nova-member.css (คลาส nvm-*)
 | เมนูข้าง/ค้นหาเมนู มาจาก config/menus.php['user'] ผ่าน MenuService (แหล่งเดียวกับ user-v4)
 | โหมดมืดใช้ค่าเดียวกับธีม V4 (localStorage tp_theme_v4.dark + class "dark" บน <html>)
 |   → สลับไปมาระหว่างหน้านี้กับหน้าที่ยังเป็น user-v4 แล้วโหมดไม่เพี้ยน
 |
 | การใช้งาน:  @extends('layouts.user-nova')  →  @section('content') ... @endsection
 | ส่วนเสริม:   @section('title','...')  /  @push('styles')  /  @push('scripts')
 |
 | โหมดสะพาน V4 ($nvmV4Bridge — ส่งมาจาก layouts.user-v4):
 |   หน้าที่ยังเขียนด้วยคลาส tp-* ได้เปลือกนี้ + theme-v4.css + nova-v4-bridge.css (ทับตัวแปรสีเป็นโนวา)
 --}}
<!DOCTYPE html>
<html lang="th"@if(! empty($nvmV4Bridge)) class="nvm-v4"@endif>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        // ไอคอน/ชื่อแอปจาก Admin Settings (PWA + favicon) — ชุดเดียวกับ user-v4
        $tpSite = \App\Models\SiteSetting::getSetting();
        $tpAppName = $tpSite->app_name ?: ($tpSite->site_name ?: config('app.name'));
        $tpAppIcon = $tpSite->app_icon_url;
        $tpTheme = \App\Models\ThemeSetting::active();
        $tpFavicon = $tpTheme && $tpTheme->favicon_path
            ? asset('storage/'.$tpTheme->favicon_path)
            : ($tpSite->favicon_url ?: asset('images/brand/favicon.ico'));

        // เมนูสมาชิกดึงครั้งเดียว แล้วส่งให้ทั้งเมนูข้างและช่องค้นหาในแถบบน
        $nvmMenus = app(\App\Services\MenuService::class)->getMenuForRole('user', auth()->user());
        $nvmCssVer = is_file(public_path('theme-nova/nova-member.css')) ? filemtime(public_path('theme-nova/nova-member.css')) : 1;
        $nvmV4Bridge = ! empty($nvmV4Bridge);
        $nvmBridgeVer = $nvmV4Bridge && is_file(public_path('theme-nova/nova-v4-bridge.css')) ? filemtime(public_path('theme-nova/nova-v4-bridge.css')) : 1;
    @endphp

    <title>@yield('title', 'หน้าหลัก') · {{ config('app.brand_name', 'Thai Prompt') }}</title>

    {{-- PWA / มือถือ --}}
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ $tpAppName }}">
    <meta name="theme-color" content="#070d20">
    @if(\Illuminate\Support\Facades\Route::has('manifest.json'))
        <link rel="manifest" href="{{ route('manifest.json') }}">
    @endif
    <link rel="icon" href="{{ $tpFavicon }}">
    <link rel="shortcut icon" href="{{ $tpFavicon }}">
    @if($tpAppIcon)
        <link rel="apple-touch-icon" href="{{ $tpAppIcon }}">
        <link rel="apple-touch-icon" sizes="512x512" href="{{ $tpAppIcon }}">
    @endif

    {{-- ฟอนต์ชุดโนวา: Anuphan (ใช้งาน) · Trirong (หัวเรื่อง/ตัวเลขใหญ่) · Cinzel (ป้ายละติน) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anuphan:wght@400;500;600;700&family=Cinzel:wght@500;600;700&family=Trirong:wght@500;600;700&display=swap" rel="stylesheet">

    {{-- Font Awesome — ไอคอนเมนูใน config บางรายการเป็นคลาส fa- --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    {{-- Vite: Tailwind base + Alpine (มี $store.pinnedMenus) — theme-v4.css โหลดเฉพาะโหมดสะพาน (หน้าที่ยังใช้คลาส tp-*) --}}
    @if($nvmV4Bridge)
        @vite(['resources/css/app.css', 'resources/css/theme-v4.css', 'resources/js/app.js'])
    @else
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <link rel="stylesheet" href="{{ asset('theme-nova/nova-member.css') }}?v={{ $nvmCssVer }}">
    @if($nvmV4Bridge)
        <link rel="stylesheet" href="{{ asset('theme-nova/nova-v4-bridge.css') }}?v={{ $nvmBridgeVer }}">
    @endif

    {{-- โหมดมืด: ตั้งก่อน paint (กันจอกระพริบ) แล้วให้ Alpine รับช่วง
         ⚠️ app.js มี store 'theme' (V3) ที่ลบ class dark ตอนโหลด ถ้า localStorage.darkMode ไม่ใช่ 'true'
            store nvmTheme ด้านล่างตั้งกลับตอน alpine:init — แต่ถ้าเบราว์เซอร์คำนวณสไตล์คั่นกลาง
            body (transition 0.4s) จะเฟดจากสว่างกลับมืดให้เห็นทุกครั้งที่โหลดหน้า
            → ปิด transition ทั้งหน้า (class nvm-preload) จนโหมดถูกตั้งเสร็จแล้ว 2 เฟรม
            (ไม่เขียนทับ darkMode — คีย์นั้นหน้าตลาดสด/หน้า guest ใช้คุมโหมดของตัวเองอยู่) --}}
    <script>
    (function () {
        var KEY = 'tp_theme_v4';
        var root = document.documentElement;
        function read() { try { return JSON.parse(localStorage.getItem(KEY)) || {}; } catch (e) { return {}; } }
        function apply(dark) { root.classList.toggle('dark', !!dark); }
        function settle() { requestAnimationFrame(function () { requestAnimationFrame(function () { root.classList.remove('nvm-preload'); }); }); }
        apply(read().dark);
        root.classList.add('nvm-js', 'nvm-preload');
        // สำรอง: Alpine โหลดไม่ขึ้นก็ต้องคืน transition ให้หน้า
        window.addEventListener('load', settle);

        document.addEventListener('alpine:init', function () {
            window.Alpine.store('nvmTheme', {
                dark: !!read().dark,
                init() { apply(this.dark); settle(); },
                toggle() {
                    this.dark = !this.dark;
                    // เขียนทับเฉพาะ dark — ค่าอื่นของ Theme Studio V4 (พาเลตต์ ฯลฯ) ต้องอยู่ครบ
                    var s = read();
                    s.dark = this.dark;
                    try { localStorage.setItem(KEY, JSON.stringify(s)); } catch (e) {}
                    apply(this.dark);
                }
            });

            window.Alpine.data('nvmShell', function () {
                return {
                    menuOpen: false,
                    openMenu() { this.menuOpen = true; root.style.overflow = 'hidden'; },
                    closeMenu() { this.menuOpen = false; root.style.overflow = ''; },
                    init() {
                        var self = this;
                        window.addEventListener('resize', function () { if (window.innerWidth >= 1100 && self.menuOpen) self.closeMenu(); });
                    }
                };
            });
        });
    })();
    </script>

    @stack('styles')
</head>
<body class="nvm-body">
    <x-nova.member-icons />

    <div x-data="nvmShell" @keydown.escape.window="closeMenu()">
        <x-nova.member-topbar :menus="$nvmMenus" />

        <div class="nvm-shell">
            <x-nova.member-sidebar :menus="$nvmMenus" />
            <div class="nvm-scrim" x-show="menuOpen" x-cloak @click="closeMenu()" x-transition.opacity></div>

            <main class="nvm-main">
                <div class="nvm-main__in">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    <x-nova.member-tabbar />

    {{-- แจ้งเตือนลอย: รับอีเวนต์ notify ชุดเดียวกับ user-v4 (window.showNotification / $dispatch('notify')) --}}
    <div class="nvm-toasts" role="status" aria-live="polite"
         x-data="{ items: [], push(d) { const n = Object.assign({ id: Date.now() + Math.random(), type: 'info' }, d); this.items.push(n); setTimeout(() => { this.items = this.items.filter(x => x.id !== n.id); }, 3500); } }"
         @notify.window="push($event.detail)">
        <template x-for="n in items" :key="n.id">
            <div class="nvm-toast" :class="'nvm-toast--' + n.type" x-transition.opacity>
                <svg class="nvm-ico"><use :href="n.type === 'error' ? '#nvm-i-x' : (n.type === 'success' ? '#nvm-i-check' : '#nvm-i-info')"/></svg>
                <span x-text="n.message"></span>
            </div>
        </template>
    </div>
    @foreach(['success', 'error', 'warning', 'info'] as $nvmFlash)
        @if(session($nvmFlash))<div x-data x-init="$dispatch('notify', { type: @js($nvmFlash), message: @js(session($nvmFlash)) })"></div>@endif
    @endforeach
    {{-- 🪪 ผู้ซื้อยังไม่ยืนยันตัวตน → แถบปุ่มดาวน์โหลดแอปไปยืนยัน (EkycGate::FLASH_KEY) --}}
    <x-ekyc-required-banner />

    <script>
        window.showNotification = function (message, type) {
            window.dispatchEvent(new CustomEvent('notify', { detail: { message: message, type: type || 'info' } }));
        };
        // คัดลอกข้อความ + แจ้งผล — หน้า http/เบราว์เซอร์เก่าไม่มี navigator.clipboard จึงมีทางสำรอง execCommand
        window.nvmCopy = function (text, okMessage) {
            var done = function () { window.showNotification(okMessage || 'คัดลอกแล้ว', 'success'); };
            var fail = function () { window.showNotification('คัดลอกไม่สำเร็จ', 'error'); };
            if (navigator.clipboard && window.isSecureContext) { navigator.clipboard.writeText(text).then(done, fail); return; }
            try {
                var t = document.createElement('textarea');
                t.value = text; t.setAttribute('readonly', ''); t.className = 'nvm-sr';
                document.body.appendChild(t); t.select();
                document.execCommand('copy') ? done() : fail();
                t.remove();
            } catch (e) { fail(); }
        };
        // เผยการ์ดตอนเลื่อนถึง — เบราว์เซอร์ไม่มี IntersectionObserver ก็โชว์ทั้งหมดทันที
        (function () {
            var els = document.querySelectorAll('.nvm-rv');
            if (!('IntersectionObserver' in window)) { els.forEach(function (el) { el.classList.add('is-in'); }); return; }
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('is-in'); io.unobserve(e.target); } });
            }, { threshold: 0.08 });
            els.forEach(function (el) { io.observe(el); });
        })();
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('/service-worker.js', { scope: '/' }).catch(function () {});
            });
        }
    </script>

    {{-- 🤖 น้อง Eve ผู้ช่วย AI — ต้องวางก่อน @stack('scripts') เสมอ
         (วิดเจ็ตส่งสคริปต์ตัวเองผ่าน @push('scripts') ถ้าวางทีหลัง stack จะไม่ถูก render = ปุ่มขึ้นแต่กดไม่ทำงาน)
         mobileOffset=92 ยกพ้นแถบล่างมือถือ (nvm-tabbar สูง ~66px + ซุ้มกนก) --}}
    <x-eve.widget surface="user" :mobile-offset="92" />

    @stack('scripts')
</body>
</html>
