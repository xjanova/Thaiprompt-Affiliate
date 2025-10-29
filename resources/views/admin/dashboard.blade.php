@extends('layouts.admin')

@section('title', 'แดชบอร์ด')

@section('content')
<div class="space-y-6">
    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">ผู้ใช้ทั้งหมด</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $stats['total_users'] }}</p>
                </div>
                <div class="text-4xl">👥</div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Affiliates</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $stats['total_affiliates'] }}</p>
                </div>
                <div class="text-4xl">🌐</div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">คอมมิชชั่นทั้งหมด</p>
                    <p class="text-3xl font-bold text-gray-900">{{ number_format($stats['total_commissions'], 2) }}฿</p>
                </div>
                <div class="text-4xl">💰</div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">รอดำเนินการ</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $stats['pending_commissions'] }}</p>
                </div>
                <div class="text-4xl">⏳</div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">รายได้รายเดือน</h3>
            <canvas id="revenueChart"></canvas>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">สถานะคอมมิชชั่น</h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">รอดำเนินการ</span>
                    <span class="font-semibold text-yellow-600">{{ $stats['pending_commissions'] }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">อนุมัติแล้ว</span>
                    <span class="font-semibold text-green-600">{{ $stats['approved_commissions'] }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">Affiliates ที่ใช้งาน</span>
                    <span class="font-semibold text-blue-600">{{ $stats['active_affiliates'] }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Commissions -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">คอมมิชชั่นล่าสุด</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Affiliate</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">จำนวน</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ประเภท</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สถานะ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">วันที่</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($recentCommissions as $commission)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $commission->affiliate->user->name }}</div>
                                <div class="text-sm text-gray-500">{{ $commission->affiliate->user->email }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ number_format($commission->amount, 2) }}฿
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $commission->type }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                    @if($commission->status === 'pending') bg-yellow-100 text-yellow-800
                                    @elseif($commission->status === 'approved') bg-green-100 text-green-800
                                    @elseif($commission->status === 'paid') bg-blue-100 text-blue-800
                                    @else bg-red-100 text-red-800
                                    @endif">
                                    {{ $commission->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $commission->created_at->format('d/m/Y H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">ยังไม่มีข้อมูล</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top Affiliates -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Affiliates ยอดนิยม</h3>
        <div class="space-y-4">
            @forelse($topAffiliates as $affiliate)
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div>
                        <p class="font-medium text-gray-900">{{ $affiliate->user->name }}</p>
                        <p class="text-sm text-gray-500">{{ $affiliate->user->email }}</p>
                    </div>
                    <div class="text-right">
                        <p class="font-semibold text-gray-900">{{ number_format($affiliate->total_earnings, 2) }}฿</p>
                        <p class="text-sm text-gray-500">{{ $affiliate->total_referrals }} referrals</p>
                    </div>
                </div>
            @empty
                <p class="text-center text-gray-500">ยังไม่มีข้อมูล</p>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Revenue Chart
    const ctx = document.getElementById('revenueChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode($monthlyRevenue->pluck('month')->reverse()) !!},
                datasets: [{
                    label: 'รายได้ (฿)',
                    data: {!! json_encode($monthlyRevenue->pluck('total')->reverse()) !!},
                    borderColor: 'rgb(99, 102, 241)',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
</script>
@endpush
