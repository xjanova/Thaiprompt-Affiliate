@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb -->
        <nav class="mb-8" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2 text-sm">
                <li>
                    <a href="{{ route('home') }}" class="text-gray-500 hover:text-indigo-600 transition">
                        หน้าแรก
                    </a>
                </li>
                <li class="text-gray-400">/</li>
                <li>
                    <a href="{{ route('shop.index') }}" class="text-gray-500 hover:text-indigo-600 transition">
                        ร้านค้า
                    </a>
                </li>
                @if($product->category)
                <li class="text-gray-400">/</li>
                <li>
                    <a href="{{ route('shop.category', $product->category->slug) }}" class="text-gray-500 hover:text-indigo-600 transition">
                        {{ $product->category->name }}
                    </a>
                </li>
                @endif
                <li class="text-gray-400">/</li>
                <li class="text-gray-700 font-medium">{{ $product->name }}</li>
            </ol>
        </nav>

        <!-- Product Details -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden mb-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 p-8">
                <!-- Product Images -->
                <div class="space-y-4">
                    <!-- Main Image -->
                    <div class="relative aspect-square bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl overflow-hidden">
                        @if($product->main_image_url)
                            <img id="mainImage"
                                 src="{{ $product->main_image_url }}"
                                 alt="{{ $product->name }}"
                                 class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-gray-400">
                                <span class="text-9xl">📦</span>
                            </div>
                        @endif

                        <!-- Badges -->
                        <div class="absolute top-4 left-4 flex flex-col gap-2">
                            @if($product->is_featured)
                                <span class="px-4 py-2 bg-gradient-to-r from-yellow-500 to-orange-500 text-white text-sm font-bold rounded-full shadow-lg">
                                    ⭐ สินค้าแนะนำ
                                </span>
                            @endif

                            @if($product->compare_at_price && $product->compare_at_price > $product->price)
                                @php
                                    $discount = round((($product->compare_at_price - $product->price) / $product->compare_at_price) * 100);
                                @endphp
                                <span class="px-4 py-2 bg-gradient-to-r from-red-500 to-pink-500 text-white text-sm font-bold rounded-full shadow-lg">
                                    ลดราคา {{ $discount }}%
                                </span>
                            @endif
                        </div>

                        <!-- Stock Status -->
                        @if($product->stock_status === 'out_of_stock')
                            <div class="absolute inset-0 bg-black bg-opacity-60 flex items-center justify-center">
                                <span class="px-6 py-3 bg-gray-800 text-white text-xl font-bold rounded-xl shadow-lg">
                                    สินค้าหมด
                                </span>
                            </div>
                        @elseif($product->track_inventory && $product->stock_quantity < $product->low_stock_threshold)
                            <div class="absolute bottom-4 left-4 right-4">
                                <div class="bg-orange-500 text-white text-sm font-semibold py-2 px-4 rounded-lg text-center">
                                    เหลือเพียง {{ $product->stock_quantity }} ชิ้น - รีบสั่งซื้อเลย!
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Thumbnail Images -->
                    @if($product->images && $product->images->count() > 0)
                    <div class="grid grid-cols-5 gap-2">
                        @if($product->main_image_url)
                        <button onclick="changeMainImage('{{ $product->main_image_url }}')"
                                class="aspect-square rounded-lg overflow-hidden border-2 border-indigo-600 hover:border-indigo-800 transition">
                            <img src="{{ $product->main_image_url }}" alt="Main" class="w-full h-full object-cover">
                        </button>
                        @endif

                        @foreach($product->images->take(4) as $image)
                        <button onclick="changeMainImage('{{ $image->url }}')"
                                class="aspect-square rounded-lg overflow-hidden border-2 border-gray-300 hover:border-indigo-600 transition">
                            <img src="{{ $image->url }}" alt="Product Image" class="w-full h-full object-cover">
                        </button>
                        @endforeach
                    </div>
                    @endif
                </div>

                <!-- Product Info -->
                <div class="space-y-6">
                    <!-- Category -->
                    @if($product->category)
                    <div>
                        <a href="{{ route('shop.category', $product->category->slug) }}"
                           class="inline-block px-4 py-2 bg-indigo-50 text-indigo-600 text-sm font-semibold rounded-full hover:bg-indigo-100 transition">
                            {{ $product->category->name }}
                        </a>
                    </div>
                    @endif

                    <!-- Product Name -->
                    <h1 class="text-3xl lg:text-4xl font-bold text-gray-900">
                        {{ $product->name }}
                    </h1>

                    <!-- Rating & Sales -->
                    <div class="flex flex-wrap items-center gap-4">
                        <div class="flex items-center gap-2">
                            @php
                                $rating = $product->rating_average ?? 0;
                                $fullStars = floor($rating);
                                $hasHalfStar = ($rating - $fullStars) >= 0.5;
                            @endphp
                            <div class="flex">
                                @for($i = 0; $i < 5; $i++)
                                    @if($i < $fullStars)
                                        <span class="text-yellow-400 text-xl">★</span>
                                    @elseif($i == $fullStars && $hasHalfStar)
                                        <span class="text-yellow-400 text-xl">⭐</span>
                                    @else
                                        <span class="text-gray-300 text-xl">★</span>
                                    @endif
                                @endfor
                            </div>
                            <span class="text-lg font-semibold text-gray-700">
                                {{ number_format($rating, 1) }}
                            </span>
                            <span class="text-gray-500">
                                ({{ $product->rating_count }} รีวิว)
                            </span>
                        </div>

                        <div class="text-gray-400">|</div>

                        <div class="text-gray-600">
                            <span class="font-semibold">{{ $product->sales_count }}</span> ขายแล้ว
                        </div>

                        <div class="text-gray-400">|</div>

                        <div class="text-gray-600">
                            <span class="font-semibold">{{ $product->view_count }}</span> คนดู
                        </div>
                    </div>

                    <!-- Price -->
                    <div class="bg-gradient-to-r from-indigo-50 to-purple-50 p-6 rounded-xl">
                        @if($product->compare_at_price && $product->compare_at_price > $product->price)
                            <div class="space-y-2">
                                <div class="text-sm text-gray-600">ราคาปกติ</div>
                                <div class="text-2xl text-gray-400 line-through">
                                    ฿{{ number_format($product->compare_at_price, 2) }}
                                </div>
                                <div class="text-sm text-red-600 font-bold">
                                    ประหยัด ฿{{ number_format($product->compare_at_price - $product->price, 2) }}
                                </div>
                            </div>
                            <div class="mt-3 pt-3 border-t border-indigo-200">
                                <div class="text-sm text-gray-600 mb-1">ราคาพิเศษ</div>
                                <div class="text-4xl font-black text-indigo-600">
                                    ฿{{ number_format($product->price, 2) }}
                                </div>
                            </div>
                        @else
                            <div>
                                <div class="text-sm text-gray-600 mb-2">ราคา</div>
                                <div class="text-4xl font-black text-indigo-600">
                                    ฿{{ number_format($product->price, 2) }}
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Short Description -->
                    @if($product->short_description)
                    <div class="prose prose-sm max-w-none text-gray-600">
                        {!! nl2br(e($product->short_description)) !!}
                    </div>
                    @endif

                    <!-- Product Attributes -->
                    @if($product->attributes && count($product->attributes) > 0)
                    <div class="space-y-3">
                        <h3 class="text-lg font-bold text-gray-900">คุณสมบัติสินค้า</h3>
                        <div class="grid grid-cols-2 gap-3">
                            @foreach($product->attributes as $key => $value)
                            <div class="flex items-center gap-2">
                                <span class="text-gray-600">{{ ucfirst($key) }}:</span>
                                <span class="font-semibold text-gray-900">{{ $value }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Variants -->
                    @if($product->variants && $product->variants->count() > 0)
                    <div class="space-y-3">
                        <h3 class="text-lg font-bold text-gray-900">ตัวเลือกสินค้า</h3>
                        <div class="flex flex-wrap gap-2">
                            @foreach($product->variants as $variant)
                            <a href="{{ route('shop.show', $variant->slug) }}"
                               class="px-4 py-2 border-2 border-gray-300 hover:border-indigo-600 rounded-lg font-medium transition">
                                {{ $variant->name }}
                            </a>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Stock Info -->
                    <div class="flex items-center gap-3 text-sm">
                        @if($product->stock_status === 'in_stock')
                            <span class="flex items-center gap-2 text-green-600 font-semibold">
                                <span class="w-3 h-3 bg-green-600 rounded-full"></span>
                                มีสินค้าพร้อมส่ง
                            </span>
                        @else
                            <span class="flex items-center gap-2 text-red-600 font-semibold">
                                <span class="w-3 h-3 bg-red-600 rounded-full"></span>
                                สินค้าหมด
                            </span>
                        @endif

                        @if($product->sku)
                        <span class="text-gray-500">
                            SKU: <span class="font-mono">{{ $product->sku }}</span>
                        </span>
                        @endif
                    </div>

                    <!-- Actions -->
                    <div class="space-y-3 pt-4">
                        @if($product->stock_status === 'in_stock')
                        <!-- Quantity Selector -->
                        <div class="flex items-center gap-4">
                            <span class="text-gray-700 font-semibold">จำนวน:</span>
                            <div class="flex items-center border-2 border-gray-300 rounded-lg">
                                <button onclick="decrementQty()" class="px-4 py-2 hover:bg-gray-100 font-bold transition">-</button>
                                <input type="number" id="quantity" value="1" min="1" max="{{ $product->stock_quantity ?? 999 }}"
                                       class="w-20 text-center border-x-2 border-gray-300 py-2 font-semibold focus:outline-none">
                                <button onclick="incrementQty()" class="px-4 py-2 hover:bg-gray-100 font-bold transition">+</button>
                            </div>
                        </div>

                        <!-- Add to Cart Button -->
                        <button onclick="addToCart()"
                                class="w-full px-8 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-bold text-lg rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200">
                            🛒 เพิ่มลงตะกร้า
                        </button>

                        <!-- Buy Now Button -->
                        <button onclick="buyNow()"
                                class="w-full px-8 py-4 bg-gradient-to-r from-orange-500 to-red-500 hover:from-orange-600 hover:to-red-600 text-white font-bold text-lg rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200">
                            ⚡ ซื้อทันที
                        </button>
                        @else
                        <!-- Out of Stock -->
                        <button disabled
                                class="w-full px-8 py-4 bg-gray-400 text-white font-bold text-lg rounded-xl cursor-not-allowed">
                            สินค้าหมด
                        </button>
                        @endif

                        <!-- Wishlist & Share -->
                        <div class="flex gap-3">
                            <button class="flex-1 px-6 py-3 border-2 border-gray-300 hover:border-pink-500 hover:text-pink-500 font-semibold rounded-xl transition">
                                ❤️ บันทึก
                            </button>
                            <button onclick="shareProduct()"
                                    class="flex-1 px-6 py-3 border-2 border-gray-300 hover:border-blue-500 hover:text-blue-500 font-semibold rounded-xl transition">
                                🔗 แชร์
                            </button>
                        </div>
                    </div>

                    <!-- Seller Info -->
                    @if($product->seller)
                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm text-gray-600 mb-1">ขายโดย</div>
                                <div class="font-bold text-gray-900">{{ $product->seller->name }}</div>
                            </div>
                            <a href="#" class="px-4 py-2 bg-white border-2 border-gray-300 hover:border-indigo-600 font-semibold rounded-lg transition">
                                ดูร้านค้า
                            </a>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Product Tabs -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden mb-8" x-data="{ tab: 'description' }">
            <!-- Tab Headers -->
            <div class="border-b border-gray-200">
                <div class="flex overflow-x-auto">
                    <button @click="tab = 'description'"
                            :class="tab === 'description' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-600 hover:text-gray-900'"
                            class="px-8 py-4 font-semibold border-b-2 whitespace-nowrap transition">
                        รายละเอียดสินค้า
                    </button>
                    <button @click="tab = 'reviews'"
                            :class="tab === 'reviews' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-600 hover:text-gray-900'"
                            class="px-8 py-4 font-semibold border-b-2 whitespace-nowrap transition">
                        รีวิวจากผู้ซื้อ ({{ $product->rating_count }})
                    </button>
                    <button @click="tab = 'shipping'"
                            :class="tab === 'shipping' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-600 hover:text-gray-900'"
                            class="px-8 py-4 font-semibold border-b-2 whitespace-nowrap transition">
                        การจัดส่ง
                    </button>
                </div>
            </div>

            <!-- Tab Content -->
            <div class="p-8">
                <!-- Description Tab -->
                <div x-show="tab === 'description'" class="prose prose-lg max-w-none">
                    @if($product->description)
                        {!! $product->description !!}
                    @else
                        <p class="text-gray-500">ไม่มีรายละเอียดสินค้า</p>
                    @endif

                    @if($product->brand)
                    <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                        <h4 class="font-bold text-gray-900 mb-2">ยี่ห้อ</h4>
                        <p class="text-gray-700">{{ $product->brand }}</p>
                    </div>
                    @endif

                    @if($product->weight || $product->dimensions)
                    <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                        <h4 class="font-bold text-gray-900 mb-2">ข้อมูลการจัดส่ง</h4>
                        @if($product->weight)
                        <p class="text-gray-700">น้ำหนัก: {{ $product->weight }} กก.</p>
                        @endif
                        @if($product->dimensions)
                        <p class="text-gray-700">ขนาด: {{ $product->dimensions }}</p>
                        @endif
                    </div>
                    @endif
                </div>

                <!-- Reviews Tab -->
                <div x-show="tab === 'reviews'" class="space-y-6">
                    @if($product->approvedReviews && $product->approvedReviews->count() > 0)
                        @foreach($product->approvedReviews as $review)
                        <div class="border-b border-gray-200 pb-6 last:border-0">
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 bg-gradient-to-br from-indigo-400 to-purple-400 rounded-full flex items-center justify-center text-white font-bold text-lg">
                                    {{ substr($review->user->name ?? 'U', 0, 1) }}
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <span class="font-bold text-gray-900">{{ $review->user->name ?? 'ผู้ใช้' }}</span>
                                        <div class="flex">
                                            @for($i = 0; $i < 5; $i++)
                                                <span class="text-{{ $i < $review->rating ? 'yellow-400' : 'gray-300' }} text-sm">★</span>
                                            @endfor
                                        </div>
                                        <span class="text-sm text-gray-500">{{ $review->created_at->diffForHumans() }}</span>
                                    </div>
                                    @if($review->comment)
                                    <p class="text-gray-700">{{ $review->comment }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    @else
                        <div class="text-center py-12">
                            <div class="text-6xl mb-4">💬</div>
                            <p class="text-gray-500 text-lg">ยังไม่มีรีวิวสำหรับสินค้านี้</p>
                            @if($hasPurchased)
                            <button class="mt-4 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition">
                                เขียนรีวิว
                            </button>
                            @endif
                        </div>
                    @endif

                    @if($hasPurchased && $product->approvedReviews->count() > 0)
                    <div class="pt-6 border-t border-gray-200">
                        <button class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition">
                            เขียนรีวิว
                        </button>
                    </div>
                    @endif
                </div>

                <!-- Shipping Tab -->
                <div x-show="tab === 'shipping'" class="space-y-4">
                    <div class="flex items-start gap-4 p-4 bg-blue-50 rounded-lg">
                        <span class="text-3xl">🚚</span>
                        <div>
                            <h4 class="font-bold text-gray-900 mb-2">จัดส่งฟรี</h4>
                            <p class="text-gray-700">สำหรับคำสั่งซื้อที่มีมูลค่าตั้งแต่ 500 บาทขึ้นไป</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4 p-4 bg-green-50 rounded-lg">
                        <span class="text-3xl">⚡</span>
                        <div>
                            <h4 class="font-bold text-gray-900 mb-2">จัดส่งรวดเร็ว</h4>
                            <p class="text-gray-700">ส่งสินค้าภายใน 1-2 วันทำการ</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4 p-4 bg-orange-50 rounded-lg">
                        <span class="text-3xl">📦</span>
                        <div>
                            <h4 class="font-bold text-gray-900 mb-2">ติดตามพัสดุได้</h4>
                            <p class="text-gray-700">ตรวจสอบสถานะการจัดส่งแบบเรียลไทม์</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Related Products -->
        @if($relatedProducts && $relatedProducts->count() > 0)
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">สินค้าที่เกี่ยวข้อง</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($relatedProducts as $relatedProduct)
                <a href="{{ route('shop.show', $relatedProduct->slug) }}"
                   class="group bg-white rounded-xl shadow-lg hover:shadow-2xl transform hover:-translate-y-2 transition-all duration-300 overflow-hidden">
                    <div class="relative aspect-square bg-gradient-to-br from-gray-50 to-gray-100 overflow-hidden">
                        @if($relatedProduct->main_image_url)
                            <img src="{{ $relatedProduct->main_image_url }}"
                                 alt="{{ $relatedProduct->name }}"
                                 class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-300">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-gray-400">
                                <span class="text-6xl">📦</span>
                            </div>
                        @endif
                    </div>
                    <div class="p-4">
                        <h3 class="font-bold text-gray-900 mb-2 line-clamp-2 group-hover:text-indigo-600 transition">
                            {{ $relatedProduct->name }}
                        </h3>
                        <div class="flex items-baseline gap-2">
                            <span class="text-xl font-black text-indigo-600">
                                ฿{{ number_format($relatedProduct->price, 2) }}
                            </span>
                            @if($relatedProduct->compare_at_price && $relatedProduct->compare_at_price > $relatedProduct->price)
                            <span class="text-sm text-gray-400 line-through">
                                ฿{{ number_format($relatedProduct->compare_at_price, 2) }}
                            </span>
                            @endif
                        </div>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>

<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

<script>
function changeMainImage(url) {
    document.getElementById('mainImage').src = url;
}

function incrementQty() {
    const input = document.getElementById('quantity');
    const max = parseInt(input.max);
    const current = parseInt(input.value);
    if (current < max) {
        input.value = current + 1;
    }
}

function decrementQty() {
    const input = document.getElementById('quantity');
    const min = parseInt(input.min);
    const current = parseInt(input.value);
    if (current > min) {
        input.value = current - 1;
    }
}

function addToCart() {
    const quantity = document.getElementById('quantity').value;

    @guest
    // Redirect to login if not authenticated
    if (confirm('กรุณาเข้าสู่ระบบเพื่อเพิ่มสินค้าลงตะกร้า')) {
        window.location.href = '{{ route("login") }}';
    }
    return;
    @endguest

    // Show loading state
    const button = event.target;
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '⏳ กำลังเพิ่มลงตะกร้า...';

    // Send request to add to cart
    fetch('{{ route("cart.add") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            product_id: {{ $product->id }},
            quantity: parseInt(quantity),
            attributes: {}
        })
    })
    .then(response => {
        if (response.redirected) {
            window.location.href = response.url;
            return;
        }
        return response.json();
    })
    .then(data => {
        // Redirect to cart page
        window.location.href = '{{ route("cart.index") }}';
    })
    .catch(error => {
        console.error('Error:', error);
        alert('เกิดข้อผิดพลาดในการเพิ่มสินค้าลงตะกร้า');
        button.disabled = false;
        button.innerHTML = originalText;
    });
}

function buyNow() {
    const quantity = document.getElementById('quantity').value;

    @guest
    // Redirect to login if not authenticated
    if (confirm('กรุณาเข้าสู่ระบบเพื่อทำการสั่งซื้อ')) {
        window.location.href = '{{ route("login") }}';
    }
    return;
    @endguest

    // Show loading state
    const button = event.target;
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '⏳ กำลังดำเนินการ...';

    // Add to cart first, then redirect to checkout
    fetch('{{ route("cart.add") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            product_id: {{ $product->id }},
            quantity: parseInt(quantity),
            attributes: {}
        })
    })
    .then(response => {
        if (response.redirected) {
            window.location.href = response.url;
            return;
        }
        return response.json();
    })
    .then(data => {
        // Redirect directly to checkout
        window.location.href = '{{ route("checkout.index") }}';
    })
    .catch(error => {
        console.error('Error:', error);
        alert('เกิดข้อผิดพลาดในการทำรายการ');
        button.disabled = false;
        button.innerHTML = originalText;
    });
}

function shareProduct() {
    if (navigator.share) {
        navigator.share({
            title: '{{ $product->name }}',
            text: 'ดูสินค้านี้สิ!',
            url: window.location.href
        });
    } else {
        // Fallback: Copy to clipboard
        navigator.clipboard.writeText(window.location.href);
        alert('คัดลอกลิงก์แล้ว!');
    }
}
</script>
@endsection
