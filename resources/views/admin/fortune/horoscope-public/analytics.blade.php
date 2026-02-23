{{-- สถิติระบบดูดวงสาธารณะ --}}
@extends('layouts.admin')

@section('title', $pageTitle)

@section('content')
<div class="container mx-auto px-4 py-8" x-data="horoscopeAnalytics()">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-1">📊 สถิติระบบดูดวงสาธารณะ</h1>
            <p class="text-gray-500 dark:text-gray-400 text-sm">ภาพรวมการใช้งานระบบดูดวง Frontend</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.fortune.horoscope-public.settings') }}"
               class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                ⚙️ ตั้งค่า
            </a>
        </div>
    </div>

    {{-- ==================== Stats Cards — ดวงรายวัน ==================== --}}
    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
        ⭐ ดวงรายวัน
    </h2>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl shadow-lg p-5 text-white">
            <div class="flex items-center justify-between mb-2">
                <span class="text-indigo-100 text-sm">Predictions ทั้งหมด</span>
                <span class="text-2xl">📝</span>
            </div>
            <div class="text-2xl font-bold">{{ number_format($dailyStats['total_predictions']) }}</div>
        </div>
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-5 text-white">
            <div class="flex items-center justify-between mb-2">
                <span class="text-blue-100 text-sm">วันนี้</span>
                <span class="text-2xl">📅</span>
            </div>
            <div class="text-2xl font-bold">{{ number_format($dailyStats['generated_today']) }}</div>
        </div>
        <div class="bg-gradient-to-br from-cyan-500 to-cyan-600 rounded-xl shadow-lg p-5 text-white">
            <div class="flex items-center justify-between mb-2">
                <span class="text-cyan-100 text-sm">Views รวม</span>
                <span class="text-2xl">👁️</span>
            </div>
            <div class="text-2xl font-bold">{{ number_format($dailyStats['total_views']) }}</div>
        </div>
        <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl shadow-lg p-5 text-white">
            <div class="flex items-center justify-between mb-2">
                <span class="text-emerald-100 text-sm">ราศีเปิดใช้</span>
                <span class="text-2xl">✅</span>
            </div>
            <div class="text-2xl font-bold">{{ $dailyStats['zodiacs_active'] }}/12</div>
        </div>
    </div>

    {{-- ==================== Stats Cards — ทำนายฝัน ==================== --}}
    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
        💭 ทำนายฝัน
    </h2>
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-5 text-white">
            <span class="text-purple-100 text-xs">ทั้งหมด</span>
            <div class="text-xl font-bold mt-1">{{ number_format($dreamStats['total_readings']) }}</div>
        </div>
        <div class="bg-gradient-to-br from-violet-500 to-violet-600 rounded-xl shadow-lg p-5 text-white">
            <span class="text-violet-100 text-xs">วันนี้</span>
            <div class="text-xl font-bold mt-1">{{ number_format($dreamStats['today_readings']) }}</div>
        </div>
        <div class="bg-gradient-to-br from-fuchsia-500 to-fuchsia-600 rounded-xl shadow-lg p-5 text-white">
            <span class="text-fuchsia-100 text-xs">สัปดาห์นี้</span>
            <div class="text-xl font-bold mt-1">{{ number_format($dreamStats['week_readings']) }}</div>
        </div>
        <div class="bg-gradient-to-br from-pink-500 to-pink-600 rounded-xl shadow-lg p-5 text-white">
            <span class="text-pink-100 text-xs">Views รวม</span>
            <div class="text-xl font-bold mt-1">{{ number_format($dreamStats['total_views']) }}</div>
        </div>
        <div class="bg-gradient-to-br from-rose-500 to-rose-600 rounded-xl shadow-lg p-5 text-white">
            <span class="text-rose-100 text-xs">สัญลักษณ์</span>
            <div class="text-xl font-bold mt-1">{{ number_format($dreamStats['total_symbols']) }}</div>
        </div>
    </div>

    {{-- ==================== Stats Cards — เลขศาสตร์ ==================== --}}
    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
        🔢 เลขศาสตร์
    </h2>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl shadow-lg p-5 text-white">
            <span class="text-amber-100 text-xs">ทั้งหมด</span>
            <div class="text-xl font-bold mt-1">{{ number_format($numerologyStats['total_readings']) }}</div>
        </div>
        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg p-5 text-white">
            <span class="text-orange-100 text-xs">วันนี้</span>
            <div class="text-xl font-bold mt-1">{{ number_format($numerologyStats['today_readings']) }}</div>
        </div>
        <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl shadow-lg p-5 text-white">
            <span class="text-yellow-100 text-xs">สัปดาห์นี้</span>
            <div class="text-xl font-bold mt-1">{{ number_format($numerologyStats['week_readings']) }}</div>
        </div>
        <div class="bg-gradient-to-br from-lime-500 to-lime-600 rounded-xl shadow-lg p-5 text-white">
            <span class="text-lime-100 text-xs">Views รวม</span>
            <div class="text-xl font-bold mt-1">{{ number_format($numerologyStats['total_views']) }}</div>
        </div>
    </div>

    {{-- ==================== กราฟ 7 วัน ==================== --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-8">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">📈 กราฟการใช้งาน 7 วัน</h2>
            <div class="flex gap-2">
                <button @click="loadChart(7)" :class="chartDays === 7 ? 'bg-indigo-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                        class="px-3 py-1 rounded-lg text-xs font-medium transition">7 วัน</button>
                <button @click="loadChart(30)" :class="chartDays === 30 ? 'bg-indigo-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                        class="px-3 py-1 rounded-lg text-xs font-medium transition">30 วัน</button>
            </div>
        </div>
        <div class="h-64">
            <canvas id="usageChart"></canvas>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- ==================== ราศียอดนิยม ==================== --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">⭐ ราศียอดนิยม (7 วัน)</h2>
            @if($topZodiacs->isNotEmpty())
                <div class="space-y-3">
                    @foreach($topZodiacs as $idx => $item)
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-bold text-gray-400 w-6">{{ $idx + 1 }}</span>
                            <span class="text-lg">{{ $item->zodiacSign?->symbol_emoji ?? '⭐' }}</span>
                            <div class="flex-1">
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $item->zodiacSign?->name_th ?? 'ไม่ระบุ' }}</span>
                            </div>
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ number_format($item->total_views) }} views</span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 dark:text-gray-400 text-sm text-center py-4">ยังไม่มีข้อมูล</p>
            @endif
        </div>

        {{-- ==================== สัญลักษณ์ฝันยอดนิยม ==================== --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">💭 สัญลักษณ์ฝันยอดนิยม</h2>
            @if($topDreamSymbols->isNotEmpty())
                <div class="space-y-3">
                    @foreach($topDreamSymbols as $idx => $symbol)
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-bold text-gray-400 w-6">{{ $idx + 1 }}</span>
                            <span class="text-lg">{{ $symbol->category?->icon ?? '💭' }}</span>
                            <div class="flex-1">
                                <span class="text-sm font-medium text-gray-900 dark:text-white">ฝันเห็น{{ $symbol->keyword_th }}</span>
                            </div>
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ number_format($symbol->search_count) }} ครั้ง</span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 dark:text-gray-400 text-sm text-center py-4">ยังไม่มีข้อมูล</p>
            @endif
        </div>

        {{-- ==================== ประเภทเลขศาสตร์ ==================== --}}
        @if($numerologyTypes->isNotEmpty())
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">🔢 ประเภทเลขศาสตร์</h2>
            @php
                $typeLabels = [
                    'name' => '📝 วิเคราะห์ชื่อ',
                    'phone' => '📱 วิเคราะห์เบอร์โทร',
                    'license_plate' => '🚗 ทะเบียนรถ',
                    'id_card' => '🪪 เลขบัตรประชาชน',
                    'birthday' => '🎂 วันเกิด',
                ];
            @endphp
            <div class="space-y-3">
                @foreach($numerologyTypes as $type)
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-900 dark:text-white">{{ $typeLabels[$type->reading_type] ?? $type->reading_type }}</span>
                        <div class="flex items-center gap-2">
                            <div class="w-32 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                @php $pct = $numerologyStats['total_readings'] > 0 ? ($type->count / $numerologyStats['total_readings']) * 100 : 0; @endphp
                                <div class="bg-amber-500 h-2 rounded-full" style="width: {{ $pct }}%"></div>
                            </div>
                            <span class="text-sm text-gray-500 dark:text-gray-400 w-12 text-right">{{ number_format($type->count) }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
function horoscopeAnalytics() {
    return {
        chart: null,
        chartDays: 7,

        init() {
            this.renderChart(@json($chartData));
        },

        /**
         * โหลดข้อมูลกราฟจาก API
         */
        async loadChart(days) {
            this.chartDays = days;
            try {
                const res = await fetch(`{{ route('admin.fortune.horoscope-public.analytics.data') }}?days=${days}`);
                const json = await res.json();
                if (json.success) {
                    this.renderChart(json.data);
                }
            } catch (e) {
                console.error('โหลดข้อมูลกราฟผิดพลาด:', e);
            }
        },

        /**
         * วาดกราฟ Chart.js
         */
        renderChart(data) {
            const ctx = document.getElementById('usageChart');
            if (!ctx) return;

            if (this.chart) {
                this.chart.destroy();
            }

            const isDark = document.documentElement.classList.contains('dark');

            this.chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: data.datasets.map(ds => ({
                        ...ds,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 3,
                        pointHoverRadius: 6,
                        borderWidth: 2,
                    })),
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            labels: { color: isDark ? '#d1d5db' : '#374151' }
                        },
                    },
                    scales: {
                        x: {
                            ticks: { color: isDark ? '#9ca3af' : '#6b7280' },
                            grid: { color: isDark ? '#374151' : '#e5e7eb' },
                        },
                        y: {
                            beginAtZero: true,
                            ticks: { color: isDark ? '#9ca3af' : '#6b7280' },
                            grid: { color: isDark ? '#374151' : '#e5e7eb' },
                        },
                    },
                },
            });
        },
    };
}
</script>
@endsection
