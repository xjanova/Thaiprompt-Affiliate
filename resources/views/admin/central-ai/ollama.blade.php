@extends('layouts.admin')

@section('title', 'Ollama Control Panel')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 py-8 px-4"
     x-data="ollamaControlPanel()"
     x-init="init()">

    {{-- Toast Notification --}}
    <div x-show="toast.show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         x-cloak
         class="fixed top-6 right-6 z-[9999] max-w-sm w-full">
        <div class="rounded-xl shadow-2xl border p-4 flex items-start gap-3"
             :class="{
                 'bg-green-50 border-green-200 dark:bg-green-900/30 dark:border-green-700': toast.type === 'success',
                 'bg-red-50 border-red-200 dark:bg-red-900/30 dark:border-red-700': toast.type === 'error',
                 'bg-blue-50 border-blue-200 dark:bg-blue-900/30 dark:border-blue-700': toast.type === 'info',
                 'bg-yellow-50 border-yellow-200 dark:bg-yellow-900/30 dark:border-yellow-700': toast.type === 'warning'
             }">
            <div class="flex-shrink-0 mt-0.5">
                <template x-if="toast.type === 'success'">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </template>
                <template x-if="toast.type === 'error'">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </template>
                <template x-if="toast.type === 'info'">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </template>
                <template x-if="toast.type === 'warning'">
                    <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </template>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium"
                   :class="{
                       'text-green-800 dark:text-green-200': toast.type === 'success',
                       'text-red-800 dark:text-red-200': toast.type === 'error',
                       'text-blue-800 dark:text-blue-200': toast.type === 'info',
                       'text-yellow-800 dark:text-yellow-200': toast.type === 'warning'
                   }"
                   x-text="toast.message"></p>
            </div>
            <button @click="toast.show = false" class="flex-shrink-0 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>

    {{-- Header --}}
    <div class="max-w-7xl mx-auto mb-8">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                    Ollama Control Panel
                </h1>
                <p class="text-gray-600 dark:text-gray-400">
                    จัดการ Ollama Service, โมเดล AI และทดสอบการทำงาน
                </p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.central-ai.dashboard') }}"
                   class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg shadow transition-all duration-200 text-sm">
                    &larr; Dashboard
                </a>
                <button @click="refreshStatus()"
                        :disabled="loading"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow transition-all duration-200 disabled:opacity-50 text-sm flex items-center gap-2">
                    <svg class="w-4 h-4" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <span x-text="loading ? 'กำลังโหลด...' : 'รีเฟรช'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- Service Status Banner --}}
    <div class="max-w-7xl mx-auto mb-8">
        <div class="rounded-2xl shadow-lg overflow-hidden transition-all duration-500"
             :class="{
                 'bg-gradient-to-r from-green-500 to-emerald-600': status === 'running',
                 'bg-gradient-to-r from-red-500 to-rose-600': status === 'stopped',
                 'bg-gradient-to-r from-yellow-500 to-amber-600': status === 'error',
                 'bg-gradient-to-r from-gray-400 to-gray-500': status === 'not_installed'
             }">
            <div class="p-6">
                <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 text-white">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                            <svg x-show="status === 'running'" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <svg x-show="status === 'stopped'" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/></svg>
                            <svg x-show="status === 'error'" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <svg x-show="status === 'not_installed'" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold" x-text="getStatusText(status)"></h2>
                            <div class="flex items-center gap-4 text-white/90 text-sm mt-1">
                                <span x-show="version">Version: <span x-text="version"></span></span>
                                <span>URL: <span x-text="url" class="font-mono"></span></span>
                                <span x-text="models.length + ' โมเดล'"></span>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button @click="startOllama()"
                                :disabled="status === 'running' || controlLoading"
                                class="px-4 py-2 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white font-semibold rounded-lg transition disabled:opacity-40 disabled:cursor-not-allowed text-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/></svg>
                            เริ่มต้น
                        </button>
                        <button @click="stopOllama()"
                                :disabled="status !== 'running' || controlLoading"
                                class="px-4 py-2 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white font-semibold rounded-lg transition disabled:opacity-40 disabled:cursor-not-allowed text-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><rect x="6" y="6" width="12" height="12" rx="1"/></svg>
                            หยุด
                        </button>
                        <button @click="restartOllama()"
                                :disabled="controlLoading"
                                class="px-4 py-2 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white font-semibold rounded-lg transition disabled:opacity-40 disabled:cursor-not-allowed text-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            รีสตาร์ท
                        </button>
                        <button x-show="status === 'not_installed'"
                                @click="installOllama()"
                                :disabled="controlLoading"
                                class="px-4 py-2 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white font-semibold rounded-lg transition disabled:opacity-40 disabled:cursor-not-allowed text-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            ติดตั้ง Ollama
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tab Navigation --}}
    <div class="max-w-7xl mx-auto mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-200 dark:border-gray-700 p-1.5">
            <nav class="flex flex-wrap gap-1">
                <button @click="activeTab = 'models'"
                        class="px-4 py-2.5 text-sm font-semibold rounded-lg transition-all duration-200 flex items-center gap-2"
                        :class="activeTab === 'models'
                            ? 'bg-blue-600 text-white shadow-md'
                            : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    โมเดล
                    <span class="px-1.5 py-0.5 text-xs rounded-full"
                          :class="activeTab === 'models' ? 'bg-white/20' : 'bg-gray-200 dark:bg-gray-600'"
                          x-text="models.length"></span>
                </button>
                <button @click="activeTab = 'chat'"
                        class="px-4 py-2.5 text-sm font-semibold rounded-lg transition-all duration-200 flex items-center gap-2"
                        :class="activeTab === 'chat'
                            ? 'bg-blue-600 text-white shadow-md'
                            : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                    ทดสอบ Chat
                </button>
                <button @click="activeTab = 'monitoring'"
                        class="px-4 py-2.5 text-sm font-semibold rounded-lg transition-all duration-200 flex items-center gap-2"
                        :class="activeTab === 'monitoring'
                            ? 'bg-blue-600 text-white shadow-md'
                            : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    ทรัพยากร
                </button>
                <button @click="activeTab = 'download'"
                        class="px-4 py-2.5 text-sm font-semibold rounded-lg transition-all duration-200 flex items-center gap-2"
                        :class="activeTab === 'download'
                            ? 'bg-blue-600 text-white shadow-md'
                            : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    ดาวน์โหลดโมเดล
                </button>
            </nav>
        </div>
    </div>

    {{-- ======================== TAB: Models ======================== --}}
    <div x-show="activeTab === 'models'" x-cloak class="max-w-7xl mx-auto space-y-6">

        {{-- Running Models (โมเดลที่โหลดอยู่ใน Memory) --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-3 rounded-full bg-green-500 animate-pulse"></div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">โมเดลที่กำลังทำงาน (ใน Memory)</h3>
                    </div>
                    <button @click="fetchRunningModels()"
                            :disabled="loadingRunning"
                            class="text-sm text-blue-600 dark:text-blue-400 hover:underline disabled:opacity-50">
                        <span x-text="loadingRunning ? 'กำลังโหลด...' : 'รีเฟรช'"></span>
                    </button>
                </div>
            </div>
            <div class="p-6">
                <template x-if="runningModels.length === 0">
                    <p class="text-center text-gray-500 dark:text-gray-400 py-4">ไม่มีโมเดลที่กำลังทำงานอยู่ใน memory</p>
                </template>
                <template x-if="runningModels.length > 0">
                    <div class="space-y-3">
                        <template x-for="rm in runningModels" :key="rm.name">
                            <div class="flex items-center justify-between p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                                <div class="flex items-center gap-3">
                                    <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div>
                                    <div>
                                        <span class="font-semibold text-gray-900 dark:text-white" x-text="rm.name"></span>
                                        <span class="text-sm text-gray-500 dark:text-gray-400 ml-2" x-text="rm.size_gb + ' GB'"></span>
                                    </div>
                                </div>
                                <span x-show="rm.expires_at" class="text-xs text-gray-500 dark:text-gray-400" x-text="'หมดอายุ: ' + formatDate(rm.expires_at)"></span>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>

        {{-- Installed Models --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">โมเดลที่ติดตั้ง</h3>
                    <button @click="activeTab = 'download'"
                            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        ดาวน์โหลดโมเดลใหม่
                    </button>
                </div>
            </div>
            <div class="p-6">
                <template x-if="models.length === 0">
                    <div class="text-center py-12">
                        <svg class="w-16 h-16 mx-auto text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">ยังไม่มีโมเดลที่ติดตั้ง</p>
                        <button @click="activeTab = 'download'"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition">
                            ดาวน์โหลดโมเดลแรก
                        </button>
                    </div>
                </template>

                <template x-if="models.length > 0">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">ชื่อโมเดล</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">ขนาด</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">แก้ไขล่าสุด</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">รายละเอียด</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <template x-for="model in models" :key="model.name || model">
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                        <td class="px-4 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-purple-500 to-blue-600 flex items-center justify-center">
                                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                                                </div>
                                                <span class="font-semibold text-gray-900 dark:text-white" x-text="getModelName(model)"></span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-400" x-text="formatSize(model.size)"></td>
                                        <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-400" x-text="formatDate(model.modified_at)"></td>
                                        <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-400">
                                            <span x-show="model.details" x-text="getModelDetails(model)"></span>
                                        </td>
                                        <td class="px-4 py-4 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <button @click="chatModel = getModelName(model); activeTab = 'chat'"
                                                        class="px-3 py-1.5 text-xs bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 hover:bg-blue-200 dark:hover:bg-blue-900/50 font-semibold rounded-lg transition">
                                                    ทดสอบ
                                                </button>
                                                <button @click="confirmDeleteModel(getModelName(model))"
                                                        :disabled="deletingModel === getModelName(model)"
                                                        class="px-3 py-1.5 text-xs bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 hover:bg-red-200 dark:hover:bg-red-900/50 font-semibold rounded-lg transition disabled:opacity-50">
                                                    <span x-show="deletingModel !== getModelName(model)">ลบ</span>
                                                    <span x-show="deletingModel === getModelName(model)">กำลังลบ...</span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- ======================== TAB: Chat Test ======================== --}}
    <div x-show="activeTab === 'chat'" x-cloak class="max-w-7xl mx-auto">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">ทดสอบ Chat กับ Ollama</h3>
                    <div class="flex items-center gap-3">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">โมเดล:</label>
                        <select x-model="chatModel"
                                class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500">
                            <option value="">-- เลือกโมเดล --</option>
                            <template x-for="m in models" :key="getModelName(m)">
                                <option :value="getModelName(m)" x-text="getModelName(m)"></option>
                            </template>
                        </select>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Temperature:</label>
                        <input type="number" x-model.number="chatTemperature" min="0" max="2" step="0.1"
                               class="w-20 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            {{-- Chat Messages --}}
            <div class="p-6 min-h-[400px] max-h-[600px] overflow-y-auto space-y-4" id="chatContainer">
                <template x-if="chatMessages.length === 0">
                    <div class="text-center py-16">
                        <svg class="w-16 h-16 mx-auto text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                        </svg>
                        <p class="text-gray-600 dark:text-gray-400 mb-2">พร้อมทดสอบ Chat กับ Ollama</p>
                        <p class="text-sm text-gray-500 dark:text-gray-500">เลือกโมเดลและพิมพ์ข้อความด้านล่าง</p>
                    </div>
                </template>

                <template x-for="(msg, idx) in chatMessages" :key="idx">
                    <div class="flex" :class="msg.role === 'user' ? 'justify-end' : 'justify-start'">
                        <div class="max-w-[80%] rounded-2xl px-4 py-3 shadow-sm"
                             :class="msg.role === 'user'
                                 ? 'bg-blue-600 text-white rounded-br-none'
                                 : 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white rounded-bl-none'">
                            <div class="whitespace-pre-wrap break-words text-sm" x-text="msg.content"></div>
                            <div x-show="msg.meta" class="mt-2 pt-2 border-t text-xs opacity-70"
                                 :class="msg.role === 'user' ? 'border-white/20' : 'border-gray-300 dark:border-gray-600'">
                                <span x-show="msg.meta?.model" x-text="'Model: ' + (msg.meta?.model || '')"></span>
                                <span x-show="msg.meta?.duration" x-text="' | ' + formatDuration(msg.meta?.duration)"></span>
                                <span x-show="msg.meta?.eval_count" x-text="' | ' + (msg.meta?.eval_count || 0) + ' tokens'"></span>
                            </div>
                        </div>
                    </div>
                </template>

                <template x-if="chatLoading">
                    <div class="flex justify-start">
                        <div class="bg-gray-100 dark:bg-gray-700 rounded-2xl rounded-bl-none px-4 py-3 shadow-sm">
                            <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>กำลังคิด...</span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Chat Input --}}
            <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                <div class="flex gap-3">
                    <textarea x-model="chatInput"
                              @keydown.enter.meta="sendChat()"
                              @keydown.ctrl.enter="sendChat()"
                              rows="2"
                              placeholder="พิมพ์ข้อความทดสอบ... (Ctrl+Enter เพื่อส่ง)"
                              class="flex-1 px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 resize-none text-sm"></textarea>
                    <div class="flex flex-col gap-2">
                        <button @click="sendChat()"
                                :disabled="!chatInput.trim() || !chatModel || chatLoading || status !== 'running'"
                                class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                            ส่ง
                        </button>
                        <button @click="chatMessages = []"
                                class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition">
                            ล้างแชท
                        </button>
                    </div>
                </div>
                <p x-show="status !== 'running'" class="text-xs text-red-500 mt-2">Ollama ไม่ได้ทำงาน กรุณาเริ่ม Ollama ก่อนทดสอบ Chat</p>
            </div>
        </div>
    </div>

    {{-- ======================== TAB: Monitoring ======================== --}}
    <div x-show="activeTab === 'monitoring'" x-cloak class="max-w-7xl mx-auto space-y-6">

        {{-- Resource Usage --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">การใช้ทรัพยากร Ollama</h3>
                    <button @click="fetchResourceUsage()"
                            :disabled="loadingResources"
                            class="text-sm text-blue-600 dark:text-blue-400 hover:underline disabled:opacity-50">
                        <span x-text="loadingResources ? 'กำลังโหลด...' : 'รีเฟรช'"></span>
                    </button>
                </div>
            </div>
            <div class="p-6">
                <template x-if="status !== 'running'">
                    <div class="text-center py-8">
                        <p class="text-gray-500 dark:text-gray-400">Ollama ไม่ได้ทำงาน - ไม่มีข้อมูลทรัพยากร</p>
                    </div>
                </template>
                <template x-if="status === 'running'">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        {{-- CPU --}}
                        <div class="p-5 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-xl border border-blue-200 dark:border-blue-800">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-10 h-10 rounded-lg bg-blue-500 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-blue-700 dark:text-blue-300">CPU</p>
                                </div>
                            </div>
                            <p class="text-3xl font-bold text-blue-900 dark:text-blue-100">
                                <span x-text="resourceUsage.ollama?.cpu_percent ?? '0'"></span><span class="text-lg">%</span>
                            </p>
                            <div class="mt-3 h-2 bg-blue-200 dark:bg-blue-800 rounded-full overflow-hidden">
                                <div class="h-full bg-blue-500 rounded-full transition-all duration-500"
                                     :style="'width: ' + Math.min(resourceUsage.ollama?.cpu_percent ?? 0, 100) + '%'"></div>
                            </div>
                        </div>

                        {{-- Memory --}}
                        <div class="p-5 bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-xl border border-green-200 dark:border-green-800">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-10 h-10 rounded-lg bg-green-500 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-green-700 dark:text-green-300">Memory</p>
                                </div>
                            </div>
                            <p class="text-3xl font-bold text-green-900 dark:text-green-100">
                                <span x-text="resourceUsage.ollama?.memory_gb ?? '0'"></span><span class="text-lg"> GB</span>
                            </p>
                            <div class="mt-3 h-2 bg-green-200 dark:bg-green-800 rounded-full overflow-hidden">
                                <div class="h-full bg-green-500 rounded-full transition-all duration-500"
                                     :style="'width: ' + Math.min(resourceUsage.ollama?.memory_percent ?? 0, 100) + '%'"></div>
                            </div>
                            <p class="text-xs text-green-600 dark:text-green-400 mt-1" x-text="(resourceUsage.ollama?.memory_percent ?? 0) + '% ของ RAM ทั้งหมด'"></p>
                        </div>

                        {{-- GPU --}}
                        <div class="p-5 bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 rounded-xl border border-purple-200 dark:border-purple-800">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-10 h-10 rounded-lg bg-purple-500 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-purple-700 dark:text-purple-300">GPU</p>
                                </div>
                            </div>
                            <template x-if="resourceUsage.ollama?.gpu">
                                <div>
                                    <template x-for="gpu in resourceUsage.ollama.gpu.gpus" :key="gpu.index">
                                        <div>
                                            <p class="text-sm font-medium text-purple-700 dark:text-purple-300" x-text="gpu.name"></p>
                                            <p class="text-3xl font-bold text-purple-900 dark:text-purple-100">
                                                <span x-text="gpu.utilization_percent"></span><span class="text-lg">%</span>
                                            </p>
                                            <p class="text-xs text-purple-600 dark:text-purple-400 mt-1" x-text="gpu.memory_used_gb + ' / ' + gpu.memory_total_gb + ' GB VRAM | ' + gpu.temperature_c + ' C'"></p>
                                        </div>
                                    </template>
                                </div>
                            </template>
                            <template x-if="!resourceUsage.ollama?.gpu">
                                <div>
                                    <p class="text-2xl font-bold text-purple-900 dark:text-purple-100">N/A</p>
                                    <p class="text-xs text-purple-600 dark:text-purple-400 mt-1">ไม่พบ GPU (NVIDIA)</p>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- System Resources --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">ทรัพยากรเซิร์ฟเวอร์</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">CPU Cores</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="resourceUsage.system?.cpu_cores ?? '{{ $setting->cpu_cores ?? 'N/A' }}'"></p>
                    </div>
                    <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">RAM ทั้งหมด</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            <span x-text="resourceUsage.system?.ram_gb ?? '{{ $setting->ram_gb ?? 'N/A' }}'"></span> GB
                        </p>
                    </div>
                    <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Disk ทั้งหมด</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            <span x-text="resourceUsage.system?.disk_gb ?? '{{ $setting->disk_gb ?? 'N/A' }}'"></span> GB
                        </p>
                    </div>
                    <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Disk ว่าง</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            <span x-text="resourceUsage.system?.disk_available_gb ?? '{{ $setting->disk_available_gb ?? 'N/A' }}'"></span> GB
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ======================== TAB: Download Models ======================== --}}
    <div x-show="activeTab === 'download'" x-cloak class="max-w-7xl mx-auto space-y-6">

        {{-- Custom Download --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">ดาวน์โหลดโมเดลจากชื่อ</h3>
            </div>
            <div class="p-6">
                <div class="flex gap-3">
                    <input type="text"
                           x-model="customModelName"
                           placeholder="ชื่อโมเดล เช่น llama3:8b, mistral:7b, codellama:7b"
                           @keydown.enter="downloadModel(customModelName)"
                           class="flex-1 px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 text-sm">
                    <button @click="downloadModel(customModelName)"
                            :disabled="!customModelName.trim() || downloadingModel || status !== 'running'"
                            class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                        <svg x-show="!downloadingModel" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        <svg x-show="downloadingModel" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                        <span x-text="downloadingModel ? 'กำลังดาวน์โหลด...' : 'ดาวน์โหลด'"></span>
                    </button>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                    ดูรายการโมเดลทั้งหมดได้ที่ <a href="https://ollama.com/library" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline">ollama.com/library</a>
                </p>
                <p x-show="status !== 'running'" class="text-xs text-red-500 mt-2">Ollama ไม่ได้ทำงาน กรุณาเริ่ม Ollama ก่อนดาวน์โหลดโมเดล</p>
            </div>
        </div>

        {{-- Recommended Models --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">โมเดลแนะนำตามทรัพยากรเซิร์ฟเวอร์</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">เลือกโมเดลที่เหมาะกับ RAM ของระบบ</p>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($recommendedModels as $model)
                    <div class="p-4 rounded-xl border-2 transition-all duration-200
                                {{ $model['recommended'] ? 'border-green-500 bg-green-50 dark:bg-green-900/20 shadow-md' : 'border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50' }}">
                        <div class="flex items-start justify-between mb-2">
                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-white">{{ $model['name'] }}</h4>
                                @if($model['recommended'])
                                <span class="inline-block mt-1 px-2 py-0.5 text-xs font-semibold bg-green-500 text-white rounded">แนะนำ</span>
                                @endif
                            </div>
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $model['size'] }}</span>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">{{ $model['description'] }}</p>
                        <button @click="downloadModel('{{ $model['name'] }}')"
                                :disabled="downloadingModel || status !== 'running'"
                                class="w-full px-3 py-2 text-sm font-semibold rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2
                                       {{ $model['recommended'] ? 'bg-green-600 hover:bg-green-700 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white' }}">
                            <span x-show="downloadingModel !== '{{ $model['name'] }}'">ดาวน์โหลด</span>
                            <span x-show="downloadingModel === '{{ $model['name'] }}'">กำลังดาวน์โหลด...</span>
                        </button>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Additional Models --}}
        @if(!empty($additionalModels))
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">โมเดลเพิ่มเติม</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">โมเดลอื่นๆ ที่สามารถดาวน์โหลดได้</p>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($additionalModels as $model)
                    <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50">
                        <div class="flex items-start justify-between mb-2">
                            <h4 class="font-semibold text-gray-900 dark:text-white">{{ $model['name'] }}</h4>
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $model['size'] }}</span>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">{{ $model['description'] }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-500 mb-3">RAM ขั้นต่ำ: {{ $model['min_ram'] }} GB</p>
                        <button @click="downloadModel('{{ $model['name'] }}')"
                                :disabled="downloadingModel || status !== 'running'"
                                class="w-full px-3 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed">
                            <span x-show="downloadingModel !== '{{ $model['name'] }}'">ดาวน์โหลด</span>
                            <span x-show="downloadingModel === '{{ $model['name'] }}'">กำลังดาวน์โหลด...</span>
                        </button>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- Delete Confirmation Modal --}}
    <div x-show="showDeleteModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         @keydown.escape.window="showDeleteModal = false">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 transition-opacity" @click="showDeleteModal = false"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full p-6"
                 x-transition>
                <div class="text-center">
                    <div class="w-16 h-16 mx-auto rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">ยืนยันการลบโมเดล</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-1">คุณต้องการลบโมเดล</p>
                    <p class="font-semibold text-red-600 dark:text-red-400 mb-4" x-text="deleteTargetModel"></p>
                    <p class="text-sm text-gray-500 dark:text-gray-500 mb-6">การลบจะไม่สามารถกู้คืนได้ ต้องดาวน์โหลดใหม่</p>
                </div>
                <div class="flex gap-3">
                    <button @click="showDeleteModal = false"
                            class="flex-1 px-4 py-2.5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 font-semibold rounded-lg transition border border-gray-300 dark:border-gray-600">
                        ยกเลิก
                    </button>
                    <button @click="deleteModel(deleteTargetModel); showDeleteModal = false"
                            class="flex-1 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition">
                        ลบโมเดล
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function ollamaControlPanel() {
    return {
        // สถานะหลัก
        loading: false,
        controlLoading: false,
        status: @json($setting->ollama_status),
        version: @json($setting->ollama_version),
        url: @json($setting->ollama_url),
        models: @json($setting->installed_models ?? []),

        // แท็บ
        activeTab: 'models',

        // ดาวน์โหลดโมเดล
        customModelName: '',
        downloadingModel: null,

        // ลบโมเดล
        deletingModel: null,
        showDeleteModal: false,
        deleteTargetModel: '',

        // Running models
        runningModels: [],
        loadingRunning: false,

        // ทรัพยากร
        resourceUsage: {},
        loadingResources: false,

        // Chat
        chatModel: '',
        chatInput: '',
        chatMessages: [],
        chatLoading: false,
        chatTemperature: 0.7,

        // Toast
        toast: { show: false, message: '', type: 'info' },
        toastTimeout: null,

        init() {
            // รีเฟรชสถานะเริ่มต้น
            this.refreshStatus();
            if (this.status === 'running') {
                this.fetchRunningModels();
                this.fetchResourceUsage();
            }

            // ตั้ง default model สำหรับ chat
            if (this.models.length > 0) {
                this.chatModel = this.getModelName(this.models[0]);
            }

            // Auto refresh ทุก 30 วินาที
            setInterval(() => {
                this.refreshStatus();
                if (this.status === 'running') {
                    this.fetchRunningModels();
                    if (this.activeTab === 'monitoring') {
                        this.fetchResourceUsage();
                    }
                }
            }, 30000);
        },

        // ========== Toast Notification ==========
        showNotification(message, type = 'info') {
            if (this.toastTimeout) clearTimeout(this.toastTimeout);
            this.toast = { show: true, message, type };
            this.toastTimeout = setTimeout(() => {
                this.toast.show = false;
            }, 5000);
        },

        // ========== Status ==========
        async refreshStatus() {
            if (this.loading) return;
            this.loading = true;

            try {
                const response = await fetch('{{ route("admin.central-ai.ollama.status") }}', {
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                const data = await response.json();

                if (data.success) {
                    this.status = data.data.status;
                    this.version = data.data.version;
                    this.models = data.data.models || [];

                    // อัพเดท default chat model ถ้ายังไม่ได้ตั้ง
                    if (!this.chatModel && this.models.length > 0) {
                        this.chatModel = this.getModelName(this.models[0]);
                    }
                }
            } catch (error) {
                console.error('Failed to refresh status:', error);
            } finally {
                this.loading = false;
            }
        },

        // ========== Service Control ==========
        async startOllama() {
            this.controlLoading = true;
            try {
                const response = await fetch('{{ route("admin.central-ai.ollama.start") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                const data = await response.json();
                if (data.success) {
                    this.status = 'running';
                    this.showNotification('เริ่ม Ollama สำเร็จ', 'success');
                    setTimeout(() => this.refreshStatus(), 2000);
                } else {
                    this.showNotification(data.message || 'ไม่สามารถเริ่ม Ollama ได้', 'error');
                }
            } catch (error) {
                this.showNotification('เกิดข้อผิดพลาด: ' + error.message, 'error');
            } finally {
                this.controlLoading = false;
            }
        },

        async stopOllama() {
            this.controlLoading = true;
            try {
                const response = await fetch('{{ route("admin.central-ai.ollama.stop") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                const data = await response.json();
                if (data.success) {
                    this.status = 'stopped';
                    this.runningModels = [];
                    this.showNotification('หยุด Ollama สำเร็จ', 'success');
                } else {
                    this.showNotification(data.message || 'ไม่สามารถหยุด Ollama ได้', 'error');
                }
            } catch (error) {
                this.showNotification('เกิดข้อผิดพลาด: ' + error.message, 'error');
            } finally {
                this.controlLoading = false;
            }
        },

        async restartOllama() {
            this.controlLoading = true;
            try {
                const response = await fetch('{{ route("admin.central-ai.ollama.restart") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                const data = await response.json();
                if (data.success) {
                    this.showNotification('รีสตาร์ท Ollama สำเร็จ', 'success');
                    setTimeout(() => this.refreshStatus(), 3000);
                } else {
                    this.showNotification(data.message || 'ไม่สามารถรีสตาร์ท Ollama ได้', 'error');
                }
            } catch (error) {
                this.showNotification('เกิดข้อผิดพลาด: ' + error.message, 'error');
            } finally {
                this.controlLoading = false;
            }
        },

        async installOllama() {
            this.controlLoading = true;
            this.showNotification('กำลังติดตั้ง Ollama... อาจใช้เวลาสักครู่', 'info');
            try {
                const response = await fetch('{{ route("admin.central-ai.ollama.install") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                const data = await response.json();
                if (data.success) {
                    this.showNotification('ติดตั้ง Ollama สำเร็จ', 'success');
                    await this.refreshStatus();
                } else {
                    this.showNotification(data.message || 'ไม่สามารถติดตั้ง Ollama ได้', 'error');
                }
            } catch (error) {
                this.showNotification('เกิดข้อผิดพลาด: ' + error.message, 'error');
            } finally {
                this.controlLoading = false;
            }
        },

        // ========== Model Management ==========
        async downloadModel(modelName) {
            if (!modelName || !modelName.trim()) return;
            modelName = modelName.trim();

            this.downloadingModel = modelName;
            this.showNotification(`กำลังดาวน์โหลดโมเดล ${modelName}... อาจใช้เวลานาน`, 'info');

            try {
                const response = await fetch('{{ route("admin.central-ai.ollama.download-model") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ model: modelName })
                });
                const data = await response.json();
                if (data.success) {
                    this.showNotification(`ดาวน์โหลดโมเดล ${modelName} สำเร็จ`, 'success');
                    if (data.data?.installed_models) {
                        this.models = data.data.installed_models;
                    } else {
                        await this.refreshStatus();
                    }
                    this.customModelName = '';
                } else {
                    this.showNotification(data.message || 'ไม่สามารถดาวน์โหลดโมเดลได้', 'error');
                }
            } catch (error) {
                this.showNotification('เกิดข้อผิดพลาด: ' + error.message, 'error');
            } finally {
                this.downloadingModel = null;
            }
        },

        confirmDeleteModel(modelName) {
            this.deleteTargetModel = modelName;
            this.showDeleteModal = true;
        },

        async deleteModel(modelName) {
            this.deletingModel = modelName;
            try {
                const response = await fetch('{{ route("admin.central-ai.ollama.delete-model") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ model: modelName })
                });
                const data = await response.json();
                if (data.success) {
                    this.showNotification(`ลบโมเดล ${modelName} สำเร็จ`, 'success');
                    if (data.data?.installed_models) {
                        this.models = data.data.installed_models;
                    } else {
                        await this.refreshStatus();
                    }
                } else {
                    this.showNotification(data.message || 'ไม่สามารถลบโมเดลได้', 'error');
                }
            } catch (error) {
                this.showNotification('เกิดข้อผิดพลาด: ' + error.message, 'error');
            } finally {
                this.deletingModel = null;
            }
        },

        // ========== Running Models ==========
        async fetchRunningModels() {
            if (this.status !== 'running') return;
            this.loadingRunning = true;
            try {
                const response = await fetch('{{ route("admin.central-ai.ollama.running-models") }}', {
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                const data = await response.json();
                if (data.success) {
                    this.runningModels = data.data || [];
                }
            } catch (error) {
                console.error('Failed to fetch running models:', error);
            } finally {
                this.loadingRunning = false;
            }
        },

        // ========== Resource Usage ==========
        async fetchResourceUsage() {
            if (this.status !== 'running') return;
            this.loadingResources = true;
            try {
                const response = await fetch('{{ route("admin.central-ai.ollama.resource-usage") }}', {
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                const data = await response.json();
                if (data.success) {
                    this.resourceUsage = data.data || {};
                }
            } catch (error) {
                console.error('Failed to fetch resource usage:', error);
            } finally {
                this.loadingResources = false;
            }
        },

        // ========== Chat ==========
        async sendChat() {
            if (!this.chatInput.trim() || !this.chatModel || this.chatLoading) return;
            if (this.status !== 'running') {
                this.showNotification('Ollama ไม่ได้ทำงาน กรุณาเริ่ม Ollama ก่อน', 'warning');
                return;
            }

            const prompt = this.chatInput.trim();
            this.chatMessages.push({ role: 'user', content: prompt });
            this.chatInput = '';
            this.chatLoading = true;

            // เลื่อนไปล่าง
            this.$nextTick(() => {
                const container = document.getElementById('chatContainer');
                if (container) container.scrollTop = container.scrollHeight;
            });

            try {
                const response = await fetch('{{ route("admin.central-ai.ollama.chat-test") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({
                        model: this.chatModel,
                        prompt: prompt,
                        temperature: this.chatTemperature
                    })
                });
                const data = await response.json();
                if (data.success) {
                    this.chatMessages.push({
                        role: 'assistant',
                        content: data.data.response,
                        meta: {
                            model: data.data.model,
                            duration: data.data.total_duration,
                            eval_count: data.data.eval_count
                        }
                    });
                } else {
                    this.chatMessages.push({
                        role: 'assistant',
                        content: 'Error: ' + (data.message || 'โมเดลไม่ตอบกลับ')
                    });
                }
            } catch (error) {
                this.chatMessages.push({
                    role: 'assistant',
                    content: 'Error: ' + error.message
                });
            } finally {
                this.chatLoading = false;
                this.$nextTick(() => {
                    const container = document.getElementById('chatContainer');
                    if (container) container.scrollTop = container.scrollHeight;
                });
            }
        },

        // ========== Utilities ==========
        getStatusText(status) {
            const map = {
                'running': 'กำลังทำงาน',
                'stopped': 'หยุดทำงาน',
                'error': 'เกิดข้อผิดพลาด',
                'not_installed': 'ไม่ได้ติดตั้ง'
            };
            return map[status] || status;
        },

        getModelName(model) {
            if (typeof model === 'string') return model;
            return model?.name || 'unknown';
        },

        getModelDetails(model) {
            if (!model?.details) return '';
            const d = model.details;
            const parts = [];
            if (d.family) parts.push(d.family);
            if (d.parameter_size) parts.push(d.parameter_size);
            if (d.quantization_level) parts.push(d.quantization_level);
            return parts.join(' | ');
        },

        formatSize(bytes) {
            if (!bytes || bytes === 0) return 'N/A';
            if (typeof bytes === 'string') return bytes;
            const gb = bytes / (1024 * 1024 * 1024);
            if (gb >= 1) return gb.toFixed(2) + ' GB';
            const mb = bytes / (1024 * 1024);
            return mb.toFixed(0) + ' MB';
        },

        formatDate(dateString) {
            if (!dateString) return 'N/A';
            try {
                return new Date(dateString).toLocaleDateString('th-TH', {
                    year: 'numeric', month: 'short', day: 'numeric',
                    hour: '2-digit', minute: '2-digit'
                });
            } catch {
                return dateString;
            }
        },

        formatDuration(nanoseconds) {
            if (!nanoseconds) return '';
            const seconds = nanoseconds / 1e9;
            if (seconds < 1) return (nanoseconds / 1e6).toFixed(0) + 'ms';
            if (seconds < 60) return seconds.toFixed(1) + ' วินาที';
            return (seconds / 60).toFixed(1) + ' นาที';
        }
    }
}
</script>
@endpush
@endsection
