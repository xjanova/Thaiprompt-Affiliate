@extends('layouts.user-v4')

@section('title', 'เครื่องคำนวณ TPIX Staking')

@section('content')
{{--
    TPIX Staking Calculator (Theme V4 "นวลทองคำ")
    ============================================
    หน้าคำนวณผลตอบแทนจากการ Staking เหรียญ TPIX
    - คำนวณ staking rewards ตามเวลา
    - Lock period options (30, 90, 180, 365 วัน)
    - APY แตกต่างกันตาม lock period (30% - 120%)
    - Auto-compound option + Real-time animation
    - กราฟใช้ Chart.js (กราฟจริงเพื่อ core functionality — คงไว้ตามกฎ V4)

    APY Structure (ตามเอกสาร TPIX):
    - 30 วัน → 30% APY
    - 90 วัน → 60% APY
    - 180 วัน → 90% APY
    - 365 วัน → 120% APY (Max)
--}}

<div style="display:flex; flex-direction:column; gap:18px;"
     x-data="tpixStakingCalculator()"
     x-init="init()">

    {{-- ── Hero ───────────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:20px 24px; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
            <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:16px;">
                <div style="display:flex; align-items:center; gap:14px;">
                    <span class="tp-tile" style="width:56px; height:56px; border-radius:18px; font-size:26px;"><i class="fas fa-coins" style="color:#fff;"></i></span>
                    <div>
                        <h1 style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:0;">🪙 เครื่องคำนวณ TPIX Staking</h1>
                        <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">คำนวณผลตอบแทนจากการ Staking เหรียญ TPIX ของคุณ</div>
                    </div>
                </div>
                <a href="{{ route('user.dashboard') }}" class="tp-icon-btn" title="กลับหน้าหลัก" style="text-decoration:none;">
                    <i class="fas fa-arrow-left"></i>
                </a>
            </div>
        </div>
    </div>

    {{-- ── แผงตั้งค่าการคำนวณ ─────────────────────────────────── --}}
    <div class="tp-card" style="padding:20px 22px;">
        <div class="tp-section-h" style="margin-bottom:16px;">
            <i class="fas fa-sliders-h" style="color:var(--deep1); margin-right:6px;"></i> ตั้งค่าการคำนวณ
        </div>

        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(210px,1fr)); gap:16px;">

            {{-- จำนวน TPIX ที่ Stake --}}
            <div>
                <label style="display:block; font-size:13px; font-weight:700; color:var(--ink); margin-bottom:8px;">
                    <i class="fas fa-coins" style="color:var(--deep1); margin-right:6px;"></i> จำนวน TPIX ที่ Stake
                </label>
                <div style="position:relative;">
                    <input type="number"
                           x-model.number="config.stakingAmount"
                           @input="calculateRewards()"
                           step="100"
                           min="0"
                           class="tp-input tp-num"
                           style="width:100%; padding-right:56px; font-size:16px; font-weight:700;">
                    <span style="position:absolute; right:14px; top:50%; transform:translateY(-50%); font-size:12px; font-weight:700; color:var(--deep1);">TPIX</span>
                </div>
                <div style="margin-top:6px; font-size:11px; color:var(--ink2);">ขั้นต่ำ: 100 TPIX</div>
            </div>

            {{-- Lock Period --}}
            <div>
                <label style="display:block; font-size:13px; font-weight:700; color:var(--ink); margin-bottom:8px;">
                    <i class="fas fa-lock" style="color:#5689b8; margin-right:6px;"></i> ระยะเวลา Lock
                </label>
                <select x-model="config.lockPeriod"
                        @change="updateAPY(); calculateRewards()"
                        class="tp-input"
                        style="width:100%; font-size:15px; font-weight:700;">
                    <option value="30">30 วัน (APY 30%)</option>
                    <option value="90">90 วัน (APY 60%)</option>
                    <option value="180">180 วัน (APY 90%)</option>
                    <option value="365">365 วัน (APY 120%)</option>
                </select>
                <div style="margin-top:6px; font-size:11px; color:var(--ink2);">ระยะเวลาที่ล็อค TPIX</div>
            </div>

            {{-- APY (Auto-calculated) --}}
            <div>
                <label style="display:block; font-size:13px; font-weight:700; color:var(--ink); margin-bottom:8px;">
                    <i class="fas fa-chart-line" style="color:#5aa07e; margin-right:6px;"></i> อัตราผลตอบแทน (APY)
                </label>
                <div style="display:flex; align-items:center; justify-content:space-between; padding:13px 14px; border-radius:13px; box-shadow:var(--inset-sm); font-size:16px; font-weight:800; color:#5aa07e;">
                    <span class="tp-num" x-text="config.apy"></span>
                    <span style="font-size:13px;">%</span>
                </div>
                <div style="margin-top:6px; font-size:11px; color:var(--ink2);">คำนวณอัตโนมัติตาม Lock Period</div>
            </div>

            {{-- Auto Compound --}}
            <div>
                <label style="display:block; font-size:13px; font-weight:700; color:var(--ink); margin-bottom:8px;">
                    <i class="fas fa-sync-alt" style="color:var(--accent2); margin-right:6px;"></i> Auto-Compound
                </label>
                <label style="display:flex; align-items:center; gap:12px; cursor:pointer; padding:11px 14px; border-radius:13px; box-shadow:var(--inset-sm);">
                    <input type="checkbox"
                           x-model="config.autoCompound"
                           @change="calculateRewards()"
                           style="position:absolute; opacity:0; width:0; height:0;">
                    <span style="position:relative; flex-shrink:0; width:48px; height:26px; border-radius:20px; transition:background .25s; box-shadow:var(--inset-sm);"
                          :style="config.autoCompound ? 'background:linear-gradient(90deg,var(--accent1),var(--accent2));' : 'background:color-mix(in srgb,var(--ink2) 28%,transparent);'">
                        <span style="position:absolute; top:3px; left:3px; width:20px; height:20px; border-radius:50%; background:#fff; box-shadow:0 1px 3px rgba(0,0,0,.3); transition:transform .25s; display:flex; align-items:center; justify-content:center; font-size:10px;"
                              :style="config.autoCompound ? 'transform:translateX(22px);' : ''">
                            <i class="fas" :class="config.autoCompound ? 'fa-check' : 'fa-times'" :style="config.autoCompound ? 'color:var(--deep1);' : 'color:var(--ink2);'"></i>
                        </span>
                    </span>
                    <span style="font-size:13px; font-weight:700;" x-text="config.autoCompound ? 'เปิดใช้งาน' : 'ปิดใช้งาน'"></span>
                </label>
                <div style="margin-top:6px; font-size:11px; color:var(--ink2);">นำรางวัลกลับไป stake อัตโนมัติ</div>
            </div>
        </div>

        {{-- ปุ่มดำเนินการ --}}
        <div style="margin-top:18px; display:flex; flex-wrap:wrap; gap:12px;">
            <button @click="startSimulation()"
                    :disabled="isSimulating"
                    class="tp-btn tp-btn-primary"
                    style="flex:1; min-width:200px; justify-content:center; font-size:15px; padding:13px 24px;"
                    :style="isSimulating ? 'opacity:.6; cursor:not-allowed;' : ''">
                <i class="fas" :class="isSimulating ? 'fa-spinner fa-spin' : 'fa-play'"></i>
                <span x-text="isSimulating ? 'กำลังคำนวณ...' : '▶️ เริ่มคำนวณ'"></span>
            </button>

            <button @click="resetSimulation()"
                    class="tp-btn"
                    style="justify-content:center; font-size:15px; padding:13px 24px;">
                <i class="fas fa-redo"></i> รีเซ็ต
            </button>
        </div>
    </div>

    {{-- ── ระหว่างจำลอง (Animation) ───────────────────────────── --}}
    <div x-show="isSimulating"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-cloak
         style="display:flex; flex-direction:column; gap:16px;">

        {{-- การ์ดความคืบหน้า --}}
        <div class="tp-card" style="padding:0; overflow:hidden;">
            <div style="padding:28px 24px; text-align:center; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 18%, transparent), transparent 72%);">
                <div style="font-size:16px; font-weight:700; margin-bottom:10px;">กำลังคำนวณ...</div>
                <div class="tp-num" style="font-size:clamp(38px,8vw,60px); font-weight:800; line-height:1.1; color:var(--deep1);">
                    วันที่ <span x-text="simulation.currentDay"></span>
                </div>
                <div style="font-size:14px; color:var(--ink2); margin-top:4px;">
                    จาก <span x-text="config.lockPeriod"></span> วัน
                </div>

                {{-- Progress Bar --}}
                <div style="margin-top:18px; height:14px; border-radius:20px; box-shadow:var(--inset-sm); overflow:hidden;">
                    <div style="height:100%; border-radius:20px; background:linear-gradient(90deg,var(--accent1),var(--accent2)); transition:width .5s ease;"
                         :style="`width:${(simulation.currentDay / config.lockPeriod) * 100}%`"></div>
                </div>
                <div style="margin-top:8px; font-size:12px; color:var(--ink2);">
                    ความคืบหน้า: <span x-text="Math.floor((simulation.currentDay / config.lockPeriod) * 100)"></span>%
                </div>
            </div>
        </div>

        {{-- สถิติ Real-time 4 ใบ --}}
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:16px;">

            {{-- TPIX ที่ Stake --}}
            <div class="tp-card" style="padding:18px;">
                <span class="tp-tile" style="width:42px; height:42px; border-radius:13px; font-size:17px;"><i class="fas fa-coins" style="color:#fff;"></i></span>
                <div class="tp-num" style="font-size:24px; font-weight:800; margin-top:12px;"><span x-text="formatNumber(simulation.currentStake)"></span></div>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:2px;">TPIX ที่ Stake</div>
            </div>

            {{-- รางวัลวันนี้ --}}
            <div class="tp-card" style="padding:18px;">
                <span class="tp-tile" style="width:42px; height:42px; border-radius:13px; font-size:17px; background:color-mix(in srgb, #5aa07e 18%, transparent);"><i class="fas fa-gift" style="color:#5aa07e;"></i></span>
                <div class="tp-num" style="font-size:24px; font-weight:800; margin-top:12px;"><span x-text="formatNumber(simulation.dailyReward)"></span></div>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:2px;">รางวัลวันนี้</div>
            </div>

            {{-- รางวัลสะสม --}}
            <div class="tp-card" style="padding:18px;">
                <span class="tp-tile" style="width:42px; height:42px; border-radius:13px; font-size:17px; background:color-mix(in srgb, #5689b8 18%, transparent);"><i class="fas fa-chart-line" style="color:#5689b8;"></i></span>
                <div class="tp-num" style="font-size:24px; font-weight:800; margin-top:12px;"><span x-text="formatNumber(simulation.totalRewards)"></span></div>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:2px;">รางวัลสะสมทั้งหมด</div>
            </div>

            {{-- TPIX รวม --}}
            <div class="tp-card" style="padding:18px;">
                <span class="tp-tile" style="width:42px; height:42px; border-radius:13px; font-size:17px; background:color-mix(in srgb, #e0a52e 18%, transparent);"><i class="fas fa-wallet" style="color:#e0a52e;"></i></span>
                <div class="tp-num" style="font-size:24px; font-weight:800; margin-top:12px; color:var(--deep1);"><span x-text="formatNumber(simulation.totalTPIX)"></span></div>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:2px;">TPIX รวมทั้งหมด</div>
            </div>
        </div>
    </div>

    {{-- ── ผลลัพธ์ ─────────────────────────────────────────────── --}}
    <div x-show="showResults"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-cloak
         style="display:flex; flex-direction:column; gap:18px;">

        {{-- สรุปผล 4 ใบ --}}
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:16px;">

            {{-- TPIX ที่ Stake --}}
            <div class="tp-card" style="padding:20px; text-align:center;">
                <div style="font-size:40px;">💰</div>
                <div class="tp-num" style="font-size:26px; font-weight:800; color:var(--deep1); margin-top:8px;"><span x-text="formatNumber(config.stakingAmount)"></span></div>
                <div style="font-size:12.5px; font-weight:600; color:var(--ink2); margin-top:2px;">TPIX ที่ Stake</div>
            </div>

            {{-- รางวัลทั้งหมด --}}
            <div class="tp-card" style="padding:20px; text-align:center;">
                <div style="font-size:40px;">🎁</div>
                <div class="tp-num" style="font-size:26px; font-weight:800; color:#5aa07e; margin-top:8px;"><span x-text="formatNumber(results.totalRewards)"></span></div>
                <div style="font-size:12.5px; font-weight:600; color:var(--ink2); margin-top:2px;">รางวัลทั้งหมด</div>
            </div>

            {{-- ROI --}}
            <div class="tp-card" style="padding:20px; text-align:center;">
                <div style="font-size:40px;">📈</div>
                <div class="tp-num" style="font-size:26px; font-weight:800; color:#5689b8; margin-top:8px;"><span x-text="formatNumber(results.roi)"></span>%</div>
                <div style="font-size:12.5px; font-weight:600; color:var(--ink2); margin-top:2px;">ผลตอบแทน (ROI)</div>
            </div>

            {{-- TPIX ที่ได้รับ --}}
            <div class="tp-card" style="padding:20px; text-align:center;">
                <div style="font-size:40px;">🚀</div>
                <div class="tp-num" style="font-size:26px; font-weight:800; color:#e0a52e; margin-top:8px;"><span x-text="formatNumber(results.finalTPIX)"></span></div>
                <div style="font-size:12.5px; font-weight:600; color:var(--ink2); margin-top:2px;">TPIX ทั้งหมดที่ได้</div>
            </div>
        </div>

        {{-- ตารางรายละเอียดรายวัน --}}
        <div class="tp-card" style="padding:0; overflow:hidden;">
            <div style="padding:18px 20px; border-bottom:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                <div class="tp-section-h"><i class="fas fa-table" style="color:var(--deep1); margin-right:6px;"></i> รายละเอียดรางวัลแต่ละวัน</div>
            </div>
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; font-size:13px;">
                    <thead>
                        <tr style="text-align:left; color:var(--ink2); box-shadow:var(--inset-sm);">
                            <th style="padding:12px 16px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px;">วันที่</th>
                            <th style="padding:12px 16px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; text-align:right;">TPIX ที่ Stake</th>
                            <th style="padding:12px 16px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; text-align:right;">รางวัลวันนี้</th>
                            <th style="padding:12px 16px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; text-align:right;">รางวัลสะสม</th>
                            <th style="padding:12px 16px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; text-align:right;">TPIX รวม</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(day, index) in results.dailyBreakdown" :key="index">
                            <tr style="border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                                <td style="padding:12px 16px; white-space:nowrap; font-weight:600;">วันที่ <span class="tp-num" x-text="day.day"></span></td>
                                <td class="tp-num" style="padding:12px 16px; text-align:right; white-space:nowrap; font-weight:700; color:var(--deep1);"><span x-text="formatNumber(day.stake)"></span></td>
                                <td class="tp-num" style="padding:12px 16px; text-align:right; white-space:nowrap; font-weight:700; color:#5aa07e;"><span x-text="formatNumber(day.dailyReward)"></span></td>
                                <td class="tp-num" style="padding:12px 16px; text-align:right; white-space:nowrap; font-weight:700; color:#5689b8;"><span x-text="formatNumber(day.totalRewards)"></span></td>
                                <td class="tp-num" style="padding:12px 16px; text-align:right; white-space:nowrap; font-weight:700; color:#e0a52e;"><span x-text="formatNumber(day.totalTPIX)"></span></td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot>
                        <tr style="border-top:2px solid color-mix(in srgb, var(--ink2) 20%, transparent); box-shadow:var(--inset-sm);">
                            <td colspan="3" style="padding:14px 16px; text-align:right; font-size:13px; font-weight:700;">รวมทั้งหมด:</td>
                            <td class="tp-num" style="padding:14px 16px; text-align:right; font-size:15px; font-weight:800; color:#5aa07e;"><span x-text="formatNumber(results.totalRewards)"></span></td>
                            <td class="tp-num" style="padding:14px 16px; text-align:right; font-size:15px; font-weight:800; color:#e0a52e;"><span x-text="formatNumber(results.finalTPIX)"></span></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- กราฟ (Chart.js — กราฟจริงเพื่อ core functionality) --}}
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(300px,1fr)); gap:16px;">

            {{-- Growth Chart --}}
            <div class="tp-card" style="padding:20px;">
                <div class="tp-section-h" style="margin-bottom:14px;"><i class="fas fa-chart-area" style="color:var(--deep1); margin-right:6px;"></i> กราฟการเติบโตของ TPIX</div>
                <div style="position:relative; height:300px;">
                    <canvas id="growth-chart"></canvas>
                </div>
            </div>

            {{-- Rewards Distribution --}}
            <div class="tp-card" style="padding:20px;">
                <div class="tp-section-h" style="margin-bottom:14px;"><i class="fas fa-chart-pie" style="color:var(--accent2); margin-right:6px;"></i> สัดส่วนผลตอบแทน</div>
                <div style="position:relative; height:300px;">
                    <canvas id="distribution-chart"></canvas>
                </div>
            </div>
        </div>

        {{-- Call to Action --}}
        <div class="tp-card" style="padding:0; overflow:hidden;">
            <div style="padding:28px 24px; text-align:center; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 20%, transparent), transparent 74%);">
                <h3 style="font-size:clamp(22px,5vw,30px); font-weight:800; margin:0;">🎉 เริ่ม Stake TPIX วันนี้!</h3>
                <p style="font-size:14px; color:var(--ink2); margin:10px 0 18px;">รับผลตอบแทนสูงสุด 120% ต่อปี ด้วยระบบ TPIX Staking</p>
                <div style="display:flex; gap:12px; justify-content:center; flex-wrap:wrap;">
                    <button @click="window.location.href='{{ route('user.dashboard') }}'" class="tp-btn tp-btn-primary" style="font-size:15px; padding:13px 24px;">
                        <i class="fas fa-rocket"></i> เริ่มต้น Stake!
                    </button>
                    <button @click="resetSimulation(); startSimulation()" class="tp-btn" style="font-size:15px; padding:13px 24px;">
                        <i class="fas fa-redo"></i> คำนวณอีกครั้ง
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- JavaScript with Alpine.js + Chart.js --}}
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
/**
 * TPIX Staking Calculator Component
 * ===================================
 * คำนวณผลตอบแทนจากการ Staking TPIX
 *
 * Features:
 * - รองรับ Lock Period 30, 90, 180, 365 วัน
 * - APY แตกต่างกันตาม Lock Period (30% - 120%)
 * - Auto-compound option
 * - Real-time animation
 * - Charts visualization (Chart.js)
 *
 * @returns object Alpine.js component
 */
function tpixStakingCalculator() {
    return {
        // ===== State Management =====

        // การตั้งค่าการคำนวณ
        config: {
            stakingAmount: 10000,    // จำนวน TPIX ที่ stake
            lockPeriod: 365,         // ระยะเวลา lock (วัน)
            apy: 120,                // APY (%) - Max 120% สำหรับ 365 วัน
            autoCompound: true,      // auto-compound รางวัลหรือไม่
        },

        // ข้อมูลการจำลอง (ระหว่างคำนวณ)
        simulation: {
            currentDay: 0,           // วันที่กำลังคำนวณ
            currentStake: 0,         // TPIX ที่ stake ปัจจุบัน
            dailyReward: 0,          // รางวัลวันนี้
            totalRewards: 0,         // รางวัลสะสม
            totalTPIX: 0,            // TPIX รวมทั้งหมด
        },

        // ผลลัพธ์การคำนวณ
        results: {
            totalRewards: 0,         // รางวัลทั้งหมด
            roi: 0,                  // ผลตอบแทน (%)
            finalTPIX: 0,            // TPIX ทั้งหมดที่ได้
            dailyBreakdown: [],      // รายละเอียดแต่ละวัน
        },

        // สถานะของ UI
        isSimulating: false,         // กำลังจำลองหรือไม่
        showResults: false,          // แสดงผลลัพธ์หรือไม่

        // Chart instances
        growthChart: null,
        distributionChart: null,

        // ===== Lifecycle Methods =====

        // เริ่มต้น component
        init() {
            // อัพเดท APY ตาม lock period เริ่มต้น
            this.updateAPY();

            console.log('🪙 TPIX Staking Calculator initialized');
        },

        // ===== Methods =====

        /**
         * อัพเดท APY ตาม Lock Period
         *
         * ตามเอกสาร TPIX:
         * - 30 วัน = 30% APY
         * - 90 วัน = 60% APY
         * - 180 วัน = 90% APY
         * - 365 วัน = 120% APY (Max)
         */
        updateAPY() {
            const apyMap = {
                30: 30,      // 30 วัน = 30% APY
                90: 60,      // 90 วัน = 60% APY
                180: 90,     // 180 วัน = 90% APY
                365: 120,    // 365 วัน = 120% APY (Max)
            };

            this.config.apy = apyMap[this.config.lockPeriod] || 120;
        },

        // คำนวณรางวัลรายวัน
        calculateDailyReward(currentStake) {
            // Daily APY = APY / 365
            const dailyAPY = this.config.apy / 365 / 100;
            return currentStake * dailyAPY;
        },

        // เริ่มการจำลอง
        async startSimulation() {
            // ป้องกันการกดซ้ำ
            if (this.isSimulating) return;

            // Validation
            if (this.config.stakingAmount < 100) {
                if (window.showNotification) {
                    window.showNotification('กรุณากรอกจำนวน TPIX ขั้นต่ำ 100 TPIX', 'error');
                } else {
                    alert('กรุณากรอกจำนวน TPIX ขั้นต่ำ 100 TPIX');
                }
                return;
            }

            // เริ่มจำลอง
            this.isSimulating = true;
            this.showResults = false;

            // Reset simulation data
            this.simulation.currentDay = 0;
            this.simulation.currentStake = this.config.stakingAmount;
            this.simulation.dailyReward = 0;
            this.simulation.totalRewards = 0;
            this.simulation.totalTPIX = this.config.stakingAmount;

            // Reset results
            this.results.dailyBreakdown = [];

            // จำลองแต่ละวัน
            for (let day = 1; day <= this.config.lockPeriod; day++) {
                this.simulation.currentDay = day;

                // คำนวณรางวัลวันนี้
                const dailyReward = this.calculateDailyReward(this.simulation.currentStake);
                this.simulation.dailyReward = dailyReward;
                this.simulation.totalRewards += dailyReward;

                // Auto-compound: นำรางวัลกลับไป stake
                if (this.config.autoCompound) {
                    this.simulation.currentStake += dailyReward;
                }

                // คำนวณ TPIX รวม
                this.simulation.totalTPIX = this.simulation.currentStake +
                    (this.config.autoCompound ? 0 : this.simulation.totalRewards);

                // บันทึกข้อมูลรายวัน (เฉพาะบางวันเพื่อประสิทธิภาพ)
                if (day === 1 ||
                    day === 7 ||
                    day === 14 ||
                    day === 30 ||
                    day === 60 ||
                    day === 90 ||
                    day === 180 ||
                    day === 365 ||
                    day === this.config.lockPeriod) {

                    this.results.dailyBreakdown.push({
                        day: day,
                        stake: this.simulation.currentStake,
                        dailyReward: dailyReward,
                        totalRewards: this.simulation.totalRewards,
                        totalTPIX: this.simulation.totalTPIX,
                    });
                }

                // หน่วงเวลาเพื่อให้เห็น animation (ปรับความเร็วตาม lock period)
                const delay = this.config.lockPeriod > 90 ? 20 : 50;
                await this.sleep(delay);
            }

            // คำนวณผลลัพธ์สุดท้าย
            this.results.totalRewards = this.simulation.totalRewards;
            this.results.finalTPIX = this.simulation.totalTPIX;
            this.results.roi = ((this.results.finalTPIX - this.config.stakingAmount) /
                                this.config.stakingAmount) * 100;

            // แสดงผลลัพธ์
            this.isSimulating = false;
            this.showResults = true;

            // สร้าง charts
            await this.$nextTick();
            this.createCharts();
        },

        // สร้าง Charts (Chart.js)
        createCharts() {
            // Growth Chart
            const ctx1 = document.getElementById('growth-chart');
            if (this.growthChart) {
                this.growthChart.destroy();
            }

            this.growthChart = new Chart(ctx1, {
                type: 'line',
                data: {
                    labels: this.results.dailyBreakdown.map(d => `วันที่ ${d.day}`),
                    datasets: [
                        {
                            label: 'TPIX ที่ Stake',
                            data: this.results.dailyBreakdown.map(d => d.stake),
                            borderColor: 'rgb(147, 51, 234)',
                            backgroundColor: 'rgba(147, 51, 234, 0.1)',
                            tension: 0.4,
                            fill: true,
                        },
                        {
                            label: 'รางวัลสะสม',
                            data: this.results.dailyBreakdown.map(d => d.totalRewards),
                            borderColor: 'rgb(34, 197, 94)',
                            backgroundColor: 'rgba(34, 197, 94, 0.1)',
                            tension: 0.4,
                            fill: true,
                        },
                        {
                            label: 'TPIX รวม',
                            data: this.results.dailyBreakdown.map(d => d.totalTPIX),
                            borderColor: 'rgb(249, 115, 22)',
                            backgroundColor: 'rgba(249, 115, 22, 0.1)',
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
                                callback: value => this.formatNumber(value) + ' TPIX'
                            }
                        }
                    }
                }
            });

            // Distribution Chart
            const ctx2 = document.getElementById('distribution-chart');
            if (this.distributionChart) {
                this.distributionChart.destroy();
            }

            this.distributionChart = new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: ['TPIX เริ่มต้น', 'รางวัลที่ได้รับ'],
                    datasets: [{
                        data: [
                            this.config.stakingAmount,
                            this.results.totalRewards
                        ],
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
        },

        // รีเซ็ตการจำลอง
        resetSimulation() {
            this.isSimulating = false;
            this.showResults = false;

            this.simulation.currentDay = 0;
            this.simulation.currentStake = 0;
            this.simulation.dailyReward = 0;
            this.simulation.totalRewards = 0;
            this.simulation.totalTPIX = 0;

            this.results.totalRewards = 0;
            this.results.roi = 0;
            this.results.finalTPIX = 0;
            this.results.dailyBreakdown = [];

            // ทำลาย charts
            if (this.growthChart) {
                this.growthChart.destroy();
                this.growthChart = null;
            }
            if (this.distributionChart) {
                this.distributionChart.destroy();
                this.distributionChart = null;
            }
        },

        // คำนวณรางวัล (ไม่แสดง animation)
        calculateRewards() {
            // คำนวณแบบง่ายเพื่อแสดงตัวอย่าง
            const dailyAPY = this.config.apy / 365 / 100;
            let stake = this.config.stakingAmount;
            let totalRewards = 0;

            for (let day = 1; day <= this.config.lockPeriod; day++) {
                const dailyReward = stake * dailyAPY;
                totalRewards += dailyReward;

                if (this.config.autoCompound) {
                    stake += dailyReward;
                }
            }

            // อัพเดท simulation preview (ไม่แสดง animation)
            this.simulation.totalRewards = totalRewards;
            this.simulation.totalTPIX = stake + (this.config.autoCompound ? 0 : totalRewards);
        },

        // ===== Utility Methods =====

        // จัดรูปแบบตัวเลข (เพิ่ม comma)
        formatNumber(num) {
            return Math.floor(num).toLocaleString('en-US', {
                maximumFractionDigits: 2
            });
        },

        // หน่วงเวลา (สำหรับ animation)
        sleep(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        },
    };
}

// Export สำหรับใช้ใน Alpine.js
window.tpixStakingCalculator = tpixStakingCalculator;
</script>
@endpush
@endsection
