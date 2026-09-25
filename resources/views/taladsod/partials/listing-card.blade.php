{{--
 | การ์ดสินค้าตลาดสด (ใช้ในกริด .sf-grid)
 | ใช้: @include('taladsod.partials.listing-card', ['listing' => $listing])
 |     หน้าร้าน: @include('taladsod.partials.listing-card', ['listing' => $listing, 'shop' => $seller, 'hideShop' => true])
 | ร้านเปิด/ปิด: ผลค้นหาใกล้ฉันมีคอลัมน์ shop_is_open / distance_km มาให้ ไม่งั้นอ่านจากร้านที่โหลดมา
 --}}
@php
    $lcAttrs = $listing->getAttributes();
    $lcSeller = isset($shop) && $shop instanceof \App\Models\FreshMarketSeller
        ? $shop
        : ($listing->relationLoaded('seller') ? $listing->seller : null);
    $lcHideShop = (bool) ($hideShop ?? false);
    $lcOpen = array_key_exists('shop_is_open', $lcAttrs)
        ? (bool) $lcAttrs['shop_is_open']
        : ($lcSeller ? $lcSeller->isOpenNow() : true);
    $lcMobile = $lcSeller ? $lcSeller->isMobileShop() : false;
    $lcDistance = array_key_exists('distance_km', $lcAttrs) && $lcAttrs['distance_km'] !== null ? (float) $lcAttrs['distance_km'] : null;
    $lcImage = $listing->primary_image;
    $lcDiscount = (float) $listing->discount_percentage;
    $lcHasOptions = (int) ($listing->option_groups_count ?? 0) > 0;
    $lcCashback = $listing->cashback_percent;
@endphp
<article class="sf-card">
    <a href="{{ route('taladsod.listing', $listing->slug) }}" aria-label="{{ $listing->title }}">
        <div class="sf-media">
            @if($lcImage)
                <img src="{{ $lcImage }}" alt="{{ $listing->title }}" loading="lazy">
            @else
                <span class="sf-noimg" aria-hidden="true">🥬</span>
            @endif
            <div class="sf-badges">
                @if($lcDiscount > 0)
                    <span class="sf-badge sf-badge-sale">-{{ (int) round($lcDiscount) }}%</span>
                @endif
                @if($listing->is_organic)
                    <span class="sf-badge sf-badge-ok"><i class="fas fa-leaf" aria-hidden="true"></i> อินทรีย์</span>
                @endif
                @if($lcCashback)
                    <span class="sf-badge sf-badge-gold">คืน {{ rtrim(rtrim(number_format((float) $lcCashback, 1), '0'), '.') }}%</span>
                @endif
            </div>
            @if(! $lcOpen)
                <div class="sf-ribbon is-warm"><i class="fas fa-moon" aria-hidden="true"></i> ร้านปิดอยู่</div>
            @elseif($lcMobile)
                <div class="sf-ribbon"><i class="fas fa-cart-flatbed" aria-hidden="true"></i> รถเข็นเปิดอยู่ตอนนี้</div>
            @endif
        </div>
        <div class="sf-body">
            <div class="sf-name">{{ $listing->title }}</div>
            <div style="display:flex; align-items:baseline; gap:6px; flex-wrap:wrap;">
                <span class="sf-price">฿{{ \App\Support\TaladsodWebUi::money($listing->price) }}</span>
                <span class="ts-muted ts-small">/ {{ $listing->unit ?: 'ชิ้น' }}</span>
                @if($listing->compare_at_price && (float) $listing->compare_at_price > (float) $listing->price)
                    <span class="sf-compare">฿{{ \App\Support\TaladsodWebUi::money($listing->compare_at_price) }}</span>
                @endif
            </div>
            <div class="sf-meta">
                @if($lcSeller && ! $lcHideShop)
                    <span style="min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:100%;"><i class="fas fa-store" aria-hidden="true"></i> {{ $lcSeller->shop_name }}</span>
                @endif
                @if($lcDistance !== null)
                    <span><i class="fas fa-location-dot" aria-hidden="true"></i> {{ \App\Support\TaladsodWebUi::distance($lcDistance) }}</span>
                @endif
                @if($lcHasOptions)
                    <span class="ts-pill ts-tone-info" style="padding:4px 8px; font-size:10.5px;"><i class="fas fa-sliders" aria-hidden="true"></i> เลือกได้</span>
                @endif
            </div>
        </div>
    </a>
</article>
