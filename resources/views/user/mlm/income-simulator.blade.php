@extends('layouts.user')

@section('title', 'จำลองรายได้ MLM')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-purple-50 via-pink-50 to-blue-50 py-8">
    <div class="container mx-auto px-4">
        <!-- Hero Section -->
        <div class="text-center mb-8">
            <h1 class="text-5xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent mb-4">
                💰 จำลองรายได้ MLM
            </h1>
            <p class="text-xl text-gray-700 mb-2">ดูภาพชัดเจนว่าคุณจะได้รับค่าตอบแทนเท่าไร</p>
            <p class="text-gray-600">เครื่องมือสำหรับนักการตลาด - นำเสนอได้เลย</p>
        </div>

        <!-- Control Panel -->
        <div class="bg-white rounded-2xl shadow-2xl p-6 mb-6">
            <!-- Rank Selection -->
            <div class="mb-6 p-4 bg-gradient-to-r from-yellow-50 to-orange-50 rounded-xl border-2 border-yellow-300">
                <label class="block text-sm font-bold text-gray-700 mb-3">
                    ⭐ ระดับยศของคุณ (Rank) - <span class="text-sm font-normal text-gray-500">ไม่บังคับ</span>
                </label>
                <select id="user_rank" onchange="updateRankInfo()"
                        class="w-full px-4 py-3 text-lg font-semibold border-2 border-yellow-400 rounded-xl focus:ring-4 focus:ring-yellow-200 focus:border-yellow-500 bg-white">
                    <option value="">กำลังโหลด...</option>
                </select>
                <div id="rank-info" class="mt-3 hidden">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                        <div class="bg-white p-3 rounded-lg shadow">
                            <div class="text-gray-600 mb-1">ค่าคอมมิชชั่นพื้นฐาน</div>
                            <div class="text-2xl font-bold text-blue-600"><span id="rank-commission-rate">0</span>%</div>
                        </div>
                        <div class="bg-white p-3 rounded-lg shadow">
                            <div class="text-gray-600 mb-1">ตัวคูณโบนัส</div>
                            <div class="text-2xl font-bold text-purple-600"><span id="rank-multiplier">1.0</span>x</div>
                        </div>
                        <div class="bg-white p-3 rounded-lg shadow">
                            <div class="text-gray-600 mb-1">ระดับ</div>
                            <div class="text-2xl font-bold text-orange-600"><span id="rank-level">1</span> / 5</div>
                        </div>
                    </div>
                </div>
                <div id="no-rank-info" class="mt-3 hidden">
                    <div class="bg-blue-50 p-3 rounded-lg border border-blue-200">
                        <div class="text-sm text-blue-700">
                            💡 <strong>ไม่ได้เลือกยศ:</strong> จะคำนวณ Unilevel และ Binary แบบพื้นฐาน (ไม่มีโบนัสจากยศ)
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <!-- Personal Sales -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">
                        💵 ยอดขายของคุณ (บาท/เดือน)
                    </label>
                    <input type="number" id="personal_sales" value="10000" step="1000" min="0"
                           class="w-full px-4 py-3 text-lg font-semibold border-2 border-purple-300 rounded-xl focus:ring-4 focus:ring-purple-200 focus:border-purple-500">
                </div>

                <!-- Team Size -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">
                        👥 จำนวนสมาชิกในทีม
                    </label>
                    <input type="number" id="team_size" value="10" step="1" min="0"
                           class="w-full px-4 py-3 text-lg font-semibold border-2 border-pink-300 rounded-xl focus:ring-4 focus:ring-pink-200 focus:border-pink-500">
                </div>

                <!-- Team Sales -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">
                        🛒 ยอดขายเฉลี่ยต่อคน (บาท)
                    </label>
                    <input type="number" id="team_avg_sales" value="5000" step="500" min="0"
                           class="w-full px-4 py-3 text-lg font-semibold border-2 border-blue-300 rounded-xl focus:ring-4 focus:ring-blue-200 focus:border-blue-500">
                </div>

                <!-- Months -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">
                        📅 ระยะเวลา (เดือน)
                    </label>
                    <input type="number" id="months" value="12" step="1" min="1" max="60"
                           class="w-full px-4 py-3 text-lg font-semibold border-2 border-green-300 rounded-xl focus:ring-4 focus:ring-green-200 focus:border-green-500">
                </div>
            </div>

            <div class="mt-6 flex gap-4">
                <button onclick="startSimulation()"
                        class="flex-1 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white text-lg font-bold py-4 rounded-xl shadow-lg transition-all duration-300 transform hover:scale-105">
                    ▶️ เริ่มจำลอง
                </button>
                <button onclick="resetSimulation()"
                        class="px-8 bg-gray-600 hover:bg-gray-700 text-white text-lg font-bold py-4 rounded-xl shadow-lg transition-all duration-300">
                    🔄 รีเซ็ต
                </button>
                <button onclick="printResults()"
                        class="px-8 bg-green-600 hover:bg-green-700 text-white text-lg font-bold py-4 rounded-xl shadow-lg transition-all duration-300">
                    🖨️ พิมพ์
                </button>
            </div>
        </div>

        <!-- Animation Area -->
        <div id="animation-container" class="hidden">
            <!-- Current Month Display -->
            <div class="bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-2xl shadow-2xl p-8 mb-6 text-center">
                <h2 class="text-2xl font-bold mb-2">กำลังจำลอง...</h2>
                <div class="text-6xl font-bold mb-2">เดือนที่ <span id="current-month">1</span></div>
                <div class="text-xl">จาก <span id="total-months">12</span> เดือน</div>
            </div>

            <!-- Income Flow Animation -->
            <div class="bg-white rounded-2xl shadow-2xl p-8 mb-6">
                <h3 class="text-2xl font-bold text-gray-800 mb-6 text-center">💸 การไหลของรายได้</h3>

                <div class="relative" style="height: 400px;">
                    <!-- You (Center) -->
                    <div class="absolute" style="left: 50%; top: 50%; transform: translate(-50%, -50%);">
                        <div class="text-center">
                            <div class="w-32 h-32 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full shadow-2xl flex items-center justify-center mb-3 animate-pulse">
                                <span class="text-5xl">👤</span>
                            </div>
                            <div class="font-bold text-lg">คุณ</div>
                            <div class="text-sm text-gray-600">ผู้ประกอบการ</div>
                        </div>
                    </div>

                    <!-- Income Streams (will be animated) -->
                    <svg class="absolute inset-0 w-full h-full pointer-events-none" style="z-index: 1;">
                        <defs>
                            <linearGradient id="gradient1" x1="0%" y1="0%" x2="100%" y2="0%">
                                <stop offset="0%" style="stop-color:#8B5CF6;stop-opacity:1" />
                                <stop offset="100%" style="stop-color:#EC4899;stop-opacity:1" />
                            </linearGradient>
                        </defs>
                        <g id="income-lines"></g>
                    </svg>

                    <!-- Team Members (will be positioned dynamically) -->
                    <div id="team-members-container"></div>

                    <!-- Income Particles -->
                    <div id="particles-container" class="absolute inset-0 pointer-events-none" style="z-index: 2;"></div>
                </div>
            </div>

            <!-- Real-time Calculation Display -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <!-- Personal Commission -->
                <div class="bg-gradient-to-br from-green-400 to-green-600 rounded-2xl shadow-2xl p-6 text-white transform transition-all duration-300 hover:scale-105">
                    <div class="text-sm font-semibold mb-2 opacity-90">📦 คอมมิชชั่นส่วนตัว</div>
                    <div class="text-4xl font-bold mb-2">฿<span id="personal-income">0</span></div>
                    <div class="text-sm opacity-75" id="personal-income-label">จาก PV ของคุณ (ต้องมียศ)</div>
                </div>

                <!-- Team Commission -->
                <div class="bg-gradient-to-br from-blue-400 to-blue-600 rounded-2xl shadow-2xl p-6 text-white transform transition-all duration-300 hover:scale-105">
                    <div class="text-sm font-semibold mb-2 opacity-90">👥 คอมมิชชั่นจากทีม</div>
                    <div class="text-4xl font-bold mb-2">฿<span id="team-income">0</span></div>
                    <div class="text-sm opacity-75" id="team-income-label">Unilevel + Binary</div>
                </div>

                <!-- Total Income -->
                <div class="bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl shadow-2xl p-6 text-white transform transition-all duration-300 hover:scale-105 animate-pulse">
                    <div class="text-sm font-semibold mb-2 opacity-90">💰 รวมคอมมิชชั่น</div>
                    <div class="text-4xl font-bold mb-2">฿<span id="total-income">0</span></div>
                    <div class="text-sm opacity-75">รวมทั้งหมดเดือนนี้</div>
                </div>
            </div>

            <!-- Progress Bar -->
            <div class="bg-white rounded-2xl shadow-2xl p-6 mb-6">
                <div class="flex justify-between text-sm font-semibold text-gray-700 mb-2">
                    <span>ความคืบหน้า</span>
                    <span id="progress-percent">0%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-6">
                    <div id="progress-bar" class="h-6 rounded-full bg-gradient-to-r from-purple-600 to-pink-600 transition-all duration-500" style="width: 0%"></div>
                </div>
            </div>
        </div>

        <!-- Results Summary -->
        <div id="results-container" class="hidden">
            <!-- Monthly Breakdown Table -->
            <div class="bg-white rounded-2xl shadow-2xl p-8 mb-6">
                <h3 class="text-3xl font-bold text-gray-800 mb-6 text-center">📊 สรุปรายได้แต่ละเดือน</h3>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gradient-to-r from-purple-600 to-pink-600 text-white">
                            <tr>
                                <th class="px-4 py-4 text-left font-bold">เดือน</th>
                                <th class="px-4 py-4 text-right font-bold">ยอดขายตัวเอง</th>
                                <th class="px-4 py-4 text-right font-bold">ยอดขายทีม</th>
                                <th class="px-4 py-4 text-right font-bold">Com.ส่วนตัว</th>
                                <th class="px-4 py-4 text-right font-bold">Unilevel</th>
                                <th class="px-4 py-4 text-right font-bold">Binary</th>
                                <th class="px-4 py-4 text-right font-bold">รวม</th>
                                <th class="px-4 py-4 text-right font-bold">สะสม</th>
                            </tr>
                        </thead>
                        <tbody id="monthly-breakdown" class="divide-y divide-gray-200">
                        </tbody>
                        <tfoot class="bg-gradient-to-r from-green-100 to-blue-100 font-bold text-lg">
                            <tr>
                                <td colspan="5" class="px-4 py-4 text-right">รวมทั้งหมด:</td>
                                <td id="grand-total" class="px-4 py-4 text-right text-purple-600"></td>
                                <td class="px-4 py-4"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Charts -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Income Growth Chart -->
                <div class="bg-white rounded-2xl shadow-2xl p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-4">📈 กราฟการเติบโตของรายได้</h3>
                    <div style="position: relative; height: 300px;">
                        <canvas id="income-chart"></canvas>
                    </div>
                </div>

                <!-- Income Breakdown Pie -->
                <div class="bg-white rounded-2xl shadow-2xl p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-4">🥧 สัดส่วนรายได้</h3>
                    <div style="position: relative; height: 300px;">
                        <canvas id="breakdown-chart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Key Insights -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="bg-gradient-to-br from-yellow-400 to-orange-500 rounded-2xl shadow-2xl p-6 text-white text-center">
                    <div class="text-5xl mb-3">💎</div>
                    <div class="text-3xl font-bold mb-2">฿<span id="avg-monthly">0</span></div>
                    <div class="text-sm font-semibold">รายได้เฉลี่ย/เดือน</div>
                </div>

                <div class="bg-gradient-to-br from-green-400 to-teal-500 rounded-2xl shadow-2xl p-6 text-white text-center">
                    <div class="text-5xl mb-3">🎯</div>
                    <div class="text-3xl font-bold mb-2">฿<span id="best-month">0</span></div>
                    <div class="text-sm font-semibold">เดือนที่ได้สูงสุด</div>
                </div>

                <div class="bg-gradient-to-br from-blue-400 to-indigo-500 rounded-2xl shadow-2xl p-6 text-white text-center">
                    <div class="text-5xl mb-3">⚡</div>
                    <div class="text-3xl font-bold mb-2"><span id="growth-rate">0</span>%</div>
                    <div class="text-sm font-semibold">อัตราการเติบโต</div>
                </div>

                <div class="bg-gradient-to-br from-purple-400 to-pink-500 rounded-2xl shadow-2xl p-6 text-white text-center">
                    <div class="text-5xl mb-3">🚀</div>
                    <div class="text-3xl font-bold mb-2">฿<span id="yearly-projection">0</span></div>
                    <div class="text-sm font-semibold">คาดการณ์รายได้/ปี</div>
                </div>
            </div>

            <!-- Call to Action -->
            <div class="bg-gradient-to-r from-purple-600 to-pink-600 rounded-2xl shadow-2xl p-8 text-white text-center">
                <h3 class="text-3xl font-bold mb-4">🎉 พร้อมเริ่มต้นหรือยัง?</h3>
                <p class="text-xl mb-6 opacity-90">นี่คือโอกาสของคุณที่จะสร้างรายได้แบบ Passive Income</p>
                <div class="flex gap-4 justify-center">
                    <button onclick="window.location.href='{{ route('user.mlm.dashboard') }}'"
                            class="bg-white text-purple-600 px-8 py-4 rounded-xl font-bold text-lg hover:bg-gray-100 transition-all duration-300 transform hover:scale-105">
                        เริ่มต้นเลย!
                    </button>
                    <button onclick="startSimulation()"
                            class="bg-purple-800 text-white px-8 py-4 rounded-xl font-bold text-lg hover:bg-purple-900 transition-all duration-300 transform hover:scale-105">
                        จำลองอีกครั้ง
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes float-up {
    0% {
        transform: translateY(0) scale(1);
        opacity: 1;
    }
    100% {
        transform: translateY(-100px) scale(0.5);
        opacity: 0;
    }
}

.income-particle {
    animation: float-up 2s ease-out forwards;
}

@keyframes pulse-glow {
    0%, 100% {
        box-shadow: 0 0 20px rgba(139, 92, 246, 0.5);
    }
    50% {
        box-shadow: 0 0 40px rgba(236, 72, 153, 0.8);
    }
}

@media print {
    .no-print {
        display: none !important;
    }

    body {
        background: white !important;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let simulationData = {
    months: [],
    settings: {},
    ranks: [],
    selectedRank: null,
    isRunning: false,
};

let incomeChart, breakdownChart;

async function loadSettings() {
    try {
        const response = await fetch('{{ route("admin.mlm.settings.get-settings") }}');
        const data = await response.json();
        simulationData.settings = data.settings;
    } catch (error) {
        console.error('Failed to load settings:', error);
    }
}

async function loadRanks() {
    try {
        const response = await fetch('/api/v1/ranks');
        const data = await response.json();
        simulationData.ranks = data.data || data;

        const select = document.getElementById('user_rank');
        select.innerHTML = '<option value="">ไม่เลือกยศ (คำนวณแบบพื้นฐาน)</option>';

        simulationData.ranks.forEach(rank => {
            const option = document.createElement('option');
            option.value = rank.id;
            option.textContent = `${rank.name_th || rank.name} (${rank.commission_rate}% + ${rank.bonus_multiplier}x)`;
            option.dataset.rank = JSON.stringify(rank);
            select.appendChild(option);
        });

        // Don't auto-select any rank - let user choose
        updateRankInfo();
    } catch (error) {
        console.error('Failed to load ranks:', error);
        document.getElementById('user_rank').innerHTML = '<option value="">ไม่สามารถโหลดข้อมูล Rank ได้ - จะคำนวณแบบพื้นฐาน</option>';
        updateRankInfo();
    }
}

function updateRankInfo() {
    const select = document.getElementById('user_rank');
    const selectedOption = select.options[select.selectedIndex];

    if (!selectedOption || !selectedOption.dataset.rank || !selectedOption.value) {
        // No rank selected
        document.getElementById('rank-info').classList.add('hidden');
        document.getElementById('no-rank-info').classList.remove('hidden');
        simulationData.selectedRank = null;

        // Update labels for no rank
        document.getElementById('personal-income-label').textContent = 'ไม่มี (ต้องมียศ)';
        document.getElementById('team-income-label').textContent = 'Unilevel + Binary (แบบพื้นฐาน)';
        return;
    }

    const rank = JSON.parse(selectedOption.dataset.rank);
    simulationData.selectedRank = rank;

    document.getElementById('rank-commission-rate').textContent = rank.commission_rate || 0;
    document.getElementById('rank-multiplier').textContent = (rank.bonus_multiplier || 1).toFixed(2);
    document.getElementById('rank-level').textContent = rank.level || 1;
    document.getElementById('rank-info').classList.remove('hidden');
    document.getElementById('no-rank-info').classList.add('hidden');

    // Update labels with rank
    document.getElementById('personal-income-label').textContent = `จาก PV × ${rank.commission_rate}%`;
    document.getElementById('team-income-label').textContent = `Unilevel + Binary × ${(rank.bonus_multiplier || 1).toFixed(2)}x`;
}

async function startSimulation() {
    if (simulationData.isRunning) return;

    const personalSales = parseFloat(document.getElementById('personal_sales').value);
    const teamSize = parseInt(document.getElementById('team_size').value);
    const teamAvgSales = parseFloat(document.getElementById('team_avg_sales').value);
    const months = parseInt(document.getElementById('months').value);

    simulationData.isRunning = true;
    simulationData.months = [];

    document.getElementById('animation-container').classList.remove('hidden');
    document.getElementById('results-container').classList.add('hidden');
    document.getElementById('total-months').textContent = months;

    // Simulate each month
    for (let month = 1; month <= months; month++) {
        document.getElementById('current-month').textContent = month;

        await simulateMonth(month, personalSales, teamSize, teamAvgSales);

        updateProgress(month, months);

        // Wait for animation
        await sleep(800);
    }

    simulationData.isRunning = false;
    showResults();
}

async function simulateMonth(month, personalSales, teamSize, teamAvgSales) {
    // Calculate growth factor (team grows over time)
    const growthFactor = 1 + (month * 0.03); // 3% growth per month (more realistic)
    const currentTeamSize = Math.floor(teamSize * growthFactor);
    const totalTeamSales = currentTeamSize * teamAvgSales;

    // Calculate PV (Personal Volume)
    const pvRate = parseFloat(simulationData.settings.global_pv_rate || 1);
    const personalPv = personalSales * pvRate;
    const teamPv = totalTeamSales * pvRate;

    // Get rank multiplier and commission rate
    // If no rank selected, multiplier = 1 (no bonus), commission rate = 0 (no personal commission)
    const rankMultiplier = simulationData.selectedRank ? parseFloat(simulationData.selectedRank.bonus_multiplier || 1) : 1;
    const rankCommissionRate = simulationData.selectedRank ? parseFloat(simulationData.selectedRank.commission_rate || 0) : 0;

    // Unilevel calculation
    const unilevelLevels = simulationData.settings.unilevel_levels || [];
    let unilevelCommission = 0;

    if (Array.isArray(unilevelLevels)) {
        unilevelLevels.forEach(level => {
            const percentage = level.percentage || 0;
            const levelCommission = (teamPv * percentage) / 100;
            unilevelCommission += levelCommission;
        });
    }

    // Apply rank multiplier to unilevel (if no rank, multiplier = 1 so no bonus)
    unilevelCommission *= rankMultiplier;

    // Binary calculation (more realistic)
    let binaryCommission = 0;
    if (simulationData.settings.binary_enabled !== false) {
        // Simulate left and right leg distribution (60/40 split)
        const leftLegPv = teamPv * 0.6;
        const rightLegPv = teamPv * 0.4;
        const weakLegPv = Math.min(leftLegPv, rightLegPv);

        // Calculate pairs
        const pairRatio = parseFloat(simulationData.settings.binary_pair_ratio || 1);
        const pairsAvailable = Math.floor(weakLegPv / pairRatio);

        // Daily pair limit
        const dailyPairLimit = parseInt(simulationData.settings.binary_daily_pair_limit || 100);
        const pairsToPay = Math.min(pairsAvailable, dailyPairLimit);

        // Commission per pair
        const binaryPairCommission = parseFloat(simulationData.settings.binary_pair_commission || 0);
        const binaryMatchPercentage = parseFloat(simulationData.settings.binary_match_percentage || 0);

        if (binaryPairCommission > 0) {
            binaryCommission = pairsToPay * binaryPairCommission;
        } else if (binaryMatchPercentage > 0) {
            binaryCommission = (weakLegPv * binaryMatchPercentage) / 100;
        }

        // Apply rank multiplier to binary (if no rank, multiplier = 1 so no bonus)
        binaryCommission *= rankMultiplier;
    }

    // Personal PV commission (based on personal volume only)
    // If no rank selected, this will be 0 because rankCommissionRate = 0
    const personalCommission = (personalPv * rankCommissionRate) / 100;

    // Total commission income
    const totalIncome = personalCommission + unilevelCommission + binaryCommission;

    // Animate income display
    animateNumber('personal-income', personalCommission);
    animateNumber('team-income', unilevelCommission + binaryCommission);
    animateNumber('total-income', totalIncome);

    // Create floating particles
    createIncomeParticles(totalIncome);

    // Store month data
    const accumulated = simulationData.months.reduce((sum, m) => sum + m.total, 0) + totalIncome;

    simulationData.months.push({
        month,
        personalSales,
        teamSales: totalTeamSales,
        teamSize: currentTeamSize,
        personalPv,
        teamPv,
        unilevel: unilevelCommission,
        binary: binaryCommission,
        personal: personalCommission,
        rankMultiplier,
        total: totalIncome,
        accumulated
    });
}

function createIncomeParticles(amount) {
    const container = document.getElementById('particles-container');
    const numParticles = Math.min(Math.floor(amount / 1000), 20); // Max 20 particles

    for (let i = 0; i < numParticles; i++) {
        setTimeout(() => {
            const particle = document.createElement('div');
            particle.className = 'income-particle absolute';
            particle.style.left = `${50 + (Math.random() - 0.5) * 30}%`;
            particle.style.top = `${50}%`;
            particle.innerHTML = '💰';
            particle.style.fontSize = '24px';
            particle.style.zIndex = '10';

            container.appendChild(particle);

            setTimeout(() => particle.remove(), 2000);
        }, i * 100);
    }
}

function animateNumber(elementId, value) {
    const element = document.getElementById(elementId);
    const start = parseFloat(element.textContent.replace(/,/g, '')) || 0;
    const end = value;
    const duration = 500;
    const startTime = performance.now();

    function update(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);

        const current = start + (end - start) * easeOutCubic(progress);
        element.textContent = Math.floor(current).toLocaleString();

        if (progress < 1) {
            requestAnimationFrame(update);
        }
    }

    requestAnimationFrame(update);
}

function easeOutCubic(t) {
    return 1 - Math.pow(1 - t, 3);
}

function updateProgress(current, total) {
    const percent = (current / total) * 100;
    document.getElementById('progress-bar').style.width = `${percent}%`;
    document.getElementById('progress-percent').textContent = `${Math.floor(percent)}%`;
}

function showResults() {
    document.getElementById('animation-container').classList.add('hidden');
    document.getElementById('results-container').classList.remove('hidden');

    // Build monthly breakdown table
    const tbody = document.getElementById('monthly-breakdown');
    tbody.innerHTML = simulationData.months.map(month => `
        <tr class="hover:bg-purple-50 transition-colors">
            <td class="px-4 py-3 font-semibold">เดือน ${month.month}</td>
            <td class="px-4 py-3 text-right">฿${Math.floor(month.personalSales).toLocaleString()}</td>
            <td class="px-4 py-3 text-right">฿${Math.floor(month.teamSales).toLocaleString()}</td>
            <td class="px-4 py-3 text-right text-green-600 font-semibold">฿${Math.floor(month.personal).toLocaleString()}</td>
            <td class="px-4 py-3 text-right text-blue-600 font-semibold">฿${Math.floor(month.unilevel).toLocaleString()}</td>
            <td class="px-4 py-3 text-right text-indigo-600 font-semibold">฿${Math.floor(month.binary).toLocaleString()}</td>
            <td class="px-4 py-3 text-right text-purple-600 font-bold text-lg">฿${Math.floor(month.total).toLocaleString()}</td>
            <td class="px-4 py-3 text-right text-gray-600">฿${Math.floor(month.accumulated).toLocaleString()}</td>
        </tr>
    `).join('');

    // Calculate totals
    const grandTotal = simulationData.months.reduce((sum, m) => sum + m.total, 0);
    const avgMonthly = grandTotal / simulationData.months.length;
    const bestMonth = Math.max(...simulationData.months.map(m => m.total));
    const growthRate = simulationData.months.length > 1
        ? ((simulationData.months[simulationData.months.length - 1].total - simulationData.months[0].total) / simulationData.months[0].total) * 100
        : 0;
    const yearlyProjection = avgMonthly * 12;

    document.getElementById('grand-total').textContent = `฿${Math.floor(grandTotal).toLocaleString()}`;
    document.getElementById('avg-monthly').textContent = Math.floor(avgMonthly).toLocaleString();
    document.getElementById('best-month').textContent = Math.floor(bestMonth).toLocaleString();
    document.getElementById('growth-rate').textContent = Math.floor(growthRate);
    document.getElementById('yearly-projection').textContent = Math.floor(yearlyProjection).toLocaleString();

    // Create charts
    createCharts();
}

function createCharts() {
    // Income Growth Chart
    const ctx1 = document.getElementById('income-chart');
    if (incomeChart) incomeChart.destroy();

    incomeChart = new Chart(ctx1, {
        type: 'line',
        data: {
            labels: simulationData.months.map(m => `เดือน ${m.month}`),
            datasets: [{
                label: 'รายได้รวม',
                data: simulationData.months.map(m => m.total),
                borderColor: 'rgb(147, 51, 234)',
                backgroundColor: 'rgba(147, 51, 234, 0.1)',
                tension: 0.4,
                fill: true,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
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

    // Breakdown Pie Chart
    const ctx2 = document.getElementById('breakdown-chart');
    if (breakdownChart) breakdownChart.destroy();

    const totalPersonal = simulationData.months.reduce((sum, m) => sum + m.personal, 0);
    const totalUnilevel = simulationData.months.reduce((sum, m) => sum + m.unilevel, 0);
    const totalBinary = simulationData.months.reduce((sum, m) => sum + m.binary, 0);

    const hasRank = simulationData.selectedRank !== null;
    const labels = hasRank
        ? ['Com.ส่วนตัว (จากยศ)', 'Unilevel (รวม Rank Bonus)', 'Binary (รวม Rank Bonus)']
        : ['Com.ส่วนตัว (ไม่มี)', 'Unilevel (แบบพื้นฐาน)', 'Binary (แบบพื้นฐาน)'];

    breakdownChart = new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: [totalPersonal, totalUnilevel, totalBinary],
                backgroundColor: [
                    'rgb(34, 197, 94)',
                    'rgb(59, 130, 246)',
                    'rgb(99, 102, 241)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += '฿' + Math.floor(context.parsed).toLocaleString();
                            return label;
                        }
                    }
                }
            }
        }
    });
}

function resetSimulation() {
    simulationData.months = [];
    simulationData.isRunning = false;

    document.getElementById('animation-container').classList.add('hidden');
    document.getElementById('results-container').classList.add('hidden');

    document.getElementById('personal-income').textContent = '0';
    document.getElementById('team-income').textContent = '0';
    document.getElementById('total-income').textContent = '0';
    document.getElementById('progress-bar').style.width = '0%';
}

function printResults() {
    window.print();
}

function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

// Load settings and ranks on page load
async function initializeSimulator() {
    await loadSettings();
    await loadRanks();
}

initializeSimulator();
</script>
@endsection
