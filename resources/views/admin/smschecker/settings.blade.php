@extends('layouts.admin-v3')

@section('title', 'ตั้งค่า SMS Payment Checker')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                ⚙️ ตั้งค่า SMS Payment Checker
            </h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">
                ตั้งค่าการเชื่อมต่อและคู่มือการใช้งานแอพ SmsChecker
            </p>
        </div>
        <a href="{{ route('admin.smschecker.index') }}"
           class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
            ← กลับ Dashboard
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Getting Started --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-blue-500 to-indigo-600">
                <h2 class="text-lg font-semibold text-white flex items-center">
                    ⚡ เริ่มต้นใช้งาน
                </h2>
            </div>
            <div class="p-6 space-y-6">
                {{-- Step 1 --}}
                <div class="flex gap-4">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                        <span class="text-blue-600 dark:text-blue-400 font-bold">1</span>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-1">ดาวน์โหลดแอพ SmsChecker</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">ติดตั้งแอพ SmsChecker บนมือถือ Android ที่รับ SMS จากธนาคาร</p>
                        <a href="{{ $appDownloadUrl }}" target="_blank"
                           class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-xl transition-colors">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                            </svg>
                            ดาวน์โหลด APK
                        </a>
                    </div>
                </div>

                {{-- Step 2 --}}
                <div class="flex gap-4">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                        <span class="text-blue-600 dark:text-blue-400 font-bold">2</span>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-1">สร้างอุปกรณ์ใหม่</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">สร้าง Device ใหม่เพื่อรับ API Key และ QR Code</p>
                        <a href="{{ route('admin.smschecker.device-create') }}"
                           class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition-colors">
                            ➕ สร้างอุปกรณ์ใหม่
                        </a>
                    </div>
                </div>

                {{-- Step 3 --}}
                <div class="flex gap-4">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                        <span class="text-blue-600 dark:text-blue-400 font-bold">3</span>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-1">สแกน QR Code</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">เปิดแอพ SmsChecker แล้วสแกน QR Code ที่แสดงหลังสร้าง Device เพื่อเชื่อมต่ออัตโนมัติ</p>
                    </div>
                </div>

                {{-- Step 4 --}}
                <div class="flex gap-4">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                        <span class="text-blue-600 dark:text-blue-400 font-bold">4</span>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-1">ให้สิทธิ์อ่าน SMS</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">อนุญาตให้แอพอ่าน SMS เพื่อตรวจจับการแจ้งเตือนจากธนาคาร</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Server Info --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                    🖥️ ข้อมูล Server
                </h2>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Server URL</label>
                    <div class="flex items-center gap-2">
                        <code class="flex-1 px-4 py-3 bg-gray-100 dark:bg-gray-700 rounded-xl text-sm font-mono text-gray-900 dark:text-white overflow-x-auto">
                            {{ config('app.url') }}
                        </code>
                        <button onclick="navigator.clipboard.writeText('{{ config('app.url') }}'); alert('คัดลอกแล้ว!')"
                                class="px-3 py-3 bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 rounded-xl transition-colors"
                                title="คัดลอก">
                            📋
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">API Endpoint</label>
                    <code class="block w-full px-4 py-3 bg-gray-100 dark:bg-gray-700 rounded-xl text-sm font-mono text-gray-900 dark:text-white overflow-x-auto">
                        {{ config('app.url') }}/api/v1/sms-payment/notify
                    </code>
                </div>
            </div>
        </div>
    </div>

    {{-- FCM Configuration --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-orange-500 to-amber-600">
            <h2 class="text-lg font-semibold text-white flex items-center">
                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                </svg>
                Firebase Cloud Messaging (FCM)
            </h2>
        </div>
        <div class="p-6">
            {{-- สถานะ FCM --}}
            <div class="mb-6 p-4 rounded-xl {{ $fcmServiceAccount ? 'bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800' : 'bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800' }}">
                <div class="flex items-center gap-3">
                    @if($fcmServiceAccount)
                        <div class="flex-shrink-0 w-10 h-10 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                            <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold text-emerald-700 dark:text-emerald-400">เชื่อมต่อ Firebase แล้ว</p>
                            <p class="text-sm text-emerald-600 dark:text-emerald-500 font-mono">{{ $fcmServiceAccount }}</p>
                        </div>
                    @else
                        <div class="flex-shrink-0 w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                            <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold text-amber-700 dark:text-amber-400">ยังไม่ได้ตั้งค่า Firebase</p>
                            <p class="text-sm text-amber-600 dark:text-amber-500">อัพโหลด Service Account JSON เพื่อเปิดใช้งาน Push Notification</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- FCM Settings Form - ใช้ Alpine.js + AJAX เพื่อป้องกัน session หมดอายุ --}}
            <div x-data="fcmSettingsForm()" x-cloak>
                <form @submit.prevent="submitForm" enctype="multipart/form-data">

                    {{-- FCM Enabled --}}
                    <div class="mb-5">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="hidden" name="fcm_enabled" value="0">
                            <input type="checkbox" name="fcm_enabled" value="1" {{ $fcmEnabled ? 'checked' : '' }}
                                class="w-5 h-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">เปิดใช้งาน FCM Push Notification</span>
                        </label>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 ml-8">ส่ง push notification ไปยังแอพ Android เมื่อมีบิลใหม่</p>
                    </div>

                    {{-- Firebase Project ID --}}
                    <div class="mb-5">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Firebase Project ID</label>
                        <input type="text" name="fcm_project_id" value="{{ old('fcm_project_id', $fcmProjectId) }}"
                            class="w-full px-4 py-3 bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm font-mono text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="your-project-id">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ดูได้จาก Firebase Console → Project Settings → General</p>
                    </div>

                    {{-- Service Account JSON --}}
                    <div class="mb-5">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Service Account JSON</label>
                        <input type="file" name="fcm_credentials" accept=".json"
                            class="w-full px-4 py-3 bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm text-gray-900 dark:text-white file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 dark:file:bg-blue-900/30 dark:file:text-blue-400 hover:file:bg-blue-100 dark:hover:file:bg-blue-900/50">
                        @if($fcmCredentialsPath)
                            <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-1">
                                ไฟล์ปัจจุบัน: {{ basename($fcmCredentialsPath) }}
                            </p>
                        @else
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ดาวน์โหลดจาก Firebase Console → Project Settings → Service accounts → Generate new private key</p>
                        @endif
                    </div>

                    {{-- Status Message --}}
                    <div x-show="message" x-transition
                        :class="messageType === 'success'
                            ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800'
                            : 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-800'"
                        class="mb-4 px-4 py-3 rounded-xl text-sm">
                        <span x-text="message"></span>
                    </div>

                    {{-- Buttons --}}
                    <div class="flex items-center gap-3">
                        <button type="submit" :disabled="submitting"
                            class="inline-flex items-center px-5 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white font-medium rounded-xl transition-colors">
                            <template x-if="!submitting">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </template>
                            <template x-if="submitting">
                                <svg class="w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            </template>
                            <span x-text="submitting ? 'กำลังบันทึก...' : 'บันทึก'"></span>
                        </button>
                    </div>
                </form>
            </div>

            <script>
            /**
             * FCM Settings Form - ใช้ AJAX + refresh CSRF token ก่อน submit
             * ป้องกัน session หมดอายุเมื่อเปิดหน้าค้างไว้นาน
             */
            function fcmSettingsForm() {
                return {
                    submitting: false,
                    message: '',
                    messageType: 'success',

                    async submitForm(e) {
                        this.submitting = true;
                        this.message = '';

                        try {
                            // ขอ CSRF token ใหม่ก่อน submit (ป้องกัน session หมดอายุ)
                            const tokenResponse = await fetch('{{ route("admin.smschecker.settings") }}', {
                                method: 'GET',
                                credentials: 'same-origin',
                                headers: { 'X-Requested-With': 'XMLHttpRequest' }
                            });

                            if (!tokenResponse.ok) {
                                throw new Error('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้ กรุณา refresh หน้าและลองใหม่');
                            }

                            // ดึง CSRF token ใหม่จาก response HTML
                            const html = await tokenResponse.text();
                            const tokenMatch = html.match(/name="_token"\s+value="([^"]+)"/);
                            const freshToken = tokenMatch ? tokenMatch[1] : null;

                            if (!freshToken) {
                                // Session หมดจริงๆ → redirect ไปหน้า login
                                throw new Error('Session หมดอายุ กรุณา login ใหม่');
                            }

                            // สร้าง FormData พร้อม CSRF token ใหม่
                            const form = e.target;
                            const formData = new FormData(form);
                            formData.set('_token', freshToken);

                            // ส่งข้อมูลไป server ผ่าน AJAX
                            const response = await fetch('{{ route("admin.smschecker.settings-fcm") }}', {
                                method: 'POST',
                                body: formData,
                                credentials: 'same-origin',
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json'
                                }
                            });

                            if (response.ok) {
                                const data = await response.json().catch(() => null);
                                this.message = data?.message || 'บันทึกการตั้งค่า FCM เรียบร้อยแล้ว';
                                this.messageType = (data?.success !== false) ? 'success' : 'error';
                                // Reload หน้าหลังจากแสดง message 1 วินาที เพื่ออัพเดท status
                                if (this.messageType === 'success') {
                                    setTimeout(() => window.location.reload(), 1000);
                                }
                            } else if (response.status === 419) {
                                // CSRF ยังไม่ถูกต้อง → reload หน้าใหม่
                                this.message = 'Session หมดอายุ กำลัง refresh หน้า...';
                                this.messageType = 'error';
                                setTimeout(() => window.location.reload(), 1500);
                            } else if (response.status === 422) {
                                const data = await response.json().catch(() => null);
                                this.message = data?.message || 'ไฟล์ JSON ไม่ถูกต้อง';
                                this.messageType = 'error';
                            } else if (response.redirected) {
                                // Server redirect (เช่น login page) → follow redirect
                                window.location.href = response.url;
                            } else {
                                throw new Error('เกิดข้อผิดพลาดในการบันทึก (HTTP ' + response.status + ')');
                            }

                        } catch (err) {
                            this.message = err.message || 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง';
                            this.messageType = 'error';
                        } finally {
                            this.submitting = false;
                        }
                    }
                };
            }
            </script>

            {{-- Test FCM Button (แยกฟอร์ม - ใช้ AJAX ป้องกัน session หมดอายุ) --}}
            @if($fcmServiceAccount)
            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700" x-data="{ testing: false, testMsg: '', testType: '' }">
                <button @click="testFcm($el)" :disabled="testing"
                    class="inline-flex items-center px-4 py-2 bg-amber-500 hover:bg-amber-600 disabled:bg-amber-300 text-white font-medium rounded-xl transition-colors">
                    <template x-if="!testing">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                    </template>
                    <template x-if="testing">
                        <svg class="w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </template>
                    <span x-text="testing ? 'กำลังทดสอบ...' : 'ทดสอบส่ง FCM'"></span>
                </button>
                <span class="text-xs text-gray-500 dark:text-gray-400 ml-3">ส่ง silent push ไปยังอุปกรณ์ที่ active ทั้งหมด</span>

                {{-- ผลลัพธ์ทดสอบ --}}
                <div x-show="testMsg" x-transition class="mt-3 px-4 py-3 rounded-xl text-sm"
                    :class="testType === 'success'
                        ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800'
                        : 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-800'">
                    <span x-text="testMsg"></span>
                </div>
            </div>

            <script>
            /**
             * ทดสอบส่ง FCM push notification ผ่าน AJAX
             * ป้องกัน session หมดอายุ โดยขอ CSRF token ใหม่ก่อนส่ง
             */
            async function testFcm(el) {
                const component = Alpine.$data(el.closest('[x-data]'));
                component.testing = true;
                component.testMsg = '';

                try {
                    // ขอ CSRF token ใหม่
                    const tokenRes = await fetch('{{ route("admin.smschecker.settings") }}', {
                        method: 'GET', credentials: 'same-origin',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const html = await tokenRes.text();
                    const match = html.match(/name="_token"\s+value="([^"]+)"/);
                    const token = match ? match[1] : null;

                    if (!token) {
                        throw new Error('Session หมดอายุ กรุณา login ใหม่');
                    }

                    const res = await fetch('{{ route("admin.smschecker.settings-fcm-test") }}', {
                        method: 'POST', credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: '_token=' + encodeURIComponent(token)
                    });

                    if (res.ok) {
                        const data = await res.json().catch(() => null);
                        component.testMsg = data?.message || 'ส่ง FCM test push สำเร็จ!';
                        component.testType = data?.success !== false ? 'success' : 'error';
                    } else if (res.status === 419) {
                        component.testMsg = 'Session หมดอายุ กำลัง refresh...';
                        component.testType = 'error';
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        throw new Error('เกิดข้อผิดพลาด (HTTP ' + res.status + ')');
                    }
                } catch (err) {
                    component.testMsg = err.message || 'เกิดข้อผิดพลาด';
                    component.testType = 'error';
                } finally {
                    component.testing = false;
                }
            }
            </script>
            @endif

            {{-- คู่มือ --}}
            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                <a href="https://console.firebase.google.com" target="_blank" class="inline-flex items-center text-sm text-blue-600 dark:text-blue-400 hover:underline">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                    </svg>
                    เปิด Firebase Console
                </a>
            </div>
        </div>
    </div>

    {{-- Current Settings --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">📋 การตั้งค่าปัจจุบัน</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">การตั้งค่าถูกจัดเก็บใน <code>config/smschecker.php</code></p>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">ระบบ</h4>
                    <p class="mt-1 text-lg font-semibold {{ $settings['enabled'] ? 'text-green-600' : 'text-red-600' }}">
                        {{ $settings['enabled'] ? '✅ เปิดใช้งาน' : '❌ ปิดใช้งาน' }}
                    </p>
                </div>

                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">อายุ Unique Amount</h4>
                    <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ $settings['unique_amount_expiry'] }} นาที</p>
                </div>

                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Sync Interval</h4>
                    <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ $settings['sync_interval'] }} วินาที</p>
                </div>

                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">โหมดอนุมัติเริ่มต้น</h4>
                    <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ ucfirst($settings['default_approval_mode']) }}</p>
                </div>

                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">อนุมัติอัตโนมัติ</h4>
                    <p class="mt-1 text-lg font-semibold {{ $settings['auto_confirm_matched'] ? 'text-green-600' : 'text-orange-600' }}">
                        {{ $settings['auto_confirm_matched'] ? '✅ เปิด' : '⚠️ ต้องอนุมัติเอง' }}
                    </p>
                </div>

                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Rate Limit</h4>
                    <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ $settings['rate_limit_per_minute'] }} ครั้ง/นาที</p>
                </div>
            </div>
        </div>
    </div>

    {{-- App Download URL --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-emerald-500 to-green-600">
            <h2 class="text-lg font-semibold text-white flex items-center">
                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                URL ดาวน์โหลดแอป SmsChecker
            </h2>
        </div>
        <div class="p-6" x-data="appDownloadForm()">
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                ตั้งค่า URL ดาวน์โหลดแอป SmsChecker สำหรับแอดมินและร้านค้า Enterprise
            </p>
            <form @submit.prevent="saveUrl">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">URL ดาวน์โหลดแอป</label>
                    <input type="url" name="smschecker_app_download_url" x-model="downloadUrl"
                        class="w-full px-4 py-3 bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm font-mono text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                        placeholder="https://github.com/xjanova/SmsChecker/releases/latest">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">URL ที่ร้านค้า Enterprise ใช้ดาวน์โหลดแอป (เว้นว่างเพื่อใช้ค่าเริ่มต้น GitHub)</p>
                </div>

                {{-- Status Message --}}
                <div x-show="message" x-transition
                    :class="messageType === 'success'
                        ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800'
                        : 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-800'"
                    class="mb-4 px-4 py-3 rounded-xl text-sm">
                    <span x-text="message"></span>
                </div>

                <button type="submit" :disabled="saving"
                    class="inline-flex items-center px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 disabled:bg-emerald-400 text-white font-medium rounded-xl transition-colors">
                    <template x-if="!saving">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </template>
                    <template x-if="saving">
                        <svg class="w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </template>
                    <span x-text="saving ? 'กำลังบันทึก...' : 'บันทึก URL'"></span>
                </button>
            </form>

            <script>
            function appDownloadForm() {
                return {
                    downloadUrl: '{{ $appDownloadUrl }}',
                    saving: false,
                    message: '',
                    messageType: 'success',

                    async saveUrl() {
                        this.saving = true;
                        this.message = '';

                        try {
                            // ขอ CSRF token ใหม่
                            const tokenRes = await fetch('{{ route("admin.smschecker.settings") }}', {
                                method: 'GET', credentials: 'same-origin',
                                headers: { 'X-Requested-With': 'XMLHttpRequest' }
                            });
                            const html = await tokenRes.text();
                            const match = html.match(/name="_token"\s+value="([^"]+)"/);
                            const token = match ? match[1] : null;

                            if (!token) throw new Error('Session หมดอายุ กรุณา login ใหม่');

                            const res = await fetch('{{ route("admin.smschecker.settings-download-url") }}', {
                                method: 'POST', credentials: 'same-origin',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json'
                                },
                                body: '_token=' + encodeURIComponent(token) + '&smschecker_app_download_url=' + encodeURIComponent(this.downloadUrl)
                            });

                            if (res.ok) {
                                const data = await res.json().catch(() => null);
                                this.message = data?.message || 'บันทึก URL เรียบร้อยแล้ว';
                                this.messageType = data?.success !== false ? 'success' : 'error';
                            } else if (res.status === 419) {
                                this.message = 'Session หมดอายุ กำลัง refresh...';
                                this.messageType = 'error';
                                setTimeout(() => window.location.reload(), 1500);
                            } else {
                                throw new Error('เกิดข้อผิดพลาด (HTTP ' + res.status + ')');
                            }
                        } catch (err) {
                            this.message = err.message || 'เกิดข้อผิดพลาด';
                            this.messageType = 'error';
                        } finally {
                            this.saving = false;
                        }
                    }
                };
            }
            </script>
        </div>
    </div>

    {{-- Supported Banks --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">🏦 ธนาคารที่รองรับ</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">แอพจะตรวจจับ SMS แจ้งเงินเข้าจากธนาคารเหล่านี้อัตโนมัติ</p>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                @foreach($supportedBanks as $code => $name)
                <div class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 dark:bg-gray-700/50">
                    <div class="w-10 h-10 rounded-full bg-white dark:bg-gray-600 flex items-center justify-center shadow text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">
                        {{ strtoupper(substr($code, 0, 3)) }}
                    </div>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $name }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
