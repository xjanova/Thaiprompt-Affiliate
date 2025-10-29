@extends('layouts.admin')

@section('title', 'แดชบอร์ดของ ' . $user->name)

@section('content')
<div class="space-y-6">
    <!-- Header with Back Button -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.users.index') }}" class="text-gray-600 hover:text-gray-900">
                ← กลับ
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">แดชบอร์ดของ {{ $user->name }}</h1>
                <p class="text-sm text-gray-500">{{ $user->email }} • {{ ucfirst($user->role) }}</p>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Commissions -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">คอมมิชชั่นทั้งหมด</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ $stats['total_commissions'] }}</p>
                </div>
                <div class="text-4xl">📊</div>
            </div>
        </div>

        <!-- Pending Commissions -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">รอดำเนินการ</p>
                    <p class="text-3xl font-bold text-yellow-600 mt-2">{{ $stats['pending_commissions'] }}</p>
                </div>
                <div class="text-4xl">⏳</div>
            </div>
        </div>

        <!-- Approved Commissions -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">อนุมัติแล้ว</p>
                    <p class="text-3xl font-bold text-green-600 mt-2">{{ $stats['approved_commissions'] }}</p>
                </div>
                <div class="text-4xl">✅</div>
            </div>
        </div>

        <!-- Total Earnings -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">รายได้ทั้งหมด</p>
                    <p class="text-3xl font-bold text-blue-600 mt-2">฿{{ number_format($stats['total_earnings'], 2) }}</p>
                </div>
                <div class="text-4xl">💰</div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Commission Timeline Chart -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">คอมมิชชั่นรายเดือน (6 เดือนล่าสุด)</h2>
            <div class="h-64">
                @if($chartData->count() > 0)
                    <canvas id="commissionChart"></canvas>
                @else
                    <div class="flex items-center justify-center h-full text-gray-500">
                        <div class="text-center">
                            <p class="text-4xl mb-2">📭</p>
                            <p>ยังไม่มีข้อมูลคอมมิชชั่น</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Status Breakdown -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">สถานะคอมมิชชั่น</h2>
            <div class="space-y-4">
                <div class="flex items-center justify-between p-4 bg-yellow-50 rounded-lg">
                    <div class="flex items-center gap-3">
                        <div class="text-2xl">⏳</div>
                        <div>
                            <p class="font-medium text-gray-900">รอดำเนินการ</p>
                            <p class="text-sm text-gray-500">฿{{ number_format($stats['pending_earnings'], 2) }}</p>
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-yellow-600">{{ $stats['pending_commissions'] }}</p>
                </div>

                <div class="flex items-center justify-between p-4 bg-green-50 rounded-lg">
                    <div class="flex items-center gap-3">
                        <div class="text-2xl">✅</div>
                        <div>
                            <p class="font-medium text-gray-900">อนุมัติแล้ว</p>
                            <p class="text-sm text-gray-500">{{ $stats['approved_commissions'] }} รายการ</p>
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-green-600">{{ $stats['approved_commissions'] }}</p>
                </div>

                <div class="flex items-center justify-between p-4 bg-blue-50 rounded-lg">
                    <div class="flex items-center gap-3">
                        <div class="text-2xl">💰</div>
                        <div>
                            <p class="font-medium text-gray-900">จ่ายแล้ว</p>
                            <p class="text-sm text-gray-500">฿{{ number_format($stats['total_earnings'], 2) }}</p>
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-blue-600">{{ $stats['paid_commissions'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Commissions -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">คอมมิชชั่นล่าสุด</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">วันที่</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ประเภท</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">จำนวน</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สถานะ</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($recentCommissions as $commission)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $commission->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $commission->order_id }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ ucfirst($commission->type) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                ฿{{ number_format($commission->amount, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                    {{ $commission->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                    {{ $commission->status === 'approved' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $commission->status === 'paid' ? 'bg-blue-100 text-blue-800' : '' }}">
                                    {{ ucfirst($commission->status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <span class="text-4xl mb-2">📭</span>
                                    <span>ยังไม่มีข้อมูลคอมมิชชั่น</span>
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

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.map(d => d.month),
            datasets: [{
                label: 'คอมมิชชั่น (฿)',
                data: chartData.map(d => d.total),
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '฿' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
</script>
@endif
@endsection
