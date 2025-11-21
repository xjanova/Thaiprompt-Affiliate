{{-- Charts & Calculators Section --}}

{{-- Section: Charts --}}
<section id="charts" class="section-padding bg-gradient-to-br from-purple-50 to-pink-50 dark:from-gray-900 dark:to-gray-800">
    <div class="container mx-auto px-4">
        <div class="text-center mb-16">
            <div class="inline-block px-6 py-2 gradient-primary text-white rounded-full text-sm font-bold mb-4 shadow-lg">
                📊 Data Visualization
            </div>
            <h2 class="text-5xl font-black text-gray-900 dark:text-white mb-4">
                Charts & Statistics
            </h2>
            <p class="text-xl text-gray-600 dark:text-gray-400 max-w-3xl mx-auto">
                ข้อมูลสถิติและกราฟที่สำคัญของ TPIX
            </p>
        </div>

        <div class="grid lg:grid-cols-2 gap-8 max-w-7xl mx-auto">

            {{-- Chart 1: Tokenomics Pie Chart --}}
            <div class="glass rounded-3xl p-8 feature-card">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 text-center flex items-center justify-center">
                    <i class="fas fa-chart-pie text-purple-600 dark:text-purple-400 mr-3"></i>
                    Token Distribution
                </h3>
                <div class="max-w-md mx-auto mb-6">
                    <canvas id="tokenomicsChart"></canvas>
                </div>
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div class="flex items-center">
                        <div class="w-4 h-4 rounded-full mr-2" style="background: #667eea;"></div>
                        <span class="text-gray-700 dark:text-gray-300">Ecosystem (30%)</span>
                    </div>
                    <div class="flex items-center">
                        <div class="w-4 h-4 rounded-full mr-2" style="background: #764ba2;"></div>
                        <span class="text-gray-700 dark:text-gray-300">Rewards (25%)</span>
                    </div>
                    <div class="flex items-center">
                        <div class="w-4 h-4 rounded-full mr-2" style="background: #f093fb;"></div>
                        <span class="text-gray-700 dark:text-gray-300">Staking (20%)</span>
                    </div>
                    <div class="flex items-center">
                        <div class="w-4 h-4 rounded-full mr-2" style="background: #4facfe;"></div>
                        <span class="text-gray-700 dark:text-gray-300">Team (15%)</span>
                    </div>
                    <div class="flex items-center">
                        <div class="w-4 h-4 rounded-full mr-2" style="background: #00f2fe;"></div>
                        <span class="text-gray-700 dark:text-gray-300">Marketing (10%)</span>
                    </div>
                </div>
            </div>

            {{-- Chart 2: TPS Comparison --}}
            <div class="glass rounded-3xl p-8 feature-card">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 text-center flex items-center justify-center">
                    <i class="fas fa-tachometer-alt text-blue-600 dark:text-blue-400 mr-3"></i>
                    TPS Comparison
                </h3>
                <canvas id="tpsChart"></canvas>
                <p class="text-xs text-gray-600 dark:text-gray-400 text-center mt-4">
                    * ใช้ Log Scale เพื่อให้เห็นความแตกต่างชัดเจน
                </p>
            </div>

            {{-- Chart 3: Staking APY --}}
            <div class="glass rounded-3xl p-8 feature-card">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 text-center flex items-center justify-center">
                    <i class="fas fa-chart-line text-green-600 dark:text-green-400 mr-3"></i>
                    Staking APY by Lock Period
                </h3>
                <canvas id="apyChart"></canvas>
                <div class="mt-6 p-4 bg-green-50 dark:bg-green-900/20 rounded-xl">
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        <strong class="text-green-600 dark:text-green-400">💡 Tips:</strong>
                        Lock ยิ่งนาน APY ยิ่งสูง สูงสุด 120% สำหรับ 365 วัน
                    </p>
                </div>
            </div>

            {{-- Chart 4: Token Vesting --}}
            <div class="glass rounded-3xl p-8 feature-card">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 text-center flex items-center justify-center">
                    <i class="fas fa-calendar-alt text-orange-600 dark:text-orange-400 mr-3"></i>
                    Token Release Schedule
                </h3>
                <canvas id="vestingChart"></canvas>
                <p class="text-xs text-gray-600 dark:text-gray-400 text-center mt-4">
                    * Team tokens มี vesting 4 ปี with 1 year cliff
                </p>
            </div>

        </div>
    </div>
</section>

{{-- Section: Calculators --}}
<section id="calculators" class="section-padding bg-white dark:bg-gray-900">
    <div class="container mx-auto px-4">
        <div class="text-center mb-16">
            <div class="inline-block px-6 py-2 gradient-green text-white rounded-full text-sm font-bold mb-4 shadow-lg">
                🧮 Calculators
            </div>
            <h2 class="text-5xl font-black text-gray-900 dark:text-white mb-4">
                Rewards Calculators
            </h2>
            <p class="text-xl text-gray-600 dark:text-gray-400 max-w-3xl mx-auto">
                คำนวณผลตอบแทนของคุณได้ทันที พร้อมสูตรคำนวณที่ชัดเจน
            </p>
        </div>

        <div class="space-y-12 max-w-6xl mx-auto">

            {{-- Calculator 1: Staking Rewards --}}
            <div class="glass rounded-3xl p-8 lg:p-12" x-data="stakingCalculator()">
                <div class="text-center mb-8">
                    <div class="inline-block px-4 py-2 bg-blue-100 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-full text-sm font-bold mb-4">
                        💰 Staking Calculator
                    </div>
                    <h3 class="text-3xl font-black text-gray-900 dark:text-white mb-4">
                        คำนวณผลตอบแทนจาก Staking
                    </h3>

                    {{-- Formula Display --}}
                    <div class="max-w-2xl mx-auto p-6 bg-blue-50 dark:bg-blue-900/20 rounded-2xl border-2 border-blue-200 dark:border-blue-800 mb-8">
                        <div class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">📐 สูตรคำนวณ:</div>
                        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 font-mono text-center">
                            <div class="text-xl text-gray-900 dark:text-white">
                                Reward = <span class="text-blue-600 dark:text-blue-400">(Amount × APY × Days)</span> / 365
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Inputs --}}
                <div class="grid md:grid-cols-2 gap-6 mb-8">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                            จำนวน TPIX ที่ต้องการ Stake
                        </label>
                        <input type="number" x-model.number="amount" @input="calculate()"
                               class="w-full px-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:border-blue-500 focus:outline-none"
                               placeholder="10000">
                        <div class="mt-2 flex flex-wrap gap-2">
                            <button @click="amount = 10000; calculate()" class="px-3 py-1 text-xs rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition">10K</button>
                            <button @click="amount = 50000; calculate()" class="px-3 py-1 text-xs rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition">50K</button>
                            <button @click="amount = 100000; calculate()" class="px-3 py-1 text-xs rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition">100K</button>
                            <button @click="amount = 1000000; calculate()" class="px-3 py-1 text-xs rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition">1M</button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                            ระยะเวลา Lock
                        </label>
                        <select x-model.number="lockPeriod" @change="updateAPY(); calculate()"
                                class="w-full px-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:border-blue-500 focus:outline-none">
                            <option value="30">30 วัน (APY: 30%)</option>
                            <option value="90">90 วัน (APY: 60%)</option>
                            <option value="180">180 วัน (APY: 90%)</option>
                            <option value="365" selected>365 วัน (APY: 120%)</option>
                        </select>
                        <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            APY ปัจจุบัน: <span class="font-bold text-blue-600 dark:text-blue-400" x-text="apy + '%'"></span>
                        </div>
                    </div>
                </div>

                {{-- Results --}}
                <div class="grid md:grid-cols-3 gap-6">
                    <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl p-6 text-white text-center">
                        <div class="text-sm opacity-80 mb-2">รายวัน</div>
                        <div class="text-3xl font-black mb-1" x-text="dailyReward.toLocaleString()"></div>
                        <div class="text-xs opacity-80">TPIX/วัน</div>
                    </div>
                    <div class="bg-gradient-to-br from-blue-500 to-cyan-600 rounded-2xl p-6 text-white text-center">
                        <div class="text-sm opacity-80 mb-2">ผลตอบแทนรวม</div>
                        <div class="text-3xl font-black mb-1" x-text="totalReward.toLocaleString()"></div>
                        <div class="text-xs opacity-80">TPIX</div>
                    </div>
                    <div class="bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl p-6 text-white text-center">
                        <div class="text-sm opacity-80 mb-2">ยอดรวมที่ได้</div>
                        <div class="text-3xl font-black mb-1" x-text="finalAmount.toLocaleString()"></div>
                        <div class="text-xs opacity-80">TPIX</div>
                    </div>
                </div>
            </div>

            {{-- Calculator 2: Commission Calculator --}}
            <div class="glass rounded-3xl p-8 lg:p-12" x-data="commissionCalculator()">
                <div class="text-center mb-8">
                    <div class="inline-block px-4 py-2 bg-green-100 dark:bg-green-900/20 text-green-600 dark:text-green-400 rounded-full text-sm font-bold mb-4">
                        💸 Commission Calculator
                    </div>
                    <h3 class="text-3xl font-black text-gray-900 dark:text-white mb-4">
                        คำนวณคอมมิชชั่น Affiliate (MLM 5 ระดับ)
                    </h3>

                    {{-- Formula Display --}}
                    <div class="max-w-2xl mx-auto p-6 bg-green-50 dark:bg-green-900/20 rounded-2xl border-2 border-green-200 dark:border-green-800 mb-8">
                        <div class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">📐 สูตร MLM:</div>
                        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 space-y-2 text-sm">
                            <div class="flex justify-between"><span>Level 1 (Direct):</span><span class="font-bold text-green-600 dark:text-green-400">5%</span></div>
                            <div class="flex justify-between"><span>Level 2:</span><span class="font-bold text-green-600 dark:text-green-400">3%</span></div>
                            <div class="flex justify-between"><span>Level 3:</span><span class="font-bold text-green-600 dark:text-green-400">2%</span></div>
                            <div class="flex justify-between"><span>Level 4:</span><span class="font-bold text-green-600 dark:text-green-400">1%</span></div>
                            <div class="flex justify-between"><span>Level 5:</span><span class="font-bold text-green-600 dark:text-green-400">0.5%</span></div>
                            <div class="border-t pt-2 flex justify-between font-bold"><span>Total:</span><span class="text-green-600 dark:text-green-400">11.5%</span></div>
                        </div>
                    </div>
                </div>

                {{-- Input --}}
                <div class="mb-8">
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2 text-center">
                        ยอดซื้อของ Downline (TPIX)
                    </label>
                    <input type="number" x-model.number="purchaseAmount" @input="calculate()"
                           class="w-full max-w-md mx-auto block px-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:border-green-500 focus:outline-none text-2xl font-bold text-center"
                           placeholder="1000">
                    <div class="mt-2 flex flex-wrap justify-center gap-2">
                        <button @click="purchaseAmount = 100; calculate()" class="px-4 py-2 text-sm rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition">100</button>
                        <button @click="purchaseAmount = 500; calculate()" class="px-4 py-2 text-sm rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition">500</button>
                        <button @click="purchaseAmount = 1000; calculate()" class="px-4 py-2 text-sm rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition font-bold">1,000</button>
                        <button @click="purchaseAmount = 5000; calculate()" class="px-4 py-2 text-sm rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition">5,000</button>
                    </div>
                </div>

                {{-- Results by Level --}}
                <div class="space-y-4 mb-8">
                    <template x-for="(level, index) in levels" :key="index">
                        <div class="flex items-center justify-between p-4 rounded-xl" :class="'bg-gradient-to-r ' + level.color">
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center text-white font-black text-xl">
                                    <span x-text="index + 1"></span>
                                </div>
                                <div class="text-white">
                                    <div class="font-bold" x-text="level.name"></div>
                                    <div class="text-sm opacity-80" x-text="level.rate + '%'"></div>
                                </div>
                            </div>
                            <div class="text-right text-white">
                                <div class="text-3xl font-black" x-text="level.commission.toLocaleString()"></div>
                                <div class="text-sm opacity-80">TPIX</div>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Total --}}
                <div class="p-8 bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl text-white text-center">
                    <div class="text-sm opacity-80 mb-2">💰 คอมมิชชั่นรวมทั้งหมด</div>
                    <div class="text-6xl font-black mb-2" x-text="totalCommission.toLocaleString()"></div>
                    <div class="text-xl opacity-90">TPIX</div>
                    <div class="mt-4 text-sm opacity-80">
                        (11.5% ของ <span x-text="purchaseAmount.toLocaleString()"></span> TPIX)
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

{{-- Chart.js Scripts --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Chart 1: Tokenomics Pie Chart
    const tokenomicsCtx = document.getElementById('tokenomicsChart');
    if (tokenomicsCtx) {
        new Chart(tokenomicsCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Ecosystem (30%)', 'Rewards (25%)', 'Staking (20%)', 'Team (15%)', 'Marketing (10%)'],
                datasets: [{
                    data: [30, 25, 20, 15, 10],
                    backgroundColor: ['#667eea', '#764ba2', '#f093fb', '#4facfe', '#00f2fe'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const value = context.parsed;
                                const total = 7000000000;
                                const amount = (total * value / 100).toLocaleString();
                                return context.label + ': ' + amount + ' TPIX';
                            }
                        }
                    }
                }
            }
        });
    }

    // Chart 2: TPS Comparison
    const tpsCtx = document.getElementById('tpsChart');
    if (tpsCtx) {
        new Chart(tpsCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: ['TPIX', 'Ethereum', 'Bitcoin', 'BSC', 'Visa'],
                datasets: [{
                    label: 'TPS',
                    data: [1500, 25, 7, 100, 24000],
                    backgroundColor: ['#667eea', '#627eea', '#f7931a', '#f0b90b', '#1434cb'],
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        type: 'logarithmic',
                        beginAtZero: true,
                        title: { display: true, text: 'TPS (Log Scale)' }
                    }
                },
                plugins: { legend: { display: false } }
            }
        });
    }

    // Chart 3: Staking APY
    const apyCtx = document.getElementById('apyChart');
    if (apyCtx) {
        new Chart(apyCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: ['30 Days', '90 Days', '180 Days', '365 Days'],
                datasets: [{
                    label: 'APY (%)',
                    data: [30, 60, 90, 120],
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 6,
                    pointHoverRadius: 8
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true, max: 140, title: { display: true, text: 'APY (%)' } }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'APY: ' + context.parsed.y + '%';
                            },
                            afterLabel: function(context) {
                                const amount = 10000;
                                const apy = context.parsed.y / 100;
                                const days = [30, 90, 180, 365][context.dataIndex];
                                const reward = Math.round(amount * apy * days / 365);
                                return 'Stake 10,000 TPIX: +' + reward.toLocaleString() + ' TPIX';
                            }
                        }
                    }
                }
            }
        });
    }

    // Chart 4: Token Vesting
    const vestingCtx = document.getElementById('vestingChart');
    if (vestingCtx) {
        new Chart(vestingCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: ['Launch', 'Year 1', 'Year 2', 'Year 3', 'Year 4', 'Year 5'],
                datasets: [
                    { label: 'Ecosystem', data: [630, 1050, 1470, 1890, 2100, 2100], borderColor: '#667eea', fill: false },
                    { label: 'Rewards', data: [525, 875, 1225, 1575, 1750, 1750], borderColor: '#764ba2', fill: false },
                    { label: 'Staking', data: [420, 700, 980, 1260, 1400, 1400], borderColor: '#f093fb', fill: false },
                    { label: 'Team', data: [0, 263, 525, 788, 1050, 1050], borderColor: '#4facfe', fill: false },
                    { label: 'Marketing', data: [210, 350, 490, 630, 700, 700], borderColor: '#00f2fe', fill: false }
                ]
            },
            options: {
                responsive: true,
                scales: { y: { beginAtZero: true, title: { display: true, text: 'Circulating Supply (Million TPIX)' } } },
                plugins: { tooltip: { mode: 'index', intersect: false } }
            }
        });
    }
});

// Alpine.js Calculator Functions
function stakingCalculator() {
    return {
        amount: 10000,
        lockPeriod: 365,
        apy: 120,
        dailyReward: 0,
        totalReward: 0,
        finalAmount: 0,
        updateAPY() {
            const apyMap = { 30: 30, 90: 60, 180: 90, 365: 120 };
            this.apy = apyMap[this.lockPeriod] || 120;
        },
        calculate() {
            this.totalReward = Math.round((this.amount * (this.apy / 100) * this.lockPeriod) / 365);
            this.dailyReward = Math.round(this.totalReward / this.lockPeriod);
            this.finalAmount = this.amount + this.totalReward;
        },
        init() { this.calculate(); }
    }
}

function commissionCalculator() {
    return {
        purchaseAmount: 1000,
        totalCommission: 0,
        levels: [
            { name: 'Level 1 (Direct)', rate: 5, commission: 0, color: 'from-green-500 to-green-600' },
            { name: 'Level 2', rate: 3, commission: 0, color: 'from-blue-500 to-blue-600' },
            { name: 'Level 3', rate: 2, commission: 0, color: 'from-purple-500 to-purple-600' },
            { name: 'Level 4', rate: 1, commission: 0, color: 'from-orange-500 to-orange-600' },
            { name: 'Level 5', rate: 0.5, commission: 0, color: 'from-pink-500 to-pink-600' }
        ],
        calculate() {
            this.totalCommission = 0;
            this.levels.forEach(level => {
                level.commission = Math.round(this.purchaseAmount * (level.rate / 100));
                this.totalCommission += level.commission;
            });
        },
        init() { this.calculate(); }
    }
}
</script>
