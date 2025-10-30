@extends('layouts.admin')

@section('title', 'เพิ่มสไลด์')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">เพิ่มสไลด์ใหม่</h2>
            <p class="text-gray-600 mt-1">อัพโหลดรูปภาพหรือวีดีโอและตั้งค่าสไลด์</p>
        </div>
        <a href="{{ route('admin.sliders.index') }}" class="px-6 py-3 bg-gray-200 text-gray-700 font-semibold rounded-lg hover:bg-gray-300 transition">
            ← กลับ
        </a>
    </div>

    <!-- Form -->
    <div class="bg-white rounded-lg shadow-md p-6" x-data="{
        mediaType: 'image',
        videoType: 'youtube',
        showOverlay: false
    }">
        <form method="POST" action="{{ route('admin.sliders.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="space-y-6">
                <!-- Media Type Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">ประเภทสื่อ <span class="text-red-500">*</span></label>
                    <div class="flex gap-4">
                        <label class="flex items-center px-4 py-3 bg-gray-50 border-2 rounded-lg cursor-pointer transition"
                               :class="mediaType === 'image' ? 'border-indigo-600 bg-indigo-50' : 'border-gray-300'">
                            <input type="radio" name="media_type" value="image" x-model="mediaType" class="hidden">
                            <span class="text-2xl mr-2">🖼️</span>
                            <span class="font-medium">รูปภาพ</span>
                        </label>
                        <label class="flex items-center px-4 py-3 bg-gray-50 border-2 rounded-lg cursor-pointer transition"
                               :class="mediaType === 'video' ? 'border-indigo-600 bg-indigo-50' : 'border-gray-300'">
                            <input type="radio" name="media_type" value="video" x-model="mediaType" class="hidden">
                            <span class="text-2xl mr-2">🎥</span>
                            <span class="font-medium">วีดีโอ</span>
                        </label>
                    </div>
                </div>

                <!-- Image Upload Section -->
                <div x-show="mediaType === 'image'" x-transition>
                    <label class="block text-sm font-medium text-gray-700 mb-2">รูปภาพ <span class="text-red-500">*</span></label>
                    <input type="file" name="image" accept="image/*"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <p class="text-xs text-gray-500 mt-1">ขนาดแนะนำ: 1920x600px, สูงสุด 5MB (JPG, PNG, GIF)</p>
                    @error('image')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Video Section -->
                <div x-show="mediaType === 'video'" x-transition>
                    <!-- Video Type Selection -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-3">แหล่งที่มาของวีดีโอ <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <label class="flex items-center justify-center px-3 py-2 bg-gray-50 border-2 rounded-lg cursor-pointer transition text-sm"
                                   :class="videoType === 'youtube' ? 'border-red-600 bg-red-50' : 'border-gray-300'">
                                <input type="radio" name="video_type" value="youtube" x-model="videoType" class="hidden">
                                <span>YouTube</span>
                            </label>
                            <label class="flex items-center justify-center px-3 py-2 bg-gray-50 border-2 rounded-lg cursor-pointer transition text-sm"
                                   :class="videoType === 'vimeo' ? 'border-blue-600 bg-blue-50' : 'border-gray-300'">
                                <input type="radio" name="video_type" value="vimeo" x-model="videoType" class="hidden">
                                <span>Vimeo</span>
                            </label>
                            <label class="flex items-center justify-center px-3 py-2 bg-gray-50 border-2 rounded-lg cursor-pointer transition text-sm"
                                   :class="videoType === 'upload' ? 'border-indigo-600 bg-indigo-50' : 'border-gray-300'">
                                <input type="radio" name="video_type" value="upload" x-model="videoType" class="hidden">
                                <span>อัพโหลด</span>
                            </label>
                            <label class="flex items-center justify-center px-3 py-2 bg-gray-50 border-2 rounded-lg cursor-pointer transition text-sm"
                                   :class="videoType === 'other' ? 'border-purple-600 bg-purple-50' : 'border-gray-300'">
                                <input type="radio" name="video_type" value="other" x-model="videoType" class="hidden">
                                <span>อื่นๆ</span>
                            </label>
                        </div>
                    </div>

                    <!-- Video URL Input -->
                    <div x-show="videoType !== 'upload'" class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">URL วีดีโอ</label>
                        <input type="text" name="video_url" value="{{ old('video_url') }}"
                               placeholder="https://www.youtube.com/watch?v=..."
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1" x-show="videoType === 'youtube'">วาง URL จาก YouTube (เช่น https://www.youtube.com/watch?v=xxxxx)</p>
                        <p class="text-xs text-gray-500 mt-1" x-show="videoType === 'vimeo'">วาง URL จาก Vimeo (เช่น https://vimeo.com/xxxxx)</p>
                        <p class="text-xs text-gray-500 mt-1" x-show="videoType === 'other'">วาง URL วีดีโอที่สามารถเล่นได้โดยตรง</p>
                        @error('video_url')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Video File Upload -->
                    <div x-show="videoType === 'upload'" class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">ไฟล์วีดีโอ</label>
                        <input type="file" name="video_file" accept="video/*"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">รองรับ MP4, WebM, OGG, สูงสุด 50MB</p>
                        @error('video_file')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Video Settings -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="font-medium text-gray-700 mb-3">ตั้งค่าการเล่นวีดีโอ</h3>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="flex items-center">
                                <input type="checkbox" name="video_autoplay" value="1" checked
                                       class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700">เล่นอัตโนมัติ</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="video_muted" value="1" checked
                                       class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700">ปิดเสียง</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="video_loop" value="1"
                                       class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700">เล่นวนซ้ำ</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="video_controls" value="1"
                                       class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700">แสดงปุ่มควบคุม</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Text Overlay Section -->
                <div class="bg-gradient-to-r from-purple-50 to-pink-50 p-4 rounded-lg border border-purple-200">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-medium text-gray-700">✨ ข้อความทับ (Text Overlay)</h3>
                        <button type="button" @click="showOverlay = !showOverlay"
                                class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                            <span x-text="showOverlay ? 'ซ่อน' : 'แสดง'"></span>
                        </button>
                    </div>

                    <div x-show="showOverlay" x-transition>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">ข้อความ</label>
                                <input type="text" name="overlay_text" value="{{ old('overlay_text') }}"
                                       placeholder="ใส่ข้อความที่ต้องการแสดงบนสไลด์..."
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">ตำแหน่ง</label>
                                    <select name="overlay_position" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                                        <option value="bottom-left">ล่างซ้าย</option>
                                        <option value="bottom-center">ล่างกลาง</option>
                                        <option value="bottom-right">ล่างขวา</option>
                                        <option value="center-left">กลางซ้าย</option>
                                        <option value="center">กลาง</option>
                                        <option value="center-right">กลางขวา</option>
                                        <option value="top-left">บนซ้าย</option>
                                        <option value="top-center">บนกลาง</option>
                                        <option value="top-right">บนขวา</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">สไตล์</label>
                                    <select name="overlay_style" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                                        <option value="elegant">สง่างาม</option>
                                        <option value="modern">ทันสมัย</option>
                                        <option value="bold">โดดเด่น</option>
                                        <option value="minimal">เรียบง่าย</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">ขนาดตัวอักษร</label>
                                    <select name="overlay_font_size" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                                        <option value="text-2xl">เล็ก</option>
                                        <option value="text-3xl">กลาง</option>
                                        <option value="text-4xl" selected>ใหญ่</option>
                                        <option value="text-5xl">ใหญ่มาก</option>
                                        <option value="text-6xl">ใหญ่พิเศษ</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">แอนิเมชั่น</label>
                                    <select name="overlay_animation" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                                        <option value="fade-in">ค่อยๆปรากฏ</option>
                                        <option value="slide-in-left">สไลด์จากซ้าย</option>
                                        <option value="slide-in-right">สไลด์จากขวา</option>
                                        <option value="slide-in-up">สไลด์จากล่าง</option>
                                        <option value="zoom-in">ขยายเข้า</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Title -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">หัวข้อ (ไม่บังคับ)</label>
                    <input type="text" name="title" value="{{ old('title') }}" maxlength="255"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    @error('title')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">คำอธิบาย (ไม่บังคับ)</label>
                    <textarea name="description" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Link -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ลิงก์ (ไม่บังคับ)</label>
                    <input type="url" name="link" value="{{ old('link') }}" placeholder="https://example.com"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <p class="text-xs text-gray-500 mt-1">ลิงก์ที่จะไปเมื่อคลิกที่สไลด์</p>
                    @error('link')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Order -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ลำดับ</label>
                    <input type="number" name="order" value="{{ old('order', 0) }}" min="0"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <p class="text-xs text-gray-500 mt-1">ลำดับการแสดงสไลด์ (เลขน้อยแสดงก่อน)</p>
                    @error('order')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Active Status -->
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="ml-2 text-sm text-gray-700">เปิดใช้งานสไลด์นี้</span>
                    </label>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="flex gap-3 mt-8">
                <button type="submit" class="px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition shadow-md">
                    บันทึก
                </button>
                <a href="{{ route('admin.sliders.index') }}" class="px-6 py-3 bg-gray-200 text-gray-700 font-semibold rounded-lg hover:bg-gray-300 transition shadow-md">
                    ยกเลิก
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
