@extends('layouts.admin')

@section('title', 'Knowledge Base Management')

@section('content')
<div class="container-fluid px-4 py-6" x-data="{
    sourceType: 'text',
    showAddModal: false
}">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center gap-3 mb-2">
            <a href="{{ route('admin.line-bot.ai.index') }}" class="text-gray-600 hover:text-gray-900">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div class="flex-1">
                <h1 class="text-2xl font-bold text-gray-900">Knowledge Base Management</h1>
                <p class="text-sm text-gray-600">
                    For: <span class="font-semibold text-indigo-600">{{ $aiSetting->name }}</span>
                </p>
            </div>
            <button @click="showAddModal = true"
                    class="px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl hover:from-indigo-700 hover:to-purple-700 transition shadow-lg">
                <i class="fas fa-plus mr-2"></i>Add Knowledge Base
            </button>
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
    <div class="mb-6 p-6 bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-2xl">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-500 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-info-circle text-white text-xl"></i>
            </div>
            <div>
                <h3 class="font-bold text-blue-900 mb-2">What is Knowledge Base?</h3>
                <p class="text-sm text-blue-800">
                    Knowledge bases provide context and information to your AI. The AI will use this information to answer questions more accurately. You can add data from URLs, internal pages, uploaded files, or manual text input.
                </p>
            </div>
        </div>
    </div>

    <!-- Knowledge Base List -->
    <div class="grid grid-cols-1 gap-4">
        @forelse($knowledgeBases as $kb)
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden hover:shadow-xl transition">
                <div class="p-6">
                    <div class="flex items-start gap-4">
                        <!-- Icon -->
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0
                            @if($kb->type === 'url') bg-blue-100
                            @elseif($kb->type === 'internal') bg-green-100
                            @elseif($kb->type === 'file') bg-purple-100
                            @else bg-yellow-100
                            @endif">
                            @if($kb->type === 'url')
                                <i class="fas fa-link text-blue-600 text-xl"></i>
                            @elseif($kb->type === 'internal')
                                <i class="fas fa-home text-green-600 text-xl"></i>
                            @elseif($kb->type === 'file')
                                <i class="fas fa-file text-purple-600 text-xl"></i>
                            @else
                                <i class="fas fa-keyboard text-yellow-600 text-xl"></i>
                            @endif
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between mb-2">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="px-3 py-1 rounded-full text-xs font-bold
                                            @if($kb->type === 'url') bg-blue-100 text-blue-800
                                            @elseif($kb->type === 'internal') bg-green-100 text-green-800
                                            @elseif($kb->type === 'file') bg-purple-100 text-purple-800
                                            @else bg-yellow-100 text-yellow-800
                                            @endif">
                                            {{ strtoupper($kb->type) }}
                                        </span>
                                        <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-xs font-bold">
                                            Priority: {{ $kb->priority }}
                                        </span>
                                        @if($kb->is_enabled)
                                            <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-bold">
                                                <i class="fas fa-check-circle mr-1"></i>Enabled
                                            </span>
                                        @else
                                            <span class="px-3 py-1 bg-gray-100 text-gray-600 rounded-full text-xs font-bold">
                                                <i class="fas fa-pause-circle mr-1"></i>Disabled
                                            </span>
                                        @endif
                                    </div>

                                    @if($kb->type === 'url' || $kb->type === 'internal')
                                        <p class="text-sm text-gray-900 font-semibold mb-1">{{ $kb->source }}</p>
                                    @elseif($kb->type === 'file')
                                        <p class="text-sm text-gray-900 font-semibold mb-1">
                                            <i class="fas fa-file-alt mr-1"></i>{{ basename($kb->file_path) }}
                                        </p>
                                    @else
                                        <p class="text-sm text-gray-900 font-semibold mb-1">Manual Text Entry</p>
                                    @endif

                                    @if($kb->content)
                                        <p class="text-sm text-gray-600 line-clamp-2">{{ Str::limit($kb->content, 200) }}</p>
                                    @endif

                                    <div class="mt-2 flex items-center gap-4 text-xs text-gray-500">
                                        <span><i class="fas fa-clock mr-1"></i>{{ $kb->created_at->diffForHumans() }}</span>
                                        @if($kb->last_synced_at)
                                            <span><i class="fas fa-sync-alt mr-1"></i>Synced {{ $kb->last_synced_at->diffForHumans() }}</span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex items-center gap-2 ml-4">
                                    @if($kb->type === 'url' || $kb->type === 'internal')
                                        <form method="POST" action="{{ route('admin.line-bot.ai.knowledge.sync', [$aiSetting->id, $kb->id]) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="px-3 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition text-sm">
                                                <i class="fas fa-sync-alt"></i>
                                            </button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('admin.line-bot.ai.knowledge.destroy', [$aiSetting->id, $kb->id]) }}"
                                          onsubmit="return confirm('Are you sure you want to delete this knowledge base?')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-3 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition text-sm">
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
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-12 text-center">
                <div class="w-24 h-24 rounded-full bg-indigo-100 flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-book text-indigo-500 text-4xl"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-2">No Knowledge Bases Yet</h3>
                <p class="text-gray-600 mb-6">Add your first knowledge base to enhance AI responses</p>
                <button @click="showAddModal = true"
                        class="inline-block px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl hover:from-indigo-700 hover:to-purple-700 transition shadow-lg">
                    <i class="fas fa-plus mr-2"></i>Add Knowledge Base
                </button>
            </div>
        @endforelse
    </div>
</div>

<!-- Add Knowledge Base Modal -->
<div x-show="showAddModal"
     x-cloak
     @click.self="showAddModal = false"
     class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full overflow-hidden transform transition-all">
        <div class="bg-gradient-to-r from-indigo-500 to-purple-600 px-6 py-4">
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
                <label class="block text-sm font-semibold text-gray-700 mb-3">
                    <i class="fas fa-layer-group text-indigo-500 mr-1"></i> Source Type
                </label>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <button type="button" @click="sourceType = 'url'"
                            :class="sourceType === 'url' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-700'"
                            class="p-4 rounded-xl transition hover:shadow-lg">
                        <i class="fas fa-link text-2xl mb-2"></i>
                        <p class="text-sm font-semibold">URL</p>
                    </button>
                    <button type="button" @click="sourceType = 'internal'"
                            :class="sourceType === 'internal' ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-700'"
                            class="p-4 rounded-xl transition hover:shadow-lg">
                        <i class="fas fa-home text-2xl mb-2"></i>
                        <p class="text-sm font-semibold">Internal</p>
                    </button>
                    <button type="button" @click="sourceType = 'file'"
                            :class="sourceType === 'file' ? 'bg-purple-500 text-white' : 'bg-gray-100 text-gray-700'"
                            class="p-4 rounded-xl transition hover:shadow-lg">
                        <i class="fas fa-file text-2xl mb-2"></i>
                        <p class="text-sm font-semibold">File</p>
                    </button>
                    <button type="button" @click="sourceType = 'text'"
                            :class="sourceType === 'text' ? 'bg-yellow-500 text-white' : 'bg-gray-100 text-gray-700'"
                            class="p-4 rounded-xl transition hover:shadow-lg">
                        <i class="fas fa-keyboard text-2xl mb-2"></i>
                        <p class="text-sm font-semibold">Text</p>
                    </button>
                </div>
                <input type="hidden" name="type" x-model="sourceType">
            </div>

            <!-- URL Source -->
            <div x-show="sourceType === 'url'" x-cloak>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-link text-blue-500 mr-1"></i> URL
                </label>
                <input type="url" name="url"
                    class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                    placeholder="https://example.com/documentation">
                <p class="text-xs text-gray-500 mt-1">The content will be fetched and indexed automatically</p>
            </div>

            <!-- Internal Source -->
            <div x-show="sourceType === 'internal'" x-cloak>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-home text-green-500 mr-1"></i> Internal Path
                </label>
                <input type="text" name="internal"
                    class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all"
                    placeholder="/about-us, /faq, /products">
                <p class="text-xs text-gray-500 mt-1">Relative path to page on your website</p>
            </div>

            <!-- File Upload -->
            <div x-show="sourceType === 'file'" x-cloak>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-upload text-purple-500 mr-1"></i> Upload File
                </label>
                <input type="file" name="file" accept=".pdf,.txt,.docx,.csv"
                    class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all">
                <p class="text-xs text-gray-500 mt-1">Supported: PDF, TXT, DOCX, CSV (Max: 10MB)</p>
            </div>

            <!-- Text Input -->
            <div x-show="sourceType === 'text'" x-cloak>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-keyboard text-yellow-500 mr-1"></i> Text Content
                </label>
                <textarea name="text" rows="8"
                    class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 transition-all"
                    placeholder="Enter your knowledge base content here..."></textarea>
                <p class="text-xs text-gray-500 mt-1">Manually enter the information you want the AI to know</p>
            </div>

            <!-- Priority -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-sort-amount-up text-indigo-500 mr-1"></i> Priority (1-10)
                </label>
                <input type="number" name="priority" value="5" min="1" max="10"
                    class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                <p class="text-xs text-gray-500 mt-1">Higher priority sources are used first (1 = lowest, 10 = highest)</p>
            </div>

            <!-- Enable -->
            <div class="flex items-center gap-3">
                <input type="checkbox" name="is_enabled" value="1" checked id="kb-enabled"
                    class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                <label for="kb-enabled" class="text-sm font-semibold text-gray-700">
                    Enable this knowledge base immediately
                </label>
            </div>

            <!-- Submit -->
            <div class="flex gap-3 pt-4">
                <button type="button" @click="showAddModal = false"
                        class="flex-1 px-4 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-semibold">
                    Cancel
                </button>
                <button type="submit"
                        class="flex-1 px-4 py-3 bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-lg hover:from-indigo-600 hover:to-purple-700 transition shadow-lg font-semibold">
                    <i class="fas fa-plus mr-2"></i>Add Knowledge Base
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

<script>
// Close modal on ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        Alpine.store('showAddModal', false);
    }
});
</script>
@endsection
