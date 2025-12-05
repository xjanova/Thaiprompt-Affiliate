@extends('layouts.admin')

@section('title', 'สร้าง Banner ใหม่')

@section('content')
<div class="container mx-auto px-4 py-6">
    {{-- Header --}}
    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('admin.mobile-app.banners.index') }}"
           class="p-2 rounded-lg bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 transition">
            <span class="text-xl">←</span>
        </a>
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                <span class="text-4xl">➕</span>
                สร้าง Banner ใหม่
            </h1>
            <p class="text-gray-500 dark:text-gray-400 mt-2">
                สร้าง Banner โฆษณาสำหรับแสดงในแอพมือถือ
            </p>
        </div>
    </div>

    {{-- Form --}}
    <form action="{{ route('admin.mobile-app.banners.store') }}" method="POST" enctype="multipart/form-data"
          x-data="bannerForm()" class="max-w-4xl">
        @csrf

        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 space-y-6">
            {{-- Title --}}
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    ชื่อ Banner <span class="text-red-500">*</span>
                </label>
                <input type="text" name="title" id="title" required
                       value="{{ old('title') }}"
                       class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600
                              bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                              focus:ring-2 focus:ring-orange-500 focus:border-transparent transition"
                       placeholder="เช่น โปรโมชั่นพิเศษ 50%">
                @error('title')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Image Upload --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    รูปภาพ Banner <span class="text-red-500">*</span>
                </label>
                <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-6
                            hover:border-orange-500 dark:hover:border-orange-400 transition cursor-pointer"
                     @click="$refs.imageInput.click()"
                     @dragover.prevent="dragging = true"
                     @dragleave.prevent="dragging = false"
                     @drop.prevent="handleDrop($event)"
                     :class="{ 'border-orange-500 bg-orange-50 dark:bg-orange-900/20': dragging }">

                    <input type="file" name="image" ref="imageInput" accept="image/*"
                           class="hidden" @change="handleImageChange($event)">

                    <div x-show="!imagePreview" class="text-center">
                        <div class="text-4xl mb-2">📁</div>
                        <p class="text-gray-600 dark:text-gray-400">
                            คลิกหรือลากไฟล์มาวางที่นี่
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">
                            แนะนำขนาด 1200x400 pixels (อัตราส่วน 3:1)
                        </p>
                    </div>

                    <div x-show="imagePreview" class="text-center">
                        <img :src="imagePreview" alt="Preview"
                             class="max-h-48 mx-auto rounded-lg mb-3">
                        <button type="button" @click.stop="removeImage()"
                                class="text-red-500 hover:text-red-600 text-sm">
                            🗑️ ลบรูปภาพ
                        </button>
                    </div>
                </div>
                @error('image')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Link URL --}}
            <div>
                <label for="link_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    ลิงก์เมื่อคลิก
                </label>
                <input type="url" name="link_url" id="link_url"
                       value="{{ old('link_url') }}"
                       class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600
                              bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                              focus:ring-2 focus:ring-orange-500 focus:border-transparent transition"
                       placeholder="https://example.com/promotion">
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    URL ที่จะเปิดเมื่อผู้ใช้คลิกที่ Banner (ไม่บังคับ)
                </p>
                @error('link_url')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Position --}}
            <div>
                <label for="position" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    ตำแหน่งแสดง <span class="text-red-500">*</span>
                </label>
                <select name="position" id="position" required
                        class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600
                               bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                               focus:ring-2 focus:ring-orange-500 focus:border-transparent transition">
                    <option value="top" {{ old('position', 'top') == 'top' ? 'selected' : '' }}>
                        🔝 ด้านบน (Top)
                    </option>
                    <option value="middle" {{ old('position') == 'middle' ? 'selected' : '' }}>
                        ➖ ตรงกลาง (Middle)
                    </option>
                    <option value="bottom" {{ old('position') == 'bottom' ? 'selected' : '' }}>
                        🔽 ด้านล่าง (Bottom)
                    </option>
                </select>
                @error('position')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Sort Order --}}
            <div>
                <label for="sort_order" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    ลำดับการแสดง
                </label>
                <input type="number" name="sort_order" id="sort_order"
                       value="{{ old('sort_order', 0) }}" min="0"
                       class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600
                              bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                              focus:ring-2 focus:ring-orange-500 focus:border-transparent transition">
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    ตัวเลขน้อยกว่าจะแสดงก่อน
                </p>
                @error('sort_order')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Date Range --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        วันที่เริ่มแสดง
                    </label>
                    <input type="datetime-local" name="start_date" id="start_date"
                           value="{{ old('start_date') }}"
                           class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600
                                  bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                                  focus:ring-2 focus:ring-orange-500 focus:border-transparent transition">
                    @error('start_date')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        วันที่สิ้นสุด
                    </label>
                    <input type="datetime-local" name="end_date" id="end_date"
                           value="{{ old('end_date') }}"
                           class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600
                                  bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                                  focus:ring-2 focus:ring-orange-500 focus:border-transparent transition">
                    @error('end_date')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Active Status --}}
            <div class="flex items-center gap-3">
                <input type="checkbox" name="is_active" id="is_active" value="1"
                       {{ old('is_active', true) ? 'checked' : '' }}
                       class="w-5 h-5 rounded border-gray-300 text-orange-500 focus:ring-orange-500">
                <label for="is_active" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    เปิดใช้งานทันที
                </label>
            </div>

            {{-- Submit Buttons --}}
            <div class="flex items-center gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <button type="submit"
                        class="px-6 py-3 bg-orange-500 hover:bg-orange-600 text-white font-medium rounded-xl transition">
                    💾 บันทึก Banner
                </button>
                <a href="{{ route('admin.mobile-app.banners.index') }}"
                   class="px-6 py-3 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600
                          text-gray-700 dark:text-gray-300 font-medium rounded-xl transition">
                    ยกเลิก
                </a>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
    function bannerForm() {
        return {
            dragging: false,
            imagePreview: null,

            handleImageChange(event) {
                const file = event.target.files[0];
                if (file) {
                    this.showPreview(file);
                }
            },

            handleDrop(event) {
                this.dragging = false;
                const file = event.dataTransfer.files[0];
                if (file && file.type.startsWith('image/')) {
                    this.$refs.imageInput.files = event.dataTransfer.files;
                    this.showPreview(file);
                }
            },

            showPreview(file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.imagePreview = e.target.result;
                };
                reader.readAsDataURL(file);
            },

            removeImage() {
                this.imagePreview = null;
                this.$refs.imageInput.value = '';
            }
        };
    }
</script>
@endpush
@endsection
