@extends('layouts.seller')

@section('title', 'ตั้งค่า POS')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">⚙️ ตั้งค่า POS</h1>
            <p class="text-gray-500 mt-1">กำหนดค่าการใช้งานระบบ POS</p>
        </div>
        <a href="{{ route('seller.pos.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
            ← กลับ
        </a>
    </div>

    <form action="{{ route('seller.pos.settings.update') }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Store Display -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">การแสดงผลร้านค้า</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ชื่อร้านที่แสดง</label>
                    <input type="text" name="store_display_name" value="{{ old('store_display_name', $settings->store_display_name ?? '') }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">สีธีม</label>
                    <input type="color" name="theme_color" value="{{ old('theme_color', $settings->theme_color ?? '#3B82F6') }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
        </div>

        <!-- Receipt Settings -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">การตั้งค่าใบเสร็จ</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ส่วนหัวใบเสร็จ</label>
                    <input type="text" name="receipt_header" value="{{ old('receipt_header', $settings->receipt_header ?? '') }}"
                        placeholder="ขอบคุณที่ใช้บริการ"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ส่วนท้ายใบเสร็จ</label>
                    <input type="text" name="receipt_footer" value="{{ old('receipt_footer', $settings->receipt_footer ?? '') }}"
                        placeholder="โปรดเก็บใบเสร็จไว้เป็นหลักฐาน"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ขนาดใบเสร็จ</label>
                    <select name="receipt_size" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="58mm" {{ old('receipt_size', $settings->receipt_size ?? '') == '58mm' ? 'selected' : '' }}>58mm</option>
                        <option value="80mm" {{ old('receipt_size', $settings->receipt_size ?? '') == '80mm' ? 'selected' : '' }}>80mm</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">จำนวนสำเนาใบเสร็จ</label>
                    <input type="number" name="receipt_copies" value="{{ old('receipt_copies', $settings->receipt_copies ?? 1) }}"
                        min="1" max="5"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex items-center">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="auto_print_receipt" value="1" {{ old('auto_print_receipt', $settings->auto_print_receipt ?? false) ? 'checked' : '' }}
                            class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm font-medium text-gray-700">พิมพ์ใบเสร็จอัตโนมัติ</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Tax Settings -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">การตั้งค่าภาษี</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="flex items-center">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="tax_enabled" value="1" {{ old('tax_enabled', $settings->tax_enabled ?? false) ? 'checked' : '' }}
                            class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm font-medium text-gray-700">เปิดใช้งานภาษี</span>
                    </label>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">อัตราภาษี (%)</label>
                    <input type="number" name="tax_percentage" value="{{ old('tax_percentage', $settings->tax_percentage ?? 7) }}"
                        step="0.01" min="0" max="100"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">เลขประจำตัวผู้เสียภาษี</label>
                    <input type="text" name="tax_id_number" value="{{ old('tax_id_number', $settings->tax_id_number ?? '') }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex items-center">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="tax_inclusive" value="1" {{ old('tax_inclusive', $settings->tax_inclusive ?? false) ? 'checked' : '' }}
                            class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm font-medium text-gray-700">ภาษีรวมในราคา</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Discount Settings -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">การตั้งค่าส่วนลด</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="flex items-center">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="allow_discounts" value="1" {{ old('allow_discounts', $settings->allow_discounts ?? true) ? 'checked' : '' }}
                            class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm font-medium text-gray-700">อนุญาตให้ใช้ส่วนลด</span>
                    </label>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ส่วนลดสูงสุด (%)</label>
                    <input type="number" name="max_discount_percentage" value="{{ old('max_discount_percentage', $settings->max_discount_percentage ?? 10) }}"
                        step="0.01" min="0" max="100"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
        </div>

        <!-- Features -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">ฟีเจอร์</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <label class="flex items-center cursor-pointer p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                    <input type="checkbox" name="dual_screen_enabled" value="1" {{ old('dual_screen_enabled', $settings->dual_screen_enabled ?? false) ? 'checked' : '' }}
                        class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <span class="ml-3">
                        <span class="text-sm font-medium text-gray-900">Dual Screen (จอคู่)</span>
                        <p class="text-xs text-gray-500">รองรับการแสดงผลหน้าจอลูกค้า</p>
                    </span>
                </label>

                <label class="flex items-center cursor-pointer p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                    <input type="checkbox" name="offline_mode_enabled" value="1" {{ old('offline_mode_enabled', $settings->offline_mode_enabled ?? false) ? 'checked' : '' }}
                        class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <span class="ml-3">
                        <span class="text-sm font-medium text-gray-900">Offline Mode</span>
                        <p class="text-xs text-gray-500">ทำงานแบบออฟไลน์ได้</p>
                    </span>
                </label>

                <label class="flex items-center cursor-pointer p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                    <input type="checkbox" name="show_product_images" value="1" {{ old('show_product_images', $settings->show_product_images ?? true) ? 'checked' : '' }}
                        class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <span class="ml-3">
                        <span class="text-sm font-medium text-gray-900">แสดงรูปสินค้า</span>
                        <p class="text-xs text-gray-500">แสดงภาพสินค้าในหน้าขาย</p>
                    </span>
                </label>

                <label class="flex items-center cursor-pointer p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                    <input type="checkbox" name="show_stock_levels" value="1" {{ old('show_stock_levels', $settings->show_stock_levels ?? true) ? 'checked' : '' }}
                        class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <span class="ml-3">
                        <span class="text-sm font-medium text-gray-900">แสดงจำนวนสต็อก</span>
                        <p class="text-xs text-gray-500">แสดงจำนวนสินค้าคงเหลือ</p>
                    </span>
                </label>

                <label class="flex items-center cursor-pointer p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                    <input type="checkbox" name="real_time_stock_sync" value="1" {{ old('real_time_stock_sync', $settings->real_time_stock_sync ?? true) ? 'checked' : '' }}
                        class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <span class="ml-3">
                        <span class="text-sm font-medium text-gray-900">อัปเดตสต็อกแบบเรียลไทม์</span>
                        <p class="text-xs text-gray-500">อัปเดตสต็อกทันทีที่มีการขาย</p>
                    </span>
                </label>

                <label class="flex items-center cursor-pointer p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                    <input type="checkbox" name="prevent_negative_stock" value="1" {{ old('prevent_negative_stock', $settings->prevent_negative_stock ?? true) ? 'checked' : '' }}
                        class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <span class="ml-3">
                        <span class="text-sm font-medium text-gray-900">ป้องกันสต็อกติดลบ</span>
                        <p class="text-xs text-gray-500">ห้ามขายเมื่อสินค้าหมด</p>
                    </span>
                </label>
            </div>
        </div>

        <!-- Submit Buttons -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-end gap-4">
                <a href="{{ route('seller.pos.index') }}" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                    ยกเลิก
                </a>
                <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    💾 บันทึกการตั้งค่า
                </button>
            </div>
        </div>
    </form>
</div>

@if(session('success'))
<script>
    alert('{{ session("success") }}');
</script>
@endif

@if($errors->any())
<script>
    alert('กรุณาตรวจสอบข้อมูลที่กรอก:\n\n{{ implode("\n", $errors->all()) }}');
</script>
@endif
@endsection
