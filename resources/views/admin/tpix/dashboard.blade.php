@extends('layouts.admin-v3')

@section('title', 'TPIX Blockchain Dashboard')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="rounded-2xl shadow-2xl p-8 text-white relative overflow-hidden" style="background: var(--arrow-x-primary-gradient)">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-40 h-40 glass-fusion opacity-10 rounded-full"></div>
        <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-32 h-32 glass-fusion opacity-10 rounded-full"></div>
        <div class="relative z-10">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold mb-2 flex items-center gap-3">
                        <span class="text-5xl">⛓️</span>
                        <span>TPIX Blockchain Dashboard</span>
                    </h1>
                    <p class="opacity-80">ภาพรวมระบบ TPIX Blockchain และ Smart Contracts</p>
                </div>
                <div class="text-right">
                    <div class="text-sm opacity-70 mb-1">Current Block</div>
                    <div class="text-3xl font-bold">{{ number_format($stats['current_block']) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Network Quick Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        {{-- Total Supply --}}
        <div class="relative overflow-hidden rounded-xl shadow-lg p-5 text-white" style="background: var(--arrow-x-primary-gradient)">
            <div class="absolute top-0 right-0 -mt-2 -mr-2 w-20 h-20 glass-fusion opacity-10 rounded-full"></div>
            <div class="relative z-10">
                <div class="text-3xl mb-2">💎</div>
                <p class="text-xs opacity-70 mb-1">Total Supply</p>
                <p class="text-2xl font-bold">{{ number_format($stats['total_supply']) }}</p>
                <p class="text-xs opacity-60 mt-1">TPIX</p>
            </div>
        </div>

        {{-- Circulating Supply --}}
        <div class="relative overflow-hidden style="background: var(--arrow-x-accent-gradient)" rounded-xl shadow-lg p-5 text-white">
            <div class="absolute top-0 right-0 -mt-2 -mr-2 w-20 h-20 glass-fusion opacity-10 rounded-full" border border-white/20 dark:border-white/10></div>
            <div class="relative z-10">
                <div class="text-3xl mb-2">🔄</div>
                <p class="text-xs text-purple-100 mb-1">Circulating</p>
                <p class="text-2xl font-bold">{{ number_format($stats['circulating_supply']) }}</p>
                <p class="text-xs text-purple-200 mt-1">TPIX</p>
            </div>
        </div>

        {{-- Total Wallets --}}
        <div class="relative overflow-hidden bg-gradient-to-br from-cyan-500 to-cyan-600 rounded-xl shadow-lg p-5 text-white">
            <div class="absolute top-0 right-0 -mt-2 -mr-2 w-20 h-20 glass-fusion opacity-10 rounded-full" border border-white/20 dark:border-white/10></div>
            <div class="relative z-10">
                <div class="text-3xl mb-2">👛</div>
                <p class="text-xs text-cyan-100 mb-1">Total Wallets</p>
                <p class="text-2xl font-bold">{{ number_format($stats['total_wallets']) }}</p>
                <p class="text-xs text-cyan-200 mt-1">{{ number_format($stats['active_wallets']) }} ใช้งาน</p>
            </div>
        </div>

        {{-- 24h Transactions --}}
        <div class="relative overflow-hidden style="background: var(--arrow-x-success-gradient)" rounded-xl shadow-lg p-5 text-white">
            <div class="absolute top-0 right-0 -mt-2 -mr-2 w-20 h-20 glass-fusion opacity-10 rounded-full" border border-white/20 dark:border-white/10></div>
            <div class="relative z-10">
                <div class="text-3xl mb-2">📊</div>
                <p class="text-xs text-green-100 mb-1">24h Transactions</p>
                <p class="text-2xl font-bold">{{ number_format($stats['transactions_24h']) }}</p>
                <p class="text-xs text-green-200 mt-1">รายการ</p>
            </div>
        </div>

        {{-- 24h Volume --}}
        <div class="relative overflow-hidden bg-gradient-to-br from-yellow-500 to-orange-600 rounded-xl shadow-lg p-5 text-white">
            <div class="absolute top-0 right-0 -mt-2 -mr-2 w-20 h-20 glass-fusion opacity-10 rounded-full" border border-white/20 dark:border-white/10></div>
            <div class="relative z-10">
                <div class="text-3xl mb-2">💰</div>
                <p class="text-xs text-yellow-100 mb-1">24h Volume</p>
                <p class="text-2xl font-bold">{{ number_format($stats['volume_24h'], 2) }}</p>
                <p class="text-xs text-yellow-200 mt-1">TPIX</p>
            </div>
        </div>
    </div>

    {{-- Main Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {{-- Total Balance --}}
        <a href="{{ route('admin.tpix.wallets') }}" class="relative overflow-hidden bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition duration-300 block cursor-pointer">
            <div class="absolute top-0 right-0 -mt-4 -mr-4">
                <div class="w-24 h-24 glass-fusion opacity-10 rounded-full" border border-white/20 dark:border-white/10></div>
            </div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-5xl">💵</div>
                </div>
                <p class="text-white text-opacity-80 text-sm mb-1">Total Balance</p>
                <p class="text-4xl font-bold">{{ number_format($stats['total_balance'], 2) }}</p>
                <p class="text-xs text-white text-opacity-70 mt-2">TPIX ทั้งหมดในระบบ</p>
            </div>
        </a>

        {{-- Pending Deposits --}}
        <a href="{{ route('admin.tpix.transactions', ['type' => 'deposit', 'status' => 'pending']) }}" class="relative overflow-hidden bg-gradient-to-br from-green-500 to-green-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition duration-300 block cursor-pointer">
            <div class="absolute top-0 right-0 -mt-4 -mr-4">
                <div class="w-24 h-24 glass-fusion opacity-10 rounded-full" border border-white/20 dark:border-white/10></div>
            </div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-5xl">📥</div>
                    @if($stats['deposits_pending'] > 0)
                    <span class="px-2 py-1 glass-fusion bg-opacity-20 rounded-xl text-xs font-semibold animate-pulse">
                        รอดำเนินการ
                    </span>
                    @endif
                </div>
                <p class="text-white text-opacity-80 text-sm mb-1">ฝากรอดำเนินการ</p>
                <p class="text-4xl font-bold">{{ number_format($stats['deposits_pending']) }}</p>
                <p class="text-xs text-white text-opacity-70 mt-2">คลิกเพื่อดูรายละเอียด</p>
            </div>
        </a>

        {{-- Pending Withdrawals --}}
        <a href="{{ route('admin.tpix.transactions', ['type' => 'withdrawal', 'status' => 'pending']) }}" class="relative overflow-hidden style="background: var(--arrow-x-warning-gradient)" rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition duration-300 block cursor-pointer">
            <div class="absolute top-0 right-0 -mt-4 -mr-4">
                <div class="w-24 h-24 glass-fusion opacity-10 rounded-full" border border-white/20 dark:border-white/10></div>
            </div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-5xl">📤</div>
                    @if($stats['withdrawals_pending'] > 0)
                    <span class="px-2 py-1 glass-fusion bg-opacity-20 rounded-xl text-xs font-semibold animate-pulse">
                        ต้องตรวจสอบ
                    </span>
                    @endif
                </div>
                <p class="text-white text-opacity-80 text-sm mb-1">ถอนรอดำเนินการ</p>
                <p class="text-4xl font-bold">{{ number_format($stats['withdrawals_pending']) }}</p>
                <p class="text-xs text-white text-opacity-70 mt-2">คลิกเพื่อตรวจสอบ</p>
            </div>
        </a>

        {{-- Network Status --}}
        <a href="{{ route('admin.tpix.network-status') }}" class="relative overflow-hidden bg-gradient-to-br from-pink-500 to-purple-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition duration-300 block cursor-pointer">
            <div class="absolute top-0 right-0 -mt-4 -mr-4">
                <div class="w-24 h-24 glass-fusion opacity-10 rounded-full" border border-white/20 dark:border-white/10></div>
            </div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-5xl">🌐</div>
                    <span class="px-2 py-1 bg-green-500 bg-opacity-80 rounded-xl text-xs font-semibold">
                        ● Online
                    </span>
                </div>
                <p class="text-white text-opacity-80 text-sm mb-1">Network Status</p>
                <p class="text-2xl font-bold">{{ $networkInfo['name'] ?? 'TPIX Network' }}</p>
                <p class="text-xs text-white text-opacity-70 mt-2">Chain ID: {{ $networkInfo['chainId'] ?? 'N/A' }}</p>
            </div>
        </a>
    </div>

    {{-- Charts and Lists --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Volume Chart --}}
        <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-xl p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                <span>📈</span>
                <span>ปริมาณการทำธุรกรรม (30 วันล่าสุด)</span>
            </h2>
            <canvas id="volumeChart" class="w-full" height="80"></canvas>
        </div>

        {{-- Top Wallets --}}
        <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-xl p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <span>🏆</span>
                    <span>Top 10 Wallets</span>
                </h2>
                <a href="{{ route('admin.tpix.wallets', ['sort' => 'balance', 'order' => 'desc']) }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 font-medium">
                    ดูทั้งหมด →
                </a>
            </div>

            <div class="space-y-3">
                @forelse($topWallets as $index => $wallet)
                <div class="flex items-center justify-between p-3 bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-slate-700 rounded-xl hover:bg-gray-100/50 dark:bg-gray-800/50 dark:hover:bg-slate-600 transition">
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center text-white font-bold text-sm">
                            #{{ $index + 1 }}
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $wallet->user->name ?? 'Unknown' }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 font-mono">
                                {{ Str::limit($wallet->address, 20) }}
                            </div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm font-bold text-gray-900 dark:text-white">
                            {{ number_format($wallet->balance, 2) }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">
                            TPIX
                        </div>
                    </div>
                </div>
                @empty
                <div class="text-center py-8 text-gray-500 dark:text-gray-400 dark:text-gray-400">
                    ไม่มีข้อมูล Wallets
                </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Recent Transactions --}}
    <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-xl p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <span>⚡</span>
                <span>ธุรกรรมล่าสุด</span>
            </h2>
            <a href="{{ route('admin.tpix.transactions') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 font-medium">
                ดูทั้งหมด →
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                <thead class="bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-slate-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">
                            ประเภท
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">
                            ผู้ใช้
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">
                            จำนวน
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">
                            สถานะ
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">
                            เวลา
                        </th>
                    </tr>
                </thead>
                <tbody class="glass-fusion dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse($recentTransactions as $tx)
                    <tr class="hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:hover:bg-slate-700 transition">
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $typeIcons = [
                                    'deposit' => '📥',
                                    'withdrawal' => '📤',
                                    'transfer' => '🔄',
                                    'swap' => '💱',
                                    'stake' => '🔒',
                                ];
                                $typeColors = [
                                    'deposit' => 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200',
                                    'withdrawal' => 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200',
                                    'transfer' => 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200',
                                    'swap' => 'bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200',
                                    'stake' => 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200',
                                ];
                            @endphp
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium {{ $typeColors[$tx->type] ?? $typeColors['transfer'] }}">
                                <span>{{ $typeIcons[$tx->type] ?? '💸' }}</span>
                                <span>{{ ucfirst($tx->type) }}</span>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $tx->user->name ?? 'Unknown' }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                {{ $tx->user->email ?? '-' }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-bold text-gray-900 dark:text-white">
                                {{ number_format($tx->amount, 4) }} TPIX
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $statusColors = [
                                    'pending' => 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200',
                                    'confirmed' => 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200',
                                    'failed' => 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200',
                                ];
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$tx->status] ?? $statusColors['pending'] }}">
                                {{ ucfirst($tx->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">
                            {{ $tx->created_at->diffForHumans() }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400 dark:text-gray-400">
                            ไม่มีธุรกรรมล่าสุด
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Volume Chart
    const volumeCtx = document.getElementById('volumeChart');
    if (volumeCtx) {
        const isDark = document.documentElement.classList.contains('dark');
        const textColor = isDark ? '#e2e8f0' : '#1e293b';
        const gridColor = isDark ? '#475569' : '#e2e8f0';

        new Chart(volumeCtx, {
            type: 'line',
            data: {
                labels: {!! json_encode($volumeData->pluck('date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('d/m'))) !!},
                datasets: [{
                    label: 'Volume (TPIX)',
                    data: {!! json_encode($volumeData->pluck('volume')) !!},
                    borderColor: 'rgb(99, 102, 241)',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            color: textColor
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: textColor
                        },
                        grid: {
                            color: gridColor
                        }
                    },
                    x: {
                        ticks: {
                            color: textColor
                        },
                        grid: {
                            color: gridColor
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush
@endsection
