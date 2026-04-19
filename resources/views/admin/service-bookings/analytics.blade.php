@extends('layouts.admin-v3')

@section('title', $pageTitle ?? 'รายงานและสถิติการจองบริการ')

@section('content')
<div class="max-w-7xl mx-auto">
    {{-- Header --}}
    <div class="mb-6 p-6 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 shadow-xl">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-orange-600 to-red-600 bg-clip-text text-transparent">
                    <i class="fas fa-chart-line mr-3"></i>{{ $pageTitle ?? 'รายงานและสถิติการจองบริการ' }}
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">วิเคราะห์ข้อมูลการจองบริการ ยอดขาย และประสิทธิภาพ</p>
            </div>
            <a href="{{ route('admin.service-bookings.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">
                <i class="fas fa-arrow-left mr-2"></i>กลับ
            </a>
        </div>
    </div>

    {{-- Date Filter --}}
    <div class="mb-6 p-4 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 shadow-xl">
        <form action="{{ route('admin.service-bookings.analytics') }}" method="GET" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">วันที่เริ่มต้น</label>
                <input type="date" name="start_date" value="{{ $startDate }}" class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">วันที่สิ้นสุด</label>
                <input type="date" name="end_date" value="{{ $endDate }}" class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700">
            </div>
            <button type="submit" class="px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">
                <i class="fas fa-filter mr-2"></i>กรอง
            </button>
        </form>
    </div>

    {{-- Stats Overview --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
        <div class="p-6 rounded-xl backdrop-blur-xl bg-gradient-to-br from-blue-500/90 to-blue-600/90 text-white shadow-xl">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-80">จองทั้งหมด</p>
                    <p class="text-3xl font-bold">{{ number_format($stats['total_bookings'] ?? 0) }}</p>
                </div>
                <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center">
                    <i class="fas fa-calendar-check text-2xl"></i>
                </div>
            </div>
        </div>
        <div class="p-6 rounded-xl backdrop-blur-xl bg-gradient-to-br from-green-500/90 to-green-600/90 text-white shadow-xl">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-80">รายได้รวม</p>
                    <p class="text-3xl font-bold">฿{{ number_format($stats['total_revenue'] ?? 0, 0) }}</p>
                </div>
                <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center">
                    <i class="fas fa-baht-sign text-2xl"></i>
                </div>
            </div>
        </div>
        <div class="p-6 rounded-xl backdrop-blur-xl bg-gradient-to-br from-emerald-500/90 to-emerald-600/90 text-white shadow-xl">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-80">เสร็จสมบูรณ์</p>
                    <p class="text-3xl font-bold">{{ number_format($stats['completed_bookings'] ?? 0) }}</p>
                </div>
                <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center">
                    <i class="fas fa-check-circle text-2xl"></i>
                </div>
            </div>
        </div>
        <div class="p-6 rounded-xl backdrop-blur-xl bg-gradient-to-br from-red-500/90 to-red-600/90 text-white shadow-xl">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-80">ยกเลิก</p>
                    <p class="text-3xl font-bold">{{ number_format($stats['cancelled_bookings'] ?? 0) }}</p>
                </div>
                <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center">
                    <i class="fas fa-times-circle text-2xl"></i>
                </div>
            </div>
        </div>
        <div class="p-6 rounded-xl backdrop-blur-xl bg-gradient-to-br from-yellow-500/90 to-orange-500/90 text-white shadow-xl">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-80">คะแนนเฉลี่ย</p>
                    <p class="text-3xl font-bold">{{ number_format($stats['average_rating'] ?? 0, 1) }}</p>
                </div>
                <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center">
                    <i class="fas fa-star text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Daily Bookings Chart --}}
        <div class="p-6 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 shadow-xl">
            <h2 class="text-xl font-bold mb-4 text-gray-900 dark:text-white">
                <i class="fas fa-chart-bar mr-2 text-blue-500"></i>การจองรายวัน
            </h2>
            <div class="h-64">
                <canvas id="dailyBookingsChart"></canvas>
            </div>
        </div>

        {{-- Daily Revenue Chart --}}
        <div class="p-6 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 shadow-xl">
            <h2 class="text-xl font-bold mb-4 text-gray-900 dark:text-white">
                <i class="fas fa-chart-line mr-2 text-green-500"></i>รายได้รายวัน
            </h2>
            <div class="h-64">
                <canvas id="dailyRevenueChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Status Distribution & Top Data --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        {{-- Status Distribution --}}
        <div class="p-6 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 shadow-xl">
            <h2 class="text-xl font-bold mb-4 text-gray-900 dark:text-white">
                <i class="fas fa-pie-chart mr-2 text-purple-500"></i>สัดส่วนตามสถานะ
            </h2>
            <div class="h-64">
                <canvas id="statusChart"></canvas>
            </div>
        </div>

        {{-- Top Services --}}
        <div class="p-6 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 shadow-xl">
            <h2 class="text-xl font-bold mb-4 text-gray-900 dark:text-white">
                <i class="fas fa-trophy mr-2 text-yellow-500"></i>บริการยอดนิยม
            </h2>
            <div class="space-y-3">
                @forelse($topServices as $index => $item)
                    <div class="flex items-center justify-between p-2 rounded-lg {{ $index == 0 ? 'bg-yellow-50 dark:bg-yellow-900/20' : 'hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                        <div class="flex items-center gap-3">
                            <span class="w-6 h-6 flex items-center justify-center rounded-full {{ $index == 0 ? 'bg-yellow-500 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400' }} text-sm font-bold">
                                {{ $index + 1 }}
                            </span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $item->service->name ?? 'ไม่ระบุ' }}</span>
                        </div>
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ $item->count }} ครั้ง</span>
                    </div>
                @empty
                    <p class="text-center text-gray-500 dark:text-gray-400 py-4">ไม่มีข้อมูล</p>
                @endforelse
            </div>
        </div>

        {{-- Top Providers --}}
        <div class="p-6 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 shadow-xl">
            <h2 class="text-xl font-bold mb-4 text-gray-900 dark:text-white">
                <i class="fas fa-star mr-2 text-orange-500"></i>ผู้ให้บริการยอดนิยม
            </h2>
            <div class="space-y-3">
                @forelse($topProviders as $index => $item)
                    <div class="flex items-center justify-between p-2 rounded-lg {{ $index == 0 ? 'bg-orange-50 dark:bg-orange-900/20' : 'hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                        <div class="flex items-center gap-3">
                            <span class="w-6 h-6 flex items-center justify-center rounded-full {{ $index == 0 ? 'bg-orange-500 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400' }} text-sm font-bold">
                                {{ $index + 1 }}
                            </span>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $item->provider->name ?? 'ไม่ระบุ' }}</p>
                                <p class="text-xs text-gray-500">
                                    <i class="fas fa-star text-yellow-500"></i>
                                    {{ number_format($item->avg_rating ?? 0, 1) }}
                                </p>
                            </div>
                        </div>
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ $item->count }} งาน</span>
                    </div>
                @empty
                    <p class="text-center text-gray-500 dark:text-gray-400 py-4">ไม่มีข้อมูล</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ข้อมูลจาก Backend
    const dailyBookings = @json($dailyBookings);
    const dailyRevenue = @json($dailyRevenue);
    const statusStats = @json($statusStats);

    // สีสำหรับ dark mode
    const isDark = document.documentElement.classList.contains('dark');
    const textColor = isDark ? '#e5e7eb' : '#374151';
    const gridColor = isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';

    // Daily Bookings Chart
    const bookingsCtx = document.getElementById('dailyBookingsChart').getContext('2d');
    new Chart(bookingsCtx, {
        type: 'bar',
        data: {
            labels: Object.keys(dailyBookings),
            datasets: [{
                label: 'จำนวนการจอง',
                data: Object.values(dailyBookings),
                backgroundColor: 'rgba(59, 130, 246, 0.8)',
                borderColor: 'rgb(59, 130, 246)',
                borderWidth: 1,
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: {
                    grid: { color: gridColor },
                    ticks: { color: textColor }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: gridColor },
                    ticks: { color: textColor }
                }
            }
        }
    });

    // Daily Revenue Chart
    const revenueCtx = document.getElementById('dailyRevenueChart').getContext('2d');
    new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: Object.keys(dailyRevenue),
            datasets: [{
                label: 'รายได้ (฿)',
                data: Object.values(dailyRevenue),
                borderColor: 'rgb(34, 197, 94)',
                backgroundColor: 'rgba(34, 197, 94, 0.2)',
                fill: true,
                tension: 0.4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: {
                    grid: { color: gridColor },
                    ticks: { color: textColor }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: gridColor },
                    ticks: {
                        color: textColor,
                        callback: function(value) {
                            return '฿' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });

    // Status Distribution Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    const statusLabels = {
        'pending': 'รอดำเนินการ',
        'paid': 'ชำระแล้ว',
        'waiting_provider': 'รอผู้ให้บริการ',
        'provider_accepted': 'รับงานแล้ว',
        'provider_on_way': 'กำลังเดินทาง',
        'in_progress': 'กำลังดำเนินการ',
        'completed': 'เสร็จสิ้น',
        'cancelled': 'ยกเลิก',
    };
    const statusColors = {
        'pending': '#fbbf24',
        'paid': '#60a5fa',
        'waiting_provider': '#a78bfa',
        'provider_accepted': '#34d399',
        'provider_on_way': '#2dd4bf',
        'in_progress': '#f472b6',
        'completed': '#22c55e',
        'cancelled': '#ef4444',
    };

    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: Object.keys(statusStats).map(k => statusLabels[k] || k),
            datasets: [{
                data: Object.values(statusStats),
                backgroundColor: Object.keys(statusStats).map(k => statusColors[k] || '#9ca3af'),
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: textColor }
                }
            }
        }
    });
});
</script>
@endpush
