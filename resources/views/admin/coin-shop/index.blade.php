{{--
    หน้าจัดการสินค้า Coin Shop (Admin)
--}}

@extends('layouts.admin')

@section('title', $pageTitle ?? 'จัดการสินค้า Coin Shop')

@section('content')
<div class="container-fluid px-4 py-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                🛒 จัดการสินค้า Coin Shop
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">จัดการสินค้าที่แลกได้ด้วย Video Coins</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.coin-shop.purchases') }}"
               class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition">
                📋 คำสั่งซื้อ
            </a>
            <a href="{{ route('admin.coin-shop.create') }}"
               class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg transition">
                + เพิ่มสินค้า
            </a>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                    <span class="text-blue-600 dark:text-blue-400">📦</span>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">สินค้าทั้งหมด</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_products']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                    <span class="text-green-600 dark:text-green-400">✅</span>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">เปิดขาย</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['active_products']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-red-100 dark:bg-red-900 rounded-lg flex items-center justify-center">
                    <span class="text-red-600 dark:text-red-400">⚠️</span>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">สินค้าหมด</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['out_of_stock']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-yellow-100 dark:bg-yellow-900 rounded-lg flex items-center justify-center">
                    <span class="text-yellow-600 dark:text-yellow-400">🛍️</span>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">ขายแล้ว</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_sold']) }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 mb-6">
        <form action="{{ route('admin.coin-shop.index') }}" method="GET" class="flex flex-wrap items-center gap-4">
            {{-- Search --}}
            <div class="flex-1 min-w-[200px]">
                <input type="text"
                       name="search"
                       value="{{ $search ?? '' }}"
                       placeholder="ค้นหาชื่อสินค้า/ID..."
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>

            {{-- Status --}}
            <select name="status" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                <option value="">ทุกสถานะ</option>
                <option value="active" {{ $currentStatus === 'active' ? 'selected' : '' }}>เปิดขาย</option>
                <option value="inactive" {{ $currentStatus === 'inactive' ? 'selected' : '' }}>ปิดขาย</option>
                <option value="out_of_stock" {{ $currentStatus === 'out_of_stock' ? 'selected' : '' }}>สินค้าหมด</option>
            </select>

            {{-- Type --}}
            <select name="type" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                <option value="">ทุกประเภท</option>
                @foreach($types as $key => $typeInfo)
                    <option value="{{ $key }}" {{ $currentType === $key ? 'selected' : '' }}>
                        {{ $typeInfo['icon'] }} {{ $typeInfo['name'] }}
                    </option>
                @endforeach
            </select>

            {{-- Sort --}}
            <select name="sort" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                <option value="latest" {{ $currentSort === 'latest' ? 'selected' : '' }}>ล่าสุด</option>
                <option value="price_low" {{ $currentSort === 'price_low' ? 'selected' : '' }}>ราคาต่ำ-สูง</option>
                <option value="price_high" {{ $currentSort === 'price_high' ? 'selected' : '' }}>ราคาสูง-ต่ำ</option>
                <option value="popular" {{ $currentSort === 'popular' ? 'selected' : '' }}>ยอดขายสูง</option>
                <option value="name" {{ $currentSort === 'name' ? 'selected' : '' }}>ชื่อ A-Z</option>
            </select>

            <button type="submit" class="px-4 py-2 bg-gray-800 dark:bg-gray-600 text-white rounded-lg hover:bg-gray-900 dark:hover:bg-gray-500 transition">
                ค้นหา
            </button>
        </form>
    </div>

    {{-- Products Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">สินค้า</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ประเภท</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ราคา</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">สต็อก</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ขายแล้ว</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">สถานะ</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($products as $product)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                @if($product->thumbnail)
                                    <img src="{{ $product->thumbnail_url }}" alt="{{ $product->name }}" class="w-12 h-12 rounded-lg object-cover">
                                @else
                                    <div class="w-12 h-12 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                        <span class="text-xl">{{ $product->type_icon }}</span>
                                    </div>
                                @endif
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ $product->display_name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">ID: {{ $product->id }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-xs font-medium bg-{{ $product->type_color }}-100 dark:bg-{{ $product->type_color }}-900 text-{{ $product->type_color }}-800 dark:text-{{ $product->type_color }}-200 rounded-full">
                                {{ $product->type_icon }} {{ $product->type_name }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <p class="font-bold text-yellow-600 dark:text-yellow-400">{{ number_format($product->price_coins, 0) }}</p>
                            @if($product->has_discount)
                                <p class="text-xs text-gray-400 line-through">{{ number_format($product->original_price_coins, 0) }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($product->stock_quantity === null)
                                <span class="text-gray-500 dark:text-gray-400">∞</span>
                            @elseif($product->stock_quantity <= 0)
                                <span class="text-red-500 font-bold">หมด</span>
                            @elseif($product->stock_quantity <= 10)
                                <span class="text-orange-500 font-bold">{{ $product->stock_quantity }}</span>
                            @else
                                <span class="text-gray-900 dark:text-white">{{ $product->stock_quantity }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center text-gray-900 dark:text-white">
                            {{ number_format($product->total_sold) }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($product->is_active)
                                <span class="px-2 py-1 text-xs font-medium bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-full">
                                    เปิดขาย
                                </span>
                            @else
                                <span class="px-2 py-1 text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-full">
                                    ปิดขาย
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('admin.coin-shop.edit', $product) }}"
                                   class="p-2 text-blue-600 hover:bg-blue-100 dark:hover:bg-blue-900 rounded-lg transition"
                                   title="แก้ไข">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                                <form action="{{ route('admin.coin-shop.toggle-active', $product) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit"
                                            class="p-2 {{ $product->is_active ? 'text-orange-600 hover:bg-orange-100 dark:hover:bg-orange-900' : 'text-green-600 hover:bg-green-100 dark:hover:bg-green-900' }} rounded-lg transition"
                                            title="{{ $product->is_active ? 'ปิดขาย' : 'เปิดขาย' }}">
                                        @if($product->is_active)
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                            </svg>
                                        @else
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        @endif
                                    </button>
                                </form>
                                <form action="{{ route('admin.coin-shop.destroy', $product) }}" method="POST" class="inline"
                                      onsubmit="return confirm('ยืนยันการลบสินค้านี้?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="p-2 text-red-600 hover:bg-red-100 dark:hover:bg-red-900 rounded-lg transition"
                                            title="ลบ">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                            ไม่พบสินค้า
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($products->hasPages())
        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
            {{ $products->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
