{{--
 | หน้าร้านแบบแบรนด์ (/lazada, /aliexpress และร้านที่เรียก StorefrontController::showStore) — ธีม V4
 | ข้อมูล: $store (VendorStore), $products (paginator), $storeCategories (มี products_count), $storeBanners
 | ตัวกรอง: ?category=<slug> ?sort_by=newest|popular|price_low|price_high|rating
 --}}
@extends('layouts.frontend-v4')

@section('title', ($store->store_name ?? 'ร้านค้า').' - สินค้าคุณภาพดี')
@section('meta_description', \Illuminate\Support\Str::limit((string) ($store->store_description ?: 'สินค้าคุณภาพจากร้านค้าที่ได้รับการยืนยัน'), 160))

@php
    $bsPrimary = \App\Support\Shop\StoreTheme::brand($store->primary_color ?? null, 'var(--accent1)');
    $bsSecondary = \App\Support\Shop\StoreTheme::brand($store->secondary_color ?? null, 'var(--accent2)');
    $bsBanner = \App\Services\Shop\ShopPresenter::imageUrl($store->store_banner);
    $bsLogo = \App\Services\Shop\ShopPresenter::imageUrl($store->store_logo);
    $bsCategory = is_scalar(request('category')) ? (string) request('category') : '';
    $bsSort = is_scalar(request('sort_by')) ? (string) request('sort_by') : 'newest';
    $bsSorts = ['newest' => 'ใหม่ล่าสุด', 'popular' => 'ยอดนิยม', 'price_low' => 'ราคาต่ำ → สูง', 'price_high' => 'ราคาสูง → ต่ำ', 'rating' => 'คะแนนสูงสุด'];
    $bsSocial = array_filter([
        'facebook' => \App\Support\Shop\StoreTheme::url($store->facebook_url ?? null),
        'instagram' => \App\Support\Shop\StoreTheme::url($store->instagram_url ?? null),
        'line' => ($store->line_oa_id ?? null) ? 'https://line.me/R/ti/p/'.rawurlencode(ltrim((string) $store->line_oa_id, '@')) : null,
    ]);
    $bsCss = \App\Support\Shop\StoreTheme::css($store->custom_css ?? null);

    $bsFavIds = [];
    if (auth()->check()) {
        try {
            $bsFavIds = \App\Models\ProductFavorite::where('user_id', auth()->id())
                ->whereIn('product_id', collect($products->items())->pluck('id')->all())
                ->pluck('product_id')->map(fn ($id) => (int) $id)->all();
        } catch (\Throwable $e) {
            $bsFavIds = [];
        }
    }
@endphp

@if($bsCss !== '')
    @push('styles')
    <style>{!! $bsCss !!}</style>
    @endpush
@endif

@section('content')
<x-theme-v4.shop-kit />
<x-theme-v4.public-header active="shop" :search="true" />

<main style="flex:1; padding-bottom:40px; --store-a: {{ $bsPrimary }}; --store-b: {{ $bsSecondary }};">
    <section class="sf-wrap" style="padding-top:22px;">
        <div class="tp-card" style="padding:0; overflow:hidden;">
            <div style="position:relative; height:clamp(140px, 22vw, 230px); background:linear-gradient(135deg, var(--store-a), var(--store-b));">
                @if($bsBanner)
                    <img src="{{ $bsBanner }}" alt="" aria-hidden="true" fetchpriority="high"
                         style="width:100%; height:100%; object-fit:cover; object-position:center {{ max(0, min(100, (int) ($store->banner_position_y ?? 50))) }}%;"
                         onerror="this.style.display='none';">
                @endif
                <div aria-hidden="true" style="position:absolute; inset:0; background:linear-gradient(180deg, transparent 40%, rgba(0,0,0,.35) 100%);"></div>
            </div>
            <div style="padding:0 clamp(16px, 3vw, 28px) 22px;">
                <div style="display:flex; flex-wrap:wrap; align-items:flex-end; gap:16px; margin-top:-44px; position:relative;">
                    <span style="width:96px; height:96px; flex:none; border-radius:26px; overflow:hidden; display:grid; place-items:center; font-size:40px; color:var(--on-accent, #fff); background:linear-gradient(135deg, var(--store-a), var(--store-b)); box-shadow:var(--card-shadow); border:4px solid var(--card-bg);">
                        @if($bsLogo)
                            <img src="{{ $bsLogo }}" alt="{{ $store->store_name }}" style="width:100%; height:100%; object-fit:cover;" onerror="this.style.display='none';">
                        @else
                            {{ mb_substr((string) $store->store_name, 0, 1) }}
                        @endif
                    </span>
                    <div style="flex:1; min-width:220px; padding-bottom:4px;">
                        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                            <h1 class="sf-h1">{{ $store->store_name }}</h1>
                            @if($store->is_verified)
                                <span class="sf-badge sf-badge-deep"><i class="fas fa-circle-check"></i> ร้านยืนยันแล้ว</span>
                            @endif
                        </div>
                        <div class="sf-meta" style="margin-top:6px; font-size:12.5px;">
                            @if(($store->rating_average ?? 0) > 0)
                                <span><span class="sf-stars">★</span> {{ number_format((float) $store->rating_average, 1) }} ({{ number_format((int) $store->rating_count) }} รีวิว)</span>
                            @endif
                            <span>{{ number_format($products->total()) }} สินค้า</span>
                            @if($store->created_at)
                                <span>เปิดร้านเมื่อ {{ $store->created_at->diffForHumans() }}</span>
                            @endif
                        </div>
                    </div>
                    @if($bsSocial !== [])
                        <div style="display:flex; gap:8px;">
                            @foreach($bsSocial as $network => $link)
                                <a href="{{ $link }}" target="_blank" rel="noopener nofollow" class="tp-icon-btn" style="width:44px; height:44px; text-decoration:none;" aria-label="{{ $network }}">
                                    <i class="fab fa-{{ $network }}"></i>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>

                @if($store->store_description)
                    <p class="tp-muted" style="margin:14px 0 0; font-size:14px; line-height:1.7; max-width:860px;">{{ \Illuminate\Support\Str::limit($store->store_description, 400) }}</p>
                @endif

                <div style="display:flex; flex-wrap:wrap; gap:8px; margin-top:14px;">
                    @if(($store->free_shipping_threshold ?? 0) > 0)
                        <span class="tp-pill tp-pill-soft" style="padding:7px 12px;"><i class="fas fa-truck-fast"></i> ส่งฟรีเมื่อซื้อครบ ฿{{ number_format((float) $store->free_shipping_threshold) }}</span>
                    @endif
                    @if($store->canUseRiderDelivery())
                        <span class="tp-pill tp-pill-soft" style="padding:7px 12px;"><i class="fas fa-motorcycle"></i> ส่งด่วนด้วยไรเดอร์</span>
                    @endif
                    @if(($store->minimum_order_amount ?? 0) > 0)
                        <span class="tp-pill tp-pill-soft" style="padding:7px 12px;"><i class="fas fa-basket-shopping"></i> ขั้นต่ำ ฿{{ number_format((float) $store->minimum_order_amount) }}</span>
                    @endif
                </div>
            </div>
        </div>
    </section>

    @if($storeBanners && $storeBanners->count() > 0)
        <section class="sf-wrap" style="padding-top:16px;">
            <div class="sf-scroll">
                @foreach($storeBanners as $sb)
                    @php
                        $sbImg = \App\Services\Shop\ShopPresenter::imageUrl($sb['image'] ?? null);
                        $sbUrl = \App\Support\Shop\StoreTheme::url($sb['cta_url'] ?? null) ?? (($sb['cta_url'] ?? '') === '#products' ? '#products' : null);
                    @endphp
                    <a @if($sbUrl) href="{{ $sbUrl }}" @endif class="tp-card" style="flex:none; width:min(560px, 86vw); padding:0; overflow:hidden; text-decoration:none; position:relative; min-height:150px; display:flex; align-items:flex-end; background:linear-gradient(135deg, var(--store-a), var(--store-b));">
                        @if($sbImg)
                            <img src="{{ $sbImg }}" alt="" aria-hidden="true" loading="lazy" style="position:absolute; inset:0; width:100%; height:100%; object-fit:cover;">
                        @endif
                        <span aria-hidden="true" style="position:absolute; inset:0; background:linear-gradient(0deg, rgba(0,0,0,.55), transparent 70%);"></span>
                        <span style="position:relative; padding:16px; color:var(--on-accent, #fff);">
                            <span style="display:block; font-weight:800; font-size:17px;">{{ $sb['title'] ?? '' }}</span>
                            @if(! empty($sb['subtitle']))
                                <span style="display:block; font-size:12.5px; opacity:.92; margin-top:2px;">{{ \Illuminate\Support\Str::limit($sb['subtitle'], 90) }}</span>
                            @endif
                        </span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <section class="sf-wrap sf-section" id="products">
        <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px; margin-bottom:14px;">
            <div class="sf-scroll" style="padding-bottom:4px; max-width:100%;">
                <a href="{{ request()->fullUrlWithQuery(['category' => null, 'page' => null]) }}#products" class="sf-chip {{ $bsCategory === '' ? 'is-on' : '' }}">ทั้งหมด</a>
                @foreach($storeCategories ?? [] as $cat)
                    <a href="{{ request()->fullUrlWithQuery(['category' => $cat->slug, 'page' => null]) }}#products" class="sf-chip {{ $bsCategory === $cat->slug ? 'is-on' : '' }}">
                        {{ $cat->name }} <span class="tp-num" style="opacity:.75;">{{ number_format((int) $cat->products_count) }}</span>
                    </a>
                @endforeach
            </div>
            <form method="GET" action="{{ url()->current() }}" style="display:flex; align-items:center; gap:6px;">
                @if($bsCategory !== '')
                    <input type="hidden" name="category" value="{{ $bsCategory }}">
                @endif
                <label for="bs-sort" class="tp-muted" style="font-size:12.5px; font-weight:600;">เรียง</label>
                <select id="bs-sort" name="sort_by" class="tp-input" style="height:44px; width:auto; padding:0 12px;" onchange="this.form.submit()">
                    @foreach($bsSorts as $sk => $sl)
                        <option value="{{ $sk }}" @selected($bsSort === $sk)>{{ $sl }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        @if($products->count() > 0)
            <div class="sf-grid">
                @foreach($products as $product)
                    <x-theme-v4.product-card :product="$product" :favorited="in_array((int) $product->id, $bsFavIds, true)" />
                @endforeach
            </div>
            @if($products->hasPages())
                <div style="margin-top:20px;">
                    {{ $products->links(view()->exists('vendor.pagination.tp-v4') ? 'vendor.pagination.tp-v4' : null) }}
                </div>
            @endif
        @else
            <div class="tp-card" style="text-align:center; padding:40px 16px;">
                <div style="font-size:44px;" aria-hidden="true">📦</div>
                <h2 style="margin:10px 0 6px; font-size:18px; font-weight:800; color:var(--ink);">ยังไม่มีสินค้าในหมวดนี้</h2>
                <a href="{{ url()->current() }}" class="sf-btn3d" style="margin-top:8px;">ดูสินค้าทั้งหมดของร้าน</a>
            </div>
        @endif
    </section>

    <section class="sf-wrap sf-section">
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:14px;">
            <div class="tp-card">
                <div class="tp-section-h" style="margin-bottom:10px;"><i class="fas fa-address-card" style="color:var(--deep1);"></i> ติดต่อร้าน</div>
                <div style="display:flex; flex-direction:column; gap:8px; font-size:13.5px; color:var(--ink2);">
                    @if($store->store_email)<span><i class="fas fa-envelope" style="width:18px;"></i> {{ $store->store_email }}</span>@endif
                    @if($store->store_phone)<span><i class="fas fa-phone" style="width:18px;"></i> {{ $store->store_phone }}</span>@endif
                    @if($store->store_address)<span><i class="fas fa-location-dot" style="width:18px;"></i> {{ $store->store_address }}</span>@endif
                    @if(! $store->store_email && ! $store->store_phone && ! $store->store_address)
                        <span>ร้านยังไม่ได้ระบุช่องทางติดต่อ</span>
                    @endif
                </div>
            </div>
            <div class="tp-card">
                <div class="tp-section-h" style="margin-bottom:10px;"><i class="fas fa-truck" style="color:var(--deep1);"></i> การจัดส่ง</div>
                <div style="display:flex; flex-direction:column; gap:8px; font-size:13.5px; color:var(--ink2);">
                    @if(($store->shipping_fee ?? 0) > 0)<span>ค่าจัดส่งพัสดุ ฿{{ number_format((float) $store->shipping_fee) }}</span>@endif
                    @if(($store->free_shipping_threshold ?? 0) > 0)<span>ส่งฟรีเมื่อซื้อครบ ฿{{ number_format((float) $store->free_shipping_threshold) }}</span>@endif
                    @if($store->canUseRiderDelivery())<span>ส่งด่วนด้วยไรเดอร์ในพื้นที่ ติดตามได้สด</span>@endif
                    <span>ค่าส่งจริงคำนวณตอนชำระเงินตามที่อยู่ของคุณ</span>
                </div>
            </div>
        </div>
    </section>
</main>

<x-theme-v4.public-footer />
<x-eve.widget surface="storefront" />
@endsection
