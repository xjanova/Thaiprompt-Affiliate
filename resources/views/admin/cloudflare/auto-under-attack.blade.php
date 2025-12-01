@extends('layouts.admin-v3')

@section('title', 'Auto Under Attack Mode')

@section('content')
<div class="container-fluid px-4 py-6" x-data="autoUnderAttack()">
    <!-- Header -->
    <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-red-600 via-red-700 to-rose-800 dark:from-red-700 dark:via-red-800 dark:to-rose-900 p-8 shadow-2xl">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS1vcGFjaXR5PSIwLjEiIHN0cm9rZS13aWR0aD0iMSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNncmlkKSIvPjwvc3ZnPg==')] opacity-30"></div>
        <div class="relative flex items-center">
            <a href="{{ route('admin.cloudflare.index') }}" class="w-10 h-10 rounded-lg bg-white/20 flex items-center justify-center hover:bg-white/30 transition-colors mr-3">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div class="w-14 h-14 rounded-xl backdrop-blur-sm flex items-center justify-center bg-white/20 mr-4">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div>
                <h1 class="text-3xl font-bold text-white mb-1">Auto Under Attack Mode</h1>
                <p class="text-red-100">เปิด Under Attack Mode อัตโนมัติเมื่อตรวจพบการโจมตี</p>
            </div>
        </div>
    </div>

    @if(!$isConfigured)
    <div class="mb-6 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl">
        <div class="flex items-start">
            <svg class="w-5 h-5 text-yellow-500 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            <div>
                <p class="text-yellow-700 dark:text-yellow-300 font-medium">Cloudflare ยังไม่ได้ตั้งค่า</p>
                <p class="text-yellow-600 dark:text-yellow-400 text-sm mt-1">กรุณา <a href="{{ route('admin.cloudflare.settings') }}" class="underline font-medium">ตั้งค่า API Token และ Zone ID</a> ก่อนใช้งาน</p>
            </div>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column: Status & Controls -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Current Status Card -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-700 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">สถานะปัจจุบัน</h3>
                    <button @click="refreshStatus"
                            :disabled="refreshing"
                            class="px-3 py-1.5 text-sm bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 rounded-lg transition flex items-center">
                        <svg :class="{'animate-spin': refreshing}" class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        รีเฟรช
                    </button>
                </div>

                <!-- Status Indicator -->
                <div class="flex items-center p-4 rounded-xl" :class="status.is_under_attack ? 'bg-red-100 dark:bg-red-900/30' : 'bg-green-100 dark:bg-green-900/30'">
                    <div class="w-16 h-16 rounded-full flex items-center justify-center mr-4" :class="status.is_under_attack ? 'bg-red-500' : 'bg-green-500'">
                        <template x-if="status.is_under_attack">
                            <svg class="w-8 h-8 text-white animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </template>
                        <template x-if="!status.is_under_attack">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </template>
                    </div>
                    <div>
                        <h4 class="text-xl font-bold" :class="status.is_under_attack ? 'text-red-700 dark:text-red-300' : 'text-green-700 dark:text-green-300'" x-text="status.is_under_attack ? 'Under Attack Mode เปิดอยู่!' : 'ปกติ (ไม่ได้เปิด Under Attack Mode)'"></h4>
                        <p class="text-sm" :class="status.is_under_attack ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'">
                            <span>Security Level: <strong x-text="status.current_security_level || 'กำลังโหลด...'"></strong></span>
                        </p>
                        <template x-if="status.auto_enabled && status.auto_reason">
                            <p class="text-sm text-red-600 dark:text-red-400 mt-1">
                                เหตุผล: <span x-text="status.auto_reason"></span>
                            </p>
                        </template>
                    </div>
                </div>

                <!-- Manual Toggle -->
                <div class="mt-6 flex items-center justify-between p-4 bg-gray-50 dark:bg-slate-700/50 rounded-xl">
                    <div>
                        <h4 class="font-medium text-gray-900 dark:text-white">เปิด/ปิด Under Attack Mode ด้วยตนเอง</h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400">ควบคุมการเปิด Under Attack Mode ทันที</p>
                    </div>
                    <div class="flex space-x-2">
                        <button @click="toggleUnderAttack(true)"
                                :disabled="toggling || status.is_under_attack"
                                class="px-4 py-2 bg-red-600 hover:bg-red-700 disabled:bg-gray-400 text-white font-medium rounded-lg transition flex items-center">
                            <svg x-show="toggling" class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            เปิด
                        </button>
                        <button @click="toggleUnderAttack(false)"
                                :disabled="toggling || !status.is_under_attack"
                                class="px-4 py-2 bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white font-medium rounded-lg transition flex items-center">
                            <svg x-show="toggling" class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            ปิด
                        </button>
                    </div>
                </div>
            </div>

            <!-- Live Metrics Card -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-700 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">สถิติการโจมตี (ใน <span x-text="checkResult.time_window || 5"></span> นาทีที่ผ่านมา)</h3>
                    <button @click="testDetection"
                            :disabled="testing"
                            class="px-3 py-1.5 text-sm bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-800/50 rounded-lg transition flex items-center">
                        <svg :class="{'animate-spin': testing}" class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                        ทดสอบการตรวจจับ
                    </button>
                </div>

                <!-- Threat Score -->
                <div class="mb-6">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Threat Score</span>
                        <span class="text-sm font-bold" :class="checkResult.threat_score >= checkResult.threshold ? 'text-red-600' : 'text-green-600'" x-text="checkResult.threat_score + ' / ' + checkResult.threshold"></span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-slate-700 rounded-full h-4">
                        <div class="h-4 rounded-full transition-all duration-500"
                             :class="checkResult.threat_score >= checkResult.threshold ? 'bg-red-500' : 'bg-green-500'"
                             :style="'width: ' + Math.min((checkResult.threat_score / checkResult.threshold) * 100, 100) + '%'"></div>
                    </div>
                    <p class="mt-2 text-sm" :class="checkResult.should_activate ? 'text-red-600 dark:text-red-400 font-medium' : 'text-gray-500 dark:text-gray-400'" x-text="checkResult.reason"></p>
                </div>

                <!-- Metrics Grid -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="p-4 bg-gray-50 dark:bg-slate-700/50 rounded-xl text-center">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white" x-text="metrics.failed_logins"></div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Failed Logins</div>
                    </div>
                    <div class="p-4 bg-gray-50 dark:bg-slate-700/50 rounded-xl text-center">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white" x-text="metrics.rate_limit_exceeded"></div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Rate Limit Exceeded</div>
                    </div>
                    <div class="p-4 bg-gray-50 dark:bg-slate-700/50 rounded-xl text-center">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white" x-text="metrics.turnstile_failures"></div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Turnstile Failures</div>
                    </div>
                    <div class="p-4 bg-gray-50 dark:bg-slate-700/50 rounded-xl text-center">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white" x-text="metrics.unique_attack_ips"></div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Unique Attack IPs</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Settings -->
        <div class="space-y-6">
            <!-- Settings Card -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">การตั้งค่า</h3>

                <form @submit.prevent="saveSettings" class="space-y-5">
                    <!-- Enable Toggle -->
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-slate-700/50 rounded-xl">
                        <div>
                            <h4 class="font-medium text-gray-900 dark:text-white">เปิดใช้งาน Auto Mode</h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400">เปิด Under Attack อัตโนมัติเมื่อถูกโจมตี</p>
                        </div>
                        <button type="button"
                                @click="settings.enabled = !settings.enabled"
                                :class="settings.enabled ? 'bg-red-600' : 'bg-gray-300 dark:bg-slate-600'"
                                class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full transition-colors duration-200 ease-in-out focus:outline-none">
                            <span :class="settings.enabled ? 'translate-x-5' : 'translate-x-0'" class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200 ease-in-out mt-0.5 ml-0.5"></span>
                        </button>
                    </div>

                    <!-- Threshold -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Threat Score Threshold</label>
                        <input type="number" x-model="settings.threshold" min="10" max="1000"
                               class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">เปิด Under Attack เมื่อ score ถึงค่านี้</p>
                    </div>

                    <!-- Time Window -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Time Window (นาที)</label>
                        <input type="number" x-model="settings.time_window" min="1" max="60"
                               class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ตรวจสอบข้อมูลใน X นาทีที่ผ่านมา</p>
                    </div>

                    <!-- Auto Disable After -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ปิดอัตโนมัติหลัง (นาที)</label>
                        <input type="number" x-model="settings.auto_disable_after" min="5" max="180"
                               class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ปิด Under Attack หลังจากเปิดไป X นาที</p>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit"
                            :disabled="saving"
                            class="w-full px-4 py-3 bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 text-white font-medium rounded-lg shadow-lg hover:shadow-xl transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center">
                        <svg x-show="saving" class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span x-text="saving ? 'กำลังบันทึก...' : 'บันทึกการตั้งค่า'"></span>
                    </button>
                </form>

                <!-- Message -->
                <div x-show="message" x-cloak class="mt-4">
                    <div :class="messageSuccess ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 border-red-200 dark:border-red-800'"
                         class="p-3 rounded-lg border text-sm" x-text="message"></div>
                </div>
            </div>

            <!-- Info Card -->
            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-2xl shadow-lg border border-blue-100 dark:border-blue-800 p-6">
                <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-200 mb-4">วิธีการทำงาน</h3>
                <div class="space-y-3 text-sm text-blue-800 dark:text-blue-300">
                    <div class="flex items-start">
                        <span class="w-6 h-6 rounded-full bg-blue-100 dark:bg-blue-800 flex items-center justify-center text-xs font-bold mr-2 flex-shrink-0">1</span>
                        <p>ระบบตรวจสอบ Security Logs (Failed Login, Rate Limit, Turnstile) ทุกนาที</p>
                    </div>
                    <div class="flex items-start">
                        <span class="w-6 h-6 rounded-full bg-blue-100 dark:bg-blue-800 flex items-center justify-center text-xs font-bold mr-2 flex-shrink-0">2</span>
                        <p>คำนวณ Threat Score จากจำนวนการโจมตีใน Time Window</p>
                    </div>
                    <div class="flex items-start">
                        <span class="w-6 h-6 rounded-full bg-blue-100 dark:bg-blue-800 flex items-center justify-center text-xs font-bold mr-2 flex-shrink-0">3</span>
                        <p>ถ้า Score เกิน Threshold → เปิด Under Attack Mode อัตโนมัติ</p>
                    </div>
                    <div class="flex items-start">
                        <span class="w-6 h-6 rounded-full bg-blue-100 dark:bg-blue-800 flex items-center justify-center text-xs font-bold mr-2 flex-shrink-0">4</span>
                        <p>ปิด Under Attack Mode อัตโนมัติเมื่อหมดเวลาที่กำหนด</p>
                    </div>
                </div>

                <div class="mt-4 p-3 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg border border-yellow-200 dark:border-yellow-800">
                    <p class="text-xs text-yellow-700 dark:text-yellow-300">
                        <strong>หมายเหตุ:</strong> ต้องตั้งค่า Scheduler (cron job) เพื่อให้ระบบตรวจสอบอัตโนมัติ<br>
                        <code class="bg-yellow-200 dark:bg-yellow-800 px-1 rounded">* * * * * php artisan cloudflare:check-attack</code>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

@php
// เตรียมค่าเริ่มต้นสำหรับ metrics เพื่อหลีกเลี่ยงปัญหา @json directive
$metricsData = $checkResult['metrics'] ?? [
    'failed_logins' => 0,
    'rate_limit_exceeded' => 0,
    'turnstile_failures' => 0,
    'unique_attack_ips' => 0
];
@endphp

@push('scripts')
<script>
function autoUnderAttack() {
    return {
        settings: @json($settings),
        status: @json($status),
        checkResult: @json($checkResult),
        metrics: @json($metricsData),
        saving: false,
        refreshing: false,
        testing: false,
        toggling: false,
        message: '',
        messageSuccess: false,

        async saveSettings() {
            this.saving = true;
            this.message = '';

            try {
                const response = await fetch('{{ route("admin.cloudflare.auto-under-attack.settings") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.settings),
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.message = data.message;
                    this.messageSuccess = true;
                } else {
                    this.message = data.message || 'เกิดข้อผิดพลาด';
                    this.messageSuccess = false;
                }
            } catch (error) {
                this.message = 'เกิดข้อผิดพลาด: ' + error.message;
                this.messageSuccess = false;
            } finally {
                this.saving = false;
            }
        },

        async refreshStatus() {
            this.refreshing = true;

            try {
                const response = await fetch('{{ route("admin.cloudflare.auto-under-attack.status") }}');
                const data = await response.json();

                if (data.success) {
                    this.status = data.status;
                    this.checkResult = data.check_result;
                    this.metrics = data.check_result.metrics || this.metrics;
                }
            } catch (error) {
                console.error('Error refreshing status:', error);
            } finally {
                this.refreshing = false;
            }
        },

        async testDetection() {
            this.testing = true;

            try {
                const response = await fetch('{{ route("admin.cloudflare.auto-under-attack.test") }}');
                const data = await response.json();

                if (data.success) {
                    this.checkResult.threat_score = data.threat_score;
                    this.checkResult.threshold = data.threshold;
                    this.checkResult.should_activate = data.would_activate;
                    this.checkResult.reason = data.reason;
                    this.metrics = data.metrics;

                    if (data.would_activate) {
                        this.message = 'ตรวจพบการโจมตี! ถ้าเปิด Auto Mode จะเปิด Under Attack Mode';
                        this.messageSuccess = false;
                    } else {
                        this.message = 'ยังไม่ถึง threshold การโจมตี';
                        this.messageSuccess = true;
                    }
                }
            } catch (error) {
                console.error('Error testing detection:', error);
            } finally {
                this.testing = false;
            }
        },

        async toggleUnderAttack(enable) {
            this.toggling = true;
            this.message = '';

            try {
                const response = await fetch('{{ route("admin.cloudflare.auto-under-attack.toggle") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ enable: enable }),
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.message = data.message;
                    this.messageSuccess = true;
                    // Refresh status
                    await this.refreshStatus();
                } else {
                    this.message = data.message || 'เกิดข้อผิดพลาด';
                    this.messageSuccess = false;
                }
            } catch (error) {
                this.message = 'เกิดข้อผิดพลาด: ' + error.message;
                this.messageSuccess = false;
            } finally {
                this.toggling = false;
            }
        },

        init() {
            // Refresh status every 30 seconds
            setInterval(() => {
                this.refreshStatus();
            }, 30000);
        }
    };
}
</script>
@endpush
@endsection
