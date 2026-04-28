@extends('layouts.admin-v3')

@section('title', 'แก้ไขแบนเนอร์: ' . $banner->name)

@section('content')
<div class="container mx-auto px-4 py-8 max-w-2xl">
    <div class="mb-6">
        <a href="{{ route('admin.fortune.banners.index') }}"
           class="text-blue-600 hover:text-blue-800 dark:text-blue-400 mb-4 inline-block">
            ← กลับ
        </a>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
            ✏️ แก้ไขแบนเนอร์
        </h1>
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

    {{-- Current image preview --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-4 mb-4">
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">รูปปัจจุบัน:</p>
        <img src="{{ $banner->image_url }}" class="max-w-full max-h-64 rounded border">
        <div class="text-xs text-gray-500 dark:text-gray-400 mt-2">
            {{ $banner->width }}×{{ $banner->height }} • {{ round(($banner->file_size ?? 0) / 1024) }} KB
            • ส่งไปแล้ว {{ number_format($banner->send_count) }} ครั้ง
        </div>
    </div>

    <form action="{{ route('admin.fortune.banners.update', $banner) }}"
          method="POST"
          enctype="multipart/form-data"
          x-data="{ preview: null, onFileChange(e) { const f = e.target.files[0]; if (!f) { this.preview = null; return; } const r = new FileReader(); r.onload = (ev) => this.preview = ev.target.result; r.readAsDataURL(f); } }"
          class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 space-y-4">
        @csrf @method('PUT')

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                ชื่อแบนเนอร์ <span class="text-red-500">*</span>
            </label>
            <input type="text" name="name" required maxlength="100"
                   value="{{ old('name', $banner->name) }}"
                   class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-white">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                คำอธิบาย
            </label>
            <textarea name="description" rows="2" maxlength="500"
                      class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-white">{{ old('description', $banner->description) }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                เปลี่ยนรูป (optional)
            </label>
            <input type="file" name="image" accept="image/png,image/jpg,image/jpeg"
                   @change="onFileChange($event)"
                   class="w-full text-sm">
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">เว้นไว้ถ้าไม่ต้องการเปลี่ยนรูป</p>

            <template x-if="preview">
                <div class="mt-3 p-2 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg">
                    <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">Preview รูปใหม่:</p>
                    <img :src="preview" class="max-h-64 rounded">
                </div>
            </template>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    ลำดับ
                </label>
                <input type="number" name="sort_order" min="0" value="{{ old('sort_order', $banner->sort_order) }}"
                       class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>

            <label class="flex items-center gap-2 mt-7">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $banner->is_active))
                       class="w-5 h-5 text-blue-600">
                <span class="text-sm text-gray-700 dark:text-gray-300">เปิดใช้งาน</span>
            </label>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit"
                    class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow transition">
                💾 บันทึก
            </button>
            <a href="{{ route('admin.fortune.banners.index') }}"
               class="px-6 py-2.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
                ยกเลิก
            </a>
        </div>
    </form>
</div>
@endsection
