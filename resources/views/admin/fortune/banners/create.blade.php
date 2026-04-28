@extends('layouts.admin-v3')

@section('title', 'อัพโหลดแบนเนอร์ใหม่')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-2xl">
    <div class="mb-6">
        <a href="{{ route('admin.fortune.banners.index') }}"
           class="text-blue-600 hover:text-blue-800 dark:text-blue-400 mb-4 inline-block">
            ← กลับ
        </a>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
            ➕ อัพโหลดแบนเนอร์ใหม่
        </h1>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            ระบบจะ resize เป็น 1280px wide + แปลงเป็น JPEG quality 85 อัตโนมัติ
        </p>
    </div>

    @if($errors->any())
        <div class="mb-4 p-4 rounded-lg bg-red-50 dark:bg-red-900/30 text-red-800 dark:text-red-200">
            <ul class="list-disc list-inside text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.fortune.banners.store') }}"
          method="POST"
          enctype="multipart/form-data"
          x-data="{
              preview: null,
              filename: null,
              onFileChange(e) {
                  const file = e.target.files[0];
                  if (!file) { this.preview = null; this.filename = null; return; }
                  this.filename = file.name;
                  const reader = new FileReader();
                  reader.onload = (ev) => this.preview = ev.target.result;
                  reader.readAsDataURL(file);
              }
          }"
          class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                ชื่อแบนเนอร์ <span class="text-red-500">*</span>
            </label>
            <input type="text" name="name" required maxlength="100"
                   value="{{ old('name') }}"
                   placeholder="เช่น: แบนเนอร์ #5 — promo สงกรานต์"
                   class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-white">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                คำอธิบาย (optional)
            </label>
            <textarea name="description" rows="2" maxlength="500"
                      class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-white">{{ old('description') }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                ไฟล์รูป <span class="text-red-500">*</span>
            </label>
            <input type="file" name="image" required accept="image/png,image/jpg,image/jpeg"
                   @change="onFileChange($event)"
                   class="w-full text-sm">
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                รองรับ PNG, JPG, JPEG • สูงสุด 10 MB • แนะนำ aspect 16:9 (เช่น 1920×1080, 1280×720)
            </p>

            <template x-if="preview">
                <div class="mt-3 p-2 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg">
                    <p class="text-xs text-gray-600 dark:text-gray-400 mb-1" x-text="filename"></p>
                    <img :src="preview" class="max-h-64 rounded">
                </div>
            </template>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    ลำดับ
                </label>
                <input type="number" name="sort_order" min="0" value="{{ old('sort_order', 0) }}"
                       class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">เลขน้อย = มาก่อน</p>
            </div>

            <label class="flex items-center gap-2 mt-7">
                <input type="checkbox" name="is_active" value="1" checked
                       class="w-5 h-5 text-blue-600">
                <span class="text-sm text-gray-700 dark:text-gray-300">เปิดใช้งานทันที</span>
            </label>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit"
                    class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow transition">
                💾 อัพโหลด
            </button>
            <a href="{{ route('admin.fortune.banners.index') }}"
               class="px-6 py-2.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
                ยกเลิก
            </a>
        </div>
    </form>
</div>
@endsection
