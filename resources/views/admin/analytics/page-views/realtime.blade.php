{{--
/**
 * หน้า Real-time Page Views Analytics
 *
 * แสดงสถิติการเข้าชมแบบเรียลไทม์
 */
--}}

@extends('layouts.admin-v3')

@section('title', 'Real-time Analytics')

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                <span class="text-3xl">⚡</span>
                Real-time Analytics
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                ดูการเข้าชมเว็บแบบเรียลไทม์
            </p>
        </div>

        <a href="{{ route('admin.analytics.page-views.index') }}"
           class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg flex items-center gap-2 transition">
            <i class="fas fa-arrow-left"></i>
            <span>กลับ</span>
        </a>
    </div>

    {{-- Live Counter --}}
    <div class="bg-gradient-to-r from-red-500 to-pink-500 rounded-xl shadow-lg p-8 mb-8 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-lg opacity-90">ผู้ใช้ที่ Active ตอนนี้</p>
                <p class="text-6xl font-bold mt-2" id="active-counter">-</p>
                <p class="text-sm opacity-75 mt-2">อัพเดททุก 10 วินาที</p>
            </div>
            <div class="w-24 h-24 bg-white/20 rounded-full flex items-center justify-center">
                <span class="text-5xl animate-pulse">🔴</span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Last Hour Chart --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <span>📈</span>
                Page Views ใน 1 ชั่วโมงล่าสุด
            </h3>
            <div class="h-64">
                <canvas id="hourlyChart"></canvas>
            </div>
        </div>

        {{-- Current Active Pages --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <span>🔥</span>
                    หน้าที่กำลังดูตอนนี้
                </h3>
            </div>
            <div class="max-h-80 overflow-y-auto" id="current-pages">
                <div class="p-6 text-center text-gray-500 dark:text-gray-400">
                    กำลังโหลด...
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
let hourlyChart = null;

document.addEventListener('DOMContentLoaded', function() {
    // Initialize Chart
    const ctx = document.getElementById('hourlyChart').getContext('2d');
    hourlyChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                label: 'Page Views',
                data: [],
                backgroundColor: 'rgba(239, 68, 68, 0.7)',
                borderColor: '#ef4444',
                borderWidth: 1,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    // Fetch data immediately and set interval
    fetchRealtimeData();
    setInterval(fetchRealtimeData, 10000);
});

function fetchRealtimeData() {
    fetch('{{ route("admin.analytics.page-views.realtime-data") }}')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update counter
                document.getElementById('active-counter').textContent = data.data.active_now;

                // Update chart
                const hourlyData = data.data.last_hour;
                hourlyChart.data.labels = hourlyData.map(d => d.minute + 'm');
                hourlyChart.data.datasets[0].data = hourlyData.map(d => d.views);
                hourlyChart.update();

                // Update current pages
                const pagesHtml = data.data.current_pages.map(page => `
                    <div class="px-6 py-3 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        <span class="text-sm text-gray-900 dark:text-white truncate">${page.path}</span>
                        <span class="text-sm font-bold text-red-600 dark:text-red-400">${page.views}</span>
                    </div>
                `).join('');

                document.getElementById('current-pages').innerHTML = pagesHtml || '<div class="p-6 text-center text-gray-500">ไม่มีการเข้าชมในขณะนี้</div>';
            }
        })
        .catch(err => console.error('Error fetching realtime data:', err));
}
</script>
@endpush
@endsection
