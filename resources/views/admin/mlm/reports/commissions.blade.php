@extends('layouts.admin')

@section('title', 'รายงานค่าคอมมิชชั่น MLM')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-emerald-600 via-green-600 to-teal-600 rounded-xl shadow-xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold mb-2 flex items-center">
                    <i class="fas fa-coins mr-3"></i>
                    รายงานค่าคอมมิชชั่น MLM
                </h1>
                <p class="text-emerald-100">วิเคราะห์และติดตามค่าคอมมิชชั่นทั้งหมด</p>
            </div>
        </div>
    </div>

    <!-- Commission Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-yellow-500 to-orange-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90 mb-1">รอดำเนินการ</p>
                    <p class="text-3xl font-bold">{{ number_format($commissionStats['pending_count'] ?? 0) }}</p>
                    <p class="text-xl mt-2">฿{{ number_format($commissionStats['pending_amount'] ?? 0, 2) }}</p>
                </div>
                <div class="bg-white/20 p-4 rounded-full">
                    <i class="fas fa-hourglass-half text-3xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90 mb-1">อนุมัติแล้ว</p>
                    <p class="text-3xl font-bold">{{ number_format($commissionStats['approved_count'] ?? 0) }}</p>
                    <p class="text-xl mt-2">฿{{ number_format($commissionStats['approved_amount'] ?? 0, 2) }}</p>
                </div>
                <div class="bg-white/20 p-4 rounded-full">
                    <i class="fas fa-check-circle text-3xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90 mb-1">จ่ายแล้ว</p>
                    <p class="text-3xl font-bold">{{ number_format($commissionStats['paid_count'] ?? 0) }}</p>
                    <p class="text-xl mt-2">฿{{ number_format($commissionStats['paid_amount'] ?? 0, 2) }}</p>
                </div>
                <div class="bg-white/20 p-4 rounded-full">
                    <i class="fas fa-money-bill-wave text-3xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90 mb-1">จ่ายเดือนนี้</p>
                    <p class="text-3xl font-bold">฿{{ number_format($commissionStats['paid_this_month'] ?? 0, 2) }}</p>
                    <p class="text-sm mt-2 opacity-75">{{ now()->format('F Y') }}</p>
                </div>
                <div class="bg-white/20 p-4 rounded-full">
                    <i class="fas fa-calendar-check text-3xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Commission Trends -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                <i class="fas fa-chart-line text-green-600 mr-2"></i>
                แนวโน้มค่าคอมมิชชั่น (30 วัน)
            </h2>
            <canvas id="commissionTrendsChart"></canvas>
        </div>

        <!-- Commission by Type -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                <i class="fas fa-chart-pie text-purple-600 mr-2"></i>
                ค่าคอมมิชชั่นตามประเภท
            </h2>
            <canvas id="commissionTypeChart"></canvas>
        </div>
    </div>

    <!-- Top Earners -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
            <i class="fas fa-trophy text-yellow-500 mr-2"></i>
            สมาชิกที่ได้รับค่าคอมมิชชั่นสูงสุด (Top 10)
        </h2>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">อันดับ</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">สมาชิก</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">รหัสสมาชิก</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">ค่าคอมมิชชั่นรวม</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">จำนวนครั้ง</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">ระดับ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @php
                        // Mock data for demonstration
                        $topEarners = collect([
                            ['rank' => 1, 'name' => 'สมชาย ใจดี', 'code' => 'MLM001', 'total' => 125000, 'count' => 48, 'level' => 'Diamond'],
                            ['rank' => 2, 'name' => 'สมหญิง รักงาม', 'code' => 'MLM002', 'total' => 98500, 'count' => 42, 'level' => 'Platinum'],
                            ['rank' => 3, 'name' => 'วิชัย สมบูรณ์', 'code' => 'MLM003', 'total' => 87200, 'count' => 39, 'level' => 'Gold'],
                            ['rank' => 4, 'name' => 'อรุณี แสงสว่าง', 'code' => 'MLM004', 'total' => 76300, 'count' => 35, 'level' => 'Gold'],
                            ['rank' => 5, 'name' => 'ประยุทธ มั่นคง', 'code' => 'MLM005', 'total' => 65400, 'count' => 31, 'level' => 'Silver'],
                        ]);
                    @endphp

                    @forelse($topEarners as $earner)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-2xl">
                                    @if($earner['rank'] == 1) 🥇
                                    @elseif($earner['rank'] == 2) 🥈
                                    @elseif($earner['rank'] == 3) 🥉
                                    @else
                                        <span class="text-gray-500 dark:text-gray-400 font-semibold">#{{ $earner['rank'] }}</span>
                                    @endif
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold mr-3">
                                        {{ strtoupper(substr($earner['name'], 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900 dark:text-white">{{ $earner['name'] }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="font-mono text-sm text-gray-700 dark:text-gray-300">{{ $earner['code'] }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-lg font-bold text-green-600 dark:text-green-400">
                                    ฿{{ number_format($earner['total'], 2) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-full text-sm font-semibold">
                                    {{ $earner['count'] }} ครั้ง
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-3 py-1 rounded-full text-sm font-semibold
                                    {{ $earner['level'] === 'Diamond' ? 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200' : '' }}
                                    {{ $earner['level'] === 'Platinum' ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' : '' }}
                                    {{ $earner['level'] === 'Gold' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : '' }}
                                    {{ $earner['level'] === 'Silver' ? 'bg-gray-200 text-gray-700 dark:bg-gray-600 dark:text-gray-200' : '' }}">
                                    {{ $earner['level'] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                ไม่มีข้อมูล
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Level Analysis -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
            <i class="fas fa-layer-group text-indigo-600 mr-2"></i>
            วิเคราะห์ค่าคอมมิชชั่นตามระดับ
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            @php
                $levels = [
                    ['name' => 'Level 1', 'count' => 125, 'amount' => 450000, 'color' => 'from-blue-500 to-blue-600'],
                    ['name' => 'Level 2', 'count' => 98, 'amount' => 320000, 'color' => 'from-green-500 to-green-600'],
                    ['name' => 'Level 3', 'count' => 75, 'amount' => 210000, 'color' => 'from-yellow-500 to-yellow-600'],
                    ['name' => 'Level 4', 'count' => 52, 'amount' => 145000, 'color' => 'from-orange-500 to-orange-600'],
                    ['name' => 'Level 5+', 'count' => 30, 'amount' => 85000, 'color' => 'from-red-500 to-red-600'],
                ];
            @endphp

            @foreach($levels as $level)
                <div class="bg-gradient-to-br {{ $level['color'] }} rounded-xl shadow-md p-6 text-white">
                    <h3 class="text-lg font-bold mb-2">{{ $level['name'] }}</h3>
                    <div class="space-y-2">
                        <div>
                            <p class="text-sm opacity-80">จำนวนสมาชิก</p>
                            <p class="text-2xl font-bold">{{ number_format($level['count']) }}</p>
                        </div>
                        <div>
                            <p class="text-sm opacity-80">ค่าคอมมิชชั่น</p>
                            <p class="text-xl font-bold">฿{{ number_format($level['amount']) }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Export -->
    <div class="bg-gradient-to-r from-emerald-600 to-teal-600 rounded-xl shadow-xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold mb-1">ส่งออกรายงาน</h3>
                <p class="text-emerald-100 text-sm">ดาวน์โหลดรายงานค่าคอมมิชชั่น</p>
            </div>
            <div class="flex gap-3">
                <button onclick="exportReport('csv')"
                        class="bg-white text-emerald-600 px-6 py-3 rounded-lg hover:bg-emerald-50 transition-all font-semibold">
                    <i class="fas fa-file-csv mr-2"></i>
                    CSV
                </button>
                <button onclick="exportReport('excel')"
                        class="bg-white text-teal-600 px-6 py-3 rounded-lg hover:bg-teal-50 transition-all font-semibold">
                    <i class="fas fa-file-excel mr-2"></i>
                    Excel
                </button>
                <button onclick="exportReport('pdf')"
                        class="bg-white text-green-600 px-6 py-3 rounded-lg hover:bg-green-50 transition-all font-semibold">
                    <i class="fas fa-file-pdf mr-2"></i>
                    PDF
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Commission Trends Chart
    const trendsCtx = document.getElementById('commissionTrendsChart').getContext('2d');
    new Chart(trendsCtx, {
        type: 'line',
        data: {
            labels: Array.from({length: 30}, (_, i) => `Day ${i + 1}`),
            datasets: [{
                label: 'ค่าคอมมิชชั่น (฿)',
                data: Array.from({length: 30}, () => Math.floor(Math.random() * 50000) + 10000),
                borderColor: 'rgb(16, 185, 129)',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    labels: { color: window.isDarkMode() ? '#e2e8f0' : '#374151' }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { color: window.isDarkMode() ? '#e2e8f0' : '#374151' },
                    grid: { color: window.isDarkMode() ? 'rgba(148, 163, 184, 0.1)' : 'rgba(0, 0, 0, 0.05)' }
                },
                x: {
                    ticks: { color: window.isDarkMode() ? '#e2e8f0' : '#374151' },
                    grid: { color: window.isDarkMode() ? 'rgba(148, 163, 184, 0.1)' : 'rgba(0, 0, 0, 0.05)' }
                }
            }
        }
    });

    // Commission Type Chart
    const typeCtx = document.getElementById('commissionTypeChart').getContext('2d');
    new Chart(typeCtx, {
        type: 'doughnut',
        data: {
            labels: ['Direct Commission', 'Level Commission', 'Binary Bonus', 'Matching Bonus', 'Leadership Bonus'],
            datasets: [{
                data: [35, 25, 20, 12, 8],
                backgroundColor: [
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(16, 185, 129, 0.8)',
                    'rgba(251, 191, 36, 0.8)',
                    'rgba(139, 92, 246, 0.8)',
                    'rgba(236, 72, 153, 0.8)'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: window.isDarkMode() ? '#e2e8f0' : '#374151', padding: 15 }
                }
            }
        }
    });

    function exportReport(format) {
        alert('กำลังพัฒนาฟีเจอร์ Export ' + format.toUpperCase());
    }

    // Animations
    gsap.from('.space-y-6 > div', {
        opacity: 0,
        y: 20,
        duration: 0.5,
        stagger: 0.1,
        ease: 'power2.out'
    });
</script>
@endpush
@endsection
