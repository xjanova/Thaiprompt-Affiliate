{{--
/**
 * Admin Dashboard V3 - Dashboard หลักสำหรับ Admin พร้อม V3 Components
 *
 * @uses layouts/admin-v3.blade.php - Layout หลัก
 * @uses arrow-x/stats/card-3d.blade.php - 3D Stat Cards
 * @uses arrow-x/charts/line.blade.php - Line Charts
 * @uses arrow-x/activity/list.blade.php - Activity Lists
 *
 * @data จาก Admin\DashboardController:
 * - $stats - สถิติหลักทั้งหมด
 * - $monthlyRevenue - ข้อมูลรายได้รายเดือน
 * - $recentCommissions - คอมมิชชั่นล่าสุด
 * - $topAffiliates - Affiliate อันดับต้นๆ
 * - $userGrowth - เปอร์เซ็นต์การเติบโตของผู้ใช้
 * - $revenueGrowth - เปอร์เซ็นต์การเติบโตของรายได้
 *
 * @tip View นี้ใช้ V3 Coding Guidelines 100%
 * @tip รองรับ dark mode อัตโนมัติ
 * @tip Responsive ทุก breakpoint
 */
--}}

@extends('layouts.admin-v3')

@section('title', 'Dashboard V3')
@section('page-title', 'Dashboard')

@section('content')
<div class="space-y-6">
    {{-- Stats Cards Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
        {{-- Total Users --}}
        <x-arrow-x.stats.card-3d
            :value="number_format($stats['total_users'])"
            label="ผู้ใช้งานทั้งหมด"
            icon="fas fa-users"
            gradient="from-blue-500 to-cyan-600"
            :change="$userGrowth"
            href="{{ route('admin.users.index') }}"
        />

        {{-- Total Affiliates --}}
        <x-arrow-x.stats.card-3d
            :value="number_format($stats['active_affiliates'])"
            label="Affiliate ที่ใช้งาน"
            icon="fas fa-network-wired"
            gradient="from-green-500 to-emerald-600"
            href="{{ route('admin.affiliates.index') }}"
        />

        {{-- Total Commissions --}}
        <x-arrow-x.stats.card-3d
            :value="'฿' . number_format($stats['total_commissions'], 2)"
            label="คอมมิชชั่นทั้งหมด"
            icon="fas fa-coins"
            gradient="from-purple-500 to-pink-600"
            :change="$revenueGrowth"
            href="{{ route('admin.commissions.index') }}"
        />

        {{-- Paid Commissions --}}
        <x-arrow-x.stats.card-3d
            :value="'฿' . number_format($stats['paid_commissions'], 2)"
            label="จ่ายแล้ว"
            icon="fas fa-check-circle"
            gradient="from-orange-500 to-red-600"
            href="{{ route('admin.commissions.index', ['status' => 'paid']) }}"
        />
    </div>

    {{-- Charts & Activity Section --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6">
        {{-- Revenue Chart (2/3 width) --}}
        <div class="lg:col-span-2">
            <x-arrow-x.charts.line
                id="revenue-chart"
                title="สถิติรายได้รายเดือน"
                icon="fas fa-chart-area"
                gradient="from-blue-500 to-purple-600"
                :height="350"
            />
        </div>

        {{-- Recent Activity (1/3 width) --}}
        <div>
            <x-arrow-x.activity.list title="กิจกรรมล่าสุด" icon="fas fa-history" gradient="from-purple-500 to-pink-600">
                @forelse($recentCommissions->take(5) as $commission)
                    <x-arrow-x.activity.item
                        icon="fas fa-coins"
                        iconBg="{{ $commission->status === 'paid' ? 'bg-green-500' : ($commission->status === 'pending' ? 'bg-yellow-500' : 'bg-blue-500') }}"
                        title="คอมมิชชั่น ฿{{ number_format($commission->amount, 2) }}"
                        description="{{ $commission->affiliate->user->name ?? 'N/A' }}"
                        time="{{ $commission->created_at->diffForHumans() }}"
                        href="{{ route('admin.commissions.show', $commission) }}"
                    />
                @empty
                    <div class="text-center py-8">
                        <i class="fas fa-inbox text-4xl text-white/30 mb-2"></i>
                        <p class="text-white/60 text-sm">ไม่มีกิจกรรม</p>
                    </div>
                @endforelse
            </x-arrow-x.activity.list>
        </div>
    </div>

    {{-- Additional Stats Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
        {{-- Pending Commissions --}}
        <div class="glass-fusion rounded-2xl p-6 border border-white/30 shadow-2xl">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-gradient-to-br from-yellow-500 to-orange-600 rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fas fa-clock text-white text-xl drop-shadow"></i>
                </div>
                <span class="px-3 py-1 bg-gradient-to-r from-yellow-400 to-orange-500 text-white text-xs font-bold rounded-full shadow-lg">
                    รออนุมัติ
                </span>
            </div>
            <h3 class="text-2xl font-bold text-white mb-1 drop-shadow-lg">
                {{ number_format($stats['pending_commissions']) }}
            </h3>
            <p class="text-sm text-white/90 drop-shadow">คอมมิชชั่นรออนุมัติ</p>
        </div>

        {{-- Approved Commissions --}}
        <div class="glass-fusion rounded-2xl p-6 border border-white/30 shadow-2xl">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fas fa-check-double text-white text-xl drop-shadow"></i>
                </div>
                <span class="px-3 py-1 bg-gradient-to-r from-green-400 to-emerald-500 text-white text-xs font-bold rounded-full shadow-lg">
                    อนุมัติแล้ว
                </span>
            </div>
            <h3 class="text-2xl font-bold text-white mb-1 drop-shadow-lg">
                {{ number_format($stats['approved_commissions']) }}
            </h3>
            <p class="text-sm text-white/90 drop-shadow">คอมมิชชั่นที่อนุมัติ</p>
        </div>

        {{-- Rejected Commissions --}}
        <div class="glass-fusion rounded-2xl p-6 border border-white/30 shadow-2xl">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-pink-600 rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fas fa-times-circle text-white text-xl drop-shadow"></i>
                </div>
                <span class="px-3 py-1 bg-gradient-to-r from-red-400 to-pink-500 text-white text-xs font-bold rounded-full shadow-lg">
                    ปฏิเสธ
                </span>
            </div>
            <h3 class="text-2xl font-bold text-white mb-1 drop-shadow-lg">
                {{ number_format($stats['rejected_commissions']) }}
            </h3>
            <p class="text-sm text-white/90 drop-shadow">คอมมิชชั่นที่ปฏิเสธ</p>
        </div>
    </div>

    {{-- Top Affiliates Table --}}
    <div class="glass-fusion rounded-2xl overflow-hidden border border-white/30 shadow-2xl">
        <div class="px-6 py-4 border-b border-white/30 bg-gradient-to-r from-blue-500/20 to-purple-600/20">
            <h3 class="text-xl font-bold text-white drop-shadow flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center shadow-lg">
                    <i class="fas fa-trophy text-white drop-shadow"></i>
                </div>
                Top Affiliates
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="glass-neu border-b border-white/20">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white/90 uppercase tracking-wider drop-shadow">
                            อันดับ
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white/90 uppercase tracking-wider drop-shadow">
                            ชื่อ
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-white/90 uppercase tracking-wider drop-shadow">
                            รายได้รวม
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/10">
                    @forelse($topAffiliates as $index => $affiliate)
                        <tr class="hover:bg-white/10 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <span class="w-8 h-8 flex items-center justify-center rounded-full {{ $index === 0 ? 'bg-gradient-to-br from-yellow-400 to-orange-500' : ($index === 1 ? 'bg-gradient-to-br from-gray-300 to-gray-400' : ($index === 2 ? 'bg-gradient-to-br from-orange-400 to-orange-600' : 'bg-white/20')) }} text-white font-bold text-sm shadow-lg">
                                        {{ $index + 1 }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
                                        <span class="text-white font-bold drop-shadow">{{ substr($affiliate->user->name ?? 'N/A', 0, 1) }}</span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-white drop-shadow">{{ $affiliate->user->name ?? 'N/A' }}</p>
                                        <p class="text-xs text-white/70">{{ $affiliate->user->email ?? '' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <span class="text-sm font-bold text-white drop-shadow">
                                    ฿{{ number_format($affiliate->total_earnings ?? 0, 2) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-12 text-center">
                                <i class="fas fa-inbox text-4xl text-white/30 mb-2"></i>
                                <p class="text-white/60">ไม่มีข้อมูล</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- Chart.js Library --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script>
/**
 * Revenue Chart - กราฟแสดงรายได้รายเดือน
 */
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('revenue-chart');
    if (!ctx) return;

    // เตรียมข้อมูล
    const monthlyData = @json($monthlyRevenue);
    const labels = monthlyData.map(item => {
        const [year, month] = item.month.split('-');
        const monthNames = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
        return monthNames[parseInt(month) - 1] + ' ' + year;
    });
    const data = monthlyData.map(item => parseFloat(item.total));

    // สร้าง gradient
    const gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 350);
    gradient.addColorStop(0, 'rgba(59, 130, 246, 0.5)');
    gradient.addColorStop(1, 'rgba(139, 92, 246, 0.1)');

    // สร้าง chart
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'รายได้ (บาท)',
                data: data,
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: gradient,
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: 'rgb(59, 130, 246)',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: 'rgba(255, 255, 255, 0.2)',
                    borderWidth: 1,
                    callbacks: {
                        label: function(context) {
                            return 'รายได้: ฿' + context.parsed.y.toLocaleString('th-TH', {minimumFractionDigits: 2});
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)',
                        drawBorder: false,
                    },
                    ticks: {
                        color: 'rgba(255, 255, 255, 0.8)',
                        callback: function(value) {
                            return '฿' + value.toLocaleString('th-TH');
                        }
                    }
                },
                x: {
                    grid: {
                        display: false,
                        drawBorder: false,
                    },
                    ticks: {
                        color: 'rgba(255, 255, 255, 0.8)',
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index',
            }
        }
    });
});
</script>
@endpush

<style>
/**
 * Glass Fusion Effect - ความโปร่งใสพร้อม backdrop blur
 */
.glass-fusion {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
}

/**
 * Glass Neumorphic Effect
 */
.glass-neu {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}
</style>
