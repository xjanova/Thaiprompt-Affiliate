{{--
    Provider PV Calculator Component

    Component สำหรับให้ Provider ตั้งค่า PV และดู Earnings แบบ Real-time

    Props:
    - minPv: ค่า PV ต่ำสุด (default: 0)
    - maxPv: ค่า PV สูงสุด (default: 30)
    - currentPv: ค่า PV ปัจจุบัน (default: 5)
    - platformFee: ค่าแพลตฟอร์ม (default: 10)
    - samplePrice: ราคาตัวอย่างสำหรับคำนวณ (default: 1000)

    Usage:
    <x-provider-pv-calculator
        :minPv="0"
        :maxPv="30"
        :currentPv="$service->provider_pv_percentage ?? 5"
        :platformFee="$service->platform_fee_percentage ?? 10"
        :samplePrice="1000"
    />
--}}

@props([
    'minPv' => 0,
    'maxPv' => 30,
    'currentPv' => 5,
    'platformFee' => 10,
    'samplePrice' => 1000,
])

<div x-data="pvCalculator({
    minPv: {{ $minPv }},
    maxPv: {{ $maxPv }},
    currentPv: {{ $currentPv }},
    platformFee: {{ $platformFee }},
    samplePrice: {{ $samplePrice }}
})" class="space-y-6">

    {{-- ส่วนอธิบาย PV --}}
    <div class="p-4 rounded-xl backdrop-blur-xl bg-gradient-to-br from-blue-50/90 to-indigo-100/80 dark:from-blue-900/40 dark:to-indigo-800/30 border border-blue-200/50 dark:border-blue-700/30">
        <h3 class="font-semibold text-blue-900 dark:text-blue-200 mb-2 flex items-center gap-2">
            <i class="fas fa-info-circle"></i>
            ค่า PV (Point Value) คืออะไร?
        </h3>
        <ul class="text-sm text-blue-800 dark:text-blue-300 space-y-1 list-disc list-inside">
            <li>ค่าการตลาดที่คุณยอมจ่ายเพื่อให้ระบบแนะนำบริการของคุณ</li>
            <li><strong>PV สูง</strong> = โอกาสถูกแนะนำ<strong>มากขึ้น</strong> แต่กำไร<strong>ลดลง</strong></li>
            <li><strong>PV ต่ำ</strong> = โอกาสถูกแนะนำ<strong>น้อยลง</strong> แต่กำไร<strong>เพิ่มขึ้น</strong></li>
            <li>คุณสามารถปรับเปลี่ยนได้ตลอดเวลาตามกลยุทธ์ของคุณ</li>
        </ul>
    </div>

    {{-- PV Slider --}}
    <div class="p-6 rounded-xl backdrop-blur-xl bg-white/90 dark:bg-gray-800/90 border border-gray-200 dark:border-gray-700">
        <label class="block font-semibold text-gray-900 dark:text-gray-100 mb-4">
            <i class="fas fa-sliders-h text-purple-600"></i>
            ตั้งค่า PV ของคุณ
        </label>

        {{-- Current PV Display --}}
        <div class="mb-4 text-center">
            <span class="text-4xl font-bold text-purple-600 dark:text-purple-400" x-text="currentPv.toFixed(2)"></span>
            <span class="text-2xl text-gray-600 dark:text-gray-400">%</span>
        </div>

        {{-- Slider --}}
        <input type="range"
               name="provider_pv_percentage"
               x-model.number="currentPv"
               :min="minPv"
               :max="maxPv"
               step="0.5"
               class="w-full h-3 bg-gradient-to-r from-green-200 via-yellow-200 to-red-200 dark:from-green-800 dark:via-yellow-800 dark:to-red-800 rounded-lg appearance-none cursor-pointer accent-purple-600">

        {{-- Min/Max Labels --}}
        <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-2">
            <span>ต่ำสุด: <span x-text="minPv"></span>%</span>
            <span>สูงสุด: <span x-text="maxPv"></span>%</span>
        </div>

        {{-- Strategy Indicator --}}
        <div class="mt-4 p-3 rounded-lg text-sm text-center font-semibold transition-all"
             :class="{
                 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300': currentPv < 10,
                 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300': currentPv >= 10 && currentPv < 20,
                 'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300': currentPv >= 20 && currentPv < 25,
                 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300': currentPv >= 25
             }">
            <template x-if="currentPv < 10">
                <span>🎯 กลยุทธ์: <strong>กำไรสูง</strong> - เหมาะกับผู้ให้บริการที่มีฐานลูกค้าแล้ว</span>
            </template>
            <template x-if="currentPv >= 10 && currentPv < 20">
                <span>⚖️ กลยุทธ์: <strong>สมดุล</strong> - กำไรและการตลาดพอดี</span>
            </template>
            <template x-if="currentPv >= 20 && currentPv < 25">
                <span>🚀 กลยุทธ์: <strong>การตลาดเชิงรุก</strong> - เหมาะกับผู้เริ่มต้น</span>
            </template>
            <template x-if="currentPv >= 25">
                <span>💪 กลยุทธ์: <strong>โฟกัสยอดขาย</strong> - ขยายฐานลูกค้าสูงสุด</span>
            </template>
        </div>
    </div>

    {{-- Earnings Calculator --}}
    <div class="p-6 rounded-xl backdrop-blur-xl bg-gradient-to-br from-green-50/90 to-emerald-100/80 dark:from-green-900/40 dark:to-emerald-800/30 border border-green-200/50 dark:border-green-700/30">
        <h3 class="font-semibold text-green-900 dark:text-green-200 mb-4 flex items-center gap-2">
            <i class="fas fa-calculator"></i>
            คำนวณรายได้ (ตัวอย่างราคา <span x-text="samplePrice.toLocaleString()"></span> บาท)
        </h3>

        <div class="space-y-3">
            {{-- ราคารวม --}}
            <div class="flex justify-between items-center text-sm">
                <span class="text-gray-600 dark:text-gray-400">ราคารวม:</span>
                <span class="font-semibold text-gray-900 dark:text-gray-100">
                    ฿<span x-text="samplePrice.toLocaleString('th-TH', {minimumFractionDigits: 2})"></span>
                </span>
            </div>

            {{-- ค่าแพลตฟอร์ม --}}
            <div class="flex justify-between items-center text-sm">
                <span class="text-gray-600 dark:text-gray-400">
                    ค่าแพลตฟอร์ม (<span x-text="platformFee.toFixed(2)"></span>%)
                </span>
                <span class="text-red-600 dark:text-red-400">
                    -฿<span x-text="platformFeeAmount.toLocaleString('th-TH', {minimumFractionDigits: 2})"></span>
                </span>
            </div>

            {{-- ค่า PV --}}
            <div class="flex justify-between items-center text-sm">
                <span class="text-gray-600 dark:text-gray-400">
                    ค่า PV/การตลาด (<span x-text="currentPv.toFixed(2)"></span>%)
                </span>
                <span class="text-orange-600 dark:text-orange-400">
                    -฿<span x-text="pvAmount.toLocaleString('th-TH', {minimumFractionDigits: 2})"></span>
                </span>
            </div>

            {{-- เส้นแบ่ง --}}
            <div class="border-t border-green-300/50 dark:border-green-600/30"></div>

            {{-- รายได้สุทธิ --}}
            <div class="flex justify-between items-center pt-2">
                <span class="font-bold text-gray-900 dark:text-gray-100">คุณจะได้รับ:</span>
                <span class="text-3xl font-bold text-green-600 dark:text-green-400">
                    ฿<span x-text="earnings.toLocaleString('th-TH', {minimumFractionDigits: 2})"></span>
                </span>
            </div>

            {{-- เปอร์เซ็นต์รายได้ --}}
            <div class="text-center pt-2 pb-2">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/50 dark:bg-gray-800/50">
                    <span class="text-sm text-gray-600 dark:text-gray-400">คุณได้รับ</span>
                    <span class="text-2xl font-bold" :class="{
                        'text-green-600 dark:text-green-400': earningsPercentage > 75,
                        'text-yellow-600 dark:text-yellow-400': earningsPercentage > 65 && earningsPercentage <= 75,
                        'text-orange-600 dark:text-orange-400': earningsPercentage <= 65
                    }" x-text="earningsPercentage.toFixed(2)"></span>
                    <span class="text-sm text-gray-600 dark:text-gray-400">%</span>
                </div>
            </div>

            {{-- ตารางเปรียบเทียบ --}}
            <div class="mt-4 p-3 rounded-lg bg-white/50 dark:bg-gray-800/50">
                <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">💡 เปรียบเทียบ:</p>
                <div class="grid grid-cols-3 gap-2 text-xs">
                    <div class="text-center">
                        <div class="font-semibold text-gray-600 dark:text-gray-400">PV 5%</div>
                        <div class="text-green-600 dark:text-green-400 font-bold">
                            ฿<span x-text="(samplePrice * 0.85).toLocaleString('th-TH', {minimumFractionDigits: 0})"></span>
                        </div>
                    </div>
                    <div class="text-center">
                        <div class="font-semibold text-gray-600 dark:text-gray-400">PV 15%</div>
                        <div class="text-yellow-600 dark:text-yellow-400 font-bold">
                            ฿<span x-text="(samplePrice * 0.75).toLocaleString('th-TH', {minimumFractionDigits: 0})"></span>
                        </div>
                    </div>
                    <div class="text-center">
                        <div class="font-semibold text-gray-600 dark:text-gray-400">PV 25%</div>
                        <div class="text-orange-600 dark:text-orange-400 font-bold">
                            ฿<span x-text="(samplePrice * 0.65).toLocaleString('th-TH', {minimumFractionDigits: 0})"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
function pvCalculator(config) {
    return {
        minPv: config.minPv,
        maxPv: config.maxPv,
        currentPv: config.currentPv,
        platformFee: config.platformFee,
        samplePrice: config.samplePrice,

        get platformFeeAmount() {
            return this.samplePrice * (this.platformFee / 100);
        },

        get pvAmount() {
            return this.samplePrice * (this.currentPv / 100);
        },

        get earnings() {
            return this.samplePrice - this.platformFeeAmount - this.pvAmount;
        },

        get earningsPercentage() {
            return 100 - this.platformFee - this.currentPv;
        }
    }
}
</script>
@endpush
