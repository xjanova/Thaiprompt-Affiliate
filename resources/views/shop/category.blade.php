@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Category Hero Section -->
    <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 text-white py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Breadcrumb -->
            <nav class="mb-6" aria-label="Breadcrumb">
                <ol class="flex items-center space-x-2 text-sm text-indigo-100">
                    <li>
                        <a href="{{ route('home') }}" class="hover:text-white transition">
                            หน้าแรก
                        </a>
                    </li>
                    <li>/</li>
                    <li>
                        <a href="{{ route('shop.index') }}" class="hover:text-white transition">
                            ร้านค้า
                        </a>
                    </li>
                    <li>/</li>
                    <li class="text-white font-medium">{{ $category->name }}</li>
                </ol>
            </nav>

            <h1 class="text-4xl md:text-5xl font-black mb-4">{{ $category->name }}</h1>
            @if($category->description)
            <p class="text-xl text-indigo-100 max-w-3xl">{{ $category->description }}</p>
            @endif
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Category Info -->
        @if($category->image_url)
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden mb-8">
            <div class="aspect-[21/9] bg-gradient-to-br from-gray-50 to-gray-100">
                <img src="{{ $category->image_url }}"
                     alt="{{ $category->name }}"
                     class="w-full h-full object-cover">
            </div>
        </div>
        @endif

        <!-- Subcategories (if any) -->
        @if($category->children && $category->children->count() > 0)
        <div class="mb-12">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">หมวดหมู่ย่อย</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                @foreach($category->children as $subcategory)
                <a href="{{ route('shop.category', $subcategory->slug) }}"
                   class="bg-white rounded-xl shadow-md hover:shadow-xl p-6 text-center transform hover:-translate-y-1 transition-all duration-300 group">
                    @if($subcategory->icon)
                    <div class="text-4xl mb-3">{{ $subcategory->icon }}</div>
                    @else
                    <div class="text-4xl mb-3">📦</div>
                    @endif
                    <h3 class="font-bold text-gray-900 group-hover:text-indigo-600 transition">
                        {{ $subcategory->name }}
                    </h3>
                </a>
                @endforeach
            </div>
        </div>
        @endif

        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Filters Sidebar -->
            <aside class="lg:w-64 flex-shrink-0">
                <div class="bg-white rounded-2xl shadow-lg p-6 sticky top-4">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">ตัวกรอง</h2>

                    <form method="GET" action="{{ route('shop.category', $category->slug) }}" id="filterForm">
                        <!-- Search -->
                        <div class="mb-6">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">ค้นหา</label>
                            <input type="text"
                                   name="search"
                                   value="{{ request('search') }}"
                                   placeholder="ชื่อสินค้า..."
                                   class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-indigo-600 focus:outline-none">
                        </div>

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
                        @if(isset($brands) && $brands && $brands->count() > 0)
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
                        @if(request()->hasAny(['search', 'min_price', 'max_price', 'brand']))
                        <a href="{{ route('shop.category', $category->slug) }}"
                           class="block w-full text-center px-6 py-3 mt-3 border-2 border-gray-300 hover:border-red-500 text-gray-700 hover:text-red-500 font-semibold rounded-xl transition">
                            ล้างตัวกรอง
                        </a>
                        @endif
                    </form>
                </div>
            </aside>

            <!-- Products Grid -->
            <div class="flex-1">
                <!-- Sort and Results Count -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                    <div class="text-gray-600">
                        แสดง <span class="font-semibold">{{ $products->count() }}</span> จาก <span class="font-semibold">{{ $products->total() }}</span> สินค้า
                    </div>

                    <form method="GET" action="{{ route('shop.category', $category->slug) }}" class="flex items-center gap-3">
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
                    <div class="text-8xl mb-6">📭</div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">ยังไม่มีสินค้าในหมวดหมู่นี้</h3>
                    <p class="text-gray-600 mb-6">กำลังเพิ่มสินค้าใหม่เร็วๆ นี้</p>
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
