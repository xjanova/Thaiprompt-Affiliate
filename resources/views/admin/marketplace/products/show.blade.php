@extends('layouts.admin-v3')

@section('title', 'รายละเอียดสินค้า - ' . $product->name)

@section('content')
<div class="p-6 space-y-6">
    {{-- Header --}}
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.marketplace.products.index') }}"
           class="p-2 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">รายละเอียดสินค้า</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $product->external_product_id }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Product Info --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Main Info --}}
            <div class="glass-card p-6 rounded-xl">
                <div class="flex flex-col md:flex-row gap-6">
                    {{-- Image --}}
                    <div class="w-full md:w-64 flex-shrink-0">
                        <div class="aspect-square bg-gray-100 dark:bg-gray-700 rounded-xl overflow-hidden">
                            @if($product->main_image_url)
                                <img src="{{ $product->main_image_url }}" alt="{{ $product->name }}"
                                     class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-gray-400">
                                    <i class="fas fa-image text-4xl"></i>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Info --}}
                    <div class="flex-1 space-y-4">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ $product->name }}</h2>

                        <div class="flex items-center gap-4">
                            @if($product->platform)
                                <span class="px-3 py-1 rounded-full text-sm font-medium
                                    @if($product->platform->slug == 'lazada') bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400
                                    @elseif($product->platform->slug == 'shopee') bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400
                                    @elseif($product->platform->slug == 'tiktok') bg-pink-100 text-pink-700 dark:bg-pink-900/30 dark:text-pink-400
                                    @endif">
                                    {{ $product->platform->name }}
                                </span>
                            @endif

                            <span class="px-3 py-1 rounded-full text-sm font-medium
                                {{ $product->is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' }}">
                                {{ $product->is_active ? 'ใช้งาน' : 'ปิดใช้งาน' }}
                            </span>

                            <span class="px-3 py-1 rounded-full text-sm font-medium
                                {{ $product->is_available ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' }}">
                                {{ $product->is_available ? 'มีสินค้า' : 'หมดสต็อก' }}
                            </span>
                        </div>

                        <div class="flex items-baseline gap-3">
                            <p class="text-3xl font-bold text-blue-600">฿{{ number_format($product->price, 2) }}</p>
                            @if($product->original_price && $product->original_price > $product->price)
                                <p class="text-lg text-gray-400 line-through">฿{{ number_format($product->original_price, 2) }}</p>
                            @endif
                        </div>

                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <p class="text-gray-500 dark:text-gray-400">SKU</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $product->external_sku ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500 dark:text-gray-400">หมวดหมู่</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $product->category ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500 dark:text-gray-400">แบรนด์</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $product->brand ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500 dark:text-gray-400">สต็อก</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ number_format($product->stock_quantity) }} ชิ้น</p>
                            </div>
                            <div>
                                <p class="text-gray-500 dark:text-gray-400">อัตราคอมมิชชั่น</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ number_format($product->commission_rate ?? 0, 2) }}%</p>
                            </div>
                            <div>
                                <p class="text-gray-500 dark:text-gray-400">บัญชี</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $product->account->account_name ?? '-' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                @if($product->description)
                    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-2">รายละเอียดสินค้า</h3>
                        <p class="text-gray-600 dark:text-gray-400 whitespace-pre-wrap">{{ $product->description }}</p>
                    </div>
                @endif
            </div>

            {{-- Stats --}}
            <div class="glass-card p-6 rounded-xl">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">สถิติ</h3>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_views']) }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">การเข้าชม</p>
                    </div>
                    <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_clicks']) }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">คลิก</p>
                    </div>
                    <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_shares']) }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">แชร์</p>
                    </div>
                    <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_orders']) }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">ออเดอร์</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Actions --}}
            <div class="glass-card p-6 rounded-xl space-y-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">การจัดการ</h3>

                <form action="{{ route('admin.marketplace.products.update', $product) }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="flex items-center gap-3">
                        <input type="checkbox" name="is_active" value="1" id="is_active"
                               {{ $product->is_active ? 'checked' : '' }}
                               class="w-5 h-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <label for="is_active" class="text-gray-700 dark:text-gray-300">เปิดใช้งาน</label>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">อัตราคอมมิชชั่น (%)</label>
                        <input type="number" name="commission_rate" value="{{ $product->commission_rate }}"
                               min="0" max="100" step="0.01"
                               class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>

                    <button type="submit"
                            class="w-full px-4 py-3 bg-blue-500 text-white rounded-xl hover:bg-blue-600 transition">
                        <i class="fas fa-save mr-2"></i>บันทึก
                    </button>
                </form>

                @if($product->affiliate_url)
                    <a href="{{ $product->affiliate_url }}" target="_blank"
                       class="block w-full px-4 py-3 bg-green-500 text-white rounded-xl hover:bg-green-600 transition text-center">
                        <i class="fas fa-external-link-alt mr-2"></i>ดูสินค้าบน Platform
                    </a>
                @endif

                <form action="{{ route('admin.marketplace.products.destroy', $product) }}" method="POST"
                      onsubmit="return confirm('ต้องการลบสินค้านี้?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="w-full px-4 py-3 bg-red-500 text-white rounded-xl hover:bg-red-600 transition">
                        <i class="fas fa-trash mr-2"></i>ลบสินค้า
                    </button>
                </form>
            </div>

            {{-- Info --}}
            <div class="glass-card p-6 rounded-xl">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ข้อมูลระบบ</h3>

                <div class="space-y-3 text-sm">
                    <div>
                        <p class="text-gray-500 dark:text-gray-400">Product ID</p>
                        <p class="font-mono text-gray-900 dark:text-white">{{ $product->external_product_id }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 dark:text-gray-400">Sync ล่าสุด</p>
                        <p class="text-gray-900 dark:text-white">{{ $product->last_synced_at ? $product->last_synced_at->format('d/m/Y H:i') : '-' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 dark:text-gray-400">สร้างเมื่อ</p>
                        <p class="text-gray-900 dark:text-white">{{ $product->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
