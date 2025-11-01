@extends('layouts.admin')

@section('title', 'ตั้งค่าระบบ')

@section('content')
<div x-data="{ activeTab: 'general' }" class="space-y-6">
    <!-- Tab Navigation -->
    <div class="bg-white rounded-lg shadow-md">
        <div class="border-b border-gray-200">
            <nav class="flex -mb-px">
                <button @click="activeTab = 'general'"
                        :class="{ 'border-indigo-500 text-indigo-600': activeTab === 'general', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'general' }"
                        class="px-6 py-4 text-sm font-medium border-b-2 transition-colors duration-200">
                    <span class="flex items-center">
                        <span class="text-lg mr-2">⚙️</span>
                        ตั้งค่าทั่วไป
                    </span>
                </button>

                <button @click="activeTab = 'affiliate'"
                        :class="{ 'border-indigo-500 text-indigo-600': activeTab === 'affiliate', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'affiliate' }"
                        class="px-6 py-4 text-sm font-medium border-b-2 transition-colors duration-200">
                    <span class="flex items-center">
                        <span class="text-lg mr-2">🌐</span>
                        ตั้งค่า Affiliate
                    </span>
                </button>

                <button @click="activeTab = 'branding'"
                        :class="{ 'border-indigo-500 text-indigo-600': activeTab === 'branding', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'branding' }"
                        class="px-6 py-4 text-sm font-medium border-b-2 transition-colors duration-200">
                    <span class="flex items-center">
                        <span class="text-lg mr-2">🎨</span>
                        โลโก้ & Favicon
                    </span>
                </button>

                <button @click="activeTab = 'theme'"
                        :class="{ 'border-indigo-500 text-indigo-600': activeTab === 'theme', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'theme' }"
                        class="px-6 py-4 text-sm font-medium border-b-2 transition-colors duration-200">
                    <span class="flex items-center">
                        <span class="text-lg mr-2">🎨</span>
                        สีธีม
                    </span>
                </button>

                <button @click="activeTab = 'api'"
                        :class="{ 'border-indigo-500 text-indigo-600': activeTab === 'api', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'api' }"
                        class="px-6 py-4 text-sm font-medium border-b-2 transition-colors duration-200">
                    <span class="flex items-center">
                        <span class="text-lg mr-2">🔑</span>
                        การตั้งค่า API
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
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">ตั้งค่าทั่วไป</h3>

                            <div class="mb-4">
                                <label for="app_name" class="block text-sm font-medium text-gray-700 mb-2">ชื่อแอพพลิเคชั่น</label>
                                <input type="text" name="app_name" id="app_name" value="{{ old('app_name', $settings->get('general')->firstWhere('key', 'app_name')->value ?? 'TP-Affiliate') }}"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
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
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">ตั้งค่า Affiliate</h3>

                            <div class="mb-4">
                                <label for="commission_rate" class="block text-sm font-medium text-gray-700 mb-2">อัตราคอมมิชชั่น (%)</label>
                                <input type="number" name="commission_rate" id="commission_rate" min="0" max="100" step="0.01"
                                       value="{{ old('commission_rate', $settings->get('affiliate')->firstWhere('key', 'commission_rate')->value ?? 10) }}"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
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
                                <label for="default_sponsor_referral_code" class="block text-sm font-medium text-gray-700 mb-2">รหัสแนะนำเริ่มต้น (Default Sponsor ID)</label>
                                <input type="text" name="default_sponsor_referral_code" id="default_sponsor_referral_code"
                                       value="{{ old('default_sponsor_referral_code', $settings->get('affiliate')->firstWhere('key', 'default_sponsor_referral_code')->value ?? '') }}"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                       placeholder="กรอกรหัสแนะนำเริ่มต้น">
                                <p class="text-xs text-gray-500 mt-1">ผู้สมัครที่ไม่มีรหัสแนะนำจะถูกต่อสายงานอัตโนมัติกับรหัสนี้</p>
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

                        <h3 class="text-lg font-semibold text-gray-900 mb-4">ตั้งค่าโลโก้และ Favicon</h3>

                    <div class="grid md:grid-cols-2 gap-6">
                        <!-- Logo Upload -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">โลโก้</label>
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
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            <p class="text-xs text-gray-500 mt-1">รองรับ PNG, JPG, SVG (สูงสุด 2MB)</p>

                            <!-- Logo Preview -->
                            <div x-show="logoPreview" x-transition class="mt-4">
                                <p class="text-sm font-medium text-gray-700 mb-2">ตัวอย่างโลโก้ใหม่:</p>
                                <div class="border rounded-lg p-4 bg-gray-50">
                                    <img :src="logoPreview" alt="Preview" class="h-20 object-contain">
                                </div>
                            </div>
                        </div>

                        <!-- Favicon Upload -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Favicon</label>
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
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            <p class="text-xs text-gray-500 mt-1">รองรับ PNG, JPG, ICO (สูงสุด 512KB)</p>

                            <!-- Favicon Preview -->
                            <div x-show="faviconPreview" x-transition class="mt-4">
                                <p class="text-sm font-medium text-gray-700 mb-2">ตัวอย่าง Favicon ใหม่:</p>
                                <div class="border rounded-lg p-4 bg-gray-50">
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

                    <h3 class="text-lg font-semibold text-gray-900 mb-4">ตั้งค่าสีของทีม (Gradient)</h3>

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

                    <h3 class="text-lg font-semibold text-gray-900 mb-4">การตั้งค่า API Keys</h3>
                    <p class="text-gray-600 mb-6">จัดการ API Keys สำหรับบริการต่างๆ</p>

                    <!-- Google Translate API -->
                    <div class="mb-8 p-6 bg-gray-50 rounded-lg">
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
                                <label class="block text-sm font-medium text-gray-700 mb-2">API Key</label>
                                <input type="text" name="google_translate_api_key"
                                       value="{{ old('google_translate_api_key', $settings->get('general')->firstWhere('key', 'google_translate_api_key')->value ?? '') }}"
                                       placeholder="AIzaSy..."
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                <p class="text-xs text-gray-500 mt-1">ดูวิธีการสร้าง API Key ได้ที่ <a href="https://console.cloud.google.com" target="_blank" class="text-indigo-600 hover:underline">Google Cloud Console</a></p>
                            </div>

                            <!-- Project ID -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Project ID (ไม่บังคับ)</label>
                                <input type="text" name="google_translate_project_id"
                                       value="{{ old('google_translate_project_id', $settings->get('general')->firstWhere('key', 'google_translate_project_id')->value ?? '') }}"
                                       placeholder="my-project-id"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            </div>

                            <!-- Source Language -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">ภาษาต้นทาง</label>
                                <select name="translate_source_language"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
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
                    <div class="mb-8 p-6 bg-gray-50 rounded-lg">
                        <div class="flex items-center mb-4">
                            <span class="text-2xl mr-3">💳</span>
                            <h4 class="text-lg font-semibold text-gray-900">Payment Gateway API</h4>
                            <span class="ml-3 px-2 py-1 text-xs bg-gray-200 text-gray-600 rounded">Coming Soon</span>
                        </div>
                        <p class="text-sm text-gray-500">การตั้งค่า API สำหรับระบบชำระเงิน (Stripe, PayPal, etc.)</p>
                    </div>

                    <div class="mb-8 p-6 bg-gray-50 rounded-lg">
                        <div class="flex items-center mb-4">
                            <span class="text-2xl mr-3">📧</span>
                            <h4 class="text-lg font-semibold text-gray-900">Email Service API</h4>
                            <span class="ml-3 px-2 py-1 text-xs bg-gray-200 text-gray-600 rounded">Coming Soon</span>
                        </div>
                        <p class="text-sm text-gray-500">การตั้งค่า API สำหรับบริการอีเมล (SendGrid, Mailgun, etc.)</p>
                    </div>

                    <div class="flex justify-end mt-6">
                        <button type="submit" class="px-6 py-2 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition">
                            บันทึกการตั้งค่า API
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
