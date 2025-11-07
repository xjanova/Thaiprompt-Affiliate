@extends('layouts.admin')

@section('title', 'แก้ไขสินค้า: ' . $product->name)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('admin.ecommerce.products.index') }}" class="text-orange-600 hover:text-orange-800 text-sm mb-2 inline-block">
                ← กลับไปรายการสินค้า
            </a>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">แก้ไขสินค้า</h1>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
            {{ session('error') }}
        </div>
    @endif

    <!-- Form -->
    <form action="{{ route('admin.ecommerce.products.update', $product) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Basic Information -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">ข้อมูลพื้นฐาน</h2>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                ชื่อสินค้า <span class="text-red-600">*</span>
                            </label>
                            <input type="text" name="name" value="{{ old('name', $product->name) }}" required
                                   class="w-full border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white rounded-lg">
                            @error('name')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                คำอธิบายสั้น
                            </label>
                            <textarea name="short_description" rows="2"
                                      class="w-full border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white rounded-lg">{{ old('short_description', $product->short_description) }}</textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                คำอธิบายสินค้า
                            </label>
                            <textarea name="description" rows="6"
                                      class="w-full border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white rounded-lg">{{ old('description', $product->description) }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Pricing -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">ราคา</h2>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                ราคาขาย <span class="text-red-600">*</span>
                            </label>
                            <input type="number" name="price" value="{{ old('price', $product->price) }}" step="0.01" min="0" required
                                   class="w-full border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white rounded-lg">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                ราคาเปรียบเทียบ
                            </label>
                            <input type="number" name="compare_at_price" value="{{ old('compare_at_price', $product->compare_at_price) }}" step="0.01" min="0"
                                   class="w-full border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white rounded-lg">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                ราคาทุน
                            </label>
                            <input type="number" name="cost_price" value="{{ old('cost_price', $product->cost_price) }}" step="0.01" min="0"
                                   class="w-full border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white rounded-lg">
                        </div>
                    </div>
                </div>

                <!-- Inventory -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">สต็อก</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                SKU
                            </label>
                            <input type="text" name="sku" value="{{ old('sku', $product->sku) }}"
                                   class="w-full border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white rounded-lg">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                จำนวนสต็อก <span class="text-red-600">*</span>
                            </label>
                            <input type="number" name="stock_quantity" value="{{ old('stock_quantity', $product->stock_quantity) }}" min="0" required
                                   class="w-full border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white rounded-lg">
                        </div>
                    </div>
                </div>

                <!-- Images -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">รูปภาพสินค้า</h2>

                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                รูปหลัก
                            </label>
                            <x-image-upload
                                name="main_image"
                                :multiple="false"
                                :maxFiles="1"
                                :maxSize="5"
                                :existingImages="$product->main_image_url ? [[
                                    'url' => Storage::url($product->main_image_url),
                                    'name' => 'รูปหลัก'
                                ]] : []"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                รูปเพิ่มเติม (Gallery)
                            </label>
                            <x-image-upload
                                name="images"
                                :multiple="true"
                                :maxFiles="10"
                                :maxSize="5"
                                :existingImages="$product->images->map(function($img) {
                                    return [
                                        'id' => $img->id,
                                        'url' => Storage::url($img->image_url),
                                        'name' => 'Gallery Image'
                                    ];
                                })->toArray()"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Category -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">หมวดหมู่</h2>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            เลือกหมวดหมู่ <span class="text-red-600">*</span>
                        </label>
                        <select name="category_id" required
                                class="w-full border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white rounded-lg">
                            <option value="">-- เลือกหมวดหมู่ --</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Product Details -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">รายละเอียดเพิ่มเติม</h2>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                แบรนด์
                            </label>
                            <input type="text" name="brand" value="{{ old('brand', $product->brand) }}"
                                   class="w-full border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white rounded-lg">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                น้ำหนัก (กรัม)
                            </label>
                            <input type="number" name="weight" value="{{ old('weight', $product->weight) }}" step="0.01" min="0"
                                   class="w-full border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white rounded-lg">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                % คอมมิชชั่น
                            </label>
                            <input type="number" name="commission_rate" value="{{ old('commission_rate', $product->commission_rate) }}" step="0.01" min="0" max="100"
                                   class="w-full border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white rounded-lg">
                        </div>

                        <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                            <label class="flex items-center mb-2">
                                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">ใช้งาน</span>
                            </label>
                            <label class="flex items-center mb-2">
                                <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $product->is_featured) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">สินค้าแนะนำ</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="track_inventory" value="1" {{ old('track_inventory', $product->track_inventory) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">ติดตามสต็อก</span>
                            </label>
                        </div>

                        <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                            <p class="text-sm text-gray-500 dark:text-gray-400">ยอดขาย: <span class="font-semibold text-gray-900 dark:text-white">{{ number_format($product->sales_count) }}</span></p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">สร้างเมื่อ: {{ $product->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow p-6">
                    <div class="space-y-3">
                        <button type="submit"
                                class="w-full px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg font-medium transition">
                            บันทึกการเปลี่ยนแปลง
                        </button>
                        <a href="{{ route('admin.ecommerce.products.index') }}"
                           class="block w-full px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-center rounded-lg font-medium transition">
                            ยกเลิก
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
