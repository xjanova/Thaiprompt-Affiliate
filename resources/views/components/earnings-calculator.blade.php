{{--
    Universal Earnings Calculator Component

    คำนวณรายได้สุทธิแบบ Real-time สำหรับผู้ขาย/ผู้ให้บริการ
    ใช้ได้กับ Product, Service, Hotel ทุกประเภท

    Props:
    - itemType: 'product', 'service', 'hotel'
    - price: ราคาขาย (required)
    - costPrice: ราคาต้นทุน (optional)
    - platformFee: % Platform Fee (default: 10)
    - providerPv: % Provider PV (Service only, default: 0)
    - cashback: % Cashback (default: 0)
    - vat: % VAT (default: 7)
    - pvValue: PV สำหรับ MLM (default: 0)
    - mlmLevels: Array of MLM levels (default: from settings)

    Usage:
    <x-earnings-calculator
        itemType="product"
        :price="$product->price"
        :costPrice="$product->cost_price"
        :cashback="$product->cashback_percentage"
        :pvValue="$product->pv_value"
    />
--}}

@props([
    'itemType' => 'product',
    'price' => 0,
    'costPrice' => null,
    'platformFee' => 10,
    'providerPv' => 0,
    'cashback' => 0,
    'vat' => 7,
    'pvValue' => 0,
    'mlmLevels' => null,
])

@php
// ดึง MLM levels จาก settings ถ้าไม่มี
$defaultMlmLevels = App\Models\MlmGlobalSetting::get('unilevel_levels', []);
$levels = $mlmLevels ?? $defaultMlmLevels;
$pvToMoneyRate = App\Models\MlmGlobalSetting::get('pv_to_money_rate', 1.00);
$vatEnabled = App\Models\MlmGlobalSetting::get('vat_enabled', true);
@endphp

<div x-data="earningsCalculator({
    itemType: '{{ $itemType }}',
    price: {{ $price }},
    costPrice: {{ $costPrice ?? 'null' }},
    platformFee: {{ $platformFee }},
    providerPv: {{ $providerPv }},
    cashback: {{ $cashback }},
    vat: {{ $vat }},
    pvValue: {{ $pvValue }},
    pvToMoneyRate: {{ $pvToMoneyRate }},
    mlmLevels: @js($levels),
    vatEnabled: {{ $vatEnabled ? 'true' : 'false' }}
})" class="backdrop-blur-xl bg-gradient-to-br from-blue-50/90 to-indigo-100/80 dark:from-blue-900/40 dark:to-indigo-800/30 rounded-2xl border border-blue-200/50 dark:border-blue-700/30 p-6 sticky top-4">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-bold text-blue-900 dark:text-blue-100 flex items-center gap-2">
            <i class="fas fa-calculator"></i>
            คำนวณรายได้สุทธิ
        </h3>
        <button @click="showExplanation = !showExplanation"
                class="text-blue-600 dark:text-blue-400 hover:text-blue-700 text-sm">
            <i class="fas fa-info-circle"></i> วิธีคำนวณ
        </button>
    </div>

    {{-- Explanation Modal --}}
    <div x-show="showExplanation"
         x-transition
         @click.away="showExplanation = false"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div @click.stop class="bg-white dark:bg-gray-800 rounded-2xl p-6 max-w-2xl max-h-[80vh] overflow-y-auto">
            <h4 class="text-xl font-bold mb-4">🧮 สูตรการคำนวณ</h4>
            <div class="space-y-3 text-sm">
                <div><strong>1. ราคาขาย:</strong> ราคาที่ลูกค้าจ่าย</div>
                <div><strong>2. หัก Platform Fee:</strong> ค่าบริการแพลตฟอร์ม (แอดมินตั้งได้)</div>
                <template x-if="itemType === 'service'">
                    <div><strong>3. หัก Provider PV:</strong> ค่าการตลาด (คุณตั้งเอง, ยิ่งสูง=โอกาสถูกแนะนำเยอะ)</div>
                </template>
                <div><strong>4. หัก Cashback:</strong> คุณจ่ายเอง เป็นค่าการตลาด</div>
                <div><strong>5. หัก VAT:</strong> ภาษีมูลค่าเพิ่ม (คุณจ่าย)</div>
                <div><strong>6. หัก MLM Commission:</strong> คำนวณจาก PV (คุณจ่าย)</div>
                <div class="pt-2 border-t"><strong>= รายได้สุทธิ</strong> (โอนเข้า wallet)</div>
                <div class="mt-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                    <strong>💡 เคล็ดลับ:</strong> ยิ่ง Cashback & PV สูง = ลูกค้าสนใจมากขึ้น แต่กำไรคุณลด
                </div>
            </div>
            <button @click="showExplanation = false" class="mt-4 w-full py-2 bg-blue-600 text-white rounded-lg">
                เข้าใจแล้ว
            </button>
        </div>
    </div>

    {{-- Input Section --}}
    <div class="space-y-4 mb-6">
        {{-- ราคาขาย --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                ราคาขาย (บาท)
            </label>
            <input type="number"
                   x-model.number="price"
                   min="0"
                   step="0.01"
                   class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
        </div>

        {{-- ราคาต้นทุน --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
                ราคาต้นทุน (บาท)
                <i class="fas fa-info-circle text-xs text-gray-500 cursor-help"
                   title="ถ้ากรอก จะแสดงกำไรสุทธิและกราฟ"></i>
            </label>
            <input type="number"
                   x-model.number="costPrice"
                   min="0"
                   step="0.01"
                   placeholder="ไม่บังคับ"
                   class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
        </div>

        {{-- Cashback --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
                Cashback (%)
                <i class="fas fa-info-circle text-xs text-gray-500 cursor-help"
                   title="คุณจ่ายเอง, ยิ่งสูง=ลูกค้าสนใจเยอะ"></i>
            </label>
            <input type="number"
                   x-model.number="cashback"
                   min="0"
                   max="100"
                   step="0.5"
                   class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
        </div>

        {{-- PV Value --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
                PV (สำหรับ MLM)
                <i class="fas fa-info-circle text-xs text-gray-500 cursor-help"
                   title="PV ที่ใช้คำนวณค่าคอม MLM (0=ไม่มีคอม)"></i>
            </label>
            <input type="number"
                   x-model.number="pvValue"
                   min="0"
                   step="1"
                   class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
        </div>
    </div>

    {{-- Calculation Result --}}
    <div class="space-y-3">
        <h4 class="font-semibold text-gray-900 dark:text-gray-100 text-sm mb-3 flex items-center gap-2">
            <i class="fas fa-chart-line"></i>
            การคำนวณ
        </h4>

        {{-- 1. ราคาขาย --}}
        <div class="flex justify-between items-center text-sm">
            <span class="text-gray-600 dark:text-gray-400">ราคาขาย:</span>
            <span class="font-semibold text-gray-900 dark:text-gray-100">
                ฿<span x-text="price.toLocaleString('th-TH', {minimumFractionDigits: 2})"></span>
            </span>
        </div>

        {{-- 2. Platform Fee --}}
        <div class="flex justify-between items-center text-sm">
            <span class="text-gray-600 dark:text-gray-400">
                ค่า Platform (<span x-text="platformFee.toFixed(2)"></span>%):
            </span>
            <span class="text-red-600 dark:text-red-400">
                -฿<span x-text="calculations.platformFeeAmount.toLocaleString('th-TH', {minimumFractionDigits: 2})"></span>
            </span>
        </div>

        {{-- 3. Provider PV (Service only) --}}
        <template x-if="itemType === 'service' && providerPv > 0">
            <div class="flex justify-between items-center text-sm">
                <span class="text-gray-600 dark:text-gray-400">
                    ค่า PV (<span x-text="providerPv.toFixed(2)"></span>%):
                </span>
                <span class="text-orange-600 dark:text-orange-400">
                    -฿<span x-text="calculations.providerPvAmount.toLocaleString('th-TH', {minimumFractionDigits: 2})"></span>
                </span>
            </div>
        </template>

        {{-- 4. Cashback --}}
        <template x-if="cashback > 0">
            <div class="flex justify-between items-center text-sm">
                <span class="text-gray-600 dark:text-gray-400">
                    Cashback (<span x-text="cashback.toFixed(2)"></span>%):
                </span>
                <span class="text-purple-600 dark:text-purple-400">
                    -฿<span x-text="calculations.cashbackAmount.toLocaleString('th-TH', {minimumFractionDigits: 2})"></span>
                </span>
            </div>
        </template>

        {{-- 5. VAT --}}
        <template x-if="vatEnabled">
            <div class="flex justify-between items-center text-sm">
                <span class="text-gray-600 dark:text-gray-400">
                    VAT (<span x-text="vat.toFixed(2)"></span>%):
                </span>
                <span class="text-yellow-600 dark:text-yellow-400">
                    -฿<span x-text="calculations.vatAmount.toLocaleString('th-TH', {minimumFractionDigits: 2})"></span>
                </span>
            </div>
        </template>

        {{-- 6. MLM Commission --}}
        <template x-if="pvValue > 0">
            <div class="flex justify-between items-center text-sm">
                <span class="text-gray-600 dark:text-gray-400 flex items-center gap-1">
                    คอม MLM
                    <button @click="showMlmDetail = !showMlmDetail" class="text-xs text-blue-500">
                        <i class="fas" :class="showMlmDetail ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                    </button>
                </span>
                <span class="text-indigo-600 dark:text-indigo-400">
                    -฿<span x-text="calculations.mlmCommissionTotal.toLocaleString('th-TH', {minimumFractionDigits: 2})"></span>
                </span>
            </div>

            {{-- MLM Detail --}}
            <div x-show="showMlmDetail" x-transition class="ml-4 space-y-1 text-xs">
                <template x-for="(level, index) in calculations.mlmLevels" :key="index">
                    <div class="flex justify-between text-gray-500 dark:text-gray-400">
                        <span>Level <span x-text="level.level"></span> (<span x-text="level.percentage"></span>%):</span>
                        <span>-฿<span x-text="level.amount.toFixed(2)"></span></span>
                    </div>
                </template>
            </div>
        </template>

        {{-- Divider --}}
        <div class="border-t border-blue-300/50 dark:border-blue-600/30 my-3"></div>

        {{-- รายได้สุทธิ --}}
        <div class="flex justify-between items-center pt-2">
            <span class="font-bold text-gray-900 dark:text-gray-100">คุณจะได้รับ:</span>
            <span class="text-3xl font-bold text-green-600 dark:text-green-400">
                ฿<span x-text="calculations.netEarnings.toLocaleString('th-TH', {minimumFractionDigits: 2})"></span>
            </span>
        </div>

        {{-- เปอร์เซ็นต์รายได้ --}}
        <div class="text-center">
            <span class="text-xs text-gray-500 dark:text-gray-400">
                คุณได้รับ
                <span class="font-bold text-lg" :class="{
                    'text-green-600 dark:text-green-400': calculations.earningsPercentage > 70,
                    'text-yellow-600 dark:text-yellow-400': calculations.earningsPercentage > 50 && calculations.earningsPercentage <= 70,
                    'text-orange-600 dark:text-orange-400': calculations.earningsPercentage <= 50
                }" x-text="calculations.earningsPercentage.toFixed(2)"></span>%
            </span>
        </div>

        {{-- Progress Bar --}}
        <div class="w-full h-4 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
            <div class="h-full bg-gradient-to-r from-green-500 to-emerald-600 transition-all duration-300"
                 :style="{ width: calculations.earningsPercentage + '%' }"></div>
        </div>

        {{-- กำไรสุทธิ (ถ้ามี cost) --}}
        <template x-if="costPrice !== null && costPrice > 0">
            <div class="mt-4 p-4 rounded-lg bg-gradient-to-br from-green-50/90 to-emerald-100/80 dark:from-green-900/40 dark:to-emerald-800/30 border border-green-200/50 dark:border-green-700/30">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">ต้นทุน:</span>
                    <span class="text-sm text-gray-600 dark:text-gray-400">
                        ฿<span x-text="costPrice.toLocaleString('th-TH', {minimumFractionDigits: 2})"></span>
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="font-bold text-gray-900 dark:text-gray-100">กำไรสุทธิ:</span>
                    <span class="text-2xl font-bold"
                          :class="calculations.netProfit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                        <span x-text="calculations.netProfit >= 0 ? '+' : ''"></span>฿<span x-text="Math.abs(calculations.netProfit).toLocaleString('th-TH', {minimumFractionDigits: 2})"></span>
                    </span>
                </div>
                <div class="text-center mt-2">
                    <span class="text-xs text-gray-600 dark:text-gray-400">
                        Profit Margin:
                        <span class="font-bold" x-text="calculations.profitMargin.toFixed(2)"></span>%
                    </span>
                </div>
            </div>
        </template>

        {{-- Tip --}}
        <div class="mt-4 p-3 rounded-lg bg-blue-50/50 dark:bg-blue-900/20 border border-blue-200/50 dark:border-blue-700/30">
            <p class="text-xs text-blue-700 dark:text-blue-300">
                💡 <strong>เคล็ดลับ:</strong> ปรับค่า Cashback และ PV เพื่อหายอดขายเพิ่ม แต่อย่าลืมคำนวณกำไรด้วย!
            </p>
        </div>
    </div>
</div>

@push('scripts')
<script>
function earningsCalculator(config) {
    return {
        itemType: config.itemType,
        price: config.price,
        costPrice: config.costPrice,
        platformFee: config.platformFee,
        providerPv: config.providerPv,
        cashback: config.cashback,
        vat: config.vat,
        pvValue: config.pvValue,
        pvToMoneyRate: config.pvToMoneyRate,
        mlmLevels: config.mlmLevels,
        vatEnabled: config.vatEnabled,
        showExplanation: false,
        showMlmDetail: false,

        get calculations() {
            const price = this.price || 0;

            // 1. Platform Fee
            const platformFeeAmount = price * (this.platformFee / 100);

            // 2. Provider PV (Service only)
            const providerPvAmount = this.itemType === 'service'
                ? price * (this.providerPv / 100)
                : 0;

            // 3. Cashback
            const cashbackAmount = price * (this.cashback / 100);

            // 4. ก่อนหัก VAT
            const beforeTax = price - platformFeeAmount - providerPvAmount - cashbackAmount;

            // 5. VAT
            const vatAmount = this.vatEnabled ? (beforeTax * (this.vat / 100)) : 0;

            // 6. MLM Commission
            const mlmResult = this.calculateMlmCommission();

            // 7. รายได้สุทธิ
            const netEarnings = beforeTax - vatAmount - mlmResult.total;

            // 8. กำไร
            const cost = this.costPrice || 0;
            const netProfit = netEarnings - cost;
            const profitMargin = cost > 0 ? (netProfit / price) * 100 : 0;

            return {
                platformFeeAmount,
                providerPvAmount,
                cashbackAmount,
                vatAmount,
                mlmCommissionTotal: mlmResult.total,
                mlmLevels: mlmResult.levels,
                netEarnings: Math.max(0, netEarnings),
                earningsPercentage: price > 0 ? (Math.max(0, netEarnings) / price) * 100 : 0,
                netProfit,
                profitMargin,
            };
        },

        calculateMlmCommission() {
            if (this.pvValue <= 0) {
                return { total: 0, levels: [] };
            }

            let total = 0;
            const levels = [];

            this.mlmLevels.forEach(level => {
                const percentage = level.percentage || 0;
                if (percentage > 0) {
                    const levelPv = this.pvValue * (percentage / 100);
                    const amount = levelPv * this.pvToMoneyRate;
                    total += amount;

                    levels.push({
                        level: level.level,
                        percentage: percentage,
                        pv: levelPv,
                        amount: amount,
                    });
                }
            });

            return { total, levels };
        },
    };
}
</script>
@endpush
