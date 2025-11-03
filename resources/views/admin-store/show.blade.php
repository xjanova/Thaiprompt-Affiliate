@extends('layouts.app')

@section('title', $product->name . ' - ' . $adminStore->store_name)

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-purple-50 to-blue-50">
    <div class="container mx-auto px-4 py-8">
        <!-- Breadcrumb -->
        <nav class="mb-8">
            <div class="flex items-center gap-2 text-sm bg-white rounded-xl shadow-lg px-6 py-3 inline-flex">
                <a href="{{ route('admin-store.index') }}" class="text-indigo-600 hover:text-indigo-700 font-semibold transition-colors">
                    🏪 ร้านค้า
                </a>
                <span class="text-gray-400">/</span>
                <a href="{{ route('admin-store.index', ['category' => $product->category_id]) }}" class="text-indigo-600 hover:text-indigo-700 font-semibold transition-colors">
                    {{ $product->category->name ?? 'ทั่วไป' }}
                </a>
                <span class="text-gray-400">/</span>
                <span class="text-gray-600 font-semibold">{{ $product->name }}</span>
            </div>
        </nav>

        <!-- Product Detail -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12">
            <!-- Product Images -->
            <div class="space-y-4">
                <!-- Main Image -->
                <div class="bg-white rounded-3xl shadow-2xl overflow-hidden border border-gray-100 p-8">
                    @if($product->main_image_url)
                        <img src="{{ $product->main_image_url }}"
                             alt="{{ $product->name }}"
                             class="w-full aspect-square object-contain rounded-2xl">
                    @else
                        <div class="w-full aspect-square bg-gradient-to-br from-gray-100 to-gray-200 rounded-2xl flex items-center justify-center">
                            <span class="text-9xl text-gray-400">📦</span>
                        </div>
                    @endif
                </div>

                <!-- Thumbnail Images -->
                @if($product->image_urls && count($product->image_urls) > 0)
                    <div class="grid grid-cols-4 gap-3">
                        @foreach($product->image_urls as $imageUrl)
                            <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100 p-3 cursor-pointer hover:shadow-2xl transition-all">
                                <img src="{{ $imageUrl }}" alt="{{ $product->name }}" class="w-full aspect-square object-cover rounded-lg">
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Product Info -->
            <div class="space-y-6">
                <!-- Title & Rating -->
                <div class="bg-white rounded-3xl shadow-2xl p-8 border border-gray-100">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <h1 class="text-3xl md:text-4xl font-black text-gray-800 mb-3">
                                {{ $product->name }}
                            </h1>

                            <!-- Rating -->
                            <div class="flex items-center gap-4 mb-4">
                                <div class="flex items-center gap-2 bg-yellow-50 px-4 py-2 rounded-xl">
                                    <span class="text-yellow-500 text-xl">⭐</span>
                                    <span class="font-bold text-gray-800">{{ number_format($product->rating_average, 1) }}</span>
                                    <span class="text-sm text-gray-600">({{ $product->rating_count }} รีวิว)</span>
                                </div>
                                <div class="flex items-center gap-2 bg-blue-50 px-4 py-2 rounded-xl">
                                    <span class="text-blue-600 text-xl">📦</span>
                                    <span class="text-sm font-semibold text-gray-700">ขายแล้ว {{ $product->sales_count }} ชิ้น</span>
                                </div>
                            </div>
                        </div>

                        <!-- Favorite Button -->
                        <button class="w-14 h-14 bg-gradient-to-br from-pink-50 to-red-50 hover:from-pink-100 hover:to-red-100 rounded-2xl shadow-lg hover:shadow-xl flex items-center justify-center text-2xl transform hover:scale-110 transition-all">
                            ❤️
                        </button>
                    </div>

                    <!-- Price -->
                    <div class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-2xl p-6 mb-6">
                        @if($product->compare_at_price && $product->compare_at_price > $product->price)
                            @php
                                $discount = round((($product->compare_at_price - $product->price) / $product->compare_at_price) * 100);
                            @endphp
                            <div class="flex items-center gap-3 mb-2">
                                <span class="text-sm font-semibold text-gray-600 line-through">
                                    ฿{{ number_format($product->compare_at_price, 2) }}
                                </span>
                                <span class="px-3 py-1 bg-gradient-to-r from-red-500 to-pink-500 text-white text-sm font-bold rounded-full">
                                    ลด {{ $discount }}%
                                </span>
                            </div>
                        @endif
                        <div class="flex items-baseline gap-2">
                            <span class="text-5xl font-black text-indigo-600">
                                ฿{{ number_format($product->price, 0) }}
                            </span>
                            <span class="text-xl text-gray-600">.{{ str_pad(($product->price - floor($product->price)) * 100, 2, '0') }}</span>
                        </div>
                    </div>

                    <!-- Stock Status -->
                    <div class="mb-6">
                        @if($product->stock_status === 'in_stock')
                            <div class="flex items-center gap-2 text-green-600">
                                <span class="text-2xl">✓</span>
                                <span class="font-bold">สินค้าพร้อมส่ง</span>
                                @if($product->track_inventory)
                                    <span class="text-sm text-gray-600">(เหลือ {{ $product->stock_quantity }} ชิ้น)</span>
                                @endif
                            </div>
                        @elseif($product->stock_status === 'out_of_stock')
                            <div class="flex items-center gap-2 text-red-600">
                                <span class="text-2xl">✕</span>
                                <span class="font-bold">สินค้าหมด</span>
                            </div>
                        @else
                            <div class="flex items-center gap-2 text-orange-600">
                                <span class="text-2xl">⏰</span>
                                <span class="font-bold">สั่งจองล่วงหน้า</span>
                            </div>
                        @endif
                    </div>

                    <!-- Quantity Selector -->
                    @if($product->stock_status === 'in_stock')
                        <div class="mb-6">
                            <label class="block text-sm font-semibold text-gray-700 mb-3">จำนวน</label>
                            <div class="flex items-center gap-3">
                                <button class="w-12 h-12 bg-gray-100 hover:bg-gray-200 rounded-xl font-bold text-xl transition-all">
                                    -
                                </button>
                                <input type="number" value="1" min="1" class="w-20 h-12 text-center border-2 border-gray-200 rounded-xl font-bold text-lg focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 transition-all">
                                <button class="w-12 h-12 bg-gray-100 hover:bg-gray-200 rounded-xl font-bold text-xl transition-all">
                                    +
                                </button>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex gap-3">
                            <button class="flex-1 px-8 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200 flex items-center justify-center gap-2">
                                <span class="text-xl">🛒</span>
                                <span>เพิ่มลงตะกร้า</span>
                            </button>
                            <button class="px-8 py-4 bg-gradient-to-r from-pink-600 to-red-600 hover:from-pink-700 hover:to-red-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200">
                                ซื้อเลย
                            </button>
                        </div>
                    @endif
                </div>

                <!-- Additional Info -->
                <div class="bg-white rounded-3xl shadow-2xl p-8 border border-gray-100">
                    <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <span>ℹ️</span>
                        <span>ข้อมูลเพิ่มเติม</span>
                    </h3>

                    <div class="space-y-3">
                        @if($product->brand)
                            <div class="flex items-center justify-between py-3 border-b border-gray-100">
                                <span class="text-gray-600 font-semibold">แบรนด์</span>
                                <span class="font-bold text-gray-800">{{ $product->brand }}</span>
                            </div>
                        @endif

                        @if($product->sku)
                            <div class="flex items-center justify-between py-3 border-b border-gray-100">
                                <span class="text-gray-600 font-semibold">SKU</span>
                                <span class="font-mono text-sm text-gray-800">{{ $product->sku }}</span>
                            </div>
                        @endif

                        @if($product->weight)
                            <div class="flex items-center justify-between py-3 border-b border-gray-100">
                                <span class="text-gray-600 font-semibold">น้ำหนัก</span>
                                <span class="font-bold text-gray-800">{{ number_format($product->weight / 1000, 2) }} kg</span>
                            </div>
                        @endif

                        <div class="flex items-center justify-between py-3">
                            <span class="text-gray-600 font-semibold">หมวดหมู่</span>
                            <span class="font-bold text-indigo-600">{{ $product->category->name ?? 'ทั่วไป' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Description -->
        <div class="bg-white rounded-3xl shadow-2xl p-8 md:p-12 mb-12 border border-gray-100">
            <h2 class="text-3xl font-black text-gray-800 mb-6 flex items-center gap-3">
                <span>📝</span>
                <span>รายละเอียดสินค้า</span>
            </h2>

            <div class="prose max-w-none text-gray-700 leading-relaxed">
                @if($product->description)
                    {!! nl2br(e($product->description)) !!}
                @else
                    <p class="text-gray-500">ไม่มีรายละเอียดสินค้า</p>
                @endif
            </div>
        </div>

        <!-- Related Products -->
        @if($relatedProducts->count() > 0)
            <div class="mb-12">
                <h2 class="text-3xl font-black text-gray-800 mb-8 flex items-center gap-3">
                    <span>🔗</span>
                    <span>สินค้าที่เกี่ยวข้อง</span>
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach($relatedProducts as $relatedProduct)
                        @include('admin-store.partials.product-card', ['product' => $relatedProduct, 'featured' => false])
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
