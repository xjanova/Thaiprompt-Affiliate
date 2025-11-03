@extends('layouts.admin')

@section('title', 'รายละเอียดสินค้า')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">📦 รายละเอียดสินค้า</h1>
        <div class="flex gap-2">
            <button onclick="document.getElementById('editProductModal').classList.remove('hidden')" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                แก้ไขสินค้า
            </button>
            <a href="{{ route('admin.ecommerce.products.index') }}" class="text-orange-600 dark:text-orange-400 hover:text-orange-700 px-6 py-2">
                ← กลับ
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    <!-- Product Info -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">{{ $product->name }}</h2>
                <div class="space-y-2 text-sm">
                    <p><span class="font-semibold">SKU:</span> {{ $product->sku }}</p>
                    <p><span class="font-semibold">หมวดหมู่:</span> {{ $product->category->name ?? 'ไม่ระบุ' }}</p>
                    <p><span class="font-semibold">ราคา:</span> ฿{{ number_format($product->price, 2) }}</p>
                    @if($product->compare_at_price)
                        <p><span class="font-semibold">ราคาเทียบเคียง:</span> <span class="line-through text-gray-500">฿{{ number_format($product->compare_at_price, 2) }}</span></p>
                    @endif
                    <p><span class="font-semibold">สต็อก:</span> {{ $product->stock_quantity }}</p>
                    <p><span class="font-semibold">คอมมิชชั่น:</span> {{ $product->commission_rate }}%</p>
                    <p><span class="font-semibold">ยอดขาย:</span> {{ $stats['total_sales'] ?? 0 }} ชิ้น</p>
                    <p><span class="font-semibold">รายได้:</span> ฿{{ number_format($stats['total_revenue'] ?? 0, 2) }}</p>
                    <p><span class="font-semibold">สถานะ:</span>
                        @if($product->is_active)
                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">ใช้งาน</span>
                        @else
                            <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">ไม่ใช้งาน</span>
                        @endif
                    </p>
                </div>
            </div>
            <div>
                @if($product->description)
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-2">คำอธิบาย</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $product->description }}</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">ยอดขาย</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total_sales'] ?? 0 }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">รายได้</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">฿{{ number_format($stats['total_revenue'] ?? 0) }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">รีวิว</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total_reviews'] ?? 0 }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">คะแนนเฉลี่ย</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['average_rating'] ?? 0, 1) }} ⭐</p>
        </div>
    </div>
</div>

<!-- Edit Product Modal -->
<div id="editProductModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 overflow-y-auto">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-2xl p-6 w-full max-w-2xl mx-4 my-8">
        <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white">แก้ไขสินค้า</h2>
        <form action="{{ route('admin.ecommerce.products.update', $product) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="space-y-4 max-h-[70vh] overflow-y-auto pr-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อสินค้า *</label>
                    <input type="text" name="name" value="{{ $product->name }}" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">หมวดหมู่ *</label>
                    <select name="category_id" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white">
                        <option value="">เลือกหมวดหมู่</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ $product->category_id == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">SKU</label>
                    <input type="text" name="sku" value="{{ $product->sku }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คำอธิบาย</label>
                    <textarea name="description" rows="3" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white">{{ $product->description }}</textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ราคา *</label>
                        <input type="number" name="price" step="0.01" value="{{ $product->price }}" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ราคาเทียบเคียง</label>
                        <input type="number" name="compare_at_price" step="0.01" value="{{ $product->compare_at_price }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">จำนวนสต็อก</label>
                        <input type="number" name="stock_quantity" value="{{ $product->stock_quantity }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">% คอมมิชชั่น</label>
                        <input type="number" name="commission_rate" step="0.01" value="{{ $product->commission_rate }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white">
                    </div>
                </div>

                <div class="flex items-center space-x-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" value="1" {{ $product->is_active ? 'checked' : '' }} class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">ใช้งาน</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="is_featured" value="1" {{ $product->is_featured ? 'checked' : '' }} class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">สินค้าแนะนำ</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="track_inventory" value="1" {{ $product->track_inventory ? 'checked' : '' }} class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">ติดตามสต็อก</span>
                    </label>
                </div>
            </div>

            <div class="flex justify-end space-x-2 mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                <button type="button" onclick="document.getElementById('editProductModal').classList.add('hidden')" class="px-4 py-2 bg-gray-300 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-400 dark:hover:bg-gray-600">
                    ยกเลิก
                </button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    บันทึกการแก้ไข
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
