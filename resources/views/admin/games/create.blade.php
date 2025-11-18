@extends('layouts.admin-v3')

@section('title', 'เพิ่มเกมใหม่')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <a href="{{ route('admin.games.index') }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">
            ← กลับไปรายการเกม
        </a>
    </div>

    <div class="max-w-4xl mx-auto">
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">🎮 เพิ่มเกมใหม่</h2>

            <form action="{{ route('admin.games.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Title (Thai) -->
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            ชื่อเกม (ไทย) <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="title" name="title" value="{{ old('title') }}" required
                               class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('title') border-red-500 @enderror">
                        @error('title')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Title (English) -->
                    <div>
                        <label for="title_en" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            ชื่อเกม (English)
                        </label>
                        <input type="text" id="title_en" name="title_en" value="{{ old('title_en') }}"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <!-- Description (Thai) -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        คำอธิบาย (ไทย)
                    </label>
                    <textarea id="description" name="description" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('description') }}</textarea>
                </div>

                <!-- Description (English) -->
                <div>
                    <label for="description_en" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        คำอธิบาย (English)
                    </label>
                    <textarea id="description_en" name="description_en" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('description_en') }}</textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Icon -->
                    <div>
                        <label for="icon" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            ไอคอน (Emoji) <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="icon" name="icon" value="{{ old('icon', '🎮') }}" required maxlength="10"
                               placeholder="🎮 🚀 ⚡ 🧭"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 text-4xl @error('icon') border-red-500 @enderror">
                        <p class="mt-1 text-sm text-gray-500">ใส่ Emoji ที่ต้องการ</p>
                        @error('icon')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Image -->
                    <div>
                        <label for="image" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            รูปภาพเกม
                        </label>
                        <input type="file" id="image" name="image" accept="image/*"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <p class="mt-1 text-sm text-gray-500">รองรับ: JPG, PNG, GIF, WebP (สูงสุด 2MB)</p>
                    </div>
                </div>

                <!-- URL -->
                <div>
                    <label for="url" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        URL เกม <span class="text-red-500">*</span>
                    </label>
                    <input type="url" id="url" name="url" value="{{ old('url') }}" required
                           placeholder="https://example.com/game"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('url') border-red-500 @enderror">
                    @error('url')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Colors -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Primary Color -->
                    <div>
                        <label for="primary_color" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            สีหลัก <span class="text-red-500">*</span>
                        </label>
                        <div class="flex gap-2">
                            <input type="color" id="primary_color" name="primary_color" value="{{ old('primary_color', '#00ffff') }}" required
                                   class="h-10 w-20 border border-gray-300 dark:border-slate-600 rounded-md">
                            <input type="text" id="primary_color_text" value="{{ old('primary_color', '#00ffff') }}"
                                   class="flex-1 px-3 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-md">
                        </div>
                    </div>

                    <!-- Secondary Color -->
                    <div>
                        <label for="secondary_color" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            สีรอง <span class="text-red-500">*</span>
                        </label>
                        <div class="flex gap-2">
                            <input type="color" id="secondary_color" name="secondary_color" value="{{ old('secondary_color', '#0080ff') }}" required
                                   class="h-10 w-20 border border-gray-300 dark:border-slate-600 rounded-md">
                            <input type="text" id="secondary_color_text" value="{{ old('secondary_color', '#0080ff') }}"
                                   class="flex-1 px-3 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-md">
                        </div>
                    </div>

                    <!-- Glow Color -->
                    <div>
                        <label for="glow_color" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            สี Glow <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="glow_color" name="glow_color" value="{{ old('glow_color', 'rgba(0, 255, 255, 0.8)') }}" required
                               placeholder="rgba(0, 255, 255, 0.8)"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-md">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Card Style -->
                    <div>
                        <label for="card_style" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            รูปแบบการ์ด <span class="text-red-500">*</span>
                        </label>
                        <select id="card_style" name="card_style" required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="default" {{ old('card_style') == 'default' ? 'selected' : '' }}>Default</option>
                            <option value="gradient" {{ old('card_style') == 'gradient' ? 'selected' : '' }}>Gradient</option>
                            <option value="glass" {{ old('card_style') == 'glass' ? 'selected' : '' }}>Glass</option>
                            <option value="neon" {{ old('card_style') == 'neon' ? 'selected' : '' }}>Neon</option>
                        </select>
                    </div>

                    <!-- Order -->
                    <div>
                        <label for="order" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            ลำดับการแสดงผล
                        </label>
                        <input type="number" id="order" name="order" value="{{ old('order', 0) }}" min="0"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <!-- Active Status -->
                <div class="flex items-center">
                    <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                           class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                    <label for="is_active" class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                        เปิดใช้งานเกมนี้
                    </label>
                </div>

                <!-- Submit Buttons -->
                <div class="flex gap-4 pt-6 border-t border-gray-200 dark:border-slate-700">
                    <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-lg font-semibold shadow-lg transition">
                        ✓ บันทึกเกม
                    </button>
                    <a href="{{ route('admin.games.index') }}" class="flex-1 bg-gray-300 hover:bg-gray-400 dark:bg-slate-600 dark:hover:bg-slate-500 text-gray-800 dark:text-white px-6 py-3 rounded-lg font-semibold shadow-lg transition text-center">
                        ✗ ยกเลิก
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Sync color picker with text input
document.getElementById('primary_color').addEventListener('input', function(e) {
    document.getElementById('primary_color_text').value = e.target.value;
});
document.getElementById('primary_color_text').addEventListener('input', function(e) {
    document.getElementById('primary_color').value = e.target.value;
});

document.getElementById('secondary_color').addEventListener('input', function(e) {
    document.getElementById('secondary_color_text').value = e.target.value;
});
document.getElementById('secondary_color_text').addEventListener('input', function(e) {
    document.getElementById('secondary_color').value = e.target.value;
});
</script>
@endsection
