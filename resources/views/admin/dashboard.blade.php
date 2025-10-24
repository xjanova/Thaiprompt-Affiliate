@extends('layouts.admin')

@section('title', 'Admin Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    @include('admin.widgets.stat-card', [
        'title' => 'ยอดขายวันนี้',
        'value' => '฿' . number_format($stats['today_sales'] ?? 0, 2),
        'icon' => 'money',
        'color' => 'green',
        'change' => '+12%'
    ])

    @include('admin.widgets.stat-card', [
        'title' => 'คำสั่งซื้อใหม่',
        'value' => $stats['pending_orders'] ?? 0,
        'icon' => 'shopping-bag',
        'color' => 'blue',
        'badge' => 'ต้องดำเนินการ'
    ])

    @include('admin.widgets.stat-card', [
        'title' => 'ผู้ใช้ทั้งหมด',
        'value' => number_format($stats['total_users'] ?? 0),
        'icon' => 'users',
        'color' => 'purple',
        'change' => '+5%'
    ])

    @include('admin.widgets.stat-card', [
        'title' => 'รายได้เดือนนี้',
        'value' => '฿' . number_format($stats['month_revenue'] ?? 0, 2),
        'icon' => 'chart',
        'color' => 'yellow',
        'change' => '+18%'
    ])
</div>

<!-- Charts Row -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Sales Chart -->
    <div class="bg-white rounded-lg shadow-lg p-6">
        <h3 class="text-lg font-semibold mb-4">ยอดขายรายวัน (7 วันล่าสุด)</h3>
        <canvas id="salesChart" class="w-full" height="300"></canvas>
    </div>

    <!-- Orders Chart -->
    <div class="bg-white rounded-lg shadow-lg p-6">
        <h3 class="text-lg font-semibold mb-4">สถานะคำสั่งซื้อ</h3>
        <canvas id="ordersChart" class="w-full" height="300"></canvas>
    </div>
</div>

<!-- Tables Row -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Recent Orders -->
    <div class="bg-white rounded-lg shadow-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-semibold">คำสั่งซื้อล่าสุด</h3>
                <a href="{{ route('admin.orders') }}" class="text-sm text-indigo-600 hover:text-indigo-500">ดูทั้งหมด</a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">คำสั่งซื้อ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ลูกค้า</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ยอดรวม</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">สถานะ</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($recentOrders ?? [] as $order)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                #{{ $order->order_number }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $order->user->name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                ฿{{ number_format($order->total_amount, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                    @if($order->status === 'delivered') bg-green-100 text-green-800
                                    @elseif($order->status === 'cancelled') bg-red-100 text-red-800
                                    @else bg-yellow-100 text-yellow-800 @endif">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-gray-500">ไม่มีคำสั่งซื้อ</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top Products -->
    <div class="bg-white rounded-lg shadow-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-semibold">สินค้ายอดนิยม</h3>
                <a href="{{ route('admin.products') }}" class="text-sm text-indigo-600 hover:text-indigo-500">ดูทั้งหมด</a>
            </div>
        </div>
        <div class="p-6">
            @forelse($topProducts ?? [] as $product)
                <div class="flex items-center justify-between py-3 border-b last:border-b-0">
                    <div class="flex items-center space-x-3">
                        <img src="{{ $product->featured_image ?? asset('images/placeholder.jpg') }}"
                             alt="{{ $product->name }}"
                             class="w-12 h-12 rounded object-cover">
                        <div>
                            <p class="font-medium text-gray-900">{{ $product->name }}</p>
                            <p class="text-sm text-gray-500">{{ $product->vendor->name }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-medium text-gray-900">{{ $product->sales_count ?? 0 }} ขาย</p>
                        <p class="text-sm text-gray-500">฿{{ number_format($product->price, 2) }}</p>
                    </div>
                </div>
            @empty
                <p class="text-gray-500 text-center py-8">ยังไม่มีข้อมูล</p>
            @endforelse
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
// Sales Chart
const salesCtx = document.getElementById('salesChart').getContext('2d');
new Chart(salesCtx, {
    type: 'line',
    data: {
        labels: {!! json_encode($salesChart['labels'] ?? ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']) !!},
        datasets: [{
            label: 'ยอดขาย (บาท)',
            data: {!! json_encode($salesChart['data'] ?? [12000, 19000, 15000, 25000, 22000, 30000, 28000]) !!},
            borderColor: 'rgb(79, 70, 229)',
            backgroundColor: 'rgba(79, 70, 229, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Orders Chart
const ordersCtx = document.getElementById('ordersChart').getContext('2d');
new Chart(ordersCtx, {
    type: 'doughnut',
    data: {
        labels: ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'],
        datasets: [{
            data: {!! json_encode($ordersChart ?? [25, 15, 30, 20, 10]) !!},
            backgroundColor: [
                'rgb(251, 191, 36)',
                'rgb(59, 130, 246)',
                'rgb(139, 92, 246)',
                'rgb(34, 197, 94)',
                'rgb(239, 68, 68)'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
</script>
@endpush
@endsection
