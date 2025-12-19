@extends('layouts.admin')

@section('title', 'สร้าง Banner ใหม่')

@push('styles')
{{-- Cropper.js CSS --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
<style>
    .cropper-container {
        max-height: 400px;
    }
    .crop-preview {
        overflow: hidden;
        width: 300px;
        height: 100px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
    }
</style>
@endpush

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
          x-data="bannerForm()" class="max-w-4xl" @submit="prepareSubmit($event)">
        @csrf

        {{-- Hidden input สำหรับรูปที่ครอปแล้ว --}}
        <input type="hidden" name="cropped_image" x-ref="croppedImageInput">

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

            {{-- Image Upload with Cropper --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    รูปภาพ Banner <span class="text-red-500">*</span>
                </label>

                {{-- Upload Area --}}
                <div x-show="!imagePreview"
                     class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-6
                            hover:border-orange-500 dark:hover:border-orange-400 transition cursor-pointer"
                     @click="$refs.imageInput.click()"
                     @dragover.prevent="dragging = true"
                     @dragleave.prevent="dragging = false"
                     @drop.prevent="handleDrop($event)"
                     :class="{ 'border-orange-500 bg-orange-50 dark:bg-orange-900/20': dragging }">

                    <input type="file" name="image" x-ref="imageInput" accept="image/*"
                           class="hidden" @change="handleImageChange($event)">

                    <div class="text-center">
                        <div class="text-4xl mb-2">📁</div>
                        <p class="text-gray-600 dark:text-gray-400">
                            คลิกหรือลากไฟล์มาวางที่นี่
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">
                            แนะนำขนาด 1200x400 pixels (อัตราส่วน 3:1)
                        </p>
                    </div>
                </div>

                {{-- Cropper Area --}}
                <div x-show="imagePreview" x-cloak class="space-y-4">
                    {{-- Image Cropper --}}
                    <div class="bg-gray-100 dark:bg-gray-900 rounded-xl p-4">
                        <img x-ref="cropperImage" :src="imagePreview" alt="Crop Preview"
                             class="max-w-full" style="display: block; max-height: 400px;">
                    </div>

                    {{-- Cropper Controls --}}
                    <div class="flex flex-wrap items-center gap-2">
                        {{-- Zoom --}}
                        <div class="flex items-center gap-2 bg-gray-100 dark:bg-gray-700 rounded-lg px-3 py-2">
                            <span class="text-sm text-gray-600 dark:text-gray-300">ซูม:</span>
                            <button type="button" @click="zoom(-0.1)"
                                    class="w-8 h-8 flex items-center justify-center bg-white dark:bg-gray-600 rounded hover:bg-gray-200 dark:hover:bg-gray-500 transition">
                                ➖
                            </button>
                            <button type="button" @click="zoom(0.1)"
                                    class="w-8 h-8 flex items-center justify-center bg-white dark:bg-gray-600 rounded hover:bg-gray-200 dark:hover:bg-gray-500 transition">
                                ➕
                            </button>
                        </div>

                        {{-- Rotate --}}
                        <div class="flex items-center gap-2 bg-gray-100 dark:bg-gray-700 rounded-lg px-3 py-2">
                            <span class="text-sm text-gray-600 dark:text-gray-300">หมุน:</span>
                            <button type="button" @click="rotate(-90)"
                                    class="w-8 h-8 flex items-center justify-center bg-white dark:bg-gray-600 rounded hover:bg-gray-200 dark:hover:bg-gray-500 transition">
                                ↺
                            </button>
                            <button type="button" @click="rotate(90)"
                                    class="w-8 h-8 flex items-center justify-center bg-white dark:bg-gray-600 rounded hover:bg-gray-200 dark:hover:bg-gray-500 transition">
                                ↻
                            </button>
                        </div>

                        {{-- Reset --}}
                        <button type="button" @click="resetCrop()"
                                class="px-3 py-2 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                            🔄 รีเซ็ต
                        </button>

                        {{-- Remove --}}
                        <button type="button" @click="removeImage()"
                                class="px-3 py-2 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 transition">
                            🗑️ ลบรูป
                        </button>
                    </div>

                    {{-- Preview --}}
                    <div class="flex items-center gap-4">
                        <span class="text-sm text-gray-600 dark:text-gray-400">ตัวอย่าง:</span>
                        <div class="crop-preview dark:border-gray-600"></div>
                    </div>
                </div>

                @error('image')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Link --}}
            <div>
                <label for="link_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    ลิงก์เมื่อคลิก
                </label>
                <input type="text" name="link_url" id="link_url"
                       value="{{ old('link_url') }}"
                       class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600
                              bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                              focus:ring-2 focus:ring-orange-500 focus:border-transparent transition"
                       placeholder="เช่น /products, /promotions, https://example.com">
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    ลิงก์ภายใน (เช่น /products) หรือ URL ภายนอก (ไม่บังคับ)
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
                        :disabled="submitting"
                        :class="{ 'opacity-50 cursor-not-allowed': submitting }"
                        class="px-6 py-3 bg-orange-500 hover:bg-orange-600 text-white font-medium rounded-xl transition flex items-center gap-2">
                    <span x-show="!submitting">💾 บันทึก Banner</span>
                    <span x-show="submitting" class="flex items-center gap-2">
                        <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        กำลังบันทึก...
                    </span>
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

{{-- Cropper.js --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>

@push('scripts')
<script>
    /**
     * Banner Form Component พร้อม Image Cropper
     *
     * ฟีเจอร์:
     * - อัพโหลดรูปภาพ (คลิกหรือลาก)
     * - ครอป/ปรับขนาดรูป
     * - ซูมเข้า/ออก
     * - หมุนรูป
     */
    function bannerForm() {
        return {
            dragging: false,
            imagePreview: null,
            cropper: null,
            submitting: false,

            /**
             * เมื่อเลือกไฟล์
             */
            handleImageChange(event) {
                const file = event.target.files[0];
                if (file) {
                    this.loadImage(file);
                }
            },

            /**
             * เมื่อลากไฟล์มาวาง
             */
            handleDrop(event) {
                this.dragging = false;
                const file = event.dataTransfer.files[0];
                if (file && file.type.startsWith('image/')) {
                    this.$refs.imageInput.files = event.dataTransfer.files;
                    this.loadImage(file);
                }
            },

            /**
             * โหลดรูปและเริ่ม cropper
             */
            loadImage(file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.imagePreview = e.target.result;

                    // รอให้ DOM อัพเดทก่อนเริ่ม cropper
                    this.$nextTick(() => {
                        this.initCropper();
                    });
                };
                reader.readAsDataURL(file);
            },

            /**
             * เริ่มต้น Cropper.js
             */
            initCropper() {
                // ทำลาย cropper เดิมถ้ามี
                if (this.cropper) {
                    this.cropper.destroy();
                }

                const image = this.$refs.cropperImage;
                if (!image) return;

                this.cropper = new Cropper(image, {
                    aspectRatio: 3 / 1,  // อัตราส่วน 3:1 สำหรับ banner
                    viewMode: 1,
                    dragMode: 'move',
                    autoCropArea: 1,
                    restore: false,
                    guides: true,
                    center: true,
                    highlight: false,
                    cropBoxMovable: true,
                    cropBoxResizable: true,
                    toggleDragModeOnDblclick: false,
                    preview: '.crop-preview',
                });
            },

            /**
             * ซูมเข้า/ออก
             */
            zoom(ratio) {
                if (this.cropper) {
                    this.cropper.zoom(ratio);
                }
            },

            /**
             * หมุนรูป
             */
            rotate(degree) {
                if (this.cropper) {
                    this.cropper.rotate(degree);
                }
            },

            /**
             * รีเซ็ตการครอป
             */
            resetCrop() {
                if (this.cropper) {
                    this.cropper.reset();
                }
            },

            /**
             * ลบรูป
             */
            removeImage() {
                if (this.cropper) {
                    this.cropper.destroy();
                    this.cropper = null;
                }
                this.imagePreview = null;
                this.$refs.imageInput.value = '';
                this.$refs.croppedImageInput.value = '';
            },

            /**
             * เตรียมข้อมูลก่อน submit
             */
            prepareSubmit(event) {
                // ป้องกัน double submit
                if (this.submitting) {
                    event.preventDefault();
                    return;
                }

                if (this.cropper) {
                    // ดึงรูปที่ครอปแล้วเป็น base64
                    const canvas = this.cropper.getCroppedCanvas({
                        width: 1200,
                        height: 400,
                        imageSmoothingEnabled: true,
                        imageSmoothingQuality: 'high',
                    });

                    if (canvas) {
                        // แปลงเป็น base64 และใส่ใน hidden input
                        this.$refs.croppedImageInput.value = canvas.toDataURL('image/jpeg', 0.85);

                        // ⭐ ลบ file input เพื่อไม่ส่งซ้ำกับ cropped_image
                        this.$refs.imageInput.value = '';
                    }
                }

                // แสดง loading state
                this.submitting = true;
            }
        };
    }
</script>
@endpush
@endsection
