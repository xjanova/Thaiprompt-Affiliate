@extends('layouts.admin')

@section('title', 'จัดการ API Endpoints')

@section('content')
<div class="max-w-7xl mx-auto" x-data="apiEndpointsManager()">
    <!-- Header Section -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200">
                    🔌 จัดการ API Endpoints
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    กำหนดและจัดการ API endpoints ทั้งหมด พร้อมระบบโควต้าและการตรวจสอบ
                </p>
            </div>
            <a href="{{ route('admin.api-management.endpoints.create') }}"
               class="px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-lg transition-all duration-200 flex items-center gap-2 shadow-lg hover:shadow-xl">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                เพิ่ม Endpoint ใหม่
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-blue-100">Total Endpoints</p>
                    <p class="text-3xl font-bold mt-1">{{ $endpoints->total() }}</p>
                </div>
                <div class="w-14 h-14 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-green-100">Active Endpoints</p>
                    <p class="text-3xl font-bold mt-1">{{ \App\Models\ApiEndpoint::where('is_active', true)->count() }}</p>
                </div>
                <div class="w-14 h-14 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-orange-100">Inactive Endpoints</p>
                    <p class="text-3xl font-bold mt-1">{{ \App\Models\ApiEndpoint::where('is_active', false)->count() }}</p>
                </div>
                <div class="w-14 h-14 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-purple-100">Categories</p>
                    <p class="text-3xl font-bold mt-1">{{ $categories->count() }}</p>
                </div>
                <div class="w-14 h-14 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Search -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">🔍 ค้นหา</label>
                <form action="{{ route('admin.api-management.endpoints.index') }}" method="GET">
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="ค้นหา endpoint, path, คำอธิบาย..."
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                </form>
            </div>

            <!-- Category Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">📁 หมวดหมู่</label>
                <select x-model="filters.category" @change="applyFilters()"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                    <option value="">ทั้งหมด</option>
                    @foreach($categories as $category)
                        <option value="{{ $category }}" {{ request('category') == $category ? 'selected' : '' }}>{{ $category }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Method Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">🔧 Method</label>
                <select x-model="filters.method" @change="applyFilters()"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                    <option value="">ทั้งหมด</option>
                    <option value="GET" {{ request('method') == 'GET' ? 'selected' : '' }}>GET</option>
                    <option value="POST" {{ request('method') == 'POST' ? 'selected' : '' }}>POST</option>
                    <option value="PUT" {{ request('method') == 'PUT' ? 'selected' : '' }}>PUT</option>
                    <option value="PATCH" {{ request('method') == 'PATCH' ? 'selected' : '' }}>PATCH</option>
                    <option value="DELETE" {{ request('method') == 'DELETE' ? 'selected' : '' }}>DELETE</option>
                </select>
            </div>
        </div>

        <div class="flex items-center gap-4 mt-4">
            <!-- Status Filter -->
            <div class="flex items-center gap-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">สถานะ:</label>
                <div class="flex gap-2">
                    <button @click="filters.status = ''; applyFilters()"
                            :class="filters.status === '' ? 'bg-gray-600 text-white' : 'bg-gray-200 text-gray-700'"
                            class="px-3 py-1 rounded-lg text-sm transition-all">
                        ทั้งหมด
                    </button>
                    <button @click="filters.status = 'active'; applyFilters()"
                            :class="filters.status === 'active' ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-700'"
                            class="px-3 py-1 rounded-lg text-sm transition-all">
                        เปิดใช้งาน
                    </button>
                    <button @click="filters.status = 'inactive'; applyFilters()"
                            :class="filters.status === 'inactive' ? 'bg-red-600 text-white' : 'bg-gray-200 text-gray-700'"
                            class="px-3 py-1 rounded-lg text-sm transition-all">
                        ปิดใช้งาน
                    </button>
                </div>
            </div>

            <!-- Clear Filters -->
            <a href="{{ route('admin.api-management.endpoints.index') }}"
               class="ml-auto px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg text-sm transition-all">
                🔄 ล้างฟิลเตอร์
            </a>
        </div>
    </div>

    <!-- Endpoints List -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Method
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Endpoint
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Category
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            คำอธิบาย
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Rate Limit
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            สถานะ
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            จัดการ
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($endpoints as $endpoint)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded
                                {{ $endpoint->method === 'GET' ? 'bg-blue-100 text-blue-800' : '' }}
                                {{ $endpoint->method === 'POST' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $endpoint->method === 'PUT' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $endpoint->method === 'PATCH' ? 'bg-orange-100 text-orange-800' : '' }}
                                {{ $endpoint->method === 'DELETE' ? 'bg-red-100 text-red-800' : '' }}">
                                {{ $endpoint->method }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900 dark:text-gray-200">{{ $endpoint->name }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 font-mono">{{ $endpoint->path }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($endpoint->category)
                                <span class="px-2 py-1 text-xs bg-purple-100 text-purple-800 rounded">{{ $endpoint->category }}</span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-700 dark:text-gray-300 max-w-xs truncate" title="{{ $endpoint->description }}">
                                {{ $endpoint->description ?: 'ไม่มีคำอธิบาย' }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            <div>{{ $endpoint->rate_limit_per_minute }}/min</div>
                            <div class="text-xs">{{ $endpoint->rate_limit_per_hour }}/hour</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <button @click="toggleStatus({{ $endpoint->id }}, {{ $endpoint->is_active ? 'true' : 'false' }})"
                                    class="px-3 py-1 text-xs font-semibold rounded transition-all
                                    {{ $endpoint->is_active ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-red-100 text-red-800 hover:bg-red-200' }}">
                                {{ $endpoint->is_active ? '✓ เปิดใช้งาน' : '✗ ปิดใช้งาน' }}
                            </button>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('admin.api-management.endpoints.show', $endpoint) }}"
                                   class="text-blue-600 hover:text-blue-900 dark:text-blue-400" title="ดูรายละเอียด">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </a>
                                <a href="{{ route('admin.api-management.endpoints.edit', $endpoint) }}"
                                   class="text-yellow-600 hover:text-yellow-900 dark:text-yellow-400" title="แก้ไข">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </a>
                                <button @click="deleteEndpoint({{ $endpoint->id }})"
                                        class="text-red-600 hover:text-red-900 dark:text-red-400" title="ลบ">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            <div class="text-6xl mb-4">📭</div>
                            <div class="text-lg font-medium">ไม่พบ API Endpoints</div>
                            <div class="text-sm mt-2">ลองเปลี่ยนฟิลเตอร์หรือเพิ่ม endpoint ใหม่</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($endpoints->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $endpoints->links() }}
        </div>
        @endif
    </div>
</div>

<script>
function apiEndpointsManager() {
    return {
        filters: {
            category: '{{ request('category') }}',
            method: '{{ request('method') }}',
            status: '{{ request('status') }}',
        },

        applyFilters() {
            const params = new URLSearchParams();
            if (this.filters.category) params.append('category', this.filters.category);
            if (this.filters.method) params.append('method', this.filters.method);
            if (this.filters.status) params.append('status', this.filters.status);

            window.location.href = '{{ route('admin.api-management.endpoints.index') }}?' + params.toString();
        },

        toggleStatus(id, currentStatus) {
            if (!confirm('คุณต้องการเปลี่ยนสถานะ endpoint นี้หรือไม่?')) return;

            fetch(`/admin/api-management/endpoints/${id}/toggle-status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                }
            });
        },

        deleteEndpoint(id) {
            if (!confirm('คุณต้องการลบ endpoint นี้หรือไม่? การดำเนินการนี้ไม่สามารถย้อนกลับได้')) return;

            fetch(`/admin/api-management/endpoints/${id}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                window.location.reload();
            });
        }
    }
}
</script>
@endsection
