@extends('layouts.admin')

@section('title', 'เพิ่มหมวดหมู่การทำนาย')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-4xl">
    <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-8">เพิ่มหมวดหมู่การทำนาย</h1>

    <form action="{{ route('admin.fortune.categories.store') }}" method="POST" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ชื่อหมวดหมู่ *</label>
                <input type="text" name="name" required value="{{ old('name') }}"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                       placeholder="ความรัก">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Slug</label>
                <input type="text" name="slug" value="{{ old('slug') }}"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                       placeholder="khwam-rak">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Icon (Emoji)</label>
                <input type="text" name="icon" value="{{ old('icon') }}"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                       placeholder="💕">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">สี (Hex)</label>
                <input type="text" name="color" value="{{ old('color', '#3B82F6') }}"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                       placeholder="#3B82F6">
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">คำอธิบาย</label>
                <textarea name="description" rows="3"
                          class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">{{ old('description') }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ลำดับ</label>
                <input type="number" name="order" value="{{ old('order', 0) }}"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>

            <div class="flex items-center">
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                           class="w-4 h-4 text-blue-600 rounded">
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">เปิดใช้งาน</span>
                </label>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.fortune.categories.index') }}" 
               class="px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition">
                ยกเลิก
            </a>
            <button type="submit" 
                    class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                💾 บันทึก
            </button>
        </div>
    </form>
</div>
@endsection
