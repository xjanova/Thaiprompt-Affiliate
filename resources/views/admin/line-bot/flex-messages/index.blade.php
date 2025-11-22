@extends('layouts.admin-v3')

@section('title', 'Flex Message Templates')

@push('styles')
@vite(['resources/css/app.css'])
@endpush

@section('content')
<div class="container-fluid px-4 py-6" x-data="flexMessageIndex()">
    <!-- Animated Header with LINE Green Theme -->
    <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-[#00B900] via-[#06C755] to-[#00E600] p-8 shadow-2xl">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS1vcGFjaXR5PSIwLjEiIHN0cm9rZS13aWR0aD0iMSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNncmlkKSIvPjwvc3ZnPg==')] opacity-30"></div>
        <div class="relative flex items-center justify-between">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-14 h-14 rounded-xl glass-fusion backdrop-blur-sm border border-white/20 flex items-center justify-center">
                        <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-white mb-1">Flex Message Templates</h1>
                        <p class="text-green-100">Create beautiful, interactive LINE messages</p>
                    </div>
                </div>
            </div>
            <div>
                <a href="{{ route('admin.line-bot.flex.create') }}"
                   class="px-6 py-3 glass-fusion backdrop-blur-md border border-white/30 text-white rounded-xl hover:bg-white/10 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                    <i class="fas fa-plus mr-2"></i>New Template
                </a>
            </div>
        </div>
    </div>

    <!-- Statistics Cards with LINE Green Theme -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <!-- Total Flex Messages -->
        <div class="glass-fusion backdrop-blur-xl bg-gradient-to-br from-purple-50/80 to-pink-50/80 dark:from-purple-900/30 dark:to-pink-900/30 p-6 rounded-2xl border border-white/20 dark:border-purple-700/50">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-purple-700 dark:text-purple-300">Flex Messages</h3>
                <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center">
                    <span class="text-2xl">💬</span>
                </div>
            </div>
            <div x-data="{ count: 0 }" x-init="animateCount(0, {{ $templates->total() }}, 1500, val => count = val)">
                <h2 class="text-4xl font-bold text-gray-900 dark:text-white mb-1" x-text="Math.floor(count)">0</h2>
            </div>
            <p class="text-xs text-purple-600 dark:text-purple-400">Total templates created</p>
        </div>

        <!-- Templates by Category -->
        <div class="glass-fusion backdrop-blur-xl bg-gradient-to-br from-blue-50/80 to-cyan-50/80 dark:from-blue-900/30 dark:to-cyan-900/30 p-6 rounded-2xl border border-white/20 dark:border-blue-700/50">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-blue-700 dark:text-blue-300">Categories</h3>
                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl flex items-center justify-center">
                    <span class="text-2xl">📋</span>
                </div>
            </div>
            <div x-data="{ count: 0 }" x-init="animateCount(0, {{ $templates->pluck('category')->unique()->count() }}, 1500, val => count = val)">
                <h2 class="text-4xl font-bold text-gray-900 dark:text-white mb-1" x-text="Math.floor(count)">0</h2>
            </div>
            <p class="text-xs text-blue-600 dark:text-blue-400">Different categories</p>
        </div>

        <!-- Active Templates -->
        <div class="glass-fusion backdrop-blur-xl bg-gradient-to-br from-green-50/80 to-emerald-50/80 dark:from-green-900/30 dark:to-emerald-900/30 p-6 rounded-2xl border border-white/20 dark:border-green-700/50">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-green-700 dark:text-green-300">Active</h3>
                <div class="w-12 h-12 bg-gradient-to-br from-[#00B900] to-[#00E600] rounded-xl flex items-center justify-center">
                    <span class="text-2xl">✅</span>
                </div>
            </div>
            <div x-data="{ count: 0 }" x-init="animateCount(0, {{ $templates->where('is_seed', false)->count() }}, 1500, val => count = val)">
                <h2 class="text-4xl font-bold text-gray-900 dark:text-white mb-1" x-text="Math.floor(count)">0</h2>
            </div>
            <p class="text-xs text-green-600 dark:text-green-400">Custom templates</p>
        </div>

        <!-- Total Usage -->
        <div class="glass-fusion backdrop-blur-xl bg-gradient-to-br from-orange-50/80 to-red-50/80 dark:from-orange-900/30 dark:to-red-900/30 p-6 rounded-2xl border border-white/20 dark:border-orange-700/50">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-orange-700 dark:text-orange-300">Total Sends</h3>
                <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-red-600 rounded-xl flex items-center justify-center">
                    <span class="text-2xl">📨</span>
                </div>
            </div>
            <div x-data="{ count: 0 }" x-init="animateCount(0, {{ $templates->sum('usage_count') }}, 1500, val => count = val)">
                <h2 class="text-4xl font-bold text-gray-900 dark:text-white mb-1" x-text="Math.floor(count)">0</h2>
            </div>
            <p class="text-xs text-orange-600 dark:text-orange-400">Messages sent</p>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 rounded-xl bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/30 dark:to-emerald-900/30 border border-green-200 dark:border-green-700 animate-fade-in">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-[#00B900] to-[#00E600] flex items-center justify-center mr-3">
                    <i class="fas fa-check text-white"></i>
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-green-900 dark:text-green-100">Success!</p>
                    <p class="text-sm text-green-700 dark:text-green-300">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Category Filter with LINE Green Theme -->
    <div class="mb-6 flex gap-2 flex-wrap">
        <a href="{{ route('admin.line-bot.flex.index') }}"
           class="px-4 py-2 rounded-xl {{ !request('category') ? 'bg-gradient-to-r from-[#00B900] to-[#00E600] text-white' : 'glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700' }} transition">
            All Templates
        </a>
        <a href="{{ route('admin.line-bot.flex.index', ['category' => 'welcome']) }}"
           class="px-4 py-2 rounded-xl {{ request('category') === 'welcome' ? 'bg-gradient-to-r from-[#00B900] to-[#00E600] text-white' : 'glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700' }} transition">
            Welcome
        </a>
        <a href="{{ route('admin.line-bot.flex.index', ['category' => 'promotion']) }}"
           class="px-4 py-2 rounded-xl {{ request('category') === 'promotion' ? 'bg-gradient-to-r from-[#00B900] to-[#00E600] text-white' : 'glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700' }} transition">
            Promotion
        </a>
        <a href="{{ route('admin.line-bot.flex.index', ['category' => 'product']) }}"
           class="px-4 py-2 rounded-xl {{ request('category') === 'product' ? 'bg-gradient-to-r from-[#00B900] to-[#00E600] text-white' : 'glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700' }} transition">
            Product
        </a>
        <a href="{{ route('admin.line-bot.flex.index', ['category' => 'notification']) }}"
           class="px-4 py-2 rounded-xl {{ request('category') === 'notification' ? 'bg-gradient-to-r from-[#00B900] to-[#00E600] text-white' : 'glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700' }} transition">
            Notification
        </a>
    </div>

    <!-- Templates Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($templates as $template)
            <div class="glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 rounded-2xl shadow-lg border border-white/20 dark:border-slate-700/50 overflow-hidden hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                <!-- Header with LINE Green Theme -->
                <div class="bg-gradient-to-r from-[#00B900] to-[#00E600] px-6 py-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold text-white">{{ $template->name }}</h3>
                        @if($template->is_seed)
                            <span class="px-3 py-1 bg-yellow-400 text-yellow-900 rounded-full text-xs font-bold">
                                <i class="fas fa-star mr-1"></i>Seed
                            </span>
                        @endif
                    </div>
                    <p class="text-xs text-green-100 mt-1">{{ ucfirst($template->category) }}</p>
                </div>

                <!-- Content -->
                <div class="p-6">
                    @if($template->description)
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">{{ Str::limit($template->description, 100) }}</p>
                    @endif

                    <!-- Stats -->
                    <div class="flex items-center justify-between mb-4 pb-4 border-b border-gray-100 dark:border-slate-700">
                        <div class="text-center">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Used</p>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">{{ number_format($template->usage_count) }}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Created</p>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $template->created_at->format('M d') }}</p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-2">
                        <button @click="previewTemplate({{ $template->id }})"
                                class="flex-1 px-4 py-2 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-xl hover:bg-blue-200 dark:hover:bg-blue-900/50 transition text-sm font-semibold">
                            <i class="fas fa-eye mr-1"></i>Preview
                        </button>
                        <button @click="testSend({{ $template->id }})"
                                class="flex-1 px-4 py-2 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-xl hover:bg-green-200 dark:hover:bg-green-900/50 transition text-sm font-semibold">
                            <i class="fas fa-paper-plane mr-1"></i>Send
                        </button>
                        <a href="{{ route('admin.line-bot.flex.edit', $template->id) }}"
                           class="flex-1 px-4 py-2 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded-xl hover:bg-purple-200 dark:hover:bg-purple-900/50 transition text-sm font-semibold text-center">
                            <i class="fas fa-edit mr-1"></i>Edit
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-3">
                <div class="glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 rounded-2xl shadow-lg border border-white/20 dark:border-slate-700/50 p-12 text-center">
                    <div class="w-24 h-24 rounded-full bg-gradient-to-br from-green-100 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/30 flex items-center justify-center mx-auto mb-6">
                        <svg class="w-12 h-12 text-[#06C755]" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">No Templates Yet</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">Create your first Flex Message template</p>
                    <a href="{{ route('admin.line-bot.flex.create') }}"
                       class="inline-block px-6 py-3 bg-gradient-to-r from-[#00B900] to-[#00E600] text-white rounded-xl hover:shadow-lg transition transform hover:-translate-y-0.5">
                        <i class="fas fa-plus mr-2"></i>Create First Template
                    </a>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($templates->hasPages())
        <div class="mt-8">
            {{ $templates->links() }}
        </div>
    @endif
</div>

<!-- Preview Modal -->
<div x-show="showPreviewModal"
     x-cloak
     @keydown.escape.window="showPreviewModal = false"
     class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="glass-fusion backdrop-blur-xl bg-white/90 dark:bg-slate-800/90 rounded-2xl shadow-2xl max-w-md w-full overflow-hidden border border-white/20 dark:border-slate-700/50 transform transition-all">
        <div class="bg-gradient-to-r from-[#00B900] to-[#00E600] px-6 py-4">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-white flex items-center">
                    <i class="fas fa-eye mr-2"></i>Template Preview
                </h3>
                <button @click="showPreviewModal = false" class="text-white/80 hover:text-white transition">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>

        <div class="p-6 bg-gray-100/50 dark:bg-slate-900/50" style="max-height: 70vh; overflow-y: auto;">
            <div x-show="loadingPreview" class="text-center py-8">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-[#06C755]"></div>
                <p class="mt-4 text-gray-600 dark:text-gray-400">Loading preview...</p>
            </div>
            <div x-show="!loadingPreview" x-html="previewContent"></div>
        </div>
    </div>
</div>

<!-- Test Send Modal -->
<div x-show="showTestModal"
     x-cloak
     @keydown.escape.window="showTestModal = false"
     class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="glass-fusion backdrop-blur-xl bg-white/90 dark:bg-slate-800/90 rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden border border-white/20 dark:border-slate-700/50 transform transition-all">
        <div class="bg-gradient-to-r from-[#00B900] to-[#00E600] px-6 py-4">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-white flex items-center">
                    <i class="fas fa-paper-plane mr-2"></i>Test Send Template
                </h3>
                <button @click="showTestModal = false" class="text-white/80 hover:text-white transition">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>

        <form @submit.prevent="submitTest" class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-user text-[#06C755] mr-1"></i> LINE User ID
                </label>
                <input type="text" x-model="testUserId" required
                    class="w-full px-4 py-3 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-[#06C755] focus:border-[#06C755] transition-all"
                    placeholder="U1234567890abcdef1234567890abcdef">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">The recipient's LINE User ID</p>
            </div>

            <div x-show="testResult.show"
                 :class="testResult.success ? 'bg-green-50 dark:bg-green-900/30 border-green-200 dark:border-green-700' : 'bg-red-50 dark:bg-red-900/30 border-red-200 dark:border-red-700'"
                 class="p-4 rounded-xl border">
                <p class="text-sm"
                   :class="testResult.success ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200'"
                   x-text="testResult.message"></p>
            </div>

            <div class="flex gap-3 pt-4">
                <button type="button" @click="showTestModal = false"
                        class="flex-1 px-4 py-3 bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300 dark:hover:bg-slate-600 transition font-semibold">
                    Cancel
                </button>
                <button type="submit" :disabled="testSending"
                        class="flex-1 px-4 py-3 bg-gradient-to-r from-[#00B900] to-[#00E600] text-white rounded-xl hover:shadow-lg transition font-semibold disabled:opacity-50">
                    <i class="fas fa-paper-plane mr-2"></i>
                    <span x-text="testSending ? 'Sending...' : 'Send Test'"></span>
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
@vite(['resources/js/app.js'])
<script>
/**
 * Alpine.js component สำหรับ Flex Message Index
 */
function flexMessageIndex() {
    return {
        // State
        showPreviewModal: false,
        showTestModal: false,
        loadingPreview: false,
        previewContent: '',
        currentTemplateId: null,
        testUserId: '',
        testSending: false,
        testResult: {
            show: false,
            success: false,
            message: ''
        },

        /**
         * แสดง Preview Modal
         */
        async previewTemplate(templateId) {
            this.currentTemplateId = templateId;
            this.showPreviewModal = true;
            this.loadingPreview = true;
            this.previewContent = '';

            try {
                const response = await fetch(`/admin/line-bot/flex/${templateId}/preview`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.previewContent = data.html || '<p class="text-center text-gray-500 dark:text-gray-400">Preview not available</p>';
                } else {
                    this.previewContent = '<p class="text-center text-red-500 dark:text-red-400">Failed to load preview</p>';
                }
            } catch (error) {
                this.previewContent = `<p class="text-center text-red-500 dark:text-red-400">Error: ${error.message}</p>`;
            } finally {
                this.loadingPreview = false;
            }
        },

        /**
         * แสดง Test Send Modal
         */
        testSend(templateId) {
            this.currentTemplateId = templateId;
            this.showTestModal = true;
            this.testUserId = '';
            this.testResult = {
                show: false,
                success: false,
                message: ''
            };
        },

        /**
         * ส่ง Test Message
         */
        async submitTest() {
            if (!this.testUserId) return;

            this.testSending = true;
            this.testResult.show = false;

            try {
                const response = await fetch(`/admin/line-bot/flex/${this.currentTemplateId}/test-send`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        line_user_id: this.testUserId
                    })
                });

                const data = await response.json();

                this.testResult = {
                    show: true,
                    success: data.success,
                    message: data.message || (data.success ? 'Message sent successfully!' : 'Failed to send message')
                };

                if (data.success) {
                    setTimeout(() => {
                        this.showTestModal = false;
                    }, 2000);
                }
            } catch (error) {
                this.testResult = {
                    show: true,
                    success: false,
                    message: `Error: ${error.message}`
                };
            } finally {
                this.testSending = false;
            }
        }
    };
}

/**
 * Animate number counting
 */
function animateCount(start, end, duration, callback) {
    const startTime = Date.now();
    const range = end - start;

    function update() {
        const now = Date.now();
        const progress = Math.min((now - startTime) / duration, 1);
        const easeOutQuad = progress * (2 - progress); // Easing function
        const current = start + (range * easeOutQuad);

        callback(current);

        if (progress < 1) {
            requestAnimationFrame(update);
        } else {
            callback(end);
        }
    }

    requestAnimationFrame(update);
}
</script>
@endpush

<style>
/* Fade-in animation */
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

/* Alpine x-cloak */
[x-cloak] {
    display: none !important;
}
</style>
@endsection
