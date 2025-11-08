@extends('layouts.admin')

@section('title', 'Crypto Management Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Welcome Header -->
    <div class="bg-gradient-to-r from-cyan-600 via-blue-600 to-indigo-600 rounded-2xl shadow-2xl p-8 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-40 h-40 bg-white opacity-10 rounded-full"></div>
        <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-32 h-32 bg-white opacity-10 rounded-full"></div>
        <div class="relative z-10">
            <h1 class="text-3xl font-bold mb-2">₿ Crypto Management Dashboard</h1>
            <p class="text-cyan-100">ภาพรวมระบบจัดการสกุลเงินดิจิทัล</p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Wallets -->
        <a href="{{ route('admin.crypto.wallets') }}" class="relative overflow-hidden bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition duration-300 block cursor-pointer">
            <div class="absolute top-0 right-0 -mt-4 -mr-4">
                <div class="w-24 h-24 bg-white opacity-10 rounded-full"></div>
            </div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-5xl">💼</div>
                    <span class="px-2 py-1 bg-white bg-opacity-20 rounded-lg text-xs font-semibold">
                        {{ $stats['active_wallets'] }} ใช้งาน
                    </span>
                </div>
                <p class="text-white text-opacity-80 text-sm mb-1">กระเป๋าทั้งหมด</p>
                <p class="text-4xl font-bold">{{ number_format($stats['total_wallets']) }}</p>
                <p class="text-xs text-white text-opacity-70 mt-2">{{ number_format($stats['total_users']) }} ผู้ใช้</p>
            </div>
        </a>

        <!-- Pending Deposits -->
        <a href="{{ route('admin.crypto.transactions', ['type' => 'deposit', 'status' => 'pending']) }}" class="relative overflow-hidden bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition duration-300 block cursor-pointer">
            <div class="absolute top-0 right-0 -mt-4 -mr-4">
                <div class="w-24 h-24 bg-white opacity-10 rounded-full"></div>
            </div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-5xl">📥</div>
                    <span class="px-2 py-1 bg-white bg-opacity-20 rounded-lg text-xs font-semibold">
                        {{ $stats['deposits_24h'] }} (24h)
                    </span>
                </div>
                <p class="text-white text-opacity-80 text-sm mb-1">ฝากรอดำเนินการ</p>
                <p class="text-4xl font-bold">{{ number_format($stats['pending_deposits']) }}</p>
                <p class="text-xs text-white text-opacity-70 mt-2">คลิกเพื่อดูรายละเอียด</p>
            </div>
        </a>

        <!-- Pending Withdrawals -->
        <a href="{{ route('admin.crypto.withdrawals', ['status' => 'pending']) }}" class="relative overflow-hidden bg-gradient-to-br from-orange-500 to-red-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition duration-300 block cursor-pointer">
            <div class="absolute top-0 right-0 -mt-4 -mr-4">
                <div class="w-24 h-24 bg-white opacity-10 rounded-full"></div>
            </div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-5xl">📤</div>
                    <span class="px-2 py-1 bg-white bg-opacity-20 rounded-lg text-xs font-semibold">
                        {{ $stats['reviewing_withdrawals'] }} รีวิว
                    </span>
                </div>
                <p class="text-white text-opacity-80 text-sm mb-1">ถอนรอดำเนินการ</p>
                <p class="text-4xl font-bold">{{ number_format($stats['pending_withdrawals']) }}</p>
                <p class="text-xs text-white text-opacity-70 mt-2">{{ $stats['withdrawals_24h'] }} รายการ (24h)</p>
            </div>
        </a>

        <!-- 24h Volume -->
        <div class="relative overflow-hidden bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl shadow-xl p-6 text-white">
            <div class="absolute top-0 right-0 -mt-4 -mr-4">
                <div class="w-24 h-24 bg-white opacity-10 rounded-full"></div>
            </div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-5xl">💰</div>
                </div>
                <p class="text-white text-opacity-80 text-sm mb-1">ปริมาณ 24 ชั่วโมง</p>
                <p class="text-4xl font-bold">฿{{ number_format($stats['total_volume_24h'], 0) }}</p>
                <p class="text-xs text-white text-opacity-70 mt-2">รายการที่ยืนยันแล้ว</p>
            </div>
        </div>
    </div>

    <!-- Currency Stats -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">💎 สถิติตามสกุลเงิน</h2>
            <a href="{{ route('admin.crypto.currencies') }}" class="text-sm text-cyan-600 dark:text-cyan-400 hover:text-cyan-700 dark:hover:text-cyan-300 font-medium">
                จัดการสกุลเงิน →
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($currencyStats as $currency)
                <div class="bg-gradient-to-br from-gray-50 to-white dark:from-slate-700 dark:to-slate-800 rounded-xl p-4 border border-gray-100 dark:border-slate-600 hover:shadow-md transition">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center space-x-3">
                            @php
                                $iconPath = public_path('icons/cryptocurrency/' . strtolower($currency->code) . '.svg');
                            @endphp
                            @if(file_exists($iconPath))
                                <img src="{{ asset('icons/cryptocurrency/' . strtolower($currency->code) . '.svg') }}"
                                     alt="{{ $currency->code }}"
                                     class="w-10 h-10">
                            @else
                                <div class="w-10 h-10 bg-gradient-to-br from-cyan-500 to-blue-600 rounded-full flex items-center justify-center text-white font-bold">
                                    {{ strtoupper(substr($currency->code, 0, 1)) }}
                                </div>
                            @endif
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white">{{ $currency->code }}</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $currency->name }}</p>
                            </div>
                        </div>
                        <span class="px-2 py-1 rounded-lg text-xs font-semibold {{ $currency->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200' }}">
                            {{ $currency->is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}
                        </span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <div>
                            <p class="text-gray-500 dark:text-gray-400 text-xs">ธุรกรรม (30 วัน)</p>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ number_format($currency->transactions_count ?? 0) }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-gray-500 dark:text-gray-400 text-xs">ค่าธรรมเนียม</p>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $currency->withdrawal_fee ?? 0 }} {{ $currency->code }}</p>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-3 text-center text-gray-500 py-8">
                    <span class="text-4xl mb-2 block">💎</span>
                    <p>ยังไม่มีสกุลเงินดิจิทัล</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Volume Chart -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">📊 ปริมาณการทำธุรกรรมรายวัน</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">30 วันย้อนหลัง</p>
            </div>
        </div>
        <canvas id="volumeChart" height="80"></canvas>
    </div>

    <!-- Recent Transactions & Pending Withdrawals -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Transactions -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">🔄 ธุรกรรมล่าสุด</h2>
                <a href="{{ route('admin.crypto.transactions') }}" class="text-sm text-cyan-600 dark:text-cyan-400 hover:text-cyan-700 dark:hover:text-cyan-300 font-medium">
                    ดูทั้งหมด →
                </a>
            </div>

            <div class="space-y-3 max-h-96 overflow-y-auto">
                @forelse($recentTransactions as $transaction)
                    <div class="flex items-center gap-3 p-3 hover:bg-gray-50 dark:hover:bg-slate-700 rounded-lg transition">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 {{ $transaction->type === 'deposit' ? 'bg-green-100 dark:bg-green-900' : 'bg-orange-100 dark:bg-orange-900' }} rounded-full flex items-center justify-center">
                                <span class="text-lg">{{ $transaction->type === 'deposit' ? '📥' : '📤' }}</span>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                {{ $transaction->user->name ?? 'ไม่ระบุ' }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $transaction->amount }} {{ $transaction->currency->code ?? '' }}
                                <span class="mx-1">•</span>
                                ฿{{ number_format($transaction->amount_thb ?? 0, 2) }}
                            </p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                {{ $transaction->created_at->diffForHumans() }}
                            </p>
                        </div>
                        <span class="px-2 py-1 rounded-full text-xs font-semibold flex-shrink-0
                            {{ $transaction->status === 'pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : '' }}
                            {{ $transaction->status === 'confirmed' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
                            {{ $transaction->status === 'failed' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : '' }}">
                            {{ ucfirst($transaction->status) }}
                        </span>
                    </div>
                @empty
                    <div class="text-center text-gray-500 py-8">
                        <span class="text-4xl mb-2 block">📭</span>
                        <p>ยังไม่มีธุรกรรม</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Pending Withdrawals -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">⏳ คำขอถอนรอดำเนินการ</h2>
                <a href="{{ route('admin.crypto.withdrawals') }}" class="text-sm text-cyan-600 dark:text-cyan-400 hover:text-cyan-700 dark:hover:text-cyan-300 font-medium">
                    ดูทั้งหมด →
                </a>
            </div>

            <div class="space-y-3 max-h-96 overflow-y-auto">
                @forelse($pendingWithdrawals as $withdrawal)
                    <div class="flex items-start gap-3 p-3 hover:bg-gray-50 dark:hover:bg-slate-700 rounded-lg transition border border-gray-100 dark:border-slate-600">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-orange-100 dark:bg-orange-900 rounded-full flex items-center justify-center">
                                <span class="text-lg">💸</span>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                {{ $withdrawal->user->name ?? 'ไม่ระบุ' }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $withdrawal->amount }} {{ $withdrawal->currency->code ?? '' }}
                                <span class="mx-1">•</span>
                                ฿{{ number_format($withdrawal->amount_thb ?? 0, 2) }}
                            </p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1 truncate">
                                ที่อยู่: {{ substr($withdrawal->to_address ?? '', 0, 20) }}...
                            </p>
                            <p class="text-xs text-gray-400 dark:text-gray-500">
                                {{ $withdrawal->created_at->diffForHumans() }}
                            </p>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="px-2 py-1 rounded-full text-xs font-semibold flex-shrink-0
                                {{ $withdrawal->status === 'pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : '' }}
                                {{ $withdrawal->status === 'reviewing' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : '' }}">
                                {{ ucfirst($withdrawal->status) }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-gray-500 py-8">
                        <span class="text-4xl mb-2 block">✅</span>
                        <p>ไม่มีคำขอถอนที่รอดำเนินการ</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-gradient-to-r from-cyan-500 to-blue-600 rounded-2xl shadow-xl p-6 text-white">
        <h3 class="text-xl font-bold mb-4">⚡ การกระทำด่วน</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <form action="{{ route('admin.crypto.scan-deposits') }}" method="POST" class="w-full">
                @csrf
                <button type="submit" class="w-full bg-white bg-opacity-20 hover:bg-opacity-30 rounded-xl p-4 text-center transition">
                    <div class="text-3xl mb-2">🔍</div>
                    <p class="text-sm font-semibold">สแกนฝากเงิน</p>
                </button>
            </form>

            <a href="{{ route('admin.crypto.withdrawals', ['status' => 'pending']) }}" class="bg-white bg-opacity-20 hover:bg-opacity-30 rounded-xl p-4 text-center transition">
                <div class="text-3xl mb-2">✅</div>
                <p class="text-sm font-semibold">อนุมัติถอนเงิน</p>
            </a>

            <a href="{{ route('admin.crypto.transactions') }}" class="bg-white bg-opacity-20 hover:bg-opacity-30 rounded-xl p-4 text-center transition">
                <div class="text-3xl mb-2">📊</div>
                <p class="text-sm font-semibold">ดูธุรกรรม</p>
            </a>

            <a href="{{ route('admin.crypto.settings') }}" class="bg-white bg-opacity-20 hover:bg-opacity-30 rounded-xl p-4 text-center transition">
                <div class="text-3xl mb-2">⚙️</div>
                <p class="text-sm font-semibold">ตั้งค่าระบบ</p>
            </a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Volume Chart
const volumeCtx = document.getElementById('volumeChart');
if (volumeCtx) {
    const colors = window.getChartColors();
    new Chart(volumeCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode(array_column($volumeData, 'date')) !!},
            datasets: [
                {
                    label: 'ฝากเงิน (฿)',
                    data: {!! json_encode(array_column($volumeData, 'deposits')) !!},
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 2,
                    pointRadius: 3
                },
                {
                    label: 'ถอนเงิน (฿)',
                    data: {!! json_encode(array_column($volumeData, 'withdrawals')) !!},
                    borderColor: 'rgb(249, 115, 22)',
                    backgroundColor: 'rgba(249, 115, 22, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 2,
                    pointRadius: 3
                },
                {
                    label: 'รวม (฿)',
                    data: {!! json_encode(array_column($volumeData, 'total')) !!},
                    borderColor: 'rgb(99, 102, 241)',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    fill: false,
                    tension: 0.4,
                    borderWidth: 3,
                    pointRadius: 4,
                    borderDash: [5, 5]
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        color: colors.textColor,
                        usePointStyle: true,
                        padding: 15
                    }
                },
                tooltip: {
                    backgroundColor: colors.tooltipBg,
                    padding: 12,
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: 'rgba(99, 102, 241, 0.5)',
                    borderWidth: 1,
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += '฿' + context.parsed.y.toLocaleString();
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: colors.gridColor
                    },
                    ticks: {
                        color: colors.textColor,
                        callback: function(value) {
                            return '฿' + value.toLocaleString();
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: colors.textColor,
                        maxTicksLimit: 15
                    }
                }
            }
        }
    });
}
</script>
@endpush
