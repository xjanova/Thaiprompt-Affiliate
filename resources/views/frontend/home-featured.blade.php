<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $siteInfo['name'] }} - หน้าแรก</title>

    <!-- Tailwind CSS -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700;800&display=swap');

        * {
            font-family: 'Kanit', sans-serif;
        }

        body {
            background: #f8fafc;
        }

        /* Simple animations */
        .card-hover {
            transition: all 0.3s ease;
        }

        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
    </style>
</head>
<body>

    <!-- Simple Header -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center justify-between">
                <!-- Logo -->
                <div class="flex items-center">
                    <img src="{{ $siteLogo }}" alt="{{ $siteInfo['name'] }}" class="h-10 w-auto">
                </div>

                <!-- Back Button -->
                <a href="javascript:history.back()" class="inline-flex items-center px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    กลับ
                </a>
            </div>
        </div>
    </header>

    <!-- Simple Hero Banner -->
    <section class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-3xl md:text-4xl font-bold mb-3">{{ $siteInfo['name'] }}</h1>
            <p class="text-lg md:text-xl mb-4">{{ $siteInfo['tagline'] }}</p>
            <div class="flex flex-wrap gap-3 justify-center">
                <a href="{{ route('user.dashboard') }}" class="px-6 py-2 bg-white text-purple-600 font-semibold rounded-lg hover:bg-gray-100 transition-colors">
                    เริ่มต้นใช้งาน
                </a>
                <a href="#featured-stores" class="px-6 py-2 bg-purple-700 text-white font-semibold rounded-lg hover:bg-purple-800 transition-colors">
                    ดูร้านค้า
                </a>
            </div>
        </div>
    </section>

    <!-- Featured Stores Section -->
    <section id="featured-stores" class="py-12 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-8">
                <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">ร้านค้าแนะนำ</h2>
                <p class="text-gray-600">ร้านค้าคุณภาพที่ได้รับการรับรอง</p>
            </div>

            @if($featuredStores->count() > 0)
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                    @foreach($featuredStores as $store)
                        <a href="{{ route('vendor.store.show', $store->store_slug) }}" class="card-hover bg-white border border-gray-200 rounded-lg p-4 flex flex-col items-center">
                            <!-- Store Logo -->
                            <div class="w-16 h-16 rounded-full overflow-hidden mb-3 border-2 border-gray-200">
                                @if($store->store_logo)
                                    <img src="{{ asset($store->store_logo) }}" alt="{{ $store->store_name }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full bg-gradient-to-br from-purple-500 to-indigo-500 flex items-center justify-center text-white text-xl font-bold">
                                        {{ substr($store->store_name, 0, 1) }}
                                    </div>
                                @endif
                            </div>

                            <!-- Store Name -->
                            <h3 class="text-sm font-semibold text-gray-900 mb-1 text-center line-clamp-1 w-full">{{ $store->store_name }}</h3>

                            <!-- Rating -->
                            <div class="flex items-center mb-2">
                                @for($i = 1; $i <= 5; $i++)
                                    <svg class="w-3 h-3 {{ $i <= $store->rating_average ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                @endfor
                                <span class="ml-1 text-xs text-gray-600">({{ number_format($store->rating_average, 1) }})</span>
                            </div>

                            <!-- Products Count -->
                            <p class="text-xs text-gray-500">{{ number_format($store->total_products) }} สินค้า</p>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <div class="text-5xl mb-3">🏪</div>
                    <p class="text-gray-500">ยังไม่มีร้านค้าแนะนำในขณะนี้</p>
                </div>
            @endif

            @if($featuredStores->count() > 0)
                <div class="text-center mt-8">
                    <a href="#new-products" class="inline-flex items-center px-6 py-3 bg-purple-600 text-white font-semibold rounded-lg hover:bg-purple-700 transition-colors">
                        ดูสินค้ามาใหม่
                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </a>
                </div>
            @endif
        </div>
    </section>

    <!-- New Products Section -->
    <section id="new-products" class="py-12 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-8">
                <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">สินค้ามาใหม่</h2>
                <p class="text-gray-600">สินค้าล่าสุดที่เพิ่งเข้ามาในระบบ</p>
            </div>

            @if($newProducts->count() > 0)
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    @foreach($newProducts as $product)
                        <a href="{{ route('products.show', $product->slug) }}" class="card-hover bg-white border border-gray-200 rounded-lg overflow-hidden">
                            <!-- Product Image -->
                            <div class="relative h-32 bg-gray-100">
                                @if($product->main_image_url)
                                    <img src="{{ asset($product->main_image_url) }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full bg-gradient-to-br from-purple-300 to-indigo-300 flex items-center justify-center">
                                        <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                    </div>
                                @endif

                                <!-- New Badge -->
                                <div class="absolute top-2 right-2 bg-red-500 text-white px-2 py-0.5 rounded-full text-xs font-bold">
                                    ใหม่
                                </div>
                            </div>

                            <!-- Product Info -->
                            <div class="p-3">
                                <h3 class="text-xs font-semibold text-gray-900 mb-1 line-clamp-2 h-8">{{ $product->name }}</h3>

                                <!-- Price -->
                                <div class="flex items-baseline gap-1">
                                    <span class="text-sm font-bold text-purple-600">฿{{ number_format($product->price, 0) }}</span>
                                    @if($product->compare_at_price && $product->compare_at_price > $product->price)
                                        <span class="text-xs text-gray-400 line-through">฿{{ number_format($product->compare_at_price, 0) }}</span>
                                    @endif
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <div class="text-5xl mb-3">📦</div>
                    <p class="text-gray-500">ยังไม่มีสินค้ามาใหม่ในขณะนี้</p>
                </div>
            @endif
        </div>
    </section>

    <!-- Simple Footer -->
    <footer class="bg-gray-900 text-white py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <img src="{{ $siteLogo }}" alt="{{ $siteInfo['name'] }}" class="h-10 w-auto mx-auto mb-3 filter brightness-0 invert opacity-80">
            <p class="text-sm text-gray-400 mb-1">{{ $siteInfo['name'] }}</p>
            <p class="text-xs text-gray-500">{{ $siteInfo['tagline'] }}</p>
            <div class="mt-4 text-xs text-gray-600">
                &copy; {{ date('Y') }} {{ $siteInfo['name'] }}. All rights reserved.
            </div>
        </div>
    </footer>

</body>
</html>
