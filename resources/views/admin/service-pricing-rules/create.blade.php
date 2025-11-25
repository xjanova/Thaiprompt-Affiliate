@extends('layouts.admin')

@section('title', $pageTitle ?? 'เพิ่มกฎการคิดราคาใหม่')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="mb-6 p-6 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 shadow-xl">
        <h1 class="text-3xl font-bold bg-gradient-to-r from-orange-600 to-red-600 bg-clip-text text-transparent">
            <i class="fas fa-plus-circle mr-3"></i>{{ $pageTitle ?? 'เพิ่มกฎการคิดราคาใหม่' }}
        </h1>
    </div>

    <form action="{{ route('admin.service-pricing-rules.store') }}" method="POST" class="space-y-6">
        @csrf

        <div class="p-6 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 shadow-xl space-y-4">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">ข้อมูลกฎ</h2>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อกฎ <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 @error('name') border-red-500 @enderror" required>
                @error('name')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คำอธิบาย</label>
                <textarea name="description" rows="2" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700">{{ old('description') }}</textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ประเภท <span class="text-red-500">*</span></label>
                    <select name="type" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 @error('type') border-red-500 @enderror" required>
                        <option value="fixed" {{ old('type') == 'fixed' ? 'selected' : '' }}>ราคาคงที่</option>
                        <option value="distance" {{ old('type') == 'distance' ? 'selected' : '' }}>ตามระยะทาง</option>
                        <option value="time" {{ old('type') == 'time' ? 'selected' : '' }}>ตามเวลา</option>
                        <option value="zone" {{ old('type') == 'zone' ? 'selected' : '' }}>ตามโซน</option>
                        <option value="peak" {{ old('type') == 'peak' ? 'selected' : '' }}>ช่วงพีค</option>
                    </select>
                    @error('type')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ลำดับความสำคัญ</label>
                    <input type="number" name="priority" value="{{ old('priority', 0) }}" min="0" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                    <p class="text-xs text-gray-500 mt-1">เลขน้อย = ตรวจสอบก่อน</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">บริการ (ไม่ระบุ = ทุกบริการ)</label>
                    <select name="service_id" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                        <option value="">ใช้กับทุกบริการ</option>
                        @foreach($services ?? [] as $service)
                            <option value="{{ $service->id }}" {{ old('service_id') == $service->id ? 'selected' : '' }}>{{ $service->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">หมวดหมู่ (ไม่ระบุ = ทุกหมวด)</label>
                    <select name="category_id" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                        <option value="">ใช้กับทุกหมวดหมู่</option>
                        @foreach($categories ?? [] as $category)
                            <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="p-6 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 shadow-xl space-y-4">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">การคำนวณราคา</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ราคาพื้นฐาน (฿) <span class="text-red-500">*</span></label>
                    <input type="number" name="base_price" value="{{ old('base_price', 0) }}" min="0" step="0.01" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 @error('base_price') border-red-500 @enderror" required>
                    @error('base_price')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ราคาต่อกิโลเมตร (฿)</label>
                    <input type="number" name="price_per_km" value="{{ old('price_per_km') }}" min="0" step="0.01" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ราคาต่อนาที (฿)</label>
                    <input type="number" name="price_per_minute" value="{{ old('price_per_minute') }}" min="0" step="0.01" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ตัวคูณ (Multiplier)</label>
                    <input type="number" name="multiplier" value="{{ old('multiplier', 1) }}" min="0" step="0.01" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ระยะทางขั้นต่ำ (km)</label>
                    <input type="number" name="min_distance" value="{{ old('min_distance', 0) }}" min="0" step="0.1" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ระยะทางสูงสุด (km)</label>
                    <input type="number" name="max_distance" value="{{ old('max_distance') }}" min="0" step="0.1" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                    <p class="text-xs text-gray-500 mt-1">ไม่ระบุ = ไม่จำกัด</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', true) ? 'checked' : '' }} class="w-4 h-4 text-orange-600 rounded">
                <label for="is_active" class="text-sm font-medium text-gray-700 dark:text-gray-300">เปิดใช้งานกฎนี้</label>
            </div>
        </div>

        <div class="flex gap-4">
            <button type="submit" class="flex-1 px-6 py-3 bg-gradient-to-r from-orange-500 to-red-600 text-white rounded-xl hover:shadow-lg transition">
                <i class="fas fa-save mr-2"></i>บันทึก
            </button>
            <a href="{{ route('admin.service-pricing-rules.index') }}" class="px-6 py-3 bg-gray-200 text-gray-800 rounded-xl hover:bg-gray-300 transition text-center">
                ยกเลิก
            </a>
        </div>
    </form>
</div>
@endsection
