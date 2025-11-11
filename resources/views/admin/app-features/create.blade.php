@extends('layouts.admin')

@section('title', 'เพิ่มฟีเจอร์ใหม่')

@section('content')
<div x-data="{
    config: '',
    validateJson() {
        if (!this.config) return true;
        try {
            JSON.parse(this.config);
            return true;
        } catch(e) {
            return false;
        }
    }
}" class="container-fluid px-4 py-6">

    <!-- Animated Header -->
    <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-purple-500 via-pink-600 to-rose-600 dark:from-purple-600 dark:via-pink-700 dark:to-rose-700 p-8 shadow-2xl">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS1vcGFjaXR5PSIwLjEiIHN0cm9rZS13aWR0aD0iMSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNncmlkKSIvPjwvc3ZnPg==')] opacity-30"></div>
        <div class="relative flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-14 h-14 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-white mb-1">เพิ่มฟีเจอร์ใหม่</h1>
                    <p class="text-purple-100 dark:text-pink-200">สร้างฟีเจอร์ใหม่สำหรับแอพพลิเคชั่น</p>
                </div>
            </div>
            <a href="{{ route('admin.app-features.index') }}" class="px-6 py-3 bg-white/20 backdrop-blur-md hover:bg-white/30 text-white rounded-xl transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 font-bold border border-white/30">
                <i class="fas fa-arrow-left mr-2"></i>กลับ
            </a>
        </div>
    </div>

    <!-- Form Card -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
        <form method="POST" action="{{ route('admin.app-features.store') }}" class="p-8">
            @csrf

            <div class="space-y-6">
                <!-- Basic Information Section -->
                <div class="bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl p-6 border border-purple-200 dark:border-purple-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                        <i class="fas fa-info-circle text-purple-600 dark:text-purple-400 mr-2"></i>
                        ข้อมูลพื้นฐาน
                    </h3>

                    <div class="grid md:grid-cols-2 gap-6">
                        <!-- Feature Key -->
                        <div>
                            <label for="feature_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-key text-purple-500 mr-1"></i>Feature Key *
                            </label>
                            <input type="text" name="feature_key" id="feature_key" required
                                   value="{{ old('feature_key') }}"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 @error('feature_key') border-red-500 @enderror"
                                   placeholder="e.g., feature_notifications">
                            @error('feature_key')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Feature Name -->
                        <div>
                            <label for="feature_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-tag text-pink-500 mr-1"></i>ชื่อฟีเจอร์ *
                            </label>
                            <input type="text" name="feature_name" id="feature_name" required
                                   value="{{ old('feature_name') }}"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 @error('feature_name') border-red-500 @enderror"
                                   placeholder="ระบบแจ้งเตือน">
                            @error('feature_name')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mt-6">
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <i class="fas fa-align-left text-blue-500 mr-1"></i>คำอธิบาย
                        </label>
                        <textarea name="description" id="description" rows="3"
                                  class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200"
                                  placeholder="อธิบายรายละเอียดฟีเจอร์...">{{ old('description') }}</textarea>
                    </div>
                </div>

                <!-- Category & Settings Section -->
                <div class="bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 rounded-xl p-6 border border-blue-200 dark:border-blue-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                        <i class="fas fa-cog text-blue-600 dark:text-blue-400 mr-2"></i>
                        หมวดหมู่และการตั้งค่า
                    </h3>

                    <div class="grid md:grid-cols-3 gap-6">
                        <!-- Category -->
                        <div>
                            <label for="category" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-folder text-yellow-500 mr-1"></i>หมวดหมู่ *
                            </label>
                            <input type="text" name="category" id="category" required
                                   value="{{ old('category', 'general') }}"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 @error('category') border-red-500 @enderror"
                                   placeholder="general, ui, backend">
                            @error('category')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Sort Order -->
                        <div>
                            <label for="sort_order" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-sort-numeric-down text-indigo-500 mr-1"></i>ลำดับการจัดเรียง
                            </label>
                            <input type="number" name="sort_order" id="sort_order" min="0"
                                   value="{{ old('sort_order', 0) }}"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                        </div>

                        <!-- Platform -->
                        <div>
                            <label for="platform" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-mobile-alt text-green-500 mr-1"></i>แพลตฟอร์ม
                            </label>
                            <select name="platform" id="platform"
                                    class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                                <option value="all" {{ old('platform', 'all') === 'all' ? 'selected' : '' }}>ทั้งหมด</option>
                                <option value="android" {{ old('platform') === 'android' ? 'selected' : '' }}>Android</option>
                                <option value="ios" {{ old('platform') === 'ios' ? 'selected' : '' }}>iOS</option>
                            </select>
                        </div>
                    </div>

                    <!-- Min App Version -->
                    <div class="mt-6">
                        <label for="min_app_version" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <i class="fas fa-code-branch text-cyan-500 mr-1"></i>เวอร์ชั่นแอพขั้นต่ำ
                        </label>
                        <input type="text" name="min_app_version" id="min_app_version"
                               value="{{ old('min_app_version') }}"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                               placeholder="e.g., 1.0.0">
                    </div>
                </div>

                <!-- Enable Toggle Section -->
                <div class="bg-gradient-to-r from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 rounded-xl p-6 border border-emerald-200 dark:border-emerald-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2 flex items-center">
                                <i class="fas fa-toggle-on text-emerald-600 dark:text-emerald-400 mr-2"></i>
                                สถานะการใช้งาน
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">เปิดหรือปิดใช้งานฟีเจอร์นี้</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="is_enabled" value="1" {{ old('is_enabled', true) ? 'checked' : '' }} class="sr-only peer">
                            <div class="w-16 h-8 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-300 dark:peer-focus:ring-emerald-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-1 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all dark:border-gray-500 peer-checked:bg-gradient-to-r peer-checked:from-emerald-500 peer-checked:to-teal-600"></div>
                        </label>
                    </div>
                </div>

                <!-- Config JSON Section -->
                <div class="bg-gradient-to-r from-orange-50 to-amber-50 dark:from-orange-900/20 dark:to-amber-900/20 rounded-xl p-6 border border-orange-200 dark:border-orange-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                        <i class="fas fa-code text-orange-600 dark:text-orange-400 mr-2"></i>
                        Config (JSON)
                    </h3>
                    <textarea name="config" id="config" rows="6" x-model="config"
                              class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all duration-200 font-mono text-sm"
                              placeholder='{"key": "value", "enabled": true}'>{{ old('config') }}</textarea>
                    <div x-show="!validateJson() && config" class="mt-2 p-3 bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-700 rounded-lg">
                        <p class="text-sm text-red-800 dark:text-red-300">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            JSON ไม่ถูกต้อง กรุณาตรวจสอบรูปแบบ
                        </p>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end gap-3 pt-6 border-t border-gray-200 dark:border-slate-700">
                    <a href="{{ route('admin.app-features.index') }}"
                       class="px-6 py-3 bg-gray-200 dark:bg-slate-700 hover:bg-gray-300 dark:hover:bg-slate-600 text-gray-800 dark:text-gray-200 rounded-xl transition-all duration-200 font-medium">
                        <i class="fas fa-times mr-2"></i>ยกเลิก
                    </a>
                    <button type="submit"
                            class="px-8 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-xl transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 font-bold">
                        <i class="fas fa-save mr-2"></i>บันทึกฟีเจอร์
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
