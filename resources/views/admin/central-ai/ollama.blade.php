@extends('layouts.admin')

@section('title', 'Ollama Management')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 py-8 px-4"
     x-data="ollamaManager()"
     x-init="init()">

    {{-- Header --}}
    <div class="max-w-7xl mx-auto mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                    🤖 Ollama Management
                </h1>
                <p class="text-gray-600 dark:text-gray-400">
                    จัดการ Ollama Service และ AI Models
                </p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.central-ai.dashboard') }}"
                   class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg shadow transition-all duration-200">
                    ← กลับไป Dashboard
                </a>
                <button @click="refreshStatus()"
                        :disabled="loading"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow transition-all duration-200 disabled:opacity-50">
                    <span x-show="!loading">🔄 รีเฟรช</span>
                    <span x-show="loading">กำลังโหลด...</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Ollama Status Card --}}
    <div class="max-w-7xl mx-auto mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-purple-500/10 to-blue-500/10">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-xl bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center shadow-lg">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Ollama Service</h2>
                            <p class="text-gray-600 dark:text-gray-400">Local AI Engine for LLMs</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="px-4 py-2 text-sm font-semibold rounded-full"
                              :class="{
                                  'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': status === 'running',
                                  'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': status === 'stopped',
                                  'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400': status === 'error',
                                  'bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-400': status === 'not_installed'
                              }"
                              x-text="getStatusText(status)">
                        </span>
                    </div>
                </div>
            </div>

            <div class="p-6">
                {{-- Service Info --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">เวอร์ชัน</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white" x-text="version || 'N/A'"></p>
                    </div>
                    <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">URL</p>
                        <p class="text-xl font-mono text-blue-600 dark:text-blue-400" x-text="url"></p>
                    </div>
                    <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">โมเดลที่ติดตั้ง</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white" x-text="models.length + ' โมเดล'"></p>
                    </div>
                </div>

                {{-- Service Controls --}}
                <div class="flex flex-wrap gap-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <button @click="startOllama()"
                            :disabled="status === 'running' || controlLoading"
                            class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        เริ่มต้น
                    </button>
                    <button @click="stopOllama()"
                            :disabled="status !== 'running' || controlLoading"
                            class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"></path>
                        </svg>
                        หยุด
                    </button>
                    <button @click="restartOllama()"
                            :disabled="controlLoading"
                            class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        รีสตาร์ท
                    </button>
                    <button x-show="status === 'not_installed'"
                            @click="installOllama()"
                            :disabled="controlLoading"
                            class="px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        ติดตั้ง Ollama
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Installed Models --}}
    <div class="max-w-7xl mx-auto mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">📦 โมเดลที่ติดตั้ง</h3>
                    <button @click="showDownloadModal = true"
                            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg transition">
                        + ดาวน์โหลดโมเดลใหม่
                    </button>
                </div>
            </div>
            <div class="p-6">
                <template x-if="models.length === 0">
                    <div class="text-center py-12">
                        <svg class="w-16 h-16 mx-auto text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                        </svg>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">ยังไม่มีโมเดลที่ติดตั้ง</p>
                        <button @click="showDownloadModal = true"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition">
                            ดาวน์โหลดโมเดลแรก
                        </button>
                    </div>
                </template>

                <template x-if="models.length > 0">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <template x-for="model in models" :key="model.name">
                            <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600">
                                <div class="flex items-start justify-between mb-3">
                                    <div>
                                        <h4 class="font-semibold text-gray-900 dark:text-white" x-text="model.name"></h4>
                                        <p class="text-sm text-gray-500 dark:text-gray-400" x-text="formatSize(model.size)"></p>
                                    </div>
                                    <span class="px-2 py-1 text-xs font-semibold bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 rounded">
                                        AI Model
                                    </span>
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    <span x-text="'Modified: ' + formatDate(model.modified_at)"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Recommended Models --}}
    <div class="max-w-7xl mx-auto">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">💡 โมเดลแนะนำ</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">เลือกโมเดลที่เหมาะกับระบบของคุณ</p>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($recommendedModels as $model)
                    <div class="p-4 rounded-lg border-2 transition-all duration-200
                                {{ $model['recommended'] ? 'border-green-500 bg-green-50 dark:bg-green-900/20' : 'border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50' }}">
                        <div class="flex items-start justify-between mb-2">
                            <h4 class="font-semibold text-gray-900 dark:text-white">{{ $model['name'] }}</h4>
                            @if($model['recommended'])
                            <span class="px-2 py-1 text-xs font-semibold bg-green-500 text-white rounded">แนะนำ</span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">{{ $model['description'] }}</p>
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $model['size'] }}</span>
                            <button @click="downloadModel('{{ $model['name'] }}')"
                                    :disabled="downloadingModel"
                                    class="px-3 py-1 text-sm bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded transition disabled:opacity-50">
                                <span x-show="downloadingModel !== '{{ $model['name'] }}'">ดาวน์โหลด</span>
                                <span x-show="downloadingModel === '{{ $model['name'] }}'">กำลังโหลด...</span>
                            </button>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Download Modal --}}
    <div x-show="showDownloadModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         @keydown.escape.window="showDownloadModal = false">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 transition-opacity"
                 @click="showDownloadModal = false"></div>

            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ดาวน์โหลดโมเดลใหม่</h3>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ชื่อโมเดล</label>
                    <input type="text"
                           x-model="customModelName"
                           placeholder="เช่น llama3:8b, mistral:7b"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ใส่ชื่อโมเดลจาก Ollama library</p>
                </div>

                <div class="flex justify-end gap-3">
                    <button @click="showDownloadModal = false"
                            class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                        ยกเลิก
                    </button>
                    <button @click="downloadModel(customModelName); showDownloadModal = false"
                            :disabled="!customModelName || downloadingModel"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition disabled:opacity-50">
                        ดาวน์โหลด
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function ollamaManager() {
    return {
        // State
        loading: false,
        controlLoading: false,
        status: @json($setting->ollama_status),
        version: @json($setting->ollama_version),
        url: @json($setting->ollama_url),
        models: @json($setting->installed_models ?? []),

        // Download modal
        showDownloadModal: false,
        customModelName: '',
        downloadingModel: null,

        init() {
            // รีเฟรชสถานะทุก 30 วินาที
            setInterval(() => {
                this.refreshStatus();
            }, 30000);
        },

        async refreshStatus() {
            if (this.loading) return;
            this.loading = true;

            try {
                const response = await fetch('{{ route('admin.central-ai.ollama.status') }}', {
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.status = data.data.status;
                    this.version = data.data.version;
                    this.models = data.data.models || [];
                }
            } catch (error) {
                console.error('Failed to refresh status:', error);
            } finally {
                this.loading = false;
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
                    this.status = 'running';
                    this.showNotification('เริ่ม Ollama สำเร็จ', 'success');
                } else {
                    this.showNotification('ไม่สามารถเริ่ม Ollama ได้: ' + data.message, 'error');
                }
            } catch (error) {
                console.error('Start Ollama failed:', error);
                this.showNotification('เกิดข้อผิดพลาด: ' + error.message, 'error');
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
                    this.status = 'stopped';
                    this.showNotification('หยุด Ollama สำเร็จ', 'success');
                } else {
                    this.showNotification('ไม่สามารถหยุด Ollama ได้: ' + data.message, 'error');
                }
            } catch (error) {
                console.error('Stop Ollama failed:', error);
                this.showNotification('เกิดข้อผิดพลาด: ' + error.message, 'error');
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
                    this.showNotification('รีสตาร์ท Ollama สำเร็จ', 'success');
                    await this.refreshStatus();
                } else {
                    this.showNotification('ไม่สามารถรีสตาร์ท Ollama ได้: ' + data.message, 'error');
                }
            } catch (error) {
                console.error('Restart Ollama failed:', error);
                this.showNotification('เกิดข้อผิดพลาด: ' + error.message, 'error');
            } finally {
                this.controlLoading = false;
            }
        },

        async installOllama() {
            this.controlLoading = true;

            try {
                const response = await fetch('{{ route('admin.central-ai.ollama.install') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.showNotification('ติดตั้ง Ollama สำเร็จ', 'success');
                    await this.refreshStatus();
                } else {
                    this.showNotification('ไม่สามารถติดตั้ง Ollama ได้: ' + data.message, 'error');
                }
            } catch (error) {
                console.error('Install Ollama failed:', error);
                this.showNotification('เกิดข้อผิดพลาด: ' + error.message, 'error');
            } finally {
                this.controlLoading = false;
            }
        },

        async downloadModel(modelName) {
            if (!modelName) return;

            this.downloadingModel = modelName;

            try {
                const response = await fetch('{{ route('admin.central-ai.ollama.download-model') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ model: modelName })
                });

                const data = await response.json();

                if (data.success) {
                    this.showNotification(`ดาวน์โหลดโมเดล ${modelName} สำเร็จ`, 'success');
                    this.models = data.data.installed_models || [];
                    this.customModelName = '';
                } else {
                    this.showNotification('ไม่สามารถดาวน์โหลดโมเดลได้: ' + data.message, 'error');
                }
            } catch (error) {
                console.error('Download model failed:', error);
                this.showNotification('เกิดข้อผิดพลาด: ' + error.message, 'error');
            } finally {
                this.downloadingModel = null;
            }
        },

        getStatusText(status) {
            const statusMap = {
                'running': 'กำลังทำงาน',
                'stopped': 'หยุดทำงาน',
                'error': 'เกิดข้อผิดพลาด',
                'not_installed': 'ไม่ได้ติดตั้ง'
            };
            return statusMap[status] || status;
        },

        formatSize(bytes) {
            if (!bytes) return 'N/A';
            const gb = bytes / (1024 * 1024 * 1024);
            return gb.toFixed(2) + ' GB';
        },

        formatDate(dateString) {
            if (!dateString) return 'N/A';
            return new Date(dateString).toLocaleDateString('th-TH');
        },

        showNotification(message, type) {
            // ใช้ alert ชั่วคราว (ควรเปลี่ยนเป็น toast notification)
            alert(message);
        }
    }
}
</script>
@endpush
@endsection
