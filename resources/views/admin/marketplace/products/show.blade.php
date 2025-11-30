@extends('layouts.admin-v3')

@section('title', 'รายละเอียดสินค้า - ' . $product->name)

@push('styles')
<style>
    @keyframes float {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-20px) rotate(5deg); }
    }
    .float-animation {
        animation: float 6s ease-in-out infinite;
    }
    @keyframes pulse-slow {
        0%, 100% { opacity: 0.4; }
        50% { opacity: 0.8; }
    }
    .pulse-slow {
        animation: pulse-slow 4s ease-in-out infinite;
    }
</style>
@endpush

@section('content')
<div class="space-y-6">
    {{-- Premium Hero Header --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-violet-600 via-purple-600 to-fuchsia-600 p-8">
        {{-- Animated Background Orbs --}}
        <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full blur-3xl pulse-slow"></div>
        <div class="absolute bottom-0 left-0 w-48 h-48 bg-pink-400/20 rounded-full blur-3xl pulse-slow" style="animation-delay: 2s;"></div>

        {{-- Floating Icon --}}
        <div class="absolute right-8 top-1/2 -translate-y-1/2 hidden lg:block">
            <div class="w-32 h-32 bg-white/10 backdrop-blur-sm rounded-2xl flex items-center justify-center float-animation">
                <i class="fas fa-box-open text-5xl text-white/80"></i>
            </div>
        </div>

        <div class="relative">
            {{-- Back Button --}}
            <a href="{{ route('admin.marketplace.products.index') }}"
               class="inline-flex items-center gap-2 text-white/80 hover:text-white transition mb-4">
                <i class="fas fa-arrow-left"></i>
                <span>กลับไปรายการ</span>
            </a>

            <h1 class="text-3xl font-bold text-white mb-2">รายละเอียดสินค้า</h1>
            <p class="text-white/80 font-mono">{{ $product->external_product_id }}</p>

            {{-- Status Badges --}}
            <div class="flex flex-wrap items-center gap-2 mt-4">
                @if($product->platform)
                    <span class="px-3 py-1.5 rounded-full text-sm font-medium shadow-lg
                        @if($product->platform->slug == 'lazada') bg-orange-500 text-white
                        @elseif($product->platform->slug == 'shopee') bg-red-500 text-white
                        @elseif($product->platform->slug == 'tiktok') bg-pink-500 text-white
                        @else bg-gray-500 text-white
                        @endif">
                        {{ $product->platform->name }}
                    </span>
                @endif

                <span class="px-3 py-1.5 rounded-full text-sm font-medium shadow-lg
                    {{ $product->is_active ? 'bg-emerald-500 text-white' : 'bg-gray-500 text-white' }}">
                    <i class="fas {{ $product->is_active ? 'fa-check-circle' : 'fa-pause-circle' }} mr-1"></i>
                    {{ $product->is_active ? 'ใช้งาน' : 'ปิดใช้งาน' }}
                </span>

                <span class="px-3 py-1.5 rounded-full text-sm font-medium shadow-lg
                    {{ $product->is_available ? 'bg-blue-500 text-white' : 'bg-red-500 text-white' }}">
                    <i class="fas {{ $product->is_available ? 'fa-box' : 'fa-box-open' }} mr-1"></i>
                    {{ $product->is_available ? 'มีสินค้า' : 'หมดสต็อก' }}
                </span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Product Info Card --}}
            <div class="glass-card rounded-2xl overflow-hidden">
                <div class="p-6">
                    <div class="flex flex-col md:flex-row gap-6">
                        {{-- Product Image --}}
                        <div class="w-full md:w-72 flex-shrink-0">
                            <div class="aspect-square bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 rounded-2xl overflow-hidden shadow-lg">
                                @if($product->main_image_url)
                                    <img src="{{ $product->main_image_url }}" alt="{{ $product->name }}"
                                         class="w-full h-full object-cover hover:scale-110 transition-transform duration-500">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-gray-400">
                                        <i class="fas fa-image text-6xl"></i>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Product Details --}}
                        <div class="flex-1 space-y-4">
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $product->name }}</h2>

                            {{-- Price Display --}}
                            <div class="flex items-baseline gap-4">
                                <p class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                                    ฿{{ number_format($product->price, 2) }}
                                </p>
                                @if($product->original_price && $product->original_price > $product->price)
                                    <p class="text-xl text-gray-400 line-through">฿{{ number_format($product->original_price, 2) }}</p>
                                    <span class="px-2 py-1 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-lg text-sm font-medium">
                                        -{{ round((($product->original_price - $product->price) / $product->original_price) * 100) }}%
                                    </span>
                                @endif
                            </div>

                            {{-- Product Attributes --}}
                            <div class="grid grid-cols-2 gap-4 pt-4">
                                <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">SKU</p>
                                    <p class="font-semibold text-gray-900 dark:text-white font-mono">{{ $product->external_sku ?? '-' }}</p>
                                </div>
                                <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">หมวดหมู่</p>
                                    <p class="font-semibold text-gray-900 dark:text-white">{{ $product->category ?? '-' }}</p>
                                </div>
                                <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">แบรนด์</p>
                                    <p class="font-semibold text-gray-900 dark:text-white">{{ $product->brand ?? '-' }}</p>
                                </div>
                                <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">สต็อก</p>
                                    <p class="font-semibold {{ $product->stock_quantity > 10 ? 'text-emerald-600' : ($product->stock_quantity > 0 ? 'text-amber-600' : 'text-red-600') }}">
                                        {{ number_format($product->stock_quantity) }} ชิ้น
                                    </p>
                                </div>
                                <div class="p-4 bg-gradient-to-br from-blue-50 to-purple-50 dark:from-blue-900/30 dark:to-purple-900/30 rounded-xl">
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">อัตราคอมมิชชั่น</p>
                                    <p class="font-bold text-blue-600 dark:text-blue-400 text-lg">{{ number_format($product->commission_rate ?? 0, 2) }}%</p>
                                </div>
                                <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">บัญชี</p>
                                    <p class="font-semibold text-gray-900 dark:text-white">{{ $product->account->account_name ?? '-' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($product->description)
                        <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <h3 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                <i class="fas fa-align-left text-gray-400"></i>
                                รายละเอียดสินค้า
                            </h3>
                            <p class="text-gray-600 dark:text-gray-400 whitespace-pre-wrap leading-relaxed">{{ $product->description }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Stats Card --}}
            <div class="glass-card rounded-2xl overflow-hidden">
                <div class="bg-gradient-to-r from-cyan-500/10 to-blue-500/10 dark:from-cyan-500/20 dark:to-blue-500/20 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-chart-bar text-cyan-500"></i>
                        สถิติสินค้า
                    </h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="text-center p-5 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/30 dark:to-indigo-900/30 rounded-xl group hover:scale-105 transition-transform">
                            <div class="w-12 h-12 mx-auto mb-3 bg-gradient-to-br from-blue-500 to-indigo-500 rounded-xl flex items-center justify-center shadow-lg group-hover:shadow-blue-500/25">
                                <i class="fas fa-eye text-white"></i>
                            </div>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_views']) }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">การเข้าชม</p>
                        </div>
                        <div class="text-center p-5 bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/30 dark:to-pink-900/30 rounded-xl group hover:scale-105 transition-transform">
                            <div class="w-12 h-12 mx-auto mb-3 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl flex items-center justify-center shadow-lg group-hover:shadow-purple-500/25">
                                <i class="fas fa-mouse-pointer text-white"></i>
                            </div>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_clicks']) }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">คลิก</p>
                        </div>
                        <div class="text-center p-5 bg-gradient-to-br from-cyan-50 to-teal-50 dark:from-cyan-900/30 dark:to-teal-900/30 rounded-xl group hover:scale-105 transition-transform">
                            <div class="w-12 h-12 mx-auto mb-3 bg-gradient-to-br from-cyan-500 to-teal-500 rounded-xl flex items-center justify-center shadow-lg group-hover:shadow-cyan-500/25">
                                <i class="fas fa-share-alt text-white"></i>
                            </div>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_shares']) }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">แชร์</p>
                        </div>
                        <div class="text-center p-5 bg-gradient-to-br from-emerald-50 to-green-50 dark:from-emerald-900/30 dark:to-green-900/30 rounded-xl group hover:scale-105 transition-transform">
                            <div class="w-12 h-12 mx-auto mb-3 bg-gradient-to-br from-emerald-500 to-green-500 rounded-xl flex items-center justify-center shadow-lg group-hover:shadow-emerald-500/25">
                                <i class="fas fa-shopping-cart text-white"></i>
                            </div>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_orders']) }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">ออเดอร์</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Management Card --}}
            <div class="glass-card rounded-2xl overflow-hidden">
                <div class="bg-gradient-to-r from-purple-500/10 to-pink-500/10 dark:from-purple-500/20 dark:to-pink-500/20 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-cog text-purple-500"></i>
                        การจัดการ
                    </h3>
                </div>
                <div class="p-6">
                    <form action="{{ route('admin.marketplace.products.update', $product) }}" method="POST" class="space-y-4">
                        @csrf
                        @method('PUT')

                        <label class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                            <div class="relative">
                                <input type="checkbox" name="is_active" value="1" id="is_active"
                                       {{ $product->is_active ? 'checked' : '' }}
                                       class="peer sr-only">
                                <div class="w-12 h-7 bg-gray-300 dark:bg-gray-600 rounded-full peer-checked:bg-emerald-500 transition-colors"></div>
                                <div class="absolute left-1 top-1 w-5 h-5 bg-white rounded-full shadow-md peer-checked:translate-x-5 transition-transform"></div>
                            </div>
                            <span class="font-medium text-gray-700 dark:text-gray-300">เปิดใช้งานสินค้า</span>
                        </label>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                อัตราคอมมิชชั่น (%)
                            </label>
                            <div class="relative">
                                <input type="number" name="commission_rate" value="{{ $product->commission_rate }}"
                                       min="0" max="100" step="0.01"
                                       class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition">
                                <div class="absolute right-3 top-1/2 -translate-y-1/2">
                                    <span class="text-gray-400">%</span>
                                </div>
                            </div>
                        </div>

                        <button type="submit"
                                class="w-full px-4 py-3 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-xl hover:shadow-lg hover:shadow-purple-500/25 transition-all font-medium">
                            <i class="fas fa-save mr-2"></i>บันทึก
                        </button>
                    </form>

                    @if($product->affiliate_url)
                        <a href="{{ $product->affiliate_url }}" target="_blank"
                           class="flex items-center justify-center gap-2 w-full px-4 py-3 mt-4 bg-gradient-to-r from-emerald-500 to-teal-500 text-white rounded-xl hover:shadow-lg hover:shadow-emerald-500/25 transition-all">
                            <i class="fas fa-external-link-alt"></i>
                            <span>ดูสินค้าบน Platform</span>
                        </a>
                    @endif

                    <form action="{{ route('admin.marketplace.products.destroy', $product) }}" method="POST" class="mt-4"
                          onsubmit="return confirm('⚠️ ต้องการลบสินค้านี้?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="w-full px-4 py-3 bg-gradient-to-r from-red-500 to-rose-600 text-white rounded-xl hover:shadow-lg hover:shadow-red-500/25 transition-all">
                            <i class="fas fa-trash mr-2"></i>ลบสินค้า
                        </button>
                    </form>
                </div>
            </div>

            {{-- System Info Card --}}
            <div class="glass-card rounded-2xl overflow-hidden">
                <div class="bg-gradient-to-r from-gray-500/10 to-slate-500/10 dark:from-gray-500/20 dark:to-slate-500/20 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-server text-gray-500"></i>
                        ข้อมูลระบบ
                    </h3>
                </div>
                <div class="p-6 space-y-4">
                    <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Product ID</p>
                        <p class="font-mono text-gray-900 dark:text-white text-sm break-all">{{ $product->external_product_id }}</p>
                    </div>
                    <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Sync ล่าสุด</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $product->last_synced_at ? $product->last_synced_at->format('d/m/Y H:i') : '-' }}</p>
                    </div>
                    <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">สร้างเมื่อ</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $product->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
