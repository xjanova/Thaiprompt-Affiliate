{{-- Section: All Products + Sidebar (สินค้าทั้งหมด + ตัวกรอง) --}}
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
        <div class="flex flex-col lg:flex-row gap-6">
            {{-- Filters Sidebar --}}
            @if($layoutSettings->show_sidebar ?? true)
                <aside class="lg:w-80 flex-shrink-0" style="order: {{ $layoutSettings->sidebar_position === 'right' ? '2' : '1' }}">
                    <div class="{{ $lc['sidebar_card'] }}">
                        {{-- Filters Header --}}
                        <div class="text-white px-6 py-4" style="background: linear-gradient(135deg, var(--store-primary), var(--store-secondary))">
                            <h2 class="text-lg font-bold flex items-center gap-2">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd"/>
                                </svg>
                                ค้นหา & กรอง
                            </h2>
                        </div>

                        @if(!$isPreview)
                            <form method="GET" action="{{ route('store.show', $store->store_slug) }}" class="p-6 space-y-6">
                                {{-- Search --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ค้นหาสินค้า</label>
                                    <input type="text" name="search" value="{{ request('search') }}"
                                           placeholder="พิมพ์ชื่อสินค้า..."
                                           class="w-full px-4 py-3 {{ $lc['border_radius'] }} border-2 border-gray-200 dark:border-gray-600 dark:bg-gray-700 focus:ring-2 focus:ring-opacity-50 transition"
                                           style="--tw-ring-color: var(--store-primary)">
                                </div>

                                {{-- Categories --}}
                                @if(isset($categories) && $categories->count() > 0)
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">หมวดหมู่</label>
                                    <select name="category" class="w-full px-4 py-3 {{ $lc['border_radius'] }} border-2 border-gray-200 dark:border-gray-600 dark:bg-gray-700 focus:ring-2 focus:ring-opacity-50 transition">
                                        <option value="">ทุกหมวดหมู่</option>
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat->slug }}" {{ request('category') == $cat->slug ? 'selected' : '' }}>
                                                {{ $cat->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @endif

                                {{-- Sort --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เรียงตาม</label>
                                    <select name="sort" class="w-full px-4 py-3 {{ $lc['border_radius'] }} border-2 border-gray-200 dark:border-gray-600 dark:bg-gray-700 focus:ring-2 focus:ring-opacity-50 transition">
                                        <option value="latest" {{ request('sort') == 'latest' ? 'selected' : '' }}>ล่าสุด</option>
                                        <option value="price_low" {{ request('sort') == 'price_low' ? 'selected' : '' }}>ราคาต่ำ-สูง</option>
                                        <option value="price_high" {{ request('sort') == 'price_high' ? 'selected' : '' }}>ราคาสูง-ต่ำ</option>
                                        <option value="popular" {{ request('sort') == 'popular' ? 'selected' : '' }}>ยอดนิยม</option>
                                    </select>
                                </div>

                                {{-- Submit --}}
                                <button type="submit" class="store-button w-full text-white font-semibold py-3 {{ $lc['button'] }} transition hover:shadow-lg">
                                    ค้นหา
                                </button>
                            </form>
                        @else
                            {{-- Preview mode: แสดง form แบบ static --}}
                            <div class="p-6 space-y-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ค้นหาสินค้า</label>
                                    <input type="text" placeholder="พิมพ์ชื่อสินค้า..." disabled
                                           class="w-full px-4 py-3 {{ $lc['border_radius'] }} border-2 border-gray-200 dark:border-gray-600 dark:bg-gray-700">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">หมวดหมู่</label>
                                    <select disabled class="w-full px-4 py-3 {{ $lc['border_radius'] }} border-2 border-gray-200 dark:border-gray-600 dark:bg-gray-700">
                                        <option>ทุกหมวดหมู่</option>
                                    </select>
                                </div>
                                <button type="button" class="store-button w-full text-white font-semibold py-3 {{ $lc['button'] }} cursor-not-allowed opacity-80">
                                    ค้นหา
                                </button>
                            </div>
                        @endif
                    </div>
                </aside>
            @endif

            {{-- Products Grid --}}
            <div class="flex-1" style="order: {{ $layoutSettings->sidebar_position === 'right' ? '1' : '2' }}">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="{{ $lc['heading'] }} text-gray-800 dark:text-gray-200">
                        🛍️ สินค้าทั้งหมด
                        @if(isset($products) && !$isPreview)
                            <span class="text-gray-500 text-base font-normal">({{ $products->total() }} รายการ)</span>
                        @endif
                    </h2>
                </div>

                @if(isset($products) && $products->count() > 0)
                    <div class="grid {{ $gridCols }} gap-4 md:gap-6">
                        @foreach($products as $product)
                            <a href="{{ $isPreview ? '#' : route('product.show', $product->slug ?? '#') }}"
                               class="product-card-{{ $productCardStyle }} block group {{ $lc['card_hover'] }}">
                                {{-- Product Image --}}
                                <div class="aspect-square relative overflow-hidden">
                                    @if($product->primary_image_url ?? null)
                                        <img src="{{ $product->primary_image_url }}"
                                             alt="{{ $product->name }}"
                                             class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                                             loading="lazy">
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

                    {{-- Pagination --}}
                    @if(!$isPreview && $products->hasPages())
                        <div class="mt-8">
                            {{ $products->links() }}
                        </div>
                    @endif
                @else
                    <div class="text-center py-16 {{ $lc['card'] }}">
                        <div class="text-6xl mb-4">🔍</div>
                        <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-2">ไม่พบสินค้า</h3>
                        <p class="text-gray-500 dark:text-gray-400">ลองค้นหาด้วยคำค้นอื่น หรือเลือกหมวดหมู่อื่น</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
