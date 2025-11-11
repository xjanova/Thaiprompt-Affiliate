@extends('layouts.admin')

@section('title', 'วิเคราะห์คุกกี้')

@section('content')
<div class="space-y-6" x-data="{ dateRange: '{{ $dateRange }}' }">
    <!-- Header -->
    <div class="bg-gradient-to-r from-cyan-600 via-blue-600 to-indigo-600 rounded-xl shadow-xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold mb-2 flex items-center">
                    <i class="fas fa-cookie-bite mr-3"></i>
                    วิเคราะห์คุกกี้และผู้เยี่ยมชม
                </h1>
                <p class="text-cyan-100">ติดตามและวิเคราะห์พฤติกรรมผู้ใช้งานเว็บไซต์</p>
            </div>
            <select x-model="dateRange"
                    @change="window.location.href = '?range=' + dateRange"
                    class="bg-white text-indigo-600 px-4 py-2 rounded-lg font-semibold focus:ring-2 focus:ring-white">
                <option value="today">วันนี้</option>
                <option value="7days">7 วันที่แล้ว</option>
                <option value="30days">30 วันที่แล้ว</option>
                <option value="90days">90 วันที่แล้ว</option>
            </select>
        </div>
    </div>

    <!-- Main Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-2">
                <i class="fas fa-users text-4xl opacity-75"></i>
                <div class="text-right">
                    <p class="text-sm opacity-90">ผู้เยี่ยมชม</p>
                    <p class="text-3xl font-bold">{{ number_format($stats['total_visitors']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-2">
                <i class="fas fa-desktop text-4xl opacity-75"></i>
                <div class="text-right">
                    <p class="text-sm opacity-90">เซสชั่น</p>
                    <p class="text-3xl font-bold">{{ number_format($stats['total_sessions']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-2">
                <i class="fas fa-eye text-4xl opacity-75"></i>
                <div class="text-right">
                    <p class="text-sm opacity-90">หน้าเพจ</p>
                    <p class="text-3xl font-bold">{{ number_format($stats['total_page_views']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-yellow-500 to-orange-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-2">
                <i class="fas fa-clock text-4xl opacity-75"></i>
                <div class="text-right">
                    <p class="text-sm opacity-90">เวลาเฉลี่ย</p>
                    <p class="text-3xl font-bold">{{ number_format($stats['avg_session_duration'] / 60, 1) }}</p>
                    <p class="text-xs opacity-75">นาที</p>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-pink-500 to-rose-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-2">
                <i class="fas fa-check-circle text-4xl opacity-75"></i>
                <div class="text-right">
                    <p class="text-sm opacity-90">ยินยอม</p>
                    <p class="text-3xl font-bold">{{ number_format($stats['consent_rate'], 1) }}%</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                <i class="fas fa-mobile-alt text-blue-600 mr-2"></i>
                อุปกรณ์ที่ใช้เข้าชม
            </h2>
            <canvas id="deviceChart"></canvas>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                <i class="fas fa-globe text-green-600 mr-2"></i>
                เบราว์เซอร์
            </h2>
            <canvas id="browserChart"></canvas>
        </div>
    </div>

    <!-- Export -->
    <div class="bg-gradient-to-r from-cyan-600 to-blue-600 rounded-xl shadow-xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold mb-1">ส่งออกข้อมูล</h3>
                <p class="text-cyan-100 text-sm">ดาวน์โหลดรายงานการวิเคราะห์คุกกี้</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.cookie-analytics.export', ['format' => 'csv', 'range' => $dateRange]) }}"
                   class="bg-white text-cyan-600 px-6 py-3 rounded-lg hover:bg-cyan-50 transition-all font-semibold">
                    <i class="fas fa-file-csv mr-2"></i>CSV
                </a>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const deviceCtx = document.getElementById('deviceChart').getContext('2d');
    new Chart(deviceCtx, {
        type: 'pie',
        data: {
            labels: @json($deviceStats->pluck('device_type')),
            datasets: [{
                data: @json($deviceStats->pluck('count')),
                backgroundColor: ['rgba(59, 130, 246, 0.8)', 'rgba(16, 185, 129, 0.8)', 'rgba(251, 191, 36, 0.8)']
            }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { color: window.isDarkMode() ? '#e2e8f0' : '#374151' }}}}
    });

    const browserCtx = document.getElementById('browserChart').getContext('2d');
    new Chart(browserCtx, {
        type: 'bar',
        data: {
            labels: @json($browserStats->pluck('browser')),
            datasets: [{ label: 'ผู้ใช้', data: @json($browserStats->pluck('count')), backgroundColor: 'rgba(16, 185, 129, 0.8)' }]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true }}}
    });

    gsap.from('.space-y-6 > div', { opacity: 0, y: 20, duration: 0.5, stagger: 0.1 });
</script>
@endpush
@endsection
