@extends('layouts.admin')
@section('title', 'จัดการ AI Providers')

@push('styles')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');

    * {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    }

    .modern-container {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        padding: 2rem;
    }

    .glass-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 24px;
        border: 1px solid rgba(255, 255, 255, 0.3);
        box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
        transition: all 0.3s ease;
    }

    .glass-card:hover {
        box-shadow: 0 12px 48px 0 rgba(31, 38, 135, 0.25);
        transform: translateY(-4px);
    }

    .stat-card-modern {
        background: linear-gradient(135deg, rgba(255,255,255,0.9), rgba(255,255,255,0.7));
        backdrop-filter: blur(10px);
        border-radius: 20px;
        padding: 2rem;
        border: 1px solid rgba(255, 255, 255, 0.5);
        box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.1);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }

    .stat-card-modern::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #667eea, #764ba2, #f093fb);
        transition: all 0.3s ease;
    }

    .stat-card-modern:hover {
        transform: translateY(-8px) scale(1.02);
        box-shadow: 0 16px 48px 0 rgba(31, 38, 135, 0.2);
    }

    .stat-card-modern:hover::before {
        height: 6px;
    }

    .provider-card-modern {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 24px;
        border: 1px solid rgba(255, 255, 255, 0.3);
        overflow: hidden;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
    }

    .provider-logo {
        transition: all 0.3s ease;
    }

    .provider-card-modern:hover .provider-logo {
        transform: scale(1.1) rotate(5deg);
    }

    .provider-card-modern::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 6px;
        height: 100%;
        background: linear-gradient(180deg, #667eea, #764ba2);
        transition: width 0.3s ease;
    }

    .provider-card-modern:hover::before {
        width: 10px;
    }

    .provider-card-modern:hover {
        transform: translateX(4px);
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
    }

    .btn-modern {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 12px;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        position: relative;
        overflow: hidden;
    }

    .btn-modern::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        transform: translate(-50%, -50%);
        transition: width 0.6s, height 0.6s;
    }

    .btn-modern:hover::before {
        width: 300px;
        height: 300px;
    }

    .btn-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4);
    }

    .badge-modern {
        padding: 0.5rem 1rem;
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .badge-cloud {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        color: white;
    }

    .badge-local {
        background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        color: white;
    }

    .model-item-modern {
        background: linear-gradient(135deg, rgba(255,255,255,0.9), rgba(249,250,251,0.9));
        border-radius: 16px;
        padding: 1.5rem;
        border: 1px solid rgba(0,0,0,0.05);
        transition: all 0.3s ease;
        position: relative;
    }

    .model-item-modern::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        background: linear-gradient(180deg, #667eea, #764ba2);
        border-radius: 16px 0 0 16px;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .model-item-modern:hover::before {
        opacity: 1;
    }

    .model-item-modern:hover {
        transform: translateX(4px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.08);
    }

    .toggle-modern {
        position: relative;
        width: 60px;
        height: 32px;
        background: #e5e7eb;
        border-radius: 50px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .toggle-modern.active {
        background: linear-gradient(135deg, #10b981, #059669);
    }

    .toggle-modern::after {
        content: '';
        position: absolute;
        top: 4px;
        left: 4px;
        width: 24px;
        height: 24px;
        background: white;
        border-radius: 50%;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    }

    .toggle-modern.active::after {
        left: 32px;
    }

    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        background: rgba(255, 255, 255, 0.9);
        border-radius: 24px;
        border: 2px dashed rgba(102, 126, 234, 0.3);
    }

    .empty-state-icon {
        width: 120px;
        height: 120px;
        margin: 0 auto 2rem;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        color: white;
        animation: float 3s ease-in-out infinite;
    }

    @keyframes float {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-20px); }
    }

    .tooltip-modern {
        position: relative;
    }

    .tooltip-modern:hover::after {
        content: attr(data-tooltip);
        position: absolute;
        bottom: 100%;
        left: 50%;
        transform: translateX(-50%);
        padding: 0.5rem 1rem;
        background: rgba(0, 0, 0, 0.9);
        color: white;
        border-radius: 8px;
        font-size: 0.75rem;
        white-space: nowrap;
        margin-bottom: 0.5rem;
        z-index: 1000;
    }

    .metric-number {
        font-size: 3rem;
        font-weight: 800;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .section-title {
        font-size: 2rem;
        font-weight: 800;
        color: white;
        margin-bottom: 2rem;
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .pulse-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        animation: pulse 2s ease-in-out infinite;
    }

    @keyframes pulse {
        0%, 100% {
            opacity: 1;
            transform: scale(1);
        }
        50% {
            opacity: 0.5;
            transform: scale(1.2);
        }
    }

    .loading-shimmer {
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: shimmer 1.5s infinite;
    }

    @keyframes shimmer {
        0% { background-position: -200% 0; }
        100% { background-position: 200% 0; }
    }
</style>
@endpush

@section('content')
<div class="modern-container" x-data="aiProvidersManager()">
    <!-- Toast Notification Container -->
    <div id="toast-container" class="fixed top-4 right-4 z-50 space-y-3"></div>

    <!-- Header Section -->
    <div class="section-title">
        <div class="pulse-dot bg-white"></div>
        <span>AI Providers Management</span>
    </div>

    @if($providers->count() > 0)
    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="stat-card-modern">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-semibold mb-2">Total Providers</p>
                    <p class="metric-number">{{ $providers->count() }}</p>
                    <p class="text-xs text-gray-500 mt-2">All AI services</p>
                </div>
                <div class="text-6xl opacity-20">
                    <i class="fas fa-server"></i>
                </div>
            </div>
        </div>

        <div class="stat-card-modern">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-semibold mb-2">Active Providers</p>
                    <p class="metric-number">{{ $providers->where('is_active', true)->count() }}</p>
                    <p class="text-xs text-gray-500 mt-2">Currently enabled</p>
                </div>
                <div class="text-6xl opacity-20">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>

        <div class="stat-card-modern">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-semibold mb-2">Total Models</p>
                    <p class="metric-number">{{ $providers->sum(fn($p) => $p->models->count()) }}</p>
                    <p class="text-xs text-gray-500 mt-2">AI models available</p>
                </div>
                <div class="text-6xl opacity-20">
                    <i class="fas fa-brain"></i>
                </div>
            </div>
        </div>

        <div class="stat-card-modern">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-semibold mb-2">Active Models</p>
                    <p class="metric-number">{{ $providers->sum(fn($p) => $p->models->where('is_active', true)->count()) }}</p>
                    <p class="text-xs text-gray-500 mt-2">Ready to use</p>
                </div>
                <div class="text-6xl opacity-20">
                    <i class="fas fa-rocket"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Local AI Status Panel (if exists) -->
    @if($localAiStatus)
    <div class="glass-card p-8 mb-8">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-full bg-white flex items-center justify-center shadow-lg p-2 border border-gray-100">
                    <img src="https://cdn.simpleicons.org/ollama/000000"
                         alt="Ollama"
                         class="w-full h-full object-contain provider-logo"
                         onerror="this.onerror=null; this.parentElement.innerHTML='<i class=&quot;fas fa-microchip text-2xl text-gray-400&quot;></i>';">
                </div>
                <div>
                    <h3 class="text-2xl font-bold text-gray-800">Local AI Server</h3>
                    <p class="text-gray-600">Ollama Self-Hosted Instance</p>
                </div>
            </div>

            @if($localAiStatus['running'])
                <span class="badge-modern bg-green-500 text-white">
                    <span class="pulse-dot bg-white"></span>
                    Running
                </span>
            @else
                <span class="badge-modern bg-red-500 text-white">
                    <i class="fas fa-circle"></i>
                    Stopped
                </span>
            @endif
        </div>

        @if($localAiStatus['running'])
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-2xl p-6">
                <p class="text-blue-600 text-sm font-semibold mb-2">Endpoint</p>
                <p class="text-blue-900 font-bold text-lg break-all">{{ $localAiStatus['endpoint'] }}</p>
            </div>
            <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-2xl p-6">
                <p class="text-green-600 text-sm font-semibold mb-2">Uptime</p>
                <p class="text-green-900 font-bold text-lg">{{ $localAiStatus['uptime'] }}</p>
            </div>
            <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-2xl p-6">
                <p class="text-purple-600 text-sm font-semibold mb-2">Loaded Models</p>
                <p class="text-purple-900 font-bold text-lg">{{ $localAiStatus['loaded_models'] ?? 0 }}</p>
            </div>
        </div>
        @endif

        <div class="flex gap-3">
            @if($localAiStatus['running'])
                <button @click="stopLocalAi" class="btn-modern bg-gradient-to-r from-red-500 to-red-600">
                    <i class="fas fa-stop mr-2"></i>Stop Server
                </button>
                <button @click="restartLocalAi" class="btn-modern bg-gradient-to-r from-yellow-500 to-orange-600">
                    <i class="fas fa-redo mr-2"></i>Restart
                </button>
            @else
                <button @click="startLocalAi" class="btn-modern bg-gradient-to-r from-green-500 to-green-600">
                    <i class="fas fa-play mr-2"></i>Start Server
                </button>
            @endif
        </div>
    </div>
    @endif

    <!-- Providers List -->
    <div class="space-y-6">
        @foreach($providers as $provider)
        <div class="provider-card-modern">
            <div class="p-8">
                <!-- Provider Header -->
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 mb-6">
                    <div class="flex items-center gap-6">
                        <div class="w-20 h-20 rounded-2xl bg-white flex items-center justify-center shadow-lg p-3 border border-gray-100">
                            @if(isset($provider->config['logo_url']))
                                <img src="{{ $provider->config['logo_url'] }}"
                                     alt="{{ $provider->display_name }}"
                                     class="w-full h-full object-contain provider-logo"
                                     onerror="this.onerror=null; this.parentElement.innerHTML='<i class=&quot;fas {{ $provider->provider_type === 'cloud' ? 'fa-cloud' : 'fa-server' }} text-3xl text-gray-400&quot;></i>';">
                            @else
                                <i class="fas {{ $provider->provider_type === 'cloud' ? 'fa-cloud' : 'fa-server' }} text-3xl text-gray-400"></i>
                            @endif
                        </div>
                        <div>
                            <div class="flex items-center gap-3 mb-2">
                                <h3 class="text-3xl font-bold text-gray-800">{{ $provider->display_name }}</h3>
                                <span class="badge-modern {{ $provider->provider_type === 'cloud' ? 'badge-cloud' : 'badge-local' }}">
                                    {{ strtoupper($provider->provider_type) }}
                                </span>
                            </div>
                            <div class="flex items-center gap-4 text-gray-600">
                                <span class="flex items-center gap-2">
                                    <i class="fas fa-brain"></i>
                                    <strong>{{ $provider->models->count() }}</strong> models
                                </span>
                                <span class="flex items-center gap-2">
                                    <i class="fas fa-check-circle text-green-500"></i>
                                    <strong>{{ $provider->models->where('is_active', true)->count() }}</strong> active
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <!-- Toggle Provider -->
                        <button @click="toggleProvider({{ $provider->id }})"
                                class="tooltip-modern"
                                data-tooltip="Toggle Provider">
                            <div class="toggle-modern" :class="{ 'active': providers[{{ $provider->id }}]?.is_active }"></div>
                        </button>

                        <!-- Test Connection -->
                        <button @click="testConnection({{ $provider->id }})"
                                class="btn-modern bg-gradient-to-r from-blue-500 to-blue-600 tooltip-modern"
                                data-tooltip="Test Connection">
                            <i class="fas fa-plug"></i>
                        </button>

                        <!-- Settings -->
                        <button @click="openConfigModal({{ $provider->id }})"
                                class="btn-modern bg-gradient-to-r from-purple-500 to-purple-600 tooltip-modern"
                                data-tooltip="Configuration">
                            <i class="fas fa-cog"></i>
                        </button>

                        <!-- Expand -->
                        <button @click="toggleModels({{ $provider->id }})"
                                class="btn-modern bg-gradient-to-r from-gray-500 to-gray-600 tooltip-modern"
                                data-tooltip="View Models">
                            <i class="fas" :class="expandedProviders.includes({{ $provider->id }}) ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                        </button>
                    </div>
                </div>

                <!-- Provider Info Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl p-4">
                        <p class="text-xs text-gray-500 font-semibold mb-2 uppercase tracking-wider">API Endpoint</p>
                        <p class="text-sm font-bold text-gray-800 break-all">{{ $provider->api_endpoint ?? 'N/A' }}</p>
                    </div>
                    <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl p-4">
                        <p class="text-xs text-gray-500 font-semibold mb-2 uppercase tracking-wider">API Key Status</p>
                        @if(isset($provider->config['api_key']) && !empty($provider->config['api_key']))
                            <span class="inline-flex items-center gap-2 text-green-600 font-bold">
                                <i class="fas fa-check-circle"></i>
                                Configured
                            </span>
                        @else
                            <span class="inline-flex items-center gap-2 text-red-600 font-bold">
                                <i class="fas fa-times-circle"></i>
                                Not Set
                            </span>
                        @endif
                    </div>
                    <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl p-4">
                        <p class="text-xs text-gray-500 font-semibold mb-2 uppercase tracking-wider">Last Updated</p>
                        <p class="text-sm font-bold text-gray-800">{{ $provider->updated_at->diffForHumans() }}</p>
                    </div>
                </div>

                <!-- Models Section -->
                <div x-show="expandedProviders.includes({{ $provider->id }})"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 -translate-y-4"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="border-t pt-6">
                    <h4 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-brain text-purple-500"></i>
                        Available Models ({{ $provider->models->count() }})
                    </h4>

                    @if($provider->models->count() > 0)
                    <div class="grid gap-4">
                        @foreach($provider->models as $model)
                        <div class="model-item-modern">
                            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                                <div class="flex items-start gap-4 flex-1">
                                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br {{ $model->is_active ? 'from-green-500 to-emerald-500' : 'from-gray-400 to-gray-500' }} flex items-center justify-center text-white flex-shrink-0">
                                        <i class="fas fa-robot"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h5 class="font-bold text-gray-800 text-lg mb-1">{{ $model->display_name }}</h5>
                                        <p class="text-xs text-gray-500 mb-2 font-mono">{{ $model->name }}</p>
                                        @if($model->description)
                                        <p class="text-sm text-gray-600">{{ $model->description }}</p>
                                        @endif

                                        <!-- Model Features -->
                                        <div class="flex flex-wrap gap-2 mt-3">
                                            @if($model->max_tokens)
                                            <span class="text-xs bg-blue-100 text-blue-800 px-3 py-1 rounded-full font-semibold">
                                                <i class="fas fa-memory mr-1"></i>{{ number_format($model->max_tokens) }} tokens
                                            </span>
                                            @endif
                                            @if($model->supports_vision ?? false)
                                            <span class="text-xs bg-purple-100 text-purple-800 px-3 py-1 rounded-full font-semibold">
                                                <i class="fas fa-eye mr-1"></i>Vision
                                            </span>
                                            @endif
                                            @if($model->supports_functions ?? false)
                                            <span class="text-xs bg-green-100 text-green-800 px-3 py-1 rounded-full font-semibold">
                                                <i class="fas fa-code mr-1"></i>Functions
                                            </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <button @click="toggleModel({{ $model->id }})" class="tooltip-modern" data-tooltip="Toggle Model">
                                    <div class="toggle-modern" :class="{ 'active': models[{{ $model->id }}]?.is_active }"></div>
                                </button>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center py-12 text-gray-400">
                        <i class="fas fa-inbox text-6xl mb-4"></i>
                        <p class="text-lg">No models available</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>

    @else
    <!-- Empty State -->
    <div class="empty-state">
        <div class="empty-state-icon">
            <i class="fas fa-robot"></i>
        </div>
        <h2 class="text-3xl font-bold text-gray-800 mb-4">No AI Providers Found</h2>
        <p class="text-gray-600 mb-8 text-lg max-w-2xl mx-auto">
            You haven't configured any AI providers yet. Run the seeder to populate with default providers.
        </p>
        <div class="bg-gray-50 rounded-xl p-6 max-w-xl mx-auto text-left">
            <p class="text-sm font-semibold text-gray-700 mb-3">Run this command to seed providers:</p>
            <div class="bg-gray-900 text-green-400 p-4 rounded-lg font-mono text-sm">
                php artisan db:seed --class=AiProvidersSeeder
            </div>
        </div>
    </div>
    @endif

    <!-- Configuration Modal -->
    <div x-show="configModal.show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm" @click="configModal.show = false"></div>

            <div class="relative bg-white rounded-3xl shadow-2xl max-w-2xl w-full p-8 z-10">
                <div class="flex items-center justify-between mb-8">
                    <h3 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white">
                            <i class="fas fa-cog"></i>
                        </div>
                        Provider Configuration
                    </h3>
                    <button @click="configModal.show = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>

                <form @submit.prevent="saveConfig" class="space-y-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-3">API Endpoint</label>
                        <input type="url" x-model="configModal.data.api_endpoint"
                               class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-purple-500 focus:border-purple-500 transition-all"
                               placeholder="https://api.example.com">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-3">API Key</label>
                        <div class="relative">
                            <input :type="configModal.showKey ? 'text' : 'password'"
                                   x-model="configModal.data.api_key"
                                   class="w-full px-4 py-3 pr-12 border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-purple-500 focus:border-purple-500 transition-all"
                                   placeholder="sk-...">
                            <button type="button" @click="configModal.showKey = !configModal.showKey"
                                    class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <i class="fas" :class="configModal.showKey ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-3">Additional Configuration (JSON)</label>
                        <textarea x-model="configModal.data.config"
                                  rows="6"
                                  class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-purple-500 focus:border-purple-500 transition-all font-mono text-sm"
                                  placeholder='{"timeout": 30}'></textarea>
                    </div>

                    <div class="flex gap-4 pt-4">
                        <button type="submit" class="flex-1 btn-modern bg-gradient-to-r from-purple-500 to-purple-600 py-4 text-lg">
                            <i class="fas fa-save mr-2"></i>Save Configuration
                        </button>
                        <button type="button" @click="configModal.show = false"
                                class="px-8 py-4 bg-gray-200 text-gray-700 rounded-xl font-semibold hover:bg-gray-300 transition-colors">
                            Cancel
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
                success: { bg: 'linear-gradient(135deg, #10b981, #059669)', icon: 'check-circle' },
                error: { bg: 'linear-gradient(135deg, #ef4444, #dc2626)', icon: 'times-circle' },
                warning: { bg: 'linear-gradient(135deg, #f59e0b, #d97706)', icon: 'exclamation-triangle' },
                info: { bg: 'linear-gradient(135deg, #3b82f6, #2563eb)', icon: 'info-circle' }
            };

            const color = colors[type] || colors.info;
            const toast = document.createElement('div');
            toast.className = 'transform transition-all duration-300 ease-out';
            toast.style.animation = 'slideInRight 0.3s ease-out';
            toast.innerHTML = `
                <div class="rounded-2xl shadow-2xl p-4 flex items-center gap-3 min-w-[320px]" style="background: ${color.bg}; color: white;">
                    <i class="fas fa-${color.icon} text-2xl"></i>
                    <span class="font-semibold">${message}</span>
                </div>
            `;

            document.getElementById('toast-container').appendChild(toast);

            setTimeout(() => {
                toast.style.animation = 'slideOutRight 0.3s ease-in';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    }
}

// Animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from { transform: translateX(400px); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOutRight {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(400px); opacity: 0; }
    }
`;
document.head.appendChild(style);
</script>
@endpush
