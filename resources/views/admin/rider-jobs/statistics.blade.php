{{--
    Admin Rider Jobs Statistics - สถิติงานไรเดอร์
    แสดงกราฟและข้อมูลสถิติรายวัน/รายเดือน
--}}

@extends('layouts.admin')

@section('title', 'สถิติงานไรเดอร์')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-purple-900 to-gray-900 p-6">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.rider-jobs.index') }}"
               class="p-3 bg-white/10 hover:bg-white/20 rounded-xl transition backdrop-blur-lg border border-white/10">
                <i class="fas fa-arrow-left text-white"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-white flex items-center gap-3">
                    <i class="fas fa-chart-line text-green-400"></i>
                    สถิติงานไรเดอร์
                </h1>
                <p class="text-gray-400 text-sm mt-1">วิเคราะห์ข้อมูลงานและรายได้</p>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/10 mb-6">
        <form method="GET" action="{{ route('admin.rider-jobs.statistics') }}" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="text-gray-300 text-sm block mb-2">ช่วงเวลา</label>
                <select name="period" class="bg-gray-800 border border-gray-700 rounded-xl px-4 py-2.5 text-white focus:ring-purple-500">
                    <option value="daily" {{ $period == 'daily' ? 'selected' : '' }}>รายวัน</option>
                    <option value="monthly" {{ $period == 'monthly' ? 'selected' : '' }}>รายเดือน</option>
                </select>
            </div>
            <div>
                <label class="text-gray-300 text-sm block mb-2">จากวันที่</label>
                <input type="date" name="start_date" value="{{ $startDate }}"
                       class="bg-gray-800 border border-gray-700 rounded-xl px-4 py-2.5 text-white focus:ring-purple-500">
            </div>
            <div>
                <label class="text-gray-300 text-sm block mb-2">ถึงวันที่</label>
                <input type="date" name="end_date" value="{{ $endDate }}"
                       class="bg-gray-800 border border-gray-700 rounded-xl px-4 py-2.5 text-white focus:ring-purple-500">
            </div>
            <button type="submit" class="px-6 py-2.5 bg-purple-600 hover:bg-purple-500 text-white rounded-xl font-medium transition">
                <i class="fas fa-filter mr-2"></i>กรอง
            </button>
        </form>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        {{-- Summary Cards --}}
        @php
            $totalJobs = $stats->sum('total');
            $totalCompleted = $stats->sum('completed');
            $totalCancelled = $stats->sum('cancelled');
            $totalRevenue = $stats->sum('revenue');
            $totalRiderEarnings = $stats->sum('rider_earnings');
        @endphp

        <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/10">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-blue-500 to-cyan-600 flex items-center justify-center">
                    <i class="fas fa-box text-white text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-400 text-sm">งานทั้งหมด</p>
                    <p class="text-3xl font-bold text-white">{{ number_format($totalJobs) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/10">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center">
                    <i class="fas fa-check-circle text-white text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-400 text-sm">สำเร็จ</p>
                    <p class="text-3xl font-bold text-green-400">{{ number_format($totalCompleted) }}</p>
                    <p class="text-sm text-gray-500">
                        {{ $totalJobs > 0 ? number_format(($totalCompleted / $totalJobs) * 100, 1) : 0 }}%
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/10">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-yellow-500 to-orange-600 flex items-center justify-center">
                    <i class="fas fa-coins text-white text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-400 text-sm">รายได้รวม</p>
                    <p class="text-3xl font-bold text-yellow-400">฿{{ number_format($totalRevenue) }}</p>
                    <p class="text-sm text-gray-500">
                        รายได้ไรเดอร์ ฿{{ number_format($totalRiderEarnings) }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Jobs Chart --}}
        <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/10">
            <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                <i class="fas fa-chart-bar text-blue-400"></i>
                จำนวนงาน
            </h3>
            <div class="h-[300px]">
                <canvas id="jobsChart"></canvas>
            </div>
        </div>

        {{-- Revenue Chart --}}
        <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/10">
            <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                <i class="fas fa-chart-line text-green-400"></i>
                รายได้
            </h3>
            <div class="h-[300px]">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Top Riders --}}
    <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/10">
        <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
            <i class="fas fa-trophy text-yellow-400"></i>
            ไรเดอร์ยอดเยี่ยม
        </h3>

        @if($topRiders->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-white/10">
                            <th class="px-4 py-3 text-left text-gray-400 text-sm font-medium">อันดับ</th>
                            <th class="px-4 py-3 text-left text-gray-400 text-sm font-medium">ไรเดอร์</th>
                            <th class="px-4 py-3 text-left text-gray-400 text-sm font-medium">งานสำเร็จ</th>
                            <th class="px-4 py-3 text-left text-gray-400 text-sm font-medium">คะแนน</th>
                            <th class="px-4 py-3 text-left text-gray-400 text-sm font-medium">รายได้รวม</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        @foreach($topRiders as $index => $rider)
                            <tr class="hover:bg-white/5 transition">
                                <td class="px-4 py-4">
                                    @if($index == 0)
                                        <span class="text-3xl">🥇</span>
                                    @elseif($index == 1)
                                        <span class="text-3xl">🥈</span>
                                    @elseif($index == 2)
                                        <span class="text-3xl">🥉</span>
                                    @else
                                        <span class="text-white font-bold">#{{ $index + 1 }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-orange-500 to-red-500 flex items-center justify-center text-white font-bold">
                                            {{ mb_substr($rider->full_name, 0, 1) }}
                                        </div>
                                        <div>
                                            <p class="text-white font-medium">{{ $rider->full_name }}</p>
                                            <p class="text-gray-400 text-sm">{{ $rider->phone }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <span class="text-green-400 font-bold">{{ number_format($rider->completed_jobs_count) }}</span>
                                    <span class="text-gray-400">งาน</span>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-1">
                                        <i class="fas fa-star text-yellow-400"></i>
                                        <span class="text-white">{{ number_format($rider->rating ?? 0, 1) }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <span class="text-yellow-400 font-bold">฿{{ number_format($rider->total_earnings ?? 0) }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-8 text-gray-400">
                <i class="fas fa-users-slash text-4xl mb-4"></i>
                <p>ไม่พบข้อมูลไรเดอร์ในช่วงเวลาที่เลือก</p>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const statsData = @json($stats);

        // Jobs Chart
        const jobsCtx = document.getElementById('jobsChart').getContext('2d');
        new Chart(jobsCtx, {
            type: 'bar',
            data: {
                labels: statsData.map(s => s.date || s.month),
                datasets: [
                    {
                        label: 'สำเร็จ',
                        data: statsData.map(s => s.completed),
                        backgroundColor: 'rgba(34, 197, 94, 0.8)',
                        borderRadius: 4,
                    },
                    {
                        label: 'ยกเลิก',
                        data: statsData.map(s => s.cancelled),
                        backgroundColor: 'rgba(239, 68, 68, 0.8)',
                        borderRadius: 4,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            color: '#9ca3af',
                        },
                    },
                },
                scales: {
                    x: {
                        stacked: true,
                        ticks: { color: '#9ca3af' },
                        grid: { color: 'rgba(255,255,255,0.05)' },
                    },
                    y: {
                        stacked: true,
                        ticks: { color: '#9ca3af' },
                        grid: { color: 'rgba(255,255,255,0.05)' },
                    },
                },
            },
        });

        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: statsData.map(s => s.date || s.month),
                datasets: [
                    {
                        label: 'รายได้รวม',
                        data: statsData.map(s => s.revenue),
                        borderColor: 'rgba(234, 179, 8, 1)',
                        backgroundColor: 'rgba(234, 179, 8, 0.1)',
                        fill: true,
                        tension: 0.4,
                    },
                    {
                        label: 'รายได้ไรเดอร์',
                        data: statsData.map(s => s.rider_earnings),
                        borderColor: 'rgba(34, 197, 94, 1)',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        fill: true,
                        tension: 0.4,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            color: '#9ca3af',
                        },
                    },
                },
                scales: {
                    x: {
                        ticks: { color: '#9ca3af' },
                        grid: { color: 'rgba(255,255,255,0.05)' },
                    },
                    y: {
                        ticks: {
                            color: '#9ca3af',
                            callback: function(value) {
                                return '฿' + value.toLocaleString();
                            },
                        },
                        grid: { color: 'rgba(255,255,255,0.05)' },
                    },
                },
            },
        });
    });
</script>
@endpush
@endsection
