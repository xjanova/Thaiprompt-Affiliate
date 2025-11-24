@extends('layouts.admin')

@section('title', 'AI Rental Analytics')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-purple-900 to-gray-900 py-8 px-4">
    <div class="max-w-7xl mx-auto">
        {{-- Header --}}
        <div class="mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-4xl font-bold text-white mb-2 flex items-center gap-3">
                        <i class="fas fa-chart-line text-cyan-400"></i>
                        Analytics Dashboard
                    </h1>
                    <p class="text-white/60">Usage Statistics & Cost Analysis</p>
                </div>
                <div class="flex gap-3">
                    <select class="px-4 py-2 bg-white/10 border border-white/30 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-cyan-400">
                        <option>Last 7 Days</option>
                        <option>Last 30 Days</option>
                        <option>Last 90 Days</option>
                        <option>All Time</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Key Metrics --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            {{-- Total Deployments --}}
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20 hover:border-cyan-400/50 transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-xl flex items-center justify-center">
                        <i class="fas fa-server text-white text-xl"></i>
                    </div>
                </div>
                <div class="text-3xl font-bold text-white mb-1">{{ $stats['total_deployments'] }}</div>
                <div class="text-white/60 text-sm">Total Deployments</div>
                <div class="mt-3 flex items-center text-sm">
                    <span class="text-green-400"><i class="fas fa-arrow-up mr-1"></i>12%</span>
                    <span class="text-white/40 ml-2">vs last period</span>
                </div>
            </div>

            {{-- Running Now --}}
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20 hover:border-green-400/50 transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-green-400 to-emerald-500 rounded-xl flex items-center justify-center">
                        <i class="fas fa-play-circle text-white text-xl"></i>
                    </div>
                </div>
                <div class="text-3xl font-bold text-white mb-1">{{ $stats['running_now'] }}</div>
                <div class="text-white/60 text-sm">Running Now</div>
                <div class="mt-3 flex items-center text-sm">
                    <span class="text-cyan-400"><i class="fas fa-circle mr-1 animate-pulse"></i>Live</span>
                </div>
            </div>

            {{-- Total Hours --}}
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20 hover:border-purple-400/50 transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-purple-400 to-pink-500 rounded-xl flex items-center justify-center">
                        <i class="fas fa-clock text-white text-xl"></i>
                    </div>
                </div>
                <div class="text-3xl font-bold text-white mb-1">{{ number_format($stats['total_hours'], 1) }}</div>
                <div class="text-white/60 text-sm">Total Hours</div>
                <div class="mt-3 flex items-center text-sm">
                    <span class="text-green-400"><i class="fas fa-arrow-up mr-1"></i>8%</span>
                    <span class="text-white/40 ml-2">vs last period</span>
                </div>
            </div>

            {{-- Total Cost --}}
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20 hover:border-yellow-400/50 transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-xl flex items-center justify-center">
                        <i class="fas fa-dollar-sign text-white text-xl"></i>
                    </div>
                </div>
                <div class="text-3xl font-bold text-white mb-1">${{ number_format($stats['total_cost'], 2) }}</div>
                <div class="text-white/60 text-sm">Total Cost</div>
                <div class="mt-3 flex items-center text-sm">
                    <span class="text-yellow-400"><i class="fas fa-equals mr-1"></i>Stable</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            {{-- Cost Over Time Chart --}}
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                <h2 class="text-xl font-bold text-white mb-4">Cost Over Time</h2>
                <div class="h-64">
                    <canvas id="costChart"></canvas>
                </div>
            </div>

            {{-- Usage by GPU Type --}}
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                <h2 class="text-xl font-bold text-white mb-4">Usage by GPU Type</h2>
                <div class="h-64">
                    <canvas id="gpuChart"></canvas>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            {{-- Top Models --}}
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                <h2 class="text-xl font-bold text-white mb-4">Top Models</h2>
                <div class="space-y-3">
                    @foreach($stats['top_models'] as $model)
                        <div class="flex items-center justify-between p-3 bg-white/5 rounded-xl">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-brain text-white"></i>
                                </div>
                                <div>
                                    <div class="text-white font-semibold">{{ $model['name'] }}</div>
                                    <div class="text-white/60 text-xs">{{ $model['deployments'] }} deployments</div>
                                </div>
                            </div>
                            <div class="text-white font-bold">{{ $model['hours'] }}h</div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Top Providers --}}
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                <h2 class="text-xl font-bold text-white mb-4">Top Providers</h2>
                <div class="space-y-3">
                    @foreach($stats['top_providers'] as $provider)
                        <div class="flex items-center justify-between p-3 bg-white/5 rounded-xl">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-purple-400 to-pink-500 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-cloud text-white"></i>
                                </div>
                                <div>
                                    <div class="text-white font-semibold">{{ $provider['name'] }}</div>
                                    <div class="text-white/60 text-xs">{{ $provider['deployments'] }} deployments</div>
                                </div>
                            </div>
                            <div class="text-white font-bold">${{ number_format($provider['cost'], 0) }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Recent Activity --}}
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                <h2 class="text-xl font-bold text-white mb-4">Recent Activity</h2>
                <div class="space-y-3">
                    @foreach($stats['recent_activities'] as $activity)
                        <div class="flex items-start gap-3 p-3 bg-white/5 rounded-xl">
                            <div class="w-2 h-2 bg-cyan-400 rounded-full mt-2"></div>
                            <div class="flex-1">
                                <div class="text-white text-sm">{{ $activity['message'] }}</div>
                                <div class="text-white/40 text-xs mt-1">{{ $activity['time'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Detailed Statistics Table --}}
        <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
            <h2 class="text-xl font-bold text-white mb-4">Detailed Statistics</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-white/10">
                            <th class="px-4 py-3 text-left text-white/60 text-sm font-semibold">Deployment</th>
                            <th class="px-4 py-3 text-left text-white/60 text-sm font-semibold">Model</th>
                            <th class="px-4 py-3 text-left text-white/60 text-sm font-semibold">Provider</th>
                            <th class="px-4 py-3 text-right text-white/60 text-sm font-semibold">Hours</th>
                            <th class="px-4 py-3 text-right text-white/60 text-sm font-semibold">Requests</th>
                            <th class="px-4 py-3 text-right text-white/60 text-sm font-semibold">Cost</th>
                            <th class="px-4 py-3 text-right text-white/60 text-sm font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @foreach($deployments as $deployment)
                            <tr class="hover:bg-white/5 transition">
                                <td class="px-4 py-3 text-white">{{ $deployment->name }}</td>
                                <td class="px-4 py-3 text-white/80">{{ $deployment->model->name }}</td>
                                <td class="px-4 py-3 text-white/80">{{ $deployment->cloudConfig->cloudProvider->name }}</td>
                                <td class="px-4 py-3 text-right text-white font-mono">{{ number_format($deployment->total_hours, 2) }}</td>
                                <td class="px-4 py-3 text-right text-white font-mono">{{ number_format($deployment->total_requests) }}</td>
                                <td class="px-4 py-3 text-right text-white font-mono">${{ number_format($deployment->total_cost, 2) }}</td>
                                <td class="px-4 py-3 text-right">
                                    @php
                                        $statusColors = [
                                            'running' => 'green',
                                            'stopped' => 'gray',
                                            'failed' => 'red',
                                        ];
                                        $color = $statusColors[$deployment->status] ?? 'gray';
                                    @endphp
                                    <span class="px-2 py-1 bg-{{ $color }}-500/20 text-{{ $color }}-400 text-xs rounded-full uppercase font-bold">
                                        {{ $deployment->status }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
// Cost Over Time Chart
const costCtx = document.getElementById('costChart').getContext('2d');
new Chart(costCtx, {
    type: 'line',
    data: {
        labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
        datasets: [{
            label: 'Daily Cost ($)',
            data: [12.5, 15.2, 18.7, 22.1, 19.8, 25.3, 28.9],
            borderColor: 'rgb(34, 211, 238)',
            backgroundColor: 'rgba(34, 211, 238, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                labels: {
                    color: 'rgba(255, 255, 255, 0.8)'
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    color: 'rgba(255, 255, 255, 0.6)'
                },
                grid: {
                    color: 'rgba(255, 255, 255, 0.1)'
                }
            },
            x: {
                ticks: {
                    color: 'rgba(255, 255, 255, 0.6)'
                },
                grid: {
                    color: 'rgba(255, 255, 255, 0.1)'
                }
            }
        }
    }
});

// GPU Usage Chart
const gpuCtx = document.getElementById('gpuChart').getContext('2d');
new Chart(gpuCtx, {
    type: 'doughnut',
    data: {
        labels: ['A100-80GB', 'A100-40GB', 'RTX 4090', 'RTX 3090', 'Others'],
        datasets: [{
            data: [35, 25, 20, 15, 5],
            backgroundColor: [
                'rgba(34, 211, 238, 0.8)',
                'rgba(59, 130, 246, 0.8)',
                'rgba(168, 85, 247, 0.8)',
                'rgba(236, 72, 153, 0.8)',
                'rgba(156, 163, 175, 0.8)'
            ],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    color: 'rgba(255, 255, 255, 0.8)',
                    padding: 15
                }
            }
        }
    }
});
</script>
@endpush
@endsection
