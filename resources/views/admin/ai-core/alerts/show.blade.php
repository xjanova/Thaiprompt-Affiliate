@extends('layouts.admin')
@section('title', 'รายละเอียด Alert')
@section('content')
<div class="container-fluid px-4 py-6">
    <div class="mb-6">
        <a href="{{ route('admin.ai-core.alerts.index') }}"
           class="inline-flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white mb-4">
            ← กลับ
        </a>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">🚨 รายละเอียด Alert</h1>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            {{-- Alert Info --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                <div class="flex items-start justify-between mb-4">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $alert->title }}</h2>
                    <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-semibold
                        {{ $alert->severity === 'critical' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : '' }}
                        {{ $alert->severity === 'error' ? 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400' : '' }}
                        {{ $alert->severity === 'warning' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : '' }}
                        {{ $alert->severity === 'info' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' : '' }}">
                        {{ strtoupper($alert->severity) }}
                    </span>
                </div>
                <div class="prose dark:prose-invert max-w-none">
                    <p class="text-gray-700 dark:text-gray-300">{{ $alert->message }}</p>
                </div>
                @if($alert->metadata)
                    <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Metadata:</p>
                        <pre class="text-xs text-gray-800 dark:text-gray-200 overflow-x-auto">{{ json_encode($alert->metadata, JSON_PRETTY_PRINT) }}</pre>
                    </div>
                @endif
            </div>

            {{-- Actions --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">⚡ Actions</h2>
                <div class="flex gap-3">
                    @if(!$alert->is_acknowledged)
                        <form method="POST" action="{{ route('admin.ai-core.alerts.acknowledge', $alert) }}">
                            @csrf
                            <button type="submit"
                                    class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg">
                                ✅ Acknowledge
                            </button>
                        </form>
                    @endif
                    @if(!$alert->is_resolved)
                        <form method="POST" action="{{ route('admin.ai-core.alerts.resolve', $alert) }}">
                            @csrf
                            <button type="submit"
                                    class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg">
                                ✔️ Resolve
                            </button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('admin.ai-core.alerts.dismiss', $alert) }}" onsubmit="return confirm('Dismiss alert?')">
                        @csrf
                        <button type="submit"
                                class="px-6 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg">
                            ❌ Dismiss
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            {{-- Status --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">ℹ️ Status</h2>
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Read</span>
                        <span>{{ $alert->is_read ? '✅' : '❌' }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Acknowledged</span>
                        <span>{{ $alert->is_acknowledged ? '✅' : '❌' }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Resolved</span>
                        <span>{{ $alert->is_resolved ? '✅' : '❌' }}</span>
                    </div>
                    @if($alert->occurrence_count > 1)
                        <div class="flex items-center justify-between p-3 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
                            <span class="text-sm text-orange-600 dark:text-orange-400">Occurrences</span>
                            <span class="font-bold text-orange-700 dark:text-orange-300">{{ $alert->occurrence_count }}</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Related Info --}}
            @if($alert->tenant || $alert->feature)
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">🔗 Related</h2>
                    <div class="space-y-3">
                        @if($alert->tenant)
                            <div>
                                <label class="text-sm text-gray-500 dark:text-gray-400">Tenant</label>
                                <p class="text-gray-900 dark:text-white font-medium">{{ $alert->tenant->tenant_name }}</p>
                            </div>
                        @endif
                        @if($alert->feature)
                            <div>
                                <label class="text-sm text-gray-500 dark:text-gray-400">Feature</label>
                                <p class="text-gray-900 dark:text-white font-medium">{{ $alert->feature->feature_name }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Timestamps --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">🕐 เวลา</h2>
                <div class="space-y-2 text-sm">
                    <div>
                        <label class="text-gray-500 dark:text-gray-400">Created</label>
                        <p class="text-gray-900 dark:text-white">{{ $alert->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                    @if($alert->acknowledged_at)
                        <div>
                            <label class="text-gray-500 dark:text-gray-400">Acknowledged</label>
                            <p class="text-gray-900 dark:text-white">{{ $alert->acknowledged_at->format('d/m/Y H:i') }}</p>
                        </div>
                    @endif
                    @if($alert->resolved_at)
                        <div>
                            <label class="text-gray-500 dark:text-gray-400">Resolved</label>
                            <p class="text-gray-900 dark:text-white">{{ $alert->resolved_at->format('d/m/Y H:i') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
