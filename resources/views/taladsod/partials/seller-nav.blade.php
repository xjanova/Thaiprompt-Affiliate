{{--
 | แถบเมนูร้านตลาดสด (หน้าผู้ขาย — layouts.user-v4)
 | ใช้: @include('taladsod.partials.seller-nav', ['seller' => $seller, 'active' => 'dashboard|orders|listings|earnings|profile'])
 | ป้ายออเดอร์รอรับอัปเดตสดเมื่อมีอีเวนต์ ts-pending-count {count}
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
        ['key' => 'dashboard', 'label' => 'หน้าร้านวันนี้', 'icon' => 'fa-store', 'href' => route('taladsod.seller.dashboard')],
        ['key' => 'orders', 'label' => 'ออเดอร์', 'icon' => 'fa-receipt', 'href' => route('taladsod.seller.orders'), 'badge' => true],
        ['key' => 'listings', 'label' => 'สินค้า/เมนู', 'icon' => 'fa-bowl-food', 'href' => route('taladsod.seller.listings')],
        ['key' => 'earnings', 'label' => 'รายได้', 'icon' => 'fa-sack-dollar', 'href' => route('taladsod.seller.earnings')],
        ['key' => 'profile', 'label' => 'ตั้งค่าร้าน', 'icon' => 'fa-gear', 'href' => route('taladsod.seller.profile')],
        ['key' => 'public', 'label' => 'ดูหน้าร้าน', 'icon' => 'fa-arrow-up-right-from-square', 'href' => route('taladsod.seller', $seller->id)],
    ];
@endphp
<nav class="ts-nav" aria-label="เมนูร้านตลาดสด" x-data="{ pending: {{ $snPending }} }" x-on:ts-pending-count.window="pending = Number($event.detail.count) || 0">
    @foreach($snItems as $item)
        <a href="{{ $item['href'] }}" class="{{ $snActive === $item['key'] ? 'is-on' : '' }}" @if($snActive === $item['key']) aria-current="page" @endif>
            <i class="fas {{ $item['icon'] }}" aria-hidden="true"></i> {{ $item['label'] }}
            @if(! empty($item['badge']))
                <span class="ts-badge" x-show="pending > 0" x-text="pending > 99 ? '99+' : pending" @if($snPending <= 0) x-cloak @endif>{{ $snPending }}</span>
            @endif
        </a>
    @endforeach
</nav>
