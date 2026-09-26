{{--
 | แถบหัวธีม "โนวา"
 |   หน้าแรก (solid=false): โปร่งบนฉากกลางคืน แล้วกลายเป็นกระจกกรมท่าเมื่อเลื่อน (nova.js ใส่ is-solid)
 |   หน้าอื่น (solid=true): กรมท่าทึบติดขอบบน (sticky อยู่ในกระแสหน้า ไม่ลอยทับเนื้อหา) + โหลด nova.css เอง
 | ข้อมูล/ลิงก์ชุดเดียวกับ x-theme-v4.public-header (ตะกร้า · บัญชี · เมนูเปิดร้าน · ค้นหา+แนะนำสินค้า)
 | x-theme-v4.public-header ส่งต่อมาที่นี่เมื่อเปิด config shop.nova_public — props จึงรับชุดเดียวกับของ V4
 |
 | props:
 |   active            string|null  home|shop|official|stores|taladsod|rider|merchant|fortune|cart|orders
 |   solid             bool         แถบทึบสำหรับหน้าที่ไม่มีฮีโร่มืด (ไม่มีปุ่มเสียง เพราะไม่มีการ์ดเอฟเฟกต์)
 |   links/linksAfter  array        ลิงก์เสริมก่อน/หลังเมนูหลัก [['href' => '#x', 'label' => '...']]
 |   search            bool|null    แสดงปุ่มค้นหา (null = หน้าแรกแสดง / หน้าอื่นไม่แสดง เหมือน V4)
 |   searchAction/searchName/searchValue/searchPlaceholder  ปลายทางและช่องค้นหา
 |   suggest           bool         แนะนำสินค้าขณะพิมพ์ (เฉพาะค้นหาร้านค้าออนไลน์)
 |   sticky            bool         ติดขอบบนเมื่อเลื่อน (ใช้กับ solid)
 --}}
@props([
    'active' => 'home',
    'solid' => false,
    'links' => [],
    'linksAfter' => [],
    'search' => null,
    'searchAction' => null,
    'searchName' => 'search',
    'searchValue' => null,
    'searchPlaceholder' => 'ค้นหาสินค้า ร้านค้า หรือหมวดหมู่...',
    'suggest' => true,
    'sticky' => true,
])

@php
    $nv = fn (string $name, array $params = [], ?string $fallback = null) => \Illuminate\Support\Facades\Route::has($name)
        ? route($name, $params)
        : ($fallback ?? url('/'));

    $nvUser = auth()->user();
    $nvIsAdmin = $nvUser && ((($nvUser->role ?? null) === 'admin') || ($nvUser->is_super_admin ?? false));
    $nvDashUrl = $nvIsAdmin ? $nv('admin.dashboard') : $nv('user.dashboard');
    $nvLoginUrl = $nv('login', [], url('/login'));
    $nvRegisterUrl = $nv('register', [], url('/register'));
    $nvLogoutUrl = $nv('logout', [], url('/logout'));
    $nvCartUrl = $nv('cart.index', [], url('/cart'));
    $nvOrdersUrl = $nv('orders.index', [], url('/orders'));
    $nvCartCountUrl = $nv('cart.count', [], url('/cart/count'));

    // เมนูที่กำลังอยู่ — คีย์ของ V4 ที่ไม่มีในแถบโนวา รวมเข้ากลุ่มใกล้สุด
    $nvActive = in_array($active, ['official', 'stores'], true) ? 'shop' : $active;

    // ค้นหา: หน้าแรกเปิดเสมอ · หน้าอื่นตาม prop (V4 ค่าเริ่มต้น = ไม่แสดง)
    $nvSearchOn = $search ?? ! $solid;
    $nvSearchAction = $searchAction ?: $nv('storefront.index', [], url('/storefront'));
    $nvSearchValue = is_scalar($searchValue) ? (string) $searchValue : '';
    $nvSuggestUrl = ($nvSearchOn && $suggest && ! $searchAction) ? $nv('storefront.search', [], '') : '';
    $nvShowUrlBase = $nv('shop.show', ['slug' => '__SLUG__'], url('/shop/__SLUG__'));

    // จำนวนสินค้าในตะกร้า — อ่านครั้งเดียวตอนเรนเดอร์ แล้วรีเฟรชเมื่อมีอีเวนต์ cart-updated (เหมือนแถบหัว V4)
    $nvCartCount = 0;
    if ($nvUser) {
        try {
            $nvCartCount = (int) \App\Models\ShoppingCart::where('user_id', $nvUser->id)->sum('quantity');
        } catch (\Throwable $e) {
            $nvCartCount = 0;
        }
    }

    $nvMain = [
        ['key' => 'home', 'label' => 'หน้าแรก', 'icon' => 'fa-house', 'href' => url('/')],
        ['key' => 'shop', 'label' => 'ร้านค้า', 'icon' => 'fa-bag-shopping', 'href' => url('/').'#products'],
        ['key' => 'taladsod', 'label' => 'ตลาดสด', 'icon' => 'fa-carrot', 'href' => $nv('taladsod.home', [], url('/taladsod'))],
        ['key' => 'rider', 'label' => 'เป็นไรเดอร์', 'icon' => 'fa-motorcycle', 'href' => $nvUser ? $nv('user.rider.dashboard') : $nv('taladsod.landing.rider')],
    ];
    $nvFortune = ['key' => 'fortune', 'label' => 'ดูดวง', 'icon' => 'fa-moon', 'href' => $nv('tarot.index', [], url('/tarot'))];
    $nvMerchant = [
        ['label' => 'ร้านค้าออนไลน์ (ส่งทั่วไทย)', 'desc' => 'ขายสินค้า ส่งพัสดุหรือไรเดอร์', 'icon' => 'fa-store', 'href' => $nv('user.seller-apply.index', [], $nvLoginUrl)],
        ['label' => 'ร้านตลาดสด / รถเข็น', 'desc' => 'ขายของสด อาหาร ใกล้บ้าน', 'icon' => 'fa-cart-flatbed', 'href' => $nv('taladsod.landing.seller')],
    ];
    $nvVer = static fn (string $p) => is_file(public_path($p)) ? filemtime(public_path($p)) : 1;
@endphp

@if($solid)
    @once
        @push('styles')
            <link href="https://fonts.googleapis.com/css2?family=Trirong:wght@600;700&family=Cinzel:wght@600&display=swap" rel="stylesheet">
            <link rel="stylesheet" href="{{ asset('theme-nova/nova.css') }}?v={{ $nvVer('theme-nova/nova.css') }}">
        @endpush
    @endonce
@endif

<header id="nv-nav" class="nv-nav {{ $solid ? 'nv-nav--solid is-solid' : '' }} {{ $solid && ! $sticky ? 'nv-nav--static' : '' }}"
        x-data="{ open: false, merchantOpen: false, searchOpen: {{ $nvSearchOn && $nvSearchValue !== '' ? 'true' : 'false' }}, cartCount: {{ (int) $nvCartCount }},
                  q: @js($nvSearchValue), items: [], timer: null,
                  suggest() {
                      clearTimeout(this.timer);
                      const url = @js($nvSuggestUrl);
                      if (!url || this.q.trim().length < 2) { this.items = []; return; }
                      this.timer = setTimeout(() => {
                          fetch(url + '?q=' + encodeURIComponent(this.q.trim()), { headers: { 'Accept': 'application/json' } })
                              .then(r => r.ok ? r.json() : [])
                              .then(d => { this.items = Array.isArray(d) ? d.slice(0, 8) : []; })
                              .catch(() => { this.items = []; });
                      }, 300);
                  } }"
        :class="(open || searchOpen) ? 'is-open' : ''"
        @cart-updated.window="fetch(@js($nvCartCountUrl), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }).then(r => r.ok ? r.json() : null).then(d => { if (d && typeof d.count !== 'undefined') cartCount = Number(d.count) || 0; }).catch(() => {})"
        @keydown.escape.window="open = false; merchantOpen = false; items = []">
    <div class="nv-nav__in">
        <a href="{{ url('/') }}" class="nv-nav__logo" title="Thai Prompt">
            <x-theme-v4.brand-logo :height="40" variant="dark" />
        </a>

        {{-- เมนูหลัก (จอกว้าง) --}}
        <nav class="nv-nav__links" aria-label="เมนูหลัก">
            @foreach($links as $link)
                <a href="{{ $link['href'] }}" class="nv-nav__link nv-nav__link--extra">{{ $link['label'] }}</a>
            @endforeach
            @foreach($nvMain as $item)
                <a href="{{ $item['href'] }}" class="nv-nav__link {{ $nvActive === $item['key'] ? 'is-on' : '' }}" @if($nvActive === $item['key']) aria-current="page" @endif>{{ $item['label'] }}</a>
            @endforeach
            <div style="position:relative;" @click.outside="merchantOpen = false">
                <button type="button" class="nv-nav__link {{ $nvActive === 'merchant' ? 'is-on' : '' }}" @click="merchantOpen = !merchantOpen" :aria-expanded="merchantOpen.toString()" aria-haspopup="true">
                    เปิดร้าน <i class="fas fa-chevron-down" style="font-size:10px;" :style="merchantOpen ? 'transform:rotate(180deg)' : ''" aria-hidden="true"></i>
                </button>
                <div class="nv-nav__drop" x-show="merchantOpen" x-cloak x-transition.opacity>
                    @foreach($nvMerchant as $m)
                        <a href="{{ $m['href'] }}">
                            <i class="fas {{ $m['icon'] }}" aria-hidden="true"></i>
                            <span><b>{{ $m['label'] }}</b><small>{{ $m['desc'] }}</small></span>
                        </a>
                    @endforeach
                </div>
            </div>
            <a href="{{ $nvFortune['href'] }}" class="nv-nav__link {{ $nvActive === 'fortune' ? 'is-on' : '' }}">{{ $nvFortune['label'] }}</a>
            @foreach($linksAfter as $link)
                <a href="{{ $link['href'] }}" class="nv-nav__link nv-nav__link--extra">{{ $link['label'] }}</a>
            @endforeach
        </nav>

        {{-- ขวา: เสียงเอฟเฟกต์ (หน้าแรก) · ค้นหา · ตะกร้า/บัญชี · เมนูจอแคบ --}}
        <div class="nv-nav__right">
            @unless($solid)
                <button type="button" class="nv-ibtn nv-sfx" data-nv-sfx aria-pressed="true" aria-label="เปิด/ปิดเสียงเอฟเฟกต์ตอนเลือกการ์ด">
                    <i class="fas fa-volume-high nv-sfx__on" aria-hidden="true"></i>
                    <i class="fas fa-volume-xmark nv-sfx__off" aria-hidden="true"></i>
                    <span class="nv-tip">เสียงเอฟเฟกต์ เปิด</span>
                </button>
            @endunless
            @if($nvSearchOn)
                <button type="button" class="nv-ibtn" @click="searchOpen = !searchOpen; open = false; if (searchOpen) $nextTick(() => $refs.nvq.focus())" :aria-expanded="searchOpen.toString()" aria-controls="nv-search" aria-label="ค้นหา">
                    <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
                </button>
            @endif
            @auth
                <a href="{{ $nvCartUrl }}" class="nv-ibtn {{ $nvActive === 'cart' ? 'is-on' : '' }}" title="ตะกร้าสินค้า" aria-label="ตะกร้าสินค้า" @if($nvActive === 'cart') aria-current="page" @endif>
                    <i class="fas fa-cart-shopping" aria-hidden="true"></i>
                    <span class="nv-ibtn__count" x-show="cartCount > 0" x-cloak x-text="cartCount > 99 ? '99+' : cartCount"></span>
                </a>
                <a href="{{ $nvOrdersUrl }}" class="nv-ibtn nv-hide-t {{ $nvActive === 'orders' ? 'is-on' : '' }}" title="คำสั่งซื้อของฉัน" aria-label="คำสั่งซื้อของฉัน" @if($nvActive === 'orders') aria-current="page" @endif><i class="fas fa-receipt" aria-hidden="true"></i></a>
                <a href="{{ $nvDashUrl }}" class="nv-btn nv-btn--gold nv-hide-m">แดชบอร์ด</a>
                <form method="POST" action="{{ $nvLogoutUrl }}" class="nv-hide-t" style="margin:0;">
                    @csrf
                    <button type="submit" class="nv-ibtn" title="ออกจากระบบ" aria-label="ออกจากระบบ"><i class="fas fa-arrow-right-from-bracket" aria-hidden="true"></i></button>
                </form>
            @else
                <a href="{{ $nvLoginUrl }}" class="nv-btn nv-btn--ghost nv-hide-m">เข้าสู่ระบบ</a>
                <a href="{{ $nvRegisterUrl }}" class="nv-btn nv-btn--gold">สมัครสมาชิก</a>
            @endauth
            <button type="button" class="nv-ibtn nv-nav__burger" @click="open = !open; searchOpen = false" :aria-expanded="open.toString()" aria-controls="nv-panel" aria-label="เปิดเมนู">
                <i class="fas" :class="open ? 'fa-xmark' : 'fa-bars'" aria-hidden="true"></i>
            </button>
        </div>
    </div>

    {{-- ช่องค้นหา (+ แนะนำสินค้าขณะพิมพ์) --}}
    @if($nvSearchOn)
        <form id="nv-search" method="GET" action="{{ $nvSearchAction }}" role="search" class="nv-nav__search" x-show="searchOpen" x-cloak x-transition.opacity @click.outside="items = []">
            <label for="nv-q" class="nv-sr">{{ $searchPlaceholder }}</label>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.6-3.6"/></svg>
            <input id="nv-q" x-ref="nvq" type="search" name="{{ $searchName }}" value="{{ $nvSearchValue }}" x-model="q" @input="suggest()" autocomplete="off" placeholder="{{ $searchPlaceholder }}">
            <div class="nv-suggest" x-show="items.length > 0" x-cloak>
                <template x-for="s in items" :key="s.id">
                    <a :href="@js($nvShowUrlBase).replace('__SLUG__', encodeURIComponent(s.slug || s.id))">
                        <span class="nv-suggest__img"><img x-show="s.main_image_url" :src="s.main_image_url" alt=""></span>
                        <span class="nv-suggest__txt"><b x-text="s.name"></b><small x-text="'฿' + Number(s.price || 0).toLocaleString('th-TH')"></small></span>
                    </a>
                </template>
            </div>
        </form>
    @endif

    {{-- แผงเมนู (จอแคบ) --}}
    <div id="nv-panel" class="nv-nav__panel" x-show="open" x-cloak x-transition.opacity>
        @foreach($links as $link)
            <a href="{{ $link['href'] }}" @click="open = false"><i class="fas fa-angle-right" aria-hidden="true"></i>{{ $link['label'] }}</a>
        @endforeach
        @foreach($nvMain as $item)
            <a href="{{ $item['href'] }}" @click="open = false"><i class="fas {{ $item['icon'] }}" aria-hidden="true"></i>{{ $item['label'] }}</a>
        @endforeach
        @foreach($nvMerchant as $m)
            <a href="{{ $m['href'] }}"><i class="fas {{ $m['icon'] }}" aria-hidden="true"></i>เปิดร้าน · {{ $m['label'] }}</a>
        @endforeach
        <a href="{{ $nvFortune['href'] }}"><i class="fas {{ $nvFortune['icon'] }}" aria-hidden="true"></i>{{ $nvFortune['label'] }}</a>
        @foreach($linksAfter as $link)
            <a href="{{ $link['href'] }}" @click="open = false"><i class="fas fa-angle-right" aria-hidden="true"></i>{{ $link['label'] }}</a>
        @endforeach
        @auth
            <div class="nv-nav__panel-row">
                <a href="{{ $nvOrdersUrl }}"><i class="fas fa-receipt" aria-hidden="true"></i>คำสั่งซื้อ</a>
                <a href="{{ $nvDashUrl }}" class="nv-nav__panel-gold">แดชบอร์ด</a>
            </div>
            <form method="POST" action="{{ $nvLogoutUrl }}" style="margin:0;">
                @csrf
                <button type="submit"><i class="fas fa-arrow-right-from-bracket" aria-hidden="true"></i>ออกจากระบบ</button>
            </form>
        @else
            <div class="nv-nav__panel-row">
                <a href="{{ $nvLoginUrl }}">เข้าสู่ระบบ</a>
                <a href="{{ $nvRegisterUrl }}" class="nv-nav__panel-gold">สมัครสมาชิก</a>
            </div>
        @endauth
    </div>
</header>
