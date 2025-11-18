@extends('layouts.admin-v3')

@section('title', 'สร้าง Cashback Setting')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-br from-green-600 via-emerald-600 to-teal-600 rounded-2xl shadow-2xl p-8 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-3xl font-bold mb-2">สร้าง Cashback Setting ใหม่</h2>
                <p class="text-green-100 text-sm">กำหนดเงื่อนไขและอัตรา Cashback</p>
            </div>
            <a href="{{ route('admin.cashback.index') }}" class="bg-white/20 backdrop-blur-sm text-white px-6 py-3 rounded-lg font-semibold hover:bg-white/30 transition-all">
                ← ย้อนกลับ
            </a>
        </div>
    </div>

    <!-- Form -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-8">
        <form action="{{ route('admin.cashback.store') }}" method="POST" class="space-y-6" x-data="{ type: 'global', valueType: 'percentage' }">
            @csrf

            <!-- Type Selection -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">ประเภท Cashback <span class="text-red-500">*</span></label>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <label class="relative flex items-center p-4 border-2 border-gray-300 dark:border-slate-600 rounded-lg cursor-pointer hover:border-green-500 dark:hover:border-green-500 transition" :class="type === 'global' ? 'border-green-500 bg-green-50 dark:bg-green-900/20' : ''">
                        <input type="radio" name="type" value="global" x-model="type" class="mr-3" required>
                        <div>
                            <div class="font-semibold text-gray-900 dark:text-white">🌐 Global Cashback</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">ใช้กับทุกสินค้าในระบบ</div>
                        </div>
                    </label>

                    <label class="relative flex items-center p-4 border-2 border-gray-300 dark:border-slate-600 rounded-lg cursor-pointer hover:border-green-500 dark:hover:border-green-500 transition" :class="type === 'product' ? 'border-green-500 bg-green-50 dark:bg-green-900/20' : ''">
                        <input type="radio" name="type" value="product" x-model="type" class="mr-3" required>
                        <div>
                            <div class="font-semibold text-gray-900 dark:text-white">📦 Product-specific Cashback</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">ใช้กับสินค้าเฉพาะ</div>
                        </div>
                    </label>
                </div>
                @error('type')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Product Selection (conditional) -->
            <div x-show="type === 'product'" x-transition>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เลือกสินค้า <span class="text-red-500">*</span></label>
                <select name="product_id" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    <option value="">-- เลือกสินค้า --</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                            {{ $product->name }} ({{ number_format($product->price, 2) }} THB)
                        </option>
                    @endforeach
                </select>
                @error('product_id')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Value Type Selection -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">ประเภทค่า Cashback <span class="text-red-500">*</span></label>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <label class="relative flex items-center p-4 border-2 border-gray-300 dark:border-slate-600 rounded-lg cursor-pointer hover:border-green-500 dark:hover:border-green-500 transition" :class="valueType === 'percentage' ? 'border-green-500 bg-green-50 dark:bg-green-900/20' : ''">
                        <input type="radio" name="value_type" value="percentage" x-model="valueType" class="mr-3" required>
                        <div>
                            <div class="font-semibold text-gray-900 dark:text-white">📊 เปอร์เซ็นต์ (%)</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">คิดจากยอดคำสั่งซื้อ</div>
                        </div>
                    </label>

                    <label class="relative flex items-center p-4 border-2 border-gray-300 dark:border-slate-600 rounded-lg cursor-pointer hover:border-green-500 dark:hover:border-green-500 transition" :class="valueType === 'fixed' ? 'border-green-500 bg-green-50 dark:bg-green-900/20' : ''">
                        <input type="radio" name="value_type" value="fixed" x-model="valueType" class="mr-3" required>
                        <div>
                            <div class="font-semibold text-gray-900 dark:text-white">💰 จำนวนคงที่ (THB)</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Cashback เท่ากันทุกครั้ง</div>
                        </div>
                    </label>
                </div>
                @error('value_type')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Value Input -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    <span x-show="valueType === 'percentage'">เปอร์เซ็นต์ Cashback</span>
                    <span x-show="valueType === 'fixed'">จำนวน Cashback</span>
                    <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input type="number" name="value" value="{{ old('value') }}" step="0.01" min="0" max="100" required class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent" placeholder="0.00">
                    <span class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400" x-text="valueType === 'percentage' ? '%' : 'THB'"></span>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    <span x-show="valueType === 'percentage'">ค่าต้องไม่เกิน 100%</span>
                    <span x-show="valueType === 'fixed'">ระบุจำนวนเงินที่ต้องการคืน</span>
                </p>
                @error('value')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Min Order Amount -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ยอดขั้นต่ำที่ต้องซื้อ (THB)</label>
                    <input type="number" name="min_order_amount" value="{{ old('min_order_amount') }}" step="0.01" min="0" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent" placeholder="0.00">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ยอดคำสั่งซื้อขั้นต่ำที่จะได้รับ Cashback</p>
                    @error('min_order_amount')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Max Cashback Amount -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Cashback สูงสุด (THB)</label>
                    <input type="number" name="max_cashback_amount" value="{{ old('max_cashback_amount') }}" step="0.01" min="0" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent" placeholder="0.00">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">จำนวน Cashback สูงสุดที่จะได้รับต่อคำสั่งซื้อ</p>
                    @error('max_cashback_amount')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Description -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">คำอธิบาย</label>
                <textarea name="description" rows="3" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent" placeholder="คำอธิบายเพิ่มเติมเกี่ยวกับ Cashback นี้">{{ old('description') }}</textarea>
                @error('description')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Active Status -->
            <div class="flex items-center">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active') ? 'checked' : 'checked' }} class="mr-3 rounded border-gray-300 dark:border-slate-600 text-green-600 focus:ring-green-500">
                <label for="is_active" class="text-sm font-medium text-gray-700 dark:text-gray-300">เปิดใช้งานทันทีหลังสร้าง</label>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200 dark:border-slate-700">
                <a href="{{ route('admin.cashback.index') }}" class="px-6 py-2 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 transition">
                    ยกเลิก
                </a>
                <button type="submit" class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition">
                    💾 บันทึก
                </button>
            </div>
        </form>
    </div>

    <!-- Help Guide -->
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6">
        <h3 class="text-lg font-bold text-blue-900 dark:text-blue-300 mb-3">💡 คำแนะนำ</h3>
        <ul class="space-y-2 text-sm text-blue-800 dark:text-blue-300">
            <li><strong>Global Cashback:</strong> ใช้กับทุกสินค้า เหมาะสำหรับโปรโมชันทั่วไป</li>
            <li><strong>Product Cashback:</strong> ใช้กับสินค้าเฉพาะ เหมาะสำหรับสินค้าพิเศษ</li>
            <li><strong>Percentage:</strong> คิดเป็น % จากยอดซื้อ เช่น 5% ของ 1,000 = 50 บาท</li>
            <li><strong>Fixed Amount:</strong> คืนเงินเท่ากันทุกคำสั่งซื้อ เช่น คืน 100 บาททุกครั้ง</li>
            <li><strong>ยอดขั้นต่ำ:</strong> กำหนดยอดซื้อขั้นต่ำเพื่อได้รับ Cashback</li>
            <li><strong>Cashback สูงสุด:</strong> จำกัด Cashback สูงสุดต่อคำสั่งซื้อ (ใช้กับ % เพื่อป้องกันการคืนมากเกินไป)</li>
        </ul>
    </div>
</div>

<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
@endsection
