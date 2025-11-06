@extends('layouts.app')

@section('title', $product->name . ' - ร้านค้าออนไลน์')

@section('meta')
<meta name="description" content="{{ $product->short_description ?? Str::limit($product->description, 160) }}">
@endsection

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-indigo-50/20">
    <div class="container mx-auto px-4 py-6">

        <!-- Breadcrumb -->
        <nav class="mb-6" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2 text-sm flex-wrap">
                <li>
                    <a href="{{ route('home') }}" class="text-gray-500 hover:text-indigo-600 transition flex items-center gap-1">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                        </svg>
                        หน้าแรก
                    </a>
                </li>
                <li><span class="text-gray-400">/</span></li>
                <li>
                    <a href="{{ route('shop.index') }}" class="text-gray-500 hover:text-indigo-600 transition">
                        ร้านค้า
                    </a>
                </li>
                @if($product->category)
                <li><span class="text-gray-400">/</span></li>
                <li>
                    <a href="{{ route('shop.category', $product->category->slug) }}" class="text-gray-500 hover:text-indigo-600 transition">
                        {{ $product->category->name }}
                    </a>
                </li>
                @endif
                <li><span class="text-gray-400">/</span></li>
                <li class="text-gray-700 font-medium">{{ Str::limit($product->name, 50) }}</li>
            </ol>
        </nav>

        <!-- Main Product Section -->
        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden mb-8 border border-gray-100">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 p-6 lg:p-10">

                <!-- Product Images Gallery -->
                <div class="space-y-4">
                    <!-- Main Image Display -->
                    <div class="relative aspect-square bg-gradient-to-br from-gray-50 to-gray-100 rounded-2xl overflow-hidden group">
                        @if($product->main_image_url)
                            <img id="mainImage"
                                 src="{{ $product->main_image_url }}"
                                 alt="{{ $product->name }}"
                                 class="w-full h-full object-cover cursor-zoom-in transition-transform duration-500">
                        @else
                            <div class="w-full h-full flex items-center justify-center">
                                <svg class="w-32 h-32 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        @endif

                        <!-- Top Badges -->
                        <div class="absolute top-4 left-4 flex flex-col gap-2 z-10">
                            @if($product->is_featured)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gradient-to-r from-amber-400 to-orange-500 text-white text-sm font-bold rounded-xl shadow-lg">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                    สินค้าแนะนำ
                                </span>
                            @endif

                            @if($product->compare_at_price && $product->compare_at_price > $product->price)
                                @php
                                    $discount = round((($product->compare_at_price - $product->price) / $product->compare_at_price) * 100);
                                @endphp
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gradient-to-r from-red-500 to-pink-500 text-white text-sm font-bold rounded-xl shadow-lg">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                                        <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm9.707 5.707a1 1 0 00-1.414-1.414L9 12.586l-1.293-1.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    ลด {{ $discount }}%
                                </span>
                            @endif
                        </div>

                        <!-- Stock Status Overlay -->
                        @if($product->stock_status === 'out_of_stock')
                            <div class="absolute inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-20">
                                <div class="text-center">
                                    <span class="inline-block px-6 py-3 bg-gray-900 text-white text-xl font-bold rounded-2xl shadow-2xl">
                                        สินค้าหมด
                                    </span>
                                </div>
                            </div>
                        @elseif($product->track_inventory && $product->stock_quantity < $product->low_stock_threshold)
                            <div class="absolute bottom-4 left-4 right-4 z-10">
                                <div class="bg-gradient-to-r from-orange-500 to-red-500 text-white text-sm font-bold py-3 px-4 rounded-xl text-center shadow-lg backdrop-blur-sm">
                                    <div class="flex items-center justify-center gap-2">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                        </svg>
                                        เหลือเพียง {{ $product->stock_quantity }} ชิ้น - รีบสั่งซื้อเลย!
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Thumbnail Gallery -->
                    @if($product->images && $product->images->count() > 0)
                    <div class="grid grid-cols-5 gap-3">
                        @if($product->main_image_url)
                        <button onclick="changeMainImage('{{ $product->main_image_url }}')"
                                class="thumbnail-btn aspect-square rounded-xl overflow-hidden border-2 border-indigo-600 hover:border-indigo-800 transition-all hover:shadow-lg">
                            <img src="{{ $product->main_image_url }}" alt="Main" class="w-full h-full object-cover">
                        </button>
                        @endif

                        @foreach($product->images->take(4) as $image)
                        <button onclick="changeMainImage('{{ $image->url }}')"
                                class="thumbnail-btn aspect-square rounded-xl overflow-hidden border-2 border-gray-300 hover:border-indigo-600 transition-all hover:shadow-lg">
                            <img src="{{ $image->url }}" alt="Product Image" class="w-full h-full object-cover">
                        </button>
                        @endforeach
                    </div>
                    @endif
                </div>

                <!-- Product Info -->
                <div class="space-y-6">

                    <!-- Category & Brand -->
                    <div class="flex items-center gap-3 flex-wrap">
                        @if($product->category)
                            <a href="{{ route('shop.category', $product->category->slug) }}"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gradient-to-r from-indigo-50 to-purple-50 text-indigo-600 text-sm font-bold rounded-xl border border-indigo-200 hover:border-indigo-400 transition-all">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
                                </svg>
                                {{ $product->category->name }}
                            </a>
                        @endif

                        @if($product->brand)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 text-gray-700 text-sm font-semibold rounded-xl">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                {{ $product->brand }}
                            </span>
                        @endif
                    </div>

                    <!-- Product Name -->
                    <h1 class="text-3xl lg:text-4xl font-black text-gray-900 leading-tight">
                        {{ $product->name }}
                    </h1>

                    <!-- Rating & Sales Stats -->
                    <div class="flex items-center gap-4 flex-wrap pb-6 border-b-2 border-gray-100">
                        @php
                            $rating = $product->rating_average ?? 0;
                            $fullStars = floor($rating);
                            $hasHalfStar = ($rating - $fullStars) >= 0.5;
                        @endphp

                        <div class="flex items-center gap-2 bg-amber-50 px-4 py-2 rounded-xl border border-amber-100">
                            <div class="flex">
                                @for($i = 0; $i < 5; $i++)
                                    @if($i < $fullStars)
                                        <svg class="w-5 h-5 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    @elseif($i == $fullStars && $hasHalfStar)
                                        <svg class="w-5 h-5 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    @else
                                        <svg class="w-5 h-5 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    @endif
                                @endfor
                            </div>
                            <span class="text-lg font-bold text-gray-900">
                                {{ number_format($rating, 1) }}
                            </span>
                            <span class="text-gray-600">
                                ({{ number_format($product->rating_count) }})
                            </span>
                        </div>

                        <div class="h-6 w-px bg-gray-300"></div>

                        <div class="flex items-center gap-2">
                            <span class="text-gray-600">ขายแล้ว</span>
                            <span class="text-xl font-bold text-gray-900">{{ number_format($product->sales_count) }}</span>
                        </div>

                        <div class="h-6 w-px bg-gray-300"></div>

                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-600">{{ number_format($product->view_count) }} ครั้ง</span>
                        </div>
                    </div>

                    <!-- Price Section -->
                    <div class="bg-gradient-to-br from-indigo-50 via-purple-50 to-pink-50 p-6 rounded-2xl border-2 border-indigo-100">
                        @if($product->compare_at_price && $product->compare_at_price > $product->price)
                            <div class="space-y-2">
                                <div class="text-sm font-semibold text-gray-600">ราคาปกติ</div>
                                <div class="text-2xl text-gray-400 line-through font-bold">
                                    ฿{{ number_format($product->compare_at_price, 2) }}
                                </div>
                                <div class="flex items-center gap-2 text-sm">
                                    <span class="px-3 py-1 bg-red-500 text-white font-bold rounded-lg">
                                        ประหยัด ฿{{ number_format($product->compare_at_price - $product->price, 2) }}
                                    </span>
                                </div>
                            </div>
                            <div class="mt-4 pt-4 border-t-2 border-indigo-200">
                                <div class="text-sm font-semibold text-gray-700 mb-2">ราคาพิเศษ</div>
                                <div class="text-5xl font-black text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-purple-600">
                                    ฿{{ number_format($product->price, 2) }}
                                </div>
                            </div>
                        @else
                            <div>
                                <div class="text-sm font-semibold text-gray-700 mb-2">ราคา</div>
                                <div class="text-5xl font-black text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-purple-600">
                                    ฿{{ number_format($product->price, 2) }}
                                </div>
                            </div>
                        @endif

                        @if($product->price >= 500)
                            <div class="mt-4 flex items-center gap-2 text-emerald-600 font-semibold bg-emerald-50 px-4 py-2 rounded-xl border border-emerald-200">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
                                    <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"/>
                                </svg>
                                ส่งฟรี! คุณได้สิทธิ์ส่งฟรีสำหรับสินค้านี้
                            </div>
                        @endif
                    </div>

                    <!-- Short Description -->
                    @if($product->short_description)
                    <div class="prose prose-sm max-w-none text-gray-700 leading-relaxed">
                        {!! nl2br(e($product->short_description)) !!}
                    </div>
                    @endif

                    <!-- Stock Status -->
                    <div class="flex items-center gap-3 text-sm font-semibold">
                        @if($product->stock_status === 'in_stock')
                            <div class="flex items-center gap-2 text-emerald-600 bg-emerald-50 px-4 py-2 rounded-xl border border-emerald-200">
                                <span class="relative flex h-3 w-3">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                                </span>
                                มีสินค้าพร้อมส่ง
                            </div>
                        @else
                            <div class="flex items-center gap-2 text-red-600 bg-red-50 px-4 py-2 rounded-xl border border-red-200">
                                <span class="w-3 h-3 bg-red-600 rounded-full"></span>
                                สินค้าหมด
                            </div>
                        @endif

                        @if($product->sku)
                        <span class="text-gray-500 bg-gray-100 px-4 py-2 rounded-xl">
                            SKU: <span class="font-mono font-bold text-gray-900">{{ $product->sku }}</span>
                        </span>
                        @endif
                    </div>

                    <!-- Action Buttons -->
                    <div class="space-y-4 pt-6">
                        @if($product->stock_status === 'in_stock')
                        <!-- Quantity Selector -->
                        <div class="flex items-center gap-4">
                            <span class="text-gray-900 font-bold text-lg">จำนวน:</span>
                            <div class="flex items-center border-2 border-gray-300 rounded-xl overflow-hidden bg-white">
                                <button onclick="decrementQty()"
                                        class="px-5 py-3 hover:bg-gray-100 font-bold text-lg transition-colors">
                                    −
                                </button>
                                <input type="number"
                                       id="quantity"
                                       value="1"
                                       min="1"
                                       max="{{ $product->stock_quantity ?? 999 }}"
                                       class="w-20 text-center border-x-2 border-gray-300 py-3 font-bold text-lg focus:outline-none">
                                <button onclick="incrementQty()"
                                        class="px-5 py-3 hover:bg-gray-100 font-bold text-lg transition-colors">
                                    +
                                </button>
                            </div>
                        </div>

                        <!-- Add to Cart & Buy Now -->
                        <div class="flex gap-3">
                            <button onclick="addToCart()"
                                    class="flex-1 px-8 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-bold text-lg rounded-2xl shadow-lg hover:shadow-2xl transform hover:scale-105 transition-all duration-200 flex items-center justify-center gap-2">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                เพิ่มลงตะกร้า
                            </button>
                        </div>

                        <button onclick="buyNow()"
                                class="w-full px-8 py-4 bg-gradient-to-r from-orange-500 to-red-500 hover:from-orange-600 hover:to-red-600 text-white font-bold text-lg rounded-2xl shadow-lg hover:shadow-2xl transform hover:scale-105 transition-all duration-200 flex items-center justify-center gap-2">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            ซื้อทันที
                        </button>
                        @else
                        <!-- Out of Stock -->
                        <button disabled
                                class="w-full px-8 py-4 bg-gray-300 text-gray-500 font-bold text-lg rounded-2xl cursor-not-allowed flex items-center justify-center gap-2">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 5.11a6 6 0 018.367 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z" clip-rule="evenodd"/>
                            </svg>
                            สินค้าหมด
                        </button>
                        @endif

                        <!-- Wishlist & Share -->
                        <div class="grid grid-cols-2 gap-3">
                            <button class="flex items-center justify-center gap-2 px-6 py-3 border-2 border-gray-300 hover:border-pink-500 hover:bg-pink-50 hover:text-pink-600 text-gray-700 font-bold rounded-xl transition-all">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                </svg>
                                บันทึก
                            </button>
                            <button onclick="shareProduct()"
                                    class="flex items-center justify-center gap-2 px-6 py-3 border-2 border-gray-300 hover:border-blue-500 hover:bg-blue-50 hover:text-blue-600 text-gray-700 font-bold rounded-xl transition-all">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                                </svg>
                                แชร์
                            </button>
                        </div>
                    </div>

                    <!-- Trust Badges -->
                    <div class="grid grid-cols-3 gap-3 pt-6">
                        <div class="flex flex-col items-center gap-2 p-3 bg-blue-50 rounded-xl border border-blue-100">
                            <svg class="w-8 h-8 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-xs font-bold text-blue-900 text-center">ของแท้ 100%</span>
                        </div>
                        <div class="flex flex-col items-center gap-2 p-3 bg-green-50 rounded-xl border border-green-100">
                            <svg class="w-8 h-8 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
                                <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"/>
                            </svg>
                            <span class="text-xs font-bold text-green-900 text-center">จัดส่งรวดเร็ว</span>
                        </div>
                        <div class="flex flex-col items-center gap-2 p-3 bg-purple-50 rounded-xl border border-purple-100">
                            <svg class="w-8 h-8 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-xs font-bold text-purple-900 text-center">คืนเงิน 100%</span>
                        </div>
                    </div>

                    <!-- Seller Info -->
                    @if($product->seller)
                    <div class="bg-gradient-to-br from-gray-50 to-gray-100 p-5 rounded-2xl border border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 bg-gradient-to-br from-indigo-400 to-purple-400 rounded-xl flex items-center justify-center text-white font-bold text-xl">
                                    {{ substr($product->seller->name ?? 'S', 0, 1) }}
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500 font-semibold">ขายโดย</div>
                                    <div class="font-bold text-gray-900 text-lg">{{ $product->seller->name }}</div>
                                </div>
                            </div>
                            <a href="#"
                               class="px-4 py-2 bg-white border-2 border-gray-300 hover:border-indigo-600 hover:text-indigo-600 font-bold rounded-xl transition-all shadow-sm hover:shadow-md">
                                ดูร้านค้า
                            </a>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Product Details Tabs -->
        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden mb-8 border border-gray-100" x-data="{ tab: 'description' }">
            <!-- Tab Navigation -->
            <div class="border-b-2 border-gray-100">
                <div class="flex overflow-x-auto">
                    <button @click="tab = 'description'"
                            :class="tab === 'description' ? 'border-indigo-600 text-indigo-600 bg-indigo-50' : 'border-transparent text-gray-600 hover:text-gray-900 hover:bg-gray-50'"
                            class="px-8 py-4 font-bold border-b-4 whitespace-nowrap transition-all flex items-center gap-2">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                        </svg>
                        รายละเอียดสินค้า
                    </button>
                    <button @click="tab = 'reviews'"
                            :class="tab === 'reviews' ? 'border-indigo-600 text-indigo-600 bg-indigo-50' : 'border-transparent text-gray-600 hover:text-gray-900 hover:bg-gray-50'"
                            class="px-8 py-4 font-bold border-b-4 whitespace-nowrap transition-all flex items-center gap-2">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        รีวิวจากผู้ซื้อ ({{ $product->rating_count }})
                    </button>
                    <button @click="tab = 'shipping'"
                            :class="tab === 'shipping' ? 'border-indigo-600 text-indigo-600 bg-indigo-50' : 'border-transparent text-gray-600 hover:text-gray-900 hover:bg-gray-50'"
                            class="px-8 py-4 font-bold border-b-4 whitespace-nowrap transition-all flex items-center gap-2">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
                            <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"/>
                        </svg>
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

                    @if($product->brand || $product->weight || $product->dimensions)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-8">
                        @if($product->brand)
                        <div class="p-5 bg-gradient-to-br from-indigo-50 to-purple-50 rounded-2xl border border-indigo-100">
                            <h4 class="font-bold text-gray-900 mb-2 flex items-center gap-2">
                                <svg class="w-5 h-5 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                ยี่ห้อ
                            </h4>
                            <p class="text-gray-700 font-semibold text-lg">{{ $product->brand }}</p>
                        </div>
                        @endif

                        @if($product->weight || $product->dimensions)
                        <div class="p-5 bg-gradient-to-br from-green-50 to-emerald-50 rounded-2xl border border-green-100">
                            <h4 class="font-bold text-gray-900 mb-2 flex items-center gap-2">
                                <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                </svg>
                                ข้อมูลการจัดส่ง
                            </h4>
                            @if($product->weight)
                            <p class="text-gray-700">น้ำหนัก: <span class="font-semibold">{{ $product->weight }} กก.</span></p>
                            @endif
                            @if($product->dimensions)
                            <p class="text-gray-700">ขนาด: <span class="font-semibold">{{ $product->dimensions }}</span></p>
                            @endif
                        </div>
                        @endif
                    </div>
                    @endif
                </div>

                <!-- Reviews Tab -->
                <div x-show="tab === 'reviews'" class="space-y-6">
                    @if($product->approvedReviews && $product->approvedReviews->count() > 0)
                        @foreach($product->approvedReviews as $review)
                        <div class="border-b-2 border-gray-100 pb-6 last:border-0">
                            <div class="flex items-start gap-4">
                                <div class="w-14 h-14 bg-gradient-to-br from-indigo-400 to-purple-400 rounded-2xl flex items-center justify-center text-white font-bold text-xl flex-shrink-0">
                                    {{ substr($review->user->name ?? 'U', 0, 1) }}
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2 flex-wrap">
                                        <span class="font-bold text-gray-900 text-lg">{{ $review->user->name ?? 'ผู้ใช้' }}</span>
                                        <div class="flex">
                                            @for($i = 0; $i < 5; $i++)
                                                <svg class="w-4 h-4 {{ $i < $review->rating ? 'text-amber-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                </svg>
                                            @endfor
                                        </div>
                                        <span class="text-sm text-gray-500">{{ $review->created_at->diffForHumans() }}</span>
                                    </div>
                                    @if($review->comment)
                                    <p class="text-gray-700 leading-relaxed">{{ $review->comment }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    @else
                        <div class="text-center py-16">
                            <div class="inline-flex items-center justify-center w-20 h-20 bg-gray-100 rounded-full mb-4">
                                <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                                </svg>
                            </div>
                            <p class="text-gray-500 text-lg font-semibold">ยังไม่มีรีวิวสำหรับสินค้านี้</p>
                            @if($hasPurchased)
                            <button class="mt-6 px-8 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all">
                                เขียนรีวิว
                            </button>
                            @endif
                        </div>
                    @endif
                </div>

                <!-- Shipping Tab -->
                <div x-show="tab === 'shipping'" class="space-y-4">
                    <div class="flex items-start gap-5 p-6 bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl border border-blue-200">
                        <div class="w-14 h-14 bg-blue-600 rounded-2xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
                                <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 mb-2 text-lg">จัดส่งฟรี</h4>
                            <p class="text-gray-700 leading-relaxed">สำหรับคำสั่งซื้อที่มีมูลค่าตั้งแต่ 500 บาทขึ้นไป</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-5 p-6 bg-gradient-to-br from-green-50 to-emerald-50 rounded-2xl border border-green-200">
                        <div class="w-14 h-14 bg-green-600 rounded-2xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 mb-2 text-lg">จัดส่งรวดเร็ว</h4>
                            <p class="text-gray-700 leading-relaxed">ส่งสินค้าภายใน 1-2 วันทำการหลังจากชำระเงิน</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-5 p-6 bg-gradient-to-br from-orange-50 to-amber-50 rounded-2xl border border-orange-200">
                        <div class="w-14 h-14 bg-orange-600 rounded-2xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 mb-2 text-lg">ติดตามพัสดุได้</h4>
                            <p class="text-gray-700 leading-relaxed">ตรวจสอบสถานะการจัดส่งได้แบบเรียลไทม์ผ่านระบบของเรา</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Related Products -->
        @if($relatedProducts && $relatedProducts->count() > 0)
        <div class="mb-8">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-3xl font-black text-gray-900 flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-500 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
                        </svg>
                    </div>
                    สินค้าที่เกี่ยวข้อง
                </h2>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($relatedProducts as $relatedProduct)
                    <x-shop.product-card :product="$relatedProduct" />
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>

<script>
function changeMainImage(url) {
    const mainImage = document.getElementById('mainImage');
    mainImage.src = url;

    // Update active thumbnail
    document.querySelectorAll('.thumbnail-btn').forEach(btn => {
        btn.classList.remove('border-indigo-600');
        btn.classList.add('border-gray-300');
    });
    event.currentTarget.classList.remove('border-gray-300');
    event.currentTarget.classList.add('border-indigo-600');
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
    if (confirm('กรุณาเข้าสู่ระบบเพื่อเพิ่มสินค้าลงตะกร้า')) {
        window.location.href = '{{ route("login") }}';
    }
    return;
    @endguest

    const button = event.target;
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<svg class="animate-spin h-6 w-6 text-white mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';

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
    if (confirm('กรุณาเข้าสู่ระบบเพื่อทำการสั่งซื้อ')) {
        window.location.href = '{{ route("login") }}';
    }
    return;
    @endguest

    const button = event.target;
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<svg class="animate-spin h-6 w-6 text-white mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';

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
        navigator.clipboard.writeText(window.location.href);
        alert('คัดลอกลิงก์แล้ว!');
    }
}
</script>
@endsection
