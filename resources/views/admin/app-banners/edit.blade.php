@extends('layouts.admin')

@section('title', 'แก้ไข Emergency Alert Banner')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">แก้ไข Emergency Alert Banner</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">แก้ไขการแจ้งเตือนฉุกเฉิน</p>
        </div>
        <a href="{{ route('admin.app-banners.index') }}"
           class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
            <i class="fas fa-arrow-left mr-2"></i> กลับ
        </a>
    </div>

    <form action="{{ route('admin.app-banners.update', $appBanner) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Basic Information -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-info-circle mr-2"></i> ข้อมูลพื้นฐาน
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Title (Thai) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        หัวข้อ (ไทย) <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="title" value="{{ old('title', $appBanner->title) }}" required
                           class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500"
                           placeholder="เช่น: แจ้งปรับปรุงระบบ">
                    @error('title')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Title (English) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        หัวข้อ (English)
                    </label>
                    <input type="text" name="title_en" value="{{ old('title_en', $appBanner->title_en) }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500"
                           placeholder="e.g., System Maintenance">
                    @error('title_en')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description (Thai) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        รายละเอียด (ไทย)
                    </label>
                    <textarea name="description" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500"
                              placeholder="รายละเอียดเพิ่มเติม...">{{ old('description', $appBanner->description) }}</textarea>
                    @error('description')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description (English) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        รายละเอียด (English)
                    </label>
                    <textarea name="description_en" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500"
                              placeholder="Additional details...">{{ old('description_en', $appBanner->description_en) }}</textarea>
                    @error('description_en')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Icon -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ไอคอน (Emoji or Icon Class)
                    </label>
                    <input type="text" name="icon" value="{{ old('icon', $appBanner->icon ?? '⚠️') }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500"
                           placeholder="⚠️ or fa-exclamation-triangle">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        ใช้ Emoji หรือ Font Awesome class (เช่น: 🔔, 🚨, fa-bell)
                    </p>
                    @error('icon')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Display Settings -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-desktop mr-2"></i> การตั้งค่าการแสดงผล
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ประเภท <span class="text-red-500">*</span>
                    </label>
                    <select name="type" required
                            class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500">
                        <option value="info" {{ old('type', $appBanner->type) === 'info' ? 'selected' : '' }}>Info</option>
                        <option value="warning" {{ old('type', $appBanner->type) === 'warning' ? 'selected' : '' }}>Warning</option>
                        <option value="success" {{ old('type', $appBanner->type) === 'success' ? 'selected' : '' }}>Success</option>
                        <option value="promotion" {{ old('type', $appBanner->type) === 'promotion' ? 'selected' : '' }}>Promotion</option>
                        <option value="announcement" {{ old('type', $appBanner->type) === 'announcement' ? 'selected' : '' }}>Announcement</option>
                    </select>
                </div>

                <!-- Display Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        รูปแบบการแสดง <span class="text-red-500">*</span>
                    </label>
                    <select name="display_type" required
                            class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500">
                        <option value="popup" {{ old('display_type', $appBanner->display_type) === 'popup' ? 'selected' : '' }}>Popup เท่านั้น</option>
                        <option value="banner" {{ old('display_type', $appBanner->display_type) === 'banner' ? 'selected' : '' }}>Banner เท่านั้น</option>
                        <option value="marquee" {{ old('display_type', $appBanner->display_type) === 'marquee' ? 'selected' : '' }}>Marquee (แถบวิ่ง) เท่านั้น</option>
                        <option value="popup_and_banner" {{ old('display_type', $appBanner->display_type) === 'popup_and_banner' ? 'selected' : '' }}>Popup + Banner</option>
                        <option value="popup_and_marquee" {{ old('display_type', $appBanner->display_type) === 'popup_and_marquee' ? 'selected' : '' }}>Popup + Marquee</option>
                        <option value="banner_and_marquee" {{ old('display_type', $appBanner->display_type) === 'banner_and_marquee' ? 'selected' : '' }}>Banner + Marquee</option>
                        <option value="all" {{ old('display_type', $appBanner->display_type) === 'all' ? 'selected' : '' }}>ทั้งหมด (Popup + Banner + Marquee)</option>
                    </select>
                </div>

                <!-- Display Position -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ตำแหน่งการแสดง <span class="text-red-500">*</span>
                    </label>
                    <select name="display_position" required
                            class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500">
                        <option value="top" {{ old('display_position', $appBanner->display_position) === 'top' ? 'selected' : '' }}>บนสุด (Top)</option>
                        <option value="bottom" {{ old('display_position', $appBanner->display_position) === 'bottom' ? 'selected' : '' }}>ล่างสุด (Bottom)</option>
                        <option value="top-right" {{ old('display_position', $appBanner->display_position) === 'top-right' ? 'selected' : '' }}>บนขวา (Top Right)</option>
                        <option value="top-left" {{ old('display_position', $appBanner->display_position) === 'top-left' ? 'selected' : '' }}>บนซ้าย (Top Left)</option>
                        <option value="bottom-right" {{ old('display_position', $appBanner->display_position) === 'bottom-right' ? 'selected' : '' }}>ล่างขวา (Bottom Right)</option>
                        <option value="bottom-left" {{ old('display_position', $appBanner->display_position) === 'bottom-left' ? 'selected' : '' }}>ล่างซ้าย (Bottom Left)</option>
                        <option value="center" {{ old('display_position', $appBanner->display_position) === 'center' ? 'selected' : '' }}>กลางจอ (Center)</option>
                    </select>
                </div>

                <!-- Size -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ขนาด <span class="text-red-500">*</span>
                    </label>
                    <select name="size" required
                            class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500">
                        <option value="small" {{ old('size', $appBanner->size) === 'small' ? 'selected' : '' }}>เล็ก (Small)</option>
                        <option value="medium" {{ old('size', $appBanner->size ?? 'medium') === 'medium' ? 'selected' : '' }}>กลาง (Medium)</option>
                        <option value="large" {{ old('size', $appBanner->size) === 'large' ? 'selected' : '' }}>ใหญ่ (Large)</option>
                        <option value="full" {{ old('size', $appBanner->size) === 'full' ? 'selected' : '' }}>เต็มจอ (Full Screen)</option>
                    </select>
                </div>

                <!-- Position (Page) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        แสดงบนหน้า <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="position" value="{{ old('position', $appBanner->position ?? 'global') }}" required
                           class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500"
                           placeholder="global, home, profile, wallet">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        global = ทุกหน้า, home = หน้าแรก, profile = โปรไฟล์
                    </p>
                </div>
            </div>
        </div>

        <!-- Colors & Styling -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-palette mr-2"></i> สีและสไตล์
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Background Color -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        สีพื้นหลัง
                    </label>
                    <input type="color" name="background_color" value="{{ old('background_color', '#EF4444') }}"
                           class="w-full h-10 border border-gray-300 dark:border-slate-600 rounded-lg cursor-pointer">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        ถ้าไม่ระบุ จะใช้สีตามประเภท (type)
                    </p>
                </div>

                <!-- Text Color -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        สีตัวอักษร
                    </label>
                    <input type="color" name="text_color" value="{{ old('text_color', '#FFFFFF') }}"
                           class="w-full h-10 border border-gray-300 dark:border-slate-600 rounded-lg cursor-pointer">
                </div>

                <!-- Border Color -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        สีขอบ
                    </label>
                    <input type="color" name="border_color" value="{{ old('border_color', '#DC2626') }}"
                           class="w-full h-10 border border-gray-300 dark:border-slate-600 rounded-lg cursor-pointer">
                </div>
            </div>
        </div>

        <!-- Animation & Effects -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-magic mr-2"></i> Animation & Effects
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <!-- Animation Style -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        สไตล์ Animation
                    </label>
                    <select name="animation_style"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg">
                        <option value="fade" {{ old('animation_style', 'fade') === 'fade' ? 'selected' : '' }}>Fade</option>
                        <option value="slide" {{ old('animation_style') === 'slide' ? 'selected' : '' }}>Slide</option>
                        <option value="bounce" {{ old('animation_style') === 'bounce' ? 'selected' : '' }}>Bounce</option>
                        <option value="zoom" {{ old('animation_style') === 'zoom' ? 'selected' : '' }}>Zoom</option>
                        <option value="shake" {{ old('animation_style') === 'shake' ? 'selected' : '' }}>Shake</option>
                    </select>
                </div>

                <!-- Animation Duration -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ความเร็ว Animation (ms)
                    </label>
                    <input type="number" name="animation_duration" value="{{ old('animation_duration', 300) }}" min="100" max="3000"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg">
                </div>

                <!-- Scroll Speed (for Marquee) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ความเร็ววิ่ง (1-100)
                    </label>
                    <input type="number" name="scroll_speed" value="{{ old('scroll_speed', 50) }}" min="1" max="100"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        สำหรับ Marquee
                    </p>
                </div>

                <!-- Scroll Direction -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ทิศทางวิ่ง
                    </label>
                    <select name="scroll_direction"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg">
                        <option value="left" {{ old('scroll_direction', 'left') === 'left' ? 'selected' : '' }}>ซ้าย</option>
                        <option value="right" {{ old('scroll_direction') === 'right' ? 'selected' : '' }}>ขวา</option>
                        <option value="up" {{ old('scroll_direction') === 'up' ? 'selected' : '' }}>ขึ้น</option>
                        <option value="down" {{ old('scroll_direction') === 'down' ? 'selected' : '' }}>ลง</option>
                    </select>
                </div>

                <!-- Auto Dismiss -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ปิดอัตโนมัติ (วินาที)
                    </label>
                    <input type="number" name="auto_dismiss_seconds" value="{{ old('auto_dismiss_seconds') }}" min="1" max="300"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg"
                           placeholder="ไม่ปิดอัตโนมัติ">
                </div>
            </div>
        </div>

        <!-- Sound Settings -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-volume-up mr-2"></i> การตั้งค่าเสียง
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Sound Enabled -->
                <div class="flex items-center">
                    <input type="checkbox" name="sound_enabled" id="sound_enabled" value="1" {{ old('sound_enabled') ? 'checked' : '' }}
                           class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                    <label for="sound_enabled" class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                        เปิดเสียงแจ้งเตือน
                    </label>
                </div>

                <!-- Sound Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ประเภทเสียง
                    </label>
                    <select name="sound_type"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg">
                        <option value="default" {{ old('sound_type', 'default') === 'default' ? 'selected' : '' }}>Default</option>
                        <option value="success" {{ old('sound_type') === 'success' ? 'selected' : '' }}>Success</option>
                        <option value="warning" {{ old('sound_type') === 'warning' ? 'selected' : '' }}>Warning</option>
                        <option value="error" {{ old('sound_type') === 'error' ? 'selected' : '' }}>Error</option>
                        <option value="custom" {{ old('sound_type') === 'custom' ? 'selected' : '' }}>Custom</option>
                    </select>
                </div>

                <!-- Sound URL -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Custom Sound URL
                    </label>
                    <input type="url" name="sound_url" value="{{ old('sound_url') }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg"
                           placeholder="https://example.com/sound.mp3">
                </div>
            </div>
        </div>

        <!-- Images -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-image mr-2"></i> รูปภาพ (สำหรับ Popup/Promotion)
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Image (Light Mode) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        รูปภาพ (Light Mode)
                    </label>
                    <input type="file" name="image_url" accept="image/*"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        แนะนำ: 1200x600px, ไม่เกิน 5MB
                    </p>
                </div>

                <!-- Image (Dark Mode) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        รูปภาพ (Dark Mode)
                    </label>
                    <input type="file" name="image_url_dark" accept="image/*"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        แนะนำ: 1200x600px, ไม่เกิน 5MB
                    </p>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-mouse-pointer mr-2"></i> ปุ่มและการกระทำ
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Action Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ประเภทการกระทำ
                    </label>
                    <select name="action_type"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg">
                        <option value="none" {{ old('action_type', 'none') === 'none' ? 'selected' : '' }}>ไม่มีปุ่ม</option>
                        <option value="url" {{ old('action_type') === 'url' ? 'selected' : '' }}>URL (ลิงก์ภายนอก)</option>
                        <option value="route" {{ old('action_type') === 'route' ? 'selected' : '' }}>Route (ลิงก์ภายใน)</option>
                        <option value="deeplink" {{ old('action_type') === 'deeplink' ? 'selected' : '' }}>Deeplink (แอพมือถือ)</option>
                    </select>
                </div>

                <!-- Action Value -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        URL / Route / Deeplink
                    </label>
                    <input type="text" name="action_value" value="{{ old('action_value') }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg"
                           placeholder="https://example.com หรือ route.name">
                </div>

                <!-- Button Text (Thai) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ข้อความบนปุ่ม (ไทย)
                    </label>
                    <input type="text" name="button_text" value="{{ old('button_text') }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg"
                           placeholder="เช่น: ดูเพิ่มเติม, รับโปรโมชั่น">
                </div>

                <!-- Button Text (English) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ข้อความบนปุ่ม (English)
                    </label>
                    <input type="text" name="button_text_en" value="{{ old('button_text_en') }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg"
                           placeholder="e.g., Learn More, Get Promotion">
                </div>

                <!-- Button Color -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        สีปุ่ม
                    </label>
                    <input type="color" name="button_color" value="{{ old('button_color', '#3B82F6') }}"
                           class="w-full h-10 border border-gray-300 dark:border-slate-600 rounded-lg cursor-pointer">
                </div>

                <!-- Secondary Button Text (Thai) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ปุ่มรอง (ไทย)
                    </label>
                    <input type="text" name="secondary_button_text" value="{{ old('secondary_button_text') }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg"
                           placeholder="เช่น: ยกเลิก, ภายหลัง">
                </div>

                <!-- Secondary Button Text (English) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ปุ่มรอง (English)
                    </label>
                    <input type="text" name="secondary_button_text_en" value="{{ old('secondary_button_text_en') }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg"
                           placeholder="e.g., Cancel, Later">
                </div>

                <!-- Secondary Button Action -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        การกระทำของปุ่มรอง
                    </label>
                    <input type="text" name="secondary_button_action" value="{{ old('secondary_button_action', 'dismiss') }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg"
                           placeholder="dismiss, url, route">
                </div>
            </div>
        </div>

        <!-- Schedule & Target -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-calendar mr-2"></i> ตารางเวลาและกลุ่มเป้าหมาย
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Start Date -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        วันเวลาเริ่มต้น
                    </label>
                    <input type="datetime-local" name="start_date" value="{{ old('start_date') }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg">
                </div>

                <!-- End Date -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        วันเวลาสิ้นสุด
                    </label>
                    <input type="datetime-local" name="end_date" value="{{ old('end_date') }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg">
                </div>

                <!-- Target Audience -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        กลุ่มเป้าหมาย <span class="text-red-500">*</span>
                    </label>
                    <select name="target_audience" required
                            class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg">
                        <option value="all" {{ old('target_audience', 'all') === 'all' ? 'selected' : '' }}>ทุกคน</option>
                        <option value="new_users" {{ old('target_audience') === 'new_users' ? 'selected' : '' }}>สมาชิกใหม่</option>
                        <option value="members" {{ old('target_audience') === 'members' ? 'selected' : '' }}>สมาชิกทั่วไป</option>
                        <option value="vip" {{ old('target_audience') === 'vip' ? 'selected' : '' }}>สมาชิก VIP</option>
                        <option value="affiliates" {{ old('target_audience') === 'affiliates' ? 'selected' : '' }}>Affiliates</option>
                    </select>
                </div>

                <!-- Target User IDs -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        User IDs เฉพาะ (คั่นด้วย comma)
                    </label>
                    <input type="text" name="target_user_ids" value="{{ old('target_user_ids') }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg"
                           placeholder="1, 2, 3, 4, 5">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        ถ้าระบุ จะแสดงเฉพาะ User IDs เหล่านี้เท่านั้น
                    </p>
                </div>

                <!-- Platform -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        แพลตฟอร์ม
                    </label>
                    <select name="platform"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg">
                        <option value="all" {{ old('platform', 'all') === 'all' ? 'selected' : '' }}>ทั้งหมด</option>
                        <option value="android" {{ old('platform') === 'android' ? 'selected' : '' }}>Android</option>
                        <option value="ios" {{ old('platform') === 'ios' ? 'selected' : '' }}>iOS</option>
                    </select>
                </div>

                <!-- Sort Order -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ลำดับการแสดง
                    </label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg">
                </div>

                <!-- Priority -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ความสำคัญ (0-100)
                    </label>
                    <input type="number" name="priority" value="{{ old('priority', 0) }}" min="0" max="100"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        ค่าสูงกว่าจะแสดงก่อน
                    </p>
                </div>
            </div>
        </div>

        <!-- Behavior Settings -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-cog mr-2"></i> พฤติกรรมและตัวเลือกเพิ่มเติม
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Is Active -->
                <div class="flex items-center">
                    <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $appBanner->is_active ?? true) ? 'checked' : '' }}
                           class="w-4 h-4 text-indigo-600 border-gray-300 rounded">
                    <label for="is_active" class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                        เปิดใช้งาน
                    </label>
                </div>

                <!-- Is Dismissible -->
                <div class="flex items-center">
                    <input type="checkbox" name="is_dismissible" id="is_dismissible" value="1" {{ old('is_dismissible', $appBanner->is_dismissible ?? true) ? 'checked' : '' }}
                           class="w-4 h-4 text-indigo-600 border-gray-300 rounded">
                    <label for="is_dismissible" class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                        สามารถปิดได้
                    </label>
                </div>

                <!-- Show Once -->
                <div class="flex items-center">
                    <input type="checkbox" name="show_once" id="show_once" value="1" {{ old('show_once') ? 'checked' : '' }}
                           class="w-4 h-4 text-indigo-600 border-gray-300 rounded">
                    <label for="show_once" class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                        แสดงครั้งเดียวต่อผู้ใช้
                    </label>
                </div>

                <!-- Require Action -->
                <div class="flex items-center">
                    <input type="checkbox" name="require_action" id="require_action" value="1" {{ old('require_action') ? 'checked' : '' }}
                           class="w-4 h-4 text-indigo-600 border-gray-300 rounded">
                    <label for="require_action" class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                        บังคับให้กดปุ่มก่อนปิด
                    </label>
                </div>

                <!-- Fullscreen -->
                <div class="flex items-center">
                    <input type="checkbox" name="fullscreen" id="fullscreen" value="1" {{ old('fullscreen') ? 'checked' : '' }}
                           class="w-4 h-4 text-indigo-600 border-gray-300 rounded">
                    <label for="fullscreen" class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                        เต็มจอ (Fullscreen Overlay)
                    </label>
                </div>
            </div>
        </div>

        <!-- Submit Buttons -->
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('admin.app-banners.index') }}"
               class="px-6 py-3 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                ยกเลิก
            </a>
            <button type="submit"
                    class="px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg hover:from-indigo-700 hover:to-purple-700 shadow-lg transition-all">
                <i class="fas fa-save mr-2"></i> บันทึกการแก้ไข
            </button>
        </div>
    </form>
</div>
@endsection
