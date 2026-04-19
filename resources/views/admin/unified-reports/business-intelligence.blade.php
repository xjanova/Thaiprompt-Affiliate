{{--
    Business Intelligence Dashboard
    แดชบอร์ดวิเคราะห์ธุรกิจแบบเต็มรูปแบบ พร้อมกราฟและ KPIs
--}}
@extends('layouts.admin-v3')

@section('title', $pageTitle ?? 'Business Intelligence Dashboard')

@section('content')
<div x-data="biDashboard()" class="min-h-screen bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 py-6">
    <div class="max-w-[1920px] mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Header --}}
        <div class="mb-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-white flex items-center gap-3">
                        <div class="p-2 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        {{ $pageTitle ?? 'Business Intelligence' }}
                    </h1>
                    <p class="mt-1 text-gray-300">{{ $pageDescription ?? 'วิเคราะห์ภาพรวมธุรกิจจากทุกมิติ' }}</p>
                </div>

                <div class="flex flex-wrap gap-3">
                    {{-- Period Selector --}}
                    <select x-model="period" @change="refreshData()"
                            class="px-4 py-2 bg-white/10 border border-white/20 rounded-lg text-white
                                   backdrop-blur-sm focus:ring-2 focus:ring-indigo-500">
                        <option value="today" class="bg-gray-800">วันนี้</option>
                        <option value="week" class="bg-gray-800">7 วัน</option>
                        <option value="month" class="bg-gray-800" selected>30 วัน</option>
                        <option value="quarter" class="bg-gray-800">3 เดือน</option>
                        <option value="year" class="bg-gray-800">1 ปี</option>
                    </select>

                    {{-- Refresh Button --}}
                    <button @click="refreshData()"
                            class="px-4 py-2 bg-white/10 border border-white/20 rounded-lg text-white
                                   hover:bg-white/20 transition-colors backdrop-blur-sm">
                        <svg class="w-5 h-5" :class="{'animate-spin': loading}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </button>

                    {{-- Export --}}
                    <a href="{{ route('admin.unified-reports.export', ['type' => 'executive', 'period' => $period]) }}"
                       class="px-4 py-2 bg-gradient-to-r from-green-500 to-emerald-600 rounded-lg text-white
                              font-medium hover:from-green-600 hover:to-emerald-700 transition-all">
                        <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Export
                    </a>
                </div>
            </div>
        </div>

        {{-- KPI Cards Row --}}
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-6">
            {{-- Total Revenue --}}
            <div class="col-span-2 bg-gradient-to-br from-green-500/20 to-emerald-500/10 backdrop-blur-xl
                        rounded-2xl p-6 border border-green-500/30">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-green-300 text-sm font-medium">รายได้รวม</p>
                        <p class="text-3xl font-bold text-white mt-1">
                            ฿{{ number_format($summary['revenue']['total'] ?? 0, 0) }}
                        </p>
                        <div class="flex items-center gap-1 mt-2">
                            <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                            </svg>
                            <span class="text-green-400 text-sm">+12.5%</span>
                        </div>
                    </div>
                    <div class="w-16 h-16 rounded-2xl bg-green-500/20 flex items-center justify-center">
                        <svg class="w-8 h-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Users --}}
            <div class="bg-gradient-to-br from-blue-500/20 to-indigo-500/10 backdrop-blur-xl
                        rounded-2xl p-6 border border-blue-500/30">
                <p class="text-blue-300 text-sm font-medium">ผู้ใช้</p>
                <p class="text-2xl font-bold text-white mt-1">{{ number_format($summary['users']['total'] ?? 0) }}</p>
                <p class="text-blue-400 text-xs mt-1">+{{ $summary['users']['new'] ?? 0 }} ใหม่</p>
            </div>

            {{-- Affiliates --}}
            <div class="bg-gradient-to-br from-purple-500/20 to-pink-500/10 backdrop-blur-xl
                        rounded-2xl p-6 border border-purple-500/30">
                <p class="text-purple-300 text-sm font-medium">Affiliates</p>
                <p class="text-2xl font-bold text-white mt-1">{{ number_format($summary['mlm']['total_affiliates'] ?? 0) }}</p>
                <p class="text-purple-400 text-xs mt-1">{{ $summary['mlm']['active_affiliates'] ?? 0 }} active</p>
            </div>

            {{-- Orders --}}
            <div class="bg-gradient-to-br from-orange-500/20 to-red-500/10 backdrop-blur-xl
                        rounded-2xl p-6 border border-orange-500/30">
                <p class="text-orange-300 text-sm font-medium">คำสั่งซื้อ</p>
                <p class="text-2xl font-bold text-white mt-1">{{ number_format($summary['ecommerce']['total_orders'] ?? 0) }}</p>
                <p class="text-orange-400 text-xs mt-1">{{ $summary['ecommerce']['completed_orders'] ?? 0 }} สำเร็จ</p>
            </div>

            {{-- Commissions --}}
            <div class="bg-gradient-to-br from-yellow-500/20 to-amber-500/10 backdrop-blur-xl
                        rounded-2xl p-6 border border-yellow-500/30">
                <p class="text-yellow-300 text-sm font-medium">คอมมิชชั่น</p>
                <p class="text-2xl font-bold text-white mt-1">฿{{ number_format($summary['mlm']['total_commissions'] ?? 0, 0) }}</p>
                <p class="text-yellow-400 text-xs mt-1">{{ number_format($summary['mlm']['pending_commissions'] ?? 0, 0) }} รอจ่าย</p>
            </div>
        </div>

        {{-- Charts Grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6 mb-6">
            {{-- Revenue Trend --}}
            <div class="xl:col-span-2 bg-white/5 backdrop-blur-xl rounded-2xl p-6 border border-white/10">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                        แนวโน้มรายได้
                    </h3>
                    <div class="flex gap-2">
                        <button class="px-3 py-1 text-xs bg-indigo-500/20 text-indigo-300 rounded-lg">วัน</button>
                        <button class="px-3 py-1 text-xs bg-white/5 text-gray-400 rounded-lg hover:bg-white/10">สัปดาห์</button>
                        <button class="px-3 py-1 text-xs bg-white/5 text-gray-400 rounded-lg hover:bg-white/10">เดือน</button>
                    </div>
                </div>
                <div class="h-80">
                    <canvas id="revenueTrendChart"></canvas>
                </div>
            </div>

            {{-- Revenue Distribution --}}
            <div class="bg-white/5 backdrop-blur-xl rounded-2xl p-6 border border-white/10">
                <h3 class="text-lg font-semibold text-white mb-6 flex items-center gap-2">
                    <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
                    </svg>
                    สัดส่วนรายได้
                </h3>
                <div class="h-64">
                    <canvas id="revenueDistributionChart"></canvas>
                </div>
                <div class="mt-4 space-y-2">
                    <div class="flex items-center justify-between text-sm">
                        <span class="flex items-center gap-2 text-gray-300">
                            <span class="w-3 h-3 bg-indigo-500 rounded-full"></span>
                            E-commerce
                        </span>
                        <span class="text-white font-medium">฿{{ number_format($summary['revenue']['ecommerce'] ?? 0, 0) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="flex items-center gap-2 text-gray-300">
                            <span class="w-3 h-3 bg-green-500 rounded-full"></span>
                            โรงแรม
                        </span>
                        <span class="text-white font-medium">฿{{ number_format($summary['revenue']['hotel'] ?? 0, 0) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="flex items-center gap-2 text-gray-300">
                            <span class="w-3 h-3 bg-orange-500 rounded-full"></span>
                            POS
                        </span>
                        <span class="text-white font-medium">฿{{ number_format($summary['revenue']['pos'] ?? 0, 0) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="flex items-center gap-2 text-gray-300">
                            <span class="w-3 h-3 bg-pink-500 rounded-full"></span>
                            บริการ
                        </span>
                        <span class="text-white font-medium">฿{{ number_format($summary['revenue']['service'] ?? 0, 0) }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Second Row Charts --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            {{-- User Growth --}}
            <div class="bg-white/5 backdrop-blur-xl rounded-2xl p-6 border border-white/10">
                <h3 class="text-lg font-semibold text-white mb-6 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    การเติบโตผู้ใช้
                </h3>
                <div class="h-64">
                    <canvas id="userGrowthChart"></canvas>
                </div>
            </div>

            {{-- Commission Breakdown --}}
            <div class="bg-white/5 backdrop-blur-xl rounded-2xl p-6 border border-white/10">
                <h3 class="text-lg font-semibold text-white mb-6 flex items-center gap-2">
                    <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    คอมมิชชั่น MLM
                </h3>
                <div class="space-y-4">
                    {{-- Total Commission --}}
                    <div class="p-4 bg-white/5 rounded-xl">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-400 text-sm">คอมมิชชั่นรวม</span>
                            <span class="text-xl font-bold text-white">฿{{ number_format($summary['mlm']['total_commissions'] ?? 0, 2) }}</span>
                        </div>
                        <div class="mt-3 h-2 bg-gray-700 rounded-full overflow-hidden">
                            @php
                                $total = ($summary['mlm']['total_commissions'] ?? 1) ?: 1;
                                $paid = ($summary['mlm']['paid_commissions'] ?? 0);
                                $paidPercent = min(($paid / $total) * 100, 100);
                            @endphp
                            <div class="h-full bg-gradient-to-r from-green-500 to-emerald-500" style="width: {{ $paidPercent }}%"></div>
                        </div>
                        <div class="flex justify-between mt-2 text-xs">
                            <span class="text-green-400">จ่ายแล้ว: ฿{{ number_format($paid, 0) }}</span>
                            <span class="text-yellow-400">รอจ่าย: ฿{{ number_format($summary['mlm']['pending_commissions'] ?? 0, 0) }}</span>
                        </div>
                    </div>

                    {{-- Commission by Type --}}
                    @if(isset($mlmReport['commission_by_type']) && count($mlmReport['commission_by_type']) > 0)
                        @foreach(array_slice($mlmReport['commission_by_type'], 0, 4) as $type)
                            <div class="flex items-center justify-between p-3 bg-white/5 rounded-lg">
                                <span class="text-gray-300 text-sm">{{ $type['type'] ?? 'N/A' }}</span>
                                <div class="text-right">
                                    <span class="text-white font-medium">฿{{ number_format($type['total'] ?? 0, 0) }}</span>
                                    <span class="text-gray-500 text-xs ml-2">({{ $type['count'] ?? 0 }})</span>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <p class="text-gray-500 text-center py-4">ไม่มีข้อมูลคอมมิชชั่น</p>
                    @endif
                </div>
            </div>

            {{-- Order Analytics --}}
            <div class="bg-white/5 backdrop-blur-xl rounded-2xl p-6 border border-white/10">
                <h3 class="text-lg font-semibold text-white mb-6 flex items-center gap-2">
                    <svg class="w-5 h-5 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    วิเคราะห์คำสั่งซื้อ
                </h3>
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="p-4 bg-white/5 rounded-xl text-center">
                            <p class="text-gray-400 text-xs">คำสั่งซื้อทั้งหมด</p>
                            <p class="text-2xl font-bold text-white mt-1">{{ number_format($ecommerceReport['summary']['total_orders'] ?? 0) }}</p>
                        </div>
                        <div class="p-4 bg-white/5 rounded-xl text-center">
                            <p class="text-gray-400 text-xs">มูลค่าเฉลี่ย</p>
                            <p class="text-2xl font-bold text-white mt-1">฿{{ number_format($ecommerceReport['summary']['average_order_value'] ?? 0, 0) }}</p>
                        </div>
                    </div>

                    <div class="h-40">
                        <canvas id="orderStatusChart"></canvas>
                    </div>

                    {{-- Top Products --}}
                    @if(isset($ecommerceReport['top_products']) && count($ecommerceReport['top_products']) > 0)
                        <div class="pt-4 border-t border-white/10">
                            <p class="text-sm text-gray-400 mb-3">สินค้าขายดี</p>
                            @foreach(array_slice($ecommerceReport['top_products'], 0, 3) as $product)
                                <div class="flex items-center justify-between py-2">
                                    <span class="text-gray-300 text-sm truncate" style="max-width: 150px;">{{ $product['name'] ?? 'N/A' }}</span>
                                    <span class="text-white font-medium text-sm">{{ $product['total_sold'] ?? 0 }} ชิ้น</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Third Row - Detailed Tables --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Top Earners --}}
            <div class="bg-white/5 backdrop-blur-xl rounded-2xl p-6 border border-white/10">
                <h3 class="text-lg font-semibold text-white mb-6 flex items-center gap-2">
                    <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                    </svg>
                    ผู้มีรายได้สูงสุด
                </h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs text-gray-400 uppercase border-b border-white/10">
                                <th class="pb-3">#</th>
                                <th class="pb-3">ชื่อ</th>
                                <th class="pb-3 text-right">รายได้</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(($mlmReport['top_earners'] ?? []) as $index => $earner)
                                <tr class="border-b border-white/5 hover:bg-white/5">
                                    <td class="py-3 text-gray-400">{{ $index + 1 }}</td>
                                    <td class="py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500
                                                        flex items-center justify-center text-white text-xs font-bold">
                                                {{ substr($earner['user']['name'] ?? 'N', 0, 1) }}
                                            </div>
                                            <div>
                                                <p class="text-white text-sm font-medium">{{ $earner['user']['name'] ?? 'N/A' }}</p>
                                                <p class="text-gray-500 text-xs">{{ $earner['user']['email'] ?? '' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 text-right">
                                        <span class="text-green-400 font-medium">฿{{ number_format($earner['total_earned'] ?? 0, 2) }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-8 text-center text-gray-500">ไม่มีข้อมูล</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Wallet Flow --}}
            <div class="bg-white/5 backdrop-blur-xl rounded-2xl p-6 border border-white/10">
                <h3 class="text-lg font-semibold text-white mb-6 flex items-center gap-2">
                    <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                    กระแสเงิน (Wallet)
                </h3>

                <div class="grid grid-cols-3 gap-4 mb-6">
                    <div class="p-4 bg-blue-500/10 rounded-xl text-center border border-blue-500/20">
                        <p class="text-blue-400 text-xs">ฝากรวม</p>
                        <p class="text-xl font-bold text-white mt-1">฿{{ number_format($financeReport['summary']['total_deposits'] ?? 0, 0) }}</p>
                    </div>
                    <div class="p-4 bg-red-500/10 rounded-xl text-center border border-red-500/20">
                        <p class="text-red-400 text-xs">ถอนรวม</p>
                        <p class="text-xl font-bold text-white mt-1">฿{{ number_format($financeReport['summary']['total_withdrawals'] ?? 0, 0) }}</p>
                    </div>
                    <div class="p-4 bg-purple-500/10 rounded-xl text-center border border-purple-500/20">
                        <p class="text-purple-400 text-xs">Net Flow</p>
                        <p class="text-xl font-bold text-white mt-1">฿{{ number_format($financeReport['summary']['net_flow'] ?? 0, 0) }}</p>
                    </div>
                </div>

                <div class="h-48">
                    <canvas id="walletFlowChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function biDashboard() {
    return {
        period: '{{ $period }}',
        loading: false,

        init() {
            this.initCharts();
        },

        refreshData() {
            this.loading = true;
            window.location.href = '{{ route("admin.unified-reports.business-intelligence") }}?period=' + this.period;
        },

        initCharts() {
            const isDark = true;
            const gridColor = 'rgba(255, 255, 255, 0.1)';
            const textColor = 'rgba(255, 255, 255, 0.7)';

            // Revenue Trend Chart
            const revenueTrends = @json($trends['revenue'] ?? []);
            const revenueTrendCtx = document.getElementById('revenueTrendChart');
            if (revenueTrendCtx && revenueTrends.length > 0) {
                new Chart(revenueTrendCtx, {
                    type: 'line',
                    data: {
                        labels: revenueTrends.map(item => item.date),
                        datasets: [{
                            label: 'รายได้',
                            data: revenueTrends.map(item => item.total || 0),
                            borderColor: '#10B981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#10B981',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            x: {
                                grid: { color: gridColor },
                                ticks: { color: textColor }
                            },
                            y: {
                                grid: { color: gridColor },
                                ticks: {
                                    color: textColor,
                                    callback: function(value) {
                                        return '฿' + value.toLocaleString();
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Revenue Distribution Chart
            const revenueDistCtx = document.getElementById('revenueDistributionChart');
            if (revenueDistCtx) {
                const revenueData = @json($summary['revenue'] ?? []);
                new Chart(revenueDistCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['E-commerce', 'โรงแรม', 'POS', 'บริการ'],
                        datasets: [{
                            data: [
                                revenueData.ecommerce || 0,
                                revenueData.hotel || 0,
                                revenueData.pos || 0,
                                revenueData.service || 0
                            ],
                            backgroundColor: ['#6366F1', '#10B981', '#F59E0B', '#EC4899'],
                            borderWidth: 0,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '70%',
                        plugins: {
                            legend: { display: false }
                        }
                    }
                });
            }

            // User Growth Chart
            const userTrends = @json($trends['users'] ?? []);
            const userGrowthCtx = document.getElementById('userGrowthChart');
            if (userGrowthCtx && userTrends.length > 0) {
                new Chart(userGrowthCtx, {
                    type: 'bar',
                    data: {
                        labels: userTrends.map(item => item.date),
                        datasets: [{
                            label: 'ผู้ใช้ใหม่',
                            data: userTrends.map(item => item.count || 0),
                            backgroundColor: 'rgba(99, 102, 241, 0.8)',
                            borderRadius: 6,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: { color: textColor }
                            },
                            y: {
                                grid: { color: gridColor },
                                ticks: { color: textColor }
                            }
                        }
                    }
                });
            }

            // Order Status Chart
            const orderStatusCtx = document.getElementById('orderStatusChart');
            if (orderStatusCtx) {
                const statusData = @json($ecommerceReport['order_status_distribution'] ?? []);
                new Chart(orderStatusCtx, {
                    type: 'bar',
                    data: {
                        labels: statusData.map(item => item.status || 'N/A'),
                        datasets: [{
                            data: statusData.map(item => item.count || 0),
                            backgroundColor: ['#10B981', '#F59E0B', '#EF4444', '#6366F1', '#8B5CF6'],
                            borderRadius: 4,
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            x: {
                                grid: { color: gridColor },
                                ticks: { color: textColor }
                            },
                            y: {
                                grid: { display: false },
                                ticks: { color: textColor }
                            }
                        }
                    }
                });
            }

            // Wallet Flow Chart
            const dailyFlow = @json($financeReport['daily_flow'] ?? []);
            const walletFlowCtx = document.getElementById('walletFlowChart');
            if (walletFlowCtx && dailyFlow.length > 0) {
                new Chart(walletFlowCtx, {
                    type: 'line',
                    data: {
                        labels: dailyFlow.map(item => item.date),
                        datasets: [
                            {
                                label: 'ฝาก',
                                data: dailyFlow.map(item => item.deposits || 0),
                                borderColor: '#3B82F6',
                                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                fill: true,
                                tension: 0.4,
                            },
                            {
                                label: 'ถอน',
                                data: dailyFlow.map(item => item.withdrawals || 0),
                                borderColor: '#EF4444',
                                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                                fill: true,
                                tension: 0.4,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                                labels: { color: textColor }
                            }
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: { color: textColor }
                            },
                            y: {
                                grid: { color: gridColor },
                                ticks: { color: textColor }
                            }
                        }
                    }
                });
            }
        }
    }
}
</script>
@endpush
@endsection
