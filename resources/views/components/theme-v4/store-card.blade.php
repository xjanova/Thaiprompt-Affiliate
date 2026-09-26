{{--
 | การ์ดร้านค้า ธีม V4 (หน้ารวมร้าน / ร้านแนะนำ) — ต้องมี <x-theme-v4.shop-kit /> ในหน้า
 |
 | ตัวอย่าง:  <x-theme-v4.store-card :store="$store" :products="$store->products->take(4)" />
 |           <x-theme-v4.store-card :official="true" />
 |
 | props:
 |   store     VendorStore|null  ร้าน (null + official=true = การ์ดร้านค้าทางการ)
 |   official  bool              การ์ดร้านค้าทางการของแพลตฟอร์ม
 |   products  Collection|null   สินค้าตัวอย่างของร้าน (รูปย่อ)
 |   count     int|null          จำนวนสินค้า (ไม่ระบุ = products_count ของร้าน)
 --}}
@props([
    'store' => null,
    'official' => false,
    'products' => null,
    'count' => null,
])

@php
    $scIsStore = $store instanceof \App\Models\VendorStore;
    $scName = $scIsStore ? (string) $store->store_name : 'ร้านค้าทางการ ไทยพร๊อมท์';
    $scHref = $scIsStore
        ? route('store.show', $store->store_slug)
        : (\Illuminate\Support\Facades\Route::has('official-shop.index') ? route('official-shop.index') : url('/official-shop'));
    $scBanner = $scIsStore ? \App\Services\Shop\ShopPresenter::imageUrl($store->store_banner) : null;
    $scLogo = $scIsStore ? \App\Services\Shop\ShopPresenter::imageUrl($store->store_logo) : null;
    $scRating = $scIsStore ? (float) ($store->rating_average ?? 0) : 0;
    $scRatingCount = $scIsStore ? (int) ($store->rating_count ?? 0) : 0;
    $scCount = $count ?? ($scIsStore ? (int) ($store->products_count ?? 0) : null);
    $scRider = $scIsStore && $store->canUseRiderDelivery();
    $scVerified = $official || ($scIsStore && $store->is_verified);
    $scProducts = collect($products ?? [])->take(4);
    $scBannerY = $scIsStore ? max(0, min(100, (int) ($store->banner_position_y ?? 50))) : 50;
@endphp

<a href="{{ $scHref }}" class="sf-card" style="text-decoration:none; color:inherit;" {{ $attributes }}>
    <div class="sf-store-banner" style="position:relative; height:96px; overflow:hidden; background:linear-gradient(135deg, var(--accent1), var(--accent2));">
        @if($scBanner)
            <img src="{{ $scBanner }}" alt="" aria-hidden="true" loading="lazy" decoding="async"
                 style="width:100%; height:100%; object-fit:cover; object-position:center {{ $scBannerY }}%;"
                 onerror="this.style.display='none';">
        @endif
        <div class="sf-badges">
            @if($official)
                <span class="sf-badge sf-badge-deep"><i class="fas fa-crown"></i> ร้านทางการ</span>
            @elseif($scVerified)
                <span class="sf-badge sf-badge-deep"><i class="fas fa-circle-check"></i> ยืนยันแล้ว</span>
            @endif
            @if($scRider)
                <span class="sf-badge sf-badge-ok"><i class="fas fa-motorcycle"></i> ส่งไรเดอร์</span>
            @endif
        </div>
    </div>
    <div style="padding:0 14px 14px; display:flex; flex-direction:column; gap:10px; flex:1;">
        <div style="display:flex; align-items:flex-end; gap:10px; margin-top:-26px; position:relative;">
            <span class="sf-store-logo" style="width:56px; height:56px; flex:none; border-radius:16px; overflow:hidden; display:grid; place-items:center; font-size:24px; background:var(--surf); box-shadow:var(--raise); border:3px solid var(--card-bg);">
                @if($scLogo)
                    <img src="{{ $scLogo }}" alt="{{ $scName }}" loading="lazy" style="width:100%; height:100%; object-fit:cover;" onerror="this.style.display='none';">
                @else
                    {{ $official ? '👑' : '🏪' }}
                @endif
            </span>
            <div style="min-width:0; padding-bottom:2px;">
                <div style="font-weight:800; font-size:14px; color:var(--ink); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $scName }}</div>
                <div class="sf-meta" style="margin-top:3px;">
                    @if($scRating > 0)
                        <span><span class="sf-stars">★</span> {{ number_format($scRating, 1) }} ({{ number_format($scRatingCount) }})</span>
                    @endif
                    @if($scCount !== null)
                        <span>{{ number_format($scCount) }} สินค้า</span>
                    @endif
                    @if($official)
                        <span>ของแท้ รับประกันโดยแพลตฟอร์ม</span>
                    @endif
                </div>
            </div>
        </div>

        @if($scProducts->isNotEmpty())
            <div style="display:grid; grid-template-columns:repeat(4, minmax(0, 1fr)); gap:6px;">
                @foreach($scProducts as $scProduct)
                    @php $scImg = \App\Services\Shop\ShopPresenter::productImages($scProduct)[0] ?? null; @endphp
                    <span style="aspect-ratio:1/1; border-radius:10px; overflow:hidden; background:var(--surf); box-shadow:var(--inset-sm); display:grid; place-items:center; font-size:16px;">
                        @if($scImg)
                            <img src="{{ $scImg }}" alt="{{ $scProduct->name }}" loading="lazy" style="width:100%; height:100%; object-fit:cover;" onerror="this.style.display='none';">
                        @else
                            📦
                        @endif
                    </span>
                @endforeach
            </div>
        @endif

        <span class="tp-btn tp-btn-sm" style="margin-top:auto; width:100%;">เข้าชมร้าน <i class="fas fa-arrow-right"></i></span>
    </div>
</a>
