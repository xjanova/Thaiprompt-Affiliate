{{--
 | รายละเอียดสินค้าในบริบทร้าน (/store/{storeSlug}/product/{productSlug}) — ธีม V4 (frontend-v4)
 | ข้อมูลจาก VendorStoreController@showProduct: $store, $product, $relatedProducts, $hasPurchased, $cashbackInfo, $shippingInfo, $layoutSettings
 | ใช้สีของร้าน (ถ้าผู้ขายตั้งไว้) แทนสีหลักของธีมในหน้านี้
 --}}
@extends('layouts.frontend-v4')

@section('title', $product->name.' - '.$store->store_name)
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags((string) ($product->short_description ?: $product->description)), 160))

@php
    // สีร้าน — ใส่ทับ --accent1/--accent2 เฉพาะเมื่อเป็นสีที่ถูกต้อง (ห้ามอ้างตัวแปรตัวเอง จะกลายเป็นค่าไม่ถูกต้องทั้งหน้า)
    $ppA = \App\Support\Shop\StoreTheme::brand($layoutSettings->primary_color ?? $store->primary_color ?? null, '');
    $ppB = \App\Support\Shop\StoreTheme::brand($layoutSettings->secondary_color ?? $store->secondary_color ?? null, '');
    $ppVars = trim(($ppA !== '' ? '--accent1: '.$ppA.'; --store-a: '.$ppA.';' : '--store-a: var(--accent1);')
        .' '.($ppB !== '' ? '--accent2: '.$ppB.'; --store-b: '.$ppB.';' : '--store-b: var(--accent2);'));
    $ppLogo = \App\Services\Shop\ShopPresenter::imageUrl($store->store_logo);

    $ppRelatedFav = [];
    if (auth()->check() && $relatedProducts && $relatedProducts->count() > 0) {
        try {
            $ppRelatedFav = \App\Models\ProductFavorite::where('user_id', auth()->id())
                ->whereIn('product_id', $relatedProducts->pluck('id')->all())
                ->pluck('product_id')->map(fn ($id) => (int) $id)->all();
        } catch (\Throwable $e) {
            $ppRelatedFav = [];
        }
    }
@endphp

@section('content')
<x-theme-v4.shop-kit />
<x-theme-v4.public-header active="shop" :search="true" />

<main style="flex:1; padding-bottom:40px; {{ $ppVars }}">
    <section class="sf-wrap" style="padding-top:18px;">
        <a href="{{ route('store.show', $store->store_slug) }}" style="display:flex; align-items:center; gap:10px; padding:10px 14px; border-radius:18px; text-decoration:none; color:var(--on-accent, #fff); background:linear-gradient(135deg, var(--store-a), var(--store-b)); box-shadow:var(--raise);">
            <span style="width:36px; height:36px; flex:none; border-radius:12px; overflow:hidden; display:grid; place-items:center; background:rgba(255,255,255,.22);">
                @if($ppLogo)<img src="{{ $ppLogo }}" alt="" style="width:100%; height:100%; object-fit:cover;" onerror="this.style.display='none';">@else 🏪 @endif
            </span>
            <strong style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $store->store_name }}</strong>
            @if($store->is_verified)<i class="fas fa-circle-check"></i>@endif
            <span style="margin-left:auto; font-size:12.5px; font-weight:700; opacity:.92;">ดูร้าน <i class="fas fa-arrow-right"></i></span>
        </a>
        <nav class="sf-breadcrumb" aria-label="เส้นทาง" style="margin-top:12px;">
            <a href="{{ route('store.show', $store->store_slug) }}">{{ $store->store_name }}</a>
            @if($product->category)
                <span aria-hidden="true">/</span>
                <a href="{{ route('store.show', ['slug' => $store->store_slug, 'category' => $product->category->slug]) }}">{{ $product->category->name }}</a>
            @endif
            <span aria-hidden="true">/</span>
            <span style="color:var(--ink); font-weight:600;">{{ \Illuminate\Support\Str::limit($product->name, 50) }}</span>
        </nav>
    </section>

    @include('shop._product-detail', [
        'product' => $product,
        'pdCashback' => $cashbackInfo,
        'pdShipping' => $shippingInfo,
        'pdStore' => $store,
        'pdStoreHref' => route('store.show', $store->store_slug),
    ])

    @if($relatedProducts && $relatedProducts->count() > 0)
        <section class="sf-wrap sf-section">
            <div class="sf-section-h">
                <div>
                    <div class="sf-kicker" style="color:var(--store-a);">MORE FROM THIS STORE</div>
                    <h2 class="sf-title">สินค้าอื่นในร้านนี้</h2>
                </div>
                <a href="{{ route('store.show', $store->store_slug) }}#all-products" class="tp-btn" style="text-decoration:none;">ดูทั้งหมด <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="sf-grid">
                @foreach($relatedProducts as $related)
                    <x-theme-v4.product-card :product="$related"
                        :href="route('store.product', ['storeSlug' => $store->store_slug, 'productSlug' => $related->slug ?: $related->id])"
                        :favorited="in_array((int) $related->id, $ppRelatedFav, true)" />
                @endforeach
            </div>
        </section>
    @endif
</main>

<x-theme-v4.public-footer />
<x-eve.widget surface="storefront" />
@endsection
