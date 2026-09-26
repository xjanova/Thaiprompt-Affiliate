{{--
 | แถบหัวธีม "โนวา" — โปร่งบนฉากกลางคืน แล้วกลายเป็นกระจกกรมท่าเมื่อเลื่อน (nova.js ใส่ is-solid)
 | ข้อมูล/ลิงก์ชุดเดียวกับ x-theme-v4.public-header (ตะกร้า · บัญชี · เมนูเปิดร้าน) เพื่อให้พฤติกรรมเหมือนเดิมทุกอย่าง
 | ต้องโหลดคู่กับ public/theme-nova/nova.css (+ nova.js สำหรับปุ่มเสียง/สถานะเลื่อน)
 |
 | props:
 |   active  string|null  เมนูที่กำลังอยู่ home|shop|taladsod|rider|merchant|fortune
 --}}
@props(['active' => 'home'])

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
    $nvSearchUrl = $nv('storefront.index', [], url('/storefront'));

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
@endphp

<header id="nv-nav" class="nv-nav" x-data="{ open: false, merchantOpen: false, searchOpen: false, cartCount: {{ (int) $nvCartCount }} }"
        :class="(open || searchOpen) ? 'is-open' : ''"
        @cart-updated.window="fetch(@js($nvCartCountUrl), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }).then(r => r.ok ? r.json() : null).then(d => { if (d && typeof d.count !== 'undefined') cartCount = Number(d.count) || 0; }).catch(() => {})"
        @keydown.escape.window="open = false; merchantOpen = false; searchOpen = false">
    <div class="nv-nav__in">
        <a href="{{ url('/') }}" class="nv-nav__logo" title="Thai Prompt">
            <x-theme-v4.brand-logo :height="40" variant="dark" />
        </a>

        {{-- เมนูหลัก (จอกว้าง) --}}
        <nav class="nv-nav__links" aria-label="เมนูหลัก">
            @foreach($nvMain as $item)
                <a href="{{ $item['href'] }}" class="nv-nav__link {{ $active === $item['key'] ? 'is-on' : '' }}" @if($active === $item['key']) aria-current="page" @endif>{{ $item['label'] }}</a>
            @endforeach
            <div style="position:relative;" @click.outside="merchantOpen = false">
                <button type="button" class="nv-nav__link {{ $active === 'merchant' ? 'is-on' : '' }}" @click="merchantOpen = !merchantOpen" :aria-expanded="merchantOpen.toString()" aria-haspopup="true">
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
            <a href="{{ $nvFortune['href'] }}" class="nv-nav__link {{ $active === 'fortune' ? 'is-on' : '' }}">{{ $nvFortune['label'] }}</a>
        </nav>

        {{-- ขวา: เสียงเอฟเฟกต์ · ค้นหา · ตะกร้า/บัญชี · เมนูจอแคบ --}}
        <div class="nv-nav__right">
            <button type="button" class="nv-ibtn nv-sfx" data-nv-sfx aria-pressed="true" aria-label="เปิด/ปิดเสียงเอฟเฟกต์ตอนเลือกการ์ด">
                <i class="fas fa-volume-high nv-sfx__on" aria-hidden="true"></i>
                <i class="fas fa-volume-xmark nv-sfx__off" aria-hidden="true"></i>
                <span class="nv-tip">เสียงเอฟเฟกต์ เปิด</span>
            </button>
            <button type="button" class="nv-ibtn" @click="searchOpen = !searchOpen; open = false; if (searchOpen) $nextTick(() => $refs.rhq.focus())" :aria-expanded="searchOpen.toString()" aria-controls="nv-search" aria-label="ค้นหาสินค้า">
                <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
            </button>
            @auth
                <a href="{{ $nvCartUrl }}" class="nv-ibtn" title="ตะกร้าสินค้า" aria-label="ตะกร้าสินค้า">
                    <i class="fas fa-cart-shopping" aria-hidden="true"></i>
                    <span class="nv-ibtn__count" x-show="cartCount > 0" x-cloak x-text="cartCount > 99 ? '99+' : cartCount"></span>
                </a>
                <a href="{{ $nvOrdersUrl }}" class="nv-ibtn nv-hide-t" title="คำสั่งซื้อของฉัน" aria-label="คำสั่งซื้อของฉัน"><i class="fas fa-receipt" aria-hidden="true"></i></a>
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

    {{-- ช่องค้นหา --}}
    <form id="nv-search" method="GET" action="{{ $nvSearchUrl }}" role="search" class="nv-nav__search" x-show="searchOpen" x-cloak x-transition.opacity>
        <label for="nv-q" class="nv-sr">ค้นหาสินค้า</label>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.6-3.6"/></svg>
        <input id="nv-q" x-ref="rhq" type="search" name="search" autocomplete="off" placeholder="ค้นหาสินค้า ร้านค้า หรือหมวดหมู่...">
    </form>

    {{-- แผงเมนู (จอแคบ) --}}
    <div id="nv-panel" class="nv-nav__panel" x-show="open" x-cloak x-transition.opacity>
        @foreach($nvMain as $item)
            <a href="{{ $item['href'] }}" @click="open = false"><i class="fas {{ $item['icon'] }}" aria-hidden="true"></i>{{ $item['label'] }}</a>
        @endforeach
        @foreach($nvMerchant as $m)
            <a href="{{ $m['href'] }}"><i class="fas {{ $m['icon'] }}" aria-hidden="true"></i>เปิดร้าน · {{ $m['label'] }}</a>
        @endforeach
        <a href="{{ $nvFortune['href'] }}"><i class="fas {{ $nvFortune['icon'] }}" aria-hidden="true"></i>{{ $nvFortune['label'] }}</a>
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
