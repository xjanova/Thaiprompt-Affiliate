@extends('layouts.admin-v3')

@section('title', 'แก้ไขการตั้งค่า LINE Bot AI')

@section('content')
<div class="min-h-screen bg-gray-100/50 dark:bg-slate-900 py-6 px-4 sm:px-6 lg:px-8" x-data="{
    provider: '{{ old('provider', $aiSetting->provider) }}',
    model: '{{ old('model', $aiSetting->model) }}',
    isActive: {{ old('is_active', $aiSetting->is_active) ? 'true' : 'false' }},
    enableFallback: {{ old('enable_fallback', $aiSetting->enable_fallback) ? 'true' : 'false' }},
    enableHistory: {{ old('enable_conversation_history', $aiSetting->enable_conversation_history) ? 'true' : 'false' }},
    testMessage: '',
    testResponse: '',
    testLoading: false,
    previewMessages: [
        { type: 'user', text: 'สวัสดีครับ', time: '14:30' },
        { type: 'bot', text: 'สวัสดีค่ะ! ยินดีต้อนรับสู่ระบบ Affiliate 😊 มีอะไรให้ช่วยไหมคะ?', time: '14:30' }
    ],
    models: {
        openai: ['gpt-3.5-turbo', 'gpt-4', 'gpt-4-turbo', 'gpt-4o', 'gpt-4o-mini'],
        deepseek: ['deepseek-chat', 'deepseek-coder'],
        anthropic: ['claude-3-5-sonnet-20241022', 'claude-3-5-haiku-20241022', 'claude-3-opus-20240229'],
        gemini: ['gemini-1.5-pro', 'gemini-1.5-flash', 'gemini-pro'],
        custom: []
    },
    get modelOptions() {
        return this.models[this.provider] || [];
    },
    updateModel() {
        const defaults = {
            openai: 'gpt-3.5-turbo',
            deepseek: 'deepseek-chat',
            anthropic: 'claude-3-5-sonnet-20241022',
            gemini: 'gemini-1.5-pro',
            custom: ''
        };
        this.model = defaults[this.provider] || '';
    },
    addPreviewMessage(type, text) {
        if (!text.trim()) return;
        this.previewMessages.push({
            type,
            text,
            time: new Date().toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' })
        });
    }
}">

    <!-- LINE-Themed Header -->
    <div class="mb-8">
        <div class="flex items-center gap-4 mb-4">
            <a href="{{ route('admin.line-bot.ai.index') }}"
               class="flex items-center justify-center w-10 h-10 rounded-xl glass-fusion dark:bg-slate-800 shadow-lg hover:shadow-xl transition-all duration-300 group">
                <svg class="w-5 h-5 text-gray-600 dark:text-gray-400 dark:text-gray-300 group-hover:text-[#00B900] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#00B900] to-[#00E600] flex items-center justify-center shadow-lg shadow-green-500/30">
                        <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold bg-gradient-to-r from-[#00B900] to-[#00E600] bg-clip-text text-transparent">
                            แก้ไข LINE Bot AI
                        </h1>
                        <p class="text-gray-600 dark:text-gray-400 mt-1">
                            แก้ไขการตั้งค่า: {{ $aiSetting->name }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Status Badge -->
            @if($aiSetting->is_active)
                <div class="px-4 py-2 bg-gradient-to-r from-[#00B900] to-[#00E600] text-white rounded-xl font-bold shadow-lg shadow-green-500/30 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    กำลังใช้งาน
                </div>
            @else
                <div class="px-4 py-2 bg-gray-200 dark:bg-gray-700 dark:bg-slate-700 text-gray-600 dark:text-gray-400 rounded-xl font-bold flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    ปิดใช้งาน
                </div>
            @endif
        </div>
    </div>

    @if($errors->any())
        <div class="mb-6 rounded-2xl bg-red-50 dark:bg-red-900/20 border-2 border-red-200 dark:border-red-800 p-6 shadow-lg">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 w-12 h-12 rounded-full bg-red-500 flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-bold text-red-900 dark:text-red-200 mb-2">พบข้อผิดพลาด</h3>
                    <ul class="space-y-1 text-sm text-red-700 dark:text-red-300">
                        @foreach($errors->all() as $error)
                            <li class="flex items-start gap-2">
                                <span class="text-red-500 mt-0.5">•</span>
                                <span>{{ $error }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.line-bot.ai.update', $aiSetting->id) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Form (Left - 2 columns) -->
            <div class="lg:col-span-2 space-y-6">

                <!-- ข้อมูลพื้นฐาน -->
                <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-100 dark:border-slate-700 overflow-hidden transition-all duration-300 hover:shadow-2xl" border border-white/20 dark:border-white/10>
                    <div class="bg-gradient-to-r from-[#00B900] to-[#00E600] px-6 py-5">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl glass-fusion backdrop-blur-sm flex items-center justify-center" border border-white/20 dark:border-white/10>
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-white">ข้อมูลพื้นฐาน</h3>
                        </div>
                    </div>
                    <div class="p-6 space-y-5">
                        <!-- ชื่อการตั้งค่า -->
                        <div class="group">
                            <label class="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                                <svg class="w-5 h-5 text-[#00B900]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                </svg>
                                ชื่อการตั้งค่า
                                <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="name" value="{{ old('name', $aiSetting->name) }}" required
                                   placeholder="เช่น: AI ตอบคำถามทั่วไป"
                                   class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 dark:border-slate-600 glass-fusion dark:bg-slate-700 text-gray-900 dark:text-white rounded-xl focus:ring-4 focus:ring-green-500/20 focus:border-[#00B900] transition-all duration-300 @error('name') border-red-500 @enderror">
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                ตั้งชื่อที่จำง่ายเพื่อระบุการตั้งค่านี้
                            </p>
                        </div>

                        <!-- AI Provider -->
                        <div>
                            <label class="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                                <svg class="w-5 h-5 text-[#00B900]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                                </svg>
                                ผู้ให้บริการ AI
                                <span class="text-red-500">*</span>
                            </label>
                            <select name="provider" x-model="provider" @change="updateModel()" required
                                    class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 dark:border-slate-600 glass-fusion dark:bg-slate-700 text-gray-900 dark:text-white rounded-xl focus:ring-4 focus:ring-green-500/20 focus:border-[#00B900] transition-all duration-300">
                                <option value="openai">OpenAI (GPT-3.5, GPT-4)</option>
                                <option value="deepseek">DeepSeek</option>
                                <option value="anthropic">Anthropic (Claude)</option>
                                <option value="gemini">Google Gemini</option>
                                <option value="custom">กำหนดเอง</option>
                            </select>
                        </div>

                        <!-- Model -->
                        <div>
                            <label class="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                                <svg class="w-5 h-5 text-[#00B900]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                                </svg>
                                โมเดล AI
                                <span class="text-red-500">*</span>
                            </label>
                            <div x-show="provider !== 'custom'">
                                <select x-model="model" required
                                        class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 dark:border-slate-600 glass-fusion dark:bg-slate-700 text-gray-900 dark:text-white rounded-xl focus:ring-4 focus:ring-green-500/20 focus:border-[#00B900] transition-all duration-300">
                                    <template x-for="modelOption in modelOptions" :key="modelOption">
                                        <option :value="modelOption" x-text="modelOption"></option>
                                    </template>
                                </select>
                            </div>
                            <div x-show="provider === 'custom'" x-cloak>
                                <input type="text" x-model="model" placeholder="ชื่อโมเดลของคุณ"
                                       class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 dark:border-slate-600 glass-fusion dark:bg-slate-700 text-gray-900 dark:text-white rounded-xl focus:ring-4 focus:ring-green-500/20 focus:border-[#00B900] transition-all duration-300">
                            </div>
                            <input type="hidden" name="model" :value="model">
                        </div>

                        <!-- API Key -->
                        <div>
                            <label class="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                                <svg class="w-5 h-5 text-[#00B900]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                </svg>
                                API Key
                                <span class="text-red-500">*</span>
                            </label>
                            <input type="password" name="api_key" value="{{ old('api_key', $aiSetting->api_key) }}" required
                                   placeholder="sk-..."
                                   class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 dark:border-slate-600 glass-fusion dark:bg-slate-700 text-gray-900 dark:text-white rounded-xl focus:ring-4 focus:ring-green-500/20 focus:border-[#00B900] transition-all duration-300 font-mono text-sm">
                        </div>

                        <!-- Custom Endpoint -->
                        <div x-show="provider === 'custom'" x-cloak>
                            <label class="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                                <svg class="w-5 h-5 text-[#00B900]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                </svg>
                                API Endpoint URL
                            </label>
                            <input type="url" name="api_endpoint" value="{{ old('api_endpoint', $aiSetting->api_endpoint) }}"
                                   placeholder="https://api.example.com/v1/chat/completions"
                                   class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 dark:border-slate-600 glass-fusion dark:bg-slate-700 text-gray-900 dark:text-white rounded-xl focus:ring-4 focus:ring-green-500/20 focus:border-[#00B900] transition-all duration-300">
                        </div>
                    </div>
                </div>

                <!-- พารามิเตอร์ AI -->
                <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-100 dark:border-slate-700 overflow-hidden" border border-white/20 dark:border-white/10>
                    <div class="bg-gradient-to-r from-blue-500 to-cyan-500 px-6 py-5">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl glass-fusion backdrop-blur-sm flex items-center justify-center" border border-white/20 dark:border-white/10>
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-white">พารามิเตอร์ AI</h3>
                        </div>
                    </div>
                    <div class="p-6 space-y-5">
                        <!-- Temperature -->
                        <div>
                            <label class="flex items-center justify-between text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">
                                <span class="flex items-center gap-2">
                                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                    </svg>
                                    ความสร้างสรรค์ (Temperature)
                                </span>
                                <span id="temp-value" class="px-3 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-full font-mono text-sm">{{ old('temperature', $aiSetting->temperature) }}</span>
                            </label>
                            <input type="range" name="temperature" min="0" max="2" step="0.1" value="{{ old('temperature', $aiSetting->temperature) }}"
                                   oninput="document.getElementById('temp-value').textContent = this.value"
                                   class="w-full h-3 bg-gray-200 dark:bg-gray-700 dark:bg-slate-700 rounded-full appearance-none cursor-pointer accent-blue-500">
                            <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400 mt-2">
                                <span>เน้นความแม่นยำ</span>
                                <span>สมดุล</span>
                                <span>สร้างสรรค์</span>
                            </div>
                        </div>

                        <!-- Max Tokens -->
                        <div>
                            <label class="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                                </svg>
                                จำนวน Token สูงสุด
                            </label>
                            <input type="number" name="max_tokens" value="{{ old('max_tokens', $aiSetting->max_tokens) }}" min="100" max="4000" step="100"
                                   class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 dark:border-slate-600 glass-fusion dark:bg-slate-700 text-gray-900 dark:text-white rounded-xl focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 transition-all duration-300">
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">ความยาวสูงสุดของคำตอบ (100-4000)</p>
                        </div>

                        <!-- Memory Limit -->
                        <div>
                            <label class="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                จำนวนข้อความที่จำได้
                            </label>
                            <input type="number" name="conversation_memory_limit" value="{{ old('conversation_memory_limit', $aiSetting->conversation_memory_limit) }}" min="1" max="50"
                                   class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 dark:border-slate-600 glass-fusion dark:bg-slate-700 text-gray-900 dark:text-white rounded-xl focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 transition-all duration-300">
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">จำนวนข้อความที่ AI จะจดจำในบทสนทนา</p>
                        </div>

                        <!-- Enable History -->
                        <div class="flex items-center justify-between p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border-2 border-blue-200 dark:border-blue-800">
                            <div>
                                <h4 class="font-bold text-gray-900 dark:text-white">จดจำบทสนทนา</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">เปิดใช้งานความจำบทสนทนา</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="enable_conversation_history" value="1" class="sr-only peer"
                                       {{ old('enable_conversation_history', $aiSetting->enable_conversation_history) ? 'checked' : '' }} x-model="enableHistory">
                                <div class="w-14 h-7 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer peer-checked:after:translate-x-7 peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:glass-fusion after:border-gray-300 dark:border-gray-600 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-blue-600" border border-white/20 dark:border-white/10></div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- System Prompt -->
                <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-100 dark:border-slate-700 overflow-hidden" border border-white/20 dark:border-white/10>
                    <div class="bg-gradient-to-r from-purple-500 to-pink-500 px-6 py-5">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl glass-fusion backdrop-blur-sm flex items-center justify-center" border border-white/20 dark:border-white/10>
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-white">คำสั่ง System Prompt</h3>
                        </div>
                    </div>
                    <div class="p-6">
                        <label class="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                            <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                            </svg>
                            ตั้งค่าบุคลิกภาพและหน้าที่ของ AI
                        </label>
                        <textarea name="system_prompt" rows="8"
                                  placeholder="คุณคือผู้ช่วยที่เป็นมิตรและมีประโยชน์สำหรับระบบแอฟฟิลิเอต..."
                                  class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 dark:border-slate-600 glass-fusion dark:bg-slate-700 text-gray-900 dark:text-white rounded-xl focus:ring-4 focus:ring-purple-500/20 focus:border-purple-500 transition-all duration-300 font-mono text-sm">{{ old('system_prompt', $aiSetting->system_prompt) }}</textarea>

                        <div class="mt-4 p-4 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl border-2 border-purple-200 dark:border-purple-800">
                            <h4 class="font-bold text-purple-900 dark:text-purple-200 mb-2 flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                </svg>
                                เคล็ดลับการเขียน Prompt
                            </h4>
                            <ul class="space-y-2 text-sm text-purple-800 dark:text-purple-200">
                                <li class="flex items-start gap-2">
                                    <svg class="w-4 h-4 text-purple-600 dark:text-purple-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <span>ระบุบทบาทและความเชี่ยวชาญของ AI ให้ชัดเจน</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <svg class="w-4 h-4 text-purple-600 dark:text-purple-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <span>กำหนดน้ำเสียงและสไตล์การพูดคุย</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <svg class="w-4 h-4 text-purple-600 dark:text-purple-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <span>เพิ่มข้อมูลเกี่ยวกับธุรกิจและบริการ</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Fallback Message -->
                <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-100 dark:border-slate-700 overflow-hidden" border border-white/20 dark:border-white/10>
                    <div class="bg-gradient-to-r from-orange-500 to-red-500 px-6 py-5">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl glass-fusion backdrop-blur-sm flex items-center justify-center" border border-white/20 dark:border-white/10>
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-white">ข้อความสำรอง</h3>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="mb-4">
                            <label class="flex items-center justify-between">
                                <span class="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-300">
                                    <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                    </svg>
                                    เปิดใช้ข้อความสำรอง
                                </span>
                                <input type="checkbox" name="enable_fallback" value="1"
                                       {{ old('enable_fallback', $aiSetting->enable_fallback) ? 'checked' : '' }} x-model="enableFallback"
                                       class="w-5 h-5 text-orange-600 bg-gray-100/50 dark:bg-gray-800/50 border-gray-300 dark:border-gray-600 rounded focus:ring-orange-500 dark:focus:ring-orange-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                            </label>
                        </div>
                        <div x-show="enableFallback">
                            <label class="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                                ข้อความเมื่อ AI ไม่สามารถตอบได้
                            </label>
                            <textarea name="fallback_message" rows="4"
                                      placeholder="ขออภัยค่ะ ขณะนี้ระบบไม่สามารถตอบคำถามได้ กรุณาติดต่อเจ้าหน้าที่..."
                                      class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 dark:border-slate-600 glass-fusion dark:bg-slate-700 text-gray-900 dark:text-white rounded-xl focus:ring-4 focus:ring-orange-500/20 focus:border-orange-500 transition-all duration-300">{{ old('fallback_message', $aiSetting->fallback_message) }}</textarea>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Sidebar (Right - 1 column) -->
            <div class="lg:col-span-1 space-y-6">

                <!-- สถานะ -->
                <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-100 dark:border-slate-700 overflow-hidden sticky top-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                    <div class="bg-gradient-to-r from-[#00B900] to-[#00E600] px-6 py-5">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl glass-fusion backdrop-blur-sm flex items-center justify-center" border border-white/20 dark:border-white/10>
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-white">สถานะ</h3>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h4 class="text-base font-bold text-gray-900 dark:text-white">เปิดใช้งาน</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">ใช้การตั้งค่านี้</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="is_active" value="1" class="sr-only peer"
                                       {{ old('is_active', $aiSetting->is_active) ? 'checked' : '' }} x-model="isActive">
                                <div class="w-14 h-7 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 rounded-full peer peer-checked:after:translate-x-7 peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:glass-fusion after:border-gray-300 dark:border-gray-600 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-[#00B900] peer-checked:to-[#00E600]" border border-white/20 dark:border-white/10></div>
                            </label>
                        </div>

                        <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-xl border-2 border-yellow-200 dark:border-yellow-800">
                            <div class="flex items-start gap-2">
                                <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <p class="text-sm text-yellow-800 dark:text-yellow-200">
                                    สามารถเปิดใช้งานได้เพียง 1 การตั้งค่าเท่านั้น
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chat Preview -->
                <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-100 dark:border-slate-700 overflow-hidden" border border-white/20 dark:border-white/10>
                    <div class="bg-gradient-to-r from-[#00B900] to-[#00E600] px-6 py-5">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl glass-fusion backdrop-blur-sm flex items-center justify-center" border border-white/20 dark:border-white/10>
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-white">ตัวอย่างแชท</h3>
                        </div>
                    </div>
                    <div class="h-96 bg-gray-100/50 dark:bg-slate-900 p-4 overflow-y-auto">
                        <div class="space-y-3">
                            <template x-for="(msg, index) in previewMessages" :key="index">
                                <div :class="msg.type === 'user' ? 'flex justify-end' : 'flex justify-start'">
                                    <div :class="msg.type === 'user' ? 'bg-[#00B900] text-white' : 'glass-fusion dark:bg-slate-800 text-gray-900 dark:text-white'" border border-white/20 dark:border-white/10
                                         class="max-w-[80%] rounded-2xl px-4 py-3 shadow-lg">
                                        <div class="flex items-start gap-2">
                                            <div x-show="msg.type === 'bot'" class="w-6 h-6 rounded-full bg-[#00B900] flex items-center justify-center flex-shrink-0">
                                                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z"/>
                                                    <path d="M15 7v2a4 4 0 01-4 4H9.828l-1.766 1.767c.28.149.599.233.938.233h2l3 3v-3h2a2 2 0 002-2V9a2 2 0 00-2-2h-1z"/>
                                                </svg>
                                            </div>
                                            <div class="flex-1">
                                                <p class="text-sm" x-text="msg.text"></p>
                                                <p class="text-xs opacity-70 mt-1" x-text="msg.time"></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                    <div class="p-4 glass-fusion dark:bg-slate-800 border-t border-gray-200 dark:border-gray-700 dark:border-slate-700" border border-white/20 dark:border-white/10>
                        <div class="flex gap-2">
                            <input type="text" x-model="testMessage" placeholder="พิมพ์ข้อความ..."
                                   @keyup.enter="addPreviewMessage('user', testMessage); testMessage = ''"
                                   class="flex-1 px-4 py-2 border-2 border-gray-200 dark:border-gray-700 dark:border-slate-600 glass-fusion dark:bg-slate-700 text-gray-900 dark:text-white rounded-full focus:ring-2 focus:ring-[#00B900] focus:border-[#00B900]">
                            <button type="button" @click="addPreviewMessage('user', testMessage); testMessage = ''"
                                    class="w-10 h-10 bg-gradient-to-r from-[#00B900] to-[#00E600] text-white rounded-full flex items-center justify-center hover:shadow-lg transition-all duration-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- สถิติการใช้งาน -->
                <div class="bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-2xl border-2 border-indigo-200 dark:border-indigo-800 p-6">
                    <h3 class="font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        ข้อมูลการใช้งาน
                    </h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex items-center justify-between p-3 glass-fusion dark:bg-slate-800 rounded-xl" border border-white/20 dark:border-white/10>
                            <span class="text-gray-600 dark:text-gray-400">สร้างเมื่อ</span>
                            <span class="font-bold text-gray-900 dark:text-white">{{ $aiSetting->created_at->format('d/m/Y') }}</span>
                        </div>
                        <div class="flex items-center justify-between p-3 glass-fusion dark:bg-slate-800 rounded-xl" border border-white/20 dark:border-white/10>
                            <span class="text-gray-600 dark:text-gray-400">แก้ไขล่าสุด</span>
                            <span class="font-bold text-gray-900 dark:text-white">{{ $aiSetting->updated_at->diffForHumans() }}</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Submit Button -->
        <div class="mt-8">
            <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-100 dark:border-slate-700 p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <a href="{{ route('admin.line-bot.ai.index') }}"
                       class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        กลับไปหน้ารายการ
                    </a>
                    <button type="submit"
                            class="px-8 py-4 bg-gradient-to-r from-[#00B900] to-[#00E600] text-white rounded-xl hover:shadow-2xl hover:shadow-green-500/50 transition-all duration-300 transform hover:-translate-y-1 font-bold text-lg flex items-center gap-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        บันทึกการเปลี่ยนแปลง
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
[x-cloak] { display: none !important; }

/* Custom scrollbar for chat preview */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    @apply bg-gray-100/50 dark:bg-gray-800/50 dark:bg-slate-800 rounded-full;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    @apply bg-gray-300 dark:bg-slate-600 rounded-full hover:bg-gray-400 dark:hover:bg-slate-500;
}
</style>
@endsection
