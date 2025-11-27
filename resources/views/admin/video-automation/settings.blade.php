@extends('layouts.admin')

@section('title', 'ตั้งค่า API - Video Automation')

@section('styles')
<style>
    /* การ์ด Glassmorphism */
    .glass-card {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    .dark .glass-card {
        background: rgba(30, 41, 59, 0.8);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    /* ปุ่ม Gradient */
    .btn-gradient-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        transition: all 0.3s ease;
    }
    .btn-gradient-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
    }

    /* Input Focus Effect */
    .input-modern:focus {
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
    }

    /* Password Toggle */
    .password-toggle {
        cursor: pointer;
        transition: color 0.2s;
    }
    .password-toggle:hover {
        color: #667eea;
    }

    /* Section Headers */
    .section-header {
        position: relative;
        padding-left: 1rem;
    }
    .section-header::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        border-radius: 2px;
        background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
    }

    /* Status Badge */
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 500;
    }
    .status-badge.configured {
        background: rgba(16, 185, 129, 0.1);
        color: #10b981;
    }
    .status-badge.not-configured {
        background: rgba(239, 68, 68, 0.1);
        color: #ef4444;
    }
</style>
@endsection

@section('content')
<div class="container mx-auto px-4 py-8" x-data="settingsPage()">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <a href="{{ route('admin.video-automation.index') }}"
                   class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    ตั้งค่า API
                </h1>
            </div>
            <p class="text-gray-600 dark:text-gray-400">
                กำหนดค่า API Keys สำหรับเชื่อมต่อกับบริการต่างๆ
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.video-automation.documentation') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 rounded-lg hover:bg-blue-200 dark:hover:bg-blue-900/50 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                คู่มือการหา API Keys
            </a>
        </div>
    </div>

    {{-- Alert Messages --}}
    @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-xl flex items-center gap-3">
            <svg class="w-6 h-6 text-green-600 dark:text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="text-green-700 dark:text-green-300">{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 p-4 bg-red-100 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-xl flex items-center gap-3">
            <svg class="w-6 h-6 text-red-600 dark:text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="text-red-700 dark:text-red-300">{{ session('error') }}</span>
        </div>
    @endif

    <form action="{{ route('admin.video-automation.settings.update') }}" method="POST" class="space-y-8">
        @csrf
        @method('PUT')

        {{-- Suno AI Settings --}}
        <div class="glass-card rounded-2xl p-6 shadow-lg">
            <div class="flex items-center justify-between mb-6">
                <div class="section-header">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                        <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
                            </svg>
                        </span>
                        Suno AI
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 ml-13">
                        บริการสร้างเพลงด้วย AI
                    </p>
                </div>
                @php
                    $sunoConfigured = !empty($settings['suno_api_key'] ?? '');
                @endphp
                <span class="status-badge {{ $sunoConfigured ? 'configured' : 'not-configured' }}">
                    <span class="w-2 h-2 rounded-full {{ $sunoConfigured ? 'bg-green-500' : 'bg-red-500' }}"></span>
                    {{ $sunoConfigured ? 'เชื่อมต่อแล้ว' : 'ยังไม่ได้ตั้งค่า' }}
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Suno API Key --}}
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        API Key <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input :type="showSunoKey ? 'text' : 'password'"
                               name="suno_api_key"
                               value="{{ $settings['suno_api_key'] ?? '' }}"
                               class="input-modern w-full px-4 py-3 pr-12 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition"
                               placeholder="sk-xxxx-xxxx-xxxx">
                        <button type="button"
                                @click="showSunoKey = !showSunoKey"
                                class="password-toggle absolute right-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <svg x-show="!showSunoKey" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg x-show="showSunoKey" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        รับ API Key ได้ที่ <a href="https://suno.ai" target="_blank" class="text-purple-600 hover:underline">suno.ai</a>
                    </p>
                </div>

                {{-- Suno API URL --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        API URL
                    </label>
                    <input type="url"
                           name="suno_api_url"
                           value="{{ $settings['suno_api_url'] ?? 'https://api.suno.ai/v1' }}"
                           class="input-modern w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition"
                           placeholder="https://api.suno.ai/v1">
                </div>

                {{-- Suno Webhook URL --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Webhook URL
                    </label>
                    <input type="url"
                           name="suno_webhook_url"
                           value="{{ $settings['suno_webhook_url'] ?? '' }}"
                           class="input-modern w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition"
                           placeholder="https://yourdomain.com/webhooks/suno">
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        URL สำหรับรับแจ้งเตือนเมื่อเพลงสร้างเสร็จ (ถ้ามี)
                    </p>
                </div>
            </div>

            {{-- Test Connection Button --}}
            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                <button type="button"
                        @click="testConnection('suno')"
                        :disabled="testingSuno"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400 rounded-lg hover:bg-purple-200 dark:hover:bg-purple-900/50 transition disabled:opacity-50">
                    <svg x-show="!testingSuno" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <svg x-show="testingSuno" class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="testingSuno ? 'กำลังทดสอบ...' : 'ทดสอบการเชื่อมต่อ'"></span>
                </button>
            </div>
        </div>

        {{-- Freepik AI Settings --}}
        <div class="glass-card rounded-2xl p-6 shadow-lg">
            <div class="flex items-center justify-between mb-6">
                <div class="section-header">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                        <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-cyan-500 to-blue-500 flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </span>
                        Freepik AI
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 ml-13">
                        บริการสร้างภาพด้วย AI
                    </p>
                </div>
                @php
                    $freepikConfigured = !empty($settings['freepik_api_key'] ?? '');
                @endphp
                <span class="status-badge {{ $freepikConfigured ? 'configured' : 'not-configured' }}">
                    <span class="w-2 h-2 rounded-full {{ $freepikConfigured ? 'bg-green-500' : 'bg-red-500' }}"></span>
                    {{ $freepikConfigured ? 'เชื่อมต่อแล้ว' : 'ยังไม่ได้ตั้งค่า' }}
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Freepik API Key --}}
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        API Key <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input :type="showFreepikKey ? 'text' : 'password'"
                               name="freepik_api_key"
                               value="{{ $settings['freepik_api_key'] ?? '' }}"
                               class="input-modern w-full px-4 py-3 pr-12 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition"
                               placeholder="fpk-xxxx-xxxx-xxxx">
                        <button type="button"
                                @click="showFreepikKey = !showFreepikKey"
                                class="password-toggle absolute right-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <svg x-show="!showFreepikKey" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg x-show="showFreepikKey" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        รับ API Key ได้ที่ <a href="https://www.freepik.com/api" target="_blank" class="text-cyan-600 hover:underline">freepik.com/api</a>
                    </p>
                </div>

                {{-- Freepik API URL --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        API URL
                    </label>
                    <input type="url"
                           name="freepik_api_url"
                           value="{{ $settings['freepik_api_url'] ?? 'https://api.freepik.com/v1' }}"
                           class="input-modern w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition"
                           placeholder="https://api.freepik.com/v1">
                </div>

                {{-- Default Image Style --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        สไตล์ภาพเริ่มต้น
                    </label>
                    <select name="freepik_default_style"
                            class="input-modern w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition">
                        <option value="realistic" {{ ($settings['freepik_default_style'] ?? '') == 'realistic' ? 'selected' : '' }}>Realistic (สมจริง)</option>
                        <option value="anime" {{ ($settings['freepik_default_style'] ?? '') == 'anime' ? 'selected' : '' }}>Anime (อนิเมะ)</option>
                        <option value="3d" {{ ($settings['freepik_default_style'] ?? '') == '3d' ? 'selected' : '' }}>3D Render</option>
                        <option value="cartoon" {{ ($settings['freepik_default_style'] ?? '') == 'cartoon' ? 'selected' : '' }}>Cartoon (การ์ตูน)</option>
                        <option value="watercolor" {{ ($settings['freepik_default_style'] ?? '') == 'watercolor' ? 'selected' : '' }}>Watercolor (สีน้ำ)</option>
                        <option value="oil_painting" {{ ($settings['freepik_default_style'] ?? '') == 'oil_painting' ? 'selected' : '' }}>Oil Painting (สีน้ำมัน)</option>
                        <option value="digital_art" {{ ($settings['freepik_default_style'] ?? '') == 'digital_art' ? 'selected' : '' }}>Digital Art</option>
                        <option value="minimalist" {{ ($settings['freepik_default_style'] ?? '') == 'minimalist' ? 'selected' : '' }}>Minimalist</option>
                    </select>
                </div>
            </div>

            {{-- Test Connection Button --}}
            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                <button type="button"
                        @click="testConnection('freepik')"
                        :disabled="testingFreepik"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-cyan-100 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-400 rounded-lg hover:bg-cyan-200 dark:hover:bg-cyan-900/50 transition disabled:opacity-50">
                    <svg x-show="!testingFreepik" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <svg x-show="testingFreepik" class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="testingFreepik ? 'กำลังทดสอบ...' : 'ทดสอบการเชื่อมต่อ'"></span>
                </button>
            </div>
        </div>

        {{-- YouTube API Settings --}}
        <div class="glass-card rounded-2xl p-6 shadow-lg">
            <div class="flex items-center justify-between mb-6">
                <div class="section-header">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                        <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-red-500 to-red-600 flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                            </svg>
                        </span>
                        YouTube
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 ml-13">
                        อัปโหลดวิดีโอไปยัง YouTube
                    </p>
                </div>
                @php
                    $youtubeConfigured = !empty($settings['youtube_client_id'] ?? '') && !empty($settings['youtube_client_secret'] ?? '');
                @endphp
                <span class="status-badge {{ $youtubeConfigured ? 'configured' : 'not-configured' }}">
                    <span class="w-2 h-2 rounded-full {{ $youtubeConfigured ? 'bg-green-500' : 'bg-red-500' }}"></span>
                    {{ $youtubeConfigured ? 'เชื่อมต่อแล้ว' : 'ยังไม่ได้ตั้งค่า' }}
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- YouTube Client ID --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Client ID <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="youtube_client_id"
                           value="{{ $settings['youtube_client_id'] ?? '' }}"
                           class="input-modern w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent transition"
                           placeholder="xxxx.apps.googleusercontent.com">
                </div>

                {{-- YouTube Client Secret --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Client Secret <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input :type="showYoutubeSecret ? 'text' : 'password'"
                               name="youtube_client_secret"
                               value="{{ $settings['youtube_client_secret'] ?? '' }}"
                               class="input-modern w-full px-4 py-3 pr-12 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent transition"
                               placeholder="GOCSPX-xxxx">
                        <button type="button"
                                @click="showYoutubeSecret = !showYoutubeSecret"
                                class="password-toggle absolute right-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <svg x-show="!showYoutubeSecret" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg x-show="showYoutubeSecret" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Redirect URI --}}
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Redirect URI
                    </label>
                    <div class="flex gap-2">
                        <input type="text"
                               readonly
                               value="{{ url('/admin/video-automation/youtube/callback') }}"
                               class="flex-1 px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 cursor-not-allowed">
                        <button type="button"
                                onclick="navigator.clipboard.writeText('{{ url('/admin/video-automation/youtube/callback') }}'); alert('คัดลอกแล้ว!');"
                                class="px-4 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                        </button>
                    </div>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        นำ URL นี้ไปใส่ใน Google Cloud Console → Credentials → OAuth 2.0 Client → Authorized redirect URIs
                    </p>
                </div>
            </div>

            {{-- YouTube Connection Status --}}
            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                @php
                    $youtubePlatform = \App\Models\VideoAutoPlatform::ofType('youtube')->connected()->first();
                @endphp

                @if($youtubePlatform)
                    <div class="flex items-center justify-between p-4 bg-green-50 dark:bg-green-900/20 rounded-xl">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                                <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $youtubePlatform->account_name }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">เชื่อมต่อแล้ว • {{ $youtubePlatform->connected_at?->diffForHumans() }}</p>
                            </div>
                        </div>
                        <form action="{{ route('admin.video-automation.platforms.disconnect', $youtubePlatform) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    onclick="return confirm('ต้องการยกเลิกการเชื่อมต่อ YouTube หรือไม่?')"
                                    class="px-4 py-2 text-red-600 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition">
                                ยกเลิกการเชื่อมต่อ
                            </button>
                        </form>
                    </div>
                @else
                    <a href="{{ route('admin.video-automation.youtube.auth') }}"
                       class="inline-flex items-center gap-2 px-6 py-3 bg-red-600 hover:bg-red-700 text-white rounded-xl transition {{ !$youtubeConfigured ? 'opacity-50 pointer-events-none' : '' }}">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                        </svg>
                        เชื่อมต่อ YouTube
                    </a>
                    @if(!$youtubeConfigured)
                        <p class="mt-2 text-sm text-yellow-600 dark:text-yellow-400">
                            กรุณากรอก Client ID และ Client Secret ก่อนเชื่อมต่อ
                        </p>
                    @endif
                @endif
            </div>
        </div>

        {{-- Video Settings --}}
        <div class="glass-card rounded-2xl p-6 shadow-lg">
            <div class="section-header mb-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                    <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-green-500 to-emerald-500 flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                    </span>
                    ตั้งค่าวิดีโอ
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 ml-13">
                    ค่าเริ่มต้นสำหรับการสร้างวิดีโอ
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                {{-- Default Video Resolution --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ความละเอียดเริ่มต้น
                    </label>
                    <select name="video_default_resolution"
                            class="input-modern w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
                        <option value="1080p" {{ ($settings['video_default_resolution'] ?? '1080p') == '1080p' ? 'selected' : '' }}>1080p (Full HD)</option>
                        <option value="720p" {{ ($settings['video_default_resolution'] ?? '') == '720p' ? 'selected' : '' }}>720p (HD)</option>
                        <option value="4k" {{ ($settings['video_default_resolution'] ?? '') == '4k' ? 'selected' : '' }}>4K (Ultra HD)</option>
                    </select>
                </div>

                {{-- Default Frame Rate --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Frame Rate
                    </label>
                    <select name="video_default_fps"
                            class="input-modern w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
                        <option value="30" {{ ($settings['video_default_fps'] ?? '30') == '30' ? 'selected' : '' }}>30 FPS</option>
                        <option value="24" {{ ($settings['video_default_fps'] ?? '') == '24' ? 'selected' : '' }}>24 FPS (Cinematic)</option>
                        <option value="60" {{ ($settings['video_default_fps'] ?? '') == '60' ? 'selected' : '' }}>60 FPS (Smooth)</option>
                    </select>
                </div>

                {{-- Default Image Duration --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ระยะเวลาแต่ละภาพ (วินาที)
                    </label>
                    <input type="number"
                           name="video_image_duration"
                           value="{{ $settings['video_image_duration'] ?? 5 }}"
                           min="1"
                           max="30"
                           class="input-modern w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
                </div>

                {{-- Default Transition --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Transition เริ่มต้น
                    </label>
                    <select name="video_default_transition"
                            class="input-modern w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
                        <option value="fade" {{ ($settings['video_default_transition'] ?? 'fade') == 'fade' ? 'selected' : '' }}>Fade</option>
                        <option value="crossfade" {{ ($settings['video_default_transition'] ?? '') == 'crossfade' ? 'selected' : '' }}>Crossfade</option>
                        <option value="slide_left" {{ ($settings['video_default_transition'] ?? '') == 'slide_left' ? 'selected' : '' }}>Slide Left</option>
                        <option value="slide_right" {{ ($settings['video_default_transition'] ?? '') == 'slide_right' ? 'selected' : '' }}>Slide Right</option>
                        <option value="zoom_in" {{ ($settings['video_default_transition'] ?? '') == 'zoom_in' ? 'selected' : '' }}>Zoom In</option>
                        <option value="zoom_out" {{ ($settings['video_default_transition'] ?? '') == 'zoom_out' ? 'selected' : '' }}>Zoom Out</option>
                        <option value="dissolve" {{ ($settings['video_default_transition'] ?? '') == 'dissolve' ? 'selected' : '' }}>Dissolve</option>
                    </select>
                </div>

                {{-- Transition Duration --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ระยะเวลา Transition (วินาที)
                    </label>
                    <input type="number"
                           name="video_transition_duration"
                           value="{{ $settings['video_transition_duration'] ?? 1 }}"
                           min="0.5"
                           max="5"
                           step="0.5"
                           class="input-modern w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
                </div>

                {{-- Enable Ken Burns Effect --}}
                <div class="flex items-center gap-3">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox"
                               name="video_ken_burns_effect"
                               value="1"
                               {{ ($settings['video_ken_burns_effect'] ?? true) ? 'checked' : '' }}
                               class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-green-600"></div>
                    </label>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Ken Burns Effect</span>
                </div>
            </div>
        </div>

        {{-- Storage Settings --}}
        <div class="glass-card rounded-2xl p-6 shadow-lg">
            <div class="section-header mb-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                    <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-orange-500 to-amber-500 flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                        </svg>
                    </span>
                    ตั้งค่าที่เก็บไฟล์
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 ml-13">
                    กำหนดที่เก็บไฟล์และนโยบายการลบ
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Storage Path --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        โฟลเดอร์เก็บไฟล์
                    </label>
                    <input type="text"
                           name="storage_path"
                           value="{{ $settings['storage_path'] ?? 'video-automation' }}"
                           class="input-modern w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-orange-500 focus:border-transparent transition"
                           placeholder="video-automation">
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        ภายใน storage/app/
                    </p>
                </div>

                {{-- Auto Delete Days --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ลบไฟล์อัตโนมัติหลัง (วัน)
                    </label>
                    <input type="number"
                           name="auto_delete_days"
                           value="{{ $settings['auto_delete_days'] ?? 30 }}"
                           min="0"
                           max="365"
                           class="input-modern w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-orange-500 focus:border-transparent transition">
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        ใส่ 0 เพื่อไม่ลบอัตโนมัติ
                    </p>
                </div>

                {{-- Max Storage Size --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ขนาดที่เก็บสูงสุด (GB)
                    </label>
                    <input type="number"
                           name="max_storage_gb"
                           value="{{ $settings['max_storage_gb'] ?? 50 }}"
                           min="1"
                           max="1000"
                           class="input-modern w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-orange-500 focus:border-transparent transition">
                </div>

                {{-- Keep Original Files --}}
                <div class="flex items-center gap-3 self-end pb-3">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox"
                               name="keep_original_files"
                               value="1"
                               {{ ($settings['keep_original_files'] ?? false) ? 'checked' : '' }}
                               class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-300 dark:peer-focus:ring-orange-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-orange-600"></div>
                    </label>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">เก็บไฟล์ต้นฉบับ (เพลง, ภาพ)</span>
                </div>
            </div>
        </div>

        {{-- Submit Button --}}
        <div class="flex justify-end gap-4">
            <a href="{{ route('admin.video-automation.index') }}"
               class="px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                ยกเลิก
            </a>
            <button type="submit"
                    class="btn-gradient-primary px-8 py-3 text-white rounded-xl font-semibold">
                บันทึกการตั้งค่า
            </button>
        </div>
    </form>
</div>

{{-- Test Connection Modal --}}
<div x-show="showTestModal"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
     @click.self="showTestModal = false">
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md mx-4 shadow-2xl"
         x-transition>
        <div class="text-center">
            <div class="mb-4" :class="testResult?.success ? 'text-green-500' : 'text-red-500'">
                <svg x-show="testResult?.success" class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <svg x-show="!testResult?.success" class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2" x-text="testResult?.success ? 'เชื่อมต่อสำเร็จ!' : 'เชื่อมต่อไม่สำเร็จ'"></h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6" x-text="testResult?.message"></p>
            <button type="button"
                    @click="showTestModal = false"
                    class="px-6 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                ปิด
            </button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function settingsPage() {
    return {
        showSunoKey: false,
        showFreepikKey: false,
        showYoutubeSecret: false,
        testingSuno: false,
        testingFreepik: false,
        showTestModal: false,
        testResult: null,

        async testConnection(service) {
            if (service === 'suno') {
                this.testingSuno = true;
            } else if (service === 'freepik') {
                this.testingFreepik = true;
            }

            try {
                const response = await fetch(`{{ url('/admin/video-automation/test-api') }}/${service}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const result = await response.json();
                this.testResult = result;
                this.showTestModal = true;

            } catch (error) {
                this.testResult = {
                    success: false,
                    message: 'เกิดข้อผิดพลาดในการทดสอบ: ' + error.message
                };
                this.showTestModal = true;
            } finally {
                if (service === 'suno') {
                    this.testingSuno = false;
                } else if (service === 'freepik') {
                    this.testingFreepik = false;
                }
            }
        }
    }
}
</script>
@endsection
