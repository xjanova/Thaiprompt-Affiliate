@extends('layouts.seller')

@section('title', 'แดชบอร์ดผู้ขาย')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    <!-- Welcome Header with Premium Gradient -->
    <div class="bg-gradient-to-r from-blue-600 via-cyan-600 to-teal-600 rounded-2xl shadow-2xl p-8 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-40 h-40 bg-white opacity-10 rounded-full"></div>
        <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-32 h-32 bg-white opacity-10 rounded-full"></div>
        <div class="absolute top-1/2 right-1/4 w-24 h-24 bg-white opacity-5 rounded-full"></div>
        <div class="relative z-10">
            <h1 class="text-3xl md:text-4xl font-bold mb-2">🏪 สวัสดี, {{ $user->name }}!</h1>
            <p class="text-blue-100 text-lg">ยินดีต้อนรับสู่ระบบจัดการร้านค้า - {{ now()->format('d/m/Y') }}</p>
            <div class="mt-4 inline-flex items-center px-4 py-2 bg-white bg-opacity-20 rounded-xl">
                <span class="text-sm font-semibold">ระบบผู้ขาย</span>
            </div>
        </div>
    </div>

    <!-- Premium Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Revenue -->
        <div class="relative overflow-hidden bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition duration-300">
            <div class="absolute top-0 right-0 -mt-4 -mr-4">
                <div class="w-24 h-24 bg-white opacity-10 rounded-full"></div>
            </div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-5xl">💰</div>
                    @if($salesGrowth != 0)
                        <span class="px-2 py-1 bg-white bg-opacity-20 rounded-lg text-xs font-semibold">
                            {{ $salesGrowth > 0 ? '↑' : '↓' }} {{ number_format(abs($salesGrowth), 1) }}%
                        </span>
                    @endif
                </div>
                <p class="text-white text-opacity-80 text-sm mb-1">รายได้รวม</p>
                <p class="text-3xl md:text-4xl font-bold">฿{{ number_format($totalRevenue, 0) }}</p>
                <p class="text-xs text-white text-opacity-70 mt-2">รายได้ทั้งหมด</p>
            </div>
        </div>

        <!-- Total Sales -->
        <div class="relative overflow-hidden bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition duration-300">
            <div class="absolute top-0 right-0 -mt-4 -mr-4">
                <div class="w-24 h-24 bg-white opacity-10 rounded-full"></div>
            </div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-5xl">🛒</div>
                    <span class="px-2 py-1 bg-white bg-opacity-20 rounded-lg text-xs font-semibold">
                        ยอดขาย
                    </span>
                </div>
                <p class="text-white text-opacity-80 text-sm mb-1">ยอดขายทั้งหมด</p>
                <p class="text-3xl md:text-4xl font-bold">{{ number_format($totalSales) }}</p>
                <p class="text-xs text-white text-opacity-70 mt-2">{{ number_format($completedSales) }} สำเร็จ</p>
            </div>
        </div>

        <!-- Pending Orders -->
        <div class="relative overflow-hidden bg-gradient-to-br from-yellow-500 to-orange-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition duration-300">
            <div class="absolute top-0 right-0 -mt-4 -mr-4">
                <div class="w-24 h-24 bg-white opacity-10 rounded-full"></div>
            </div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-5xl">⏳</div>
                    <span class="px-2 py-1 bg-white bg-opacity-20 rounded-lg text-xs font-semibold">
                        รอดำเนินการ
                    </span>
                </div>
                <p class="text-white text-opacity-80 text-sm mb-1">คำสั่งซื้อรอดำเนินการ</p>
                <p class="text-3xl md:text-4xl font-bold">{{ number_format($pendingSales) }}</p>
                <p class="text-xs text-white text-opacity-70 mt-2">ต้องดำเนินการ</p>
            </div>
        </div>

        <!-- Total Products -->
        <div class="relative overflow-hidden bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition duration-300">
            <div class="absolute top-0 right-0 -mt-4 -mr-4">
                <div class="w-24 h-24 bg-white opacity-10 rounded-full"></div>
            </div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-5xl">📦</div>
                    <span class="px-2 py-1 bg-white bg-opacity-20 rounded-lg text-xs font-semibold">
                        {{ $activeProducts }} ใช้งาน
                    </span>
                </div>
                <p class="text-white text-opacity-80 text-sm mb-1">สินค้าทั้งหมด</p>
                <p class="text-3xl md:text-4xl font-bold">{{ number_format($totalProducts) }}</p>
                <p class="text-xs text-white text-opacity-70 mt-2">{{ $outOfStockProducts }} หมด</p>
            </div>
        </div>
    </div>

    <!-- Revenue Chart -->
    <div class="bg-white rounded-2xl shadow-xl p-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
            <span class="text-3xl mr-3">📊</span>
            รายได้รายเดือน (12 เดือนล่าสุด)
        </h2>
        <div class="h-80">
            <canvas id="revenueChart"></canvas>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="bg-white rounded-2xl shadow-xl p-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
            <span class="text-3xl mr-3">📋</span>
            คำสั่งซื้อล่าสุด
        </h2>
        @if($recentOrders->isEmpty())
            <div class="text-center py-12">
                <div class="text-6xl mb-4">🛍️</div>
                <p class="text-gray-500 text-lg">ยังไม่มีคำสั่งซื้อ</p>
                <p class="text-gray-400 text-sm mt-2">คำสั่งซื้อจะแสดงที่นี่เมื่อมีลูกค้าสั่งซื้อสินค้า</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">หมายเลขคำสั่งซื้อ</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">วันที่</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ยอดรวม</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สถานะ</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <!-- Orders will be listed here -->
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Top Selling Products -->
    <div class="bg-white rounded-2xl shadow-xl p-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
            <span class="text-3xl mr-3">🏆</span>
            สินค้าขายดี
        </h2>
        @if($topProducts->isEmpty())
            <div class="text-center py-12">
                <div class="text-6xl mb-4">📦</div>
                <p class="text-gray-500 text-lg">ยังไม่มีข้อมูลสินค้าขายดี</p>
                <p class="text-gray-400 text-sm mt-2">ข้อมูลสินค้าขายดีจะแสดงที่นี่</p>
            </div>
        @else
            <!-- Top products will be listed here -->
        @endif
    </div>
</div>

@push('scripts')
<script>
const colors = window.getChartColors();
const borderColor = window.isDarkMode() ? '#1e293b' : '#fff';
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Revenue Chart
    const ctx = document.getElementById('revenueChart').getContext('2d');
    const revenueData = @json($monthlyRevenue);

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: revenueData.map(item => item.month),
            datasets: [{
                label: 'รายได้ (บาท)',
                data: revenueData.map(item => item.total),
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
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
                    beginAtZero: true,
                    ticks: {
                        color: colors.textColor,
                        callback: function(value) {
                            return '฿' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
</script>
@endpush
@endsection
