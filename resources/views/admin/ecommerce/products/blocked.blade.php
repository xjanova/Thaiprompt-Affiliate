{{--
  * หน้าสินค้าที่ถูกบล็อก (Blocked Products)
  * แสดงรายการสินค้าที่ถูกบล็อกพร้อมเหตุผลและตัวเลือกปลดบล็อก
  * รองรับ Dark Mode และ Responsive Design
  --}}

@extends('layouts.admin-v3')

@section('title', 'สินค้าที่ถูกบล็อก')

@section('content')
<div class="space-y-6" x-data="{ selectedProducts: [] }">
    {{-- Alert Messages --}}
    @if(session('success'))
        <div x-data="{ show: true }" x-show="show" x-transition class="bg-gradient-to-r from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 border-l-4 border-green-500 rounded-xl shadow-lg p-4" role="alert">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-green-800 dark:text-green-200 font-medium">{{ session('success') }}</span>
                </div>
                <button @click="show = false" class="text-green-500 hover:text-green-700 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div x-data="{ show: true }" x-show="show" x-transition class="bg-gradient-to-r from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20 border-l-4 border-red-500 rounded-xl shadow-lg p-4" role="alert">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-red-800 dark:text-red-200 font-medium">{{ session('error') }}</span>
                </div>
                <button @click="show = false" class="text-red-500 hover:text-red-700 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    @endif

    {{-- Header Section --}}
    <div class="relative overflow-hidden bg-gradient-to-br from-red-500 to-orange-600 dark:from-red-600 dark:to-orange-700 rounded-2xl shadow-2xl p-8">
        <div class="absolute inset-0 bg-black/10" style="background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,0.15) 1px, transparent 0); background-size: 40px 40px;"></div>

        <div class="relative flex flex-col md:flex-row md:justify-between md:items-center gap-4">
            <div class="flex items-center gap-4">
                <div class="glass-fusion backdrop-blur-sm rounded-2xl p-4 border border-white/20">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                </div>
                <div class="text-white">
                    <h1 class="text-3xl md:text-4xl font-bold mb-1">สินค้าที่ถูกบล็อก</h1>
                    <p class="text-white/90 text-sm md:text-base">จัดการสินค้าที่ถูกระงับการขาย</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.ecommerce.products.index') }}" class="px-6 py-3 glass-fusion backdrop-blur-sm text-white rounded-xl hover:bg-white/20 transition-all duration-200 border border-white/30 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span>กลับไปสินค้าทั้งหมด</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-red-100 dark:bg-red-900/30 rounded-xl">
                    <svg class="w-8 h-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">สินค้าถูกบล็อก</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $products->total() }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Search & Filter --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
        <form action="{{ route('admin.ecommerce.products.blocked') }}" method="GET" class="flex flex-col md:flex-row gap-4">
            <div class="flex-1">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="ค้นหาสินค้า, SKU, เหตุผลการบล็อก..."
                       class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent transition">
            </div>
            <div class="w-full md:w-48">
                <select name="category" class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent transition">
                    <option value="">ทุกหมวดหมู่</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white rounded-xl transition-all duration-200 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <span>ค้นหา</span>
            </button>
        </form>
    </div>

    {{-- Products Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden border border-gray-100 dark:border-gray-700">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">สินค้า</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">ผู้ขาย</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">เหตุผลการบล็อก</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">บล็อกโดย</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">วันที่บล็อก</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($products as $product)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-4">
                                    <div class="w-16 h-16 rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-700 flex-shrink-0">
                                        @if($product->images->first())
                                            <img src="{{ asset('storage/' . $product->images->first()->image_path) }}"
                                                 alt="{{ $product->name }}"
                                                 class="w-full h-full object-cover opacity-50 grayscale">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center text-gray-400">
                                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                </svg>
                                            </div>
                                        @endif
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900 dark:text-white line-through">{{ $product->name }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">SKU: {{ $product->sku ?? '-' }}</p>
                                        <p class="text-sm font-medium text-red-600 dark:text-red-400">฿{{ number_format($product->price, 2) }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    @if($product->seller)
                                        <span class="text-gray-900 dark:text-white">{{ $product->seller->name }}</span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="max-w-xs">
                                    <p class="text-sm text-red-600 dark:text-red-400 font-medium">{{ $product->block_reason ?? 'ไม่ระบุเหตุผล' }}</p>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @if($product->blockedByUser)
                                    <span class="text-gray-900 dark:text-white">{{ $product->blockedByUser->name }}</span>
                                @else
                                    <span class="text-gray-400">ระบบ</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-gray-600 dark:text-gray-300">
                                    {{ $product->blocked_at ? $product->blocked_at->format('d/m/Y H:i') : '-' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('admin.ecommerce.products.show', $product) }}"
                                       class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition"
                                       title="ดูรายละเอียด">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>
                                    <form action="{{ route('admin.ecommerce.products.unblock', $product) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit"
                                                class="p-2 text-green-600 hover:bg-green-50 dark:hover:bg-green-900/30 rounded-lg transition"
                                                title="ปลดบล็อก"
                                                onclick="return confirm('ยืนยันปลดบล็อกสินค้านี้?')">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center gap-4">
                                    <div class="p-4 bg-green-100 dark:bg-green-900/30 rounded-full">
                                        <svg class="w-12 h-12 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-lg font-semibold text-gray-900 dark:text-white">ไม่มีสินค้าถูกบล็อก</p>
                                        <p class="text-gray-500 dark:text-gray-400">สินค้าทั้งหมดในระบบพร้อมขาย</p>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($products->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
                {{ $products->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
