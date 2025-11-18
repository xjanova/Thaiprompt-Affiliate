@extends('layouts.admin-v3')

@section('title', 'Assignment Rules')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-7xl">
    <!-- Modern Gradient Header -->
    <div class="bg-gradient-to-br from-indigo-600 via-purple-600 to-violet-600 rounded-2xl shadow-2xl p-8 text-white mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-3xl font-bold mb-2 flex items-center">
                    <i class="fas fa-random mr-3"></i>
                    Assignment Rules
                </h2>
                <p class="text-indigo-100 text-lg">
                    กำหนดกฎการมอบหมาย Ticket อัตโนมัติ
                </p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.tickets.index') }}"
                   class="inline-flex items-center px-6 py-3 bg-white/20 hover:bg-white/30 backdrop-blur-sm rounded-xl font-semibold transition-all duration-200 hover:scale-105">
                    <i class="fas fa-arrow-left mr-2"></i>
                    กลับหน้าหลัก
                </a>
                <button onclick="openCreateModal()"
                        class="inline-flex items-center px-6 py-3 bg-white text-indigo-600 hover:bg-indigo-50 rounded-xl font-semibold transition-all duration-200 hover:scale-105 shadow-lg">
                    <i class="fas fa-plus mr-2"></i>
                    Create Rule
                </button>
            </div>
        </div>
    </div>

    <!-- Info Alert -->
    <div class="bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 border-l-4 border-blue-500 rounded-lg p-6 mb-8 shadow-md">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <i class="fas fa-info-circle text-2xl text-blue-500"></i>
            </div>
            <div class="ml-4">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-1">Rule Execution Order</h3>
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    Rules are executed based on priority (lower number = higher priority). The first matching rule will be applied to the ticket.
                </p>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <!-- Total Rules Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-indigo-500 hover:shadow-xl transition-all duration-200 hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm font-medium mb-1">Total Rules</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-white">{{ $rules->count() }}</p>
                </div>
                <div class="bg-indigo-100 dark:bg-indigo-900/30 p-4 rounded-lg">
                    <i class="fas fa-list-ol text-2xl text-indigo-600 dark:text-indigo-400"></i>
                </div>
            </div>
        </div>

        <!-- Active Rules Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-green-500 hover:shadow-xl transition-all duration-200 hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm font-medium mb-1">Active Rules</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-white">{{ $rules->where('is_active', true)->count() }}</p>
                </div>
                <div class="bg-green-100 dark:bg-green-900/30 p-4 rounded-lg">
                    <i class="fas fa-check-circle text-2xl text-green-600 dark:text-green-400"></i>
                </div>
            </div>
        </div>

        <!-- Inactive Rules Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-gray-500 hover:shadow-xl transition-all duration-200 hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm font-medium mb-1">Inactive Rules</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-white">{{ $rules->where('is_active', false)->count() }}</p>
                </div>
                <div class="bg-gray-100 dark:bg-gray-900/30 p-4 rounded-lg">
                    <i class="fas fa-pause-circle text-2xl text-gray-600 dark:text-gray-400"></i>
                </div>
            </div>
        </div>

        <!-- Categories Covered Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-purple-500 hover:shadow-xl transition-all duration-200 hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm font-medium mb-1">Categories Covered</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-white">{{ $rules->whereNotNull('category_id')->pluck('category_id')->unique()->count() }}</p>
                </div>
                <div class="bg-purple-100 dark:bg-purple-900/30 p-4 rounded-lg">
                    <i class="fas fa-folder text-2xl text-purple-600 dark:text-purple-400"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Rules Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-600">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">
                            Priority
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">
                            Rule Name
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">
                            Category Filter
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">
                            Priority Filter
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">
                            Assign To
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($rules as $rule)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-150">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 text-white font-bold shadow-lg">
                                    {{ $rule->priority }}
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $rule->name }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300">
                                <i class="fas fa-folder mr-1"></i>
                                {{ $rule->category->name ?? 'All Categories' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($rule->priority_filter)
                                @php
                                    $priorityColors = [
                                        'critical' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                                        'high' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
                                        'medium' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
                                        'low' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300'
                                    ];
                                    $colorClass = $priorityColors[$rule->priority_filter] ?? 'bg-gray-100 text-gray-800';
                                @endphp
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $colorClass }}">
                                    <i class="fas fa-flag mr-1"></i>
                                    {{ ucfirst($rule->priority_filter) }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                    All Priorities
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-8 w-8">
                                    <div class="h-8 w-8 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white font-semibold">
                                        {{ substr($rule->assignTo->name ?? 'N/A', 0, 1) }}
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $rule->assignTo->name ?? 'N/A' }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <form action="{{ route('admin.tickets.assignment-rules.toggle', $rule->id) }}"
                                  method="POST"
                                  class="inline-block">
                                @csrf
                                @if($rule->is_active)
                                    <button type="submit"
                                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300 hover:bg-green-200 dark:hover:bg-green-900/50 transition-all">
                                        <span class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></span>
                                        Active
                                    </button>
                                @else
                                    <button type="submit"
                                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition-all">
                                        <span class="w-2 h-2 bg-gray-400 rounded-full mr-2"></span>
                                        Inactive
                                    </button>
                                @endif
                            </form>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <div class="flex items-center space-x-2">
                                <button onclick="editRule({{ $rule->id }})"
                                        class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-blue-100 text-blue-600 hover:bg-blue-600 hover:text-white transition-all duration-200 dark:bg-blue-900/30 dark:text-blue-400 dark:hover:bg-blue-600">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form action="{{ route('admin.tickets.assignment-rules.destroy', $rule->id) }}"
                                      method="POST"
                                      onsubmit="return confirm('Are you sure you want to delete this rule?')"
                                      class="inline-block">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-red-100 text-red-600 hover:bg-red-600 hover:text-white transition-all duration-200 dark:bg-red-900/30 dark:text-red-400 dark:hover:bg-red-600">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <i class="fas fa-random text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
                                <p class="text-gray-500 dark:text-gray-400 text-lg font-medium">No assignment rules yet</p>
                                <p class="text-gray-400 dark:text-gray-500 text-sm mt-2">Create your first rule to automate ticket assignment</p>
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
<div id="ruleModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 py-8">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-3xl w-full transform transition-all">
            <form id="ruleForm" method="POST">
                @csrf
                <div id="methodField"></div>

                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-8 py-6 rounded-t-2xl">
                    <div class="flex items-center justify-between">
                        <h3 id="modalTitle" class="text-2xl font-bold text-white flex items-center">
                            <i class="fas fa-random mr-3"></i>
                            Create Assignment Rule
                        </h3>
                        <button type="button" onclick="closeModal()"
                                class="text-white/80 hover:text-white transition-colors">
                            <i class="fas fa-times text-2xl"></i>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="p-8 space-y-6">
                    <!-- Rule Name -->
                    <div>
                        <label for="name" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Rule Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" id="name" required
                               class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                               placeholder="e.g., High Priority Tickets to Senior Staff">
                    </div>

                    <!-- Priority, Category, Priority Filter -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label for="priority" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-sort-numeric-down mr-1"></i>
                                Execution Priority <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="priority" id="priority" required min="1" value="100"
                                   class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Lower = Higher priority</p>
                        </div>

                        <div>
                            <label for="category_id" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-folder mr-1"></i>
                                Category Filter
                            </label>
                            <select name="category_id" id="category_id"
                                    class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                                <option value="">All Categories</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="priority_filter" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-flag mr-1"></i>
                                Priority Filter
                            </label>
                            <select name="priority_filter" id="priority_filter"
                                    class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                                <option value="">All Priorities</option>
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                    </div>

                    <!-- Assign To -->
                    <div>
                        <label for="assign_to_user_id" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            <i class="fas fa-user mr-1"></i>
                            Assign To <span class="text-red-500">*</span>
                        </label>
                        <select name="assign_to_user_id" id="assign_to_user_id" required
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                            <option value="">Select User</option>
                            @foreach($staffUsers as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Rule Description Box -->
                    <div class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-lg p-4">
                        <div class="flex items-start">
                            <i class="fas fa-lightbulb text-indigo-500 mt-1 mr-3"></i>
                            <div class="text-sm text-gray-700 dark:text-gray-300">
                                <strong>How it works:</strong> When a ticket matches all selected filters (category and priority),
                                it will be automatically assigned to the selected user. Rules are processed in priority order.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-8 py-6 bg-gray-50 dark:bg-gray-900 rounded-b-2xl flex justify-end space-x-3">
                    <button type="button" onclick="closeModal()"
                            class="px-6 py-3 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-semibold hover:bg-gray-100 dark:hover:bg-gray-700 transition-all">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-6 py-3 rounded-lg bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold hover:from-indigo-700 hover:to-purple-700 transition-all shadow-lg">
                        <i class="fas fa-save mr-2"></i>
                        Save Rule
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openCreateModal() {
    document.getElementById('ruleModal').classList.remove('hidden');
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-random mr-3"></i>Create Assignment Rule';
    document.getElementById('ruleForm').action = '{{ route("admin.tickets.assignment-rules.store") }}';
    document.getElementById('methodField').innerHTML = '';
    document.getElementById('ruleForm').reset();
}

function editRule(id) {
    alert('Edit functionality will be implemented with AJAX');
    // TODO: Load rule data and populate form
}

function closeModal() {
    document.getElementById('ruleModal').classList.add('hidden');
    document.getElementById('ruleForm').reset();
}

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});

// Close modal on outside click
document.getElementById('ruleModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>
@endsection
