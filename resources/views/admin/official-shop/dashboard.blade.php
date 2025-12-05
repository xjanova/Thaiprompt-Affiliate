{{--
    Official Shop Dashboard - Admin Panel
    แดชบอร์ดจัดการร้านของระบบ (Premium V3)
--}}

@extends('layouts.admin-v3')

@section('title', 'Official Shop Dashboard')

@section('content')
<div class="space-y-6">
    {{-- Header Section --}}
    <div class="relative overflow-hidden bg-gradient-to-br from-amber-500 via-purple-600 to-pink-600 dark:from-amber-600 dark:via-purple-700 dark:to-pink-700 rounded-2xl shadow-2xl p-8">
        {{-- Background Pattern --}}
        <div class="absolute inset-0 bg-black/10 dark:bg-black/20" style="background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,0.15) 1px, transparent 0); background-size: 40px 40px;"></div>

        <div class="relative flex flex-col md:flex-row md:justify-between md:items-center gap-4">
            <div class="flex items-center gap-4">
                <div class="bg-white/20 backdrop-blur-sm rounded-2xl p-4 ring-4 ring-amber-400/30">
                    <i class="fas fa-crown text-4xl text-amber-300"></i>
                </div>
                <div class="text-white">
                    <h1 class="text-3xl md:text-4xl font-bold mb-1">Official Shop</h1>
                    <p class="text-white/90 text-sm md:text-base">จัดการสินค้าร้านทางการของระบบ</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.official-shop.products.create') }}"
                   class="px-6 py-3 bg-white text-purple-600 font-bold rounded-xl shadow-lg
                          hover:bg-purple-50 transition-all flex items-center gap-2">
                    <i class="fas fa-plus"></i>
                    เพิ่มสินค้าใหม่
                </a>
                <a href="{{ route('official-shop.index') }}" target="_blank"
                   class="px-6 py-3 bg-white/20 backdrop-blur-sm text-white font-bold rounded-xl
                          hover:bg-white/30 transition-all flex items-center gap-2 border border-white/30">
                    <i class="fas fa-external-link-alt"></i>
                    ดูหน้าร้าน
                </a>
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        {{-- Total Products --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg border border-gray-100 dark:border-gray-700">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center">
                    <i class="fas fa-boxes text-2xl text-white"></i>
                </div>
                <div>
                    <p class="text-3xl font-black text-gray-900 dark:text-white">{{ number_format($stats['total_products']) }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">สินค้าทั้งหมด</p>
                </div>
            </div>
        </div>

        {{-- Active Products --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg border border-gray-100 dark:border-gray-700">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center">
                    <i class="fas fa-check-circle text-2xl text-white"></i>
                </div>
                <div>
                    <p class="text-3xl font-black text-gray-900 dark:text-white">{{ number_format($stats['active_products']) }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">เปิดใช้งาน</p>
                </div>
            </div>
        </div>

        {{-- Featured Products --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg border border-gray-100 dark:border-gray-700">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center">
                    <i class="fas fa-star text-2xl text-white"></i>
                </div>
                <div>
                    <p class="text-3xl font-black text-gray-900 dark:text-white">{{ number_format($stats['featured_products']) }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">สินค้าแนะนำ</p>
                </div>
            </div>
        </div>

        {{-- Out of Stock --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg border border-gray-100 dark:border-gray-700">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-red-500 to-rose-600 flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-2xl text-white"></i>
                </div>
                <div>
                    <p class="text-3xl font-black text-gray-900 dark:text-white">{{ number_format($stats['out_of_stock']) }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">สินค้าหมด</p>
                </div>
            </div>
        </div>

        {{-- Total Views --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg border border-gray-100 dark:border-gray-700">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-purple-500 to-violet-600 flex items-center justify-center">
                    <i class="fas fa-eye text-2xl text-white"></i>
                </div>
                <div>
                    <p class="text-3xl font-black text-gray-900 dark:text-white">{{ number_format($stats['total_views']) }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">ยอดเข้าชม</p>
                </div>
            </div>
        </div>

        {{-- Total Sales --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg border border-gray-100 dark:border-gray-700">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-pink-500 to-rose-600 flex items-center justify-center">
                    <i class="fas fa-shopping-cart text-2xl text-white"></i>
                </div>
                <div>
                    <p class="text-3xl font-black text-gray-900 dark:text-white">{{ number_format($stats['total_sales']) }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">ยอดขาย</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Content Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Top Products --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-fire text-orange-500"></i>
                    สินค้าขายดี
                </h2>
                <a href="{{ route('admin.official-shop.products.index', ['sort_by' => 'sales_count', 'sort_order' => 'desc']) }}"
                   class="text-sm text-purple-600 dark:text-purple-400 hover:underline">
                    ดูทั้งหมด →
                </a>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($topProducts as $product)
                <div class="px-6 py-4 flex items-center gap-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <div class="w-16 h-16 rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-700 flex-shrink-0">
                        @if($product->main_image_url)
                        <img src="{{ $product->main_image_url }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                        @else
                        <div class="w-full h-full flex items-center justify-center">
                            <i class="fas fa-image text-gray-400 text-2xl"></i>
                        </div>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-semibold text-gray-900 dark:text-white truncate">{{ $product->name }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $product->category->name ?? '-' }}</p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-green-600 dark:text-green-400">฿{{ number_format($product->price) }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ number_format($product->sales_count) }} ขาย</p>
                    </div>
                </div>
                @empty
                <div class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                    <i class="fas fa-box-open text-4xl mb-4 opacity-50"></i>
                    <p>ยังไม่มีข้อมูลสินค้า</p>
                </div>
                @endforelse
            </div>
        </div>

        {{-- Recent Products --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-clock text-blue-500"></i>
                    สินค้าล่าสุด
                </h2>
                <a href="{{ route('admin.official-shop.products.index') }}"
                   class="text-sm text-purple-600 dark:text-purple-400 hover:underline">
                    ดูทั้งหมด →
                </a>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($recentProducts as $product)
                <div class="px-6 py-4 flex items-center gap-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <div class="w-16 h-16 rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-700 flex-shrink-0">
                        @if($product->main_image_url)
                        <img src="{{ $product->main_image_url }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                        @else
                        <div class="w-full h-full flex items-center justify-center">
                            <i class="fas fa-image text-gray-400 text-2xl"></i>
                        </div>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-semibold text-gray-900 dark:text-white truncate">{{ $product->name }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $product->created_at->diffForHumans() }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        @if($product->is_active)
                        <span class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 text-xs rounded-full">Active</span>
                        @else
                        <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 text-xs rounded-full">Inactive</span>
                        @endif
                        @if($product->is_featured)
                        <span class="px-2 py-1 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 text-xs rounded-full">Featured</span>
                        @endif
                    </div>
                </div>
                @empty
                <div class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                    <i class="fas fa-box-open text-4xl mb-4 opacity-50"></i>
                    <p>ยังไม่มีข้อมูลสินค้า</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Categories with Products --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="fas fa-folder text-purple-500"></i>
                หมวดหมู่ที่มีสินค้า Official
            </h2>
        </div>
        <div class="p-6">
            @if($categoriesWithProducts->count() > 0)
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                @foreach($categoriesWithProducts as $category)
                <a href="{{ route('admin.official-shop.products.index', ['category' => $category->id]) }}"
                   class="group p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl
                          hover:bg-gradient-to-br hover:from-purple-500 hover:to-pink-500
                          transition-all text-center">
                    <div class="w-12 h-12 mx-auto mb-3 rounded-xl bg-white dark:bg-gray-600
                               group-hover:bg-white/20
                               flex items-center justify-center shadow">
                        <i class="fas fa-tag text-purple-500 group-hover:text-white text-xl"></i>
                    </div>
                    <h3 class="font-semibold text-gray-900 dark:text-white group-hover:text-white text-sm truncate">
                        {{ $category->name }}
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 group-hover:text-white/80 mt-1">
                        {{ $category->products_count }} สินค้า
                    </p>
                </a>
                @endforeach
            </div>
            @else
            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                <i class="fas fa-folder-open text-4xl mb-4 opacity-50"></i>
                <p>ยังไม่มีหมวดหมู่ที่มีสินค้า Official</p>
            </div>
            @endif
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <i class="fas fa-bolt text-yellow-500"></i>
            การดำเนินการด่วน
        </h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="{{ route('admin.official-shop.products.create') }}"
               class="flex items-center gap-3 p-4 bg-gradient-to-br from-green-500 to-emerald-600 text-white rounded-xl hover:shadow-lg transition-all">
                <i class="fas fa-plus-circle text-2xl"></i>
                <span class="font-semibold">เพิ่มสินค้าใหม่</span>
            </a>
            <a href="{{ route('admin.official-shop.products.index') }}"
               class="flex items-center gap-3 p-4 bg-gradient-to-br from-blue-500 to-indigo-600 text-white rounded-xl hover:shadow-lg transition-all">
                <i class="fas fa-list text-2xl"></i>
                <span class="font-semibold">ดูสินค้าทั้งหมด</span>
            </a>
            <a href="{{ route('admin.ecommerce.categories.index') }}"
               class="flex items-center gap-3 p-4 bg-gradient-to-br from-purple-500 to-violet-600 text-white rounded-xl hover:shadow-lg transition-all">
                <i class="fas fa-folder-plus text-2xl"></i>
                <span class="font-semibold">จัดการหมวดหมู่</span>
            </a>
            <a href="{{ route('official-shop.index') }}" target="_blank"
               class="flex items-center gap-3 p-4 bg-gradient-to-br from-amber-500 to-orange-600 text-white rounded-xl hover:shadow-lg transition-all">
                <i class="fas fa-external-link-alt text-2xl"></i>
                <span class="font-semibold">ดูหน้าร้าน</span>
            </a>
        </div>
    </div>
</div>
@endsection
