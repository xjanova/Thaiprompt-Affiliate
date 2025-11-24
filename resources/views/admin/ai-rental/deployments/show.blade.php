@extends('layouts.admin')

@section('title', 'Deployment Details')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-purple-900 to-gray-900 py-8 px-4">
    <div class="max-w-7xl mx-auto">
        {{-- Header --}}
        <div class="mb-8">
            <a href="{{ route('admin.ai-rental.deployments.index') }}"
               class="inline-flex items-center text-cyan-400 hover:text-cyan-300 mb-4 transition">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Deployments
            </a>
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
                <div>
                    <h1 class="text-4xl font-bold text-white mb-2 flex items-center gap-3">
                        <i class="fas fa-server text-cyan-400"></i>
                        {{ $deployment->name }}
                    </h1>
                    <p class="text-white/60">Deployment Details & Monitoring</p>
                </div>

                {{-- Status Badge --}}
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
                <div class="px-6 py-3 bg-gradient-to-r {{ $color }} text-white rounded-xl font-bold text-lg uppercase shadow-lg">
                    <i class="fas fa-circle mr-2 animate-pulse"></i>
                    {{ $deployment->status }}
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Left Column: Info & Stats --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Actions --}}
                <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                    <h2 class="text-xl font-bold text-white mb-4">Actions</h2>
                    <div class="flex flex-wrap gap-3">
                        @if($metrics['can_start'])
                            <form action="{{ route('admin.ai-rental.deployments.start', $deployment) }}" method="POST" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                        class="px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white rounded-xl font-semibold transition shadow-lg hover:shadow-green-500/50">
                                    <i class="fas fa-play mr-2"></i>Start
                                </button>
                            </form>
                        @endif

                        @if($metrics['can_stop'])
                            <form action="{{ route('admin.ai-rental.deployments.stop', $deployment) }}" method="POST" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                        class="px-6 py-3 bg-gradient-to-r from-yellow-500 to-orange-600 hover:from-yellow-600 hover:to-orange-700 text-white rounded-xl font-semibold transition shadow-lg hover:shadow-yellow-500/50">
                                    <i class="fas fa-stop mr-2"></i>Stop
                                </button>
                            </form>
                        @endif

                        @if($metrics['can_restart'])
                            <form action="{{ route('admin.ai-rental.deployments.restart', $deployment) }}" method="POST" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                        class="px-6 py-3 bg-gradient-to-r from-blue-500 to-cyan-600 hover:from-blue-600 hover:to-cyan-700 text-white rounded-xl font-semibold transition shadow-lg hover:shadow-blue-500/50">
                                    <i class="fas fa-redo mr-2"></i>Restart
                                </button>
                            </form>
                        @endif

                        <a href="{{ route('admin.ai-rental.deployments.logs', $deployment) }}"
                           class="px-6 py-3 bg-white/10 hover:bg-white/20 text-white rounded-xl font-semibold transition border border-white/30">
                            <i class="fas fa-terminal mr-2"></i>View Logs
                        </a>

                        @if($deployment->endpoint_url)
                            <a href="{{ $deployment->endpoint_url }}"
                               target="_blank"
                               class="px-6 py-3 bg-white/10 hover:bg-white/20 text-white rounded-xl font-semibold transition border border-white/30">
                                <i class="fas fa-external-link-alt mr-2"></i>Open Endpoint
                            </a>
                        @endif
                    </div>
                </div>

                {{-- Metrics --}}
                <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                    <h2 class="text-xl font-bold text-white mb-4">Metrics</h2>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-white/5 rounded-xl p-4 text-center">
                            <div class="text-cyan-400 text-3xl mb-2">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="text-white font-bold text-2xl mb-1">{{ $metrics['uptime_minutes'] }}</div>
                            <div class="text-white/60 text-sm">Minutes Uptime</div>
                        </div>
                        <div class="bg-white/5 rounded-xl p-4 text-center">
                            <div class="text-green-400 text-3xl mb-2">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <div class="text-white font-bold text-2xl mb-1">${{ number_format($metrics['estimated_cost'], 2) }}</div>
                            <div class="text-white/60 text-sm">Est. Cost</div>
                        </div>
                        <div class="bg-white/5 rounded-xl p-4 text-center">
                            <div class="text-yellow-400 text-3xl mb-2">
                                <i class="fas fa-tachometer-alt"></i>
                            </div>
                            <div class="text-white font-bold text-2xl mb-1">${{ number_format($metrics['cost_per_hour'], 2) }}</div>
                            <div class="text-white/60 text-sm">Per Hour</div>
                        </div>
                        <div class="bg-white/5 rounded-xl p-4 text-center">
                            <div class="text-purple-400 text-3xl mb-2">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="text-white font-bold text-2xl mb-1">{{ number_format($deployment->total_requests) }}</div>
                            <div class="text-white/60 text-sm">Requests</div>
                        </div>
                    </div>
                </div>

                {{-- Details --}}
                <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                    <h2 class="text-xl font-bold text-white mb-4">Deployment Details</h2>
                    <div class="space-y-4">
                        <div class="flex justify-between py-3 border-b border-white/10">
                            <span class="text-white/60">AI Model</span>
                            <span class="text-white font-semibold">{{ $deployment->model->name }}</span>
                        </div>
                        <div class="flex justify-between py-3 border-b border-white/10">
                            <span class="text-white/60">Model Size</span>
                            <span class="text-white font-semibold">{{ $deployment->model->size }}</span>
                        </div>
                        <div class="flex justify-between py-3 border-b border-white/10">
                            <span class="text-white/60">Instance Type</span>
                            <span class="text-white font-semibold">{{ $deployment->instance_type }}</span>
                        </div>
                        <div class="flex justify-between py-3 border-b border-white/10">
                            <span class="text-white/60">Cloud Provider</span>
                            <span class="text-white font-semibold">{{ $deployment->cloudConfig->cloudProvider->name }}</span>
                        </div>
                        <div class="flex justify-between py-3 border-b border-white/10">
                            <span class="text-white/60">Configuration</span>
                            <span class="text-white font-semibold">{{ $deployment->cloudConfig->config_name }}</span>
                        </div>
                        @if($deployment->instance_id)
                            <div class="flex justify-between py-3 border-b border-white/10">
                                <span class="text-white/60">Instance ID</span>
                                <span class="text-white font-mono text-sm">{{ $deployment->instance_id }}</span>
                            </div>
                        @endif
                        @if($deployment->endpoint_url)
                            <div class="flex justify-between py-3 border-b border-white/10">
                                <span class="text-white/60">Endpoint URL</span>
                                <a href="{{ $deployment->endpoint_url }}"
                                   target="_blank"
                                   class="text-cyan-400 hover:text-cyan-300 font-mono text-sm hover:underline">
                                    {{ Str::limit($deployment->endpoint_url, 50) }}
                                    <i class="fas fa-external-link-alt ml-1 text-xs"></i>
                                </a>
                            </div>
                        @endif
                        <div class="flex justify-between py-3 border-b border-white/10">
                            <span class="text-white/60">Created</span>
                            <span class="text-white font-semibold">{{ $deployment->created_at->format('Y-m-d H:i:s') }}</span>
                        </div>
                        @if($deployment->deployed_at)
                            <div class="flex justify-between py-3 border-b border-white/10">
                                <span class="text-white/60">Deployed At</span>
                                <span class="text-white font-semibold">{{ $deployment->deployed_at->format('Y-m-d H:i:s') }}</span>
                            </div>
                        @endif
                        @if($deployment->stopped_at)
                            <div class="flex justify-between py-3 border-b border-white/10">
                                <span class="text-white/60">Stopped At</span>
                                <span class="text-white font-semibold">{{ $deployment->stopped_at->format('Y-m-d H:i:s') }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- API Usage Example --}}
                @if($deployment->endpoint_url && $deployment->status === 'running')
                    <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                        <h2 class="text-xl font-bold text-white mb-4">API Usage Example</h2>
                        <div class="bg-black/50 rounded-xl p-4 overflow-x-auto">
                            <pre class="text-green-400 text-sm font-mono"><code>curl -X POST {{ $deployment->endpoint_url }}/generate \
  -H "Content-Type: application/json" \
  -d '{
    "prompt": "Hello, how are you?",
    "max_tokens": 100,
    "temperature": 0.7
  }'</code></pre>
                        </div>
                        <button onclick="copyToClipboard()"
                                class="mt-3 px-4 py-2 bg-cyan-500 hover:bg-cyan-600 text-white rounded-lg text-sm transition">
                            <i class="fas fa-copy mr-2"></i>Copy
                        </button>
                    </div>
                @endif
            </div>

            {{-- Right Column: Status & Timeline --}}
            <div class="space-y-6">
                {{-- Quick Stats --}}
                <div class="bg-gradient-to-br from-cyan-500/20 to-blue-600/20 backdrop-blur-lg rounded-2xl p-6 border border-cyan-500/30">
                    <h2 class="text-xl font-bold text-white mb-4">Quick Stats</h2>
                    <div class="space-y-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-white/60">Total Hours</span>
                            <span class="text-white font-bold">{{ number_format($deployment->total_hours, 2) }}h</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-white/60">Total Cost</span>
                            <span class="text-white font-bold">${{ number_format($deployment->total_cost, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-white/60">Total Requests</span>
                            <span class="text-white font-bold">{{ number_format($deployment->total_requests) }}</span>
                        </div>
                    </div>
                </div>

                {{-- Recent Logs --}}
                <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold text-white">Recent Logs</h2>
                        <a href="{{ route('admin.ai-rental.deployments.logs', $deployment) }}"
                           class="text-cyan-400 hover:text-cyan-300 text-sm">
                            View All <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                    <div class="space-y-2 max-h-96 overflow-y-auto">
                        @forelse(array_slice($deployment->logs ?? [], -10) as $log)
                            @php
                                $levelColors = [
                                    'info' => 'text-blue-400',
                                    'success' => 'text-green-400',
                                    'warning' => 'text-yellow-400',
                                    'error' => 'text-red-400',
                                ];
                                $color = $levelColors[$log['level']] ?? 'text-white/60';
                            @endphp
                            <div class="bg-black/30 rounded-lg p-3">
                                <div class="flex items-start gap-2">
                                    <i class="fas fa-circle {{ $color }} text-xs mt-1"></i>
                                    <div class="flex-1">
                                        <div class="text-white/80 text-sm">{{ $log['message'] }}</div>
                                        <div class="text-white/40 text-xs mt-1">
                                            {{ \Carbon\Carbon::parse($log['timestamp'])->diffForHumans() }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-white/40 text-sm text-center py-4">
                                No logs available
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Delete --}}
                @if($deployment->status !== 'running')
                    <div class="bg-red-500/10 backdrop-blur-lg rounded-2xl p-6 border border-red-500/30">
                        <h2 class="text-xl font-bold text-red-400 mb-3">Danger Zone</h2>
                        <p class="text-white/60 text-sm mb-4">
                            Delete this deployment permanently. This action cannot be undone.
                        </p>
                        <form action="{{ route('admin.ai-rental.deployments.destroy', $deployment) }}"
                              method="POST"
                              onsubmit="return confirm('Are you sure you want to delete this deployment?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="w-full px-6 py-3 bg-red-500 hover:bg-red-600 text-white rounded-xl font-semibold transition">
                                <i class="fas fa-trash mr-2"></i>Delete Deployment
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function copyToClipboard() {
    const code = document.querySelector('pre code').textContent;
    navigator.clipboard.writeText(code).then(() => {
        alert('Copied to clipboard!');
    });
}
</script>
@endpush
@endsection
