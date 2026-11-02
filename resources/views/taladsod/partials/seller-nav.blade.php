{{--
 | แถบเมนูร้านตลาดสด (หน้าผู้ขาย — ธีมโนวา layouts.user-nova)
 | ใช้: @include('taladsod.partials.seller-nav', ['seller' => $seller, 'active' => 'dashboard|orders|listings|earnings|profile'])
 | ป้ายออเดอร์รอรับอัปเดตสดเมื่อมีอีเวนต์ ts-pending-count {count} (หน้าแดชบอร์ด/ออเดอร์ poll แล้วส่งมา)
 --}}
@php
    $snActive = $active ?? null;
    $snPending = 0;

    try {
        $snPending = (int) \App\Models\FreshMarketOrder::where('seller_id', $seller->id)->pending()->count();
    } catch (\Throwable $e) {
        $snPending = 0;
    }

    $snItems = [
        ['key' => 'dashboard', 'label' => 'หน้าร้านวันนี้', 'icon' => 'store', 'href' => route('taladsod.seller.dashboard')],
        ['key' => 'orders', 'label' => 'ออเดอร์', 'icon' => 'bag', 'href' => route('taladsod.seller.orders'), 'badge' => true],
        ['key' => 'listings', 'label' => 'สินค้า/เมนู', 'icon' => 'basket', 'href' => route('taladsod.seller.listings')],
        ['key' => 'earnings', 'label' => 'รายได้', 'icon' => 'cash', 'href' => route('taladsod.seller.earnings')],
        ['key' => 'profile', 'label' => 'ตั้งค่าร้าน', 'icon' => 'gear', 'href' => route('taladsod.seller.profile')],
    ];
@endphp
<nav class="nvm-tabs nvm-td-nav" aria-label="เมนูร้านตลาดสด" x-data="{ pending: {{ $snPending }} }" x-on:ts-pending-count.window="pending = Number($event.detail.count) || 0">
    @foreach($snItems as $item)
        <a href="{{ $item['href'] }}" class="{{ $snActive === $item['key'] ? 'is-on' : '' }}" @if($snActive === $item['key']) aria-current="page" @endif>
            <svg class="nvm-ico"><use href="#nvm-i-{{ $item['icon'] }}"/></svg>{{ $item['label'] }}
            @if(! empty($item['badge']))
                <span class="nvm-chip nvm-chip--warn" x-show="pending > 0" x-text="pending > 99 ? '99+' : pending" @if($snPending <= 0) x-cloak @endif>{{ $snPending }}</span>
            @endif
        </a>
    @endforeach
    <a href="{{ route('taladsod.seller', $seller->id) }}">
        <svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>ดูหน้าร้าน
    </a>
</nav>
