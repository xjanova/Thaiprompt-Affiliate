@extends('layouts.admin')

@section('title', 'รายละเอียด API Key')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Header with API Key Display -->
    @if(session('api_key'))
    <div class="bg-gradient-to-r from-green-500 to-emerald-600 rounded-lg shadow-lg p-6 mb-6 text-white">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <h3 class="text-xl font-bold mb-2">✅ API Key สร้างสำเร็จ!</h3>
                <p class="text-sm text-green-100 mb-4">
                    โปรดคัดลอกและเก็บ API Key นี้ไว้อย่างปลอดภัย คุณจะไม่สามารถดู key นี้อีกครั้ง
                </p>
                <div class="bg-white bg-opacity-20 rounded-lg p-4">
                    <label class="block text-xs text-green-100 mb-2">API Key ของคุณ:</label>
                    <div class="flex items-center gap-2">
                        <code class="flex-1 bg-gray-900 text-white px-4 py-3 rounded font-mono text-sm break-all">{{ session('api_key') }}</code>
                        <button onclick="copyApiKey('{{ session('api_key') }}')"
                                class="px-4 py-3 bg-white text-green-600 rounded hover:bg-green-50 transition flex-shrink-0">
                            📋 คัดลอก
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200">
                    🔑 {{ $apiKey->name }}
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                    <code class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono">{{ $apiKey->prefix }}</code>
                </p>
                @if($apiKey->description)
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">{{ $apiKey->description }}</p>
                @endif
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.api-management.keys.edit', $apiKey) }}"
                   class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg transition">
                    แก้ไข
                </a>
                <a href="{{ route('admin.api-management.keys.index') }}"
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

    <!-- Key Details and Quota -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Key Information -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">📋 ข้อมูล API Key</h3>

            <div class="space-y-3">
                @if($apiKey->user)
                <div>
                    <label class="text-sm text-gray-500 dark:text-gray-400">เจ้าของ</label>
                    <p class="text-gray-900 dark:text-gray-200">
                        {{ $apiKey->user->name }}
                        <span class="text-sm text-gray-500">({{ $apiKey->user->email }})</span>
                    </p>
                </div>
                @endif

                <div>
                    <label class="text-sm text-gray-500 dark:text-gray-400">สถานะ</label>
                    <p class="text-gray-900 dark:text-gray-200">
                        @php
                            $isExpired = $apiKey->expires_at && $apiKey->expires_at->isPast();
                            $isActive = $apiKey->is_active && !$isExpired;
                        @endphp
                        <span class="px-3 py-1 text-xs font-semibold rounded
                            {{ $isActive ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $isActive ? '✓ เปิดใช้งาน' : ($isExpired ? '⏰ หมดอายุ' : '✗ ปิดใช้งาน') }}
                        </span>
                    </p>
                </div>

                @if($apiKey->scopes)
                <div>
                    <label class="text-sm text-gray-500 dark:text-gray-400">Scopes</label>
                    <p class="text-gray-900 dark:text-gray-200">
                        @foreach($apiKey->scopes as $scope)
                            <span class="inline-block px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded mr-1 mb-1">{{ $scope }}</span>
                        @endforeach
                    </p>
                </div>
                @endif

                @if($apiKey->allowed_ips)
                <div>
                    <label class="text-sm text-gray-500 dark:text-gray-400">IP Addresses ที่อนุญาต</label>
                    <div class="text-gray-900 dark:text-gray-200 text-sm font-mono">
                        @foreach($apiKey->allowed_ips as $ip)
                            <div>{{ $ip }}</div>
                        @endforeach
                    </div>
                </div>
                @endif

                <div>
                    <label class="text-sm text-gray-500 dark:text-gray-400">สร้างเมื่อ</label>
                    <p class="text-gray-900 dark:text-gray-200">{{ $apiKey->created_at->format('d/m/Y H:i:s') }}</p>
                </div>

                @if($apiKey->last_used_at)
                <div>
                    <label class="text-sm text-gray-500 dark:text-gray-400">ใช้งานล่าสุด</label>
                    <p class="text-gray-900 dark:text-gray-200">{{ $apiKey->last_used_at->diffForHumans() }}</p>
                </div>
                @endif

                @if($apiKey->expires_at)
                <div>
                    <label class="text-sm text-gray-500 dark:text-gray-400">วันหมดอายุ</label>
                    <p class="text-gray-900 dark:text-gray-200">{{ $apiKey->expires_at->format('d/m/Y') }}</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Quota and Rate Limiting -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">⏱️ โควต้าและข้อจำกัด</h3>

            @if($apiKey->monthly_quota)
            <div class="mb-6">
                <div class="flex justify-between items-center mb-2">
                    <label class="text-sm text-gray-500 dark:text-gray-400">การใช้งานรายเดือน</label>
                    <span class="text-sm font-semibold text-gray-900 dark:text-gray-200">
                        {{ number_format($apiKey->monthly_usage) }} / {{ number_format($apiKey->monthly_quota) }}
                    </span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3">
                    @php
                        $percentage = ($apiKey->monthly_usage / $apiKey->monthly_quota) * 100;
                        $colorClass = $percentage >= 90 ? 'bg-red-500' : ($percentage >= 70 ? 'bg-yellow-500' : 'bg-green-500');
                    @endphp
                    <div class="{{ $colorClass }} h-3 rounded-full transition-all" style="width: {{ min($percentage, 100) }}%"></div>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ number_format($percentage, 1) }}% ใช้ไปแล้ว</p>
            </div>
            @endif

            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-sm text-gray-500 dark:text-gray-400">จำกัดต่อนาที</span>
                    <span class="text-sm text-gray-900 dark:text-gray-200">{{ $apiKey->rate_limit_per_minute ?? 'ไม่จำกัด' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-500 dark:text-gray-400">จำกัดต่อชั่วโมง</span>
                    <span class="text-sm text-gray-900 dark:text-gray-200">{{ $apiKey->rate_limit_per_hour ?? 'ไม่จำกัด' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-500 dark:text-gray-400">จำกัดต่อวัน</span>
                    <span class="text-sm text-gray-900 dark:text-gray-200">{{ $apiKey->rate_limit_per_day ?? 'ไม่จำกัด' }}</span>
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

    <!-- Top Endpoints -->
    @if($topEndpoints->isNotEmpty())
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">🔝 Endpoints ที่ใช้บ่อยที่สุด</h3>

        <div class="space-y-3">
            @foreach($topEndpoints as $log)
                @if($log->apiEndpoint)
                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="flex items-center gap-3">
                        <span class="px-2 py-1 text-xs font-semibold rounded
                            {{ $log->apiEndpoint->method === 'GET' ? 'bg-blue-100 text-blue-800' : '' }}
                            {{ $log->apiEndpoint->method === 'POST' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $log->apiEndpoint->method === 'PUT' ? 'bg-yellow-100 text-yellow-800' : '' }}
                            {{ $log->apiEndpoint->method === 'PATCH' ? 'bg-orange-100 text-orange-800' : '' }}
                            {{ $log->apiEndpoint->method === 'DELETE' ? 'bg-red-100 text-red-800' : '' }}">
                            {{ $log->apiEndpoint->method }}
                        </span>
                        <div>
                            <div class="text-sm font-medium text-gray-900 dark:text-gray-200">{{ $log->apiEndpoint->name }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 font-mono">{{ $log->apiEndpoint->path }}</div>
                        </div>
                    </div>
                    <div class="text-sm font-semibold text-gray-900 dark:text-gray-200">
                        {{ number_format($log->count) }} requests
                    </div>
                </div>
                @endif
            @endforeach
        </div>
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Endpoint</th>
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
                        <td class="px-6 py-4 text-sm">
                            @if($log->apiEndpoint)
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-1 text-xs font-semibold rounded
                                        {{ $log->apiEndpoint->method === 'GET' ? 'bg-blue-100 text-blue-800' : '' }}
                                        {{ $log->apiEndpoint->method === 'POST' ? 'bg-green-100 text-green-800' : '' }}
                                        {{ $log->apiEndpoint->method === 'PUT' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                        {{ $log->apiEndpoint->method === 'PATCH' ? 'bg-orange-100 text-orange-800' : '' }}
                                        {{ $log->apiEndpoint->method === 'DELETE' ? 'bg-red-100 text-red-800' : '' }}">
                                        {{ $log->apiEndpoint->method }}
                                    </span>
                                    <a href="{{ route('admin.api-management.endpoints.show', $log->apiEndpoint) }}"
                                       class="text-blue-600 hover:text-blue-900">
                                        {{ $log->apiEndpoint->name }}
                                    </a>
                                </div>
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

<script>
function copyApiKey(key) {
    navigator.clipboard.writeText(key).then(() => {
        alert('คัดลอก API Key แล้ว!');
    });
}
</script>
@endsection
