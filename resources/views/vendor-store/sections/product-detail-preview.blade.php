{{--
    Section: Product Detail Preview (ตัวอย่างหน้ารายละเอียดสินค้า)
    ใช้ใน layout-preview เพื่อให้ seller เห็นว่าธีมจะแสดงผลหน้าสินค้าอย่างไร
    ต้องตรงกับ vendor-store/product-show.blade.php ทุกประการ
--}}
@php
    $lc = $layoutSettings->layout_classes;
    $isPreview = true;

    // Dummy product สำหรับ preview
    $demoProduct = (object)[
        'name' => 'เสื้อยืดคอกลม Premium Collection',
        'slug' => 'demo-product',
        'price' => 1290,
        'compare_at_price' => 1890,
        'short_description' => 'เสื้อยืดคอกลมเนื้อผ้า Cotton 100% นุ่มสบาย ระบายอากาศได้ดี เหมาะสำหรับทุกโอกาส ทั้งใส่ลำลองและออกกำลังกาย',
        'stock_status' => 'in_stock',
        'stock_quantity' => 15,
        'sku' => 'TSH-2024-001',
        'brand' => 'ThaiPrompt',
        'is_featured' => true,
        'rating_average' => 4.7,
        'rating_count' => 128,
        'sales_count' => 542,
        'view_count' => 3840,
        'main_image_url' => null,
        'category' => (object)['name' => 'เสื้อผ้า', 'slug' => 'clothing'],
        'tags' => ['เสื้อยืด', 'Cotton', 'Premium'],
        'weight' => 0.3,
        'dimensions' => '30 x 40 x 5 ซม.',
    ];

    $discount = round((($demoProduct->compare_at_price - $demoProduct->price) / $demoProduct->compare_at_price) * 100);

    // Dummy related products
    $demoRelated = collect([
        (object)['name' => 'เสื้อยืดคอวี Slim Fit', 'price' => 990, 'compare_at_price' => 1490, 'primary_image_url' => null, 'slug' => 'demo-1'],
        (object)['name' => 'กางเกงขาสั้น Cool Breeze', 'price' => 790, 'compare_at_price' => null, 'primary_image_url' => null, 'slug' => 'demo-2'],
        (object)['name' => 'หมวกแก๊ป Minimal Style', 'price' => 490, 'compare_at_price' => 690, 'primary_image_url' => null, 'slug' => 'demo-3'],
        (object)['name' => 'สร้อยคอ Silver Chain', 'price' => 1590, 'compare_at_price' => null, 'primary_image_url' => null, 'slug' => 'demo-4'],
    ]);

    $productCardStyle = $layoutSettings->product_card_style ?? 'default';
@endphp

{{-- Divider --}}
<div class="{{ $lc['container'] }} py-4">
    <div class="flex items-center gap-4">
        <div class="flex-1 h-px bg-gradient-to-r from-transparent via-gray-300 dark:via-gray-600 to-transparent"></div>
        <span class="px-6 py-2.5 store-button text-white font-bold text-sm {{ $lc['button'] }} shadow-lg flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
            ตัวอย่างหน้ารายละเอียดสินค้า
        </span>
        <div class="flex-1 h-px bg-gradient-to-r from-transparent via-gray-300 dark:via-gray-600 to-transparent"></div>
    </div>
</div>

{{-- Store Mini Header --}}
<div class="text-white py-3" style="background: linear-gradient(135deg, var(--store-primary), var(--store-secondary))">
    <div class="{{ $lc['container'] }}">
        <div class="flex items-center gap-3">
            @if($store->store_logo ?? null)
                <img src="{{ $store->logo_url ?? '' }}" alt="{{ $store->store_name ?? 'ร้านค้า' }}" class="w-8 h-8 rounded-full object-contain bg-white/20 p-0.5">
            @else
                <span class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center text-sm">🏪</span>
            @endif
            <span class="font-bold text-white">{{ $store->store_name ?? 'ร้านค้าตัวอย่าง' }}</span>
            <span class="text-white/60 text-sm">›</span>
            <span class="text-white/80 text-sm line-clamp-1">{{ $demoProduct->name }}</span>
        </div>
    </div>
</div>

<div class="{{ $lc['container'] }} py-6">

    {{-- Breadcrumb --}}
    <nav class="mb-6">
        <ol class="flex items-center space-x-2 text-sm flex-wrap">
            <li><span class="text-gray-500 dark:text-gray-400 flex items-center gap-1"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg> {{ $store->store_name ?? 'ร้านค้า' }}</span></li>
            <li><span class="text-gray-400">/</span></li>
            <li><span class="text-gray-500 dark:text-gray-400">{{ $demoProduct->category->name }}</span></li>
            <li><span class="text-gray-400">/</span></li>
            <li class="text-gray-700 dark:text-gray-200 font-medium">{{ Str::limit($demoProduct->name, 50) }}</li>
        </ol>
    </nav>

    {{-- Main Product Section --}}
    <div class="bg-white dark:bg-gray-800 {{ $lc['border_radius'] }} shadow-xl overflow-hidden mb-8 border border-gray-100 dark:border-gray-700">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 p-6 lg:p-10">

            {{-- Product Image (Placeholder) --}}
            <div class="space-y-4">
                <div class="relative aspect-square bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800 {{ $lc['border_radius'] }} overflow-hidden">
                    <div class="w-full h-full flex items-center justify-center">
                        <div class="text-center">
                            <div class="text-8xl mb-4 opacity-30">👕</div>
                            <span class="text-gray-400 dark:text-gray-500 text-sm">ตัวอย่างรูปสินค้า</span>
                        </div>
                    </div>
                    {{-- Featured badge --}}
                    <div class="absolute top-4 left-4 flex flex-col gap-2 z-10">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 store-badge-featured text-white text-sm font-bold {{ $lc['border_radius'] }} shadow-lg">
                            ⭐ สินค้าแนะนำ
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 store-accent-bg text-white text-sm font-bold {{ $lc['border_radius'] }} shadow-lg">
                            ลด {{ $discount }}%
                        </span>
                    </div>
                </div>

                {{-- Thumbnail placeholders --}}
                <div class="grid grid-cols-5 gap-3">
                    @for($t = 0; $t < 5; $t++)
                    <div class="aspect-square {{ $lc['border_radius'] }} overflow-hidden border-2 {{ $t === 0 ? 'store-thumbnail-active' : 'border-gray-300 dark:border-gray-600' }} bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-2xl opacity-40">
                        👕
                    </div>
                    @endfor
                </div>
            </div>

            {{-- Product Info --}}
            <div class="space-y-6">

                {{-- Category & Brand --}}
                <div class="flex items-center gap-3 flex-wrap">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-bold {{ $lc['border_radius'] }} border"
                          style="background: color-mix(in srgb, var(--store-primary) 8%, white); color: var(--store-primary); border-color: color-mix(in srgb, var(--store-primary) 30%, white);">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/></svg>
                        {{ $demoProduct->category->name }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-semibold {{ $lc['border_radius'] }}">
                        {{ $demoProduct->brand }}
                    </span>
                </div>

                {{-- Tags --}}
                <div class="flex flex-wrap gap-2">
                    @foreach($demoProduct->tags as $tag)
                        <span class="inline-flex items-center gap-1 px-3 py-1.5 bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-sm rounded-full border border-gray-200 dark:border-gray-600">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M17.707 9.293a1 1 0 010 1.414l-7 7a1 1 0 01-1.414 0l-7-7A.997.997 0 012 10V5a3 3 0 013-3h5c.256 0 .512.098.707.293l7 7zM5 6a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
                            {{ $tag }}
                        </span>
                    @endforeach
                </div>

                {{-- Product Name --}}
                <h1 class="text-3xl lg:text-4xl font-black leading-tight" style="color: var(--store-text)">
                    {{ $demoProduct->name }}
                </h1>

                {{-- Rating & Sales --}}
                <div class="flex items-center gap-4 flex-wrap pb-6 border-b-2 border-gray-100 dark:border-gray-700">
                    <div class="flex items-center gap-2 bg-amber-50 dark:bg-amber-900/30 px-4 py-2 {{ $lc['border_radius'] }} border border-amber-100 dark:border-amber-800">
                        <div class="flex">
                            @for($i = 0; $i < 5; $i++)
                                <svg class="w-5 h-5 {{ $i < 4 ? 'text-amber-400' : ($i == 4 ? 'text-amber-400' : 'text-gray-300') }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            @endfor
                        </div>
                        <span class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ number_format($demoProduct->rating_average, 1) }}</span>
                        <span class="text-gray-600 dark:text-gray-400">({{ number_format($demoProduct->rating_count) }})</span>
                    </div>
                    <div class="h-6 w-px bg-gray-300 dark:bg-gray-600"></div>
                    <div class="flex items-center gap-2">
                        <span class="text-gray-600 dark:text-gray-400">ขายแล้ว</span>
                        <span class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($demoProduct->sales_count) }}</span>
                    </div>
                    <div class="h-6 w-px bg-gray-300 dark:bg-gray-600"></div>
                    <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/></svg>
                        <span>{{ number_format($demoProduct->view_count) }} ครั้ง</span>
                    </div>
                </div>

                {{-- Price Section --}}
                <div class="store-price-box p-6 {{ $lc['border_radius'] }} border-2">
                    <div class="space-y-2">
                        <div class="text-sm font-semibold text-gray-600 dark:text-gray-400">ราคาปกติ</div>
                        <div class="text-2xl text-gray-400 line-through font-bold">
                            ฿{{ number_format($demoProduct->compare_at_price, 2) }}
                        </div>
                        <span class="inline-block px-3 py-1 store-accent-bg text-white font-bold {{ $lc['border_radius'] }} text-sm">
                            ประหยัด ฿{{ number_format($demoProduct->compare_at_price - $demoProduct->price, 2) }}
                        </span>
                    </div>
                    <div class="mt-4 pt-4 border-t-2" style="border-color: color-mix(in srgb, var(--store-primary) 25%, white)">
                        <div class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ราคาพิเศษ</div>
                        <div class="text-5xl font-black store-price-gradient">
                            ฿{{ number_format($demoProduct->price, 2) }}
                        </div>
                    </div>

                    {{-- Cashback demo --}}
                    <div class="mt-4 flex items-center gap-2 text-amber-700 dark:text-amber-300 font-bold bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/30 dark:to-orange-900/30 px-4 py-3 {{ $lc['border_radius'] }} border-2 border-amber-300 dark:border-amber-700 shadow-md">
                        <svg class="w-6 h-6 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                        </svg>
                        <div>
                            <div class="text-lg">รับ Cashback คืน <span class="text-xl">฿129.00</span></div>
                            <div class="text-xs text-amber-600 dark:text-amber-400 mt-0.5">(10% ของราคาสินค้า)</div>
                        </div>
                    </div>
                </div>

                {{-- Short Description --}}
                <div class="prose prose-sm max-w-none text-gray-700 dark:text-gray-300 leading-relaxed">
                    {{ $demoProduct->short_description }}
                </div>

                {{-- Stock Status --}}
                <div class="flex items-center gap-3 text-sm font-semibold flex-wrap">
                    <div class="flex items-center gap-2 text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/30 px-4 py-2 {{ $lc['border_radius'] }} border border-emerald-200 dark:border-emerald-800">
                        <span class="relative flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                        </span>
                        มีสินค้าพร้อมส่ง
                    </div>
                    <span class="text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-4 py-2 {{ $lc['border_radius'] }}">
                        SKU: <span class="font-mono font-bold text-gray-900 dark:text-gray-100">{{ $demoProduct->sku }}</span>
                    </span>
                </div>

                {{-- Action Buttons --}}
                <div class="space-y-4 pt-6">
                    {{-- Quantity --}}
                    <div class="flex items-center gap-4">
                        <span class="text-gray-900 dark:text-gray-100 font-bold text-lg">จำนวน:</span>
                        <div class="flex items-center border-2 border-gray-300 dark:border-gray-600 {{ $lc['border_radius'] }} overflow-hidden bg-white dark:bg-gray-700">
                            <span class="px-5 py-3 font-bold text-lg text-gray-900 dark:text-gray-100 cursor-default">−</span>
                            <span class="w-20 text-center border-x-2 border-gray-300 dark:border-gray-600 py-3 font-bold text-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">1</span>
                            <span class="px-5 py-3 font-bold text-lg text-gray-900 dark:text-gray-100 cursor-default">+</span>
                        </div>
                    </div>

                    {{-- Add to Cart --}}
                    <div class="flex gap-3">
                        <span class="flex-1 px-8 py-4 store-button text-white font-bold text-lg {{ $lc['button'] }} shadow-lg flex items-center justify-center gap-2 cursor-default">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            เพิ่มลงตะกร้า
                        </span>
                    </div>

                    {{-- Buy Now --}}
                    <span class="w-full px-8 py-4 text-white font-bold text-lg {{ $lc['button'] }} shadow-lg flex items-center justify-center gap-2 cursor-default"
                          style="background: linear-gradient(135deg, var(--store-accent), color-mix(in srgb, var(--store-accent) 80%, #ff4400))">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        ซื้อทันที
                    </span>

                    {{-- Wishlist & Share --}}
                    <div class="grid grid-cols-2 gap-3">
                        <span class="flex items-center justify-center gap-2 px-6 py-3 border-2 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-bold {{ $lc['border_radius'] }} cursor-default">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                            บันทึก
                        </span>
                        <span class="flex items-center justify-center gap-2 px-6 py-3 border-2 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-bold {{ $lc['border_radius'] }} cursor-default">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                            แชร์
                        </span>
                    </div>
                </div>

                {{-- Trust Badges --}}
                <div class="grid grid-cols-3 gap-3 pt-6">
                    <div class="flex flex-col items-center gap-2 p-3 {{ $lc['border_radius'] }} border border-gray-100 dark:border-gray-700" style="background: color-mix(in srgb, var(--store-primary) 5%, white)">
                        <svg class="w-8 h-8" style="color: var(--store-primary)" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        <span class="text-xs font-bold text-gray-900 dark:text-gray-100 text-center">ของแท้ 100%</span>
                    </div>
                    <div class="flex flex-col items-center gap-2 p-3 {{ $lc['border_radius'] }} border border-gray-100 dark:border-gray-700 bg-emerald-50 dark:bg-emerald-900/20">
                        <svg class="w-8 h-8 text-emerald-600 dark:text-emerald-400" fill="currentColor" viewBox="0 0 20 20"><path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/><path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"/></svg>
                        <span class="text-xs font-bold text-gray-900 dark:text-gray-100 text-center">จัดส่งรวดเร็ว</span>
                    </div>
                    <div class="flex flex-col items-center gap-2 p-3 {{ $lc['border_radius'] }} border border-gray-100 dark:border-gray-700" style="background: color-mix(in srgb, var(--store-secondary) 5%, white)">
                        <svg class="w-8 h-8" style="color: var(--store-secondary)" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                        <span class="text-xs font-bold text-gray-900 dark:text-gray-100 text-center">คืนเงิน 100%</span>
                    </div>
                </div>

                {{-- Seller/Store Info --}}
                <div class="bg-gray-50 dark:bg-gray-800 p-5 {{ $lc['border_radius'] }} border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 store-button {{ $lc['border_radius'] }} flex items-center justify-center text-white font-bold text-xl flex-shrink-0">
                                {{ mb_substr($store->store_name ?? 'ร', 0, 1) }}
                            </div>
                            <div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 font-semibold">ขายโดย</div>
                                <div class="font-bold text-gray-900 dark:text-gray-100 text-lg">{{ $store->store_name ?? 'ร้านค้าตัวอย่าง' }}</div>
                            </div>
                        </div>
                        <span class="px-4 py-2 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 font-bold {{ $lc['border_radius'] }} cursor-default" style="color: var(--store-primary);">
                            ดูร้านค้า
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Product Detail Tabs (Preview) --}}
    <div class="bg-white dark:bg-gray-800 {{ $lc['border_radius'] }} shadow-xl overflow-hidden mb-8 border border-gray-100 dark:border-gray-700">
        <div class="border-b-2 border-gray-100 dark:border-gray-700">
            <div class="flex overflow-x-auto">
                <span class="px-8 py-4 font-bold border-b-4 whitespace-nowrap store-tab-active flex items-center gap-2">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/></svg>
                    รายละเอียดสินค้า
                </span>
                <span class="px-8 py-4 font-bold border-b-4 border-transparent whitespace-nowrap text-gray-600 dark:text-gray-400 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    รีวิวจากผู้ซื้อ (128)
                </span>
                <span class="px-8 py-4 font-bold border-b-4 border-transparent whitespace-nowrap text-gray-600 dark:text-gray-400 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/><path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"/></svg>
                    การจัดส่ง
                </span>
            </div>
        </div>
        <div class="p-8">
            <div class="prose prose-lg max-w-none dark:prose-invert">
                <p class="text-gray-700 dark:text-gray-300">{{ $demoProduct->short_description }}</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
                    <div class="p-5 {{ $lc['border_radius'] }} border" style="background: color-mix(in srgb, var(--store-primary) 5%, white); border-color: color-mix(in srgb, var(--store-primary) 20%, white)">
                        <h4 class="font-bold text-gray-900 dark:text-gray-100 mb-2 flex items-center gap-2">
                            <svg class="w-5 h-5" style="color: var(--store-primary)" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            ยี่ห้อ
                        </h4>
                        <p class="text-gray-700 dark:text-gray-300 font-semibold text-lg">{{ $demoProduct->brand }}</p>
                    </div>
                    <div class="p-5 bg-emerald-50 dark:bg-emerald-900/20 {{ $lc['border_radius'] }} border border-emerald-200 dark:border-emerald-800">
                        <h4 class="font-bold text-gray-900 dark:text-gray-100 mb-2 flex items-center gap-2">
                            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
                            ข้อมูลการจัดส่ง
                        </h4>
                        <p class="text-gray-700 dark:text-gray-300">น้ำหนัก: <span class="font-semibold">{{ $demoProduct->weight }} กก.</span></p>
                        <p class="text-gray-700 dark:text-gray-300">ขนาด: <span class="font-semibold">{{ $demoProduct->dimensions }}</span></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Related Products --}}
    @php
        $productsPerRow = $layoutSettings->products_per_row ?? 4;
        $gridCols = match($productsPerRow) {
            2 => 'grid-cols-1 sm:grid-cols-2',
            3 => 'grid-cols-1 sm:grid-cols-2 md:grid-cols-3',
            5 => 'grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5',
            6 => 'grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6',
            default => 'grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4',
        };
    @endphp
    <div class="mb-8">
        <div class="flex items-center justify-between mb-6">
            <h2 class="{{ $lc['heading'] }} flex items-center gap-3" style="color: var(--store-primary)">
                สินค้าที่เกี่ยวข้อง
            </h2>
        </div>
        <div class="grid {{ $gridCols }} gap-4 md:gap-6">
            @foreach($demoRelated as $relProduct)
                <span class="product-card-{{ $productCardStyle }} block group {{ $lc['card_hover'] }} cursor-default">
                    <div class="aspect-square relative overflow-hidden">
                        <div class="w-full h-full bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 flex items-center justify-center text-6xl text-gray-300 opacity-40">📦</div>
                    </div>
                    <div class="p-4">
                        <h3 class="font-semibold text-gray-800 dark:text-gray-200 line-clamp-2 mb-2">{{ $relProduct->name }}</h3>
                        <div class="flex items-center gap-2">
                            <span class="text-lg font-bold" style="color: var(--store-primary)">฿{{ number_format($relProduct->price, 0) }}</span>
                            @if($relProduct->compare_at_price && $relProduct->compare_at_price > $relProduct->price)
                                <span class="text-sm text-gray-400 line-through">฿{{ number_format($relProduct->compare_at_price, 0) }}</span>
                            @endif
                        </div>
                    </div>
                </span>
            @endforeach
        </div>
    </div>

</div>
