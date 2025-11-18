@extends('layouts.admin-v3')

@section('title', 'รายละเอียด API Endpoint')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200 flex items-center gap-2">
                        <span class="px-3 py-1 text-sm font-semibold rounded
                            {{ $apiEndpoint->method === 'GET' ? 'bg-blue-100 text-blue-800' : '' }}
                            {{ $apiEndpoint->method === 'POST' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $apiEndpoint->method === 'PUT' ? 'bg-yellow-100 text-yellow-800' : '' }}
                            {{ $apiEndpoint->method === 'PATCH' ? 'bg-orange-100 text-orange-800' : '' }}
                            {{ $apiEndpoint->method === 'DELETE' ? 'bg-red-100 text-red-800' : '' }}">
                            {{ $apiEndpoint->method }}
                        </span>
                        {{ $apiEndpoint->name }}
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-2 font-mono">{{ $apiEndpoint->path }}</p>
                </div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.api-management.endpoints.edit', $apiEndpoint) }}"
                   class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg transition">
                    แก้ไข
                </a>
                <a href="{{ route('admin.api-management.endpoints.index') }}"
                   class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg transition">
                    ← กลับ
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-blue-100">Total Requests</p>
                    <p class="text-3xl font-bold mt-1">{{ number_format($stats['total_requests']) }}</p>
                </div>
                <div class="w-14 h-14 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-green-100">Successful</p>
                    <p class="text-3xl font-bold mt-1">{{ number_format($stats['successful_requests']) }}</p>
                </div>
                <div class="w-14 h-14 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-red-100">Failed</p>
                    <p class="text-3xl font-bold mt-1">{{ number_format($stats['failed_requests']) }}</p>
                </div>
                <div class="w-14 h-14 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-purple-100">Avg Response Time</p>
                    <p class="text-3xl font-bold mt-1">{{ number_format($stats['avg_response_time'] ?? 0) }}ms</p>
                </div>
                <div class="w-14 h-14 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Endpoint Details -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Basic Information -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">📋 ข้อมูลพื้นฐาน</h3>

            <div class="space-y-3">
                <div>
                    <label class="text-sm text-gray-500 dark:text-gray-400">หมวดหมู่</label>
                    <p class="text-gray-900 dark:text-gray-200">
                        @if($apiEndpoint->category)
                            <span class="px-2 py-1 text-xs bg-purple-100 text-purple-800 rounded">{{ $apiEndpoint->category }}</span>
                        @else
                            <span class="text-gray-400">ไม่ระบุ</span>
                        @endif
                    </p>
                </div>

                <div>
                    <label class="text-sm text-gray-500 dark:text-gray-400">คำอธิบาย</label>
                    <p class="text-gray-900 dark:text-gray-200">{{ $apiEndpoint->description ?: 'ไม่มีคำอธิบาย' }}</p>
                </div>

                <div>
                    <label class="text-sm text-gray-500 dark:text-gray-400">สถานะ</label>
                    <p class="text-gray-900 dark:text-gray-200">
                        <span class="px-3 py-1 text-xs font-semibold rounded
                            {{ $apiEndpoint->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $apiEndpoint->is_active ? '✓ เปิดใช้งาน' : '✗ ปิดใช้งาน' }}
                        </span>
                    </p>
                </div>

                <div>
                    <label class="text-sm text-gray-500 dark:text-gray-400">ต้องการ Authentication</label>
                    <p class="text-gray-900 dark:text-gray-200">
                        {{ $apiEndpoint->requires_auth ? '✓ ใช่' : '✗ ไม่' }}
                    </p>
                </div>

                @if($apiEndpoint->allowed_roles)
                <div>
                    <label class="text-sm text-gray-500 dark:text-gray-400">Roles ที่อนุญาต</label>
                    <p class="text-gray-900 dark:text-gray-200">
                        @foreach($apiEndpoint->allowed_roles as $role)
                            <span class="inline-block px-2 py-1 text-xs bg-gray-100 dark:bg-gray-700 rounded mr-1 mb-1">{{ $role }}</span>
                        @endforeach
                    </p>
                </div>
                @endif

                <div>
                    <label class="text-sm text-gray-500 dark:text-gray-400">ลำดับความสำคัญ</label>
                    <p class="text-gray-900 dark:text-gray-200">{{ $apiEndpoint->priority ?? 0 }}</p>
                </div>
            </div>
        </div>

        <!-- Rate Limiting -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">⏱️ การจำกัดอัตรา</h3>

            <div class="space-y-3">
                <div>
                    <label class="text-sm text-gray-500 dark:text-gray-400">จำกัดต่อนาที</label>
                    <p class="text-gray-900 dark:text-gray-200">{{ $apiEndpoint->rate_limit_per_minute ?? 'ไม่จำกัด' }}</p>
                </div>

                <div>
                    <label class="text-sm text-gray-500 dark:text-gray-400">จำกัดต่อชั่วโมง</label>
                    <p class="text-gray-900 dark:text-gray-200">{{ $apiEndpoint->rate_limit_per_hour ?? 'ไม่จำกัด' }}</p>
                </div>

                <div>
                    <label class="text-sm text-gray-500 dark:text-gray-400">จำกัดต่อวัน</label>
                    <p class="text-gray-900 dark:text-gray-200">{{ $apiEndpoint->rate_limit_per_day ?? 'ไม่จำกัด' }}</p>
                </div>
            </div>

            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-3">สถิติการใช้งาน</h4>
                <div class="space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">วันนี้</span>
                        <span class="text-gray-900 dark:text-gray-200 font-medium">{{ number_format($stats['today_requests']) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">เดือนนี้</span>
                        <span class="text-gray-900 dark:text-gray-200 font-medium">{{ number_format($stats['this_month_requests']) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Documentation -->
    @if($apiEndpoint->parameters || $apiEndpoint->response_example)
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">📚 เอกสารประกอบ</h3>

        @if($apiEndpoint->parameters)
        <div class="mb-6">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 block">Parameters</label>
            <pre class="bg-gray-100 dark:bg-gray-900 rounded-lg p-4 overflow-x-auto text-sm"><code>{{ $apiEndpoint->parameters }}</code></pre>
        </div>
        @endif

        @if($apiEndpoint->response_example)
        <div>
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 block">ตัวอย่าง Response</label>
            <pre class="bg-gray-100 dark:bg-gray-900 rounded-lg p-4 overflow-x-auto text-sm"><code>{{ $apiEndpoint->response_example }}</code></pre>
        </div>
        @endif
    </div>
    @endif

    <!-- Recent Logs -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">📊 การใช้งานล่าสุด</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">เวลา</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">API Key</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Response Code</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Response Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">IP Address</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($recentLogs as $log)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                            {{ $log->created_at->format('d/m/Y H:i:s') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            @if($log->apiKey)
                                <a href="{{ route('admin.api-management.keys.show', $log->apiKey) }}" class="text-blue-600 hover:text-blue-900">
                                    {{ $log->apiKey->name }}
                                </a>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded
                                {{ $log->response_code >= 200 && $log->response_code < 300 ? 'bg-green-100 text-green-800' : '' }}
                                {{ $log->response_code >= 400 ? 'bg-red-100 text-red-800' : '' }}
                                {{ $log->response_code >= 300 && $log->response_code < 400 ? 'bg-yellow-100 text-yellow-800' : '' }}">
                                {{ $log->response_code }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                            {{ number_format($log->response_time_ms) }}ms
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 font-mono">
                            {{ $log->ip_address }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            <div class="text-6xl mb-4">📊</div>
                            <div class="text-lg font-medium">ยังไม่มีการใช้งาน</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
