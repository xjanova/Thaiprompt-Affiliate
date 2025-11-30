@extends('layouts.admin-v3')

@section('title', 'สินค้า Marketplace')

@section('content')
<div class="space-y-6">
    {{-- Premium Hero Header --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-purple-500 via-pink-500 to-rose-600 dark:from-purple-700 dark:via-pink-700 dark:to-rose-800 rounded-2xl shadow-2xl p-8">
        {{-- Animated Background Orbs --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s"></div>
        </div>

        {{-- Floating Icons --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute text-white/10 text-8xl top-10 right-20" style="animation: float 6s ease-in-out infinite">
                <i class="fas fa-boxes"></i>
            </div>
            <div class="absolute text-white/10 text-6xl bottom-10 right-40" style="animation: float 6s ease-in-out infinite; animation-delay: 0.3s">
                <i class="fas fa-cube"></i>
            </div>
        </div>

        {{-- Header Content --}}
        <div class="relative z-10">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div class="flex-1">
                    <div class="flex items-center gap-4 mb-3">
                        <div class="glass-fusion p-4 rounded-2xl">
                            <i class="fas fa-boxes text-4xl text-white drop-shadow-lg"></i>
                        </div>
                        <div>
                            <h1 class="text-4xl font-bold text-white drop-shadow-lg">สินค้า Marketplace</h1>
                            <p class="text-purple-100 text-lg mt-1">สินค้าที่ Sync มาจาก Lazada, Shopee, TikTok Shop</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="group relative overflow-hidden bg-gradient-to-br from-blue-500 to-blue-700 dark:from-blue-600 dark:to-blue-900 rounded-2xl shadow-xl p-5 text-white transform hover:scale-105 transition-all duration-300">
            <div class="absolute -right-6 -top-6 opacity-10">
                <i class="fas fa-boxes text-7xl"></i>
            </div>
            <div class="relative z-10">
                <p class="text-3xl font-bold mb-1">{{ number_format($stats['total_products'] ?? 0) }}</p>
                <p class="text-sm opacity-90">สินค้าทั้งหมด</p>
            </div>
        </div>

        <div class="group relative overflow-hidden bg-gradient-to-br from-green-500 to-emerald-700 rounded-2xl shadow-xl p-5 text-white transform hover:scale-105 transition-all duration-300">
            <div class="absolute -right-6 -top-6 opacity-10">
                <i class="fas fa-check-circle text-7xl"></i>
            </div>
            <div class="relative z-10">
                <p class="text-3xl font-bold mb-1">{{ number_format($stats['active_products'] ?? 0) }}</p>
                <p class="text-sm opacity-90">ใช้งาน</p>
            </div>
        </div>

        <div class="group relative overflow-hidden bg-gradient-to-br from-cyan-500 to-blue-600 rounded-2xl shadow-xl p-5 text-white transform hover:scale-105 transition-all duration-300">
            <div class="absolute -right-6 -top-6 opacity-10">
                <i class="fas fa-warehouse text-7xl"></i>
            </div>
            <div class="relative z-10">
                <p class="text-3xl font-bold mb-1">{{ number_format($stats['available_products'] ?? 0) }}</p>
                <p class="text-sm opacity-90">มีสินค้า</p>
            </div>
        </div>

        <div class="group relative overflow-hidden bg-gradient-to-br from-red-500 to-rose-700 rounded-2xl shadow-xl p-5 text-white transform hover:scale-105 transition-all duration-300">
            <div class="absolute -right-6 -top-6 opacity-10">
                <i class="fas fa-exclamation-triangle text-7xl"></i>
            </div>
            <div class="relative z-10">
                <p class="text-3xl font-bold mb-1">{{ number_format($stats['out_of_stock'] ?? 0) }}</p>
                <p class="text-sm opacity-90">หมดสต็อก</p>
            </div>
        </div>

        <div class="group relative overflow-hidden bg-gradient-to-br from-purple-500 to-indigo-700 rounded-2xl shadow-xl p-5 text-white transform hover:scale-105 transition-all duration-300">
            <div class="absolute -right-6 -top-6 opacity-10">
                <i class="fas fa-mouse-pointer text-7xl"></i>
            </div>
            <div class="relative z-10">
                <p class="text-3xl font-bold mb-1">{{ number_format($stats['total_clicks'] ?? 0) }}</p>
                <p class="text-sm opacity-90">คลิกทั้งหมด</p>
            </div>
        </div>

        <div class="group relative overflow-hidden bg-gradient-to-br from-emerald-500 to-teal-700 rounded-2xl shadow-xl p-5 text-white transform hover:scale-105 transition-all duration-300">
            <div class="absolute -right-6 -top-6 opacity-10">
                <i class="fas fa-shopping-cart text-7xl"></i>
            </div>
            <div class="relative z-10">
                <p class="text-3xl font-bold mb-1">{{ number_format($stats['total_sales'] ?? 0) }}</p>
                <p class="text-sm opacity-90">ยอดขาย</p>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="glass-card dark:bg-gray-800/50 backdrop-blur-xl rounded-2xl shadow-xl p-6 border border-white/20 dark:border-gray-700/50">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <i class="fas fa-filter text-purple-600 dark:text-purple-400"></i>
            ตัวกรองข้อมูล
        </h3>

        <form method="GET" action="{{ route('admin.marketplace.products.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div class="md:col-span-2">
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="ค้นหาชื่อสินค้า, SKU..."
                           class="w-full pl-12 pr-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:border-purple-500 focus:ring-4 focus:ring-purple-500/20 transition-all">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>
                </div>
            </div>

            <div>
                <select name="platform" class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:border-purple-500 focus:ring-4 focus:ring-purple-500/20 transition-all">
                    <option value="">ทุก Platform</option>
                    @foreach($platforms ?? [] as $platform)
                        <option value="{{ $platform->id }}" {{ request('platform') == $platform->id ? 'selected' : '' }}>
                            {{ $platform->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <select name="status" class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:border-purple-500 focus:ring-4 focus:ring-purple-500/20 transition-all">
                    <option value="">ทุกสถานะ</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>ใช้งาน</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>ไม่ใช้งาน</option>
                    <option value="available" {{ request('status') == 'available' ? 'selected' : '' }}>มีสินค้า</option>
                    <option value="out_of_stock" {{ request('status') == 'out_of_stock' ? 'selected' : '' }}>หมดสต็อก</option>
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="flex-1 px-6 py-3 bg-gradient-to-r from-purple-500 to-pink-600 hover:from-purple-600 hover:to-pink-700 text-white rounded-xl font-semibold transition-all shadow-lg hover:shadow-xl transform hover:scale-105 flex items-center justify-center gap-2">
                    <i class="fas fa-search"></i>
                    ค้นหา
                </button>
                <a href="{{ route('admin.marketplace.products.index') }}" class="px-4 py-3 bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-200 rounded-xl transition-all">
                    <i class="fas fa-redo"></i>
                </a>
            </div>
        </form>
    </div>

    {{-- Products Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        @forelse($products ?? [] as $product)
            <div class="glass-card dark:bg-gray-800/50 backdrop-blur-xl rounded-2xl shadow-xl overflow-hidden border border-white/20 dark:border-gray-700/50 group hover:shadow-2xl transition-all duration-300 transform hover:scale-[1.02]">
                {{-- Image --}}
                <div class="aspect-square bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 relative overflow-hidden">
                    @if($product->main_image_url)
                        <img src="{{ $product->main_image_url }}" alt="{{ $product->name }}"
                             class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                    @else
                        <div class="w-full h-full flex items-center justify-center text-gray-400 dark:text-gray-500">
                            <i class="fas fa-image text-6xl"></i>
                        </div>
                    @endif

                    {{-- Platform Badge --}}
                    @if($product->platform)
                        @php
                            $platformColors = [
                                'lazada' => 'from-blue-500 to-indigo-600',
                                'shopee' => 'from-orange-500 to-red-600',
                                'tiktok' => 'from-gray-800 to-black',
                            ];
                            $pGradient = $platformColors[$product->platform->slug ?? ''] ?? 'from-gray-500 to-gray-600';
                        @endphp
                        <span class="absolute top-3 left-3 px-3 py-1.5 rounded-xl text-xs font-semibold bg-gradient-to-r {{ $pGradient }} text-white shadow-lg">
                            {{ $product->platform->name }}
                        </span>
                    @endif

                    {{-- Status Badge --}}
                    <span class="absolute top-3 right-3 px-3 py-1.5 rounded-xl text-xs font-semibold shadow-lg
                        {{ $product->is_active ? 'bg-gradient-to-r from-green-400 to-emerald-500 text-white' : 'bg-gradient-to-r from-gray-400 to-gray-500 text-white' }}">
                        {{ $product->is_active ? 'ใช้งาน' : 'ปิด' }}
                    </span>

                    {{-- Overlay on Hover --}}
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                </div>

                {{-- Info --}}
                <div class="p-5 space-y-3">
                    <h3 class="font-semibold text-gray-900 dark:text-white line-clamp-2 min-h-[48px]" title="{{ $product->name }}">
                        {{ $product->name }}
                    </h3>

                    <div class="flex items-center justify-between">
                        <p class="text-2xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">
                            ฿{{ number_format($product->price, 2) }}
                        </p>
                        @if($product->original_price && $product->original_price > $product->price)
                            <p class="text-sm text-gray-400 line-through">฿{{ number_format($product->original_price, 2) }}</p>
                        @endif
                    </div>

                    <div class="flex items-center justify-between text-sm">
                        <span class="px-2 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded-lg">
                            <i class="fas fa-warehouse mr-1"></i>{{ number_format($product->stock_quantity ?? 0) }}
                        </span>
                        <span class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-lg">
                            <i class="fas fa-percentage mr-1"></i>{{ number_format($product->commission_rate ?? 0, 1) }}%
                        </span>
                    </div>

                    <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 pt-2 border-t border-gray-200 dark:border-gray-700">
                        <span class="flex items-center gap-1">
                            <i class="fas fa-eye"></i>{{ number_format($product->view_count ?? 0) }}
                        </span>
                        <span class="flex items-center gap-1">
                            <i class="fas fa-mouse-pointer"></i>{{ number_format($product->click_count ?? 0) }}
                        </span>
                        <span class="flex items-center gap-1">
                            <i class="fas fa-shopping-cart"></i>{{ number_format($product->sales_count ?? 0) }}
                        </span>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-2 pt-3">
                        <a href="{{ route('admin.marketplace.products.show', $product) }}"
                           class="flex-1 px-4 py-2.5 bg-gradient-to-r from-blue-500 to-blue-600 text-white text-center rounded-xl text-sm font-semibold hover:from-blue-600 hover:to-blue-700 transition-all shadow-md hover:shadow-lg transform hover:scale-105">
                            <i class="fas fa-eye mr-1"></i>ดูรายละเอียด
                        </a>
                        <form action="{{ route('admin.marketplace.products.destroy', $product) }}" method="POST"
                              onsubmit="return confirm('ต้องการลบสินค้านี้?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="px-4 py-2.5 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-xl text-sm font-semibold hover:bg-red-200 dark:hover:bg-red-800 transition-all">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full">
                <div class="glass-card dark:bg-gray-800/50 backdrop-blur-xl rounded-2xl shadow-xl p-16 text-center border border-white/20 dark:border-gray-700/50">
                    <div class="w-24 h-24 bg-gradient-to-br from-purple-100 to-pink-100 dark:from-purple-900/30 dark:to-pink-900/30 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-box text-5xl text-purple-500"></i>
                    </div>
                    <h4 class="text-2xl font-bold text-gray-700 dark:text-gray-300 mb-3">ยังไม่มีสินค้า Marketplace</h4>
                    <p class="text-gray-500 dark:text-gray-400 mb-6 max-w-md mx-auto">เริ่มต้น Sync สินค้าจาก Lazada, Shopee หรือ TikTok Shop เพื่อแสดงสินค้าในระบบ</p>
                    <a href="{{ route('admin.marketplace.accounts.index') }}"
                       class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-purple-500 to-pink-600 hover:from-purple-600 hover:to-pink-700 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl transform hover:scale-105 transition-all">
                        <i class="fas fa-sync"></i>
                        ไป Sync สินค้าจากบัญชี Marketplace
                    </a>
                </div>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if(($products ?? collect())->hasPages())
        <div class="flex justify-center">
            {{ $products->links() }}
        </div>
    @endif
</div>

@push('styles')
<style>
    @keyframes float {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-20px) rotate(5deg); }
    }
    .glass-card {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(20px);
    }
    .dark .glass-card {
        background: rgba(31, 41, 55, 0.8);
    }
</style>
@endpush
@endsection
