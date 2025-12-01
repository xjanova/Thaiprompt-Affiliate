@extends('layouts.admin-v3')

@section('title', 'รายงานภารกิจดูคลิป')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                📈 รายงานภารกิจดูคลิป
            </h1>
            <p class="text-gray-600 dark:text-gray-400">วิเคราะห์ข้อมูลการทำภารกิจและรางวัล</p>
        </div>
        <a href="{{ route('admin.video-missions.index') }}"
           class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-white rounded-xl font-medium transition-all">
            ← กลับ Dashboard
        </a>
    </div>

    {{-- Date Filter --}}
    <div class="glass-fusion dark:bg-gray-800/50 rounded-xl p-4 mb-6 backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">วันที่เริ่มต้น</label>
                <input type="date" name="start_date" value="{{ $startDate }}"
                       class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">วันที่สิ้นสุด</label>
                <input type="date" name="end_date" value="{{ $endDate }}"
                       class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500">
            </div>
            <button type="submit" class="px-6 py-2 bg-pink-600 hover:bg-pink-700 text-white rounded-lg font-medium transition">
                🔍 ดูรายงาน
            </button>
        </form>
    </div>

    {{-- Overview Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="glass-fusion dark:bg-gray-800/50 rounded-xl p-5 backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
            <p class="text-sm text-gray-600 dark:text-gray-400">ทำสำเร็จ</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($overview['total_completions']) }}</p>
        </div>
        <div class="glass-fusion dark:bg-gray-800/50 rounded-xl p-5 backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
            <p class="text-sm text-gray-600 dark:text-gray-400">ผู้ใช้ที่ทำ</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($overview['unique_users']) }}</p>
        </div>
        <div class="glass-fusion dark:bg-gray-800/50 rounded-xl p-5 backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
            <p class="text-sm text-gray-600 dark:text-gray-400">รางวัลรวม</p>
            <p class="text-3xl font-bold text-green-600 dark:text-green-400">฿{{ number_format($overview['total_rewards'], 2) }}</p>
        </div>
        <div class="glass-fusion dark:bg-gray-800/50 rounded-xl p-5 backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
            <p class="text-sm text-gray-600 dark:text-gray-400">อัตราน่าสงสัย</p>
            <p class="text-3xl font-bold {{ $overview['suspicious_rate'] > 10 ? 'text-red-600' : 'text-gray-900 dark:text-white' }}">{{ $overview['suspicious_rate'] }}%</p>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6 mb-6">
        {{-- Daily Chart --}}
        <div class="glass-fusion dark:bg-gray-800/50 rounded-2xl shadow-lg p-6 backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">📊 การทำภารกิจรายวัน</h2>
            <canvas id="dailyChart" height="250"></canvas>
        </div>

        {{-- Rewards Chart --}}
        <div class="glass-fusion dark:bg-gray-800/50 rounded-2xl shadow-lg p-6 backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">💰 รางวัลรายวัน</h2>
            <canvas id="rewardsChart" height="250"></canvas>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        {{-- Top Missions --}}
        <div class="glass-fusion dark:bg-gray-800/50 rounded-2xl shadow-lg overflow-hidden backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">🏆 ภารกิจยอดนิยม</h2>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($topMissions as $index => $mission)
                <div class="flex items-center gap-4 p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                    <span class="w-8 h-8 flex items-center justify-center rounded-full text-sm font-bold
                        {{ $index === 0 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300' : '' }}
                        {{ $index === 1 ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' : '' }}
                        {{ $index === 2 ? 'bg-orange-100 text-orange-800 dark:bg-orange-900/50 dark:text-orange-300' : '' }}
                        {{ $index > 2 ? 'bg-gray-50 text-gray-600 dark:bg-gray-800 dark:text-gray-400' : '' }}
                    ">{{ $index + 1 }}</span>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-gray-900 dark:text-white truncate">{{ $mission->display_title }}</p>
                    </div>
                    <span class="font-semibold text-gray-900 dark:text-white">{{ number_format($mission->verified_completions) }}</span>
                </div>
                @empty
                <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                    ไม่มีข้อมูล
                </div>
                @endforelse
            </div>
        </div>

        {{-- Top Users --}}
        <div class="glass-fusion dark:bg-gray-800/50 rounded-2xl shadow-lg overflow-hidden backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">👑 ผู้ใช้ที่ทำมากที่สุด</h2>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($topUsers as $index => $item)
                <div class="flex items-center gap-4 p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                    <span class="w-8 h-8 flex items-center justify-center rounded-full text-sm font-bold
                        {{ $index === 0 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300' : '' }}
                        {{ $index === 1 ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' : '' }}
                        {{ $index === 2 ? 'bg-orange-100 text-orange-800 dark:bg-orange-900/50 dark:text-orange-300' : '' }}
                        {{ $index > 2 ? 'bg-gray-50 text-gray-600 dark:bg-gray-800 dark:text-gray-400' : '' }}
                    ">{{ $index + 1 }}</span>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-gray-900 dark:text-white truncate">{{ $item->user->name ?? 'Unknown' }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $item->completions }} ครั้ง</p>
                    </div>
                    <span class="font-semibold text-green-600 dark:text-green-400">฿{{ number_format($item->earnings, 2) }}</span>
                </div>
                @empty
                <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                    ไม่มีข้อมูล
                </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Daily Report Table --}}
    <div class="glass-fusion dark:bg-gray-800/50 rounded-2xl shadow-lg overflow-hidden backdrop-blur-sm border border-white/20 dark:border-gray-700/50 mt-6">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">📅 รายงานรายวัน</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">วันที่</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ทำสำเร็จ</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ผู้ใช้</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">รางวัลรวม</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($dailyReport as $row)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="px-4 py-3 text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($row->completion_date)->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-right text-gray-900 dark:text-white">{{ number_format($row->completions) }}</td>
                        <td class="px-4 py-3 text-right text-gray-900 dark:text-white">{{ number_format($row->unique_users) }}</td>
                        <td class="px-4 py-3 text-right text-green-600 dark:text-green-400 font-medium">฿{{ number_format($row->total_rewards, 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                            ไม่มีข้อมูล
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
document.addEventListener('DOMContentLoaded', function() {
    const dailyData = @json($dailyReport);

    // Daily Completions Chart
    new Chart(document.getElementById('dailyChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: dailyData.map(d => d.completion_date),
            datasets: [{
                label: 'ทำสำเร็จ',
                data: dailyData.map(d => d.completions),
                backgroundColor: 'rgba(236, 72, 153, 0.5)',
                borderColor: 'rgb(236, 72, 153)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    // Rewards Chart
    new Chart(document.getElementById('rewardsChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: dailyData.map(d => d.completion_date),
            datasets: [{
                label: 'รางวัล (บาท)',
                data: dailyData.map(d => d.total_rewards),
                borderColor: 'rgb(34, 197, 94)',
                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
});
</script>
@endpush
@endsection
