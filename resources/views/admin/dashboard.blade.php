@extends('layouts.admin')

@section('title', 'แดชบอร์ด')

@section('content')
<div class="space-y-6">
    <!-- Welcome Header with Gradient -->
    <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 rounded-2xl shadow-2xl p-8 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-40 h-40 bg-white opacity-10 rounded-full"></div>
        <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-32 h-32 bg-white opacity-10 rounded-full"></div>
        <div class="relative z-10">
            <h1 class="text-3xl font-bold mb-2">👋 สวัสดี, {{ Auth::user()->name }}</h1>
            <p class="text-indigo-100">ยินดีต้อนรับกลับสู่แดชบอร์ด Admin - {{ now()->format('d/m/Y') }}</p>
        </div>
    </div>

    <!-- Stats Cards with Growth Indicators -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Users -->
        <a href="{{ route('admin.users.index') }}" class="relative overflow-hidden bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition duration-300 block cursor-pointer">
            <div class="absolute top-0 right-0 -mt-4 -mr-4">
                <div class="w-24 h-24 bg-white opacity-10 rounded-full"></div>
            </div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-5xl">👥</div>
                    @if($userGrowth != 0)
                        <span class="px-2 py-1 bg-white bg-opacity-20 rounded-lg text-xs font-semibold">
                            {{ $userGrowth > 0 ? '↑' : '↓' }} {{ number_format(abs($userGrowth), 1) }}%
                        </span>
                    @endif
                </div>
                <p class="text-white text-opacity-80 text-sm mb-1">ผู้ใช้ทั้งหมด</p>
                <p class="text-4xl font-bold">{{ number_format($stats['total_users']) }}</p>
                <p class="text-xs text-white text-opacity-70 mt-2">คลิกเพื่อดูรายละเอียด</p>
            </div>
        </a>

        <!-- Total Affiliates -->
        <a href="{{ route('admin.affiliates.index') }}" class="relative overflow-hidden bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition duration-300 block cursor-pointer">
            <div class="absolute top-0 right-0 -mt-4 -mr-4">
                <div class="w-24 h-24 bg-white opacity-10 rounded-full"></div>
            </div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-5xl">🌐</div>
                    <span class="px-2 py-1 bg-white bg-opacity-20 rounded-lg text-xs font-semibold">
                        {{ $stats['active_affiliates'] }} ใช้งาน
                    </span>
                </div>
                <p class="text-white text-opacity-80 text-sm mb-1">Affiliates ทั้งหมด</p>
                <p class="text-4xl font-bold">{{ number_format($stats['total_affiliates']) }}</p>
                <p class="text-xs text-white text-opacity-70 mt-2">คลิกเพื่อดูรายละเอียด</p>
            </div>
        </a>

        <!-- Total Revenue -->
        <a href="{{ route('admin.commissions.index', ['status' => 'paid']) }}" class="relative overflow-hidden bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition duration-300 block cursor-pointer">
            <div class="absolute top-0 right-0 -mt-4 -mr-4">
                <div class="w-24 h-24 bg-white opacity-10 rounded-full"></div>
            </div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-5xl">💰</div>
                    @if($revenueGrowth != 0)
                        <span class="px-2 py-1 bg-white bg-opacity-20 rounded-lg text-xs font-semibold">
                            {{ $revenueGrowth > 0 ? '↑' : '↓' }} {{ number_format(abs($revenueGrowth), 1) }}%
                        </span>
                    @endif
                </div>
                <p class="text-white text-opacity-80 text-sm mb-1">รายได้ทั้งหมด</p>
                <p class="text-4xl font-bold">฿{{ number_format($stats['paid_commissions'], 0) }}</p>
                <p class="text-xs text-white text-opacity-70 mt-2">คลิกเพื่อดูรายละเอียด</p>
            </div>
        </a>

        <!-- Pending Commissions -->
        <a href="{{ route('admin.commissions.index', ['status' => 'pending']) }}" class="relative overflow-hidden bg-gradient-to-br from-orange-500 to-red-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition duration-300 block cursor-pointer">
            <div class="absolute top-0 right-0 -mt-4 -mr-4">
                <div class="w-24 h-24 bg-white opacity-10 rounded-full"></div>
            </div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-5xl">⏳</div>
                    <span class="px-2 py-1 bg-white bg-opacity-20 rounded-lg text-xs font-semibold">
                        {{ $stats['approved_commissions'] }} อนุมัติ
                    </span>
                </div>
                <p class="text-white text-opacity-80 text-sm mb-1">รอดำเนินการ</p>
                <p class="text-4xl font-bold">{{ number_format($stats['pending_commissions']) }}</p>
                <p class="text-xs text-white text-opacity-70 mt-2">คลิกเพื่อดูรายละเอียด</p>
            </div>
        </a>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Revenue Area Chart -->
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-xl p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-xl font-bold text-gray-900">รายได้รายเดือน</h3>
                    <p class="text-sm text-gray-500">12 เดือนย้อนหลัง</p>
                </div>
                <div class="flex gap-2">
                    <span class="px-3 py-1 bg-green-100 text-green-800 rounded-lg text-xs font-semibold">
                        ฿{{ number_format($monthlyRevenue->sum('total'), 0) }}
                    </span>
                </div>
            </div>
            <canvas id="revenueChart" height="80"></canvas>
        </div>

        <!-- Commission Status Donut Chart -->
        <div class="bg-white rounded-2xl shadow-xl p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-6">สถานะคอมมิชชั่น</h3>
            <canvas id="statusChart"></canvas>
            <div class="mt-6 space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                        <span class="text-sm text-gray-600">รอดำเนินการ</span>
                    </div>
                    <span class="font-semibold text-gray-900">{{ $commissionStatus['pending'] }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                        <span class="text-sm text-gray-600">อนุมัติแล้ว</span>
                    </div>
                    <span class="font-semibold text-gray-900">{{ $commissionStatus['approved'] }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                        <span class="text-sm text-gray-600">จ่ายแล้ว</span>
                    </div>
                    <span class="font-semibold text-gray-900">{{ $commissionStatus['paid'] }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                        <span class="text-sm text-gray-600">ปฏิเสธ</span>
                    </div>
                    <span class="font-semibold text-gray-900">{{ $commissionStatus['rejected'] }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Commission Types & Daily Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Commission Types Bar Chart -->
        <div class="bg-white rounded-2xl shadow-xl p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-6">ประเภทคอมมิชชั่น</h3>
            <canvas id="typesChart"></canvas>
        </div>

        <!-- Daily Activity Line Chart -->
        <div class="bg-white rounded-2xl shadow-xl p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-6">กิจกรรมรายวัน (30 วัน)</h3>
            <canvas id="dailyChart"></canvas>
        </div>
    </div>

    <!-- Bottom Section: Top Affiliates & Recent Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top Affiliates Leaderboard -->
        <div class="bg-white rounded-2xl shadow-xl p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-gray-900">🏆 Top Affiliates</h3>
                <a href="{{ route('admin.affiliates.index') }}" class="text-sm text-indigo-600 hover:text-indigo-700 font-semibold">
                    ดูทั้งหมด →
                </a>
            </div>
            <div class="space-y-4">
                @forelse($topAffiliates as $index => $affiliate)
                    <div class="flex items-center gap-4 p-4 bg-gradient-to-r from-gray-50 to-white rounded-xl hover:shadow-md transition border border-gray-100">
                        <div class="flex-shrink-0">
                            @if($index === 0)
                                <div class="w-12 h-12 bg-gradient-to-br from-yellow-400 to-yellow-600 rounded-full flex items-center justify-center text-white font-bold text-lg shadow-lg">
                                    🥇
                                </div>
                            @elseif($index === 1)
                                <div class="w-12 h-12 bg-gradient-to-br from-gray-300 to-gray-500 rounded-full flex items-center justify-center text-white font-bold text-lg shadow-lg">
                                    🥈
                                </div>
                            @elseif($index === 2)
                                <div class="w-12 h-12 bg-gradient-to-br from-orange-400 to-orange-600 rounded-full flex items-center justify-center text-white font-bold text-lg shadow-lg">
                                    🥉
                                </div>
                            @else
                                <div class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold text-lg">
                                    {{ $index + 1 }}
                                </div>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-gray-900 truncate">{{ $affiliate->user->name }}</p>
                            <p class="text-sm text-gray-500 truncate">{{ $affiliate->user->email }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-lg text-green-600">฿{{ number_format($affiliate->total_earnings, 0) }}</p>
                            <p class="text-xs text-gray-500">{{ $affiliate->total_referrals }} refs</p>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-gray-500 py-8">
                        <span class="text-4xl mb-2 block">📊</span>
                        <p>ยังไม่มีข้อมูล</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Recent Commissions Activity Feed -->
        <div class="bg-white rounded-2xl shadow-xl p-6" x-data="activityFeed()">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-gray-900">🔔 กิจกรรมล่าสุด</h3>
                <a href="{{ route('admin.commissions.index') }}" class="text-sm text-indigo-600 hover:text-indigo-700 font-semibold">
                    ดูทั้งหมด →
                </a>
            </div>
            <div class="space-y-4 max-h-96 overflow-y-auto"
                 x-ref="scrollContainer"
                 @scroll="handleScroll()">
                @forelse($recentCommissions as $index => $commission)
                    <div class="flex items-start gap-3 p-3 hover:bg-gray-50 rounded-lg transition"
                         x-show="$index < visibleCount"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 transform translate-y-4"
                         x-transition:enter-end="opacity-100 transform translate-y-0">
                        <img src="{{ $commission->affiliate->user->profile_picture_url }}"
                             alt="{{ $commission->affiliate->user->name }}"
                             class="w-10 h-10 rounded-full object-cover flex-shrink-0 ring-2 ring-gray-200">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-900 truncate">
                                {{ $commission->affiliate->user->name }}
                            </p>
                            <p class="text-xs text-gray-500">
                                ได้รับ <span class="font-semibold text-green-600">฿{{ number_format($commission->amount, 2) }}</span>
                            </p>
                            <p class="text-xs text-gray-400 mt-1">
                                {{ $commission->created_at->diffForHumans() }}
                            </p>
                        </div>
                        <span class="px-2 py-1 rounded-full text-xs font-semibold flex-shrink-0
                            {{ $commission->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                            {{ $commission->status === 'approved' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $commission->status === 'paid' ? 'bg-blue-100 text-blue-800' : '' }}
                            {{ $commission->status === 'rejected' ? 'bg-red-100 text-red-800' : '' }}">
                            {{ ucfirst($commission->status) }}
                        </span>
                    </div>
                @empty
                    <div class="text-center text-gray-500 py-8">
                        <span class="text-4xl mb-2 block">📭</span>
                        <p>ยังไม่มีกิจกรรม</p>
                    </div>
                @endforelse

                <!-- Loading indicator -->
                <div x-show="loading" class="text-center py-4">
                    <div class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-indigo-600"></div>
                    <p class="text-xs text-gray-500 mt-2">กำลังโหลด...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-2xl shadow-xl p-6 text-white">
        <h3 class="text-xl font-bold mb-4">⚡ การกระทำด่วน</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="{{ route('admin.users.create') }}" class="bg-white bg-opacity-20 hover:bg-opacity-30 rounded-xl p-4 text-center transition">
                <div class="text-3xl mb-2">➕</div>
                <p class="text-sm font-semibold">เพิ่มผู้ใช้</p>
            </a>
            <a href="{{ route('admin.commissions.index', ['status' => 'pending']) }}" class="bg-white bg-opacity-20 hover:bg-opacity-30 rounded-xl p-4 text-center transition">
                <div class="text-3xl mb-2">✅</div>
                <p class="text-sm font-semibold">อนุมัติคอมมิชชั่น</p>
            </a>
            <a href="{{ route('admin.affiliates.tree') }}" class="bg-white bg-opacity-20 hover:bg-opacity-30 rounded-xl p-4 text-center transition">
                <div class="text-3xl mb-2">🌳</div>
                <p class="text-sm font-semibold">ดู Affiliate Tree</p>
            </a>
            <a href="{{ route('admin.settings.index') }}" class="bg-white bg-opacity-20 hover:bg-opacity-30 rounded-xl p-4 text-center transition">
                <div class="text-3xl mb-2">⚙️</div>
                <p class="text-sm font-semibold">ตั้งค่าระบบ</p>
            </a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Alpine.js Activity Feed Component with Infinite Scroll
function activityFeed() {
    return {
        visibleCount: 8,
        loading: false,
        totalItems: {{ $recentCommissions->count() }},

        handleScroll() {
            const container = this.$refs.scrollContainer;
            const scrollPercentage = (container.scrollTop + container.clientHeight) / container.scrollHeight;

            // Load more when scrolled to 80% of the container
            if (scrollPercentage > 0.8 && !this.loading && this.visibleCount < this.totalItems) {
                this.loadMore();
            }
        },

        loadMore() {
            this.loading = true;

            // Simulate loading delay for smooth UX
            setTimeout(() => {
                this.visibleCount = Math.min(this.visibleCount + 5, this.totalItems);
                this.loading = false;
            }, 300);
        }
    }
}

// Revenue Area Chart
const revenueCtx = document.getElementById('revenueChart');
if (revenueCtx) {
    const colors = window.getChartColors();
    new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode($monthlyRevenue->pluck('month')) !!},
            datasets: [{
                label: 'รายได้ (฿)',
                data: {!! json_encode($monthlyRevenue->pluck('total')) !!},
                borderColor: 'rgb(99, 102, 241)',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                fill: true,
                tension: 0.4,
                borderWidth: 3,
                pointRadius: 4,
                pointBackgroundColor: 'rgb(99, 102, 241)',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: colors.tooltipBg,
                    padding: 12,
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: 'rgba(99, 102, 241, 0.5)',
                    borderWidth: 1
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: colors.gridColor
                    },
                    ticks: {
                        color: colors.textColor,
                        callback: function(value) {
                            return '฿' + value.toLocaleString();
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: colors.textColor
                    }
                }
            }
        }
    });
}

// Status Donut Chart
const statusCtx = document.getElementById('statusChart');
if (statusCtx) {
    const colors = window.getChartColors();
    const borderColor = window.isDarkMode() ? '#1e293b' : '#fff';
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['รอดำเนินการ', 'อนุมัติแล้ว', 'จ่ายแล้ว', 'ปฏิเสธ'],
            datasets: [{
                data: [
                    {{ $commissionStatus['pending'] }},
                    {{ $commissionStatus['approved'] }},
                    {{ $commissionStatus['paid'] }},
                    {{ $commissionStatus['rejected'] }}
                ],
                backgroundColor: [
                    'rgba(234, 179, 8, 0.8)',
                    'rgba(34, 197, 94, 0.8)',
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(239, 68, 68, 0.8)'
                ],
                borderWidth: 2,
                borderColor: borderColor
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: colors.tooltipBg,
                    padding: 12,
                    titleColor: '#fff',
                    bodyColor: '#fff'
                }
            },
            cutout: '70%'
        }
    });
}

// Commission Types Bar Chart
const typesCtx = document.getElementById('typesChart');
if (typesCtx) {
    const colors = window.getChartColors();
    new Chart(typesCtx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($commissionTypes->pluck('type')) !!},
            datasets: [{
                label: 'จำนวนเงิน (฿)',
                data: {!! json_encode($commissionTypes->pluck('total')) !!},
                backgroundColor: [
                    'rgba(99, 102, 241, 0.8)',
                    'rgba(168, 85, 247, 0.8)',
                    'rgba(236, 72, 153, 0.8)'
                ],
                borderRadius: 8,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: colors.tooltipBg,
                    titleColor: '#fff',
                    bodyColor: '#fff'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: colors.gridColor
                    },
                    ticks: {
                        color: colors.textColor
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: colors.textColor
                    }
                }
            }
        }
    });
}

// Daily Activity Chart
const dailyCtx = document.getElementById('dailyChart');
if (dailyCtx) {
    const colors = window.getChartColors();
    new Chart(dailyCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode($dailyCommissions->pluck('date')) !!},
            datasets: [{
                label: 'จำนวนคอมมิชชั่น',
                data: {!! json_encode($dailyCommissions->pluck('count')) !!},
                borderColor: 'rgb(168, 85, 247)',
                backgroundColor: 'rgba(168, 85, 247, 0.1)',
                fill: true,
                tension: 0.4,
                borderWidth: 2,
                pointRadius: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: colors.tooltipBg,
                    titleColor: '#fff',
                    bodyColor: '#fff'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: colors.gridColor
                    },
                    ticks: {
                        color: colors.textColor
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: colors.textColor,
                        maxTicksLimit: 10
                    }
                }
            }
        }
    });
}
</script>
@endpush
