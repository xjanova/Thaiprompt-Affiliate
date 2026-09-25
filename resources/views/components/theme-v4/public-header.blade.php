{{--
 | ส่วนหัวสาธารณะ ธีม V4 (ใช้กับทุกหน้าที่ @extends('layouts.frontend-v4'))
 | ใช้ร่วมกัน: หน้าแรก / ร้านค้า / ตะกร้า / ชำระเงิน / ตลาดสด / หน้าร้าน
 |
 | ตัวอย่าง:
 |   <x-theme-v4.public-header active="shop" :search="true" />
 |   <x-theme-v4.public-header active="home" :links="[['href' => '#services', 'label' => 'บริการ']]" />
 |
 | props:
 |   active            string|null  เมนูที่กำลังอยู่ home|shop|official|stores|taladsod|rider|merchant|cart|orders
 |   links             array        ลิงก์เสริมก่อนเมนูหลัก [['href' => '#x', 'label' => '...']]
 |   linksAfter        array        ลิงก์เสริมหลังเมนูหลัก
 |   search            bool         แสดงช่องค้นหาสินค้า (ค่าเริ่มต้นไม่แสดง)
 |   searchAction      string|null  ปลายทางฟอร์มค้นหา (ค่าเริ่มต้น storefront.index)
 |   searchName        string       ชื่อช่องค้นหา (ค่าเริ่มต้น search)
 |   searchValue       string|null  ค่าเริ่มต้นในช่องค้นหา
 |   searchPlaceholder string
 |   suggest           bool         แนะนำสินค้าขณะพิมพ์ (storefront.search) — ใช้กับการค้นหาร้านค้าออนไลน์เท่านั้น
 |   sticky            bool         ติดขอบบนเมื่อเลื่อน
 --}}
@props([
    'active' => null,
    'links' => [],
    'linksAfter' => [],
    'search' => false,
    'searchAction' => null,
    'searchName' => 'search',
    'searchValue' => null,
    'searchPlaceholder' => 'ค้นหาสินค้า ร้านค้า หรือหมวดหมู่...',
    'suggest' => true,
    'sticky' => true,
])

@php
    $rh = fn (string $name, array $params = [], ?string $fallback = null) => \Illuminate\Support\Facades\Route::has($name)
        ? route($name, $params)
        : ($fallback ?? url('/'));

    $phUser = auth()->user();
    $phIsAdmin = $phUser && ((($phUser->role ?? null) === 'admin') || ($phUser->is_super_admin ?? false));
    $phDashUrl = $phIsAdmin ? $rh('admin.dashboard') : $rh('user.dashboard');
    $phLoginUrl = $rh('login', [], url('/login'));
    $phRegisterUrl = $rh('register', [], url('/register'));
    $phLogoutUrl = $rh('logout', [], url('/logout'));
    $phCartUrl = $rh('cart.index', [], url('/cart'));
    $phOrdersUrl = $rh('orders.index', [], url('/orders'));
    $phCartCountUrl = $rh('cart.count', [], url('/cart/count'));

    // จำนวนสินค้าในตะกร้า (ตะกร้าเว็บ) — อ่านครั้งเดียวตอนเรนเดอร์ แล้วรีเฟรชเมื่อมีอีเวนต์ cart-updated
    $phCartCount = 0;
    if ($phUser) {
        try {
            $phCartCount = (int) \App\Models\ShoppingCart::where('user_id', $phUser->id)->sum('quantity');
        } catch (\Throwable $e) {
            $phCartCount = 0;
        }
    }

    // เมนูหลัก — ทุกหน้าสาธารณะใช้ชุดเดียวกัน
    $phMain = [
        ['key' => 'shop', 'label' => 'ร้านค้า', 'icon' => 'fa-bag-shopping', 'href' => $rh('storefront.index', [], url('/storefront'))],
        ['key' => 'taladsod', 'label' => 'ตลาดสด', 'icon' => 'fa-carrot', 'href' => $rh('taladsod.home', [], url('/taladsod'))],
        ['key' => 'rider', 'label' => 'เป็นไรเดอร์', 'icon' => 'fa-motorcycle', 'href' => $phUser ? $rh('user.rider.dashboard') : $rh('taladsod.landing.rider')],
    ];
    $phMerchant = [
        ['label' => 'ร้านค้าออนไลน์ (ส่งทั่วไทย)', 'desc' => 'ขายสินค้า ส่งพัสดุหรือไรเดอร์', 'icon' => 'fa-store', 'href' => $rh('user.seller-apply.index', [], $phLoginUrl)],
        ['label' => 'ร้านตลาดสด / รถเข็น', 'desc' => 'ขายของสด อาหาร ใกล้บ้าน', 'icon' => 'fa-cart-flatbed', 'href' => $rh('taladsod.landing.seller')],
    ];

    $phSearchAction = $searchAction ?: $rh('storefront.index', [], url('/storefront'));
    $phSuggestUrl = $suggest ? $rh('storefront.search', [], '') : '';
    $phShowUrlBase = $rh('shop.show', ['slug' => '__SLUG__'], url('/shop/__SLUG__'));
    $phSearchValue = is_scalar($searchValue) ? (string) $searchValue : '';
@endphp

@once
@push('styles')
<style>
    .tp-ph-link { text-decoration:none; display:inline-flex; align-items:center; gap:7px; min-height:40px; padding:9px 14px; border-radius:11px; font-size:13px; font-weight:600; color:var(--ink2); transition:background .15s ease, color .15s ease, box-shadow .15s ease; }
    .tp-ph-link:hover { color:var(--ink); background:color-mix(in srgb, var(--accent1) 10%, transparent); }
    .tp-ph-link.is-active { color:var(--deep1); background:var(--a1soft); box-shadow:var(--inset-sm); }
    .tp-ph-menu-item { display:flex; align-items:flex-start; gap:11px; padding:10px 12px; border-radius:12px; text-decoration:none; color:var(--ink); transition:background .15s ease; }
    .tp-ph-menu-item:hover { background:color-mix(in srgb, var(--accent1) 12%, transparent); }
    .tp-ph-desk { display:flex; }
    .tp-ph-mob { display:none; }
    .tp-ph-suggest a:hover { background:color-mix(in srgb, var(--accent1) 12%, transparent); }
    @media (max-width: 960px) {
        .tp-ph-desk { display:none !important; }
        .tp-ph-mob { display:inline-flex !important; }
    }
</style>
@endpush
@endonce

<header x-data="{ open: false, merchantOpen: false, cartCount: {{ (int) $phCartCount }} }"
        @cart-updated.window="fetch(@js($phCartCountUrl), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }).then(r => r.ok ? r.json() : null).then(d => { if (d && typeof d.count !== 'undefined') cartCount = Number(d.count) || 0; }).catch(() => {})"
        @keydown.escape.window="open = false; merchantOpen = false"
        style="{{ $sticky ? 'position:sticky; top:0;' : 'position:relative;' }} z-index:30; display:flex; align-items:center; flex-wrap:wrap; gap:12px 18px; padding:15px clamp(16px,3vw,40px); background:var(--card-bg); -webkit-backdrop-filter:blur(10px); backdrop-filter:blur(10px); box-shadow:var(--card-shadow-sm); border-bottom:var(--card-border);">

    <a href="{{ url('/') }}" style="display:flex; align-items:center; text-decoration:none;" title="ไทยพร๊อมท์">
        <x-theme-v4.brand-logo :height="44" />
    </a>

    {{-- เมนูหลัก (จอกว้าง) --}}
    <nav class="tp-ph-desk" aria-label="เมนูหลัก" style="gap:4px; margin-left:14px; flex-wrap:wrap; align-items:center;">
        @foreach($links as $link)
            <a href="{{ $link['href'] }}" class="tp-ph-link">{{ $link['label'] }}</a>
        @endforeach
        @foreach($phMain as $item)
            <a href="{{ $item['href'] }}" class="tp-ph-link {{ $active === $item['key'] ? 'is-active' : '' }}" @if($active === $item['key']) aria-current="page" @endif>{{ $item['label'] }}</a>
        @endforeach
        <div style="position:relative;" @click.outside="merchantOpen = false">
            <button type="button" class="tp-ph-link {{ $active === 'merchant' ? 'is-active' : '' }}" style="border:0; cursor:pointer; font-family:inherit; {{ $active === 'merchant' ? '' : 'background:transparent;' }}"
                    @click="merchantOpen = !merchantOpen" :aria-expanded="merchantOpen.toString()" aria-haspopup="true">
                เปิดร้าน <i class="fas fa-chevron-down" style="font-size:10px; transition:transform .15s ease;" :style="merchantOpen ? 'transform:rotate(180deg)' : ''"></i>
            </button>
            <div x-show="merchantOpen" x-cloak x-transition.opacity
                 style="position:absolute; top:calc(100% + 8px); left:0; z-index:40; width:290px; padding:8px; border-radius:16px; background:var(--surf); box-shadow:var(--card-shadow); border:var(--card-border);">
                @foreach($phMerchant as $m)
                    <a href="{{ $m['href'] }}" class="tp-ph-menu-item">
                        <span class="tp-tile" style="width:36px; height:36px; font-size:15px;"><i class="fas {{ $m['icon'] }}"></i></span>
                        <span style="min-width:0;">
                            <span style="display:block; font-size:13px; font-weight:700;">{{ $m['label'] }}</span>
                            <span class="tp-muted" style="display:block; font-size:11.5px; margin-top:2px;">{{ $m['desc'] }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
        @foreach($linksAfter as $link)
            <a href="{{ $link['href'] }}" class="tp-ph-link">{{ $link['label'] }}</a>
        @endforeach
    </nav>

    {{-- ช่องค้นหา (จอกว้าง) --}}
    @if($search)
        <form method="GET" action="{{ $phSearchAction }}" role="search" class="tp-ph-desk"
              style="flex:1; min-width:220px; max-width:440px; position:relative;"
              x-data="{ q: @js($phSearchValue), items: [], timer: null,
                        suggest() {
                            clearTimeout(this.timer);
                            const url = @js($phSuggestUrl);
                            if (!url || this.q.trim().length < 2) { this.items = []; return; }
                            this.timer = setTimeout(() => {
                                fetch(url + '?q=' + encodeURIComponent(this.q.trim()), { headers: { 'Accept': 'application/json' } })
                                    .then(r => r.ok ? r.json() : [])
                                    .then(d => { this.items = Array.isArray(d) ? d.slice(0, 8) : []; })
                                    .catch(() => { this.items = []; });
                            }, 300);
                        } }"
              @click.outside="items = []">
            <label for="tp-ph-search" style="position:absolute; left:-9999px;">ค้นหาสินค้า</label>
            <i class="fas fa-magnifying-glass" aria-hidden="true" style="position:absolute; left:15px; top:50%; transform:translateY(-50%); color:var(--ink2); font-size:13px;"></i>
            <input id="tp-ph-search" type="search" name="{{ $searchName }}" x-model="q" @input="suggest()" autocomplete="off"
                   class="tp-input" placeholder="{{ $searchPlaceholder }}" style="padding-left:40px; height:44px;">
            <div x-show="items.length > 0" x-cloak class="tp-ph-suggest"
                 style="position:absolute; top:calc(100% + 6px); left:0; right:0; z-index:40; padding:6px; border-radius:14px; background:var(--surf); box-shadow:var(--card-shadow); border:var(--card-border);">
                <template x-for="s in items" :key="s.id">
                    <a :href="@js($phShowUrlBase).replace('__SLUG__', encodeURIComponent(s.slug || s.id))"
                       style="display:flex; align-items:center; gap:10px; padding:8px 10px; border-radius:10px; text-decoration:none; color:var(--ink);">
                        <span style="width:38px; height:38px; flex:none; border-radius:10px; overflow:hidden; background:var(--bg); box-shadow:var(--inset-sm); display:grid; place-items:center;">
                            <img x-show="s.main_image_url" :src="s.main_image_url" alt="" style="width:100%; height:100%; object-fit:cover;">
                        </span>
                        <span style="min-width:0; flex:1;">
                            <span style="display:block; font-size:13px; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" x-text="s.name"></span>
                            <span class="tp-num" style="display:block; font-size:12px; font-weight:700; color:var(--deep1);" x-text="'฿' + Number(s.price || 0).toLocaleString('th-TH')"></span>
                        </span>
                    </a>
                </template>
            </div>
        </form>
    @endif

    {{-- ขวา: ตะกร้า + บัญชี --}}
    <div style="display:flex; align-items:center; gap:9px; margin-left:auto;">
        @auth
            <a href="{{ $phCartUrl }}" title="ตะกร้าสินค้า" aria-label="ตะกร้าสินค้า"
               style="position:relative; text-decoration:none; display:grid; place-items:center; width:44px; height:44px; border-radius:12px; color:var(--ink); background:var(--card-bg); box-shadow:var(--raise); {{ $active === 'cart' ? 'box-shadow:var(--inset-sm); color:var(--deep1);' : '' }}">
                <i class="fas fa-cart-shopping"></i>
                <span x-show="cartCount > 0" x-cloak x-text="cartCount > 99 ? '99+' : cartCount" class="tp-num grid"
                      style="position:absolute; top:-5px; right:-5px; min-width:20px; height:20px; padding:0 5px; border-radius:10px; place-items:center; font-size:10.5px; font-weight:800; color:var(--on-accent, #fff); background:linear-gradient(135deg,var(--accent2),var(--deep2)); box-shadow:0 2px 6px rgba(0,0,0,.18);"></span>
            </a>
            <a href="{{ $phOrdersUrl }}" title="คำสั่งซื้อของฉัน" aria-label="คำสั่งซื้อของฉัน" class="tp-ph-desk"
               style="text-decoration:none; place-items:center; justify-content:center; align-items:center; width:44px; height:44px; border-radius:12px; color:var(--ink); background:var(--card-bg); box-shadow:var(--raise); {{ $active === 'orders' ? 'box-shadow:var(--inset-sm); color:var(--deep1);' : '' }}">
                <i class="fas fa-receipt"></i>
            </a>
            <span class="tp-muted tp-ph-desk" style="font-size:12px; font-weight:600; max-width:140px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">สวัสดี, {{ \Illuminate\Support\Str::limit($phUser->name ?? 'สมาชิก', 16) }}</span>
            <a href="{{ $phDashUrl }}" class="tp-ph-desk" style="text-decoration:none; align-items:center; gap:6px; padding:0 16px; height:44px; border-radius:12px; font-weight:700; font-size:12.5px; color:var(--on-accent, #fff); background:linear-gradient(135deg,var(--accent1),var(--accent2)); box-shadow:var(--raise); text-shadow:0 1px 2px rgba(0,0,0,.14);">
                <i class="fas fa-gauge-high"></i> แดชบอร์ด
            </a>
            <form method="POST" action="{{ $phLogoutUrl }}" class="tp-ph-desk" style="margin:0;">
                @csrf
                <button type="submit" title="ออกจากระบบ" aria-label="ออกจากระบบ" style="cursor:pointer; border:0; display:grid; place-items:center; width:44px; height:44px; border-radius:12px; font-weight:600; color:var(--ink); background:var(--card-bg); box-shadow:var(--raise);">
                    <i class="fas fa-arrow-right-from-bracket"></i>
                </button>
            </form>
        @else
            <a href="{{ $phLoginUrl }}" class="tp-ph-desk" style="text-decoration:none; place-items:center; align-items:center; padding:0 15px; height:44px; border-radius:12px; font-weight:600; font-size:12.5px; color:var(--ink); background:var(--card-bg); box-shadow:var(--raise);">เข้าสู่ระบบ</a>
            <a href="{{ $phRegisterUrl }}" class="tp-ph-desk" style="text-decoration:none; place-items:center; align-items:center; padding:0 18px; height:44px; border-radius:12px; font-weight:700; font-size:12.5px; color:var(--on-accent, #fff); background:linear-gradient(135deg,var(--accent1),var(--accent2)); box-shadow:var(--raise); text-shadow:0 1px 2px rgba(0,0,0,.14);">สมัครสมาชิก</a>
        @endauth

        {{-- ปุ่มเมนู (จอแคบ) --}}
        <button type="button" class="tp-ph-mob" @click="open = !open" :aria-expanded="open.toString()" aria-controls="tp-ph-panel" aria-label="เปิดเมนู"
                style="cursor:pointer; border:0; align-items:center; justify-content:center; width:44px; height:44px; border-radius:12px; color:var(--ink); background:var(--card-bg); box-shadow:var(--raise);">
            <i class="fas" :class="open ? 'fa-xmark' : 'fa-bars'"></i>
        </button>
    </div>

    {{-- แผงเมนู (จอแคบ) --}}
    <div id="tp-ph-panel" x-show="open" x-cloak x-transition.opacity class="flex"
         style="flex-basis:100%; flex-direction:column; gap:12px; padding-top:6px;">
        @if($search)
            <form method="GET" action="{{ $phSearchAction }}" role="search" style="position:relative;">
                <i class="fas fa-magnifying-glass" aria-hidden="true" style="position:absolute; left:15px; top:50%; transform:translateY(-50%); color:var(--ink2); font-size:13px;"></i>
                <input type="search" name="{{ $searchName }}" value="{{ $phSearchValue }}" class="tp-input" placeholder="{{ $searchPlaceholder }}" aria-label="ค้นหาสินค้า" style="padding-left:40px; height:46px;">
            </form>
        @endif
        <nav aria-label="เมนูหลัก (มือถือ)" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:8px;">
            @foreach($links as $link)
                <a href="{{ $link['href'] }}" @click="open = false" class="tp-ph-link" style="background:var(--surf); box-shadow:var(--raise);">{{ $link['label'] }}</a>
            @endforeach
            @foreach($phMain as $item)
                <a href="{{ $item['href'] }}" class="tp-ph-link {{ $active === $item['key'] ? 'is-active' : '' }}" style="{{ $active === $item['key'] ? '' : 'background:var(--surf); box-shadow:var(--raise);' }}">
                    <i class="fas {{ $item['icon'] }}" style="width:16px; text-align:center;"></i>{{ $item['label'] }}
                </a>
            @endforeach
            @foreach($linksAfter as $link)
                <a href="{{ $link['href'] }}" @click="open = false" class="tp-ph-link" style="background:var(--surf); box-shadow:var(--raise);">{{ $link['label'] }}</a>
            @endforeach
        </nav>
        <div style="padding:10px; border-radius:16px; background:var(--surf); box-shadow:var(--inset-sm);">
            <div class="tp-muted" style="font-size:11.5px; font-weight:700; margin:2px 4px 6px;">เปิดร้านกับไทยพร๊อมท์</div>
            @foreach($phMerchant as $m)
                <a href="{{ $m['href'] }}" class="tp-ph-menu-item">
                    <span class="tp-tile" style="width:36px; height:36px; font-size:15px;"><i class="fas {{ $m['icon'] }}"></i></span>
                    <span style="min-width:0;">
                        <span style="display:block; font-size:13px; font-weight:700;">{{ $m['label'] }}</span>
                        <span class="tp-muted" style="display:block; font-size:11.5px; margin-top:2px;">{{ $m['desc'] }}</span>
                    </span>
                </a>
            @endforeach
        </div>
        @auth
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(130px,1fr)); gap:8px;">
                <a href="{{ $phOrdersUrl }}" class="tp-btn" style="text-decoration:none; height:46px;"><i class="fas fa-receipt"></i> คำสั่งซื้อของฉัน</a>
                <a href="{{ $phDashUrl }}" class="tp-btn tp-btn-primary" style="text-decoration:none; height:46px;"><i class="fas fa-gauge-high"></i> แดชบอร์ด</a>
                <form method="POST" action="{{ $phLogoutUrl }}" style="margin:0; display:grid;">
                    @csrf
                    <button type="submit" class="tp-btn" style="height:46px;"><i class="fas fa-arrow-right-from-bracket"></i> ออกจากระบบ</button>
                </form>
            </div>
        @else
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">
                <a href="{{ $phLoginUrl }}" class="tp-btn" style="text-decoration:none; height:46px;">เข้าสู่ระบบ</a>
                <a href="{{ $phRegisterUrl }}" class="tp-btn tp-btn-primary" style="text-decoration:none; height:46px;">สมัครสมาชิก</a>
            </div>
        @endauth
    </div>
</header>
