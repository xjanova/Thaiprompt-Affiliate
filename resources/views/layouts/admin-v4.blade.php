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

    <title>@yield('title', 'หลังบ้าน') · {{ config('app.name') }}</title>

    {{-- Favicon (ใช้จาก Theme Setting ถ้ามี — เป็นแค่ branding ไม่เกี่ยวกับ engine ธีม) --}}
    @php
        $tpFavicon = optional(\App\Models\ThemeSetting::active())->favicon_path;
        $faviconUrl = $tpFavicon ? asset('storage/' . $tpFavicon) : asset('favicon.ico');
    @endphp
    <link rel="icon" type="image/x-icon" href="{{ $faviconUrl }}">

    {{-- ฟอนต์: Anuphan (ไทย/ทั่วไป) + Sora (ตัวเลข/หัวข้อ) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anuphan:wght@400;500;600;700&family=Sora:wght@500;600;700;800&display=swap" rel="stylesheet">

    {{-- Font Awesome 6.5.1 --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    {{-- Vite: Tailwind base (app.css) + ธีม V4 + Alpine (app.js) --}}
    @vite(['resources/css/app.css', 'resources/css/theme-v4.css', 'resources/js/app.js'])

    {{-- ============================================================
         Theme Engine V4 — applyTheme() + Alpine store ('tp')
         รันก่อน paint เพื่อกัน FOUC (โหมดมืด/พาเลตต์) แล้ว Alpine มารับช่วงต่อ
         ============================================================ --}}
    <script>
    (function () {
        const bases = {
            light: { bg:'#f3eee4', surf:'#ece5d8', sd:'#cdc1ad', sl:'#fffdf7', ink:'#544c40', ink2:'#9a8f7c' },
            dark:  { bg:'#1d1912', surf:'#272118', sd:'#100d08', sl:'#352b1d', ink:'#ece3d2', ink2:'#a89c84' }
        };
        const palettes = {
            'ทองคำ':      { a1:'#e6b347', a2:'#d98e3f', d1:'#b8892a', d2:'#b06f2f', light:{a1s:'#f6eccf',a2s:'#f4e3cc'}, dark:{a1s:'#3a2f16',a2s:'#372917'} },
            'นวลชมพู':    { a1:'#e69ec0', a2:'#b79ae8', d1:'#cf6f9c', d2:'#8c6fd6', light:{a1s:'#f9e0ea',a2s:'#e9e0f8'}, dark:{a1s:'#3a2530',a2s:'#2c2640'} },
            'ลาเวนเดอร์': { a1:'#c4a6e0', a2:'#9fb0e8', d1:'#9a6fc4', d2:'#6f82d6', light:{a1s:'#ece2f7',a2s:'#e2e7fb'}, dark:{a1s:'#2f2740',a2s:'#262a44'} },
            'มินต์':      { a1:'#86cfae', a2:'#8fbfe0', d1:'#4f9e7e', d2:'#5689b8', light:{a1s:'#e0f1ea',a2s:'#e1eef7'}, dark:{a1s:'#1f3a30',a2s:'#1f3340'} },
            'มหาสมุทร':   { a1:'#6fc3d4', a2:'#7d9ee0', d1:'#4d97a6', d2:'#5f78c2', light:{a1s:'#dbeff3',a2s:'#e0e6f9'}, dark:{a1s:'#1d3940',a2s:'#212a48'} },
            'พระอาทิตย์': { a1:'#f0a86a', a2:'#ec828f', d1:'#d6824a', d2:'#cf6f7c', light:{a1s:'#fae6d6',a2s:'#fbdfe3'}, dark:{a1s:'#3d2c1c',a2s:'#3a2226'} }
        };
        const surfaceTones = { 'ครีม':null, 'พีช':70, 'ทราย':45, 'กุหลาบ':20, 'ม่วง':330, 'คราม':285, 'ฟ้า':230, 'เขียวน้ำ':195, 'เซจ':150 };

        function genAccents(h1, h2, mode) {
            return {
                a1:'oklch(0.73 0.145 '+h1+')', a2:'oklch(0.67 0.145 '+h2+')',
                d1:'oklch(0.56 0.13 '+h1+')',  d2:'oklch(0.53 0.13 '+h2+')',
                a1s: mode==='dark' ? 'oklch(0.32 0.05 '+h1+')' : 'oklch(0.93 0.045 '+h1+')',
                a2s: mode==='dark' ? 'oklch(0.31 0.05 '+h2+')' : 'oklch(0.925 0.045 '+h2+')'
            };
        }
        function genBase(h, mode) {
            if (mode==='dark') return { bg:'oklch(0.165 0.016 '+h+')', surf:'oklch(0.205 0.018 '+h+')', sd:'oklch(0.10 0.012 '+h+')', sl:'oklch(0.27 0.02 '+h+')', ink:'oklch(0.90 0.012 '+h+')', ink2:'oklch(0.66 0.014 '+h+')' };
            return { bg:'oklch(0.95 0.013 '+h+')', surf:'oklch(0.925 0.016 '+h+')', sd:'oklch(0.80 0.024 '+h+')', sl:'oklch(0.99 0.006 '+h+')', ink:'oklch(0.36 0.022 '+h+')', ink2:'oklch(0.60 0.016 '+h+')' };
        }
        function variantVars(v, mode) {
            const dk = mode === 'dark';
            if (v === 'แบนทอง Flat') {
                const bd = dk ? '1px solid rgba(255,255,255,.07)' : '1px solid rgba(83,76,64,.09)';
                const ins = dk ? 'inset 0 0 0 1px rgba(255,255,255,.06)' : 'inset 0 0 0 1px rgba(83,76,64,.08)';
                return { '--card-border':bd, '--card-radius':'16px',
                    '--card-shadow': dk?'0 1px 2px rgba(0,0,0,.45), 0 14px 30px rgba(0,0,0,.30)':'0 1px 2px rgba(83,76,64,.06), 0 14px 30px rgba(83,76,64,.07)',
                    '--card-shadow-sm': dk?'0 1px 2px rgba(0,0,0,.4)':'0 1px 2px rgba(83,76,64,.08)',
                    '--card-shadow-hover': dk?'0 10px 28px rgba(0,0,0,.5)':'0 12px 28px rgba(83,76,64,.13)',
                    '--raise': dk?'0 2px 8px rgba(0,0,0,.45)':'0 2px 8px rgba(83,76,64,.10)',
                    '--inset':ins, '--inset-sm':ins, '--bar-shadow':'none' };
            }
            if (v === 'กระจกใส Glass') {
                const bd = dk ? '1px solid rgba(255,255,255,.10)' : '1px solid rgba(255,255,255,.6)';
                const ins = dk ? 'inset 0 0 0 1px rgba(255,255,255,.08)' : 'inset 0 0 0 1px rgba(255,255,255,.45)';
                return { '--card-border':bd, '--card-radius':'20px',
                    '--card-shadow': dk?'0 12px 36px rgba(0,0,0,.42)':'0 12px 36px rgba(120,90,40,.14)',
                    '--card-shadow-sm': dk?'0 4px 16px rgba(0,0,0,.3)':'0 4px 16px rgba(120,90,40,.10)',
                    '--card-shadow-hover': dk?'0 16px 44px rgba(0,0,0,.55)':'0 16px 44px rgba(120,90,40,.2)',
                    '--raise': dk?'0 4px 16px rgba(0,0,0,.3)':'0 4px 16px rgba(120,90,40,.13)',
                    '--inset':ins, '--inset-sm':ins, '--bar-shadow':'0 6px 14px rgba(0,0,0,.12)' };
            }
            // Clay (default neumorphic)
            return {
                '--card-border':'1px solid transparent', '--card-radius':'22px',
                '--card-shadow':'7px 7px 16px var(--sd), -7px -7px 16px var(--sl)',
                '--card-shadow-sm':'4px 4px 9px var(--sd), -4px -4px 9px var(--sl)',
                '--card-shadow-hover':'9px 9px 20px var(--sd), -9px -9px 20px var(--sl)',
                '--raise':'2px 2px 5px var(--sd), -2px -2px 5px var(--sl), inset 0 1px 1.5px '+(dk?'rgba(255,255,255,.10)':'rgba(255,255,255,.55)')+', inset 0 -1px 1.5px '+(dk?'rgba(0,0,0,.25)':'rgba(120,100,70,.10)'),
                '--inset':'inset 3px 3px 7px var(--sd), inset -3px -3px 7px var(--sl)',
                '--inset-sm':'inset 2px 2px 5px var(--sd), inset -2px -2px 5px var(--sl)',
                '--bar-shadow':'4px 5px 11px var(--sd), inset -2px -3px 6px rgba(0,0,0,.12), inset 2px 2px 4px rgba(255,255,255,.4)'
            };
        }
        function accentSet(s, mode) {
            if (s.colorMode === 'custom') {
                const h2 = s.gradientOn ? s.endHue : (s.baseHue + 16);
                return genAccents(s.baseHue, h2, mode);
            }
            const p = palettes[s.palette] || palettes['ทองคำ'];
            return { a1:p.a1, a2:p.a2, d1:p.d1, d2:p.d2, a1s:p[mode].a1s, a2s:p[mode].a2s };
        }
        function apply(s) {
            const r = document.documentElement;
            const mode = s.dark ? 'dark' : 'light';
            const b = (s.surfMode === 'custom') ? genBase(s.surfHue, mode) : bases[mode];
            const A = accentSet(s, mode);
            const set = (k, v) => r.style.setProperty(k, v);
            set('--bg',b.bg); set('--surf',b.surf); set('--sd',b.sd); set('--sl',b.sl); set('--ink',b.ink); set('--ink2',b.ink2);
            set('--accent1',A.a1); set('--accent2',A.a2); set('--deep1',A.d1); set('--deep2',A.d2); set('--a1soft',A.a1s); set('--a2soft',A.a2s);
            const vv = variantVars(s.variant, mode); Object.keys(vv).forEach(k => set(k, vv[k]));
            const lv = (s.lavaLevel) / 100;
            set('--lava-op',  (lv*(mode==='dark'?0.7:0.8)).toFixed(3));
            set('--lava-op2', (lv*(mode==='dark'?0.55:0.6)).toFixed(3));
            const sf = 0.35 + (s.lavaSpeed/100)*2.2;
            set('--lava-d1',(26/sf).toFixed(1)+'s'); set('--lava-d2',(32/sf).toFixed(1)+'s'); set('--lava-d3',(38/sf).toFixed(1)+'s');
            const gt = s.glassLevel/100; const op = Math.max(15, Math.round((1 - gt*0.85)*100));
            set('--card-bg',  gt>0.02 ? 'color-mix(in srgb, var(--surf) '+op+'%, transparent)' : 'var(--surf)');
            set('--card-blur', gt>0.02 ? 'blur('+(gt*22).toFixed(1)+'px)' : 'none');
            // พื้นหลัง: accent radial + ฐานผิว
            const accentRadial = mode==='dark'
                ? 'radial-gradient(1200px 700px at 80% -5%, '+A.a2s+' 0%, transparent 55%)'
                : 'radial-gradient(1200px 700px at 82% -8%, '+A.a1s+' 0%, transparent 52%)';
            let baseLayer = b.bg;
            if (s.surfMode==='custom' && s.bgGradOn) baseLayer = 'linear-gradient(160deg, '+b.bg+' 0%, '+genBase(s.surfHue2, mode).bg+' 100%)';
            if (document.body) document.body.style.background = accentRadial + ', ' + baseLayer;
            r.classList.toggle('dark', !!s.dark); // เผื่อ utility tailwind dark:
        }

        const DEFAULTS = { dark:false, palette:'ทองคำ', colorMode:'preset', baseHue:45, endHue:28, gradientOn:true, surfMode:'preset', surfHue:70, surfHue2:30, bgGradOn:false, variant:'นวลนุ่ม Clay', lavaLevel:45, lavaSpeed:50, glassLevel:0 };
        const KEYS = Object.keys(DEFAULTS);
        function load() { try { return Object.assign({}, DEFAULTS, JSON.parse(localStorage.getItem('tp_theme_v4')) || {}); } catch (e) { return Object.assign({}, DEFAULTS); } }

        window.TPTheme = { bases, palettes, surfaceTones, apply, DEFAULTS, KEYS, load, paletteNames: Object.keys(palettes) };
        // apply ทันที (กัน FOUC) — body ยังไม่มีตอนนี้ก็ตั้ง var บน :root ไว้ก่อน
        apply(load());

        // ลงทะเบียน Alpine store + component หลัง alpine:init (ก่อน Alpine.start ใน app.js)
        document.addEventListener('alpine:init', () => {
            window.Alpine.store('tp', Object.assign({}, window.TPTheme.load(), {
                studioOpen: false,
                paletteNames: window.TPTheme.paletteNames,
                surfaceTones: window.TPTheme.surfaceTones,
                init() { this.apply(); },
                apply() { window.TPTheme.apply(this); },
                save() { const o = {}; window.TPTheme.KEYS.forEach(k => o[k] = this[k]); localStorage.setItem('tp_theme_v4', JSON.stringify(o)); },
                commit() { this.apply(); this.save(); },
                toggleDark() { this.dark = !this.dark; this.commit(); },
                cyclePalette() { this.colorMode = 'preset'; const n = this.paletteNames; this.palette = n[(n.indexOf(this.palette) + 1) % n.length]; this.commit(); },
                setPalette(p) { this.colorMode = 'preset'; this.palette = p; this.commit(); },
                setVariant(v) { this.variant = v; if (v === 'กระจกใส Glass' && this.glassLevel < 2) this.glassLevel = 55; if (v !== 'กระจกใส Glass') this.glassLevel = 0; this.commit(); },
                // สีหลักแบบกำหนดเอง (custom oklch)
                setHue(h) { this.colorMode = 'custom'; this.baseHue = h; this.commit(); },
                setEndHue(h) { this.colorMode = 'custom'; this.gradientOn = true; this.endHue = h; this.commit(); },
                toggleGradient() { this.colorMode = 'custom'; this.gradientOn = !this.gradientOn; this.commit(); },
                // สีพื้นผิวแบบกำหนดเอง + ไล่เฉดพื้นหลัง
                setSurfHue(h) { this.surfMode = 'custom'; this.surfHue = h; this.commit(); },
                setSurfEnd(h) { this.surfMode = 'custom'; this.bgGradOn = true; this.surfHue2 = h; this.commit(); },
                toggleBgGrad() { this.surfMode = 'custom'; this.bgGradOn = !this.bgGradOn; this.commit(); },
                setSurfDefault() { this.surfMode = 'preset'; this.commit(); },
                reset() { Object.assign(this, window.TPTheme.DEFAULTS); this.commit(); }
            }));

            window.Alpine.data('tpShell', () => ({
                vw: window.innerWidth,
                drawer: false,
                sidebarHidden: false,
                get isMobile() { return this.vw <= 860; },
                init() {
                    window.addEventListener('resize', () => { this.vw = window.innerWidth; if (!this.isMobile) this.drawer = false; });
                },
                toggleSidebar() { if (this.isMobile) this.drawer = !this.drawer; else this.sidebarHidden = !this.sidebarHidden; },
                closeDrawer() { this.drawer = false; }
            }));
        });
    })();
    </script>

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

        {{-- เนื้อหา: sidebar + main (gutter แนวนอนกว้าง + จำกัดความกว้างเนื้อหา กันยืดเต็มจอ/ชิดขอบ) --}}
        <div style="display:flex; gap:24px; padding:8px clamp(18px,4vw,72px) 36px; flex:1; align-items:flex-start;">
            <x-theme-v4.sidebar />
            <main style="flex:1; min-width:0; max-width:1440px; margin-inline:auto;">
                @yield('content')
            </main>
        </div>
    </div>

    {{-- Theme Studio (ปุ่ม 🎨 มุมขวาล่าง) --}}
    <x-theme-v4.theme-studio />

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

    @stack('scripts')
</body>
</html>
