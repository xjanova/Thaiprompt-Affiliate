@extends('layouts.admin')

@section('title', 'Investment Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 rounded-2xl shadow-2xl p-8 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-40 h-40 bg-white opacity-10 rounded-full"></div>
        <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-32 h-32 bg-white opacity-10 rounded-full"></div>
        <div class="relative z-10">
            <h1 class="text-3xl font-bold mb-2">Investment Dashboard</h1>
            <p class="text-indigo-100">Overview of all investment activities - {{ now()->format('d/m/Y') }}</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Active Positions -->
        <div class="relative overflow-hidden bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition duration-300">
            <div class="absolute top-0 right-0 -mt-4 -mr-4">
                <div class="w-24 h-24 bg-white opacity-10 rounded-full"></div>
            </div>
            <div class="relative z-10">
                <div class="text-5xl mb-4">💼</div>
                <p class="text-white text-opacity-80 text-sm mb-1">Active Positions</p>
                <p class="text-4xl font-bold">{{ number_format($stats['total_active_positions']) }}</p>
            </div>
        </div>

        <!-- Total Staked Amount -->
        <div class="relative overflow-hidden bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition duration-300">
            <div class="absolute top-0 right-0 -mt-4 -mr-4">
                <div class="w-24 h-24 bg-white opacity-10 rounded-full"></div>
            </div>
            <div class="relative z-10">
                <div class="text-5xl mb-4">💰</div>
                <p class="text-white text-opacity-80 text-sm mb-1">Total Staked</p>
                <p class="text-4xl font-bold">฿{{ number_format($stats['total_staked_amount'], 0) }}</p>
            </div>
        </div>

        <!-- Total ROI Distributed -->
        <div class="relative overflow-hidden bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition duration-300">
            <div class="absolute top-0 right-0 -mt-4 -mr-4">
                <div class="w-24 h-24 bg-white opacity-10 rounded-full"></div>
            </div>
            <div class="relative z-10">
                <div class="text-5xl mb-4">📈</div>
                <p class="text-white text-opacity-80 text-sm mb-1">ROI Distributed</p>
                <p class="text-4xl font-bold">฿{{ number_format($stats['total_roi_distributed'], 0) }}</p>
            </div>
        </div>

        <!-- Total Investors -->
        <div class="relative overflow-hidden bg-gradient-to-br from-orange-500 to-red-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition duration-300">
            <div class="absolute top-0 right-0 -mt-4 -mr-4">
                <div class="w-24 h-24 bg-white opacity-10 rounded-full"></div>
            </div>
            <div class="relative z-10">
                <div class="text-5xl mb-4">👥</div>
                <p class="text-white text-opacity-80 text-sm mb-1">Total Investors</p>
                <p class="text-4xl font-bold">{{ number_format($stats['total_investors']) }}</p>
            </div>
        </div>
    </div>

    <!-- Additional Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Pending ROI -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Pending ROI</p>
                    <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">฿{{ number_format($stats['total_roi_pending'], 2) }}</p>
                </div>
                <div class="text-4xl">⏳</div>
            </div>
        </div>

        <!-- Matured Today -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Matured Today</p>
                    <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ number_format($stats['positions_matured_today']) }}</p>
                </div>
                <div class="text-4xl">🎯</div>
            </div>
        </div>

        <!-- Distributions Today -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Distributions Today</p>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($stats['distributions_today']) }}</p>
                </div>
                <div class="text-4xl">💸</div>
            </div>
        </div>
    </div>

    <!-- Pending Positions and Recent Distributions -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Pending Positions -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">Pending Positions</h3>
                <a href="{{ route('admin.investments.positions.index', ['status' => 'pending']) }}" class="text-sm text-indigo-600 hover:text-indigo-700 font-semibold">
                    View All →
                </a>
            </div>
            <div class="space-y-4">
                @forelse($pendingPositions as $position)
                    <div class="flex items-start gap-4 p-4 bg-gradient-to-r from-gray-50 to-white dark:from-slate-700 dark:to-slate-800 rounded-xl hover:shadow-md transition border border-gray-100 dark:border-slate-600">
                        <img src="{{ $position->user->profile_picture_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($position->user->name) }}"
                             alt="{{ $position->user->name }}"
                             class="w-12 h-12 rounded-full object-cover flex-shrink-0 ring-2 ring-gray-200">
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-gray-900 dark:text-white truncate">{{ $position->user->name }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ $position->investmentPlan->name ?? 'N/A' }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                {{ $position->created_at->diffForHumans() }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-lg text-green-600 dark:text-green-400">฿{{ number_format($position->amount, 0) }}</p>
                            <div class="flex gap-2 mt-2">
                                <form action="{{ route('admin.investments.positions.approve', $position) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-green-600 hover:text-green-900 text-xs" title="Approve">
                                        ✅
                                    </button>
                                </form>
                                <a href="{{ route('admin.investments.positions.show', $position) }}" class="text-indigo-600 hover:text-indigo-900 text-xs" title="View">
                                    👁️
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-gray-500 py-8">
                        <span class="text-4xl mb-2 block">📭</span>
                        <p>No pending positions</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Recent Distributions -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">Recent Distributions</h3>
                <a href="{{ route('admin.investments.distributions.index') }}" class="text-sm text-indigo-600 hover:text-indigo-700 font-semibold">
                    View All →
                </a>
            </div>
            <div class="space-y-4">
                @forelse($recentDistributions as $distribution)
                    <div class="flex items-start gap-3 p-3 hover:bg-gray-50 dark:hover:bg-slate-700 rounded-lg transition">
                        <img src="{{ $distribution->user->profile_picture_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($distribution->user->name) }}"
                             alt="{{ $distribution->user->name }}"
                             class="w-10 h-10 rounded-full object-cover flex-shrink-0 ring-2 ring-gray-200">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                {{ $distribution->user->name }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                ROI: <span class="font-semibold text-green-600 dark:text-green-400">฿{{ number_format($distribution->roi_amount, 2) }}</span>
                            </p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                {{ $distribution->created_at->diffForHumans() }}
                            </p>
                        </div>
                        <span class="px-2 py-1 rounded-full text-xs font-semibold flex-shrink-0
                            {{ $distribution->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                            {{ $distribution->status === 'processing' ? 'bg-blue-100 text-blue-800' : '' }}
                            {{ $distribution->status === 'completed' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $distribution->status === 'failed' ? 'bg-red-100 text-red-800' : '' }}">
                            {{ ucfirst($distribution->status) }}
                        </span>
                    </div>
                @empty
                    <div class="text-center text-gray-500 py-8">
                        <span class="text-4xl mb-2 block">📭</span>
                        <p>No recent distributions</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-2xl shadow-xl p-6 text-white">
        <h3 class="text-xl font-bold mb-4">Quick Actions</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="{{ route('admin.investments.plans.index') }}" class="bg-white bg-opacity-20 hover:bg-opacity-30 rounded-xl p-4 text-center transition">
                <div class="text-3xl mb-2">📋</div>
                <p class="text-sm font-semibold">Investment Plans</p>
            </a>
            <a href="{{ route('admin.investments.positions.index', ['status' => 'pending']) }}" class="bg-white bg-opacity-20 hover:bg-opacity-30 rounded-xl p-4 text-center transition">
                <div class="text-3xl mb-2">✅</div>
                <p class="text-sm font-semibold">Approve Positions</p>
            </a>
            <form action="{{ route('admin.investments.distributions.trigger') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="w-full bg-white bg-opacity-20 hover:bg-opacity-30 rounded-xl p-4 text-center transition">
                    <div class="text-3xl mb-2">💸</div>
                    <p class="text-sm font-semibold">Trigger ROI Distribution</p>
                </button>
            </form>
            <form action="{{ route('admin.investments.process-mature') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="w-full bg-white bg-opacity-20 hover:bg-opacity-30 rounded-xl p-4 text-center transition">
                    <div class="text-3xl mb-2">🎯</div>
                    <p class="text-sm font-semibold">Process Mature Positions</p>
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
