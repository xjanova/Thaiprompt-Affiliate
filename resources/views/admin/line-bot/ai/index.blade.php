@extends('layouts.admin-v3')

@section('title', 'ตั้งค่าแชทบอท AI - LINE Official Account')

@section('content')
<div class="container-fluid px-4 py-6" x-data="aiDashboard()">
    <!-- Premium Animated Header -->
    <div class="relative mb-8 overflow-hidden rounded-3xl bg-gradient-to-br from-purple-600 via-indigo-700 to-purple-900 p-10 shadow-2xl">
        <!-- Animated Background Pattern -->
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS1vcGFjaXR5PSIwLjA1IiBzdHJva2Utd2lkdGg9IjEiLz48L3BhdHRlcm4+PC9kZWZzPjxyZWN0IHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIGZpbGw9InVybCgjZ3JpZCkiLz48L3N2Zz4=')] opacity-40"></div>

        <!-- Enhanced Floating Particles Effect -->
        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute top-10 left-10 w-2 h-2 bg-white/30 rounded-full animate-ping"></div>
            <div class="absolute top-20 right-20 w-3 h-3 bg-purple-300/40 rounded-full animate-pulse"></div>
            <div class="absolute bottom-10 left-1/3 w-2 h-2 bg-indigo-300/30 rounded-full animate-bounce"></div>
            <div class="absolute top-1/2 right-1/4 w-4 h-4 bg-pink-300/20 rounded-full animate-pulse" style="animation-delay: 0.5s;"></div>
            <div class="absolute bottom-20 right-10 w-2 h-2 bg-blue-300/30 rounded-full animate-ping" style="animation-delay: 1s;"></div>
            <div class="absolute top-40 left-1/4 w-3 h-3 bg-cyan-300/25 rounded-full animate-bounce" style="animation-delay: 1.5s;"></div>
        </div>

        <div class="relative flex items-center justify-between">
            <div class="flex-1">
                <div class="flex items-center gap-4 mb-3">
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-white/25 to-white/10 backdrop-blur-lg flex items-center justify-center shadow-xl border border-white/20 transform hover:rotate-12 transition-transform duration-500">
                        <svg class="w-10 h-10 text-white drop-shadow-lg" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-4xl font-black text-white mb-2 drop-shadow-lg tracking-tight">🤖 ตั้งค่าแชทบอท AI</h1>
                        <p class="text-purple-100 text-lg font-medium">ควบคุมและปรับแต่ง AI เพื่อตอบคำถามลูกค้าอัตโนมัติ</p>
                        <div class="flex items-center gap-4 mt-2">
                            <span class="text-xs bg-white/10 backdrop-blur-sm px-3 py-1 rounded-full text-white font-semibold border border-white/30">
                                OpenAI • DeepSeek • Claude • Gemini
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.line-bot.ai.conversations') }}"
                   class="px-6 py-3 bg-white/10 backdrop-blur-md border border-white/25 text-white rounded-xl hover:bg-white/20 transition-all duration-300 shadow-lg hover:shadow-2xl transform hover:-translate-y-1 hover:scale-105 flex items-center gap-2">
                    <i class="fas fa-comments"></i>
                    <span class="font-semibold">บทสนทนา</span>
                </a>
                <button @click="showTemplateModal = true"
                        class="px-6 py-3 bg-white/10 backdrop-blur-md border border-white/25 text-white rounded-xl hover:bg-white/20 transition-all duration-300 shadow-lg hover:shadow-2xl transform hover:-translate-y-1 hover:scale-105 flex items-center gap-2">
                    <i class="fas fa-rocket"></i>
                    <span class="font-semibold">Quick Start</span>
                </button>
                <a href="{{ route('admin.line-bot.ai.create') }}"
                   class="px-8 py-3 bg-gradient-to-r from-white to-purple-50 text-purple-700 rounded-xl hover:from-purple-50 hover:to-white transition-all duration-300 shadow-xl hover:shadow-2xl transform hover:-translate-y-1 hover:scale-105 font-bold flex items-center gap-2">
                    <i class="fas fa-plus-circle"></i>
                    <span>เพิ่ม AI ใหม่</span>
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 rounded-xl bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/30 dark:to-emerald-900/30 border border-green-200 dark:border-green-800 animate-fade-in">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-full bg-green-500 flex items-center justify-center mr-3">
                    <i class="fas fa-check text-white"></i>
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-green-900 dark:text-green-300">Success!</p>
                    <p class="text-sm text-green-700 dark:text-green-400">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 p-4 rounded-xl bg-gradient-to-r from-red-50 to-pink-50 dark:from-red-900/30 dark:to-pink-900/30 border border-red-200 dark:border-red-800 animate-fade-in">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-full bg-red-500 flex items-center justify-center mr-3">
                    <i class="fas fa-exclamation-circle text-white"></i>
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-red-900 dark:text-red-300">Error!</p>
                    <p class="text-sm text-red-700 dark:text-red-400">{{ session('error') }}</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Enhanced Statistics Cards with Animated Counters -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total AI Settings -->
        <div class="group relative overflow-hidden bg-gradient-to-br from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700 rounded-2xl p-6 text-white shadow-xl hover:shadow-2xl transition-all duration-500 transform hover:-translate-y-2 hover:scale-105">
            <!-- Background Glow -->
            <div class="absolute inset-0 bg-gradient-to-br from-white/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>

            <div class="relative flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm font-medium mb-1">การตั้งค่า AI ทั้งหมด</p>
                    <h3 class="text-4xl font-bold mb-2" x-data="{ count: 0 }" x-init="animateValue(0, {{ $aiSettings->count() }}, 1000, (val) => count = val)" x-text="count">0</h3>
                    <div class="flex items-center gap-1 text-xs text-blue-100">
                        <i class="fas fa-arrow-up"></i>
                        <span>+2 this month</span>
                    </div>
                </div>
                <div class="w-16 h-16 bg-white/10 backdrop-blur-md rounded-2xl flex items-center justify-center border border-white/20 group-hover:rotate-12 transition-transform duration-500">
                    <i class="fas fa-robot text-3xl"></i>
                </div>
            </div>
        </div>

        <!-- Active AI -->
        <div class="group relative overflow-hidden bg-gradient-to-br from-green-500 to-green-600 dark:from-green-600 dark:to-green-700 rounded-2xl p-6 text-white shadow-xl hover:shadow-2xl transition-all duration-500 transform hover:-translate-y-2 hover:scale-105">
            <div class="absolute inset-0 bg-gradient-to-br from-white/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>

            <div class="relative flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm font-medium mb-1">AI ที่เปิดใช้งาน</p>
                    <h3 class="text-4xl font-bold mb-2" x-data="{ count: 0 }" x-init="animateValue(0, {{ $aiSettings->where('is_active', true)->count() }}, 1000, (val) => count = val)" x-text="count">0</h3>
                    <div class="flex items-center gap-1 text-xs text-green-100">
                        <span class="w-2 h-2 bg-green-300 rounded-full animate-pulse"></span>
                        <span>Online</span>
                    </div>
                </div>
                <div class="w-16 h-16 bg-white/10 backdrop-blur-md rounded-2xl flex items-center justify-center border border-white/20 group-hover:rotate-12 transition-transform duration-500">
                    <i class="fas fa-check-circle text-3xl"></i>
                </div>
            </div>
        </div>

        <!-- Knowledge Bases -->
        <div class="group relative overflow-hidden bg-gradient-to-br from-purple-500 to-purple-600 dark:from-purple-600 dark:to-purple-700 rounded-2xl p-6 text-white shadow-xl hover:shadow-2xl transition-all duration-500 transform hover:-translate-y-2 hover:scale-105">
            <div class="absolute inset-0 bg-gradient-to-br from-white/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>

            <div class="relative flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm font-medium mb-1">ฐานความรู้</p>
                    <h3 class="text-4xl font-bold mb-2" x-data="{ count: 0 }" x-init="animateValue(0, {{ $aiSettings->sum(fn($s) => $s->knowledgeBases->count()) }}, 1000, (val) => count = val)" x-text="count">0</h3>
                    <div class="flex items-center gap-1 text-xs text-purple-100">
                        <i class="fas fa-arrow-up"></i>
                        <span>+5 documents</span>
                    </div>
                </div>
                <div class="w-16 h-16 bg-white/10 backdrop-blur-md rounded-2xl flex items-center justify-center border border-white/20 group-hover:rotate-12 transition-transform duration-500">
                    <i class="fas fa-book text-3xl"></i>
                </div>
            </div>
        </div>

        <!-- Today's Conversations -->
        <div class="group relative overflow-hidden bg-gradient-to-br from-orange-500 to-orange-600 dark:from-orange-600 dark:to-orange-700 rounded-2xl p-6 text-white shadow-xl hover:shadow-2xl transition-all duration-500 transform hover:-translate-y-2 hover:scale-105">
            <div class="absolute inset-0 bg-gradient-to-br from-white/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>

            <div class="relative flex items-center justify-between">
                <div>
                    <p class="text-orange-100 text-sm font-medium mb-1">บทสนทนาวันนี้</p>
                    <h3 class="text-4xl font-bold mb-2">0</h3>
                    <div class="flex items-center gap-1 text-xs text-orange-100">
                        <i class="fas fa-clock"></i>
                        <span>Live updates</span>
                    </div>
                </div>
                <div class="w-16 h-16 bg-white/10 backdrop-blur-md rounded-2xl flex items-center justify-center border border-white/20 group-hover:rotate-12 transition-transform duration-500">
                    <i class="fas fa-comments text-3xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Filters & Search -->
    <div class="mb-6 bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-700 p-4">
        <div class="flex flex-col lg:flex-row gap-4">
            <!-- Search Box -->
            <div class="flex-1">
                <div class="relative">
                    <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    <input type="text"
                           x-model="searchQuery"
                           placeholder="ค้นหา AI settings..."
                           class="w-full pl-12 pr-4 py-3 border border-gray-200 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-100 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all">
                </div>
            </div>

            <!-- Filter Buttons -->
            <div class="flex flex-wrap gap-2">
                <button @click="filterStatus = 'all'"
                        :class="filterStatus === 'all' ? 'bg-purple-600 text-white' : 'bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300'"
                        class="px-4 py-3 rounded-xl font-semibold transition-all hover:scale-105">
                    <i class="fas fa-list mr-1"></i> All
                </button>
                <button @click="filterStatus = 'active'"
                        :class="filterStatus === 'active' ? 'bg-green-600 text-white' : 'bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300'"
                        class="px-4 py-3 rounded-xl font-semibold transition-all hover:scale-105">
                    <i class="fas fa-check-circle mr-1"></i> Active
                </button>
                <button @click="filterStatus = 'inactive'"
                        :class="filterStatus === 'inactive' ? 'bg-gray-600 text-white' : 'bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300'"
                        class="px-4 py-3 rounded-xl font-semibold transition-all hover:scale-105">
                    <i class="fas fa-times-circle mr-1"></i> Inactive
                </button>
            </div>

            <!-- Provider Filter -->
            <div class="flex flex-wrap gap-2">
                <button @click="filterProvider = ''"
                        :class="filterProvider === '' ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300'"
                        class="px-4 py-3 rounded-xl font-semibold transition-all hover:scale-105">
                    All Providers
                </button>
                <button @click="filterProvider = 'openai'"
                        :class="filterProvider === 'openai' ? 'bg-green-600 text-white' : 'bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300'"
                        class="px-4 py-3 rounded-xl font-semibold transition-all hover:scale-105">
                    OpenAI
                </button>
                <button @click="filterProvider = 'anthropic'"
                        :class="filterProvider === 'anthropic' ? 'bg-orange-600 text-white' : 'bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300'"
                        class="px-4 py-3 rounded-xl font-semibold transition-all hover:scale-105">
                    Claude
                </button>
                <button @click="filterProvider = 'gemini'"
                        :class="filterProvider === 'gemini' ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300'"
                        class="px-4 py-3 rounded-xl font-semibold transition-all hover:scale-105">
                    Gemini
                </button>
                <button @click="filterProvider = 'deepseek'"
                        :class="filterProvider === 'deepseek' ? 'bg-purple-600 text-white' : 'bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300'"
                        class="px-4 py-3 rounded-xl font-semibold transition-all hover:scale-105">
                    DeepSeek
                </button>
            </div>

            <!-- Sort -->
            <select x-model="sortBy"
                    class="px-4 py-3 border border-gray-200 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-100 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 font-semibold">
                <option value="newest">Newest First</option>
                <option value="name">By Name</option>
                <option value="provider">By Provider</option>
                <option value="most_used">Most Used</option>
            </select>
        </div>
    </div>

    <!-- AI Settings Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @php
            $settings = $aiSettings->toArray();
        @endphp

        @forelse($aiSettings as $setting)
            <div x-show="filterAiSetting({{ json_encode([
                    'name' => $setting->name,
                    'provider' => $setting->provider,
                    'is_active' => $setting->is_active
                ]) }})"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100"
                 class="group relative bg-white dark:bg-slate-800 rounded-2xl shadow-xl border-2 border-gray-100 dark:border-slate-700 overflow-hidden hover:shadow-2xl hover:border-purple-300 dark:hover:border-purple-700 transition-all duration-500 transform hover:-translate-y-2">

                <!-- Premium Header with Provider Badge -->
                <div class="relative bg-gradient-to-br
                    @if($setting->provider === 'openai') from-emerald-500 via-green-600 to-teal-700
                    @elseif($setting->provider === 'deepseek') from-blue-500 via-indigo-600 to-purple-700
                    @elseif($setting->provider === 'anthropic') from-orange-500 via-red-600 to-pink-700
                    @elseif($setting->provider === 'gemini') from-purple-500 via-fuchsia-600 to-pink-700
                    @else from-gray-500 via-gray-600 to-gray-700
                    @endif px-6 py-5 overflow-hidden">

                    <!-- Animated Background -->
                    <div class="absolute inset-0 bg-gradient-to-br from-white/10 to-transparent"></div>
                    <div class="absolute top-0 right-0 w-32 h-32 bg-white/5 rounded-full -mr-16 -mt-16 group-hover:scale-150 transition-transform duration-500"></div>

                    <div class="relative flex items-center justify-between">
                        <div class="flex items-center gap-3 flex-1 min-w-0">
                            <div class="w-14 h-14 rounded-xl bg-white/10 backdrop-blur-md flex items-center justify-center shadow-lg group-hover:scale-110 group-hover:rotate-6 transition-all duration-500 border border-white/30">
                                @if($setting->provider === 'openai')
                                    <i class="fas fa-brain text-white text-2xl"></i>
                                @elseif($setting->provider === 'deepseek')
                                    <i class="fas fa-search text-white text-2xl"></i>
                                @elseif($setting->provider === 'anthropic')
                                    <i class="fas fa-robot text-white text-2xl"></i>
                                @elseif($setting->provider === 'gemini')
                                    <i class="fas fa-gem text-white text-2xl"></i>
                                @else
                                    <i class="fas fa-cog text-white text-2xl"></i>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="text-lg font-bold text-white truncate mb-1">{{ $setting->name }}</h3>
                                <p class="text-xs text-white/90 font-semibold tracking-wide">{{ strtoupper($setting->provider) }}</p>
                            </div>
                        </div>
                        @if($setting->is_active)
                            <span class="px-3 py-1.5 bg-white/10 backdrop-blur-md rounded-full text-xs font-bold text-white shadow-lg border border-white/40 flex items-center gap-1.5">
                                <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
                                Active
                            </span>
                        @else
                            <span class="px-3 py-1 bg-white/5 backdrop-blur-sm rounded-full text-xs font-semibold text-white/70">
                                Inactive
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Content -->
                <div class="p-6 space-y-4">
                    <!-- Model Info with Premium Design -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-slate-700 dark:to-slate-600 rounded-xl border border-gray-200 dark:border-slate-600 group-hover:border-purple-200 dark:group-hover:border-purple-700 transition-colors">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-microchip text-purple-600 dark:text-purple-400 text-sm"></i>
                                </div>
                                <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Model</span>
                            </div>
                            <span class="font-bold text-sm text-gray-900 dark:text-gray-100 px-3 py-1 bg-white dark:bg-slate-700 rounded-lg shadow-sm">{{ $setting->model }}</span>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="p-3 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl border border-blue-100 dark:border-blue-800/50">
                                <div class="flex items-center gap-2 mb-1">
                                    <i class="fas fa-thermometer-half text-blue-600 dark:text-blue-400 text-xs"></i>
                                    <span class="text-[10px] font-medium text-blue-700 dark:text-blue-400 uppercase tracking-wide">Temp</span>
                                </div>
                                <p class="text-xl font-bold text-blue-900 dark:text-blue-300">{{ $setting->temperature }}</p>
                            </div>

                            <div class="p-3 bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl border border-green-100 dark:border-green-800/50">
                                <div class="flex items-center gap-2 mb-1">
                                    <i class="fas fa-coins text-green-600 dark:text-green-400 text-xs"></i>
                                    <span class="text-[10px] font-medium text-green-700 dark:text-green-400 uppercase tracking-wide">Tokens</span>
                                </div>
                                <p class="text-xl font-bold text-green-900 dark:text-green-300">{{ number_format($setting->max_tokens) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Knowledge Base & Memory Status -->
                    <div class="flex gap-3">
                        <div class="flex-1 p-3 bg-gradient-to-br from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 rounded-xl border border-purple-100 dark:border-purple-800/50">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-book text-purple-600 dark:text-purple-400"></i>
                                    <span class="text-xs font-medium text-purple-700 dark:text-purple-400">Knowledge</span>
                                </div>
                                <span class="px-2.5 py-1 bg-purple-600 dark:bg-purple-700 text-white rounded-lg text-xs font-bold shadow-md">
                                    {{ $setting->knowledgeBases->count() }}
                                </span>
                            </div>
                        </div>

                        <div class="flex-1 p-3 bg-gradient-to-br from-orange-50 to-amber-50 dark:from-orange-900/20 dark:to-amber-900/20 rounded-xl border border-orange-100 dark:border-orange-800/50">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-history text-orange-600 dark:text-orange-400"></i>
                                    <span class="text-xs font-medium text-orange-700 dark:text-orange-400">Memory</span>
                                </div>
                                <span class="px-2.5 py-1 bg-orange-600 dark:bg-orange-700 text-white rounded-lg text-xs font-bold shadow-md">
                                    {{ $setting->conversation_memory_limit ?? 10 }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Last Used -->
                    <div class="p-3 bg-gradient-to-r from-gray-50 to-slate-50 dark:from-slate-700 dark:to-slate-600 rounded-xl border border-gray-200 dark:border-slate-600">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-clock text-gray-500 dark:text-gray-400 text-sm"></i>
                                <span class="text-xs text-gray-600 dark:text-gray-400">Last used</span>
                            </div>
                            <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ $setting->updated_at->diffForHumans() }}</span>
                        </div>
                    </div>

                    <!-- Premium Action Buttons with Dropdown -->
                    <div class="relative pt-2 space-y-2" x-data="{ showActions: false }">
                        <div class="grid grid-cols-2 gap-2">
                            <a href="{{ route('admin.line-bot.ai.knowledge.index', $setting->id) }}"
                               class="px-4 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-xl transition-all text-center text-sm font-bold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
                                <i class="fas fa-book"></i>
                                <span>Knowledge</span>
                            </a>
                            <button @click="testAi({{ $setting->id }})"
                                    class="px-4 py-2.5 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white rounded-xl transition-all text-center text-sm font-bold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
                                <i class="fas fa-vial"></i>
                                <span>Test</span>
                            </button>
                        </div>
                        <a href="{{ route('admin.line-bot.ai.edit', $setting->id) }}"
                           class="block w-full px-4 py-2.5 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white rounded-xl transition-all text-center text-sm font-bold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
                            <i class="fas fa-edit"></i>
                            <span>แก้ไขการตั้งค่า</span>
                        </a>

                        <!-- Quick Actions Dropdown -->
                        <button @click="showActions = !showActions"
                                class="block w-full px-4 py-2.5 bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800 text-white rounded-xl transition-all text-center text-sm font-bold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
                            <i class="fas fa-ellipsis-h"></i>
                            <span>More Actions</span>
                        </button>

                        <!-- Dropdown Menu -->
                        <div x-show="showActions"
                             @click.away="showActions = false"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 transform scale-95"
                             x-transition:enter-end="opacity-100 transform scale-100"
                             class="absolute bottom-full mb-2 left-0 right-0 bg-white dark:bg-slate-700 rounded-xl shadow-2xl border border-gray-200 dark:border-slate-600 overflow-hidden z-10">
                            <a href="{{ route('admin.line-bot.ai.edit', $setting->id) }}"
                               class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-slate-600 text-gray-700 dark:text-gray-300 transition-colors">
                                <i class="fas fa-copy mr-2 text-blue-500"></i> Duplicate
                            </a>
                            <button onclick="confirmDelete({{ $setting->id }})"
                                    class="w-full text-left px-4 py-3 hover:bg-red-50 dark:hover:bg-red-900/20 text-red-600 dark:text-red-400 transition-colors">
                                <i class="fas fa-trash mr-2"></i> Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <!-- Beautiful Empty State -->
            <div class="col-span-full">
                <div class="relative overflow-hidden bg-gradient-to-br from-purple-50 via-indigo-50 to-blue-50 dark:from-slate-800 dark:via-slate-800 dark:to-slate-700 rounded-3xl shadow-2xl border-2 border-purple-200 dark:border-slate-600 p-16 text-center">
                    <!-- Animated Background -->
                    <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0iIzk0OTRhNCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDUiIHN0cm9rZS13aWR0aD0iMSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNncmlkKSIvPjwvc3ZnPg==')] opacity-40"></div>

                    <div class="relative">
                        <!-- Icon with Animation -->
                        <div class="w-32 h-32 rounded-full bg-gradient-to-br from-purple-400 to-indigo-500 dark:from-purple-600 dark:to-indigo-700 flex items-center justify-center mx-auto mb-8 shadow-2xl animate-bounce">
                            <i class="fas fa-robot text-white text-6xl"></i>
                        </div>

                        <h3 class="text-4xl font-black text-gray-900 dark:text-white mb-4">ยังไม่มีการตั้งค่า AI</h3>
                        <p class="text-xl text-gray-600 dark:text-gray-400 mb-8 max-w-2xl mx-auto">
                            เริ่มต้นใช้งานโดยการสร้างการตั้งค่า AI แรกของคุณ<br>
                            รองรับ OpenAI, Claude, Gemini และ DeepSeek
                        </p>

                        <!-- Action Buttons -->
                        <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                            <a href="{{ route('admin.line-bot.ai.create') }}"
                               class="px-8 py-4 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-2xl transition-all shadow-xl hover:shadow-2xl transform hover:scale-105 font-bold text-lg flex items-center gap-3">
                                <i class="fas fa-plus-circle text-2xl"></i>
                                <span>สร้างการตั้งค่า AI แรก</span>
                            </a>
                            <button @click="showTemplateModal = true"
                                    class="px-8 py-4 bg-white dark:bg-slate-700 hover:bg-gray-50 dark:hover:bg-slate-600 text-purple-600 dark:text-purple-400 rounded-2xl transition-all shadow-lg hover:shadow-xl transform hover:scale-105 font-bold text-lg flex items-center gap-3 border-2 border-purple-200 dark:border-purple-700">
                                <i class="fas fa-rocket text-2xl"></i>
                                <span>Quick Start Templates</span>
                            </button>
                        </div>

                        <!-- Feature Highlights -->
                        <div class="mt-12 grid grid-cols-1 sm:grid-cols-4 gap-6 max-w-4xl mx-auto">
                            <div class="bg-white/50 dark:bg-slate-700/50 backdrop-blur-sm rounded-2xl p-6 border border-white dark:border-slate-600">
                                <div class="w-12 h-12 bg-green-500 rounded-xl flex items-center justify-center mx-auto mb-3">
                                    <i class="fas fa-brain text-white text-xl"></i>
                                </div>
                                <h4 class="font-bold text-gray-900 dark:text-white mb-1">OpenAI</h4>
                                <p class="text-xs text-gray-600 dark:text-gray-400">GPT-4, GPT-3.5</p>
                            </div>
                            <div class="bg-white/50 dark:bg-slate-700/50 backdrop-blur-sm rounded-2xl p-6 border border-white dark:border-slate-600">
                                <div class="w-12 h-12 bg-orange-500 rounded-xl flex items-center justify-center mx-auto mb-3">
                                    <i class="fas fa-robot text-white text-xl"></i>
                                </div>
                                <h4 class="font-bold text-gray-900 dark:text-white mb-1">Claude</h4>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Anthropic AI</p>
                            </div>
                            <div class="bg-white/50 dark:bg-slate-700/50 backdrop-blur-sm rounded-2xl p-6 border border-white dark:border-slate-600">
                                <div class="w-12 h-12 bg-blue-500 rounded-xl flex items-center justify-center mx-auto mb-3">
                                    <i class="fas fa-gem text-white text-xl"></i>
                                </div>
                                <h4 class="font-bold text-gray-900 dark:text-white mb-1">Gemini</h4>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Google AI</p>
                            </div>
                            <div class="bg-white/50 dark:bg-slate-700/50 backdrop-blur-sm rounded-2xl p-6 border border-white dark:border-slate-600">
                                <div class="w-12 h-12 bg-purple-500 rounded-xl flex items-center justify-center mx-auto mb-3">
                                    <i class="fas fa-search text-white text-xl"></i>
                                </div>
                                <h4 class="font-bold text-gray-900 dark:text-white mb-1">DeepSeek</h4>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Advanced AI</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    <!-- AI Provider Templates Modal -->
    <div x-show="showTemplateModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         @click.self="showTemplateModal = false"
         class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-2xl max-w-6xl w-full max-h-[90vh] overflow-hidden border-2 border-purple-200 dark:border-slate-600"
             @click.away="showTemplateModal = false"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform scale-95"
             x-transition:enter-end="opacity-100 transform scale-100">

            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-purple-600 via-indigo-600 to-blue-600 px-8 py-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-3xl font-black text-white flex items-center gap-3">
                            <i class="fas fa-rocket"></i>
                            AI Quick Start Templates
                        </h3>
                        <p class="text-purple-100 mt-1">เลือก template สำเร็จรูปเพื่อเริ่มต้นใช้งานได้ทันที</p>
                    </div>
                    <button @click="showTemplateModal = false"
                            class="text-white/80 hover:text-white transition-colors">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="p-8 overflow-y-auto max-h-[calc(90vh-120px)]">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- OpenAI Template -->
                    <div class="group relative bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-2xl p-6 border-2 border-green-200 dark:border-green-800 hover:border-green-400 dark:hover:border-green-600 transition-all hover:shadow-2xl cursor-pointer">
                        <div class="flex items-start gap-4 mb-4">
                            <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                                <i class="fas fa-brain text-white text-3xl"></i>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-xl font-bold text-gray-900 dark:text-white mb-1">OpenAI GPT-4</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Most advanced AI model</p>
                            </div>
                        </div>
                        <div class="space-y-2 mb-4">
                            <div class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <i class="fas fa-check-circle text-green-600"></i>
                                <span>GPT-4 Turbo model</span>
                            </div>
                            <div class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <i class="fas fa-check-circle text-green-600"></i>
                                <span>Temperature: 0.7</span>
                            </div>
                            <div class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <i class="fas fa-check-circle text-green-600"></i>
                                <span>Max Tokens: 4000</span>
                            </div>
                        </div>
                        <a href="{{ route('admin.line-bot.ai.create', ['provider' => 'openai']) }}"
                           class="block w-full px-4 py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white rounded-xl transition-all text-center font-bold shadow-lg">
                            Use This Template
                        </a>
                    </div>

                    <!-- Claude Template -->
                    <div class="group relative bg-gradient-to-br from-orange-50 to-red-50 dark:from-orange-900/20 dark:to-red-900/20 rounded-2xl p-6 border-2 border-orange-200 dark:border-orange-800 hover:border-orange-400 dark:hover:border-orange-600 transition-all hover:shadow-2xl cursor-pointer">
                        <div class="flex items-start gap-4 mb-4">
                            <div class="w-16 h-16 bg-gradient-to-br from-orange-500 to-red-600 rounded-2xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                                <i class="fas fa-robot text-white text-3xl"></i>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-xl font-bold text-gray-900 dark:text-white mb-1">Claude (Anthropic)</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Safe & helpful AI</p>
                            </div>
                        </div>
                        <div class="space-y-2 mb-4">
                            <div class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <i class="fas fa-check-circle text-orange-600"></i>
                                <span>Claude 3 Opus</span>
                            </div>
                            <div class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <i class="fas fa-check-circle text-orange-600"></i>
                                <span>Temperature: 0.8</span>
                            </div>
                            <div class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <i class="fas fa-check-circle text-orange-600"></i>
                                <span>Max Tokens: 4096</span>
                            </div>
                        </div>
                        <a href="{{ route('admin.line-bot.ai.create', ['provider' => 'anthropic']) }}"
                           class="block w-full px-4 py-3 bg-gradient-to-r from-orange-600 to-red-600 hover:from-orange-700 hover:to-red-700 text-white rounded-xl transition-all text-center font-bold shadow-lg">
                            Use This Template
                        </a>
                    </div>

                    <!-- Gemini Template -->
                    <div class="group relative bg-gradient-to-br from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 rounded-2xl p-6 border-2 border-blue-200 dark:border-blue-800 hover:border-blue-400 dark:hover:border-blue-600 transition-all hover:shadow-2xl cursor-pointer">
                        <div class="flex items-start gap-4 mb-4">
                            <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                                <i class="fas fa-gem text-white text-3xl"></i>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-xl font-bold text-gray-900 dark:text-white mb-1">Google Gemini</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Multimodal AI by Google</p>
                            </div>
                        </div>
                        <div class="space-y-2 mb-4">
                            <div class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <i class="fas fa-check-circle text-blue-600"></i>
                                <span>Gemini Pro model</span>
                            </div>
                            <div class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <i class="fas fa-check-circle text-blue-600"></i>
                                <span>Temperature: 0.7</span>
                            </div>
                            <div class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <i class="fas fa-check-circle text-blue-600"></i>
                                <span>Max Tokens: 2048</span>
                            </div>
                        </div>
                        <a href="{{ route('admin.line-bot.ai.create', ['provider' => 'gemini']) }}"
                           class="block w-full px-4 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white rounded-xl transition-all text-center font-bold shadow-lg">
                            Use This Template
                        </a>
                    </div>

                    <!-- DeepSeek Template -->
                    <div class="group relative bg-gradient-to-br from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 rounded-2xl p-6 border-2 border-purple-200 dark:border-purple-800 hover:border-purple-400 dark:hover:border-purple-600 transition-all hover:shadow-2xl cursor-pointer">
                        <div class="flex items-start gap-4 mb-4">
                            <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-2xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                                <i class="fas fa-search text-white text-3xl"></i>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-xl font-bold text-gray-900 dark:text-white mb-1">DeepSeek</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Advanced reasoning AI</p>
                            </div>
                        </div>
                        <div class="space-y-2 mb-4">
                            <div class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <i class="fas fa-check-circle text-purple-600"></i>
                                <span>DeepSeek Chat</span>
                            </div>
                            <div class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <i class="fas fa-check-circle text-purple-600"></i>
                                <span>Temperature: 0.7</span>
                            </div>
                            <div class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <i class="fas fa-check-circle text-purple-600"></i>
                                <span>Max Tokens: 4096</span>
                            </div>
                        </div>
                        <a href="{{ route('admin.line-bot.ai.create', ['provider' => 'deepseek']) }}"
                           class="block w-full px-4 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-xl transition-all text-center font-bold shadow-lg">
                            Use This Template
                        </a>
                    </div>
                </div>

                <!-- Additional Info -->
                <div class="mt-8 p-6 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-2xl border border-indigo-200 dark:border-indigo-800">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-indigo-500 rounded-xl flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-info-circle text-white text-xl"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 dark:text-white mb-2">Need Help Getting Started?</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                Templates include pre-configured settings for each AI provider. You can customize them after creation.
                            </p>
                            <a href="#" class="text-indigo-600 dark:text-indigo-400 hover:underline text-sm font-semibold">
                                View Documentation <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Test AI Modal -->
<div id="testModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-2xl w-full overflow-hidden transform transition-all border border-gray-200 dark:border-slate-700" x-data="{ testing: false, result: '', error: '' }">
        <div class="bg-gradient-to-r from-purple-500 to-indigo-600 px-6 py-4">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-white flex items-center">
                    <i class="fas fa-vial mr-2"></i>Test AI Connection
                </h3>
                <button onclick="closeTestModal()" class="text-white/80 hover:text-white transition">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>

        <form onsubmit="return submitTest(event)" class="p-6 space-y-4">
            <input type="hidden" id="test-ai-id" name="ai_id">

            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-comment text-purple-500 mr-1"></i> Test Message
                </label>
                <textarea id="test-message" name="message" rows="3" required
                    class="w-full px-4 py-3 border border-gray-200 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-100 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all"
                    placeholder="Hello! Please introduce yourself.">Hello! Please introduce yourself.</textarea>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">The AI will respond to this message</p>
            </div>

            <!-- Result Area -->
            <div id="test-result-area" class="hidden">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-robot text-green-500 mr-1"></i> AI Response
                </label>
                <div class="p-4 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border border-green-200 dark:border-green-800 rounded-xl">
                    <p id="test-result" class="text-sm text-gray-900 dark:text-white whitespace-pre-wrap"></p>
                </div>
            </div>

            <!-- Error Area -->
            <div id="test-error-area" class="hidden">
                <div class="p-4 bg-gradient-to-r from-red-50 to-pink-50 dark:from-red-900/20 dark:to-pink-900/20 border border-red-200 dark:border-red-800 rounded-xl">
                    <div class="flex items-start gap-2">
                        <i class="fas fa-exclamation-circle text-red-500 mt-0.5"></i>
                        <p id="test-error" class="text-sm text-red-700 dark:text-red-400"></p>
                    </div>
                </div>
            </div>

            <!-- Loading State -->
            <div id="test-loading" class="hidden text-center py-4">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600 mb-2"></div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Testing AI connection...</p>
            </div>

            <div class="flex gap-3 pt-4">
                <button type="button" onclick="closeTestModal()"
                        class="flex-1 px-4 py-3 bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300 dark:hover:bg-slate-600 transition font-semibold">
                    Close
                </button>
                <button type="submit" id="test-submit-btn"
                        class="flex-1 px-4 py-3 bg-gradient-to-r from-purple-500 to-indigo-600 text-white rounded-xl hover:from-purple-600 hover:to-indigo-700 transition shadow-lg font-semibold">
                    <i class="fas fa-vial mr-2"></i>Run Test
                </button>
            </div>
        </form>
    </div>
</div>

<style>
@keyframes fade-in {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: fade-in 0.3s ease-out;
}
</style>

<script>
/**
 * Alpine.js component สำหรับ AI Dashboard
 */
function aiDashboard() {
    return {
        // State
        searchQuery: '',
        filterStatus: 'all',
        filterProvider: '',
        sortBy: 'newest',
        showTemplateModal: false,

        /**
         * ฟังก์ชันกรองและค้นหา AI settings
         */
        filterAiSetting(setting) {
            // ค้นหาตามชื่อ
            if (this.searchQuery && !setting.name.toLowerCase().includes(this.searchQuery.toLowerCase())) {
                return false;
            }

            // กรองตาม status
            if (this.filterStatus === 'active' && !setting.is_active) {
                return false;
            }
            if (this.filterStatus === 'inactive' && setting.is_active) {
                return false;
            }

            // กรองตาม provider
            if (this.filterProvider && setting.provider !== this.filterProvider) {
                return false;
            }

            return true;
        },

        /**
         * Animate number counter
         */
        animateValue(start, end, duration, callback) {
            const range = end - start;
            const increment = range / (duration / 16);
            let current = start;

            const timer = setInterval(() => {
                current += increment;
                if (current >= end) {
                    current = end;
                    clearInterval(timer);
                }
                callback(Math.floor(current));
            }, 16);
        }
    }
}

let currentTestId = null;

/**
 * เปิด modal ทดสอบ AI
 */
function testAi(aiId) {
    currentTestId = aiId;
    document.getElementById('test-ai-id').value = aiId;
    document.getElementById('test-result-area').classList.add('hidden');
    document.getElementById('test-error-area').classList.add('hidden');
    document.getElementById('testModal').classList.remove('hidden');
    document.getElementById('testModal').classList.add('flex');
}

/**
 * ปิด modal ทดสอบ AI
 */
function closeTestModal() {
    document.getElementById('testModal').classList.add('hidden');
    document.getElementById('testModal').classList.remove('flex');
}

/**
 * ส่ง request ทดสอบ AI
 */
function submitTest(event) {
    event.preventDefault();

    const aiId = document.getElementById('test-ai-id').value;
    const message = document.getElementById('test-message').value;

    // แสดง loading state
    document.getElementById('test-loading').classList.remove('hidden');
    document.getElementById('test-submit-btn').disabled = true;
    document.getElementById('test-result-area').classList.add('hidden');
    document.getElementById('test-error-area').classList.add('hidden');

    // ส่ง request
    fetch(`/admin/line-bot/ai/${aiId}/test`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ message })
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('test-loading').classList.add('hidden');
        document.getElementById('test-submit-btn').disabled = false;

        if (data.success) {
            document.getElementById('test-result').textContent = data.response;
            document.getElementById('test-result-area').classList.remove('hidden');
        } else {
            document.getElementById('test-error').textContent = data.message || 'Test failed';
            document.getElementById('test-error-area').classList.remove('hidden');
        }
    })
    .catch(error => {
        document.getElementById('test-loading').classList.add('hidden');
        document.getElementById('test-submit-btn').disabled = false;
        document.getElementById('test-error').textContent = 'Network error: ' + error.message;
        document.getElementById('test-error-area').classList.remove('hidden');
    });

    return false;
}

/**
 * ยืนยันการลบ AI setting
 */
function confirmDelete(aiId) {
    if (confirm('คุณแน่ใจหรือไม่ที่จะลบการตั้งค่า AI นี้?')) {
        // ส่ง request ลบ
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/admin/line-bot/ai/${aiId}`;

        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'DELETE';

        const tokenInput = document.createElement('input');
        tokenInput.type = 'hidden';
        tokenInput.name = '_token';
        tokenInput.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        form.appendChild(methodInput);
        form.appendChild(tokenInput);
        document.body.appendChild(form);
        form.submit();
    }
}

// ปิด modal เมื่อกด ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeTestModal();
    }
});
</script>
@endsection
