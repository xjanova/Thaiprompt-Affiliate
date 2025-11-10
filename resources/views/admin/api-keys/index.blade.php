@extends('layouts.admin')

@section('title', 'จัดการ API Keys')

@section('content')
<div class="max-w-7xl mx-auto" x-data="apiKeysManager()">
    <!-- Header Section -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200">
                    🔑 จัดการ API Keys
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    สร้างและจัดการ API keys สำหรับการเข้าถึง API พร้อมตรวจสอบการใช้งาน
                </p>
            </div>
            <a href="{{ route('admin.api-management.keys.create') }}"
               class="px-4 py-2 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white rounded-lg transition-all duration-200 flex items-center gap-2 shadow-lg hover:shadow-xl">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                สร้าง API Key ใหม่
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-green-100">Total API Keys</p>
                    <p class="text-3xl font-bold mt-1">{{ $apiKeys->total() }}</p>
                </div>
                <div class="w-14 h-14 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-blue-100">Active Keys</p>
                    <p class="text-3xl font-bold mt-1">{{ \App\Models\ApiKey::active()->count() }}</p>
                </div>
                <div class="w-14 h-14 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-purple-100">Total Requests Today</p>
                    <p class="text-3xl font-bold mt-1">{{ \App\Models\ApiUsageLog::today()->count() }}</p>
                </div>
                <div class="w-14 h-14 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-orange-500 to-red-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-orange-100">Failed Requests Today</p>
                    <p class="text-3xl font-bold mt-1">{{ \App\Models\ApiUsageLog::today()->failed()->count() }}</p>
                </div>
                <div class="w-14 h-14 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Search -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">🔍 ค้นหา</label>
                <form action="{{ route('admin.api-management.keys.index') }}" method="GET">
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="ค้นหา key name, user..."
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white">
                </form>
            </div>

            <!-- User Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">👤 ผู้ใช้</label>
                <select x-model="filters.user_id" @change="applyFilters()"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white">
                    <option value="">ทั้งหมด</option>
                    @foreach(\App\Models\User::orderBy('name')->get() as $user)
                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                            {{ $user->name }} ({{ $user->email }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Status Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">📊 สถานะ</label>
                <select x-model="filters.status" @change="applyFilters()"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white">
                    <option value="">ทั้งหมด</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>เปิดใช้งาน</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>ปิดใช้งาน</option>
                    <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>หมดอายุ</option>
                </select>
            </div>
        </div>

        <div class="mt-4">
            <a href="{{ route('admin.api-management.keys.index') }}"
               class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg text-sm transition-all">
                🔄 ล้างฟิลเตอร์
            </a>
        </div>
    </div>

    <!-- API Keys List -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            ชื่อ
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            API Key
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            ผู้ใช้
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            การใช้งาน
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            โควต้า
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
                    @forelse($apiKeys as $key)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900 dark:text-gray-200">{{ $key->name }}</div>
                            @if($key->description)
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ Str::limit($key->description, 50) }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <code class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono">{{ $key->masked_key }}</code>
                                <button @click="copyToClipboard('{{ $key->prefix }}')"
                                        class="text-gray-400 hover:text-gray-600" title="คัดลอก">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                </button>
                            </div>
                            @if($key->last_used_at)
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    ใช้ล่าสุด: {{ $key->last_used_at->diffForHumans() }}
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($key->user)
                                <div class="text-sm text-gray-900 dark:text-gray-200">{{ $key->user->name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $key->user->email }}</div>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900 dark:text-gray-200">
                                {{ number_format($key->monthly_usage) }} requests
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">เดือนนี้</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($key->monthly_quota)
                                @php
                                    $percentage = ($key->monthly_usage / $key->monthly_quota) * 100;
                                    $colorClass = $percentage >= 90 ? 'bg-red-500' : ($percentage >= 70 ? 'bg-yellow-500' : 'bg-green-500');
                                @endphp
                                <div class="text-sm text-gray-900 dark:text-gray-200">
                                    {{ number_format($key->monthly_usage) }} / {{ number_format($key->monthly_quota) }}
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                                    <div class="{{ $colorClass }} h-2 rounded-full" style="width: {{ min($percentage, 100) }}%"></div>
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ number_format($percentage, 1) }}%</div>
                            @else
                                <span class="text-sm text-gray-500 dark:text-gray-400">ไม่จำกัด</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $isExpired = $key->expires_at && $key->expires_at->isPast();
                                $isActive = $key->is_active && !$isExpired;
                            @endphp
                            <button @click="toggleStatus({{ $key->id }}, {{ $isActive ? 'true' : 'false' }})"
                                    class="px-3 py-1 text-xs font-semibold rounded transition-all
                                    {{ $isActive ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-red-100 text-red-800 hover:bg-red-200' }}">
                                {{ $isActive ? '✓ เปิดใช้งาน' : ($isExpired ? '⏰ หมดอายุ' : '✗ ปิดใช้งาน') }}
                            </button>
                            @if($key->expires_at)
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    หมดอายุ: {{ $key->expires_at->format('d/m/Y') }}
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('admin.api-management.keys.show', $key) }}"
                                   class="text-blue-600 hover:text-blue-900 dark:text-blue-400" title="ดูรายละเอียด">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                </a>
                                <a href="{{ route('admin.api-management.keys.edit', $key) }}"
                                   class="text-yellow-600 hover:text-yellow-900 dark:text-yellow-400" title="แก้ไข">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </a>
                                <button @click="resetUsage({{ $key->id }})"
                                        class="text-purple-600 hover:text-purple-900 dark:text-purple-400" title="รีเซ็ตการใช้งาน">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                </button>
                                <button @click="deleteKey({{ $key->id }})"
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
                            <div class="text-6xl mb-4">🔑</div>
                            <div class="text-lg font-medium">ไม่พบ API Keys</div>
                            <div class="text-sm mt-2">เริ่มต้นด้วยการสร้าง API Key ใหม่</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($apiKeys->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $apiKeys->links() }}
        </div>
        @endif
    </div>
</div>

<script>
function apiKeysManager() {
    return {
        filters: {
            user_id: '{{ request('user_id') }}',
            status: '{{ request('status') }}',
        },

        applyFilters() {
            const params = new URLSearchParams();
            if (this.filters.user_id) params.append('user_id', this.filters.user_id);
            if (this.filters.status) params.append('status', this.filters.status);

            window.location.href = '{{ route('admin.api-management.keys.index') }}?' + params.toString();
        },

        copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('คัดลอก API Key แล้ว!');
            });
        },

        toggleStatus(id, currentStatus) {
            if (!confirm('คุณต้องการเปลี่ยนสถานะ API Key นี้หรือไม่?')) return;

            fetch(`/admin/api-management/keys/${id}/toggle-status`, {
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

        resetUsage(id) {
            if (!confirm('คุณต้องการรีเซ็ตการใช้งานรายเดือนหรือไม่?')) return;

            fetch(`/admin/api-management/keys/${id}/reset-usage`, {
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

        deleteKey(id) {
            if (!confirm('คุณต้องการลบ API Key นี้หรือไม่? การดำเนินการนี้ไม่สามารถย้อนกลับได้')) return;

            fetch(`/admin/api-management/keys/${id}`, {
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
