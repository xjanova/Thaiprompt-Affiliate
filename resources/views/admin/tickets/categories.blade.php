@extends('layouts.admin-v3')

@section('title', 'Ticket Categories')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-7xl">
    <!-- Modern Gradient Header -->
    <div class="bg-gradient-to-br from-orange-600 via-amber-600 to-yellow-600 rounded-2xl shadow-2xl p-8 text-white mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-3xl font-bold mb-2 flex items-center">
                    <i class="fas fa-folder-open mr-3"></i>
                    Ticket Categories
                </h2>
                <p class="text-orange-100 text-lg">
                    จัดการหมวดหมู่สำหรับ Ticket Support System
                </p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.tickets.index') }}"
                   class="inline-flex items-center px-6 py-3 glass-fusion hover:glass-fusion backdrop-blur-sm rounded-xl font-semibold transition-all duration-200 hover:scale-105">
                    <i class="fas fa-arrow-left mr-2"></i>
                    กลับหน้าหลัก
                </a>
                <button onclick="openCreateModal()"
                        class="inline-flex items-center px-6 py-3 glass-fusion text-orange-600 hover:bg-orange-50 rounded-xl font-semibold transition-all duration-200 hover:scale-105 shadow-lg">
                    <i class="fas fa-plus mr-2"></i>
                    Create Category
                </button>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <!-- Total Categories Card -->
        <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-orange-500 hover:shadow-xl transition-all duration-200 hover:-translate-y-1" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 dark:text-gray-400 text-sm font-medium mb-1">Total Categories</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white dark:text-white">{{ count($categories ?? []) }}</p>
                </div>
                <div class="bg-orange-100 dark:bg-orange-900/30 p-4 rounded-xl">
                    <i class="fas fa-folder text-2xl text-orange-600 dark:text-orange-400"></i>
                </div>
            </div>
        </div>

        <!-- Active Categories Card -->
        <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-green-500 hover:shadow-xl transition-all duration-200 hover:-translate-y-1" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 dark:text-gray-400 text-sm font-medium mb-1">Active Categories</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white dark:text-white">
                        {{ collect($categories ?? [])->where('is_active', true)->count() }}
                    </p>
                </div>
                <div class="bg-green-100 dark:bg-green-900/30 p-4 rounded-xl">
                    <i class="fas fa-check-circle text-2xl text-green-600 dark:text-green-400"></i>
                </div>
            </div>
        </div>

        <!-- Total Tickets Card -->
        <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-blue-500 hover:shadow-xl transition-all duration-200 hover:-translate-y-1" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 dark:text-gray-400 text-sm font-medium mb-1">Total Tickets</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white dark:text-white">
                        {{ collect($categories ?? [])->sum('tickets_count') }}
                    </p>
                </div>
                <div class="bg-blue-100 dark:bg-blue-900/30 p-4 rounded-xl">
                    <i class="fas fa-ticket-alt text-2xl text-blue-600 dark:text-blue-400"></i>
                </div>
            </div>
        </div>

        <!-- Most Used Card -->
        <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-purple-500 hover:shadow-xl transition-all duration-200 hover:-translate-y-1" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 dark:text-gray-400 text-sm font-medium mb-1">Most Used</p>
                    <p class="text-sm font-bold text-gray-900 dark:text-white dark:text-white truncate">
                        {{ collect($categories ?? [])->sortByDesc('tickets_count')->first()->name ?? 'N/A' }}
                    </p>
                </div>
                <div class="bg-purple-100 dark:bg-purple-900/30 p-4 rounded-xl">
                    <i class="fas fa-star text-2xl text-purple-600 dark:text-purple-400"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Categories Table -->
    <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden" border border-white/20 dark:border-white/10>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-600">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 dark:text-gray-200 uppercase tracking-wider">
                            ID
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 dark:text-gray-200 uppercase tracking-wider">
                            Category Name
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 dark:text-gray-200 uppercase tracking-wider">
                            Description
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 dark:text-gray-200 uppercase tracking-wider">
                            Icon
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 dark:text-gray-200 uppercase tracking-wider">
                            Ticket Count
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 dark:text-gray-200 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 dark:text-gray-200 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($categories ?? [] as $category)
                    <tr class="hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:hover:bg-gray-700/50 transition-colors duration-150">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gradient-to-br from-orange-400 to-amber-600 text-white font-bold text-sm">
                                    {{ $category->id }}
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                @if($category->color ?? null)
                                    <div class="w-3 h-3 rounded-full mr-3" style="background-color: {{ $category->color }}"></div>
                                @endif
                                <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ $category->name ?? 'N/A' }}
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">
                                {{ Str::limit($category->description ?? 'No description', 50) }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($category->icon ?? null)
                                <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-orange-100 dark:bg-orange-900/30">
                                    <i class="{{ $category->icon }} text-orange-600 dark:text-orange-400"></i>
                                </div>
                            @else
                                <span class="text-gray-400 text-sm">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                    <i class="fas fa-ticket-alt mr-1"></i>
                                    {{ $category->tickets_count ?? 0 }}
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if(($category->is_active ?? true))
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                    <span class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></span>
                                    Active
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100/50 dark:bg-gray-800/50 text-gray-900 dark:text-white dark:bg-gray-700 dark:text-gray-300">
                                    <span class="w-2 h-2 bg-gray-400 rounded-full mr-2"></span>
                                    Inactive
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <div class="flex items-center space-x-2">
                                <button onclick="editCategory({{ $category->id }})"
                                        class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-blue-100 text-blue-600 hover:bg-blue-600 hover:text-white transition-all duration-200 dark:bg-blue-900/30 dark:text-blue-400 dark:hover:bg-blue-600">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <i class="fas fa-folder-open text-6xl text-gray-300 dark:text-gray-600 dark:text-gray-400 mb-4"></i>
                                <p class="text-gray-500 dark:text-gray-400 dark:text-gray-400 text-lg font-medium">No categories found</p>
                                <p class="text-gray-400 dark:text-gray-500 dark:text-gray-400 text-sm mt-2">Create your first category to organize tickets</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create/Edit Modal -->
<div id="categoryModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 py-8">
        <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-2xl max-w-2xl w-full transform transition-all" border border-white/20 dark:border-white/10>
            <form id="categoryForm" method="POST">
                @csrf
                <div id="methodField"></div>

                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-orange-600 to-amber-600 px-8 py-6 rounded-t-2xl">
                    <div class="flex items-center justify-between">
                        <h3 id="modalTitle" class="text-2xl font-bold text-white flex items-center">
                            <i class="fas fa-folder-open mr-3"></i>
                            Create Category
                        </h3>
                        <button type="button" onclick="closeModal()"
                                class="text-white/80 hover:text-white transition-colors">
                            <i class="fas fa-times text-2xl"></i>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="p-8 space-y-6">
                    <!-- Category Name -->
                    <div>
                        <label for="name" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                            Category Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" id="name" required
                               class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all"
                               placeholder="e.g., Technical Support">
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                            Description
                        </label>
                        <textarea name="description" id="description" rows="3"
                                  class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all"
                                  placeholder="Brief description of this category"></textarea>
                    </div>

                    <!-- Icon and Color -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="icon" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                                <i class="fas fa-icons mr-1"></i>
                                Icon Class
                            </label>
                            <input type="text" name="icon" id="icon"
                                   class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all"
                                   placeholder="fas fa-wrench">
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">FontAwesome icon class</p>
                        </div>

                        <div>
                            <label for="color" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                                <i class="fas fa-palette mr-1"></i>
                                Color
                            </label>
                            <input type="color" name="color" id="color"
                                   class="w-full h-12 rounded-xl border border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-gray-700 focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all"
                                   value="#f59e0b">
                        </div>
                    </div>

                    <!-- Sort Order -->
                    <div>
                        <label for="sort_order" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                            <i class="fas fa-sort mr-1"></i>
                            Sort Order
                        </label>
                        <input type="number" name="sort_order" id="sort_order" min="0" value="0"
                               class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all"
                               placeholder="0">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">Lower number appears first</p>
                    </div>

                    <!-- Active Status -->
                    <div class="flex items-center p-4 bg-orange-50 dark:bg-orange-900/20 rounded-xl border border-orange-200 dark:border-orange-800">
                        <input type="checkbox" name="is_active" id="is_active" value="1" checked
                               class="w-5 h-5 text-orange-600 bg-gray-100/50 dark:bg-gray-800/50 border-gray-300 dark:border-gray-600 rounded focus:ring-orange-500 dark:focus:ring-orange-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                        <label for="is_active" class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300">
                            <i class="fas fa-check-circle mr-2 text-orange-600"></i>
                            Active category (visible to users)
                        </label>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-8 py-6 bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-900 rounded-b-2xl flex justify-end space-x-3">
                    <button type="button" onclick="closeModal()"
                            class="px-6 py-3 rounded-xl border border-gray-300 dark:border-gray-600 dark:border-gray-600 text-gray-700 dark:text-gray-300 dark:text-gray-300 font-semibold hover:bg-gray-100/50 dark:bg-gray-800/50 dark:hover:bg-gray-700 transition-all">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-6 py-3 rounded-xl bg-gradient-to-r from-orange-600 to-amber-600 text-white font-semibold hover:from-orange-700 hover:to-amber-700 transition-all shadow-lg">
                        <i class="fas fa-save mr-2"></i>
                        Save Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openCreateModal() {
    document.getElementById('categoryModal').classList.remove('hidden');
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-folder-open mr-3"></i>Create Category';
    document.getElementById('categoryForm').action = '{{ route("admin.tickets.categories.store") }}';
    document.getElementById('methodField').innerHTML = '';
    document.getElementById('categoryForm').reset();
}

function editCategory(id) {
    alert('Edit functionality will be implemented with AJAX');
    // TODO: Load category data and populate form
}

function closeModal() {
    document.getElementById('categoryModal').classList.add('hidden');
    document.getElementById('categoryForm').reset();
}

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});

// Close modal on outside click
document.getElementById('categoryModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>
@endsection
