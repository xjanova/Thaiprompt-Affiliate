{{--
 | แถบเมนูตลาดสด (หน้าผู้ซื้อ/หน้าสาธารณะ) — ใต้ส่วนหัวสาธารณะ
 | ใช้: @include('taladsod.partials.nav', ['active' => 'home|search|cart|orders|shop'])
 | ป้ายจำนวนในตะกร้าอัปเดตสดเมื่อมีอีเวนต์ ts-cart-count {count}
 --}}
@php
    $tnUser = auth()->user();
    $tnActive = $active ?? null;
    $tnCart = 0;
    $tnIsSeller = false;

    if ($tnUser) {
        try {
            $tnCart = (int) \App\Models\FreshMarketCartItem::where('user_id', $tnUser->id)->sum('quantity');
            $tnIsSeller = \App\Models\FreshMarketSeller::where('user_id', $tnUser->id)->exists();
        } catch (\Throwable $e) {
            $tnCart = 0;
        }
    }

    $tnItems = [
        ['key' => 'home', 'label' => 'ตลาดสด', 'icon' => 'fa-carrot', 'href' => route('taladsod.home')],
        ['key' => 'search', 'label' => 'ค้นหา', 'icon' => 'fa-magnifying-glass', 'href' => route('taladsod.search')],
        ['key' => 'cart', 'label' => 'ตะกร้า', 'icon' => 'fa-basket-shopping', 'href' => route('taladsod.cart'), 'badge' => true],
        ['key' => 'orders', 'label' => 'ออเดอร์ของฉัน', 'icon' => 'fa-receipt', 'href' => route('taladsod.orders')],
    ];

    $tnItems[] = $tnIsSeller
        ? ['key' => 'shop', 'label' => 'ร้านของฉัน', 'icon' => 'fa-store', 'href' => route('taladsod.seller.dashboard')]
        : ['key' => 'open-shop', 'label' => 'เปิดร้านฟรี', 'icon' => 'fa-store', 'href' => route('taladsod.landing.seller'), 'cta' => true];
@endphp
<nav class="ts-nav" aria-label="เมนูตลาดสด" x-data="{ cartCount: {{ $tnCart }} }" x-on:ts-cart-count.window="cartCount = Number($event.detail.count) || 0">
    @foreach($tnItems as $item)
        <a href="{{ $item['href'] }}"
           class="{{ $tnActive === $item['key'] ? 'is-on' : '' }} {{ ! empty($item['cta']) ? 'is-cta' : '' }}"
           @if($tnActive === $item['key']) aria-current="page" @endif>
            <i class="fas {{ $item['icon'] }}" aria-hidden="true"></i> {{ $item['label'] }}
            @if(! empty($item['badge']))
                <span class="ts-badge" x-show="cartCount > 0" x-text="cartCount > 99 ? '99+' : cartCount" @if($tnCart <= 0) x-cloak @endif>{{ $tnCart > 99 ? '99+' : $tnCart }}</span>
            @endif
        </a>
    @endforeach
</nav>
