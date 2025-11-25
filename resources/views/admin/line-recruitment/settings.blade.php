{{--
    หน้าตั้งค่าระบบรับสมัคร LINE AI
    จัดการ AI Settings และ Provider
--}}
@extends('layouts.admin')

@section('title', 'ตั้งค่าระบบรับสมัคร - LINE Recruitment')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900" x-data="settingsManager()">
    {{-- Header --}}
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-2">
                        <a href="{{ route('admin.line-recruitment.index') }}" class="hover:text-green-600">LINE Recruitment</a>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        <span>ตั้งค่า</span>
                    </nav>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">ตั้งค่าระบบรับสมัคร AI</h1>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if(session('success'))
            <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl text-green-700 dark:text-green-400">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl text-red-700 dark:text-red-400">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- AI Settings List --}}
        <div class="space-y-6">
            @forelse($aiSettings as $setting)
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                    {{-- Setting Header --}}
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-green-400 to-emerald-500 flex items-center justify-center text-white font-bold">
                                {{ strtoupper(substr($setting->provider, 0, 2)) }}
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $setting->name }}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $providers[$setting->provider] ?? $setting->provider }} - {{ $setting->model }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            @if($setting->is_active)
                                <span class="flex items-center px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded-full text-sm">
                                    <span class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></span>
                                    Active
                                </span>
                            @endif
                            @if($setting->use_for_recruitment)
                                <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400 rounded-full text-sm">
                                    Recruitment
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Setting Form --}}
                    <form action="{{ route('admin.line-recruitment.settings.update', $setting->id) }}" method="POST" class="p-6 space-y-6">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {{-- Basic Info --}}
                            <div class="space-y-4">
                                <h4 class="font-medium text-gray-900 dark:text-white">ข้อมูลพื้นฐาน</h4>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อ</label>
                                    <input type="text" name="name" value="{{ $setting->name }}" required
                                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">AI Provider</label>
                                    <select name="provider" required
                                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500">
                                        @foreach($providers as $key => $label)
                                            <option value="{{ $key }}" {{ $setting->provider === $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">API Key</label>
                                    <input type="password" name="api_key" placeholder="ไม่เปลี่ยนให้เว้นว่าง"
                                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ปัจจุบัน: {{ $setting->getMaskedApiKey() }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Model</label>
                                    <input type="text" name="model" value="{{ $setting->model }}" required
                                           placeholder="gpt-4, gemini-pro, claude-3-sonnet"
                                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500">
                                </div>
                            </div>

                            {{-- Parameters --}}
                            <div class="space-y-4">
                                <h4 class="font-medium text-gray-900 dark:text-white">พารามิเตอร์</h4>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Temperature</label>
                                        <input type="number" name="temperature" value="{{ $setting->temperature }}" step="0.1" min="0" max="2" required
                                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Max Tokens</label>
                                        <input type="number" name="max_tokens" value="{{ $setting->max_tokens }}" min="1" required
                                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สไตล์การตอบ</label>
                                    <select name="response_style" required
                                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500">
                                        @foreach($responseStyles as $key => $label)
                                            <option value="{{ $key }}" {{ $setting->response_style === $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Max Conversation Turns</label>
                                        <input type="number" name="max_conversation_turns" value="{{ $setting->max_conversation_turns ?? 50 }}" min="1" max="100"
                                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Memory Limit</label>
                                        <input type="number" name="conversation_memory_limit" value="{{ $setting->conversation_memory_limit }}" min="1"
                                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- System Prompts --}}
                        <div class="space-y-4">
                            <h4 class="font-medium text-gray-900 dark:text-white">System Prompts</h4>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">System Prompt (ทั่วไป)</label>
                                <textarea name="system_prompt" rows="3"
                                          class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500">{{ $setting->system_prompt }}</textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Recruitment System Prompt (สำหรับรับสมัคร)</label>
                                <textarea name="recruitment_system_prompt" rows="4"
                                          placeholder="คุณเป็นผู้ช่วยรับสมัครสมาชิกที่เป็นมิตร..."
                                          class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500">{{ $setting->recruitment_system_prompt }}</textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ข้อความต้อนรับ</label>
                                <textarea name="greeting_message" rows="2"
                                          placeholder="สวัสดีค่ะ! ยินดีต้อนรับ..."
                                          class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500">{{ $setting->greeting_message }}</textarea>
                            </div>
                        </div>

                        {{-- Messages --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ข้อความเมื่อหมดรอบ</label>
                                <textarea name="max_turns_message" rows="2"
                                          placeholder="ขอบคุณที่สนใจค่ะ..."
                                          class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500">{{ $setting->max_turns_message }}</textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ข้อความเมื่อคุยเรื่องที่ห้าม</label>
                                <textarea name="off_topic_message" rows="2"
                                          placeholder="ขออภัยค่ะ หัวข้อนี้อยู่นอกเหนือ..."
                                          class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500">{{ $setting->off_topic_message }}</textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ข้อความ Fallback (เมื่อ AI Error)</label>
                                <textarea name="fallback_message" rows="2"
                                          class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500">{{ $setting->fallback_message }}</textarea>
                            </div>
                        </div>

                        {{-- Toggles --}}
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <label class="flex items-center gap-2 cursor-pointer p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <input type="checkbox" name="is_active" value="1" {{ $setting->is_active ? 'checked' : '' }}
                                       class="w-4 h-4 text-green-600 rounded focus:ring-green-500">
                                <span class="text-sm text-gray-700 dark:text-gray-300">Active</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <input type="checkbox" name="use_for_recruitment" value="1" {{ $setting->use_for_recruitment ? 'checked' : '' }}
                                       class="w-4 h-4 text-green-600 rounded focus:ring-green-500">
                                <span class="text-sm text-gray-700 dark:text-gray-300">ใช้สำหรับรับสมัคร</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <input type="checkbox" name="enable_topic_filtering" value="1" {{ $setting->enable_topic_filtering ? 'checked' : '' }}
                                       class="w-4 h-4 text-green-600 rounded focus:ring-green-500">
                                <span class="text-sm text-gray-700 dark:text-gray-300">เปิด Topic Filter</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <input type="checkbox" name="enable_conversation_history" value="1" {{ $setting->enable_conversation_history ? 'checked' : '' }}
                                       class="w-4 h-4 text-green-600 rounded focus:ring-green-500">
                                <span class="text-sm text-gray-700 dark:text-gray-300">จำประวัติ</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <input type="checkbox" name="enable_fallback" value="1" {{ $setting->enable_fallback ? 'checked' : '' }}
                                       class="w-4 h-4 text-green-600 rounded focus:ring-green-500">
                                <span class="text-sm text-gray-700 dark:text-gray-300">เปิด Fallback</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <input type="checkbox" name="collect_user_info" value="1" {{ $setting->collect_user_info ? 'checked' : '' }}
                                       class="w-4 h-4 text-green-600 rounded focus:ring-green-500">
                                <span class="text-sm text-gray-700 dark:text-gray-300">เก็บข้อมูลผู้ใช้</span>
                            </label>
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex gap-3">
                                <a href="{{ route('admin.line-recruitment.topic-boundaries', $setting->id) }}"
                                   class="px-4 py-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition">
                                    ขอบเขตหัวข้อ
                                </a>
                                <a href="{{ route('admin.line-recruitment.knowledge-base', $setting->id) }}"
                                   class="px-4 py-2 text-purple-600 hover:bg-purple-50 dark:hover:bg-purple-900/20 rounded-lg transition">
                                    แหล่งข้อมูล
                                </a>
                                <button type="button" @click="testAi({{ $setting->id }})"
                                        class="px-4 py-2 text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition">
                                    ทดสอบ AI
                                </button>
                            </div>
                            <button type="submit"
                                    class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
                                บันทึกการตั้งค่า
                            </button>
                        </div>
                    </form>
                </div>
            @empty
                <div class="text-center py-12 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700">
                    <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400 mb-4">ยังไม่มี AI Settings</p>
                    <a href="{{ route('admin.line-bot.ai.create') }}" class="text-green-600 hover:underline">
                        สร้าง AI Setting ใหม่
                    </a>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Test Modal --}}
    <div x-show="showTestModal" x-transition
         class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center">
            <div class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-90 transition-opacity" @click="showTestModal = false"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-2xl max-w-lg w-full p-6 shadow-xl">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ทดสอบ AI</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ข้อความทดสอบ</label>
                        <input type="text" x-model="testMessage"
                               placeholder="พิมพ์ข้อความทดสอบ..."
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500">
                    </div>
                    <button @click="sendTest()"
                            :disabled="testLoading"
                            class="w-full px-4 py-2 bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white rounded-lg transition">
                        <span x-show="!testLoading">ส่งทดสอบ</span>
                        <span x-show="testLoading">กำลังทดสอบ...</span>
                    </button>
                    <div x-show="testResponse" class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">คำตอบจาก AI:</p>
                        <p class="text-gray-900 dark:text-white" x-text="testResponse"></p>
                        <div x-show="testMeta" class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            <span x-text="'Provider: ' + testMeta.provider"></span> |
                            <span x-text="'Tokens: ' + testMeta.tokens_used"></span> |
                            <span x-text="'Time: ' + testMeta.response_time + 's'"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function settingsManager() {
        return {
            showTestModal: false,
            testMessage: 'สวัสดีค่ะ อยากสมัครสมาชิก',
            testLoading: false,
            testResponse: null,
            testMeta: null,
            testSettingId: null,

            testAi(settingId) {
                this.testSettingId = settingId;
                this.testResponse = null;
                this.testMeta = null;
                this.showTestModal = true;
            },

            async sendTest() {
                if (!this.testMessage) return;

                this.testLoading = true;
                this.testResponse = null;
                this.testMeta = null;

                try {
                    const response = await fetch(`/admin/line-recruitment/test-ai/${this.testSettingId}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ message: this.testMessage })
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.testResponse = data.message;
                        this.testMeta = {
                            provider: data.provider,
                            tokens_used: data.tokens_used || 'N/A',
                            response_time: data.response_time || 'N/A'
                        };
                    } else {
                        this.testResponse = 'Error: ' + data.error;
                    }
                } catch (error) {
                    this.testResponse = 'Error: ' + error.message;
                } finally {
                    this.testLoading = false;
                }
            }
        }
    }
</script>
@endpush
@endsection
