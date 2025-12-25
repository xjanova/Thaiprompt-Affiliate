@extends('layouts.admin-v3')

@section('title', 'แดชบอร์ดของ ' . $user->name)

@section('content')
<div class="space-y-6">
    {{-- Back Link --}}
    <div>
        <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 hover:bg-white/20 dark:bg-white/5 dark:hover:bg-white/10 backdrop-blur-sm rounded-lg transition-all text-white">
            <i class="fas fa-arrow-left"></i>
            กลับไปรายการผู้ใช้
        </a>
    </div>

    {{-- Premium Hero Header (Purple-Pink-Red for User Dashboard) --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-purple-600 via-pink-600 to-red-600 dark:from-purple-800 dark:via-pink-800 dark:to-red-800 rounded-2xl shadow-2xl p-8">
        {{-- Animated Background Orbs --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s"></div>
        </div>

        {{-- Floating Icons --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute text-white/10 text-8xl top-10 right-20" style="animation: float 6s ease-in-out infinite">
                <i class="fas fa-chart-line"></i>
            </div>
        </div>

        {{-- User Profile with Dashboard Info --}}
        <div class="relative z-10">
            <div class="flex items-center gap-6">
                @if($user->profile_picture_url)
                    <img src="{{ $user->profile_picture_url }}"
                         alt="{{ $user->name }}"
                         class="h-24 w-24 rounded-2xl object-cover ring-4 ring-white/30 shadow-2xl">
                @else
                    <div class="h-24 w-24 glass-fusion rounded-2xl flex items-center justify-center text-3xl font-bold text-white shadow-2xl ring-4 ring-white/30">
                        {{ strtoupper(substr($user->name, 0, 2)) }}
                    </div>
                @endif
                <div>
                    <h1 class="text-4xl font-bold text-white drop-shadow-lg">แดชบอร์ดของ {{ $user->name }}</h1>
                    <p class="text-purple-100 text-lg mt-1">{{ $user->email }} • {{ ucfirst($user->role) }}</p>
                    <div class="mt-2">
                        <span class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 backdrop-blur-sm text-white rounded-lg font-semibold">
                            <i class="fas fa-calendar"></i>
                            สมาชิกตั้งแต่ {{ $user->created_at->format('d/m/Y') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {{-- Total Commissions Card (Blue-Indigo) --}}
        <div class="group relative overflow-hidden rounded-2xl shadow-xl p-6 transform hover:scale-105 transition-all duration-300 cursor-pointer bg-gradient-to-br from-blue-500 to-indigo-600 dark:from-blue-600 dark:to-indigo-800">
            <div class="absolute -right-8 -top-8 opacity-10">
                <i class="fas fa-list text-9xl text-white"></i>
            </div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-5xl">📊</span>
                    <div class="p-2 bg-white/20 rounded-lg backdrop-blur-sm">
                        <i class="fas fa-list text-white text-xl"></i>
                    </div>
                </div>
                <p class="text-3xl font-bold text-white mb-1">{{ $stats['total_commissions'] }}</p>
                <p class="text-sm text-blue-100">คอมมิชชั่นทั้งหมด</p>
            </div>
        </div>

        {{-- Pending Commissions Card (Yellow-Amber) --}}
        <div class="group relative overflow-hidden rounded-2xl shadow-xl p-6 transform hover:scale-105 transition-all duration-300 cursor-pointer bg-gradient-to-br from-yellow-500 to-amber-600 dark:from-yellow-600 dark:to-amber-800">
            <div class="absolute -right-8 -top-8 opacity-10">
                <i class="fas fa-clock text-9xl text-white"></i>
            </div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-5xl">⏳</span>
                    <div class="p-2 bg-white/20 rounded-lg backdrop-blur-sm">
                        <i class="fas fa-clock text-white text-xl"></i>
                    </div>
                </div>
                <p class="text-3xl font-bold text-white mb-1">{{ $stats['pending_commissions'] }}</p>
                <p class="text-sm text-yellow-100">รอดำเนินการ</p>
            </div>
        </div>

        {{-- Approved Commissions Card (Green-Emerald) --}}
        <div class="group relative overflow-hidden rounded-2xl shadow-xl p-6 transform hover:scale-105 transition-all duration-300 cursor-pointer bg-gradient-to-br from-green-500 to-emerald-600 dark:from-green-600 dark:to-emerald-800">
            <div class="absolute -right-8 -top-8 opacity-10">
                <i class="fas fa-check-circle text-9xl text-white"></i>
            </div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-5xl">✅</span>
                    <div class="p-2 bg-white/20 rounded-lg backdrop-blur-sm">
                        <i class="fas fa-check-circle text-white text-xl"></i>
                    </div>
                </div>
                <p class="text-3xl font-bold text-white mb-1">{{ $stats['approved_commissions'] }}</p>
                <p class="text-sm text-green-100">อนุมัติแล้ว</p>
            </div>
        </div>

        {{-- Total Earnings Card (Purple-Pink) --}}
        <div class="group relative overflow-hidden rounded-2xl shadow-xl p-6 transform hover:scale-105 transition-all duration-300 cursor-pointer bg-gradient-to-br from-purple-500 to-pink-600 dark:from-purple-600 dark:to-pink-800">
            <div class="absolute -right-8 -top-8 opacity-10">
                <i class="fas fa-coins text-9xl text-white"></i>
            </div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-5xl">💰</span>
                    <div class="p-2 bg-white/20 rounded-lg backdrop-blur-sm">
                        <i class="fas fa-coins text-white text-xl"></i>
                    </div>
                </div>
                <p class="text-3xl font-bold text-white mb-1">฿{{ number_format($stats['total_earnings'], 2) }}</p>
                <p class="text-sm text-purple-100">รายได้ทั้งหมด</p>
            </div>
        </div>
    </div>

    {{-- Charts Section --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Commission Timeline Chart --}}
        <div class="bg-white/85 dark:bg-white/15 backdrop-blur-xl border border-black/5 dark:border-white/30 rounded-2xl shadow-xl p-8">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                <i class="fas fa-chart-line text-blue-600 dark:text-blue-400"></i>
                คอมมิชชั่นรายเดือน (6 เดือนล่าสุด)
            </h2>
            <div class="h-64">
                @if($chartData->count() > 0)
                    <canvas id="commissionChart"></canvas>
                @else
                    <div class="flex items-center justify-center h-full text-gray-500 dark:text-gray-400">
                        <div class="text-center">
                            <p class="text-6xl mb-4">📭</p>
                            <p class="text-lg font-semibold">ยังไม่มีข้อมูลคอมมิชชั่น</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Status Breakdown --}}
        <div class="bg-white/85 dark:bg-white/15 backdrop-blur-xl border border-black/5 dark:border-white/30 rounded-2xl shadow-xl p-8">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                <i class="fas fa-tasks text-purple-600 dark:text-purple-400"></i>
                สถานะคอมมิชชั่น
            </h2>
            <div class="space-y-4">
                <div class="bg-gradient-to-r from-yellow-50 to-amber-50 dark:from-yellow-900/20 dark:to-amber-900/20 backdrop-blur-sm rounded-xl p-5 border-2 border-yellow-200 dark:border-yellow-800">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="text-3xl">⏳</div>
                            <div>
                                <p class="font-bold text-gray-900 dark:text-white text-lg">รอดำเนินการ</p>
                                <p class="text-sm text-yellow-700 dark:text-yellow-300">฿{{ number_format($stats['pending_earnings'], 2) }}</p>
                            </div>
                        </div>
                        <p class="text-3xl font-bold text-yellow-600 dark:text-yellow-400">{{ $stats['pending_commissions'] }}</p>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 backdrop-blur-sm rounded-xl p-5 border-2 border-green-200 dark:border-green-800">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="text-3xl">✅</div>
                            <div>
                                <p class="font-bold text-gray-900 dark:text-white text-lg">อนุมัติแล้ว</p>
                                <p class="text-sm text-green-700 dark:text-green-300">{{ $stats['approved_commissions'] }} รายการ</p>
                            </div>
                        </div>
                        <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $stats['approved_commissions'] }}</p>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 backdrop-blur-sm rounded-xl p-5 border-2 border-blue-200 dark:border-blue-800">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="text-3xl">💰</div>
                            <div>
                                <p class="font-bold text-gray-900 dark:text-white text-lg">จ่ายแล้ว</p>
                                <p class="text-sm text-blue-700 dark:text-blue-300">฿{{ number_format($stats['total_earnings'], 2) }}</p>
                            </div>
                        </div>
                        <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['paid_commissions'] }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Commissions --}}
    <div class="bg-white/85 dark:bg-white/15 backdrop-blur-xl border border-black/5 dark:border-white/30 rounded-2xl shadow-xl overflow-hidden">
        <div class="px-8 py-6 border-b border-gray-200 dark:border-white/10">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="fas fa-history text-indigo-600 dark:text-indigo-400"></i>
                คอมมิชชั่นล่าสุด
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-white/10">
                <thead class="bg-white/60 dark:bg-white/10">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">วันที่</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Order ID</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">ประเภท</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">จำนวน</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">สถานะ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                    @forelse($recentCommissions as $commission)
                        <tr class="hover:bg-white/80 dark:hover:bg-white/10 transition-all duration-200">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 font-medium">
                                {{ $commission->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                {{ $commission->order_id }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                {{ ucfirst($commission->type) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-white">
                                ฿{{ number_format($commission->amount, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($commission->status === 'pending')
                                    <span class="inline-flex items-center gap-1 px-3 py-1 text-xs font-bold rounded-lg bg-gradient-to-r from-yellow-100 to-amber-100 dark:from-yellow-900/30 dark:to-amber-900/30 text-yellow-800 dark:text-yellow-300">
                                        <i class="fas fa-clock"></i>
                                        Pending
                                    </span>
                                @elseif($commission->status === 'approved')
                                    <span class="inline-flex items-center gap-1 px-3 py-1 text-xs font-bold rounded-lg bg-gradient-to-r from-green-100 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/30 text-green-800 dark:text-green-300">
                                        <i class="fas fa-check"></i>
                                        Approved
                                    </span>
                                @elseif($commission->status === 'paid')
                                    <span class="inline-flex items-center gap-1 px-3 py-1 text-xs font-bold rounded-lg bg-gradient-to-r from-blue-100 to-indigo-100 dark:from-blue-900/30 dark:to-indigo-900/30 text-blue-800 dark:text-blue-300">
                                        <i class="fas fa-check-double"></i>
                                        Paid
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-16 text-center text-gray-500 dark:text-gray-400">
                                <div class="flex flex-col items-center justify-center">
                                    <span class="text-6xl mb-4">📭</span>
                                    <span class="text-lg font-semibold">ยังไม่มีข้อมูลคอมมิชชั่น</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($chartData->count() > 0)
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('commissionChart');
    const chartData = @json($chartData);

    // Detect dark mode
    const isDarkMode = document.documentElement.classList.contains('dark');
    const textColor = isDarkMode ? '#e5e7eb' : '#374151';
    const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.map(d => d.month),
            datasets: [{
                label: 'คอมมิชชั่น (฿)',
                data: chartData.map(d => d.total),
                borderColor: 'rgb(147, 51, 234)',
                backgroundColor: 'rgba(147, 51, 234, 0.1)',
                tension: 0.4,
                fill: true,
                borderWidth: 3,
                pointRadius: 5,
                pointHoverRadius: 7,
                pointBackgroundColor: 'rgb(147, 51, 234)',
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        color: textColor,
                        padding: 20,
                        font: {
                            size: 13,
                            weight: 'bold'
                        }
                    }
                },
                tooltip: {
                    backgroundColor: isDarkMode ? 'rgba(17, 24, 39, 0.9)' : 'rgba(255, 255, 255, 0.9)',
                    titleColor: textColor,
                    bodyColor: textColor,
                    borderColor: isDarkMode ? 'rgba(255, 255, 255, 0.2)' : 'rgba(0, 0, 0, 0.2)',
                    borderWidth: 1,
                    padding: 12,
                    displayColors: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: textColor,
                        callback: function(value) {
                            return '฿' + value.toLocaleString();
                        }
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
</script>
@endif

@push('styles')
<style>
@keyframes float {
    0%, 100% {
        transform: translateY(0px);
    }
    50% {
        transform: translateY(-20px);
    }
}
</style>
@endpush
@endsection
