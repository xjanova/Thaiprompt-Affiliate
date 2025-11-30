@extends('layouts.admin-v3')

@section('title', 'สินค้า Marketplace')

@section('content')
<div class="p-6 space-y-6">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">สินค้า Marketplace</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">สินค้าที่ Sync มาจาก Lazada, Shopee, TikTok Shop</p>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="glass-card p-4 rounded-xl text-center">
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_products']) }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">สินค้าทั้งหมด</p>
        </div>
        <div class="glass-card p-4 rounded-xl text-center">
            <p class="text-2xl font-bold text-green-600">{{ number_format($stats['active_products']) }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">ใช้งาน</p>
        </div>
        <div class="glass-card p-4 rounded-xl text-center">
            <p class="text-2xl font-bold text-blue-600">{{ number_format($stats['available_products']) }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">มีสินค้า</p>
        </div>
        <div class="glass-card p-4 rounded-xl text-center">
            <p class="text-2xl font-bold text-red-600">{{ number_format($stats['out_of_stock']) }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">หมดสต็อก</p>
        </div>
        <div class="glass-card p-4 rounded-xl text-center">
            <p class="text-2xl font-bold text-purple-600">{{ number_format($stats['total_clicks']) }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">คลิกทั้งหมด</p>
        </div>
        <div class="glass-card p-4 rounded-xl text-center">
            <p class="text-2xl font-bold text-emerald-600">{{ number_format($stats['total_sales']) }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">ยอดขาย</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="glass-card p-4 rounded-xl">
        <form method="GET" action="{{ route('admin.marketplace.products.index') }}" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="ค้นหาชื่อสินค้า, SKU..."
                       class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="w-40">
                <select name="platform" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="">ทุก Platform</option>
                    @foreach($platforms as $platform)
                        <option value="{{ $platform->id }}" {{ request('platform') == $platform->id ? 'selected' : '' }}>
                            {{ $platform->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="w-40">
                <select name="account" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="">ทุกบัญชี</option>
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}" {{ request('account') == $account->id ? 'selected' : '' }}>
                            {{ $account->account_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="w-40">
                <select name="status" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="">ทุกสถานะ</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>ใช้งาน</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>ไม่ใช้งาน</option>
                    <option value="available" {{ request('status') == 'available' ? 'selected' : '' }}>มีสินค้า</option>
                    <option value="out_of_stock" {{ request('status') == 'out_of_stock' ? 'selected' : '' }}>หมดสต็อก</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                <i class="fas fa-search mr-1"></i> ค้นหา
            </button>
        </form>
    </div>

    {{-- Products Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        @forelse($products as $product)
            <div class="glass-card rounded-xl overflow-hidden hover:shadow-lg transition">
                {{-- Image --}}
                <div class="aspect-square bg-gray-100 dark:bg-gray-700 relative">
                    @if($product->main_image_url)
                        <img src="{{ $product->main_image_url }}" alt="{{ $product->name }}"
                             class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full flex items-center justify-center text-gray-400">
                            <i class="fas fa-image text-4xl"></i>
                        </div>
                    @endif

                    {{-- Platform Badge --}}
                    @if($product->platform)
                        <span class="absolute top-2 left-2 px-2 py-1 rounded text-xs font-medium
                            @if($product->platform->slug == 'lazada') bg-orange-500 text-white
                            @elseif($product->platform->slug == 'shopee') bg-red-500 text-white
                            @elseif($product->platform->slug == 'tiktok') bg-pink-500 text-white
                            @else bg-gray-500 text-white
                            @endif">
                            {{ $product->platform->name }}
                        </span>
                    @endif

                    {{-- Status Badge --}}
                    <span class="absolute top-2 right-2 px-2 py-1 rounded text-xs font-medium
                        {{ $product->is_active ? 'bg-green-500 text-white' : 'bg-gray-500 text-white' }}">
                        {{ $product->is_active ? 'ใช้งาน' : 'ปิด' }}
                    </span>
                </div>

                {{-- Info --}}
                <div class="p-4 space-y-2">
                    <h3 class="font-medium text-gray-900 dark:text-white line-clamp-2" title="{{ $product->name }}">
                        {{ $product->name }}
                    </h3>

                    <div class="flex items-center justify-between">
                        <p class="text-lg font-bold text-blue-600">฿{{ number_format($product->price, 2) }}</p>
                        @if($product->original_price && $product->original_price > $product->price)
                            <p class="text-sm text-gray-400 line-through">฿{{ number_format($product->original_price, 2) }}</p>
                        @endif
                    </div>

                    <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
                        <span>สต็อก: {{ number_format($product->stock_quantity) }}</span>
                        <span>คอมมิชชั่น: {{ number_format($product->commission_rate ?? 0, 1) }}%</span>
                    </div>

                    <div class="flex items-center justify-between text-xs text-gray-400">
                        <span><i class="fas fa-eye mr-1"></i>{{ number_format($product->view_count ?? 0) }}</span>
                        <span><i class="fas fa-mouse-pointer mr-1"></i>{{ number_format($product->click_count ?? 0) }}</span>
                        <span><i class="fas fa-shopping-cart mr-1"></i>{{ number_format($product->sales_count ?? 0) }}</span>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-2 pt-2 border-t border-gray-200 dark:border-gray-700">
                        <a href="{{ route('admin.marketplace.products.show', $product) }}"
                           class="flex-1 px-3 py-2 bg-blue-500 text-white text-center rounded-lg text-sm hover:bg-blue-600 transition">
                            <i class="fas fa-eye mr-1"></i>ดู
                        </a>
                        <form action="{{ route('admin.marketplace.products.destroy', $product) }}" method="POST" class="flex-1"
                              onsubmit="return confirm('ต้องการลบสินค้านี้?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="w-full px-3 py-2 bg-red-500 text-white rounded-lg text-sm hover:bg-red-600 transition">
                                <i class="fas fa-trash mr-1"></i>ลบ
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full">
                <div class="glass-card p-12 rounded-xl text-center">
                    <i class="fas fa-box text-4xl text-gray-300 dark:text-gray-600 mb-4"></i>
                    <p class="text-gray-500 dark:text-gray-400">ยังไม่มีสินค้า Marketplace</p>
                    <a href="{{ route('admin.marketplace.accounts.index') }}" class="mt-2 text-blue-500 hover:underline">
                        ไป Sync สินค้าจากบัญชี Marketplace
                    </a>
                </div>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($products->hasPages())
        <div class="flex justify-center">
            {{ $products->links() }}
        </div>
    @endif
</div>
@endsection
