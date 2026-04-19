@extends('layouts.admin-v3')

@section('title', 'Platform Revenue Dashboard')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-emerald-900 to-gray-900 py-8 px-4">
    <div class="max-w-7xl mx-auto">
        {{-- Header --}}
        <div class="mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-4xl font-bold text-white mb-2 flex items-center gap-3">
                        <i class="fas fa-chart-pie text-emerald-400"></i>
                        Platform Revenue Dashboard
                    </h1>
                    <p class="text-white/60">ภาพรวมรายได้และการเงินของ Platform</p>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('admin.platform-revenue.reports') }}"
                       class="px-4 py-2 bg-white/10 border border-white/30 rounded-xl text-white hover:bg-white/20 transition">
                        <i class="fas fa-file-alt mr-2"></i>รายงาน
                    </a>
                    <a href="{{ route('admin.platform-revenue.transactions') }}"
                       class="px-4 py-2 bg-emerald-500 rounded-xl text-white hover:bg-emerald-600 transition">
                        <i class="fas fa-list mr-2"></i>Transactions
                    </a>
                </div>
            </div>
        </div>

        {{-- Wallet Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            @foreach($wallets as $wallet)
            <a href="{{ route('admin.platform-revenue.wallets.show', $wallet) }}"
               class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20 hover:border-{{ $wallet->slug === 'fee' ? 'blue' : ($wallet->slug === 'vat' ? 'green' : ($wallet->slug === 'mlm_pool' ? 'purple' : 'yellow')) }}-400/50 transition-all duration-300 group">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br {{ $wallet->slug === 'fee' ? 'from-blue-400 to-cyan-500' : ($wallet->slug === 'vat' ? 'from-green-400 to-emerald-500' : ($wallet->slug === 'mlm_pool' ? 'from-purple-400 to-pink-500' : 'from-yellow-400 to-orange-500')) }} rounded-xl flex items-center justify-center">
                        <i class="fas {{ $wallet->slug === 'fee' ? 'fa-coins' : ($wallet->slug === 'vat' ? 'fa-receipt' : ($wallet->slug === 'mlm_pool' ? 'fa-users' : 'fa-piggy-bank')) }} text-white text-xl"></i>
                    </div>
                    <i class="fas fa-arrow-right text-white/30 group-hover:text-white/60 transition"></i>
                </div>
                <div class="text-3xl font-bold text-white mb-1">฿{{ number_format($wallet->balance, 2) }}</div>
                <div class="text-white/60 text-sm">{{ $wallet->name }}</div>
                <div class="mt-3 flex items-center text-sm">
                    <span class="text-emerald-400">
                        <i class="fas fa-arrow-up mr-1"></i>
                        +฿{{ number_format($todayStats['by_wallet'][$wallet->slug] ?? 0, 2) }}
                    </span>
                    <span class="text-white/40 ml-2">วันนี้</span>
                </div>
            </a>
            @endforeach
        </div>

        {{-- Total Balance --}}
        <div class="bg-gradient-to-r from-emerald-500/20 to-cyan-500/20 backdrop-blur-lg rounded-2xl p-6 border border-emerald-400/30 mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-white/60 text-sm mb-1">ยอดรวมทุก Wallet</div>
                    <div class="text-4xl font-bold text-white">฿{{ number_format($totalBalance, 2) }}</div>
                </div>
                <div class="text-right">
                    <div class="text-white/60 text-sm mb-1">รายได้วันนี้</div>
                    <div class="text-2xl font-bold text-emerald-400">+฿{{ number_format($todayStats['total'], 2) }}</div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            {{-- Revenue Chart --}}
            <div class="lg:col-span-2 bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                <h2 class="text-xl font-bold text-white mb-4">รายได้ 30 วันล่าสุด</h2>
                <div class="h-80">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            {{-- Monthly Stats --}}
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                <h2 class="text-xl font-bold text-white mb-4">สถิติเดือนนี้</h2>
                <div class="space-y-4">
                    <div class="flex justify-between items-center p-3 bg-white/5 rounded-xl">
                        <span class="text-white/60">รายได้รวม</span>
                        <span class="text-emerald-400 font-bold">฿{{ number_format($monthlyStats['total'], 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-white/5 rounded-xl">
                        <span class="text-white/60">ค่า Fee</span>
                        <span class="text-blue-400 font-bold">฿{{ number_format($monthlyStats['by_wallet']['fee'] ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-white/5 rounded-xl">
                        <span class="text-white/60">VAT</span>
                        <span class="text-green-400 font-bold">฿{{ number_format($monthlyStats['by_wallet']['vat'] ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-white/5 rounded-xl">
                        <span class="text-white/60">MLM Pool</span>
                        <span class="text-purple-400 font-bold">฿{{ number_format($monthlyStats['by_wallet']['mlm_pool'] ?? 0, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            {{-- Pending Payouts --}}
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold text-white">Payout รอดำเนินการ</h2>
                    <a href="{{ route('admin.platform-revenue.payouts.index') }}"
                       class="text-emerald-400 hover:text-emerald-300 text-sm">
                        ดูทั้งหมด <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>

                @if($pendingPayouts->isEmpty())
                <div class="text-center py-8 text-white/40">
                    <i class="fas fa-check-circle text-4xl mb-3"></i>
                    <p>ไม่มี Payout รอดำเนินการ</p>
                </div>
                @else
                <div class="space-y-3">
                    @foreach($pendingPayouts as $payout)
                    <a href="{{ route('admin.platform-revenue.payouts.show', $payout) }}"
                       class="flex items-center justify-between p-3 bg-white/5 rounded-xl hover:bg-white/10 transition group">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-orange-400 to-red-500 rounded-full flex items-center justify-center text-white font-bold">
                                {{ substr($payout->user->name ?? '?', 0, 1) }}
                            </div>
                            <div>
                                <div class="text-white font-medium">{{ $payout->user->name ?? 'User #'.$payout->user_id }}</div>
                                <div class="text-white/40 text-sm">{{ $payout->earning_type }}</div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-orange-400 font-bold">฿{{ number_format($payout->net_amount, 2) }}</div>
                            <div class="text-white/40 text-xs">{{ $payout->created_at->diffForHumans() }}</div>
                        </div>
                    </a>
                    @endforeach
                </div>
                @endif

                <div class="mt-4 pt-4 border-t border-white/10 flex justify-between text-sm">
                    <span class="text-white/60">รอดำเนินการ: {{ $payoutStats['pending_count'] ?? 0 }} รายการ</span>
                    <span class="text-orange-400 font-bold">฿{{ number_format($payoutStats['pending_amount'] ?? 0, 2) }}</span>
                </div>
            </div>

            {{-- Debt Stats --}}
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold text-white">สถานะหนี้</h2>
                    <a href="{{ route('admin.platform-revenue.debts.index') }}"
                       class="text-emerald-400 hover:text-emerald-300 text-sm">
                        ดูทั้งหมด <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div class="bg-red-500/20 rounded-xl p-4 text-center">
                        <div class="text-3xl font-bold text-red-400">{{ $debtStats['active']['count'] ?? 0 }}</div>
                        <div class="text-white/60 text-sm">หนี้ค้างชำระ</div>
                    </div>
                    <div class="bg-green-500/20 rounded-xl p-4 text-center">
                        <div class="text-3xl font-bold text-green-400">{{ $debtStats['paid']['count'] ?? 0 }}</div>
                        <div class="text-white/60 text-sm">ชำระแล้ว</div>
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="flex justify-between items-center p-3 bg-white/5 rounded-xl">
                        <span class="text-white/60">ยอดหนี้คงค้าง</span>
                        <span class="text-red-400 font-bold">฿{{ number_format($debtStats['active']['amount'] ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-white/5 rounded-xl">
                        <span class="text-white/60">อัตราการเก็บหนี้</span>
                        <span class="text-emerald-400 font-bold">{{ number_format($debtStats['collection_rate'] ?? 0, 1) }}%</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Recent Transactions --}}
        <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-white">Transactions ล่าสุด</h2>
                <a href="{{ route('admin.platform-revenue.transactions') }}"
                   class="text-emerald-400 hover:text-emerald-300 text-sm">
                    ดูทั้งหมด <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-white/60 text-left text-sm">
                            <th class="pb-3 font-medium">Wallet</th>
                            <th class="pb-3 font-medium">ประเภท</th>
                            <th class="pb-3 font-medium">จำนวน</th>
                            <th class="pb-3 font-medium">รายละเอียด</th>
                            <th class="pb-3 font-medium">เวลา</th>
                        </tr>
                    </thead>
                    <tbody class="text-white">
                        @forelse($recentTransactions as $tx)
                        <tr class="border-t border-white/10">
                            <td class="py-3">
                                <span class="px-2 py-1 bg-white/10 rounded-lg text-sm">{{ $tx->wallet->name ?? '-' }}</span>
                            </td>
                            <td class="py-3">
                                <span class="px-2 py-1 rounded-lg text-sm {{ $tx->type === 'income' ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400' }}">
                                    {{ $tx->type }}
                                </span>
                            </td>
                            <td class="py-3 font-mono {{ $tx->type === 'income' ? 'text-green-400' : 'text-red-400' }}">
                                {{ $tx->type === 'income' ? '+' : '-' }}฿{{ number_format(abs($tx->amount), 2) }}
                            </td>
                            <td class="py-3 text-white/60 text-sm max-w-xs truncate">{{ $tx->description }}</td>
                            <td class="py-3 text-white/40 text-sm">{{ $tx->created_at->diffForHumans() }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-white/40">ไม่มีข้อมูล</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Revenue Chart
    const ctx = document.getElementById('revenueChart').getContext('2d');
    const chartData = @json($revenueChart);

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.labels,
            datasets: [
                {
                    label: 'ค่า Fee',
                    data: chartData.fee,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    fill: true,
                    tension: 0.4,
                },
                {
                    label: 'VAT',
                    data: chartData.vat,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    fill: true,
                    tension: 0.4,
                },
                {
                    label: 'MLM Pool',
                    data: chartData.mlm_pool,
                    borderColor: '#a855f7',
                    backgroundColor: 'rgba(168, 85, 247, 0.1)',
                    fill: true,
                    tension: 0.4,
                },
                {
                    label: 'Payout',
                    data: chartData.payout,
                    borderColor: '#f97316',
                    backgroundColor: 'rgba(249, 115, 22, 0.1)',
                    fill: true,
                    tension: 0.4,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: 'rgba(255,255,255,0.6)' }
                }
            },
            scales: {
                x: {
                    ticks: { color: 'rgba(255,255,255,0.4)' },
                    grid: { color: 'rgba(255,255,255,0.1)' }
                },
                y: {
                    ticks: { color: 'rgba(255,255,255,0.4)' },
                    grid: { color: 'rgba(255,255,255,0.1)' }
                }
            }
        }
    });

    // Auto refresh stats every 30 seconds
    setInterval(async () => {
        try {
            const response = await fetch('{{ route("admin.platform-revenue.api.stats") }}');
            const data = await response.json();
            if (data.success) {
                // Update UI if needed
                console.log('Stats updated:', data.data);
            }
        } catch (e) {
            console.error('Failed to refresh stats:', e);
        }
    }, 30000);
});
</script>
@endpush
