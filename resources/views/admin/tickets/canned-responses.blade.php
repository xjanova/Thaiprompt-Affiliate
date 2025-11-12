@extends('layouts.admin')

@section('title', 'Canned Responses')

@section('content')
<div class="space-y-6">
    <!-- Header with Gradient -->
    <div class="bg-gradient-to-br from-green-600 via-emerald-600 to-teal-600 rounded-2xl shadow-2xl p-8 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-3xl font-bold mb-2 flex items-center">
                    <i class="fas fa-comment-dots mr-3"></i>
                    Canned Responses
                </h2>
                <p class="text-green-100 text-sm">จัดการข้อความสำเร็จรูปสำหรับการตอบกลับอย่างรวดเร็ว</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.tickets.index') }}"
                   class="inline-flex items-center px-6 py-3 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white font-semibold rounded-xl transition-all transform hover:scale-105 shadow-lg">
                    <i class="fas fa-arrow-left mr-2"></i>
                    กลับหน้าหลัก
                </a>
                <button onclick="document.getElementById('createModal').classList.remove('hidden')"
                        class="inline-flex items-center px-6 py-3 bg-white text-green-600 hover:bg-green-50 font-semibold rounded-xl transition-all transform hover:scale-105 shadow-lg">
                    <i class="fas fa-plus mr-2"></i>
                    Create Response
                </button>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6 border-l-4 border-green-500 hover:shadow-xl transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">Total Responses</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-white mt-1">{{ $responses->count() }}</p>
                </div>
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                    <i class="fas fa-comments text-2xl text-green-600 dark:text-green-400"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6 border-l-4 border-blue-500 hover:shadow-xl transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">Active</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-white mt-1">{{ $responses->where('is_active', true)->count() }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                    <i class="fas fa-check-circle text-2xl text-blue-600 dark:text-blue-400"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6 border-l-4 border-purple-500 hover:shadow-xl transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">Public</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-white mt-1">{{ $responses->where('is_public', true)->count() }}</p>
                </div>
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                    <i class="fas fa-globe text-2xl text-purple-600 dark:text-purple-400"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6 border-l-4 border-orange-500 hover:shadow-xl transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">Categories</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-white mt-1">{{ $responses->unique('category_id')->count() }}</p>
                </div>
                <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center">
                    <i class="fas fa-folder text-2xl text-orange-600 dark:text-orange-400"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Responses List -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg overflow-hidden">
        <div class="bg-gradient-to-r from-green-500 to-emerald-500 px-6 py-4">
            <h3 class="text-xl font-bold text-white flex items-center">
                <i class="fas fa-list mr-3"></i>
                Quick Reply Templates
            </h3>
        </div>

        <div class="p-6">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b-2 border-gray-200 dark:border-slate-700">
                            <th class="text-left py-4 px-4 text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Title</th>
                            <th class="text-left py-4 px-4 text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Shortcode</th>
                            <th class="text-left py-4 px-4 text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Category</th>
                            <th class="text-left py-4 px-4 text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Tags</th>
                            <th class="text-center py-4 px-4 text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Public</th>
                            <th class="text-center py-4 px-4 text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Status</th>
                            <th class="text-center py-4 px-4 text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                        @forelse($responses as $response)
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-colors">
                            <td class="py-4 px-4">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-gradient-to-br from-green-400 to-emerald-500 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fas fa-comment text-white"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-800 dark:text-white">{{ $response->title }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Created {{ $response->created_at->diffForHumans() }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-4">
                                <code class="px-3 py-1 bg-gray-100 dark:bg-slate-700 text-green-600 dark:text-green-400 rounded-lg font-mono text-sm">{{ $response->shortcode }}</code>
                            </td>
                            <td class="py-4 px-4">
                                <span class="px-3 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded-lg text-sm font-medium">
                                    {{ $response->category->name ?? 'All' }}
                                </span>
                            </td>
                            <td class="py-4 px-4">
                                <div class="flex flex-wrap gap-1">
                                    @if($response->tags)
                                        @foreach($response->tags as $tag)
                                            <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded text-xs font-medium">{{ $tag }}</span>
                                        @endforeach
                                    @else
                                        <span class="text-gray-400 text-sm">-</span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-4 px-4 text-center">
                                @if($response->is_public)
                                    <span class="inline-flex items-center px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-full text-xs font-bold">
                                        <i class="fas fa-globe mr-1"></i> Yes
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300 rounded-full text-xs font-bold">
                                        <i class="fas fa-lock mr-1"></i> No
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 px-4 text-center">
                                @if($response->is_active)
                                    <span class="inline-flex items-center px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-full text-xs font-bold">
                                        <span class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></span> Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-full text-xs font-bold">
                                        <span class="w-2 h-2 bg-gray-400 rounded-full mr-2"></span> Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 px-4">
                                <div class="flex items-center justify-center gap-2">
                                    <button onclick="viewResponse('{{ addslashes($response->content) }}')"
                                            class="p-2 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-lg hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors" title="View">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button onclick="editResponse({{ $response->id }})"
                                            class="p-2 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400 rounded-lg hover:bg-yellow-200 dark:hover:bg-yellow-900/50 transition-colors" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form action="{{ route('admin.tickets.canned-responses.destroy', $response->id) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" onclick="return confirm('Are you sure?')"
                                                class="p-2 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-20 h-20 bg-gray-100 dark:bg-slate-700 rounded-full flex items-center justify-center mb-4">
                                        <i class="fas fa-inbox text-4xl text-gray-400"></i>
                                    </div>
                                    <p class="text-gray-500 dark:text-gray-400 text-lg font-medium">No canned responses yet</p>
                                    <p class="text-gray-400 dark:text-gray-500 text-sm mt-1">Create your first quick reply template</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Create Modal -->
<div id="createModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
        <form action="{{ route('admin.tickets.canned-responses.store') }}" method="POST">
            @csrf
            <div class="bg-gradient-to-r from-green-500 to-emerald-500 px-6 py-4 flex items-center justify-between">
                <h3 class="text-xl font-bold text-white">Create Canned Response</h3>
                <button type="button" onclick="document.getElementById('createModal').classList.add('hidden')"
                        class="text-white hover:bg-white/20 rounded-lg p-2 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" required
                           class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Shortcode <span class="text-red-500">*</span></label>
                    <input type="text" name="shortcode" required placeholder="/greeting"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Use format: /shortcode (e.g., /greeting, /thanks)</p>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Content <span class="text-red-500">*</span></label>
                    <textarea name="content" rows="6" required
                              class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all"></textarea>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Available variables: {user_name}, {ticket_number}, {agent_name}</p>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Category (Optional)</label>
                    <select name="category_id"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="is_public" id="is_public" value="1" checked
                           class="w-5 h-5 text-green-600 border-gray-300 rounded focus:ring-green-500">
                    <label for="is_public" class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                        Public (visible to all agents)
                    </label>
                </div>
            </div>

            <div class="bg-gray-50 dark:bg-slate-700/50 px-6 py-4 flex items-center justify-end gap-3">
                <button type="button" onclick="document.getElementById('createModal').classList.add('hidden')"
                        class="px-6 py-3 bg-gray-200 dark:bg-slate-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-slate-500 font-semibold transition-colors">
                    Cancel
                </button>
                <button type="submit"
                        class="px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-500 text-white rounded-lg hover:from-green-600 hover:to-emerald-600 font-semibold transition-all transform hover:scale-105 shadow-lg">
                    <i class="fas fa-save mr-2"></i>Create Response
                </button>
            </div>
        </form>
    </div>
</div>

<!-- View Modal -->
<div id="viewModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-3xl w-full">
        <div class="bg-gradient-to-r from-blue-500 to-indigo-500 px-6 py-4 flex items-center justify-between">
            <h3 class="text-xl font-bold text-white">Response Content</h3>
            <button type="button" onclick="document.getElementById('viewModal').classList.add('hidden')"
                    class="text-white hover:bg-white/20 rounded-lg p-2 transition-colors">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-6">
            <pre id="responseContent" class="bg-gray-100 dark:bg-slate-700 text-gray-800 dark:text-gray-200 p-4 rounded-lg whitespace-pre-wrap font-mono text-sm"></pre>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function viewResponse(content) {
    document.getElementById('responseContent').textContent = content;
    document.getElementById('viewModal').classList.remove('hidden');
}

function editResponse(id) {
    // TODO: Implement edit functionality
    alert('Edit functionality coming soon!');
}

// Close modals on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.getElementById('createModal').classList.add('hidden');
        document.getElementById('viewModal').classList.add('hidden');
    }
});

// Close modals on outside click
document.querySelectorAll('[id$="Modal"]').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.add('hidden');
        }
    });
});
</script>
@endpush
