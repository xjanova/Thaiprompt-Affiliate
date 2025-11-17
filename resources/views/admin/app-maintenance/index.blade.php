@extends('layouts.admin-v3')

@section('title', 'การปรับปรุงระบบ')

@section('content')
<div x-data="{
    previewImage(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                document.getElementById('imagePreview').src = e.target.result;
                document.getElementById('imagePreviewContainer').classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        }
    }
}" class="container-fluid px-4 py-6">

    <!-- Animated Header -->
    <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-amber-500 via-orange-600 to-red-600 dark:from-amber-600 dark:via-orange-700 dark:to-red-700 p-8 shadow-2xl">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS1vcGFjaXR5PSIwLjEiIHN0cm9rZS13aWR0aD0iMSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNncmlkKSIvPjwvc3ZnPg==')] opacity-30"></div>
        <div class="relative flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-14 h-14 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-white mb-1">การปรับปรุงระบบ</h1>
                    <p class="text-amber-100 dark:text-orange-200">จัดการโหมดปรับปรุงระบบของแอพพลิเคชั่น</p>
                </div>
            </div>

            <!-- Status Badge -->
            @if($maintenance->is_maintenance_mode)
                <div class="px-6 py-3 bg-red-500/90 backdrop-blur-md text-white rounded-xl font-bold border border-white/30 flex items-center gap-2">
                    <span class="w-3 h-3 bg-white rounded-full animate-pulse"></span>
                    กำลังปรับปรุง
                </div>
            @else
                <div class="px-6 py-3 bg-green-500/90 backdrop-blur-md text-white rounded-xl font-bold border border-white/30 flex items-center gap-2">
                    <i class="fas fa-check-circle"></i>
                    ใช้งานปกติ
                </div>
            @endif
        </div>
    </div>

    <!-- Success Message -->
    @if(session('success'))
        <div class="mb-6 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-l-4 border-green-500 p-4 rounded-xl shadow-md">
            <div class="flex items-center">
                <svg class="h-6 w-6 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-green-800 dark:text-green-300 font-semibold">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    <!-- Form Card -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
        <form method="POST" action="{{ route('admin.app-maintenance.update') }}" enctype="multipart/form-data" class="p-8">
            @csrf
            @method('PUT')

            <div class="space-y-6">
                <!-- Maintenance Mode Toggle -->
                <div class="bg-gradient-to-r from-red-50 to-orange-50 dark:from-red-900/20 dark:to-orange-900/20 rounded-xl p-6 border border-red-200 dark:border-red-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2 flex items-center">
                                <i class="fas fa-power-off text-red-600 dark:text-red-400 mr-2"></i>
                                โหมดปรับปรุงระบบ
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">เปิดหรือปิดโหมดปรับปรุงระบบสำหรับแอพพลิเคชั่น</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="is_maintenance_mode" value="1" {{ old('is_maintenance_mode', $maintenance->is_maintenance_mode) ? 'checked' : '' }} class="sr-only peer">
                            <div class="w-20 h-10 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-red-300 dark:peer-focus:ring-red-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-1 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-8 after:w-8 after:transition-all dark:border-gray-500 peer-checked:bg-gradient-to-r peer-checked:from-red-500 peer-checked:to-orange-600"></div>
                        </label>
                    </div>
                </div>

                <!-- Titles -->
                <div class="bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 rounded-xl p-6 border border-blue-200 dark:border-blue-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                        <i class="fas fa-heading text-blue-600 dark:text-blue-400 mr-2"></i>
                        หัวข้อและข้อความ
                    </h3>

                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-flag text-blue-500 mr-1"></i>หัวข้อ (ไทย) *
                            </label>
                            <input type="text" name="title" required value="{{ old('title', $maintenance->title) }}"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-blue-500 @error('title') border-red-500 @enderror">
                            @error('title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-flag text-indigo-500 mr-1"></i>หัวข้อ (English) *
                            </label>
                            <input type="text" name="title_en" required value="{{ old('title_en', $maintenance->title_en) }}"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-blue-500 @error('title_en') border-red-500 @enderror">
                            @error('title_en')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="grid md:grid-cols-2 gap-6 mt-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-align-left text-gray-500 mr-1"></i>ข้อความ (ไทย)
                            </label>
                            <textarea name="message" rows="4"
                                      class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-blue-500">{{ old('message', $maintenance->message) }}</textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-align-left text-gray-500 mr-1"></i>ข้อความ (English)
                            </label>
                            <textarea name="message_en" rows="4"
                                      class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-blue-500">{{ old('message_en', $maintenance->message_en) }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Image -->
                <div class="bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl p-6 border border-purple-200 dark:border-purple-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                        <i class="fas fa-image text-purple-600 dark:text-purple-400 mr-2"></i>
                        รูปภาพประกอบ
                    </h3>

                    @if($maintenance->image_url)
                        <div class="mb-4">
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">รูปภาพปัจจุบัน:</p>
                            <img src="{{ asset($maintenance->image_url) }}" alt="Maintenance" class="h-32 rounded-lg border border-gray-300 dark:border-slate-600">
                        </div>
                    @endif

                    <input type="file" name="image_url" accept="image/*" @change="previewImage"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-purple-500">
                    <p class="text-xs text-gray-500 mt-2">รองรับไฟล์ PNG, JPG, WebP (สูงสุด 5MB)</p>

                    <div id="imagePreviewContainer" class="mt-4 hidden">
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ตัวอย่างรูปภาพใหม่:</p>
                        <img id="imagePreview" class="h-32 rounded-lg border border-purple-300 dark:border-purple-600">
                    </div>
                </div>

                <!-- Schedule -->
                <div class="bg-gradient-to-r from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 rounded-xl p-6 border border-emerald-200 dark:border-emerald-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                        <i class="fas fa-calendar-alt text-emerald-600 dark:text-emerald-400 mr-2"></i>
                        กำหนดเวลา
                    </h3>

                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-clock text-green-500 mr-1"></i>เวลาเริ่มต้น
                            </label>
                            <input type="datetime-local" name="scheduled_start" value="{{ old('scheduled_start', $maintenance->scheduled_start ? \Carbon\Carbon::parse($maintenance->scheduled_start)->format('Y-m-d\TH:i') : '') }}"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-emerald-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-clock text-red-500 mr-1"></i>เวลาสิ้นสุด
                            </label>
                            <input type="datetime-local" name="scheduled_end" value="{{ old('scheduled_end', $maintenance->scheduled_end ? \Carbon\Carbon::parse($maintenance->scheduled_end)->format('Y-m-d\TH:i') : '') }}"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-emerald-500">
                        </div>
                    </div>

                    <div class="mt-6 flex items-center">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="show_countdown" value="1" {{ old('show_countdown', $maintenance->show_countdown) ? 'checked' : '' }} class="sr-only peer">
                            <div class="relative w-11 h-6 bg-gray-300 peer-focus:ring-4 peer-focus:ring-emerald-300 dark:peer-focus:ring-emerald-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                            <span class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-300">แสดงการนับถอยหลัง</span>
                        </label>
                    </div>
                </div>

                <!-- Advanced Settings -->
                <div class="bg-gradient-to-r from-orange-50 to-amber-50 dark:from-orange-900/20 dark:to-amber-900/20 rounded-xl p-6 border border-orange-200 dark:border-orange-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                        <i class="fas fa-cog text-orange-600 dark:text-orange-400 mr-2"></i>
                        การตั้งค่าขั้นสูง
                    </h3>

                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-mobile-alt text-blue-500 mr-1"></i>แพลตฟอร์ม
                            </label>
                            <select name="platform" class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-orange-500">
                                <option value="all" {{ old('platform', $maintenance->platform) === 'all' ? 'selected' : '' }}>ทั้งหมด</option>
                                <option value="android" {{ old('platform', $maintenance->platform) === 'android' ? 'selected' : '' }}>Android</option>
                                <option value="ios" {{ old('platform', $maintenance->platform) === 'ios' ? 'selected' : '' }}>iOS</option>
                                <option value="web" {{ old('platform', $maintenance->platform) === 'web' ? 'selected' : '' }}>Web</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-key text-purple-500 mr-1"></i>Bypass Key
                            </label>
                            <input type="text" name="bypass_key" value="{{ old('bypass_key', $maintenance->bypass_key) }}"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-orange-500"
                                   placeholder="รหัสลับสำหรับเข้าถึงระบบ">
                        </div>
                    </div>

                    <div class="mt-6">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="allow_admin_access" value="1" {{ old('allow_admin_access', $maintenance->allow_admin_access) ? 'checked' : '' }} class="sr-only peer">
                            <div class="relative w-11 h-6 bg-gray-300 peer-focus:ring-4 peer-focus:ring-orange-300 dark:peer-focus:ring-orange-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-600"></div>
                            <span class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-300">อนุญาตให้ผู้ดูแลระบบเข้าถึง</span>
                        </label>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end gap-3 pt-6 border-t border-gray-200 dark:border-slate-700">
                    <button type="submit"
                            class="px-8 py-3 bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-700 hover:to-orange-700 text-white rounded-xl transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 font-bold">
                        <i class="fas fa-save mr-2"></i>บันทึกการตั้งค่า
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
