@extends('layouts.admin')

@section('title', 'ตั้งค่าแอปพลิเคชัน')

@section('content')
<div class="container-fluid px-4 py-6">
    <!-- Header -->
    <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-blue-500 via-indigo-600 to-purple-600 dark:from-blue-600 dark:via-indigo-700 dark:to-purple-700 p-8 shadow-2xl">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS1vcGFjaXR5PSIwLjEiIHN0cm9rZS13aWR0aD0iMSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNncmlkKSIvPjwvc3ZnPg==')] opacity-30"></div>
        <div class="relative flex items-center justify-between">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-14 h-14 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-white mb-1">ตั้งค่าแอปพลิเคชัน</h1>
                        <p class="text-blue-100 dark:text-purple-200">จัดการการตั้งค่าแอปพลิเคชันมือถือ</p>
                    </div>
                </div>
            </div>
            @if($appSettings->is_active)
                <div class="hidden md:block">
                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-green-100 text-green-800">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        เปิดใช้งาน
                    </span>
                </div>
            @else
                <div class="hidden md:block">
                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-red-100 text-red-800">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                        ปิดใช้งาน
                    </span>
                </div>
            @endif
        </div>
    </div>

    <!-- Success Message -->
    @if(session('success'))
        <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-lg">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-green-800 font-semibold">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Error Messages -->
    @if($errors->any())
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <h4 class="text-red-800 font-semibold mb-1">เกิดข้อผิดพลาด</h4>
                    <ul class="list-disc list-inside text-red-700 text-sm">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <!-- Settings Form -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-700 overflow-hidden">
        <form method="POST" action="{{ route('admin.app-management.settings.update') }}" class="p-6 dark:bg-slate-800">
            @csrf
            @method('PUT')

            <div class="space-y-8">
                <!-- Basic Information -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 pb-2 border-b border-gray-200 dark:border-slate-700">
                        <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        ข้อมูลพื้นฐาน
                    </h3>

                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label for="app_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                ชื่อแอปพลิเคชัน <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="app_name" id="app_name" value="{{ old('app_name', $appSettings->app_name) }}"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                   required>
                        </div>

                        <div>
                            <label for="app_short_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                ชื่อย่อแอปพลิเคชัน
                            </label>
                            <input type="text" name="app_short_name" id="app_short_name" value="{{ old('app_short_name', $appSettings->app_short_name) }}"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                   maxlength="50">
                        </div>

                        <div class="md:col-span-2">
                            <label for="app_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                คำอธิบาย
                            </label>
                            <textarea name="app_description" id="app_description" rows="3"
                                      class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">{{ old('app_description', $appSettings->app_description) }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Version Information -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 pb-2 border-b border-gray-200 dark:border-slate-700">
                        <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                        ข้อมูลเวอร์ชัน
                    </h3>

                    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div>
                            <label for="app_version" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                เวอร์ชัน <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="app_version" id="app_version" value="{{ old('app_version', $appSettings->app_version) }}"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                   placeholder="1.0.0" required>
                        </div>

                        <div>
                            <label for="app_build_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Build Number <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="app_build_number" id="app_build_number" value="{{ old('app_build_number', $appSettings->app_build_number) }}"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                   placeholder="1" required>
                        </div>

                        <div>
                            <label for="min_supported_version" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                เวอร์ชันขั้นต่ำที่รองรับ
                            </label>
                            <input type="text" name="min_supported_version" id="min_supported_version" value="{{ old('min_supported_version', $appSettings->min_supported_version) }}"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                   placeholder="1.0.0">
                        </div>

                        <div>
                            <label for="latest_version" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                เวอร์ชันล่าสุด
                            </label>
                            <input type="text" name="latest_version" id="latest_version" value="{{ old('latest_version', $appSettings->latest_version) }}"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                   placeholder="1.0.0">
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="force_update" value="1" {{ old('force_update', $appSettings->force_update) ? 'checked' : '' }}
                                   class="form-checkbox h-5 w-5 text-indigo-600 rounded focus:ring-indigo-500 transition">
                            <span class="ml-3 text-gray-700 dark:text-gray-300 font-medium">บังคับอัพเดท (Force Update)</span>
                        </label>
                        <p class="ml-8 mt-1 text-sm text-gray-500">ผู้ใช้จะต้องอัพเดทแอปก่อนจึงจะใช้งานได้</p>
                    </div>
                </div>

                <!-- Update Messages -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 pb-2 border-b border-gray-200 dark:border-slate-700">
                        <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                        </svg>
                        ข้อความอัพเดท
                    </h3>

                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label for="update_message_th" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                ข้อความอัพเดท (ไทย)
                            </label>
                            <textarea name="update_message_th" id="update_message_th" rows="4"
                                      class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">{{ old('update_message_th', $appSettings->update_message_th) }}</textarea>
                        </div>

                        <div>
                            <label for="update_message_en" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                ข้อความอัพเดท (English)
                            </label>
                            <textarea name="update_message_en" id="update_message_en" rows="4"
                                      class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">{{ old('update_message_en', $appSettings->update_message_en) }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Store Links -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 pb-2 border-b border-gray-200 dark:border-slate-700">
                        <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        ลิงก์ร้านค้าแอปพลิเคชัน
                    </h3>

                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label for="app_store_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                App Store URL (iOS)
                            </label>
                            <input type="url" name="app_store_url" id="app_store_url" value="{{ old('app_store_url', $appSettings->app_store_url) }}"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                   placeholder="https://apps.apple.com/...">
                        </div>

                        <div>
                            <label for="play_store_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Play Store URL (Android)
                            </label>
                            <input type="url" name="play_store_url" id="play_store_url" value="{{ old('play_store_url', $appSettings->play_store_url) }}"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                   placeholder="https://play.google.com/store/apps/details?id=...">
                        </div>
                    </div>
                </div>

                <!-- Support Information -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 pb-2 border-b border-gray-200 dark:border-slate-700">
                        <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        ข้อมูลการติดต่อและสนับสนุน
                    </h3>

                    <div class="grid md:grid-cols-3 gap-6">
                        <div>
                            <label for="support_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                อีเมลสนับสนุน
                            </label>
                            <input type="email" name="support_email" id="support_email" value="{{ old('support_email', $appSettings->support_email) }}"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                   placeholder="support@example.com">
                        </div>

                        <div>
                            <label for="support_phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                เบอร์โทรศัพท์สนับสนุน
                            </label>
                            <input type="text" name="support_phone" id="support_phone" value="{{ old('support_phone', $appSettings->support_phone) }}"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                   placeholder="02-xxx-xxxx">
                        </div>

                        <div>
                            <label for="support_line_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                LINE ID สนับสนุน
                            </label>
                            <input type="text" name="support_line_id" id="support_line_id" value="{{ old('support_line_id', $appSettings->support_line_id) }}"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                   placeholder="@yourlineid">
                        </div>
                    </div>
                </div>

                <!-- Legal Links -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 pb-2 border-b border-gray-200 dark:border-slate-700">
                        <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        เอกสารทางกฎหมาย
                    </h3>

                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label for="privacy_policy_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                นโยบายความเป็นส่วนตัว (Privacy Policy URL)
                            </label>
                            <input type="url" name="privacy_policy_url" id="privacy_policy_url" value="{{ old('privacy_policy_url', $appSettings->privacy_policy_url) }}"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                   placeholder="https://example.com/privacy">
                        </div>

                        <div>
                            <label for="terms_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                เงื่อนไขการใช้งาน (Terms of Service URL)
                            </label>
                            <input type="url" name="terms_url" id="terms_url" value="{{ old('terms_url', $appSettings->terms_url) }}"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                   placeholder="https://example.com/terms">
                        </div>
                    </div>
                </div>

                <!-- Localization Settings -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 pb-2 border-b border-gray-200 dark:border-slate-700">
                        <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        การตั้งค่าภาษาและภูมิภาค
                    </h3>

                    <div class="grid md:grid-cols-3 gap-6">
                        <div>
                            <label for="default_language" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                ภาษาเริ่มต้น <span class="text-red-500">*</span>
                            </label>
                            <select name="default_language" id="default_language"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required>
                                <option value="th" {{ old('default_language', $appSettings->default_language) === 'th' ? 'selected' : '' }}>ไทย (Thai)</option>
                                <option value="en" {{ old('default_language', $appSettings->default_language) === 'en' ? 'selected' : '' }}>English</option>
                            </select>
                        </div>

                        <div>
                            <label for="currency" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                สกุลเงิน <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="currency" id="currency" value="{{ old('currency', $appSettings->currency) }}"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                   placeholder="THB" required>
                        </div>

                        <div>
                            <label for="timezone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                เขตเวลา <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="timezone" id="timezone" value="{{ old('timezone', $appSettings->timezone) }}"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                   placeholder="Asia/Bangkok" required>
                        </div>
                    </div>
                </div>

                <!-- Status -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 pb-2 border-b border-gray-200 dark:border-slate-700">
                        <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        สถานะ
                    </h3>

                    <div>
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $appSettings->is_active) ? 'checked' : '' }}
                                   class="form-checkbox h-5 w-5 text-indigo-600 rounded focus:ring-indigo-500 transition">
                            <span class="ml-3 text-gray-700 dark:text-gray-300 font-medium">เปิดใช้งานแอปพลิเคชัน</span>
                        </label>
                        <p class="ml-8 mt-1 text-sm text-gray-500">ปิดการใช้งานนี้เพื่อทำให้แอปอยู่ในโหมดบำรุงรักษา</p>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end mt-8 pt-6 border-t border-gray-200 dark:border-slate-700">
                <button type="submit" class="px-8 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 dark:from-blue-500 dark:to-indigo-500 text-white rounded-xl hover:from-blue-700 hover:to-indigo-700 dark:hover:from-blue-600 dark:hover:to-indigo-600 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 font-bold">
                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                    </svg>
                    บันทึกการตั้งค่า
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
