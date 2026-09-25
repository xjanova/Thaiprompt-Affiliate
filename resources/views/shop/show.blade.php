{{--
 | รายละเอียดสินค้า — ธีม V4 (frontend-v4)
 | ข้อมูลจาก ShopController@show:
 |   $product (category, seller, images, variants, approvedReviews.user), $relatedProducts, $hasPurchased,
 |   $cashbackInfo|null, $shippingInfo|null, $isAffiliate, $platform, $platformLabel, $platformAccent, $platformAccent2, $outboundUrl
 | ใส่ตะกร้า/ซื้อทันที: POST cart.add (JSON) ผ่าน window.tpShop.addToCart (อยู่ใน shop._product-detail)
 | สินค้า affiliate: ลิงก์ขาออกต้องวิ่งผ่าน $outboundUrl (/go/{code}) เท่านั้น — ห้ามใส่ affiliate_url ดิบ
 --}}
@extends('layouts.frontend-v4')

@section('title', $product->name.' - ร้านค้าออนไลน์')
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags((string) ($product->short_description ?: $product->description)), 160))

@php
    $spRelatedFav = [];
    if (auth()->check() && $relatedProducts && $relatedProducts->count() > 0) {
        try {
            $spRelatedFav = \App\Models\ProductFavorite::where('user_id', auth()->id())
                ->whereIn('product_id', $relatedProducts->pluck('id')->all())
                ->pluck('product_id')->map(fn ($id) => (int) $id)->all();
        } catch (\Throwable $e) {
            $spRelatedFav = [];
        }
    }
@endphp

@section('content')
<x-theme-v4.shop-kit />
<x-theme-v4.public-header active="shop" :search="true" />

<main style="flex:1; padding-bottom:40px;">
    <section class="sf-wrap" style="padding-top:18px;">
        <nav class="sf-breadcrumb" aria-label="เส้นทาง">
            <a href="{{ route('home') }}"><i class="fas fa-house"></i> หน้าแรก</a>
            <span aria-hidden="true">/</span>
            <a href="{{ route('storefront.index') }}">ร้านค้า</a>
            @if($product->category)
                <span aria-hidden="true">/</span>
                <a href="{{ route('storefront.index', ['category' => $product->category->slug]) }}">{{ $product->category->name }}</a>
            @endif
            <span aria-hidden="true">/</span>
            <span style="color:var(--ink); font-weight:600;">{{ \Illuminate\Support\Str::limit($product->name, 50) }}</span>
        </nav>
    </section>

    @include('shop._product-detail', [
        'product' => $product,
        'pdAffiliate' => $isAffiliate,
        'pdPlatform' => $platform,
        'pdPlatformLabel' => $platformLabel,
        'pdOutbound' => $outboundUrl,
        'pdCashback' => $cashbackInfo,
        'pdShipping' => $shippingInfo,
    ])

    @if($relatedProducts && $relatedProducts->count() > 0)
        <section class="sf-wrap sf-section">
            <div class="sf-section-h">
                <div>
                    <div class="sf-kicker">YOU MAY ALSO LIKE</div>
                    <h2 class="sf-title">สินค้าที่เกี่ยวข้อง</h2>
                </div>
            </div>
            <div class="sf-grid">
                @foreach($relatedProducts as $related)
                    <x-theme-v4.product-card :product="$related" :favorited="in_array((int) $related->id, $spRelatedFav, true)" />
                @endforeach
            </div>
        </section>
    @endif
</main>

<x-theme-v4.public-footer />
<x-eve.widget surface="storefront" />
@endsection
