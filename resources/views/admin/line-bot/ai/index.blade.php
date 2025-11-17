@extends('layouts.admin-v3')

@section('title', 'ตั้งค่าแชทบอท AI - LINE Official Account')

@section('content')
<div class="container-fluid px-4 py-6">
    <!-- Premium Animated Header -->
    <div class="relative mb-8 overflow-hidden rounded-3xl bg-gradient-to-br from-purple-600 via-indigo-700 to-purple-900 p-10 shadow-2xl">
        <!-- Animated Background Pattern -->
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS1vcGFjaXR5PSIwLjA1IiBzdHJva2Utd2lkdGg9IjEiLz48L3BhdHRlcm4+PC9kZWZzPjxyZWN0IHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIGZpbGw9InVybCgjZ3JpZCkiLz48L3N2Zz4=')] opacity-40"></div>

        <!-- Floating Particles Effect -->
        <div class="absolute inset-0">
            <div class="absolute top-10 left-10 w-2 h-2 glass-fusion rounded-full animate-ping" border border-white/20 dark:border-white/10></div>
            <div class="absolute top-20 right-20 w-3 h-3 bg-purple-300/40 rounded-full animate-pulse"></div>
            <div class="absolute bottom-10 left-1/3 w-2 h-2 bg-indigo-300/30 rounded-full animate-bounce"></div>
        </div>

        <div class="relative flex items-center justify-between">
            <div class="flex-1">
                <div class="flex items-center gap-4 mb-3">
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-white/25 to-white/10 backdrop-blur-lg flex items-center justify-center shadow-xl border border-white/20">
                        <svg class="w-10 h-10 text-white drop-shadow-lg" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-4xl font-black text-white mb-2 drop-shadow-lg tracking-tight">🤖 ตั้งค่าแชทบอท AI</h1>
                        <p class="text-purple-100 text-lg font-medium">ควบคุมและปรับแต่ง AI เพื่อตอบคำถามลูกค้าอัตโนมัติ</p>
                        <div class="flex items-center gap-4 mt-2">
                            <span class="text-xs glass-fusion backdrop-blur-sm px-3 py-1 rounded-full text-white font-semibold border border-white/30">
                                OpenAI • DeepSeek • Claude • Gemini
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.line-bot.ai.conversations') }}"
                   class="px-6 py-3 glass-fusion backdrop-blur-md border border-white/25 text-white rounded-xl hover:glass-fusion transition-all duration-300 shadow-lg hover:shadow-2xl transform hover:-translate-y-1 hover:scale-105 flex items-center gap-2">
                    <i class="fas fa-comments"></i>
                    <span class="font-semibold">บทสนทนา</span>
                </a>
                <a href="{{ route('admin.line-bot.ai.create') }}"
                   class="px-8 py-3 bg-gradient-to-r from-white to-purple-50 text-purple-700 rounded-xl hover:from-purple-50 hover:to-white transition-all duration-300 shadow-xl hover:shadow-2xl transform hover:-translate-y-1 hover:scale-105 font-bold flex items-center gap-2">
                    <i class="fas fa-plus-circle"></i>
                    <span>เพิ่ม AI ใหม่</span>
                </a>
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

    @if(session('error'))
        <div class="mb-6 p-4 rounded-xl bg-gradient-to-r from-red-50 to-pink-50 border border-red-200 animate-fade-in">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-full bg-red-500 flex items-center justify-center mr-3">
                    <i class="fas fa-exclamation-circle text-white"></i>
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-red-900">Error!</p>
                    <p class="text-sm text-red-700">{{ session('error') }}</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="style="background: var(--arrow-x-primary-gradient)" rounded-2xl p-6 text-white shadow-xl hover:shadow-2xl transition-all transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm font-medium mb-1">การตั้งค่า AI ทั้งหมด</p>
                    <h3 class="text-3xl font-bold">{{ $aiSettings->count() }}</h3>
                </div>
                <div class="w-14 h-14 glass-fusion rounded-xl flex items-center justify-center" border border-white/20 dark:border-white/10>
                    <i class="fas fa-robot text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-2xl p-6 text-white shadow-xl hover:shadow-2xl transition-all transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm font-medium mb-1">AI ที่เปิดใช้งาน</p>
                    <h3 class="text-3xl font-bold">{{ $aiSettings->where('is_active', true)->count() }}</h3>
                </div>
                <div class="w-14 h-14 glass-fusion rounded-xl flex items-center justify-center" border border-white/20 dark:border-white/10>
                    <i class="fas fa-check-circle text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="style="background: var(--arrow-x-accent-gradient)" rounded-2xl p-6 text-white shadow-xl hover:shadow-2xl transition-all transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm font-medium mb-1">ฐานความรู้</p>
                    <h3 class="text-3xl font-bold">{{ $aiSettings->sum(fn($s) => $s->knowledgeBases->count()) }}</h3>
                </div>
                <div class="w-14 h-14 glass-fusion rounded-xl flex items-center justify-center" border border-white/20 dark:border-white/10>
                    <i class="fas fa-book text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-2xl p-6 text-white shadow-xl hover:shadow-2xl transition-all transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-orange-100 text-sm font-medium mb-1">บทสนทนาวันนี้</p>
                    <h3 class="text-3xl font-bold">0</h3>
                </div>
                <div class="w-14 h-14 glass-fusion rounded-xl flex items-center justify-center" border border-white/20 dark:border-white/10>
                    <i class="fas fa-comments text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- AI Settings Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($aiSettings as $setting)
            <div class="group glass-fusion dark:bg-slate-800 rounded-2xl shadow-xl border-2 border-gray-100 dark:border-slate-700 overflow-hidden hover:shadow-2xl hover:border-purple-300 dark:hover:border-purple-700 transition-all duration-500 transform hover:-translate-y-2" border border-white/20 dark:border-white/10>
                <!-- Premium Header with Provider Badge -->
                <div class="relative bg-gradient-to-br
                    @if($setting->provider === 'openai') from-emerald-500 via-green-600 to-teal-700
                    @elseif($setting->provider === 'deepseek') from-blue-500 via-indigo-600 to-purple-700
                    @elseif($setting->provider === 'anthropic') from-orange-500 via-red-600 to-pink-700
                    @elseif($setting->provider === 'gemini') from-purple-500 via-fuchsia-600 to-pink-700
                    @else from-gray-500 via-gray-600 to-gray-700
                    @endif px-6 py-5 overflow-hidden">

                    <!-- Animated Background -->
                    <div class="absolute inset-0 glass-fusion backdrop-blur-sm" border border-white/20 dark:border-white/10></div>
                    <div class="absolute top-0 right-0 w-32 h-32 glass-fusion rounded-full -mr-16 -mt-16" border border-white/20 dark:border-white/10></div>

                    <div class="relative flex items-center justify-between">
                        <div class="flex items-center gap-3 flex-1">
                            <div class="w-12 h-12 rounded-xl glass-fusion backdrop-blur-md flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform border border-white/30" border border-white/20 dark:border-white/10>
                                @if($setting->provider === 'openai')
                                    <i class="fas fa-brain text-white text-xl"></i>
                                @elseif($setting->provider === 'deepseek')
                                    <i class="fas fa-search text-white text-xl"></i>
                                @elseif($setting->provider === 'anthropic')
                                    <i class="fas fa-robot text-white text-xl"></i>
                                @elseif($setting->provider === 'gemini')
                                    <i class="fas fa-gem text-white text-xl"></i>
                                @else
                                    <i class="fas fa-cog text-white text-xl"></i>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="text-lg font-bold text-white truncate">{{ $setting->name }}</h3>
                                <p class="text-xs text-white/90 font-semibold tracking-wide">{{ strtoupper($setting->provider) }}</p>
                            </div>
                        </div>
                        @if($setting->is_active)
                            <span class="px-3 py-1.5 glass-fusion backdrop-blur-md rounded-full text-xs font-bold text-white shadow-lg border border-white/40 flex items-center gap-1">
                                <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
                                Active
                            </span>
                        @else
                            <span class="px-3 py-1 glass-fusion backdrop-blur-sm rounded-full text-xs font-semibold text-white/70">
                                Inactive
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Content -->
                <div class="p-6 space-y-5">
                    <!-- Model Info with Premium Design -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-slate-700 dark:to-slate-600 rounded-xl border border-gray-200 dark:border-gray-700 dark:border-slate-600 group-hover:border-purple-200 dark:group-hover:border-purple-700 transition-colors">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center">
                                    <i class="fas fa-microchip text-purple-600 dark:text-purple-400 text-sm"></i>
                                </div>
                                <span class="text-xs font-medium text-gray-600 dark:text-gray-400 dark:text-gray-400">Model</span>
                            </div>
                            <span class="font-bold text-sm text-gray-900 dark:text-gray-100 px-3 py-1 glass-fusion dark:bg-slate-700 rounded-xl shadow-sm">{{ $setting->model }}</span>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="p-3 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/30 dark:to-indigo-900/30 rounded-xl border border-blue-100 dark:border-blue-800/50">
                                <div class="flex items-center gap-2 mb-1">
                                    <i class="fas fa-thermometer-half text-blue-600 dark:text-blue-400 text-xs"></i>
                                    <span class="text-[10px] font-medium text-blue-700 dark:text-blue-400 uppercase tracking-wide">Temp</span>
                                </div>
                                <p class="text-xl font-bold text-blue-900 dark:text-blue-300">{{ $setting->temperature }}</p>
                            </div>

                            <div class="p-3 bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/30 dark:to-emerald-900/30 rounded-xl border border-green-100 dark:border-green-800/50">
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
                        <div class="flex-1 p-3 bg-gradient-to-br from-purple-50 to-indigo-50 dark:from-purple-900/30 dark:to-indigo-900/30 rounded-xl border border-purple-100 dark:border-purple-800/50">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-book text-purple-600 dark:text-purple-400"></i>
                                    <span class="text-xs font-medium text-purple-700 dark:text-purple-400">Knowledge</span>
                                </div>
                                <span class="px-2.5 py-1 bg-purple-600 dark:bg-purple-700 text-white rounded-xl text-xs font-bold shadow-md">
                                    {{ $setting->knowledgeBases->count() }}
                                </span>
                            </div>
                        </div>

                        <div class="flex-1 p-3 bg-gradient-to-br from-orange-50 to-amber-50 dark:from-orange-900/30 dark:to-amber-900/30 rounded-xl border border-orange-100 dark:border-orange-800/50">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-history text-orange-600 dark:text-orange-400"></i>
                                    <span class="text-xs font-medium text-orange-700 dark:text-orange-400">Memory</span>
                                </div>
                                <span class="px-2.5 py-1 bg-orange-600 dark:bg-orange-700 text-white rounded-xl text-xs font-bold shadow-md">
                                    {{ $setting->conversation_memory_limit ?? 10 }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- API Key Status -->
                    <div class="p-3 bg-gradient-to-r from-gray-50 to-slate-50 dark:from-slate-700 dark:to-slate-600 rounded-xl border border-gray-200 dark:border-gray-700 dark:border-slate-600">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-key text-gray-500 dark:text-gray-400 dark:text-gray-400 text-sm"></i>
                            <span class="font-mono text-xs text-gray-700 dark:text-gray-300 dark:text-gray-300">{{ $setting->getMaskedApiKey() }}</span>
                        </div>
                    </div>

                    <!-- Premium Action Buttons -->
                    <div class="pt-2 space-y-2">
                        <div class="grid grid-cols-2 gap-2">
                            <a href="{{ route('admin.line-bot.ai.knowledge.index', $setting->id) }}"
                               class="px-4 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl hover:from-indigo-700 hover:to-purple-700 transition-all text-center text-sm font-bold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
                                <i class="fas fa-book"></i>
                                <span>Knowledge</span>
                            </a>
                            <button onclick="testAi({{ $setting->id }})"
                                    class="px-4 py-2.5 bg-gradient-to-r from-blue-600 to-cyan-600 text-white rounded-xl hover:from-blue-700 hover:to-cyan-700 transition-all text-center text-sm font-bold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
                                <i class="fas fa-vial"></i>
                                <span>Test</span>
                            </button>
                        </div>
                        <a href="{{ route('admin.line-bot.ai.edit', $setting->id) }}"
                           class="block w-full px-4 py-2.5 bg-gradient-to-r from-green-600 to-emerald-600 text-white rounded-xl hover:from-green-700 hover:to-emerald-700 transition-all text-center text-sm font-bold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
                            <i class="fas fa-edit"></i>
                            <span>แก้ไขการตั้งค่า</span>
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-3">
                <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-700 p-12 text-center" border border-white/20 dark:border-white/10>
                    <div class="w-24 h-24 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-robot text-purple-500 dark:text-purple-400 text-4xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">No AI Settings Yet</h3>
                    <p class="text-gray-600 dark:text-gray-400 dark:text-gray-400 mb-6">Get started by creating your first AI configuration</p>
                    <a href="{{ route('admin.line-bot.ai.create') }}"
                       class="inline-block px-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-xl hover:from-purple-700 hover:to-indigo-700 transition shadow-lg">
                        <i class="fas fa-plus mr-2"></i>Create First AI Setting
                    </a>
                </div>
            </div>
        @endforelse
    </div>
</div>

<!-- Test AI Modal -->
<div id="testModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-2xl max-w-2xl w-full overflow-hidden transform transition-all border border-gray-100 dark:border-slate-700" border border-white/20 dark:border-white/10 x-data="{ testing: false, result: '', error: '' }">
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
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                    <i class="fas fa-comment text-purple-500 mr-1"></i> Test Message
                </label>
                <textarea id="test-message" name="message" rows="3" required
                    class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-100 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all"
                    placeholder="Hello! Please introduce yourself.">Hello! Please introduce yourself.</textarea>
                <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 mt-1">The AI will respond to this message</p>
            </div>

            <!-- Result Area -->
            <div id="test-result-area" class="hidden">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-robot text-green-500 mr-1"></i> AI Response
                </label>
                <div class="p-4 bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-xl">
                    <p id="test-result" class="text-sm text-gray-900 dark:text-white whitespace-pre-wrap"></p>
                </div>
            </div>

            <!-- Error Area -->
            <div id="test-error-area" class="hidden">
                <div class="p-4 bg-gradient-to-r from-red-50 to-pink-50 border border-red-200 rounded-xl">
                    <div class="flex items-start gap-2">
                        <i class="fas fa-exclamation-circle text-red-500 mt-0.5"></i>
                        <p id="test-error" class="text-sm text-red-700"></p>
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
                        class="flex-1 px-4 py-3 bg-gray-200 dark:bg-gray-700 dark:bg-slate-700 text-gray-700 dark:text-gray-300 dark:text-gray-300 rounded-xl hover:bg-gray-300 dark:hover:bg-slate-600 transition font-semibold">
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
let currentTestId = null;

function testAi(aiId) {
    currentTestId = aiId;
    document.getElementById('test-ai-id').value = aiId;
    document.getElementById('test-result-area').classList.add('hidden');
    document.getElementById('test-error-area').classList.add('hidden');
    document.getElementById('testModal').classList.remove('hidden');
    document.getElementById('testModal').classList.add('flex');
}

function closeTestModal() {
    document.getElementById('testModal').classList.add('hidden');
    document.getElementById('testModal').classList.remove('flex');
}

function submitTest(event) {
    event.preventDefault();

    const aiId = document.getElementById('test-ai-id').value;
    const message = document.getElementById('test-message').value;

    // Show loading
    document.getElementById('test-loading').classList.remove('hidden');
    document.getElementById('test-submit-btn').disabled = true;
    document.getElementById('test-result-area').classList.add('hidden');
    document.getElementById('test-error-area').classList.add('hidden');

    // Make request
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

// Close modal on ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeTestModal();
    }
});
</script>
@endsection
