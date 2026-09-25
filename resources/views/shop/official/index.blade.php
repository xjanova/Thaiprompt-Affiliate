{{--
 | ร้านค้าทางการ (Official Shop) — ธีม V4 (frontend-v4)
 | ข้อมูลจาก OfficialShopController@index: $products (paginator 24), $categories, $brands, $featuredProducts, $stats [official, featured, categories]
 | ตัวกรอง GET: search, category (slug), brand, min_price, max_price, sort_by (newest|popular|price_low|price_high|rating)
 --}}
@extends('layouts.frontend-v4')

@section('title', 'ร้านค้าทางการ ไทยพร๊อมท์ - สินค้าของแท้')
@section('meta_description', 'ร้านค้าทางการของไทยพร๊อมท์ สินค้าคุณภาพจากแพลตฟอร์มโดยตรง รับประกันของแท้ 100%')

@php
    $ofQ = fn (string $key) => is_scalar(request($key)) ? (string) request($key) : '';
    $ofSearch = $ofQ('search');
    $ofSort = $ofQ('sort_by') ?: 'newest';
    $ofBrand = $ofQ('brand');
    $ofMin = $ofQ('min_price');
    $ofMax = $ofQ('max_price');
    $ofSorts = ['newest' => 'ใหม่ล่าสุด', 'popular' => 'ยอดนิยม', 'price_low' => 'ราคาต่ำ → สูง', 'price_high' => 'ราคาสูง → ต่ำ', 'rating' => 'คะแนนสูงสุด'];
    $ofHref = fn ($p) => route('official-shop.show', $p->slug ?: $p->id);

    $ofFav = [];
    if (auth()->check()) {
        try {
            $ofIds = collect($products->items())->pluck('id')->merge(($featuredProducts ?? collect())->pluck('id'))->unique()->all();
            $ofFav = \App\Models\ProductFavorite::where('user_id', auth()->id())->whereIn('product_id', $ofIds)
                ->pluck('product_id')->map(fn ($id) => (int) $id)->all();
        } catch (\Throwable $e) {
            $ofFav = [];
        }
    }
@endphp

@section('content')
<x-theme-v4.shop-kit />
<x-theme-v4.public-header active="shop" :search="true" />

<main style="flex:1; padding-bottom:40px;">
    @include('shop.official._hero', [
        'ohTitle' => 'ร้านค้าทางการ ไทยพร๊อมท์',
        'ohSubtitle' => 'สินค้าคัดสรรจากแพลตฟอร์มโดยตรง รับประกันของแท้ บริการหลังการขายครบ',
        'ohStats' => [
            ['value' => $stats['official'] ?? 0, 'label' => 'สินค้า'],
            ['value' => $stats['featured'] ?? 0, 'label' => 'สินค้าแนะนำ'],
            ['value' => $stats['categories'] ?? 0, 'label' => 'หมวดหมู่'],
        ],
        'ohCrumbs' => [
            ['label' => 'ร้านค้า', 'href' => route('storefront.index')],
            ['label' => 'ร้านค้าทางการ', 'href' => null],
        ],
        'ohSearch' => $ofSearch,
        'ohAction' => route('official-shop.index'),
    ])

    @if($categories && $categories->count() > 0)
        <section class="sf-wrap" style="padding-top:16px;">
            <div class="sf-scroll">
                <a href="{{ route('official-shop.index') }}" class="sf-chip is-on"><i class="fas fa-border-all"></i> ทั้งหมด</a>
                @foreach($categories as $cat)
                    <a href="{{ route('official-shop.category', $cat->slug) }}" class="sf-chip">{{ $cat->name }}</a>
                @endforeach
            </div>
        </section>
    @endif

    @if($featuredProducts && $featuredProducts->count() > 0 && ! request()->hasAny(['search', 'brand', 'min_price', 'max_price']))
        <section class="sf-wrap sf-section">
            <div class="sf-section-h">
                <div>
                    <div class="sf-kicker"><i class="fas fa-crown"></i> FEATURED</div>
                    <h2 class="sf-title">สินค้าแนะนำจากร้านทางการ</h2>
                </div>
                <a href="{{ route('official-shop.featured') }}" class="sf-btn3d" style="min-height:44px;">ดูทั้งหมด <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="sf-scroll">
                @foreach($featuredProducts as $fp)
                    <div style="width:180px; flex:none;">
                        <x-theme-v4.product-card :product="$fp" :href="$ofHref($fp)" :favorited="in_array((int) $fp->id, $ofFav, true)" />
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <section class="sf-wrap sf-section" id="products">
        <form method="GET" action="{{ route('official-shop.index') }}" class="tp-card" style="display:flex; flex-wrap:wrap; align-items:flex-end; gap:10px; margin-bottom:16px;">
            @if($ofSearch !== '')
                <input type="hidden" name="search" value="{{ $ofSearch }}">
            @endif
            @if($brands && $brands->count() > 0)
                <label style="display:flex; flex-direction:column; gap:4px; flex:1 1 160px;">
                    <span class="tp-muted" style="font-size:12px; font-weight:600;">ยี่ห้อ</span>
                    <select name="brand" class="tp-input" style="height:44px; padding:0 12px;">
                        <option value="">ทุกยี่ห้อ</option>
                        @foreach($brands as $brand)
                            <option value="{{ $brand }}" @selected($ofBrand === (string) $brand)>{{ $brand }}</option>
                        @endforeach
                    </select>
                </label>
            @endif
            <label style="display:flex; flex-direction:column; gap:4px; flex:1 1 110px;">
                <span class="tp-muted" style="font-size:12px; font-weight:600;">ราคาต่ำสุด</span>
                <input type="number" name="min_price" min="0" step="1" value="{{ $ofMin }}" class="tp-input" style="height:44px;" inputmode="numeric" placeholder="฿">
            </label>
            <label style="display:flex; flex-direction:column; gap:4px; flex:1 1 110px;">
                <span class="tp-muted" style="font-size:12px; font-weight:600;">ราคาสูงสุด</span>
                <input type="number" name="max_price" min="0" step="1" value="{{ $ofMax }}" class="tp-input" style="height:44px;" inputmode="numeric" placeholder="฿">
            </label>
            <label style="display:flex; flex-direction:column; gap:4px; flex:1 1 150px;">
                <span class="tp-muted" style="font-size:12px; font-weight:600;">เรียงตาม</span>
                <select name="sort_by" class="tp-input" style="height:44px; padding:0 12px;">
                    @foreach($ofSorts as $sk => $sl)
                        <option value="{{ $sk }}" @selected($ofSort === $sk)>{{ $sl }}</option>
                    @endforeach
                </select>
            </label>
            <div style="display:flex; gap:8px;">
                <button type="submit" class="tp-btn tp-btn-primary" style="height:44px;"><i class="fas fa-filter"></i> กรอง</button>
                @if(request()->hasAny(['search', 'brand', 'min_price', 'max_price', 'sort_by']))
                    <a href="{{ route('official-shop.index') }}" class="tp-btn" style="height:44px; text-decoration:none;">ล้าง</a>
                @endif
            </div>
        </form>

        <div class="sf-section-h" style="margin-bottom:12px;">
            <h2 class="sf-title" style="font-size:20px;">สินค้าทั้งหมด <span class="tp-muted tp-num" style="font-size:14px; font-weight:600;">({{ number_format($products->total()) }})</span></h2>
        </div>

        @if($products->count() > 0)
            <div class="sf-grid">
                @foreach($products as $product)
                    <x-theme-v4.product-card :product="$product" :href="$ofHref($product)" :favorited="in_array((int) $product->id, $ofFav, true)" />
                @endforeach
            </div>
            @if($products->hasPages())
                <div style="margin-top:20px;">{{ $products->links(view()->exists('vendor.pagination.tp-v4') ? 'vendor.pagination.tp-v4' : null) }}</div>
            @endif
        @else
            <div class="tp-card" style="text-align:center; padding:40px 16px;">
                <div style="font-size:44px;" aria-hidden="true">🔎</div>
                <h3 style="margin:10px 0 6px; font-size:18px; font-weight:800; color:var(--ink);">ไม่พบสินค้าที่ตรงกับตัวกรอง</h3>
                <a href="{{ route('official-shop.index') }}" class="sf-btn3d" style="margin-top:8px;">ดูสินค้าทั้งหมด</a>
            </div>
        @endif
    </section>
</main>

<x-theme-v4.public-footer />
<x-eve.widget surface="storefront" />
@endsection
