@extends('layouts.admin')

@section('title', 'ตั้งค่าระบบดูดวง Facebook')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="fortuneSettings()">
    {{-- Header --}}
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
            🔮 ตั้งค่าระบบดูดวง Facebook Messenger
        </h1>
        <p class="text-gray-600 dark:text-gray-400">
            จัดการการตั้งค่า Facebook App, AI Provider และการทำงานของระบบดูดวง
        </p>
    </div>

    {{-- Quick Navigation Links --}}
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-3 mb-6">
        <a href="{{ route('admin.fortune.dashboard') }}"
           class="flex items-center gap-2 px-4 py-3 bg-gradient-to-r from-blue-500 to-indigo-500 text-white rounded-xl shadow hover:shadow-lg transition transform hover:-translate-y-0.5">
            <span class="text-xl">📊</span>
            <span class="text-sm font-semibold">Dashboard</span>
        </a>
        <a href="{{ route('admin.fortune.playground') }}"
           class="flex items-center gap-2 px-4 py-3 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-xl shadow hover:shadow-lg transition transform hover:-translate-y-0.5">
            <span class="text-xl">🎮</span>
            <span class="text-sm font-semibold">AI Playground</span>
        </a>
        <a href="{{ route('admin.fortune.astrology.index') }}"
           class="flex items-center gap-2 px-4 py-3 bg-gradient-to-r from-yellow-500 to-orange-500 text-white rounded-xl shadow hover:shadow-lg transition transform hover:-translate-y-0.5">
            <span class="text-xl">✨</span>
            <span class="text-sm font-semibold">โหราศาสตร์</span>
        </a>
        <a href="{{ route('admin.fortune.channels.index') }}"
           class="flex items-center gap-2 px-4 py-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm hover:shadow transition">
            <span class="text-xl">📡</span>
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">ช่องทาง</span>
        </a>
        <a href="{{ route('admin.fortune.categories.index') }}"
           class="flex items-center gap-2 px-4 py-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm hover:shadow transition">
            <span class="text-xl">📂</span>
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">หมวดหมู่</span>
        </a>
        <a href="{{ route('admin.fortune.readings.index') }}"
           class="flex items-center gap-2 px-4 py-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm hover:shadow transition">
            <span class="text-xl">📊</span>
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">ประวัติ</span>
        </a>
        <a href="{{ route('admin.fortune.response-templates.index') }}"
           class="flex items-center gap-2 px-4 py-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm hover:shadow transition">
            <span class="text-xl">📝</span>
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">เทมเพลต</span>
        </a>
        <a href="{{ route('admin.fortune.billing.index') }}"
           class="flex items-center gap-2 px-4 py-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm hover:shadow transition">
            <span class="text-xl">💳</span>
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">การเงิน</span>
        </a>
    </div>

    <form action="{{ route('admin.fortune.settings.update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        {{-- Status Card --}}
        <div class="bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl shadow-lg p-6 mb-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-bold mb-2">สถานะระบบ</h3>
                    <p class="text-blue-100">
                        @if($settings->is_enabled)
                            ✅ เปิดใช้งาน - พร้อมรับคำขอดูดวง
                        @else
                            ⛔ ปิดใช้งาน - ระบบไม่ตอบสนองคำขอ
                        @endif
                    </p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="is_enabled" value="1" 
                           {{ $settings->is_enabled ? 'checked' : '' }}
                           class="sr-only peer">
                    <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all dark:border-gray-600 peer-checked:bg-green-500"></div>
                </label>
            </div>
        </div>

        {{-- 🔍 AI Diagnostic Panel --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6" x-data="aiDiagnostic()">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                    🔍 ตรวจเช็คระบบ AI
                </h3>
                <button type="button" @click="runDiagnose()"
                        :disabled="loading"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-400 text-white rounded-lg transition flex items-center gap-2">
                    <span x-show="loading" class="animate-spin">⏳</span>
                    <span x-show="!loading">🔍</span>
                    <span x-text="loading ? 'กำลังตรวจสอบ...' : 'ตรวจเช็คทั้งหมด'"></span>
                </button>
            </div>

            {{-- สถานะรวม --}}
            <div x-show="result" x-transition class="mb-4">
                <div class="p-4 rounded-lg border-2"
                     :class="{
                        'bg-green-50 dark:bg-green-900/20 border-green-300 dark:border-green-700': result?.overall === 'ok',
                        'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-300 dark:border-yellow-700': result?.overall === 'warning',
                        'bg-red-50 dark:bg-red-900/20 border-red-300 dark:border-red-700': result?.overall === 'error'
                     }">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="text-2xl" x-text="result?.overall === 'ok' ? '✅' : (result?.overall === 'warning' ? '⚠️' : '❌')"></span>
                        <span class="text-lg font-bold"
                              :class="{
                                'text-green-800 dark:text-green-200': result?.overall === 'ok',
                                'text-yellow-800 dark:text-yellow-200': result?.overall === 'warning',
                                'text-red-800 dark:text-red-200': result?.overall === 'error'
                              }"
                              x-text="result?.overall === 'ok' ? 'ระบบพร้อมใช้งาน' : (result?.overall === 'warning' ? 'มีคำเตือน' : 'มีปัญหา - ต้องแก้ไข')">
                        </span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400" x-text="'ตรวจเมื่อ: ' + (result?.timestamp || '')"></p>
                </div>
            </div>

            {{-- รายการตรวจเช็ค --}}
            <div x-show="result?.checks" x-transition class="space-y-3">
                <template x-for="(check, key) in result?.checks" :key="key">
                    <div class="flex items-start gap-3 p-3 rounded-lg"
                         :class="{
                            'bg-green-50 dark:bg-green-900/10': check.status === 'ok',
                            'bg-yellow-50 dark:bg-yellow-900/10': check.status === 'warning',
                            'bg-red-50 dark:bg-red-900/10': check.status === 'error'
                         }">
                        <span class="text-lg flex-shrink-0 mt-0.5"
                              x-text="check.status === 'ok' ? '✅' : (check.status === 'warning' ? '⚠️' : '❌')"></span>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="font-semibold text-sm text-gray-900 dark:text-white" x-text="check.label"></span>
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium"
                                      :class="{
                                        'bg-green-200 dark:bg-green-800 text-green-800 dark:text-green-200': check.status === 'ok',
                                        'bg-yellow-200 dark:bg-yellow-800 text-yellow-800 dark:text-yellow-200': check.status === 'warning',
                                        'bg-red-200 dark:bg-red-800 text-red-800 dark:text-red-200': check.status === 'error'
                                      }"
                                      x-text="check.status === 'ok' ? 'ผ่าน' : (check.status === 'warning' ? 'คำเตือน' : 'ไม่ผ่าน')">
                                </span>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1" x-text="check.message"></p>

                            {{-- แสดงคำแนะนำการแก้ไข --}}
                            <div x-show="check.fix" class="mt-2 p-2 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded text-xs text-amber-800 dark:text-amber-200">
                                <span class="font-semibold">วิธีแก้ไข:</span>
                                <span x-text="check.fix"></span>
                            </div>

                            {{-- ปุ่มแก้ไขฐานข้อมูล --}}
                            <div x-show="check.has_pending_migrations" class="mt-2">
                                <button type="button" @click="fixDatabase()"
                                        :disabled="fixingDb"
                                        class="px-3 py-1.5 bg-orange-600 hover:bg-orange-700 disabled:bg-gray-400 text-white text-xs rounded-lg transition flex items-center gap-1.5">
                                    <span x-show="fixingDb" class="animate-spin">⏳</span>
                                    <span x-show="!fixingDb">🔧</span>
                                    <span x-text="fixingDb ? 'กำลังแก้ไข...' : 'แก้ไขฐานข้อมูล (รัน Migration)'"></span>
                                </button>
                            </div>

                            {{-- แสดง preview ถ้ามี (ตัวอย่างคำตอบ AI) --}}
                            <div x-show="check.preview" class="mt-2 p-2 bg-gray-100 dark:bg-gray-700 rounded text-xs text-gray-700 dark:text-gray-300 max-h-20 overflow-y-auto">
                                <span class="font-medium">ตัวอย่างคำตอบ:</span>
                                <span x-text="check.preview"></span>
                            </div>

                            {{-- แสดง details ถ้ามี --}}
                            <div x-show="check.details && Object.keys(check.details).length > 0"
                                 class="mt-2">
                                <button type="button" @click="check._showDetails = !check._showDetails"
                                        class="text-xs text-blue-600 dark:text-blue-400 hover:underline">
                                    <span x-text="check._showDetails ? '🔼 ซ่อนรายละเอียด' : '🔽 ดูรายละเอียด'"></span>
                                </button>
                                <div x-show="check._showDetails" x-transition
                                     class="mt-1 p-2 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-gray-700 dark:text-gray-300">
                                    <template x-for="(val, dKey) in check.details" :key="dKey">
                                        <div class="flex gap-2">
                                            <span class="text-gray-500" x-text="dKey + ':'"></span>
                                            <span x-text="typeof val === 'object' ? JSON.stringify(val) : String(val)"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            {{-- ผลลัพธ์การแก้ไข DB --}}
            <div x-show="dbFixResult" x-transition class="mt-4 p-3 rounded-lg border"
                 :class="dbFixResult?.success ? 'bg-green-50 dark:bg-green-900/20 border-green-300 dark:border-green-700' : 'bg-red-50 dark:bg-red-900/20 border-red-300 dark:border-red-700'">
                <div class="flex items-center gap-2 mb-1">
                    <span x-text="dbFixResult?.success ? '✅' : '❌'"></span>
                    <span class="font-semibold text-sm" x-text="dbFixResult?.message"></span>
                </div>
                <div x-show="dbFixResult?.output" class="mt-1 p-2 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-gray-700 dark:text-gray-300 max-h-32 overflow-y-auto whitespace-pre-wrap" x-text="dbFixResult?.output"></div>
            </div>

            {{-- ข้อความเริ่มต้น --}}
            <div x-show="!result && !loading" class="text-center py-8 text-gray-400 dark:text-gray-500">
                <span class="text-4xl mb-2 block">🔍</span>
                <p>กดปุ่ม "ตรวจเช็คทั้งหมด" เพื่อตรวจสอบสถานะการเชื่อมต่อ AI, Facebook และฐานข้อมูล</p>
            </div>
        </div>

        {{-- Facebook Settings --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                📘 การตั้งค่า Facebook
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        App ID
                    </label>
                    <input type="text" name="facebook_app_id" 
                           value="{{ old('facebook_app_id', $settings->facebook_app_id) }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                           placeholder="1234567890123456">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        App Secret
                    </label>
                    <input type="password" name="facebook_app_secret" 
                           value="{{ old('facebook_app_secret', $settings->facebook_app_secret) }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                           placeholder="••••••••••••••••">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Page ID
                    </label>
                    <input type="text" name="facebook_page_id" 
                           value="{{ old('facebook_page_id', $settings->facebook_page_id) }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                           placeholder="1234567890">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Verify Token
                    </label>
                    <input type="text" name="facebook_verify_token" 
                           value="{{ old('facebook_verify_token', $settings->facebook_verify_token) }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                           placeholder="your_verify_token_here">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Page Access Token
                    </label>
                    <textarea name="facebook_page_token" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                              placeholder="EAAxx...">{{ old('facebook_page_token', $settings->facebook_page_token) }}</textarea>
                </div>
            </div>

            <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <p class="text-sm text-blue-800 dark:text-blue-200">
                    💡 <strong>Webhook URL:</strong> <code class="bg-white dark:bg-gray-800 px-2 py-1 rounded">{{ url('/webhook/facebook') }}</code>
                </p>
            </div>
        </div>

        {{-- AI Settings --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6" x-data="{ useGlobal: {{ old('use_global_ai_settings', $settings->use_global_ai_settings ?? true) ? 'true' : 'false' }} }">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                    🤖 การตั้งค่า AI Provider
                </h3>
                <button type="button" @click="testAI()"
                        class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
                    🧪 ทดสอบการเชื่อมต่อ
                </button>
            </div>

            {{-- Toggle: Use Global AI Settings --}}
            <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox"
                                   name="use_global_ai_settings"
                                   value="1"
                                   x-model="useGlobal"
                                   {{ old('use_global_ai_settings', $settings->use_global_ai_settings ?? true) ? 'checked' : '' }}
                                   class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <span class="ml-3 text-sm font-medium text-gray-900 dark:text-white">
                                🔗 ใช้การตั้งค่า AI จากระบบหลัก
                            </span>
                        </label>
                        <p class="mt-1 ml-8 text-xs text-gray-600 dark:text-gray-400">
                            เมื่อเปิดใช้งาน ระบบจะใช้ Gemini/Claude API Key จากการตั้งค่าระบบหลัก (ไม่ต้องตั้งค่าซ้ำ)
                        </p>
                    </div>
                    <div x-show="useGlobal" class="ml-4">
                        <span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 text-xs font-semibold rounded-full">
                            ✓ ใช้ค่าจากระบบหลัก
                        </span>
                    </div>
                </div>
            </div>

            {{-- แสดงเมื่อใช้ Global Settings --}}
            <div x-show="useGlobal" x-cloak class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-blue-500 text-lg"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-1">
                            กำลังใช้การตั้งค่า AI จากระบบหลัก
                        </h4>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">
                            ระบบจะอ่าน API Key จากการตั้งค่าระบบหลัก (AiContentSetting) โดยอัตโนมัติ
                        </p>
                        <div class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                            <div>• หากมี <strong>Gemini API Key</strong> จะใช้ Gemini (แนะนำ - ฟรี)</div>
                            <div>• หากมี <strong>Claude API Key</strong> จะใช้ Claude (via OpenRouter)</div>
                            <div>• หากมี <strong>OpenAI API Key</strong> จะใช้ GPT (via OpenRouter)</div>
                            <div>• หากมี Key ใน <strong>API Key Pool</strong> จะใช้ Key จาก Pool — วนครบทุก key อัตโนมัติ</div>
                        </div>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <a href="{{ route('admin.ai-api-keys.index') }}"
                               class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg transition shadow-sm"
                               target="_blank">
                                🔑 จัดการ AI API Key Pool
                                <svg class="ml-1.5 w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                            </a>
                        </div>
                        <p class="mt-2 text-xs text-amber-600 dark:text-amber-400">
                            💡 แนะนำเพิ่ม key หลายตัวใน Pool — ระบบจะวนใช้ครบทุก key ก่อน fail
                        </p>
                    </div>
                </div>
            </div>

            {{-- แสดงเมื่อใช้ Custom Settings --}}
            <div x-show="!useGlobal" x-cloak class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        AI Provider
                    </label>
                    <select name="ai_provider" 
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                        <option value="gemini" {{ $settings->ai_provider === 'gemini' ? 'selected' : '' }}>Gemini (Google) - แนะนำ ฟรี</option>
                        <option value="typhoon" {{ $settings->ai_provider === 'typhoon' ? 'selected' : '' }}>Typhoon (SCB 10X) - ภาษาไทยดีสุด ฟรี</option>
                        <option value="groq" {{ $settings->ai_provider === 'groq' ? 'selected' : '' }}>Groq - เร็วที่สุด ฟรี</option>
                        <option value="deepseek" {{ $settings->ai_provider === 'deepseek' ? 'selected' : '' }}>DeepSeek - ราคาถูก มี credits ฟรี</option>
                        <option value="grok" {{ $settings->ai_provider === 'grok' ? 'selected' : '' }}>Grok (xAI) - ฟันธง</option>
                        <option value="qwen" {{ $settings->ai_provider === 'qwen' ? 'selected' : '' }}>Qwen (Alibaba)</option>
                        <option value="openrouter" {{ $settings->ai_provider === 'openrouter' ? 'selected' : '' }}>OpenRouter - รวมหลาย AI</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Model
                    </label>
                    <input type="text" name="ai_model" 
                           value="{{ old('ai_model', $settings->ai_model) }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                           placeholder="gemini-2.0-flash-exp">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        API Key
                    </label>
                    <input type="password" name="ai_api_key"
                           value="{{ old('ai_api_key', $settings->ai_api_key) }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                           placeholder="AIz...">
                </div>
            </div> {{-- End of custom settings grid --}}

            {{-- ลิงก์ตั้งค่าที่เกี่ยวข้อง --}}
            <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg border border-gray-200 dark:border-gray-700">
                <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">🔗 ตั้งค่าที่เกี่ยวข้อง</h4>
                <div class="flex flex-wrap gap-2">
                    @if(Route::has('admin.ai-api-keys.index'))
                    <a href="{{ route('admin.ai-api-keys.index') }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 text-xs font-medium rounded-full hover:bg-blue-200 dark:hover:bg-blue-900/50 transition">
                        🔑 จัดการ API Key Pool
                    </a>
                    @endif
                    @if(Route::has('admin.ai-content-writer.settings'))
                    <a href="{{ route('admin.ai-content-writer.settings') }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 text-xs font-medium rounded-full hover:bg-green-200 dark:hover:bg-green-900/50 transition">
                        🤖 ตั้งค่า AI หลัก (Global)
                    </a>
                    @endif
                    <a href="{{ route('admin.fortune.playground') }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300 text-xs font-medium rounded-full hover:bg-purple-200 dark:hover:bg-purple-900/50 transition">
                        🎮 ทดสอบ AI Playground
                    </a>
                </div>
            </div>
        </div> {{-- End of AI Settings card --}}

        {{-- AI Chat ทั่วไป (สนทนาอัจฉริยะ) --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6"
             x-data="{ enableAiChat: {{ old('enable_ai_chat', $settings->enable_ai_chat ?? true) ? 'true' : 'false' }} }">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                    💬 AI Chat ทั่วไป (สนทนาอัจฉริยะ)
                </h3>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="hidden" name="enable_ai_chat" value="0">
                    <input type="checkbox" name="enable_ai_chat" value="1"
                           {{ old('enable_ai_chat', $settings->enable_ai_chat ?? true) ? 'checked' : '' }}
                           class="sr-only peer"
                           x-model="enableAiChat">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:after:border-gray-500 peer-checked:bg-green-600"></div>
                </label>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                เมื่อเปิดใช้งาน ข้อความที่ไม่ตรงกับคำสั่งดูดวงจะถูกส่งให้ AI ตอบแบบสนทนาทั่วไป แทนที่จะถามว่า "จะให้ดูดวงไหม" ทุกครั้ง
            </p>

            <div x-show="enableAiChat" x-transition class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Chat AI Provider --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Chat AI Provider
                        </label>
                        <select name="chat_ai_provider"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                            <option value="gemini" {{ old('chat_ai_provider', $settings->chat_ai_provider ?? 'gemini') === 'gemini' ? 'selected' : '' }}>Gemini (Google) - แนะนำ ฟรี</option>
                            <option value="typhoon" {{ old('chat_ai_provider', $settings->chat_ai_provider ?? '') === 'typhoon' ? 'selected' : '' }}>Typhoon (SCB 10X) - ภาษาไทยดีสุด</option>
                            <option value="groq" {{ old('chat_ai_provider', $settings->chat_ai_provider ?? '') === 'groq' ? 'selected' : '' }}>Groq - เร็วที่สุด ฟรี</option>
                            <option value="deepseek" {{ old('chat_ai_provider', $settings->chat_ai_provider ?? '') === 'deepseek' ? 'selected' : '' }}>DeepSeek - ราคาถูก</option>
                            <option value="grok" {{ old('chat_ai_provider', $settings->chat_ai_provider ?? '') === 'grok' ? 'selected' : '' }}>Grok (xAI)</option>
                            <option value="qwen" {{ old('chat_ai_provider', $settings->chat_ai_provider ?? '') === 'qwen' ? 'selected' : '' }}>Qwen (Alibaba)</option>
                            <option value="openrouter" {{ old('chat_ai_provider', $settings->chat_ai_provider ?? '') === 'openrouter' ? 'selected' : '' }}>OpenRouter</option>
                        </select>
                    </div>

                    {{-- Chat AI Model --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Chat AI Model
                        </label>
                        <input type="text" name="chat_ai_model"
                               value="{{ old('chat_ai_model', $settings->chat_ai_model ?? 'gemini-2.0-flash') }}"
                               placeholder="gemini-2.0-flash"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                {{-- Chat API Key --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Chat API Key <span class="text-gray-400 font-normal">(ถ้าว่าง จะใช้ Key จาก Pool/ระบบหลัก)</span>
                    </label>
                    <input type="password" name="chat_ai_api_key"
                           value="{{ old('chat_ai_api_key', $settings->chat_ai_api_key) }}"
                           placeholder="ไม่จำเป็น — ใช้ Key จากระบบหลักได้"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                </div>

                {{-- Custom System Prompt --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        System Prompt สำหรับ Chat <span class="text-gray-400 font-normal">(ถ้าว่าง จะใช้ค่าเริ่มต้น)</span>
                    </label>
                    <textarea name="chat_system_prompt" rows="4"
                              placeholder="ปล่อยว่างเพื่อใช้ prompt เริ่มต้น — จันทราจะสนทนาเป็นมิตรและชวนดูดวงอย่างเป็นธรรมชาติ"
                              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">{{ old('chat_system_prompt', $settings->chat_system_prompt) }}</textarea>
                </div>

                {{-- Info box --}}
                <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <p class="text-xs text-blue-800 dark:text-blue-200">
                        💡 <strong>AI Chat</strong> จะทำงานเฉพาะเมื่อข้อความไม่ตรงกับ keywords ดูดวง และ keywords จากฐานข้อมูล — ไม่กระทบ limit ดูดวงฟรี ไม่สร้าง FortuneReading
                    </p>
                    <p class="text-xs text-blue-800 dark:text-blue-200 mt-1">
                        🎯 <strong>แนะนำ:</strong> ใช้ Gemini สำหรับ Chat (เร็ว ฟรี สนทนาดี) และ Grok/อื่นๆ สำหรับทำนาย (ฟันธง แม่น)
                    </p>
                </div>
            </div>
        </div> {{-- End of AI Chat card --}}

        {{-- Comment Engagement Settings --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                    🗨️ ระบบตอบคอมเม้นต์อัตโนมัติ
                </h3>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="hidden" name="comment_engagement_enabled" value="0">
                    <input type="checkbox" name="comment_engagement_enabled" value="1"
                           {{ old('comment_engagement_enabled', $settings->comment_engagement_enabled) ? 'checked' : '' }}
                           class="sr-only peer"
                           x-model="commentEngagementEnabled">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:after:border-gray-500 peer-checked:bg-green-600"></div>
                </label>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                เมื่อเปิดใช้งาน ระบบจะตอบคอมเม้นต์ทุกโพสต์ในเพจ + ทักแชทส่วนตัวชวนดูดวง (เฉพาะคอมเม้นต์ที่ไม่ใช่คำสั่ง "ดูดวง")
            </p>

            <div x-show="commentEngagementEnabled" x-transition>
                {{-- โหมดการทำงาน --}}
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        โหมดสร้างข้อความ
                    </label>
                    <select name="comment_engagement_mode"
                            x-model="commentEngagementMode"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                        <option value="ai" {{ old('comment_engagement_mode', $settings->comment_engagement_mode) === 'ai' ? 'selected' : '' }}>
                            🤖 AI สร้างข้อความ - AI อ่านคอมเม้นต์แล้วสร้างข้อความชวนที่เหมาะกับบริบท
                        </option>
                        <option value="template" {{ old('comment_engagement_mode', $settings->comment_engagement_mode) === 'template' ? 'selected' : '' }}>
                            📝 เทมเพลตคงที่ - ส่งข้อความเดิมทุกครั้ง (เร็วกว่า)
                        </option>
                    </select>
                </div>

                {{-- AI Mode: Prompt --}}
                <div x-show="commentEngagementMode === 'ai'" x-transition class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        AI Prompt (คำสั่งสำหรับ AI สร้างข้อความชวน)
                    </label>
                    <textarea name="comment_engagement_prompt" rows="8"
                              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 font-mono text-sm"
                              placeholder="ใช้ค่าเริ่มต้น (ถ้าว่าง)">{{ old('comment_engagement_prompt', $settings->comment_engagement_prompt) }}</textarea>
                    <p class="text-xs text-gray-400 mt-1">Placeholders: {comment} = ข้อความคอมเม้นต์, {name} = ชื่อผู้คอมเม้นต์, {profile_info} = ข้อมูลโปรไฟล์ (เพศ/วันเกิด) | เว้นว่างเพื่อใช้ค่าเริ่มต้น</p>
                </div>

                {{-- Template Mode: Comment Reply --}}
                <div x-show="commentEngagementMode === 'template'" x-transition class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        เทมเพลตตอบคอมเม้นต์
                    </label>
                    <textarea name="comment_reply_template" rows="2"
                              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                              placeholder="สวัสดีค่ะคุณ {name} 🔮 สนใจดูดวงไหมคะ? ทักมาใน inbox ได้เลยนะคะ ✨">{{ old('comment_reply_template', $settings->comment_reply_template) }}</textarea>
                    <p class="text-xs text-gray-400 mt-1">Placeholders: {name} = ชื่อ, {comment} = ข้อความคอมเม้นต์ | เว้นว่างเพื่อใช้ค่าเริ่มต้น</p>
                </div>

                {{-- Template Mode: DM Message --}}
                <div x-show="commentEngagementMode === 'template'" x-transition class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        เทมเพลตข้อความ inbox
                    </label>
                    <textarea name="comment_dm_template" rows="6"
                              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                              placeholder="สวัสดีค่ะคุณ {name} 🔮✨ ลองพิมพ์ &quot;ดูดวง&quot; ได้เลยค่ะ!">{{ old('comment_dm_template', $settings->comment_dm_template) }}</textarea>
                    <p class="text-xs text-gray-400 mt-1">Placeholders: {name} = ชื่อ, {comment} = ข้อความคอมเม้นต์ | เว้นว่างเพื่อใช้ค่าเริ่มต้น</p>
                </div>

                {{-- Info Box --}}
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                    <p class="text-sm text-blue-700 dark:text-blue-300">
                        💡 <strong>วิธีทำงาน:</strong> เมื่อมีคนคอมเม้นต์อะไรก็ได้ในโพสต์ของเพจ (ยกเว้นคำสั่ง "ดูดวง") ระบบจะ:
                    </p>
                    <ul class="text-sm text-blue-600 dark:text-blue-400 mt-2 ml-4 list-disc">
                        <li>ตอบคอมเม้นต์สั้นๆ ชวนดูดวง</li>
                        <li>ส่งข้อความส่วนตัว (inbox) พร้อมปุ่ม Quick Reply "ดูดวง" / "ดูดวงละเอียด"</li>
                        <li>ดึงโปรไฟล์ผู้คอมเม้นต์ (ชื่อ/เพศ/วันเกิด) มาใช้ประกอบ</li>
                        <li>ไม่ engage ซ้ำคนเดิมในโพสต์เดิม</li>
                        <li>ไม่ตอบคอมเม้นต์ที่เพจเป็นคนโพสต์เอง</li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Usage Settings --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                ⚙️ การตั้งค่าการใช้งานพื้นฐาน
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        จำนวนดูดวงฟรี/วัน (พื้นฐาน)
                    </label>
                    <input type="number" name="max_free_readings" min="0" max="100"
                           value="{{ old('max_free_readings', $settings->max_free_readings) }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">จำนวนครั้งที่ผู้ใช้ดูดวงพื้นฐานได้ฟรีต่อวัน</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ราคาดูดวงพื้นฐาน/ครั้ง (บาท)
                    </label>
                    <input type="number" name="reading_price" min="0" step="0.01"
                           value="{{ old('reading_price', $settings->reading_price) }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">0 = ฟรี (ใช้ร่วมกับจำนวนฟรี/วัน)</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        QR Code ชำระเงิน
                    </label>
                    <input type="file" name="payment_qr_image" accept="image/*"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    @if($settings->payment_qr_image)
                        <p class="mt-1 text-xs text-green-600 dark:text-green-400">มี QR Code อยู่แล้ว</p>
                    @endif
                </div>
            </div>

            {{-- โหมดแสดงช่องทางชำระเงิน --}}
            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    💳 โหมดแสดงช่องทางชำระเงิน
                </label>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                    เลือกช่องทางชำระเงินที่จะแสดงให้ลูกค้าเห็นในข้อความชำระเงิน (ช่วยหลีกเลี่ยง Facebook ตรวจจับเรื่องการเงิน)
                </p>
                <div class="space-y-2">
                    <label class="flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition
                        {{ ($settings->payment_display_mode ?? 'both') === 'both' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                        <input type="radio" name="payment_display_mode" value="both"
                            {{ ($settings->payment_display_mode ?? 'both') === 'both' ? 'checked' : '' }}
                            class="text-blue-600 focus:ring-blue-500">
                        <div>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">🏦 โอนเงิน + พร้อมเพย์</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400">แสดงทั้งเลขบัญชีธนาคารและพร้อมเพย์</p>
                        </div>
                    </label>
                    <label class="flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition
                        {{ ($settings->payment_display_mode ?? 'both') === 'bank_only' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                        <input type="radio" name="payment_display_mode" value="bank_only"
                            {{ ($settings->payment_display_mode ?? 'both') === 'bank_only' ? 'checked' : '' }}
                            class="text-blue-600 focus:ring-blue-500">
                        <div>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">🏦 โอนเงินเท่านั้น</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400">แสดงเฉพาะเลขบัญชีธนาคาร (ไม่แสดงพร้อมเพย์)</p>
                        </div>
                    </label>
                    <label class="flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition
                        {{ ($settings->payment_display_mode ?? 'both') === 'promptpay_only' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                        <input type="radio" name="payment_display_mode" value="promptpay_only"
                            {{ ($settings->payment_display_mode ?? 'both') === 'promptpay_only' ? 'checked' : '' }}
                            class="text-blue-600 focus:ring-blue-500">
                        <div>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">📱 พร้อมเพย์เท่านั้น</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400">แสดงเฉพาะพร้อมเพย์ (ไม่แสดงเลขบัญชี — เลี่ยง FB ตรวจจับ)</p>
                        </div>
                    </label>
                </div>
            </div>

            {{-- บัญชีธนาคารสำหรับระบบดูดวง (CRUD) --}}
            <div class="mt-6" x-data="fortuneBankAccounts()">
                <div class="flex items-center justify-between mb-3">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        🏦 บัญชีธนาคารสำหรับระบบดูดวง
                    </label>
                    <button type="button" @click="showAddForm = !showAddForm"
                            class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition">
                        <span x-text="showAddForm ? '✕ ปิด' : '+ เพิ่มบัญชีใหม่'"></span>
                    </button>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                    จัดการบัญชีธนาคารที่จะแสดงให้ลูกค้าเมื่อต้องชำระเงินดูดวง (แยกจากอีคอมเมิร์ช)
                </p>

                {{-- ข้อความแจ้งเตือน --}}
                <div x-show="alertMessage" x-cloak x-transition
                     :class="alertType === 'success' ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800 text-green-700 dark:text-green-400' : 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800 text-red-700 dark:text-red-400'"
                     class="p-3 rounded-lg border text-sm mb-3">
                    <span x-text="alertMessage"></span>
                </div>

                {{-- ฟอร์มเพิ่มบัญชีใหม่ --}}
                <div x-show="showAddForm" x-cloak x-transition
                     class="p-4 mb-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-700">
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">เพิ่มบัญชีธนาคารใหม่</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">ธนาคาร</label>
                            <select x-model="newAccount.bank_code" @change="updateBankName()"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                                <option value="">-- เลือกธนาคาร --</option>
                                @foreach($supportedBanks ?? [] as $code => $name)
                                    <option value="{{ $code }}" data-name="{{ $name }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">เลขบัญชี</label>
                            <input type="text" x-model="newAccount.account_number" placeholder="เช่น 123-4-56789-0"
                                   class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อบัญชี</label>
                            <input type="text" x-model="newAccount.account_name" placeholder="เช่น นายสมชาย ใจดี"
                                   class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">พร้อมเพย์ (ถ้ามี)</label>
                            <input type="text" x-model="newAccount.promptpay_id" placeholder="เช่น 0812345678"
                                   class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    <div class="flex items-center justify-between mt-3">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" x-model="newAccount.sms_checker_enabled"
                                   class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                            <span class="text-xs text-gray-700 dark:text-gray-300">เปิด SMS Checker (ตรวจสอบยอดโอนอัตโนมัติ)</span>
                        </label>
                        <div class="flex gap-2">
                            <button type="button" @click="showAddForm = false"
                                    class="px-3 py-1.5 text-xs text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 transition">
                                ยกเลิก
                            </button>
                            <button type="button" @click="addAccount()" :disabled="saving"
                                    class="px-4 py-1.5 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition disabled:opacity-50">
                                <span x-show="!saving">💾 บันทึก</span>
                                <span x-show="saving">กำลังบันทึก...</span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- รายการบัญชี --}}
                <div class="space-y-2">
                    <template x-for="account in accounts" :key="account.id">
                        <div class="p-3 rounded-lg border transition"
                             :class="account.selected
                                 ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 dark:border-blue-400'
                                 : 'border-gray-200 dark:border-gray-600'">

                            {{-- โหมดแสดงผล --}}
                            <div x-show="editingId !== account.id" class="flex items-start gap-3">
                                <input type="checkbox"
                                       :name="'fortune_bank_account_ids[]'"
                                       :value="account.id"
                                       x-model="account.selected"
                                       class="mt-1 w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="account.bank_name"></span>
                                        <template x-if="account.sms_checker_enabled">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                                📱 SMS Checker
                                            </span>
                                        </template>
                                    </div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                        เลขบัญชี: <span x-text="account.account_number"></span> |
                                        ชื่อ: <span x-text="account.account_name"></span>
                                        <template x-if="account.promptpay_id">
                                            <span> | พร้อมเพย์: <span x-text="account.promptpay_id"></span></span>
                                        </template>
                                    </div>
                                </div>
                                <div class="flex gap-1 shrink-0">
                                    <button type="button" @click="startEdit(account)"
                                            class="p-1.5 text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded transition"
                                            title="แก้ไข">
                                        ✏️
                                    </button>
                                    <button type="button" @click="deleteAccount(account)"
                                            class="p-1.5 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/30 rounded transition"
                                            title="ลบ">
                                        🗑️
                                    </button>
                                </div>
                            </div>

                            {{-- โหมดแก้ไข --}}
                            <div x-show="editingId === account.id" x-cloak>
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">แก้ไขบัญชี</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">ธนาคาร</label>
                                        <select x-model="editData.bank_code" @change="updateEditBankName()"
                                                class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                                            @foreach($supportedBanks ?? [] as $code => $name)
                                                <option value="{{ $code }}">{{ $name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">เลขบัญชี</label>
                                        <input type="text" x-model="editData.account_number"
                                               class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อบัญชี</label>
                                        <input type="text" x-model="editData.account_name"
                                               class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">พร้อมเพย์</label>
                                        <input type="text" x-model="editData.promptpay_id"
                                               class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                                    </div>
                                </div>
                                <div class="flex items-center justify-between mt-3">
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" x-model="editData.sms_checker_enabled"
                                               class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                                        <span class="text-xs text-gray-700 dark:text-gray-300">เปิด SMS Checker</span>
                                    </label>
                                    <div class="flex gap-2">
                                        <button type="button" @click="editingId = null"
                                                class="px-3 py-1.5 text-xs text-gray-600 dark:text-gray-400 hover:text-gray-800 transition">
                                            ยกเลิก
                                        </button>
                                        <button type="button" @click="saveEdit(account)" :disabled="saving"
                                                class="px-4 py-1.5 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition disabled:opacity-50">
                                            <span x-show="!saving">💾 บันทึก</span>
                                            <span x-show="saving">กำลังบันทึก...</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    {{-- ข้อความเมื่อไม่มีบัญชี --}}
                    <div x-show="accounts.length === 0" class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg text-center">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            ยังไม่มีบัญชีธนาคาร กด "+ เพิ่มบัญชีใหม่" เพื่อเพิ่ม
                        </p>
                    </div>
                </div>

                <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">
                    💡 ติ๊กเลือกบัญชีที่ต้องการใช้กับระบบดูดวง หากไม่เลือก จะใช้บัญชีทั้งหมดที่เปิด SMS Checker
                </p>
            </div>

            <script>
                /**
                 * Alpine.js component สำหรับจัดการบัญชีธนาคารระบบดูดวง
                 */
                function fortuneBankAccounts() {
                    // รายชื่อธนาคารจาก supportedBanks
                    const bankNames = @json($supportedBanks ?? []);
                    // IDs ที่เลือกไว้
                    const selectedIds = @json($settings->fortune_bank_account_ids ?? []);

                    return {
                        showAddForm: false,
                        saving: false,
                        editingId: null,
                        alertMessage: '',
                        alertType: 'success',
                        // บัญชีทั้งหมด (เตรียมจาก controller)
                        accounts: @json($bankAccountsJson ?? []),
                        // ฟอร์มเพิ่มบัญชีใหม่
                        newAccount: {
                            bank_code: '',
                            bank_name: '',
                            account_number: '',
                            account_name: '',
                            promptpay_id: '',
                            sms_checker_enabled: true,
                        },
                        // ฟอร์มแก้ไข
                        editData: {
                            bank_code: '',
                            bank_name: '',
                            account_number: '',
                            account_name: '',
                            promptpay_id: '',
                            sms_checker_enabled: true,
                        },

                        /**
                         * อัพเดทชื่อธนาคารจาก bank_code (ฟอร์มเพิ่ม)
                         */
                        updateBankName() {
                            this.newAccount.bank_name = bankNames[this.newAccount.bank_code] || '';
                        },

                        /**
                         * อัพเดทชื่อธนาคารจาก bank_code (ฟอร์มแก้ไข)
                         */
                        updateEditBankName() {
                            this.editData.bank_name = bankNames[this.editData.bank_code] || '';
                        },

                        /**
                         * แสดงข้อความแจ้งเตือน
                         */
                        showAlert(message, type = 'success') {
                            this.alertMessage = message;
                            this.alertType = type;
                            setTimeout(() => { this.alertMessage = ''; }, 4000);
                        },

                        /**
                         * เพิ่มบัญชีใหม่ via AJAX
                         */
                        async addAccount() {
                            if (!this.newAccount.bank_code || !this.newAccount.account_number || !this.newAccount.account_name) {
                                this.showAlert('กรุณากรอกข้อมูลให้ครบ (ธนาคาร, เลขบัญชี, ชื่อบัญชี)', 'error');
                                return;
                            }
                            this.saving = true;
                            try {
                                const res = await fetch('{{ route("admin.fortune.settings.bank-accounts.store") }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                        'Accept': 'application/json',
                                    },
                                    body: JSON.stringify(this.newAccount),
                                });
                                const data = await res.json();
                                if (data.success) {
                                    data.account.selected = true;
                                    this.accounts.push(data.account);
                                    // reset ฟอร์ม
                                    this.newAccount = { bank_code: '', bank_name: '', account_number: '', account_name: '', promptpay_id: '', sms_checker_enabled: true };
                                    this.showAddForm = false;
                                    this.showAlert(data.message);
                                } else {
                                    this.showAlert(data.message || 'เกิดข้อผิดพลาด', 'error');
                                }
                            } catch (e) {
                                this.showAlert('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
                            }
                            this.saving = false;
                        },

                        /**
                         * เริ่มแก้ไขบัญชี
                         */
                        startEdit(account) {
                            this.editingId = account.id;
                            this.editData = {
                                bank_code: account.bank_code,
                                bank_name: account.bank_name,
                                account_number: account.account_number,
                                account_name: account.account_name,
                                promptpay_id: account.promptpay_id || '',
                                sms_checker_enabled: account.sms_checker_enabled,
                            };
                        },

                        /**
                         * บันทึกการแก้ไขบัญชี via AJAX
                         */
                        async saveEdit(account) {
                            this.saving = true;
                            try {
                                const url = '{{ route("admin.fortune.settings.bank-accounts.update", ":id") }}'.replace(':id', account.id);
                                const res = await fetch(url, {
                                    method: 'PUT',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                        'Accept': 'application/json',
                                    },
                                    body: JSON.stringify(this.editData),
                                });
                                const data = await res.json();
                                if (data.success) {
                                    // อัพเดทข้อมูลใน list
                                    const idx = this.accounts.findIndex(a => a.id === account.id);
                                    if (idx !== -1) {
                                        this.accounts[idx] = { ...this.accounts[idx], ...data.account };
                                    }
                                    this.editingId = null;
                                    this.showAlert(data.message);
                                } else {
                                    this.showAlert(data.message || 'เกิดข้อผิดพลาด', 'error');
                                }
                            } catch (e) {
                                this.showAlert('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
                            }
                            this.saving = false;
                        },

                        /**
                         * ลบบัญชี via AJAX
                         */
                        async deleteAccount(account) {
                            if (!confirm('ต้องการลบบัญชี ' + account.bank_name + ' (' + account.account_number + ') หรือไม่?')) return;
                            try {
                                const url = '{{ route("admin.fortune.settings.bank-accounts.delete", ":id") }}'.replace(':id', account.id);
                                const res = await fetch(url, {
                                    method: 'DELETE',
                                    headers: {
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                        'Accept': 'application/json',
                                    },
                                });
                                const data = await res.json();
                                if (data.success) {
                                    this.accounts = this.accounts.filter(a => a.id !== account.id);
                                    this.showAlert(data.message);
                                } else {
                                    this.showAlert(data.message || 'เกิดข้อผิดพลาด', 'error');
                                }
                            } catch (e) {
                                this.showAlert('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
                            }
                        },
                    };
                }
            </script>

            <div class="mt-4 space-y-3">
                <label class="flex items-center">
                    <input type="checkbox" name="respond_in_comment" value="1"
                           {{ $settings->respond_in_comment ? 'checked' : '' }}
                           class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">ตอบกลับในคอมเมนต์ (ไม่ใช่ส่ง private message)</span>
                </label>

                <label class="flex items-center">
                    <input type="checkbox" name="require_registration" value="1"
                           {{ $settings->require_registration ? 'checked' : '' }}
                           class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">บังคับให้สมัครสมาชิกก่อนใช้งาน</span>
                </label>
            </div>
        </div>

        {{-- Freemium: Deep Reading Settings --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6" x-data="{ enableDeep: {{ old('enable_deep_reading', $settings->enable_deep_reading ?? true) ? 'true' : 'false' }} }">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                    🌟 คำทำนายเชิงลึก (Freemium)
                </h3>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="enable_deep_reading" value="1"
                           x-model="enableDeep"
                           {{ old('enable_deep_reading', $settings->enable_deep_reading ?? true) ? 'checked' : '' }}
                           class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 dark:peer-focus:ring-purple-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-purple-600"></div>
                </label>
            </div>

            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                เปิดใช้งานระบบคำทำนายสองระดับ: พื้นฐาน (ฟรี/ราคาถูก) + เชิงลึก (ต้องจ่ายเงินหรือสมัครสมาชิก)
            </p>

            <div x-show="enableDeep" x-cloak>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            💰 ราคาคำทำนายเชิงลึก/ครั้ง (บาท)
                        </label>
                        <input type="number" name="deep_reading_price" min="0" step="0.01"
                               value="{{ old('deep_reading_price', $settings->deep_reading_price ?? 99) }}"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">ราคาต่อครั้งสำหรับคำทำนายละเอียดลึกซึ้ง</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            🎁 ทดลองฟรีเชิงลึก/วัน
                        </label>
                        <input type="number" name="free_deep_per_day" min="0" max="10"
                               value="{{ old('free_deep_per_day', $settings->free_deep_per_day ?? 1) }}"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">ดูดวงเชิงลึกฟรีกี่ครั้ง/วัน (ก่อนต้องจ่ายเงิน)</p>
                    </div>

                    <div class="flex items-end">
                        <label class="flex items-center pb-2">
                            <input type="checkbox" name="allow_try_before_buy" value="1"
                                   {{ old('allow_try_before_buy', $settings->allow_try_before_buy ?? true) ? 'checked' : '' }}
                                   class="w-4 h-4 text-purple-600 bg-gray-100 border-gray-300 rounded focus:ring-purple-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                อนุญาตให้ขอคำทำนายก่อนจ่ายทีหลัง
                            </span>
                        </label>
                    </div>
                </div>

                {{-- Tip Box --}}
                <div class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg border border-purple-200 dark:border-purple-800">
                    <div class="flex items-start gap-3">
                        <span class="text-2xl">💡</span>
                        <div>
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-1">วิธีทำงาน</h4>
                            <ul class="text-xs text-gray-600 dark:text-gray-400 space-y-1 list-disc list-inside">
                                <li>ผู้ใช้พิมพ์ <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">ดูดวง [คำถาม]</code> = ได้คำทำนายพื้นฐาน (ฟรีตามจำนวนที่ตั้ง)</li>
                                <li>ผู้ใช้พิมพ์ <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">ดูดวงละเอียด [คำถาม]</code> = ได้คำทำนายเชิงลึก</li>
                                <li>คำทำนายเชิงลึกฟรี {{ $settings->free_deep_per_day ?? 1 }} ครั้ง/วัน จากนั้นต้องจ่ายเงินหรือสมัครสมาชิก</li>
                                <li>หลังทำนายเชิงลึกฟรี ระบบจะส่งข้อความแนะนำการจ่ายเงิน/สมัครสมาชิกอัตโนมัติ</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Subscription Settings --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6" x-data="{ enableSub: {{ old('subscription_enabled', $settings->subscription_enabled ?? true) ? 'true' : 'false' }} }">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                    💎 ระบบสมัครสมาชิก
                </h3>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="subscription_enabled" value="1"
                           x-model="enableSub"
                           {{ old('subscription_enabled', $settings->subscription_enabled ?? true) ? 'checked' : '' }}
                           class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                </label>
            </div>

            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                สมาชิกจะสามารถดูดวงเชิงลึกได้ไม่จำกัดจำนวนครั้ง โดยไม่ต้องจ่ายต่อครั้ง
            </p>

            <div x-show="enableSub" x-cloak>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            💎 ราคาสมาชิกรายเดือน (บาท)
                        </label>
                        <input type="number" name="subscription_monthly_price" min="0" step="0.01"
                               value="{{ old('subscription_monthly_price', $settings->subscription_monthly_price ?? 199) }}"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            👑 ราคาสมาชิกรายปี (บาท)
                        </label>
                        <input type="number" name="subscription_yearly_price" min="0" step="0.01"
                               value="{{ old('subscription_yearly_price', $settings->subscription_yearly_price ?? 1990) }}"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        📋 สิทธิประโยชน์ของสมาชิก
                    </label>
                    <textarea name="subscription_benefits" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                              placeholder="ดูดวงเชิงลึกไม่จำกัด&#10;คำทำนายพร้อมสีมงคล เลขมงคล&#10;วิเคราะห์ดวงจากดาวเคราะห์ส่งผล">{{ old('subscription_benefits', $settings->subscription_benefits ?? '') }}</textarea>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">แสดงให้ผู้ใช้เห็นเมื่อแนะนำสมัครสมาชิก (1 สิทธิ์/บรรทัด)</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ข้อความแนะนำสมัครสมาชิก
                    </label>
                    <textarea name="subscription_message" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                              placeholder="ข้อความที่ส่งให้ผู้ใช้เมื่อแนะนำสมัครสมาชิก (ว่างไว้ = ใช้ค่าเริ่มต้น)">{{ old('subscription_message', $settings->subscription_message ?? '') }}</textarea>
                </div>
            </div>
        </div>

        {{-- Prompt Templates --}}
        <div x-data="{
            basicDefault: {{ json_encode($defaultBasicPrompt) }},
            deepDefault: {{ json_encode($defaultDeepPrompt) }},
            resetBasic() {
                if (confirm('รีเซ็ต Prompt พื้นฐานกลับเป็นค่าเริ่มต้น?')) {
                    this.$refs.basicPrompt.value = this.basicDefault;
                }
            },
            resetDeep() {
                if (confirm('รีเซ็ต Prompt เชิงลึกกลับเป็นค่าเริ่มต้น?')) {
                    this.$refs.deepPrompt.value = this.deepDefault;
                }
            }
        }" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                📝 เทมเพลตคำทำนาย (AI Prompt)
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                แก้ไข prompt ที่ AI ใช้ทำนายได้โดยตรง — กดปุ่ม "🔄 รีเซ็ต" เพื่อกลับค่าเริ่มต้น | ตัวแปร <code class="text-xs bg-gray-200 dark:bg-gray-700 px-1 rounded">{name}</code> จะถูกแทนที่ด้วยข้อมูลจริงตอนทำนาย
            </p>

            <div class="space-y-6">
                {{-- Basic Prompt --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            🔮 Prompt ดูดวงพื้นฐาน (ฟรี — สั้น กระชับ)
                        </label>
                        <button type="button" @click="resetBasic()"
                                class="text-xs px-3 py-1 bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-300 rounded-lg hover:bg-red-200 dark:hover:bg-red-800 transition">
                            🔄 รีเซ็ตเป็นค่าเริ่มต้น
                        </button>
                    </div>

                    <textarea name="basic_prompt_template" rows="20" x-ref="basicPrompt"
                              class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 font-mono text-sm leading-relaxed"
                              placeholder="ใส่ Prompt สำหรับดูดวงพื้นฐาน...">{{ old('basic_prompt_template', $settings->basic_prompt_template ?? $defaultBasicPrompt) }}</textarea>
                    <div class="mt-2 p-3 bg-blue-50 dark:bg-blue-900/30 rounded-lg">
                        <p class="text-xs font-semibold text-blue-700 dark:text-blue-300 mb-1">📌 ตัวแปรที่ใช้ได้ใน Prompt พื้นฐาน:</p>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-1 text-xs text-blue-600 dark:text-blue-400 font-mono">
                            <span>{name} — ชื่อผู้ถาม</span>
                            <span>{gender_prefix} — คำนำหน้า</span>
                            <span>{gender} — เพศ</span>
                            <span>{questions} — คำถาม</span>
                            <span>{question} — คำถาม (เหมือน questions)</span>
                            <span>{user_context} — บริบทผู้ถาม</span>
                            <span>{detected_category} — หมวดหมู่คำถาม</span>
                            <span>{user_profile} — ข้อมูลผู้ถาม (JSON)</span>
                            <span>{birth_date_section} — ส่วนวันเกิด</span>
                        </div>
                    </div>
                </div>

                {{-- Deep Prompt --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            🌟 Prompt ดูดวงเชิงลึก (จ่ายเงิน — ละเอียด ลึกซึ้ง ทีละคำถาม)
                        </label>
                        <button type="button" @click="resetDeep()"
                                class="text-xs px-3 py-1 bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-300 rounded-lg hover:bg-red-200 dark:hover:bg-red-800 transition">
                            🔄 รีเซ็ตเป็นค่าเริ่มต้น
                        </button>
                    </div>

                    <textarea name="deep_prompt_template" rows="25" x-ref="deepPrompt"
                              class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 font-mono text-sm leading-relaxed"
                              placeholder="ใส่ Prompt สำหรับดูดวงเชิงลึก...">{{ old('deep_prompt_template', $settings->deep_prompt_template ?? $defaultDeepPrompt) }}</textarea>
                    <div class="mt-2 p-3 bg-purple-50 dark:bg-purple-900/30 rounded-lg">
                        <p class="text-xs font-semibold text-purple-700 dark:text-purple-300 mb-1">📌 ตัวแปรที่ใช้ได้ใน Prompt เชิงลึก:</p>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-1 text-xs text-purple-600 dark:text-purple-400 font-mono">
                            <span>{name} — ชื่อผู้ถาม</span>
                            <span>{gender_prefix} — คำนำหน้า</span>
                            <span>{gender} — เพศ</span>
                            <span>{question} — คำถามปัจจุบัน</span>
                            <span>{question_number} — ลำดับคำถาม</span>
                            <span>{total_questions} — จำนวนคำถามทั้งหมด</span>
                            <span>{birth_info} — ข้อมูลวันเกิด (ภาษาไทย)</span>
                            <span>{birth_date} — วันเกิด (raw)</span>
                            <span>{zodiac_info} — ข้อมูลราศี</span>
                            <span>{planet_positions} — ตำแหน่งดาวในภพ</span>
                            <span>{transit_info} — ดาวโคจรปัจจุบัน</span>
                            <span>{previous_context} — คำทำนายก่อนหน้า</span>
                            <span>{user_profile} — ข้อมูลผู้ถาม (JSON)</span>
                        </div>
                    </div>
                </div>

                {{-- ข้อความหลังทดลองดูฟรี --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        💬 ข้อความหลังทดลองดูฟรี (แนะนำจ่ายเงิน)
                    </label>
                    <textarea name="try_before_buy_message" rows="4"
                              class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 text-sm"
                              placeholder="ว่างไว้ = ใช้ค่าเริ่มต้น">{{ old('try_before_buy_message', $settings->try_before_buy_message ?? '') }}</textarea>
                </div>
            </div>
        </div>

        {{-- ============================================================ --}}
        {{-- Affiliate/MLM Settings สำหรับดูดวง --}}
        {{-- ============================================================ --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6"
             x-data="{
                affEnabled: {{ old('fortune_affiliate_enabled', $settings->fortune_affiliate_enabled ?? false) ? 'true' : 'false' }},
                autoReg: {{ old('fortune_auto_register_enabled', $settings->fortune_auto_register_enabled ?? true) ? 'true' : 'false' }},
                useGlobal: {{ old('fortune_use_global_commission_rate', $settings->fortune_use_global_commission_rate ?? true) ? 'true' : 'false' }},
                pvValue: '{{ old('fortune_pv_value', $settings->fortune_pv_value ?? 0) }}',
                customRate: '{{ old('fortune_custom_commission_per_pv', $settings->fortune_custom_commission_per_pv ?? '') }}',
                commissionMode: '{{ old('fortune_commission_mode', $settings->fortune_commission_mode ?? 'pv') }}',
                staticAmount: '{{ old('fortune_static_commission_amount', $settings->fortune_static_commission_amount ?? 0) }}',
                preview: null,
                previewLoading: false,
                previewError: null,
                async calcPreview() {
                    this.previewLoading = true;
                    this.previewError = null;
                    try {
                        const params = new URLSearchParams({
                            pv_value: this.pvValue,
                            use_global: this.useGlobal ? '1' : '0',
                            custom_rate: this.customRate || '0',
                            commission_mode: this.commissionMode,
                            static_amount: this.staticAmount || '0'
                        });
                        const res = await fetch('{{ route('admin.fortune.settings.fortune-commission-preview') }}?' + params);
                        const contentType = res.headers.get('content-type') || '';
                        if (!contentType.includes('application/json')) {
                            this.previewError = 'เซิร์ฟเวอร์ตอบกลับไม่ถูกต้อง (HTTP ' + res.status + ') กรุณาล็อกอินใหม่แล้วลองอีกครั้ง';
                            return;
                        }
                        const json = await res.json();
                        if (json.success) {
                            this.preview = json.data;
                        } else {
                            this.previewError = json.message || 'เกิดข้อผิดพลาดในการคำนวณ';
                        }
                    } catch (e) {
                        this.previewError = 'เกิดข้อผิดพลาด: ' + e.message;
                    } finally {
                        this.previewLoading = false;
                    }
                }
             }">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                💰 ตั้งค่าระบบ Affiliate สำหรับดูดวง
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                ลงทะเบียนลูกค้าดูดวง LINE เป็นสมาชิก Thaiprompt อัตโนมัติ พร้อมระบบคอมมิชชั่น MLM
            </p>

            {{-- Toggle เปิด/ปิดระบบ --}}
            <div class="flex items-center justify-between mb-6 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <div>
                    <label class="font-medium text-gray-900 dark:text-white">เปิดระบบ Affiliate อัตโนมัติ</label>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">เมื่อเปิด ลูกค้าดูดวง LINE จะถูกสมัครสมาชิก + MLM อัตโนมัติหลังจ่ายเงิน</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="fortune_affiliate_enabled" value="1"
                           x-model="affEnabled" class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 dark:peer-focus:ring-purple-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:after:border-gray-500 peer-checked:bg-purple-600"></div>
                </label>
            </div>

            {{-- Settings (แสดงเมื่อ enabled) --}}
            <div x-show="affEnabled" x-transition x-cloak class="space-y-5">
                {{-- Auto-register toggle --}}
                <div class="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <div>
                        <label class="text-sm font-medium text-gray-900 dark:text-white">สร้าง User อัตโนมัติ</label>
                        <p class="text-xs text-gray-500 dark:text-gray-400">สร้างบัญชีสมาชิกจาก LINE profile อัตโนมัติหลังจ่ายค่าดูดวง</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="fortune_auto_register_enabled" value="1"
                               x-model="autoReg" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>

                {{-- Commission Mode Selector --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        🎯 โหมดจ่ายคอมมิชชั่น
                    </label>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                        เลือกวิธีคำนวณคอมมิชชั่นจากการดูดวง — ใช้ PV ตามระบบ MLM หรือจ่ายตรงตามจำนวนที่กำหนด
                    </p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        {{-- PV Mode --}}
                        <label class="relative cursor-pointer">
                            <input type="radio" name="fortune_commission_mode" value="pv"
                                   x-model="commissionMode" class="sr-only peer">
                            <div class="p-4 rounded-lg border-2 transition-all
                                        peer-checked:border-purple-500 peer-checked:bg-purple-50 dark:peer-checked:bg-purple-900/30
                                        border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700
                                        hover:border-purple-300 dark:hover:border-purple-600">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-lg">📊</span>
                                    <span class="font-bold text-gray-900 dark:text-white">PV Mode</span>
                                    <span x-show="commissionMode === 'pv'" class="ml-auto text-xs bg-purple-600 text-white px-2 py-0.5 rounded-full">เลือกอยู่</span>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    คำนวณผ่านระบบ MLM: (PV × Level% / 100) × commission_per_pv
                                </p>
                            </div>
                        </label>
                        {{-- Static Mode --}}
                        <label class="relative cursor-pointer">
                            <input type="radio" name="fortune_commission_mode" value="static"
                                   x-model="commissionMode" class="sr-only peer">
                            <div class="p-4 rounded-lg border-2 transition-all
                                        peer-checked:border-orange-500 peer-checked:bg-orange-50 dark:peer-checked:bg-orange-900/30
                                        border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700
                                        hover:border-orange-300 dark:hover:border-orange-600">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-lg">💵</span>
                                    <span class="font-bold text-gray-900 dark:text-white">ค่าแนะนำ (Static)</span>
                                    <span x-show="commissionMode === 'static'" class="ml-auto text-xs bg-orange-600 text-white px-2 py-0.5 rounded-full">เลือกอยู่</span>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    จ่ายค่าแนะนำให้ผู้แนะนำตรง (Level 1) เต็มจำนวน เช่น 10 บาท
                                </p>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Static Commission Amount (แสดงเมื่อเลือก static mode) --}}
                <div x-show="commissionMode === 'static'" x-transition x-cloak>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        💵 ค่าแนะนำ (บาท)
                    </label>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                        จำนวนเงินที่ผู้แนะนำตรงได้รับเมื่อมีคนมาดูดวงผ่านลิงก์ เช่น ตั้ง 10 บาท → ผู้แนะนำได้ 10 บาท/ครั้ง
                    </p>
                    <input type="number" name="fortune_static_commission_amount" step="0.01" min="0"
                           x-model="staticAmount"
                           class="w-full md:w-48 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-orange-500">
                </div>

                {{-- PV Value (แสดงเมื่อเลือก pv mode) --}}
                <div x-show="commissionMode === 'pv'" x-transition x-cloak>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        📊 ค่า PV (Override)
                    </label>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                        ถ้าเว้นว่าง (0) ระบบจะคำนวณ PV อัตโนมัติจาก <strong>ราคาดูดวง × global_pv_rate</strong> (ตั้งค่าใน MLM Settings)<br>
                        ใส่ค่าที่นี่เพื่อ override PV เฉพาะระบบดูดวง
                    </p>
                    <input type="number" name="fortune_pv_value" step="0.01" min="0"
                           x-model="pvValue" placeholder="0 = ใช้ auto (ราคา × pv_rate)"
                           class="w-full md:w-48 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500">
                </div>

                {{-- Commission Rate (แสดงเมื่อเลือก pv mode) --}}
                <div x-show="commissionMode === 'pv'" x-transition x-cloak>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            💱 อัตราคอมมิชชั่น
                        </label>
                    </div>
                    <div class="flex items-center gap-3 mb-2">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="fortune_use_global_commission_rate" value="1"
                                   x-model="useGlobal" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                        </label>
                        <span class="text-sm text-gray-700 dark:text-gray-300">ใช้อัตราคอมมิชชั่นกลาง (MlmGlobalSetting)</span>
                    </div>
                    <div x-show="!useGlobal" x-transition class="mt-3">
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">อัตราคอมมิชชั่นกำหนดเอง (ต่อ PV)</label>
                        <input type="number" name="fortune_custom_commission_per_pv" step="0.01" min="0"
                               x-model="customRate"
                               class="w-full md:w-48 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500">
                    </div>
                </div>

                {{-- Commission Preview --}}
                <div class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg border border-purple-200 dark:border-purple-700">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="text-sm font-bold text-purple-800 dark:text-purple-300">📈 ตัวอย่างการคำนวณคอมมิชชั่น</h4>
                        <button type="button" @click="calcPreview()"
                                :disabled="previewLoading"
                                class="px-4 py-1.5 text-xs bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition disabled:opacity-50">
                            <span x-show="!previewLoading">🔄 คำนวณ</span>
                            <span x-show="previewLoading">⏳ กำลังคำนวณ...</span>
                        </button>
                    </div>

                    {{-- แสดง error message ถ้าคำนวณไม่สำเร็จ --}}
                    <template x-if="previewError">
                        <div class="p-3 rounded-lg bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-sm text-red-700 dark:text-red-300">
                            ⚠️ <span x-text="previewError"></span>
                        </div>
                    </template>

                    <template x-if="preview">
                        <div class="space-y-2 text-sm">
                            {{-- โหมดที่ใช้ --}}
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">🎯 โหมด</span>
                                <span class="font-medium text-gray-900 dark:text-white"
                                      x-text="preview.mode === 'static' ? '💵 Static (จ่ายตรง)' : '📊 PV (ตาม MLM)'"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">💵 ราคาดูดวง</span>
                                <span class="font-medium text-gray-900 dark:text-white" x-text="preview.price + ' บาท'"></span>
                            </div>
                            {{-- PV mode: แสดงค่า PV + global_pv_rate + commission_per_pv --}}
                            <template x-if="preview.mode === 'pv'">
                                <div class="space-y-2">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">📊 PV Rate (บาท→PV)</span>
                                        <span class="font-medium text-gray-900 dark:text-white" x-text="'×' + preview.global_pv_rate"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">📊 ค่า PV</span>
                                        <span class="font-medium text-gray-900 dark:text-white">
                                            <span x-text="preview.pv_value + ' PV'"></span>
                                            <span class="text-xs text-gray-400 ml-1" x-text="preview.pv_source === 'auto' ? '(auto)' : '(override)'"></span>
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">💱 Commission/PV</span>
                                        <span class="font-medium text-gray-900 dark:text-white" x-text="preview.commission_per_pv + ' บาท'"></span>
                                    </div>
                                </div>
                            </template>
                            {{-- Static mode: แสดงค่าแนะนำ --}}
                            <template x-if="preview.mode === 'static'">
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">🤝 ค่าแนะนำ (ผู้แนะนำตรง)</span>
                                    <span class="font-medium text-orange-600 dark:text-orange-400" x-text="preview.static_amount + ' บาท'"></span>
                                </div>
                            </template>
                            <div class="border-t border-purple-200 dark:border-purple-700 my-2"></div>
                            {{-- PV mode: Level breakdown --}}
                            <template x-if="preview.mode === 'pv'">
                                <div class="space-y-1">
                                    <template x-for="(level, idx) in (preview.levels || []).slice(0, 5)" :key="idx">
                                        <div class="flex justify-between">
                                            <span class="text-gray-600 dark:text-gray-400" x-text="'📈 Level ' + level.level + ' (' + level.percentage + '%)'"></span>
                                            <span class="text-gray-900 dark:text-white" x-text="level.amount + ' บาท'"></span>
                                        </div>
                                    </template>
                                </div>
                            </template>
                            <div class="border-t border-purple-200 dark:border-purple-700 my-2"></div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">💸 รวมจ่ายคอมมิชชั่น</span>
                                <span class="font-medium text-red-600 dark:text-red-400" x-text="preview.total_commission + ' บาท'"></span>
                            </div>
                            <div class="flex justify-between p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                                <span class="font-bold text-green-800 dark:text-green-300">✅ กำไรสุทธิ</span>
                                <span class="font-bold text-green-800 dark:text-green-300" x-text="preview.net_profit + ' บาท (' + preview.profit_percentage + '%)'"></span>
                            </div>
                        </div>
                    </template>
                    <template x-if="!preview">
                        <p class="text-xs text-purple-600 dark:text-purple-400">กดปุ่ม "คำนวณ" เพื่อดู preview คอมมิชชั่น</p>
                    </template>
                </div>

                {{-- Custom Invite Message --}}
                @php
                    // ข้อความเชิญชวนเริ่มต้น (ใช้เมื่อแอดมินไม่ได้กำหนดเอง)
                    $defaultInviteMessage = '🌟 แชร์ลิงก์ให้เพื่อน เพื่อนมาดูดวง คุณได้คอมมิชชั่นทุกครั้งที่เพื่อนจ่ายเงิน ง่ายๆ ไม่ต้องขาย!';
                    $currentInviteMessage = old('fortune_affiliate_invite_message', $settings->fortune_affiliate_invite_message ?? '');
                @endphp
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        ✉️ ข้อความเชิญชวน (ไม่บังคับ)
                    </label>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                        ข้อความที่จะแสดงใน Flex Message เชิญชวน — เว้นว่างเพื่อใช้ข้อความเริ่มต้น
                    </p>

                    {{-- แสดง default text ให้เห็นก่อนแก้ไข --}}
                    <div class="mb-2 px-3 py-2 rounded-lg bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-700 text-xs text-purple-700 dark:text-purple-300">
                        <span class="font-semibold">ข้อความเริ่มต้น:</span>
                        {{ $defaultInviteMessage }}
                    </div>

                    <textarea name="fortune_affiliate_invite_message" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 text-sm"
                              placeholder="{{ $defaultInviteMessage }}">{{ $currentInviteMessage }}</textarea>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                        💡 พิมพ์ข้อความเพื่อแทนที่ข้อความเริ่มต้น หรือเว้นว่างเพื่อใช้ข้อความเริ่มต้นด้านบน
                    </p>
                </div>
            </div>
        </div>

        {{-- Submit Button --}}
        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.fortune.readings.index') }}"
               class="px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition">
                ดูประวัติการทำนาย
            </a>
            <button type="submit"
                    class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                💾 บันทึกการตั้งค่า
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function aiDiagnostic() {
    return {
        loading: false,
        result: null,
        fixingDb: false,
        dbFixResult: null,
        async runDiagnose() {
            this.loading = true;
            this.result = null;
            this.dbFixResult = null;

            try {
                const response = await fetch('{{ route("admin.fortune.settings.diagnose") }}', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                this.result = await response.json();
            } catch (error) {
                this.result = {
                    overall: 'error',
                    checks: {
                        connection: {
                            label: 'การเชื่อมต่อ',
                            status: 'error',
                            message: 'ไม่สามารถเรียก API ตรวจสอบได้: ' + error.message,
                        }
                    },
                    timestamp: new Date().toLocaleString('th-TH'),
                };
            } finally {
                this.loading = false;
            }
        },
        async fixDatabase() {
            if (!confirm('ต้องการรัน Migration เพื่อสร้างตารางที่ขาดหายไป?')) return;

            this.fixingDb = true;
            this.dbFixResult = null;

            try {
                const response = await fetch('{{ route("admin.fortune.settings.run-migrations") }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                this.dbFixResult = await response.json();

                // ถ้าสำเร็จ ให้ตรวจเช็คใหม่อัตโนมัติ
                if (this.dbFixResult.success) {
                    setTimeout(() => this.runDiagnose(), 1500);
                }
            } catch (error) {
                this.dbFixResult = {
                    success: false,
                    message: 'เกิดข้อผิดพลาด: ' + error.message,
                };
            } finally {
                this.fixingDb = false;
            }
        }
    };
}

function fortuneSettings() {
    return {
        commentEngagementEnabled: {{ $settings->comment_engagement_enabled ? 'true' : 'false' }},
        commentEngagementMode: '{{ old('comment_engagement_mode', $settings->comment_engagement_mode ?? 'ai') }}',
        async testAI() {
            const btn = event.target.closest('button');
            const originalText = btn.innerHTML;
            btn.innerHTML = '⏳ กำลังทดสอบ...';
            btn.disabled = true;

            try {
                const response = await fetch('{{ route("admin.fortune.settings.test-ai") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const result = await response.json();

                let debugInfo = '';
                if (result.debug) {
                    debugInfo = '\n\nDebug Info:'
                        + '\nProvider: ' + (result.debug.provider || '-')
                        + '\nModel: ' + (result.debug.model || '-')
                        + '\nHas API Key: ' + (result.debug.has_api_key !== undefined ? result.debug.has_api_key : '-')
                        + (result.debug.tokens_used ? '\nTokens Used: ' + result.debug.tokens_used : '')
                        + (result.debug.response_length ? '\nResponse Length: ' + result.debug.response_length + ' chars' : '')
                        + (result.debug.api_key_prefix ? '\nAPI Key Prefix: ' + result.debug.api_key_prefix : '')
                        + (result.debug.use_global !== undefined ? '\nUse Global: ' + result.debug.use_global : '');
                }

                if (result.success) {
                    alert('✅ ' + result.message + debugInfo);
                } else {
                    alert('❌ ' + result.message + debugInfo);
                }
            } catch (error) {
                alert('❌ เกิดข้อผิดพลาด: ' + error.message);
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }
    };
}
</script>
@endpush
@endsection
