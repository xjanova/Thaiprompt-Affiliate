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
        <div class="lg:col-span-2"
             x-data
             x-init="() => {
                     console.log('🚀 กำลังโหลดกราฟรายได้...');

                     // ตรวจสอบว่า ApexCharts โหลดสำเร็จหรือไม่
                     if (typeof ApexCharts === 'undefined') {
                         console.error('❌ ApexCharts library ไม่โหลด');
                         return;
                     }

                     // ตรวจสอบว่ามี element สำหรับกราฟหรือไม่
                     const chartElement = document.querySelector('#revenue-chart');
                     if (!chartElement) {
                         console.error('❌ ไม่พบ element #revenue-chart');
                         return;
                     }

                     console.log('✅ พบ chart element:', chartElement);

                     // ข้อมูลจริงจากฐานข้อมูล
                     let monthlyData = @json($monthlyRevenue);

                     // ถ้าไม่มีข้อมูล ใช้ข้อมูลจริง 12 เดือนที่ผ่านมา
                     if (!monthlyData || monthlyData.length === 0) {
                         console.log('⚠️  ไม่มีข้อมูลรายได้ ใช้ข้อมูลเริ่มต้น');
                         const now = new Date();
                         monthlyData = [];
                         for (let i = 11; i >= 0; i--) {
                             const date = new Date(now.getFullYear(), now.getMonth() - i, 1);
                             const year = date.getFullYear();
                             const month = String(date.getMonth() + 1).padStart(2, '0');
                             monthlyData.push({
                                 month: `${year}-${month}`,
                                 total: '0.00'
                             });
                         }
                     }

                     // เตรียม labels และ data สำหรับ ApexCharts
                     const categories = monthlyData.map(item => {
                         const [year, month] = item.month.split('-');
                         const monthNames = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
                         return monthNames[parseInt(month) - 1] + ' ' + year;
                     });

                     const seriesData = monthlyData.map(item => parseFloat(item.total || 0));

                     console.log('📊 ข้อมูลกราฟ:', {
                         categories: categories,
                         data: seriesData,
                         rawData: monthlyData
                     });

                     // ตั้งค่า ApexCharts
                     const options = {
                         series: [{
                             name: 'รายได้',
                             data: seriesData
                         }],
                         chart: {
                             type: 'area',
                             height: 350,
                             fontFamily: 'Inter, Noto Sans Thai, sans-serif',
                             toolbar: { show: false },
                             zoom: { enabled: false },
                             animations: {
                                 enabled: true,
                                 easing: 'easeinout',
                                 speed: 800,
                                 animateGradually: { enabled: true, delay: 150 },
                                 dynamicAnimation: { enabled: true, speed: 350 }
                             }
                         },
                         dataLabels: { enabled: false },
                         stroke: {
                             curve: 'smooth',
                             width: 3,
                             colors: ['#3b82f6']
                         },
                         fill: {
                             type: 'gradient',
                             gradient: {
                                 shadeIntensity: 1,
                                 opacityFrom: 0.6,
                                 opacityTo: 0.1,
                                 stops: [0, 90, 100],
                                 colorStops: [
                                     { offset: 0, color: '#3b82f6', opacity: 0.6 },
                                     { offset: 50, color: '#8b5cf6', opacity: 0.4 },
                                     { offset: 100, color: '#ec4899', opacity: 0.1 }
                                 ]
                             }
                         },
                         xaxis: {
                             categories: categories,
                             labels: {
                                 style: {
                                     colors: 'rgba(255, 255, 255, 0.9)',
                                     fontSize: '11px',
                                     fontWeight: 500
                                 },
                                 rotate: -45,
                                 rotateAlways: false
                             },
                             axisBorder: { show: false },
                             axisTicks: { show: false }
                         },
                         yaxis: {
                             labels: {
                                 style: {
                                     colors: 'rgba(255, 255, 255, 0.9)',
                                     fontSize: '12px',
                                     fontWeight: 500
                                 },
                                 formatter: function(value) {
                                     if (value >= 1000000) {
                                         return '฿' + (value / 1000000).toFixed(1) + 'M';
                                     } else if (value >= 1000) {
                                         return '฿' + (value / 1000).toFixed(0) + 'K';
                                     }
                                     return '฿' + value.toLocaleString('th-TH');
                                 }
                             }
                         },
                         grid: {
                             borderColor: 'rgba(255, 255, 255, 0.1)',
                             strokeDashArray: 3,
                             xaxis: { lines: { show: false } },
                             yaxis: { lines: { show: true } },
                             padding: { top: 0, right: 10, bottom: 0, left: 10 }
                         },
                         tooltip: {
                             theme: 'dark',
                             style: {
                                 fontSize: '13px',
                                 fontFamily: 'Inter, Noto Sans Thai, sans-serif'
                             },
                             x: { show: true },
                             y: {
                                 formatter: function(value) {
                                     return '฿' + value.toLocaleString('th-TH', {
                                         minimumFractionDigits: 2,
                                         maximumFractionDigits: 2
                                     });
                                 }
                             },
                             marker: { show: true }
                         },
                         markers: {
                             size: 5,
                             colors: ['#3b82f6'],
                             strokeColors: '#fff',
                             strokeWidth: 2,
                             hover: { size: 8 }
                         }
                     };

                     // สร้าง chart
                     try {
                         const chart = new ApexCharts(chartElement, options);
                         chart.render();
                         console.log('✅ กราฟแสดงผลสำเร็จ!');
                     } catch (error) {
                         console.error('❌ เกิดข้อผิดพลาดในการสร้างกราฟ:', error);
                     }
             }">
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
{{-- ApexCharts Library --}}
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.45.1/dist/apexcharts.min.js"></script>
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
