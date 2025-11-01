@extends('layouts.admin')

@section('title', 'Security Analytics')

@section('content')
<div x-data="{ period: {{ $days }} }" class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">📊 Security Analytics</h1>
            <p class="text-gray-600 mt-1">ข้อมูลสถิติและวิเคราะห์ความปลอดภัย</p>
        </div>
        <div class="flex gap-3">
            <!-- Period Selector -->
            <select x-model="period" @change="window.location.href = '{{ route('admin.security.analytics') }}?days=' + period"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                <option value="7">7 วันล่าสุด</option>
                <option value="30">30 วันล่าสุด</option>
                <option value="60">60 วันล่าสุด</option>
                <option value="90">90 วันล่าสุด</option>
            </select>

            <!-- Export Buttons -->
            <a href="{{ route('admin.security.export.logs') }}"
               class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition flex items-center gap-2">
                <span>📥</span>
                <span>Export CSV</span>
            </a>
            <a href="{{ route('admin.security.export.analytics', ['days' => $days]) }}"
               class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                <span>📊</span>
                <span>Export JSON</span>
            </a>
        </div>
    </div>

    <!-- Auto-Ban Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm font-medium">Auto-Bans ทั้งหมด</p>
                    <p class="text-3xl font-bold mt-2">{{ number_format($autoBanStats['total_auto_bans']) }}</p>
                </div>
                <div class="text-5xl opacity-50">🚫</div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-red-100 text-sm font-medium">Auto-Bans วันนี้</p>
                    <p class="text-3xl font-bold mt-2">{{ number_format($autoBanStats['auto_bans_today']) }}</p>
                </div>
                <div class="text-5xl opacity-50">🔥</div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-orange-100 text-sm font-medium">Auto-Bans สัปดาห์นี้</p>
                    <p class="text-3xl font-bold mt-2">{{ number_format($autoBanStats['auto_bans_this_week']) }}</p>
                </div>
                <div class="text-5xl opacity-50">📅</div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-indigo-100 text-sm font-medium">IP ที่ถูกแบนอยู่</p>
                    <p class="text-3xl font-bold mt-2">{{ number_format($autoBanStats['currently_banned']) }}</p>
                </div>
                <div class="text-5xl opacity-50">⛔</div>
            </div>
        </div>
    </div>

    <!-- Event Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">📈 Total Events</h3>
            <p class="text-4xl font-bold text-indigo-600">{{ number_format($analytics['total_events']) }}</p>
            <p class="text-sm text-gray-500 mt-2">ในช่วง {{ $days }} วันที่ผ่านมา</p>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">🔴 Critical Events</h3>
            <p class="text-4xl font-bold text-red-600">{{ number_format($analytics['by_severity']['critical'] ?? 0) }}</p>
            <p class="text-sm text-gray-500 mt-2">ระดับ Critical</p>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">🛡️ VPN/Proxy Detected</h3>
            <p class="text-4xl font-bold text-orange-600">{{ number_format($analytics['vpn_proxy_count']) }}</p>
            <p class="text-sm text-gray-500 mt-2">การเข้าถึงผ่าน VPN/Proxy</p>
        </div>
    </div>

    <!-- Timeline Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- All Events Timeline -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">📊 Security Events Timeline</h3>
            <canvas id="timelineAllChart" height="300"></canvas>
        </div>

        <!-- Failed Logins Timeline -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">🔐 Failed Login Attempts</h3>
            <canvas id="timelineFailedLoginsChart" height="300"></canvas>
        </div>
    </div>

    <!-- Event Type & Severity Distribution -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Event Types -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">📋 Event Types Distribution</h3>
            <canvas id="eventTypesChart" height="300"></canvas>
        </div>

        <!-- Severity Levels -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">⚠️ Severity Levels</h3>
            <canvas id="severityChart" height="300"></canvas>
        </div>
    </div>

    <!-- Geographic Distribution -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top Countries -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">🌍 Top Countries</h3>
            <canvas id="countriesChart" height="300"></canvas>
        </div>

        <!-- Country List -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">🗺️ Geographic Distribution</h3>
            <div class="space-y-3 max-h-96 overflow-y-auto">
                @foreach($analytics['by_country'] as $country)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">{{ \App\Services\IpIntelligenceService::getCountryFlag($country['country_code']) }}</span>
                        <div>
                            <p class="font-medium text-gray-900">{{ $country['country_name'] }}</p>
                            <p class="text-sm text-gray-500">{{ $country['country_code'] }}</p>
                        </div>
                    </div>
                    <span class="text-lg font-bold text-indigo-600">{{ number_format($country['count']) }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- OS & Browser Distribution -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Operating Systems -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">💻 Operating Systems</h3>
            <canvas id="osChart" height="300"></canvas>
        </div>

        <!-- Browsers -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">🌐 Browsers</h3>
            <canvas id="browserChart" height="300"></canvas>
        </div>
    </div>

    <!-- Device Types -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">📱 Device Types</h3>
        <canvas id="deviceChart" height="150"></canvas>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Chart.js default configuration
    Chart.defaults.color = '#6B7280';
    Chart.defaults.font.family = "'Inter', sans-serif";

    // Timeline - All Events
    new Chart(document.getElementById('timelineAllChart'), {
        type: 'line',
        data: {
            labels: {!! json_encode(array_keys($timelineAll)) !!},
            datasets: [{
                label: 'Security Events',
                data: {!! json_encode(array_values($timelineAll)) !!},
                borderColor: 'rgb(99, 102, 241)',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                tension: 0.4,
                fill: true
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

    // Timeline - Failed Logins
    new Chart(document.getElementById('timelineFailedLoginsChart'), {
        type: 'line',
        data: {
            labels: {!! json_encode(array_keys($timelineFailedLogins)) !!},
            datasets: [{
                label: 'Failed Logins',
                data: {!! json_encode(array_values($timelineFailedLogins)) !!},
                borderColor: 'rgb(239, 68, 68)',
                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                tension: 0.4,
                fill: true
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

    // Event Types
    new Chart(document.getElementById('eventTypesChart'), {
        type: 'doughnut',
        data: {
            labels: {!! json_encode(array_keys($analytics['by_event_type'])) !!},
            datasets: [{
                data: {!! json_encode(array_values($analytics['by_event_type'])) !!},
                backgroundColor: [
                    'rgb(239, 68, 68)',
                    'rgb(251, 146, 60)',
                    'rgb(234, 179, 8)',
                    'rgb(34, 197, 94)',
                    'rgb(59, 130, 246)',
                    'rgb(168, 85, 247)',
                    'rgb(236, 72, 153)',
                    'rgb(20, 184, 166)',
                    'rgb(148, 163, 184)',
                    'rgb(100, 116, 139)',
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right'
                }
            }
        }
    });

    // Severity Levels
    new Chart(document.getElementById('severityChart'), {
        type: 'pie',
        data: {
            labels: ['Critical', 'High', 'Medium', 'Low'],
            datasets: [{
                data: [
                    {{ $analytics['by_severity']['critical'] ?? 0 }},
                    {{ $analytics['by_severity']['high'] ?? 0 }},
                    {{ $analytics['by_severity']['medium'] ?? 0 }},
                    {{ $analytics['by_severity']['low'] ?? 0 }}
                ],
                backgroundColor: [
                    'rgb(239, 68, 68)',
                    'rgb(251, 146, 60)',
                    'rgb(234, 179, 8)',
                    'rgb(59, 130, 246)',
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right'
                }
            }
        }
    });

    // Countries
    new Chart(document.getElementById('countriesChart'), {
        type: 'bar',
        data: {
            labels: {!! json_encode(array_map(function($c) { return $c['country_name']; }, $analytics['by_country'])) !!},
            datasets: [{
                label: 'Events',
                data: {!! json_encode(array_map(function($c) { return $c['count']; }, $analytics['by_country'])) !!},
                backgroundColor: 'rgb(99, 102, 241)',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: { beginAtZero: true }
            }
        }
    });

    // Operating Systems
    new Chart(document.getElementById('osChart'), {
        type: 'doughnut',
        data: {
            labels: {!! json_encode(array_keys($analytics['by_os'])) !!},
            datasets: [{
                data: {!! json_encode(array_values($analytics['by_os'])) !!},
                backgroundColor: [
                    'rgb(59, 130, 246)',
                    'rgb(168, 85, 247)',
                    'rgb(236, 72, 153)',
                    'rgb(251, 146, 60)',
                    'rgb(34, 197, 94)',
                    'rgb(234, 179, 8)',
                    'rgb(20, 184, 166)',
                    'rgb(239, 68, 68)',
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right'
                }
            }
        }
    });

    // Browsers
    new Chart(document.getElementById('browserChart'), {
        type: 'doughnut',
        data: {
            labels: {!! json_encode(array_keys($analytics['by_browser'])) !!},
            datasets: [{
                data: {!! json_encode(array_values($analytics['by_browser'])) !!},
                backgroundColor: [
                    'rgb(234, 179, 8)',
                    'rgb(251, 146, 60)',
                    'rgb(59, 130, 246)',
                    'rgb(34, 197, 94)',
                    'rgb(239, 68, 68)',
                    'rgb(168, 85, 247)',
                    'rgb(236, 72, 153)',
                    'rgb(20, 184, 166)',
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right'
                }
            }
        }
    });

    // Device Types
    new Chart(document.getElementById('deviceChart'), {
        type: 'bar',
        data: {
            labels: {!! json_encode(array_keys($analytics['by_device'])) !!},
            datasets: [{
                label: 'Devices',
                data: {!! json_encode(array_values($analytics['by_device'])) !!},
                backgroundColor: [
                    'rgb(99, 102, 241)',
                    'rgb(168, 85, 247)',
                    'rgb(236, 72, 153)',
                    'rgb(251, 146, 60)',
                ]
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
});
</script>
@endpush
@endsection
