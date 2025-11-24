@extends('layouts.admin')

@section('title', 'My Deployments')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-purple-900 to-gray-900 py-8 px-4">
    <div class="max-w-7xl mx-auto">
        {{-- Header --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
            <div>
                <h1 class="text-4xl font-bold text-white mb-2 flex items-center gap-3">
                    <i class="fas fa-rocket text-cyan-400"></i>
                    My Deployments
                </h1>
                <p class="text-white/60">จัดการ AI Model Deployments ของคุณ</p>
            </div>
            <a href="{{ route('admin.ai-rental.deployments.create') }}"
               class="px-6 py-3 bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-600 hover:to-blue-700 text-white rounded-xl font-semibold transition-all duration-300 shadow-lg hover:shadow-cyan-500/50 flex items-center gap-2">
                <i class="fas fa-plus"></i>
                New Deployment
            </a>
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            {{-- Total --}}
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20 hover:border-cyan-400/50 transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-xl flex items-center justify-center">
                        <i class="fas fa-server text-white text-xl"></i>
                    </div>
                    <span class="text-3xl font-bold text-white">{{ $stats['total'] }}</span>
                </div>
                <div class="text-white/60 text-sm">Total Deployments</div>
            </div>

            {{-- Running --}}
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20 hover:border-green-400/50 transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-green-400 to-emerald-500 rounded-xl flex items-center justify-center">
                        <i class="fas fa-play-circle text-white text-xl"></i>
                    </div>
                    <span class="text-3xl font-bold text-white">{{ $stats['running'] }}</span>
                </div>
                <div class="text-white/60 text-sm">Running</div>
            </div>

            {{-- Total Hours --}}
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20 hover:border-purple-400/50 transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-purple-400 to-pink-500 rounded-xl flex items-center justify-center">
                        <i class="fas fa-clock text-white text-xl"></i>
                    </div>
                    <span class="text-3xl font-bold text-white">{{ number_format($stats['total_hours'], 1) }}</span>
                </div>
                <div class="text-white/60 text-sm">Total Hours</div>
            </div>

            {{-- Total Cost --}}
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20 hover:border-yellow-400/50 transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-xl flex items-center justify-center">
                        <i class="fas fa-dollar-sign text-white text-xl"></i>
                    </div>
                    <span class="text-3xl font-bold text-white">${{ number_format($stats['total_cost'], 2) }}</span>
                </div>
                <div class="text-white/60 text-sm">Total Cost</div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20 mb-8">
            <form method="GET" action="{{ route('admin.ai-rental.deployments.index') }}" class="flex flex-col md:flex-row gap-4">
                {{-- Status Filter --}}
                <div class="flex-1">
                    <label class="block text-white/80 text-sm mb-2">Status</label>
                    <select name="status"
                            class="w-full px-4 py-2 bg-white/10 border border-white/30 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-cyan-400">
                        <option value="">All Status</option>
                        <option value="running" {{ request('status') === 'running' ? 'selected' : '' }}>Running</option>
                        <option value="stopped" {{ request('status') === 'stopped' ? 'selected' : '' }}>Stopped</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                    </select>
                </div>

                {{-- Model Filter --}}
                <div class="flex-1">
                    <label class="block text-white/80 text-sm mb-2">Model</label>
                    <select name="model"
                            class="w-full px-4 py-2 bg-white/10 border border-white/30 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-cyan-400">
                        <option value="">All Models</option>
                        @foreach($models as $model)
                            <option value="{{ $model->id }}" {{ request('model') == $model->id ? 'selected' : '' }}>
                                {{ $model->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Config Filter --}}
                <div class="flex-1">
                    <label class="block text-white/80 text-sm mb-2">Configuration</label>
                    <select name="config"
                            class="w-full px-4 py-2 bg-white/10 border border-white/30 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-cyan-400">
                        <option value="">All Configs</option>
                        @foreach($configs as $config)
                            <option value="{{ $config->id }}" {{ request('config') == $config->id ? 'selected' : '' }}>
                                {{ $config->config_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Submit --}}
                <div class="flex items-end gap-2">
                    <button type="submit"
                            class="px-6 py-2 bg-cyan-500 hover:bg-cyan-600 text-white rounded-xl transition">
                        <i class="fas fa-filter mr-2"></i>Filter
                    </button>
                    <a href="{{ route('admin.ai-rental.deployments.index') }}"
                       class="px-6 py-2 bg-white/10 hover:bg-white/20 text-white rounded-xl transition border border-white/30">
                        <i class="fas fa-redo"></i>
                    </a>
                </div>
            </form>
        </div>

        {{-- Deployments List --}}
        @if($deployments->isEmpty())
            {{-- Empty State --}}
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-12 border border-white/20 text-center">
                <div class="w-24 h-24 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-rocket text-white text-4xl"></i>
                </div>
                <h3 class="text-2xl font-bold text-white mb-3">ยังไม่มี Deployment</h3>
                <p class="text-white/60 mb-6 max-w-md mx-auto">
                    เริ่มต้น Deploy AI Models บน Cloud GPU เพื่อใช้งาน inference หรือ fine-tuning
                </p>
                <a href="{{ route('admin.ai-rental.deployments.create') }}"
                   class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-600 hover:to-blue-700 text-white rounded-xl font-semibold transition">
                    <i class="fas fa-plus"></i>
                    Create First Deployment
                </a>
            </div>
        @else
            {{-- Deployments Cards --}}
            <div class="space-y-4">
                @foreach($deployments as $deployment)
                    <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20 hover:border-cyan-400/50 transition-all duration-300">
                        <div class="flex flex-col lg:flex-row gap-6">
                            {{-- Left: Info --}}
                            <div class="flex-1">
                                <div class="flex items-start gap-4 mb-4">
                                    <div class="w-16 h-16 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-xl flex items-center justify-center flex-shrink-0">
                                        <i class="fas fa-microchip text-white text-2xl"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="text-xl font-bold text-white mb-1">{{ $deployment->name }}</h3>
                                        <div class="flex flex-wrap gap-2 mb-2">
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
                                            <span class="px-3 py-1 bg-gradient-to-r {{ $color }} text-white text-xs font-bold rounded-full uppercase">
                                                {{ $deployment->status }}
                                            </span>
                                        </div>
                                        <div class="text-white/60 text-sm space-y-1">
                                            <div><i class="fas fa-brain w-4"></i> {{ $deployment->model->name }}</div>
                                            <div><i class="fas fa-server w-4"></i> {{ $deployment->instance_type }}</div>
                                            <div><i class="fas fa-cloud w-4"></i> {{ $deployment->cloudConfig->cloudProvider->name }}</div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Stats --}}
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    <div class="bg-white/5 rounded-xl p-3">
                                        <div class="text-white/60 text-xs mb-1">Uptime</div>
                                        <div class="text-white font-bold">{{ $deployment->getUptimeMinutes() }}m</div>
                                    </div>
                                    <div class="bg-white/5 rounded-xl p-3">
                                        <div class="text-white/60 text-xs mb-1">Cost</div>
                                        <div class="text-white font-bold">${{ number_format($deployment->total_cost, 2) }}</div>
                                    </div>
                                    <div class="bg-white/5 rounded-xl p-3">
                                        <div class="text-white/60 text-xs mb-1">Requests</div>
                                        <div class="text-white font-bold">{{ number_format($deployment->total_requests) }}</div>
                                    </div>
                                    <div class="bg-white/5 rounded-xl p-3">
                                        <div class="text-white/60 text-xs mb-1">Created</div>
                                        <div class="text-white font-bold">{{ $deployment->created_at->diffForHumans() }}</div>
                                    </div>
                                </div>
                            </div>

                            {{-- Right: Actions --}}
                            <div class="flex flex-col justify-center gap-2 lg:w-48">
                                <a href="{{ route('admin.ai-rental.deployments.show', $deployment) }}"
                                   class="px-4 py-2 bg-cyan-500 hover:bg-cyan-600 text-white rounded-xl text-center transition font-semibold">
                                    <i class="fas fa-eye mr-2"></i>Details
                                </a>

                                @if($deployment->status === 'running')
                                    <form action="{{ route('admin.ai-rental.deployments.stop', $deployment) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                class="w-full px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-xl transition font-semibold">
                                            <i class="fas fa-stop mr-2"></i>Stop
                                        </button>
                                    </form>
                                @elseif(in_array($deployment->status, ['stopped', 'failed']))
                                    <form action="{{ route('admin.ai-rental.deployments.start', $deployment) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                class="w-full px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-xl transition font-semibold">
                                            <i class="fas fa-play mr-2"></i>Start
                                        </button>
                                    </form>
                                @endif

                                <a href="{{ route('admin.ai-rental.deployments.logs', $deployment) }}"
                                   class="px-4 py-2 bg-white/10 hover:bg-white/20 text-white rounded-xl text-center transition border border-white/30">
                                    <i class="fas fa-terminal mr-2"></i>Logs
                                </a>

                                @if($deployment->status !== 'running')
                                    <form action="{{ route('admin.ai-rental.deployments.destroy', $deployment) }}"
                                          method="POST"
                                          onsubmit="return confirm('ต้องการลบ Deployment นี้?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="w-full px-4 py-2 bg-red-500/20 hover:bg-red-500/30 text-red-400 rounded-xl transition border border-red-500/30">
                                            <i class="fas fa-trash mr-2"></i>Delete
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="mt-8">
                {{ $deployments->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
