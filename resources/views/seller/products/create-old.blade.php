@extends('layouts.seller')

@section('title', 'เพิ่มสินค้าใหม่')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('seller.products.index') }}" class="text-blue-600 hover:text-blue-800 text-sm mb-2 inline-block">
                ← กลับไปรายการสินค้า
            </a>
            <h1 class="text-3xl font-bold text-gray-800 dark:text-white">เพิ่มสินค้าใหม่</h1>
        </div>
    </div>

    <!-- Form -->
    <form action="{{ route('seller.products.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Basic Information -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-white mb-4">ข้อมูลพื้นฐาน</h2>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                ชื่อสินค้า <span class="text-red-600">*</span>
                            </label>
                            <input type="text" name="name" value="{{ old('name') }}" required
                                   class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg"
                                   placeholder="กรอกชื่อสินค้า">
                            @error('name')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                คำอธิบายสั้น
                            </label>
                            <textarea name="short_description" rows="2"
                                      class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg"
                                      placeholder="คำอธิบายสั้นๆ สำหรับแสดงในรายการสินค้า">{{ old('short_description') }}</textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                คำอธิบายสินค้า
                            </label>
                            <textarea name="description" rows="6"
                                      class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg"
                                      placeholder="คำอธิบายรายละเอียดสินค้า">{{ old('description') }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Pricing -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-white mb-4">ราคา</h2>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                ราคาขาย <span class="text-red-600">*</span>
                            </label>
                            <input type="number" name="price" value="{{ old('price') }}" step="0.01" min="0" required
                                   class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg"
                                   placeholder="0.00">
                            @error('price')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                ราคาเปรียบเทียบ
                            </label>
                            <input type="number" name="compare_at_price" value="{{ old('compare_at_price') }}" step="0.01" min="0"
                                   class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg"
                                   placeholder="0.00">
                            <p class="text-xs text-gray-500 mt-1">ราคาก่อนลด (แสดงขีดทับ)</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                ราคาทุน
                            </label>
                            <input type="number" name="cost_price" value="{{ old('cost_price') }}" step="0.01" min="0"
                                   class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg"
                                   placeholder="0.00">
                            <p class="text-xs text-gray-500 mt-1">สำหรับคำนวณกำไร</p>
                        </div>
                    </div>
                </div>

                <!-- Inventory -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-white mb-4">สต็อก</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                SKU
                            </label>
                            <input type="text" name="sku" value="{{ old('sku') }}"
                                   class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg"
                                   placeholder="SKU (จะสร้างอัตโนมัติถ้าไม่กรอก)">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                จำนวนสต็อก <span class="text-red-600">*</span>
                            </label>
                            <input type="number" name="stock_quantity" value="{{ old('stock_quantity', 0) }}" min="0" required
                                   class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg">
                        </div>
                    </div>
                </div>

                <!-- Images -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-white mb-4">รูปภาพสินค้า</h2>

                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                รูปหลัก <span class="text-red-600">*</span>
                            </label>
                            <x-image-upload
                                name="main_image"
                                :multiple="false"
                                :maxFiles="1"
                                :maxSize="5"
                                :required="true"
                            />
                            <p class="text-xs text-gray-500 mt-2">ขนาดแนะนำ: 1200x1200px สำหรับคุณภาพที่ดีที่สุด</p>
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
                            />
                            <p class="text-xs text-gray-500 mt-2">อัปโหลดได้สูงสุด 10 รูป, ขนาดรูปละไม่เกิน 5MB</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Category -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-white mb-4">หมวดหมู่</h2>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            เลือกหมวดหมู่ <span class="text-red-600">*</span>
                        </label>
                        <select name="category_id" required
                                class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg">
                            <option value="">-- เลือกหมวดหมู่ --</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Product Details -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-white mb-4">รายละเอียดเพิ่มเติม</h2>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                แบรนด์
                            </label>
                            <input type="text" name="brand" value="{{ old('brand') }}"
                                   class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                น้ำหนัก (กรัม)
                            </label>
                            <input type="number" name="weight" value="{{ old('weight') }}" step="0.01" min="0"
                                   class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                ขนาด
                            </label>
                            <input type="text" name="dimensions" value="{{ old('dimensions') }}"
                                   class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg"
                                   placeholder="เช่น 10x20x5 cm">
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
                    <div class="space-y-3">
                        <button type="submit"
                                class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition">
                            บันทึกสินค้า
                        </button>
                        <a href="{{ route('seller.products.index') }}"
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
