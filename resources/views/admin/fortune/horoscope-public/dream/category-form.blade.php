{{-- ฟอร์มเพิ่ม/แก้ไขหมวดหมู่ฝัน --}}
@extends('layouts.admin')

@section('title', $pageTitle)

@section('content')
<div class="container mx-auto px-4 py-8">

    {{-- Header --}}
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('admin.fortune.horoscope-public.dream.categories') }}"
           class="px-3 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
            ← กลับ
        </a>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $pageTitle }}</h1>
    </div>

    @if($errors->any())
        <div class="mb-6 p-4 bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-700 rounded-xl">
            <ul class="list-disc list-inside text-sm text-red-700 dark:text-red-300">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ $category
            ? route('admin.fortune.horoscope-public.dream.categories.update', $category)
            : route('admin.fortune.horoscope-public.dream.categories.store') }}"
          method="POST">
        @csrf
        @if($category)
            @method('PUT')
        @endif

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">📂 ข้อมูลหมวดหมู่</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ชื่อหมวดหมู่ (ไทย) <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name_th" value="{{ old('name_th', $category?->name_th) }}"
                           placeholder="เช่น สัตว์, คน, ธรรมชาติ"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Slug (URL)
                    </label>
                    <input type="text" name="slug" value="{{ old('slug', $category?->slug) }}"
                           placeholder="สร้างอัตโนมัติถ้าปล่อยว่าง"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ไอคอน (Emoji)
                    </label>
                    <input type="text" name="icon" value="{{ old('icon', $category?->icon) }}"
                           placeholder="🐍"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        สี (hex) <span class="text-red-500">*</span>
                    </label>
                    <div class="flex gap-2">
                        <input type="color" name="color_hex" value="{{ old('color_hex', $category?->color_hex ?? '#818cf8') }}"
                               class="w-12 h-10 rounded border border-gray-300 dark:border-gray-600 cursor-pointer">
                        <input type="text" value="{{ old('color_hex', $category?->color_hex ?? '#818cf8') }}" disabled
                               class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ลำดับ <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', $category?->sort_order ?? 0) }}"
                           min="0"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required>
                </div>
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">คำอธิบาย</label>
                <textarea name="description_th" rows="2"
                          placeholder="อธิบายหมวดหมู่สั้นๆ"
                          class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">{{ old('description_th', $category?->description_th) }}</textarea>
            </div>

            <div class="mt-4">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1"
                           class="w-5 h-5 text-indigo-600 rounded focus:ring-indigo-500"
                           {{ old('is_active', $category?->is_active ?? true) ? 'checked' : '' }}>
                    <span class="font-semibold text-gray-900 dark:text-white">เปิดใช้งาน</span>
                </label>
            </div>
        </div>

        {{-- ปุ่มบันทึก --}}
        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.fortune.horoscope-public.dream.categories') }}"
               class="px-6 py-3 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition">
                ← ยกเลิก
            </a>
            <button type="submit"
                    class="px-8 py-3 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white rounded-lg transition font-semibold shadow-lg">
                💾 {{ $category ? 'บันทึก' : 'เพิ่มหมวดหมู่' }}
            </button>
        </div>
    </form>
</div>
@endsection
