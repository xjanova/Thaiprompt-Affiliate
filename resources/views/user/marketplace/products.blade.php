@extends('layouts.user-arrow-x')

@section('title', 'Marketplace Affiliate - สินค้าแนะนำสำหรับแอฟฟิลิเอท')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900">

    <!-- Hero Section -->
    <div class="relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-b from-transparent to-slate-900/50 z-10"></div>
        <div class="container mx-auto px-4 py-16 relative z-20">
            <div class="text-center mb-8">
                <div class="inline-block mb-6">
                    <div class="text-7xl mb-4 animate-bounce">🛍️</div>
                    <h1 class="text-5xl md:text-6xl font-bold bg-gradient-to-r from-yellow-400 via-amber-500 to-orange-500 bg-clip-text text-transparent drop-shadow-2xl mb-4">
                        Marketplace Affiliate
                    </h1>
                    <p class="text-xl text-yellow-100 font-semibold mb-4">แชร์สินค้า รับค่าคอมมิชชั่นสูง</p>
                    <p class="text-lg text-gray-300 max-w-3xl mx-auto">
                        เลือกสินค้าคุณภาพ แชร์ให้เพื่อน รับค่าคอมมิชชั่นทันที พร้อมโอกาสสร้างรายได้ไม่จำกัด
                    </p>
                </div>

                <!-- Quick Stats -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 max-w-4xl mx-auto mb-8">
                    <div class="bg-gradient-to-br from-blue-500/20 to-blue-600/20 backdrop-blur-sm rounded-xl p-4 border border-blue-400/30">
                        <div class="text-3xl mb-2">📦</div>
                        <div class="text-2xl font-bold text-blue-400">{{ $products->total() }}</div>
                        <div class="text-sm text-gray-300">สินค้าทั้งหมด</div>
                    </div>
                    <div class="bg-gradient-to-br from-green-500/20 to-green-600/20 backdrop-blur-sm rounded-xl p-4 border border-green-400/30">
                        <div class="text-3xl mb-2">💰</div>
                        <div class="text-2xl font-bold text-green-400">{{ number_format($products->max('commission_rate') ?? 0) }}%</div>
                        <div class="text-sm text-gray-300">คอมมิชชั่นสูงสุด</div>
                    </div>
                    <div class="bg-gradient-to-br from-purple-500/20 to-purple-600/20 backdrop-blur-sm rounded-xl p-4 border border-purple-400/30">
                        <div class="text-3xl mb-2">🎯</div>
                        <div class="text-2xl font-bold text-purple-400">{{ $categories->count() }}</div>
                        <div class="text-sm text-gray-300">หมวดหมู่</div>
                    </div>
                    <div class="bg-gradient-to-br from-amber-500/20 to-amber-600/20 backdrop-blur-sm rounded-xl p-4 border border-amber-400/30">
                        <div class="text-3xl mb-2">🚀</div>
                        <div class="text-2xl font-bold text-amber-400">∞</div>
                        <div class="text-sm text-gray-300">รายได้ไม่จำกัด</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="container mx-auto px-4 pb-8">
        <div class="bg-slate-800/50 backdrop-blur-sm rounded-2xl p-6 border border-slate-700/50 mb-8">
            <form method="GET" action="{{ route('user.marketplace.products') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Search -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">🔍 ค้นหา</label>
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="ชื่อสินค้า..."
                               class="w-full px-4 py-2 bg-slate-900/50 border border-slate-600 rounded-lg text-white placeholder-gray-400 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-500/50 transition-all">
                    </div>

                    <!-- Category -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">📁 หมวดหมู่</label>
                        <select name="category"
                                class="w-full px-4 py-2 bg-slate-900/50 border border-slate-600 rounded-lg text-white focus:border-yellow-500 focus:ring-2 focus:ring-yellow-500/50 transition-all">
                            <option value="">ทั้งหมด</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Price Range -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">💵 ราคา (฿)</label>
                        <div class="flex gap-2">
                            <input type="number" name="min_price" value="{{ request('min_price') }}"
                                   placeholder="ต่ำสุด"
                                   class="w-1/2 px-3 py-2 bg-slate-900/50 border border-slate-600 rounded-lg text-white placeholder-gray-400 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-500/50 transition-all">
                            <input type="number" name="max_price" value="{{ request('max_price') }}"
                                   placeholder="สูงสุด"
                                   class="w-1/2 px-3 py-2 bg-slate-900/50 border border-slate-600 rounded-lg text-white placeholder-gray-400 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-500/50 transition-all">
                        </div>
                    </div>

                    <!-- Sort By -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">🔄 เรียงตาม</label>
                        <select name="sort_by"
                                class="w-full px-4 py-2 bg-slate-900/50 border border-slate-600 rounded-lg text-white focus:border-yellow-500 focus:ring-2 focus:ring-yellow-500/50 transition-all">
                            <option value="newest" {{ request('sort_by') == 'newest' ? 'selected' : '' }}>ใหม่ล่าสุด</option>
                            <option value="popular" {{ request('sort_by') == 'popular' ? 'selected' : '' }}>ขายดี</option>
                            <option value="price_low" {{ request('sort_by') == 'price_low' ? 'selected' : '' }}>ราคาต่ำ-สูง</option>
                            <option value="price_high" {{ request('sort_by') == 'price_high' ? 'selected' : '' }}>ราคาสูง-ต่ำ</option>
                            <option value="commission_high" {{ request('sort_by') == 'commission_high' ? 'selected' : '' }}>คอมมิชชั่นสูงสุด</option>
                        </select>
                    </div>
                </div>

                <div class="flex gap-4">
                    <button type="submit"
                            class="px-6 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition-all font-semibold">
                        🔍 ค้นหา
                    </button>
                    <a href="{{ route('user.marketplace.products') }}"
                       class="px-6 py-2 bg-slate-700 text-white rounded-lg hover:bg-slate-600 transition-all font-semibold">
                        🔄 รีเซ็ต
                    </a>
                </div>
            </form>
        </div>

        <!-- Products Grid -->
        @if($products->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
                @foreach($products as $product)
                    <div class="group bg-gradient-to-br from-slate-800/90 to-slate-900/90 backdrop-blur-sm rounded-2xl shadow-xl border border-slate-700/50 hover:border-yellow-500/50 transition-all hover:scale-[1.02] overflow-hidden">
                        <!-- Product Image -->
                        <div class="relative h-48 bg-slate-900/50 overflow-hidden">
                            @if($product->primary_image)
                                <img src="{{ $product->primary_image }}"
                                     alt="{{ $product->name }}"
                                     class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-6xl">
                                    📦
                                </div>
                            @endif

                            <!-- Commission Badge -->
                            @if($product->commission_rate > 0)
                                <div class="absolute top-3 right-3 bg-gradient-to-r from-green-500 to-emerald-600 text-white px-3 py-1 rounded-full text-sm font-bold shadow-lg">
                                    💰 {{ number_format($product->commission_rate) }}%
                                </div>
                            @endif

                            <!-- Featured Badge -->
                            @if($product->is_featured)
                                <div class="absolute top-3 left-3 bg-gradient-to-r from-yellow-400 to-amber-500 text-slate-900 px-3 py-1 rounded-full text-sm font-bold shadow-lg">
                                    ⭐ แนะนำ
                                </div>
                            @endif
                        </div>

                        <!-- Product Info -->
                        <div class="p-5">
                            <!-- Category -->
                            @if($product->category)
                                <div class="text-xs text-gray-400 mb-2">
                                    {{ $product->category->name }}
                                </div>
                            @endif

                            <!-- Product Name -->
                            <h3 class="text-lg font-bold text-white mb-2 line-clamp-2 group-hover:text-yellow-400 transition-colors">
                                {{ $product->name }}
                            </h3>

                            <!-- Short Description -->
                            @if($product->short_description)
                                <p class="text-sm text-gray-400 mb-3 line-clamp-2">
                                    {{ $product->short_description }}
                                </p>
                            @endif

                            <!-- Price -->
                            <div class="flex items-baseline gap-2 mb-4">
                                <span class="text-2xl font-bold text-yellow-400">
                                    ฿{{ number_format($product->price, 2) }}
                                </span>
                                @if($product->compare_at_price && $product->compare_at_price > $product->price)
                                    <span class="text-sm text-gray-500 line-through">
                                        ฿{{ number_format($product->compare_at_price, 2) }}
                                    </span>
                                    <span class="text-xs bg-red-500/20 text-red-400 px-2 py-1 rounded-full font-bold">
                                        -{{ $product->discount_percentage }}%
                                    </span>
                                @endif
                            </div>

                            <!-- Commission Calculation -->
                            @if($product->commission_rate > 0)
                                <div class="bg-green-500/10 border border-green-500/30 rounded-lg p-3 mb-4">
                                    <div class="text-xs text-green-300 mb-1">คอมมิชชั่นที่คุณจะได้รับ:</div>
                                    <div class="text-lg font-bold text-green-400">
                                        ฿{{ number_format($product->calculateCommission($product->price), 2) }}
                                    </div>
                                </div>
                            @endif

                            <!-- Stats -->
                            <div class="flex items-center justify-between text-xs text-gray-400 mb-4">
                                <span>👁️ {{ number_format($product->view_count) }}</span>
                                <span>🛒 {{ number_format($product->sales_count) }} ขาย</span>
                                @if($product->rating_count > 0)
                                    <span>⭐ {{ number_format($product->rating_average, 1) }}</span>
                                @endif
                            </div>

                            <!-- CTA Button -->
                            <a href="{{ route('shop.show', $product->slug) }}"
                               target="_blank"
                               class="block w-full px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition-all text-center font-semibold">
                                🚀 ดูสินค้าและแชร์
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="flex justify-center">
                {{ $products->links() }}
            </div>
        @else
            <!-- Empty State -->
            <div class="text-center py-16">
                <div class="text-8xl mb-6">🔍</div>
                <h3 class="text-2xl font-bold text-white mb-4">ไม่พบสินค้า</h3>
                <p class="text-gray-400 mb-6">ลองปรับเกณฑ์การค้นหาใหม่อีกครั้ง</p>
                <a href="{{ route('user.marketplace.products') }}"
                   class="inline-block px-6 py-3 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition-all font-semibold">
                    🔄 ดูสินค้าทั้งหมด
                </a>
            </div>
        @endif
    </div>

    <!-- How It Works Section -->
    <div class="container mx-auto px-4 py-16">
        <div class="bg-gradient-to-br from-slate-800/90 to-slate-900/90 backdrop-blur-sm rounded-2xl p-8 border border-slate-700/50">
            <h2 class="text-3xl font-bold text-center text-yellow-400 mb-8">💡 วิธีสร้างรายได้จาก Marketplace Affiliate</h2>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="text-center">
                    <div class="text-5xl mb-4">1️⃣</div>
                    <h3 class="text-xl font-bold text-white mb-2">เลือกสินค้า</h3>
                    <p class="text-gray-400">เลือกสินค้าที่คุณสนใจจากรายการด้านบน</p>
                </div>

                <div class="text-center">
                    <div class="text-5xl mb-4">2️⃣</div>
                    <h3 class="text-xl font-bold text-white mb-2">แชร์ลิงก์</h3>
                    <p class="text-gray-400">แชร์ลิงก์สินค้าให้เพื่อนและครอบครัว</p>
                </div>

                <div class="text-center">
                    <div class="text-5xl mb-4">3️⃣</div>
                    <h3 class="text-xl font-bold text-white mb-2">มีคนซื้อ</h3>
                    <p class="text-gray-400">เมื่อมีคนซื้อผ่านลิงก์ของคุณ</p>
                </div>

                <div class="text-center">
                    <div class="text-5xl mb-4">4️⃣</div>
                    <h3 class="text-xl font-bold text-white mb-2">รับค่าคอมฯ</h3>
                    <p class="text-gray-400">คุณจะได้รับค่าคอมมิชชั่นทันที</p>
                </div>
            </div>

            <div class="mt-8 p-6 bg-yellow-500/10 border border-yellow-500/30 rounded-xl">
                <div class="text-center">
                    <div class="text-4xl mb-3">💰</div>
                    <h3 class="text-xl font-bold text-yellow-400 mb-2">ตัวอย่างรายได้</h3>
                    <p class="text-gray-300">
                        แชร์สินค้าราคา <strong class="text-yellow-400">1,000฿</strong>
                        คอมมิชชั่น <strong class="text-yellow-400">10%</strong>
                        มีคนซื้อ <strong class="text-yellow-400">100</strong> คน
                    </p>
                    <p class="text-2xl font-bold text-green-400 mt-3">
                        = รายได้ 10,000฿
                    </p>
                </div>
            </div>
        </div>
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
@endsection
