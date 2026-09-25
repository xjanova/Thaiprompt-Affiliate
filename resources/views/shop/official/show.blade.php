{{--
 | รายละเอียดสินค้าร้านค้าทางการ — ธีม V4 (frontend-v4)
 | ข้อมูลจาก OfficialShopController@show: $product (category, images, variants, approvedReviews.user), $relatedProducts, $hasPurchased, $cashbackInfo, $coinBalance
 | ซื้อด้วย Coins: POST official-shop.purchase-with-coin {quantity} (เฉพาะสินค้าที่เปิด allow_coin_purchase)
 --}}
@extends('layouts.frontend-v4')

@section('title', $product->name.' - ร้านค้าทางการ')
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags((string) ($product->short_description ?: $product->description)), 160))

@php
    $osRelatedFav = [];
    if (auth()->check() && $relatedProducts && $relatedProducts->count() > 0) {
        try {
            $osRelatedFav = \App\Models\ProductFavorite::where('user_id', auth()->id())
                ->whereIn('product_id', $relatedProducts->pluck('id')->all())
                ->pluck('product_id')->map(fn ($id) => (int) $id)->all();
        } catch (\Throwable $e) {
            $osRelatedFav = [];
        }
    }
    $osCoin = ($product->allow_coin_purchase && (int) $product->price_coins > 0)
        ? ['price' => (int) $product->price_coins, 'balance' => (int) ($coinBalance ?? 0), 'action' => route('official-shop.purchase-with-coin', $product->slug)]
        : null;
@endphp

@section('content')
<x-theme-v4.shop-kit />
<x-theme-v4.public-header active="shop" :search="true" />

<main style="flex:1; padding-bottom:40px;">
    <section class="sf-wrap" style="padding-top:18px;">
        <nav class="sf-breadcrumb" aria-label="เส้นทาง">
            <a href="{{ route('storefront.index') }}">ร้านค้า</a>
            <span aria-hidden="true">/</span>
            <a href="{{ route('official-shop.index') }}"><i class="fas fa-crown"></i> ร้านค้าทางการ</a>
            @if($product->category)
                <span aria-hidden="true">/</span>
                <a href="{{ route('official-shop.category', $product->category->slug) }}">{{ $product->category->name }}</a>
            @endif
            <span aria-hidden="true">/</span>
            <span style="color:var(--ink); font-weight:600;">{{ \Illuminate\Support\Str::limit($product->name, 50) }}</span>
        </nav>
    </section>

    @if(session('error'))
        <section class="sf-wrap" style="padding-top:12px;">
            <div class="sf-note sf-note-err" role="alert">{{ session('error') }}</div>
        </section>
    @endif

    @include('shop._product-detail', [
        'product' => $product,
        'pdCashback' => $cashbackInfo,
        'pdShipping' => (new \App\Services\ShippingService)->getShippingDisplayInfo($product),
        'pdCoin' => $osCoin,
    ])

    @if($relatedProducts && $relatedProducts->count() > 0)
        <section class="sf-wrap sf-section">
            <div class="sf-section-h">
                <div>
                    <div class="sf-kicker"><i class="fas fa-crown"></i> OFFICIAL</div>
                    <h2 class="sf-title">สินค้าอื่นจากร้านค้าทางการ</h2>
                </div>
            </div>
            <div class="sf-grid">
                @foreach($relatedProducts as $related)
                    <x-theme-v4.product-card :product="$related" :href="route('official-shop.show', $related->slug ?: $related->id)" :favorited="in_array((int) $related->id, $osRelatedFav, true)" />
                @endforeach
            </div>
        </section>
    @endif
</main>

<x-theme-v4.public-footer />
<x-eve.widget surface="storefront" />
@endsection
