@extends('layouts.admin')

@section('title', 'สร้างโฆษณา POS')

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="{
    adType: 'image',
    previewImage: null,
    previewVideo: null
}">
    <!-- Header -->
    <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 rounded-xl shadow-xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold mb-2 flex items-center">
                    <i class="fas fa-plus-circle mr-3"></i>
                    สร้างโฆษณาใหม่
                </h1>
                <p class="text-purple-100">กรอกข้อมูลเพื่อสร้างโฆษณาสำหรับ POS</p>
            </div>
            <a href="{{ route('admin.pos.advertisements.index') }}"
               class="bg-white text-indigo-600 px-4 py-2 rounded-lg hover:bg-indigo-50 transition-all">
                <i class="fas fa-arrow-left mr-2"></i>
                กลับ
            </a>
        </div>
    </div>

    <form action="{{ route('admin.pos.advertisements.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <!-- Basic Information -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                <i class="fas fa-info-circle text-indigo-600 mr-2"></i>
                ข้อมูลพื้นฐาน
            </h2>

            <div class="space-y-4">
                <!-- Title -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ชื่อโฆษณา <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="title"
                           value="{{ old('title') }}"
                           required
                           class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                           placeholder="เช่น: โปรโมชั่นพิเศษ ลด 50%">
                    @error('title')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        คำอธิบาย
                    </label>
                    <textarea name="description"
                              rows="3"
                              class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                              placeholder="อธิบายรายละเอียดโฆษณา...">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Store & Type -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <i class="fas fa-store mr-1"></i> ร้านค้า
                        </label>
                        <select name="store_id"
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                            <option value="">ทั้งหมด</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}" {{ old('store_id') == $store->id ? 'selected' : '' }}>
                                    {{ $store->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <i class="fas fa-layer-group mr-1"></i> ประเภทโฆษณา <span class="text-red-500">*</span>
                        </label>
                        <select name="type"
                                x-model="adType"
                                required
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                            <option value="image">รูปภาพ</option>
                            <option value="video">วิดีโอ</option>
                            <option value="html">HTML</option>
                            <option value="promotion">โปรโมชั่น</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Media Upload -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                <i class="fas fa-photo-video text-purple-600 mr-2"></i>
                สื่อโฆษณา
            </h2>

            <!-- Image Upload -->
            <div x-show="adType === 'image'" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-image mr-1"></i> อัปโหลดรูปภาพ
                    </label>
                    <input type="file"
                           name="image"
                           accept="image/*"
                           @change="previewImage = URL.createObjectURL($event.target.files[0])"
                           class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">รองรับไฟล์ JPG, PNG (สูงสุด 5MB)</p>
                </div>
                <div x-show="previewImage" class="mt-4">
                    <img :src="previewImage" class="max-w-xs rounded-lg shadow-md">
                </div>
            </div>

            <!-- Video Upload -->
            <div x-show="adType === 'video'" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-video mr-1"></i> อัปโหลดวิดีโอ
                    </label>
                    <input type="file"
                           name="video"
                           accept="video/*"
                           @change="previewVideo = URL.createObjectURL($event.target.files[0])"
                           class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">รองรับไฟล์ MP4, WebM (สูงสุด 50MB)</p>
                </div>
                <div x-show="previewVideo" class="mt-4">
                    <video :src="previewVideo" controls class="max-w-xs rounded-lg shadow-md"></video>
                </div>
            </div>

            <!-- HTML Content -->
            <div x-show="adType === 'html'" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-code mr-1"></i> HTML Code
                    </label>
                    <textarea name="html_content"
                              rows="10"
                              class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 font-mono text-sm"
                              placeholder="<div>Your HTML here...</div>">{{ old('html_content') }}</textarea>
                </div>
            </div>

            <!-- Promotion -->
            <div x-show="adType === 'promotion'" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            โค้ดโปรโมชั่น
                        </label>
                        <input type="text"
                               name="promotion_code"
                               value="{{ old('promotion_code') }}"
                               class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                               placeholder="SAVE50">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            ส่วนลดเป็นจำนวน (บาท)
                        </label>
                        <input type="number"
                               name="discount_amount"
                               value="{{ old('discount_amount') }}"
                               step="0.01"
                               class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            ส่วนลดเป็นเปอร์เซ็นต์ (%)
                        </label>
                        <input type="number"
                               name="discount_percentage"
                               value="{{ old('discount_percentage') }}"
                               step="0.01"
                               max="100"
                               class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            เริ่มโปรโมชั่น
                        </label>
                        <input type="datetime-local"
                               name="promotion_start"
                               value="{{ old('promotion_start') }}"
                               class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            สิ้นสุดโปรโมชั่น
                        </label>
                        <input type="datetime-local"
                               name="promotion_end"
                               value="{{ old('promotion_end') }}"
                               class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
            </div>
        </div>

        <!-- Display Settings -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                <i class="fas fa-cog text-green-600 mr-2"></i>
                การตั้งค่าการแสดงผล
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ระยะเวลาแสดง (วินาที) <span class="text-red-500">*</span>
                    </label>
                    <input type="number"
                           name="duration_seconds"
                           value="{{ old('duration_seconds', 10) }}"
                           min="1"
                           max="300"
                           required
                           class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ลำดับการแสดง
                    </label>
                    <input type="number"
                           name="order"
                           value="{{ old('order', 0) }}"
                           min="0"
                           class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        URL ลิงก์
                    </label>
                    <input type="url"
                           name="link_url"
                           value="{{ old('link_url') }}"
                           class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                           placeholder="https://example.com">
                </div>
            </div>

            <!-- Checkboxes -->
            <div class="mt-6 space-y-3">
                <label class="flex items-center">
                    <input type="checkbox"
                           name="is_active"
                           value="1"
                           {{ old('is_active', true) ? 'checked' : '' }}
                           class="w-5 h-5 text-indigo-600 rounded focus:ring-indigo-500">
                    <span class="ml-3 text-gray-700 dark:text-gray-300">เปิดใช้งานทันที</span>
                </label>

                <label class="flex items-center">
                    <input type="checkbox"
                           name="show_on_idle_only"
                           value="1"
                           {{ old('show_on_idle_only') ? 'checked' : '' }}
                           class="w-5 h-5 text-indigo-600 rounded focus:ring-indigo-500">
                    <span class="ml-3 text-gray-700 dark:text-gray-300">แสดงเฉพาะเมื่อ POS ไม่ได้ใช้งาน</span>
                </label>

                <label class="flex items-center">
                    <input type="checkbox"
                           name="link_opens_new_tab"
                           value="1"
                           {{ old('link_opens_new_tab', true) ? 'checked' : '' }}
                           class="w-5 h-5 text-indigo-600 rounded focus:ring-indigo-500">
                    <span class="ml-3 text-gray-700 dark:text-gray-300">เปิดลิงก์ในแท็บใหม่</span>
                </label>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <a href="{{ route('admin.pos.advertisements.index') }}"
                   class="text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
                    <i class="fas fa-times mr-2"></i>
                    ยกเลิก
                </a>
                <button type="submit"
                        class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-8 py-3 rounded-lg font-semibold hover:from-indigo-700 hover:to-purple-700 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-105">
                    <i class="fas fa-save mr-2"></i>
                    บันทึกโฆษณา
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        gsap.from('.space-y-6 > div', {
            opacity: 0,
            y: 20,
            duration: 0.5,
            stagger: 0.1,
            ease: 'power2.out'
        });
    });
</script>
@endpush
@endsection
