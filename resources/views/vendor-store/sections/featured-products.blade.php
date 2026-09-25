{{--
 | สินค้าแนะนำของร้าน — ธีม V4
 | ตัวแปร: $store, $layoutSettings (show_featured_products, featured_title, products_per_row), $featuredProducts, $isPreview
 | ต้องมี <x-theme-v4.shop-kit /> ในหน้าแม่
 --}}
@php
    $vfMin = [2 => '260px', 3 => '220px', 4 => '180px', 5 => '160px', 6 => '140px'][(int) ($layoutSettings->products_per_row ?? 4)] ?? '180px';
    $vfPreview = (bool) ($isPreview ?? false);
@endphp

@if($layoutSettings->show_featured_products && isset($featuredProducts) && $featuredProducts && $featuredProducts->count() > 0)
    <section class="sf-wrap sf-section">
        <div class="sf-section-h">
            <div>
                <div class="sf-kicker" style="color:var(--store-a);"><i class="fas fa-star"></i> FEATURED</div>
                <h2 class="sf-title">{{ $layoutSettings->featured_title ?: 'สินค้าแนะนำ' }}</h2>
            </div>
            @if(! $vfPreview)
                <a href="{{ route('store.show', $store->store_slug) }}#all-products" class="tp-btn" style="text-decoration:none;">ดูทั้งหมด <i class="fas fa-arrow-right"></i></a>
            @endif
        </div>
        <div class="sf-grid" style="--sf-min:{{ $vfMin }};">
            @foreach($featuredProducts as $product)
                <x-theme-v4.product-card :product="$product" :preview="$vfPreview"
                    :href="$vfPreview ? '#' : route('store.product', ['storeSlug' => $store->store_slug, 'productSlug' => $product->slug ?: $product->id])" />
            @endforeach
        </div>
    </section>
@endif
