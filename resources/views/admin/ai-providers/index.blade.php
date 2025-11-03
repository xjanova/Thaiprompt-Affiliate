@extends('layouts.admin')
@section('title', 'จัดการ AI Providers')

@push('styles')
<style>
    .provider-card {
        transition: all 0.3s ease;
        border-left: 4px solid transparent;
    }
    .provider-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
    .provider-card.cloud {
        border-left-color: #3b82f6;
    }
    .provider-card.local {
        border-left-color: #10b981;
    }
    .stat-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 12px;
        transition: transform 0.3s ease;
    }
    .stat-card:hover {
        transform: scale(1.05);
    }
    .model-badge {
        display: inline-block;
        margin: 4px;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }
    .model-badge.active {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
    }
    .model-badge.inactive {
        background: #e5e7eb;
        color: #6b7280;
    }
    .status-indicator {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 6px;
        animation: pulse 2s ease-in-out infinite;
    }
    .status-indicator.online {
        background-color: #10b981;
        box-shadow: 0 0 10px rgba(16, 185, 129, 0.5);
    }
    .status-indicator.offline {
        background-color: #ef4444;
        box-shadow: 0 0 10px rgba(239, 68, 68, 0.5);
    }
    @keyframes pulse {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: 0.5;
        }
    }
    .config-modal {
        max-width: 600px;
    }
    .toast {
        position: fixed;
        top: 20px;
        right: 20px;
        min-width: 300px;
        z-index: 9999;
        animation: slideInRight 0.3s ease;
    }
    @keyframes slideInRight {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    .local-ai-panel {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 24px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4" x-data="aiProvidersManager()">

    <!-- Toast Notification Container -->
    <div id="toast-container"></div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="stat-card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90">Total Providers</p>
                    <h3 class="text-3xl font-bold mt-2">{{ $providers->count() }}</h3>
                </div>
                <div class="text-4xl opacity-80">
                    <i class="fas fa-server"></i>
                </div>
            </div>
        </div>

        <div class="stat-card p-6" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90">Active Providers</p>
                    <h3 class="text-3xl font-bold mt-2">{{ $providers->where('is_active', true)->count() }}</h3>
                </div>
                <div class="text-4xl opacity-80">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>

        <div class="stat-card p-6" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90">Total Models</p>
                    <h3 class="text-3xl font-bold mt-2">{{ $providers->sum(fn($p) => $p->models->count()) }}</h3>
                </div>
                <div class="text-4xl opacity-80">
                    <i class="fas fa-brain"></i>
                </div>
            </div>
        </div>

        <div class="stat-card p-6" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90">Active Models</p>
                    <h3 class="text-3xl font-bold mt-2">{{ $providers->sum(fn($p) => $p->models->where('is_active', true)->count()) }}</h3>
                </div>
                <div class="text-4xl opacity-80">
                    <i class="fas fa-rocket"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Local AI Status Panel -->
    @if($localAiStatus)
    <div class="local-ai-panel mb-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold flex items-center">
                <i class="fas fa-microchip mr-3"></i>
                Local AI Status (Ollama)
            </h3>
            @if($localAiStatus['running'])
                <span class="bg-green-500 px-4 py-2 rounded-full text-sm font-semibold">
                    <i class="fas fa-circle animate-pulse mr-2"></i>Running
                </span>
            @else
                <span class="bg-red-500 px-4 py-2 rounded-full text-sm font-semibold">
                    <i class="fas fa-circle mr-2"></i>Stopped
                </span>
            @endif
        </div>

        @if($localAiStatus['running'])
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white bg-opacity-20 rounded-lg p-4">
                <p class="text-sm opacity-90">Endpoint</p>
                <p class="font-semibold mt-1">{{ $localAiStatus['endpoint'] }}</p>
            </div>
            <div class="bg-white bg-opacity-20 rounded-lg p-4">
                <p class="text-sm opacity-90">Uptime</p>
                <p class="font-semibold mt-1">{{ $localAiStatus['uptime'] }}</p>
            </div>
            <div class="bg-white bg-opacity-20 rounded-lg p-4">
                <p class="text-sm opacity-90">Loaded Models</p>
                <p class="font-semibold mt-1">{{ $localAiStatus['loaded_models'] ?? 0 }}</p>
            </div>
        </div>
        @endif

        <div class="flex gap-2 mt-4">
            @if($localAiStatus['running'])
                <button @click="stopLocalAi" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded-lg text-sm font-semibold transition-colors">
                    <i class="fas fa-stop mr-2"></i>หยุด
                </button>
                <button @click="restartLocalAi" class="bg-yellow-500 hover:bg-yellow-600 px-4 py-2 rounded-lg text-sm font-semibold transition-colors">
                    <i class="fas fa-redo mr-2"></i>Restart
                </button>
            @else
                <button @click="startLocalAi" class="bg-green-500 hover:bg-green-600 px-4 py-2 rounded-lg text-sm font-semibold transition-colors">
                    <i class="fas fa-play mr-2"></i>เริ่มต้น
                </button>
            @endif
        </div>
    </div>
    @endif

    <!-- Providers Grid -->
    <div class="grid grid-cols-1 gap-6">
        @foreach($providers as $provider)
        <div class="provider-card {{ $provider->provider_type }} bg-white rounded-xl shadow-md overflow-hidden">
            <div class="p-6">
                <!-- Provider Header -->
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <div class="w-16 h-16 rounded-full flex items-center justify-center text-2xl mr-4"
                             style="background: linear-gradient(135deg, {{ $provider->provider_type === 'cloud' ? '#3b82f6, #1d4ed8' : '#10b981, #059669' }});">
                            @if($provider->provider_type === 'cloud')
                                <i class="fas fa-cloud text-white"></i>
                            @else
                                <i class="fas fa-server text-white"></i>
                            @endif
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-gray-800 flex items-center">
                                {{ $provider->display_name }}
                                <span class="ml-3 text-xs px-3 py-1 rounded-full font-semibold"
                                      style="background: {{ $provider->provider_type === 'cloud' ? '#dbeafe' : '#d1fae5' }}; color: {{ $provider->provider_type === 'cloud' ? '#1e40af' : '#065f46' }};">
                                    {{ strtoupper($provider->provider_type) }}
                                </span>
                            </h3>
                            <p class="text-sm text-gray-600 mt-1">
                                <i class="fas fa-brain mr-2"></i>{{ $provider->models->count() }} models
                                <span class="mx-2">•</span>
                                <i class="fas fa-check-circle mr-2"></i>{{ $provider->models->where('is_active', true)->count() }} active
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <!-- Status Toggle -->
                        <button @click="toggleProvider({{ $provider->id }})"
                                class="px-4 py-2 rounded-lg font-semibold text-sm transition-all"
                                :class="providers[{{ $provider->id }}]?.is_active ? 'bg-green-500 text-white hover:bg-green-600' : 'bg-gray-300 text-gray-700 hover:bg-gray-400'">
                            <span x-show="providers[{{ $provider->id }}]?.is_active">
                                <i class="fas fa-toggle-on mr-2"></i>เปิดใช้งาน
                            </span>
                            <span x-show="!providers[{{ $provider->id }}]?.is_active">
                                <i class="fas fa-toggle-off mr-2"></i>ปิดใช้งาน
                            </span>
                        </button>

                        <!-- Test Connection -->
                        <button @click="testConnection({{ $provider->id }})"
                                class="px-4 py-2 bg-blue-500 text-white rounded-lg font-semibold text-sm hover:bg-blue-600 transition-colors">
                            <i class="fas fa-plug mr-2"></i>ทดสอบ
                        </button>

                        <!-- Config Button -->
                        <button @click="openConfigModal({{ $provider->id }})"
                                class="px-4 py-2 bg-purple-500 text-white rounded-lg font-semibold text-sm hover:bg-purple-600 transition-colors">
                            <i class="fas fa-cog mr-2"></i>ตั้งค่า
                        </button>

                        <!-- Expand/Collapse -->
                        <button @click="toggleModels({{ $provider->id }})"
                                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg font-semibold text-sm hover:bg-gray-300 transition-colors">
                            <i class="fas" :class="expandedProviders.includes({{ $provider->id }}) ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                        </button>
                    </div>
                </div>

                <!-- Provider Details -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-xs text-gray-600 mb-1">Endpoint</p>
                        <p class="text-sm font-semibold text-gray-800 break-all">
                            {{ $provider->api_endpoint ?? 'N/A' }}
                        </p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-xs text-gray-600 mb-1">API Key Status</p>
                        <p class="text-sm font-semibold">
                            @if(isset($provider->config['api_key']) && !empty($provider->config['api_key']))
                                <span class="text-green-600"><i class="fas fa-check-circle mr-1"></i>Configured</span>
                            @else
                                <span class="text-red-600"><i class="fas fa-times-circle mr-1"></i>Not Set</span>
                            @endif
                        </p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-xs text-gray-600 mb-1">Last Updated</p>
                        <p class="text-sm font-semibold text-gray-800">
                            {{ $provider->updated_at->diffForHumans() }}
                        </p>
                    </div>
                </div>

                <!-- Models Section (Expandable) -->
                <div x-show="expandedProviders.includes({{ $provider->id }})"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 transform -translate-y-4"
                     x-transition:enter-end="opacity-100 transform translate-y-0"
                     class="mt-4 border-t pt-4">
                    <h4 class="font-semibold text-gray-800 mb-3 flex items-center">
                        <i class="fas fa-list-ul mr-2"></i>Available Models ({{ $provider->models->count() }})
                    </h4>

                    @if($provider->models->count() > 0)
                    <div class="space-y-3">
                        @foreach($provider->models as $model)
                        <div class="flex items-center justify-between bg-gray-50 rounded-lg p-4 hover:bg-gray-100 transition-colors">
                            <div class="flex items-center flex-1">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center mr-3"
                                     style="background: {{ $model->is_active ? 'linear-gradient(135deg, #10b981, #059669)' : 'linear-gradient(135deg, #9ca3af, #6b7280)' }};">
                                    <i class="fas fa-robot text-white"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="font-semibold text-gray-800">{{ $model->display_name }}</p>
                                    <p class="text-xs text-gray-600">{{ $model->name }}</p>
                                    @if($model->description)
                                    <p class="text-xs text-gray-500 mt-1">{{ $model->description }}</p>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                @if($model->max_tokens)
                                <span class="text-xs bg-blue-100 text-blue-800 px-3 py-1 rounded-full">
                                    <i class="fas fa-memory mr-1"></i>{{ number_format($model->max_tokens) }} tokens
                                </span>
                                @endif
                                <button @click="toggleModel({{ $model->id }})"
                                        class="px-3 py-1 rounded-lg text-xs font-semibold transition-colors"
                                        :class="models[{{ $model->id }}]?.is_active ? 'bg-green-500 text-white hover:bg-green-600' : 'bg-gray-300 text-gray-700 hover:bg-gray-400'">
                                    <i class="fas" :class="models[{{ $model->id }}]?.is_active ? 'fa-toggle-on' : 'fa-toggle-off'"></i>
                                    <span x-text="models[{{ $model->id }}]?.is_active ? 'Active' : 'Inactive'"></span>
                                </button>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-3"></i>
                        <p>No models available</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Configuration Modal -->
    <div x-show="configModal.show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black opacity-50" @click="configModal.show = false"></div>

            <div class="relative bg-white rounded-xl shadow-2xl max-w-2xl w-full p-6 z-10">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-2xl font-bold text-gray-800">
                        <i class="fas fa-cog mr-2"></i>Provider Configuration
                    </h3>
                    <button @click="configModal.show = false" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>

                <form @submit.prevent="saveConfig">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">API Endpoint</label>
                            <input type="url" x-model="configModal.data.api_endpoint"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                   placeholder="https://api.example.com">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">API Key</label>
                            <div class="relative">
                                <input :type="configModal.showKey ? 'text' : 'password'"
                                       x-model="configModal.data.api_key"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                       placeholder="sk-...">
                                <button type="button" @click="configModal.showKey = !configModal.showKey"
                                        class="absolute right-3 top-3 text-gray-500 hover:text-gray-700">
                                    <i class="fas" :class="configModal.showKey ? 'fa-eye-slash' : 'fa-eye'"></i>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Additional Configuration (JSON)</label>
                            <textarea x-model="configModal.data.config"
                                      rows="4"
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent font-mono text-sm"
                                      placeholder='{"timeout": 30}'></textarea>
                        </div>
                    </div>

                    <div class="flex gap-3 mt-6">
                        <button type="submit"
                                class="flex-1 bg-purple-500 text-white px-6 py-3 rounded-lg font-semibold hover:bg-purple-600 transition-colors">
                            <i class="fas fa-save mr-2"></i>บันทึก
                        </button>
                        <button type="button" @click="configModal.show = false"
                                class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition-colors">
                            ยกเลิก
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function aiProvidersManager() {
    return {
        providers: @json($providers->keyBy('id')->map(fn($p) => ['is_active' => $p->is_active])),
        models: @json($providers->flatMap(fn($p) => $p->models)->keyBy('id')->map(fn($m) => ['is_active' => $m->is_active])),
        expandedProviders: [],
        configModal: {
            show: false,
            providerId: null,
            showKey: false,
            data: {
                api_endpoint: '',
                api_key: '',
                config: ''
            }
        },

        toggleModels(providerId) {
            const index = this.expandedProviders.indexOf(providerId);
            if (index === -1) {
                this.expandedProviders.push(providerId);
            } else {
                this.expandedProviders.splice(index, 1);
            }
        },

        async toggleProvider(providerId) {
            try {
                const response = await fetch(`/admin/ai-providers/${providerId}/toggle`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();

                if (data.success) {
                    this.providers[providerId].is_active = data.is_active;
                    this.showToast(data.message, 'success');
                } else {
                    this.showToast(data.message || 'เกิดข้อผิดพลาด', 'error');
                }
            } catch (error) {
                this.showToast('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
            }
        },

        async toggleModel(modelId) {
            try {
                const response = await fetch(`/admin/ai-providers/models/${modelId}/toggle`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();

                if (data.success) {
                    this.models[modelId].is_active = data.is_active;
                    this.showToast(data.message, 'success');
                } else {
                    this.showToast(data.message || 'เกิดข้อผิดพลาด', 'error');
                }
            } catch (error) {
                this.showToast('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
            }
        },

        async testConnection(providerId) {
            this.showToast('กำลังทดสอบการเชื่อมต่อ...', 'info');

            try {
                const response = await fetch(`/admin/ai-providers/${providerId}/test`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();

                if (data.success) {
                    this.showToast(data.message, 'success');
                } else {
                    this.showToast(data.message || 'การทดสอบล้มเหลว', 'error');
                }
            } catch (error) {
                this.showToast('เกิดข้อผิดพลาดในการทดสอบ', 'error');
            }
        },

        openConfigModal(providerId) {
            const provider = @json($providers->keyBy('id'));
            this.configModal.providerId = providerId;
            this.configModal.data.api_endpoint = provider[providerId].api_endpoint || '';
            this.configModal.data.api_key = provider[providerId].config?.api_key || '';
            this.configModal.data.config = JSON.stringify(provider[providerId].config || {}, null, 2);
            this.configModal.show = true;
        },

        async saveConfig() {
            try {
                const response = await fetch(`/admin/ai-providers/${this.configModal.providerId}/config`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        api_endpoint: this.configModal.data.api_endpoint,
                        api_key: this.configModal.data.api_key,
                        config: JSON.parse(this.configModal.data.config || '{}')
                    })
                });
                const data = await response.json();

                if (data.success) {
                    this.showToast(data.message, 'success');
                    this.configModal.show = false;
                    setTimeout(() => location.reload(), 1500);
                } else {
                    this.showToast(data.message || 'เกิดข้อผิดพลาด', 'error');
                }
            } catch (error) {
                this.showToast('เกิดข้อผิดพลาดในการบันทึก', 'error');
            }
        },

        async startLocalAi() {
            this.showToast('กำลังเริ่มต้น Local AI...', 'info');

            try {
                const response = await fetch('/admin/ai-providers/local/start', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();

                this.showToast(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    setTimeout(() => location.reload(), 2000);
                }
            } catch (error) {
                this.showToast('เกิดข้อผิดพลาด', 'error');
            }
        },

        async stopLocalAi() {
            this.showToast('กำลังหยุด Local AI...', 'info');

            try {
                const response = await fetch('/admin/ai-providers/local/stop', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();

                this.showToast(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    setTimeout(() => location.reload(), 2000);
                }
            } catch (error) {
                this.showToast('เกิดข้อผิดพลาด', 'error');
            }
        },

        async restartLocalAi() {
            this.showToast('กำลัง Restart Local AI...', 'info');

            try {
                const response = await fetch('/admin/ai-providers/local/restart', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();

                this.showToast(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    setTimeout(() => location.reload(), 2000);
                }
            } catch (error) {
                this.showToast('เกิดข้อผิดพลาด', 'error');
            }
        },

        showToast(message, type = 'info') {
            const colors = {
                success: { bg: '#10b981', icon: 'check-circle' },
                error: { bg: '#ef4444', icon: 'times-circle' },
                warning: { bg: '#f59e0b', icon: 'exclamation-triangle' },
                info: { bg: '#3b82f6', icon: 'info-circle' }
            };

            const color = colors[type] || colors.info;
            const toast = document.createElement('div');
            toast.className = 'toast';
            toast.innerHTML = `
                <div class="rounded-lg shadow-xl p-4 flex items-center gap-3" style="background: ${color.bg}; color: white;">
                    <i class="fas fa-${color.icon} text-xl"></i>
                    <span class="font-semibold">${message}</span>
                </div>
            `;

            document.getElementById('toast-container').appendChild(toast);

            setTimeout(() => {
                toast.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    }
}
</script>
@endpush
