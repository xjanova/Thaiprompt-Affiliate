@extends('layouts.admin-v3')

@section('title', 'สถิติโฆษณา POS')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 rounded-xl shadow-xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold mb-2 flex items-center">
                    <i class="fas fa-chart-line mr-3"></i>
                    สถิติและวิเคราะห์โฆษณา POS
                </h1>
                <p class="text-blue-100">วิเคราะห์ประสิทธิภาพโฆษณาของคุณ</p>
            </div>
            <a href="{{ route('admin.pos.advertisements.index') }}"
               class="bg-white text-indigo-600 px-4 py-2 rounded-lg hover:bg-indigo-50 transition-all">
                <i class="fas fa-arrow-left mr-2"></i>
                กลับ
            </a>
        </div>
    </div>

    <!-- Overall Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <i class="fas fa-eye text-5xl opacity-75"></i>
                <div class="text-right">
                    <p class="text-sm opacity-80">การดูทั้งหมด</p>
                    <p class="text-4xl font-bold">{{ number_format($totalViews) }}</p>
                </div>
            </div>
            <div class="h-1 bg-white/30 rounded-full overflow-hidden">
                <div class="h-full bg-white animate-pulse" style="width: 100%"></div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <i class="fas fa-mouse-pointer text-5xl opacity-75"></i>
                <div class="text-right">
                    <p class="text-sm opacity-80">คลิกทั้งหมด</p>
                    <p class="text-4xl font-bold">{{ number_format($totalClicks) }}</p>
                </div>
            </div>
            <div class="h-1 bg-white/30 rounded-full overflow-hidden">
                <div class="h-full bg-white animate-pulse" style="width: {{ $totalViews > 0 ? ($totalClicks / $totalViews * 100) : 0 }}%"></div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <i class="fas fa-percentage text-5xl opacity-75"></i>
                <div class="text-right">
                    <p class="text-sm opacity-80">CTR เฉลี่ย</p>
                    <p class="text-4xl font-bold">{{ $averageCTR }}%</p>
                </div>
            </div>
            <div class="h-1 bg-white/30 rounded-full overflow-hidden">
                <div class="h-full bg-white" style="width: {{ min($averageCTR * 10, 100) }}%"></div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Performance Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                <i class="fas fa-chart-bar text-blue-600 mr-2"></i>
                ประสิทธิภาพโฆษณา
            </h2>
            <canvas id="performanceChart"></canvas>
        </div>

        <!-- CTR Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                <i class="fas fa-chart-pie text-purple-600 mr-2"></i>
                อัตราคลิก (CTR)
            </h2>
            <canvas id="ctrChart"></canvas>
        </div>
    </div>

    <!-- Top Performing Ads -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
            <i class="fas fa-trophy text-yellow-500 mr-2"></i>
            โฆษณาที่มีประสิทธิภาพสูงสุด
        </h2>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">อันดับ</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">โฆษณา</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">ประเภท</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">การดู</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">คลิก</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">CTR</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">สถานะ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @php
                        $sortedAds = $advertisements->sortByDesc(function($ad) {
                            return $ad->view_count > 0 ? ($ad->click_count / $ad->view_count) * 100 : 0;
                        })->take(10);
                        $rank = 1;
                    @endphp

                    @forelse($sortedAds as $ad)
                        @php
                            $ctr = $ad->view_count > 0 ? round(($ad->click_count / $ad->view_count) * 100, 2) : 0;
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($rank <= 3)
                                    <span class="text-2xl">
                                        @if($rank == 1) 🥇
                                        @elseif($rank == 2) 🥈
                                        @else 🥉
                                        @endif
                                    </span>
                                @else
                                    <span class="text-gray-500 dark:text-gray-400 font-semibold">#{{ $rank }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <a href="{{ route('admin.pos.advertisements.show', $ad) }}"
                                   class="font-semibold text-gray-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400">
                                    {{ $ad->title }}
                                </a>
                                @if($ad->store)
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        <i class="fas fa-store mr-1"></i>{{ $ad->store->name }}
                                    </p>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold
                                    {{ $ad->type === 'image' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : '' }}
                                    {{ $ad->type === 'video' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' : '' }}
                                    {{ $ad->type === 'html' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
                                    {{ $ad->type === 'promotion' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : '' }}">
                                    {{ ucfirst($ad->type) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-gray-900 dark:text-white font-medium">{{ number_format($ad->view_count) }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-gray-900 dark:text-white font-medium">{{ number_format($ad->click_count) }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <span class="text-gray-900 dark:text-white font-bold mr-2">{{ $ctr }}%</span>
                                    <div class="w-20 bg-gray-200 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
                                        <div class="h-full rounded-full {{ $ctr >= 5 ? 'bg-green-500' : ($ctr >= 2 ? 'bg-yellow-500' : 'bg-red-500') }}"
                                             style="width: {{ min($ctr * 10, 100) }}%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $ad->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                    {{ $ad->is_active ? 'ใช้งาน' : 'หยุด' }}
                                </span>
                            </td>
                        </tr>
                        @php $rank++; @endphp
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                ไม่มีข้อมูลโฆษณา
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Export & Actions -->
    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-xl shadow-xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold mb-1">ส่งออกรายงาน</h3>
                <p class="text-indigo-100 text-sm">ดาวน์โหลดข้อมูลสถิติเพื่อวิเคราะห์เพิ่มเติม</p>
            </div>
            <div class="flex gap-3">
                <button onclick="exportData('csv')"
                        class="bg-white text-indigo-600 px-6 py-3 rounded-lg hover:bg-indigo-50 transition-all">
                    <i class="fas fa-file-csv mr-2"></i>
                    Export CSV
                </button>
                <button onclick="exportData('pdf')"
                        class="bg-white text-purple-600 px-6 py-3 rounded-lg hover:bg-purple-50 transition-all">
                    <i class="fas fa-file-pdf mr-2"></i>
                    Export PDF
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Performance Chart
    const performanceCtx = document.getElementById('performanceChart').getContext('2d');
    new Chart(performanceCtx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($advertisements->pluck('title')->take(10)) !!},
            datasets: [
                {
                    label: 'การดู',
                    data: {!! json_encode($advertisements->pluck('view_count')->take(10)) !!},
                    backgroundColor: 'rgba(59, 130, 246, 0.8)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 2
                },
                {
                    label: 'คลิก',
                    data: {!! json_encode($advertisements->pluck('click_count')->take(10)) !!},
                    backgroundColor: 'rgba(16, 185, 129, 0.8)',
                    borderColor: 'rgba(16, 185, 129, 1)',
                    borderWidth: 2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    labels: {
                        color: window.isDarkMode() ? '#e2e8f0' : '#374151'
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: window.isDarkMode() ? '#e2e8f0' : '#374151'
                    },
                    grid: {
                        color: window.isDarkMode() ? 'rgba(148, 163, 184, 0.1)' : 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    ticks: {
                        color: window.isDarkMode() ? '#e2e8f0' : '#374151'
                    },
                    grid: {
                        color: window.isDarkMode() ? 'rgba(148, 163, 184, 0.1)' : 'rgba(0, 0, 0, 0.05)'
                    }
                }
            }
        }
    });

    // CTR Chart
    const ctrCtx = document.getElementById('ctrChart').getContext('2d');
    const ctrData = {!! json_encode($advertisements->map(function($ad) {
        return $ad->view_count > 0 ? round(($ad->click_count / $ad->view_count) * 100, 2) : 0;
    })->take(10)) !!};

    new Chart(ctrCtx, {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($advertisements->pluck('title')->take(10)) !!},
            datasets: [{
                data: ctrData,
                backgroundColor: [
                    'rgba(239, 68, 68, 0.8)',
                    'rgba(249, 115, 22, 0.8)',
                    'rgba(251, 191, 36, 0.8)',
                    'rgba(16, 185, 129, 0.8)',
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(99, 102, 241, 0.8)',
                    'rgba(139, 92, 246, 0.8)',
                    'rgba(236, 72, 153, 0.8)',
                    'rgba(244, 63, 94, 0.8)',
                    'rgba(148, 163, 184, 0.8)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: window.isDarkMode() ? '#e2e8f0' : '#374151',
                        padding: 15
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ' + context.parsed + '%';
                        }
                    }
                }
            }
        }
    });

    function exportData(format) {
        alert('กำลังพัฒนาฟีเจอร์ Export ' + format.toUpperCase());
    }

    // Animations
    document.addEventListener('DOMContentLoaded', function() {
        gsap.from('.space-y-6 > div', {
            opacity: 0,
            y: 20,
            duration: 0.6,
            stagger: 0.1,
            ease: 'power2.out'
        });
    });
</script>
@endpush
@endsection
