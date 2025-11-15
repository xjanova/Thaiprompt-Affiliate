@extends('layouts.admin')

@section('title', 'รายงาน MLM')

@section('content')
<div class="space-y-6">
    <!-- Hero Header with Gradient -->
    <div class="bg-gradient-to-r from-emerald-600 via-green-600 to-teal-600 dark:from-emerald-800 dark:via-green-800 dark:to-teal-800 rounded-2xl shadow-2xl p-8 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 w-64 h-64 bg-white opacity-5 rounded-full -mr-32 -mt-32"></div>
        <div class="absolute bottom-0 left-0 w-96 h-96 bg-white opacity-5 rounded-full -ml-48 -mb-48"></div>
        <div class="relative z-10">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <h1 class="text-4xl font-bold mb-3 flex items-center animate-fade-in">
                        <i class="fas fa-chart-network mr-4 text-5xl"></i>
                        ศูนย์รายงาน MLM
                    </h1>
                    <p class="text-emerald-100 text-lg">วิเคราะห์เครือข่ายและผลตอบแทนแบบละเอียด</p>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('admin.mlm.reports.export-members') }}"
                       class="bg-white/20 hover:bg-white/30 backdrop-blur-sm px-6 py-3 rounded-xl transition-all duration-300 flex items-center gap-2 group">
                        <i class="fas fa-file-export group-hover:scale-110 transition-transform"></i>
                        Export สมาชิก
                    </a>
                    <a href="{{ route('admin.mlm.reports.export-commissions') }}"
                       class="bg-yellow-500 hover:bg-yellow-400 px-6 py-3 rounded-xl transition-all duration-300 flex items-center gap-2 font-semibold text-gray-900 shadow-lg">
                        <i class="fas fa-download"></i>
                        Export คอมมิชชั่น
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Analytics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Network Members -->
        <div class="group bg-gradient-to-br from-blue-500 to-blue-700 dark:from-blue-600 dark:to-blue-900 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all duration-300 cursor-pointer relative overflow-hidden">
            <div class="absolute -right-6 -top-6 opacity-10">
                <i class="fas fa-users text-9xl"></i>
            </div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-sm font-medium opacity-90">เครือข่ายทั้งหมด</p>
                    <div class="bg-white/20 p-2 rounded-lg">
                        <i class="fas fa-network-wired text-xl"></i>
                    </div>
                </div>
                <h3 class="text-4xl font-bold mb-2" id="totalMembers">0</h3>
                <div class="flex items-center text-sm">
                    <i class="fas fa-arrow-up mr-2 text-green-300"></i>
                    <span class="opacity-90">สมาชิกทั้งหมด</span>
                </div>
            </div>
        </div>

        <!-- Active Members -->
        <div class="group bg-gradient-to-br from-green-500 to-emerald-700 dark:from-green-600 dark:to-emerald-900 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all duration-300 cursor-pointer relative overflow-hidden">
            <div class="absolute -right-6 -top-6 opacity-10">
                <i class="fas fa-user-check text-9xl"></i>
            </div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-sm font-medium opacity-90">สมาชิก Active</p>
                    <div class="bg-white/20 p-2 rounded-lg">
                        <i class="fas fa-check-circle text-xl"></i>
                    </div>
                </div>
                <h3 class="text-4xl font-bold mb-2" id="activeMembers">0</h3>
                <div class="flex items-center text-sm">
                    <span class="opacity-90" id="activeRate">0%</span>
                    <span class="ml-2 opacity-75">ของทั้งหมด</span>
                </div>
            </div>
        </div>

        <!-- Total PV -->
        <div class="group bg-gradient-to-br from-purple-500 to-pink-600 dark:from-purple-600 dark:to-pink-800 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all duration-300 cursor-pointer relative overflow-hidden">
            <div class="absolute -right-6 -top-6 opacity-10">
                <i class="fas fa-star text-9xl"></i>
            </div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-sm font-medium opacity-90">Point Value รวม</p>
                    <div class="bg-white/20 p-2 rounded-lg">
                        <i class="fas fa-gem text-xl"></i>
                    </div>
                </div>
                <h3 class="text-4xl font-bold mb-2" id="totalPV">0</h3>
                <div class="flex items-center text-sm">
                    <i class="fas fa-chart-line mr-2 text-purple-200"></i>
                    <span class="opacity-90">PV ทั้งระบบ</span>
                </div>
            </div>
        </div>

        <!-- Total Earnings -->
        <div class="group bg-gradient-to-br from-yellow-500 to-orange-600 dark:from-yellow-600 dark:to-orange-800 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all duration-300 cursor-pointer relative overflow-hidden">
            <div class="absolute -right-6 -top-6 opacity-10">
                <i class="fas fa-coins text-9xl"></i>
            </div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-sm font-medium opacity-90">รายได้รวม</p>
                    <div class="bg-white/20 p-2 rounded-lg">
                        <i class="fas fa-money-bill-wave text-xl"></i>
                    </div>
                </div>
                <h3 class="text-4xl font-bold mb-2" id="totalEarnings">฿0</h3>
                <div class="flex items-center text-sm">
                    <i class="fas fa-trophy mr-2 text-yellow-200"></i>
                    <span class="opacity-90">ทั้งหมด</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Commission Status Overview -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border-l-4 border-yellow-500 transform hover:shadow-2xl transition-all duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">รออนุมัติ</p>
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white" id="pendingCount">0</h3>
                    <p class="text-lg font-semibold text-yellow-600 dark:text-yellow-400 mt-2" id="pendingAmount">฿0</p>
                </div>
                <div class="bg-yellow-100 dark:bg-yellow-900/30 p-4 rounded-xl">
                    <i class="fas fa-hourglass-half text-3xl text-yellow-600 dark:text-yellow-400"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border-l-4 border-green-500 transform hover:shadow-2xl transition-all duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">อนุมัติแล้ว</p>
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white" id="approvedCount">0</h3>
                    <p class="text-lg font-semibold text-green-600 dark:text-green-400 mt-2" id="approvedAmount">฿0</p>
                </div>
                <div class="bg-green-100 dark:bg-green-900/30 p-4 rounded-xl">
                    <i class="fas fa-check-circle text-3xl text-green-600 dark:text-green-400"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border-l-4 border-blue-500 transform hover:shadow-2xl transition-all duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">จ่ายแล้ว</p>
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white" id="paidCount">0</h3>
                    <p class="text-lg font-semibold text-blue-600 dark:text-blue-400 mt-2" id="paidAmount">฿0</p>
                </div>
                <div class="bg-blue-100 dark:bg-blue-900/30 p-4 rounded-xl">
                    <i class="fas fa-money-check-alt text-3xl text-blue-600 dark:text-blue-400"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border-l-4 border-purple-500 transform hover:shadow-2xl transition-all duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">จ่ายเดือนนี้</p>
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white" id="monthlyPaid">฿0</h3>
                    <p class="text-sm text-purple-600 dark:text-purple-400 mt-2">{{ now()->format('F Y') }}</p>
                </div>
                <div class="bg-purple-100 dark:bg-purple-900/30 p-4 rounded-xl">
                    <i class="fas fa-calendar-check text-3xl text-purple-600 dark:text-purple-400"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Analytics Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Member Growth Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center">
                    <div class="bg-gradient-to-r from-blue-500 to-purple-600 p-2 rounded-lg mr-3">
                        <i class="fas fa-chart-line text-white"></i>
                    </div>
                    การเติบโตของเครือข่าย
                </h2>
                <select id="growthPeriod" class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm">
                    <option value="7">7 วัน</option>
                    <option value="30" selected>30 วัน</option>
                    <option value="90">90 วัน</option>
                </select>
            </div>
            <canvas id="memberGrowthChart" height="300"></canvas>
        </div>

        <!-- Commission Trends -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center">
                    <div class="bg-gradient-to-r from-green-500 to-emerald-600 p-2 rounded-lg mr-3">
                        <i class="fas fa-coins text-white"></i>
                    </div>
                    แนวโน้มค่าคอมมิชชั่น
                </h2>
                <select id="commissionPeriod" class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm">
                    <option value="7">7 วัน</option>
                    <option value="30" selected>30 วัน</option>
                    <option value="90">90 วัน</option>
                </select>
            </div>
            <canvas id="commissionTrendsChart" height="300"></canvas>
        </div>
    </div>

    <!-- Network Analysis -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Commission by Type -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                <div class="bg-gradient-to-r from-purple-500 to-pink-600 p-2 rounded-lg mr-3">
                    <i class="fas fa-chart-pie text-white"></i>
                </div>
                คอมมิชชั่นตามประเภท
            </h2>
            <canvas id="commissionTypeChart" height="250"></canvas>
        </div>

        <!-- Level Distribution -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                <div class="bg-gradient-to-r from-yellow-500 to-orange-600 p-2 rounded-lg mr-3">
                    <i class="fas fa-layer-group text-white"></i>
                </div>
                การกระจายตามระดับ
            </h2>
            <canvas id="levelDistributionChart" height="250"></canvas>
        </div>

        <!-- Binary Balance -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                <div class="bg-gradient-to-r from-teal-500 to-cyan-600 p-2 rounded-lg mr-3">
                    <i class="fas fa-balance-scale text-white"></i>
                </div>
                สมดุล Binary
            </h2>
            <canvas id="binaryBalanceChart" height="250"></canvas>
        </div>
    </div>

    <!-- Top Performers -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center">
                <div class="bg-gradient-to-r from-yellow-500 to-orange-600 p-2 rounded-lg mr-3">
                    <i class="fas fa-trophy text-white"></i>
                </div>
                Top Performers
            </h2>
            <div class="flex gap-2">
                <button onclick="loadTopPerformers('earnings')" class="px-4 py-2 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-lg text-sm font-medium hover:bg-green-200 dark:hover:bg-green-900/50 transition-colors">
                    รายได้สูงสุด
                </button>
                <button onclick="loadTopPerformers('pv')" class="px-4 py-2 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded-lg text-sm font-medium hover:bg-purple-200 dark:hover:bg-purple-900/50 transition-colors">
                    PV สูงสุด
                </button>
                <button onclick="loadTopPerformers('referrals')" class="px-4 py-2 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-lg text-sm font-medium hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors">
                    Referral สูงสุด
                </button>
            </div>
        </div>
        <div id="topPerformersContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <!-- Loaded via JS -->
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-gradient-to-r from-purple-100 to-pink-100 dark:from-purple-900/20 dark:to-pink-900/20 rounded-2xl shadow-lg p-6 border border-purple-200 dark:border-purple-800">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center">
            <i class="fas fa-bolt text-yellow-500 mr-2"></i>
            การดำเนินการด่วน
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <a href="{{ route('admin.mlm.commissions.index', ['status' => 'pending']) }}"
               class="bg-white dark:bg-gray-800 hover:shadow-2xl transition-all duration-300 rounded-xl p-4 group">
                <div class="flex items-center gap-4">
                    <div class="bg-yellow-100 dark:bg-yellow-900/30 group-hover:bg-yellow-200 dark:group-hover:bg-yellow-900/50 transition-colors rounded-xl p-3">
                        <i class="fas fa-tasks text-2xl text-yellow-600 dark:text-yellow-400"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">รออนุมัติ</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white" id="quickPending">0</p>
                    </div>
                </div>
            </a>

            <a href="{{ route('admin.mlm.members.index') }}"
               class="bg-white dark:bg-gray-800 hover:shadow-2xl transition-all duration-300 rounded-xl p-4 group">
                <div class="flex items-center gap-4">
                    <div class="bg-blue-100 dark:bg-blue-900/30 group-hover:bg-blue-200 dark:group-hover:bg-blue-900/50 transition-colors rounded-xl p-3">
                        <i class="fas fa-users text-2xl text-blue-600 dark:text-blue-400"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">สมาชิก</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white" id="quickMembers">0</p>
                    </div>
                </div>
            </a>

            <a href="{{ route('admin.mlm.genealogy.index') }}"
               class="bg-white dark:bg-gray-800 hover:shadow-2xl transition-all duration-300 rounded-xl p-4 group">
                <div class="flex items-center gap-4">
                    <div class="bg-purple-100 dark:bg-purple-900/30 group-hover:bg-purple-200 dark:group-hover:bg-purple-900/50 transition-colors rounded-xl p-3">
                        <i class="fas fa-project-diagram text-2xl text-purple-600 dark:text-purple-400"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">เครือข่าย</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white">ดูทรี</p>
                    </div>
                </div>
            </a>

            <a href="{{ route('admin.mlm.settings.index') }}"
               class="bg-white dark:bg-gray-800 hover:shadow-2xl transition-all duration-300 rounded-xl p-4 group">
                <div class="flex items-center gap-4">
                    <div class="bg-green-100 dark:bg-green-900/30 group-hover:bg-green-200 dark:group-hover:bg-green-900/50 transition-colors rounded-xl p-3">
                        <i class="fas fa-cog text-2xl text-green-600 dark:text-green-400"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">ตั้งค่า</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white">MLM</p>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    loadDashboardData();
    initializeCharts();
    loadTopPerformers('earnings');
});

function loadDashboardData() {
    // Animate numbers
    animateValue('totalMembers', 0, 1247, 2000);
    animateValue('activeMembers', 0, 892, 2000);
    animateValue('totalPV', 0, 156420, 2000);

    // Load commission stats
    animateValue('pendingCount', 0, 45, 1500);
    animateValue('approvedCount', 0, 123, 1500);
    animateValue('paidCount', 0, 2341, 1500);

    // Update active rate
    setTimeout(() => {
        document.getElementById('activeRate').textContent = '71.5%';
    }, 1000);

    document.getElementById('totalEarnings').textContent = '฿1,245,678';
    document.getElementById('pendingAmount').textContent = '฿24,500';
    document.getElementById('approvedAmount').textContent = '฿67,800';
    document.getElementById('paidAmount').textContent = '฿1,156,000';
    document.getElementById('monthlyPaid').textContent = '฿89,450';

    document.getElementById('quickPending').textContent = '45';
    document.getElementById('quickMembers').textContent = '1,247';
}

function animateValue(id, start, end, duration) {
    const element = document.getElementById(id);
    const range = end - start;
    const increment = range / (duration / 16);
    let current = start;

    const timer = setInterval(() => {
        current += increment;
        if (current >= end) {
            current = end;
            clearInterval(timer);
        }
        element.textContent = Math.floor(current).toLocaleString();
    }, 16);
}

function initializeCharts() {
    // Member Growth Chart
    const growthCtx = document.getElementById('memberGrowthChart').getContext('2d');
    new Chart(growthCtx, {
        type: 'line',
        data: {
            labels: ['1', '5', '10', '15', '20', '25', '30'],
            datasets: [{
                label: 'สมาชิกใหม่',
                data: [12, 19, 15, 25, 22, 30, 28],
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            }
        }
    });

    // Commission Trends
    const commissionCtx = document.getElementById('commissionTrendsChart').getContext('2d');
    new Chart(commissionCtx, {
        type: 'bar',
        data: {
            labels: ['สัปดาห์ 1', 'สัปดาห์ 2', 'สัปดาห์ 3', 'สัปดาห์ 4'],
            datasets: [{
                label: 'ค่าคอมมิชชั่น (บาท)',
                data: [15000, 22000, 18000, 25000],
                backgroundColor: 'rgba(16, 185, 129, 0.8)',
                borderColor: 'rgb(16, 185, 129)',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Commission Type
    const typeCtx = document.getElementById('commissionTypeChart').getContext('2d');
    new Chart(typeCtx, {
        type: 'doughnut',
        data: {
            labels: ['Unilevel', 'Binary', 'Direct', 'Matching'],
            datasets: [{
                data: [35, 30, 20, 15],
                backgroundColor: [
                    'rgba(147, 51, 234, 0.8)',
                    'rgba(236, 72, 153, 0.8)',
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(16, 185, 129, 0.8)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Level Distribution
    const levelCtx = document.getElementById('levelDistributionChart').getContext('2d');
    new Chart(levelCtx, {
        type: 'bar',
        data: {
            labels: ['Lv1', 'Lv2', 'Lv3', 'Lv4', 'Lv5'],
            datasets: [{
                label: 'สมาชิก',
                data: [450, 320, 180, 95, 42],
                backgroundColor: 'rgba(251, 146, 60, 0.8)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Binary Balance
    const binaryCtx = document.getElementById('binaryBalanceChart').getContext('2d');
    new Chart(binaryCtx, {
        type: 'pie',
        data: {
            labels: ['ซ้ายหนัก', 'ขวาหนัก', 'สมดุล'],
            datasets: [{
                data: [45, 35, 20],
                backgroundColor: [
                    'rgba(20, 184, 166, 0.8)',
                    'rgba(6, 182, 212, 0.8)',
                    'rgba(14, 165, 233, 0.8)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
}

function loadTopPerformers(metric) {
    const container = document.getElementById('topPerformersContainer');
    // Sample data - replace with actual AJAX call
    const performers = [
        { name: 'นาย ก', value: '฿125,000', rank: 1 },
        { name: 'นาง ข', value: '฿98,500', rank: 2 },
        { name: 'นาย ค', value: '฿87,300', rank: 3 },
        { name: 'นาง ง', value: '฿76,200', rank: 4 },
        { name: 'นาย จ', value: '฿65,100', rank: 5 }
    ];

    container.innerHTML = performers.map(p => `
        <div class="bg-gradient-to-br ${p.rank === 1 ? 'from-yellow-400 to-orange-500' : p.rank === 2 ? 'from-gray-300 to-gray-400' : p.rank === 3 ? 'from-orange-300 to-orange-400' : 'from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800'} rounded-xl p-4 shadow-lg text-center transform hover:scale-105 transition-all duration-300">
            <div class="text-4xl mb-2"><i class="fas fa-${p.rank === 1 ? 'trophy' : p.rank === 2 ? 'medal' : p.rank === 3 ? 'award' : 'star'} ${p.rank <= 3 ? 'text-white' : 'text-yellow-500 dark:text-yellow-400'}"></i></div>
            <p class="font-bold ${p.rank <= 3 ? 'text-white' : 'text-gray-900 dark:text-white'}">${p.name}</p>
            <p class="text-2xl font-bold ${p.rank <= 3 ? 'text-white' : 'text-gray-900 dark:text-white'} mt-2">${p.value}</p>
            <p class="text-xs ${p.rank <= 3 ? 'text-white opacity-75' : 'text-gray-600 dark:text-gray-400'} mt-1">#${p.rank}</p>
        </div>
    `).join('');
}
</script>

<style>
@keyframes fade-in {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.animate-fade-in {
    animation: fade-in 0.5s ease-out;
}
</style>
@endsection
