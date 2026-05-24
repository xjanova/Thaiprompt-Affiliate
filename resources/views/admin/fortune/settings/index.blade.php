@extends('layouts.admin-v3')

@section('title', 'ตั้งค่าระบบดูดวง Facebook')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="fortuneSettings()" x-init="initTabs()">
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

    {{-- ===== Facebook App Review Guide ===== --}}
    <div class="bg-gradient-to-br from-gray-800 to-gray-900 dark:from-gray-800 dark:to-gray-900 rounded-xl shadow-lg p-6 mb-6 border border-gray-700" x-data="{ showGuide: false }">
        <div class="flex items-center justify-between cursor-pointer" @click="showGuide = !showGuide">
            <div class="flex items-center gap-3">
                <span class="text-2xl">📋</span>
                <div>
                    <h3 class="text-lg font-bold text-white">Facebook App Review Guide</h3>
                    <p class="text-sm text-gray-400">สถานะ permissions ที่ต้องส่งตรวจสอบ + หน้า Demo สำหรับ reviewer</p>
                </div>
            </div>
            <svg class="w-5 h-5 text-gray-400 transition-transform" :class="showGuide && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </div>

        <div x-show="showGuide" x-collapse class="mt-5">
            {{-- Permission Status Cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
                <div class="bg-gray-700/50 rounded-lg p-4 border border-gray-600">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-lg">📋</span>
                        <span class="text-xs font-mono text-indigo-400">pages_show_list</span>
                    </div>
                    <p class="text-sm text-gray-300 font-semibold">เลือก Page</p>
                    <p class="text-xs text-gray-500 mt-1">แสดงรายการ Pages ที่จัดการ</p>
                    @if($settings->facebook_page_id)
                        <span class="inline-block mt-2 text-xs bg-green-900/50 text-green-400 px-2 py-0.5 rounded">✅ เชื่อมต่อแล้ว</span>
                    @else
                        <span class="inline-block mt-2 text-xs bg-yellow-900/50 text-yellow-400 px-2 py-0.5 rounded">⚠️ ยังไม่เชื่อมต่อ</span>
                    @endif
                </div>

                <div class="bg-gray-700/50 rounded-lg p-4 border border-gray-600">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-lg">⚙️</span>
                        <span class="text-xs font-mono text-violet-400">pages_manage_metadata</span>
                    </div>
                    <p class="text-sm text-gray-300 font-semibold">ตั้งค่า Messenger</p>
                    <p class="text-xs text-gray-500 mt-1">Get Started, เมนู, ข้อความต้อนรับ</p>
                    <span class="inline-block mt-2 text-xs bg-blue-900/50 text-blue-400 px-2 py-0.5 rounded">🔧 ตั้งค่าได้ที่หน้า Page Management</span>
                </div>

                <div class="bg-gray-700/50 rounded-lg p-4 border border-gray-600">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-lg">💬</span>
                        <span class="text-xs font-mono text-blue-400">pages_messaging</span>
                    </div>
                    <p class="text-sm text-gray-300 font-semibold">ส่งข้อความดูดวง</p>
                    <p class="text-xs text-gray-500 mt-1">Bot ตอบผลดูดวงผ่าน Messenger</p>
                    @if($settings->is_enabled)
                        <span class="inline-block mt-2 text-xs bg-green-900/50 text-green-400 px-2 py-0.5 rounded">✅ เปิดใช้งาน</span>
                    @else
                        <span class="inline-block mt-2 text-xs bg-red-900/50 text-red-400 px-2 py-0.5 rounded">⛔ ปิดอยู่</span>
                    @endif
                </div>

                <div class="bg-gray-700/50 rounded-lg p-4 border border-gray-600">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-lg">🗨️</span>
                        <span class="text-xs font-mono text-emerald-400">pages_read_engagement</span>
                    </div>
                    <p class="text-sm text-gray-300 font-semibold">ตอบคอมเม้นต์</p>
                    <p class="text-xs text-gray-500 mt-1">Auto-reply เมื่อมีคนคอมเม้นต์โพสต์</p>
                    @if($settings->comment_engagement_enabled)
                        <span class="inline-block mt-2 text-xs bg-green-900/50 text-green-400 px-2 py-0.5 rounded">✅ เปิดใช้งาน</span>
                    @else
                        <span class="inline-block mt-2 text-xs bg-yellow-900/50 text-yellow-400 px-2 py-0.5 rounded">⚠️ ปิดอยู่</span>
                    @endif
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('facebook-app-review-demo') }}"
                   target="_blank"
                   class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg transition">
                    🌐 เปิดหน้า Demo สำหรับ Facebook Reviewer
                </a>
                <a href="https://developers.facebook.com/apps/664172615253513/app-review/submissions/"
                   target="_blank"
                   class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition">
                    📝 ไปหน้า Facebook App Review
                </a>
                @if(Route::has('admin.fortune.channels.facebook-page-management'))
                <a href="{{ route('admin.fortune.channels.facebook-page-management') }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-600 hover:bg-gray-500 text-white text-sm font-semibold rounded-lg transition">
                    ⚙️ จัดการ Facebook Page
                </a>
                @endif
            </div>

            {{-- Video Recording Guide --}}
            <div class="mt-5 bg-gray-700/30 rounded-lg p-4 border border-gray-600">
                <h4 class="text-sm font-bold text-gray-300 mb-3">📹 คู่มือบันทึกวิดีโอสำหรับ App Review</h4>
                <div class="space-y-2 text-xs text-gray-400">
                    <p><strong class="text-indigo-400">Video 1 — pages_show_list:</strong> เปิดหน้า Settings → แสดง Page ID ที่เชื่อมต่อ → ไปหน้า Page Management → แสดงรายการ Pages</p>
                    <p><strong class="text-violet-400">Video 2 — pages_manage_metadata:</strong> เปิดหน้า Page Management → กด Setup Messenger Profile → แสดง Get Started Button + Persistent Menu</p>
                    <p><strong class="text-blue-400">Video 3 — pages_messaging:</strong> เปิด Messenger → ส่ง "ดูดวง" → Bot ถามวันเกิด → เลือกวัน → ได้ผลดูดวง</p>
                    <p><strong class="text-emerald-400">Video 4 — pages_read_engagement:</strong> เปิด Facebook Page → คอมเม้นต์โพสต์ → Bot ตอบคอมเม้นต์ + ส่ง DM → แสดง engagement stats</p>
                </div>
            </div>
        </div>
    </div>

    {{-- 🆕 (2026-05-13) Tab Navigation — จัดกลุ่มที่เชื่อมโยงกันให้อยู่ tab เดียวกัน --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg mb-6 sticky top-0 z-20 backdrop-blur-md bg-opacity-95 dark:bg-opacity-95">
        <div class="flex flex-wrap gap-1 p-2 border-b border-gray-200 dark:border-gray-700">
            <button type="button" @click="switchTab('ai')"
                    :class="activeTab === 'ai' ? 'bg-gradient-to-r from-blue-500 to-indigo-600 text-white shadow' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600'"
                    class="px-4 py-2 rounded-lg font-medium text-sm transition flex items-center gap-2">
                🤖 <span>AI Models</span>
            </button>
            <button type="button" @click="switchTab('service')"
                    :class="activeTab === 'service' ? 'bg-gradient-to-r from-purple-500 to-pink-600 text-white shadow' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600'"
                    class="px-4 py-2 rounded-lg font-medium text-sm transition flex items-center gap-2">
                🔮 <span>Fortune Service</span>
            </button>
            <button type="button" @click="switchTab('engagement')"
                    :class="activeTab === 'engagement' ? 'bg-gradient-to-r from-emerald-500 to-teal-600 text-white shadow' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600'"
                    class="px-4 py-2 rounded-lg font-medium text-sm transition flex items-center gap-2">
                💬 <span>Engagement</span>
            </button>
            <button type="button" @click="switchTab('tts')"
                    :class="activeTab === 'tts' ? 'bg-gradient-to-r from-amber-500 to-orange-600 text-white shadow' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600'"
                    class="px-4 py-2 rounded-lg font-medium text-sm transition flex items-center gap-2">
                🎙️ <span>TTS / Voice</span>
            </button>
            <button type="button" @click="switchTab('platform')"
                    :class="activeTab === 'platform' ? 'bg-gradient-to-r from-rose-500 to-red-600 text-white shadow' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600'"
                    class="px-4 py-2 rounded-lg font-medium text-sm transition flex items-center gap-2">
                ⚙️ <span>Platform & Payment</span>
            </button>
        </div>
        <div class="px-4 py-2 text-xs text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-900/30">
            <span x-show="activeTab === 'ai'">🤖 <strong>AI Models</strong> — AI Provider · AI Chat · Sensitive AI · Bill Psychology · Celtic Premium · ตรวจเช็คระบบ AI</span>
            <span x-show="activeTab === 'service'">🔮 <strong>Fortune Service</strong> — คำทำนาย Freemium · สมัครสมาชิก · เทมเพลตคำทำนาย · Affiliate · พื้นฐาน</span>
            <span x-show="activeTab === 'engagement'">💬 <strong>Engagement</strong> — ตอบคอมเม้นต์ · Satisfaction · เชิญกลุ่ม FB · กรองสแปม</span>
            <span x-show="activeTab === 'tts'">🎙️ <strong>TTS / Voice</strong> — เสียงสรุปคำทำนาย · Voice Library</span>
            <span x-show="activeTab === 'platform'">⚙️ <strong>Platform & Payment</strong> — Facebook setup · Stripe · สถานะระบบ</span>
        </div>
    </div>

    <form action="{{ route('admin.fortune.settings.update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        {{-- Status Card --}}
        <div class="bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl shadow-lg p-6 mb-6 text-white" data-fortune-tab="platform">
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
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6" x-data="aiDiagnostic()" data-fortune-tab="ai">
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
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6" data-fortune-tab="platform">
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

        {{-- 🎯 (2026-05-13) AI Provider Info Card — Pool-first architecture
             เดิม: section นี้มี toggle/dropdown/key field ที่ "งง" — user ตั้ง openai แต่ระบบใช้ gemini
                  เพราะ logic เก่าตรวจ use_global_ai_settings → vote จาก global key (Gemini ก่อน)
             ใหม่: ไม่ต้องตั้ง provider/model/key ในหน้านี้ — Pool เป็น single source of truth
                  จัดที่ /admin/ai-api-keys แห่งเดียว: เพิ่ม/ลบ key พร้อม purpose
                  ระบบเลือก key ตาม purpose (prediction_deep / prediction_celtic / chat / sensitive)
                  และเลือก provider เองจาก Pool — admin ไม่ต้องตั้งซ้ำ --}}
        <div class="bg-gradient-to-br from-indigo-50 to-blue-50 dark:from-indigo-900/20 dark:to-blue-900/20 border-2 border-indigo-200 dark:border-indigo-700 rounded-xl shadow-lg p-6 mb-6" data-fortune-tab="ai">
            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div class="flex-1 min-w-0">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2 flex items-center gap-2">
                        🤖 AI Provider — ควบคุมจาก Pool
                    </h3>
                    <p class="text-sm text-gray-700 dark:text-gray-300 mb-3">
                        ระบบ Fortune ใช้ <strong>AI API Key Pool</strong> เป็นแหล่งเดียวในการเลือก provider/model/key —
                        ไม่ต้องตั้งค่าซ้ำในหน้านี้.
                        Pool เลือก key ที่ <strong>priority สูง + load น้อย</strong> โดยอัตโนมัติ
                        ตาม <strong>purpose</strong> ที่ admin มาร์คไว้:
                    </p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-xs mb-3">
                        <div class="bg-white/60 dark:bg-gray-800/40 rounded-lg px-3 py-2 border border-gray-200 dark:border-gray-700">
                            <div class="font-semibold text-indigo-700 dark:text-indigo-300">💬 chat</div>
                            <div class="text-gray-600 dark:text-gray-400">แชทกับลูกค้า (สนทนาทั่วไป)</div>
                        </div>
                        <div class="bg-white/60 dark:bg-gray-800/40 rounded-lg px-3 py-2 border border-gray-200 dark:border-gray-700">
                            <div class="font-semibold text-blue-700 dark:text-blue-300">🌟 prediction_deep</div>
                            <div class="text-gray-600 dark:text-gray-400">ทำนาย Deep 39฿ (เน้น speed)</div>
                        </div>
                        <div class="bg-white/60 dark:bg-gray-800/40 rounded-lg px-3 py-2 border border-gray-200 dark:border-gray-700">
                            <div class="font-semibold text-purple-700 dark:text-purple-300">💎 prediction_celtic</div>
                            <div class="text-gray-600 dark:text-gray-400">ทำนาย Celtic 99฿ (เน้นคุณภาพ)</div>
                        </div>
                        <div class="bg-white/60 dark:bg-gray-800/40 rounded-lg px-3 py-2 border border-gray-200 dark:border-gray-700">
                            <div class="font-semibold text-rose-700 dark:text-rose-300">🌟 sensitive</div>
                            <div class="text-gray-600 dark:text-gray-400">Pro Session (Bill + Celtic Premium)</div>
                        </div>
                        <div class="bg-white/60 dark:bg-gray-800/40 rounded-lg px-3 py-2 border border-gray-200 dark:border-gray-700">
                            <div class="font-semibold text-emerald-700 dark:text-emerald-300">🎁 free_card</div>
                            <div class="text-gray-600 dark:text-gray-400">ทำนายฟรี 1 ใบ (หลัง DM)</div>
                        </div>
                        <div class="bg-white/60 dark:bg-gray-800/40 rounded-lg px-3 py-2 border border-gray-200 dark:border-gray-700">
                            <div class="font-semibold text-gray-700 dark:text-gray-300">⚙️ any</div>
                            <div class="text-gray-600 dark:text-gray-400">ใช้ได้ทุก purpose (default fallback)</div>
                        </div>
                    </div>
                    <p class="text-xs text-amber-700 dark:text-amber-300 mb-3 leading-relaxed">
                        💡 <strong>Fallback hierarchy</strong>: ถ้า admin มาร์ค key 'prediction_celtic' → จะใช้ก่อน,
                        ถ้าไม่มี → ใช้ 'prediction' → ใช้ 'any' → fallback ไป legacy settings
                        (เพื่อกันบิลเก่า)
                    </p>
                </div>
                <div class="flex flex-col gap-2">
                    <a href="{{ route('admin.ai-api-keys.index') }}"
                       class="inline-flex items-center gap-2 px-5 py-3 bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-700 hover:to-blue-700 text-white text-sm font-bold rounded-lg shadow-md transition whitespace-nowrap">
                        🔑 จัดการ Pool
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                    </a>
                    @if(Route::has('admin.fortune.playground'))
                    <a href="{{ route('admin.fortune.playground') }}"
                       class="inline-flex items-center gap-2 px-5 py-2 bg-purple-100 hover:bg-purple-200 dark:bg-purple-900/30 dark:hover:bg-purple-900/50 text-purple-800 dark:text-purple-300 text-xs font-semibold rounded-lg transition whitespace-nowrap">
                        🎮 ทดสอบ Playground
                    </a>
                    @endif
                    @if(Route::has('admin.ai-api-keys.usage-dashboard'))
                    <a href="{{ route('admin.ai-api-keys.usage-dashboard') }}"
                       class="inline-flex items-center gap-2 px-5 py-2 bg-amber-100 hover:bg-amber-200 dark:bg-amber-900/30 dark:hover:bg-amber-900/50 text-amber-800 dark:text-amber-300 text-xs font-semibold rounded-lg transition whitespace-nowrap">
                        📊 ดู Usage Dashboard
                    </a>
                    @endif
                </div>
            </div>

            {{-- 🎯 Strict provider mode — เก็บไว้สำหรับ admin ที่ต้องการ lock provider
                 logic: ถ้าเปิด → ระบบใช้ provider ของ Pool first key เท่านั้น ไม่ fallback ไป provider อื่น
                 (กัน Pool คนละ provider ผสม → คำทำนายต่าง provider ต่าง tone) --}}
            <div class="mt-4 pt-4 border-t border-indigo-200 dark:border-indigo-700">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="hidden" name="prediction_strict_provider" value="0">
                    <input type="checkbox" name="prediction_strict_provider" value="1"
                           {{ old('prediction_strict_provider', $settings->prediction_strict_provider ?? false) ? 'checked' : '' }}
                           class="mt-1 w-5 h-5 rounded text-purple-600 focus:ring-purple-500 focus:ring-2">
                    <div class="flex-1">
                        <p class="font-semibold text-gray-900 dark:text-white text-sm">
                            🎯 Lock Provider — ใช้แค่ provider ของ key แรกใน Pool (ไม่ fallback ไป provider อื่น)
                        </p>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 leading-relaxed">
                            <b>เปิด</b> = ถ้า Pool first key เป็น OpenAI → ใช้แค่ OpenAI keys, fail แล้ว fail ไม่สลับไป Gemini/Groq.<br>
                            <b>ปิด (default)</b> = fallback ทุก provider ใน Pool — ลูกค้าได้คำทำนายแน่นอน แม้ provider แรกพัง
                        </p>
                    </div>
                </label>
            </div>

            {{-- ✅ Hidden — backward compat: เก็บค่าเดิมใน DB ไว้
                 use_global_ai_settings=false (ใช้ค่าจาก Pool, ไม่ vote global)
                 ai_provider/ai_model/ai_api_key — เก็บค่าเดิมไว้ใน DB ถ้ามี (legacy fallback) --}}
            <input type="hidden" name="use_global_ai_settings" value="0">
        </div>

        {{-- (2026-05-13) Legacy AI Settings card — ถูกแทนด้วย Pool-first info card ด้านบน --}}

        {{-- AI Chat ทั่วไป (สนทนาอัจฉริยะ) --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6" data-fortune-tab="ai"
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

                {{-- 📦 (2026-05-20 Phase 4) Message Debounce — รวมข้อความที่ลูกค้าพิมพ์ติด ๆ --}}
                <div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-700 rounded-lg p-4">
                    <label class="block text-sm font-bold text-purple-700 dark:text-purple-300 mb-2">
                        📦 Message Debounce (วินาที)
                        <span class="text-xs font-normal text-purple-600 dark:text-purple-400">— รอรวมข้อความที่ลูกค้าพิมพ์ติด ๆ ก่อนตอบ</span>
                    </label>
                    <input type="number" name="message_debounce_seconds"
                           value="{{ old('message_debounce_seconds', $settings->message_debounce_seconds ?? 3) }}"
                           min="0" max="30" step="1"
                           class="w-full px-4 py-2 border border-purple-300 dark:border-purple-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500">
                    <div class="text-xs text-gray-600 dark:text-gray-400 mt-2 space-y-1">
                        <p>💡 <strong>0 = ปิด feature</strong> (บอทตอบทันทีทุกข้อความ — behavior เดิม)</p>
                        <p>💡 <strong>3-5 วินาที = แนะนำ</strong> — ลูกค้าพิมพ์ติด ๆ จะรวมเป็นข้อความเดียวก่อนตอบ</p>
                        <p>💡 <strong>10+ วินาที</strong> — รอรวมเยอะขึ้น แต่ลูกค้าอาจคิดว่าบอทช้า</p>
                        <p class="text-amber-600 dark:text-amber-400">⚠️ ใช้กับ chat ปกติ + Celtic Q2+ (Q1 ตอบทันที / payment/command bypass อัตโนมัติ)</p>
                    </div>
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

        {{-- ===== 🌟 Sensitive AI Mode (2026-05-07) ===== --}}
        @php
            $sKeys = $sensitiveKeys ?? [];
            $hasSensitiveKey = count($sKeys) > 0;
            $lockedKeyId = old('sensitive_ai_pool_key_id', $settings->sensitive_ai_pool_key_id);
        @endphp
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6" data-fortune-tab="ai"
             x-data="sensitiveAiPanel(@js($sKeys), @js($hasSensitiveKey), @js($lockedKeyId))">

            {{-- Header + ปุ่ม collapse คำอธิบาย --}}
            <div class="flex items-center justify-between mb-4" x-data="{ showHelp: false }">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    🌟 Sensitive AI Mode
                    <span class="text-xs font-normal text-purple-600 dark:text-purple-400">(จิตวิทยาขั้นสูง — สลับ Pro model อัตโนมัติ)</span>
                </h3>
                <button type="button" @click="showHelp = !showHelp"
                        class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                    <span x-show="!showHelp">ℹ️ ดูคำอธิบาย</span>
                    <span x-show="showHelp" x-cloak>ปิดคำอธิบาย</span>
                </button>
            </div>

            <div x-show="showHelp" x-cloak x-collapse
                 class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-700 rounded-lg p-4 mb-4">
                <p class="text-sm text-purple-900 dark:text-purple-200 mb-2 font-semibold">
                    🧠 ระบบนี้ทำอะไร?
                </p>
                <ul class="text-xs text-purple-800 dark:text-purple-300 space-y-1 list-disc list-inside">
                    <li>เมื่อบอทเจอลูกค้า <strong>อารมณ์รุนแรง</strong> / คำหยาบ / หัวข้อหนัก (ตาย/ป่วย/หย่า) → สลับใช้ <strong>Pro model</strong> (Gemini Pro / GPT-5+)</li>
                    <li>เมื่อบริบทเย็นลง → กลับใช้ default model อัตโนมัติ (ไม่ sticky)</li>
                    <li>ใช้ <strong>จิตวิทยาขั้นสูง</strong> — ต้อนแต่ไม่ด่า, ไม้แข็งแต่สุภาพ</li>
                    <li>ตรวจจับ <strong>off-topic</strong> (ถามนอกเรื่องดูดวง) → strike นับ → action ตามที่ตั้ง</li>
                    <li>มี <strong>budget cap</strong> — กัน abuse / กัน spend เกิน</li>
                </ul>
                <p class="text-xs text-purple-700 dark:text-purple-300 mt-3">
                    ⚠️ ก่อนเปิดใช้งาน: เพิ่ม API Key ของ Pro model
                    (เช่น Gemini Pro) ใน <a href="{{ route('admin.ai-api-keys.index') ?? '#' }}" class="underline font-semibold">AI API Keys</a>
                    แล้วตั้ง <strong>purpose = sensitive</strong>
                </p>
            </div>

            {{-- 🌟 (2026-05-08) Block toggle ถ้าไม่มี key — แสดง warning + link เพิ่ม key --}}
            @unless($hasSensitiveKey)
                <div class="mb-5 p-4 bg-red-50 dark:bg-red-900/20 border-2 border-red-300 dark:border-red-700 rounded-lg">
                    <div class="flex items-start gap-3">
                        <span class="text-2xl">⛔</span>
                        <div class="flex-1">
                            <h4 class="font-bold text-red-900 dark:text-red-200">ยังไม่มี key purpose='sensitive' ใน Account Pool</h4>
                            <p class="text-sm text-red-800 dark:text-red-300 mt-1">
                                Sensitive AI Mode <strong>เปิดไม่ได้</strong> จนกว่าจะเพิ่ม API key ของ Pro model
                                แล้วตั้ง <code class="bg-red-100 dark:bg-red-800/50 px-2 py-0.5 rounded">purpose = sensitive</code>
                            </p>
                            <a href="{{ route('admin.ai-api-keys.index') ?? '#' }}"
                               target="_blank"
                               class="inline-block mt-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm rounded-lg">
                                🔑 ไปเพิ่ม key ที่ Account Pool
                            </a>
                        </div>
                    </div>
                </div>
            @endunless

            {{-- Mode toggle (off / paid_only / all) --}}
            <div class="mb-5">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    🎚️ ขอบเขตการใช้งาน
                    @if(! $hasSensitiveKey)
                        <span class="text-xs text-red-600 ml-2">(disabled — ไม่มี key)</span>
                    @endif
                </label>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3" :class="!hasSensitiveKey ? 'opacity-60 pointer-events-none' : ''">
                    <label class="flex items-start p-3 rounded-lg border-2 cursor-pointer transition"
                           :class="sensitiveMode === 'off' ? 'border-gray-400 bg-gray-50 dark:bg-gray-700' : 'border-gray-200 dark:border-gray-600 hover:border-gray-300'">
                        <input type="radio" name="sensitive_ai_mode" value="off" x-model="sensitiveMode" class="mt-1 mr-3">
                        <div>
                            <div class="font-semibold text-gray-900 dark:text-white">🔒 ปิด</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">ไม่ใช้ Pro model เลย</div>
                        </div>
                    </label>
                    <label class="flex items-start p-3 rounded-lg border-2 cursor-pointer transition"
                           :class="sensitiveMode === 'paid_only' ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/30' : 'border-gray-200 dark:border-gray-600 hover:border-gray-300'">
                        <input type="radio" name="sensitive_ai_mode" value="paid_only" x-model="sensitiveMode"
                               :disabled="!hasSensitiveKey" class="mt-1 mr-3">
                        <div>
                            <div class="font-semibold text-gray-900 dark:text-white">💎 เฉพาะทำนายเสียเงิน <span class="text-xs text-purple-600">(แนะนำ)</span></div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Deep 39฿ + Celtic 99฿ เท่านั้น (chat ฟรียังใช้ Groq)</div>
                        </div>
                    </label>
                    <label class="flex items-start p-3 rounded-lg border-2 cursor-pointer transition"
                           :class="sensitiveMode === 'all' ? 'border-orange-500 bg-orange-50 dark:bg-orange-900/30' : 'border-gray-200 dark:border-gray-600 hover:border-gray-300'">
                        <input type="radio" name="sensitive_ai_mode" value="all" x-model="sensitiveMode"
                               :disabled="!hasSensitiveKey" class="mt-1 mr-3">
                        <div>
                            <div class="font-semibold text-gray-900 dark:text-white">🌐 ทั่วทั้งบอท</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">รวมแชทธรรมดาด้วย ⚠️ ระวัง budget</div>
                        </div>
                    </label>
                </div>
            </div>

            {{-- 🌟 (2026-05-08) Lock specific key dropdown + test button --}}
            @if($hasSensitiveKey)
            <div x-show="sensitiveMode !== 'off'" x-cloak class="mb-5 p-4 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-700 rounded-lg">
                <label class="block text-sm font-semibold text-purple-900 dark:text-purple-200 mb-2">
                    🔑 เลือก Key (จาก Account Pool)
                </label>
                <p class="text-xs text-purple-700 dark:text-purple-300 mb-3">
                    Lock key ตัวเดียวเพื่อ predictable cost — หรือเว้นเป็น "Pool rotation (auto)" ให้ระบบสุ่มเลือก
                </p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <select name="sensitive_ai_pool_key_id" x-model="lockedKeyId"
                            class="md:col-span-2 px-4 py-2 border border-purple-300 dark:border-purple-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <option value="">🔄 Pool rotation (auto — ใช้ key ใดก็ได้ที่ purpose=sensitive)</option>
                        @foreach($sKeys as $k)
                            <option value="{{ $k['id'] }}" {{ $lockedKeyId == $k['id'] ? 'selected' : '' }}>
                                🔒 {{ $k['name'] }} ({{ $k['provider'] }} / {{ $k['model'] ?? '?' }})
                                @if($k['is_critical']) ⚠️CRITICAL @endif
                                @if($k['is_disabled']) ⏰DISABLED @endif
                            </option>
                        @endforeach
                    </select>
                    <button type="button" @click="testKey()" :disabled="testing"
                            class="px-4 py-2 bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white rounded-lg text-sm font-semibold">
                        <span x-show="!testing">🧪 ทดสอบเสียงนี้</span>
                        <span x-show="testing" x-cloak>⏳ กำลังทดสอบ...</span>
                    </button>
                </div>

                {{-- Selected key details --}}
                <template x-if="selectedKey()">
                    <div class="mt-3 p-3 bg-white dark:bg-gray-800 rounded border border-purple-200 dark:border-purple-700 text-xs">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                            <div><span class="text-gray-500">Provider:</span> <span class="font-mono" x-text="selectedKey().provider"></span></div>
                            <div><span class="text-gray-500">Model:</span> <span class="font-mono" x-text="selectedKey().model || '?'"></span></div>
                            <div><span class="text-gray-500">Tokens today:</span> <span class="font-mono" x-text="selectedKey().tokens_used_today.toLocaleString()"></span></div>
                            <div><span class="text-gray-500">Errors:</span> <span class="font-mono" x-text="selectedKey().consecutive_errors"></span></div>
                        </div>
                    </div>
                </template>

                {{-- Test result --}}
                <div x-show="testResult" x-cloak x-transition class="mt-3 p-3 rounded-lg text-sm"
                     :class="testResult && testResult.success ? 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-900 dark:text-emerald-100' : 'bg-red-100 dark:bg-red-900/40 text-red-900 dark:text-red-100'">
                    <template x-if="testResult && testResult.success">
                        <div>
                            <div class="font-semibold mb-1">✅ ใช้งานได้! (latency: <span x-text="testResult.response_time_ms"></span>ms, tokens: <span x-text="testResult.tokens_used"></span>)</div>
                            <div class="text-xs mb-2 opacity-80">
                                ใช้ key: <code x-text="(testResult.key_info && testResult.key_info.key_name) || '(pool)'"></code>
                                · <span x-text="testResult.key_info?.provider || '?'"></span> / <span x-text="testResult.key_info?.model || '?'"></span>
                            </div>
                            <div class="bg-white/50 dark:bg-black/20 p-2 rounded text-xs whitespace-pre-wrap" x-text="testResult.response"></div>
                        </div>
                    </template>
                    <template x-if="testResult && !testResult.success">
                        <div>
                            <div class="font-semibold">❌ ทดสอบไม่ผ่าน</div>
                            <div class="text-xs mt-1" x-text="testResult.error"></div>
                        </div>
                    </template>
                </div>
            </div>
            @endif

            {{-- ส่วน config (ซ่อนเมื่อ off) --}}
            <div x-show="sensitiveMode !== 'off'" x-cloak x-collapse>

                {{-- Detection mode --}}
                <div class="mb-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            🔍 โหมดตรวจจับ
                        </label>
                        <select name="sensitive_detection_mode" x-model="detectionMode"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="heuristic">🚀 Heuristic (regex เท่านั้น — เร็ว ฟรี)</option>
                            <option value="hybrid">⭐ Hybrid (regex + Groq classifier — แนะนำ)</option>
                            <option value="hybrid_with_brain">🧠 Hybrid + ObsidianX (Phase 3 — ยังไม่เปิดใช้)</option>
                        </select>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1" x-show="detectionMode === 'hybrid'">
                            Groq llama-8b ฟรี — เพิ่ม latency ~100ms / message
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            🤖 Classifier Provider/Model
                        </label>
                        <div class="flex gap-2">
                            <select name="sensitive_classifier_provider"
                                    class="w-1/3 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                <option value="groq" {{ ($settings->sensitive_classifier_provider ?? 'groq') === 'groq' ? 'selected' : '' }}>Groq</option>
                                <option value="openai" {{ ($settings->sensitive_classifier_provider ?? '') === 'openai' ? 'selected' : '' }}>OpenAI</option>
                                <option value="gemini" {{ ($settings->sensitive_classifier_provider ?? '') === 'gemini' ? 'selected' : '' }}>Gemini</option>
                            </select>
                            <input type="text" name="sensitive_classifier_model"
                                   value="{{ old('sensitive_classifier_model', $settings->sensitive_classifier_model ?? 'llama-3.3-70b-versatile') }}"
                                   class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                   placeholder="llama-3.3-70b-versatile">
                        </div>
                    </div>
                </div>

                {{-- Pro Provider/Model --}}
                <div class="mb-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            🧠 Pro Model Provider
                        </label>
                        <select name="sensitive_provider"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            @foreach(['gemini' => 'Gemini', 'openai' => 'OpenAI (GPT-5/4o)', 'anthropic' => 'Anthropic Claude', 'openrouter' => 'OpenRouter', 'grok' => 'Grok'] as $val => $label)
                                <option value="{{ $val }}" {{ ($settings->sensitive_provider ?? 'gemini') === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            ✨ Pro Model
                        </label>
                        <input type="text" name="sensitive_model"
                               value="{{ old('sensitive_model', $settings->sensitive_model ?? 'gemini-3.1-pro-preview') }}"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                               placeholder="gemini-3.1-pro-preview">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            แนะนำ: <code class="px-1 bg-gray-100 dark:bg-gray-700 rounded">gemini-3.1-pro-preview</code>,
                            <code class="px-1 bg-gray-100 dark:bg-gray-700 rounded">gpt-4o</code>,
                            <code class="px-1 bg-gray-100 dark:bg-gray-700 rounded">claude-opus-4-7</code>
                        </p>
                    </div>
                </div>

                {{-- Budget Cap --}}
                <div class="mb-5 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-lg">
                    <h4 class="text-sm font-semibold text-amber-900 dark:text-amber-200 mb-3">
                        💰 Budget Cap (กัน abuse / spend bleed)
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs text-amber-800 dark:text-amber-300 mb-1">
                                ครั้ง/user/วัน
                            </label>
                            <input type="number" name="sensitive_max_per_user_daily" min="0" max="100"
                                   value="{{ old('sensitive_max_per_user_daily', $settings->sensitive_max_per_user_daily ?? 5) }}"
                                   class="w-full px-3 py-2 border border-amber-300 dark:border-amber-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs text-amber-800 dark:text-amber-300 mb-1">
                                THB/วัน รวมทั้งระบบ
                            </label>
                            <input type="number" name="sensitive_max_total_daily_thb" min="0" max="10000" step="0.01"
                                   value="{{ old('sensitive_max_total_daily_thb', $settings->sensitive_max_total_daily_thb ?? 200) }}"
                                   class="w-full px-3 py-2 border border-amber-300 dark:border-amber-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs text-amber-800 dark:text-amber-300 mb-1">
                                Tokens/call สูงสุด
                            </label>
                            <input type="number" name="sensitive_max_tokens_per_call" min="200" max="8000"
                                   value="{{ old('sensitive_max_tokens_per_call', $settings->sensitive_max_tokens_per_call ?? 2000) }}"
                                   class="w-full px-3 py-2 border border-amber-300 dark:border-amber-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                    </div>
                </div>

                {{-- Off-topic guard --}}
                <div class="mb-5 p-4 bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-700 rounded-lg">
                    <h4 class="text-sm font-semibold text-rose-900 dark:text-rose-200 mb-3">
                        🚧 Off-topic Guard (กันถามนอกเรื่องเวิ่นเว้อ)
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3">
                        <div>
                            <label class="block text-xs text-rose-800 dark:text-rose-300 mb-1">
                                จำนวน strike ก่อนตัดสินใจ
                            </label>
                            <input type="number" name="sensitive_offtopic_strikes" min="1" max="20"
                                   value="{{ old('sensitive_offtopic_strikes', $settings->sensitive_offtopic_strikes ?? 3) }}"
                                   class="w-full px-3 py-2 border border-rose-300 dark:border-rose-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs text-rose-800 dark:text-rose-300 mb-1">
                                Action เมื่อเกิน strike
                            </label>
                            <select name="sensitive_offtopic_action" x-model="offtopicAction"
                                    class="w-full px-3 py-2 border border-rose-300 dark:border-rose-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                <option value="revert">🔄 Revert (กลับใช้ default model)</option>
                                <option value="block">🛑 Block (ตัดบทสนทนา ส่งข้อความเตือน)</option>
                                <option value="handoff">👤 Handoff (ส่งให้แอดมินดูแล — Phase 2)</option>
                            </select>
                        </div>
                    </div>
                    <div x-show="offtopicAction === 'block'" x-cloak>
                        <label class="block text-xs text-rose-800 dark:text-rose-300 mb-1">
                            ข้อความเมื่อ block
                        </label>
                        <textarea name="sensitive_offtopic_block_message" rows="2"
                                  class="w-full px-3 py-2 border border-rose-300 dark:border-rose-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                  placeholder="ขออภัยค่ะ 🙏 แม่หมอจันทรารับเฉพาะคำถามเรื่องดูดวงเท่านั้นนะคะ&#10;&#10;หากต้องการดูดวง พิมพ์ &quot;ดูดวง&quot; ได้เลยค่ะ ✨">{{ old('sensitive_offtopic_block_message', $settings->sensitive_offtopic_block_message) }}</textarea>
                    </div>
                </div>

                {{-- Custom keywords / topics --}}
                <div class="mb-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            🔤 Sensitive Keywords (1 บรรทัด/คำ — เว้นว่าง = ใช้ default)
                        </label>
                        <textarea name="sensitive_keywords_text" rows="6"
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono text-sm"
                                  placeholder="มึง&#10;กู&#10;เหี้ย&#10;ระยำ&#10;...">{{ old('sensitive_keywords_text', is_array($settings->sensitive_keywords ?? null) ? implode("\n", $settings->sensitive_keywords) : '') }}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            🎯 Sensitive Topics (1 บรรทัด/หัวข้อ — เว้นว่าง = ใช้ default)
                        </label>
                        <textarea name="sensitive_topics_text" rows="6"
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono text-sm"
                                  placeholder="ฆ่าตัวตาย&#10;มะเร็ง&#10;หย่า&#10;ป่วยหนัก&#10;...">{{ old('sensitive_topics_text', is_array($settings->sensitive_topics ?? null) ? implode("\n", $settings->sensitive_topics) : '') }}</textarea>
                    </div>
                </div>

                {{-- Logging toggle --}}
                <div class="flex items-center gap-2 mt-4">
                    <input type="hidden" name="sensitive_log_enabled" value="0">
                    <input type="checkbox" name="sensitive_log_enabled" id="sensitive_log_enabled" value="1"
                           {{ old('sensitive_log_enabled', $settings->sensitive_log_enabled ?? true) ? 'checked' : '' }}
                           class="w-4 h-4 text-purple-600 rounded">
                    <label for="sensitive_log_enabled" class="text-sm text-gray-700 dark:text-gray-300">
                        📊 บันทึกทุก trigger ลงตาราง <code class="px-1 bg-gray-100 dark:bg-gray-700 rounded text-xs">fortune_sensitive_events</code>
                        (วิเคราะห์ pattern + cost tracking)
                    </label>
                </div>

            </div> {{-- End config (hidden when off) --}}
        </div> {{-- End Sensitive AI Mode card --}}

        {{-- ===== 💳 Bill Psychology (2026-05-07 Phase 2) ===== --}}
        @php
            $billHasSensitiveKey = ! empty($sensitiveKeys ?? []);
        @endphp
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6" data-fortune-tab="ai"
             x-data="proFeatureTester('bill', {{ $billHasSensitiveKey ? 'true' : 'false' }})">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    💳 Bill Psychology
                    <span class="text-xs font-normal text-emerald-600 dark:text-emerald-400">(แม่หมอใจดีคุยให้ลูกค้ายอมโอน)</span>
                </h3>
                <label class="relative inline-flex items-center cursor-pointer"
                       :class="!hasKey ? 'opacity-60 pointer-events-none' : ''">
                    <input type="hidden" name="bill_psychology_enabled" value="0">
                    <input type="checkbox" name="bill_psychology_enabled" value="1"
                           x-model="featureEnabled" :disabled="!hasKey"
                           {{ old('bill_psychology_enabled', $settings->bill_psychology_enabled ?? true) ? 'checked' : '' }}
                           class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-300 dark:peer-focus:ring-emerald-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:after:border-gray-500 peer-checked:bg-emerald-600"></div>
                </label>
            </div>

            {{-- 🌟 (2026-05-08) Notice: ใช้ key ร่วมกับ Sensitive AI Mode --}}
            <div class="mb-4 p-3 rounded-lg border text-sm flex items-start gap-2"
                 :class="hasKey ? 'bg-purple-50 dark:bg-purple-900/20 border-purple-200 dark:border-purple-700' : 'bg-red-50 dark:bg-red-900/20 border-red-300 dark:border-red-700'">
                <span class="text-lg" x-text="hasKey ? '🔗' : '⛔'"></span>
                <div class="flex-1">
                    <div class="font-semibold" :class="hasKey ? 'text-purple-900 dark:text-purple-200' : 'text-red-900 dark:text-red-200'">
                        <span x-show="hasKey" x-cloak>ใช้ key เดียวกับ Sensitive AI Mode (purpose='sensitive')</span>
                        <span x-show="!hasKey" x-cloak>เปิดไม่ได้ — ไม่มี key purpose='sensitive' ใน Account Pool</span>
                    </div>
                    <p class="text-xs mt-1" :class="hasKey ? 'text-purple-700 dark:text-purple-300' : 'text-red-800 dark:text-red-300'">
                        Bill Psychology + Celtic Premium + Sensitive AI ทั้ง 3 ใช้ Pro model ร่วมกัน
                        — เปลี่ยน key ที่ <strong>Sensitive AI Mode</strong> section ด้านบน → มีผลกับทุก feature
                    </p>
                </div>
                <button type="button" @click="testFeature()" :disabled="!hasKey || testing"
                        x-show="hasKey" x-cloak
                        class="px-3 py-1.5 text-xs bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white rounded font-semibold whitespace-nowrap">
                    <span x-show="!testing">🧪 ทดสอบ Bill</span>
                    <span x-show="testing" x-cloak>⏳</span>
                </button>
            </div>

            {{-- Test result --}}
            <div x-show="testResult" x-cloak x-transition class="mb-4 p-3 rounded-lg text-sm"
                 :class="testResult && testResult.success ? 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-900 dark:text-emerald-100' : 'bg-red-100 dark:bg-red-900/40 text-red-900 dark:text-red-100'">
                <template x-if="testResult && testResult.success">
                    <div>
                        <div class="font-semibold mb-1">✅ Bill Psychology ใช้งานได้! (latency: <span x-text="testResult.response_time_ms"></span>ms · tokens: <span x-text="testResult.tokens_used"></span>)</div>
                        <div class="text-xs mb-2 opacity-80">
                            ใช้ key: <code x-text="(testResult.key_info && testResult.key_info.key_name) || '(pool)'"></code>
                            · <span x-text="testResult.key_info?.provider"></span> / <span x-text="testResult.key_info?.model"></span>
                        </div>
                        <div class="bg-white/50 dark:bg-black/20 p-2 rounded text-xs whitespace-pre-wrap" x-text="testResult.response"></div>
                    </div>
                </template>
                <template x-if="testResult && !testResult.success">
                    <div>
                        <div class="font-semibold">❌ ทดสอบไม่ผ่าน</div>
                        <div class="text-xs mt-1" x-text="testResult.error"></div>
                    </div>
                </template>
            </div>

            <div x-show="featureEnabled" x-cloak x-collapse>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-xs text-gray-700 dark:text-gray-300 mb-1">
                            ⏰ Window (ชั่วโมง)
                        </label>
                        <input type="number" name="bill_psychology_window_hours" min="1" max="168"
                               value="{{ old('bill_psychology_window_hours', $settings->bill_psychology_window_hours ?? 24) }}"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <p class="text-xs text-gray-500 mt-1">ถือว่ามีบิลค้างใน N ชั่วโมงล่าสุด</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-700 dark:text-gray-300 mb-1">
                            🔢 Mention บิลได้สูงสุด/session
                        </label>
                        <input type="number" name="bill_max_mentions_per_session" min="1" max="10"
                               value="{{ old('bill_max_mentions_per_session', $settings->bill_max_mentions_per_session ?? 2) }}"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <p class="text-xs text-gray-500 mt-1">เกินแล้ว Bot ห้ามทวงบิลอีก (กันสแกม)</p>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        💡 ข้อความค่าครู (admin custom — ใช้ default ถ้าเว้น)
                    </label>
                    <textarea name="bill_charity_message" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                              placeholder="ค่าครู 39 บาท แม่หมอเอาไปทำบุญ + ค่าธูปเทียนบูชาพระ + เป็นพลังงานแลกเปลี่ยนตามความเชื่อ — เพื่อให้คำทำนายมีพลังจริง">{{ old('bill_charity_message', $settings->bill_charity_message) }}</textarea>
                    <p class="text-xs text-gray-500 mt-1">AI จะใช้ข้อความนี้เมื่ออธิบายว่า "ทำไมต้องจ่าย" ให้ลูกค้า</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        💱 ทางเลือกชำระเงิน (สำหรับลูกค้าต่างประเทศ — ใช้ default ถ้าเว้น)
                    </label>
                    <textarea name="bill_alternative_payment_methods" rows="4"
                              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                              placeholder="เช่น:&#10;- TrueMoney Wallet (เบอร์ XXX-XXX-XXXX)&#10;- หาก promptpay ต่างประเทศไม่ได้ ทักแอดมินช่วยเปิดบัญชี Wise/Revolut&#10;- รับโอนจากธนาคารลาว/กัมพูชา (BCEL/ABA)">{{ old('bill_alternative_payment_methods', $settings->bill_alternative_payment_methods) }}</textarea>
                    <p class="text-xs text-gray-500 mt-1">
                        ⚠️ AI จะ <strong>ใช้แค่ข้อมูลในนี้</strong> เวลาแนะนำวิธีโอนทางเลือก —
                        ถ้าเว้นว่าง AI จะบอกว่า "จะตรวจสอบกับแอดมินให้" (ปลอดภัยที่สุด)
                    </p>
                </div>
            </div>
        </div> {{-- End Bill Psychology --}}

        {{-- ===== 🌙 Celtic Premium Chat (2026-05-07 Phase 2) ===== --}}
        @php
            $celticHasSensitiveKey = ! empty($sensitiveKeys ?? []);
        @endphp
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6" data-fortune-tab="ai"
             x-data="proFeatureTester('celtic', {{ $celticHasSensitiveKey ? 'true' : 'false' }}, {
                 showPromptOverride: false,
                 initialEnabled: {{ old('celtic_premium_chat_enabled', $settings->celtic_premium_chat_enabled ?? true) ? 'true' : 'false' }},
             })">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    🌙 Celtic Premium Chat
                    <span class="text-xs font-normal text-indigo-600 dark:text-indigo-400">(หลังตอบครบ ให้คุยต่อจนหมด window)</span>
                </h3>
                <label class="relative inline-flex items-center cursor-pointer"
                       :class="!hasKey ? 'opacity-60 pointer-events-none' : ''">
                    <input type="hidden" name="celtic_premium_chat_enabled" value="0">
                    <input type="checkbox" name="celtic_premium_chat_enabled" value="1"
                           x-model="featureEnabled" :disabled="!hasKey" class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:after:border-gray-500 peer-checked:bg-indigo-600"></div>
                </label>
            </div>

            {{-- 🌟 (2026-05-08) Notice: ใช้ key ร่วมกับ Sensitive AI Mode --}}
            <div class="mb-4 p-3 rounded-lg border text-sm flex items-start gap-2"
                 :class="hasKey ? 'bg-purple-50 dark:bg-purple-900/20 border-purple-200 dark:border-purple-700' : 'bg-red-50 dark:bg-red-900/20 border-red-300 dark:border-red-700'">
                <span class="text-lg" x-text="hasKey ? '🔗' : '⛔'"></span>
                <div class="flex-1">
                    <div class="font-semibold" :class="hasKey ? 'text-purple-900 dark:text-purple-200' : 'text-red-900 dark:text-red-200'">
                        <span x-show="hasKey" x-cloak>ใช้ key เดียวกับ Sensitive AI Mode (purpose='sensitive')</span>
                        <span x-show="!hasKey" x-cloak>เปิดไม่ได้ — ไม่มี key purpose='sensitive' ใน Account Pool</span>
                    </div>
                    <p class="text-xs mt-1" :class="hasKey ? 'text-purple-700 dark:text-purple-300' : 'text-red-800 dark:text-red-300'">
                        ลูกค้า Celtic 99฿ คุยต่อกับแม่หมอ — ใช้ context ไพ่ 10 ใบ + Q&A เดิม จนหมด QA window (~30 นาที)
                    </p>
                </div>
                <button type="button" @click="testFeature()" :disabled="!hasKey || testing"
                        x-show="hasKey" x-cloak
                        class="px-3 py-1.5 text-xs bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white rounded font-semibold whitespace-nowrap">
                    <span x-show="!testing">🧪 ทดสอบ Celtic</span>
                    <span x-show="testing" x-cloak>⏳</span>
                </button>
            </div>

            {{-- Test result --}}
            <div x-show="testResult" x-cloak x-transition class="mb-4 p-3 rounded-lg text-sm"
                 :class="testResult && testResult.success ? 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-900 dark:text-emerald-100' : 'bg-red-100 dark:bg-red-900/40 text-red-900 dark:text-red-100'">
                <template x-if="testResult && testResult.success">
                    <div>
                        <div class="font-semibold mb-1">✅ Celtic Premium ใช้งานได้! (latency: <span x-text="testResult.response_time_ms"></span>ms · tokens: <span x-text="testResult.tokens_used"></span>)</div>
                        <div class="text-xs mb-2 opacity-80">
                            ใช้ key: <code x-text="(testResult.key_info && testResult.key_info.key_name) || '(pool)'"></code>
                            · <span x-text="testResult.key_info?.provider"></span> / <span x-text="testResult.key_info?.model"></span>
                        </div>
                        <div class="bg-white/50 dark:bg-black/20 p-2 rounded text-xs whitespace-pre-wrap" x-text="testResult.response"></div>
                    </div>
                </template>
                <template x-if="testResult && !testResult.success">
                    <div>
                        <div class="font-semibold">❌ ทดสอบไม่ผ่าน</div>
                        <div class="text-xs mt-1" x-text="testResult.error"></div>
                    </div>
                </template>
            </div>

            <div x-show="featureEnabled" x-cloak x-collapse>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-xs text-gray-700 dark:text-gray-300 mb-1">
                            🎯 Trigger
                        </label>
                        <select name="celtic_premium_chat_trigger"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="after_questions_done" {{ ($settings->celtic_premium_chat_trigger ?? 'after_questions_done') === 'after_questions_done' ? 'selected' : '' }}>หลังตอบครบจำนวน max_questions</option>
                            <option value="always_after_q1" {{ ($settings->celtic_premium_chat_trigger ?? '') === 'always_after_q1' ? 'selected' : '' }}>ตลอดเวลาหลัง Q1</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-700 dark:text-gray-300 mb-1">
                            ⏰ เตือนเวลาเหลือ (นาที)
                        </label>
                        <input type="number" name="celtic_premium_chat_warn_minutes_left" min="1" max="30"
                               value="{{ old('celtic_premium_chat_warn_minutes_left', $settings->celtic_premium_chat_warn_minutes_left ?? 5) }}"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-700 dark:text-gray-300 mb-1">
                            🔢 Max messages (0 = unlimited)
                        </label>
                        <input type="number" name="celtic_premium_chat_max_messages" min="0" max="200"
                               value="{{ old('celtic_premium_chat_max_messages', $settings->celtic_premium_chat_max_messages ?? 30) }}"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                </div>

                <button type="button" @click="showPromptOverride = !showPromptOverride"
                        class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline mb-2">
                    <span x-show="!showPromptOverride">⚙️ Override system prompt (advanced)</span>
                    <span x-show="showPromptOverride" x-cloak>ซ่อน prompt override</span>
                </button>

                <div x-show="showPromptOverride" x-cloak x-collapse>
                    <textarea name="celtic_premium_chat_prompt_override" rows="6"
                              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono text-sm"
                              placeholder="เว้นว่าง = ใช้ default prompt&#10;Variables ที่ใช้ได้: {minutes_remaining}, {cards_and_qa}, {msg_count}, {max_msg}">{{ old('celtic_premium_chat_prompt_override', $settings->celtic_premium_chat_prompt_override) }}</textarea>
                    <p class="text-xs text-gray-500 mt-1">⚠️ ถ้าใส่ prompt เอง ต้องระบุ context ไพ่/Q&A ผ่าน <code>{cards_and_qa}</code></p>
                </div>
            </div>
        </div> {{-- End Celtic Premium --}}

        {{-- ===== 🎙️ Voice Summary (TTS) — 2026-05-08 ===== --}}
        @php
            $voiceFallbacks = old('voice_summary_fallback_providers', $settings->voice_summary_fallback_providers ?? []);
            if (!is_array($voiceFallbacks)) { $voiceFallbacks = []; }
        @endphp
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6"
             x-data="{
                 voiceEnabled: {{ old('voice_summary_enabled', $settings->voice_summary_enabled ?? false) ? 'true' : 'false' }},
                 primaryProvider: '{{ old('voice_summary_primary_provider', $settings->voice_summary_primary_provider ?? 'minimax') }}',
                 showAdvanced: false,
             }" data-fortune-tab="tts">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    🎙️ เสียงสรุปคำทำนาย (TTS)
                    <span class="text-xs font-normal text-purple-600 dark:text-purple-400">(VIP perk — Celtic 99฿ เท่านั้น)</span>
                </h3>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="hidden" name="voice_summary_enabled" value="0">
                    <input type="checkbox" name="voice_summary_enabled" value="1" x-model="voiceEnabled" class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 dark:peer-focus:ring-purple-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:after:border-gray-500 peer-checked:bg-purple-600"></div>
                </label>
            </div>

            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                เปิดใช้ ระบบจะสังเคราะห์เสียงสรุปคำทำนายเป็นไฟล์ mp3 ส่งให้ลูกค้าฟัง (เป็นโบนัสหลัง Grand Finale)
                <br>
                <strong>Provider chain:</strong> Primary → Fallback 1 → Fallback 2... (ลำดับล้มเหลวจะข้ามไปตัวถัดไป)
            </p>

            <div x-show="voiceEnabled" x-cloak x-collapse>
                {{-- Tier scope --}}
                <div class="mb-4">
                    <label class="block text-sm text-gray-700 dark:text-gray-300 mb-2">🎯 Scope (ใช้กับใคร)</label>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                        @foreach([
                            'celtic_99_only' => ['🌙 เฉพาะ Celtic 99฿', 'แนะนำ — VIP perk'],
                            'paid_all' => ['💎 ทุก paid reading', 'รวม Deep 39฿'],
                            'all' => ['🌐 ทุก reading', 'รวมฟรี (ไม่แนะนำ — ค่า TTS เปลือง)'],
                        ] as $val => $info)
                            <label class="flex items-start p-3 border rounded-lg cursor-pointer hover:bg-purple-50 dark:hover:bg-purple-900/20"
                                   :class="'{{ $val }}' === '{{ old('voice_summary_tier_scope', $settings->voice_summary_tier_scope ?? 'celtic_99_only') }}' ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20' : 'border-gray-300 dark:border-gray-600'">
                                <input type="radio" name="voice_summary_tier_scope" value="{{ $val }}"
                                       {{ ($settings->voice_summary_tier_scope ?? 'celtic_99_only') === $val ? 'checked' : '' }}
                                       class="mt-1 mr-3">
                                <div>
                                    <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $info[0] }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $info[1] }}</div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Primary provider --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">⭐ Primary Provider</label>
                        <select name="voice_summary_primary_provider" x-model="primaryProvider"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="minimax">MiniMax (paid, premium HD voice)</option>
                            <option value="openai_tts">OpenAI TTS (paid, ผ่าน API key pool)</option>
                            <option value="google_tts">Google Cloud TTS (free 1M chars/เดือน)</option>
                            <option value="gtts">gTTS (Google Translate, ฟรี — ไม่ต้องตั้ง key)</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">ตัวที่ใช้ก่อน — fail แล้วค่อย fallback</p>
                    </div>

                    <div>
                        <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">🔁 Fallback Providers (ลำดับ)</label>
                        <div class="space-y-1">
                            @foreach(['google_tts' => 'Google Cloud TTS (free)', 'gtts' => 'gTTS (free)', 'openai_tts' => 'OpenAI TTS', 'minimax' => 'MiniMax'] as $val => $label)
                                <label class="flex items-center text-sm">
                                    <input type="checkbox" name="voice_summary_fallback_providers[]" value="{{ $val }}"
                                           {{ in_array($val, $voiceFallbacks) ? 'checked' : '' }}
                                           class="mr-2 rounded">
                                    <span class="text-gray-700 dark:text-gray-300">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        <p class="text-xs text-gray-500 mt-1">เว้นทั้งหมด = ใช้ chain default ตาม primary</p>
                    </div>
                </div>

                {{-- MiniMax config --}}
                <div x-show="primaryProvider === 'minimax' || {{ in_array('minimax', $voiceFallbacks) ? 'true' : 'false' }}" x-cloak
                     class="mb-4 p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg border border-purple-200 dark:border-purple-800">
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-3">🎙️ MiniMax Config</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div class="md:col-span-2">
                            <label class="block text-xs text-gray-700 dark:text-gray-300 mb-1">🔑 MiniMax API Key (JWT)</label>
                            <input type="password" name="minimax_api_key"
                                   value="{{ old('minimax_api_key', $settings->minimax_api_key) }}"
                                   placeholder="เว้นว่าง = ดึงจาก ai_api_keys pool (provider=minimax, purpose=tts)"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-700 dark:text-gray-300 mb-1">📦 Group ID (จำเป็น)</label>
                            <input type="text" name="minimax_group_id"
                                   value="{{ old('minimax_group_id', $settings->minimax_group_id) }}"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-700 dark:text-gray-300 mb-1">🎯 Model</label>
                            <select name="minimax_model"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                                @php $currentMm = $settings->minimax_model ?? 'speech-2.8-hd'; @endphp
                                <optgroup label="🌟 Latest (2026)">
                                    <option value="speech-2.8-hd" {{ $currentMm === 'speech-2.8-hd' ? 'selected' : '' }}>speech-2.8-hd (HD ใหม่สุด ⭐)</option>
                                    <option value="speech-2.8-turbo" {{ $currentMm === 'speech-2.8-turbo' ? 'selected' : '' }}>speech-2.8-turbo (turbo ใหม่สุด)</option>
                                    <option value="speech-2.6-hd" {{ $currentMm === 'speech-2.6-hd' ? 'selected' : '' }}>speech-2.6-hd</option>
                                    <option value="speech-2.6-turbo" {{ $currentMm === 'speech-2.6-turbo' ? 'selected' : '' }}>speech-2.6-turbo</option>
                                </optgroup>
                                <optgroup label="Standard">
                                    <option value="speech-02-hd" {{ $currentMm === 'speech-02-hd' ? 'selected' : '' }}>speech-02-hd</option>
                                    <option value="speech-02-turbo" {{ $currentMm === 'speech-02-turbo' ? 'selected' : '' }}>speech-02-turbo</option>
                                </optgroup>
                                <optgroup label="Legacy">
                                    <option value="speech-01-hd" {{ $currentMm === 'speech-01-hd' ? 'selected' : '' }}>speech-01-hd</option>
                                    <option value="speech-01-turbo" {{ $currentMm === 'speech-01-turbo' ? 'selected' : '' }}>speech-01-turbo</option>
                                </optgroup>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs text-gray-700 dark:text-gray-300 mb-1">🎤 Voice ID</label>
                            <input type="text" name="minimax_voice_id"
                                   value="{{ old('minimax_voice_id', $settings->minimax_voice_id ?? 'Thai_warmFemaleHost') }}"
                                   placeholder="Thai_warmFemaleHost / Thai_femaleSerene / female-tianmei"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                            <p class="text-xs text-gray-500 mt-1">
                                ⚠️ Endpoint: <code>https://api.minimax.io/v1/t2a_v2</code> — group_id ใส่ก็ได้ ไม่ใส่ก็ได้ใน v2
                                · ดูรายชื่อ voice ที่ <a href="https://platform.minimax.io/docs/faq/system-voice-id" target="_blank" class="text-purple-600 hover:underline">MiniMax Voice List</a>
                            </p>
                        </div>
                    </div>
                </div>

                {{-- OpenAI TTS config --}}
                <div x-show="primaryProvider === 'openai_tts' || {{ in_array('openai_tts', $voiceFallbacks) ? 'true' : 'false' }}" x-cloak
                     class="mb-4 p-4 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg border border-emerald-200 dark:border-emerald-800">
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-3">🎙️ OpenAI TTS Config</h4>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mb-3">ใช้ key จาก ai_api_keys pool (provider=openai, purpose=tts หรือ any)</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-gray-700 dark:text-gray-300 mb-1">🎯 Model</label>
                            <select name="openai_tts_model"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                                <option value="gpt-4o-mini-tts" {{ ($settings->openai_tts_model ?? 'gpt-4o-mini-tts') === 'gpt-4o-mini-tts' ? 'selected' : '' }}>gpt-4o-mini-tts (ใหม่+ถูก)</option>
                                <option value="tts-1" {{ ($settings->openai_tts_model ?? '') === 'tts-1' ? 'selected' : '' }}>tts-1 (standard)</option>
                                <option value="tts-1-hd" {{ ($settings->openai_tts_model ?? '') === 'tts-1-hd' ? 'selected' : '' }}>tts-1-hd (HD)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-700 dark:text-gray-300 mb-1">🎤 Voice</label>
                            <select name="openai_tts_voice"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                                @foreach(['shimmer', 'nova', 'alloy', 'echo', 'fable', 'onyx'] as $voice)
                                    <option value="{{ $voice }}" {{ ($settings->openai_tts_voice ?? 'shimmer') === $voice ? 'selected' : '' }}>{{ $voice }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Google TTS config --}}
                <div x-show="primaryProvider === 'google_tts' || {{ in_array('google_tts', $voiceFallbacks) ? 'true' : 'false' }}" x-cloak
                     class="mb-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-3">🎙️ Google Cloud TTS Config (free tier)</h4>

                    {{-- 🆕 (2026-05-08) Credentials diagnostic — Google ใช้ key/credentials เดียวกันได้กับ Translate/Vision/Speech-to-Text --}}
                    @php
                        $gDiag = $googleTtsDiag ?? \App\Services\Tts\GoogleCloudTtsProvider::diagnoseCredentials();
                        $hasAnyKey = $gDiag['resolved_key_source'] !== null;
                        $hasServiceAccount = ! empty($gDiag['service_account_path']);
                    @endphp

                    <div class="mb-3 p-3 rounded-lg border {{ $hasAnyKey ? 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-300 dark:border-emerald-800' : 'bg-amber-50 dark:bg-amber-900/20 border-amber-300 dark:border-amber-800' }}">
                        <div class="flex items-start gap-2 mb-2">
                            <span class="text-lg">{{ $hasAnyKey ? '✅' : '⚠️' }}</span>
                            <div class="text-sm">
                                <div class="font-semibold text-gray-900 dark:text-white">
                                    {{ $hasAnyKey ? "ตรวจเจอ key — ใช้ได้: <code>{$gDiag['resolved_key_source']}</code>" : 'ยังไม่พบ Google API key ใน .env' }}
                                </div>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                    💡 <strong>Google Cloud TTS / Translate / Vision / Speech-to-Text ใช้ key/credentials เดียวกันได้</strong> —
                                    ขอเปิด <code>Cloud Text-to-Speech API</code> ใน project เดียวกัน
                                    (<a href="https://console.cloud.google.com/apis/library/texttospeech.googleapis.com" target="_blank" class="text-blue-600 hover:underline">เปิด API ที่นี่</a>)
                                </p>
                            </div>
                        </div>

                        <div class="mt-2 space-y-1 text-xs">
                            <div class="font-medium text-gray-700 dark:text-gray-300 mb-1">📋 รายการ key ที่ระบบตรวจเจอ:</div>
                            @foreach($gDiag['keys_found'] as $envName => $found)
                                <div class="flex items-center gap-2 pl-2">
                                    <span class="{{ $found ? 'text-green-600' : 'text-gray-400' }}">{{ $found ? '✅' : '⬜' }}</span>
                                    <code class="text-gray-700 dark:text-gray-300">{{ $envName }}</code>
                                    @if($found && $envName === $gDiag['resolved_key_source'])
                                        <span class="text-xs px-2 py-0.5 bg-emerald-200 dark:bg-emerald-800 text-emerald-900 dark:text-emerald-100 rounded font-semibold">USING</span>
                                    @endif
                                </div>
                            @endforeach
                            <div class="flex items-center gap-2 pl-2 pt-1 border-t border-gray-200 dark:border-gray-700">
                                <span class="{{ $hasServiceAccount ? 'text-green-600' : 'text-gray-400' }}">{{ $hasServiceAccount ? '✅' : '⬜' }}</span>
                                <code class="text-gray-700 dark:text-gray-300">GOOGLE_APPLICATION_CREDENTIALS</code>
                                <span class="text-xs text-gray-500">(Service Account JSON — ที่ Speech-to-Text ใช้อยู่)</span>
                            </div>
                            @if($gDiag['project_id'])
                                <div class="flex items-center gap-2 pl-2 pt-1">
                                    <span class="text-blue-600">📦</span>
                                    <span class="text-gray-700 dark:text-gray-300">Project ID:</span>
                                    <code class="bg-blue-100 dark:bg-blue-900/40 px-2 py-0.5 rounded">{{ $gDiag['project_id'] }}</code>
                                </div>
                            @endif
                        </div>

                        @unless($hasAnyKey)
                            <div class="mt-3 p-2 bg-white dark:bg-gray-800 rounded text-xs text-gray-700 dark:text-gray-300">
                                <strong>วิธีตั้งค่า:</strong> เพิ่มใน <code>.env</code>:
                                <pre class="mt-1 bg-gray-100 dark:bg-gray-900 p-2 rounded overflow-x-auto">GOOGLE_TTS_API_KEY=YOUR_KEY_HERE
# หรือใช้ key ของ Translate ที่มีอยู่ก็ได้:
# GOOGLE_TRANSLATE_API_KEY=...</pre>
                            </div>
                        @endunless
                    </div>

                    <p class="text-xs text-gray-600 dark:text-gray-400 mb-3">
                        🎁 <strong>Free tier:</strong> 1M chars/เดือน (WaveNet+Neural2), 4M chars/เดือน (Standard)
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-gray-700 dark:text-gray-300 mb-1">🎤 Voice</label>
                            <select name="google_tts_voice"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                                @foreach([
                                    'th-TH-Neural2-C' => 'th-TH-Neural2-C (female, premium)',
                                    'th-TH-Neural2-A' => 'th-TH-Neural2-A (female)',
                                    'th-TH-Standard-A' => 'th-TH-Standard-A (female, free tier)',
                                    'th-TH-Wavenet-C' => 'th-TH-Wavenet-C (female, WaveNet)',
                                ] as $val => $label)
                                    <option value="{{ $val }}" {{ ($settings->google_tts_voice ?? 'th-TH-Neural2-C') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-700 dark:text-gray-300 mb-1">🐢 Speaking Rate (0.25-4.0, 1.0=ปกติ)</label>
                            <input type="number" name="google_tts_speaking_rate" step="0.05" min="0.25" max="4.0"
                                   value="{{ old('google_tts_speaking_rate', $settings->google_tts_speaking_rate ?? 0.95) }}"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                        </div>
                    </div>
                </div>

                {{-- Common settings --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-xs text-gray-700 dark:text-gray-300 mb-1">📏 Max chars (cost cap)</label>
                        <input type="number" name="voice_summary_max_chars" min="200" max="5000"
                               value="{{ old('voice_summary_max_chars', $settings->voice_summary_max_chars ?? 2000) }}"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                        <p class="text-xs text-gray-500 mt-1">2000 chars ≈ 1.5-2 นาที (แนะนำ)</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-700 dark:text-gray-300 mb-1">💬 ข้อความก่อนส่ง audio</label>
                        <input type="text" name="voice_summary_intro_message" maxlength="500"
                               value="{{ old('voice_summary_intro_message', $settings->voice_summary_intro_message ?? '🎙️ แม่หมอจันทราอัดเสียงสรุปคำทำนายให้เจ้าชะตาแล้วค่ะ ลองฟังดูนะ ✨') }}"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                    </div>
                </div>

                <button type="button" @click="showAdvanced = !showAdvanced"
                        class="text-sm text-purple-600 dark:text-purple-400 hover:underline mb-2">
                    <span x-show="!showAdvanced">⚙️ Override AI summary prompt (advanced)</span>
                    <span x-show="showAdvanced" x-cloak>ซ่อน prompt override</span>
                </button>

                <div x-show="showAdvanced" x-cloak x-collapse>
                    <textarea name="voice_summary_prompt" rows="6"
                              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono text-sm"
                              placeholder="เว้นว่าง = ใช้ default prompt&#10;Variables: {name}, {source}, {max_chars}">{{ old('voice_summary_prompt', $settings->voice_summary_prompt) }}</textarea>
                    <p class="text-xs text-gray-500 mt-1">⚠️ ต้องสั่ง AI ตอบเป็น plain text ไม่มี markdown</p>
                </div>
            </div>
        </div> {{-- End Voice Summary --}}

        {{-- ===== 🎼 Voice Library (2026-05-08) — ตั้งค่า/ทดสอบ/อิมพอตเสียง ===== --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6" data-fortune-tab="tts"
             x-data="voiceLibrary()"
             x-init="init()">
            <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    🎼 Voice Library
                    <span class="text-xs font-normal text-purple-600 dark:text-purple-400">(ตั้งค่า · ทดสอบ · อิมพอตโค๊ดเสียง)</span>
                </h3>
                <div class="flex gap-2">
                    <button type="button" @click="seedThai()" :disabled="busy"
                            class="px-3 py-2 text-sm bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white rounded-lg transition">
                        🌱 Seed Thai voices
                    </button>
                    <button type="button" @click="showImport = !showImport"
                            class="px-3 py-2 text-sm bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition">
                        <span x-show="!showImport">📥 Import bulk</span>
                        <span x-show="showImport" x-cloak>ซ่อน</span>
                    </button>
                    <button type="button" @click="showAdd = !showAdd"
                            class="px-3 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                        <span x-show="!showAdd">➕ เพิ่มเสียง</span>
                        <span x-show="showAdd" x-cloak>ซ่อน</span>
                    </button>
                </div>
            </div>

            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                บันทึก voice combinations ที่ใช้บ่อย ๆ — กด <strong>Apply</strong> เพื่อตั้งเป็นเสียงปัจจุบัน, กด <strong>Test</strong> เพื่อฟังตัวอย่าง
            </p>

            {{-- Add new preset --}}
            <div x-show="showAdd" x-cloak x-collapse
                 class="mb-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                <h4 class="font-semibold mb-3 text-gray-900 dark:text-white">➕ เพิ่ม Voice Preset</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-gray-700 dark:text-gray-300 mb-1">ชื่อ</label>
                        <input type="text" x-model="newPreset.name" placeholder="เช่น แม่หมอใจดี"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-700 dark:text-gray-300 mb-1">Provider</label>
                        <select x-model="newPreset.provider"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm">
                            <option value="minimax">MiniMax</option>
                            <option value="openai_tts">OpenAI TTS</option>
                            <option value="google_tts">Google Cloud TTS</option>
                            <option value="gtts">gTTS</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-700 dark:text-gray-300 mb-1">Voice ID</label>
                        <input type="text" x-model="newPreset.voice_id" placeholder="Thai_warmFemaleHost / shimmer / th-TH-Neural2-C"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-700 dark:text-gray-300 mb-1">Model (optional)</label>
                        <input type="text" x-model="newPreset.model" placeholder="speech-2.8-hd / gpt-4o-mini-tts"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm">
                    </div>
                </div>
                <button type="button" @click="addPreset()" :disabled="busy"
                        class="mt-3 px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white rounded-lg text-sm">
                    บันทึก
                </button>
            </div>

            {{-- Bulk import --}}
            <div x-show="showImport" x-cloak x-collapse
                 class="mb-4 p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg border border-purple-200 dark:border-purple-800">
                <h4 class="font-semibold mb-3 text-gray-900 dark:text-white">📥 Import bulk โค๊ดเสียง</h4>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                    <div>
                        <label class="block text-xs text-gray-700 dark:text-gray-300 mb-1">Provider ของชุดที่อิมพอต</label>
                        <select x-model="importProvider"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm">
                            <option value="minimax">MiniMax</option>
                            <option value="openai_tts">OpenAI TTS</option>
                            <option value="google_tts">Google Cloud TTS</option>
                            <option value="gtts">gTTS</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <label class="flex items-center text-sm">
                            <input type="checkbox" x-model="importReplace" class="mr-2 rounded">
                            <span class="text-gray-700 dark:text-gray-300">⚠️ ลบของเดิม provider นี้ก่อน import</span>
                        </label>
                    </div>
                </div>

                <textarea x-model="importRaw" rows="8"
                          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono text-xs"
                          placeholder="Format 1 — pipe-delimited (1 บรรทัด/voice):
voice_id|name|model
Thai_warmFemaleHost|แม่หมอใจดี|speech-2.8-hd
Thai_femaleSerene|เสียงสงบ
female-tianmei|เสียงหวาน

Format 2 — JSON array:
[{&quot;name&quot;:&quot;แม่หมอ&quot;,&quot;voice_id&quot;:&quot;Thai_warmFemaleHost&quot;,&quot;model&quot;:&quot;speech-2.8-hd&quot;}]

# บรรทัดที่ขึ้นต้นด้วย # หรือ // จะถูกข้าม"></textarea>

                <button type="button" @click="importBulk()" :disabled="busy || !importRaw.trim()"
                        class="mt-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white rounded-lg text-sm">
                    🚀 อิมพอตเลย
                </button>
                <p class="text-xs text-gray-500 mt-2">
                    💡 ดูรายชื่อ voice MiniMax ที่ <a href="https://platform.minimax.io/docs/faq/system-voice-id" target="_blank" class="text-purple-600 hover:underline">platform.minimax.io/docs/faq/system-voice-id</a>
                </p>
            </div>

            {{-- Preset list --}}
            <div class="overflow-x-auto">
                <template x-if="presets.length === 0 && !loading">
                    <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                        <div class="text-4xl mb-2">🎙️</div>
                        <p>ยังไม่มี voice preset — กด "Seed Thai voices" เพื่อเพิ่มชุดเริ่มต้น หรือ "Import bulk"</p>
                    </div>
                </template>
                <template x-if="loading">
                    <div class="text-center py-8 text-gray-500">⏳ กำลังโหลด...</div>
                </template>
                <table x-show="presets.length > 0" x-cloak class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="text-left py-2 px-3">ชื่อ</th>
                            <th class="text-left py-2 px-3">Provider</th>
                            <th class="text-left py-2 px-3 font-mono text-xs">Voice ID / Model</th>
                            <th class="text-center py-2 px-3">ฟัง</th>
                            <th class="text-right py-2 px-3">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="preset in presets" :key="preset.id">
                            <tr class="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="py-2 px-3">
                                    <div class="flex items-center gap-2">
                                        <span x-show="preset.is_default" class="text-xs px-2 py-0.5 bg-emerald-200 dark:bg-emerald-800 text-emerald-900 dark:text-emerald-100 rounded font-semibold">USING</span>
                                        <span x-text="preset.name" class="font-medium"></span>
                                    </div>
                                </td>
                                <td class="py-2 px-3">
                                    <span class="text-xs px-2 py-0.5 rounded"
                                          :class="{
                                            'bg-purple-100 dark:bg-purple-900/40 text-purple-800 dark:text-purple-200': preset.provider === 'minimax',
                                            'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-800 dark:text-emerald-200': preset.provider === 'openai_tts',
                                            'bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-200': preset.provider === 'google_tts',
                                            'bg-gray-100 dark:bg-gray-900/40 text-gray-800 dark:text-gray-200': preset.provider === 'gtts',
                                          }"
                                          x-text="preset.provider"></span>
                                </td>
                                <td class="py-2 px-3 font-mono text-xs">
                                    <div x-text="preset.voice_id"></div>
                                    <div x-show="preset.model" x-text="preset.model" class="text-gray-500"></div>
                                </td>
                                <td class="py-2 px-3 text-center">
                                    <template x-if="preset.sample_audio_url">
                                        <audio :src="preset.sample_audio_url" controls class="h-8"></audio>
                                    </template>
                                    <template x-if="!preset.sample_audio_url">
                                        <span class="text-gray-400 text-xs">—</span>
                                    </template>
                                </td>
                                <td class="py-2 px-3 text-right whitespace-nowrap">
                                    <button type="button" @click="testPreset(preset.id)" :disabled="busy"
                                            class="px-2 py-1 text-xs bg-amber-500 hover:bg-amber-600 disabled:opacity-50 text-white rounded">
                                        🎙️ Test
                                    </button>
                                    <button type="button" @click="applyPreset(preset.id)" :disabled="busy || preset.is_default"
                                            class="px-2 py-1 text-xs bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white rounded">
                                        ✓ Apply
                                    </button>
                                    <button type="button" @click="deletePreset(preset.id, preset.name)" :disabled="busy"
                                            class="px-2 py-1 text-xs bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white rounded">
                                        🗑
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div x-show="message" x-cloak x-transition class="mt-4 p-3 rounded-lg text-sm"
                 :class="messageOk ? 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-800 dark:text-emerald-200' : 'bg-red-100 dark:bg-red-900/40 text-red-800 dark:text-red-200'"
                 x-text="message"></div>
        </div> {{-- End Voice Library --}}

        {{-- ===== 🌥️ (2026-05-18) Voice File Storage — Cloud Storage Config ===== --}}
        @php
            $voiceStorageDriver = $settings->voice_storage_driver ?: 'local';
            $voiceStorageConfig = is_array($settings->voice_storage_config) ? $settings->voice_storage_config : [];
            $cfgR2 = $voiceStorageConfig['r2'] ?? [];
            $cfgS3 = $voiceStorageConfig['s3'] ?? [];
            $cfgGcs = $voiceStorageConfig['gcs'] ?? [];
            $cfgFirebase = $voiceStorageConfig['firebase'] ?? [];
        @endphp
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6"
             data-fortune-tab="tts"
             x-data="voiceStorage({{ json_encode([
                'driver' => $voiceStorageDriver,
                'r2' => $cfgR2,
                's3' => $cfgS3,
                'gcs' => $cfgGcs,
                'firebase' => $cfgFirebase,
             ]) }})">
            <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    🌥️ Voice File Storage
                    <span class="text-xs font-normal text-blue-600 dark:text-blue-400">(แก้ปัญหา server เต็มเร็ว — ย้าย mp3 ไป cloud)</span>
                </h3>
                <a href="{{ route('admin.fortune.voice-diagnostic') }}"
                   class="px-3 py-2 text-sm bg-rose-600 hover:bg-rose-700 text-white rounded-lg transition flex items-center gap-1">
                    🩺 Diagnostic
                </a>
            </div>

            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                เลือก driver ที่จะใช้เก็บไฟล์เสียง mp3 — แต่ละการทำนาย Celtic 99฿ จะสร้างไฟล์ ~500KB-2MB.
                ย้ายไป cloud เพื่อ:<br>
                ✅ ไม่กิน disk บนเซิร์ฟเวอร์ &nbsp; ✅ โหลดเร็วผ่าน CDN &nbsp; ✅ Backup อัตโนมัติ
            </p>

            {{-- Driver picker --}}
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">📦 เลือก Driver</label>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-2">
                    @foreach([
                        'local' => ['📁 Local', 'แนะนำสำหรับเริ่มต้น'],
                        'r2' => ['🟧 Cloudflare R2', 'ฟรี 10GB · free egress ⭐'],
                        'firebase' => ['🔥 Firebase Storage', 'ฟรี 5GB · ง่ายสุด'],
                        'gcs' => ['🔵 Google Cloud Storage', 'แม่นยำ · paid'],
                        's3' => ['🟨 AWS S3', 'มาตรฐาน · paid'],
                    ] as $val => $info)
                        <label class="flex flex-col items-start p-3 border-2 rounded-lg cursor-pointer transition"
                               :class="driver === '{{ $val }}' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-300 dark:border-gray-600 hover:border-blue-300'">
                            <input type="radio" x-model="driver" value="{{ $val }}" class="sr-only">
                            <div class="text-sm font-bold text-gray-900 dark:text-white">{{ $info[0] }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $info[1] }}</div>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- Local config --}}
            <div x-show="driver === 'local'" x-cloak class="mb-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                <h4 class="font-semibold text-gray-900 dark:text-white mb-3">📁 Local Disk</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                    เก็บใน <code class="bg-gray-200 dark:bg-gray-800 px-1 rounded">storage/app/public/fortune-voice/</code> — ต้องรัน <code class="bg-gray-200 dark:bg-gray-800 px-1 rounded">php artisan storage:link</code>
                </p>
                <button type="button" @click="fixSymlink()" :disabled="busy"
                        class="px-4 py-2 text-sm bg-amber-500 hover:bg-amber-600 disabled:opacity-50 text-white rounded-lg">
                    🔗 สร้าง symlink (storage:link)
                </button>
                <details class="mt-3">
                    <summary class="cursor-pointer text-sm font-medium text-blue-600 dark:text-blue-400">📖 วิธีใช้งาน</summary>
                    <ol class="mt-2 ml-5 text-xs text-gray-600 dark:text-gray-400 list-decimal space-y-1">
                        <li>กดปุ่ม "สร้าง symlink" ด้านบน หรือ SSH เข้าไป run <code>php artisan storage:link</code></li>
                        <li>กดปุ่ม "ทดสอบการเชื่อมต่อ" ด้านล่าง เพื่อยืนยันว่า upload + delete ใช้ได้</li>
                        <li>กด "บันทึก driver" → เริ่มใช้งานได้ทันที</li>
                        <li>⚠️ ขนาดเพิ่มเร็ว ~50MB-200MB/วัน ถ้าลูกค้าจ่าย Celtic 99฿ บ่อย → แนะนำย้ายไป cloud</li>
                    </ol>
                </details>
            </div>

            {{-- R2 config --}}
            <div x-show="driver === 'r2'" x-cloak class="mb-4 p-4 bg-orange-50 dark:bg-orange-900/20 rounded-lg border border-orange-300 dark:border-orange-700">
                <h4 class="font-semibold text-gray-900 dark:text-white mb-3">🟧 Cloudflare R2 (แนะนำ)</h4>
                @if(! class_exists(\League\Flysystem\AwsS3V3\AwsS3V3Adapter::class))
                    <div class="mb-3 p-3 bg-red-50 dark:bg-red-900/20 border border-red-300 dark:border-red-700 rounded text-sm">
                        ⚠️ <strong>ยังไม่ได้ติดตั้ง package</strong> — รันคำสั่งนี้ที่ server ก่อน:<br>
                        <code class="block mt-1 p-2 bg-gray-900 text-green-400 rounded font-mono text-xs">composer require league/flysystem-aws-s3-v3 "^3.0"</code>
                    </div>
                @endif
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="md:col-span-2">
                        <label class="block text-xs text-gray-700 dark:text-gray-300 mb-1">🆔 Account ID</label>
                        <input type="text" x-model="r2.account_id" placeholder="32 chars hex"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm font-mono">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-700 dark:text-gray-300 mb-1">🔑 Access Key ID</label>
                        <input type="text" x-model="r2.access_key_id"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm font-mono">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-700 dark:text-gray-300 mb-1">🔒 Secret Access Key</label>
                        <input type="password" x-model="r2.secret_access_key" placeholder="เว้นว่าง = ใช้ค่าเดิม"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm font-mono">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-700 dark:text-gray-300 mb-1">🪣 Bucket Name</label>
                        <input type="text" x-model="r2.bucket" placeholder="fortune-voice"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-700 dark:text-gray-300 mb-1">🌐 Public URL (CDN/Public bucket URL)</label>
                        <input type="text" x-model="r2.public_url" placeholder="https://pub-xxx.r2.dev"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm">
                    </div>
                </div>
                <details class="mt-4">
                    <summary class="cursor-pointer text-sm font-medium text-orange-700 dark:text-orange-400">📖 วิธีสมัคร R2 + หา credentials (5 นาที)</summary>
                    <ol class="mt-3 ml-5 text-xs text-gray-700 dark:text-gray-300 list-decimal space-y-2">
                        <li>เข้า <a href="https://dash.cloudflare.com/" target="_blank" class="text-orange-600 underline">dash.cloudflare.com</a> → R2 → Subscribe (ฟรี 10GB)</li>
                        <li>สร้าง bucket ใหม่ ตั้งชื่อ เช่น <code>fortune-voice</code></li>
                        <li>เปิด bucket → Settings → Public Access → "Allow Access" → จด URL ที่ขึ้นต้น <code>pub-xxx.r2.dev</code> ใส่ในช่อง "Public URL" ด้านบน</li>
                        <li>กลับไป R2 → Manage API tokens → "Create API token" → Object Read & Write → Specify bucket → จด Access Key + Secret + Account ID</li>
                        <li>วาง credentials ในฟอร์มด้านบน → กด "ทดสอบ" → กด "บันทึก driver"</li>
                        <li>🎁 R2 = <strong>free egress</strong> = ลูกค้าฟัง audio กี่ครั้งก็ไม่เสีย bandwidth (S3 คิดเงิน!)</li>
                    </ol>
                </details>
            </div>

            {{-- Firebase config --}}
            <div x-show="driver === 'firebase'" x-cloak class="mb-4 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-300 dark:border-amber-700">
                <h4 class="font-semibold text-gray-900 dark:text-white mb-3">🔥 Firebase Storage (ใช้ project plptdb ที่มีอยู่)</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-gray-700 dark:text-gray-300 mb-1">📂 Credentials JSON Path</label>
                        <input type="text" x-model="firebase.credentials_path"
                               placeholder="storage/app/firebase-credentials.json"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm font-mono">
                        <p class="text-xs text-gray-500 mt-1">ใช้ service account JSON เดียวกับ FCM</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-700 dark:text-gray-300 mb-1">🪣 Storage Bucket</label>
                        <input type="text" x-model="firebase.bucket"
                               placeholder="plptdb.firebasestorage.app"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm">
                        <p class="text-xs text-gray-500 mt-1">ดูที่ Firebase Console → Storage → Bucket</p>
                    </div>
                </div>
                <details class="mt-4">
                    <summary class="cursor-pointer text-sm font-medium text-amber-700 dark:text-amber-400">📖 วิธีตั้งค่า Firebase Storage (3 นาที)</summary>
                    <ol class="mt-3 ml-5 text-xs text-gray-700 dark:text-gray-300 list-decimal space-y-2">
                        <li>เข้า <a href="https://console.firebase.google.com/project/plptdb/storage" target="_blank" class="text-amber-600 underline">Firebase Console → Storage</a></li>
                        <li>ถ้ายังไม่เปิด Storage → กด "Get Started" → เลือก location (asia-southeast1 = สิงคโปร์ แนะนำ)</li>
                        <li>คัดลอกชื่อ bucket (เช่น <code>plptdb.firebasestorage.app</code> หรือ <code>plptdb.appspot.com</code>) วางในช่องด้านบน</li>
                        <li>Credentials Path: ใช้ค่า default <code>storage/app/firebase-credentials.json</code> (มีอยู่แล้ว — ใช้กับ FCM)</li>
                        <li>กด "ทดสอบ" → กด "บันทึก driver"</li>
                        <li>🎁 ฟรี 5GB + 1GB/วัน download — เหมาะกับเริ่มต้น (ไม่ต้องตั้ง credentials ใหม่)</li>
                    </ol>
                </details>
            </div>

            {{-- GCS config --}}
            <div x-show="driver === 'gcs'" x-cloak class="mb-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-300 dark:border-blue-700">
                <h4 class="font-semibold text-gray-900 dark:text-white mb-3">🔵 Google Cloud Storage</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-gray-700 dark:text-gray-300 mb-1">📂 Service Account JSON Path</label>
                        <input type="text" x-model="gcs.credentials_path"
                               placeholder="storage/app/firebase-credentials.json"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm font-mono">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-700 dark:text-gray-300 mb-1">🪣 Bucket Name</label>
                        <input type="text" x-model="gcs.bucket"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs text-gray-700 dark:text-gray-300 mb-1">🌐 Public URL (optional — ถ้า bucket = public)</label>
                        <input type="text" x-model="gcs.public_url"
                               placeholder="https://storage.googleapis.com/your-bucket"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm">
                        <p class="text-xs text-gray-500 mt-1">เว้นว่าง = ใช้ standard storage.googleapis.com URL (bucket ต้อง public)</p>
                    </div>
                </div>
                <details class="mt-4">
                    <summary class="cursor-pointer text-sm font-medium text-blue-700 dark:text-blue-400">📖 วิธีตั้งค่า Google Cloud Storage (5 นาที)</summary>
                    <ol class="mt-3 ml-5 text-xs text-gray-700 dark:text-gray-300 list-decimal space-y-2">
                        <li>เข้า <a href="https://console.cloud.google.com/storage/browser" target="_blank" class="text-blue-600 underline">GCP Console → Cloud Storage</a></li>
                        <li>กด "CREATE" → ตั้งชื่อ bucket → เลือก location (asia-southeast1) → Standard storage class → Uniform access</li>
                        <li>(ถ้าอยากใช้ public URL) Permissions → Grant access → <code>allUsers</code> = Storage Object Viewer</li>
                        <li>ใช้ service account เดียวกับ Translate/Vision/TTS — เพิ่ม role "Storage Object Admin" ที่ <a href="https://console.cloud.google.com/iam-admin/iam" target="_blank" class="text-blue-600 underline">IAM</a></li>
                        <li>วางชื่อ bucket + credentials path ในฟอร์มด้านบน → กด "ทดสอบ"</li>
                        <li>💡 5GB แรกฟรี/เดือน · หลังนั้น $0.020/GB</li>
                    </ol>
                </details>
            </div>

            {{-- S3 config --}}
            <div x-show="driver === 's3'" x-cloak class="mb-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-300 dark:border-yellow-700">
                <h4 class="font-semibold text-gray-900 dark:text-white mb-3">🟨 AWS S3</h4>
                @if(! class_exists(\League\Flysystem\AwsS3V3\AwsS3V3Adapter::class))
                    <div class="mb-3 p-3 bg-red-50 dark:bg-red-900/20 border border-red-300 dark:border-red-700 rounded text-sm">
                        ⚠️ <strong>ยังไม่ได้ติดตั้ง package</strong> — รันคำสั่งนี้ที่ server ก่อน:<br>
                        <code class="block mt-1 p-2 bg-gray-900 text-green-400 rounded font-mono text-xs">composer require league/flysystem-aws-s3-v3 "^3.0"</code>
                    </div>
                @endif
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-gray-700 dark:text-gray-300 mb-1">🔑 Access Key ID</label>
                        <input type="text" x-model="s3.access_key_id"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm font-mono">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-700 dark:text-gray-300 mb-1">🔒 Secret Access Key</label>
                        <input type="password" x-model="s3.secret_access_key" placeholder="เว้นว่าง = ใช้ค่าเดิม"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm font-mono">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-700 dark:text-gray-300 mb-1">🌏 Region</label>
                        <input type="text" x-model="s3.region" placeholder="ap-southeast-1"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-700 dark:text-gray-300 mb-1">🪣 Bucket Name</label>
                        <input type="text" x-model="s3.bucket"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs text-gray-700 dark:text-gray-300 mb-1">🌐 Public URL (CDN/Static website)</label>
                        <input type="text" x-model="s3.public_url"
                               placeholder="https://my-bucket.s3.ap-southeast-1.amazonaws.com"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm">
                    </div>
                </div>
                <details class="mt-4">
                    <summary class="cursor-pointer text-sm font-medium text-yellow-700 dark:text-yellow-400">📖 วิธีตั้งค่า AWS S3 (10 นาที)</summary>
                    <ol class="mt-3 ml-5 text-xs text-gray-700 dark:text-gray-300 list-decimal space-y-2">
                        <li>เข้า <a href="https://s3.console.aws.amazon.com/" target="_blank" class="text-yellow-600 underline">AWS S3 Console</a></li>
                        <li>Create bucket → ตั้งชื่อ → region: <code>ap-southeast-1</code> (Singapore)</li>
                        <li>Permissions → ปิด "Block all public access" → Bucket Policy: อนุญาต public read สำหรับ object</li>
                        <li>IAM → Users → Create user → permission: <code>AmazonS3FullAccess</code> → จด Access Key + Secret</li>
                        <li>วางใน form → กด "ทดสอบ" → "บันทึก"</li>
                        <li>💡 5GB แรกฟรี/เดือน 12 เดือน · หลังนั้น ~$0.025/GB + $0.09/GB egress</li>
                    </ol>
                </details>
            </div>

            {{-- Action buttons --}}
            <div class="flex flex-wrap gap-2 mb-3">
                <button type="button" @click="testConnection()" :disabled="busy"
                        class="px-4 py-2 text-sm bg-amber-500 hover:bg-amber-600 disabled:opacity-50 text-white rounded-lg flex items-center gap-1">
                    <span x-show="!busy">🧪 ทดสอบการเชื่อมต่อ</span>
                    <span x-show="busy" x-cloak>⏳ กำลังทดสอบ...</span>
                </button>
                <button type="button" @click="saveDriver()" :disabled="busy"
                        class="px-4 py-2 text-sm bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white rounded-lg flex items-center gap-1">
                    💾 บันทึก driver
                </button>
                <button type="button" @click="showMigrate = !showMigrate"
                        class="px-4 py-2 text-sm bg-purple-600 hover:bg-purple-700 text-white rounded-lg flex items-center gap-1">
                    🚚 ย้ายไฟล์เก่า
                </button>
            </div>

            {{-- Result message --}}
            <div x-show="message" x-cloak x-transition
                 :class="messageOk ? 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-800 dark:text-emerald-200 border-emerald-300' : 'bg-red-100 dark:bg-red-900/40 text-red-800 dark:text-red-200 border-red-300'"
                 class="p-3 rounded-lg text-sm border" x-text="message"></div>

            {{-- Migrate section --}}
            <div x-show="showMigrate" x-cloak x-collapse class="mt-4 p-4 bg-purple-50 dark:bg-purple-900/20 border border-purple-300 dark:border-purple-700 rounded-lg">
                <h4 class="font-semibold text-gray-900 dark:text-white mb-2">🚚 ย้ายไฟล์เก่าระหว่าง driver</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                    ถ้าเปลี่ยน driver แล้ว ไฟล์เก่าจะอยู่ที่ driver เก่า — กดย้ายเพื่อ migrate ไฟล์ทั้งหมดไปยัง driver ใหม่
                </p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-2 mb-3">
                    <div>
                        <label class="block text-xs text-gray-700 dark:text-gray-300 mb-1">จาก</label>
                        <select x-model="migrate.from" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-sm">
                            <option value="local">📁 Local</option>
                            <option value="r2">🟧 R2</option>
                            <option value="firebase">🔥 Firebase</option>
                            <option value="gcs">🔵 GCS</option>
                            <option value="s3">🟨 S3</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-700 dark:text-gray-300 mb-1">ไป</label>
                        <select x-model="migrate.to" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-sm">
                            <option value="local">📁 Local</option>
                            <option value="r2">🟧 R2</option>
                            <option value="firebase">🔥 Firebase</option>
                            <option value="gcs">🔵 GCS</option>
                            <option value="s3">🟨 S3</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <label class="flex items-center text-sm">
                            <input type="checkbox" x-model="migrate.dryRun" class="mr-2 rounded">
                            <span class="text-gray-700 dark:text-gray-300">🧪 Dry run (ไม่ย้ายจริง)</span>
                        </label>
                    </div>
                </div>
                <button type="button" @click="runMigrate()" :disabled="busy"
                        class="px-4 py-2 text-sm bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white rounded">
                    🚀 เริ่มย้าย
                </button>
                <pre x-show="migrateOutput" x-cloak class="mt-3 p-3 bg-gray-900 text-green-400 text-xs font-mono rounded overflow-x-auto max-h-64" x-text="migrateOutput"></pre>
            </div>
        </div> {{-- End Voice Storage --}}

        {{-- ===== 🙏 Satisfaction Detector (2026-05-07 Phase 2) ===== --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6" data-fortune-tab="engagement"
             x-data="{
                 satisfactionEnabled: {{ old('satisfaction_detection_enabled', $settings->satisfaction_detection_enabled ?? true) ? 'true' : 'false' }},
             }">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    🙏 Satisfaction Detector
                    <span class="text-xs font-normal text-pink-600 dark:text-pink-400">(ปิด session อบอุ่นเมื่อลูกค้าพอใจ)</span>
                </h3>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="hidden" name="satisfaction_detection_enabled" value="0">
                    <input type="checkbox" name="satisfaction_detection_enabled" value="1" x-model="satisfactionEnabled" class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-pink-300 dark:peer-focus:ring-pink-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:after:border-gray-500 peer-checked:bg-pink-600"></div>
                </label>
            </div>

            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                ตรวจ "ขอบคุณ / พอแล้ว / ดีมาก / บาย" → ปิด session แบบอบอุ่น (ไม่เสนอบริการเพิ่ม)
            </p>

            <div x-show="satisfactionEnabled" x-cloak x-collapse>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    💌 ข้อความปิด session (admin custom — เว้นว่าง = ใช้ default)
                </label>
                <textarea name="satisfaction_close_message" rows="4"
                          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                          placeholder="🌙 ขอบคุณ{name}ที่ไว้วางใจแม่หมอจันทรานะคะ&#10;&#10;ขอให้ดวงดาวคุ้มครอง พบเจอแต่สิ่งดี ๆ ✨&#10;หากต้องการปรึกษาเพิ่มเติมเมื่อใด แม่หมอพร้อมเสมอค่ะ 🙏">{{ old('satisfaction_close_message', $settings->satisfaction_close_message) }}</textarea>
                <p class="text-xs text-gray-500 mt-1">Variables: <code>{name}</code> = ชื่อลูกค้า (default 'เจ้าชะตา')</p>
            </div>
        </div> {{-- End Satisfaction Detector --}}

        {{-- Comment Engagement Settings --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6" data-fortune-tab="engagement">
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
                {{-- 🚫 (2026-05-24) Sub-toggle: ตอบคอมเม้นต์สาธารณะ
                     Default: false — รอ Facebook App Review อนุมัติ pages_manage_engagement scope
                     ก่อน flip ON: ทดสอบว่า replyToComment สำเร็จ (ไม่ใช่ 403) ก่อน --}}
                <div class="mb-4 p-4 rounded-lg border-2"
                     :class="enablePublicCommentReply
                        ? 'bg-amber-50 dark:bg-amber-900/20 border-amber-300 dark:border-amber-700'
                        : 'bg-blue-50 dark:bg-blue-900/20 border-blue-300 dark:border-blue-700'">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <span class="text-lg" x-text="enablePublicCommentReply ? '⚠️' : '🔒'"></span>
                            <span class="font-semibold text-gray-900 dark:text-white">
                                ตอบคอมเม้นต์สาธารณะ (Public Comment Reply)
                            </span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="hidden" name="enable_public_comment_reply" value="0">
                            <input type="checkbox" name="enable_public_comment_reply" value="1"
                                   {{ old('enable_public_comment_reply', $settings->enable_public_comment_reply ?? false) ? 'checked' : '' }}
                                   class="sr-only peer"
                                   x-model="enablePublicCommentReply">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-amber-300 dark:peer-focus:ring-amber-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:after:border-gray-500 peer-checked:bg-amber-600"></div>
                        </label>
                    </div>
                    <div x-show="!enablePublicCommentReply" class="text-xs text-blue-800 dark:text-blue-200 space-y-1">
                        <p>🔒 <strong>ปิดอยู่ (แนะนำ)</strong> — บอทส่งเฉพาะ DM ไม่ตอบในโพสต์สาธารณะ</p>
                        <p>• <strong>ประหยัด AI quota</strong> — ไม่เรียก AI สำหรับ comment_reply (~400 tokens/ครั้ง)</p>
                        <p>• <strong>ไม่ขึ้น 403 error</strong> — ระบบไม่พยายามโพสต์ในคอมเม้นต์ (Page Token ขาด <code>pages_manage_engagement</code> scope)</p>
                        <p>• DM ผ่าน Page Messaging ใช้ scope แยก ทำงานปกติ</p>
                    </div>
                    <div x-show="enablePublicCommentReply" class="text-xs text-amber-800 dark:text-amber-200 space-y-1">
                        <p>⚠️ <strong>เปิดอยู่</strong> — บอทจะตอบทั้งในคอมเม้นต์สาธารณะ + ส่ง DM</p>
                        <p>• ⚠️ <strong>ต้องการ <code>pages_manage_engagement</code> scope</strong> — ต้องผ่าน Facebook App Review</p>
                        <p>• ถ้ายังไม่ได้รับอนุมัติ → AI gen ทุก comment เปล่าๆ (เผา quota)</p>
                        <p>• เช็คได้จาก log: ถ้าเห็น <code>"Page Access Token ขาด pages_manage_engagement scope"</code> → ปิด toggle นี้</p>
                    </div>
                </div>

                {{-- โหมดการทำงาน --}}
                <div class="mb-4" x-show="enablePublicCommentReply" x-transition>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        โหมดสร้างข้อความตอบคอมเม้นต์
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
                <div x-show="enablePublicCommentReply && commentEngagementMode === 'ai'" x-transition class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        AI Prompt (คำสั่งสำหรับ AI สร้างข้อความชวน)
                    </label>
                    <textarea name="comment_engagement_prompt" rows="8"
                              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 font-mono text-sm"
                              placeholder="ใช้ค่าเริ่มต้น (ถ้าว่าง)">{{ old('comment_engagement_prompt', $settings->comment_engagement_prompt) }}</textarea>
                    <p class="text-xs text-gray-400 mt-1">Placeholders: {comment} = ข้อความคอมเม้นต์, {name} = ชื่อผู้คอมเม้นต์, {profile_info} = ข้อมูลโปรไฟล์ (เพศ/วันเกิด) | เว้นว่างเพื่อใช้ค่าเริ่มต้น</p>
                </div>

                {{-- Template Mode: Comment Reply --}}
                <div x-show="enablePublicCommentReply && commentEngagementMode === 'template'" x-transition class="mb-4">
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
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6" data-fortune-tab="service">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                ⚙️ การตั้งค่าการใช้งานพื้นฐาน
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                {{-- 🎁 (2026-05-03) ระบบใหม่: ทำนายฟรี 1 ใบ ครั้งเดียว/platform --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        🎁 ทำนายฟรี 1 ใบ (ครั้งแรก/ลูกค้า)
                    </label>
                    <label class="flex items-center gap-3 p-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 cursor-pointer hover:border-amber-400 transition">
                        <input type="checkbox" name="enable_free_card_reading" value="1"
                               {{ old('enable_free_card_reading', $settings->enable_free_card_reading ?? true) ? 'checked' : '' }}
                               class="w-5 h-5 text-amber-600 rounded focus:ring-amber-500">
                        <span class="text-sm text-gray-900 dark:text-white">เปิดระบบทำนายฟรี 1 ใบ</span>
                    </label>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        เปิด → ลูกค้าใหม่ครั้งแรก/platform เห็นปุ่ม "🎁 ทำนายฟรี (1 ใบ)" ใน DM แรก
                        <br>กดปุ่ม → จั่วไพ่ 1 ใบ + Gemini ทำนายสถานการณ์ปัจจุบัน + ชวนซื้อ 39/99 เนียน
                        <br>ปฏิเสธ → คำลา + ปรัชญาชีวิต (ไม่ฮาร์ดเซล)
                    </p>
                    @if(($settings->enable_free_card_reading ?? true))
                        <p class="mt-2 text-xs text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 rounded px-2 py-1">
                            ✅ เปิดสิทธิ์ฟรี — ลูกค้าใหม่ทุกคนได้ทำนาย 1 ใบฟรี <strong>ครั้งเดียวต่อท่าน</strong>
                        </p>
                    @else
                        <p class="mt-2 text-xs font-medium text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 rounded px-2 py-1">
                            ⚠️ ปิดอยู่ — ลูกค้าใหม่จะเห็นแค่ 39฿ / 99฿ (ไม่มีปุ่มฟรี)
                        </p>
                    @endif
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

            {{-- 📰 (2026-05-03) บริบทข่าวบ้านเมืองสำหรับทำนายฟรี --}}
            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    📰 บริบทข่าว/เหตุการณ์บ้านเมืองปัจจุบัน (ใช้กับทำนายฟรี)
                </label>
                <textarea name="free_card_news_context" rows="3"
                          placeholder="เช่น: เศรษฐกิจชะลอตัว, การเลือกตั้งใกล้, น้ำมันแพง, ดอกเบี้ยขึ้น"
                          class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500">{{ old('free_card_news_context', $settings->free_card_news_context ?? '') }}</textarea>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    💡 AI Gemini จะ inject บริบทนี้ลง prompt ทำนายฟรี เพื่อให้คำทำนายผูกกับยุคปัจจุบัน
                    <br>เว้นว่าง = ใช้ความรู้ทั่วไปของ AI เอง
                </p>
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

            {{-- 🔍 (2026-05-16) Fuzzy Payment Match — รับโอนยอดไม่ตรง --}}
            <div class="mt-6 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-700"
                 x-data="{ enabled: {{ ($settings->enable_fuzzy_payment_match ?? false) ? 'true' : 'false' }} }">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <label class="block text-sm font-bold text-gray-900 dark:text-white">
                            🔍 รับโอนยอดไม่ตรง (Fuzzy Payment Match)
                        </label>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Auto-approve บิลที่ลูกค้าโอนยอดใกล้เคียง — ลูกค้าโอน 39.00 แทน 39.37 ก็ผ่านได้
                        </p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="enable_fuzzy_payment_match" value="0">
                        <input type="checkbox" name="enable_fuzzy_payment_match" value="1"
                               x-model="enabled"
                               class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 dark:bg-gray-700 peer-focus:ring-2 peer-focus:ring-amber-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-500"></div>
                    </label>
                </div>

                <div x-show="enabled" x-cloak x-transition class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 mt-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                            รับขาดได้ (บาท)
                        </label>
                        <input type="number" name="fuzzy_underpay_max_baht" step="0.01" min="0" max="100"
                               value="{{ $settings->fuzzy_underpay_max_baht ?? 1.00 }}"
                               class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">เช่น 1 = ลูกค้าโอนขาดได้ ≤ 1 บาท (39.37 → 39.00)</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                            รับเกินได้ (บาท)
                        </label>
                        <input type="number" name="fuzzy_overpay_max_baht" step="0.01" min="0" max="500"
                               value="{{ $settings->fuzzy_overpay_max_baht ?? 11.00 }}"
                               class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">เช่น 11 = ลูกค้าโอนเกินได้ ≤ 11 บาท (99 → 110)</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                            ช่วงเวลาตรวจ (นาที)
                        </label>
                        <input type="number" name="fuzzy_window_minutes" step="1" min="5" max="1440"
                               value="{{ $settings->fuzzy_window_minutes ?? 60 }}"
                               class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">SMS หลังบิลถูกสร้าง — default 60 นาที</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                            ชื่อผู้โอนต้องตรง (%)
                        </label>
                        <input type="number" name="fuzzy_name_auto_threshold" step="1" min="0" max="100"
                               value="{{ $settings->fuzzy_name_auto_threshold ?? 70 }}"
                               class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">% similar กับชื่อ Facebook — default 70%</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                            แจ้ง admin ถ้า delta &gt; (บาท)
                        </label>
                        <input type="number" name="fuzzy_admin_alert_above_baht" step="0.01" min="0" max="1000"
                               value="{{ $settings->fuzzy_admin_alert_above_baht ?? 5.00 }}"
                               class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ส่ง LINE OA เตือน admin ถ้าส่วนต่าง &gt; ค่านี้</p>
                    </div>
                </div>

                <div x-show="enabled" x-cloak class="mt-3 p-3 bg-blue-50 dark:bg-blue-900/30 rounded-lg text-xs text-blue-700 dark:text-blue-300">
                    💡 <strong>วิธีทำงาน:</strong> เมื่อ SMS เข้าและไม่พบ UPA ตรง ระบบจะค้นบิล pending ในช่วงเวลา {{ $settings->fuzzy_window_minutes ?? 60 }} นาที
                    → ถ้า delta อยู่ในกรอบ (-{{ $settings->fuzzy_underpay_max_baht ?? 1 }} ถึง +{{ $settings->fuzzy_overpay_max_baht ?? 11 }})
                    + ชื่อผู้โอนตรงพอ ({{ $settings->fuzzy_name_auto_threshold ?? 70 }}%) → auto-approve
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

        {{-- 🌟 (2026-05-04) Group Invite + Monthly Free Claim --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6 border-2 border-purple-200 dark:border-purple-700" data-fortune-tab="engagement">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                🌟 ระบบเชิญเข้ากลุ่ม Facebook + แคมเปญดูฟรีรายเดือน
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                บอทส่งปุ่มเชิญเข้ากลุ่มอัตโนมัติให้คนทักใหม่ที่ยังไม่ดูดวง<br>
                + ลิงก์ในโพสต์กลุ่มให้สมาชิกกดรับสิทธิ์ดูไพ่ฟรี 1 ใบทุกเดือน (รีเซ็ตวันที่ 1)
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- URL กลุ่ม --}}
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        🔗 URL กลุ่ม Facebook
                    </label>
                    <input type="url" name="fortune_group_url"
                           value="{{ old('fortune_group_url', $settings->fortune_group_url ?? '') }}"
                           placeholder="https://www.facebook.com/groups/1539006181120751"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        💡 ใส่ URL กลุ่มเต็มรูป facebook.com/groups/...
                    </p>
                </div>

                {{-- Toggle เชิญเข้ากลุ่ม --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        🎯 ปุ่มเชิญเข้ากลุ่มใน Messenger
                    </label>
                    <label class="flex items-center gap-3 p-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 cursor-pointer hover:border-purple-400 transition">
                        <input type="checkbox" name="fortune_group_invite_enabled" value="1"
                               {{ old('fortune_group_invite_enabled', $settings->fortune_group_invite_enabled ?? false) ? 'checked' : '' }}
                               class="w-5 h-5 text-purple-600 rounded focus:ring-purple-500">
                        <span class="text-sm text-gray-900 dark:text-white">เปิดใช้งานปุ่มเชิญ</span>
                    </label>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        บอทจะส่งปุ่มเชิญให้ลูกค้าใหม่ (ที่ยังไม่ดูดวง) ตอน GET_STARTED
                        <br>Cooldown 7 วัน/คน — ไม่สแปม
                    </p>
                </div>

                {{-- Toggle Monthly Claim --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        🎁 แคมเปญดูฟรีรายเดือน
                    </label>
                    <label class="flex items-center gap-3 p-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 cursor-pointer hover:border-purple-400 transition">
                        <input type="checkbox" name="monthly_free_claim_enabled" value="1"
                               {{ old('monthly_free_claim_enabled', $settings->monthly_free_claim_enabled ?? false) ? 'checked' : '' }}
                               class="w-5 h-5 text-purple-600 rounded focus:ring-purple-500">
                        <span class="text-sm text-gray-900 dark:text-white">เปิดให้ claim ผ่านลิงก์</span>
                    </label>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Landing: <code class="text-purple-600">/fortune/monthly-claim</code>
                        <br>กดได้ครั้งเดียวต่อเดือน — รีเซ็ตวันที่ 1
                    </p>
                </div>

                {{-- ข้อความเชิญ --}}
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        💬 ข้อความเชิญเข้ากลุ่ม (เว้นว่าง = ใช้ default)
                    </label>
                    <textarea name="fortune_group_invite_message" rows="4"
                              placeholder="🌟 อยากดูดวงฟรีทุกเดือน? เข้ากลุ่มสมาชิก..."
                              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500">{{ old('fortune_group_invite_message', $settings->fortune_group_invite_message ?? '') }}</textarea>
                </div>

                {{-- ข้อความหลัง claim สำเร็จ --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ✅ ข้อความหลัง claim สำเร็จ
                    </label>
                    <textarea name="monthly_free_claim_success_message" rows="3"
                              placeholder="🎁 รับสิทธิ์ดูไพ่ฟรี 1 ใบประจำเดือนเรียบร้อย..."
                              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500">{{ old('monthly_free_claim_success_message', $settings->monthly_free_claim_success_message ?? '') }}</textarea>
                </div>

                {{-- ข้อความ claim ซ้ำ --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ⛔ ข้อความเมื่อ claim ซ้ำเดือนเดียวกัน
                    </label>
                    <textarea name="monthly_free_claim_already_message" rows="3"
                              placeholder="🌙 คุณได้รับสิทธิ์ดูฟรีของเดือนนี้ไปแล้ว..."
                              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500">{{ old('monthly_free_claim_already_message', $settings->monthly_free_claim_already_message ?? '') }}</textarea>
                </div>
            </div>

            {{-- เครื่องมือ: คัดลอก bait post + ลิงก์ --}}
            @php
                $claimSvc = app(\App\Services\FortuneMonthlyClaimService::class);
                $baitCopy = $claimSvc->generateBaitPostCopy();
                $landingUrl = $claimSvc->landingUrl();
            @endphp
            <div class="mt-6 p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg border border-purple-200 dark:border-purple-700">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="font-semibold text-gray-900 dark:text-white">
                        📋 คัดลอกโพสต์ "ล่อเป้า" สำหรับโพสต์ในกลุ่ม
                    </h4>
                    <button type="button"
                            onclick="navigator.clipboard.writeText(document.getElementById('bait-post-copy').value); this.textContent='✅ คัดลอกแล้ว!'; setTimeout(()=>this.textContent='📋 คัดลอก',2000)"
                            class="px-3 py-1 bg-purple-600 hover:bg-purple-700 text-white text-sm rounded transition">
                        📋 คัดลอก
                    </button>
                </div>
                <textarea id="bait-post-copy" rows="10" readonly
                          class="w-full px-3 py-2 text-sm font-mono bg-white dark:bg-gray-900 border border-purple-300 dark:border-purple-600 rounded text-gray-900 dark:text-gray-100">{{ $baitCopy }}</textarea>
                <p class="mt-2 text-xs text-gray-600 dark:text-gray-400">
                    💡 Copy-paste ไปโพสต์ในกลุ่ม เป็นโพสต์รายเดือน (ทุกวันที่ 1) — บอทจะ handle ลิงก์เอง
                    <br>📍 Landing URL: <code class="text-purple-600 dark:text-purple-400">{{ $landingUrl }}</code>
                </p>
            </div>

            {{-- คำแนะนำ Linked Pages --}}
            <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-700">
                <h4 class="font-semibold text-gray-900 dark:text-white mb-2">
                    💡 อยากให้โพสต์ของเพจ auto-mirror เข้ากลุ่ม?
                </h4>
                <p class="text-sm text-gray-700 dark:text-gray-300 mb-2">
                    Meta บล็อก API auto-post ของ Page เข้ากลุ่มแล้ว (ตั้งแต่ April 2024) — แต่มี <strong>Linked Pages</strong> feature ใช้แทนได้:
                </p>
                <ol class="list-decimal list-inside text-sm text-gray-700 dark:text-gray-300 space-y-1">
                    <li>เปิดกลุ่ม → <strong>Settings</strong> (จัดการกลุ่ม)</li>
                    <li>ไปที่ <strong>Manage</strong> → <strong>Linked Pages</strong></li>
                    <li>เลือกเพจของคุณ → <strong>Link</strong></li>
                    <li>หลังจากนั้น โพสต์ของเพจจะ auto-appear ในกลุ่ม (treated as native, reach เต็ม)</li>
                </ol>
                <p class="mt-2 text-xs text-gray-600 dark:text-gray-400">
                    ⚠️ Auto-post ระบบ Mystic Content (08:00/20:00) จะโพสต์ในเพจ + mirror เข้ากลุ่มอัตโนมัติ
                </p>
            </div>
        </div>

        {{-- Freemium: Deep Reading Settings --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6" data-fortune-tab="service" x-data="{ enableDeep: {{ old('enable_deep_reading', $settings->enable_deep_reading ?? true) ? 'true' : 'false' }} }">
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
                               value="{{ old('deep_reading_price', $settings->deep_reading_price ?? 39) }}"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">ราคาต่อ 1 คำถาม (Bot จะแสดงราคานี้ทุกจุด — ค่าเริ่มต้น 39)</p>
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
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6" data-fortune-tab="service" x-data="{ enableSub: {{ old('subscription_enabled', $settings->subscription_enabled ?? true) ? 'true' : 'false' }} }">
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
        }" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6" data-fortune-tab="service">
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

                    {{-- 🆕 (2026-05-01) Pattern update notice --}}
                    @if(empty($settings->deep_prompt_template) && !empty($settings->deep_prompt_template_legacy ?? null))
                    <div class="mb-3 p-4 bg-amber-50 dark:bg-amber-900/30 border border-amber-300 dark:border-amber-700 rounded-lg">
                        <div class="flex items-start gap-2">
                            <span class="text-2xl">🔔</span>
                            <div class="flex-1 text-sm">
                                <p class="font-bold text-amber-900 dark:text-amber-100">Prompt ถูกอัปเดตเป็นแพทเทิร์นใหม่ (2026-05-01)</p>
                                <p class="mt-1 text-amber-700 dark:text-amber-300">
                                    เวอร์ชันเดิมของคุณถูก backup ใน <code class="px-1 bg-amber-100 dark:bg-amber-800 rounded">deep_prompt_template_legacy</code> ใน DB
                                    <br>เวอร์ชันใหม่ มี: Section A (ทาย persona) + closing + Thai cultural context + ความยาว 600-900 คำ
                                </p>
                            </div>
                        </div>
                    </div>
                    @endif

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
                            <span>{tarot_card} — ไพ่ที่เปิดได้ (ถ้ามี)</span>
                            <span>{previous_context} — คำทำนายก่อนหน้า</span>
                            <span>{user_profile} — ข้อมูลผู้ถาม (JSON)</span>
                            <span class="font-bold text-purple-800 dark:text-purple-200">{section_a_block} — 🆕 Section A (Q1)</span>
                            <span class="font-bold text-purple-800 dark:text-purple-200">{closing_section} — 🆕 ปิดท้าย (Q สุดท้าย)</span>
                            <span class="font-bold text-purple-800 dark:text-purple-200">{thai_context} — 🆕 บริบทไทย (วันหวย ฯลฯ)</span>
                            <span class="font-bold text-purple-800 dark:text-purple-200">{life_stage_hint} — 🆕 ช่วงชีวิต</span>
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
                level1Type: '{{ old('fortune_level1_commission_type', $settings->fortune_level1_commission_type ?? 'fixed') }}',
                level1Amount: '{{ old('fortune_level1_commission_amount', $settings->fortune_level1_commission_amount ?? 10) }}',
                level2Enabled: {{ old('fortune_level2_enabled', $settings->fortune_level2_enabled ?? true) ? 'true' : 'false' }},
                centralFallbackEnabled: {{ old('fortune_central_fallback_enabled', $settings->fortune_central_fallback_enabled ?? true) ? 'true' : 'false' }},
                centralUserId: '{{ old('fortune_central_user_id', $settings->fortune_central_user_id ?? '') }}',
                level2Type: '{{ old('fortune_level2_commission_type', $settings->fortune_level2_commission_type ?? 'fixed') }}',
                level2Amount: '{{ old('fortune_level2_commission_amount', $settings->fortune_level2_commission_amount ?? 5) }}',
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
             }" data-fortune-tab="service">
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

                {{-- Static Commission Amount (เก็บไว้สำหรับ backward compatibility) --}}
                <input type="hidden" name="fortune_static_commission_amount" x-model="level1Amount">

                {{-- ===== Level 1/Level 2 Settings (แสดงเมื่อเลือก static mode) ===== --}}
                <div x-show="commissionMode === 'static'" x-transition x-cloak class="space-y-4">

                    {{-- Level 1: สายตรง --}}
                    <div class="p-4 rounded-lg border border-green-200 dark:border-green-800 bg-green-50/50 dark:bg-green-900/20">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="text-lg">🤝</span>
                            <span class="font-bold text-gray-900 dark:text-white">Level 1 — ค่าแนะนำสายตรง</span>
                            <span class="text-xs bg-green-600 text-white px-2 py-0.5 rounded-full">ผู้แนะนำตรง</span>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">ประเภท</label>
                                <select name="fortune_level1_commission_type" x-model="level1Type"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-green-500">
                                    <option value="fixed">จำนวนเงินคงที่ (บาท)</option>
                                    <option value="percent">เปอร์เซ็นต์จากราคาดูดวง (%)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                    <span x-show="level1Type === 'fixed'">จำนวนเงิน (บาท)</span>
                                    <span x-show="level1Type === 'percent'">เปอร์เซ็นต์ (%)</span>
                                </label>
                                <input type="number" name="fortune_level1_commission_amount" step="0.01" min="0"
                                       x-model="level1Amount"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-green-500">
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                            <span x-show="level1Type === 'fixed'">เช่น ตั้ง 10 → ผู้แนะนำตรงได้ 10 บาท/ครั้ง</span>
                            <span x-show="level1Type === 'percent'">เช่น ตั้ง 10% ราคาดูดวง 99 บาท → ได้ 9.90 บาท/ครั้ง</span>
                        </p>
                    </div>

                    {{-- Level 2: ชั้นหลาน --}}
                    <div class="p-4 rounded-lg border border-amber-200 dark:border-amber-800 bg-amber-50/50 dark:bg-amber-900/20">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <span class="text-lg">👶</span>
                                <span class="font-bold text-gray-900 dark:text-white">Level 2 — ค่าแนะนำชั้นหลาน</span>
                                <span class="text-xs bg-amber-600 text-white px-2 py-0.5 rounded-full">ผู้แนะนำของผู้แนะนำ</span>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="hidden" name="fortune_level2_enabled" value="0">
                                <input type="checkbox" name="fortune_level2_enabled" value="1" x-model="level2Enabled"
                                       class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-amber-300 dark:peer-focus:ring-amber-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:after:border-gray-500 peer-checked:bg-amber-500"></div>
                                <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300" x-text="level2Enabled ? 'เปิด' : 'ปิด'"></span>
                            </label>
                        </div>
                        <div x-show="level2Enabled" x-transition x-cloak>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">ประเภท</label>
                                    <select name="fortune_level2_commission_type" x-model="level2Type"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-amber-500">
                                        <option value="fixed">จำนวนเงินคงที่ (บาท)</option>
                                        <option value="percent">เปอร์เซ็นต์จากราคาดูดวง (%)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                        <span x-show="level2Type === 'fixed'">จำนวนเงิน (บาท)</span>
                                        <span x-show="level2Type === 'percent'">เปอร์เซ็นต์ (%)</span>
                                    </label>
                                    <input type="number" name="fortune_level2_commission_amount" step="0.01" min="0"
                                           x-model="level2Amount"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-amber-500">
                                </div>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                <span x-show="level2Type === 'fixed'">เช่น ตั้ง 5 → ผู้แนะนำชั้น 2 ได้ 5 บาท/ครั้ง</span>
                                <span x-show="level2Type === 'percent'">เช่น ตั้ง 5% ราคาดูดวง 99 บาท → ได้ 4.95 บาท/ครั้ง</span>
                            </p>
                        </div>
                        <p x-show="!level2Enabled" class="text-xs text-gray-400 dark:text-gray-500">
                            ปิดการจ่ายคอมมิชชั่นชั้นหลาน — จ่ายเฉพาะ Level 1 (สายตรง) เท่านั้น
                        </p>
                    </div>
                </div>

                {{-- 🏛️ กระเป๋ากลาง (Central Wallet Fallback) --}}
                <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300">
                                🏛️ กระเป๋ากลาง (Fallback)
                            </label>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                ถ้าลูกค้า<strong>ไม่มีผู้แนะนำ</strong> หรือผู้แนะนำ<strong>ไม่ active</strong> →
                                ค่าแนะนำจะเข้ากระเป๋าของ user นี้แทน (มี audit trail สมบูรณ์)
                            </p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer ml-4">
                            <input type="hidden" name="fortune_central_fallback_enabled" value="0">
                            <input type="checkbox" name="fortune_central_fallback_enabled" value="1"
                                   x-model="centralFallbackEnabled"
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-600"></div>
                        </label>
                    </div>

                    <div x-show="centralFallbackEnabled" x-transition x-cloak class="space-y-2">
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">
                            User ID ของกระเป๋ากลาง
                        </label>
                        <input type="number" name="fortune_central_user_id" min="1"
                               x-model="centralUserId"
                               placeholder="เช่น 1 (Super Admin)"
                               class="w-full md:w-48 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-amber-500">
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            ระบุ User ID จากหน้า <a href="{{ route('admin.users.index') }}" class="text-amber-600 dark:text-amber-400 hover:underline">จัดการผู้ใช้</a>
                            — แนะนำใช้ user "Super Admin" หรือบัญชีระบบเฉพาะ
                        </p>
                        <div class="p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-700">
                            <p class="text-xs text-amber-800 dark:text-amber-300">
                                ⚠️ user ที่เลือกต้องเป็นสมาชิก MLM (มี MlmMember record) — ถ้าไม่มี ระบบจะ skip fallback
                                และเงินยังคงอยู่ในบัญชีบริษัทแต่ไม่มี record (เหมือนเดิม)
                            </p>
                        </div>
                    </div>

                    <p x-show="!centralFallbackEnabled" class="text-xs text-gray-400 dark:text-gray-500 mt-2">
                        ⚠️ ปิดอยู่ — เมื่อหา sponsor ไม่ได้ ค่าแนะนำจะ<strong class="text-red-500">หายโดยไม่มี record</strong>
                    </p>
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

        {{-- ========================================= --}}
        {{-- 🎯 Admin Takeover (เทคโอเวอร์) --}}
        {{-- ========================================= --}}
        <div id="takeover" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6 border-l-4 border-purple-500" data-fortune-tab="engagement">
            <div class="flex items-center justify-between flex-wrap gap-3 mb-5">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        🎯 ระบบเทคโอเวอร์ (Takeover Control)
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        ให้แม่หมอ/แอดมินคุยแทน AI ทั้ง LINE และ Facebook — ตรวจจับอัตโนมัติเมื่อแอดมินพิมพ์ หรือลูกค้าขอ
                    </p>
                </div>
                <a href="{{ route('admin.fortune.takeover.index') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 dark:bg-purple-500 dark:hover:bg-purple-600 text-white rounded-lg transition text-sm font-medium">
                    📋 ไปที่แผงเทคโอเวอร์ →
                </a>
            </div>

            {{-- เปิด/ปิดระบบ --}}
            <div class="mb-4">
                <label class="inline-flex items-center cursor-pointer">
                    <input type="checkbox"
                           name="admin_handover_enabled"
                           value="1"
                           {{ $settings->admin_handover_enabled ? 'checked' : '' }}
                           class="form-checkbox h-5 w-5 text-purple-600 rounded">
                    <span class="ml-2 text-gray-900 dark:text-white font-medium">เปิดใช้งานระบบเทคโอเวอร์</span>
                </label>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 ml-7">
                    เมื่อปิด — บอท AI จะตอบทุกข้อความเสมอ ไม่เคารพการเทคโอเวอร์
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- ระยะเวลา default --}}
                <div>
                    <label class="block text-sm font-medium text-gray-900 dark:text-white mb-1">
                        ⏱ ระยะเวลา Default (นาที)
                    </label>
                    <input type="number"
                           name="admin_handover_timeout"
                           value="{{ old('admin_handover_timeout', $settings->admin_handover_timeout ?? 15) }}"
                           min="1" max="1440"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        เมื่อแอดมินพิมพ์ตอบอัตโนมัติ — AI จะหยุดเป็นเวลานี้
                    </p>
                </div>

                {{-- คำสั่งให้ AI กลับมา --}}
                <div>
                    <label class="block text-sm font-medium text-gray-900 dark:text-white mb-1">
                        ✨ คำสั่งให้ AI กลับมา
                    </label>
                    <input type="text"
                           name="ai_resume_command"
                           value="{{ old('ai_resume_command', $settings->ai_resume_command ?? '/ai') }}"
                           placeholder="/ai"
                           maxlength="50"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white font-mono">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        แอดมินพิมพ์คำนี้ใน LINE/Facebook เพื่อให้ AI กลับมาทำงานทันที
                    </p>
                </div>
            </div>

            {{-- คำลูกค้าที่ trigger เทคโอเวอร์ --}}
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-900 dark:text-white mb-1">
                    🙋 คำที่ลูกค้าพิมพ์แล้วให้เทคโอเวอร์อัตโนมัติ
                </label>
                <textarea name="customer_handoff_keywords"
                          rows="4"
                          placeholder="คุยกับคน&#10;คุยกับแม่หมอ&#10;ขอแอดมิน&#10;ติดต่อแอดมิน"
                          class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white font-mono text-sm">{{ old('customer_handoff_keywords', is_array($settings->customer_handoff_keywords) ? implode("\n", $settings->customer_handoff_keywords) : '') }}</textarea>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    ใส่ 1 คำต่อบรรทัด (หรือคั่นด้วย comma) — ถ้าลูกค้าพิมพ์คำใดคำหนึ่ง จะเทคโอเวอร์และแจ้งเตือนแอดมิน
                    <span class="text-gray-400">(เว้นว่าง = ใช้ค่า default)</span>
                </p>
            </div>

            {{-- แจ้งลูกค้าเมื่อเทคโอเวอร์ --}}
            <div class="mt-4 p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                <label class="inline-flex items-center cursor-pointer">
                    <input type="checkbox"
                           name="takeover_notify_customer"
                           value="1"
                           {{ ($settings->takeover_notify_customer ?? true) ? 'checked' : '' }}
                           class="form-checkbox h-5 w-5 text-purple-600 rounded">
                    <span class="ml-2 text-gray-900 dark:text-white font-medium">แจ้งลูกค้าเมื่อแม่หมอเข้ามาคุย</span>
                </label>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                            ข้อความเมื่อแม่หมอเข้ามา
                        </label>
                        <textarea name="takeover_customer_message"
                                  rows="2"
                                  maxlength="500"
                                  placeholder="🙏 สวัสดีค่ะ แม่หมอเข้ามาดูแลเอง ขอสักครู่นะคะ"
                                  class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">{{ old('takeover_customer_message', $settings->takeover_customer_message) }}</textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                            ข้อความเมื่อ AI กลับมาทำงาน
                        </label>
                        <textarea name="takeover_resume_message"
                                  rows="2"
                                  maxlength="500"
                                  placeholder="✨ ระบบอัจฉริยะกลับมาดูแลต่อแล้ว"
                                  class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">{{ old('takeover_resume_message', $settings->takeover_resume_message) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- 🛡️ (2026-05-10) Link Moderation — ซ่อน/ลบคอมเม้นต์ที่มีลิงค์สแปม --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6" data-fortune-tab="engagement"
             x-data="{ enableLinkMod: {{ old('auto_hide_link_comments', $settings->auto_hide_link_comments ?? false) ? 'true' : 'false' }} }">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        🛡️ ระบบกรองคอมเม้นต์สแปม (Auto-Hide Links)
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        ซ่อน/ลบคอมเม้นต์ที่มีลิงค์ภายนอกอัตโนมัติ — ใช้ Page Token ที่มี <code class="text-xs bg-gray-100 dark:bg-gray-700 px-1 rounded">pages_manage_engagement</code>
                    </p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="hidden" name="auto_hide_link_comments" value="0">
                    <input type="checkbox" name="auto_hide_link_comments" value="1"
                           x-model="enableLinkMod"
                           {{ old('auto_hide_link_comments', $settings->auto_hide_link_comments ?? false) ? 'checked' : '' }}
                           class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-red-300 dark:peer-focus:ring-red-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-red-600"></div>
                </label>
            </div>

            <div x-show="enableLinkMod" x-cloak class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            ⚙️ การกระทำ
                        </label>
                        <select name="link_comment_action"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500">
                            <option value="hide" {{ old('link_comment_action', $settings->link_comment_action ?? 'hide') === 'hide' ? 'selected' : '' }}>
                                🙈 ซ่อน (แนะนำ — ผู้คอมยังเห็นเอง คนอื่นไม่เห็น)
                            </option>
                            <option value="delete" {{ old('link_comment_action', $settings->link_comment_action ?? 'hide') === 'delete' ? 'selected' : '' }}>
                                🗑️ ลบถาวร (ผู้คอมเห็นว่าโดนลบ — อาจกลับมาแก้แค้น)
                            </option>
                        </select>
                    </div>

                    <div class="flex items-end">
                        <label class="flex items-center pb-2">
                            <input type="hidden" name="link_moderation_log_only" value="0">
                            <input type="checkbox" name="link_moderation_log_only" value="1"
                                   {{ old('link_moderation_log_only', $settings->link_moderation_log_only ?? false) ? 'checked' : '' }}
                                   class="w-4 h-4 text-yellow-600 bg-gray-100 border-gray-300 rounded focus:ring-yellow-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                🧪 Dry-run (log อย่างเดียว ไม่ทำจริง)
                            </span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ✅ Whitelist Domains (ลิงค์ที่อนุญาต — บรรทัดละ 1 โดเมน)
                    </label>
                    <textarea name="link_whitelist_domains_text"
                              rows="4"
                              placeholder="thaiprompt.online&#10;main.thaiprompt.online&#10;m.me&#10;lin.ee"
                              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm font-mono focus:ring-2 focus:ring-red-500">{{ old('link_whitelist_domains_text', is_array($settings->link_whitelist_domains ?? null) ? implode("\n", $settings->link_whitelist_domains) : '') }}</textarea>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        💡 โดเมนเริ่มต้น (thaiprompt.online, m.me, lin.ee, line.me, facebook.com) จะถูกเพิ่มอัตโนมัติเสมอ — ไม่ต้องใส่ซ้ำ
                    </p>
                </div>

                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-3">
                    <p class="text-xs text-yellow-800 dark:text-yellow-300">
                        ⚠️ <strong>คำแนะนำ:</strong> เปิด Dry-run ก่อนสัก 1-2 วัน → ดู log ว่าจับถูกไหม → ค่อยปิด Dry-run ให้ทำงานจริง
                    </p>
                </div>
            </div>
        </div>

        {{-- 💳 (2026-05-22) Payment Mode Toggle — เลือก 3 โหมด (SMS-only / Stripe-only / Both) --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6" data-fortune-tab="platform"
             x-data="{
                enableSms: {{ old('enable_sms_payment', $settings->enable_sms_payment ?? true) ? 'true' : 'false' }},
                enableStripe: {{ old('enable_stripe_payment', $settings->enable_stripe_payment ?? false) ? 'true' : 'false' }},
                get mode() {
                    if (this.enableSms && this.enableStripe) return 'both';
                    if (this.enableStripe) return 'stripe_only';
                    if (this.enableSms) return 'sms_only';
                    return 'none';
                },
                get modeLabel() {
                    return {
                        both: '🎯 Both — ลูกค้าเลือก 2 ปุ่ม (QR ไทย / บัตรเครดิต +15)',
                        stripe_only: '💳 Stripe-only — บัตรเครดิตเท่านั้น (ข้ามเมนู ไปหน้าจ่ายเลย)',
                        sms_only: '💚 SMS-only — QR ไทยตรงๆ (default, backward compat)',
                        none: '⚠️ ทั้งคู่ปิด — fallback กลับ SMS อัตโนมัติ (แก้ในแอดมิน)',
                    }[this.mode];
                },
                get modeColor() {
                    return {
                        both: 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-700 text-blue-800 dark:text-blue-300',
                        stripe_only: 'bg-purple-50 dark:bg-purple-900/20 border-purple-200 dark:border-purple-700 text-purple-800 dark:text-purple-300',
                        sms_only: 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-700 text-green-800 dark:text-green-300',
                        none: 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-700 text-red-800 dark:text-red-300',
                    }[this.mode];
                }
             }">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                💰 วิธีรับชำระเงิน
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                {{-- SMS / QR Thai toggle --}}
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            💚 QR ไทย + SMS Checker
                        </h4>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="enable_sms_payment" value="1"
                                   x-model="enableSms"
                                   {{ old('enable_sms_payment', $settings->enable_sms_payment ?? true) ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-green-600"></div>
                        </label>
                    </div>
                    <p class="text-xs text-gray-600 dark:text-gray-400">
                        PromptPay + ทุกธนาคารไทย — ตรวจสลิปอัตโนมัติผ่าน SmsChecker app
                    </p>
                </div>

                {{-- Stripe toggle --}}
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            💳 บัตรเครดิต (Stripe)
                        </h4>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="enable_stripe_payment" value="1"
                                   x-model="enableStripe"
                                   {{ old('enable_stripe_payment', $settings->enable_stripe_payment ?? false) ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 dark:peer-focus:ring-purple-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-purple-600"></div>
                        </label>
                    </div>
                    <p class="text-xs text-gray-600 dark:text-gray-400">
                        Visa/Mastercard/AmEx — บัตรเครดิตทุกใบ +15฿ ค่าบริการ (รับทุกประเทศ)
                    </p>
                </div>
            </div>

            {{-- Mode preview --}}
            <div :class="modeColor" class="border rounded-lg p-3 text-sm font-medium" x-text="modeLabel"></div>
        </div>

        {{-- 💳 (2026-05-09) Stripe Payment Settings --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6" data-fortune-tab="platform"
             x-data="{ enableStripe: {{ old('enable_stripe_payment', $settings->enable_stripe_payment ?? false) ? 'true' : 'false' }} }">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        🔑 Stripe Configuration
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        ตั้งค่า API keys + webhook สำหรับ Stripe Checkout (รับบัตรเครดิตทุกประเทศ +15฿ ค่าบริการ)
                    </p>
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    เปิด/ปิดได้ที่ section "วิธีรับชำระเงิน" ด้านบน
                </div>
            </div>

            <div x-show="enableStripe" x-cloak class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            💰 ค่าบริการบัตรเครดิต (บาท)
                        </label>
                        <input type="number" name="stripe_service_fee" min="0" step="1"
                               value="{{ (int) old('stripe_service_fee', $settings->stripe_service_fee ?? 15) }}"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            บวกเพิ่มทุกครั้งที่ลูกค้าเลือกชำระผ่านบัตร — Stripe หัก ~3.65%+11฿/transaction.<br>
                            รับบัตรทุกประเทศ (ไทย / ลาว / USA / ทั่วโลก).
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            ⏰ อายุลิงก์ (นาที)
                        </label>
                        <input type="number" name="stripe_session_expiry_minutes" min="30" max="1440"
                               value="{{ old('stripe_session_expiry_minutes', $settings->stripe_session_expiry_minutes ?? 30) }}"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Stripe ขั้นต่ำ 30 นาที</p>
                    </div>

                    <div class="flex items-end">
                        <label class="flex items-center pb-2">
                            <input type="checkbox" name="stripe_test_mode" value="1"
                                   {{ old('stripe_test_mode', $settings->stripe_test_mode ?? true) ? 'checked' : '' }}
                                   class="w-4 h-4 text-yellow-600 bg-gray-100 border-gray-300 rounded focus:ring-yellow-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                🧪 Test Mode
                            </span>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            🔑 Secret Key <span class="text-red-500">*</span>
                        </label>
                        <input type="password" name="stripe_secret_key"
                               value="{{ old('stripe_secret_key', $settings->stripe_secret_key ?? '') }}"
                               placeholder="sk_live_xxx หรือ sk_test_xxx"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 font-mono text-xs">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">เก็บ encrypted ใน DB</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            🌐 Publishable Key
                        </label>
                        <input type="text" name="stripe_publishable_key"
                               value="{{ old('stripe_publishable_key', $settings->stripe_publishable_key ?? '') }}"
                               placeholder="pk_live_xxx หรือ pk_test_xxx"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 font-mono text-xs">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        🔒 Webhook Secret <span class="text-red-500">*</span>
                    </label>
                    <input type="password" name="stripe_webhook_secret"
                           value="{{ old('stripe_webhook_secret', $settings->stripe_webhook_secret ?? '') }}"
                           placeholder="whsec_xxx"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 font-mono text-xs">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        URL webhook: <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">{{ url('/webhook/fortune-stripe') }}</code>
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            🏢 Account ID
                        </label>
                        <input type="text" name="stripe_account_id"
                               value="{{ old('stripe_account_id', $settings->stripe_account_id ?? '') }}"
                               placeholder="acct_1K7aDRD3aYAdvmlU"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 font-mono text-xs">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">สำหรับสร้างลิงก์ admin</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            📦 Deep Product ID (39฿)
                        </label>
                        <input type="text" name="stripe_product_deep_id"
                               value="{{ old('stripe_product_deep_id', $settings->stripe_product_deep_id ?? 'prod_UU1wXx9DI4s2gq') }}"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 font-mono text-xs">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            🔮 Celtic Product ID (99฿)
                        </label>
                        <input type="text" name="stripe_product_celtic_id"
                               value="{{ old('stripe_product_celtic_id', $settings->stripe_product_celtic_id ?? 'prod_UU1zVarkNVzkpp') }}"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 font-mono text-xs">
                    </div>
                </div>

                <div class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg border border-purple-200 dark:border-purple-800">
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">📋 Setup Checklist</h4>
                    <ol class="text-xs text-gray-600 dark:text-gray-400 space-y-1 list-decimal list-inside">
                        <li>ไป <a href="https://dashboard.stripe.com/apikeys" target="_blank" class="text-purple-600 hover:underline">Stripe Dashboard → API keys</a> → คัดลอก Secret Key + Publishable Key</li>
                        <li>ไป Webhooks → Add endpoint → URL: <code class="bg-white dark:bg-gray-700 px-1 rounded text-xs">{{ url('/webhook/fortune-stripe') }}</code></li>
                        <li>เลือก events: <code class="text-xs">checkout.session.completed</code>, <code class="text-xs">checkout.session.expired</code>, <code class="text-xs">charge.refunded</code></li>
                        <li>คัดลอก Signing Secret (whsec_xxx) มาใส่ Webhook Secret</li>
                        <li>เปิด toggle ด้านบน + บันทึก → ระบบจะถามวิธีชำระลูกค้าทุกบิลใหม่</li>
                    </ol>
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
// 🌟 (2026-05-08) Pro Feature Tester (Bill Psychology + Celtic Premium)
//   ทุก Phase 2 features ใช้ key ร่วมกับ Sensitive AI Mode (purpose='sensitive')
//   component นี้ shared — รับ scenario param เพื่อ test endpoint ที่ตรง
function proFeatureTester(scenario, hasKey, options) {
    const csrf = '{{ csrf_token() }}';
    const testUrl = '{{ route("admin.fortune.playground.test-sensitive") }}';

    const opts = options || {};
    const sampleMessages = {
        bill: 'ราคา 39 บาท แพงเกินไปครับ ลดได้ไหม กลัวโดนหลอกด้วย',
        celtic: 'ขอบคุณค่ะแม่หมอ คำตอบช่วยได้มาก แต่อยากถามต่ออีกนิด เรื่องไพ่ใบที่ 7 แม่หมอเห็นอะไรเพิ่ม',
        sensitive: 'ฉันเครียดมากเลยค่ะ ผัวมีกิ๊ก จะหย่าดีไหม',
    };

    const initialEnabled = opts.initialEnabled === undefined ? true : !!opts.initialEnabled;

    return {
        scenario: scenario,
        hasKey: !!hasKey,
        featureEnabled: initialEnabled,
        testing: false,
        testResult: null,
        showPromptOverride: opts.showPromptOverride || false,

        async testFeature() {
            const defaultMsg = sampleMessages[this.scenario] || sampleMessages.sensitive;
            const message = prompt(`ใส่ข้อความทดสอบ scenario "${this.scenario}":`, defaultMsg);
            if (!message || message.trim().length < 5) {
                this.testResult = { success: false, error: 'ข้อความต้องมีอย่างน้อย 5 ตัวอักษร' };
                return;
            }

            this.testing = true;
            this.testResult = null;
            try {
                const res = await fetch(testUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({
                        message,
                        scenario: this.scenario,
                    }),
                });
                this.testResult = await res.json();
            } catch (e) {
                this.testResult = { success: false, error: e.message };
            } finally {
                this.testing = false;
            }
        },
    };
}

// 🌟 (2026-05-08) Sensitive AI Panel — dropdown + test button
function sensitiveAiPanel(sensitiveKeys, hasSensitiveKey, lockedKeyId) {
    const csrf = '{{ csrf_token() }}';
    const testUrl = '{{ route("admin.fortune.playground.test-sensitive") }}';

    return {
        sensitiveMode: '{{ old('sensitive_ai_mode', $settings->sensitive_ai_mode ?? 'paid_only') }}',
        detectionMode: '{{ old('sensitive_detection_mode', $settings->sensitive_detection_mode ?? 'hybrid') }}',
        offtopicAction: '{{ old('sensitive_offtopic_action', $settings->sensitive_offtopic_action ?? 'revert') }}',
        sensitiveKeys: sensitiveKeys || [],
        hasSensitiveKey: hasSensitiveKey || false,
        lockedKeyId: lockedKeyId ? String(lockedKeyId) : '',
        testing: false,
        testResult: null,

        selectedKey() {
            if (!this.lockedKeyId) return null;
            return this.sensitiveKeys.find(k => String(k.id) === String(this.lockedKeyId)) || null;
        },

        async testKey() {
            const message = prompt(
                'ใส่ข้อความทดสอบ (เช่น "ฉันเหนื่อยจะตายแล้ว ผัวมีกิ๊ก")',
                'ฉันเครียดมากเลยค่ะ ผัวมีกิ๊ก จะหย่าดีไหม'
            );
            if (!message || message.trim().length < 5) {
                this.testResult = { success: false, error: 'ข้อความต้องมีอย่างน้อย 5 ตัวอักษร' };
                return;
            }

            this.testing = true;
            this.testResult = null;
            try {
                const payload = { message };
                if (this.lockedKeyId) {
                    payload.pool_key_id = parseInt(this.lockedKeyId, 10);
                }
                const res = await fetch(testUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify(payload),
                });
                this.testResult = await res.json();
            } catch (e) {
                this.testResult = { success: false, error: e.message };
            } finally {
                this.testing = false;
            }
        },
    };
}

// 🎼 (2026-05-08) Voice Library Alpine component
function voiceLibrary() {
    const baseUrl = '{{ route("admin.fortune.voice-presets.index") }}';
    const csrf = '{{ csrf_token() }}';

    async function jsonFetch(url, opts = {}) {
        const res = await fetch(url, {
            ...opts,
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                ...(opts.headers || {}),
            },
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            throw new Error(data.error || data.message || ('HTTP ' + res.status));
        }
        return data;
    }

    return {
        presets: [],
        loading: false,
        busy: false,
        showAdd: false,
        showImport: false,
        message: '',
        messageOk: true,
        newPreset: { name: '', provider: 'minimax', voice_id: '', model: '' },
        importProvider: 'minimax',
        importRaw: '',
        importReplace: false,

        async init() {
            await this.refresh();
        },

        flash(msg, ok = true) {
            this.message = msg;
            this.messageOk = ok;
            setTimeout(() => { this.message = ''; }, 5000);
        },

        async refresh() {
            this.loading = true;
            try {
                const data = await jsonFetch(baseUrl);
                this.presets = data.data || [];
            } catch (e) {
                this.flash('โหลดรายการเสียงล้มเหลว: ' + e.message, false);
            } finally {
                this.loading = false;
            }
        },

        async addPreset() {
            if (!this.newPreset.name || !this.newPreset.voice_id) {
                this.flash('กรอกชื่อ + voice_id ให้ครบ', false);
                return;
            }
            this.busy = true;
            try {
                await jsonFetch(baseUrl, {
                    method: 'POST',
                    body: JSON.stringify(this.newPreset),
                });
                this.flash('เพิ่มสำเร็จ');
                this.newPreset = { name: '', provider: 'minimax', voice_id: '', model: '' };
                this.showAdd = false;
                await this.refresh();
            } catch (e) {
                this.flash('เพิ่มไม่สำเร็จ: ' + e.message, false);
            } finally {
                this.busy = false;
            }
        },

        async deletePreset(id, name) {
            if (!confirm('ลบ "' + name + '" ใช่ไหม?')) return;
            this.busy = true;
            try {
                await jsonFetch(baseUrl + '/' + id, { method: 'DELETE' });
                this.flash('ลบแล้ว');
                await this.refresh();
            } catch (e) {
                this.flash('ลบไม่สำเร็จ: ' + e.message, false);
            } finally {
                this.busy = false;
            }
        },

        async applyPreset(id) {
            this.busy = true;
            try {
                const r = await jsonFetch(baseUrl + '/' + id + '/apply', { method: 'POST' });
                this.flash(r.message || 'ตั้งเป็นเสียงปัจจุบันแล้ว');
                await this.refresh();
            } catch (e) {
                this.flash('Apply ไม่สำเร็จ: ' + e.message, false);
            } finally {
                this.busy = false;
            }
        },

        async testPreset(id) {
            this.busy = true;
            this.flash('🎙️ กำลังสร้าง sample (อาจใช้เวลา 10-30 วิ)...');
            try {
                const r = await jsonFetch(baseUrl + '/' + id + '/test', { method: 'POST' });
                this.flash('✅ สร้าง sample สำเร็จ — กดเล่นได้เลย');
                await this.refresh();
            } catch (e) {
                this.flash('Test ไม่สำเร็จ: ' + e.message, false);
            } finally {
                this.busy = false;
            }
        },

        async importBulk() {
            if (!this.importRaw.trim()) return;
            if (this.importReplace && !confirm('จะลบเสียง provider ' + this.importProvider + ' เดิมทั้งหมดก่อน import — แน่ใจ?')) {
                return;
            }
            this.busy = true;
            try {
                const r = await jsonFetch(baseUrl + '/import', {
                    method: 'POST',
                    body: JSON.stringify({
                        provider: this.importProvider,
                        raw: this.importRaw,
                        replace: this.importReplace,
                    }),
                });
                this.flash(r.message || ('สำเร็จ ' + r.created + ' รายการ'));
                this.importRaw = '';
                this.showImport = false;
                await this.refresh();
            } catch (e) {
                this.flash('Import ไม่สำเร็จ: ' + e.message, false);
            } finally {
                this.busy = false;
            }
        },

        async seedThai() {
            this.busy = true;
            try {
                const r = await jsonFetch(baseUrl + '/seed-thai', { method: 'POST' });
                this.flash(r.message || 'Seed สำเร็จ');
                await this.refresh();
            } catch (e) {
                this.flash('Seed ไม่สำเร็จ: ' + e.message, false);
            } finally {
                this.busy = false;
            }
        },
    };
}

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
        // 🆕 (2026-05-13) Tab state — ใช้ filter sections ตาม group
        //   sections ที่มี data-fortune-tab attribute → show/hide ตาม activeTab
        //   default: 'ai' (กลุ่มหลัก — AI Provider/Chat/Sensitive/Bill/Celtic)
        activeTab: localStorage.getItem('fortune_settings_tab') || 'ai',
        commentEngagementEnabled: {{ $settings->comment_engagement_enabled ? 'true' : 'false' }},
        commentEngagementMode: '{{ old('comment_engagement_mode', $settings->comment_engagement_mode ?? 'ai') }}',
        // 🚫 (2026-05-24) Sub-toggle: ตอบคอมเม้นต์สาธารณะ (default false — รอ App Review)
        enablePublicCommentReply: {{ old('enable_public_comment_reply', $settings->enable_public_comment_reply ?? false) ? 'true' : 'false' }},
        switchTab(tab) {
            this.activeTab = tab;
            localStorage.setItem('fortune_settings_tab', tab);
            this.applyTabFilter();
            // scroll up to show tab content from top
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
        applyTabFilter() {
            const active = this.activeTab;
            document.querySelectorAll('[data-fortune-tab]').forEach(el => {
                const tabs = (el.getAttribute('data-fortune-tab') || '').split(',').map(s => s.trim());
                el.style.display = tabs.includes(active) ? '' : 'none';
            });
        },
        initTabs() {
            // apply filter ทันทีหลัง Alpine init
            this.$nextTick(() => this.applyTabFilter());
        },
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

// 🌥️ (2026-05-18) Voice Storage Alpine component — multi-cloud config + test + migrate
function voiceStorage(initial) {
    const csrf = '{{ csrf_token() }}';
    const urls = {
        save: '{{ route("admin.fortune.voice-storage.save") }}',
        test: '{{ route("admin.fortune.voice-storage.test") }}',
        fixSymlink: '{{ route("admin.fortune.voice-storage.fix-symlink") }}',
        migrate: '{{ route("admin.fortune.voice-storage.migrate") }}',
    };

    return {
        driver: initial.driver || 'local',
        r2: initial.r2 || { account_id: '', access_key_id: '', secret_access_key: '', bucket: '', public_url: '' },
        s3: initial.s3 || { access_key_id: '', secret_access_key: '', region: 'ap-southeast-1', bucket: '', endpoint: '', public_url: '' },
        gcs: initial.gcs || { credentials_path: 'storage/app/firebase-credentials.json', bucket: '', public_url: '' },
        firebase: initial.firebase || { credentials_path: 'storage/app/firebase-credentials.json', bucket: '' },
        busy: false,
        message: '',
        messageOk: false,
        showMigrate: false,
        migrate: { from: 'local', to: 'r2', dryRun: true },
        migrateOutput: '',

        currentConfig() {
            return { [this.driver]: this[this.driver] || {} };
        },

        async testConnection() {
            this.busy = true;
            this.message = '';
            try {
                const res = await fetch(urls.test, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        override_driver: this.driver,
                        override_config: this[this.driver] || {},
                    }),
                });
                const data = await res.json();
                this.messageOk = !!data.success;
                this.message = data.message || (data.success ? '✅ OK' : '❌ Failed');
            } catch (e) {
                this.messageOk = false;
                this.message = '❌ Fetch error: ' + e.message;
            } finally {
                this.busy = false;
            }
        },

        async saveDriver() {
            if (!confirm('บันทึก driver: ' + this.driver + ' + config?\n\nหลังบันทึก voice ใหม่จะใช้ driver นี้ทันที')) return;
            this.busy = true;
            this.message = '';
            try {
                const res = await fetch(urls.save, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        driver: this.driver,
                        config: {
                            r2: this.r2,
                            s3: this.s3,
                            gcs: this.gcs,
                            firebase: this.firebase,
                        },
                    }),
                });
                const data = await res.json();
                this.messageOk = !!data.success;
                this.message = data.message || (data.success ? '✅ บันทึกแล้ว' : '❌ Failed');
            } catch (e) {
                this.messageOk = false;
                this.message = '❌ Fetch error: ' + e.message;
            } finally {
                this.busy = false;
            }
        },

        async fixSymlink() {
            this.busy = true;
            try {
                const res = await fetch(urls.fixSymlink, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                });
                const data = await res.json();
                this.messageOk = !!data.success;
                this.message = data.message;
            } catch (e) {
                this.messageOk = false;
                this.message = '❌ ' + e.message;
            } finally {
                this.busy = false;
            }
        },

        async runMigrate() {
            if (this.migrate.from === this.migrate.to) {
                alert('from กับ to ต้องไม่เหมือนกัน');
                return;
            }
            const confirmMsg = this.migrate.dryRun
                ? 'ทดลองดูว่าจะย้ายกี่ไฟล์ (dry run — ไม่ย้ายจริง)?'
                : '⚠️ ย้ายไฟล์ทั้งหมดจาก ' + this.migrate.from + ' → ' + this.migrate.to + ' จริงๆ?\n\nไฟล์ source จะถูกลบหลังย้าย';
            if (!confirm(confirmMsg)) return;

            this.busy = true;
            this.migrateOutput = '⏳ กำลังย้าย... (อาจใช้เวลาหลายนาที สำหรับไฟล์เยอะ)';
            try {
                const res = await fetch(urls.migrate, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        from: this.migrate.from,
                        to: this.migrate.to,
                        dry_run: this.migrate.dryRun,
                    }),
                });
                const data = await res.json();
                this.messageOk = !!data.success;
                this.message = data.message;
                this.migrateOutput = data.output || JSON.stringify(data, null, 2);
            } catch (e) {
                this.messageOk = false;
                this.message = '❌ ' + e.message;
                this.migrateOutput = e.message;
            } finally {
                this.busy = false;
            }
        },
    };
}
</script>
@endpush
@endsection
