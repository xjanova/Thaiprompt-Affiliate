@extends('layouts.user')

@section('title', 'ไปช๊อปปิ้ง')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-green-50 via-teal-50 to-blue-50 py-8">
    <div class="container mx-auto px-4">
        <!-- Hero Section -->
        <div class="text-center mb-8">
            <h1 class="text-5xl font-bold bg-gradient-to-r from-green-600 to-teal-600 bg-clip-text text-transparent mb-4">
                🛒 ร้านค้าหลัก
            </h1>
            <p class="text-xl text-gray-700 mb-2">สินค้าคุณภาพจากระบบหลัก</p>
            <p class="text-gray-600">ช้อปสินค้าแล้วรับคอมมิชชั่น!</p>
        </div>

        <div class="flex flex-col lg:flex-row gap-6">
            <!-- Sidebar Filters -->
            <div class="lg:w-64 space-y-6">
                <!-- Search -->
                <div class="bg-white rounded-2xl shadow-xl p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">🔍 ค้นหา</h3>
                    <form method="GET" action="{{ route('user.shop.index') }}">
                        <input type="text"
                               name="search"
                               value="{{ request('search') }}"
                               placeholder="ค้นหาสินค้า..."
                               class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-4 focus:ring-green-200 focus:border-green-500">
                        <button type="submit"
                                class="w-full mt-3 bg-gradient-to-r from-green-600 to-teal-600 hover:from-green-700 hover:to-teal-700 text-white font-bold py-2 rounded-lg transition-all duration-300">
                            ค้นหา
                        </button>
                    </form>
                </div>

                <!-- Categories -->
                @if($categories->count() > 0)
                <div class="bg-white rounded-2xl shadow-xl p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">📁 หมวดหมู่</h3>
                    <div class="space-y-2">
                        <a href="{{ route('user.shop.index') }}"
                           class="block px-4 py-2 rounded-lg transition-all duration-200 {{ !request('category') ? 'bg-gradient-to-r from-green-600 to-teal-600 text-white' : 'hover:bg-gray-100 text-gray-700' }}">
                            ทั้งหมด
                        </a>
                        @foreach($categories as $category)
                        <a href="{{ route('user.shop.index', ['category' => $category->id]) }}"
                           class="block px-4 py-2 rounded-lg transition-all duration-200 {{ request('category') == $category->id ? 'bg-gradient-to-r from-green-600 to-teal-600 text-white' : 'hover:bg-gray-100 text-gray-700' }}">
                            {{ $category->name }}
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Price Range -->
                <div class="bg-white rounded-2xl shadow-xl p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">💰 ราคา</h3>
                    <form method="GET" action="{{ route('user.shop.index') }}" class="space-y-3">
                        <input type="hidden" name="search" value="{{ request('search') }}">
                        <input type="hidden" name="category" value="{{ request('category') }}">
                        <input type="hidden" name="sort" value="{{ request('sort') }}">

                        <div>
                            <label class="block text-sm text-gray-600 mb-1">ราคาต่ำสุด</label>
                            <input type="number"
                                   name="min_price"
                                   value="{{ request('min_price') }}"
                                   placeholder="0"
                                   class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-green-200 focus:border-green-500">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">ราคาสูงสุด</label>
                            <input type="number"
                                   name="max_price"
                                   value="{{ request('max_price') }}"
                                   placeholder="999999"
                                   class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-green-200 focus:border-green-500">
                        </div>
                        <button type="submit"
                                class="w-full bg-gradient-to-r from-green-600 to-teal-600 hover:from-green-700 hover:to-teal-700 text-white font-bold py-2 rounded-lg transition-all duration-300">
                            กรอง
                        </button>
                    </form>
                </div>
            </div>

            <!-- Products Grid -->
            <div class="flex-1">
                <!-- Sorting & Results Count -->
                <div class="bg-white rounded-2xl shadow-xl p-4 mb-6 flex flex-col sm:flex-row justify-between items-center gap-4">
                    <div class="text-gray-700">
                        แสดง <span class="font-bold">{{ $products->total() }}</span> สินค้า
                    </div>
                    <div class="flex items-center gap-2">
                        <label class="text-sm text-gray-600">เรียงโดย:</label>
                        <form method="GET" action="{{ route('user.shop.index') }}" id="sortForm">
                            <input type="hidden" name="search" value="{{ request('search') }}">
                            <input type="hidden" name="category" value="{{ request('category') }}">
                            <input type="hidden" name="min_price" value="{{ request('min_price') }}">
                            <input type="hidden" name="max_price" value="{{ request('max_price') }}">

                            <select name="sort"
                                    onchange="document.getElementById('sortForm').submit()"
                                    class="px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-green-200 focus:border-green-500">
                                <option value="latest" {{ request('sort') == 'latest' ? 'selected' : '' }}>ล่าสุด</option>
                                <option value="popular" {{ request('sort') == 'popular' ? 'selected' : '' }}>ยอดนิยม</option>
                                <option value="price_low" {{ request('sort') == 'price_low' ? 'selected' : '' }}>ราคาต่ำ-สูง</option>
                                <option value="price_high" {{ request('sort') == 'price_high' ? 'selected' : '' }}>ราคาสูง-ต่ำ</option>
                                <option value="rating" {{ request('sort') == 'rating' ? 'selected' : '' }}>คะแนนสูงสุด</option>
                            </select>
                        </form>
                    </div>
                </div>

                @if($products->count() > 0)
                    <!-- Products Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-6">
                        @foreach($products as $product)
                        <div class="bg-white rounded-2xl shadow-xl overflow-hidden hover:shadow-2xl transition-all duration-300 transform hover:scale-105">
                            <a href="{{ route('user.shop.show', $product->slug) }}">
                                <!-- Product Image -->
                                <div class="relative h-48 bg-gray-200 overflow-hidden">
                                    @if($product->main_image_url)
                                        <img src="{{ asset($product->main_image_url) }}"
                                             alt="{{ $product->name }}"
                                             class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-6xl">
                                            📦
                                        </div>
                                    @endif

                                    <!-- Badges -->
                                    <div class="absolute top-2 right-2 flex flex-col gap-2">
                                        @if($product->is_featured)
                                        <span class="bg-yellow-500 text-white text-xs font-bold px-2 py-1 rounded-full">
                                            ⭐ แนะนำ
                                        </span>
                                        @endif

                                        @if($product->discount_percentage)
                                        <span class="bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full">
                                            -{{ $product->discount_percentage }}%
                                        </span>
                                        @endif

                                        @if($product->isLowStock())
                                        <span class="bg-orange-500 text-white text-xs font-bold px-2 py-1 rounded-full">
                                            ⚠️ เหลือน้อย
                                        </span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Product Info -->
                                <div class="p-4">
                                    <!-- Category -->
                                    @if($product->category)
                                    <div class="text-xs text-gray-500 mb-2">
                                        {{ $product->category->name }}
                                    </div>
                                    @endif

                                    <!-- Product Name -->
                                    <h3 class="font-bold text-gray-800 mb-2 line-clamp-2 min-h-[3rem]">
                                        {{ $product->name }}
                                    </h3>

                                    <!-- Rating -->
                                    @if($product->rating_count > 0)
                                    <div class="flex items-center gap-2 mb-2">
                                        <div class="flex text-yellow-400">
                                            @for($i = 1; $i <= 5; $i++)
                                                @if($i <= floor($product->rating_average))
                                                    ⭐
                                                @else
                                                    ☆
                                                @endif
                                            @endfor
                                        </div>
                                        <span class="text-xs text-gray-600">
                                            ({{ $product->rating_count }})
                                        </span>
                                    </div>
                                    @endif

                                    <!-- Price -->
                                    <div class="flex items-center gap-2">
                                        <span class="text-2xl font-bold text-green-600">
                                            ฿{{ number_format($product->price, 2) }}
                                        </span>
                                        @if($product->compare_at_price && $product->compare_at_price > $product->price)
                                        <span class="text-sm text-gray-400 line-through">
                                            ฿{{ number_format($product->compare_at_price, 2) }}
                                        </span>
                                        @endif
                                    </div>

                                    <!-- Stock Status -->
                                    <div class="mt-3">
                                        @if($product->isInStock())
                                        <span class="text-xs text-green-600 font-semibold">
                                            ✓ มีสินค้า
                                        </span>
                                        @else
                                        <span class="text-xs text-red-600 font-semibold">
                                            ✗ สินค้าหมด
                                        </span>
                                        @endif
                                    </div>

                                    <!-- View Button -->
                                    <button class="w-full mt-4 bg-gradient-to-r from-green-600 to-teal-600 hover:from-green-700 hover:to-teal-700 text-white font-bold py-2 rounded-lg transition-all duration-300">
                                        ดูรายละเอียด
                                    </button>
                                </div>
                            </a>
                        </div>
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    <div class="bg-white rounded-2xl shadow-xl p-6">
                        {{ $products->links() }}
                    </div>
                @else
                    <!-- No Products -->
                    <div class="bg-white rounded-2xl shadow-xl p-12 text-center">
                        <div class="text-6xl mb-4">📦</div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-2">ไม่พบสินค้า</h3>
                        <p class="text-gray-600 mb-6">ลองเปลี่ยนเงื่อนไขการค้นหาหรือหมวดหมู่</p>
                        <a href="{{ route('user.shop.index') }}"
                           class="inline-block bg-gradient-to-r from-green-600 to-teal-600 hover:from-green-700 hover:to-teal-700 text-white font-bold px-8 py-3 rounded-lg transition-all duration-300">
                            ดูสินค้าทั้งหมด
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
