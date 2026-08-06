{{--
    หน้าตั้งค่าระบบรับสมัคร LINE AI
    จัดการ AI Settings และ Provider
    Redesigned V3 - Modern & Beautiful UI
--}}
@extends('layouts.admin-v3')

@section('title', 'ตั้งค่าระบบรับสมัคร - LINE AI')

@section('content')
<div class="min-h-screen bg-gray-100/50 dark:bg-slate-900 py-6 px-4 sm:px-6 lg:px-8" x-data="settingsManager()">

    <!-- Animated Premium Header -->
    <div class="relative mb-8 overflow-hidden rounded-3xl bg-gradient-to-br from-[#00B900] via-[#00C900] to-[#00E600] p-8 shadow-2xl">
        <!-- Animated Background Pattern -->
        <div class="absolute inset-0 opacity-20">
            <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS1vcGFjaXR5PSIwLjMiIHN0cm9rZS13aWR0aD0iMSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNncmlkKSIvPjwvc3ZnPg==')]"></div>
        </div>

        <!-- Floating Particles -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute top-10 left-10 w-3 h-3 bg-white/30 rounded-full animate-ping"></div>
            <div class="absolute top-20 right-20 w-4 h-4 bg-white/20 rounded-full animate-pulse"></div>
            <div class="absolute bottom-10 left-1/3 w-2 h-2 bg-white/40 rounded-full animate-bounce"></div>
            <div class="absolute bottom-20 right-1/4 w-3 h-3 bg-white/25 rounded-full animate-pulse delay-300"></div>
        </div>

        <div class="relative flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div class="flex items-center gap-5">
                <!-- LINE Logo -->
                <div class="w-20 h-20 rounded-2xl bg-white/20 backdrop-blur-lg flex items-center justify-center shadow-xl border border-white/30 transform hover:scale-110 transition-transform duration-300">
                    <svg class="w-12 h-12 text-white drop-shadow-lg" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                    </svg>
                </div>
                <div>
                    <!-- Breadcrumb -->
                    <nav class="flex items-center gap-2 text-sm text-white/70 mb-2">
                        <a href="{{ route('admin.line-recruitment.index') }}" class="hover:text-white transition-colors">LINE Recruitment</a>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        <span class="text-white">ตั้งค่า AI</span>
                    </nav>
                    <h1 class="text-3xl md:text-4xl font-black text-white drop-shadow-lg tracking-tight">
                        ⚙️ ตั้งค่าระบบรับสมัคร AI
                    </h1>
                    <p class="text-white/80 text-lg mt-2 font-medium">
                        ควบคุมและปรับแต่ง AI เพื่อตอบคำถามและรับสมัครสมาชิกอัตโนมัติ
                    </p>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.line-recruitment.index') }}"
                   class="px-5 py-3 bg-white/20 backdrop-blur-md border border-white/30 text-white rounded-xl hover:bg-white/30 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1 flex items-center gap-2 font-semibold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    กลับหน้าหลัก
                </a>
                <a href="{{ route('admin.line-bot.ai.create') }}"
                   class="px-5 py-3 bg-white text-[#00B900] rounded-xl hover:bg-green-50 transition-all duration-300 shadow-xl hover:shadow-2xl transform hover:-translate-y-1 font-bold flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    เพิ่ม AI ใหม่
                </a>
            </div>
        </div>
    </div>

    <!-- Alerts -->
    @if(session('success'))
        <div class="mb-6 p-5 rounded-2xl bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/30 dark:to-emerald-900/30 border-2 border-green-200 dark:border-green-700 animate-fade-in shadow-lg">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-green-400 to-emerald-500 flex items-center justify-center flex-shrink-0 shadow-lg shadow-green-500/30">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div>
                    <h4 class="font-bold text-green-900 dark:text-green-100">สำเร็จ!</h4>
                    <p class="text-green-700 dark:text-green-300">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    @if(session('info'))
        <div class="mb-6 p-5 rounded-2xl bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/30 dark:to-indigo-900/30 border-2 border-blue-200 dark:border-blue-700 animate-fade-in shadow-lg">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-400 to-indigo-500 flex items-center justify-center flex-shrink-0 shadow-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h4 class="font-bold text-blue-900 dark:text-blue-100">แจ้งเตือน</h4>
                    <p class="text-blue-700 dark:text-blue-300">{{ session('info') }}</p>
                </div>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 p-5 rounded-2xl bg-gradient-to-r from-red-50 to-pink-50 dark:from-red-900/30 dark:to-pink-900/30 border-2 border-red-200 dark:border-red-700 animate-fade-in shadow-lg">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-red-400 to-pink-500 flex items-center justify-center flex-shrink-0 shadow-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h4 class="font-bold text-red-900 dark:text-red-100">พบข้อผิดพลาด</h4>
                    <ul class="mt-1 space-y-1 text-red-700 dark:text-red-300">
                        @foreach($errors->all() as $error)
                            <li class="flex items-center gap-2">
                                <span class="w-1.5 h-1.5 bg-red-500 rounded-full"></span>
                                {{ $error }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <!-- AI Settings Cards -->
    <div class="space-y-8">
        @forelse($aiSettings as $setting)
            <div class="glass-fusion dark:bg-slate-800 rounded-3xl shadow-xl border border-gray-200/50 dark:border-slate-700 overflow-hidden hover:shadow-2xl transition-all duration-500 group">
                <!-- Card Header with Provider Gradient -->
                <div class="relative overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-br
                        @if($setting->provider === 'openai') from-emerald-500 via-green-600 to-teal-700
                        @elseif($setting->provider === 'deepseek') from-blue-500 via-indigo-600 to-purple-700
                        @elseif($setting->provider === 'anthropic') from-orange-500 via-red-600 to-pink-700
                        @elseif($setting->provider === 'gemini') from-purple-500 via-fuchsia-600 to-pink-700
                        @else from-gray-500 via-gray-600 to-gray-700
                        @endif"></div>

                    <!-- Animated Background -->
                    <div class="absolute inset-0 bg-gradient-to-r from-white/10 to-transparent"></div>
                    <div class="absolute -right-10 -top-10 w-40 h-40 bg-white/10 rounded-full blur-2xl"></div>

                    <div class="relative p-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <!-- Provider Icon -->
                            <div class="w-16 h-16 rounded-2xl bg-white/20 backdrop-blur-lg flex items-center justify-center shadow-xl border border-white/30 group-hover:scale-110 transition-transform duration-300">
                                @if($setting->provider === 'openai')
                                    <svg class="w-9 h-9 text-white" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M22.2819 9.8211a5.9847 5.9847 0 0 0-.5157-4.9108 6.0462 6.0462 0 0 0-6.5098-2.9A6.0651 6.0651 0 0 0 4.9807 4.1818a5.9847 5.9847 0 0 0-3.9977 2.9 6.0462 6.0462 0 0 0 .7427 7.0966 5.98 5.98 0 0 0 .511 4.9107 6.051 6.051 0 0 0 6.5146 2.9001A5.9847 5.9847 0 0 0 13.2599 24a6.0557 6.0557 0 0 0 5.7718-4.2058 5.9894 5.9894 0 0 0 3.9977-2.9001 6.0557 6.0557 0 0 0-.7475-7.0729zm-9.022 12.6081a4.4755 4.4755 0 0 1-2.8764-1.0408l.1419-.0804 4.7783-2.7582a.7948.7948 0 0 0 .3927-.6813v-6.7369l2.02 1.1686a.071.071 0 0 1 .038.052v5.5826a4.504 4.504 0 0 1-4.4945 4.4944zm-9.6607-4.1254a4.4708 4.4708 0 0 1-.5346-3.0137l.142.0852 4.783 2.7582a.7712.7712 0 0 0 .7806 0l5.8428-3.3685v2.3324a.0804.0804 0 0 1-.0332.0615L9.74 19.9502a4.4992 4.4992 0 0 1-6.1408-1.6464zM2.3408 7.8956a4.485 4.485 0 0 1 2.3655-1.9728V11.6a.7664.7664 0 0 0 .3879.6765l5.8144 3.3543-2.0201 1.1685a.0757.0757 0 0 1-.071 0l-4.8303-2.7865A4.504 4.504 0 0 1 2.3408 7.872zm16.5963 3.8558L13.1038 8.364 15.1192 7.2a.0757.0757 0 0 1 .071 0l4.8303 2.7913a4.4944 4.4944 0 0 1-.6765 8.1042v-5.6772a.79.79 0 0 0-.407-.667zm2.0107-3.0231l-.142-.0852-4.7735-2.7818a.7759.7759 0 0 0-.7854 0L9.409 9.2297V6.8974a.0662.0662 0 0 1 .0284-.0615l4.8303-2.7866a4.4992 4.4992 0 0 1 6.6802 4.66zM8.3065 12.863l-2.02-1.1638a.0804.0804 0 0 1-.038-.0567V6.0742a4.4992 4.4992 0 0 1 7.3757-3.4537l-.142.0805L8.704 5.459a.7948.7948 0 0 0-.3927.6813zm1.0976-2.3654l2.602-1.4998 2.6069 1.4998v2.9994l-2.5974 1.4997-2.6067-1.4997Z"/>
                                    </svg>
                                @elseif($setting->provider === 'anthropic')
                                    <svg class="w-9 h-9 text-white" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17.507 8.048l-4.97 12.905h-2.62L4.493 8.048h2.54l3.627 10.142 3.706-10.142h3.141z"/>
                                    </svg>
                                @else
                                    <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                    </svg>
                                @endif
                            </div>
                            <div>
                                <h3 class="text-2xl font-bold text-white drop-shadow-md">{{ $setting->name }}</h3>
                                <p class="text-white/80 font-medium">
                                    {{ $providers[$setting->provider] ?? $setting->provider }}
                                    @if($setting->model)
                                        <span class="text-white/60">•</span>
                                        <span class="text-white/70">{{ $setting->model }}</span>
                                    @endif
                                </p>
                            </div>
                        </div>

                        <!-- Status Badges -->
                        <div class="flex flex-wrap gap-2">
                            @if($setting->is_active)
                                <span class="px-4 py-2 bg-white/20 backdrop-blur-md rounded-full text-sm font-bold text-white shadow-lg border border-white/30 flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 bg-green-400 rounded-full animate-pulse shadow-lg shadow-green-400/50"></span>
                                    Active
                                </span>
                            @else
                                <span class="px-4 py-2 bg-black/20 backdrop-blur-md rounded-full text-sm font-semibold text-white/70 border border-white/10">
                                    Inactive
                                </span>
                            @endif
                            @if($setting->use_for_recruitment)
                                <span class="px-4 py-2 bg-blue-500/30 backdrop-blur-md rounded-full text-sm font-bold text-white shadow-lg border border-blue-300/30 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    Recruitment
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Form Content -->
                <form action="{{ route('admin.line-recruitment.settings.update', $setting->id) }}" method="POST" class="p-6 lg:p-8">
                    @csrf
                    @method('PUT')

                    <!-- Main Settings Grid -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Left Column: Basic Info & Parameters -->
                        <div class="space-y-6">
                            <!-- Basic Info Card -->
                            <div class="glass-fusion dark:bg-slate-700/50 rounded-2xl p-6 border border-gray-200 dark:border-slate-600 shadow-lg">
                                <h4 class="flex items-center gap-2 text-lg font-bold text-gray-900 dark:text-white mb-5">
                                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    ข้อมูลพื้นฐาน
                                </h4>

                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">ชื่อการตั้งค่า</label>
                                        <input type="text" name="name" value="{{ $setting->name }}" required
                                               class="w-full px-4 py-3 border-2 border-gray-200 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">AI Provider</label>
                                        <select name="provider" required
                                                class="w-full px-4 py-3 border-2 border-gray-200 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                                            @foreach($providers as $key => $label)
                                                <option value="{{ $key }}" {{ $setting->provider === $key ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">API Key</label>
                                        <input type="password" name="api_key" placeholder="ไม่เปลี่ยนให้เว้นว่าง"
                                               class="w-full px-4 py-3 border-2 border-gray-200 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-mono">
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ปัจจุบัน: {{ $setting->getMaskedApiKey() }}</p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Model</label>
                                        <input type="text" name="model" value="{{ $setting->model }}"
                                               placeholder="gpt-4, gemini-pro, claude-3-sonnet"
                                               class="w-full px-4 py-3 border-2 border-gray-200 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                                    </div>
                                </div>
                            </div>

                            <!-- Parameters Card -->
                            <div class="glass-fusion dark:bg-slate-700/50 rounded-2xl p-6 border border-gray-200 dark:border-slate-600 shadow-lg">
                                <h4 class="flex items-center gap-2 text-lg font-bold text-gray-900 dark:text-white mb-5">
                                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                                        </svg>
                                    </div>
                                    พารามิเตอร์
                                </h4>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Temperature</label>
                                        <input type="number" name="temperature" value="{{ $setting->temperature }}" step="0.1" min="0" max="2" required
                                               class="w-full px-4 py-3 border-2 border-gray-200 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-4 focus:ring-purple-500/20 focus:border-purple-500 transition-all">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Max Tokens</label>
                                        <input type="number" name="max_tokens" value="{{ $setting->max_tokens }}" min="1" required
                                               class="w-full px-4 py-3 border-2 border-gray-200 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-4 focus:ring-purple-500/20 focus:border-purple-500 transition-all">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Max Turns</label>
                                        <input type="number" name="max_conversation_turns" value="{{ $setting->max_conversation_turns ?? 50 }}" min="1" max="100"
                                               class="w-full px-4 py-3 border-2 border-gray-200 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-4 focus:ring-purple-500/20 focus:border-purple-500 transition-all">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Memory Limit</label>
                                        <input type="number" name="conversation_memory_limit" value="{{ $setting->conversation_memory_limit }}" min="1"
                                               class="w-full px-4 py-3 border-2 border-gray-200 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-4 focus:ring-purple-500/20 focus:border-purple-500 transition-all">
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">สไตล์การตอบ</label>
                                    <select name="response_style" required
                                            class="w-full px-4 py-3 border-2 border-gray-200 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-4 focus:ring-purple-500/20 focus:border-purple-500 transition-all">
                                        @foreach($responseStyles as $key => $label)
                                            <option value="{{ $key }}" {{ $setting->response_style === $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Prompts & Messages -->
                        <div class="space-y-6">
                            <!-- System Prompts Card -->
                            <div class="glass-fusion dark:bg-slate-700/50 rounded-2xl p-6 border border-gray-200 dark:border-slate-600 shadow-lg">
                                <h4 class="flex items-center gap-2 text-lg font-bold text-gray-900 dark:text-white mb-5">
                                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-orange-500 to-red-500 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                                        </svg>
                                    </div>
                                    System Prompts
                                </h4>

                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">System Prompt (ทั่วไป)</label>
                                        <textarea name="system_prompt" rows="3"
                                                  class="w-full px-4 py-3 border-2 border-gray-200 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-4 focus:ring-orange-500/20 focus:border-orange-500 transition-all">{{ $setting->system_prompt }}</textarea>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Recruitment System Prompt</label>
                                        <textarea name="recruitment_system_prompt" rows="4"
                                                  placeholder="คุณเป็นผู้ช่วยรับสมัครสมาชิกที่เป็นมิตร..."
                                                  class="w-full px-4 py-3 border-2 border-gray-200 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-4 focus:ring-orange-500/20 focus:border-orange-500 transition-all">{{ $setting->recruitment_system_prompt }}</textarea>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">ข้อความต้อนรับ</label>
                                        <textarea name="greeting_message" rows="2"
                                                  placeholder="สวัสดีค่ะ! ยินดีต้อนรับ..."
                                                  class="w-full px-4 py-3 border-2 border-gray-200 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-4 focus:ring-orange-500/20 focus:border-orange-500 transition-all">{{ $setting->greeting_message }}</textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Messages Card -->
                            <div class="glass-fusion dark:bg-slate-700/50 rounded-2xl p-6 border border-gray-200 dark:border-slate-600 shadow-lg">
                                <h4 class="flex items-center gap-2 text-lg font-bold text-gray-900 dark:text-white mb-5">
                                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-green-500 to-emerald-500 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                                        </svg>
                                    </div>
                                    ข้อความพิเศษ
                                </h4>

                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">ข้อความเมื่อหมดรอบ</label>
                                        <textarea name="max_turns_message" rows="2"
                                                  placeholder="ขอบคุณที่สนใจค่ะ..."
                                                  class="w-full px-4 py-3 border-2 border-gray-200 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-4 focus:ring-green-500/20 focus:border-green-500 transition-all">{{ $setting->max_turns_message }}</textarea>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">ข้อความนอกขอบเขต</label>
                                        <textarea name="off_topic_message" rows="2"
                                                  placeholder="ขออภัยค่ะ หัวข้อนี้..."
                                                  class="w-full px-4 py-3 border-2 border-gray-200 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-4 focus:ring-green-500/20 focus:border-green-500 transition-all">{{ $setting->off_topic_message }}</textarea>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">ข้อความ Fallback</label>
                                        <textarea name="fallback_message" rows="2"
                                                  class="w-full px-4 py-3 border-2 border-gray-200 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-4 focus:ring-green-500/20 focus:border-green-500 transition-all">{{ $setting->fallback_message }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Toggles Section -->
                    <div class="mt-8 p-6 glass-fusion dark:bg-slate-700/50 rounded-2xl border border-gray-200 dark:border-slate-600 shadow-lg">
                        <h4 class="flex items-center gap-2 text-lg font-bold text-gray-900 dark:text-white mb-5">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                                </svg>
                            </div>
                            การตั้งค่าเพิ่มเติม
                        </h4>

                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                            <label class="flex items-center gap-3 p-4 bg-white dark:bg-slate-800 rounded-xl border-2 border-gray-200 dark:border-slate-600 cursor-pointer hover:border-green-400 dark:hover:border-green-500 hover:shadow-md transition-all group">
                                <input type="checkbox" name="is_active" value="1" {{ $setting->is_active ? 'checked' : '' }}
                                       class="w-5 h-5 rounded border-gray-300 text-green-600 focus:ring-green-500">
                                <div>
                                    <span class="font-bold text-gray-900 dark:text-white text-sm">Active</span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">เปิดใช้งาน</span>
                                </div>
                            </label>
                            <label class="flex items-center gap-3 p-4 bg-white dark:bg-slate-800 rounded-xl border-2 border-gray-200 dark:border-slate-600 cursor-pointer hover:border-blue-400 dark:hover:border-blue-500 hover:shadow-md transition-all group">
                                <input type="checkbox" name="use_for_recruitment" value="1" {{ $setting->use_for_recruitment ? 'checked' : '' }}
                                       class="w-5 h-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <div>
                                    <span class="font-bold text-gray-900 dark:text-white text-sm">รับสมัคร</span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">Recruitment</span>
                                </div>
                            </label>
                            <label class="flex items-center gap-3 p-4 bg-white dark:bg-slate-800 rounded-xl border-2 border-gray-200 dark:border-slate-600 cursor-pointer hover:border-purple-400 dark:hover:border-purple-500 hover:shadow-md transition-all group">
                                <input type="checkbox" name="enable_topic_filtering" value="1" {{ $setting->enable_topic_filtering ? 'checked' : '' }}
                                       class="w-5 h-5 rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                                <div>
                                    <span class="font-bold text-gray-900 dark:text-white text-sm">Topic Filter</span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">กรองหัวข้อ</span>
                                </div>
                            </label>
                            <label class="flex items-center gap-3 p-4 bg-white dark:bg-slate-800 rounded-xl border-2 border-gray-200 dark:border-slate-600 cursor-pointer hover:border-orange-400 dark:hover:border-orange-500 hover:shadow-md transition-all group">
                                <input type="checkbox" name="enable_conversation_history" value="1" {{ $setting->enable_conversation_history ? 'checked' : '' }}
                                       class="w-5 h-5 rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                                <div>
                                    <span class="font-bold text-gray-900 dark:text-white text-sm">จำประวัติ</span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">History</span>
                                </div>
                            </label>
                            <label class="flex items-center gap-3 p-4 bg-white dark:bg-slate-800 rounded-xl border-2 border-gray-200 dark:border-slate-600 cursor-pointer hover:border-red-400 dark:hover:border-red-500 hover:shadow-md transition-all group">
                                <input type="checkbox" name="enable_fallback" value="1" {{ $setting->enable_fallback ? 'checked' : '' }}
                                       class="w-5 h-5 rounded border-gray-300 text-red-600 focus:ring-red-500">
                                <div>
                                    <span class="font-bold text-gray-900 dark:text-white text-sm">Fallback</span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">ข้อความสำรอง</span>
                                </div>
                            </label>
                            <label class="flex items-center gap-3 p-4 bg-white dark:bg-slate-800 rounded-xl border-2 border-gray-200 dark:border-slate-600 cursor-pointer hover:border-cyan-400 dark:hover:border-cyan-500 hover:shadow-md transition-all group">
                                <input type="checkbox" name="collect_user_info" value="1" {{ $setting->collect_user_info ? 'checked' : '' }}
                                       class="w-5 h-5 rounded border-gray-300 text-cyan-600 focus:ring-cyan-500">
                                <div>
                                    <span class="font-bold text-gray-900 dark:text-white text-sm">เก็บข้อมูล</span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">User Info</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Actions Footer -->
                    <div class="mt-8 flex flex-col sm:flex-row items-center justify-between gap-4 pt-6 border-t-2 border-gray-200 dark:border-slate-700">
                        <div class="flex flex-wrap gap-3">
                            <a href="{{ route('admin.line-recruitment.topic-boundaries', $setting->id) }}"
                               class="px-5 py-3 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/30 dark:to-indigo-900/30 border-2 border-blue-200 dark:border-blue-700 text-blue-700 dark:text-blue-300 rounded-xl hover:shadow-lg hover:-translate-y-0.5 transition-all font-semibold flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                                ขอบเขตหัวข้อ
                            </a>
                            <a href="{{ route('admin.line-recruitment.knowledge-base', $setting->id) }}"
                               class="px-5 py-3 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/30 dark:to-pink-900/30 border-2 border-purple-200 dark:border-purple-700 text-purple-700 dark:text-purple-300 rounded-xl hover:shadow-lg hover:-translate-y-0.5 transition-all font-semibold flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                </svg>
                                แหล่งข้อมูล
                            </a>
                            <button type="button" @click="testAi({{ $setting->id }})"
                                    class="px-5 py-3 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/30 dark:to-emerald-900/30 border-2 border-green-200 dark:border-green-700 text-green-700 dark:text-green-300 rounded-xl hover:shadow-lg hover:-translate-y-0.5 transition-all font-semibold flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                                ทดสอบ AI
                            </button>
                        </div>

                        <button type="submit"
                                class="px-8 py-4 bg-gradient-to-r from-[#00B900] to-[#00E600] text-white rounded-xl hover:shadow-2xl hover:shadow-green-500/30 transition-all transform hover:-translate-y-1 font-bold text-lg flex items-center gap-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            บันทึกการตั้งค่า
                        </button>
                    </div>
                </form>
            </div>
        @empty
            <!-- Empty State -->
            <div class="glass-fusion dark:bg-slate-800 rounded-3xl shadow-xl border border-gray-200 dark:border-slate-700 p-12 text-center">
                <div class="w-24 h-24 rounded-full bg-gradient-to-br from-green-100 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/30 flex items-center justify-center mx-auto mb-6 shadow-lg">
                    <svg class="w-12 h-12 text-[#00B900]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">ยังไม่มี AI Settings</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">เริ่มต้นด้วยการสร้างการตั้งค่า AI แรกของคุณ</p>
                <a href="{{ route('admin.line-bot.ai.create') }}"
                   class="inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-[#00B900] to-[#00E600] text-white rounded-xl hover:shadow-xl transition-all font-bold text-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    สร้าง AI Setting ใหม่
                </a>
            </div>
        @endforelse
    </div>

    <!-- Test Modal -->
    <div x-show="showTestModal" x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="showTestModal = false"></div>

            <div x-show="showTestModal"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="relative glass-fusion dark:bg-slate-800 rounded-3xl max-w-lg w-full shadow-2xl border border-gray-200 dark:border-slate-700 overflow-hidden">
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-[#00B900] to-[#00E600] px-6 py-5">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-bold text-white flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                            </div>
                            ทดสอบ AI
                        </h3>
                        <button @click="showTestModal = false" class="w-10 h-10 rounded-xl bg-white/20 hover:bg-white/30 flex items-center justify-center transition-colors">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-5">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">ข้อความทดสอบ</label>
                        <input type="text" x-model="testMessage"
                               @keyup.enter="sendTest()"
                               placeholder="พิมพ์ข้อความทดสอบ..."
                               class="w-full px-4 py-3 border-2 border-gray-200 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-4 focus:ring-green-500/20 focus:border-green-500 transition-all">
                    </div>

                    <button @click="sendTest()" :disabled="testLoading"
                            class="w-full px-6 py-4 bg-gradient-to-r from-[#00B900] to-[#00E600] text-white rounded-xl font-bold hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed transition-all flex items-center justify-center gap-2">
                        <svg x-show="!testLoading" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        <svg x-show="testLoading" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="testLoading ? 'กำลังทดสอบ...' : 'ส่งทดสอบ'"></span>
                    </button>

                    <div x-show="testResponse" x-transition class="p-5 bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl border-2 border-green-200 dark:border-green-700">
                        <p class="text-sm font-bold text-green-900 dark:text-green-100 mb-2 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                            </svg>
                            คำตอบจาก AI:
                        </p>
                        <p class="text-gray-900 dark:text-white" x-text="testResponse"></p>
                        <div x-show="testMeta" class="mt-3 pt-3 border-t border-green-200 dark:border-green-700 flex flex-wrap gap-3 text-xs">
                            <span class="px-2 py-1 bg-green-100 dark:bg-green-800/50 text-green-800 dark:text-green-200 rounded-lg" x-text="'Provider: ' + (testMeta?.provider || 'N/A')"></span>
                            <span class="px-2 py-1 bg-green-100 dark:bg-green-800/50 text-green-800 dark:text-green-200 rounded-lg" x-text="'Tokens: ' + (testMeta?.tokens_used || 'N/A')"></span>
                            <span class="px-2 py-1 bg-green-100 dark:bg-green-800/50 text-green-800 dark:text-green-200 rounded-lg" x-text="'Time: ' + (testMeta?.response_time || 'N/A') + 's'"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
[x-cloak] { display: none !important; }

@keyframes fade-in {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.animate-fade-in {
    animation: fade-in 0.3s ease-out;
}

.glass-fusion {
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
}

.dark .glass-fusion {
    background: rgba(30, 41, 59, 0.9);
}
</style>

@push('scripts')
<script>
    function settingsManager() {
        return {
            showTestModal: false,
            testMessage: 'สวัสดีค่ะ อยากสมัครสมาชิก',
            testLoading: false,
            testResponse: null,
            testMeta: null,
            testSettingId: null,

            testAi(settingId) {
                this.testSettingId = settingId;
                this.testResponse = null;
                this.testMeta = null;
                this.showTestModal = true;
            },

            async sendTest() {
                if (!this.testMessage) return;

                this.testLoading = true;
                this.testResponse = null;
                this.testMeta = null;

                try {
                    const response = await fetch(`/admin/line-recruitment/test-ai/${this.testSettingId}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ message: this.testMessage })
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.testResponse = data.message;
                        this.testMeta = {
                            provider: data.provider,
                            tokens_used: data.tokens_used || 'N/A',
                            response_time: data.response_time || 'N/A'
                        };
                    } else {
                        this.testResponse = 'Error: ' + data.error;
                    }
                } catch (error) {
                    this.testResponse = 'Error: ' + error.message;
                } finally {
                    this.testLoading = false;
                }
            }
        }
    }
</script>
@endpush
@endsection
