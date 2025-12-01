@extends('layouts.admin-v3')

@section('title', 'Cloudflare Settings')

@section('content')
<div class="container-fluid px-4 py-6" x-data="cloudflareSettings()">
    <!-- Header -->
    <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-orange-500 via-orange-600 to-amber-600 dark:from-orange-600 dark:via-orange-700 dark:to-amber-700 p-8 shadow-2xl">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS1vcGFjaXR5PSIwLjEiIHN0cm9rZS13aWR0aD0iMSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNncmlkKSIvPjwvc3ZnPg==')] opacity-30"></div>
        <div class="relative flex items-center">
            <a href="{{ route('admin.cloudflare.index') }}" class="w-10 h-10 rounded-lg bg-white/20 flex items-center justify-center hover:bg-white/30 transition-colors mr-3">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div class="w-14 h-14 rounded-xl backdrop-blur-sm flex items-center justify-center bg-white/20 mr-4">
                <!-- Cloudflare Logo -->
                <svg class="w-10 h-10" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M42.8 40.3l.5-1.7c.5-1.8.3-3.4-.6-4.5-.8-1-2.1-1.5-3.5-1.6l-16.6-.2c-.2 0-.4-.1-.5-.3-.1-.2-.1-.4 0-.5.1-.3.4-.5.7-.5l16.8.2c3.3-.1 6.9-2.7 8-5.9l1.4-4c.1-.2.1-.4 0-.5-1.2-5.5-6.2-9.6-12.1-9.6-5.5 0-10.2 3.5-11.9 8.5-.9-.7-2.1-1.1-3.4-1-2.3.2-4.1 2-4.4 4.3-.1.5-.1.9 0 1.4-4.2.4-7.5 4-7.5 8.4 0 .8.1 1.6.3 2.4.1.3.3.5.6.5h31.5c.3 0 .6-.2.7-.4z" fill="white"/>
                    <path d="M48.4 24.4c-.2 0-.4 0-.6.1-.2.1-.3.2-.4.4l-1.1 3.8c-.5 1.8-.3 3.4.6 4.5.8 1 2.1 1.5 3.5 1.6l3.5.2c.2 0 .4.1.5.3.1.2.1.4 0 .5-.1.3-.4.5-.7.5l-3.7-.2c-3.3.1-6.9 2.7-8 5.9l-.4 1.3c-.1.3.1.6.4.6h14.4c.3 0 .5-.2.6-.4.5-1.5.7-3.1.7-4.7 0-7.7-6.2-14-13.8-14.4z" fill="#F6821F"/>
                </svg>
            </div>
            <div>
                <h1 class="text-3xl font-bold text-white mb-1">ตั้งค่า Cloudflare API</h1>
                <p class="text-orange-100">กรอก Zone ID และ API Token เพื่อเชื่อมต่อกับ Cloudflare</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Settings Form -->
        <div class="space-y-6">
            <!-- Form Card -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-700 p-6">
                <div class="flex items-center mb-6">
                    <div class="w-10 h-10 rounded-lg bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center mr-3">
                        <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">API Configuration</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">กรอกข้อมูลการเชื่อมต่อ Cloudflare</p>
                    </div>
                </div>

                <form @submit.prevent="saveSettings" class="space-y-5">
                    <!-- Zone ID -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Zone ID <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               x-model="zoneId"
                               placeholder="xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                               maxlength="32"
                               class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-3 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition font-mono text-sm"
                               required>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Zone ID มี 32 ตัวอักษร (ดูวิธีหาได้จากคำแนะนำด้านขวา)
                        </p>
                    </div>

                    <!-- API Token -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            API Token <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input :type="showToken ? 'text' : 'password'"
                                   x-model="apiToken"
                                   placeholder="Enter your Cloudflare API Token"
                                   class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-3 pr-12 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition font-mono text-sm"
                                   required>
                            <button type="button"
                                    @click="showToken = !showToken"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                <svg x-show="!showToken" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg x-show="showToken" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                </svg>
                            </button>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            API Token สำหรับเข้าถึง Cloudflare API (ดูวิธีสร้างจากคำแนะนำด้านขวา)
                        </p>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-4">
                        <button type="submit"
                                :disabled="saving"
                                class="w-full px-6 py-3 bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white font-medium rounded-lg shadow-lg hover:shadow-xl transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center">
                            <template x-if="saving">
                                <svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </template>
                            <span x-text="saving ? 'กำลังบันทึก...' : 'บันทึกและทดสอบการเชื่อมต่อ'"></span>
                        </button>
                    </div>
                </form>

                <!-- Result Messages -->
                <div x-show="message" x-cloak class="mt-4">
                    <div :class="success ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800'"
                         class="rounded-lg border p-4">
                        <div class="flex items-start">
                            <template x-if="success">
                                <svg class="w-5 h-5 text-green-500 dark:text-green-400 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </template>
                            <template x-if="!success">
                                <svg class="w-5 h-5 text-red-500 dark:text-red-400 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                            </template>
                            <div>
                                <p :class="success ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200'" class="font-medium" x-text="message"></p>
                                <template x-if="connectionDetails">
                                    <div class="mt-2 text-sm" :class="success ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300'">
                                        <template x-if="connectionDetails.zone_name">
                                            <p>Zone: <span class="font-medium" x-text="connectionDetails.zone_name"></span></p>
                                        </template>
                                        <template x-if="connectionDetails.plan">
                                            <p>Plan: <span class="font-medium" x-text="connectionDetails.plan"></span></p>
                                        </template>
                                        <template x-if="connectionDetails.token_status">
                                            <p>Token Status: <span class="font-medium" x-text="connectionDetails.token_status"></span></p>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Current Status -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">สถานะปัจจุบัน</h3>

                <div class="space-y-4">
                    <!-- Database Settings -->
                    <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-slate-700/50">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                            </svg>
                            <span class="text-sm text-gray-700 dark:text-gray-300">Database Settings</span>
                        </div>
                        @if($storedZoneId && $storedApiToken)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                ตั้งค่าแล้ว
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                ยังไม่ได้ตั้งค่า
                            </span>
                        @endif
                    </div>

                    <!-- Config Settings -->
                    <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-slate-700/50">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span class="text-sm text-gray-700 dark:text-gray-300">Config (.env) Settings</span>
                        </div>
                        @if($configZoneId && $hasConfigApiToken)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                ตั้งค่าแล้ว
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                ยังไม่ได้ตั้งค่า
                            </span>
                        @endif
                    </div>

                    <!-- Connection Status -->
                    <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-slate-700/50">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            <span class="text-sm text-gray-700 dark:text-gray-300">การเชื่อมต่อ</span>
                        </div>
                        @if($isConfigured)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                พร้อมใช้งาน
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                ยังไม่พร้อม
                            </span>
                        @endif
                    </div>
                </div>

                <div class="mt-4 p-3 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800">
                    <p class="text-xs text-blue-700 dark:text-blue-300">
                        <strong>หมายเหตุ:</strong> ระบบจะดึง settings จาก Database ก่อน ถ้าไม่มีจะใช้จาก .env
                    </p>
                </div>
            </div>
        </div>

        <!-- Instructions -->
        <div class="space-y-6">
            <!-- Step 1: Get Zone ID -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-700 p-6">
                <div class="flex items-center mb-4">
                    <div class="w-8 h-8 rounded-full bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center mr-3 text-orange-600 dark:text-orange-400 font-bold">
                        1
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">วิธีหา Zone ID</h3>
                </div>

                <div class="space-y-3 text-sm text-gray-600 dark:text-gray-400">
                    <div class="flex items-start">
                        <span class="w-6 h-6 rounded-full bg-gray-100 dark:bg-slate-700 flex items-center justify-center text-xs font-medium mr-2 flex-shrink-0">1</span>
                        <p>เปิด <a href="https://dash.cloudflare.com" target="_blank" class="text-orange-600 dark:text-orange-400 hover:underline font-medium">Cloudflare Dashboard</a></p>
                    </div>
                    <div class="flex items-start">
                        <span class="w-6 h-6 rounded-full bg-gray-100 dark:bg-slate-700 flex items-center justify-center text-xs font-medium mr-2 flex-shrink-0">2</span>
                        <p>เลือก Domain/Website ที่ต้องการเชื่อมต่อ</p>
                    </div>
                    <div class="flex items-start">
                        <span class="w-6 h-6 rounded-full bg-gray-100 dark:bg-slate-700 flex items-center justify-center text-xs font-medium mr-2 flex-shrink-0">3</span>
                        <p>ดูที่ <strong>sidebar ขวามือ</strong> ใต้หัวข้อ "API"</p>
                    </div>
                    <div class="flex items-start">
                        <span class="w-6 h-6 rounded-full bg-gray-100 dark:bg-slate-700 flex items-center justify-center text-xs font-medium mr-2 flex-shrink-0">4</span>
                        <p>คัดลอก <strong>Zone ID</strong> (32 ตัวอักษร)</p>
                    </div>
                </div>

                <!-- Screenshot placeholder -->
                <div class="mt-4 rounded-lg bg-gradient-to-br from-gray-100 to-gray-200 dark:from-slate-700 dark:to-slate-600 p-4 border-2 border-dashed border-gray-300 dark:border-slate-500">
                    <div class="flex items-center justify-center">
                        <div class="text-center">
                            <svg class="w-12 h-12 mx-auto text-gray-400 dark:text-gray-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Zone ID อยู่ในส่วน Overview > API</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 2: Create API Token -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-700 p-6">
                <div class="flex items-center mb-4">
                    <div class="w-8 h-8 rounded-full bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center mr-3 text-orange-600 dark:text-orange-400 font-bold">
                        2
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">วิธีสร้าง API Token</h3>
                </div>

                <div class="space-y-3 text-sm text-gray-600 dark:text-gray-400">
                    <div class="flex items-start">
                        <span class="w-6 h-6 rounded-full bg-gray-100 dark:bg-slate-700 flex items-center justify-center text-xs font-medium mr-2 flex-shrink-0">1</span>
                        <p>ไปที่ <a href="https://dash.cloudflare.com/profile/api-tokens" target="_blank" class="text-orange-600 dark:text-orange-400 hover:underline font-medium">My Profile → API Tokens</a></p>
                    </div>
                    <div class="flex items-start">
                        <span class="w-6 h-6 rounded-full bg-gray-100 dark:bg-slate-700 flex items-center justify-center text-xs font-medium mr-2 flex-shrink-0">2</span>
                        <p>คลิก <strong>"Create Token"</strong></p>
                    </div>
                    <div class="flex items-start">
                        <span class="w-6 h-6 rounded-full bg-gray-100 dark:bg-slate-700 flex items-center justify-center text-xs font-medium mr-2 flex-shrink-0">3</span>
                        <p>เลือก <strong>"Create Custom Token"</strong> (หรือใช้ template "Edit zone DNS")</p>
                    </div>
                    <div class="flex items-start">
                        <span class="w-6 h-6 rounded-full bg-gray-100 dark:bg-slate-700 flex items-center justify-center text-xs font-medium mr-2 flex-shrink-0">4</span>
                        <p>ตั้งชื่อ Token (เช่น "TP-Affiliate Integration")</p>
                    </div>
                    <div class="flex items-start">
                        <span class="w-6 h-6 rounded-full bg-gray-100 dark:bg-slate-700 flex items-center justify-center text-xs font-medium mr-2 flex-shrink-0">5</span>
                        <div>
                            <p class="mb-2">เพิ่ม <strong>Permissions</strong>:</p>
                        </div>
                    </div>
                </div>

                <!-- Permissions List -->
                <div class="mt-3 ml-8 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800">
                    <h4 class="text-sm font-semibold text-amber-800 dark:text-amber-200 mb-3">Permissions ที่จำเป็น:</h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            <span class="text-gray-700 dark:text-gray-300"><strong>Zone → Zone</strong> → Read</span>
                        </div>
                        <div class="flex items-center">
                            <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            <span class="text-gray-700 dark:text-gray-300"><strong>Zone → Zone Settings</strong> → Edit</span>
                        </div>
                        <div class="flex items-center">
                            <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            <span class="text-gray-700 dark:text-gray-300"><strong>Zone → Cache Purge</strong> → Purge</span>
                        </div>
                        <div class="flex items-center">
                            <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            <span class="text-gray-700 dark:text-gray-300"><strong>Zone → DNS</strong> → Edit</span>
                        </div>
                        <div class="flex items-center">
                            <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            <span class="text-gray-700 dark:text-gray-300"><strong>Zone → Analytics</strong> → Read</span>
                        </div>
                    </div>
                </div>

                <div class="mt-3 ml-8 space-y-3 text-sm text-gray-600 dark:text-gray-400">
                    <div class="flex items-start">
                        <span class="w-6 h-6 rounded-full bg-gray-100 dark:bg-slate-700 flex items-center justify-center text-xs font-medium mr-2 flex-shrink-0">6</span>
                        <p><strong>Zone Resources:</strong> เลือก "Include → Specific zone" → เลือก domain ของคุณ</p>
                    </div>
                    <div class="flex items-start">
                        <span class="w-6 h-6 rounded-full bg-gray-100 dark:bg-slate-700 flex items-center justify-center text-xs font-medium mr-2 flex-shrink-0">7</span>
                        <p>คลิก <strong>"Continue to summary"</strong> → <strong>"Create Token"</strong></p>
                    </div>
                    <div class="flex items-start">
                        <span class="w-6 h-6 rounded-full bg-gray-100 dark:bg-slate-700 flex items-center justify-center text-xs font-medium mr-2 flex-shrink-0">8</span>
                        <p>คัดลอก Token ที่แสดง (จะแสดงครั้งเดียวเท่านั้น!)</p>
                    </div>
                </div>

                <!-- Warning -->
                <div class="mt-4 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-red-500 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <div class="text-sm text-red-700 dark:text-red-300">
                            <p class="font-semibold">สำคัญมาก!</p>
                            <p>API Token จะแสดงครั้งเดียวเท่านั้น! คัดลอกเก็บไว้ก่อนปิดหน้าต่าง</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-gradient-to-br from-slate-50 to-gray-100 dark:from-slate-800 dark:to-slate-700 rounded-2xl shadow-lg border border-gray-200 dark:border-slate-600 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Quick Links</h3>
                <div class="grid grid-cols-2 gap-3">
                    <a href="https://dash.cloudflare.com" target="_blank"
                       class="flex items-center p-3 bg-white dark:bg-slate-700 rounded-lg border border-gray-200 dark:border-slate-600 hover:shadow-md transition">
                        <svg class="w-5 h-5 text-orange-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        <span class="text-sm text-gray-700 dark:text-gray-300">Dashboard</span>
                    </a>
                    <a href="https://dash.cloudflare.com/profile/api-tokens" target="_blank"
                       class="flex items-center p-3 bg-white dark:bg-slate-700 rounded-lg border border-gray-200 dark:border-slate-600 hover:shadow-md transition">
                        <svg class="w-5 h-5 text-orange-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                        </svg>
                        <span class="text-sm text-gray-700 dark:text-gray-300">API Tokens</span>
                    </a>
                    <a href="https://developers.cloudflare.com/api" target="_blank"
                       class="flex items-center p-3 bg-white dark:bg-slate-700 rounded-lg border border-gray-200 dark:border-slate-600 hover:shadow-md transition">
                        <svg class="w-5 h-5 text-orange-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                        <span class="text-sm text-gray-700 dark:text-gray-300">API Docs</span>
                    </a>
                    <a href="{{ route('admin.cloudflare.optimization') }}"
                       class="flex items-center p-3 bg-white dark:bg-slate-700 rounded-lg border border-gray-200 dark:border-slate-600 hover:shadow-md transition">
                        <svg class="w-5 h-5 text-orange-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        <span class="text-sm text-gray-700 dark:text-gray-300">Optimization</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function cloudflareSettings() {
    return {
        zoneId: '{{ $storedZoneId }}',
        apiToken: '{{ $storedApiToken ? '' : '' }}', // ไม่แสดง token เดิมเพื่อความปลอดภัย
        showToken: false,
        saving: false,
        message: '',
        success: false,
        connectionDetails: null,

        async saveSettings() {
            if (this.saving) return;

            // Validate
            if (!this.zoneId || this.zoneId.length !== 32) {
                this.message = 'Zone ID ต้องมี 32 ตัวอักษร';
                this.success = false;
                return;
            }

            if (!this.apiToken || this.apiToken.length < 40) {
                this.message = 'API Token สั้นเกินไป';
                this.success = false;
                return;
            }

            this.saving = true;
            this.message = '';
            this.connectionDetails = null;

            try {
                const response = await fetch('{{ route('admin.cloudflare.settings.save') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        zone_id: this.zoneId,
                        api_token: this.apiToken,
                    }),
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.success = true;
                    this.message = data.message;

                    // ถ้ามีผลการทดสอบการเชื่อมต่อ
                    if (data.connection_test) {
                        if (data.connection_test.success) {
                            this.connectionDetails = data.connection_test.details;
                            this.message = 'บันทึกและทดสอบการเชื่อมต่อสำเร็จ!';
                        } else {
                            this.success = false;
                            this.message = 'บันทึกสำเร็จ แต่ทดสอบการเชื่อมต่อไม่ผ่าน: ' + data.connection_test.message;
                        }
                    }

                    // รีโหลดหน้าหลังจาก 2 วินาที ถ้าสำเร็จ
                    if (this.success) {
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    }
                } else {
                    this.success = false;
                    this.message = data.message || 'เกิดข้อผิดพลาดในการบันทึก';
                }
            } catch (error) {
                this.success = false;
                this.message = 'เกิดข้อผิดพลาด: ' + error.message;
            } finally {
                this.saving = false;
            }
        }
    };
}
</script>
@endpush
@endsection
