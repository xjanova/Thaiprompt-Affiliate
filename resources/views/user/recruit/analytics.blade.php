@extends('layouts.user-arrow-x')

@section('title', 'สถิติการตลาด')

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
@endpush

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 via-purple-50 to-pink-50 dark:from-gray-900 dark:via-purple-900 dark:to-gray-900 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Header --}}
        <div class="mb-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 bg-clip-text text-transparent">
                        สถิติการตลาด
                    </h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        วิเคราะห์ผลการตลาดหน้า Recruit ของคุณ
                    </p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('user.marketing.recruit.index') }}"
                       class="inline-flex items-center px-6 py-3 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 border border-gray-200 dark:border-gray-700">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        กลับไปหน้าจัดการ
                    </a>
                </div>
            </div>
        </div>

        {{-- Time Period Selector --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-6 mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    ช่วงเวลา
                </h3>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('user.marketing.recruit.analytics', ['days' => 7]) }}"
                       class="px-4 py-2 rounded-lg font-medium transition-all duration-200 {{ $days == 7 ? 'bg-gradient-to-r from-blue-500 to-purple-600 text-white shadow-lg' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                        7 วัน
                    </a>
                    <a href="{{ route('user.marketing.recruit.analytics', ['days' => 30]) }}"
                       class="px-4 py-2 rounded-lg font-medium transition-all duration-200 {{ $days == 30 ? 'bg-gradient-to-r from-blue-500 to-purple-600 text-white shadow-lg' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                        30 วัน
                    </a>
                    <a href="{{ route('user.marketing.recruit.analytics', ['days' => 90]) }}"
                       class="px-4 py-2 rounded-lg font-medium transition-all duration-200 {{ $days == 90 ? 'bg-gradient-to-r from-blue-500 to-purple-600 text-white shadow-lg' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                        90 วัน
                    </a>
                </div>
            </div>
        </div>

        {{-- Conversion Funnel --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-6 mb-8">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">
                กระบวนการ Conversion ({{ $days }} วันล่าสุด)
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                {{-- Step 1: Visits --}}
                <div class="relative">
                    <div class="text-center">
                        <div class="mx-auto w-24 h-24 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center shadow-lg mb-4">
                            <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </div>
                        <h4 class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($funnel['visits']) }}</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">การเข้าชม</p>
                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-2">100%</p>
                    </div>
                    <div class="hidden md:block absolute top-1/2 -right-3 transform -translate-y-1/2">
                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </div>

                {{-- Step 2: Clicked Register --}}
                <div class="relative">
                    <div class="text-center">
                        <div class="mx-auto w-24 h-24 rounded-full bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center shadow-lg mb-4">
                            <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"></path>
                            </svg>
                        </div>
                        <h4 class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($funnel['clicked_register']) }}</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">คลิกสมัคร</p>
                        @php
                            $clickRate = $funnel['visits'] > 0 ? ($funnel['clicked_register'] / $funnel['visits'] * 100) : 0;
                        @endphp
                        <p class="text-xs text-green-600 dark:text-green-400 font-medium mt-2">{{ number_format($clickRate, 1) }}%</p>
                    </div>
                    <div class="hidden md:block absolute top-1/2 -right-3 transform -translate-y-1/2">
                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </div>

                {{-- Step 3: Leads --}}
                <div class="relative">
                    <div class="text-center">
                        <div class="mx-auto w-24 h-24 rounded-full bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center shadow-lg mb-4">
                            <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                        </div>
                        <h4 class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($funnel['leads']) }}</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">ผู้มุ่งหวัง</p>
                        @php
                            $leadRate = $funnel['visits'] > 0 ? ($funnel['leads'] / $funnel['visits'] * 100) : 0;
                        @endphp
                        <p class="text-xs text-purple-600 dark:text-purple-400 font-medium mt-2">{{ number_format($leadRate, 1) }}%</p>
                    </div>
                    <div class="hidden md:block absolute top-1/2 -right-3 transform -translate-y-1/2">
                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </div>

                {{-- Step 4: Conversions --}}
                <div class="relative">
                    <div class="text-center">
                        <div class="mx-auto w-24 h-24 rounded-full bg-gradient-to-br from-pink-400 to-pink-600 flex items-center justify-center shadow-lg mb-4">
                            <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h4 class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($funnel['conversions']) }}</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">สมัครสำเร็จ</p>
                        @php
                            $conversionRate = $funnel['visits'] > 0 ? ($funnel['conversions'] / $funnel['visits'] * 100) : 0;
                        @endphp
                        <p class="text-xs text-pink-600 dark:text-pink-400 font-medium mt-2">{{ number_format($conversionRate, 1) }}%</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Charts Row --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            {{-- Visits & Leads Chart --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">
                    การเข้าชมและผู้มุ่งหวังรายวัน
                </h3>
                <canvas id="visitsLeadsChart" class="w-full" style="max-height: 300px;"></canvas>
            </div>

            {{-- Device Breakdown Chart --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">
                    การเข้าชมตามอุปกรณ์
                </h3>
                <div class="flex items-center justify-center" style="height: 300px;">
                    <canvas id="deviceChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Top Referrers --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">
                แหล่งอ้างอิงยอดนิยม (Top 10)
            </h3>
            @if($topReferrers->count() > 0)
                <div class="space-y-4">
                    @foreach($topReferrers as $index => $referrer)
                        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-200">
                            <div class="flex items-center flex-1 min-w-0">
                                <div class="flex-shrink-0 w-8 h-8 rounded-full bg-gradient-to-br from-blue-400 to-purple-500 flex items-center justify-center text-white font-bold text-sm mr-4">
                                    {{ $index + 1 }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                        {{ parse_url($referrer->referrer_url, PHP_URL_HOST) ?? $referrer->referrer_url }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                        {{ Str::limit($referrer->referrer_url, 60) }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex-shrink-0 ml-4">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gradient-to-r from-blue-500 to-purple-600 text-white shadow-lg">
                                    {{ number_format($referrer->count) }} ครั้ง
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                        </svg>
                    </div>
                    <p class="text-gray-500 dark:text-gray-400">
                        ยังไม่มีข้อมูลแหล่งอ้างอิง
                    </p>
                </div>
            @endif
        </div>

    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // สีสำหรับ Dark Mode
    const isDarkMode = document.documentElement.classList.contains('dark');
    const textColor = isDarkMode ? '#e5e7eb' : '#374151';
    const gridColor = isDarkMode ? '#374151' : '#e5e7eb';

    // Chart 1: Visits & Leads Line Chart
    const visitsLeadsCtx = document.getElementById('visitsLeadsChart').getContext('2d');

    // เตรียมข้อมูล
    const dates = @json($visitsPerDay->pluck('date'));
    const visitsData = @json($visitsPerDay->pluck('count'));

    // สร้าง Map สำหรับ leads
    const leadsMap = new Map();
    @json($leadsPerDay).forEach(item => {
        leadsMap.set(item.date, item.count);
    });

    // จับคู่ leads กับ dates
    const leadsData = dates.map(date => leadsMap.get(date) || 0);

    new Chart(visitsLeadsCtx, {
        type: 'line',
        data: {
            labels: dates.map(date => {
                const d = new Date(date);
                return d.toLocaleDateString('th-TH', { day: 'numeric', month: 'short' });
            }),
            datasets: [
                {
                    label: 'การเข้าชม',
                    data: visitsData,
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: 'rgb(59, 130, 246)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                },
                {
                    label: 'ผู้มุ่งหวัง',
                    data: leadsData,
                    borderColor: 'rgb(168, 85, 247)',
                    backgroundColor: 'rgba(168, 85, 247, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: 'rgb(168, 85, 247)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        color: textColor,
                        font: {
                            size: 12,
                            family: "'Inter', sans-serif"
                        },
                        padding: 15,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    padding: 12,
                    cornerRadius: 8,
                    displayColors: true,
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.parsed.y.toLocaleString() + ' ครั้ง';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: textColor,
                        font: {
                            size: 11
                        },
                        callback: function(value) {
                            return value.toLocaleString();
                        }
                    },
                    grid: {
                        color: gridColor,
                        drawBorder: false
                    }
                },
                x: {
                    ticks: {
                        color: textColor,
                        font: {
                            size: 11
                        }
                    },
                    grid: {
                        display: false
                    }
                }
            }
        }
    });

    // Chart 2: Device Pie Chart
    const deviceCtx = document.getElementById('deviceChart').getContext('2d');

    const deviceData = @json($deviceBreakdown);
    const deviceLabels = deviceData.map(d => {
        const labels = {
            'mobile': 'มือถือ',
            'desktop': 'คอมพิวเตอร์',
            'tablet': 'แท็บเล็ต'
        };
        return labels[d.device_type] || d.device_type || 'ไม่ระบุ';
    });
    const deviceCounts = deviceData.map(d => d.count);

    new Chart(deviceCtx, {
        type: 'doughnut',
        data: {
            labels: deviceLabels,
            datasets: [{
                data: deviceCounts,
                backgroundColor: [
                    'rgba(59, 130, 246, 0.8)',   // Blue
                    'rgba(168, 85, 247, 0.8)',   // Purple
                    'rgba(236, 72, 153, 0.8)',   // Pink
                ],
                borderColor: [
                    'rgb(59, 130, 246)',
                    'rgb(168, 85, 247)',
                    'rgb(236, 72, 153)',
                ],
                borderWidth: 2,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        color: textColor,
                        font: {
                            size: 12,
                            family: "'Inter', sans-serif"
                        },
                        padding: 15,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return context.label + ': ' + context.parsed.toLocaleString() + ' (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });
});
</script>
@endpush
@endsection
