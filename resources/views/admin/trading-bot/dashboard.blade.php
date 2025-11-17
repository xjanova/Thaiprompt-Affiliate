@extends('layouts.admin-v3')

@section('title', 'Trading Bot Admin Dashboard')

@section('content')
<div class="container-fluid px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">🤖 Trading Bot Management</h1>
            <p class="text-gray-600 dark:text-gray-400 dark:text-gray-400 mt-1">ระบบจัดการบอทเทรดอัตโนมัติ</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('admin.trading-bot.arbitrage-monitor') }}" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded-xl transition-colors">
                💹 Arbitrage Monitor
            </a>
            <a href="{{ route('admin.trading-bot.packages.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-xl transition-colors">
                + New Package
            </a>
        </div>
    </div>

    <!-- System Health -->
    <div class="mb-8 p-6 rounded-xl {{ $systemHealth['status'] === 'good' ? 'bg-green-50 dark:bg-green-900/20' : ($systemHealth['status'] === 'warning' ? 'bg-yellow-50 dark:bg-yellow-900/20' : 'bg-red-50 dark:bg-red-900/20') }}">
        <div class="flex items-center">
            <div class="text-3xl mr-4">
                @if($systemHealth['status'] === 'good')
                    ✅
                @elseif($systemHealth['status'] === 'warning')
                    ⚠️
                @else
                    ❌
                @endif
            </div>
            <div>
                <h3 class="font-bold text-lg {{ $systemHealth['status'] === 'good' ? 'text-green-800 dark:text-green-300' : ($systemHealth['status'] === 'warning' ? 'text-yellow-800 dark:text-yellow-300' : 'text-red-800 dark:text-red-300') }}">
                    System Health: {{ ucfirst($systemHealth['status']) }}
                </h3>
                <p class="text-sm {{ $systemHealth['status'] === 'good' ? 'text-green-700 dark:text-green-400' : ($systemHealth['status'] === 'warning' ? 'text-yellow-700 dark:text-yellow-400' : 'text-red-700 dark:text-red-400') }}">
                    Active Bots: {{ $systemHealth['active_bots'] }} | Error Bots: {{ $systemHealth['error_bots'] }} | Active Exchanges: {{ $systemHealth['active_exchanges'] }}
                </p>
            </div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="style="background: var(--arrow-x-primary-gradient)" rounded-xl p-6 text-white shadow-lg">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-blue-100 text-sm mb-1">Total Users</p>
                    <p class="text-3xl font-bold">{{ number_format($stats['total_users']) }}</p>
                </div>
                <div class="text-4xl opacity-50">👥</div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-6 text-white shadow-lg">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-green-100 text-sm mb-1">Active Bots</p>
                    <p class="text-3xl font-bold">{{ number_format($stats['active_bots']) }}</p>
                </div>
                <div class="text-4xl opacity-50">🤖</div>
            </div>
        </div>

        <div class="style="background: var(--arrow-x-accent-gradient)" rounded-xl p-6 text-white shadow-lg">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-purple-100 text-sm mb-1">Active Subscriptions</p>
                    <p class="text-3xl font-bold">{{ number_format($stats['total_subscriptions']) }}</p>
                </div>
                <div class="text-4xl opacity-50">📋</div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-yellow-500 to-orange-600 rounded-xl p-6 text-white shadow-lg">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-yellow-100 text-sm mb-1">Monthly Revenue</p>
                    <p class="text-3xl font-bold">฿{{ number_format($stats['monthly_revenue'], 0) }}</p>
                </div>
                <div class="text-4xl opacity-50">💰</div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Recent Subscriptions -->
        <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden" border border-white/20 dark:border-white/10>
            <div class="p-6 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Recent Subscriptions</h2>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($recentSubscriptions as $subscription)
                <div class="p-4 hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:hover:bg-gray-700">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $subscription->user->name }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">{{ $subscription->package->name }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-green-600">฿{{ number_format($subscription->package->price, 0) }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">{{ $subscription->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                </div>
                @empty
                <div class="p-6 text-center text-gray-500 dark:text-gray-400 dark:text-gray-400">
                    No subscriptions yet
                </div>
                @endforelse
            </div>
            <div class="p-4 bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-900">
                <a href="{{ route('admin.trading-bot.subscriptions.index') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                    View All Subscriptions →
                </a>
            </div>
        </div>

        <!-- Top Performing Bots -->
        <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden" border border-white/20 dark:border-white/10>
            <div class="p-6 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Top Performing Bots</h2>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($topPerformingBots as $bot)
                <div class="p-4 hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:hover:bg-gray-700">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $bot->name }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">{{ $bot->trading_pair }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold {{ $bot->roi >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $bot->roi >= 0 ? '+' : '' }}{{ number_format($bot->roi, 2) }}%
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">ROI</p>
                        </div>
                    </div>
                </div>
                @empty
                <div class="p-6 text-center text-gray-500 dark:text-gray-400 dark:text-gray-400">
                    No bots yet
                </div>
                @endforelse
            </div>
            <div class="p-4 bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-900">
                <a href="{{ route('admin.trading-bot.bots.index') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                    View All Bots →
                </a>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <a href="{{ route('admin.trading-bot.packages.index') }}" class="glass-fusion dark:bg-gray-800 rounded-xl p-6 shadow-lg hover:shadow-xl transition-shadow">
            <div class="text-3xl mb-3">📦</div>
            <h3 class="font-bold text-lg text-gray-900 dark:text-white mb-2">Packages</h3>
            <p class="text-gray-600 dark:text-gray-400 dark:text-gray-300 text-sm">Manage subscription packages</p>
        </a>

        <a href="{{ route('admin.trading-bot.exchanges.index') }}" class="glass-fusion dark:bg-gray-800 rounded-xl p-6 shadow-lg hover:shadow-xl transition-shadow">
            <div class="text-3xl mb-3">🌐</div>
            <h3 class="font-bold text-lg text-gray-900 dark:text-white mb-2">Exchanges</h3>
            <p class="text-gray-600 dark:text-gray-400 dark:text-gray-300 text-sm">Manage supported exchanges</p>
        </a>

        <a href="{{ route('admin.trading-bot.analytics') }}" class="glass-fusion dark:bg-gray-800 rounded-xl p-6 shadow-lg hover:shadow-xl transition-shadow">
            <div class="text-3xl mb-3">📊</div>
            <h3 class="font-bold text-lg text-gray-900 dark:text-white mb-2">Analytics</h3>
            <p class="text-gray-600 dark:text-gray-400 dark:text-gray-300 text-sm">View detailed analytics</p>
        </a>

        <a href="{{ route('admin.trading-bot.settings') }}" class="glass-fusion dark:bg-gray-800 rounded-xl p-6 shadow-lg hover:shadow-xl transition-shadow">
            <div class="text-3xl mb-3">⚙️</div>
            <h3 class="font-bold text-lg text-gray-900 dark:text-white mb-2">Settings</h3>
            <p class="text-gray-600 dark:text-gray-400 dark:text-gray-300 text-sm">System configuration</p>
        </a>
    </div>
</div>
@endsection
