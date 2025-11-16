@extends('layouts.user-arrow-x')

@section('title', 'จำลองการปันผล')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 py-8">
    <div class="container mx-auto px-4">
        <!-- Hero Section -->
        <div class="text-center mb-8">
            <h1 class="text-5xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent mb-4">
                💎 จำลองการปันผล
            </h1>
            <p class="text-xl text-gray-700 mb-2">คำนวณผลตอบแทนจากการปันผลบริษัท</p>
            <p class="text-gray-600">เครื่องมือสำหรับนักธุรกิจและนักการตลาด</p>
        </div>

        <!-- Control Panel -->
        <div class="bg-white rounded-2xl shadow-2xl p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <!-- Initial Investment -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">
                        💵 เงินลงทุนเริ่มต้น (บาท)
                    </label>
                    <input type="number" id="initial_investment" value="100000" step="10000" min="0"
                           class="w-full px-4 py-3 text-lg font-semibold border-2 border-blue-300 rounded-xl focus:ring-4 focus:ring-blue-200 focus:border-blue-500">
                </div>

                <!-- Dividend Rate -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">
                        📈 อัตราปันผล (%/ปี)
                    </label>
                    <input type="number" id="dividend_rate" value="5" step="0.5" min="0" max="100"
                           class="w-full px-4 py-3 text-lg font-semibold border-2 border-purple-300 rounded-xl focus:ring-4 focus:ring-purple-200 focus:border-purple-500">
                </div>

                <!-- Years -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">
                        📅 ระยะเวลา (ปี)
                    </label>
                    <input type="number" id="years" value="10" step="1" min="1" max="50"
                           class="w-full px-4 py-3 text-lg font-semibold border-2 border-green-300 rounded-xl focus:ring-4 focus:ring-green-200 focus:border-green-500">
                </div>

                <!-- Reinvestment -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">
                        🔄 นำปันผลกลับไปลงทุน
                    </label>
                    <select id="reinvestment" class="w-full px-4 py-3 text-lg font-semibold border-2 border-indigo-300 rounded-xl focus:ring-4 focus:ring-indigo-200 focus:border-indigo-500">
                        <option value="0">ไม่นำกลับไปลงทุน</option>
                        <option value="25">นำกลับไปลงทุน 25%</option>
                        <option value="50">นำกลับไปลงทุน 50%</option>
                        <option value="75">นำกลับไปลงทุน 75%</option>
                        <option value="100" selected>นำกลับไปลงทุนทั้งหมด</option>
                    </select>
                </div>
            </div>

            <div class="mt-6 flex gap-4">
                <button onclick="startSimulation()"
                        class="flex-1 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white text-lg font-bold py-4 rounded-xl shadow-lg transition-all duration-300 transform hover:scale-105">
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
            <!-- Current Year Display -->
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-2xl shadow-2xl p-8 mb-6 text-center">
                <h2 class="text-2xl font-bold mb-2">กำลังจำลอง...</h2>
                <div class="text-6xl font-bold mb-2">ปีที่ <span id="current-year">1</span></div>
                <div class="text-xl">จาก <span id="total-years">10</span> ปี</div>
            </div>

            <!-- Investment Flow Animation -->
            <div class="bg-white rounded-2xl shadow-2xl p-8 mb-6">
                <h3 class="text-2xl font-bold text-gray-800 mb-6 text-center">💰 การไหลของเงินปันผล</h3>

                <div class="relative" style="height: 400px;">
                    <!-- Investment (Center) -->
                    <div class="absolute" style="left: 50%; top: 50%; transform: translate(-50%, -50%);">
                        <div class="text-center">
                            <div class="w-32 h-32 bg-gradient-to-br from-blue-500 to-purple-500 rounded-full shadow-2xl flex items-center justify-center mb-3 animate-pulse">
                                <span class="text-5xl">💎</span>
                            </div>
                            <div class="font-bold text-lg">เงินลงทุน</div>
                            <div class="text-sm text-gray-600">฿<span id="current-principal">0</span></div>
                        </div>
                    </div>

                    <!-- Dividend Particles -->
                    <div id="dividend-particles-container" class="absolute inset-0 pointer-events-none" style="z-index: 2;"></div>
                </div>
            </div>

            <!-- Real-time Calculation Display -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <!-- Annual Dividend -->
                <div class="bg-gradient-to-br from-green-400 to-green-600 rounded-2xl shadow-2xl p-6 text-white transform transition-all duration-300 hover:scale-105">
                    <div class="text-sm font-semibold mb-2 opacity-90">📦 ปันผลปีนี้</div>
                    <div class="text-4xl font-bold mb-2">฿<span id="annual-dividend">0</span></div>
                    <div class="text-sm opacity-75">รับจากการปันผล</div>
                </div>

                <!-- Reinvested Amount -->
                <div class="bg-gradient-to-br from-blue-400 to-blue-600 rounded-2xl shadow-2xl p-6 text-white transform transition-all duration-300 hover:scale-105">
                    <div class="text-sm font-semibold mb-2 opacity-90">🔄 นำกลับไปลงทุน</div>
                    <div class="text-4xl font-bold mb-2">฿<span id="reinvested-amount">0</span></div>
                    <div class="text-sm opacity-75">เพิ่มเงินต้น</div>
                </div>

                <!-- Cash Out -->
                <div class="bg-gradient-to-br from-yellow-400 to-orange-500 rounded-2xl shadow-2xl p-6 text-white transform transition-all duration-300 hover:scale-105">
                    <div class="text-sm font-semibold mb-2 opacity-90">💸 รับเข้ากระเป๋า</div>
                    <div class="text-4xl font-bold mb-2">฿<span id="cash-out">0</span></div>
                    <div class="text-sm opacity-75">เงินสดที่ได้รับ</div>
                </div>

                <!-- Total Principal -->
                <div class="bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl shadow-2xl p-6 text-white transform transition-all duration-300 hover:scale-105 animate-pulse">
                    <div class="text-sm font-semibold mb-2 opacity-90">💰 เงินต้นรวม</div>
                    <div class="text-4xl font-bold mb-2">฿<span id="total-principal">0</span></div>
                    <div class="text-sm opacity-75">ณ สิ้นปี</div>
                </div>
            </div>

            <!-- Progress Bar -->
            <div class="bg-white rounded-2xl shadow-2xl p-6 mb-6">
                <div class="flex justify-between text-sm font-semibold text-gray-700 mb-2">
                    <span>ความคืบหน้า</span>
                    <span id="progress-percent">0%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-6">
                    <div id="progress-bar" class="h-6 rounded-full bg-gradient-to-r from-blue-600 to-purple-600 transition-all duration-500" style="width: 0%"></div>
                </div>
            </div>
        </div>

        <!-- Results Summary -->
        <div id="results-container" class="hidden">
            <!-- Yearly Breakdown Table -->
            <div class="bg-white rounded-2xl shadow-2xl p-8 mb-6">
                <h3 class="text-3xl font-bold text-gray-800 mb-6 text-center">📊 สรุปการปันผลแต่ละปี</h3>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gradient-to-r from-blue-600 to-purple-600 text-white">
                            <tr>
                                <th class="px-4 py-4 text-left font-bold">ปีที่</th>
                                <th class="px-4 py-4 text-right font-bold">เงินต้นต้นปี</th>
                                <th class="px-4 py-4 text-right font-bold">ปันผลที่ได้รับ</th>
                                <th class="px-4 py-4 text-right font-bold">นำกลับไปลงทุน</th>
                                <th class="px-4 py-4 text-right font-bold">รับเข้ากระเป๋า</th>
                                <th class="px-4 py-4 text-right font-bold">เงินต้นสิ้นปี</th>
                                <th class="px-4 py-4 text-right font-bold">สะสมเงินสด</th>
                            </tr>
                        </thead>
                        <tbody id="yearly-breakdown" class="divide-y divide-gray-200">
                        </tbody>
                        <tfoot class="bg-gradient-to-r from-green-100 to-blue-100 font-bold text-lg">
                            <tr>
                                <td colspan="4" class="px-4 py-4 text-right">รวมทั้งหมด:</td>
                                <td id="total-cash-received" class="px-4 py-4 text-right text-green-600"></td>
                                <td id="final-principal" class="px-4 py-4 text-right text-purple-600"></td>
                                <td class="px-4 py-4"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Charts -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Principal Growth Chart -->
                <div class="bg-white rounded-2xl shadow-2xl p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-4">📈 กราฟการเติบโตของเงินต้น</h3>
                    <div style="position: relative; height: 300px;">
                        <canvas id="principal-chart"></canvas>
                    </div>
                </div>

                <!-- Dividend Breakdown -->
                <div class="bg-white rounded-2xl shadow-2xl p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-4">🥧 สัดส่วนผลตอบแทน</h3>
                    <div style="position: relative; height: 300px;">
                        <canvas id="dividend-breakdown-chart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Key Insights -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="bg-gradient-to-br from-yellow-400 to-orange-500 rounded-2xl shadow-2xl p-6 text-white text-center">
                    <div class="text-5xl mb-3">💰</div>
                    <div class="text-3xl font-bold mb-2">฿<span id="total-dividends">0</span></div>
                    <div class="text-sm font-semibold">รวมปันผลทั้งหมด</div>
                </div>

                <div class="bg-gradient-to-br from-green-400 to-teal-500 rounded-2xl shadow-2xl p-6 text-white text-center">
                    <div class="text-5xl mb-3">🎯</div>
                    <div class="text-3xl font-bold mb-2">฿<span id="total-cash-out">0</span></div>
                    <div class="text-sm font-semibold">เงินสดรวมที่ได้รับ</div>
                </div>

                <div class="bg-gradient-to-br from-blue-400 to-indigo-500 rounded-2xl shadow-2xl p-6 text-white text-center">
                    <div class="text-5xl mb-3">⚡</div>
                    <div class="text-3xl font-bold mb-2"><span id="roi">0</span>%</div>
                    <div class="text-sm font-semibold">ผลตอบแทนรวม (ROI)</div>
                </div>

                <div class="bg-gradient-to-br from-purple-400 to-pink-500 rounded-2xl shadow-2xl p-6 text-white text-center">
                    <div class="text-5xl mb-3">🚀</div>
                    <div class="text-3xl font-bold mb-2">฿<span id="avg-yearly-dividend">0</span></div>
                    <div class="text-sm font-semibold">ปันผลเฉลี่ย/ปี</div>
                </div>
            </div>

            <!-- Call to Action -->
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-2xl shadow-2xl p-8 text-white text-center">
                <h3 class="text-3xl font-bold mb-4">🎉 เริ่มลงทุนวันนี้!</h3>
                <p class="text-xl mb-6 opacity-90">สร้างรายได้แบบ Passive Income ด้วยการปันผล</p>
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

.dividend-particle {
    animation: float-up 2s ease-out forwards;
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
    years: [],
    isRunning: false,
};

let principalChart, dividendBreakdownChart;

async function startSimulation() {
    if (simulationData.isRunning) return;

    const initialInvestment = parseFloat(document.getElementById('initial_investment').value);
    const dividendRate = parseFloat(document.getElementById('dividend_rate').value) / 100;
    const years = parseInt(document.getElementById('years').value);
    const reinvestmentRate = parseFloat(document.getElementById('reinvestment').value) / 100;

    simulationData.isRunning = true;
    simulationData.years = [];

    document.getElementById('animation-container').classList.remove('hidden');
    document.getElementById('results-container').classList.add('hidden');
    document.getElementById('total-years').textContent = years;

    let currentPrincipal = initialInvestment;

    // Simulate each year
    for (let year = 1; year <= years; year++) {
        document.getElementById('current-year').textContent = year;

        const startPrincipal = currentPrincipal;
        const annualDividend = currentPrincipal * dividendRate;
        const reinvestedAmount = annualDividend * reinvestmentRate;
        const cashOut = annualDividend - reinvestedAmount;
        currentPrincipal += reinvestedAmount;

        // Animate displays
        animateNumber('current-principal', currentPrincipal);
        animateNumber('annual-dividend', annualDividend);
        animateNumber('reinvested-amount', reinvestedAmount);
        animateNumber('cash-out', cashOut);
        animateNumber('total-principal', currentPrincipal);

        // Create floating particles
        createDividendParticles(annualDividend);

        updateProgress(year, years);

        // Store year data
        const totalCashOut = simulationData.years.reduce((sum, y) => sum + y.cashOut, 0) + cashOut;

        simulationData.years.push({
            year,
            startPrincipal,
            annualDividend,
            reinvestedAmount,
            cashOut,
            endPrincipal: currentPrincipal,
            totalCashOut
        });

        // Wait for animation
        await sleep(800);
    }

    simulationData.isRunning = false;
    showResults();
}

function createDividendParticles(amount) {
    const container = document.getElementById('dividend-particles-container');
    const numParticles = Math.min(Math.floor(amount / 5000), 20); // Max 20 particles

    for (let i = 0; i < numParticles; i++) {
        setTimeout(() => {
            const particle = document.createElement('div');
            particle.className = 'dividend-particle absolute';
            particle.style.left = `${50 + (Math.random() - 0.5) * 30}%`;
            particle.style.top = `${50}%`;
            particle.innerHTML = '💎';
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

    // Build yearly breakdown table
    const tbody = document.getElementById('yearly-breakdown');
    tbody.innerHTML = simulationData.years.map(year => `
        <tr class="hover:bg-purple-50 transition-colors">
            <td class="px-4 py-3 font-semibold">ปี ${year.year}</td>
            <td class="px-4 py-3 text-right">฿${Math.floor(year.startPrincipal).toLocaleString()}</td>
            <td class="px-4 py-3 text-right text-blue-600 font-semibold">฿${Math.floor(year.annualDividend).toLocaleString()}</td>
            <td class="px-4 py-3 text-right text-purple-600">฿${Math.floor(year.reinvestedAmount).toLocaleString()}</td>
            <td class="px-4 py-3 text-right text-green-600 font-semibold">฿${Math.floor(year.cashOut).toLocaleString()}</td>
            <td class="px-4 py-3 text-right text-purple-600 font-bold text-lg">฿${Math.floor(year.endPrincipal).toLocaleString()}</td>
            <td class="px-4 py-3 text-right text-gray-600">฿${Math.floor(year.totalCashOut).toLocaleString()}</td>
        </tr>
    `).join('');

    // Calculate totals
    const totalDividends = simulationData.years.reduce((sum, y) => sum + y.annualDividend, 0);
    const totalCashOut = simulationData.years.reduce((sum, y) => sum + y.cashOut, 0);
    const finalPrincipal = simulationData.years[simulationData.years.length - 1].endPrincipal;
    const initialInvestment = simulationData.years[0].startPrincipal;
    const roi = ((totalCashOut + finalPrincipal - initialInvestment) / initialInvestment) * 100;
    const avgYearlyDividend = totalDividends / simulationData.years.length;

    document.getElementById('total-cash-received').textContent = `฿${Math.floor(totalCashOut).toLocaleString()}`;
    document.getElementById('final-principal').textContent = `฿${Math.floor(finalPrincipal).toLocaleString()}`;
    document.getElementById('total-dividends').textContent = Math.floor(totalDividends).toLocaleString();
    document.getElementById('total-cash-out').textContent = Math.floor(totalCashOut).toLocaleString();
    document.getElementById('roi').textContent = Math.floor(roi);
    document.getElementById('avg-yearly-dividend').textContent = Math.floor(avgYearlyDividend).toLocaleString();

    // Create charts
    createCharts();
}

function createCharts() {
    // Principal Growth Chart
    const ctx1 = document.getElementById('principal-chart');
    if (principalChart) principalChart.destroy();

    principalChart = new Chart(ctx1, {
        type: 'line',
        data: {
            labels: simulationData.years.map(y => `ปี ${y.year}`),
            datasets: [
                {
                    label: 'เงินต้น',
                    data: simulationData.years.map(y => y.endPrincipal),
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true,
                },
                {
                    label: 'เงินสดสะสม',
                    data: simulationData.years.map(y => y.totalCashOut),
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    tension: 0.4,
                    fill: true,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom'
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

    // Dividend Breakdown Chart
    const ctx2 = document.getElementById('dividend-breakdown-chart');
    if (dividendBreakdownChart) dividendBreakdownChart.destroy();

    const totalReinvested = simulationData.years.reduce((sum, y) => sum + y.reinvestedAmount, 0);
    const totalCashOut = simulationData.years.reduce((sum, y) => sum + y.cashOut, 0);

    dividendBreakdownChart = new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: ['นำกลับไปลงทุน', 'รับเข้ากระเป๋า'],
            datasets: [{
                data: [totalReinvested, totalCashOut],
                backgroundColor: [
                    'rgb(147, 51, 234)',
                    'rgb(34, 197, 94)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

function resetSimulation() {
    simulationData.years = [];
    simulationData.isRunning = false;

    document.getElementById('animation-container').classList.add('hidden');
    document.getElementById('results-container').classList.add('hidden');

    document.getElementById('current-principal').textContent = '0';
    document.getElementById('annual-dividend').textContent = '0';
    document.getElementById('reinvested-amount').textContent = '0';
    document.getElementById('cash-out').textContent = '0';
    document.getElementById('total-principal').textContent = '0';
    document.getElementById('progress-bar').style.width = '0%';
}

function printResults() {
    window.print();
}

function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}
</script>
@endsection
