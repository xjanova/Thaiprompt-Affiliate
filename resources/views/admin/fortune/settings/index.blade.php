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
                            <div>• หากมี <strong>Gemini API Key</strong> จะใช้ Gemini</div>
                            <div>• หากมี <strong>Claude API Key</strong> จะใช้ Claude (via OpenRouter)</div>
                            <div>• หากมี <strong>OpenAI API Key</strong> จะใช้ GPT (via OpenRouter)</div>
                        </div>
                        <p class="mt-2 text-xs text-amber-600 dark:text-amber-400">
                            💡 หากต้องการตั้งค่าแยกเฉพาะระบบดูดวง ให้ปิดตัวเลือกนี้
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
                        <option value="gemini" {{ $settings->ai_provider === 'gemini' ? 'selected' : '' }}>Gemini (Google) - แนะนำ</option>
                        <option value="groq" {{ $settings->ai_provider === 'groq' ? 'selected' : '' }}>Groq - เร็วที่สุด</option>
                        <option value="qwen" {{ $settings->ai_provider === 'qwen' ? 'selected' : '' }}>Qwen (Alibaba)</option>
                        <option value="openrouter" {{ $settings->ai_provider === 'openrouter' ? 'selected' : '' }}>OpenRouter</option>
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
        </div> {{-- End of AI Settings card --}}

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
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                📝 เทมเพลตคำทำนาย
            </h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        🔮 Prompt ดูดวงพื้นฐาน (สั้น กระชับ)
                    </label>
                    <textarea name="basic_prompt_template" rows="4"
                              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 font-mono text-sm"
                              placeholder="ว่างไว้ = ใช้ค่าเริ่มต้น (ทำนายสั้นๆ 2-3 ประโยค)">{{ old('basic_prompt_template', $settings->basic_prompt_template ?? '') }}</textarea>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">ตัวแปร: {user_profile}, {user_posts}, {questions}, {birth_date_section}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        🌟 Prompt ดูดวงเชิงลึก (ละเอียด ลึกซึ้ง)
                    </label>
                    <textarea name="deep_prompt_template" rows="6"
                              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 font-mono text-sm"
                              placeholder="ว่างไว้ = ใช้ค่าเริ่มต้น (ทำนายละเอียดพร้อมสีมงคล เลขมงคล ฯลฯ)">{{ old('deep_prompt_template', $settings->deep_prompt_template ?? '') }}</textarea>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">ตัวแปร: {user_profile}, {user_posts}, {questions}, {birth_date_section}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ข้อความหลังทดลองดูฟรี (แนะนำจ่ายเงิน)
                    </label>
                    <textarea name="try_before_buy_message" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                              placeholder="ว่างไว้ = ใช้ค่าเริ่มต้น">{{ old('try_before_buy_message', $settings->try_before_buy_message ?? '') }}</textarea>
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
        async runDiagnose() {
            this.loading = true;
            this.result = null;

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
