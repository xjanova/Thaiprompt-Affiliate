{{--
 | การ์ดร้าน (ข้อมูลรูปแบบ FreshMarketShopPresenceService::shopCard() + url [+ distance_km, top_items])
 | ใช้: @include('taladsod.partials.shop-card', ['shop' => $shopArray])
 --}}
@php
    $scPresence = $shop['presence'] ?? [];
    $scOpen = (bool) ($shop['is_open'] ?? false);
    $scMobile = (bool) ($shop['is_mobile'] ?? false);
    $scLive = $scOpen && (bool) ($scPresence['live_location_sharing'] ?? false);
    $scLabel = $scPresence['location_label'] ?? null;
    $scCloses = $scOpen ? ($scPresence['closes_at'] ?? null) : null;
@endphp
<a href="{{ $shop['url'] ?? route('taladsod.seller', $shop['id']) }}" class="ts-shop">
    <span class="ts-avatar">
        @if(! empty($shop['shop_image']))
            <img src="{{ $shop['shop_image'] }}" alt="" loading="lazy">
        @else
            {{ \App\Support\TaladsodWebUi::initial($shop['shop_name'] ?? '') }}
        @endif
    </span>
    <span style="flex:1; min-width:0; display:flex; flex-direction:column; gap:6px;">
        <span style="display:flex; align-items:center; gap:6px; min-width:0;">
            <b style="font-size:14.5px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $shop['shop_name'] ?? 'ร้านตลาดสด' }}</b>
            @if(! empty($shop['is_verified']))
                <i class="fas fa-circle-check" style="color:var(--ts-info); font-size:13px;" title="ร้านยืนยันแล้ว" aria-label="ร้านยืนยันแล้ว"></i>
            @endif
        </span>
        <span class="ts-row" style="gap:6px;">
            @if($scOpen)
                <span class="ts-pill ts-tone-ok"><span class="ts-dot {{ $scLive ? 'live' : '' }}"></span> {{ $scLive ? 'เปิดอยู่ · ตำแหน่งสด' : 'เปิดอยู่' }}</span>
            @else
                <span class="ts-pill ts-tone-muted"><i class="fas fa-moon" aria-hidden="true"></i> ปิดอยู่</span>
            @endif
            @if($scMobile)
                <span class="ts-pill ts-tone-deep"><i class="fas fa-cart-flatbed" aria-hidden="true"></i> รถเข็น/ตลาดนัด</span>
            @endif
            @if(isset($shop['distance_km']))
                <span class="ts-pill ts-tone-info"><i class="fas fa-location-dot" aria-hidden="true"></i> {{ \App\Support\TaladsodWebUi::distance($shop['distance_km']) }}</span>
            @endif
        </span>
        @if($scLabel || $scCloses)
            <span class="ts-muted ts-small" style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                @if($scLabel)<i class="fas fa-map-pin" aria-hidden="true"></i> {{ $scLabel }}@endif
                @if($scCloses) · ปิด {{ \App\Support\TaladsodWebUi::time($scCloses) }} น.@endif
            </span>
        @endif
        <span class="ts-muted ts-small"><span class="ts-star-view">★</span> {{ number_format((float) ($shop['rating_average'] ?? 0), 1) }} ({{ number_format((int) ($shop['rating_count'] ?? 0)) }} รีวิว)</span>
        @if(! empty($shop['top_items']))
            <span class="ts-thumbs" aria-hidden="true">
                @foreach(array_slice($shop['top_items'], 0, 3) as $item)
                    @if(! empty($item['image_url']))
                        <img src="{{ $item['image_url'] }}" alt="" loading="lazy">
                    @else
                        <span>🥬</span>
                    @endif
                @endforeach
            </span>
        @endif
    </span>
</a>
