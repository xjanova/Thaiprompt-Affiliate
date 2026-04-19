@extends('layouts.admin-v3')

@section('title', 'ติดตั้ง Llama AI')

@section('content')
<div x-data="llamaInstaller()" x-init="init()" class="min-h-screen bg-gradient-to-br from-gray-900 via-purple-900 to-blue-900 p-6">
    {{-- Header --}}
    <div class="max-w-6xl mx-auto">
        <div class="flex items-center justify-between mb-8">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.ai-providers.index') }}" class="p-2 rounded-lg bg-white/10 hover:bg-white/20 transition">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <div>
                    <h1 class="text-3xl font-bold text-white flex items-center gap-3">
                        <span class="text-5xl">🦙</span>
                        ติดตั้ง Llama AI
                    </h1>
                    <p class="text-purple-200 mt-1">ติดตั้ง Meta Llama สำหรับใช้งาน AI บนเซิร์ฟเวอร์ของคุณ</p>
                </div>
            </div>
        </div>

        {{-- System Info Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-4 border border-white/20">
                <div class="flex items-center gap-3">
                    <div class="p-3 bg-blue-500/20 rounded-xl">
                        <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-gray-400 text-sm">CPU Cores</p>
                        <p class="text-2xl font-bold text-white">{{ $systemInfo['cpu_cores'] }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-4 border border-white/20">
                <div class="flex items-center gap-3">
                    <div class="p-3 bg-green-500/20 rounded-xl">
                        <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-gray-400 text-sm">RAM</p>
                        <p class="text-2xl font-bold text-white">{{ $systemInfo['ram_gb'] }} GB</p>
                    </div>
                </div>
            </div>

            <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-4 border border-white/20">
                <div class="flex items-center gap-3">
                    <div class="p-3 bg-purple-500/20 rounded-xl">
                        <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-gray-400 text-sm">พื้นที่ว่าง</p>
                        <p class="text-2xl font-bold text-white">{{ $systemInfo['disk_free_gb'] }} GB</p>
                    </div>
                </div>
            </div>

            <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-4 border border-white/20">
                <div class="flex items-center gap-3">
                    <div class="p-3 bg-yellow-500/20 rounded-xl">
                        <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-gray-400 text-sm">แนะนำ</p>
                        <p class="text-lg font-bold text-white">{{ $systemInfo['recommended_model'] }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Main Content --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Left: Installation Options --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Progress Card (เมื่อกำลังติดตั้ง) --}}
                <div x-show="progress.status === 'running'" x-cloak
                     class="bg-white/10 backdrop-blur-xl rounded-3xl p-6 border border-white/20">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-white flex items-center gap-2">
                            <span class="animate-pulse">🦙</span>
                            กำลังติดตั้ง...
                        </h2>
                        <button @click="cancelInstall()"
                                class="px-4 py-2 bg-red-500/20 text-red-400 rounded-lg hover:bg-red-500/30 transition">
                            ยกเลิก
                        </button>
                    </div>

                    {{-- Progress Bar --}}
                    <div class="relative mb-4">
                        <div class="h-6 bg-gray-700 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-purple-500 via-blue-500 to-cyan-500 rounded-full transition-all duration-500 flex items-center justify-center"
                                 :style="'width: ' + progress.percentage + '%'">
                                <span class="text-xs font-bold text-white" x-text="progress.percentage + '%'"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Step Info --}}
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 bg-purple-500/20 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-400 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-white font-medium" x-text="progress.step_name || 'กำลังเริ่มต้น...'"></p>
                            <p class="text-gray-400 text-sm" x-text="progress.message || ''"></p>
                        </div>
                    </div>

                    {{-- Installation Steps --}}
                    <div class="grid grid-cols-5 gap-2 mt-6">
                        <template x-for="(step, key) in installSteps" :key="key">
                            <div class="relative">
                                <div :class="{
                                    'bg-green-500': isStepComplete(key),
                                    'bg-purple-500 animate-pulse': progress.step === key,
                                    'bg-gray-600': !isStepComplete(key) && progress.step !== key
                                }" class="h-2 rounded-full transition-all"></div>
                                <p class="text-xs text-gray-400 mt-1 text-center" x-text="step.name"></p>
                            </div>
                        </template>
                    </div>

                    {{-- Model Info --}}
                    <div class="mt-6 p-4 bg-gray-800/50 rounded-xl">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-400">วิธีติดตั้ง:</span>
                            <span class="text-white" x-text="progress.method === 'ollama' ? 'Ollama' : 'Hugging Face'"></span>
                        </div>
                        <div class="flex items-center justify-between text-sm mt-2">
                            <span class="text-gray-400">Model:</span>
                            <span class="text-white" x-text="progress.model"></span>
                        </div>
                    </div>
                </div>

                {{-- Completed Card --}}
                <div x-show="progress.status === 'completed'" x-cloak
                     class="bg-gradient-to-br from-green-500/20 to-emerald-500/20 backdrop-blur-xl rounded-3xl p-6 border border-green-500/30">
                    <div class="text-center py-8">
                        <div class="text-6xl mb-4">✅</div>
                        <h2 class="text-2xl font-bold text-white mb-2">ติดตั้งสำเร็จ!</h2>
                        <p class="text-green-300" x-text="progress.message"></p>
                        <div class="mt-6 flex justify-center gap-4">
                            <a href="{{ route('admin.ai-providers.index') }}"
                               class="px-6 py-3 bg-white/20 text-white rounded-xl hover:bg-white/30 transition">
                                ไปหน้าจัดการ AI
                            </a>
                            <button @click="resetProgress()"
                                    class="px-6 py-3 bg-green-500 text-white rounded-xl hover:bg-green-600 transition">
                                ติดตั้ง Model เพิ่ม
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Error Card --}}
                <div x-show="progress.status === 'error'" x-cloak
                     class="bg-gradient-to-br from-red-500/20 to-pink-500/20 backdrop-blur-xl rounded-3xl p-6 border border-red-500/30">

                    {{-- Shared Hosting Notice --}}
                    <div x-show="progress.is_shared_hosting" class="mb-6">
                        <div class="text-center py-4">
                            <div class="text-5xl mb-3">⚠️</div>
                            <h2 class="text-xl font-bold text-white mb-2">Shared Hosting ตรวจพบ</h2>
                            <p class="text-yellow-300 text-sm" x-text="progress.message"></p>
                        </div>

                        {{-- Manual Instructions --}}
                        <div x-show="progress.manual_instructions" class="mt-4 p-4 bg-gray-800/50 rounded-xl">
                            <h3 class="text-white font-bold mb-3" x-text="progress.manual_instructions?.title"></h3>
                            <ol class="text-sm text-gray-300 space-y-2 list-decimal list-inside">
                                <template x-for="step in progress.manual_instructions?.steps || []">
                                    <li x-text="step"></li>
                                </template>
                            </ol>

                            {{-- Copy Commands --}}
                            <div class="mt-4" x-show="progress.manual_instructions?.commands">
                                <p class="text-gray-400 text-xs mb-2">คำสั่งสำหรับ copy:</p>
                                <template x-for="cmd in progress.manual_instructions?.commands || []">
                                    <div class="bg-gray-900 rounded-lg p-2 mb-2 flex items-center justify-between">
                                        <code class="text-green-400 text-xs" x-text="cmd"></code>
                                        <button @click="navigator.clipboard.writeText(cmd)"
                                                class="text-gray-400 hover:text-white text-xs px-2 py-1">
                                            📋 Copy
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Alternative Options --}}
                        <div x-show="progress.alternative_options" class="mt-6">
                            <h3 class="text-white font-bold mb-3">🌟 ทางเลือกอื่น (แนะนำ)</h3>
                            <div class="grid gap-3">
                                <template x-for="option in progress.alternative_options || []">
                                    <div class="p-3 bg-gray-800/50 rounded-xl border border-gray-700">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <h4 class="text-white font-medium" x-text="option.name"></h4>
                                                <p class="text-gray-400 text-xs" x-text="option.description"></p>
                                            </div>
                                            <span x-show="option.free_tier" class="px-2 py-1 bg-green-500/20 text-green-400 text-xs rounded-full">ฟรี</span>
                                        </div>
                                        <p class="text-purple-300 text-xs mt-2" x-text="'💡 ' + option.setup"></p>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-center gap-4">
                            <a href="{{ route('admin.ai-providers.index') }}"
                               class="px-6 py-3 bg-purple-500 text-white rounded-xl hover:bg-purple-600 transition">
                                ไปตั้งค่า Cloud AI แทน
                            </a>
                            <button @click="resetProgress()"
                                    class="px-6 py-3 bg-gray-600 text-white rounded-xl hover:bg-gray-700 transition">
                                ลองใหม่
                            </button>
                        </div>
                    </div>

                    {{-- Generic Error --}}
                    <div x-show="!progress.is_shared_hosting" class="text-center py-8">
                        <div class="text-6xl mb-4">❌</div>
                        <h2 class="text-2xl font-bold text-white mb-2">เกิดข้อผิดพลาด</h2>
                        <p class="text-red-300" x-text="progress.message"></p>
                        <div class="mt-6">
                            <button @click="resetProgress()"
                                    class="px-6 py-3 bg-red-500 text-white rounded-xl hover:bg-red-600 transition">
                                ลองใหม่
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Installation Form (เมื่อยังไม่ได้ติดตั้ง) --}}
                <div x-show="progress.status === 'idle' || progress.status === 'cancelled'" x-cloak>
                    {{-- Method Selection --}}
                    <div class="bg-white/10 backdrop-blur-xl rounded-3xl p-6 border border-white/20 mb-6">
                        <h2 class="text-xl font-bold text-white mb-4">1. เลือกวิธีติดตั้ง</h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {{-- Ollama --}}
                            <div @click="selectedMethod = 'ollama'"
                                 :class="selectedMethod === 'ollama' ? 'border-purple-500 bg-purple-500/20' : 'border-white/20 hover:border-white/40'"
                                 class="p-4 rounded-2xl border-2 cursor-pointer transition-all">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-500 rounded-xl flex items-center justify-center">
                                        <span class="text-2xl">🦙</span>
                                    </div>
                                    <div>
                                        <h3 class="text-white font-bold">Ollama</h3>
                                        <p class="text-gray-400 text-sm">แนะนำ - ง่ายและเร็ว</p>
                                    </div>
                                    <div x-show="selectedMethod === 'ollama'" class="ml-auto">
                                        <svg class="w-6 h-6 text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                </div>
                                <ul class="text-sm text-gray-300 space-y-1">
                                    <li>✅ ติดตั้งง่าย อัตโนมัติ</li>
                                    <li>✅ จัดการ Model ผ่าน CLI</li>
                                    <li>✅ รองรับ API OpenAI-compatible</li>
                                </ul>
                            </div>

                            {{-- Hugging Face --}}
                            <div @click="selectedMethod = 'huggingface'"
                                 :class="selectedMethod === 'huggingface' ? 'border-purple-500 bg-purple-500/20' : 'border-white/20 hover:border-white/40'"
                                 class="p-4 rounded-2xl border-2 cursor-pointer transition-all">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-12 h-12 bg-gradient-to-br from-yellow-500 to-orange-500 rounded-xl flex items-center justify-center">
                                        <span class="text-2xl">🤗</span>
                                    </div>
                                    <div>
                                        <h3 class="text-white font-bold">Hugging Face</h3>
                                        <p class="text-gray-400 text-sm">GGUF + llama.cpp</p>
                                    </div>
                                    <div x-show="selectedMethod === 'huggingface'" class="ml-auto">
                                        <svg class="w-6 h-6 text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                </div>
                                <ul class="text-sm text-gray-300 space-y-1">
                                    <li>✅ Model คุณภาพสูง (GGUF)</li>
                                    <li>✅ Llama 4 รุ่นใหม่ล่าสุด</li>
                                    <li>⚠️ ต้องการพื้นที่มากกว่า</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    {{-- Model Selection --}}
                    <div class="bg-white/10 backdrop-blur-xl rounded-3xl p-6 border border-white/20">
                        <h2 class="text-xl font-bold text-white mb-4">2. เลือก Model</h2>

                        <div class="space-y-3">
                            <template x-for="model in getAvailableModels()" :key="model.id">
                                <div @click="selectedModel = model.id"
                                     :class="selectedModel === model.id ? 'border-purple-500 bg-purple-500/20' : 'border-white/10 hover:border-white/30'"
                                     class="p-4 rounded-xl border-2 cursor-pointer transition-all">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div x-show="selectedModel === model.id"
                                                 class="w-5 h-5 bg-purple-500 rounded-full flex items-center justify-center">
                                                <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                </svg>
                                            </div>
                                            <div x-show="selectedModel !== model.id"
                                                 class="w-5 h-5 border-2 border-gray-500 rounded-full"></div>
                                            <div>
                                                <h3 class="text-white font-medium" x-text="model.name"></h3>
                                                <p class="text-gray-400 text-sm" x-text="model.description"></p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm text-purple-300" x-text="model.size"></p>
                                            <p class="text-xs text-gray-500">RAM: <span x-text="model.ram"></span></p>
                                        </div>
                                    </div>
                                    {{-- Recommended Badge --}}
                                    <div x-show="model.id === '{{ $systemInfo['recommended_model'] }}'"
                                         class="mt-2 inline-flex items-center gap-1 px-2 py-1 bg-green-500/20 text-green-400 text-xs rounded-full">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        แนะนำสำหรับเครื่องคุณ
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- Install Button --}}
                        <div class="mt-6">
                            <button @click="startInstall()"
                                    :disabled="!selectedModel || installing"
                                    :class="!selectedModel ? 'opacity-50 cursor-not-allowed' : 'hover:from-purple-600 hover:to-blue-600'"
                                    class="w-full py-4 bg-gradient-to-r from-purple-500 to-blue-500 text-white font-bold rounded-xl transition flex items-center justify-center gap-2">
                                <span class="text-2xl">🦙</span>
                                <span x-text="installing ? 'กำลังเริ่มติดตั้ง...' : 'เริ่มติดตั้ง'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right: Log & Info --}}
            <div class="space-y-6">
                {{-- Installation Log --}}
                <div class="bg-white/10 backdrop-blur-xl rounded-3xl p-6 border border-white/20">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-bold text-white">📋 Log การติดตั้ง</h2>
                        <button @click="refreshLog()"
                                class="p-2 hover:bg-white/10 rounded-lg transition"
                                title="รีเฟรช">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                        </button>
                    </div>
                    <div class="bg-gray-900/50 rounded-xl p-4 h-64 overflow-y-auto font-mono text-xs">
                        <pre class="text-green-400 whitespace-pre-wrap" x-text="installLog || 'ยังไม่มี log...'"></pre>
                    </div>
                </div>

                {{-- Help Info --}}
                <div class="bg-white/10 backdrop-blur-xl rounded-3xl p-6 border border-white/20">
                    <h2 class="text-lg font-bold text-white mb-4">💡 ข้อมูลเพิ่มเติม</h2>

                    <div class="space-y-4 text-sm">
                        <div class="p-3 bg-blue-500/10 border border-blue-500/30 rounded-xl">
                            <h3 class="text-blue-400 font-medium mb-1">Ollama คืออะไร?</h3>
                            <p class="text-gray-300">เครื่องมือสำหรับรัน AI models แบบ local อย่างง่าย รองรับ API เหมือน OpenAI</p>
                        </div>

                        <div class="p-3 bg-purple-500/10 border border-purple-500/30 rounded-xl">
                            <h3 class="text-purple-400 font-medium mb-1">GGUF คืออะไร?</h3>
                            <p class="text-gray-300">รูปแบบไฟล์ model ที่ถูกบีบอัด (quantized) เพื่อใช้ RAM น้อยลงแต่ยังคงคุณภาพ</p>
                        </div>

                        <div class="p-3 bg-green-500/10 border border-green-500/30 rounded-xl">
                            <h3 class="text-green-400 font-medium mb-1">เลือก Model ไหนดี?</h3>
                            <p class="text-gray-300">ขึ้นอยู่กับ RAM ที่มี:
                                <br>• 8GB RAM → Llama 3.2 3B
                                <br>• 16GB RAM → Llama 3.1 8B
                                <br>• 64GB RAM → Llama 3.1 70B
                            </p>
                        </div>

                        <div class="p-3 bg-yellow-500/10 border border-yellow-500/30 rounded-xl">
                            <h3 class="text-yellow-400 font-medium mb-1">⚠️ หมายเหตุ</h3>
                            <p class="text-gray-300">การดาวน์โหลด Model อาจใช้เวลา 10-60 นาที ขึ้นอยู่กับความเร็วอินเทอร์เน็ต</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
</style>

<script>
function llamaInstaller() {
    return {
        // State
        selectedMethod: 'ollama',
        selectedModel: '{{ $systemInfo['recommended_model'] }}',
        installing: false,
        progress: @json($progress),
        installLog: '',

        // Installation Steps
        installSteps: {
            checking_system: { name: 'ตรวจสอบ' },
            installing_dependencies: { name: 'Dependencies' },
            downloading_model: { name: 'ดาวน์โหลด' },
            setting_up_server: { name: 'ตั้งค่า' },
            testing: { name: 'ทดสอบ' },
        },

        // Available Models
        availableModels: @json($availableModels),

        // Initialize
        init() {
            // เริ่ม polling ถ้ากำลังติดตั้ง
            if (this.progress.status === 'running') {
                this.startPolling();
            }
        },

        // Get models for selected method
        getAvailableModels() {
            return this.availableModels[this.selectedMethod] || [];
        },

        // Check if step is complete
        isStepComplete(stepKey) {
            const steps = Object.keys(this.installSteps);
            const currentIndex = steps.indexOf(this.progress.step);
            const stepIndex = steps.indexOf(stepKey);
            return stepIndex < currentIndex;
        },

        // Start Installation
        async startInstall() {
            if (!this.selectedModel || this.installing) return;

            this.installing = true;

            try {
                const response = await fetch('{{ route('admin.ai-providers.install.start') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({
                        method: this.selectedMethod,
                        model: this.selectedModel,
                    }),
                });

                const data = await response.json();

                if (data.success) {
                    this.progress = {
                        status: 'running',
                        step: 'checking_system',
                        step_name: 'ตรวจสอบระบบ',
                        percentage: 0,
                        message: 'เริ่มต้นการติดตั้ง...',
                        method: this.selectedMethod,
                        model: this.selectedModel,
                    };
                    this.startPolling();
                } else {
                    alert(data.message || 'เกิดข้อผิดพลาด');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('เกิดข้อผิดพลาดในการเริ่มติดตั้ง');
            }

            this.installing = false;
        },

        // Start polling for progress
        startPolling() {
            this.pollInterval = setInterval(async () => {
                await this.fetchProgress();
                await this.refreshLog();

                // หยุด polling เมื่อเสร็จหรือ error
                if (this.progress.status === 'completed' ||
                    this.progress.status === 'error' ||
                    this.progress.status === 'cancelled') {
                    clearInterval(this.pollInterval);
                }
            }, 2000); // Poll every 2 seconds
        },

        // Fetch Progress
        async fetchProgress() {
            try {
                const response = await fetch('{{ route('admin.ai-providers.install.progress') }}');
                const data = await response.json();

                if (data.success) {
                    this.progress = data.data;
                }
            } catch (error) {
                console.error('Error fetching progress:', error);
            }
        },

        // Refresh Log
        async refreshLog() {
            try {
                const response = await fetch('{{ route('admin.ai-providers.install.log') }}');
                const data = await response.json();

                if (data.success) {
                    this.installLog = data.log;
                }
            } catch (error) {
                console.error('Error fetching log:', error);
            }
        },

        // Cancel Installation
        async cancelInstall() {
            if (!confirm('ยืนยันการยกเลิกการติดตั้ง?')) return;

            try {
                const response = await fetch('{{ route('admin.ai-providers.install.cancel') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                });

                const data = await response.json();

                if (data.success) {
                    this.progress = {
                        status: 'cancelled',
                        percentage: 0,
                        message: 'ยกเลิกการติดตั้งแล้ว',
                    };
                    clearInterval(this.pollInterval);
                }
            } catch (error) {
                console.error('Error cancelling:', error);
            }
        },

        // Reset Progress
        resetProgress() {
            this.progress = {
                status: 'idle',
                percentage: 0,
                message: '',
            };
            this.installLog = '';
        },
    };
}
</script>
@endsection
