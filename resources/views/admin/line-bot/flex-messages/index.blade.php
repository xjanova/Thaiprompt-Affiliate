@extends('layouts.admin')

@section('title', 'Flex Message Templates')

@section('content')
<div class="container-fluid px-4 py-6">
    <!-- Animated Header -->
    <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-pink-500 via-rose-600 to-red-700 p-8 shadow-2xl">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS1vcGFjaXR5PSIwLjEiIHN0cm9rZS13aWR0aD0iMSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNncmlkKSIvPjwvc3ZnPg==')] opacity-30"></div>
        <div class="relative flex items-center justify-between">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-14 h-14 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                        <i class="fas fa-layer-group text-white text-3xl"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-white mb-1">Flex Message Templates</h1>
                        <p class="text-pink-100">Create beautiful, interactive LINE messages</p>
                    </div>
                </div>
            </div>
            <div>
                <a href="{{ route('admin.line-bot.flex.create') }}"
                   class="px-6 py-3 bg-white/20 backdrop-blur-md border border-white/30 text-white rounded-xl hover:bg-white/30 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                    <i class="fas fa-plus mr-2"></i>New Template
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

    <!-- Category Filter -->
    <div class="mb-6 flex gap-2 flex-wrap">
        <a href="{{ route('admin.line-bot.flex.index') }}"
           class="px-4 py-2 rounded-lg {{ !request('category') ? 'bg-pink-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }} transition">
            All Templates
        </a>
        <a href="{{ route('admin.line-bot.flex.index', ['category' => 'welcome']) }}"
           class="px-4 py-2 rounded-lg {{ request('category') === 'welcome' ? 'bg-pink-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }} transition">
            Welcome
        </a>
        <a href="{{ route('admin.line-bot.flex.index', ['category' => 'promotion']) }}"
           class="px-4 py-2 rounded-lg {{ request('category') === 'promotion' ? 'bg-pink-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }} transition">
            Promotion
        </a>
        <a href="{{ route('admin.line-bot.flex.index', ['category' => 'product']) }}"
           class="px-4 py-2 rounded-lg {{ request('category') === 'product' ? 'bg-pink-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }} transition">
            Product
        </a>
        <a href="{{ route('admin.line-bot.flex.index', ['category' => 'notification']) }}"
           class="px-4 py-2 rounded-lg {{ request('category') === 'notification' ? 'bg-pink-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }} transition">
            Notification
        </a>
    </div>

    <!-- Templates Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($templates as $template)
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                <!-- Header -->
                <div class="bg-gradient-to-r from-pink-500 to-rose-600 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold text-white">{{ $template->name }}</h3>
                        @if($template->is_seed)
                            <span class="px-3 py-1 bg-yellow-400 text-yellow-900 rounded-full text-xs font-bold">
                                <i class="fas fa-star mr-1"></i>Seed
                            </span>
                        @endif
                    </div>
                    <p class="text-xs text-pink-100 mt-1">{{ ucfirst($template->category) }}</p>
                </div>

                <!-- Content -->
                <div class="p-6">
                    @if($template->description)
                        <p class="text-sm text-gray-600 mb-4">{{ Str::limit($template->description, 100) }}</p>
                    @endif

                    <!-- Stats -->
                    <div class="flex items-center justify-between mb-4 pb-4 border-b border-gray-100">
                        <div class="text-center">
                            <p class="text-xs text-gray-500">Used</p>
                            <p class="text-lg font-bold text-gray-900">{{ number_format($template->usage_count) }}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-xs text-gray-500">Created</p>
                            <p class="text-sm font-semibold text-gray-900">{{ $template->created_at->format('M d') }}</p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-2">
                        <button onclick="previewTemplate({{ $template->id }})"
                                class="flex-1 px-4 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition text-sm font-semibold">
                            <i class="fas fa-eye mr-1"></i>Preview
                        </button>
                        <button onclick="testSend({{ $template->id }})"
                                class="flex-1 px-4 py-2 bg-green-100 text-green-700 rounded-lg hover:bg-green-200 transition text-sm font-semibold">
                            <i class="fas fa-paper-plane mr-1"></i>Send
                        </button>
                        <a href="{{ route('admin.line-bot.flex.edit', $template->id) }}"
                           class="flex-1 px-4 py-2 bg-purple-100 text-purple-700 rounded-lg hover:bg-purple-200 transition text-sm font-semibold text-center">
                            <i class="fas fa-edit mr-1"></i>Edit
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-3">
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-12 text-center">
                    <div class="w-24 h-24 rounded-full bg-pink-100 flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-layer-group text-pink-500 text-4xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">No Templates Yet</h3>
                    <p class="text-gray-600 mb-6">Create your first Flex Message template</p>
                    <a href="{{ route('admin.line-bot.flex.create') }}"
                       class="inline-block px-6 py-3 bg-gradient-to-r from-pink-600 to-rose-600 text-white rounded-xl hover:from-pink-700 hover:to-rose-700 transition shadow-lg">
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
<div id="previewModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden transform transition-all">
        <div class="bg-gradient-to-r from-pink-500 to-rose-600 px-6 py-4">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-white flex items-center">
                    <i class="fas fa-eye mr-2"></i>Template Preview
                </h3>
                <button onclick="closePreview()" class="text-white/80 hover:text-white transition">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>

        <div id="preview-content" class="p-6 bg-gray-50" style="max-height: 70vh; overflow-y: auto;">
            <div class="text-center py-8">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-pink-600"></div>
                <p class="mt-4 text-gray-600">Loading preview...</p>
            </div>
        </div>
    </div>
</div>

<!-- Test Send Modal -->
<div id="testModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden transform transition-all">
        <div class="bg-gradient-to-r from-green-500 to-emerald-600 px-6 py-4">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-white flex items-center">
                    <i class="fas fa-paper-plane mr-2"></i>Test Send Template
                </h3>
                <button onclick="closeTest()" class="text-white/80 hover:text-white transition">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>

        <form onsubmit="return submitTest(event)" class="p-6 space-y-4">
            <input type="hidden" id="test-template-id">

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-user text-green-500 mr-1"></i> LINE User ID
                </label>
                <input type="text" id="test-user-id" required
                    class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all"
                    placeholder="U1234567890abcdef1234567890abcdef">
                <p class="text-xs text-gray-500 mt-1">The recipient's LINE User ID</p>
            </div>

            <div id="test-result" class="hidden"></div>

            <div class="flex gap-3 pt-4">
                <button type="button" onclick="closeTest()"
                        class="flex-1 px-4 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-semibold">
                    Cancel
                </button>
                <button type="submit" id="test-submit-btn"
                        class="flex-1 px-4 py-3 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-lg hover:from-green-600 hover:to-emerald-700 transition shadow-lg font-semibold">
                    <i class="fas fa-paper-plane mr-2"></i>Send Test
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
function previewTemplate(templateId) {
    document.getElementById('previewModal').classList.remove('hidden');
    document.getElementById('previewModal').classList.add('flex');

    fetch(`/admin/line-bot/flex/${templateId}/preview`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('preview-content').innerHTML = data.html || '<p class="text-center text-gray-500">Preview not available</p>';
        } else {
            document.getElementById('preview-content').innerHTML = '<p class="text-center text-red-500">Failed to load preview</p>';
        }
    })
    .catch(error => {
        document.getElementById('preview-content').innerHTML = '<p class="text-center text-red-500">Error: ' + error.message + '</p>';
    });
}

function closePreview() {
    document.getElementById('previewModal').classList.add('hidden');
    document.getElementById('previewModal').classList.remove('flex');
}

function testSend(templateId) {
    document.getElementById('test-template-id').value = templateId;
    document.getElementById('test-result').classList.add('hidden');
    document.getElementById('testModal').classList.remove('hidden');
    document.getElementById('testModal').classList.add('flex');
}

function closeTest() {
    document.getElementById('testModal').classList.add('hidden');
    document.getElementById('testModal').classList.remove('flex');
}

function submitTest(event) {
    event.preventDefault();

    const templateId = document.getElementById('test-template-id').value;
    const userId = document.getElementById('test-user-id').value;
    const resultDiv = document.getElementById('test-result');

    document.getElementById('test-submit-btn').disabled = true;

    fetch(`/admin/line-bot/flex/${templateId}/test-send`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ line_user_id: userId })
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('test-submit-btn').disabled = false;

        if (data.success) {
            resultDiv.className = 'p-4 bg-green-50 border border-green-200 rounded-lg';
            resultDiv.innerHTML = '<p class="text-sm text-green-800"><i class="fas fa-check-circle mr-2"></i>' + (data.message || 'Message sent successfully!') + '</p>';
        } else {
            resultDiv.className = 'p-4 bg-red-50 border border-red-200 rounded-lg';
            resultDiv.innerHTML = '<p class="text-sm text-red-800"><i class="fas fa-exclamation-circle mr-2"></i>' + (data.message || 'Failed to send message') + '</p>';
        }
        resultDiv.classList.remove('hidden');
    })
    .catch(error => {
        document.getElementById('test-submit-btn').disabled = false;
        resultDiv.className = 'p-4 bg-red-50 border border-red-200 rounded-lg';
        resultDiv.innerHTML = '<p class="text-sm text-red-800"><i class="fas fa-exclamation-circle mr-2"></i>Error: ' + error.message + '</p>';
        resultDiv.classList.remove('hidden');
    });

    return false;
}

// Close modals on ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closePreview();
        closeTest();
    }
});
</script>
@endsection
