@extends('layouts.admin-v3')
@section('title', 'AI Core Dashboard - จัดการ AI Providers')

@push('styles')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');

    * {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    }

    .ai-dashboard-container {
        background: linear-gradient(135deg, #1e3a5f 0%, #0d1b2a 50%, #1b263b 100%);
        min-height: 100vh;
        padding: 2rem;
    }

    .glass-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        border-radius: 24px;
        border: 1px solid rgba(255, 255, 255, 0.3);
        box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.2);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .glass-card:hover {
        box-shadow: 0 16px 48px 0 rgba(0, 0, 0, 0.25);
        transform: translateY(-4px);
    }

    .stat-card {
        background: linear-gradient(135deg, rgba(255,255,255,0.95), rgba(255,255,255,0.85));
        border-radius: 20px;
        padding: 1.5rem;
        border: 1px solid rgba(255, 255, 255, 0.5);
        box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        transition: all 0.3s ease;
    }

    .stat-card.blue::before { background: linear-gradient(90deg, #3b82f6, #60a5fa); }
    .stat-card.green::before { background: linear-gradient(90deg, #10b981, #34d399); }
    .stat-card.purple::before { background: linear-gradient(90deg, #8b5cf6, #a78bfa); }
    .stat-card.orange::before { background: linear-gradient(90deg, #f59e0b, #fbbf24); }

    .stat-card:hover {
        transform: translateY(-6px) scale(1.02);
        box-shadow: 0 20px 50px 0 rgba(0, 0, 0, 0.15);
    }

    .stat-number {
        font-size: 2.5rem;
        font-weight: 800;
        line-height: 1;
    }

    .stat-card.blue .stat-number { color: #3b82f6; }
    .stat-card.green .stat-number { color: #10b981; }
    .stat-card.purple .stat-number { color: #8b5cf6; }
    .stat-card.orange .stat-number { color: #f59e0b; }

    .provider-card {
        background: rgba(255, 255, 255, 0.98);
        border-radius: 24px;
        border: 1px solid rgba(0, 0, 0, 0.05);
        overflow: hidden;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
    }

    .provider-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 6px;
        height: 100%;
        transition: width 0.3s ease;
    }

    .provider-card.openai::before { background: linear-gradient(180deg, #10a37f, #00a67e); }
    .provider-card.anthropic::before { background: linear-gradient(180deg, #d97706, #f59e0b); }
    .provider-card.google::before { background: linear-gradient(180deg, #4285f4, #34a853, #fbbc05, #ea4335); }
    .provider-card.meta::before { background: linear-gradient(180deg, #0668E1, #00b4ff); }
    .provider-card.deepseek::before { background: linear-gradient(180deg, #536dfe, #304ffe); }

    .provider-card:hover {
        transform: translateX(4px);
        box-shadow: 0 24px 60px rgba(0, 0, 0, 0.15);
    }

    .provider-card:hover::before {
        width: 10px;
    }

    .provider-logo {
        width: 64px;
        height: 64px;
        border-radius: 16px;
        background: white;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        overflow: hidden;
        padding: 8px;
    }

    .provider-logo img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

    .provider-card:hover .provider-logo {
        transform: scale(1.1) rotate(3deg);
    }

    .toggle-switch {
        position: relative;
        width: 56px;
        height: 28px;
        background: #e5e7eb;
        border-radius: 28px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .toggle-switch.active {
        background: linear-gradient(135deg, #10b981, #059669);
    }

    .toggle-switch::after {
        content: '';
        position: absolute;
        top: 3px;
        left: 3px;
        width: 22px;
        height: 22px;
        background: white;
        border-radius: 50%;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    }

    .toggle-switch.active::after {
        left: 31px;
    }

    .btn-action {
        padding: 0.75rem 1.25rem;
        border-radius: 12px;
        font-weight: 600;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        border: none;
        cursor: pointer;
        font-size: 0.875rem;
    }

    .btn-action.primary {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }

    .btn-action.success {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }

    .btn-action.warning {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: white;
        box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
    }

    .btn-action.danger {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    }

    .btn-action.secondary {
        background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
        color: white;
        box-shadow: 0 4px 12px rgba(107, 114, 128, 0.3);
    }

    .btn-action:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
    }

    .badge-type {
        padding: 0.4rem 0.8rem;
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.7rem;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }

    .badge-cloud {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        color: white;
    }

    .badge-local {
        background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        color: white;
    }

    .badge-self-hosted {
        background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        color: white;
    }

    .model-item {
        background: linear-gradient(135deg, #f8fafc, #f1f5f9);
        border-radius: 16px;
        padding: 1.25rem;
        border: 1px solid rgba(0,0,0,0.05);
        transition: all 0.3s ease;
    }

    .model-item:hover {
        transform: translateX(4px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.08);
        background: white;
    }

    .model-badge {
        padding: 0.25rem 0.6rem;
        border-radius: 50px;
        font-size: 0.65rem;
        font-weight: 600;
    }

    .chat-container {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        border-radius: 20px;
        overflow: hidden;
    }

    .chat-messages {
        max-height: 300px;
        overflow-y: auto;
        padding: 1.5rem;
    }

    .chat-message {
        padding: 1rem;
        border-radius: 16px;
        margin-bottom: 1rem;
        max-width: 85%;
    }

    .chat-message.user {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: white;
        margin-left: auto;
        border-bottom-right-radius: 4px;
    }

    .chat-message.ai {
        background: rgba(255, 255, 255, 0.1);
        color: white;
        margin-right: auto;
        border-bottom-left-radius: 4px;
    }

    .chat-input-container {
        background: rgba(255, 255, 255, 0.05);
        padding: 1rem;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    .pulse-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        animation: pulse 2s ease-in-out infinite;
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.5; transform: scale(1.2); }
    }

    .status-online { background: #10b981; }
    .status-offline { background: #ef4444; }

    .llama-logo-container {
        position: relative;
    }

    .llama-logo-container::after {
        content: '🦙';
        position: absolute;
        bottom: -4px;
        right: -4px;
        font-size: 20px;
    }

    .section-title {
        font-size: 1.75rem;
        font-weight: 800;
        color: white;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .modal-overlay {
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(8px);
    }

    .modal-content {
        background: white;
        border-radius: 24px;
        max-width: 700px;
        width: 100%;
        max-height: 90vh;
        overflow-y: auto;
    }

    .input-modern {
        width: 100%;
        padding: 0.875rem 1rem;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        font-size: 0.95rem;
        transition: all 0.3s ease;
    }

    .input-modern:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
    }

    /* Toast styles */
    .toast {
        animation: slideIn 0.3s ease-out;
    }

    @keyframes slideIn {
        from { transform: translateX(100px); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
</style>
@endpush

@section('content')
<div class="ai-dashboard-container" x-data="aiDashboard()">
    <!-- Toast Container -->
    <div id="toast-container" class="fixed top-4 right-4 z-50 space-y-3"></div>

    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="section-title">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center">
                    <i class="fas fa-brain text-white text-xl"></i>
                </div>
                AI Core Dashboard
            </h1>
            <p class="text-gray-300 text-sm">จัดการ AI Providers, Models และทดสอบการทำงาน</p>
        </div>

        <div class="flex gap-3">
            <button @click="refreshStatus()" class="btn-action primary">
                <i class="fas fa-sync-alt"></i>
                <span>รีเฟรช</span>
            </button>
            <a href="{{ route('admin.ai-monitoring.index') }}" class="btn-action secondary">
                <i class="fas fa-chart-line"></i>
                <span>Monitoring</span>
            </a>
        </div>
    </div>

    @if($providers->count() > 0)
    <!-- Stats Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="stat-card blue">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-xs font-semibold mb-1">PROVIDERS</p>
                    <p class="stat-number">{{ $providers->count() }}</p>
                    <p class="text-xs text-gray-400 mt-1">ทั้งหมด</p>
                </div>
                <i class="fas fa-server text-4xl text-blue-100"></i>
            </div>
        </div>

        <div class="stat-card green">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-xs font-semibold mb-1">ACTIVE</p>
                    <p class="stat-number">{{ $providers->where('is_active', true)->count() }}</p>
                    <p class="text-xs text-gray-400 mt-1">เปิดใช้งาน</p>
                </div>
                <i class="fas fa-check-circle text-4xl text-green-100"></i>
            </div>
        </div>

        <div class="stat-card purple">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-xs font-semibold mb-1">MODELS</p>
                    <p class="stat-number">{{ $providers->sum(fn($p) => $p->models->count()) }}</p>
                    <p class="text-xs text-gray-400 mt-1">โมเดลทั้งหมด</p>
                </div>
                <i class="fas fa-robot text-4xl text-purple-100"></i>
            </div>
        </div>
    </div>

    <!-- Providers List -->
    <div class="section-title">
        <i class="fas fa-layer-group"></i>
        <span>AI Providers ({{ $providers->count() }})</span>
    </div>

    <div class="space-y-6">
        @foreach($providers as $provider)
        <div class="provider-card {{ $provider->name }}">
            <div class="p-6">
                <!-- Provider Header -->
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-4">
                    <div class="flex items-center gap-4">
                        <div class="provider-logo {{ $provider->name === 'meta' ? 'llama-logo-container' : '' }}">
                            @if(isset($providerLogos[$provider->name]))
                                <img src="{{ $providerLogos[$provider->name] }}"
                                     alt="{{ $provider->display_name }}"
                                     onerror="this.onerror=null; this.parentElement.innerHTML='<i class=\'fas fa-robot text-3xl text-gray-400\'></i>';">
                            @elseif(isset($provider->config['logo_url']))
                                <img src="{{ $provider->config['logo_url'] }}"
                                     alt="{{ $provider->display_name }}"
                                     onerror="this.onerror=null; this.parentElement.innerHTML='<i class=\'fas fa-robot text-3xl text-gray-400\'></i>';">
                            @else
                                <i class="fas {{ $provider->provider_type === 'cloud' ? 'fa-cloud' : 'fa-server' }} text-3xl text-gray-400"></i>
                            @endif
                        </div>
                        <div>
                            <div class="flex items-center gap-3 mb-1">
                                <h3 class="text-xl font-bold text-gray-800">{{ $provider->display_name }}</h3>
                                <span class="badge-type {{ $provider->provider_type === 'cloud' ? 'badge-cloud' : ($provider->provider_type === 'self-hosted' ? 'badge-self-hosted' : 'badge-local') }}">
                                    {{ strtoupper($provider->provider_type) }}
                                </span>
                                @if($provider->name === 'meta')
                                    <span class="text-xl">🦙</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-4 text-sm text-gray-500">
                                <span><i class="fas fa-robot mr-1"></i>{{ $provider->models->count() }} models</span>
                                <span><i class="fas fa-check-circle text-green-500 mr-1"></i>{{ $provider->models->where('is_active', true)->count() }} active</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <!-- Toggle -->
                        <button @click="toggleProvider({{ $provider->id }})"
                                class="toggle-switch"
                                :class="{ 'active': providers[{{ $provider->id }}]?.is_active }"
                                title="เปิด/ปิด Provider">
                        </button>

                        <!-- Test Connection -->
                        <button @click="testConnection({{ $provider->id }})" class="btn-action primary" title="ทดสอบการเชื่อมต่อ">
                            <i class="fas fa-plug"></i>
                        </button>

                        <!-- Test Chat -->
                        @if($provider->models->where('is_active', true)->count() > 0)
                        <button @click="openTestChat({{ $provider->id }})" class="btn-action success" title="ทดสอบแชท">
                            <i class="fas fa-comments"></i>
                        </button>
                        @endif

                        <!-- Settings -->
                        <button @click="openConfig({{ $provider->id }})" class="btn-action secondary" title="ตั้งค่า">
                            <i class="fas fa-cog"></i>
                        </button>

                        <!-- Expand Models -->
                        <button @click="toggleModels({{ $provider->id }})" class="btn-action secondary" title="ดู Models">
                            <i class="fas" :class="expandedProviders.includes({{ $provider->id }}) ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                        </button>
                    </div>
                </div>

                <!-- Provider Info -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
                    <div class="bg-gray-50 rounded-xl p-3">
                        <p class="text-xs text-gray-500 font-semibold mb-1">API ENDPOINT</p>
                        <p class="text-sm font-medium text-gray-800 break-all">{{ $provider->api_endpoint ?? 'N/A' }}</p>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-3">
                        <p class="text-xs text-gray-500 font-semibold mb-1">API KEY</p>
                        @if(isset($provider->config['api_key']) && !empty($provider->config['api_key']))
                            <span class="inline-flex items-center gap-1 text-green-600 font-semibold text-sm">
                                <i class="fas fa-check-circle"></i>
                                ตั้งค่าแล้ว
                            </span>
                        @elseif($provider->provider_type === 'self-hosted')
                            <span class="inline-flex items-center gap-1 text-blue-600 font-semibold text-sm">
                                <i class="fas fa-info-circle"></i>
                                ไม่ต้องใช้
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 text-red-600 font-semibold text-sm">
                                <i class="fas fa-times-circle"></i>
                                ยังไม่ได้ตั้งค่า
                            </span>
                        @endif
                    </div>
                    <div class="bg-gray-50 rounded-xl p-3">
                        <p class="text-xs text-gray-500 font-semibold mb-1">PRICING</p>
                        @if($provider->provider_type === 'self-hosted')
                            <span class="text-green-600 font-bold text-sm">ฟรี (Self-hosted)</span>
                        @elseif(isset($provider->pricing['input']))
                            <span class="text-sm text-gray-800">
                                ${{ $provider->pricing['input'] ?? 0 }}/{{ $provider->pricing['output'] ?? 0 }} per 1M
                            </span>
                        @else
                            <span class="text-gray-500 text-sm">-</span>
                        @endif
                    </div>
                </div>

                <!-- Models Section -->
                <div x-show="expandedProviders.includes({{ $provider->id }})"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 -translate-y-4"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="border-t pt-4">
                    <h4 class="text-lg font-bold text-gray-800 mb-3 flex items-center gap-2">
                        <i class="fas fa-robot text-purple-500"></i>
                        Models ({{ $provider->models->count() }})
                    </h4>

                    @if($provider->models->count() > 0)
                    <div class="grid gap-3">
                        @foreach($provider->models as $model)
                        <div class="model-item">
                            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <h5 class="font-bold text-gray-800">{{ $model->display_name }}</h5>
                                        @if($model->supports_vision ?? false)
                                            <span class="model-badge bg-purple-100 text-purple-700">
                                                <i class="fas fa-eye mr-1"></i>Vision
                                            </span>
                                        @endif
                                        @if($model->supports_functions ?? false)
                                            <span class="model-badge bg-green-100 text-green-700">
                                                <i class="fas fa-code mr-1"></i>Functions
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-gray-500 font-mono mb-1">{{ $model->model_identifier }}</p>
                                    @if($model->description)
                                    <p class="text-sm text-gray-600">{{ Str::limit($model->description, 100) }}</p>
                                    @endif
                                    <div class="flex gap-3 mt-2 text-xs text-gray-500">
                                        @if($model->context_window)
                                        <span><i class="fas fa-database mr-1"></i>{{ number_format($model->context_window) }} context</span>
                                        @endif
                                        @if($model->max_output_tokens)
                                        <span><i class="fas fa-file-alt mr-1"></i>{{ number_format($model->max_output_tokens) }} max output</span>
                                        @endif
                                    </div>
                                </div>

                                <button @click="toggleModel({{ $model->id }})"
                                        class="toggle-switch"
                                        :class="{ 'active': models[{{ $model->id }}]?.is_active }">
                                </button>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center py-8 text-gray-400">
                        <i class="fas fa-inbox text-4xl mb-2"></i>
                        <p>ไม่มี models</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>

    @else
    <!-- Empty State -->
    <div class="glass-card p-12 text-center">
        <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center">
            <i class="fas fa-robot text-4xl text-white"></i>
        </div>
        <h2 class="text-2xl font-bold text-gray-800 mb-3">ยังไม่มี AI Providers</h2>
        <p class="text-gray-600 mb-6">รันคำสั่งนี้เพื่อเพิ่ม providers:</p>
        <code class="block p-4 bg-gray-900 text-green-400 rounded-xl font-mono text-sm">
            php artisan db:seed --class=AiProvidersSeeder
        </code>
    </div>
    @endif

    <!-- Config Modal -->
    <div x-show="configModal.show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 modal-overlay"
         style="display: none;">
        <div class="modal-content p-8" @click.away="configModal.show = false">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-cog text-purple-500"></i>
                    ตั้งค่า Provider
                </h3>
                <button @click="configModal.show = false" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <form @submit.prevent="saveConfig" class="space-y-5">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">API Endpoint</label>
                    <input type="url" x-model="configModal.data.api_endpoint" class="input-modern" placeholder="https://api.example.com">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">API Key</label>
                    <div class="relative">
                        <input :type="configModal.showKey ? 'text' : 'password'"
                               x-model="configModal.data.api_key"
                               class="input-modern pr-12"
                               placeholder="sk-...">
                        <button type="button" @click="configModal.showKey = !configModal.showKey"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <i class="fas" :class="configModal.showKey ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="submit" class="flex-1 btn-action success py-3">
                        <i class="fas fa-save"></i>
                        บันทึก
                    </button>
                    <button type="button" @click="configModal.show = false" class="btn-action secondary py-3 px-6">
                        ยกเลิก
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Test Chat Modal -->
    <div x-show="chatModal.show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 modal-overlay"
         style="display: none;">
        <div class="modal-content p-0 overflow-hidden" style="max-width: 600px;" @click.away="chatModal.show = false">
            <!-- Chat Header -->
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 p-4 text-white">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center">
                            <i class="fas fa-robot"></i>
                        </div>
                        <div>
                            <h3 class="font-bold">ทดสอบ AI Chat</h3>
                            <p class="text-sm text-white/80" x-text="chatModal.providerName"></p>
                        </div>
                    </div>
                    <button @click="chatModal.show = false" class="text-white/80 hover:text-white">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <!-- Model Select -->
                <div class="mt-3">
                    <select x-model="chatModal.selectedModel" class="w-full bg-white/20 text-white border border-white/30 rounded-lg px-3 py-2 text-sm">
                        <option value="" class="text-gray-800">เลือก Model</option>
                        <template x-for="model in chatModal.models" :key="model.id">
                            <option :value="model.id" x-text="model.display_name" class="text-gray-800"></option>
                        </template>
                    </select>
                </div>
            </div>

            <!-- Chat Messages -->
            <div class="chat-container">
                <div class="chat-messages" id="chat-messages">
                    <template x-for="(msg, index) in chatModal.messages" :key="index">
                        <div class="chat-message" :class="msg.role">
                            <p x-text="msg.content"></p>
                            <template x-if="msg.meta">
                                <p class="text-xs mt-2 opacity-70">
                                    <span x-text="msg.meta.model"></span> •
                                    <span x-text="msg.meta.time + 'ms'"></span> •
                                    <span x-text="msg.meta.tokens + ' tokens'"></span>
                                </p>
                            </template>
                        </div>
                    </template>

                    <!-- Typing indicator -->
                    <div x-show="chatModal.loading" class="chat-message ai">
                        <div class="flex gap-1">
                            <span class="w-2 h-2 bg-white/50 rounded-full animate-bounce"></span>
                            <span class="w-2 h-2 bg-white/50 rounded-full animate-bounce" style="animation-delay: 0.1s"></span>
                            <span class="w-2 h-2 bg-white/50 rounded-full animate-bounce" style="animation-delay: 0.2s"></span>
                        </div>
                    </div>
                </div>

                <!-- Chat Input -->
                <div class="chat-input-container">
                    <form @submit.prevent="sendTestMessage" class="flex gap-2">
                        <input type="text"
                               x-model="chatModal.input"
                               placeholder="พิมพ์ข้อความทดสอบ..."
                               class="flex-1 bg-white/10 border border-white/20 rounded-lg px-4 py-3 text-white placeholder-white/50 focus:outline-none focus:border-white/40"
                               :disabled="chatModal.loading">
                        <button type="submit"
                                class="btn-action primary"
                                :disabled="chatModal.loading || !chatModal.selectedModel">
                            <i class="fas" :class="chatModal.loading ? 'fa-spinner fa-spin' : 'fa-paper-plane'"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function aiDashboard() {
    return {
        providers: @json($providers->keyBy('id')->map(fn($p) => ['is_active' => $p->is_active])),
        models: @json($providers->flatMap(fn($p) => $p->models)->keyBy('id')->map(fn($m) => ['is_active' => $m->is_active])),
        expandedProviders: [],
        configModal: {
            show: false,
            providerId: null,
            showKey: false,
            data: { api_endpoint: '', api_key: '' }
        },
        chatModal: {
            show: false,
            providerId: null,
            providerName: '',
            models: [],
            selectedModel: '',
            messages: [],
            input: '',
            loading: false
        },

        toggleModels(providerId) {
            const idx = this.expandedProviders.indexOf(providerId);
            if (idx === -1) {
                this.expandedProviders.push(providerId);
            } else {
                this.expandedProviders.splice(idx, 1);
            }
        },

        async toggleProvider(providerId) {
            try {
                const res = await fetch(`/admin/ai-providers/${providerId}/toggle`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                const data = await res.json();
                if (data.success) {
                    this.providers[providerId].is_active = data.is_active;
                    this.showToast(data.message, 'success');
                } else {
                    this.showToast(data.message, 'error');
                }
            } catch (e) {
                this.showToast('เกิดข้อผิดพลาด', 'error');
            }
        },

        async toggleModel(modelId) {
            try {
                const res = await fetch(`/admin/ai-providers/models/${modelId}/toggle`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                const data = await res.json();
                if (data.success) {
                    this.models[modelId].is_active = data.is_active;
                    this.showToast(data.message, 'success');
                } else {
                    this.showToast(data.message, 'error');
                }
            } catch (e) {
                this.showToast('เกิดข้อผิดพลาด', 'error');
            }
        },

        async testConnection(providerId) {
            this.showToast('กำลังทดสอบการเชื่อมต่อ...', 'info');
            try {
                const res = await fetch(`/admin/ai-providers/${providerId}/test`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                const data = await res.json();
                this.showToast(data.message, data.success ? 'success' : 'error');
            } catch (e) {
                this.showToast('เกิดข้อผิดพลาด', 'error');
            }
        },

        openConfig(providerId) {
            const provider = @json($providers->keyBy('id'));
            this.configModal.providerId = providerId;
            this.configModal.data.api_endpoint = provider[providerId].api_endpoint || '';
            this.configModal.data.api_key = provider[providerId].config?.api_key || '';
            this.configModal.show = true;
        },

        async saveConfig() {
            try {
                const res = await fetch(`/admin/ai-providers/${this.configModal.providerId}/config`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify(this.configModal.data)
                });
                const data = await res.json();
                if (data.success) {
                    this.showToast(data.message, 'success');
                    this.configModal.show = false;
                    setTimeout(() => location.reload(), 1500);
                } else {
                    this.showToast(data.message, 'error');
                }
            } catch (e) {
                this.showToast('เกิดข้อผิดพลาด', 'error');
            }
        },

        openTestChat(providerId) {
            const provider = @json($providers->keyBy('id'));
            this.chatModal.providerId = providerId;
            this.chatModal.providerName = provider[providerId].display_name;
            this.chatModal.models = provider[providerId].models.filter(m => m.is_active);
            this.chatModal.selectedModel = this.chatModal.models[0]?.id || '';
            this.chatModal.messages = [];
            this.chatModal.input = '';
            this.chatModal.show = true;
        },

        async sendTestMessage() {
            if (!this.chatModal.input.trim() || !this.chatModal.selectedModel) return;

            const userMsg = this.chatModal.input.trim();
            this.chatModal.messages.push({ role: 'user', content: userMsg });
            this.chatModal.input = '';
            this.chatModal.loading = true;

            // Scroll to bottom
            this.$nextTick(() => {
                const container = document.getElementById('chat-messages');
                container.scrollTop = container.scrollHeight;
            });

            try {
                const res = await fetch(`/admin/ai-providers/${this.chatModal.providerId}/test-chat`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({
                        message: userMsg,
                        model_id: this.chatModal.selectedModel
                    })
                });
                const data = await res.json();

                if (data.success) {
                    this.chatModal.messages.push({
                        role: 'ai',
                        content: data.response,
                        meta: {
                            model: data.model,
                            time: data.response_time_ms,
                            tokens: data.usage?.total_tokens || 0
                        }
                    });
                } else {
                    this.chatModal.messages.push({
                        role: 'ai',
                        content: '❌ ' + data.message
                    });
                }
            } catch (e) {
                this.chatModal.messages.push({
                    role: 'ai',
                    content: '❌ เกิดข้อผิดพลาดในการเชื่อมต่อ'
                });
            }

            this.chatModal.loading = false;
            this.$nextTick(() => {
                const container = document.getElementById('chat-messages');
                container.scrollTop = container.scrollHeight;
            });
        },

        refreshStatus() {
            this.showToast('กำลังรีเฟรช...', 'info');
            setTimeout(() => location.reload(), 500);
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
            toast.className = 'toast';
            toast.innerHTML = `
                <div class="rounded-xl shadow-2xl p-4 flex items-center gap-3 min-w-[280px]" style="background: ${color.bg}; color: white;">
                    <i class="fas fa-${color.icon} text-xl"></i>
                    <span class="font-medium">${message}</span>
                </div>
            `;
            document.getElementById('toast-container').appendChild(toast);
            setTimeout(() => toast.remove(), 3500);
        }
    }
}
</script>
@endpush
