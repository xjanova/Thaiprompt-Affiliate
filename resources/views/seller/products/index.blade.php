@extends('layouts.seller')

@section('title', 'จัดการสินค้า')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <h1 class="text-3xl font-bold text-gray-800 dark:text-white">จัดการสินค้า</h1>
        <a href="{{ route('seller.products.create') }}"
           class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition">
            ➕ เพิ่มสินค้าใหม่
        </a>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">สินค้าทั้งหมด</p>
                    <p class="text-2xl font-bold text-gray-800 dark:text-white">{{ number_format($stats['total']) }}</p>
                </div>
                <div class="text-4xl">📦</div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">เปิดใช้งาน</p>
                    <p class="text-2xl font-bold text-green-600">{{ number_format($stats['active']) }}</p>
                </div>
                <div class="text-4xl">✅</div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">สต็อกต่ำ</p>
                    <p class="text-2xl font-bold text-orange-600">{{ number_format($stats['low_stock']) }}</p>
                </div>
                <div class="text-4xl">⚠️</div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">สินค้าหมด</p>
                    <p class="text-2xl font-bold text-red-600">{{ number_format($stats['out_of_stock']) }}</p>
                </div>
                <div class="text-4xl">❌</div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4">
        <form method="GET" class="flex flex-col md:flex-row gap-4">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="ค้นหาสินค้า (ชื่อหรือ SKU)..."
                   class="flex-1 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg">

            <select name="status" class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg">
                <option value="">ทุกสถานะ</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>เปิดใช้งาน</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>ปิดใช้งาน</option>
            </select>

            <select name="stock" class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg">
                <option value="">สต็อกทั้งหมด</option>
                <option value="in_stock" {{ request('stock') === 'in_stock' ? 'selected' : '' }}>มีสินค้า</option>
                <option value="low_stock" {{ request('stock') === 'low_stock' ? 'selected' : '' }}>สต็อกต่ำ</option>
                <option value="out_of_stock" {{ request('stock') === 'out_of_stock' ? 'selected' : '' }}>สินค้าหมด</option>
            </select>

            <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition">
                🔍 ค้นหา
            </button>
            @if(request()->anyFilled(['search', 'status', 'stock']))
                <a href="{{ route('seller.products.index') }}"
                   class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg font-medium transition">
                    ล้างตัวกรอง
                </a>
            @endif
        </form>
    </div>

    <!-- Products List -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
        @if($products->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">สินค้า</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">SKU</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">หมวดหมู่</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ราคา</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">สต็อก</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ยอดขาย</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">สถานะ</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($products as $product)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        @if($product->main_image_url)
                                            <img src="{{ Storage::url($product->main_image_url) }}"
                                                 alt="{{ $product->name }}"
                                                 class="w-12 h-12 object-cover rounded-lg">
                                        @else
                                            <div class="w-12 h-12 bg-gray-200 dark:bg-gray-600 rounded-lg flex items-center justify-center">
                                                <span class="text-xl">📦</span>
                                            </div>
                                        @endif
                                        <div>
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $product->name }}</div>
                                            @if($product->brand)
                                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $product->brand }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">{{ $product->sku }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">{{ $product->category->name ?? '-' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-gray-900 dark:text-white">฿{{ number_format($product->price, 2) }}</div>
                                    @if($product->compare_at_price && $product->compare_at_price > $product->price)
                                        <div class="text-xs text-gray-500 line-through">฿{{ number_format($product->compare_at_price, 2) }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        {{ number_format($product->stock_quantity) }}
                                        @if($product->stock_quantity <= $product->low_stock_threshold)
                                            <span class="text-red-600">⚠️</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">{{ number_format($product->sales_count) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <form action="{{ route('seller.products.toggle-status', $product) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="relative inline-flex h-6 w-11 items-center rounded-full transition {{ $product->is_active ? 'bg-green-600' : 'bg-gray-300' }}">
                                            <span class="inline-block h-4 w-4 transform rounded-full bg-white transition {{ $product->is_active ? 'translate-x-6' : 'translate-x-1' }}"></span>
                                        </button>
                                    </form>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <div class="flex gap-2">
                                        <a href="{{ route('seller.products.edit', $product) }}"
                                           class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 font-medium">
                                            แก้ไข
                                        </a>
                                        <form action="{{ route('seller.products.destroy', $product) }}" method="POST"
                                              onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบสินค้านี้?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 font-medium">
                                                ลบ
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $products->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <div class="text-6xl mb-4">📦</div>
                <p class="text-gray-500 dark:text-gray-400 text-lg mb-4">ยังไม่มีสินค้า</p>
                <a href="{{ route('seller.products.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition">
                    ➕ เพิ่มสินค้าแรกของคุณ
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
