@extends('layouts.user-arrow-x')

@section('title', 'เครื่องคำนวณ TPIX Staking')

@section('content')
{{--
    TPIX Staking Calculator
    ======================
    หน้าคำนวณผลตอบแทนจากการ Staking เหรียญ TPIX
    รองรับ V3 Design System, Alpine.js, Dark Mode 100%

    Features:
    - คำนวณ staking rewards ตามเวลา
    - Lock period options (30, 60, 90, 180, 365 วัน)
    - APY แตกต่างกันตาม lock period
    - Auto-compound option
    - Real-time animation
    - Mobile responsive
--}}

<div class="container-fluid px-4 py-6"
     x-data="tpixStakingCalculator()"
     x-init="init()">

    {{-- Hero Header --}}
    <div class="mb-8 relative overflow-hidden rounded-3xl">
        {{-- Gradient Background with Pattern --}}
        <div class="relative bg-gradient-to-br from-purple-600 via-pink-600 to-orange-600 rounded-3xl p-8 shadow-2xl">
            {{-- Dots Pattern Overlay --}}
            <div class="absolute inset-0 opacity-20"
                 style="background-image: radial-gradient(circle, white 1px, transparent 1px);
                        background-size: 20px 20px;"></div>

            {{-- Content --}}
            <div class="relative z-10">
                <div class="flex items-center gap-6 mb-4">
                    {{-- Icon --}}
                    <div class="w-20 h-20 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center shadow-xl">
                        <i class="fas fa-coins text-4xl text-white"></i>
                    </div>

                    {{-- Title --}}
                    <div>
                        <h1 class="text-5xl font-black text-white drop-shadow-lg mb-2">
                            🪙 เครื่องคำนวณ TPIX Staking
                        </h1>
                        <p class="text-white/90 text-xl">
                            คำนวณผลตอบแทนจากการ Staking เหรียญ TPIX ของคุณ
                        </p>
                    </div>
                </div>

                {{-- Sub Description --}}
                <p class="text-white/80 text-lg">
                    💰 Stake เหรียญ TPIX และรับผลตอบแทนสูงสุด 365% ต่อปี!
                </p>
            </div>
        </div>
    </div>

    {{-- Control Panel --}}
    <div class="glass-fusion-card rounded-3xl shadow-2xl p-8 mb-8
                backdrop-blur-xl bg-white/80 dark:bg-gray-800/80
                border border-white/20 dark:border-gray-700/30">

        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-3">
            <i class="fas fa-sliders-h text-purple-600"></i>
            <span>ตั้งค่าการคำนวณ</span>
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

            {{-- จำนวน TPIX ที่ Stake --}}
            <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">
                    <i class="fas fa-coins text-purple-600 mr-2"></i>
                    จำนวน TPIX ที่ Stake
                </label>
                <div class="relative">
                    <input type="number"
                           x-model.number="config.stakingAmount"
                           @input="calculateRewards()"
                           step="100"
                           min="0"
                           class="w-full pl-4 pr-16 py-4 text-lg font-bold
                                  bg-white dark:bg-gray-700
                                  border-2 border-purple-300 dark:border-purple-600
                                  rounded-xl
                                  focus:ring-4 focus:ring-purple-500/20 focus:border-purple-500
                                  text-gray-900 dark:text-white
                                  transition-all">
                    <div class="absolute right-4 top-1/2 -translate-y-1/2 text-purple-600 dark:text-purple-400 font-bold">
                        TPIX
                    </div>
                </div>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    ขั้นต่ำ: 100 TPIX
                </p>
            </div>

            {{-- Lock Period --}}
            <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">
                    <i class="fas fa-lock text-blue-600 mr-2"></i>
                    ระยะเวลา Lock
                </label>
                <select x-model="config.lockPeriod"
                        @change="updateAPY(); calculateRewards()"
                        class="w-full px-4 py-4 text-lg font-bold
                               bg-white dark:bg-gray-700
                               border-2 border-blue-300 dark:border-blue-600
                               rounded-xl
                               focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500
                               text-gray-900 dark:text-white
                               transition-all">
                    <option value="30">30 วัน (APY 36%)</option>
                    <option value="60">60 วัน (APY 72%)</option>
                    <option value="90">90 วัน (APY 120%)</option>
                    <option value="180">180 วัน (APY 240%)</option>
                    <option value="365">365 วัน (APY 365%)</option>
                </select>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    ระยะเวลาที่ล็อค TPIX
                </p>
            </div>

            {{-- APY (Auto-calculated) --}}
            <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">
                    <i class="fas fa-chart-line text-green-600 mr-2"></i>
                    อัตราผลตอบแทน (APY)
                </label>
                <div class="relative">
                    <div class="w-full px-4 py-4 text-lg font-bold
                                bg-gradient-to-r from-green-50 to-emerald-50
                                dark:from-green-900/20 dark:to-emerald-900/20
                                border-2 border-green-300 dark:border-green-600
                                rounded-xl
                                text-green-700 dark:text-green-400
                                flex items-center justify-between">
                        <span x-text="config.apy"></span>
                        <span class="text-sm">%</span>
                    </div>
                </div>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    คำนวณอัตโนมัติตาม Lock Period
                </p>
            </div>

            {{-- Auto Compound --}}
            <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">
                    <i class="fas fa-sync-alt text-pink-600 mr-2"></i>
                    Auto-Compound
                </label>
                <div class="relative">
                    <label class="relative inline-flex items-center cursor-pointer w-full">
                        <input type="checkbox"
                               x-model="config.autoCompound"
                               @change="calculateRewards()"
                               class="sr-only peer">
                        <div class="w-full h-14
                                    bg-gray-300 dark:bg-gray-600
                                    rounded-xl
                                    peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-pink-500/20
                                    peer-checked:bg-gradient-to-r peer-checked:from-pink-500 peer-checked:to-purple-600
                                    transition-all
                                    relative
                                    shadow-inner">
                            <div class="absolute top-2 left-2
                                        bg-white
                                        w-10 h-10
                                        rounded-lg
                                        shadow-lg
                                        peer-checked:translate-x-[calc(100%-2.5rem)]
                                        transition-transform
                                        flex items-center justify-center">
                                <i class="fas"
                                   :class="config.autoCompound ? 'fa-check text-pink-600' : 'fa-times text-gray-400'"></i>
                            </div>
                            <div class="absolute inset-0 flex items-center justify-center text-white font-bold">
                                <span x-show="config.autoCompound">เปิดใช้งาน</span>
                                <span x-show="!config.autoCompound" class="text-gray-600">ปิดใช้งาน</span>
                            </div>
                        </div>
                    </label>
                </div>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    นำรางวัลกลับไป stake อัตโนมัติ
                </p>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="mt-8 flex gap-4">
            <button @click="startSimulation()"
                    :disabled="isSimulating"
                    class="flex-1 group relative overflow-hidden
                           px-8 py-4
                           bg-gradient-to-r from-purple-600 via-pink-600 to-orange-600
                           hover:from-purple-700 hover:via-pink-700 hover:to-orange-700
                           text-white font-bold text-lg
                           rounded-xl shadow-lg
                           hover:shadow-2xl hover:scale-[1.02]
                           disabled:opacity-50 disabled:cursor-not-allowed
                           transition-all duration-300">

                {{-- Shine Effect --}}
                <div class="absolute inset-0
                            bg-gradient-to-r from-transparent via-white/20 to-transparent
                            -translate-x-full group-hover:translate-x-full
                            transition-transform duration-1000"></div>

                {{-- Button Content --}}
                <span class="relative z-10 flex items-center justify-center gap-2">
                    <i class="fas" :class="isSimulating ? 'fa-spinner fa-spin' : 'fa-play'"></i>
                    <span x-text="isSimulating ? 'กำลังคำนวณ...' : '▶️ เริ่มคำนวณ'"></span>
                </span>
            </button>

            <button @click="resetSimulation()"
                    class="px-8 py-4
                           bg-gray-600 hover:bg-gray-700
                           dark:bg-gray-700 dark:hover:bg-gray-600
                           text-white font-bold text-lg
                           rounded-xl shadow-lg
                           hover:shadow-xl
                           transition-all duration-300">
                <i class="fas fa-redo mr-2"></i>
                รีเซ็ต
            </button>
        </div>
    </div>

    {{-- Animation Container --}}
    <div x-show="isSimulating"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-cloak
         class="mb-8">

        {{-- Current Progress Card --}}
        <div class="relative overflow-hidden rounded-3xl mb-6">
            <div class="bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 p-8">
                <div class="text-center text-white">
                    <h2 class="text-2xl font-bold mb-4">กำลังคำนวณ...</h2>
                    <div class="text-7xl font-black mb-3">
                        วันที่ <span x-text="simulation.currentDay"></span>
                    </div>
                    <div class="text-xl opacity-90">
                        จาก <span x-text="config.lockPeriod"></span> วัน
                    </div>

                    {{-- Progress Bar --}}
                    <div class="mt-6 w-full bg-white/20 rounded-full h-4 overflow-hidden">
                        <div class="h-full bg-white rounded-full transition-all duration-500"
                             :style="`width: ${(simulation.currentDay / config.lockPeriod) * 100}%`"></div>
                    </div>
                    <div class="mt-2 text-sm opacity-75">
                        ความคืบหน้า: <span x-text="Math.floor((simulation.currentDay / config.lockPeriod) * 100)"></span>%
                    </div>
                </div>
            </div>
        </div>

        {{-- Real-time Stats Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

            {{-- TPIX ที่ Stake --}}
            <div class="group perspective-1000">
                <div class="relative transform-gpu transition-all duration-500 group-hover:scale-105">
                    {{-- Glow Effect --}}
                    <div class="absolute inset-0 bg-gradient-to-br from-purple-500 to-pink-600
                                rounded-2xl blur-xl opacity-50 group-hover:opacity-75
                                transition-opacity"></div>

                    {{-- Card --}}
                    <div class="relative glass-fusion-card rounded-2xl p-6
                                backdrop-blur-xl bg-white/80 dark:bg-gray-800/80
                                border border-white/20 dark:border-gray-700/30
                                shadow-2xl">
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-600
                                        rounded-xl flex items-center justify-center shadow-lg">
                                <i class="fas fa-coins text-white text-xl"></i>
                            </div>
                        </div>
                        <div class="text-3xl font-bold text-gray-900 dark:text-white mb-1">
                            <span x-text="formatNumber(simulation.currentStake)"></span>
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            TPIX ที่ Stake
                        </div>
                    </div>
                </div>
            </div>

            {{-- รางวัลวันนี้ --}}
            <div class="group perspective-1000">
                <div class="relative transform-gpu transition-all duration-500 group-hover:scale-105">
                    <div class="absolute inset-0 bg-gradient-to-br from-green-500 to-emerald-600
                                rounded-2xl blur-xl opacity-50 group-hover:opacity-75
                                transition-opacity"></div>

                    <div class="relative glass-fusion-card rounded-2xl p-6
                                backdrop-blur-xl bg-white/80 dark:bg-gray-800/80
                                border border-white/20 dark:border-gray-700/30
                                shadow-2xl">
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600
                                        rounded-xl flex items-center justify-center shadow-lg">
                                <i class="fas fa-gift text-white text-xl"></i>
                            </div>
                        </div>
                        <div class="text-3xl font-bold text-gray-900 dark:text-white mb-1">
                            <span x-text="formatNumber(simulation.dailyReward)"></span>
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            รางวัลวันนี้
                        </div>
                    </div>
                </div>
            </div>

            {{-- รางวัลสะสม --}}
            <div class="group perspective-1000">
                <div class="relative transform-gpu transition-all duration-500 group-hover:scale-105">
                    <div class="absolute inset-0 bg-gradient-to-br from-blue-500 to-cyan-600
                                rounded-2xl blur-xl opacity-50 group-hover:opacity-75
                                transition-opacity"></div>

                    <div class="relative glass-fusion-card rounded-2xl p-6
                                backdrop-blur-xl bg-white/80 dark:bg-gray-800/80
                                border border-white/20 dark:border-gray-700/30
                                shadow-2xl">
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-cyan-600
                                        rounded-xl flex items-center justify-center shadow-lg">
                                <i class="fas fa-chart-line text-white text-xl"></i>
                            </div>
                        </div>
                        <div class="text-3xl font-bold text-gray-900 dark:text-white mb-1">
                            <span x-text="formatNumber(simulation.totalRewards)"></span>
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            รางวัลสะสมทั้งหมด
                        </div>
                    </div>
                </div>
            </div>

            {{-- TPIX รวม --}}
            <div class="group perspective-1000">
                <div class="relative transform-gpu transition-all duration-500 group-hover:scale-105">
                    <div class="absolute inset-0 bg-gradient-to-br from-orange-500 to-red-600
                                rounded-2xl blur-xl opacity-50 group-hover:opacity-75
                                transition-opacity animate-pulse"></div>

                    <div class="relative glass-fusion-card rounded-2xl p-6
                                backdrop-blur-xl bg-white/80 dark:bg-gray-800/80
                                border border-white/20 dark:border-gray-700/30
                                shadow-2xl">
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-red-600
                                        rounded-xl flex items-center justify-center shadow-lg">
                                <i class="fas fa-wallet text-white text-xl"></i>
                            </div>
                        </div>
                        <div class="text-3xl font-bold text-gray-900 dark:text-white mb-1">
                            <span x-text="formatNumber(simulation.totalTPIX)"></span>
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            TPIX รวมทั้งหมด
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Results Container --}}
    <div x-show="showResults"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-cloak
         class="space-y-8">

        {{-- Summary Stats --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

            {{-- เงินลงทุนเริ่มต้น --}}
            <div class="text-center">
                <div class="glass-fusion-card rounded-2xl p-6
                            backdrop-blur-xl bg-white/80 dark:bg-gray-800/80
                            border border-white/20 dark:border-gray-700/30
                            shadow-2xl">
                    <div class="text-5xl mb-3">💰</div>
                    <div class="text-3xl font-bold text-purple-600 dark:text-purple-400 mb-2">
                        <span x-text="formatNumber(config.stakingAmount)"></span>
                    </div>
                    <div class="text-sm font-semibold text-gray-600 dark:text-gray-400">
                        TPIX ที่ Stake
                    </div>
                </div>
            </div>

            {{-- รางวัลทั้งหมด --}}
            <div class="text-center">
                <div class="glass-fusion-card rounded-2xl p-6
                            backdrop-blur-xl bg-white/80 dark:bg-gray-800/80
                            border border-white/20 dark:border-gray-700/30
                            shadow-2xl">
                    <div class="text-5xl mb-3">🎁</div>
                    <div class="text-3xl font-bold text-green-600 dark:text-green-400 mb-2">
                        <span x-text="formatNumber(results.totalRewards)"></span>
                    </div>
                    <div class="text-sm font-semibold text-gray-600 dark:text-gray-400">
                        รางวัลทั้งหมด
                    </div>
                </div>
            </div>

            {{-- ROI --}}
            <div class="text-center">
                <div class="glass-fusion-card rounded-2xl p-6
                            backdrop-blur-xl bg-white/80 dark:bg-gray-800/80
                            border border-white/20 dark:border-gray-700/30
                            shadow-2xl">
                    <div class="text-5xl mb-3">📈</div>
                    <div class="text-3xl font-bold text-blue-600 dark:text-blue-400 mb-2">
                        <span x-text="formatNumber(results.roi)"></span>%
                    </div>
                    <div class="text-sm font-semibold text-gray-600 dark:text-gray-400">
                        ผลตอบแทน (ROI)
                    </div>
                </div>
            </div>

            {{-- TPIX ที่ได้รับ --}}
            <div class="text-center">
                <div class="glass-fusion-card rounded-2xl p-6
                            backdrop-blur-xl bg-white/80 dark:bg-gray-800/80
                            border border-white/20 dark:border-gray-700/30
                            shadow-2xl">
                    <div class="text-5xl mb-3">🚀</div>
                    <div class="text-3xl font-bold text-orange-600 dark:text-orange-400 mb-2">
                        <span x-text="formatNumber(results.finalTPIX)"></span>
                    </div>
                    <div class="text-sm font-semibold text-gray-600 dark:text-gray-400">
                        TPIX ทั้งหมดที่ได้
                    </div>
                </div>
            </div>
        </div>

        {{-- Detailed Breakdown Table --}}
        <div class="glass-fusion-card rounded-3xl shadow-2xl overflow-hidden
                    backdrop-blur-xl bg-white/80 dark:bg-gray-800/80
                    border border-white/20 dark:border-gray-700/30">

            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                    <i class="fas fa-table text-purple-600"></i>
                    <span>รายละเอียดรางวัลแต่ละวัน</span>
                </h3>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gradient-to-r from-purple-600 via-pink-600 to-orange-600">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                                วันที่
                            </th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-white uppercase tracking-wider">
                                TPIX ที่ Stake
                            </th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-white uppercase tracking-wider">
                                รางวัลวันนี้
                            </th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-white uppercase tracking-wider">
                                รางวัลสะสม
                            </th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-white uppercase tracking-wider">
                                TPIX รวม
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <template x-for="(day, index) in results.dailyBreakdown" :key="index">
                            <tr class="hover:bg-purple-50 dark:hover:bg-purple-900/20 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-white">
                                    วันที่ <span x-text="day.day"></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-purple-600 dark:text-purple-400 font-semibold">
                                    <span x-text="formatNumber(day.stake)"></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-600 dark:text-green-400 font-semibold">
                                    <span x-text="formatNumber(day.dailyReward)"></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-blue-600 dark:text-blue-400 font-semibold">
                                    <span x-text="formatNumber(day.totalRewards)"></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-orange-600 dark:text-orange-400 font-bold">
                                    <span x-text="formatNumber(day.totalTPIX)"></span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot class="bg-gradient-to-r from-green-100 to-blue-100 dark:from-green-900/20 dark:to-blue-900/20">
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-right text-sm font-bold text-gray-900 dark:text-white">
                                รวมทั้งหมด:
                            </td>
                            <td class="px-6 py-4 text-right text-lg font-bold text-green-600 dark:text-green-400">
                                <span x-text="formatNumber(results.totalRewards)"></span>
                            </td>
                            <td class="px-6 py-4 text-right text-lg font-bold text-orange-600 dark:text-orange-400">
                                <span x-text="formatNumber(results.finalTPIX)"></span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Charts --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- Growth Chart --}}
            <div class="glass-fusion-card rounded-2xl p-6 shadow-2xl
                        backdrop-blur-xl bg-white/80 dark:bg-gray-800/80
                        border border-white/20 dark:border-gray-700/30">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-3">
                    <i class="fas fa-chart-area text-purple-600"></i>
                    <span>กราฟการเติบโตของ TPIX</span>
                </h3>
                <div style="position: relative; height: 300px;">
                    <canvas id="growth-chart"></canvas>
                </div>
            </div>

            {{-- Rewards Distribution --}}
            <div class="glass-fusion-card rounded-2xl p-6 shadow-2xl
                        backdrop-blur-xl bg-white/80 dark:bg-gray-800/80
                        border border-white/20 dark:border-gray-700/30">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-3">
                    <i class="fas fa-chart-pie text-pink-600"></i>
                    <span>สัดส่วนผลตอบแทน</span>
                </h3>
                <div style="position: relative; height: 300px;">
                    <canvas id="distribution-chart"></canvas>
                </div>
            </div>
        </div>

        {{-- Call to Action --}}
        <div class="relative overflow-hidden rounded-3xl">
            <div class="bg-gradient-to-r from-purple-600 via-pink-600 to-orange-600 p-8 text-center">
                <div class="relative z-10">
                    <h3 class="text-4xl font-black text-white mb-4">
                        🎉 เริ่ม Stake TPIX วันนี้!
                    </h3>
                    <p class="text-xl text-white/90 mb-6">
                        รับผลตอบแทนสูงสุด 365% ต่อปี ด้วยระบบ TPIX Staking
                    </p>
                    <div class="flex gap-4 justify-center flex-wrap">
                        <button @click="window.location.href='{{ route('user.dashboard') }}'"
                                class="px-8 py-4
                                       bg-white text-purple-600
                                       font-bold text-lg
                                       rounded-xl shadow-lg
                                       hover:bg-gray-100 hover:scale-105
                                       transition-all duration-300">
                            <i class="fas fa-rocket mr-2"></i>
                            เริ่มต้น Stake!
                        </button>
                        <button @click="resetSimulation(); startSimulation()"
                                class="px-8 py-4
                                       bg-purple-800 hover:bg-purple-900
                                       text-white font-bold text-lg
                                       rounded-xl shadow-lg
                                       hover:scale-105
                                       transition-all duration-300">
                            <i class="fas fa-redo mr-2"></i>
                            คำนวณอีกครั้ง
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Custom Styles --}}
<style>
/**
 * Custom Styles สำหรับ TPIX Staking Calculator
 * รองรับ Dark Mode และ Animations
 */

/* Perspective สำหรับ 3D effects */
.perspective-1000 {
    perspective: 1000px;
}

/* Glass Fusion Card Effect */
.glass-fusion-card {
    background: rgba(255, 255, 255, 0.8);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
}

.dark .glass-fusion-card {
    background: rgba(31, 41, 55, 0.8);
}

/* Hide elements with x-cloak until Alpine.js loads */
[x-cloak] {
    display: none !important;
}

/* Smooth number transitions */
.number-transition {
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Print styles */
@media print {
    .no-print {
        display: none !important;
    }

    body {
        background: white !important;
    }

    .glass-fusion-card {
        background: white !important;
        border: 1px solid #e5e7eb !important;
    }
}
</style>

{{-- JavaScript with Alpine.js --}}
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
/**
 * TPIX Staking Calculator Component
 * ===================================
 * คำนวณผลตอบแทนจากการ Staking TPIX
 *
 * Features:
 * - รองรับ Lock Period 30, 60, 90, 180, 365 วัน
 * - APY แตกต่างกันตาม Lock Period
 * - Auto-compound option
 * - Real-time animation
 * - Charts visualization
 *
 * @returns {object} Alpine.js component
 */
function tpixStakingCalculator() {
    return {
        // ===== State Management =====

        /**
         * การตั้งค่าการคำนวณ
         */
        config: {
            stakingAmount: 10000,    // จำนวน TPIX ที่ stake
            lockPeriod: 365,         // ระยะเวลา lock (วัน)
            apy: 365,                // APY (%)
            autoCompound: true,      // auto-compound รางวัลหรือไม่
        },

        /**
         * ข้อมูลการจำลอง (ระหว่างคำนวณ)
         */
        simulation: {
            currentDay: 0,           // วันที่กำลังคำนวณ
            currentStake: 0,         // TPIX ที่ stake ปัจจุบัน
            dailyReward: 0,          // รางวัลวันนี้
            totalRewards: 0,         // รางวัลสะสม
            totalTPIX: 0,            // TPIX รวมทั้งหมด
        },

        /**
         * ผลลัพธ์การคำนวณ
         */
        results: {
            totalRewards: 0,         // รางวัลทั้งหมด
            roi: 0,                  // ผลตอบแทน (%)
            finalTPIX: 0,            // TPIX ทั้งหมดที่ได้
            dailyBreakdown: [],      // รายละเอียดแต่ละวัน
        },

        /**
         * สถานะของ UI
         */
        isSimulating: false,         // กำลังจำลองหรือไม่
        showResults: false,          // แสดงผลลัพธ์หรือไม่

        /**
         * Chart instances
         */
        growthChart: null,
        distributionChart: null,

        // ===== Lifecycle Methods =====

        /**
         * เริ่มต้น component
         */
        init() {
            // อัพเดท APY ตาม lock period เริ่มต้น
            this.updateAPY();

            console.log('🪙 TPIX Staking Calculator initialized');
        },

        // ===== Methods =====

        /**
         * อัพเดท APY ตาม Lock Period
         */
        updateAPY() {
            const apyMap = {
                30: 36,      // 30 วัน = 36% APY
                60: 72,      // 60 วัน = 72% APY
                90: 120,     // 90 วัน = 120% APY
                180: 240,    // 180 วัน = 240% APY
                365: 365,    // 365 วัน = 365% APY
            };

            this.config.apy = apyMap[this.config.lockPeriod] || 365;
        },

        /**
         * คำนวณรางวัลรายวัน
         */
        calculateDailyReward(currentStake) {
            // Daily APY = APY / 365
            const dailyAPY = this.config.apy / 365 / 100;
            return currentStake * dailyAPY;
        },

        /**
         * เริ่มการจำลอง
         */
        async startSimulation() {
            // ป้องกันการกดซ้ำ
            if (this.isSimulating) return;

            // Validation
            if (this.config.stakingAmount < 100) {
                alert('กรุณากรอกจำนวน TPIX ขั้นต่ำ 100 TPIX');
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

        /**
         * สร้าง Charts
         */
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

        /**
         * รีเซ็ตการจำลอง
         */
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

        /**
         * คำนวณรางวัล (ไม่แสดง animation)
         */
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

        /**
         * จัดรูปแบบตัวเลข (เพิ่ม comma)
         */
        formatNumber(num) {
            return Math.floor(num).toLocaleString('en-US', {
                maximumFractionDigits: 2
            });
        },

        /**
         * หน่วงเวลา (สำหรับ animation)
         */
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
