@extends('layouts.admin-v3')

@section('title', 'สร้างหน้าใหม่')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.pages.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200">
            ← กลับ
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">สร้างหน้าใหม่</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">สร้างหน้าเพจใหม่สำหรับเว็บไซต์</p>
        </div>
    </div>

    <!-- Form -->
    <form action="{{ route('admin.pages.store') }}" method="POST" class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
        @csrf

        <div class="space-y-6">
            <!-- Title -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    ชื่อหน้า <span class="text-red-500">*</span>
                </label>
                <input type="text" name="title" value="{{ old('title') }}" required
                       class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500 @error('title') border-red-500 @enderror"
                       placeholder="เช่น เกี่ยวกับเรา">
                @error('title')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Slug -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Slug (URL)
                </label>
                <input type="text" name="slug" value="{{ old('slug') }}"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500 @error('slug') border-red-500 @enderror"
                       placeholder="เว้นว่างเพื่อสร้างอัตโนมัติจากชื่อ">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">ถ้าเว้นว่างจะถูกสร้างจากชื่อโดยอัตโนมัติ</p>
                @error('slug')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Type -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    ประเภท <span class="text-red-500">*</span>
                </label>
                <select name="type" required
                        class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500 @error('type') border-red-500 @enderror">
                    @foreach($types as $key => $label)
                        <option value="{{ $key }}" {{ old('type') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('type')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Content -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    เนื้อหา <span class="text-red-500">*</span>
                </label>
                <textarea name="content" rows="15" required
                          class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500 font-mono text-sm @error('content') border-red-500 @enderror"
                          placeholder="ใส่เนื้อหา HTML ได้">{{ old('content') }}</textarea>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">รองรับ HTML</p>
                @error('content')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Sort Order -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    ลำดับการแสดง
                </label>
                <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500 @error('sort_order') border-red-500 @enderror">
                @error('sort_order')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Published -->
            <div class="flex items-center">
                <input type="checkbox" name="is_published" value="1" id="is_published"
                       {{ old('is_published', true) ? 'checked' : '' }}
                       class="w-4 h-4 text-indigo-600 border-gray-300 dark:border-slate-600 rounded focus:ring-indigo-500">
                <label for="is_published" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                    เผยแพร่ทันที
                </label>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-3 mt-6 pt-6 border-t dark:border-slate-700">
            <a href="{{ route('admin.pages.index') }}"
               class="px-6 py-2 bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-slate-600 transition">
                ยกเลิก
            </a>
            <button type="submit"
                    class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                สร้างหน้า
            </button>
        </div>
    </form>
</div>
@endsection
