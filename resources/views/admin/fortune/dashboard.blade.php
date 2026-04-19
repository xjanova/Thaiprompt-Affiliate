@extends('layouts.admin-v3')

@section('title', 'Dashboard ระบบดูดวง')

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                🔮 Dashboard ระบบดูดวง
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">ภาพรวมสถิติและประสิทธิภาพของแม่หมอจันทรา</p>
        </div>
        <div class="flex gap-3 mt-4 md:mt-0">
            <a href="{{ route('admin.fortune.settings.index') }}"
               class="flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm hover:shadow transition text-sm">
                <span>⚙️</span> ตั้งค่า
            </a>
            <a href="{{ route('admin.fortune.readings.index') }}"
               class="flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-xl shadow hover:shadow-lg transition text-sm">
                <span>📊</span> ดูทั้งหมด
            </a>
        </div>
    </div>

    {{-- AI Status Bar --}}
    <div class="bg-gradient-to-r {{ $aiStatus['enabled'] ? 'from-green-500 to-emerald-600' : 'from-red-500 to-pink-600' }} rounded-xl shadow-lg p-4 mb-6 text-white flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="text-2xl">{{ $aiStatus['enabled'] ? '✅' : '⛔' }}</span>
            <div>
                <p class="font-bold">{{ $aiStatus['enabled'] ? 'ระบบเปิดใช้งาน' : 'ระบบปิดอยู่' }}</p>
                <p class="text-sm opacity-90">
                    AI: {{ strtoupper($aiStatus['provider']) }} / {{ $aiStatus['model'] }}
                    {{ $aiStatus['has_key'] ? '🔑' : '⚠️ ไม่มี API Key' }}
                </p>
            </div>
        </div>
        <a href="{{ route('admin.fortune.settings.index') }}" class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg text-sm font-medium transition">
            จัดการ →
        </a>
    </div>

    {{-- Stats Grid 4 columns --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        {{-- Total Readings --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5 border border-gray-100 dark:border-gray-700 hover:shadow-xl transition-all hover:-translate-y-0.5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">คำทำนายทั้งหมด</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($stats['total']) }}</p>
                    <p class="text-xs text-green-600 mt-1">+{{ number_format($stats['today']) }} วันนี้</p>
                </div>
                <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl flex items-center justify-center shadow">
                    <span class="text-white text-lg">🔮</span>
                </div>
            </div>
        </div>

        {{-- Unique Users --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5 border border-gray-100 dark:border-gray-700 hover:shadow-xl transition-all hover:-translate-y-0.5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">ผู้ใช้ทั้งหมด</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($stats['unique_users']) }}</p>
                    <p class="text-xs text-blue-600 mt-1">{{ $stats['this_week'] }} ครั้ง/สัปดาห์</p>
                </div>
                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl flex items-center justify-center shadow">
                    <span class="text-white text-lg">👥</span>
                </div>
            </div>
        </div>

        {{-- Conversion Rate --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5 border border-gray-100 dark:border-gray-700 hover:shadow-xl transition-all hover:-translate-y-0.5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">Basic → Deep</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ $stats['conversion_rate'] }}%</p>
                    <p class="text-xs text-orange-600 mt-1">{{ number_format($stats['deep_count']) }} deep readings</p>
                </div>
                <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-red-600 rounded-xl flex items-center justify-center shadow">
                    <span class="text-white text-lg">📈</span>
                </div>
            </div>
        </div>

        {{-- Revenue --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5 border border-gray-100 dark:border-gray-700 hover:shadow-xl transition-all hover:-translate-y-0.5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">รายได้รวม</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">฿{{ number_format($stats['total_revenue'], 0) }}</p>
                    <p class="text-xs text-green-600 mt-1">+฿{{ number_format($stats['today_revenue'], 0) }} วันนี้</p>
                </div>
                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center shadow">
                    <span class="text-white text-lg">💰</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Revenue Summary Cards --}}
    <div class="grid grid-cols-3 gap-4 mb-8">
        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/30 dark:to-indigo-900/30 rounded-xl p-4 border border-blue-100 dark:border-blue-800">
            <p class="text-blue-600 dark:text-blue-400 text-sm font-medium">รายได้วันนี้</p>
            <p class="text-2xl font-bold text-blue-800 dark:text-blue-200">฿{{ number_format($stats['today_revenue'], 0) }}</p>
        </div>
        <div class="bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/30 dark:to-pink-900/30 rounded-xl p-4 border border-purple-100 dark:border-purple-800">
            <p class="text-purple-600 dark:text-purple-400 text-sm font-medium">สัปดาห์นี้</p>
            <p class="text-2xl font-bold text-purple-800 dark:text-purple-200">฿{{ number_format($stats['week_revenue'], 0) }}</p>
        </div>
        <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/30 dark:to-emerald-900/30 rounded-xl p-4 border border-green-100 dark:border-green-800">
            <p class="text-green-600 dark:text-green-400 text-sm font-medium">เดือนนี้</p>
            <p class="text-2xl font-bold text-green-800 dark:text-green-200">฿{{ number_format($stats['month_revenue'], 0) }}</p>
        </div>
    </div>

    {{-- Charts Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        {{-- 7-Day Readings Chart --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                📊 คำทำนาย 7 วันล่าสุด
            </h3>
            <canvas id="readingsChart" height="200"></canvas>
        </div>

        {{-- Revenue Chart --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                💰 รายได้ 7 วันล่าสุด
            </h3>
            <canvas id="revenueChart" height="200"></canvas>
        </div>
    </div>

    {{-- Pie Charts Row --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        {{-- Basic vs Deep --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
            <h3 class="font-bold text-gray-900 dark:text-white mb-4">🎯 Basic vs Deep</h3>
            <canvas id="typeChart" height="200"></canvas>
            <div class="flex justify-center gap-6 mt-3 text-sm">
                <span class="flex items-center gap-1"><span class="w-3 h-3 bg-blue-500 rounded-full inline-block"></span> Basic: {{ number_format($stats['basic_count']) }}</span>
                <span class="flex items-center gap-1"><span class="w-3 h-3 bg-purple-500 rounded-full inline-block"></span> Deep: {{ number_format($stats['deep_count']) }}</span>
            </div>
        </div>

        {{-- Free vs Paid --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
            <h3 class="font-bold text-gray-900 dark:text-white mb-4">💎 ฟรี vs จ่ายเงิน</h3>
            <canvas id="paidChart" height="200"></canvas>
            <div class="flex justify-center gap-6 mt-3 text-sm">
                <span class="flex items-center gap-1"><span class="w-3 h-3 bg-green-500 rounded-full inline-block"></span> ฟรี: {{ number_format($stats['free_count']) }}</span>
                <span class="flex items-center gap-1"><span class="w-3 h-3 bg-yellow-500 rounded-full inline-block"></span> จ่าย: {{ number_format($stats['paid_count']) }}</span>
            </div>
        </div>

        {{-- Category Distribution --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
            <h3 class="font-bold text-gray-900 dark:text-white mb-4">📂 หมวดยอดนิยม</h3>
            @if($categoryStats->isNotEmpty())
                <canvas id="categoryChart" height="200"></canvas>
            @else
                <div class="flex items-center justify-center h-48 text-gray-400">
                    <p>ยังไม่มีข้อมูลหมวดหมู่</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Quick Navigation --}}
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3 mb-8">
        <a href="{{ route('admin.fortune.playground') }}"
           class="flex flex-col items-center gap-2 p-4 bg-gradient-to-br from-purple-500 to-pink-500 text-white rounded-xl shadow hover:shadow-lg transition hover:-translate-y-0.5">
            <span class="text-2xl">🎮</span>
            <span class="text-sm font-semibold">AI Playground</span>
        </a>
        <a href="{{ route('admin.fortune.channels.index') }}"
           class="flex flex-col items-center gap-2 p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm hover:shadow transition hover:-translate-y-0.5">
            <span class="text-2xl">📡</span>
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">ช่องทาง</span>
        </a>
        <a href="{{ route('admin.fortune.categories.index') }}"
           class="flex flex-col items-center gap-2 p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm hover:shadow transition hover:-translate-y-0.5">
            <span class="text-2xl">📂</span>
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">หมวดหมู่</span>
        </a>
        <a href="{{ route('admin.fortune.users.index') }}"
           class="flex flex-col items-center gap-2 p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm hover:shadow transition hover:-translate-y-0.5">
            <span class="text-2xl">👥</span>
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">ผู้ใช้</span>
        </a>
        <a href="{{ route('admin.fortune.billing.index') }}"
           class="flex flex-col items-center gap-2 p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm hover:shadow transition hover:-translate-y-0.5">
            <span class="text-2xl">💳</span>
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">การเงิน</span>
        </a>
        <a href="{{ route('admin.fortune.marketing.index') }}"
           class="flex flex-col items-center gap-2 p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm hover:shadow transition hover:-translate-y-0.5">
            <span class="text-2xl">📢</span>
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">การตลาด</span>
        </a>
    </div>

    {{-- Top Users & Recent Readings --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        {{-- Top 5 Users --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    🏆 Top 5 ผู้ใช้ดูดวงบ่อย
                </h3>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($topUsers as $i => $user)
                    <div class="px-6 py-3 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <div class="flex items-center gap-3">
                            <span class="w-8 h-8 rounded-full bg-gradient-to-br {{ ['from-yellow-400 to-yellow-600', 'from-gray-300 to-gray-500', 'from-orange-400 to-orange-600', 'from-blue-400 to-blue-600', 'from-green-400 to-green-600'][$i] ?? 'from-gray-400 to-gray-600' }} flex items-center justify-center text-white text-sm font-bold shadow">
                                {{ $i + 1 }}
                            </span>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white text-sm">{{ $user->facebook_user_name ?: 'ไม่ทราบชื่อ' }}</p>
                                <p class="text-xs text-gray-500">ล่าสุด: {{ Carbon\Carbon::parse($user->last_reading)->diffForHumans() }}</p>
                            </div>
                        </div>
                        <span class="text-sm font-bold text-purple-600 dark:text-purple-400">{{ $user->reading_count }} ครั้ง</span>
                    </div>
                @empty
                    <div class="px-6 py-8 text-center text-gray-400">ยังไม่มีข้อมูล</div>
                @endforelse
            </div>
        </div>

        {{-- Recent 10 Readings --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    🕐 คำทำนายล่าสุด
                </h3>
                <a href="{{ route('admin.fortune.readings.index') }}" class="text-sm text-purple-600 hover:text-purple-700 font-medium">ดูทั้งหมด →</a>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($recentReadings as $reading)
                    <a href="{{ route('admin.fortune.readings.show', $reading->id) }}"
                       class="block px-6 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="w-8 h-8 rounded-full bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center text-white text-xs font-bold shadow flex-shrink-0">
                                    {{ mb_substr($reading->facebook_user_name ?: '?', 0, 1) }}
                                </span>
                                <div class="min-w-0">
                                    <p class="font-medium text-gray-900 dark:text-white text-sm truncate">{{ $reading->facebook_user_name ?: 'ไม่ทราบชื่อ' }}</p>
                                    <p class="text-xs text-gray-500 truncate">
                                        {{ mb_substr(($reading->questions[0] ?? 'ไม่มีคำถาม'), 0, 40) }}...
                                    </p>
                                </div>
                            </div>
                            <div class="text-right flex-shrink-0 ml-2">
                                @if($reading->reading_type === 'deep')
                                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-purple-100 dark:bg-purple-900/50 text-purple-800 dark:text-purple-300">
                                        Deep
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300">
                                        Basic
                                    </span>
                                @endif
                                <p class="text-xs text-gray-400 mt-1">{{ $reading->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="px-6 py-8 text-center text-gray-400">ยังไม่มีคำทำนาย</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Token Usage & Additional Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border border-gray-100 dark:border-gray-700">
            <p class="text-gray-500 dark:text-gray-400 text-xs">Avg Tokens/Reading</p>
            <p class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['avg_tokens']) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border border-gray-100 dark:border-gray-700">
            <p class="text-gray-500 dark:text-gray-400 text-xs">Basic Readings</p>
            <p class="text-xl font-bold text-blue-600">{{ number_format($stats['basic_count']) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border border-gray-100 dark:border-gray-700">
            <p class="text-gray-500 dark:text-gray-400 text-xs">Deep Readings</p>
            <p class="text-xl font-bold text-purple-600">{{ number_format($stats['deep_count']) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border border-gray-100 dark:border-gray-700">
            <p class="text-gray-500 dark:text-gray-400 text-xs">Paid Readings</p>
            <p class="text-xl font-bold text-green-600">{{ number_format($stats['paid_count']) }}</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const isDark = document.documentElement.classList.contains('dark');
    const textColor = isDark ? '#d1d5db' : '#374151';
    const gridColor = isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.06)';

    // 7-Day Readings Chart
    new Chart(document.getElementById('readingsChart'), {
        type: 'bar',
        data: {
            labels: @json($chartLabels),
            datasets: [
                {
                    label: 'Basic',
                    data: @json($chartBasic),
                    backgroundColor: 'rgba(59, 130, 246, 0.7)',
                    borderRadius: 6,
                },
                {
                    label: 'Deep',
                    data: @json($chartDeep),
                    backgroundColor: 'rgba(147, 51, 234, 0.7)',
                    borderRadius: 6,
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { labels: { color: textColor, font: { size: 12 } } }
            },
            scales: {
                x: { stacked: true, ticks: { color: textColor }, grid: { color: gridColor } },
                y: { stacked: true, beginAtZero: true, ticks: { color: textColor, stepSize: 1 }, grid: { color: gridColor } }
            }
        }
    });

    // Revenue Chart
    new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: {
            labels: @json($chartLabels),
            datasets: [{
                label: 'รายได้ (฿)',
                data: @json($chartRevenue),
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 5,
                pointBackgroundColor: '#10b981',
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { labels: { color: textColor } }
            },
            scales: {
                x: { ticks: { color: textColor }, grid: { color: gridColor } },
                y: { beginAtZero: true, ticks: { color: textColor, callback: v => '฿' + v.toLocaleString() }, grid: { color: gridColor } }
            }
        }
    });

    // Type Pie Chart
    new Chart(document.getElementById('typeChart'), {
        type: 'doughnut',
        data: {
            labels: ['Basic', 'Deep'],
            datasets: [{
                data: [{{ $stats['basic_count'] }}, {{ $stats['deep_count'] }}],
                backgroundColor: ['#3b82f6', '#9333ea'],
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            cutout: '65%',
        }
    });

    // Paid Pie Chart
    new Chart(document.getElementById('paidChart'), {
        type: 'doughnut',
        data: {
            labels: ['ฟรี', 'จ่ายเงิน'],
            datasets: [{
                data: [{{ $stats['free_count'] }}, {{ $stats['paid_count'] }}],
                backgroundColor: ['#22c55e', '#eab308'],
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            cutout: '65%',
        }
    });

    // Category Chart
    @if($categoryStats->isNotEmpty())
    const categoryColors = ['#8b5cf6', '#ec4899', '#f59e0b', '#10b981', '#3b82f6', '#ef4444'];
    new Chart(document.getElementById('categoryChart'), {
        type: 'doughnut',
        data: {
            labels: @json($categoryStats->keys()->values()),
            datasets: [{
                data: @json($categoryStats->values()),
                backgroundColor: categoryColors.slice(0, {{ $categoryStats->count() }}),
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: textColor, font: { size: 10 }, padding: 8 }
                }
            },
            cutout: '55%',
        }
    });
    @endif
});
</script>
@endpush
