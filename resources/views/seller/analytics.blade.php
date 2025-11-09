@extends('layouts.seller')

@section('title', 'วิเคราะห์ยอดขาย')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="bg-white rounded-2xl shadow-xl p-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">📊 วิเคราะห์ยอดขาย</h1>
                <p class="text-gray-600 mt-2">ข้อมูลการวิเคราะห์ร้านค้าของคุณ</p>
            </div>

            <!-- Date Range Selector -->
            <form method="GET" action="{{ route('seller.analytics') }}" class="flex gap-2">
                <input type="date" name="start_date" value="{{ $startDate }}"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                <input type="date" name="end_date" value="{{ $endDate }}"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    ค้นหา
                </button>
            </form>
        </div>
    </div>

    <!-- Real-time Stats -->
    <div class="bg-gradient-to-r from-purple-600 to-blue-600 rounded-2xl shadow-xl p-6 text-white">
        <h2 class="text-xl font-bold mb-4">📈 สถิติแบบเรียลไทม์ (วันนี้)</h2>
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4">
                <div class="text-sm opacity-80">ผู้เยี่ยมชมออนไลน์</div>
                <div class="text-3xl font-bold mt-2">{{ number_format($realTimeStats['current_active_visitors']) }}</div>
            </div>
            <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4">
                <div class="text-sm opacity-80">เข้าชมวันนี้</div>
                <div class="text-3xl font-bold mt-2">{{ number_format($realTimeStats['page_views_today']) }}</div>
            </div>
            <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4">
                <div class="text-sm opacity-80">ผู้เยี่ยมชมไม่ซ้ำ</div>
                <div class="text-3xl font-bold mt-2">{{ number_format($realTimeStats['unique_visitors_today']) }}</div>
            </div>
            <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4">
                <div class="text-sm opacity-80">ออเดอร์วันนี้</div>
                <div class="text-3xl font-bold mt-2">{{ number_format($realTimeStats['orders_today']) }}</div>
            </div>
            <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4">
                <div class="text-sm opacity-80">รายได้วันนี้</div>
                <div class="text-3xl font-bold mt-2">฿{{ number_format($realTimeStats['revenue_today'], 2) }}</div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Page Views -->
        <div class="bg-white rounded-2xl shadow-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">การเข้าชมทั้งหมด</p>
                    <p class="text-3xl font-bold text-gray-800 mt-2">{{ number_format($summary['total_page_views']) }}</p>
                </div>
                <div class="w-14 h-14 bg-blue-100 rounded-full flex items-center justify-center text-2xl">
                    👁️
                </div>
            </div>
        </div>

        <!-- Unique Visitors -->
        <div class="bg-white rounded-2xl shadow-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">ผู้เยี่ยมชมไม่ซ้ำ</p>
                    <p class="text-3xl font-bold text-gray-800 mt-2">{{ number_format($summary['total_unique_visitors']) }}</p>
                </div>
                <div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center text-2xl">
                    👥
                </div>
            </div>
        </div>

        <!-- Total Orders -->
        <div class="bg-white rounded-2xl shadow-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">ออเดอร์ทั้งหมด</p>
                    <p class="text-3xl font-bold text-gray-800 mt-2">{{ number_format($summary['total_orders']) }}</p>
                </div>
                <div class="w-14 h-14 bg-yellow-100 rounded-full flex items-center justify-center text-2xl">
                    🛒
                </div>
            </div>
        </div>

        <!-- Total Revenue -->
        <div class="bg-white rounded-2xl shadow-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">รายได้ทั้งหมด</p>
                    <p class="text-3xl font-bold text-gray-800 mt-2">฿{{ number_format($summary['total_revenue'], 2) }}</p>
                </div>
                <div class="w-14 h-14 bg-purple-100 rounded-full flex items-center justify-center text-2xl">
                    💰
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-2xl shadow-xl p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">อัตราการแปลง</h3>
            <div class="text-4xl font-bold text-blue-600">{{ number_format($summary['avg_conversion_rate'], 2) }}%</div>
            <p class="text-sm text-gray-600 mt-2">จากผู้เยี่ยมชมเป็นลูกค้า</p>
        </div>

        <div class="bg-white rounded-2xl shadow-xl p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">มูลค่าเฉลี่ยต่อออเดอร์</h3>
            <div class="text-4xl font-bold text-green-600">฿{{ number_format($summary['avg_order_value'], 2) }}</div>
            <p class="text-sm text-gray-600 mt-2">ค่าเฉลี่ยต่อครั้งที่สั่งซื้อ</p>
        </div>

        <div class="bg-white rounded-2xl shadow-xl p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">อัตราตีกลับ</h3>
            <div class="text-4xl font-bold text-orange-600">{{ number_format($summary['avg_bounce_rate'], 2) }}%</div>
            <p class="text-sm text-gray-600 mt-2">ผู้เยี่ยมชมที่ออกทันที</p>
        </div>
    </div>

    <!-- Charts -->
    <div class="bg-white rounded-2xl shadow-xl p-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">📈 กราฟแสดงข้อมูล</h2>

        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-700 mb-4">การเข้าชมและผู้เยี่ยมชม</h3>
            <canvas id="visitorChart" height="80"></canvas>
        </div>

        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-700 mb-4">ออเดอร์และรายได้</h3>
            <canvas id="revenueChart" height="80"></canvas>
        </div>
    </div>

    <!-- Daily Analytics Table -->
    <div class="bg-white rounded-2xl shadow-xl p-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">📅 ข้อมูลรายวัน</h2>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">วันที่</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">การเข้าชม</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ผู้เยี่ยมชม</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ออเดอร์</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">รายได้</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">อัตราแปลง</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($dailyAnalytics as $day)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ \Carbon\Carbon::parse($day->date)->format('d M Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ number_format($day->page_views) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ number_format($day->unique_visitors) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ number_format($day->orders_count) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            ฿{{ number_format($day->total_sales, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                {{ $day->conversion_rate >= 5 ? 'bg-green-100 text-green-800' : ($day->conversion_rate >= 2 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                {{ number_format($day->conversion_rate, 2) }}%
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                            ยังไม่มีข้อมูล - ข้อมูลจะถูกรวบรวมโดยอัตโนมัติทุกวัน
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Visitor Chart
    const visitorCtx = document.getElementById('visitorChart').getContext('2d');
    new Chart(visitorCtx, {
        type: 'line',
        data: {
            labels: @json($chartData['dates']),
            datasets: [
                {
                    label: 'การเข้าชม',
                    data: @json($chartData['page_views']),
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4
                },
                {
                    label: 'ผู้เยี่ยมชมไม่ซ้ำ',
                    data: @json($chartData['unique_visitors']),
                    borderColor: 'rgb(16, 185, 129)',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4
                }
            ]
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

    // Revenue Chart
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    new Chart(revenueCtx, {
        type: 'bar',
        data: {
            labels: @json($chartData['dates']),
            datasets: [
                {
                    label: 'ออเดอร์',
                    data: @json($chartData['orders']),
                    backgroundColor: 'rgba(251, 191, 36, 0.8)',
                    yAxisID: 'y'
                },
                {
                    label: 'รายได้ (฿)',
                    data: @json($chartData['revenue']),
                    backgroundColor: 'rgba(147, 51, 234, 0.8)',
                    yAxisID: 'y1'
                }
            ]
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
                    type: 'linear',
                    display: true,
                    position: 'left',
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'ออเดอร์'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'รายได้ (฿)'
                    },
                    grid: {
                        drawOnChartArea: false,
                    }
                }
            }
        }
    });
</script>
@endpush
@endsection
