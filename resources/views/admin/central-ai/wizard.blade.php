@extends('layouts.admin')

@section('title', 'ติดตั้ง Central AI')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 py-8 px-4"
     x-data="wizardManager()"
     x-init="init()">

    {{-- Header --}}
    <div class="max-w-6xl mx-auto mb-8">
        <div class="text-center">
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 shadow-lg mb-4">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                </svg>
            </div>
            <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-2">
                ติดตั้ง Central AI
            </h1>
            <p class="text-lg text-gray-600 dark:text-gray-400">
                ระบบจัดการ Ollama และ AI Providers แบบรวมศูนย์
            </p>
        </div>
    </div>

    {{-- Progress Steps --}}
    <div class="max-w-6xl mx-auto mb-12">
        <div class="flex items-center justify-between relative">
            {{-- Progress Line --}}
            <div class="absolute top-5 left-0 w-full h-1 bg-gray-200 dark:bg-gray-700" style="z-index: 0;"></div>
            <div class="absolute top-5 left-0 h-1 bg-gradient-to-r from-blue-500 to-purple-600 transition-all duration-500"
                 :style="`width: ${((currentStep - 1) / 4) * 100}%; z-index: 0;`"></div>

            {{-- Steps --}}
            <template x-for="step in steps" :key="step.number">
                <div class="flex flex-col items-center relative" style="z-index: 1;">
                    <div class="flex items-center justify-center w-10 h-10 rounded-full border-2 transition-all duration-300"
                         :class="{
                             'bg-gradient-to-br from-blue-500 to-purple-600 border-blue-500 text-white shadow-lg': currentStep >= step.number,
                             'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-400': currentStep < step.number
                         }">
                        <span class="text-sm font-semibold" x-text="step.number"></span>
                    </div>
                    <span class="mt-2 text-xs font-medium text-center max-w-24"
                          :class="{
                              'text-blue-600 dark:text-blue-400': currentStep === step.number,
                              'text-gray-600 dark:text-gray-400': currentStep !== step.number
                          }"
                          x-text="step.title"></span>
                </div>
            </template>
        </div>
    </div>

    {{-- Wizard Content --}}
    <div class="max-w-4xl mx-auto">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden border border-gray-200 dark:border-gray-700">

            {{-- Step 1: System Check --}}
            <div x-show="currentStep === 1" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-x-4" x-transition:enter-end="opacity-100 transform translate-x-0">
                <div class="p-8">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                        ตรวจสอบระบบ
                    </h2>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        กำลังตรวจสอบทรัพยากรของเครื่องเพื่อแนะนำการติดตั้งที่เหมาะสม
                    </p>

                    {{-- System Resources --}}
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-4 rounded-xl bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 border border-blue-200 dark:border-blue-700">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-blue-500 flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">CPU Cores</p>
                                    <p class="text-lg font-bold text-gray-900 dark:text-white" x-text="systemResources.cpu_cores || 'กำลังตรวจสอบ...'"></p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                    พร้อมใช้งาน
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between p-4 rounded-xl bg-gradient-to-r from-green-50 to-teal-50 dark:from-green-900/20 dark:to-teal-900/20 border border-green-200 dark:border-green-700">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-green-500 flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">RAM</p>
                                    <p class="text-lg font-bold text-gray-900 dark:text-white">
                                        <span x-text="systemResources.ram_gb ? systemResources.ram_gb + ' GB' : 'กำลังตรวจสอบ...'"></span>
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="px-3 py-1 text-xs font-semibold rounded-full"
                                      :class="systemResources.ram_gb >= 8 ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400'"
                                      x-text="systemResources.ram_gb >= 8 ? 'เพียงพอ' : 'จำกัด'">
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between p-4 rounded-xl bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 border border-purple-200 dark:border-purple-700">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-purple-500 flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Disk Space Available</p>
                                    <p class="text-lg font-bold text-gray-900 dark:text-white">
                                        <span x-text="systemResources.disk_available_gb ? systemResources.disk_available_gb + ' GB' : 'กำลังตรวจสอบ...'"></span>
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="px-3 py-1 text-xs font-semibold rounded-full"
                                      :class="systemResources.disk_available_gb >= 20 ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'"
                                      x-text="systemResources.disk_available_gb >= 20 ? 'เพียงพอ' : 'ไม่เพียงพอ'">
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between p-4 rounded-xl bg-gradient-to-r from-orange-50 to-red-50 dark:from-orange-900/20 dark:to-red-900/20 border border-orange-200 dark:border-orange-700">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-orange-500 flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Operating System</p>
                                    <p class="text-lg font-bold text-gray-900 dark:text-white capitalize" x-text="systemResources.os_type || 'กำลังตรวจสอบ...'"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- exec() disabled warning --}}
                    <div x-show="!canExec" x-cloak
                         class="mt-6 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-xl">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <div>
                                <p class="text-sm font-semibold text-amber-800 dark:text-amber-300 mb-1">PHP exec() ถูกปิดใช้งานบนเซิร์ฟเวอร์นี้</p>
                                <p class="text-sm text-amber-700 dark:text-amber-400">
                                    การติดตั้ง Ollama อัตโนมัติไม่สามารถทำได้ คุณจะต้องติดตั้ง Ollama ด้วยตนเองผ่าน SSH ในขั้นตอนถัดไป
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Recommended Models --}}
                    <div class="mt-8">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            โมเดลที่แนะนำสำหรับระบบของคุณ
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <template x-for="model in recommendedModels" :key="model.name">
                                <div class="p-4 rounded-xl border transition-all duration-200 cursor-pointer"
                                     :class="{
                                         'border-blue-500 bg-blue-50 dark:bg-blue-900/20': selectedModel === model.name,
                                         'border-gray-200 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-700': selectedModel !== model.name
                                     }"
                                     @click="selectedModel = model.name">
                                    <div class="flex items-start justify-between mb-2">
                                        <div>
                                            <p class="font-semibold text-gray-900 dark:text-white" x-text="model.name"></p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400" x-text="model.size"></p>
                                        </div>
                                        <span x-show="model.recommended" class="px-2 py-1 text-xs font-semibold bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400 rounded-full">
                                            แนะนำ
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400" x-text="model.description"></p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Step 2: Install Ollama --}}
            <div x-show="currentStep === 2" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-x-4" x-transition:enter-end="opacity-100 transform translate-x-0">
                <div class="p-8">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                        ติดตั้ง Ollama
                    </h2>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        Ollama เป็น LLM Engine ที่ทำงานบนเครื่องของคุณโดยไม่ต้องใช้ internet
                    </p>

                    {{-- แสดง error ถ้ามี --}}
                    <div x-show="installationError" x-cloak
                         class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-xl">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <p class="text-sm text-red-700 dark:text-red-300" x-text="installationError"></p>
                        </div>
                    </div>

                    {{-- โหมด Auto Install (exec() พร้อมใช้งาน) --}}
                    <div x-show="canExec" class="text-center py-12">
                        <template x-if="!installationStarted">
                            <div>
                                <div class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-gradient-to-br from-blue-100 to-purple-100 dark:from-blue-900/30 dark:to-purple-900/30 mb-6">
                                    <svg class="w-12 h-12 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                                    </svg>
                                </div>
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">พร้อมติดตั้ง Ollama</h3>
                                <p class="text-gray-600 dark:text-gray-400 mb-6">คลิกปุ่มด้านล่างเพื่อเริ่มการติดตั้ง</p>
                                <button @click="installOllama()"
                                        class="px-8 py-3 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-semibold rounded-xl shadow-lg transition-all duration-200 transform hover:scale-105">
                                    เริ่มติดตั้ง Ollama
                                </button>
                            </div>
                        </template>

                        <template x-if="installationStarted && !installationComplete">
                            <div>
                                <div class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-gradient-to-br from-blue-100 to-purple-100 dark:from-blue-900/30 dark:to-purple-900/30 mb-6">
                                    <svg class="w-12 h-12 text-blue-600 dark:text-blue-400 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                </div>
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">กำลังติดตั้ง...</h3>
                                <p class="text-gray-600 dark:text-gray-400 mb-6" x-text="installationMessage"></p>
                                <div class="max-w-md mx-auto">
                                    <div class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                        <div class="h-full bg-gradient-to-r from-blue-500 to-purple-600 transition-all duration-500" :style="`width: ${installationProgress}%`"></div>
                                    </div>
                                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400" x-text="`${installationProgress}%`"></p>
                                </div>
                            </div>
                        </template>

                        <template x-if="installationComplete">
                            <div>
                                <div class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-gradient-to-br from-green-100 to-teal-100 dark:from-green-900/30 dark:to-teal-900/30 mb-6">
                                    <svg class="w-12 h-12 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">ติดตั้งสำเร็จ!</h3>
                                <p class="text-gray-600 dark:text-gray-400">Ollama พร้อมใช้งานแล้ว</p>
                            </div>
                        </template>
                    </div>

                    {{-- โหมด Manual Install (exec() ถูก disable) --}}
                    <div x-show="!canExec" x-cloak>
                        {{-- สถานะยังไม่ได้ตรวจสอบ หรือ ตรวจสอบแล้วยังไม่พบ --}}
                        <template x-if="!installationComplete">
                            <div>
                                {{-- คำแนะนำการติดตั้งด้วยตนเอง --}}
                                <div class="mb-6 p-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-xl">
                                    <div class="flex items-start gap-3 mb-4">
                                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <div>
                                            <p class="text-sm font-semibold text-blue-800 dark:text-blue-300">
                                                PHP exec() ถูกปิดใช้งาน - กรุณาติดตั้ง Ollama ด้วยตนเอง
                                            </p>
                                            <p class="text-sm text-blue-700 dark:text-blue-400 mt-1">
                                                เปิด SSH terminal เข้าสู่เซิร์ฟเวอร์ แล้วรันคำสั่งด้านล่าง จากนั้นกด "ตรวจสอบการติดตั้ง"
                                            </p>
                                        </div>
                                    </div>

                                    {{-- ขั้นตอนที่ 1: ติดตั้ง Ollama --}}
                                    <div class="mb-4">
                                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                            1. ติดตั้ง Ollama:
                                        </p>
                                        <div class="relative">
                                            <pre class="bg-gray-900 text-green-400 p-4 rounded-lg text-sm font-mono overflow-x-auto">curl -fsSL https://ollama.com/install.sh | sh</pre>
                                            <button @click="copyCommand('curl -fsSL https://ollama.com/install.sh | sh')"
                                                    class="absolute top-2 right-2 px-2 py-1 bg-gray-700 hover:bg-gray-600 text-gray-300 text-xs rounded transition">
                                                คัดลอก
                                            </button>
                                        </div>
                                    </div>

                                    {{-- ขั้นตอนที่ 2: เริ่ม service --}}
                                    <div class="mb-4">
                                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                            2. เริ่ม Ollama Service:
                                        </p>
                                        <div class="relative">
                                            <pre class="bg-gray-900 text-green-400 p-4 rounded-lg text-sm font-mono overflow-x-auto">ollama serve &</pre>
                                            <button @click="copyCommand('ollama serve &')"
                                                    class="absolute top-2 right-2 px-2 py-1 bg-gray-700 hover:bg-gray-600 text-gray-300 text-xs rounded transition">
                                                คัดลอก
                                            </button>
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            หรือถ้าใช้ systemd: <code class="bg-gray-200 dark:bg-gray-700 px-1 rounded">sudo systemctl start ollama</code>
                                        </p>
                                    </div>

                                    {{-- ขั้นตอนที่ 3: ตรวจสอบ --}}
                                    <div>
                                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                            3. ตรวจสอบว่าทำงานได้:
                                        </p>
                                        <div class="relative">
                                            <pre class="bg-gray-900 text-green-400 p-4 rounded-lg text-sm font-mono overflow-x-auto">curl http://localhost:11434/api/version</pre>
                                            <button @click="copyCommand('curl http://localhost:11434/api/version')"
                                                    class="absolute top-2 right-2 px-2 py-1 bg-gray-700 hover:bg-gray-600 text-gray-300 text-xs rounded transition">
                                                คัดลอก
                                            </button>
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            ถ้าแสดงข้อมูล version แสดงว่า Ollama พร้อมใช้งาน
                                        </p>
                                    </div>
                                </div>

                                {{-- ปุ่มตรวจสอบการติดตั้ง --}}
                                <div class="text-center">
                                    <div class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-gradient-to-br from-blue-100 to-purple-100 dark:from-blue-900/30 dark:to-purple-900/30 mb-6">
                                        <svg class="w-12 h-12 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                        </svg>
                                    </div>
                                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">ติดตั้ง Ollama แล้ว?</h3>
                                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                                        หลังจากติดตั้งและเริ่ม Ollama ผ่าน SSH แล้ว กดปุ่มด้านล่างเพื่อตรวจสอบ
                                    </p>

                                    {{-- แสดง spinner ขณะตรวจสอบ --}}
                                    <div x-show="verifying" class="mb-4">
                                        <svg class="w-8 h-8 text-blue-600 dark:text-blue-400 animate-spin mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">กำลังตรวจสอบ...</p>
                                    </div>

                                    <button @click="verifyOllamaInstallation()"
                                            :disabled="verifying"
                                            class="px-8 py-3 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-semibold rounded-xl shadow-lg transition-all duration-200 transform hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed">
                                        <span x-show="!verifying">ตรวจสอบการติดตั้ง</span>
                                        <span x-show="verifying">กำลังตรวจสอบ...</span>
                                    </button>
                                </div>
                            </div>
                        </template>

                        {{-- ตรวจสอบสำเร็จ --}}
                        <template x-if="installationComplete">
                            <div class="text-center py-12">
                                <div class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-gradient-to-br from-green-100 to-teal-100 dark:from-green-900/30 dark:to-teal-900/30 mb-6">
                                    <svg class="w-12 h-12 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">ตรวจพบ Ollama!</h3>
                                <p class="text-gray-600 dark:text-gray-400">Ollama ติดตั้งและทำงานอยู่บนเซิร์ฟเวอร์แล้ว</p>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Step 3: Download Model --}}
            <div x-show="currentStep === 3" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-x-4" x-transition:enter-end="opacity-100 transform translate-x-0">
                <div class="p-8">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                        ดาวน์โหลดโมเดล AI
                    </h2>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        โมเดลที่เลือก: <span class="font-semibold text-blue-600 dark:text-blue-400" x-text="selectedModel || 'ยังไม่ได้เลือก'"></span>
                    </p>

                    {{-- แสดง error ถ้ามี --}}
                    <div x-show="downloadError" x-cloak
                         class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-xl">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <p class="text-sm text-red-700 dark:text-red-300" x-text="downloadError"></p>
                        </div>
                    </div>

                    <div class="text-center py-12">
                        <template x-if="!downloadStarted">
                            <div>
                                <div class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-gradient-to-br from-purple-100 to-pink-100 dark:from-purple-900/30 dark:to-pink-900/30 mb-6">
                                    <svg class="w-12 h-12 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                    </svg>
                                </div>
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">พร้อมดาวน์โหลดโมเดล</h3>
                                <p class="text-gray-600 dark:text-gray-400 mb-6">ขนาดโมเดล: <span x-text="getModelSize(selectedModel)"></span></p>
                                <button @click="downloadModel()"
                                        :disabled="!selectedModel"
                                        class="px-8 py-3 bg-gradient-to-r from-purple-500 to-pink-600 hover:from-purple-600 hover:to-pink-700 text-white font-semibold rounded-xl shadow-lg transition-all duration-200 transform hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed">
                                    ดาวน์โหลดโมเดล
                                </button>
                            </div>
                        </template>

                        <template x-if="downloadStarted && !downloadComplete">
                            <div>
                                <div class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-gradient-to-br from-purple-100 to-pink-100 dark:from-purple-900/30 dark:to-pink-900/30 mb-6">
                                    <svg class="w-12 h-12 text-purple-600 dark:text-purple-400 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                    </svg>
                                </div>
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">กำลังดาวน์โหลด...</h3>
                                <p class="text-gray-600 dark:text-gray-400 mb-6">กรุณารอสักครู่ การดาวน์โหลดอาจใช้เวลาหลายนาที</p>
                                <div class="max-w-md mx-auto">
                                    <div class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                        <div class="h-full bg-gradient-to-r from-purple-500 to-pink-600 transition-all duration-500" :style="`width: ${downloadProgress}%`"></div>
                                    </div>
                                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400" x-text="`${downloadProgress}%`"></p>
                                </div>
                            </div>
                        </template>

                        <template x-if="downloadComplete">
                            <div>
                                <div class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-gradient-to-br from-green-100 to-teal-100 dark:from-green-900/30 dark:to-teal-900/30 mb-6">
                                    <svg class="w-12 h-12 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">ดาวน์โหลดสำเร็จ!</h3>
                                <p class="text-gray-600 dark:text-gray-400">โมเดล <span x-text="selectedModel"></span> พร้อมใช้งาน</p>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Step 4: PostXAgent (Optional) --}}
            <div x-show="currentStep === 4" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-x-4" x-transition:enter-end="opacity-100 transform translate-x-0">
                <div class="p-8">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                        PostXAgent (ไม่บังคับ)
                    </h2>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        PostXAgent เป็น AI Manager ที่ช่วยจัดการหลาย AI Providers พร้อมกัน
                    </p>

                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-xl p-6 mb-6">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0">
                                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-300 mb-1">ข้อมูลเพิ่มเติม</h4>
                                <p class="text-sm text-blue-700 dark:text-blue-400">
                                    คุณสามารถข้ามขั้นตอนนี้และตั้งค่า PostXAgent ภายหลังได้ใน Dashboard
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-center gap-4">
                        <button @click="skipPostXAgent()"
                                class="px-6 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-xl transition-all duration-200">
                            ข้ามขั้นตอนนี้
                        </button>
                        <button @click="skipPostXAgent()"
                                class="px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-semibold rounded-xl shadow-lg transition-all duration-200"
                                title="สามารถตั้งค่าได้ภายหลังจาก Dashboard">
                            ข้ามและตั้งค่าภายหลัง
                        </button>
                    </div>
                </div>
            </div>

            {{-- Step 5: Complete --}}
            <div x-show="currentStep === 5" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-x-4" x-transition:enter-end="opacity-100 transform translate-x-0">
                <div class="p-8">
                    <div class="text-center py-12">
                        <div class="inline-flex items-center justify-center w-32 h-32 rounded-full bg-gradient-to-br from-green-100 to-teal-100 dark:from-green-900/30 dark:to-teal-900/30 mb-8">
                            <svg class="w-16 h-16 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">
                            ติดตั้งเสร็จสมบูรณ์!
                        </h2>
                        <p class="text-lg text-gray-600 dark:text-gray-400 mb-8">
                            Central AI พร้อมใช้งานแล้ว
                        </p>

                        <div class="max-w-md mx-auto mb-8 p-6 bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 rounded-xl border border-blue-200 dark:border-blue-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">สรุปการติดตั้ง</h3>
                            <div class="space-y-2 text-left">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Ollama:</span>
                                    <span class="text-sm font-semibold text-green-600 dark:text-green-400">ติดตั้งแล้ว</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">โมเดล:</span>
                                    <span class="text-sm font-semibold text-blue-600 dark:text-blue-400" x-text="selectedModel"></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">PostXAgent:</span>
                                    <span class="text-sm font-semibold text-gray-600 dark:text-gray-400">ไม่ได้ติดตั้ง</span>
                                </div>
                            </div>
                        </div>

                        {{-- แสดง error ถ้ามี --}}
                        <div x-show="completionError" x-cloak
                             class="max-w-md mx-auto mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-xl">
                            <p class="text-sm text-red-700 dark:text-red-300" x-text="completionError"></p>
                        </div>

                        <button @click="completeSetup()"
                                :disabled="completing"
                                class="px-8 py-4 bg-gradient-to-r from-green-500 to-teal-600 hover:from-green-600 hover:to-teal-700 text-white text-lg font-semibold rounded-xl shadow-lg transition-all duration-200 transform hover:scale-105 disabled:opacity-50">
                            <span x-show="!completing">ไปที่ Control Panel</span>
                            <span x-show="completing">กำลังบันทึก...</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Navigation Buttons --}}
            <div class="px-8 py-6 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <button @click="prevStep()"
                        x-show="currentStep > 1 && currentStep < 5"
                        class="px-6 py-2 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white font-medium transition-colors">
                    ← ย้อนกลับ
                </button>
                <div class="flex-1"></div>
                <button @click="nextStep()"
                        x-show="currentStep < 5"
                        :disabled="!canProceed()"
                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                    ถัดไป →
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function wizardManager() {
    return {
        currentStep: 1,
        steps: [
            { number: 1, title: 'ตรวจสอบระบบ' },
            { number: 2, title: 'ติดตั้ง Ollama' },
            { number: 3, title: 'ดาวน์โหลดโมเดล' },
            { number: 4, title: 'PostXAgent' },
            { number: 5, title: 'เสร็จสิ้น' }
        ],
        systemResources: @json($systemResources),
        recommendedModels: @json($recommendedModels),
        selectedModel: '{{ $setting->default_ollama_model }}',
        canExec: @json($systemResources['can_exec'] ?? false),
        installationStarted: false,
        installationComplete: false,
        installationProgress: 0,
        installationMessage: 'กำลังเตรียมการติดตั้ง...',
        installationError: null,
        verifying: false,
        downloadStarted: false,
        downloadComplete: false,
        downloadProgress: 0,
        downloadError: null,
        completing: false,
        completionError: null,

        init() {
            // เลือกโมเดลที่แนะนำอัตโนมัติ
            const recommended = this.recommendedModels.find(m => m.recommended);
            if (recommended && !this.selectedModel) {
                this.selectedModel = recommended.name;
            }
            // ถ้ายังไม่มีโมเดลที่เลือก เลือกตัวแรก
            if (!this.selectedModel && this.recommendedModels.length > 0) {
                this.selectedModel = this.recommendedModels[0].name;
            }
        },

        nextStep() {
            if (this.canProceed()) {
                this.currentStep++;
            }
        },

        prevStep() {
            if (this.currentStep > 1) {
                this.currentStep--;
            }
        },

        canProceed() {
            switch (this.currentStep) {
                case 1:
                    return !!this.selectedModel;
                case 2:
                    return this.installationComplete;
                case 3:
                    return this.downloadComplete;
                case 4:
                    return true;
                default:
                    return true;
            }
        },

        async installOllama() {
            this.installationStarted = true;
            this.installationProgress = 0;
            this.installationMessage = 'กำลังดาวน์โหลด Ollama...';
            this.installationError = null;

            try {
                // แสดง progress ระหว่างรอ API
                const progressInterval = setInterval(() => {
                    if (this.installationProgress < 90) {
                        this.installationProgress += 10;

                        if (this.installationProgress === 30) {
                            this.installationMessage = 'กำลังติดตั้ง Ollama...';
                        } else if (this.installationProgress === 60) {
                            this.installationMessage = 'กำลังตั้งค่าและเริ่ม Ollama Service...';
                        }
                    }
                }, 500);

                const response = await fetch('{{ route('admin.central-ai.ollama.install') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();
                clearInterval(progressInterval);

                // ตรวจสอบว่า API สำเร็จหรือไม่
                if (!response.ok || !data.success) {
                    const errorMsg = data.error || data.message || 'ไม่ทราบสาเหตุ';
                    this.installationProgress = 0;
                    this.installationMessage = '';
                    this.installationError = 'การติดตั้ง Ollama ล้มเหลว: ' + errorMsg;
                    this.installationStarted = false;
                    return;
                }

                this.installationProgress = 100;
                this.installationMessage = 'ติดตั้งเสร็จสมบูรณ์';

                setTimeout(() => {
                    this.installationComplete = true;
                }, 500);

            } catch (error) {
                console.error('Installation failed:', error);
                this.installationProgress = 0;
                this.installationMessage = '';
                this.installationError = 'การติดตั้ง Ollama ล้มเหลว: ไม่สามารถเชื่อมต่อกับ server ได้';
                this.installationStarted = false;
            }
        },

        async verifyOllamaInstallation() {
            this.verifying = true;
            this.installationError = null;

            try {
                // เรียก install endpoint เดิม - ถ้า Ollama ติดตั้งแล้วจะตรวจพบผ่าน HTTP API
                const response = await fetch('{{ route('admin.central-ai.ollama.install') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.installationComplete = true;
                } else {
                    const errorMsg = data.error || data.message || 'ไม่ทราบสาเหตุ';
                    // ถ้า error เป็นเรื่อง exec ให้แสดงข้อความที่เหมาะสม
                    if (errorMsg.includes('exec') || errorMsg.includes('disable')) {
                        this.installationError = 'ไม่พบ Ollama บนเซิร์ฟเวอร์ กรุณาติดตั้งผ่าน SSH ก่อน แล้วลองตรวจสอบอีกครั้ง';
                    } else {
                        this.installationError = 'ไม่พบ Ollama: ' + errorMsg;
                    }
                }
            } catch (error) {
                console.error('Verification failed:', error);
                this.installationError = 'ไม่สามารถเชื่อมต่อกับ server ได้ กรุณาลองใหม่';
            } finally {
                this.verifying = false;
            }
        },

        copyCommand(command) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(command).then(() => {
                    // แสดงผลว่าคัดลอกสำเร็จ (เปลี่ยนข้อความปุ่มชั่วคราว)
                    const btn = event.target;
                    const originalText = btn.textContent;
                    btn.textContent = 'คัดลอกแล้ว!';
                    setTimeout(() => { btn.textContent = originalText; }, 1500);
                });
            } else {
                // fallback สำหรับ browser ที่ไม่รองรับ clipboard API
                const textarea = document.createElement('textarea');
                textarea.value = command;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
            }
        },

        async downloadModel() {
            if (!this.selectedModel) return;

            this.downloadStarted = true;
            this.downloadProgress = 0;
            this.downloadError = null;

            try {
                // แสดง progress ระหว่างรอ (โมเดลอาจใช้เวลานาน)
                const progressInterval = setInterval(() => {
                    if (this.downloadProgress < 90) {
                        this.downloadProgress += 2;
                    }
                }, 1000);

                const response = await fetch('{{ route('admin.central-ai.ollama.download-model') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        model: this.selectedModel
                    })
                });

                const data = await response.json();
                clearInterval(progressInterval);

                // ตรวจสอบว่า API สำเร็จหรือไม่
                if (!response.ok || !data.success) {
                    const errorMsg = data.error || data.message || 'ไม่ทราบสาเหตุ';
                    this.downloadProgress = 0;
                    this.downloadError = 'การดาวน์โหลดล้มเหลว: ' + errorMsg;
                    this.downloadStarted = false;
                    return;
                }

                this.downloadProgress = 100;

                setTimeout(() => {
                    this.downloadComplete = true;
                }, 500);

            } catch (error) {
                console.error('Download failed:', error);
                this.downloadProgress = 0;
                this.downloadError = 'การดาวน์โหลดล้มเหลว: ไม่สามารถเชื่อมต่อกับ server ได้';
                this.downloadStarted = false;
            }
        },

        skipPostXAgent() {
            this.currentStep = 5;
        },

        async completeSetup() {
            this.completing = true;

            try {
                const response = await fetch('{{ route('admin.central-ai.setup.complete') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    window.location.href = '{{ route('admin.central-ai.ollama.index') }}';
                } else {
                    this.completionError = 'เกิดข้อผิดพลาด: ' + (data.error || data.message || 'ไม่ทราบสาเหตุ');
                    this.completing = false;
                }
            } catch (error) {
                console.error('Complete setup failed:', error);
                this.completionError = 'ไม่สามารถบันทึกการตั้งค่าได้ กรุณาลองใหม่อีกครั้ง';
                this.completing = false;
            }
        },

        getModelSize(modelName) {
            const model = this.recommendedModels.find(m => m.name === modelName);
            return model ? model.size : 'N/A';
        }
    }
}
</script>
@endpush
@endsection
