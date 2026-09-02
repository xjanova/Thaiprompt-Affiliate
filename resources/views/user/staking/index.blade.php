@extends('layouts.user-v4')

@section('title', 'Investment Staking - TPIX')

@section('content')
{{-- Risk Warning Banner --}}
<div class="bg-gradient-to-r from-red-500/10 via-orange-500/10 to-yellow-500/10 dark:from-red-900/30 dark:via-orange-900/30 dark:to-yellow-900/30 border border-red-200 dark:border-red-800 rounded-2xl p-6 mb-6">
    <div class="flex items-start gap-4">
        <div class="flex-shrink-0 w-12 h-12 rounded-full bg-red-100 dark:bg-red-900/50 flex items-center justify-center">
            <i class="fas fa-exclamation-triangle text-2xl text-red-600 dark:text-red-400"></i>
        </div>
        <div class="flex-1">
            <h3 class="text-lg font-bold text-red-800 dark:text-red-300 mb-2">
                <i class="fas fa-shield-alt mr-2"></i>คำเตือนความเสี่ยงในการลงทุน
            </h3>
            <ul class="text-sm text-red-700 dark:text-red-400 space-y-1">
                <li>• การลงทุนมีความเสี่ยง ผลตอบแทนในอดีตไม่รับประกันผลในอนาคต</li>
                <li>• ควรศึกษาข้อมูลให้ละเอียดก่อนตัดสินใจลงทุน</li>
                <li>• ลงทุนเฉพาะเงินที่คุณพร้อมจะสูญเสียได้</li>
                <li>• ผลตอบแทนขึ้นอยู่กับเงื่อนไขของแต่ละแผน</li>
            </ul>
        </div>
        <button onclick="this.closest('.bg-gradient-to-r').style.display='none'" class="text-red-400 hover:text-red-600 dark:hover:text-red-300">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>

{{-- Premium Hero Header (Indigo-Purple-Pink for Staking/Investment) --}}
<div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-500 dark:from-indigo-900 dark:via-purple-900 dark:to-pink-900 p-8 md:p-12 mb-8 shadow-2xl">
    {{-- Premium Animated Background Orbs --}}
    <div class="absolute inset-0 opacity-10">
        <div class="absolute top-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s"></div>
    </div>

    {{-- Floating Icons --}}
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute text-white/10 text-8xl top-10 right-20" style="animation: float 6s ease-in-out infinite">
            <i class="fas fa-piggy-bank"></i>
        </div>
    </div>

    <div class="relative z-10">
        <div class="flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="text-center md:text-left">
                <div class="inline-flex items-center gap-2 glass-fusion rounded-full px-4 py-2 mb-4">
                    <i class="fas fa-chart-line text-white"></i>
                    <span class="text-white font-semibold text-sm">Smart Investment</span>
                </div>
                <h1 class="text-3xl md:text-5xl font-bold text-white mb-3 drop-shadow-lg">
                    ลงทุนวันนี้ รับผลตอบแทนทุกวัน
                </h1>
                <p class="text-white/90 text-lg mb-2">เลือกแผนที่เหมาะกับคุณ • ลงทุนได้หลาย Pot</p>
                <p class="text-white/70 text-sm">รองรับการลงทุนด้วย THB และ Coin</p>
            </div>

            {{-- Quick Stats --}}
            <div class="flex gap-4">
                <div class="glass-fusion rounded-2xl p-4 text-center min-w-[120px]">
                    <div class="text-3xl font-bold text-white" id="userTotalStaked">-</div>
                    <div class="text-white/70 text-xs">ยอดลงทุนของฉัน</div>
                </div>
                <div class="glass-fusion rounded-2xl p-4 text-center min-w-[120px]">
                    <div class="text-3xl font-bold text-green-300" id="userTotalEarned">-</div>
                    <div class="text-white/70 text-xs">กำไรที่ได้รับ</div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

{{-- User Balance Cards --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    {{-- THB Balance --}}
    <div class="tp-card rounded-2xl p-6 border border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-full bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center shadow-lg">
                <i class="fas fa-wallet text-2xl text-white"></i>
            </div>
            <div>
                <div class="text-sm text-gray-500 dark:text-gray-400">ยอดเงินในกระเป๋า</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white">
                    ฿{{ number_format(auth()->user()->wallet->balance ?? 0, 2) }}
                </div>
            </div>
        </div>
    </div>

    {{-- Coin Balance --}}
    <div class="tp-card rounded-2xl p-6 border border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-full bg-gradient-to-br from-yellow-500 to-orange-600 flex items-center justify-center shadow-lg">
                <i class="fas fa-coins text-2xl text-white"></i>
            </div>
            <div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Coin ที่มี</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ number_format(auth()->user()->video_coins ?? 0) }} <span class="text-sm text-yellow-600">Coins</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Active Positions --}}
    <div class="tp-card rounded-2xl p-6 border border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-full bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center shadow-lg">
                <i class="fas fa-layer-group text-2xl text-white"></i>
            </div>
            <div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Pot ที่กำลังลงทุน</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white" id="activePotCount">0</div>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    {{-- Main Content: Investment Plans --}}
    <div class="lg:col-span-2 space-y-6">
        {{-- Section Header --}}
        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                <span class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-600 to-purple-600 flex items-center justify-center">
                    <i class="fas fa-cubes text-white text-lg"></i>
                </span>
                แผนการลงทุน
            </h2>
            <div class="flex gap-2">
                <button class="plan-filter active px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium" data-filter="all">
                    ทั้งหมด
                </button>
                <button class="plan-filter px-4 py-2 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium" data-filter="coin">
                    <i class="fas fa-coins mr-1"></i>รับ Coin
                </button>
            </div>
        </div>

        {{-- Plans List --}}
        <div id="investmentPlans" class="space-y-4">
            {{-- Loading State --}}
            <div class="flex items-center justify-center py-12">
                <div class="animate-spin rounded-full h-12 w-12 border-4 border-indigo-600 border-t-transparent"></div>
            </div>
        </div>
    </div>

    {{-- Sidebar: My Positions --}}
    <div class="lg:col-span-1 space-y-6">
        {{-- My Active Positions --}}
        <div class="tp-card rounded-3xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-gray-900 dark:to-gray-800 border-b border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-chart-pie text-indigo-600 dark:text-indigo-400"></i>
                    Pot ที่กำลังลงทุน
                </h3>
            </div>

            <div id="myPositions" class="divide-y divide-gray-200 dark:divide-gray-700 max-h-[500px] overflow-y-auto">
                {{-- Will be populated by JavaScript --}}
                <div class="flex flex-col items-center justify-center py-12 px-6 text-center">
                    <i class="fas fa-seedling text-5xl text-gray-300 dark:text-gray-600 mb-4"></i>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">ยังไม่มี Pot ที่ลงทุน</p>
                    <p class="text-gray-400 dark:text-gray-500 text-xs mt-1">เริ่มลงทุนเลย!</p>
                </div>
            </div>
        </div>

        {{-- Investment Tips --}}
        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-gray-800 dark:to-gray-900 rounded-2xl p-6 border border-blue-200 dark:border-gray-700">
            <h4 class="font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <i class="fas fa-lightbulb text-yellow-500"></i>
                เคล็ดลับการลงทุน
            </h4>
            <ul class="space-y-3 text-sm text-gray-600 dark:text-gray-400">
                <li class="flex items-start gap-2">
                    <i class="fas fa-check-circle text-green-500 mt-0.5"></i>
                    <span>กระจายความเสี่ยงด้วยการลงทุนหลาย Pot</span>
                </li>
                <li class="flex items-start gap-2">
                    <i class="fas fa-check-circle text-green-500 mt-0.5"></i>
                    <span>เลือกแผนที่เหมาะกับระยะเวลาที่ต้องการ</span>
                </li>
                <li class="flex items-start gap-2">
                    <i class="fas fa-check-circle text-green-500 mt-0.5"></i>
                    <span>ใช้ Coin จากภารกิจมาลงทุนเพิ่มได้</span>
                </li>
                <li class="flex items-start gap-2">
                    <i class="fas fa-check-circle text-green-500 mt-0.5"></i>
                    <span>ติดตามผลตอบแทนทุกวันผ่านหน้านี้</span>
                </li>
            </ul>
        </div>
    </div>
</div>

{{-- Investment Modal --}}
<div id="investModal" class="fixed inset-0 z-50 hidden" x-data="investmentModal()">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="tp-card rounded-3xl max-w-lg w-full max-h-[90vh] overflow-y-auto" @click.stop>
            {{-- Modal Header --}}
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 p-6 text-white">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold" x-text="plan?.name || 'ลงทุน'"></h3>
                    <button @click="closeModal()" class="text-white/80 hover:text-white">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <p class="text-white/80 text-sm mt-1" x-text="plan?.description"></p>
            </div>

            {{-- Modal Body --}}
            <div class="p-6 space-y-6">
                {{-- Risk Warning --}}
                <div class="bg-orange-50 dark:bg-orange-900/30 border border-orange-200 dark:border-orange-800 rounded-xl p-4">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-exclamation-circle text-orange-600 dark:text-orange-400 mt-0.5"></i>
                        <div>
                            <p class="text-sm font-medium text-orange-800 dark:text-orange-300">ความเสี่ยง: <span x-text="plan?.risk_label"></span></p>
                            <p class="text-xs text-orange-600 dark:text-orange-400 mt-1" x-text="plan?.risk_warning"></p>
                        </div>
                    </div>
                </div>

                {{-- Plan Details --}}
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-xl p-4">
                        <div class="text-sm text-gray-500 dark:text-gray-400">ผลตอบแทน</div>
                        <div class="text-xl font-bold text-green-600 dark:text-green-400" x-text="plan?.roi_rate + '%'"></div>
                        <div class="text-xs text-gray-400" x-text="'/' + plan?.roi_frequency"></div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-xl p-4">
                        <div class="text-sm text-gray-500 dark:text-gray-400">ระยะเวลา</div>
                        <div class="text-xl font-bold text-indigo-600 dark:text-indigo-400" x-text="plan?.term_days + ' วัน'"></div>
                        <div class="text-xs text-gray-400" x-text="'Lock ' + plan?.lock_days + ' วัน'"></div>
                    </div>
                </div>

                {{-- Payment Method --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">เลือกวิธีชำระ</label>
                    <div class="grid grid-cols-2 gap-3">
                        <button type="button"
                            @click="paymentMethod = 'money'"
                            :class="paymentMethod === 'money' ? 'ring-2 ring-green-500 bg-green-50 dark:bg-green-900/30' : 'bg-gray-50 dark:bg-gray-700'"
                            class="p-4 rounded-xl border border-gray-200 dark:border-gray-600 transition-all">
                            <i class="fas fa-wallet text-2xl text-green-600 dark:text-green-400"></i>
                            <div class="text-sm font-medium mt-2 text-gray-900 dark:text-white">เงินสด (THB)</div>
                            <div class="text-xs text-gray-500">คงเหลือ: ฿<span x-text="formatNumber(userBalance)"></span></div>
                        </button>
                        <button type="button"
                            @click="paymentMethod = 'coin'"
                            :class="paymentMethod === 'coin' ? 'ring-2 ring-yellow-500 bg-yellow-50 dark:bg-yellow-900/30' : 'bg-gray-50 dark:bg-gray-700'"
                            class="p-4 rounded-xl border border-gray-200 dark:border-gray-600 transition-all"
                            x-show="plan?.allow_coin">
                            <i class="fas fa-coins text-2xl text-yellow-600 dark:text-yellow-400"></i>
                            <div class="text-sm font-medium mt-2 text-gray-900 dark:text-white">Coin</div>
                            <div class="text-xs text-gray-500">คงเหลือ: <span x-text="formatNumber(userCoins)"></span> Coins</div>
                        </button>
                    </div>
                </div>

                {{-- Amount Input --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        จำนวนที่ต้องการลงทุน
                        <span x-show="paymentMethod === 'money'">(THB)</span>
                        <span x-show="paymentMethod === 'coin'">(Coins)</span>
                    </label>
                    <div class="relative">
                        <input type="number" x-model="amount"
                            :min="plan?.min_amount"
                            :max="plan?.max_amount"
                            class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            placeholder="0.00">
                        <button type="button" @click="setMaxAmount()"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:underline">
                            MAX
                        </button>
                    </div>
                    <div class="flex justify-between text-xs text-gray-500 mt-2">
                        <span>ขั้นต่ำ: <span x-text="formatNumber(plan?.min_amount)"></span></span>
                        <span>สูงสุด: <span x-text="plan?.max_amount ? formatNumber(plan?.max_amount) : 'ไม่จำกัด'"></span></span>
                    </div>
                </div>

                {{-- Expected ROI --}}
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/30 dark:to-emerald-900/30 rounded-xl p-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">ผลตอบแทนที่คาดหวัง</span>
                        <span class="text-xl font-bold text-green-600 dark:text-green-400">
                            ฿<span x-text="formatNumber(expectedROI)"></span>
                        </span>
                    </div>
                    <div class="text-xs text-gray-500 mt-1">ตลอดระยะเวลา <span x-text="plan?.term_days"></span> วัน</div>
                </div>

                {{-- Checkbox Agreement --}}
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" x-model="agreed" class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-sm text-gray-600 dark:text-gray-400">
                        ข้าพเจ้าได้อ่านและเข้าใจความเสี่ยงในการลงทุนแล้ว และยอมรับเงื่อนไขการลงทุน
                    </span>
                </label>
            </div>

            {{-- Modal Footer --}}
            <div class="p-6 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
                <button type="button"
                    @click="submitInvestment()"
                    :disabled="!canSubmit"
                    :class="canSubmit ? 'bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700' : 'bg-gray-400 cursor-not-allowed'"
                    class="w-full py-4 rounded-xl text-white font-bold text-lg transition-all shadow-lg">
                    <i class="fas fa-rocket mr-2"></i>
                    ยืนยันการลงทุน
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
/**
 * Investment Modal Alpine Component
 * จัดการ Modal สำหรับการลงทุน
 */
function investmentModal() {
    return {
        plan: null,
        amount: 0,
        paymentMethod: 'money',
        userBalance: {{ auth()->user()->wallet->balance ?? 0 }},
        userCoins: {{ auth()->user()->video_coins ?? 0 }},
        agreed: false,

        get expectedROI() {
            if (!this.plan || !this.amount) return 0;
            const rate = this.plan.roi_rate / 100;
            let multiplier = this.plan.term_days;
            if (this.plan.roi_frequency === 'weekly') multiplier = Math.ceil(this.plan.term_days / 7);
            if (this.plan.roi_frequency === 'monthly') multiplier = Math.ceil(this.plan.term_days / 30);

            let investAmount = this.amount;
            if (this.paymentMethod === 'coin') {
                investAmount = this.amount * (this.plan.coin_rate || 1);
            }
            return investAmount * rate * multiplier;
        },

        get canSubmit() {
            if (!this.agreed || !this.amount || this.amount <= 0) return false;
            if (this.plan?.min_amount && this.amount < this.plan.min_amount) return false;
            if (this.plan?.max_amount && this.amount > this.plan.max_amount) return false;
            if (this.paymentMethod === 'money' && this.amount > this.userBalance) return false;
            if (this.paymentMethod === 'coin' && this.amount > this.userCoins) return false;
            return true;
        },

        formatNumber(num) {
            if (!num) return '0';
            return parseFloat(num).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        setMaxAmount() {
            if (this.paymentMethod === 'money') {
                this.amount = Math.min(this.userBalance, this.plan?.max_amount || this.userBalance);
            } else {
                this.amount = Math.min(this.userCoins, this.plan?.max_coin_amount || this.userCoins);
            }
        },

        closeModal() {
            document.getElementById('investModal').classList.add('hidden');
            this.plan = null;
            this.amount = 0;
            this.agreed = false;
        },

        submitInvestment() {
            if (!this.canSubmit) return;

            // TODO: Implement API call
            console.log('Submitting investment:', {
                plan_id: this.plan?.id,
                amount: this.amount,
                payment_method: this.paymentMethod
            });

            // Show success message
            alert('ลงทุนสำเร็จ! (Demo)');
            this.closeModal();
        }
    }
}

/**
 * เปิด Modal ลงทุน
 * @param {Object} plan - ข้อมูลแผนการลงทุน
 */
function openInvestModal(plan) {
    const modal = document.getElementById('investModal');
    modal.classList.remove('hidden');

    // Set plan data to Alpine component
    const component = Alpine.$data(modal);
    if (component) {
        component.plan = plan;
        component.amount = plan.min_amount || 0;
        component.paymentMethod = 'money';
        component.agreed = false;
    }
}

/**
 * โหลดข้อมูลแผนการลงทุน
 */
async function loadInvestmentPlans() {
    const container = document.getElementById('investmentPlans');

    // Mock data - Replace with actual API call
    const plans = [
        {
            id: 1,
            name: 'Starter Plan',
            name_th: 'แผนเริ่มต้น',
            description: 'เหมาะสำหรับผู้เริ่มต้น ความเสี่ยงต่ำ',
            min_amount: 1000,
            max_amount: 50000,
            roi_rate: 0.5,
            roi_frequency: 'daily',
            term_days: 30,
            lock_days: 7,
            risk_level: 'low',
            risk_label: 'ต่ำ',
            risk_color: 'green',
            allow_coin: true,
            coin_rate: 1.5,
            is_featured: true
        },
        {
            id: 2,
            name: 'Growth Plan',
            name_th: 'แผนเติบโต',
            description: 'ผลตอบแทนสูงขึ้น ความเสี่ยงปานกลาง',
            min_amount: 5000,
            max_amount: 200000,
            roi_rate: 1.0,
            roi_frequency: 'daily',
            term_days: 60,
            lock_days: 14,
            risk_level: 'medium',
            risk_label: 'ปานกลาง',
            risk_color: 'yellow',
            allow_coin: true,
            coin_rate: 1.5,
            is_featured: false
        },
        {
            id: 3,
            name: 'Premium Plan',
            name_th: 'แผนพรีเมียม',
            description: 'ผลตอบแทนสูงสุด สำหรับนักลงทุนมืออาชีพ',
            min_amount: 20000,
            max_amount: null,
            roi_rate: 1.5,
            roi_frequency: 'daily',
            term_days: 90,
            lock_days: 30,
            risk_level: 'high',
            risk_label: 'สูง',
            risk_color: 'orange',
            allow_coin: false,
            coin_rate: 0,
            is_featured: true
        }
    ];

    // Render plans
    container.innerHTML = plans.map(plan => renderPlanCard(plan)).join('');
}

/**
 * สร้าง HTML สำหรับ Plan Card
 */
function renderPlanCard(plan) {
    const riskColors = {
        low: 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300',
        medium: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300',
        high: 'bg-orange-100 text-orange-800 dark:bg-orange-900/50 dark:text-orange-300',
        very_high: 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300'
    };

    return `
        <div class="tp-card rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-xl transition-shadow">
            <div class="p-6">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            ${plan.is_featured ? '<span class="px-2 py-1 bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 text-xs font-medium rounded-full"><i class="fas fa-star mr-1"></i>แนะนำ</span>' : ''}
                            <span class="px-2 py-1 ${riskColors[plan.risk_level]} text-xs font-medium rounded-full">
                                <i class="fas fa-shield-alt mr-1"></i>${plan.risk_label}
                            </span>
                            ${plan.allow_coin ? '<span class="px-2 py-1 bg-yellow-100 dark:bg-yellow-900/50 text-yellow-700 dark:text-yellow-300 text-xs font-medium rounded-full"><i class="fas fa-coins mr-1"></i>รับ Coin</span>' : ''}
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">${plan.name_th || plan.name}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">${plan.description}</p>
                    </div>
                    <div class="text-right">
                        <div class="text-3xl font-bold text-green-600 dark:text-green-400">${plan.roi_rate}%</div>
                        <div class="text-xs text-gray-500">/วัน</div>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4 mb-6 py-4 border-y border-gray-100 dark:border-gray-700">
                    <div class="text-center">
                        <div class="text-sm text-gray-500 dark:text-gray-400">ขั้นต่ำ</div>
                        <div class="font-bold text-gray-900 dark:text-white">฿${plan.min_amount.toLocaleString()}</div>
                    </div>
                    <div class="text-center">
                        <div class="text-sm text-gray-500 dark:text-gray-400">ระยะเวลา</div>
                        <div class="font-bold text-gray-900 dark:text-white">${plan.term_days} วัน</div>
                    </div>
                    <div class="text-center">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Lock</div>
                        <div class="font-bold text-gray-900 dark:text-white">${plan.lock_days} วัน</div>
                    </div>
                </div>

                <button onclick='openInvestModal(${JSON.stringify(plan).replace(/'/g, "\\'")})'
                    class="w-full py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-bold rounded-xl transition-all shadow-lg hover:shadow-xl">
                    <i class="fas fa-rocket mr-2"></i>ลงทุนเลย
                </button>
            </div>
        </div>
    `;
}

/**
 * โหลด Positions ของ User
 */
async function loadMyPositions() {
    const container = document.getElementById('myPositions');

    // Mock data - Replace with actual API call
    const positions = [
        {
            id: 1,
            plan_name: 'Starter Plan',
            amount: 10000,
            invested_at: '2024-11-01',
            next_roi_at: new Date(Date.now() + 3600000), // 1 hour from now
            earned_roi: 450,
            progress: 75,
            status: 'active'
        },
        {
            id: 2,
            plan_name: 'Growth Plan',
            amount: 25000,
            invested_at: '2024-11-15',
            next_roi_at: new Date(Date.now() + 7200000), // 2 hours from now
            earned_roi: 750,
            progress: 40,
            status: 'locked'
        }
    ];

    if (positions.length === 0) {
        container.innerHTML = `
            <div class="flex flex-col items-center justify-center py-12 px-6 text-center">
                <i class="fas fa-seedling text-5xl text-gray-300 dark:text-gray-600 mb-4"></i>
                <p class="text-gray-500 dark:text-gray-400 text-sm">ยังไม่มี Pot ที่ลงทุน</p>
                <p class="text-gray-400 dark:text-gray-500 text-xs mt-1">เริ่มลงทุนเลย!</p>
            </div>
        `;
        return;
    }

    // Update pot count
    document.getElementById('activePotCount').textContent = positions.length;

    // Render positions with countdown
    container.innerHTML = positions.map(pos => renderPositionCard(pos)).join('');

    // Start countdown timers
    startCountdownTimers();
}

/**
 * สร้าง HTML สำหรับ Position Card พร้อม Countdown
 */
function renderPositionCard(position) {
    const statusColors = {
        active: 'text-green-600 bg-green-100 dark:bg-green-900/50',
        locked: 'text-orange-600 bg-orange-100 dark:bg-orange-900/50',
        matured: 'text-blue-600 bg-blue-100 dark:bg-blue-900/50'
    };

    return `
        <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
            <div class="flex items-start justify-between mb-3">
                <div>
                    <h4 class="font-bold text-gray-900 dark:text-white">${position.plan_name}</h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400">฿${position.amount.toLocaleString()}</p>
                </div>
                <span class="px-2 py-1 text-xs font-medium rounded-full ${statusColors[position.status] || statusColors.active}">
                    ${position.status === 'active' ? 'กำลังทำงาน' : position.status === 'locked' ? 'ล็อค' : 'ครบกำหนด'}
                </span>
            </div>

            {{-- Progress Bar --}}
            <div class="mb-3">
                <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
                    <span>ความคืบหน้า</span>
                    <span>${position.progress}%</span>
                </div>
                <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full transition-all" style="width: ${position.progress}%"></div>
                </div>
            </div>

            {{-- Countdown Timer --}}
            <div class="bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/30 dark:to-purple-900/30 rounded-xl p-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-600 dark:text-gray-400">
                        <i class="fas fa-clock mr-1"></i>ปันผลถัดไป
                    </span>
                    <span class="countdown-timer font-mono font-bold text-indigo-600 dark:text-indigo-400"
                          data-target="${position.next_roi_at.toISOString()}">
                        --:--:--
                    </span>
                </div>
            </div>

            {{-- Earned ROI --}}
            <div class="flex justify-between items-center mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                <span class="text-sm text-gray-500 dark:text-gray-400">กำไรที่ได้รับ</span>
                <span class="font-bold text-green-600 dark:text-green-400">+฿${position.earned_roi.toLocaleString()}</span>
            </div>
        </div>
    `;
}

/**
 * เริ่ม Countdown Timers
 */
function startCountdownTimers() {
    const timers = document.querySelectorAll('.countdown-timer');

    function updateTimers() {
        timers.forEach(timer => {
            const target = new Date(timer.dataset.target);
            const now = new Date();
            const diff = target - now;

            if (diff <= 0) {
                timer.textContent = '00:00:00';
                timer.classList.add('text-green-600', 'animate-pulse');
                return;
            }

            const hours = Math.floor(diff / 3600000);
            const minutes = Math.floor((diff % 3600000) / 60000);
            const seconds = Math.floor((diff % 60000) / 1000);

            timer.textContent = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        });
    }

    // Initial update
    updateTimers();

    // Update every second
    setInterval(updateTimers, 1000);
}

/**
 * กรองแผนการลงทุน
 */
function filterPlans(filter) {
    document.querySelectorAll('.plan-filter').forEach(btn => {
        btn.classList.remove('active', 'bg-indigo-600', 'text-white');
        btn.classList.add('bg-gray-200', 'dark:bg-gray-700', 'text-gray-700', 'dark:text-gray-300');
    });

    event.target.classList.add('active', 'bg-indigo-600', 'text-white');
    event.target.classList.remove('bg-gray-200', 'dark:bg-gray-700', 'text-gray-700', 'dark:text-gray-300');

    // TODO: Implement actual filtering
    console.log('Filter plans:', filter);
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    loadInvestmentPlans();
    loadMyPositions();

    // Add click handlers for filter buttons
    document.querySelectorAll('.plan-filter').forEach(btn => {
        btn.addEventListener('click', () => filterPlans(btn.dataset.filter));
    });
});
</script>
@endpush

@push('styles')
<style>
.glass-fusion {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}
@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-20px); }
}
</style>
@endpush
@endsection
