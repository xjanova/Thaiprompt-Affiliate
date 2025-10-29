@extends('layouts.admin')

@section('title', 'แก้ไขสไลด์')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">แก้ไขสไลด์</h2>
            <p class="text-gray-600 mt-1">อัพเดตรูปภาพและตั้งค่าสไลด์</p>
        </div>
        <a href="{{ route('admin.sliders.index') }}" class="px-6 py-3 bg-gray-200 text-gray-700 font-semibold rounded-lg hover:bg-gray-300 transition">
            ← กลับ
        </a>
    </div>

    <!-- Form -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <form method="POST" action="{{ route('admin.sliders.update', $slider) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="space-y-6">
                <!-- Current Image -->
                @if($slider->image)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">รูปภาพปัจจุบัน</label>
                        <img src="{{ asset($slider->image) }}" alt="{{ $slider->title }}" class="h-32 rounded-lg shadow-md">
                    </div>
                @endif

                <!-- Image Upload -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">รูปภาพใหม่ (ไม่บังคับ)</label>
                    <input type="file" name="image" accept="image/*"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <p class="text-xs text-gray-500 mt-1">ขนาดแนะนำ: 1920x600px, สูงสุด 5MB (JPG, PNG, GIF) - เว้นว่างหากไม่ต้องการเปลี่ยน</p>
                    @error('image')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Title -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">หัวข้อ (ไม่บังคับ)</label>
                    <input type="text" name="title" value="{{ old('title', $slider->title) }}" maxlength="255"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    @error('title')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">คำอธิบาย (ไม่บังคับ)</label>
                    <textarea name="description" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">{{ old('description', $slider->description) }}</textarea>
                    @error('description')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Link -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ลิงก์ (ไม่บังคับ)</label>
                    <input type="url" name="link" value="{{ old('link', $slider->link) }}" placeholder="https://example.com"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <p class="text-xs text-gray-500 mt-1">ลิงก์ที่จะไปเมื่อคลิกที่สไลด์</p>
                    @error('link')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Order -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ลำดับ</label>
                    <input type="number" name="order" value="{{ old('order', $slider->order) }}" min="0"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <p class="text-xs text-gray-500 mt-1">ลำดับการแสดงสไลด์ (เลขน้อยแสดงก่อน)</p>
                    @error('order')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Active Status -->
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $slider->is_active) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="ml-2 text-sm text-gray-700">เปิดใช้งานสไลด์นี้</span>
                    </label>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="flex gap-3 mt-8">
                <button type="submit" class="px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition shadow-md">
                    💾 บันทึก
                </button>
                <a href="{{ route('admin.sliders.index') }}" class="px-6 py-3 bg-gray-200 text-gray-700 font-semibold rounded-lg hover:bg-gray-300 transition shadow-md">
                    ❌ ยกเลิก
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
