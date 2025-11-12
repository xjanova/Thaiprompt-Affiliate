@extends('layouts.admin')

@section('title', 'SLA Policies')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-7xl">
    <!-- Modern Gradient Header -->
    <div class="bg-gradient-to-br from-red-600 via-rose-600 to-pink-600 rounded-2xl shadow-2xl p-8 text-white mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-3xl font-bold mb-2 flex items-center">
                    <i class="fas fa-clock mr-3"></i>
                    SLA Policies
                </h2>
                <p class="text-red-100 text-lg">
                    กำหนดและจัดการนโยบาย Service Level Agreement
                </p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.tickets.index') }}"
                   class="inline-flex items-center px-6 py-3 bg-white/20 hover:bg-white/30 backdrop-blur-sm rounded-xl font-semibold transition-all duration-200 hover:scale-105">
                    <i class="fas fa-arrow-left mr-2"></i>
                    กลับหน้าหลัก
                </a>
                <button onclick="openCreateModal()"
                        class="inline-flex items-center px-6 py-3 bg-white text-red-600 hover:bg-red-50 rounded-xl font-semibold transition-all duration-200 hover:scale-105 shadow-lg">
                    <i class="fas fa-plus mr-2"></i>
                    Create Policy
                </button>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <!-- Total Policies Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-red-500 hover:shadow-xl transition-all duration-200 hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm font-medium mb-1">Total Policies</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-white">{{ $policies->count() }}</p>
                </div>
                <div class="bg-red-100 dark:bg-red-900/30 p-4 rounded-lg">
                    <i class="fas fa-file-contract text-2xl text-red-600 dark:text-red-400"></i>
                </div>
            </div>
        </div>

        <!-- Active Policies Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-green-500 hover:shadow-xl transition-all duration-200 hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm font-medium mb-1">Active Policies</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-white">{{ $policies->where('is_active', true)->count() }}</p>
                </div>
                <div class="bg-green-100 dark:bg-green-900/30 p-4 rounded-lg">
                    <i class="fas fa-check-circle text-2xl text-green-600 dark:text-green-400"></i>
                </div>
            </div>
        </div>

        <!-- Business Hours Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-yellow-500 hover:shadow-xl transition-all duration-200 hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm font-medium mb-1">Business Hours Only</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-white">{{ $policies->where('business_hours_only', true)->count() }}</p>
                </div>
                <div class="bg-yellow-100 dark:bg-yellow-900/30 p-4 rounded-lg">
                    <i class="fas fa-business-time text-2xl text-yellow-600 dark:text-yellow-400"></i>
                </div>
            </div>
        </div>

        <!-- 24/7 Policies Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-blue-500 hover:shadow-xl transition-all duration-200 hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm font-medium mb-1">24/7 Support</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-white">{{ $policies->where('business_hours_only', false)->count() }}</p>
                </div>
                <div class="bg-blue-100 dark:bg-blue-900/30 p-4 rounded-lg">
                    <i class="fas fa-clock text-2xl text-blue-600 dark:text-blue-400"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Policies Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-600">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">
                            Policy Name
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">
                            Category
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">
                            Priority
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">
                            First Response
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">
                            Resolution Time
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">
                            Business Hours
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
                    @forelse($policies as $policy)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-150">
                        <td class="px-6 py-4">
                            <div class="flex items-start">
                                <div>
                                    <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $policy->name }}
                                    </div>
                                    @if($policy->description)
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            {{ Str::limit($policy->description, 50) }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300">
                                <i class="fas fa-folder mr-1"></i>
                                {{ $policy->category->name ?? 'All Categories' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($policy->priority)
                                @php
                                    $priorityColors = [
                                        'critical' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                                        'high' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
                                        'medium' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
                                        'low' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300'
                                    ];
                                    $colorClass = $priorityColors[$policy->priority] ?? 'bg-gray-100 text-gray-800';
                                @endphp
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $colorClass }}">
                                    <i class="fas fa-flag mr-1"></i>
                                    {{ ucfirst($policy->priority) }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                    All Priorities
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center text-sm">
                                <i class="fas fa-hourglass-start text-blue-500 mr-2"></i>
                                <span class="font-semibold text-gray-900 dark:text-white">{{ $policy->first_response_time }}</span>
                                <span class="text-gray-500 dark:text-gray-400 ml-1">min</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center text-sm">
                                <i class="fas fa-hourglass-end text-green-500 mr-2"></i>
                                <span class="font-semibold text-gray-900 dark:text-white">{{ $policy->resolution_time }}</span>
                                <span class="text-gray-500 dark:text-gray-400 ml-1">min</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($policy->business_hours_only)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">
                                    <i class="fas fa-business-time mr-1"></i>
                                    Business Only
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                    <i class="fas fa-clock mr-1"></i>
                                    24/7
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($policy->is_active)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                    <span class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></span>
                                    Active
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                    <span class="w-2 h-2 bg-gray-400 rounded-full mr-2"></span>
                                    Inactive
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <div class="flex items-center space-x-2">
                                <button onclick="editPolicy({{ $policy->id }})"
                                        class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-blue-100 text-blue-600 hover:bg-blue-600 hover:text-white transition-all duration-200 dark:bg-blue-900/30 dark:text-blue-400 dark:hover:bg-blue-600">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form action="{{ route('admin.tickets.sla-policies.destroy', $policy->id) }}"
                                      method="POST"
                                      onsubmit="return confirm('Are you sure you want to delete this policy?')"
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
                        <td colspan="8" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <i class="fas fa-clock text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
                                <p class="text-gray-500 dark:text-gray-400 text-lg font-medium">No SLA policies yet</p>
                                <p class="text-gray-400 dark:text-gray-500 text-sm mt-2">Create your first policy to get started</p>
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
<div id="policyModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 py-8">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-3xl w-full transform transition-all">
            <form id="policyForm" method="POST">
                @csrf
                <div id="methodField"></div>

                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-red-600 to-rose-600 px-8 py-6 rounded-t-2xl">
                    <div class="flex items-center justify-between">
                        <h3 id="modalTitle" class="text-2xl font-bold text-white flex items-center">
                            <i class="fas fa-clock mr-3"></i>
                            Create SLA Policy
                        </h3>
                        <button type="button" onclick="closeModal()"
                                class="text-white/80 hover:text-white transition-colors">
                            <i class="fas fa-times text-2xl"></i>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="p-8 space-y-6">
                    <!-- Policy Name -->
                    <div>
                        <label for="name" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Policy Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" id="name" required
                               class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all">
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Description
                        </label>
                        <textarea name="description" id="description" rows="2"
                                  class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all"></textarea>
                    </div>

                    <!-- Category and Priority -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="category_id" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-folder mr-1"></i>
                                Category
                            </label>
                            <select name="category_id" id="category_id"
                                    class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all">
                                <option value="">All Categories</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="priority" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-flag mr-1"></i>
                                Priority
                            </label>
                            <select name="priority" id="priority"
                                    class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all">
                                <option value="">All Priorities</option>
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                    </div>

                    <!-- Response and Resolution Times -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="first_response_time" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-hourglass-start mr-1"></i>
                                First Response Time (minutes) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="first_response_time" id="first_response_time" required min="1"
                                   class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all"
                                   placeholder="e.g., 15">
                        </div>

                        <div>
                            <label for="resolution_time" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-hourglass-end mr-1"></i>
                                Resolution Time (minutes) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="resolution_time" id="resolution_time" required min="1"
                                   class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all"
                                   placeholder="e.g., 240">
                        </div>
                    </div>

                    <!-- Business Hours Only Checkbox -->
                    <div class="flex items-center p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800">
                        <input type="checkbox" name="business_hours_only" id="business_hours_only" value="1"
                               class="w-5 h-5 text-red-600 bg-gray-100 border-gray-300 rounded focus:ring-red-500 dark:focus:ring-red-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                        <label for="business_hours_only" class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                            <i class="fas fa-business-time mr-2 text-yellow-600"></i>
                            Apply only during business hours
                        </label>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-8 py-6 bg-gray-50 dark:bg-gray-900 rounded-b-2xl flex justify-end space-x-3">
                    <button type="button" onclick="closeModal()"
                            class="px-6 py-3 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-semibold hover:bg-gray-100 dark:hover:bg-gray-700 transition-all">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-6 py-3 rounded-lg bg-gradient-to-r from-red-600 to-rose-600 text-white font-semibold hover:from-red-700 hover:to-rose-700 transition-all shadow-lg">
                        <i class="fas fa-save mr-2"></i>
                        Save Policy
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openCreateModal() {
    document.getElementById('policyModal').classList.remove('hidden');
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-clock mr-3"></i>Create SLA Policy';
    document.getElementById('policyForm').action = '{{ route("admin.tickets.sla-policies.store") }}';
    document.getElementById('methodField').innerHTML = '';
    document.getElementById('policyForm').reset();
}

function editPolicy(id) {
    alert('Edit functionality will be implemented with AJAX');
    // TODO: Load policy data and populate form
}

function closeModal() {
    document.getElementById('policyModal').classList.add('hidden');
    document.getElementById('policyForm').reset();
}

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});

// Close modal on outside click
document.getElementById('policyModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>
@endsection
