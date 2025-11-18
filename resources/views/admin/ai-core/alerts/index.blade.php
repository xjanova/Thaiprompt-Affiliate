@extends('layouts.admin')
@section('title', 'จัดการ Alerts')
@section('content')
<div class="container-fluid px-4 py-6">
    <h1 class="text-3xl font-bold bg-gradient-to-r from-red-600 to-orange-600 dark:from-red-400 dark:to-orange-400 bg-clip-text text-transparent mb-6">
        🚨 จัดการ Alerts
    </h1>

    {{-- Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-2xl p-6 text-white shadow-xl">
            <p class="text-red-100 text-sm mb-1">Critical</p>
            <h3 class="text-3xl font-bold">{{ $stats['critical'] }}</h3>
        </div>
        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-2xl p-6 text-white shadow-xl">
            <p class="text-orange-100 text-sm mb-1">Error</p>
            <h3 class="text-3xl font-bold">{{ $stats['error'] }}</h3>
        </div>
        <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-2xl p-6 text-white shadow-xl">
            <p class="text-yellow-100 text-sm mb-1">Warning</p>
            <h3 class="text-3xl font-bold">{{ $stats['warning'] }}</h3>
        </div>
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-6 text-white shadow-xl">
            <p class="text-blue-100 text-sm mb-1">Unread</p>
            <h3 class="text-3xl font-bold">{{ $stats['unread'] }}</h3>
        </div>
    </div>

    {{-- Alerts Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Alert</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Tenant/Feature</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Severity</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Status</th>
                        <th class="px-6 py-4 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($alerts as $alert)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ !$alert->is_read ? 'bg-blue-50 dark:bg-blue-900/10' : '' }}">
                            <td class="px-6 py-4">
                                <div class="font-semibold text-gray-900 dark:text-white">{{ $alert->title }}</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">{{ Str::limit($alert->message, 60) }}</div>
                                <div class="text-xs text-gray-500 mt-1">{{ $alert->created_at->diffForHumans() }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                @if($alert->tenant)
                                    <div class="text-gray-900 dark:text-white">{{ $alert->tenant->tenant_name }}</div>
                                @endif
                                @if($alert->feature)
                                    <div class="text-gray-500 dark:text-gray-400">{{ $alert->feature->feature_name }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium
                                    {{ $alert->severity === 'critical' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : '' }}
                                    {{ $alert->severity === 'error' ? 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400' : '' }}
                                    {{ $alert->severity === 'warning' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : '' }}
                                    {{ $alert->severity === 'info' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' : '' }}">
                                    {{ strtoupper($alert->severity) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($alert->is_acknowledged)
                                    <span class="text-green-600 dark:text-green-400">✅ Acknowledged</span>
                                @elseif($alert->is_read)
                                    <span class="text-blue-600 dark:text-blue-400">👁️ Read</span>
                                @else
                                    <span class="text-gray-600 dark:text-gray-400">📬 Unread</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.ai-core.alerts.show', $alert) }}"
                                       class="inline-flex items-center px-3 py-1.5 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900/30 dark:hover:bg-blue-900/50 text-blue-700 dark:text-blue-400 rounded-lg transition-colors duration-150">
                                        ดู
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">ไม่มี Alerts</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($alerts->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">{{ $alerts->links() }}</div>
        @endif
    </div>
</div>
@endsection
