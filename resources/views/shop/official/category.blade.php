{{--
 | สินค้าร้านค้าทางการตามหมวด — ธีม V4 (frontend-v4)
 | ข้อมูลจาก OfficialShopController@category: $category, $products (paginator 24), $stats [official]
 | ตัวกรอง GET: search, sort_by (newest|popular|price_low|price_high|rating)
 --}}
@extends('layouts.frontend-v4')

@section('title', $category->name.' - ร้านค้าทางการ')
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags((string) ($category->description ?: 'สินค้าหมวด '.$category->name.' จากร้านค้าทางการ ของแท้ 100%')), 160))

@php
    $ocSearch = is_scalar(request('search')) ? (string) request('search') : '';
    $ocSort = is_scalar(request('sort_by')) ? (string) request('sort_by') : 'newest';
    $ocSorts = ['newest' => 'ใหม่ล่าสุด', 'popular' => 'ยอดนิยม', 'price_low' => 'ราคาต่ำ → สูง', 'price_high' => 'ราคาสูง → ต่ำ', 'rating' => 'คะแนนสูงสุด'];
    $ocFav = [];
    if (auth()->check()) {
        try {
            $ocFav = \App\Models\ProductFavorite::where('user_id', auth()->id())
                ->whereIn('product_id', collect($products->items())->pluck('id')->all())
                ->pluck('product_id')->map(fn ($id) => (int) $id)->all();
        } catch (\Throwable $e) {
            $ocFav = [];
        }
    }
@endphp

@section('content')
<x-theme-v4.shop-kit />
<x-theme-v4.public-header active="shop" :search="true" />

<main style="flex:1; padding-bottom:40px;">
    @include('shop.official._hero', [
        'ohTitle' => $category->name,
        'ohSubtitle' => $category->description ? \Illuminate\Support\Str::limit(strip_tags((string) $category->description), 180) : 'สินค้าหมวด '.$category->name.' จากร้านค้าทางการ ของแท้ทุกชิ้น',
        'ohStats' => [['value' => $stats['official'] ?? $products->total(), 'label' => 'สินค้าในหมวด']],
        'ohCrumbs' => [
            ['label' => 'ร้านค้า', 'href' => route('storefront.index')],
            ['label' => 'ร้านค้าทางการ', 'href' => route('official-shop.index')],
            ['label' => $category->name, 'href' => null],
        ],
        'ohSearch' => $ocSearch,
        'ohAction' => route('official-shop.category', $category->slug),
    ])

    <section class="sf-wrap sf-section" id="products">
        <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px; margin-bottom:14px;">
            <a href="{{ route('official-shop.index') }}" class="tp-btn" style="text-decoration:none; height:44px;"><i class="fas fa-arrow-left"></i> ร้านค้าทางการทั้งหมด</a>
            <form method="GET" action="{{ route('official-shop.category', $category->slug) }}" style="display:flex; align-items:center; gap:6px;">
                @if($ocSearch !== '')
                    <input type="hidden" name="search" value="{{ $ocSearch }}">
                @endif
                <label for="oc-sort" class="tp-muted" style="font-size:12.5px; font-weight:600;">เรียง</label>
                <select id="oc-sort" name="sort_by" class="tp-input" style="height:44px; width:auto; padding:0 12px;" onchange="this.form.submit()">
                    @foreach($ocSorts as $sk => $sl)
                        <option value="{{ $sk }}" @selected($ocSort === $sk)>{{ $sl }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        @if($products->count() > 0)
            <div class="sf-grid">
                @foreach($products as $product)
                    <x-theme-v4.product-card :product="$product" :href="route('official-shop.show', $product->slug ?: $product->id)" :favorited="in_array((int) $product->id, $ocFav, true)" />
                @endforeach
            </div>
            @if($products->hasPages())
                <div style="margin-top:20px;">{{ $products->links(view()->exists('vendor.pagination.tp-v4') ? 'vendor.pagination.tp-v4' : null) }}</div>
            @endif
        @else
            <div class="tp-card" style="text-align:center; padding:40px 16px;">
                <div style="font-size:44px;" aria-hidden="true">📦</div>
                <h2 style="margin:10px 0 6px; font-size:18px; font-weight:800; color:var(--ink);">ยังไม่มีสินค้าในหมวดนี้</h2>
                <a href="{{ route('official-shop.index') }}" class="sf-btn3d" style="margin-top:8px;">ดูสินค้าทั้งหมด</a>
            </div>
        @endif
    </section>
</main>

<x-theme-v4.public-footer />
<x-eve.widget surface="storefront" />
@endsection
