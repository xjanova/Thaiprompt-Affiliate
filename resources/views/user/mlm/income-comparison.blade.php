@extends('layouts.user-arrow-x')

@section('title', 'เปรียบเทียบแผนรายได้')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-indigo-50 via-purple-50 to-pink-50 py-8">
    <div class="container mx-auto px-4">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-5xl font-bold bg-gradient-to-r from-indigo-600 to-pink-600 bg-clip-text text-transparent mb-4">
                🔥 เปรียบเทียบแผนรายได้
            </h1>
            <p class="text-xl text-gray-700 dark:text-gray-300">เลือกแผนที่เหมาะกับคุณ - มองเห็นผลตอบแทนชัดเจน</p>
        </div>

        <!-- Scenario Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Starter Plan -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden transform transition-all duration-300 hover:scale-105 hover:shadow-3xl">
                <div class="bg-gradient-to-br from-green-400 to-teal-500 p-6 text-white">
                    <div class="text-4xl mb-3 text-center">🌱</div>
                    <h3 class="text-2xl font-bold text-center mb-2">แผนเริ่มต้น</h3>
                    <p class="text-center opacity-90">เหมาะกับมือใหม่</p>
                </div>

                <div class="p-6">
                    <div class="space-y-4 mb-6">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 dark:text-gray-400">ยอดขาย/เดือน:</span>
                            <span class="font-bold text-lg">฿5,000</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 dark:text-gray-400">จำนวนทีม:</span>
                            <span class="font-bold text-lg">5 คน</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 dark:text-gray-400">ยอดขายทีม:</span>
                            <span class="font-bold text-lg">฿15,000</span>
                        </div>
                    </div>

                    <div class="border-t pt-4 mb-4">
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">รายได้รวม/เดือน:</div>
                        <div class="text-3xl font-bold text-green-600">฿<span class="starter-income">0</span></div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">≈ <span class="starter-yearly">0</span> บาท/ปี</div>
                    </div>

                    <button onclick="simulateScenario('starter')"
                            class="w-full bg-green-500 hover:bg-green-600 text-white font-bold py-3 rounded-lg transition-all duration-300">
                        ดูรายละเอียด
                    </button>
                </div>
            </div>

            <!-- Growth Plan -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden transform transition-all duration-300 hover:scale-105 hover:shadow-3xl ring-4 ring-purple-500">
                <div class="bg-gradient-to-br from-purple-500 to-pink-600 p-6 text-white relative">
                    <div class="absolute top-2 right-2 bg-yellow-400 text-yellow-900 text-xs font-bold px-3 py-1 rounded-full">
                        แนะนำ!
                    </div>
                    <div class="text-4xl mb-3 text-center">🚀</div>
                    <h3 class="text-2xl font-bold text-center mb-2">แผนเติบโต</h3>
                    <p class="text-center opacity-90">สร้างรายได้มั่นคง</p>
                </div>

                <div class="p-6">
                    <div class="space-y-4 mb-6">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 dark:text-gray-400">ยอดขาย/เดือน:</span>
                            <span class="font-bold text-lg">฿20,000</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 dark:text-gray-400">จำนวนทีม:</span>
                            <span class="font-bold text-lg">20 คน</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 dark:text-gray-400">ยอดขายทีม:</span>
                            <span class="font-bold text-lg">฿100,000</span>
                        </div>
                    </div>

                    <div class="border-t pt-4 mb-4">
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">รายได้รวม/เดือน:</div>
                        <div class="text-3xl font-bold text-purple-600">฿<span class="growth-income">0</span></div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">≈ <span class="growth-yearly">0</span> บาท/ปี</div>
                    </div>

                    <button onclick="simulateScenario('growth')"
                            class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 rounded-lg transition-all duration-300">
                        ดูรายละเอียด
                    </button>
                </div>
            </div>

            <!-- Elite Plan -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden transform transition-all duration-300 hover:scale-105 hover:shadow-3xl">
                <div class="bg-gradient-to-br from-yellow-400 to-orange-500 p-6 text-white">
                    <div class="text-4xl mb-3 text-center">👑</div>
                    <h3 class="text-2xl font-bold text-center mb-2">แผนชนชั้นสูง</h3>
                    <p class="text-center opacity-90">สร้างอิสรภาพทางการเงิน</p>
                </div>

                <div class="p-6">
                    <div class="space-y-4 mb-6">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 dark:text-gray-400">ยอดขาย/เดือน:</span>
                            <span class="font-bold text-lg">฿50,000</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 dark:text-gray-400">จำนวนทีม:</span>
                            <span class="font-bold text-lg">50 คน</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 dark:text-gray-400">ยอดขายทีม:</span>
                            <span class="font-bold text-lg">฿250,000</span>
                        </div>
                    </div>

                    <div class="border-t pt-4 mb-4">
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">รายได้รวม/เดือน:</div>
                        <div class="text-3xl font-bold text-orange-600">฿<span class="elite-income">0</span></div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">≈ <span class="elite-yearly">0</span> บาท/ปี</div>
                    </div>

                    <button onclick="simulateScenario('elite')"
                            class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 rounded-lg transition-all duration-300">
                        ดูรายละเอียด
                    </button>
                </div>
            </div>
        </div>

        <!-- Comparison Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-8 mb-8">
            <h3 class="text-3xl font-bold text-gray-800 dark:text-white mb-6 text-center">📊 เปรียบเทียบรายได้</h3>
            <canvas id="comparison-chart" height="100"></canvas>
        </div>

        <!-- Detailed Breakdown -->
        <div id="detailed-breakdown" class="hidden">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-8 mb-8">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-3xl font-bold text-gray-800 dark:text-white">รายละเอียด: <span id="scenario-name" class="text-purple-600"></span></h3>
                    <button onclick="closeBreakdown()" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:text-gray-300">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <!-- Income Breakdown Pie -->
                    <div>
                        <h4 class="text-xl font-bold text-gray-700 dark:text-gray-300 mb-4">สัดส่วนรายได้</h4>
                        <canvas id="breakdown-pie" height="250"></canvas>
                    </div>

                    <!-- Growth Projection -->
                    <div>
                        <h4 class="text-xl font-bold text-gray-700 dark:text-gray-300 mb-4">การเติบโต 12 เดือน</h4>
                        <canvas id="growth-projection" height="250"></canvas>
                    </div>
                </div>

                <!-- Key Metrics -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-gradient-to-br from-blue-100 to-blue-200 rounded-xl p-4 text-center">
                        <div class="text-sm text-gray-700 dark:text-gray-300 mb-1">รายได้เดือนแรก</div>
                        <div class="text-2xl font-bold text-blue-600">฿<span id="month-1">0</span></div>
                    </div>

                    <div class="bg-gradient-to-br from-green-100 to-green-200 rounded-xl p-4 text-center">
                        <div class="text-sm text-gray-700 dark:text-gray-300 mb-1">เดือนที่ 6</div>
                        <div class="text-2xl font-bold text-green-600">฿<span id="month-6">0</span></div>
                    </div>

                    <div class="bg-gradient-to-br from-purple-100 to-purple-200 rounded-xl p-4 text-center">
                        <div class="text-sm text-gray-700 dark:text-gray-300 mb-1">เดือนที่ 12</div>
                        <div class="text-2xl font-bold text-purple-600">฿<span id="month-12">0</span></div>
                    </div>

                    <div class="bg-gradient-to-br from-pink-100 to-pink-200 rounded-xl p-4 text-center">
                        <div class="text-sm text-gray-700 dark:text-gray-300 mb-1">ROI (%)</div>
                        <div class="text-2xl font-bold text-pink-600"><span id="roi">0</span>%</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Success Stories -->
        <div class="bg-gradient-to-r from-purple-600 to-pink-600 rounded-2xl shadow-2xl p-8 text-white mb-8">
            <h3 class="text-3xl font-bold mb-6 text-center">🌟 เรื่องราวความสำเร็จ</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white dark:bg-gray-800 bg-opacity-20 backdrop-blur-lg rounded-xl p-6">
                    <div class="text-5xl mb-4 text-center">👨‍💼</div>
                    <h4 class="font-bold text-xl mb-2 text-center">คุณสมชาย</h4>
                    <p class="text-sm opacity-90 mb-3">เริ่มต้นด้วยแผนเติบโต ปัจจุบันมีรายได้</p>
                    <div class="text-3xl font-bold text-center">฿85,000/เดือน</div>
                </div>

                <div class="bg-white dark:bg-gray-800 bg-opacity-20 backdrop-blur-lg rounded-xl p-6">
                    <div class="text-5xl mb-4 text-center">👩‍💼</div>
                    <h4 class="font-bold text-xl mb-2 text-center">คุณสมหญิง</h4>
                    <p class="text-sm opacity-90 mb-3">ทำงานพาร์ทไทม์ สร้างรายได้เสริม</p>
                    <div class="text-3xl font-bold text-center">฿42,000/เดือน</div>
                </div>

                <div class="bg-white dark:bg-gray-800 bg-opacity-20 backdrop-blur-lg rounded-xl p-6">
                    <div class="text-5xl mb-4 text-center">👨‍🎓</div>
                    <h4 class="font-bold text-xl mb-2 text-center">คุณสมศักดิ์</h4>
                    <p class="text-sm opacity-90 mb-3">นักศึกษา สร้างรายได้ระหว่างเรียน</p>
                    <div class="text-3xl font-bold text-center">฿18,000/เดือน</div>
                </div>
            </div>
        </div>

        <!-- CTA -->
        <div class="text-center">
            <a href="{{ route('user.mlm.income-simulator') }}"
               class="inline-block bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white text-xl font-bold px-12 py-5 rounded-2xl shadow-2xl transition-all duration-300 transform hover:scale-105">
                🚀 ลองจำลองรายได้ของคุณเอง
            </a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let comparisonChart, breakdownPie, growthProjection;
let settings = {};

const scenarios = {
    starter: {
        name: 'แผนเริ่มต้น',
        personalSales: 5000,
        teamSize: 5,
        teamAvgSales: 3000,
        color: '#10b981'
    },
    growth: {
        name: 'แผนเติบโต',
        personalSales: 20000,
        teamSize: 20,
        teamAvgSales: 5000,
        color: '#8b5cf6'
    },
    elite: {
        name: 'แผนชนชั้นสูง',
        personalSales: 50000,
        teamSize: 50,
        teamAvgSales: 5000,
        color: '#f59e0b'
    }
};

async function loadSettings() {
    try {
        const response = await fetch('{{ route("admin.mlm.settings.get-settings") }}');
        const data = await response.json();
        settings = data.settings;
        calculateAllScenarios();
    } catch (error) {
        console.error('Failed to load settings:', error);
    }
}

function calculateScenario(scenario) {
    const personalSales = scenario.personalSales;
    const teamSales = scenario.teamSize * scenario.teamAvgSales;

    const pvRate = parseFloat(settings.global_pv_rate || 1);
    const teamPv = teamSales / pvRate;

    // Unilevel
    const unilevelLevels = settings.unilevel_levels || [];
    let unilevel = 0;

    if (Array.isArray(unilevelLevels)) {
        unilevelLevels.forEach(level => {
            const percentage = level.percentage || 0;
            unilevel += (teamPv * percentage) / 100;
        });
    }

    // Binary (simplified)
    const binaryPairCommission = parseFloat(settings.binary_pair_commission || 100);
    const weakLegPv = teamPv * 0.4;
    const pairs = Math.floor(weakLegPv);
    const binary = Math.min(pairs * binaryPairCommission, teamPv * 0.1);

    const personal = personalSales * 0.2; // 20% retail profit
    const total = personal + unilevel + binary;

    return {
        personal,
        unilevel,
        binary,
        total,
        yearly: total * 12
    };
}

function calculateAllScenarios() {
    Object.keys(scenarios).forEach(key => {
        const result = calculateScenario(scenarios[key]);

        document.querySelector(`.${key}-income`).textContent = Math.floor(result.total).toLocaleString();
        document.querySelector(`.${key}-yearly`).textContent = Math.floor(result.yearly).toLocaleString();

        scenarios[key].result = result;
    });

    createComparisonChart();
}

function createComparisonChart() {
    const ctx = document.getElementById('comparison-chart');

    if (comparisonChart) comparisonChart.destroy();

    comparisonChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['แผนเริ่มต้น', 'แผนเติบโต', 'แผนชนชั้นสูง'],
            datasets: [{
                label: 'รายได้/เดือน (บาท)',
                data: [
                    scenarios.starter.result.total,
                    scenarios.growth.result.total,
                    scenarios.elite.result.total
                ],
                backgroundColor: [
                    'rgba(16, 185, 129, 0.8)',
                    'rgba(139, 92, 246, 0.8)',
                    'rgba(245, 158, 11, 0.8)'
                ],
                borderColor: [
                    'rgb(16, 185, 129)',
                    'rgb(139, 92, 246)',
                    'rgb(245, 158, 11)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: value => '฿' + value.toLocaleString()
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
}

function simulateScenario(type) {
    const scenario = scenarios[type];
    const result = scenario.result;

    document.getElementById('scenario-name').textContent = scenario.name;
    document.getElementById('detailed-breakdown').classList.remove('hidden');

    // Breakdown Pie
    const ctxPie = document.getElementById('breakdown-pie');
    if (breakdownPie) breakdownPie.destroy();

    breakdownPie = new Chart(ctxPie, {
        type: 'doughnut',
        data: {
            labels: ['ยอดขายตัวเอง', 'Unilevel', 'Binary'],
            datasets: [{
                data: [result.personal, result.unilevel, result.binary],
                backgroundColor: [
                    'rgb(34, 197, 94)',
                    'rgb(59, 130, 246)',
                    'rgb(236, 72, 153)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Growth Projection
    const growthData = [];
    for (let i = 1; i <= 12; i++) {
        const growthFactor = 1 + (i * 0.05);
        growthData.push(result.total * growthFactor);
    }

    const ctxGrowth = document.getElementById('growth-projection');
    if (growthProjection) growthProjection.destroy();

    growthProjection = new Chart(ctxGrowth, {
        type: 'line',
        data: {
            labels: Array.from({length: 12}, (_, i) => `เดือน ${i + 1}`),
            datasets: [{
                label: 'รายได้',
                data: growthData,
                borderColor: scenario.color,
                backgroundColor: scenario.color + '33',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: value => '฿' + value.toLocaleString()
                    }
                }
            }
        }
    });

    // Key Metrics
    document.getElementById('month-1').textContent = Math.floor(growthData[0]).toLocaleString();
    document.getElementById('month-6').textContent = Math.floor(growthData[5]).toLocaleString();
    document.getElementById('month-12').textContent = Math.floor(growthData[11]).toLocaleString();
    document.getElementById('roi').textContent = Math.floor(((growthData[11] - growthData[0]) / growthData[0]) * 100);

    // Scroll to breakdown
    document.getElementById('detailed-breakdown').scrollIntoView({ behavior: 'smooth' });
}

function closeBreakdown() {
    document.getElementById('detailed-breakdown').classList.add('hidden');
}

loadSettings();
</script>
@endsection
