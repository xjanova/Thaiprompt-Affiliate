{{-- Section: Featured Products (สินค้าแนะนำ) --}}
@if($layoutSettings->show_featured_products && isset($featuredProducts) && $featuredProducts && $featuredProducts->count() > 0)
    @php
        $lc = $layoutSettings->layout_classes;
        $productsPerRow = $layoutSettings->products_per_row ?? 4;
        $gridCols = match($productsPerRow) {
            2 => 'grid-cols-1 sm:grid-cols-2',
            3 => 'grid-cols-1 sm:grid-cols-2 md:grid-cols-3',
            4 => 'grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4',
            5 => 'grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5',
            6 => 'grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6',
            default => 'grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4',
        };
        $productCardStyle = $layoutSettings->product_card_style ?? 'default';
        $isPreview = $isPreview ?? false;
    @endphp

    <section class="{{ $lc['section_spacing'] }}">
        <div class="{{ $lc['container'] }}">
            <div class="flex items-center justify-between mb-6">
                <h2 class="{{ $lc['heading'] }} flex items-center gap-2" style="color: var(--store-primary)">
                    <span>⭐</span>
                    {{ $layoutSettings->featured_title ?? 'สินค้าแนะนำ' }}
                </h2>
                @if(!$isPreview)
                    <a href="{{ route('store.show', $store->store_slug) }}"
                       class="store-button text-white px-4 py-2 {{ $lc['button'] }} text-sm transition hover:shadow-lg">
                        ดูทั้งหมด →
                    </a>
                @endif
            </div>

            <div class="grid {{ $gridCols }} gap-4 md:gap-6">
                @foreach($featuredProducts as $product)
                    <a href="{{ $isPreview ? '#' : route('store.product', ['storeSlug' => $store->store_slug, 'productSlug' => $product->slug ?? '#']) }}"
                       class="product-card-{{ $productCardStyle }} block group {{ $lc['card_hover'] }}">
                        {{-- Product Image --}}
                        <div class="aspect-square relative overflow-hidden">
                            @if($product->primary_image_url ?? null)
                                <img src="{{ $product->primary_image_url }}"
                                     alt="{{ $product->name }}"
                                     class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                            @else
                                <div class="w-full h-full bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center text-6xl text-gray-300">
                                    📦
                                </div>
                            @endif
                            @if(($product->discount_percent ?? 0) > 0)
                                <div class="absolute top-2 left-2 store-accent-bg text-white text-xs font-bold px-2 py-1 rounded">
                                    -{{ $product->discount_percent }}%
                                </div>
                            @endif
                        </div>
                        {{-- Product Info --}}
                        <div class="p-4">
                            <h3 class="font-semibold text-gray-800 dark:text-gray-200 line-clamp-2 mb-2 group-hover:store-primary-text transition">
                                {{ $product->name }}
                            </h3>
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-lg font-bold" style="color: var(--store-primary)">
                                    ฿{{ number_format($product->sale_price ?? $product->price ?? 0) }}
                                </span>
                                @if(($product->sale_price ?? null) && ($product->price ?? 0) > ($product->sale_price ?? 0))
                                    <span class="text-sm text-gray-400 line-through">
                                        ฿{{ number_format($product->price) }}
                                    </span>
                                @endif
                            </div>
                            @if(($product->rating_average ?? 0) > 0 || ($product->sales_count ?? 0) > 0)
                                <div class="flex items-center gap-1 text-sm text-gray-500 dark:text-gray-400">
                                    @if(($product->rating_average ?? 0) > 0)
                                        <span class="text-yellow-400">★</span>
                                        <span>{{ number_format($product->rating_average, 1) }}</span>
                                        <span class="text-gray-300">|</span>
                                    @endif
                                    <span>ขายแล้ว {{ $product->sales_count ?? 0 }}</span>
                                </div>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif
