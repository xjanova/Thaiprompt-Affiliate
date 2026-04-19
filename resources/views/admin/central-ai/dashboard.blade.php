@extends('layouts.admin-v3')

@section('title', 'Central AI Dashboard')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 py-8 px-4"
     x-data="dashboardManager()"
     x-init="init()">

    {{-- Header --}}
    <div class="max-w-7xl mx-auto mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                    Central AI Dashboard
                </h1>
                <p class="text-gray-600 dark:text-gray-400">
                    ระบบจัดการ Ollama และ AI Providers
                </p>
            </div>
            <div>
                <button @click="performHealthCheck()"
                        :disabled="healthChecking"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow transition-all duration-200 disabled:opacity-50">
                    <span x-show="!healthChecking">🔍 Health Check</span>
                    <span x-show="healthChecking">กำลังตรวจสอบ...</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Overall Status Banner --}}
    <div class="max-w-7xl mx-auto mb-8">
        <div class="p-6 rounded-2xl shadow-lg transition-all duration-300"
             :class="{
                 'bg-gradient-to-r from-green-500 to-teal-600': overallStatus === 'healthy',
                 'bg-gradient-to-r from-yellow-500 to-orange-600': overallStatus === 'partial',
                 'bg-gradient-to-r from-red-500 to-pink-600': overallStatus === 'unhealthy'
             }">
            <div class="flex items-center justify-between text-white">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 rounded-full bg-white/20 flex items-center justify-center backdrop-blur-sm">
                        <svg x-show="overallStatus === 'healthy'" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <svg x-show="overallStatus === 'partial'" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <svg x-show="overallStatus === 'unhealthy'" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold">
                            <span x-show="overallStatus === 'healthy'">ระบบทำงานปกติ</span>
                            <span x-show="overallStatus === 'partial'">ระบบทำงานบางส่วน</span>
                            <span x-show="overallStatus === 'unhealthy'">ระบบมีปัญหา</span>
                        </h2>
                        <p class="text-white/90 text-sm" x-text="'อัพเดทล่าสุด: ' + lastHealthCheck"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="max-w-7xl mx-auto mb-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {{-- Total Requests --}}
            <div class="p-6 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                        </svg>
                    </div>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">คำขอทั้งหมด</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white" x-text="statistics.total_requests.toLocaleString()"></p>
            </div>

            {{-- Success Rate --}}
            <div class="p-6 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">อัตราความสำเร็จ</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">
                    <span x-text="statistics.success_rate"></span>%
                </p>
            </div>

            {{-- Successful Requests --}}
            <div class="p-6 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-teal-500 to-teal-600 flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">สำเร็จ</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white" x-text="statistics.successful_requests.toLocaleString()"></p>
            </div>

            {{-- Failed Requests --}}
            <div class="p-6 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-red-500 to-red-600 flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">ล้มเหลว</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white" x-text="statistics.failed_requests.toLocaleString()"></p>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-8">
        {{-- Ollama Control Panel --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Ollama Service</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Local AI Engine</p>
                        </div>
                    </div>
                    <div>
                        <span class="px-3 py-1 text-xs font-semibold rounded-full"
                              :class="{
                                  'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': ollamaStatus === 'running',
                                  'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': ollamaStatus === 'stopped',
                                  'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400': ollamaStatus === 'error',
                                  'bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-400': ollamaStatus === 'not_installed'
                              }"
                              x-text="getStatusText(ollamaStatus)">
                        </span>
                    </div>
                </div>
            </div>

            <div class="p-6">
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">เวอร์ชัน:</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white" x-text="ollamaVersion || 'N/A'"></span>
                    </div>

                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">โมเดลที่ติดตั้ง:</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white" x-text="installedModels.length + ' โมเดล'"></span>
                    </div>

                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">URL:</span>
                        <span class="text-sm font-mono text-blue-600 dark:text-blue-400" x-text="ollamaUrl"></span>
                    </div>

                    <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                        <div class="grid grid-cols-3 gap-3">
                            <button @click="startOllama()"
                                    :disabled="ollamaStatus === 'running' || controlLoading"
                                    class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed">
                                Start
                            </button>
                            <button @click="stopOllama()"
                                    :disabled="ollamaStatus !== 'running' || controlLoading"
                                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed">
                                Stop
                            </button>
                            <button @click="restartOllama()"
                                    :disabled="controlLoading"
                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed">
                                Restart
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- PostXAgent Status --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">PostXAgent</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">AI Manager (.NET)</p>
                        </div>
                    </div>
                    <div>
                        <span class="px-3 py-1 text-xs font-semibold rounded-full"
                              :class="{
                                  'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': postxagentStatus === 'running',
                                  'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': postxagentStatus === 'stopped' || postxagentStatus === 'error',
                                  'bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-400': postxagentStatus === 'not_configured'
                              }"
                              x-text="getStatusText(postxagentStatus)">
                        </span>
                    </div>
                </div>
            </div>

            <div class="p-6">
                <template x-if="postxagentStatus === 'not_configured'">
                    <div class="text-center py-8">
                        <svg class="w-16 h-16 mx-auto text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">ยังไม่ได้ตั้งค่า PostXAgent</p>
                        <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition">
                            ตั้งค่า PostXAgent
                        </button>
                    </div>
                </template>

                <template x-if="postxagentStatus !== 'not_configured'">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">URL:</span>
                            <span class="text-sm font-mono text-blue-600 dark:text-blue-400" x-text="postxagentUrl"></span>
                        </div>

                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Providers:</span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white" x-text="availableProviders.length + ' providers'"></span>
                        </div>

                        <div x-show="availableProviders.length > 0" class="pt-4 border-t border-gray-200 dark:border-gray-700">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Providers ที่พร้อมใช้งาน:</p>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="provider in availableProviders" :key="provider">
                                    <span class="px-3 py-1 text-xs font-semibold bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 rounded-full capitalize"
                                          x-text="provider"></span>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- System Resources --}}
    <div class="max-w-7xl mx-auto mt-8">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">ทรัพยากรระบบ</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="text-center">
                        <div class="w-16 h-16 mx-auto rounded-full bg-gradient-to-br from-blue-100 to-blue-200 dark:from-blue-900/30 dark:to-blue-800/30 flex items-center justify-center mb-3">
                            <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                            </svg>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">CPU Cores</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="systemResources.cpu_cores || 'N/A'"></p>
                    </div>

                    <div class="text-center">
                        <div class="w-16 h-16 mx-auto rounded-full bg-gradient-to-br from-green-100 to-green-200 dark:from-green-900/30 dark:to-green-800/30 flex items-center justify-center mb-3">
                            <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                            </svg>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">RAM</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="(systemResources.ram_gb || 'N/A') + ' GB'"></p>
                    </div>

                    <div class="text-center">
                        <div class="w-16 h-16 mx-auto rounded-full bg-gradient-to-br from-purple-100 to-purple-200 dark:from-purple-900/30 dark:to-purple-800/30 flex items-center justify-center mb-3">
                            <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path>
                            </svg>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Disk Total</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="(systemResources.disk_gb || 'N/A') + ' GB'"></p>
                    </div>

                    <div class="text-center">
                        <div class="w-16 h-16 mx-auto rounded-full bg-gradient-to-br from-orange-100 to-orange-200 dark:from-orange-900/30 dark:to-orange-800/30 flex items-center justify-center mb-3">
                            <svg class="w-8 h-8 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path>
                            </svg>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Disk Available</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="(systemResources.disk_available_gb || 'N/A') + ' GB'"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function dashboardManager() {
    return {
        // Health Status
        overallStatus: @json($healthStatus['overall_status'] ?? 'unknown'),
        lastHealthCheck: @json($setting->last_health_check_at?->format('d/m/Y H:i') ?? 'ไม่เคยตรวจสอบ'),
        healthChecking: false,

        // Statistics
        statistics: @json($statistics),

        // Ollama
        ollamaStatus: @json($setting->ollama_status),
        ollamaVersion: @json($setting->ollama_version),
        ollamaUrl: @json($setting->ollama_url),
        installedModels: @json($setting->installed_models ?? []),
        controlLoading: false,

        // PostXAgent
        postxagentStatus: @json($setting->postxagent_status),
        postxagentUrl: @json($setting->postxagent_url),
        postxagentEnabled: @json($setting->postxagent_enabled),
        availableProviders: @json($healthStatus['postxagent']['providers'] ?? []),

        // System Resources
        systemResources: {
            cpu_cores: @json($setting->cpu_cores),
            ram_gb: @json($setting->ram_gb),
            disk_gb: @json($setting->disk_gb),
            disk_available_gb: @json($setting->disk_available_gb),
            os_type: @json($setting->os_type)
        },

        init() {
            // Auto refresh every 30 seconds
            setInterval(() => {
                this.performHealthCheck();
            }, 30000);
        },

        async performHealthCheck() {
            if (this.healthChecking) return;

            this.healthChecking = true;

            try {
                const response = await fetch('{{ route('admin.central-ai.health-check') }}', {
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.updateStatus(data.data);
                }
            } catch (error) {
                console.error('Health check failed:', error);
            } finally {
                this.healthChecking = false;
            }
        },

        updateStatus(healthData) {
            this.overallStatus = healthData.overall_status;
            this.lastHealthCheck = new Date().toLocaleString('th-TH');

            // Update Ollama status
            if (healthData.ollama) {
                this.ollamaStatus = healthData.ollama.status;
                this.ollamaVersion = healthData.ollama.version;
                this.installedModels = healthData.ollama.models || [];
            }

            // Update PostXAgent status
            if (healthData.postxagent) {
                this.postxagentStatus = healthData.postxagent.status;
                this.availableProviders = healthData.postxagent.providers || [];
            }
        },

        async startOllama() {
            this.controlLoading = true;

            try {
                const response = await fetch('{{ route('admin.central-ai.ollama.start') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.ollamaStatus = 'running';
                    alert('เริ่ม Ollama สำเร็จ');
                } else {
                    alert('ไม่สามารถเริ่ม Ollama ได้: ' + data.message);
                }
            } catch (error) {
                console.error('Start Ollama failed:', error);
                alert('เกิดข้อผิดพลาด: ' + error.message);
            } finally {
                this.controlLoading = false;
            }
        },

        async stopOllama() {
            this.controlLoading = true;

            try {
                const response = await fetch('{{ route('admin.central-ai.ollama.stop') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.ollamaStatus = 'stopped';
                    alert('หยุด Ollama สำเร็จ');
                } else if (data.manual_required) {
                    const commands = (data.manual_commands || []).join('\n');
                    alert('ไม่สามารถหยุดอัตโนมัติได้ (exec ถูก disable)\n\nกรุณารันคำสั่งนี้ผ่าน SSH:\n\n' + commands);
                } else {
                    alert('ไม่สามารถหยุด Ollama ได้: ' + (data.message || 'ไม่ทราบสาเหตุ'));
                }
            } catch (error) {
                console.error('Stop Ollama failed:', error);
                alert('เกิดข้อผิดพลาด: ' + error.message);
            } finally {
                this.controlLoading = false;
            }
        },

        async restartOllama() {
            this.controlLoading = true;

            try {
                const response = await fetch('{{ route('admin.central-ai.ollama.restart') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    alert('รีสตาร์ท Ollama สำเร็จ');
                    await this.performHealthCheck();
                } else {
                    alert('ไม่สามารถรีสตาร์ท Ollama ได้: ' + data.message);
                }
            } catch (error) {
                console.error('Restart Ollama failed:', error);
                alert('เกิดข้อผิดพลาด: ' + error.message);
            } finally {
                this.controlLoading = false;
            }
        },

        getStatusText(status) {
            const statusMap = {
                'running': 'กำลังทำงาน',
                'stopped': 'หยุดทำงาน',
                'error': 'เกิดข้อผิดพลาด',
                'not_installed': 'ไม่ได้ติดตั้ง',
                'not_configured': 'ยังไม่ได้ตั้งค่า'
            };

            return statusMap[status] || status;
        }
    }
}
</script>
@endpush
@endsection
