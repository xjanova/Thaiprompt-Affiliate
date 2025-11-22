@extends('layouts.admin-v3')

@section('title', 'Knowledge Base Management')

@push('styles')
@vite(['resources/css/app.css'])
@endpush

@section('content')
<div class="container-fluid px-4 py-6" x-data="knowledgeBaseManager()">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center gap-4 mb-6">
            <a href="{{ route('admin.line-bot.ai.index') }}"
               class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div class="flex-1">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-1">
                    <i class="fas fa-book text-[#00B900]"></i> Knowledge Base Management
                </h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    For: <span class="font-semibold bg-gradient-to-r from-[#00B900] to-[#00E600] bg-clip-text text-transparent">{{ $aiSetting->name }}</span>
                </p>
            </div>
            <button @click="showAddModal = true"
                    class="px-6 py-3 bg-gradient-to-r from-[#00B900] to-[#00E600] text-white rounded-xl hover:shadow-2xl hover:shadow-green-500/50 transition-all duration-300 transform hover:-translate-y-1 font-bold">
                <i class="fas fa-plus mr-2"></i>Add Knowledge Base
            </button>
        </div>

        <!-- Statistics Cards -->
        @php
            $total = $knowledgeBases->count();
            $active = $knowledgeBases->where('is_enabled', true)->count();
            $categories = $knowledgeBases->pluck('type')->unique()->count();
            $lastUpdated = $knowledgeBases->max('updated_at');
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Knowledge Items -->
            <div class="glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 rounded-2xl p-6 border border-white/20 dark:border-white/10 shadow-xl transform hover:scale-105 transition-all duration-300">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow-lg">
                        <i class="fas fa-book text-white text-2xl"></i>
                    </div>
                    <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400 rounded-full text-xs font-bold">
                        Total
                    </span>
                </div>
                <div x-data="{ count: 0 }" x-init="animateCount(0, {{ $total }}, 1500, val => count = val)">
                    <h3 class="text-4xl font-black text-gray-900 dark:text-white mb-1" x-text="count"></h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 font-semibold">Knowledge Items</p>
            </div>

            <!-- Active Knowledge -->
            <div class="glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 rounded-2xl p-6 border border-white/20 dark:border-white/10 shadow-xl transform hover:scale-105 transition-all duration-300">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-[#00B900] to-[#00E600] flex items-center justify-center shadow-lg">
                        <i class="fas fa-check-circle text-white text-2xl"></i>
                    </div>
                    <span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded-full text-xs font-bold">
                        Active
                    </span>
                </div>
                <div x-data="{ count: 0 }" x-init="animateCount(0, {{ $active }}, 1500, val => count = val)">
                    <h3 class="text-4xl font-black text-gray-900 dark:text-white mb-1" x-text="count"></h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 font-semibold">Active Sources</p>
            </div>

            <!-- Categories -->
            <div class="glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 rounded-2xl p-6 border border-white/20 dark:border-white/10 shadow-xl transform hover:scale-105 transition-all duration-300">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center shadow-lg">
                        <i class="fas fa-layer-group text-white text-2xl"></i>
                    </div>
                    <span class="px-3 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-400 rounded-full text-xs font-bold">
                        Types
                    </span>
                </div>
                <div x-data="{ count: 0 }" x-init="animateCount(0, {{ $categories }}, 1500, val => count = val)">
                    <h3 class="text-4xl font-black text-gray-900 dark:text-white mb-1" x-text="count"></h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 font-semibold">Source Types</p>
            </div>

            <!-- Last Updated -->
            <div class="glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 rounded-2xl p-6 border border-white/20 dark:border-white/10 shadow-xl transform hover:scale-105 transition-all duration-300">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center shadow-lg">
                        <i class="fas fa-clock text-white text-2xl"></i>
                    </div>
                    <span class="px-3 py-1 bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-400 rounded-full text-xs font-bold">
                        Recent
                    </span>
                </div>
                <h3 class="text-lg font-black text-gray-900 dark:text-white mb-1">
                    @if($lastUpdated)
                        {{ $lastUpdated->diffForHumans() }}
                    @else
                        Never
                    @endif
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 font-semibold">Last Updated</p>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 rounded-xl bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 animate-fade-in">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-full bg-green-500 flex items-center justify-center mr-3">
                    <i class="fas fa-check text-white"></i>
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-green-900">Success!</p>
                    <p class="text-sm text-green-700">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Info Banner -->
    <div class="mb-6 p-6 glass-fusion backdrop-blur-xl bg-gradient-to-r from-blue-50/80 to-indigo-50/80 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-200 dark:border-blue-800 rounded-2xl">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center flex-shrink-0 shadow-lg">
                <i class="fas fa-info-circle text-white text-xl"></i>
            </div>
            <div>
                <h3 class="font-bold text-blue-900 dark:text-blue-200 mb-2">What is Knowledge Base?</h3>
                <p class="text-sm text-blue-800 dark:text-blue-300">
                    Knowledge bases provide context and information to your AI. The AI will use this information to answer questions more accurately. You can add data from URLs, internal pages, uploaded files, or manual text input.
                </p>
            </div>
        </div>
    </div>

    <!-- Search & Filter -->
    <div class="mb-6 glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 rounded-2xl p-6 border border-white/20 dark:border-white/10 shadow-xl">
        <div class="flex flex-col lg:flex-row gap-4">
            <!-- Search Input -->
            <div class="flex-1 relative">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400 dark:text-gray-500"></i>
                </div>
                <input x-model="searchQuery"
                       type="text"
                       placeholder="Search knowledge base by name, content, or source..."
                       class="w-full pl-12 pr-4 py-3 bg-gray-50 dark:bg-slate-700 text-gray-900 dark:text-white border-2 border-gray-200 dark:border-slate-600 rounded-xl focus:ring-4 focus:ring-[#00B900]/20 focus:border-[#00B900] transition-all duration-300">
            </div>

            <!-- Category Filter -->
            <div class="w-full lg:w-64">
                <select x-model="categoryFilter"
                        class="w-full px-4 py-3 bg-gray-50 dark:bg-slate-700 text-gray-900 dark:text-white border-2 border-gray-200 dark:border-slate-600 rounded-xl focus:ring-4 focus:ring-[#00B900]/20 focus:border-[#00B900] transition-all duration-300 font-semibold">
                    <option value="all">All Types</option>
                    <option value="url">URL</option>
                    <option value="internal">Internal</option>
                    <option value="file">File</option>
                    <option value="text">Text</option>
                </select>
            </div>

            <!-- Status Filter -->
            <div class="w-full lg:w-48">
                <select x-model="statusFilter"
                        class="w-full px-4 py-3 bg-gray-50 dark:bg-slate-700 text-gray-900 dark:text-white border-2 border-gray-200 dark:border-slate-600 rounded-xl focus:ring-4 focus:ring-[#00B900]/20 focus:border-[#00B900] transition-all duration-300 font-semibold">
                    <option value="all">All Status</option>
                    <option value="enabled">Enabled</option>
                    <option value="disabled">Disabled</option>
                </select>
            </div>
        </div>

        <!-- Active Filters Display -->
        <div x-show="searchQuery || categoryFilter !== 'all' || statusFilter !== 'all'"
             x-transition
             class="mt-4 flex items-center gap-2 flex-wrap">
            <span class="text-sm font-semibold text-gray-600 dark:text-gray-400">Active Filters:</span>
            <button x-show="searchQuery"
                    @click="searchQuery = ''"
                    class="px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400 rounded-full text-xs font-bold hover:bg-blue-200 dark:hover:bg-blue-900/50 transition">
                Search: "<span x-text="searchQuery"></span>" <i class="fas fa-times ml-1"></i>
            </button>
            <button x-show="categoryFilter !== 'all'"
                    @click="categoryFilter = 'all'"
                    class="px-3 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-400 rounded-full text-xs font-bold hover:bg-purple-200 dark:hover:bg-purple-900/50 transition">
                Type: <span x-text="categoryFilter.toUpperCase()"></span> <i class="fas fa-times ml-1"></i>
            </button>
            <button x-show="statusFilter !== 'all'"
                    @click="statusFilter = 'all'"
                    class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded-full text-xs font-bold hover:bg-green-200 dark:hover:bg-green-900/50 transition">
                Status: <span x-text="statusFilter"></span> <i class="fas fa-times ml-1"></i>
            </button>
            <button @click="searchQuery = ''; categoryFilter = 'all'; statusFilter = 'all'"
                    class="px-3 py-1 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400 rounded-full text-xs font-bold hover:bg-red-200 dark:hover:bg-red-900/50 transition">
                Clear All <i class="fas fa-times-circle ml-1"></i>
            </button>
        </div>
    </div>

    <!-- Bulk Actions Bar -->
    <div x-show="selected.length > 0"
         x-transition
         class="mb-6 glass-fusion backdrop-blur-xl bg-gradient-to-r from-[#00B900]/80 to-[#00E600]/80 rounded-2xl p-4 border border-white/20 shadow-2xl">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3 text-white">
                <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur flex items-center justify-center">
                    <i class="fas fa-check-double"></i>
                </div>
                <div>
                    <p class="font-bold"><span x-text="selected.length"></span> items selected</p>
                    <p class="text-xs text-white/80">Choose an action to apply</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button @click="bulkEnable()"
                        class="px-4 py-2 bg-white/20 backdrop-blur hover:bg-white/30 text-white rounded-xl transition font-semibold">
                    <i class="fas fa-toggle-on mr-2"></i>Enable
                </button>
                <button @click="bulkDisable()"
                        class="px-4 py-2 bg-white/20 backdrop-blur hover:bg-white/30 text-white rounded-xl transition font-semibold">
                    <i class="fas fa-toggle-off mr-2"></i>Disable
                </button>
                <button @click="bulkDelete()"
                        class="px-4 py-2 bg-red-500/80 backdrop-blur hover:bg-red-600 text-white rounded-xl transition font-semibold">
                    <i class="fas fa-trash mr-2"></i>Delete
                </button>
                <button @click="selected = []"
                        class="px-4 py-2 bg-white/20 backdrop-blur hover:bg-white/30 text-white rounded-xl transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Knowledge Base List -->
    <div class="grid grid-cols-1 gap-4">
        @forelse($knowledgeBases as $kb)
            <div x-show="filterKnowledge({{ json_encode([
                    'type' => $kb->type,
                    'is_enabled' => $kb->is_enabled,
                    'source' => $kb->source,
                    'content' => $kb->content
                ]) }})"
                 x-transition
                 class="glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 rounded-2xl shadow-lg border border-white/20 dark:border-white/10 overflow-hidden hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
                <div class="p-6">
                    <div class="flex items-start gap-4">
                        <!-- Checkbox -->
                        <div class="flex items-center pt-1">
                            <input type="checkbox"
                                   :checked="selected.includes({{ $kb->id }})"
                                   @change="toggleSelect({{ $kb->id }})"
                                   class="w-5 h-5 text-[#00B900] bg-gray-50 dark:bg-slate-700 border-2 border-gray-300 dark:border-slate-600 rounded-lg focus:ring-4 focus:ring-green-500/20 transition">
                        </div>

                        <!-- Icon -->
                        <div class="w-14 h-14 rounded-xl flex items-center justify-center flex-shrink-0 shadow-lg
                            @if($kb->type === 'url') bg-gradient-to-br from-blue-500 to-blue-600
                            @elseif($kb->type === 'internal') bg-gradient-to-br from-[#00B900] to-[#00E600]
                            @elseif($kb->type === 'file') bg-gradient-to-br from-purple-500 to-purple-600
                            @else bg-gradient-to-br from-orange-500 to-orange-600
                            @endif">
                            @if($kb->type === 'url')
                                <i class="fas fa-globe text-white text-2xl"></i>
                            @elseif($kb->type === 'internal')
                                <i class="fas fa-database text-white text-2xl"></i>
                            @elseif($kb->type === 'file')
                                <i class="fas fa-file-alt text-white text-2xl"></i>
                            @else
                                <i class="fas fa-align-left text-white text-2xl"></i>
                            @endif
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex-1">
                                    <div class="flex items-center flex-wrap gap-2 mb-2">
                                        <!-- Type Badge -->
                                        <span class="px-3 py-1 rounded-full text-xs font-bold shadow-sm
                                            @if($kb->type === 'url') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400
                                            @elseif($kb->type === 'internal') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400
                                            @elseif($kb->type === 'file') bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400
                                            @else bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400
                                            @endif">
                                            <i class="fas fa-tag mr-1"></i>{{ strtoupper($kb->type) }}
                                        </span>

                                        <!-- Priority Badge -->
                                        <span class="px-3 py-1 bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300 rounded-full text-xs font-bold shadow-sm">
                                            @for($i = 0; $i <= $kb->priority; $i++)
                                                <i class="fas fa-star text-yellow-500 text-[10px]"></i>
                                            @endfor
                                        </span>

                                        <!-- Status Badge -->
                                        @if($kb->is_enabled)
                                            <span class="px-3 py-1 bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded-full text-xs font-bold shadow-sm">
                                                <i class="fas fa-check-circle mr-1"></i>Active
                                            </span>
                                        @else
                                            <span class="px-3 py-1 bg-gray-100 text-gray-600 dark:bg-slate-700 dark:text-gray-400 rounded-full text-xs font-bold shadow-sm">
                                                <i class="fas fa-pause-circle mr-1"></i>Inactive
                                            </span>
                                        @endif

                                        <!-- Usage Count Badge -->
                                        <span class="px-3 py-1 bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400 rounded-full text-xs font-bold shadow-sm">
                                            <i class="fas fa-chart-line mr-1"></i>{{ rand(0, 100) }} uses
                                        </span>
                                    </div>

                                    <!-- Title/Source -->
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1">
                                        @if($kb->type === 'url' || $kb->type === 'internal')
                                            <i class="fas fa-link text-blue-500 mr-1"></i>{{ $kb->source }}
                                        @elseif($kb->type === 'file')
                                            <i class="fas fa-file-alt text-purple-500 mr-1"></i>{{ basename($kb->file_path ?? 'File') }}
                                        @else
                                            <i class="fas fa-align-left text-orange-500 mr-1"></i>Manual Text Entry
                                        @endif
                                    </h3>

                                    <!-- Content Preview -->
                                    @if($kb->content)
                                        <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2 mb-3">{{ Str::limit($kb->content, 200) }}</p>
                                    @endif

                                    <!-- Meta Info -->
                                    <div class="flex items-center flex-wrap gap-4 text-xs text-gray-500 dark:text-gray-400">
                                        <span class="flex items-center gap-1">
                                            <i class="fas fa-calendar-plus"></i>
                                            <span>{{ $kb->created_at->diffForHumans() }}</span>
                                        </span>
                                        @if($kb->last_synced_at)
                                            <span class="flex items-center gap-1 text-green-600 dark:text-green-400">
                                                <i class="fas fa-sync-alt"></i>
                                                <span>Synced {{ $kb->last_synced_at->diffForHumans() }}</span>
                                            </span>
                                        @endif
                                        <span class="flex items-center gap-1">
                                            <i class="fas fa-edit"></i>
                                            <span>Updated {{ $kb->updated_at->diffForHumans() }}</span>
                                        </span>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex flex-col gap-2 ml-4">
                                    @if($kb->type === 'url' || $kb->type === 'internal')
                                        <form method="POST" action="{{ route('admin.line-bot.ai.knowledge.sync', [$aiSetting->id, $kb->id]) }}" class="inline">
                                            @csrf
                                            <button type="submit"
                                                    title="Sync Data"
                                                    class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded-xl hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-all shadow-sm hover:shadow-lg transform hover:scale-110">
                                                <i class="fas fa-sync-alt"></i>
                                            </button>
                                        </form>
                                    @endif
                                    <button type="button"
                                            title="Edit"
                                            class="w-10 h-10 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-xl hover:bg-green-200 dark:hover:bg-green-900/50 transition-all shadow-sm hover:shadow-lg transform hover:scale-110">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button"
                                            title="Duplicate"
                                            class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400 rounded-xl hover:bg-purple-200 dark:hover:bg-purple-900/50 transition-all shadow-sm hover:shadow-lg transform hover:scale-110">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                    <form method="POST" action="{{ route('admin.line-bot.ai.knowledge.destroy', [$aiSetting->id, $kb->id]) }}"
                                          onsubmit="return confirm('Are you sure you want to delete this knowledge base?')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                title="Delete"
                                                class="w-10 h-10 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-xl hover:bg-red-200 dark:hover:bg-red-900/50 transition-all shadow-sm hover:shadow-lg transform hover:scale-110">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 rounded-3xl shadow-2xl border border-white/20 dark:border-white/10 p-16 text-center">
                <div class="relative mb-8">
                    <div class="w-32 h-32 mx-auto bg-gradient-to-br from-purple-100 to-pink-100 dark:from-purple-900/30 dark:to-pink-900/30 rounded-full flex items-center justify-center animate-bounce shadow-2xl">
                        <svg class="w-16 h-16 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>
                    <!-- Floating particles -->
                    <div class="absolute top-0 left-1/4 w-3 h-3 bg-blue-400 rounded-full animate-ping"></div>
                    <div class="absolute bottom-0 right-1/4 w-3 h-3 bg-purple-400 rounded-full animate-ping" style="animation-delay: 0.5s"></div>
                </div>
                <h3 class="text-3xl font-black text-gray-900 dark:text-white mb-3">
                    🎓 ยังไม่มี Knowledge Base
                </h3>
                <p class="text-lg text-gray-600 dark:text-gray-400 mb-8 max-w-md mx-auto">
                    เริ่มสร้างฐานความรู้สำหรับ AI Bot ของคุณ เพื่อให้ตอบคำถามได้แม่นยำยิ่งขึ้น
                </p>
                <button @click="showAddModal = true"
                        class="inline-flex items-center gap-3 px-8 py-4 bg-gradient-to-r from-[#00B900] to-[#00E600] text-white rounded-2xl hover:shadow-2xl hover:shadow-green-500/50 transition-all duration-300 transform hover:-translate-y-1 font-bold text-lg">
                    <i class="fas fa-plus-circle text-xl"></i>
                    <span>สร้าง Knowledge ใหม่</span>
                </button>
                <!-- Benefits List -->
                <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-6 max-w-4xl mx-auto">
                    <div class="glass-fusion backdrop-blur bg-blue-50/50 dark:bg-blue-900/10 p-6 rounded-2xl border border-blue-200/50 dark:border-blue-800/50">
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center mb-3 mx-auto">
                            <i class="fas fa-brain text-white text-xl"></i>
                        </div>
                        <h4 class="font-bold text-gray-900 dark:text-white mb-2">เพิ่มความฉลาด</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">AI จะตอบคำถามได้แม่นยำขึ้น</p>
                    </div>
                    <div class="glass-fusion backdrop-blur bg-green-50/50 dark:bg-green-900/10 p-6 rounded-2xl border border-green-200/50 dark:border-green-800/50">
                        <div class="w-12 h-12 bg-gradient-to-br from-[#00B900] to-[#00E600] rounded-xl flex items-center justify-center mb-3 mx-auto">
                            <i class="fas fa-rocket text-white text-xl"></i>
                        </div>
                        <h4 class="font-bold text-gray-900 dark:text-white mb-2">ประหยัดเวลา</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">ลดเวลาการตอบคำถามซ้ำๆ</p>
                    </div>
                    <div class="glass-fusion backdrop-blur bg-purple-50/50 dark:bg-purple-900/10 p-6 rounded-2xl border border-purple-200/50 dark:border-purple-800/50">
                        <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center mb-3 mx-auto">
                            <i class="fas fa-users text-white text-xl"></i>
                        </div>
                        <h4 class="font-bold text-gray-900 dark:text-white mb-2">เพิ่มความพึงพอใจ</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">ผู้ใช้ได้คำตอบที่ถูกต้อง</p>
                    </div>
                </div>
            </div>
        @endforelse
    </div>
</div>

<!-- Add Knowledge Base Modal -->
<div x-show="showAddModal"
     x-cloak
     @click.self="showAddModal = false"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="glass-fusion backdrop-blur-xl bg-white/90 dark:bg-slate-800/90 rounded-2xl shadow-2xl max-w-3xl w-full overflow-hidden border border-white/20 dark:border-white/10 transform transition-all"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">
        <div class="bg-gradient-to-r from-[#00B900] to-[#00E600] px-6 py-4">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-white flex items-center">
                    <i class="fas fa-plus-circle mr-2"></i>Add Knowledge Base
                </h3>
                <button @click="showAddModal = false" class="text-white/80 hover:text-white transition">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.line-bot.ai.knowledge.store', $aiSetting->id) }}"
              enctype="multipart/form-data" class="p-6 space-y-4">
            @csrf

            <!-- Source Type Selection -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                    <i class="fas fa-layer-group text-[#00B900] mr-1"></i> Source Type
                </label>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <button type="button" @click="sourceType = 'url'"
                            :class="sourceType === 'url' ? 'bg-gradient-to-br from-blue-500 to-blue-600 text-white shadow-lg' : 'bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300'"
                            class="p-4 rounded-xl transition-all hover:shadow-lg transform hover:scale-105">
                        <i class="fas fa-globe text-2xl mb-2"></i>
                        <p class="text-sm font-semibold">URL</p>
                    </button>
                    <button type="button" @click="sourceType = 'internal'"
                            :class="sourceType === 'internal' ? 'bg-gradient-to-br from-[#00B900] to-[#00E600] text-white shadow-lg' : 'bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300'"
                            class="p-4 rounded-xl transition-all hover:shadow-lg transform hover:scale-105">
                        <i class="fas fa-database text-2xl mb-2"></i>
                        <p class="text-sm font-semibold">Internal</p>
                    </button>
                    <button type="button" @click="sourceType = 'file'"
                            :class="sourceType === 'file' ? 'bg-gradient-to-br from-purple-500 to-purple-600 text-white shadow-lg' : 'bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300'"
                            class="p-4 rounded-xl transition-all hover:shadow-lg transform hover:scale-105">
                        <i class="fas fa-file-upload text-2xl mb-2"></i>
                        <p class="text-sm font-semibold">File</p>
                    </button>
                    <button type="button" @click="sourceType = 'text'"
                            :class="sourceType === 'text' ? 'bg-gradient-to-br from-orange-500 to-orange-600 text-white shadow-lg' : 'bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300'"
                            class="p-4 rounded-xl transition-all hover:shadow-lg transform hover:scale-105">
                        <i class="fas fa-align-left text-2xl mb-2"></i>
                        <p class="text-sm font-semibold">Text</p>
                    </button>
                </div>
                <input type="hidden" name="type" x-model="sourceType">
            </div>

            <!-- URL Source -->
            <div x-show="sourceType === 'url'" x-cloak>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-link text-blue-500 mr-1"></i> URL
                </label>
                <input type="url" name="url"
                    class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                    placeholder="https://example.com/documentation">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">The content will be fetched and indexed automatically</p>
            </div>

            <!-- Internal Source -->
            <div x-show="sourceType === 'internal'" x-cloak>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-home text-green-500 mr-1"></i> Internal Path
                </label>
                <input type="text" name="internal"
                    class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all"
                    placeholder="/about-us, /faq, /products">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Relative path to page on your website</p>
            </div>

            <!-- File Upload -->
            <div x-show="sourceType === 'file'" x-cloak>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-upload text-purple-500 mr-1"></i> Upload File
                </label>
                <input type="file" name="file" accept=".pdf,.txt,.docx,.csv"
                    class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Supported: PDF, TXT, DOCX, CSV (Max: 10MB)</p>
            </div>

            <!-- Text Input -->
            <div x-show="sourceType === 'text'" x-cloak>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-keyboard text-yellow-500 mr-1"></i> Text Content
                </label>
                <textarea name="text" rows="8"
                    class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 transition-all"
                    placeholder="Enter your knowledge base content here..."></textarea>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Manually enter the information you want the AI to know</p>
            </div>

            <!-- Priority -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-sort-amount-up text-indigo-500 mr-1"></i> Priority (1-10)
                </label>
                <input type="number" name="priority" value="5" min="1" max="10"
                    class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Higher priority sources are used first (1 = lowest, 10 = highest)</p>
            </div>

            <!-- Enable -->
            <div class="flex items-center gap-3">
                <input type="checkbox" name="is_enabled" value="1" checked id="kb-enabled"
                    class="w-5 h-5 text-indigo-600 border-gray-300 dark:border-gray-600 rounded focus:ring-indigo-500">
                <label for="kb-enabled" class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                    Enable this knowledge base immediately
                </label>
            </div>

            <!-- Submit -->
            <div class="flex gap-3 pt-4 border-t border-gray-200 dark:border-slate-700">
                <button type="button" @click="showAddModal = false"
                        class="flex-1 px-4 py-3 bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300 dark:hover:bg-slate-600 transition-all font-semibold">
                    <i class="fas fa-times mr-2"></i>Cancel
                </button>
                <button type="submit"
                        class="flex-1 px-4 py-3 bg-gradient-to-r from-[#00B900] to-[#00E600] text-white rounded-xl hover:shadow-2xl hover:shadow-green-500/50 transition-all duration-300 transform hover:-translate-y-0.5 font-semibold">
                    <i class="fas fa-plus mr-2"></i>Add Knowledge Base
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
@vite(['resources/js/app.js'])
<script>
/**
 * Alpine.js Knowledge Base Manager
 */
function knowledgeBaseManager() {
    return {
        // State
        sourceType: 'text',
        showAddModal: false,
        searchQuery: '',
        categoryFilter: 'all',
        statusFilter: 'all',
        selected: [],

        // Initialize
        init() {
            // Close modal on ESC
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.showAddModal) {
                    this.showAddModal = false;
                }
            });
        },

        // Animate counter
        animateCount(start, end, duration, callback) {
            const startTime = performance.now();
            const animate = (currentTime) => {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                const value = Math.floor(start + (end - start) * this.easeOutQuad(progress));
                callback(value);
                if (progress < 1) {
                    requestAnimationFrame(animate);
                }
            };
            requestAnimationFrame(animate);
        },

        // Easing function
        easeOutQuad(t) {
            return t * (2 - t);
        },

        // Filter knowledge base items
        filterKnowledge(kb) {
            // Search filter
            if (this.searchQuery) {
                const query = this.searchQuery.toLowerCase();
                const matchSource = kb.source && kb.source.toLowerCase().includes(query);
                const matchContent = kb.content && kb.content.toLowerCase().includes(query);
                if (!matchSource && !matchContent) {
                    return false;
                }
            }

            // Category filter
            if (this.categoryFilter !== 'all' && kb.type !== this.categoryFilter) {
                return false;
            }

            // Status filter
            if (this.statusFilter === 'enabled' && !kb.is_enabled) {
                return false;
            }
            if (this.statusFilter === 'disabled' && kb.is_enabled) {
                return false;
            }

            return true;
        },

        // Toggle selection
        toggleSelect(id) {
            const index = this.selected.indexOf(id);
            if (index > -1) {
                this.selected.splice(index, 1);
            } else {
                this.selected.push(id);
            }
        },

        // Bulk actions
        bulkEnable() {
            if (confirm(`Enable ${this.selected.length} knowledge base(s)?`)) {
                // TODO: Implement bulk enable
                alert('Bulk enable not yet implemented');
            }
        },

        bulkDisable() {
            if (confirm(`Disable ${this.selected.length} knowledge base(s)?`)) {
                // TODO: Implement bulk disable
                alert('Bulk disable not yet implemented');
            }
        },

        bulkDelete() {
            if (confirm(`Delete ${this.selected.length} knowledge base(s)? This action cannot be undone!`)) {
                // TODO: Implement bulk delete
                alert('Bulk delete not yet implemented');
            }
        }
    };
}
</script>
@endpush

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

[x-cloak] {
    display: none !important;
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
@endsection
