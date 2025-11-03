@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Hero Section -->
    <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 text-white py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-4xl md:text-5xl font-black mb-4">ร้านค้าออนไลน์</h1>
            <p class="text-xl text-indigo-100">ค้นพบสินค้าคุณภาพมากมาย ในราคาที่คุ้มค่า</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Filters Sidebar -->
            <aside class="lg:w-64 flex-shrink-0">
                <div class="bg-white rounded-2xl shadow-lg p-6 sticky top-4">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">ตัวกรอง</h2>

                    <form method="GET" action="{{ route('shop.index') }}" id="filterForm">
                        <!-- Search -->
                        <div class="mb-6">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">ค้นหา</label>
                            <input type="text"
                                   name="search"
                                   value="{{ request('search') }}"
                                   placeholder="ชื่อสินค้า, SKU..."
                                   class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-indigo-600 focus:outline-none">
                        </div>

                        <!-- Category Filter -->
                        @if($categories && $categories->count() > 0)
                        <div class="mb-6">
                            <label class="block text-sm font-semibold text-gray-700 mb-3">หมวดหมู่</label>
                            <div class="space-y-2 max-h-64 overflow-y-auto">
                                @foreach($categories as $category)
                                <label class="flex items-center gap-2 cursor-pointer hover:bg-gray-50 p-2 rounded-lg transition">
                                    <input type="radio"
                                           name="category"
                                           value="{{ $category->slug }}"
                                           {{ request('category') === $category->slug ? 'checked' : '' }}
                                           onchange="document.getElementById('filterForm').submit()"
                                           class="w-4 h-4 text-indigo-600">
                                    <span class="text-sm text-gray-700">{{ $category->name }}</span>
                                </label>
                                @endforeach
                                @if(request('category'))
                                <button type="button"
                                        onclick="document.querySelector('input[name=category]:checked').checked = false; document.getElementById('filterForm').submit()"
                                        class="text-sm text-indigo-600 hover:text-indigo-800 font-semibold">
                                    ล้างหมวดหมู่
                                </button>
                                @endif
                            </div>
                        </div>
                        @endif

                        <!-- Price Range -->
                        <div class="mb-6">
                            <label class="block text-sm font-semibold text-gray-700 mb-3">ช่วงราคา</label>
                            <div class="space-y-2">
                                <input type="number"
                                       name="min_price"
                                       value="{{ request('min_price') }}"
                                       placeholder="ราคาต่ำสุด"
                                       min="0"
                                       class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-indigo-600 focus:outline-none">
                                <input type="number"
                                       name="max_price"
                                       value="{{ request('max_price') }}"
                                       placeholder="ราคาสูงสุด"
                                       min="0"
                                       class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-indigo-600 focus:outline-none">
                            </div>
                        </div>

                        <!-- Brand Filter -->
                        @if($brands && $brands->count() > 0)
                        <div class="mb-6">
                            <label class="block text-sm font-semibold text-gray-700 mb-3">ยี่ห้อ</label>
                            <select name="brand"
                                    onchange="document.getElementById('filterForm').submit()"
                                    class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-indigo-600 focus:outline-none">
                                <option value="">ทุกยี่ห้อ</option>
                                @foreach($brands as $brand)
                                <option value="{{ $brand }}" {{ request('brand') === $brand ? 'selected' : '' }}>
                                    {{ $brand }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        <!-- Apply Filters Button -->
                        <button type="submit"
                                class="w-full px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-bold rounded-xl shadow-lg transition-all">
                            ค้นหา
                        </button>

                        <!-- Clear Filters -->
                        @if(request()->hasAny(['search', 'category', 'min_price', 'max_price', 'brand']))
                        <a href="{{ route('shop.index') }}"
                           class="block w-full text-center px-6 py-3 mt-3 border-2 border-gray-300 hover:border-red-500 text-gray-700 hover:text-red-500 font-semibold rounded-xl transition">
                            ล้างตัวกรอง
                        </a>
                        @endif
                    </form>
                </div>
            </aside>

            <!-- Products Grid -->
            <div class="flex-1">
                <!-- Featured Products -->
                @if($featuredProducts && $featuredProducts->count() > 0 && !request()->hasAny(['search', 'category', 'min_price', 'max_price', 'brand', 'sort_by']))
                <div class="mb-12">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">⭐ สินค้าแนะนำ</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        @foreach($featuredProducts as $product)
                        @include('admin-store.partials.product-card', ['product' => $product, 'featured' => true])
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Sort and Results Count -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                    <div class="text-gray-600">
                        แสดง <span class="font-semibold">{{ $products->count() }}</span> จาก <span class="font-semibold">{{ $products->total() }}</span> สินค้า
                    </div>

                    <form method="GET" action="{{ route('shop.index') }}" class="flex items-center gap-3">
                        @foreach(request()->except(['sort_by', 'page']) as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach

                        <label class="text-sm font-semibold text-gray-700">เรียงตาม:</label>
                        <select name="sort_by"
                                onchange="this.form.submit()"
                                class="px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-indigo-600 focus:outline-none font-medium">
                            <option value="newest" {{ request('sort_by') === 'newest' ? 'selected' : '' }}>ใหม่ล่าสุด</option>
                            <option value="popular" {{ request('sort_by') === 'popular' ? 'selected' : '' }}>ยอดนิยม</option>
                            <option value="price_low" {{ request('sort_by') === 'price_low' ? 'selected' : '' }}>ราคาต่ำ-สูง</option>
                            <option value="price_high" {{ request('sort_by') === 'price_high' ? 'selected' : '' }}>ราคาสูง-ต่ำ</option>
                            <option value="rating" {{ request('sort_by') === 'rating' ? 'selected' : '' }}>คะแนนสูงสุด</option>
                        </select>
                    </form>
                </div>

                <!-- Products Grid -->
                @if($products->count() > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                    @foreach($products as $product)
                    @include('admin-store.partials.product-card', ['product' => $product])
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="flex justify-center">
                    {{ $products->appends(request()->query())->links() }}
                </div>
                @else
                <!-- No Results -->
                <div class="bg-white rounded-2xl shadow-lg p-12 text-center">
                    <div class="text-8xl mb-6">🔍</div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">ไม่พบสินค้าที่ค้นหา</h3>
                    <p class="text-gray-600 mb-6">ลองปรับเปลี่ยนเงื่อนไขการค้นหาหรือลองใหม่อีกครั้ง</p>
                    <a href="{{ route('shop.index') }}"
                       class="inline-block px-8 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-bold rounded-xl shadow-lg transition">
                        ดูสินค้าทั้งหมด
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
