{{--
  * หน้าแรก (Landing) ธีม V4 "นวลทองคำ" — อิงดีไซน์จากไฟล์ต้นแบบ .theme-new
  * เพิ่ม: ส่วนสินค้าแนะนำ (ดึงจาก DB จริง) + ลิงก์ไปหน้าร้านอีคอมเมิร์ซ /storefront
  * ข้อมูลจาก HomeController@index: $products (Collection), $stats (array)
--}}
@extends('layouts.frontend-v4')

@section('title', 'ไทยพร๊อมท์ · แพลตฟอร์มเพื่อชีวิตที่ดีกว่า')

@php
    $shopUrl = \Illuminate\Support\Facades\Route::has('storefront.index') ? route('storefront.index') : url('/storefront');
    $loginUrl = \Illuminate\Support\Facades\Route::has('login') ? route('login') : url('/login');
    $registerUrl = \Illuminate\Support\Facades\Route::has('register') ? route('register') : url('/register');

    // สถานะล็อกอิน — หน้าแรกต้องรับรู้ว่าผู้ใช้ล็อกอินอยู่ (ไม่โชว์ปุ่มเข้าสู่ระบบ/สมัคร)
    $authUser = auth()->user();
    $isAdmin = $authUser && ((($authUser->role ?? null) === 'admin') || ($authUser->is_super_admin ?? false));
    if ($isAdmin && \Illuminate\Support\Facades\Route::has('admin.dashboard')) {
        $dashUrl = route('admin.dashboard');
    } elseif (\Illuminate\Support\Facades\Route::has('user.dashboard')) {
        $dashUrl = route('user.dashboard');
    } else {
        $dashUrl = url('/');
    }
    $logoutUrl = \Illuminate\Support\Facades\Route::has('logout') ? route('logout') : url('/logout');
    $primaryCtaUrl = $authUser ? $dashUrl : $registerUrl;
    $primaryCtaLabel = $authUser ? 'ไปที่แดชบอร์ด' : 'เริ่มต้นใช้งานฟรี';

    $services = [
        ['emoji' => '🛒', 'tag' => 'E-COMMERCE', 'th' => 'อีคอมเมิร์ซ & ตลาดสด', 'desc' => 'ช้อปสินค้าหลากหลายหมวด ส่งไว ปลอดภัย พร้อมระบบรีวิวจริง', 'url' => $shopUrl],
        ['emoji' => '🏍️', 'tag' => 'DELIVERY', 'th' => 'ไรเดอร์ & เดลิเวอรี่', 'desc' => 'ส่งอาหารและพัสดุทั่วเมือง ค่าส่งเป็นธรรม ติดตามเรียลไทม์', 'url' => '#services'],
        ['emoji' => '💰', 'tag' => 'WALLET', 'th' => 'กระเป๋าเงินดิจิทัล', 'desc' => 'จัดการเงิน เหรียญ TPX และปันผล จากแอปเดียว โปร่งใสบนเชน', 'url' => '#wallet'],
        ['emoji' => '📈', 'tag' => 'AFFILIATE', 'th' => 'ปันผล & พันธมิตร', 'desc' => 'สร้างรายได้จากเครือข่าย ระบบคอมมิชชั่นโปร่งใส ตรวจสอบได้', 'url' => '#services'],
        ['emoji' => '🤖', 'tag' => 'AI BOT', 'th' => 'ตลาด AI Bot', 'desc' => 'เช่า/ขายบอท AI ช่วยงานขายและบริการลูกค้าอัตโนมัติ 24 ชม.', 'url' => \Illuminate\Support\Facades\Route::has('marketplace.index') ? route('marketplace.index') : '#'],
        ['emoji' => '⛓️', 'tag' => 'BLOCKCHAIN', 'th' => 'Blockchain ของเราเอง', 'desc' => 'ทุกธุรกรรมบันทึกบนเชน TPIX ตรวจสอบย้อนหลังได้ทุกขั้นตอน', 'url' => '#wallet'],
    ];

    $trust = [
        ['value' => number_format($stats['products'] ?? 0).'+', 'th' => 'สินค้าพร้อมขาย', 'en' => 'Products live'],
        ['value' => number_format($stats['categories'] ?? 0), 'th' => 'หมวดหมู่สินค้า', 'en' => 'Categories'],
        ['value' => number_format($stats['members'] ?? 0).'+', 'th' => 'สมาชิกแพลตฟอร์ม', 'en' => 'Members'],
        ['value' => '99.9%', 'th' => 'ความเสถียรระบบ', 'en' => 'Uptime'],
    ];
@endphp

@section('content')
<div x-data="{ mobileMenu: false }">

    {{-- ════════ NAV ════════ --}}
    <header style="position:sticky; top:0; z-index:30; display:flex; align-items:center; flex-wrap:wrap; gap:12px 18px; padding:15px clamp(16px,3vw,40px); background:var(--card-bg); -webkit-backdrop-filter:blur(10px); backdrop-filter:blur(10px); box-shadow:var(--card-shadow-sm); border-bottom:var(--card-border);">
        <a href="{{ url('/') }}" style="display:flex; align-items:center; text-decoration:none;" title="ไทยพร๊อมท์">
            <x-theme-v4.brand-logo :height="44" />
        </a>
        <nav style="display:flex; gap:4px; margin-left:14px; flex-wrap:wrap;">
            <a href="#services" style="text-decoration:none; padding:9px 14px; border-radius:11px; font-size:13px; font-weight:600; color:var(--ink2);">บริการ</a>
            <a href="#products" style="text-decoration:none; padding:9px 14px; border-radius:11px; font-size:13px; font-weight:600; color:var(--ink2);">สินค้า</a>
            <a href="{{ $shopUrl }}" style="text-decoration:none; padding:9px 14px; border-radius:11px; font-size:13px; font-weight:600; color:var(--ink2);">ร้านค้า</a>
            <a href="#wallet" style="text-decoration:none; padding:9px 14px; border-radius:11px; font-size:13px; font-weight:600; color:var(--ink2);">Wallet</a>
        </nav>
        <div style="display:flex; align-items:center; gap:9px; margin-left:auto;">
            <button @click="$store.tp.toggleDark()" title="สลับโหมดสว่าง/มืด" type="button" style="cursor:pointer; border:0; width:40px; height:40px; border-radius:12px; background:var(--card-bg); box-shadow:var(--raise); display:grid; place-items:center; color:var(--deep2);">
                <i class="fas" :class="$store.tp && $store.tp.dark ? 'fa-sun' : 'fa-moon'"></i>
            </button>
            @auth
                <span class="tp-muted" style="font-size:12px; font-weight:600; max-width:140px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">สวัสดี, {{ \Illuminate\Support\Str::limit($authUser->name ?? 'สมาชิก', 16) }}</span>
                <a href="{{ $dashUrl }}" style="text-decoration:none; display:inline-flex; align-items:center; gap:6px; padding:0 16px; height:40px; border-radius:12px; font-weight:700; font-size:12.5px; color:#fff; background:linear-gradient(135deg,var(--accent1),var(--accent2)); box-shadow:var(--raise); text-shadow:0 1px 2px rgba(0,0,0,.14);">
                    <i class="fas fa-gauge-high"></i> แดชบอร์ด
                </a>
                <form method="POST" action="{{ $logoutUrl }}" style="margin:0;">
                    @csrf
                    <button type="submit" title="ออกจากระบบ" style="cursor:pointer; border:0; display:grid; place-items:center; width:40px; height:40px; border-radius:12px; font-weight:600; color:var(--ink); background:var(--card-bg); box-shadow:var(--raise);">
                        <i class="fas fa-arrow-right-from-bracket"></i>
                    </button>
                </form>
            @else
                <a href="{{ $loginUrl }}" style="text-decoration:none; display:grid; place-items:center; padding:0 15px; height:40px; border-radius:12px; font-weight:600; font-size:12.5px; color:var(--ink); background:var(--card-bg); box-shadow:var(--raise);">เข้าสู่ระบบ</a>
                <a href="{{ $registerUrl }}" style="text-decoration:none; display:grid; place-items:center; padding:0 18px; height:40px; border-radius:12px; font-weight:700; font-size:12.5px; color:#fff; background:linear-gradient(135deg,var(--accent1),var(--accent2)); box-shadow:var(--raise); text-shadow:0 1px 2px rgba(0,0,0,.14);">สมัครสมาชิก</a>
            @endauth
        </div>
    </header>

    {{-- ════════ HERO ════════ --}}
    <section style="max-width:1180px; margin:0 auto; padding:54px clamp(16px,3vw,40px) 30px; display:grid; grid-template-columns:repeat(auto-fit,minmax(320px,1fr)); gap:44px; align-items:center;">
        <div>
            <div style="display:inline-flex; align-items:center; gap:9px; padding:7px 14px; border-radius:20px; background:var(--a1soft); color:var(--deep1); font-size:12px; font-weight:700; margin-bottom:18px;"><span style="width:8px; height:8px; border-radius:50%; background:#4f9e7e; box-shadow:0 0 0 4px rgba(79,158,126,.18);"></span>แพลตฟอร์มคนไทย • เพื่อคนไทย • เพื่อเอเชีย</div>
            <h1 style="margin:0; font-size:clamp(32px,5.5vw,46px); line-height:1.12; font-weight:700; letter-spacing:-1px; color:var(--ink);"><span style="background:linear-gradient(135deg,var(--accent1),var(--deep1)); -webkit-background-clip:text; background-clip:text; -webkit-text-fill-color:transparent;">ไทยพร๊อมท์</span> แพลตฟอร์ม<br/>เพื่อชีวิตที่ดีกว่า</h1>
            <div style="font-family:'Sora','Anuphan'; font-size:14px; font-weight:600; color:var(--deep2); margin-top:8px; letter-spacing:.3px;">ThaiPrompt · one platform for a better life</div>
            <p style="font-size:15px; color:var(--ink2); line-height:1.7; margin:18px 0 22px; max-width:470px;">รวมทุกโซลูชันในที่เดียว — อีคอมเมิร์ซ &amp; ตลาดสด, ไรเดอร์, จัดการด้วย AI, กระเป๋าเงินดิจิทัล และระบบปันผลโปร่งใสด้วย Blockchain ของเราเอง</p>
            <div style="display:flex; gap:12px; flex-wrap:wrap;">
                <a href="{{ $shopUrl }}" style="text-decoration:none; display:inline-grid; place-items:center; padding:14px 26px; border-radius:14px; font-weight:700; font-size:14px; color:#fff; background:linear-gradient(135deg,var(--accent1),var(--accent2)); box-shadow:var(--raise); text-shadow:0 1px 2px rgba(0,0,0,.14);">🛒 ช้อปสินค้าเลย</a>
                <a href="#products" style="text-decoration:none; display:inline-grid; place-items:center; padding:14px 24px; border-radius:14px; font-weight:600; font-size:14px; color:var(--ink); background:var(--card-bg); box-shadow:var(--raise);">ดูสินค้าแนะนำ</a>
            </div>
            <div style="display:flex; gap:26px; margin-top:34px; flex-wrap:wrap;">
                <div><div style="font-family:'Sora','Anuphan'; font-weight:800; font-size:24px; letter-spacing:-.5px; background:linear-gradient(135deg,var(--deep1),var(--deep2)); -webkit-background-clip:text; background-clip:text; -webkit-text-fill-color:transparent;">{{ number_format($stats['products'] ?? 0) }}+</div><div style="font-size:11.5px; color:var(--ink2); font-weight:600; margin-top:2px;">สินค้าพร้อมขาย</div></div>
                <div><div style="font-family:'Sora','Anuphan'; font-weight:800; font-size:24px; letter-spacing:-.5px; background:linear-gradient(135deg,var(--deep1),var(--deep2)); -webkit-background-clip:text; background-clip:text; -webkit-text-fill-color:transparent;">{{ number_format($stats['categories'] ?? 0) }}</div><div style="font-size:11.5px; color:var(--ink2); font-weight:600; margin-top:2px;">หมวดหมู่</div></div>
                <div><div style="font-family:'Sora','Anuphan'; font-weight:800; font-size:24px; letter-spacing:-.5px; background:linear-gradient(135deg,var(--deep1),var(--deep2)); -webkit-background-clip:text; background-clip:text; -webkit-text-fill-color:transparent;">24/7</div><div style="font-size:11.5px; color:var(--ink2); font-weight:600; margin-top:2px;">บริการ</div></div>
            </div>
        </div>

        {{-- HERO CARD --}}
        <div style="padding:24px; border-radius:28px; background:var(--card-bg); box-shadow:var(--card-shadow); border:var(--card-border); -webkit-backdrop-filter:var(--card-blur); backdrop-filter:var(--card-blur);">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;"><div style="font-size:12px; color:var(--ink2); font-weight:700;">ภาพรวมแพลตฟอร์ม · LIVE</div><span style="display:inline-flex; align-items:center; gap:6px; font-size:10.5px; font-weight:700; color:#4f9e7e; background:rgba(79,158,126,.14); padding:4px 10px; border-radius:20px;"><span style="width:6px; height:6px; border-radius:50%; background:#4f9e7e;"></span>เรียลไทม์</span></div>
            <div style="padding:20px 22px; border-radius:20px; background:linear-gradient(135deg,var(--accent1),var(--accent2)); box-shadow:0 8px 22px rgba(0,0,0,.12); color:#fff;">
                <div style="font-size:12px; font-weight:600; opacity:.92;">สินค้าพร้อมขายในระบบ</div>
                <div style="font-family:'Sora','Anuphan'; font-weight:800; font-size:34px; letter-spacing:-1px; margin:3px 0; text-shadow:0 1px 3px rgba(0,0,0,.16);">{{ number_format($stats['products'] ?? 0) }} <span style="font-size:18px; opacity:.85;">รายการ</span></div>
                <div style="display:flex; align-items:flex-end; gap:4px; height:34px; margin-top:8px;">
                    @foreach([55,72,48,88,64,95,70,82,60] as $h)
                        <span style="flex:1; height:{{ $h }}%; border-radius:4px 4px 1px 1px; background:rgba(255,255,255,.75);"></span>
                    @endforeach
                </div>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:11px; margin-top:14px;">
                <div style="padding:14px; border-radius:15px; background:var(--surf); box-shadow:var(--inset-sm);"><div style="font-size:10.5px; color:var(--ink2); font-weight:600;">หมวดหมู่สินค้า</div><div style="font-family:'Sora','Anuphan'; font-weight:700; font-size:19px; margin-top:2px; color:var(--ink);">{{ number_format($stats['categories'] ?? 0) }}</div></div>
                <div style="padding:14px; border-radius:15px; background:var(--surf); box-shadow:var(--inset-sm);"><div style="font-size:10.5px; color:var(--ink2); font-weight:600;">สมาชิก</div><div style="font-family:'Sora','Anuphan'; font-weight:700; font-size:19px; margin-top:2px; color:var(--ink);">{{ number_format($stats['members'] ?? 0) }}+</div></div>
            </div>
            <a href="{{ $shopUrl }}" style="text-decoration:none; display:flex; align-items:center; justify-content:center; gap:8px; margin-top:14px; padding:12px 14px; border-radius:15px; background:var(--surf); box-shadow:var(--inset-sm); font-size:12.5px; color:var(--deep1); font-weight:700;">เข้าสู่ร้านค้า <span style="font-family:'Sora','Anuphan';">→</span></a>
        </div>
    </section>

    {{-- ════════ TRUST STRIP ════════ --}}
    <section style="max-width:1180px; margin:14px auto 0; padding:0 clamp(16px,3vw,40px);">
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:16px; padding:22px; border-radius:22px; background:var(--card-bg); box-shadow:var(--card-shadow); border:var(--card-border);">
            @foreach($trust as $t)
                <div style="text-align:center;"><div style="font-family:'Sora','Anuphan'; font-weight:800; font-size:26px; letter-spacing:-.5px; color:var(--ink);">{{ $t['value'] }}</div><div style="font-size:12px; color:var(--ink2); font-weight:600; margin-top:3px;">{{ $t['th'] }}</div><div style="font-size:10px; color:var(--ink2); font-weight:500; opacity:.75;">{{ $t['en'] }}</div></div>
            @endforeach
        </div>
    </section>

    {{-- ════════ PRODUCTS (สินค้าแนะนำจาก DB จริง) ════════ --}}
    <section id="products" style="max-width:1180px; margin:0 auto; padding:54px clamp(16px,3vw,40px) 20px;">
        <div style="display:flex; align-items:flex-end; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:24px;">
            <div>
                <div style="font-size:12.5px; color:var(--deep2); font-weight:700; letter-spacing:.4px;">สินค้าแนะนำ · FEATURED</div>
                <h2 style="margin:6px 0 4px; font-size:clamp(23px,4vw,30px); font-weight:700; letter-spacing:-.4px; color:var(--ink);">ช้อปสินค้าคุณภาพ ราคาดี</h2>
                <p style="font-size:14px; color:var(--ink2); margin:0; line-height:1.6;">คัดสรรสินค้าไอที เครื่องใช้ไฟฟ้า และอีกมากมาย จัดส่งทั่วไทย</p>
            </div>
            <a href="{{ $shopUrl }}" style="text-decoration:none; flex:none; display:inline-flex; align-items:center; gap:8px; padding:12px 20px; border-radius:13px; font-weight:700; font-size:13.5px; color:#fff; background:linear-gradient(135deg,var(--accent1),var(--accent2)); box-shadow:var(--raise); text-shadow:0 1px 2px rgba(0,0,0,.14);">ดูสินค้าทั้งหมด <span style="font-family:'Sora','Anuphan';">→</span></a>
        </div>

        @if($products->isEmpty())
            <div style="padding:48px; border-radius:22px; background:var(--card-bg); box-shadow:var(--card-shadow); text-align:center; color:var(--ink2);">
                <div style="font-size:40px; margin-bottom:10px;">🛍️</div>
                ยังไม่มีสินค้าแนะนำ — <a href="{{ $shopUrl }}" style="color:var(--deep1); font-weight:700;">ไปที่หน้าร้าน</a>
            </div>
        @else
            <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(210px,1fr)); gap:16px;">
                @foreach($products as $product)
                    <a href="{{ $shopUrl }}" style="text-decoration:none; display:flex; flex-direction:column; border-radius:20px; overflow:hidden; background:var(--card-bg); box-shadow:var(--card-shadow); border:var(--card-border);">
                        <div style="aspect-ratio:1/1; background:var(--surf); box-shadow:var(--inset-sm); display:flex; align-items:center; justify-content:center; overflow:hidden;">
                            @if($product->primary_image)
                                <img src="{{ $product->primary_image }}" alt="{{ $product->name }}" loading="lazy" style="width:100%; height:100%; object-fit:contain;" onerror="this.style.opacity=0">
                            @else
                                <span style="font-size:34px; opacity:.4;">📦</span>
                            @endif
                        </div>
                        <div style="padding:13px; display:flex; flex-direction:column; gap:7px; flex:1;">
                            @if($product->brand)<div style="font-size:11px; color:var(--ink2); font-weight:600;">{{ $product->brand }}</div>@endif
                            <div style="font-size:13.5px; font-weight:600; color:var(--ink); line-height:1.4; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; min-height:2.6em;">{{ $product->name }}</div>
                            <div style="margin-top:auto; display:flex; align-items:baseline; gap:7px;">
                                <span style="font-family:'Sora','Anuphan'; font-size:18px; font-weight:800; color:var(--deep1);">฿{{ number_format($product->price, 0) }}</span>
                                @if($product->compare_at_price && $product->compare_at_price > $product->price)
                                    <span style="font-size:11.5px; color:var(--ink2); text-decoration:line-through;">฿{{ number_format($product->compare_at_price, 0) }}</span>
                                @endif
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </section>

    {{-- ════════ SERVICES ════════ --}}
    <section id="services" style="max-width:1180px; margin:0 auto; padding:40px clamp(16px,3vw,40px) 20px;">
        <div style="text-align:center; margin-bottom:30px;">
            <div style="font-size:12.5px; color:var(--deep2); font-weight:700; letter-spacing:.4px;">หกเสาหลักของเรา · SIX PILLARS</div>
            <h2 style="margin:6px 0 8px; font-size:clamp(23px,4vw,30px); font-weight:700; letter-spacing:-.4px; color:var(--ink);">แพลตฟอร์มเดียว รวมทุกโซลูชัน</h2>
            <p style="font-size:14px; color:var(--ink2); max-width:540px; margin:0 auto; line-height:1.6;">เชื่อมร้านค้า ผู้บริโภค ไรเดอร์ และเครือข่ายปันผล เข้าด้วยกันบน Blockchain ของเราเอง</p>
        </div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:16px;">
            @foreach($services as $x)
                <a href="{{ $x['url'] }}" style="text-decoration:none; display:block; padding:24px; border-radius:22px; background:var(--card-bg); box-shadow:var(--card-shadow); border:var(--card-border);">
                    <div style="display:flex; align-items:center; gap:13px; margin-bottom:13px;">
                        <span style="width:48px; height:48px; flex:none; border-radius:15px; background:linear-gradient(135deg,var(--accent1),var(--accent2)); box-shadow:var(--raise); display:grid; place-items:center; font-size:24px;">{{ $x['emoji'] }}</span>
                        <span style="font-size:11px; font-weight:700; color:var(--deep2); letter-spacing:.3px;">{{ $x['tag'] }}</span>
                    </div>
                    <div style="font-weight:700; font-size:17px; margin-bottom:6px; color:var(--ink);">{{ $x['th'] }}</div>
                    <p style="font-size:13px; color:var(--ink2); line-height:1.6; margin:0 0 14px;">{{ $x['desc'] }}</p>
                    <div style="display:flex; align-items:center; gap:7px; font-size:12.5px; font-weight:700; color:var(--deep1);">เรียนรู้เพิ่มเติม <span style="font-family:'Sora','Anuphan';">→</span></div>
                </a>
            @endforeach
        </div>
    </section>

    {{-- ════════ WALLET ════════ --}}
    <section id="wallet" style="max-width:1180px; margin:0 auto; padding:46px clamp(16px,3vw,40px) 30px;">
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(300px,1fr)); gap:30px; align-items:center; padding:34px; border-radius:28px; background:var(--card-bg); box-shadow:var(--card-shadow); border:var(--card-border);">
            <div>
                <div style="font-size:12.5px; color:var(--deep2); font-weight:700; letter-spacing:.4px;">ThaiPrompt Wallet</div>
                <h2 style="margin:8px 0 12px; font-size:28px; font-weight:700; letter-spacing:-.4px; color:var(--ink);">กระเป๋าเงินดิจิทัล <span style="color:var(--deep1);">ปลอดภัย โปร่งใส</span></h2>
                <p style="font-size:14px; color:var(--ink2); line-height:1.7; margin:0 0 22px;">จัดการเงิน ปันผล และเหรียญ TPX ได้จากแอปเดียว ทุกธุรกรรมบันทึกบน Blockchain ของเรา ตรวจสอบได้ทุกขั้นตอน</p>
                <div style="display:flex; gap:12px; flex-wrap:wrap;">
                    <a href="{{ $primaryCtaUrl }}" style="text-decoration:none; display:inline-grid; place-items:center; padding:13px 24px; border-radius:13px; font-weight:700; font-size:13.5px; color:#fff; background:linear-gradient(135deg,var(--accent1),var(--accent2)); box-shadow:var(--raise); text-shadow:0 1px 2px rgba(0,0,0,.14);">{{ $authUser ? 'ไปที่กระเป๋าเงิน' : 'เปิดกระเป๋า TPX' }}</a>
                    <a href="{{ $shopUrl }}" style="text-decoration:none; display:inline-grid; place-items:center; padding:13px 22px; border-radius:13px; font-weight:600; font-size:13.5px; color:var(--ink); background:var(--surf); box-shadow:var(--raise);">เริ่มช้อป</a>
                </div>
            </div>
            <div style="position:relative; padding:24px; border-radius:24px; background:linear-gradient(150deg,var(--accent1),var(--accent2)); box-shadow:0 16px 40px rgba(0,0,0,.18); color:#fff; overflow:hidden;">
                <div style="position:absolute; top:-20px; right:-20px; width:96px; height:96px; border-radius:50%; background:rgba(255,255,255,.12);"></div>
                <div style="position:relative; display:flex; align-items:center; justify-content:space-between; margin-bottom:18px;"><div style="font-weight:700; font-size:13.5px;">ThaiPrompt Wallet</div><span style="display:inline-flex; align-items:center; gap:6px; font-size:10.5px; font-weight:700; background:rgba(255,255,255,.2); padding:4px 10px; border-radius:20px;"><span style="width:6px; height:6px; border-radius:50%; background:#9affc9;"></span>Live</span></div>
                <div style="position:relative; padding:18px 20px; border-radius:18px; background:rgba(255,255,255,.16); margin-bottom:16px;">
                    <div style="font-size:11.5px; opacity:.92; font-weight:600;">ยอดคงเหลือทั้งหมด</div>
                    <div style="font-family:'Sora','Anuphan'; font-weight:800; font-size:31px; letter-spacing:-1px; margin:3px 0; text-shadow:0 1px 3px rgba(0,0,0,.18);">฿0<span style="font-size:18px; opacity:.8;">.00</span></div>
                    <div style="font-size:11.5px; font-weight:700; color:#cffae0;">เปิดบัญชีฟรี ไม่มีค่าธรรมเนียมแรกเข้า</div>
                </div>
                <div style="position:relative; display:grid; grid-template-columns:repeat(auto-fit,minmax(120px,1fr)); gap:9px;">
                    @foreach([['ic'=>'💸','l'=>'โอน'],['ic'=>'📥','l'=>'รับเงิน'],['ic'=>'🪙','l'=>'TPX'],['ic'=>'📊','l'=>'ปันผล']] as $a)
                        <div style="display:flex; flex-direction:column; align-items:center; gap:6px; padding:12px 4px; border-radius:13px; background:rgba(255,255,255,.14);">
                            <span style="font-size:15px; line-height:1;">{{ $a['ic'] }}</span>
                            <span style="font-size:11px; font-weight:600;">{{ $a['l'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- ════════ CTA BAND ════════ --}}
    <section style="max-width:1180px; margin:20px auto 50px; padding:0 clamp(16px,3vw,40px);">
        <div style="position:relative; overflow:hidden; padding:46px clamp(24px,4vw,44px); border-radius:28px; background:linear-gradient(135deg,var(--accent1),var(--accent2)); box-shadow:0 14px 40px rgba(0,0,0,.16); color:#fff; display:flex; align-items:center; justify-content:space-between; gap:24px; flex-wrap:wrap;">
            <div><h2 style="margin:0; font-size:clamp(23px,4vw,30px); font-weight:700; letter-spacing:-.4px; text-shadow:0 1px 3px rgba(0,0,0,.14);">พร้อมเริ่มต้นกับไทยพร๊อมท์แล้วหรือยัง?</h2><div style="font-family:'Sora','Anuphan'; font-size:14px; font-weight:600; opacity:.92; margin-top:6px;">หนึ่งแอป ครบทุกบริการ เพื่อชีวิตที่ดีกว่า</div></div>
            <a href="{{ $primaryCtaUrl }}" style="text-decoration:none; flex:none; display:inline-grid; place-items:center; padding:16px 32px; border-radius:15px; font-weight:700; font-size:15px; color:var(--deep1); background:#fff; box-shadow:0 8px 22px rgba(0,0,0,.16);">{{ $primaryCtaLabel }} →</a>
        </div>
    </section>

    {{-- ════════ FOOTER ════════ --}}
    <footer style="border-top:var(--card-border); padding:30px; text-align:center; margin-top:auto;">
        <div style="font-weight:700; font-size:15px; color:var(--ink);">ไทยพร๊อมท์ · ThaiPrompt</div>
        <div style="font-size:12px; color:var(--ink2); margin-top:6px;">© {{ date('Y') + 543 }} ThaiPrompt · แพลตฟอร์มคนไทย เพื่อคนไทย เพื่อเอเชีย</div>
        <div style="font-size:12px; color:var(--ink2); margin-top:10px; display:flex; gap:16px; justify-content:center; flex-wrap:wrap;">
            <a href="{{ $shopUrl }}" style="color:var(--deep1); text-decoration:none; font-weight:600;">ร้านค้า</a>
            <a href="#services" style="color:var(--ink2); text-decoration:none;">บริการ</a>
            <a href="{{ $authUser ? $dashUrl : $loginUrl }}" style="color:var(--ink2); text-decoration:none;">{{ $authUser ? 'แดชบอร์ด' : 'เข้าสู่ระบบ' }}</a>
        </div>
    </footer>
</div>
@endsection
