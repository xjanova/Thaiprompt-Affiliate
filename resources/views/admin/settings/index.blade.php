@extends('layouts.admin')

@section('title', 'ตั้งค่าระบบ')

@section('content')
<div x-data="{ activeTab: 'general' }" class="space-y-6">
    <!-- Tab Navigation -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md">
        <div class="border-b border-gray-200 dark:border-slate-700">
            <nav class="flex -mb-px">
                <button @click="activeTab = 'general'"
                        :class="{ 'border-indigo-500 text-indigo-600 dark:text-indigo-400': activeTab === 'general', 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-slate-600': activeTab !== 'general' }"
                        class="px-6 py-4 text-sm font-medium border-b-2 transition-colors duration-200">
                    <span class="flex items-center">
                        <span class="text-lg mr-2">⚙️</span>
                        ตั้งค่าทั่วไป
                    </span>
                </button>

                <button @click="activeTab = 'affiliate'"
                        :class="{ 'border-indigo-500 text-indigo-600 dark:text-indigo-400': activeTab === 'affiliate', 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-slate-600': activeTab !== 'affiliate' }"
                        class="px-6 py-4 text-sm font-medium border-b-2 transition-colors duration-200">
                    <span class="flex items-center">
                        <span class="text-lg mr-2">🌐</span>
                        ตั้งค่า Affiliate
                    </span>
                </button>

                <button @click="activeTab = 'branding'"
                        :class="{ 'border-indigo-500 text-indigo-600 dark:text-indigo-400': activeTab === 'branding', 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-slate-600': activeTab !== 'branding' }"
                        class="px-6 py-4 text-sm font-medium border-b-2 transition-colors duration-200">
                    <span class="flex items-center">
                        <span class="text-lg mr-2">🎨</span>
                        โลโก้ & Favicon
                    </span>
                </button>

                <button @click="activeTab = 'theme'"
                        :class="{ 'border-indigo-500 text-indigo-600 dark:text-indigo-400': activeTab === 'theme', 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-slate-600': activeTab !== 'theme' }"
                        class="px-6 py-4 text-sm font-medium border-b-2 transition-colors duration-200">
                    <span class="flex items-center">
                        <span class="text-lg mr-2">🎨</span>
                        สีธีม
                    </span>
                </button>

                <button @click="activeTab = 'api'"
                        :class="{ 'border-indigo-500 text-indigo-600 dark:text-indigo-400': activeTab === 'api', 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-slate-600': activeTab !== 'api' }"
                        class="px-6 py-4 text-sm font-medium border-b-2 transition-colors duration-200">
                    <span class="flex items-center">
                        <span class="text-lg mr-2">🔑</span>
                        การตั้งค่า API
                    </span>
                </button>

                <button @click="activeTab = 'pageloader'"
                        :class="{ 'border-indigo-500 text-indigo-600 dark:text-indigo-400': activeTab === 'pageloader', 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-slate-600': activeTab !== 'pageloader' }"
                        class="px-6 py-4 text-sm font-medium border-b-2 transition-colors duration-200">
                    <span class="flex items-center">
                        <span class="text-lg mr-2">⏳</span>
                        อนิเมชั่นโหลดหน้า
                    </span>
                </button>
            </nav>
        </div>

        <!-- Tab Contents -->
        <div class="p-6">
            <!-- General Settings Tab -->
            <div x-show="activeTab === 'general'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <form method="POST" action="{{ route('admin.settings.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="space-y-6">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ตั้งค่าทั่วไป</h3>

                            <div class="mb-4">
                                <label for="app_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ชื่อแอพพลิเคชั่น</label>
                                <input type="text" name="app_name" id="app_name" value="{{ old('app_name', $settings->get('general')->firstWhere('key', 'app_name')->value ?? 'TP-Affiliate') }}"
                                       class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition">
                                บันทึกการตั้งค่า
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Affiliate Settings Tab -->
            <div x-show="activeTab === 'affiliate'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" style="display: none;">
                <form method="POST" action="{{ route('admin.settings.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="space-y-6">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ตั้งค่า Affiliate</h3>

                            <div class="mb-4">
                                <label for="commission_rate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">อัตราคอมมิชชั่น (%)</label>
                                <input type="number" name="commission_rate" id="commission_rate" min="0" max="100" step="0.01"
                                       value="{{ old('commission_rate', $settings->get('affiliate')->firstWhere('key', 'commission_rate')->value ?? 10) }}"
                                       class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            </div>

                            <div class="mb-4">
                                <label class="flex items-center">
                                    <input type="checkbox" name="multi_level_enabled" value="1"
                                           {{ old('multi_level_enabled', $settings->get('affiliate')->firstWhere('key', 'multi_level_enabled')->value ?? true) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span class="ml-2 text-sm text-gray-700">เปิดใช้งานระบบหลายระดับ</span>
                                </label>
                            </div>

                            <div class="mb-4">
                                <label for="default_sponsor_referral_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">รหัสแนะนำเริ่มต้น (Default Sponsor ID)</label>
                                <input type="text" name="default_sponsor_referral_code" id="default_sponsor_referral_code"
                                       value="{{ old('default_sponsor_referral_code', $settings->get('affiliate')->firstWhere('key', 'default_sponsor_referral_code')->value ?? '') }}"
                                       class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                       placeholder="กรอกรหัสแนะนำเริ่มต้น">
                                <p class="text-xs text-gray-500 mt-1">ผู้สมัครที่ไม่มีรหัสแนะนำจะถูกต่อสายงานอัตโนมัติกับรหัสนี้</p>
                            </div>

                            <div class="mb-4">
                                <label for="commission_depth" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ความลึกคอมมิชชั่น (Commission Depth)</label>
                                <input type="number" name="commission_depth" id="commission_depth" min="1" max="100"
                                       value="{{ old('commission_depth', $settings->get('affiliate')->firstWhere('key', 'commission_depth')->value ?? 10) }}"
                                       class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                <p class="text-xs text-gray-500 mt-1">กำหนดจำนวนชั้นสายงานที่สมาชิกจะมองเห็นและได้รับคอมมิชชั่น (ค่าเริ่มต้น: 10 ชั้น)</p>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition">
                                บันทึกการตั้งค่า
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Branding Settings Tab -->
            <div x-show="activeTab === 'branding'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" style="display: none;">
                <div x-data="{
                    logoPreview: null,
                    faviconPreview: null,
                    isUploading: false,
                    handleLogoUpload(event) {
                        const file = event.target.files[0];
                        if (file && file.type.startsWith('image/')) {
                            const reader = new FileReader();
                            reader.onload = (e) => {
                                this.logoPreview = e.target.result;
                            };
                            reader.readAsDataURL(file);
                        }
                    },
                    handleFaviconUpload(event) {
                        const file = event.target.files[0];
                        if (file && file.type.startsWith('image/')) {
                            const reader = new FileReader();
                            reader.onload = (e) => {
                                this.faviconPreview = e.target.result;
                            };
                            reader.readAsDataURL(file);
                        }
                    },
                    handleSubmit(event) {
                        this.isUploading = true;
                    }
                }">
                    <form method="POST" action="{{ route('admin.settings.branding') }}" enctype="multipart/form-data" @submit="handleSubmit">
                        @csrf

                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ตั้งค่าโลโก้และ Favicon</h3>

                    <div class="grid md:grid-cols-2 gap-6">
                        <!-- Logo Upload -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">โลโก้</label>
                            @php
                                $logo = $settings->get('branding')->firstWhere('key', 'logo')->value ?? '';
                            @endphp
                            @if($logo)
                                <div class="mb-3">
                                    <p class="text-xs text-gray-600 mb-1">โลโก้ปัจจุบัน:</p>
                                    <img src="{{ asset($logo) }}" alt="Logo" class="h-20 object-contain border rounded p-2">
                                </div>
                            @endif
                            <input type="file" name="logo" accept="image/png,image/jpeg,image/jpg,image/svg+xml" @change="handleLogoUpload"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            <p class="text-xs text-gray-500 mt-1">รองรับ PNG, JPG, SVG (สูงสุด 2MB)</p>

                            <!-- Logo Preview -->
                            <div x-show="logoPreview" x-transition class="mt-4">
                                <p class="text-sm font-medium text-gray-700 mb-2">ตัวอย่างโลโก้ใหม่:</p>
                                <div class="border border-gray-300 dark:border-slate-600 rounded-lg p-4 bg-gray-50 dark:bg-slate-700">
                                    <img :src="logoPreview" alt="Preview" class="h-20 object-contain">
                                </div>
                            </div>
                        </div>

                        <!-- Favicon Upload -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Favicon</label>
                            @php
                                $favicon = $settings->get('branding')->firstWhere('key', 'favicon')->value ?? '';
                            @endphp
                            @if($favicon)
                                <div class="mb-3">
                                    <p class="text-xs text-gray-600 mb-1">Favicon ปัจจุบัน:</p>
                                    <img src="{{ asset($favicon) }}" alt="Favicon" class="h-16 w-16 object-contain border rounded p-2">
                                </div>
                            @endif
                            <input type="file" name="favicon" accept="image/png,image/jpeg,image/jpg,image/x-icon" @change="handleFaviconUpload"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            <p class="text-xs text-gray-500 mt-1">รองรับ PNG, JPG, ICO (สูงสุด 512KB)</p>

                            <!-- Favicon Preview -->
                            <div x-show="faviconPreview" x-transition class="mt-4">
                                <p class="text-sm font-medium text-gray-700 mb-2">ตัวอย่าง Favicon ใหม่:</p>
                                <div class="border border-gray-300 dark:border-slate-600 rounded-lg p-4 bg-gray-50 dark:bg-slate-700">
                                    <img :src="faviconPreview" alt="Preview" class="h-16 w-16 object-contain">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end mt-6">
                        <button type="submit" class="px-6 py-2 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition">
                            อัพโหลด
                        </button>
                    </div>
                </form>

                <!-- Loading Overlay -->
                <div x-show="isUploading" x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                     class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
                     style="display: none;">
                    <div class="bg-white rounded-lg p-8 max-w-sm mx-4 text-center shadow-2xl">
                        <div class="mb-4">
                            <svg class="animate-spin h-16 w-16 text-indigo-600 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">กำลังอัพโหลด...</h3>
                        <p class="text-gray-600">กรุณารอสักครู่ ระบบกำลังบันทึกข้อมูล</p>
                    </div>
                </div>
                </div>
            </div>

            <!-- Theme Colors Tab -->
            <div x-show="activeTab === 'theme'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" style="display: none;">
                <form method="POST" action="{{ route('admin.settings.theme') }}" x-data="{
                    primaryStart: '{{ $settings->get('theme')->firstWhere('key', 'theme_primary_start')->value ?? '#3B82F6' }}',
                    primaryEnd: '{{ $settings->get('theme')->firstWhere('key', 'theme_primary_end')->value ?? '#1D4ED8' }}',
                    secondaryStart: '{{ $settings->get('theme')->firstWhere('key', 'theme_secondary_start')->value ?? '#10B981' }}',
                    secondaryEnd: '{{ $settings->get('theme')->firstWhere('key', 'theme_secondary_end')->value ?? '#059669' }}',
                    accentStart: '{{ $settings->get('theme')->firstWhere('key', 'theme_accent_start')->value ?? '#8B5CF6' }}',
                    accentEnd: '{{ $settings->get('theme')->firstWhere('key', 'theme_accent_end')->value ?? '#6D28D9' }}'
                }">
                    @csrf
                    @method('PUT')

                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ตั้งค่าสีของทีม (Gradient)</h3>

                    <div class="space-y-6">
                        <!-- Primary Colors -->
                        <div>
                            <h4 class="font-medium text-gray-700 mb-3">สีหลัก (Primary)</h4>
                            <div class="grid md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm text-gray-600 mb-2">สีเริ่มต้น</label>
                                    <div class="flex gap-2">
                                        <input type="color" x-model="primaryStart" name="theme_primary_start"
                                               class="h-10 w-20 border border-gray-300 rounded">
                                        <input type="text" x-model="primaryStart"
                                               class="flex-1 px-3 py-2 border border-gray-300 rounded-lg">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-600 mb-2">สีสิ้นสุด</label>
                                    <div class="flex gap-2">
                                        <input type="color" x-model="primaryEnd" name="theme_primary_end"
                                               class="h-10 w-20 border border-gray-300 rounded">
                                        <input type="text" x-model="primaryEnd"
                                               class="flex-1 px-3 py-2 border border-gray-300 rounded-lg">
                                    </div>
                                </div>
                            </div>
                            <div class="mt-2 h-16 rounded-lg" :style="`background: linear-gradient(to right, ${primaryStart}, ${primaryEnd})`"></div>
                        </div>

                        <!-- Secondary Colors -->
                        <div>
                            <h4 class="font-medium text-gray-700 mb-3">สีรอง (Secondary)</h4>
                            <div class="grid md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm text-gray-600 mb-2">สีเริ่มต้น</label>
                                    <div class="flex gap-2">
                                        <input type="color" x-model="secondaryStart" name="theme_secondary_start"
                                               class="h-10 w-20 border border-gray-300 rounded">
                                        <input type="text" x-model="secondaryStart"
                                               class="flex-1 px-3 py-2 border border-gray-300 rounded-lg">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-600 mb-2">สีสิ้นสุด</label>
                                    <div class="flex gap-2">
                                        <input type="color" x-model="secondaryEnd" name="theme_secondary_end"
                                               class="h-10 w-20 border border-gray-300 rounded">
                                        <input type="text" x-model="secondaryEnd"
                                               class="flex-1 px-3 py-2 border border-gray-300 rounded-lg">
                                    </div>
                                </div>
                            </div>
                            <div class="mt-2 h-16 rounded-lg" :style="`background: linear-gradient(to right, ${secondaryStart}, ${secondaryEnd})`"></div>
                        </div>

                        <!-- Accent Colors -->
                        <div>
                            <h4 class="font-medium text-gray-700 mb-3">สีเน้น (Accent)</h4>
                            <div class="grid md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm text-gray-600 mb-2">สีเริ่มต้น</label>
                                    <div class="flex gap-2">
                                        <input type="color" x-model="accentStart" name="theme_accent_start"
                                               class="h-10 w-20 border border-gray-300 rounded">
                                        <input type="text" x-model="accentStart"
                                               class="flex-1 px-3 py-2 border border-gray-300 rounded-lg">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-600 mb-2">สีสิ้นสุด</label>
                                    <div class="flex gap-2">
                                        <input type="color" x-model="accentEnd" name="theme_accent_end"
                                               class="h-10 w-20 border border-gray-300 rounded">
                                        <input type="text" x-model="accentEnd"
                                               class="flex-1 px-3 py-2 border border-gray-300 rounded-lg">
                                    </div>
                                </div>
                            </div>
                            <div class="mt-2 h-16 rounded-lg" :style="`background: linear-gradient(to right, ${accentStart}, ${accentEnd})`"></div>
                        </div>
                    </div>

                    <div class="flex justify-end mt-6">
                        <button type="submit" class="px-6 py-2 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition">
                            บันทึกสีธีม
                        </button>
                    </div>
                </form>
            </div>

            <!-- API Settings Tab -->
            <div x-show="activeTab === 'api'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" style="display: none;">
                <form method="POST" action="{{ route('admin.settings.update') }}">
                    @csrf
                    @method('PUT')

                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">การตั้งค่า API Keys</h3>
                    <p class="text-gray-600 mb-6">จัดการ API Keys สำหรับบริการต่างๆ</p>

                    <!-- Google Translate API -->
                    <div class="mb-8 p-6 bg-gray-50 dark:bg-slate-700 rounded-lg">
                        <div class="flex items-center mb-4">
                            <span class="text-2xl mr-3">🌐</span>
                            <h4 class="text-lg font-semibold text-gray-900">Google Translate API</h4>
                        </div>

                        <div class="space-y-4">
                            <!-- Enable/Disable -->
                            <div>
                                <label class="flex items-center">
                                    <input type="checkbox" name="google_translate_enabled" value="1"
                                           {{ old('google_translate_enabled', $settings->get('general')->firstWhere('key', 'google_translate_enabled')->value ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span class="ml-2 text-sm text-gray-700 font-medium">เปิดใช้งาน Google Translate</span>
                                </label>
                            </div>

                            <!-- API Key -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">API Key</label>
                                <input type="text" name="google_translate_api_key"
                                       value="{{ old('google_translate_api_key', $settings->get('general')->firstWhere('key', 'google_translate_api_key')->value ?? '') }}"
                                       placeholder="AIzaSy..."
                                       class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                <p class="text-xs text-gray-500 mt-1">ดูวิธีการสร้าง API Key ได้ที่ <a href="https://console.cloud.google.com" target="_blank" class="text-indigo-600 hover:underline">Google Cloud Console</a></p>
                            </div>

                            <!-- Project ID -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Project ID (ไม่บังคับ)</label>
                                <input type="text" name="google_translate_project_id"
                                       value="{{ old('google_translate_project_id', $settings->get('general')->firstWhere('key', 'google_translate_project_id')->value ?? '') }}"
                                       placeholder="my-project-id"
                                       class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            </div>

                            <!-- Source Language -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ภาษาต้นทาง</label>
                                <select name="translate_source_language"
                                        class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                    @php
                                        $currentSource = $settings->get('general')->firstWhere('key', 'translate_source_language')->value ?? 'th';
                                    @endphp
                                    <option value="th" {{ $currentSource === 'th' ? 'selected' : '' }}>ไทย (Thai)</option>
                                    <option value="en" {{ $currentSource === 'en' ? 'selected' : '' }}>English</option>
                                </select>
                            </div>

                            <!-- Cache Settings -->
                            <div>
                                <label class="flex items-center">
                                    <input type="checkbox" name="translate_cache_enabled" value="1"
                                           {{ old('translate_cache_enabled', $settings->get('general')->firstWhere('key', 'translate_cache_enabled')->value ?? true) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span class="ml-2 text-sm text-gray-700">เปิดใช้งานแคช (ลด API Calls)</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Future API Sections (Placeholder) -->
                    <div class="mb-8 p-6 bg-gray-50 dark:bg-slate-700 rounded-lg">
                        <div class="flex items-center mb-4">
                            <span class="text-2xl mr-3">💳</span>
                            <h4 class="text-lg font-semibold text-gray-900">Payment Gateway API</h4>
                            <span class="ml-3 px-2 py-1 text-xs bg-gray-200 dark:bg-slate-600 text-gray-600 dark:text-gray-300 rounded">Coming Soon</span>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">การตั้งค่า API สำหรับระบบชำระเงิน (Stripe, PayPal, etc.)</p>
                    </div>

                    <div class="mb-8 p-6 bg-gray-50 dark:bg-slate-700 rounded-lg">
                        <div class="flex items-center mb-4">
                            <span class="text-2xl mr-3">📧</span>
                            <h4 class="text-lg font-semibold text-gray-900">Email Service API</h4>
                            <span class="ml-3 px-2 py-1 text-xs bg-gray-200 dark:bg-slate-600 text-gray-600 dark:text-gray-300 rounded">Coming Soon</span>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">การตั้งค่า API สำหรับบริการอีเมล (SendGrid, Mailgun, etc.)</p>
                    </div>

                    <div class="flex justify-end mt-6">
                        <button type="submit" class="px-6 py-2 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition">
                            บันทึกการตั้งค่า API
                        </button>
                    </div>
                </form>
            </div>

            <!-- Page Loader Settings Tab -->
            <div x-show="activeTab === 'pageloader'" style="display: none;">
                <div x-data="{
                    loaderEnabled: {{ \App\Models\Setting::get('page_loader_enabled', true) ? 'true' : 'false' }},
                    loaderType: '{{ \App\Models\Setting::get('page_loader_type', 'spinner') }}',
                    loaderColor: '{{ \App\Models\Setting::get('page_loader_color', '#6366f1') }}',
                    loaderColorSecondary: '{{ \App\Models\Setting::get('page_loader_color_secondary', '#8b5cf6') }}',
                    gifPreview: null,
                    handleGifUpload(event) {
                        const file = event.target.files[0];
                        if (file && file.type === 'image/gif') {
                            const reader = new FileReader();
                            reader.onload = (e) => {
                                this.gifPreview = e.target.result;
                            };
                            reader.readAsDataURL(file);
                        }
                    }
                }">
                    <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div class="mb-8">
                            <h3 class="text-xl font-semibold text-gray-800 mb-4">การตั้งค่าอนิเมชั่นโหลดหน้า</h3>
                            <p class="text-gray-600 mb-6">กำหนดรูปแบบและการแสดงผลของอนิเมชั่นโหลดหน้าแบบมืออาชีพ</p>

                            <!-- Enable/Disable -->
                            <div class="mb-6 p-6 bg-gray-50 dark:bg-slate-700 rounded-lg">
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" name="page_loader_enabled" value="1" x-model="loaderEnabled"
                                           class="form-checkbox h-5 w-5 text-indigo-600 rounded focus:ring-indigo-500 transition">
                                    <span class="ml-3 text-gray-700 font-medium">เปิดใช้งานอนิเมชั่นโหลดหน้า</span>
                                </label>
                                <p class="ml-8 mt-2 text-sm text-gray-500">แสดงอนิเมชั่นขณะโหลดหน้าเว็บ</p>
                            </div>

                            <!-- Progress Mode Selection -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-3">โหมดการแสดงความคืบหน้า</label>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    @php
                                        $currentProgressMode = \App\Models\Setting::get('page_loader_progress_mode', 'real');
                                    @endphp

                                    <!-- Real Progress -->
                                    <label class="relative flex items-start p-4 border-2 rounded-lg cursor-pointer transition-all {{ $currentProgressMode === 'real' ? 'border-green-500 bg-green-50' : 'border-gray-200 hover:border-green-300' }}">
                                        <input type="radio" name="page_loader_progress_mode" value="real"
                                               {{ $currentProgressMode === 'real' ? 'checked' : '' }}
                                               class="form-radio h-5 w-5 text-green-600 focus:ring-green-500 mt-1">
                                        <div class="ml-3">
                                            <div class="flex items-center">
                                                <span class="text-2xl mr-2">✅</span>
                                                <span class="font-semibold text-gray-800">Real Progress</span>
                                            </div>
                                            <p class="text-sm text-gray-600 mt-1">ใช้ Performance API ติดตามการโหลดจริง</p>
                                            <ul class="text-xs text-gray-500 mt-2 space-y-1 list-disc list-inside">
                                                <li>แสดงเปอร์เซ็นต์จริง 100%</li>
                                                <li>ติดตาม JS, CSS, รูปภาพ, ฟอนต์</li>
                                                <li>แสดงสถานะและรายละเอียด</li>
                                            </ul>
                                        </div>
                                    </label>

                                    <!-- Fake Progress (Animation Only) -->
                                    <label class="relative flex items-start p-4 border-2 rounded-lg cursor-pointer transition-all {{ $currentProgressMode === 'fake' ? 'border-purple-500 bg-purple-50' : 'border-gray-200 hover:border-purple-300' }}">
                                        <input type="radio" name="page_loader_progress_mode" value="fake"
                                               {{ $currentProgressMode === 'fake' ? 'checked' : '' }}
                                               class="form-radio h-5 w-5 text-purple-600 focus:ring-purple-500 mt-1">
                                        <div class="ml-3">
                                            <div class="flex items-center">
                                                <span class="text-2xl mr-2">🎭</span>
                                                <span class="font-semibold text-gray-800">Fake Progress (Animation Only)</span>
                                            </div>
                                            <p class="text-sm text-gray-600 mt-1">แสดงแค่ animation ไม่ติดตามการโหลด</p>
                                            <ul class="text-xs text-gray-500 mt-2 space-y-1 list-disc list-inside">
                                                <li>แสดง Loader Animation เฉยๆ</li>
                                                <li>ไม่แสดงเปอร์เซ็นต์และสถานะ</li>
                                                <li>เบาและรวดเร็วกว่า Real Progress</li>
                                            </ul>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Loader Type -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-3">รูปแบบอนิเมชั่น</label>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                    @php
                                        $types = [
                                            'spinner' => ['name' => 'วงล้อหมุน', 'icon' => '🔄'],
                                            'gradient_spinner' => ['name' => 'วงล้อ Gradient', 'icon' => '🌈'],
                                            'dots' => ['name' => 'จุดกระโดด', 'icon' => '⚫'],
                                            'pulse' => ['name' => 'พัลส์', 'icon' => '💫'],
                                            'progress' => ['name' => 'แถบความคืบหน้า', 'icon' => '📊'],
                                            'wave' => ['name' => 'คลื่น', 'icon' => '〰️'],
                                            'bouncing_balls' => ['name' => 'ลูกบอลกระเด้ง', 'icon' => '⚽'],
                                            'custom_gif' => ['name' => 'GIF ที่กำหนดเอง', 'icon' => '🎬']
                                        ];
                                    @endphp

                                    @foreach($types as $type => $info)
                                    <label class="relative flex items-center p-4 border-2 rounded-lg cursor-pointer transition-all"
                                           :class="loaderType === '{{ $type }}' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-indigo-300'">
                                        <input type="radio" name="page_loader_type" value="{{ $type }}" x-model="loaderType"
                                               class="form-radio h-5 w-5 text-indigo-600 focus:ring-indigo-500">
                                        <span class="ml-3 flex items-center">
                                            <span class="text-2xl mr-2">{{ $info['icon'] }}</span>
                                            <span class="font-medium text-gray-700">{{ $info['name'] }}</span>
                                        </span>
                                    </label>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Color Settings -->
                            <div class="mb-6 p-6 bg-gray-50 dark:bg-slate-700 rounded-lg">
                                <label class="block text-sm font-medium text-gray-700 mb-3">การตั้งค่าสี</label>

                                <!-- Primary Color -->
                                <div class="mb-4">
                                    <label class="block text-sm text-gray-600 mb-2">สีหลัก</label>
                                    <div class="flex items-center space-x-4">
                                        <input type="color" name="page_loader_color" x-model="loaderColor"
                                               class="h-12 w-24 rounded cursor-pointer border-2 border-gray-300">
                                        <input type="text" x-model="loaderColor"
                                               placeholder="#6366f1"
                                               class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                    </div>
                                </div>

                                <!-- Secondary Color (for gradients) -->
                                <div x-show="['gradient_spinner', 'wave', 'progress'].includes(loaderType)" x-transition>
                                    <label class="block text-sm text-gray-600 mb-2">สีรอง (สำหรับ Gradient)</label>
                                    <div class="flex items-center space-x-4">
                                        <input type="color" name="page_loader_color_secondary" x-model="loaderColorSecondary"
                                               class="h-12 w-24 rounded cursor-pointer border-2 border-gray-300">
                                        <input type="text" x-model="loaderColorSecondary"
                                               placeholder="#8b5cf6"
                                               class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                    </div>
                                    <p class="mt-2 text-xs text-gray-500">สีนี้จะถูกใช้เป็นสีที่สองใน gradient effect</p>
                                </div>
                            </div>

                            <!-- GIF Upload (shown only when custom_gif is selected) -->
                            <div x-show="loaderType === 'custom_gif'" x-transition class="mb-6 p-6 bg-gradient-to-r from-purple-50 to-pink-50 rounded-lg border-2 border-purple-200">
                                <label class="block text-sm font-medium text-gray-700 mb-3">อัพโหลด GIF</label>
                                <input type="file" name="page_loader_gif" accept="image/gif" @change="handleGifUpload"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                <p class="mt-2 text-xs text-gray-500">รองรับไฟล์ GIF เท่านั้น (สูงสุด 2MB)</p>

                                @php
                                    $existingGif = \App\Models\Setting::get('page_loader_gif');
                                @endphp
                                @if($existingGif)
                                <div class="mt-4">
                                    <p class="text-sm font-medium text-gray-700 mb-2">GIF ปัจจุบัน:</p>
                                    <img src="{{ asset($existingGif) }}" alt="Current GIF" class="max-w-xs rounded-lg border-2 border-gray-300">
                                </div>
                                @endif

                                <!-- GIF Preview -->
                                <div x-show="gifPreview" x-transition class="mt-4">
                                    <p class="text-sm font-medium text-gray-700 mb-2">ตัวอย่าง GIF ใหม่:</p>
                                    <img :src="gifPreview" alt="Preview" class="max-w-xs rounded-lg border-2 border-purple-300">
                                </div>
                            </div>

                            <!-- Live Preview Section -->
                            <div class="mb-6 p-6 bg-gradient-to-br from-gray-900 to-gray-800 rounded-lg border-2 border-gray-700">
                                <h4 class="text-sm font-medium text-white mb-4 flex items-center">
                                    <span class="mr-2">👁️</span>
                                    ตัวอย่างแบบ Real-time
                                </h4>
                                <div class="flex items-center justify-center h-48 bg-white dark:bg-gray-900 rounded-lg relative overflow-hidden">
                                    <!-- Spinner -->
                                    <div x-show="loaderType === 'spinner'" class="relative">
                                        <div class="w-20 h-20 border-4 border-gray-200 rounded-full animate-spin"
                                             :style="`border-top-color: ${loaderColor}`">
                                        </div>
                                    </div>

                                    <!-- Gradient Spinner -->
                                    <div x-show="loaderType === 'gradient_spinner'" class="relative">
                                        <div class="w-20 h-20 rounded-full animate-spin"
                                             :style="`background: conic-gradient(from 0deg, ${loaderColor}, ${loaderColorSecondary}, ${loaderColor}); mask: radial-gradient(farthest-side, transparent calc(100% - 4px), #000 calc(100% - 4px))`">
                                        </div>
                                    </div>

                                    <!-- Dots -->
                                    <div x-show="loaderType === 'dots'" class="flex space-x-2">
                                        <div class="w-4 h-4 rounded-full animate-bounce" :style="`background-color: ${loaderColor}`"></div>
                                        <div class="w-4 h-4 rounded-full animate-bounce" :style="`background-color: ${loaderColor}; animation-delay: 0.2s`"></div>
                                        <div class="w-4 h-4 rounded-full animate-bounce" :style="`background-color: ${loaderColor}; animation-delay: 0.4s`"></div>
                                    </div>

                                    <!-- Pulse -->
                                    <div x-show="loaderType === 'pulse'" class="relative">
                                        <div class="w-20 h-20 rounded-full animate-ping absolute" :style="`background-color: ${loaderColor}; opacity: 0.3`"></div>
                                        <div class="w-20 h-20 rounded-full animate-pulse" :style="`background-color: ${loaderColor}`"></div>
                                    </div>

                                    <!-- Progress Bar -->
                                    <div x-show="loaderType === 'progress'" class="w-64">
                                        <div class="h-3 bg-gray-200 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full animate-pulse" :style="`background: linear-gradient(90deg, ${loaderColor}, ${loaderColorSecondary}); width: 70%`"></div>
                                        </div>
                                    </div>

                                    <!-- Wave -->
                                    <div x-show="loaderType === 'wave'" class="flex items-end space-x-1">
                                        <div class="w-2 h-8 rounded-full animate-pulse" :style="`background: linear-gradient(180deg, ${loaderColor}, ${loaderColorSecondary}); animation-delay: 0s`"></div>
                                        <div class="w-2 h-12 rounded-full animate-pulse" :style="`background: linear-gradient(180deg, ${loaderColor}, ${loaderColorSecondary}); animation-delay: 0.1s`"></div>
                                        <div class="w-2 h-16 rounded-full animate-pulse" :style="`background: linear-gradient(180deg, ${loaderColor}, ${loaderColorSecondary}); animation-delay: 0.2s`"></div>
                                        <div class="w-2 h-12 rounded-full animate-pulse" :style="`background: linear-gradient(180deg, ${loaderColor}, ${loaderColorSecondary}); animation-delay: 0.3s`"></div>
                                        <div class="w-2 h-8 rounded-full animate-pulse" :style="`background: linear-gradient(180deg, ${loaderColor}, ${loaderColorSecondary}); animation-delay: 0.4s`"></div>
                                    </div>

                                    <!-- Bouncing Balls -->
                                    <div x-show="loaderType === 'bouncing_balls'" class="flex space-x-1">
                                        <div class="w-5 h-5 rounded-full animate-bounce" :style="`background-color: ${loaderColor}`"></div>
                                        <div class="w-5 h-5 rounded-full animate-bounce" :style="`background-color: ${loaderColor}; animation-delay: 0.1s; opacity: 0.8`"></div>
                                        <div class="w-5 h-5 rounded-full animate-bounce" :style="`background-color: ${loaderColor}; animation-delay: 0.2s; opacity: 0.6`"></div>
                                    </div>

                                    <!-- Custom GIF -->
                                    <div x-show="loaderType === 'custom_gif'" class="text-center">
                                        <template x-if="gifPreview">
                                            <img :src="gifPreview" alt="Custom GIF" class="max-h-32 rounded-lg">
                                        </template>
                                        <template x-if="!gifPreview">
                                            @if($existingGif ?? false)
                                            <img src="{{ asset($existingGif) }}" alt="Current GIF" class="max-h-32 rounded-lg">
                                            @else
                                            <p class="text-gray-500">กรุณาอัพโหลด GIF</p>
                                            @endif
                                        </template>
                                    </div>

                                    <!-- Disabled Overlay -->
                                    <div x-show="!loaderEnabled" class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center">
                                        <p class="text-white font-semibold">ปิดใช้งาน</p>
                                    </div>
                                </div>
                                <p class="mt-3 text-xs text-gray-400">ตัวอย่างจะเปลี่ยนแปลงแบบ real-time ตามการตั้งค่าของคุณ</p>
                            </div>
                        </div>

                        <div class="flex justify-end mt-6">
                            <button type="submit" class="px-6 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-lg hover:from-indigo-700 hover:to-purple-700 transition shadow-lg">
                                💾 บันทึกการตั้งค่า
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
