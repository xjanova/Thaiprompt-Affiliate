@extends('layouts.admin')

@section('title', 'แก้ไข Banner')

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
                <span class="text-4xl">✏️</span>
                แก้ไข Banner
            </h1>
            <p class="text-gray-500 dark:text-gray-400 mt-2">
                {{ $banner->title }}
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Form --}}
        <form action="{{ route('admin.mobile-app.banners.update', $banner) }}" method="POST" enctype="multipart/form-data"
              x-data="bannerForm('{{ $banner->image }}')" class="lg:col-span-2" @submit="prepareSubmit($event)">
            @csrf
            @method('PUT')

            {{-- Hidden input สำหรับรูปที่ครอปแล้ว --}}
            <input type="hidden" name="cropped_image" x-ref="croppedImageInput">

            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 space-y-6">
                {{-- Title --}}
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ชื่อ Banner <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="title" id="title" required
                           value="{{ old('title', $banner->title) }}"
                           class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600
                                  bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                                  focus:ring-2 focus:ring-orange-500 focus:border-transparent transition"
                           placeholder="เช่น โปรโมชั่นพิเศษ 50%">
                    @error('title')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Current Image --}}
                @if($banner->image)
                <div x-show="!newImagePreview">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        รูปภาพปัจจุบัน
                    </label>
                    <div class="aspect-[3/1] bg-gray-100 dark:bg-gray-700 rounded-xl overflow-hidden max-w-md">
                        <img src="{{ $banner->image }}" alt="{{ $banner->title }}"
                             class="w-full h-full object-cover">
                    </div>
                </div>
                @endif

                {{-- New Image Upload with Cropper --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ $banner->image ? 'เปลี่ยนรูปภาพ' : 'อัพโหลดรูปภาพ' }}
                    </label>

                    {{-- Upload Area --}}
                    <div x-show="!newImagePreview"
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
                                คลิกหรือลากไฟล์มาวางเพื่อเปลี่ยนรูป
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">
                                แนะนำขนาด 1200x400 pixels (อัตราส่วน 3:1)
                            </p>
                        </div>
                    </div>

                    {{-- Cropper Area --}}
                    <div x-show="newImagePreview" x-cloak class="space-y-4">
                        {{-- Image Cropper --}}
                        <div class="bg-gray-100 dark:bg-gray-900 rounded-xl p-4">
                            <img x-ref="cropperImage" :src="newImagePreview" alt="Crop Preview"
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
                            <button type="button" @click="removeNewImage()"
                                    class="px-3 py-2 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 transition">
                                🗑️ ยกเลิกรูปใหม่
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
                           value="{{ old('link_url', $banner->link) }}"
                           class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600
                                  bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                                  focus:ring-2 focus:ring-orange-500 focus:border-transparent transition"
                           placeholder="เช่น /dashboard, /shopping, https://example.com">
                    <div class="mt-2 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                        <p class="text-sm font-medium text-blue-700 dark:text-blue-300 mb-1">📱 Routes ภายในแอพที่ใช้ได้:</p>
                        <p class="text-xs text-blue-600 dark:text-blue-400">
                            <code class="bg-blue-100 dark:bg-blue-900/50 px-1 rounded">/dashboard</code>
                            <code class="bg-blue-100 dark:bg-blue-900/50 px-1 rounded">/shopping</code>
                            <code class="bg-blue-100 dark:bg-blue-900/50 px-1 rounded">/register</code>
                            <code class="bg-blue-100 dark:bg-blue-900/50 px-1 rounded">/wallet</code>
                            <code class="bg-blue-100 dark:bg-blue-900/50 px-1 rounded">/referral</code>
                            <code class="bg-blue-100 dark:bg-blue-900/50 px-1 rounded">/services</code>
                            <code class="bg-blue-100 dark:bg-blue-900/50 px-1 rounded">/academy</code>
                            <code class="bg-blue-100 dark:bg-blue-900/50 px-1 rounded">/support</code>
                            <code class="bg-blue-100 dark:bg-blue-900/50 px-1 rounded">/tpix</code>
                            <code class="bg-blue-100 dark:bg-blue-900/50 px-1 rounded">/stores</code>
                            <code class="bg-blue-100 dark:bg-blue-900/50 px-1 rounded">/cart</code>
                            <code class="bg-blue-100 dark:bg-blue-900/50 px-1 rounded">/orders</code>
                            <code class="bg-blue-100 dark:bg-blue-900/50 px-1 rounded">/commissions</code>
                            <code class="bg-blue-100 dark:bg-blue-900/50 px-1 rounded">/leaderboard</code>
                        </p>
                        <p class="text-xs text-blue-500 dark:text-blue-400 mt-1">
                            💡 หรือใช้ URL ภายนอก เช่น <code class="bg-blue-100 dark:bg-blue-900/50 px-1 rounded">https://line.me/...</code>
                        </p>
                    </div>
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
                        <option value="home" {{ old('position', $banner->position) == 'home' ? 'selected' : '' }}>
                            🏠 หน้าหลัก (Home)
                        </option>
                        <option value="shop" {{ old('position', $banner->position) == 'shop' ? 'selected' : '' }}>
                            🛒 หน้าช้อป (Shop)
                        </option>
                    </select>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        📍 เลือกว่า Banner จะแสดงที่หน้าไหนในแอพ
                    </p>
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
                           value="{{ old('sort_order', $banner->sort_order) }}" min="0"
                           class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600
                                  bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                                  focus:ring-2 focus:ring-orange-500 focus:border-transparent transition">
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
                               value="{{ old('start_date', $banner->start_date?->format('Y-m-d\TH:i')) }}"
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
                               value="{{ old('end_date', $banner->end_date?->format('Y-m-d\TH:i')) }}"
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
                           {{ old('is_active', $banner->is_active) ? 'checked' : '' }}
                           class="w-5 h-5 rounded border-gray-300 text-orange-500 focus:ring-orange-500">
                    <label for="is_active" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        เปิดใช้งาน
                    </label>
                </div>

                {{-- Submit Buttons --}}
                <div class="flex items-center gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button type="submit"
                            :disabled="submitting"
                            :class="{ 'opacity-50 cursor-not-allowed': submitting }"
                            class="px-6 py-3 bg-orange-500 hover:bg-orange-600 text-white font-medium rounded-xl transition flex items-center gap-2">
                        <span x-show="!submitting">💾 บันทึกการแก้ไข</span>
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

        {{-- Stats Sidebar --}}
        <div class="space-y-6">
            {{-- Statistics --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    📊 สถิติ Banner
                </h3>

                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600 dark:text-gray-400">การแสดงผล</span>
                        <span class="text-xl font-bold text-blue-500">
                            {{ number_format($banner->view_count) }}
                        </span>
                    </div>

                    <div class="flex justify-between items-center">
                        <span class="text-gray-600 dark:text-gray-400">การคลิก</span>
                        <span class="text-xl font-bold text-purple-500">
                            {{ number_format($banner->click_count) }}
                        </span>
                    </div>

                    <div class="flex justify-between items-center">
                        <span class="text-gray-600 dark:text-gray-400">CTR</span>
                        <span class="text-xl font-bold text-green-500">
                            {{ $banner->ctr }}%
                        </span>
                    </div>
                </div>
            </div>

            {{-- Info --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    ℹ️ ข้อมูล Banner
                </h3>

                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500 dark:text-gray-400">ID</span>
                        <span class="text-gray-900 dark:text-white font-mono">
                            #{{ $banner->id }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500 dark:text-gray-400">สร้างเมื่อ</span>
                        <span class="text-gray-900 dark:text-white">
                            {{ $banner->created_at->format('d/m/Y H:i') }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500 dark:text-gray-400">อัพเดทล่าสุด</span>
                        <span class="text-gray-900 dark:text-white">
                            {{ $banner->updated_at->format('d/m/Y H:i') }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500 dark:text-gray-400">สถานะ</span>
                        <span class="inline-flex px-2 py-1 rounded-full text-xs font-medium
                            {{ $banner->is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                                  : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400' }}">
                            {{ $banner->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Delete --}}
            <div class="bg-red-50 dark:bg-red-900/20 rounded-2xl border border-red-200 dark:border-red-800 p-6">
                <h3 class="text-lg font-semibold text-red-700 dark:text-red-400 mb-2">
                    ⚠️ Danger Zone
                </h3>
                <p class="text-sm text-red-600 dark:text-red-400 mb-4">
                    การลบ Banner จะไม่สามารถกู้คืนได้
                </p>
                <form action="{{ route('admin.mobile-app.banners.destroy', $banner) }}"
                      method="POST"
                      onsubmit="return confirm('ต้องการลบ Banner นี้หรือไม่?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="w-full px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-xl transition">
                        🗑️ ลบ Banner
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Cropper.js --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>

@push('scripts')
<script>
    /**
     * Banner Form Component พร้อม Image Cropper (Edit Mode)
     *
     * ฟีเจอร์:
     * - แสดงรูปปัจจุบัน
     * - อัพโหลดรูปใหม่ (คลิกหรือลาก)
     * - ครอป/ปรับขนาดรูป
     * - ซูมเข้า/ออก
     * - หมุนรูป
     */
    function bannerForm(existingImage) {
        return {
            dragging: false,
            existingImage: existingImage,
            newImagePreview: null,
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
                    this.newImagePreview = e.target.result;

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
             * ลบรูปใหม่
             */
            removeNewImage() {
                if (this.cropper) {
                    this.cropper.destroy();
                    this.cropper = null;
                }
                this.newImagePreview = null;
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
