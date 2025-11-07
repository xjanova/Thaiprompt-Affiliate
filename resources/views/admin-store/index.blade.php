@extends('layouts.app')

@section('title', $adminStore->store_name . ' - ร้านค้าของแอดมิน')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-purple-50 to-blue-50">
    <!-- Hero Section with Store Banner -->
    <div class="relative overflow-hidden bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 shadow-2xl">
        @if($adminStore->store_banner)
            <div class="absolute inset-0">
                <img src="{{ $adminStore->banner_url }}" alt="{{ $adminStore->store_name }}" class="w-full h-full object-cover opacity-30">
            </div>
        @else
            <div class="absolute inset-0 opacity-20">
                <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.4\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
            </div>
        @endif

        <div class="container mx-auto px-4 py-16 md:py-24 relative">
            <div class="max-w-5xl mx-auto">
                <div class="flex flex-col md:flex-row items-center gap-8 text-white">
                    @if($adminStore->store_logo)
                        <div class="w-32 h-32 md:w-40 md:h-40 bg-white/20 backdrop-blur-lg rounded-3xl shadow-2xl p-4 border-4 border-white/30">
                            <img src="{{ $adminStore->logo_url }}" alt="{{ $adminStore->store_name }}" class="w-full h-full object-contain">
                        </div>
                    @else
                        <div class="w-32 h-32 md:w-40 md:h-40 bg-white/20 backdrop-blur-lg rounded-3xl shadow-2xl flex items-center justify-center text-6xl md:text-7xl border-4 border-white/30">
                            🏪
                        </div>
                    @endif

                    <div class="flex-1 text-center md:text-left">
                        <div class="inline-flex items-center gap-2 bg-white/20 backdrop-blur-lg px-4 py-2 rounded-full mb-4 border border-white/30">
                            <span class="text-yellow-300 text-xl">⭐</span>
                            <span class="font-semibold">Official Store</span>
                        </div>
                        <h1 class="text-4xl md:text-6xl font-black mb-4 tracking-tight drop-shadow-lg">
                            @if($adminStore->user && $adminStore->user->is_super_admin)
                                TP-MALL
                            @else
                                {{ $adminStore->store_name }}
                            @endif
                        </h1>
                        @if($adminStore->store_description)
                            <p class="text-xl md:text-2xl text-purple-100 mb-6 font-medium">
                                {{ $adminStore->store_description }}
                            </p>
                        @endif
                        <div class="flex flex-wrap gap-4 justify-center md:justify-start">
                            <div class="flex items-center gap-2 bg-white/20 backdrop-blur-lg px-4 py-2 rounded-full border border-white/30">
                                <span class="text-2xl">📦</span>
                                <span class="font-semibold">{{ $adminStore->total_products }} สินค้า</span>
                            </div>
                            <div class="flex items-center gap-2 bg-white/20 backdrop-blur-lg px-4 py-2 rounded-full border border-white/30">
                                <span class="text-2xl">⭐</span>
                                <span class="font-semibold">{{ number_format($adminStore->rating_average, 1) }} ({{ $adminStore->rating_count }})</span>
                            </div>
                            <div class="flex items-center gap-2 bg-white/20 backdrop-blur-lg px-4 py-2 rounded-full border border-white/30">
                                <span class="text-2xl">✓</span>
                                <span class="font-semibold">Verified</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="absolute bottom-0 left-0 right-0 h-20 bg-gradient-to-t from-slate-50 to-transparent"></div>
    </div>

    <div class="container mx-auto px-4 py-8 -mt-10 relative z-10">
        <!-- Featured Products -->
        @if($featuredProducts->count() > 0)
            <div class="mb-12">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-3xl font-black text-gray-800 mb-2">⭐ สินค้าแนะนำ</h2>
                        <p class="text-gray-600">สินค้าคุณภาพสูง คัดสรรมาเพื่อคุณ</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    @foreach($featuredProducts as $product)
                        @include('admin-store.partials.product-card', ['product' => $product, 'featured' => true])
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Search & Filters -->
        <div class="bg-white rounded-3xl shadow-2xl p-6 md:p-8 mb-8 border border-gray-100">
            <form method="GET" action="{{ route('admin-store.index') }}">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
                    <!-- Search -->
                    <div class="lg:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">🔍 ค้นหาสินค้า</label>
                        <input type="text"
                               name="search"
                               class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 transition-all"
                               placeholder="ค้นหาชื่อสินค้า..."
                               value="{{ request('search') }}">
                    </div>

                    <!-- Category -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">📂 หมวดหมู่</label>
                        <select name="category" class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 transition-all">
                            <option value="">ทั้งหมด</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }} ({{ $category->products_count }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Price Range -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">💰 ราคาต่ำสุด</label>
                        <input type="number"
                               name="min_price"
                               class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 transition-all"
                               placeholder="0"
                               value="{{ request('min_price') }}">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">💰 ราคาสูงสุด</label>
                        <input type="number"
                               name="max_price"
                               class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 transition-all"
                               placeholder="∞"
                               value="{{ request('max_price') }}">
                    </div>

                    <!-- Sort -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">📊 เรียงตาม</label>
                        <select name="sort_by" class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 transition-all">
                            <option value="newest" {{ request('sort_by') == 'newest' ? 'selected' : '' }}>ใหม่ล่าสุด</option>
                            <option value="popular" {{ request('sort_by') == 'popular' ? 'selected' : '' }}>ยอดนิยม</option>
                            <option value="price_low" {{ request('sort_by') == 'price_low' ? 'selected' : '' }}>ราคาต่ำ-สูง</option>
                            <option value="price_high" {{ request('sort_by') == 'price_high' ? 'selected' : '' }}>ราคาสูง-ต่ำ</option>
                            <option value="rating" {{ request('sort_by') == 'rating' ? 'selected' : '' }}>คะแนนสูงสุด</option>
                        </select>
                    </div>
                </div>

                <div class="mt-4 flex gap-3">
                    <button type="submit" class="px-8 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200">
                        ค้นหา
                    </button>
                    <a href="{{ route('admin-store.index') }}" class="px-8 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl shadow hover:shadow-lg transition-all duration-200">
                        รีเซ็ต
                    </a>
                </div>
            </form>
        </div>

        <!-- Results Header -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-gradient-to-r from-indigo-600 to-purple-600 rounded-xl flex items-center justify-center text-white font-bold shadow-lg">
                    {{ $products->total() }}
                </div>
                <div>
                    <p class="text-lg font-bold text-gray-800">พบ {{ $products->total() }} สินค้า</p>
                    <p class="text-sm text-gray-500">พร้อมจัดส่ง</p>
                </div>
            </div>
        </div>

        <!-- Products Grid -->
        @if($products->count() > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                @foreach($products as $product)
                    @include('admin-store.partials.product-card', ['product' => $product, 'featured' => false])
                @endforeach
            </div>
        @else
            <!-- Empty State -->
            <div class="bg-white rounded-3xl shadow-2xl p-12 text-center">
                <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-indigo-100 to-purple-100 rounded-full mb-6">
                    <span class="text-5xl">🔍</span>
                </div>
                <h3 class="text-2xl font-bold text-gray-800 mb-2">ไม่พบสินค้าที่ตรงกับเงื่อนไข</h3>
                <p class="text-gray-600 mb-6">ลองปรับเปลี่ยนตัวกรองหรือค้นหาด้วยคำค้นอื่น</p>
                <a href="{{ route('admin-store.index') }}" class="inline-block px-8 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200">
                    ดูสินค้าทั้งหมด
                </a>
            </div>
        @endif

        <!-- Pagination -->
        @if($products->hasPages())
            <div class="flex justify-center">
                <div class="bg-white rounded-2xl shadow-lg p-4">
                    {{ $products->links() }}
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
