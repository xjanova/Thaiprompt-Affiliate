{{--
 | สินค้าแนะนำของร้านค้าทางการ — ธีม V4 (frontend-v4) · GAP-08 (เดิมไม่มีไฟล์ view → หน้า 500)
 | ข้อมูลจาก OfficialShopController@featured: $products (paginator 24), $stats [official, featured]
 --}}
@extends('layouts.frontend-v4')

@section('title', 'สินค้าแนะนำ - ร้านค้าทางการ')
@section('meta_description', 'สินค้าแนะนำจากร้านค้าทางการของไทยพร๊อมท์ คัดสรรคุณภาพ ของแท้ 100%')

@php
    $ofeFav = [];
    if (auth()->check()) {
        try {
            $ofeFav = \App\Models\ProductFavorite::where('user_id', auth()->id())
                ->whereIn('product_id', collect($products->items())->pluck('id')->all())
                ->pluck('product_id')->map(fn ($id) => (int) $id)->all();
        } catch (\Throwable $e) {
            $ofeFav = [];
        }
    }
@endphp

@section('content')
<x-theme-v4.shop-kit />
<x-theme-v4.public-header active="shop" :search="true" />

<main style="flex:1; padding-bottom:40px;">
    @include('shop.official._hero', [
        'ohTitle' => 'สินค้าแนะนำจากร้านค้าทางการ',
        'ohSubtitle' => 'คัดสรรโดยทีมไทยพร๊อมท์ — สินค้าขายดีและคุ้มค่าที่สุดของร้านทางการ',
        'ohStats' => [
            ['value' => $stats['featured'] ?? $products->total(), 'label' => 'สินค้าแนะนำ'],
            ['value' => $stats['official'] ?? 0, 'label' => 'สินค้าทั้งร้าน'],
        ],
        'ohCrumbs' => [
            ['label' => 'ร้านค้า', 'href' => route('storefront.index')],
            ['label' => 'ร้านค้าทางการ', 'href' => route('official-shop.index')],
            ['label' => 'สินค้าแนะนำ', 'href' => null],
        ],
        'ohSearch' => null,
        'ohAction' => route('official-shop.index'),
    ])

    <section class="sf-wrap sf-section">
        <div style="margin-bottom:14px;">
            <a href="{{ route('official-shop.index') }}" class="tp-btn" style="text-decoration:none; height:44px;"><i class="fas fa-arrow-left"></i> ร้านค้าทางการทั้งหมด</a>
        </div>

        @if($products->count() > 0)
            <div class="sf-grid">
                @foreach($products as $product)
                    <x-theme-v4.product-card :product="$product" :href="route('official-shop.show', $product->slug ?: $product->id)" :favorited="in_array((int) $product->id, $ofeFav, true)" />
                @endforeach
            </div>
            @if($products->hasPages())
                <div style="margin-top:20px;">{{ $products->links(view()->exists('vendor.pagination.tp-v4') ? 'vendor.pagination.tp-v4' : null) }}</div>
            @endif
        @else
            <div class="tp-card" style="text-align:center; padding:40px 16px;">
                <div style="font-size:44px;" aria-hidden="true">⭐</div>
                <h2 style="margin:10px 0 6px; font-size:18px; font-weight:800; color:var(--ink);">ยังไม่มีสินค้าแนะนำในขณะนี้</h2>
                <a href="{{ route('official-shop.index') }}" class="sf-btn3d" style="margin-top:8px;">ดูสินค้าทั้งหมดของร้านทางการ</a>
            </div>
        @endif
    </section>
</main>

<x-theme-v4.public-footer />
<x-eve.widget surface="storefront" />
@endsection
