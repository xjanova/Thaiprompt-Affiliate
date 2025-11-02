@extends('layouts.admin')

@section('title', 'AI Chat Bot Settings')

@section('content')
<div class="container-fluid px-4 py-6">
    <!-- Animated Header -->
    <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-purple-500 via-indigo-600 to-blue-700 p-8 shadow-2xl">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS1vcGFjaXR5PSIwLjEiIHN0cm9rZS13aWR0aD0iMSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNncmlkKSIvPjwvc3ZnPg==')] opacity-30"></div>
        <div class="relative flex items-center justify-between">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-14 h-14 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-white mb-1">AI Chat Bot Settings</h1>
                        <p class="text-purple-100">Configure AI providers for intelligent conversations</p>
                    </div>
                </div>
            </div>
            <div>
                <a href="{{ route('admin.line-bot.ai.create') }}"
                   class="px-6 py-3 bg-white/20 backdrop-blur-md border border-white/30 text-white rounded-xl hover:bg-white/30 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                    <i class="fas fa-plus mr-2"></i>New AI Setting
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

    <!-- AI Settings Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($aiSettings as $setting)
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                <!-- Header with Provider Badge -->
                <div class="bg-gradient-to-r
                    @if($setting->provider === 'openai') from-green-500 to-emerald-600
                    @elseif($setting->provider === 'deepseek') from-blue-500 to-cyan-600
                    @elseif($setting->provider === 'anthropic') from-orange-500 to-red-600
                    @elseif($setting->provider === 'gemini') from-purple-500 to-pink-600
                    @else from-gray-500 to-gray-600
                    @endif px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-white/20 backdrop-blur-sm flex items-center justify-center">
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
                            <div>
                                <h3 class="text-lg font-bold text-white">{{ $setting->name }}</h3>
                                <p class="text-xs text-white/80">{{ strtoupper($setting->provider) }}</p>
                            </div>
                        </div>
                        @if($setting->is_active)
                            <span class="px-3 py-1 bg-white/30 backdrop-blur-sm rounded-full text-xs font-bold text-white">
                                <i class="fas fa-check-circle mr-1"></i>Active
                            </span>
                        @else
                            <span class="px-3 py-1 bg-white/10 backdrop-blur-sm rounded-full text-xs font-bold text-white/70">
                                Inactive
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Content -->
                <div class="p-6">
                    <!-- Model Info -->
                    <div class="mb-4 pb-4 border-b border-gray-100">
                        <div class="flex items-center justify-between text-sm mb-2">
                            <span class="text-gray-600">Model:</span>
                            <span class="font-semibold text-gray-900">{{ $setting->model }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm mb-2">
                            <span class="text-gray-600">Temperature:</span>
                            <span class="font-semibold text-gray-900">{{ $setting->temperature }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600">Max Tokens:</span>
                            <span class="font-semibold text-gray-900">{{ number_format($setting->max_tokens) }}</span>
                        </div>
                    </div>

                    <!-- Knowledge Base Status -->
                    <div class="mb-4">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600">
                                <i class="fas fa-book text-indigo-500 mr-1"></i>Knowledge Bases:
                            </span>
                            <span class="px-2 py-1 bg-indigo-100 text-indigo-800 rounded-full text-xs font-bold">
                                {{ $setting->knowledgeBases->count() }}
                            </span>
                        </div>
                    </div>

                    <!-- API Key Status -->
                    <div class="mb-4 p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center gap-2 text-xs text-gray-600">
                            <i class="fas fa-key text-gray-400"></i>
                            <span class="font-mono">{{ $setting->getMaskedApiKey() }}</span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-2">
                        <a href="{{ route('admin.line-bot.ai.knowledge.index', $setting->id) }}"
                           class="flex-1 px-4 py-2 bg-indigo-100 text-indigo-700 rounded-lg hover:bg-indigo-200 transition text-center text-sm font-semibold">
                            <i class="fas fa-book mr-1"></i>Knowledge
                        </a>
                        <button onclick="testAi({{ $setting->id }})"
                                class="flex-1 px-4 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition text-center text-sm font-semibold">
                            <i class="fas fa-vial mr-1"></i>Test
                        </button>
                        <a href="{{ route('admin.line-bot.ai.edit', $setting->id) }}"
                           class="flex-1 px-4 py-2 bg-green-100 text-green-700 rounded-lg hover:bg-green-200 transition text-center text-sm font-semibold">
                            <i class="fas fa-edit mr-1"></i>Edit
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-3">
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-12 text-center">
                    <div class="w-24 h-24 rounded-full bg-purple-100 flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-robot text-purple-500 text-4xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">No AI Settings Yet</h3>
                    <p class="text-gray-600 mb-6">Get started by creating your first AI configuration</p>
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
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full overflow-hidden transform transition-all" x-data="{ testing: false, result: '', error: '' }">
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
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-comment text-purple-500 mr-1"></i> Test Message
                </label>
                <textarea id="test-message" name="message" rows="3" required
                    class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all"
                    placeholder="Hello! Please introduce yourself.">Hello! Please introduce yourself.</textarea>
                <p class="text-xs text-gray-500 mt-1">The AI will respond to this message</p>
            </div>

            <!-- Result Area -->
            <div id="test-result-area" class="hidden">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-robot text-green-500 mr-1"></i> AI Response
                </label>
                <div class="p-4 bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-lg">
                    <p id="test-result" class="text-sm text-gray-800 whitespace-pre-wrap"></p>
                </div>
            </div>

            <!-- Error Area -->
            <div id="test-error-area" class="hidden">
                <div class="p-4 bg-gradient-to-r from-red-50 to-pink-50 border border-red-200 rounded-lg">
                    <div class="flex items-start gap-2">
                        <i class="fas fa-exclamation-circle text-red-500 mt-0.5"></i>
                        <p id="test-error" class="text-sm text-red-700"></p>
                    </div>
                </div>
            </div>

            <!-- Loading State -->
            <div id="test-loading" class="hidden text-center py-4">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600 mb-2"></div>
                <p class="text-sm text-gray-600">Testing AI connection...</p>
            </div>

            <div class="flex gap-3 pt-4">
                <button type="button" onclick="closeTestModal()"
                        class="flex-1 px-4 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-semibold">
                    Close
                </button>
                <button type="submit" id="test-submit-btn"
                        class="flex-1 px-4 py-3 bg-gradient-to-r from-purple-500 to-indigo-600 text-white rounded-lg hover:from-purple-600 hover:to-indigo-700 transition shadow-lg font-semibold">
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
