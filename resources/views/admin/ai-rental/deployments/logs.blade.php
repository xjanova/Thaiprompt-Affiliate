@extends('layouts.admin')

@section('title', 'Deployment Logs')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-purple-900 to-gray-900 py-8 px-4">
    <div class="max-w-7xl mx-auto">
        {{-- Header --}}
        <div class="mb-8">
            <a href="{{ route('admin.ai-rental.deployments.show', $deployment) }}"
               class="inline-flex items-center text-cyan-400 hover:text-cyan-300 mb-4 transition">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Deployment
            </a>
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
                <div>
                    <h1 class="text-4xl font-bold text-white mb-2 flex items-center gap-3">
                        <i class="fas fa-terminal text-cyan-400"></i>
                        Deployment Logs
                    </h1>
                    <p class="text-white/60">{{ $deployment->name }}</p>
                </div>

                {{-- Status --}}
                @php
                    $statusColors = [
                        'running' => 'from-green-400 to-emerald-500',
                        'starting' => 'from-blue-400 to-cyan-500',
                        'stopping' => 'from-yellow-400 to-orange-500',
                        'stopped' => 'from-gray-400 to-gray-500',
                        'failed' => 'from-red-400 to-rose-500',
                        'error' => 'from-red-400 to-rose-500',
                    ];
                    $color = $statusColors[$deployment->status] ?? 'from-gray-400 to-gray-500';
                @endphp
                <div class="px-6 py-3 bg-gradient-to-r {{ $color }} text-white rounded-xl font-bold uppercase shadow-lg">
                    <i class="fas fa-circle mr-2 animate-pulse"></i>
                    {{ $deployment->status }}
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            {{-- Sidebar: Info --}}
            <div class="lg:col-span-1 space-y-6">
                {{-- Deployment Info --}}
                <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                    <h2 class="text-lg font-bold text-white mb-4">Info</h2>
                    <div class="space-y-3 text-sm">
                        <div>
                            <div class="text-white/60 mb-1">Model</div>
                            <div class="text-white font-semibold">{{ $deployment->model->name }}</div>
                        </div>
                        <div>
                            <div class="text-white/60 mb-1">Provider</div>
                            <div class="text-white font-semibold">{{ $deployment->cloudConfig->cloudProvider->name }}</div>
                        </div>
                        <div>
                            <div class="text-white/60 mb-1">Instance</div>
                            <div class="text-white font-semibold">{{ $deployment->instance_type }}</div>
                        </div>
                        @if($deployment->instance_id)
                            <div>
                                <div class="text-white/60 mb-1">Instance ID</div>
                                <div class="text-white font-mono text-xs">{{ $deployment->instance_id }}</div>
                            </div>
                        @endif
                        <div>
                            <div class="text-white/60 mb-1">Uptime</div>
                            <div class="text-white font-semibold">{{ $deployment->getUptimeMinutes() }} minutes</div>
                        </div>
                    </div>
                </div>

                {{-- Log Controls --}}
                <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                    <h2 class="text-lg font-bold text-white mb-4">Controls</h2>
                    <div class="space-y-2">
                        <button onclick="refreshLogs()"
                                class="w-full px-4 py-2 bg-cyan-500 hover:bg-cyan-600 text-white rounded-xl font-semibold transition">
                            <i class="fas fa-sync-alt mr-2"></i>Refresh
                        </button>
                        <button onclick="clearLogs()"
                                class="w-full px-4 py-2 bg-white/10 hover:bg-white/20 text-white rounded-xl font-semibold transition border border-white/30">
                            <i class="fas fa-eraser mr-2"></i>Clear View
                        </button>
                        <button onclick="downloadLogs()"
                                class="w-full px-4 py-2 bg-white/10 hover:bg-white/20 text-white rounded-xl font-semibold transition border border-white/30">
                            <i class="fas fa-download mr-2"></i>Download
                        </button>
                    </div>

                    {{-- Auto Refresh Toggle --}}
                    <div class="mt-4 pt-4 border-t border-white/10">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox"
                                   id="autoRefresh"
                                   onchange="toggleAutoRefresh()"
                                   class="w-5 h-5 rounded bg-white/10 border-white/30 text-cyan-500 focus:ring-cyan-400">
                            <span class="text-white/80 text-sm">Auto-refresh (5s)</span>
                        </label>
                    </div>
                </div>

                {{-- Log Filters --}}
                <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                    <h2 class="text-lg font-bold text-white mb-4">Filters</h2>
                    <div class="space-y-3">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox"
                                   id="filter-info"
                                   checked
                                   onchange="applyFilters()"
                                   class="w-4 h-4 rounded bg-white/10 border-white/30 text-blue-500">
                            <i class="fas fa-circle text-blue-400 text-xs"></i>
                            <span class="text-white/80 text-sm">Info</span>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox"
                                   id="filter-success"
                                   checked
                                   onchange="applyFilters()"
                                   class="w-4 h-4 rounded bg-white/10 border-white/30 text-green-500">
                            <i class="fas fa-circle text-green-400 text-xs"></i>
                            <span class="text-white/80 text-sm">Success</span>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox"
                                   id="filter-warning"
                                   checked
                                   onchange="applyFilters()"
                                   class="w-4 h-4 rounded bg-white/10 border-white/30 text-yellow-500">
                            <i class="fas fa-circle text-yellow-400 text-xs"></i>
                            <span class="text-white/80 text-sm">Warning</span>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox"
                                   id="filter-error"
                                   checked
                                   onchange="applyFilters()"
                                   class="w-4 h-4 rounded bg-white/10 border-white/30 text-red-500">
                            <i class="fas fa-circle text-red-400 text-xs"></i>
                            <span class="text-white/80 text-sm">Error</span>
                        </label>
                    </div>
                </div>
            </div>

            {{-- Main: Logs Display --}}
            <div class="lg:col-span-3">
                <div class="bg-black/80 backdrop-blur-lg rounded-2xl border border-white/20 overflow-hidden">
                    {{-- Logs Header --}}
                    <div class="bg-white/5 px-6 py-4 border-b border-white/10 flex justify-between items-center">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-terminal text-cyan-400"></i>
                            <span class="text-white font-semibold">Console Output</span>
                        </div>
                        <div class="text-white/40 text-sm font-mono">
                            Last updated: <span id="lastUpdated">{{ now()->format('H:i:s') }}</span>
                        </div>
                    </div>

                    {{-- Logs Container --}}
                    <div id="logsContainer"
                         class="p-6 font-mono text-sm h-[600px] overflow-y-auto">
                        @forelse($logs as $log)
                            @php
                                $levelColors = [
                                    'info' => 'text-blue-400',
                                    'success' => 'text-green-400',
                                    'warning' => 'text-yellow-400',
                                    'error' => 'text-red-400',
                                ];
                                $color = $levelColors[$log['level']] ?? 'text-white/60';
                            @endphp
                            <div class="log-entry level-{{ $log['level'] }} py-2 border-b border-white/5 hover:bg-white/5">
                                <div class="flex items-start gap-3">
                                    <span class="text-white/40 text-xs mt-0.5">
                                        {{ \Carbon\Carbon::parse($log['timestamp'])->format('H:i:s') }}
                                    </span>
                                    <span class="{{ $color }} font-bold uppercase text-xs mt-0.5">
                                        [{{ $log['level'] }}]
                                    </span>
                                    <span class="text-white/80 flex-1">
                                        {{ $log['message'] }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="text-white/40 text-center py-12">
                                <i class="fas fa-inbox text-4xl mb-4 block"></i>
                                No logs available yet
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let autoRefreshInterval = null;
let logs = @json($logs);

// Refresh logs
function refreshLogs() {
    console.log('Refreshing logs...');

    // TODO: เรียก AJAX เพื่อดึง logs ใหม่
    // ตอนนี้แค่แสดง message
    const lastUpdated = document.getElementById('lastUpdated');
    const now = new Date();
    lastUpdated.textContent = now.toLocaleTimeString('th-TH');

    // In production, use:
    // fetch('/api/deployments/{{ $deployment->id }}/logs')
    //     .then(response => response.json())
    //     .then(data => {
    //         updateLogsDisplay(data.logs);
    //     });
}

// Toggle auto-refresh
function toggleAutoRefresh() {
    const checkbox = document.getElementById('autoRefresh');

    if (checkbox.checked) {
        autoRefreshInterval = setInterval(refreshLogs, 5000); // Every 5 seconds
        console.log('Auto-refresh enabled');
    } else {
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
            autoRefreshInterval = null;
            console.log('Auto-refresh disabled');
        }
    }
}

// Clear logs view
function clearLogs() {
    const container = document.getElementById('logsContainer');
    container.innerHTML = `
        <div class="text-white/40 text-center py-12">
            <i class="fas fa-inbox text-4xl mb-4 block"></i>
            Logs cleared. Click Refresh to reload.
        </div>
    `;
}

// Download logs
function downloadLogs() {
    let content = `Deployment Logs: {{ $deployment->name }}\n`;
    content += `Generated: ${new Date().toISOString()}\n`;
    content += `=${'='.repeat(60)}\n\n`;

    logs.forEach(log => {
        const timestamp = new Date(log.timestamp).toISOString();
        content += `[${timestamp}] [${log.level.toUpperCase()}] ${log.message}\n`;
    });

    const blob = new Blob([content], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `deployment-{{ $deployment->id }}-logs-${Date.now()}.txt`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

// Apply filters
function applyFilters() {
    const filters = {
        info: document.getElementById('filter-info').checked,
        success: document.getElementById('filter-success').checked,
        warning: document.getElementById('filter-warning').checked,
        error: document.getElementById('filter-error').checked,
    };

    const entries = document.querySelectorAll('.log-entry');
    entries.forEach(entry => {
        const level = entry.className.match(/level-(\w+)/)?.[1];
        if (level && filters[level] !== undefined) {
            entry.style.display = filters[level] ? 'block' : 'none';
        }
    });
}

// Auto-scroll to bottom
function scrollToBottom() {
    const container = document.getElementById('logsContainer');
    container.scrollTop = container.scrollHeight;
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    scrollToBottom();
});

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
    }
});
</script>
@endpush
@endsection
